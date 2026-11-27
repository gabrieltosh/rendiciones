<template>
    <q-dialog :model-value="modelValue" persistent :maximized="$q.screen.xs"
        @update:model-value="emit('update:modelValue', $event)">
        <q-card style="width: 900px; max-width: 95vw;">
            <q-card-section class="row items-center q-pb-none">
                <div>
                    <div class="text-h6 title-form">{{ isEdit ? "Editar datos de la rendición" : "Nueva rendición" }}</div>
                    <div class="text-caption text-grey">
                        <template v-if="isEdit">Rendición #{{ accountabilityId }}</template>
                        <template v-else-if="data">Perfil: {{ data.profile?.name }}</template>
                    </div>
                </div>
                <q-space />
                <q-btn icon="close" flat round dense aria-label="Cerrar" :disable="saving" @click="HandleClose" />
            </q-card-section>

            <q-card-section v-if="loading" class="text-center q-py-xl">
                <q-spinner size="40px" color="primary" />
            </q-card-section>

            <q-card-section v-else-if="data">
                <div v-if="!isEdit" class="text-caption text-grey q-mb-md">
                    Registre el dinero que recibió y el periodo. Al crearla pasará al detalle para agregar sus gastos.
                </div>
                <AccountabilityFormFields data-tour="accountability-form" embedded :form="form" :errors="errors"
                    :profile="data.profile" :accounts="data.accounts" :employees="data.employees || []" />
            </q-card-section>

            <q-card-actions align="right" class="q-pa-md">
                <q-btn flat label="Cancelar" color="secondary" no-caps :disable="saving" @click="HandleClose" />
                <q-btn data-tour="save-accountability" color="primary" no-caps unelevated icon="eva-checkmark-outline"
                    :label="isEdit ? 'Guardar cambios' : 'Crear y agregar gastos'" :loading="saving"
                    :disable="saving || !data" @click="HandleSave" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

<script setup>
// Crea o edita la cabecera de una rendición del solicitante (empleado, cuenta, monto,
// descripción y periodo) en un diálogo, sin salir de la pantalla actual.
// - Sin accountabilityId: crea la rendición; el servidor lleva al detalle para cargar gastos.
// - Con accountabilityId: la actualiza; `returnTo` = 'detail' vuelve al detalle, 'index' a la lista.
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import axios from "axios";
import AccountabilityFormFields from "@/components/accountability/AccountabilityFormFields.vue";

const props = defineProps({
    modelValue: Boolean,
    profileId: { type: [Number, String], required: true },
    accountabilityId: { type: [Number, String], default: null },
    returnTo: { type: String, default: "detail" },
});
const emit = defineEmits(["update:modelValue", "saved"]);

const $q = useQuasar();
const page = usePage();
const isEdit = computed(() => !!props.accountabilityId);
const loading = ref(false);
const saving = ref(false);
const data = ref(null);
const form = ref({});
const errors = ref({});
let snapshot = null;

const EMPTY_FORM = {
    employee: null,
    account: null,
    total: null,
    description: null,
    start_date: null,
    end_date: null,
};

watch(
    () => [props.modelValue, props.accountabilityId],
    ([open]) => {
        if (open) HandleLoad();
    },
    { immediate: true }
);

async function HandleLoad() {
    data.value = null;
    errors.value = {};
    loading.value = true;
    const url = isEdit.value
        ? route("panel.accountability.manage.edit", [props.profileId, props.accountabilityId])
        : route("panel.accountability.manage.create", props.profileId);
    try {
        const res = await axios.get(url, { headers: { Accept: "application/json" } });
        data.value = res.data;
        form.value = isEdit.value ? { ...res.data.accountability } : { ...EMPTY_FORM };
        snapshot = JSON.stringify(form.value);
    } catch (error) {
        $q.notify({
            type: "negative",
            message: error.response?.data?.message || "No se pudieron cargar los datos de la rendición.",
        });
        emit("update:modelValue", false);
    } finally {
        loading.value = false;
    }
}

// Cerrar: si hay cambios sin guardar, se pide confirmación
function HandleClose() {
    if (saving.value) return;
    if (!data.value || JSON.stringify(form.value) === snapshot) {
        emit("update:modelValue", false);
        return;
    }
    $q.dialog({
        title: "Cambios sin guardar",
        message: "Tiene cambios sin guardar. Si cierra ahora, se perderán. ¿Desea cerrar de todas formas?",
        cancel: { label: "Seguir editando", flat: true, noCaps: true },
        ok: { label: "Cerrar sin guardar", color: "negative", noCaps: true, unelevated: true },
        persistent: true,
    }).onOk(() => emit("update:modelValue", false));
}

function HandleSave() {
    if (saving.value) return;
    errors.value = {};
    const options = {
        preserveScroll: true,
        preserveState: true,
        onStart: () => (saving.value = true),
        onSuccess: () => {
            // Si el servidor rechazó el cambio (p. ej. ya enviada) el aviso lo muestra el layout
            if (page.props.flash?.type !== "negative") {
                emit("update:modelValue", false);
                emit("saved");
            }
        },
        onError: (e) => (errors.value = e),
        onFinish: () => (saving.value = false),
    };
    if (isEdit.value) {
        router.put(
            route("panel.accountability.manage.update", props.profileId),
            { ...form.value, return_to: props.returnTo },
            options
        );
    } else {
        router.post(route("panel.accountability.manage.store", props.profileId), form.value, options);
    }
}
</script>
