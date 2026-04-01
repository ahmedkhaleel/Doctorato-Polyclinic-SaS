<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    slips: Object,
});

const months = isRtl.value ? ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'] : ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const { formatCurrency, currencyCode } = useCurrency();

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

const statusConfig = {
    draft:    { label: 'Draft',    bg: 'bg-gray-100',    text: 'text-gray-600' },
    approved: { label: 'Approved', bg: 'bg-blue-50',     text: 'text-blue-700' },
    paid:     { label: 'Paid',     bg: 'bg-emerald-50',  text: 'text-emerald-700' },
};

function getStatus(status) {
    return statusConfig[status] || { label: status, bg: 'bg-gray-100', text: 'text-gray-600' };
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'كشوف مرتباتي' : 'My Salary Slips' }}</h1>
            <p class="text-sm text-gray-500 mt-1">View your monthly salary slips and payment history</p>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Period</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Slip #</th>
                        <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Earnings</th>
                        <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Deductions</th>
                        <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Net Salary</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Paid Date</th>
                        <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="slip in slips.data" :key="slip.id" class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 font-medium text-gray-800">{{ months[slip.month] }} {{ slip.year }}</td>
                        <td class="px-6 py-3 text-[#0d9488] font-mono font-semibold">{{ slip.slip_number }}</td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left text-gray-700">{{ formatCurrency(slip.total_earnings) }}</td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left text-red-600">{{ formatCurrency(slip.total_deductions) }}</td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left font-bold text-gray-900">{{ formatCurrency(slip.net_salary) }}</td>
                        <td class="px-6 py-3">
                            <span
                                :class="[getStatus(slip.status).bg, getStatus(slip.status).text]"
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold"
                            >
                                {{ getStatus(slip.status).label }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-500">{{ formatDate(slip.paid_at) }}</td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left">
                            <Link
                                :href="`/secretary/my-salary-slips/${slip.id}`"
                                class="text-[#0d9488] hover:text-[#0b8278] text-xs font-semibold transition-colors"
                            >
                                View
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!slips.data || slips.data.length === 0" class="py-16 text-center">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">No salary slips found</p>
                    <p class="text-xs text-gray-300 mt-1">Your salary slips will appear here once generated</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="slips.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in slips.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#0d9488] text-white' : 'text-gray-500 hover:bg-gray-100'"
                        v-html="link.label"
                        preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
