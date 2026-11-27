<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">
            <PageHeader
                title="Reporte de Rendiciones"
                subtitle="Consulta rendiciones por área, usuario, perfil, estado y fechas"
                icon="eva-bar-chart-outline"
            >
                <template #actions>
                    <q-btn data-tour="export-excel"
                        outline
                        color="positive"
                        icon="eva-download-outline"
                        label="Exportar a Excel"
                        no-caps
                        :disable="data.length === 0"
                        :href="exportUrl"
                        type="a"
                    >
                        <q-tooltip>Descarga el reporte con los filtros aplicados</q-tooltip>
                    </q-btn>
                </template>
            </PageHeader>

            <!-- Filtros -->
            <q-card data-tour="report-filters" class="card-form q-mb-md">
                <div class="q-pa-md">
                    <div class="text-subtitle1 text-weight-medium q-mb-md">Filtros</div>
                    <div class="row q-col-gutter-md">
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-label">Sucursal / Área</div>
                            <q-select
                                v-model="filters.area_id"
                                :options="areas"
                                option-value="value"
                                option-label="label"
                                emit-value
                                map-options
                                dense
                                outlined
                                class="input-theme"
                                clearable
                                @update:model-value="onAreaChange"
                            />
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-label">Usuario</div>
                            <q-select
                                v-model="filters.user_id"
                                :options="filteredUsers"
                                option-value="value"
                                option-label="label"
                                emit-value
                                map-options
                                dense
                                outlined
                                class="input-theme"
                                clearable
                                use-input
                                input-debounce="0"
                                @filter="filterUsers"
                            />
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-2">
                            <div class="form-label">Fecha Desde</div>
                            <q-input
                                v-model="filters.date_from"
                                dense
                                outlined
                                class="input-theme"
                                type="date"
                                clearable
                            />
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-2">
                            <div class="form-label">Fecha Hasta</div>
                            <q-input
                                v-model="filters.date_to"
                                dense
                                outlined
                                class="input-theme"
                                type="date"
                                clearable
                            />
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-2">
                            <div class="form-label">Estado SAP</div>
                            <q-select
                                v-model="filters.sap_status"
                                :options="sapStatusOptions"
                                option-value="value"
                                option-label="label"
                                emit-value
                                map-options
                                dense
                                outlined
                                class="input-theme"
                                clearable
                            />
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-2 flex items-end">
                            <div class="row q-gutter-sm full-width">
                                <q-btn data-tour="report-search"
                                    color="primary"
                                    label="Buscar"
                                    icon="eva-search-outline"
                                    size="12px"
                                    no-caps
                                    class="col"
                                    @click="HandleSearch()"
                                />
                                <q-btn
                                    color="secondary"
                                    label="Limpiar"
                                    icon="eva-close-outline"
                                    size="12px"
                                    no-caps
                                    flat
                                    class="col"
                                    @click="HandleClear()"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </q-card>

            <!-- Tabla de resultados -->
            <q-card class="card-form">
                <q-table data-tour="report-table"
                    :rows="data"
                    :columns="table.columns"
                    row-key="id"
                    class="table-theme"
                    :filter="table.filter"
                    separator="horizontal"
                    flat
                    :table-header-class="{ 'table-header-theme': true }"
                    :pagination="{ rowsPerPage: 15 }"
                    :grid="$q.screen.xs"
                >
                    <template v-slot:top>
                        <div class="text-subtitle1 text-weight-medium">Resultados</div>
                        <q-space />
                        <div data-tour="report-totals" v-if="data.length > 0" class="row q-gutter-xs q-mr-md">
                            <q-chip
                                v-for="item in totalsByCurrency"
                                :key="item.currency"
                                dense
                                color="blue-grey-1"
                                text-color="blue-grey-9"
                                class="text-weight-bold"
                            >
                                Total {{ formatMoney(item.total, item.currency) }}
                            </q-chip>
                        </div>
                        <q-input
                            outlined
                            dense
                            clearable
                            placeholder="Buscar…"
                            v-model="table.filter"
                            class="input-theme search-input"
                        >
                            <template v-slot:append>
                                <q-icon name="search" />
                            </template>
                        </q-input>
                    </template>

                    <template v-slot:header="props">
                        <q-tr :props="props">
                            <q-th v-for="col in props.cols" :key="col.name" :props="props">
                                {{ col.label }}
                            </q-th>
                        </q-tr>
                    </template>

                    <template v-slot:body="props">
                        <q-tr :props="props" class="cursor-pointer" @click="HandleOpenDetail(props.row)">
                            <q-td key="id" :props="props">{{ props.row.id }}</q-td>
                            <q-td key="user_name" :props="props">{{ props.row.user_name }}</q-td>
                            <q-td key="area_name" :props="props">
                                <q-badge v-if="props.row.area_name" color="blue-grey" :label="props.row.area_name" />
                                <span v-else class="text-grey-5">—</span>
                            </q-td>
                            <q-td key="profile_name" :props="props">{{ props.row.profile_name }}</q-td>
                            <q-td key="employee_name" :props="props">{{ props.row.employee_name }}</q-td>
                            <q-td key="description" :props="props">{{ props.row.description }}</q-td>
                            <q-td key="start_date" :props="props">{{ props.row.start_date }}</q-td>
                            <q-td key="end_date" :props="props">{{ props.row.end_date }}</q-td>
                            <q-td key="total" :props="props" class="text-right" style="font-variant-numeric: tabular-nums">
                                {{ formatMoney(props.row.total, props.row.type_currency) }}
                            </q-td>
                            <q-td key="status" :props="props">
                                <q-badge
                                    :color="statusColor(props.row.status)"
                                    :label="statusLabel(props.row.status)"
                                />
                            </q-td>
                            <q-td key="sap_location" :props="props">
                                <q-badge
                                    :color="sapLocationColor(props.row)"
                                    :label="sapLocationLabel(props.row)"
                                />
                            </q-td>
                        </q-tr>
                    </template>

                    <template v-slot:item="props">
                        <div class="col-12 q-pa-xs">
                            <q-card flat bordered class="card-grid cursor-pointer" @click="HandleOpenDetail(props.row)">
                                <q-card-section class="q-pb-xs">
                                    <div class="row items-center no-wrap">
                                        <div class="col text-weight-medium ellipsis">N° {{ props.row.id }} · {{ props.row.description }}</div>
                                        <q-badge :color="statusColor(props.row.status)" :label="statusLabel(props.row.status)" />
                                    </div>
                                    <div class="text-caption text-grey-7">{{ props.row.user_name }} · {{ props.row.profile_name }}</div>
                                    <div class="text-caption text-grey">{{ props.row.start_date }} — {{ props.row.end_date }}</div>
                                </q-card-section>
                                <q-card-section class="q-pt-none row items-center">
                                    <q-badge :color="sapLocationColor(props.row)" :label="sapLocationLabel(props.row)" />
                                    <q-space />
                                    <span class="text-weight-bold">{{ formatMoney(props.row.total, props.row.type_currency) }}</span>
                                </q-card-section>
                            </q-card>
                        </div>
                    </template>

                    <template v-slot:no-data>
                        <div class="full-width text-center q-pa-md text-grey-6" style="font-size:13px">
                            {{ searched ? 'Sin resultados para los filtros aplicados' : 'Aplica los filtros y presiona Buscar' }}
                        </div>
                    </template>
                </q-table>
            </q-card>

            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { formatMoney, currencySymbol, statusColor, statusLabel } from "@/utils/accountability";

defineProps({
    title: String,
});

const page = usePage();

const areas = ref(page.props.areas);
const allUsers = ref(page.props.users);
const data = ref(page.props.data);

const initialFilters = page.props.filters ?? {};
const filters = ref({
    area_id:    initialFilters.area_id    ?? null,
    user_id:    initialFilters.user_id    ?? null,
    date_from:  initialFilters.date_from  ?? null,
    date_to:    initialFilters.date_to    ?? null,
    sap_status: initialFilters.sap_status ?? null,
});

const sapStatusOptions = [
    { value: 'exported',       label: 'En ambos sistemas'      },
    { value: 'pending',        label: 'Autorizado (sin SAP)'   },
    { value: 'not_authorized', label: 'Solo en Rendiciones'    },
];

const searched = ref(Object.values(initialFilters).some(v => v));
const table = ref({
    filter: "",
    columns: [
        { name: "id",           align: "center", label: "ID",            field: "id",           sortable: true },
        { name: "user_name",    align: "left",   label: "Usuario",       field: "user_name",    sortable: true },
        { name: "area_name",    align: "center", label: "Sucursal/Área", field: "area_name",    sortable: true },
        { name: "profile_name", align: "center", label: "Perfil",        field: "profile_name", sortable: true },
        { name: "employee_name",align: "left",   label: "Empleado",      field: "employee_name",sortable: true },
        { name: "description",  align: "left",   label: "Descripción",   field: "description",  sortable: true },
        { name: "start_date",   align: "center", label: "Fecha Inicio",  field: "start_date",   sortable: true },
        { name: "end_date",     align: "center", label: "Fecha Fin",     field: "end_date",     sortable: true },
        { name: "total",        align: "right",  label: "Monto",         field: "total",        sortable: true, sort: (a, b) => Number(a) - Number(b) },
        { name: "status",       align: "center", label: "Estado",        field: "status",       sortable: true },
        { name: "sap_location", align: "center", label: "Ubicación",      field: "sap_location", sortable: false },
    ],
});

// Usuarios filtrados por área seleccionada
const usersForArea = computed(() => {
    if (!filters.value.area_id) return allUsers.value;
    return allUsers.value.filter(u => u.area_id == filters.value.area_id);
});

// Búsqueda local dentro del select de usuarios
const userSearchText = ref("");
const filteredUsers = computed(() => {
    const base = usersForArea.value;
    if (!userSearchText.value) return base;
    const needle = userSearchText.value.toLowerCase();
    return base.filter(u => u.label.toLowerCase().includes(needle));
});

function filterUsers(val, update) {
    userSearchText.value = val;
    update();
}

function onAreaChange() {
    // Si el usuario seleccionado no pertenece al área nueva, lo limpiamos
    if (filters.value.user_id) {
        const stillValid = usersForArea.value.some(u => u.value == filters.value.user_id);
        if (!stillValid) filters.value.user_id = null;
    }
}

function HandleClear() {
    filters.value = { area_id: null, user_id: null, date_from: null, date_to: null, sap_status: null };
    router.get(route("panel.report.accountability"), {}, {
        preserveState: true,
        onSuccess: () => {
            data.value = page.props.data;
            searched.value = false;
        },
    });
}

function HandleSearch() {
    router.get(route("panel.report.accountability"), filters.value, {
        preserveState: true,
        onSuccess: () => {
            data.value = page.props.data;
            searched.value = true;
        },
    });
}

// Totales agrupados por moneda del perfil (no se suman bolivianos con dólares)
const totalsByCurrency = computed(() => {
    const groups = {};
    for (const row of data.value) {
        const key = currencySymbol(row.type_currency);
        groups[key] = groups[key] ?? { currency: row.type_currency, total: 0 };
        groups[key].total += Number(row.total ?? 0);
    }
    return Object.values(groups);
});

// Descarga Excel con los mismos filtros aplicados en la pantalla
const exportUrl = computed(() => {
    const params = Object.fromEntries(
        Object.entries(filters.value).filter(([, value]) => value !== null && value !== "")
    );
    return route("panel.report.accountability.export", params);
});

function HandleOpenDetail(row) {
    router.visit(route("panel.accountability.authorization.detail.index", row.id));
}

function sapLocationLabel(row) {
    if (row.status === 'Autorizado' && row.sap_exported) {
        return row.sap_trans_id ? `Rendiciones + SAP · N° ${row.sap_trans_id}` : 'Rendiciones + SAP';
    }
    if (row.status === 'Autorizado' && !row.sap_exported) return 'Pendiente SAP';
    return 'Solo Rendiciones';
}

function sapLocationColor(row) {
    if (row.status === 'Autorizado' && row.sap_exported) return 'positive';
    if (row.status === 'Autorizado' && !row.sap_exported) return 'warning';
    return 'blue-grey';
}
</script>
