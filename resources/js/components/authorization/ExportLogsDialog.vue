<template>
    <q-dialog :model-value="modelValue" :maximized="$q.screen.xs" @update:model-value="emit('update:modelValue', $event)">
        <q-card style="width: 900px; max-width: 95vw;">
            <q-card-section class="row items-center q-pb-none">
                <div class="text-h6 title-form">Historial de exportación · Rendición #{{ accountabilityId }}</div>
                <q-space />
                <q-btn icon="close" flat round dense v-close-popup aria-label="Cerrar" />
            </q-card-section>
            <q-card-section>
                <q-list v-if="logs?.length" bordered separator class="rounded-borders">
                    <q-item v-for="log in logs" :key="log.id">
                        <q-item-section avatar top>
                            <q-icon :name="log.success ? 'check_circle' : 'error'" :color="log.success ? 'positive' : 'negative'" />
                        </q-item-section>
                        <q-item-section>
                            <q-item-label class="text-weight-medium">
                                {{ log.success ? "Exportada" : "Error de SAP" }}
                                <span v-if="log.sap_key" class="text-grey"> · Asiento {{ log.sap_key }}</span>
                            </q-item-label>
                            <q-item-label caption style="white-space: normal; word-break: break-word;">{{ log.message }}</q-item-label>
                        </q-item-section>
                        <q-item-section side top class="text-right">
                            <q-item-label caption>{{ log.created_at }}</q-item-label>
                            <q-item-label caption>{{ log.user?.name || "—" }}{{ log.mode ? ` · ${log.mode}` : "" }}</q-item-label>
                        </q-item-section>
                    </q-item>
                </q-list>
                <div v-else class="text-center text-grey q-pa-lg">
                    Todavía no hay intentos de exportación registrados.
                </div>
            </q-card-section>
        </q-card>
    </q-dialog>
</template>

<script setup>
// Intentos de exportación a SAP de una rendición (tabla sap_export_logs), del más reciente al más antiguo.
import { useQuasar } from "quasar";

defineProps({
    modelValue: Boolean,
    accountabilityId: { type: [Number, String], default: null },
    logs: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue"]);
const $q = useQuasar();
</script>
