<?php

namespace Database\Seeders\Copa;

use App\Helpers\Hana;
use App\Models\AccountAlias;
use App\Models\Management;
use Config;
use DB;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Base para cargar alias (glosas) en `account_aliases` (panel/account-alias).
 *
 * Igual que la importación por Excel del panel, resuelve cada código
 * (FormatCode) contra OACT en SAP para obtener AcctCode y AcctName. Si SAP
 * no responde o la cuenta no existe, usa el código y el nombre del Excel.
 *
 * Es idempotente: no duplica un alias que ya exista para la misma cuenta.
 */
abstract class AccountAliasSeeder extends Seeder
{
    /**
     * @return array<int, array{0: string, 1: string, 2: string}> [format_code, acct_name, alias]
     */
    abstract protected function rows(): array;

    public function run(): void
    {
        $rows = $this->rows();
        $sapAccounts = $this->getAccountsByFormatCode(array_values(array_unique(array_column($rows, 0))));

        $created = 0;
        $existing = 0;
        $notFound = [];

        foreach ($rows as [$formatCode, $acctName, $alias]) {
            $sap = $sapAccounts[$formatCode] ?? null;
            if (!$sap) {
                $notFound[$formatCode] = true;
            }

            $record = AccountAlias::firstOrCreate(
                [
                    'acct_code' => $sap['AcctCode'] ?? $formatCode,
                    'alias'     => $alias,
                ],
                [
                    'format_code' => $formatCode,
                    'acct_name'   => $sap['AcctName'] ?? $acctName,
                ]
            );

            $record->wasRecentlyCreated ? $created++ : $existing++;
        }

        $this->command?->info(static::class . ": {$created} creados, {$existing} ya existían.");
        if ($notFound) {
            $this->command?->warn('Cuentas no encontradas en SAP (se usó el código del Excel): ' . implode(', ', array_keys($notFound)));
        }
    }

    private function getAccountsByFormatCode(array $formatCodes): array
    {
        if (empty($formatCodes)) {
            return [];
        }

        try {
            $params = Management::where('group', 'accountability')->get();

            if ($params->where('name', 'hana_enable')->first()?->value == 'SI') {
                $db        = Config::get('database.connections.hana.database');
                $formatted = implode(',', array_map(fn($c) => "'" . addslashes($c) . "'", $formatCodes));
                $sql       = <<<SQL
                    select T1."AcctCode", T1."AcctName", T1."FormatCode"
                    from {$db}.OACT as T1
                    where T1."FormatCode" in ({$formatted})
SQL;
                return collect(Hana::query($sql))->keyBy('FormatCode')->toArray();
            }

            return DB::connection('sap')
                ->table('OACT')
                ->select('AcctCode', 'AcctName', 'FormatCode')
                ->whereIn('FormatCode', $formatCodes)
                ->get()
                ->keyBy('FormatCode')
                ->map(fn($r) => (array) $r)
                ->toArray();
        } catch (Throwable $e) {
            $this->command?->warn('No se pudo consultar SAP: ' . $e->getMessage());
            return [];
        }
    }
}
