<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-9">
                <PageHeader
                    title="Editar Usuario"
                    :subtitle="form.name || ''"
                    icon="eva-person-outline"
                    :back="route('panel.user.index')"
                    sticky
                >
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving"
                            @click="router.visit(route('panel.user.index'))" />
                        <q-btn data-tour="save-user" color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="saving" @click="HandleUpdateForm()" />
                    </template>
                </PageHeader>

                <UserForm data-tour="user-form" v-model:tab="tab" :form="form" :errors="errors" :options="options" mode="edit"
                    @create-cycle="newCycleDialog = true" />
            </div>
        </div>

        <QuickCycleDialog v-model="newCycleDialog" :users="options.usersForCycle" @created="HandleCycleCreated" />
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import UserForm from "@/components/users/UserForm.vue";
import QuickCycleDialog from "@/components/users/QuickCycleDialog.vue";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { firstSectionWithError, scrollToFirstError, useHashTab } from "@/utils/forms";

defineProps({
    title: String,
    errors: Object,
    user: Object,
});
const page = usePage();
const tab = useHashTab("data", ["data", "profiles", "authorization", "distribution"]);
const saving = ref(false);
const newCycleDialog = ref(false);

const sections = {
    data: ["name", "username", "email", "password", "type", "status", "area_id", "card_code"],
};

const options = ref({
    profiles: page.props.profiles,
    distribution: page.props.distribution,
    areas: page.props.areas,
    cycles: page.props.cycles ?? [],
    usersForCycle: page.props.usersForCycle ?? [],
});

const form = ref({ ...page.props.user, password: null });

function HandleCycleCreated(cycle) {
    options.value.cycles.push(cycle);
    form.value.cycle_id = cycle.value;
}

function HandleUpdateForm() {
    router.put(route("panel.user.update"), form.value, {
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
        onError: (errors) => {
            tab.value = firstSectionWithError(errors, sections) ?? tab.value;
            scrollToFirstError();
        },
    });
}
</script>
