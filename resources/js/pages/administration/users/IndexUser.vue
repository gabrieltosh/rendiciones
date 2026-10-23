<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">
                <PageHeader
                    title="Usuarios"
                    :subtitle="`${data.length} usuario${data.length !== 1 ? 's' : ''} registrados`"
                    icon="eva-people-outline"
                >
                    <template #actions>
                        <q-btn
                            color="primary"
                            icon="add"
                            label="Crear Usuario"
                            no-caps
                            unelevated
                            @click="HandleCreateUser()"
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
                            <div class="text-subtitle1 text-weight-medium">Lista de Usuarios</div>
                            <q-space />
                            <q-input
                                outlined
                                dense
                                clearable
                                placeholder="Buscar usuario…"
                                v-model="table.filter"
                                class="input-theme search-input"
                                aria-label="Buscar usuarios"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="search" />
                                </template>
                            </q-input>
                        </template>
                        <template v-slot:body="props">
                            <q-tr :props="props">
                                <q-td key="name" :props="props">
                                    <div class="row items-center no-wrap">
                                        <q-avatar size="30px" color="blue-grey-1" text-color="blue-grey-9" class="text-caption text-weight-medium">
                                            {{ initials(props.row.name) }}
                                        </q-avatar>
                                        <div class="q-ml-sm">
                                            <div class="text-weight-medium">{{ props.row.name }}</div>
                                            <div class="text-caption text-grey">{{ props.row.email }}</div>
                                        </div>
                                    </div>
                                </q-td>
                                <q-td key="username" :props="props">
                                    {{ props.row.username }}
                                </q-td>
                                <q-td key="type" :props="props">
                                    <q-badge outline color="blue-grey" :label="props.row.type || '—'" />
                                </q-td>
                                <q-td key="status" :props="props">
                                    <q-badge :color="statusColor(props.row.status)" :label="props.row.status" />
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
                                        aria-label="Editar usuario"
                                        @click="HandleEditUser(props.row.id)"
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
                                        aria-label="Eliminar usuario"
                                        @click="HandleDeleteUser(props.row)"
                                    >
                                        <q-tooltip>Eliminar</q-tooltip>
                                    </q-btn>
                                </q-td>
                            </q-tr>
                        </template>
                        <template v-slot:no-data>
                            <div class="full-width text-center q-pa-xl text-grey">
                                <q-icon name="eva-people-outline" size="56px" color="grey-4" />
                                <div class="q-mt-sm">
                                    {{ table.filter ? 'Sin resultados para la búsqueda' : 'No hay usuarios registrados' }}
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
        { name: "name", align: "left", label: "Nombre", field: "name", sortable: true },
        { name: "username", align: "left", label: "Usuario", field: "username", sortable: true },
        { name: "type", align: "center", label: "Rol", field: "type", sortable: true },
        { name: "status", align: "center", label: "Estado", field: "status", sortable: true },
        { name: "created_at", align: "center", label: "Fecha de Creación", field: "created_at", sortable: true },
        // Columna oculta solo para que el buscador también encuentre por correo
        { name: "email", label: "Correo", field: "email", classes: "hidden", headerClasses: "hidden" },
        { name: "actions", align: "center", label: "Acciones", field: "actions", style: "width: 100px" },
    ],
});

function initials(name) {
    return (name || "")
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join("");
}

function statusColor(status) {
    if (status === "Activo") return "positive";
    if (status === "PreActivo") return "warning";
    return "negative";
}

function HandleCreateUser() {
    router.visit(route("panel.user.create"));
}
function HandleEditUser($id) {
    router.visit(route("panel.user.edit", $id));
}
function HandleDeleteUser(row) {
    $q.dialog({
        title: "Eliminar usuario",
        message: `¿Está seguro de eliminar a ${row.name}? Esta acción no se puede deshacer.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.user.delete", row.id), {
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
