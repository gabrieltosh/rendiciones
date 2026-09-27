<?php

namespace Database\Seeders\Copa;

use App\Helpers\Hana;
use App\Models\DetailAccounts;
use App\Models\Document;
use App\Models\DocumentDetail;
use App\Models\GeneralAccounts;
use App\Models\Management;
use App\Models\Profile;
use Config;
use DB;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Perfiles de rendición administrativos de COPA.
 *
 * Fuente: "Datos Rendiciones -Administrativo - Actualizado.xlsx"
 *  - Nombre del perfil  = nombre de la hoja.
 *  - Cuentas Detalle    = columna C ("NUEVAS CUENTAS SAP-REIMP") de cada hoja,
 *                         verificadas contra el plan de cuentas (CuentasCopa.xlsx).
 *  - Cuenta Cabecera    = 1110201 - CAJA CHICA (todas).
 *  - Documentos         = hoja "TIPOS DE RENDICION ADMINISTRATI", columna
 *                         "b. TIPO DE DOCUMENTOS", mapeados a las plantillas de abajo.
 *
 * Cálculo: todas las líneas de prorrateo llevan marcado el check "calculation"
 * (retenciones, commit 2176937), con los % sobre el bruto tal cual el cuadro:
 *  - Grossing Down (IVA): base = importe × (1 - Σ%), la línea va al Debe.
 *    Factura de 100 → gasto 87, crédito fiscal 13, caja chica 100.
 *  - Grossing Up (retenciones): base = importe / (1 - Σ%), las líneas van al Haber.
 *    El usuario registra lo pagado (líquido): 92 → gasto 100, IUE 5, IT 3, caja chica 92.
 *
 * Es idempotente: si ya existe un perfil con el mismo nombre, se omite.
 *
 * Uso: php artisan db:seed --class="Database\\Seeders\\Copa\\CopaProfileSeeder"
 */
class CopaProfileSeeder extends Seeder
{
    /** Cuenta cabecera de todos los perfiles. */
    private const HEADER_ACCOUNT = ['1110201' => 'CAJA CHICA'];

    /** Moneda del perfil (OCRN.CurrCode). null = moneda local de SAP (OADM.MainCurncy). */
    private const CURRENCY = null;

    /**
     * Valores de U_LB_Indicador (UFD1 de JDT1) para `type_document_sap`.
     * Se validan contra SAP al ejecutar.
     */
    private const SAP_TYPE_FACTURA = '7';     // Factura de proveedores
    private const SAP_TYPE_RETENCION = '11';  // Retenciones proveedores
    private const SAP_TYPE_NO_APLICA = '0';   // No Aplica

    private const ACCOUNT_CREDITO_FISCAL = '1130601'; // CRÉDITO FISCAL IVA
    private const ACCOUNT_IUE_RETENCION = '2110706';  // I.U.E. RETENCIÓN COMPRAS
    private const ACCOUNT_RC_IVA_RETENCION = '2110707'; // RC-IVA RETENCIÓN SERVICIOS
    private const ACCOUNT_IT_RETENCION = '2110708';   // I.T. RETENCIONES COMPRAS Y SERVICIOS

    private const FACTURA_SERVICIO = 'FACTURA_SERVICIO';
    private const FACTURA_BIENES = 'FACTURA_BIENES';
    private const FACTURA_COMBUSTIBLE = 'FACTURA_COMBUSTIBLE';
    private const RETENCION_BIENES = 'RETENCION_BIENES';
    private const RETENCION_SERVICIOS = 'RETENCION_SERVICIOS';
    private const RECIBO = 'RECIBO';
    private const RECIBO_PASAJES = 'RECIBO_PASAJES';
    private const PROFORMA = 'PROFORMA';
    private const CARNET = 'CARNET';
    private const DEPOSITO = 'DEPOSITO';

    public function run(): void
    {
        $currency = self::CURRENCY ?? $this->getLocalCurrency();
        $this->validateAgainstSap();

        foreach ($this->profiles() as $data) {
            if (Profile::where('name', $data['name'])->exists()) {
                $this->command?->warn("Perfil \"{$data['name']}\" ya existe, se omite.");
                continue;
            }

            DB::transaction(function () use ($data, $currency) {
                $profile = Profile::create([
                    'name'          => $data['name'],
                    'type_currency' => $currency,
                    // La cabecera es una cuenta contable (caja chica), no un socio de negocio.
                    'sin_empleado'  => true,
                ]);

                foreach (self::HEADER_ACCOUNT as $code => $name) {
                    GeneralAccounts::create([
                        'profile_id'   => $profile->id,
                        'account_code' => $code,
                        'format_code'  => $code,
                        'account_name' => $name,
                    ]);
                }

                foreach ($data['accounts'] as $code => $name) {
                    DetailAccounts::create([
                        'profile_id'   => $profile->id,
                        'account_code' => (string) $code,
                        'format_code'  => (string) $code,
                        'account_name' => $name,
                    ]);
                }

                $templates = $this->documentTemplates();
                foreach ($data['documents'] as $key) {
                    $template = $templates[$key];
                    $document = Document::create(array_merge($this->documentDefaults(), $template['document'], [
                        'profile_id' => $profile->id,
                    ]));

                    foreach ($template['detail'] as $detail) {
                        DocumentDetail::create(array_merge(['exento' => 0, 'calculation' => true], $detail, [
                            'document_id' => $document->id,
                        ]));
                    }
                }
            });

            $this->command?->info("Perfil \"{$data['name']}\" creado.");
        }
    }

    private function documentDefaults(): array
    {
        return [
            'ice' => 0,
            'tasas' => 0,
            'exento' => 0,
            'ice_status' => false,
            'tasas_status' => false,
            'exento_status' => false,
            'authorization_number_status' => false,
            'cuf_status' => false,
            'control_code_status' => false,
            'business_name_status' => false,
            'nit_status' => false,
            'discount_status' => false,
            'gift_card_status' => false,
            'rate_zero_status' => false,
        ];
    }

    private function documentTemplates(): array
    {
        // Campos de una factura SIAT
        $factura = [
            'type_document_sap' => self::SAP_TYPE_FACTURA,
            'authorization_number_status' => true,
            'cuf_status' => true,
            'control_code_status' => true,
            'business_name_status' => true,
            'nit_status' => true,
            'discount_status' => true,
        ];
        // Compras sin factura: NRO CARNET (nit), NOMBRE (razón social); el nro. de recibo es el nro. de documento
        $sinFactura = [
            'type_document_sap' => self::SAP_TYPE_NO_APLICA,
            'business_name_status' => true,
            'nit_status' => true,
        ];
        $retencion = ['type_document_sap' => self::SAP_TYPE_RETENCION] + $sinFactura;
        $iva = [
            ['type' => 'IVA', 'type_calculation' => 'Grossing Down', 'percentage' => '13', 'account' => self::ACCOUNT_CREDITO_FISCAL],
        ];

        return [
            // CUENTA DE GASTO 87% / CRÉDITO FISCAL 13% / CAJA CHICA 100%
            self::FACTURA_SERVICIO => [
                'document' => ['name' => 'FACTURA DE COMPRA SERVICIO'] + $factura,
                'detail'   => $iva,
            ],
            // CUENTA DE INVENTARIOS (TRANSITORIA) 87% / CRÉDITO FISCAL 13% / CAJA CHICA 100%
            // (la "cuenta de inventarios" es una cuenta de gasto más: la elige el usuario en el detalle)
            self::FACTURA_BIENES => [
                'document' => ['name' => 'FACTURA DE COMPRA BIENES'] + $factura,
                'detail'   => $iva,
            ],
            // Igual que una factura, pero el usuario registra el importe exento de la factura
            self::FACTURA_COMBUSTIBLE => [
                'document' => ['name' => 'FACTURA DE COMBUSTIBLE', 'exento_status' => true] + $factura,
                'detail'   => $iva,
            ],
            // CUENTA DE INVENTARIOS (TRANSITORIA) 100% / CAJA CHICA 92% / IUE 5% / IT 3%
            self::RETENCION_BIENES => [
                'document' => ['name' => 'COMPRA RETENCIONES BIENES'] + $retencion,
                'detail'   => [
                    ['type' => 'IUE', 'type_calculation' => 'Grossing Up', 'percentage' => '5', 'account' => self::ACCOUNT_IUE_RETENCION],
                    ['type' => 'IT', 'type_calculation' => 'Grossing Up', 'percentage' => '3', 'account' => self::ACCOUNT_IT_RETENCION],
                ],
            ],
            // CUENTA DE GASTO 100% / CAJA CHICA 84% / RC-IVA 13% / IT 3%
            self::RETENCION_SERVICIOS => [
                'document' => ['name' => 'COMPRA RETENCIONES SERVICIOS'] + $retencion,
                'detail'   => [
                    ['type' => 'RC-IVA', 'type_calculation' => 'Grossing Up', 'percentage' => '13', 'account' => self::ACCOUNT_RC_IVA_RETENCION],
                    ['type' => 'IT', 'type_calculation' => 'Grossing Up', 'percentage' => '3', 'account' => self::ACCOUNT_IT_RETENCION],
                ],
            ],
            // REGISTROS OTROS: CUENTA DE GASTO 100% / CAJA CHICA 100%
            self::RECIBO => [
                'document' => ['name' => 'RECIBO'] + $sinFactura,
                'detail'   => [],
            ],
            self::RECIBO_PASAJES => [
                'document' => ['name' => 'RECIBO DE PASAJES'] + $sinFactura,
                'detail'   => [],
            ],
            self::PROFORMA => [
                'document' => ['name' => 'PROFORMA'] + $sinFactura,
                'detail'   => [],
            ],
            self::CARNET => [
                'document' => ['name' => 'CARNET'] + $sinFactura,
                'detail'   => [],
            ],
            self::DEPOSITO => [
                'document' => ['name' => 'DEPOSITO A ENTIDADES'] + $sinFactura,
                'detail'   => [],
            ],
        ];
    }

    private function profiles(): array
    {
        return [
        [
            // CAJA CHICA COMPRAS — FACTURAS, RECIBOS, PROFORMAS, CARNETS
            'name'      => 'CAJA CHICA COMPRAS',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::PROFORMA, self::CARNET],
            'accounts'  => [
                '6110302' => 'SERVICIO DE TE Y REFRIGERIO',
                '5230102' => 'SERVICIO DE TÉ Y REFRIGERIO',
                '6210302' => 'SERVICIO DE TE Y REFRIGERIO',
                '6210408' => 'SERVICIO DE TRANSPORTE',
                '6210704' => 'MANT. MAQUINARIA Y EQUIPO',
                '1150201' => 'OBRAS EN CURSO',
                '6210504' => 'GAS NATURAL',
                '6112212' => 'FORMULARIOS CAJA DE SALUD, MIN. DE TRABAJO Y OTROS',
                '6112201' => 'TRANSPORTE PÚBLICO',
                '1220101' => 'ACTIVOS FIJOS EN TRÁNSITO',
                '5120102' => 'COSTO ALQUILER BIENES MUEBLES FRANQUICIA',
                '6210701' => 'MANT. INFRAESTRUCTURA',
                '6210702' => 'MANT. MUEBLES Y ENSERES',
                '6210903' => 'MATERIAL ELÉCTRICO',
            ],
        ],
        [
            // CAJA CHICA CONTABILIDAD — FACTURAS, RECIBOS, CARNETS
            'name'      => 'CAJA CHICA CONTABILIDAD',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::CARNET],
            'accounts'  => [
                '6110602' => 'INTERNET Y GASTOS DE COMUNICACIÓN',
                '6212201' => 'TRANSPORTE PÚBLICO',
                '6212203' => 'COURRIER Y MENSAJERÍA',
                '6112201' => 'TRANSPORTE PÚBLICO',
                '1130502' => 'OPERACIONES POR LIQUIDAR VENTAS RESTAURANTES',
                '6210903' => 'MATERIAL ELÉCTRICO',
                '6110302' => 'SERVICIO DE TE Y REFRIGERIO',
            ],
        ],
        [
            // CAJA CHICA RECURSOS HUMANOS — FACTURAS, RECIBOS, DEPOSITOS A ENTIDADES, CARNETS
            'name'      => 'CAJA CHICA RR.HH',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::CARNET, self::DEPOSITO],
            'accounts'  => [
                '6112212' => 'FORMULARIOS CAJA DE SALUD, MIN. DE TRABAJO Y OTROS',
                '6110302' => 'SERVICIO DE TE Y REFRIGERIO',
                '6112201' => 'TRANSPORTE PÚBLICO',
                '6212203' => 'COURRIER Y MENSAJERÍA',
                '6110901' => 'MATERIAL DE ESCRITORIO',
            ],
        ],
        [
            // CAJA CHICA PROCESO POLLO (COMBUSTIBLE) — FACTURAS DE COMBUSTIBLE
            'name'      => 'CAJA CHICA PROCESO POLLO COMBUS',
            'documents' => [self::FACTURA_COMBUSTIBLE],
            'accounts'  => [
                '6311002' => 'COMBUSTIBLES Y LUBRICANTES',
            ],
        ],
        [
            // CAJA CHICA PROCESO POLLO — FACTURAS, RECIBOS, CARNETS
            'name'      => 'CAJA CHICA PROCESO POLLO',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::CARNET],
            'accounts'  => [
                '6311002' => 'COMBUSTIBLES Y LUBRICANTES',
                '6212203' => 'COURRIER Y MENSAJERÍA',
                '5230102' => 'SERVICIO DE TÉ Y REFRIGERIO',
                '5230501' => 'MANTENIMIENTO EDIF. CENTRO ALIMENTOS',
                '5230605' => 'UTENSILIOS DE TRABAJO',
                '1150201' => 'OBRAS EN CURSO',
            ],
        ],
        [
            // CAJA CHICA CONTROL DE CALIDAD — FACTURAS, RECIBOS, CARNETS
            'name'      => 'CAJA CHICA CONTROL DE CALIDAD',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::CARNET],
            'accounts'  => [
                '5231505' => 'CONTROL DE CALIDAD',
                '6212205' => 'CONTROL DE CALIDAD',
                '6112201' => 'TRANSPORTE PÚBLICO',
            ],
        ],
        [
            // CAJA CHICA GESTION DE CALIDAD — FACTURAS, RECIBOS, CARNETS
            'name'      => 'CAJA CHICA GESTION DE CALIDAD',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::CARNET],
            'accounts'  => [
                '6212205' => 'CONTROL DE CALIDAD',
                '6112201' => 'TRANSPORTE PÚBLICO',
            ],
        ],
        [
            // CAJA CHICA MANTENIMIENTO — RECIBOS DE PASAJES
            'name'      => 'CAJA CHICA MTTO',
            'documents' => [self::RECIBO_PASAJES],
            'accounts'  => [
                '6212201' => 'TRANSPORTE PÚBLICO',
                '5120102' => 'COSTO ALQUILER BIENES MUEBLES FRANQUICIA',
            ],
        ],
        [
            // GASTOS DE ADMINISTRACION ANTONIO SAINZ (CXP) — FACTURAS, RECIBOS, DEPOSITOS A ENTIDADES, CARNETS
            'name'      => 'GASTOS DE ADM',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::CARNET, self::DEPOSITO],
            'accounts'  => [
                '6112212' => 'FORMULARIOS CAJA DE SALUD, MIN. DE TRABAJO Y OTROS',
                '6112201' => 'TRANSPORTE PÚBLICO',
                '6212203' => 'COURRIER Y MENSAJERÍA',
                '6110901' => 'MATERIAL DE ESCRITORIO',
                '6110406' => 'GASTOS LEGALES',
                '6110701' => 'MANT. INFRAESTRUCTURA',
            ],
        ],
        [
            // GASTOS LEGALES BETY BOSQUE (CXP) — FACTURAS, RECIBOS, DEPOSITOS A ENTIDADES, CARNETS
            'name'      => 'GASTOS TRAMITES BETTY BOSQUE',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::CARNET, self::DEPOSITO],
            'accounts'  => [
                '6110406' => 'GASTOS LEGALES',
                '6112212' => 'FORMULARIOS CAJA DE SALUD, MIN. DE TRABAJO Y OTROS',
                '6110903' => 'MATERIAL ELÉCTRICO',
                '6210406' => 'GASTOS LEGALES',
            ],
        ],
        [
            // REEMBOLSO VIATICOS POR VIAJES A SCZ (CXP) — FACTURAS, RECIBOS, CARNETS
            'name'      => 'VIATICOS-VIAJES A SCZ',
            'documents' => [self::FACTURA_SERVICIO, self::FACTURA_BIENES, self::RECIBO, self::RETENCION_BIENES, self::RETENCION_SERVICIOS, self::CARNET],
            'accounts'  => [
                '6112206' => 'PASAJES Y VIÁTICOS',
                '6212201' => 'TRANSPORTE PÚBLICO',
                '6210304' => 'LAVANDERÍA Y OTROS',
                '6210303' => 'GASTOS MÉDICOS Y FARMACEÚTICOS',
            ],
        ],
        [
            // REPOSICION GASTOS COMPRA COMBUSTIBLE-BETTY ARIAS (CXP) — FACTURAS DE COMBUSTIBLE
            'name'      => 'BETY ARIAS-REMMB COMBUSTIBLES',
            'documents' => [self::FACTURA_COMBUSTIBLE],
            'accounts'  => [
                '6311002' => 'COMBUSTIBLES Y LUBRICANTES',
                '6111002' => 'COMBUSTIBLES Y LUBRICANTES',
            ],
        ],
        ];
    }

    private function isHana(): bool
    {
        return Management::where('group', 'accountability')->where('name', 'hana_enable')->first()?->value == 'SI';
    }

    private function getLocalCurrency(): string
    {
        try {
            if ($this->isHana()) {
                $db = Config::get('database.connections.hana.database');
                $currency = Hana::query("select \"MainCurncy\" from {$db}.OADM")[0]['MainCurncy'] ?? null;
            } else {
                $currency = DB::connection('sap')->table('OADM')->value('MainCurncy');
            }
            if ($currency) {
                return $currency;
            }
        } catch (Throwable $e) {
            $this->command?->warn('No se pudo leer la moneda local de SAP: ' . $e->getMessage());
        }

        throw new \RuntimeException('No se pudo determinar la moneda. Defina CopaProfileSeeder::CURRENCY.');
    }

    /**
     * Comprueba que las cuentas existan en OACT y que los tipos de documento
     * sean valores válidos de U_LB_Indicador. Solo avisa: no detiene el seeder.
     */
    private function validateAgainstSap(): void
    {
        $codes = array_keys(self::HEADER_ACCOUNT);
        foreach ($this->profiles() as $profile) {
            $codes = array_merge($codes, array_map('strval', array_keys($profile['accounts'])));
        }
        $codes = array_values(array_unique(array_merge($codes, [
            self::ACCOUNT_CREDITO_FISCAL,
            self::ACCOUNT_IUE_RETENCION,
            self::ACCOUNT_RC_IVA_RETENCION,
            self::ACCOUNT_IT_RETENCION,
        ])));

        try {
            $field = Management::where('name', 'document_type')->first()?->value;
            $aliasId = preg_replace('/^U_/', '', (string) $field, 1);

            if ($this->isHana()) {
                $db = Config::get('database.connections.hana.database');
                $in = implode(',', array_map(fn($c) => "'" . addslashes($c) . "'", $codes));
                $existing = array_column(Hana::query("select \"AcctCode\" from {$db}.OACT where \"AcctCode\" in ({$in})"), 'AcctCode');
                $types = array_column(Hana::query(
                    "select T2.\"FldValue\" from {$db}.CUFD T1 inner join {$db}.UFD1 T2 on T1.\"TableID\" = T2.\"TableID\" and T1.\"FieldID\" = T2.\"FieldID\" where T1.\"TableID\" = 'JDT1' and T1.\"AliasID\" = '{$aliasId}'"
                ), 'FldValue');
            } else {
                $existing = DB::connection('sap')->table('OACT')->whereIn('AcctCode', $codes)->pluck('AcctCode')->all();
                $types = DB::connection('sap')->table('CUFD as T1')
                    ->join('UFD1 as T2', fn($j) => $j->on('T1.TableID', 'T2.TableID')->on('T1.FieldID', 'T2.FieldID'))
                    ->where('T1.TableID', 'JDT1')->where('T1.AliasID', $aliasId)
                    ->pluck('T2.FldValue')->all();
            }
        } catch (Throwable $e) {
            $this->command?->warn('No se pudo validar contra SAP: ' . $e->getMessage());
            return;
        }

        if ($missing = array_diff($codes, array_map('strval', $existing))) {
            $this->command?->warn('Cuentas no encontradas en SAP (OACT): ' . implode(', ', $missing));
        }
        foreach ([self::SAP_TYPE_FACTURA, self::SAP_TYPE_RETENCION, self::SAP_TYPE_NO_APLICA] as $type) {
            if (!in_array($type, array_map('strval', $types), true)) {
                $this->command?->warn("Tipo de documento \"{$type}\" no es un valor válido de {$field}. Valores: " . implode(', ', $types));
            }
        }
    }
}
