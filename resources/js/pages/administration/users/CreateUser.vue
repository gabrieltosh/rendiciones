<template>

    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <q-stepper v-model="step" header-nav ref="stepper" color="primary" animated flat alternative-labels
                    class="backgroup-theme">
                    <q-step :name="1" title="Datos Usuario" icon="settings" :done="step > 1"
                        :error="Object.keys(errors).length > 0 ? true : false">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Crear Usuario</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                        router.visit(
                                            route('panel.user.index')
                                        )
                                        " flat />
                                    <q-btn @click="$refs.stepper.next()" color="primary" no-caps size="12px"
                                        label="Continuar" />
                                </div>
                            </div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Nombres <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.name" dense outlined class="input-theme" />
                                    <div v-if="errors.name" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.name" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Username <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.username" dense outlined class="input-theme" />
                                    <div v-if="errors.username" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.username" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Correo Electronico
                                        <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.email" dense outlined class="input-theme" type="email" />
                                    <div v-if="errors.email" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.email" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Contraseña
                                        <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.password" dense outlined class="input-theme"
                                        type="password" />
                                    <div v-if="errors.password" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.password" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Tipo <span class="text-red">*</span>
                                    </div>
                                    <q-select v-model="form.type" :options="['Administrador', 'Usuario']" dense outlined
                                        class="input-theme" type="password" />
                                    <div v-if="errors.type" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.type" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Codigo Empleado
                                        <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.card_code" dense outlined class="input-theme" />
                                    <div v-if="errors.card_code" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.card_code" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step :name="2" title="Perfiles" icon="eva-person-outline" :done="step > 2">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row q-ma-sm">
                                        <div class="col-sm-6">
                                            <h5 class="title-form">
                                                Asignación de Perfiles
                                            </h5>
                                        </div>
                                        <div class="col-sm-6 text-right q-gutter-sm">
                                            <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                                router.visit(
                                                    route(
                                                        'panel.user.index'
                                                    )
                                                )
                                                " flat />
                                            <q-btn flat color="primary" @click="
                                                $refs.stepper.previous()
                                                " label="Atras" no-caps size="12px" class="q-ml-sm" />
                                            <q-btn @click="$refs.stepper.next()" color="primary" no-caps size="12px"
                                                label="Continuar" />
                                        </div>
                                    </div>
                                    <q-separator />
                                </div>
                                <div class="col-sm-6">
                                    <h5 class="title-tree text-center">
                                        Lista de Perfiles
                                    </h5>
                                    <q-input outlined dense color="primary" placeholder="Buscar..." v-model="filter"
                                        class="input-theme"></q-input>
                                    <q-tree :nodes="options.profiles" node-key="label" tick-strategy="leaf"
                                        v-model:ticked="form.profiles" :filter="filter"
                                        :filter-method="HandleFilterAccounts">
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
                                        Perfiles Seleccionados
                                    </h5>
                                    <ul class="q-ma-none">
                                        <div v-for="tick in form.profiles" :key="`ticked-${tick}`" class="tree-label">
                                            <li>{{ tick }}</li>
                                        </div>
                                    </ul>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step :name="3" title="Autorización" icon="eva-person-outline" :done="step > 3">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row q-ma-sm">
                                        <div class="col-sm-6">
                                            <h5 class="title-form">
                                                Asignación de Autorizadores
                                            </h5>
                                        </div>
                                        <div class="col-sm-6 text-right q-gutter-sm">
                                            <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                                router.visit(
                                                    route(
                                                        'panel.user.index'
                                                    )
                                                )
                                                " flat />
                                            <q-btn flat color="primary" @click="
                                                $refs.stepper.previous()
                                                " label="Atras" no-caps size="12px" class="q-ml-sm" />
                                            <q-btn @click="$refs.stepper.next()" color="primary" no-caps size="12px"
                                                label="Continuar" />
                                        </div>
                                    </div>
                                    <q-separator />
                                </div>
                                <div class="col-sm-6">
                                    <h5 class="title-tree text-center">
                                        Lista de Usuarios
                                    </h5>
                                    <q-input outlined dense color="primary" placeholder="Buscar..." v-model="filter2"
                                        class="input-theme"></q-input>
                                    <q-tree :nodes="options.users" node-key="label" tick-strategy="leaf"
                                        v-model:ticked="form.users" :filter="filter2"
                                        :filter-method="HandleFilterAccounts">
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
                                        Usuario Seleccionados
                                    </h5>
                                    <ul class="q-ma-none">
                                        <div v-for="tick in form.users" :key="`ticked-${tick}`" class="tree-label">
                                            <li>{{ tick }}</li>
                                        </div>
                                    </ul>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step :name="4" title="Normas Reparto" icon="eva-file-text-outline">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">
                                        Normas de Reparto
                                    </h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                        router.visit(
                                            route('panel.user.index')
                                        )
                                        " flat />
                                    <q-btn flat color="primary" @click="$refs.stepper.previous()" label="Atras" no-caps
                                        size="12px" class="q-ml-sm" />
                                    <q-btn color="primary" label="Crear" size="12px" no-caps
                                        @click="HandleStoreForm()" />
                                </div>
                            </div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 1
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[1]"
                                        v-model="form.distribution_rule_one" option-value="PrcCode" option-label="Name"
                                        clearable emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 2
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[2]"
                                        v-model="form.distribution_rule_second" option-value="PrcCode"
                                        option-label="Name" clearable emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 3
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[3]"
                                        v-model="form.distribution_rule_three" option-value="PrcCode"
                                        option-label="Name" clearable emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 4
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[4]"
                                        v-model="form.distribution_rule_four" option-value="PrcCode" option-label="Name"
                                        clearable emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Norma Reparto 5
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[5]"
                                        v-model="form.distribution_rule_five" option-value="PrcCode" option-label="Name"
                                        clearable emit-value map-options />
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
let message = ref(page.props.flash.message);
let type = ref(page.props.flash.type);
let step = ref(1);

const options = ref({
    profiles: page.props.profiles,
    distribution: page.props.distribution,
    users: page.props.users,
});
const filter = ref("");
const filter2 = ref("");
const loading = ref({
    card: false,
});
const form = ref({
    name: null,
    username: null,
    email: null,
    password: null,
    type: null,
    distribution_rule_one: null,
    distribution_rule_two: null,
    distribution_rule_three: null,
    distribution_rule_four: null,
    distribution_rule_five: null,
    card_code: null,
    profiles: [],
    users: [],
});
function HandleStoreForm() {
    router.post(route("panel.user.store"), form.value, {
        onSuccess: () => {
            message.value = page.props.flash.message;
            type.value = page.props.flash.type;
            $q.notify({
                type: type.value,
                message: message.value,
            });
        },
    });
}
function HandleFilterAccounts(node, filter) {
    const filt = filter.toLowerCase();
    return node.label && node.label.toLowerCase().indexOf(filt) > -1;
}
onMounted(() => {
    if (page.props.flash.message) {
        message.value = page.props.flash.message;
        type.value = page.props.flash.type;
        $q.notify({
            type: type.value,
            message: message.value,
        });
    }
})
</script>
