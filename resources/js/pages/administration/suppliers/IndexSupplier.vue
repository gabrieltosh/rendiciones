<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">
                <PageHeader
                    title="Proveedores"
                    subtitle="Proveedores registrados para las facturas de las rendiciones"
                    icon="eva-car-outline"
                >
                    <template #actions>
                        <q-btn data-tour="create-supplier"
                            color="primary"
                            icon="add"
                            label="Crear Proveedor"
                            no-caps
                            unelevated
                            @click="HandleCreateSupplier()"
                        />
                    </template>
                </PageHeader>

                <q-card class="card-form">
                    <q-table data-tour="suppliers-table"
                        :rows="data"
                        :columns="table.columns"
                        row-key="id"
                        class="table-theme"
                        :filter="table.filter"
                        flat
                        separator="horizontal"
                        :table-header-class="{ 'table-header-theme': true }"
                        :pagination="{ rowsPerPage: 15 }"
                        :grid="$q.screen.xs"
                    >
                        <template v-slot:top>
                            <div class="text-subtitle1 text-weight-medium">Lista de Proveedores</div>
                            <q-space />
                            <q-input
                                outlined
                                dense
                                clearable
                                placeholder="Buscar razón social o NIT…"
                                v-model="table.filter"
                                class="input-theme search-input"
                                aria-label="Buscar proveedores"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="search" />
                                </template>
                            </q-input>
                        </template>
                        <template v-slot:body="props">
                            <q-tr :props="props" :class="{ 'bg-blue-1': props.row.id === highlightId }">
                                <q-td key="id" :props="props">{{ props.row.id }}</q-td>
                                <q-td key="business" :props="props">
                                    <span class="text-weight-medium">{{ props.row.business }}</span>
                                </q-td>
                                <q-td key="nit" :props="props">{{ props.row.nit }}</q-td>
                                <q-td key="created_at" :props="props">{{ props.row.created_at }}</q-td>
                                <q-td key="actions" :props="props">
                                    <q-btn data-tour="edit-supplier"
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        color="primary"
                                        icon="eva-edit-2-outline"
                                        aria-label="Editar proveedor"
                                        @click="HandleEditSupplier(props.row)"
                                    >
                                        <q-tooltip>Editar</q-tooltip>
                                    </q-btn>
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        size="sm"
                                        color="negative"
                                        icon="eva-trash-2-outline"
                                        aria-label="Eliminar proveedor"
                                        @click="HandleDeleteSupplier(props.row)"
                                    >
                                        <q-tooltip>Eliminar</q-tooltip>
                                    </q-btn>
                                </q-td>
                            </q-tr>
                        </template>
                        <template v-slot:item="props">
                            <div class="col-12 q-pa-xs">
                                <q-card flat bordered class="card-grid">
                                    <q-card-section class="q-pb-xs">
                                        <div class="text-weight-medium">{{ props.row.business }}</div>
                                        <div class="text-caption text-grey-7">NIT: {{ props.row.nit }}</div>
                                        <div class="text-caption text-grey">Creado: {{ props.row.created_at }}</div>
                                    </q-card-section>
                                    <q-card-actions align="right" class="q-pt-none">
                                        <q-btn flat dense no-caps size="sm" color="primary" icon="eva-edit-2-outline"
                                            label="Editar" @click="HandleEditSupplier(props.row)" />
                                        <q-btn flat dense no-caps size="sm" color="negative" icon="eva-trash-2-outline"
                                            label="Eliminar" @click="HandleDeleteSupplier(props.row)" />
                                    </q-card-actions>
                                </q-card>
                            </div>
                        </template>
                        <template v-slot:no-data>
                            <div class="full-width text-center q-pa-xl text-grey">
                                <q-icon name="eva-car-outline" size="56px" color="grey-4" />
                                <div class="q-mt-sm">
                                    {{ table.filter ? 'Sin resultados para la búsqueda' : 'No hay proveedores registrados' }}
                                </div>
                                <q-btn v-if="!table.filter" flat no-caps color="primary" icon="add" label="Registrar el primer proveedor"
                                    class="q-mt-sm" @click="HandleCreateSupplier()" />
                                <q-btn v-else flat no-caps color="primary" icon="add" :label="`Registrar «${table.filter}»`"
                                    class="q-mt-sm" @click="HandleCreateSupplier(table.filter)" />
                            </div>
                        </template>
                    </q-table>
                </q-card>
            </div>
        </div>

        <!-- Crear / editar sin salir de la lista -->
        <q-dialog v-model="dialog.open" :persistent="saving">
            <q-card data-tour="supplier-form" style="width: 560px; max-width: 96vw;">
                <q-card-section class="row items-center q-pb-none">
                    <div class="text-h6">{{ form.id ? 'Editar proveedor' : 'Crear proveedor' }}</div>
                    <q-space />
                    <q-btn icon="close" flat round dense v-close-popup aria-label="Cerrar" :disable="saving" />
                </q-card-section>
                <q-form @submit.prevent="HandleSaveForm()">
                    <q-card-section class="q-gutter-y-md">
                        <FormField label="Razón Social" required :error="formErrors.business" input-id="supplier-business">
                            <q-input v-model="form.business" for="supplier-business" dense outlined class="input-theme" autofocus />
                        </FormField>
                        <div data-tour="field-nit">
                            <FormField label="NIT" required :error="formErrors.nit" input-id="supplier-nit">
                                <q-input v-model="form.nit" for="supplier-nit" dense outlined class="input-theme" />
                            </FormField>
                        </div>
                    </q-card-section>
                    <q-card-actions align="right" class="q-px-md q-pb-md">
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving" v-close-popup />
                        <q-btn data-tour="save-supplier" type="submit" color="primary" icon="eva-checkmark-outline"
                            :label="form.id ? 'Guardar cambios' : 'Crear Proveedor'" no-caps unelevated :loading="saving" />
                    </q-card-actions>
                </q-form>
            </q-card>
        </q-dialog>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import FormField from "@/components/FormField.vue";
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

const props = defineProps({
    title: String,
    data: Object,
    errors: { type: Object, default: () => ({}) },
});
const $q = useQuasar();
const page = usePage();
// Siempre los datos actuales: la lista se actualiza sin recargar la página tras guardar
const data = computed(() => props.data ?? []);

const table = ref({
    filter: "",
    columns: [
        { name: "id", align: "center", label: "ID", field: "id", sortable: true, style: "width: 60px" },
        { name: "business", align: "left", label: "Razón Social", field: "business", sortable: true },
        { name: "nit", align: "left", label: "NIT", field: "nit", sortable: true },
        { name: "created_at", align: "center", label: "Fecha de Creación", field: "created_at", sortable: true },
        { name: "actions", align: "center", label: "Acciones", field: "actions", style: "width: 100px" },
    ],
});

// ── Crear / editar en un diálogo ────────────────────────────
const dialog = ref({ open: false });
const form = ref({ id: null, business: null, nit: null });
const saving = ref(false);
const highlightId = ref(null);
// Los errores de validación solo se muestran tras intentar guardar en este diálogo
const showErrors = ref(false);
const formErrors = computed(() => (showErrors.value ? props.errors ?? {} : {}));

function HandleOpenDialog(values) {
    form.value = { id: null, business: null, nit: null, ...values };
    showErrors.value = false;
    dialog.value.open = true;
}
// Desde la búsqueda sin resultados: si lo buscado es un número se toma como NIT
function HandleCreateSupplier(search = null) {
    const text = typeof search === "string" ? search.trim() : "";
    HandleOpenDialog(/^\d+$/.test(text) ? { nit: text } : { business: text || null });
}
function HandleEditSupplier(row) {
    HandleOpenDialog({ id: row.id, business: row.business, nit: row.nit });
}
function HandleSaveForm() {
    const editing = !!form.value.id;
    const options = {
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            saving.value = true;
            showErrors.value = true;
        },
        onFinish: () => (saving.value = false),
        onSuccess: () => {
            dialog.value.open = false;
            // Resalta la fila guardada (al crear, la más reciente queda primera)
            highlightId.value = editing ? form.value.id : data.value[0]?.id ?? null;
            setTimeout(() => (highlightId.value = null), 3000);
        },
    };
    editing
        ? router.put(route("panel.supplier.update"), form.value, options)
        : router.post(route("panel.supplier.store"), form.value, options);
}
function HandleDeleteSupplier(row) {
    $q.dialog({
        title: "Eliminar proveedor",
        message: `¿Está seguro de eliminar el proveedor "${row.business}" (NIT ${row.nit})? Esta acción no se puede deshacer.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.supplier.delete", row.id), { preserveScroll: true });
    });
}
</script>
