<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-10 col-lg-8">
                <PageHeader title="Editar rendición"
                    :subtitle="`Perfil: ${page.props.profile.name} · Rendición #${form.id}`" icon="eva-edit-2-outline"
                    :back="returnUrl" sticky>
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving"
                            aria-label="Cancelar y volver" @click="HandleCancel" />
                        <q-btn data-tour="save-accountability" color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="saving" @click="HandleUpdateForm()" />
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

const saving = ref(false);
const form = ref(page.props.accountability);

// Si la edición se abrió desde el detalle, Cancelar y Guardar vuelven al detalle
const fromDetail = new URLSearchParams(window.location.search).get("from") === "detail";
const returnUrl = fromDetail
    ? route("panel.accountability.manage.detail.index", [page.props.profile.id, form.value.id])
    : route("panel.accountability.manage.index", page.props.profile.id);

const { confirmLeave } = useDirtyGuard(form);

function HandleCancel() {
    confirmLeave(() => router.visit(returnUrl));
}

function HandleUpdateForm() {
    router.put(
        route("panel.accountability.manage.update", page.props.profile.id),
        { ...form.value, return_to: fromDetail ? "detail" : null },
        {
            preserveState: "errors",
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
            onError: () => scrollToFirstError(),
        }
    );
}
</script>
