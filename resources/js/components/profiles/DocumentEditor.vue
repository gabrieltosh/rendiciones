<template>
    <q-card flat bordered class="doc-card" :class="{ 'doc-card--deleted': deleted, 'doc-card--error': hasErrors }">
        <!-- Cabecera del documento -->
        <q-card-section class="row items-center no-wrap q-py-sm cursor-pointer" @click="expanded = !expanded">
            <q-avatar size="30px" :color="deleted ? 'grey-5' : 'primary'" text-color="white"
                class="text-caption text-weight-bold">
                {{ index + 1 }}
            </q-avatar>
            <div class="col q-ml-md" style="min-width: 0;">
                <div class="text-weight-medium ellipsis" :class="{ 'text-strike': deleted }">
                    {{ document.name || `Documento ${index + 1}` }}
                </div>
                <div class="text-caption text-grey ellipsis">
                    {{ documentTypeLabel || 'Sin tipo de documento SAP' }}
                    · {{ activeLines(document.detail).length }} línea(s) de prorrateo
                </div>
            </div>
            <q-badge v-if="hasErrors" color="negative" label="Revisar" class="q-mr-sm" />
            <template v-if="deleted">
                <q-badge color="grey-6" label="Se eliminará al guardar" class="q-mr-sm" />
                <q-btn flat dense no-caps size="sm" color="primary" icon="undo" label="Deshacer"
                    @click.stop="document.for_delete = 0" />
            </template>
            <q-btn v-else flat round dense size="sm" color="negative" icon="eva-trash-2-outline"
                aria-label="Eliminar documento" @click.stop="emit('remove')">
                <q-tooltip>Eliminar documento</q-tooltip>
            </q-btn>
            <q-btn flat round dense size="sm" :icon="expanded ? 'expand_less' : 'expand_more'"
                :aria-label="expanded ? 'Contraer documento' : 'Expandir documento'" @click.stop="expanded = !expanded" />
        </q-card-section>

        <q-slide-transition>
            <div v-show="expanded && !deleted">
                <q-separator />
                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-xs-12 col-md-6">
                            <FormField label="Nombre del documento" required :error="errorFor('name')">
                                <q-input v-model="document.name" dense outlined class="input-theme"
                                    placeholder="Ej: Factura, Recibo, Planilla de viáticos" />
                            </FormField>
                        </div>
                        <div class="col-xs-12 col-md-6">
                            <FormField label="Tipo de documento SAP" required :error="errorFor('type_document_sap')">
                                <q-select v-model="document.type_document_sap" :options="options.document_types"
                                    option-value="FldValue" option-label="Descr" emit-value map-options clearable
                                    dense outlined class="input-theme" />
                            </FormField>
                        </div>
                    </div>
                </q-card-section>

                <!-- Datos que se piden al registrar el gasto -->
                <q-card-section class="q-pt-none">
                    <div class="text-subtitle2">Datos de la factura</div>
                    <div class="form-section-caption q-mb-sm">
                        Marca los campos que el usuario deberá completar al registrar este documento.
                    </div>
                    <div class="row q-col-gutter-xs">
                        <div v-for="flag in invoiceFlags" :key="flag.field" class="col-xs-6 col-sm-4 col-md-3">
                            <q-checkbox v-model="document[flag.field]" :label="flag.label" dense color="primary"
                                class="flag-check" />
                        </div>
                    </div>
                </q-card-section>

                <q-card-section class="q-pt-none">
                    <div class="text-subtitle2">Impuestos y montos variables</div>
                    <div class="form-section-caption q-mb-sm">
                        Si un monto es variable, el usuario lo ingresa en cada gasto; si no, se usa el valor fijo.
                    </div>
                    <div class="row q-col-gutter-md items-start">
                        <div class="col-xs-12 col-sm-4">
                            <q-checkbox v-model="document.exento_status" label="Exento variable" dense color="primary" />
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <q-checkbox v-model="document.tasas_status" label="Tasa variable" dense color="primary" />
                            <FormField v-if="!document.tasas_status" label="Tasa fija" class="q-mt-sm">
                                <q-input v-model="document.tasas" type="number" dense outlined class="input-theme"
                                    input-style="font-variant-numeric: tabular-nums" />
                            </FormField>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <q-checkbox v-model="document.ice_status" label="ICE variable" dense color="primary" />
                            <FormField v-if="!document.ice_status" label="ICE fijo" class="q-mt-sm">
                                <q-input v-model="document.ice" type="number" dense outlined class="input-theme"
                                    input-style="font-variant-numeric: tabular-nums" />
                            </FormField>
                        </div>
                    </div>
                </q-card-section>

                <!-- Prorrateo del asiento -->
                <q-card-section class="q-pt-none">
                    <div class="row items-center q-mb-sm">
                        <div class="col">
                            <div class="text-subtitle2">Prorrateo del asiento</div>
                            <div class="form-section-caption">Cómo se distribuye el monto del documento en cuentas contables.</div>
                        </div>
                        <q-btn outline dense no-caps size="sm" color="primary" icon="add" label="Agregar línea"
                            class="q-px-sm" @click="AddDetailLine()" />
                    </div>
                    <q-markup-table flat bordered dense separator="horizontal" class="lines-table">
                        <thead>
                            <tr>
                                <th class="text-left" style="width: 22%">Tipo <span class="text-negative">*</span></th>
                                <th class="text-left" style="width: 18%">Cálculo <span class="text-negative">*</span></th>
                                <th class="text-left" style="width: 30%">Cuenta <span class="text-negative">*</span></th>
                                <th class="text-left" style="width: 13%">Porcentaje <span class="text-negative">*</span></th>
                                <th class="text-left" style="width: 12%">% Exento</th>
                                <th style="width: 5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(line, li) in document.detail" :key="li"
                                :class="{ 'line--deleted': line.for_delete == 1 }">
                                <td>
                                    <FormField :error="errorFor(`detail.${li}.type`)">
                                        <q-select v-model="line.type" :options="options.type" dense outlined
                                            class="input-theme" aria-label="Tipo"
                                            :disable="line.for_delete == 1" @update:model-value="OnLineTypeChange(line)" />
                                    </FormField>
                                </td>
                                <td>
                                    <FormField :error="errorFor(`detail.${li}.type_calculation`)">
                                        <q-select v-model="line.type_calculation" :options="['Grossing Up', 'Grossing Down']"
                                            dense outlined clearable class="input-theme" aria-label="Cálculo"
                                            :disable="line.for_delete == 1" />
                                    </FormField>
                                </td>
                                <td>
                                    <FormField :error="errorFor(`detail.${li}.account`)">
                                        <q-select v-model="line.account" :options="filteredAccounts"
                                            option-value="AcctCode" option-label="AcctName" emit-value map-options
                                            use-input input-debounce="0" clearable dense outlined class="input-theme"
                                            aria-label="Cuenta" @filter="FilterAccounts"
                                            :disable="isTaxLine(line) || line.for_delete == 1" />
                                    </FormField>
                                </td>
                                <td>
                                    <FormField :error="errorFor(`detail.${li}.percentage`)">
                                        <q-input v-model="line.percentage" type="number" dense outlined class="input-theme"
                                            suffix="%" aria-label="Porcentaje" :disable="line.for_delete == 1"
                                            input-style="font-variant-numeric: tabular-nums" />
                                    </FormField>
                                </td>
                                <td>
                                    <q-input v-model="line.exento" type="number" dense outlined class="input-theme"
                                        suffix="%" aria-label="Porcentaje exento"
                                        :disable="document.exento_status || line.for_delete == 1"
                                        input-style="font-variant-numeric: tabular-nums" />
                                </td>
                                <td class="text-center">
                                    <q-btn v-if="line.for_delete == 1" flat round dense size="sm" icon="undo"
                                        color="primary" aria-label="Restaurar línea" @click="line.for_delete = 0">
                                        <q-tooltip>Restaurar</q-tooltip>
                                    </q-btn>
                                    <q-btn v-else flat round dense size="sm" icon="eva-trash-2-outline" color="negative"
                                        aria-label="Eliminar línea" @click="RemoveLine(document.detail, li)">
                                        <q-tooltip>Eliminar línea</q-tooltip>
                                    </q-btn>
                                </td>
                            </tr>
                            <tr v-if="document.detail.length === 0">
                                <td colspan="6" class="text-center text-grey text-caption q-pa-md">
                                    Sin líneas de prorrateo. Agrega al menos una.
                                </td>
                            </tr>
                        </tbody>
                    </q-markup-table>
                </q-card-section>

                <!-- Campos adicionales -->
                <q-card-section class="q-pt-none">
                    <div class="row items-center q-mb-sm">
                        <div class="col">
                            <div class="text-subtitle2">Campos adicionales</div>
                            <div class="form-section-caption">Montos extra que el usuario puede ingresar, con su cuenta contable.</div>
                        </div>
                        <q-btn outline dense no-caps size="sm" color="primary" icon="add" label="Agregar campo"
                            class="q-px-sm" @click="AddFieldLine()" />
                    </div>
                    <q-markup-table flat bordered dense separator="horizontal" class="lines-table">
                        <thead>
                            <tr>
                                <th class="text-left" style="width: 22%">Tipo <span class="text-negative">*</span></th>
                                <th class="text-left" style="width: 35%">Nombre <span class="text-negative">*</span></th>
                                <th class="text-left" style="width: 38%">Cuenta <span class="text-negative">*</span></th>
                                <th style="width: 5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(field, fi) in document.fields" :key="fi"
                                :class="{ 'line--deleted': field.for_delete == 1 }">
                                <td>
                                    <FormField :error="errorFor(`fields.${fi}.type_calculation`)">
                                        <q-select v-model="field.type_calculation" :options="['Debito', 'Credito']" dense
                                            outlined clearable class="input-theme" aria-label="Tipo"
                                            :disable="field.for_delete == 1" />
                                    </FormField>
                                </td>
                                <td>
                                    <FormField :error="errorFor(`fields.${fi}.name`)">
                                        <q-input v-model="field.name" dense outlined class="input-theme"
                                            aria-label="Nombre" :disable="field.for_delete == 1" />
                                    </FormField>
                                </td>
                                <td>
                                    <FormField :error="errorFor(`fields.${fi}.account`)">
                                        <q-select v-model="field.account" :options="filteredAccounts"
                                            option-value="AcctCode" option-label="AcctName" emit-value map-options
                                            use-input input-debounce="0" clearable dense outlined class="input-theme"
                                            aria-label="Cuenta" :disable="field.for_delete == 1"
                                            @filter="FilterAccounts" />
                                    </FormField>
                                </td>
                                <td class="text-center">
                                    <q-btn v-if="field.for_delete == 1" flat round dense size="sm" icon="undo"
                                        color="primary" aria-label="Restaurar campo" @click="field.for_delete = 0">
                                        <q-tooltip>Restaurar</q-tooltip>
                                    </q-btn>
                                    <q-btn v-else flat round dense size="sm" icon="eva-trash-2-outline" color="negative"
                                        aria-label="Eliminar campo" @click="RemoveLine(document.fields, fi)">
                                        <q-tooltip>Eliminar campo</q-tooltip>
                                    </q-btn>
                                </td>
                            </tr>
                            <tr v-if="document.fields.length === 0">
                                <td colspan="4" class="text-center text-grey text-caption q-pa-md">
                                    Sin campos adicionales.
                                </td>
                            </tr>
                        </tbody>
                    </q-markup-table>
                </q-card-section>
            </div>
        </q-slide-transition>
    </q-card>
</template>

<script setup>
import { ref, computed } from "vue";
import FormField from "@/components/FormField.vue";
import { emptyDetailLine, emptyFieldLine } from "@/utils/profiles";

const props = defineProps({
    // Documento del perfil; se edita en el mismo objeto del formulario padre
    document: { type: Object, required: true },
    index: { type: Number, required: true },
    errors: { type: Object, default: () => ({}) },
    // { type, document_types, accounts_document }
    options: { type: Object, required: true },
});
const emit = defineEmits(["remove"]);

const invoiceFlags = [
    { field: "authorization_number_status", label: "Nº Autorización" },
    { field: "cuf_status", label: "CUF" },
    { field: "control_code_status", label: "Código de control" },
    { field: "business_name_status", label: "Razón social" },
    { field: "nit_status", label: "NIT" },
    { field: "discount_status", label: "Descuento" },
    { field: "gift_card_status", label: "Gift Card" },
    { field: "rate_zero_status", label: "Tasa cero" },
];

// Tipos de línea que no llevan cuenta propia
const TAX_TYPES = ["EXENTO", "TASA", "ICE"];

const expanded = ref(true);
const filteredAccounts = ref(props.options.accounts_document ?? []);

const deleted = computed(() => props.document.for_delete == 1);
const prefix = computed(() => `documents.${props.index}.`);
const hasErrors = computed(() => Object.keys(props.errors || {}).some((key) => key.startsWith(prefix.value)));
const documentTypeLabel = computed(
    () => props.options.document_types?.find((t) => t.FldValue === props.document.type_document_sap)?.Descr
);

function errorFor(path) {
    return props.errors?.[prefix.value + path];
}

function activeLines(lines) {
    return (lines || []).filter((line) => line.for_delete != 1);
}

function isTaxLine(line) {
    return TAX_TYPES.includes(line.type);
}

function OnLineTypeChange(line) {
    line.account = isTaxLine(line) ? "-" : null;
}

function FilterAccounts(val, update) {
    update(() => {
        const needle = val.toLowerCase();
        filteredAccounts.value = needle
            ? props.options.accounts_document.filter((a) => a.AcctName?.toLowerCase().includes(needle))
            : props.options.accounts_document;
    });
}

function AddDetailLine() {
    props.document.detail.push(emptyDetailLine());
}

function AddFieldLine() {
    props.document.fields.push(emptyFieldLine());
}

// Las líneas ya guardadas se marcan para eliminarse al guardar; las nuevas se quitan directamente
function RemoveLine(lines, i) {
    if (lines[i].id) {
        lines[i].for_delete = 1;
    } else {
        lines.splice(i, 1);
    }
}
</script>

<style scoped>
.doc-card {
    border-radius: 0.75rem;
    overflow: hidden;
}
.doc-card--error {
    border-color: var(--q-negative);
}
.doc-card--deleted {
    opacity: 0.7;
}
.lines-table td {
    vertical-align: top;
    padding-top: 6px;
    padding-bottom: 6px;
}
.lines-table th {
    font-weight: 600;
    font-size: 11.5px;
}
.line--deleted td {
    opacity: 0.45;
    text-decoration: line-through;
}
.flag-check {
    font-size: 12.5px;
}
</style>
