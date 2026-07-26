<?php

namespace Tests\Unit;

use App\Services\Sap\UdfTypes;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class UdfTypesTest extends TestCase
{
    public function test_clasifica_los_campos_segun_su_tipo_sap(): void
    {
        $this->assertSame(UdfTypes::TYPE_DATE, UdfTypes::forField('date'));
        $this->assertSame(UdfTypes::TYPE_FLOAT, UdfTypes::forField('amount'));
        $this->assertSame(UdfTypes::TYPE_FLOAT, UdfTypes::forField('ice'));
        $this->assertSame(UdfTypes::TYPE_ALPHA, UdfTypes::forField('cuf'));
    }

    public function test_normaliza_fechas_y_montos(): void
    {
        $this->assertSame('2026-03-14', UdfTypes::normalize('date', '2026-03-14T00:00:00'));
        $this->assertSame(1200.5, UdfTypes::normalize('amount', '1200.50'));
        $this->assertSame('ABC-123', UdfTypes::normalize('cuf', 'ABC-123'));
    }

    public function test_conserva_los_valores_vacios_sin_alterar_el_payload(): void
    {
        $this->assertNull(UdfTypes::normalize('amount', null));
        $this->assertSame('', UdfTypes::normalize('date', ''));
    }

    public function test_arma_el_mapa_de_tipos_omitiendo_parametros_sin_nombre_de_udf(): void
    {
        $management = new Collection([
            (object) ['name' => 'date', 'value' => 'U_FechaDeFactura'],
            (object) ['name' => 'amount', 'value' => 'U_Importe'],
            (object) ['name' => 'cuf', 'value' => 'U_CUF'],
            (object) ['name' => 'user_field', 'value' => ''],
        ]);

        $this->assertSame([
            'U_FechaDeFactura' => UdfTypes::TYPE_DATE,
            'U_Importe' => UdfTypes::TYPE_FLOAT,
            'U_CUF' => UdfTypes::TYPE_ALPHA,
        ], UdfTypes::map($management));
    }
}
