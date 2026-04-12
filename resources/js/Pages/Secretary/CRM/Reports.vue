<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    funnel: Object,
    sourcePerformance: Array,
    activityTrend: Array,
    avgResponseHours: Number,
    topLeads: Array,
    comparison: Object,
    period: String,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

/* ---------- Period filter ---------- */
const selectedPeriod = ref(props.period || '30');
const showCustomRange = ref(false);
const customFrom = ref('');
const customTo = ref('');

function changePeriod() {
    if (selectedPeriod.value === 'custom') {
        showCustomRange.value = true;
        return;
    }
    showCustomRange.value = false;
    router.get('/secretary/crm/reports', { period: selectedPeriod.value }, { preserveState: true, replace: true });
}

function applyCustomRange() {
    if (!customFrom.value || !customTo.value) return;
    router.get('/secretary/crm/reports', {
        period: 'custom',
        date_from: customFrom.value,
        date_to: customTo.value,
    }, { preserveState: true, replace: true });
}

/* ---------- Comparison toggle ---------- */
const showComparison = ref(true);

const periodLabels = {
    '7': { en: 'Last 7 Days', ar: 'آخر 7 أيام' },
    '30': { en: 'Last 30 Days', ar: 'آخر 30 يوم' },
    '90': { en: 'Last 90 Days', ar: 'آخر 90 يوم' },
    'year': { en: 'This Year', ar: 'هذه السنة' },
    'custom': { en: 'Custom Range', ar: 'فترة مخصصة' },
};

/* ---------- Funnel ---------- */
const funnelConfig = {
    new: { en: 'New Leads', ar: 'عملاء جدد', color: '#3b82f6' },
    contacted: { en: 'Contacted', ar: 'تم التواصل', color: '#06b6d4' },
    qualified: { en: 'Qualified', ar: 'مؤهل', color: '#8b5cf6' },
    appointment_booked: { en: 'Booked', ar: 'تم الحجز', color: '#f59e0b' },
    consultation_done: { en: 'Consulted', ar: 'تم الاستشارة', color: '#0d9488' },
    negotiation: { en: 'Negotiation', ar: 'تفاوض', color: '#f97316' },
    converted: { en: 'Converted', ar: 'تم التحويل', color: '#10b981' },
};

const funnelMax = computed(() => {
    const vals = Object.values(props.funnel || {});
    return Math.max(...vals, 1);
});

/* ---------- Activity Trend ---------- */
const trendMax = computed(() => {
    return Math.max(...(props.activityTrend || []).map(d => d.activities + d.follow_ups), 1);
});

// Show every Nth label based on data length
const labelStep = computed(() => {
    const len = props.activityTrend?.length || 0;
    if (len <= 7) return 1;
    if (len <= 14) return 2;
    if (len <= 30) return 5;
    return 10;
});

/* ---------- Comparison helpers ---------- */
function getChange(current, previous) {
    if (previous === 0) return current > 0 ? 100 : 0;
    return Math.round(((current - previous) / previous) * 100);
}

const statusLabels = {
    new: { en: 'New', ar: 'جديد' },
    contacted: { en: 'Contacted', ar: 'تم التواصل' },
    qualified: { en: 'Qualified', ar: 'مؤهل' },
    appointment_booked: { en: 'Booked', ar: 'تم الحجز' },
    consultation_done: { en: 'Consulted', ar: 'تم الاستشارة' },
    negotiation: { en: 'Negotiation', ar: 'تفاوض' },
};

/* ---------- Export CSV ---------- */
function exportCSV() {
    // Build CSV from funnel + source + top leads
    let csv = 'Report Type,Item,Value\n';

    // Funnel data
    Object.entries(props.funnel || {}).forEach(([status, count]) => {
        const label = funnelConfig[status]?.en || status;
        csv += `Funnel,${label},${count}\n`;
    });

    // Source performance
    (props.sourcePerformance || []).forEach(s => {
        csv += `Source,${s.name_en},${s.total} leads / ${s.converted} converted / ${s.conversion_rate}% rate\n`;
    });

    // Top leads
    (props.topLeads || []).forEach((lead, i) => {
        csv += `Top Lead #${i+1},${lead.full_name},Score: ${lead.score}\n`;
    });

    // Comparison
    if (props.comparison) {
        Object.entries(props.comparison).forEach(([key, val]) => {
            csv += `Comparison,${key},Current: ${val.current} / Previous: ${val.previous}\n`;
        });
    }

    csv += `\nAvg Response Time,,${props.avgResponseHours}h\n`;
    csv += `Period,,${selectedPeriod.value} days\n`;

    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `crm-report-${selectedPeriod.value}d-${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    URL.revokeObjectURL(url);
}

const priorityLabels = {
    1: { en: 'Hot', ar: 'ساخن', color: '#dc2626' },
    2: { en: 'Warm', ar: 'دافئ', color: '#d97706' },
    3: { en: 'Cold', ar: 'بارد', color: '#2563eb' },
};
</script>

<template>
<SecretaryLayout :title="isRtl ? 'تقارير CRM' : 'CRM Reports'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/20 to-slate-50 p-4 md:p-6" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- Header -->
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 p-6 md:p-8 mb-6 shadow-xl transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white rounded-full"></div>
        </div>
        <div class="relative flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ isRtl ? 'تقارير CRM' : 'CRM Reports' }}</h1>
                    <p class="text-teal-100 mt-1 text-sm">{{ isRtl ? 'تحليلات متقدمة لأداء المبيعات' : 'Advanced sales performance analytics' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Period Selector -->
                <select v-model="selectedPeriod" @change="changePeriod"
                        class="bg-white/20 backdrop-blur text-white border border-white/30 rounded-xl py-2.5 px-4 text-sm focus:ring-2 focus:ring-white/50 [&>option]:text-slate-800">
                    <option v-for="(label, key) in periodLabels" :key="key" :value="key">{{ isRtl ? label.ar : label.en }}</option>
                </select>
                <!-- Comparison toggle -->
                <button @click="showComparison = !showComparison"
                    :class="['inline-flex items-center gap-1.5 backdrop-blur border rounded-xl py-2.5 px-4 text-sm font-medium transition-all duration-200',
                        showComparison ? 'bg-white/30 text-white border-white/40' : 'bg-white/10 text-white/70 border-white/20 hover:bg-white/20']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    {{ isRtl ? 'مقارنة' : 'Compare' }}
                </button>
                <!-- Export CSV -->
                <button @click="exportCSV"
                        class="inline-flex items-center gap-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white border border-white/30 rounded-xl py-2.5 px-4 text-sm font-medium transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ isRtl ? 'تصدير' : 'Export' }}
                </button>
            </div>
        </div>
        <!-- Custom date range -->
        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showCustomRange" class="mt-4 flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-teal-100">{{ isRtl ? 'من' : 'From' }}</label>
                    <input v-model="customFrom" type="date" class="bg-white/20 backdrop-blur text-white border border-white/30 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-white/50 [color-scheme:dark]">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-teal-100">{{ isRtl ? 'إلى' : 'To' }}</label>
                    <input v-model="customTo" type="date" class="bg-white/20 backdrop-blur text-white border border-white/30 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-white/50 [color-scheme:dark]">
                </div>
                <button @click="applyCustomRange" :disabled="!customFrom || !customTo"
                    :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-all duration-200',
                        customFrom && customTo ? 'bg-white text-teal-700 hover:bg-teal-50 shadow-sm' : 'bg-white/20 text-white/50 cursor-not-allowed']">
                    {{ isRtl ? 'تطبيق' : 'Apply' }}
                </button>
                <button @click="showCustomRange = false; selectedPeriod = '30'; changePeriod()" class="text-xs text-teal-200 hover:text-white transition-colors underline">
                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                </button>
            </div>
        </Transition>
    </div>

    <!-- Period Comparison Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div v-for="(item, key) in [
            { key: 'new_leads', icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z', labelEn: 'New Leads', labelAr: 'عملاء جدد', bg: 'bg-blue-100', iconColor: 'text-blue-600' },
            { key: 'activities', icon: 'M13 10V3L4 14h7v7l9-11h-7z', labelEn: 'Activities', labelAr: 'النشاطات', bg: 'bg-teal-100', iconColor: 'text-teal-600' },
            { key: 'converted', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', labelEn: 'Converted', labelAr: 'تم تحويلهم', bg: 'bg-emerald-100', iconColor: 'text-emerald-600' },
        ]" :key="item.key"
             :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: (100 + key * 50) + 'ms' }">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="[item.bg]">
                        <svg class="w-5 h-5" :class="[item.iconColor]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-800">{{ comparison?.[item.key]?.current ?? 0 }}</p>
                        <p class="text-xs text-slate-500">{{ isRtl ? item.labelAr : item.labelEn }}</p>
                    </div>
                </div>
                <!-- Change indicator -->
                <div v-if="showComparison && comparison?.[item.key]" class="text-right">
                    <div :class="['flex items-center gap-1 text-sm font-semibold', getChange(comparison[item.key].current, comparison[item.key].previous) >= 0 ? 'text-emerald-600' : 'text-red-500']">
                        <svg v-if="getChange(comparison[item.key].current, comparison[item.key].previous) >= 0" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                        {{ Math.abs(getChange(comparison[item.key].current, comparison[item.key].previous)) }}%
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">{{ isRtl ? 'مقارنة بالفترة السابقة' : 'vs previous period' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversion Funnel + Response Time -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Funnel -->
        <div :class="['lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '250ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-5 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                {{ isRtl ? 'قمع التحويل' : 'Conversion Funnel' }}
            </h3>
            <div class="space-y-3">
                <div v-for="(count, status) in funnel" :key="status" class="group">
                    <div class="flex items-center gap-3 mb-1">
                        <span class="text-xs font-medium text-slate-600 w-24 truncate">{{ isRtl ? funnelConfig[status]?.ar : funnelConfig[status]?.en }}</span>
                        <div class="flex-1 h-8 bg-slate-50 rounded-lg overflow-hidden relative">
                            <div class="h-full rounded-lg transition-all duration-1000 ease-out flex items-center"
                                 :style="{ width: mounted ? Math.max((count / funnelMax) * 100, 2) + '%' : '0%', background: funnelConfig[status]?.color }">
                            </div>
                        </div>
                        <span class="text-sm font-bold text-slate-800 w-10 text-center">{{ count }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Response Time + Key Metrics -->
        <div class="space-y-4">
            <!-- Avg Response Time -->
            <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
                 :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '300ms' }">
                <div class="w-16 h-16 mx-auto mb-3 relative">
                    <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" stroke-width="6"/>
                        <circle cx="50" cy="50" r="42" fill="none" :stroke="avgResponseHours <= 2 ? '#10b981' : avgResponseHours <= 12 ? '#f59e0b' : '#ef4444'" stroke-width="6" stroke-linecap="round"
                                :stroke-dasharray="(Math.min(avgResponseHours / 48, 1) * 264) + ' 264'"
                                class="transition-all duration-1000"/>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-lg font-bold"
                          :class="avgResponseHours <= 2 ? 'text-emerald-600' : avgResponseHours <= 12 ? 'text-amber-600' : 'text-red-600'">
                        {{ avgResponseHours }}h
                    </span>
                </div>
                <p class="text-sm font-semibold text-slate-700">{{ isRtl ? 'متوسط وقت الاستجابة' : 'Avg Response Time' }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ isRtl ? 'من الإنشاء للتواصل الأول' : 'Creation to first contact' }}</p>
            </div>

            <!-- Quick Stats -->
            <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
                 :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '350ms' }">
                <h4 class="text-sm font-semibold text-slate-800 mb-3">{{ isRtl ? 'إحصائيات سريعة' : 'Quick Stats' }}</h4>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">{{ isRtl ? 'إجمالي في القمع' : 'Total in Funnel' }}</span>
                        <span class="text-sm font-bold text-slate-800">{{ funnel?.new || 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">{{ isRtl ? 'معدل التحويل' : 'Conversion Rate' }}</span>
                        <span class="text-sm font-bold text-emerald-600">{{ funnel?.new > 0 ? Math.round(((funnel?.converted || 0) / funnel.new) * 100) : 0 }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-500">{{ isRtl ? 'في مرحلة التفاوض' : 'In Negotiation' }}</span>
                        <span class="text-sm font-bold text-orange-600">{{ funnel?.negotiation || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Trend Chart -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '400ms' }">
        <h3 class="text-sm font-semibold text-slate-800 mb-1 flex items-center gap-2">
            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            {{ isRtl ? 'اتجاه النشاط' : 'Activity Trend' }}
        </h3>
        <div class="flex items-center gap-4 mb-4 text-xs text-slate-500">
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-full bg-teal-500"></div>
                {{ isRtl ? 'نشاطات' : 'Activities' }}
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                {{ isRtl ? 'متابعات مكتملة' : 'Follow-ups Done' }}
            </div>
        </div>
        <!-- SVG Chart -->
        <div class="relative h-48 overflow-x-auto">
            <svg :width="Math.max((activityTrend?.length || 7) * 28, 400)" height="192" class="w-full min-w-[400px]" preserveAspectRatio="none">
                <!-- Grid lines -->
                <line v-for="i in 4" :key="i" x1="0" :x2="Math.max((activityTrend?.length || 7) * 28, 400)" :y1="(i-1) * 42 + 10" :y2="(i-1) * 42 + 10" stroke="#f1f5f9" stroke-width="1"/>

                <!-- Activity bars -->
                <g v-for="(day, idx) in activityTrend" :key="'bar-' + idx">
                    <!-- Activity bar -->
                    <rect :x="idx * 28 + 4" :y="mounted ? 170 - Math.max((day.activities / trendMax) * 150, 2) : 170"
                          width="10" :height="mounted ? Math.max((day.activities / trendMax) * 150, 2) : 0"
                          rx="3" fill="#0d9488" opacity="0.85"
                          class="transition-all duration-1000 ease-out"/>
                    <!-- Follow-up bar -->
                    <rect :x="idx * 28 + 16" :y="mounted ? 170 - Math.max((day.follow_ups / trendMax) * 150, 2) : 170"
                          width="10" :height="mounted ? Math.max((day.follow_ups / trendMax) * 150, 2) : 0"
                          rx="3" fill="#34d399" opacity="0.85"
                          class="transition-all duration-1000 ease-out"/>
                    <!-- Labels -->
                    <text v-if="idx % labelStep === 0" :x="idx * 28 + 14" y="188" text-anchor="middle" class="fill-slate-400" style="font-size: 9px">{{ isRtl ? day.label_ar : day.label }}</text>
                </g>
            </svg>
        </div>
    </div>

    <!-- Source Performance + Top Leads -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Source Performance -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '450ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                {{ isRtl ? 'أداء المصادر' : 'Source Performance' }}
            </h3>
            <div v-if="sourcePerformance && sourcePerformance.length" class="space-y-3">
                <div v-for="source in sourcePerformance" :key="source.name_en"
                     class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white"
                                 :style="{ background: source.color || '#0d9488' }">
                                {{ (source.name_en || '?')[0].toUpperCase() }}
                            </div>
                            <span class="text-sm font-medium text-slate-700">{{ isRtl ? source.name_ar : source.name_en }}</span>
                        </div>
                        <span class="text-xs text-slate-500">{{ source.total }} {{ isRtl ? 'عميل' : 'leads' }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <div class="flex items-center gap-1 text-emerald-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ source.converted }} {{ isRtl ? 'محول' : 'converted' }}
                        </div>
                        <div class="flex items-center gap-1 text-blue-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            {{ source.avg_score }} {{ isRtl ? 'نقاط' : 'score' }}
                        </div>
                        <div class="flex-1"></div>
                        <span class="font-semibold" :class="source.conversion_rate >= 30 ? 'text-emerald-600' : source.conversion_rate >= 15 ? 'text-amber-600' : 'text-slate-500'">
                            {{ source.conversion_rate }}% {{ isRtl ? 'تحويل' : 'conv.' }}
                        </span>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-sm text-slate-400">
                {{ isRtl ? 'لا توجد بيانات' : 'No data' }}
            </div>
        </div>

        <!-- Top Leads by Score -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '500ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                {{ isRtl ? 'أفضل العملاء بالنقاط' : 'Top Leads by Score' }}
            </h3>
            <div v-if="topLeads && topLeads.length" class="space-y-2">
                <Link v-for="(lead, idx) in topLeads" :key="lead.id"
                      :href="'/secretary/crm/leads/' + lead.id"
                      class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100 group">
                    <!-- Rank -->
                    <div :class="['w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0',
                                  idx === 0 ? 'bg-amber-100 text-amber-700' : idx === 1 ? 'bg-slate-200 text-slate-600' : idx === 2 ? 'bg-orange-100 text-orange-600' : 'bg-slate-100 text-slate-500']">
                        {{ idx + 1 }}
                    </div>
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate group-hover:text-teal-700 transition-colors">{{ lead.full_name }}</p>
                        <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                            <span class="truncate">{{ lead.phone }}</span>
                            <span class="px-1.5 py-0.5 rounded text-xs font-medium"
                                  :style="{ color: priorityLabels[lead.priority]?.color }">
                                {{ isRtl ? priorityLabels[lead.priority]?.ar : priorityLabels[lead.priority]?.en }}
                            </span>
                        </div>
                    </div>
                    <!-- Score -->
                    <div class="text-center flex-shrink-0">
                        <p class="text-lg font-bold text-teal-700">{{ lead.score }}</p>
                        <p class="text-xs text-slate-400">{{ isRtl ? 'نقاط' : 'pts' }}</p>
                    </div>
                </Link>
            </div>
            <div v-else class="text-center py-8 text-sm text-slate-400">
                {{ isRtl ? 'لا توجد بيانات' : 'No data' }}
            </div>
        </div>
    </div>

</div>
</SecretaryLayout>
</template>
