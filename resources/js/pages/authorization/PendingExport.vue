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
                        por {{ totalsLabel(data) }} que aún no llegan a SAP
                    </span>
                    <span v-else>Todas las rendiciones autorizadas están en SAP</span>
                </template>
                <template v-if="selected.length" #actions>
                    <q-btn data-tour="batch-export"
                        color="positive"
                        icon="cloud_upload"
                        no-caps
                        unelevated
                        :label="`Reexportar seleccionadas (${selected.length})`"
                        :loading="batch.running"
                        :disable="batch.running || exportingId !== null"
                        @click="HandleBatchReExport"
                    />
                </template>
            </PageHeader>

            <!-- Progreso de la reexportación en lote -->
            <q-card data-tour="batch-progress" v-if="batch.total" class="card-form q-pa-md q-mb-md">
                <div class="row items-center q-col-gutter-sm q-mb-sm">
                    <div class="col text-body2 text-weight-medium">
                        {{ batch.running
                            ? `Exportando ${batch.done + 1} de ${batch.total}…`
                            : `Reexportación terminada: ${batch.ok} exportada${batch.ok !== 1 ? 's' : ''}, ${batch.failed} con error` }}
                    </div>
                    <div class="col-auto">
                        <q-btn v-if="!batch.running" flat dense no-caps size="12px" label="Cerrar" color="secondary"
                            @click="HandleCloseBatch" />
                    </div>
                </div>
                <q-linear-progress :value="batch.total ? batch.done / batch.total : 0" color="positive" rounded size="8px" />
                <q-list dense class="q-mt-sm">
                    <q-item v-for="r in batch.results" :key="r.id" class="q-px-none">
                        <q-item-section avatar>
                            <q-icon :name="r.success ? 'check_circle' : 'error'" :color="r.success ? 'positive' : 'negative'" />
                        </q-item-section>
                        <q-item-section>
                            <q-item-label class="text-caption">Rendición #{{ r.id }}</q-item-label>
                            <q-item-label caption style="white-space: normal;">{{ r.message }}</q-item-label>
                        </q-item-section>
                    </q-item>
                </q-list>
            </q-card>

            <q-card class="card-form">
                <q-table data-tour="pending-export-table"
                    :rows="data"
                    :columns="columns"
                    row-key="id"
                    class="table-theme"
                    :filter="filter"
                    separator="horizontal"
                    flat
                    selection="multiple"
                    v-model:selected="selected"
                    :table-header-class="{ 'table-header-theme': true }"
                    :pagination="{ rowsPerPage: 15 }"
                    :selected-rows-label="(n) => `${n} seleccionada${n !== 1 ? 's' : ''} · ${totalsLabel(selected)}`"
                    rows-per-page-label="Filas por página"
                    :pagination-label="(start, end, total) => `${start}-${end} de ${total}`"
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
                        <q-tr data-tour="select-row" :props="props">
                            <q-td auto-width>
                                <q-checkbox v-model="props.selected" dense :disable="batch.running"
                                    :aria-label="`Seleccionar rendición ${props.row.id}`" />
                            </q-td>
                            <q-td key="id" :props="props">{{ props.row.id }}</q-td>
                            <q-td key="user_name" :props="props">{{ props.row.user?.name }}</q-td>
                            <q-td key="profile_name" :props="props">{{ props.row.profile?.name }}</q-td>
                            <q-td key="description" :props="props">
                                <a href="#" class="text-primary text-weight-medium" style="text-decoration: none;"
                                    @click.prevent="HandleOpenDetail(props.row.id)">{{ props.row.description }}</a>
                            </q-td>
                            <q-td key="employee_name" :props="props">{{ props.row.employee_name || '—' }}</q-td>
                            <q-td key="total" :props="props" class="text-right stat-number">
                                {{ formatMoney(props.row.total, props.row.profile?.type_currency) }}
                            </q-td>
                            <q-td key="end_date" :props="props">{{ props.row.end_date }}</q-td>
                            <q-td data-tour="last-attempt" key="last_attempt" :props="props" style="max-width: 260px; white-space: normal;">
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
                                <div class="row no-wrap items-center justify-center q-gutter-xs">
                                    <q-btn data-tour="open-detail" unelevated dense no-caps no-wrap color="primary"
                                        icon="build" label="Revisar y corregir" class="q-px-sm"
                                        aria-label="Revisar y corregir la rendición" :disable="batch.running"
                                        @click="HandleOpenDetail(props.row.id)">
                                        <q-tooltip>Abre la rendición: corrija encabezado o documentos y exporte desde ahí</q-tooltip>
                                    </q-btn>
                                    <q-btn data-tour="export-one" flat round dense size="sm" icon="cloud_upload" color="positive"
                                        aria-label="Exportar a SAP" :loading="exportingId === props.row.id"
                                        :disable="batch.running || (exportingId !== null && exportingId !== props.row.id)"
                                        @click="HandleReExport(props.row.id)">
                                        <q-tooltip>Exportar a SAP</q-tooltip>
                                    </q-btn>
                                    <q-btn data-tour="export-logs" flat round dense size="sm" icon="history" color="grey-8"
                                        aria-label="Historial de exportación" @click="HandleOpenLogs(props.row)">
                                        <q-badge v-if="props.row.export_logs?.length" floating rounded color="grey-7"
                                            :label="props.row.export_logs.length" />
                                        <q-tooltip>Historial de exportación</q-tooltip>
                                    </q-btn>
                                    <q-btn data-tour="edit-header" flat round dense size="sm" icon="edit" color="secondary"
                                        aria-label="Editar encabezado" :disable="batch.running" @click="HandleOpenEdit(props.row.id)">
                                        <q-tooltip>Editar encabezado rápido</q-tooltip>
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

        <!-- Intentos de exportación y edición rápida del encabezado -->
        <ExportLogsDialog v-model="logsDialog" :accountability-id="logsRow?.id" :logs="logsRow?.export_logs ?? []" />
        <EditHeaderDialog v-model="editDialog" :accountability-id="editId" from="pending-export" />
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import EditHeaderDialog from "@/components/authorization/EditHeaderDialog.vue";
import ExportLogsDialog from "@/components/authorization/ExportLogsDialog.vue";
import { ref, reactive, watch } from "vue";
import { formatMoney } from "@/utils/accountability";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import axios from "axios";

const props = defineProps({
    title: String,
    data: { type: Array, default: () => [] },
});

const $q = useQuasar();
const page = usePage();
const data = ref(props.data);
const selected = ref([]);
watch(() => props.data, (rows) => {
    data.value = rows;
    // Las rendiciones que ya salieron de la lista no quedan seleccionadas
    const ids = new Set(rows.map((r) => r.id));
    selected.value = selected.value.filter((r) => ids.has(r.id));
});
const filter = ref("");

const columns = [
    { name: "id",           align: "center", label: "ID",          field: "id",           sortable: true },
    { name: "user_name",    align: "left",   label: "Usuario",     field: row => row.user?.name,    sortable: true },
    { name: "profile_name", align: "left",   label: "Perfil",      field: row => row.profile?.name, sortable: true },
    { name: "description",  align: "left",   label: "Descripción", field: "description",  sortable: true },
    { name: "employee_name",align: "left",   label: "Empleado",    field: "employee_name",sortable: true },
    { name: "total",        align: "right",  label: "Monto",       field: row => Number(row.total ?? 0), sortable: true },
    { name: "end_date",     align: "center", label: "Fecha Fin",   field: "end_date",     sortable: true },
    { name: "last_attempt", align: "left",   label: "Último intento", field: row => row.export_logs?.[0]?.message ?? "" },
    { name: "actions",      align: "center", label: "Acciones",    field: "actions" },
];

// Total por moneda del perfil: "Bs 1.000,00 · $us 250,00"
function totalsLabel(rows) {
    const sums = {};
    for (const row of rows) {
        const currency = row.profile?.type_currency ?? "";
        sums[currency] = (sums[currency] ?? 0) + Number(row.total ?? 0);
    }
    const parts = Object.entries(sums).map(([currency, sum]) => formatMoney(sum, currency));
    return parts.length ? parts.join(" · ") : formatMoney(0);
}

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
    if (exportingId.value !== null || batch.running) return;
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
                preserveScroll: true,
                onStart: () => (exportingId.value = id),
                onFinish: () => (exportingId.value = null),
                onSuccess: () => {
                    // Con número de asiento se muestra un diálogo para anotarlo; el resto
                    // de los avisos lo muestra el manejador global de flash del layout
                    if (page.props.flash?.sap_number) {
                        $q.dialog({
                            title: "Rendición exportada a SAP",
                            message: `${page.props.flash.message}.`,
                            ok: { label: "Aceptar", color: "primary", noCaps: true },
                        });
                    }
                },
            }
        );
    });
}

// ── Reexportación en lote: una por una, en orden, mostrando el avance ──
const batch = reactive({ running: false, total: 0, done: 0, ok: 0, failed: 0, results: [] });

function HandleBatchReExport() {
    if (batch.running || !selected.value.length) return;
    const ids = selected.value.map((r) => r.id);
    $q.dialog({
        title: "Reexportar a SAP",
        message: `Se intentará exportar ${ids.length} rendición${ids.length !== 1 ? "es" : ""} a SAP, una por una. ¿Desea continuar?`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Exportar", color: "positive", noCaps: true },
        persistent: true,
    }).onOk(async () => {
        Object.assign(batch, { running: true, total: ids.length, done: 0, ok: 0, failed: 0, results: [] });
        for (const id of ids) {
            let result;
            try {
                const res = await axios.post(
                    route("panel.accountability.authorization.detail.re-export", id),
                    {},
                    { headers: { Accept: "application/json" } }
                );
                result = { id, success: !!res.data.success, message: res.data.message };
            } catch (error) {
                result = {
                    id,
                    success: false,
                    message: error.response?.data?.message || "No se pudo contactar al servidor.",
                };
            }
            batch.results.push(result);
            result.success ? batch.ok++ : batch.failed++;
            batch.done++;
        }
        batch.running = false;
        selected.value = [];
        // Recarga la lista: las exportadas salen y el historial de las demás se actualiza
        router.reload({ only: ["data"], preserveScroll: true });
    });
}

function HandleCloseBatch() {
    Object.assign(batch, { total: 0, done: 0, ok: 0, failed: 0, results: [] });
}

function HandleOpenDetail(id) {
    router.visit(route("panel.accountability.authorization.detail.index", id) + "?from=pending-export");
}

// ── Edición rápida del encabezado (diálogo compartido con el detalle) ──
const editDialog = ref(false);
const editId = ref(null);

function HandleOpenEdit(id) {
    editId.value = id;
    editDialog.value = true;
}
</script>

<style scoped>
.stat-number {
    font-variant-numeric: tabular-nums;
}
</style>
