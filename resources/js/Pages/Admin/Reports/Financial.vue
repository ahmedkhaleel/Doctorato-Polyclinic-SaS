<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import BarChart from '@/Components/Admin/BarChart.vue';
import DonutChart from '@/Components/Admin/DonutChart.vue';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    totalRevenue: Number,
    totalExpenses: Number,
    netIncome: Number,
    unpaidInvoices: Number,
    revenueByMethod: Array,
    expensesByCategory: Array,
    dailyRevenue: Array,
    modules: Object,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');

// Only show medical modules (derma, dental) - not hr, inventory, insurance
const medicalSlugs = ['derma', 'dental', 'pediatric'];
const activeModules = computed(() => {
    if (!props.modules) return [];
    return Object.values(props.modules).filter(m => m.enabled && medicalSlugs.includes(m.slug));
});

function applyFilters() {
    router.get('/admin/reports/financial', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

let dateTimeout = null;
watch([dateFrom, dateTo], () => {
    clearTimeout(dateTimeout);
    dateTimeout = setTimeout(applyFilters, 400);
});

watch(moduleFilter, () => applyFilters());

const { formatCurrency, currencyCode } = useCurrency();

/* ── Animations ────────────────────────────────────── */
const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

/* ── Chart data computeds ──────────────────────────── */
const dailyRevenueChartData = computed(() => {
    if (!props.dailyRevenue?.length) return [];
    return props.dailyRevenue.map(d => ({ label: d.label, value: d.value }));
});

const revenueMethodChartData = computed(() => {
    if (!props.revenueByMethod?.length) return [];
    return props.revenueByMethod.map((d, i) => ({ label: d.label, value: d.value, color: methodColors[i % methodColors.length] }));
});

const expenseCategoryChartData = computed(() => {
    if (!props.expensesByCategory?.length) return [];
    return props.expensesByCategory.map((d, i) => ({ label: d.label, value: d.value, color: categoryColors[i % categoryColors.length] }));
});

/* ── Totals ────────────────────────────────────────── */
const totalRevenueByMethod = computed(() =>
    (props.revenueByMethod || []).reduce((s, d) => s + (d.value || 0), 0)
);
const totalExpensesByCategory = computed(() =>
    (props.expensesByCategory || []).reduce((s, d) => s + (d.value || 0), 0)
);

const maxRevenueMethod = computed(() => {
    if (!props.revenueByMethod?.length) return 1;
    return Math.max(...props.revenueByMethod.map(d => d.value || 0), 1);
});

const maxExpenseCategory = computed(() => {
    if (!props.expensesByCategory?.length) return 1;
    return Math.max(...props.expensesByCategory.map(d => d.value || 0), 1);
});

/* ── Analytics ─────────────────────────────────────── */
const profitMargin = computed(() => {
    if (!props.totalRevenue || props.totalRevenue === 0) return 0;
    return Math.round((props.netIncome / props.totalRevenue) * 100);
});

const expenseRatio = computed(() => {
    if (!props.totalRevenue || props.totalRevenue === 0) return 0;
    return Math.round((props.totalExpenses / props.totalRevenue) * 100);
});

const avgDailyRevenue = computed(() => {
    if (!props.dailyRevenue?.length) return 0;
    const total = props.dailyRevenue.reduce((s, d) => s + Number(d.value || 0), 0);
    return Math.round(total / props.dailyRevenue.length);
});

const peakDay = computed(() => {
    if (!props.dailyRevenue?.length) return null;
    let max = 0, day = null;
    props.dailyRevenue.forEach(d => {
        if (Number(d.value) > max) { max = Number(d.value); day = d.label; }
    });
    return day;
});

/* ── KPI Cards ─────────────────────────────────────── */
const kpiCards = computed(() => [
    {
        label: isRtl.value ? 'إجمالي الإيرادات' : 'Total Revenue',
        value: formatCurrency(props.totalRevenue),
        icon: 'revenue',
        bg: 'bg-emerald-500/10',
        iconColor: 'text-emerald-600',
        accentColor: 'from-emerald-500 to-emerald-600',
        badgeLabel: isRtl.value ? 'الدخل' : 'Income',
        badgeClass: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: isRtl.value ? 'إجمالي المصروفات' : 'Total Expenses',
        value: formatCurrency(props.totalExpenses),
        icon: 'expenses',
        bg: 'bg-[#C4A265]/10',
        iconColor: 'text-[#C4A265]',
        accentColor: 'from-[#C4A265] to-[#8B7043]',
        badgeLabel: `${expenseRatio.value}% ${isRtl.value ? 'من الإيرادات' : 'of revenue'}`,
        badgeClass: expenseRatio.value > 70 ? 'bg-red-50 text-red-500' : 'bg-amber-50 text-amber-600',
    },
    {
        label: isRtl.value ? 'صافي الدخل' : 'Net Income',
        value: formatCurrency(props.netIncome),
        icon: 'net',
        bg: props.netIncome >= 0 ? 'bg-[#1B365D]/10' : 'bg-red-500/10',
        iconColor: props.netIncome >= 0 ? 'text-[#1B365D]' : 'text-red-600',
        accentColor: props.netIncome >= 0 ? 'from-[#1B365D] to-[#1B365D]' : 'from-red-400 to-red-500',
        badgeLabel: `${profitMargin.value}% ${isRtl.value ? 'هامش ربح' : 'margin'}`,
        badgeClass: profitMargin.value >= 30 ? 'bg-emerald-50 text-emerald-600' : profitMargin.value >= 0 ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-500',
    },
    {
        label: isRtl.value ? 'المبالغ غير المدفوعة' : 'Unpaid Amount',
        value: formatCurrency(props.unpaidInvoices),
        icon: 'unpaid',
        bg: 'bg-[#C4A265]/10',
        iconColor: 'text-amber-600',
        accentColor: 'from-[#C4A265] to-[#8B7043]',
        badgeLabel: isRtl.value ? 'معلقة' : 'Outstanding',
        badgeClass: 'bg-amber-50 text-amber-600',
    },
]);

/* ── Colors ─────────────────────────────────────────── */
const methodColors = ['#C4A265', '#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444', '#06B6D4', '#EC4899'];
const categoryColors = ['#EF4444', '#F59E0B', '#3B82F6', '#8B5CF6', '#10B981', '#EC4899', '#06B6D4', '#C4A265'];
</script>

<template>
    <AdminLayout :title="$t('a_financial_report')">
        <div class="space-y-6">

            <!-- ═════════ Navy Hero Header ═════════ -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                    <div class="flex items-start gap-3 md:gap-4 min-w-0">
                        <Link href="/admin/reports" class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 flex items-center justify-center text-white transition flex-shrink-0">
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </Link>
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                            <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'مالي' : 'Financial' }}</span>
                            </div>
                            <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight truncate">{{ $t('a_financial_report') }}</h1>
                            <p class="text-xs md:text-sm text-white/70 mt-1 max-w-xl">{{ isRtl ? 'الإيرادات والمصروفات والتحليلات المالية' : 'Revenue, expenses and financial analytics' }}</p>
                        </div>
                    </div>
                    <!-- Quick Stats -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <div v-if="avgDailyRevenue" class="px-3 py-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20">
                            <span class="text-[10px] font-medium text-[#C4A265] uppercase tracking-wider block">{{ isRtl ? 'المتوسط اليومي' : 'Avg Daily' }}</span>
                            <span class="text-sm font-bold text-white">{{ formatCurrency(avgDailyRevenue) }}</span>
                        </div>
                        <div v-if="peakDay" class="px-3 py-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20">
                            <span class="text-[10px] font-medium text-[#C4A265] uppercase tracking-wider block">{{ isRtl ? 'أعلى يوم' : 'Peak Day' }}</span>
                            <span class="text-sm font-bold text-white">{{ peakDay }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Tabs -->
            <div v-if="activeModules.length > 1" class="bg-white rounded-xl shadow-sm border border-gray-100 p-1.5 flex gap-1 flex-wrap">
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

            <!-- ── Date Range ─────────────────────────────────── -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 flex flex-wrap gap-3 items-center transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                :style="{ transitionDelay: '80ms' }"
            >
                <div class="w-9 h-9 rounded-xl bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <span class="text-sm font-medium text-gray-700">{{ $t('a_date_range') }}:</span>
                <input v-model="dateFrom" type="date" :max="dateTo || undefined"
                    class="doctorato-input px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none transition-colors" />
                <span class="text-sm text-gray-400">{{ $t('a_to') }}</span>
                <input v-model="dateTo" type="date" :min="dateFrom || undefined"
                    class="doctorato-input px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none transition-colors" />
                <button @click="dateFrom = ''; dateTo = ''" v-if="dateFrom || dateTo"
                    class="ltr:ml-auto rtl:mr-auto px-3 py-2 text-xs font-medium text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-all duration-200">
                    {{ isRtl ? 'مسح' : 'Clear' }}
                </button>
            </div>

            <!-- ── KPI Cards ──────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div
                    v-for="(card, index) in kpiCards"
                    :key="card.label"
                    class="group relative bg-white rounded-2xl p-5 border border-gray-100/80 hover:border-gray-200 transition-all duration-500 overflow-hidden hover:shadow-lg hover:-translate-y-0.5"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transitionDelay: `${150 + index * 80}ms` }"
                >
                    <div :class="`absolute top-0 inset-x-0 h-[3px] bg-gradient-to-r ${card.accentColor} opacity-0 group-hover:opacity-100 transition-opacity duration-500`"></div>
                    <div :class="`absolute -end-6 -top-6 w-24 h-24 rounded-full ${card.bg} opacity-0 group-hover:opacity-100 transition-all duration-500 group-hover:scale-150 blur-2xl`"></div>

                    <div class="relative flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-semibold text-gray-400 uppercase tracking-wider">{{ card.label }}</p>
                            <p class="text-xl md:text-2xl font-bold text-gray-900 mt-1.5 leading-none truncate">{{ card.value }}</p>
                            <div class="mt-2.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="card.badgeClass">
                                    {{ card.badgeLabel }}
                                </span>
                            </div>
                        </div>
                        <div :class="card.bg" class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 transition-all duration-300 group-hover:scale-110">
                            <svg v-if="card.icon === 'revenue'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            <svg v-else-if="card.icon === 'expenses'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <svg v-else-if="card.icon === 'net'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="card.icon === 'unpaid'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Revenue vs Expenses Bar ─────────────────────── -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 md:p-6 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                :style="{ transitionDelay: '500ms' }"
            >
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'الإيرادات مقابل المصروفات' : 'Revenue vs Expenses' }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'نظرة عامة على التوازن المالي' : 'Financial balance overview' }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                            <span class="text-[11px] text-gray-500">{{ isRtl ? 'الإيرادات' : 'Revenue' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-[#C4A265]"></div>
                            <span class="text-[11px] text-gray-500">{{ isRtl ? 'المصروفات' : 'Expenses' }}</span>
                        </div>
                    </div>
                </div>

                <div class="relative h-10 rounded-xl overflow-hidden bg-gray-100">
                    <div
                        v-if="totalRevenue > 0 || totalExpenses > 0"
                        class="absolute inset-y-0 start-0 bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-1000 ease-out rounded-xl"
                        :style="{ width: mounted ? `${Math.max(totalRevenue / (totalRevenue + totalExpenses) * 100, 2)}%` : '0%' }"
                    >
                        <span class="absolute inset-0 flex items-center justify-center text-[11px] font-bold text-white whitespace-nowrap px-2">
                            {{ formatCurrency(totalRevenue) }}
                        </span>
                    </div>
                    <div
                        v-if="totalRevenue > 0 || totalExpenses > 0"
                        class="absolute inset-y-0 end-0 bg-gradient-to-r from-[#C4A265] to-[#8B7043] transition-all duration-1000 ease-out rounded-xl"
                        :style="{ width: mounted ? `${Math.max(totalExpenses / (totalRevenue + totalExpenses) * 100, 2)}%` : '0%' }"
                    >
                        <span class="absolute inset-0 flex items-center justify-center text-[11px] font-bold text-white whitespace-nowrap px-2">
                            {{ formatCurrency(totalExpenses) }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <div :class="netIncome >= 0 ? 'bg-slate-50 text-[#1B365D]' : 'bg-red-50 text-red-500'" class="px-3 py-1 rounded-lg text-[12px] font-bold">
                            {{ isRtl ? 'الصافي' : 'Net' }}: {{ formatCurrency(netIncome) }}
                        </div>
                        <div :class="profitMargin >= 30 ? 'bg-emerald-50 text-emerald-600' : profitMargin >= 0 ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-500'" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold">
                            {{ profitMargin }}% {{ isRtl ? 'هامش ربح' : 'margin' }}
                        </div>
                    </div>
                    <div class="text-[11px] text-gray-400">
                        {{ isRtl ? 'نسبة المصروفات' : 'Expense ratio' }}: {{ expenseRatio }}%
                    </div>
                </div>
            </div>

            <!-- ── Daily Revenue BarChart ──────────────────────── -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 md:p-6 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                :style="{ transitionDelay: '600ms' }"
            >
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_daily_revenue') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'اتجاه الإيرادات خلال الفترة المحددة' : 'Revenue trend for the selected period' }}</p>
                    </div>
                    <div v-if="dailyRevenue?.length" class="flex items-center gap-3">
                        <div class="text-end">
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الأيام' : 'Days' }}</p>
                            <p class="text-sm font-bold text-gray-900">{{ dailyRevenue.length }}</p>
                        </div>
                        <div class="w-px h-8 bg-gray-200"></div>
                        <div class="text-end">
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المتوسط' : 'Average' }}</p>
                            <p class="text-sm font-bold text-[#C4A265]">{{ formatCurrency(avgDailyRevenue) }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="dailyRevenueChartData.length">
                    <BarChart
                        :data="dailyRevenueChartData"
                        color="#C4A265"
                        :height="260"
                        :formatValue="(v) => formatCurrency(v)"
                    />
                </div>
                <div v-else class="flex flex-col items-center justify-center py-16">
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات إيرادات لهذه الفترة' : 'No revenue data for this period' }}</p>
                </div>
            </div>

            <!-- ── Two-column: Revenue & Expenses ─────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Revenue by Payment Method -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden transition-all duration-700"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transitionDelay: '700ms' }"
                >
                    <div class="p-4 md:p-6 pb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_revenue_by_method') }}</h2>
                                <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'توزيع قنوات الدفع' : 'Payment channels breakdown' }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-[#C4A265]/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                            </div>
                        </div>
                    </div>

                    <div v-if="revenueMethodChartData.length" class="px-4 md:px-6 pb-4">
                        <DonutChart
                            :data="revenueMethodChartData"
                            :size="160"
                            :formatValue="(v) => formatCurrency(v)"
                            :centerLabel="isRtl ? 'الإجمالي' : 'Total'"
                        />
                    </div>

                    <div class="border-t border-gray-100">
                        <div v-if="revenueByMethod?.length" class="divide-y divide-gray-50">
                            <div
                                v-for="(item, i) in revenueByMethod"
                                :key="item.label"
                                class="px-4 md:px-6 py-3 hover:bg-gray-50/50 transition-colors duration-200"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: methodColors[i % methodColors.length] }"></div>
                                        <span class="text-sm font-medium text-gray-800 capitalize">{{ item.label || (isRtl ? 'غير معروف' : 'Unknown') }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[11px] text-gray-400">{{ item.count || 0 }} {{ isRtl ? 'عملية' : 'txns' }}</span>
                                        <span class="text-sm font-bold text-[#C4A265]">{{ formatCurrency(item.value) }}</span>
                                    </div>
                                </div>
                                <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-1000 ease-out"
                                        :style="{
                                            width: mounted ? `${(item.value / maxRevenueMethod) * 100}%` : '0%',
                                            backgroundColor: methodColors[i % methodColors.length],
                                            transitionDelay: `${800 + i * 100}ms`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-4 md:px-6 py-10 text-center text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات إيرادات' : 'No revenue data found' }}</div>

                        <div v-if="revenueByMethod?.length" class="px-4 md:px-6 py-3 bg-gray-50/80 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-gray-700">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-gray-400">{{ revenueByMethod.reduce((s, d) => s + (d.count || 0), 0) }} {{ isRtl ? 'عملية' : 'txns' }}</span>
                                    <span class="text-sm font-bold text-[#C4A265]">{{ formatCurrency(totalRevenueByMethod) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expenses by Category -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden transition-all duration-700"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transitionDelay: '800ms' }"
                >
                    <div class="p-4 md:p-6 pb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_expenses_by_category') }}</h2>
                                <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'توزيع الإنفاق حسب الفئة' : 'Spending breakdown by category' }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-[#C4A265]/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                            </div>
                        </div>
                    </div>

                    <div v-if="expenseCategoryChartData.length" class="px-4 md:px-6 pb-4">
                        <DonutChart
                            :data="expenseCategoryChartData"
                            :size="160"
                            :formatValue="(v) => formatCurrency(v)"
                            :centerLabel="isRtl ? 'الإجمالي' : 'Total'"
                        />
                    </div>

                    <div class="border-t border-gray-100">
                        <div v-if="expensesByCategory?.length" class="divide-y divide-gray-50">
                            <div
                                v-for="(item, i) in expensesByCategory"
                                :key="item.label"
                                class="px-4 md:px-6 py-3 hover:bg-gray-50/50 transition-colors duration-200"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: categoryColors[i % categoryColors.length] }"></div>
                                        <span class="text-sm font-medium text-gray-800 capitalize">{{ item.label || (isRtl ? 'غير مصنف' : 'Uncategorized') }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[11px] text-gray-400">{{ item.count || 0 }} {{ isRtl ? 'عنصر' : 'items' }}</span>
                                        <span class="text-sm font-bold text-[#C4A265]">{{ formatCurrency(item.value) }}</span>
                                    </div>
                                </div>
                                <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-1000 ease-out"
                                        :style="{
                                            width: mounted ? `${(item.value / maxExpenseCategory) * 100}%` : '0%',
                                            backgroundColor: categoryColors[i % categoryColors.length],
                                            transitionDelay: `${900 + i * 100}ms`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-4 md:px-6 py-10 text-center text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات مصروفات' : 'No expense data found' }}</div>

                        <div v-if="expensesByCategory?.length" class="px-4 md:px-6 py-3 bg-gray-50/80 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-gray-700">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-gray-400">{{ expensesByCategory.reduce((s, d) => s + (d.count || 0), 0) }} {{ isRtl ? 'عنصر' : 'items' }}</span>
                                    <span class="text-sm font-bold text-[#C4A265]">{{ formatCurrency(totalExpensesByCategory) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Financial Summary ──────────────────────────── -->
            <div
                class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-4 md:p-6 text-white transition-all duration-700 overflow-hidden relative"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                :style="{ transitionDelay: '950ms' }"
            >
                <div class="absolute inset-0 opacity-5">
                    <svg class="w-full h-full" viewBox="0 0 400 200">
                        <defs>
                            <pattern id="finGrid" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                                <circle cx="20" cy="20" r="1" fill="white" />
                            </pattern>
                        </defs>
                        <rect fill="url(#finGrid)" width="400" height="200" />
                    </svg>
                </div>

                <div class="relative">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" /></svg>
                        </div>
                        <h2 class="text-[15px] font-semibold">{{ isRtl ? 'ملخص مالي' : 'Financial Summary' }}</h2>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white/5 backdrop-blur rounded-xl p-4 border border-white/10">
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الإيرادات' : 'Revenue' }}</p>
                            <p class="text-lg font-bold text-emerald-400">{{ formatCurrency(totalRevenue) }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur rounded-xl p-4 border border-white/10">
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'المصروفات' : 'Expenses' }}</p>
                            <p class="text-lg font-bold text-amber-400">{{ formatCurrency(totalExpenses) }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur rounded-xl p-4 border border-white/10">
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_net_income') }}</p>
                            <p class="text-lg font-bold" :class="netIncome >= 0 ? 'text-slate-400' : 'text-red-400'">{{ formatCurrency(netIncome) }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur rounded-xl p-4 border border-white/10">
                            <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'غير مدفوع' : 'Unpaid' }}</p>
                            <p class="text-lg font-bold text-amber-400">{{ formatCurrency(unpaidInvoices) }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <div class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-[11px]">
                            <span class="text-gray-400">{{ isRtl ? 'هامش الربح:' : 'Profit Margin:' }}</span>
                            <span class="ms-1 font-bold" :class="profitMargin >= 30 ? 'text-emerald-400' : profitMargin >= 0 ? 'text-amber-400' : 'text-red-400'">{{ profitMargin }}%</span>
                        </div>
                        <div class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-[11px]">
                            <span class="text-gray-400">{{ isRtl ? 'نسبة المصروفات:' : 'Expense Ratio:' }}</span>
                            <span class="ms-1 font-bold" :class="expenseRatio <= 50 ? 'text-emerald-400' : expenseRatio <= 70 ? 'text-amber-400' : 'text-red-400'">{{ expenseRatio }}%</span>
                        </div>
                        <div v-if="revenueByMethod?.length" class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-[11px]">
                            <span class="text-gray-400">{{ isRtl ? 'طرق الدفع:' : 'Payment Methods:' }}</span>
                            <span class="ms-1 font-bold text-[#C4A265]">{{ revenueByMethod.length }}</span>
                        </div>
                        <div v-if="expensesByCategory?.length" class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-[11px]">
                            <span class="text-gray-400">{{ isRtl ? 'فئات المصروفات:' : 'Expense Categories:' }}</span>
                            <span class="ms-1 font-bold text-[#C4A265]">{{ expensesByCategory.length }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
