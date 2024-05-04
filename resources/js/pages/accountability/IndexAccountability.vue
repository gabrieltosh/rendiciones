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
                        <h5 class="title-form">Lista de Rendiciones</h5>
                        <q-space />
                        <q-btn
                            color="primary"
                            label="Crear Rendición"
                            size="11px"
                            no-caps
                            @click="HandleCreateAccountability()"
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
                                        @click="HandleDetailAccountability(props.row.id)"
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
                                        dense
                                        @click="HandleEditAccountability(props.row.id)"
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
                                        dense
                                        @click="HandleDeleteAccountability(props.row.id)"
                                        icon="eva-trash-2-outline"
                                    >
                                        <q-tooltip
                                            class="bg-red"
                                            :offset="[10, 10]"
                                        >
                                            Eliminar Rendición
                                        </q-tooltip>
                                    </q-btn>
                                </div>
                            </q-td>
                            <q-td key="id" :props="props">
                                {{ props.row.id }}
                            </q-td>
                            <q-td key="employee" :props="props">
                                {{ props.row.employee }}
                            </q-td>
                            <q-td key="account" :props="props">
                                {{ props.row.account }}
                            </q-td>
                            <q-td key="total" :props="props">
                                {{ props.row.total }}
                            </q-td>
                            <q-td key="start_date" :props="props">
                                {{ props.row.start_date }}
                            </q-td>
                            <q-td key="end_date" :props="props">
                                {{ props.row.end_date }}
                            </q-td>
                            <q-td key="created_at" :props="props">
                                {{ props.row.created_at }}
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
            name: "employee",
            align: "center",
            label: "Empleado",
            field: "employee",
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
            name: "total",
            align: "center",
            label: "Monto",
            field: "total",
            sortable: true,
        },
        {
            name: "start_date",
            align: "center",
            label: "Fecha Inicio",
            field: "start_date",
        },
        {
            name: "end_date",
            align: "center",
            label: "Fecha Fin",
            field: "end_date",
        },
        {
            name: "created_at",
            align: "center",
            label: "Fecha de Creación",
            field: "created_at",
            sortable: true,
        },
    ],
});

function HandleCreateAccountability() {
    router.visit(route("panel.accountability.manage.create",page.props.profile.id));
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
function HandleDetailAccountability(id){
    router.visit(route("panel.accountability.manage.detail.index",[page.props.profile.id, id]));

}
</script>
<style lang="scss">


</style>
