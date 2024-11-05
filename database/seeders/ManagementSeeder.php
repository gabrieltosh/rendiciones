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
            [
                'group'=>'accountability',
                'name'=>'service_layer',
                'label'=>'URL Service Layer SAP',
                'type'=>'text',
                'value'=>'https://localhost:50000'
            ],
            [
                'group'=>'accountability',
                'name'=>'bd_sap',
                'label'=>'Nombre BD SAP',
                'type'=>'text',
                'value'=>'RENDICIONES'
            ],
            [
                'group'=>'accountability',
                'name'=>'hana_enable',
                'label'=>'Base de Datos HANA',
                'type'=>'text',
                'value'=>'NO'
            ],
            [
                'group'=>'accountability',
                'name'=>'notification_email',
                'label'=>'Notificaciones por Correo',
                'type'=>'text',
                'value'=>'NO'
            ]
            /*[
                'group'=>'accountability',
                'name'=>'list_separator',
                'label'=>'Separador de Listas',
                'type'=>'text',
                'value'=>'--'
            ]*/,
            //OCRD Empleados Habilitados
            [
                'group'=>'employee',
                'name'=>'employee_enablement_field',
                'label'=>'Habilitación Usuario',
                'type'=>'text',
                'value'=>'U_User_Rend'
            ],
            [
                'group'=>'employee',
                'name'=>'employee_enablement_field_value',
                'label'=>'Habilitación Usuario Valor',
                'type'=>'text',
                'value'=>'1'
            ],
            //Detalle Rendición
            [
                'group'=>'accountability_detail',
                'name'=>'date',
                'label'=>'Fecha Factura',
                'type'=>'text',
                'value'=>'U_FechaDeFactura'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'document_number',
                'label'=>'Numero Factura/Documento',
                'type'=>'text',
                'value'=>'U_NumeroDeFactura'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'authorization_number',
                'label'=>'Nº Autorización',
                'type'=>'text',
                'value'=>'U_Autorizacion'
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
                'value'=>'U_CodigoDeControl'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'business_name',
                'label'=>'Razón Social',
                'type'=>'text',
                'value'=>'U_RazonSocial'
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
                'value'=>'U_Importe'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'discount',
                'label'=>'Descuento',
                'type'=>'text',
                'value'=>'U_Descuento'
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
                'value'=>'U_GiftCard'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'rate_zero',
                'label'=>'Tasa Cero',
                'type'=>'text',
                'value'=>'U_TasaCero'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'ice',
                'label'=>'ICE',
                'type'=>'text',
                'value'=>'U_ICE'
            ],
            [
                'group'=>'accountability_detail',
                'name'=>'document_type',
                'label'=>'Tipo de Documento',
                'type'=>'text',
                'value'=>'U_TipoDeDocumento'
            ],
            // Proveedores
            [
                'group'=>'supplier',
                'name'=>'business_name',
                'label'=>'Razón Social',
                'type'=>'text',
                'value'=>'CardFName'
            ],
            [
                'group'=>'supplier',
                'name'=>'nit',
                'label'=>'NIT',
                'type'=>'text',
                'value'=>'LicTradNum'
            ],
            //Empresa
            [
                'group'=>'company',
                'name'=>'company_name',
                'label'=>'Nombre de Empresa',
                'type'=>'text',
                'value'=>'NOVANEXA SRL'
            ],
            [
                'group'=>'company',
                'name'=>'company_location',
                'label'=>'Ubicación',
                'type'=>'text',
                'value'=>'LA Paz - Bolivia'
            ],
            [
                'group'=>'company',
                'name'=>'nit',
                'label'=>'NIT',
                'type'=>'text',
                'value'=>'3123125341'
            ],
            [
                'group'=>'company',
                'name'=>'logo',
                'label'=>'Logo Empresarial',
                'type'=>'file',
                'value'=>'logo.png'
            ],
        ]);
    }
}
