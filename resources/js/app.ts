import '../css/app.css';

// Importações do Quasar
import { Quasar, Dialog } from 'quasar';

import * as AllQuasarComponents from 'quasar';

// import 'quasar/dist/quasar.css';
// import 'quasar/src/css/index.sass'

import '@quasar/extras/material-icons/material-icons.css'; // Ícones Material

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Adicione o plugin do Quasar
        app.use(plugin)
            .use(ZiggyVue)
            .use(Quasar, {
                plugins: {
                  Dialog,
                },
                components: AllQuasarComponents,
                cssPrefix: 'q-' // Garante que todos os estilos do Quasar tenham prefixo
              })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Configuração de tema (light/dark mode)
initializeTheme();