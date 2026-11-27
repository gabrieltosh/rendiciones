<template>
    <q-card class="q-pa-lg card-form">
        <div class="text-subtitle1 text-weight-medium q-mb-md">Datos de la rendición</div>
        <div class="row q-col-gutter-md">
            <div data-tour="field-employee" v-if="!profile.sin_empleado" class="col-xs-12 col-md-6">
                <FormField label="Empleado" required :error="errors.employee">
                    <q-select class="input-theme" dense outlined :options="employeeOptions" v-model="form.employee"
                        option-value="card_code" option-label="card_name" emit-value map-options use-input
                        input-debounce="0" clearable aria-label="Empleado" @filter="HandleFilterEmployee">
                        <template v-slot:no-option>
                            <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                        </template>
                    </q-select>
                </FormField>
            </div>

            <div data-tour="field-account" class="col-xs-12 col-md-6">
                <FormField label="Cuenta de la rendición" required :error="errors.account"
                    hint="Cuenta contable del dinero recibido (fondo o anticipo)">
                    <q-select class="input-theme" dense outlined :options="accountOptions" v-model="form.account"
                        option-value="key" option-label="label" emit-value map-options use-input input-debounce="0"
                        clearable aria-label="Cuenta de la rendición" @filter="HandleFilterAccounts">
                        <template v-slot:no-option>
                            <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                        </template>
                    </q-select>
                </FormField>
            </div>

            <div data-tour="field-total" class="col-xs-12 col-md-6">
                <FormField label="Monto recibido" required :error="errors.total"
                    hint="Dinero que le entregaron para esta rendición">
                    <q-input :model-value="form.total" dense outlined type="text" inputmode="decimal"
                        class="input-theme tabular" autocomplete="off" :suffix="symbol" aria-label="Monto recibido"
                        @update:model-value="(v) => (form.total = normalizeDecimal(v))" />
                </FormField>
            </div>

            <div data-tour="field-description" class="col-xs-12 col-md-6">
                <FormField label="Descripción" required :error="errors.description"
                    hint="Por ejemplo: viaje a Santa Cruz, caja chica de marzo">
                    <q-input v-model="form.description" dense outlined class="input-theme" maxlength="250"
                        autocomplete="off" aria-label="Descripción de la rendición" />
                </FormField>
            </div>

            <div class="col-xs-12 col-sm-6">
                <FormField label="Fecha de inicio" required :error="errors.start_date">
                    <q-input v-model="form.start_date" dense outlined class="input-theme" type="date"
                        aria-label="Fecha de inicio" />
                </FormField>
            </div>

            <div data-tour="field-period" class="col-xs-12 col-sm-6">
                <FormField label="Fecha final" required :error="errors.end_date"
                    hint="Los documentos deben tener fecha dentro de este periodo">
                    <q-input v-model="form.end_date" dense outlined type="date" class="input-theme"
                        :min="form.start_date || undefined" aria-label="Fecha final" />
                </FormField>
            </div>
        </div>
    </q-card>
</template>

<script setup>
import { ref, computed } from "vue";
import FormField from "@/components/FormField.vue";
import { currencySymbol } from "@/utils/accountability";
import { normalizeDecimal } from "./siat";

const props = defineProps({
    // Formulario de la rendición (se edita en el lugar)
    form: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    profile: { type: Object, required: true },
    accounts: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
});

const symbol = computed(() => currencySymbol(props.profile?.type_currency));
const accountOptions = ref(props.accounts);
const employeeOptions = ref(props.employees);

function HandleFilterAccounts(val, update) {
    update(() => {
        const needle = val.toLowerCase();
        accountOptions.value = props.accounts.filter((a) => !needle || a.label.toLowerCase().includes(needle));
    });
}

function HandleFilterEmployee(val, update) {
    update(() => {
        const needle = val.toLowerCase();
        employeeOptions.value = (props.employees || []).filter(
            (e) => !needle || String(e.card_name ?? "").toLowerCase().includes(needle)
        );
    });
}
</script>

<style scoped>
.tabular :deep(input) {
    font-variant-numeric: tabular-nums;
}
</style>
