<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const modules = computed(() => page.props.modules || {});

const props = defineProps({
    financial: Object,
    clinic: Object,
    alerts: Object,
    crm: Object,
    revenueTrend: Array,
    visitTrend: Array,
    todayQueue: Array,
    unpaidInvoices: Array,
    topServices: Array,
    recentBookings: Array,
    dental: Object,
    pediatric: Object,
});

/* ── Helpers ─────────────────────────────────────────────── */

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function percentChange(current, previous) {
    const cur = Number(current) || 0;
    const prev = Number(previous) || 0;
    if (prev === 0) return { value: cur > 0 ? 100 : 0, direction: cur > 0 ? 'up' : 'flat' };
    const pct = ((cur - prev) / prev) * 100;
    return {
        value: Math.abs(Math.round(pct)),
        direction: pct > 0 ? 'up' : pct < 0 ? 'down' : 'flat',
    };
}

function waitTime(createdAt) {
    if (!createdAt) return 0;
    const diff = Date.now() - new Date(createdAt).getTime();
    return Math.max(0, Math.round(diff / 60000));
}

function waitTimeColor(minutes) {
    if (minutes < 15) return 'text-emerald-600';
    if (minutes < 30) return 'text-amber-600';
    return 'text-red-600';
}

/* ── Financial KPI cards ─────────────────────────────────── */

const revenueChange = computed(() =>
    percentChange(props.financial?.revenue_this_month, props.financial?.revenue_last_month),
);

const financialCards = computed(() => [
    {
        labelKey: 'a_revenue',
        value: formatCurrency(props.financial?.revenue_this_month),
        change: revenueChange.value,
        gradient: 'from-emerald-500 to-emerald-600',
        lightBg: 'bg-emerald-50',
        iconColor: 'text-emerald-500',
        icon: 'revenue',
        href: null,
    },
    {
        labelKey: 'a_expenses',
        value: formatCurrency(props.financial?.expenses_this_month),
        change: null,
        gradient: 'from-red-400 to-red-500',
        lightBg: 'bg-red-50',
        iconColor: 'text-red-500',
        icon: 'expenses',
        href: null,
    },
    {
        labelKey: 'a_net_income',
        value: formatCurrency(props.financial?.net_income),
        change: null,
        gradient: 'from-[#1B365D] to-[#1B365D]',
        lightBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
        icon: 'net',
        href: null,
    },
    {
        labelKey: 'a_unpaid_balance',
        value: formatCurrency(props.financial?.unpaid_balance),
        change: null,
        gradient: 'from-amber-500 to-amber-600',
        lightBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        icon: 'unpaid',
        href: '/admin/invoices',
    },
]);

/* ── Revenue by Module ───────────────────────────────────── */

const revenueByModule = computed(() => {
    const data = props.financial?.revenue_by_module || {};
    const totalRevenue = props.financial?.revenue_this_month || 0;
    return Object.entries(data)
        .filter(([key]) => key !== 'unassigned')
        .map(([slug, amount]) => {
            const mod = modules.value[slug];
            return {
                slug,
                name: mod ? (isRtl.value ? mod.name_ar : mod.name_en) : slug,
                amount: formatCurrency(amount),
                rawAmount: amount,
                percent: totalRevenue > 0 ? Math.round((amount / totalRevenue) * 100) : 0,
                color: slug === 'derma' ? 'bg-[#C4A265]' : slug === 'dental' ? 'bg-[#1B365D]' : slug === 'pediatric' ? 'bg-emerald-500' : 'bg-gray-400',
            };
        });
});

/* ── Clinic KPI cards ────────────────────────────────────── */

const clinicCards = computed(() => [
    {
        labelKey: 'a_todays_visits',
        value: props.clinic?.today_visits ?? 0,
        subKey: null,
        subDynamic: true,
        gradient: 'from-[#1B365D] to-[#1B365D]',
        lightBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
        icon: 'visits',
    },
    {
        labelKey: 'a_month_visits',
        value: props.clinic?.month_visits ?? 0,
        subKey: null,
        gradient: 'from-[#1B365D] to-[#1B365D]',
        lightBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
        icon: 'month',
    },
    {
        labelKey: 'a_new_patients',
        value: props.clinic?.new_patients_month ?? 0,
        subKey: 'a_this_month',
        gradient: 'from-[#C4A265] to-[#D4B87A]',
        lightBg: 'bg-[#C4A265]/10',
        iconColor: 'text-[#C4A265]',
        icon: 'newpatients',
    },
    {
        labelKey: 'a_total_patients',
        value: props.clinic?.total_patients ?? 0,
        subKey: null,
        gradient: 'from-slate-500 to-slate-600',
        lightBg: 'bg-slate-50',
        iconColor: 'text-slate-500',
        icon: 'totalpatients',
    },
]);

/* ── Chart computeds ─────────────────────────────────────── */

const maxRevenue = computed(() => {
    if (!props.revenueTrend?.length) return 1;
    return Math.max(...props.revenueTrend.map((d) => d.value), 1);
});

const maxVisit = computed(() => {
    if (!props.visitTrend?.length) return 1;
    return Math.max(...props.visitTrend.map((d) => d.value), 1);
});

const maxServiceCount = computed(() => {
    if (!props.topServices?.length) return 1;
    return Math.max(...props.topServices.map((s) => s.visit_count), 1);
});

/* ── Alert items ─────────────────────────────────────────── */

const alertItems = computed(() => [
    {
        labelKey: 'a_pending_bookings',
        count: props.alerts?.pending_bookings ?? 0,
        href: '/admin/bookings?status=new',
        icon: 'calendar',
        color: 'text-[#1B365D]',
        bg: 'bg-slate-50',
    },
    {
        labelKey: 'a_unread_messages',
        count: props.alerts?.unread_messages ?? 0,
        href: '/admin/contact-messages',
        icon: 'mail',
        color: 'text-[#C4A265]',
        bg: 'bg-amber-50',
    },
    {
        labelKey: 'a_low_stock_items',
        count: props.alerts?.low_stock_count ?? 0,
        href: '/admin/supplies?filter=low_stock',
        icon: 'box',
        color: 'text-amber-500',
        bg: 'bg-amber-50',
        moduleKey: 'inventory',
    },
    {
        labelKey: 'a_pending_leaves',
        count: props.alerts?.pending_leaves ?? 0,
        href: '/admin/leaves?status=pending',
        icon: 'clock',
        color: 'text-[#1B365D]',
        bg: 'bg-slate-50',
        moduleKey: 'hr',
    },
].filter(a => !a.moduleKey || modules.value[a.moduleKey]?.enabled));

/* ── Status helpers ──────────────────────────────────────── */

const visitStatusStyles = {
    waiting: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500' },
    in_progress: { bg: 'bg-slate-50', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    completed: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
};

function getVisitStatusStyle(status) {
    return visitStatusStyles[status] || visitStatusStyles.waiting;
}

/* ── SVG chart bar helpers ───────────────────────────────── */

const chartPadding = { top: 30, bottom: 28, left: 10, right: 10 };
const chartWidth = 420;
const chartHeight = 200;
const barAreaWidth = computed(() => chartWidth - chartPadding.left - chartPadding.right);
const barAreaHeight = computed(() => chartHeight - chartPadding.top - chartPadding.bottom);

function barX(index, total) {
    const gap = barAreaWidth.value / total;
    return chartPadding.left + gap * index + gap * 0.15;
}

function barWidth(total) {
    const gap = barAreaWidth.value / total;
    return gap * 0.7;
}

function barHeight(value, max) {
    if (max === 0) return 0;
    return (value / max) * barAreaHeight.value;
}

function barY(value, max) {
    return chartPadding.top + barAreaHeight.value - barHeight(value, max);
}

function labelX(index, total) {
    const gap = barAreaWidth.value / total;
    return chartPadding.left + gap * index + gap / 2;
}
</script>

<template>
    <AdminLayout :title="$t('a_dashboard')">
        <div class="space-y-4 md:space-y-6 lg:space-y-8">

            <!-- ═════════ Navy Hero Header ═════════ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
                <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                    <div class="flex items-start gap-3 md:gap-4 min-w-0">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                            <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'نظرة عامة' : 'Overview' }}</span>
                            </div>
                            <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight truncate">{{ $t('a_dashboard') }}</h1>
                            <p class="text-xs md:text-sm text-white/70 mt-1 max-w-xl">{{ $t('a_welcome_back') }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-stretch md:items-center gap-2 w-full md:w-auto">
                        <Link href="/admin/bookings/create" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-3 md:px-4 py-2 md:py-2.5 flex-1 md:flex-none shadow-md hover:shadow-lg transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ $t('a_new_booking') }}
                        </Link>
                        <Link href="/admin/patients/create" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 text-white font-semibold px-3 md:px-4 py-2 md:py-2.5 transition text-sm flex-1 md:flex-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            {{ $t('a_new_patient') }}
                        </Link>
                        <Link href="/admin/visits/today-queue" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 text-white font-semibold px-3 md:px-4 py-2 md:py-2.5 transition text-sm flex-1 md:flex-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            {{ $t('a_today_queue') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Row 2: Financial KPI Cards ───────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                <component
                    :is="card.href ? Link : 'div'"
                    v-for="card in financialCards"
                    :key="card.labelKey"
                    :href="card.href || undefined"
                    class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden"
                >
                    <!-- Gradient accent top -->
                    <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80`"></div>

                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[13px] font-medium text-gray-500">{{ $t(card.labelKey) }}</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-900 mt-2">{{ card.value }}</p>
                            <!-- Percentage change (revenue only) -->
                            <div v-if="card.change" class="flex items-center gap-1 mt-1.5">
                                <svg v-if="card.change.direction === 'up'" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                <svg v-else-if="card.change.direction === 'down'" class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                <span
                                    :class="card.change.direction === 'up' ? 'text-emerald-600' : card.change.direction === 'down' ? 'text-red-600' : 'text-gray-400'"
                                    class="text-xs font-semibold"
                                >
                                    {{ card.change.value }}% {{ $t('a_vs_last_month') }}
                                </span>
                            </div>
                        </div>
                        <div :class="[card.lightBg]" class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <!-- Revenue icon -->
                            <svg v-if="card.icon === 'revenue'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            <!-- Expenses icon -->
                            <svg v-else-if="card.icon === 'expenses'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                            <!-- Net income icon -->
                            <svg v-else-if="card.icon === 'net'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <!-- Unpaid icon -->
                            <svg v-else-if="card.icon === 'unpaid'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                        </div>
                    </div>
                </component>
            </div>

            <!-- ── Revenue by Department ──────────────────────── -->
            <div v-if="revenueByModule.length > 1" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'الإيرادات حسب القسم' : 'Revenue by Department' }}</h3>
                <div class="flex items-center gap-6">
                    <div v-for="mod in revenueByModule" :key="mod.slug" class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-gray-600">{{ mod.name }}</span>
                            <span class="text-xs font-bold text-gray-800">{{ mod.amount }} ({{ mod.percent }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div :class="mod.color" class="h-2.5 rounded-full transition-all duration-500" :style="{ width: mod.percent + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Row 3: Clinic KPI Cards ──────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                <div
                    v-for="card in clinicCards"
                    :key="card.labelKey"
                    class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden"
                >
                    <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80`"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[13px] font-medium text-gray-500">{{ $t(card.labelKey) }}</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900 mt-2">{{ card.value }}</p>
                            <p v-if="card.subDynamic" class="text-xs text-gray-400 mt-1">{{ clinic?.today_waiting ?? 0 }} {{ $t('a_waiting') }} / {{ clinic?.today_completed ?? 0 }} {{ $t('a_completed') }}</p>
                            <p v-else-if="card.subKey" class="text-xs text-gray-400 mt-1">{{ $t(card.subKey) }}</p>
                        </div>
                        <div :class="[card.lightBg]" class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <!-- Today visits -->
                            <svg v-if="card.icon === 'visits'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <!-- Month visits -->
                            <svg v-else-if="card.icon === 'month'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            <!-- New patients -->
                            <svg v-else-if="card.icon === 'newpatients'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                            <!-- Total patients -->
                            <svg v-else-if="card.icon === 'totalpatients'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Row 4: SVG Bar Charts ────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">

                <!-- Revenue Trend Chart -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="mb-4">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_revenue_trend') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_last_7_days') }}</p>
                    </div>
                    <svg
                        v-if="revenueTrend && revenueTrend.length"
                        :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                        class="w-full h-auto"
                        preserveAspectRatio="xMidYMid meet"
                    >
                        <!-- Grid lines -->
                        <line
                            v-for="i in 4"
                            :key="'rg' + i"
                            :x1="chartPadding.left"
                            :x2="chartWidth - chartPadding.right"
                            :y1="chartPadding.top + (barAreaHeight * (i - 1)) / 3"
                            :y2="chartPadding.top + (barAreaHeight * (i - 1)) / 3"
                            stroke="#f3f4f6"
                            stroke-width="1"
                        />
                        <!-- Bars -->
                        <g v-for="(d, i) in revenueTrend" :key="'rb' + i">
                            <rect
                                :x="barX(i, revenueTrend.length)"
                                :y="barY(d.value, maxRevenue)"
                                :width="barWidth(revenueTrend.length)"
                                :height="barHeight(d.value, maxRevenue)"
                                rx="4"
                                fill="#C4A265"
                                class="transition-opacity duration-200 hover:opacity-75"
                                opacity="0.9"
                            />
                            <!-- Value label on top -->
                            <text
                                :x="labelX(i, revenueTrend.length)"
                                :y="barY(d.value, maxRevenue) - 6"
                                text-anchor="middle"
                                class="text-[10px] fill-gray-500 font-medium"
                            >
                                {{ d.value >= 1000 ? (d.value / 1000).toFixed(1) + 'k' : d.value }}
                            </text>
                            <!-- Day label below -->
                            <text
                                :x="labelX(i, revenueTrend.length)"
                                :y="chartHeight - 6"
                                text-anchor="middle"
                                class="text-[11px] fill-gray-400 font-medium"
                            >
                                {{ d.label }}
                            </text>
                        </g>
                    </svg>
                    <div v-else class="flex flex-col items-center justify-center py-12">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0h6m-6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m6 0v-6a2 2 0 00-2-2h-2a2 2 0 00-2 2v6" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_revenue_data') }}</p>
                    </div>
                </div>

                <!-- Visit Trend Chart -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="mb-4">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_visit_trend') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_last_7_days') }}</p>
                    </div>
                    <svg
                        v-if="visitTrend && visitTrend.length"
                        :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                        class="w-full h-auto"
                        preserveAspectRatio="xMidYMid meet"
                    >
                        <!-- Grid lines -->
                        <line
                            v-for="i in 4"
                            :key="'vg' + i"
                            :x1="chartPadding.left"
                            :x2="chartWidth - chartPadding.right"
                            :y1="chartPadding.top + (barAreaHeight * (i - 1)) / 3"
                            :y2="chartPadding.top + (barAreaHeight * (i - 1)) / 3"
                            stroke="#f3f4f6"
                            stroke-width="1"
                        />
                        <!-- Bars -->
                        <g v-for="(d, i) in visitTrend" :key="'vb' + i">
                            <rect
                                :x="barX(i, visitTrend.length)"
                                :y="barY(d.value, maxVisit)"
                                :width="barWidth(visitTrend.length)"
                                :height="barHeight(d.value, maxVisit)"
                                rx="4"
                                fill="#3B82F6"
                                class="transition-opacity duration-200 hover:opacity-75"
                                opacity="0.9"
                            />
                            <!-- Value label on top -->
                            <text
                                :x="labelX(i, visitTrend.length)"
                                :y="barY(d.value, maxVisit) - 6"
                                text-anchor="middle"
                                class="text-[10px] fill-gray-500 font-medium"
                            >
                                {{ d.value }}
                            </text>
                            <!-- Day label below -->
                            <text
                                :x="labelX(i, visitTrend.length)"
                                :y="chartHeight - 6"
                                text-anchor="middle"
                                class="text-[11px] fill-gray-400 font-medium"
                            >
                                {{ d.label }}
                            </text>
                        </g>
                    </svg>
                    <div v-else class="flex flex-col items-center justify-center py-12">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0h6m-6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m6 0v-6a2 2 0 00-2-2h-2a2 2 0 00-2 2v6" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_visit_data') }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Row 5: Today Queue + Alerts ──────────────────── -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">

                <!-- Today Queue Table (2/3) -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-3 md:px-6 py-3 md:py-5 border-b border-gray-100 flex items-center justify-between gap-2 flex-wrap">
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_todays_queue') }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_patients_waiting') }}</p>
                        </div>
                        <Link
                            href="/admin/visits/today-queue"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 transition-colors duration-200"
                        >
                            {{ $t('a_view_all') }}
                            <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px]">
                            <thead class="bg-[#1B365D]/5 text-[#1B365D]">
                                <tr>
                                    <th class="px-4 md:px-6 py-3 text-start text-[11px] font-bold uppercase tracking-wider">#</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-[11px] font-bold uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ $t('a_doctor') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ $t('a_service') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-[11px] font-bold uppercase tracking-wider">{{ $t('a_status') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-[11px] font-bold uppercase tracking-wider hidden sm:table-cell">{{ $t('a_wait_time') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(visit, index) in todayQueue"
                                    :key="visit.id"
                                    class="hover:bg-gray-50/50 transition-colors duration-150"
                                >
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-400 font-mono">{{ index + 1 }}</td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        {{ visit.patient?.name ?? '-' }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden md:table-cell">
                                        {{ $localized(visit.doctor, 'name') || '-' }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden lg:table-cell">
                                        {{ $localized(visit.service, 'name') || '-' }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[getVisitStatusStyle(visit.status).bg, getVisitStatusStyle(visit.status).text]"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                        >
                                            <span :class="getVisitStatusStyle(visit.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                            {{ visit.status === 'in_progress' ? $t('a_in_progress') : visit.status === 'waiting' ? $t('a_waiting') : visit.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                        <span :class="waitTimeColor(waitTime(visit.created_at))" class="text-sm font-semibold">
                                            {{ waitTime(visit.created_at) }} {{ $t('a_min') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!todayQueue || todayQueue.length === 0">
                                    <td colspan="6" class="px-4 md:px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-400">{{ $t('a_no_patients_queue') }}</p>
                                            <p class="text-xs text-gray-300 mt-1">{{ $t('a_visits_will_appear') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Alerts Card (1/3) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="mb-5">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_alerts') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_items_requiring_attention') }}</p>
                    </div>
                    <div class="space-y-3">
                        <Link
                            v-for="alert in alertItems"
                            :key="alert.labelKey"
                            :href="alert.href"
                            class="flex items-center gap-4 p-3.5 rounded-xl hover:bg-gray-50 transition-colors duration-200 group"
                        >
                            <div :class="[alert.bg]" class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-200">
                                <!-- Calendar -->
                                <svg v-if="alert.icon === 'calendar'" :class="alert.color" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <!-- Mail -->
                                <svg v-else-if="alert.icon === 'mail'" :class="alert.color" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                <!-- Box (inventory) -->
                                <svg v-else-if="alert.icon === 'box'" :class="alert.color" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                <!-- Clock (leaves) -->
                                <svg v-else-if="alert.icon === 'clock'" :class="alert.color" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-700">{{ $t(alert.labelKey) }}</p>
                            </div>
                            <span
                                :class="alert.count > 0 ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-400'"
                                class="text-sm font-bold px-2.5 py-1 rounded-lg"
                            >
                                {{ alert.count }}
                            </span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Row 5b: CRM Summary Widget ──────────────────── -->
            <div v-if="crm" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-3 md:px-6 py-3 md:py-5 border-b border-gray-100 flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#C4A265]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_crm_overview') }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_lead_pipeline_snapshot') }}</p>
                        </div>
                    </div>
                    <Link
                        href="/admin/crm"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 transition-colors duration-200"
                    >
                        {{ $t('a_crm_dashboard') }}
                        <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-y sm:divide-y-0 sm:divide-x rtl:sm:divide-x-reverse divide-gray-100">
                    <Link href="/admin/leads?status=new" class="group px-3 md:px-4 py-4 md:py-5 text-center hover:bg-slate-50/30 transition-colors">
                        <p class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ crm.new_leads ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1 group-hover:text-[#1B365D] transition-colors">{{ $t('a_new_leads') }}</p>
                    </Link>
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-red-600">{{ crm.hot_leads ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_hot_leads') }}</p>
                    </div>
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-gray-800">{{ crm.month_leads ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_this_month') }}</p>
                    </div>
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-emerald-600">{{ crm.month_conversions ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_conversions') }}</p>
                    </div>
                    <Link href="/admin/leads?follow_up=overdue" class="group px-3 md:px-4 py-4 md:py-5 text-center hover:bg-red-50/30 transition-colors">
                        <p class="text-xl md:text-2xl font-bold" :class="crm.overdue_follow_ups > 0 ? 'text-red-600' : 'text-gray-400'">{{ crm.overdue_follow_ups ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1 group-hover:text-red-600 transition-colors">{{ $t('a_overdue_follow_ups') }}</p>
                    </Link>
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-amber-600">{{ crm.today_follow_ups ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_today_follow_ups') }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Row 6: Unpaid Invoices + Top Services ────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">

                <!-- Unpaid Invoices Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-3 md:px-6 py-3 md:py-5 border-b border-gray-100 flex items-center justify-between gap-2 flex-wrap">
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_unpaid_invoices') }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_outstanding_balances') }}</p>
                        </div>
                        <Link
                            href="/admin/invoices?status=unpaid"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 transition-colors duration-200"
                        >
                            {{ $t('a_view_all') }}
                            <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_invoice_num') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_total') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_paid') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_balance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="invoice in unpaidInvoices"
                                    :key="invoice.id"
                                    class="hover:bg-gray-50/50 transition-colors duration-150"
                                >
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                        #{{ invoice.id }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        {{ invoice.patient?.name ?? '-' }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 ltr:text-right rtl:text-left">
                                        {{ formatCurrency(invoice.total) }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 ltr:text-right rtl:text-left">
                                        {{ formatCurrency(invoice.paid) }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600 ltr:text-right rtl:text-left">
                                        {{ formatCurrency(invoice.total - invoice.paid) }}
                                    </td>
                                </tr>
                                <tr v-if="!unpaidInvoices || unpaidInvoices.length === 0">
                                    <td colspan="5" class="px-4 md:px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mb-3">
                                                <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-400">{{ $t('a_all_invoices_paid') }}</p>
                                            <p class="text-xs text-gray-300 mt-1">{{ $t('a_no_outstanding_balances') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Services -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="mb-5">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_top_services') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_most_popular_services') }}</p>
                    </div>
                    <div v-if="topServices && topServices.length" class="space-y-4">
                        <div v-for="item in topServices" :key="item.service_id" class="group">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-gray-700">{{ $localized(item.service, 'name') || $t('a_unknown') }}</span>
                                <span class="text-sm font-bold text-gray-900">{{ item.visit_count }}</span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-[#C4A265] to-[#D4B87A] rounded-full transition-all duration-500 group-hover:opacity-80"
                                    :style="{ width: (item.visit_count / maxServiceCount) * 100 + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-12">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_service_data') }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Row 7: Dental Overview ────────────────────────── -->
            <div v-if="dental" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-3 md:px-6 py-3 md:py-5 border-b border-gray-100 flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_dental_overview') }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_dental_kpis') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/dental/chart-search"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-[#C4A265] bg-[#C4A265]/10 hover:bg-[#C4A265]/20 border border-[#C4A265]/30 transition-colors duration-200"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
                            {{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}
                        </Link>
                        <Link
                            href="/admin/dental/treatments"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-[#1B365D] bg-slate-50 hover:bg-slate-100 transition-colors duration-200"
                        >
                            {{ $t('a_view_all_treatments') }}
                            <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 divide-y sm:divide-y-0 sm:divide-x rtl:sm:divide-x-reverse divide-gray-100">
                    <!-- Revenue -->
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl font-bold text-[#1B365D]">{{ formatCurrency(dental.revenue_this_month) }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_dental_revenue_month') }}</p>
                    </div>
                    <!-- Treatments Today -->
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-gray-800">{{ dental.treatments_today ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_treatments_today') }}</p>
                    </div>
                    <!-- Completed Today -->
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-emerald-600">{{ dental.completed_today ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_completed_today') }}</p>
                    </div>
                    <!-- Active Plans -->
                    <Link href="/admin/dental/treatment-plans?status=in_progress" class="px-3 md:px-4 py-4 md:py-5 text-center hover:bg-gray-50/50 transition-colors">
                        <p class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ dental.active_plans ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_active_plans') }}</p>
                    </Link>
                    <!-- Overdue Plans -->
                    <Link href="/admin/dental/treatment-plans" class="px-3 md:px-4 py-4 md:py-5 text-center hover:bg-gray-50/50 transition-colors">
                        <p class="text-xl md:text-2xl font-bold" :class="(dental.overdue_plans ?? 0) > 0 ? 'text-red-600' : 'text-gray-400'">{{ dental.overdue_plans ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_overdue_plans') }}</p>
                    </Link>
                    <!-- Pending Lab -->
                    <Link href="/admin/dental/lab-orders" class="px-3 md:px-4 py-4 md:py-5 text-center hover:bg-gray-50/50 transition-colors">
                        <p class="text-xl md:text-2xl font-bold text-amber-600">{{ dental.pending_lab_orders ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_pending_lab') }}</p>
                    </Link>
                    <!-- Overdue Lab -->
                    <Link href="/admin/dental/lab-orders?overdue=1" class="px-3 md:px-4 py-4 md:py-5 text-center hover:bg-gray-50/50 transition-colors">
                        <p class="text-xl md:text-2xl font-bold" :class="(dental.overdue_lab_orders ?? 0) > 0 ? 'text-red-600' : 'text-gray-400'">{{ dental.overdue_lab_orders ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_overdue_lab') }}</p>
                    </Link>
                    <!-- Lab Ready -->
                    <Link href="/admin/dental/lab-orders?status=ready" class="px-3 md:px-4 py-4 md:py-5 text-center hover:bg-gray-50/50 transition-colors">
                        <p class="text-xl md:text-2xl font-bold text-emerald-600">{{ dental.lab_ready ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ $t('a_lab_ready') }}</p>
                    </Link>
                </div>
            </div>

            <!-- ── Row 8: Pediatric Overview ─────────────────────── -->
            <div v-if="pediatric" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-3 md:px-6 py-3 md:py-5 border-b border-gray-100 flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'نظرة عامة على طب الأطفال' : 'Pediatric Overview' }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'مؤشرات أداء طب الأطفال' : 'Pediatric KPIs' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/pediatric/dashboard"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors duration-200"
                        >
                            {{ isRtl ? 'لوحة التحكم' : 'Dashboard' }}
                            <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 divide-y sm:divide-y-0 sm:divide-x rtl:sm:divide-x-reverse divide-gray-100">
                    <!-- Patients -->
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-emerald-600">{{ pediatric.total_patients ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ isRtl ? 'إجمالي المرضى' : 'Total Patients' }}</p>
                    </div>
                    <!-- Visits This Month -->
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-gray-800">{{ pediatric.visits_this_month ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ isRtl ? 'زيارات الشهر' : 'Visits This Month' }}</p>
                    </div>
                    <!-- Vaccinations Due -->
                    <Link href="/admin/pediatric/vaccinations" class="px-3 md:px-4 py-4 md:py-5 text-center hover:bg-gray-50/50 transition-colors">
                        <p class="text-xl md:text-2xl font-bold text-amber-600">{{ pediatric.vaccinations_due ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ isRtl ? 'تطعيمات مستحقة' : 'Vaccinations Due' }}</p>
                    </Link>
                    <!-- Growth Alerts -->
                    <Link href="/admin/pediatric/growth" class="px-3 md:px-4 py-4 md:py-5 text-center hover:bg-gray-50/50 transition-colors">
                        <p class="text-xl md:text-2xl font-bold" :class="(pediatric.growth_alerts ?? 0) > 0 ? 'text-red-600' : 'text-gray-400'">{{ pediatric.growth_alerts ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ isRtl ? 'تنبيهات النمو' : 'Growth Alerts' }}</p>
                    </Link>
                    <!-- Revenue -->
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl font-bold text-emerald-600">{{ formatCurrency(pediatric.revenue_this_month ?? 0) }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ isRtl ? 'إيرادات الشهر' : 'Revenue This Month' }}</p>
                    </div>
                    <!-- Screening Pending -->
                    <div class="px-3 md:px-4 py-4 md:py-5 text-center">
                        <p class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ pediatric.screening_pending ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-gray-500 mt-1">{{ isRtl ? 'فحوصات معلقة' : 'Screening Pending' }}</p>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
