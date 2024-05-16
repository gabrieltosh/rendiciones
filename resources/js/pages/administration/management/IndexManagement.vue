<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
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
                        :name="1"
                        title="Datos Usuario"
                        icon="settings"
                        :done="step > 1"
                    >
                        <q-card
                            class="q-px-lg q-py-md q-ma-sm card-form q-mt-md"
                        >
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Rendicion</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn
                                        color="secondary"
                                        label="Cancelar"
                                        size="12px"
                                        no-caps
                                        @click="
                                            router.visit(
                                                route('panel.user.index')
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
                                    v-for="item in data.accountability"
                                >
                                    <div class="form-label" for="device_name">
                                        {{item.label}} <span class="text-red">*</span>
                                    </div>
                                    <q-input
                                        v-model="item.value"
                                        dense
                                        outlined
                                        :type="item.type"
                                        class="input-theme"
                                    />
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step
                        :name="2"
                        title="Campos Rendición"
                        icon="settings"
                        :done="step > 1"
                    >
                        <q-card
                            class="q-px-lg q-py-md q-ma-sm card-form q-mt-md"
                        >
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Detalle Rendición</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn
                                        color="secondary"
                                        label="Cancelar"
                                        size="12px"
                                        no-caps
                                        @click="
                                            router.visit(
                                                route('panel.user.index')
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
                                    v-for="item in data.accountability_detail"
                                >
                                    <div class="form-label" for="device_name">
                                        {{item.label}} <span class="text-red">*</span>
                                    </div>
                                    <q-input
                                        v-model="item.value"
                                        dense
                                        outlined
                                        :type="item.type"
                                        class="input-theme"
                                    />
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step
                        :name="3"
                        title="Empleados"
                        icon="settings"
                        :done="step > 2"
                    >
                        <q-card
                            class="q-px-lg q-py-md q-ma-sm card-form q-mt-md"
                        >
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Empleados</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn
                                        color="secondary"
                                        label="Cancelar"
                                        size="12px"
                                        no-caps
                                        @click="
                                            router.visit(
                                                route('panel.user.index')
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
                                    v-for="item in data.employee"
                                >
                                    <div class="form-label" for="device_name">
                                        {{item.label}} <span class="text-red">*</span>
                                    </div>
                                    <q-input
                                        v-model="item.value"
                                        dense
                                        outlined
                                        :type="item.type"
                                        class="input-theme"
                                    />
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step
                        :name="4"
                        title="Proveedores"
                        icon="settings"
                        :done="step > 3"
                    >
                        <q-card
                            class="q-px-lg q-py-md q-ma-sm card-form q-mt-md"
                        >
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Proveedores</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn
                                        color="secondary"
                                        label="Cancelar"
                                        size="12px"
                                        no-caps
                                        @click="
                                            router.visit(
                                                route('panel.user.index')
                                            )
                                        "
                                        flat
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
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div
                                    class="col-xs-12 col-sm-12 col-md-6 col-lg-6"
                                    v-for="item in data.suppliers"
                                >
                                    <div class="form-label" for="device_name">
                                        {{item.label}} <span class="text-red">*</span>
                                    </div>
                                    <q-input
                                        v-model="item.value"
                                        dense
                                        outlined
                                        :type="item.type"
                                        class="input-theme"
                                    />
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
let data=ref({
    accountability:page.props.accountability,
    accountability_detail:page.props.accountability_detail,
    employee:page.props.employee,
    suppliers:page.props.suppliers,
})
let step = ref(1);

const filter = ref("");
const loading = ref({
    card: false,
});
function HandleStoreForm() {
    router.post(route("panel.management.update"),
        {
            accountability: data.value.accountability,
            accountability_detail: data.value.accountability_detail,
            employee: data.value.employee,
            suppliers:data.value.suppliers
        }
    , {
        onSuccess: () => {
            message.value = page.props.flash.message;
            type.value = page.props.flash.type;
            $q.notify({
                type: type.value,
                message: message.value,
            });
            data.value.accountability=page.props.accountability
            data.value.accountability_detail=page.props.accountability_detail
            data.value.employee=page.props.employee
            data.value.suppliers=page.props.suppliers
        },
    });
}

</script>
