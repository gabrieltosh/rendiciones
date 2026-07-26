<?php

namespace App\Services\Sap;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Tipos de los campos de usuario (UDF) que se exportan en cada línea del asiento.
 *
 * El Service Layer acepta cualquier valor como texto, pero el DI API asigna
 * mediante UserFields.Fields.Item("U_X").Value y exige el tipo correcto
 * (db_Date => DateTime, db_Float => double). Esta clase centraliza el mapeo
 * entre el nombre del parámetro en la tabla `management` y el tipo SAP.
 */
class UdfTypes
{
    public const TYPE_DATE = 'Date';

    public const TYPE_FLOAT = 'Float';

    public const TYPE_ALPHA = 'Alpha';

    /** Parámetros de `management` (grupo accountability_detail) que son fechas. */
    private const DATE_FIELDS = ['date'];

    /** Parámetros de `management` (grupo accountability_detail) que son numéricos. */
    private const FLOAT_FIELDS = [
        'amount',
        'discount',
        'excento',
        'rate',
        'gift_card',
        'rate_zero',
        'ice',
    ];

    /**
     * Tipo SAP correspondiente al nombre del parámetro en `management`.
     */
    public static function forField(string $field): string
    {
        if (in_array($field, self::DATE_FIELDS, true)) {
            return self::TYPE_DATE;
        }

        if (in_array($field, self::FLOAT_FIELDS, true)) {
            return self::TYPE_FLOAT;
        }

        return self::TYPE_ALPHA;
    }

    /**
     * Mapa [nombre del UDF en SAP => tipo] para enviar al servicio DI API.
     *
     * @param  Collection  $management  filas del grupo accountability_detail
     */
    public static function map(Collection $management): array
    {
        $types = [];

        foreach ($management as $param) {
            $udf = trim((string) $param->value);

            if ($udf === '') {
                continue;
            }

            $types[$udf] = self::forField($param->name);
        }

        return $types;
    }

    /**
     * Normaliza el valor según el tipo del campo: fechas a Y-m-d y montos a float.
     * Los valores nulos o vacíos se devuelven tal cual para no alterar el payload.
     */
    public static function normalize(string $field, $value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        switch (self::forField($field)) {
            case self::TYPE_DATE:
                try {
                    return Carbon::parse($value)->format('Y-m-d');
                } catch (Throwable $e) {
                    return $value;
                }
            case self::TYPE_FLOAT:
                return is_numeric($value) ? (float) $value : $value;
            default:
                return $value;
        }
    }
}
