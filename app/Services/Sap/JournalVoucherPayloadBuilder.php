<?php

namespace App\Services\Sap;

use App\Models\Accountability;
use App\Models\AccountabilityDetail;
use App\Models\Management;
use App\Models\Profile;
use App\Models\User;
use App\Services\Accountability\ExpenseDistribution;
use Illuminate\Support\Collection;

/**
 * Arma el payload del asiento preliminar (JournalVoucher) que se envía a SAP B1.
 *
 * El mismo payload sirve para los dos modos de exportación: el Service Layer lo
 * recibe tal cual y el servicio DI API lo traduce a objetos COM.
 *
 * Los montos de cada documento salen de DocumentLineCalculator, que también usa
 * la vista previa en PDF (AccountabilityController::HandleFormatLineReport).
 */
class JournalVoucherPayloadBuilder
{
    /** Largo de OJDT.Memo y JDT1.LineMemo en SAP B1. */
    private const MEMO_LENGTH = 50;

    private Collection $management;

    public function __construct(Collection $management = null)
    {
        $this->management = $management ?? Management::where('group', 'accountability_detail')->get();
    }

    /**
     * @return array ['JournalVoucher' => ['JournalEntry' => [...]]]
     */
    public function build(Accountability $accountability): array
    {
        $journal_entry_lines = [];

        $documents = AccountabilityDetail::with('document.detail', 'field.document_field', 'lines')
            ->where('accountability_id', $accountability->id)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($documents as $document) {
            $journal_entry_lines = array_merge($journal_entry_lines, $this->formatLine($document));
        }

        $total_debit = 0;
        $total_credit = 0;
        foreach ($journal_entry_lines as $line) {
            $total_debit += (float) $line['Debit'];
            $total_credit += (float) $line['Credit'];
        }
        $debit = round($total_debit - $total_credit, 2);

        $export_profile = Profile::where('id', $accountability->profile_id)->first();

        $closing_line = [
            'AccountCode' => $accountability->account_code,
            'Debit' => 0,
            'Credit' => $debit,
            'LineMemo' => $this->memo($accountability->description),
        ];

        // 'sin_empleado' no tiene un CardCode real que mandar (account_code es
        // una cuenta contable, no un socio de negocio): se omite ShortName.
        if (!$export_profile || !$export_profile->sin_empleado) {
            $closing_line['ShortName'] = $accountability->employee_code;
        }

        $journal_entry_lines[] = $closing_line;

        $journalEntry = [
            'Memo' => $this->memo($accountability->description),
            'ReferenceDate' => $accountability->end_date,
            'TaxDate' => $accountability->end_date,
            'DueDate' => $accountability->end_date,
            'JournalEntryLines' => $journal_entry_lines,
        ];

        $userFieldKey = $this->udfName('user_field');
        if ($userFieldKey) {
            $exportUser = User::find($accountability->user_id);
            $journalEntry[$userFieldKey] = $exportUser?->name;
        }

        return [
            'JournalVoucher' => [
                'JournalEntry' => $journalEntry,
            ],
        ];
    }

    /**
     * Tipos de los UDF de línea, para que el servicio DI API castee antes de asignar.
     */
    public function udfTypes(): array
    {
        return UdfTypes::map($this->management);
    }

    /**
     * Líneas contables generadas por un documento de la rendición.
     */
    public function formatLine($document_line): array
    {
        $result = (new DocumentLineCalculator())->calculate($document_line);
        $udfs = $this->buildUdfs($document_line, $result['exento']);
        // Glosa de la línea: el comentario que el usuario cargó en el documento.
        $lineMemo = $this->memo($document_line->concept);

        // Gasto: una línea, o una por cada línea del reparto con su cuenta y dimensiones.
        $split = ExpenseDistribution::expenseLines($document_line, $result['expense']);
        if ($split === null) {
            $journal = [array_merge([
                'AccountCode' => $document_line->account,
                'Debit' => $result['expense'],
                'Credit' => 0,
                'LineMemo' => $lineMemo,
            ], $udfs)];
        } else {
            $journal = array_map(fn ($line) => array_merge([
                'AccountCode' => $line['account'],
                'Debit' => $line['amount'],
                'Credit' => 0,
                'LineMemo' => $this->memo($line['concept']) ?? $lineMemo,
            ], $udfs, $line['costing']), $split);
        }

        foreach ($result['lines'] as $line) {
            $journal[] = array_merge([
                'AccountCode' => $line['account'],
                'Debit' => $line['debit'],
                'Credit' => $line['credit'],
                'LineMemo' => $lineMemo,
            ], $udfs);
        }

        return $journal;
    }

    /**
     * Dimensiones y campos de usuario que se repiten en todas las líneas del documento.
     */
    private function buildUdfs($document_line, $max_exento): array
    {
        $udfs = [
            'ProjectCode' => $document_line->project_code,
            'CostingCode' => $document_line->distribution_rule_one,
            'CostingCode2' => $document_line->distribution_rule_second,
            'CostingCode3' => $document_line->distribution_rule_three,
            'CostingCode4' => $document_line->distribution_rule_four,
            'CostingCode5' => $document_line->distribution_rule_five,
        ];

        $sources = [
            'date' => $document_line->date,
            'document_number' => $document_line->document_number,
            'authorization_number' => $document_line->authorization_number,
            'cuf' => $document_line->cuf,
            'control_code' => $document_line->control_code,
            'business_name' => $document_line->business_name,
            'nit' => $document_line->nit,
            'amount' => $document_line->amount,
            'discount' => $document_line->discount,
            'excento' => $max_exento,
            'rate' => $document_line->rate,
            'gift_card' => $document_line->gift_card,
            'ice' => $document_line->ice,
            'document_type' => $document_line->document->type_document_sap,
        ];

        foreach ($sources as $field => $value) {
            // Un parámetro sin nombre de UDF se omite: enviar una clave vacía
            // rompe la exportación completa.
            if ($udf = $this->udfName($field)) {
                $udfs[$udf] = UdfTypes::normalize($field, $value);
            }
        }

        return $udfs;
    }

    /**
     * Comentarios del asiento (OJDT.Memo / JDT1.LineMemo): SAP los guarda en 50
     * caracteres y rechaza o descarta un texto más largo.
     */
    private function memo(?string $text): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));

        return $text === '' ? null : mb_substr($text, 0, self::MEMO_LENGTH);
    }

    private function udfName(string $field): ?string
    {
        $name = trim((string) $this->management->where('name', $field)->first()?->value);

        return $name === '' ? null : $name;
    }
}
