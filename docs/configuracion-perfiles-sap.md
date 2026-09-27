# Configuración de Perfiles y Exportación a SAP

Sistema de Rendiciones — Guía técnica de configuración de documentos y generación de asientos contables.

---

## Flujo General

```
Perfil (Profile)
 └─ Documento (Document)          ← define cómo se exporta UN tipo de gasto a SAP
      ├─ Tasas fijas / variables   ← tasas (IVA), ice (ICE), exento
      ├─ Checkboxes de factura     ← qué campos captura el usuario al registrar gastos
      ├─ Prorrateo (DocumentDetail)    ← líneas del asiento contable en SAP
      └─ Campos Adicionales (DocumentField)  ← ajustes al monto base (anticipos, retenciones)
```

Cuando una rendición se **autoriza**, el sistema genera un `JournalVoucher` en SAP usando
la configuración del perfil como plantilla de cálculo.

---

## 1. Campos del Documento

### `type_document_sap`

Tipo de documento SAP del asiento (`JDT1`). Determina la clasificación del diario en SAP
(ej: "Factura Compras", "Nota Débito", etc.). Los valores disponibles se obtienen desde
la tabla UDF de SAP configurada en la tabla `management`.

---

### Tasas fijas y sus checkboxes "Variable"

Cada campo numérico tiene un checkbox que controla si el valor es **fijo** (definido en el
perfil) o **variable** (el usuario lo ingresa al cargar cada gasto):

| Campo numérico | Checkbox `_status = false` (Fijo) | Checkbox `_status = true` (Variable) |
|---|---|---|
| **`tasas`** (% IVA u otro impuesto) | Se aplica el % configurado en el perfil | El usuario ingresa el monto de tasas por cada documento |
| **`ice`** (% ICE — excise tax) | Se aplica el % configurado en el perfil | El usuario ingresa el monto ICE por cada documento |
| **`exento`** (% exento en prorrateo) | Se calcula como `monto × exento%` de la línea | El usuario ingresa el monto exento por cada documento |

**Lógica en el controlador de exportación:**

```php
$total_ice    = $doc_line->ice_status
    ? $doc_line->ice                          // valor ingresado por usuario
    : $amount_line * $ice_percentage;         // % fijo del perfil

$total_tasas  = $doc_line->tasas_status
    ? $doc_line->tasas
    : $amount_line * $rate_percentage;

$total_excento = $doc_line->exento_status
    ? $doc_line->exento
    : $amount_line * $exento_percentage;      // exento_percentage viene de la línea de prorrateo
```

---

### Checkboxes de campos de factura

Determinan qué campos se muestran y son requeridos cuando el usuario registra un gasto
dentro de una rendición:

| Checkbox | Campo que habilita en el formulario de gasto |
|---|---|
| `authorization_number_status` | Nº de Autorización |
| `cuf_status` | CUF (Código Único de Factura — SIAT Bolivia) |
| `control_code_status` | Código de Control |
| `business_name_status` | Razón Social del proveedor |
| `nit_status` | NIT del proveedor |
| `discount_status` | Descuento |
| `gift_card_status` | Gift Card |
| `rate_zero_status` | Tasa Cero |

> Si el checkbox está **activo** → el campo aparece en el formulario de carga de gasto
> y su valor se guarda en el detalle de la rendición.

---

## 2. Prorrateo del Asiento (`DocumentDetail`)

Cada línea de prorrateo define **una cuenta contable** en el asiento SAP y cómo se calcula
su monto. Un documento puede tener **N líneas de prorrateo**, cada una con su propia lógica.

### Columnas de cada línea

#### `Tipo`

Opciones: `IVA` · `IT` · `IUE` · `RC-IVA` · `EXENTO` · `TASA` · `ICE`

Actualmente es un campo **informativo / organizativo**. Cuando se selecciona `EXENTO`,
`TASA` o `ICE`, la cuenta se establece como `'-'` automáticamente en la UI, indicando
que esa línea no genera una cuenta contable propia.

> La lógica de cálculo **no cambia** según el tipo — el tipo sirve para identificar
> visualmente el propósito de cada línea.

---

#### `Calculo` (`type_calculation`) y check `calculation`

| Valor | Efecto en el asiento |
|---|---|
| **Grossing Down** | Monto sobre la base del crédito fiscal → **Debe**. Se resta del gasto (ej. IVA 13%). |
| **Grossing Up** | Monto sobre el bruto → **Haber**. Se suma al gasto (ej. retenciones IUE, IT, RC-IVA). |

El check **`calculation`** en una línea Grossing Up indica que el usuario registra el
importe **pagado (líquido)** y que hay que calcular el bruto:
`bruto = importe / (1 − Σ% de las líneas Grossing Up marcadas)`. En Grossing Down no tiene efecto.

---

#### `Cuenta`

Código de cuenta contable de SAP (tabla `OACT`) de la línea.

---

#### `Porcentaje`

Porcentaje sobre el bruto (Grossing Up) o sobre la base del crédito fiscal (Grossing Down),
tal como lo define la norma (ej. IUE 5%, IT 3%, IVA 13%).

---

#### `% Exento`

Porcentaje fijo del importe que se considera exento para esta línea. Si el documento tiene
`exento_status = true` y el usuario ingresa el monto exento, se usa ese monto.

---

### Fórmula completa de cálculo del asiento

Implementada en `App\Services\Sap\DocumentLineCalculator` (única fuente, la usan el export
y la vista previa; cubierta por `tests/Unit/DocumentLineCalculatorTest.php`).

```
1. importe = monto ± campos adicionales (Crédito suma, Débito resta)
2. bruto   = importe / (1 − Σ% Grossing Up con calculation)
3. exento  = monto exento ingresado, o importe × %exento de la línea
   ICE     = ICE ingresado,           o importe × % ICE del documento
   tasas   = tasas ingresadas,        o importe × % tasas del documento
4. Grossing Up   → Haber = bruto × %
   Grossing Down → Debe  = (bruto − exento − ICE − tasas) × %
5. Gasto (Debe)  = importe + Σ Grossing Up − Σ Grossing Down
6. Contrapartida (caja chica / empleado, Haber) = Σ Debe − Σ Haber = importe
```

Cada línea se redondea a 2 decimales; el asiento siempre cuadra.

| Documento | Configuración | Importe | Asiento |
|---|---|---|---|
| Factura | IVA 13% Grossing Down | 100 | Gasto 87 D · IVA 13 D · Caja 100 H |
| Compra retenciones bienes | IUE 5% + IT 3% Grossing Up ✔ | 92 (pagado) | Gasto 100 D · IUE 5 H · IT 3 H · Caja 92 H |
| Compra retenciones servicios | RC-IVA 13% + IT 3% Grossing Up ✔ | 84 (pagado) | Gasto 100 D · RC-IVA 13 H · IT 3 H · Caja 84 H |
| Factura combustible | IVA 13% Down, exento ingresado 30 | 100 | Gasto 90.90 D · IVA 9.10 D · Caja 100 H |
| Registros otros | Sin prorrateo | 50 | Gasto 50 D · Caja 50 H |

---

## 3. Campos Adicionales (`DocumentField`)

Ajustan el `amount_line` **antes** de calcular el prorrateo. Sirven para anticipos,
retenciones, complementos u otros conceptos que deben aparecer como líneas en el asiento.

| Campo | Descripción |
|---|---|
| `name` | Etiqueta visible para el usuario al registrar el gasto (ej: "Anticipo aplicado") |
| `account` | Cuenta contable de SAP donde va el ajuste |
| `type_calculation` | **Crédito** → suma al monto base · **Débito** → resta del monto base |

**Lógica:**
```php
// Por cada campo adicional del documento:
$amount_line += ($tipo == 'Credito' ? +1 : -1) * $valor_ingresado

// Genera una línea en el asiento:
Debe  ← si type_calculation == 'Débito'
Haber ← si type_calculation == 'Crédito'
```

---

## 4. Opciones disponibles en los selectores

| Selector | Opciones |
|---|---|
| **Tipo** (prorrateo) | `IVA` · `IT` · `IUE` · `RC-IVA` · `EXENTO` · `TASA` · `ICE` |
| **Calculo** (prorrateo) | `Grossing Up` · `Grossing Down` |
| **type_calculation** (campos adicionales) | `Debito` · `Credito` |

---

## 5. Integración con SAP Service Layer

Al autorizar la rendición, el sistema:

1. Recorre todos los `AccountabilityDetail` de la rendición
2. Ejecuta `HandleFormatLine()` por cada documento → genera las líneas del asiento
3. Autentica contra SAP Service Layer: `POST {url}/b1s/v1/Login`
4. Crea el asiento: `POST JournalVouchersService_Add`

```json
{
  "JournalVoucher": {
    "JournalEntry": {
      "Memo": "<descripción de la rendición>",
      "ReferenceDate": "<fecha_fin>",
      "TaxDate": "<fecha_fin>",
      "DueDate": "<fecha_fin>",
      "JournalEntryLines": [ ...líneas generadas... ]
    }
  }
}
```

Credenciales y URL del Service Layer se configuran en la tabla `management`
con `group = 'accountability'`.
