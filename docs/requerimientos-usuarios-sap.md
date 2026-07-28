# Requerimientos SAP Business One

Documento para el equipo/administrador de SAP B1: campos personalizados a
crear y credenciales de acceso a proveer para la integración con el Sistema
de Rendiciones.

---

## 1. Campos a crear en SAP (UDF)

### 1.1 En Socios de Negocio (OCRD)

| Nombre de campo | Tipo | Descripción |
|---|---|---|
| `U_User_Rend` | Alfanumérico (100) | Marca si el socio de negocio es un empleado habilitado para registrar rendiciones (valor `1` = habilitado) |

### 1.2 En el detalle del asiento contable (JDT1)

| Nombre de campo | Tipo | Descripción |
|---|---|---|
| `U_FechaDeFactura` | Fecha | Fecha de la factura |
| `U_NumeroDeFactura` | Alfanumérico (100) | Número de factura/documento |
| `U_Autorizacion` | Alfanumérico (100) | Número de autorización |
| `U_CUF` | Alfanumérico (100) | Código Único de Factura (SIAT) |
| `U_CodigoDeControl` | Alfanumérico (100) | Código de control de la factura |
| `U_RazonSocial` | Alfanumérico (100) | Razón social del proveedor |
| `U_NIT` | Alfanumérico (100) | NIT del proveedor |
| `U_Importe` | Numérico | Monto de la factura |
| `U_Descuento` | Numérico | Descuento |
| `U_Exento` | Numérico | Monto exento |
| `U_Tasas` | Numérico | Monto de tasas (IVA u otro) |
| `U_GiftCard` | Numérico | Monto Gift Card |
| `U_TasaCero` | Numérico | Monto con tasa cero |
| `U_ICE` | Numérico | Monto ICE |
| `U_TipoDeDocumento` | Alfanumérico (100) | Tipo de documento (factura, recibo, etc.) |

### 1.3 Pendiente de definir (opcional)

| Nombre de campo | Tipo | Descripción |
|---|---|---|
| Campo de usuario en cabecera del asiento (nombre a definir) | Alfanumérico | Identifica qué usuario del sistema generó el asiento contable. Aún no está definido/creado — si se requiere trazabilidad por usuario, hay que asignar un nombre de campo. |

---

## 2. Credenciales y datos de conexión que SAP debe proveer

### 2.1 Conexión a la base de datos de SAP B1

- Tipo de base de datos: **SQL Server** o **HANA** (indicar cuál usa la instalación)
- Host / servidor
- Puerto
- Nombre de la base de datos de la compañía
- Usuario y contraseña de conexión

### 2.2 Conexión al Service Layer (para registrar los asientos contables)

- URL del Service Layer (ej. `https://servidor:50000`)
- Nombre de la base de compañía (CompanyDB)
- Usuario y contraseña de acceso al Service Layer

### 2.3 Credenciales de correo (para notificaciones del sistema)

Necesarias para que el sistema envíe avisos por correo (nueva rendición
pendiente de aprobación, cambios de estado — aprobado/rechazado, etc.). Puede
ser cualquier cuenta/servidor SMTP (Gmail, Office 365, servidor propio, etc.):

- Servidor SMTP (host)
- Puerto
- Tipo de cifrado (TLS o SSL)
- Usuario / cuenta de correo
- Contraseña (o contraseña de aplicación, según el proveedor)
- Correo remitente y nombre a mostrar como remitente

---

## 3. Datos por usuario (a completar por el cliente en el sistema, no en SAP)

Por cada usuario del sistema de rendiciones que sea empleado:

- **Código de socio de negocio (CardCode)** correspondiente en SAP.
- **Códigos de dimensión de distribución** (centro de costo / proyecto en SAP),
  si la empresa distribuye gastos por dimensiones — hasta 5 códigos por usuario.
