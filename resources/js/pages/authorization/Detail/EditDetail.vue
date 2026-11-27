<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">

                <PageHeader title="Editar gasto" :subtitle="`Rendición #${page.props.accountability.id}`"
                    icon="eva-edit-2-outline" :back="detailUrl" sticky>
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving" @click="HandleCancel" />
                        <q-btn data-tour="save-expense" color="primary" icon="eva-checkmark-outline" label="Guardar cambios" no-caps unelevated
                            :loading="saving" :disable="!hasDocument" @click="HandleUpdateForm()" />
                    </template>
                </PageHeader>

                <DocumentFormFields :form="form" :errors="errors" :documents="page.props.documents"
                    :document="document" :accounts="page.props.accounts" :projects="page.props.projects"
                    :distribution="page.props.distribution" :accountability="page.props.accountability"
                    :currency="page.props.profile.type_currency" :exclude-id="page.props.data.id" />

                <!-- Artículos (factura con ítems) -->
                <ExpenseItems v-if="document.items_status" :form="form" :errors="errors" />

                <!-- Reparto por líneas -->
                <ExpenseDistribution v-if="hasDocument && !document.items_status" :form="form"
                    :distribution="page.props.distribution" :accounts="page.props.accounts" :errors="errors" />

            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import DocumentFormFields from "@/components/accountability/DocumentFormFields.vue";
import ExpenseDistribution from "@/components/accountability/ExpenseDistribution.vue";
import ExpenseItems from "@/components/accountability/ExpenseItems.vue";
import { useDirtyGuard } from "@/composables/useDirtyGuard";
import { scrollToFirstError, firstError } from "@/utils/forms";
import { ref, computed, onMounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

defineProps({
    title: String,
    errors: Object,
});
const $q = useQuasar();
const page = usePage();

// Al volver (cancelar o atrás) el detalle se ubica en este mismo documento
const detailUrl = route("panel.accountability.authorization.detail.index", {
    id: page.props.accountability.id,
    focus: page.props.data.id,
});

const form = ref(page.props.data);
form.value.document_id = parseInt(form.value.document_id);

const document = computed(() => page.props.documents.find((d) => d.id == form.value.document_id) ?? {});
const hasDocument = computed(() => Object.keys(document.value).length > 0);
const saving = ref(false);

const { confirmLeave } = useDirtyGuard(form);

onMounted(() => {
    // Los centros de costo guardados se enlazan con la opción correspondiente del select
    const distFields = [
        { field: "distribution_rule_one", dim: 1 },
        { field: "distribution_rule_second", dim: 2 },
        { field: "distribution_rule_three", dim: 3 },
        { field: "distribution_rule_four", dim: 4 },
        { field: "distribution_rule_five", dim: 5 },
    ];
    distFields.forEach(({ field, dim }) => {
        const val = form.value[field];
        const options = page.props.distribution[dim];
        if (val && typeof val === "object" && options) {
            const code = val.OcrCode || val.PrcCode;
            form.value[field] = code ? options.find((o) => (o.OcrCode || o.PrcCode) === code) || null : null;
        } else if (!val || (typeof val === "object" && Object.keys(val).length === 0)) {
            form.value[field] = null;
        }
    });
});

function HandleCancel() {
    confirmLeave(() => router.visit(detailUrl));
}

function HandleUpdateForm() {
    router.post(
        route("panel.accountability.authorization.detail.update", [page.props.accountability.id]),
        form.value,
        {
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
            // Ante un error se conserva lo cargado en el formulario
            preserveState: "errors",
            onError: (errors) => {
                if (errors.general) $q.notify({ type: "negative", message: firstError(errors.general) });
                scrollToFirstError();
            },
        }
    );
}
</script>
