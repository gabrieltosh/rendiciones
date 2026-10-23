import { nextTick, ref, watch } from "vue";

// Devuelve la primera sección (pestaña) que contiene un campo con error.
// `sections` mapea nombre de sección -> lista de campos (o prefijos terminados en ".").
export function firstSectionWithError(errors, sections) {
    const fields = Object.keys(errors || {});
    return (
        Object.keys(sections).find((section) =>
            sections[section].some((name) =>
                fields.some((field) =>
                    name.endsWith(".") ? field.startsWith(name) : field === name
                )
            )
        ) ?? null
    );
}

export function sectionHasError(errors, fields) {
    return firstSectionWithError(errors, { section: fields }) !== null;
}

// Lleva la vista al primer campo marcado con error
export function scrollToFirstError() {
    nextTick(() => {
        const field = document.querySelector(".form-field--error, .q-field--error");
        field?.scrollIntoView({ behavior: "smooth", block: "center" });
    });
}

// Pestaña activa sincronizada con el hash de la URL (#seccion), para que al
// recargar o compartir el enlace se abra la misma pestaña
export function useHashTab(defaultTab, tabs) {
    const initial = window.location.hash.slice(1);
    const tab = ref(tabs.includes(initial) ? initial : defaultTab);
    watch(tab, (value) => {
        // Se conserva history.state porque Inertia guarda ahí la página actual
        window.history.replaceState(window.history.state, "", `#${value}`);
    });
    return tab;
}
