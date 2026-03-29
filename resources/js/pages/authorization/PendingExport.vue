<template>
    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">
            <q-card class="card-form">
                <q-table
                    :rows="data"
                    :columns="columns"
                    row-key="id"
                    class="table-theme"
                    :filter="filter"
                    separator="horizontal"
                    flat
                    bordered
                    :table-header-class="{ 'table-header-theme': true }"
                >
                    <template v-slot:top>
                        <h5 class="title-form">Pendientes de Exportación SAP</h5>
                        <q-space />
                        <q-input
                            outlined
                            dense
                            color="primary"
                            placeholder="Buscar..."
                            v-model="filter"
                            class="input-theme"
                        >
                            <template v-slot:append>
                                <q-icon name="search" />
                            </template>
                        </q-input>
                    </template>

                    <template v-slot:header="props">
                        <q-tr :props="props">
                            <q-th v-for="col in props.cols" :key="col.name" :props="props">
                                {{ col.label }}
                            </q-th>
                        </q-tr>
                    </template>

                    <template v-slot:body="props">
                        <q-tr :props="props">
                            <q-td key="id" :props="props">{{ props.row.id }}</q-td>
                            <q-td key="user_name" :props="props">{{ props.row.user?.name }}</q-td>
                            <q-td key="profile_name" :props="props">{{ props.row.profile?.name }}</q-td>
                            <q-td key="description" :props="props">{{ props.row.description }}</q-td>
                            <q-td key="employee_name" :props="props">{{ props.row.employee_name || '—' }}</q-td>
                            <q-td key="total" :props="props" class="text-right">
                                {{ formatAmount(props.row.total) }}
                            </q-td>
                            <q-td key="end_date" :props="props">{{ props.row.end_date }}</q-td>
                            <q-td key="actions" :props="props">
                                <div class="q-gutter-xs">
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        icon="cloud_upload"
                                        color="positive"
                                        @click="HandleReExport(props.row.id)"
                                    >
                                        <q-tooltip>Exportar a SAP</q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        icon="visibility"
                                        color="primary"
                                        @click="HandleDetail(props.row.id)"
                                    >
                                        <q-tooltip>Ver detalle</q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        icon="edit"
                                        color="secondary"
                                        @click="HandleEdit(props.row.id)"
                                    >
                                        <q-tooltip>Editar</q-tooltip>
                                    </q-btn>
                                </div>
                            </q-td>
                        </q-tr>
                    </template>

                    <template v-slot:no-data>
                        <div class="full-width text-center q-pa-xl text-grey-6">
                            <q-icon name="check_circle" size="64px" color="positive" class="q-mb-md" />
                            <div>No hay rendiciones pendientes de exportación</div>
                        </div>
                    </template>
                </q-table>
            </q-card>
        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

defineProps({ title: String });

const $q = useQuasar();
const page = usePage();
const data = ref(page.props.data);
const filter = ref("");

const columns = [
    { name: "id",           align: "center", label: "ID",          field: "id",           sortable: true },
    { name: "user_name",    align: "left",   label: "Usuario",     field: row => row.user?.name,    sortable: true },
    { name: "profile_name", align: "left",   label: "Perfil",      field: row => row.profile?.name, sortable: true },
    { name: "description",  align: "left",   label: "Descripción", field: "description",  sortable: true },
    { name: "employee_name",align: "left",   label: "Empleado",    field: "employee_name",sortable: true },
    { name: "total",        align: "right",  label: "Monto",       field: "total",        sortable: true },
    { name: "end_date",     align: "center", label: "Fecha Fin",   field: "end_date",     sortable: true },
    { name: "actions",      align: "center", label: "Acciones",    field: "actions" },
];

function formatAmount(val) {
    return Number(val ?? 0).toLocaleString("es-BO", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function HandleReExport(id) {
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
                onSuccess: () => {
                    data.value = page.props.data;
                    $q.notify({
                        type: page.props.flash.type,
                        message: page.props.flash.message,
                    });
                },
            }
        );
    });
}

function HandleDetail(id) {
    router.visit(route("panel.accountability.authorization.detail.index", id));
}

function HandleEdit(id) {
    router.visit(route("panel.accountability.authorization.edit", id));
}
</script>
