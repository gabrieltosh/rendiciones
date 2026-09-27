<?php

namespace App\Services\Sap;

/**
 * Calcula las líneas contables de un documento de la rendición (AccountabilityDetail)
 * a partir de la configuración de su Document (prorrateo y campos adicionales).
 *
 * Única fuente del cálculo: la usan el export a SAP (JournalVoucherPayloadBuilder)
 * y la vista previa del asiento (AccountabilityController::HandleFormatLineReport).
 *
 *  1. importe      = monto del documento ± campos adicionales (Crédito suma, Débito resta).
 *  2. bruto        = importe / (1 − Σ% de las líneas Grossing Up con "calculation").
 *                    Es el grossing up de las retenciones: el usuario registra lo
 *                    pagado (líquido) y las retenciones se calculan sobre el bruto.
 *  3. Grossing Up  → Haber: bruto × %  (retenciones IUE, IT, RC-IVA).
 *  4. Grossing Down→ Debe:  (bruto − exento − ICE − tasas) × %  (crédito fiscal IVA).
 *  5. gasto (Debe) = importe + Σ Grossing Up − Σ Grossing Down.
 *
 * Con esto la contrapartida (caja chica / empleado) siempre es igual al importe y el
 * asiento cuadra. Ejemplos con el cuadro de COPA:
 *  - Factura 100, IVA 13% Down         → gasto 87, IVA 13 (D), caja 100.
 *  - Pagado 92, IUE 5% + IT 3% Up      → gasto 100, IUE 5 + IT 3 (H), caja 92.
 *
 * Exento, ICE y tasas: si el usuario ingresó el monto en el documento se usa ese;
 * si no, el % fijo configurado (exento por línea de prorrateo; ICE y tasas por documento).
 */
class DocumentLineCalculator
{
    public const GROSSING_UP = 'Grossing Up';
    public const CREDIT_FIELD = 'Credito';

    /**
     * @return array{
     *     expense: float,
     *     exento: float,
     *     lines: array<int, array{account: string, debit: float, credit: float, source: mixed}>
     * }
     */
    public function calculate($document_line): array
    {
        $document = $document_line->document;
        $lines = [];

        $amount = (float) $document_line->amount;
        foreach ($document_line->field ?? [] as $field) {
            $value = round((float) $field->value, 2);
            $isCredit = $field->document_field->type_calculation == self::CREDIT_FIELD;
            $amount += $isCredit ? $value : -$value;
            $lines[] = [
                'account' => $field->document_field->account,
                'debit' => $isCredit ? 0.0 : $value,
                'credit' => $isCredit ? $value : 0.0,
                'source' => $field,
            ];
        }

        $details = $document->detail ?? [];

        $grossUpPercentage = 0;
        foreach ($details as $detail) {
            if ($detail->type_calculation == self::GROSSING_UP && $detail->calculation) {
                $grossUpPercentage += (float) $detail->percentage / 100;
            }
        }
        $gross = $grossUpPercentage > 0 && $grossUpPercentage < 1
            ? $amount / (1 - $grossUpPercentage)
            : $amount;

        $ice = $this->enteredOrPercentage($document_line->ice, $amount, $document->ice);
        $tasas = $this->enteredOrPercentage($document_line->rate, $amount, $document->tasas);

        $expense = $amount;
        // El exento ingresado se informa a SAP aunque el documento no tenga líneas de IVA.
        $maxExento = round((float) $document_line->excento, 2);
        foreach ($details as $detail) {
            $percentage = (float) $detail->percentage / 100;

            if ($detail->type_calculation == self::GROSSING_UP) {
                $value = round($gross * $percentage, 2);
                $expense += $value;
                $lines[] = ['account' => $detail->account, 'debit' => 0.0, 'credit' => $value, 'source' => $detail];
                continue;
            }

            $exento = $this->enteredOrPercentage($document_line->excento, $amount, $detail->exento);
            $maxExento = max($maxExento, $exento);

            $value = round(max($gross - $exento - $ice - $tasas, 0) * $percentage, 2);
            $expense -= $value;
            $lines[] = ['account' => $detail->account, 'debit' => $value, 'credit' => 0.0, 'source' => $detail];
        }

        return [
            'expense' => round($expense, 2),
            'exento' => round($maxExento, 2),
            'lines' => $lines,
        ];
    }

    /** Monto ingresado por el usuario si lo hay; si no, el % fijo sobre el importe. */
    private function enteredOrPercentage($entered, float $amount, $percentage): float
    {
        $entered = (float) $entered;

        return round($entered > 0 ? $entered : $amount * (float) $percentage / 100, 2);
    }
}
