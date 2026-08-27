<?php

namespace Database\Seeders;

use App\Models\Management;
use Illuminate\Database\Seeder;

/**
 * Actualiza los nombres de UDF/campo en `management` con los nombres físicos
 * reales confirmados en la BD COPABOL_NEW (Desarrollo) vía
 * INFORMATION_SCHEMA.COLUMNS sobre OCRD/JDT1.
 *
 * OJO: `U_User_Rend` y `U_Autorizacion` quedaron creados en SAP con el
 * prefijo `U_` duplicado (`U_U_User_Rend` / `U_U_Autorizacion`), porque al
 * crear el UDF se escribió el nombre completo (con `U_`) en el asistente y
 * SAP antepone su propio `U_` automáticamente. Los demás campos (`LB_...`,
 * `EXX_FE_...`) ya existían de antes (otro add-on) y sí llevan un solo `U_`.
 *
 * `document_type` (U_LB_Indicador) es un smallint con lista de valores
 * válidos cerrada (códigos de clasificación fiscal de SAP) y un valor
 * estándar fijo (7). El select de "Tipo de Documento" en Documentos se
 * arma leyendo esos valores válidos desde CUFD/UFD1 (JDT1.LB_Indicador),
 * no como texto libre — ver ProfileController::HandleGetDocumentType.
 *
 * A diferencia de ManagementSeeder (valores genéricos para una instalación
 * nueva), este seeder es de actualización: corre contra una BD que ya tiene
 * la tabla `management` poblada y sólo pisa el `value` de los parámetros
 * indicados, sin tocar el resto.
 *
 * Uso: php artisan db:seed --class=UpdateCopabolSapFieldsSeeder
 */
class UpdateCopabolSapFieldsSeeder extends Seeder
{
    private array $fields = [
        ['group' => 'accountability_detail', 'name' => 'date', 'value' => 'U_LB_FechaFactura'],
        ['group' => 'accountability_detail', 'name' => 'document_number', 'value' => 'U_LB_NumeroFactura'],
        ['group' => 'accountability_detail', 'name' => 'authorization_number', 'value' => 'U_U_Autorizacion'],
        ['group' => 'accountability_detail', 'name' => 'cuf', 'value' => 'U_EXX_FE_Cuf'],
        ['group' => 'accountability_detail', 'name' => 'control_code', 'value' => 'U_LB_CodigoControl'],
        ['group' => 'accountability_detail', 'name' => 'business_name', 'value' => 'U_LB_RazonSocial'],
        ['group' => 'accountability_detail', 'name' => 'nit', 'value' => 'U_LB_NIT'],
        ['group' => 'accountability_detail', 'name' => 'amount', 'value' => 'U_LB_Importe'],
        ['group' => 'accountability_detail', 'name' => 'discount', 'value' => 'U_LB_DesctoBr'],
        ['group' => 'accountability_detail', 'name' => 'excento', 'value' => 'U_EXX_FE_ImporteExento'],
        ['group' => 'accountability_detail', 'name' => 'rate', 'value' => 'U_EXX_FE_Tasas'],
        ['group' => 'accountability_detail', 'name' => 'gift_card', 'value' => 'U_EXX_FE_MontoGifCard'],
        ['group' => 'accountability_detail', 'name' => 'rate_zero', 'value' => 'U_EXX_FE_Compras_TasaCero'],
        ['group' => 'accountability_detail', 'name' => 'ice', 'value' => 'U_EXX_FE_ImporteICE'],
        ['group' => 'accountability_detail', 'name' => 'document_type', 'value' => 'U_LB_Indicador'],
        ['group' => 'employee', 'name' => 'employee_enablement_field', 'value' => 'U_U_User_Rend'],
    ];

    public function run(): void
    {
        foreach ($this->fields as $field) {
            Management::where('group', $field['group'])
                ->where('name', $field['name'])
                ->update(['value' => $field['value']]);
        }
    }
}
