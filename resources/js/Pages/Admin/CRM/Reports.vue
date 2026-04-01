<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency } = useCurrency();

const props = defineProps({
    funnel: Object,
    bySource: Array,
    byCampaign: Array,
    teamPerformance: Array,
    lossReasons: Array,
    commissionsSummary: Object,
    dailyTrend: Object,
    campaignRoi: Array,
    conversionTimeData: Object,
    staffPerformance: Array,
    monthlyComparison: Array,
    modules: Object,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');
const selectedPeriod = ref(props.filters?.period || 'month');

const activeModules = computed(() => {
    if (!props.modules) return [];
    return Object.values(props.modules).filter(m => m.enabled);
});

const periods = [
    { key: 'today', label: 'Today' },
    { key: 'week', label: 'This Week' },
    { key: 'month', label: 'This Month' },
    { key: 'custom', label: 'Custom' },
];

function selectPeriod(period) {
    selectedPeriod.value = period;
    if (period === 'custom') return;

    const now = new Date();
    let to = now.toISOString().split('T')[0];
    let from = to;

    if (period === 'week') {
        const d = new Date(now);
        d.setDate(d.getDate() - 7);
        from = d.toISOString().split('T')[0];
    } else if (period === 'month') {
        const d = new Date(now);
        d.setMonth(d.getMonth() - 1);
        from = d.toISOString().split('T')[0];
    }

    dateFrom.value = from;
    dateTo.value = to;
    applyFilters();
}

function applyFilters() {
    router.get('/admin/crm-reports', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

watch(moduleFilter, () => applyFilters());

const funnelStages = [
    { key: 'total', label: 'Total Leads', color: '#3B82F6', gradient: 'from-blue-500 to-blue-600' },
    { key: 'contacted', label: 'Contacted', color: '#6366F1', gradient: 'from-indigo-500 to-indigo-600' },
    { key: 'qualified', label: 'Qualified', color: '#8B5CF6', gradient: 'from-purple-500 to-purple-600' },
    { key: 'appointment', label: 'Appointment', color: '#C4A265', gradient: 'from-[#C4A265] to-[#D4B87A]' },
    { key: 'converted', label: 'Converted', color: '#22C55E', gradient: 'from-green-500 to-green-600' },
    { key: 'lost', label: 'Lost', color: '#EF4444', gradient: 'from-red-500 to-red-600' },
];

function funnelWidth(key) {
    if (!props.funnel?.total || props.funnel.total === 0) return '0%';
    if (key === 'total') return '100%';
    return Math.max((props.funnel[key] / props.funnel.total) * 100, 4) + '%';
}

function funnelPercent(key) {
    if (!props.funnel?.total || props.funnel.total === 0) return 0;
    return ((props.funnel[key] / props.funnel.total) * 100).toFixed(1);
}

const maxDailyTrend = ref(0);
const trendEntries = ref([]);

if (props.dailyTrend) {
    const entries = Object.entries(props.dailyTrend);
    trendEntries.value = entries;
    maxDailyTrend.value = Math.max(...entries.map(([, v]) => v), 1);
}

const svgChartWidth = computed(() => trendEntries.value.length * 28);
const svgChartHeight = 140;

function barX(idx) { return idx * 28 + 4; }
function barHeight(count) { return (count / maxDailyTrend.value) * (svgChartHeight - 24); }
function barY(count) { return svgChartHeight - barHeight(count) - 20; }

function sourceMaxTotal() {
    if (!props.bySource?.length) return 1;
    return Math.max(...props.bySource.map(s => s.total), 1);
}

function campaignMaxTotal() {
    if (!props.byCampaign?.length) return 1;
    return Math.max(...props.byCampaign.map(c => c.total), 1);
}

function conversionRate() {
    if (!props.funnel?.total || props.funnel.total === 0) return '0.0';
    return ((props.funnel.converted / props.funnel.total) * 100).toFixed(1);
}

// ── Monthly Comparison helpers ──
const monthlyMax = computed(() => {
    if (!props.monthlyComparison?.length) return 1;
    return Math.max(...props.monthlyComparison.map(m => m.total), 1);
});

const monthlyChartHeight = 160;

function monthBarHeight(val) {
    return (val / monthlyMax.value) * (monthlyChartHeight - 30);
}

// ── Conversion time bucket helpers ──
const maxBucketCount = computed(() => {
    if (!props.conversionTimeData?.buckets) return 1;
    return Math.max(...Object.values(props.conversionTimeData.buckets), 1);
});

const kpiCards = [
    { key: 'total', label: 'Total Leads', gradient: 'from-blue-500 to-blue-600', bgLight: 'bg-blue-50', iconPath: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { key: 'contacted', label: 'Contacted', gradient: 'from-indigo-500 to-indigo-600', bgLight: 'bg-indigo-50', iconPath: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
    { key: 'converted', label: 'Converted', gradient: 'from-green-500 to-green-600', bgLight: 'bg-green-50', iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'lost', label: 'Lost', gradient: 'from-red-500 to-red-600', bgLight: 'bg-red-50', iconPath: 'M6 18L18 6M6 6l12 12' },
];
</script>

<template>
    <AdminLayout title="CRM Reports">
        <div class="space-y-8">

            <!-- Module Tabs -->
            <div v-if="activeModules.length > 1" class="bg-white rounded-lg shadow-sm p-1.5 flex gap-1 flex-wrap">
                <button @click="moduleFilter = ''"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="moduleFilter === '' ? 'bg-gray-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    {{ $t('a_all') }}
                </button>
                <button v-for="mod in activeModules" :key="mod.slug" @click="moduleFilter = mod.slug"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1.5"
                    :class="moduleFilter === mod.slug ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                    :style="moduleFilter === mod.slug ? { backgroundColor: mod.color } : {}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                    <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
                </button>
            </div>

            <!-- ====== Page Header with Gold Top Bar ====== -->
            <div
                class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500 ease-out"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            >
                <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #C4A265, #D4B87A, #C4A265);"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Title -->
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_crm_reports') }}</h1>
                                <p class="text-sm text-gray-500 mt-0.5">Lead funnel, source performance, team metrics, and commissions</p>
                            </div>
                        </div>

                        <!-- Period Selector Pills -->
                        <div class="flex items-center bg-gray-50 rounded-xl p-1 border border-gray-100">
                            <button
                                v-for="p in periods"
                                :key="p.key"
                                @click="selectPeriod(p.key)"
                                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200"
                                :class="selectedPeriod === p.key
                                    ? 'text-white shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700 hover:bg-white'"
                                :style="selectedPeriod === p.key ? 'background: linear-gradient(135deg, #C4A265, #D4B87A);' : ''"
                            >
                                {{ p.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="mt-6 pt-5 border-t border-gray-100 flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_from') }}</label>
                            <div class="relative">
                                <input
                                    v-model="dateFrom"
                                    type="date"
                                    class="ltr:pl-9 rtl:pr-9 ltr:pr-3 rtl:pl-3 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200"
                                />
                                <svg class="w-4 h-4 text-gray-400 absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                        <div class="flex items-center gap-2">
                            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_to_label') }}</label>
                            <div class="relative">
                                <input
                                    v-model="dateTo"
                                    type="date"
                                    class="ltr:pl-9 rtl:pr-9 ltr:pr-3 rtl:pl-3 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200"
                                />
                                <svg class="w-4 h-4 text-gray-400 absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <button
                            @click="applyFilters"
                            class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0"
                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Apply Filter
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ====== KPI Summary Cards ====== -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div
                    v-for="(kpi, idx) in kpiCards"
                    :key="kpi.key"
                    class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 overflow-hidden hover:shadow-md hover:-translate-y-1 transition-all duration-300 ease-out group cursor-default"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: (idx * 60 + 80) + 'ms' }"
                >
                    <!-- Decorative circle -->
                    <div class="absolute -top-4 -ltr:right-4 rtl:left-4 w-20 h-20 rounded-full opacity-[0.04] group-hover:opacity-[0.08] transition-opacity duration-300"
                        :class="'bg-gradient-to-br ' + kpi.gradient"></div>

                    <div class="flex items-start justify-between mb-4">
                        <div
                            :class="'w-11 h-11 rounded-xl bg-gradient-to-br ' + kpi.gradient + ' flex items-center justify-center shadow-sm group-hover:scale-110 group-hover:shadow-md transition-all duration-300'"
                        >
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="kpi.iconPath" />
                            </svg>
                        </div>
                        <!-- Trend indicator for converted -->
                        <div v-if="kpi.key === 'converted' && funnel?.total > 0" class="flex items-center gap-1 text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded-lg">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                            {{ conversionRate() }}%
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800 tracking-tight">{{ funnel?.[kpi.key] ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1 font-semibold uppercase tracking-wider">{{ kpi.label }}</p>

                    <!-- Subtle bottom accent -->
                    <div class="absolute bottom-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-0.5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        :class="'bg-gradient-to-r ' + kpi.gradient"></div>
                </div>
            </div>

            <!-- ====== Lead Funnel ====== -->
            <div
                class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                :style="{ transitionDelay: '320ms' }"
            >
                <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #C4A265, #D4B87A, #C4A265);"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_lead_funnel') }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_conversion_pipeline_viz') }}</p>
                        </div>
                    </div>
                    <div class="max-w-3xl mx-auto space-y-2.5">
                        <div
                            v-for="(stage, idx) in funnelStages"
                            :key="stage.key"
                            class="flex items-center gap-4 group transition-all duration-500"
                            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
                            :style="{ transitionDelay: (idx * 80 + 350) + 'ms' }"
                        >
                            <div class="w-28 ltr:text-right rtl:text-left">
                                <p class="text-sm font-semibold text-gray-600">{{ stage.label }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ funnelPercent(stage.key) }}%</p>
                            </div>
                            <div class="flex-1 flex justify-center">
                                <div
                                    class="relative h-11 rounded-xl overflow-hidden transition-all duration-700 ease-out group-hover:shadow-sm"
                                    :style="{ width: mounted ? funnelWidth(stage.key) : '0%', background: `linear-gradient(135deg, ${stage.color}, ${stage.color}BB)` }"
                                >
                                    <div class="absolute inset-0 bg-white/10"></div>
                                    <!-- Shimmer effect on hover -->
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-sm font-bold text-white drop-shadow-sm">{{ funnel?.[stage.key] ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="w-14"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== Source Performance + Campaign Performance ====== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Source Performance (Bar Chart + Table) -->
                <div
                    class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: '400ms' }"
                >
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #C4A265, #D4B87A, #C4A265);"></div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_source_performance') }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_lead_distribution_channel') }}</p>
                            </div>
                        </div>

                        <!-- CSS Bar Chart -->
                        <div v-if="bySource?.length" class="space-y-4 mb-6">
                            <div v-for="(s, idx) in bySource" :key="s.name" class="group">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-3 h-3 rounded-full shadow-sm flex-shrink-0" :style="{ backgroundColor: s.color }"></span>
                                        <span class="text-sm font-semibold text-gray-700">{{ s.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="text-gray-400">{{ s.total }} leads</span>
                                        <span
                                            class="font-bold px-2 py-0.5 rounded-full"
                                            :class="s.rate >= 30 ? 'text-green-700 bg-green-50' : s.rate >= 15 ? 'text-amber-700 bg-amber-50' : 'text-gray-500 bg-gray-50'"
                                        >{{ s.rate }}%</span>
                                    </div>
                                </div>
                                <div class="h-2.5 bg-gray-50 rounded-full overflow-hidden border border-gray-100">
                                    <div
                                        class="h-full rounded-full transition-all duration-700 ease-out group-hover:opacity-80"
                                        :style="{
                                            width: mounted ? (s.total / sourceMaxTotal() * 100) + '%' : '0%',
                                            background: `linear-gradient(135deg, ${s.color}, ${s.color}CC)`,
                                            transitionDelay: (idx * 60 + 420) + 'ms',
                                        }"
                                    ></div>
                                </div>
                                <div class="flex items-center gap-4 mt-1">
                                    <span class="text-[10px] text-green-600 font-semibold">{{ s.converted }} converted</span>
                                    <span class="text-[10px] text-red-400">{{ s.lost }} lost</span>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Source Table -->
                        <div v-if="bySource?.length" class="border-t border-gray-100 pt-5">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $t('a_detailed_breakdown') }}</p>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-[10px] text-gray-400 uppercase border-b border-gray-100">
                                            <th class="ltr:text-left rtl:text-right py-2.5 font-semibold tracking-wider">{{ $t('a_source') }}</th>
                                            <th class="text-center py-2.5 font-semibold tracking-wider">{{ $t('a_total') }}</th>
                                            <th class="text-center py-2.5 font-semibold tracking-wider">Conv.</th>
                                            <th class="text-center py-2.5 font-semibold tracking-wider">{{ $t('a_lost') }}</th>
                                            <th class="ltr:text-right rtl:text-left py-2.5 font-semibold tracking-wider">{{ $t('a_rate') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="s in bySource" :key="'tbl-'+s.name" class="hover:bg-gray-50/60 transition-colors duration-150">
                                            <td class="py-2.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: s.color }"></span>
                                                    <span class="font-medium text-gray-700 text-xs">{{ s.name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-2.5 text-center text-gray-600 text-xs font-medium">{{ s.total }}</td>
                                            <td class="py-2.5 text-center text-green-600 font-semibold text-xs">{{ s.converted }}</td>
                                            <td class="py-2.5 text-center text-red-500 text-xs">{{ s.lost }}</td>
                                            <td class="py-2.5 ltr:text-right rtl:text-left">
                                                <span class="text-xs font-bold" :class="s.rate >= 30 ? 'text-green-600' : s.rate >= 15 ? 'text-amber-600' : 'text-gray-500'">{{ s.rate }}%</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-if="!bySource?.length" class="py-16 text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">No data for this period</p>
                        </div>
                    </div>
                </div>

                <!-- Campaign Performance -->
                <div
                    class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: '460ms' }"
                >
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #C4A265, #D4B87A, #C4A265);"></div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_campaign_performance') }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_campaign_effectiveness') }}</p>
                            </div>
                        </div>

                        <!-- Campaign Cards -->
                        <div v-if="byCampaign?.length" class="space-y-4">
                            <div
                                v-for="(c, idx) in byCampaign"
                                :key="c.name"
                                class="p-4 rounded-xl border border-gray-100 hover:border-[#C4A265]/30 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-200 group"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-semibold text-gray-700">{{ c.name }}</span>
                                    <div class="flex items-center gap-1.5">
                                        <svg v-if="c.rate >= 20" class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        <svg v-else class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                        <span class="text-sm font-bold" :class="c.rate >= 20 ? 'text-green-600' : 'text-gray-500'">{{ c.rate }}%</span>
                                    </div>
                                </div>
                                <!-- Progress bar -->
                                <div class="h-2 bg-gray-50 rounded-full overflow-hidden border border-gray-100 mb-3">
                                    <div
                                        class="h-full rounded-full transition-all duration-700 ease-out"
                                        :style="{
                                            width: mounted ? (c.total / campaignMaxTotal() * 100) + '%' : '0%',
                                            background: 'linear-gradient(135deg, #C4A265, #D4B87A)',
                                            transitionDelay: (idx * 60 + 480) + 'ms',
                                        }"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="text-gray-400">{{ c.total }} leads</span>
                                        <span class="text-green-600 font-semibold">{{ c.converted }} conv.</span>
                                    </div>
                                    <span class="text-gray-400 font-mono">CPL {{ formatCurrency(c.cost_per_lead) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="!byCampaign?.length" class="py-16 text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">No campaigns with leads in this period</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== Team Performance + Loss Reasons ====== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Team Performance -->
                <div
                    class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: '520ms' }"
                >
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #C4A265, #D4B87A, #C4A265);"></div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_team_performance') }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_agent_conversion_metrics') }}</p>
                            </div>
                        </div>

                        <div v-if="teamPerformance?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="(u, idx) in teamPerformance"
                                :key="u.name"
                                class="p-4 rounded-xl border border-gray-100 hover:border-[#C4A265]/30 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 group"
                                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
                                :style="{ transitionDelay: (540 + idx * 60) + 'ms' }"
                            >
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="relative flex-shrink-0">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-sm group-hover:shadow-md group-hover:scale-105 transition-all duration-200" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                            {{ u.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div
                                            class="absolute -bottom-1 -ltr:right-1 rtl:left-1 w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold border-2 border-white shadow-sm"
                                            :class="idx === 0 ? 'bg-amber-400 text-amber-900' : idx === 1 ? 'bg-gray-300 text-gray-700' : idx === 2 ? 'bg-orange-300 text-orange-800' : 'bg-gray-100 text-gray-500'"
                                        >{{ idx + 1 }}</div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-800 truncate">{{ u.name }}</p>
                                        <p class="text-[10px] text-gray-400">{{ u.total }} total leads</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">{{ $t('a_conversion') }}</span>
                                    <div class="flex items-center gap-1.5">
                                        <svg v-if="u.rate >= 30" class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        <svg v-else-if="u.rate < 15" class="w-3 h-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                        <span class="text-sm font-bold" :class="u.rate >= 30 ? 'text-green-600' : u.rate >= 15 ? 'text-amber-600' : 'text-gray-500'">{{ u.rate }}%</span>
                                    </div>
                                </div>
                                <div class="h-1.5 bg-gray-50 rounded-full overflow-hidden border border-gray-100">
                                    <div
                                        class="h-full rounded-full transition-all duration-700 ease-out"
                                        :style="{ width: mounted ? u.rate + '%' : '0%', background: 'linear-gradient(135deg, #C4A265, #D4B87A)', transitionDelay: (idx * 60 + 560) + 'ms' }"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-[10px] text-green-600 font-semibold">{{ u.converted }} converted</span>
                                    <span class="text-[10px] text-gray-400">{{ u.total - u.converted }} remaining</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="!teamPerformance?.length" class="py-16 text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">No team data for this period</p>
                        </div>
                    </div>
                </div>

                <!-- Loss Reasons -->
                <div
                    class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: '580ms' }"
                >
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #C4A265, #D4B87A, #C4A265);"></div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_top_loss_reasons') }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_lead_attrition_causes') }}</p>
                            </div>
                        </div>

                        <div v-if="lossReasons?.length" class="space-y-3">
                            <div
                                v-for="(reason, idx) in lossReasons"
                                :key="idx"
                                class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:border-red-100 hover:bg-red-50/20 transition-all duration-200 group"
                                :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                                :style="{ transitionDelay: (600 + idx * 60) + 'ms' }"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 font-bold text-sm transition-all duration-200"
                                    :class="idx === 0 ? 'bg-red-100 text-red-600' : idx === 1 ? 'bg-red-50 text-red-500' : idx === 2 ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-400'"
                                >{{ idx + 1 }}</div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-700 capitalize truncate">{{ reason.loss_reason?.replace(/_/g, ' ') }}</p>
                                    <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-700 ease-out"
                                            :style="{
                                                width: mounted ? Math.max((reason.count / (lossReasons[0]?.count || 1)) * 100, 4) + '%' : '0%',
                                                background: idx === 0 ? 'linear-gradient(135deg, #EF4444, #F87171)' : idx === 1 ? 'linear-gradient(135deg, #F97316, #FB923C)' : idx === 2 ? 'linear-gradient(135deg, #F59E0B, #FBBF24)' : 'linear-gradient(135deg, #9CA3AF, #D1D5DB)',
                                                transitionDelay: (idx * 80 + 620) + 'ms',
                                            }"
                                        ></div>
                                    </div>
                                </div>
                                <div class="ltr:text-right rtl:text-left flex-shrink-0">
                                    <span class="text-lg font-bold text-gray-800">{{ reason.count }}</span>
                                    <p class="text-[10px] text-gray-400">leads</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="!lossReasons?.length" class="py-16 text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-green-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">No lost leads in this period</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== Commissions Summary + Daily Trend ====== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Commissions Summary -->
                <div
                    class="transition-all duration-500 ease-out space-y-4"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: '640ms' }"
                >
                    <div class="flex items-center gap-2 px-1">
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $t('a_commissions_summary') }}</h3>
                    </div>

                    <!-- Total Card (Gold Gradient) -->
                    <div class="relative overflow-hidden rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                        <div class="absolute top-0 ltr:right-0 rtl:left-0 w-28 h-28 rounded-full bg-white/10 -translate-y-8 translate-x-8"></div>
                        <div class="absolute bottom-0 ltr:left-0 rtl:right-0 w-16 h-16 rounded-full bg-white/5 translate-y-6 -translate-x-4"></div>
                        <div class="relative">
                            <p class="text-sm text-white/70 font-semibold uppercase tracking-wider">{{ $t('a_total_earned') }}</p>
                            <p class="text-3xl font-bold text-white mt-2 tracking-tight">{{ formatCurrency(commissionsSummary?.total) }}</p>
                        </div>
                    </div>

                    <!-- Paid + Pending -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="relative bg-white rounded-2xl border border-gray-100 p-5 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 group">
                            <div class="absolute bottom-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-0.5 bg-gradient-to-r from-green-400 to-green-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-4.5 h-4.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-lg font-bold text-green-600">{{ formatCurrency(commissionsSummary?.paid) }}</p>
                            <p class="text-[10px] text-gray-400 mt-1 font-semibold uppercase tracking-wider">{{ $t('a_paid_out') }}</p>
                        </div>
                        <div class="relative bg-white rounded-2xl border border-gray-100 p-5 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 group">
                            <div class="absolute bottom-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-0.5 bg-gradient-to-r from-amber-400 to-amber-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-lg font-bold text-amber-600">{{ formatCurrency(commissionsSummary?.pending) }}</p>
                            <p class="text-[10px] text-gray-400 mt-1 font-semibold uppercase tracking-wider">{{ $t('a_pending') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Daily Lead Trend (SVG Chart) -->
                <div
                    class="lg:col-span-2 relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: '700ms' }"
                >
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #C4A265, #D4B87A, #C4A265);"></div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_daily_lead_trend') }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_new_leads_per_day') }}</p>
                            </div>
                        </div>

                        <div v-if="trendEntries.length" class="overflow-x-auto">
                            <svg :width="Math.max(svgChartWidth, 300)" :height="svgChartHeight + 30" class="w-full" :viewBox="`0 0 ${Math.max(svgChartWidth, 300)} ${svgChartHeight + 30}`" preserveAspectRatio="xMidYMid meet">
                                <!-- Grid lines -->
                                <line x1="0" :y1="svgChartHeight - 20" :x2="Math.max(svgChartWidth, 300)" :y2="svgChartHeight - 20" stroke="#F3F4F6" stroke-width="1" />
                                <line x1="0" :y1="(svgChartHeight - 20) / 2" :x2="Math.max(svgChartWidth, 300)" :y2="(svgChartHeight - 20) / 2" stroke="#F3F4F6" stroke-width="1" stroke-dasharray="4,4" />
                                <line x1="0" y1="0" :x2="Math.max(svgChartWidth, 300)" y2="0" stroke="#F3F4F6" stroke-width="1" stroke-dasharray="4,4" />

                                <!-- Bars -->
                                <g v-for="([date, count], idx) in trendEntries" :key="date">
                                    <rect :x="barX(idx) + 1" :y="barY(count) + 1" width="20" :height="barHeight(count)"
                                        rx="4" fill="#C4A26510" />
                                    <rect :x="barX(idx)" :y="barY(count)" width="20" :height="Math.max(barHeight(count), 2)"
                                        rx="4" fill="url(#goldGradient)" class="transition-all duration-500"
                                        :style="{ opacity: mounted ? 1 : 0, transitionDelay: (idx * 20) + 'ms' }">
                                        <title>{{ date }}: {{ count }} leads</title>
                                    </rect>
                                    <text v-if="count > 0" :x="barX(idx) + 10" :y="barY(count) - 4"
                                        text-anchor="middle" fill="#9CA3AF" font-size="9" font-weight="600" font-family="monospace">{{ count }}</text>
                                    <text :x="barX(idx) + 10" :y="svgChartHeight + 8"
                                        text-anchor="middle" fill="#D1D5DB" font-size="7" font-family="sans-serif"
                                        :transform="`rotate(-45, ${barX(idx) + 10}, ${svgChartHeight + 8})`">{{ date.slice(5) }}</text>
                                </g>

                                <defs>
                                    <linearGradient id="goldGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#C4A265" />
                                        <stop offset="100%" stop-color="#D4B87A" />
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div v-else class="py-16 text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">No trend data available</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== Campaign ROI Report ====== -->
            <div
                class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                :style="{ transitionDelay: '760ms' }"
            >
                <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #22C55E, #16A34A);"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-gradient-to-br from-green-500 to-green-600">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_campaign_roi') }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_cost_vs_revenue') }}</p>
                        </div>
                    </div>

                    <div v-if="campaignRoi?.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-[10px] text-gray-400 uppercase border-b border-gray-100">
                                    <th class="ltr:text-left rtl:text-right py-3 font-semibold tracking-wider">{{ $t('a_campaign') }}</th>
                                    <th class="text-center py-3 font-semibold tracking-wider">{{ $t('a_leads') }}</th>
                                    <th class="text-center py-3 font-semibold tracking-wider">Conv.</th>
                                    <th class="ltr:text-right rtl:text-left py-3 font-semibold tracking-wider">{{ $t('a_cost') }}</th>
                                    <th class="ltr:text-right rtl:text-left py-3 font-semibold tracking-wider">{{ $t('a_revenue') }}</th>
                                    <th class="ltr:text-right rtl:text-left py-3 font-semibold tracking-wider">CPL</th>
                                    <th class="ltr:text-right rtl:text-left py-3 font-semibold tracking-wider">CPC</th>
                                    <th class="ltr:text-right rtl:text-left py-3 font-semibold tracking-wider">{{ $t('a_roi') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="c in campaignRoi" :key="c.name" class="hover:bg-gray-50/60 transition-colors duration-150">
                                    <td class="py-3">
                                        <span class="font-semibold text-gray-700 text-xs">{{ c.name }}</span>
                                    </td>
                                    <td class="py-3 text-center text-gray-600 text-xs font-medium">{{ c.total_leads }}</td>
                                    <td class="py-3 text-center text-green-600 font-semibold text-xs">{{ c.converted_leads }}</td>
                                    <td class="py-3 ltr:text-right rtl:text-left text-gray-600 text-xs font-mono">{{ formatCurrency(c.actual_cost || c.budget) }}</td>
                                    <td class="py-3 ltr:text-right rtl:text-left text-green-600 font-semibold text-xs font-mono">{{ formatCurrency(c.revenue) }}</td>
                                    <td class="py-3 ltr:text-right rtl:text-left text-gray-500 text-xs font-mono">{{ formatCurrency(c.cost_per_lead) }}</td>
                                    <td class="py-3 ltr:text-right rtl:text-left text-gray-500 text-xs font-mono">{{ formatCurrency(c.cost_per_conversion) }}</td>
                                    <td class="py-3 ltr:text-right rtl:text-left">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                            :class="c.roi > 0 ? 'bg-green-50 text-green-700' : c.roi === 0 ? 'bg-gray-50 text-gray-500' : 'bg-red-50 text-red-600'"
                                        >
                                            <svg v-if="c.roi > 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                            <svg v-else-if="c.roi < 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                            {{ c.roi }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-16 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-400 font-medium">No campaign ROI data for this period</p>
                    </div>
                </div>
            </div>

            <!-- ====== Conversion Time + Monthly Comparison ====== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Average Conversion Time -->
                <div
                    class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: '820ms' }"
                >
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #6366F1, #818CF8);"></div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-gradient-to-br from-indigo-500 to-indigo-600">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_conversion_time') }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_days_to_conversion') }}</p>
                            </div>
                        </div>

                        <div v-if="conversionTimeData?.total_converted > 0">
                            <!-- KPI Cards -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                                <div class="p-3 rounded-xl bg-indigo-50 text-center">
                                    <p class="text-2xl font-bold text-indigo-700">{{ conversionTimeData.avg }}</p>
                                    <p class="text-[10px] text-indigo-500 font-semibold uppercase tracking-wider mt-1">{{ $t('a_avg_days') }}</p>
                                </div>
                                <div class="p-3 rounded-xl bg-green-50 text-center">
                                    <p class="text-2xl font-bold text-green-700">{{ conversionTimeData.min }}</p>
                                    <p class="text-[10px] text-green-500 font-semibold uppercase tracking-wider mt-1">{{ $t('a_fastest') }}</p>
                                </div>
                                <div class="p-3 rounded-xl bg-purple-50 text-center">
                                    <p class="text-2xl font-bold text-purple-700">{{ conversionTimeData.median }}</p>
                                    <p class="text-[10px] text-purple-500 font-semibold uppercase tracking-wider mt-1">{{ $t('a_median') }}</p>
                                </div>
                                <div class="p-3 rounded-xl bg-amber-50 text-center">
                                    <p class="text-2xl font-bold text-amber-700">{{ conversionTimeData.max }}</p>
                                    <p class="text-[10px] text-amber-500 font-semibold uppercase tracking-wider mt-1">{{ $t('a_slowest') }}</p>
                                </div>
                            </div>

                            <!-- Distribution Buckets -->
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $t('a_distribution') }}</p>
                            <div class="space-y-2.5">
                                <div v-for="(count, label) in conversionTimeData.buckets" :key="label" class="group">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-medium text-gray-600">{{ label }}</span>
                                        <span class="text-xs font-bold text-gray-800">{{ count }}</span>
                                    </div>
                                    <div class="h-2 bg-gray-50 rounded-full overflow-hidden border border-gray-100">
                                        <div
                                            class="h-full rounded-full transition-all duration-700 ease-out"
                                            :style="{
                                                width: mounted ? Math.max((count / maxBucketCount) * 100, count > 0 ? 4 : 0) + '%' : '0%',
                                                background: 'linear-gradient(135deg, #6366F1, #818CF8)',
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-4 text-center">Based on {{ conversionTimeData.total_converted }} converted leads</p>
                        </div>

                        <div v-else class="py-16 text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">No conversion data for this period</p>
                        </div>
                    </div>
                </div>

                <!-- Monthly Comparison Chart -->
                <div
                    class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    :style="{ transitionDelay: '880ms' }"
                >
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #F59E0B, #D97706);"></div>
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-gradient-to-br from-amber-500 to-amber-600">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_monthly_comparison') }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Performance trend over 6 months</p>
                            </div>
                        </div>

                        <div v-if="monthlyComparison?.length">
                            <!-- SVG Bar Chart -->
                            <svg :width="monthlyComparison.length * 80" :height="monthlyChartHeight + 40" class="w-full"
                                 :viewBox="`0 0 ${monthlyComparison.length * 80} ${monthlyChartHeight + 40}`" preserveAspectRatio="xMidYMid meet">
                                <!-- Grid lines -->
                                <line x1="0" :y1="monthlyChartHeight" :x2="monthlyComparison.length * 80" :y2="monthlyChartHeight" stroke="#F3F4F6" stroke-width="1" />
                                <line x1="0" :y1="monthlyChartHeight / 2" :x2="monthlyComparison.length * 80" :y2="monthlyChartHeight / 2" stroke="#F3F4F6" stroke-width="1" stroke-dasharray="4,4" />

                                <g v-for="(m, idx) in monthlyComparison" :key="m.month">
                                    <!-- Total bar -->
                                    <rect
                                        :x="idx * 80 + 10" :y="monthlyChartHeight - monthBarHeight(m.total)"
                                        width="24" :height="Math.max(monthBarHeight(m.total), 2)"
                                        rx="4" fill="#C4A265" opacity="0.3"
                                        class="transition-all duration-500"
                                        :style="{ opacity: mounted ? 0.3 : 0, transitionDelay: (idx * 60) + 'ms' }"
                                    >
                                        <title>{{ m.month }}: {{ m.total }} total</title>
                                    </rect>
                                    <!-- Converted bar -->
                                    <rect
                                        :x="idx * 80 + 38" :y="monthlyChartHeight - monthBarHeight(m.converted)"
                                        width="24" :height="Math.max(monthBarHeight(m.converted), m.converted > 0 ? 2 : 0)"
                                        rx="4" fill="#22C55E"
                                        class="transition-all duration-500"
                                        :style="{ opacity: mounted ? 1 : 0, transitionDelay: (idx * 60 + 100) + 'ms' }"
                                    >
                                        <title>{{ m.month }}: {{ m.converted }} converted</title>
                                    </rect>

                                    <!-- Total label -->
                                    <text v-if="m.total > 0" :x="idx * 80 + 22" :y="monthlyChartHeight - monthBarHeight(m.total) - 4"
                                        text-anchor="middle" fill="#C4A265" font-size="10" font-weight="600">{{ m.total }}</text>
                                    <!-- Converted label -->
                                    <text v-if="m.converted > 0" :x="idx * 80 + 50" :y="monthlyChartHeight - monthBarHeight(m.converted) - 4"
                                        text-anchor="middle" fill="#22C55E" font-size="10" font-weight="600">{{ m.converted }}</text>

                                    <!-- Month label -->
                                    <text :x="idx * 80 + 36" :y="monthlyChartHeight + 16"
                                        text-anchor="middle" fill="#9CA3AF" font-size="10" font-weight="500">{{ m.month_short }}</text>
                                    <!-- Rate -->
                                    <text :x="idx * 80 + 36" :y="monthlyChartHeight + 30"
                                        text-anchor="middle" fill="#6B7280" font-size="9" font-weight="700">{{ m.conversion_rate }}%</text>
                                </g>
                            </svg>

                            <!-- Legend -->
                            <div class="flex items-center justify-center gap-6 mt-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-sm" style="background: #C4A265; opacity: 0.5;"></span>
                                    <span class="text-[10px] text-gray-500 font-semibold">{{ $t('a_total_leads') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-sm bg-green-500"></span>
                                    <span class="text-[10px] text-gray-500 font-semibold">{{ $t('a_converted') }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-16 text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">No monthly data available</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== Staff Performance (Enhanced) ====== -->
            <div
                class="relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                :style="{ transitionDelay: '940ms' }"
            >
                <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(135deg, #8B5CF6, #A78BFA);"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-gradient-to-br from-purple-500 to-purple-600">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_staff_performance') }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Response rate, follow-ups, and conversion metrics</p>
                        </div>
                    </div>

                    <div v-if="staffPerformance?.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-[10px] text-gray-400 uppercase border-b border-gray-100">
                                    <th class="ltr:text-left rtl:text-right py-3 font-semibold tracking-wider">{{ $t('a_staff') }}</th>
                                    <th class="text-center py-3 font-semibold tracking-wider">{{ $t('a_leads') }}</th>
                                    <th class="text-center py-3 font-semibold tracking-wider">{{ $t('a_response_rate') }}</th>
                                    <th class="text-center py-3 font-semibold tracking-wider">{{ $t('a_avg_response') }}</th>
                                    <th class="text-center py-3 font-semibold tracking-wider">Follow-ups</th>
                                    <th class="text-center py-3 font-semibold tracking-wider">F/U Rate</th>
                                    <th class="text-center py-3 font-semibold tracking-wider">{{ $t('a_converted') }}</th>
                                    <th class="ltr:text-right rtl:text-left py-3 font-semibold tracking-wider">Conv. Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(s, idx) in staffPerformance" :key="s.name" class="hover:bg-gray-50/60 transition-colors duration-150">
                                    <td class="py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold" style="background: linear-gradient(135deg, #8B5CF6, #A78BFA);">
                                                {{ s.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <span class="font-semibold text-gray-700 text-xs">{{ s.name }}</span>
                                                <div v-if="idx === 0" class="text-[9px] text-amber-600 font-bold">TOP PERFORMER</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 text-center text-gray-600 text-xs font-medium">{{ s.total_leads }}</td>
                                    <td class="py-3 text-center">
                                        <span
                                            class="text-xs font-bold px-2 py-0.5 rounded-full"
                                            :class="s.response_rate >= 80 ? 'text-green-700 bg-green-50' : s.response_rate >= 50 ? 'text-amber-700 bg-amber-50' : 'text-red-600 bg-red-50'"
                                        >{{ s.response_rate }}%</span>
                                    </td>
                                    <td class="py-3 text-center text-gray-500 text-xs font-mono">{{ s.avg_response_hours }}h</td>
                                    <td class="py-3 text-center">
                                        <span class="text-xs text-gray-600">{{ s.completed_follow_ups }}/{{ s.follow_up_count }}</span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span
                                            class="text-xs font-bold px-2 py-0.5 rounded-full"
                                            :class="s.follow_up_completion_rate >= 80 ? 'text-green-700 bg-green-50' : s.follow_up_completion_rate >= 50 ? 'text-amber-700 bg-amber-50' : 'text-red-600 bg-red-50'"
                                        >{{ s.follow_up_completion_rate }}%</span>
                                    </td>
                                    <td class="py-3 text-center text-green-600 font-semibold text-xs">{{ s.converted_leads }}</td>
                                    <td class="py-3 ltr:text-right rtl:text-left">
                                        <span
                                            class="text-xs font-bold px-2.5 py-1 rounded-full"
                                            :class="s.conversion_rate >= 30 ? 'text-green-700 bg-green-50' : s.conversion_rate >= 15 ? 'text-amber-700 bg-amber-50' : 'text-gray-500 bg-gray-50'"
                                        >{{ s.conversion_rate }}%</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-16 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-400 font-medium">No staff data for this period</p>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
