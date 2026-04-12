<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    monthlyStats: Object,
    weeklyStats: Object,
    lastWeekStats: Object,
    dailyActivity: Array,
    statusDistribution: Object,
    conversionRate: Number,
    avgResponseTime: Number,
    streak: Number,
    overdueFollowUps: Number,
    todayFollowUps: Number,
    sourcePerformance: Array,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const maxDailyCount = computed(() => {
    return Math.max(...(props.dailyActivity || []).map(d => d.count), 1);
});

const statusLabels = {
    new: { en: 'New', ar: 'جديد', color: '#3b82f6' },
    contacted: { en: 'Contacted', ar: 'تم التواصل', color: '#6366f1' },
    qualified: { en: 'Qualified', ar: 'مؤهل', color: '#a855f7' },
    appointment_booked: { en: 'Booked', ar: 'تم الحجز', color: '#f59e0b' },
    consultation_done: { en: 'Consulted', ar: 'تم الاستشارة', color: '#14b8a6' },
    negotiation: { en: 'Negotiation', ar: 'تفاوض', color: '#f97316' },
};

const totalLeads = computed(() => {
    return Object.values(props.statusDistribution || {}).reduce((a, b) => a + b, 0);
});

function weekChange(key) {
    const cur = props.weeklyStats?.[key] || 0;
    const prev = props.lastWeekStats?.[key] || 0;
    if (prev === 0) return cur > 0 ? 100 : 0;
    return Math.round(((cur - prev) / prev) * 100);
}

// Response time color
const responseTimeColor = computed(() => {
    const t = props.avgResponseTime;
    if (t === null) return '#9ca3af';
    if (t <= 2) return '#10b981';
    if (t <= 6) return '#f59e0b';
    return '#ef4444';
});

const responseTimeAngle = computed(() => {
    const t = props.avgResponseTime || 0;
    const max = 24;
    return Math.min((t / max) * 264, 264);
});

// Streak message
const streakMessage = computed(() => {
    const s = props.streak || 0;
    if (s === 0) return isRtl.value ? 'ابدأ سلسلة جديدة اليوم' : 'Start a streak today';
    if (s < 3) return isRtl.value ? 'بداية جيدة' : 'Good start';
    if (s < 7) return isRtl.value ? 'أداء رائع' : 'Great work';
    if (s < 14) return isRtl.value ? 'مذهل' : 'Amazing';
    return isRtl.value ? 'أسطوري' : 'Legendary';
});
</script>

<template>
<SecretaryLayout :title="isRtl ? 'تقارير الأداء' : 'Performance'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/20 to-slate-50 p-4 md:p-6" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- Header -->
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 p-6 md:p-8 mb-6 shadow-xl transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white rounded-full"></div>
        </div>
        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ isRtl ? 'تقارير الأداء' : 'My Performance' }}</h1>
                    <p class="text-teal-100 mt-1 text-sm">{{ isRtl ? 'متابعة إنتاجيتك ونتائج عملك' : 'Track your productivity and results' }}</p>
                </div>
            </div>

            <!-- Streak + Today's Tasks -->
            <div class="flex items-center gap-3">
                <!-- Streak -->
                <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/20 text-center">
                    <div class="flex items-center gap-1.5 justify-center">
                        <svg class="w-5 h-5 text-amber-300" fill="currentColor" viewBox="0 0 24 24"><path d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                        <span class="text-2xl font-bold text-white">{{ streak || 0 }}</span>
                    </div>
                    <span class="text-teal-100 text-[10px] block mt-0.5">{{ isRtl ? 'يوم متتالي' : 'Day Streak' }}</span>
                </div>

                <!-- Today -->
                <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/20 text-center">
                    <span class="text-2xl font-bold text-white">{{ todayFollowUps || 0 }}</span>
                    <span class="text-teal-100 text-[10px] block mt-0.5">{{ isRtl ? 'متابعات اليوم' : 'Today Tasks' }}</span>
                </div>

                <!-- Overdue -->
                <div v-if="overdueFollowUps > 0" class="bg-red-500/30 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-red-300/30 text-center">
                    <span class="text-2xl font-bold text-white">{{ overdueFollowUps }}</span>
                    <span class="text-red-100 text-[10px] block mt-0.5">{{ isRtl ? 'متأخرة' : 'Overdue' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TOP ROW: Conversion Rate + Response Time + Week Comparison ═══ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Conversion Rate Gauge -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }">
            <div class="relative w-20 h-20 mx-auto mb-3">
                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#0d9488" stroke-width="8" stroke-linecap="round"
                            :stroke-dasharray="(mounted ? (conversionRate / 100) * 264 : 0) + ' 264'"
                            class="transition-all duration-1000 ease-out"/>
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-lg font-bold text-teal-700">{{ conversionRate }}%</span>
            </div>
            <p class="text-xs font-medium text-slate-500">{{ isRtl ? 'نسبة التحويل' : 'Conversion Rate' }}</p>
        </div>

        <!-- Response Time Gauge -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '150ms' }">
            <div class="relative w-20 h-20 mx-auto mb-3">
                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                    <circle cx="50" cy="50" r="42" fill="none" :stroke="responseTimeColor" stroke-width="8" stroke-linecap="round"
                            :stroke-dasharray="(mounted ? responseTimeAngle : 0) + ' 264'"
                            class="transition-all duration-1000 ease-out"/>
                </svg>
                <span class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-lg font-bold" :style="{ color: responseTimeColor }">{{ avgResponseTime ?? '-' }}</span>
                    <span class="text-[9px] text-slate-400">{{ isRtl ? 'ساعة' : 'hrs' }}</span>
                </span>
            </div>
            <p class="text-xs font-medium text-slate-500">{{ isRtl ? 'وقت الاستجابة' : 'Avg Response' }}</p>
        </div>

        <!-- Week Activities (with comparison) -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '200ms' }">
            <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ weeklyStats?.total_activities || 0 }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ isRtl ? 'نشاط هذا الأسبوع' : 'This week activities' }}</p>
            <div class="flex items-center gap-1 mt-1.5">
                <svg v-if="weekChange('total_activities') > 0" class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                <svg v-else-if="weekChange('total_activities') < 0" class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                <span class="text-[11px] font-semibold" :class="weekChange('total_activities') >= 0 ? 'text-emerald-600' : 'text-red-500'">
                    {{ weekChange('total_activities') >= 0 ? '+' : '' }}{{ weekChange('total_activities') }}%
                </span>
                <span class="text-[10px] text-slate-400">{{ isRtl ? 'vs الأسبوع الماضي' : 'vs last week' }}</span>
            </div>
        </div>

        <!-- Week Conversions (with comparison) -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '250ms' }">
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ weeklyStats?.leads_converted || 0 }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ isRtl ? 'تحويلات الأسبوع' : 'This week converts' }}</p>
            <div class="flex items-center gap-1 mt-1.5">
                <svg v-if="weekChange('leads_converted') > 0" class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                <svg v-else-if="weekChange('leads_converted') < 0" class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                <span class="text-[11px] font-semibold" :class="weekChange('leads_converted') >= 0 ? 'text-emerald-600' : 'text-red-500'">
                    {{ weekChange('leads_converted') >= 0 ? '+' : '' }}{{ weekChange('leads_converted') }}%
                </span>
                <span class="text-[10px] text-slate-400">{{ isRtl ? 'vs الأسبوع الماضي' : 'vs last week' }}</span>
            </div>
        </div>
    </div>

    <!-- ═══ MIDDLE ROW: Activity Chart + Status Distribution ═══ -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Activity Chart (14 days) -->
        <div :class="['lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '300ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                {{ isRtl ? 'النشاط اليومي (آخر 14 يوم)' : 'Daily Activity (Last 14 Days)' }}
            </h3>
            <div class="flex items-end gap-1.5 h-40">
                <div v-for="(day, idx) in dailyActivity" :key="day.date" class="flex-1 flex flex-col items-center gap-1 group">
                    <span class="text-[10px] font-semibold text-teal-700 opacity-0 group-hover:opacity-100 transition-opacity">{{ day.count }}</span>
                    <div class="w-full rounded-t-md relative overflow-hidden transition-all duration-300 hover:opacity-80"
                         :style="{ height: Math.max((day.count / maxDailyCount) * 100, 3) + '%', minHeight: '3px' }">
                        <div class="absolute inset-0 rounded-t-md transition-all duration-1000"
                             :style="{ background: day.count > 0 ? 'linear-gradient(to top, #0d9488, #14b8a6)' : '#e5e7eb', height: mounted ? '100%' : '0%' }"></div>
                    </div>
                    <span class="text-[9px] text-slate-400 leading-none">{{ day.day_num }}</span>
                </div>
            </div>
            <!-- Streak indicator -->
            <div v-if="streak > 0" class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                <span class="text-sm font-semibold text-slate-700">{{ streak }} {{ isRtl ? 'يوم متتالي' : 'day streak' }}</span>
                <span class="text-xs text-slate-400">- {{ streakMessage }}</span>
            </div>
        </div>

        <!-- Status Distribution -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '350ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                {{ isRtl ? 'توزيع الحالات' : 'Status Distribution' }}
            </h3>
            <div class="space-y-3">
                <div v-for="(count, status) in statusDistribution" :key="status" class="group">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: statusLabels[status]?.color || '#9ca3af' }"></div>
                            <span class="text-xs text-slate-600">{{ isRtl ? (statusLabels[status]?.ar || status) : (statusLabels[status]?.en || status) }}</span>
                        </div>
                        <span class="text-xs font-bold text-slate-800">{{ count }}</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-1000"
                             :style="{ backgroundColor: statusLabels[status]?.color || '#9ca3af', width: mounted ? ((count / (totalLeads || 1)) * 100) + '%' : '0%' }"></div>
                    </div>
                </div>
                <div v-if="Object.keys(statusDistribution || {}).length === 0" class="text-center py-4 text-sm text-slate-400">
                    {{ isRtl ? 'لا توجد بيانات' : 'No data available' }}
                </div>
                <!-- Total -->
                <div v-if="totalLeads > 0" class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-medium text-slate-500">{{ isRtl ? 'الإجمالي' : 'Total Active' }}</span>
                    <span class="text-sm font-bold text-teal-700">{{ totalLeads }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ COMMUNICATION BREAKDOWN ═══ -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '400ms' }">
        <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            {{ isRtl ? 'ملخص التواصل هذا الشهر' : 'Communication Summary This Month' }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div v-for="item in [
                { key: 'calls', label: { en: 'Calls', ar: 'مكالمات' }, gradient: 'from-green-500 to-emerald-500', bg: 'bg-green-50', text: 'text-green-700', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
                { key: 'whatsapp', label: { en: 'WhatsApp', ar: 'واتساب' }, gradient: 'from-emerald-500 to-teal-500', bg: 'bg-emerald-50', text: 'text-emerald-700', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
                { key: 'emails', label: { en: 'Emails', ar: 'بريد' }, gradient: 'from-blue-500 to-indigo-500', bg: 'bg-blue-50', text: 'text-blue-700', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
                { key: 'meetings', label: { en: 'Meetings', ar: 'اجتماعات' }, gradient: 'from-amber-500 to-orange-500', bg: 'bg-amber-50', text: 'text-amber-700', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
                { key: 'follow_ups_completed', label: { en: 'Follow-ups', ar: 'متابعات' }, gradient: 'from-teal-500 to-cyan-500', bg: 'bg-teal-50', text: 'text-teal-700', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
            ]" :key="item.key"
                class="rounded-xl p-4 text-center border border-slate-100 hover:shadow-md transition-all duration-200"
                :class="item.bg">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-2 bg-white shadow-sm">
                    <svg class="w-5 h-5" :class="item.text" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"/></svg>
                </div>
                <p class="text-xl font-bold text-slate-800">{{ monthlyStats?.[item.key] || 0 }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ isRtl ? item.label.ar : item.label.en }}</p>
            </div>
        </div>
    </div>

    <!-- ═══ BOTTOM ROW: Source Performance + Follow-up + Monthly Stats ═══ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Source Performance -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '450ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                {{ isRtl ? 'أداء المصادر' : 'Source Performance' }}
            </h3>
            <div v-if="sourcePerformance && sourcePerformance.length" class="space-y-3">
                <div v-for="(src, idx) in sourcePerformance" :key="idx"
                     class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm"
                         :style="{ backgroundColor: src.color || '#0d9488' }">
                        {{ idx + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-700 truncate">{{ isRtl ? src.name_ar : src.name_en }}</p>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-[11px] text-slate-400">{{ src.total }} {{ isRtl ? 'عميل' : 'leads' }}</span>
                            <span class="text-[11px] text-emerald-600 font-medium">{{ src.converted }} {{ isRtl ? 'محوّل' : 'converted' }}</span>
                        </div>
                    </div>
                    <div class="text-center flex-shrink-0">
                        <div class="relative w-12 h-12">
                            <svg viewBox="0 0 36 36" class="w-12 h-12 -rotate-90">
                                <circle cx="18" cy="18" r="14" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                                <circle cx="18" cy="18" r="14" fill="none" :stroke="src.color || '#0d9488'" stroke-width="3" stroke-linecap="round"
                                        :stroke-dasharray="(mounted ? (src.rate / 100) * 88 : 0) + ' 88'"
                                        class="transition-all duration-1000 ease-out"/>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold" :style="{ color: src.color || '#0d9488' }">
                                {{ src.rate }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-sm text-slate-400">
                {{ isRtl ? 'لا توجد بيانات مصادر' : 'No source data available' }}
            </div>
        </div>

        <!-- Follow-up & Monthly Performance -->
        <div class="space-y-6">
            <!-- Follow-up Stats -->
            <div :class="['grid grid-cols-3 gap-3 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
                 :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '500ms' }">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 text-center">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">{{ monthlyStats?.follow_ups_completed || 0 }}</p>
                    <p class="text-[10px] text-slate-500 mt-1">{{ isRtl ? 'متابعات مكتملة' : 'Completed' }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 text-center">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">{{ monthlyStats?.follow_ups_missed || 0 }}</p>
                    <p class="text-[10px] text-slate-500 mt-1">{{ isRtl ? 'فائتة' : 'Missed' }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 text-center">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">{{ monthlyStats?.leads_lost || 0 }}</p>
                    <p class="text-[10px] text-slate-500 mt-1">{{ isRtl ? 'خسارة' : 'Lost' }}</p>
                </div>
            </div>

            <!-- Monthly Leads Summary -->
            <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
                 :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '550ms' }">
                <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ isRtl ? 'ملخص الشهر' : 'Monthly Summary' }}
                </h3>
                <div class="space-y-3">
                    <div v-for="item in [
                        { label: { en: 'New leads added', ar: 'عملاء جدد' }, value: monthlyStats?.leads_created || 0, color: 'text-blue-600' },
                        { label: { en: 'Leads converted', ar: 'تم تحويلهم' }, value: monthlyStats?.leads_converted || 0, color: 'text-emerald-600' },
                        { label: { en: 'Total activities', ar: 'إجمالي النشاطات' }, value: monthlyStats?.total_activities || 0, color: 'text-teal-600' },
                    ]" :key="item.label.en"
                        class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                        <span class="text-sm text-slate-600">{{ isRtl ? item.label.ar : item.label.en }}</span>
                        <span class="text-lg font-bold" :class="item.color">{{ item.value }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</SecretaryLayout>
</template>
