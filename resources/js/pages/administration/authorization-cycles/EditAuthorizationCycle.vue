<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-10 col-lg-8">

                <PageHeader
                    title="Editar Ciclo de Autorización"
                    subtitle="Define en orden quién debe aprobar las rendiciones"
                    icon="eva-shuffle-2-outline"
                    :back="route('panel.authorization-cycle.index')"
                    sticky
                >
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving"
                            @click="router.visit(route('panel.authorization-cycle.index'))" />
                        <q-btn color="primary" icon="eva-checkmark-outline" label="Guardar ciclo" no-caps unelevated
                            :loading="saving" @click="HandleUpdate()" />
                    </template>
                </PageHeader>

                <!-- Datos del ciclo -->
                <q-card class="card-form q-pa-md q-mb-md">
                    <div class="form-section-title q-mb-md">Datos del ciclo</div>
                    <div class="row q-col-gutter-md">
                        <div class="col-xs-12 col-sm-8">
                            <FormField label="Nombre" required :error="errors.name">
                                <q-input v-model="form.name" dense outlined class="input-theme"
                                    placeholder="Ej: Aprobación Gerencial" />
                            </FormField>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <FormField label="Estado" hint="Solo los ciclos activos se asignan a usuarios">
                                <q-toggle v-model="form.is_active" :label="form.is_active ? 'Activo' : 'Inactivo'" color="positive" />
                            </FormField>
                        </div>
                        <div class="col-xs-12">
                            <FormField label="Descripción" :error="errors.description">
                                <q-input v-model="form.description" dense outlined class="input-theme" type="textarea" rows="2" />
                            </FormField>
                        </div>
                    </div>
                </q-card>

                <!-- Niveles -->
                <q-card class="card-form q-pa-md">
                    <div class="row items-center q-mb-md">
                        <div class="col">
                            <div class="form-section-title">Niveles de autorización</div>
                            <div class="form-section-caption">La rendición pasa por cada nivel en orden; basta con que apruebe uno de sus autorizadores.</div>
                        </div>
                        <div class="col-auto">
                            <q-btn outline color="primary" icon="add" label="Agregar nivel" no-caps
                                @click="HandleAddLevel()" />
                        </div>
                    </div>

                    <div v-if="form.levels.length === 0" class="text-center q-pa-xl text-grey"
                        :class="{ 'form-field--error': errors.levels }">
                        <q-icon name="account_tree" size="48px" :color="errors.levels ? 'negative' : 'grey-4'" class="q-mb-sm" />
                        <div class="text-body2" :class="{ 'text-negative': errors.levels }">Agrega al menos un nivel de autorización</div>
                        <q-btn flat color="primary" icon="add" label="Agregar primer nivel" no-caps class="q-mt-sm"
                            @click="HandleAddLevel()" />
                    </div>

                    <div v-else>
                        <div
                            v-for="(level, idx) in form.levels"
                            :key="idx"
                            class="level-row row items-start no-wrap"
                        >
                            <!-- Spine -->
                            <div class="level-spine col-auto column items-center q-mr-md">
                                <q-avatar size="32px" color="primary" text-color="white" class="text-caption text-weight-bold">
                                    {{ idx + 1 }}
                                </q-avatar>
                                <div v-if="idx < form.levels.length - 1" class="level-connector" />
                            </div>

                            <!-- Card -->
                            <div class="col q-pb-md">
                                <q-card flat bordered>
                                    <q-card-section class="q-py-sm">
                                        <div class="row items-center q-col-gutter-sm no-wrap">
                                            <div class="col">
                                                <FormField :error="errors[`levels.${idx}.name`]">
                                                    <q-input
                                                        v-model="level.name"
                                                        dense
                                                        outlined
                                                        class="input-theme"
                                                        placeholder="Nombre del nivel (ej. Jefe de Área)"
                                                        :aria-label="`Nombre del nivel ${idx + 1}`"
                                                    />
                                                </FormField>
                                            </div>
                                            <div class="col-auto q-gutter-xs">
                                                <q-btn flat round dense size="sm" icon="arrow_upward" color="grey"
                                                    :disable="idx === 0" aria-label="Subir nivel" @click="HandleMoveLevel(idx, -1)">
                                                    <q-tooltip>Subir</q-tooltip>
                                                </q-btn>
                                                <q-btn flat round dense size="sm" icon="arrow_downward" color="grey"
                                                    :disable="idx === form.levels.length - 1" aria-label="Bajar nivel" @click="HandleMoveLevel(idx, 1)">
                                                    <q-tooltip>Bajar</q-tooltip>
                                                </q-btn>
                                                <q-btn flat round dense size="sm" icon="delete_outline" color="negative"
                                                    aria-label="Eliminar nivel" @click="HandleRemoveLevel(idx)">
                                                    <q-tooltip>Eliminar nivel</q-tooltip>
                                                </q-btn>
                                            </div>
                                        </div>
                                    </q-card-section>
                                    <q-separator />
                                    <q-card-section class="q-py-sm">
                                        <FormField label="Autorizadores" required :error="errors[`levels.${idx}.users`]">
                                            <q-select
                                                v-model="level.users"
                                                :options="filteredUsers"
                                                option-value="id"
                                                option-label="name"
                                                emit-value
                                                map-options
                                                multiple
                                                dense
                                                outlined
                                                class="input-theme"
                                                use-chips
                                                use-input
                                                input-debounce="0"
                                                placeholder="Buscar y seleccionar autorizadores…"
                                                @filter="HandleFilterUsers"
                                            >
                                                <template v-slot:no-option>
                                                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                                                </template>
                                            </q-select>
                                        </FormField>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </div>
                    </div>
                </q-card>

            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import FormField from "@/components/FormField.vue";
import { ref } from "vue";
import { scrollToFirstError } from "@/utils/forms";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

defineProps({ title: String });

const page = usePage();
const users = page.props.users;
const cycleData = page.props.cycle;
const errors = ref(page.props.errors ?? {});
const saving = ref(false);
const filteredUsers = ref(users);

function HandleFilterUsers(val, update) {
    update(() => {
        const needle = val.toLowerCase();
        filteredUsers.value = needle
            ? users.filter((u) => u.name.toLowerCase().includes(needle))
            : users;
    });
}

const form = ref({
    id: cycleData.id,
    name: cycleData.name,
    description: cycleData.description ?? "",
    is_active: cycleData.is_active,
    levels: cycleData.levels.map((l) => ({
        name: l.name,
        users: l.users.map((u) => u.id),
    })),
});

function HandleAddLevel() {
    form.value.levels.push({ name: "", users: [] });
}

function HandleRemoveLevel(idx) {
    form.value.levels.splice(idx, 1);
}

function HandleMoveLevel(idx, dir) {
    const arr = form.value.levels;
    const target = idx + dir;
    if (target < 0 || target >= arr.length) return;
    [arr[idx], arr[target]] = [arr[target], arr[idx]];
}

function HandleUpdate() {
    router.put(route("panel.authorization-cycle.update"), form.value, {
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
        onError: (e) => {
            errors.value = e;
            scrollToFirstError();
        },
    });
}
</script>

<style scoped>
.level-spine {
    min-width: 32px;
    padding-top: 4px;
}
.level-connector {
    width: 2px;
    flex: 1;
    min-height: 24px;
    background: currentColor;
    opacity: 0.15;
    margin-top: 4px;
}
</style>
