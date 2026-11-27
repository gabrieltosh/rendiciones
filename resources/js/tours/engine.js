import { driver } from "driver.js";
import "driver.js/dist/driver.css";
import { router } from "@inertiajs/vue3";
import { tours } from "./definitions";
import { processes } from "./processes";

/*
 * Manual interactivo (driver.js).
 *
 * Cada página Inertia (indexada por el nombre del componente, p. ej.
 * "accountability/IndexAccountabilityNew") puede tener varios tutoriales:
 *  - "Recorrido de la pantalla": los pasos de ./definitions.js.
 *  - Tutoriales de procesos ("Cargar una factura escaneando el QR",
 *    "Autorizar una rendición"…): ./processes.js.
 *
 * Los tutoriales nunca se inician solos: el usuario los elige en el menú del botón
 * "Ayuda" de la cabecera (o en su menú de usuario).
 *
 * Los pasos apuntan a elementos marcados con data-tour="...". Un paso cuyo elemento
 * no está en pantalla (lista vacía, diálogo cerrado, sección que no aplica al rol)
 * se muestra centrado con su ejemplo (`example`), así se entiende aunque el
 * usuario todavía no tenga datos; con `optional: true` se omite.
 */

export const OVERVIEW_ID = "overview";

let installed = false;
let active = null;

function currentComponent() {
    return router.page?.component ?? null;
}

function buildContext(props = router.page?.props ?? {}) {
    const user = props?.auth?.user ?? null;
    return {
        user,
        role: user?.type,
        isAdmin: user?.type === "Administrador",
        isAuthorizer: user?.type === "Administrador" || user?.type === "Autorizador",
        props,
    };
}

// Tarjeta de datos de ejemplo que acompaña a un paso (HTML simple, sin estilos externos)
function exampleHtml(example) {
    if (!example) return "";
    const rows = (example.rows || [])
        .map(
            ([label, value, tone]) =>
                `<div class="tour-example__row"><span>${label}</span><strong class="${tone ? `tour-example--${tone}` : ""}">${value}</strong></div>`
        )
        .join("");
    const title = example.title ? `<div class="tour-example__title">${example.title}</div>` : "";
    return `<div class="tour-example"><div class="tour-example__badge">Ejemplo</div>${title}${rows}</div>`;
}

// Un elemento "está en pantalla" si ocupa espacio y no quedó fuera del viewport en
// horizontal (p. ej. el menú lateral cerrado en celular, botones ocultos con gt-xs o v-show)
function isOnScreen(element) {
    const rect = element.getBoundingClientRect();
    if (rect.width === 0 && rect.height === 0) return false;
    return rect.right > 0 && rect.left < window.innerWidth;
}

// Primer elemento visible que coincide (las listas repiten la marca en cada fila/tarjeta)
function findElement(selector) {
    return Array.from(document.querySelectorAll(selector)).find(isOnScreen) ?? null;
}

function buildSteps(rawSteps, context) {
    const raw = typeof rawSteps === "function" ? rawSteps(context) : rawSteps;
    return (raw || [])
        .filter((step) => !step.when || step.when(context))
        .map((step) => {
            const selector = step.target ? `[data-tour="${step.target}"]` : step.element;
            const element = selector ? findElement(selector) : null;
            // Sin elemento visible: se omite si es opcional; si no, va centrado con su ejemplo
            if (selector && !element && step.optional) return null;
            const description = `${step.text ?? ""}${exampleHtml(element ? step.exampleAlways && step.example : step.example)}`;
            return {
                element: element ?? undefined,
                popover: {
                    title: step.title,
                    description,
                    side: step.side,
                    align: step.align,
                },
            };
        })
        .filter(Boolean);
}

/**
 * Tutoriales disponibles en una página para el usuario actual:
 * [{ id, title, description, icon, steps }]. El recorrido de la pantalla va primero.
 */
export function tutorialsFor(component = currentComponent(), props = router.page?.props) {
    if (!component) return [];
    const context = buildContext(props);
    const list = [];

    const overview = tours[component];
    if (overview?.steps) {
        list.push({
            id: OVERVIEW_ID,
            title: "Recorrido de la pantalla",
            description: overview.description ?? "Qué es cada parte de esta pantalla.",
            icon: "explore",
            steps: overview.steps,
        });
    }

    for (const tutorial of processes[component] ?? []) {
        if (tutorial.when && !tutorial.when(context)) continue;
        list.push({ icon: "school", ...tutorial });
    }

    return list;
}

export function startTour(tutorialId = OVERVIEW_ID, component = currentComponent()) {
    const tutorial = tutorialsFor(component).find((t) => t.id === tutorialId) ?? tutorialsFor(component)[0];
    if (!tutorial) return false;

    const steps = buildSteps(tutorial.steps, buildContext());
    if (!steps.length) return false;

    active?.destroy();
    active = driver({
        showProgress: true,
        allowClose: true,
        smoothScroll: true,
        overlayOpacity: 0.55,
        stagePadding: 6,
        stageRadius: 10,
        popoverClass: "tour-popover",
        progressText: "{{current}} de {{total}}",
        nextBtnText: "Siguiente",
        prevBtnText: "Anterior",
        doneBtnText: "Entendido",
        steps,
        onDestroyed: () => {
            active = null;
        },
    });
    active.drive();
    return true;
}

// El evento window 'start-tour' (detail: { id }) inicia un tutorial de la página actual
export function installTours() {
    if (installed) return;
    installed = true;
    window.addEventListener("start-tour", (event) => startTour(event.detail?.id));
    // Al cambiar de página se cierra cualquier tutorial abierto
    router.on("navigate", () => active?.destroy());
}
