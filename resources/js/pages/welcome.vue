<template>
    <Head :title="title" />
    <Layout>
        <q-page class="column flex-center">
            <q-img
                src="/images/RendicionFacil.png"
                :ratio="16/9"
                spinner-color="primary"
                spinner-size="82px"
                fit="contain"
            />
        </q-page>
    </Layout>
</template>
<script setup>
import Layout from "../layouts/MainLayout.vue";
import { ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import { Dark } from "quasar";

defineProps({
    title: String,
});

const count = ref(0);
const min = -5;
const max = 5;

const darkMode = ref(Dark.isActive);
watch(darkMode, (value) => {
    Dark.set(value);
});
</script>
