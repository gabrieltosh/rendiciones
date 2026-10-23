<template>
    <div class="row items-center q-mb-xs">
        <div class="col">
            <div class="form-section-title">Ciclo de autorización</div>
            <div class="form-section-caption">
                Define los niveles de aprobación en orden. Cada nivel puede tener uno o más autorizadores.
            </div>
        </div>
        <div class="col-auto">
            <q-btn outline color="primary" icon="add" label="Crear nuevo ciclo" no-caps @click="emit('create')" />
        </div>
    </div>

    <div v-if="cycles.length === 0" class="text-center q-pa-xl text-grey">
        <q-icon name="account_tree" size="48px" color="grey-4" />
        <div class="q-mt-sm">No hay ciclos activos. Crea uno con el botón de arriba.</div>
    </div>
    <div v-else class="q-mt-md" style="max-width: 560px;">
        <q-select :model-value="modelValue" :options="cycles" option-value="value" option-label="label" emit-value
            map-options dense outlined class="input-theme" clearable placeholder="Seleccionar ciclo…"
            aria-label="Ciclo de autorización" @update:model-value="emit('update:modelValue', $event)" />

        <div v-if="selected" class="q-mt-lg">
            <div class="text-caption text-grey-6 text-uppercase q-mb-sm" style="letter-spacing: 0.08em; font-weight: 600;">
                Niveles de aprobación
            </div>
            <q-card flat bordered class="rounded-borders overflow-hidden">
                <div v-for="(level, idx) in selected.levels" :key="level.order">
                    <q-item class="q-py-sm">
                        <q-item-section avatar>
                            <q-avatar size="32px" color="primary" text-color="white" class="text-caption text-weight-bold">
                                {{ level.order }}
                            </q-avatar>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label class="text-weight-medium">{{ level.name }}</q-item-label>
                            <q-item-label caption>
                                <div class="row q-gutter-xs q-mt-xs">
                                    <q-chip v-for="u in level.users" :key="u" dense size="sm" color="blue-1"
                                        text-color="primary" icon="person" class="q-ma-none">
                                        {{ u }}
                                    </q-chip>
                                    <span v-if="level.users.length === 0" class="text-caption text-grey-5 self-center">
                                        Sin autorizadores asignados
                                    </span>
                                </div>
                            </q-item-label>
                        </q-item-section>
                    </q-item>
                    <q-separator v-if="idx < selected.levels.length - 1" inset="item" />
                </div>
            </q-card>
        </div>
        <div v-else class="text-caption text-grey q-mt-sm">
            Selecciona un ciclo para definir quién aprobará las rendiciones de este usuario.
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    // [{ value, label, levels: [{ order, name, users: [nombre] }] }]
    cycles: { type: Array, default: () => [] },
    modelValue: [Number, String],
});
const emit = defineEmits(["update:modelValue", "create"]);

const selected = computed(() => props.cycles.find((c) => c.value === props.modelValue) ?? null);
</script>
