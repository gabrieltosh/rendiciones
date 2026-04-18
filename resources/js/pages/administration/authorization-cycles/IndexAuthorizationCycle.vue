<template>
    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">
            <q-card class="card-form q-mt-sm">
                <q-table
                    :rows="data"
                    :columns="columns"
                    row-key="id"
                    class="table-theme"
                    :filter="filter"
                    separator="horizontal"
                    :table-header-class="{ 'table-header-theme': true }"
                >
                    <template v-slot:top>
                        <h5 class="title-form">Ciclos de Autorización</h5>
                        <q-space />
                        <q-btn
                            color="primary"
                            icon="add"
                            label="Nuevo Ciclo"
                            size="11px"
                            no-caps
                            unelevated
                            @click="router.visit(route('panel.authorization-cycle.create'))"
                        />
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
                            <q-th auto-width />
                            <q-th v-for="col in props.cols" :key="col.name" :props="props">
                                {{ col.label }}
                            </q-th>
                        </q-tr>
                    </template>

                    <template v-slot:body="props">
                        <q-tr :props="props">
                            <q-td style="width: 120px !important">
                                <div class="text-center q-gutter-xs">
                                    <q-btn
                                        flat round dense size="sm"
                                        :icon="props.expand ? 'expand_less' : 'expand_more'"
                                        color="grey"
                                        @click="props.expand = !props.expand"
                                    >
                                        <q-tooltip>{{ props.expand ? 'Ocultar niveles' : 'Ver niveles' }}</q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        size="sm"
                                        color="primary"
                                        dense
                                        icon="eva-edit-2-outline"
                                        @click="router.visit(route('panel.authorization-cycle.edit', props.row.id))"
                                    >
                                        <q-tooltip class="bg-secondary">Editar ciclo</q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        size="sm"
                                        color="red"
                                        dense
                                        icon="eva-trash-2-outline"
                                        @click="HandleDelete(props.row.id)"
                                    >
                                        <q-tooltip class="bg-red">Eliminar ciclo</q-tooltip>
                                    </q-btn>
                                </div>
                            </q-td>
                            <q-td key="name" :props="props">{{ props.row.name }}</q-td>
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
                        </q-tr>

                        <!-- Fila expandible: niveles del ciclo -->
                        <q-tr v-show="props.expand" :props="props">
                            <q-td colspan="100%" class="q-pa-md bg-grey-1">
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

defineProps({ title: String, data: Array });

const $q = useQuasar();
const filter = ref("");

const columns = [
    { name: "name",        label: "Nombre",      field: "name",        align: "left",   sortable: true },
    { name: "description", label: "Descripción", field: "description", align: "left"                  },
    { name: "levels",      label: "Niveles",     field: r => r.levels.length, align: "center", sortable: true },
    { name: "is_active",   label: "Estado",      field: "is_active",   align: "center", sortable: true },
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
