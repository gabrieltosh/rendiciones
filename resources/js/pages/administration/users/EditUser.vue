<template>

    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">
                <q-stepper v-model="step" header-nav ref="stepper" color="primary" animated flat alternative-labels
                    class="backgroup-theme">
                    <q-step :name="1" title="Datos Usuario" icon="settings" :done="step > 1"
                        :error="Object.keys(errors).length > 0 ? true : false">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">Crear Usuario</h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                        router.visit(
                                            route('panel.user.index')
                                        )
                                        " flat />
                                    <q-btn @click="$refs.stepper.next()" outline color="primary" no-caps size="12px"
                                        label="Continuar" />
                                    <q-btn color="primary" label="Actualizar" size="12px" no-caps
                                        @click="HandleUpdateForm()" />
                                </div>
                            </div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Nombres <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.name" dense outlined class="input-theme" />
                                    <div v-if="errors.name" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.name" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Username <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.username" dense outlined class="input-theme" />
                                    <div v-if="errors.username" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.username" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Correo Electronico
                                        <span class="text-red">*</span>
                                    </div>
                                    <q-input v-model="form.email" dense outlined class="input-theme" type="email" />
                                    <div v-if="errors.email" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.email" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Contraseña
                                    </div>
                                    <q-input v-model="form.password" dense outlined class="input-theme"
                                        type="password" />
                                    <div v-if="errors.password" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.password" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Tipo <span class="text-red">*</span>
                                    </div>
                                    <q-select v-model="form.type" :options="['Administrador', 'Autorizador', 'Usuario']" dense outlined
                                        class="input-theme" type="password" />
                                    <div v-if="errors.type" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.type" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Estado <span class="text-red">*</span>
                                    </div>
                                    <q-select v-model="form.status" :options="[
                                        'Activo',
                                        'PreActivo',
                                        'Bloqueado',
                                    ]" dense outlined class="input-theme" type="password" />
                                    <div v-if="errors.status" class="container-error">
                                        <ul v-for="(
                                                error, index
                                            ) in errors.status" :key="index" class="message-error">
                                            <li>{{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Área
                                    </div>
                                    <q-select
                                        v-model="form.area_id"
                                        :options="options.areas"
                                        option-value="value"
                                        option-label="label"
                                        emit-value
                                        map-options
                                        dense
                                        outlined
                                        class="input-theme"
                                        clearable
                                    />
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step :name="2" title="Perfiles" icon="eva-person-outline" :done="step > 2">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row q-ma-sm">
                                        <div class="col-sm-6">
                                            <h5 class="title-form">
                                                Asignación de Perfiles
                                            </h5>
                                        </div>
                                        <div class="col-sm-6 text-right q-gutter-sm">
                                            <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                                router.visit(
                                                    route(
                                                        'panel.user.index'
                                                    )
                                                )
                                                " flat />
                                            <q-btn flat color="primary" @click="
                                                $refs.stepper.previous()
                                                " label="Atras" no-caps size="12px" class="q-ml-sm" />
                                            <q-btn @click="$refs.stepper.next()" color="primary" no-caps size="12px"
                                                label="Continuar" outline />
                                            <q-btn color="primary" label="Actualizar" size="12px" no-caps
                                                @click="HandleUpdateForm()" />
                                        </div>
                                    </div>
                                    <q-separator />
                                </div>
                                <div class="col-sm-6">
                                    <h5 class="title-tree text-center">
                                        Lista de Perfiles
                                    </h5>
                                    <q-input outlined dense color="primary" placeholder="Buscar..." v-model="filter"
                                        class="input-theme"></q-input>
                                    <q-tree :nodes="options.profiles" node-key="label" tick-strategy="leaf"
                                        v-model:ticked="form.profiles" :filter="filter"
                                        :filter-method="HandleFilterAccounts">
                                        <template v-slot:default-header="prop">
                                            <div class="tree-label">
                                                {{ prop.node.label }}
                                            </div>
                                        </template>
                                    </q-tree>
                                </div>
                                <q-separator spaced inset vertical />
                                <div class="col-sm-5">
                                    <h5 class="title-tree text-center">
                                        Perfiles Seleccionados
                                    </h5>
                                    <ul class="q-ma-none">
                                        <div v-for="tick in form.profiles" :key="`ticked-${tick}`" class="tree-label">
                                            <li>{{ tick }}</li>
                                        </div>
                                    </ul>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step :name="3" title="Autorización" icon="eva-person-outline" :done="step > 3">
                        <q-card class="q-px-lg q-py-md q-ma-sm card-form q-mt-md">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row q-ma-sm">
                                        <div class="col-sm-6">
                                            <h5 class="title-form">
                                                Asignación de Autorizadores
                                            </h5>
                                        </div>
                                        <div class="col-sm-6 text-right q-gutter-sm">
                                            <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                                router.visit(
                                                    route(
                                                        'panel.user.index'
                                                    )
                                                )
                                                " flat />
                                            <q-btn flat color="primary" @click="
                                                $refs.stepper.previous()
                                                " label="Atras" no-caps size="12px" class="q-ml-sm" />
                                            <q-btn @click="$refs.stepper.next()" outline color="primary" no-caps
                                                size="12px" label="Continuar" />
                                            <q-btn color="primary" label="Actualizar" size="12px" no-caps
                                                @click="HandleUpdateForm()" />
                                        </div>
                                    </div>
                                    <q-separator />
                                </div>
                                <div class="col-12 q-mt-md">
                                    <div class="row items-center q-mb-xs">
                                        <div class="form-label q-mb-none">Ciclo de Autorización</div>
                                        <q-btn flat dense no-caps size="sm" color="primary" icon="add"
                                            label="Crear nuevo ciclo" class="q-ml-sm"
                                            @click="newCycleDialog = true" />
                                    </div>
                                    <div class="text-caption text-grey q-mb-sm">
                                        El ciclo define los niveles de aprobación en orden. Cada nivel puede tener uno o más autorizadores.
                                    </div>
                                    <div v-if="options.cycles.length === 0" class="text-caption text-grey-6 q-py-sm">
                                        No hay ciclos activos. Crea uno nuevo con el botón de arriba.
                                    </div>
                                    <q-select
                                        v-else
                                        v-model="form.cycle_id"
                                        :options="options.cycles"
                                        option-value="value"
                                        option-label="label"
                                        emit-value
                                        map-options
                                        dense
                                        outlined
                                        class="input-theme"
                                        clearable
                                        placeholder="Seleccionar ciclo…"
                                        style="max-width: 460px;"
                                    />
                                    <div v-if="selectedCycle" class="q-mt-lg" style="max-width: 520px;">
                                        <div class="text-caption text-grey-6 text-uppercase q-mb-sm"
                                            style="letter-spacing: 0.08em; font-weight: 600;">
                                            Niveles de aprobación
                                        </div>
                                        <q-card flat bordered class="rounded-borders overflow-hidden">
                                            <div v-for="(level, idx) in selectedCycle.levels" :key="level.order">
                                                <q-item class="q-py-sm">
                                                    <q-item-section avatar>
                                                        <q-avatar size="32px" color="primary" text-color="white"
                                                            class="text-caption text-weight-bold">
                                                            {{ level.order }}
                                                        </q-avatar>
                                                    </q-item-section>
                                                    <q-item-section>
                                                        <q-item-label class="text-weight-medium">{{ level.name }}</q-item-label>
                                                        <q-item-label caption>
                                                            <div class="row q-gutter-xs q-mt-xs">
                                                                <q-chip v-for="u in level.users" :key="u"
                                                                    dense size="sm" color="blue-1" text-color="primary"
                                                                    icon="person" class="q-ma-none">
                                                                    {{ u }}
                                                                </q-chip>
                                                                <span v-if="level.users.length === 0"
                                                                    class="text-caption text-grey-5 self-center">Sin autorizadores asignados</span>
                                                            </div>
                                                        </q-item-label>
                                                    </q-item-section>
                                                    <q-item-section side>
                                                        <q-badge color="grey-3" text-color="grey-7" class="text-caption">
                                                            Nivel {{ level.order }}
                                                        </q-badge>
                                                    </q-item-section>
                                                </q-item>
                                                <q-separator v-if="idx < selectedCycle.levels.length - 1" inset="item" />
                                            </div>
                                        </q-card>
                                    </div>
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                    <q-step :name="4" title="Normas Reparto" icon="eva-file-text-outline">
                        <q-card class="q-pa-lg card-form q-mt-md">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h5 class="title-form">
                                        Normas de Reparto
                                    </h5>
                                </div>
                                <div class="col-sm-6 text-right q-gutter-sm">
                                    <q-btn color="secondary" label="Cancelar" size="12px" no-caps @click="
                                        router.visit(
                                            route('panel.user.index')
                                        )
                                        " flat />
                                    <q-btn flat color="primary" @click="$refs.stepper.previous()" label="Atras" no-caps
                                        size="12px" class="q-ml-sm" />
                                    <q-btn color="primary" label="Actualizar" size="12px" no-caps
                                        @click="HandleUpdateForm()" />
                                </div>
                            </div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Centro de Costo 1
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[1]"
                                        v-model="form.distribution_rule_one" option-value="PrcCode" option-label="Name"
                                        emit-value clearable map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Centro de Costo 2
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[2]"
                                        v-model="form.distribution_rule_second" option-value="PrcCode"
                                        option-label="Name" clearable emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Centro de Costo 3
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[3]"
                                        v-model="form.distribution_rule_three" option-value="PrcCode"
                                        option-label="Name" clearable emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Centro de Costo 4
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[4]"
                                        v-model="form.distribution_rule_four" option-value="PrcCode" option-label="Name"
                                        clearable emit-value map-options />
                                </div>
                                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                    <div class="form-label" for="device_name">
                                        Centro de Costo 5
                                    </div>
                                    <q-select class="input-theme" dense outlined :options="options.distribution[5]"
                                        v-model="form.distribution_rule_five" option-value="PrcCode" option-label="Name"
                                        clearable emit-value map-options />
                                </div>
                            </div>
                        </q-card>
                    </q-step>
                </q-stepper>
            </div>
        </div>
        <!-- Dialog: crear ciclo rápido -->
        <q-dialog v-model="newCycleDialog" persistent>
            <q-card style="min-width: 480px; max-width: 96vw;">
                <q-card-section class="row items-center q-pb-none">
                    <div class="text-h6">Nuevo ciclo de autorización</div>
                    <q-space />
                    <q-btn icon="close" flat round dense v-close-popup aria-label="Cerrar" />
                </q-card-section>
                <q-card-section>
                    <div class="form-label">Nombre <span class="text-red">*</span></div>
                    <q-input v-model="newCycle.name" dense outlined class="input-theme q-mb-md"
                        placeholder="Ej: Aprobación Gerencial" />

                    <div class="row items-center q-mb-sm">
                        <span class="form-label q-mb-none">Niveles</span>
                        <q-btn flat dense no-caps size="sm" color="primary" icon="add" label="Agregar nivel"
                            class="q-ml-sm" @click="addNewLevel" />
                    </div>

                    <div v-for="(level, idx) in newCycle.levels" :key="idx"
                        class="q-pa-sm q-mb-sm rounded-borders"
                        style="border: 1px solid rgba(0,0,0,0.12);">
                        <div class="row items-center q-mb-xs">
                            <q-badge color="primary" rounded class="q-mr-sm">{{ idx + 1 }}</q-badge>
                            <q-input v-model="level.name" dense outlined class="input-theme col"
                                :placeholder="`Nombre del nivel ${idx + 1}`" />
                            <q-btn flat round dense icon="delete" color="negative" class="q-ml-xs"
                                :disable="newCycle.levels.length === 1"
                                :aria-label="`Eliminar nivel ${idx + 1}`"
                                @click="newCycle.levels.splice(idx, 1)" />
                        </div>
                        <div class="form-label text-caption q-mb-xs">Autorizadores <span class="text-red">*</span></div>
                        <q-select
                            v-model="level.user_ids"
                            :options="options.usersForCycle"
                            option-value="value"
                            option-label="label"
                            emit-value
                            map-options
                            multiple
                            use-chips
                            dense
                            outlined
                            class="input-theme"
                            placeholder="Seleccionar autorizadores…"
                        />
                    </div>
                </q-card-section>
                <q-card-actions align="right" class="q-px-md q-pb-md">
                    <q-btn flat label="Cancelar" no-caps v-close-popup />
                    <q-btn color="primary" label="Crear ciclo" no-caps :loading="savingCycle"
                        @click="HandleSaveQuickCycle" />
                </q-card-actions>
            </q-card>
        </q-dialog>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, computed, watch, onMounted } from "vue";
import { Head, usePage, useForm, router } from "@inertiajs/vue3";
import axios from "axios";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

defineProps({
    title: String,
    errors: Object,
    user: Object,
});
const $q = useQuasar();
const page = usePage();
let message = ref(page.props.flash.message);
let type = ref(page.props.flash.type);
const options = ref({
    profiles: page.props.profiles,
    distribution: page.props.distribution,
    users: page.props.users,
    areas: page.props.areas,
    cycles: page.props.cycles ?? [],
    usersForCycle: page.props.usersForCycle ?? [],
});
const filter = ref("");
const form = ref(page.props.user);
const selectedCycle = computed(() =>
    options.value.cycles.find(c => c.value === form.value.cycle_id) ?? null
);

const newCycleDialog = ref(false);
const savingCycle = ref(false);
const newCycle = ref({ name: '', levels: [{ name: '', user_ids: [] }] });

function addNewLevel() {
    newCycle.value.levels.push({ name: '', user_ids: [] });
}

async function HandleSaveQuickCycle() {
    savingCycle.value = true;
    try {
        const { data } = await axios.post(route('panel.authorization-cycle.quick-store'), newCycle.value);
        options.value.cycles.push(data);
        form.value.cycle_id = data.value;
        newCycleDialog.value = false;
        newCycle.value = { name: '', levels: [{ name: '', user_ids: [] }] };
        $q.notify({ type: 'positive', message: 'Ciclo creado y seleccionado' });
    } catch (e) {
        const msg = e.response?.data?.message ?? e.response?.data?.errors
            ? Object.values(e.response.data.errors).flat().join(' ')
            : 'Error al crear el ciclo';
        $q.notify({ type: 'negative', message: msg });
    } finally {
        savingCycle.value = false;
    }
}

let step = ref(1);

function HandleUpdateForm() {
    router.put(route("panel.user.update"), form.value, {
        onSuccess: () => {
            message.value = page.props.flash.message;
            type.value = page.props.flash.type;
            $q.notify({
                type: type.value,
                message: message.value,
            });
        },
    });
}
function HandleFilterAccounts(node, filter) {
    const filt = filter.toLowerCase();
    return node.label && node.label.toLowerCase().indexOf(filt) > -1;
}
</script>
