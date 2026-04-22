<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-9">

                <!-- Header -->
                <q-card class="q-pa-md card-form q-mb-md">
                    <div class="row items-center">
                        <div class="col">
                            <div class="text-h6 title-form">Editar Detalle</div>
                            <div class="text-caption text-grey">
                                Rendicion #{{ page.props.accountability.id }}
                            </div>
                        </div>
                        <div class="col-auto q-gutter-sm">
                            <q-btn
                                color="secondary"
                                label="Cancelar"
                                size="12px"
                                no-caps
                                flat
                                unelevated
                                aria-label="Cancelar y volver al detalle"
                                @click="HandleCancel"
                            />
                            <q-btn
                                color="primary"
                                label="Actualizar"
                                icon="save"
                                size="12px"
                                no-caps
                                unelevated
                                :loading="loading.submit"
                                :disable="loading.submit || Object.keys(document).length === 0"
                                aria-label="Guardar cambios del detalle"
                                @click="HandleUpdateForm"
                            />
                        </div>
                    </div>
                </q-card>

                <!-- Seccion: General -->
                <q-card class="q-pa-lg card-form q-mb-md">
                    <div class="text-subtitle1 text-weight-medium q-mb-md">
                        Informacion General
                    </div>
                    <div class="row q-col-gutter-md">
                        <div class="col-xs-12 col-sm-6">
                            <label class="form-label">
                                Concepto <span class="text-red">*</span>
                            </label>
                            <q-select
                                class="input-theme"
                                dense
                                outlined
                                :options="options.accounts"
                                v-model="form.account"
                                option-value="key"
                                option-label="label"
                                emit-value
                                map-options
                                use-input
                                input-debounce="0"
                                @filter="HandleFilterAccounts"
                                clearable
                                aria-label="Seleccionar cuenta"
                                name="account"
                                autocomplete="off"
                            >
                                <template v-slot:no-option>
                                    <q-item>
                                        <q-item-section class="text-grey">
                                            Sin resultados
                                        </q-item-section>
                                    </q-item>
                                </template>
                            </q-select>
                            <div v-if="errors.account" class="container-error">
                                <ul class="message-error">
                                    <li
                                        v-for="(error, index) in errors.account"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <label class="form-label">
                                Fecha <span class="text-red">*</span>
                            </label>
                            <q-input
                                v-model="form.date"
                                dense
                                outlined
                                type="date"
                                :min="page.props.accountability.start_date"
                                :max="page.props.accountability.end_date"
                                class="input-theme"
                                aria-label="Fecha del detalle"
                                name="date"
                            />
                            <div v-if="errors.date" class="container-error">
                                <ul class="message-error">
                                    <li
                                        v-for="(error, index) in errors.date"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <label class="form-label">
                                Tipo Documento <span class="text-red">*</span>
                            </label>
                            <q-select
                                class="input-theme"
                                dense
                                outlined
                                :options="options.documents"
                                v-model="form.document_id"
                                option-value="id"
                                option-label="name"
                                emit-value
                                map-options
                                @update:model-value="HandleFindDocument()"
                                aria-label="Seleccionar tipo de documento"
                                name="document_id"
                                autocomplete="off"
                            >
                                <template v-slot:no-option>
                                    <q-item>
                                        <q-item-section class="text-grey">
                                            Sin resultados
                                        </q-item-section>
                                    </q-item>
                                </template>
                            </q-select>
                            <div v-if="errors.document_id" class="container-error">
                                <ul class="message-error">
                                    <li
                                        v-for="(error, index) in errors.document_id"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <label class="form-label">
                                Comentario <span class="text-red">*</span>
                            </label>
                            <q-input
                                v-model="form.concept"
                                dense
                                outlined
                                class="input-theme"
                                aria-label="Comentario del detalle"
                                name="concept"
                                autocomplete="off"
                            />
                            <div v-if="errors.concept" class="container-error">
                                <ul class="message-error">
                                    <li
                                        v-for="(error, index) in errors.concept"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </q-card>

                <!-- Seccion: Libro de Compras -->
                <q-slide-transition>
                    <q-card v-if="Object.keys(document).length > 0" class="q-pa-lg card-form q-mb-md">
                        <div class="row items-center q-mb-md">
                            <div class="col">
                                <div class="text-subtitle1 text-weight-medium">
                                    Libro de Compras
                                </div>
                            </div>
                            <div class="col-auto q-gutter-sm">
                                <q-btn
                                    v-if="facturaOriginal"
                                    color="teal"
                                    icon="receipt_long"
                                    label="Ver Factura"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    aria-label="Ver datos originales de la factura SIAT"
                                    @click="showFacturaDialog = true"
                                />
                                <q-btn
                                    color="blue"
                                    icon="qr_code_scanner"
                                    label="Escanear QR"
                                    size="12px"
                                    no-caps
                                    unelevated
                                    aria-label="Abrir escaner de codigos QR para facturas"
                                    @click="showQrScanner = true"
                                />
                            </div>
                        </div>
                        <div class="row q-col-gutter-md">
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <label class="form-label">
                                    N. Documento <span class="text-red">*</span>
                                </label>
                                <q-input
                                    v-model="form.document_number"
                                    dense
                                    outlined
                                    class="input-theme"
                                    type="number"
                                    style="font-variant-numeric: tabular-nums"
                                    aria-label="Numero de documento"
                                    name="document_number"
                                    autocomplete="off"
                                />
                                <div v-if="errors.document_number" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors.document_number"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.authorization_number_status">
                                <label class="form-label">N. Autorizacion</label>
                                <q-input
                                    v-model="form.authorization_number"
                                    dense
                                    outlined
                                    class="input-theme"
                                    aria-label="Numero de autorizacion"
                                    name="authorization_number"
                                    autocomplete="off"
                                />
                                <div v-if="errors.authorization_number" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors.authorization_number"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.cuf_status">
                                <label class="form-label">CUF</label>
                                <q-input
                                    v-model="form.cuf"
                                    dense
                                    outlined
                                    class="input-theme"
                                    aria-label="Codigo unico de facturacion"
                                    name="cuf"
                                    autocomplete="off"
                                />
                                <div v-if="errors.cuf" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors.cuf"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.control_code_status">
                                <label class="form-label">Codigo Control</label>
                                <q-input
                                    v-model="form.control_code"
                                    dense
                                    outlined
                                    class="input-theme"
                                    aria-label="Codigo de control"
                                    name="control_code"
                                    autocomplete="off"
                                />
                                <div v-if="errors.control_code" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors.control_code"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.nit_status">
                                <label class="form-label">NIT</label>
                                <q-input
                                    v-model="form.nit"
                                    dense
                                    outlined
                                    class="input-theme"
                                    @update:model-value="HandleChangeNIT()"
                                    aria-label="NIT del proveedor"
                                    name="nit"
                                    autocomplete="off"
                                />
                                <div v-if="errors.nit" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors.nit"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.business_name_status">
                                <label class="form-label">Razon Social</label>
                                <q-input
                                    v-model="form.business_name"
                                    dense
                                    outlined
                                    class="input-theme"
                                    @update:model-value="HandleChangeBusinessName()"
                                    aria-label="Razon social del proveedor"
                                    name="business_name"
                                    autocomplete="off"
                                />
                                <div v-if="errors.business_name" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors.business_name"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <label class="form-label">
                                    Importe <span class="text-red">*</span>
                                </label>
                                <q-input
                                    v-model="form.amount"
                                    dense
                                    type="number"
                                    outlined
                                    class="input-theme"
                                    style="font-variant-numeric: tabular-nums"
                                    aria-label="Importe del detalle"
                                    name="amount"
                                    autocomplete="off"
                                />
                                <div v-if="errors.amount" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors.amount"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div
                                class="col-xs-12 col-sm-6 col-md-4"
                                v-for="(field, i) in form.field"
                                :key="i"
                            >
                                <label class="form-label">
                                    {{ field.name }} <span class="text-red">*</span>
                                </label>
                                <q-input
                                    v-model="field.value"
                                    dense
                                    type="number"
                                    outlined
                                    class="input-theme"
                                    style="font-variant-numeric: tabular-nums"
                                    :aria-label="field.name"
                                    :name="'field_' + i"
                                    autocomplete="off"
                                />
                                <div v-if="errors['field.' + i + '.value']" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors['field.' + i + '.value']"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.discount_status">
                                <label class="form-label">Descuento</label>
                                <q-input
                                    v-model="form.discount"
                                    dense
                                    type="number"
                                    outlined
                                    class="input-theme"
                                    style="font-variant-numeric: tabular-nums"
                                    aria-label="Descuento"
                                    name="discount"
                                    autocomplete="off"
                                />
                                <div v-if="errors.discount" class="container-error">
                                    <ul class="message-error">
                                        <li
                                            v-for="(error, index) in errors.discount"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.exento_status">
                                <label class="form-label">Exento</label>
                                <q-input
                                    v-model="form.excento"
                                    dense
                                    type="number"
                                    outlined
                                    class="input-theme"
                                    style="font-variant-numeric: tabular-nums"
                                    aria-label="Monto exento"
                                    name="excento"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.tasas">
                                <label class="form-label">Tasas</label>
                                <q-input
                                    v-model="form.rate"
                                    type="number"
                                    dense
                                    outlined
                                    class="input-theme"
                                    style="font-variant-numeric: tabular-nums"
                                    aria-label="Tasas"
                                    name="rate"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.gift_card_status">
                                <label class="form-label">Gift Card</label>
                                <q-input
                                    v-model="form.gift_card"
                                    dense
                                    type="number"
                                    outlined
                                    class="input-theme"
                                    style="font-variant-numeric: tabular-nums"
                                    aria-label="Gift card"
                                    name="gift_card"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.rate_zero_status">
                                <label class="form-label">Tasa Cero</label>
                                <q-input
                                    v-model="form.rate_zero"
                                    type="number"
                                    dense
                                    outlined
                                    class="input-theme"
                                    style="font-variant-numeric: tabular-nums"
                                    aria-label="Tasa cero"
                                    name="rate_zero"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4" v-if="document.ice_status">
                                <label class="form-label">ICE</label>
                                <q-input
                                    v-model="form.ice"
                                    dense
                                    type="number"
                                    outlined
                                    class="input-theme"
                                    style="font-variant-numeric: tabular-nums"
                                    aria-label="ICE"
                                    name="ice"
                                    autocomplete="off"
                                />
                            </div>
                        </div>
                    </q-card>
                </q-slide-transition>

                <!-- Seccion: Datos Contables -->
                <q-slide-transition>
                    <q-card v-if="Object.keys(document).length > 0" class="q-pa-lg card-form q-mb-md">
                        <div class="text-subtitle1 text-weight-medium q-mb-md">
                            Datos Contables
                        </div>
                        <div class="row q-col-gutter-md">
                            <div class="col-xs-12 col-sm-6">
                                <label class="form-label">Proyecto</label>
                                <q-select
                                    class="input-theme"
                                    dense
                                    outlined
                                    :options="options.projects"
                                    v-model="form.project_code"
                                    option-value="PrjCode"
                                    option-label="PrjName"
                                    emit-value
                                    map-options
                                    use-input
                                    input-debounce="0"
                                    @filter="HandleFilterProjects"
                                    clearable
                                    aria-label="Seleccionar proyecto"
                                    name="project_code"
                                    autocomplete="off"
                                >
                                    <template v-slot:no-option>
                                        <q-item>
                                            <q-item-section class="text-grey">
                                                Sin resultados
                                            </q-item-section>
                                        </q-item>
                                    </template>
                                </q-select>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <label class="form-label">Centro de Costo 1</label>
                                <q-select
                                    class="input-theme"
                                    dense
                                    outlined
                                    :options="options.distribution[1]"
                                    v-model="form.distribution_rule_one"
                                    option-label="Name"
                                    clearable
                                    aria-label="Centro de costo 1"
                                    name="distribution_rule_one"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <label class="form-label">Centro de Costo 2</label>
                                <q-select
                                    class="input-theme"
                                    dense
                                    outlined
                                    :options="options.distribution[2]"
                                    v-model="form.distribution_rule_second"
                                    option-label="Name"
                                    clearable
                                    aria-label="Centro de costo 2"
                                    name="distribution_rule_second"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <label class="form-label">Centro de Costo 3</label>
                                <q-select
                                    class="input-theme"
                                    dense
                                    outlined
                                    :options="options.distribution[3]"
                                    v-model="form.distribution_rule_three"
                                    option-label="Name"
                                    clearable
                                    aria-label="Centro de costo 3"
                                    name="distribution_rule_three"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <label class="form-label">Centro de Costo 4</label>
                                <q-select
                                    class="input-theme"
                                    dense
                                    outlined
                                    :options="options.distribution[4]"
                                    v-model="form.distribution_rule_four"
                                    option-label="Name"
                                    clearable
                                    aria-label="Centro de costo 4"
                                    name="distribution_rule_four"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <label class="form-label">Centro de Costo 5</label>
                                <q-select
                                    class="input-theme"
                                    dense
                                    outlined
                                    :options="options.distribution[5]"
                                    v-model="form.distribution_rule_five"
                                    option-label="Name"
                                    clearable
                                    aria-label="Centro de costo 5"
                                    name="distribution_rule_five"
                                    autocomplete="off"
                                />
                            </div>
                        </div>
                    </q-card>
                </q-slide-transition>

            </div>
        </div>

        <!-- Dialog QR Scanner -->
        <q-dialog
            v-model="showQrScanner"
            persistent
            :transition-show="prefersReducedMotion ? 'none' : 'slide-up'"
            :transition-hide="prefersReducedMotion ? 'none' : 'slide-down'"
        >
            <q-card class="qr-dialog-card" style="width: 700px; max-width: 80vw;">
                <q-bar class="bg-primary q-py-lg">
                    <q-space />
                    <span class="text-body1 text-white">Escanear QR de Factura</span>
                    <q-space />
                    <q-btn
                        dense
                        flat
                        icon="close"
                        aria-label="Cerrar escaner QR"
                        @click="handleCloseScanner"
                        color="white"
                    >
                        <q-tooltip>Cerrar</q-tooltip>
                    </q-btn>
                </q-bar>

                <!-- Vista Preview: datos escaneados antes de aplicar -->
                <q-card-section v-if="showPreview && scannedData" class="q-pa-md">
                    <div class="q-pa-md" style="max-width: 600px; margin: 0 auto;">
                        <div class="text-h6 q-mb-md">Datos Detectados</div>
                        <div class="text-body2 text-grey q-mb-lg">
                            Revise los datos extraidos de la factura antes de aplicarlos al formulario
                        </div>

                        <q-list bordered separator class="rounded-borders q-mb-md">
                            <q-item v-if="scannedData.document_number">
                                <q-item-section>
                                    <q-item-label caption>N. Factura</q-item-label>
                                    <q-item-label>{{ scannedData.document_number }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="scannedData.cuf">
                                <q-item-section>
                                    <q-item-label caption>CUF</q-item-label>
                                    <q-item-label class="text-caption" style="word-break: break-all;">{{ scannedData.cuf }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="scannedData.nit">
                                <q-item-section>
                                    <q-item-label caption>NIT Emisor</q-item-label>
                                    <q-item-label>{{ scannedData.nit }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="scannedData.business_name">
                                <q-item-section>
                                    <q-item-label caption>Razon Social</q-item-label>
                                    <q-item-label style="word-break: break-word;">{{ scannedData.business_name }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="scannedData.amount">
                                <q-item-section>
                                    <q-item-label caption>Monto Total</q-item-label>
                                    <q-item-label>Bs {{ scannedData.amount }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="scannedData.date">
                                <q-item-section>
                                    <q-item-label caption>Fecha Emision</q-item-label>
                                    <q-item-label>{{ scannedData.date }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>

                        <!-- Detalle de lineas de la factura -->
                        <div v-if="scannedData.detalles && scannedData.detalles.length > 0" class="q-mb-md">
                            <div class="text-subtitle2 q-mb-sm">
                                Detalle de la Factura ({{ scannedData.detalles.length }} items)
                            </div>
                            <q-virtual-scroll
                                :items="scannedData.detalles"
                                :virtual-scroll-item-size="48"
                                style="max-height: 250px;"
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
                                            <q-item-label style="word-break: break-word;">{{ item.descripcion }}</q-item-label>
                                            <q-item-label caption>
                                                {{ item.cantidad }} x Bs {{ item.precioUnitario }}
                                            </q-item-label>
                                        </q-item-section>
                                        <q-item-section side>
                                            <q-item-label class="text-weight-medium">Bs {{ item.subTotal }}</q-item-label>
                                        </q-item-section>
                                    </q-item>
                                </template>
                            </q-virtual-scroll>
                        </div>

                        <div class="row q-gutter-sm justify-end">
                            <q-btn
                                color="white"
                                text-color="dark"
                                label="Cancelar"
                                no-caps
                                flat
                                @click="cancelPreview"
                            />
                            <q-btn
                                color="primary"
                                label="Aplicar Datos"
                                icon="check"
                                no-caps
                                @click="applyScannedData"
                            />
                        </div>
                    </div>
                </q-card-section>

                <!-- Vista Escaneo -->
                <q-card-section v-else class="q-pa-md">
                    <div style="max-width: 600px; margin: 0 auto;">
                        <q-tabs
                            v-model="scanMethod"
                            dense
                            active-color="primary"
                            indicator-color="primary"
                            align="justify"
                            narrow-indicator
                            class="q-mb-md"
                        >
                            <q-tab name="camera" icon="qr_code_scanner" label="Camara" />
                            <q-tab name="manual" icon="link" label="URL Manual" />
                        </q-tabs>

                        <q-tab-panels v-model="scanMethod" animated>
                            <q-tab-panel name="camera" class="q-pa-none">
                                <div style="max-width: 500px; margin: 0 auto; position: relative;">
                                    <QrcodeStream
                                        v-if="!qrCameraError"
                                        @detect="onQrDetect"
                                        @error="onQrError"
                                        :paused="qrLoading"
                                        class="rounded-borders overflow-hidden"
                                    >
                                        <!-- Guia visual -->
                                        <div v-if="!qrLoading" class="qr-guide-overlay">
                                            <div class="qr-guide-frame"></div>
                                            <div class="qr-guide-text">
                                                Posicione el codigo QR dentro del marco
                                            </div>
                                        </div>

                                        <!-- Loading overlay -->
                                        <div v-if="qrLoading" class="text-center q-pa-xl">
                                            <q-spinner-dots color="primary" size="40px" />
                                            <div class="q-mt-sm text-body1">Consultando factura...</div>
                                        </div>
                                    </QrcodeStream>

                                    <!-- Error de camara -->
                                    <div v-if="qrCameraError" class="text-center q-pa-md">
                                        <q-icon name="videocam_off" size="48px" color="warning" />
                                        <div class="q-mt-sm text-body2" style="word-break: break-word;">{{ qrCameraError }}</div>
                                        <q-btn
                                            color="primary"
                                            label="Reintentar"
                                            icon="refresh"
                                            no-caps
                                            flat
                                            class="q-mt-md"
                                            @click="retryCamera"
                                        />
                                    </div>
                                </div>
                            </q-tab-panel>

                            <q-tab-panel name="manual" class="q-pa-md">
                                <label for="qr-manual-url" class="form-label q-mb-sm" style="display: block;">
                                    Pegar URL de factura SIAT:
                                </label>
                                <q-input
                                    id="qr-manual-url"
                                    v-model="qrManualUrl"
                                    dense
                                    outlined
                                    type="url"
                                    name="siat-url"
                                    autocomplete="url"
                                    placeholder="https://siat.impuestos.gob.bo/consulta/QR?nit=..."
                                    class="q-mb-sm input-theme"
                                />
                                <q-btn
                                    color="primary"
                                    label="Consultar"
                                    aria-label="Consultar factura en SIAT"
                                    no-caps
                                    size="12px"
                                    :loading="qrLoading"
                                    @click="HandleConsultaSiat(qrManualUrl)"
                                    :disable="!qrManualUrl"
                                />
                            </q-tab-panel>
                        </q-tab-panels>
                    </div>
                </q-card-section>
            </q-card>
        </q-dialog>

        <!-- Dialog Ver Factura Original -->
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

                <q-card-section v-if="facturaOriginal" class="q-pa-md">
                    <div class="row q-col-gutter-sm q-mb-md">
                        <div class="col-6">
                            <div class="text-caption text-grey">N. Factura</div>
                            <div class="text-body2">{{ facturaOriginal.numeroFactura }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Fecha Emision</div>
                            <div class="text-body2">{{ facturaOriginal.fechaEmision ? facturaOriginal.fechaEmision.substring(0, 10) : '-' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">NIT Emisor</div>
                            <div class="text-body2">{{ facturaOriginal.nitEmisor }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Razon Social Emisor</div>
                            <div class="text-body2" style="word-break: break-word;">{{ facturaOriginal.razonSocialEmisor }}</div>
                        </div>
                        <div class="col-6" v-if="facturaOriginal.direccion">
                            <div class="text-caption text-grey">Direccion</div>
                            <div class="text-body2" style="word-break: break-word;">{{ facturaOriginal.direccion }}</div>
                        </div>
                        <div class="col-6" v-if="facturaOriginal.codigoSucursal !== undefined">
                            <div class="text-caption text-grey">Sucursal</div>
                            <div class="text-body2">{{ facturaOriginal.codigoSucursal }}</div>
                        </div>
                        <div class="col-12" v-if="facturaOriginal.cuf">
                            <div class="text-caption text-grey">CUF</div>
                            <div class="text-caption" style="word-break: break-all;">{{ facturaOriginal.cuf }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Cliente</div>
                            <div class="text-body2">{{ facturaOriginal.nombreRazonSocial }}</div>
                        </div>
                        <div class="col-6" v-if="facturaOriginal.numeroDocumento">
                            <div class="text-caption text-grey">Documento Cliente</div>
                            <div class="text-body2">{{ facturaOriginal.numeroDocumento }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-caption text-grey">Monto Total</div>
                            <div class="text-body2 text-weight-bold">Bs {{ facturaOriginal.montoTotal }}</div>
                        </div>
                        <div class="col-6" v-if="facturaOriginal.estadoFactura">
                            <div class="text-caption text-grey">Estado</div>
                            <q-badge :color="facturaOriginal.estadoFactura === 'VALIDA' ? 'positive' : 'negative'">
                                {{ facturaOriginal.estadoFactura }}
                            </q-badge>
                        </div>
                    </div>

                    <!-- Detalle de lineas -->
                    <div v-if="facturaOriginal.listaDetalle && facturaOriginal.listaDetalle.length > 0">
                        <q-separator class="q-mb-md" />
                        <div class="text-subtitle2 q-mb-sm">
                            Detalle ({{ facturaOriginal.listaDetalle.length }} items)
                        </div>
                        <q-virtual-scroll
                            :items="facturaOriginal.listaDetalle"
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
                                        <q-item-label style="word-break: break-word;">{{ item.descripcion }}</q-item-label>
                                        <q-item-label caption>
                                            {{ item.cantidad }} x Bs {{ item.precioUnitario }}
                                        </q-item-label>
                                    </q-item-section>
                                    <q-item-section side>
                                        <q-item-label class="text-weight-medium">Bs {{ item.subTotal }}</q-item-label>
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
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";
import { QrcodeStream } from "vue-qrcode-reader";
import axios from "axios";

defineProps({
    title: String,
    errors: Object,
});

const $q = useQuasar();
const page = usePage();
let message = ref(page.props.flash.message);
let type = ref(page.props.flash.type);
let document = ref({});

const options = ref({
    accounts: null,
    documents: page.props.documents,
    suppliers: page.props.suppliers,
    distribution: page.props.distribution,
    projects: page.props.projects,
});

const loading = ref({
    submit: false,
});

const form = ref(page.props.data);
const initialForm = JSON.stringify(page.props.data);

const isDirty = computed(() => {
    return JSON.stringify(form.value) !== initialForm;
});

// QR Scanner state
const showQrScanner = ref(false);
const qrLoading = ref(false);
const qrManualUrl = ref("");
const qrCameraError = ref(null);
const scanMethod = ref("camera");
const scannedData = ref(null);
const showPreview = ref(false);
const facturaOriginal = ref(null);
const showFacturaDialog = ref(false);

const prefersReducedMotion = computed(() => {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
});

function handleBeforeUnload(e) {
    if (isDirty.value) {
        e.preventDefault();
        e.returnValue = "";
    }
}

onMounted(() => {
    options.value.accounts = page.props.accounts;
    options.value.documents = page.props.documents;
    form.value.document_id = parseInt(form.value.document_id);
    document.value = options.value.documents.find(
        (e) => e.id == form.value.document_id
    );

    // Map distribution rule values to matching option references
    const distFields = [
        { field: "distribution_rule_one", dim: 1 },
        { field: "distribution_rule_second", dim: 2 },
        { field: "distribution_rule_three", dim: 3 },
        { field: "distribution_rule_four", dim: 4 },
        { field: "distribution_rule_five", dim: 5 },
    ];
    distFields.forEach(({ field, dim }) => {
        const val = form.value[field];
        if (val && typeof val === "object" && options.value.distribution[dim]) {
            const code = val.OcrCode || val.PrcCode;
            if (code) {
                form.value[field] =
                    options.value.distribution[dim].find(
                        (o) => (o.PrcCode || o.OcrCode) === code
                    ) || null;
            } else {
                form.value[field] = null;
            }
        } else if (!val || (typeof val === "object" && Object.keys(val).length === 0)) {
            form.value[field] = null;
        }
    });

    window.addEventListener("beforeunload", handleBeforeUnload);
});

onUnmounted(() => {
    window.removeEventListener("beforeunload", handleBeforeUnload);
});

function HandleCancel() {
    const detailRoute = route(
        "panel.accountability.manage.detail.index",
        [page.props.profile.id, page.props.accountability.id]
    );
    if (isDirty.value) {
        $q.dialog({
            title: "Cambios sin guardar",
            message:
                "Tienes cambios sin guardar. ¿Estas seguro de que deseas salir?",
            cancel: { label: "Quedarse", flat: true, noCaps: true },
            ok: { label: "Salir", color: "negative", noCaps: true },
            persistent: true,
        }).onOk(() => {
            router.visit(detailRoute);
        });
    } else {
        router.visit(detailRoute);
    }
}

function HandleChangeNIT() {
    let result = options.value.suppliers.find(
        (e) => e.nit == form.value.nit
    );
    form.value.business_name = result
        ? result.business_name
        : form.value.business_name;
}

function HandleChangeBusinessName() {
    let result = options.value.suppliers.find(
        (e) => e.business_name == form.value.business_name
    );
    form.value.nit = result ? result.nit : form.value.nit;
}

function HandleUpdateForm() {
    loading.value.submit = true;
    router.post(
        route("panel.accountability.manage.detail.update", [
            page.props.profile.id,
            page.props.accountability.id,
        ]),
        form.value,
        {
            onSuccess: () => {
                message.value = page.props.flash.message;
                type.value = page.props.flash.type;
                $q.notify({
                    type: type.value,
                    message: message.value,
                });
            },
            onFinish: () => {
                loading.value.submit = false;
            },
        }
    );
}

function HandleFilterAccounts(val, update) {
    if (val === "") {
        update(() => {
            options.value.accounts = page.props.accounts;
        });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.accounts = page.props.accounts.filter(
            (v) => v.label.toLowerCase().indexOf(needle) > -1
        );
    });
}

function HandleFilterProjects(val, update) {
    if (val === "") {
        update(() => {
            options.value.projects = page.props.projects;
        });
        return;
    }
    update(() => {
        const needle = val.toLowerCase();
        options.value.projects = page.props.projects.filter(
            (v) => v.PrjName.toLowerCase().indexOf(needle) > -1
        );
    });
}

function HandleFindDocument() {
    form.value.field = [];
    document.value = options.value.documents.find(
        (e) => e.id == form.value.document_id
    );
    document.value.fields.forEach((e) => {
        form.value.field.push({ name: e.name, value: null, id: e.id });
    });
}

// QR Scanner functions
function onQrDetect(detectedCodes) {
    if (detectedCodes.length > 0) {
        HandleConsultaSiat(detectedCodes[0].rawValue);
    }
}

function onQrError(error) {
    const messages = {
        NotAllowedError: "Se necesita permiso para acceder a la camara.",
        NotFoundError: "No se encontro una camara en este dispositivo.",
        NotReadableError:
            "La camara esta siendo utilizada por otra aplicacion.",
        OverconstrainedError: "La camara no es compatible.",
        StreamApiNotSupportedError:
            "Este navegador no soporta la API de camara. Use HTTPS.",
    };
    qrCameraError.value =
        messages[error.name] || "Error de camara: " + error.message;
}

async function HandleConsultaSiat(url) {
    if (!url) return;
    qrLoading.value = true;
    try {
        const response = await axios.post(route("siat.consulta"), { url });
        const factura = response.data;

        if (factura.error) {
            $q.notify({ type: "negative", message: factura.error });
            return;
        }

        facturaOriginal.value = factura;

        scannedData.value = {
            document_number: factura.numeroFactura || null,
            cuf: factura.cuf || null,
            nit: factura.nitEmisor ? String(factura.nitEmisor) : null,
            business_name: factura.razonSocialEmisor || null,
            amount: factura.montoTotal || null,
            date: factura.fechaEmision
                ? factura.fechaEmision.substring(0, 10)
                : null,
            detalles: factura.listaDetalle || [],
        };

        showPreview.value = true;
        qrManualUrl.value = "";
    } catch (error) {
        const msg =
            error.response?.data?.error || "Error al consultar SIAT";
        $q.notify({ type: "negative", message: msg });
    } finally {
        qrLoading.value = false;
    }
}

function applyScannedData() {
    if (!scannedData.value) return;

    Object.entries(scannedData.value).forEach(([key, value]) => {
        if (value !== null && key !== "detalles" && key in form.value) {
            form.value[key] = value;
        }
    });

    if (scannedData.value.nit) {
        HandleChangeNIT();
    }

    showQrScanner.value = false;
    showPreview.value = false;
    scannedData.value = null;
    $q.notify({
        type: "positive",
        message: "Datos de factura aplicados correctamente",
    });
}

function cancelPreview() {
    scannedData.value = null;
    showPreview.value = false;
}

function handleCloseScanner() {
    showQrScanner.value = false;
    qrManualUrl.value = "";
    qrCameraError.value = null;
    scannedData.value = null;
    showPreview.value = false;
    scanMethod.value = "camera";
}

function retryCamera() {
    qrCameraError.value = null;
}
</script>

<style scoped>
.qr-dialog-card {
    overscroll-behavior: contain;
}

.bordered {
    border: 1px solid rgba(0, 0, 0, 0.12);
}

.qr-guide-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.qr-guide-frame {
    width: 250px;
    height: 250px;
    border: 3px solid rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
}

.qr-guide-text {
    margin-top: 1.5rem;
    color: #fff;
    font-size: 0.875rem;
    text-align: center;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
    padding: 0.5rem 1rem;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 8px;
}

@media (prefers-reduced-motion: reduce) {
    .q-dialog__inner--animating {
        transition: none !important;
    }
}
</style>
