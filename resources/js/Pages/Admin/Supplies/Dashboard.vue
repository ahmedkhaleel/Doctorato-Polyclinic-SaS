<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
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
    activeModule: { type: String, default: 'all' },
});

/* ── Module tabs ── */
const moduleTabs = [
    { value: 'all',    label: { en: 'All', ar: 'الكل' }, icon: 'M4 6h16M4 10h16M4 14h16M4 18h16' },
    { value: 'derma',  label: { en: 'Dermatology', ar: 'الجلدية' }, icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
    { value: 'dental', label: { en: 'Dental', ar: 'الأسنان' }, icon: 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z' },
    { value: 'shared', label: { en: 'Shared', ar: 'مشترك' }, icon: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z' },
];

function switchModule(mod) {
    router.get('/admin/inventory', mod === 'all' ? {} : { module: mod }, { preserveState: true, replace: true });
}

/* ── Animation state ── */
const mounted = ref(false);
const statsVisible = ref(false);
const chartsVisible = ref(false);
const alertsVisible = ref(false);
const bottomVisible = ref(false);
const dataLoading = ref(true);

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    setTimeout(() => { statsVisible.value = true; }, 200);
    setTimeout(() => { alertsVisible.value = true; }, 400);
    setTimeout(() => { chartsVisible.value = true; }, 550);
    setTimeout(() => { bottomVisible.value = true; }, 700);
    setTimeout(() => { dataLoading.value = false; }, 800);
});

/* ── Formatters ── */
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-GB', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDateTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-GB', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

/* ── Stats cards config ── */
const ACCENT = '#6366F1'; // Inventory module color - indigo

const statCards = computed(() => [
    {
        label: isRtl.value ? 'إجمالي المنتجات' : 'Total Products',
        value: props.stats?.totalProducts ?? 0,
        sub: `${props.stats?.activeProducts ?? 0} ${isRtl.value ? 'نشط' : 'active'}`,
        gradient: 'from-indigo-500 to-indigo-600',
        iconBg: 'bg-indigo-50',
        iconColor: 'text-indigo-600',
        icon: 'box',
        pulse: false,
    },
    {
        label: isRtl.value ? 'قيمة المخزون' : 'Inventory Value',
        value: formatCurrency(props.stats?.totalValue),
        sub: null,
        gradient: 'from-emerald-500 to-emerald-600',
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
        icon: 'currency',
        pulse: false,
    },
    {
        label: isRtl.value ? 'تنبيهات النقص' : 'Low Stock Alerts',
        value: props.stats?.lowStockCount ?? 0,
        sub: isRtl.value ? 'أقل من الحد الأدنى' : 'Below minimum',
        gradient: 'from-rose-500 to-rose-600',
        iconBg: 'bg-rose-50',
        iconColor: 'text-rose-600',
        icon: 'warning',
        pulse: (props.stats?.lowStockCount ?? 0) > 0,
    },
    {
        label: isRtl.value ? 'قريبة الانتهاء' : 'Expiring Soon',
        value: (props.stats?.expiringSoonCount ?? 0) + (props.stats?.expiredCount ?? 0),
        sub: props.stats?.expiredCount ? `${props.stats.expiredCount} ${isRtl.value ? 'منتهية' : 'expired'}` : (isRtl.value ? 'خلال 30 يوم' : 'Within 30 days'),
        gradient: 'from-amber-500 to-orange-500',
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        icon: 'calendar',
        pulse: ((props.stats?.expiringSoonCount ?? 0) + (props.stats?.expiredCount ?? 0)) > 0,
    },
]);

/* ── Transaction type helpers ── */
const typeConfig = {
    purchase: { label: () => isRtl.value ? 'شراء' : 'Purchase', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', border: 'border-emerald-200' },
    usage: { label: () => isRtl.value ? 'استخدام' : 'Usage', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500', border: 'border-blue-200' },
    adjustment: { label: () => isRtl.value ? 'تعديل' : 'Adjustment', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', border: 'border-amber-200' },
    return: { label: () => isRtl.value ? 'إرجاع' : 'Return', bg: 'bg-purple-50', text: 'text-purple-700', dot: 'bg-purple-500', border: 'border-purple-200' },
};

/* ── Stock helpers ── */
function stockPercent(item) {
    if (!item.min_quantity || item.min_quantity === 0) return 100;
    return Math.min(Math.round((item.quantity / item.min_quantity) * 100), 100);
}

function stockBarColor(item) {
    const pct = stockPercent(item);
    if (pct <= 25) return 'bg-red-500';
    if (pct <= 50) return 'bg-orange-500';
    if (pct <= 75) return 'bg-amber-400';
    return 'bg-emerald-500';
}

function stockTextColor(item) {
    const pct = stockPercent(item);
    if (pct <= 25) return 'text-red-600';
    if (pct <= 50) return 'text-orange-600';
    return 'text-amber-600';
}

/* ── Expiry helpers ── */
function daysUntilExpiry(dateStr) {
    if (!dateStr) return Infinity;
    return Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
}

function expiryBadge(dateStr) {
    const days = daysUntilExpiry(dateStr);
    if (days < 0) return { text: isRtl.value ? 'منتهي' : 'Expired', cls: 'bg-red-100 text-red-700 border border-red-200' };
    if (days <= 7) return { text: `${days}${isRtl.value ? ' يوم' : 'd'}`, cls: 'bg-red-50 text-red-600 border border-red-200' };
    if (days <= 30) return { text: `${days}${isRtl.value ? ' يوم' : 'd'}`, cls: 'bg-orange-50 text-orange-600 border border-orange-200' };
    return { text: `${days}${isRtl.value ? ' يوم' : 'd'}`, cls: 'bg-amber-50 text-amber-600 border border-amber-200' };
}

/* ── Category chart ── */
const maxCategoryCount = computed(() => {
    if (!props.categoryDistribution?.length) return 1;
    return Math.max(...props.categoryDistribution.map(c => c.count), 1);
});

const totalCategoryItems = computed(() => {
    if (!props.categoryDistribution?.length) return 0;
    return props.categoryDistribution.reduce((sum, c) => sum + c.count, 0);
});

/* ── Monthly trends SVG line chart ── */
const trendMonths = computed(() => {
    if (!props.monthlyTrends) return [];
    return Object.entries(props.monthlyTrends)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([month, data]) => ({
            label: new Date(month + '-01').toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-GB', { month: 'short' }),
            fullLabel: new Date(month + '-01').toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-GB', { month: 'short', year: 'numeric' }),
            purchases: data.purchases ?? 0,
            usage: data.usage ?? 0,
        }));
});

const maxTrendValue = computed(() => {
    if (!trendMonths.value.length) return 1;
    return Math.max(...trendMonths.value.flatMap(m => [m.purchases, m.usage]), 1);
});

// SVG line chart path generators
const chartWidth = 320;
const chartHeight = 120;
const chartPadding = 16;

function trendLinePath(key) {
    if (!trendMonths.value.length) return '';
    const pts = trendMonths.value.map((m, i) => {
        const x = chartPadding + (i / Math.max(trendMonths.value.length - 1, 1)) * (chartWidth - chartPadding * 2);
        const y = chartHeight - chartPadding - ((m[key] / maxTrendValue.value) * (chartHeight - chartPadding * 2));
        return { x, y };
    });
    if (pts.length === 1) return `M${pts[0].x},${pts[0].y}`;
    let d = `M${pts[0].x},${pts[0].y}`;
    for (let i = 1; i < pts.length; i++) {
        const cp1x = pts[i - 1].x + (pts[i].x - pts[i - 1].x) * 0.4;
        const cp2x = pts[i].x - (pts[i].x - pts[i - 1].x) * 0.4;
        d += ` C${cp1x},${pts[i - 1].y} ${cp2x},${pts[i].y} ${pts[i].x},${pts[i].y}`;
    }
    return d;
}

function trendAreaPath(key) {
    const line = trendLinePath(key);
    if (!line || !trendMonths.value.length) return '';
    const lastPt = trendMonths.value.length - 1;
    const endX = chartPadding + (lastPt / Math.max(lastPt, 1)) * (chartWidth - chartPadding * 2);
    return `${line} L${endX},${chartHeight - chartPadding} L${chartPadding},${chartHeight - chartPadding} Z`;
}

function trendPoints(key) {
    if (!trendMonths.value.length) return [];
    return trendMonths.value.map((m, i) => ({
        x: chartPadding + (i / Math.max(trendMonths.value.length - 1, 1)) * (chartWidth - chartPadding * 2),
        y: chartHeight - chartPadding - ((m[key] / maxTrendValue.value) * (chartHeight - chartPadding * 2)),
        value: m[key],
        label: m.fullLabel,
    }));
}

/* ── Alerts visibility ── */
const hasAlerts = computed(() => (props.lowStockItems?.length > 0) || (props.expiringSoonItems?.length > 0));

/* ── Quick links ── */
const quickLinks = computed(() => [
    { label: isRtl.value ? 'كل المنتجات' : 'All Products', href: '/admin/supplies', icon: 'grid', count: props.stats?.totalProducts },
    { label: isRtl.value ? 'الفئات' : 'Categories', href: '/admin/supply-categories', icon: 'folder' },
    { label: isRtl.value ? 'الموردين' : 'Suppliers', href: '/admin/suppliers', icon: 'truck' },
    { label: isRtl.value ? 'أوامر الشراء' : 'Purchase Orders', href: '/admin/purchase-orders', icon: 'clipboard' },
]);
</script>

<template>
    <AdminLayout :title="isRtl ? 'نظرة عامة على المخزون' : 'Inventory Overview'">
        <div class="space-y-6">

            <!-- ═══ Hero Header ═══ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-900 via-indigo-800 to-purple-900 p-6 sm:p-8"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
            >
                <!-- Decorative elements -->
                <div class="absolute top-0 right-0 w-80 h-80 bg-indigo-400/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-56 h-56 bg-purple-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
                <div class="absolute top-1/2 left-1/3 w-40 h-40 bg-indigo-300/5 rounded-full blur-2xl"></div>
                <!-- Grid pattern -->
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;40&quot; height=&quot;40&quot; viewBox=&quot;0 0 40 40&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;%23fff&quot;%3E%3Ccircle cx=&quot;1&quot; cy=&quot;1&quot; r=&quot;1&quot;/%3E%3C/g%3E%3C/svg%3E')"></div>

                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-indigo-400/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <p class="text-indigo-300 text-xs font-semibold tracking-wider uppercase">{{ isRtl ? 'إدارة المخزون' : 'Inventory Management' }}</p>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'نظرة عامة' : 'Overview' }}</h1>
                            <p class="text-indigo-200/70 text-sm mt-1">{{ isRtl ? 'تتبع المخزون والمستلزمات الطبية' : 'Track medical supplies and inventory' }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link v-if="can('supplies.create')" href="/admin/supplies/create"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 backdrop-blur-sm text-white text-sm font-semibold rounded-xl border border-white/20 hover:bg-white/20 transition-all duration-200 shadow-lg shadow-black/10"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ isRtl ? 'إضافة منتج' : 'Add Product' }}
                            </Link>
                        </div>
                    </div>

                    <!-- Module Tabs -->
                    <div class="flex flex-wrap gap-1.5 mb-3"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.12s"
                    >
                        <button
                            v-for="tab in moduleTabs"
                            :key="tab.value"
                            @click="switchModule(tab.value)"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold transition-all duration-200 border"
                            :class="activeModule === tab.value
                                ? 'bg-white text-indigo-900 border-white/80 shadow-lg shadow-white/10'
                                : 'bg-white/5 text-white/70 border-white/10 hover:bg-white/10 hover:text-white'"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                            </svg>
                            {{ isRtl ? tab.label.ar : tab.label.en }}
                        </button>
                    </div>

                    <!-- Quick navigation pills -->
                    <div class="flex flex-wrap gap-2"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                    >
                        <Link v-for="link in quickLinks" :key="link.href" :href="link.href"
                            class="inline-flex items-center gap-2 px-3.5 py-2 bg-white/5 backdrop-blur-sm rounded-lg border border-white/10 text-white/80 text-xs font-medium hover:bg-white/10 hover:text-white transition-all duration-200"
                        >
                            <svg v-if="link.icon === 'grid'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                            <svg v-else-if="link.icon === 'folder'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                            <svg v-else-if="link.icon === 'truck'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                            <svg v-else-if="link.icon === 'clipboard'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            {{ link.label }}
                            <span v-if="link.count" class="text-[10px] bg-white/10 px-1.5 py-0.5 rounded-full">{{ link.count }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ═══ Stats Cards ═══ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4"
                :class="statsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
            >
                <div v-for="(card, idx) in statCards" :key="idx"
                    class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-indigo-100 transition-all duration-300 overflow-hidden"
                    :style="{ transitionDelay: `${idx * 0.06}s` }"
                >
                    <div :class="`absolute top-0 inset-x-0 h-1 bg-gradient-to-r ${card.gradient} opacity-0 group-hover:opacity-100 transition-opacity duration-300`"></div>

                    <div class="flex items-start justify-between">
                        <div class="min-w-0">
                            <p class="text-[12px] font-semibold text-gray-400 uppercase tracking-wider">{{ card.label }}</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-2 truncate tabular-nums">{{ card.value }}</p>
                            <p v-if="card.sub" class="text-xs text-gray-400 mt-1.5">{{ card.sub }}</p>
                        </div>
                        <div :class="[card.iconBg, card.pulse ? 'animate-alert-pulse' : '']"
                            class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300"
                        >
                            <svg v-if="card.icon === 'box'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            <svg v-else-if="card.icon === 'currency'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="card.icon === 'warning'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <svg v-else-if="card.icon === 'calendar'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Alerts Section ═══ -->
            <div v-if="hasAlerts" class="grid grid-cols-1 lg:grid-cols-2 gap-5"
                :class="alertsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
            >
                <!-- Low Stock Items -->
                <div v-if="lowStockItems?.length" class="bg-white rounded-2xl shadow-sm border border-red-100/60 overflow-hidden">
                    <div class="px-6 py-4 border-b border-red-50 flex items-center justify-between bg-gradient-to-r from-red-50/50 to-transparent">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'مخزون منخفض' : 'Low Stock' }}</h2>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">{{ lowStockItems.length }}</span>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto overscroll-contain">
                        <Link v-for="(item, idx) in lowStockItems" :key="item.id"
                            :href="`/admin/supplies/${item.id}`"
                            class="flex items-center gap-3 px-5 py-3.5 hover:bg-red-50/30 transition-all duration-200"
                        >
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center flex-shrink-0 border border-gray-100">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ isRtl ? item.name_ar : item.name_en }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-mono text-gray-400">{{ item.sku }}</span>
                                    <span v-if="item.supply_category" class="text-[9px] font-bold px-1.5 py-0.5 rounded text-white" :style="{ backgroundColor: item.supply_category.color || '#6B7280' }">{{ isRtl ? item.supply_category.name_ar : item.supply_category.name_en }}</span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-sm font-bold" :class="stockTextColor(item)">{{ item.quantity }}</p>
                                <p class="text-[10px] text-gray-400">/ {{ item.min_quantity }} {{ item.unit || '' }}</p>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Expiring Soon -->
                <div v-if="expiringSoonItems?.length" class="bg-white rounded-2xl shadow-sm border border-amber-100/60 overflow-hidden">
                    <div class="px-6 py-4 border-b border-amber-50 flex items-center justify-between bg-gradient-to-r from-amber-50/50 to-transparent">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'قريبة الانتهاء' : 'Expiring Soon' }}</h2>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">{{ expiringSoonItems.length }}</span>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto overscroll-contain">
                        <Link v-for="item in expiringSoonItems" :key="item.id"
                            :href="`/admin/supplies/${item.id}`"
                            class="flex items-center gap-3 px-5 py-3.5 hover:bg-amber-50/30 transition-all duration-200"
                        >
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center flex-shrink-0 border border-gray-100">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ isRtl ? item.name_ar : item.name_en }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span v-if="item.supply_category" class="text-[9px] font-bold px-1.5 py-0.5 rounded text-white" :style="{ backgroundColor: item.supply_category.color || '#6B7280' }">{{ isRtl ? item.supply_category.name_ar : item.supply_category.name_en }}</span>
                                    <span class="text-[10px] text-gray-400">{{ item.quantity }} {{ item.unit || '' }}</span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span :class="expiryBadge(item.expiry_date).cls" class="text-[10px] font-bold px-2 py-0.5 rounded-full">{{ expiryBadge(item.expiry_date).text }}</span>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ formatDate(item.expiry_date) }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ═══ Charts Row ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5"
                :class="chartsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
            >
                <!-- Category Distribution -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'توزيع الفئات' : 'Category Distribution' }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ totalCategoryItems }} {{ isRtl ? 'منتج' : 'products' }}</p>
                        </div>
                        <Link href="/admin/supply-categories" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">{{ isRtl ? 'إدارة' : 'Manage' }}</Link>
                    </div>
                    <div v-if="categoryDistribution?.length" class="p-5 space-y-3">
                        <div v-for="(cat, idx) in categoryDistribution" :key="cat.name"
                            class="group cursor-default"
                            :style="{ animationDelay: `${idx * 50}ms` }"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 ring-2 ring-white shadow-sm" :style="{ backgroundColor: cat.color || '#6366F1' }"></span>
                                    <span class="text-xs font-semibold text-gray-700">{{ cat.name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-gray-400">{{ totalCategoryItems > 0 ? Math.round((cat.count / totalCategoryItems) * 100) : 0 }}%</span>
                                    <span class="text-xs font-bold text-gray-800 tabular-nums min-w-[24px] text-right">{{ cat.count }}</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-700 ease-out group-hover:opacity-80"
                                    :style="{ width: ((cat.count / maxCategoryCount) * 100) + '%', backgroundColor: cat.color || '#6366F1' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-14 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </div>
                        <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد فئات' : 'No categories yet' }}</p>
                    </div>
                </div>

                <!-- Monthly Trends - SVG Line Chart -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الاتجاهات الشهرية' : 'Monthly Trends' }}</h2>
                            <div class="flex items-center gap-4">
                                <span class="flex items-center gap-1.5 text-[11px] text-gray-500 font-medium">
                                    <span class="w-2.5 h-1 rounded-full bg-emerald-500"></span> {{ isRtl ? 'شراء' : 'Purchases' }}
                                </span>
                                <span class="flex items-center gap-1.5 text-[11px] text-gray-500 font-medium">
                                    <span class="w-2.5 h-1 rounded-full bg-indigo-500"></span> {{ isRtl ? 'استخدام' : 'Usage' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-if="trendMonths.length" class="p-5">
                        <!-- SVG Line Chart -->
                        <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-auto" preserveAspectRatio="xMidYMid meet">
                            <!-- Grid lines -->
                            <line v-for="i in 4" :key="'grid-'+i"
                                :x1="chartPadding" :x2="chartWidth - chartPadding"
                                :y1="chartPadding + ((i - 1) / 3) * (chartHeight - chartPadding * 2)"
                                :y2="chartPadding + ((i - 1) / 3) * (chartHeight - chartPadding * 2)"
                                stroke="#f3f4f6" stroke-width="0.5" stroke-dasharray="3,3"
                            />
                            <!-- Area fills -->
                            <path :d="trendAreaPath('purchases')" fill="url(#purchaseGrad)" opacity="0.15" />
                            <path :d="trendAreaPath('usage')" fill="url(#usageGrad)" opacity="0.15" />
                            <!-- Lines -->
                            <path :d="trendLinePath('purchases')" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" class="chart-line" />
                            <path :d="trendLinePath('usage')" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" class="chart-line" />
                            <!-- Data points - purchases -->
                            <g v-for="(pt, idx) in trendPoints('purchases')" :key="'p-'+idx">
                                <circle :cx="pt.x" :cy="pt.y" r="3" fill="#10b981" stroke="white" stroke-width="1.5" class="chart-dot" />
                                <text :x="pt.x" :y="pt.y - 8" text-anchor="middle" class="text-[7px] fill-gray-400 font-medium" v-if="pt.value > 0">{{ pt.value }}</text>
                            </g>
                            <!-- Data points - usage -->
                            <g v-for="(pt, idx) in trendPoints('usage')" :key="'u-'+idx">
                                <circle :cx="pt.x" :cy="pt.y" r="3" fill="#6366f1" stroke="white" stroke-width="1.5" class="chart-dot" />
                                <text :x="pt.x" :y="pt.y - 8" text-anchor="middle" class="text-[7px] fill-gray-400 font-medium" v-if="pt.value > 0">{{ pt.value }}</text>
                            </g>
                            <!-- Month labels -->
                            <text v-for="(m, idx) in trendMonths" :key="'ml-'+idx"
                                :x="chartPadding + (idx / Math.max(trendMonths.length - 1, 1)) * (chartWidth - chartPadding * 2)"
                                :y="chartHeight - 2"
                                text-anchor="middle"
                                class="text-[7px] fill-gray-400 font-medium"
                            >{{ m.label }}</text>
                            <!-- Gradients -->
                            <defs>
                                <linearGradient id="purchaseGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#10b981" /><stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                                </linearGradient>
                                <linearGradient id="usageGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#6366f1" /><stop offset="100%" stop-color="#6366f1" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <div v-else class="px-6 py-14 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                        <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات بعد' : 'No trend data yet' }}</p>
                    </div>
                </div>
            </div>

            <!-- ═══ Bottom Row ═══ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5"
                :class="bottomVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
            >
                <!-- Recent Transactions -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'آخر الحركات' : 'Recent Transactions' }}</h2>
                        <Link href="/admin/supplies" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">{{ isRtl ? 'عرض الكل' : 'View All' }}</Link>
                    </div>
                    <div v-if="recentTransactions?.length" class="divide-y divide-gray-50">
                        <div v-for="txn in recentTransactions" :key="txn.id"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50/50 transition-colors"
                        >
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                :class="typeConfig[txn.transaction_type]?.bg || 'bg-gray-100'"
                            >
                                <div class="w-2 h-2 rounded-full" :class="typeConfig[txn.transaction_type]?.dot || 'bg-gray-400'"></div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ isRtl ? (txn.supply?.name_ar || txn.supply?.name_en) : (txn.supply?.name_en || '-') }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span :class="[typeConfig[txn.transaction_type]?.bg, typeConfig[txn.transaction_type]?.text]"
                                        class="text-[10px] font-bold px-1.5 py-0.5 rounded">
                                        {{ typeConfig[txn.transaction_type]?.label() || txn.transaction_type }}
                                    </span>
                                    <span class="text-[10px] text-gray-400">{{ txn.creator?.name || '-' }}</span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-sm font-bold tabular-nums"
                                    :class="['purchase', 'return'].includes(txn.transaction_type) ? 'text-emerald-600' : 'text-red-500'"
                                >
                                    {{ ['purchase', 'return'].includes(txn.transaction_type) ? '+' : '-' }}{{ Math.abs(txn.quantity) }}
                                </p>
                                <p class="text-[10px] text-gray-400">{{ formatDateTime(txn.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-16 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا توجد حركات' : 'No transactions yet' }}</p>
                        <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'ستظهر الحركات هنا' : 'Transactions will appear here' }}</p>
                    </div>
                </div>

                <!-- Top Used Products -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الأكثر استخداما' : 'Most Used (30d)' }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'آخر 30 يوم' : 'Last 30 days' }}</p>
                    </div>
                    <div v-if="topUsed?.length" class="divide-y divide-gray-50">
                        <div v-for="(item, idx) in topUsed" :key="idx"
                            class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/50 transition-colors"
                        >
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                :class="{
                                    'bg-gradient-to-br from-amber-100 to-amber-50 text-amber-600 ring-1 ring-amber-200/50': idx === 0,
                                    'bg-gradient-to-br from-gray-100 to-gray-50 text-gray-500 ring-1 ring-gray-200/50': idx === 1,
                                    'bg-gradient-to-br from-orange-50 to-amber-50 text-orange-500 ring-1 ring-orange-200/50': idx === 2,
                                    'bg-gray-50 text-gray-400': idx > 2,
                                }"
                            >{{ idx + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ isRtl ? (item.supply?.name_ar || item.supply?.name_en) : (item.supply?.name_en || '-') }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ item.supply?.unit || 'units' }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600 tabular-nums">{{ item.total_used }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-16 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No usage data' }}</p>
                        <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'يتم تتبع الاستخدام تلقائيا' : 'Usage tracked automatically' }}</p>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
/* ── Alert pulse ── */
.animate-alert-pulse {
    animation: alertPulse 2s ease-in-out infinite;
}

@keyframes alertPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.05); }
}

/* ── Chart line animation ── */
.chart-line {
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    animation: drawLine 1.5s ease-out forwards;
    animation-delay: 0.5s;
}

.chart-dot {
    opacity: 0;
    animation: fadeDot 0.3s ease-out forwards;
    animation-delay: 1.5s;
}

@keyframes drawLine {
    to { stroke-dashoffset: 0; }
}

@keyframes fadeDot {
    to { opacity: 1; }
}
</style>
