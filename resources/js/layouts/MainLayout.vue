<template>
    <q-layout view="LHh Lpr lff" class="backgroup-theme">
        <q-header class="header-theme" height-hint="56">
            <q-toolbar>
                <q-btn data-tour="menu-toggle"
                    dense
                    flat
                    round
                    icon="menu"
                    aria-label="Abrir o cerrar menú"
                    @click="toggleLeftDrawer"
                    class="navbar-btn"
                />

                <nav class="breadcrumb q-ml-xs" aria-label="Ubicación">
                    <template v-if="currentSection && $q.screen.gt.xs">
                        <span class="breadcrumb__section">{{ currentSection }}</span>
                        <q-icon name="chevron_right" class="breadcrumb__sep" aria-hidden="true" />
                    </template>
                    <span class="header-title ellipsis" aria-current="page">{{ page.props.title }}</span>
                </nav>

                <q-space />

                <q-btn data-tour="help-button"
                    id="tour-help-btn"
                    flat
                    dense
                    no-caps
                    icon="help_outline"
                    :label="$q.screen.gt.xs ? 'Ayuda' : undefined"
                    aria-label="Ayuda: tutoriales de esta pantalla"
                    class="navbar-btn q-mr-xs q-px-sm"
                >
                    <q-badge v-if="tutorials.length > 1" floating rounded color="primary" :label="tutorials.length" />
                    <q-menu anchor="bottom right" self="top right" :offset="[0, 8]" class="help-menu">
                        <q-list style="min-width: 300px; max-width: 360px;">
                            <q-item-label header class="text-weight-medium">Tutoriales de esta pantalla</q-item-label>
                            <q-item
                                v-for="tutorial in tutorials"
                                :key="tutorial.id"
                                clickable
                                v-close-popup
                                @click="startTour(tutorial.id)"
                            >
                                <q-item-section avatar>
                                    <q-avatar size="32px" :color="tutorial.id === 'overview' ? 'blue-1' : 'teal-1'"
                                        :text-color="tutorial.id === 'overview' ? 'primary' : 'teal-8'" :icon="tutorial.icon" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label>{{ tutorial.title }}</q-item-label>
                                    <q-item-label caption lines="2">{{ tutorial.description }}</q-item-label>
                                </q-item-section>
                            </q-item>
                            <q-item v-if="!tutorials.length">
                                <q-item-section class="text-grey">Esta pantalla todavía no tiene tutoriales.</q-item-section>
                            </q-item>
                        </q-list>
                    </q-menu>
                </q-btn>

                <q-btn data-tour="dark-mode"
                    flat
                    dense
                    round
                    :icon="$q.dark.isActive ? 'eva-sun-outline' : 'eva-moon-outline'"
                    :aria-label="$q.dark.isActive ? 'Usar modo claro' : 'Usar modo oscuro'"
                    class="navbar-btn q-mr-xs gt-xs"
                    @click="toggleDarkMode()"
                >
                    <q-tooltip>{{ $q.dark.isActive ? 'Modo claro' : 'Modo oscuro' }}</q-tooltip>
                </q-btn>

                <q-btn data-tour="user-menu" flat no-caps class="user-btn" aria-label="Menú de usuario">
                    <q-avatar color="primary" text-color="white" size="28px" class="user-avatar">
                        {{ initials }}
                    </q-avatar>
                    <div class="column items-start q-ml-sm gt-xs" style="line-height: 1.25;">
                        <span class="user-name ellipsis">{{ user.name }}</span>
                        <span class="user-role">{{ user.type }}</span>
                    </div>
                    <q-icon name="expand_more" size="18px" class="q-ml-xs text-grey gt-xs" />

                    <q-menu anchor="bottom right" self="top right" :offset="[0, 8]" class="user-menu">
                        <q-list style="min-width: 240px;">
                            <q-item>
                                <q-item-section avatar>
                                    <q-avatar color="primary" text-color="white" size="36px" class="user-avatar">
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
                            <q-item v-if="tutorials.length" clickable v-close-popup @click="startTour()">
                                <q-item-section avatar>
                                    <q-icon name="help_outline" />
                                </q-item-section>
                                <q-item-section>Recorrido de la pantalla</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup @click="openPalette()">
                                <q-item-section avatar>
                                    <q-icon name="eva-search-outline" />
                                </q-item-section>
                                <q-item-section>Ir a…</q-item-section>
                                <q-item-section side>
                                    <span class="kbd-group" aria-hidden="true">
                                        <kbd class="kbd">{{ modKey }}</kbd><kbd class="kbd">K</kbd>
                                    </span>
                                </q-item-section>
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
            bordered
            v-model="leftDrawerOpen"
            side="left"
            :width="256"
            class="menu"
        >
            <div class="sidebar">
                <div class="sidebar__brand">
                    <q-avatar square size="30px" font-size="18px" class="brand-icon" icon="eva-briefcase-outline" text-color="white" />
                    <div style="min-width: 0;">
                        <div class="brand-title ellipsis">Rendiciones</div>
                        <div class="brand-caption ellipsis">Gastos y viáticos</div>
                    </div>
                </div>

                <div class="sidebar__search">
                    <button
                        type="button"
                        class="search-trigger"
                        aria-haspopup="dialog"
                        aria-keyshortcuts="Control+K Meta+K"
                        aria-label="Buscar pantalla (Ctrl+K)"
                        @click="openPalette()"
                    >
                        <q-icon name="eva-search-outline" size="16px" />
                        <span class="search-trigger__label">Buscar…</span>
                        <span class="kbd-group" aria-hidden="true">
                            <kbd class="kbd">{{ modKey }}</kbd><kbd class="kbd">K</kbd>
                        </span>
                    </button>
                </div>

                <q-scroll-area class="sidebar__nav">
                    <q-list class="q-px-sm q-pb-sm" aria-label="Menú principal">
                        <template v-for="section in menu" :key="section.label">
                            <q-item-label header class="menu-section">{{ section.label }}</q-item-label>
                            <q-item
                                v-for="item in section.items"
                                :key="item.route"
                                clickable
                                class="menu-item"
                                :active="item.active"
                                active-class="menu-item--active"
                                :aria-current="item.active ? 'page' : undefined"
                                @click="router.visit(route(item.route))"
                            >
                                <q-item-section avatar>
                                    <q-icon :name="item.icon" class="menu-icon" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label class="ellipsis">{{ item.label }}</q-item-label>
                                </q-item-section>
                                <q-item-section v-if="item.count" side>
                                    <q-badge :color="item.countColor || 'orange'" :label="item.count" rounded />
                                </q-item-section>
                            </q-item>
                        </template>
                    </q-list>
                </q-scroll-area>

                <div class="sidebar__footer">
                    © {{ new Date().getFullYear() }} <strong>TOMATE FÁCIL</strong>. Todos los derechos reservados.
                </div>
            </div>
        </q-drawer>

        <q-page-container>
            <slot />
        </q-page-container>

        <!-- Navegación rápida (Ctrl/Cmd + K): filtra las opciones del menú lateral -->
        <q-dialog
            v-model="paletteOpen"
            position="top"
            @show="focusPaletteInput"
            @hide="paletteQuery = ''"
        >
            <q-card class="command-palette" aria-label="Navegación rápida">
                <div class="command-palette__input">
                    <q-icon name="eva-search-outline" size="18px" />
                    <input
                        ref="paletteInput"
                        v-model="paletteQuery"
                        type="text"
                        role="combobox"
                        aria-label="Buscar pantalla"
                        aria-autocomplete="list"
                        aria-expanded="true"
                        aria-controls="command-palette-list"
                        :aria-activedescendant="paletteResults.length ? `command-option-${paletteIndex}` : undefined"
                        placeholder="Buscar pantalla…"
                        autocomplete="off"
                        spellcheck="false"
                        @keydown="onPaletteKeydown"
                    />
                    <kbd class="kbd" aria-hidden="true">Esc</kbd>
                </div>
                <div id="command-palette-list" ref="paletteList" class="command-palette__list" role="listbox" aria-label="Pantallas">
                    <div v-for="group in paletteGroups" :key="group.label" role="group" :aria-label="group.label">
                        <div class="command-palette__group" aria-hidden="true">{{ group.label }}</div>
                        <div
                            v-for="item in group.items"
                            :id="`command-option-${item.index}`"
                            :key="item.route"
                            role="option"
                            :aria-selected="item.index === paletteIndex ? 'true' : 'false'"
                            class="command-palette__item"
                            :class="{ 'is-active': item.index === paletteIndex, 'is-current': item.active }"
                            @click="goTo(item)"
                            @mousemove="paletteIndex = item.index"
                        >
                            <q-icon :name="item.icon" />
                            <span class="ellipsis">{{ item.label }}</span>
                            <span class="command-palette__current">Pantalla actual</span>
                        </div>
                    </div>
                    <div v-if="!paletteResults.length" class="command-palette__empty" role="status">
                        Sin resultados para “{{ paletteQuery }}”
                    </div>
                </div>
                <div class="command-palette__footer gt-xs" aria-hidden="true">
                    <span><kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd> navegar</span>
                    <span><kbd class="kbd">↵</kbd> abrir</span>
                    <span><kbd class="kbd">Esc</kbd> cerrar</span>
                </div>
            </q-card>
        </q-dialog>
    </q-layout>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar, LocalStorage } from "quasar";
import { installFlashNotifications, notifyInitialFlash } from "@/utils/flash";
import { installTours, tutorialsFor, OVERVIEW_ID } from "@/tours/engine";

// Notificación global de mensajes flash (ver utils/flash.js): un único listener
installFlashNotifications();
// Manual interactivo (ver tours/engine.js): solo se abre desde el botón "Ayuda"
installTours();
onMounted(() => notifyInitialFlash());

const leftDrawerOpen = ref(false);
const page = usePage();

// Tutoriales de la pantalla actual (recorrido + procesos), según el rol del usuario
const tutorials = computed(() => tutorialsFor(page.component, page.props));
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

// Sección del menú de la pantalla actual (migas de pan del navbar)
const currentSection = computed(() => {
    const section = menu.value.find((s) => s.items.some((item) => item.active));
    if (!section) return null;
    const item = section.items.find((i) => i.active);
    // "Inicio" ya es la raíz: no se repite como sección
    return item && item.route !== "home" ? section.label : null;
});

// ── Navegación rápida (Ctrl/Cmd + K) ────────────────────────────
const isMac = typeof navigator !== "undefined" && /Mac|iPhone|iPad/i.test(navigator.platform || navigator.userAgent);
const modKey = isMac ? "⌘" : "Ctrl";
const paletteOpen = ref(false);
const paletteQuery = ref("");
const paletteIndex = ref(0);
const paletteInput = ref(null);
const paletteList = ref(null);

// Comparación sin tildes ni mayúsculas ("areas" encuentra "Áreas")
function normalize(text) {
    return String(text ?? "")
        .normalize("NFD")
        .replace(/[̀-ͯ]/g, "")
        .toLowerCase()
        .trim();
}

const paletteResults = computed(() => {
    const terms = normalize(paletteQuery.value).split(/\s+/).filter(Boolean);
    const results = [];
    menu.value.forEach((section) => {
        section.items.forEach((item) => {
            const haystack = normalize(`${item.label} ${section.label}`);
            if (terms.every((term) => haystack.includes(term))) {
                results.push({ ...item, section: section.label, index: results.length });
            }
        });
    });
    return results;
});

const paletteGroups = computed(() => {
    const groups = [];
    paletteResults.value.forEach((item) => {
        let group = groups.find((g) => g.label === item.section);
        if (!group) {
            group = { label: item.section, items: [] };
            groups.push(group);
        }
        group.items.push(item);
    });
    return groups;
});

watch(paletteQuery, () => (paletteIndex.value = 0));

function openPalette() {
    paletteIndex.value = 0;
    paletteOpen.value = true;
}

function focusPaletteInput() {
    nextTick(() => paletteInput.value?.focus());
}

function scrollActiveOption() {
    nextTick(() => {
        const option = paletteList.value?.querySelector(`#command-option-${paletteIndex.value}`);
        option?.scrollIntoView({ block: "nearest" });
    });
}

function onPaletteKeydown(event) {
    const total = paletteResults.value.length;
    if (event.key === "ArrowDown" && total) {
        event.preventDefault();
        paletteIndex.value = (paletteIndex.value + 1) % total;
        scrollActiveOption();
    } else if (event.key === "ArrowUp" && total) {
        event.preventDefault();
        paletteIndex.value = (paletteIndex.value - 1 + total) % total;
        scrollActiveOption();
    } else if (event.key === "Home" && total) {
        event.preventDefault();
        paletteIndex.value = 0;
        scrollActiveOption();
    } else if (event.key === "End" && total) {
        event.preventDefault();
        paletteIndex.value = total - 1;
        scrollActiveOption();
    } else if (event.key === "Enter") {
        event.preventDefault();
        const item = paletteResults.value[paletteIndex.value];
        if (item) goTo(item);
    }
}

function goTo(item) {
    paletteOpen.value = false;
    // Bajo 1024px el menú lateral es un drawer superpuesto: se cierra al navegar
    if ($q.screen.lt.md) leftDrawerOpen.value = false;
    router.visit(route(item.route));
}

function onGlobalKeydown(event) {
    if ((event.ctrlKey || event.metaKey) && !event.altKey && event.key?.toLowerCase() === "k") {
        event.preventDefault();
        if (paletteOpen.value) {
            paletteOpen.value = false;
        } else {
            openPalette();
        }
    }
}

onMounted(() => window.addEventListener("keydown", onGlobalKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", onGlobalKeydown));

// El motor de guías (tours/engine.js) escucha este evento
function startTour(id = OVERVIEW_ID) {
    window.dispatchEvent(new CustomEvent("start-tour", { detail: { id } }));
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
        ok: { label: "Cerrar sesión", color: "negative", noCaps: true, unelevated: true },
        persistent: true,
    }).onOk(() => {
        router.post(route("auth.logout"));
    });
}
</script>
