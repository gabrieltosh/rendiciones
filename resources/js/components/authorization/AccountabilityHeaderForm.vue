<template>
    <div class="row q-col-gutter-md">
        <div data-tour="field-employee" v-if="!profile?.sin_empleado" class="col-xs-12 col-sm-6">
            <FormField label="Empleado" required :error="errors.employee"
                hint="Escriba al menos 2 letras para buscar en SAP">
                <q-select
                    class="input-theme"
                    dense
                    outlined
                    :options="filteredEmployees"
                    :model-value="modelValue.employee"
                    option-value="card_code"
                    :option-label="employeeLabel"
                    emit-value
                    map-options
                    use-input
                    input-debounce="300"
                    clearable
                    :loading="searching"
                    aria-label="Empleado"
                    @filter="HandleFilterEmployees"
                    @update:model-value="HandleSelectEmployee"
                >
                    <template #no-option>
                        <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                    </template>
                </q-select>
            </FormField>
        </div>
        <div data-tour="field-account" class="col-xs-12 col-sm-6">
            <FormField label="Cuenta" required :error="errors.account">
                <q-select
                    class="input-theme"
                    dense
                    outlined
                    :options="filteredAccounts"
                    :model-value="modelValue.account"
                    option-value="key"
                    option-label="label"
                    emit-value
                    map-options
                    use-input
                    input-debounce="0"
                    clearable
                    aria-label="Cuenta"
                    @filter="HandleFilterAccounts"
                    @update:model-value="(v) => HandleSet('account', v)"
                />
            </FormField>
        </div>
        <div data-tour="field-total" class="col-xs-12 col-sm-6">
            <FormField label="Monto recibido" required :error="errors.total">
                <q-input
                    :model-value="modelValue.total"
                    dense
                    outlined
                    type="number"
                    step="0.01"
                    class="input-theme"
                    aria-label="Monto recibido"
                    @update:model-value="(v) => HandleSet('total', v)"
                >
                    <template #prepend>
                        <span class="text-caption text-grey">{{ currencySymbol(profile?.type_currency) }}</span>
                    </template>
                </q-input>
            </FormField>
        </div>
        <div class="col-xs-12 col-sm-6">
            <FormField label="Descripción" required :error="errors.description">
                <q-input
                    :model-value="modelValue.description"
                    dense
                    outlined
                    class="input-theme"
                    aria-label="Descripción"
                    @update:model-value="(v) => HandleSet('description', v)"
                />
            </FormField>
        </div>
        <div class="col-xs-12 col-sm-6">
            <FormField label="Fecha inicio" required :error="errors.start_date">
                <q-input
                    :model-value="modelValue.start_date"
                    dense
                    outlined
                    type="date"
                    class="input-theme"
                    aria-label="Fecha inicio"
                    @update:model-value="(v) => HandleSet('start_date', v)"
                />
            </FormField>
        </div>
        <div data-tour="field-period" class="col-xs-12 col-sm-6">
            <FormField label="Fecha final" required :error="errors.end_date">
                <q-input
                    :model-value="modelValue.end_date"
                    dense
                    outlined
                    type="date"
                    class="input-theme"
                    aria-label="Fecha final"
                    @update:model-value="(v) => HandleSet('end_date', v)"
                />
            </FormField>
        </div>
    </div>
</template>

<script setup>
// Formulario de cabecera de una rendición para el autorizador (empleado, cuenta,
// monto, descripción y fechas). Lo usan la página de edición y el modal de
// Pendientes de Exportación. El empleado se busca en SAP al escribir.
import FormField from "@/components/FormField.vue";
import { ref, watch } from "vue";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { currencySymbol } from "@/utils/accountability";

const props = defineProps({
    modelValue: { type: Object, required: true },
    profile: { type: Object, default: null },
    accounts: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(["update:modelValue"]);

const $q = useQuasar();
const knownEmployees = ref([...props.employees]);
const filteredEmployees = ref([...props.employees]);
const filteredAccounts = ref([...props.accounts]);
const searching = ref(false);

watch(() => props.employees, (list) => {
    knownEmployees.value = [...list];
    filteredEmployees.value = [...list];
});
watch(() => props.accounts, (list) => (filteredAccounts.value = [...list]));

function employeeLabel(e) {
    if (!e) return "";
    if (typeof e !== "object") return e;
    return e.card_name ? `${e.card_code} - ${e.card_name}` : e.card_code;
}

function HandleSet(key, value) {
    emit("update:modelValue", { ...props.modelValue, [key]: value });
}

// Un empleado elegido de la búsqueda en SAP se conserva para que se siga mostrando su nombre
function HandleSelectEmployee(code) {
    if (code && !knownEmployees.value.some((e) => e.card_code === code)) {
        const found = filteredEmployees.value.find((e) => e.card_code === code);
        if (found) knownEmployees.value = [found, ...knownEmployees.value];
    }
    HandleSet("employee", code);
}

function HandleFilterAccounts(val, update) {
    const needle = (val || "").toLowerCase();
    update(() => {
        filteredAccounts.value = !needle
            ? props.accounts
            : props.accounts.filter((v) => String(v.label).toLowerCase().includes(needle));
    });
}

// Empleados del perfil (o el del usuario) y, desde 2 letras, socios de negocio de SAP
async function HandleFilterEmployees(val, update) {
    const needle = (val || "").trim().toLowerCase();
    const local = knownEmployees.value.filter(
        (v) => !needle || `${v.card_code} ${v.card_name ?? ""}`.toLowerCase().includes(needle)
    );
    if (needle.length < 2) {
        update(() => (filteredEmployees.value = local));
        return;
    }
    searching.value = true;
    try {
        const res = await fetch(
            route("panel.accountability.authorization.employees", { search: val.trim() }),
            { headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" }, credentials: "same-origin" }
        );
        if (!res.ok) throw new Error(res.statusText);
        const remote = await res.json();
        const codes = new Set(local.map((e) => e.card_code));
        update(() => {
            filteredEmployees.value = [...local, ...remote.filter((e) => !codes.has(e.card_code))];
        });
    } catch {
        update(() => (filteredEmployees.value = local));
        $q.notify({ type: "negative", message: "No se pudo buscar empleados en SAP." });
    } finally {
        searching.value = false;
    }
}
</script>
