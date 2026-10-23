import "@quasar/extras/material-icons/material-icons.css";
import "@quasar/extras/mdi-v6/mdi-v6.css";
import "quasar/src/css/index.sass";
import "@quasar/extras/eva-icons/eva-icons.css";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { Quasar, LocalStorage } from "quasar";
import quasarIconSet from "quasar/icon-set/svg-mdi-v6";
import quasarLang from "quasar/lang/es";
import { createApp, h } from "vue";
import { ZiggyVue } from 'ziggy-js';
import { Notify,Dialog } from "quasar";
const appName = import.meta.env.VITE_APP_NAME;

import lottie from "lottie-web";
import { defineElement } from "@lordicon/element";

defineElement(lottie.loadAnimation);


createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob("./pages/**/*.vue")
        ),

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Quasar, {
                plugins: {
                    Notify,
                    Dialog
                }, // import Quasar plugins and add here
                iconSet: quasarIconSet,
                lang: quasarLang,
                config: {
                    screen: {
                        bodyClasses: true // <<< add this
                    },
                    dark: LocalStorage.getItem("dark-mode") ?? false,
                    notify: {
                        position: "top-right",
                        timeout: 3500,
                        progress: true,
                        actions: [{ icon: "close", color: "white", round: true, dense: true }],
                    },
                }, // quasar config see: https://quasar.dev/start/vite-plugin/
            })
            .mount(el);
    },
    progress: {
        // The delay after which the progress bar will appear, in milliseconds...
        delay: 250,
        color: "#1A73E8",
        includeCSS: true,
        showSpinner: false,
    },
});
