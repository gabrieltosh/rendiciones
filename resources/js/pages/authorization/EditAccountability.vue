<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <PageHeader title="Editar Rendición" :subtitle="`${profile.name} · Rendición #${form.id}`" icon="eva-edit-2-outline" sticky>
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving"
                            @click="router.visit(route('panel.accountability.authorization.index'))" />
                        <q-btn data-tour="save-accountability" color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="saving" :disable="saving" @click="HandleUpdateForm()" />
                    </template>
                </PageHeader>

                <q-card data-tour="accountability-form" class="q-pa-lg card-form">
                    <div class="form-section-title q-mb-md">Datos de la rendición</div>
                    <AccountabilityHeaderForm
                        v-model="form"
                        :profile="profile"
                        :accounts="accounts"
                        :employees="employees"
                        :errors="errors"
                    />
                </q-card>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import AccountabilityHeaderForm from "@/components/authorization/AccountabilityHeaderForm.vue";
import { scrollToFirstError } from "@/utils/forms";
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps({
    title: String,
    errors: { type: Object, default: () => ({}) },
    profile: { type: Object, required: true },
    accounts: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    accountability: { type: Object, required: true },
});

const form = ref({ ...props.accountability });
const saving = ref(false);

// El aviso de resultado lo muestra el manejador global de flash del layout
function HandleUpdateForm() {
    if (saving.value) return;
    router.put(route("panel.accountability.authorization.update"), form.value, {
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
        onError: () => scrollToFirstError(),
    });
}
</script>
