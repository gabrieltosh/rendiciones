<template>

    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">
            <q-card class="card-form q-mt-sm">
                <q-table :loading="table.loading" :rows="data" :columns="table.columns" row-key="id" class="table-theme"
                    :filter="table.filter" separator="horizontal" :grid="grid" flat bordered
                    :table-header-class="{ 'table-header-theme': true }">
                    <template v-slot:top>
                        <h5 class="title-form">Lista de Rendiciones a Autorizar</h5>
                        <q-space />
                        <q-input outlined dense color="primary" placeholder="Buscar..." v-model="table.filter"
                            class="input-theme">
                            <template v-slot:append>
                                <q-icon name="search" />
                            </template>
                        </q-input>
                        <q-toggle v-model="grid" checked-icon="eva-grid-outline" color="green"
                            unchecked-icon="eva-menu-outline" keep-color size="lg" />
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
                            <q-td style="width: 110px !important">
                                <div class="text-center q-gutter-xs">
                                    <q-btn size="sm" color="primary" dense @click="
                                        HandleDetailAccountability(
                                            props.row.id
                                        )
                                        " icon="eva-plus">
                                        <q-tooltip class="bg-secondary" :offset="[10, 10]">
                                            Detalle de Rendición
                                        </q-tooltip>
                                    </q-btn>
                                    <q-btn size="sm" color="secondary" dense @click="
                                        HandleEditAccountability(
                                            props.row.id
                                        )
                                        " icon="eva-edit-2-outline">
                                        <q-tooltip class="bg-secondary" :offset="[10, 10]">
                                            Editar de Rendición
                                        </q-tooltip>
                                    </q-btn>
                                </div>
                            </q-td>
                            <q-td key="id" :props="props">
                                {{ props.row.id }}
                            </q-td>
                            <q-td key="employee_name" :props="props">
                                {{ props.row.employee_name }}
                            </q-td>
                            <q-td key="account_name" :props="props">
                                {{ props.row.account_name }}
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
                    <template v-slot:item="props">
                        <div class="q-pa-xs col-xs-12 col-sm-6 col-md-3">
                            <q-card class="card-grid q-mt-md shadow-0" bordered>
                                <q-avatar square size="50px" class="absolute" style="
                                        top: 12px;
                                        left: 12px;
                                        transform: translateY(-50%);
                                        border-radius: 10px;
                                        background-color: #1b2033;
                                        box-shadow: 0rem 0.25rem 1.25rem 0rem
                                                rgba(0, 0, 0, 0.14),
                                            0rem 0.4375rem 0.625rem -0.3125rem rgba(64, 64, 64, 0.4);
                                    ">
                                    <lord-icon target=".q-card" src="https://cdn.lordicon.com/dnbjoceq.json"
                                        trigger="hover" colors="primary:white,secondary:#08a88a"
                                        style="width: 50px; height: 50px" ref="animation">
                                    </lord-icon>
                                </q-avatar>
                                <div class="row q-pa-sm">
                                    <div class="col-xs-3 col-sm-3 col-md-2"></div>
                                    <div class="col-xs-9 col-sm-9 col-md-10 text-right">
                                        <div class="form-label">
                                            {{ props.row.description }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 q-mt-sm">
                                    <q-separator spaced />
                                    <q-list dense>
                                        <q-item class="text-right">
                                            <q-item-section>
                                                <q-item-label class="title-grid">Realizado por</q-item-label>
                                                <q-item-label class="text-grid">{{
                                                    props.row.user.name
                                                    }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-right">
                                            <q-item-section>
                                                <q-item-label class="title-grid">Empleado</q-item-label>
                                                <q-item-label class="text-grid">{{
                                                    props.row.employee_name
                                                    }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-right">
                                            <q-item-section>
                                                <q-item-label class="title-grid">Cuenta</q-item-label>
                                                <q-item-label class="text-grid">{{
                                                    props.row.account_name
                                                    }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-right">
                                            <q-item-section>
                                                <q-item-label class="title-grid">Monto</q-item-label>
                                                <q-item-label class="text-grid">{{
                                                    props.row.total
                                                    }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-right">
                                            <q-item-section>
                                                <q-item-label class="title-grid">Fecha Inicio</q-item-label>
                                                <q-item-label class="text-grid">{{
                                                    props.row.start_date
                                                    }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-right">
                                            <q-item-section>
                                                <q-item-label class="title-grid">Fecha Final</q-item-label>
                                                <q-item-label class="text-grid">{{
                                                    props.row.end_date
                                                    }}</q-item-label>
                                            </q-item-section>
                                        </q-item>
                                        <q-item class="text-right" v-if="props.row.status">
                                            <q-item-section>
                                                <q-item-label class="title-grid">Estado</q-item-label>
                                                <q-item-label class="text-grid"><q-badge
                                                        :color="props.row.status == 'Pendiente' ? 'orange' : props.row.status == 'Rechazado' ? 'red' : 'green'"
                                                        :label="props.row.status" /></q-item-label>
                                            </q-item-section>
                                        </q-item>
                                    </q-list>
                                    <q-separator spaced />
                                </div>
                                <q-card-actions align="center" class="q-pt-none">
                                    <q-btn size="sm" color="primary" @click="
                                        HandleDetailAccountability(
                                            props.row.id
                                        )
                                        " icon="eva-plus">
                                        <q-tooltip class="bg-secondary" :offset="[10, 10]">
                                            Detalle de Rendición
                                        </q-tooltip>
                                    </q-btn>
                                    <q-btn size="sm" color="secondary" @click="
                                        HandleEditAccountability(
                                            props.row.id
                                        )
                                        " icon="eva-edit-2-outline">
                                        <q-tooltip class="bg-secondary" :offset="[10, 10]">
                                            Editar de Rendición
                                        </q-tooltip>
                                    </q-btn>
                                </q-card-actions>
                            </q-card>
                        </div>
                    </template>
                </q-table>
            </q-card>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, watch } from "vue";
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
let message = ref(page.props.flash.message);
let type = ref(page.props.flash.type);
let grid = ref(true);
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
            name: "employee_name",
            align: "center",
            label: "Empleado",
            field: "employee_name",
            sortable: true,
        },
        {
            name: "account_name",
            align: "center",
            label: "Cuenta",
            field: "account_name",
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

function HandleEditAccountability(id) {
    router.visit(
        route("panel.accountability.authorization.edit", id)
    );
}
function HandleDetailAccountability(id) {
    router.visit(
        route("panel.accountability.authorization.detail.index",id)
    );
}
</script>
<style lang="scss"></style>
