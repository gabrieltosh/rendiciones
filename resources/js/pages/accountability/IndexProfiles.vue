<template>
    <Head :title="title" />
    <Layout>
        <div class="row justify-center q-px-md q-py-lg">
            <div class="col-xs-12 col-sm-12 col-md-11 col-lg-10">

                <PageHeader title="Mis rendiciones" icon="eva-folder-outline"
                    subtitle="Elija un perfil para ver y registrar sus rendiciones" />

                <!-- Empty State -->
                <div data-tour="profile-list"
                    v-if="profiles.length === 0"
                    class="text-center q-pa-xl"
                >
                    <q-icon
                        name="folder_open"
                        size="64px"
                        color="grey-4"
                        aria-hidden="true"
                    />
                    <div class="text-body1 text-grey q-mt-md">
                        No tiene perfiles asignados. Solicite al administrador que le asigne uno.
                    </div>
                </div>

                <!-- Profile Cards -->
                <div data-tour="profile-list" v-else class="row q-col-gutter-md">
                    <div
                        v-for="(profile, index) in profiles"
                        :key="index"
                        class="col-xs-12 col-sm-6 col-md-4 col-lg-3"
                    >
                        <q-card
                            class="card-form profile-card full-height"
                            tabindex="0"
                            role="link"
                            :aria-label="'Ir a rendiciones de ' + profile.profile.name"
                            @click="HandleVisitAccountability(profile.profile_id)"
                            @keydown.enter="HandleVisitAccountability(profile.profile_id)"
                        >
                            <q-card-section>
                                <div class="row items-center q-gutter-sm">
                                    <div class="col-auto">
                                        <q-avatar
                                            size="42px"
                                            class="profile-avatar"
                                        >
                                            <q-icon
                                                name="description"
                                                color="white"
                                                size="sm"
                                                aria-hidden="true"
                                            />
                                        </q-avatar>
                                    </div>
                                    <div class="col" style="min-width: 0;">
                                        <div class="text-body2 text-weight-medium ellipsis">
                                            {{ profile.profile.name }}
                                        </div>
                                        <div class="text-caption text-grey">
                                            Moneda: {{ currencySymbol(profile.profile.type_currency) }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <q-icon
                                            name="chevron_right"
                                            color="grey"
                                            aria-hidden="true"
                                        />
                                    </div>
                                </div>
                            </q-card-section>
                        </q-card>
                    </div>
                </div>

            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from "@/layouts/MainLayout.vue";
import PageHeader from "@/components/PageHeader.vue";
import { currencySymbol } from "@/utils/accountability";
import { ref } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

defineProps({
    title: String,
});

const page = usePage();
const profiles = ref(page.props.profiles);

function HandleVisitAccountability(profile_id) {
    router.visit(route("panel.accountability.manage.index", profile_id));
}
</script>

<style scoped>
.profile-card {
    cursor: pointer;
    transition: box-shadow 0.2s ease;
}

.profile-card:hover {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.profile-card:focus-visible {
    outline: 2px solid var(--q-primary);
    outline-offset: 2px;
}

.profile-avatar {
    background-color: #1b2033;
    border-radius: 10px;
}

.full-height {
    height: 100%;
}

@media (prefers-reduced-motion: reduce) {
    .profile-card {
        transition: none;
    }
}
</style>
