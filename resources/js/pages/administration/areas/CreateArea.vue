<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-10 col-lg-8">
                <PageHeader
                    title="Crear Área"
                    subtitle="Registra una nueva sucursal o área"
                    icon="eva-pie-chart-outline"
                    :back="route('panel.area.index')"
                >
                    <template #actions>
                        <q-btn
                            flat
                            color="secondary"
                            label="Cancelar"
                            no-caps
                            :disable="saving"
                            @click="router.visit(route('panel.area.index'))"
                        />
                        <q-btn
                            color="primary"
                            icon="eva-checkmark-outline"
                            label="Crear Área"
                            no-caps
                            unelevated
                            :loading="saving"
                            @click="HandleStoreForm()"
                        />
                    </template>
                </PageHeader>

                <q-card class="q-pa-lg card-form">
                    <div class="form-section-title">Datos del área</div>
                    <div class="form-section-caption q-mb-md">Los campos marcados con <span class="text-negative">*</span> son obligatorios.</div>
                    <q-form class="row q-col-gutter-md" @submit.prevent="HandleStoreForm()">
                        <div class="col-xs-12 col-md-6">
                            <FormField label="Nombre" required :error="errors.name" input-id="name">
                                <q-input v-model="form.name" for="name" dense outlined class="input-theme" autofocus />
                            </FormField>
                        </div>
                        <div class="col-xs-12 col-md-6">
                            <FormField label="Descripción" hint="Opcional, para identificarla mejor" :error="errors.description" input-id="description">
                                <q-input v-model="form.description" for="description" dense outlined class="input-theme" />
                            </FormField>
                        </div>
                        <button type="submit" hidden />
                    </q-form>
                </q-card>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import FormField from "@/components/FormField.vue";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { scrollToFirstError } from "@/utils/forms";

defineProps({
    title: String,
    errors: Object,
});
const $q = useQuasar();
const page = usePage();
const saving = ref(false);

const form = ref({
    name: null,
    description: null,
});

function HandleStoreForm() {
    router.post(route("panel.area.store"), form.value, {
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
        onError: () => scrollToFirstError(),
        onSuccess: () => {
            $q.notify({ type: page.props.flash.type, message: page.props.flash.message });
        },
    });
}
</script>
