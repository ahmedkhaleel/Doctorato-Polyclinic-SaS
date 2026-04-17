<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    service: Object,
    stats: Object,
    monthlyData: Array,
    doctorPerformance: Array,
    doctorRates: Array,
    statusDistribution: Object,
    recentVisits: Array,
    recentBookings: Array,
    priceInfo: Object,
    filters: Object,
});

/* ── Animation ── */
const mounted = ref(false);
onMounted(() => { setTimeout(() => mounted.value = true, 50); });

/* ── Date Filter ── */
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

function applyDateFilter() {
    router.get(`/admin/services/${props.service.id}`, {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearDateFilter() {
    dateFrom.value = '';
    dateTo.value = '';
    router.get(`/admin/services/${props.service.id}`, {}, {
        preserveState: true,
        replace: true,
    });
}

const hasDateFilter = computed(() => dateFrom.value || dateTo.value);

/* ── Formatters ── */
function formatNumber(n) {
    if (!n && n !== 0) return '0';
    return Number(n).toLocaleString('en-EG');
}

/* ── Stats Cards ── */
const statCards = computed(() => [
    {
        label: 'Total Revenue',
        value: formatCurrency(props.stats?.totalRevenue),
        sub: `${formatCurrency(props.stats?.totalPaid)} collected`,
        icon: 'revenue',
        gradient: 'from-emerald-500 to-emerald-400',
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
    },
    {
        label: 'Total Visits',
        value: formatNumber(props.stats?.totalVisits),
        sub: `${props.stats?.completedVisits ?? 0} completed (${props.stats?.completionRate ?? 0}%)`,
        icon: 'visits',
        gradient: 'from-[#1B365D] to-slate-400',
        iconBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
    },
    {
        label: 'Bookings',
        value: formatNumber(props.stats?.totalBookings),
        sub: `${props.stats?.completedSessions ?? 0}/${props.stats?.totalSessions ?? 0} sessions done`,
        icon: 'bookings',
        gradient: 'from-[#C4A265] to-[#D4B87A]',
        iconBg: 'bg-[#C4A265]/10',
        iconColor: 'text-[#C4A265]',
    },
    {
        label: 'Commission Paid',
        value: formatCurrency(props.stats?.totalCommission),
        sub: `Avg ${formatCurrency(props.stats?.avgRevenuePerVisit)} / visit`,
        icon: 'commission',
        gradient: 'from-[#1B365D] to-slate-400',
        iconBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
    },
]);

/* ── Chart helpers ── */
const activeChart = ref('revenue');
const maxChartValue = computed(() => {
    if (!props.monthlyData) return 1;
    if (activeChart.value === 'revenue') {
        return Math.max(...props.monthlyData.map(m => m.revenue), 1);
    } else if (activeChart.value === 'visits') {
        return Math.max(...props.monthlyData.map(m => m.visits), 1);
    } else {
        return Math.max(...props.monthlyData.map(m => m.commission), 1);
    }
});

/* ── Status Colors ── */
const statusColorMap = {
    completed: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    in_progress: { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
    checked_in: { bg: 'bg-slate-100', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    scheduled: { bg: 'bg-slate-100', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    cancelled: { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
    no_show: { bg: 'bg-gray-100', text: 'text-gray-600', dot: 'bg-gray-400' },
    active: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    inactive: { bg: 'bg-gray-100', text: 'text-gray-600', dot: 'bg-gray-400' },
    confirmed: { bg: 'bg-slate-100', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    pending: { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
    paid: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    partial: { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
    unpaid: { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
};
function statusColor(status) {
    return statusColorMap[status] || { bg: 'bg-gray-100', text: 'text-gray-600', dot: 'bg-gray-400' };
}

/* ── Status distribution total ── */
const totalStatusCount = computed(() => {
    if (!props.statusDistribution) return 0;
    return Object.values(props.statusDistribution).reduce((s, v) => s + v, 0);
});

/* ── Active tab for tables ── */
const activeTab = ref('visits');

/* ── Pie colors ── */
const pieColors = ['#10B981', '#F59E0B', '#06B6D4', '#6366F1', '#EF4444', '#9CA3AF', '#3B82F6', '#EC4899'];
const statusLabels = {
    completed: 'Completed',
    in_progress: 'In Progress',
    checked_in: 'Checked In',
    scheduled: 'Scheduled',
    cancelled: 'Cancelled',
    no_show: 'No Show',
};
</script>

<template>
    <AdminLayout :title="$localized(service, 'name') + ' — ' + $t('a_service_details')">
        <div class="space-y-6">

            <!-- ============ HEADER + BREADCRUMB ============ -->
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'">
                <div class="flex-1">
                    <nav class="flex items-center text-sm text-gray-500 mb-3 ltr:space-x-2 rtl:space-x-reverse rtl:space-x-2">
                        <Link href="/admin" class="hover:text-[#C4A265] transition">{{ $t('a_dashboard') }}</Link>
                        <span>/</span>
                        <Link href="/admin/services" class="hover:text-[#C4A265] transition">{{ $t('a_services') }}</Link>
                        <span>/</span>
                        <span class="text-gray-800 font-medium">{{ $localized(service, 'name') }}</span>
                    </nav>
                    <div class="flex items-center gap-4">
                        <!-- Service Image or Icon -->
                        <div v-if="service.image"
                             class="w-16 h-16 rounded-2xl bg-cover bg-center border-2 border-white shadow-lg ring-2 ring-[#C4A265]/20"
                             :style="{ backgroundImage: `url(${service.image})` }">
                        </div>
                        <div v-else class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg"
                             style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $localized(service, 'name') }}</h1>
                            <p class="text-sm text-gray-500" :dir="isRtl ? 'ltr' : 'rtl'">{{ locale === 'ar' ? service.name_en : service.name_ar }}</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                      :class="`${statusColor(service.status).bg} ${statusColor(service.status).text}`">
                                    {{ service.status }}
                                </span>
                                <span v-if="service.category" class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                                    {{ $localized(service.category, 'name') }}
                                </span>
                                <span v-if="service.bookable" class="text-xs text-[#1B365D] bg-slate-50 px-2 py-0.5 rounded-full font-medium">
                                    {{ $t('a_bookable') }}
                                </span>
                                <span v-if="service.session_duration_minutes" class="text-xs text-gray-400">
                                    {{ service.session_duration_minutes }} {{ $t('a_min_per_session') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <Link v-if="can('services.update')"
                          :href="`/admin/services/${service.id}/edit`"
                          class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-medium border-2 border-[#C4A265] text-[#C4A265] hover:bg-[#C4A265] hover:text-white transition-all duration-300">
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ $t('a_edit') }}
                    </Link>
                    <Link href="/admin/services"
                          class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all duration-300">
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ $t('a_back') }}
                    </Link>
                </div>
            </div>

            <!-- ============ DATE FILTER ============ -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.05s;">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-700">{{ $t('a_filter_by_date') }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 flex-1">
                        <input type="date" v-model="dateFrom"
                               class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition" />
                        <span class="text-gray-400 text-sm">{{ $t('a_to') }}</span>
                        <input type="date" v-model="dateTo"
                               class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition" />
                        <button @click="applyDateFilter"
                                class="px-4 py-2 rounded-xl text-sm font-medium text-white transition-all duration-200 hover:shadow-md"
                                style="background-color: #C4A265;">
                            {{ $t('a_apply') }}
                        </button>
                        <button v-if="hasDateFilter" @click="clearDateFilter"
                                class="px-4 py-2 rounded-xl text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                            {{ $t('a_clear') }}
                        </button>
                    </div>
                    <div v-if="hasDateFilter" class="flex items-center gap-1.5 px-3 py-1.5 bg-[#C4A265]/10 rounded-lg">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#C4A265] animate-pulse"></div>
                        <span class="text-xs font-medium text-[#C4A265]">{{ $t('a_filtered') }}</span>
                    </div>
                </div>
            </div>

            <!-- ============ STATS CARDS ============ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 stagger-inventory"
                 :class="mounted ? '' : 'opacity-0'">
                <div v-for="(card, i) in statCards" :key="i"
                     class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden inventory-card-hover animate-inventory-card">
                    <div class="absolute top-0 left-0 right-0 h-1 rounded-t-2xl"
                         :class="`bg-gradient-to-r ${card.gradient}`"></div>
                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ card.label }}</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-900 leading-none">{{ card.value }}</p>
                            <p v-if="card.sub" class="text-xs text-gray-500 mt-1">{{ card.sub }}</p>
                        </div>
                        <div :class="[card.iconBg, 'w-11 h-11 rounded-xl flex items-center justify-center']">
                            <svg v-if="card.icon === 'revenue'" :class="[card.iconColor, 'w-5 h-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="card.icon === 'visits'" :class="[card.iconColor, 'w-5 h-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg v-else-if="card.icon === 'bookings'" :class="[card.iconColor, 'w-5 h-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <svg v-else :class="[card.iconColor, 'w-5 h-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ PRICE BREAKDOWN + SERVICE INFO ============ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.15s;">

                <!-- Price Breakdown Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_price_breakdown') }}</h3>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-gray-500">{{ $t('a_base_price') }}</span>
                            <span class="text-lg font-bold text-gray-900">{{ formatCurrency(priceInfo.price) }}</span>
                        </div>
                        <div v-if="priceInfo.price_after_discount && priceInfo.price_after_discount < priceInfo.price"
                             class="flex items-center justify-between py-2 border-t border-gray-100">
                            <span class="text-sm text-emerald-600">{{ $t('a_after_discount') }}</span>
                            <span class="text-lg font-bold text-emerald-600">{{ formatCurrency(priceInfo.price_after_discount) }}</span>
                        </div>
                        <div class="border-t border-gray-100 pt-3 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                                    <span class="text-xs text-gray-500">{{ $t('a_supply_cost') }}</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">{{ formatCurrency(priceInfo.supply_cost) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                                    <span class="text-xs text-gray-500">{{ $t('a_medical_fee') }}</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">{{ formatCurrency(priceInfo.medical_fee) }}</span>
                            </div>
                            <div v-if="priceInfo.doctor_commission_percentage" class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                                    <span class="text-xs text-gray-500">{{ $t('a_dr_commission') }}</span>
                                </div>
                                <span class="text-sm font-semibold text-[#1B365D]">{{ priceInfo.doctor_commission_percentage }}%</span>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 pt-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $t('a_profit_margin') }}</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-1000 ease-out"
                                             style="background: linear-gradient(90deg, #C4A265, #D4B87A);"
                                             :style="{ width: mounted ? `${priceInfo.profit_margin}%` : '0%' }"></div>
                                    </div>
                                    <span class="text-sm font-bold text-[#C4A265]">{{ priceInfo.profit_margin }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visit Status Distribution -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_visit_status') }}</h3>
                    </div>

                    <div v-if="statusDistribution && Object.keys(statusDistribution).length > 0" class="space-y-3">
                        <div v-for="(count, status, idx) in statusDistribution" :key="status" class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: pieColors[idx % pieColors.length] }"></div>
                                    <span class="text-xs font-medium text-gray-700 capitalize">{{ statusLabels[status] || status.replace('_', ' ') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-600">{{ count }}</span>
                                    <span class="text-xs text-gray-400">({{ totalStatusCount > 0 ? Math.round((count / totalStatusCount) * 100) : 0 }}%)</span>
                                </div>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000 ease-out"
                                     :style="{
                                         width: mounted ? `${totalStatusCount > 0 ? (count / totalStatusCount) * 100 : 0}%` : '0%',
                                         backgroundColor: pieColors[idx % pieColors.length],
                                     }"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="text-sm">{{ $t('a_no_visits_yet') }}</p>
                    </div>
                </div>

                <!-- Doctor Performance -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_doctors') }}</h3>
                    </div>

                    <div v-if="doctorPerformance && doctorPerformance.length > 0" class="space-y-3">
                        <div v-for="(doc, idx) in doctorPerformance" :key="idx"
                             class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold"
                                 :class="idx === 0 ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'bg-gray-100 text-gray-500'">
                                {{ idx + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ doc.doctor_name }}</p>
                                <p class="text-xs text-gray-400">{{ doc.visit_count }} visits</p>
                            </div>
                            <span class="text-xs font-bold text-[#1B365D]">{{ formatCurrency(doc.total_commission) }}</span>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <p class="text-sm">{{ $t('a_no_doctor_data') }}</p>
                    </div>

                    <!-- Doctor Rates Section -->
                    <div v-if="doctorRates && doctorRates.length > 0" class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $t('a_commission_rates') }}</p>
                        <div class="space-y-2">
                            <div v-for="(rate, idx) in doctorRates" :key="idx"
                                 class="flex items-center justify-between text-xs">
                                <span class="text-gray-600">{{ rate.doctor_name }}</span>
                                <span class="font-bold text-[#C4A265]">{{ rate.commission_percentage }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ MONTHLY TRENDS CHART ============ -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.2s;">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_monthly_trends') }}</h3>
                        <span class="text-xs text-gray-400">({{ $t('a_last_12_months') }})</span>
                    </div>
                    <div class="flex bg-gray-100 rounded-lg p-0.5">
                        <button @click="activeChart = 'revenue'"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200"
                                :class="activeChart === 'revenue' ? 'bg-white text-[#C4A265] shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            {{ $t('a_revenue') }}
                        </button>
                        <button @click="activeChart = 'visits'"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200"
                                :class="activeChart === 'visits' ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            {{ $t('a_visits_tab') }}
                        </button>
                        <button @click="activeChart = 'commission'"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200"
                                :class="activeChart === 'commission' ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            {{ $t('a_commission') }}
                        </button>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <div v-for="(m, idx) in monthlyData" :key="idx" class="flex items-center gap-3">
                        <span class="w-10 text-xs font-medium text-gray-500 ltr:text-right rtl:text-left">{{ m.short }}</span>
                        <div class="flex-1 h-8 bg-gray-50 rounded-lg overflow-hidden relative">
                            <!-- Revenue bar -->
                            <div v-if="activeChart === 'revenue'"
                                 class="h-full rounded-lg transition-all duration-1000 ease-out"
                                 style="background: linear-gradient(90deg, #C4A265, #D4B87A);"
                                 :style="{ width: mounted ? `${Math.max((m.revenue / maxChartValue) * 100, 2)}%` : '0%' }">
                            </div>
                            <!-- Visits bar -->
                            <div v-else-if="activeChart === 'visits'"
                                 class="h-full rounded-lg transition-all duration-1000 ease-out bg-[#1B365D]"
                                 :style="{ width: mounted ? `${Math.max((m.visits / maxChartValue) * 100, 2)}%` : '0%' }">
                            </div>
                            <!-- Commission bar -->
                            <div v-else
                                 class="h-full rounded-lg transition-all duration-1000 ease-out bg-[#1B365D]"
                                 :style="{ width: mounted ? `${Math.max((m.commission / maxChartValue) * 100, 2)}%` : '0%' }">
                            </div>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-600">
                                <template v-if="activeChart === 'revenue'">{{ formatCurrency(m.revenue) }}</template>
                                <template v-else-if="activeChart === 'visits'">{{ m.visits }} {{ $t('a_visits') }}</template>
                                <template v-else>{{ formatCurrency(m.commission) }}</template>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ VISITS & BOOKINGS TABS ============ -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.3s;">
                <div class="px-4 md:px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5 w-fit">
                        <button @click="activeTab = 'visits'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                                :class="activeTab === 'visits' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            {{ $t('a_recent_visits') }}
                            <span class="ltr:ml-1.5 rtl:mr-1.5 px-1.5 py-0.5 text-xs rounded-full"
                                  :class="activeTab === 'visits' ? 'bg-slate-100 text-[#1B365D]' : 'bg-gray-200 text-gray-500'">
                                {{ recentVisits?.length || 0 }}
                            </span>
                        </button>
                        <button @click="activeTab = 'bookings'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                                :class="activeTab === 'bookings' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                            {{ $t('a_recent_bookings') }}
                            <span class="ltr:ml-1.5 rtl:mr-1.5 px-1.5 py-0.5 text-xs rounded-full"
                                  :class="activeTab === 'bookings' ? 'bg-[#C4A265]/20 text-[#C4A265]' : 'bg-gray-200 text-gray-500'">
                                {{ recentBookings?.length || 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Visits Table -->
                <div v-if="activeTab === 'visits'" class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_patient') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_doctor') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('a_session') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('a_time') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('a_invoice_total') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('a_commission') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="visit in recentVisits" :key="visit.id"
                                class="hover:bg-[#C4A265]/[0.02] transition-colors duration-150">
                                <td class="px-4 md:px-6 py-3.5">
                                    <div class="text-sm font-medium text-gray-800">{{ visit.visit_date }}</div>
                                </td>
                                <td class="px-4 md:px-6 py-3.5">
                                    <div class="text-sm font-semibold text-gray-800">{{ visit.patient_name || '-' }}</div>
                                    <div v-if="visit.patient_phone" class="text-xs text-gray-400">{{ visit.patient_phone }}</div>
                                </td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-gray-600">{{ visit.doctor_name || '-' }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-center">
                                    <span v-if="visit.session_number" class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[#C4A265]/10 text-[#C4A265] text-xs font-bold">
                                        {{ visit.session_number }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-4 md:px-6 py-3.5 text-center">
                                    <div v-if="visit.started_at" class="text-xs text-gray-500">
                                        {{ visit.started_at }}
                                        <span v-if="visit.completed_at"> - {{ visit.completed_at }}</span>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-4 md:px-6 py-3.5 text-center">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full capitalize"
                                          :class="`${statusColor(visit.status).bg} ${statusColor(visit.status).text}`">
                                        {{ visit.status?.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left">
                                    <div v-if="visit.invoice_total !== null">
                                        <span class="text-sm font-semibold text-gray-800">{{ formatCurrency(visit.invoice_total) }}</span>
                                        <div>
                                            <span class="text-xs px-1.5 py-0.5 rounded-full capitalize"
                                                  :class="`${statusColor(visit.invoice_status).bg} ${statusColor(visit.invoice_status).text}`">
                                                {{ visit.invoice_status }}
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">{{ $t('a_no_invoice') }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-sm font-medium text-[#1B365D]">
                                    {{ visit.commission_amount ? formatCurrency(visit.commission_amount) : '-' }}
                                </td>
                            </tr>
                            <tr v-if="!recentVisits || recentVisits.length === 0">
                                <td colspan="8" class="px-4 md:px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_visits_found') }}</p>
                                        <p v-if="hasDateFilter" class="text-xs text-gray-400">{{ $t('a_try_adjusting_date_filter') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Bookings Table -->
                <div v-if="activeTab === 'bookings'" class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_booking_number') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_patient') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_doctor') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('a_sessions') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('a_unit_price') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('a_total') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="booking in recentBookings" :key="booking.id"
                                class="hover:bg-[#C4A265]/[0.02] transition-colors duration-150">
                                <td class="px-4 md:px-6 py-3.5 text-sm font-mono text-[#C4A265] font-medium">{{ booking.booking_number || '-' }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm font-semibold text-gray-800">{{ booking.patient_name || '-' }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-gray-600">{{ booking.doctor_name || '-' }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-sm font-bold text-emerald-600">{{ booking.completed_sessions }}</span>
                                        <span class="text-xs text-gray-400">/</span>
                                        <span class="text-sm text-gray-600">{{ booking.sessions_count }}</span>
                                    </div>
                                    <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden mx-auto mt-1">
                                        <div class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                             :style="{ width: `${booking.sessions_count > 0 ? (booking.completed_sessions / booking.sessions_count) * 100 : 0}%` }"></div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-sm text-gray-600">{{ formatCurrency(booking.unit_price) }}</td>
                                <td class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-sm font-bold text-gray-800">{{ formatCurrency(booking.total_price) }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-center">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full capitalize"
                                          :class="`${statusColor(booking.status).bg} ${statusColor(booking.status).text}`">
                                        {{ booking.status?.replace('_', ' ') || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-gray-500">{{ booking.created_at }}</td>
                            </tr>
                            <tr v-if="!recentBookings || recentBookings.length === 0">
                                <td colspan="8" class="px-4 md:px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_bookings_found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ============ SERVICE DETAILS ============ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.35s;">

                <!-- Service Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_service_information') }}</h3>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">{{ $t('a_default_sessions') }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ service.default_sessions || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">{{ $t('a_session_duration') }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ service.session_duration_minutes ? `${service.session_duration_minutes} min` : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">{{ $t('a_display_order') }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ service.display_order ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">{{ $t('a_category') }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ service.category ? $localized(service.category, 'name') : '-' }}</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $t('a_show_on_website') }}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                      :class="service.show_on_website ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'">
                                    {{ service.show_on_website ? $t('a_yes') : $t('a_no') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $t('a_show_on_home') }}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                      :class="service.show_on_home ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'">
                                    {{ service.show_on_home ? $t('a_yes') : $t('a_no') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Bookable</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                      :class="service.bookable ? 'bg-slate-100 text-[#1B365D]' : 'bg-gray-100 text-gray-500'">
                                    {{ service.bookable ? $t('a_yes') : $t('a_no') }}
                                </span>
                            </div>
                        </div>

                        <div v-if="service.clinic_notes" class="border-t border-gray-100 pt-4">
                            <p class="text-xs text-gray-400 mb-2">{{ $t('a_clinic_notes') }}</p>
                            <p class="text-sm text-gray-600 bg-gray-50 rounded-xl p-3">{{ service.clinic_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Service Descriptions -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_descriptions') }}</h3>
                    </div>

                    <div class="space-y-4">
                        <div v-if="service.short_desc_en">
                            <p class="text-xs text-gray-400 mb-1">{{ $t('a_short_desc_en') }}</p>
                            <p class="text-sm text-gray-600">{{ service.short_desc_en }}</p>
                        </div>
                        <div v-if="service.short_desc_ar">
                            <p class="text-xs text-gray-400 mb-1">{{ $t('a_short_desc_ar') }}</p>
                            <p class="text-sm text-gray-600" dir="rtl">{{ service.short_desc_ar }}</p>
                        </div>
                        <div v-if="service.benefits_en" class="border-t border-gray-100 pt-4">
                            <p class="text-xs text-gray-400 mb-1">{{ $t('a_benefits_en') }}</p>
                            <div class="text-sm text-gray-600" v-html="service.benefits_en"></div>
                        </div>
                        <div v-if="service.results_en" class="border-t border-gray-100 pt-4">
                            <p class="text-xs text-gray-400 mb-1">{{ $t('a_results_en') }}</p>
                            <div class="text-sm text-gray-600" v-html="service.results_en"></div>
                        </div>
                        <div v-if="!service.short_desc_en && !service.short_desc_ar && !service.benefits_en && !service.results_en"
                             class="flex flex-col items-center justify-center py-8 text-gray-400">
                            <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm">{{ $t('a_no_descriptions_added') }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out both;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
