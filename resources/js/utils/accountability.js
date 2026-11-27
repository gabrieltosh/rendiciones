// Helpers de presentación compartidos por las páginas de rendiciones.

const STATUS_COLORS = {
    Borrador: "blue-grey",
    Pendiente: "orange",
    Rechazado: "red",
    Anulado: "grey",
    Autorizado: "green",
};

const STATUS_ICONS = {
    Borrador: "eva-edit-2-outline",
    Pendiente: "eva-clock-outline",
    Rechazado: "eva-alert-triangle-outline",
    Anulado: "eva-slash-outline",
    Autorizado: "eva-checkmark-circle-2-outline",
};

// Un status null en base de datos corresponde a una rendición en borrador
export function statusLabel(status) {
    return status || "Borrador";
}

export function statusColor(status) {
    return STATUS_COLORS[statusLabel(status)] || "blue-grey";
}

export function statusIcon(status) {
    return STATUS_ICONS[statusLabel(status)] || "eva-info-outline";
}

export function formatAmount(value) {
    return Number(value ?? 0).toLocaleString("es-BO", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

// Símbolo de la moneda del perfil (profiles.type_currency). Sin moneda: bolivianos.
export function currencySymbol(currency) {
    const code = String(currency ?? "").trim().toUpperCase();
    if (!code || code === "BS" || code === "BOB" || code === "BS.") return "Bs";
    if (code === "USD" || code === "US$" || code === "$") return "$us";
    return code;
}

// Monto con formato y moneda: "Bs 1.234,50"
export function formatMoney(value, currency) {
    return `${currencySymbol(currency)} ${formatAmount(value)}`;
}

// Saldo de una rendición: positivo = el solicitante devuelve, negativo = se le reembolsa
export function balanceInfo(received, spent) {
    const balance = Math.round((Number(received ?? 0) - Number(spent ?? 0)) * 100) / 100;
    if (balance > 0) return { balance, label: "Saldo a devolver", color: "positive" };
    if (balance < 0) return { balance: -balance, label: "Saldo a reembolsar", color: "warning" };
    return { balance: 0, label: "Rendición cuadrada", color: "primary" };
}
