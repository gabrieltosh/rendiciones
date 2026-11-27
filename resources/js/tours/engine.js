import { driver } from "driver.js";
import "driver.js/dist/driver.css";
import { router } from "@inertiajs/vue3";
import { LocalStorage } from "quasar";
import { tours } from "./definitions";

/*
 * Manual interactivo (driver.js).
 *
 * Cada página Inertia tiene un recorrido en ./definitions.js, indexado por el
 * nombre del componente (p. ej. "accountability/IndexAccountabilityNew"). Los
 * pasos apuntan a elementos marcados con data-tour="..." en las páginas. Un paso
 * cuyo elemento no está en pantalla (lista vacía, sección que no aplica al rol)
 * se muestra centrado con su ejemplo (`example`): así la guía se entiende aunque
 * el usuario todavía no tenga datos.
 *
 * - El botón "?" de la cabecera dispara el evento window 'start-tour'.
 * - La primera vez que un usuario entra a una pantalla con guía, se ofrece sola.
 */

const SEEN_PREFIX = "tour-seen:";
let installed = false;
let active = null;

function currentComponent() {
    return router.page?.component ?? null;
}

function currentUser() {
    return router.page?.props?.auth?.user ?? null;
}

function seenKey(component) {
    const user = currentUser();
    return `${SEEN_PREFIX}${user?.username ?? user?.email ?? "anon"}:${component}`;
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

function buildSteps(definition, context) {
    const raw = typeof definition.steps === "function" ? definition.steps(context) : definition.steps;
    return raw
        .filter((step) => !step.when || step.when(context))
        .map((step) => {
            const selector = step.target ? `[data-tour="${step.target}"]` : step.element;
            const element = selector ? findElement(selector) : null;
            // Sin elemento visible: paso centrado, solo si trae texto útil (ejemplo u "optional: false")
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

export function hasTour(component = currentComponent()) {
    return Boolean(component && tours[component]);
}

export function startTour(component = currentComponent()) {
    const definition = tours[component];
    if (!definition) return false;

    const user = currentUser();
    const context = {
        user,
        role: user?.type,
        isAdmin: user?.type === "Administrador",
        isAuthorizer: user?.type === "Administrador" || user?.type === "Autorizador",
        props: router.page?.props ?? {},
    };
    const steps = buildSteps(definition, context);
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
            LocalStorage.set(seenKey(component), true);
            active = null;
        },
    });
    active.drive();
    return true;
}

// Ofrece la guía la primera vez que el usuario abre una pantalla que la tiene
function offerFirstTime() {
    const component = currentComponent();
    if (!hasTour(component) || tours[component].auto === false) return;
    if (LocalStorage.getItem(seenKey(component))) return;
    // Se espera a que la página termine de pintarse (tablas, tarjetas)
    setTimeout(() => {
        if (currentComponent() === component && !active) startTour(component);
    }, 900);
}

export function installTours() {
    if (installed) return;
    installed = true;
    window.addEventListener("start-tour", () => startTour());
    router.on("navigate", () => {
        active?.destroy();
        offerFirstTime();
    });
}

// Para la primera carga (la visita inicial no emite 'navigate' en todas las versiones)
export function offerInitialTour() {
    offerFirstTime();
}
