<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9 col-lg-8">

                <q-card class="card-form q-pa-md q-mb-md">
                    <div class="row items-center">
                        <div class="col">
                            <div class="text-h6 title-form">Editar Ciclo de Autorización</div>
                        </div>
                        <div class="col-auto q-gutter-sm">
                            <q-btn flat color="secondary" label="Cancelar" size="12px" no-caps
                                @click="router.visit(route('panel.authorization-cycle.index'))" />
                            <q-btn color="primary" label="Guardar" size="12px" no-caps unelevated
                                @click="HandleUpdate()" />
                        </div>
                    </div>
                </q-card>

                <!-- Datos del ciclo -->
                <q-card class="card-form q-pa-md q-mb-md">
                    <div class="text-subtitle2 q-mb-md">Datos del Ciclo</div>
                    <div class="row q-col-gutter-md">
                        <div class="col-xs-12 col-sm-8">
                            <div class="form-label">Nombre <span class="text-red">*</span></div>
                            <q-input v-model="form.name" dense outlined class="input-theme" />
                            <div v-if="errors.name" class="container-error">
                                <ul v-for="(e, i) in errors.name" :key="i" class="message-error"><li>{{ e }}</li></ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div class="form-label">Estado</div>
                            <q-toggle v-model="form.is_active" label="Activo" color="positive" />
                        </div>
                        <div class="col-xs-12">
                            <div class="form-label">Descripción</div>
                            <q-input v-model="form.description" dense outlined class="input-theme" type="textarea" rows="2" />
                        </div>
                    </div>
                </q-card>

                <!-- Niveles -->
                <q-card class="card-form q-pa-md">
                    <div class="row items-center q-mb-md">
                        <div class="col text-subtitle2">Niveles de Autorización</div>
                        <div class="col-auto">
                            <q-btn color="primary" icon="add" label="Agregar Nivel" size="12px" no-caps unelevated
                                @click="HandleAddLevel()" />
                        </div>
                    </div>

                    <div v-if="form.levels.length === 0" class="text-center q-pa-xl text-grey">
                        <q-icon name="account_tree" size="48px" color="grey-4" class="q-mb-sm" />
                        <div class="text-body2">Agrega al menos un nivel de autorización</div>
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
                                                <q-input
                                                    v-model="level.name"
                                                    dense
                                                    outlined
                                                    class="input-theme"
                                                    placeholder="Nombre del nivel (ej. Jefe de Área)"
                                                />
                                                <div v-if="errors[`levels.${idx}.name`]" class="container-error">
                                                    <ul class="message-error"><li>{{ errors[`levels.${idx}.name`][0] }}</li></ul>
                                                </div>
                                            </div>
                                            <div class="col-auto q-gutter-xs">
                                                <q-btn flat round dense size="sm" icon="arrow_upward" color="grey"
                                                    :disable="idx === 0" @click="HandleMoveLevel(idx, -1)">
                                                    <q-tooltip>Subir</q-tooltip>
                                                </q-btn>
                                                <q-btn flat round dense size="sm" icon="arrow_downward" color="grey"
                                                    :disable="idx === form.levels.length - 1" @click="HandleMoveLevel(idx, 1)">
                                                    <q-tooltip>Bajar</q-tooltip>
                                                </q-btn>
                                                <q-btn flat round dense size="sm" icon="delete_outline" color="negative"
                                                    @click="HandleRemoveLevel(idx)">
                                                    <q-tooltip>Eliminar nivel</q-tooltip>
                                                </q-btn>
                                            </div>
                                        </div>
                                    </q-card-section>
                                    <q-separator />
                                    <q-card-section class="q-py-sm">
                                        <div class="form-label q-mb-xs">Autorizadores <span class="text-red">*</span></div>
                                        <q-select
                                            v-model="level.users"
                                            :options="users"
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
                                            placeholder="Seleccione autorizadores"
                                            @filter="(val, update) => update()"
                                        />
                                        <div v-if="errors[`levels.${idx}.users`]" class="container-error">
                                            <ul class="message-error"><li>{{ errors[`levels.${idx}.users`][0] }}</li></ul>
                                        </div>
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
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

defineProps({ title: String });

const page = usePage();
const users = page.props.users;
const cycleData = page.props.cycle;
const errors = ref(page.props.errors ?? {});

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
        onError: (e) => { errors.value = e; },
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
