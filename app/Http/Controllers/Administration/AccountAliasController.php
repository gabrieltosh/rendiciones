<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AccountAlias;
use App\Models\Management;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Session;
use DB;
use Config;
use App\Helpers\Hana;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountAliasController extends Controller
{
    public function HandleIndexAccountAlias()
    {
        Session::put('title', 'Alias de Cuentas Contables');

        $aliases = AccountAlias::whereNotNull('alias')
            ->where('alias', '!=', '')
            ->orderBy('acct_code')
            ->orderBy('id')
            ->get()
            ->map(function ($a) {
                $a->id = (int) $a->id;
                return $a;
            });

        return Inertia::render('administration/account-aliases/IndexAccountAlias', [
            'aliases'   => $aliases,
            'accounts'  => $this->HandleGetAccountsTree(),
            'alias_map' => $aliases->groupBy('acct_code')
                ->map(fn($g) => $g->pluck('alias')->toArray()),
        ]);
    }

    public function HandleStoreAlias(Request $request)
    {
        $request->validate([
            'acct_code'   => 'required|string|max:255',
            'format_code' => 'nullable|string|max:255',
            'acct_name'   => 'nullable|string|max:255',
            'alias'       => 'required|string|max:255',
        ]);

        AccountAlias::create([
            'acct_code'   => $request->acct_code,
            'format_code' => $request->format_code,
            'acct_name'   => $request->acct_name,
            'alias'       => $request->alias,
        ]);

        Session::flash('message', 'Alias creado correctamente');
        Session::flash('type', 'positive');
        return back();
    }

    public function HandleUpdateAlias(Request $request, $id)
    {
        $request->validate(['alias' => 'required|string|max:255']);

        AccountAlias::findOrFail($id)->update(['alias' => $request->alias]);

        Session::flash('message', 'Alias actualizado correctamente');
        Session::flash('type', 'positive');
        return back();
    }

    public function HandleDeleteAlias($id)
    {
        AccountAlias::findOrFail($id)->delete();

        Session::flash('message', 'Alias eliminado correctamente');
        Session::flash('type', 'positive');
        return back();
    }

    public function HandleImportAliases(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        // Collect FormatCodes from Excel (skip header) — allow multiple rows per account
        $entries = [];
        foreach ($rows as $i => $row) {
            if ($i === 0) continue;
            $formatCode = trim((string) ($row[0] ?? ''));
            $alias      = trim((string) ($row[1] ?? ''));
            if ($formatCode === '' || $alias === '') continue;
            $entries[] = ['format_code' => $formatCode, 'alias' => $alias];
        }

        if (empty($entries)) {
            Session::flash('message', 'No se encontraron filas válidas en el archivo.');
            Session::flash('type', 'warning');
            return back();
        }

        // Bulk lookup in SAP by unique FormatCodes
        $formatCodes = array_unique(array_column($entries, 'format_code'));
        $sapAccounts = $this->HandleGetAccountsByFormatCode(array_values($formatCodes));

        $created = 0;
        $skipped = 0;

        foreach ($entries as $entry) {
            $sap = $sapAccounts[$entry['format_code']] ?? null;
            if (!$sap) { $skipped++; continue; }

            AccountAlias::create([
                'acct_code'   => $sap['AcctCode'],
                'format_code' => $entry['format_code'],
                'acct_name'   => $sap['AcctName'],
                'alias'       => $entry['alias'],
            ]);
            $created++;
        }

        Session::flash('message', "Importación completada: {$created} creados, {$skipped} omitidos (código formato no encontrado en SAP).");
        Session::flash('type', 'positive');
        return back();
    }

    private function HandleGetAccountsByFormatCode(array $formatCodes): array
    {
        if (empty($formatCodes)) return [];

        $params = Management::where('group', 'accountability')->get();

        if ($params->where('name', 'hana_enable')->first()?->value == 'SI') {
            $db        = Config::get('database.connections.hana.database');
            $formatted = implode(',', array_map(fn($c) => "'" . addslashes($c) . "'", $formatCodes));
            $sql       = <<<SQL
                select T1."AcctCode", T1."AcctName", T1."FormatCode"
                from {$db}.OACT as T1
                where T1."FormatCode" in ({$formatted})
SQL;
            return collect(Hana::query($sql))
                ->keyBy('FormatCode')
                ->toArray();
        }

        return DB::connection('sap')
            ->table('OACT')
            ->select('AcctCode', 'AcctName', 'FormatCode')
            ->whereIn('FormatCode', $formatCodes)
            ->get()
            ->keyBy('FormatCode')
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    public function HandleDownloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Código Formato');
        $sheet->setCellValue('B1', 'Alias');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);

        $sheet->setCellValue('A2', '1.01.001');
        $sheet->setCellValue('B2', 'Caja principal');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'plantilla_alias_cuentas.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function HandleGetAccountsTree(): array
    {
        $params = Management::where('group', 'accountability')->get();

        if ($params->where('name', 'hana_enable')->first()?->value == 'SI') {
            $db  = Config::get('database.connections.hana.database');
            $sql = <<<SQL
                select CASE
                        WHEN T1."FormatCode" IS NULL OR T1."FormatCode" = ''
                        THEN T1."AcctName"
                        ELSE CONCAT(CONCAT(T1."FormatCode",'-'),T1."AcctName")
                       END as "label",
                    T1."AcctName",
                    T1."AcctCode",
                    T1."FormatCode",
                    T1."FatherNum",
                    T1."Levels"
                from {$db}.OACT as T1
                where T1."Levels" in (1,2,3,4,5,6,7,8,9,10)
                order by T1."Levels", T1."FatherNum"
SQL;
            $accounts       = collect(Hana::query($sql))->map(fn($i) => (object) $i);
            $accountsByCode = $accounts->keyBy('AcctCode');

            $accounts->each(function ($account) use ($accountsByCode) {
                if ($account->FatherNum && isset($accountsByCode[$account->FatherNum])) {
                    $parent           = $accountsByCode[$account->FatherNum];
                    $parent->children = $parent->children ?? collect();
                    $parent->children->push($account);
                }
            });

            return $accounts->filter(fn($a) => $a->Levels == 1)->values()->all();
        }

        $accounts = DB::connection('sap')->table('OACT as T1')
            ->select(
                DB::raw("CASE WHEN T1.FormatCode IS NULL OR T1.FormatCode = '' THEN T1.AcctName ELSE CONCAT(T1.FormatCode,'-',T1.AcctName) END as label"),
                'T1.AcctName', 'T1.AcctCode', 'T1.FormatCode', 'T1.FatherNum', 'T1.Levels'
            )
            ->whereIn('T1.Levels', range(1, 10))
            ->orderBy('T1.Levels')->orderBy('T1.FatherNum')
            ->get();

        $accountsByCode = [];
        foreach ($accounts as $account) {
            $accountsByCode[$account->AcctCode] = $account;
        }
        foreach ($accounts as $account) {
            if ($account->FatherNum && isset($accountsByCode[$account->FatherNum])) {
                $parent = $accountsByCode[$account->FatherNum];
                if (!isset($parent->children)) $parent->children = [];
                $parent->children[] = $account;
            }
        }

        return array_values(array_filter($accountsByCode, fn($a) => $a->Levels == 1));
    }
}
