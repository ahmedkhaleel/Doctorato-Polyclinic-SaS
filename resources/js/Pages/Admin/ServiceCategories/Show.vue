<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link , usePage} from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const { can } = usePermissions();

const props = defineProps({
    category: Object,
    services: Array,
    stats: Object,
    monthlyRevenue: Array,
    monthlyVisits: Array,
    topByRevenue: Array,
    topByVisits: Array,
    recentVisits: Array,
    revenueShare: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

/* ── Animation ── */
const mounted = ref(false);
onMounted(() => { setTimeout(() => mounted.value = true, 50); });

/* ── Formatters ── */
function formatNumber(n) {
    if (!n && n !== 0) return '0';
    return Number(n).toLocaleString('en-EG');
}

/* ── Stats Cards ── */
const statCards = computed(() => [
    {
        label: 'Total Services',
        value: props.stats?.totalServices ?? 0,
        sub: `${props.stats?.activeServices ?? 0} active`,
        icon: 'services',
        gradient: 'from-[#C4A265] to-[#D4B87A]',
        iconBg: 'bg-[#C4A265]/10',
        iconColor: 'text-[#C4A265]',
    },
    {
        label: 'Total Revenue',
        value: formatCurrency(props.stats?.totalRevenue),
        sub: null,
        icon: 'revenue',
        gradient: 'from-emerald-500 to-emerald-400',
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
    },
    {
        label: 'Total Visits',
        value: formatNumber(props.stats?.totalVisits),
        sub: `${formatNumber(props.stats?.totalBookings)} bookings`,
        icon: 'visits',
        gradient: 'from-[#1B365D] to-slate-400',
        iconBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
    },
    {
        label: 'Avg. Price',
        value: formatCurrency(props.stats?.avgPrice),
        sub: null,
        icon: 'price',
        gradient: 'from-[#1B365D] to-slate-400',
        iconBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
    },
]);

/* ── Chart helpers ── */
const maxRevenue = computed(() => {
    const vals = (props.monthlyRevenue || []).map(m => m.revenue);
    return Math.max(...vals, 1);
});
const maxVisits = computed(() => {
    const vals = (props.monthlyVisits || []).map(m => m.count);
    return Math.max(...vals, 1);
});
const totalRevenueShare = computed(() => (props.revenueShare || []).reduce((s, r) => s + r.revenue, 0));

/* ── Tab for charts ── */
const activeChart = ref('revenue');

/* ── Status colors ── */
function statusColor(status) {
    const map = {
        active: 'bg-emerald-100 text-emerald-700',
        inactive: 'bg-gray-100 text-gray-600',
        completed: 'bg-slate-100 text-[#1B365D]',
        in_progress: 'bg-amber-100 text-amber-700',
        checked_in: 'bg-slate-100 text-[#1B365D]',
        cancelled: 'bg-red-100 text-red-700',
        scheduled: 'bg-slate-100 text-[#1B365D]',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
}

/* ── Pie colors ── */
const pieColors = ['#C4A265', '#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444', '#EC4899', '#14B8A6'];
</script>

<template>
    <AdminLayout :title="$localized(category, 'name') + ' — ' + $t('a_category_details')">
        <div class="space-y-6">

            <!-- ═══ BREADCRUMB + HEADER ═══ -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'">
                <div>
                    <nav class="flex items-center text-sm text-gray-500 mb-2 ltr:space-x-2 rtl:space-x-reverse rtl:space-x-2">
                        <Link href="/admin" class="hover:text-[#C4A265] transition">{{ $t('a_dashboard') }}</Link>
                        <span>›</span>
                        <Link href="/admin/service-categories" class="hover:text-[#C4A265] transition">{{ $t('a_service_categories') }}</Link>
                        <span>›</span>
                        <span class="text-gray-800 font-medium">{{ $localized(category, 'name') }}</span>
                    </nav>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg"
                             style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $localized(category, 'name') }}</h1>
                            <p class="text-sm text-gray-500" :dir="isRtl ? 'ltr' : 'rtl'">{{ locale === 'ar' ? category.name_en : category.name_ar }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link v-if="can('service_categories.update')"
                          :href="`/admin/service-categories/${category.id}/edit`"
                          class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-medium border-2 border-[#C4A265] text-[#C4A265] hover:bg-[#C4A265] hover:text-white transition-all duration-300">
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ $t('a_edit') }}
                    </Link>
                    <Link href="/admin/service-categories"
                          class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all duration-300">
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ $t('a_back') }}
                    </Link>
                </div>
            </div>

            <!-- ═══ STATS CARDS ═══ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 stagger-inventory"
                 :class="mounted ? '' : 'opacity-0'">
                <div v-for="(card, i) in statCards" :key="i"
                     class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden inventory-card-hover animate-inventory-card">
                    <!-- Gradient accent -->
                    <div class="absolute top-0 left-0 right-0 h-1 rounded-t-2xl"
                         :class="`bg-gradient-to-r ${card.gradient}`"></div>

                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ card.label }}</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-900 leading-none">{{ card.value }}</p>
                            <p v-if="card.sub" class="text-xs text-gray-500 mt-1">{{ card.sub }}</p>
                        </div>
                        <div :class="[card.iconBg, 'w-11 h-11 rounded-xl flex items-center justify-center']">
                            <!-- Services icon -->
                            <svg v-if="card.icon === 'services'" :class="[card.iconColor, 'w-5 h-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <!-- Revenue icon -->
                            <svg v-else-if="card.icon === 'revenue'" :class="[card.iconColor, 'w-5 h-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Visits icon -->
                            <svg v-else-if="card.icon === 'visits'" :class="[card.iconColor, 'w-5 h-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <!-- Price icon -->
                            <svg v-else :class="[card.iconColor, 'w-5 h-5']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ CHARTS ROW ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.2s;">

                <!-- Monthly Chart (2/3 width) -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_performance_trends') }}</h3>
                        <div class="flex bg-gray-100 rounded-lg p-0.5">
                            <button @click="activeChart = 'revenue'"
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200"
                                    :class="activeChart === 'revenue' ? 'bg-white text-[#C4A265] shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                                {{ $t('a_revenue') }}
                            </button>
                            <button @click="activeChart = 'visits'"
                                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200"
                                    :class="activeChart === 'visits' ? 'bg-white text-[#C4A265] shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                                {{ $t('a_visits_tab') }}
                            </button>
                        </div>
                    </div>

                    <!-- Revenue Chart -->
                    <div v-if="activeChart === 'revenue'" class="space-y-3">
                        <div v-for="(m, idx) in monthlyRevenue" :key="idx" class="flex items-center gap-3">
                            <span class="w-12 text-xs font-medium text-gray-500 ltr:text-right rtl:text-left">{{ m.short }}</span>
                            <div class="flex-1 h-8 bg-gray-50 rounded-lg overflow-hidden relative">
                                <div class="h-full rounded-lg transition-all duration-1000 ease-out"
                                     style="background: linear-gradient(90deg, #C4A265, #D4B87A);"
                                     :style="{ width: mounted ? `${Math.max((m.revenue / maxRevenue) * 100, 2)}%` : '0%' }">
                                </div>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-600">
                                    {{ formatCurrency(m.revenue) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Visits Chart -->
                    <div v-else class="space-y-3">
                        <div v-for="(m, idx) in monthlyVisits" :key="idx" class="flex items-center gap-3">
                            <span class="w-12 text-xs font-medium text-gray-500 ltr:text-right rtl:text-left">{{ m.short }}</span>
                            <div class="flex-1 h-8 bg-gray-50 rounded-lg overflow-hidden relative">
                                <div class="h-full rounded-lg transition-all duration-1000 ease-out bg-[#1B365D]"
                                     :style="{ width: mounted ? `${Math.max((m.count / maxVisits) * 100, 2)}%` : '0%' }">
                                </div>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-600">
                                    {{ m.count }} {{ $t('a_visits') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Share (1/3 width) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-5">{{ $t('a_revenue_share') }}</h3>
                    <div v-if="revenueShare && revenueShare.length > 0" class="space-y-3">
                        <div v-for="(item, idx) in revenueShare" :key="idx" class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: pieColors[idx % pieColors.length] }"></div>
                                    <span class="text-xs font-medium text-gray-700 truncate max-w-[120px]">{{ item.name }}</span>
                                </div>
                                <span class="text-xs font-bold text-gray-600">{{ totalRevenueShare > 0 ? Math.round((item.revenue / totalRevenueShare) * 100) : 0 }}%</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000 ease-out"
                                     :style="{
                                         width: mounted ? `${totalRevenueShare > 0 ? (item.revenue / totalRevenueShare) * 100 : 0}%` : '0%',
                                         backgroundColor: pieColors[idx % pieColors.length],
                                     }">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                        <p class="text-sm">{{ $t('a_no_revenue_data') }}</p>
                    </div>
                </div>
            </div>

            <!-- ═══ TOP SERVICES ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.3s;">

                <!-- Top by Revenue -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_top_by_revenue') }}</h3>
                    </div>
                    <div class="space-y-3">
                        <div v-for="(svc, idx) in topByRevenue" :key="svc.id"
                             class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                 :class="idx === 0 ? 'bg-[#C4A265]/10 text-[#C4A265]' : idx === 1 ? 'bg-slate-50 text-[#1B365D]' : 'bg-gray-100 text-gray-500'">
                                {{ idx + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $localized(svc, 'name') }}</p>
                                <p class="text-xs text-gray-400">{{ svc.visits_count }} visits</p>
                            </div>
                            <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(svc.total_revenue) }}</span>
                        </div>
                        <div v-if="!topByRevenue || topByRevenue.length === 0"
                             class="text-center py-4 md:py-6 text-sm text-gray-400">{{ $t('a_no_revenue_data_available') }}</div>
                    </div>
                </div>

                <!-- Top by Visits -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_top_by_visits') }}</h3>
                    </div>
                    <div class="space-y-3">
                        <div v-for="(svc, idx) in topByVisits" :key="svc.id"
                             class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                 :class="idx === 0 ? 'bg-[#C4A265]/10 text-[#C4A265]' : idx === 1 ? 'bg-slate-50 text-[#1B365D]' : 'bg-gray-100 text-gray-500'">
                                {{ idx + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $localized(svc, 'name') }}</p>
                                <p class="text-xs text-gray-400">{{ formatCurrency(svc.total_revenue) }} revenue</p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-bold text-[#1B365D]">{{ svc.visits_count }}</span>
                                <span class="text-xs text-gray-400">visits</span>
                            </div>
                        </div>
                        <div v-if="!topByVisits || topByVisits.length === 0"
                             class="text-center py-4 md:py-6 text-sm text-gray-400">{{ $t('a_no_visit_data_available') }}</div>
                    </div>
                </div>
            </div>

            <!-- ═══ ALL SERVICES TABLE ═══ -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.35s;">
                <div class="px-4 md:px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                             style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_all_services') }}</h3>
                        <span class="ltr:ml-2 rtl:mr-2 px-2 py-0.5 text-xs font-bold rounded-full bg-[#C4A265]/10 text-[#C4A265]">{{ services?.length || 0 }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_service') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_price') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_cost_breakdown') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_visits') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_bookings') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_revenue') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="svc in services" :key="svc.id"
                                class="hover:bg-[#C4A265]/[0.02] transition-colors duration-150">
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div v-if="svc.image"
                                             class="w-10 h-10 rounded-lg bg-cover bg-center border border-gray-200"
                                             :style="{ backgroundImage: `url(${svc.image})` }">
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#C4A265]/20 to-[#D4B87A]/20 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ $localized(svc, 'name') }}</p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span v-if="svc.bookable" class="text-[10px] px-1.5 py-0.5 rounded bg-slate-50 text-[#1B365D] font-medium">{{ $t('a_bookable') }}</span>
                                                <span v-if="svc.show_on_website" class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-600 font-medium">{{ $t('a_website') }}</span>
                                                <span v-if="svc.session_duration_minutes" class="text-[10px] text-gray-400">{{ svc.session_duration_minutes }}min</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div class="text-sm font-bold text-gray-800">{{ formatCurrency(svc.price) }}</div>
                                    <div v-if="svc.price_after_discount && svc.price_after_discount < svc.price" class="text-xs text-emerald-600 font-medium">
                                        {{ $t('a_after_discount') }}: {{ formatCurrency(svc.price_after_discount) }}
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-400">{{ $t('a_supply_cost') }}:</span>
                                            <span class="font-medium text-gray-600">{{ formatCurrency(svc.supply_cost) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-400">{{ $t('a_medical_fee') }}:</span>
                                            <span class="font-medium text-gray-600">{{ formatCurrency(svc.medical_fee) }}</span>
                                        </div>
                                        <div v-if="svc.doctor_commission_percentage" class="flex items-center justify-between text-xs">
                                            <span class="text-gray-400">{{ $t('a_commission') }}:</span>
                                            <span class="font-medium text-[#1B365D]">{{ svc.doctor_commission_percentage }}%</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span class="text-sm font-bold text-gray-800">{{ formatNumber(svc.visits_count) }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span class="text-sm font-bold text-gray-800">{{ formatNumber(svc.total_bookings) }}</span>
                                    <p v-if="svc.completed_sessions" class="text-xs text-gray-400">{{ svc.completed_sessions }} sessions</p>
                                </td>
                                <td class="px-4 md:px-6 py-4 ltr:text-right rtl:text-left">
                                    <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(svc.total_revenue) }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full capitalize"
                                          :class="statusColor(svc.status)">
                                        {{ svc.status }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <Link v-if="can('services.update')"
                                          :href="`/admin/services/${svc.id}/edit`"
                                          class="text-xs font-medium text-[#C4A265] hover:underline">
                                        Edit
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!services || services.length === 0">
                                <td colspan="8" class="px-4 md:px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_services_in_category') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══ RECENT VISITS ═══ -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                 :class="mounted ? 'animate-fade-in-up' : 'opacity-0'" style="animation-delay: 0.4s;">
                <div class="px-4 md:px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_recent_visits') }}</h3>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_visit_number') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_patient') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_doctor') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_service') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ $t('a_commission') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="visit in recentVisits" :key="visit.id"
                                class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 md:px-6 py-3.5 text-sm font-mono text-[#C4A265] font-medium">{{ visit.visit_number }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm font-medium text-gray-800">{{ visit.patient_name || '-' }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-gray-600">{{ visit.doctor_name || '-' }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-gray-600">{{ visit.service_name || '-' }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-gray-500">{{ visit.visit_date }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-center">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                          :class="statusColor(visit.status)">
                                        {{ visit.status?.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-3.5 ltr:text-right rtl:text-left text-sm font-medium text-[#1B365D]">
                                    {{ visit.commission_amount ? formatCurrency(visit.commission_amount) : '-' }}
                                </td>
                            </tr>
                            <tr v-if="!recentVisits || recentVisits.length === 0">
                                <td colspan="7" class="px-4 md:px-6 py-8 text-center text-sm text-gray-400">{{ $t('a_no_recent_visits') }}</td>
                            </tr>
                        </tbody>
                    </table>
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
