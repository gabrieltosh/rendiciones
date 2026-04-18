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

        $aliases = AccountAlias::whereNotNull('alias')->where('alias', '!=', '')->get();

        $sapMap = $this->HandleGetSapNames($aliases->pluck('acct_code')->toArray());

        $aliasedAccounts = $aliases->map(fn($a) => [
            'acct_code' => $a->acct_code,
            'acct_name' => $sapMap[$a->acct_code] ?? $a->acct_code,
            'alias'     => $a->alias,
        ])->values();

        return Inertia::render('administration/account-aliases/IndexAccountAlias', [
            'aliases'   => $aliasedAccounts,
            'accounts'  => $this->HandleGetAccountsTree(),
            'alias_map' => $aliases->pluck('alias', 'acct_code'),
        ]);
    }

    public function HandleUpdateAlias(Request $request, $acct_code)
    {
        $request->validate(['alias' => 'nullable|string|max:255']);

        AccountAlias::updateOrCreate(
            ['acct_code' => $acct_code],
            ['alias'     => $request->alias ?: null]
        );

        Session::flash('message', 'Alias guardado correctamente');
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

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // skip header

            $code  = trim((string) ($row[0] ?? ''));
            $alias = trim((string) ($row[1] ?? ''));

            if ($code === '') { $skipped++; continue; }

            $existing = AccountAlias::where('acct_code', $code)->first();
            if ($alias === '') {
                if ($existing) { $existing->delete(); }
                $skipped++;
                continue;
            }

            if ($existing) {
                $existing->update(['alias' => $alias]);
                $updated++;
            } else {
                AccountAlias::create(['acct_code' => $code, 'alias' => $alias]);
                $created++;
            }
        }

        Session::flash('message', "Importación completada: {$created} nuevos, {$updated} actualizados, {$skipped} omitidos.");
        Session::flash('type', 'positive');
        return back();
    }

    public function HandleDownloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Codigo Cuenta');
        $sheet->setCellValue('B1', 'Alias');

        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);

        $sheet->setCellValue('A2', '1.1.01.001');
        $sheet->setCellValue('B2', 'Ejemplo de alias');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'plantilla_alias_cuentas.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function HandleDeleteAlias($acct_code)
    {
        AccountAlias::where('acct_code', $acct_code)->delete();

        Session::flash('message', 'Alias eliminado correctamente');
        Session::flash('type', 'positive');
        return back();
    }

    private function HandleGetSapNames(array $codes): array
    {
        if (empty($codes)) return [];

        $params = Management::where('group', 'accountability')->get();

        if ($params->where('name', 'hana_enable')->first()?->value == 'SI') {
            $db        = Config::get('database.connections.hana.database');
            $formatted = implode(',', array_map(fn($c) => "'{$c}'", $codes));
            $sql       = <<<SQL
                select T1."AcctCode", T1."AcctName"
                from {$db}.OACT as T1
                where T1."AcctCode" in ({$formatted})
SQL;
            return collect(Hana::query($sql))->pluck('AcctName', 'AcctCode')->toArray();
        }

        return DB::connection('sap')
            ->table('OACT')
            ->select('AcctCode', 'AcctName')
            ->whereIn('AcctCode', $codes)
            ->get()
            ->pluck('AcctName', 'AcctCode')
            ->toArray();
    }

    private function HandleGetAccountsTree(): array
    {
        $params = Management::where('group', 'accountability')->get();

        if ($params->where('name', 'hana_enable')->first()?->value == 'SI') {
            $db  = Config::get('database.connections.hana.database');
            $sql = <<<SQL
                select CONCAT(CONCAT(T1."AcctCode",'-'),T1."AcctName") as "label",
                    T1."AcctName",
                    T1."AcctCode",
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

        $accounts       = DB::connection('sap')->table('OACT as T1')
            ->select(
                DB::raw("CONCAT(T1.AcctCode,'-',T1.AcctName) as label"),
                'T1.AcctName', 'T1.AcctCode', 'T1.FatherNum', 'T1.Levels'
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
