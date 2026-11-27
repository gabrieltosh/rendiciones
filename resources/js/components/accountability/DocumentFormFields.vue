<template>
    <!-- Información general -->
    <q-card data-tour="expense-general" class="q-pa-lg card-form q-mb-md">
        <div class="text-subtitle1 text-weight-medium q-mb-md">Información general</div>
        <div class="row q-col-gutter-md">
            <div data-tour="field-expense-account" class="col-xs-12 col-sm-6">
                <FormField label="Tipo de gasto (cuenta)" required :error="errors.account"
                    hint="Elija en qué se gastó: pasajes, alimentación, hospedaje…">
                    <q-select class="input-theme" dense outlined :options="accountOptions" v-model="form.account"
                        option-value="key" option-label="label" emit-value map-options use-input input-debounce="0"
                        clearable aria-label="Tipo de gasto (cuenta)" @filter="HandleFilterAccounts">
                        <template v-slot:no-option>
                            <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                        </template>
                    </q-select>
                </FormField>
            </div>
            <div data-tour="field-expense-date" class="col-xs-12 col-sm-6">
                <FormField label="Fecha del documento" required :error="errors.date" :hint="periodHint">
                    <q-input v-model="form.date" dense outlined type="date" class="input-theme"
                        :min="periodStart" :max="periodEnd" aria-label="Fecha del documento" />
                </FormField>
                <div v-if="!errors.date && dateOutsidePeriod" class="text-caption text-warning q-mt-xs">
                    <q-icon name="eva-alert-triangle-outline" /> La fecha está fuera del periodo de la rendición; no
                    podrá guardar el documento con esa fecha.
                </div>
            </div>
            <div data-tour="field-document-type" class="col-xs-12 col-sm-6">
                <FormField label="Tipo de documento" required :error="errors.document_id">
                    <q-select class="input-theme" dense outlined :options="documents" :model-value="form.document_id"
                        option-value="id" option-label="name" emit-value map-options
                        aria-label="Tipo de documento" @update:model-value="(id) => changeDocument(id)" />
                </FormField>
            </div>
            <div data-tour="field-concept" class="col-xs-12 col-sm-6">
                <FormField label="Concepto / glosa" required :error="errors.concept"
                    hint="Describa brevemente el gasto; se usa como glosa en contabilidad.">
                    <q-input v-model="form.concept" dense outlined class="input-theme" maxlength="250"
                        aria-label="Concepto / glosa" autocomplete="off" />
                </FormField>
            </div>
        </div>
    </q-card>

    <!-- Libro de compras -->
    <q-slide-transition>
        <q-card data-tour="receipt-data" v-if="hasDocument" class="q-pa-lg card-form q-mb-md">
            <div class="row items-center q-mb-md q-col-gutter-sm">
                <div class="col">
                    <div class="text-subtitle1 text-weight-medium">Datos del comprobante</div>
                    <div class="text-caption text-grey">Copie los datos tal como figuran en la factura o recibo.</div>
                </div>
                <div class="col-auto q-gutter-sm">
                    <q-btn v-if="hasInvoice" color="teal" icon="receipt_long" label="Ver factura" no-caps unelevated
                        size="12px" aria-label="Ver los datos de la factura en el SIAT" @click="emit('view-invoice')" />
                    <q-btn data-tour="scan-qr" v-if="canScan" color="primary" icon="qr_code_scanner" label="Escanear QR" no-caps
                        unelevated size="12px" aria-label="Escanear el código QR de la factura" @click="emit('scan')" />
                </div>
            </div>

            <q-banner data-tour="duplicate-warning" v-if="duplicate" dense rounded class="bg-warning text-dark q-mb-md">
                <template v-slot:avatar><q-icon name="eva-copy-outline" /></template>
                Esta factura ya está registrada en la rendición #{{ duplicate.accountability_id }}
                ({{ duplicate.status }}{{ duplicate.own ? '' : ', de otro usuario' }}). Verifique que no la esté
                rindiendo dos veces.
            </q-banner>

            <div class="row q-col-gutter-md">
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <FormField label="N° de documento" required :error="errors.document_number"
                        hint="Número de la factura o recibo">
                        <q-input v-model="form.document_number" dense outlined class="input-theme tabular" type="text"
                            inputmode="numeric" autocomplete="off" aria-label="Número de documento" />
                    </FormField>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.authorization_number_status">
                    <FormField label="N° de autorización" :error="errors.authorization_number">
                        <q-input v-model="form.authorization_number" dense outlined class="input-theme" type="text"
                            inputmode="numeric" autocomplete="off" aria-label="Número de autorización" />
                    </FormField>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.cuf_status">
                    <FormField label="CUF" :error="errors.cuf" hint="Código único de la factura electrónica">
                        <q-input v-model="form.cuf" dense outlined class="input-theme" autocomplete="off"
                            aria-label="Código único de facturación (CUF)" />
                    </FormField>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.control_code_status">
                    <FormField label="Código de control" :error="errors.control_code">
                        <q-input v-model="form.control_code" dense outlined class="input-theme" autocomplete="off"
                            aria-label="Código de control" />
                    </FormField>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.nit_status">
                    <FormField label="NIT / CI del proveedor" :error="errors.nit" :hint="nitHint">
                        <q-input :model-value="form.nit" dense outlined class="input-theme" type="text"
                            inputmode="numeric" autocomplete="off" :loading="nitLoading"
                            aria-label="NIT o CI del proveedor" @update:model-value="HandleNitInput" />
                    </FormField>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.business_name_status">
                    <FormField label="Razón social" :error="errors.business_name"
                        hint="Escriba al menos 2 letras para buscar el proveedor">
                        <q-select :model-value="form.business_name" dense outlined class="input-theme" use-input
                            hide-selected fill-input input-debounce="0" :options="supplierOptions"
                            option-label="business_name" behavior="menu" :loading="supplierLoading"
                            aria-label="Razón social del proveedor" @filter="HandleFilterSuppliers"
                            @input-value="(value) => (form.business_name = value)"
                            @update:model-value="HandlePickSupplier">
                            <template v-slot:option="scope">
                                <q-item v-bind="scope.itemProps">
                                    <q-item-section>
                                        <q-item-label>{{ scope.opt.business_name }}</q-item-label>
                                        <q-item-label caption v-if="scope.opt.nit">NIT / CI: {{ scope.opt.nit }}</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </template>
                            <template v-slot:no-option>
                                <q-item v-if="supplierSearched">
                                    <q-item-section class="text-grey">
                                        Sin coincidencias: se usará el nombre escrito.
                                    </q-item-section>
                                </q-item>
                            </template>
                        </q-select>
                    </FormField>
                </div>
                <div data-tour="field-amount" class="col-xs-12 col-sm-6 col-md-4">
                    <FormField label="Importe" required :error="errors.amount" hint="Monto total del comprobante">
                        <q-input :model-value="form.amount" dense outlined class="input-theme tabular" type="text"
                            inputmode="decimal" autocomplete="off" :suffix="symbol" aria-label="Importe"
                            @update:model-value="(v) => (form.amount = normalizeDecimal(v))" />
                    </FormField>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4" v-for="(field, i) in form.field" :key="field.id ?? field.field_id ?? i">
                    <FormField :label="field.name" required :error="errors['field.' + i + '.value']">
                        <q-input :model-value="field.value" dense outlined class="input-theme tabular" type="text"
                            inputmode="decimal" autocomplete="off" :aria-label="field.name"
                            @update:model-value="(v) => (field.value = normalizeDecimal(v))" />
                    </FormField>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4" v-for="extra in visibleExtras" :key="extra.key">
                    <FormField :label="extra.label" :error="errors[extra.key]">
                        <q-input :model-value="form[extra.key]" dense outlined class="input-theme tabular" type="text"
                            inputmode="decimal" autocomplete="off" :aria-label="extra.label"
                            @update:model-value="(v) => (form[extra.key] = normalizeDecimal(v))" />
                    </FormField>
                </div>
            </div>
        </q-card>
    </q-slide-transition>

    <!-- Datos contables -->
    <q-slide-transition>
        <q-card data-tour="cost-centers" v-if="hasDocument" class="card-form q-mb-md">
            <q-card-section class="q-py-sm cursor-pointer" role="button" tabindex="0"
                :aria-expanded="costCenterExpanded" aria-controls="datos-contables-body"
                @click="costCenterExpanded = !costCenterExpanded"
                @keydown.enter.space.prevent="costCenterExpanded = !costCenterExpanded">
                <div class="row items-center no-wrap">
                    <div class="col">
                        <div class="text-subtitle1 text-weight-medium">Proyecto y centros de costo</div>
                        <div v-if="!costCenterExpanded" class="text-caption text-grey q-mt-xs">
                            {{ form.split_active ? 'Asignados en Reparto por líneas' : (costCenterSummary || 'Sin asignar') }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <q-icon :name="costCenterExpanded ? 'expand_less' : 'expand_more'" color="grey-6" />
                    </div>
                </div>
            </q-card-section>

            <q-slide-transition>
                <div v-show="costCenterExpanded" id="datos-contables-body">
                    <q-separator />
                    <q-card-section class="q-pa-lg">
                        <div class="text-caption text-grey q-mb-md">
                            Indique a qué proyecto y área se carga el gasto. Si no está seguro, deje los valores
                            sugeridos.
                        </div>
                        <div class="row q-col-gutter-md">
                            <div class="col-xs-12 col-sm-6">
                                <FormField label="Proyecto" :error="errors.project_code">
                                    <q-select class="input-theme" dense outlined :options="projectOptions"
                                        v-model="form.project_code" option-value="PrjCode" option-label="PrjName"
                                        emit-value map-options use-input input-debounce="0" clearable
                                        aria-label="Proyecto" @filter="HandleFilterProjects">
                                        <template v-slot:no-option>
                                            <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                                        </template>
                                    </q-select>
                                </FormField>
                            </div>
                            <div class="col-xs-12 col-sm-6" v-for="dim in DIMENSIONS" :key="dim">
                                <FormField :label="`Centro de costo ${dim}`"
                                    :hint="form.split_active ? 'Asignados en Reparto por líneas' : undefined">
                                    <q-select class="input-theme" dense outlined :options="distribution[dim]"
                                        v-model="form[DIMENSION_FIELDS[dim]]" :disable="!!form.split_active"
                                        :option-value="distributionEmitValue ? 'PrcCode' : undefined"
                                        option-label="Name" :emit-value="distributionEmitValue"
                                        :map-options="distributionEmitValue" clearable
                                        :aria-label="`Centro de costo ${dim}`" />
                                </FormField>
                            </div>
                        </div>
                    </q-card-section>
                </div>
            </q-slide-transition>
        </q-card>
    </q-slide-transition>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import { useQuasar } from "quasar";
import axios from "axios";
import { route } from "ziggy-js";
import FormField from "@/components/FormField.vue";
import { currencySymbol } from "@/utils/accountability";
import { isOutsidePeriod, formatDate, normalizeDecimal } from "./siat";

const props = defineProps({
    // Formulario del gasto (se edita en el lugar, como en ExpenseItems / ExpenseDistribution)
    form: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    documents: { type: Array, default: () => [] },
    // Tipo de documento elegido ({} si no hay)
    document: { type: Object, default: () => ({}) },
    accounts: { type: Array, default: () => [] },
    projects: { type: Array, default: () => [] },
    distribution: { type: Object, default: () => ({}) },
    accountability: { type: Object, required: true },
    // Nuevo gasto: los centros de costo se guardan como código; edición: como opción completa
    distributionEmitValue: { type: Boolean, default: false },
    canScan: { type: Boolean, default: false },
    hasInvoice: { type: Boolean, default: false },
    // Documento que se edita (no es duplicado de sí mismo)
    excludeId: { type: [Number, String], default: null },
    currency: { type: String, default: null },
});
const emit = defineEmits(["scan", "view-invoice"]);

const $q = useQuasar();

const DIMENSIONS = [1, 2, 3, 4, 5];
const DIMENSION_FIELDS = {
    1: "distribution_rule_one",
    2: "distribution_rule_second",
    3: "distribution_rule_three",
    4: "distribution_rule_four",
    5: "distribution_rule_five",
};
const EXTRAS = [
    { key: "discount", label: "Descuento", flag: "discount_status" },
    { key: "excento", label: "Exento", flag: "exento_status" },
    { key: "rate", label: "Tasas", flag: "tasas" },
    { key: "gift_card", label: "Gift card", flag: "gift_card_status" },
    { key: "rate_zero", label: "Tasa cero", flag: "rate_zero_status" },
    { key: "ice", label: "ICE", flag: "ice_status" },
];

const hasDocument = computed(() => Object.keys(props.document || {}).length > 0);
const visibleExtras = computed(() => EXTRAS.filter((extra) => props.document?.[extra.flag]));
const symbol = computed(() => currencySymbol(props.currency));

// ── Periodo de la rendición ─────────────────────────────────────────────
const periodStart = computed(() => props.accountability?.start_date?.substring(0, 10) ?? null);
const periodEnd = computed(() => props.accountability?.end_date?.substring(0, 10) ?? null);
const periodHint = computed(() =>
    periodStart.value && periodEnd.value
        ? `Periodo de la rendición: ${formatDate(periodStart.value)} al ${formatDate(periodEnd.value)}`
        : undefined
);
const dateOutsidePeriod = computed(() => isOutsidePeriod(props.form.date, periodStart.value, periodEnd.value));

// ── Cuentas y proyectos ─────────────────────────────────────────────────
const accountOptions = ref(props.accounts);
const projectOptions = ref(props.projects);

function HandleFilterAccounts(val, update) {
    update(() => {
        const needle = val.toLowerCase();
        accountOptions.value = props.accounts.filter((a) => !needle || a.label.toLowerCase().includes(needle));
    });
}

function HandleFilterProjects(val, update) {
    update(() => {
        const needle = val.toLowerCase();
        projectOptions.value = (props.projects || []).filter(
            (p) => !needle || String(p.PrjName).toLowerCase().includes(needle)
        );
    });
}

// ── Tipo de documento ───────────────────────────────────────────────────
/**
 * Cambia el tipo de documento y arma sus campos adicionales. Se conservan los valores
 * de los campos con el mismo nombre; si se perderían datos cargados, se pregunta antes.
 * onDone(applied) se llama al terminar.
 */
function changeDocument(id, onDone) {
    if (id === props.form.document_id) {
        onDone?.(true);
        return;
    }
    const target = props.documents.find((d) => d.id == id);
    const targetNames = (target?.fields || []).map((f) => f.name);
    const lost = (props.form.field || []).filter(
        (f) => f.value !== null && f.value !== "" && !targetNames.includes(f.name)
    );

    const apply = () => {
        const previous = Object.fromEntries((props.form.field || []).map((f) => [f.name, f.value]));
        props.form.document_id = id;
        props.form.field = (target?.fields || []).map((f) => ({
            name: f.name,
            value: previous[f.name] ?? null,
            id: f.id,
        }));
        onDone?.(true);
    };

    if (lost.length === 0) {
        apply();
        return;
    }
    $q.dialog({
        title: "Cambiar tipo de documento",
        message: `Al cambiar el tipo de documento se borrarán los datos cargados en: ${lost.map((f) => f.name).join(", ")}. ¿Desea continuar?`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Cambiar", color: "primary", noCaps: true, unelevated: true },
        persistent: true,
    })
        .onOk(apply)
        .onCancel(() => onDone?.(false));
}

// ── Proveedor: búsqueda remota (panel.supplier-lookup) ───────────────────
const supplierOptions = ref([]);
const supplierLoading = ref(false);
const supplierSearched = ref(false);
const nitLoading = ref(false);
const nitHint = ref(undefined);
let supplierTimer = null;
let nitTimer = null;

function HandleFilterSuppliers(val, update, abort) {
    clearTimeout(supplierTimer);
    const search = (val || "").trim();
    if (search.length < 2) {
        supplierSearched.value = false;
        update(() => (supplierOptions.value = []));
        return;
    }
    supplierTimer = setTimeout(async () => {
        supplierLoading.value = true;
        try {
            const { data } = await axios.get(route("panel.supplier-lookup"), { params: { search } });
            supplierSearched.value = true;
            update(() => (supplierOptions.value = Array.isArray(data) ? data : []));
        } catch {
            abort();
        } finally {
            supplierLoading.value = false;
        }
    }, 300);
}

function HandlePickSupplier(option) {
    if (option && typeof option === "object") {
        props.form.business_name = option.business_name;
        if (option.nit) props.form.nit = option.nit;
    } else {
        props.form.business_name = option ?? null;
    }
}

// Al escribir un NIT conocido se completa la razón social
function HandleNitInput(value) {
    props.form.nit = value;
    nitHint.value = undefined;
    clearTimeout(nitTimer);
    const nit = String(value ?? "").trim();
    if (nit.length < 3) return;
    nitTimer = setTimeout(async () => {
        nitLoading.value = true;
        try {
            const { data } = await axios.get(route("panel.supplier-lookup"), { params: { nit } });
            if (String(props.form.nit ?? "").trim() !== nit) return;
            const found = Array.isArray(data) ? data.find((s) => s.business_name) : null;
            if (found) {
                props.form.business_name = found.business_name;
                nitHint.value = `Proveedor encontrado: ${found.business_name}`;
            }
        } catch {
            // Sin búsqueda: el usuario completa la razón social a mano
        } finally {
            nitLoading.value = false;
        }
    }, 500);
}

// ── Factura ya registrada en alguna rendición (aviso no bloqueante) ──────
const duplicate = ref(null);
let duplicateTimer = null;
watch(
    () => [props.form.cuf, props.form.nit, props.form.document_number],
    ([cuf, nit, number]) => {
        clearTimeout(duplicateTimer);
        if (!cuf && !(nit && number)) {
            duplicate.value = null;
            return;
        }
        duplicateTimer = setTimeout(async () => {
            try {
                const { data } = await axios.get(route("panel.document-duplicate"), {
                    params: { cuf, nit, document_number: number, exclude_id: props.excludeId },
                });
                duplicate.value = data?.duplicate ? data : null;
            } catch {
                duplicate.value = null;
            }
        }, 600);
    },
    { immediate: true }
);

// ── Centros de costo ────────────────────────────────────────────────────
function costCenterName(dim) {
    const value = props.form[DIMENSION_FIELDS[dim]];
    if (!value) return null;
    if (typeof value === "object") return value.Name || value.PrcCode || value.OcrCode || null;
    const option = (props.distribution[dim] || []).find((o) => (o.PrcCode || o.OcrCode) === value);
    return option ? option.Name : value;
}
const costCenterSummary = computed(() => DIMENSIONS.map(costCenterName).filter(Boolean).join(" · "));
// Si ya vienen centros de costo sugeridos, la sección empieza contraída
const costCenterExpanded = ref(!costCenterSummary.value);

onBeforeUnmount(() => {
    clearTimeout(supplierTimer);
    clearTimeout(nitTimer);
    clearTimeout(duplicateTimer);
});

defineExpose({ changeDocument });
</script>

<style scoped>
.tabular :deep(input) {
    font-variant-numeric: tabular-nums;
}
</style>
