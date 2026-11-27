<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-10 col-lg-9">

                <!-- Header -->
                <q-card class="q-pa-md card-form q-mb-md">
                    <div class="row items-center q-col-gutter-sm">
                        <div class="col">
                            <div class="row items-center q-gutter-sm">
                                <div class="text-subtitle1 text-weight-medium">
                                    {{ accountability.description }}
                                </div>
                                <q-badge
                                    :color="statusColor(accountability.status)"
                                    :label="statusLabel(accountability.status)"
                                    class="q-pa-xs"
                                />
                            </div>
                            <div class="text-caption text-grey q-mt-xs">
                                {{ accountability.employee_name }}
                                &middot; Rendición #{{ accountability.id }}
                                <span v-if="profile?.name"> &middot; {{ profile.name }}</span>
                            </div>
                        </div>
                        <div class="col-auto q-gutter-sm">
                            <q-btn
                                color="secondary"
                                label="Volver"
                                size="12px"
                                no-caps
                                flat
                                aria-label="Volver"
                                :disable="!!busy"
                                @click="HandleBack"
                            />
                            <q-btn data-tour="print-accountability"
                                color="primary"
                                icon="print"
                                label="Imprimir"
                                size="12px"
                                no-caps
                                flat
                                aria-label="Imprimir reporte de rendición"
                                @click="HandleGetReport()"
                            />
                            <q-btn data-tour="annul-button"
                                v-if="can_annul"
                                color="grey"
                                label="Anular"
                                size="12px"
                                no-caps
                                unelevated
                                aria-label="Anular rendición"
                                :loading="busy === 'annul'"
                                :disable="!!busy"
                                @click="HandleCancelStatus"
                            />
                            <template v-if="can_authorize">
                                <q-btn data-tour="reject-button"
                                    color="negative"
                                    label="Rechazar"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    aria-label="Rechazar rendición"
                                    :loading="busy === 'reject'"
                                    :disable="!!busy"
                                    @click="HandleRejectStatus"
                                />
                                <q-btn data-tour="authorize-button"
                                    color="positive"
                                    icon="check"
                                    :label="authorizeLabel"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    :aria-label="authorizeLabel"
                                    :loading="busy === 'authorize'"
                                    :disable="!!busy"
                                    @click="HandleAuthorizeStatus"
                                />
                            </template>
                        </div>
                    </div>
                </q-card>

                <!-- Status Banner -->
                <q-banner data-tour="status-banner"
                    v-if="statusMessage"
                    :class="['card-form q-mb-md text-white', 'bg-' + statusBannerColor]"
                    dense
                    rounded
                >
                    <template v-slot:avatar>
                        <q-icon :name="statusIcon(accountability.status)" color="white" />
                    </template>
                    {{ statusMessage }}
                    <div v-if="status_change && ['Rechazado', 'Anulado'].includes(accountability.status)" class="text-caption">
                        Por {{ status_change.user }} el {{ status_change.created_at }}
                    </div>
                    <template v-if="pendingSap" v-slot:action>
                        <q-btn
                            flat
                            color="white"
                            no-caps
                            icon-right="arrow_forward"
                            label="Ir a Pendientes de Exportación"
                            @click="router.visit(route('panel.accountability.authorization.pending-export'))"
                        />
                    </template>
                </q-banner>

                <!-- Información General -->
                <q-expansion-item data-tour="general-info"
                    class="card-form q-mb-md overflow-hidden rounded-borders"
                    icon="info"
                    label="Información General"
                    header-class="text-subtitle2 text-weight-medium"
                    dense
                    default-opened
                >
                    <q-card>
                        <q-card-section>
                            <!-- Resumen -->
                            <div class="row q-col-gutter-sm q-mb-md">
                                <div class="col-xs-6 col-sm-3">
                                    <div class="text-caption text-grey">Recibido</div>
                                    <div class="text-body2 text-weight-medium stat-number">
                                        {{ money(accountability.total) }}
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="text-caption text-grey">Gastado ({{ documents.length }} doc.)</div>
                                    <div class="text-body2 text-weight-medium stat-number">
                                        {{ money(spent) }}
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="text-caption text-grey">{{ balance.label }}</div>
                                    <div class="text-body2 text-weight-medium stat-number" :class="`text-${balance.color}`">
                                        {{ money(balance.balance) }}
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="text-caption text-grey">Periodo</div>
                                    <div class="text-body2 text-weight-medium">
                                        {{ accountability.start_date || '-' }} &mdash; {{ accountability.end_date || '-' }}
                                    </div>
                                </div>
                            </div>
                            <q-separator class="q-mb-md" />
                            <!-- Datos generales -->
                            <div class="row q-col-gutter-md">
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="text-caption text-grey">Cuenta</div>
                                    <div class="text-caption text-weight-medium">
                                        {{ accountability.account_alias || accountability.account_name || '-' }}
                                    </div>
                                    <div v-if="accountability.account_alias" class="text-caption text-grey">
                                        {{ accountability.account_name }}
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="text-caption text-grey">Empleado</div>
                                    <div class="text-caption">{{ accountability.employee_name || '-' }}</div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="text-caption text-grey">Descripción</div>
                                    <div class="text-caption" style="word-break: break-word;">
                                        {{ accountability.description || '-' }}
                                    </div>
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                </q-expansion-item>

                <!-- Ciclo de Autorización -->
                <q-expansion-item data-tour="authorization-cycle"
                    v-if="cycleChain"
                    class="card-form q-mb-md overflow-hidden rounded-borders"
                    icon="account_tree"
                    label="Ciclo de Autorización"
                    :caption="cycleChain.round > 1 ? `Envío N° ${cycleChain.round}` : undefined"
                    header-class="text-subtitle2 text-weight-medium"
                    dense
                    default-opened
                >
                    <q-card>
                        <q-card-section>
                            <div class="text-caption text-grey q-mb-md">{{ cycleChain.cycle_name }}</div>
                            <div class="row q-col-gutter-sm">
                                <div
                                    v-for="level in cycleChain.levels"
                                    :key="level.id"
                                    class="col-xs-12 col-sm-6 col-md-4"
                                >
                                    <q-card flat bordered :class="getLevelCardClass(level)">
                                        <q-card-section class="q-py-sm q-px-md">
                                            <div class="row items-center q-gutter-xs no-wrap q-mb-xs">
                                                <q-badge color="primary" :label="level.order" rounded />
                                                <span class="text-body2 text-weight-medium">{{ level.name }}</span>
                                                <q-space />
                                                <q-icon
                                                    v-if="lastApproval(level)"
                                                    :name="APPROVAL_STATUS[lastApproval(level).status]?.icon ?? 'info'"
                                                    :color="APPROVAL_STATUS[lastApproval(level).status]?.color ?? 'grey'"
                                                    size="18px"
                                                />
                                                <q-icon
                                                    v-else-if="cycleChain.current_level_id === level.id"
                                                    name="schedule"
                                                    color="orange"
                                                    size="18px"
                                                />
                                            </div>
                                            <div class="text-caption text-grey">
                                                Autorizadores:
                                                {{ level.users.map((u) => u.name).join(', ') || '—' }}
                                            </div>
                                            <div v-if="level.approvals.length > 0" class="q-mt-xs">
                                                <div
                                                    v-for="(ap, ai) in level.approvals"
                                                    :key="ai"
                                                    class="text-caption"
                                                    :class="`text-${APPROVAL_STATUS[ap.status]?.color ?? 'grey'}`"
                                                >
                                                    {{ APPROVAL_STATUS[ap.status]?.label ?? ap.status }}:
                                                    {{ ap.user }} &mdash; {{ ap.acted_at }}
                                                    <div v-if="ap.comments" class="text-grey-8 approval-comment">
                                                        “{{ ap.comments }}”
                                                    </div>
                                                </div>
                                            </div>
                                        </q-card-section>
                                    </q-card>
                                </div>
                            </div>

                            <!-- Envíos anteriores (rondas rechazadas) -->
                            <q-expansion-item
                                v-if="cycleChain.previous_rounds?.length"
                                class="q-mt-md rounded-borders bordered"
                                icon="history"
                                :label="`Envíos anteriores (${cycleChain.previous_rounds.length})`"
                                dense
                                header-class="text-caption text-weight-medium"
                            >
                                <q-list dense separator>
                                    <q-item v-for="r in cycleChain.previous_rounds" :key="r.round">
                                        <q-item-section>
                                            <q-item-label class="text-weight-medium">Envío N° {{ r.round }}</q-item-label>
                                            <q-item-label
                                                v-for="(ap, ai) in r.approvals"
                                                :key="ai"
                                                caption
                                                :class="`text-${APPROVAL_STATUS[ap.status]?.color ?? 'grey'}`"
                                            >
                                                {{ ap.level }} &middot; {{ APPROVAL_STATUS[ap.status]?.label ?? ap.status }}:
                                                {{ ap.user }} &mdash; {{ ap.acted_at }}
                                                <span v-if="ap.comments" class="text-grey-8"> “{{ ap.comments }}”</span>
                                            </q-item-label>
                                        </q-item-section>
                                    </q-item>
                                </q-list>
                            </q-expansion-item>
                        </q-card-section>
                    </q-card>
                </q-expansion-item>

                <!-- Historial de cambios -->
                <q-expansion-item data-tour="history"
                    class="card-form q-mb-md overflow-hidden rounded-borders"
                    icon="history"
                    label="Historial de cambios"
                    header-class="text-subtitle2 text-weight-medium"
                    dense
                >
                    <q-card>
                        <q-card-section>
                            <div v-if="audits.length === 0" class="text-center q-pa-lg text-grey">
                                Sin registros de cambios
                            </div>
                            <q-timeline v-else color="grey-5" layout="loose">
                                <q-timeline-entry
                                    v-for="audit in audits"
                                    :key="audit.id"
                                    :icon="auditIcon(audit.event)"
                                    :color="auditColor(audit.event)"
                                >
                                    <template v-slot:title>
                                        <span class="text-body2 text-weight-medium">{{ auditLabel(audit.event) }}</span>
                                        <span class="text-caption text-grey q-ml-sm">por {{ audit.user }}</span>
                                    </template>
                                    <template v-slot:subtitle>
                                        <span class="text-caption text-grey">{{ audit.created_at }}</span>
                                        <span v-if="audit.ip_address" class="text-caption text-grey q-ml-sm">&middot; {{ audit.ip_address }}</span>
                                    </template>

                                    <div v-if="audit.event === 'updated' && Object.keys(audit.new_values || {}).length" class="q-mt-xs">
                                        <div
                                            v-for="(val, field) in audit.new_values"
                                            :key="field"
                                            class="row q-col-gutter-sm q-mb-xs items-center"
                                        >
                                            <div class="col-xs-12 col-sm-3">
                                                <q-badge color="grey-4" text-color="grey-8" :label="fieldLabel(field)" />
                                            </div>
                                            <div class="col-xs-5 col-sm-4 text-caption text-negative" style="word-break:break-word;">
                                                {{ audit.old_values?.[field] ?? '—' }}
                                            </div>
                                            <div class="col-auto">
                                                <q-icon name="arrow_forward" size="12px" color="grey" />
                                            </div>
                                            <div class="col-xs-5 col-sm-4 text-caption text-positive" style="word-break:break-word;">
                                                {{ val ?? '—' }}
                                            </div>
                                        </div>
                                    </div>
                                </q-timeline-entry>
                            </q-timeline>
                        </q-card-section>
                    </q-card>
                </q-expansion-item>

                <!-- Documentos / Vista contable -->
                <q-card data-tour="documents-panel" class="card-form">
                    <q-tabs
                        v-model="detailTab"
                        dense
                        no-caps
                        align="left"
                        active-color="primary"
                        indicator-color="primary"
                        narrow-indicator
                        class="q-px-md q-pt-sm"
                    >
                        <q-tab name="documents" icon="receipt_long" :label="`Documentos (${documents.length})`" />
                        <q-tab data-tour="accounting-tab" name="accounting" icon="account_balance" label="Vista contable" />
                    </q-tabs>
                    <q-separator />

                    <template v-if="detailTab === 'documents'">
                    <!-- Toolbar -->
                    <q-card-section class="q-pb-none">
                        <div class="row items-center q-col-gutter-sm">
                            <div class="col">
                                <div class="text-subtitle1 text-weight-medium">
                                    Detalle de Rendición
                                </div>
                            </div>
                            <div class="col-auto">
                                <q-input
                                    v-model="filter"
                                    outlined
                                    dense
                                    placeholder="Buscar…"
                                    name="search-detail"
                                    autocomplete="off"
                                    aria-label="Buscar documentos en el detalle"
                                    class="input-theme"
                                    style="min-width: 200px;"
                                    clearable
                                >
                                    <template v-slot:prepend>
                                        <q-icon name="search" aria-hidden="true" />
                                    </template>
                                </q-input>
                            </div>
                            <div class="col-auto" v-if="canEditDocuments">
                                <q-btn data-tour="add-document"
                                    color="primary"
                                    icon="add"
                                    label="Nuevo"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    aria-label="Crear nuevo documento"
                                    @click="HandleCreateDocument()"
                                />
                            </div>
                        </div>
                    </q-card-section>

                    <q-card-section>
                        <!-- Empty State -->
                        <div v-if="filteredDocuments.length === 0" class="text-center q-pa-xl">
                            <q-icon name="receipt_long" size="64px" color="grey-4" aria-hidden="true" />
                            <div class="text-body1 text-grey q-mt-md">
                                {{ filter ? 'Sin resultados para la búsqueda' : 'No hay documentos registrados' }}
                            </div>
                            <q-btn
                                v-if="!filter && canEditDocuments"
                                color="primary"
                                label="Crear Documento"
                                icon="add"
                                no-caps
                                unelevated
                                class="q-mt-md"
                                @click="HandleCreateDocument()"
                            />
                        </div>

                        <!-- Document Cards -->
                        <div data-tour="documents-list" v-else class="q-gutter-md">
                            <q-card data-tour="document-card"
                                v-for="(doc, i) in filteredDocuments"
                                :key="doc.id"
                                flat
                                bordered
                                class="doc-card"
                            >
                                <!-- Card Header -->
                                <q-card-section class="q-py-sm">
                                    <div class="row items-center q-col-gutter-sm">
                                        <div class="col-auto">
                                            <q-btn
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                :icon="expandedRows[doc.id] ? 'expand_less' : 'expand_more'"
                                                :aria-label="expandedRows[doc.id] ? 'Contraer detalle' : 'Expandir detalle'"
                                                @click="toggleExpand(doc.id)"
                                            />
                                        </div>
                                        <div class="col-auto">
                                            <span class="q-mx-md text-grey">{{ i + 1 }}</span>
                                        </div>
                                        <!-- Concepto + main info -->
                                        <div class="col">
                                            <div class="row items-center q-gutter-xs">
                                                <div class="text-body2 text-weight-medium" style="word-break: break-word;">
                                                    {{ doc.concept || 'Sin concepto' }}
                                                </div>
                                                <q-badge v-if="doc.lines_count > 0" color="secondary" outline
                                                    :label="`Repartido en ${doc.lines_count} líneas`" />
                                                <q-badge v-if="doc.items_count > 0" color="teal" outline
                                                    :label="`${doc.items_count} artículo${doc.items_count !== 1 ? 's' : ''}`" />
                                                <q-badge v-if="doc.sap_receipt_draft_entry" color="teal"
                                                    :label="`Entrada de mercancías (borrador) N° ${doc.sap_receipt_draft_entry}`" />
                                            </div>
                                            <div class="text-caption text-grey q-mt-xs">
                                                <span v-if="doc.date">{{ doc.date }}</span>
                                                <span v-if="doc.document_number"> &middot; N° {{ doc.document_number }}</span>
                                                <span v-if="doc.nit"> &middot; NIT/CI {{ doc.nit }}</span>
                                                <span v-if="doc.business_name"> &middot; {{ doc.business_name }}</span>
                                            </div>
                                        </div>

                                        <!-- Amount -->
                                        <div class="col-auto text-right">
                                            <div class="text-body2 text-weight-medium stat-number">
                                                {{ money(doc.amount) }}
                                            </div>
                                            <div class="text-caption text-grey">
                                                {{ doc.account_alias || doc.account || '' }}
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="col-auto q-gutter-xs">
                                            <q-btn data-tour="siat-button"
                                                v-if="doc.cuf"
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="receipt_long"
                                                color="teal"
                                                :loading="loadingFactura === doc.id"
                                                aria-label="Ver datos originales de factura SIAT"
                                                @click="HandleConsultaFactura(doc)"
                                            >
                                                <q-tooltip>Ver Factura SIAT</q-tooltip>
                                            </q-btn>
                                            <q-btn
                                                v-if="canEditDocuments"
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="edit"
                                                color="secondary"
                                                aria-label="Editar documento"
                                                @click="HandleEditDocument(doc.id)"
                                            >
                                                <q-tooltip>Editar</q-tooltip>
                                            </q-btn>
                                            <q-btn
                                                v-if="canEditDocuments"
                                                flat
                                                round
                                                dense
                                                size="sm"
                                                icon="delete_outline"
                                                color="negative"
                                                aria-label="Eliminar documento"
                                                @click="HandleDeleteDocument(doc.id)"
                                            >
                                                <q-tooltip>Eliminar</q-tooltip>
                                            </q-btn>
                                        </div>
                                    </div>
                                </q-card-section>

                                <!-- Expanded Detail -->
                                <q-slide-transition>
                                    <div v-show="expandedRows[doc.id]">
                                        <q-separator />
                                        <q-card-section class="q-py-sm">
                                            <!-- Libro de Compras -->
                                            <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">
                                                Libro de Compras
                                            </div>
                                            <div class="row q-col-gutter-sm q-mb-md">
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.document_number">
                                                    <div class="text-caption text-grey">N° Documento / Factura / Recibo</div>
                                                    <div class="text-body2">{{ doc.document_number }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.authorization_number">
                                                    <div class="text-caption text-grey">N° Autorización</div>
                                                    <div class="text-body2">{{ doc.authorization_number }}</div>
                                                </div>
                                                <div class="col-xs-12 col-sm-8 col-md-6" v-if="doc.cuf">
                                                    <div class="text-caption text-grey">CUF</div>
                                                    <div class="text-caption" style="word-break: break-all;">{{ doc.cuf }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.control_code">
                                                    <div class="text-caption text-grey">Cód. Control</div>
                                                    <div class="text-body2">{{ doc.control_code }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.nit">
                                                    <div class="text-caption text-grey">NIT / CI / Doc. Identif.</div>
                                                    <div class="text-body2">{{ doc.nit }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.business_name">
                                                    <div class="text-caption text-grey">Razón Social</div>
                                                    <div class="text-body2" style="word-break: break-word;">
                                                        {{ doc.business_name }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Importes -->
                                            <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">Importes</div>
                                            <div class="row q-col-gutter-sm q-mb-md">
                                                <div class="col-xs-6 col-sm-4 col-md-3">
                                                    <div class="text-caption text-grey">Importe</div>
                                                    <div class="text-body2 stat-number">{{ money(doc.amount) }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.discount">
                                                    <div class="text-caption text-grey">Descuento</div>
                                                    <div class="text-body2 stat-number">{{ money(doc.discount) }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.excento">
                                                    <div class="text-caption text-grey">Exento</div>
                                                    <div class="text-body2 stat-number">{{ money(doc.excento) }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.rate">
                                                    <div class="text-caption text-grey">Tasas</div>
                                                    <div class="text-body2 stat-number">{{ money(doc.rate) }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.gift_card">
                                                    <div class="text-caption text-grey">Gift Card</div>
                                                    <div class="text-body2 stat-number">{{ money(doc.gift_card) }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.rate_zero">
                                                    <div class="text-caption text-grey">Tasa Cero</div>
                                                    <div class="text-body2 stat-number">{{ money(doc.rate_zero) }}</div>
                                                </div>
                                                <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.ice">
                                                    <div class="text-caption text-grey">ICE</div>
                                                    <div class="text-body2 stat-number">{{ money(doc.ice) }}</div>
                                                </div>
                                            </div>

                                            <!-- Datos Contables -->
                                            <div v-if="doc.project_code || doc.distribution_rule_one">
                                                <div class="text-caption text-weight-medium text-grey-7 q-mb-sm">
                                                    Datos Contables
                                                </div>
                                                <div class="row q-col-gutter-sm">
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.project_code">
                                                        <div class="text-caption text-grey">Proyecto</div>
                                                        <div class="text-body2">{{ doc.project_code }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_one">
                                                        <div class="text-caption text-grey">CC 1</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_one }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_second">
                                                        <div class="text-caption text-grey">CC 2</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_second }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_three">
                                                        <div class="text-caption text-grey">CC 3</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_three }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_four">
                                                        <div class="text-caption text-grey">CC 4</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_four }}</div>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3" v-if="doc.distribution_rule_five">
                                                        <div class="text-caption text-grey">CC 5</div>
                                                        <div class="text-body2">{{ doc.distribution_rule_five }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </q-card-section>
                                    </div>
                                </q-slide-transition>
                            </q-card>
                        </div>
                    </q-card-section>
                    </template>

                    <!-- Vista contable: el asiento que se enviaría a SAP -->
                    <q-card-section v-else>
                        <div v-if="preview.loading" class="text-center q-pa-xl">
                            <q-spinner size="40px" color="primary" />
                            <div class="text-caption text-grey q-mt-sm">Armando el asiento…</div>
                        </div>
                        <div v-else-if="preview.error" class="text-center q-pa-lg">
                            <q-icon name="error_outline" size="48px" color="negative" />
                            <div class="text-body2 q-mt-sm">{{ preview.error }}</div>
                            <q-btn flat no-caps color="primary" label="Reintentar" class="q-mt-sm" @click="HandleLoadPreview(true)" />
                        </div>
                        <template v-else-if="preview.data">
                            <div class="row items-center q-col-gutter-sm q-mb-md">
                                <div class="col">
                                    <div class="text-subtitle2">{{ preview.data.memo || 'Asiento contable' }}</div>
                                    <div class="text-caption text-grey">
                                        Fecha {{ preview.data.date || '-' }} &middot;
                                        {{ preview.data.lines.length }} línea{{ preview.data.lines.length !== 1 ? 's' : '' }}
                                        &middot; vista previa, no es el asiento registrado en SAP
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <q-badge
                                        :color="preview.data.balanced ? 'positive' : 'negative'"
                                        :label="preview.data.balanced ? 'Cuadrado' : 'Descuadrado'"
                                        class="q-pa-sm text-body2"
                                    />
                                </div>
                                <div class="col-auto">
                                    <q-btn flat round dense icon="refresh" aria-label="Actualizar vista contable"
                                        @click="HandleLoadPreview(true)">
                                        <q-tooltip>Actualizar</q-tooltip>
                                    </q-btn>
                                </div>
                            </div>
                            <q-banner v-for="(w, wi) in preview.data.warnings" :key="wi" dense rounded
                                class="bg-orange-1 text-orange-10 q-mb-sm">
                                <template v-slot:avatar><q-icon name="warning" color="orange" /></template>
                                {{ w }}
                            </q-banner>
                            <q-markup-table flat bordered dense separator="horizontal" class="table-theme">
                                <thead>
                                    <tr class="table-header-theme">
                                        <th class="text-left">Cuenta</th>
                                        <th class="text-left">Centros de costo / Proyecto</th>
                                        <th class="text-left">Glosa</th>
                                        <th class="text-right">Debe</th>
                                        <th class="text-right">Haber</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(line, li) in preview.data.lines" :key="li">
                                        <td>
                                            <div class="text-weight-medium">{{ line.account_code || '—' }}</div>
                                            <div v-if="line.account_name" class="text-caption text-grey">{{ line.account_name }}</div>
                                            <div v-if="line.short_name" class="text-caption text-grey">Socio: {{ line.short_name }}</div>
                                        </td>
                                        <td>
                                            <q-badge v-for="cc in line.cost_centers" :key="cc" outline color="secondary"
                                                :label="cc" class="q-mr-xs" />
                                            <q-badge v-if="line.project" outline color="teal" :label="`Proy. ${line.project}`" />
                                            <span v-if="!line.cost_centers.length && !line.project" class="text-grey">—</span>
                                        </td>
                                        <td style="white-space: normal; min-width: 160px;">{{ line.line_memo || '—' }}</td>
                                        <td class="text-right stat-number">{{ line.debit ? money(line.debit) : '' }}</td>
                                        <td class="text-right stat-number">{{ line.credit ? money(line.credit) : '' }}</td>
                                    </tr>
                                    <tr class="text-weight-bold">
                                        <td colspan="3" class="text-right">Totales</td>
                                        <td class="text-right stat-number">{{ money(preview.data.debit) }}</td>
                                        <td class="text-right stat-number">{{ money(preview.data.credit) }}</td>
                                    </tr>
                                </tbody>
                            </q-markup-table>
                        </template>
                    </q-card-section>
                </q-card>

            </div>
        </div>

        <!-- Dialog Ver Factura Original SIAT -->
        <q-dialog v-model="showFacturaDialog">
            <q-card style="width: 700px; max-width: 80vw;">
                <q-bar class="bg-teal q-py-lg">
                    <q-space />
                    <span class="text-body1 text-white">Datos Originales de Factura</span>
                    <q-space />
                    <q-btn
                        dense
                        flat
                        icon="close"
                        color="white"
                        aria-label="Cerrar detalle de factura"
                        @click="showFacturaDialog = false"
                    >
                        <q-tooltip>Cerrar</q-tooltip>
                    </q-btn>
                </q-bar>

                <q-card-section v-if="selectedFactura" class="q-pa-md">
                    <div class="row q-col-gutter-sm q-mb-md">
                        <div class="col-6">
                            <div class="text-caption text-grey">N° Documento / Factura / Recibo</div>
                            <div class="text-body2">{{ selectedFactura.numeroFactura }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Fecha emisión</div>
                            <div class="text-body2">
                                {{ selectedFactura.fechaEmision ? selectedFactura.fechaEmision.substring(0, 10) : '-' }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">NIT Emisor</div>
                            <div class="text-body2">{{ selectedFactura.nitEmisor }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Razón social emisor</div>
                            <div class="text-body2" style="word-break: break-word;">
                                {{ selectedFactura.razonSocialEmisor }}
                            </div>
                        </div>
                        <div class="col-6" v-if="selectedFactura.direccion">
                            <div class="text-caption text-grey">Dirección</div>
                            <div class="text-body2" style="word-break: break-word;">
                                {{ selectedFactura.direccion }}
                            </div>
                        </div>
                        <div class="col-6" v-if="selectedFactura.codigoSucursal !== undefined">
                            <div class="text-caption text-grey">Sucursal</div>
                            <div class="text-body2">{{ selectedFactura.codigoSucursal }}</div>
                        </div>
                        <div class="col-12" v-if="selectedFactura.cuf">
                            <div class="text-caption text-grey">CUF</div>
                            <div class="text-caption" style="word-break: break-all;">{{ selectedFactura.cuf }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Cliente</div>
                            <div class="text-body2">{{ selectedFactura.nombreRazonSocial }}</div>
                        </div>
                        <div class="col-6" v-if="selectedFactura.numeroDocumento">
                            <div class="text-caption text-grey">Documento Cliente</div>
                            <div class="text-body2">{{ selectedFactura.numeroDocumento }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Monto Total</div>
                            <div class="text-body2 text-weight-bold">{{ formatMoney(selectedFactura.montoTotal, 'BOB') }}</div>
                        </div>
                        <div class="col-6" v-if="selectedFactura.estadoFactura">
                            <div class="text-caption text-grey">Estado</div>
                            <q-badge :color="selectedFactura.estadoFactura === 'VALIDA' ? 'positive' : 'negative'">
                                {{ selectedFactura.estadoFactura }}
                            </q-badge>
                        </div>
                    </div>

                    <!-- Detalle de lineas -->
                    <div v-if="selectedFactura.listaDetalle && selectedFactura.listaDetalle.length > 0">
                        <q-separator class="q-mb-md" />
                        <div class="text-subtitle2 q-mb-sm">
                            Detalle ({{ selectedFactura.listaDetalle.length }} items)
                        </div>
                        <q-virtual-scroll
                            :items="selectedFactura.listaDetalle"
                            :virtual-scroll-item-size="56"
                            style="max-height: 300px;"
                            class="rounded-borders bordered"
                        >
                            <template v-slot="{ item, index }">
                                <q-item :key="index" dense>
                                    <q-item-section avatar>
                                        <q-avatar
                                            size="24px"
                                            color="grey-3"
                                            text-color="grey-8"
                                            class="text-caption"
                                            style="font-variant-numeric: tabular-nums;"
                                        >
                                            {{ index + 1 }}
                                        </q-avatar>
                                    </q-item-section>
                                    <q-item-section>
                                        <q-item-label style="word-break: break-word;">
                                            {{ item.descripcion }}
                                        </q-item-label>
                                        <q-item-label caption>
                                            {{ item.cantidad }} x {{ formatMoney(item.precioUnitario, 'BOB') }}
                                        </q-item-label>
                                    </q-item-section>
                                    <q-item-section side>
                                        <q-item-label class="text-weight-medium">
                                            {{ formatMoney(item.subTotal, 'BOB') }}
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>
                            </template>
                        </q-virtual-scroll>
                    </div>
                </q-card-section>

                <q-card-actions align="right" class="q-pa-md">
                    <q-btn
                        color="primary"
                        label="Cerrar"
                        no-caps
                        flat
                        size="12px"
                        @click="showFacturaDialog = false"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, computed, reactive, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar, openURL } from "quasar";
import axios from "axios";
import {
    formatMoney,
    balanceInfo,
    statusLabel,
    statusColor,
    statusIcon,
} from "@/utils/accountability";

const props = defineProps({
    title: String,
    profile: { type: Object, default: null },
    accountability: { type: Object, required: true },
    documents: { type: Array, default: () => [] },
    from: { type: String, default: null },
    audits: { type: Array, default: () => [] },
    cycleChain: { type: Object, default: null },
    status_change: { type: Object, default: null },
    can_authorize: Boolean,
    can_annul: Boolean,
    has_next_level: Boolean,
    // Mensaje de error de SAP: solo viene tras un fallo real de exportación al autorizar
    sap_error: { type: String, default: null },
});

const $q = useQuasar();
const accountability = computed(() => props.accountability);
const documents = computed(() => props.documents ?? []);
const audits = computed(() => props.audits ?? []);
const cycleChain = computed(() => props.cycleChain);

const currency = computed(() => props.profile?.type_currency);
function money(value) {
    return formatMoney(value, currency.value);
}

const spent = computed(() =>
    documents.value.reduce((sum, doc) => sum + Number(doc.amount ?? 0), 0)
);
const balance = computed(() => balanceInfo(accountability.value.total, spent.value));

const FIELD_LABELS = {
    status:           'Estado',
    comments:         'Comentarios',
    total:            'Total',
    description:      'Descripción',
    start_date:       'Fecha inicio',
    end_date:         'Fecha fin',
    account_code:     'Código cuenta',
    account_name:     'Nombre cuenta',
    employee_code:    'Código empleado',
    employee_name:    'Empleado',
    current_level_id: 'Nivel actual',
    sap_exported:     'Exportada a SAP',
    sap_trans_id:     'N° asiento SAP',
    sap_exported_at:  'Fecha exportación SAP',
    sap_export_mode:  'Modo exportación SAP',
    submission_round: 'Envío N°',
    submitted_at:     'Fecha de envío',
};

const APPROVAL_STATUS = {
    aprobado:  { label: 'Aprobado',  color: 'positive', icon: 'check_circle' },
    rechazado: { label: 'Rechazado', color: 'negative', icon: 'cancel' },
    anulado:   { label: 'Anulado',   color: 'grey-7',   icon: 'block' },
};

function fieldLabel(field) {
    return FIELD_LABELS[field] ?? field;
}

function auditLabel(event) {
    const labels = { created: 'Creado', updated: 'Modificado', deleted: 'Eliminado' };
    return labels[event] ?? event;
}

function auditIcon(event) {
    const icons = { created: 'add_circle', updated: 'edit', deleted: 'delete' };
    return icons[event] ?? 'info';
}

function auditColor(event) {
    const colors = { created: 'positive', updated: 'primary', deleted: 'negative' };
    return colors[event] ?? 'grey';
}

const filter = ref("");
const expandedRows = ref({});
const selectedFactura = ref(null);
const showFacturaDialog = ref(false);
const loadingFactura = ref(null);
// Acción en curso ('authorize' | 'reject' | 'annul' | 'force'): bloquea los demás botones
const busy = ref(null);

// Los documentos (y su reparto) se pueden corregir hasta que la rendición se exporta a SAP
const canEditDocuments = computed(() => !accountability.value.sap_exported);

const authorizeLabel = computed(() => (props.has_next_level ? "Aprobar y avanzar" : "Autorizar"));

function lastApproval(level) {
    return level.approvals.length ? level.approvals[level.approvals.length - 1] : null;
}

function getLevelCardClass(level) {
    const last = lastApproval(level);
    if (last) {
        return { aprobado: "bg-green-1", rechazado: "bg-red-1", anulado: "bg-grey-3" }[last.status] ?? "";
    }
    if (cycleChain.value?.current_level_id === level.id) return "bg-orange-1";
    return "";
}

// Autorizada pero sin asiento en SAP: pendiente de exportación
const pendingSap = computed(
    () => accountability.value.status === "Autorizado" && !accountability.value.sap_exported
);

const statusBannerColor = computed(() => (pendingSap.value ? "warning" : statusColor(accountability.value.status)));

const statusMessage = computed(() => {
    const a = accountability.value;
    switch (a.status) {
        case "Pendiente":
            return "La rendición está pendiente de autorización";
        case "Anulado":
            return `La rendición fue anulada${a.comments ? `: ${a.comments}` : ""}`;
        case "Rechazado":
            return `La rendición fue rechazada${a.comments ? `: ${a.comments}` : ""}`;
        case "Autorizado":
            if (!a.sap_exported) return "Autorizada, pendiente de exportación a SAP";
            return a.sap_trans_id
                ? `La rendición fue aprobada y exportada (asiento preliminar SAP N° ${a.sap_trans_id})`
                : "La rendición fue aprobada y exportada a SAP";
        default:
            return null;
    }
});

const filteredDocuments = computed(() => {
    if (!filter.value) return documents.value;
    const needle = filter.value.toLowerCase();
    return documents.value.filter((doc) => {
        return (
            (doc.concept && doc.concept.toLowerCase().includes(needle)) ||
            (doc.business_name && doc.business_name.toLowerCase().includes(needle)) ||
            (doc.nit && String(doc.nit).includes(needle)) ||
            (doc.document_number && String(doc.document_number).includes(needle)) ||
            (doc.account && doc.account.toLowerCase().includes(needle)) ||
            (doc.account_name && doc.account_name.toLowerCase().includes(needle))
        );
    });
});

function toggleExpand(id) {
    expandedRows.value[id] = !expandedRows.value[id];
}

// ── Vista contable (se carga al abrir la pestaña) ───────────
const detailTab = ref("documents");
const preview = reactive({ loading: false, error: null, data: null });

async function HandleLoadPreview(force = false) {
    if (preview.loading || (preview.data && !force)) return;
    preview.loading = true;
    preview.error = null;
    try {
        const res = await axios.get(
            route("panel.accountability.authorization.detail.preview", accountability.value.id),
            { headers: { Accept: "application/json" } }
        );
        preview.data = res.data;
    } catch (error) {
        preview.error = error.response?.data?.message || "No se pudo cargar la vista contable.";
    } finally {
        preview.loading = false;
    }
}

watch(detailTab, (tab) => {
    if (tab === "accounting") HandleLoadPreview();
});
// Si cambian los documentos, la vista contable se vuelve a armar la próxima vez
watch(documents, () => {
    preview.data = null;
    if (detailTab.value === "accounting") HandleLoadPreview(true);
});

async function HandleConsultaFactura(doc) {
    if (!doc.cuf || !doc.nit || !doc.document_number) {
        $q.notify({
            type: "negative",
            message: "Faltan datos para consultar la factura (CUF, NIT o N° de factura)",
        });
        return;
    }
    const url = `https://siat.impuestos.gob.bo/consulta/QR?nit=${doc.nit}&cuf=${doc.cuf}&numero=${doc.document_number}`;
    loadingFactura.value = doc.id;
    try {
        const response = await axios.post(route("siat.consulta"), { url });
        const factura = response.data;
        if (factura.error) {
            $q.notify({ type: "negative", message: factura.error });
            return;
        }
        selectedFactura.value = factura;
        showFacturaDialog.value = true;
    } catch (error) {
        const msg = error.response?.data?.error || "Error al consultar SIAT";
        $q.notify({ type: "negative", message: msg });
    } finally {
        loadingFactura.value = null;
    }
}

function HandleBack() {
    router.visit(
        props.from === "pending-export"
            ? route("panel.accountability.authorization.pending-export")
            : route("panel.accountability.authorization.index")
    );
}

function HandleCreateDocument() {
    router.visit(
        route("panel.accountability.authorization.detail.create", [accountability.value.id])
    );
}

function HandleEditDocument(id) {
    router.visit(
        route("panel.accountability.authorization.detail.edit", [accountability.value.id, id])
    );
}

// Los avisos de resultado (flash) los muestra el manejador global del layout
function HandleDeleteDocument(id) {
    $q.dialog({
        title: "Eliminar documento",
        message: "¿Está seguro de eliminar este documento? Esta acción no se puede deshacer.",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Eliminar", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.delete(
            route("panel.accountability.authorization.detail.delete", [accountability.value.id, id]),
            { preserveScroll: true }
        );
    });
}

// Envía la acción una sola vez: mientras corre, todos los botones quedan deshabilitados
function HandlePost(action, url, payload, options = {}) {
    if (busy.value) return;
    router.post(url, payload, {
        preserveScroll: true,
        // Si vuelve al mismo detalle (p. ej. fallo de SAP) se conserva esta instancia,
        // para que el diálogo "Autorizar sin SAP" siga controlando el estado de los botones
        preserveState: true,
        ...options,
        onStart: () => (busy.value = action),
        onFinish: () => (busy.value = null),
    });
}

function HandleCancelStatus() {
    if (busy.value) return;
    $q.dialog({
        title: "Anular rendición",
        message: "Escriba el motivo de la anulación",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Anular", color: "grey-8", noCaps: true },
        persistent: true,
        prompt: {
            model: "",
            isValid: (val) => val.trim().length > 5,
            type: "textarea",
            label: "Motivo (mínimo 6 caracteres)",
        },
    }).onOk((comments) => {
        HandlePost(
            "annul",
            route("panel.accountability.authorization.detail.status", accountability.value.id),
            { status: "Anulado", comments: comments.trim() }
        );
    });
}

function HandleRejectStatus() {
    if (busy.value) return;
    $q.dialog({
        title: "Rechazar rendición",
        message: "Escriba el motivo del rechazo. El solicitante lo verá para corregir y volver a enviar.",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Rechazar", color: "negative", noCaps: true },
        persistent: true,
        prompt: {
            model: "",
            isValid: (val) => val.trim().length > 5,
            type: "textarea",
            label: "Motivo (mínimo 6 caracteres)",
        },
    }).onOk((comments) => {
        HandlePost(
            "reject",
            route("panel.accountability.authorization.detail.status", accountability.value.id),
            { status: "Rechazado", comments: comments.trim() }
        );
    });
}

function HandleAuthorizeStatus() {
    if (busy.value) return;
    const isIntermediate = props.has_next_level;
    // El comentario de aprobación se guarda en la cadena del ciclo; sin ciclo no hay dónde guardarlo
    const withComment = !!accountability.value.current_level_id;
    $q.dialog({
        title: isIntermediate ? "Aprobar nivel" : "Autorizar rendición",
        message: isIntermediate
            ? "¿Está seguro de aprobar este nivel? La rendición avanzará al siguiente nivel de autorización."
            : "¿Está seguro de autorizar esta rendición? Se exportará a SAP.",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: isIntermediate ? "Aprobar y avanzar" : "Autorizar", color: "positive", noCaps: true },
        persistent: true,
        ...(withComment
            ? { prompt: { model: "", type: "textarea", label: "Comentario (opcional)" } }
            : {}),
    }).onOk((value) => {
        const comments = withComment && typeof value === "string" && value.trim() ? value.trim() : null;
        HandlePost(
            "authorize",
            route("panel.accountability.authorization.detail.export", accountability.value.id),
            { comments },
            {
                // Fallo real de exportación: el servidor vuelve al detalle con sap_error
                onSuccess: (visit) => {
                    const sapError = visit.props?.sap_error;
                    if (sapError) HandleOfferForceAuthorize(sapError, comments);
                },
            }
        );
    });
}

function HandleOfferForceAuthorize(message, comments) {
    $q.dialog({
        title: "Error al exportar a SAP",
        message: `${message}\n\n¿Desea autorizar la rendición sin exportar a SAP? Quedará pendiente de exportación.`,
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Autorizar sin SAP", color: "warning", noCaps: true },
        persistent: true,
        style: "white-space: pre-line;",
    }).onOk(() => {
        HandlePost(
            "force",
            route("panel.accountability.authorization.detail.force-authorize", accountability.value.id),
            { comments }
        );
    }).onCancel(() => {
        $q.notify({ type: "negative", message });
    });
}

function HandleGetReport() {
    openURL(route("panel.accountability.authorization.report", accountability.value.id));
}
</script>

<style scoped>
.stat-number {
    font-variant-numeric: tabular-nums;
}

.bordered {
    border: 1px solid rgba(0, 0, 0, 0.12);
}

.approval-comment {
    font-style: italic;
    word-break: break-word;
}

.doc-card {
    transition: box-shadow 0.2s ease;
}

.doc-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

@media (prefers-reduced-motion: reduce) {
    .doc-card {
        transition: none;
    }
}
</style>
