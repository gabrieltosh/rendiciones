<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11">
                <PageHeader
                    title="Crear Perfil"
                    subtitle="Configura un tipo de rendición: empleados, cuentas contables y documentos"
                    icon="eva-layers-outline"
                    :back="route('panel.profile.index')"
                    sticky
                >
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving"
                            @click="router.visit(route('panel.profile.index'))" />
                        <q-btn color="primary" icon="eva-checkmark-outline" label="Crear Perfil" no-caps unelevated
                            :loading="saving" @click="HandleStoreForm()" />
                    </template>
                </PageHeader>

                <ProfileForm
                    v-model:tab="tab"
                    :form="form"
                    :errors="errors"
                    :accounts="page.props.accounts"
                    :employees="page.props.employees"
                    :currencies="page.props.currencies"
                    :alias-map="page.props.alias_map ?? {}"
                    :document-options="documentOptions"
                />
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import ProfileForm from "@/components/profiles/ProfileForm.vue";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { firstSectionWithError, scrollToFirstError, useHashTab } from "@/utils/forms";
import { emptyDocument, profileSections } from "@/utils/profiles";

defineProps({
    title: String,
    errors: Object,
});
const $q = useQuasar();
const page = usePage();
const tab = useHashTab("general", ["general", "employees", "general_accounts", "detail_accounts", "documents"]);
const saving = ref(false);

const documentOptions = {
    type: page.props.type,
    document_types: page.props.document_types,
    accounts_document: page.props.accounts_document,
};

const form = ref({
    name: null,
    type_currency: null,
    sin_empleado: false,
    detail: [],
    general: [],
    employees: [],
    documents: [emptyDocument()],
});

function HandleStoreForm() {
    router.post(route("panel.profile.store"), form.value, {
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
        onError: (errors) => {
            tab.value = firstSectionWithError(errors, profileSections) ?? tab.value;
            scrollToFirstError();
        },
        onSuccess: () => {
            $q.notify({ type: page.props.flash.type, message: page.props.flash.message });
        },
    });
}
</script>
