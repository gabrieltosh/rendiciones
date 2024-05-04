<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <q-card class="q-pa-lg card-form">
                    <div class="row">
                        <div class="col-sm-6">
                            <h5 class="title-form">Actualizar Usuario</h5>
                        </div>
                        <div class="col-sm-6 text-right q-gutter-sm">
                            <q-btn
                                color="secondary"
                                label="Cancelar"
                                size="12px"
                                no-caps
                                @click="
                                    router.visit(route('panel.user.index'))
                                "
                                flat
                            />
                            <q-btn
                                color="primary"
                                label="Actualizar"
                                size="12px"
                                no-caps
                                @click="HandleUpdateForm()"
                            />
                        </div>
                    </div>
                    <div class="row q-col-gutter-md q-mt-xs">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Nombres <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.name"
                                dense
                                outlined
                                class="input-theme"
                            />
                            <div v-if="errors.name" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.name"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Username <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.username"
                                dense
                                outlined
                                class="input-theme"
                            />
                            <div v-if="errors.username" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.username"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Correo Electronico
                                <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.email"
                                dense
                                outlined
                                class="input-theme"
                                type="email"
                            />
                            <div v-if="errors.email" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.email"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Contraseña
                            </div>
                            <q-input
                                v-model="form.password"
                                dense
                                outlined
                                class="input-theme"
                                type="password"
                            />
                            <div v-if="errors.password" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.password"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Tipo <span class="text-red">*</span>
                            </div>
                            <q-select v-model="form.type" :options="['Administrador','Usuario']"
                                dense
                                outlined
                                class="input-theme"
                                type="password" />
                            <div v-if="errors.type" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.type"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Estado <span class="text-red">*</span>
                            </div>
                            <q-select v-model="form.status" :options="['Activo','PreActivo','Bloqueado']"
                                dense
                                outlined
                                class="input-theme"
                                type="password" />
                            <div v-if="errors.status" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.status"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </q-card>
                <q-card class="q-px-lg q-pa-sm card-form q-mt-md">
                    <div class="row">
                        <div class="col-12">
                            <h5 class="title-form">
                                Asignación de Perfiles
                            </h5>
                            <q-separator />
                        </div>
                        <div class="col-sm-6">
                            <h5 class="title-tree text-center">
                                Lista de Perfiles
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
                                :nodes="options.profiles"
                                node-key="label"
                                tick-strategy="leaf"
                                v-model:ticked="form.profiles"
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
                                Perfiles Seleccionados
                            </h5>
                            <ul class="q-ma-none">
                                <div
                                    v-for="tick in form.profiles"
                                    :key="`ticked-${tick}`"
                                    class="tree-label"
                                >
                                    <li>{{ tick }}</li>
                                </div>
                            </ul>
                        </div>
                    </div>
                </q-card>
                <q-card class="q-pa-lg card-form q-mt-md">
                    <div>
                        <div class="row">
                            <div class="col-sm-6">
                                <h5 class="title-form">Normas de Reparto</h5>
                            </div>
                        </div>
                    </div>
                    <div class="row q-col-gutter-md q-mt-xs">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Norma de Reparto 1
                            </div>
                            <q-input
                                v-model="form.distribution_rule_one"
                                dense
                                outlined
                                class="input-theme"
                            />
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Norma de Reparto 2
                            </div>
                            <q-input
                                v-model="form.distribution_rule_second"
                                dense
                                outlined
                                class="input-theme"
                            />
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Norma de Reparto 3
                            </div>
                            <q-input
                                v-model="form.distribution_rule_three"
                                dense
                                outlined
                                class="input-theme"
                            />
                        </div>
                    </div>
                </q-card>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, watch, onMounted} from "vue";
import { Head, usePage, useForm, router } from "@inertiajs/vue3";
import {route} from "ziggy-js"
import { useQuasar } from "quasar";


defineProps({
    title: String,
    errors: Object,
    user:Object
});
const $q = useQuasar();
const page = usePage();
let message = ref(page.props.flash.message)
let type = ref(page.props.flash.type)
const options = ref({
    profiles:page.props.profiles
})
const filter = ref('')
const form = ref(page.props.user);

function HandleUpdateForm() {
    router.put(route('panel.user.update'),form.value,{
        onSuccess:()=>{
            message.value=page.props.flash.message
            type.value=page.props.flash.type
            $q.notify({
                type: type.value,
                message: message.value,
            });
        }
    });
}
function HandleFilterAccounts(node, filter) {
    const filt = filter.toLowerCase();
    return node.label && node.label.toLowerCase().indexOf(filt) > -1;
}
</script>
