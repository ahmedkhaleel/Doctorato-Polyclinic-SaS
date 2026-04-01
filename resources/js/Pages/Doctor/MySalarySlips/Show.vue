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
    slip: Object,
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

function periodLabel() {
    const month = monthNames[props.slip?.month] || props.slip?.month;
    return `${month} ${props.slip?.year}`;
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

function handlePrint() {
    window.print();
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between print:hidden">
            <div class="flex items-center gap-3">
                <Link href="/doctor/my-salary-slips" class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'تفاصيل كشف الراتب' : 'Salary Slip Details' }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ periodLabel() }} &mdash; {{ slip?.slip_number }}</p>
                </div>
            </div>
            <button @click="handlePrint" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#4f46e5] text-white text-sm font-semibold rounded-xl hover:bg-[#4338ca] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                {{ isRtl ? 'طباعة' : 'Print' }}
            </button>
        </div>

        <!-- Print Header (hidden on screen) -->
        <div class="hidden print:block text-center mb-6">
            <h1 class="text-xl font-bold text-gray-900">{{ isRtl ? 'كشف الراتب' : 'Salary Slip' }}</h1>
            <p class="text-sm text-gray-500">{{ periodLabel() }} &mdash; {{ slip?.slip_number }}</p>
        </div>

        <!-- Employee Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-[#4f46e5]/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#4f46e5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">{{ slip?.employee?.user?.name || '-' }}</h2>
                    <div class="flex items-center gap-3 mt-0.5">
                        <span class="text-sm text-gray-500">{{ slip?.employee?.user?.email }}</span>
                        <span v-if="slip?.employee?.department?.name_en" class="text-xs text-[#4f46e5] font-medium bg-[#4f46e5]/5 px-2.5 py-0.5 rounded-full">{{ slip.employee.department.name_en }}</span>
                    </div>
                </div>
            </div>
            <div v-if="slip?.payment_method || slip?.paid_at" class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-6 text-sm">
                <div v-if="slip.paid_at">
                    <span class="text-gray-400 text-xs">{{ isRtl ? 'تاريخ الدفع' : 'Paid On' }}</span>
                    <p class="font-medium text-gray-700">{{ formatDate(slip.paid_at) }}</p>
                </div>
                <div v-if="slip.payment_method">
                    <span class="text-gray-400 text-xs">{{ isRtl ? 'طريقة الدفع' : 'Payment Method' }}</span>
                    <p class="font-medium text-gray-700 capitalize">{{ slip.payment_method }}</p>
                </div>
            </div>
        </div>

        <!-- Earnings & Deductions -->
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Earnings Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'المكاسب' : 'Earnings' }}</h3>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="item in earnings" :key="item.key" class="flex items-center justify-between px-6 py-3">
                        <span class="text-sm text-gray-600">{{ item.label }}</span>
                        <span class="text-sm font-medium text-gray-800 tabular-nums">{{ formatCurrency(slip?.[item.key]) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between px-6 py-4 bg-emerald-50/50 border-t border-emerald-100">
                    <span class="text-sm font-bold text-emerald-700">{{ isRtl ? 'إجمالي المكاسب' : 'Total Earnings' }}</span>
                    <span class="text-sm font-bold text-emerald-700 tabular-nums">{{ formatCurrency(slip?.total_earnings) }}</span>
                </div>
            </div>

            <!-- Deductions Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الخصومات' : 'Deductions' }}</h3>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="item in deductions" :key="item.key" class="flex items-center justify-between px-6 py-3">
                        <span class="text-sm text-gray-600">{{ item.label }}</span>
                        <span class="text-sm font-medium text-gray-800 tabular-nums">{{ formatCurrency(slip?.[item.key]) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between px-6 py-4 bg-red-50/50 border-t border-red-100">
                    <span class="text-sm font-bold text-red-700">{{ isRtl ? 'إجمالي الخصومات' : 'Total Deductions' }}</span>
                    <span class="text-sm font-bold text-red-700 tabular-nums">{{ formatCurrency(slip?.total_deductions) }}</span>
                </div>
            </div>
        </div>

        <!-- Net Salary -->
        <div class="bg-gradient-to-r from-[#4f46e5] to-[#6366f1] rounded-2xl shadow-sm p-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm text-white/70 font-medium">{{ isRtl ? 'صافي الراتب' : 'Net Salary' }}</p>
                        <p class="text-3xl font-bold">{{ formatCurrency(slip?.net_salary) }}</p>
                    </div>
                </div>
                <div class="text-right hidden sm:block">
                    <p class="text-sm text-white/60">Earnings</p>
                    <p class="text-sm font-semibold text-white/90">{{ formatCurrency(slip?.total_earnings) }}</p>
                    <p class="text-sm text-white/60 mt-1">Deductions</p>
                    <p class="text-sm font-semibold text-white/90">- {{ formatCurrency(slip?.total_deductions) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
