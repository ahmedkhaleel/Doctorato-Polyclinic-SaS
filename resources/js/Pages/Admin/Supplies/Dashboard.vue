<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    stats: Object,
    categoryDistribution: Array,
    lowStockItems: Array,
    expiringSoonItems: Array,
    recentTransactions: Array,
    topUsed: Array,
    monthlyTrends: Object,
});

/* ── Formatters ── */

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatDateTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/* ── Stats cards config ── */
const statCards = computed(() => [
    {
        label: 'a_total_products',
        value: props.stats?.totalProducts ?? 0,
        sub: `${props.stats?.activeProducts ?? 0} ${t('a_active').toLowerCase()}`,
        gradient: 'from-[#C4A265] to-[#D4B87A]',
        iconBg: 'bg-[#C4A265]/10',
        iconColor: 'text-[#C4A265]',
        icon: 'box',
        pulse: false,
    },
    {
        label: 'a_inventory_value',
        value: formatCurrency(props.stats?.totalValue),
        sub: null,
        gradient: 'from-emerald-500 to-emerald-600',
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
        icon: 'currency',
        pulse: false,
    },
    {
        label: 'a_low_stock_alerts',
        value: props.stats?.lowStockCount ?? 0,
        sub: t('a_items_below_minimum'),
        gradient: 'from-rose-500 to-rose-600',
        iconBg: 'bg-rose-50',
        iconColor: 'text-rose-600',
        icon: 'warning',
        pulse: (props.stats?.lowStockCount ?? 0) > 0,
    },
    {
        label: 'a_expiring_soon',
        value: (props.stats?.expiringSoonCount ?? 0) + (props.stats?.expiredCount ?? 0),
        sub: props.stats?.expiredCount ? `${props.stats.expiredCount} ${t('a_already_expired')}` : t('a_within_90_days'),
        gradient: 'from-amber-500 to-orange-500',
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        icon: 'calendar',
        pulse: ((props.stats?.expiringSoonCount ?? 0) + (props.stats?.expiredCount ?? 0)) > 0,
    },
]);

/* ── Transaction type helpers ── */
const typeLabels = computed(() => ({
    purchase: t('a_purchase'),
    usage: t('a_usage'),
    adjustment: t('a_adjustment'),
    return: t('a_return'),
}));

const typeColors = {
    purchase: 'bg-green-100 text-green-800',
    usage: 'bg-blue-100 text-blue-800',
    adjustment: 'bg-yellow-100 text-yellow-800',
    return: 'bg-purple-100 text-purple-800',
};

/* ── Low stock bar ── */
function stockPercent(item) {
    if (!item.min_quantity || item.min_quantity === 0) return 100;
    return Math.min(Math.round((item.quantity / item.min_quantity) * 100), 100);
}

function stockBarColor(item) {
    const pct = stockPercent(item);
    if (pct <= 25) return 'bg-red-500';
    if (pct <= 50) return 'bg-orange-500';
    if (pct <= 75) return 'bg-yellow-500';
    return 'bg-green-500';
}

/* ── Expiry helpers ── */
function daysUntilExpiry(dateStr) {
    if (!dateStr) return Infinity;
    const now = new Date();
    const expiry = new Date(dateStr);
    return Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
}

function expiryBadge(dateStr) {
    const days = daysUntilExpiry(dateStr);
    if (days < 0) return { text: t('a_expired'), cls: 'bg-red-100 text-red-800' };
    if (days <= 7) return { text: `${days}d left`, cls: 'bg-red-100 text-red-700' };
    if (days <= 30) return { text: `${days}d left`, cls: 'bg-orange-100 text-orange-700' };
    return { text: `${days}d left`, cls: 'bg-amber-100 text-amber-700' };
}

/* ── Category bar max ── */
const maxCategoryCount = computed(() => {
    if (!props.categoryDistribution?.length) return 1;
    return Math.max(...props.categoryDistribution.map(c => c.count), 1);
});

/* ── Monthly trends for mini chart ── */
const trendMonths = computed(() => {
    if (!props.monthlyTrends) return [];
    return Object.entries(props.monthlyTrends)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([month, data]) => ({
            label: new Date(month + '-01').toLocaleDateString('en-GB', { month: 'short' }),
            fullLabel: new Date(month + '-01').toLocaleDateString('en-GB', { month: 'short', year: 'numeric' }),
            purchases: data.purchases ?? 0,
            usage: data.usage ?? 0,
        }));
});

const maxTrendValue = computed(() => {
    if (!trendMonths.value.length) return 1;
    return Math.max(...trendMonths.value.flatMap(m => [m.purchases, m.usage]), 1);
});

/* ── Alerts visibility ── */
const hasAlerts = computed(() => {
    return (props.lowStockItems?.length > 0) || (props.expiringSoonItems?.length > 0);
});
</script>

<template>
    <AdminLayout :title="$t('a_inventory_overview')">
        <div class="space-y-6">

            <!-- ═══ Header ═══ -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center text-sm text-gray-500 mb-1">
                        <Link href="/admin" class="hover:text-[#C4A265] transition-colors">{{ $t('a_dashboard') }}</Link>
                        <svg class="w-4 h-4 mx-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <Link href="/admin/supplies" class="hover:text-[#C4A265] transition-colors">{{ $t('a_supply') }}</Link>
                        <svg class="w-4 h-4 mx-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-gray-700 font-medium">{{ $t('a_overview') }}</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_inventory_overview') }}</h1>
                </div>
                <Link
                    v-if="can('supplies.create')"
                    href="/admin/supplies/create"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-200"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_product') }}
                </Link>
            </div>

            <!-- ═══ Stats Cards ═══ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 stagger-children">
                <div
                    v-for="(card, idx) in statCards"
                    :key="idx"
                    class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden animate-fade-in-up"
                    :style="{ animationDelay: `${idx * 100}ms` }"
                >
                    <!-- Accent top bar -->
                    <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80`"></div>

                    <div class="flex items-start justify-between">
                        <div class="min-w-0">
                            <p class="text-[13px] font-medium text-gray-500 uppercase tracking-wide">{{ $t(card.label) }}</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2 truncate">{{ card.value }}</p>
                            <p v-if="card.sub" class="text-xs text-gray-400 mt-1">{{ card.sub }}</p>
                        </div>
                        <div
                            :class="[card.iconBg, card.pulse ? 'animate-alert-pulse' : '']"
                            class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300"
                        >
                            <!-- Box icon -->
                            <svg v-if="card.icon === 'box'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <!-- Currency icon -->
                            <svg v-else-if="card.icon === 'currency'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Warning icon -->
                            <svg v-else-if="card.icon === 'warning'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <!-- Calendar icon -->
                            <svg v-else-if="card.icon === 'calendar'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Alerts Section ═══ -->
            <div v-if="hasAlerts" class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-fade-in-up" style="animation-delay: 450ms;">

                <!-- Low Stock Items -->
                <div v-if="lowStockItems?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-red-500 animate-alert-pulse"></span>
                            <h2 class="text-base font-semibold text-gray-800">{{ $t('a_low_stock_items') }}</h2>
                        </div>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-red-50 text-red-700">{{ lowStockItems.length }} {{ $t('a_items') }}</span>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
                        <Link
                            v-for="item in lowStockItems"
                            :key="item.id"
                            :href="`/admin/supplies/${item.id}/transactions`"
                            class="block px-6 py-4 hover:bg-gray-50/50 transition-colors"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ item.name_en }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs font-mono text-gray-400">{{ item.sku }}</span>
                                        <span
                                            v-if="item.supply_category"
                                            class="text-[10px] font-medium px-1.5 py-0.5 rounded-full text-white"
                                            :style="{ backgroundColor: item.supply_category.color || '#6B7280' }"
                                        >{{ item.supply_category.name_en }}</span>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 ml-3">
                                    <p class="text-sm font-bold text-red-600">{{ item.quantity }} <span class="text-xs font-normal text-gray-400">/ {{ item.min_quantity }} {{ item.unit || '' }}</span></p>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div
                                    :class="stockBarColor(item)"
                                    class="h-1.5 rounded-full transition-all duration-500"
                                    :style="{ width: stockPercent(item) + '%' }"
                                ></div>
                            </div>
                        </Link>
                    </div>
                    <div v-if="!lowStockItems?.length" class="px-6 py-10 text-center">
                        <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="text-sm text-gray-400">{{ $t('a_all_items_well_stocked') }}</p>
                    </div>
                </div>

                <!-- Expiring Soon Items -->
                <div v-if="expiringSoonItems?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-alert-pulse"></span>
                            <h2 class="text-base font-semibold text-gray-800">{{ $t('a_expiring_soon') }}</h2>
                        </div>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">{{ expiringSoonItems.length }} {{ $t('a_items') }}</span>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
                        <Link
                            v-for="item in expiringSoonItems"
                            :key="item.id"
                            :href="`/admin/supplies/${item.id}/transactions`"
                            class="block px-6 py-4 hover:bg-gray-50/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ item.name_en }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span
                                            v-if="item.supply_category"
                                            class="text-[10px] font-medium px-1.5 py-0.5 rounded-full text-white"
                                            :style="{ backgroundColor: item.supply_category.color || '#6B7280' }"
                                        >{{ item.supply_category.name_en }}</span>
                                        <span class="text-xs text-gray-400">{{ item.quantity }} {{ item.unit || '' }}</span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ml-3 text-right">
                                    <span
                                        :class="expiryBadge(item.expiry_date).cls"
                                        class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                                    >{{ expiryBadge(item.expiry_date).text }}</span>
                                    <p class="text-[11px] text-gray-400 mt-0.5">{{ formatDate(item.expiry_date) }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                    <div v-if="!expiringSoonItems?.length" class="px-6 py-10 text-center">
                        <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="text-sm text-gray-400">{{ $t('a_no_items_expiring') }}</p>
                    </div>
                </div>
            </div>

            <!-- ═══ Middle Row: Category Distribution + Monthly Trends ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-fade-in-up" style="animation-delay: 550ms;">

                <!-- Category Distribution -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-800">{{ $t('a_category_distribution') }}</h2>
                    </div>
                    <div v-if="categoryDistribution?.length" class="p-6 space-y-4">
                        <div v-for="cat in categoryDistribution" :key="cat.name" class="group">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-3 h-3 rounded-full flex-shrink-0"
                                        :style="{ backgroundColor: cat.color || '#C4A265' }"
                                    ></span>
                                    <span class="text-sm font-medium text-gray-700">{{ cat.name }}</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ cat.count }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div
                                    class="h-2 rounded-full transition-all duration-700 ease-out"
                                    :style="{
                                        width: ((cat.count / maxCategoryCount) * 100) + '%',
                                        backgroundColor: cat.color || '#C4A265',
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        <p class="text-sm text-gray-400">{{ $t('a_no_categories_available') }}</p>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-800">{{ $t('a_monthly_trends') }}</h2>
                        <div class="flex items-center gap-4 mt-1">
                            <span class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span> {{ $t('a_purchases') }}
                            </span>
                            <span class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="w-2.5 h-2.5 rounded-sm bg-blue-500"></span> {{ $t('a_usage') }}
                            </span>
                        </div>
                    </div>
                    <div v-if="trendMonths.length" class="p-6">
                        <div class="flex items-end gap-2 h-40">
                            <div
                                v-for="month in trendMonths"
                                :key="month.fullLabel"
                                class="flex-1 flex flex-col items-center gap-1 group"
                            >
                                <div class="w-full flex gap-0.5 items-end justify-center h-28">
                                    <div
                                        class="w-[45%] bg-emerald-500/80 rounded-t transition-all duration-500 hover:bg-emerald-500"
                                        :style="{ height: ((month.purchases / maxTrendValue) * 100) + '%', minHeight: month.purchases > 0 ? '4px' : '0px' }"
                                        :title="`Purchases: ${month.purchases}`"
                                    ></div>
                                    <div
                                        class="w-[45%] bg-blue-500/80 rounded-t transition-all duration-500 hover:bg-blue-500"
                                        :style="{ height: ((month.usage / maxTrendValue) * 100) + '%', minHeight: month.usage > 0 ? '4px' : '0px' }"
                                        :title="`Usage: ${month.usage}`"
                                    ></div>
                                </div>
                                <span class="text-[10px] text-gray-400 font-medium">{{ month.label }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-12 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        <p class="text-sm text-gray-400">{{ $t('a_no_trend_data') }}</p>
                    </div>
                </div>
            </div>

            <!-- ═══ Bottom Row: Recent Transactions + Top Used ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in-up" style="animation-delay: 650ms;">

                <!-- Recent Transactions -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-800">{{ $t('a_recent_transactions') }}</h2>
                        <Link href="/admin/supplies" class="text-xs font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view_all') }}</Link>
                    </div>
                    <div v-if="recentTransactions?.length" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead>
                                <tr class="bg-gray-50/60">
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_products') }}</th>
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_type') }}</th>
                                    <th class="px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_quantity') }}</th>
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_by') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="txn in recentTransactions" :key="txn.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-gray-500">{{ formatDateTime(txn.created_at) }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-sm font-medium text-gray-800">{{ txn.supply?.name_en || '-' }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap">
                                        <span
                                            :class="typeColors[txn.transaction_type] || 'bg-gray-100 text-gray-700'"
                                            class="px-2 py-0.5 inline-flex text-[11px] leading-5 font-semibold rounded-full"
                                        >{{ typeLabels[txn.transaction_type] || txn.transaction_type }}</span>
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-sm font-semibold text-right" :class="txn.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ txn.quantity > 0 ? '+' : '' }}{{ txn.quantity }}
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-xs text-gray-500">{{ txn.creator?.name || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-6 py-14 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        <p class="text-sm text-gray-400 font-medium">{{ $t('a_no_transactions_recorded') }}</p>
                        <p class="text-xs text-gray-300 mt-1">{{ $t('a_transactions_appear_here') }}</p>
                    </div>
                </div>

                <!-- Top Used Products -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-800">{{ $t('a_top_used_products') }}</h2>
                    </div>
                    <div v-if="topUsed?.length" class="divide-y divide-gray-50">
                        <div
                            v-for="(item, idx) in topUsed"
                            :key="idx"
                            class="px-6 py-3.5 flex items-center gap-3 hover:bg-gray-50/50 transition-colors"
                        >
                            <span
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                :class="{
                                    'bg-[#C4A265]/10 text-[#C4A265]': idx === 0,
                                    'bg-gray-100 text-gray-500': idx === 1,
                                    'bg-orange-50 text-orange-500': idx === 2,
                                    'bg-gray-50 text-gray-400': idx > 2,
                                }"
                            >{{ idx + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ item.supply?.name_en || '-' }}</p>
                                <p class="text-[11px] text-gray-400">{{ item.supply?.unit || 'units' }}</p>
                            </div>
                            <span class="text-sm font-bold text-gray-900 flex-shrink-0">{{ item.total_used }}</span>
                        </div>
                    </div>
                    <div v-else class="px-6 py-14 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        <p class="text-sm text-gray-400 font-medium">{{ $t('a_no_usage_data') }}</p>
                        <p class="text-xs text-gray-300 mt-1">{{ $t('a_product_usage_tracked') }}</p>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
/* ── Staggered entrance ── */
.stagger-children > *:nth-child(1) { animation-delay: 0ms; }
.stagger-children > *:nth-child(2) { animation-delay: 100ms; }
.stagger-children > *:nth-child(3) { animation-delay: 200ms; }
.stagger-children > *:nth-child(4) { animation-delay: 300ms; }

/* ── Fade in up ── */
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ── Alert pulse ── */
.animate-alert-pulse {
    animation: alertPulse 2s ease-in-out infinite;
}

@keyframes alertPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}
</style>
