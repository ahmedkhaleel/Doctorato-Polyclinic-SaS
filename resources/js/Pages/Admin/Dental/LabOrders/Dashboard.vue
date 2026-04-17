<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { t } = useLocale();
const { formatCurrency } = useCurrency();

const props = defineProps({
    pipeline: Object,
    stats: Object,
    overdueOrders: Array,
    upcomingDeliveries: Array,
    labPerformance: Array,
    itemTypeBreakdown: Array,
    monthlyTrend: Array,
    recentOrders: Array,
});

const activeSection = ref('overview');

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function daysUntil(date) {
    if (!date) return null;
    const diff = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24));
    return diff;
}

function daysOverdue(date) {
    if (!date) return 0;
    const diff = Math.ceil((new Date() - new Date(date)) / (1000 * 60 * 60 * 24));
    return Math.max(0, diff);
}

const pipelineTotal = computed(() => Object.values(props.pipeline).reduce((a, b) => a + b, 0));

const pipelineStages = computed(() => [
    { key: 'ordered', label: t('a_lab_ordered'), color: 'bg-slate-400', lightBg: 'bg-slate-50', text: 'text-slate-700' },
    { key: 'in_production', label: t('a_lab_in_production'), color: 'bg-[#1B365D]', lightBg: 'bg-slate-50', text: 'text-[#1B365D]' },
    { key: 'ready', label: t('a_lab_ready'), color: 'bg-emerald-500', lightBg: 'bg-emerald-50', text: 'text-emerald-700' },
    { key: 'delivered', label: t('a_lab_delivered'), color: 'bg-[#1B365D]', lightBg: 'bg-slate-50', text: 'text-[#1B365D]' },
    { key: 'adjustment', label: t('a_lab_adjustment'), color: 'bg-[#C4A265]', lightBg: 'bg-[#F5E7C8]/40', text: 'text-[#8B7043]' },
    { key: 'completed', label: t('a_lab_completed'), color: 'bg-emerald-500', lightBg: 'bg-emerald-50', text: 'text-emerald-700' },
]);

const deliveryGrowth = computed(() => {
    if (!props.stats.last_month_delivered) return null;
    const pct = ((props.stats.this_month_delivered - props.stats.last_month_delivered) / props.stats.last_month_delivered * 100);
    return pct.toFixed(0);
});

const costGrowth = computed(() => {
    if (!props.stats.last_month_cost) return null;
    const pct = ((props.stats.this_month_cost - props.stats.last_month_cost) / props.stats.last_month_cost * 100);
    return pct.toFixed(0);
});

const maxLabOrders = computed(() => {
    if (!props.labPerformance?.length) return 1;
    return Math.max(...props.labPerformance.map(l => l.total_orders));
});

const monthLabels = computed(() => {
    return props.monthlyTrend?.map(m => {
        const [y, mo] = m.month.split('-');
        const date = new Date(y, mo - 1);
        return date.toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US', { month: 'short' });
    }) ?? [];
});

const maxMonthlyOrders = computed(() => {
    if (!props.monthlyTrend?.length) return 1;
    return Math.max(...props.monthlyTrend.map(m => m.ordered));
});

const labStatusColors = {
    ordered: 'bg-gray-100 text-gray-700',
    in_production: 'bg-slate-100 text-[#1B365D]',
    ready: 'bg-emerald-100 text-emerald-700',
    delivered: 'bg-slate-100 text-[#1B365D]',
    adjustment: 'bg-[#F5E7C8]/60 text-[#8B7043]',
    completed: 'bg-emerald-100 text-emerald-700',
};

const itemTypeLabels = {
    crown: 'Crown',
    bridge: 'Bridge',
    denture: 'Denture',
    retainer: 'Retainer',
    aligner: 'Aligner',
    veneer: 'Veneer',
    implant_abutment: 'Implant',
    night_guard: 'Night Guard',
    inlay_onlay: 'Inlay/Onlay',
};
</script>

<template>
    <AdminLayout :title="$t('a_lab_dashboard')">
        <div class="lab-dashboard space-y-6">
            <!-- ═══ HEADER ═══ -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-7">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-[#2C4E7A]/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-emerald-300/15 rounded-full blur-3xl"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">{{ $t('a_lab_dashboard') }}</h1>
                            <p class="text-slate-100/80 text-sm mt-0.5">{{ $t('a_lab_dashboard_desc') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link href="/admin/dental/lab-orders/profitability" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            {{ $t('a_profitability') || 'تحليل الربحية' }}
                        </Link>
                        <Link href="/admin/dental/lab-orders" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200">
                            {{ $t('a_view_all_orders') }}
                        </Link>
                        <Link href="/admin/dental/lab-orders/create" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-[#C4A265] hover:bg-[#D4B57E] shadow-lg hover:shadow-xl transition-all duration-300 backdrop-blur-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ $t('a_new_lab_order') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ═══ KPI CARDS ═══ -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="dental-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden" style="animation-delay:0.1s">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1B365D] to-[#1B365D] opacity-80"></div>
                    <div class="text-2xl font-bold text-[#1B365D] tabular-nums">{{ stats.total_active }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">{{ $t('a_active_orders') }}</div>
                </div>
                <div class="dental-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border transition-all duration-300 overflow-hidden" :class="stats.overdue > 0 ? 'border-red-200/60' : 'border-gray-100/80'" style="animation-delay:0.15s">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-500 to-red-600 opacity-80"></div>
                    <div class="text-2xl font-bold tabular-nums" :class="stats.overdue > 0 ? 'text-red-600' : 'text-emerald-600'">{{ stats.overdue }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">{{ $t('a_overdue') }}</div>
                    <span v-if="stats.overdue > 0" class="absolute top-3 ltr:right-3 rtl:left-3 w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                </div>
                <div class="dental-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden" style="animation-delay:0.2s">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1B365D] to-[#1B365D] opacity-80"></div>
                    <div class="text-2xl font-bold text-[#1B365D] tabular-nums">{{ stats.this_month_ordered }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">{{ $t('a_ordered_this_month') }}</div>
                </div>
                <div class="dental-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden" style="animation-delay:0.25s">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-emerald-600 opacity-80"></div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-emerald-600 tabular-nums">{{ stats.this_month_delivered }}</span>
                        <span v-if="deliveryGrowth !== null" class="text-xs font-semibold" :class="deliveryGrowth >= 0 ? 'text-emerald-500' : 'text-red-500'">
                            {{ deliveryGrowth >= 0 ? '↑' : '↓' }}{{ Math.abs(deliveryGrowth) }}%
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">{{ $t('a_delivered_this_month') }}</div>
                </div>
                <div class="dental-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden" style="animation-delay:0.3s">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#1B365D] to-[#1B365D] opacity-80"></div>
                    <div class="text-2xl font-bold text-[#1B365D] tabular-nums">{{ stats.avg_delivery_days }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">{{ $t('a_avg_delivery_days') }}</div>
                </div>
                <div class="dental-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden" style="animation-delay:0.35s">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-amber-600 opacity-80"></div>
                    <div class="text-2xl font-bold text-amber-600 tabular-nums">{{ formatCurrency(stats.this_month_profit) }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium">{{ $t('a_monthly_profit') }}</div>
                </div>
            </div>

            <!-- ═══ PIPELINE VISUALIZATION ═══ -->
            <div class="dental-card-enter pipeline-section" style="animation-delay:0.4s">
                <h2 class="section-title">{{ $t('a_order_pipeline') }}</h2>
                <div class="pipeline-bar">
                    <template v-for="stage in pipelineStages" :key="stage.key">
                        <div
                            v-if="pipeline[stage.key] > 0"
                            class="pipeline-bar__segment"
                            :class="stage.color"
                            :style="{ width: (pipeline[stage.key] / pipelineTotal * 100) + '%' }"
                            :title="`${stage.label}: ${pipeline[stage.key]}`"
                        >
                            <span v-if="pipeline[stage.key] / pipelineTotal > 0.08" class="text-white text-xs font-bold">
                                {{ pipeline[stage.key] }}
                            </span>
                        </div>
                    </template>
                </div>
                <div class="pipeline-legend">
                    <div v-for="stage in pipelineStages" :key="stage.key" class="pipeline-legend__item">
                        <div class="pipeline-legend__dot" :class="stage.color"></div>
                        <span class="pipeline-legend__label">{{ stage.label }}</span>
                        <span class="pipeline-legend__count">{{ pipeline[stage.key] || 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- ═══ TWO COLUMN: OVERDUE + UPCOMING ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Overdue Orders -->
                <div class="content-card" :class="{ 'content-card--danger': overdueOrders.length > 0 }">
                    <div class="content-card__header">
                        <h3 class="content-card__title">
                            <svg class="w-4.5 h-4.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                            {{ $t('a_overdue_orders') }}
                            <span v-if="overdueOrders.length" class="content-card__badge bg-red-100 text-red-700">{{ overdueOrders.length }}</span>
                        </h3>
                    </div>
                    <div v-if="overdueOrders.length === 0" class="content-card__empty">
                        <svg class="w-10 h-10 text-emerald-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p>{{ $t('a_no_overdue_orders') }}</p>
                    </div>
                    <div v-else class="content-card__list">
                        <div v-for="order in overdueOrders" :key="order.id" class="content-card__row content-card__row--danger">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm text-gray-900 truncate">
                                    {{ order.patient?.full_name || '-' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ itemTypeLabels[order.item_type] || order.item_type }} &middot; {{ order.tooth_number || '-' }} &middot; {{ order.lab_name || '-' }}
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-bold text-red-600">{{ daysOverdue(order.expected_date) }} {{ $t('a_days_late') }}</div>
                                <div class="text-xs text-gray-400">{{ formatDate(order.expected_date) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Deliveries -->
                <div class="content-card">
                    <div class="content-card__header">
                        <h3 class="content-card__title">
                            <svg class="w-4.5 h-4.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ $t('a_upcoming_deliveries') }}
                            <span v-if="upcomingDeliveries.length" class="content-card__badge bg-slate-100 text-[#1B365D]">{{ upcomingDeliveries.length }}</span>
                        </h3>
                    </div>
                    <div v-if="upcomingDeliveries.length === 0" class="content-card__empty">
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                        <p>{{ $t('a_no_upcoming_deliveries') }}</p>
                    </div>
                    <div v-else class="content-card__list">
                        <div v-for="order in upcomingDeliveries" :key="order.id" class="content-card__row">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm text-gray-900 truncate">
                                    {{ order.patient?.full_name || '-' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ itemTypeLabels[order.item_type] || order.item_type }} &middot; {{ order.tooth_number || '-' }} &middot; {{ order.lab_name || '-' }}
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-semibold" :class="daysUntil(order.expected_date) <= 1 ? 'text-[#C4A265]' : 'text-[#1B365D]'">
                                    <template v-if="daysUntil(order.expected_date) === 0">{{ $t('a_today') }}</template>
                                    <template v-else-if="daysUntil(order.expected_date) === 1">{{ $t('a_tomorrow') }}</template>
                                    <template v-else>{{ daysUntil(order.expected_date) }} {{ $t('a_days') }}</template>
                                </div>
                                <div class="text-xs text-gray-400">{{ formatDate(order.expected_date) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ LAB PERFORMANCE ═══ -->
            <div class="dental-card-enter content-card" v-if="labPerformance.length > 0" style="animation-delay:0.55s">
                <div class="content-card__header">
                    <h3 class="content-card__title">
                        <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        {{ $t('a_lab_performance') }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">{{ $t('a_lab_name') }}</th>
                                <th class="text-center py-3 px-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('a_total_orders_label') }}</th>
                                <th class="text-center py-3 px-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('a_delivered_count') }}</th>
                                <th class="text-center py-3 px-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('a_overdue') }}</th>
                                <th class="text-center py-3 px-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('a_avg_days') }}</th>
                                <th class="text-right py-3 px-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('a_cost') }}</th>
                                <th class="text-right py-3 px-3 text-xs font-semibold text-gray-500 uppercase">{{ $t('a_profit') }}</th>
                                <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase w-32">{{ $t('a_delivery_rate') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="lab in labPerformance" :key="lab.lab_name" class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="py-3 px-4 font-medium text-gray-900">{{ lab.lab_name }}</td>
                                <td class="py-3 px-3 text-center">{{ lab.total_orders }}</td>
                                <td class="py-3 px-3 text-center text-emerald-600 font-medium">{{ lab.delivered_count }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span :class="lab.overdue_count > 0 ? 'text-red-600 font-bold' : 'text-gray-400'">{{ lab.overdue_count }}</span>
                                </td>
                                <td class="py-3 px-3 text-center">{{ lab.avg_days ? Number(lab.avg_days).toFixed(1) : '-' }}</td>
                                <td class="py-3 px-3 text-right text-gray-600">{{ formatCurrency(lab.total_cost) }}</td>
                                <td class="py-3 px-3 text-right font-medium" :class="(lab.total_charge - lab.total_cost) > 0 ? 'text-emerald-600' : 'text-red-500'">
                                    {{ formatCurrency(lab.total_charge - lab.total_cost) }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="lab.total_orders > 0 && (lab.delivered_count / lab.total_orders * 100) >= 80 ? 'bg-emerald-500' : (lab.delivered_count / lab.total_orders * 100) >= 50 ? 'bg-amber-500' : 'bg-red-400'"
                                                :style="{ width: (lab.total_orders > 0 ? lab.delivered_count / lab.total_orders * 100 : 0) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-medium text-gray-600 w-10 text-right">
                                            {{ lab.total_orders > 0 ? Math.round(lab.delivered_count / lab.total_orders * 100) : 0 }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══ TWO COLUMN: ITEM TYPES + MONTHLY TREND ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Item Type Breakdown -->
                <div class="content-card" v-if="itemTypeBreakdown.length > 0">
                    <div class="content-card__header">
                        <h3 class="content-card__title">
                            <svg class="w-4.5 h-4.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            {{ $t('a_item_type_breakdown') }}
                        </h3>
                        <span class="text-xs text-gray-400">{{ $t('a_this_month') }}</span>
                    </div>
                    <div class="space-y-3 p-4 pt-0">
                        <div v-for="item in itemTypeBreakdown" :key="item.item_type" class="flex items-center gap-3">
                            <div class="w-28 text-sm font-medium text-gray-700 truncate">
                                {{ itemTypeLabels[item.item_type] || item.item_type }}
                            </div>
                            <div class="flex-1 h-7 bg-gray-100 rounded-lg overflow-hidden relative">
                                <div
                                    class="h-full bg-gradient-to-r from-[#1B365D] to-[#2C4E7A] rounded-lg flex items-center justify-end px-2"
                                    :style="{ width: Math.max(15, (item.count / (itemTypeBreakdown[0]?.count || 1) * 100)) + '%' }"
                                >
                                    <span class="text-xs font-bold text-white">{{ item.count }}</span>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 w-20 text-right">{{ formatCurrency(item.total_cost) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend -->
                <div class="content-card" v-if="monthlyTrend.length > 0">
                    <div class="content-card__header">
                        <h3 class="content-card__title">
                            <svg class="w-4.5 h-4.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            {{ $t('a_monthly_trend') }}
                        </h3>
                        <span class="text-xs text-gray-400">{{ $t('a_last_6_months') }}</span>
                    </div>
                    <div class="p-4 pt-0">
                        <!-- Simple bar chart -->
                        <div class="flex items-end gap-2 h-40">
                            <div v-for="(m, idx) in monthlyTrend" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
                                <div class="text-xs font-bold text-gray-600">{{ m.ordered }}</div>
                                <div class="w-full flex gap-0.5" style="align-items: flex-end; height: 100px;">
                                    <div
                                        class="flex-1 bg-[#2C4E7A] rounded-t-sm transition-all"
                                        :style="{ height: Math.max(4, m.ordered / maxMonthlyOrders * 100) + 'px' }"
                                        :title="`Ordered: ${m.ordered}`"
                                    ></div>
                                    <div
                                        class="flex-1 bg-emerald-400 rounded-t-sm transition-all"
                                        :style="{ height: Math.max(4, m.delivered / maxMonthlyOrders * 100) + 'px' }"
                                        :title="`Delivered: ${m.delivered}`"
                                    ></div>
                                </div>
                                <div class="text-[10px] text-gray-500 font-medium">{{ monthLabels[idx] }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-center gap-6 mt-4">
                            <div class="flex items-center gap-1.5">
                                <div class="w-3 h-3 rounded-sm bg-[#2C4E7A]"></div>
                                <span class="text-xs text-gray-500">{{ $t('a_lab_ordered') }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-3 h-3 rounded-sm bg-emerald-400"></div>
                                <span class="text-xs text-gray-500">{{ $t('a_lab_delivered') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ RECENT ACTIVITY ═══ -->
            <div class="content-card">
                <div class="content-card__header">
                    <h3 class="content-card__title">
                        <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ $t('a_recent_activity') }}
                    </h3>
                    <Link href="/admin/dental/lab-orders" class="text-xs text-[#1B365D] hover:text-[#1B365D] font-medium">
                        {{ $t('a_view_all') }} →
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-2.5 px-4 text-xs font-semibold text-gray-500">{{ $t('a_patient') }}</th>
                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500">{{ $t('a_item') }}</th>
                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500">{{ $t('a_lab') }}</th>
                                <th class="text-center py-2.5 px-3 text-xs font-semibold text-gray-500">{{ $t('a_status') }}</th>
                                <th class="text-left py-2.5 px-3 text-xs font-semibold text-gray-500">{{ $t('a_doctor') }}</th>
                                <th class="text-right py-2.5 px-4 text-xs font-semibold text-gray-500">{{ $t('a_cost') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in recentOrders" :key="order.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="py-2.5 px-4">
                                    <div class="font-medium text-gray-900">{{ order.patient?.full_name || '-' }}</div>
                                    <div class="text-xs text-gray-400">{{ order.patient?.file_number || '' }}</div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="text-sm">{{ itemTypeLabels[order.item_type] || order.item_type }}</span>
                                    <span v-if="order.tooth_number" class="text-xs text-gray-400 block">#{{ order.tooth_number }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-gray-600">{{ order.lab_name || '-' }}</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full" :class="labStatusColors[order.status] || 'bg-gray-100 text-gray-600'">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-gray-600 text-sm">
                                    {{ locale === 'ar' ? order.doctor?.name_ar : order.doctor?.name_en }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-medium text-gray-700">{{ formatCurrency(order.cost) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.lab-dashboard {
    max-width: 1400px;
}

/* ── Animations ─── */
@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.dental-hero-enter {
    animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.dental-card-enter {
    animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* ── Pipeline ─── */
.pipeline-section {
    background: #fff;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.section-title {
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
}
.pipeline-bar {
    display: flex;
    height: 32px;
    border-radius: 8px;
    overflow: hidden;
    gap: 2px;
    margin-bottom: 1rem;
}
.pipeline-bar__segment {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    transition: all 0.3s;
    border-radius: 4px;
}
.pipeline-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.5rem;
}
.pipeline-legend__item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
}
.pipeline-legend__dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}
.pipeline-legend__label {
    color: #6b7280;
}
.pipeline-legend__count {
    font-weight: 700;
    color: #374151;
}

/* ── Content Cards ─── */
.content-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
}
.content-card--danger {
    border-color: rgba(239,68,68,0.15);
}
.content-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(0,0,0,0.04);
}
.content-card__title {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.content-card__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 999px;
    font-size: 0.6875rem;
    font-weight: 700;
}
.content-card__empty {
    padding: 2.5rem 1.5rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.875rem;
}
.content-card__list {
    max-height: 360px;
    overflow-y: auto;
}
.content-card__row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid rgba(0,0,0,0.03);
    transition: background 0.15s;
}
.content-card__row:hover { background: #f9fafb; }
.content-card__row--danger {
    border-left: 3px solid #ef4444;
}
.content-card__row--danger:hover { background: #fef2f2; }

@media (max-width: 768px) {
    .lab-header__content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    .kpi-card__value { font-size: 1.375rem; }
    .pipeline-legend { gap: 0.5rem 1rem; }
}
</style>
