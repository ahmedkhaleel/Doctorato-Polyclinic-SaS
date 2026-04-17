<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { useCurrency } from '@/Composables/useCurrency.js';
import SkeletonLoader from '@/Components/Doctor/SkeletonLoader.vue';
import { getStatusConfig } from '@/Constants/visitStatus';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    today: Object,
    monthly: Object,
    prevMonthly: Object,
    completionRate: Number,
    visitTrend: Array,
    todayQueue: Array,
    recentVisits: Array,
    upcomingBookings: Array,
    payoutSummary: Object,
    doctorInfo: Object,
    dental: Object,
    pediatric: Object,
    pendingFollowups: Array,
    todayMedicalAlerts: Array,
});

// Animation state
const mounted = ref(false);
const dataLoading = ref(true);
const animatedValues = ref({
    todayTotal: 0,
    waiting: 0,
    completed: 0,
    commission: 0,
    completionRate: 0,
});

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    setTimeout(() => { dataLoading.value = false; }, 500);
    animateCounters();
});

function animateCounters() {
    const targets = {
        todayTotal: props.today?.total || 0,
        waiting: props.today?.waiting || 0,
        completed: props.today?.completed || 0,
        commission: props.monthly?.total_commission || 0,
        completionRate: props.completionRate || 0,
    };

    const duration = 1200;
    const start = performance.now();

    function step(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic

        animatedValues.value.todayTotal = Math.round(targets.todayTotal * eased);
        animatedValues.value.waiting = Math.round(targets.waiting * eased);
        animatedValues.value.completed = Math.round(targets.completed * eased);
        animatedValues.value.commission = Math.round(targets.commission * eased);
        animatedValues.value.completionRate = Math.round(targets.completionRate * eased);

        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

const maxTrend = computed(() => Math.max(...(props.visitTrend?.map(d => d.value) || [1]), 1));

const greeting = computed(() => {
    const h = new Date().getHours();
    if (isRtl.value) {
        if (h < 12) return 'صباح الخير';
        if (h < 17) return 'مساء الخير';
        return 'مساء الخير';
    }
    if (h < 12) return 'Good Morning';
    if (h < 17) return 'Good Afternoon';
    return 'Good Evening';
});

const currentDate = computed(() => {
    return new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
});

// Month-over-month change
function getChange(current, previous) {
    if (!previous || previous === 0) return current > 0 ? 100 : 0;
    return Math.round(((current - previous) / previous) * 100);
}

const visitChange = computed(() => getChange(props.monthly?.total_visits, props.prevMonthly?.total_visits));
const commissionChange = computed(() => getChange(props.monthly?.total_commission, props.prevMonthly?.total_commission));

const statusConfig = computed(() => getStatusConfig(isRtl.value));

// Current time for timeline
const currentTimeString = computed(() => {
    const now = new Date();
    return now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
});

// Follow-up urgency helper
function getFollowupUrgency(dueDate) {
    if (!dueDate) return 'upcoming';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(dueDate);
    due.setHours(0, 0, 0, 0);
    if (due < today) return 'overdue';
    if (due.getTime() === today.getTime()) return 'today';
    return 'upcoming';
}

// Completion rate ring
const completionDash = computed(() => {
    const circumference = 2 * Math.PI * 40;
    const offset = circumference - ((props.completionRate || 0) / 100) * circumference;
    return { circumference, offset };
});
</script>

<template>
    <div>
        <!-- Hero Header -->
        <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-5 sm:p-7 shadow-xl"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)">
            <!-- Gold accent line -->
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-[#C4A265]/20 blur-3xl -translate-y-1/2 translate-x-1/4"></div>
            <div class="absolute bottom-0 left-1/3 w-64 h-64 rounded-full bg-[#C4A265]/10 blur-3xl translate-y-1/2"></div>
            <div class="absolute top-4 right-6 opacity-[0.03]">
                <svg class="w-48 h-48 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            </div>

            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</span>
                    </div>
                    <p class="text-white/60 text-xs font-medium tracking-wide uppercase mb-1">{{ currentDate }}</p>
                    <h1 class="text-2xl font-bold text-white">
                        {{ greeting }}, <span class="text-[#C4A265]">Dr. {{ $page.props.auth?.doctor?.name_en || 'Doctor' }}</span>
                    </h1>
                    <p class="text-sm text-gray-400 mt-2">{{ isRtl ? 'إليك نظرة عامة على عيادتك اليوم' : 'Here\'s your practice overview for today' }}</p>
                </div>
                <Link href="/doctor/queue" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm text-white text-xs font-semibold rounded-xl hover:bg-white/20 transition-all border border-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    {{ isRtl ? 'عرض الطابور' : 'View Queue' }}
                </Link>
            </div>

            <!-- Inline Mini Stats -->
            <div class="relative z-10 grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
                <div class="bg-white/[0.06] backdrop-blur-sm rounded-xl p-3.5 border border-white/[0.08] hover:bg-white/[0.1] transition-all duration-300 group">
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-6 h-6 rounded-lg bg-[#C4A265]/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'اليوم' : 'Today' }}</span>
                    </div>
                    <p class="text-2xl font-bold text-white tabular-nums group-hover:text-[#C4A265] transition-colors">{{ animatedValues.todayTotal }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'إجمالي الزيارات' : 'visits total' }}</p>
                </div>
                <div class="bg-white/[0.06] backdrop-blur-sm rounded-xl p-3.5 border border-white/[0.08] hover:bg-white/[0.1] transition-all duration-300 group">
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-6 h-6 rounded-lg bg-amber-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'انتظار' : 'Waiting' }}</span>
                    </div>
                    <p class="text-2xl font-bold text-white tabular-nums group-hover:text-amber-400 transition-colors">{{ animatedValues.waiting }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'في الانتظار' : 'in queue' }}</p>
                </div>
                <div class="bg-white/[0.06] backdrop-blur-sm rounded-xl p-3.5 border border-white/[0.08] hover:bg-white/[0.1] transition-all duration-300 group">
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-6 h-6 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'مكتمل' : 'Completed' }}</span>
                    </div>
                    <p class="text-2xl font-bold text-white tabular-nums group-hover:text-emerald-400 transition-colors">{{ animatedValues.completed }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'اليوم' : 'today' }}</p>
                </div>
                <div class="bg-white/[0.06] backdrop-blur-sm rounded-xl p-3.5 border border-white/[0.08] hover:bg-white/[0.1] transition-all duration-300 group">
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-6 h-6 rounded-lg bg-[#C4A265]/20 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'العمولة' : 'Commission' }}</span>
                    </div>
                    <p class="text-xl font-bold text-[#C4A265] tabular-nums">{{ formatCurrency(animatedValues.commission) }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ currencyCode }} {{ isRtl ? 'هذا الشهر' : 'this month' }}</p>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Left Column (2 cols) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Today's Queue -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.15s">
                    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'طابور اليوم' : 'Today\'s Queue' }}</h2>
                                <p class="text-[10px] text-gray-400">{{ isRtl ? `${todayQueue.length} مريض في الانتظار` : `${todayQueue.length} patient${todayQueue.length !== 1 ? 's' : ''} waiting` }}</p>
                            </div>
                        </div>
                        <Link href="/doctor/queue" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#C4A265] hover:text-[#B3914F] px-3 py-1.5 rounded-lg hover:bg-[#C4A265]/5 transition-all">
                            {{ isRtl ? 'عرض الكل' : 'View all' }}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                    <SkeletonLoader v-if="dataLoading" type="list" :count="4" />
                    <div v-else-if="todayQueue.length > 0" class="divide-y divide-gray-50">
                        <div v-for="(visit, i) in todayQueue" :key="visit.id"
                            class="flex items-center justify-between px-4 sm:px-6 py-3 hover:bg-gray-50/60 transition-all duration-200 group"
                            :class="mounted ? 'translate-x-0 opacity-100' : '-translate-x-4 opacity-0'"
                            :style="{ transitionDelay: (0.2 + i * 0.05) + 's' }">
                            <div class="flex items-center gap-3.5">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#C4A265]/20 to-[#C4A265]/5 flex items-center justify-center text-sm font-bold text-[#C4A265] group-hover:scale-105 transition-transform">
                                        {{ visit.patient?.full_name?.charAt(0) || '?' }}
                                    </div>
                                    <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white flex items-center justify-center"
                                        :class="visit.status === 'in_progress' ? 'bg-[#1B365D]' : 'bg-amber-400'">
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ visit.patient?.full_name }}</p>
                                    <p class="text-[11px] text-gray-400 flex items-center gap-1.5">
                                        <span>{{ visit.service?.name_en || visit.visit_type }}</span>
                                        <span v-if="visit.patient?.file_number" class="text-gray-300">&middot;</span>
                                        <span v-if="visit.patient?.file_number" class="font-mono text-[10px]">#{{ visit.patient.file_number }}</span>
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold px-2.5 py-1 rounded-full"
                                :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[visit.status]?.dot"
                                    :style="visit.status === 'in_progress' ? 'animation: pulse 2s infinite' : ''"></span>
                                {{ statusConfig[visit.status]?.label || visit.status }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="py-14 text-center">
                        <div class="inline-flex flex-col items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'الطابور فارغ' : 'Queue is clear' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'لا يوجد مرضى في الانتظار حالياً' : 'No patients waiting right now' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Timeline -->
                <div v-if="todayQueue.length > 0"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.2s">
                    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'جدول اليوم' : 'Today\'s Timeline' }}</h2>
                                <p class="text-[10px] text-gray-400">{{ isRtl ? 'الترتيب الزمني لمرضى اليوم' : 'Chronological view of today\'s patients' }}</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-semibold text-gray-400 tabular-nums">{{ currentTimeString }}</span>
                    </div>
                    <div class="px-4 sm:px-6 py-4">
                        <div class="relative">
                            <!-- Timeline vertical line -->
                            <div class="absolute top-0 bottom-0 w-0.5 bg-gray-100 rounded-full"
                                :class="isRtl ? 'right-[15px]' : 'left-[15px]'"></div>

                            <!-- Current time indicator -->
                            <div class="absolute top-0 z-10 flex items-center gap-2"
                                :class="isRtl ? 'right-[9px]' : 'left-[9px]'">
                                <div class="w-3 h-3 rounded-full bg-[#C4A265] shadow-[0_0_0_4px_rgba(196,162,101,0.15)]"
                                    style="animation: pulse 2s infinite"></div>
                                <span class="text-[9px] font-bold text-[#C4A265] uppercase tracking-wider"
                                    :class="isRtl ? 'mr-1' : 'ml-1'">{{ isRtl ? 'الآن' : 'NOW' }}</span>
                            </div>

                            <!-- Timeline items -->
                            <div class="space-y-0 pt-6">
                                <div v-for="(visit, i) in todayQueue" :key="'tl-' + visit.id"
                                    class="relative flex items-start gap-4 pb-4"
                                    :class="[
                                        isRtl ? 'pr-10' : 'pl-10',
                                        mounted ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'
                                    ]"
                                    :style="{ transition: 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1) ' + (0.25 + i * 0.06) + 's' }">
                                    <!-- Node dot -->
                                    <div class="absolute top-1 w-[11px] h-[11px] rounded-full border-2 border-white shadow-sm"
                                        :class="[
                                            isRtl ? 'right-[10px]' : 'left-[10px]',
                                            visit.status === 'waiting' ? 'bg-amber-400' :
                                            visit.status === 'in_progress' ? 'bg-[#1B365D]' :
                                            visit.status === 'completed' ? 'bg-emerald-500' : 'bg-gray-300'
                                        ]"></div>

                                    <!-- Content -->
                                    <div class="flex-1 flex items-center justify-between gap-3 p-3 rounded-xl border transition-all duration-200 hover:shadow-sm"
                                        :class="visit.status === 'in_progress' ? 'bg-slate-50/50 border-slate-100' :
                                                visit.status === 'completed' ? 'bg-emerald-50/30 border-emerald-100/60' :
                                                'bg-gray-50/50 border-gray-100/60'">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-bold shrink-0"
                                                :class="visit.status === 'waiting' ? 'bg-amber-100 text-amber-700' :
                                                        visit.status === 'in_progress' ? 'bg-slate-100 text-[#1B365D]' :
                                                        'bg-emerald-100 text-emerald-700'">
                                                {{ visit.patient?.full_name?.charAt(0) || '?' }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-gray-800 truncate">{{ visit.patient?.full_name }}</p>
                                                <p class="text-[10px] text-gray-400 truncate">{{ visit.service?.name_en || visit.visit_type }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-[10px] font-medium text-gray-400 tabular-nums">
                                                {{ visit.checked_in_at ? new Date(visit.checked_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '--:--' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 text-[9px] font-semibold px-2 py-0.5 rounded-full"
                                                :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text]">
                                                <span class="w-1 h-1 rounded-full" :class="statusConfig[visit.status]?.dot"
                                                    :style="visit.status === 'in_progress' ? 'animation: pulse 2s infinite' : ''"></span>
                                                {{ statusConfig[visit.status]?.label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Completed Visits -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.3s">
                    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'آخر المكتملة' : 'Recent Completed' }}</h2>
                                <p class="text-[10px] text-gray-400">{{ isRtl ? 'آخر الزيارات مع تفاصيل العمولة' : 'Latest visits with commission details' }}</p>
                            </div>
                        </div>
                        <Link href="/doctor/visits" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#C4A265] hover:text-[#B3914F] px-3 py-1.5 rounded-lg hover:bg-[#C4A265]/5 transition-all">
                            {{ isRtl ? 'كل الزيارات' : 'All visits' }}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                    <SkeletonLoader v-if="dataLoading" type="card" :count="3" />
                    <div v-else-if="recentVisits?.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-50">
                                    <th class="ltr:text-left rtl:text-right px-4 sm:px-6 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                    <th class="ltr:text-left rtl:text-right px-3 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'النوع' : 'Type' }}</th>
                                    <th class="ltr:text-right rtl:text-left px-3 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                                    <th class="hidden sm:table-cell ltr:text-right rtl:text-left px-3 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'النسبة' : 'Rate' }}</th>
                                    <th class="ltr:text-right rtl:text-left px-4 sm:px-6 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'العمولة' : 'Commission' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="visit in recentVisits" :key="visit.id" class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#C4A265]/15 to-[#C4A265]/5 flex items-center justify-center text-[10px] font-bold text-[#C4A265]">
                                                {{ visit.patient?.full_name?.charAt(0) || '?' }}
                                            </div>
                                            <span class="text-xs font-semibold text-gray-800">{{ visit.patient?.full_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize"
                                            :class="visit.visit_type === 'consultation' ? 'bg-slate-50 text-[#1B365D]' : 'bg-slate-50 text-[#1B365D]'">
                                            {{ visit.visit_type === 'consultation' ? (isRtl ? 'استشارة' : 'Consultation') : (visit.service?.name_en || (isRtl ? 'جلسة' : 'Session')) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <span class="text-xs font-medium text-gray-600 tabular-nums">{{ formatCurrency(visit.visit_amount) }}</span>
                                    </td>
                                    <td class="hidden sm:table-cell px-3 py-3 text-right">
                                        <span class="text-xs font-medium text-gray-500 tabular-nums">{{ parseFloat(visit.commission_rate || 0) }}%</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3 text-right">
                                        <span class="text-sm font-bold tabular-nums" :class="parseFloat(visit.commission_amount || 0) > 0 ? 'text-[#C4A265]' : 'text-gray-300'">
                                            {{ formatCurrency(visit.commission_amount) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="py-14 text-center">
                        <div class="inline-flex flex-col items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد زيارات مكتملة بعد' : 'No completed visits yet' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-5">

                <!-- Completion Rate Ring -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.1s">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-4">{{ isRtl ? 'نسبة الإكمال' : 'Completion Rate' }}</h3>
                    <div class="flex items-center gap-5">
                        <div class="relative w-24 h-24 shrink-0">
                            <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#f3f4f6" stroke-width="8" />
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#C4A265" stroke-width="8"
                                    stroke-linecap="round"
                                    :stroke-dasharray="completionDash.circumference"
                                    :stroke-dashoffset="completionDash.offset"
                                    style="transition: stroke-dashoffset 1.5s cubic-bezier(0.16, 1, 0.3, 1)" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xl font-bold text-gray-800 tabular-nums">{{ animatedValues.completionRate }}%</span>
                            </div>
                        </div>
                        <div class="space-y-2 flex-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'مكتملة' : 'Completed' }}</span>
                                <span class="font-bold text-emerald-600">{{ monthly.completed_visits }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                                <span class="font-bold text-gray-700">{{ monthly.total_visits }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'المرضى' : 'Patients' }}</span>
                                <span class="font-bold text-gray-700">{{ monthly.unique_patients }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commission & Rates Card -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#C4A265]/10 via-[#D4B87A]/5 to-[#C4A265]/10 border border-[#C4A265]/15 p-5"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.2s">
                    <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-[#C4A265] to-[#D4B87A]"></div>
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'نسبك' : 'Your Rates' }}</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-white/80 rounded-xl p-3 text-center hover:shadow-sm transition-shadow">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">{{ isRtl ? 'العمولة' : 'Commission' }}</p>
                            <p class="text-xl font-bold text-[#C4A265]">{{ doctorInfo?.default_commission_percentage || 0 }}%</p>
                        </div>
                        <div class="bg-white/80 rounded-xl p-3 text-center hover:shadow-sm transition-shadow">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-0.5">{{ isRtl ? 'رسوم الاستشارة' : 'Consult Fee' }}</p>
                            <p class="text-xl font-bold text-gray-800">{{ formatCurrency(doctorInfo?.consultation_fee) }}</p>
                            <p class="text-[10px] text-gray-400">{{ currencyCode }}</p>
                        </div>
                    </div>
                    <!-- Month-over-month change -->
                    <div class="space-y-2 border-t border-[#C4A265]/10 pt-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">{{ isRtl ? 'هذا الشهر' : 'This month' }}</span>
                            <span class="font-bold text-[#C4A265]">{{ formatCurrency(monthly.total_commission) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">{{ isRtl ? 'مقارنة بالشهر الماضي' : 'vs last month' }}</span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold"
                                :class="commissionChange >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                <svg v-if="commissionChange >= 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                {{ commissionChange >= 0 ? '+' : '' }}{{ commissionChange }}%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Dental Quick Stats -->
                <div v-if="dental && (dental.active_plans || dental.pending_treatments || dental.overdue_followups)"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.25s">
                    <div class="px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-slate-50/40 to-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'ملخص الأسنان' : 'Dental Quick Stats' }}</h3>
                    </div>
                    <div class="p-5 grid grid-cols-3 gap-3">
                        <div class="text-center p-2.5 rounded-xl bg-slate-50/50">
                            <p class="text-lg font-bold text-[#1B365D] tabular-nums">{{ dental.active_plans ?? 0 }}</p>
                            <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'خطط نشطة' : 'Active Plans' }}</p>
                        </div>
                        <div class="text-center p-2.5 rounded-xl bg-amber-50/50">
                            <p class="text-lg font-bold text-amber-600 tabular-nums">{{ dental.pending_treatments ?? 0 }}</p>
                            <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'معلّق' : 'Pending' }}</p>
                        </div>
                        <div class="text-center p-2.5 rounded-xl" :class="(dental.overdue_followups ?? 0) > 0 ? 'bg-red-50/50' : 'bg-gray-50/50'">
                            <p class="text-lg font-bold tabular-nums" :class="(dental.overdue_followups ?? 0) > 0 ? 'text-red-600' : 'text-gray-400'">{{ dental.overdue_followups ?? 0 }}</p>
                            <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'متأخرة' : 'Overdue' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payout Summary -->
                <div v-if="payoutSummary" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.3s">
                    <div class="px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white flex items-center justify-between">
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'المدفوعات' : 'Payouts' }}</h3>
                        <Link href="/doctor/commission" class="text-[10px] font-semibold text-[#C4A265] hover:text-[#B3914F]">{{ isRtl ? 'التفاصيل' : 'Details' }}</Link>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                {{ isRtl ? 'إجمالي المستلم' : 'Total Received' }}
                            </span>
                            <span class="font-bold text-emerald-600 tabular-nums">{{ formatCurrency(payoutSummary.total_paid) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                {{ isRtl ? 'قيد الانتظار' : 'Pending' }}
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-amber-600 tabular-nums">{{ formatCurrency(payoutSummary.pending) }}</span>
                                <span v-if="payoutSummary.pending_count > 0" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-600">{{ payoutSummary.pending_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visit Trend Chart -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.35s">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'اتجاه 7 أيام' : '7-Day Trend' }}</h3>
                        <div class="flex items-center gap-1 text-[10px] font-semibold"
                            :class="visitChange >= 0 ? 'text-emerald-600' : 'text-red-500'">
                            <svg v-if="visitChange >= 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                            {{ visitChange >= 0 ? '+' : '' }}{{ visitChange }}%
                        </div>
                    </div>
                    <div class="flex items-end gap-1.5 h-28">
                        <div v-for="(day, i) in visitTrend" :key="day.label" class="flex-1 flex flex-col items-center gap-1 group">
                            <div class="relative">
                                <!-- Tooltip -->
                                <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-[9px] font-medium px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                                    {{ day.date }}: {{ day.value }}
                                </div>
                                <span class="text-[10px] font-bold tabular-nums" :class="day.value > 0 ? 'text-gray-600' : 'text-gray-300'">{{ day.value }}</span>
                            </div>
                            <div class="w-full rounded-lg transition-all duration-700 ease-out cursor-pointer group-hover:opacity-80"
                                :class="i === visitTrend.length - 1 ? 'bg-gradient-to-t from-[#C4A265] to-[#D4B87A]' : 'bg-gradient-to-t from-gray-200 to-gray-300'"
                                :style="{
                                    height: mounted ? Math.max(6, (day.value / maxTrend) * 72) + 'px' : '6px',
                                    transitionDelay: (0.4 + i * 0.08) + 's'
                                }">
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium">{{ day.label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pending Follow-ups (Sidebar) -->
                <div v-if="pendingFollowups?.length > 0"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.38s">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'المتابعات المعلقة' : 'Pending Follow-ups' }}</h3>
                        </div>
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-[10px] font-bold text-amber-700">{{ pendingFollowups.length }}</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="followup in pendingFollowups.slice(0, 5)" :key="'fu-' + followup.id"
                            class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50/50 transition-colors">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-bold shrink-0"
                                :class="getFollowupUrgency(followup.scheduled_date) === 'overdue' ? 'bg-red-100 text-red-700' :
                                        getFollowupUrgency(followup.scheduled_date) === 'today' ? 'bg-amber-100 text-amber-700' :
                                        'bg-emerald-100 text-emerald-700'">
                                {{ (followup.patient?.full_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 truncate">{{ followup.patient?.full_name || '-' }}</p>
                                <p class="text-[10px] text-gray-400 truncate">
                                    {{ followup.scheduled_date ? new Date(followup.scheduled_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) : '-' }}
                                </p>
                            </div>
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full shrink-0"
                                :class="getFollowupUrgency(followup.scheduled_date) === 'overdue' ? 'bg-red-50 text-red-600' :
                                        getFollowupUrgency(followup.scheduled_date) === 'today' ? 'bg-amber-50 text-amber-600' :
                                        'bg-emerald-50 text-emerald-600'">
                                {{ getFollowupUrgency(followup.scheduled_date) === 'overdue' ? (isRtl ? 'متأخر' : 'Overdue') :
                                   getFollowupUrgency(followup.scheduled_date) === 'today' ? (isRtl ? 'اليوم' : 'Today') :
                                   (isRtl ? 'قادم' : 'Upcoming') }}
                            </span>
                        </div>
                    </div>
                    <div v-if="pendingFollowups.length > 5" class="px-5 py-2.5 border-t border-gray-100 text-center">
                        <Link href="/doctor/dental/followups" class="text-[10px] font-semibold text-[#C4A265] hover:text-[#B3914F]">
                            {{ isRtl ? `عرض الكل (${pendingFollowups.length})` : `View all (${pendingFollowups.length})` }}
                        </Link>
                    </div>
                </div>

                <!-- Upcoming Bookings -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.42s">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'الحجوزات القادمة' : 'Upcoming Bookings' }}</h3>
                        </div>
                        <Link href="/doctor/bookings" class="text-[10px] font-semibold text-[#C4A265] hover:text-[#B3914F]">{{ isRtl ? 'عرض الكل' : 'View all' }}</Link>
                    </div>
                    <div v-if="upcomingBookings?.length > 0" class="divide-y divide-gray-50">
                        <div v-for="booking in upcomingBookings" :key="booking.id" class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50/50 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-[10px] font-bold text-[#1B365D] shrink-0">
                                {{ new Date(booking.preferred_date).getDate() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 truncate">{{ booking.full_name }}</p>
                                <p class="text-[10px] text-gray-400">{{ booking.service?.name_en }}</p>
                            </div>
                            <span class="text-[10px] text-gray-400 shrink-0">{{ new Date(booking.preferred_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) }}</span>
                        </div>
                    </div>
                    <div v-else class="py-10 text-center">
                        <p class="text-[11px] text-gray-400">{{ isRtl ? 'لا توجد حجوزات قادمة' : 'No upcoming bookings' }}</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-5"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.45s">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">{{ isRtl ? 'إجراءات سريعة' : 'Quick Actions' }}</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <Link href="/doctor/queue" class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 hover:bg-[#C4A265]/5 hover:border-[#C4A265]/20 border border-transparent transition-all group">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-600 group-hover:text-[#C4A265] transition-colors">{{ isRtl ? 'الطابور' : 'My Queue' }}</span>
                        </Link>
                        <Link href="/doctor/patients" class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 hover:bg-[#C4A265]/5 hover:border-[#C4A265]/20 border border-transparent transition-all group">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-600 group-hover:text-[#C4A265] transition-colors">{{ isRtl ? 'المرضى' : 'Patients' }}</span>
                        </Link>
                        <Link href="/doctor/commission" class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 hover:bg-[#C4A265]/5 hover:border-[#C4A265]/20 border border-transparent transition-all group">
                            <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-600 group-hover:text-[#C4A265] transition-colors">{{ isRtl ? 'العمولة' : 'Commission' }}</span>
                        </Link>
                        <Link href="/doctor/profile" class="flex items-center gap-2.5 p-3 rounded-xl bg-gray-50 hover:bg-[#C4A265]/5 hover:border-[#C4A265]/20 border border-transparent transition-all group">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-600 group-hover:text-[#C4A265] transition-colors">{{ isRtl ? 'الإعدادات' : 'Settings' }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Performance Overview -->
        <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.5s">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'ملخص الأداء الشهري' : 'Monthly Performance' }}</h2>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'مقارنة بالشهر الماضي' : 'Compared to last month' }}</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <!-- Total Visits -->
                    <div class="relative group p-4 rounded-xl border border-gray-100 hover:border-slate-200 hover:shadow-sm transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-slate-400 to-[#1B365D] transform origin-left transition-transform duration-500" :style="{ transform: mounted ? 'scaleX(1)' : 'scaleX(0)' }"></div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الزيارات' : 'Visits' }}</p>
                        <p class="text-2xl font-bold text-gray-800 tabular-nums">{{ monthly?.total_visits || 0 }}</p>
                        <div class="flex items-center gap-1 mt-1.5">
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                                :class="visitChange >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="visitChange >= 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                {{ Math.abs(visitChange) }}%
                            </span>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div class="relative group p-4 rounded-xl border border-gray-100 hover:border-emerald-200 hover:shadow-sm transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-emerald-600 transform origin-left transition-transform duration-500" :style="{ transform: mounted ? 'scaleX(1)' : 'scaleX(0)', transitionDelay: '0.1s' }"></div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'مكتملة' : 'Completed' }}</p>
                        <p class="text-2xl font-bold text-gray-800 tabular-nums">{{ monthly?.completed_visits || 0 }}</p>
                        <div class="mt-1.5">
                            <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000" :style="{ width: mounted ? (completionRate || 0) + '%' : '0%' }"></div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 tabular-nums">{{ completionRate || 0 }}% {{ isRtl ? 'نسبة الإكمال' : 'completion' }}</p>
                        </div>
                    </div>

                    <!-- Unique Patients -->
                    <div class="relative group p-4 rounded-xl border border-gray-100 hover:border-slate-200 hover:shadow-sm transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-slate-400 to-[#1B365D] transform origin-left transition-transform duration-500" :style="{ transform: mounted ? 'scaleX(1)' : 'scaleX(0)', transitionDelay: '0.2s' }"></div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'مرضى فريدون' : 'Unique Patients' }}</p>
                        <p class="text-2xl font-bold text-gray-800 tabular-nums">{{ monthly?.unique_patients || 0 }}</p>
                        <p class="text-[10px] text-gray-400 mt-1.5">{{ isRtl ? 'من إجمالي' : 'out of' }} {{ monthly?.total_visits || 0 }} {{ isRtl ? 'زيارة' : 'visits' }}</p>
                    </div>

                    <!-- Monthly Commission -->
                    <div class="relative group p-4 rounded-xl border border-gray-100 hover:border-[#C4A265]/30 hover:shadow-sm transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#C4A265] to-[#D4B87A] transform origin-left transition-transform duration-500" :style="{ transform: mounted ? 'scaleX(1)' : 'scaleX(0)', transitionDelay: '0.3s' }"></div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'العمولة' : 'Commission' }}</p>
                        <p class="text-2xl font-bold text-[#C4A265] tabular-nums">{{ formatCurrency(monthly?.total_commission || 0) }}</p>
                        <div class="flex items-center gap-1 mt-1.5">
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                                :class="commissionChange >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="commissionChange >= 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                {{ Math.abs(commissionChange) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Risk Alerts for Today's Dental Patients -->
        <div v-if="todayMedicalAlerts && todayMedicalAlerts.length > 0"
            class="mt-6 bg-red-50 rounded-2xl border border-red-200 shadow-sm overflow-hidden"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.35s">
            <div class="px-6 py-4 border-b border-red-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-red-800">
                        {{ isRtl ? 'تنبيهات طبية - مرضى اليوم' : 'Medical Alerts - Today\'s Patients' }}
                    </h2>
                    <p class="text-[10px] text-red-600">
                        {{ isRtl
                            ? `${todayMedicalAlerts.length} مريض لديه مخاطر طبية تتطلب احتياطات خاصة`
                            : `${todayMedicalAlerts.length} patient(s) with medical risks requiring special precautions` }}
                    </p>
                </div>
            </div>
            <div class="divide-y divide-red-100">
                <div v-for="alert in todayMedicalAlerts" :key="alert.visit_id" class="px-6 py-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center text-red-700 text-xs font-bold flex-shrink-0">
                                {{ (alert.patient_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <p class="text-sm font-semibold text-red-900">{{ alert.patient_name }}</p>
                        </div>
                        <div class="flex flex-wrap gap-1.5 justify-end">
                            <span v-for="flag in alert.flags" :key="flag.key"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold whitespace-nowrap"
                                :class="{
                                    'bg-red-100 text-red-700': flag.severity === 'high',
                                    'bg-amber-100 text-amber-700': flag.severity === 'medium',
                                    'bg-slate-100 text-[#1B365D]': flag.severity === 'low',
                                }">
                                <svg v-if="flag.severity === 'high'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ isRtl ? flag.label_ar : flag.label_en }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Follow-ups (for dental doctors) -->
        <div v-if="pendingFollowups && pendingFollowups.length > 0"
            class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.42s">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'متابعات تحتاج جدولة' : 'Follow-ups Need Scheduling' }}</h2>
                        <p class="text-[10px] text-gray-400">
                            {{ isRtl ? 'مرضى بحاجة لمواعيد متابعة' : 'Patients needing follow-up appointments' }}
                            <span v-if="dental?.overdue_followups > 0" class="text-red-600 font-bold">
                                &middot; {{ dental.overdue_followups }} {{ isRtl ? 'متأخرة' : 'overdue' }}
                            </span>
                        </p>
                    </div>
                </div>
                <Link href="/doctor/dental/followups"
                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-600 hover:text-amber-700 px-3 py-1.5 rounded-lg hover:bg-amber-50 transition-all">
                    {{ isRtl ? 'عرض الكل' : 'View All' }}
                    <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="f in pendingFollowups" :key="f.id" class="px-6 py-3 hover:bg-gray-50/50 transition-colors">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                :class="f.scheduled_date && new Date(f.scheduled_date) < new Date() ? 'bg-red-100 text-red-700' : 'bg-amber-50 text-amber-700'">
                                {{ (f.patient?.full_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ f.patient?.full_name || '-' }}</p>
                                <p class="text-[10px] text-gray-400 truncate">
                                    {{ f.treatment?.treatment_type?.replace(/_/g, ' ') || '' }}
                                    <span v-if="f.treatment?.tooth_number"> &middot; #{{ f.treatment.tooth_number }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="text-right">
                                <p class="text-xs text-gray-600 font-medium">{{ f.scheduled_date ? new Date(f.scheduled_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '-' }}</p>
                                <p v-if="f.scheduled_date" class="text-[10px]"
                                    :class="new Date(f.scheduled_date) < new Date() ? 'text-red-600 font-bold' : 'text-amber-500'">
                                    {{ new Date(f.scheduled_date) < new Date() ? (isRtl ? 'متأخر' : 'Overdue') : (isRtl ? 'قادم' : 'Upcoming') }}
                                </p>
                            </div>
                            <Link :href="`/doctor/patients/${f.patient?.id}`"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-[#C4A265] hover:bg-amber-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dental Overview (only for dental doctors) -->
        <div v-if="dental" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.5s">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-slate-50/50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'نظرة عامة على الأسنان' : 'Dental Overview' }}</h2>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'ملخص علاجات وطلبات اليوم' : 'Today\'s treatments & lab orders summary' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/doctor/dental/chart-search" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#C4A265] hover:text-[#b8923a] px-3 py-1.5 rounded-lg hover:bg-[#C4A265]/10 transition-all border border-[#C4A265]/30">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
                        {{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}
                    </Link>
                    <Link href="/doctor/dental/treatments" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#1B365D] hover:text-[#1B365D] px-3 py-1.5 rounded-lg hover:bg-slate-50 transition-all">
                        {{ isRtl ? 'كل العلاجات' : 'All treatments' }}
                        <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 divide-x rtl:divide-x-reverse divide-gray-100">
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ dental.treatments_today ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'علاجات اليوم' : 'Today' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600">{{ dental.completed_today ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-amber-600">{{ dental.pending_treatments ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'معلّق' : 'Pending' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-[#1B365D]">{{ dental.active_plans ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'خطط نشطة' : 'Active Plans' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold" :class="(dental.lab_ready ?? 0) > 0 ? 'text-emerald-600' : 'text-gray-400'">{{ dental.lab_ready ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'معمل جاهز' : 'Lab Ready' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-amber-500">{{ dental.lab_pending ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'معمل معلّق' : 'Lab Pending' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ dental.month_treatments ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'الشهر' : 'Month' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-lg font-bold text-[#1B365D]">{{ formatCurrency(dental.month_revenue) }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'إيرادات' : 'Revenue' }}</p>
                </div>
            </div>
        </div>
        <!-- Pediatric Overview (only for pediatric doctors) -->
        <div v-if="pediatric" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.55s">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50/50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'نظرة عامة على طب الأطفال' : 'Pediatric Overview' }}</h2>
                        <p class="text-[10px] text-gray-400">{{ isRtl ? 'ملخص زيارات وتطعيمات ونمو الأطفال' : 'Children visits, vaccinations & growth summary' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/doctor/pediatric/dashboard" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 px-3 py-1.5 rounded-lg hover:bg-emerald-50 transition-all">
                        {{ isRtl ? 'لوحة التحكم' : 'Dashboard' }}
                        <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-x rtl:divide-x-reverse divide-gray-100">
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600">{{ pediatric.total_patients ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'مرضى' : 'Patients' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ pediatric.visits_today ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'زيارات اليوم' : 'Today' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-amber-600">{{ pediatric.vaccinations_due ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'تطعيمات مستحقة' : 'Vacc. Due' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold" :class="(pediatric.growth_alerts ?? 0) > 0 ? 'text-red-600' : 'text-gray-400'">{{ pediatric.growth_alerts ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'تنبيهات نمو' : 'Growth Alerts' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ pediatric.visits_this_month ?? 0 }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'الشهر' : 'Month' }}</p>
                </div>
                <div class="px-3 py-4 text-center">
                    <p class="text-lg font-bold text-emerald-600">{{ formatCurrency(pediatric.revenue_this_month ?? 0) }}</p>
                    <p class="text-[10px] font-medium text-gray-500 mt-0.5">{{ isRtl ? 'إيرادات' : 'Revenue' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
</style>
