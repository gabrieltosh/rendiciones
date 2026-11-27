<template>
    <q-layout view="lHh Lpr fFf" class="backgroup-theme">
        <q-header class="q-ma-sm header-theme" height-hint="58">
            <q-toolbar class="q-py-xs">
                <q-btn data-tour="menu-toggle"
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

                <q-btn data-tour="help-button"
                    id="tour-help-btn"
                    flat
                    dense
                    round
                    icon="help_outline"
                    aria-label="Ver guía de esta pantalla"
                    class="text-profile q-mr-xs"
                    @click="startTour()"
                >
                    <q-tooltip>Ver guía de esta pantalla</q-tooltip>
                </q-btn>

                <q-btn data-tour="dark-mode"
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

                <q-btn data-tour="user-menu" flat no-caps class="user-btn q-px-sm" aria-label="Menú de usuario">
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
                            <q-item clickable v-close-popup @click="startTour()">
                                <q-item-section avatar>
                                    <q-icon name="help_outline" />
                                </q-item-section>
                                <q-item-section>Guía de la pantalla</q-item-section>
                            </q-item>
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

        <q-drawer data-tour="main-menu"
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
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar, LocalStorage } from "quasar";
import { installFlashNotifications, notifyInitialFlash } from "@/utils/flash";
import { installTours, offerInitialTour } from "@/tours/engine";

// Notificación global de mensajes flash (ver utils/flash.js): un único listener
installFlashNotifications();
// Manual interactivo (ver tours/engine.js): botón "?" y guía de primera visita
installTours();
onMounted(() => {
    notifyInitialFlash();
    offerInitialTour();
});

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
                    label: "Proveedores",
                    icon: "eva-car-outline",
                    route: "panel.supplier.index",
                    active: isCurrent("panel.supplier.*"),
                },
            ],
        });
    }

    if (isAdminOrAutorizador.value) {
        const reports = [
            {
                label: "Reporte de Rendiciones",
                icon: "eva-bar-chart-outline",
                route: "panel.report.accountability",
                active: isCurrent("panel.report.accountability", "panel.report.accountability.*"),
            },
        ];
        if (isAdmin.value) {
            reports.push({
                label: "Log de Auditoría",
                icon: "eva-clock-outline",
                route: "panel.report.audit-log",
                active: isCurrent("panel.report.audit-log"),
            });
        }
        sections.push({ label: "Reportes", items: reports });
    }

    return sections;
});

// El motor de guías (tours/engine.js) escucha este evento
function startTour() {
    window.dispatchEvent(new CustomEvent("start-tour"));
}

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
