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
                            <div class="form-label">Modelo</div>
                            <q-select
                                v-model="filters.model"
                                :options="models"
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
                        <div class="col-xs-12 col-sm-6 col-md-2">
                            <div class="form-label">Acción</div>
                            <q-select
                                v-model="filters.event"
                                :options="eventOptions"
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
                        <div class="col-xs-12 col-sm-12 col-md-1 flex items-end">
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

            <!-- Tabla -->
            <q-card class="card-form">
                <q-table
                    :rows="audits.data"
                    :columns="columns"
                    row-key="id"
                    class="table-theme"
                    separator="horizontal"
                    flat
                    bordered
                    :table-header-class="{ 'table-header-theme': true }"
                    :rows-per-page-options="[0]"
                    hide-pagination
                >
                    <template v-slot:top>
                        <h5 class="title-form">Log de Auditoría</h5>
                        <q-space />
                        <div class="text-caption text-grey">
                            {{ audits.total }} registros encontrados
                        </div>
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
                            <q-td key="created_at" :props="props">
                                <span class="text-caption">{{ props.row.created_at }}</span>
                            </q-td>
                            <q-td key="user" :props="props">{{ props.row.user }}</q-td>
                            <q-td key="event" :props="props">
                                <q-badge
                                    :color="eventColor(props.row.event)"
                                    :label="eventLabel(props.row.event)"
                                />
                            </q-td>
                            <q-td key="auditable_type" :props="props">
                                {{ props.row.auditable_type }}
                            </q-td>
                            <q-td key="auditable_id" :props="props" class="text-center">
                                {{ props.row.auditable_id }}
                            </q-td>
                            <q-td key="changes" :props="props">
                                <template v-if="props.row.event === 'updated' && hasChanges(props.row)">
                                    <div
                                        v-for="(val, field) in props.row.new_values"
                                        :key="field"
                                        class="row items-center q-gutter-xs q-mb-xs"
                                    >
                                        <q-badge color="grey-4" text-color="grey-8" :label="field" class="text-caption" />
                                        <span class="text-caption text-negative" style="text-decoration:line-through;">
                                            {{ props.row.old_values?.[field] ?? '—' }}
                                        </span>
                                        <q-icon name="arrow_forward" size="10px" color="grey" />
                                        <span class="text-caption text-positive">{{ val ?? '—' }}</span>
                                    </div>
                                </template>
                                <span v-else class="text-caption text-grey">—</span>
                            </q-td>
                            <q-td key="ip_address" :props="props">
                                <span class="text-caption text-grey">{{ props.row.ip_address ?? '—' }}</span>
                            </q-td>
                        </q-tr>
                    </template>

                    <template v-slot:no-data>
                        <div class="full-width text-center q-pa-md text-grey-6" style="font-size:13px">
                            {{ searched ? 'Sin resultados para los filtros aplicados' : 'Aplica los filtros y presiona Buscar' }}
                        </div>
                    </template>
                </q-table>

                <!-- Paginación -->
                <div v-if="audits.last_page > 1" class="row justify-center q-pa-md">
                    <q-pagination
                        v-model="currentPage"
                        :max="audits.last_page"
                        :max-pages="7"
                        boundary-numbers
                        color="primary"
                        @update:model-value="HandlePageChange"
                    />
                </div>
            </q-card>

        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

defineProps({ title: String });

const page = usePage();

const allUsers   = ref(page.props.users);
const models     = ref(page.props.models);
const audits     = ref(page.props.audits);

const initialFilters = page.props.filters ?? {};
const filters = ref({
    user_id:   initialFilters.user_id   ?? null,
    event:     initialFilters.event     ?? null,
    model:     initialFilters.model     ?? null,
    date_from: initialFilters.date_from ?? null,
    date_to:   initialFilters.date_to   ?? null,
});

const searched    = ref(Object.values(initialFilters).some(v => v));
const currentPage = ref(audits.value.current_page ?? 1);

const eventOptions = [
    { value: 'created', label: 'Creado'    },
    { value: 'updated', label: 'Modificado'},
    { value: 'deleted', label: 'Eliminado' },
];

const columns = [
    { name: 'created_at',    align: 'left',   label: 'Fecha',      field: 'created_at',    sortable: false },
    { name: 'user',          align: 'left',   label: 'Usuario',    field: 'user',          sortable: false },
    { name: 'event',         align: 'center', label: 'Acción',     field: 'event',         sortable: false },
    { name: 'auditable_type',align: 'left',   label: 'Modelo',     field: 'auditable_type',sortable: false },
    { name: 'auditable_id',  align: 'center', label: 'ID Registro',field: 'auditable_id',  sortable: false },
    { name: 'changes',       align: 'left',   label: 'Cambios',    field: 'changes',       sortable: false },
    { name: 'ip_address',    align: 'left',   label: 'IP',         field: 'ip_address',    sortable: false },
];

const userSearchText = ref('');
const filteredUsers = computed(() => {
    if (!userSearchText.value) return allUsers.value;
    const needle = userSearchText.value.toLowerCase();
    return allUsers.value.filter(u => u.label.toLowerCase().includes(needle));
});

function filterUsers(val, update) {
    userSearchText.value = val;
    update();
}

function HandleSearch(pg = 1) {
    router.get(route('panel.report.audit-log'), { ...filters.value, page: pg }, {
        preserveState: true,
        onSuccess: () => {
            audits.value  = page.props.audits;
            currentPage.value = audits.value.current_page;
            searched.value = true;
        },
    });
}

function HandleClear() {
    filters.value = { user_id: null, event: null, model: null, date_from: null, date_to: null };
    router.get(route('panel.report.audit-log'), {}, {
        preserveState: true,
        onSuccess: () => {
            audits.value  = page.props.audits;
            currentPage.value = 1;
            searched.value = false;
        },
    });
}

function HandlePageChange(pg) {
    HandleSearch(pg);
}

function hasChanges(row) {
    return row.new_values && Object.keys(row.new_values).length > 0;
}

function eventLabel(event) {
    const map = { created: 'Creado', updated: 'Modificado', deleted: 'Eliminado' };
    return map[event] ?? event;
}

function eventColor(event) {
    const map = { created: 'positive', updated: 'primary', deleted: 'negative' };
    return map[event] ?? 'grey';
}
</script>
