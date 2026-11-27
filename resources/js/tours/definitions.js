// Recorridos del manual interactivo, por componente de página Inertia.
//
// Cada paso apunta a un elemento marcado con data-tour="..." (ver tours/engine.js).
// Si el elemento no está en pantalla (lista vacía, sección que no aplica), el paso se
// muestra centrado con su `example`. Los datos de ejemplo son ficticios.

// ── Pasos compartidos ───────────────────────────────────────────────────────

// Formulario de cabecera de una rendición (solicitante y autorizador)
const accountabilityFormSteps = [
    {
        target: "field-employee",
        optional: true,
        title: "Empleado",
        text: "Seleccione el empleado (socio de negocio en SAP) a cuyo nombre se rinde el dinero. Algunos perfiles no lo piden.",
    },
    {
        target: "field-account",
        title: "Cuenta de la rendición",
        text: "Es la cuenta contable del dinero que recibió: el fondo a rendir, el anticipo o la caja chica.",
    },
    {
        target: "field-total",
        title: "Monto recibido",
        text: "Escriba cuánto dinero le entregaron. Con este monto se calcula el saldo: lo que debe devolver o lo que se le debe reembolsar.",
        example: {
            title: "Así se calcula el saldo",
            rows: [
                ["Recibido", "Bs 3.000,00"],
                ["Gastado", "Bs 2.745,50"],
                ["Saldo a devolver", "Bs 254,50", "positive"],
            ],
        },
        exampleAlways: true,
    },
    {
        target: "field-period",
        title: "Periodo",
        text: "Las fechas de inicio y fin delimitan el periodo de la rendición: los documentos que cargue deben tener fecha dentro de este rango.",
    },
];

// Formulario de un gasto (documento) — solicitante y autorizador
function expenseSteps({ canScan = true } = {}) {
    return [
        {
            target: "scan-invoice-cta",
            optional: true,
            title: "Empiece escaneando la factura",
            text: "Si su gasto es una factura con código QR, escanéela primero: el sistema completa el número, el NIT, la razón social, la fecha y el importe por usted.",
        },
        {
            target: "expense-general",
            title: "Información general",
            text: "Elija el tipo de gasto (la cuenta contable), la fecha del comprobante, el tipo de documento (factura, recibo, etc.) y una glosa que explique el gasto. La fecha debe estar dentro del periodo de la rendición; si queda fuera, verá un aviso.",
        },
        {
            target: "receipt-data",
            title: "Datos del comprobante",
            text: "Los campos cambian según el tipo de documento. Para facturas se piden N° de factura, CUF o código de control, NIT y razón social.",
            example: {
                title: "Factura SIAT de ejemplo",
                rows: [
                    ["N° de factura", "1542"],
                    ["NIT del proveedor", "1023456027"],
                    ["Razón social", "Hotel Los Tajibos S.R.L. (ficticio)"],
                    ["Fecha", "12/03/2026"],
                    ["Importe", "Bs 850,00"],
                ],
            },
        },
        ...(canScan
            ? [
                  {
                      target: "scan-qr",
                      title: "Escanear QR y validar en el SIAT",
                      text: "Con la cámara del celular (o pegando el enlace del QR) se consulta la factura en el SIAT de Impuestos Nacionales. El sistema le avisa si la factura está anulada, si está fuera del periodo o si ya fue registrada antes (duplicada).",
                      example: {
                          title: "Resultado de la validación",
                          rows: [
                              ["Estado en el SIAT", "VÁLIDA", "positive"],
                              ["Dentro del periodo", "Sí", "positive"],
                              ["¿Ya registrada?", "No"],
                          ],
                      },
                  },
              ]
            : []),
        {
            target: "cost-centers",
            optional: true,
            title: "Proyecto y centros de costo",
            text: "Si el gasto corresponde a un proyecto o a un centro de costo específico, indíquelo aquí. Si no, se usan los de su usuario.",
        },
        {
            target: "expense-distribution",
            title: "Reparto por líneas",
            text: "Active el reparto si un mismo gasto se divide entre varios centros de costo (por ejemplo 60 % Ventas y 40 % Logística). Cada combinación genera una línea con su propio tipo de gasto y glosa.",
            example: {
                title: "Factura de Bs 1.000,00 repartida",
                rows: [
                    ["CC Ventas · 60 %", "Bs 600,00"],
                    ["CC Logística · 40 %", "Bs 400,00"],
                    ["Total", "Bs 1.000,00", "positive"],
                ],
            },
        },
        {
            target: "expense-items",
            optional: true,
            title: "Artículos de la factura",
            text: "Para facturas con ítems de inventario, registre cada artículo con cantidad, precio sin IVA y almacén. Puede cargarlos desde el detalle de la factura SIAT; la suma debe coincidir con el importe.",
        },
    ];
}

// ── Recorridos ──────────────────────────────────────────────────────────────

export const tours = {
    // ── Inicio ──────────────────────────────────────────────────────────────
    welcome: {
        steps: [
            {
                title: "Bienvenido a Rendiciones",
                text: "Aquí registra sus rendiciones de gastos y viáticos: carga sus facturas y recibos, y las envía a autorización. Le mostramos lo principal en unos pasos.",
            },
            {
                target: "main-menu",
                title: "Menú principal",
                text: "Desde el menú llega a <strong>Mis Rendiciones</strong> y, según su rol, a Autorizaciones, Pendientes SAP, Administración y Reportes. En el celular se abre con el botón ☰.",
            },
            {
                target: "new-accountability",
                title: "Nueva rendición",
                text: "El camino más corto para empezar: elija un perfil y cree su rendición.",
            },
            {
                target: "review-cards",
                optional: true,
                when: (ctx) => ctx.isAuthorizer,
                title: "Sus tareas de revisión",
                text: "Rendiciones que esperan su autorización y las autorizadas que aún no llegan a SAP. Haga clic en una tarjeta para ir a la bandeja.",
            },
            {
                target: "summary-cards",
                title: "Resumen de sus rendiciones",
                text: "Cuántas rendiciones tiene en borrador, en revisión, rechazadas y autorizadas, con el total de cada estado.",
            },
            {
                target: "help-button",
                title: "Guía de cada pantalla",
                text: "Cada pantalla tiene su propia guía. Púlsela cuando la necesite; también está en su menú de usuario como <strong>Guía de la pantalla</strong>.",
            },
            {
                target: "user-menu",
                title: "Su cuenta",
                text: "Desde aquí activa el modo oscuro, vuelve a ver la guía o cierra la sesión.",
            },
        ],
    },

    // ── Solicitante ─────────────────────────────────────────────────────────
    "accountability/IndexProfiles": {
        steps: [
            {
                title: "Mis rendiciones",
                text: "Las rendiciones se organizan por <strong>perfil</strong>: cada perfil es un tipo de gasto (viáticos, caja chica, fondos a rendir…) con su moneda, sus cuentas y sus tipos de documento.",
            },
            {
                target: "profile-list",
                title: "Elija un perfil",
                text: "Haga clic en el perfil para ver sus rendiciones y crear una nueva. Si no aparece ninguno, solicite al administrador que le asigne uno.",
                example: {
                    title: "Perfiles de ejemplo",
                    rows: [
                        ["Viáticos nacionales", "Bs"],
                        ["Caja chica La Paz", "Bs"],
                        ["Viajes al exterior", "USD"],
                    ],
                },
            },
            {
                title: "Estados de una rendición",
                text: "Su rendición avanza por estos estados. Solo puede modificarla en <strong>Borrador</strong> o cuando fue <strong>Rechazada</strong>.",
                example: {
                    title: "Ciclo de vida",
                    rows: [
                        ["Borrador", "La está llenando"],
                        ["Pendiente", "En revisión", "warning"],
                        ["Rechazado", "Corrija y reenvíe", "negative"],
                        ["Autorizado", "Aprobada y enviada a SAP", "positive"],
                    ],
                },
            },
            {
                title: "El proceso completo",
                text: "1) Cree la rendición con el monto recibido. 2) Cargue sus gastos escaneando las facturas. 3) Envíela a autorización. 4) Si la observan, corrija y reenvíe.",
            },
        ],
    },

    "accountability/IndexAccountabilityNew": {
        steps: [
            {
                target: "new-accountability",
                title: "Nueva rendición",
                text: "Cree una rendición por cada dinero recibido: un viaje, un anticipo o un periodo de caja chica.",
                side: "bottom",
            },
            {
                target: "status-filters",
                title: "Filtrar por estado",
                text: "<strong>Borrador</strong>: aún la está llenando. <strong>Pendiente</strong>: en revisión. <strong>Rechazado</strong>: debe corregir y reenviar. <strong>Autorizado</strong>: aprobada. <strong>Anulado</strong>: ya no sigue.",
            },
            { target: "search", title: "Buscar", text: "Busque por número, descripción o empleado." },
            {
                target: "view-mode",
                optional: true,
                title: "Tarjetas o tabla",
                text: "Cambie entre la vista de tarjetas y la de tabla según prefiera.",
            },
            {
                target: "accountability-list",
                title: "Sus rendiciones",
                text: "Cada rendición muestra lo recibido, lo gastado y el saldo. Haga clic para abrirla y cargar sus gastos.",
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
                target: "accountability-card",
                optional: true,
                title: "Estado en contabilidad",
                text: "Las autorizadas indican además si ya están registradas en SAP o pendientes de contabilizar. Solo puede editar o eliminar las que están en borrador o rechazadas.",
            },
        ],
    },

    "accountability/CreateAccountability": {
        steps: [
            {
                title: "Nueva rendición",
                text: "Primero se registran los datos generales: el dinero que recibió y el periodo. Después agregará los gastos uno por uno.",
            },
            { target: "accountability-form", title: "Datos de la rendición", text: "Complete los campos marcados con *." },
            ...accountabilityFormSteps,
            {
                target: "save-accountability",
                title: "Crear y agregar documentos",
                text: "Al guardar, la rendición queda en <strong>Borrador</strong> y pasa directamente a la pantalla para cargar sus gastos.",
            },
        ],
    },

    "accountability/EditAccountability": {
        auto: false,
        steps: [
            {
                target: "accountability-form",
                title: "Editar rendición",
                text: "Puede corregir los datos mientras la rendición esté en borrador o haya sido rechazada.",
            },
            ...accountabilityFormSteps,
            { target: "save-accountability", title: "Guardar cambios", text: "Guarde para volver al detalle de la rendición." },
        ],
    },

    "accountability/DetailAccountabilityNew": {
        steps: [
            {
                target: "rejection-notes",
                optional: true,
                title: "Observaciones del autorizador",
                text: "Su rendición fue rechazada. Lea el motivo, pulse <strong>Corregir y reenviar</strong>, haga los cambios y vuelva a enviarla.",
            },
            {
                target: "status-banner",
                optional: true,
                title: "Estado de la rendición",
                text: "Aquí verá si está en revisión, autorizada (y si ya se registró en SAP) o anulada.",
            },
            {
                target: "no-authorizer",
                optional: true,
                title: "Sin autorizador",
                text: "Puede cargar sus gastos, pero para enviar la rendición el administrador debe asignarle un ciclo de autorización.",
            },
            {
                target: "balance-summary",
                title: "Recibido, gastado y saldo",
                text: "El saldo se actualiza con cada gasto: si gastó menos de lo recibido, debe devolver la diferencia; si gastó más, se le reembolsa.",
                example: {
                    title: "Rendición #10024 · Viaje a Santa Cruz",
                    rows: [
                        ["Recibido", "Bs 3.000,00"],
                        ["Gastado", "Bs 3.180,00"],
                        ["Saldo a reembolsar", "Bs 180,00", "warning"],
                    ],
                },
                exampleAlways: true,
            },
            {
                target: "add-expense",
                optional: true,
                title: "Agregar gasto",
                text: "Registre cada factura o recibo. Desde el celular puede escanear el QR de la factura y se valida en el SIAT.",
            },
            {
                target: "documents-list",
                title: "Documentos de la rendición",
                text: "Todos sus gastos cargados. Expanda uno para ver sus datos, importes y centros de costo; en las facturas con CUF, el ícono de recibo muestra los datos originales en el SIAT.",
                example: {
                    title: "Gasto de ejemplo",
                    rows: [
                        ["Hospedaje 2 noches · Factura N° 1542", "Bs 850,00"],
                        ["Pasaje aéreo LPB–VVI · Factura N° 88731", "Bs 1.240,00"],
                        ["Alimentación · Recibo", "Bs 155,50"],
                    ],
                },
            },
            {
                target: "send-accountability",
                optional: true,
                title: "Enviar a autorización",
                text: "Cuando termine, envíe la rendición. Necesita al menos un documento y un autorizador asignado. Mientras esté en revisión no podrá modificarla. Si fue rechazada, el botón dice <strong>Reenviar</strong>.",
            },
            {
                target: "print-accountability",
                title: "Imprimir",
                text: "Genera el reporte PDF de la rendición para firmar o archivar.",
            },
            {
                target: "history",
                title: "Historial de cambios",
                text: "Registro de quién cambió qué y cuándo.",
            },
        ],
    },

    "accountability/Detail/CreateDetailNew": {
        steps: [
            ...expenseSteps(),
            {
                target: "save-and-new",
                title: "Guardar y agregar otro",
                text: "Guarda este gasto y deja el formulario listo para el siguiente, ideal cuando tiene varias facturas.",
            },
            {
                target: "save-expense",
                title: "Guardar gasto",
                text: "Guarda y vuelve al detalle de la rendición, donde verá el saldo actualizado.",
            },
        ],
    },

    "accountability/Detail/EditDetailNew": {
        auto: false,
        steps: [
            { title: "Editar gasto", text: "Corrija los datos del comprobante. Puede volver a escanear el QR si lo necesita." },
            ...expenseSteps(),
            { target: "save-expense", title: "Guardar cambios", text: "Guarda y vuelve al detalle de la rendición." },
        ],
    },

    // ── Autorizador ─────────────────────────────────────────────────────────
    "authorization/IndexAccountabilityNew": {
        steps: [
            {
                title: "Bandeja de autorización",
                text: "Aquí llegan las rendiciones que le toca revisar según el ciclo de autorización. Cada rendición pasa por uno o más niveles hasta quedar autorizada y exportada a SAP.",
            },
            {
                target: "show-all",
                optional: true,
                when: (ctx) => ctx.isAdmin,
                title: "Ver todas",
                text: "Como administrador puede ver y autorizar las rendiciones de todos los niveles, no solo las que le corresponden.",
            },
            {
                target: "inbox-tabs",
                title: "Pendientes y autorizadas",
                text: "<strong>Pendientes</strong>: esperan su revisión. <strong>Autorizadas</strong>: historial con filtros por perfil y estado SAP.",
            },
            { target: "search", title: "Buscar", text: "Busque por número, descripción o solicitante." },
            {
                target: "pending-list",
                title: "Rendiciones por revisar",
                text: "Se ordenan por antigüedad: las que llevan más tiempo esperando aparecen primero. Cada una muestra el solicitante, el perfil y el nivel actual del ciclo.",
                example: {
                    title: "Rendición #10024 · Viaje a Santa Cruz",
                    rows: [
                        ["Realizado por", "Juan Pérez"],
                        ["Nivel actual", "Nivel 1 · Jefatura de área"],
                        ["Enviada", "Hace 6 días", "negative"],
                    ],
                },
            },
            {
                target: "aging-badge",
                optional: true,
                title: "Antigüedad",
                text: "Verde: enviada hace poco. Amarillo: más de 2 días. Rojo: más de 5 días esperando.",
            },
            {
                target: "card-actions",
                optional: true,
                title: "Revisar",
                text: "Abra el detalle para revisar los documentos y autorizar o rechazar. Con el lápiz puede corregir los datos de cabecera.",
            },
        ],
    },

    "authorization/DetailAccountabilityNew": {
        steps: [
            {
                title: "Revisión de la rendición",
                text: "Revise los datos, el ciclo de autorización, los documentos y el asiento contable antes de decidir.",
            },
            {
                target: "status-banner",
                optional: true,
                title: "Estado",
                text: "Indica si la rendición ya fue autorizada, rechazada o anulada, y si llegó a SAP.",
            },
            {
                target: "general-info",
                title: "Información general",
                text: "Monto recibido, gastado y saldo, más el empleado, la cuenta y el periodo.",
            },
            {
                target: "authorization-cycle",
                title: "Ciclo de autorización",
                text: "Los niveles por los que pasa la rendición, quién aprobó cada uno y sus comentarios. También verá los envíos anteriores si fue rechazada antes.",
                example: {
                    title: "Ciclo de ejemplo",
                    rows: [
                        ["Nivel 1 · Jefatura", "Aprobado · Ana Mamani", "positive"],
                        ["Nivel 2 · Contabilidad", "En revisión", "warning"],
                    ],
                },
            },
            {
                target: "documents-list",
                title: "Documentos",
                text: "Cada gasto con su comprobante, importes y centros de costo. En las facturas con CUF, el ícono de recibo muestra los datos originales del SIAT para comparar. Puede editar o agregar documentos si hace falta corregir algo.",
            },
            {
                target: "accounting-tab",
                title: "Vista contable",
                text: "Antes de autorizar, revise el asiento que se enviará a SAP: cuentas, débitos y créditos, centros de costo y si está cuadrado.",
                example: {
                    title: "Asiento de ejemplo",
                    rows: [
                        ["6.1.02 Viáticos (Debe)", "Bs 2.389,04"],
                        ["1.1.05 Crédito fiscal IVA (Debe)", "Bs 356,46"],
                        ["1.1.03 Fondos a rendir (Haber)", "Bs 2.745,50"],
                        ["Estado", "Cuadrado", "positive"],
                    ],
                },
                exampleAlways: true,
            },
            {
                target: "authorize-button",
                optional: true,
                title: "Aprobar o autorizar",
                text: "Si hay más niveles, <strong>Aprobar y avanzar</strong> la pasa al siguiente. En el último nivel, <strong>Autorizar</strong> la exporta a SAP. Puede dejar un comentario al aprobar.",
            },
            {
                target: "reject-button",
                optional: true,
                title: "Rechazar",
                text: "Devuelve la rendición al solicitante con un motivo, para que corrija y la reenvíe.",
            },
            {
                target: "annul-button",
                optional: true,
                title: "Anular",
                text: "Cierra la rendición definitivamente; no se puede reenviar. Úselo solo cuando la rendición no debe continuar.",
            },
        ],
    },

    "authorization/EditAccountability": {
        auto: false,
        steps: [
            {
                target: "accountability-form",
                title: "Corregir cabecera",
                text: "Como autorizador puede corregir los datos generales de la rendición antes de autorizarla.",
            },
            ...accountabilityFormSteps,
            { target: "save-accountability", title: "Guardar cambios", text: "Los cambios quedan en el historial de la rendición." },
        ],
    },

    "authorization/Detail/CreateDetailNew": {
        auto: false,
        steps: [
            { title: "Agregar documento", text: "Agregue un gasto a la rendición que está revisando." },
            ...expenseSteps(),
            { target: "save-expense", title: "Guardar gasto", text: "Guarda y vuelve al detalle de la revisión." },
        ],
    },

    "authorization/Detail/EditDetail": {
        auto: false,
        steps: [
            { title: "Corregir documento", text: "Corrija los datos del gasto antes de autorizar la rendición." },
            ...expenseSteps({ canScan: false }),
            { target: "save-expense", title: "Guardar cambios", text: "Guarda y vuelve al detalle de la revisión." },
        ],
    },

    "authorization/PendingExport": {
        steps: [
            {
                title: "Pendientes de exportación",
                text: "Rendiciones ya autorizadas que aún no llegaron a SAP, por ejemplo porque el Service Layer no respondió o faltaba un dato contable.",
            },
            {
                target: "pending-export-table",
                title: "Rendiciones por exportar",
                text: "Revise el solicitante, el monto y el último intento de exportación de cada una.",
                example: {
                    title: "Rendición #10031 · Caja chica marzo",
                    rows: [
                        ["Solicitante", "Ana Mamani"],
                        ["Monto", "Bs 1.480,00"],
                        ["Último intento", "Error: cuenta inactiva", "negative"],
                    ],
                },
            },
            {
                target: "last-attempt",
                optional: true,
                title: "Último intento",
                text: "Muestra si la última exportación falló y el mensaje de SAP, para saber qué corregir.",
            },
            {
                target: "export-logs",
                optional: true,
                title: "Historial de intentos",
                text: "Todos los intentos de exportación con fecha, usuario y respuesta de SAP.",
            },
            {
                target: "edit-header",
                optional: true,
                title: "Editar cabecera o empleado",
                text: "Corrija la cuenta, el empleado u otros datos de cabecera que causaron el error, sin devolver la rendición.",
            },
            {
                target: "export-one",
                optional: true,
                title: "Exportar a SAP",
                text: "Vuelve a intentar la exportación de esta rendición.",
            },
            {
                target: "select-row",
                optional: true,
                title: "Reexportar en lote",
                text: "Marque varias rendiciones con la casilla y pulse <strong>Reexportar seleccionadas</strong>; verá el avance y el resultado de cada una.",
            },
        ],
    },

    // ── Administración ──────────────────────────────────────────────────────
    "administration/users/IndexUser": {
        steps: [
            {
                title: "Usuarios",
                text: "Gestione quién entra al sistema, con qué rol, qué perfiles puede usar y quién le autoriza.",
            },
            { target: "create-user", title: "Crear usuario", text: "Registre un nuevo usuario con sus datos, perfiles y ciclo de autorización." },
            {
                target: "role-filters",
                optional: true,
                title: "Roles",
                text: "<strong>Usuario</strong>: rinde sus gastos. <strong>Autorizador</strong>: además revisa rendiciones. <strong>Administrador</strong>: además configura el sistema.",
            },
            {
                target: "users-table",
                title: "Lista de usuarios",
                text: "Vea el rol y el estado de cada usuario, y búsquelo por nombre o usuario.",
                example: {
                    title: "Usuario de ejemplo",
                    rows: [
                        ["Juan Pérez", "Usuario"],
                        ["Ana Mamani", "Autorizador"],
                        ["Estado", "Activo", "positive"],
                    ],
                },
            },
            { target: "edit-user", optional: true, title: "Editar", text: "Cambie datos, perfiles asignados o ciclo de autorización." },
        ],
    },

    "administration/users/CreateUser": {
        auto: false,
        steps: [
            { target: "user-tabs", title: "Datos por pestañas", text: "Complete cada pestaña antes de guardar." },
            { target: "field-role", title: "Rol", text: "Define qué menús y acciones verá el usuario." },
            {
                target: "field-business-partner",
                title: "Socio de negocio SAP",
                text: "Vincule al usuario con su empleado en SAP (socio de negocio). Es la cuenta a la que se cargan sus rendiciones.",
                example: { title: "Ejemplo", rows: [["Socio de negocio", "E00123 · Juan Pérez"]] },
            },
            { target: "tab-profiles", title: "Perfiles", text: "Marque los perfiles (tipos de rendición) que el usuario puede usar." },
            {
                target: "tab-authorization",
                title: "Autorización",
                text: "Asigne el ciclo de autorización: define quién aprueba sus rendiciones y en qué orden. Puede crear un ciclo nuevo desde aquí.",
            },
            { target: "tab-distribution", title: "Centros de costo", text: "Centros de costo (normas de reparto) que se usan por defecto al contabilizar en SAP sus rendiciones." },
            { target: "save-user", title: "Guardar", text: "Guarde para crear el usuario." },
        ],
    },

    "administration/users/EditUser": {
        auto: false,
        steps: [
            { target: "user-tabs", title: "Editar usuario", text: "Los datos están organizados en pestañas." },
            { target: "field-role", title: "Rol", text: "Cambiar el rol modifica lo que el usuario ve en el menú." },
            {
                target: "field-business-partner",
                title: "Socio de negocio SAP",
                text: "Empleado de SAP al que se cargan las rendiciones del usuario.",
            },
            { target: "tab-profiles", title: "Perfiles", text: "Perfiles de rendición habilitados para el usuario." },
            { target: "tab-authorization", title: "Autorización", text: "Ciclo de autorización que siguen sus rendiciones." },
            { target: "save-user", title: "Guardar cambios", text: "Si deja la contraseña vacía, se mantiene la actual." },
        ],
    },

    "administration/profile/IndexProfile": {
        steps: [
            {
                title: "Perfiles",
                text: "Un perfil es un tipo de rendición (viáticos, caja chica, fondos a rendir). Define la moneda, los empleados, las cuentas permitidas y los tipos de documento con su prorrateo contable.",
            },
            { target: "create-profile", title: "Crear perfil", text: "Configure un nuevo tipo de rendición." },
            {
                target: "profiles-table",
                title: "Lista de perfiles",
                text: "Cada perfil con su moneda.",
                example: { title: "Perfil de ejemplo", rows: [["Viáticos nacionales", "Bs"], ["Viajes al exterior", "USD"]] },
            },
            {
                target: "copy-profile",
                optional: true,
                title: "Duplicar",
                text: "Copie un perfil existente con sus documentos y prorrateos para crear uno parecido sin empezar de cero.",
            },
            { target: "edit-profile", optional: true, title: "Editar", text: "Modifique cuentas, documentos y prorrateos." },
        ],
    },

    "administration/profile/CreateProfile": {
        auto: false,
        steps: [
            { target: "profile-tabs", title: "Configuración por pestañas", text: "Recorra cada pestaña para definir el perfil." },
            { target: "field-currency", title: "Moneda", text: "Moneda en la que se registran las rendiciones de este perfil." },
            {
                target: "tab-employees",
                title: "Empleados",
                text: "Empleados de SAP que pueden elegirse al crear una rendición, o marque que el perfil no requiere empleado.",
            },
            {
                target: "tab-general-accounts",
                title: "Cuentas cabecera",
                text: "Cuentas del dinero recibido (fondos, anticipos, caja chica) que el usuario elige al crear la rendición.",
            },
            {
                target: "tab-detail-accounts",
                title: "Cuentas detalle",
                text: "Cuentas de gasto (tipo de gasto) permitidas para cada documento.",
            },
            {
                target: "tab-documents",
                title: "Tipos de documento y prorrateo",
                text: "Defina los documentos (factura, recibo, factura con ítems…), qué datos piden (CUF, NIT, N° de autorización) y el prorrateo del asiento: cómo se reparte el importe entre gasto, IVA, IT o retenciones.",
                example: {
                    title: "Prorrateo de una factura",
                    rows: [
                        ["Gasto (cuenta detalle)", "87 %"],
                        ["Crédito fiscal IVA", "13 %"],
                        ["Total", "100 %", "positive"],
                    ],
                },
            },
            { target: "save-profile", title: "Guardar", text: "Si un documento tiene errores, se marcará con «Revisar»." },
        ],
    },

    "administration/profile/EditProfile": {
        auto: false,
        steps: [
            { target: "profile-tabs", title: "Editar perfil", text: "Los cambios afectan a las rendiciones nuevas de este perfil." },
            { target: "tab-employees", title: "Empleados", text: "Empleados disponibles en el perfil." },
            { target: "tab-general-accounts", title: "Cuentas cabecera", text: "Cuentas del dinero recibido." },
            { target: "tab-detail-accounts", title: "Cuentas detalle", text: "Tipos de gasto permitidos." },
            {
                target: "tab-documents",
                title: "Documentos y prorrateo",
                text: "Tipos de documento, datos que piden y reparto contable. Los porcentajes del prorrateo deben sumar 100 %.",
            },
            { target: "save-profile", title: "Guardar cambios", text: "Guarde para aplicar la configuración." },
        ],
    },

    "administration/areas/IndexArea": {
        auto: false,
        steps: [
            { title: "Áreas", text: "Las áreas agrupan a los usuarios (Comercial, Finanzas, Logística…) y sirven para filtrar los reportes." },
            { target: "create-area", title: "Crear área", text: "Registre una nueva área." },
            {
                target: "areas-table",
                title: "Lista de áreas",
                text: "Todas las áreas registradas.",
                example: { title: "Ejemplo", rows: [["Comercial", "Ventas y marketing"], ["Logística", "Almacenes y transporte"]] },
            },
            { target: "edit-area", optional: true, title: "Editar o eliminar", text: "Cambie el nombre o elimine un área que ya no se usa." },
            { title: "Dónde se usan", text: "Asigne el área a cada usuario en <strong>Usuarios</strong>; el <strong>Reporte de Rendiciones</strong> permite filtrar por área." },
        ],
    },

    "administration/areas/CreateArea": {
        auto: false,
        steps: [
            { title: "Nueva área", text: "Las áreas agrupan a los usuarios para organizar los reportes." },
            { target: "area-form", title: "Datos del área", text: "Indique el nombre y, si quiere, una descripción para identificarla mejor." },
            { target: "save-area", title: "Guardar", text: "Guarde para registrar el área." },
            { title: "Siguiente paso", text: "Asigne el área a los usuarios desde <strong>Usuarios</strong> → Datos del usuario." },
        ],
    },

    "administration/areas/EditArea": {
        auto: false,
        steps: [
            { title: "Editar área", text: "Los cambios se reflejan en los usuarios que tienen asignada esta área." },
            { target: "area-form", title: "Datos del área", text: "Cambie el nombre o la descripción." },
            { target: "save-area", title: "Guardar cambios", text: "Guarde para aplicar los cambios." },
            { title: "Reportes", text: "El nuevo nombre aparecerá en el filtro de área del Reporte de Rendiciones." },
        ],
    },

    "administration/suppliers/IndexSupplier": {
        auto: false,
        steps: [
            {
                title: "Proveedores",
                text: "Catálogo de proveedores (NIT y razón social). Al escribir el NIT en un gasto, la razón social se completa sola.",
            },
            { target: "create-supplier", title: "Crear proveedor", text: "Registre un proveedor nuevo." },
            {
                target: "suppliers-table",
                title: "Lista de proveedores",
                text: "Busque, edite o elimine proveedores.",
                example: { title: "Ejemplo (ficticio)", rows: [["1023456027", "Hotel Los Tajibos S.R.L."], ["4587120019", "Transportes Illimani Ltda."]] },
            },
            { target: "edit-supplier", optional: true, title: "Editar o eliminar", text: "Corrija la razón social o el NIT de un proveedor." },
        ],
    },

    "administration/suppliers/CreateSupplier": {
        auto: false,
        steps: [
            { title: "Nuevo proveedor", text: "Registre proveedores frecuentes para que la razón social se complete sola al cargar gastos." },
            { target: "supplier-form", title: "Datos del proveedor", text: "Indique la razón social y el NIT." },
            { target: "field-nit", title: "NIT", text: "Debe ser único: es la clave para autocompletar la razón social en los gastos." },
            { target: "save-supplier", title: "Guardar", text: "Guarde para agregarlo al catálogo." },
        ],
    },

    "administration/suppliers/EditSupplier": {
        auto: false,
        steps: [
            { title: "Editar proveedor", text: "Los cambios se aplican al autocompletar la razón social en los próximos gastos." },
            { target: "supplier-form", title: "Datos del proveedor", text: "Corrija la razón social o el NIT." },
            { target: "field-nit", title: "NIT", text: "Debe ser único en el catálogo." },
            { target: "save-supplier", title: "Guardar cambios", text: "Guarde para aplicar los cambios." },
        ],
    },

    "administration/authorization-cycles/IndexAuthorizationCycle": {
        steps: [
            {
                title: "Ciclos de autorización",
                text: "Un ciclo define por cuántos niveles pasa una rendición y quién aprueba cada nivel. Cada usuario tiene asignado un ciclo.",
            },
            { target: "create-cycle", title: "Nuevo ciclo", text: "Cree un ciclo con sus niveles y autorizadores." },
            {
                target: "cycles-table",
                title: "Lista de ciclos",
                text: "Vea cuántos niveles tiene cada ciclo y si está activo.",
                example: {
                    title: "Ciclo «Viáticos Comercial»",
                    rows: [
                        ["Nivel 1 · Jefatura", "Ana Mamani"],
                        ["Nivel 2 · Contabilidad", "Carlos Quispe"],
                        ["Estado", "Activo", "positive"],
                    ],
                },
            },
            { target: "expand-levels", optional: true, title: "Ver niveles", text: "Despliegue el ciclo para ver sus niveles y autorizadores." },
        ],
    },

    "administration/authorization-cycles/CreateAuthorizationCycle": {
        auto: false,
        steps: [
            { target: "cycle-data", title: "Datos del ciclo", text: "Nombre y descripción para identificarlo al asignarlo a los usuarios." },
            { target: "cycle-status", title: "Estado", text: "Solo los ciclos activos se pueden asignar." },
            {
                target: "cycle-levels",
                title: "Niveles",
                text: "La rendición pasa por los niveles en orden. En cada nivel basta con que uno de sus autorizadores apruebe para avanzar.",
            },
            { target: "add-level", title: "Agregar nivel", text: "Añada niveles; el último es el que autoriza y exporta a SAP." },
            { target: "level-order", optional: true, title: "Orden", text: "Suba o baje un nivel para cambiar el orden de aprobación." },
            { target: "level-approvers", optional: true, title: "Autorizadores", text: "Usuarios que pueden aprobar este nivel." },
            { target: "save-cycle", title: "Guardar ciclo", text: "Luego asígnelo a los usuarios desde la pantalla de Usuarios." },
        ],
    },

    "administration/authorization-cycles/EditAuthorizationCycle": {
        auto: false,
        steps: [
            { target: "cycle-data", title: "Editar ciclo", text: "Cambie nombre, descripción o estado." },
            { target: "cycle-levels", title: "Niveles", text: "Agregue, quite o reordene niveles y sus autorizadores." },
            { target: "level-approvers", optional: true, title: "Autorizadores", text: "Usuarios que pueden aprobar cada nivel." },
            { target: "save-cycle", title: "Guardar ciclo", text: "Los cambios aplican a los próximos envíos." },
        ],
    },

    "administration/account-aliases/IndexAccountAlias": {
        steps: [
            {
                title: "Alias de cuentas",
                text: "Los alias son nombres amigables para las cuentas de SAP. El usuario ve «Pasajes y viáticos» en lugar del código contable.",
            },
            { target: "new-alias", title: "Nuevo alias", text: "Busque la cuenta en el árbol de SAP y escriba su alias." },
            {
                target: "aliases-table",
                title: "Lista de alias",
                text: "Cada alias con su cuenta SAP.",
                example: {
                    title: "Ejemplo",
                    rows: [
                        ["6.1.02.001 Gastos de viaje", "Pasajes y viáticos"],
                        ["6.1.05.003 Material de escritorio", "Útiles de oficina"],
                    ],
                },
            },
            { target: "alias-template", title: "Plantilla", text: "Descargue la plantilla de Excel con las columnas Código Formato y Alias." },
            {
                target: "alias-import",
                title: "Importar Excel",
                text: "Suba la plantilla llena: el sistema busca cada cuenta en SAP y le informa los códigos que no encontró.",
            },
        ],
    },

    "administration/management/IndexManagement": {
        auto: false,
        steps: [
            {
                title: "Configuración",
                text: "Parámetros generales del sistema y de la integración con SAP. Cambie estos valores con cuidado: afectan a todas las rendiciones.",
            },
            {
                target: "management-tabs",
                title: "Secciones",
                text: "<strong>Rendición</strong>: conexión con SAP. <strong>Detalle</strong>: campos de SAP donde se registra cada gasto. <strong>Empleados</strong> y <strong>Proveedores</strong>: cómo se identifican en SAP. <strong>Empresa</strong>: datos y logo de los reportes.",
            },
            {
                target: "management-panel",
                title: "Conexión con SAP",
                text: "URL del Service Layer, base de datos, usuario, modo de exportación (Service Layer o DI API) y si la base es HANA. Los valores sensibles se ocultan; use el ojo para verlos.",
                example: {
                    title: "Ejemplo (ficticio)",
                    rows: [
                        ["URL Service Layer SAP", "https://sap.ejemplo.bo:50000/b1s/v1"],
                        ["Nombre BD SAP", "SBO_EJEMPLO"],
                        ["Modo de exportación", "SL"],
                        ["Base de datos HANA", "Sí"],
                    ],
                },
            },
            { target: "save-management", title: "Guardar cambios", text: "Guarda todas las secciones a la vez." },
        ],
    },

    // ── Reportes ────────────────────────────────────────────────────────────
    "administration/reports/ReportAccountability": {
        steps: [
            { title: "Reporte de rendiciones", text: "Consulte las rendiciones de todos los usuarios y expórtelas a Excel." },
            {
                target: "report-filters",
                title: "Filtros",
                text: "Filtre por área, usuario, rango de fechas y estado en SAP; luego pulse <strong>Buscar</strong>.",
            },
            {
                target: "report-table",
                title: "Resultados",
                text: "Haga clic en una fila para abrir la rendición. Se ve el estado y si ya está en contabilidad.",
                example: {
                    title: "Rendición #10024",
                    rows: [
                        ["Juan Pérez · Comercial", "Bs 2.745,50"],
                        ["Estado", "Autorizado", "positive"],
                        ["SAP", "En contabilidad · N° 45123"],
                    ],
                },
            },
            { target: "report-totals", optional: true, title: "Totales", text: "Totales de los resultados filtrados por moneda." },
            { target: "export-excel", title: "Exportar a Excel", text: "Descarga el resultado con los filtros aplicados." },
        ],
    },

    "administration/reports/AuditLog": {
        auto: false,
        steps: [
            { title: "Log de auditoría", text: "Registro de cada creación, modificación y eliminación en el sistema: quién, qué y cuándo." },
            { target: "audit-filters", title: "Filtros", text: "Filtre por usuario, modelo (rendición, documento, usuario…), acción y fechas." },
            {
                target: "audit-table",
                title: "Registros",
                text: "Cada fila muestra el cambio con los valores anteriores y nuevos.",
                example: {
                    title: "Cambio de ejemplo",
                    rows: [
                        ["Usuario", "Ana Mamani"],
                        ["Acción", "Modificado", "warning"],
                        ["Monto", "Bs 2.500,00 → Bs 3.000,00"],
                    ],
                },
            },
            { target: "audit-search", title: "Buscar", text: "Aplique los filtros; <strong>Limpiar</strong> vuelve a mostrar todo." },
        ],
    },
};
