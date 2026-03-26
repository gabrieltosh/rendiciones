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

#### `Calculo` (`type_calculation`)

Controla la dirección del asiento para esta línea:

| Valor | Efecto en el asiento |
|---|---|
| **Grossing Up** | El monto calculado va al **Haber (Credit)** |
| **Grossing Down** | El monto calculado va al **Debe (Debit)** |

---

#### `Cuenta`

Código de cuenta contable de SAP (tabla `OACT`). Es la cuenta donde se acredita o debita
el monto de esta línea del asiento.

---

#### `Porcentaje`

Porcentaje del **monto base calculado** que se asigna a esta cuenta.

```
monto_linea = monto_base × (porcentaje / 100)
```

---

#### `% Exento`

Porcentaje del monto del gasto que se trata como **exento de impuesto** para
**esta línea de prorrateo específicamente**.

- Si `% Exento = 0` → la base del prorrateo es el monto completo del gasto + impuestos
- Si `% Exento > 0` → la base del prorrateo es solo la porción exenta + impuestos

> Este valor puede ser sobreescrito por el usuario documento a documento
> si `exento_status = true` en el documento.

---

### Fórmula completa de cálculo del asiento

```
┌─────────────────────────────────────────────────────────────────────┐
│ 1. Ajuste por Campos Adicionales                                    │
│                                                                     │
│    amount_line = monto_gasto                                        │
│    Por cada campo adicional:                                        │
│      amount_line += (Crédito ? +valor : -valor)                     │
│                                                                     │
│ 2. Calcular impuestos sobre amount_line                             │
│                                                                     │
│    total_ice   = ice_status   ? ice_usuario   : amount_line × ice%  │
│    total_tasas = tasas_status ? tasas_usuario : amount_line × tasas%│
│                                                                     │
│ 3. Por cada línea de prorrateo:                                     │
│                                                                     │
│    total_excento = exento_status                                    │
│                  ? exento_usuario                                   │
│                  : amount_line × (exento_linea% / 100)              │
│                                                                     │
│    base = (total_excento == 0 ? amount_line : total_excento)        │
│           + total_tasas + total_ice                                 │
│                                                                     │
│    monto_linea = base × (porcentaje% / 100)                         │
│                                                                     │
│    → Grossing Up   → Haber (Credit) = monto_linea                  │
│    → Grossing Down → Debe  (Debit)  = monto_linea                  │
│                                                                     │
│ 4. Línea principal (cuenta del gasto del usuario):                  │
│                                                                     │
│    Debe cuenta_gasto = Σ(operaciones) + amount_line                 │
└─────────────────────────────────────────────────────────────────────┘
```

> **Comportamiento del exento:** Cuando una línea tiene `% Exento > 0`, la base de
> cálculo del prorrateo es el **monto exento**, no el monto total. Esto permite separar
> contablemente la porción gravada de la exenta dentro del mismo asiento.

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

## 4. Configuraciones Típicas

### Factura normal con IVA (13%)

```
tasas = 13      tasas_status = false (fijo)
ice   = 0       ice_status   = false

Prorrateo:
  Tipo=IVA · Calculo=Grossing Up · Cuenta=2140 (IVA CF) · Porcentaje=100% · %Exento=0%
```

**Asiento generado** para una factura de Bs 1,000:

| Cuenta | Debe | Haber |
|---|---|---|
| 5xxxx — Gasto (cuenta del detalle del usuario) | 1,130 | |
| 2140 — IVA Crédito Fiscal | | 1,130 |

---

### Factura con porción exenta (ej: combustible)

En Bolivia, el combustible tiene una parte del IVA no reembolsable. Se configura con
dos líneas de prorrateo:

```
tasas = 13      tasas_status = false

Prorrateo:
  Línea 1: Tipo=IVA    · Calculo=Grossing Up · Cuenta=2140 (IVA CF Reembolsable)   · Porcentaje=87% · %Exento=0%
  Línea 2: Tipo=EXENTO · Calculo=Grossing Up · Cuenta=2141 (IVA No Reembolsable)   · Porcentaje=13% · %Exento=100%
```

**Asiento generado** para una factura de Bs 1,000:

| Cuenta | Debe | Haber |
|---|---|---|
| 5xxxx — Gasto | 1,130 | |
| 2140 — IVA CF Reembolsable (87%) | | 982.10 |
| 2141 — IVA No Reembolsable (13%) | | 147.90 |

---

### Factura con ICE variable (ej: bebidas, tabaco)

El ICE varía por producto, por lo que el usuario lo ingresa en cada factura:

```
tasas = 13      tasas_status = false
ice   = 0       ice_status   = true   ← usuario ingresa el ICE por documento
exento_status   = true                ← usuario ingresa el exento por documento

Prorrateo:
  Línea 1: Tipo=IVA · Calculo=Grossing Up · Cuenta=2140 · Porcentaje=100% · %Exento=0%
  Línea 2: Tipo=ICE · Calculo=Grossing Up · Cuenta=2142 · Porcentaje=100% · %Exento=0%
```

---

### Factura con anticipos (campo adicional — Débito)

El usuario aplicó un anticipo previo al proveedor. Se descuenta del monto base:

```
Campo adicional:
  Nombre             = "Anticipo aplicado"
  Cuenta             = 1310 (Anticipos a Proveedores)
  type_calculation   = Débito   ← RESTA del monto base

Prorrateo: IVA 13% normal
```

**Efecto:** `amount_line = monto_factura - anticipo`

**Asiento generado** para factura Bs 1,000 con anticipo Bs 300:

| Cuenta | Debe | Haber |
|---|---|---|
| 5xxxx — Gasto | 791 | |
| 1310 — Anticipo Proveedores | 300 | |
| 2140 — IVA CF | | 791 |

> El anticipo aparece en el Debe porque es una recuperación del activo anticipado.

---

### Factura con retención IT (Impuesto a las Transacciones — 3%)

Se usa `Grossing Down` para generar el Debe de la retención:

```
tasas = 13      (IVA)

Prorrateo:
  Línea 1: Tipo=IVA · Calculo=Grossing Up   · Cuenta=2140 · Porcentaje=100% · %Exento=0%
  Línea 2: Tipo=IT  · Calculo=Grossing Down · Cuenta=2150 · Porcentaje=3%   · %Exento=0%
```

**Asiento generado** para factura Bs 1,000:

| Cuenta | Debe | Haber |
|---|---|---|
| 5xxxx — Gasto | 1,097 | |
| 2150 — IT Retenido | 33 | |
| 2140 — IVA CF | | 1,130 |

---

### Sin impuestos (viáticos, gastos varios)

```
tasas = 0       tasas_status = false
ice   = 0       ice_status   = false

Prorrateo:
  Línea 1: Tipo=EXENTO · Calculo=Grossing Up · Cuenta='-' · Porcentaje=100% · %Exento=0%

Campos opcionales: NIT, Razón Social desactivados (checkboxes en false)
```

**Asiento generado** para gasto de Bs 500:

| Cuenta | Debe | Haber |
|---|---|---|
| 5xxxx — Gasto | 500 | |
| — | | 500 |

> Con `%Exento = 0` y tasas = 0, el monto pasa sin transformación.

---

## 5. Opciones disponibles en los selectores

| Selector | Opciones |
|---|---|
| **Tipo** (prorrateo) | `IVA` · `IT` · `IUE` · `RC-IVA` · `EXENTO` · `TASA` · `ICE` |
| **Calculo** (prorrateo) | `Grossing Up` · `Grossing Down` |
| **type_calculation** (campos adicionales) | `Debito` · `Credito` |

---

## 6. Integración con SAP Service Layer

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
