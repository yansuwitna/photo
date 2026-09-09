import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'PHOTOBOOTH PRO';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<any>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .mount(el);
    },
    progress: {
        color: '#f59e0b',
        showSpinner: true,
    },
});

// Registrasi PWA Service Worker untuk instalasi WebAPK di Android
if ('serviceWorker' in navigator && window.location.protocol.startsWith('http')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then((reg) => {
            console.info('PHOTOBOOTH PRO PWA ServiceWorker aktif:', reg.scope);
        }).catch((err) => {
            console.warn('Gagal registrasi ServiceWorker:', err);
        });
    });
}