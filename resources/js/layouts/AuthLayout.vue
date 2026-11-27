<template>
    <q-layout class="backgroup-login-theme">
        <slot />
        <q-footer class="footer-theme">
            <div class="column">
                <div class="col">
                    <div
                        class="text-center q-pa-xs"
                    >
                        Copyright © {{ new Date().getFullYear() }}
                        <strong>TOMATE FÁCIL</strong>, Todos los derechos reservados.
                    </div>
                </div>
            </div>
        </q-footer>
    </q-layout>
</template>
<script setup>
// Los estilos de este layout viven en resources/css/app.scss
import { onMounted } from "vue";
import { installFlashNotifications, notifyInitialFlash } from "@/utils/flash";

installFlashNotifications();
onMounted(() => notifyInitialFlash());
</script>
