import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import axios from 'axios';
import AOS from 'aos';
import 'aos/dist/aos.css';
import '../css/app.css';
import { ScrollRevealPlugin } from '@/Composables/useScrollReveal.js';

// ─── Global Inertia Error Handler ──────────────────────────────
// Catches server errors (500, 403, 404, etc.) that individual
// router calls don't handle, and displays a user-friendly toast.
router.on('invalid', (event) => {
    event.preventDefault();
    const status = event.detail.response?.status;
    if (status === 419) {
        // CSRF token expired — reload the page
        window.location.reload();
    } else {
        window.dispatchEvent(new CustomEvent('inertia-error', {
            detail: { message: `خطأ في الخادم (${status || 'unknown'}) / Server error`, type: 'error' },
        }));
    }
});

router.on('error', () => {
    // Network or unexpected errors
    window.dispatchEvent(new CustomEvent('inertia-error', {
        detail: { message: 'حدث خطأ غير متوقع / An unexpected error occurred', type: 'error' },
    }));
});

// ModSecurity on cPanel blocks PUT, PATCH, DELETE HTTP methods.
// This interceptor converts them to POST with _method spoofing,
// which Laravel automatically understands.
axios.interceptors.request.use((config) => {
    const method = config.method?.toLowerCase();
    if (method === 'put' || method === 'patch' || method === 'delete') {
        if (config.data instanceof FormData) {
            config.data.append('_method', method.toUpperCase());
        } else {
            config.data = { ...(config.data || {}), _method: method.toUpperCase() };
        }
        config.method = 'post';
    }
    return config;
});

createInertiaApp({
    title: (title) => title ? `${title} — AURA Derma Clinic` : 'AURA Derma Aesthetic Clinic',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(pinia);
        app.use(ScrollRevealPlugin);

        app.config.globalProperties.$t = function (key, replacements = {}) {
            const translations = this.$page?.props?.translations || {};
            let translation = translations[key] || key;
            Object.keys(replacements).forEach((k) => {
                translation = translation.replace(`:${k}`, replacements[k]);
            });
            return translation;
        };

        app.config.globalProperties.$locale = function () {
            return this.$page?.props?.locale || 'ar';
        };

        app.config.globalProperties.$dir = function () {
            return this.$page?.props?.dir || 'rtl';
        };

        app.config.globalProperties.$localized = function (obj, field) {
            const locale = this.$page?.props?.locale || 'ar';
            return obj?.[`${field}_${locale}`] || obj?.[`${field}_ar`] || '';
        };

        app.mount(el);

        AOS.init({
            duration: 800,
            easing: 'ease-out',
            once: true,
            offset: 100,
        });
    },
    progress: {
        color: '#C4A265',
        showSpinner: true,
    },
});
