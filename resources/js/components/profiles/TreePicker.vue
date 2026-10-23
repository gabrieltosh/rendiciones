<template>
    <div class="row q-col-gutter-lg">
        <div class="col-xs-12 col-md-7">
            <div class="row items-center q-mb-sm q-gutter-sm">
                <q-input v-model="filter" outlined dense clearable :placeholder="searchPlaceholder"
                    class="input-theme col" :aria-label="searchPlaceholder">
                    <template v-slot:prepend><q-icon name="search" /></template>
                </q-input>
                <q-btn flat dense no-caps size="sm" color="primary" icon="unfold_more" label="Expandir"
                    @click="tree?.expandAll()" />
                <q-btn flat dense no-caps size="sm" color="primary" icon="unfold_less" label="Contraer"
                    @click="tree?.collapseAll()" />
            </div>
            <div class="tree-box rounded-borders q-pa-sm">
                <q-tree ref="tree" :nodes="nodes" :node-key="nodeKey" tick-strategy="leaf"
                    :ticked="modelValue" :filter="filter" :filter-method="FilterNode"
                    no-nodes-label="No hay elementos disponibles" no-results-label="Sin resultados"
                    @update:ticked="emit('update:modelValue', $event)">
                    <template v-slot:default-header="prop">
                        <div class="tree-label row items-center q-gutter-xs no-wrap">
                            <span>{{ prop.node.label }}</span>
                            <q-badge v-if="aliasMap[prop.node.AcctCode]?.length" color="orange-7" dense class="q-px-xs">
                                <q-tooltip>{{ aliasMap[prop.node.AcctCode].join(' · ') }}</q-tooltip>
                                {{ aliasMap[prop.node.AcctCode].length }} alias
                            </q-badge>
                        </div>
                    </template>
                </q-tree>
            </div>
        </div>
        <div class="col-xs-12 col-md-5">
            <div class="row items-center q-mb-sm">
                <div class="form-label q-mb-none col">Seleccionados ({{ modelValue.length }})</div>
                <q-btn v-if="modelValue.length" flat dense no-caps size="sm" color="negative" label="Quitar todos"
                    @click="emit('update:modelValue', [])" />
            </div>
            <div v-if="modelValue.length === 0" class="text-caption text-grey q-py-md">{{ emptyText }}</div>
            <q-list v-else dense bordered separator class="rounded-borders selected-list">
                <q-item v-for="key in modelValue" :key="key">
                    <q-item-section class="text-caption">{{ labels[key] ?? key }}</q-item-section>
                    <q-item-section side>
                        <q-btn flat round dense size="xs" icon="close" aria-label="Quitar"
                            @click="emit('update:modelValue', modelValue.filter((k) => k !== key))" />
                    </q-item-section>
                </q-item>
            </q-list>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";

defineProps({
    nodes: { type: Array, default: () => [] },
    nodeKey: { type: String, default: "label" },
    // Claves marcadas
    modelValue: { type: Array, default: () => [] },
    // Texto a mostrar por clave en la lista de seleccionados
    labels: { type: Object, default: () => ({}) },
    // Alias por código de cuenta (solo para árboles de cuentas)
    aliasMap: { type: Object, default: () => ({}) },
    searchPlaceholder: { type: String, default: "Buscar…" },
    emptyText: { type: String, default: "No hay elementos seleccionados." },
});
const emit = defineEmits(["update:modelValue"]);

const tree = ref(null);
const filter = ref("");

function FilterNode(node, value) {
    return node.label?.toLowerCase().includes(value.toLowerCase());
}
</script>

<style scoped>
.tree-box {
    border: 1px solid rgba(0, 0, 0, 0.12);
    max-height: 420px;
    overflow-y: auto;
}
.selected-list {
    max-height: 420px;
    overflow-y: auto;
}
.body--dark .tree-box {
    border-color: rgba(255, 255, 255, 0.15);
}
</style>
