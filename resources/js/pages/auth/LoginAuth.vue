<template>
    <Head :title="title" />
    <Login>
        <q-page-container>
            <q-page class="login-page">
                <div class="login-wrap">
                    <div class="login-brand">
                        <div class="login-brand__icon" aria-hidden="true">
                            <q-icon name="eva-briefcase-outline" size="22px" />
                        </div>
                        <div>
                            <div class="login-brand__title">Rendiciones</div>
                            <div class="login-brand__caption">Gastos y viáticos</div>
                        </div>
                    </div>

                    <q-card class="login-card">
                        <q-form @submit.prevent="HandleAuthLogin()" class="login-form">
                            <div class="login-header">
                                <h1 class="title-login">Iniciar sesión</h1>
                                <p class="text-login">
                                    Ingresa tu usuario y contraseña para acceder al
                                    sistema de rendiciones.
                                </p>
                            </div>

                            <div>
                                <label class="form-label" for="login-username">Usuario</label>
                                <q-input
                                    for="login-username"
                                    outlined
                                    v-model="auth.email"
                                    name="username"
                                    autocomplete="username"
                                    placeholder="Tu usuario"
                                    autofocus
                                    class="login-input"
                                    :error="!!errors.email"
                                    :error-message="firstError(errors.email)"
                                >
                                    <template v-slot:prepend>
                                        <q-icon name="eva-person-outline" size="18px" />
                                    </template>
                                </q-input>
                            </div>

                            <div>
                                <label class="form-label" for="login-password">Contraseña</label>
                                <q-input
                                    for="login-password"
                                    outlined
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="auth.password"
                                    name="password"
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="login-input"
                                    :error="!!errors.password"
                                    :error-message="firstError(errors.password)"
                                >
                                    <template v-slot:prepend>
                                        <q-icon name="eva-lock-outline" size="18px" />
                                    </template>
                                    <template v-slot:append>
                                        <q-btn
                                            flat
                                            round
                                            dense
                                            size="sm"
                                            :icon="showPassword ? 'eva-eye-off-outline' : 'eva-eye-outline'"
                                            :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                            @click="showPassword = !showPassword"
                                        />
                                    </template>
                                </q-input>
                            </div>

                            <q-toggle
                                v-model="auth.accept"
                                label="Mantener sesión iniciada"
                                color="primary"
                                dense
                            />

                            <q-btn
                                class="full-width login-btn"
                                label="Iniciar sesión"
                                type="submit"
                                color="primary"
                                no-caps
                                unelevated
                                :loading="loading"
                            />
                        </q-form>
                    </q-card>

                    <div class="login-help">
                        ¿Olvidaste tu contraseña? Comunícate con el administrador del sistema.
                    </div>
                </div>
            </q-page>
        </q-page-container>
    </Login>
</template>
<script setup>
import Login from "@/layouts/AuthLayout.vue";
import { ref } from "vue";
import { router, Head } from "@inertiajs/vue3";
defineProps({
    errors: Object,
    title: String,
});

const auth = ref({
    email: null,
    password: null,
    accept: true,
});
const loading = ref(false);
const showPassword = ref(false);

function firstError(messages) {
    return Array.isArray(messages) ? messages[0] : messages;
}

function HandleAuthLogin() {
    router.post("/login", auth.value, {
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
}
</script>
<style lang="scss">
.login-page {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 16px 56px;
    background:
        radial-gradient(60% 50% at 50% 0%, var(--ui-primary-soft) 0%, transparent 70%),
        var(--ui-bg-page);
}
.login-wrap {
    width: 100%;
    max-width: 400px;
}
.login-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
}
.login-brand__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--q-primary);
    color: #fff;
}
.login-brand__title {
    font-size: 15px;
    font-weight: 600;
    line-height: 1.2;
    color: var(--ui-text-highlighted);
}
.login-brand__caption {
    font-size: 12px;
    color: var(--ui-text-muted);
}
.login-card.q-card {
    padding: 28px 24px 24px;
    box-shadow: var(--ui-shadow-xs);
}
.login-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.login-header {
    text-align: center;
    margin-bottom: 4px;
}
.title-login {
    margin: 0 0 4px;
    font-size: 20px;
    line-height: 28px;
    font-weight: 600;
    letter-spacing: -0.015em;
    color: var(--ui-text-highlighted);
}
.text-login {
    margin: 0;
    font-size: 14px;
    line-height: 20px;
    color: var(--ui-text-muted);
}
.login-input.q-field--outlined .q-field__control {
    height: 40px;
    min-height: 40px;
}
.login-input .q-field__marginal {
    height: 40px;
}
.login-input .q-field__bottom {
    padding-left: 2px;
}
.login-input.q-field--with-bottom {
    padding-bottom: 20px;
}
.login-btn {
    height: 40px;
    font-weight: 500;
}
.login-help {
    margin-top: 16px;
    text-align: center;
    font-size: 13px;
    color: var(--ui-text-muted);
}
</style>
