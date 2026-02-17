<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">

                <!-- Header -->
                <q-card class="q-pa-md card-form q-mb-md">
                    <div class="row items-center">
                        <div class="col">
                            <div class="text-h6">Nuevo Detalle</div>
                            <div class="text-caption text-grey">Rendicion #{{ page.props.accountability.id }}</div>
                        </div>
                        <div class="col-auto q-gutter-sm">
                            <q-btn color="secondary" label="Cancelar" size="12px" no-caps flat
                                @click="router.visit(route('panel.accountability.authorization.detail.index', page.props.accountability.id))" />
                            <q-btn color="primary" label="Guardar" icon="save" size="12px" no-caps
                                @click="HandleStoreForm()" :disable="Object.keys(document).length === 0" />
                        </div>
                    </div>
                </q-card>

                <!-- Seccion: General -->
                <q-card class="q-pa-lg card-form q-mb-md">
                    <div class="text-subtitle1 text-weight-medium q-mb-md">Informacion General</div>
                    <div class="row q-col-gutter-md">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Cuenta <span class="text-red">*</span></div>
                            <q-select class="input-theme" dense outlined :options="options.accounts"
                                v-model="form.account" option-value="account_code" option-label="label"
                                emit-value map-options use-input input-debounce="0"
                                @filter="HandleFilterAccounts" clearable />
                            <div v-if="errors.account" class="container-error">
                                <ul v-for="(error, index) in errors.account" :key="index" class="message-error">
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Fecha <span class="text-red">*</span></div>
                            <q-input v-model="form.date" dense outlined type="date"
                                :min="page.props.accountability.start_date"
                                :max="page.props.accountability.end_date" class="input-theme" />
                            <div v-if="errors.date" class="container-error">
                                <ul v-for="(error, index) in errors.date" :key="index" class="message-error">
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Tipo Documento <span class="text-red">*</span></div>
                            <q-select class="input-theme" dense outlined :options="options.documents"
                                v-model="form.document_id" option-value="id" option-label="name" emit-value
                                map-options @update:model-value="HandleFindDocument()" />
                            <div v-if="errors.document_id" class="container-error">
                                <ul v-for="(error, index) in errors.document_id" :key="index" class="message-error">
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Concepto <span class="text-red">*</span></div>
                            <q-input v-model="form.concept" dense outlined class="input-theme" />
                            <div v-if="errors.concept" class="container-error">
                                <ul v-for="(error, index) in errors.concept" :key="index" class="message-error">
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </q-card>

                <!-- Seccion: Libro de Compras (visible cuando se selecciona tipo documento) -->
                <q-slide-transition>
                    <q-card v-if="Object.keys(document).length > 0" class="q-pa-lg card-form q-mb-md">
                        <div class="row items-center q-mb-md">
                            <div class="col">
                                <div class="text-subtitle1 text-weight-medium">Libro de Compras</div>
                            </div>
                            <div class="col-auto">
                                <q-btn color="deep-purple" icon="qr_code_scanner" label="Escanear QR" size="12px" no-caps
                                    @click="showQrScanner = true" />
                            </div>
                        </div>
                        <div class="row q-col-gutter-md">
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-label">N. Documento <span class="text-red">*</span></div>
                                <q-input v-model="form.document_number" dense outlined class="input-theme" type="number" />
                                <div v-if="errors.document_number" class="container-error">
                                    <ul v-for="(error, index) in errors.document_number" :key="index" class="message-error">
                                        <li>{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.authorization_number_status">
                                <div class="form-label">N. Autorizacion</div>
                                <q-input v-model="form.authorization_number" dense outlined class="input-theme" />
                                <div v-if="errors.authorization_number" class="container-error">
                                    <ul v-for="(error, index) in errors.authorization_number" :key="index" class="message-error">
                                        <li>{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.cuf_status">
                                <div class="form-label">CUF</div>
                                <q-input v-model="form.cuf" dense outlined class="input-theme" />
                                <div v-if="errors.cuf" class="container-error">
                                    <ul v-for="(error, index) in errors.cuf" :key="index" class="message-error">
                                        <li>{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.control_code_status">
                                <div class="form-label">Codigo Control</div>
                                <q-input v-model="form.control_code" dense outlined class="input-theme" />
                                <div v-if="errors.control_code" class="container-error">
                                    <ul v-for="(error, index) in errors.control_code" :key="index" class="message-error">
                                        <li>{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.nit_status">
                                <div class="form-label">NIT</div>
                                <q-input v-model="form.nit" dense outlined class="input-theme"
                                    @update:model-value="HandleChangeNIT()" />
                                <div v-if="errors.nit" class="container-error">
                                    <ul v-for="(error, index) in errors.nit" :key="index" class="message-error">
                                        <li>{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.business_name_status">
                                <div class="form-label">Razon Social</div>
                                <q-input v-model="form.business_name" dense outlined class="input-theme"
                                    @update:model-value="HandleChangeBusinessName()" />
                                <div v-if="errors.business_name" class="container-error">
                                    <ul v-for="(error, index) in errors.business_name" :key="index" class="message-error">
                                        <li>{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-label">Importe <span class="text-red">*</span></div>
                                <q-input v-model="form.amount" dense type="number" outlined class="input-theme" />
                                <div v-if="errors.amount" class="container-error">
                                    <ul v-for="(error, index) in errors.amount" :key="index" class="message-error">
                                        <li>{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.discount_status">
                                <div class="form-label">Descuento</div>
                                <q-input v-model="form.discount" dense type="number" outlined class="input-theme" />
                                <div v-if="errors.discount" class="container-error">
                                    <ul v-for="(error, index) in errors.discount" :key="index" class="message-error">
                                        <li>{{ error }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.exento_status">
                                <div class="form-label">Exento</div>
                                <q-input v-model="form.excento" dense type="number" outlined class="input-theme" />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.tasas">
                                <div class="form-label">Tasas</div>
                                <q-input v-model="form.rate" type="number" dense outlined class="input-theme" />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.gift_card_status">
                                <div class="form-label">Gift Card</div>
                                <q-input v-model="form.gift_card" dense type="number" outlined class="input-theme" />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.rate_zero_status">
                                <div class="form-label">Tasa Cero</div>
                                <q-input v-model="form.rate_zero" type="number" dense outlined class="input-theme" />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.ice_status">
                                <div class="form-label">ICE</div>
                                <q-input v-model="form.ice" dense type="number" outlined class="input-theme" />
                            </div>
                        </div>
                    </q-card>
                </q-slide-transition>

                <!-- Seccion: Datos Contables (visible cuando se selecciona tipo documento) -->
                <q-slide-transition>
                    <q-card v-if="Object.keys(document).length > 0" class="q-pa-lg card-form q-mb-md">
                        <div class="text-subtitle1 text-weight-medium q-mb-md">Datos Contables</div>
                        <div class="row q-col-gutter-md">
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-label">Proyecto</div>
                                <q-select class="input-theme" dense outlined :options="options.projects"
                                    v-model="form.project_code" option-value="PrjCode" option-label="PrjName"
                                    emit-value map-options use-input input-debounce="0"
                                    @filter="HandleFilterProjects" clearable />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-label">Centro de Costo 1</div>
                                <q-select class="input-theme" dense outlined :options="options.distribution[1]"
                                    v-model="form.distribution_rule_one" option-value="PrcCode" option-label="Name"
                                    emit-value map-options clearable />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-label">Centro de Costo 2</div>
                                <q-select class="input-theme" dense outlined :options="options.distribution[2]"
                                    v-model="form.distribution_rule_second" option-value="PrcCode"
                                    option-label="Name" emit-value map-options clearable />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-label">Centro de Costo 3</div>
                                <q-select class="input-theme" dense outlined :options="options.distribution[3]"
                                    v-model="form.distribution_rule_three" option-value="PrcCode"
                                    option-label="Name" emit-value map-options clearable />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-label">Centro de Costo 4</div>
                                <q-select class="input-theme" dense outlined :options="options.distribution[4]"
                                    v-model="form.distribution_rule_four" option-value="PrcCode" option-label="Name"
                                    emit-value map-options clearable />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-label">Centro de Costo 5</div>
                                <q-select class="input-theme" dense outlined :options="options.distribution[5]"
                                    v-model="form.distribution_rule_five" option-value="PrcCode" option-label="Name"
                                    emit-value map-options clearable />
                            </div>
                        </div>
                    </q-card>
                </q-slide-transition>

            </div>
        </div>

        <!-- Dialog QR Scanner -->
        <q-dialog v-model="showQrScanner" persistent maximized transition-show="slide-up" transition-hide="slide-down">
            <q-card class="bg-dark text-white">
                <q-bar class="bg-primary">
                    <q-space />
                    <span class="text-body1">Escanear QR de Factura</span>
                    <q-space />
                    <q-btn dense flat icon="close" @click="showQrScanner = false">
                        <q-tooltip>Cerrar</q-tooltip>
                    </q-btn>
                </q-bar>
                <q-card-section class="q-pa-md">
                    <div class="row justify-center q-col-gutter-md">
                        <div class="col-xs-12 col-sm-12 col-md-6">
                            <div style="max-width: 500px; margin: 0 auto;">
                                <QrcodeStream @detect="onQrDetect" @error="onQrError" :paused="qrLoading">
                                    <div v-if="qrLoading" class="text-center q-pa-xl">
                                        <q-spinner-dots color="primary" size="40px" />
                                        <div class="q-mt-sm text-body1">Consultando factura...</div>
                                    </div>
                                </QrcodeStream>
                                <div v-if="qrCameraError" class="text-center q-pa-md">
                                    <q-icon name="videocam_off" size="48px" color="warning" />
                                    <div class="q-mt-sm text-body2">{{ qrCameraError }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6">
                            <div class="text-body2 q-mb-sm">O pegar URL manualmente:</div>
                            <q-input v-model="qrManualUrl" dense outlined dark
                                placeholder="https://siat.impuestos.gob.bo/consulta/QR?nit=..." class="q-mb-sm" />
                            <q-btn color="primary" label="Consultar" no-caps :loading="qrLoading"
                                @click="HandleConsultaSiat(qrManualUrl)" :disable="!qrManualUrl" />
                        </div>
                    </div>
                </q-card-section>
            </q-card>
        </q-dialog>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, onMounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { QrcodeStream } from "vue-qrcode-reader";
import axios from "axios";

defineProps({
    title: String,
    errors: Object,
});

const $q = useQuasar();
const page = usePage();
let message = ref(page.props.flash.message);
let type = ref(page.props.flash.type);
let document = ref({});

const options = ref({
    accounts: null,
    documents: page.props.documents,
    suppliers: page.props.suppliers,
    distribution: page.props.distribution,
    projects: page.props.projects,
});

const showQrScanner = ref(false);
const qrLoading = ref(false);
const qrManualUrl = ref("");
const qrCameraError = ref(null);

const form = ref({
    account: null,
    account_name: null,
    date: null,
    document_id: null,
    document_number: null,
    authorization_number: null,
    cuf: null,
    control_code: null,
    supplier_code: null,
    business_name: null,
    nit: null,
    concept: null,
    amount: null,
    discount: null,
    excento: null,
    rate: null,
    gift_card: null,
    rate_zero: null,
    ice: null,
    project_code: null,
    distribution_rule_one: null,
    distribution_rule_second: null,
    distribution_rule_three: null,
    distribution_rule_four: null,
    distribution_rule_five: null,
});

function HandleChangeNIT() {
    let result = options.value.suppliers.find(e => e.nit == form.value.nit);
    form.value.business_name = result ? result.business_name : form.value.business_name;
}

function HandleChangeBusinessName() {
    let result = options.value.suppliers.find(e => e.business_name == form.value.business_name);
    form.value.nit = result ? result.nit : form.value.nit;
}

function HandleStoreForm() {
    router.post(
        route("panel.accountability.authorization.detail.store", [page.props.accountability.id]),
        form.value,
        {
            onSuccess: () => {
                message.value = page.props.flash.message;
                type.value = page.props.flash.type;
                $q.notify({ type: type.value, message: message.value });
            },
        }
    );
}

function HandleFilterAccounts(val, update) {
    if (val === "") {
        update(() => { options.value.accounts = page.props.accounts; });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.accounts = page.props.accounts.filter(
            (v) => v.label.toLowerCase().indexOf(needle) > -1
        );
    });
}

function HandleFilterProjects(val, update) {
    if (val === "") {
        update(() => { options.value.projects = page.props.projects; });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.projects = page.props.projects.filter(
            (v) => v.PrjName.toLowerCase().indexOf(needle) > -1
        );
    });
}

function HandleFindDocument() {
    document.value = options.value.documents.find(e => e.id == form.value.document_id);
}

onMounted(() => {
    form.value.distribution_rule_one = page.props.auth.user.distribution_rule_one;
    form.value.distribution_rule_second = page.props.auth.user.distribution_rule_second;
    form.value.distribution_rule_three = page.props.auth.user.distribution_rule_three;
    form.value.distribution_rule_four = page.props.auth.user.distribution_rule_four;
    form.value.distribution_rule_five = page.props.auth.user.distribution_rule_five;
});

function onQrDetect(detectedCodes) {
    if (detectedCodes.length > 0) {
        HandleConsultaSiat(detectedCodes[0].rawValue);
    }
}

function onQrError(error) {
    const messages = {
        NotAllowedError: "Se necesita permiso para acceder a la camara.",
        NotFoundError: "No se encontro una camara en este dispositivo.",
        NotReadableError: "La camara esta siendo utilizada por otra aplicacion.",
        OverconstrainedError: "La camara no es compatible.",
        StreamApiNotSupportedError: "Este navegador no soporta la API de camara. Use HTTPS.",
    };
    qrCameraError.value = messages[error.name] || "Error de camara: " + error.message;
}

async function HandleConsultaSiat(url) {
    if (!url) return;
    qrLoading.value = true;
    try {
        const response = await axios.post(route("siat.consulta"), { url });
        const factura = response.data;

        if (factura.error) {
            $q.notify({ type: "negative", message: factura.error });
            return;
        }

        if (factura.numeroFactura) form.value.document_number = factura.numeroFactura;
        if (factura.cuf) form.value.cuf = factura.cuf;
        if (factura.nitEmisor) form.value.nit = String(factura.nitEmisor);
        if (factura.razonSocialEmisor) form.value.business_name = factura.razonSocialEmisor;
        if (factura.montoTotal) form.value.amount = factura.montoTotal;
        if (factura.fechaEmision) form.value.date = factura.fechaEmision.substring(0, 10);

        showQrScanner.value = false;
        qrManualUrl.value = "";
        $q.notify({ type: "positive", message: "Datos de factura cargados correctamente" });
    } catch (error) {
        const msg = error.response?.data?.error || "Error al consultar SIAT";
        $q.notify({ type: "negative", message: msg });
    } finally {
        qrLoading.value = false;
    }
}
</script>
