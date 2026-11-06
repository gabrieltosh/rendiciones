<template>
    <q-card class="card-form q-mb-md">
        <q-card-section class="q-pb-sm">
            <div class="row items-center no-wrap">
                <div class="col">
                    <div class="text-subtitle1 text-weight-medium">Reparto por líneas</div>
                    <div class="text-caption text-grey">
                        Reparta el gasto entre varios centros de costo. Las líneas del asiento salen de
                        combinar los repartos de cada dimensión.
                    </div>
                </div>
                <div class="col-auto">
                    <q-toggle :model-value="open" color="primary" aria-label="Repartir el gasto en varias líneas"
                        @update:model-value="HandleToggle" />
                </div>
            </div>
            <div v-if="errors.splits" class="container-error">
                <ul class="message-error">
                    <li>{{ firstError(errors.splits) }}</li>
                </ul>
            </div>
        </q-card-section>

        <q-slide-transition>
            <div v-show="open">
                <q-separator />
                <q-card-section class="q-pa-lg">
                    <div class="row q-col-gutter-lg">
                        <div v-for="dim in DIMENSIONS" :key="dim" class="col-xs-12 col-md-6">
                            <div class="row items-center no-wrap q-mb-xs">
                                <div class="col form-label q-mb-none">Centro de Costo {{ dim }}</div>
                                <div class="col-auto">
                                    <q-btn v-if="!form.splits[dim]" flat dense no-caps size="12px" color="primary"
                                        icon="call_split" label="Repartir" @click="HandleStartSplit(dim)" />
                                    <q-btn v-else flat dense no-caps size="12px" color="negative" icon="close"
                                        label="Quitar reparto" @click="HandleRemoveSplit(dim)" />
                                </div>
                            </div>

                            <div v-if="!form.splits[dim]" class="text-caption text-grey">
                                Sin reparto: todas las líneas usan el valor de Datos Contables
                                <span v-if="singleCode(dim)">({{ codeLabel(dim, singleCode(dim)) }})</span>.
                            </div>

                            <template v-else>
                                <div v-for="(row, i) in form.splits[dim]" :key="i"
                                    class="row q-col-gutter-sm items-start q-mb-xs no-wrap">
                                    <div class="col" style="min-width: 0;">
                                        <q-select v-model="row.code" class="input-theme" dense outlined
                                            :options="filtered[dim]" option-value="OcrCode" option-label="Name"
                                            emit-value map-options use-input input-debounce="0"
                                            :aria-label="`Centro de costo ${dim}, línea ${i + 1}`"
                                            :error="!!errors[`splits.${dim}.${i}.code`]" hide-bottom-space
                                            @filter="(val, update) => HandleFilter(dim, val, update)" />
                                    </div>
                                    <div style="width: 110px;">
                                        <q-input v-model.number="row.percentage" class="input-theme" dense outlined
                                            type="number" min="0" max="100" step="0.01" suffix="%"
                                            :aria-label="`Porcentaje del centro de costo ${dim}, línea ${i + 1}`"
                                            :error="!!errors[`splits.${dim}.${i}.percentage`]" hide-bottom-space
                                            style="font-variant-numeric: tabular-nums" />
                                    </div>
                                    <div class="col-auto">
                                        <q-btn flat round dense icon="remove_circle_outline" color="negative"
                                            :disable="form.splits[dim].length === 1"
                                            :aria-label="`Quitar línea ${i + 1} del reparto`"
                                            @click="form.splits[dim].splice(i, 1)" />
                                    </div>
                                </div>
                                <div class="row items-center">
                                    <q-btn flat dense no-caps size="12px" color="primary" icon="add" label="Agregar"
                                        @click="form.splits[dim].push({ code: null, percentage: null })" />
                                    <q-space />
                                    <span class="text-caption"
                                        :class="sumOk(dim) ? 'text-positive' : 'text-negative'"
                                        style="font-variant-numeric: tabular-nums">
                                        Suma: {{ formatPercentage(sum(dim)) }} %
                                    </span>
                                </div>
                                <div v-if="errors[`splits.${dim}`]" class="container-error">
                                    <ul class="message-error">
                                        <li>{{ firstError(errors[`splits.${dim}`]) }}</li>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Líneas resultantes -->
                    <div v-if="combinations.length > 0" class="q-mt-lg">
                        <div class="row items-center q-mb-sm">
                            <div class="col text-subtitle2">Líneas del asiento</div>
                            <div class="col-auto text-caption"
                                :class="combinations.length > MAX_LINES ? 'text-negative' : 'text-grey'">
                                {{ combinations.length }} línea{{ combinations.length !== 1 ? 's' : '' }}
                                (máx. {{ MAX_LINES }})
                            </div>
                        </div>
                        <q-markup-table flat bordered dense separator="horizontal" class="table-theme">
                            <thead>
                                <tr class="table-header-theme">
                                    <th class="text-left">#</th>
                                    <th class="text-left">Centros de costo</th>
                                    <th class="text-right">%</th>
                                    <th class="text-left" style="min-width: 220px;">Cuenta</th>
                                    <th class="text-left" style="min-width: 180px;">Glosa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(combo, i) in combinations" :key="combo.combination">
                                    <td>{{ i + 1 }}</td>
                                    <td style="white-space: normal;">
                                        <div v-for="(code, dim) in combo.codes" :key="dim" class="text-caption">
                                            CC{{ dim }}: {{ codeLabel(dim, code) }}
                                        </div>
                                    </td>
                                    <td class="text-right" style="font-variant-numeric: tabular-nums">
                                        {{ formatPercentage(combo.percentage) }}
                                    </td>
                                    <td>
                                        <q-select v-if="lineFor(combo)" v-model="lineFor(combo).account"
                                            class="input-theme" dense outlined :options="filteredAccounts"
                                            option-value="key" option-label="label" emit-value map-options
                                            use-input input-debounce="0" clearable
                                            :placeholder="lineFor(combo).account ? '' : documentAccountLabel"
                                            :aria-label="`Cuenta de la línea ${i + 1}`"
                                            @filter="HandleFilterAccounts" />
                                    </td>
                                    <td>
                                        <q-input v-if="lineFor(combo)" v-model="lineFor(combo).concept"
                                            class="input-theme" dense outlined maxlength="50"
                                            :placeholder="form.concept || 'Glosa del documento'"
                                            :aria-label="`Glosa de la línea ${i + 1}`" />
                                    </td>
                                </tr>
                            </tbody>
                        </q-markup-table>
                        <div class="text-caption text-grey q-mt-xs">
                            Sin cuenta o glosa propia, la línea usa la cuenta (Concepto) y el comentario del
                            documento. El IVA y las retenciones se calculan sobre el total de la factura.
                        </div>
                    </div>
                </q-card-section>
            </div>
        </q-slide-transition>
    </q-card>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useQuasar } from "quasar";
import { firstError } from "@/utils/forms";

const props = defineProps({
    // Formulario del gasto; el reparto se edita en form.splits y form.lines
    form: { type: Object, required: true },
    // Opciones de cada dimensión: { 1: [...], ..., 5: [...] }
    distribution: { type: Object, required: true },
    // Cuentas (Concepto) disponibles: [{ key, label }]
    accounts: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const $q = useQuasar();

const DIMENSIONS = [1, 2, 3, 4, 5];
const MAX_LINES = 50;
const DIMENSION_FIELDS = {
    1: "distribution_rule_one",
    2: "distribution_rule_second",
    3: "distribution_rule_three",
    4: "distribution_rule_four",
    5: "distribution_rule_five",
};

if (!props.form.splits || Array.isArray(props.form.splits)) props.form.splits = {};
if (!Array.isArray(props.form.lines)) props.form.lines = [];

const open = ref(Object.keys(props.form.splits).length > 0);
const filtered = ref({ ...props.distribution });
const filteredAccounts = ref(props.accounts);

// Código del select de Datos Contables: texto (nuevo gasto) u opción completa (edición)
function singleCode(dim) {
    const value = props.form[DIMENSION_FIELDS[dim]];
    if (value && typeof value === "object") return value.OcrCode || value.PrcCode || null;
    return value || null;
}

function codeLabel(dim, code) {
    const option = (props.distribution[dim] || []).find((o) => o.OcrCode === code);
    return option ? option.Name : code;
}

const documentAccountLabel = computed(() => {
    const account = props.accounts.find((a) => a.key === props.form.account);
    return account ? `Cuenta del documento: ${account.label}` : "Cuenta del documento";
});

function sum(dim) {
    return (props.form.splits[dim] || []).reduce((total, row) => total + (Number(row.percentage) || 0), 0);
}

function sumOk(dim) {
    return Math.abs(sum(dim) - 100) <= 0.01;
}

function formatPercentage(value) {
    return Number(value.toFixed(4)).toLocaleString("es-BO", { maximumFractionDigits: 4 });
}

// Misma combinación que ExpenseDistribution::combinations en el backend
const combinations = computed(() => {
    let list = [{ codes: {}, percentage: 100 }];
    let split = false;
    DIMENSIONS.forEach((dim) => {
        const rows = (props.form.splits[dim] || []).filter((row) => row.code);
        if (rows.length === 0) return;
        split = true;
        const next = [];
        list.forEach((combo) =>
            rows.forEach((row) =>
                next.push({
                    codes: { ...combo.codes, [dim]: row.code },
                    percentage: (combo.percentage * (Number(row.percentage) || 0)) / 100,
                })
            )
        );
        list = next;
    });
    if (!split) return [];
    return list.map((combo) => ({
        ...combo,
        combination: Object.keys(combo.codes)
            .sort()
            .map((dim) => `${dim}:${combo.codes[dim]}`)
            .join("|"),
    }));
});

// Al cambiar el reparto se regeneran las líneas, conservando cuenta y glosa de las
// combinaciones que siguen existiendo
watch(
    () => combinations.value.map((combo) => combo.combination).join(","),
    () => {
        const previous = Object.fromEntries((props.form.lines || []).map((line) => [line.combination, line]));
        props.form.lines = combinations.value.map(
            (combo) => previous[combo.combination] ?? { combination: combo.combination, account: null, concept: null }
        );
    },
    { immediate: true }
);

function lineFor(combo) {
    return props.form.lines.find((line) => line.combination === combo.combination);
}

function HandleStartSplit(dim) {
    props.form.splits[dim] = [
        { code: singleCode(dim), percentage: 50 },
        { code: null, percentage: 50 },
    ];
}

function HandleRemoveSplit(dim) {
    delete props.form.splits[dim];
}

function HandleToggle(value) {
    if (value) {
        open.value = true;
        return;
    }
    if (Object.keys(props.form.splits).length === 0) {
        open.value = false;
        return;
    }
    $q.dialog({
        title: "Quitar reparto",
        message: "Se eliminará el reparto y el gasto volverá a exportarse en una sola línea. ¿Continuar?",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Quitar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        props.form.splits = {};
        open.value = false;
    });
}

function HandleFilter(dim, val, update) {
    update(() => {
        const needle = val.toLowerCase();
        filtered.value[dim] = (props.distribution[dim] || []).filter(
            (option) => !needle || option.Name.toLowerCase().includes(needle)
        );
    });
}

function HandleFilterAccounts(val, update) {
    update(() => {
        const needle = val.toLowerCase();
        filteredAccounts.value = props.accounts.filter(
            (account) => !needle || account.label.toLowerCase().includes(needle)
        );
    });
}
</script>
