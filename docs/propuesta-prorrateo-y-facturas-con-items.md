# Propuesta: prorrateo de facturas y facturas con ítems

Respuesta a las observaciones 10 y 11 de la revisión de Rendiciones. El prorrateo por
líneas (sección 1) está implementado; las facturas con ítems (sección 2) tienen el diseño
definido y están pendientes de implementar.

---

## 1. Prorrateo de una factura por líneas

**Caso:** una sola factura cuyo gasto se reparte entre varios valores de **cada**
dimensión. Por ejemplo, el Centro de Costo 1 se reparte 50 % / 50 % entre dos centros y
el Centro de Costo 2 se reparte 30 % / 70 %. Además, cada línea resultante puede ir a la
misma cuenta o a cuentas distintas.

**Hoy:** cada documento de la rendición lleva una sola combinación de dimensiones
(Centro de Costo 1..5 + Proyecto) y una sola cuenta (Concepto).

### Cómo se reparte: combinación de las 5 dimensiones

Cada dimensión tiene su propio reparto, y las líneas del asiento salen de **combinar**
los repartos de todas las dimensiones (producto cartesiano). El porcentaje de cada línea es
el producto de los porcentajes:

`líneas = (valores CC1) × (valores CC2) × (valores CC3) × (valores CC4) × (valores CC5)`

Ejemplo: factura de Bs 1.000 con IVA 13 %, gasto Bs 870.

| Dimensión | Reparto |
|---|---|
| Centro de Costo 1 | A 50 % · B 50 % |
| Centro de Costo 2 | X 30 % · Y 70 % |
| Centro de Costo 3..5 | un solo valor (o vacío) |

Resultado: 2 × 2 = **4 líneas de gasto**.

| Línea | CC1 | CC2 | % | Monto | Cuenta |
|---|---|---|---|---|---|
| 1 | A | X | 15 % | 130,50 | 6110 |
| 2 | A | Y | 35 % | 304,50 | 6110 |
| 3 | B | X | 15 % | 130,50 | 6120 |
| 4 | B | Y | 35 % | 304,50 | 6120 |
| | | | 100 % | **870,00** | |

Una dimensión sin reparto (un solo valor) no multiplica las líneas: va igual en todas.

### Quién lo carga

El reparto está en la sección **Reparto por líneas** del formulario del gasto, y se puede
cargar o corregir:

- **El usuario**, al registrar o editar el gasto, mientras la rendición está en borrador o
  rechazada.
- **El autorizador**, al revisar la rendición, y también después de autorizarla mientras
  **no se haya exportado a SAP** (por ejemplo, desde *Pendientes de exportación*).

Una vez exportada, los documentos ya no se pueden modificar: el asiento existe en SAP.

### Formulario del gasto

Cada Centro de Costo puede tener **uno o varios valores con su %** (botón "Repartir"). Cada
dimensión repartida debe sumar 100 %. Mientras una dimensión está repartida, su select de
*Datos Contables* queda deshabilitado. El Proyecto no se reparte.

Debajo se muestra la **tabla de líneas resultante** (la combinación), con el % y el monto
calculados. En cada línea el usuario puede:

- **Cambiar la cuenta (Concepto).** Por defecto lleva la cuenta del documento.
- **Escribir un comentario propio**, que será la glosa de la línea en SAP.

Si después se cambia el reparto de una dimensión, las líneas se vuelven a generar y se
conserva la cuenta elegida en las combinaciones que siguen existiendo.

Para no generar asientos enormes, se limita la cantidad de líneas (por ejemplo, 50 por
documento): 2 valores en las 5 dimensiones ya son 32 líneas.

### Base de datos

- `accountability_detail_splits`: el reparto por dimensión (`accountability_detail_id`,
  `dimension` 1..5, `code`, `percentage`).
- `accountability_detail_lines`: las líneas resultantes (`accountability_detail_id`,
  `combination`, `percentage`, `account`, `account_name`, `concept`,
  `distribution_rule_one..five`). El monto se calcula al exportar, sobre el gasto ya
  descontado el IVA.

Código: `App\Services\Accountability\ExpenseDistribution` (cálculo y guardado) y
`resources/js/components/accountability/ExpenseDistribution.vue` (formulario).

Un documento sin reparto sigue funcionando como hoy (una línea).

### Asiento en SAP

- **Gasto:** la línea de gasto (Debe) se reemplaza por las N líneas, cada una con su cuenta,
  sus dimensiones y su monto. El redondeo se ajusta en la última línea para que el asiento
  cuadre.
- **Impuestos:** IVA, retenciones y campos adicionales se calculan igual que hoy, sobre el
  total de la factura (`DocumentLineCalculator`).

### Relación con las normas de reparto de SAP

Elegir una norma de reparto en cada dimensión (disponible desde esta versión) produce en la
contabilidad de costos de SAP **el mismo reparto combinado**, pero en una sola línea de
asiento y con una sola cuenta. Sirve cuando el reparto es fijo y la cuenta es la misma. El
reparto por líneas hace falta cuando cambia de una factura a otra o cuando las cuentas son
distintas.

### Decisiones tomadas por defecto (a confirmar con Contabilidad)

- **IVA y retenciones** no se reparten: van en una sola línea, con el primer valor de cada
  dimensión repartida.
- **Libro de compras:** los UDF `U_LB_*` van en todas las líneas del documento, como ya
  ocurría con las líneas de gasto e IVA. Si el add-on cuenta la factura una vez por línea,
  hay que dejarlos solo en una.
- **Cuenta:** se elige por línea resultante. No se reparte por %.
- **Proyecto:** no se reparte.

---

## 2. Facturas con ítems (como las rendiciones de las sucursales)

**Caso:** facturas de compra de **artículos de inventario** (no servicios), que en SAP deben
quedar registradas por ítem: artículo, cantidad, precio y almacén.

**Hoy:** Rendiciones genera solo un **asiento preliminar** (JournalVoucher), que es contable:
no mueve inventario ni registra artículos.

### Diseño definido

Cada factura con ítems genera, además del asiento de la rendición, una **entrada de
mercancías de inventario en borrador** (SAP: *Inventario → Transacciones de stock →
Entrada de mercancías*, objeto `oInventoryGenEntry`, tabla OIGN). No lleva proveedor, así
que no hay factura de proveedores ni deuda que liquidar.

Contablemente:

| Documento | Debe | Haber |
|---|---|---|
| Asiento de la rendición (igual que hoy) | Cuenta del gasto (sin IVA) + IVA | Fondo del empleado / caja |
| Entrada de mercancías (borrador) | Inventario del almacén | **La misma cuenta del gasto** |

Al contabilizar ambos en SAP, la cuenta del gasto queda en cero y lo comprado queda como
inventario. El libro de compras sigue saliendo del asiento de la rendición (UDF `U_LB_*`).

### Decisiones

| Tema | Decisión |
|---|---|
| Documento en SAP | Entrada de mercancías de inventario, **en borrador** (se revisa junto con el asiento preliminar) |
| Proveedor | No aplica |
| Precio | **Sin IVA**. El total de la entrada debe ser **igual al gasto del documento** (lo que el asiento carga a la cuenta del gasto) |
| ICE y descuento | No se consideran por ahora |
| Prorrateo | **No se permite** en documentos con ítems (con cantidades se complica) |
| Lotes / series | No aplica: los artículos no se manejan por lote ni serie |
| Almacén | Uno **por línea**, con una opción general para llenar todas las líneas de una vez |
| Centros de costo | Las líneas llevan los mismos centros de costo (1..5) y proyecto que el documento |
| Cuenta de contrapartida | La cuenta del gasto (Concepto) del documento, en todas las líneas |

### Cambios en Rendiciones

1. **Perfil → Documentos:** nuevo indicador **"Con ítems"** (`items_status`) en el tipo de
   documento.
2. **Formulario del gasto:** si el tipo de documento lo tiene activo, aparece una tabla de ítems:
   - Artículo (buscador sobre `OITM`, solo artículos de inventario activos), cantidad, precio
     unitario sin IVA y almacén (`OWHS`, activos).
   - Selector de **almacén general** que llena todas las líneas.
   - Total de los ítems frente al gasto del documento, con la diferencia a la vista. No se
     puede guardar si no son iguales (tolerancia de redondeo de Bs 0,01).
   - **Precarga desde el QR de SIAT:** el formulario ya recibe el detalle de la factura
     (`listaDetalle`: descripción, cantidad, precio unitario). Se precargan cantidades y
     precios convertidos a sin IVA; el usuario solo asocia cada línea con un artículo de SAP.
   - La sección de reparto por líneas no se muestra para estos documentos.
3. **Base de datos:** tabla `accountability_detail_items` (`accountability_detail_id`,
   `item_code`, `item_name`, `quantity`, `price`, `warehouse_code`) y, en el documento, el
   número del borrador creado en SAP.
4. **Exportación a SAP:**
   - Se crean primero los borradores de entrada de mercancías (uno por factura con ítems) y
     después el asiento preliminar. Si el asiento falla, se eliminan los borradores ya
     creados para no dejar documentos sueltos.
   - Cada borrador lleva la referencia `REND-{id de la rendición}` para encontrarlo en SAP.
   - El mensaje de autorización y el detalle muestran el número del asiento y de cada borrador.
   - **Service Layer:** `POST Drafts` con `DocObjectCode = oInventoryGenEntry`.
   - **DI API:** hay que agregar al servicio .NET (proyecto aparte) un endpoint para crear
     borradores de entrada de mercancías. Solo es necesario si alguna empresa exporta en
     modo `DI`.

### Esfuerzo estimado

- Rendiciones (Laravel + Vue): **1 a 1,5 semanas**.
- Servicio DI API (.NET): **2 a 3 días adicionales**, solo si se usa el modo `DI`.
