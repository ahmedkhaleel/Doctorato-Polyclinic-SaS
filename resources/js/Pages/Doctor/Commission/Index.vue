<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import SkeletonLoader from '@/Components/Doctor/SkeletonLoader.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    summary: Object,
    payoutSummary: Object,
    recentPayouts: Array,
    monthlyTrend: Array,
    visits: Object,
    commissionInfo: Object,
    filters: Object,
});

const mounted = ref(false);
const dataLoading = ref(true);
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    setTimeout(() => dataLoading.value = false, 600);
    animateCounters();
});

// Animated counters with pending payout
const animatedValues = ref({ thisMonth: 0, lastMonth: 0, total: 0, pending: 0 });

function animateCounters() {
    const targets = {
        thisMonth: props.summary?.this_month || 0,
        lastMonth: props.summary?.last_month || 0,
        total: props.summary?.total || 0,
        pending: props.payoutSummary?.total_pending || 0,
    };
    const duration = 1200;
    const start = performance.now();
    function step(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        animatedValues.value.thisMonth = Math.round(targets.thisMonth * eased);
        animatedValues.value.lastMonth = Math.round(targets.lastMonth * eased);
        animatedValues.value.total = Math.round(targets.total * eased);
        animatedValues.value.pending = Math.round(targets.pending * eased);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

// Month-over-month growth
const monthGrowth = computed(() => {
    const last = props.summary?.last_month || 0;
    const current = props.summary?.this_month || 0;
    if (last === 0) return current > 0 ? 100 : 0;
    return Math.round(((current - last) / last) * 100);
});

// Hovered bar in trend chart
const hoveredBar = ref(null);

// Share payout link to clipboard
const copiedPayoutId = ref(null);
function sharePayoutLink(payout) {
    const url = `${window.location.origin}/doctor/commission/payouts/${payout.id}`;
    if (navigator.share) {
        navigator.share({ title: payout.payout_number, url }).catch(() => {});
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            copiedPayoutId.value = payout.id;
            setTimeout(() => { copiedPayoutId.value = null; }, 2000);
        });
    }
}

function applyFilter() {
    router.get('/doctor/commission', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilter() {
    dateFrom.value = '';
    dateTo.value = '';
    router.get('/doctor/commission', {}, { preserveState: true, replace: true });
}

const maxTrend = Math.max(...(props.monthlyTrend?.map(d => d.value) || [1]), 1);
const showRates = ref(false);

const payoutStatusConfig = computed(() => ({
    draft: { label: isRtl.value ? 'مسودة' : 'Draft', bg: 'bg-gray-50', text: 'text-gray-600', border: 'border-gray-200', dot: 'bg-gray-400' },
    confirmed: { label: isRtl.value ? 'قيد الانتظار' : 'Pending', bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-400' },
    paid: { label: isRtl.value ? 'مدفوع' : 'Paid', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-red-50', text: 'text-red-600', border: 'border-red-200', dot: 'bg-red-400' },
}));

function formatDate(d) {
    if (!d) return '-';
    const date = new Date(d);
    const today = new Date();
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    if (date.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (date.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/15 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'الأرباح' : 'Earnings' }}</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ $t('a_commission') }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'تتبع أرباحك ومدفوعاتك' : 'Track your earnings and payouts' }}</p>
                    </div>
                    <a href="/doctor/export/commissions"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white/80 bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl transition-all backdrop-blur-sm hover:shadow-lg flex-shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        {{ isRtl ? 'تصدير Excel' : 'Export Excel' }}
                    </a>
                </div>

                <!-- Stats in Hero -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <!-- This Month - Primary stat -->
                    <div class="col-span-2 sm:col-span-1 bg-white/8 backdrop-blur-sm rounded-xl p-4 border border-[#C4A265]/30 ring-1 ring-[#C4A265]/10"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-[#C4A265]/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-[#C4A265]">{{ formatCurrency(animatedValues.thisMonth) }}</p>
                                <div class="flex items-center gap-1.5">
                                    <p class="text-xs text-gray-400">{{ isRtl ? 'هذا الشهر' : 'This Month' }}</p>
                                    <span v-if="monthGrowth !== 0" class="inline-flex items-center gap-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                                        :class="monthGrowth > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'">
                                        <svg v-if="monthGrowth > 0" class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" /></svg>
                                        <svg v-else class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                        {{ Math.abs(monthGrowth) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Last Month -->
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-[#1B365D]/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-white">{{ formatCurrency(animatedValues.lastMonth) }}</p>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'الشهر الماضي' : 'Last Month' }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- Pending Payout -->
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-amber-500/20"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-amber-400">{{ formatCurrency(animatedValues.pending) }}</p>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- Total Earned -->
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.25s"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-emerald-400">{{ formatCurrency(animatedValues.total) }}</p>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي الأرباح' : 'Total Earned' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payout Status Cards - staggered entrance -->
        <div v-if="payoutSummary" class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border border-emerald-100 p-4 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.28s"
            >
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-emerald-600">{{ isRtl ? 'إجمالي المدفوع' : 'Total Paid' }}</span>
                </div>
                <p class="text-lg font-bold text-emerald-700">{{ formatCurrency(payoutSummary.total_paid) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-amber-100 p-4 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.34s"
            >
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-amber-600">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</span>
                </div>
                <p class="text-lg font-bold text-amber-700">{{ formatCurrency(payoutSummary.total_pending) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.40s"
            >
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="w-7 h-7 rounded-lg bg-gray-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">{{ isRtl ? 'مسودة' : 'In Draft' }}</span>
                </div>
                <p class="text-lg font-bold text-gray-600">{{ formatCurrency(payoutSummary.total_draft) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-amber-100 p-4 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.46s"
            >
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-[#C4A265]">{{ isRtl ? 'غير مدفوع' : 'Unpaid' }}</span>
                </div>
                <p class="text-lg font-bold text-[#C4A265]">{{ formatCurrency(payoutSummary.total_unpaid) }}</p>
            </div>
        </div>

        <!-- Skeleton Loader -->
        <SkeletonLoader v-if="dataLoading" type="list" :count="5" />

        <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Main Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recent Payouts -->
                <div v-if="recentPayouts?.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
                >
                    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                            <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'المدفوعات الأخيرة' : 'Recent Payouts' }}</h2>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100/80">
                        <Link v-for="(payout, index) in recentPayouts" :key="payout.id"
                            :href="`/doctor/commission/payouts/${payout.id}`"
                            class="group flex items-center justify-between px-4 sm:px-6 py-4 hover:bg-gray-50/60 transition-all duration-200"
                            :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                            :style="{ transition: 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.35 + index * 0.04}s` }"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center border"
                                    :class="[payoutStatusConfig[payout.status]?.bg, payoutStatusConfig[payout.status]?.border]">
                                    <svg v-if="payout.status === 'paid'" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else-if="payout.status === 'confirmed'" class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-gray-900 transition-colors">{{ payout.payout_number }}</p>
                                    <p class="text-xs text-gray-400">{{ formatDate(payout.period_start) }} – {{ formatDate(payout.period_end) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="text-right">
                                    <p class="text-sm font-bold text-[#C4A265]">{{ formatCurrency(payout.net_amount) }}</p>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                                        :class="[payoutStatusConfig[payout.status]?.bg, payoutStatusConfig[payout.status]?.text, payoutStatusConfig[payout.status]?.border]"
                                    >
                                        <span class="w-1 h-1 rounded-full" :class="payoutStatusConfig[payout.status]?.dot"></span>
                                        {{ payoutStatusConfig[payout.status]?.label }}
                                    </span>
                                </div>
                                <!-- Share / Copy link button -->
                                <button v-if="payout.status === 'paid'"
                                    @click.stop.prevent="sharePayoutLink(payout)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 hover:bg-[#C4A265]/10"
                                    :title="isRtl ? 'نسخ الرابط' : 'Copy link'"
                                >
                                    <svg v-if="copiedPayoutId === payout.id" class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-3.5 h-3.5 text-gray-400 hover:text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                                </button>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-[#C4A265] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Commission Details Table with Filter -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.35s"
                >
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-100 gap-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تفاصيل العمولة' : 'Commission Details' }}</h2>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <input v-model="dateFrom" type="date" :max="dateTo || undefined" class="doctorato-input text-xs border border-gray-200 rounded-lg px-2.5 py-1.5 bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265]" />
                            <input v-model="dateTo" type="date" :min="dateFrom || undefined" class="doctorato-input text-xs border border-gray-200 rounded-lg px-2.5 py-1.5 bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265]" />
                            <button @click="applyFilter" class="px-3 py-1.5 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] rounded-lg transition-colors">{{ isRtl ? 'فلتر' : 'Filter' }}</button>
                            <button v-if="dateFrom || dateTo" @click="clearFilter" class="px-3 py-1.5 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">{{ isRtl ? 'مسح' : 'Clear' }}</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80">
                                    <th class="ltr:text-left rtl:text-right px-4 sm:px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                    <th class="hidden sm:table-cell ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                                    <th class="hidden sm:table-cell ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                    <th class="ltr:text-right rtl:text-left px-4 sm:px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'النسبة' : 'Rate' }}</th>
                                    <th class="ltr:text-right rtl:text-left px-4 sm:px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'العمولة' : 'Commission' }}</th>
                                    <th class="hidden sm:table-cell text-center px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الدفعة' : 'Payout' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="visit in visits.data" :key="visit.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 sm:px-6 py-3 font-semibold text-gray-800">{{ visit.patient?.full_name }}</td>
                                    <td class="hidden sm:table-cell px-6 py-3 text-gray-500">{{ (isRtl ? (visit.service?.name_ar || visit.service?.name_en) : visit.service?.name_en) || '-' }}</td>
                                    <td class="hidden sm:table-cell px-6 py-3 text-gray-500">{{ formatDate(visit.visit_date) }}</td>
                                    <td class="px-4 sm:px-6 py-3 text-right">
                                        <span class="font-semibold" :class="visit.commission_rate != commissionInfo?.default_rate ? 'text-[#C4A265]' : 'text-gray-500'">
                                            {{ visit.commission_rate }}%
                                        </span>
                                        <span v-if="visit.commission_rate != commissionInfo?.default_rate" class="text-[9px] text-[#C4A265] block">{{ isRtl ? 'مخصص' : 'custom' }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3 text-right font-bold text-[#C4A265]">{{ formatCurrency(visit.commission_amount) }}</td>
                                    <td class="hidden sm:table-cell px-6 py-3 text-center">
                                        <Link v-if="visit.payout_status === 'paid'" :href="`/doctor/commission/payouts/${visit.payout_id}`"
                                            class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors">
                                            <span class="w-1 h-1 rounded-full bg-emerald-500"></span> {{ isRtl ? 'مدفوع' : 'Paid' }}
                                        </Link>
                                        <Link v-else-if="visit.payout_status === 'confirmed'" :href="`/doctor/commission/payouts/${visit.payout_id}`"
                                            class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition-colors">
                                            <span class="w-1 h-1 rounded-full bg-amber-400"></span> {{ isRtl ? 'قيد الانتظار' : 'Pending' }}
                                        </Link>
                                        <span v-else-if="visit.payout_status === 'draft'" class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-50 text-gray-500 border border-gray-200">
                                            <span class="w-1 h-1 rounded-full bg-gray-400"></span> {{ isRtl ? 'مسودة' : 'Draft' }}
                                        </span>
                                        <span v-else class="text-[10px] text-gray-300">&mdash;</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="visits.data?.length === 0" class="py-16 text-center">
                        <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-3 border border-gray-100">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد سجلات عمولة لهذه الفترة' : 'No commission records for this period' }}</p>
                    </div>

                    <div v-if="visits.links?.length > 3" class="flex items-center justify-center flex-wrap gap-1 px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                        <template v-for="link in visits.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                            <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Commission Trend -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
                >
                    <div class="px-3 sm:px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الاتجاه (6 أشهر)' : 'Trend (6 months)' }}</h3>
                    </div>
                    <div class="p-3 sm:p-5">
                        <div class="flex items-end gap-2 h-32">
                            <div v-for="(item, i) in monthlyTrend" :key="item.label"
                                class="flex-1 flex flex-col items-center gap-1 relative group cursor-pointer"
                                @mouseenter="hoveredBar = i" @mouseleave="hoveredBar = null"
                            >
                                <!-- Hover tooltip -->
                                <div v-if="hoveredBar === i"
                                    class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] font-semibold px-2 py-1 rounded-md shadow-lg whitespace-nowrap z-10"
                                    style="animation: fadeIn 0.15s ease-out"
                                >
                                    {{ formatCurrency(item.value) }}
                                    <div class="absolute top-full left-1/2 -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                                </div>
                                <span class="text-[10px] font-medium tabular-nums transition-colors duration-200"
                                    :class="hoveredBar === i ? 'text-[#C4A265] font-bold' : 'text-gray-500'">
                                    {{ item.value?.toLocaleString() }}
                                </span>
                                <div class="w-full rounded-t-lg transition-all duration-700 ease-out"
                                    :class="hoveredBar === i ? 'bg-gradient-to-t from-[#A68B52] to-[#C4A265] shadow-lg shadow-[#C4A265]/20' : 'bg-gradient-to-t from-[#C4A265] to-[#D4B87A]'"
                                    :style="{ height: mounted ? Math.max(4, (item.value / maxTrend) * 88) + 'px' : '4px', transitionDelay: `${0.4 + i * 0.08}s` }"
                                ></div>
                                <span class="text-[10px] transition-colors duration-200"
                                    :class="hoveredBar === i ? 'text-gray-700 font-semibold' : 'text-gray-400'">
                                    {{ item.label }}
                                </span>
                            </div>
                        </div>
                        <!-- Trend summary line -->
                        <div v-if="monthlyTrend?.length >= 2" class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-[10px] text-gray-400">{{ isRtl ? 'المتوسط الشهري' : 'Monthly avg' }}</span>
                            <span class="text-xs font-bold text-gray-600">{{ formatCurrency(Math.round(monthlyTrend.reduce((s, m) => s + (m.value || 0), 0) / monthlyTrend.length)) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Commission Rates -->
                <div v-if="commissionInfo" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.35s"
                >
                    <div class="px-3 sm:px-5 py-4 cursor-pointer hover:bg-gray-50/50 transition-colors flex items-center justify-between" @click="showRates = !showRates">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'نسبي' : 'My Rates' }}</h3>
                                <p class="text-[10px] text-gray-400">{{ isRtl ? 'الافتراضي:' : 'Default:' }} <span class="font-bold text-[#C4A265]">{{ commissionInfo.default_rate }}%</span></p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': showRates }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>

                    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <div v-if="showRates" class="px-3 sm:px-5 pb-5 space-y-1.5">
                            <div class="bg-slate-50/50 rounded-lg p-3 mb-3">
                                <p class="text-[10px] text-[#1B365D] leading-relaxed">
                                    <strong>{{ isRtl ? 'كيف تعمل:' : 'How it works:' }}</strong> {{ isRtl ? `نسبة مخصصة لخدمة معينة، أو النسبة الافتراضية (${commissionInfo.default_rate}%). العمولة = (الفاتورة - تكلفة التوريد) × النسبة%.` : `Custom rate for specific service, or default rate (${commissionInfo.default_rate}%). Commission = (Invoice - Supply Cost) x Rate%.` }}
                                </p>
                            </div>
                            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    <span class="text-xs text-gray-600">{{ isRtl ? 'النسبة الافتراضية' : 'Default Rate' }}</span>
                                </div>
                                <span class="text-xs font-bold text-gray-800">{{ commissionInfo.default_rate }}%</span>
                            </div>
                            <div v-for="(sr, idx) in commissionInfo.service_rates" :key="idx"
                                class="flex items-center justify-between py-2 px-3 rounded-lg"
                                :class="idx % 2 === 0 ? 'bg-[#C4A265]/5' : ''">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C4A265]"></span>
                                    <span class="text-xs text-gray-700">{{ sr.service_name }}</span>
                                </div>
                                <span class="text-xs font-bold text-[#C4A265]">{{ sr.rate }}%</span>
                            </div>
                            <p v-if="!commissionInfo.service_rates?.length" class="text-[10px] text-gray-400 text-center py-2">{{ isRtl ? 'لا توجد نسب مخصصة. النسبة الافتراضية تنطبق على الكل.' : 'No custom rates. Default rate applies to all.' }}</p>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateX(-50%) translateY(4px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}
</style>
