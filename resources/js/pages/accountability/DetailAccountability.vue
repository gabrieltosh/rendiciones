<template>
    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">
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
                            <q-td style="width:110px !important">
                                <div class="text-center q-gutter-xs">
                                    <q-btn
                                        size="sm"
                                        color="primary"
                                        dense
                                        @click="HandleEditAccountability(props.row.id)"
                                        icon="eva-plus"
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
                                        color="secondary"
                                        dense
                                        @click="HandleEditAccountability(props.row.id)"
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
                                        @click="HandleDeleteAccountability(props.row.id)"
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
                                {{ props.row.rate_zero }}
                            </q-td><q-td key="ice" :props="props">
                                {{ props.row.ice }}
                            </q-td>
                            <q-td key="project_code" :props="props">
                                {{ props.row.project_code }}
                            </q-td>
                        </q-tr>
                    </template>
                </q-table>
            </q-card>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, watch } from "vue";
import { Head, usePage,router } from "@inertiajs/vue3";
import {route} from "ziggy-js"
import { useQuasar } from "quasar";
defineProps({
    title: String,
    data: Object,
});
const $q = useQuasar();
const page = usePage();
let data = ref(page.props.data)
let message = ref(page.props.flash.message)
let type = ref(page.props.flash.type)
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
            label: "Nombre de Cuenta",
            field: "employee",
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
            name: "document_id",
            align: "center",
            label: "Documento",
            field: "document_id",
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
        },{
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
        },
        {
            name: "project_code",
            align: "center",
            label: "Proyecto",
            field: "project_code",
        },
        {
            name: "distribution_rule_one",
            align: "center",
            label: "Norma Rep. 1",
            field: "distribution_rule_one",
        },
        {
            name: "distribution_rule_second",
            align: "center",
            label: "Norma Rep. 2",
            field: "distribution_rule_second",
        },
        {
            name: "distribution_rule_three",
            align: "center",
            label: "Norma Rep. 3",
            field: "distribution_rule_three",
        },
        {
            name: "distribution_rule_four",
            align: "center",
            label: "Norma Rep. 4",
            field: "distribution_rule_four",
        },
        {
            name: "distribution_rule_five",
            align: "center",
            label: "Norma Rep. 5",
            field: "distribution_rule_five",
        },
    ],
});

function HandleCreateDocument() {
    router.visit(route("panel.accountability.manage.detail.create",[page.props.profile.id,page.props.accountability.id]));
}
function HandleEditAccountability(id) {
    router.visit(route("panel.accountability.manage.edit",[page.props.profile.id, id]));
}
function HandleDeleteAccountability($id) {
    $q.dialog({
        title: "Confirmar",
        message: "¿Esta seguro de eliminar el Usuario?",
        cancel: true,
        persistent: true,
    })
        .onOk(() => {
            router.delete(route("panel.accountability.delete", $id), {
                onSuccess: () => {
                    data.value = page.props.data;
                    message.value = page.props.flash.message;
                    type.value = page.props.flash.type;
                    $q.notify({
                        type: type.value,
                        message: message.value,
                    });
                },
            });
        })
        .onCancel(() => {})
        .onDismiss(() => {});
}
</script>
<style lang="scss">


</style>
