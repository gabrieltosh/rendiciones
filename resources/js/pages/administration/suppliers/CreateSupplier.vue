<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <q-card class="q-pa-lg card-form">
                    <div class="row">
                        <div class="col-sm-6">
                            <h5 class="title-form">Crear Proveedor</h5>
                        </div>
                        <div class="col-sm-6 text-right q-gutter-sm">
                            <q-btn
                                color="secondary"
                                label="Cancelar"
                                size="12px"
                                no-caps
                                @click="
                                    router.visit(route('panel.supplier.index'))
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
                                Razon Social <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.business"
                                dense
                                outlined
                                class="input-theme"
                            />
                            <div v-if="errors.business" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.business"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-label" for="device_name">
                                NIT <span class="text-red">*</span>
                            </div>
                            <q-input
                                v-model="form.nit"
                                dense
                                outlined
                                class="input-theme"
                            />
                            <div v-if="errors.nit" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.nit"
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

const loading = ref({
    card: false,
});
const form = ref({
    business: null,
    nit: null,
});
function HandleStoreForm() {
    router.post(route("panel.supplier.store"), form.value, {
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
</script>
