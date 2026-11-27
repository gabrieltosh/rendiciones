import { ref, computed, unref, nextTick, onMounted, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";
import { useQuasar } from "quasar";

const DEFAULT_MESSAGE =
    "Tiene cambios sin guardar. Si sale ahora, se perderán. ¿Desea salir de todas formas?";

/**
 * Evita perder lo cargado en un formulario: compara el formulario con una foto
 * tomada al montar la página (después de normalizarlo en onMounted) y, si cambió,
 * pide confirmación antes de navegar a otra página de Inertia o de cerrar/recargar
 * la pestaña. Los envíos del formulario (POST/PUT/DELETE) no se interrumpen.
 *
 * @param source ref o función que devuelve el formulario
 * @returns { isDirty, markClean, confirmLeave }
 */
export function useDirtyGuard(source, options = {}) {
    const $q = useQuasar();
    const message = options.message ?? DEFAULT_MESSAGE;
    const snapshot = ref(null);
    let allowLeave = false;

    function serialize() {
        try {
            return JSON.stringify(typeof source === "function" ? source() : unref(source));
        } catch {
            return null;
        }
    }

    const isDirty = computed(() => snapshot.value !== null && serialize() !== snapshot.value);

    // Toma de nuevo la foto: el estado actual pasa a ser "sin cambios"
    function markClean() {
        snapshot.value = serialize();
    }

    function askToLeave(onLeave) {
        $q.dialog({
            title: "Cambios sin guardar",
            message,
            cancel: { label: "Seguir editando", flat: true, noCaps: true },
            ok: { label: "Salir sin guardar", color: "negative", noCaps: true, unelevated: true },
            persistent: true,
        }).onOk(() => {
            allowLeave = true;
            onLeave();
        });
    }

    // Para el botón Cancelar: pregunta solo si hay cambios
    function confirmLeave(onLeave) {
        if (!isDirty.value || allowLeave) {
            onLeave();
            return;
        }
        askToLeave(onLeave);
    }

    function handleBeforeUnload(event) {
        if (isDirty.value && !allowLeave) {
            event.preventDefault();
            event.returnValue = "";
        }
    }

    let removeBeforeListener = null;

    onMounted(() => {
        // nextTick: los componentes hijos (reparto, ítems) terminan de normalizar el formulario
        nextTick(markClean);
        window.addEventListener("beforeunload", handleBeforeUnload);
        removeBeforeListener = router.on("before", (event) => {
            const visit = event.detail.visit;
            if (allowLeave || !isDirty.value) return;
            // Guardar el formulario no es salir de la página
            if (String(visit.method).toLowerCase() !== "get") return;
            askToLeave(() =>
                router.visit(visit.url.href, {
                    method: visit.method,
                    data: visit.data,
                    replace: visit.replace,
                    preserveScroll: visit.preserveScroll,
                    preserveState: visit.preserveState,
                })
            );
            return false;
        });
    });

    onBeforeUnmount(() => {
        window.removeEventListener("beforeunload", handleBeforeUnload);
        if (removeBeforeListener) removeBeforeListener();
    });

    return { isDirty, markClean, confirmLeave };
}
