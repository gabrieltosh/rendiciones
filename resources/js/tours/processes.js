// Tutoriales de procesos del manual interactivo, por componente de página Inertia.
//
// Además del "Recorrido de la pantalla" (./definitions.js), cada página puede ofrecer
// tutoriales paso a paso de un proceso concreto. Aparecen en el menú del botón
// "Ayuda" de la cabecera y nunca se inician solos.
//
// Formato: { id, title, description, icon?, when?(ctx), steps: [...] } con los mismos
// pasos que definitions.js (target = data-tour, optional, example, exampleAlways…).
// Los datos de ejemplo son ficticios.

// ── Procesos del formulario de gasto (solicitante y autorizador) ──────────────

const scanInvoice = {
    id: "scan-invoice",
    title: "Registrar una factura escaneando el QR",
    description: "El sistema llena los datos de la factura y la valida en el SIAT.",
    icon: "qr_code_scanner",
    steps: [
        {
            target: "scan-invoice-cta",
            title: "1. Escanee la factura",
            text: "Pulse <strong>Escanear factura</strong>. En el celular se abre la cámara; en la computadora puede pegar el enlace del QR.",
        },
        {
            target: "scan-qr",
            optional: true,
            title: "También desde los datos del comprobante",
            text: "Si ya eligió el tipo de documento, este botón abre el mismo escáner.",
        },
        {
            title: "2. Revise la validación del SIAT",
            text: "Antes de aplicar los datos verá el estado de la factura en Impuestos Nacionales. Una factura <strong>anulada</strong> no se puede registrar; si la fecha queda fuera del periodo o la factura ya fue cargada, verá un aviso.",
            example: {
                title: "Factura N° 88731 · BoA (ficticio)",
                rows: [
                    ["Estado en el SIAT", "VÁLIDA", "positive"],
                    ["Fecha", "19/11/2026 · dentro del periodo", "positive"],
                    ["Importe", "Bs 912,00"],
                ],
            },
        },
        {
            target: "receipt-data",
            title: "3. Datos completados",
            text: "N° de factura, CUF, NIT, razón social, fecha e importe se completan solos. Revíselos.",
        },
        {
            target: "duplicate-warning",
            optional: true,
            title: "Factura ya registrada",
            text: "Este aviso indica en qué rendición ya está cargada la misma factura.",
        },
        {
            target: "field-expense-account",
            title: "4. Elija el tipo de gasto",
            text: "Es la cuenta contable del gasto: pasajes, hospedaje, alimentación…",
        },
        {
            target: "field-concept",
            title: "5. Escriba el concepto",
            text: "Una glosa corta que explique el gasto, por ejemplo «Pasaje a feria de proveedores».",
        },
        {
            target: "save-expense",
            title: "6. Guarde",
            text: "El gasto se suma a la rendición y el saldo se actualiza.",
        },
    ],
};

const manualReceipt = {
    id: "manual-receipt",
    title: "Registrar un recibo o una factura sin QR",
    description: "Carga manual de un comprobante.",
    icon: "edit_note",
    steps: [
        {
            target: "field-document-type",
            title: "1. Tipo de documento",
            text: "Elija <strong>Recibo</strong>, <strong>Factura</strong> u otro tipo. Los campos del comprobante cambian según lo que elija.",
        },
        {
            target: "field-expense-date",
            title: "2. Fecha del comprobante",
            text: "Debe estar dentro del periodo de la rendición.",
        },
        {
            target: "receipt-data",
            title: "3. Datos del comprobante",
            text: "Complete número, NIT y razón social. Al escribir el NIT o la razón social se buscan proveedores ya registrados.",
            example: {
                title: "Recibo de ejemplo",
                rows: [
                    ["N° de recibo", "000245"],
                    ["Razón social", "Restaurante El Aljibe (ficticio)"],
                    ["Importe", "Bs 155,50"],
                ],
            },
        },
        {
            target: "field-amount",
            title: "4. Importe",
            text: "El total del comprobante. Los impuestos y retenciones se calculan solos según el tipo de documento.",
        },
        { target: "field-expense-account", title: "5. Tipo de gasto", text: "La cuenta contable del gasto." },
        { target: "field-concept", title: "6. Concepto", text: "Explique brevemente el gasto." },
        { target: "save-expense", title: "7. Guarde", text: "El gasto queda registrado en la rendición." },
    ],
};

const splitExpense = {
    id: "split-expense",
    title: "Repartir un gasto entre centros de costo",
    description: "Dividir una factura entre áreas o proyectos.",
    icon: "call_split",
    steps: [
        {
            target: "cost-centers",
            optional: true,
            title: "Centros de costo de un solo destino",
            text: "Si todo el gasto va a un solo centro de costo, basta con elegirlo aquí.",
        },
        {
            target: "distribution-toggle",
            title: "1. Active el reparto por líneas",
            text: "Al activarlo, los centros de costo se definen solo en el reparto y los de arriba se bloquean.",
        },
        {
            target: "expense-distribution",
            title: "2. Asigne los porcentajes",
            text: "Para cada centro de costo pulse <strong>Asignar</strong> y, si se divide, <strong>Agregar</strong> otra fila. Cada centro de costo debe sumar 100 %.",
            example: {
                title: "Factura de Bs 1.000,00",
                rows: [
                    ["CC1 Ventas · 60 %", "Bs 600,00"],
                    ["CC1 Logística · 40 %", "Bs 400,00"],
                    ["Suma", "100 %", "positive"],
                ],
            },
        },
        {
            title: "3. Revise las líneas resultantes",
            text: "Debajo verá una línea por cada combinación de centros de costo, con su porcentaje. Puede darle a cada una su propio tipo de gasto y glosa.",
        },
        { target: "save-expense", title: "4. Guarde", text: "Al exportar a SAP, el gasto sale en una línea por cada combinación." },
    ],
};

const inventoryItems = {
    id: "inventory-items",
    title: "Factura con artículos de inventario",
    description: "Registrar ítems que entran al almacén.",
    icon: "inventory_2",
    steps: [
        {
            target: "field-document-type",
            title: "1. Tipo de documento con ítems",
            text: "Elija el tipo de documento configurado para facturas con artículos. Al elegirlo aparece la sección de ítems.",
        },
        {
            target: "expense-items",
            title: "2. Cargue los artículos",
            text: "Cada artículo lleva código, cantidad, precio unitario sin IVA y almacén. La suma debe coincidir con el importe de la factura.",
            example: {
                title: "Artículos de ejemplo",
                rows: [
                    ["Papel bond carta × 10", "Bs 348,00"],
                    ["Tóner HP 85A × 1", "Bs 435,00"],
                    ["Almacén", "ALM-LPZ"],
                ],
            },
        },
        {
            target: "items-from-siat",
            optional: true,
            title: "Desde la factura SIAT",
            text: "Si escaneó la factura, puede traer sus ítems y solo completar el código y el almacén.",
        },
        {
            target: "items-warehouse",
            optional: true,
            title: "Almacén",
            text: "El almacén donde ingresa la mercadería.",
        },
        {
            title: "3. Qué pasa al autorizar",
            text: "Además del asiento, en SAP se crea una <strong>entrada de mercancías en borrador</strong> por esta factura, para que almacén la revise y la contabilice.",
        },
    ],
};

const saveAndNew = {
    id: "several-invoices",
    title: "Cargar varias facturas seguidas",
    description: "Registrar muchos gastos sin volver al detalle.",
    icon: "library_add",
    steps: [
        { target: "scan-invoice-cta", title: "1. Escanee la primera factura", text: "Complete el gasto como siempre." },
        {
            target: "save-and-new",
            title: "2. Guardar y agregar otro",
            text: "Guarda el gasto y deja el formulario vacío para el siguiente. Repita con cada factura.",
        },
        {
            target: "save-expense",
            title: "3. Con la última, Guardar",
            text: "Vuelve al detalle de la rendición, donde verá todos los gastos y el saldo.",
        },
    ],
};

// ── Tutoriales por pantalla ─────────────────────────────────────────────────

export const processes = {
    welcome: [
        {
            id: "full-process",
            title: "El proceso de una rendición, de principio a fin",
            description: "Desde que recibe el dinero hasta que llega a SAP.",
            icon: "route",
            steps: [
                {
                    title: "1. Recibe el dinero",
                    text: "Un anticipo, viático o caja chica. Por cada uno crea una rendición con el monto recibido y el periodo.",
                },
                { target: "new-accountability", title: "2. Cree la rendición", text: "Empiece aquí, eligiendo el perfil." },
                {
                    title: "3. Cargue sus gastos",
                    text: "Escanee cada factura con el celular o cargue los recibos a mano. El saldo se actualiza solo.",
                    example: {
                        title: "Rendición #10024 · Viaje a Santa Cruz",
                        rows: [
                            ["Recibido", "Bs 3.000,00"],
                            ["Gastado", "Bs 2.745,50"],
                            ["Saldo a devolver", "Bs 254,50", "positive"],
                        ],
                    },
                },
                {
                    title: "4. Envíela a autorización",
                    text: "Pasa por los niveles de su ciclo (por ejemplo Jefatura y Contabilidad). Si la observan, vuelve a usted con el motivo para corregir y reenviar.",
                },
                {
                    title: "5. Autorizada y en SAP",
                    text: "Con la última aprobación se crea el asiento preliminar en SAP y la rendición queda cerrada.",
                },
                { target: "help-button", title: "¿Dudas en una pantalla?", text: "Abra <strong>Ayuda</strong> para ver sus tutoriales." },
            ],
        },
        {
            id: "authorizer-start",
            title: "Cómo revisar lo que me toca autorizar",
            description: "Dónde ver sus pendientes y las que no llegaron a SAP.",
            icon: "fact_check",
            when: (ctx) => ctx.isAuthorizer,
            steps: [
                {
                    target: "review-cards",
                    title: "Sus pendientes",
                    text: "Rendiciones esperando su aprobación y autorizadas que aún no están en SAP. Haga clic para ir a cada bandeja.",
                },
                {
                    target: "main-menu",
                    title: "Desde el menú",
                    text: "También llega por <strong>Revisión → Autorizaciones</strong> y <strong>Pendientes SAP</strong>; los números indican cuántas hay.",
                },
            ],
        },
    ],

    "accountability/IndexAccountabilityNew": [
        {
            id: "create-accountability",
            title: "Crear una rendición",
            description: "Registrar el dinero recibido y empezar a cargar gastos.",
            icon: "add_circle",
            steps: [
                { target: "new-accountability", title: "1. Nueva rendición", text: "Cree una por cada dinero recibido." },
                {
                    title: "2. Datos generales",
                    text: "En el diálogo que se abre, indique el empleado (si el perfil lo pide), la cuenta, el monto recibido, una descripción y el periodo.",
                    example: {
                        title: "Ejemplo",
                        rows: [
                            ["Descripción", "Viaje a Santa Cruz – feria"],
                            ["Monto recibido", "Bs 3.000,00"],
                            ["Periodo", "15/11/2026 – 30/11/2026"],
                        ],
                    },
                },
                {
                    title: "3. Cargue los gastos",
                    text: "Al guardar pasa directo al detalle de la rendición para agregar sus facturas y recibos.",
                },
            ],
        },
        {
            id: "fix-rejected",
            title: "Encontrar y corregir una rendición rechazada",
            description: "Ver el motivo y reenviarla.",
            icon: "report",
            steps: [
                { target: "status-filters", title: "1. Filtre por Rechazado", text: "Muestra solo las rendiciones que debe corregir." },
                {
                    target: "accountability-list",
                    title: "2. Ábrala",
                    text: "En el detalle verá las observaciones del autorizador y el botón <strong>Corregir y reenviar</strong>.",
                },
            ],
        },
    ],

    "accountability/DetailAccountabilityNew": [
        {
            id: "add-expenses",
            title: "Cargar los gastos",
            description: "Agregar facturas y recibos y controlar el saldo.",
            icon: "receipt_long",
            steps: [
                {
                    target: "add-expense",
                    title: "1. Agregar gasto",
                    text: "Abre el formulario del gasto. En el celular, empiece por <strong>Escanear factura</strong>.",
                },
                {
                    target: "documents-list",
                    title: "2. Sus gastos",
                    text: "Cada gasto cargado aparece aquí. Puede editarlo o eliminarlo mientras la rendición esté en borrador o rechazada.",
                },
                {
                    target: "balance-summary",
                    title: "3. Controle el saldo",
                    text: "Recibido menos gastado: si sobra, lo devuelve; si falta, se le reembolsa.",
                    example: {
                        rows: [
                            ["Recibido", "Bs 3.000,00"],
                            ["Gastado", "Bs 2.745,50"],
                            ["Saldo a devolver", "Bs 254,50", "positive"],
                        ],
                    },
                    exampleAlways: true,
                },
            ],
        },
        {
            id: "edit-data",
            title: "Corregir los datos de la rendición",
            description: "Monto recibido, cuenta, empleado o periodo, sin salir de la pantalla.",
            icon: "edit",
            steps: [
                {
                    target: "edit-accountability",
                    title: "Editar datos",
                    text: "Abre un diálogo con los datos de la rendición. Al guardar sigue en esta misma pantalla y el saldo se recalcula.",
                },
                {
                    target: "balance-summary",
                    title: "Saldo actualizado",
                    text: "Si cambió el monto recibido, verá aquí el nuevo saldo.",
                },
            ],
        },
        {
            id: "duplicate",
            title: "Duplicar un gasto parecido",
            description: "Reutilizar proveedor, cuenta y concepto de un gasto anterior.",
            icon: "content_copy",
            steps: [
                {
                    target: "duplicate-expense",
                    title: "Duplicar gasto",
                    text: "Abre un gasto nuevo con el mismo tipo de documento, cuenta, proveedor, concepto y centros de costo. Solo complete número, fecha e importe.",
                    example: {
                        title: "Se copia / se completa",
                        rows: [
                            ["Proveedor y NIT", "Se copia"],
                            ["Cuenta y concepto", "Se copia"],
                            ["N° de factura e importe", "Lo completa usted", "warning"],
                        ],
                    },
                },
            ],
        },
        {
            id: "send",
            title: "Enviar a autorización",
            description: "Qué se necesita y qué pasa después.",
            icon: "send",
            steps: [
                {
                    target: "no-authorizer",
                    optional: true,
                    title: "Necesita un autorizador",
                    text: "Sin ciclo de autorización no se puede enviar. Pídale al administrador que se lo asigne.",
                },
                {
                    target: "balance-summary",
                    title: "1. Revise el total",
                    text: "Confirme que cargó todos los gastos: la rendición necesita al menos un documento.",
                },
                {
                    target: "send-accountability",
                    title: "2. Enviar",
                    text: "El diálogo resume los documentos, lo gastado y el saldo. Al confirmar queda <strong>Pendiente</strong> y ya no se puede modificar.",
                },
                {
                    title: "3. Después del envío",
                    text: "Recibirá un correo si se aprueba o si la rechazan. Puede seguir el estado desde esta pantalla.",
                },
            ],
        },
        {
            id: "fix-and-resend",
            title: "Corregir y reenviar una rendición rechazada",
            description: "Atender las observaciones del autorizador.",
            icon: "restart_alt",
            steps: [
                {
                    target: "rejection-notes",
                    title: "1. Lea las observaciones",
                    text: "El motivo, quién la rechazó y cuándo.",
                    example: {
                        title: "Observaciones del autorizador",
                        rows: [
                            ["Motivo", "Factura de hotel a nombre de otra empresa"],
                            ["Por", "María Quispe · 24/11/2026"],
                        ],
                    },
                },
                {
                    target: "documents-list",
                    title: "2. Corrija los gastos",
                    text: "Edite, elimine o agregue los documentos que le observaron.",
                },
                {
                    target: "send-accountability",
                    title: "3. Reenviar",
                    text: "La rendición vuelve a empezar el ciclo de autorización. El historial del autorizador separa cada envío.",
                },
            ],
        },
    ],

    "accountability/Detail/CreateDetailNew": [scanInvoice, manualReceipt, splitExpense, inventoryItems, saveAndNew],
    "accountability/Detail/EditDetailNew": [scanInvoice, manualReceipt, splitExpense, inventoryItems],
    "authorization/Detail/CreateDetailNew": [scanInvoice, manualReceipt, splitExpense, inventoryItems],
    "authorization/Detail/EditDetail": [manualReceipt, splitExpense, inventoryItems],

    "authorization/IndexAccountabilityNew": [
        {
            id: "bulk-approve",
            title: "Aprobar varios niveles intermedios a la vez",
            description: "Para revisiones de jefatura que no exportan a SAP.",
            icon: "done_all",
            steps: [
                {
                    target: "bulk-approve",
                    title: "1. Seleccione",
                    text: "Solo las rendiciones en un nivel intermedio tienen casilla: la autorización final siempre se hace una por una, revisando la vista contable.",
                },
                {
                    title: "2. Aprobar nivel",
                    text: "Puede dejar un comentario común. Cada rendición pasa a su siguiente nivel; si alguna no se pudo aprobar, el aviso dice cuál y por qué.",
                },
            ],
        },
        {
            id: "review-pending",
            title: "Revisar mis pendientes",
            description: "Priorizar por antigüedad y abrir cada rendición.",
            icon: "inbox",
            steps: [
                { target: "inbox-tabs", title: "1. Pestaña Pendientes", text: "Las que esperan su aprobación en su nivel." },
                {
                    target: "aging-badge",
                    optional: true,
                    title: "2. Empiece por las más antiguas",
                    text: "Rojo: más de 5 días esperando. Amarillo: más de 2.",
                },
                {
                    target: "pending-list",
                    title: "3. Abra la rendición",
                    text: "Revise documentos y vista contable, y decida: aprobar, rechazar o anular.",
                    example: {
                        title: "#10031 · Juan Pérez",
                        rows: [
                            ["Nivel", "1 de 2 · Jefatura"],
                            ["Enviada", "Hace 7 días", "negative"],
                            ["Gastado", "Bs 4.120,00"],
                        ],
                    },
                },
            ],
        },
        {
            id: "find-authorized",
            title: "Buscar una rendición ya autorizada",
            description: "Historial con filtros por perfil y estado en SAP.",
            icon: "manage_search",
            steps: [
                { target: "inbox-tabs", title: "1. Pestaña Autorizadas", text: "Cámbiese a la pestaña de autorizadas." },
                {
                    target: "authorized-filters",
                    title: "2. Filtre",
                    text: "Busque por texto y filtre por perfil o por si ya está en SAP.",
                },
                { target: "authorized-list", title: "3. Resultados", text: "La lista se carga por páginas; ábrala para ver el detalle o imprimirla." },
            ],
        },
        {
            id: "see-all",
            title: "Ver las rendiciones de todos los niveles",
            description: "Solo administradores.",
            icon: "visibility",
            when: (ctx) => ctx.isAdmin,
            steps: [
                {
                    target: "show-all",
                    title: "Ver todas",
                    text: "Muestra las pendientes de cualquier nivel, no solo las suyas. Como administrador puede actuar sobre cualquiera.",
                },
            ],
        },
    ],

    "authorization/DetailAccountabilityNew": [
        {
            id: "fix-and-export",
            title: "Corregir y exportar una rendición pendiente de SAP",
            description: "Cuando la exportación falló: leer el error, corregir y reintentar aquí.",
            icon: "cloud_sync",
            when: (ctx) => ctx.props?.accountability?.status === "Autorizado" && !ctx.props?.accountability?.sap_exported,
            steps: [
                {
                    target: "sap-panel",
                    title: "1. Lea el error de SAP",
                    text: "El panel muestra el último error y cuántos intentos hubo. En <strong>Historial</strong> están todos.",
                },
                {
                    target: "edit-header-detail",
                    title: "2. Corrija el encabezado",
                    text: "Empleado, cuenta, monto recibido o periodo, en un diálogo, sin salir de la rendición.",
                },
                {
                    target: "documents-list",
                    title: "3. O corrija un documento",
                    text: "Con el lápiz de cada gasto corrige la cuenta, los centros de costo o los importes.",
                },
                {
                    target: "accounting-tab",
                    title: "4. Revise el asiento",
                    text: "La vista contable muestra exactamente lo que se enviará a SAP.",
                },
                {
                    target: "export-from-detail",
                    title: "5. Exporte",
                    text: "Si SAP acepta, la rendición queda cerrada con su número de asiento; si no, verá el nuevo error aquí mismo.",
                },
            ],
        },
        {
            id: "authorize",
            title: "Revisar y autorizar una rendición",
            description: "Qué revisar antes de aprobar y exportar a SAP.",
            icon: "verified",
            steps: [
                { target: "general-info", title: "1. Datos generales", text: "Recibido, gastado, saldo, empleado y periodo." },
                {
                    target: "documents-list",
                    title: "2. Documentos",
                    text: "Revise cada gasto. En las facturas con CUF, el botón de recibo muestra la factura original del SIAT para comparar.",
                },
                {
                    target: "accounting-tab",
                    title: "3. Vista contable",
                    text: "El asiento exacto que irá a SAP: cuentas, centros de costo, debe y haber. Debe decir <strong>Cuadrado</strong>.",
                    example: {
                        rows: [
                            ["6210301 Pasajes (Debe)", "Bs 1.740,00"],
                            ["1130601 Crédito fiscal (Debe)", "Bs 226,20"],
                            ["1120501 Fondos a rendir (Haber)", "Bs 1.966,20"],
                            ["Estado", "Cuadrado", "positive"],
                        ],
                    },
                    exampleAlways: true,
                },
                {
                    target: "authorize-button",
                    title: "4. Aprobar o autorizar",
                    text: "En un nivel intermedio pasa al siguiente y usted continúa directo con su <strong>siguiente rendición pendiente</strong>. En el último nivel crea el asiento en SAP. Puede dejar un comentario.",
                },
                {
                    title: "Si SAP falla",
                    text: "Verá el error de SAP y podrá <strong>autorizar sin SAP</strong>: la rendición queda en <strong>Pendientes SAP</strong> para reintentar después.",
                },
            ],
        },
        {
            id: "reject",
            title: "Rechazar para que corrijan",
            description: "Devolver la rendición al solicitante con un motivo.",
            icon: "undo",
            steps: [
                {
                    target: "reject-button",
                    title: "1. Rechazar",
                    text: "Escriba un motivo claro: qué documento y qué corregir. El solicitante lo verá en su pantalla y por correo.",
                },
                {
                    target: "authorization-cycle",
                    title: "2. Qué pasa después",
                    text: "Cuando la reenvíe, el ciclo empieza de nuevo; este historial guarda los envíos anteriores.",
                },
            ],
        },
        {
            id: "annul",
            title: "Anular una rendición",
            description: "Cerrarla definitivamente (no se puede reenviar).",
            icon: "block",
            steps: [
                {
                    title: "¿Rechazar o anular?",
                    text: "<strong>Rechazar</strong> permite corregir y reenviar. <strong>Anular</strong> la cierra para siempre: úselo cuando la rendición no debe seguir (por ejemplo, se cargó por error).",
                },
                {
                    target: "annul-button",
                    title: "Anular",
                    text: "Pide un motivo. No está disponible si la rendición ya está en SAP.",
                },
            ],
        },
        {
            id: "fix-documents",
            title: "Corregir un documento antes de autorizar",
            description: "Ajustar un gasto o la cabecera sin devolver la rendición.",
            icon: "build",
            steps: [
                {
                    target: "document-card",
                    optional: true,
                    title: "1. Editar un gasto",
                    text: "Use el lápiz del documento para corregir cuenta, centros de costo o importes.",
                },
                { target: "add-document", optional: true, title: "2. Agregar un gasto", text: "Si falta un comprobante, puede agregarlo usted." },
                {
                    target: "edit-header-detail",
                    optional: true,
                    title: "3. Corregir el encabezado",
                    text: "Empleado, cuenta, monto recibido o periodo, en un diálogo sin salir de la rendición.",
                },
                {
                    target: "history",
                    optional: true,
                    title: "Todo queda registrado",
                    text: "Los cambios se ven en el historial de la rendición.",
                },
            ],
        },
    ],

    "authorization/PendingExport": [
        {
            id: "retry",
            title: "Reintentar una exportación fallida",
            description: "Leer el error de SAP y volver a enviar.",
            icon: "cloud_sync",
            steps: [
                {
                    target: "last-attempt",
                    title: "1. Lea el error",
                    text: "El último intento muestra el mensaje de SAP: por ejemplo una cuenta inactiva o un empleado faltante.",
                    example: {
                        rows: [
                            ["Último intento", "Error", "negative"],
                            ["Mensaje", "Cannot perform transaction in controlling type account"],
                        ],
                    },
                },
                {
                    target: "export-logs",
                    title: "2. Historial de intentos",
                    text: "Todos los intentos con fecha, usuario, modo y respuesta de SAP.",
                },
                { target: "export-one", title: "3. Reintente", text: "Cuando el problema esté resuelto, exporte de nuevo." },
            ],
        },
        {
            id: "fix-and-export",
            title: "Corregir y exportar desde la rendición",
            description: "Todo en una pantalla: encabezado, documentos y exportación.",
            icon: "build",
            steps: [
                {
                    target: "open-detail",
                    title: "1. Revisar y corregir",
                    text: "Abre la rendición. Ahí verá el último error de SAP y podrá corregir el encabezado o los documentos y exportar, sin volver a esta lista.",
                },
                {
                    title: "2. En la rendición",
                    text: "Use <strong>Editar encabezado</strong> para el empleado o la cuenta, el lápiz de cada documento para corregir un gasto, y <strong>Exportar a SAP</strong> cuando esté listo. Si SAP vuelve a fallar, el nuevo error aparece en la misma pantalla.",
                    example: {
                        title: "Panel de exportación",
                        rows: [
                            ["Último error de SAP", "Cannot perform transaction in controlling type account", "negative"],
                            ["Acciones", "Editar encabezado · Exportar a SAP"],
                        ],
                    },
                },
            ],
        },
        {
            id: "fix-header",
            title: "Corregir solo el empleado o la cuenta (rápido)",
            description: "Cuando el error es por un dato de la cabecera.",
            icon: "manage_accounts",
            steps: [
                { target: "edit-header", title: "1. Editar encabezado", text: "Abre los datos de cabecera en un diálogo, sin salir de la lista." },
                {
                    title: "2. Corrija el dato",
                    text: "Busque el empleado en SAP escribiendo al menos 2 letras, o cambie la cuenta. Guarde.",
                    example: {
                        rows: [
                            ["Empleado", "E00125 – Juan Pérez (ficticio)"],
                            ["Cuenta", "1120501 Fondos a rendir"],
                        ],
                    },
                },
                { target: "export-one", title: "3. Exporte", text: "Vuelva a enviar la rendición a SAP." },
            ],
        },
        {
            id: "batch",
            title: "Reexportar varias a la vez",
            description: "Reintentar en lote después de resolver un problema general.",
            icon: "playlist_add_check",
            steps: [
                { target: "select-row", title: "1. Marque las rendiciones", text: "Use la casilla de cada fila." },
                {
                    target: "batch-export",
                    optional: true,
                    title: "2. Reexportar seleccionadas",
                    text: "Se envían una por una.",
                },
                {
                    target: "batch-progress",
                    optional: true,
                    title: "3. Resultado",
                    text: "Verá el avance y el resultado de cada rendición; las que fallen quedan en la lista con su error.",
                },
            ],
        },
    ],

    // ── Administración ──────────────────────────────────────────────────────
    "administration/areas/IndexArea": [
        {
            id: "area-crud",
            title: "Crear o editar un área sin salir de la lista",
            description: "Todo en un diálogo.",
            icon: "edit_square",
            steps: [
                { target: "create-area", title: "1. Crear área", text: "Abre un diálogo en esta misma pantalla." },
                { target: "area-form", title: "2. Datos", text: "Nombre y, si quiere, una descripción." },
                { target: "edit-area", optional: true, title: "3. Editar", text: "El lápiz abre el mismo diálogo; al guardar, la fila queda resaltada." },
            ],
        },
    ],
    "administration/suppliers/IndexSupplier": [
        {
            id: "supplier-crud",
            title: "Registrar un proveedor desde la búsqueda",
            description: "Si no lo encuentra, regístrelo ahí mismo.",
            icon: "store",
            steps: [
                { target: "suppliers-table", title: "1. Busque", text: "Por razón social o NIT." },
                {
                    title: "2. Si no existe",
                    text: "La lista le ofrece registrar lo que buscó: si escribió un número, se usa como NIT.",
                },
                { target: "create-supplier", title: "O créelo directamente", text: "Abre el diálogo de nuevo proveedor." },
            ],
        },
    ],
    "administration/users/IndexUser": [
        {
            id: "user-access",
            title: "Asignar perfiles y ciclo desde la lista",
            description: "Sin abrir la ficha completa del usuario.",
            icon: "manage_accounts",
            steps: [
                {
                    target: "user-access",
                    title: "Perfiles y ciclo",
                    text: "Los chips de cada fila abren un diálogo para marcar los perfiles del usuario y elegir (o crear) su ciclo de autorización.",
                    example: {
                        rows: [
                            ["Perfiles", "2 asignados"],
                            ["Ciclo", "Jefatura → Contabilidad"],
                        ],
                    },
                },
            ],
        },
        {
            id: "new-user",
            title: "Dar de alta un usuario",
            description: "Qué configurar para que pueda rendir.",
            icon: "person_add",
            steps: [
                { target: "create-user", title: "1. Crear usuario", text: "Datos de acceso, rol y área." },
                {
                    title: "2. Lo que necesita para rendir",
                    text: "Al menos un <strong>perfil</strong>, un <strong>ciclo de autorización</strong> y su <strong>socio de negocio</strong> en SAP (empleado).",
                    example: {
                        title: "Usuario de ejemplo",
                        rows: [
                            ["Rol", "Usuario"],
                            ["Perfiles", "Viáticos nacionales, Caja chica"],
                            ["Ciclo", "Jefatura → Contabilidad"],
                            ["Socio de negocio", "E00125 – Juan Pérez"],
                        ],
                    },
                },
            ],
        },
    ],
    "administration/users/CreateUser": [
        {
            id: "configure-user",
            title: "Configurar un usuario completo",
            description: "Rol, socio de negocio, perfiles, autorización y centros de costo.",
            icon: "badge",
            steps: [
                { target: "field-role", title: "1. Rol", text: "Usuario rinde; Autorizador además aprueba; Administrador configura todo." },
                {
                    target: "field-business-partner",
                    title: "2. Socio de negocio",
                    text: "El empleado en SAP: se usa por defecto en sus rendiciones.",
                },
                { target: "tab-profiles", title: "3. Perfiles", text: "Los tipos de rendición que puede crear." },
                { target: "tab-authorization", title: "4. Autorización", text: "El ciclo por el que pasan sus rendiciones." },
                { target: "tab-distribution", title: "5. Centros de costo", text: "Los que se usan por defecto en sus gastos." },
                { target: "save-user", title: "6. Guarde", text: "El usuario ya puede ingresar y rendir." },
            ],
        },
    ],
    "administration/profile/IndexProfile": [
        {
            id: "copy-profile",
            title: "Crear un perfil a partir de otro",
            description: "Copiar la configuración y ajustar lo distinto.",
            icon: "content_copy",
            steps: [
                { target: "copy-profile", title: "1. Copiar", text: "Duplica cuentas, documentos y prorrateos de un perfil existente." },
                { target: "edit-profile", title: "2. Ajuste", text: "Cambie nombre, moneda y lo que difiera." },
            ],
        },
    ],
    "administration/profile/CreateProfile": [
        {
            id: "document-types",
            title: "Configurar tipos de documento e impuestos",
            description: "Facturas, recibos, retenciones y prorrateo.",
            icon: "description",
            steps: [
                { target: "tab-documents", title: "1. Documentos", text: "Defina cada tipo de documento que podrán cargar." },
                { target: "document-editor", title: "2. Tipo de documento", text: "Nombre, tipo SAP y campos que se piden." },
                {
                    target: "invoice-flags",
                    optional: true,
                    title: "3. Factura con QR o ítems",
                    text: "Marque si el documento es factura (CUF/SIAT) o si lleva artículos de inventario.",
                },
                {
                    target: "proration-section",
                    title: "4. Impuestos y retenciones",
                    text: "Las líneas de IVA, IT, IUE o RC-IVA con su porcentaje y cuenta.",
                    example: {
                        title: "Factura (ejemplo)",
                        rows: [
                            ["Crédito fiscal IVA", "13 % · Debe"],
                            ["Gasto", "87 %"],
                        ],
                    },
                },
                { target: "save-profile", title: "5. Guarde", text: "Aplica a las rendiciones nuevas del perfil." },
            ],
        },
    ],
    "administration/authorization-cycles/CreateAuthorizationCycle": [
        {
            id: "two-levels",
            title: "Crear un ciclo de dos niveles",
            description: "Por ejemplo Jefatura y luego Contabilidad.",
            icon: "account_tree",
            steps: [
                { target: "cycle-data", title: "1. Nombre", text: "Por ejemplo «Ventas: Jefatura → Contabilidad»." },
                { target: "add-level", title: "2. Agregue los niveles", text: "Uno por cada aprobación." },
                { target: "level-approvers", optional: true, title: "3. Autorizadores", text: "Quién aprueba cada nivel." },
                { target: "level-order", optional: true, title: "4. Orden", text: "El último nivel autoriza y exporta a SAP." },
                { target: "save-cycle", title: "5. Guarde y asigne", text: "Después asígnelo a los usuarios desde Usuarios → Autorización." },
            ],
        },
    ],
    "administration/account-aliases/IndexAccountAlias": [
        {
            id: "import-aliases",
            title: "Importar alias desde Excel",
            description: "Cargar muchos alias de una vez.",
            icon: "upload_file",
            steps: [
                { target: "alias-template", title: "1. Descargue la plantilla", text: "Columnas Código Formato y Alias." },
                {
                    target: "alias-import",
                    title: "2. Importe",
                    text: "Al terminar verá cuántos se crearon y la lista de códigos omitidos (por ejemplo, cuentas que no existen).",
                },
                { target: "aliases-table", title: "3. Revise", text: "Los alias son los nombres que verán los usuarios al elegir la cuenta." },
            ],
        },
    ],
    "administration/reports/ReportAccountability": [
        {
            id: "export-excel",
            title: "Exportar el reporte a Excel",
            description: "Filtrar y descargar.",
            icon: "grid_on",
            steps: [
                { target: "report-filters", title: "1. Filtre", text: "Por fechas, perfil o estado." },
                { target: "report-totals", optional: true, title: "2. Totales por moneda", text: "Bs y $us se suman por separado." },
                { target: "export-excel", title: "3. Exportar", text: "Descarga el Excel con los mismos filtros." },
            ],
        },
    ],
    "administration/reports/AuditLog": [
        {
            id: "investigate",
            title: "Investigar un cambio",
            description: "Quién cambió qué y cuándo.",
            icon: "history",
            steps: [
                { target: "audit-filters", title: "1. Filtre", text: "Por tipo de registro, usuario o fechas." },
                { target: "audit-search", title: "2. Busque", text: "Por ejemplo el número de la rendición." },
                { target: "audit-table", title: "3. Revise", text: "Cada fila muestra los valores anteriores y nuevos." },
            ],
        },
    ],
};
