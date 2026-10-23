<template>
    <q-dialog :model-value="modelValue" persistent @update:model-value="emit('update:modelValue', $event)">
        <q-card style="width: 520px; max-width: 96vw;">
            <q-card-section class="row items-center q-pb-none">
                <div class="text-h6">Nuevo ciclo de autorización</div>
                <q-space />
                <q-btn icon="close" flat round dense v-close-popup aria-label="Cerrar" />
            </q-card-section>
            <q-card-section>
                <FormField label="Nombre" required class="q-mb-md">
                    <q-input v-model="cycle.name" dense outlined class="input-theme" placeholder="Ej: Aprobación Gerencial" />
                </FormField>

                <div class="row items-center q-mb-sm">
                    <span class="form-label q-mb-none">Niveles</span>
                    <q-btn flat dense no-caps size="sm" color="primary" icon="add" label="Agregar nivel" class="q-ml-sm"
                        @click="cycle.levels.push({ name: '', user_ids: [] })" />
                </div>

                <div v-for="(level, idx) in cycle.levels" :key="idx" class="q-pa-sm q-mb-sm rounded-borders level-box">
                    <div class="row items-center q-mb-xs">
                        <q-badge color="primary" rounded class="q-mr-sm">{{ idx + 1 }}</q-badge>
                        <q-input v-model="level.name" dense outlined class="input-theme col"
                            :placeholder="`Nombre del nivel ${idx + 1}`" />
                        <q-btn flat round dense icon="delete" color="negative" class="q-ml-xs"
                            :disable="cycle.levels.length === 1" :aria-label="`Eliminar nivel ${idx + 1}`"
                            @click="cycle.levels.splice(idx, 1)" />
                    </div>
                    <div class="form-label text-caption q-mb-xs">Autorizadores <span class="text-negative">*</span></div>
                    <q-select v-model="level.user_ids" :options="users" option-value="value" option-label="label"
                        emit-value map-options multiple use-chips dense outlined class="input-theme"
                        placeholder="Seleccionar autorizadores…" />
                </div>
            </q-card-section>
            <q-card-actions align="right" class="q-px-md q-pb-md">
                <q-btn flat label="Cancelar" no-caps v-close-popup />
                <q-btn color="primary" label="Crear ciclo" no-caps unelevated :loading="saving" @click="HandleSave" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

<script setup>
import { ref } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import FormField from "@/components/FormField.vue";

defineProps({
    modelValue: Boolean,
    // Usuarios que pueden ser autorizadores: [{ value, label }]
    users: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue", "created"]);

const $q = useQuasar();
const saving = ref(false);
const emptyCycle = () => ({ name: "", levels: [{ name: "", user_ids: [] }] });
const cycle = ref(emptyCycle());

async function HandleSave() {
    saving.value = true;
    try {
        const { data } = await axios.post(route("panel.authorization-cycle.quick-store"), cycle.value);
        emit("created", data);
        emit("update:modelValue", false);
        cycle.value = emptyCycle();
        $q.notify({ type: "positive", message: "Ciclo creado y seleccionado" });
    } catch (e) {
        const errors = e.response?.data?.errors;
        const message = errors
            ? Object.values(errors).flat().join(" ")
            : e.response?.data?.message ?? "Error al crear el ciclo";
        $q.notify({ type: "negative", message });
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
.level-box {
    border: 1px solid rgba(0, 0, 0, 0.12);
}
.body--dark .level-box {
    border-color: rgba(255, 255, 255, 0.15);
}
</style>
