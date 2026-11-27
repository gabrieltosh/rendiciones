<template>
    <q-dialog :model-value="modelValue" :maximized="$q.screen.xs" @update:model-value="(v) => emit('update:modelValue', v)">
        <q-card :class="{ 'siat-invoice-card': !$q.screen.xs }">
            <q-bar class="bg-teal q-py-lg">
                <q-space />
                <span class="text-body1 text-white">Datos de la factura en el SIAT</span>
                <q-space />
                <q-btn dense flat icon="close" color="white" aria-label="Cerrar detalle de factura"
                    @click="emit('update:modelValue', false)">
                    <q-tooltip>Cerrar</q-tooltip>
                </q-btn>
            </q-bar>

            <q-card-section v-if="factura" class="q-pa-md">
                <div class="row q-col-gutter-sm q-mb-md">
                    <div class="col-6">
                        <div class="text-caption text-grey">N° de factura</div>
                        <div class="text-body2">{{ factura.numeroFactura }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-caption text-grey">Fecha de emisión</div>
                        <div class="text-body2">{{ formatDate(invoiceDate(factura)) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-caption text-grey">NIT del emisor</div>
                        <div class="text-body2">{{ factura.nitEmisor }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-caption text-grey">Razón social del emisor</div>
                        <div class="text-body2" style="word-break: break-word;">{{ factura.razonSocialEmisor }}</div>
                    </div>
                    <div class="col-6" v-if="factura.direccion">
                        <div class="text-caption text-grey">Dirección</div>
                        <div class="text-body2" style="word-break: break-word;">{{ factura.direccion }}</div>
                    </div>
                    <div class="col-6" v-if="factura.codigoSucursal !== undefined">
                        <div class="text-caption text-grey">Sucursal</div>
                        <div class="text-body2">{{ factura.codigoSucursal }}</div>
                    </div>
                    <div class="col-12" v-if="factura.cuf">
                        <div class="text-caption text-grey">CUF</div>
                        <div class="text-caption" style="word-break: break-all;">{{ factura.cuf }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-caption text-grey">Cliente</div>
                        <div class="text-body2">{{ factura.nombreRazonSocial }}</div>
                    </div>
                    <div class="col-6" v-if="factura.numeroDocumento">
                        <div class="text-caption text-grey">Documento del cliente</div>
                        <div class="text-body2">{{ factura.numeroDocumento }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-caption text-grey">Monto total</div>
                        <div class="text-body2 text-weight-bold">{{ formatMoney(factura.montoTotal) }}</div>
                    </div>
                    <div class="col-6" v-if="statusLabel">
                        <div class="text-caption text-grey">Estado</div>
                        <q-badge :color="valid ? 'positive' : 'negative'" :label="statusLabel" />
                    </div>
                </div>

                <div v-if="factura.listaDetalle && factura.listaDetalle.length > 0">
                    <q-separator class="q-mb-md" />
                    <div class="text-subtitle2 q-mb-sm">
                        Detalle ({{ factura.listaDetalle.length }}
                        {{ factura.listaDetalle.length === 1 ? 'ítem' : 'ítems' }})
                    </div>
                    <q-virtual-scroll :items="factura.listaDetalle" :virtual-scroll-item-size="56"
                        style="max-height: 300px;" class="rounded-borders siat-bordered">
                        <template v-slot="{ item, index }">
                            <q-item :key="index" dense>
                                <q-item-section avatar>
                                    <q-avatar size="24px" color="grey-3" text-color="grey-8" class="text-caption tabular">
                                        {{ index + 1 }}
                                    </q-avatar>
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label style="word-break: break-word;">{{ item.descripcion }}</q-item-label>
                                    <q-item-label caption>
                                        {{ item.cantidad }} x {{ formatMoney(item.precioUnitario) }}
                                    </q-item-label>
                                </q-item-section>
                                <q-item-section side>
                                    <q-item-label class="text-weight-medium tabular">{{ formatMoney(item.subTotal) }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </template>
                    </q-virtual-scroll>
                </div>
            </q-card-section>

            <q-card-actions align="right" class="q-pa-md">
                <q-btn color="primary" label="Cerrar" no-caps flat @click="emit('update:modelValue', false)" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

<script setup>
import { computed } from "vue";
import { useQuasar } from "quasar";
import { formatMoney as money } from "@/utils/accountability";
import { invoiceStatusLabel, isInvoiceValid, invoiceDate, formatDate } from "./siat";

const props = defineProps({
    modelValue: Boolean,
    // Respuesta de SIAT (objeto de la factura)
    factura: { type: Object, default: null },
    // Las facturas de SIAT son en bolivianos
    currency: { type: String, default: "BS" },
});
const emit = defineEmits(["update:modelValue"]);

const $q = useQuasar();
const statusLabel = computed(() => invoiceStatusLabel(props.factura));
const valid = computed(() => isInvoiceValid(props.factura));

function formatMoney(value) {
    return money(value, props.currency);
}
</script>

<style scoped>
.siat-invoice-card {
    width: 700px;
    max-width: 92vw;
}
.siat-bordered {
    border: 1px solid rgba(0, 0, 0, 0.12);
}
.tabular {
    font-variant-numeric: tabular-nums;
}
</style>
