<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-10 col-lg-9">

                <!-- Header -->
                <q-card class="q-pa-md card-form q-mb-md">
                    <div class="row items-center q-col-gutter-sm">
                        <div class="col">
                            <div class="row items-center q-gutter-sm">
                                <div class="text-subtitle1 text-weight-medium">
                                    {{ accountability.description }}
                                </div>
                                <q-badge
                                    :color="statusColor"
                                    :label="accountability.status || 'Pendiente'"
                                    class="q-pa-xs"
                                />
                            </div>
                            <div class="text-caption text-grey q-mt-xs">
                                {{ accountability.employee_name }}
                                &middot; Rendicion #{{ accountability.id }}
                            </div>
                        </div>
                        <div class="col-auto q-gutter-sm">
                            <q-btn
                                color="secondary"
                                label="Volver"
                                size="12px"
                                no-caps
                                flat
                                aria-label="Volver a la lista de autorizaciones"
                                @click="router.visit(route('panel.accountability.authorization.index'))"
                            />
                            <template v-if="!isAuthorized">
                                <q-btn
                                    color="grey"
                                    label="Anular"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    aria-label="Anular rendicion"
                                    @click="HandleCancelStatus('Anulado')"
                                />
                                <q-btn
                                    color="negative"
                                    label="Rechazar"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    aria-label="Rechazar rendicion"
                                    @click="HandleRejectStatus('Rechazado')"
                                />
                                <q-btn
                                    color="positive"
                                    icon="check"
                                    label="Autorizar"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    aria-label="Autorizar rendicion"
                                    @click="HandleAuthorizeStatus('Autorizado')"
                                />
                            </template>
                            <q-btn
                                v-if="isAuthorized"
                                color="primary"
                                icon="print"
                                label="Imprimir"
                                size="12px"
                                no-caps
                                flat
                                aria-label="Imprimir reporte de rendicion"
                                @click="HandleGetReport()"
                            />
                        </div>
                    </div>
                </q-card>

                <!-- Status Banner -->
                <q-banner
                    v-if="statusMessage"
                    :class="['card-form q-mb-md text-white', 'bg-' + statusColor]"
                    dense
                    rounded
                >
                    <template v-slot:avatar>
                        <q-icon :name="statusIcon" color="white" />
                    </template>
                    {{ statusMessage }}
                </q-banner>

                <!-- Informacion General -->
                <q-expansion-item
                    class="card-form q-mb-md overflow-hidden rounded-borders"
                    icon="info"
                    label="Informacion General"
                    header-class="text-subtitle2 text-weight-medium"
                    default-value
                    dense
                    default-opened
                >
                    <q-card>
                        <q-card-section>
                            <!-- Resumen -->
                            <div class="row q-col-gutter-sm q-mb-md">
                                <div class="col-xs-6 col-sm-3">
                                    <div class="text-caption text-grey">Monto Total</div>
                                    <div class="text-body2 text-weight-medium stat-number">
                                        Bs {{ accountability.total || 0 }}
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="text-caption text-grey">Documentos</div>
                                    <div class="text-body2 text-weight-medium stat-number">
                                        {{ documents.length }}
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="text-caption text-grey">Inicio</div>
                                    <div class="text-body2 text-weight-medium">
                                        {{ accountability.start_date || '-' }}
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="text-caption text-grey">Fin</div>
                                    <div class="text-body2 text-weight-medium">
                                        {{ accountability.end_date || '-' }}
                                    </div>
                                </div>
                            </div>
                            <q-separator class="q-mb-md" />
                            <!-- Datos generales -->
                            <div class="row q-col-gutter-md">
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="text-caption text-grey">Cuenta</div>
                                    <div class="text-caption">{{ accountability.account_name || '-' }}</div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="text-caption text-grey">Empleado</div>
                                    <div class="text-caption">{{ accountability.employee_name || '-' }}</div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="text-caption text-grey">Descripcion</div>
                                    <div class="text-caption" style="word-break: break-word;">
                                        {{ accountability.description || '-' }}
                                    </div>
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                </q-expansion-item>

                <!-- Documents Section -->
                <q-card class="card-form">
                    <!-- Toolbar -->
                    <q-card-section class="q-pb-none">
                        <div class="row items-center q-col-gutter-sm">
                            <div class="col">
                                <div class="text-subtitle1 text-weight-medium">
                                    Detalle de Rendicion
                                </div>
                            </div>
                            <div class="col-auto">
                                <q-input
                                    v-model="filter"
                                    outlined
                                    dense
                                    placeholder="Buscar…"
                                    name="search-detail"
                                    autocomplete="off"
                                    aria-label="Buscar documentos en el detalle"
                                    class="input-theme"
                                    style="min-width: 200px;"
                                    clearable
                                >
                                    <template v-slot:prepend>
                                        <q-icon name="search" aria-hidden="true" />
                                    </template>
                                </q-input>
                            </div>
                            <div class="col-auto" v-if="!isAuthorized">
                                <q-btn
                                    color="primary"
                                    icon="add"
                                    label="Nuevo"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    aria-label="Crear nuevo documento"
                                    @click="HandleCreateDocument()"
                                />
                            </div>
                        </div>
                    </q-card-section>

                    <q-card-section>
                        <!-- Empty State -->
                        <div v-if="filteredDocuments.length === 0" class="text-center q-pa-xl">
                            <q-icon name="receipt_long" size="64px" color="grey-4" aria-hidden="true" />
                            <div class="text-body1 text-grey q-mt-md">
                                {{ filter ? 'Sin resultados para la busqueda' : 'No hay documentos registrados' }}
                            </div>
                            <q-btn
                                v-if="!filter && !isAuthorized"
                                color="primary"
                                label="Crear Documento"
                                icon="add"
                                no-caps
                                unelevated
                                class="q-mt-md"
                                @click="HandleCreateDocument()"
                            />
                        </div>

                        <!-- Document Cards -->
                        <div v-else class="q-gutter-md">
                            <q-card
                                v-for="(doc, i) in filteredDocuments"
                                :key="doc.id"
                                flat
                                bordered
                                class="doc-card"
                            >
                                <!-- Card Header -->
                                <q-card-section class="q-py-sm">
                                    <div class="row items-center q-col-gutter-sm">
                                        <div class="col-auto">
                                            <q-btn
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                :icon="expandedRows[doc.id] ? 'expand_less' : 'expand_more'"
                                                :aria-label="expandedRows[doc.id] ? 'Contraer detalle' : 'Expandir detalle'"
                                                @click="toggleExpand(doc.id)"
                                            />
                                        </div>
                                        <div class="col-auto">
                                            <span class="q-mx-md text-grey">{{ i + 1 }}</span>
                                        </div>
                                        <!-- Concepto + main info -->
                                        <div class="col">
                                            <div class="text-body2 text-weight-medium" style="word-break: break-word;">
                                                {{ doc.concept || 'Sin concepto' }}
                                            </div>
                                            <div class="text-caption text-grey q-mt-xs">
                                                <span v-if="doc.date">{{ doc.date }}</span>
                                                <span v-if="doc.document_number"> &middot; Factura #{{ doc.document_number }}</span>
                                                <span v-if="doc.nit"> &middot; NIT {{ doc.nit }}</span>
                                                <span v-if="doc.business_name"> &middot; {{ doc.business_name }}</span>
                                            </div>
                                        </div>

                                        <!-- Amount -->
                                        <div class="col-auto text-right">
                                            <div class="text-body2 text-weight-medium stat-number">
                                                Bs {{ doc.amount || 0 }}
                                            </div>
                                            <div class="text-caption text-grey">{{ doc.account || '' }}</div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="col-auto q-gutter-xs">
                                            <q-btn
                                                v-if="doc.cuf"
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="receipt_long"
                                                color="teal"
                                                :loading="loadingFactura === doc.id"
                                                aria-label="Ver datos originales de factura SIAT"
                                                @click="HandleConsultaFactura(doc)"
                                            >
                                                <q-tooltip>Ver Factura SIAT</q-tooltip>
                                            </q-btn>
                                            <q-btn
                                                v-if="!isAuthorized"
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="edit"
                                                color="secondary"
                                                aria-label="Editar documento"
                                                @click="HandleEditDocument(doc.id)"
                                            >
                                                <q-tooltip>Editar</q-tooltip>
                                            </q-btn>
                                            <q-btn
                                                v-if="!isAuthorized"
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="delete_outline"
                                                color="negative"
                                                aria-label="Eliminar documento"
                                                @click="HandleDeleteDocument(doc.id)"
                                            >
                                                <q-tooltip>Eliminar</q-tooltip>
                                            </q-btn>
                                        </div>
                                    </div>
                                </q-card-section>

                                <!-- Expanded Detail -->
                                <q-slide-transition>
                                    <div v-show="expandedRows[doc.id]">
                                        <q-separator />
                                        <q-card-section class="q-py-sm">
                                            <!-- Libro de Compras -->
                                            <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">
                                                Libro de Compras
                                            </div>
                                            <div class="row q-col-gutter-sm q-mb-md">
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.document_number">
                                                    <div class="text-caption text-grey">N. Factura</div>
                                                    <div class="text-body2">{{ doc.document_number }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.authorization_number">
                                                    <div class="text-caption text-grey">N. Autorizacion</div>
                                                    <div class="text-body2">{{ doc.authorization_number }}</div>
                                                </div>
                                                <div class="col-xs-12 col-sm-8 col-md-6" v-if="doc.cuf">
                                                    <div class="text-caption text-grey">CUF</div>
                                                    <div class="text-caption" style="word-break: break-all;">{{ doc.cuf }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.control_code">
                                                    <div class="text-caption text-grey">Cod. Control</div>
                                                    <div class="text-body2">{{ doc.control_code }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.nit">
                                                    <div class="text-caption text-grey">NIT</div>
                                                    <div class="text-body2">{{ doc.nit }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.business_name">
                                                    <div class="text-caption text-grey">Razon Social</div>
                                                    <div class="text-body2" style="word-break: break-word;">
                                                        {{ doc.business_name }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Importes -->
                                            <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">Importes</div>
                                            <div class="row q-col-gutter-sm q-mb-md">
                                                <div class="col-xs-6 col-sm-4 col-md-3">
                                                    <div class="text-caption text-grey">Importe</div>
                                                    <div class="text-body2 stat-number">Bs {{ doc.amount || 0 }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.discount">
                                                    <div class="text-caption text-grey">Descuento</div>
                                                    <div class="text-body2 stat-number">Bs {{ doc.discount }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.excento">
                                                    <div class="text-caption text-grey">Exento</div>
                                                    <div class="text-body2 stat-number">Bs {{ doc.excento }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.rate">
                                                    <div class="text-caption text-grey">Tasas</div>
                                                    <div class="text-body2 stat-number">Bs {{ doc.rate }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.gift_card">
                                                    <div class="text-caption text-grey">Gift Card</div>
                                                    <div class="text-body2 stat-number">Bs {{ doc.gift_card }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.rate_zero">
                                                    <div class="text-caption text-grey">Tasa Cero</div>
                                                    <div class="text-body2 stat-number">Bs {{ doc.rate_zero }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.ice">
                                                    <div class="text-caption text-grey">ICE</div>
                                                    <div class="text-body2 stat-number">Bs {{ doc.ice }}</div>
                                                </div>
                                            </div>

                                            <!-- Datos Contables -->
                                            <div v-if="doc.project_code || doc.distribution_rule_one">
                                                <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">
                                                    Datos Contables
                                                </div>
                                                <div class="row q-col-gutter-sm">
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.project_code">
                                                        <div class="text-caption text-grey">Proyecto</div>
                                                        <div class="text-body2">{{ doc.project_code }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_one">
                                                        <div class="text-caption text-grey">CC 1</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_one }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_second">
                                                        <div class="text-caption text-grey">CC 2</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_second }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_three">
                                                        <div class="text-caption text-grey">CC 3</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_three }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_four">
                                                        <div class="text-caption text-grey">CC 4</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_four }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_five">
                                                        <div class="text-caption text-grey">CC 5</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_five }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </q-card-section>
                                    </div>
                                </q-slide-transition>
                            </q-card>
                        </div>
                    </q-card-section>
                </q-card>

            </div>
        </div>

        <!-- Dialog Ver Factura Original SIAT -->
        <q-dialog v-model="showFacturaDialog">
            <q-card style="width: 700px; max-width: 80vw;">
                <q-bar class="bg-teal q-py-lg">
                    <q-space />
                    <span class="text-body1 text-white">Datos Originales de Factura</span>
                    <q-space />
                    <q-btn
                        dense
                        flat
                        icon="close"
                        color="white"
                        aria-label="Cerrar detalle de factura"
                        @click="showFacturaDialog = false"
                    >
                        <q-tooltip>Cerrar</q-tooltip>
                    </q-btn>
                </q-bar>

                <q-card-section v-if="selectedFactura" class="q-pa-md">
                    <div class="row q-col-gutter-sm q-mb-md">
                        <div class="col-6">
                            <div class="text-caption text-grey">N. Factura</div>
                            <div class="text-body2">{{ selectedFactura.numeroFactura }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Fecha Emision</div>
                            <div class="text-body2">
                                {{ selectedFactura.fechaEmision ? selectedFactura.fechaEmision.substring(0, 10) : '-' }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">NIT Emisor</div>
                            <div class="text-body2">{{ selectedFactura.nitEmisor }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Razon Social Emisor</div>
                            <div class="text-body2" style="word-break: break-word;">
                                {{ selectedFactura.razonSocialEmisor }}
                            </div>
                        </div>
                        <div class="col-6" v-if="selectedFactura.direccion">
                            <div class="text-caption text-grey">Direccion</div>
                            <div class="text-body2" style="word-break: break-word;">
                                {{ selectedFactura.direccion }}
                            </div>
                        </div>
                        <div class="col-6" v-if="selectedFactura.codigoSucursal !== undefined">
                            <div class="text-caption text-grey">Sucursal</div>
                            <div class="text-body2">{{ selectedFactura.codigoSucursal }}</div>
                        </div>
                        <div class="col-12" v-if="selectedFactura.cuf">
                            <div class="text-caption text-grey">CUF</div>
                            <div class="text-caption" style="word-break: break-all;">{{ selectedFactura.cuf }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Cliente</div>
                            <div class="text-body2">{{ selectedFactura.nombreRazonSocial }}</div>
                        </div>
                        <div class="col-6" v-if="selectedFactura.numeroDocumento">
                            <div class="text-caption text-grey">Documento Cliente</div>
                            <div class="text-body2">{{ selectedFactura.numeroDocumento }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Monto Total</div>
                            <div class="text-body2 text-weight-bold">Bs {{ selectedFactura.montoTotal }}</div>
                        </div>
                        <div class="col-6" v-if="selectedFactura.estadoFactura">
                            <div class="text-caption text-grey">Estado</div>
                            <q-badge :color="selectedFactura.estadoFactura === 'VALIDA' ? 'positive' : 'negative'">
                                {{ selectedFactura.estadoFactura }}
                            </q-badge>
                        </div>
                    </div>

                    <!-- Detalle de lineas -->
                    <div v-if="selectedFactura.listaDetalle && selectedFactura.listaDetalle.length > 0">
                        <q-separator class="q-mb-md" />
                        <div class="text-subtitle2 q-mb-sm">
                            Detalle ({{ selectedFactura.listaDetalle.length }} items)
                        </div>
                        <q-virtual-scroll
                            :items="selectedFactura.listaDetalle"
                            :virtual-scroll-item-size="56"
                            style="max-height: 300px;"
                            class="rounded-borders bordered"
                        >
                            <template v-slot="{ item, index }">
                                <q-item :key="index" dense>
                                    <q-item-section avatar>
                                        <q-avatar
                                            size="24px"
                                            color="grey-3"
                                            text-color="grey-8"
                                            class="text-caption"
                                            style="font-variant-numeric: tabular-nums;"
                                        >
                                            {{ index + 1 }}
                                        </q-avatar>
                                    </q-item-section>
                                    <q-item-section>
                                        <q-item-label style="word-break: break-word;">
                                            {{ item.descripcion }}
                                        </q-item-label>
                                        <q-item-label caption>
                                            {{ item.cantidad }} x Bs {{ item.precioUnitario }}
                                        </q-item-label>
                                    </q-item-section>
                                    <q-item-section side>
                                        <q-item-label class="text-weight-medium">
                                            Bs {{ item.subTotal }}
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>
                            </template>
                        </q-virtual-scroll>
                    </div>
                </q-card-section>

                <q-card-actions align="right" class="q-pa-md">
                    <q-btn
                        color="primary"
                        label="Cerrar"
                        no-caps
                        flat
                        size="12px"
                        @click="showFacturaDialog = false"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar, openURL } from "quasar";
import axios from "axios";

defineProps({
    title: String,
    data: Object,
});

const $q = useQuasar();
const page = usePage();
const accountability = page.props.accountability;
const documents = ref(page.props.documents);
const filter = ref("");
const expandedRows = ref({});
const selectedFactura = ref(null);
const showFacturaDialog = ref(false);
const loadingFactura = ref(null);

const isAuthorized = computed(() => {
    return accountability.status === "Autorizado";
});

const statusColor = computed(() => {
    const colors = {
        Pendiente: "orange",
        Rechazado: "red",
        Anulado: "grey",
        Autorizado: "green",
    };
    return colors[accountability.status] || "blue-grey";
});

const statusIcon = computed(() => {
    const icons = {
        Pendiente: "schedule",
        Rechazado: "cancel",
        Anulado: "block",
        Autorizado: "check_circle",
    };
    return icons[accountability.status] || "info";
});

const statusMessage = computed(() => {
    const messages = {
        Pendiente: "La rendicion esta pendiente de autorizacion",
        Anulado: `La rendicion fue anulada: ${accountability.comments}`,
        Rechazado: `La rendicion fue rechazada: ${accountability.comments}`,
        Autorizado: "La rendicion fue aprobada y exportada",
    };
    return messages[accountability.status] || null;
});

const filteredDocuments = computed(() => {
    if (!filter.value) return documents.value;
    const needle = filter.value.toLowerCase();
    return documents.value.filter((doc) => {
        return (
            (doc.concept && doc.concept.toLowerCase().includes(needle)) ||
            (doc.business_name && doc.business_name.toLowerCase().includes(needle)) ||
            (doc.nit && String(doc.nit).includes(needle)) ||
            (doc.document_number && String(doc.document_number).includes(needle)) ||
            (doc.account && doc.account.toLowerCase().includes(needle)) ||
            (doc.account_name && doc.account_name.toLowerCase().includes(needle))
        );
    });
});

function toggleExpand(id) {
    expandedRows.value[id] = !expandedRows.value[id];
}

function notifyFlash() {
    documents.value = page.props.documents;
    $q.notify({
        type: page.props.flash.type,
        message: page.props.flash.message,
    });
}

async function HandleConsultaFactura(doc) {
    if (!doc.cuf || !doc.nit || !doc.document_number) {
        $q.notify({
            type: "negative",
            message: "Faltan datos para consultar la factura (CUF, NIT o N. Factura)",
        });
        return;
    }
    const url = `https://siat.impuestos.gob.bo/consulta/QR?nit=${doc.nit}&cuf=${doc.cuf}&numero=${doc.document_number}`;
    loadingFactura.value = doc.id;
    try {
        const response = await axios.post(route("siat.consulta"), { url });
        const factura = response.data;
        if (factura.error) {
            $q.notify({ type: "negative", message: factura.error });
            return;
        }
        selectedFactura.value = factura;
        showFacturaDialog.value = true;
    } catch (error) {
        const msg = error.response?.data?.error || "Error al consultar SIAT";
        $q.notify({ type: "negative", message: msg });
    } finally {
        loadingFactura.value = null;
    }
}

function HandleCreateDocument() {
    router.visit(
        route("panel.accountability.authorization.detail.create", [
            accountability.id,
        ])
    );
}

function HandleEditDocument(id) {
    router.visit(
        route("panel.accountability.authorization.detail.edit", [
            accountability.id,
            id,
        ])
    );
}

function HandleDeleteDocument(id) {
    $q.dialog({
        title: "Eliminar Documento",
        message: "¿Esta seguro de eliminar este documento? Esta accion no se puede deshacer.",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(
            route("panel.accountability.authorization.detail.delete", [
                accountability.id,
                id,
            ]),
            { onSuccess: notifyFlash }
        );
    });
}

function HandleCancelStatus(status) {
    $q.dialog({
        title: "Anular Rendicion",
        message: "Escriba el motivo de la anulacion",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Anular", color: "grey-8", noCaps: true },
        persistent: true,
        prompt: {
            model: "",
            isValid: (val) => val.length > 5,
            type: "text",
            label: "Motivo",
        },
    }).onOk((comments) => {
        router.post(
            route("panel.accountability.authorization.detail.status", accountability.id),
            { status, comments },
            { onSuccess: notifyFlash }
        );
    });
}

function HandleRejectStatus(status) {
    $q.dialog({
        title: "Rechazar Rendicion",
        message: "Escriba el motivo del rechazo",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Rechazar", color: "negative", noCaps: true },
        persistent: true,
        prompt: {
            model: "",
            isValid: (val) => val.length > 5,
            type: "text",
            label: "Motivo",
        },
    }).onOk((comments) => {
        router.post(
            route("panel.accountability.authorization.detail.status", accountability.id),
            { status, comments },
            { onSuccess: notifyFlash }
        );
    });
}

function HandleAuthorizeStatus(status) {
    $q.dialog({
        title: "Autorizar Rendicion",
        message: "¿Esta seguro de autorizar esta rendicion? Se exportara a SAP.",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Autorizar", color: "positive", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.post(
            route("panel.accountability.authorization.detail.export", accountability.id),
            { status, comments: null },
            { onSuccess: notifyFlash }
        );
    });
}

function HandleGetReport() {
    openURL(
        route("panel.accountability.authorization.report", accountability.id)
    );
}
</script>

<style scoped>
.stat-number {
    font-variant-numeric: tabular-nums;
}

.bordered {
    border: 1px solid rgba(0, 0, 0, 0.12);
}

.doc-card {
    transition: box-shadow 0.2s ease;
}

.doc-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

@media (prefers-reduced-motion: reduce) {
    .doc-card {
        transition: none;
    }
}
</style>
