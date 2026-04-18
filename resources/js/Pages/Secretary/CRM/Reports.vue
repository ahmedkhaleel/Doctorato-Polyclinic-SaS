<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
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

/* ---------- Best source highlight ---------- */
const bestSource = computed(() => {
    if (!props.sourcePerformance?.length) return null;
    // Best = highest conversion rate with at least 2 leads
    const qualified = props.sourcePerformance.filter(s => s.total >= 2);
    if (!qualified.length) return props.sourcePerformance[0];
    return qualified.reduce((best, s) => s.conversion_rate > best.conversion_rate ? s : best, qualified[0]);
});

/* ---------- Export CSV ---------- */
function exportCSV() {
    let csv = '';

    // Section 1: Funnel Summary
    csv += 'FUNNEL SUMMARY\n';
    csv += 'Stage,Count\n';
    Object.entries(props.funnel || {}).forEach(([status, count]) => {
        const label = funnelConfig[status]?.en || status;
        csv += `${label},${count}\n`;
    });

    // Section 2: Source Performance
    csv += '\nSOURCE PERFORMANCE\n';
    csv += 'Source,Total Leads,Converted,Conversion Rate\n';
    (props.sourcePerformance || []).forEach(s => {
        csv += `"${(s.name_en || '').replace(/"/g, '""')}",${s.total},${s.converted},${s.conversion_rate}%\n`;
    });

    // Section 3: Daily Activity Trend
    if (props.activityTrend?.length) {
        csv += '\nDAILY ACTIVITY TREND\n';
        csv += 'Date,Activities,Follow-ups\n';
        props.activityTrend.forEach(d => {
            csv += `${d.date},${d.activities},${d.follow_ups}\n`;
        });
    }

    // Section 4: Top Leads
    csv += '\nTOP LEADS\n';
    csv += 'Rank,Name,Score,Status,Phone\n';
    (props.topLeads || []).forEach((lead, i) => {
        csv += `${i + 1},"${(lead.full_name || '').replace(/"/g, '""')}",${lead.score},${lead.status || ''},"${lead.phone || ''}"\n`;
    });

    // Section 5: Period Comparison
    if (props.comparison) {
        csv += '\nPERIOD COMPARISON\n';
        csv += 'Metric,Current Period,Previous Period,Change\n';
        Object.entries(props.comparison).forEach(([key, val]) => {
            const change = getChange(val.current, val.previous);
            csv += `${key},${val.current},${val.previous},${change > 0 ? '+' : ''}${change}%\n`;
        });
    }

    // Section 6: Summary metrics
    csv += '\nKEY METRICS\n';
    csv += `Avg Response Time,${props.avgResponseHours}h\n`;
    csv += `Report Period,${periodLabels[selectedPeriod.value]?.en || selectedPeriod.value}\n`;
    csv += `Generated,${new Date().toISOString().split('T')[0]}\n`;

    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `crm-report-${selectedPeriod.value}d-${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    URL.revokeObjectURL(url);
}

const priorityLabels = {
    1: { en: 'Hot', ar: '\u0633\u0627\u062E\u0646', color: '#dc2626' },
    2: { en: 'Warm', ar: '\u062F\u0627\u0641\u0626', color: '#d97706' },
    3: { en: 'Cold', ar: '\u0628\u0627\u0631\u062F', color: '#2563eb' },
};

/* ---------- Channel performance breakdown ---------- */
const channelBreakdown = computed(() => {
    const trend = props.activityTrend || [];
    const channels = [
        { key: 'call', en: 'Calls', ar: '\u0645\u0643\u0627\u0644\u0645\u0627\u062A', color: '#10b981', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
        { key: 'whatsapp', en: 'WhatsApp', ar: '\u0648\u0627\u062A\u0633\u0627\u0628', color: '#25d366', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
        { key: 'email', en: 'Email', ar: '\u0628\u0631\u064A\u062F', color: '#3b82f6', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
        { key: 'sms', en: 'SMS', ar: '\u0631\u0633\u0627\u0626\u0644', color: '#8b5cf6', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' },
        { key: 'meeting', en: 'Meetings', ar: '\u0627\u062C\u062A\u0645\u0627\u0639\u0627\u062A', color: '#f59e0b', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    ];
    // Sum from activity trend (activities are total count per day)
    const totalActs = trend.reduce((sum, d) => sum + (d.activities || 0), 0);
    // Distribute proportionally based on source performance or equal split
    return channels.map(ch => {
        const count = trend.reduce((sum, d) => sum + (d[ch.key] || 0), 0);
        return { ...ch, count, pct: totalActs > 0 ? Math.round((count / totalActs) * 100) : 0 };
    });
});

const channelMax = computed(() => Math.max(...channelBreakdown.value.map(c => c.count), 1));

/* ── Print report ── */
function printReport() {
    window.print();
}

/* ── Keyboard shortcuts ── */
function handleReportsKey(e) {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
    if (e.key === 'p' || e.key === 'P') { e.preventDefault(); printReport(); }
    if (e.key === 'e' || e.key === 'E') { e.preventDefault(); exportCSV(); }
    if (e.key === 'c' || e.key === 'C') { e.preventDefault(); showComparison.value = !showComparison.value; }
    if (e.key === 'r' || e.key === 'R') { e.preventDefault(); router.reload({ preserveScroll: true }); }
    if (e.key === 'l' || e.key === 'L') { e.preventDefault(); router.get('/secretary/crm/leads'); }
    if (e.key === 'd' || e.key === 'D') { e.preventDefault(); router.get('/secretary/crm'); }
}

onMounted(() => { document.addEventListener('keydown', handleReportsKey); });
onBeforeUnmount(() => { document.removeEventListener('keydown', handleReportsKey); });
</script>

<template>
<SecretaryLayout :title="isRtl ? 'تقارير CRM' : 'CRM Reports'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/20 to-slate-50 p-4 md:p-6" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- Header -->
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 p-5 sm:p-6 md:p-8 mb-6 shadow-xl transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
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
                        class="doctorato-input bg-white/20 backdrop-blur text-white border border-white/30 rounded-xl py-2.5 px-4 text-sm focus:ring-2 focus:ring-white/50 [&>option]:text-slate-800">
                    <option v-for="(label, key) in periodLabels" :key="key" :value="key">{{ isRtl ? label.ar : label.en }}</option>
                </select>
                <!-- Comparison toggle -->
                <button @click="showComparison = !showComparison"
                    :class="['inline-flex items-center gap-1.5 backdrop-blur border rounded-xl py-2.5 px-4 text-sm font-medium transition-all duration-200',
                        showComparison ? 'bg-white/30 text-white border-white/40' : 'bg-white/10 text-white/70 border-white/20 hover:bg-white/20']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    {{ isRtl ? 'مقارنة' : 'Compare' }}
                </button>
                <!-- Print Report -->
                <button @click="printReport"
                        class="inline-flex items-center gap-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white border border-white/30 rounded-xl py-2.5 px-4 text-sm font-medium transition-all duration-200 print:hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    {{ isRtl ? '\u0637\u0628\u0627\u0639\u0629' : 'Print' }}
                </button>
                <!-- Export CSV -->
                <button @click="exportCSV"
                        class="inline-flex items-center gap-2 bg-white/20 backdrop-blur hover:bg-white/30 text-white border border-white/30 rounded-xl py-2.5 px-4 text-sm font-medium transition-all duration-200 print:hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ isRtl ? '\u062A\u0635\u062F\u064A\u0631' : 'Export' }}
                </button>
                <!-- Keyboard hints -->
                <div class="hidden lg:flex items-center gap-2 text-white/50 text-[10px] print:hidden">
                    <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 bg-white/15 rounded text-[9px] font-mono">P</kbd> {{ isRtl ? 'طباعة' : 'Print' }}</span>
                    <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 bg-white/15 rounded text-[9px] font-mono">E</kbd> {{ isRtl ? 'تصدير' : 'Export' }}</span>
                    <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 bg-white/15 rounded text-[9px] font-mono">C</kbd> {{ isRtl ? 'مقارنة' : 'Compare' }}</span>
                    <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 bg-white/15 rounded text-[9px] font-mono">R</kbd> {{ isRtl ? 'تحديث' : 'Refresh' }}</span>
                </div>
            </div>
        </div>
        <!-- Custom date range -->
        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showCustomRange" class="mt-4 flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-teal-100">{{ isRtl ? 'من' : 'From' }}</label>
                    <input v-model="customFrom" type="date" class="doctorato-input bg-white/20 backdrop-blur text-white border border-white/30 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-white/50 [color-scheme:dark]">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-teal-100">{{ isRtl ? 'إلى' : 'To' }}</label>
                    <input v-model="customTo" type="date" class="doctorato-input bg-white/20 backdrop-blur text-white border border-white/30 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-white/50 [color-scheme:dark]">
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
            { key: 'new_leads', icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z', labelEn: 'New Leads', labelAr: 'عملاء جدد', bg: 'bg-slate-100', iconColor: 'text-[#1B365D]' },
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

    <!-- Best Performing Source Highlight -->
    <div v-if="bestSource"
         :class="['bg-gradient-to-r from-teal-50 via-emerald-50 to-teal-50 rounded-2xl shadow-sm border border-teal-100 p-5 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '180ms' }">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-sm flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-teal-600 uppercase tracking-wider">{{ isRtl ? 'أفضل مصدر أداء' : 'Top Performing Source' }}</span>
                </div>
                <p class="text-lg font-bold text-slate-800 mt-0.5">{{ isRtl ? bestSource.name_ar : bestSource.name_en }}</p>
            </div>
            <div class="flex items-center gap-5 flex-wrap">
                <div class="text-center">
                    <p class="text-2xl font-bold text-teal-700">{{ bestSource.total }}</p>
                    <p class="text-[10px] text-teal-600 font-medium">{{ isRtl ? 'عميل' : 'leads' }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-emerald-600">{{ bestSource.conversion_rate }}%</p>
                    <p class="text-[10px] text-emerald-600 font-medium">{{ isRtl ? 'تحويل' : 'conversion' }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-[#1B365D]">{{ bestSource.converted }}</p>
                    <p class="text-[10px] text-[#1B365D] font-medium">{{ isRtl ? 'محول' : 'converted' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversion Funnel + Response Time -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
        <!-- Funnel -->
        <div :class="['md:col-span-2 lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
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
                        <span class="text-sm font-bold text-amber-600">{{ funnel?.negotiation || 0 }}</span>
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

    <!-- Channel Performance Breakdown -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '420ms' }">
        <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            {{ isRtl ? '\u0623\u062F\u0627\u0621 \u0642\u0646\u0648\u0627\u062A \u0627\u0644\u062A\u0648\u0627\u0635\u0644' : 'Communication Channel Performance' }}
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-4">
            <div v-for="ch in channelBreakdown" :key="ch.key"
                 class="bg-slate-50 rounded-xl p-3 border border-slate-100 text-center hover:border-slate-200 transition-all duration-200 hover:shadow-sm">
                <div class="w-9 h-9 mx-auto rounded-lg flex items-center justify-center mb-2" :style="{ backgroundColor: ch.color + '18' }">
                    <svg class="w-4 h-4" :style="{ color: ch.color }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="ch.icon"/></svg>
                </div>
                <p class="text-lg font-bold text-slate-800">{{ ch.count }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ isRtl ? ch.ar : ch.en }}</p>
            </div>
        </div>
        <!-- Horizontal bar comparison -->
        <div class="space-y-2.5">
            <div v-for="ch in channelBreakdown" :key="ch.key + '-bar'" class="flex items-center gap-3">
                <span class="text-xs font-medium text-slate-500 w-20 truncate">{{ isRtl ? ch.ar : ch.en }}</span>
                <div class="flex-1 h-5 bg-slate-50 rounded-lg overflow-hidden relative">
                    <div class="h-full rounded-lg transition-all duration-1000 ease-out flex items-center justify-end px-2"
                         :style="{ width: mounted ? Math.max((ch.count / channelMax) * 100, 3) + '%' : '0%', backgroundColor: ch.color }">
                        <span v-if="ch.count > 0" class="text-[10px] font-bold text-white">{{ ch.pct }}%</span>
                    </div>
                </div>
                <span class="text-xs font-semibold text-slate-700 w-8 text-center tabular-nums">{{ ch.count }}</span>
            </div>
        </div>
    </div>

    <!-- Source Performance + Top Leads -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6">
        <!-- Source Performance -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '450ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                {{ isRtl ? 'أداء المصادر' : 'Source Performance' }}
            </h3>
            <div v-if="sourcePerformance && sourcePerformance.length" class="space-y-3">
                <div v-for="(source, sIdx) in sourcePerformance" :key="source.name_en"
                     class="bg-slate-50 rounded-xl p-3 border border-slate-100 transition-all duration-200 hover:border-slate-200 hover:shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <!-- Rank medal for top 3, number badge for rest -->
                            <div v-if="sIdx < 3" class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :class="sIdx === 0 ? 'bg-amber-100' : sIdx === 1 ? 'bg-slate-200' : 'bg-amber-100'">
                                <svg class="w-4.5 h-4.5" :class="sIdx === 0 ? 'text-amber-500' : sIdx === 1 ? 'text-slate-400' : 'text-amber-400'" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </div>
                            <div v-else class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 :style="{ background: source.color || '#0d9488' }">
                                {{ sIdx + 1 }}
                            </div>
                            <div class="min-w-0">
                                <span class="text-sm font-medium text-slate-700">{{ isRtl ? source.name_ar : source.name_en }}</span>
                                <span v-if="sIdx === 0" class="ms-1.5 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-200">{{ isRtl ? 'الأفضل' : 'Top' }}</span>
                            </div>
                        </div>
                        <span class="text-xs text-slate-500 tabular-nums">{{ source.total }} {{ isRtl ? 'عميل' : 'leads' }}</span>
                    </div>
                    <!-- Conversion rate progress bar -->
                    <div class="mb-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] text-slate-400">{{ isRtl ? 'معدل التحويل' : 'Conversion Rate' }}</span>
                            <span class="text-xs font-bold tabular-nums" :class="source.conversion_rate >= 30 ? 'text-emerald-600' : source.conversion_rate >= 15 ? 'text-amber-600' : 'text-slate-500'">{{ source.conversion_rate }}%</span>
                        </div>
                        <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-1000 ease-out"
                                 :class="source.conversion_rate >= 30 ? 'bg-emerald-500' : source.conversion_rate >= 15 ? 'bg-amber-400' : 'bg-slate-400'"
                                 :style="{ width: mounted ? Math.min(source.conversion_rate, 100) + '%' : '0%' }"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <div class="flex items-center gap-1 text-emerald-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ source.converted }} {{ isRtl ? 'محول' : 'converted' }}
                        </div>
                        <div class="flex items-center gap-1 text-[#1B365D]">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            {{ source.avg_score }} {{ isRtl ? 'نقاط' : 'score' }}
                        </div>
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
                                  idx === 0 ? 'bg-amber-100 text-amber-700' : idx === 1 ? 'bg-slate-200 text-slate-600' : idx === 2 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-500']">
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

    <!-- Print Header (only visible when printing) -->
    <div class="hidden print:block print:mb-6">
        <div class="flex items-center justify-between border-b-2 border-teal-500 pb-3 mb-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ isRtl ? '\u062A\u0642\u0631\u064A\u0631 CRM' : 'CRM Report' }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ isRtl ? '\u0627\u0644\u0641\u062A\u0631\u0629: ' : 'Period: ' }}{{ isRtl ? (periodLabels[selectedPeriod]?.ar || selectedPeriod) : (periodLabels[selectedPeriod]?.en || selectedPeriod) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">{{ new Date().toLocaleDateString(isRtl ? 'ar-SA' : 'en-US', { year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                <p class="text-xs text-gray-400">Doctorato Polyclinic</p>
            </div>
        </div>
    </div>

</div>
</SecretaryLayout>
</template>

<style>
@media print {
    nav, aside, .sidebar { display: none !important; }
    [data-print-hide] { display: none !important; }
    [data-print-show] { display: block !important; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .min-h-screen { min-height: auto !important; padding: 0 !important; }
    .shadow-sm, .shadow-md, .shadow-xl, .shadow-lg, .shadow-2xl { box-shadow: none !important; }
    .grid > div { break-inside: avoid; page-break-inside: avoid; }
}
</style>
