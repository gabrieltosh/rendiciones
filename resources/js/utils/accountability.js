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
