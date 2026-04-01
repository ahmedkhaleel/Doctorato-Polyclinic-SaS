import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function useLocale() {
    const page = usePage();

    const locale = computed(() => page.props.locale || 'ar');
    const dir = computed(() => page.props.dir || 'rtl');
    const isRtl = computed(() => dir.value === 'rtl');
    const isAr = computed(() => locale.value === 'ar');
    const translations = computed(() => page.props.translations || {});

    function t(key, replacements = {}) {
        let translation = translations.value[key] || key;
        Object.keys(replacements).forEach((k) => {
            translation = translation.replace(`:${k}`, replacements[k]);
        });
        return translation;
    }

    function localized(obj, field) {
        return obj?.[`${field}_${locale.value}`] || obj?.[`${field}_ar`] || '';
    }

    function switchLocale() {
        const newLocale = locale.value === 'ar' ? 'en' : 'ar';
        const currentPath = window.location.pathname;
        const newPath = currentPath.replace(/^\/(ar|en)/, `/${newLocale}`);
        router.visit(newPath, { preserveState: false });
    }

    function localizedRoute(path) {
        return `/${locale.value}${path}`;
    }

    return {
        locale,
        dir,
        isRtl,
        isAr,
        t,
        localized,
        switchLocale,
        localizedRoute,
    };
}
