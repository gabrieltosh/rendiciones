<?php

namespace Tests\Unit;

use App\Services\Sap\DistributionRules;
use PHPUnit\Framework\TestCase;

class DistributionRulesTest extends TestCase
{
    public function test_acepta_el_codigo_que_envia_el_select_con_emit_value(): void
    {
        // El formulario de nuevo gasto envía el código como texto: antes se
        // perdía porque el controlador leía $valor['OcrCode'].
        $this->assertSame('CC01', DistributionRules::code('CC01'));
        $this->assertSame('CC01', DistributionRules::code(' CC01 '));
    }

    public function test_acepta_la_opcion_completa_del_select(): void
    {
        $this->assertSame('CC01', DistributionRules::code(['OcrCode' => 'CC01', 'Name' => 'Ventas (CC01)']));
        $this->assertSame('CC02', DistributionRules::code(['PrcCode' => 'CC02', 'Name' => 'Compras (CC02)']));
    }

    public function test_devuelve_null_si_no_hay_dimension(): void
    {
        $this->assertNull(DistributionRules::code(null));
        $this->assertNull(DistributionRules::code(''));
        $this->assertNull(DistributionRules::code([]));
    }
}
