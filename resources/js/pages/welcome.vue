<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">

                <PageHeader :title="`Hola, ${firstName}`" :subtitle="today" icon="eva-home-outline">
                    <template #actions>
                        <q-btn data-tour="new-accountability"
                            color="primary"
                            icon="add"
                            label="Nueva Rendición"
                            no-caps
                            unelevated
                            @click="router.visit(route('panel.accountability.profiles'))"
                        />
                    </template>
                </PageHeader>

                <!-- Tareas de revisión (Administrador / Autorizador) -->
                <div data-tour="review-cards" v-if="review" class="row q-col-gutter-md q-mb-md">
                    <div class="col-xs-12 col-sm-6">
                        <q-card
                            class="card-form action-card full-height"
                            tabindex="0"
                            role="link"
                            aria-label="Ir a rendiciones por autorizar"
                            @click="router.visit(route('panel.accountability.authorization.index'))"
                            @keydown.enter="router.visit(route('panel.accountability.authorization.index'))"
                        >
                            <q-card-section class="row items-center no-wrap">
                                <q-avatar square size="40px" font-size="20px" color="orange-1" text-color="orange-9" icon="eva-checkmark-square-outline" class="stat-icon stat-icon--orange" />
                                <div class="col q-ml-md">
                                    <div class="stat-label">Por autorizar</div>
                                    <div class="stat-value stat-number">{{ review.authorization }}</div>
                                    <div class="stat-hint">
                                        {{ review.authorization ? 'Rendiciones esperando tu revisión' : 'No tienes rendiciones pendientes' }}
                                    </div>
                                </div>
                                <q-icon name="chevron_right" color="grey" size="sm" />
                            </q-card-section>
                        </q-card>
                    </div>
                    <div class="col-xs-12 col-sm-6">
                        <q-card
                            class="card-form action-card full-height"
                            tabindex="0"
                            role="link"
                            aria-label="Ir a rendiciones pendientes de exportar a SAP"
                            @click="router.visit(route('panel.accountability.authorization.pending-export'))"
                            @keydown.enter="router.visit(route('panel.accountability.authorization.pending-export'))"
                        >
                            <q-card-section class="row items-center no-wrap">
                                <q-avatar square size="40px" font-size="20px" color="blue-1" text-color="primary" icon="eva-cloud-upload-outline" class="stat-icon stat-icon--blue" />
                                <div class="col q-ml-md">
                                    <div class="stat-label">Pendientes de exportar a SAP</div>
                                    <div class="stat-value stat-number">{{ review.pending_sap }}</div>
                                    <div class="stat-hint">
                                        {{ review.pending_sap ? 'Autorizadas que aún no llegan a SAP' : 'Todo exportado correctamente' }}
                                    </div>
                                </div>
                                <q-icon name="chevron_right" color="grey" size="sm" />
                            </q-card-section>
                        </q-card>
                    </div>
                </div>

                <!-- Resumen de mis rendiciones -->
                <h2 class="section-title">Mis rendiciones</h2>
                <div data-tour="summary-cards" class="row q-col-gutter-md q-mb-md">
                    <div
                        v-for="card in summaryCards"
                        :key="card.status"
                        class="col-xs-6 col-md-3"
                    >
                        <q-card class="card-form full-height">
                            <q-card-section>
                                <div class="row items-center no-wrap q-mb-sm">
                                    <span class="stat-label ellipsis col">{{ card.label }}</span>
                                    <q-avatar
                                        square
                                        size="28px"
                                        font-size="16px"
                                        class="stat-icon"
                                        :color="statusColor(card.status) + '-1'"
                                        :text-color="statusColor(card.status) + '-9'"
                                        :icon="statusIcon(card.status)"
                                    />
                                </div>
                                <div class="stat-value stat-number">{{ card.count }}</div>
                                <div class="stat-hint stat-number">
                                    Total {{ formatAmount(card.amount) }}
                                </div>
                                <div class="stat-hint q-mt-xs gt-xs">{{ card.hint }}</div>
                            </q-card-section>
                        </q-card>
                    </div>
                </div>

                <!-- Últimas rendiciones -->
                <q-card data-tour="recent-accountabilities" class="card-form">
                    <q-card-section class="row items-center q-py-sm">
                        <h2 class="card-title col">Últimas rendiciones</h2>
                        <q-btn
                            flat
                            dense
                            no-caps
                            color="primary"
                            label="Ver todas"
                            icon-right="chevron_right"
                            @click="router.visit(route('panel.accountability.profiles'))"
                        />
                    </q-card-section>
                    <q-separator />

                    <div v-if="recent.length === 0" class="text-center q-pa-xl">
                        <q-icon name="receipt_long" size="40px" class="empty-icon" aria-hidden="true" />
                        <div class="empty-title q-mt-md">Aún no registraste rendiciones</div>
                        <div class="text-caption text-grey">Elige un perfil y crea tu primera rendición de gastos.</div>
                        <q-btn
                            color="primary"
                            label="Crear Rendición"
                            icon="add"
                            no-caps
                            unelevated
                            class="q-mt-md"
                            @click="router.visit(route('panel.accountability.profiles'))"
                        />
                    </div>

                    <q-list v-else separator>
                        <q-item
                            v-for="row in recent"
                            :key="row.id"
                            clickable
                            v-ripple
                            @click="HandleOpenAccountability(row)"
                        >
                            <q-item-section avatar>
                                <q-avatar
                                    square
                                    size="34px"
                                    font-size="18px"
                                    class="stat-icon"
                                    :color="statusColor(row.status) + '-1'"
                                    :text-color="statusColor(row.status) + '-9'"
                                    :icon="statusIcon(row.status)"
                                />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label class="ellipsis text-weight-medium">
                                    {{ row.description || 'Sin descripción' }}
                                </q-item-label>
                                <q-item-label caption class="ellipsis">
                                    #{{ row.id }} · {{ row.profile?.name }}
                                    <span v-if="row.start_date"> · {{ row.start_date }}</span>
                                </q-item-label>
                            </q-item-section>
                            <q-item-section side class="items-end">
                                <q-item-label class="text-weight-medium stat-number text-body2">
                                    {{ formatAmount(row.total) }}
                                    <span class="text-caption text-grey">{{ row.profile?.type_currency }}</span>
                                </q-item-label>
                                <q-badge :color="statusColor(row.status)" :label="statusLabel(row.status)" />
                            </q-item-section>
                        </q-item>
                    </q-list>
                </q-card>

            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import { computed } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { statusColor, statusIcon, statusLabel, formatAmount } from "@/utils/accountability";

const props = defineProps({
    title: String,
    summary: { type: Object, default: () => ({}) },
    recent: { type: Array, default: () => [] },
    review: { type: Object, default: null },
});

const page = usePage();
const firstName = computed(() => (page.props.auth.user.name || "").split(" ")[0]);

const today = new Date().toLocaleDateString("es-BO", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
});

const summaryCards = computed(() =>
    [
        { status: "Borrador", label: "Borradores", hint: "Aún no enviadas a revisión" },
        { status: "Pendiente", label: "En revisión", hint: "Esperando autorización" },
        { status: "Rechazado", label: "Rechazadas", hint: "Requieren corrección" },
        { status: "Autorizado", label: "Autorizadas", hint: "Aprobadas por tu autorizador" },
    ].map((card) => ({
        ...card,
        count: props.summary[card.status]?.count ?? 0,
        amount: props.summary[card.status]?.amount ?? 0,
    }))
);

function HandleOpenAccountability(row) {
    router.visit(
        route("panel.accountability.manage.detail.index", [row.profile_id, row.id])
    );
}
</script>
<style scoped>
.stat-number {
    font-variant-numeric: tabular-nums;
}
.section-title {
    margin: 8px 0 10px;
    font-size: 14px;
    line-height: 20px;
    font-weight: 600;
    letter-spacing: 0;
    color: var(--ui-text-highlighted);
}
.card-title {
    margin: 0;
    font-size: 15px;
    line-height: 24px;
    font-weight: 600;
    color: var(--ui-text-highlighted);
}
.stat-label {
    font-size: 13px;
    font-weight: 500;
    color: var(--ui-text-muted);
}
.stat-value {
    font-size: 24px;
    line-height: 32px;
    font-weight: 600;
    letter-spacing: -0.02em;
    color: var(--ui-text-highlighted);
}
.stat-hint {
    font-size: 12.5px;
    line-height: 18px;
    color: var(--ui-text-muted);
}
.stat-icon {
    border-radius: 8px;
}
.empty-icon {
    color: var(--ui-text-dimmed);
    background: var(--ui-bg-elevated);
    border-radius: 999px;
    padding: 12px;
    box-sizing: content-box;
}
.empty-title {
    font-size: 15px;
    font-weight: 500;
    color: var(--ui-text-highlighted);
}
.action-card {
    cursor: pointer;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}
.action-card:hover {
    border-color: var(--ui-border-accented);
    background-color: var(--ui-bg-muted);
}
.action-card:focus-visible {
    outline: 2px solid var(--q-primary);
    outline-offset: 2px;
}
.full-height {
    height: 100%;
}
@media (prefers-reduced-motion: reduce) {
    .action-card {
        transition: none;
    }
}
</style>
