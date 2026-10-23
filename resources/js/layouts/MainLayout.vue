<template>
    <q-layout view="lHh Lpr fFf" class="backgroup-theme">
        <q-header class="q-ma-sm header-theme" height-hint="58">
            <q-toolbar class="q-py-xs">
                <q-btn
                    dense
                    flat
                    round
                    icon="menu"
                    aria-label="Abrir o cerrar menú"
                    @click="toggleLeftDrawer"
                    class="text-profile"
                />

                <div class="header-title text-profile ellipsis q-ml-sm">
                    {{ page.props.title }}
                </div>

                <q-space />

                <q-btn
                    flat
                    dense
                    round
                    :icon="$q.dark.isActive ? 'eva-sun-outline' : 'eva-moon-outline'"
                    :aria-label="$q.dark.isActive ? 'Usar modo claro' : 'Usar modo oscuro'"
                    class="text-profile q-mr-xs gt-xs"
                    @click="toggleDarkMode()"
                >
                    <q-tooltip>{{ $q.dark.isActive ? 'Modo claro' : 'Modo oscuro' }}</q-tooltip>
                </q-btn>

                <q-btn flat no-caps class="user-btn q-px-sm" aria-label="Menú de usuario">
                    <q-avatar color="primary" text-color="white" size="32px" class="text-weight-medium">
                        {{ initials }}
                    </q-avatar>
                    <div class="column items-start q-ml-sm gt-xs" style="line-height: 1.2;">
                        <span class="text-profile user-name ellipsis">{{ user.name }}</span>
                        <span class="text-caption text-grey">{{ user.type }}</span>
                    </div>
                    <q-icon name="expand_more" size="18px" class="q-ml-xs text-grey gt-xs" />

                    <q-menu anchor="bottom right" self="top right" :offset="[0, 8]" class="user-menu">
                        <q-list style="min-width: 240px;">
                            <q-item>
                                <q-item-section avatar>
                                    <q-avatar color="primary" text-color="white" size="40px">
                                        {{ initials }}
                                    </q-avatar>
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label class="text-weight-medium">{{ user.name }}</q-item-label>
                                    <q-item-label caption>{{ user.email || user.username }}</q-item-label>
                                    <q-item-label caption>
                                        <q-badge color="blue-grey-1" text-color="blue-grey-9" :label="user.type" />
                                    </q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-separator />
                            <q-item clickable @click="toggleDarkMode()">
                                <q-item-section avatar>
                                    <q-icon :name="$q.dark.isActive ? 'eva-sun-outline' : 'eva-moon-outline'" />
                                </q-item-section>
                                <q-item-section>Modo oscuro</q-item-section>
                                <q-item-section side>
                                    <q-toggle
                                        :model-value="$q.dark.isActive"
                                        dense
                                        color="primary"
                                        aria-label="Modo oscuro"
                                        @update:model-value="toggleDarkMode()"
                                    />
                                </q-item-section>
                            </q-item>
                            <q-separator />
                            <q-item clickable v-close-popup class="text-negative" @click="HandleLogout()">
                                <q-item-section avatar>
                                    <q-icon name="eva-log-out-outline" />
                                </q-item-section>
                                <q-item-section>Cerrar sesión</q-item-section>
                            </q-item>
                        </q-list>
                    </q-menu>
                </q-btn>
            </q-toolbar>
        </q-header>

        <q-drawer
            show-if-above
            v-model="leftDrawerOpen"
            side="left"
            :width="260"
            class="menu q-pa-sm"
        >
            <q-scroll-area class="fit drawer-theme">
                <div class="brand row items-center no-wrap q-px-md q-pt-md q-pb-sm">
                    <q-avatar square size="36px" class="brand-icon" icon="eva-briefcase-outline" text-color="white" />
                    <div class="q-ml-sm">
                        <div class="brand-title">Rendiciones</div>
                        <div class="text-caption text-grey">Gastos y viáticos</div>
                    </div>
                </div>

                <q-list padding class="q-px-sm">
                    <template v-for="section in menu" :key="section.label">
                        <q-item-label header class="menu-section">{{ section.label }}</q-item-label>
                        <q-item
                            v-for="item in section.items"
                            :key="item.route"
                            clickable
                            v-ripple
                            class="menu-item q-mb-xs"
                            :active="item.active"
                            active-class="menu-item--active"
                            @click="router.visit(route(item.route))"
                        >
                            <q-item-section avatar>
                                <q-avatar square size="32px" class="icon-theme menu-icon" :icon="item.icon" />
                            </q-item-section>
                            <q-item-section>
                                <q-item-label class="text-menu">{{ item.label }}</q-item-label>
                            </q-item-section>
                            <q-item-section v-if="item.count" side>
                                <q-badge :color="item.countColor || 'orange'" :label="item.count" rounded />
                            </q-item-section>
                        </q-item>
                    </template>
                </q-list>
            </q-scroll-area>
        </q-drawer>

        <q-page-container>
            <slot />
        </q-page-container>
        <q-footer class="footer-theme">
            <div class="text-center q-pa-xs">
                Copyright © {{ new Date().getFullYear() }}
                <strong>TOMATE FÁCIL</strong>, Todos los derechos reservados.
            </div>
        </q-footer>
    </q-layout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar, LocalStorage } from "quasar";

const leftDrawerOpen = ref(false);
const page = usePage();
const $q = useQuasar();
const user = page.props.auth.user;

const isAdmin = computed(() => user.type === "Administrador");
const isAdminOrAutorizador = computed(
    () => user.type === "Administrador" || user.type === "Autorizador"
);

const initials = computed(() =>
    user.name
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join("")
);

function isCurrent(...patterns) {
    return patterns.some((pattern) => route().current(pattern));
}

// El detalle de una rendición se abre desde "Pendientes SAP" con ?from=pending-export
const fromPendingExport = computed(() => page.url.includes("from=pending-export"));

const menu = computed(() => {
    const counts = page.props.menu_counts || {};
    const pendingSapActive =
        isCurrent("panel.accountability.authorization.pending-export") ||
        (isCurrent("panel.accountability.authorization.*") && fromPendingExport.value);

    const sections = [
        {
            label: "Principal",
            items: [
                {
                    label: "Inicio",
                    icon: "eva-home-outline",
                    route: "home",
                    active: isCurrent("home"),
                },
                {
                    label: "Mis Rendiciones",
                    icon: "eva-briefcase-outline",
                    route: "panel.accountability.profiles",
                    active: isCurrent("panel.accountability.profiles", "panel.accountability.manage.*"),
                },
            ],
        },
    ];

    if (isAdminOrAutorizador.value) {
        sections.push({
            label: "Revisión",
            items: [
                {
                    label: "Autorizaciones",
                    icon: "eva-checkmark-square-outline",
                    route: "panel.accountability.authorization.index",
                    active: isCurrent("panel.accountability.authorization.*") && !pendingSapActive,
                    count: counts.authorization,
                    countColor: "orange",
                },
                {
                    label: "Pendientes SAP",
                    icon: "eva-cloud-upload-outline",
                    route: "panel.accountability.authorization.pending-export",
                    active: pendingSapActive,
                    count: counts.pending_sap,
                    countColor: "warning",
                },
                {
                    label: "Reportes",
                    icon: "eva-bar-chart-outline",
                    route: "panel.report.accountability",
                    active: isCurrent("panel.report.accountability"),
                },
            ],
        });
    }

    if (isAdmin.value) {
        sections.push({
            label: "Administración",
            items: [
                {
                    label: "Configuración",
                    icon: "eva-settings-2-outline",
                    route: "panel.management.index",
                    active: isCurrent("panel.management.*"),
                },
                {
                    label: "Usuarios",
                    icon: "eva-people-outline",
                    route: "panel.user.index",
                    active: isCurrent("panel.user.*"),
                },
                {
                    label: "Perfiles",
                    icon: "eva-layers-outline",
                    route: "panel.profile.index",
                    active: isCurrent("panel.profile.*"),
                },
                {
                    label: "Áreas",
                    icon: "eva-pie-chart-outline",
                    route: "panel.area.index",
                    active: isCurrent("panel.area.*"),
                },
                {
                    label: "Ciclos de Autorización",
                    icon: "eva-shuffle-2-outline",
                    route: "panel.authorization-cycle.index",
                    active: isCurrent("panel.authorization-cycle.*"),
                },
                {
                    label: "Alias de Cuentas",
                    icon: "eva-pricetags-outline",
                    route: "panel.account-alias.index",
                    active: isCurrent("panel.account-alias.*"),
                },
                {
                    label: "Log de Auditoría",
                    icon: "eva-clock-outline",
                    route: "panel.report.audit-log",
                    active: isCurrent("panel.report.audit-log"),
                },
            ],
        });
    }

    return sections;
});

function toggleLeftDrawer() {
    leftDrawerOpen.value = !leftDrawerOpen.value;
}

function toggleDarkMode() {
    $q.dark.toggle();
    LocalStorage.set("dark-mode", $q.dark.isActive);
}

function HandleLogout() {
    $q.dialog({
        title: "Cerrar sesión",
        message: "¿Está seguro de cerrar la sesión?",
        cancel: { label: "Cancelar", flat: true, noCaps: true },
        ok: { label: "Cerrar sesión", color: "negative", noCaps: true },
        persistent: true,
    }).onOk(() => {
        router.post(route("auth.logout"));
    });
}
</script>
<style lang="scss">
* {
    font-family: "Poppins", sans-serif;
}
.text-banner {
    font-size: 0.75rem;
    font-weight: 500;
}
.text-profile {
    color: black;
}
.icon-theme {
    border-radius: 10px;
    box-shadow: rgba(0, 0, 0, 0.1) 0rem 0.25rem 0.375rem -0.0625rem,
        rgba(0, 0, 0, 0.06) 0rem 0.125rem 0.25rem -0.0625rem;
}
.header-theme {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: rgba(0, 0, 0, 0.1) 0rem 0.25rem 0.375rem -0.0625rem,
        rgba(0, 0, 0, 0.06) 0rem 0.125rem 0.25rem -0.0625rem;
}
.drawer-theme {
    background-color: white;
    border-radius: 15px;
    box-shadow: rgba(0, 0, 0, 0.1) 0rem 0.25rem 0.375rem -0.0625rem,
        rgba(0, 0, 0, 0.06) 0rem 0.125rem 0.25rem -0.0625rem;
    border-radius: 0.5rem;
}
.footer-theme {
    font-size: 12px;
    font-family: Arial, Helvetica, sans-serif;
    color: #ffffff;
    box-shadow: rgba(0, 0, 0, 0.1) 0rem 0.25rem 0.375rem -0.0625rem,
        rgba(0, 0, 0, 0.06) 0rem 0.125rem 0.25rem -0.0625rem;
    background-color: #21283c;
}
.backgroup-theme {
    background-color: #f0f2f5;
}
.menu {
    background-color: #f0f2f5;
    font-size: 12.5px;
}
body.screen--xs {
    .menu {
        padding: 0;
        font-size: 12.5px;
    }
}
body.screen--sm {
    .menu {
        padding: 0;
        font-size: 12.5px;
    }
}
.q-toolbar {
    box-shadow: 0px 2px 7px #2553b91a;
    border-radius: 0.5rem;
}

::-webkit-scrollbar {
    height: 12px;
    width: 14px;
    background: transparent;
    z-index: 12;
    overflow: visible;
}

::-webkit-scrollbar-thumb {
    width: 10px;
    background-color: #c1c1c1;
    border-radius: 10px;
    z-index: 12;
    border: 4px solid rgba(0, 0, 0, 0);
    background-clip: padding-box;
    transition: background-color 0.32s ease-in-out;
    margin: 4px;
    min-height: 32px;
    min-width: 32px;
}

::-webkit-scrollbar-thumb:hover {
    background: #c1c1c1;
}
.body--dark {
    .q-stepper__step-inner {
        background-color: #1b2033;
    }
    .backgroup-theme {
        background-color: #1b2033;
    }
    .menu {
        background-color: #1b2033;
    }
    .drawer-theme {
        background-color: #21283c;
        box-shadow: rgba(0, 0, 0, 0.14) 0rem 0.125rem 0.125rem 0rem,
            rgba(0, 0, 0, 0.2) 0rem 0.1875rem 0.0625rem -0.125rem,
            rgba(0, 0, 0, 0.12) 0rem 0.0625rem 0.3125rem 0rem;
    }
    .header-theme {
        background-color: #21283c;
        box-shadow: rgba(0, 0, 0, 0.14) 0rem 0.125rem 0.125rem 0rem,
            rgba(0, 0, 0, 0.2) 0rem 0.1875rem 0.0625rem -0.125rem,
            rgba(0, 0, 0, 0.12) 0rem 0.0625rem 0.3125rem 0rem;
    }
    .text-profile {
        color: white;
    }
    .icon-theme {
        box-shadow: rgba(0, 0, 0, 0.14) 0rem 0.125rem 0.125rem 0rem,
            rgba(0, 0, 0, 0.2) 0rem 0.1875rem 0.0625rem -0.125rem,
            rgba(0, 0, 0, 0.12) 0rem 0.0625rem 0.3125rem 0rem;
    }
    .footer-theme {
        color: #ffffff;
        box-shadow: rgba(0, 0, 0, 0.14) 0rem 0.125rem 0.125rem 0rem,
            rgba(0, 0, 0, 0.2) 0rem 0.1875rem 0.0625rem -0.125rem,
            rgba(0, 0, 0, 0.12) 0rem 0.0625rem 0.3125rem 0rem;
        background-color: #21283c;
    }
    .table-theme {
        color: white;
        background-color: #21283c;
        .q-table th {
            color: white;
        }
    }
    .title-form {
        color: white;
    }
    .form-label {
        color: white;
    }
    .card-form {
        background-color: #21283c;
        box-shadow: rgba(0, 0, 0, 0.14) 0rem 0.125rem 0.125rem 0rem,
            rgba(0, 0, 0, 0.2) 0rem 0.1875rem 0.0625rem -0.125rem,
            rgba(0, 0, 0, 0.12) 0rem 0.0625rem 0.3125rem 0rem;
    }
    .title-tree {
        color: white;
    }
    .tree-label {
        color: white;
    }
}

//INPUT
.input-theme.q-tab__label {
    font-size: 12px;
}

.input-theme.q-field--auto-height.q-field--dense .q-field__control,
.q-field--auto-height.q-field--dense .q-field__native {
    min-height: 25px;
}

.input-theme.q-field--dense .q-field__control,
.q-field--dense .q-field__marginal {
    height: 25px;
}

.input-theme.q-field {
    font-size: 12px;
}

.input-theme-area.q-field {
    font-size: 12px;
}

.input-theme {
    .q-field__native,
    .q-field__prefix,
    .q-field__suffix,
    .q-field__input {
        padding: 0;
    }
    .q-field--outlined .q-field__control {
        border-radius: 0.5rem;
    }
}
.text-grid {
    font-size: 12px;
    white-space: normal;
}
.title-grid {
    font-weight: bold;
    color: #344767;
    font-size: 11.5px;
}
.card-grid {
    border-radius: 1rem;
}
//TABLE
.table-theme {
    box-shadow: 0 20px 27px 10px rgba(0, 0, 0, 0);
    border-radius: 1rem;
    color: #344767;
    .q-table__top {
        padding: 8px 20px;
    }
    .q-table th {
        font-weight: bold;
        color: #344767;
        font-size: 11.5px;
    }
    .q-table tbody td {
        font-size: 11.5px;
        white-space: normal;
    }
    .q-field {
        font-size: 12px;
    }
}

.table-header-theme {
    color: #344767;
}

//Other

.card-form {
    border-radius: 1rem;
    box-shadow: rgba(0, 0, 0, 0.1) 0rem 0.25rem 0.375rem -0.0625rem,
        rgba(0, 0, 0, 0.06) 0rem 0.125rem 0.25rem -0.0625rem;
}
.title-form {
    color: #344767;
    font-style: normal;
    font-weight: 500;
    margin-bottom: 0rem;
    margin-top: 0;
    font-size: 0.9rem;
}
.title-tree {
    color: #344767;
    font-style: normal;
    font-weight: 500;
    margin-bottom: 0rem;
    margin-top: 0;
    font-size: 0.8rem;
}

.q-btn--rectangle {
    border-radius: 0.5rem;
}
.form-label {
    color: #344767;
    font-size: 0.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    margin-left: 0.25rem;
}
.tree-label {
    color: #344767;
    font-size: 0.75rem;
    font-weight: 400;
}

//validation
.container-error {
    margin: 4px 0 0 2px;
}
.message-error {
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 11.5px;
    line-height: 1.4;
    color: $negative;
}

//FORMULARIOS
.form-field {
    .form-label {
        display: block;
    }
}
.form-field__error {
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 4px 0 0 2px;
    font-size: 11.5px;
    line-height: 1.3;
    color: $negative;
}
.form-field__hint {
    margin: 4px 0 0 2px;
    font-size: 11.5px;
    color: #7b809a;
}
.form-field--error .q-field--outlined .q-field__control:before,
// Formularios con el bloque de error clásico debajo del campo
div:has(> .container-error) > .q-field--outlined .q-field__control:before {
    border-color: $negative;
}
.form-section-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #344767;
}
.form-section-caption {
    font-size: 0.78rem;
    color: #7b809a;
    margin-top: 2px;
}
.form-tabs {
    .q-tab {
        min-height: 44px;
    }
    .q-tab__label {
        font-size: 0.8rem;
        font-weight: 500;
    }
}
.body--dark {
    .form-section-title {
        color: white;
    }
    .form-section-caption,
    .form-field__hint {
        color: rgba(255, 255, 255, 0.6);
    }
}

//stepper
.q-stepper--horizontal .q-stepper__step-inner {
    padding: 0px 0px;
}
.q-stepper__header--alternative-labels .q-stepper__tab {
    padding: 10px 32px;
    min-height: 70px;
}
.q-stepper__dot {
    font-size: 16px;
    width: 24px;
    height: 24px;
}
.q-stepper__title {
    font-size: 12px;
    line-height: 18px;
    letter-spacing: 0.1px;
}
.q-stepper__step-inner {
    background-color: #f0f2f5;
}
//BUSCADOR DE LISTAS
.search-input {
    min-width: 240px;
}
body.screen--xs .search-input {
    min-width: 0;
    width: 100%;
    margin-top: 8px;
}

//NAVEGACIÓN
.header-title {
    font-size: 0.95rem;
    font-weight: 600;
    max-width: 50vw;
}
.user-btn {
    border-radius: 0.5rem;
}
.user-name {
    font-size: 0.8rem;
    font-weight: 500;
    max-width: 180px;
}
.brand-icon {
    border-radius: 10px;
    background: linear-gradient(135deg, #1a73e8, #344767);
}
.brand-title {
    font-weight: 700;
    color: #344767;
    font-size: 0.95rem;
    line-height: 1.2;
}
.menu-section {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 12px 16px 6px;
}
.menu-item {
    border-radius: 0.5rem;
    min-height: 44px;
    color: #344767;
    .menu-icon {
        background-color: white;
        color: #344767;
        font-size: 16px;
    }
}
.menu-item--active {
    background-color: rgba(26, 115, 232, 0.1);
    color: $primary;
    font-weight: 600;
    .menu-icon {
        background-color: $primary;
        color: white;
    }
}
.body--dark {
    .brand-title {
        color: white;
    }
    .menu-item {
        color: rgba(255, 255, 255, 0.85);
        .menu-icon {
            background-color: #1b2033;
            color: white;
        }
    }
    .menu-item--active {
        background-color: rgba(26, 115, 232, 0.2);
        color: white;
        .menu-icon {
            background-color: $primary;
        }
    }
}
</style>
