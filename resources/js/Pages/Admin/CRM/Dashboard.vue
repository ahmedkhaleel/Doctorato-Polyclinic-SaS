<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, useForm , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const props = defineProps({
    pipelineStats: Object,
    metrics: Object,
    leadsBySource: Array,
    recentLeads: Array,
    todayFollowUps: Array,
    overdueFollowUps: Array,
    activeCampaigns: Array,
    leadTrend: Object,
    teamPerformance: Array,
    moduleDistribution: Object,
    weeklyComparison: Object,
    slaMetrics: Object,
    staleLeads: Array,
    period: String,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);

onMounted(() => {
    setTimeout(() => {
        mounted.value = true;
    }, 50);
});

const selectedPeriod = ref(props.period);

function changePeriod(p) {
    selectedPeriod.value = p;
    router.get('/admin/crm', { period: p }, { preserveState: true, replace: true });
}

// Quick Add Lead Modal
const showQuickAdd = ref(false);
const quickAddForm = useForm({
    full_name: '',
    phone: '',
    lead_source_id: '',
    module: 'derma',
    priority: 3,
});

function submitQuickAdd() {
    quickAddForm.post('/admin/leads', {
        preserveScroll: true,
        onSuccess: () => {
            quickAddForm.reset();
            showQuickAdd.value = false;
        },
    });
}

const statusLabels = {
    new: 'New',
    contacted: 'Contacted',
    qualified: 'Qualified',
    appointment_booked: 'Appt. Booked',
    consultation_done: 'Consultation',
    negotiation: 'Negotiation',
    converted: 'Converted',
    lost: 'Lost',
};

const statusColors = {
    new: 'bg-blue-500',
    contacted: 'bg-indigo-500',
    qualified: 'bg-purple-500',
    appointment_booked: 'bg-amber-500',
    consultation_done: 'bg-teal-500',
    negotiation: 'bg-orange-500',
    converted: 'bg-green-500',
    lost: 'bg-red-500',
};

const statusGradients = {
    new: 'from-blue-500 to-blue-600',
    contacted: 'from-indigo-500 to-indigo-600',
    qualified: 'from-purple-500 to-purple-600',
    appointment_booked: 'from-amber-500 to-amber-600',
    consultation_done: 'from-teal-500 to-teal-600',
    negotiation: 'from-orange-500 to-orange-600',
    converted: 'from-green-500 to-green-600',
    lost: 'from-red-500 to-red-600',
};

const priorityLabels = { 1: 'Hot', 2: 'Warm', 3: 'Cold' };
const priorityColors = { 1: 'text-red-600 bg-red-50', 2: 'text-amber-600 bg-amber-50', 3: 'text-blue-600 bg-blue-50' };

const leadStatusColors = {
    new: 'bg-blue-100 text-blue-700',
    contacted: 'bg-indigo-100 text-indigo-700',
    qualified: 'bg-purple-100 text-purple-700',
    appointment_booked: 'bg-amber-100 text-amber-700',
    consultation_done: 'bg-teal-100 text-teal-700',
    negotiation: 'bg-orange-100 text-orange-700',
    converted: 'bg-green-100 text-green-700',
    lost: 'bg-red-100 text-red-700',
    dormant: 'bg-gray-100 text-gray-600',
};

const sourceColors = [
    'from-blue-400 to-blue-600',
    'from-emerald-400 to-emerald-600',
    'from-purple-400 to-purple-600',
    'from-amber-400 to-amber-600',
    'from-rose-400 to-rose-600',
    'from-cyan-400 to-cyan-600',
    'from-indigo-400 to-indigo-600',
    'from-orange-400 to-orange-600',
];

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function timeAgo(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

const pipelineTotal = computed(() => {
    return Object.values(props.pipelineStats || {}).reduce((a, b) => a + b, 0);
});

// Lead trend chart data (last 14 days for visual bar chart)
const trendBars = computed(() => {
    const entries = Object.entries(props.leadTrend || {});
    const last14 = entries.slice(-14);
    const maxVal = Math.max(...last14.map(([, v]) => v), 1);
    return last14.map(([date, count]) => ({
        date,
        count,
        pct: Math.round((count / maxVal) * 100),
        dayLabel: new Date(date).toLocaleDateString('en-US', { weekday: 'short' }),
        dayNum: new Date(date).getDate(),
    }));
});

// Weekly comparison deltas
function weekDelta(key) {
    const wc = props.weeklyComparison;
    if (!wc) return 0;
    const thisVal = wc.this_week?.[key] || 0;
    const lastVal = wc.last_week?.[key] || 0;
    if (lastVal === 0) return thisVal > 0 ? 100 : 0;
    return Math.round(((thisVal - lastVal) / lastVal) * 100);
}

// Module distribution
const moduleTotal = computed(() => {
    return Object.values(props.moduleDistribution || {}).reduce((a, b) => a + b, 0);
});

const sourcesTotal = computed(() => {
    return (props.leadsBySource || []).reduce((a, s) => a + (s.total || 0), 0);
});

function getInitials(name) {
    if (!name) return '??';
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

// Follow-up actions
function completeFollowUp(fuId) {
    router.post(`/admin/follow-ups/${fuId}/complete`, { result: '' }, { preserveScroll: true });
}

function missFollowUp(fuId) {
    router.post(`/admin/follow-ups/${fuId}/miss`, {}, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout title="CRM Dashboard">
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="card-entrance flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                :class="{ 'card-entrance-active': mounted }"
                :style="{ transitionDelay: '0ms' }"
            >
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center shadow-lg shadow-[#C4A265]/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" /></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_crm_dashboard') }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $t('a_crm_dashboard_subtitle') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Period Selector Pills -->
                    <div class="flex bg-gray-100 rounded-xl p-1 relative">
                        <button v-for="p in ['week', 'month', 'quarter', 'year']" :key="p"
                            @click="changePeriod(p)"
                            :class="[
                                selectedPeriod === p
                                    ? 'bg-white text-gray-800 shadow-sm ring-1 ring-gray-200/60'
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all duration-300 capitalize relative z-10"
                        >
                            <span :class="selectedPeriod === p ? 'relative' : ''">
                                <span v-if="selectedPeriod === p" class="absolute -ltr:left-1.5 rtl:right-1.5 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-[#C4A265]"></span>
                                {{ p }}
                            </span>
                        </button>
                    </div>
                    <button v-if="can('leads.create')" @click="showQuickAdd = true"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-md shadow-[#C4A265]/25 hover:shadow-lg hover:shadow-[#C4A265]/30 hover:-translate-y-0.5 active:translate-y-0"
                        style="background: linear-gradient(135deg, #C4A265, #A8893E);"
                    >
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_new_lead') }}</button>
                </div>
            </div>

            <!-- Key Metrics (clickable drill-down) -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                <!-- Total Leads -->
                <Link href="/admin/leads"
                    class="card-entrance group bg-white rounded-2xl shadow-sm hover:shadow-md p-6 border border-gray-100 transition-all duration-300 hover:-translate-y-1 cursor-pointer block"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '80ms' }"
                >
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_total_leads') }}</p>
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-md shadow-blue-200/50 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 tracking-tight">{{ metrics.total_leads }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-0.5 text-xs font-medium text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                            {{ metrics.new_leads_period }}
                        </span>
                        <span class="text-xs text-gray-400">this period</span>
                    </div>
                </Link>

                <!-- Converted -->
                <Link href="/admin/leads?status=converted"
                    class="card-entrance group bg-white rounded-2xl shadow-sm hover:shadow-md p-6 border border-gray-100 transition-all duration-300 hover:-translate-y-1 cursor-pointer block"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '160ms' }"
                >
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_converted') }}</p>
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-md shadow-emerald-200/50 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 tracking-tight">{{ metrics.converted_period }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-0.5 text-xs font-medium text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md">
                            {{ metrics.conversion_rate }}%
                        </span>
                        <span class="text-xs text-gray-400">conversion rate</span>
                    </div>
                </Link>

                <!-- Today Follow-ups -->
                <div
                    class="card-entrance group bg-white rounded-2xl shadow-sm hover:shadow-md p-6 border border-gray-100 transition-all duration-300 hover:-translate-y-1 cursor-default"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '240ms' }"
                >
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Today Follow-ups</p>
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-md shadow-amber-200/50 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 tracking-tight">{{ metrics.today_follow_ups }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span v-if="metrics.overdue_follow_ups > 0" class="inline-flex items-center gap-0.5 text-xs font-medium text-red-600 bg-red-50 px-1.5 py-0.5 rounded-md">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.27 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                            {{ metrics.overdue_follow_ups }} overdue
                        </span>
                        <span v-else class="inline-flex items-center gap-0.5 text-xs font-medium text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            All caught up
                        </span>
                    </div>
                </div>

                <!-- SLA: Avg Response Time -->
                <div
                    class="card-entrance group bg-white rounded-2xl shadow-sm hover:shadow-md p-6 border border-gray-100 transition-all duration-300 hover:-translate-y-1 cursor-default"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '320ms' }"
                >
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'سرعة الاستجابة' : 'Avg. Response' }}</p>
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-md shadow-purple-200/50 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 tracking-tight">{{ slaMetrics?.avg_response_display || '-' }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span v-if="slaMetrics?.awaiting_contact > 0" class="inline-flex items-center gap-0.5 text-xs font-medium text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md">
                            {{ slaMetrics.awaiting_contact }} {{ isRtl ? 'بانتظار التواصل' : 'awaiting contact' }}
                        </span>
                        <span v-else-if="slaMetrics?.total_contacted > 0" class="inline-flex items-center gap-0.5 text-xs font-medium text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md">
                            {{ slaMetrics.within_1h }}/{{ slaMetrics.total_contacted }} {{ isRtl ? 'خلال ساعة' : 'within 1h' }}
                        </span>
                        <span v-else class="text-xs text-gray-400">{{ isRtl ? 'لا بيانات بعد' : 'No data yet' }}</span>
                    </div>
                </div>
            </div>

            <!-- Weekly Comparison + Module Distribution -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- This Week vs Last Week Cards -->
                <div v-for="(item, idx) in [
                    { key: 'leads', label: isRtl ? 'عملاء هذا الاسبوع' : 'Leads This Week', icon: 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z', gradient: 'from-blue-500 to-blue-600', shadow: 'shadow-blue-200/50' },
                    { key: 'converted', label: isRtl ? 'تحويلات هذا الاسبوع' : 'Converted This Week', icon: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', gradient: 'from-emerald-500 to-emerald-600', shadow: 'shadow-emerald-200/50' },
                    { key: 'activities', label: isRtl ? 'انشطة هذا الاسبوع' : 'Activities This Week', icon: 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z', gradient: 'from-violet-500 to-violet-600', shadow: 'shadow-violet-200/50' },
                ]" :key="item.key"
                    class="card-entrance group bg-white rounded-2xl shadow-sm hover:shadow-md p-5 border border-gray-100 transition-all duration-300 hover:-translate-y-0.5"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: (400 + idx * 60) + 'ms' }"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300"
                            :class="[item.gradient, item.shadow]">
                            <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" /></svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider leading-tight">{{ item.label }}</span>
                    </div>
                    <div class="flex items-end justify-between">
                        <p class="text-2xl font-bold text-gray-800">{{ weeklyComparison?.this_week?.[item.key] || 0 }}</p>
                        <div class="flex items-center gap-1">
                            <span v-if="weekDelta(item.key) > 0" class="inline-flex items-center gap-0.5 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                {{ weekDelta(item.key) }}%
                            </span>
                            <span v-else-if="weekDelta(item.key) < 0" class="inline-flex items-center gap-0.5 text-xs font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-md">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                {{ Math.abs(weekDelta(item.key)) }}%
                            </span>
                            <span v-else class="text-xs font-medium text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md">--</span>
                            <span class="text-[10px] text-gray-400">vs last week</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lead Trend (14-day bar chart) + Module Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lead Trend Chart -->
                <div
                    class="card-entrance lg:col-span-2 bg-white rounded-2xl shadow-sm hover:shadow-md p-6 border border-gray-100 transition-all duration-300"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '580ms' }"
                >
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'اتجاه العملاء المحتملين' : 'Lead Trend' }}</h3>
                        </div>
                        <span class="text-xs text-gray-400">{{ isRtl ? 'اخر 14 يوم' : 'Last 14 days' }}</span>
                    </div>
                    <div class="flex items-end gap-1.5 h-36">
                        <div v-for="(bar, i) in trendBars" :key="bar.date"
                            class="flex-1 flex flex-col items-center gap-1 group relative"
                        >
                            <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-[10px] font-bold px-2 py-0.5 rounded-md opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                {{ bar.count }} leads
                            </div>
                            <div
                                class="w-full rounded-t-md bg-gradient-to-t from-[#C4A265] to-[#d4b97a] transition-all duration-700 ease-out group-hover:from-[#A8893E] group-hover:to-[#C4A265] cursor-default"
                                :style="{ height: mounted ? Math.max(bar.pct, bar.count > 0 ? 8 : 2) + '%' : '0%', transitionDelay: (i * 40) + 'ms' }"
                            ></div>
                            <span class="text-[9px] text-gray-400 font-medium hidden sm:block">{{ bar.dayNum }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-[10px] text-gray-300 font-medium">
                        <span>{{ trendBars[0]?.dayLabel }}</span>
                        <span>{{ trendBars[trendBars.length - 1]?.dayLabel }}</span>
                    </div>
                </div>

                <!-- Module Distribution -->
                <div
                    class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md p-6 border border-gray-100 transition-all duration-300"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '640ms' }"
                >
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'توزيع الاقسام' : 'Module Split' }}</h3>
                    </div>
                    <div class="space-y-5">
                        <Link v-for="(mod, key) in { derma: { label: isRtl ? 'جلدية' : 'Derma', color: 'from-teal-400 to-teal-600', bg: 'bg-teal-100', text: 'text-teal-700' }, dental: { label: isRtl ? 'اسنان' : 'Dental', color: 'from-sky-400 to-sky-600', bg: 'bg-sky-100', text: 'text-sky-700' } }" :key="key"
                            :href="`/admin/leads?module=${key}`"
                            class="block hover:bg-gray-50/50 -mx-2 px-2 py-1 rounded-lg transition-colors"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-gradient-to-br" :class="mod.color"></span>
                                    <span class="text-sm font-semibold text-gray-700">{{ mod.label }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-bold text-gray-800">{{ moduleDistribution?.[key] || 0 }}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="[mod.bg, mod.text]">
                                        {{ moduleTotal > 0 ? Math.round(((moduleDistribution?.[key] || 0) / moduleTotal) * 100) : 0 }}%
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r transition-all duration-700 ease-out"
                                    :class="mod.color"
                                    :style="{ width: mounted ? (moduleTotal > 0 ? Math.max(((moduleDistribution?.[key] || 0) / moduleTotal) * 100, (moduleDistribution?.[key] || 0) > 0 ? 5 : 0) : 0) + '%' : '0%' }"
                                ></div>
                            </div>
                        </Link>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500">{{ isRtl ? 'اجمالي في المسار' : 'Total Active' }}</span>
                        <span class="text-sm font-bold text-gray-800">{{ moduleTotal }} {{ isRtl ? 'عميل' : 'leads' }}</span>
                    </div>
                </div>
            </div>

            <!-- Pipeline + Sources -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Pipeline Overview -->
                <div
                    class="card-entrance lg:col-span-2 bg-white rounded-2xl shadow-sm hover:shadow-md p-6 border border-gray-100 transition-all duration-300"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '400ms' }"
                >
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_pipeline_overview') }}</h3>
                        </div>
                        <Link href="/admin/leads/pipeline" class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors duration-200 hover:bg-[#C4A265]/10" style="color: #C4A265;">
                            View Pipeline
                            <svg class="w-3 h-3 inline ltr:ml-0.5 rtl:mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <Link v-for="(count, status) in pipelineStats" :key="status"
                            :href="`/admin/leads?status=${status}`"
                            class="group block"
                        >
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-medium text-gray-600 group-hover:text-[#C4A265] transition-colors">{{ statusLabels[status] || status }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-800">{{ count }}</span>
                                    <span class="text-[10px] text-gray-400 font-medium">{{ pipelineTotal > 0 ? Math.round((count / pipelineTotal) * 100) : 0 }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div
                                    :class="'bg-gradient-to-r ' + (statusGradients[status] || 'from-gray-400 to-gray-500')"
                                    class="h-full rounded-full transition-all duration-700 ease-out group-hover:opacity-90"
                                    :style="{ width: pipelineTotal > 0 ? Math.max((count / pipelineTotal) * 100, count > 0 ? 6 : 0) + '%' : '0%' }"
                                ></div>
                            </div>
                        </Link>
                    </div>
                    <!-- Pipeline totals bar -->
                    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-500">{{ $t('a_total_in_pipeline') }}</span>
                        <span class="text-sm font-bold text-gray-800">{{ pipelineTotal }} leads</span>
                    </div>
                </div>

                <!-- Leads by Source -->
                <div
                    class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md p-6 border border-gray-100 transition-all duration-300"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '480ms' }"
                >
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_leads_by_source') }}</h3>
                    </div>
                    <div class="space-y-4">
                        <div v-for="(source, index) in leadsBySource" :key="source.name" class="group cursor-pointer" @click="router.get('/admin/leads', { source_id: source.id || '' })">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-3 h-3 rounded-full shadow-sm" :style="{ backgroundColor: source.color }"></div>
                                    <span class="text-sm font-medium text-gray-600 group-hover:text-[#C4A265] transition-colors">{{ source.name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-gray-800">{{ source.total }}</span>
                                    <span class="text-[10px] text-gray-400 font-medium bg-gray-50 px-1.5 py-0.5 rounded">{{ source.rate }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500 ease-out"
                                    :style="{
                                        width: sourcesTotal > 0 ? Math.max((source.total / sourcesTotal) * 100, source.total > 0 ? 4 : 0) + '%' : '0%',
                                        backgroundColor: source.color,
                                    }"
                                ></div>
                            </div>
                        </div>
                        <div v-if="!leadsBySource?.length" class="text-sm text-gray-400 text-center py-8">
                            <svg class="w-10 h-10 mx-auto text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                            No data yet
                        </div>
                    </div>
                </div>
            </div>

            <!-- Follow-ups + Recent/Overdue -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Today's Follow-ups -->
                <div
                    class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md border border-gray-100 transition-all duration-300 overflow-hidden"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '560ms' }"
                >
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-amber-50/80 to-transparent">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_todays_followups') }}</h3>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">{{ todayFollowUps?.length || 0 }}</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="fu in todayFollowUps" :key="fu.id" class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50/70 transition-colors duration-200 group">
                            <!-- Avatar initials -->
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center flex-shrink-0 shadow-sm">
                                <span class="text-xs font-bold text-white">{{ getInitials(fu.lead?.full_name) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <Link :href="`/admin/leads/${fu.lead?.id}`" class="text-sm font-semibold text-gray-800 hover:text-[#C4A265] truncate block transition-colors">
                                    {{ fu.lead?.full_name }}
                                </Link>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-400 capitalize">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                        {{ fu.type }}
                                    </span>
                                    <span class="text-[10px] text-gray-300">|</span>
                                    <span class="text-xs text-gray-400">{{ formatTime(fu.scheduled_at) }}</span>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500 hidden sm:block">{{ fu.assigned_user?.name }}</span>
                            <!-- Quick action buttons -->
                            <div v-if="can('leads.update')" class="flex items-center gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                                <button @click="completeFollowUp(fu.id)" title="Mark Complete" class="p-2 rounded-lg text-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 transition-all duration-200 hover:shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </button>
                                <button @click="missFollowUp(fu.id)" title="Mark Missed" class="p-2 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-500 transition-all duration-200 hover:shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                        <div v-if="!todayFollowUps?.length" class="px-6 py-12 text-center">
                            <div class="w-14 h-14 mx-auto rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-sm text-gray-400">No follow-ups scheduled for today</p>
                        </div>
                    </div>
                </div>

                <!-- Overdue Follow-ups -->
                <div
                    class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md border border-gray-100 transition-all duration-300 overflow-hidden"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '640ms' }"
                    v-if="overdueFollowUps?.length"
                >
                    <div class="px-6 py-4 border-b border-red-100 flex items-center justify-between bg-gradient-to-r from-red-50/80 to-transparent">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.27 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider">{{ $t('a_overdue_followups') }}</h3>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700 animate-pulse">{{ overdueFollowUps.length }}</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="fu in overdueFollowUps" :key="fu.id" class="px-6 py-4 flex items-center gap-4 hover:bg-red-50/30 transition-colors duration-200 group">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <span class="text-xs font-bold text-white">{{ getInitials(fu.lead?.full_name) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <Link :href="`/admin/leads/${fu.lead?.id}`" class="text-sm font-semibold text-gray-800 hover:text-red-600 truncate block transition-colors">
                                    {{ fu.lead?.full_name }}
                                </Link>
                                <span class="inline-flex items-center gap-1 text-xs text-red-500 mt-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Due {{ formatDate(fu.scheduled_at) }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 hidden sm:block">{{ fu.assigned_user?.name }}</span>
                            <!-- Quick action buttons -->
                            <div v-if="can('leads.update')" class="flex items-center gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                                <button @click="completeFollowUp(fu.id)" title="Mark Complete" class="p-2 rounded-lg text-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 transition-all duration-200 hover:shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </button>
                                <button @click="missFollowUp(fu.id)" title="Mark Missed" class="p-2 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-500 transition-all duration-200 hover:shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Leads (shows when no overdue) -->
                <div
                    class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md border border-gray-100 transition-all duration-300 overflow-hidden"
                    :class="{ 'card-entrance-active': mounted }"
                    :style="{ transitionDelay: '640ms' }"
                    v-if="!overdueFollowUps?.length"
                >
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/80 to-transparent">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_recent_leads') }}</h3>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <Link v-for="lead in recentLeads" :key="lead.id" :href="`/admin/leads/${lead.id}`"
                            class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50/70 transition-colors duration-200 block group"
                        >
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center flex-shrink-0 shadow-sm group-hover:shadow-md transition-shadow">
                                <span class="text-xs font-bold text-white">{{ getInitials(lead.full_name) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-[#C4A265] transition-colors">{{ lead.full_name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ lead.phone || lead.email || '-' }}</p>
                            </div>
                            <span :class="leadStatusColors[lead.status]" class="px-2.5 py-1 text-[10px] font-bold rounded-full whitespace-nowrap uppercase">
                                {{ statusLabels[lead.status] || lead.status }}
                            </span>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ timeAgo(lead.created_at) }}</span>
                        </Link>
                        <div v-if="!recentLeads?.length" class="px-6 py-12 text-center">
                            <div class="w-14 h-14 mx-auto rounded-full bg-gray-50 flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                            </div>
                            <p class="text-sm text-gray-400">{{ $t('a_no_leads_yet') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Campaigns -->
            <div
                class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md border border-gray-100 transition-all duration-300 overflow-hidden"
                :class="{ 'card-entrance-active': mounted }"
                :style="{ transitionDelay: '720ms' }"
                v-if="activeCampaigns?.length"
            >
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-[#C4A265]/5 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_active_campaigns') }}</h3>
                    </div>
                    <Link href="/admin/campaigns" class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors duration-200 hover:bg-[#C4A265]/10" style="color: #C4A265;">
                        View All
                        <svg class="w-3 h-3 inline ltr:ml-0.5 rtl:mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[11px] text-gray-400 uppercase border-b border-gray-100 bg-gray-50/40">
                                <th class="px-6 py-3.5 font-semibold tracking-wider">{{ $t('a_campaign') }}</th>
                                <th class="px-6 py-3.5 font-semibold tracking-wider">{{ $t('a_source') }}</th>
                                <th class="px-6 py-3.5 font-semibold tracking-wider text-center">{{ $t('a_leads') }}</th>
                                <th class="px-6 py-3.5 font-semibold tracking-wider text-center">{{ $t('a_conversions') }}</th>
                                <th class="px-6 py-3.5 font-semibold tracking-wider ltr:text-right rtl:text-left">{{ $t('a_budget') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(c, index) in activeCampaigns" :key="c.id" class="hover:bg-gray-50/70 transition-colors duration-150" :class="index % 2 === 1 ? 'bg-gray-50/30' : ''">
                                <td class="px-6 py-4">
                                    <Link :href="`/admin/campaigns/${c.id}`" class="font-semibold text-gray-800 hover:text-[#C4A265] transition-colors">{{ c.name }}</Link>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="c.lead_source" class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full" :style="{ backgroundColor: c.lead_source.color + '15', color: c.lead_source.color, border: '1px solid ' + c.lead_source.color + '25' }">
                                        <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: c.lead_source.color }"></span>
                                        {{ c.lead_source.name_en }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">{{ c.leads_count }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold">{{ c.conversions_count }}</span>
                                </td>
                                <td class="px-6 py-4 ltr:text-right rtl:text-left">
                                    <span class="text-sm font-medium text-gray-700">{{ formatCurrency(c.budget) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Stale Leads Alert -->
            <div
                class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md border border-amber-200 transition-all duration-300 overflow-hidden"
                :class="{ 'card-entrance-active': mounted }"
                :style="{ transitionDelay: '780ms' }"
                v-if="staleLeads?.length"
            >
                <div class="px-6 py-4 border-b border-amber-100 flex items-center justify-between bg-gradient-to-r from-amber-50/80 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.27 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-amber-700 uppercase tracking-wider">{{ isRtl ? 'عملاء بدون متابعة' : 'Stale Leads' }}</h3>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">{{ staleLeads.length }}</span>
                </div>
                <div class="divide-y divide-gray-50">
                    <Link v-for="lead in staleLeads" :key="lead.id"
                        :href="`/admin/leads/${lead.id}`"
                        class="px-6 py-3.5 flex items-center gap-4 hover:bg-amber-50/40 transition-colors duration-200 block group"
                    >
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-300 to-amber-500 flex items-center justify-center flex-shrink-0">
                            <span class="text-[10px] font-bold text-white">{{ getInitials(lead.full_name) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-amber-700 transition-colors">{{ lead.full_name }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-400">{{ lead.phone || '-' }}</span>
                                <span class="text-[10px] text-gray-300">|</span>
                                <span :class="leadStatusColors[lead.status]" class="px-1.5 py-0.5 text-[9px] font-bold rounded-full uppercase">{{ statusLabels[lead.status] || lead.status }}</span>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs font-semibold text-amber-600">{{ timeAgo(lead.updated_at) }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ lead.assigned_user?.name || (isRtl ? 'غير مخصص' : 'Unassigned') }}</p>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Team Performance Leaderboard -->
            <div
                class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md border border-gray-100 transition-all duration-300 overflow-hidden"
                :class="{ 'card-entrance-active': mounted }"
                :style="{ transitionDelay: '800ms' }"
                v-if="teamPerformance?.length"
            >
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-[#C4A265]/5 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'اداء الفريق' : 'Team Performance' }}</h3>
                    </div>
                    <Link href="/admin/crm-reports" class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors duration-200 hover:bg-[#C4A265]/10" style="color: #C4A265;">
                        {{ isRtl ? 'تقرير مفصل' : 'Full Report' }}
                        <svg class="w-3 h-3 inline ltr:ml-0.5 rtl:mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[11px] text-gray-400 uppercase border-b border-gray-100 bg-gray-50/40">
                                <th class="px-6 py-3.5 font-semibold tracking-wider">{{ isRtl ? 'الموظف' : 'Staff' }}</th>
                                <th class="px-4 py-3.5 font-semibold tracking-wider text-center">{{ isRtl ? 'نشط' : 'Active' }}</th>
                                <th class="px-4 py-3.5 font-semibold tracking-wider text-center">{{ isRtl ? 'محول' : 'Conv.' }}</th>
                                <th class="px-4 py-3.5 font-semibold tracking-wider text-center hidden sm:table-cell">{{ isRtl ? 'نسبة' : 'Rate' }}</th>
                                <th class="px-4 py-3.5 font-semibold tracking-wider text-center hidden md:table-cell">{{ isRtl ? 'انشطة' : 'Acts.' }}</th>
                                <th class="px-4 py-3.5 font-semibold tracking-wider text-center hidden md:table-cell">{{ isRtl ? 'متاخر' : 'Overdue' }}</th>
                                <th class="px-4 py-3.5 font-semibold tracking-wider text-center">{{ isRtl ? 'الفترة' : 'Period' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(member, idx) in teamPerformance" :key="member.id"
                                class="hover:bg-gray-50/70 transition-colors duration-150 cursor-pointer"
                                :class="idx % 2 === 1 ? 'bg-gray-50/30' : ''"
                                @click="router.get('/admin/leads', { assigned_to: member.id })"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center flex-shrink-0">
                                            <span class="text-[10px] font-bold text-white">{{ getInitials(member.name) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800 text-sm">{{ member.name }}</p>
                                            <p class="text-[10px] text-gray-400">{{ member.total_leads }} total</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">{{ member.active_leads }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold">{{ member.converted_leads }}</span>
                                </td>
                                <td class="px-4 py-4 text-center hidden sm:table-cell">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-16 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-500"
                                                :style="{ width: Math.min(member.conversion_rate, 100) + '%' }"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-600 w-10 text-right">{{ member.conversion_rate }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center hidden md:table-cell">
                                    <span class="text-xs font-medium text-gray-600">{{ member.period_activities }}</span>
                                </td>
                                <td class="px-4 py-4 text-center hidden md:table-cell">
                                    <span v-if="member.overdue_follow_ups > 0" class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-lg bg-red-50 text-red-600 text-xs font-bold">
                                        {{ member.overdue_follow_ups }}
                                    </span>
                                    <span v-else class="text-xs text-emerald-500 font-medium">0</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="text-xs">
                                        <span class="font-bold text-gray-700">{{ member.period_converted }}</span>
                                        <span class="text-gray-400">/{{ member.period_leads }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Quick Add Lead Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showQuickAdd" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showQuickAdd = false">
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                        <div v-if="showQuickAdd" class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" :dir="isRtl ? 'rtl' : 'ltr'">
                            <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-[#C4A265]/10 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'إضافة سريعة' : 'Quick Add Lead' }}</h3>
                                            <p class="text-xs text-gray-400">{{ isRtl ? 'أدخل البيانات الأساسية' : 'Enter basic info' }}</p>
                                        </div>
                                    </div>
                                    <button @click="showQuickAdd = false" class="text-gray-400 hover:text-gray-600 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <form @submit.prevent="submitQuickAdd" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الاسم الكامل' : 'Full Name' }} *</label>
                                        <input v-model="quickAddForm.full_name" type="text" required
                                            class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#C4A265] focus:border-[#C4A265] transition"
                                            :placeholder="isRtl ? 'أدخل اسم العميل' : 'Enter lead name'" />
                                        <p v-if="quickAddForm.errors.full_name" class="text-xs text-red-500 mt-1">{{ quickAddForm.errors.full_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'رقم الهاتف' : 'Phone' }} *</label>
                                        <input v-model="quickAddForm.phone" type="tel" required dir="ltr"
                                            class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#C4A265] focus:border-[#C4A265] transition"
                                            placeholder="05xxxxxxxx" />
                                        <p v-if="quickAddForm.errors.phone" class="text-xs text-red-500 mt-1">{{ quickAddForm.errors.phone }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'المصدر' : 'Source' }}</label>
                                            <select v-model="quickAddForm.lead_source_id"
                                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#C4A265] focus:border-[#C4A265] transition">
                                                <option value="">{{ isRtl ? 'اختر المصدر' : 'Select source' }}</option>
                                                <option v-for="src in leadsBySource" :key="src.id" :value="src.id">{{ src.name }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'القسم' : 'Module' }}</label>
                                            <select v-model="quickAddForm.module"
                                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-[#C4A265] focus:border-[#C4A265] transition">
                                                <option value="derma">Derma</option>
                                                <option value="dental">Dental</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الأولوية' : 'Priority' }}</label>
                                        <div class="flex gap-2">
                                            <button v-for="p in [{v:1,l:'Hot',c:'border-red-300 bg-red-50 text-red-700'},{v:2,l:'Warm',c:'border-amber-300 bg-amber-50 text-amber-700'},{v:3,l:'Cold',c:'border-blue-300 bg-blue-50 text-blue-700'}]"
                                                :key="p.v" type="button" @click="quickAddForm.priority = p.v"
                                                class="flex-1 py-2 text-xs font-semibold rounded-lg border transition-all"
                                                :class="quickAddForm.priority === p.v ? p.c + ' ring-2 ring-offset-1 ring-[#C4A265]/40' : 'border-gray-200 text-gray-500 hover:bg-gray-50'">
                                                {{ p.l }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between pt-2">
                                        <Link href="/admin/leads/create" class="text-xs text-[#C4A265] hover:underline font-medium">
                                            {{ isRtl ? 'النموذج الكامل' : 'Full form' }}
                                            <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'"/></svg>
                                        </Link>
                                        <button type="submit" :disabled="quickAddForm.processing"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50"
                                            style="background: linear-gradient(135deg, #C4A265, #A8893E);">
                                            <svg v-if="quickAddForm.processing" class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            {{ quickAddForm.processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save Lead') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.card-entrance {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.5s ease-out, transform 0.5s ease-out, box-shadow 0.3s ease;
}

.card-entrance-active {
    opacity: 1;
    transform: translateY(0);
}
</style>
