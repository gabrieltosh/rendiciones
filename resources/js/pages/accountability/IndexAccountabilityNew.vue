<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">

                <PageHeader title="Rendiciones" icon="eva-file-text-outline" :back="route('panel.accountability.profiles')"
                    :subtitle="`${page.props.profile.name} · ${rows.length} registro${rows.length !== 1 ? 's' : ''}`">
                    <template #actions>
                        <q-btn data-tour="new-accountability" color="primary" icon="add" label="Nueva rendición" no-caps unelevated
                            aria-label="Crear nueva rendición" @click="HandleCreateAccountability()" />
                    </template>
                </PageHeader>

                <q-card class="card-form">
                    <!-- Barra de herramientas -->
                    <q-card-section class="q-pb-none">
                        <div class="row items-center q-col-gutter-sm">
                            <div class="col-xs-12 col-sm">
                                <div data-tour="status-filters" class="row q-gutter-xs">
                                    <q-chip v-for="chip in statusChips" :key="chip.value" clickable dense
                                        :outline="statusFilter !== chip.value"
                                        :color="statusFilter === chip.value ? chip.color : 'grey-6'"
                                        :text-color="statusFilter === chip.value ? 'white' : undefined"
                                        :aria-pressed="statusFilter === chip.value"
                                        @click="statusFilter = chip.value">
                                        {{ chip.label }}
                                        <q-badge v-if="chip.count" rounded color="white" text-color="dark"
                                            class="q-ml-xs" :label="chip.count" />
                                    </q-chip>
                                </div>
                            </div>
                            <div data-tour="search" class="col-xs col-sm-auto">
                                <q-input v-model="filter" outlined dense placeholder="Buscar…" name="search-accountability"
                                    autocomplete="off" aria-label="Buscar rendiciones" class="input-theme" clearable>
                                    <template v-slot:prepend>
                                        <q-icon name="search" aria-hidden="true" />
                                    </template>
                                </q-input>
                            </div>
                            <div data-tour="view-mode" class="col-auto q-gutter-xs">
                                <q-btn :flat="viewMode !== 'grid'" :unelevated="viewMode === 'grid'"
                                    :color="viewMode === 'grid' ? 'secondary' : 'grey'" dense round size="sm"
                                    icon="eva-grid-outline" aria-label="Vista de tarjetas" @click="viewMode = 'grid'">
                                    <q-tooltip>Vista de tarjetas</q-tooltip>
                                </q-btn>
                                <q-btn :flat="viewMode !== 'table'" :unelevated="viewMode === 'table'"
                                    :color="viewMode === 'table' ? 'secondary' : 'grey'" dense round size="sm"
                                    icon="eva-list-outline" aria-label="Vista de tabla" @click="viewMode = 'table'">
                                    <q-tooltip>Vista de tabla</q-tooltip>
                                </q-btn>
                            </div>
                        </div>
                    </q-card-section>

                    <q-card-section>
                        <!-- Sin resultados -->
                        <div v-if="filteredRows.length === 0" class="text-center q-pa-xl">
                            <q-icon name="receipt_long" size="64px" color="grey-4" aria-hidden="true" />
                            <div class="text-body1 text-grey q-mt-md">
                                {{ rows.length === 0 ? 'Todavía no tiene rendiciones' : 'Sin resultados para los filtros elegidos' }}
                            </div>
                            <q-btn v-if="rows.length === 0" color="primary" label="Crear rendición" icon="add" no-caps
                                unelevated class="q-mt-md" @click="HandleCreateAccountability()" />
                            <q-btn v-else flat color="primary" label="Quitar filtros" no-caps class="q-mt-md"
                                @click="HandleClearFilters()" />
                        </div>

                        <!-- Tarjetas -->
                        <div data-tour="accountability-list" v-else-if="viewMode === 'grid' || $q.screen.xs" class="row q-col-gutter-md">
                            <div v-for="row in filteredRows" :key="row.id" class="col-xs-12 col-sm-6 col-md-4">
                                <q-card data-tour="accountability-card" flat bordered class="acc-card full-height cursor-pointer" tabindex="0"
                                    role="link" :aria-label="`Abrir rendición ${row.id}`"
                                    @click="HandleDetailAccountability(row.id)"
                                    @keydown.enter="HandleDetailAccountability(row.id)">
                                    <q-card-section class="q-pb-sm">
                                        <div class="row items-start no-wrap q-gutter-sm">
                                            <div class="col" style="min-width: 0;">
                                                <div class="text-body2 text-weight-medium ellipsis">
                                                    {{ row.description || 'Sin descripción' }}
                                                </div>
                                                <div class="text-caption text-grey q-mt-xs">
                                                    Rendición #{{ row.id }} · {{ formatDate(row.start_date) }} al {{ formatDate(row.end_date) }}
                                                </div>
                                            </div>
                                            <div class="col-auto column items-end q-gutter-xs">
                                                <q-badge :color="statusColor(row.status)" :label="statusLabel(row.status)" class="q-pa-xs" />
                                                <q-badge v-if="row.status === 'Autorizado'" :color="getSapLocationColor(row)"
                                                    :label="getSapLocationLabel(row)" class="q-pa-xs" outline />
                                            </div>
                                        </div>
                                    </q-card-section>

                                    <q-separator inset />

                                    <q-card-section class="q-py-sm">
                                        <div class="row q-col-gutter-xs text-center">
                                            <div class="col-4">
                                                <div class="text-caption text-grey">Recibido</div>
                                                <div class="text-body2 stat-number">{{ formatMoney(row.total, currency) }}</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-caption text-grey">Gastado</div>
                                                <div class="text-body2 stat-number">{{ formatMoney(spentOf(row), currency) }}</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-caption text-grey">{{ balanceOf(row).label }}</div>
                                                <div class="text-body2 stat-number text-weight-medium" :class="`text-${balanceOf(row).color}`">
                                                    {{ formatMoney(balanceOf(row).balance, currency) }}
                                                </div>
                                            </div>
                                        </div>
                                    </q-card-section>

                                    <q-separator />

                                    <q-card-section class="q-py-xs">
                                        <div class="row items-center">
                                            <div class="col text-caption text-grey ellipsis">
                                                {{ row.detail_count ?? 0 }} documento{{ row.detail_count === 1 ? '' : 's' }}
                                                <span v-if="row.employee_name"> · {{ row.employee_name }}</span>
                                            </div>
                                            <div class="col-auto q-gutter-xs" @click.stop>
                                                <q-btn v-if="isEditable(row)" flat round dense size="sm" icon="edit"
                                                    color="secondary" aria-label="Editar rendición"
                                                    @click="HandleEditAccountability(row.id)">
                                                    <q-tooltip>Editar</q-tooltip>
                                                </q-btn>
                                                <q-btn v-if="isEditable(row)" flat round dense size="sm"
                                                    icon="delete_outline" color="negative" aria-label="Eliminar rendición"
                                                    :loading="deletingId === row.id" @click="HandleDeleteAccountability(row)">
                                                    <q-tooltip>Eliminar</q-tooltip>
                                                </q-btn>
                                            </div>
                                        </div>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </div>

                        <!-- Tabla -->
                        <q-table data-tour="accountability-list" v-else :rows="filteredRows" :columns="columns" row-key="id" class="table-theme" flat
                            bordered separator="horizontal" :table-header-class="{ 'table-header-theme': true }"
                            :pagination="{ rowsPerPage: 15, sortBy: 'id', descending: true }" hide-bottom-space
                            @row-click="(evt, row) => HandleDetailAccountability(row.id)">
                            <template v-slot:body-cell-description="props">
                                <q-td :props="props">
                                    <span class="ellipsis" style="max-width: 220px; display: inline-block;">
                                        {{ props.row.description || '-' }}
                                    </span>
                                </q-td>
                            </template>
                            <template v-slot:body-cell-total="props">
                                <q-td :props="props" class="stat-number">{{ formatMoney(props.row.total, currency) }}</q-td>
                            </template>
                            <template v-slot:body-cell-spent="props">
                                <q-td :props="props" class="stat-number">{{ formatMoney(spentOf(props.row), currency) }}</q-td>
                            </template>
                            <template v-slot:body-cell-balance="props">
                                <q-td :props="props" class="stat-number">
                                    <span :class="`text-${balanceOf(props.row).color} text-weight-medium`">
                                        {{ formatMoney(balanceOf(props.row).balance, currency) }}
                                    </span>
                                    <div class="text-caption text-grey">{{ balanceOf(props.row).label }}</div>
                                </q-td>
                            </template>
                            <template v-slot:body-cell-period="props">
                                <q-td :props="props">
                                    {{ formatDate(props.row.start_date) }} al {{ formatDate(props.row.end_date) }}
                                </q-td>
                            </template>
                            <template v-slot:body-cell-status="props">
                                <q-td :props="props">
                                    <div class="column items-center q-gutter-xs">
                                        <q-badge :color="statusColor(props.row.status)" :label="statusLabel(props.row.status)" />
                                        <q-badge v-if="props.row.status === 'Autorizado'" :color="getSapLocationColor(props.row)"
                                            :label="getSapLocationLabel(props.row)" outline />
                                    </div>
                                </q-td>
                            </template>
                            <template v-slot:body-cell-actions="props">
                                <q-td :props="props" @click.stop>
                                    <div class="q-gutter-xs">
                                        <q-btn flat round dense size="sm" icon="visibility" color="primary"
                                            aria-label="Ver detalle de rendición" @click="HandleDetailAccountability(props.row.id)">
                                            <q-tooltip>Ver detalle</q-tooltip>
                                        </q-btn>
                                        <q-btn v-if="isEditable(props.row)" flat round dense size="sm" icon="edit"
                                            color="secondary" aria-label="Editar rendición"
                                            @click="HandleEditAccountability(props.row.id)">
                                            <q-tooltip>Editar</q-tooltip>
                                        </q-btn>
                                        <q-btn v-if="isEditable(props.row)" flat round dense size="sm" icon="delete_outline"
                                            color="negative" aria-label="Eliminar rendición"
                                            :loading="deletingId === props.row.id" @click="HandleDeleteAccountability(props.row)">
                                            <q-tooltip>Eliminar</q-tooltip>
                                        </q-btn>
                                    </div>
                                </q-td>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>

            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import { formatMoney, balanceInfo, statusLabel, statusColor } from "@/utils/accountability";
import { formatDate } from "@/components/accountability/siat";
import { ref, computed, watch } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar, LocalStorage } from "quasar";

defineProps({
    title: String,
    data: Object,
});

const $q = useQuasar();
const page = usePage();
// Se leen de las props para reflejar los cambios después de eliminar
const rows = computed(() => page.props.data ?? []);
const currency = computed(() => page.props.profile?.type_currency);
const filter = ref("");
const statusFilter = ref("all");
const deletingId = ref(null);
// La vista elegida (tarjetas/tabla) se recuerda entre visitas
const viewMode = ref(LocalStorage.getItem("accountability-view-mode") || "grid");
watch(viewMode, (mode) => LocalStorage.set("accountability-view-mode", mode));

const STATUS_FILTERS = [
    { value: "all", label: "Todas", color: "primary" },
    { value: "Borrador", label: "Borrador", color: statusColor(null) },
    { value: "Rechazado", label: "Rechazadas", color: statusColor("Rechazado") },
    { value: "Pendiente", label: "En revisión", color: statusColor("Pendiente") },
    { value: "Autorizado", label: "Autorizadas", color: statusColor("Autorizado") },
    { value: "Anulado", label: "Anuladas", color: statusColor("Anulado") },
];

const statusChips = computed(() =>
    STATUS_FILTERS.map((chip) => ({
        ...chip,
        count: chip.value === "all" ? rows.value.length : rows.value.filter((r) => statusLabel(r.status) === chip.value).length,
    })).filter((chip) => chip.value === "all" || chip.count > 0)
);

const columns = [
    { name: "id", align: "center", label: "N°", field: "id", sortable: true, style: "width: 60px" },
    { name: "description", align: "left", label: "Descripción", field: "description", sortable: true },
    { name: "period", align: "left", label: "Periodo", field: "start_date", sortable: true },
    { name: "total", align: "right", label: "Recibido", field: (row) => Number(row.total) || 0, sortable: true },
    { name: "spent", align: "right", label: "Gastado", field: (row) => spentOf(row), sortable: true },
    { name: "balance", align: "right", label: "Saldo", field: (row) => balanceOf(row).balance },
    { name: "status", align: "center", label: "Estado", field: (row) => statusLabel(row.status), sortable: true },
    { name: "actions", align: "center", label: "Acciones", field: "id", style: "width: 120px" },
];

const filteredRows = computed(() => {
    let list = rows.value;
    if (statusFilter.value !== "all") {
        list = list.filter((row) => statusLabel(row.status) === statusFilter.value);
    }
    if (!filter.value) return list;
    const needle = filter.value.toLowerCase();
    return list.filter((row) =>
        [row.description, row.employee_name, row.account_name, row.id, row.sap_trans_id]
            .some((value) => value !== null && value !== undefined && String(value).toLowerCase().includes(needle))
    );
});

// Gastado = suma de los importes de los documentos (withSum del servidor)
function spentOf(row) {
    return Number(row.detail_sum_amount) || 0;
}

function balanceOf(row) {
    return balanceInfo(row.total, spentOf(row));
}

function getSapLocationLabel(row) {
    if (row.sap_exported) return row.sap_trans_id ? `En contabilidad · N° ${row.sap_trans_id}` : "En contabilidad";
    return "Pendiente de contabilizar";
}

function getSapLocationColor(row) {
    return row.sap_exported ? "positive" : "warning";
}

function isEditable(row) {
    return row.status === null || row.status === "Rechazado";
}

function HandleClearFilters() {
    filter.value = "";
    statusFilter.value = "all";
}

function HandleCreateAccountability() {
    router.visit(route("panel.accountability.manage.create", page.props.profile.id));
}

function HandleEditAccountability(id) {
    router.visit(route("panel.accountability.manage.edit", [page.props.profile.id, id]));
}

function HandleDetailAccountability(id) {
    router.visit(route("panel.accountability.manage.detail.index", [page.props.profile.id, id]));
}

function HandleDeleteAccountability(row) {
    const docs = row.detail_count ?? 0;
    $q.dialog({
        title: "Eliminar rendición",
        message:
            `¿Eliminar la rendición #${row.id} «${row.description || "Sin descripción"}»` +
            (docs ? ` y sus ${docs} documento${docs !== 1 ? "s" : ""}` : "") +
            "? Esta acción no se puede deshacer.",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true, unelevated: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.accountability.manage.delete", [page.props.profile.id, row.id]), {
            preserveScroll: true,
            onStart: () => (deletingId.value = row.id),
            onFinish: () => (deletingId.value = null),
        });
    });
}
</script>

<style scoped>
.stat-number {
    font-variant-numeric: tabular-nums;
}
.acc-card {
    transition: box-shadow 0.2s ease;
}
.acc-card:hover {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}
.acc-card:focus-visible {
    outline: 2px solid var(--q-primary);
    outline-offset: 2px;
}
.full-height {
    height: 100%;
    display: flex;
    flex-direction: column;
}
.full-height > .q-card__section:last-child {
    margin-top: auto;
}
@media (prefers-reduced-motion: reduce) {
    .acc-card {
        transition: none;
    }
}
</style>
