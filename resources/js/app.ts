import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { install as VueMonacoEditorPlugin } from '@guolao/vue-monaco-editor'



const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
            
    },
    progress: {
        color: '#4B5563',
    },
});

// Handle session timeout and authentication errors
let isHandlingSessionTimeout = false;

router.on('error', (event) => {
    if (isHandlingSessionTimeout) return;
    
    const response = event.detail.response;
    const status = response?.status;
    
    // Handle authentication and session errors
    if (status === 401 || status === 419 || status === 409) {
        isHandlingSessionTimeout = true;
        
        // Check for redirect location in header
        const redirectUrl = response.headers?.['x-inertia-location'] || '/login';
        
        // Force a full page reload
        window.location.href = redirectUrl;
    }
});

// Additional error handling for network issues
router.on('exception', (event) => {
    if (isHandlingSessionTimeout) return;
    
    console.error('Inertia exception:', event.detail);
    
    const error = event.detail.error;
    const status = error?.response?.status;
    
    // Handle authentication errors
    if (status === 401 || status === 419 || status === 409) {
        isHandlingSessionTimeout = true;
        window.location.href = '/login';
    }
});

// This will set light / dark mode on page load...
initializeTheme();
