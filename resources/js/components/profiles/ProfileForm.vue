<template>
    <q-card class="card-form">
        <q-tabs :model-value="tab" @update:model-value="emit('update:tab', $event)" align="left" no-caps active-color="primary" indicator-color="primary"
            class="form-tabs q-px-sm" inline-label outside-arrows mobile-arrows>
            <q-tab name="general" icon="eva-settings-2-outline" label="General"
                :alert="sectionHasError(errors, sections.general) ? 'negative' : false" />
            <q-tab name="employees" icon="eva-people-outline">
                <div class="row items-center no-wrap q-gutter-xs">
                    <span class="q-tab__label">Empleados</span>
                    <q-badge v-if="form.sin_empleado ? null : form.employees.length" color="primary" rounded :label="form.sin_empleado ? null : form.employees.length" />
                </div>
            </q-tab>
            <q-tab name="general_accounts" icon="eva-book-outline">
                <div class="row items-center no-wrap q-gutter-xs">
                    <span class="q-tab__label">Cuentas cabecera</span>
                    <q-badge v-if="form.general.length" color="primary" rounded :label="form.general.length" />
                </div>
            </q-tab>
            <q-tab name="detail_accounts" icon="eva-list-outline">
                <div class="row items-center no-wrap q-gutter-xs">
                    <span class="q-tab__label">Cuentas detalle</span>
                    <q-badge v-if="form.detail.length" color="primary" rounded :label="form.detail.length" />
                </div>
            </q-tab>
            <q-tab name="documents" icon="eva-file-text-outline"
                :alert="sectionHasError(errors, sections.documents) ? 'negative' : false">
                <div class="row items-center no-wrap q-gutter-xs">
                    <span class="q-tab__label">Documentos</span>
                    <q-badge v-if="activeDocuments" color="primary" rounded :label="activeDocuments" />
                </div>
            </q-tab>
        </q-tabs>
        <q-separator />

        <q-tab-panels :model-value="tab" animated keep-alive class="bg-transparent">
            <q-tab-panel name="general" class="q-pa-lg">
                <div class="form-section-title">Datos generales</div>
                <div class="form-section-caption q-mb-md">
                    Los campos marcados con <span class="text-negative">*</span> son obligatorios.
                </div>
                <div class="row q-col-gutter-md">
                    <div class="col-xs-12 col-md-6">
                        <FormField label="Nombre del perfil" required :error="errors.name"
                            hint="Es el nombre que verán los usuarios al elegir dónde rendir">
                            <q-input v-model="form.name" dense outlined class="input-theme" autocomplete="off"
                                placeholder="Ej: Viáticos Nacionales" />
                        </FormField>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <FormField label="Moneda" required :error="errors.type_currency">
                            <q-select v-model="form.type_currency" :options="currencies" option-value="CurrCode"
                                option-label="CurrName" emit-value map-options dense outlined class="input-theme" />
                        </FormField>
                    </div>
                </div>
            </q-tab-panel>

            <q-tab-panel name="employees" class="q-pa-lg">
                <div class="form-section-title">Empleados</div>
                <div class="form-section-caption q-mb-md">
                    Socios de negocio a cuyo nombre se registran las rendiciones de este perfil.
                </div>
                <q-toggle v-model="form.sin_empleado" color="primary" class="q-mb-xs"
                    label="Este perfil no requiere empleado" />
                <div class="text-caption text-grey q-mb-md">
                    Al cambiar esta opción se limpian las cuentas cabecera seleccionadas, porque cambian las cuentas disponibles.
                </div>
                <TreePicker v-if="!form.sin_empleado" v-model="form.employees" :nodes="employees" node-key="label"
                    search-placeholder="Buscar empleado…" empty-text="Aún no seleccionaste empleados." />
                <div v-else class="text-center q-pa-lg text-grey">
                    <q-icon name="eva-person-delete-outline" size="40px" color="grey-4" />
                    <div class="q-mt-sm">Las rendiciones de este perfil no se asociarán a un empleado.</div>
                </div>
            </q-tab-panel>

            <q-tab-panel name="general_accounts" class="q-pa-lg">
                <div class="form-section-title">Cuentas cabecera</div>
                <div class="form-section-caption q-mb-md">
                    Cuentas que el usuario elige al crear la rendición (cuenta del monto recibido).
                </div>
                <TreePicker v-model="form.general" :nodes="accountsForHeader" node-key="AcctCode"
                    :labels="accountLabels" :alias-map="aliasMap" search-placeholder="Buscar cuenta…"
                    empty-text="Aún no seleccionaste cuentas cabecera." />
            </q-tab-panel>

            <q-tab-panel name="detail_accounts" class="q-pa-lg">
                <div class="form-section-title">Cuentas detalle</div>
                <div class="form-section-caption q-mb-md">
                    Cuentas de gasto que el usuario puede elegir en cada línea de la rendición.
                </div>
                <TreePicker v-model="form.detail" :nodes="accounts" node-key="AcctCode" :labels="accountLabels"
                    :alias-map="aliasMap" search-placeholder="Buscar cuenta…"
                    empty-text="Aún no seleccionaste cuentas detalle." />
            </q-tab-panel>

            <q-tab-panel name="documents" class="q-pa-lg">
                <div class="row items-center q-mb-md">
                    <div class="col">
                        <div class="form-section-title">Documentos</div>
                        <div class="form-section-caption">
                            Tipos de comprobante que se pueden registrar y cómo se contabiliza cada uno.
                        </div>
                    </div>
                    <q-btn color="primary" outline icon="add" label="Agregar documento" no-caps
                        @click="AddDocument()" />
                </div>
                <div v-if="form.documents.length === 0" class="text-center q-pa-xl text-grey">
                    <q-icon name="eva-file-text-outline" size="48px" color="grey-4" />
                    <div class="q-mt-sm">Este perfil aún no tiene documentos.</div>
                </div>
                <div class="column q-gutter-md">
                    <DocumentEditor v-for="(document, index) in form.documents" :key="index" :document="document"
                        :index="index" :errors="errors" :options="documentOptions"
                        @remove="RemoveDocument(index)" />
                </div>
                <q-btn v-if="form.documents.length" flat color="primary" icon="add" label="Agregar otro documento"
                    no-caps class="q-mt-md full-width add-document" @click="AddDocument()" />
            </q-tab-panel>
        </q-tab-panels>
    </q-card>
</template>

<script setup>
import { computed, watch } from "vue";
import { useQuasar } from "quasar";
import FormField from "@/components/FormField.vue";
import TreePicker from "@/components/profiles/TreePicker.vue";
import DocumentEditor from "@/components/profiles/DocumentEditor.vue";
import { sectionHasError } from "@/utils/forms";
import { emptyDocument, buildAccountLabels, filterTreeByLocManTran, profileSections as sections } from "@/utils/profiles";

const props = defineProps({
    // Formulario del perfil (se edita en el mismo objeto)
    form: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    accounts: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    currencies: { type: Array, default: () => [] },
    aliasMap: { type: Object, default: () => ({}) },
    documentOptions: { type: Object, required: true },
    // Pestaña activa (la página la cambia a la primera con errores)
    tab: { type: String, default: "general" },
});
const emit = defineEmits(["update:tab"]);

const $q = useQuasar();

const accountLabels = buildAccountLabels(props.accounts);
const accountsForHeader = computed(() =>
    filterTreeByLocManTran(props.accounts, props.form.sin_empleado ? "N" : "Y")
);
const activeDocuments = computed(() => props.form.documents.filter((d) => d.for_delete != 1).length);

watch(
    () => props.form.sin_empleado,
    () => {
        props.form.general = [];
    }
);

function AddDocument() {
    props.form.documents.push(emptyDocument());
}

function RemoveDocument(index) {
    const document = props.form.documents[index];
    $q.dialog({
        title: "Eliminar documento",
        message: `¿Eliminar "${document.name || `Documento ${index + 1}`}" del perfil? El cambio se aplica al guardar.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        if (document.id) {
            document.for_delete = 1;
        } else {
            props.form.documents.splice(index, 1);
        }
    });
}
</script>

<style scoped>
.add-document {
    border: 1px dashed rgba(26, 115, 232, 0.4);
    border-radius: 0.75rem;
}
</style>
