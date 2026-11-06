<template>
    <q-card class="card-form q-mb-md">
        <q-card-section class="q-pb-sm">
            <div class="row items-center q-col-gutter-sm">
                <div class="col">
                    <div class="text-subtitle1 text-weight-medium">Artículos de la factura</div>
                    <div class="text-caption text-grey">
                        Precio unitario <strong>sin IVA</strong>. El total debe ser igual al gasto del documento; al
                        exportar se crea en SAP una entrada de mercancías en borrador contra la cuenta del gasto.
                    </div>
                </div>
                <div v-if="siatDetail?.length" class="col-auto">
                    <q-btn outline dense no-caps color="teal" icon="receipt_long" label="Cargar desde la factura SIAT"
                        class="q-px-sm" @click="HandleLoadFromSiat()" />
                </div>
            </div>
            <div v-if="errors.items" class="container-error">
                <ul class="message-error">
                    <li>{{ firstError(errors.items) }}</li>
                </ul>
            </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
            <!-- Almacén general -->
            <div class="row items-end q-col-gutter-sm q-mb-md">
                <div class="col-xs-12 col-sm-6 col-md-5">
                    <div class="form-label">Almacén para todas las líneas</div>
                    <q-select v-model="generalWarehouse" :options="filteredWarehouses" option-value="WhsCode"
                        :option-label="warehouseLabel" emit-value map-options use-input input-debounce="0" clearable
                        dense outlined class="input-theme" :loading="loadingWarehouses"
                        aria-label="Almacén para todas las líneas" @filter="HandleFilterWarehouses" />
                </div>
                <div class="col-auto">
                    <q-btn flat dense no-caps color="primary" icon="done_all" label="Aplicar a todas"
                        :disable="!generalWarehouse || form.items.length === 0" @click="HandleApplyWarehouse()" />
                </div>
            </div>

            <div v-if="form.items.length === 0" class="text-center text-grey q-pa-lg">
                <q-icon name="inventory_2" size="40px" color="grey-4" />
                <div class="q-mt-sm">Agregue los artículos de la factura.</div>
            </div>

            <q-markup-table v-else flat bordered dense separator="horizontal" class="items-table">
                <thead>
                    <tr>
                        <th class="text-left" style="min-width: 240px;">Artículo <span class="text-negative">*</span></th>
                        <th class="text-right" style="width: 110px;">Cantidad <span class="text-negative">*</span></th>
                        <th class="text-right" style="width: 130px;">Precio s/IVA <span class="text-negative">*</span></th>
                        <th class="text-left" style="min-width: 180px;">Almacén <span class="text-negative">*</span></th>
                        <th class="text-right" style="width: 110px;">Total</th>
                        <th style="width: 40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in form.items" :key="i">
                        <td>
                            <q-select :model-value="item.item_code" :options="itemOptions[i] || selectedItemOption(item)"
                                option-value="ItemCode" :option-label="itemLabel" emit-value map-options use-input
                                input-debounce="300" dense outlined class="input-theme"
                                :error="!!errors[`items.${i}.item_code`]" hide-bottom-space
                                :aria-label="`Artículo de la línea ${i + 1}`"
                                @filter="(val, update, abort) => HandleSearchItems(i, val, update, abort)"
                                @update:model-value="(code) => HandleSelectItem(i, code)">
                                <template v-slot:no-option>
                                    <q-item><q-item-section class="text-grey">
                                        Escriba al menos 2 letras del código o la descripción
                                    </q-item-section></q-item>
                                </template>
                            </q-select>
                            <div v-if="item.siat_description" class="text-caption text-grey q-mt-xs">
                                Factura: {{ item.siat_description }}
                            </div>
                        </td>
                        <td>
                            <q-input v-model.number="item.quantity" type="number" min="0" step="any" dense outlined
                                class="input-theme" input-class="text-right" :error="!!errors[`items.${i}.quantity`]"
                                hide-bottom-space :aria-label="`Cantidad de la línea ${i + 1}`"
                                input-style="font-variant-numeric: tabular-nums" />
                        </td>
                        <td>
                            <q-input v-model.number="item.price" type="number" min="0" step="any" dense outlined
                                class="input-theme" input-class="text-right" :error="!!errors[`items.${i}.price`]"
                                hide-bottom-space :aria-label="`Precio sin IVA de la línea ${i + 1}`"
                                input-style="font-variant-numeric: tabular-nums" />
                        </td>
                        <td>
                            <q-select v-model="item.warehouse_code" :options="filteredWarehouses"
                                option-value="WhsCode" :option-label="warehouseLabel" emit-value map-options use-input
                                input-debounce="0" dense outlined class="input-theme"
                                :error="!!errors[`items.${i}.warehouse_code`]" hide-bottom-space
                                :aria-label="`Almacén de la línea ${i + 1}`" @filter="HandleFilterWarehouses" />
                        </td>
                        <td class="text-right stat-number">{{ formatAmount(lineTotal(item)) }}</td>
                        <td class="text-center">
                            <q-btn flat round dense size="sm" icon="eva-trash-2-outline" color="negative"
                                :aria-label="`Quitar la línea ${i + 1}`" @click="HandleRemoveItem(i)" />
                        </td>
                    </tr>
                </tbody>
            </q-markup-table>

            <div class="row items-center q-mt-sm q-col-gutter-sm">
                <div class="col-auto">
                    <q-btn flat dense no-caps color="primary" icon="add" label="Agregar artículo"
                        @click="HandleAddItem()" />
                </div>
                <q-space />
                <div class="col-auto text-right stat-number">
                    <div class="text-caption text-grey">
                        Total artículos: <strong class="text-body2">{{ formatAmount(total) }}</strong>
                        · Gasto del documento sin IVA:
                        <strong class="text-body2">{{ expense === null ? '—' : formatAmount(expense) }}</strong>
                        <q-spinner v-if="loadingExpense" size="14px" class="q-ml-xs" />
                    </div>
                    <div v-if="expense !== null && form.items.length" class="text-caption"
                        :class="matches ? 'text-positive' : 'text-negative'">
                        <template v-if="matches">
                            <q-icon name="check_circle" /> El total coincide con el gasto
                        </template>
                        <template v-else>
                            Diferencia: {{ formatAmount(total - expense) }}
                            <q-btn v-if="canAdjust" flat dense no-caps size="sm" color="primary"
                                label="Ajustar en la última línea" @click="HandleAdjustLastLine()" />
                        </template>
                    </div>
                </div>
            </div>
        </q-card-section>
    </q-card>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { firstError } from "@/utils/forms";
import { formatAmount } from "@/utils/accountability";

const props = defineProps({
    // Formulario del gasto; los ítems se editan en form.items
    form: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    // Detalle de la factura consultada en SIAT (listaDetalle), si lo hay
    siatDetail: { type: Array, default: null },
});

const $q = useQuasar();

if (!Array.isArray(props.form.items)) props.form.items = [];
// Un documento con ítems no admite reparto por líneas
props.form.splits = {};
props.form.lines = [];

const warehouses = ref([]);
const filteredWarehouses = ref([]);
const loadingWarehouses = ref(false);
const generalWarehouse = ref(null);
// Opciones del buscador de artículos por línea (resultados de la última búsqueda)
const itemOptions = ref({});
const expense = ref(null);
const loadingExpense = ref(false);

function lineTotal(item) {
    return Math.round((Number(item.quantity) || 0) * (Number(item.price) || 0) * 100) / 100;
}

const total = computed(() => Math.round(props.form.items.reduce((sum, item) => sum + lineTotal(item), 0) * 100) / 100);
const matches = computed(() => expense.value !== null && Math.abs(total.value - expense.value) <= 0.005);
const canAdjust = computed(() => {
    const last = props.form.items[props.form.items.length - 1];
    return expense.value !== null && last && Number(last.quantity) > 0 && Math.abs(total.value - expense.value) < 1;
});

function itemLabel(option) {
    if (!option) return "";
    return option.ItemName ? `${option.ItemCode} - ${option.ItemName}` : option.ItemCode;
}

function warehouseLabel(option) {
    if (!option) return "";
    return option.WhsName ? `${option.WhsCode} - ${option.WhsName}` : option.WhsCode;
}

// Al editar, el select muestra el artículo guardado aunque no se haya buscado
function selectedItemOption(item) {
    return item.item_code ? [{ ItemCode: item.item_code, ItemName: item.item_name }] : [];
}

function HandleAddItem() {
    props.form.items.push({
        item_code: null,
        item_name: null,
        quantity: 1,
        price: null,
        warehouse_code: generalWarehouse.value,
    });
}

function HandleRemoveItem(i) {
    props.form.items.splice(i, 1);
    // Las opciones de búsqueda se guardan por posición: se descartan al mover las líneas
    itemOptions.value = {};
}

function HandleSelectItem(i, code) {
    const option = (itemOptions.value[i] || []).find((o) => o.ItemCode === code);
    props.form.items[i].item_code = code;
    props.form.items[i].item_name = option?.ItemName ?? null;
}

async function HandleSearchItems(i, val, update, abort) {
    if (val.length < 2) {
        update(() => (itemOptions.value[i] = selectedItemOption(props.form.items[i])));
        return;
    }
    try {
        const { data } = await axios.get(route("sap.items"), { params: { search: val } });
        update(() => (itemOptions.value[i] = data));
    } catch {
        abort();
        $q.notify({ type: "negative", message: "No se pudieron buscar los artículos en SAP" });
    }
}

function HandleFilterWarehouses(val, update) {
    update(() => {
        const needle = val.toLowerCase();
        filteredWarehouses.value = warehouses.value.filter(
            (w) => !needle || warehouseLabel(w).toLowerCase().includes(needle)
        );
    });
}

function HandleApplyWarehouse() {
    props.form.items.forEach((item) => (item.warehouse_code = generalWarehouse.value));
}

// Lleva la diferencia de redondeo a la última línea, recalculando su precio
function HandleAdjustLastLine() {
    const last = props.form.items[props.form.items.length - 1];
    const others = total.value - lineTotal(last);
    last.price = Math.round(((expense.value - others) / Number(last.quantity)) * 1e6) / 1e6;
}

// Precarga cantidades y precios desde el detalle de SIAT (con IVA) convertidos a sin IVA
// con la proporción gasto / monto del documento. El usuario elige el artículo de SAP.
function HandleLoadFromSiat() {
    const load = () => {
        const amount = Number(props.form.amount) || 0;
        const factor = expense.value !== null && amount > 0 ? expense.value / amount : 1;
        props.form.items = props.siatDetail.map((line) => {
            const quantity = Number(line.cantidad) || 1;
            const subtotal = line.subTotal !== undefined
                ? Number(line.subTotal)
                : quantity * (Number(line.precioUnitario) || 0);
            return {
                item_code: null,
                item_name: null,
                siat_description: line.descripcion,
                quantity,
                price: Math.round(((subtotal * factor) / quantity) * 1e6) / 1e6,
                warehouse_code: generalWarehouse.value,
            };
        });
        itemOptions.value = {};
    };
    if (props.form.items.length === 0) return load();
    $q.dialog({
        title: "Cargar desde la factura",
        message: "Se reemplazarán los artículos cargados por el detalle de la factura de SIAT. ¿Continuar?",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Reemplazar", color: "primary", noCaps: true },
        persistent: true,
    }).onOk(load);
}

// Gasto sin IVA: lo calcula el servidor con la misma lógica que la exportación
let expenseTimer = null;
function RefreshExpense() {
    clearTimeout(expenseTimer);
    expenseTimer = setTimeout(async () => {
        if (!props.form.document_id || !props.form.amount) {
            expense.value = null;
            return;
        }
        loadingExpense.value = true;
        try {
            const { data } = await axios.post(route("accountability.expense-preview"), {
                document_id: props.form.document_id,
                amount: props.form.amount,
                field: props.form.field,
                ice: props.form.ice,
                rate: props.form.rate,
                excento: props.form.excento,
            });
            expense.value = data.expense === null ? null : Number(data.expense);
        } catch {
            expense.value = null;
        } finally {
            loadingExpense.value = false;
        }
    }, 400);
}

watch(
    () => [props.form.document_id, props.form.amount, props.form.ice, props.form.rate, props.form.excento,
        JSON.stringify(props.form.field)],
    RefreshExpense,
    { immediate: true }
);

onMounted(async () => {
    loadingWarehouses.value = true;
    try {
        const { data } = await axios.get(route("sap.warehouses"));
        warehouses.value = data;
        filteredWarehouses.value = data;
    } catch {
        $q.notify({ type: "negative", message: "No se pudieron cargar los almacenes de SAP" });
    } finally {
        loadingWarehouses.value = false;
    }
});
</script>

<style scoped>
.stat-number {
    font-variant-numeric: tabular-nums;
}
.items-table td {
    vertical-align: top;
    padding-top: 6px;
    padding-bottom: 6px;
}
</style>
