<template>
    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">

            <!-- Tabla de alias definidos -->
            <q-card class="card-form q-mt-sm">
                <q-table
                    :rows="aliases"
                    :columns="columns"
                    row-key="acct_code"
                    class="table-theme"
                    :filter="filter"
                    separator="horizontal"
                    :table-header-class="{ 'table-header-theme': true }"
                >
                    <template v-slot:top>
                        <h5 class="title-form">Alias de Cuentas Contables</h5>
                        <q-space />
                        <q-btn
                            flat
                            color="primary"
                            icon="download"
                            label="Plantilla"
                            size="11px"
                            no-caps
                            :href="route('panel.account-alias.template')"
                            type="a"
                            target="_blank"
                            class="q-mr-xs"
                        />
                        <q-btn
                            color="secondary"
                            icon="upload_file"
                            label="Importar Excel"
                            size="11px"
                            no-caps
                            unelevated
                            @click="importDialog = true"
                            class="q-mr-xs"
                        />
                        <q-btn
                            color="primary"
                            icon="add"
                            label="Asignar Alias"
                            size="11px"
                            no-caps
                            unelevated
                            @click="dialog = true"
                        />
                        <q-space />
                        <q-input
                            outlined
                            dense
                            color="primary"
                            placeholder="Buscar..."
                            v-model="filter"
                            class="input-theme"
                        >
                            <template v-slot:append>
                                <q-icon name="search" />
                            </template>
                        </q-input>
                    </template>

                    <template v-slot:header="props">
                        <q-tr :props="props">
                            <q-th auto-width />
                            <q-th v-for="col in props.cols" :key="col.name" :props="props">
                                {{ col.label }}
                            </q-th>
                        </q-tr>
                    </template>

                    <template v-slot:body="props">
                        <q-tr :props="props">
                            <q-td style="width: 90px !important">
                                <div class="text-center q-gutter-xs">
                                    <q-btn size="sm" color="primary" dense icon="eva-edit-2-outline"
                                        @click="HandleStartEdit(props.row)">
                                        <q-tooltip class="bg-secondary">Editar alias</q-tooltip>
                                    </q-btn>
                                    <q-btn size="sm" color="red" dense icon="eva-trash-2-outline"
                                        @click="HandleDelete(props.row)">
                                        <q-tooltip class="bg-red">Quitar alias</q-tooltip>
                                    </q-btn>
                                </div>
                            </q-td>
                            <q-td key="acct_code" :props="props">
                                <span class="text-weight-medium">{{ props.row.acct_code }}</span>
                            </q-td>
                            <q-td key="acct_name" :props="props">
                                <span class="text-grey-7">{{ props.row.acct_name }}</span>
                            </q-td>
                            <q-td key="alias" :props="props">
                                <span class="text-primary text-weight-medium">{{ props.row.alias }}</span>
                            </q-td>
                        </q-tr>
                    </template>

                    <template v-slot:no-data>
                        <div class="full-width text-center q-pa-xl text-grey">
                            <q-icon name="label_off" size="48px" color="grey-4" class="q-mb-sm" />
                            <div class="text-body2">No hay alias definidos aún</div>
                            <q-btn color="primary" label="Asignar primer alias" icon="add" no-caps unelevated
                                class="q-mt-md" size="12px" @click="dialog = true" />
                        </div>
                    </template>
                </q-table>
            </q-card>

            <!-- Dialog: asignar / editar alias -->
            <q-dialog v-model="dialog" persistent @hide="HandleCloseDialog">
                <q-card style="width: 560px; max-width: 96vw;">

                    <q-card-section class="row items-center q-pb-none">
                        <div class="text-h6">{{ editingCode ? 'Editar Alias' : 'Asignar Alias' }}</div>
                        <q-space />
                        <q-btn icon="close" flat round dense v-close-popup />
                    </q-card-section>

                    <q-card-section style="max-height: 75vh; overflow-y: auto;">

                        <!-- Cuenta seleccionada -->
                        <div v-if="selectedNode" class="q-pa-sm rounded-borders q-mb-md"
                            style="border: 1px solid rgba(0,0,0,0.12); background: rgba(0,0,0,0.02);">
                            <div class="text-caption text-grey q-mb-xs">Cuenta seleccionada</div>
                            <div class="text-body2 text-weight-medium">{{ selectedNode.AcctCode }}</div>
                            <div class="text-caption text-grey">{{ selectedNode.AcctName }}</div>
                        </div>
                        <div v-else-if="!editingCode" class="text-caption text-grey q-mb-md">
                            Selecciona una cuenta del árbol para asignarle un alias.
                        </div>

                        <!-- Alias input -->
                        <div class="q-mb-md">
                            <div class="form-label">Alias <span class="text-red">*</span></div>
                            <q-input
                                v-model="aliasInput"
                                dense
                                outlined
                                class="input-theme"
                                placeholder="Nombre amigable para la cuenta..."
                                :disable="!editingCode && !selectedNode"
                                @keyup.enter="HandleSave"
                            />
                        </div>

                        <!-- Árbol SAP (solo en modo asignar) -->
                        <template v-if="!editingCode">
                            <div class="form-label q-mb-xs">Árbol de cuentas SAP</div>
                            <q-input outlined dense color="primary" placeholder="Buscar cuenta..."
                                v-model="treeFilter" class="input-theme q-mb-sm" clearable>
                                <template v-slot:prepend><q-icon name="search" /></template>
                            </q-input>
                            <q-tree
                                :nodes="accounts"
                                node-key="label"
                                v-model:selected="selectedLabel"
                                :filter="treeFilter"
                                :filter-method="HandleFilterTree"
                                @update:selected="HandleTreeSelect"
                            >
                                <template v-slot:default-header="prop">
                                    <div class="tree-label row items-center q-gutter-xs no-wrap">
                                        <span>{{ prop.node.label }}</span>
                                        <q-badge v-if="aliasMap[prop.node.AcctCode]" color="orange-7" dense class="q-px-xs">
                                            <q-tooltip>Alias actual: {{ aliasMap[prop.node.AcctCode] }}</q-tooltip>
                                            alias
                                        </q-badge>
                                    </div>
                                </template>
                            </q-tree>
                        </template>

                    </q-card-section>

                    <q-separator />

                    <q-card-actions align="right" class="q-px-md q-py-sm">
                        <q-btn flat label="Cancelar" no-caps v-close-popup />
                        <q-btn
                            color="primary"
                            label="Guardar"
                            no-caps
                            unelevated
                            :disable="!aliasInput || (!editingCode && !selectedNode)"
                            @click="HandleSave"
                        />
                    </q-card-actions>
                </q-card>
            </q-dialog>

        </div>

        <!-- Dialog: importar Excel -->
        <q-dialog v-model="importDialog" persistent @hide="HandleCloseImport">
            <q-card style="width: 480px; max-width: 96vw;">
                <q-card-section class="row items-center q-pb-none">
                    <div class="text-h6">Importar Alias desde Excel</div>
                    <q-space />
                    <q-btn icon="close" flat round dense v-close-popup />
                </q-card-section>

                <q-card-section>
                    <div class="text-caption text-grey q-mb-md">
                        El archivo debe tener dos columnas: <strong>Código Cuenta</strong> y <strong>Alias</strong>.
                        Si el alias está vacío, se eliminará el alias existente.
                        Descarga la plantilla con el botón <em>Plantilla</em>.
                    </div>

                    <q-file
                        v-model="importFile"
                        outlined
                        dense
                        accept=".xlsx,.xls,.csv"
                        class="input-theme"
                        label="Seleccionar archivo (.xlsx, .xls, .csv)"
                        :loading="importing"
                    >
                        <template v-slot:prepend>
                            <q-icon name="attach_file" />
                        </template>
                    </q-file>

                    <div v-if="importErrors.length" class="q-mt-sm">
                        <div v-for="(err, i) in importErrors" :key="i" class="text-caption text-negative">
                            {{ err }}
                        </div>
                    </div>
                </q-card-section>

                <q-separator />

                <q-card-actions align="right" class="q-px-md q-py-sm">
                    <q-btn flat label="Cancelar" no-caps v-close-popup />
                    <q-btn
                        color="primary"
                        label="Importar"
                        no-caps
                        unelevated
                        icon="upload"
                        :disable="!importFile"
                        :loading="importing"
                        @click="HandleImport"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>

    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import axios from "axios";

defineProps({ title: String });

const $q      = useQuasar();
const page    = usePage();
const aliases  = ref(page.props.aliases);
const accounts = page.props.accounts;
const aliasMap = ref({ ...page.props.alias_map });

const filter      = ref("");
const dialog      = ref(false);
const importDialog = ref(false);
const importFile   = ref(null);
const importErrors = ref([]);
const importing    = ref(false);
const treeFilter  = ref("");
const selectedLabel = ref(null);
const selectedNode  = ref(null);
const aliasInput    = ref("");
const editingCode   = ref(null);

const columns = [
    { name: "acct_code", label: "Código",     field: "acct_code", align: "left", sortable: true },
    { name: "acct_name", label: "Nombre SAP", field: "acct_name", align: "left", sortable: true },
    { name: "alias",     label: "Alias",      field: "alias",     align: "left", sortable: true },
];

function HandleFilterTree(node, filter) {
    const f = filter.toLowerCase();
    return node.label?.toLowerCase().includes(f) || node.AcctCode?.toLowerCase().includes(f);
}

function HandleTreeSelect(label) {
    if (!label) { selectedNode.value = null; return; }
    const node = FindNodeByLabel(accounts, label);
    // Only allow leaf accounts, not group nodes
    if (node?.children?.length) {
        selectedLabel.value = null;
        selectedNode.value  = null;
        return;
    }
    selectedNode.value = node;
    if (node) aliasInput.value = aliasMap.value[node.AcctCode] ?? "";
}

function FindNodeByLabel(nodes, label) {
    for (const node of nodes) {
        if (node.label === label) return node;
        if (node.children?.length) {
            const found = FindNodeByLabel(node.children, label);
            if (found) return found;
        }
    }
    return null;
}

function HandleStartEdit(row) {
    editingCode.value  = row.acct_code;
    aliasInput.value   = row.alias;
    selectedNode.value = null;
    dialog.value       = true;
}

function HandleCloseDialog() {
    editingCode.value   = null;
    aliasInput.value    = "";
    selectedNode.value  = null;
    selectedLabel.value = null;
    treeFilter.value    = "";
}

function HandleSave() {
    const acctCode = editingCode.value ?? selectedNode.value?.AcctCode;
    if (!acctCode || !aliasInput.value) return;

    router.put(
        route("panel.account-alias.update", acctCode),
        { alias: aliasInput.value },
        {
            preserveState: false,
            onSuccess: () => {
                dialog.value = false;
                $q.notify({ type: "positive", message: "Alias guardado correctamente" });
            },
        }
    );
}

function HandleCloseImport() {
    importFile.value   = null;
    importErrors.value = [];
}

async function HandleImport() {
    if (!importFile.value) return;
    importing.value = true;
    importErrors.value = [];

    const formData = new FormData();
    formData.append('file', importFile.value);
    formData.append('_method', 'POST');

    try {
        await axios.post(route('panel.account-alias.import'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        importDialog.value = false;
        $q.notify({ type: 'positive', message: 'Importación completada correctamente' });
        router.reload({ preserveScroll: true });
    } catch (e) {
        const errors = e.response?.data?.errors;
        if (errors) {
            importErrors.value = Object.values(errors).flat();
        } else {
            importErrors.value = [e.response?.data?.message ?? 'Error al importar el archivo'];
        }
    } finally {
        importing.value = false;
    }
}

function HandleDelete(row) {
    $q.dialog({
        title: "Quitar Alias",
        message: `¿Desea quitar el alias "${row.alias}" de la cuenta ${row.acct_code}?`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Quitar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(route("panel.account-alias.delete", row.acct_code), {
            preserveState: false,
            onSuccess: () => {
                $q.notify({ type: "positive", message: "Alias eliminado correctamente" });
            },
        });
    });
}
</script>
