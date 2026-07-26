# Servicio intermedio DI API (bridge .NET)

Contrato entre el Sistema de Rendiciones (Laravel) y el servicio .NET que expone el
DI API de SAP Business One como API REST interna.

## Por qué existe

La exportación del asiento contable se hacía únicamente por **Service Layer**. En
SAP B1 **versión 9 sobre SQL Server no hay Service Layer** (llegó a MSSQL recién en
la 9.3), así que la única vía es el **DI API**, que es COM STA de 32 bits y no es
usable de forma estable desde PHP sobre IIS:

- exige un pool de aplicaciones IIS en 32 bits;
- cada `Company.Connect()` cuesta segundos y consume una licencia;
- el objeto `Company` solo puede usarse desde el hilo que lo creó (STA), lo que
  choca con el reciclado de procesos e hilos de IIS.

Por eso toda la interacción con el DI API se aísla en un proceso .NET dedicado,
instalado como **Windows Service** (no bajo IIS), que mantiene conexiones vivas y
que Laravel consume por HTTP.

## Configuración del lado Laravel

Todo se parametriza desde **Parametrización → paso Rendición** (tabla `management`,
grupo `accountability`):

| Parámetro | Descripción |
|---|---|
| `export_mode` | `SL` = Service Layer (por defecto), `DI` = servicio DI API |
| `bridge_url` | URL base del servicio .NET, ej. `http://127.0.0.1:5001` |
| `bridge_api_key` | API key que el servicio valida en el header `X-Api-Key` |
| `bridge_timeout` | Timeout en segundos del request (por defecto 120) |

Cambiar `export_mode` alterna el driver sin redeploy: es también el mecanismo de
rollback si el servicio .NET falla.

Código relacionado:

- `app/Services/Sap/SapExporterFactory.php` — resuelve el driver según `export_mode`
- `app/Services/Sap/Drivers/DiApiBridgeExporter.php` — cliente HTTP del bridge
- `app/Services/Sap/JournalVoucherPayloadBuilder.php` — arma el payload (común a ambos modos)

## Endpoints

### `GET /health`

```json
{ "status": "ok", "pool": { "size": 2, "connected": 2 } }
```

### `POST /journal-vouchers`

Headers: `X-Api-Key`, `Idempotency-Key`, `Content-Type: application/json`.

```json
{
  "JournalVoucher": {
    "JournalEntry": {
      "Memo": "Rendición de gastos marzo",
      "ReferenceDate": "2026-03-31",
      "TaxDate": "2026-03-31",
      "DueDate": "2026-03-31",
      "U_Usuario": "Juan Perez",
      "JournalEntryLines": [
        {
          "AccountCode": "5110101",
          "Debit": 1200.5,
          "Credit": 0,
          "ShortName": "5110101",
          "LineMemo": "Almuerzo de trabajo",
          "ProjectCode": "P001",
          "CostingCode": "CC01",
          "CostingCode2": null,
          "U_FechaDeFactura": "2026-03-14",
          "U_NumeroDeFactura": "12345",
          "U_CUF": "...",
          "U_Importe": 1200.5
        }
      ]
    }
  },
  "UdfTypes": {
    "U_FechaDeFactura": "Date",
    "U_Importe": "Float",
    "U_CUF": "Alpha"
  }
}
```

Respuestas:

```json
200 { "success": true, "key": "123" }
400 { "success": false, "error": { "code": -5002, "message": "..." } }
```

- `key` es la clave del asiento preliminar creado (`GetNewObjectKey()`). Laravel la
  guarda en `accountabilities.sap_trans_id`. El Service Layer no devuelve este dato,
  por eso la columna queda nula en modo `SL`.
- `error.message` se muestra tal cual al usuario en la pantalla de autorización, que
  ofrece "Autorizar sin SAP" cuando la exportación falla.

Solo estos dos endpoints: los UDF ya están creados en SAP, el bridge no administra metadata.

## Reglas de implementación del servicio .NET

**Objeto SAP.** Se crea un **asiento preliminar**, igual que hoy por Service Layer
(`JournalVouchersService_Add`): usar `BoObjectTypes.oJournalVouchers` →
`JournalVouchers.JournalEntry` + `.Lines.Add()` por cada línea. **No** usar
`oJournalEntries`: crearía el asiento definitivo y cambiaría el flujo contable.

**Campos de usuario.** En el Service Layer los UDF son simples claves JSON; en DI API
se asignan con `Lines.UserFields.Fields.Item("U_X").Value` y el tipo importa. Usar el
bloque `UdfTypes` para castear antes de asignar:

| `UdfTypes` | Tipo SAP | Tipo .NET |
|---|---|---|
| `Date` | `db_Date` | `DateTime` |
| `Float` | `db_Float` | `double` |
| `Alpha` | `db_Alpha` | `string` |

Las claves `ProjectCode` y `CostingCode`..`CostingCode5` **no** son UDF: son campos
nativos de la línea del asiento.

**Idempotencia (crítico).** Un timeout HTTP con el `Add()` ya ejecutado en SAP
duplicaría un asiento contable. El servicio debe persistir cada `Idempotency-Key`
junto con su respuesta y, ante un reintento con la misma clave, devolver la respuesta
original **sin volver a ejecutar `Add()`**. Laravel envía `acc-{id de la rendición}`.

**Transacción y errores.** Envolver el `Add()` en `StartTransaction`/`EndTransaction`,
leer la clave con `GetNewObjectKey()` y devolver `GetLastErrorCode()` /
`GetLastErrorDescription()` en el bloque `error`.

**Pool de conexiones.** El volumen es bajo (una exportación por autorización de
rendición): `N = 1..2` conexiones alcanza, y cada una consume una licencia DI API
mientras esté conectada. Lo importante no es el tamaño sino el **health-check y la
reconexión automática**: las conexiones DI API se caen solas y hay que detectarlo
antes de despachar. Cada conexión vive en su propio hilo STA.

**Compilación.** .NET Framework 4.8, compilado **x86 si el DI API instalado es de 32
bits** (o x64 si instalaron el de 64). `AnyCPU` falla en runtime al cargar
`Interop.SAPbobsCOM.dll`.

**Despliegue y seguridad.** Windows Service con arranque automático y reinicio ante
fallas (es un punto único de falla para la integración). Si corre en el mismo
servidor que IIS, bindear el listener a `127.0.0.1`; si está separado, abrir el
puerto solo hacia el servidor de Laravel y exigir la API key en todos los endpoints.

**Fuera de alcance.** Las lecturas de SAP (OCRD, OOCR, OPRJ, OACT, ORTT) siguen
haciéndose por SQL directo (`DB::connection('sap')` o `App\Helpers\Hana`); no deben
pasar por el pool DI API.
