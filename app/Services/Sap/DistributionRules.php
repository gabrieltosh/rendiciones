<?php

namespace App\Services\Sap;

use App\Helpers\Hana;
use App\Models\Management;
use Config;
use DB;
use Illuminate\Support\Str;

/**
 * Opciones de las 5 dimensiones de SAP B1 para los selects de "Centro de Costo".
 *
 * Cada dimensión lista los centros de costo (OPRC) y las normas de reparto
 * (OOCR) activas, juntos y ordenados alfabéticamente por nombre. En SAP los
 * campos CostingCode..CostingCode5 de la línea del asiento aceptan cualquiera
 * de los dos códigos.
 *
 * Cada opción trae el código en OcrCode y PrcCode (los formularios usan uno u
 * otro según la versión) y la etiqueta en Name.
 */
class DistributionRules
{
    public const DIMENSIONS = 5;

    private ?array $all = null;

    /**
     * @return array<int, array<int, array>> opciones por número de dimensión (1..5)
     */
    public function all(): array
    {
        if ($this->all !== null) {
            return $this->all;
        }

        $hana = Management::where('group', 'accountability')
            ->where('name', 'hana_enable')
            ->first()?->value == 'SI';

        $this->all = [];
        for ($dim = 1; $dim <= self::DIMENSIONS; $dim++) {
            $rows = $hana ? $this->fromHana($dim) : $this->fromSqlServer($dim);
            $this->all[$dim] = collect($rows)
                ->map(fn ($row) => $this->option((string) $row['code'], $row['name'], $row['rule']))
                ->unique('OcrCode')
                ->sortBy(fn ($option) => Str::lower(Str::ascii($option['OcrName'])))
                ->values()
                ->all();
        }

        return $this->all;
    }

    /**
     * Opción de la dimensión con ese código, o [] si no existe (o ya no está activa).
     */
    public function find(?string $code, int $dim): array
    {
        if (! $code) {
            return [];
        }

        foreach ($this->all()[$dim] ?? [] as $option) {
            if ($option['OcrCode'] === $code) {
                return $option;
            }
        }

        return [];
    }

    /**
     * Código de la dimensión tal como llega del formulario: el select envía el
     * código (texto) o la opción completa (objeto con OcrCode / PrcCode).
     */
    public static function code($value): ?string
    {
        if (is_array($value)) {
            $value = $value['OcrCode'] ?? $value['PrcCode'] ?? null;
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function option(string $code, ?string $name, bool $rule): array
    {
        $name = trim((string) $name);
        $label = "{$name} ({$code})".($rule ? ' · Norma de reparto' : '');

        return [
            'Name' => $label,
            'OcrCode' => $code,
            'OcrName' => $name,
            'PrcCode' => $code,
            'PrcName' => $name,
            'Kind' => $rule ? 'Norma de reparto' : 'Centro de costo',
        ];
    }

    /**
     * En HANA se lee OOCR: SAP crea ahí una norma "directa" (Direct = 'Y') por
     * cada centro de costo, así que la tabla ya trae ambos.
     */
    private function fromHana(int $dim): array
    {
        $db = Config::get('database.connections.hana.database');
        $sql =
            <<<SQL
            select T1."OcrCode", T1."OcrName", T1."Direct"
            from $db.OOCR as T1
            where T1."DimCode" = $dim
            and T1."Active" = 'Y'
SQL;
        $rows = Hana::query($sql);

        return collect(is_array($rows) ? $rows : [])
            ->map(fn ($row) => [
                'code' => $row['OcrCode'],
                'name' => $row['OcrName'],
                'rule' => ($row['Direct'] ?? 'Y') !== 'Y',
            ])
            ->all();
    }

    private function fromSqlServer(int $dim): array
    {
        $centers = DB::connection('sap')
            ->table('OPRC')
            ->select('PrcCode as code', 'PrcName as name', DB::raw('0 as is_rule'))
            ->where('DimCode', $dim)
            ->where('Locked', 'N');

        return DB::connection('sap')
            ->table('OOCR')
            ->select('OcrCode as code', 'OcrName as name', DB::raw('1 as is_rule'))
            ->where('DimCode', $dim)
            ->where('Active', 'Y')
            ->where('Direct', 'N')
            ->union($centers)
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'name' => $row->name,
                'rule' => (bool) $row->is_rule,
            ])
            ->all();
    }
}
