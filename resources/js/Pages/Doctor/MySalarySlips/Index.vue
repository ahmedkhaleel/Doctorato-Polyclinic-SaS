<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    slips: Object,
});

const monthNames = [
    '', 'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function periodLabel(slip) {
    const month = monthNames[slip.month] || slip.month;
    return `${month} ${slip.year}`;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_my_salary_slips') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'عرض وتحميل كشوف الرواتب' : 'View and download your salary slips' }}</p>
        </div>

        <!-- Salary Slips Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الفترة' : 'Period' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'رقم الكشف' : 'Slip #' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'المكاسب' : 'Earnings' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الخصومات' : 'Deductions' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'صافي الراتب' : 'Net Salary' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'تاريخ الدفع' : 'Paid Date' }}</th>
                            <th class="text-center px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="slip in slips.data" :key="slip.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ periodLabel(slip) }}</td>
                            <td class="px-6 py-3 text-gray-500 font-mono text-xs">{{ slip.slip_number || '-' }}</td>
                            <td class="px-6 py-3 text-right text-emerald-600 font-medium tabular-nums">{{ formatCurrency(slip.total_earnings) }}</td>
                            <td class="px-6 py-3 text-right text-red-500 font-medium tabular-nums">{{ formatCurrency(slip.total_deductions) }}</td>
                            <td class="px-6 py-3 text-right font-bold text-[#4f46e5] tabular-nums">{{ formatCurrency(slip.net_salary) }}</td>
                            <td class="px-6 py-3 text-gray-500 text-xs">{{ formatDate(slip.paid_at) }}</td>
                            <td class="px-6 py-3 text-center">
                                <Link :href="`/doctor/my-salary-slips/${slip.id}`" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#4f46e5] hover:text-[#4338ca] transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    {{ isRtl ? 'عرض' : 'View' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!slips.data || slips.data.length === 0" class="py-16 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-3 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد كشوف رواتب' : 'No salary slips found' }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="slips.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <template v-for="link in slips.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#4f46e5] text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
