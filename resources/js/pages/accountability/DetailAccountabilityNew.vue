<template>

    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-10 col-lg-9">

                <PageHeader :title="accountability.description || `Rendición #${accountability.id}`"
                    icon="eva-file-text-outline" :back="indexUrl">
                    <template #subtitle>
                        <q-badge :color="statusColor(accountability.status)" :label="statusLabel(accountability.status)"
                            class="q-mr-sm" />
                        {{ accountability.employee_name ? accountability.employee_name + ' · ' : '' }}Rendición
                        #{{ accountability.id }}
                    </template>
                    <template #actions>
                        <q-btn data-tour="edit-accountability" v-if="isEditable" color="primary" icon="edit" label="Editar datos" no-caps outline
                            aria-label="Editar datos de la rendición" @click="HandleEditAccountability()" />
                        <div data-tour="send-accountability" v-if="isEditable" class="inline-block">
                            <q-btn color="positive" icon="send" :label="sendLabel" no-caps unelevated
                                :disable="!!sendBlockedReason" :loading="sending"
                                :aria-label="`${sendLabel} rendición a autorización`" @click="HandleSend()" />
                            <q-tooltip v-if="sendBlockedReason" max-width="260px">{{ sendBlockedReason }}</q-tooltip>
                        </div>
                        <q-btn data-tour="print-accountability" color="primary" icon="print" label="Imprimir" no-caps flat
                            aria-label="Imprimir reporte de rendición" @click="HandleReportAccountability()" />
                    </template>
                </PageHeader>

                <!-- Observaciones del autorizador (rendición rechazada) -->
                <q-card data-tour="rejection-notes" v-if="accountability.status === 'Rechazado'" class="card-form q-mb-md rejection-card">
                    <q-card-section>
                        <div class="row items-center q-col-gutter-sm q-mb-sm">
                            <div class="col-auto">
                                <q-icon name="eva-alert-triangle-outline" color="negative" size="28px" />
                            </div>
                            <div class="col">
                                <div class="text-subtitle1 text-weight-medium">Observaciones del autorizador</div>
                                <div class="text-caption text-grey">
                                    La rendición fue rechazada<span v-if="rejection?.by"> por {{ rejection.by }}</span><span
                                        v-if="rejection?.at"> el {{ rejection.at }}</span>.
                                </div>
                            </div>
                        </div>
                        <div class="rejection-comment q-pa-md rounded-borders">
                            <template v-if="rejection?.comments">{{ rejection.comments }}</template>
                            <span v-else class="text-grey">
                                El autorizador no dejó un comentario. Consulte con él qué debe corregir.
                            </span>
                        </div>
                    </q-card-section>
                    <q-card-actions align="right" class="q-px-md q-pb-md q-gutter-sm">
                        <q-btn color="secondary" flat no-caps icon="edit" label="Corregir datos de la rendición"
                            @click="HandleEditAccountability()" />
                        <q-btn color="negative" outline no-caps icon="eva-edit-2-outline" label="Corregir y reenviar"
                            @click="HandleStartCorrection()" />
                    </q-card-actions>
                </q-card>

                <!-- Estado -->
                <q-banner data-tour="status-banner" v-else-if="statusMessage" :class="['card-form q-mb-md text-white', 'bg-' + statusColor(accountability.status)]"
                    dense rounded>
                    <template v-slot:avatar>
                        <q-icon :name="statusIcon(accountability.status)" color="white" />
                    </template>
                    {{ statusMessage }}
                </q-banner>

                <!-- Por qué no se puede enviar -->
                <q-banner data-tour="no-authorizer" v-if="isEditable && !hasAuthorizer" class="card-form q-mb-md bg-warning text-dark" dense rounded>
                    <template v-slot:avatar><q-icon name="warning" /></template>
                    No tiene asignado un ciclo de autorización ni un autorizador. Puede registrar y guardar sus
                    documentos, pero para enviar la rendición el administrador debe asignarle un autorizador.
                </q-banner>

                <!-- Resumen: recibido, gastado y saldo -->
                <q-card data-tour="balance-summary" class="card-form q-mb-md">
                    <q-card-section>
                        <div class="row q-col-gutter-md">
                            <div class="col-xs-6 col-sm-3">
                                <div class="text-caption text-grey">Recibido</div>
                                <div class="text-subtitle1 text-weight-medium stat-number">
                                    {{ formatMoney(accountability.total, currency) }}
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="text-caption text-grey">Gastado</div>
                                <div class="text-subtitle1 text-weight-medium stat-number">
                                    {{ formatMoney(spent, currency) }}
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="text-caption text-grey">{{ balance.label }}</div>
                                <div class="text-subtitle1 text-weight-medium stat-number" :class="`text-${balance.color}`">
                                    {{ formatMoney(balance.balance, currency) }}
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="text-caption text-grey">Documentos</div>
                                <div class="text-subtitle1 text-weight-medium stat-number">{{ documents.length }}</div>
                            </div>
                        </div>
                    </q-card-section>
                </q-card>

                <!-- Información general (colapsable) -->
                <q-expansion-item data-tour="general-info" class="card-form q-mb-md overflow-hidden rounded-borders" icon="info"
                    label="Información general" header-class="text-subtitle2 text-weight-medium" dense
                    :default-opened="$q.screen.gt.xs">
                    <q-card>
                        <q-card-section>
                            <div v-if="isEditable" class="row justify-end q-mb-xs">
                                <q-btn flat dense no-caps color="secondary" icon="edit" label="Editar"
                                    aria-label="Editar datos de la rendición" @click="HandleEditAccountability()" />
                            </div>
                            <div class="row q-col-gutter-md">
                                <div class="col-xs-6 col-sm-4 col-md-3">
                                    <div class="text-caption text-grey">Inicio</div>
                                    <div class="text-body2">{{ formatDate(accountability.start_date) }}</div>
                                </div>
                                <div class="col-xs-6 col-sm-4 col-md-3">
                                    <div class="text-caption text-grey">Fin</div>
                                    <div class="text-body2">{{ formatDate(accountability.end_date) }}</div>
                                </div>
                                <div class="col-xs-12 col-sm-4 col-md-6">
                                    <div class="text-caption text-grey">Cuenta de la rendición</div>
                                    <div class="text-body2">
                                        {{ accountability.account_alias || accountability.account_name || '-' }}
                                    </div>
                                    <div v-if="accountability.account_alias" class="text-caption text-grey">
                                        {{ accountability.account_name }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6">
                                    <div class="text-caption text-grey">Empleado</div>
                                    <div class="text-body2">{{ accountability.employee_name || '-' }}</div>
                                </div>
                                <div class="col-xs-12 col-sm-6">
                                    <div class="text-caption text-grey">Descripción</div>
                                    <div class="text-body2" style="word-break: break-word;">
                                        {{ accountability.description || '-' }}
                                    </div>
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                </q-expansion-item>

                <!-- Documentos -->
                <q-card data-tour="documents-list" ref="documentsCard" class="card-form q-mb-md">
                    <q-card-section class="q-pb-none">
                        <div class="row items-center q-col-gutter-sm">
                            <div class="col-xs-12 col-sm">
                                <div class="text-subtitle1 text-weight-medium">Documentos de la rendición</div>
                            </div>
                            <div class="col-xs col-sm-auto">
                                <q-input v-model="filter" outlined dense placeholder="Buscar" name="search-detail"
                                    autocomplete="off" aria-label="Buscar documentos" class="input-theme" clearable>
                                    <template v-slot:prepend>
                                        <q-icon name="search" aria-hidden="true" />
                                    </template>
                                </q-input>
                            </div>
                            <div class="col-auto" v-if="isEditable">
                                <q-btn data-tour="add-expense" color="primary" icon="add" label="Agregar gasto" no-caps unelevated
                                    aria-label="Agregar un nuevo gasto" @click="HandleCreateDocument()" />
                            </div>
                        </div>
                    </q-card-section>

                    <q-card-section>
                        <div v-if="filteredDocuments.length === 0" class="text-center q-pa-xl">
                            <q-icon name="receipt_long" size="64px" color="grey-4" aria-hidden="true" />
                            <div class="text-body1 text-grey q-mt-md">
                                <template v-if="filter">Sin resultados para la búsqueda</template>
                                <template v-else-if="isEditable">Agregue su primer gasto</template>
                                <template v-else>La rendición no tiene documentos</template>
                            </div>
                            <div v-if="!filter && isEditable" class="text-caption text-grey q-mt-xs">
                                Agregue sus facturas y recibos; puede escanear el QR de las facturas desde el celular.
                            </div>
                            <q-btn v-if="!filter && isEditable" color="primary" label="Agregar gasto" icon="add"
                                no-caps unelevated class="q-mt-md" @click="HandleCreateDocument()" />
                            <q-btn v-if="filter" flat color="primary" label="Quitar búsqueda" no-caps class="q-mt-md"
                                @click="filter = ''" />
                        </div>

                        <div v-else class="q-gutter-md">
                            <q-card data-tour="document-card" v-for="(doc, i) in filteredDocuments" :key="doc.id" flat bordered class="doc-card">
                                <q-card-section class="q-py-sm">
                                    <div class="row items-center q-col-gutter-sm">
                                        <div class="col-auto">
                                            <q-btn flat round dense size="sm"
                                                :icon="expandedRows[doc.id] ? 'expand_less' : 'expand_more'"
                                                :aria-label="expandedRows[doc.id] ? 'Contraer detalle' : 'Expandir detalle'"
                                                @click="toggleExpand(doc.id)" />
                                        </div>
                                        <div class="col-auto gt-xs">
                                            <span class="text-grey">{{ i + 1 }}</span>
                                        </div>
                                        <div class="col" style="min-width: 0;">
                                            <div class="row items-center q-gutter-xs">
                                                <div class="text-body2 text-weight-medium" style="word-break: break-word;">
                                                    {{ doc.concept || 'Sin concepto' }}
                                                </div>
                                                <q-badge v-if="doc.lines_count > 0" color="secondary" outline
                                                    :label="`Repartido en ${doc.lines_count} líneas`" />
                                                <q-badge v-if="doc.items_count > 0" color="teal" outline
                                                    :label="`${doc.items_count} artículo${doc.items_count !== 1 ? 's' : ''}`" />
                                            </div>
                                            <div class="text-caption text-grey q-mt-xs">
                                                <span v-if="doc.date">{{ formatDate(doc.date) }}</span>
                                                <span v-if="doc.document_number"> &middot; N° {{ doc.document_number }}</span>
                                                <span v-if="doc.business_name"> &middot; {{ doc.business_name }}</span>
                                            </div>
                                        </div>
                                        <div class="col-auto text-right">
                                            <div class="text-body2 text-weight-medium stat-number">
                                                {{ formatMoney(doc.amount, currency) }}
                                            </div>
                                            <div class="text-caption text-grey ellipsis" style="max-width: 160px;">
                                                {{ doc.account_alias || doc.account_name || doc.account || '' }}
                                            </div>
                                        </div>
                                        <div class="col-xs-12 col-sm-auto row justify-end q-gutter-xs">
                                            <q-btn data-tour="siat-button" v-if="doc.cuf" flat round dense size="sm" icon="receipt_long"
                                                color="teal" :loading="loadingFactura === doc.id"
                                                aria-label="Ver la factura en el SIAT" @click="HandleConsultaFactura(doc)">
                                                <q-tooltip>Ver factura en el SIAT</q-tooltip>
                                            </q-btn>
                                            <q-btn v-if="isEditable" flat round dense size="sm" icon="edit"
                                                color="secondary" aria-label="Editar documento"
                                                @click="HandleEditDocument(doc.id)">
                                                <q-tooltip>Editar</q-tooltip>
                                            </q-btn>
                                            <q-btn data-tour="duplicate-expense" v-if="isEditable" flat round dense size="sm" icon="content_copy"
                                                color="secondary" aria-label="Duplicar gasto"
                                                @click="HandleDuplicateDocument(doc.id)">
                                                <q-tooltip>Duplicar gasto (copia proveedor, cuenta y concepto)</q-tooltip>
                                            </q-btn>
                                            <q-btn v-if="isEditable" flat round dense size="sm" icon="delete_outline"
                                                color="negative" aria-label="Eliminar documento"
                                                :loading="deletingId === doc.id" @click="HandleDeleteDocument(doc)">
                                                <q-tooltip>Eliminar</q-tooltip>
                                            </q-btn>
                                        </div>
                                    </div>
                                </q-card-section>

                                <q-slide-transition>
                                    <div v-show="expandedRows[doc.id]">
                                        <q-separator />
                                        <q-card-section class="q-py-sm">
                                            <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">Datos del comprobante</div>
                                            <div class="row q-col-gutter-sm q-mb-md">
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.document_number">
                                                    <div class="text-caption text-grey">N° de documento</div>
                                                    <div class="text-body2">{{ doc.document_number }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.authorization_number">
                                                    <div class="text-caption text-grey">N° de autorización</div>
                                                    <div class="text-body2">{{ doc.authorization_number }}</div>
                                                </div>
                                                <div class="col-xs-12 col-sm-8 col-md-6" v-if="doc.cuf">
                                                    <div class="text-caption text-grey">CUF</div>
                                                    <div class="text-caption" style="word-break: break-all;">{{ doc.cuf }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.control_code">
                                                    <div class="text-caption text-grey">Código de control</div>
                                                    <div class="text-body2">{{ doc.control_code }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.nit">
                                                    <div class="text-caption text-grey">NIT / CI del proveedor</div>
                                                    <div class="text-body2">{{ doc.nit }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.business_name">
                                                    <div class="text-caption text-grey">Razón social</div>
                                                    <div class="text-body2" style="word-break: break-word;">{{ doc.business_name }}</div>
                                                </div>
                                            </div>

                                            <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">Importes</div>
                                            <div class="row q-col-gutter-sm q-mb-md">
                                                <div class="col-xs-6 col-sm-4 col-md-3">
                                                    <div class="text-caption text-grey">Importe</div>
                                                    <div class="text-body2 stat-number">{{ formatMoney(doc.amount, currency) }}</div>
                                                </div>
                                                <template v-for="extra in AMOUNT_EXTRAS" :key="extra.key">
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="Number(doc[extra.key])">
                                                        <div class="text-caption text-grey">{{ extra.label }}</div>
                                                        <div class="text-body2 stat-number">{{ formatMoney(doc[extra.key], currency) }}</div>
                                                    </div>
                                                </template>
                                            </div>

                                            <div v-if="doc.project_code || hasCostCenters(doc)">
                                                <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">Proyecto y centros de costo</div>
                                                <div class="row q-col-gutter-sm">
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.project_code">
                                                        <div class="text-caption text-grey">Proyecto</div>
                                                        <div class="text-body2">{{ doc.project_code }}</div>
                                                    </div>
                                                    <template v-for="(field, idx) in COST_CENTER_FIELDS" :key="field">
                                                        <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc[field]">
                                                            <div class="text-caption text-grey">Centro de costo {{ idx + 1 }}</div>
                                                            <div class="text-body2">{{ doc[field] }}</div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </q-card-section>
                                    </div>
                                </q-slide-transition>
                            </q-card>
                        </div>
                    </q-card-section>
                </q-card>

                <!-- Historial de cambios -->
                <q-expansion-item data-tour="history" class="card-form q-mb-md overflow-hidden rounded-borders" icon="history"
                    label="Historial de cambios" header-class="text-subtitle2 text-weight-medium" dense>
                    <q-card>
                        <q-card-section>
                            <div v-if="audits.length === 0" class="text-center q-pa-lg text-grey">
                                Sin registros de cambios
                            </div>
                            <q-timeline v-else color="grey-5" layout="dense">
                                <q-timeline-entry v-for="audit in audits" :key="audit.id" :icon="auditIcon(audit.event)"
                                    :color="auditColor(audit.event)">
                                    <template v-slot:title>
                                        <span class="text-body2 text-weight-medium">{{ auditLabel(audit.event) }}</span>
                                        <span class="text-caption text-grey q-ml-sm">por {{ audit.user }}</span>
                                    </template>
                                    <template v-slot:subtitle>
                                        <span class="text-caption text-grey">{{ audit.created_at }}</span>
                                    </template>
                                    <div v-if="audit.event === 'updated' && Object.keys(audit.new_values || {}).length" class="q-mt-xs">
                                        <div v-for="(val, field) in audit.new_values" :key="field"
                                            class="row q-col-gutter-sm q-mb-xs items-center">
                                            <div class="col-xs-12 col-sm-3">
                                                <q-badge color="grey-4" text-color="grey-8" :label="fieldLabel(field)" />
                                            </div>
                                            <div class="col-xs-5 col-sm-4 text-caption text-negative" style="word-break:break-word;">
                                                {{ audit.old_values?.[field] ?? '—' }}
                                            </div>
                                            <div class="col-auto">
                                                <q-icon name="arrow_forward" size="12px" color="grey" />
                                            </div>
                                            <div class="col-xs-5 col-sm-4 text-caption text-positive" style="word-break:break-word;">
                                                {{ val ?? '—' }}
                                            </div>
                                        </div>
                                    </div>
                                </q-timeline-entry>
                            </q-timeline>
                        </q-card-section>
                    </q-card>
                </q-expansion-item>

            </div>
        </div>

        <SiatInvoiceDialog v-model="showFacturaDialog" :factura="selectedFactura" />
        <AccountabilityFormDialog v-model="showEditDialog" :profile-id="page.props.profile.id"
            :accountability-id="accountability.id" return-to="detail" />
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import SiatInvoiceDialog from "@/components/accountability/SiatInvoiceDialog.vue";
import AccountabilityFormDialog from "@/components/accountability/AccountabilityFormDialog.vue";
import { siatQueryUrl, formatDate } from "@/components/accountability/siat";
import { formatMoney, balanceInfo, statusLabel, statusColor, statusIcon } from "@/utils/accountability";
import { ref, computed, nextTick } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar, openURL } from "quasar";
import axios from "axios";

defineProps({
    title: String,
});

const $q = useQuasar();
const page = usePage();

// Se leen de las props para reflejar los cambios después de cada acción
const accountability = computed(() => page.props.accountability);
const documents = computed(() => page.props.documents ?? []);
const audits = computed(() => page.props.audits ?? []);
const rejection = computed(() => page.props.rejection);
const hasAuthorizer = computed(() => !!page.props.has_authorizer);
const currency = computed(() => page.props.profile?.type_currency);

const indexUrl = route("panel.accountability.manage.index", page.props.profile.id);

const AMOUNT_EXTRAS = [
    { key: "discount", label: "Descuento" },
    { key: "excento", label: "Exento" },
    { key: "rate", label: "Tasas" },
    { key: "gift_card", label: "Gift card" },
    { key: "rate_zero", label: "Tasa cero" },
    { key: "ice", label: "ICE" },
];
const COST_CENTER_FIELDS = [
    "distribution_rule_one",
    "distribution_rule_second",
    "distribution_rule_three",
    "distribution_rule_four",
    "distribution_rule_five",
];

const FIELD_LABELS = {
    status: "Estado",
    comments: "Observaciones",
    total: "Monto recibido",
    description: "Descripción",
    start_date: "Fecha de inicio",
    end_date: "Fecha final",
    account_code: "Código de cuenta",
    account_name: "Cuenta",
    employee_code: "Código de empleado",
    employee_name: "Empleado",
    current_level_id: "Nivel de autorización",
    sap_exported: "Exportada a SAP",
    sap_trans_id: "N° de asiento SAP",
    submission_round: "Envío N°",
    submitted_at: "Fecha de envío",
};

function fieldLabel(field) {
    return FIELD_LABELS[field] ?? field;
}
function auditLabel(event) {
    return { created: "Creada", updated: "Modificada", deleted: "Eliminada" }[event] ?? event;
}
function auditIcon(event) {
    return { created: "add_circle", updated: "edit", deleted: "delete" }[event] ?? "info";
}
function auditColor(event) {
    return { created: "positive", updated: "primary", deleted: "negative" }[event] ?? "grey";
}

const filter = ref("");
const expandedRows = ref({});
const selectedFactura = ref(null);
const showFacturaDialog = ref(false);
const loadingFactura = ref(null);
const sending = ref(false);
const deletingId = ref(null);
const documentsCard = ref(null);
const showEditDialog = ref(false);

const isEditable = computed(() => accountability.value.status === "Rechazado" || accountability.value.status === null);

// Gastado: lo calcula el servidor; si falta, se suma en el navegador
const spent = computed(() =>
    page.props.spent ?? documents.value.reduce((sum, doc) => sum + (Number(doc.amount) || 0), 0)
);
const balance = computed(() => balanceInfo(accountability.value.total, spent.value));

const sendLabel = computed(() => (accountability.value.status === "Rechazado" ? "Reenviar" : "Enviar"));
const sendBlockedReason = computed(() => {
    if (documents.value.length === 0) return "Agregue al menos un documento para poder enviar la rendición.";
    if (!hasAuthorizer.value) return "No tiene un autorizador asignado: solicítelo al administrador.";
    return null;
});

const statusMessage = computed(() => {
    const acc = accountability.value;
    const messages = {
        Pendiente: "La rendición está pendiente de autorización.",
        Anulado: acc.comments ? `La rendición fue anulada: ${acc.comments}` : "La rendición fue anulada.",
        Autorizado: acc.sap_exported
            ? "La rendición fue aprobada y registrada en contabilidad" +
              (acc.sap_trans_id ? ` (asiento N° ${acc.sap_trans_id}).` : ".")
            : "La rendición fue aprobada. Falta registrarla en contabilidad.",
    };
    return messages[acc.status] || null;
});

const filteredDocuments = computed(() => {
    if (!filter.value) return documents.value;
    const needle = filter.value.toLowerCase();
    return documents.value.filter((doc) =>
        [doc.concept, doc.business_name, doc.nit, doc.document_number, doc.account, doc.account_name, doc.account_alias]
            .some((value) => value && String(value).toLowerCase().includes(needle))
    );
});

function hasCostCenters(doc) {
    return COST_CENTER_FIELDS.some((field) => doc[field]);
}

function toggleExpand(id) {
    expandedRows.value[id] = !expandedRows.value[id];
}

// Los datos de la rendición se corrigen en un diálogo, sin salir del detalle
function HandleEditAccountability() {
    showEditDialog.value = true;
}

function HandleStartCorrection() {
    nextTick(() => documentsCard.value?.$el?.scrollIntoView({ behavior: "smooth", block: "start" }));
    $q.notify({
        type: "info",
        message: `Corrija los documentos o los datos de la rendición y luego pulse «${sendLabel.value}».`,
    });
}

async function HandleConsultaFactura(doc) {
    if (!doc.cuf || !doc.nit || !doc.document_number) {
        $q.notify({ type: "negative", message: "Faltan datos para consultar la factura (CUF, NIT o N° de factura)." });
        return;
    }
    loadingFactura.value = doc.id;
    try {
        const response = await axios.post(route("siat.consulta"), { url: siatQueryUrl(doc) });
        if (response.data?.error) {
            $q.notify({ type: "negative", message: response.data.error });
            return;
        }
        selectedFactura.value = response.data;
        showFacturaDialog.value = true;
    } catch (error) {
        $q.notify({
            type: "negative",
            message: error.response?.data?.error || "No se pudo consultar la factura en el SIAT.",
        });
    } finally {
        loadingFactura.value = null;
    }
}

function HandleCreateDocument() {
    router.visit(route("panel.accountability.manage.detail.create", [page.props.profile.id, accountability.value.id]));
}

// Nuevo gasto precargado con el tipo de documento, cuenta, proveedor y concepto de otro
function HandleDuplicateDocument(id) {
    router.visit(
        route("panel.accountability.manage.detail.create", [page.props.profile.id, accountability.value.id]) +
            `?from_document=${encodeURIComponent(id)}`
    );
}

function HandleEditDocument(id) {
    router.visit(route("panel.accountability.manage.detail.edit", [page.props.profile.id, accountability.value.id, id]));
}

function HandleDeleteDocument(doc) {
    const name = [doc.concept || "Sin concepto", doc.document_number ? `N° ${doc.document_number}` : null]
        .filter(Boolean)
        .join(" · ");
    $q.dialog({
        title: "Eliminar documento",
        message: `¿Eliminar «${name}» por ${formatMoney(doc.amount, currency.value)}? Esta acción no se puede deshacer.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true, unelevated: true },
        persistent: true,
    }).onOk(() => {
        router.delete(
            route("panel.accountability.manage.detail.delete", [page.props.profile.id, accountability.value.id, doc.id]),
            {
                preserveScroll: true,
                onStart: () => (deletingId.value = doc.id),
                onFinish: () => (deletingId.value = null),
            }
        );
    });
}

function HandleSend() {
    if (sendBlockedReason.value) return;
    const count = documents.value.length;
    const info = balance.value;
    $q.dialog({
        title: `${sendLabel.value} a autorización`,
        message:
            `<div>Se enviará la rendición con <strong>${count} documento${count !== 1 ? "s" : ""}</strong>.</div>` +
            `<div class="q-mt-sm">Recibido: <strong>${formatMoney(accountability.value.total, currency.value)}</strong></div>` +
            `<div>Gastado: <strong>${formatMoney(spent.value, currency.value)}</strong></div>` +
            `<div>${info.label}: <strong>${formatMoney(info.balance, currency.value)}</strong></div>` +
            `<div class="q-mt-sm text-grey-7">Mientras esté en revisión no podrá modificarla.</div>`,
        html: true,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: sendLabel.value, color: "positive", noCaps: true, unelevated: true },
        persistent: true,
    }).onOk(() => {
        router.post(
            route("panel.accountability.manage.detail.status", [page.props.profile.id, accountability.value.id]),
            {},
            {
                preserveScroll: true,
                onStart: () => (sending.value = true),
                onFinish: () => (sending.value = false),
            }
        );
    });
}

function HandleReportAccountability() {
    openURL(route("panel.accountability.manage.report", [page.props.profile.id, accountability.value.id]));
}
</script>

<style scoped>
.stat-number {
    font-variant-numeric: tabular-nums;
}
.doc-card {
    transition: box-shadow 0.2s ease;
}
.doc-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.rejection-card {
    border-left: 4px solid var(--q-negative);
}
.rejection-comment {
    background: rgba(193, 0, 21, 0.06);
    white-space: pre-line;
    word-break: break-word;
}
@media (prefers-reduced-motion: reduce) {
    .doc-card {
        transition: none;
    }
}
</style>
