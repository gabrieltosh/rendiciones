<template>
    <Head :title="title" />
    <Layout>
        <div class="q-px-sm q-py-md">
            <div class="row q-col-gutter-md">
                <div
                    class="col-sm-3 col-xs-12"
                    v-for="(profile, index) in profiles"
                    :key="index"
                >
                    <q-card
                        class="card-form q-mt-md cursor-pointer"
                        @click="HandleVisitAccountability(profile.profile_id)"
                    >
                        <q-avatar
                            square
                            size="50px"
                            class="absolute"
                            style="
                                top: 12px;
                                left: 12px;
                                transform: translateY(-50%);
                                border-radius: 10px;
                                background-color: #1b2033;
                                box-shadow: 0rem 0.25rem 1.25rem 0rem
                                        rgba(0, 0, 0, 0.14),
                                    0rem 0.4375rem 0.625rem -0.3125rem rgba(64, 64, 64, 0.4);
                            "
                        >
                            <lord-icon
                                target=".q-card"
                                src="https://cdn.lordicon.com/dlzzlyaa.json"
                                trigger="hover"
                                colors="primary:white,secondary:#08a88a"
                                style="width: 50px; height: 50px"
                                ref="animation"
                            >
                            </lord-icon>
                        </q-avatar>
                        <div class="row q-pa-sm">
                            <div class="col-xs-3 col-sm-3 col-md-2"></div>
                            <div class="col-xs-9 col-sm-9 col-md-10 text-right">
                                <div class="form-label">
                                    {{ profile.profile.name }}
                                </div>
                                <q-separator spaced  />
                                <div class="text-caption">
                                    Tipo Moneda: <strong>{{ profile.profile.type_currency }}</strong>
                                </div>
                            </div>
                        </div>
                    </q-card>
                </div>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from "@/layouts/MainLayout.vue";
import { ref, watch, onMounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useQuasar } from "quasar";

defineProps({
    title: String,
    errors: Object,
});

const $q = useQuasar();
const page = usePage();

const profiles = ref(page.props.profiles);

function HandleVisitAccountability(profile_id){
    router.visit(route('panel.accountability.manage.index',profile_id))
}

onMounted(() => {
});
</script>
