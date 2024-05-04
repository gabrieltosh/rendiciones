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
                            <q-list class="q-pb-sm">
                                <q-item>
                                    <q-item-section>
                                        <q-item-label class="text-left">
                                            <h4 class="title-login q-pa-none q-ma-none q-mb-sm">
                                                Iniciar Sesion
                                            </h4>
                                            <p class="text-login q-pa-none q-ma-none">
                                                Ingresa tu correo y contraseña para iniciar sesion
                                            </p>
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                            <q-input
                                outlined
                                dense
                                v-model="auth.email"
                                label="Usuario *"
                                autofocus
                            />
                            <div v-if="errors.email" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.email"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                            <q-input
                                outlined
                                dense
                                type="password"
                                v-model="auth.password"
                                label="Contraseña *"
                                input-class="input-theme"
                            />
                            <div v-if="errors.password" class="container-error">
                                <ul
                                    v-for="(error, index) in errors.password"
                                    :key="index"
                                    class="message-error"
                                >
                                    <li>{{ error }}</li>
                                </ul>
                            </div>
                            <q-toggle
                                v-model="auth.accept"
                                label="Recuerdame"
                                color="primary"
                            />

                            <div>
                                <q-btn
                                    class="full-width"
                                    label="Iniciar Sesion"
                                    type="submit"
                                    color="primary"
                                    size="12px"
                                />
                            </div>
                            <div class="q-pa-xs">
                                <a href="#" class="link-text"
                                    >¿Olvidaste tu contraseña?</a
                                >
                            </div>
                        </q-form>
                        <q-inner-loading :showing="loading">
                            <q-spinner-puff size="50px" color="primary" />
                        </q-inner-loading>
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
//import { Vue3Lottie } from "vue3-lottie";
import { ref, reactive, computed, watch } from "vue";
//import working from "@/assets/harvest.json";
import { router, useForm, usePage, Head } from "@inertiajs/vue3";
import NProgress from "nprogress";
import { useQuasar,Dark } from "quasar";
defineProps({
    errors: Object,
    title: String,
});
const page = usePage();
const message = ref();
const type = ref();

const $q = useQuasar();
const auth = ref({
    email: null ,
    password: null,
    accept: true,
});
const loading = ref(false);

function handleReset() {}
function HandleAuthLogin() {
    router.post("/login", auth.value,{
        onSuccess:()=>{
            message.value=page.props.flash.message
            type.value=page.props.flash.type
            $q.notify({
                type: type.value,
                message: message.value,
            });
        }
    });
}
</script>
<style lang="scss">
.container-error {
    margin: 5px 0px;
}
.message-error {
    //padding: 0;
    margin: 0;
    font-size: 10px;
    color: red;
}
.link-text {
    text-decoration: none;
    color: #1A73E8;
}
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
    width: 450px;
}

.title-login{
    font-weight: 700;
    color: #344767;
    letter-spacing: -0.05rem;
    font-size: 1.5rem;
    line-height: 1.375;
}
.text-login{
    font-size: 1rem;
    line-height: 1.625;
    font-weight: 400;
    color: rgb(103 116 142 / 1);
}
.btn-theme{
    margin-top:20px;
  padding: 20px 40px;
  background-color: #4E4DED;
  border: 0;
  border-radius: 10px;
  color: white;
  font-size: 1rem;
  font-weight: 500;
  box-shadow: 0 10px 20px rgba(0,0,0,0.10), 0 6px 6px rgba(0,0,0,0.05);
  animation: shadow-pulse 1s infinite;
  cursor:pointer;
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
