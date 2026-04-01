<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    slip: Object,
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

const earnings = [
    { label: 'Basic Salary', key: 'basic_salary' },
    { label: 'Housing Allowance', key: 'housing_allowance' },
    { label: 'Transport Allowance', key: 'transport_allowance' },
    { label: 'Other Allowances', key: 'other_allowances' },
    { label: 'Overtime', key: 'overtime_amount' },
    { label: 'Bonus', key: 'bonus' },
    { label: 'Commission', key: 'commission_amount' },
];

const deductions = [
    { label: 'Insurance', key: 'insurance_deduction' },
    { label: 'Tax', key: 'tax_deduction' },
    { label: 'Absence', key: 'absence_deduction' },
    { label: 'Advance', key: 'advance_deduction' },
    { label: 'Penalty', key: 'penalty_deduction' },
    { label: 'Other Deductions', key: 'other_deductions' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <Link href="/secretary/my-salary-slips" class="text-sm text-gray-400 hover:text-[#0d9488] transition">
                        &larr; Back to Salary Slips
                    </Link>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Salary Slip Details</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ slip.slip_number }} &mdash; {{ months[slip.month] }} {{ slip.year }}
                </p>
            </div>
            <button
                @click="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-[#0d9488] text-[#0d9488] hover:bg-[#0d9488] hover:text-white transition-all duration-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                {{ isRtl ? 'طباعة' : 'Print' }}
            </button>
        </div>

        <!-- Employee Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
            <h3 class="text-sm font-bold text-gray-800 mb-4">Employee Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ isRtl ? 'الاسم' : 'Name' }}</p>
                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ slip.employee?.user?.name }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ isRtl ? 'البريد' : 'Email' }}</p>
                    <p class="text-sm text-gray-700 mt-0.5">{{ slip.employee?.user?.email }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">Department</p>
                    <p class="text-sm text-gray-700 mt-0.5">{{ slip.employee?.department?.name_en || '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Two-column: Earnings & Deductions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Earnings Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-800">Earnings</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="px-6 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Description</th>
                            <th class="px-6 py-2.5 ltr:text-right rtl:text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Amount ({{ currencyCode }})</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="item in earnings" :key="item.key" class="hover:bg-gray-50/50">
                            <td class="px-6 py-3 text-gray-700">{{ item.label }}</td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left font-medium text-gray-800">{{ formatCurrency(slip[item.key]) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-[#0d9488]">
                            <td class="px-6 py-3 font-bold text-gray-800">Total Earnings</td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left font-bold text-[#0d9488]">{{ formatCurrency(slip.total_earnings) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Deductions Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-800">Deductions</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="px-6 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Description</th>
                            <th class="px-6 py-2.5 ltr:text-right rtl:text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Amount ({{ currencyCode }})</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="item in deductions" :key="item.key" class="hover:bg-gray-50/50">
                            <td class="px-6 py-3 text-gray-700">{{ item.label }}</td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left font-medium text-red-600">{{ formatCurrency(slip[item.key]) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-red-200">
                            <td class="px-6 py-3 font-bold text-gray-800">Total Deductions</td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left font-bold text-red-600">{{ formatCurrency(slip.total_deductions) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Net Salary Card -->
        <div class="rounded-2xl shadow-sm border-2 border-[#0d9488] p-6" style="background: linear-gradient(135deg, #f0f9f6 0%, #e6f5f1 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#0d9488]">Net Salary</p>
                    <p class="text-sm text-gray-500 mt-1">{{ months[slip.month] }} {{ slip.year }}</p>
                </div>
                <div class="ltr:text-right rtl:text-left">
                    <p class="text-3xl font-bold text-[#0d9488]">{{ formatCurrency(slip.net_salary) }}</p>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
            <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'تفاصيل الدفع' : 'Payment Details' }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ isRtl ? 'طريقة الدفع' : 'Payment Method' }}</p>
                    <p class="text-sm text-gray-700 mt-0.5 capitalize">{{ slip.payment_method?.replace(/_/g, ' ') || '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">Paid At</p>
                    <p class="text-sm text-gray-700 mt-0.5">{{ formatDate(slip.paid_at) }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">Period</p>
                    <p class="text-sm text-gray-700 mt-0.5">{{ months[slip.month] }} {{ slip.year }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
