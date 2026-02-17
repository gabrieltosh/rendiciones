<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <!-- Header card -->
                <q-card class="q-pa-md card-form q-mb-md">
                    <div class="row items-center">
                        <div class="col">
                            <div class="text-h6 title-form">
                                Crear Rendicion
                            </div>
                            <div class="text-caption text-grey">
                                Perfil: {{ page.props.profile.name }}
                            </div>
                        </div>
                        <div class="col-auto q-gutter-sm">
                            <q-btn
                                color="secondary"
                                label="Cancelar"
                                size="12px"
                                no-caps
                                flat
                                unelevated
                                aria-label="Cancelar y volver a la lista"
                                @click="HandleCancel"
                            />
                            <q-btn
                                color="primary"
                                label="Crear Rendicion"
                                size="12px"
                                no-caps
                                unelevated
                                :loading="loading.submit"
                                :disable="loading.submit"
                                aria-label="Crear nueva rendicion"
                                @click="HandleStoreForm()"
                            />
                        </div>
                    </div>
                </q-card>

                <!-- Form card -->
                <q-card class="q-pa-lg card-form">
                    <div
                        class="text-subtitle1 text-weight-medium q-mb-md"
                    >
                        Datos de la Rendicion
                    </div>
                    <div class="row q-col-gutter-md">
                        <!-- Empleado -->
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <label class="form-label">Empleado</label>
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
                                aria-label="Seleccionar empleado"
                                name="employee"
                                autocomplete="off"
                            >
                                <template v-slot:no-option>
                                    <q-item>
                                        <q-item-section class="text-grey">
                                            Sin resultados
                                        </q-item-section>
                                    </q-item>
                                </template>
                            </q-select>
                            <div
                                v-if="errors.employee"
                                class="container-error"
                            >
                                <ul class="message-error">
                                    <li
                                        v-for="(
                                            error, index
                                        ) in errors.employee"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Cuenta -->
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <label class="form-label">
                                Cuenta <span class="text-red">*</span>
                            </label>
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
                                aria-label="Seleccionar cuenta"
                                name="account"
                                autocomplete="off"
                            >
                                <template v-slot:no-option>
                                    <q-item>
                                        <q-item-section class="text-grey">
                                            Sin resultados
                                        </q-item-section>
                                    </q-item>
                                </template>
                            </q-select>
                            <div
                                v-if="errors.account"
                                class="container-error"
                            >
                                <ul class="message-error">
                                    <li
                                        v-for="(
                                            error, index
                                        ) in errors.account"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Monto Recepcionado -->
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <label class="form-label">
                                Monto Recepcionado
                                <span class="text-red">*</span>
                            </label>
                            <q-input
                                v-model="form.total"
                                dense
                                outlined
                                type="number"
                                class="input-theme"
                                style="font-variant-numeric: tabular-nums"
                                aria-label="Monto recepcionado"
                                name="total"
                                autocomplete="off"
                            />
                            <div
                                v-if="errors.total"
                                class="container-error"
                            >
                                <ul class="message-error">
                                    <li
                                        v-for="(
                                            error, index
                                        ) in errors.total"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Descripcion -->
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <label class="form-label">
                                Descripcion
                                <span class="text-red">*</span>
                            </label>
                            <q-input
                                v-model="form.description"
                                dense
                                outlined
                                class="input-theme"
                                aria-label="Descripcion de la rendicion"
                                name="description"
                                autocomplete="off"
                            />
                            <div
                                v-if="errors.description"
                                class="container-error"
                            >
                                <ul class="message-error">
                                    <li
                                        v-for="(
                                            error, index
                                        ) in errors.description"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Fecha Inicio -->
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <label class="form-label">
                                Fecha Inicio
                                <span class="text-red">*</span>
                            </label>
                            <q-input
                                v-model="form.start_date"
                                dense
                                outlined
                                class="input-theme"
                                type="date"
                                aria-label="Fecha de inicio"
                                name="start_date"
                            />
                            <div
                                v-if="errors.start_date"
                                class="container-error"
                            >
                                <ul class="message-error">
                                    <li
                                        v-for="(
                                            error, index
                                        ) in errors.start_date"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Fecha Final -->
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <label class="form-label">
                                Fecha Final
                                <span class="text-red">*</span>
                            </label>
                            <q-input
                                v-model="form.end_date"
                                dense
                                outlined
                                type="date"
                                class="input-theme"
                                aria-label="Fecha final"
                                name="end_date"
                            />
                            <div
                                v-if="errors.end_date"
                                class="container-error"
                            >
                                <ul class="message-error">
                                    <li
                                        v-for="(
                                            error, index
                                        ) in errors.end_date"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
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
import { ref, computed, onMounted, onUnmounted } from "vue";
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
    employees: [],
});
const loading = ref({
    card: false,
    submit: false,
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

const isDirty = computed(() => {
    return Object.values(form.value).some((v) => v !== null && v !== "");
});

function handleBeforeUnload(e) {
    if (isDirty.value) {
        e.preventDefault();
        e.returnValue = "";
    }
}

onMounted(() => {
    window.addEventListener("beforeunload", handleBeforeUnload);
});

onUnmounted(() => {
    window.removeEventListener("beforeunload", handleBeforeUnload);
});

function HandleCancel() {
    if (isDirty.value) {
        $q.dialog({
            title: "Cambios sin guardar",
            message:
                "Tienes cambios sin guardar. ¿Estas seguro de que deseas salir?",
            cancel: { label: "Quedarse", flat: true, noCaps: true },
            ok: { label: "Salir", color: "negative", noCaps: true },
            persistent: true,
        }).onOk(() => {
            router.visit(
                route(
                    "panel.accountability.manage.index",
                    page.props.profile.id
                )
            );
        });
    } else {
        router.visit(
            route(
                "panel.accountability.manage.index",
                page.props.profile.id
            )
        );
    }
}

function HandleStoreForm() {
    loading.value.submit = true;
    router.post(
        route(
            "panel.accountability.manage.store",
            page.props.profile.id
        ),
        form.value,
        {
            onSuccess: () => {
                message.value = page.props.flash.message;
                type.value = page.props.flash.type;
                $q.notify({
                    type: type.value,
                    message: message.value,
                });
            },
            onFinish: () => {
                loading.value.submit = false;
            },
        }
    );
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
function HandleFilterEmployee(val, update) {
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
