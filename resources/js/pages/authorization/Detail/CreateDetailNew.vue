<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">

                <PageHeader title="Nuevo gasto" :subtitle="`Rendición #${page.props.accountability.id}`"
                    icon="eva-file-add-outline" :back="detailUrl" sticky>
                    <template #actions>
                        <q-btn flat color="secondary" label="Cancelar" no-caps :disable="saving" @click="HandleCancel" />
                        <q-btn data-tour="save-expense" color="primary" icon="eva-checkmark-outline" label="Guardar gasto" no-caps unelevated
                            :loading="saving" :disable="!hasDocument" @click="HandleStoreForm()" />
                    </template>
                </PageHeader>

                <DocumentFormFields :form="form" :errors="errors" :documents="page.props.documents"
                    :document="document" :accounts="page.props.accounts" :projects="page.props.projects"
                    :distribution="page.props.distribution" :accountability="page.props.accountability"
                    :currency="page.props.profile.type_currency" distribution-emit-value can-scan
                    :has-invoice="!!facturaOriginal" @scan="showQrScanner = true"
                    @view-invoice="showFacturaDialog = true" />

                <!-- Artículos (factura con ítems) -->
                <ExpenseItems v-if="document.items_status" :form="form" :errors="errors"
                    :siat-detail="facturaOriginal?.listaDetalle" />

                <!-- Reparto por líneas -->
                <ExpenseDistribution v-if="hasDocument && !document.items_status" :form="form"
                    :distribution="page.props.distribution" :accounts="page.props.accounts" :errors="errors" />

            </div>
        </div>

        <QrScannerDialog v-model="showQrScanner" :period-start="page.props.accountability.start_date"
            :period-end="page.props.accountability.end_date" @apply="HandleApplyInvoice" />
        <SiatInvoiceDialog v-model="showFacturaDialog" :factura="facturaOriginal" />
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import DocumentFormFields from "@/components/accountability/DocumentFormFields.vue";
import ExpenseDistribution from "@/components/accountability/ExpenseDistribution.vue";
import ExpenseItems from "@/components/accountability/ExpenseItems.vue";
import QrScannerDialog from "@/components/accountability/QrScannerDialog.vue";
import SiatInvoiceDialog from "@/components/accountability/SiatInvoiceDialog.vue";
import { useDirtyGuard } from "@/composables/useDirtyGuard";
import { scrollToFirstError, firstError } from "@/utils/forms";
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

defineProps({
    title: String,
    errors: Object,
});

const $q = useQuasar();
const page = usePage();
const user = page.props.auth.user;

const detailUrl = route("panel.accountability.authorization.detail.index", page.props.accountability.id);

const form = ref({
    account: null,
    account_name: null,
    date: null,
    document_id: null,
    document_number: null,
    authorization_number: null,
    cuf: null,
    control_code: null,
    supplier_code: null,
    business_name: null,
    nit: null,
    concept: null,
    amount: null,
    discount: null,
    excento: null,
    rate: null,
    gift_card: null,
    rate_zero: null,
    ice: null,
    project_code: null,
    distribution_rule_one: user?.distribution_rule_one ?? null,
    distribution_rule_second: user?.distribution_rule_second ?? null,
    distribution_rule_three: user?.distribution_rule_three ?? null,
    distribution_rule_four: user?.distribution_rule_four ?? null,
    distribution_rule_five: user?.distribution_rule_five ?? null,
    splits: {},
    lines: [],
    items: [],
    field: [],
});

const document = computed(() => page.props.documents.find((d) => d.id == form.value.document_id) ?? {});
const hasDocument = computed(() => Object.keys(document.value).length > 0);

const showQrScanner = ref(false);
const showFacturaDialog = ref(false);
const facturaOriginal = ref(null);
const saving = ref(false);

const { confirmLeave } = useDirtyGuard(form);

function HandleCancel() {
    confirmLeave(() => router.visit(detailUrl));
}

function HandleApplyInvoice({ data, factura }) {
    Object.entries(data).forEach(([key, value]) => {
        if (value !== null && value !== undefined && key in form.value) form.value[key] = value;
    });
    facturaOriginal.value = factura;
    $q.notify({ type: "positive", message: "Datos de la factura aplicados correctamente" });
}

function HandleStoreForm() {
    router.post(
        route("panel.accountability.authorization.detail.store", [page.props.accountability.id]),
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
