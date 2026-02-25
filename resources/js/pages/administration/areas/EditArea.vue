<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <q-card class="q-pa-lg card-form">
                    <div class="row">
                        <div class="col-sm-6">
                            <h5 class="title-form">Editar Área</h5>
                        </div>
                        <div class="col-sm-6 text-right q-gutter-sm">
                            <q-btn
                                color="secondary"
                                label="Cancelar"
                                size="12px"
                                no-caps
                                flat
                                @click="router.visit(route('panel.area.index'))"
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
                            <div class="form-label">
                                Nombre <span class="text-red">*</span>
                            </div>
                            <q-input v-model="form.name" dense outlined class="input-theme" />
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
                            <div class="form-label">Descripción</div>
                            <q-input v-model="form.description" dense outlined class="input-theme" />
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
    area: Object,
});
const $q = useQuasar();
const page = usePage();
let message = ref(page.props.flash.message);
let type = ref(page.props.flash.type);

const form = ref(page.props.area);

function HandleUpdateForm() {
    router.put(route("panel.area.update"), form.value, {
        onSuccess: () => {
            message.value = page.props.flash.message;
            type.value = page.props.flash.type;
            $q.notify({ type: type.value, message: message.value });
        },
    });
}
</script>
