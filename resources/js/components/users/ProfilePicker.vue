<template>
    <div class="form-section-title">Perfiles asignados</div>
    <div class="form-section-caption q-mb-md">
        Tipos de rendición que el usuario podrá registrar.
    </div>
    <div class="row q-col-gutter-lg">
        <div class="col-xs-12 col-md-7">
            <q-input v-model="filter" outlined dense clearable placeholder="Buscar perfil…" class="input-theme q-mb-sm"
                aria-label="Buscar perfil">
                <template v-slot:prepend><q-icon name="search" /></template>
            </q-input>
            <div class="row items-center q-mb-xs">
                <q-checkbox :model-value="allState" dense size="sm" label="Seleccionar todos"
                    class="text-caption" @update:model-value="ToggleAll" />
                <q-space />
                <span class="text-caption text-grey">{{ filtered.length }} perfiles</span>
            </div>
            <q-list bordered separator class="rounded-borders picker-list">
                <q-item v-for="profile in filtered" :key="profile.label" tag="label" v-ripple dense>
                    <q-item-section side>
                        <q-checkbox :model-value="modelValue.includes(profile.label)" dense
                            @update:model-value="Toggle(profile.label)" />
                    </q-item-section>
                    <q-item-section>
                        <q-item-label class="text-body2">{{ profile.label }}</q-item-label>
                    </q-item-section>
                </q-item>
                <q-item v-if="filtered.length === 0">
                    <q-item-section class="text-grey text-caption">Sin resultados</q-item-section>
                </q-item>
            </q-list>
        </div>
        <div class="col-xs-12 col-md-5">
            <div class="form-label">Seleccionados ({{ modelValue.length }})</div>
            <div v-if="modelValue.length === 0" class="text-caption text-grey q-py-md">
                Aún no seleccionaste perfiles. Sin perfiles, el usuario no podrá crear rendiciones.
            </div>
            <div v-else class="row q-gutter-xs">
                <q-chip v-for="name in modelValue" :key="name" removable dense color="blue-1" text-color="primary"
                    icon="eva-layers-outline" @remove="Toggle(name)">
                    {{ name }}
                </q-chip>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    // Lista de perfiles disponibles: [{ label, id }]
    profiles: { type: Array, default: () => [] },
    // Nombres de los perfiles seleccionados
    modelValue: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue"]);

const filter = ref("");

const filtered = computed(() => {
    const needle = (filter.value || "").toLowerCase();
    return needle
        ? props.profiles.filter((p) => p.label?.toLowerCase().includes(needle))
        : props.profiles;
});

const allState = computed(() => {
    const selected = filtered.value.filter((p) => props.modelValue.includes(p.label)).length;
    if (selected === 0) return false;
    return selected === filtered.value.length ? true : null;
});

function Toggle(name) {
    emit(
        "update:modelValue",
        props.modelValue.includes(name)
            ? props.modelValue.filter((n) => n !== name)
            : [...props.modelValue, name]
    );
}

function ToggleAll(value) {
    const names = filtered.value.map((p) => p.label);
    emit(
        "update:modelValue",
        value
            ? [...new Set([...props.modelValue, ...names])]
            : props.modelValue.filter((n) => !names.includes(n))
    );
}
</script>

<style scoped>
.picker-list {
    max-height: 360px;
    overflow-y: auto;
}
</style>
