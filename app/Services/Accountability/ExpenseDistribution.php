<?php

namespace App\Services\Accountability;

use App\Models\AccountabilityDetail;
use App\Models\AccountabilityDetailLine;
use App\Models\AccountabilityDetailSplit;
use App\Models\DetailAccounts;
use App\Services\Sap\DistributionRules;
use Illuminate\Support\Collection;

/**
 * Reparto del gasto de un documento en varias líneas del asiento.
 *
 * Cada dimensión (Centro de Costo 1..5) puede repartirse entre varios códigos con su %.
 * Las líneas salen de combinar los repartos de todas las dimensiones (producto
 * cartesiano) y el % de cada línea es el producto de los % de sus códigos:
 *
 *   CC1: A 50 · B 50   y   CC2: X 30 · Y 70   →   A·X 15, A·Y 35, B·X 15, B·Y 35
 *
 * Con reparto, los centros de costo salen solo del reparto (una dimensión con un
 * único código al 100 % no multiplica las líneas); una dimensión sin códigos queda
 * vacía y no se usan los de Datos Contables. Cada línea puede tener su propia cuenta y glosa; si no, usa las del
 * documento. Solo se reparte la línea de gasto: impuestos y retenciones se calculan
 * como siempre sobre el total (DocumentLineCalculator).
 */
class ExpenseDistribution
{
    public const MAX_LINES = 50;

    /** Columna del documento / línea para cada número de dimensión. */
    public const DIMENSION_FIELDS = [
        1 => 'distribution_rule_one',
        2 => 'distribution_rule_second',
        3 => 'distribution_rule_three',
        4 => 'distribution_rule_four',
        5 => 'distribution_rule_five',
    ];

    /** Campo de la línea del asiento SAP para cada número de dimensión. */
    public const COSTING_FIELDS = [
        1 => 'CostingCode',
        2 => 'CostingCode2',
        3 => 'CostingCode3',
        4 => 'CostingCode4',
        5 => 'CostingCode5',
    ];

    /**
     * Reparto tal como llega del formulario, normalizado y ordenado por dimensión.
     *
     * @return array<int, array<int, array{code: string, percentage: float}>>
     */
    public static function normalize($splits): array
    {
        $result = [];
        foreach ((array) $splits as $dim => $entries) {
            $dim = (int) $dim;
            if (! isset(self::DIMENSION_FIELDS[$dim]) || ! is_array($entries)) {
                continue;
            }

            $rows = [];
            foreach ($entries as $entry) {
                $code = DistributionRules::code($entry['code'] ?? null);
                if ($code !== null) {
                    $rows[] = ['code' => $code, 'percentage' => round((float) ($entry['percentage'] ?? 0), 4)];
                }
            }

            if ($rows) {
                $result[$dim] = $rows;
            }
        }
        ksort($result);

        return $result;
    }

    /**
     * Errores del reparto normalizado, por clave del formulario.
     *
     * @return array<string, string>
     */
    public static function errors(array $splits): array
    {
        $errors = [];
        foreach ($splits as $dim => $rows) {
            $codes = array_column($rows, 'code');
            if (count($codes) !== count(array_unique($codes))) {
                $errors["splits.$dim"] = "Centro de Costo $dim: hay un código repetido en el reparto.";

                continue;
            }

            foreach ($rows as $row) {
                if ($row['percentage'] <= 0) {
                    $errors["splits.$dim"] = "Centro de Costo $dim: cada porcentaje debe ser mayor a 0.";

                    continue 2;
                }
            }

            $sum = round(array_sum(array_column($rows, 'percentage')), 4);
            if (abs($sum - 100) > 0.01) {
                $errors["splits.$dim"] = "Centro de Costo $dim: el reparto suma {$sum} % y debe sumar 100 %.";
            }
        }

        $count = self::lineCount($splits);
        if ($count > self::MAX_LINES) {
            $errors['splits'] = "El reparto genera {$count} líneas; el máximo es ".self::MAX_LINES.'.';
        }

        return $errors;
    }

    public static function lineCount(array $splits): int
    {
        return $splits ? array_product(array_map('count', $splits)) : 0;
    }

    /**
     * Líneas que resultan de combinar los repartos de todas las dimensiones.
     *
     * @return array<int, array{combination: string, codes: array<int, string>, percentage: float}>
     */
    public static function combinations(array $splits): array
    {
        $combos = [['codes' => [], 'percentage' => 100.0]];
        foreach ($splits as $dim => $rows) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($rows as $row) {
                    $next[] = [
                        'codes' => $combo['codes'] + [$dim => $row['code']],
                        'percentage' => $combo['percentage'] * $row['percentage'] / 100,
                    ];
                }
            }
            $combos = $next;
        }

        return array_map(fn ($combo) => [
            'combination' => self::combinationKey($combo['codes']),
            'codes' => $combo['codes'],
            'percentage' => round($combo['percentage'], 8),
        ], $combos);
    }

    /** "1:CC01|2:CC05": identifica una línea aunque se regenere el reparto. */
    public static function combinationKey(array $codes): string
    {
        ksort($codes);

        return implode('|', array_map(fn ($dim, $code) => "$dim:$code", array_keys($codes), $codes));
    }

    /**
     * Guarda el reparto del documento y regenera sus líneas. Sin reparto, el documento
     * vuelve a exportarse como una sola línea de gasto.
     *
     * @param  mixed  $splits  reparto por dimensión, como llega del formulario
     * @param  mixed  $lines  [{combination, account, concept}]: cuenta y glosa propias de cada línea
     */
    public static function sync(AccountabilityDetail $detail, $splits, $lines): void
    {
        AccountabilityDetailSplit::where('accountability_detail_id', $detail->id)->delete();
        AccountabilityDetailLine::where('accountability_detail_id', $detail->id)->delete();

        $splits = self::normalize($splits);
        if (! $splits) {
            return;
        }

        // Con reparto, los centros de costo salen solo del reparto: los de Datos Contables
        // no se usan. El documento guarda el primer código de cada dimensión repartida
        // (lo usan las líneas de impuestos y los listados) y deja vacías las demás.
        $first = array_fill_keys(self::DIMENSION_FIELDS, null);
        foreach ($splits as $dim => $rows) {
            $first[self::DIMENSION_FIELDS[$dim]] = $rows[0]['code'];
            foreach ($rows as $row) {
                AccountabilityDetailSplit::create([
                    'accountability_detail_id' => $detail->id,
                    'dimension' => $dim,
                    'code' => $row['code'],
                    'percentage' => $row['percentage'],
                ]);
            }
        }
        $detail->fill($first)->save();

        $overrides = collect(is_array($lines) ? $lines : [])->keyBy('combination');
        foreach (self::combinations($splits) as $combo) {
            $override = $overrides[$combo['combination']] ?? [];
            [$account, $accountName] = self::resolveAccount($override['account'] ?? null, $detail);
            $concept = trim((string) ($override['concept'] ?? ''));

            $dims = [];
            foreach (self::DIMENSION_FIELDS as $dim => $field) {
                $dims[$field] = $combo['codes'][$dim] ?? null;
            }

            AccountabilityDetailLine::create(array_merge([
                'accountability_detail_id' => $detail->id,
                'combination' => $combo['combination'],
                'percentage' => $combo['percentage'],
                'account' => $account,
                'account_name' => $accountName,
                'concept' => $concept === '' ? null : $concept,
            ], $dims));
        }
    }

    /**
     * Reparto y líneas en el formato del formulario de edición.
     *
     * @param  Collection  $accounts  opciones de cuenta del formulario (key, account_code, account_name)
     */
    public static function forForm(AccountabilityDetail $detail, Collection $accounts): array
    {
        $splits = [];
        foreach ($detail->splits as $split) {
            $splits[$split->dimension][] = ['code' => $split->code, 'percentage' => $split->percentage];
        }

        // El controlador de edición reemplaza account por la clave del select: se compara con el original.
        $docAccount = $detail->getOriginal('account');
        $docAccountName = $detail->getOriginal('account_name');
        $lines = $detail->lines->map(function ($line) use ($docAccount, $docAccountName, $accounts) {
            $own = $line->account !== $docAccount || $line->account_name !== $docAccountName;

            return [
                'combination' => $line->combination,
                // null: la línea usa la cuenta del documento (y la sigue si esta cambia)
                'account' => $own
                    ? ($accounts->first(fn ($a) => $a->account_code === $line->account && $a->account_name === $line->account_name)?->key ?? $line->account)
                    : null,
                'concept' => $line->concept,
            ];
        })->values()->all();

        return ['splits' => (object) $splits, 'lines' => $lines];
    }

    /**
     * Líneas de gasto del asiento: el gasto del documento repartido por línea. El
     * redondeo se ajusta en la última para que la suma sea exacta.
     *
     * @return array<int, array>|null  null si el documento no está repartido
     */
    public static function expenseLines($detail, float $expense): ?array
    {
        $lines = collect($detail->lines ?? [])->values();
        if ($lines->isEmpty()) {
            return null;
        }

        $result = [];
        $assigned = 0.0;
        $last = $lines->count() - 1;
        foreach ($lines as $i => $line) {
            $amount = $i === $last
                ? round($expense - $assigned, 2)
                : round($expense * $line->percentage / 100, 2);
            $assigned += $amount;

            $costing = [];
            foreach (self::DIMENSION_FIELDS as $dim => $field) {
                $costing[self::COSTING_FIELDS[$dim]] = $line->{$field};
            }

            $result[] = [
                'account' => $line->account,
                'account_name' => $line->account_name,
                'amount' => $amount,
                'concept' => $line->concept,
                'costing' => $costing,
            ];
        }

        return $result;
    }

    /** Cuenta de la línea: la elegida (clave "código||alias" del formulario) o la del documento. */
    private static function resolveAccount(?string $key, AccountabilityDetail $detail): array
    {
        if (! $key) {
            return [$detail->account, $detail->account_name];
        }

        $parts = explode('||', $key, 2);
        $code = $parts[0];
        if (isset($parts[1])) {
            return [$code, $parts[1]];
        }

        $account = DetailAccounts::where('account_code', $code)->first();

        return [$code, $account->alias ?? $account->account_name ?? $code];
    }
}
