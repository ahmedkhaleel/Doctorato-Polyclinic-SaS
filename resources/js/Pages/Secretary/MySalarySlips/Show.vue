<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    slip: Object,
});

const months = isRtl.value
    ? ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
    : ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const { formatCurrency, currencyCode } = useCurrency();

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const earnings = [
    { label: 'Basic Salary', labelAr: 'الراتب الأساسي', key: 'basic_salary' },
    { label: 'Housing Allowance', labelAr: 'بدل السكن', key: 'housing_allowance' },
    { label: 'Transport Allowance', labelAr: 'بدل النقل', key: 'transport_allowance' },
    { label: 'Other Allowances', labelAr: 'بدلات أخرى', key: 'other_allowances' },
    { label: 'Overtime', labelAr: 'العمل الإضافي', key: 'overtime_amount' },
    { label: 'Bonus', labelAr: 'المكافأة', key: 'bonus' },
    { label: 'Commission', labelAr: 'العمولة', key: 'commission_amount' },
];

const deductions = [
    { label: 'Insurance', labelAr: 'التأمين', key: 'insurance_deduction' },
    { label: 'Tax', labelAr: 'الضريبة', key: 'tax_deduction' },
    { label: 'Absence', labelAr: 'خصم الغياب', key: 'absence_deduction' },
    { label: 'Advance', labelAr: 'خصم السلفة', key: 'advance_deduction' },
    { label: 'Penalty', labelAr: 'خصم الجزاء', key: 'penalty_deduction' },
    { label: 'Other Deductions', labelAr: 'خصومات أخرى', key: 'other_deductions' },
];

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- ═══ HERO HEADER ═══ -->
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 30% 50%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <Link href="/secretary/my-salary-slips" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-400 hover:text-white transition-colors mb-4">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    {{ isRtl ? 'العودة لكشوف المرتبات' : 'Back to Salary Slips' }}
                </Link>
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'تفاصيل كشف الراتب' : 'Salary Slip Details' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ slip.slip_number }} &mdash; {{ months[slip.month] }} {{ slip.year }}</p>
                    </div>
                    <button @click="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        {{ isRtl ? 'طباعة' : 'Print' }}
                    </button>
                </div>

                <!-- Net Salary Highlight -->
                <div class="mt-6 bg-white/5 backdrop-blur-sm rounded-xl p-5 border border-white/10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-[#0d9488]/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'صافي الراتب' : 'Net Salary' }}</p>
                                <p class="text-3xl font-black text-white">{{ formatCurrency(slip.net_salary) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="text-center">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'الإجمالي' : 'Earnings' }}</p>
                                <p class="text-lg font-bold text-emerald-400">{{ formatCurrency(slip.total_earnings) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'الخصومات' : 'Deductions' }}</p>
                                <p class="text-lg font-bold text-red-400">{{ formatCurrency(slip.total_deductions) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <!-- Employee Info Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    {{ isRtl ? 'معلومات الموظف' : 'Employee Information' }}
                </h3>
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
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ isRtl ? 'القسم' : 'Department' }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ isRtl ? (slip.employee?.department?.name_ar || slip.employee?.department?.name_en) : slip.employee?.department?.name_en || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Two-column: Earnings & Deductions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Earnings -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'المستحقات' : 'Earnings' }}</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="item in earnings" :key="item.key" class="flex items-center justify-between px-6 py-3 hover:bg-gray-50/50">
                            <span class="text-sm text-gray-700">{{ isRtl ? item.labelAr : item.label }}</span>
                            <span class="text-sm font-medium text-gray-800">{{ formatCurrency(slip[item.key]) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-6 py-3 border-t-2 border-emerald-200 bg-emerald-50/50">
                        <span class="text-sm font-bold text-gray-800">{{ isRtl ? 'إجمالي المستحقات' : 'Total Earnings' }}</span>
                        <span class="text-sm font-bold text-[#0d9488]">{{ formatCurrency(slip.total_earnings) }}</span>
                    </div>
                </div>

                <!-- Deductions -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الخصومات' : 'Deductions' }}</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="item in deductions" :key="item.key" class="flex items-center justify-between px-6 py-3 hover:bg-gray-50/50">
                            <span class="text-sm text-gray-700">{{ isRtl ? item.labelAr : item.label }}</span>
                            <span class="text-sm font-medium text-red-600">{{ formatCurrency(slip[item.key]) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-6 py-3 border-t-2 border-red-200 bg-red-50/50">
                        <span class="text-sm font-bold text-gray-800">{{ isRtl ? 'إجمالي الخصومات' : 'Total Deductions' }}</span>
                        <span class="text-sm font-bold text-red-600">{{ formatCurrency(slip.total_deductions) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    {{ isRtl ? 'تفاصيل الدفع' : 'Payment Details' }}
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ isRtl ? 'طريقة الدفع' : 'Payment Method' }}</p>
                        <p class="text-sm text-gray-700 mt-0.5 capitalize">{{ slip.payment_method?.replace(/_/g, ' ') || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ isRtl ? 'تاريخ الدفع' : 'Paid At' }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ formatDate(slip.paid_at) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ isRtl ? 'الفترة' : 'Period' }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ months[slip.month] }} {{ slip.year }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
