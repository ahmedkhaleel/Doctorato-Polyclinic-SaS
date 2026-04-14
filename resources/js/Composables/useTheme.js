import { computed, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Default theme values — used when no setting is configured.
 * These match the Doctorato Polyclinic brand colors (Navy + Gold).
 */
const DEFAULTS = {
    // Frontend + Patient portal brand
    brand_primary:       '#C4A265',
    brand_primary_hover: '#A68B52',
    brand_secondary:     '#D4B87A',
    brand_accent:        '#C4A265',

    // Sidebar / header / footer backgrounds
    brand_sidebar_bg:    '#1B365D',
    brand_sidebar_text:  '#ffffff',
    brand_header_bg:     '#1B365D',
    brand_footer_bg:     '#1B365D',
    brand_hero_overlay:  '#1B365D',

    // Admin panel accent
    admin_primary:       '#1B365D',  // navy
    admin_primary_hover: '#264573',  // navy-light

    // Typography
    brand_font_ar:       'Tajawal',
    brand_font_en:       'Poppins',

    // Border radius scale (px)
    brand_border_radius: '16',
};

/**
 * Converts a hex color to an RGB triplet string "r, g, b"
 * for use in rgba() via CSS: rgba(var(--brand-primary-rgb), 0.1)
 */
function hexToRgb(hex) {
    if (!hex || !hex.startsWith('#')) return '196, 162, 101';
    const h = hex.replace('#', '');
    const bigint = parseInt(h.length === 3
        ? h.split('').map(c => c + c).join('')
        : h, 16);
    return `${(bigint >> 16) & 255}, ${(bigint >> 8) & 255}, ${bigint & 255}`;
}

/**
 * Composable that reads branding settings from Inertia shared props
 * and injects CSS custom properties into :root.
 *
 * Call `useTheme()` in any layout (Frontend, Patient, Admin)
 * to activate dynamic theming.
 */
export function useTheme() {
    const page = usePage();
    const settings = computed(() => page.props.settings || {});

    /** Get a theme value, falling back to defaults */
    function themeVal(key) {
        return settings.value[key] || DEFAULTS[key] || '';
    }

    /** All theme values as computed */
    const theme = computed(() => {
        const t = {};
        for (const key of Object.keys(DEFAULTS)) {
            t[key] = themeVal(key);
        }
        return t;
    });

    /** Inject CSS custom properties into :root */
    watchEffect(() => {
        const root = document.documentElement.style;
        const t = theme.value;

        // Color variables
        root.setProperty('--brand-primary', t.brand_primary);
        root.setProperty('--brand-primary-rgb', hexToRgb(t.brand_primary));
        root.setProperty('--brand-primary-hover', t.brand_primary_hover);
        root.setProperty('--brand-secondary', t.brand_secondary);
        root.setProperty('--brand-accent', t.brand_accent);
        root.setProperty('--brand-accent-rgb', hexToRgb(t.brand_accent));

        root.setProperty('--brand-sidebar-bg', t.brand_sidebar_bg);
        root.setProperty('--brand-sidebar-text', t.brand_sidebar_text);
        root.setProperty('--brand-header-bg', t.brand_header_bg);
        root.setProperty('--brand-footer-bg', t.brand_footer_bg);
        root.setProperty('--brand-hero-overlay', t.brand_hero_overlay);

        root.setProperty('--admin-primary', t.admin_primary);
        root.setProperty('--admin-primary-rgb', hexToRgb(t.admin_primary));
        root.setProperty('--admin-primary-hover', t.admin_primary_hover);

        // Typography
        root.setProperty('--font-ar', `'${t.brand_font_ar}', sans-serif`);
        root.setProperty('--font-en', `'${t.brand_font_en}', sans-serif`);

        // Border radius
        const r = parseInt(t.brand_border_radius) || 16;
        root.setProperty('--radius-sm', `${Math.round(r * 0.5)}px`);
        root.setProperty('--radius-md', `${Math.round(r * 0.75)}px`);
        root.setProperty('--radius-lg', `${r}px`);
        root.setProperty('--radius-xl', `${Math.round(r * 1.25)}px`);
        root.setProperty('--radius-2xl', `${Math.round(r * 1.5)}px`);
    });

    return {
        theme,
        themeVal,
        DEFAULTS,
    };
}
