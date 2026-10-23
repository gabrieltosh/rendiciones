<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">
                <PageHeader
                    title="Perfiles"
                    subtitle="Tipos de rendición con sus documentos, cuentas y reglas de cálculo"
                    icon="eva-layers-outline"
                >
                    <template #actions>
                        <q-btn
                            color="primary"
                            icon="add"
                            label="Crear Perfil"
                            no-caps
                            unelevated
                            @click="HandleCreateProfile()"
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
                            <div class="text-subtitle1 text-weight-medium">Lista de Perfiles</div>
                            <q-space />
                            <q-input
                                outlined
                                dense
                                clearable
                                placeholder="Buscar…"
                                v-model="table.filter"
                                class="input-theme search-input"
                                aria-label="Buscar perfiles"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="search" />
                                </template>
                            </q-input>
                        </template>
                        <template v-slot:body="props">
                            <q-tr :props="props">
                                <q-td key="id" :props="props">
                                    {{ props.row.id }}
                                </q-td>
                                <q-td key="name" :props="props">
                                    <span class="text-weight-medium">{{ props.row.name }}</span>
                                </q-td>
                                <q-td key="type_currency" :props="props">
                                    <q-badge outline color="blue-grey" :label="props.row.type_currency" />
                                </q-td>
                                <q-td key="created_at" :props="props">
                                    {{ props.row.created_at }}
                                </q-td>
                                <q-td key="actions" :props="props">
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        color="primary"
                                        icon="eva-edit-2-outline"
                                        aria-label="Editar perfil"
                                        @click="HandleEditProfile(props.row.id)"
                                    >
                                        <q-tooltip>Editar</q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        color="teal"
                                        icon="content_copy"
                                        aria-label="Copiar perfil"
                                        @click="HandleCopyProfile(props.row)"
                                    >
                                        <q-tooltip>Duplicar</q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        color="negative"
                                        icon="eva-trash-2-outline"
                                        aria-label="Eliminar perfil"
                                        @click="HandleDeleteProfile(props.row)"
                                    >
                                        <q-tooltip>Eliminar</q-tooltip>
                                    </q-btn>
                                </q-td>
                            </q-tr>
                        </template>
                        <template v-slot:no-data>
                            <div class="full-width text-center q-pa-xl text-grey">
                                <q-icon name="eva-layers-outline" size="56px" color="grey-4" />
                                <div class="q-mt-sm">
                                    {{ table.filter ? 'Sin resultados para la búsqueda' : 'No hay perfiles registrados' }}
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
        { name: "type_currency", align: "center", label: "Moneda", field: "type_currency", sortable: true },
        { name: "created_at", align: "center", label: "Fecha de Creación", field: "created_at", sortable: true },
        { name: "actions", align: "center", label: "Acciones", field: "actions", style: "width: 130px" },
    ],
});

function HandleCreateProfile() {
    router.visit(route("panel.profile.create"));
}
function HandleEditProfile($id) {
    router.visit(route("panel.profile.edit", $id));
}
function HandleCopyProfile(row) {
    $q.dialog({
        title: "Duplicar perfil",
        message: `Se creará una copia de "${row.name}" con todos sus documentos y configuración.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Duplicar", color: "primary", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.post(route("panel.profile.copy", row.id), {}, {
            onSuccess: () => {
                data.value = page.props.data;
                $q.notify({
                    type: page.props.flash.type,
                    message: page.props.flash.message,
                });
            },
        });
    });
}
function HandleDeleteProfile(row) {
    $q.dialog({
        title: "Eliminar perfil",
        message: `¿Está seguro de eliminar el perfil "${row.name}"? Esta acción no se puede deshacer.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.profile.delete", row.id), {
            onSuccess: () => {
                data.value = page.props.data;
                $q.notify({
                    type: page.props.flash.type,
                    message: page.props.flash.message,
                });
            },
        });
    });
}
</script>
