import { router } from "@inertiajs/vue3";
import { Notify } from "quasar";

/*
 * Notificación global de mensajes flash (session 'message' / 'type').
 *
 * Contrato: tras cada visita Inertia exitosa cuyas props compartidas traen
 * `flash.message`, se muestra un único Notify. Se omite cuando viene
 * `flash.sap_number` (esas páginas muestran su propio diálogo).
 *
 * Los layouts (MainLayout y AuthLayout) llaman a installFlashNotifications()
 * en cada montaje; el listener se registra una sola vez para toda la vida
 * de la aplicación. Las páginas NO deben volver a llamar a $q.notify con
 * page.props.flash en sus onSuccess.
 */

let installed = false;
let initialChecked = false;
// Las recargas parciales (only: [...]) conservan la misma referencia del
// objeto flash anterior: comparar por identidad evita notificarlo dos veces.
let lastFlash = null;

function notifyFlash(flash) {
    if (!flash || flash === lastFlash) return;
    lastFlash = flash;
    if (!flash.message || flash.sap_number) return;
    Notify.create({ type: flash.type || "info", message: flash.message });
}

export function installFlashNotifications() {
    if (installed) return;
    installed = true;
    router.on("success", (event) => notifyFlash(event.detail.page?.props?.flash));
}

// Primera carga de la aplicación (la visita inicial no emite 'success').
export function notifyInitialFlash() {
    if (initialChecked) return;
    initialChecked = true;
    notifyFlash(router.page?.props?.flash);
}
