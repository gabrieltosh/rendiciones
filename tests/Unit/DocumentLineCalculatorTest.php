<?php

namespace Tests\Unit;

use App\Services\Sap\DocumentLineCalculator;
use PHPUnit\Framework\TestCase;

class DocumentLineCalculatorTest extends TestCase
{
    private function line(float $amount, array $details, array $overrides = [], array $fields = []): object
    {
        return (object) array_merge([
            'amount' => $amount,
            'excento' => 0,
            'rate' => 0,
            'ice' => 0,
            'field' => array_map(fn($f) => (object) [
                'value' => $f[2],
                'document_field' => (object) ['type_calculation' => $f[0], 'account' => $f[1]],
            ], $fields),
            'document' => (object) [
                'tasas' => 0,
                'ice' => 0,
                'detail' => array_map(fn($d) => (object) [
                    'type_calculation' => $d[0],
                    'percentage' => $d[1],
                    'account' => $d[2],
                    'exento' => $d[3] ?? 0,
                    'calculation' => $d[4] ?? true,
                ], $details),
            ],
        ], $overrides);
    }

    /** Devuelve [cuenta => [debe, haber]] incluyendo la contrapartida de caja chica. */
    private function entry(object $line): array
    {
        $result = (new DocumentLineCalculator())->calculate($line);
        $entry = ['GASTO' => [$result['expense'], 0.0]];
        foreach ($result['lines'] as $l) {
            $entry[$l['account']] = [$l['debit'], $l['credit']];
        }
        $debit = array_sum(array_column($entry, 0));
        $credit = array_sum(array_column($entry, 1));
        $entry['CAJA'] = [0.0, round($debit - $credit, 2)];

        return $entry;
    }

    public function test_factura_compra_con_iva_13_grossing_down(): void
    {
        $entry = $this->entry($this->line(100, [['Grossing Down', '13', '1130601']]));

        $this->assertEquals([87.0, 0.0], $entry['GASTO']);
        $this->assertEquals([13.0, 0.0], $entry['1130601']);
        $this->assertEquals([0.0, 100.0], $entry['CAJA']);
    }

    public function test_iva_grossing_down_no_depende_del_check_calculation(): void
    {
        $entry = $this->entry($this->line(100, [['Grossing Down', '13', '1130601', 0, false]]));

        $this->assertEquals([87.0, 0.0], $entry['GASTO']);
        $this->assertEquals([0.0, 100.0], $entry['CAJA']);
    }

    public function test_compra_retenciones_bienes_iue_5_it_3(): void
    {
        $entry = $this->entry($this->line(92, [
            ['Grossing Up', '5', '2110706'],
            ['Grossing Up', '3', '2110708'],
        ]));

        $this->assertEquals([100.0, 0.0], $entry['GASTO']);
        $this->assertEquals([0.0, 5.0], $entry['2110706']);
        $this->assertEquals([0.0, 3.0], $entry['2110708']);
        $this->assertEquals([0.0, 92.0], $entry['CAJA']);
    }

    public function test_compra_retenciones_servicios_rc_iva_13_it_3(): void
    {
        $entry = $this->entry($this->line(84, [
            ['Grossing Up', '13', '2110707'],
            ['Grossing Up', '3', '2110708'],
        ]));

        $this->assertEquals([100.0, 0.0], $entry['GASTO']);
        $this->assertEquals([0.0, 13.0], $entry['2110707']);
        $this->assertEquals([0.0, 3.0], $entry['2110708']);
        $this->assertEquals([0.0, 84.0], $entry['CAJA']);
    }

    public function test_retencion_con_montos_que_redondean_cuadra(): void
    {
        $entry = $this->entry($this->line(1234.57, [
            ['Grossing Up', '13', '2110707'],
            ['Grossing Up', '3', '2110708'],
        ]));

        $this->assertEquals([0.0, 1234.57], $entry['CAJA']);
        $this->assertEqualsWithDelta(
            $entry['GASTO'][0],
            $entry['2110707'][1] + $entry['2110708'][1] + 1234.57,
            0.001
        );
    }

    public function test_grossing_up_sin_check_no_hace_grossing_del_importe(): void
    {
        $entry = $this->entry($this->line(100, [['Grossing Up', '3', '2110708', 0, false]]));

        $this->assertEquals([103.0, 0.0], $entry['GASTO']);
        $this->assertEquals([0.0, 3.0], $entry['2110708']);
        $this->assertEquals([0.0, 100.0], $entry['CAJA']);
    }

    public function test_registros_otros_sin_prorrateo_va_100_al_gasto(): void
    {
        $entry = $this->entry($this->line(50, []));

        $this->assertEquals([50.0, 0.0], $entry['GASTO']);
        $this->assertEquals([0.0, 50.0], $entry['CAJA']);
    }

    public function test_factura_combustible_iva_sobre_importe_menos_exento_ingresado(): void
    {
        $calculator = new DocumentLineCalculator();
        $result = $calculator->calculate($this->line(100, [['Grossing Down', '13', '1130601']], ['excento' => 30]));

        $this->assertEquals(9.1, $result['lines'][0]['debit']);
        $this->assertEquals(90.9, $result['expense']);
        $this->assertEquals(30.0, $result['exento']);
    }

    public function test_exento_ice_y_tasas_ingresados_reducen_la_base_del_iva(): void
    {
        $entry = $this->entry($this->line(200, [['Grossing Down', '13', '1130601']], [
            'excento' => 20,
            'ice' => 30,
            'rate' => 50,
        ]));

        $this->assertEquals([13.0, 0.0], $entry['1130601']); // (200 - 20 - 30 - 50) × 13%
        $this->assertEquals([187.0, 0.0], $entry['GASTO']);
        $this->assertEquals([0.0, 200.0], $entry['CAJA']);
    }

    public function test_exento_por_porcentaje_fijo_de_la_linea(): void
    {
        $result = (new DocumentLineCalculator())->calculate($this->line(100, [['Grossing Down', '13', '1130601', 30]]));

        $this->assertEquals(9.1, $result['lines'][0]['debit']);
        $this->assertEquals(30.0, $result['exento']);
    }

    public function test_campos_adicionales_ajustan_el_importe(): void
    {
        $entry = $this->entry($this->line(1000, [['Grossing Down', '13', '1130601']], [], [
            ['Debito', '1310', 300],
        ]));

        $this->assertEquals([300.0, 0.0], $entry['1310']);
        $this->assertEquals([91.0, 0.0], $entry['1130601']); // (1000 - 300) × 13%
        $this->assertEquals([609.0, 0.0], $entry['GASTO']);
        $this->assertEquals([0.0, 1000.0], $entry['CAJA']);
    }
}
