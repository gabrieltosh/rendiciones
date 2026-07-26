<?php

namespace App\Services\Sap;

use App\Models\Management;
use App\Services\Sap\Contracts\SapExporter;
use App\Services\Sap\Drivers\DiApiBridgeExporter;
use App\Services\Sap\Drivers\ServiceLayerExporter;

class SapExporterFactory
{
    /**
     * Resuelve el driver de exportación según el parámetro `export_mode`
     * del grupo accountability: 'DI' usa el servicio DI API, cualquier otro
     * valor mantiene el Service Layer (comportamiento histórico).
     */
    public static function make(): SapExporter
    {
        $params = Management::where('group', 'accountability')->get();
        $mode = strtoupper(trim((string) $params->where('name', 'export_mode')->first()?->value));

        return $mode === 'DI'
            ? new DiApiBridgeExporter($params)
            : new ServiceLayerExporter($params);
    }
}
