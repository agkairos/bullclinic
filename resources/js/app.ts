import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import './echo';

const appName = import.meta.env.VITE_APP_NAME || 'BullClinic';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: { color: '#00c1b3' },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
