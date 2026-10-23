<template>
    <q-card class="q-pa-md card-form q-mb-md" :class="{ 'page-header--sticky': sticky }">
        <div class="row items-center q-col-gutter-sm">
            <div v-if="back" class="col-auto">
                <q-btn
                    flat
                    round
                    dense
                    icon="arrow_back"
                    aria-label="Volver"
                    @click="router.visit(back)"
                >
                    <q-tooltip>Volver</q-tooltip>
                </q-btn>
            </div>
            <div v-if="icon" class="col-auto">
                <q-avatar square size="42px" class="page-header-icon" :icon="icon" text-color="white" />
            </div>
            <div class="col" style="min-width: 0;">
                <div class="text-h6 title-form ellipsis">{{ title }}</div>
                <div v-if="subtitle || $slots.subtitle" class="text-caption text-grey">
                    <slot name="subtitle">{{ subtitle }}</slot>
                </div>
            </div>
            <div v-if="$slots.actions" class="col-xs-12 col-sm-auto row q-gutter-sm justify-end">
                <slot name="actions" />
            </div>
        </div>
    </q-card>
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
.page-header--sticky {
    position: sticky;
    top: 74px;
    z-index: 10;
}
/* En celulares el encabezado fijo ocupa demasiado: se oculta el subtítulo */
body.screen--xs .page-header--sticky .text-caption {
    display: none;
}
.page-header-icon {
    border-radius: 10px;
    background: linear-gradient(135deg, #1a73e8, #344767);
}
</style>
