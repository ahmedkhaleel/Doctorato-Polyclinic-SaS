import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable for locale-aware patient portal paths.
 * Usage: const { lp } = usePatientLocale();
 *        lp('/bookings')  → '/ar/patient/bookings' or '/en/patient/bookings'
 */
export function usePatientLocale() {
    const page = usePage();
    const locale = computed(() => page.props.locale || 'ar');

    function lp(path = '') {
        return `/${locale.value}/patient${path}`;
    }

    return { locale, lp };
}
