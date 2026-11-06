<?php

namespace App\Services\Accountability;

use App\Models\AccountabilityDetail;
use App\Models\AccountabilityDetailItem;
use App\Models\Document;
use App\Models\DocumentField;
use App\Services\Sap\DocumentLineCalculator;

/**
 * Artículos de una factura con ítems (tipo de documento con items_status).
 *
 * El precio es sin IVA y el total de los ítems debe ser igual al gasto del
 * documento: lo que el asiento de la rendición carga a la cuenta del gasto. Al
 * exportar, esos ítems forman una entrada de mercancías en borrador contra esa
 * misma cuenta, que así queda en cero.
 */
class ExpenseItems
{
    /** Diferencia máxima entre el total de los ítems y el gasto (redondeo a centavos). */
    public const TOLERANCE = 0.005;

    /**
     * Ítems tal como llegan del formulario, sin filas vacías.
     *
     * @return array<int, array{item_code: ?string, item_name: ?string, quantity: float, price: float, warehouse_code: ?string}>
     */
    public static function normalize($items): array
    {
        $result = [];
        foreach ((array) $items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $result[] = [
                'item_code' => self::text($item['item_code'] ?? null),
                'item_name' => self::text($item['item_name'] ?? null),
                'quantity' => (float) ($item['quantity'] ?? 0),
                'price' => (float) ($item['price'] ?? 0),
                'warehouse_code' => self::text($item['warehouse_code'] ?? null),
            ];
        }

        return $result;
    }

    /** Total de una línea como lo calcula SAP: cantidad × precio, a centavos. */
    public static function lineTotal(float $quantity, float $price): float
    {
        return round($quantity * $price, 2);
    }

    public static function total(array $items): float
    {
        return round(array_sum(array_map(
            fn ($item) => self::lineTotal((float) $item['quantity'], (float) $item['price']),
            $items
        )), 2);
    }

    /**
     * Errores de los ítems normalizados, por clave del formulario.
     *
     * @param  float|null  $expense  gasto del documento sin IVA (null si no se pudo calcular)
     * @return array<string, string>
     */
    public static function errors(array $items, ?float $expense, bool $hasSplits): array
    {
        $errors = [];

        if ($hasSplits) {
            $errors['splits'] = 'Los documentos con ítems no admiten reparto por líneas.';
        }

        if (! $items) {
            $errors['items'] = 'Agregue al menos un artículo.';

            return $errors;
        }

        foreach ($items as $i => $item) {
            if (! $item['item_code']) {
                $errors["items.$i.item_code"] = 'Seleccione el artículo.';
            }
            if ($item['quantity'] <= 0) {
                $errors["items.$i.quantity"] = 'La cantidad debe ser mayor a 0.';
            }
            if ($item['price'] <= 0) {
                $errors["items.$i.price"] = 'El precio debe ser mayor a 0.';
            }
            if (! $item['warehouse_code']) {
                $errors["items.$i.warehouse_code"] = 'Seleccione el almacén.';
            }
        }

        if ($expense !== null) {
            $total = self::total($items);
            if (abs($total - $expense) > self::TOLERANCE) {
                $errors['items'] = sprintf(
                    'El total de los artículos (%s) debe ser igual al gasto del documento sin IVA (%s). Diferencia: %s.',
                    self::money($total),
                    self::money($expense),
                    self::money($total - $expense)
                );
            }
        }

        return $errors;
    }

    /**
     * Gasto del documento (lo que el asiento carga a la cuenta del gasto) a partir de
     * los datos del formulario, sin guardarlos. Usa el mismo cálculo que la exportación.
     *
     * @param  array  $input  document_id, amount, field[], ice, rate, excento
     */
    public static function expenseFromInput(array $input): ?float
    {
        $document = Document::with('detail')->find($input['document_id'] ?? null);
        if (! $document) {
            return null;
        }

        $fields = [];
        foreach ((array) ($input['field'] ?? []) as $field) {
            $documentField = DocumentField::find($field['field_id'] ?? $field['id'] ?? null);
            if ($documentField) {
                $fields[] = (object) ['value' => $field['value'] ?? 0, 'document_field' => $documentField];
            }
        }

        $line = (object) [
            'document' => $document,
            'field' => $fields,
            'amount' => $input['amount'] ?? 0,
            'ice' => $input['ice'] ?? null,
            'rate' => $input['rate'] ?? null,
            'excento' => $input['excento'] ?? null,
        ];

        return (new DocumentLineCalculator())->calculate($line)['expense'];
    }

    /**
     * Guarda los ítems del documento. Si su tipo de documento no lleva ítems, se borran.
     */
    public static function sync(AccountabilityDetail $detail, $items, bool $enabled): void
    {
        AccountabilityDetailItem::where('accountability_detail_id', $detail->id)->delete();

        if (! $enabled) {
            return;
        }

        foreach (self::normalize($items) as $item) {
            AccountabilityDetailItem::create(array_merge($item, [
                'accountability_detail_id' => $detail->id,
                'item_name' => $item['item_name'] ?? $item['item_code'],
            ]));
        }
    }

    private static function text($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function money(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
