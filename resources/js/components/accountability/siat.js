// Utilidades de facturas SIAT y de los formularios de documentos de rendición.

function plain(text) {
    return String(text ?? "")
        .normalize("NFD")
        .replace(/[̀-ͯ]/g, "")
        .trim();
}

// Estado de la factura en SIAT tal como se muestra ("VÁLIDA", "ANULADA", ...)
export function invoiceStatusLabel(factura) {
    const status = plain(factura?.estadoFactura).toUpperCase();
    if (!status) return null;
    if (status === "VALIDA") return "VÁLIDA";
    return status;
}

// Solo se bloquea cuando SIAT informa un estado distinto de válida
export function isInvoiceValid(factura) {
    const status = plain(factura?.estadoFactura).toUpperCase();
    return !status || status === "VALIDA";
}

// Fecha de emisión (AAAA-MM-DD) de la respuesta de SIAT
export function invoiceDate(factura) {
    return factura?.fechaEmision ? String(factura.fechaEmision).substring(0, 10) : null;
}

// Datos del formulario que se obtienen de la factura
export function invoiceFormData(factura) {
    return {
        document_number: factura?.numeroFactura != null ? String(factura.numeroFactura) : null,
        cuf: factura?.cuf || null,
        nit: factura?.nitEmisor != null ? String(factura.nitEmisor) : null,
        business_name: factura?.razonSocialEmisor || null,
        amount: factura?.montoTotal ?? null,
        date: invoiceDate(factura),
    };
}

// La fecha (AAAA-MM-DD) está fuera del periodo de la rendición
export function isOutsidePeriod(date, start, end) {
    if (!date) return false;
    const day = String(date).substring(0, 10);
    if (start && day < String(start).substring(0, 10)) return true;
    if (end && day > String(end).substring(0, 10)) return true;
    return false;
}

export function formatDate(date) {
    if (!date) return "-";
    const [y, m, d] = String(date).substring(0, 10).split("-");
    return d && m && y ? `${d}/${m}/${y}` : String(date);
}

/**
 * Tipo de documento para facturas con QR. No hay una marca explícita: se prefiere
 * el que pide CUF y cuyo nombre contiene "Factura" (sin ítems), luego cualquiera
 * con CUF y por último cualquiera cuyo nombre contiene "Factura".
 */
export function findInvoiceDocument(documents) {
    const list = documents || [];
    const named = (d) => plain(d.name).toLowerCase().includes("factura");
    return (
        list.find((d) => d.cuf_status && named(d) && !d.items_status) ||
        list.find((d) => d.cuf_status && named(d)) ||
        list.find((d) => d.cuf_status) ||
        list.find((d) => named(d)) ||
        null
    );
}

// Montos escritos con coma decimal: "12,50" -> "12.50"
export function normalizeDecimal(value) {
    if (value === null || value === undefined) return value;
    return String(value).replace(/\s/g, "").replace(",", ".");
}

export function siatQueryUrl(doc) {
    const params = new URLSearchParams({ nit: doc.nit, cuf: doc.cuf, numero: doc.document_number });
    return `https://siat.impuestos.gob.bo/consulta/QR?${params.toString()}`;
}
