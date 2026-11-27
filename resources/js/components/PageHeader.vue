<template>
    <header class="page-header" :class="{ 'page-header--sticky': sticky }">
        <div class="page-header__row">
            <q-btn
                v-if="back"
                flat
                round
                dense
                icon="arrow_back"
                aria-label="Volver"
                class="page-header__back"
                @click="router.visit(back)"
            >
                <q-tooltip>Volver</q-tooltip>
            </q-btn>
            <div v-if="icon" class="page-header__icon" aria-hidden="true">
                <q-icon :name="icon" size="20px" />
            </div>
            <div class="page-header__text">
                <h1 class="page-header__title ellipsis">{{ title }}</h1>
                <div v-if="subtitle || $slots.subtitle" class="page-header__description">
                    <slot name="subtitle">{{ subtitle }}</slot>
                </div>
            </div>
            <div v-if="$slots.actions" class="page-header__actions">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

defineProps({
    title: { type: String, required: true },
    subtitle: String,
    icon: String,
    // URL a la que vuelve el botón de retroceso; sin valor no se muestra
    back: String,
    // Mantiene el encabezado (y sus acciones, p. ej. Guardar) visible al hacer scroll
    sticky: Boolean,
});
</script>

<style>
/* Encabezado de página al estilo Nuxt UI (UPageHeader): sin tarjeta, con borde inferior */
.page-header {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--ui-border);
}
.page-header__row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.page-header__back {
    color: var(--ui-text-muted);
    margin-left: -4px;
}
.page-header__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    border-radius: var(--ui-radius-lg);
    background: var(--ui-primary-soft);
    color: var(--ui-primary-text);
    box-shadow: inset 0 0 0 1px rgba(26, 115, 232, 0.2);
}
.page-header__text {
    flex: 1 1 240px;
    min-width: 0;
}
.page-header__title {
    margin: 0;
    font-size: 20px;
    line-height: 28px;
    font-weight: 600;
    letter-spacing: -0.015em;
    color: var(--ui-text-highlighted);
}
.page-header__description {
    margin-top: 2px;
    font-size: 14px;
    line-height: 20px;
    color: var(--ui-text-muted);
}
.page-header__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-left: auto;
}
.page-header--sticky {
    position: sticky;
    top: var(--ui-header-height, 56px);
    z-index: 10;
    padding-top: 12px;
    margin-top: -12px;
    background: var(--ui-bg-page);
}
body.screen--xs .page-header__title {
    font-size: 18px;
    line-height: 26px;
}
body.screen--xs .page-header__actions {
    width: 100%;
    justify-content: stretch;
}
body.screen--xs .page-header__actions > .q-btn:not(.q-btn--round) {
    flex: 1 1 auto;
}
/* En celulares el encabezado fijo ocupa demasiado: se oculta la descripción */
body.screen--xs .page-header--sticky .page-header__description {
    display: none;
}
</style>
