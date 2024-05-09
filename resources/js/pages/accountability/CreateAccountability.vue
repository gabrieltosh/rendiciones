<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <q-card class="q-pa-lg card-form">
                    <div class="row">
                        <div class="col-sm-6">
                            <h5 class="title-form">Crear Rendición</h5>
                        </div>
                        <div class="col-sm-6 text-right q-gutter-sm">
                            <q-btn
                                color="secondary"
                                label="Cancelar"
                                size="12px"
                                no-caps
                                @click="
                                    router.visit(
                                        route(
                                            'panel.accountability.manage.index',
                                            page.props.profile.id
                                        )
                                    )
                                "
                                flat
                            />
                            <q-btn
                                color="primary"
                                label="Crear"
                                size="12px"
                                no-caps
                                @click="HandleStoreForm()"
                            />
                        </div>
                    </div>
                    <div class="row q-col-gutter-md q-mt-xs">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Empleado
                            </div>
                            <q-select
                                class="input-theme"
                                dense
                                outlined
                                :options="options.employees"
                                v-model="form.employee"
                                option-value="card_code"
                                option-label="card_name"
                                emit-value
                                map-options
                                use-input
                                input-debounce="0"
                                @filter="HandleFilterEmployee"
                                clearable
                            />
                            <div v-if="errors.employee" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.employee"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Cuenta <span class="text-red">*</span>
                            </div>
                            <q-select
                                class="input-theme"
                                dense
                                outlined
                                :options="options.accounts"
                                v-model="form.account"
                                option-value="account_code"
                                option-label="label"
                                emit-value
                                map-options
                                use-input
                                input-debounce="0"
                                @filter="HandleFilterAccounts"
                                clearable
                            />
                            <div v-if="errors.account" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.account"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Monto Recepcionado
                                <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.total"
                                dense
                                outlined
                                type="number"
                                class="input-theme"
                            />
                            <div v-if="errors.total" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.total"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Descripción <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.description"
                                dense
                                outlined
                                class="input-theme"
                            />
                            <div
                                v-if="errors.description"
                                class="container-error"
                            >
                                <ul
                                    v-for="(error, index) in errors.description"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Fecha Inicio <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.start_date"
                                dense
                                outlined
                                class="input-theme"
                                type="date"
                            />
                            <div
                                v-if="errors.start_date"
                                class="container-error"
                            >
                                <ul
                                    v-for="(error, index) in errors.start_date"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                Fecha Final <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.end_date"
                                dense
                                outlined
                                type="date"
                                class="input-theme"
                            />
                            <div v-if="errors.end_date" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.end_date"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </q-card>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref } from "vue";
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

const options = ref({
    accounts: null,
    employees:[]
});
const loading = ref({
    card: false,
});
const form = ref({
    user_id: null,
    employee: null,
    account: null,
    total: null,
    description: null,
    preliminary: null,
    start_date: null,
    end_date: null,
});
function HandleStoreForm() {
    router.post(route("panel.accountability.manage.store",page.props.profile.id), form.value, {
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
function HandleFilterAccounts(val, update) {
    if (val === "") {
        update(() => {
            options.value.accounts = page.props.accounts;
        });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.accounts = page.props.accounts.filter(
            (v) => v.label.toLowerCase().indexOf(needle) > -1
        );
    });
}
function HandleFilterEmployee(val,update){
    if (val === "") {
        update(() => {
            options.value.employees = page.props.employees;
        });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.employees = page.props.employees.filter(
            (v) => v.card_name.toLowerCase().indexOf(needle) > -1
        );
    });
}
</script>
