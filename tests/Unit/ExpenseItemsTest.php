<?php

namespace Tests\Unit;

use App\Services\Accountability\ExpenseItems;
use App\Services\Sap\InventoryReceiptPayloadBuilder;
use PHPUnit\Framework\TestCase;

class ExpenseItemsTest extends TestCase
{
    private function item(?string $code, float $quantity, float $price, ?string $warehouse = 'ALM01'): array
    {
        return [
            'item_code' => $code,
            'item_name' => $code ? "Artículo $code" : null,
            'quantity' => $quantity,
            'price' => $price,
            'warehouse_code' => $warehouse,
        ];
    }

    public function test_el_total_redondea_cada_linea_a_centavos(): void
    {
        // 3 × 2,333 = 6,999 → 7,00 ; 1 × 80 = 80,00
        $items = [$this->item('A', 3, 2.333), $this->item('B', 1, 80)];

        $this->assertSame(87.0, ExpenseItems::total($items));
    }

    public function test_items_que_suman_el_gasto_no_tienen_errores(): void
    {
        // Factura de 100 con IVA 13 %: gasto sin IVA 87
        $items = [$this->item('A', 2, 30), $this->item('B', 3, 9)];

        $this->assertSame([], ExpenseItems::errors($items, 87.0, false));
    }

    public function test_exige_que_el_total_sea_igual_al_gasto(): void
    {
        $items = [$this->item('A', 1, 86.99)];

        $errors = ExpenseItems::errors($items, 87.0, false);

        $this->assertArrayHasKey('items', $errors);
        $this->assertStringContainsString('86,99', $errors['items']);
        $this->assertStringContainsString('87,00', $errors['items']);
    }

    public function test_valida_cada_linea(): void
    {
        $items = [$this->item(null, 0, 0, null)];

        $errors = ExpenseItems::errors($items, null, false);

        $this->assertArrayHasKey('items.0.item_code', $errors);
        $this->assertArrayHasKey('items.0.quantity', $errors);
        $this->assertArrayHasKey('items.0.price', $errors);
        $this->assertArrayHasKey('items.0.warehouse_code', $errors);
    }

    public function test_exige_al_menos_un_articulo_y_no_admite_reparto(): void
    {
        $errors = ExpenseItems::errors([], 87.0, true);

        $this->assertArrayHasKey('items', $errors);
        $this->assertArrayHasKey('splits', $errors);
    }

    public function test_normaliza_lo_que_llega_del_formulario(): void
    {
        $items = ExpenseItems::normalize([
            ['item_code' => ' A01 ', 'quantity' => '2', 'price' => '10.5', 'warehouse_code' => 'ALM01'],
            'basura',
        ]);

        $this->assertCount(1, $items);
        $this->assertSame('A01', $items[0]['item_code']);
        $this->assertSame(2.0, $items[0]['quantity']);
        $this->assertSame(10.5, $items[0]['price']);
        $this->assertNull($items[0]['item_name']);
    }

    public function test_el_borrador_va_contra_la_cuenta_del_gasto_con_las_dimensiones_del_documento(): void
    {
        $accountability = (object) ['id' => 15, 'end_date' => '2026-10-31'];
        $document_line = (object) [
            'account' => '6110101',
            'date' => '2026-10-20',
            'document_number' => '4521',
            'business_name' => 'Ferretería Central',
            'project_code' => 'PRJ01',
            'distribution_rule_one' => 'CC01',
            'distribution_rule_second' => null,
            'distribution_rule_three' => 'NR03',
            'distribution_rule_four' => null,
            'distribution_rule_five' => null,
            'document' => (object) ['name' => 'Factura con ítems'],
            'items' => [
                (object) ['item_code' => 'A01', 'quantity' => 2, 'price' => 30, 'warehouse_code' => 'ALM01'],
                (object) ['item_code' => 'B02', 'quantity' => 3, 'price' => 9, 'warehouse_code' => 'ALM02'],
            ],
        ];

        $draft = (new InventoryReceiptPayloadBuilder())->draft($accountability, $document_line, 'REND-15');

        $this->assertSame('oInventoryGenEntry', $draft['DocObjectCode']);
        $this->assertSame('2026-10-31', $draft['DocDate']);
        $this->assertSame('2026-10-20', $draft['TaxDate']);
        $this->assertSame('REND-15', $draft['Reference2']);
        $this->assertCount(2, $draft['DocumentLines']);
        $this->assertSame([
            'ItemCode' => 'A01',
            'Quantity' => 2.0,
            'UnitPrice' => 30.0,
            'WarehouseCode' => 'ALM01',
            'AccountCode' => '6110101',
            'CostingCode' => 'CC01',
            'CostingCode3' => 'NR03',
            'ProjectCode' => 'PRJ01',
        ], $draft['DocumentLines'][0]);
        $this->assertSame('ALM02', $draft['DocumentLines'][1]['WarehouseCode']);
        $this->assertLessThanOrEqual(50, mb_strlen($draft['JournalMemo']));
    }
}
