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
                        <q-btn data-tour="create-user"
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
                    <q-table data-tour="users-table"
                        :rows="filteredRows"
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
                            <div class="column">
                                <div class="text-subtitle1 text-weight-medium">Lista de Usuarios</div>
                                <div data-tour="role-filters" class="row q-gutter-xs q-mt-xs" role="group" aria-label="Filtrar por rol">
                                    <q-chip
                                        v-for="option in roleOptions"
                                        :key="option.value"
                                        clickable
                                        dense
                                        :outline="roleFilter !== option.value"
                                        :color="roleFilter === option.value ? 'primary' : 'blue-grey'"
                                        :text-color="roleFilter === option.value ? 'white' : undefined"
                                        :aria-pressed="roleFilter === option.value"
                                        @click="roleFilter = option.value"
                                    >
                                        {{ option.label }}
                                        <q-badge
                                            rounded
                                            class="q-ml-xs"
                                            :color="roleFilter === option.value ? 'white' : 'blue-grey'"
                                            :text-color="roleFilter === option.value ? 'primary' : 'white'"
                                            :label="roleCount(option.value)"
                                        />
                                    </q-chip>
                                </div>
                            </div>
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
                                    {{ formatDate(props.row.created_at) }}
                                    <q-tooltip>{{ props.row.created_at }}</q-tooltip>
                                </q-td>
                                <q-td key="actions" :props="props">
                                    <q-btn data-tour="edit-user"
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
                                    <q-btn data-tour="delete-user"
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
                        <template v-slot:item="props">
                            <div class="col-12 q-pa-xs">
                                <q-card flat bordered class="card-grid">
                                    <q-card-section class="row items-center no-wrap q-pb-xs">
                                        <q-avatar size="36px" color="blue-grey-1" text-color="blue-grey-9" class="text-caption text-weight-medium">
                                            {{ initials(props.row.name) }}
                                        </q-avatar>
                                        <div class="q-ml-sm col" style="min-width: 0;">
                                            <div class="text-weight-medium ellipsis">{{ props.row.name }}</div>
                                            <div class="text-caption text-grey ellipsis">{{ props.row.username }} · {{ props.row.email }}</div>
                                        </div>
                                    </q-card-section>
                                    <q-card-section class="q-py-xs row items-center q-gutter-xs">
                                        <q-badge outline color="blue-grey" :label="props.row.type || '—'" />
                                        <q-badge :color="statusColor(props.row.status)" :label="props.row.status" />
                                        <q-space />
                                        <span class="text-caption text-grey">{{ formatDate(props.row.created_at) }}</span>
                                    </q-card-section>
                                    <q-card-actions align="right" class="q-pt-none">
                                        <q-btn flat dense no-caps size="sm" color="primary" icon="eva-edit-2-outline"
                                            label="Editar" @click="HandleEditUser(props.row.id)" />
                                        <q-btn flat dense no-caps size="sm" color="negative" icon="eva-trash-2-outline"
                                            label="Eliminar" @click="HandleDeleteUser(props.row)" />
                                    </q-card-actions>
                                </q-card>
                            </div>
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
import { ref, computed } from "vue";
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

// Filtro por rol (chips)
const roleFilter = ref("all");
const roleOptions = [
    { value: "all", label: "Todos" },
    { value: "Administrador", label: "Administradores" },
    { value: "Autorizador", label: "Autorizadores" },
    { value: "Usuario", label: "Usuarios" },
];
function roleCount(role) {
    return role === "all" ? data.value.length : data.value.filter((user) => user.type === role).length;
}
const filteredRows = computed(() =>
    roleFilter.value === "all" ? data.value : data.value.filter((user) => user.type === roleFilter.value)
);

// created_at llega como "YYYY-MM-DD h:mm AM" (hora de La Paz)
function formatDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value || "");
    if (!match) return value || "—";
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    return date.toLocaleDateString("es-BO", { day: "2-digit", month: "short", year: "numeric" });
}
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
        message: `¿Está seguro de eliminar al usuario "${row.name}" (${row.username})? Esta acción no se puede deshacer.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.user.delete", row.id), {
            onSuccess: () => {
                data.value = page.props.data;
            },
        });
    });
}
</script>
