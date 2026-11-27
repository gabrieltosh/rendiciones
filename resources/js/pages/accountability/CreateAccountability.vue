<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-10 col-lg-8">
                <PageHeader title="Nueva rendición" :subtitle="`Perfil: ${page.props.profile.name}`"
                    icon="eva-plus-square-outline" :back="indexUrl" sticky>
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving"
                            aria-label="Cancelar y volver a la lista" @click="HandleCancel" />
                        <q-btn data-tour="save-accountability" color="primary" icon="eva-checkmark-outline" label="Crear y agregar documentos" no-caps
                            unelevated :loading="saving" @click="HandleStoreForm()" />
                    </template>
                </PageHeader>

                <AccountabilityFormFields data-tour="accountability-form" :form="form" :errors="errors" :profile="page.props.profile"
                    :accounts="page.props.accounts" :employees="page.props.employees" />
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import AccountabilityFormFields from "@/components/accountability/AccountabilityFormFields.vue";
import { useDirtyGuard } from "@/composables/useDirtyGuard";
import { scrollToFirstError } from "@/utils/forms";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

defineProps({
    title: String,
    errors: Object,
});
const page = usePage();
const indexUrl = route("panel.accountability.manage.index", page.props.profile.id);

const saving = ref(false);
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

const { confirmLeave } = useDirtyGuard(form);

function HandleCancel() {
    confirmLeave(() => router.visit(indexUrl));
}

// Al crearla, el servidor lleva al detalle para agregar los documentos
function HandleStoreForm() {
    router.post(route("panel.accountability.manage.store", page.props.profile.id), form.value, {
        preserveState: "errors",
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
        onError: () => scrollToFirstError(),
    });
}
</script>
