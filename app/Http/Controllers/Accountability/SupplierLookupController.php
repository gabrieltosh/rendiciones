<?php

namespace App\Http\Controllers\Accountability;

use App\Helpers\Hana;
use App\Http\Controllers\Controller;
use App\Models\AccountabilityDetail;
use App\Models\Management;
use Config;
use DB;
use Illuminate\Http\Request;
use Log;
use Throwable;

/**
 * Búsqueda de proveedores para los formularios de documentos: socios de negocio de
 * SAP (OCRD) y proveedores ya usados en rendiciones. Reemplaza el envío de todo el
 * OCRD como prop de la página.
 */
class SupplierLookupController extends Controller
{
    private const LIMIT = 30;

    /**
     * GET panel/supplier-lookup
     *  - search (≥ 2 caracteres): razón social o NIT que contienen el texto
     *  - nit: NIT exacto
     * Devuelve [{business_name, nit}] (máximo 30).
     */
    public function HandleSearchSuppliers(Request $request)
    {
        $nit = trim((string) $request->query('nit', ''));
        $search = trim((string) $request->query('search', ''));

        if ($nit === '' && mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $rows = array_merge(
            $this->HandleSearchSap($search, $nit),
            $this->HandleSearchHistory($search, $nit)
        );

        // Sin duplicados (misma razón social y NIT) y con un máximo de resultados
        $unique = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['business_name'] ?? ''));
            $code = trim((string) ($row['nit'] ?? ''));
            if ($name === '' && $code === '') {
                continue;
            }
            $key = mb_strtoupper($name) . '|' . $code;
            if (!isset($unique[$key])) {
                $unique[$key] = ['business_name' => $name, 'nit' => $code];
            }
            if (count($unique) >= self::LIMIT) {
                break;
            }
        }

        return response()->json(array_values($unique));
    }

    /** Socios de negocio de SAP. Un fallo de SAP no impide usar el historial. */
    private function HandleSearchSap(string $search, string $nit): array
    {
        try {
            $params = Management::where('group', 'supplier')->get();
            $nameColumn = $params->where('name', 'business_name')->first()?->value;
            $nitColumn = $params->where('name', 'nit')->first()?->value;
            // Los nombres de columna vienen de la configuración: solo identificadores simples
            if (!$this->HandleIsIdentifier($nameColumn) || !$this->HandleIsIdentifier($nitColumn)) {
                return [];
            }

            $hana = Management::where('group', 'accountability')
                ->where('name', 'hana_enable')
                ->value('value') == 'SI';

            if ($hana) {
                $db = Config::get('database.connections.hana.database');
                $limit = self::LIMIT;
                if ($nit !== '') {
                    // HANA se consulta con SQL armado: se escapan las comillas
                    $value = str_replace("'", "''", $nit);
                    $where = "T1.\"$nitColumn\" = '$value'";
                } else {
                    $like = str_replace(["'", '%', '_'], ["''", '', ''], mb_strtoupper($search));
                    $where = "UPPER(T1.\"$nameColumn\") like '%$like%' or UPPER(T1.\"$nitColumn\") like '%$like%'";
                }
                $sql =
                    <<<SQL
                select top $limit
                    T1."$nameColumn" as "business_name",
                    T1."$nitColumn" as "nit"
                from $db.OCRD as T1
                where $where
                order by T1."$nameColumn"
SQL;
                $rows = Hana::query($sql);
                return is_array($rows) ? array_map(fn ($row) => (array) $row, $rows) : [];
            }

            $query = DB::connection('sap')
                ->table('OCRD')
                ->select($nameColumn . ' as business_name', $nitColumn . ' as nit');
            if ($nit !== '') {
                $query->where($nitColumn, $nit);
            } else {
                $like = $this->HandleLike($search);
                $query->where(fn ($q) => $q->where($nameColumn, 'like', $like)->orWhere($nitColumn, 'like', $like));
            }

            return $query->orderBy($nameColumn)
                ->limit(self::LIMIT)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        } catch (Throwable $e) {
            Log::warning('No se pudo buscar proveedores en SAP', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** Proveedores ya registrados en documentos de rendiciones. */
    private function HandleSearchHistory(string $search, string $nit): array
    {
        $query = AccountabilityDetail::select('business_name', 'nit')
            ->whereNotNull('business_name');
        if ($nit !== '') {
            $query->where('nit', $nit);
        } else {
            $like = $this->HandleLike($search);
            $query->where(fn ($q) => $q->where('business_name', 'like', $like)->orWhere('nit', 'like', $like));
        }

        return $query->groupBy('business_name', 'nit')
            ->orderBy('business_name')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($row) => ['business_name' => $row->business_name, 'nit' => $row->nit])
            ->all();
    }

    /** Patrón LIKE de SQL Server con los comodines escapados entre corchetes. */
    private function HandleLike(string $search): string
    {
        return '%' . str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $search) . '%';
    }

    private function HandleIsIdentifier($value): bool
    {
        return is_string($value) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
    }
}
