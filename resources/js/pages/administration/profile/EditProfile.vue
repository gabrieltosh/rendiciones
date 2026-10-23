<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11">
                <PageHeader
                    title="Editar Perfil"
                    :subtitle="form.name || ''"
                    icon="eva-layers-outline"
                    :back="route('panel.profile.index')"
                    sticky
                >
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving"
                            @click="router.visit(route('panel.profile.index'))" />
                        <q-btn color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="saving" @click="HandleUpdateForm()" />
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
import { profileSections } from "@/utils/profiles";

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

const form = ref(page.props.profile);

function HandleUpdateForm() {
    const deleted = form.value.documents.filter((d) => d.for_delete == 1).length;
    $q.dialog({
        title: "Guardar cambios",
        message: deleted
            ? `¿Guardar los cambios del perfil? Se eliminarán ${deleted} documento(s).`
            : "¿Guardar los cambios del perfil?",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Guardar", color: "primary", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.put(route("panel.profile.update"), form.value, {
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
    });
}
</script>
