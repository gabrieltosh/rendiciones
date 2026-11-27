<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">
            <PageHeader
                title="Pendientes de Exportación SAP"
                icon="eva-cloud-upload-outline"
            >
                <template #subtitle>
                    <span v-if="data.length">
                        {{ data.length }} rendición{{ data.length !== 1 ? 'es' : '' }} autorizada{{ data.length !== 1 ? 's' : '' }}
                        por {{ formatAmount(totalAmount) }} que aún no llegan a SAP
                    </span>
                    <span v-else>Todas las rendiciones autorizadas están en SAP</span>
                </template>
            </PageHeader>

            <q-card class="card-form">
                <q-table
                    :rows="data"
                    :columns="columns"
                    row-key="id"
                    class="table-theme"
                    :filter="filter"
                    separator="horizontal"
                    flat
                    :table-header-class="{ 'table-header-theme': true }"
                    :pagination="{ rowsPerPage: 15 }"
                >
                    <template v-slot:top>
                        <div class="text-subtitle1 text-weight-medium">Rendiciones por exportar</div>
                        <q-space />
                        <q-input
                            outlined
                            dense
                            clearable
                            placeholder="Buscar…"
                            v-model="filter"
                            class="input-theme search-input"
                            aria-label="Buscar rendiciones"
                        >
                            <template v-slot:prepend>
                                <q-icon name="search" />
                            </template>
                        </q-input>
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
                            <q-td key="last_attempt" :props="props" style="max-width: 260px; white-space: normal;">
                                <template v-if="lastLog(props.row)">
                                    <q-badge :color="lastLog(props.row).success ? 'positive' : 'negative'"
                                        :label="lastLog(props.row).success ? 'Exportada' : 'Error'" />
                                    <span class="text-caption text-grey q-ml-xs">{{ lastLog(props.row).created_at }}</span>
                                    <div v-if="!lastLog(props.row).success" class="text-caption ellipsis-2-lines">
                                        {{ lastLog(props.row).message }}
                                        <q-tooltip max-width="420px">{{ lastLog(props.row).message }}</q-tooltip>
                                    </div>
                                </template>
                                <span v-else class="text-caption text-grey">Sin intentos</span>
                            </q-td>
                            <q-td key="actions" :props="props">
                                <div class="q-gutter-xs">
                                    <q-btn flat round dense size="sm" icon="cloud_upload" color="positive"
                                        aria-label="Exportar a SAP" :loading="exportingId === props.row.id"
                                        @click="HandleReExport(props.row.id)">
                                        <q-tooltip>Exportar a SAP</q-tooltip>
                                    </q-btn>
                                    <q-btn flat round dense size="sm" icon="history" color="grey-8"
                                        aria-label="Historial de exportación" @click="HandleOpenLogs(props.row)">
                                        <q-badge v-if="props.row.export_logs?.length" floating rounded color="grey-7"
                                            :label="props.row.export_logs.length" />
                                        <q-tooltip>Historial de exportación</q-tooltip>
                                    </q-btn>
                                    <q-btn flat round dense size="sm" icon="visibility" color="primary"
                                        aria-label="Ver detalle" @click="HandleOpenDetail(props.row.id)">
                                        <q-tooltip>Ver detalle</q-tooltip>
                                    </q-btn>
                                    <q-btn flat round dense size="sm" icon="edit" color="secondary"
                                        aria-label="Editar" @click="HandleOpenEdit(props.row.id)">
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
                            <div class="text-caption">Todo lo autorizado ya fue registrado en SAP.</div>
                        </div>
                    </template>
                </q-table>
            </q-card>
            </div>
        </div>

        <!-- ── Historial de exportación ──────────────────────────────── -->
        <q-dialog v-model="logsDialog">
            <q-card style="width: 900px; max-width: 95vw;">
                <q-card-section class="row items-center q-pb-none">
                    <div class="text-h6 title-form">Historial de exportación · Rendición #{{ logsRow?.id }}</div>
                    <q-space />
                    <q-btn icon="close" flat round dense v-close-popup aria-label="Cerrar" />
                </q-card-section>
                <q-card-section>
                    <q-markup-table v-if="logsRow?.export_logs?.length" flat bordered dense separator="horizontal"
                        class="table-theme">
                        <thead>
                            <tr class="table-header-theme">
                                <th class="text-left">Fecha</th>
                                <th class="text-left">Usuario</th>
                                <th class="text-left">Resultado</th>
                                <th class="text-left">Modo</th>
                                <th class="text-left">Asiento</th>
                                <th class="text-left">Mensaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logsRow.export_logs" :key="log.id">
                                <td style="white-space: nowrap;">{{ log.created_at }}</td>
                                <td>{{ log.user?.name || '—' }}</td>
                                <td>
                                    <q-badge :color="log.success ? 'positive' : 'negative'"
                                        :label="log.success ? 'Exportada' : 'Error'" />
                                </td>
                                <td>{{ log.mode || '—' }}</td>
                                <td>{{ log.sap_key || '—' }}</td>
                                <td style="white-space: normal; min-width: 280px;">{{ log.message }}</td>
                            </tr>
                        </tbody>
                    </q-markup-table>
                    <div v-else class="text-center text-grey q-pa-lg">
                        Todavía no hay intentos de exportación registrados.
                    </div>
                </q-card-section>
            </q-card>
        </q-dialog>

        <!-- ── Modal Edición ─────────────────────────────────────────── -->
        <q-dialog v-model="editDialog" persistent>
            <q-card style="width: 900px; max-width: 95vw;">
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
                            <div class="form-label">Empleado <span class="text-red">*</span></div>
                            <q-select
                                class="input-theme"
                                dense outlined
                                :options="filteredEmployees"
                                v-model="editForm.employee"
                                option-value="card_code"
                                :option-label="(e) => e.card_name ? `${e.card_code} - ${e.card_name}` : e.card_code"
                                emit-value map-options
                                use-input input-debounce="300"
                                @filter="filterEmployees"
                                @update:model-value="HandleKeepEmployee"
                                hint="Escriba al menos 2 letras para buscar en SAP"
                                clearable
                            >
                                <template #no-option>
                                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                                </template>
                            </q-select>
                            <div v-if="editErrors.employee" class="container-error">
                                <ul class="message-error"><li>{{ firstError(editErrors.employee) }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Cuenta <span class="text-red">*</span></div>
                            <q-select
                                class="input-theme"
                                dense outlined
                                :options="filteredAccounts"
                                v-model="editForm.account"
                                option-value="key"
                                option-label="label"
                                emit-value map-options
                                use-input input-debounce="0"
                                @filter="filterAccounts"
                                clearable
                            />
                            <div v-if="editErrors.account" class="container-error">
                                <ul class="message-error"><li>{{ firstError(editErrors.account) }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Monto Recepcionado <span class="text-red">*</span></div>
                            <q-input v-model="editForm.total" dense outlined type="number" class="input-theme" />
                            <div v-if="editErrors.total" class="container-error">
                                <ul class="message-error"><li>{{ firstError(editErrors.total) }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Descripción <span class="text-red">*</span></div>
                            <q-input v-model="editForm.description" dense outlined class="input-theme" />
                            <div v-if="editErrors.description" class="container-error">
                                <ul class="message-error"><li>{{ firstError(editErrors.description) }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Fecha Inicio <span class="text-red">*</span></div>
                            <q-input v-model="editForm.start_date" dense outlined type="date" class="input-theme" />
                            <div v-if="editErrors.start_date" class="container-error">
                                <ul class="message-error"><li>{{ firstError(editErrors.start_date) }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Fecha Final <span class="text-red">*</span></div>
                            <q-input v-model="editForm.end_date" dense outlined type="date" class="input-theme" />
                            <div v-if="editErrors.end_date" class="container-error">
                                <ul class="message-error"><li>{{ firstError(editErrors.end_date) }}</li></ul>
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
import PageHeader from "@/components/PageHeader.vue";
import { ref, computed } from "vue";
import { formatAmount } from "@/utils/accountability";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { firstError } from "@/utils/forms";

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
    { name: "last_attempt", align: "left",   label: "Último intento", field: row => row.export_logs?.[0]?.message ?? "" },
    { name: "actions",      align: "center", label: "Acciones",    field: "actions" },
];

const totalAmount = computed(() => data.value.reduce((sum, row) => sum + Number(row.total ?? 0), 0));
const exportingId = ref(null);

// ── Historial de exportación ────────────────────────────────
const logsDialog = ref(false);
const logsRow = ref(null);

function lastLog(row) {
    return row.export_logs?.[0] ?? null;
}

function HandleOpenLogs(row) {
    logsRow.value = row;
    logsDialog.value = true;
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
                onStart: () => (exportingId.value = id),
                onFinish: () => (exportingId.value = null),
                onSuccess: () => {
                    data.value = page.props.data;
                    if (page.props.flash.sap_number) {
                        $q.dialog({
                            title: "Rendición exportada a SAP",
                            message: `${page.props.flash.message}.`,
                            ok: { label: "Aceptar", color: "primary", noCaps: true },
                        });
                        return;
                    }
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
        if (!res.ok) throw new Error(res.statusText);
        const json = await res.json();
        editData.value = json;
        editForm.value = { ...json.accountability, from: "pending-export" };
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

// Un empleado elegido de la búsqueda en SAP se agrega a la lista para que se siga mostrando su nombre
function HandleKeepEmployee(code) {
    if (!code || allEmployees.value.some((e) => e.card_code === code)) return;
    const found = filteredEmployees.value.find((e) => e.card_code === code);
    if (found) allEmployees.value = [found, ...allEmployees.value];
}

// Empleados: los del perfil (o el del usuario) y, al escribir, búsqueda de socios de negocio en SAP
async function filterEmployees(val, update, abort) {
    const needle = (val || "").trim().toLowerCase();
    const local = allEmployees.value.filter(
        (v) => !needle || `${v.card_code} ${v.card_name ?? ""}`.toLowerCase().includes(needle)
    );
    if (needle.length < 2) {
        update(() => (filteredEmployees.value = local));
        return;
    }
    try {
        const res = await fetch(
            route("panel.accountability.authorization.employees", { search: val.trim() }),
            { headers: { "X-Requested-With": "XMLHttpRequest" }, credentials: "same-origin" }
        );
        if (!res.ok) throw new Error(res.statusText);
        const remote = await res.json();
        const codes = new Set(local.map((e) => e.card_code));
        update(() => {
            filteredEmployees.value = [...local, ...remote.filter((e) => !codes.has(e.card_code))];
        });
    } catch {
        update(() => (filteredEmployees.value = local));
        $q.notify({ type: "negative", message: "No se pudo buscar empleados en SAP." });
    }
}

function HandleUpdateForm() {
    editSaving.value = true;
    editErrors.value = {};
    router.put(route("panel.accountability.authorization.update"), editForm.value, {
        preserveScroll: true,
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
