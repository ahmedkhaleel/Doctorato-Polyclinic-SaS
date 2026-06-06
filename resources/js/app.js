import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import axios from 'axios';
import AOS from 'aos';
import 'aos/dist/aos.css';
import '../css/app.css';
import { ScrollRevealPlugin } from '@/Composables/useScrollReveal.js';
import { focusTrap } from '@/directives/focusTrap.js';

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

// ─── Keep <html dir/lang> in sync with the active locale ───────
// Blade sets <html dir lang> ONLY on the initial full-page render.
// Inertia is an SPA: switching locale swaps page props but never
// touches the <html> element, leaving a STALE dir (e.g. dir="rtl"
// after switching to English). That stale attribute makes Tailwind's
// rtl:/ltr: logical utilities (e.g. the admin sidebar's rtl:right-0)
// resolve against the wrong direction — the sidebar jumps to the
// wrong side. Re-sync on every successful visit so dir always
// matches the locale the server just rendered.
function syncDocumentDirection(props) {
    if (!props) return;
    const locale = props.locale || 'ar';
    const dir = props.dir || (locale === 'ar' ? 'rtl' : 'ltr');
    const root = document.documentElement;
    if (root.getAttribute('dir') !== dir) root.setAttribute('dir', dir);
    if (root.getAttribute('lang') !== locale) root.setAttribute('lang', locale);
}

// Fires after every Inertia visit completes (incl. locale-switch redirect).
router.on('success', (event) => {
    syncDocumentDirection(event.detail?.page?.props);
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
    title: (title) => title ? `${title} — Doctorato Polyclinic` : 'Doctorato Polyclinic',
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

        // Global a11y directive for modals: Escape-to-close + focus trap + restore.
        app.directive('focus-trap', focusTrap);

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

        // Make Ziggy's route() helper available inside Vue <template> scope (and
        // <script setup>), not just as a window global. Without this, any page
        // that calls route(...) in its template throws "route is not a function"
        // and renders blank. Defensive: if Ziggy isn't loaded, return '#' so a
        // missing route never crashes the page render.
        app.config.globalProperties.route = function (name, params, absolute) {
            if (typeof window !== 'undefined' && typeof window.route === 'function') {
                return window.route(name, params, absolute);
            }
            return '#';
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
