<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">

                <PageHeader title="Autorización de Rendiciones" icon="eva-checkmark-square-2-outline">
                    <template #subtitle>
                        {{ pendingRows.length }} pendiente{{ pendingRows.length !== 1 ? 's' : '' }}
                        <template v-if="authorizedTotal !== null">
                            &middot; {{ authorizedTotal }} autorizada{{ authorizedTotal !== 1 ? 's' : '' }}
                        </template>
                        <template v-if="showAll"> &middot; mostrando todas las rendiciones</template>
                    </template>
                    <template v-if="isAdmin" #actions>
                        <q-toggle data-tour="show-all"
                            :model-value="showAll"
                            label="Ver todas"
                            color="primary"
                            :disable="toggling"
                            aria-label="Ver todas las rendiciones, no solo las que me toca autorizar"
                            @update:model-value="HandleToggleAll"
                        >
                            <q-tooltip>Como Administrador puede ver y autorizar las rendiciones de todos los niveles</q-tooltip>
                        </q-toggle>
                    </template>
                </PageHeader>

                <q-card class="card-form">
                    <!-- Tabs + Toolbar -->
                    <q-card-section class="q-pb-none">
                        <div class="row items-center q-col-gutter-sm">
                            <div class="col">
                                <q-tabs data-tour="inbox-tabs"
                                    v-model="activeTab"
                                    dense
                                    no-caps
                                    active-color="primary"
                                    indicator-color="primary"
                                    align="left"
                                    narrow-indicator
                                >
                                    <q-tab name="pending" aria-label="Rendiciones pendientes de autorización">
                                        <div class="row items-center q-gutter-xs no-wrap">
                                            <span>Pendientes</span>
                                            <q-badge v-if="pendingRows.length > 0" color="orange"
                                                :label="pendingRows.length" class="q-ml-xs" />
                                        </div>
                                    </q-tab>
                                    <q-tab name="authorized" aria-label="Rendiciones autorizadas">
                                        <div class="row items-center q-gutter-xs no-wrap">
                                            <span>Autorizadas</span>
                                            <q-badge v-if="authorizedTotal" color="green"
                                                :label="authorizedTotal" class="q-ml-xs" />
                                        </div>
                                    </q-tab>
                                </q-tabs>
                            </div>
                            <div data-tour="search" class="col-auto">
                                <q-input
                                    v-if="activeTab === 'pending'"
                                    v-model="filter"
                                    outlined
                                    dense
                                    placeholder="Buscar…"
                                    name="search-authorization"
                                    autocomplete="off"
                                    aria-label="Buscar rendiciones pendientes"
                                    class="input-theme"
                                    style="min-width: 200px;"
                                    clearable
                                >
                                    <template v-slot:prepend>
                                        <q-icon name="search" aria-hidden="true" />
                                    </template>
                                </q-input>
                            </div>
                            <div data-tour="view-mode" v-if="activeTab === 'pending'" class="col-auto q-gutter-xs">
                                <q-btn
                                    :flat="viewMode !== 'grid'"
                                    :unelevated="viewMode === 'grid'"
                                    :color="viewMode === 'grid' ? 'secondary' : 'grey'"
                                    dense round size="sm" icon="eva-grid-outline"
                                    aria-label="Vista de tarjetas"
                                    @click="viewMode = 'grid'"
                                >
                                    <q-tooltip>Vista de tarjetas</q-tooltip>
                                </q-btn>
                                <q-btn
                                    :flat="viewMode !== 'table'"
                                    :unelevated="viewMode === 'table'"
                                    :color="viewMode === 'table' ? 'secondary' : 'grey'"
                                    dense round size="sm" icon="eva-list-outline"
                                    aria-label="Vista de tabla"
                                    @click="viewMode = 'table'"
                                >
                                    <q-tooltip>Vista de tabla</q-tooltip>
                                </q-btn>
                            </div>
                        </div>
                    </q-card-section>

                    <q-separator />

                    <!-- ── Pendientes ─────────────────────────────────── -->
                    <q-card-section v-if="activeTab === 'pending'">
                        <div v-if="filteredPending.length === 0" class="text-center q-pa-xl">
                            <q-icon name="receipt_long" size="64px" color="grey-4" aria-hidden="true" />
                            <div class="text-body1 text-grey q-mt-md">
                                {{ filter ? 'Sin resultados para la búsqueda' : 'No hay rendiciones pendientes de autorización' }}
                            </div>
                            <q-btn v-if="filter" flat no-caps color="primary" label="Limpiar búsqueda" class="q-mt-sm"
                                @click="filter = ''" />
                            <q-btn v-else flat no-caps color="primary" icon="task_alt" label="Ver autorizadas" class="q-mt-sm"
                                @click="activeTab = 'authorized'" />
                        </div>

                        <!-- Aprobación en lote: solo niveles intermedios (la autorización final se revisa en el detalle) -->
                        <div v-if="filteredPending.length && intermediateRows.length" data-tour="bulk-approve"
                            class="row items-center q-col-gutter-sm q-mb-md">
                            <div class="col-auto">
                                <q-checkbox
                                    :model-value="allIntermediateSelected"
                                    toggle-indeterminate
                                    dense
                                    :label="selectedIds.length
                                        ? `${selectedIds.length} seleccionada${selectedIds.length !== 1 ? 's' : ''}`
                                        : `Seleccionar niveles intermedios (${intermediateRows.length})`"
                                    @update:model-value="HandleToggleAllIntermediate"
                                />
                            </div>
                            <div class="col text-caption text-grey">
                                Solo se aprueban en lote los niveles intermedios; la autorización final se hace en el detalle de cada rendición.
                            </div>
                            <div v-if="selectedIds.length" class="col-auto">
                                <q-btn unelevated no-caps color="positive" icon="done_all"
                                    :label="`Aprobar nivel (${selectedIds.length})`" :loading="bulkBusy"
                                    @click="HandleBulkApprove(selectedIds)" />
                            </div>
                        </div>

                        <!-- Tarjetas -->
                        <div data-tour="pending-list" v-if="filteredPending.length && viewMode === 'grid'" class="row q-col-gutter-md">
                            <div v-for="row in sortedPendingForGrid" :key="row.id" class="col-xs-12 col-sm-6 col-md-4">
                                <q-card data-tour="pending-card" flat bordered class="acc-card full-height cursor-pointer"
                                    :class="{ 'acc-card--selected': selectedIds.includes(row.id) }"
                                    role="link" tabindex="0" :aria-label="`Abrir rendición #${row.id}`"
                                    @click="HandleDetailAccountability(row.id)"
                                    @keyup.enter.self="HandleDetailAccountability(row.id)">
                                    <q-card-section class="q-pb-sm">
                                        <div class="row items-start no-wrap q-gutter-sm">
                                            <div v-if="row.is_intermediate" class="col-auto" @click.stop>
                                                <q-checkbox :model-value="selectedIds.includes(row.id)" dense
                                                    :aria-label="`Seleccionar rendición #${row.id} para aprobar el nivel`"
                                                    @update:model-value="HandleToggleSelect(row.id)" />
                                            </div>
                                            <div class="col" style="min-width: 0;">
                                                <div class="text-body2 text-weight-medium ellipsis">
                                                    {{ row.description || 'Sin descripción' }}
                                                </div>
                                                <div class="text-caption text-grey q-mt-xs ellipsis">
                                                    Rendición #{{ row.id }} &middot; {{ row.profile?.name || '-' }}
                                                </div>
                                            </div>
                                            <div data-tour="aging-badge" class="col-auto">
                                                <q-badge :color="agingColor(row)" class="q-pa-xs">
                                                    {{ agingLabel(row) }}
                                                    <q-tooltip>{{ sentTooltip(row) }}</q-tooltip>
                                                </q-badge>
                                            </div>
                                        </div>
                                    </q-card-section>

                                    <q-separator inset />

                                    <q-card-section class="q-py-sm">
                                        <q-list dense>
                                            <q-item class="q-px-none">
                                                <q-item-section avatar>
                                                    <q-icon name="badge" size="xs" color="grey" aria-hidden="true" />
                                                </q-item-section>
                                                <q-item-section>
                                                    <q-item-label caption>Realizado por</q-item-label>
                                                    <q-item-label class="text-caption ellipsis">{{ row.user?.name || '-' }}</q-item-label>
                                                </q-item-section>
                                            </q-item>
                                            <q-item class="q-px-none">
                                                <q-item-section avatar>
                                                    <q-icon name="account_tree" size="xs" color="grey" aria-hidden="true" />
                                                </q-item-section>
                                                <q-item-section>
                                                    <q-item-label caption>Nivel actual</q-item-label>
                                                    <q-item-label class="text-caption ellipsis">{{ row.level_label || 'Autorizador directo' }}</q-item-label>
                                                </q-item-section>
                                            </q-item>
                                            <q-item class="q-px-none">
                                                <q-item-section avatar>
                                                    <q-icon name="calendar_today" size="xs" color="grey" aria-hidden="true" />
                                                </q-item-section>
                                                <q-item-section>
                                                    <q-item-label caption>Enviada</q-item-label>
                                                    <q-item-label class="text-caption">{{ row.submitted_label || '-' }}</q-item-label>
                                                </q-item-section>
                                            </q-item>
                                        </q-list>
                                    </q-card-section>

                                    <q-separator />

                                    <q-card-section class="q-py-sm">
                                        <div class="row items-center q-col-gutter-xs">
                                            <div class="col">
                                                <div class="text-caption text-grey">Recibido / Gastado</div>
                                                <div class="text-body2 text-weight-medium stat-number">
                                                    {{ money(row.total, row) }} / {{ money(row.spent, row) }}
                                                </div>
                                                <div class="text-caption stat-number" :class="`text-${balanceInfo(row.total, row.spent).color}`">
                                                    {{ balanceInfo(row.total, row.spent).label }}: {{ money(balanceInfo(row.total, row.spent).balance, row) }}
                                                </div>
                                            </div>
                                            <div data-tour="card-actions" class="col-auto q-gutter-xs" @click.stop>
                                                <q-btn v-if="row.is_intermediate" flat round dense size="sm" icon="check" color="positive"
                                                    aria-label="Aprobar nivel" :disable="bulkBusy" @click="HandleBulkApprove([row.id])">
                                                    <q-tooltip>Aprobar nivel</q-tooltip>
                                                </q-btn>
                                                <q-btn flat round dense size="sm" icon="visibility" color="primary"
                                                    aria-label="Ver detalle de rendición" @click="HandleDetailAccountability(row.id)">
                                                    <q-tooltip>Ver detalle</q-tooltip>
                                                </q-btn>
                                                <q-btn flat round dense size="sm" icon="edit" color="secondary"
                                                    aria-label="Editar encabezado de la rendición" @click="HandleEditAccountability(row.id)">
                                                    <q-tooltip>Editar encabezado</q-tooltip>
                                                </q-btn>
                                            </div>
                                        </div>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </div>

                        <!-- Tabla -->
                        <q-table data-tour="pending-list"
                            v-else-if="filteredPending.length"
                            :rows="filteredPending"
                            :columns="pendingColumns"
                            row-key="id"
                            class="table-theme"
                            flat
                            bordered
                            separator="horizontal"
                            :table-header-class="{ 'table-header-theme': true }"
                            :pagination="{ rowsPerPage: 15, sortBy: 'submitted', descending: false }"
                            rows-per-page-label="Filas por página"
                            :pagination-label="(start, end, total) => `${start}-${end} de ${total}`"
                        >
                            <template v-slot:body="props">
                                <q-tr :props="props" class="cursor-pointer"
                                    :class="{ 'acc-row--selected': selectedIds.includes(props.row.id) }"
                                    @click="HandleDetailAccountability(props.row.id)">
                                    <q-td key="select" :props="props" @click.stop>
                                        <q-checkbox v-if="props.row.is_intermediate" :model-value="selectedIds.includes(props.row.id)" dense
                                            :aria-label="`Seleccionar rendición #${props.row.id} para aprobar el nivel`"
                                            @update:model-value="HandleToggleSelect(props.row.id)" />
                                    </q-td>
                                    <q-td key="id" :props="props">{{ props.row.id }}</q-td>
                                    <q-td key="description" :props="props">
                                        <span class="ellipsis" style="max-width: 200px; display: inline-block;">
                                            {{ props.row.description || '-' }}
                                        </span>
                                    </q-td>
                                    <q-td key="user_name" :props="props">{{ props.row.user?.name || '-' }}</q-td>
                                    <q-td key="profile" :props="props">{{ props.row.profile?.name || '-' }}</q-td>
                                    <q-td key="level" :props="props">{{ props.row.level_label || 'Autorizador directo' }}</q-td>
                                    <q-td key="submitted" :props="props">
                                        <q-badge :color="agingColor(props.row)" :label="agingLabel(props.row)" />
                                        <div class="text-caption text-grey">{{ props.row.submitted_label || '-' }}</div>
                                        <q-tooltip>{{ sentTooltip(props.row) }}</q-tooltip>
                                    </q-td>
                                    <q-td key="total" :props="props" class="stat-number">{{ money(props.row.total, props.row) }}</q-td>
                                    <q-td key="spent" :props="props" class="stat-number">{{ money(props.row.spent, props.row) }}</q-td>
                                    <q-td key="balance" :props="props" class="stat-number"
                                        :class="`text-${balanceInfo(props.row.total, props.row.spent).color}`">
                                        {{ money(balanceInfo(props.row.total, props.row.spent).balance, props.row) }}
                                        <q-tooltip>{{ balanceInfo(props.row.total, props.row.spent).label }}</q-tooltip>
                                    </q-td>
                                    <q-td key="actions" :props="props" @click.stop>
                                        <div class="q-gutter-xs no-wrap">
                                            <q-btn v-if="props.row.is_intermediate" flat round dense size="sm" icon="check" color="positive"
                                                aria-label="Aprobar nivel" :disable="bulkBusy" @click="HandleBulkApprove([props.row.id])">
                                                <q-tooltip>Aprobar nivel</q-tooltip>
                                            </q-btn>
                                            <q-btn flat round dense size="sm" icon="visibility" color="primary"
                                                aria-label="Ver detalle de rendición" @click="HandleDetailAccountability(props.row.id)">
                                                <q-tooltip>Ver detalle</q-tooltip>
                                            </q-btn>
                                            <q-btn flat round dense size="sm" icon="edit" color="secondary"
                                                aria-label="Editar encabezado de la rendición" @click="HandleEditAccountability(props.row.id)">
                                                <q-tooltip>Editar encabezado</q-tooltip>
                                            </q-btn>
                                        </div>
                                    </q-td>
                                </q-tr>
                            </template>
                        </q-table>
                    </q-card-section>

                    <!-- ── Autorizadas (paginado en el servidor) ─────── -->
                    <q-card-section v-else>
                        <div data-tour="authorized-filters" class="row q-col-gutter-sm q-mb-md">
                            <div class="col-xs-12 col-sm-5">
                                <q-input
                                    v-model="authFilters.search"
                                    outlined dense clearable debounce="400"
                                    placeholder="Buscar por descripción, empleado, usuario, N° o asiento…"
                                    class="input-theme"
                                    aria-label="Buscar rendiciones autorizadas"
                                >
                                    <template v-slot:prepend><q-icon name="search" aria-hidden="true" /></template>
                                </q-input>
                            </div>
                            <div class="col-xs-6 col-sm-4">
                                <q-select
                                    v-model="authFilters.profile_id"
                                    :options="profileOptions"
                                    emit-value map-options clearable outlined dense
                                    label="Perfil"
                                    class="input-theme"
                                />
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <q-select
                                    v-model="authFilters.sap"
                                    :options="sapOptions"
                                    emit-value map-options outlined dense
                                    label="Estado SAP"
                                    class="input-theme"
                                />
                            </div>
                        </div>

                        <q-table data-tour="authorized-list"
                            :rows="authorizedRows"
                            :columns="authorizedColumns"
                            row-key="id"
                            class="table-theme"
                            flat
                            bordered
                            separator="horizontal"
                            :table-header-class="{ 'table-header-theme': true }"
                            v-model:pagination="authPagination"
                            :rows-per-page-options="[10, 15, 25, 50]"
                            :loading="authLoading"
                            binary-state-sort
                            rows-per-page-label="Filas por página"
                            :pagination-label="(start, end, total) => `${start}-${end} de ${total}`"
                            no-data-label="No hay rendiciones autorizadas"
                            @request="HandleLoadAuthorized"
                        >
                            <template v-slot:body="props">
                                <q-tr :props="props" class="cursor-pointer" @click="HandleDetailAccountability(props.row.id)">
                                    <q-td key="id" :props="props">{{ props.row.id }}</q-td>
                                    <q-td key="description" :props="props">
                                        <span class="ellipsis" style="max-width: 200px; display: inline-block;">
                                            {{ props.row.description || '-' }}
                                        </span>
                                    </q-td>
                                    <q-td key="user_name" :props="props">{{ props.row.user?.name || '-' }}</q-td>
                                    <q-td key="profile" :props="props">{{ props.row.profile?.name || '-' }}</q-td>
                                    <q-td key="end_date" :props="props">{{ props.row.end_date || '-' }}</q-td>
                                    <q-td key="total" :props="props" class="stat-number">{{ money(props.row.total, props.row) }}</q-td>
                                    <q-td key="spent" :props="props" class="stat-number">{{ money(props.row.spent, props.row) }}</q-td>
                                    <q-td key="balance" :props="props" class="stat-number"
                                        :class="`text-${balanceInfo(props.row.total, props.row.spent).color}`">
                                        {{ money(balanceInfo(props.row.total, props.row.spent).balance, props.row) }}
                                        <q-tooltip>{{ balanceInfo(props.row.total, props.row.spent).label }}</q-tooltip>
                                    </q-td>
                                    <q-td key="sap" :props="props">
                                        <q-badge :color="props.row.sap_exported ? 'positive' : 'warning'" outline
                                            :label="props.row.sap_exported
                                                ? (props.row.sap_trans_id ? `En SAP · N° ${props.row.sap_trans_id}` : 'En SAP')
                                                : 'Pendiente SAP'" />
                                    </q-td>
                                    <q-td key="actions" :props="props" @click.stop>
                                        <div class="q-gutter-xs no-wrap">
                                            <q-btn flat round dense size="sm" icon="visibility" color="primary"
                                                aria-label="Ver detalle de rendición" @click="HandleDetailAccountability(props.row.id)">
                                                <q-tooltip>Ver detalle</q-tooltip>
                                            </q-btn>
                                            <q-btn flat round dense size="sm" icon="print" color="secondary"
                                                aria-label="Imprimir reporte de rendición" @click="HandleGetReport(props.row.id)">
                                                <q-tooltip>Reporte</q-tooltip>
                                            </q-btn>
                                        </div>
                                    </q-td>
                                </q-tr>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>

            </div>
        </div>
        <EditHeaderDialog v-model="headerDialog" :accountability-id="headerId" from="index" />
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import EditHeaderDialog from "@/components/authorization/EditHeaderDialog.vue";
import PageHeader from "@/components/PageHeader.vue";
import { ref, computed, watch, onMounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { openURL, LocalStorage, useQuasar } from "quasar";
import axios from "axios";
import { formatMoney, balanceInfo } from "@/utils/accountability";

const props = defineProps({
    title: String,
    data: { type: Array, default: () => [] },
    is_admin: Boolean,
    show_all: Boolean,
    profiles: { type: Array, default: () => [] },
});

const $q = useQuasar();
const page = usePage();
const pendingRows = computed(() => props.data ?? []);
const isAdmin = computed(() => props.is_admin);
const showAll = computed(() => props.show_all);
const filter = ref("");
// La vista elegida (tarjetas/tabla) se recuerda entre visitas
const viewMode = ref(LocalStorage.getItem("authorization-view-mode") || "grid");
watch(viewMode, (mode) => LocalStorage.set("authorization-view-mode", mode));
const activeTab = ref("pending");

function money(value, row) {
    return formatMoney(value, row?.profile?.type_currency);
}

// ── Antigüedad del envío: más de 5 días en rojo, más de 2 en ámbar ──
function agingColor(row) {
    const days = row.days_waiting;
    if (days === null || days === undefined) return "grey";
    if (days > 5) return "negative";
    if (days > 2) return "warning";
    return "positive";
}

function agingLabel(row) {
    const days = row.days_waiting;
    if (days === null || days === undefined) return "Sin fecha";
    if (days === 0) return "Hoy";
    return `Hace ${days} día${days !== 1 ? "s" : ""}`;
}

function sentTooltip(row) {
    if (!row.submitted_label) return "Sin fecha de envío";
    return row.submitted_source === "envio"
        ? `Enviada a autorización el ${row.submitted_label}`
        : `Fecha aproximada (última modificación): ${row.submitted_label}`;
}

// ── Pendientes ──────────────────────────────────────────────
const pendingColumns = [
    { name: "select", align: "center", label: "", field: "is_intermediate", style: "width: 40px" },
    { name: "id", align: "center", label: "ID", field: "id", sortable: true, style: "width: 60px" },
    { name: "description", align: "left", label: "Descripción", field: "description", sortable: true },
    { name: "user_name", align: "left", label: "Realizado por", field: (row) => row.user?.name, sortable: true },
    { name: "profile", align: "left", label: "Perfil", field: (row) => row.profile?.name, sortable: true },
    { name: "level", align: "left", label: "Nivel actual", field: "level_label", sortable: true },
    { name: "submitted", align: "left", label: "Enviada", field: (row) => row.submitted_ts ?? 0, sortable: true },
    { name: "total", align: "right", label: "Recibido", field: (row) => Number(row.total ?? 0), sortable: true },
    { name: "spent", align: "right", label: "Gastado", field: (row) => Number(row.spent ?? 0), sortable: true },
    { name: "balance", align: "right", label: "Saldo", field: (row) => Number(row.total ?? 0) - Number(row.spent ?? 0), sortable: true },
    { name: "actions", align: "center", label: "Acciones", field: "actions" },
];

const filteredPending = computed(() => {
    if (!filter.value) return pendingRows.value;
    const needle = filter.value.toLowerCase();
    return pendingRows.value.filter((row) =>
        [row.description, row.employee_name, row.account_alias, row.account_name, row.user?.name,
            row.profile?.name, row.level_label, row.id]
            .some((v) => v !== null && v !== undefined && String(v).toLowerCase().includes(needle))
    );
});

// En tarjetas, primero las que llevan más tiempo esperando
const sortedPendingForGrid = computed(() =>
    [...filteredPending.value].sort((a, b) => (a.submitted_ts ?? 0) - (b.submitted_ts ?? 0))
);

// ── Aprobación en lote de niveles intermedios ───────────────
// La autorización final no se ofrece aquí: exporta a SAP y requiere revisar el detalle
const selectedIds = ref([]);
const bulkBusy = ref(false);
const intermediateRows = computed(() => filteredPending.value.filter((row) => row.is_intermediate));
// Si cambia la búsqueda o los datos, solo quedan seleccionadas las visibles
watch(intermediateRows, (rows) => {
    const visible = new Set(rows.map((row) => row.id));
    selectedIds.value = selectedIds.value.filter((id) => visible.has(id));
});
const allIntermediateSelected = computed(() => {
    if (!selectedIds.value.length) return false;
    return selectedIds.value.length === intermediateRows.value.length ? true : null;
});

function HandleToggleSelect(id) {
    selectedIds.value = selectedIds.value.includes(id)
        ? selectedIds.value.filter((value) => value !== id)
        : [...selectedIds.value, id];
}

function HandleToggleAllIntermediate() {
    selectedIds.value = allIntermediateSelected.value === true ? [] : intermediateRows.value.map((row) => row.id);
}

function HandleBulkApprove(ids) {
    if (bulkBusy.value || !ids.length) return;
    const single = ids.length === 1;
    $q.dialog({
        title: "Aprobar nivel",
        message: single
            ? `La rendición #${ids[0]} avanzará al siguiente nivel de autorización.`
            : `Las ${ids.length} rendiciones seleccionadas avanzarán al siguiente nivel de autorización.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Aprobar nivel", color: "positive", noCaps: true },
        persistent: true,
        prompt: { model: "", type: "textarea", label: single ? "Comentario (opcional)" : "Comentario para todas (opcional)" },
    }).onOk((comments) => {
        router.post(
            route("panel.accountability.authorization.bulk-approve"),
            { ids: [...ids], comments: typeof comments === "string" && comments.trim() ? comments.trim() : null },
            {
                preserveScroll: true,
                onStart: () => (bulkBusy.value = true),
                onFinish: () => (bulkBusy.value = false),
                onSuccess: () => (selectedIds.value = []),
            }
        );
    });
}

// ── Ver todas (Administrador) ───────────────────────────────
const toggling = ref(false);
function HandleToggleAll(value) {
    router.get(
        route("panel.accountability.authorization.index"),
        value ? { all: 1 } : {},
        {
            preserveScroll: true,
            onStart: () => (toggling.value = true),
            onFinish: () => (toggling.value = false),
            onSuccess: () => HandleLoadAuthorized({ pagination: { ...authPagination.value, page: 1 } }),
        }
    );
}

// ── Autorizadas ─────────────────────────────────────────────
const authorizedColumns = [
    { name: "id", align: "center", label: "ID", field: "id", sortable: true, style: "width: 60px" },
    { name: "description", align: "left", label: "Descripción", field: "description", sortable: true },
    { name: "user_name", align: "left", label: "Realizado por", field: (row) => row.user?.name },
    { name: "profile", align: "left", label: "Perfil", field: (row) => row.profile?.name },
    { name: "end_date", align: "center", label: "Fecha fin", field: "end_date", sortable: true },
    { name: "total", align: "right", label: "Recibido", field: "total", sortable: true },
    { name: "spent", align: "right", label: "Gastado", field: "spent" },
    { name: "balance", align: "right", label: "Saldo", field: "spent" },
    { name: "sap", align: "left", label: "SAP", field: "sap_exported" },
    { name: "actions", align: "center", label: "Acciones", field: "actions" },
];

const profileOptions = computed(() => props.profiles.map((p) => ({ label: p.name, value: p.id })));
const sapOptions = [
    { label: "Todas", value: null },
    { label: "En SAP", value: "exported" },
    { label: "Pendiente SAP", value: "pending" },
];

const authorizedRows = ref([]);
const authorizedTotal = ref(null);
const authLoading = ref(false);
const authFilters = ref({ search: "", profile_id: null, sap: null });
const authPagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0, sortBy: "id", descending: true });
let authRequest = 0;

async function HandleLoadAuthorized({ pagination }) {
    const { page: p, rowsPerPage, sortBy, descending } = pagination;
    const current = ++authRequest;
    authLoading.value = true;
    try {
        const res = await axios.get(route("panel.accountability.authorization.authorized"), {
            headers: { Accept: "application/json" },
            params: {
                page: p,
                per_page: rowsPerPage,
                sort: sortBy || "id",
                descending: descending ? "true" : "false",
                search: authFilters.value.search || undefined,
                profile_id: authFilters.value.profile_id || undefined,
                sap: authFilters.value.sap || undefined,
                all: showAll.value ? 1 : undefined,
            },
        });
        // Una respuesta vieja (filtros cambiados mientras cargaba) no pisa la nueva
        if (current !== authRequest) return;
        authorizedRows.value = res.data.data;
        authorizedTotal.value = res.data.total;
        authPagination.value = { page: res.data.current_page, rowsPerPage, sortBy, descending, rowsNumber: res.data.total };
    } catch {
        if (current === authRequest) {
            $q.notify({ type: "negative", message: "No se pudieron cargar las rendiciones autorizadas." });
        }
    } finally {
        if (current === authRequest) authLoading.value = false;
    }
}

watch(authFilters, () => HandleLoadAuthorized({ pagination: { ...authPagination.value, page: 1 } }), { deep: true });

onMounted(() => {
    HandleLoadAuthorized({ pagination: authPagination.value });
    // Tras autorizar y exportar, el número de asiento se muestra en un diálogo para anotarlo
    // (el manejador global de flash no notifica cuando hay sap_number)
    if (page.props.flash?.sap_number) {
        $q.dialog({
            title: "Rendición exportada a SAP",
            message: `${page.props.flash.message}.`,
            ok: { label: "Aceptar", color: "primary", noCaps: true },
        });
    }
});

// La cabecera se edita en un diálogo, sin salir de la bandeja
const headerDialog = ref(false);
const headerId = ref(null);
function HandleEditAccountability(id) {
    headerId.value = id;
    headerDialog.value = true;
}

function HandleDetailAccountability(id) {
    router.visit(route("panel.accountability.authorization.detail.index", id));
}

function HandleGetReport(id) {
    openURL(route("panel.accountability.authorization.report", id));
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

.acc-card--selected {
    outline: 2px solid var(--q-positive);
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
