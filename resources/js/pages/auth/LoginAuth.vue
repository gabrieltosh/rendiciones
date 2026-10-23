<template>
    <Head :title="title" />
    <Login>
        <q-page-container>
            <div class="row">
                <div
                    class="col-xs-12 col-sm-12 col-md-6 col-lg-6 login-container"
                >
                    <q-page class="flex flex-center">
                        <q-form
                            @submit.prevent="HandleAuthLogin()"
                            class="q-gutter-md login-form"
                        >
                            <div class="q-px-md q-pb-sm">
                                <q-avatar
                                    square
                                    size="48px"
                                    class="login-brand q-mb-md"
                                    icon="eva-briefcase-outline"
                                    text-color="white"
                                />
                                <h4 class="title-login q-pa-none q-ma-none q-mb-sm">
                                    Iniciar sesión
                                </h4>
                                <p class="text-login q-pa-none q-ma-none">
                                    Ingresa tu usuario y contraseña para acceder al
                                    sistema de rendiciones.
                                </p>
                            </div>
                            <q-input
                                outlined
                                v-model="auth.email"
                                label="Usuario"
                                name="username"
                                autocomplete="username"
                                autofocus
                                :error="!!errors.email"
                                :error-message="firstError(errors.email)"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="eva-person-outline" />
                                </template>
                            </q-input>
                            <q-input
                                outlined
                                :type="showPassword ? 'text' : 'password'"
                                v-model="auth.password"
                                label="Contraseña"
                                name="password"
                                autocomplete="current-password"
                                :error="!!errors.password"
                                :error-message="firstError(errors.password)"
                            >
                                <template v-slot:prepend>
                                    <q-icon name="eva-lock-outline" />
                                </template>
                                <template v-slot:append>
                                    <q-btn
                                        flat
                                        round
                                        dense
                                        :icon="showPassword ? 'eva-eye-off-outline' : 'eva-eye-outline'"
                                        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                        @click="showPassword = !showPassword"
                                    />
                                </template>
                            </q-input>
                            <q-toggle
                                v-model="auth.accept"
                                label="Mantener sesión iniciada"
                                color="primary"
                            />

                            <div>
                                <q-btn
                                    class="full-width login-btn"
                                    label="Iniciar sesión"
                                    type="submit"
                                    color="primary"
                                    no-caps
                                    unelevated
                                    :loading="loading"
                                />
                            </div>
                            <div class="text-caption text-grey q-px-xs">
                                ¿Olvidaste tu contraseña? Comunícate con el
                                administrador del sistema.
                            </div>
                        </q-form>
                    </q-page>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 gt-sm image-container">
                    <q-page class="flex flex-center q-pa-none q-ma-none">
                        <img :src="Dark.isActive?'/images/login_dark.jpg':'/images/login.jpg'" alt="" >
                    </q-page>
                </div>
            </div>
        </q-page-container>
    </Login>
</template>
<script setup>
import Login from "@/layouts/AuthLayout.vue";
import { ref } from "vue";
import { router, usePage, Head } from "@inertiajs/vue3";
import { useQuasar, Dark } from "quasar";
defineProps({
    errors: Object,
    title: String,
});
const page = usePage();

const $q = useQuasar();
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
        onSuccess: () => {
            if (page.props.flash.message) {
                $q.notify({
                    type: page.props.flash.type,
                    message: page.props.flash.message,
                });
            }
        },
    });
}
</script>
<style lang="scss">
body.screen--xs {
    .login-form {
        width: 85%;
    }
}
body.screen--sm {
    .login-form {
        width: 60%;
    }
}
.login-container {
    width: 100%;
}
.login-form {
    width: 420px;
}
.login-brand {
    border-radius: 12px;
    background: linear-gradient(135deg, #1a73e8, #344767);
}
.login-btn {
    height: 44px;
    font-weight: 600;
}

.title-login{
    font-weight: 700;
    color: #344767;
    letter-spacing: -0.05rem;
    font-size: 1.5rem;
    line-height: 1.375;
}
.text-login{
    font-size: 0.95rem;
    line-height: 1.625;
    font-weight: 400;
    color: rgb(103 116 142 / 1);
}
.image-container {
  width: 900px; /* Ancho del contenedor */
  height: 500px; /* Altura del contenedor */
  overflow: hidden; /* Para ocultar cualquier parte de la imagen que exceda el contenedor */
}

.image-container img {
  max-width: 100%; /* Asegura que la imagen no se extienda más allá del ancho del contenedor */
  max-height: 100%; /* Asegura que la imagen no se extienda más allá de la altura del contenedor */
  display: block; /* Evita que la imagen tenga espacio adicional por debajo */
}
.body--dark {
    .title-login{
        color: white;
    }
    .text-login{
        color: rgb(255, 255, 255);
        opacity: 0.5;
    }
}
</style>
