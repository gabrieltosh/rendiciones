<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">
                <PageHeader
                    title="Proveedores"
                    subtitle="Proveedores registrados para las facturas de las rendiciones"
                    icon="eva-car-outline"
                >
                    <template #actions>
                        <q-btn data-tour="create-supplier"
                            color="primary"
                            icon="add"
                            label="Crear Proveedor"
                            no-caps
                            unelevated
                            @click="HandleCreateSupplier()"
                        />
                    </template>
                </PageHeader>

                <q-card class="card-form">
                    <q-table data-tour="suppliers-table"
                        :rows="data"
                        :columns="table.columns"
                        row-key="id"
                        class="table-theme"
                        :filter="table.filter"
                        flat
                        separator="horizontal"
                        :table-header-class="{ 'table-header-theme': true }"
                        :pagination="{ rowsPerPage: 15 }"
                        :grid="$q.screen.xs"
                    >
                        <template v-slot:top>
                            <div class="text-subtitle1 text-weight-medium">Lista de Proveedores</div>
                            <q-space />
                            <q-input
                                outlined
                                dense
                                clearable
                                placeholder="Buscar razón social o NIT…"
                                v-model="table.filter"
                                class="input-theme search-input"
                                aria-label="Buscar proveedores"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="search" />
                                </template>
                            </q-input>
                        </template>
                        <template v-slot:body="props">
                            <q-tr :props="props">
                                <q-td key="id" :props="props">{{ props.row.id }}</q-td>
                                <q-td key="business" :props="props">
                                    <span class="text-weight-medium">{{ props.row.business }}</span>
                                </q-td>
                                <q-td key="nit" :props="props">{{ props.row.nit }}</q-td>
                                <q-td key="created_at" :props="props">{{ props.row.created_at }}</q-td>
                                <q-td key="actions" :props="props">
                                    <q-btn data-tour="edit-supplier"
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        color="primary"
                                        icon="eva-edit-2-outline"
                                        aria-label="Editar proveedor"
                                        @click="HandleEditSupplier(props.row.id)"
                                    >
                                        <q-tooltip>Editar</q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        color="negative"
                                        icon="eva-trash-2-outline"
                                        aria-label="Eliminar proveedor"
                                        @click="HandleDeleteSupplier(props.row)"
                                    >
                                        <q-tooltip>Eliminar</q-tooltip>
                                    </q-btn>
                                </q-td>
                            </q-tr>
                        </template>
                        <template v-slot:item="props">
                            <div class="col-12 q-pa-xs">
                                <q-card flat bordered class="card-grid">
                                    <q-card-section class="q-pb-xs">
                                        <div class="text-weight-medium">{{ props.row.business }}</div>
                                        <div class="text-caption text-grey-7">NIT: {{ props.row.nit }}</div>
                                        <div class="text-caption text-grey">Creado: {{ props.row.created_at }}</div>
                                    </q-card-section>
                                    <q-card-actions align="right" class="q-pt-none">
                                        <q-btn flat dense no-caps size="sm" color="primary" icon="eva-edit-2-outline"
                                            label="Editar" @click="HandleEditSupplier(props.row.id)" />
                                        <q-btn flat dense no-caps size="sm" color="negative" icon="eva-trash-2-outline"
                                            label="Eliminar" @click="HandleDeleteSupplier(props.row)" />
                                    </q-card-actions>
                                </q-card>
                            </div>
                        </template>
                        <template v-slot:no-data>
                            <div class="full-width text-center q-pa-xl text-grey">
                                <q-icon name="eva-car-outline" size="56px" color="grey-4" />
                                <div class="q-mt-sm">
                                    {{ table.filter ? 'Sin resultados para la búsqueda' : 'No hay proveedores registrados' }}
                                </div>
                            </div>
                        </template>
                    </q-table>
                </q-card>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

defineProps({
    title: String,
    data: Object,
});
const $q = useQuasar();
const page = usePage();
const data = ref(page.props.data);

const table = ref({
    filter: "",
    columns: [
        { name: "id", align: "center", label: "ID", field: "id", sortable: true, style: "width: 60px" },
        { name: "business", align: "left", label: "Razón Social", field: "business", sortable: true },
        { name: "nit", align: "left", label: "NIT", field: "nit", sortable: true },
        { name: "created_at", align: "center", label: "Fecha de Creación", field: "created_at", sortable: true },
        { name: "actions", align: "center", label: "Acciones", field: "actions", style: "width: 100px" },
    ],
});

function HandleCreateSupplier() {
    router.visit(route("panel.supplier.create"));
}
function HandleEditSupplier(id) {
    router.visit(route("panel.supplier.edit", id));
}
function HandleDeleteSupplier(row) {
    $q.dialog({
        title: "Eliminar proveedor",
        message: `¿Está seguro de eliminar el proveedor "${row.business}" (NIT ${row.nit})? Esta acción no se puede deshacer.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.supplier.delete", row.id), {
            onSuccess: () => {
                data.value = page.props.data;
            },
        });
    });
}
</script>
