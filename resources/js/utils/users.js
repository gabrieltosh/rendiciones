export const roleOptions = [
    { label: "Administrador", value: "Administrador", caption: "Acceso total, incluida la configuración del sistema" },
    { label: "Autorizador", value: "Autorizador", caption: "Revisa, aprueba y exporta rendiciones a SAP" },
    { label: "Usuario", value: "Usuario", caption: "Registra y envía sus propias rendiciones" },
];

export const statusOptions = [
    { label: "Activo", value: "Activo", caption: "Puede iniciar sesión" },
    { label: "PreActivo", value: "PreActivo", caption: "Registrado, aún sin acceso" },
    { label: "Bloqueado", value: "Bloqueado", caption: "Sin acceso al sistema" },
];

// Dimensiones de SAP (1-5) y el campo del usuario donde se guarda cada una
export const distributionRules = [
    { dimension: 1, field: "distribution_rule_one" },
    { dimension: 2, field: "distribution_rule_second" },
    { dimension: 3, field: "distribution_rule_three" },
    { dimension: 4, field: "distribution_rule_four" },
    { dimension: 5, field: "distribution_rule_five" },
];
