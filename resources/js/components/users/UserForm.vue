<template>
    <q-card class="card-form">
        <q-tabs data-tour="user-tabs" :model-value="tab" @update:model-value="(value) => emit('update:tab', value)" align="left" no-caps
            active-color="primary" indicator-color="primary" class="form-tabs q-px-sm" inline-label outside-arrows
            mobile-arrows>
            <q-tab name="data" icon="eva-person-outline" label="Datos del usuario"
                :alert="sectionHasError(errors, sections.data) ? 'negative' : false" />
            <q-tab data-tour="tab-profiles" name="profiles" icon="eva-layers-outline">
                <div class="row items-center no-wrap q-gutter-xs">
                    <span class="q-tab__label">Perfiles</span>
                    <q-badge v-if="form.profiles.length" color="primary" rounded :label="form.profiles.length" />
                </div>
            </q-tab>
            <q-tab data-tour="tab-authorization" name="authorization" icon="eva-shuffle-2-outline" label="Autorización" />
            <q-tab data-tour="tab-distribution" name="distribution" icon="eva-pie-chart-outline" label="Centros de costo" />
        </q-tabs>
        <q-separator />

        <q-tab-panels :model-value="tab" animated keep-alive class="bg-transparent">
            <!-- Datos del usuario -->
            <q-tab-panel name="data" class="q-pa-lg">
                <div class="form-section-title">Datos del usuario</div>
                <div class="form-section-caption q-mb-md">
                    Los campos marcados con <span class="text-negative">*</span> son obligatorios.
                </div>
                <div class="row q-col-gutter-md">
                    <div class="col-xs-12 col-md-6">
                        <FormField label="Nombre completo" required :error="errors.name">
                            <q-input v-model="form.name" dense outlined class="input-theme" :autofocus="!isEdit" />
                        </FormField>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <FormField label="Usuario" required :error="errors.username"
                            hint="Con este nombre iniciará sesión">
                            <q-input v-model="form.username" dense outlined class="input-theme" autocomplete="off" />
                        </FormField>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <FormField label="Correo electrónico" required :error="errors.email">
                            <q-input v-model="form.email" dense outlined class="input-theme" type="email"
                                autocomplete="off" />
                        </FormField>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <FormField :label="isEdit ? 'Nueva contraseña' : 'Contraseña'" :required="!isEdit"
                            :error="errors.password"
                            :hint="isEdit
                                ? 'Déjala vacía para mantener la actual. Mínimo 5 caracteres, con mayúsculas, minúsculas y números'
                                : 'Mínimo 5 caracteres, con mayúsculas, minúsculas y números'">
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
                    <div data-tour="field-role" class="col-xs-12 col-md-6">
                        <FormField label="Rol" required :error="errors.type">
                            <q-select v-model="form.type" :options="roleOptions" emit-value map-options dense outlined
                                class="input-theme">
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
                    <div v-if="isEdit" class="col-xs-12 col-md-6">
                        <FormField label="Estado" required :error="errors.status">
                            <q-select v-model="form.status" :options="statusOptions" emit-value map-options dense
                                outlined class="input-theme">
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
                                option-label="label" emit-value map-options dense outlined class="input-theme"
                                clearable />
                        </FormField>
                    </div>
                    <div data-tour="field-business-partner" class="col-xs-12 col-md-6">
                        <FormField label="Socio de negocio (empleado SAP)" :error="errors.card_code"
                            hint="Escribe al menos 2 caracteres del código o nombre para buscar en SAP">
                            <q-select v-model="form.card_code" :options="employeeOptions" option-value="card_code"
                                :option-label="employeeLabel" emit-value map-options use-input input-debounce="350"
                                dense outlined clearable class="input-theme" :loading="searchingEmployees"
                                aria-label="Buscar socio de negocio en SAP" @filter="HandleSearchEmployees">
                                <template v-slot:prepend>
                                    <q-icon name="eva-search-outline" size="18px" />
                                </template>
                                <template v-slot:no-option>
                                    <q-item>
                                        <q-item-section class="text-grey">
                                            {{ employeeSearchMessage }}
                                        </q-item-section>
                                    </q-item>
                                </template>
                            </q-select>
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
                <CyclePicker v-model="form.cycle_id" :cycles="options.cycles" @create="emit('create-cycle')" />
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
                            <q-select class="input-theme" dense outlined :options="options.distribution[rule.dimension]"
                                v-model="form[rule.field]" option-value="PrcCode" option-label="Name" clearable
                                emit-value map-options />
                        </FormField>
                    </div>
                </div>
            </q-tab-panel>
        </q-tab-panels>
    </q-card>
</template>
<script setup>
import FormField from "@/components/FormField.vue";
import ProfilePicker from "@/components/users/ProfilePicker.vue";
import CyclePicker from "@/components/users/CyclePicker.vue";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import axios from "axios";
import { sectionHasError } from "@/utils/forms";
import { roleOptions, statusOptions, distributionRules } from "@/utils/users";

// Formulario compartido por CreateUser y EditUser (igual que ProfileForm)
const props = defineProps({
    form: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    options: { type: Object, required: true },
    tab: { type: String, default: "data" },
    mode: { type: String, default: "create" }, // create | edit
});
const emit = defineEmits(["update:tab", "create-cycle"]);

const isEdit = computed(() => props.mode === "edit");
const showPassword = ref(false);

const sections = computed(() => ({
    data: ["name", "username", "email", "password", "type", "area_id", "card_code", ...(isEdit.value ? ["status"] : [])],
}));

// Búsqueda remota de socios de negocio (OCRD) en SAP
const searchingEmployees = ref(false);
const lastSearch = ref("");
const employeeOptions = ref(
    props.form.card_code ? [{ card_code: props.form.card_code, card_name: props.form.card_name ?? "" }] : []
);
const employeeSearchMessage = computed(() =>
    lastSearch.value.length < 2 ? "Escribe al menos 2 caracteres" : "No se encontraron socios de negocio"
);

function employeeLabel(option) {
    if (!option) return "";
    if (typeof option === "string") return option;
    return option.card_name ? `${option.card_code} — ${option.card_name}` : option.card_code;
}

function HandleSearchEmployees(value, update, abort) {
    const search = (value || "").trim();
    lastSearch.value = search;
    if (search.length < 2) {
        update(() => {});
        return;
    }
    searchingEmployees.value = true;
    axios
        .get(route("panel.accountability.authorization.employees", { search }))
        .then(({ data }) => update(() => (employeeOptions.value = Array.isArray(data) ? data : [])))
        .catch(() => abort())
        .finally(() => (searchingEmployees.value = false));
}
</script>
