<?php

namespace App\Services\Sap;

use App\Models\Accountability;
use App\Models\AccountabilityDetail;
use App\Models\Management;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Arma el payload del asiento preliminar (JournalVoucher) que se envía a SAP B1.
 *
 * El mismo payload sirve para los dos modos de exportación: el Service Layer lo
 * recibe tal cual y el servicio DI API lo traduce a objetos COM.
 *
 * NOTA: AccountabilityController::HandleFormatLineReport duplica esta lógica
 * para la vista previa en PDF. Si cambian las reglas de cálculo, hay que
 * actualizar ambos lados.
 */
class JournalVoucherPayloadBuilder
{
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

        $documents = AccountabilityDetail::with('document.detail', 'field.document_field')
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
        $debit = $total_debit - $total_credit;

        $export_profile = Profile::where('id', $accountability->profile_id)->first();

        $closing_line = [
            'AccountCode' => $accountability->account_code,
            'Debit' => 0,
            'Credit' => $debit,
            'LineMemo' => $accountability->description,
        ];

        // 'sin_empleado' no tiene un CardCode real que mandar (account_code es
        // una cuenta contable, no un socio de negocio): se omite ShortName.
        if (!$export_profile || !$export_profile->sin_empleado) {
            $closing_line['ShortName'] = $accountability->employee_code;
        }

        $journal_entry_lines[] = $closing_line;

        $journalEntry = [
            'Memo' => $accountability->description,
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
        $journal = [];
        $detail_lines = [];
        $total = 0;
        $amount_line = $document_line->amount;
        $rate_percentage = $document_line->document->tasas / 100;
        $ice_percentage = $document_line->document->ice / 100;

        foreach ($document_line->field as $field) {
            $amount_line += ($field->document_field->type_calculation == 'Credito' ? 1 : -1) * $field->value;
            $detail_lines[] = [
                'AccountCode' => $field->document_field->account,
                'Debit' => $field->document_field->type_calculation == 'Credito' ? 0 : $field->value,
                'Credit' => $field->document_field->type_calculation == 'Credito' ? $field->value : 0,
                'LineMemo' => $document_line->concept,
            ];
        }

        $total_ice = $document_line->ice_status ? $document_line->ice : $amount_line * $ice_percentage;
        $total_tasas = $document_line->tasas_status ? $document_line->tasas : $amount_line * $rate_percentage;

        $max_exento = 0;
        foreach ($document_line->document->detail as $detail) {
            $exento_percentage = $detail->exento / 100;
            $total_excento = $document_line->exento_status ? $document_line->exento : $amount_line * $exento_percentage;
            $max_exento = $total_excento > $max_exento ? $total_excento : $max_exento;

            $amount = ($total_excento == 0 ? $amount_line : $total_excento) + $total_tasas + $total_ice;

            $operation = $detail->type_calculation == 'Grossing Up' ? 1 : -1;
            $percentage = $detail->percentage / 100;
            $total_percentage = $amount * $percentage;
            $total += $operation * $total_percentage;
            $detail_lines[] = [
                'AccountCode' => $detail->account,
                'Debit' => $document_line->document->type_calculation == 'Grossing Up' ? 0 : $total_percentage,
                'Credit' => $document_line->document->type_calculation == 'Grossing Up' ? $total_percentage : 0,
                'LineMemo' => $document_line->concept,
            ];
        }
        $total += $amount_line;

        $udfs = $this->buildUdfs($document_line, $max_exento);

        $journal[] = array_merge([
            'AccountCode' => $document_line->account,
            'Debit' => $total,
            'Credit' => 0,
            'LineMemo' => $document_line->concept,
        ], $udfs);

        foreach ($detail_lines as &$d_line) {
            $d_line = array_merge($d_line, $udfs);
        }

        return array_merge($journal, $detail_lines);
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

    private function udfName(string $field): ?string
    {
        $name = trim((string) $this->management->where('name', $field)->first()?->value);

        return $name === '' ? null : $name;
    }
}
