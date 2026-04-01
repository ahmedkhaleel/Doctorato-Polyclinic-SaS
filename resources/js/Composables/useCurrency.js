import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useCurrency() {
    const page = usePage();
    const settings = computed(() => page.props.settings || {});

    const currencyCode = computed(() => settings.value.currency_code || 'EGP');
    const currencySymbol = computed(() => settings.value.currency_symbol || 'E£');
    const currencyNameEn = computed(() => settings.value.currency_name_en || 'Egyptian Pound');
    const currencyNameAr = computed(() => settings.value.currency_name_ar || 'جنيه مصري');
    const currencyPosition = computed(() => settings.value.currency_position || 'after');
    const currencyDecimals = computed(() => parseInt(settings.value.currency_decimals ?? '0'));

    function formatCurrency(amount) {
        const num = Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: currencyDecimals.value,
            maximumFractionDigits: currencyDecimals.value,
        });
        return currencyPosition.value === 'before'
            ? `${currencyCode.value} ${num}`
            : `${num} ${currencyCode.value}`;
    }

    function currencyLabel(label) {
        return `${label} (${currencyCode.value})`;
    }

    return {
        currencyCode,
        currencySymbol,
        currencyNameEn,
        currencyNameAr,
        currencyPosition,
        currencyDecimals,
        formatCurrency,
        currencyLabel,
    };
}
