<?php

namespace App\Services\Sap;

use App\Models\Accountability;
use App\Models\AccountabilityDetail;
use App\Services\Accountability\ExpenseDistribution;

/**
 * Arma las entradas de mercancías de inventario en borrador (Drafts con
 * DocObjectCode = oInventoryGenEntry, tabla OIGN) de las facturas con ítems.
 *
 * Cada línea tiene como cuenta de contrapartida la cuenta del gasto del documento:
 * el asiento de la rendición la carga al Debe y la entrada de mercancías al Haber,
 * así que al contabilizar ambos el gasto queda en cero y lo comprado como inventario.
 */
class InventoryReceiptPayloadBuilder
{
    public const DOC_OBJECT_CODE = 'oInventoryGenEntry';

    /**
     * Borradores de la rendición, uno por documento con ítems.
     *
     * @return array<int, array> payload por id del documento de la rendición
     */
    public function build(Accountability $accountability, string $reference): array
    {
        $documents = AccountabilityDetail::with('document', 'items')
            ->where('accountability_id', $accountability->id)
            ->orderBy('id')
            ->get();

        $drafts = [];
        foreach ($documents as $document_line) {
            if ($document_line->document?->items_status && $document_line->items->isNotEmpty()) {
                $drafts[$document_line->id] = $this->draft($accountability, $document_line, $reference);
            }
        }

        return $drafts;
    }

    /**
     * Borrador de un documento. Recibe objetos simples para poder probarse sin base de datos.
     */
    public function draft($accountability, $document_line, string $reference): array
    {
        $dimensions = [];
        foreach (ExpenseDistribution::DIMENSION_FIELDS as $dim => $field) {
            if ($document_line->{$field}) {
                $dimensions[ExpenseDistribution::COSTING_FIELDS[$dim]] = $document_line->{$field};
            }
        }
        if ($document_line->project_code) {
            $dimensions['ProjectCode'] = $document_line->project_code;
        }

        $lines = [];
        foreach ($document_line->items as $item) {
            $lines[] = array_merge([
                'ItemCode' => $item->item_code,
                'Quantity' => (float) $item->quantity,
                'UnitPrice' => (float) $item->price,
                'WarehouseCode' => $item->warehouse_code,
                // Contrapartida: la misma cuenta del gasto del documento
                'AccountCode' => $document_line->account,
            ], $dimensions);
        }

        $name = $document_line->document->name ?? 'Documento';
        $comments = trim("Rendición #{$accountability->id} · {$name} N° {$document_line->document_number}"
            .($document_line->business_name ? " · {$document_line->business_name}" : ''));

        return [
            'DocObjectCode' => self::DOC_OBJECT_CODE,
            // Misma fecha contable que el asiento de la rendición; la fecha del documento va como TaxDate
            'DocDate' => $accountability->end_date,
            'TaxDate' => $document_line->date,
            'Reference2' => $reference,
            'Comments' => mb_substr($comments, 0, 254),
            'JournalMemo' => mb_substr("Rendición {$accountability->id} - {$name}", 0, 50),
            'DocumentLines' => $lines,
        ];
    }
}
