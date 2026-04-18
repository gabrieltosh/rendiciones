<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">

                <!-- Header -->
                <q-card class="q-pa-md card-form q-mb-md">
                    <div class="row items-center q-col-gutter-sm">
                        <div class="col">
                            <div class="text-h6 title-form">
                                Autorizacion de Rendiciones
                            </div>
                            <div class="text-caption text-grey">
                                {{ pendingRows.length }} pendiente{{ pendingRows.length !== 1 ? 's' : '' }}
                                &middot;
                                {{ authorizedRows.length }} autorizada{{ authorizedRows.length !== 1 ? 's' : '' }}
                            </div>
                        </div>
                    </div>
                </q-card>

                <!-- Content Card -->
                <q-card class="card-form">
                    <!-- Tabs + Toolbar -->
                    <q-card-section class="q-pb-none">
                        <div class="row items-center q-col-gutter-sm">
                            <div class="col">
                                <q-tabs
                                    v-model="activeTab"
                                    dense
                                    no-caps
                                    active-color="primary"
                                    indicator-color="primary"
                                    align="left"
                                    narrow-indicator
                                >
                                    <q-tab
                                        name="pending"
                                        aria-label="Rendiciones pendientes de autorizacion"
                                    >
                                        <div class="row items-center q-gutter-xs no-wrap">
                                            <span>Pendientes</span>
                                            <q-badge
                                                v-if="pendingRows.length > 0"
                                                color="orange"
                                                :label="pendingRows.length"
                                                class="q-ml-xs"
                                            />
                                        </div>
                                    </q-tab>
                                    <q-tab
                                        name="authorized"
                                        aria-label="Rendiciones autorizadas"
                                    >
                                        <div class="row items-center q-gutter-xs no-wrap">
                                            <span>Autorizadas</span>
                                            <q-badge
                                                v-if="authorizedRows.length > 0"
                                                color="green"
                                                :label="authorizedRows.length"
                                                class="q-ml-xs"
                                            />
                                        </div>
                                    </q-tab>
                                </q-tabs>
                            </div>
                            <div class="col-auto">
                                <q-input
                                    v-model="filter"
                                    outlined
                                    dense
                                    placeholder="Buscar…"
                                    name="search-authorization"
                                    autocomplete="off"
                                    aria-label="Buscar rendiciones"
                                    class="input-theme"
                                    style="min-width: 200px;"
                                    clearable
                                >
                                    <template v-slot:prepend>
                                        <q-icon name="search" aria-hidden="true" />
                                    </template>
                                </q-input>
                            </div>
                            <div class="col-auto q-gutter-xs">
                                <q-btn
                                    :flat="viewMode !== 'grid'"
                                    :unelevated="viewMode === 'grid'"
                                    :color="viewMode === 'grid' ? 'secondary' : 'grey'"
                                    dense
                                    round
                                    size="sm"
                                    icon="eva-grid-outline"
                                    aria-label="Vista de tarjetas"
                                    @click="viewMode = 'grid'"
                                >
                                    <q-tooltip>Vista de tarjetas</q-tooltip>
                                </q-btn>
                                <q-btn
                                    :flat="viewMode !== 'table'"
                                    :unelevated="viewMode === 'table'"
                                    :color="viewMode === 'table' ? 'secondary' : 'grey'"
                                    dense
                                    round
                                    size="sm"
                                    icon="eva-list-outline"
                                    aria-label="Vista de tabla"
                                    @click="viewMode = 'table'"
                                >
                                    <q-tooltip>Vista de tabla</q-tooltip>
                                </q-btn>
                            </div>
                        </div>
                    </q-card-section>

                    <q-separator />

                    <q-card-section>
                        <!-- Empty State -->
                        <div
                            v-if="filteredActiveRows.length === 0"
                            class="text-center q-pa-xl"
                        >
                            <q-icon
                                name="receipt_long"
                                size="64px"
                                color="grey-4"
                                aria-hidden="true"
                            />
                            <div class="text-body1 text-grey q-mt-md">
                                {{ filter
                                    ? 'Sin resultados para la busqueda'
                                    : activeTab === 'pending'
                                        ? 'No hay rendiciones pendientes de autorizacion'
                                        : 'No hay rendiciones autorizadas'
                                }}
                            </div>
                        </div>

                        <!-- Grid View -->
                        <div
                            v-else-if="viewMode === 'grid'"
                            class="row q-col-gutter-md"
                        >
                            <div
                                v-for="row in filteredActiveRows"
                                :key="row.id"
                                class="col-xs-12 col-sm-6 col-md-4"
                            >
                                <q-card flat bordered class="acc-card full-height">
                                    <q-card-section class="q-pb-sm">
                                        <div class="row items-start no-wrap q-gutter-sm">
                                            <div class="col" style="min-width: 0;">
                                                <div class="text-body2 text-weight-medium ellipsis">
                                                    {{ row.description || 'Sin descripcion' }}
                                                </div>
                                                <div class="text-caption text-grey q-mt-xs">
                                                    Rendicion #{{ row.id }}
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="column items-end q-gutter-xs">
                                                    <q-badge
                                                        :color="getStatusColor(row.status)"
                                                        :label="row.status || 'Pendiente'"
                                                        class="q-pa-xs"
                                                    />
                                                    <q-badge
                                                        v-if="activeTab === 'authorized'"
                                                        :color="getSapLocationColor(row)"
                                                        :label="getSapLocationLabel(row)"
                                                        class="q-pa-xs"
                                                        outline
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </q-card-section>

                                    <q-separator inset />

                                    <q-card-section class="q-py-sm">
                                        <q-list dense class="q-mx-none">
                                            <q-item class="q-px-none">
                                                <q-item-section avatar>
                                                    <q-icon
                                                        name="badge"
                                                        size="xs"
                                                        color="grey"
                                                        aria-hidden="true"
                                                    />
                                                </q-item-section>
                                                <q-item-section>
                                                    <q-item-label caption>Realizado por</q-item-label>
                                                    <q-item-label class="text-caption ellipsis">
                                                        {{ row.user?.name || '-' }}
                                                    </q-item-label>
                                                </q-item-section>
                                            </q-item>
                                            <q-item class="q-px-none">
                                                <q-item-section avatar>
                                                    <q-icon
                                                        name="person_outline"
                                                        size="xs"
                                                        color="grey"
                                                        aria-hidden="true"
                                                    />
                                                </q-item-section>
                                                <q-item-section>
                                                    <q-item-label class="text-caption ellipsis">
                                                        {{ row.employee_name || '-' }}
                                                    </q-item-label>
                                                </q-item-section>
                                            </q-item>
                                            <q-item class="q-px-none">
                                                <q-item-section avatar>
                                                    <q-icon
                                                        name="account_balance"
                                                        size="xs"
                                                        color="grey"
                                                        aria-hidden="true"
                                                    />
                                                </q-item-section>
                                                <q-item-section>
                                                    <q-item-label class="text-caption ellipsis">
                                                        {{ row.account_alias || row.account_name || '-' }}
                                                    </q-item-label>
                                                </q-item-section>
                                            </q-item>
                                            <q-item class="q-px-none">
                                                <q-item-section avatar>
                                                    <q-icon
                                                        name="calendar_today"
                                                        size="xs"
                                                        color="grey"
                                                        aria-hidden="true"
                                                    />
                                                </q-item-section>
                                                <q-item-section>
                                                    <q-item-label class="text-caption">
                                                        {{ row.start_date || '-' }}
                                                        <span v-if="row.end_date"> &mdash; {{ row.end_date }}</span>
                                                    </q-item-label>
                                                </q-item-section>
                                            </q-item>
                                        </q-list>
                                    </q-card-section>

                                    <q-separator />

                                    <q-card-section class="q-py-sm">
                                        <div class="row items-center">
                                            <div class="col">
                                                <div class="text-body2 text-weight-medium stat-number">
                                                    Bs {{ row.total || 0 }}
                                                </div>
                                            </div>
                                            <div class="col-auto q-gutter-xs">
                                                <q-btn
                                                    flat
                                                    round
                                                    dense
                                                    size="sm"
                                                    icon="visibility"
                                                    color="primary"
                                                    aria-label="Ver detalle de rendicion"
                                                    @click="HandleDetailAccountability(row.id)"
                                                >
                                                    <q-tooltip>Ver Detalle</q-tooltip>
                                                </q-btn>
                                                <q-btn
                                                    v-if="activeTab === 'pending'"
                                                    flat
                                                    round
                                                    dense
                                                    size="sm"
                                                    icon="edit"
                                                    color="secondary"
                                                    aria-label="Editar rendicion"
                                                    @click="HandleEditAccountability(row.id)"
                                                >
                                                    <q-tooltip>Editar</q-tooltip>
                                                </q-btn>
                                                <q-btn
                                                    v-if="activeTab === 'authorized'"
                                                    flat
                                                    round
                                                    dense
                                                    size="sm"
                                                    icon="print"
                                                    color="secondary"
                                                    aria-label="Imprimir reporte de rendicion"
                                                    @click="HandleGetReport(row.id)"
                                                >
                                                    <q-tooltip>Reporte</q-tooltip>
                                                </q-btn>
                                            </div>
                                        </div>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </div>

                        <!-- Table View -->
                        <q-table
                            v-else
                            :rows="filteredActiveRows"
                            :columns="columns"
                            row-key="id"
                            class="table-theme"
                            flat
                            bordered
                            separator="horizontal"
                            :table-header-class="{ 'table-header-theme': true }"
                            :pagination="{ rowsPerPage: 15 }"
                            hide-bottom-space
                        >
                            <template v-slot:body="props">
                                <q-tr :props="props">
                                    <q-td key="id" :props="props">
                                        {{ props.row.id }}
                                    </q-td>
                                    <q-td key="description" :props="props">
                                        <span class="ellipsis" style="max-width: 200px; display: inline-block;">
                                            {{ props.row.description || '-' }}
                                        </span>
                                    </q-td>
                                    <q-td key="user_name" :props="props">
                                        <span class="ellipsis" style="max-width: 150px; display: inline-block;">
                                            {{ props.row.user?.name || '-' }}
                                        </span>
                                    </q-td>
                                    <q-td key="employee_name" :props="props">
                                        <span class="ellipsis" style="max-width: 180px; display: inline-block;">
                                            {{ props.row.employee_name || '-' }}
                                        </span>
                                    </q-td>
                                    <q-td key="account_name" :props="props">
                                        <span class="ellipsis" style="max-width: 180px; display: inline-block;">
                                            {{ props.row.account_alias || props.row.account_name || '-' }}
                                        </span>
                                    </q-td>
                                    <q-td key="total" :props="props">
                                        <span class="stat-number text-weight-medium">
                                            Bs {{ props.row.total || 0 }}
                                        </span>
                                    </q-td>
                                    <q-td key="start_date" :props="props">
                                        {{ props.row.start_date || '-' }}
                                    </q-td>
                                    <q-td key="end_date" :props="props">
                                        {{ props.row.end_date || '-' }}
                                    </q-td>
                                    <q-td key="status" :props="props">
                                        <div class="column items-start q-gutter-xs">
                                            <q-badge
                                                :color="getStatusColor(props.row.status)"
                                                :label="props.row.status || 'Pendiente'"
                                            />
                                            <q-badge
                                                v-if="activeTab === 'authorized'"
                                                :color="getSapLocationColor(props.row)"
                                                :label="getSapLocationLabel(props.row)"
                                                outline
                                            />
                                        </div>
                                    </q-td>
                                    <q-td key="actions" :props="props">
                                        <div class="q-gutter-xs">
                                            <q-btn
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="visibility"
                                                color="primary"
                                                aria-label="Ver detalle de rendicion"
                                                @click="HandleDetailAccountability(props.row.id)"
                                            >
                                                <q-tooltip>Ver Detalle</q-tooltip>
                                            </q-btn>
                                            <q-btn
                                                v-if="activeTab === 'pending'"
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="edit"
                                                color="secondary"
                                                aria-label="Editar rendicion"
                                                @click="HandleEditAccountability(props.row.id)"
                                            >
                                                <q-tooltip>Editar</q-tooltip>
                                            </q-btn>
                                            <q-btn
                                                v-if="activeTab === 'authorized'"
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="print"
                                                color="secondary"
                                                aria-label="Imprimir reporte de rendicion"
                                                @click="HandleGetReport(props.row.id)"
                                            >
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
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { openURL } from "quasar";

defineProps({
    title: String,
    data: Object,
});

const page = usePage();
const pendingRows = ref(page.props.data);
const authorizedRows = ref(page.props.authorized);
const filter = ref("");
const viewMode = ref("grid");
const activeTab = ref("pending");

const columns = [
    {
        name: "id",
        align: "center",
        label: "ID",
        field: "id",
        sortable: true,
        style: "width: 60px",
    },
    {
        name: "description",
        align: "left",
        label: "Descripcion",
        field: "description",
        sortable: true,
    },
    {
        name: "user_name",
        align: "left",
        label: "Realizado por",
        field: (row) => row.user?.name,
        sortable: true,
    },
    {
        name: "employee_name",
        align: "left",
        label: "Empleado",
        field: "employee_name",
        sortable: true,
    },
    {
        name: "account_name",
        align: "left",
        label: "Cuenta",
        field: "account_name",
        sortable: true,
    },
    {
        name: "total",
        align: "right",
        label: "Monto",
        field: "total",
        sortable: true,
    },
    {
        name: "start_date",
        align: "center",
        label: "Inicio",
        field: "start_date",
    },
    {
        name: "end_date",
        align: "center",
        label: "Fin",
        field: "end_date",
    },
    {
        name: "status",
        align: "center",
        label: "Estado",
        field: "status",
        sortable: true,
    },
    {
        name: "actions",
        align: "center",
        label: "Acciones",
        field: "actions",
        style: "width: 120px",
    },
];

const activeRows = computed(() => {
    return activeTab.value === "pending"
        ? pendingRows.value
        : authorizedRows.value;
});

const filteredActiveRows = computed(() => {
    if (!filter.value) return activeRows.value;
    const needle = filter.value.toLowerCase();
    return activeRows.value.filter((row) => {
        return (
            (row.description && row.description.toLowerCase().includes(needle)) ||
            (row.employee_name && row.employee_name.toLowerCase().includes(needle)) ||
            (row.account_alias && row.account_alias.toLowerCase().includes(needle)) ||
            (row.account_name && row.account_name.toLowerCase().includes(needle)) ||
            (row.user?.name && row.user.name.toLowerCase().includes(needle)) ||
            (row.id && String(row.id).includes(needle))
        );
    });
});

function getStatusColor(status) {
    const colors = {
        Pendiente: "orange",
        Rechazado: "red",
        Anulado: "grey",
        Autorizado: "green",
    };
    return colors[status] || "blue-grey";
}

function getSapLocationLabel(row) {
    if (row.sap_exported) return 'En SAP';
    return 'Pendiente SAP';
}

function getSapLocationColor(row) {
    return row.sap_exported ? 'positive' : 'warning';
}

function HandleEditAccountability(id) {
    router.visit(
        route("panel.accountability.authorization.edit", id)
    );
}

function HandleDetailAccountability(id) {
    router.visit(
        route("panel.accountability.authorization.detail.index", id)
    );
}

function HandleGetReport(id) {
    openURL(
        route("panel.accountability.authorization.report", id)
    );
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
