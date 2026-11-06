<?php

namespace App\Http\Controllers\Accountability;

use App\Helpers\Hana;
use App\Http\Controllers\Controller;
use App\Models\Management;
use App\Services\Accountability\ExpenseItems;
use Config;
use DB;
use Illuminate\Http\Request;

/**
 * Datos de apoyo del formulario de facturas con ítems: artículos y almacenes de SAP
 * y el gasto del documento sin IVA, que es lo que deben sumar los ítems.
 */
class ExpenseItemsController extends Controller
{
    private const ITEMS_LIMIT = 30;

    /**
     * Artículos de inventario activos que no se manejan por lote ni número de serie,
     * buscados por código o descripción.
     */
    public function HandleSearchItems(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        if ($this->hana()) {
            $db = Config::get('database.connections.hana.database');
            $like = str_replace("'", "''", mb_strtoupper($search));
            $limit = self::ITEMS_LIMIT;
            $sql =
                <<<SQL
                select top $limit T1."ItemCode", T1."ItemName", T1."InvntryUom"
                from $db.OITM as T1
                where T1."InvntItem" = 'Y'
                and T1."frozenFor" = 'N'
                and T1."ManBtchNum" = 'N'
                and T1."ManSerNum" = 'N'
                and (upper(T1."ItemCode") like '%$like%' or upper(T1."ItemName") like '%$like%')
                order by T1."ItemName"
SQL;
            $rows = Hana::query($sql);

            return response()->json(is_array($rows) ? $rows : []);
        }

        $rows = DB::connection('sap')
            ->table('OITM')
            ->select('ItemCode', 'ItemName', 'InvntryUom')
            ->where('InvntItem', 'Y')
            ->where('frozenFor', 'N')
            ->where('ManBtchNum', 'N')
            ->where('ManSerNum', 'N')
            ->where(function ($q) use ($search) {
                $q->where('ItemCode', 'like', "%{$search}%")
                    ->orWhere('ItemName', 'like', "%{$search}%");
            })
            ->orderBy('ItemName')
            ->limit(self::ITEMS_LIMIT)
            ->get();

        return response()->json($rows);
    }

    /** Almacenes activos. */
    public function HandleListWarehouses()
    {
        if ($this->hana()) {
            $db = Config::get('database.connections.hana.database');
            $sql =
                <<<SQL
                select T1."WhsCode", T1."WhsName"
                from $db.OWHS as T1
                where T1."Inactive" = 'N'
                order by T1."WhsName"
SQL;
            $rows = Hana::query($sql);

            return response()->json(is_array($rows) ? $rows : []);
        }

        $rows = DB::connection('sap')
            ->table('OWHS')
            ->select('WhsCode', 'WhsName')
            ->where('Inactive', 'N')
            ->orderBy('WhsName')
            ->get();

        return response()->json($rows);
    }

    /** Gasto del documento sin IVA con los datos que el usuario lleva cargados. */
    public function HandlePreviewExpense(Request $request)
    {
        return response()->json([
            'expense' => ExpenseItems::expenseFromInput($request->all()),
        ]);
    }

    private function hana(): bool
    {
        return Management::where('group', 'accountability')
            ->where('name', 'hana_enable')
            ->first()?->value == 'SI';
    }
}
