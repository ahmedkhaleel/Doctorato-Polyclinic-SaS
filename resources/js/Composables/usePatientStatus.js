import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable for translating status badges in patient portal.
 * Provides localized status labels and color classes.
 */
export function usePatientStatus() {
    const page = usePage();
    const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

    const bookingStatuses = {
        unconfirmed:  { en: 'Unconfirmed',  ar: 'غير مؤكد',  color: 'bg-yellow-100 text-yellow-700' },
        confirmed:    { en: 'Confirmed',     ar: 'مؤكد',      color: 'bg-blue-100 text-blue-700' },
        completed:    { en: 'Completed',     ar: 'مكتمل',     color: 'bg-green-100 text-green-700' },
        cancelled:    { en: 'Cancelled',     ar: 'ملغي',      color: 'bg-red-100 text-red-700' },
        no_show:      { en: 'No Show',       ar: 'لم يحضر',   color: 'bg-gray-100 text-gray-500' },
    };

    const visitStatuses = {
        scheduled:    { en: 'Scheduled',     ar: 'مجدول',     color: 'bg-blue-100 text-blue-700' },
        in_progress:  { en: 'In Progress',   ar: 'جاري',      color: 'bg-yellow-100 text-yellow-700' },
        completed:    { en: 'Completed',     ar: 'مكتمل',     color: 'bg-green-100 text-green-700' },
        cancelled:    { en: 'Cancelled',     ar: 'ملغي',      color: 'bg-red-100 text-red-700' },
        no_show:      { en: 'No Show',       ar: 'لم يحضر',   color: 'bg-gray-100 text-gray-500' },
    };

    const invoiceStatuses = {
        paid:         { en: 'Paid',          ar: 'مدفوعة',    color: 'bg-green-100 text-green-700' },
        partial:      { en: 'Partial',       ar: 'جزئي',      color: 'bg-yellow-100 text-yellow-700' },
        unpaid:       { en: 'Unpaid',        ar: 'غير مدفوعة', color: 'bg-red-100 text-red-700' },
        cancelled:    { en: 'Cancelled',     ar: 'ملغية',     color: 'bg-gray-100 text-gray-500' },
    };

    const photoTypes = {
        before:       { en: 'Before',        ar: 'قبل' },
        after:        { en: 'After',         ar: 'بعد' },
        progress:     { en: 'Progress',      ar: 'تقدم' },
    };

    function statusLabel(statusMap, key) {
        const entry = statusMap[key];
        if (!entry) return key || '—';
        return isRtl.value ? entry.ar : entry.en;
    }

    function statusColor(statusMap, key) {
        return statusMap[key]?.color || 'bg-gray-100 text-gray-500';
    }

    return {
        bookingStatuses,
        visitStatuses,
        invoiceStatuses,
        photoTypes,
        bookingLabel:  (key) => statusLabel(bookingStatuses, key),
        bookingColor:  (key) => statusColor(bookingStatuses, key),
        visitLabel:    (key) => statusLabel(visitStatuses, key),
        visitColor:    (key) => statusColor(visitStatuses, key),
        invoiceLabel:  (key) => statusLabel(invoiceStatuses, key),
        invoiceColor:  (key) => statusColor(invoiceStatuses, key),
        photoTypeLabel:(key) => statusLabel(photoTypes, key),
    };
}
