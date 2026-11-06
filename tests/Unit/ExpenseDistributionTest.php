<?php

namespace Tests\Unit;

use App\Services\Accountability\ExpenseDistribution;
use App\Services\Sap\JournalVoucherPayloadBuilder;
use PHPUnit\Framework\TestCase;

class ExpenseDistributionTest extends TestCase
{
    private function splits(): array
    {
        return [
            1 => [['code' => 'A', 'percentage' => 50], ['code' => 'B', 'percentage' => 50]],
            2 => [['code' => 'X', 'percentage' => 30], ['code' => 'Y', 'percentage' => 70]],
        ];
    }

    private function line(string $combination, float $percentage, string $account, array $dims): object
    {
        return (object) [
            'combination' => $combination,
            'percentage' => $percentage,
            'account' => $account,
            'account_name' => "Cuenta $account",
            'concept' => null,
            'distribution_rule_one' => $dims[0] ?? null,
            'distribution_rule_second' => $dims[1] ?? null,
            'distribution_rule_three' => $dims[2] ?? null,
            'distribution_rule_four' => null,
            'distribution_rule_five' => null,
        ];
    }

    public function test_combina_los_repartos_de_cada_dimension(): void
    {
        $combos = ExpenseDistribution::combinations($this->splits());

        $this->assertSame(['1:A|2:X', '1:A|2:Y', '1:B|2:X', '1:B|2:Y'], array_column($combos, 'combination'));
        $this->assertEquals([15.0, 35.0, 15.0, 35.0], array_column($combos, 'percentage'));
        $this->assertSame([1 => 'B', 2 => 'Y'], $combos[3]['codes']);
    }

    public function test_una_dimension_con_un_solo_valor_no_multiplica_las_lineas(): void
    {
        $splits = $this->splits();
        $splits[3] = [['code' => 'P1', 'percentage' => 100]];

        $combos = ExpenseDistribution::combinations($splits);

        $this->assertCount(4, $combos);
        $this->assertSame('1:A|2:X|3:P1', $combos[0]['combination']);
        $this->assertEquals(100.0, array_sum(array_column($combos, 'percentage')));
    }

    public function test_normaliza_el_reparto_del_formulario(): void
    {
        $splits = ExpenseDistribution::normalize([
            '2' => [['code' => ['OcrCode' => 'X', 'Name' => 'X'], 'percentage' => '30'], ['code' => 'Y', 'percentage' => 70]],
            '1' => [['code' => 'A', 'percentage' => 100], ['code' => null, 'percentage' => 0]],
            '9' => [['code' => 'Z', 'percentage' => 100]],
            '4' => [],
        ]);

        $this->assertSame([1, 2], array_keys($splits));
        $this->assertSame([['code' => 'A', 'percentage' => 100.0]], $splits[1]);
        $this->assertSame('X', $splits[2][0]['code']);
        $this->assertSame(30.0, $splits[2][0]['percentage']);
    }

    public function test_cada_dimension_repartida_debe_sumar_100(): void
    {
        $this->assertSame([], ExpenseDistribution::errors($this->splits()));

        $errors = ExpenseDistribution::errors([
            1 => [['code' => 'A', 'percentage' => 50], ['code' => 'B', 'percentage' => 40]],
            2 => [['code' => 'X', 'percentage' => 50], ['code' => 'X', 'percentage' => 50]],
        ]);

        $this->assertStringContainsString('suma 90 %', $errors['splits.1']);
        $this->assertStringContainsString('repetido', $errors['splits.2']);
    }

    public function test_limita_la_cantidad_de_lineas(): void
    {
        $three = [['code' => 'A', 'percentage' => 40], ['code' => 'B', 'percentage' => 30], ['code' => 'C', 'percentage' => 30]];
        $two = [['code' => 'A', 'percentage' => 50], ['code' => 'B', 'percentage' => 50]];

        $errors = ExpenseDistribution::errors([1 => $three, 2 => $three, 3 => $three, 4 => $two]);

        $this->assertSame(54, ExpenseDistribution::lineCount([1 => $three, 2 => $three, 3 => $three, 4 => $two]));
        $this->assertArrayHasKey('splits', $errors);
        $this->assertArrayNotHasKey('splits', ExpenseDistribution::errors([1 => $two, 2 => $two, 3 => $two, 4 => $two, 5 => $two]));
    }

    public function test_reparte_el_gasto_y_ajusta_el_redondeo_en_la_ultima_linea(): void
    {
        $detail = (object) ['lines' => [
            $this->line('1:A', 33.33333333, '611', ['A']),
            $this->line('1:B', 33.33333333, '611', ['B']),
            $this->line('1:C', 33.33333334, '612', ['C']),
        ]];

        $lines = ExpenseDistribution::expenseLines($detail, 100.0);

        $this->assertEquals([33.33, 33.33, 33.34], array_column($lines, 'amount'));
        $this->assertSame('612', $lines[2]['account']);
        $this->assertSame('C', $lines[2]['costing']['CostingCode']);
        $this->assertNull(ExpenseDistribution::expenseLines((object) ['lines' => []], 100.0));
    }

    public function test_el_asiento_divide_el_gasto_por_lineas_y_mantiene_el_iva(): void
    {
        $document_line = (object) [
            'account' => '611',
            'concept' => 'Compra de material',
            'amount' => 1000,
            'excento' => 0,
            'rate' => 0,
            'ice' => 0,
            'discount' => 0,
            'gift_card' => 0,
            'date' => '2026-10-01',
            'document_number' => '1',
            'authorization_number' => null,
            'cuf' => null,
            'control_code' => null,
            'business_name' => null,
            'nit' => null,
            'project_code' => null,
            'distribution_rule_one' => 'A',
            'distribution_rule_second' => 'X',
            'distribution_rule_three' => 'C3',
            'distribution_rule_four' => null,
            'distribution_rule_five' => null,
            'field' => [],
            'document' => (object) [
                'tasas' => 0,
                'ice' => 0,
                'type_document_sap' => null,
                'detail' => [(object) [
                    'type_calculation' => 'Grossing Down',
                    'percentage' => 13,
                    'account' => '1130601',
                    'exento' => 0,
                    'calculation' => false,
                ]],
            ],
            'lines' => [
                $this->line('1:A|2:X', 15, '611', ['A', 'X', 'C3']),
                $this->line('1:A|2:Y', 35, '611', ['A', 'Y', 'C3']),
                $this->line('1:B|2:X', 15, '612', ['B', 'X', 'C3']),
                $this->line('1:B|2:Y', 35, '612', ['B', 'Y', 'C3']),
            ],
        ];

        $journal = (new JournalVoucherPayloadBuilder(collect()))->formatLine($document_line);

        $this->assertCount(5, $journal);
        $this->assertEquals([130.5, 304.5, 130.5, 304.5], array_column(array_slice($journal, 0, 4), 'Debit'));
        $this->assertSame(['611', '611', '612', '612'], array_column(array_slice($journal, 0, 4), 'AccountCode'));
        $this->assertSame(['A', 'A', 'B', 'B'], array_column(array_slice($journal, 0, 4), 'CostingCode'));
        $this->assertSame(['X', 'Y', 'X', 'Y'], array_column(array_slice($journal, 0, 4), 'CostingCode2'));
        $this->assertSame('Compra de material', $journal[0]['LineMemo']);

        // El IVA no se reparte: una línea con las dimensiones del documento
        $this->assertSame('1130601', $journal[4]['AccountCode']);
        $this->assertEquals(130.0, $journal[4]['Debit']);
        $this->assertSame('A', $journal[4]['CostingCode']);
        $this->assertEquals(1000.0, array_sum(array_column($journal, 'Debit')));
    }
}
