<template>
    <q-dialog :model-value="modelValue" persistent :maximized="$q.screen.xs"
        :transition-show="prefersReducedMotion ? 'none' : 'slide-up'"
        :transition-hide="prefersReducedMotion ? 'none' : 'slide-down'"
        @update:model-value="(v) => emit('update:modelValue', v)" @hide="HandleReset">
        <q-card class="qr-dialog-card" :class="{ 'qr-dialog-card--wide': !$q.screen.xs }">
            <q-bar class="bg-primary q-py-lg">
                <q-space />
                <span class="text-body1 text-white">Escanear QR de la factura</span>
                <q-space />
                <q-btn dense flat icon="close" color="white" aria-label="Cerrar escáner QR" @click="HandleClose">
                    <q-tooltip>Cerrar</q-tooltip>
                </q-btn>
            </q-bar>

            <!-- Vista previa: datos de la factura antes de aplicarlos -->
            <q-card-section data-tour="scan-result" v-if="factura" class="q-pa-md">
                <div class="qr-content">
                    <div class="row items-center q-mb-sm">
                        <div class="col text-h6">Datos detectados</div>
                        <div class="col-auto" v-if="statusLabel">
                            <q-badge :color="valid ? 'positive' : 'negative'" :label="`SIAT: ${statusLabel}`"
                                class="q-pa-xs" />
                        </div>
                    </div>
                    <div class="text-body2 text-grey q-mb-md">
                        Revise los datos extraídos de la factura antes de aplicarlos al formulario.
                    </div>

                    <q-banner v-if="!valid" dense rounded class="bg-negative text-white q-mb-md">
                        <template v-slot:avatar><q-icon name="eva-close-circle-outline" color="white" /></template>
                        La factura figura como <strong>{{ statusLabel }}</strong> en el SIAT y no se puede registrar.
                        Solicite al proveedor una factura válida.
                    </q-banner>
                    <q-banner v-if="valid && outsidePeriod" dense rounded class="bg-warning text-dark q-mb-md">
                        <template v-slot:avatar><q-icon name="eva-calendar-outline" /></template>
                        La fecha de emisión ({{ formatDate(data.date) }}) está fuera del periodo de la rendición
                        ({{ formatDate(periodStart) }} al {{ formatDate(periodEnd) }}). No podrá guardar el documento
                        con esa fecha.
                    </q-banner>
                    <q-banner v-if="valid && duplicate" dense rounded class="bg-warning text-dark q-mb-md">
                        <template v-slot:avatar><q-icon name="eva-copy-outline" /></template>
                        Esta factura ya está registrada en la rendición #{{ duplicate.accountability_id }}
                        ({{ duplicate.status }}{{ duplicate.own ? '' : ', de otro usuario' }}). Verifique que no la
                        esté rindiendo dos veces.
                    </q-banner>

                    <q-list bordered separator class="rounded-borders q-mb-md">
                        <q-item v-if="data.document_number">
                            <q-item-section>
                                <q-item-label caption>N° de factura</q-item-label>
                                <q-item-label>{{ data.document_number }}</q-item-label>
                            </q-item-section>
                        </q-item>
                        <q-item v-if="data.cuf">
                            <q-item-section>
                                <q-item-label caption>CUF</q-item-label>
                                <q-item-label class="text-caption" style="word-break: break-all;">{{ data.cuf }}</q-item-label>
                            </q-item-section>
                        </q-item>
                        <q-item v-if="data.nit">
                            <q-item-section>
                                <q-item-label caption>NIT del emisor</q-item-label>
                                <q-item-label>{{ data.nit }}</q-item-label>
                            </q-item-section>
                        </q-item>
                        <q-item v-if="data.business_name">
                            <q-item-section>
                                <q-item-label caption>Razón social</q-item-label>
                                <q-item-label style="word-break: break-word;">{{ data.business_name }}</q-item-label>
                            </q-item-section>
                        </q-item>
                        <q-item v-if="data.amount">
                            <q-item-section>
                                <q-item-label caption>Monto total</q-item-label>
                                <q-item-label>{{ formatMoney(data.amount, 'BS') }}</q-item-label>
                            </q-item-section>
                        </q-item>
                        <q-item v-if="data.date">
                            <q-item-section>
                                <q-item-label caption>Fecha de emisión</q-item-label>
                                <q-item-label :class="{ 'text-warning text-weight-medium': outsidePeriod }">
                                    {{ formatDate(data.date) }}
                                </q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-list>

                    <div v-if="details.length > 0" class="q-mb-md">
                        <div class="text-subtitle2 q-mb-sm">
                            Detalle de la factura ({{ details.length }} {{ details.length === 1 ? 'ítem' : 'ítems' }})
                        </div>
                        <q-virtual-scroll :items="details" :virtual-scroll-item-size="48" style="max-height: 250px;"
                            class="rounded-borders qr-bordered">
                            <template v-slot="{ item, index }">
                                <q-item :key="index" dense>
                                    <q-item-section>
                                        <q-item-label style="word-break: break-word;">{{ item.descripcion }}</q-item-label>
                                        <q-item-label caption>
                                            {{ item.cantidad }} x {{ formatMoney(item.precioUnitario, 'BS') }}
                                        </q-item-label>
                                    </q-item-section>
                                    <q-item-section side>
                                        <q-item-label class="text-weight-medium">{{ formatMoney(item.subTotal, 'BS') }}</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </template>
                        </q-virtual-scroll>
                    </div>

                    <div class="row q-gutter-sm justify-end">
                        <q-btn flat no-caps label="Escanear otra" icon="qr_code_scanner" @click="HandleScanAgain" />
                        <q-btn color="primary" unelevated no-caps label="Aplicar datos" icon="check"
                            :disable="!valid" @click="HandleApply" />
                    </div>
                </div>
            </q-card-section>

            <!-- Escaneo -->
            <q-card-section v-else class="q-pa-md">
                <div class="qr-content">
                    <q-tabs data-tour="scan-method" v-model="scanMethod" dense active-color="primary" indicator-color="primary" align="justify"
                        narrow-indicator class="q-mb-md">
                        <q-tab name="camera" icon="qr_code_scanner" label="Cámara" />
                        <q-tab name="manual" icon="link" label="Pegar enlace" />
                    </q-tabs>

                    <q-tab-panels v-model="scanMethod" animated>
                        <q-tab-panel name="camera" class="q-pa-none">
                            <div style="max-width: 500px; margin: 0 auto; position: relative;">
                                <QrcodeStream v-if="!cameraError && modelValue" @detect="HandleDetect" @error="HandleCameraError"
                                    :paused="loading" class="rounded-borders overflow-hidden">
                                    <div v-if="!loading" class="qr-guide-overlay">
                                        <div class="qr-guide-frame"></div>
                                        <div class="qr-guide-text">Ubique el código QR dentro del marco</div>
                                    </div>
                                    <div v-if="loading" class="text-center q-pa-xl">
                                        <q-spinner-dots color="primary" size="40px" />
                                        <div class="q-mt-sm text-body1">Consultando la factura en el SIAT…</div>
                                    </div>
                                </QrcodeStream>

                                <div v-if="cameraError" class="text-center q-pa-md">
                                    <q-icon name="videocam_off" size="48px" color="warning" />
                                    <div class="q-mt-sm text-body2" style="word-break: break-word;">{{ cameraError }}</div>
                                    <div class="row justify-center q-gutter-sm q-mt-md">
                                        <q-btn color="primary" label="Reintentar" icon="refresh" no-caps flat
                                            @click="cameraError = null" />
                                        <q-btn color="primary" label="Pegar enlace" icon="link" no-caps flat
                                            @click="scanMethod = 'manual'" />
                                    </div>
                                </div>
                            </div>
                        </q-tab-panel>

                        <q-tab-panel name="manual" class="q-pa-md">
                            <label for="qr-manual-url" class="form-label q-mb-sm" style="display: block;">
                                Enlace del QR de la factura (SIAT)
                            </label>
                            <q-input id="qr-manual-url" v-model="manualUrl" dense outlined type="url" name="siat-url"
                                autocomplete="off" placeholder="https://siat.impuestos.gob.bo/consulta/QR?nit=…"
                                class="q-mb-sm input-theme" @keyup.enter="HandleLookup(manualUrl)" />
                            <q-btn color="primary" label="Consultar" no-caps unelevated :loading="loading"
                                :disable="!manualUrl" @click="HandleLookup(manualUrl)" />
                        </q-tab-panel>
                    </q-tab-panels>
                </div>
            </q-card-section>
        </q-card>
    </q-dialog>
</template>

<script setup>
import { ref, computed } from "vue";
import { useQuasar } from "quasar";
import { QrcodeStream } from "vue-qrcode-reader";
import axios from "axios";
import { route } from "ziggy-js";
import { formatMoney } from "@/utils/accountability";
import {
    invoiceStatusLabel, isInvoiceValid, invoiceFormData, isOutsidePeriod, formatDate,
} from "./siat";

const props = defineProps({
    modelValue: Boolean,
    // Periodo de la rendición, para advertir facturas fuera de fecha
    periodStart: { type: String, default: null },
    periodEnd: { type: String, default: null },
    // Documento que se edita: no cuenta como duplicado de sí mismo
    excludeId: { type: [Number, String], default: null },
});
// apply: { data, factura } con los datos listos para el formulario y la respuesta de SIAT
const emit = defineEmits(["update:modelValue", "apply"]);

const $q = useQuasar();
const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

const scanMethod = ref("camera");
const manualUrl = ref("");
const loading = ref(false);
const cameraError = ref(null);
const factura = ref(null);
const duplicate = ref(null);

const data = computed(() => invoiceFormData(factura.value));
const details = computed(() => factura.value?.listaDetalle || []);
const statusLabel = computed(() => invoiceStatusLabel(factura.value));
const valid = computed(() => isInvoiceValid(factura.value));
const outsidePeriod = computed(() => isOutsidePeriod(data.value.date, props.periodStart, props.periodEnd));

function HandleDetect(codes) {
    if (codes.length > 0 && !loading.value) HandleLookup(codes[0].rawValue);
}

function HandleCameraError(error) {
    const messages = {
        NotAllowedError: "Se necesita permiso para usar la cámara. Habilítelo en el navegador o pegue el enlace del QR.",
        NotFoundError: "No se encontró una cámara en este dispositivo.",
        NotReadableError: "La cámara está siendo usada por otra aplicación.",
        OverconstrainedError: "La cámara no es compatible.",
        StreamApiNotSupportedError: "Este navegador no permite usar la cámara (se requiere HTTPS).",
        InsecureContextError: "La cámara solo funciona en una conexión segura (HTTPS).",
    };
    cameraError.value = messages[error?.name] || "No se pudo usar la cámara. Pegue el enlace del QR.";
}

async function HandleLookup(url) {
    if (!url) return;
    loading.value = true;
    duplicate.value = null;
    try {
        const response = await axios.post(route("siat.consulta"), { url });
        if (response.data?.error) {
            $q.notify({ type: "negative", message: response.data.error });
            return;
        }
        factura.value = response.data;
        manualUrl.value = "";
        HandleCheckDuplicate();
    } catch (error) {
        const message = error.response?.data?.error
            || error.response?.data?.errors?.url?.[0]
            || "No se pudo consultar la factura en el SIAT. Intente nuevamente o cargue los datos manualmente.";
        $q.notify({ type: "negative", message });
    } finally {
        loading.value = false;
    }
}

// Aviso (no bloqueante) si la factura ya está en alguna rendición
async function HandleCheckDuplicate() {
    const { cuf, nit, document_number } = data.value;
    if (!cuf && !(nit && document_number)) return;
    try {
        const response = await axios.get(route("panel.document-duplicate"), {
            params: { cuf, nit, document_number, exclude_id: props.excludeId },
        });
        duplicate.value = response.data?.duplicate ? response.data : null;
    } catch {
        duplicate.value = null;
    }
}

function HandleApply() {
    if (!valid.value) return;
    emit("apply", { data: { ...data.value }, factura: factura.value });
    emit("update:modelValue", false);
}

function HandleScanAgain() {
    factura.value = null;
    duplicate.value = null;
}

function HandleClose() {
    emit("update:modelValue", false);
}

function HandleReset() {
    factura.value = null;
    duplicate.value = null;
    manualUrl.value = "";
    cameraError.value = null;
    scanMethod.value = "camera";
}
</script>

<style scoped>
.qr-dialog-card {
    overscroll-behavior: contain;
}
.qr-dialog-card--wide {
    width: 700px;
    max-width: 92vw;
}
.qr-content {
    max-width: 600px;
    margin: 0 auto;
}
.qr-bordered {
    border: 1px solid rgba(0, 0, 0, 0.12);
}
.qr-guide-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.qr-guide-frame {
    width: min(250px, 70vw);
    height: min(250px, 70vw);
    border: 3px solid rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
}
.qr-guide-text {
    margin-top: 1.5rem;
    color: #fff;
    font-size: 0.875rem;
    text-align: center;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
    padding: 0.5rem 1rem;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 8px;
}
</style>
