<template>

    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <q-stepper v-model="step" header-nav ref="stepper" color="primary" animated flat alternative-labels
                    class="backgroup-theme">
                    <q-step :name="1" title="General" icon="settings" :done="step > 1"
                        :error="Object.keys(errors).length > 0 ? true : false">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">
                                        Información General
                                    </h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                        router.visit(
                                            route(
                                                'panel.accountability.manage.detail.index',
                                                [
                                                    page.props.profile.id,
                                                    page.props
                                                        .accountability.id,
                                                ]
                                            )
                                        )
                                        " flat />
                                    <q-btn @click="$refs.stepper.next()" color="primary" no-caps size="12px"
                                        label="Continuar" />
                                </div>
                            </div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Cuenta <span class="text-red">*</span>
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.accounts"
                                        v-model="form.account" option-value="account_code" option-label="label"
                                        emit-value map-options use-input input-debounce="0"
                                        @filter="HandleFilterAccounts" clearable />
                                    <div v-if="errors.account" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.account" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Fecha
                                        <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.date" dense outlined type="date" :min="page.props.accountability.start_date
                                        " :max="page.props.accountability.end_date
                                            " class="input-theme" />
                                    <div v-if="errors.date" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.date" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Tipo Documento
                                        <span class="text-red">*</span>
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.documents"
                                        v-model="form.document_id" option-value="id" option-label="name" emit-value
                                        map-options @update:model-value="HandleFindDocument()" />
                                    <div v-if="errors.document_id" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.document_id" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Concepto <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.concept" dense outlined class="input-theme" />
                                    <div v-if="errors.concept" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.concept" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step :name="2" title="Libro Compras" icon="eva-file-text-outline" :done="step > 1"
                        :error="Object.keys(errors).length > 0 ? true : false">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Libro de Compras</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                        router.visit(
                                            route(
                                                'panel.accountability.manage.detail.index',
                                                [
                                                    page.props.profile.id,
                                                    page.props
                                                        .accountability.id,
                                                ]
                                            )
                                        )
                                        " flat />
                                    <q-btn flat color="primary" @click="$refs.stepper.previous()" label="Atras" no-caps
                                        size="12px" />
                                    <q-btn @click="$refs.stepper.next()" color="primary" no-caps size="12px"
                                        label="Continuar" />
                                </div>
                            </div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4">
                                    <div class="form-label" for="device_name">
                                        Nº Documento
                                        <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.document_number" dense outlined class="input-theme"
                                        type="number" />
                                    <div v-if="errors.document_number" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.document_number" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4"
                                    v-if="document.authorization_number_status">
                                    <div class="form-label" for="device_name">
                                        Nº Autorización
                                    </div>
                                    <q-input v-model="form.authorization_number" dense outlined class="input-theme" />
                                    <div v-if="errors.authorization_number" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.authorization_number" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.cuf_status">
                                    <div class="form-label" for="device_name">
                                        CUF
                                    </div>
                                    <q-input v-model="form.cuf" dense outlined class="input-theme" />
                                    <div v-if="errors.cuf" class="container-error">
                                        <ul v-for="(error, index) in errors.cuf" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.control_code_status">
                                    <div class="form-label" for="device_name">
                                        Codigo Control
                                    </div>
                                    <q-input v-model="form.control_code" dense outlined class="input-theme" />
                                    <div v-if="errors.control_code" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.control_code" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.business_name_status">
                                    <div class="form-label" for="device_name">
                                        Razón Social
                                    </div>
                                    <q-input v-model="form.business_name" dense outlined class="input-theme" @update:model-value="HandleChangeBusinessName()"/>
                                    <div v-if="errors.business_name" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.business_name" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.nit_status">
                                    <div class="form-label" for="device_name">
                                        NIT
                                    </div>
                                    <q-input v-model="form.nit" dense outlined class="input-theme" @update:model-value="HandleChangeNIT()"/>
                                    <div v-if="errors.nit" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.nit" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4">
                                    <div class="form-label" for="device_name">
                                        Importe <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.amount" dense type="number" outlined class="input-theme" />
                                    <div v-if="errors.amount" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.amount" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.discount_status">
                                    <div class="form-label" for="device_name">
                                        Descuento
                                    </div>
                                    <q-input v-model="form.discount" dense type="number" outlined class="input-theme" />
                                    <div v-if="errors.discount" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.discount" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.exento_status">
                                    <div class="form-label" for="device_name">
                                        Exento
                                    </div>
                                    <q-input v-model="form.excento" dense type="number" outlined class="input-theme" />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.tasas">
                                    <div class="form-label" for="device_name">
                                        Tasas
                                    </div>
                                    <q-input v-model="form.rate" type="number" dense outlined class="input-theme" />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.gift_card_status">
                                    <div class="form-label" for="device_name">
                                        Gift Card
                                    </div>
                                    <q-input v-model="form.gift_card" dense type="number" outlined
                                        class="input-theme" />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.rate_zero_status">
                                    <div class="form-label" for="device_name">
                                        Tasa Cero
                                    </div>
                                    <q-input v-model="form.rate_zero" type="number" dense outlined
                                        class="input-theme" />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" v-if="document.ice_status">
                                    <div class="form-label" for="device_name">
                                        ICE
                                    </div>
                                    <q-input v-model="form.ice" dense type="number" outlined class="input-theme" />
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step :name="3" title="Contable" icon="eva-pantone-outline"
                        :error="Object.keys(errors).length > 0 ? true : false">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Datos Contables</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                        router.visit(
                                            route(
                                                'panel.accountability.manage.detail.index',
                                                [
                                                    page.props.profile.id,
                                                    page.props
                                                        .accountability.id,
                                                ]
                                            )
                                        )
                                        " flat />
                                    <q-btn flat color="primary" @click="$refs.stepper.previous()" label="Atras" no-caps
                                        size="12px" />
                                    <q-btn color="primary" label="Actualizar" size="12px" no-caps
                                        @click="HandleUpdateForm()" />
                                </div>
                            </div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Proyecto
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.projects"
                                        v-model="form.project_code" option-value="PrjCode" option-label="PrjCode"
                                        emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 1
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[1]"
                                        v-model="form.distribution_rule_one" option-value="PrcCode" option-label="Name"
                                        emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 2
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[2]"
                                        v-model="form.distribution_rule_second" option-value="PrcCode"
                                        option-label="Name" emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 3
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[3]"
                                        v-model="form.distribution_rule_three" option-value="PrcCode"
                                        option-label="Name" emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 4
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[4]"
                                        v-model="form.distribution_rule_four" option-value="PrcCode" option-label="Name"
                                        emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 5
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[5]"
                                        v-model="form.distribution_rule_five" option-value="PrcCode" option-label="Name"
                                        emit-value map-options />
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                </q-stepper>
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
let document = ref({})

const options = ref({
    accounts: null,
    documents: page.props.documents,
    suppliers: page.props.suppliers,
    distribution: page.props.distribution,
    projects: page.props.projects,
});

let step = ref(1);

const form = ref(page.props.data);

function HandleChangeNIT(){
    let result=null
    result=options.value.suppliers.find(e=>e.nit==form.value.nit)
    form.value.business_name=result?result.business_name:form.value.business_name
}
function HandleChangeBusinessName(){
    let result=null
    result=options.value.suppliers.find(e=>e.business_name==form.value.business_name)
    form.value.nit=result?result.nit:form.value.nit
}
function HandleUpdateForm() {
    router.post(
        route("panel.accountability.manage.detail.update", [
            page.props.profile.id,
            page.props.accountability.id,
        ]),
        form.value,
        {
            onSuccess: () => {
                message.value = page.props.flash.message;
                type.value = page.props.flash.type;
                $q.notify({
                    type: type.value,
                    message: message.value,
                });
            },
        }
    );
}
function HandleFilterAccounts(val, update) {
    if (val === "") {
        update(() => {
            options.value.accounts = page.props.accounts;
        });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.accounts = page.props.accounts.filter(
            (v) => v.label.toLowerCase().indexOf(needle) > -1
        );
    });
}
function HandleFilterSuppliers(val, update) {
    if (val === "") {
        update(() => {
            options.value.suppliers = page.props.suppliers;
        });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.suppliers = page.props.suppliers.filter(
            (v) => v.business.toLowerCase().indexOf(needle) > -1
        );
    });
}
onMounted(() => {
    options.value.accounts = page.props.accounts;
    options.value.documents = page.props.documents;
    form.value.document_id = parseInt(form.value.document_id);
    document.value = options.value.documents.find(e => e.id == form.value.document_id);

});

function HandleFindDocument() {
    document.value = options.value.documents.find(e => e.id == form.value.document_id);
}
</script>
