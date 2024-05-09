<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11">
                <q-stepper
                    v-model="step"
                    header-nav
                    ref="stepper"
                    color="primary"
                    animated
                    flat
                    alternative-labels
                    class="backgroup-theme"
                >
                    <q-step
                        class="backgroup-theme"
                        :name="1"
                        title="Formulario de Perfil"
                        icon="settings"
                        :done="step > 1"
                        :error="Object.keys(errors).length > 0 ? true : false"
                    >
                        <q-card class="q-pa-lg q-ma-sm card-form">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Editar Perfil</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn
                                        color="secondary"
                                        label="Cancelar"
                                        size="12px"
                                        no-caps
                                        @click="
                                            router.visit(
                                                route('panel.profile.index')
                                            )
                                        "
                                        flat
                                    />
                                    <q-btn
                                        @click="$refs.stepper.next()"
                                        color="primary"
                                        no-caps
                                        size="12px"
                                        label="Continuar"
                                    />
                                </div>
                            </div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div
                                    class="col-xs-12 col-sm-12 col-md-6 col-lg-6"
                                >
                                    <div class="form-label" for="device_name">
                                        Nombre Perfil
                                        <span class="text-red">*</span>
                                    </div>
                                    <q-input
                                        v-model="form.name"
                                        dense
                                        outlined
                                        class="input-theme"
                                    />
                                    <div
                                        v-if="errors.name"
                                        class="container-error"
                                    >
                                        <ul
                                            v-for="(
                                                error, index
                                            ) in errors.name"
                                            :key="index"
                                            class="message-error"
                                        >
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div
                                    class="col-xs-12 col-sm-12 col-md-6 col-lg-6"
                                >
                                    <div class="form-label" for="device_name">
                                        Moneda <span class="text-red">*</span>
                                    </div>
                                    <q-select
                                        class="input-theme"
                                        v-model="form.type_currency"
                                        :options="currencies"
                                        dense
                                        outlined
                                        option-value="CurrCode"
                                        option-label="CurrName"
                                        emit-value
                                        map-options
                                    />
                                    <div
                                        v-if="errors.type_currency"
                                        class="container-error"
                                    >
                                        <ul
                                            v-for="(
                                                error, index
                                            ) in errors.type_currency"
                                            :key="index"
                                            class="message-error"
                                        >
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </q-card>
                    </q-step>

                    <q-step
                        :name="2"
                        title="Cuentas Cabecera"
                        icon="assignment"
                        :done="step > 2"
                    >
                        <q-card
                            class="q-px-lg q-py-sm q-ma-sm card-form q-mt-md"
                        >
                            <div class="row">
                                <div class="col-12">
                                    <div class="row q-py-sm">
                                        <div class="col-sm-6">
                                            <h5 class="title-form">
                                                Asignar Cuentas Detalle
                                            </h5>
                                        </div>
                                        <div
                                            class="col-sm-6 text-right q-gutter-sm"
                                        >
                                            <q-btn
                                                color="secondary"
                                                label="Cancelar"
                                                size="12px"
                                                no-caps
                                                @click="
                                                    router.visit(
                                                        route(
                                                            'panel.profile.index'
                                                        )
                                                    )
                                                "
                                                flat
                                            />
                                            <q-btn
                                                flat
                                                color="primary"
                                                @click="
                                                    $refs.stepper.previous()
                                                "
                                                label="Atras"
                                                no-caps
                                                size="12px"
                                            />
                                            <q-btn
                                                @click="$refs.stepper.next()"
                                                color="primary"
                                                no-caps
                                                size="12px"
                                                label="Continuar"
                                            />
                                        </div>
                                    </div>
                                    <q-separator />
                                </div>
                                <div class="col-sm-6">
                                    <h5 class="title-tree text-center">
                                        Lista de Cuentas Contables
                                    </h5>
                                    <q-input
                                        outlined
                                        dense
                                        color="primary"
                                        placeholder="Buscar..."
                                        v-model="filter"
                                        class="input-theme"
                                    ></q-input>
                                    <q-tree
                                        :nodes="accounts"
                                        node-key="label"
                                        tick-strategy="leaf"
                                        v-model:ticked="form.general"
                                        :filter="filter"
                                        :filter-method="HandleFilterAccounts"
                                    >
                                        <template v-slot:default-header="prop">
                                            <div class="tree-label">
                                                {{ prop.node.label }}
                                            </div>
                                        </template>
                                    </q-tree>
                                </div>
                                <q-separator spaced inset vertical />
                                <div class="col-sm-5">
                                    <h5 class="title-tree text-center">
                                        Cuentas Seleccionadas
                                    </h5>
                                    <ul class="q-ma-none">
                                        <div
                                            v-for="tick in form.general"
                                            :key="`ticked-${tick}`"
                                            class="tree-label"
                                        >
                                            <li>{{ tick }}</li>
                                        </div>
                                    </ul>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step
                        :name="3"
                        title="Cuentas Detalle"
                        icon="assignment"
                        :done="step > 3"
                    >
                        <q-card
                            class="q-px-lg q-py-sm q-ma-sm card-form q-mt-md"
                        >
                            <div class="row">
                                <div class="col-12">
                                    <div class="row q-py-sm">
                                        <div class="col-sm-6">
                                            <h5 class="title-form">
                                                Asignar Cuentas Cabecera
                                            </h5>
                                        </div>
                                        <div
                                            class="col-sm-6 text-right q-gutter-sm"
                                        >
                                            <q-btn
                                                color="secondary"
                                                label="Cancelar"
                                                size="12px"
                                                no-caps
                                                @click="
                                                    router.visit(
                                                        route(
                                                            'panel.profile.index'
                                                        )
                                                    )
                                                "
                                                flat
                                            />
                                            <q-btn
                                                flat
                                                color="primary"
                                                @click="
                                                    $refs.stepper.previous()
                                                "
                                                label="Atras"
                                                no-caps
                                                size="12px"
                                                class="q-ml-sm"
                                            />
                                            <q-btn
                                                @click="$refs.stepper.next()"
                                                color="primary"
                                                no-caps
                                                size="12px"
                                                label="Continuar"
                                            />
                                        </div>
                                    </div>
                                    <q-separator />
                                </div>
                                <div class="col-sm-6">
                                    <h5 class="title-tree text-center">
                                        Lista de Cuentas Contables
                                    </h5>
                                    <q-input
                                        outlined
                                        dense
                                        color="primary"
                                        placeholder="Buscar..."
                                        v-model="filter"
                                        class="input-theme"
                                    ></q-input>
                                    <q-tree
                                        :nodes="accounts"
                                        node-key="label"
                                        tick-strategy="leaf"
                                        v-model:ticked="form.detail"
                                        :filter="filter"
                                        :filter-method="HandleFilterAccounts"
                                    >
                                        <template v-slot:default-header="prop">
                                            <div class="tree-label">
                                                {{ prop.node.label }}
                                            </div>
                                        </template>
                                    </q-tree>
                                </div>
                                <q-separator spaced inset vertical />
                                <div class="col-sm-5">
                                    <h5 class="title-tree text-center">
                                        Cuentas Seleccionadas
                                    </h5>
                                    <ul class="q-ma-none">
                                        <div
                                            v-for="tick in form.detail"
                                            :key="`ticked-${tick}`"
                                            class="tree-label"
                                        >
                                            <li>{{ tick }}</li>
                                        </div>
                                    </ul>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step
                        :name="4"
                        title="Empleados"
                        icon="eva-people-outline"
                        :done="step > 4"
                    >
                        <q-card
                            class="q-px-lg q-py-sm q-ma-sm card-form q-mt-md"
                        >
                            <div class="row">
                                <div class="col-12">
                                    <div class="row q-py-sm">
                                        <div class="col-sm-6">
                                            <h5 class="title-form">
                                                Asignar Empleados
                                            </h5>
                                        </div>
                                        <div
                                            class="col-sm-6 text-right q-gutter-sm"
                                        >
                                            <q-btn
                                                color="secondary"
                                                label="Cancelar"
                                                size="12px"
                                                no-caps
                                                @click="
                                                    router.visit(
                                                        route(
                                                            'panel.profile.index'
                                                        )
                                                    )
                                                "
                                                flat
                                            />
                                            <q-btn
                                                flat
                                                color="primary"
                                                @click="
                                                    $refs.stepper.previous()
                                                "
                                                label="Atras"
                                                no-caps
                                                size="12px"
                                            />
                                            <q-btn
                                                @click="$refs.stepper.next()"
                                                color="primary"
                                                no-caps
                                                size="12px"
                                                label="Continuar"
                                            />
                                        </div>
                                    </div>
                                    <q-separator />
                                </div>
                                <div class="col-sm-6">
                                    <h5 class="title-tree text-center">
                                        Lista de Empleados
                                    </h5>
                                    <q-input
                                        outlined
                                        dense
                                        color="primary"
                                        placeholder="Buscar..."
                                        v-model="filter_emp"
                                        class="input-theme"
                                    ></q-input>
                                    <q-tree
                                        :nodes="employees"
                                        node-key="label"
                                        tick-strategy="leaf"
                                        v-model:ticked="form.employees"
                                        :filter="filter_emp"
                                        :filter-method="HandleFilterAccounts"
                                    >
                                        <template v-slot:default-header="prop">
                                            <div class="tree-label">
                                                {{ prop.node.label }}
                                            </div>
                                        </template>
                                    </q-tree>
                                </div>
                                <q-separator spaced inset vertical />
                                <div class="col-sm-5">
                                    <h5 class="title-tree text-center">
                                        Empleados Seleccionados
                                    </h5>
                                    <ul class="q-ma-none">
                                        <div
                                            v-for="tick in form.employees"
                                            :key="`ticked-${tick}`"
                                            class="tree-label"
                                        >
                                            <li>{{ tick }}</li>
                                        </div>
                                    </ul>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step
                        :name="5"
                        title="Documentos"
                        icon="eva-file-text-outline"
                    >
                        <q-card class="q-pa-lg q-ma-sm card-form">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Documentos</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn
                                        color="secondary"
                                        label="Cancelar"
                                        size="12px"
                                        no-caps
                                        @click="
                                            router.visit(
                                                route('panel.profile.index')
                                            )
                                        "
                                        flat
                                    />
                                    <q-btn
                                        flat
                                        color="primary"
                                        @click="$refs.stepper.previous()"
                                        label="Atras"
                                        no-caps
                                        size="12px"
                                    />
                                    <q-btn
                                        @click="HandleStoreForm()"
                                        color="primary"
                                        no-caps
                                        size="12px"
                                        label="Actualizar"
                                    />
                                </div>
                            </div>
                            <div class="row q-mt-xs q-pa-sm q-mt-sm">
                                <div
                                    class="col-12 q-mb-md"
                                    v-for="(item, index) in form.documents"
                                    :key="index"

                                >
                                    <q-markup-table
                                        separator="cell"
                                        flat
                                        bordered
                                        dense
                                        :class="{'delete':item.for_delete==1}"
                                        ><q-menu touch-position context-menu>
                                            <q-list
                                                dense
                                                style="min-width: 100px"
                                            >
                                                <q-item
                                                    clickable
                                                    v-close-popup
                                                    @click="
                                                        HanldeDeleteDocument(
                                                            index
                                                        )
                                                    "
                                                >
                                                    <q-item-section
                                                        >Eliminar
                                                        Documento</q-item-section
                                                    >
                                                </q-item>
                                            </q-list>
                                        </q-menu>
                                        <thead>
                                            <tr>
                                                <th colspan="2" align="left">
                                                    Nombre Documento
                                                    <span class="text-red"
                                                        >*</span
                                                    >
                                                    <q-input
                                                        v-model="item.name"
                                                        dense
                                                        class="input-theme"
                                                    />
                                                    <div
                                                        v-if="
                                                            errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.name'
                                                            ]
                                                        "
                                                        class="container-error"
                                                    >
                                                        <ul
                                                            v-for="(
                                                                error, index
                                                            ) in errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.name'
                                                            ]"
                                                            :key="index"
                                                            class="message-error"
                                                        >
                                                            <li>{{ error }}</li>
                                                        </ul>
                                                    </div>
                                                </th>
                                                <th colspan="1" align="left">
                                                    Tipo Documento
                                                    <span class="text-red"
                                                        >*</span
                                                    >
                                                    <q-input
                                                        v-model="
                                                            item.type_document_sap
                                                        "
                                                        dense
                                                        class="input-theme"
                                                    />
                                                    <div
                                                        v-if="
                                                            errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.type_document_sap'
                                                            ]
                                                        "
                                                        class="container-error"
                                                    >
                                                        <ul
                                                            v-for="(
                                                                error, index
                                                            ) in errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.type_document_sap'
                                                            ]"
                                                            :key="index"
                                                            class="message-error"
                                                        >
                                                            <li>{{ error }}</li>
                                                        </ul>
                                                    </div>
                                                </th>
                                                <th colspan="1" align="left">
                                                    Tipo Calculo
                                                    <span class="text-red"
                                                        >*</span
                                                    >
                                                    <q-select
                                                        v-model="
                                                            item.type_calculation
                                                        "
                                                        :options="
                                                            options.type_calculation
                                                        "
                                                        dense
                                                        class="input-theme"
                                                    />
                                                    <div
                                                        v-if="
                                                            errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.type_calculation'
                                                            ]
                                                        "
                                                        class="container-error"
                                                    >
                                                        <ul
                                                            v-for="(
                                                                error, index
                                                            ) in errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.type_calculation'
                                                            ]"
                                                            :key="index"
                                                            class="message-error"
                                                        >
                                                            <li>{{ error }}</li>
                                                        </ul>
                                                    </div>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th colspan="2" align="left">
                                                    Exento
                                                    <span class="text-red"
                                                        >*</span
                                                    >
                                                    <q-input
                                                        v-model="item.exento"
                                                        dense
                                                        class="input-theme"
                                                        type="number"
                                                    />
                                                </th>
                                                <th colspan="1" align="left">
                                                    Tasas
                                                    <span class="text-red"
                                                        >*</span
                                                    >
                                                    <q-input
                                                        v-model="
                                                            item.tasas
                                                        "
                                                        dense
                                                        class="input-theme"
                                                        type="number"
                                                    />
                                                </th>
                                                <th colspan="1" align="left">
                                                    ICE
                                                    <span class="text-red"
                                                        >*</span
                                                    >
                                                    <q-input
                                                        v-model="
                                                            item.ice
                                                        "
                                                        dense
                                                        class="input-theme"
                                                        type="number"
                                                    />
                                                </th>
                                            </tr>
                                            <tr
                                                style="
                                                    color: #344767;
                                                    background-color: #f0f2f5;
                                                "
                                            >
                                                <th
                                                    class="text-center"
                                                    style="width: 10%"
                                                >
                                                    <q-btn
                                                        color="primary"
                                                        icon="eva-plus"
                                                        @click="
                                                            HandleAddLineDetail(
                                                                index
                                                            )
                                                        "
                                                        dense
                                                        flat
                                                        size="10px"
                                                    />
                                                </th>
                                                <th
                                                    class="text-center"
                                                    style="width: 30%"
                                                >
                                                    Tipo
                                                </th>
                                                <th
                                                    class="text-center"
                                                    style="width: 30%"
                                                >
                                                    Cuenta
                                                </th>
                                                <th
                                                    class="text-center"
                                                    style="width: 30%"
                                                >
                                                    Porcentaje
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(
                                                    item2, index2
                                                ) in item.detail"
                                                :key="index2"
                                                :class="{'bg-negative':item2.for_delete==1}"
                                            >
                                                <td class="text-center">
                                                    <q-btn
                                                        flat
                                                        color="negative"
                                                        icon="eva-trash-2-outline"
                                                        @click="
                                                            HandleDeleteLineDetail(
                                                                index,
                                                                index2
                                                            )
                                                        "
                                                        dense
                                                        size="10px"
                                                    />
                                                </td>
                                                <td class="text-center">
                                                    <q-select
                                                        v-model="item2.type"
                                                        :options="options.type"
                                                        dense
                                                        class="input-theme"
                                                        @update:model-value="item2.type=='EXENTO'||item2.type=='TASA'||item2.type=='ICE'?item2.account='-':item2.account=null"
                                                    />
                                                    <div
                                                        v-if="
                                                            errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.detail.' +
                                                                    index2 +
                                                                    '.type'
                                                            ]
                                                        "
                                                        class="container-error"
                                                    >
                                                        <ul
                                                            v-for="(
                                                                error, index
                                                            ) in errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.detail.' +
                                                                    index2 +
                                                                    '.type'
                                                            ]"
                                                            :key="index"
                                                            class="message-error"
                                                        >
                                                            <li>{{ error }}</li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <q-select
                                                        class="input-theme"
                                                        dense
                                                        :options="
                                                            options.accounts_filter
                                                        "
                                                        v-model="item2.account"
                                                        option-value="AcctCode"
                                                        option-label="AcctName"
                                                        emit-value
                                                        map-options
                                                        use-input
                                                        @filter="
                                                            HandleFilterAccountsDocuments
                                                        "
                                                        clearable
                                                        :disable="item2.type=='EXENTO'||item2.type=='TASA'||item2.type=='ICE'?true:false"

                                                    />
                                                    <div
                                                        v-if="
                                                            errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.detail.' +
                                                                    index2 +
                                                                    '.account'
                                                            ]
                                                        "
                                                        class="container-error"
                                                    >
                                                        <ul
                                                            v-for="(
                                                                error, index
                                                            ) in errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.detail.' +
                                                                    index2 +
                                                                    '.account'
                                                            ]"
                                                            :key="index"
                                                            class="message-error"
                                                        >
                                                            <li>{{ error }}</li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <q-input
                                                        v-model="
                                                            item2.percentage
                                                        "
                                                        dense
                                                        class="input-theme"
                                                        type="number"
                                                    />
                                                    <div
                                                        v-if="
                                                            errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.detail.' +
                                                                    index2 +
                                                                    '.percentage'
                                                            ]
                                                        "
                                                        class="container-error"
                                                    >
                                                        <ul
                                                            v-for="(
                                                                error, index
                                                            ) in errors[
                                                                'documents.' +
                                                                    index +
                                                                    '.detail.' +
                                                                    index2 +
                                                                    '.percentage'
                                                            ]"
                                                            :key="index"
                                                            class="message-error"
                                                        >
                                                            <li>{{ error }}</li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </q-markup-table>
                                </div>
                                <div
                                    class="col-12 row justify-center items-center cursor-pointer"
                                    style="
                                        height: 120px;
                                        color: #344767;
                                        background-color: #f0f2f5;
                                        border-radius: 0.5rem;
                                    "
                                    @click="HandleAddDocument()"
                                >
                                    <q-icon name="eva-plus" /> Añadir Documento
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                </q-stepper>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, onMounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

defineProps({
    title: String,
    errors: Object,
});
const $q = useQuasar();
const page = usePage();
let step = ref(1);

let message = ref(page.props.flash.message);
let type = ref(page.props.flash.type);
let accounts = ref(page.props.accounts);
let currencies = ref(page.props.currencies);
let employees = ref(page.props.employees);

let options = ref({
    type_calculation: page.props.type_calculation,
    type: page.props.type,
    accounts_document: page.props.accounts_document,
    accounts_filter: page.props.accounts_document,
});
const loading = ref({
    card: false,
});
let filter = ref("");
let filter_emp = ref("");

const form = ref(page.props.profile);
function HandleStoreForm() {
    page.props.errors = {};
    $q.dialog({
        title: "Confirmar",
        message: "¿Esta seguro de Actualizar el Perfil?",
        cancel: true,
        persistent: true,
    })
        .onOk(() => {
            router.put(route("panel.profile.update"), form.value, {
                onSuccess: () => {
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
function HandleFilterAccounts(node, filter) {
    const filt = filter.toLowerCase();
    return node.label && node.label.toLowerCase().indexOf(filt) > -1;
}
function HandleFilterAccountsDocuments(val, update) {
    if (val === "") {
        update(() => {
            options.value.accounts_filter = options.value.accounts_document;
        });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.accounts_filter = options.value.accounts_document.filter(
            (v) => v.AcctName.toLowerCase().indexOf(needle) > -1
        );
    });
}
function HandleAddDocument() {
    form.value.documents.push({
        name: null,
        type_document_sap: null,
        type_calculation: null,
        ice:null,
        tasas:null,
        exento:null,
        detail: [
            {
                type: null,
                percentage: null,
                account: null,
                for_delete:0
            },
        ],
        for_delete:0
    });
}
function HandleAddLineDetail(index) {
    form.value.documents[index].detail.push({
        type: null,
        percentage: null,
        account: null,
        for_delete:0
    });
}
function HandleDeleteLineDetail(index, index2) {
    $q.dialog({
        title: "Confirmar",
        message: "¿Esta seguro de eliminar la linea?",
        cancel: true,
        persistent: true,
    })
    .onOk(() => {
        if (form.value.documents[index].detail[index2].id) {
            form.value.documents[index].detail[index2].for_delete = 1;
        } else {
            form.value.documents[index].detail.splice(index2, 1);
        }
    })
    .onCancel(() => {})
    .onDismiss(() => {});
}
function HanldeDeleteDocument(index) {
    $q.dialog({
        title: "Confirmar",
        message: "¿Esta seguro de eliminar el documento?",
        cancel: true,
        persistent: true,
    })
    .onOk(() => {
        if (form.value.documents[index].id) {
            form.value.documents[index].for_delete = 1;
        } else {
            form.value.documents.splice(index, 1);
        }
    })
    .onCancel(() => {})
    .onDismiss(() => {});
}
</script>
<style>
.q-stepper--horizontal .q-stepper__step-inner {
    padding: 0px 0px;
}
.q-stepper__header--alternative-labels .q-stepper__tab {
    padding: 10px 32px;
    min-height: 70px;
}
.q-stepper__dot {
    font-size: 16px;
    width: 24px;
    height: 24px;
}
.q-stepper__title {
    font-size: 12px;
    line-height: 18px;
    letter-spacing: 0.1px;
}
.delete{
    border: #e91e63 2px solid !important;
}
.delete-line td {
  border-top: 1px solid #e91e63; /* Establece un borde de 1px sólido en todas las celdas */
  border-bottom: 1px solid #e91e63; /* Establece un borde de 1px sólido en todas las celdas */
}
</style>
