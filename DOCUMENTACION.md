# Documentación Técnica - Sistema de Rendiciones

## 1. Visión General
El Sistema de Rendiciones es una aplicación web desarrollada para gestionar el flujo completo de rendiciones de gastos, viáticos y cajas chicas. Permite a los empleados registrar y detallar sus gastos operativos, adjuntar comprobantes, y enviar estas solicitudes para su revisión, posterior aprobación por responsables y eventual exportación a sistemas contables como SAP.

## 2. Pila Tecnológica (Tech Stack)

### Backend
- **Framework:** Laravel 10 (PHP)
- **Base de Datos:** MySQL / MariaDB
- **Autenticación:** Laravel Sanctum / Laravel Breeze (Gestión segura de sesiones y API Tokens).
- **Auditoría:** `owen-it/laravel-auditing` (Librería de trazabilidad para registro automático de cambios por los usuarios).
- **Generación de Reportes:** `barryvdh/laravel-dompdf` (Impresión estructurada en formato PDF de las rendiciones aprobadas para firma física o comprobante formal).

### Frontend
- **Framework Core:** Vue.js 3
- **Construcción y Bundling:** Vite + `@vitejs/plugin-vue`
- **UI Framework:** Quasar Framework v2 (`@quasar/vite-plugin`) para componentes altamente responsivos.
- **Routing & State:** Inertia.js (`@inertiajs/vue3`, `inertiajs/inertia-laravel`) para estructurar la aplicación como una Single Page Application (SPA). Aporta transiciones fluidas pero evita separar la lógica principal a una API independiente.
- **Herramientas Adicionales:** 
  - `vue-qrcode-reader`: Lector de códigos QR que agiliza y automatiza la captura del Servicio de Impuestos (SIN) proveniente de las facturas.
  - `ziggy-js`: Sincronización transparente de nombres de rutas entre Laravel y el entorno JavaScript (Vue).

## 3. Arquitectura del Sistema

La arquitectura general emplea el patrón Modelo-Vista-Controlador (MVC) centralizando el acceso a la base de datos y sirviendo componentes visuales dinámicos al navegador.

* **Estructura de Accesos y Roles:** La navegación se encuentra categorizada estrictamente base a los privilegios del usuario:
    * **Públicas y Autenticación:** Módulos de entrada.
    * **Modo Administrador:** ABM (Alta, Baja y Modificación) general de la organización, como listados de empleados, proveedores, parametrización de perfiles y reglas en los tipos de documentos aceptables.
    * **Modo Autorizador:** Consolas donde directores, coordinadores y gerentes validan o auditan las diferentes rendiciones pendientes (aprobar, rechazar u observar correcciones).
    * **Modo Solicitante:** Flujos amigables de auto-gestión para el usuario operativo donde puede adjuntar comprobantes y justificar el gasto.

* **Lógica Transaccional (Cabecera vs Detalle):**
    * Cada rendición se comporta como un bloque general (monto total, estado, cuenta de destino) que va almacenando diversos ítems, tickets y facturas (el área de detalle).
    * El sistema verifica de forma reactiva si el tipo de recibo añadido exige requerimientos tributarios especiales (como incluir el detalle del descuento, tasas de ICE, NIT o códigos de autorización).

* **Capacidad de Auditoría:**
    * Prácticamente todos los hitos modificables por los usuarios guardan un registro o "Audit Trail". Esto significa que el sistema puede responder automáticamente cómo fluyó una solicitud, en qué momento exacto fue modificada una cifra de origen, y por quién fue autorizada usando el motor de la librería de auditoría instalada.

## 4. Base de Datos (Estructura Principal)

Los datos están normalizados en la base de datos priorizando relaciones sólidas. Sus respectivas tablas principales son:

- **Organización y Acceso:** 
  - `users`: Cuentas y credenciales del personal.
  - `employees`: Detalles nominales y cargos de la planilla.
  - `areas` y `management`: Mapeo y agrupaciones de la gerencia local.
- **Entidades Contables:** 
  - `general_accounts` y `detail_accounts`: Clasificación jerárquica obligatoria adonde se envían los rubros en el catálogo de cuentas.
  - `suppliers`: Proveedores comunes identificados en las facturas y compras realizadas.
- **Tipologías y Patrones de Documentos:** 
  - `documents`, `document_details` y `document_fields`: Permiten establecer y configurar qué clase de facturas o boletas pueden cargarse orgánicamente.
  - `profiles` y `user_profiles`: Grupos de reglas que delimitan si un área rinde viáticos, suministros o activos extra.
- **Tablas Transaccionales (El core del sistema):** 
  - `accountabilities`: Almacena la cabecera misma o la "Solicitud Principal".
  - `accountability_details` y `accountability_fields`: Conforman el listado pormenorizado o "ítems/facturas" adjudicadas a dichas rendiciones.
- **Seguridad y Monitorización:** 
  - `audits`: Registro global (Bitácora inalterable) donde el sistema escribe los sucesos tras bambalinas.

## 5. Integraciones Externas

- **Servicio de Impuestos Nacionales (SIN - Caso Facturación)** 
  Posee una integración para facilitar el uso y la verificación de legalidad consultando la validez y montos al portal de impuestos de forma transparente mediante peticiones o procesando el código QR impreso (SIAT en línea).
- **Conectividad con SAP ERP** 
  En la capa final del embudo financiero de revisión, la arquitectura fue concebida para exportar los legajos totalmente aprobados (marcando una bandera definitiva) enviándolos así en formato compatible al entorno global corporativo (SAP / módulos de Finanzas).

## 6. Flujo de Trabajo de Negocio 

1. **Creación (Borrador):** El usuario *Solicitante* crea un expediente asociándolo a su cuenta. Este periodo permite su edición y eliminación mientras es completado.
2. **Carga Continua:** Suma y carga recibos en serie, completando montos y tomando foto a través del dispositivo o escáner al código.
3. **Cierre de Carga y Envío:** Realiza la sumisión del reporte final. La solicitud se vuelve inmutable previniendo adulteraciones posteriores.
4. **Revisión Continua:** El responsable de área (*Autorizador*) verifica en detalle el sustento de la documentación adjunta, retroalimenta solicitando modificaciones (haciendo que el reporte retroceda en etapas), o sella con una orden de aprobación firme validando que lo presentado es lícito.
5. **Impacto Financiero:** Contabilidad concilia lo revisado y realiza el pase (o re-exporte) al sistema de operaciones globales (SAP).
