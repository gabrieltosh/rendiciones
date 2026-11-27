<template>
    <q-layout view="hHh lpR fFf" class="backgroup-login-theme">
        <slot />
        <div class="auth-footer">
            © {{ new Date().getFullYear() }} <strong>TOMATE FÁCIL</strong>. Todos los derechos reservados.
        </div>
    </q-layout>
</template>
<script setup>
// Los estilos de este layout viven en resources/css/app.scss y en LoginAuth.vue
import { onMounted } from "vue";
import { installFlashNotifications, notifyInitialFlash } from "@/utils/flash";

installFlashNotifications();
onMounted(() => notifyInitialFlash());
</script>
<style>
.auth-footer {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 12px 16px;
    text-align: center;
    font-size: 12px;
    color: var(--ui-text-dimmed);
}
</style>
