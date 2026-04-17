<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';
import SkeletonLoader from '@/Components/Doctor/SkeletonLoader.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency } = useCurrency();

const props = defineProps({
    slips: Object,
});

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
const dataLoading = ref(true);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
    setTimeout(() => dataLoading.value = false, 600);
});

const monthNamesEn = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const monthNamesAr = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function periodLabel(slip) {
    const names = isRtl.value ? monthNamesAr : monthNamesEn;
    return `${names[slip.month] || slip.month} ${slip.year}`;
}

const slipsList = computed(() => props.slips?.data || []);

const totalEarnings = computed(() => slipsList.value.reduce((s, sl) => s + Number(sl.total_earnings || 0), 0));
const totalDeductions = computed(() => slipsList.value.reduce((s, sl) => s + Number(sl.total_deductions || 0), 0));
const totalNet = computed(() => slipsList.value.reduce((s, sl) => s + Number(sl.net_salary || 0), 0));
const avgNet = computed(() => slipsList.value.length ? totalNet.value / slipsList.value.length : 0);

// Animated counters
const animatedEarnings = ref(0);
const animatedDeductions = ref(0);
const animatedNet = ref(0);

function animateCounter(targetRef, endValue, duration = 1200) {
    const start = 0;
    const startTime = performance.now();
    function step(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        targetRef.value = Math.round(start + (endValue - start) * eased);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

onMounted(() => {
    setTimeout(() => {
        animateCounter(animatedEarnings, totalEarnings.value);
        animateCounter(animatedDeductions, totalDeductions.value);
        animateCounter(animatedNet, totalNet.value);
    }, 400);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-[#1B365D]/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-[#C4A265]/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-slate-400 to-[#1B365D] flex items-center justify-center shadow-lg shadow-[#1B365D]/20">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-white">{{ $t('a_my_salary_slips') }}</h1>
                        <p class="text-sm text-gray-400 mt-0.5">{{ isRtl ? 'عرض وتحميل كشوف الرواتب' : 'View and download your salary slips' }}</p>
                    </div>
                    <!-- Export Button -->
                    <a v-if="slipsList.length" href="/doctor/export/salary-slips"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white/80 bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl transition-all backdrop-blur-sm hover:shadow-lg"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        {{ isRtl ? 'تصدير Excel' : 'Export Excel' }}
                    </a>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 group hover:bg-white/[0.08] transition-all duration-300">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'إجمالي المكاسب' : 'Total Earnings' }}</p>
                        <p class="text-lg font-bold text-emerald-400 mt-1 tabular-nums">{{ formatCurrency(animatedEarnings) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 group hover:bg-white/[0.08] transition-all duration-300">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'إجمالي الخصومات' : 'Total Deductions' }}</p>
                        <p class="text-lg font-bold text-red-400 mt-1 tabular-nums">{{ formatCurrency(animatedDeductions) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 group hover:bg-white/[0.08] transition-all duration-300">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'صافي الراتب' : 'Net Total' }}</p>
                        <p class="text-lg font-bold text-white mt-1 tabular-nums">{{ formatCurrency(animatedNet) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 group hover:bg-white/[0.08] transition-all duration-300">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'متوسط الصافي' : 'Avg Net' }}</p>
                        <p class="text-lg font-bold text-[#C4A265] mt-1 tabular-nums">{{ formatCurrency(avgNet) }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ slipsList.length }} {{ isRtl ? 'كشف' : 'slips' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salary Slips Cards -->
        <div
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <SkeletonLoader v-if="dataLoading" type="list" :count="4" />
            <div v-else-if="slipsList.length" class="space-y-3">
                <div
                    v-for="(slip, index) in slipsList"
                    :key="slip.id"
                    class="group bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 overflow-hidden"
                    :style="{ animationDelay: `${index * 60}ms` }"
                >
                    <!-- Hover accent stripe -->
                    <div class="absolute top-0 bottom-0 w-1 bg-gradient-to-b from-slate-400 to-[#1B365D] rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300" :class="isRtl ? 'right-0' : 'left-0'"></div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5 relative">
                        <!-- Period -->
                        <div class="flex items-center gap-3 sm:w-52">
                            <div class="w-13 h-13 rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 flex flex-col items-center justify-center shadow-sm group-hover:shadow-md group-hover:scale-105 transition-all duration-300">
                                <span class="text-lg font-bold text-[#1B365D] leading-none">{{ slip.month }}</span>
                                <span class="text-[9px] text-slate-400 font-semibold">{{ slip.year }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ periodLabel(slip) }}</p>
                                <p v-if="slip.slip_number" class="text-xs text-gray-400 font-mono mt-0.5">#{{ slip.slip_number }}</p>
                            </div>
                        </div>

                        <!-- Financial Info -->
                        <div class="flex-1 grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المكاسب' : 'Earnings' }}</p>
                                <p class="text-sm font-bold text-emerald-600 tabular-nums mt-0.5">{{ formatCurrency(slip.total_earnings) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الخصومات' : 'Deductions' }}</p>
                                <p class="text-sm font-bold text-red-500 tabular-nums mt-0.5">{{ formatCurrency(slip.total_deductions) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الصافي' : 'Net' }}</p>
                                <p class="text-sm font-bold text-[#1B365D] tabular-nums mt-0.5">{{ formatCurrency(slip.net_salary) }}</p>
                            </div>
                        </div>

                        <!-- Paid Date & Action -->
                        <div class="flex items-center gap-3 flex-wrap">
                            <div v-if="slip.paid_at" class="text-xs text-gray-400">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ formatDate(slip.paid_at) }}
                                </span>
                            </div>
                            <div v-else class="text-xs">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-50 text-amber-600 rounded-lg font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ isRtl ? 'بانتظار الدفع' : 'Pending' }}
                                </span>
                            </div>
                            <Link
                                :href="`/doctor/my-salary-slips/${slip.id}`"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-[#1B365D] hover:bg-[#1B365D] rounded-xl shadow-sm hover:shadow-md transition-all duration-200 group/btn"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                {{ isRtl ? 'عرض' : 'View' }}
                                <svg class="w-3 h-3 opacity-0 -translate-x-1 group-hover/btn:opacity-100 group-hover/btn:translate-x-0 transition-all duration-200 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-20 bg-white rounded-2xl border border-gray-100 relative overflow-hidden">
                <!-- Decorative background -->
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle at 1px 1px, #6366f1 1px, transparent 0); background-size: 32px 32px;"></div>
                <div class="relative">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl flex items-center justify-center mb-5 shadow-inner">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <p class="text-base font-semibold text-gray-600 mb-1">{{ isRtl ? 'لا توجد كشوف رواتب' : 'No salary slips found' }}</p>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'ستظهر كشوف الرواتب هنا عند إصدارها' : 'Salary slips will appear here when issued' }}</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="slips.links?.length > 3" class="flex items-center justify-center gap-1 mt-6">
                <template v-for="link in slips.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
                        :class="link.active ? 'bg-[#1B365D] text-white shadow-sm shadow-[#1B365D]/20' : 'text-gray-500 hover:bg-gray-100 bg-white border border-gray-200 hover:border-gray-300'"
                        v-html="link.label"
                        preserve-state
                    />
                    <span v-else class="px-3 py-2 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
