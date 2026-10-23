<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-10 col-lg-8">
                <PageHeader title="Editar Rendición" :subtitle="`Perfil: ${page.props.profile.name} · Rendición #${form.id}`" icon="eva-edit-2-outline" sticky>
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="loading.submit"
                            aria-label="Cancelar y volver a la lista" @click="HandleCancel" />
                        <q-btn color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="loading.submit" @click="HandleUpdateForm()" />
                    </template>
                </PageHeader>

                <!-- Form card -->
                <q-card class="q-pa-lg card-form">
                    <div
                        class="text-subtitle1 text-weight-medium q-mb-md"
                    >
                        Datos de la Rendición
                    </div>
                    <div class="row q-col-gutter-md">
                        <!-- Empleado -->
                        <div v-if="!page.props.profile.sin_empleado" class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <label class="form-label">Empleado <span class="text-red">*</span></label>
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
                                option-value="key"
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

                        <!-- Descripción -->
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <label class="form-label">
                                Descripción
                                <span class="text-red">*</span>
                            </label>
                            <q-input
                                v-model="form.description"
                                dense
                                outlined
                                class="input-theme"
                                aria-label="Descripción de la rendición"
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
import PageHeader from "@/components/PageHeader.vue";
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
const form = ref(page.props.accountability);
const initialForm = JSON.stringify(page.props.accountability);

const isDirty = computed(() => {
    return JSON.stringify(form.value) !== initialForm;
});

function handleBeforeUnload(e) {
    if (isDirty.value) {
        e.preventDefault();
        e.returnValue = "";
    }
}

onMounted(() => {
    options.value.accounts = page.props.accounts;
    options.value.employees = page.props.employees;
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
                "Tienes cambios sin guardar. ¿Estás seguro de que deseas salir?",
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

function HandleUpdateForm() {
    loading.value.submit = true;
    router.put(
        route(
            "panel.accountability.manage.update",
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
