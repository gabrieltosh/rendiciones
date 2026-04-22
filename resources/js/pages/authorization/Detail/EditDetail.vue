<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">

                <!-- Header -->
                <q-card class="q-pa-md card-form q-mb-md">
                    <div class="row items-center">
                        <div class="col">
                            <div class="text-h6">Editar Detalle</div>
                            <div class="text-caption text-grey">Rendicion #{{ page.props.accountability.id }}</div>
                        </div>
                        <div class="col-auto q-gutter-sm">
                            <q-btn color="secondary" label="Cancelar" size="12px" no-caps flat
                                @click="router.visit(route('panel.accountability.authorization.detail.index', page.props.accountability.id))" />
                            <q-btn color="primary" label="Actualizar" icon="save" size="12px" no-caps
                                @click="HandleUpdateForm()" />
                        </div>
                    </div>
                </q-card>

                <!-- Seccion: General -->
                <q-card class="q-pa-lg card-form q-mb-md">
                    <div class="text-subtitle1 text-weight-medium q-mb-md">Informacion General</div>
                    <div class="row q-col-gutter-md">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Concepto <span class="text-red">*</span></div>
                            <q-select class="input-theme" dense outlined :options="options.accounts"
                                v-model="form.account" option-value="key" option-label="label"
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
                            <div class="form-label">Comentario <span class="text-red">*</span></div>
                            <q-input v-model="form.concept" dense outlined class="input-theme" />
                            <div v-if="errors.concept" class="container-error">
                                <ul v-for="(error, index) in errors.concept" :key="index" class="message-error">
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </q-card>

                <!-- Seccion: Libro de Compras -->
                <q-card v-if="Object.keys(document).length > 0" class="q-pa-lg card-form q-mb-md">
                    <div class="text-subtitle1 text-weight-medium q-mb-md">Libro de Compras</div>
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
                        <div class="col-xs-12 col-sm-6 col-md-4" v-for="(field, i) in form.field" :key="i">
                            <div class="form-label">{{ field.name }} <span class="text-red">*</span></div>
                            <q-input v-model="field.value" dense type="number" outlined class="input-theme" />
                            <div v-if="errors['field.' + i + '.value']" class="container-error">
                                <ul v-for="(error, index) in errors['field.' + i + '.value']" :key="index" class="message-error">
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

                <!-- Seccion: Datos Contables -->
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
                                v-model="form.distribution_rule_one" option-label="Name" clearable />
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Centro de Costo 2</div>
                            <q-select class="input-theme" dense outlined :options="options.distribution[2]"
                                v-model="form.distribution_rule_second" option-label="Name" clearable />
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Centro de Costo 3</div>
                            <q-select class="input-theme" dense outlined :options="options.distribution[3]"
                                v-model="form.distribution_rule_three" option-label="Name" clearable />
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Centro de Costo 4</div>
                            <q-select class="input-theme" dense outlined :options="options.distribution[4]"
                                v-model="form.distribution_rule_four" option-label="Name" clearable />
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-label">Centro de Costo 5</div>
                            <q-select class="input-theme" dense outlined :options="options.distribution[5]"
                                v-model="form.distribution_rule_five" option-label="Name" clearable />
                        </div>
                    </div>
                </q-card>

            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, onMounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

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

const form = ref(page.props.data);

function HandleChangeNIT() {
    let result = options.value.suppliers.find(e => e.nit == form.value.nit);
    form.value.business_name = result ? result.business_name : form.value.business_name;
}

function HandleChangeBusinessName() {
    let result = options.value.suppliers.find(e => e.business_name == form.value.business_name);
    form.value.nit = result ? result.nit : form.value.nit;
}

function HandleUpdateForm() {
    router.post(
        route("panel.accountability.authorization.detail.update", [page.props.accountability.id]),
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
    form.value.field = [];
    document.value = options.value.documents.find(e => e.id == form.value.document_id);
    document.value.fields.forEach(e => {
        form.value.field.push({ name: e.name, value: null, id: e.id });
    });
}

onMounted(() => {
    options.value.accounts = page.props.accounts;
    options.value.documents = page.props.documents;
    form.value.document_id = parseInt(form.value.document_id);
    document.value = options.value.documents.find(e => e.id == form.value.document_id) ?? {};

    // Map pre-loaded distribution objects to actual option references to avoid [object Object]
    const distFields = [
        { field: "distribution_rule_one",    dim: 1 },
        { field: "distribution_rule_second", dim: 2 },
        { field: "distribution_rule_three",  dim: 3 },
        { field: "distribution_rule_four",   dim: 4 },
        { field: "distribution_rule_five",   dim: 5 },
    ];
    distFields.forEach(({ field, dim }) => {
        const val = form.value[field];
        if (val && typeof val === "object" && options.value.distribution[dim]) {
            const code = val.OcrCode || val.PrcCode;
            if (code) {
                form.value[field] =
                    options.value.distribution[dim].find(
                        (o) => (o.OcrCode || o.PrcCode) === code
                    ) || null;
            } else {
                form.value[field] = null;
            }
        } else if (!val || (typeof val === "object" && Object.keys(val).length === 0)) {
            form.value[field] = null;
        }
    });
});
</script>
