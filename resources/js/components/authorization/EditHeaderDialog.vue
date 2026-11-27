<template>
    <q-dialog :model-value="modelValue" persistent :maximized="$q.screen.xs" @update:model-value="emit('update:modelValue', $event)">
        <q-card style="width: 900px; max-width: 95vw;">
            <q-card-section class="row items-center q-pb-none">
                <div>
                    <div class="text-h6 title-form">Editar encabezado</div>
                    <div class="text-caption text-grey">Rendición #{{ accountabilityId }}</div>
                </div>
                <q-space />
                <q-btn icon="close" flat round dense v-close-popup aria-label="Cerrar" :disable="saving" />
            </q-card-section>

            <q-card-section v-if="loading" class="text-center q-py-xl">
                <q-spinner size="40px" color="primary" />
            </q-card-section>

            <q-card-section v-else-if="data">
                <AccountabilityHeaderForm
                    v-model="form"
                    :profile="data.profile"
                    :accounts="data.accounts"
                    :employees="data.employees"
                    :errors="errors"
                />
            </q-card-section>

            <q-card-actions align="right" class="q-pa-md">
                <q-btn flat label="Cancelar" color="secondary" v-close-popup no-caps :disable="saving" />
                <q-btn label="Guardar cambios" color="primary" no-caps unelevated
                    :loading="saving" :disable="saving || !data" @click="HandleSave" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

<script setup>
// Edición de la cabecera de una rendición (empleado, cuenta, monto, descripción y
// periodo) en un diálogo, sin salir de la pantalla. `from` indica a dónde vuelve el
// servidor al guardar: 'detail' (detalle de la rendición) o 'pending-export'.
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import axios from "axios";
import AccountabilityHeaderForm from "@/components/authorization/AccountabilityHeaderForm.vue";

const props = defineProps({
    modelValue: Boolean,
    accountabilityId: { type: [Number, String], default: null },
    from: { type: String, default: "detail" },
});
const emit = defineEmits(["update:modelValue", "saved"]);

const $q = useQuasar();
const page = usePage();
const loading = ref(false);
const saving = ref(false);
const data = ref(null);
const form = ref({});
const errors = ref({});

watch(
    () => [props.modelValue, props.accountabilityId],
    ([open, id]) => {
        if (open && id) HandleLoad(id);
    },
    { immediate: true }
);

async function HandleLoad(id) {
    data.value = null;
    errors.value = {};
    loading.value = true;
    try {
        const res = await axios.get(route("panel.accountability.authorization.edit-data", id), {
            headers: { Accept: "application/json" },
        });
        data.value = res.data;
        form.value = { ...res.data.accountability, from: props.from };
    } catch {
        $q.notify({ type: "negative", message: "No se pudieron cargar los datos de la rendición." });
        emit("update:modelValue", false);
    } finally {
        loading.value = false;
    }
}

function HandleSave() {
    if (saving.value) return;
    errors.value = {};
    router.put(route("panel.accountability.authorization.update"), form.value, {
        preserveScroll: true,
        onStart: () => (saving.value = true),
        onSuccess: () => {
            // Si el servidor rechazó el cambio (p. ej. ya exportada) el aviso lo muestra el layout
            if (page.props.flash?.type !== "negative") {
                emit("update:modelValue", false);
                emit("saved");
            }
        },
        onError: (e) => (errors.value = e),
        onFinish: () => (saving.value = false),
    });
}
</script>
