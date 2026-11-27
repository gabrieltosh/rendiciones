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
                        <q-btn outline color="primary" icon="save" label="Guardar y seguir editando" no-caps
                            :loading="saving === 'stay'" :disable="!!saving" @click="HandleUpdateForm(true)" />
                        <q-btn data-tour="save-profile" color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="saving === 'close'" :disable="!!saving" @click="HandleUpdateForm()" />
                    </template>
                </PageHeader>

                <ProfileForm data-tour="profile-form"
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
// false | 'stay' | 'close': qué botón de guardado está en curso
const saving = ref(false);

const documentOptions = {
    type: page.props.type,
    document_types: page.props.document_types,
    accounts_document: page.props.accounts_document,
};

const form = ref(page.props.profile);

// Solo se confirma cuando el guardado elimina documentos; si no, se guarda directamente
function HandleUpdateForm(stay = false) {
    if (saving.value) return;
    const deleted = form.value.documents.filter((d) => d.for_delete == 1).length;
    if (!deleted) return HandleSubmit(stay);
    $q.dialog({
        title: "Guardar cambios",
        message: `Al guardar se eliminarán ${deleted} documento(s) del perfil. ¿Desea continuar?`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Guardar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => HandleSubmit(stay));
}

function HandleSubmit(stay) {
    router.put(route("panel.profile.update"), { ...form.value, stay }, {
        // Al seguir editando se conserva la pestaña y la posición, con los datos recién guardados
        preserveState: stay,
        preserveScroll: stay,
        onStart: () => (saving.value = stay ? "stay" : "close"),
        onFinish: () => (saving.value = false),
        onSuccess: () => {
            if (stay) form.value = page.props.profile;
        },
        onError: (errors) => {
            tab.value = firstSectionWithError(errors, profileSections) ?? tab.value;
            scrollToFirstError();
        },
    });
}
</script>
