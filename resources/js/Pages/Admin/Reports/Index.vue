<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
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
    summary: Object,
    dailyRevenue: Object,
    topServices: Array,
    topDoctors: Array,
    monthLabel: String,
    modules: Object,
    moduleComparison: Object,
    filters: Object,
});

/* ── Module Tabs ──────────────────────────────────── */
const moduleFilter = ref(props.filters?.module || '');
const activeModules = computed(() => {
    if (!props.modules) return [];
    return Object.values(props.modules).filter(m => m.enabled);
});

watch(moduleFilter, (val) => {
    router.get('/admin/reports', {
        module: val || undefined,
    }, { preserveState: true, replace: true });
});

/* ── Animations ────────────────────────────────────── */
const mounted = ref(false);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

/* ── Growth calculations ───────────────────────────── */
function calcGrowth(current, previous) {
    if (!previous || previous === 0) return current > 0 ? 100 : 0;
    return Math.round(((current - previous) / previous) * 100);
}

const revenueGrowth  = computed(() => calcGrowth(props.summary?.revenue, props.summary?.prev_revenue));
const expenseGrowth  = computed(() => calcGrowth(props.summary?.expenses, props.summary?.prev_expenses));
const visitGrowth    = computed(() => calcGrowth(props.summary?.visits, props.summary?.prev_visits));
const patientGrowth  = computed(() => calcGrowth(props.summary?.new_patients, props.summary?.prev_patients));

/* ── KPI Cards ─────────────────────────────────────── */
const kpiCards = computed(() => [
    {
        label: t('a_total_revenue'),
        value: formatCurrency(props.summary?.revenue),
        growth: revenueGrowth.value,
        icon: 'revenue',
        color: 'emerald',
        gradient: 'from-emerald-500 to-teal-600',
        bg: 'bg-emerald-500/10',
        ring: 'ring-emerald-500/20',
    },
    {
        label: t('a_total_expenses'),
        value: formatCurrency(props.summary?.expenses),
        growth: expenseGrowth.value,
        invertGrowth: true,
        icon: 'expenses',
        color: 'rose',
        gradient: 'from-rose-500 to-pink-600',
        bg: 'bg-rose-500/10',
        ring: 'ring-rose-500/20',
    },
    {
        label: t('a_net_income'),
        value: formatCurrency(props.summary?.net_income),
        subtitle: props.summary?.net_income >= 0 ? t('a_profit') : t('a_loss'),
        icon: 'net',
        color: props.summary?.net_income >= 0 ? 'blue' : 'red',
        gradient: props.summary?.net_income >= 0 ? 'from-blue-500 to-indigo-600' : 'from-red-500 to-rose-600',
        bg: props.summary?.net_income >= 0 ? 'bg-blue-500/10' : 'bg-red-500/10',
        ring: props.summary?.net_income >= 0 ? 'ring-blue-500/20' : 'ring-red-500/20',
    },
    {
        label: t('a_patient_visits'),
        value: props.summary?.visits || 0,
        growth: visitGrowth.value,
        extra: `${props.summary?.completed_visits || 0} ${t('a_completed')}`,
        icon: 'visits',
        color: 'violet',
        gradient: 'from-violet-500 to-purple-600',
        bg: 'bg-violet-500/10',
        ring: 'ring-violet-500/20',
    },
    {
        label: t('a_new_patients'),
        value: props.summary?.new_patients || 0,
        growth: patientGrowth.value,
        extra: `${props.summary?.total_patients || 0} ${t('a_total')}`,
        icon: 'patients',
        color: 'amber',
        gradient: 'from-amber-500 to-orange-600',
        bg: 'bg-amber-500/10',
        ring: 'ring-amber-500/20',
    },
    {
        label: t('a_unpaid_invoices'),
        value: props.summary?.unpaid_count || 0,
        extra: formatCurrency(props.summary?.unpaid_amount),
        icon: 'unpaid',
        color: 'red',
        gradient: 'from-red-400 to-rose-500',
        bg: 'bg-red-500/10',
        ring: 'ring-red-500/20',
    },
]);

/* ── Report Links ──────────────────────────────────── */
const reportLinks = computed(() => [
    {
        title: t('a_financial_report'),
        description: t('a_financial_report_desc'),
        href: '/admin/reports/financial',
        gradient: 'from-[#C4A265] to-[#A68B52]',
        bg: 'bg-[#C4A265]',
        icon: 'financial',
    },
    {
        title: t('a_doctor_performance'),
        description: t('a_doctor_performance_desc'),
        href: '/admin/reports/doctors',
        gradient: 'from-blue-500 to-indigo-600',
        bg: 'bg-blue-500',
        icon: 'doctor',
    },
    {
        title: t('a_patient_analytics'),
        description: t('a_patient_analytics_desc'),
        href: '/admin/reports/patients',
        gradient: 'from-emerald-500 to-teal-600',
        bg: 'bg-emerald-500',
        icon: 'patient',
    },
    {
        title: t('a_service_analytics'),
        description: t('a_service_analytics_desc'),
        href: '/admin/reports/services',
        gradient: 'from-violet-500 to-purple-600',
        bg: 'bg-violet-500',
        icon: 'service',
    },
    ...(props.modules?.dental?.enabled ? [{
        title: isRtl.value ? 'تقارير الأسنان' : 'Dental Reports',
        description: isRtl.value ? 'إيرادات، أداء المعمل، تقدم العلاج' : 'Revenue, lab performance, treatment progress',
        href: '/admin/reports/dental',
        gradient: 'from-cyan-500 to-teal-600',
        bg: 'bg-cyan-500',
        icon: 'dental',
    }] : []),
]);

/* ── Export Links ──────────────────────────────────── */
const exportLinks = computed(() => [
    { label: t('a_patients'),    href: '/admin/exports/patients',    icon: 'exportPatients' },
    { label: t('a_visits'),      href: '/admin/exports/visits',      icon: 'exportVisits' },
    { label: t('a_invoices'),    href: '/admin/exports/invoices',    icon: 'exportInvoices' },
    { label: t('a_payments'),    href: '/admin/exports/payments',    icon: 'exportPayments' },
    { label: t('a_commissions'), href: '/admin/exports/commissions', icon: 'exportCommissions' },
    ...(props.modules?.dental?.enabled ? [
        { label: isRtl.value ? 'علاجات الأسنان' : 'Dental Treatments', href: '/admin/exports/dental-treatments', icon: 'exportDental' },
        { label: isRtl.value ? 'طلبات المعمل' : 'Dental Lab Orders', href: '/admin/exports/dental-lab-orders', icon: 'exportDental' },
    ] : []),
]);

/* ── Sparkline ─────────────────────────────────────── */
const sparklinePath = computed(() => {
    if (!props.dailyRevenue) return '';
    const values = Object.values(props.dailyRevenue).map(Number);
    if (values.length < 2) return '';
    const max = Math.max(...values, 1);
    const w = 200;
    const h = 40;
    const step = w / (values.length - 1);
    return values.map((v, i) => {
        const x = i * step;
        const y = h - (v / max) * h;
        return `${i === 0 ? 'M' : 'L'}${x},${y}`;
    }).join(' ');
});

/* ── Top service max for bar width ─────────────────── */
const maxServiceVisits = computed(() => {
    if (!props.topServices?.length) return 1;
    return Math.max(...props.topServices.map(s => s.visits_count), 1);
});
</script>

<template>
    <AdminLayout :title="$t('a_reports')">
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

            <!-- ── Header ──────────────────────────────────────── -->
            <div
                class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center shadow-lg shadow-[#C4A265]/20">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_reports') }}</h1>
                            <p class="text-sm text-gray-500">{{ $t('a_performance_overview') }}</p>
                        </div>
                    </div>
                </div>
                <!-- Mini sparkline -->
                <div v-if="sparklinePath" class="bg-white rounded-xl px-4 py-3 border border-gray-100 shadow-sm">
                    <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_revenue_trend') }}</p>
                    <svg viewBox="0 0 200 40" class="w-48 h-10">
                        <defs>
                            <linearGradient id="sparkGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#C4A265" stop-opacity="0.3" />
                                <stop offset="100%" stop-color="#C4A265" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <path :d="sparklinePath + ' L200,40 L0,40 Z'" fill="url(#sparkGrad)" />
                        <path :d="sparklinePath" fill="none" stroke="#C4A265" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
            </div>

            <!-- ── KPI Summary Cards ──────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    v-for="(card, index) in kpiCards"
                    :key="card.label"
                    class="group relative bg-white rounded-2xl p-6 border border-gray-100/80 hover:border-gray-200 transition-all duration-500 overflow-hidden hover:shadow-lg hover:-translate-y-0.5"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transitionDelay: `${100 + index * 80}ms` }"
                >
                    <!-- Gradient accent line -->
                    <div :class="`absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r ${card.gradient} opacity-0 group-hover:opacity-100 transition-opacity duration-500`"></div>

                    <!-- Background decoration -->
                    <div :class="`absolute -right-6 -top-6 w-24 h-24 rounded-full ${card.bg} opacity-0 group-hover:opacity-100 transition-all duration-500 group-hover:scale-150 blur-2xl`"></div>

                    <div class="relative flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-[13px] font-medium text-gray-400 uppercase tracking-wider">{{ card.label }}</p>
                            <p class="text-[28px] font-bold text-gray-900 mt-2 leading-none">{{ card.value }}</p>

                            <!-- Growth badge -->
                            <div class="flex items-center gap-2 mt-3">
                                <span
                                    v-if="card.growth !== undefined"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                    :class="(card.invertGrowth ? card.growth <= 0 : card.growth >= 0)
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-red-50 text-red-500'"
                                >
                                    <svg class="w-3 h-3" :class="card.growth >= 0 ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                    </svg>
                                    {{ Math.abs(card.growth) }}%
                                </span>
                                <span v-if="card.subtitle" class="text-[11px] font-medium" :class="card.subtitle === 'Profit' ? 'text-emerald-500' : 'text-red-500'">
                                    {{ card.subtitle }}
                                </span>
                                <span v-if="card.extra" class="text-[11px] text-gray-400">
                                    {{ card.extra }}
                                </span>
                                <span v-if="card.growth !== undefined" class="text-[10px] text-gray-300">{{ $t('a_vs_last_month') }}</span>
                            </div>
                        </div>

                        <!-- Icon -->
                        <div :class="card.bg" class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 ring-1 transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg" :style="{ '--tw-ring-color': 'transparent' }">
                            <!-- Revenue -->
                            <svg v-if="card.icon === 'revenue'" class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            <!-- Expenses -->
                            <svg v-else-if="card.icon === 'expenses'" class="w-7 h-7 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <!-- Net -->
                            <svg v-else-if="card.icon === 'net'" class="w-7 h-7" :class="summary?.net_income >= 0 ? 'text-blue-600' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Visits -->
                            <svg v-else-if="card.icon === 'visits'" class="w-7 h-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                            </svg>
                            <!-- Patients -->
                            <svg v-else-if="card.icon === 'patients'" class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                            </svg>
                            <!-- Unpaid -->
                            <svg v-else-if="card.icon === 'unpaid'" class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Module Comparison (Dermatology vs Dental vs Pediatric) ──── -->
            <div
                v-if="moduleComparison && !moduleFilter"
                class="bg-white rounded-2xl border border-gray-100/80 p-6 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                :style="{ transitionDelay: '600ms' }"
            >
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-violet-500 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">{{ isRtl ? 'مقارنة الأقسام' : 'Module Comparison' }}</h3>
                        <p class="text-[11px] text-gray-400">{{ isRtl ? 'الجلدية مقابل الأسنان مقابل الأطفال — هذا الشهر' : 'Dermatology vs Dental vs Pediatric — This Month' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Revenue Comparison -->
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">{{ isRtl ? 'الإيرادات' : 'Revenue' }}</p>
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>
                                        {{ isRtl ? 'الجلدية' : 'Dermatology' }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-900">{{ formatCurrency(moduleComparison.derma?.revenue || 0) }}</span>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-violet-400 to-violet-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.derma?.revenue || 0) / Math.max((moduleComparison.derma?.revenue || 0) + (moduleComparison.dental?.revenue || 0) + (moduleComparison.pediatric?.revenue || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
                                        {{ isRtl ? 'الأسنان' : 'Dental' }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-900">{{ formatCurrency(moduleComparison.dental?.revenue || 0) }}</span>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-cyan-400 to-cyan-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.dental?.revenue || 0) / Math.max((moduleComparison.derma?.revenue || 0) + (moduleComparison.dental?.revenue || 0) + (moduleComparison.pediatric?.revenue || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                        {{ isRtl ? 'أطفال' : 'Pediatric' }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-900">{{ formatCurrency(moduleComparison.pediatric?.revenue || 0) }}</span>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.pediatric?.revenue || 0) / Math.max((moduleComparison.derma?.revenue || 0) + (moduleComparison.dental?.revenue || 0) + (moduleComparison.pediatric?.revenue || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visits Comparison -->
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">{{ isRtl ? 'الزيارات' : 'Visits' }}</p>
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>
                                        {{ isRtl ? 'الجلدية' : 'Dermatology' }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-900">{{ moduleComparison.derma?.visits || 0 }}</span>
                                        <span class="text-[10px] text-gray-400">({{ moduleComparison.derma?.completed || 0 }} {{ isRtl ? 'مكتمل' : 'done' }})</span>
                                    </div>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-violet-400 to-violet-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.derma?.visits || 0) / Math.max((moduleComparison.derma?.visits || 0) + (moduleComparison.dental?.visits || 0) + (moduleComparison.pediatric?.visits || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
                                        {{ isRtl ? 'الأسنان' : 'Dental' }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-900">{{ moduleComparison.dental?.visits || 0 }}</span>
                                        <span class="text-[10px] text-gray-400">({{ moduleComparison.dental?.completed || 0 }} {{ isRtl ? 'مكتمل' : 'done' }})</span>
                                    </div>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-cyan-400 to-cyan-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.dental?.visits || 0) / Math.max((moduleComparison.derma?.visits || 0) + (moduleComparison.dental?.visits || 0) + (moduleComparison.pediatric?.visits || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                        {{ isRtl ? 'أطفال' : 'Pediatric' }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-900">{{ moduleComparison.pediatric?.visits || 0 }}</span>
                                        <span class="text-[10px] text-gray-400">({{ moduleComparison.pediatric?.completed || 0 }} {{ isRtl ? 'مكتمل' : 'done' }})</span>
                                    </div>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.pediatric?.visits || 0) / Math.max((moduleComparison.derma?.visits || 0) + (moduleComparison.dental?.visits || 0) + (moduleComparison.pediatric?.visits || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patients Comparison -->
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">{{ isRtl ? 'المرضى' : 'Patients' }}</p>
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>
                                        {{ isRtl ? 'الجلدية' : 'Dermatology' }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-900">{{ moduleComparison.derma?.patients || 0 }}</span>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-violet-400 to-violet-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.derma?.patients || 0) / Math.max((moduleComparison.derma?.patients || 0) + (moduleComparison.dental?.patients || 0) + (moduleComparison.pediatric?.patients || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
                                        {{ isRtl ? 'الأسنان' : 'Dental' }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-900">{{ moduleComparison.dental?.patients || 0 }}</span>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-cyan-400 to-cyan-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.dental?.patients || 0) / Math.max((moduleComparison.derma?.patients || 0) + (moduleComparison.dental?.patients || 0) + (moduleComparison.pediatric?.patients || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                        {{ isRtl ? 'أطفال' : 'Pediatric' }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-900">{{ moduleComparison.pediatric?.patients || 0 }}</span>
                                </div>
                                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all duration-1000" :style="{ width: mounted ? `${Math.min(((moduleComparison.pediatric?.patients || 0) / Math.max((moduleComparison.derma?.patients || 0) + (moduleComparison.dental?.patients || 0) + (moduleComparison.pediatric?.patients || 0), 1)) * 100, 100)}%` : '0%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Detailed Report Links ─────────────────────── -->
            <div>
                <h2
                    class="text-lg font-bold text-gray-900 mb-4 transition-all duration-700"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    :style="{ transitionDelay: '600ms' }"
                >{{ $t('a_detailed_reports') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <Link
                        v-for="(report, index) in reportLinks"
                        :key="report.href"
                        :href="report.href"
                        class="group relative bg-white rounded-2xl border border-gray-100/80 overflow-hidden hover:shadow-xl transition-all duration-500 hover:-translate-y-1"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                        :style="{ transitionDelay: `${700 + index * 100}ms` }"
                    >
                        <div class="flex items-stretch">
                            <!-- Color accent side -->
                            <div :class="`bg-gradient-to-b ${report.gradient} w-1.5 group-hover:w-2 transition-all duration-300`"></div>

                            <div class="flex-1 p-6 flex items-center gap-5">
                                <!-- Icon circle -->
                                <div :class="`${report.bg} w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-current/10 group-hover:scale-110 group-hover:rotate-3 transition-all duration-500`">
                                    <svg v-if="report.icon === 'financial'" class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else-if="report.icon === 'doctor'" class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <svg v-else-if="report.icon === 'patient'" class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                    <svg v-else-if="report.icon === 'service'" class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 5.607c.28 1.12-.728 2.093-1.867 2.093H4.665c-1.14 0-2.147-.973-1.867-2.093L4.2 15.3" />
                                    </svg>
                                    <svg v-else-if="report.icon === 'dental'" class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2C9.5 2 7 4 7 7c0 2-.5 4-1 6s-1 5 0 7c.5 1 1.5 2 3 2s2-1.5 3-4c1 2.5 1.5 4 3 4s2.5-1 3-2c1-2 .5-5 0-7s-1-4-1-6c0-3-2.5-5-5-5z" />
                                    </svg>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h3 class="text-[15px] font-bold text-gray-900 group-hover:text-[#C4A265] transition-colors duration-300">{{ report.title }}</h3>
                                    <p class="text-[12px] text-gray-400 mt-1 leading-relaxed">{{ report.description }}</p>
                                </div>

                                <!-- Arrow -->
                                <div class="w-10 h-10 rounded-xl bg-gray-50 group-hover:bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0 transition-all duration-300">
                                    <svg class="w-5 h-5 text-gray-300 group-hover:text-[#C4A265] group-hover:translate-x-0.5 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- ── Bottom Row: Top Services + Top Doctors + Exports ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Top Services -->
                <div
                    class="bg-white rounded-2xl border border-gray-100/80 p-6 transition-all duration-700"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transitionDelay: '1100ms' }"
                >
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">{{ $t('a_top_services') }}</h3>
                        <Link href="/admin/reports/services" class="text-[11px] font-medium text-[#C4A265] hover:underline">{{ $t('a_view_all') }}</Link>
                    </div>
                    <div v-if="topServices?.length" class="space-y-4">
                        <div v-for="(service, i) in topServices" :key="service.id" class="group/item">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[13px] font-medium text-gray-700 truncate flex-1">
                                    <span class="text-gray-300 mr-2">{{ i + 1 }}.</span>
                                    {{ service.name_en }}
                                </span>
                                <span class="text-[12px] font-bold text-gray-900 ml-2">{{ service.visits_count }}</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-[#C4A265] to-[#D4B87A] transition-all duration-1000 ease-out"
                                    :style="{ width: mounted ? `${(service.visits_count / maxServiceVisits) * 100}%` : '0%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-8">{{ $t('a_no_service_data') }}</p>
                </div>

                <!-- Top Doctors -->
                <div
                    class="bg-white rounded-2xl border border-gray-100/80 p-6 transition-all duration-700"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transitionDelay: '1200ms' }"
                >
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">{{ $t('a_top_doctors') }}</h3>
                        <Link href="/admin/reports/doctors" class="text-[11px] font-medium text-[#C4A265] hover:underline">{{ $t('a_view_all') }}</Link>
                    </div>
                    <div v-if="topDoctors?.length" class="space-y-3">
                        <div v-for="(doc, i) in topDoctors" :key="doc.id" class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 transition-colors duration-200">
                            <div class="relative">
                                <div v-if="doc.photo" class="w-10 h-10 rounded-xl overflow-hidden ring-2 ring-gray-100">
                                    <img :src="`/storage/${doc.photo}`" :alt="doc.name_en" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center text-white text-sm font-bold ring-2 ring-[#C4A265]/20">
                                    {{ doc.name_en?.charAt(0) || 'D' }}
                                </div>
                                <span class="absolute -top-1 -left-1 w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center"
                                    :class="i === 0 ? 'bg-amber-400 text-white' : i === 1 ? 'bg-gray-300 text-white' : i === 2 ? 'bg-amber-700 text-white' : 'bg-gray-100 text-gray-500'"
                                >{{ i + 1 }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-semibold text-gray-800 truncate">Dr. {{ doc.name_en }}</p>
                                <p class="text-[11px] text-gray-400">{{ doc.visits_count }} {{ $t('a_completed_visits') }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-8">{{ $t('a_no_doctor_data') }}</p>
                </div>

                <!-- Export Section -->
                <div
                    class="bg-white rounded-2xl border border-gray-100/80 p-6 transition-all duration-700"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transitionDelay: '1300ms' }"
                >
                    <div class="flex items-center gap-2 mb-5">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">{{ $t('a_export_data') }}</h3>
                    </div>
                    <div class="space-y-2">
                        <a
                            v-for="exp in exportLinks"
                            :key="exp.href"
                            :href="exp.href"
                            class="group/exp flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-emerald-200 hover:bg-emerald-50/50 transition-all duration-200"
                        >
                            <div class="w-9 h-9 rounded-lg bg-emerald-50 group-hover/exp:bg-emerald-100 flex items-center justify-center flex-shrink-0 transition-colors duration-200">
                                <!-- Users -->
                                <svg v-if="exp.icon === 'exportPatients'" class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                <!-- Calendar -->
                                <svg v-else-if="exp.icon === 'exportVisits'" class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                <!-- Receipt -->
                                <svg v-else-if="exp.icon === 'exportInvoices'" class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                                <!-- Cash -->
                                <svg v-else-if="exp.icon === 'exportPayments'" class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                <!-- Chart -->
                                <svg v-else-if="exp.icon === 'exportCommissions'" class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                            <div class="flex-1">
                                <span class="text-[13px] font-medium text-gray-700 group-hover/exp:text-emerald-700 transition-colors">{{ exp.label }}</span>
                                <span class="block text-[10px] text-gray-400">{{ $t('a_xlsx_format') }}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 group-hover/exp:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
