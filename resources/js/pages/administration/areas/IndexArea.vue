<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">
                <PageHeader
                    title="Áreas"
                    subtitle="Sucursales o áreas a las que pertenecen los usuarios"
                    icon="eva-pie-chart-outline"
                >
                    <template #actions>
                        <q-btn
                            color="primary"
                            icon="add"
                            label="Crear Área"
                            no-caps
                            unelevated
                            @click="HandleCreateArea()"
                        />
                    </template>
                </PageHeader>

                <q-card class="card-form">
                    <q-table
                        :rows="data"
                        :columns="table.columns"
                        row-key="id"
                        class="table-theme"
                        :filter="table.filter"
                        flat
                        separator="horizontal"
                        :table-header-class="{ 'table-header-theme': true }"
                        :pagination="{ rowsPerPage: 15 }"
                    >
                        <template v-slot:top>
                            <div class="text-subtitle1 text-weight-medium">Lista de Áreas</div>
                            <q-space />
                            <q-input
                                outlined
                                dense
                                clearable
                                placeholder="Buscar…"
                                v-model="table.filter"
                                class="input-theme search-input"
                                aria-label="Buscar áreas"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="search" />
                                </template>
                            </q-input>
                        </template>
                        <template v-slot:body="props">
                            <q-tr :props="props">
                                <q-td key="id" :props="props">{{ props.row.id }}</q-td>
                                <q-td key="name" :props="props">
                                    <span class="text-weight-medium">{{ props.row.name }}</span>
                                </q-td>
                                <q-td key="description" :props="props">
                                    <span class="text-grey-7">{{ props.row.description || '—' }}</span>
                                </q-td>
                                <q-td key="created_at" :props="props">{{ props.row.created_at }}</q-td>
                                <q-td key="actions" :props="props">
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        color="primary"
                                        icon="eva-edit-2-outline"
                                        aria-label="Editar área"
                                        @click="HandleEditArea(props.row.id)"
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
                                        aria-label="Eliminar área"
                                        @click="HandleDeleteArea(props.row)"
                                    >
                                        <q-tooltip>Eliminar</q-tooltip>
                                    </q-btn>
                                </q-td>
                            </q-tr>
                        </template>
                        <template v-slot:no-data>
                            <div class="full-width text-center q-pa-xl text-grey">
                                <q-icon name="eva-pie-chart-outline" size="56px" color="grey-4" />
                                <div class="q-mt-sm">
                                    {{ table.filter ? 'Sin resultados para la búsqueda' : 'No hay áreas registradas' }}
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
let data = ref(page.props.data);

const table = ref({
    filter: "",
    columns: [
        { name: "id", align: "center", label: "ID", field: "id", sortable: true, style: "width: 60px" },
        { name: "name", align: "left", label: "Nombre", field: "name", sortable: true },
        { name: "description", align: "left", label: "Descripción", field: "description", sortable: true },
        { name: "created_at", align: "center", label: "Fecha de Creación", field: "created_at", sortable: true },
        { name: "actions", align: "center", label: "Acciones", field: "actions", style: "width: 100px" },
    ],
});

function HandleCreateArea() {
    router.visit(route("panel.area.create"));
}
function HandleEditArea(id) {
    router.visit(route("panel.area.edit", id));
}
function HandleDeleteArea(row) {
    $q.dialog({
        title: "Eliminar área",
        message: `¿Está seguro de eliminar el área "${row.name}"? Esta acción no se puede deshacer.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.area.delete", row.id), {
            onSuccess: () => {
                data.value = page.props.data;
                $q.notify({ type: page.props.flash.type, message: page.props.flash.message });
            },
        });
    });
}
</script>
