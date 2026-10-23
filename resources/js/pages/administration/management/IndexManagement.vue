<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-9">
                <PageHeader
                    title="Configuración"
                    subtitle="Parámetros de rendiciones, integración con SAP y datos de la empresa"
                    icon="eva-settings-2-outline"
                    sticky
                >
                    <template #actions>
                        <q-btn color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="saving" @click="HandleStoreForm()" />
                    </template>
                </PageHeader>

                <q-card class="card-form">
                    <q-tabs v-model="tab" align="left" no-caps active-color="primary" indicator-color="primary"
                        class="form-tabs q-px-sm" inline-label outside-arrows mobile-arrows>
                        <q-tab v-for="section in sections" :key="section.key" :name="section.key" :icon="section.icon"
                            :label="section.label" />
                    </q-tabs>
                    <q-separator />

                    <q-tab-panels v-model="tab" animated keep-alive class="bg-transparent">
                        <q-tab-panel v-for="section in sections" :key="section.key" :name="section.key" class="q-pa-lg">
                            <div class="form-section-title">{{ section.title }}</div>
                            <div class="form-section-caption q-mb-md">{{ section.caption }}</div>
                            <div class="row q-col-gutter-md">
                                <div v-for="item in data[section.key]" :key="item.name" class="col-xs-12 col-md-6">
                                    <FormField :label="item.label">
                                        <div v-if="isYesNo(item)" class="yes-no-field row items-center">
                                            <q-toggle v-model="item.value" true-value="SI" false-value="NO" color="primary"
                                                :label="item.value === 'SI' ? 'Sí' : 'No'" />
                                        </div>
                                        <q-input v-else v-model="item.value" dense outlined class="input-theme"
                                            :type="item.type === 'password' && !visible[item.name] ? 'password' : 'text'"
                                            :autocomplete="item.type === 'password' ? 'new-password' : 'off'">
                                            <template v-if="item.type === 'password'" v-slot:append>
                                                <q-btn flat round dense size="sm"
                                                    :icon="visible[item.name] ? 'eva-eye-off-outline' : 'eva-eye-outline'"
                                                    :aria-label="visible[item.name] ? 'Ocultar valor' : 'Mostrar valor'"
                                                    @click="visible[item.name] = !visible[item.name]" />
                                            </template>
                                        </q-input>
                                    </FormField>
                                </div>

                                <div v-if="section.key === 'company'" class="col-xs-12 col-md-6">
                                    <FormField label="Logo de la empresa" hint="Se usa en los reportes PDF. Formatos de imagen (JPG, PNG).">
                                        <q-file v-model="img" dense outlined accept=".jpg, image/*" class="input-theme"
                                            clearable @rejected="onRejected">
                                            <template v-slot:prepend><q-icon name="eva-image-outline" /></template>
                                        </q-file>
                                    </FormField>
                                    <div class="logo-preview q-mt-md row items-center justify-center rounded-borders">
                                        <q-img :src="previewUrl || data.url" fit="contain" width="180px" height="100px" />
                                    </div>
                                    <div v-if="previewUrl" class="text-caption text-grey text-center q-mt-xs">
                                        Vista previa · se guardará al presionar "Guardar cambios"
                                    </div>
                                </div>
                            </div>
                        </q-tab-panel>
                    </q-tab-panels>
                </q-card>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import FormField from "@/components/FormField.vue";
import { ref, reactive, computed, onBeforeUnmount } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { useHashTab } from "@/utils/forms";

defineProps({
    title: String,
    errors: Object,
});
const $q = useQuasar();
const page = usePage();

const sections = [
    { key: "accountability", label: "Rendición", icon: "eva-briefcase-outline", title: "Rendición",
        caption: "Parámetros generales de las rendiciones y de la conexión con SAP." },
    { key: "accountability_detail", label: "Detalle", icon: "eva-list-outline", title: "Detalle de rendición",
        caption: "Campos de SAP donde se registra el detalle de cada gasto." },
    { key: "employee", label: "Empleados", icon: "eva-people-outline", title: "Empleados",
        caption: "Cómo se identifican los empleados en SAP." },
    { key: "suppliers", label: "Proveedores", icon: "eva-car-outline", title: "Proveedores",
        caption: "Campos de SAP usados para los proveedores." },
    { key: "company", label: "Empresa", icon: "eva-home-outline", title: "Datos de la empresa",
        caption: "Información que aparece en los reportes." },
];

const tab = useHashTab("accountability", ["accountability", "accountability_detail", "employee", "suppliers", "company"]);
const saving = ref(false);
const visible = reactive({});
const img = ref(null);

const data = ref({
    accountability: page.props.accountability,
    accountability_detail: page.props.accountability_detail,
    employee: page.props.employee,
    suppliers: page.props.suppliers,
    company: page.props.company,
    url: page.props.url,
});

const previewUrl = computed(() => (img.value ? URL.createObjectURL(img.value) : null));
onBeforeUnmount(() => previewUrl.value && URL.revokeObjectURL(previewUrl.value));

// Los parámetros SI/NO se muestran como interruptor
function isYesNo(item) {
    return item.type === "text" && ["SI", "NO"].includes(item.value);
}

function onRejected() {
    $q.notify({
        type: "negative",
        message: "Selecciona un archivo de imagen",
    });
}

function HandleStoreForm() {
    saving.value = true;
    if (img.value) {
        const formData = new FormData();
        formData.append("image", img.value);
        router.post(route("panel.management.logo"), formData, {
            forceFormData: true,
            preserveState: true,
            onSuccess: () => HandleStoreData(),
            onError: () => (saving.value = false),
        });
    } else {
        HandleStoreData();
    }
}

function HandleStoreData() {
    router.post(
        route("panel.management.update"),
        {
            accountability: data.value.accountability,
            accountability_detail: data.value.accountability_detail,
            employee: data.value.employee,
            suppliers: data.value.suppliers,
            company: data.value.company,
        },
        {
            preserveState: true,
            onFinish: () => (saving.value = false),
            onSuccess: () => {
                $q.notify({
                    type: page.props.flash.type,
                    message: page.props.flash.message,
                });
                img.value = null;
                data.value.accountability = page.props.accountability;
                data.value.accountability_detail = page.props.accountability_detail;
                data.value.employee = page.props.employee;
                data.value.suppliers = page.props.suppliers;
                data.value.company = page.props.company;
                data.value.url = page.props.url;
            },
        }
    );
}
</script>

<style scoped>
.yes-no-field {
    min-height: 32px;
}
.logo-preview {
    border: 1px dashed rgba(0, 0, 0, 0.15);
    padding: 12px;
}
.body--dark .logo-preview {
    border-color: rgba(255, 255, 255, 0.2);
}
</style>
