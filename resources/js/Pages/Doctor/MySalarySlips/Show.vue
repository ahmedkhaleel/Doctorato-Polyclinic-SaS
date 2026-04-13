<script setup>
import { ref, computed, onMounted } from 'vue';
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

// Entrance animations
const headerLoaded = ref(false);
const infoLoaded = ref(false);
const tablesLoaded = ref(false);
const netLoaded = ref(false);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => infoLoaded.value = true, 150);
    setTimeout(() => tablesLoaded.value = true, 300);
    setTimeout(() => netLoaded.value = true, 500);
});

const monthNamesEn = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const monthNamesAr = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function periodLabel() {
    const names = isRtl.value ? monthNamesAr : monthNamesEn;
    const month = names[props.slip?.month] || props.slip?.month;
    return `${month} ${props.slip?.year}`;
}

const earnings = [
    { label: 'Basic Salary', labelAr: 'الراتب الأساسي', key: 'basic_salary', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Housing Allowance', labelAr: 'بدل السكن', key: 'housing_allowance', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { label: 'Transport Allowance', labelAr: 'بدل النقل', key: 'transport_allowance', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
    { label: 'Other Allowances', labelAr: 'بدلات أخرى', key: 'other_allowances', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
    { label: 'Overtime', labelAr: 'العمل الإضافي', key: 'overtime_amount', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Bonus', labelAr: 'المكافأة', key: 'bonus', icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
    { label: 'Commission', labelAr: 'العمولة', key: 'commission_amount', icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z' },
];

const deductions = [
    { label: 'Insurance', labelAr: 'التأمين', key: 'insurance_deduction', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { label: 'Tax', labelAr: 'الضريبة', key: 'tax_deduction', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { label: 'Absence', labelAr: 'خصم الغياب', key: 'absence_deduction', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Advance', labelAr: 'خصم السلفة', key: 'advance_deduction', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
    { label: 'Penalty', labelAr: 'خصم الجزاء', key: 'penalty_deduction', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
    { label: 'Other Deductions', labelAr: 'خصومات أخرى', key: 'other_deductions', icon: 'M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z' },
];

// Calculate earnings/deductions ratio for mini visual
const earningsPercent = computed(() => {
    const total = Number(props.slip?.total_earnings || 0) + Number(props.slip?.total_deductions || 0);
    if (!total) return 100;
    return Math.round((Number(props.slip?.total_earnings || 0) / total) * 100);
});

function handlePrint() {
    window.print();
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 print:hidden transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <div class="flex items-center gap-3">
                <Link href="/doctor/my-salary-slips" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-all duration-200 hover:scale-105 active:scale-95">
                    <svg class="w-4 h-4 text-gray-600 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'تفاصيل كشف الراتب' : 'Salary Slip Details' }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ periodLabel() }} <span class="text-gray-300 mx-1">&mdash;</span> <span class="font-mono text-gray-400">{{ slip?.slip_number }}</span></p>
                </div>
            </div>
            <button @click="handlePrint" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#4f46e5] text-white text-sm font-semibold rounded-xl hover:bg-[#4338ca] transition-all duration-200 hover:shadow-lg hover:shadow-indigo-500/20 active:scale-95">
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
        <div
            class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden transition-all duration-700"
            :class="infoLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <div class="p-4 sm:p-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#4f46e5] to-[#6366f1] flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-gray-900">{{ slip?.employee?.user?.name || '-' }}</h2>
                        <div class="flex flex-wrap items-center gap-3 mt-1">
                            <span class="text-sm text-gray-500">{{ slip?.employee?.user?.email }}</span>
                            <span v-if="slip?.employee?.department" class="text-xs text-[#4f46e5] font-medium bg-[#4f46e5]/5 px-2.5 py-0.5 rounded-full">{{ isRtl ? (slip.employee.department.name_ar || slip.employee.department.name_en) : slip.employee.department.name_en }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="slip?.payment_method || slip?.paid_at" class="px-4 sm:px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-wrap gap-4 sm:gap-6">
                <div v-if="slip.paid_at" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">{{ isRtl ? 'تاريخ الدفع' : 'Paid On' }}</span>
                        <p class="text-sm font-semibold text-gray-700">{{ formatDate(slip.paid_at) }}</p>
                    </div>
                </div>
                <div v-if="slip.payment_method" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">{{ isRtl ? 'طريقة الدفع' : 'Payment Method' }}</span>
                        <p class="text-sm font-semibold text-gray-700 capitalize">{{ slip.payment_method }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings & Deductions -->
        <div
            class="grid lg:grid-cols-2 gap-4 sm:gap-6 transition-all duration-700"
            :class="tablesLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <!-- Earnings Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'المكاسب' : 'Earnings' }}</h3>
                        <p class="text-[10px] text-gray-400 font-medium">{{ earnings.filter(e => Number(slip?.[e.key]) > 0).length }} {{ isRtl ? 'عناصر' : 'items' }}</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="item in earnings" :key="item.key" class="flex items-center justify-between px-4 sm:px-6 py-3.5 hover:bg-gray-50/50 transition-colors group">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" /></svg>
                            <span class="text-sm text-gray-600">{{ isRtl ? item.labelAr : item.label }}</span>
                        </div>
                        <span class="text-sm font-semibold tabular-nums" :class="Number(slip?.[item.key]) > 0 ? 'text-gray-800' : 'text-gray-300'">{{ formatCurrency(slip?.[item.key]) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 bg-emerald-50/50 border-t border-emerald-100">
                    <span class="text-sm font-bold text-emerald-700">{{ isRtl ? 'إجمالي المكاسب' : 'Total Earnings' }}</span>
                    <span class="text-base font-bold text-emerald-700 tabular-nums">{{ formatCurrency(slip?.total_earnings) }}</span>
                </div>
            </div>

            <!-- Deductions Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الخصومات' : 'Deductions' }}</h3>
                        <p class="text-[10px] text-gray-400 font-medium">{{ deductions.filter(d => Number(slip?.[d.key]) > 0).length }} {{ isRtl ? 'عناصر' : 'items' }}</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="item in deductions" :key="item.key" class="flex items-center justify-between px-4 sm:px-6 py-3.5 hover:bg-gray-50/50 transition-colors group">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-red-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" /></svg>
                            <span class="text-sm text-gray-600">{{ isRtl ? item.labelAr : item.label }}</span>
                        </div>
                        <span class="text-sm font-semibold tabular-nums" :class="Number(slip?.[item.key]) > 0 ? 'text-gray-800' : 'text-gray-300'">{{ formatCurrency(slip?.[item.key]) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 bg-red-50/50 border-t border-red-100">
                    <span class="text-sm font-bold text-red-700">{{ isRtl ? 'إجمالي الخصومات' : 'Total Deductions' }}</span>
                    <span class="text-base font-bold text-red-700 tabular-nums">{{ formatCurrency(slip?.total_deductions) }}</span>
                </div>
            </div>
        </div>

        <!-- Net Salary -->
        <div
            class="relative overflow-hidden bg-gradient-to-r from-[#4f46e5] to-[#6366f1] rounded-2xl shadow-lg shadow-indigo-500/20 p-6 sm:p-8 text-white transition-all duration-700"
            :class="netLoaded ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-6 scale-95'"
            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-sm text-white/60 font-medium">{{ isRtl ? 'صافي الراتب' : 'Net Salary' }}</p>
                            <p class="text-4xl font-bold tracking-tight">{{ formatCurrency(slip?.net_salary) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 sm:text-right">
                        <!-- Earnings/Deductions mini breakdown -->
                        <div class="flex items-center gap-3">
                            <div class="text-start sm:text-end">
                                <p class="text-[10px] text-white/40 uppercase tracking-wider font-medium">{{ isRtl ? 'المكاسب' : 'Earnings' }}</p>
                                <p class="text-sm font-bold text-white/90 tabular-nums">{{ formatCurrency(slip?.total_earnings) }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-emerald-400/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-start sm:text-end">
                                <p class="text-[10px] text-white/40 uppercase tracking-wider font-medium">{{ isRtl ? 'الخصومات' : 'Deductions' }}</p>
                                <p class="text-sm font-bold text-white/90 tabular-nums">- {{ formatCurrency(slip?.total_deductions) }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-red-400/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                            </div>
                        </div>
                        <!-- Mini bar -->
                        <div class="w-40 h-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-400/60 transition-all duration-1000" :style="{ width: earningsPercent + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
