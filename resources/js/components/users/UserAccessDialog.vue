<template>
    <q-dialog :model-value="modelValue" :persistent="saving" @update:model-value="emit('update:modelValue', $event)">
        <q-card style="width: 860px; max-width: 96vw;">
            <q-card-section class="row items-center q-pb-none">
                <div class="col">
                    <div class="text-h6">Perfiles y ciclo de autorización</div>
                    <div class="text-caption text-grey">{{ user?.name }}<span v-if="user?.username"> · {{ user.username }}</span></div>
                </div>
                <q-btn icon="close" flat round dense v-close-popup aria-label="Cerrar" :disable="saving" />
            </q-card-section>

            <q-tabs v-model="tab" dense no-caps align="left" active-color="primary" indicator-color="primary"
                narrow-indicator class="q-px-md q-pt-sm">
                <q-tab name="profiles" icon="eva-layers-outline" :label="`Perfiles (${form.profiles.length})`" />
                <q-tab name="cycle" icon="eva-shuffle-2-outline" label="Ciclo de autorización" />
            </q-tabs>
            <q-separator />

            <q-card-section style="max-height: 65vh; overflow-y: auto;">
                <ProfilePicker v-if="tab === 'profiles'" v-model="form.profiles" :profiles="profiles" />
                <CyclePicker v-else v-model="form.cycle_id" :cycles="cycleOptions" @create="newCycleDialog = true" />
            </q-card-section>

            <q-card-actions align="right" class="q-px-md q-pb-md">
                <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving" v-close-popup />
                <q-btn color="primary" icon="eva-checkmark-outline" label="Guardar" no-caps unelevated :loading="saving"
                    @click="HandleSave" />
            </q-card-actions>
        </q-card>
        <QuickCycleDialog v-model="newCycleDialog" :users="usersForCycle" @created="HandleCycleCreated" />
    </q-dialog>
</template>

<script setup>
// Asignación rápida de perfiles y ciclo desde la lista de usuarios, sin abrir su edición
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import ProfilePicker from "@/components/users/ProfilePicker.vue";
import CyclePicker from "@/components/users/CyclePicker.vue";
import QuickCycleDialog from "@/components/users/QuickCycleDialog.vue";

const props = defineProps({
    modelValue: Boolean,
    // Usuario de la fila: { id, name, username, profiles: [nombres], cycle_id }
    user: { type: Object, default: null },
    profiles: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    usersForCycle: { type: Array, default: () => [] },
    // Pestaña con la que se abre: 'profiles' | 'cycle'
    initialTab: { type: String, default: "profiles" },
});
const emit = defineEmits(["update:modelValue", "saved"]);

const $q = useQuasar();
const tab = ref(props.initialTab);
const form = ref({ profiles: [], cycle_id: null });
const cycleOptions = ref([...props.cycles]);
const newCycleDialog = ref(false);
const saving = ref(false);

watch(
    () => props.modelValue,
    (open) => {
        if (!open || !props.user) return;
        tab.value = props.initialTab;
        cycleOptions.value = [...props.cycles];
        form.value = { profiles: [...(props.user.profiles ?? [])], cycle_id: props.user.cycle_id ?? null };
    }
);

function HandleCycleCreated(cycle) {
    cycleOptions.value.push(cycle);
    form.value.cycle_id = cycle.value;
}

function HandleSave() {
    router.put(route("panel.user.access", props.user.id), form.value, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
        onSuccess: () => {
            emit("update:modelValue", false);
            emit("saved", props.user.id);
        },
        onError: (errors) => {
            $q.notify({ type: "negative", message: Object.values(errors).flat()[0] ?? "No se pudo guardar." });
        },
    });
}
</script>
