<template>
    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">
            <q-card class="card-form">
                <q-table
                    :rows="data"
                    :columns="columns"
                    row-key="id"
                    class="table-theme"
                    :filter="filter"
                    separator="horizontal"
                    flat
                    bordered
                    :table-header-class="{ 'table-header-theme': true }"
                >
                    <template v-slot:top>
                        <h5 class="title-form">Pendientes de Exportación SAP</h5>
                        <q-space />
                        <q-input
                            outlined
                            dense
                            color="primary"
                            placeholder="Buscar..."
                            v-model="filter"
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
                            <q-td key="user_name" :props="props">{{ props.row.user?.name }}</q-td>
                            <q-td key="profile_name" :props="props">{{ props.row.profile?.name }}</q-td>
                            <q-td key="description" :props="props">{{ props.row.description }}</q-td>
                            <q-td key="employee_name" :props="props">{{ props.row.employee_name || '—' }}</q-td>
                            <q-td key="total" :props="props" class="text-right">
                                {{ formatAmount(props.row.total) }}
                            </q-td>
                            <q-td key="end_date" :props="props">{{ props.row.end_date }}</q-td>
                            <q-td key="actions" :props="props">
                                <div class="q-gutter-xs">
                                    <q-btn flat round dense size="sm" icon="cloud_upload" color="positive"
                                        @click="HandleReExport(props.row.id)">
                                        <q-tooltip>Exportar a SAP</q-tooltip>
                                    </q-btn>
                                    <q-btn flat round dense size="sm" icon="visibility" color="primary"
                                        @click="HandleOpenDetail(props.row.id)">
                                        <q-tooltip>Ver detalle</q-tooltip>
                                    </q-btn>
                                    <q-btn flat round dense size="sm" icon="edit" color="secondary"
                                        @click="HandleOpenEdit(props.row.id)">
                                        <q-tooltip>Editar</q-tooltip>
                                    </q-btn>
                                </div>
                            </q-td>
                        </q-tr>
                    </template>

                    <template v-slot:no-data>
                        <div class="full-width text-center q-pa-xl text-grey-6">
                            <q-icon name="check_circle" size="64px" color="positive" class="q-mb-md" />
                            <div>No hay rendiciones pendientes de exportación</div>
                        </div>
                    </template>
                </q-table>
            </q-card>
        </div>

        <!-- ── Modal Edición ─────────────────────────────────────────── -->
        <q-dialog v-model="editDialog" persistent>
            <q-card style="min-width: 600px; max-width: 900px; width: 90vw;">
                <q-card-section class="row items-center q-pb-none">
                    <div class="text-h6 title-form">Actualizar Rendición</div>
                    <q-space />
                    <q-btn icon="close" flat round dense v-close-popup />
                </q-card-section>

                <q-card-section v-if="editLoading" class="text-center q-py-xl">
                    <q-spinner size="40px" color="primary" />
                </q-card-section>

                <q-card-section v-else-if="editData">
                    <div class="row q-col-gutter-md">
                        <div v-if="!editData.profile?.sin_empleado" class="col-xs-12 col-sm-6">
                            <div class="form-label">Empleado</div>
                            <q-select
                                class="input-theme"
                                dense outlined
                                :options="filteredEmployees"
                                v-model="editForm.employee"
                                option-value="card_code"
                                option-label="card_name"
                                emit-value map-options
                                use-input input-debounce="0"
                                @filter="filterEmployees"
                                clearable
                            />
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Cuenta <span class="text-red">*</span></div>
                            <q-select
                                class="input-theme"
                                dense outlined
                                :options="filteredAccounts"
                                v-model="editForm.account"
                                option-value="account_code"
                                option-label="label"
                                emit-value map-options
                                use-input input-debounce="0"
                                @filter="filterAccounts"
                                clearable
                            />
                            <div v-if="editErrors.account" class="container-error">
                                <ul v-for="(e, i) in editErrors.account" :key="i" class="message-error"><li>{{ e }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Monto Recepcionado <span class="text-red">*</span></div>
                            <q-input v-model="editForm.total" dense outlined type="number" class="input-theme" />
                            <div v-if="editErrors.total" class="container-error">
                                <ul v-for="(e, i) in editErrors.total" :key="i" class="message-error"><li>{{ e }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Descripción <span class="text-red">*</span></div>
                            <q-input v-model="editForm.description" dense outlined class="input-theme" />
                            <div v-if="editErrors.description" class="container-error">
                                <ul v-for="(e, i) in editErrors.description" :key="i" class="message-error"><li>{{ e }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Fecha Inicio <span class="text-red">*</span></div>
                            <q-input v-model="editForm.start_date" dense outlined type="date" class="input-theme" />
                            <div v-if="editErrors.start_date" class="container-error">
                                <ul v-for="(e, i) in editErrors.start_date" :key="i" class="message-error"><li>{{ e }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Fecha Final <span class="text-red">*</span></div>
                            <q-input v-model="editForm.end_date" dense outlined type="date" class="input-theme" />
                            <div v-if="editErrors.end_date" class="container-error">
                                <ul v-for="(e, i) in editErrors.end_date" :key="i" class="message-error"><li>{{ e }}</li></ul>
                            </div>
                        </div>
                    </div>
                </q-card-section>

                <q-card-actions align="right">
                    <q-btn flat label="Cancelar" color="secondary" v-close-popup no-caps />
                    <q-btn label="Actualizar" color="primary" @click="HandleUpdateForm" no-caps :loading="editSaving" />
                </q-card-actions>
            </q-card>
        </q-dialog>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

defineProps({ title: String });

const $q = useQuasar();
const page = usePage();
const data = ref(page.props.data);
const filter = ref("");

// ── Edit modal ──────────────────────────────────────────────
const editDialog = ref(false);
const editLoading = ref(false);
const editSaving = ref(false);
const editData = ref(null);
const editForm = ref({});
const editErrors = ref({});
const filteredAccounts = ref([]);
const filteredEmployees = ref([]);
const allAccounts = ref([]);
const allEmployees = ref([]);

const columns = [
    { name: "id",           align: "center", label: "ID",          field: "id",           sortable: true },
    { name: "user_name",    align: "left",   label: "Usuario",     field: row => row.user?.name,    sortable: true },
    { name: "profile_name", align: "left",   label: "Perfil",      field: row => row.profile?.name, sortable: true },
    { name: "description",  align: "left",   label: "Descripción", field: "description",  sortable: true },
    { name: "employee_name",align: "left",   label: "Empleado",    field: "employee_name",sortable: true },
    { name: "total",        align: "right",  label: "Monto",       field: "total",        sortable: true },
    { name: "end_date",     align: "center", label: "Fecha Fin",   field: "end_date",     sortable: true },
    { name: "actions",      align: "center", label: "Acciones",    field: "actions" },
];

function formatAmount(val) {
    return Number(val ?? 0).toLocaleString("es-BO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function HandleReExport(id) {
    $q.dialog({
        title: "Exportar a SAP",
        message: "¿Desea intentar exportar esta rendición a SAP?",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Exportar", color: "positive", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.post(
            route("panel.accountability.authorization.detail.re-export", id),
            {},
            {
                onSuccess: () => {
                    data.value = page.props.data;
                    $q.notify({ type: page.props.flash.type, message: page.props.flash.message });
                },
            }
        );
    });
}

function HandleOpenDetail(id) {
    router.visit(route("panel.accountability.authorization.detail.index", id) + "?from=pending-export");
}

async function HandleOpenEdit(id) {
    editData.value = null;
    editErrors.value = {};
    editLoading.value = true;
    editDialog.value = true;

    try {
        const res = await fetch(route("panel.accountability.authorization.edit-data", id), {
            headers: { "X-Requested-With": "XMLHttpRequest" },
            credentials: "same-origin",
        });
        const json = await res.json();
        editData.value = json;
        editForm.value = { ...json.accountability };
        allAccounts.value = json.accounts;
        allEmployees.value = json.employees;
        filteredAccounts.value = json.accounts;
        filteredEmployees.value = json.employees;
    } catch {
        $q.notify({ type: "negative", message: "Error al cargar los datos." });
        editDialog.value = false;
    } finally {
        editLoading.value = false;
    }
}

function filterAccounts(val, update) {
    update(() => {
        filteredAccounts.value = val === ""
            ? allAccounts.value
            : allAccounts.value.filter(v => v.label.toLowerCase().includes(val.toLowerCase()));
    });
}

function filterEmployees(val, update) {
    update(() => {
        filteredEmployees.value = val === ""
            ? allEmployees.value
            : allEmployees.value.filter(v => v.card_name.toLowerCase().includes(val.toLowerCase()));
    });
}

function HandleUpdateForm() {
    editSaving.value = true;
    router.put(route("panel.accountability.authorization.update"), editForm.value, {
        onSuccess: () => {
            editDialog.value = false;
            data.value = page.props.data;
            $q.notify({ type: page.props.flash.type, message: page.props.flash.message });
        },
        onError: (errors) => {
            editErrors.value = errors;
        },
        onFinish: () => {
            editSaving.value = false;
        },
    });
}
</script>
