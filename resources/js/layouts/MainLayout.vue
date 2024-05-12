<template>
    <q-layout view="lHh Lpr fFf" class="backgroup-theme">
        <q-header class="q-ma-sm header-theme" height-hint="110">
            <q-toolbar>
                <q-btn
                    dense
                    flat
                    round
                    icon="menu"
                    @click="toggleLeftDrawer"
                    class="text-profile"
                />

                <q-space />
                <q-btn
                    dense
                    flat
                    round
                    icon="eva-log-out-outline"
                    @click="HandleLogout()"
                    class="text-profile"
                />
                <div class="q-gutter-sm row items-center no-wrap">
                    <q-item clickable v-ripple>
                        <q-item-section side>
                            <q-avatar
                                color="primary"
                                text-color="white"
                                size="33px"
                                >{{ name_profile }}</q-avatar
                            >
                        </q-item-section>
                        <q-item-section>
                            <q-item-label class="text-profile">{{
                                user.name
                            }}</q-item-label>
                            <q-item-label caption>Admin</q-item-label>
                        </q-item-section>
                    </q-item>
                </div>
            </q-toolbar>
        </q-header>

        <q-drawer
            show-if-above
            v-model="leftDrawerOpen"
            side="left"
            class="menu q-pa-sm"
        >
            <q-scroll-area class="fit drawer-theme">
                <q-list padding>
                    <q-item
                        clickable
                        class="cursor-pointer q-mx-md q-mb-md"
                        :active="active"
                        active-class="menu-inactive"
                        @click="router.visit(route('home'))"
                    >
                        <q-item-section avatar>
                            <q-avatar
                                square
                                class="icon-theme"
                                icon="eva-home-outline"
                            >
                            </q-avatar>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label class="text-menu"
                                >Inicio</q-item-label
                            >
                        </q-item-section>
                    </q-item>
                    <q-expansion-item
                        default-opened
                        class="cursor-pointer q-mx-md q-mb-md"
                        header-class="menu-active"
                    >
                        <template v-slot:header>
                            <q-item-section avatar>
                                <q-avatar
                                    square
                                    class="icon-theme"
                                    icon="eva-grid-outline"
                                >
                                </q-avatar>
                            </q-item-section>

                            <q-item-section class="text-menu">
                                Administración
                            </q-item-section>
                        </template>
                        <q-item
                            class="cursor-pointer q-ml-sm"
                            @click="
                                router.visit(route('panel.management.index'))
                            "
                            clickable
                        >
                            <q-item-section avatar>
                                <q-avatar
                                    class="submenu-icon-inactive"
                                    icon="hdr_weak"
                                >
                                </q-avatar>
                            </q-item-section>
                            <q-item-section>
                                <q-item-label class="text-menu"
                                    >Configuración</q-item-label
                                >
                            </q-item-section>
                        </q-item>
                        <q-item
                            class="cursor-pointer q-ml-sm"
                            @click="HandleVisitUser()"
                            clickable
                        >
                            <q-item-section avatar>
                                <q-avatar
                                    class="submenu-icon-inactive"
                                    icon="hdr_weak"
                                >
                                </q-avatar>
                            </q-item-section>
                            <q-item-section>
                                <q-item-label class="text-menu"
                                    >Usuarios</q-item-label
                                >
                            </q-item-section>
                        </q-item>
                        <q-item
                            class="cursor-pointer q-ml-sm"
                            clickable
                            @click="router.visit(route('panel.profile.index'))"
                        >
                            <q-item-section avatar>
                                <q-avatar
                                    class="submenu-icon-inactive"
                                    icon="hdr_weak"
                                >
                                </q-avatar>
                            </q-item-section>
                            <q-item-section>
                                <q-item-label class="text-menu"
                                    >Perfiles</q-item-label
                                >
                            </q-item-section>
                        </q-item>
                    </q-expansion-item>
                    <q-item
                        clickable
                        class="cursor-pointer q-mx-md q-mb-md"
                        :active="active"
                        active-class="menu-inactive"
                        @click="
                            router.visit(route('panel.accountability.profiles'))
                        "
                    >
                        <q-item-section avatar>
                            <q-avatar
                                square
                                class="icon-theme"
                                icon="eva-briefcase-outline"
                            >
                            </q-avatar>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label class="text-menu"
                                >Rendiciones</q-item-label
                            >
                        </q-item-section>
                    </q-item>
                    <q-item
                        clickable
                        class="cursor-pointer q-mx-md q-mb-md"
                        :active="active"
                        active-class="menu-inactive"
                        @click="
                            router.visit(route('panel.accountability.authorization.index'))
                        "
                    >
                        <q-item-section avatar>
                            <q-avatar
                                square
                                class="icon-theme"
                                icon="eva-checkmark-square-outline"
                            >
                            </q-avatar>
                        </q-item-section>
                        <q-item-section>
                            <q-item-label class="text-menu"
                                >Autorizaciones</q-item-label
                            >
                        </q-item-section>
                    </q-item>
                </q-list>
            </q-scroll-area>
        </q-drawer>

        <q-page-container>
            <slot />
        </q-page-container>
        <q-footer class="footer-theme">
            <div class="column">
                <div class="col">
                    <div class="text-center q-pa-xs">
                        Copyright © {{ new Date().getFullYear() }}
                        <strong>TOMATE FÁCIL</strong>, Todos los derechos
                        reservados.
                    </div>
                </div>
            </div>
        </q-footer>
    </q-layout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router, useForm, usePage, Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

const leftDrawerOpen = ref(false);
const active = ref(false);
const page = usePage();
const $q = useQuasar();
const user = page.props.auth.user;

const name_profile = computed(() => {
    let words = user.name.split(" ");
    let initials = "";
    for (var i = 0; i < Math.min(2, words.length); i++) {
        initials += words[i].charAt(0).toUpperCase(); // Get the initial and convert it to uppercase
    }
    return initials;
});

function toggleLeftDrawer() {
    leftDrawerOpen.value = !leftDrawerOpen.value;
}

function HandleLogout() {
    $q.dialog({
        title: "Confirmar",
        message: "¿Esta seguro de cerrar la sesion?",
        cancel: true,
        persistent: true,
    })
        .onOk(() => {
            router.post(route("auth.logout"));
        })
        .onCancel(() => {})
        .onDismiss(() => {});
}
function HandleVisitUser() {
    router.visit(route("panel.user.index"));
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
    margin: 5px 0px;
}
.message-error {
    //padding: 0;
    margin: 0;
    font-size: 10px;
    color: red;
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
</style>
