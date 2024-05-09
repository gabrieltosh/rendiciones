<template>
    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">
            <q-card class="card-form q-pa-md">
                <h5 class="title-form">Información de Rendicion</h5>
                <div class="row q-col-gutter-md">
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                        <div class="form-label" for="device_name">
                            Cuenta
                        </div>
                         <div class="text-grid q-pl-xs">
                             {{ page.props.accountability.account_name }}
                         </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                        <div class="form-label" for="device_name">
                            Empleado
                        </div>
                         <div class="text-grid q-pl-xs">
                             {{ page.props.accountability.employee_name }}
                         </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                        <div class="form-label" for="device_name">
                            Monto
                        </div>
                         <div class="text-grid q-pl-xs">
                             {{ page.props.accountability.total }}
                         </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                        <div class="form-label" for="device_name">
                            Descripción
                        </div>
                         <div class="text-grid q-pl-xs">
                             {{ page.props.accountability.description }}
                         </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                        <div class="form-label" for="device_name">
                            Fecha Inicio
                        </div>
                         <div class="text-grid q-pl-xs">
                             {{ page.props.accountability.start_date }}
                         </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                        <div class="form-label" for="device_name">
                            Fecha Final
                        </div>
                         <div class="text-grid q-pl-xs">
                             {{ page.props.accountability.end_date }}
                         </div>
                    </div>
                </div>
            </q-card>
            <q-card class="card-form q-mt-sm">
                <q-table
                    :loading="table.loading"
                    :rows="data"
                    :columns="table.columns"
                    row-key="id"
                    class="table-theme"
                    :filter="table.filter"
                    separator="horizontal"
                    :table-header-class="{ 'table-header-theme': true }"
                    flat
                    bordered
                    grid
                >
                    <template v-slot:top>
                        <h5 class="title-form">Detalle de Rendicion</h5>
                        <q-space />
                        <q-btn
                            color="primary"
                            label="Añadir Documento"
                            size="11px"
                            no-caps
                            @click="HandleCreateDocument()"
                        />
                        <q-space />
                        <q-input
                            outlined
                            dense
                            color="primary"
                            placeholder="Buscar..."
                            v-model="table.filter"
                            class="input-theme"
                        >
                            <template v-slot:append>
                                <q-icon name="search" />
                            </template>
                        </q-input>
                    </template>
                    <template v-slot:header="props">
                        <q-tr :props="props">
                            <q-th auto-width />
                            <q-th
                                v-for="col in props.cols"
                                :key="col.name"
                                :props="props"
                            >
                                {{ col.label }}
                            </q-th>
                        </q-tr>
                    </template>
                    <template v-slot:body="props">
                        <q-tr :props="props">
                            <q-td style="width: 110px !important">
                                <div class="text-center q-gutter-xs">
                                    <q-btn size="sm" color="primary" dense @click="props.expand = !props.expand" :icon="props.expand ? 'remove' : 'add'" />
                                    <q-btn
                                        size="sm"
                                        color="secondary"
                                        dense
                                        @click="
                                            HandleEditAccountability(
                                                props.row.id
                                            )
                                        "
                                        icon="eva-edit-2-outline"
                                    >
                                        <q-tooltip
                                            class="bg-secondary"
                                            :offset="[10, 10]"
                                        >
                                            Editar de usuario
                                        </q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        size="sm"
                                        color="red"
                                        dense
                                        @click="
                                            HandleDeleteAccountability(
                                                props.row.id
                                            )
                                        "
                                        icon="eva-trash-2-outline"
                                    >
                                        <q-tooltip
                                            class="bg-red"
                                            :offset="[10, 10]"
                                        >
                                            Eliminar usuario
                                        </q-tooltip>
                                    </q-btn>
                                </div>
                            </q-td>
                            <q-td key="id" :props="props">
                                {{ props.row.id }}
                            </q-td>
                            <q-td key="account" :props="props">
                                {{ props.row.account}}
                            </q-td>
                            <q-td key="account_name" :props="props">
                                {{ props.row.account_name }}
                            </q-td>
                            <q-td key="date" :props="props">
                                {{ props.row.date }}
                            </q-td>
                            <q-td key="document_id" :props="props">
                                {{ props.row.document_id }}
                            </q-td>
                            <q-td key="document_number" :props="props">
                                {{ props.row.document_number }}
                            </q-td>
                            <q-td key="authorization_number" :props="props">
                                {{ props.row.authorization_number }}
                            </q-td>
                            <q-td key="cuf" :props="props">
                                {{ props.row.cuf }}
                            </q-td>
                            <q-td key="control_code" :props="props">
                                {{ props.row.control_code }}
                            </q-td>
                            <q-td key="supplier_code" :props="props">
                                {{ props.row.supplier_code }}
                            </q-td>
                            <q-td key="business_name" :props="props">
                                {{ props.row.business_name }}
                            </q-td>
                            <q-td key="nit" :props="props">
                                {{ props.row.nit }}
                            </q-td>
                            <q-td key="concept" :props="props">
                                {{ props.row.concept }}
                            </q-td>
                            <q-td key="amount" :props="props">
                                {{ props.row.amount }}
                            </q-td>
                            <q-td key="discount" :props="props">
                                {{ props.row.discount }}
                            </q-td>
                            <q-td key="excento" :props="props">
                                {{ props.row.excento }}
                            </q-td>
                            <q-td key="rate" :props="props">
                                {{ props.row.rate }}
                            </q-td>
                            <q-td key="gift_card" :props="props">
                                {{ props.row.gift_card }}
                            </q-td>
                            <q-td key="rate_zero" :props="props">
                                {{ props.row.rate_zero }} </q-td
                            ><q-td key="ice" :props="props">
                                {{ props.row.ice }}
                            </q-td>
                            <q-td key="project_code" :props="props">
                                {{ props.row.project_code }}
                            </q-td>
                        </q-tr>
                        <q-tr v-show="props.expand" :props="props">
                        <q-td colspan="100%">
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                                    <div class="form-label" for="device_name">
                                        Proyecto
                                    </div>
                                    <div class="text-grid q-pl-xs">
                                        {{ props.row.project_code }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 1
                                    </div>
                                    <div class="text-grid q-pl-xs">
                                        {{ props.row.distribution_rule_one }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 2
                                    </div>
                                    <div class="text-grid q-pl-xs">
                                        {{ props.row.distribution_rule_second }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 3
                                    </div>
                                    <div class="text-grid q-pl-xs">
                                        {{ props.row.distribution_rule_three }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 4
                                    </div>
                                    <div class="text-grid q-pl-xs">
                                        {{ props.row.distribution_rule_four }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 5
                                    </div>
                                    <div class="text-grid q-pl-xs">
                                        {{ props.row.distribution_rule_five }}
                                    </div>
                                </div>
                            </div>
                        </q-td>
                        </q-tr>
                    </template>
                    <template v-slot:item="props">
                        <div class="q-pa-xs col-xs-12 col-sm-6 col-md-3">
                            <q-card class="card-grid q-mt-md shadow-0" bordered>
                                <div class="col-12 q-mt-sm">
                                    <q-list dense>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Cuenta</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.account
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Nombre Cuenta</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.account_name
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Fecha</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.date
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Nº de Factura</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.document_number
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Nº Autorización</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.authorization_number
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >CUF</q-item-label
                                                >
                                                <q-item-label class="text-grid" lines="1">{{
                                                    props.row.cuf?props.row.cuf:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Codigo Control</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.control_code?props.row.control_code:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Razón Social</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.business_name?props.row.business_name:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >NIT</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.nit?props.row.nit:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Importe</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.amount?props.row.amount:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Descuento</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.discount?props.row.discount:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Gift Card</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.gift_card?props.row.gift_card:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Tasa Cero</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.rate_zero?props.row.rate_zero:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Excento</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.excento?props.row.excento:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Tasas</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.rate?props.row.rate:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Proyecto</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.project_code?props.row.project_code:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Norma Reparto 1</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.distribution_rule_one?props.row.distribution_rule_one:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Norma Reparto 2</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.distribution_rule_second?props.row.distribution_rule_second:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Norma Reparto 3</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.distribution_rule_three?props.row.distribution_rule_three:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Norma Reparto 4</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.distribution_rule_four?props.row.distribution_rule_four:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-left" v-if="props.expand">
                                            <q-item-section>
                                                <q-item-label class="title-grid"
                                                    >Norma Reparto 5</q-item-label
                                                >
                                                <q-item-label class="text-grid">{{
                                                    props.row.distribution_rule_five?props.row.distribution_rule_five:'-'
                                                }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                    </q-list>
                                    <q-separator spaced />
                                </div>
                                <q-card-actions align="center" class="q-pt-none">
                                    <q-btn
                                        size="sm"
                                        color="primary"
                                        @click="props.expand = !props.expand" :icon="props.expand ? 'remove' : 'add'"
                                        icon="eva-plus"
                                    >
                                        <q-tooltip
                                            class="bg-secondary"
                                            :offset="[10, 10]"
                                        >
                                            Detalle de Rendición
                                        </q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        size="sm"
                                        color="secondary"
                                        @click="
                                            HandleEditDocument(
                                                props.row.id
                                            )
                                        "
                                        icon="eva-edit-2-outline"
                                    >
                                        <q-tooltip
                                            class="bg-secondary"
                                            :offset="[10, 10]"
                                        >
                                            Editar de Rendición
                                        </q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        size="sm"
                                        color="red"
                                        @click="
                                            HandleDeleteDocument(
                                                props.row.id
                                            )
                                        "
                                        icon="eva-trash-2-outline"
                                    >
                                        <q-tooltip
                                            class="bg-red"
                                            :offset="[10, 10]"
                                        >
                                            Eliminar Rendición
                                        </q-tooltip>
                                    </q-btn>
                                </q-card-actions>
                            </q-card>
                        </div>
                    </template>
                </q-table>
            </q-card>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, watch } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
defineProps({
    title: String,
    data: Object,
});
const $q = useQuasar();
const page = usePage();
let data = ref(page.props.documents);
let message = ref(page.props.flash.message);
let type = ref(page.props.flash.type);
const table = ref({
    filter: "",
    loading: false,
    columns: [
        {
            name: "id",
            align: "center",
            label: "ID",
            field: "id",
            sortable: true,
        },
        {
            name: "account",
            align: "center",
            label: "Cuenta",
            field: "account",
            sortable: true,
        },
        {
            name: "account_name",
            align: "center",
            label: "Nombre de Cuenta",
            field: "account_name",
            sortable: true,
        },
        {
            name: "date",
            align: "center",
            label: "Fecha",
            field: "date",
            sortable: true,
        },
        {
            name: "document_number",
            align: "center",
            label: "Numero Doc.",
            field: "document_number",
        },
        {
            name: "authorization_number",
            align: "center",
            label: "Autorización",
            field: "authorization_number",
        },
        {
            name: "cuf",
            align: "center",
            label: "CUF",
            field: "cuf",
        },
        {
            name: "control_code",
            align: "center",
            label: "Cod. Control",
            field: "control_code",
        },
        {
            name: "nit",
            align: "center",
            label: "NIT",
            field: "nit",
        },
        {
            name: "business_name",
            align: "center",
            label: "Razon Social",
            field: "business_name",
        },
        {
            name: "amount",
            align: "center",
            label: "Importe",
            field: "amount",
        },
        {
            name: "discount",
            align: "center",
            label: "Descto",
            field: "discount",
        },
        {
            name: "rate_zero",
            align: "center",
            label: "Tasa Cero",
            field: "rate_zero",
        },
        {
            name: "excento",
            align: "center",
            label: "Exento",
            field: "excento",
        },
        {
            name: "rate",
            align: "center",
            label: "Tasas",
            field: "rate",
        },
        {
            name: "gift_card",
            align: "center",
            label: "GiftCard",
            field: "gift_card",
        },
        {
            name: "ice",
            align: "center",
            label: "ICE",
            field: "ice",
        }
    ],
});

function HandleCreateDocument() {
    router.visit(
        route("panel.accountability.manage.detail.create", [
            page.props.profile.id,
            page.props.accountability.id,
        ])
    );
}
function HandleEditDocument(id) {
    router.visit(
        route("panel.accountability.manage.detail.edit", [page.props.profile.id, page.props.accountability.id ,id])
    );
}
function HandleDeleteDocument(id) {
    $q.dialog({
        title: "Confirmar",
        message: "¿Esta seguro de eliminar el documento?",
        cancel: true,
        persistent: true,
    })
        .onOk(() => {
            router.delete(route("panel.accountability.manage.detail.delete",  [page.props.profile.id, page.props.accountability.id ,id]), {
                onSuccess: () => {
                    data.value = page.props.data;
                    message.value = page.props.flash.message;
                    type.value = page.props.flash.type;
                    $q.notify({
                        type: type.value,
                        message: message.value,
                    });
                    data.value=page.props.documents
                },
            });
        })
        .onCancel(() => {})
        .onDismiss(() => {});
}
</script>
<style lang="scss"></style>
