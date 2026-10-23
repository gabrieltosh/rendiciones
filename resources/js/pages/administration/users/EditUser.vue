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
                        <q-btn color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="saving" @click="HandleUpdateForm()" />
                    </template>
                </PageHeader>

                <q-card class="card-form">
                    <q-tabs v-model="tab" align="left" no-caps active-color="primary" indicator-color="primary"
                        class="form-tabs q-px-sm" inline-label outside-arrows mobile-arrows>
                        <q-tab name="data" icon="eva-person-outline" label="Datos del usuario"
                            :alert="sectionHasError(errors, sections.data) ? 'negative' : false" />
                        <q-tab name="profiles" icon="eva-layers-outline">
                            <div class="row items-center no-wrap q-gutter-xs">
                                <span class="q-tab__label">Perfiles</span>
                                <q-badge v-if="form.profiles.length" color="primary" rounded :label="form.profiles.length" />
                            </div>
                        </q-tab>
                        <q-tab name="authorization" icon="eva-shuffle-2-outline" label="Autorización" />
                        <q-tab name="distribution" icon="eva-pie-chart-outline" label="Centros de costo" />
                    </q-tabs>
                    <q-separator />

                    <q-tab-panels v-model="tab" animated keep-alive class="bg-transparent">
                        <!-- Datos del usuario -->
                        <q-tab-panel name="data" class="q-pa-lg">
                            <div class="form-section-title">Datos del usuario</div>
                            <div class="form-section-caption q-mb-md">
                                Los campos marcados con <span class="text-negative">*</span> son obligatorios.
                            </div>
                            <div class="row q-col-gutter-md">
                                <div class="col-xs-12 col-md-6">
                                    <FormField label="Nombre completo" required :error="errors.name">
                                        <q-input v-model="form.name" dense outlined class="input-theme" />
                                    </FormField>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <FormField label="Usuario" required :error="errors.username"
                                        hint="Con este nombre iniciará sesión">
                                        <q-input v-model="form.username" dense outlined class="input-theme"
                                            autocomplete="off" />
                                    </FormField>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <FormField label="Correo electrónico" required :error="errors.email">
                                        <q-input v-model="form.email" dense outlined class="input-theme" type="email"
                                            autocomplete="off" />
                                    </FormField>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <FormField label="Nueva contraseña" :error="errors.password"
                                        hint="Déjala vacía para mantener la actual. Mínimo 5 caracteres, con mayúsculas, minúsculas y números">
                                        <q-input v-model="form.password" dense outlined class="input-theme"
                                            :type="showPassword ? 'text' : 'password'" autocomplete="new-password">
                                            <template v-slot:append>
                                                <q-btn flat round dense size="sm"
                                                    :icon="showPassword ? 'eva-eye-off-outline' : 'eva-eye-outline'"
                                                    :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                                    @click="showPassword = !showPassword" />
                                            </template>
                                        </q-input>
                                    </FormField>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <FormField label="Rol" required :error="errors.type">
                                        <q-select v-model="form.type" :options="roleOptions" emit-value map-options
                                            dense outlined class="input-theme">
                                            <template v-slot:option="scope">
                                                <q-item v-bind="scope.itemProps">
                                                    <q-item-section>
                                                        <q-item-label>{{ scope.opt.label }}</q-item-label>
                                                        <q-item-label caption>{{ scope.opt.caption }}</q-item-label>
                                                    </q-item-section>
                                                </q-item>
                                            </template>
                                        </q-select>
                                    </FormField>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <FormField label="Estado" required :error="errors.status">
                                        <q-select v-model="form.status" :options="statusOptions" emit-value map-options
                                            dense outlined class="input-theme">
                                            <template v-slot:option="scope">
                                                <q-item v-bind="scope.itemProps">
                                                    <q-item-section>
                                                        <q-item-label>{{ scope.opt.label }}</q-item-label>
                                                        <q-item-label caption>{{ scope.opt.caption }}</q-item-label>
                                                    </q-item-section>
                                                </q-item>
                                            </template>
                                        </q-select>
                                    </FormField>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <FormField label="Área" :error="errors.area_id">
                                        <q-select v-model="form.area_id" :options="options.areas" option-value="value"
                                            option-label="label" emit-value map-options dense outlined
                                            class="input-theme" clearable />
                                    </FormField>
                                </div>
                            </div>
                        </q-tab-panel>

                        <!-- Perfiles -->
                        <q-tab-panel name="profiles" class="q-pa-lg">
                            <ProfilePicker v-model="form.profiles" :profiles="options.profiles" />
                        </q-tab-panel>

                        <!-- Autorización -->
                        <q-tab-panel name="authorization" class="q-pa-lg">
                            <CyclePicker v-model="form.cycle_id" :cycles="options.cycles"
                                @create="newCycleDialog = true" />
                        </q-tab-panel>

                        <!-- Centros de costo -->
                        <q-tab-panel name="distribution" class="q-pa-lg">
                            <div class="form-section-title">Centros de costo (normas de reparto)</div>
                            <div class="form-section-caption q-mb-md">
                                Se usan por defecto al contabilizar en SAP las rendiciones de este usuario.
                            </div>
                            <div class="row q-col-gutter-md">
                                <div v-for="rule in distributionRules" :key="rule.field" class="col-xs-12 col-md-6">
                                    <FormField :label="`Centro de costo ${rule.dimension}`">
                                        <q-select class="input-theme" dense outlined
                                            :options="options.distribution[rule.dimension]"
                                            v-model="form[rule.field]" option-value="PrcCode" option-label="Name"
                                            clearable emit-value map-options />
                                    </FormField>
                                </div>
                            </div>
                        </q-tab-panel>
                    </q-tab-panels>
                </q-card>
            </div>
        </div>

        <QuickCycleDialog v-model="newCycleDialog" :users="options.usersForCycle" @created="HandleCycleCreated" />
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import FormField from "@/components/FormField.vue";
import ProfilePicker from "@/components/users/ProfilePicker.vue";
import CyclePicker from "@/components/users/CyclePicker.vue";
import QuickCycleDialog from "@/components/users/QuickCycleDialog.vue";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { firstSectionWithError, sectionHasError, scrollToFirstError, useHashTab } from "@/utils/forms";
import { roleOptions, statusOptions, distributionRules } from "@/utils/users";

defineProps({
    title: String,
    errors: Object,
    user: Object,
});
const $q = useQuasar();
const page = usePage();
const tab = useHashTab("data", ["data", "profiles", "authorization", "distribution"]);
const saving = ref(false);
const showPassword = ref(false);
const newCycleDialog = ref(false);

const sections = {
    data: ["name", "username", "email", "password", "type", "status", "area_id"],
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
        onSuccess: () => {
            $q.notify({
                type: page.props.flash.type,
                message: page.props.flash.message,
            });
        },
    });
}
</script>
