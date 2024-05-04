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
<script>
export default {
    name: "Login",
};
</script>
<style lang="scss">
.backgroup-login-theme {
    background-color: white;
}
*{
    font-family: "Poppins", sans-serif;
}
.footer-theme {
    font-size: 12px ;
    font-family: Arial, Helvetica, sans-serif;
    color: #ffffff;
    box-shadow: rgba(0, 0, 0, 0.1) 0rem 0.25rem 0.375rem -0.0625rem,
        rgba(0, 0, 0, 0.06) 0rem 0.125rem 0.25rem -0.0625rem;
    background-color: #21283c ;
}
.body--dark {
    .footer-theme {
        font-size: 12px ;
        font-family: Arial, Helvetica, sans-serif;
        color: #ffffff;
        box-shadow: rgba(0, 0, 0, 0.14) 0rem 0.125rem 0.125rem 0rem,
            rgba(0, 0, 0, 0.2) 0rem 0.1875rem 0.0625rem -0.125rem,
            rgba(0, 0, 0, 0.12) 0rem 0.0625rem 0.3125rem 0rem;
        background-color: #21283c ;
    }
    .backgroup-login-theme {
        background-color: #1b2033;
    }
}
</style>
