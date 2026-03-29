<template>
    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">

            <!-- Filtros -->
            <q-card class="card-form q-mb-md">
                <div class="q-pa-md">
                    <h5 class="title-form q-mb-md">Filtros</h5>
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
                                <q-btn
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
                <q-table
                    :rows="data"
                    :columns="table.columns"
                    row-key="id"
                    class="table-theme"
                    :filter="table.filter"
                    separator="horizontal"
                    flat
                    bordered
                    :table-header-class="{ 'table-header-theme': true }"
                >
                    <template v-slot:top>
                        <h5 class="title-form">Reporte de Rendiciones</h5>
                        <q-space />
                        <div v-if="data.length > 0" class="q-mr-md text-weight-bold" style="font-size:12px">
                            Total: {{ totalAmount }}
                        </div>
                        <q-input
                            outlined
                            dense
                            color="primary"
                            placeholder="Buscar..."
                            v-model="table.filter"
                            class="input-theme"
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
                        <q-tr :props="props">
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
                            <q-td key="total" :props="props" class="text-right">
                                {{ formatAmount(props.row.total) }}
                            </q-td>
                            <q-td key="status" :props="props">
                                <q-badge
                                    :color="statusColor(props.row.status)"
                                    :label="props.row.status"
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

                    <template v-slot:no-data>
                        <div class="full-width text-center q-pa-md text-grey-6" style="font-size:13px">
                            {{ searched ? 'Sin resultados para los filtros aplicados' : 'Aplica los filtros y presiona Buscar' }}
                        </div>
                    </template>
                </q-table>
            </q-card>

        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

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
        { name: "total",        align: "right",  label: "Monto",         field: "total",        sortable: true },
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


const totalAmount = computed(() => {
    const sum = data.value.reduce((acc, row) => acc + parseFloat(row.total ?? 0), 0);
    return formatAmount(sum);
});

function formatAmount(val) {
    return Number(val ?? 0).toLocaleString("es-BO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function statusColor(status) {
    const map = { Pendiente: "orange", Rechazado: "red", Autorizado: "green", Anulado: "grey" };
    return map[status] ?? "grey";
}

function sapLocationLabel(row) {
    if (row.status === 'Autorizado' && row.sap_exported) return 'Rendiciones + SAP';
    if (row.status === 'Autorizado' && !row.sap_exported) return 'Pendiente SAP';
    return 'Solo Rendiciones';
}

function sapLocationColor(row) {
    if (row.status === 'Autorizado' && row.sap_exported) return 'positive';
    if (row.status === 'Autorizado' && !row.sap_exported) return 'warning';
    return 'blue-grey';
}
</script>
