<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">
                <PageHeader
                    title="Ciclos de Autorización"
                    subtitle="Niveles y autorizadores por los que pasa cada rendición antes de exportarse"
                    icon="eva-shuffle-2-outline"
                >
                    <template #actions>
                        <q-btn
                            color="primary"
                            icon="add"
                            label="Nuevo Ciclo"
                            no-caps
                            unelevated
                            @click="router.visit(route('panel.authorization-cycle.create'))"
                        />
                    </template>
                </PageHeader>

            <q-card class="card-form">
                <q-table
                    :rows="data"
                    :columns="columns"
                    row-key="id"
                    class="table-theme"
                    :filter="filter"
                    flat
                    separator="horizontal"
                    :table-header-class="{ 'table-header-theme': true }"
                >
                    <template v-slot:top>
                        <div class="text-subtitle1 text-weight-medium">Lista de Ciclos</div>
                        <q-space />
                        <q-input
                            outlined
                            dense
                            clearable
                            placeholder="Buscar…"
                            v-model="filter"
                            class="input-theme search-input"
                            aria-label="Buscar ciclos"
                        >
                            <template v-slot:prepend>
                                <q-icon name="search" />
                            </template>
                        </q-input>
                    </template>

                    <template v-slot:header="props">
                        <q-tr :props="props">
                            <q-th auto-width />
                            <q-th v-for="col in props.cols" :key="col.name" :props="props">
                                {{ col.label }}
                            </q-th>
                        </q-tr>
                    </template>

                    <template v-slot:body="props">
                        <q-tr :props="props">
                            <q-td auto-width>
                                <q-btn
                                    flat round dense size="sm"
                                    :icon="props.expand ? 'expand_less' : 'expand_more'"
                                    color="grey"
                                    :aria-label="props.expand ? 'Ocultar niveles' : 'Ver niveles'"
                                    @click="props.expand = !props.expand"
                                >
                                    <q-tooltip>{{ props.expand ? 'Ocultar niveles' : 'Ver niveles' }}</q-tooltip>
                                </q-btn>
                            </q-td>
                            <q-td key="name" :props="props">
                                <span class="text-weight-medium">{{ props.row.name }}</span>
                            </q-td>
                            <q-td key="description" :props="props">
                                <span class="text-grey-7">{{ props.row.description || '—' }}</span>
                            </q-td>
                            <q-td key="levels" :props="props" class="text-center">
                                <q-badge color="blue-grey" :label="props.row.levels.length" />
                            </q-td>
                            <q-td key="is_active" :props="props" class="text-center">
                                <q-badge
                                    :color="props.row.is_active ? 'positive' : 'grey-5'"
                                    :label="props.row.is_active ? 'Activo' : 'Inactivo'"
                                />
                            </q-td>
                            <q-td key="actions" :props="props">
                                <q-btn
                                    flat round dense size="sm"
                                    color="primary"
                                    icon="eva-edit-2-outline"
                                    aria-label="Editar ciclo"
                                    @click="router.visit(route('panel.authorization-cycle.edit', props.row.id))"
                                >
                                    <q-tooltip>Editar</q-tooltip>
                                </q-btn>
                                <q-btn
                                    flat round dense size="sm"
                                    color="negative"
                                    icon="eva-trash-2-outline"
                                    aria-label="Eliminar ciclo"
                                    @click="HandleDelete(props.row.id)"
                                >
                                    <q-tooltip>Eliminar</q-tooltip>
                                </q-btn>
                            </q-td>
                        </q-tr>

                        <!-- Fila expandible: niveles del ciclo -->
                        <q-tr v-show="props.expand" :props="props">
                            <q-td colspan="100%" class="q-pa-md level-panel">
                                <div v-if="props.row.levels.length === 0" class="text-caption text-grey">
                                    Este ciclo no tiene niveles configurados.
                                </div>
                                <div v-else>
                                    <div class="text-caption text-grey q-mb-sm">Niveles de autorización</div>
                                    <div
                                        v-for="(level, idx) in props.row.levels"
                                        :key="level.id"
                                        class="level-row row items-start no-wrap"
                                    >
                                        <div class="level-spine col-auto column items-center q-mr-md">
                                            <q-avatar size="26px" color="primary" text-color="white" class="text-caption text-weight-bold">
                                                {{ level.order }}
                                            </q-avatar>
                                            <div v-if="idx < props.row.levels.length - 1" class="level-connector" />
                                        </div>
                                        <div class="col q-pb-sm">
                                            <div class="text-body2 text-weight-medium q-mb-xs">{{ level.name }}</div>
                                            <div class="row q-gutter-xs">
                                                <q-chip
                                                    v-for="u in level.users"
                                                    :key="u.id"
                                                    dense size="sm"
                                                    color="blue-grey-1"
                                                    text-color="blue-grey-9"
                                                    icon="person"
                                                >
                                                    {{ u.name }}
                                                </q-chip>
                                                <span v-if="level.users.length === 0" class="text-caption text-grey-5">
                                                    Sin autorizadores
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </q-td>
                        </q-tr>
                    </template>

                    <template v-slot:no-data>
                        <div class="full-width text-center q-pa-xl text-grey">
                            <q-icon name="eva-shuffle-2-outline" size="56px" color="grey-4" />
                            <div class="q-mt-sm">
                                {{ filter ? 'Sin resultados para la búsqueda' : 'No hay ciclos de autorización configurados' }}
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

defineProps({ title: String, data: Array });

const $q = useQuasar();
const filter = ref("");

const columns = [
    { name: "name",        label: "Nombre",      field: "name",        align: "left",   sortable: true },
    { name: "description", label: "Descripción", field: "description", align: "left"                  },
    { name: "levels",      label: "Niveles",     field: r => r.levels.length, align: "center", sortable: true },
    { name: "is_active",   label: "Estado",      field: "is_active",   align: "center", sortable: true },
    { name: "actions",     label: "Acciones",    field: "actions",     align: "center", style: "width: 100px" },
];

function HandleDelete(id) {
    $q.dialog({
        title: "Eliminar Ciclo",
        message: "¿Está seguro de eliminar este ciclo? Esta acción no se puede deshacer.",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.authorization-cycle.delete", id), {
            onSuccess: () => {
                const flash = usePage().props.flash;
                $q.notify({ type: flash.type, message: flash.message });
            },
        });
    });
}
</script>

<style scoped>
.level-panel {
    background-color: rgba(52, 71, 103, 0.04);
}
.level-spine {
    min-width: 26px;
    padding-top: 2px;
}
.level-connector {
    width: 2px;
    flex: 1;
    min-height: 18px;
    background: currentColor;
    opacity: 0.15;
    margin-top: 3px;
}
</style>
