<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Management;
class ManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Management::insert([
            //User Export
            [
                'group'=>'accountability',
                'name'=>'user',
                'label'=>'Usuario SAP',
                'value'=>'manager',
                'type'=>'text',
            ],
            [
                'group'=>'accountability',
                'name'=>'password',
                'label'=>'Password SAP',
                'type'=>'password',
                'value'=>'Novanexa.24'
            ],
            //OCRD Empleados Habilitados
            [
                'group'=>'employee',
                'name'=>'employee_enablement_field',
                'label'=>'Habilitación Usuario',
                'type'=>'text',
                'value'=>'U_emp'
            ],
            [
                'group'=>'employee',
                'name'=>'employee_enablement_field_value',
                'label'=>'Habilitación Usuario Valor',
                'type'=>'text',
                'value'=>'SI'
            ],
            //Detalle Rendición
            [
                'group'=>'accountability_detail',
                'name'=>'date',
                'label'=>'Fecha Factura',
                'type'=>'text',
                'value'=>'U_FECHAFAC'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'document_number',
                'label'=>'Numero Factura/Documento',
                'type'=>'text',
                'value'=>'U_NumDoc'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'authorization_number',
                'label'=>'Nº Autorización',
                'type'=>'text',
                'value'=>'U_AUTO'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'cuf',
                'label'=>'CUF',
                'type'=>'text',
                'value'=>'U_CUF'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'control_code',
                'label'=>'Codigo Control',
                'type'=>'text',
                'value'=>'U_CodCtrl'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'business_name',
                'label'=>'Razón Social',
                'type'=>'text',
                'value'=>'U_RSocial'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'nit',
                'label'=>'NIT',
                'type'=>'text',
                'value'=>'U_NIT'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'amount',
                'label'=>'Monto',
                'type'=>'text',
                'value'=>'U_Monto'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'discount',
                'label'=>'Descuento',
                'type'=>'text',
                'value'=>'U_Dsc'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'excento',
                'label'=>'Excento',
                'type'=>'text',
                'value'=>'U_Exento'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'rate',
                'label'=>'Tasas',
                'type'=>'text',
                'value'=>'U_Tasas'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'gift_card',
                'label'=>'Gif Card',
                'type'=>'text',
                'value'=>'U_gifcard'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'rate_zero',
                'label'=>'Tasa Cero',
                'type'=>'text',
                'value'=>'U_RateZ'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'ice',
                'label'=>'ICE',
                'type'=>'text',
                'value'=>'U_ICE'
            ],
        ]);
    }
}
