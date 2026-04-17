<script setup>
import { ref, computed, onMounted } from 'vue';
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
    stats: Object,
    todayAppointments: Array,
    pendingPlans: Array,
    pendingLabOrders: Array,
    medicalAlerts: Object,
    pendingFollowups: Array,
    overdueFollowups: Number,
    criticalAlerts: Array,
    doctorPerformance: Array,
});

/* ── Animated counters ─────────────────────────────────── */
const animatedStats = ref({
    today_appointments: 0,
    today_completed: 0,
    active_treatment_plans: 0,
    pending_lab_orders: 0,
    monthly_revenue: 0,
    treatment_completion_rate: 0,
    risk_patients: 0,
});

function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

function animateCounter(key, target, duration = 1200) {
    const start = performance.now();
    const from = 0;
    function tick(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        animatedStats.value[key] = Math.round(from + (target - from) * easeOutCubic(progress));
        if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

onMounted(() => {
    setTimeout(() => {
        animateCounter('today_appointments', props.stats?.today_appointments ?? 0);
        animateCounter('today_completed', props.stats?.today_completed ?? 0, 1400);
        animateCounter('active_treatment_plans', props.stats?.active_treatment_plans ?? 0, 1600);
        animateCounter('pending_lab_orders', props.stats?.pending_lab_orders ?? 0, 1800);
        animateCounter('monthly_revenue', props.stats?.monthly_revenue ?? 0, 2000);
        animateCounter('treatment_completion_rate', props.stats?.treatment_completion_rate ?? 0, 1500);
        animateCounter('risk_patients', props.stats?.risk_patients ?? 0, 1600);
    }, 200);
});

/* ── Helpers ────────────────────────────────────────────── */

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(time) {
    if (!time) return '-';
    return time;
}

/* ── Stat cards config ─────────────────────────────────── */
const statCards = computed(() => [
    {
        key: 'today_appointments',
        labelKey: 'a_today_appointments',
        value: animatedStats.value.today_appointments,
        isCurrency: false,
        gradient: 'from-[#1B365D] to-[#1B365D]',
        lightBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
        icon: 'calendar',
    },
    {
        key: 'today_completed',
        labelKey: 'a_completed_today',
        value: animatedStats.value.today_completed,
        isCurrency: false,
        gradient: 'from-emerald-500 to-emerald-600',
        lightBg: 'bg-emerald-50',
        iconColor: 'text-emerald-500',
        icon: 'check',
    },
    {
        key: 'active_treatment_plans',
        labelKey: 'a_active_plans',
        value: animatedStats.value.active_treatment_plans,
        isCurrency: false,
        gradient: 'from-[#1B365D] to-[#1B365D]',
        lightBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
        icon: 'clipboard',
    },
    {
        key: 'pending_lab_orders',
        labelKey: 'a_pending_lab_orders',
        value: animatedStats.value.pending_lab_orders,
        isCurrency: false,
        gradient: 'from-amber-500 to-amber-600',
        lightBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        icon: 'lab',
        badge: props.stats?.overdue_lab_orders > 0 ? props.stats.overdue_lab_orders : null,
    },
    {
        key: 'monthly_revenue',
        labelKey: 'a_monthly_revenue',
        value: formatCurrency(animatedStats.value.monthly_revenue),
        isCurrency: true,
        gradient: 'from-emerald-500 to-emerald-600',
        lightBg: 'bg-emerald-50',
        iconColor: 'text-emerald-500',
        icon: 'revenue',
    },
    {
        key: 'treatment_completion_rate',
        label: isRtl.value ? 'نسبة الإنجاز الشهرية' : 'Monthly Completion Rate',
        value: animatedStats.value.treatment_completion_rate + '%',
        isCurrency: false,
        gradient: 'from-[#1B365D] to-[#1B365D]',
        lightBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
        icon: 'check',
    },
    {
        key: 'risk_patients',
        label: isRtl.value ? 'مرضى عالي الخطورة' : 'High-Risk Patients',
        value: animatedStats.value.risk_patients,
        isCurrency: false,
        gradient: 'from-red-500 to-[#C4A265]',
        lightBg: 'bg-red-50',
        iconColor: 'text-red-500',
        icon: 'alert',
        badge: props.stats?.risk_patients > 0 ? props.stats.risk_patients : null,
    },
]);

/* ── Status styles ─────────────────────────────────────── */
const statusStyles = {
    waiting: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', border: 'border-amber-200' },
    in_progress: { bg: 'bg-slate-50', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]', border: 'border-slate-200' },
    completed: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', border: 'border-emerald-200' },
    cancelled: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', border: 'border-red-200' },
};

const labStatusStyles = {
    ordered: { bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-400' },
    in_production: { bg: 'bg-slate-50', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    ready: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    delivered: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    adjustment: { bg: 'bg-[#F5E7C8]/40', text: 'text-[#8B7043]', dot: 'bg-[#C4A265]' },
    completed: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
};

function getStatusStyle(status) { return statusStyles[status] || statusStyles.waiting; }
function getLabStatusStyle(status) { return labStatusStyles[status] || labStatusStyles.ordered; }

/* ── Medical Alert helpers ────────────────────────────── */
const expandedAlerts = ref({});
function hasAlert(visitId) { return !!(props.medicalAlerts && props.medicalAlerts[visitId]); }
function getAlertFlags(visitId) { return (props.medicalAlerts && props.medicalAlerts[visitId]) || []; }
function toggleAlert(visitId) { expandedAlerts.value[visitId] = !expandedAlerts.value[visitId]; }

const severityStyles = {
    high: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-500' },
    medium: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' },
    low: { bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]' },
};

function daysUntil(dateStr) {
    if (!dateStr) return null;
    return Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
}

/* ── Quick links ───────────────────────────────────────── */
const quickLinks = computed(() => [
    { href: '/admin/dental/treatments', labelKey: 'a_dental_treatments', icon: 'treatment', color: 'from-[#2C4E7A] to-[#1B365D]' },
    { href: '/admin/dental/treatment-plans', labelKey: 'a_treatment_plans', icon: 'plan', color: 'from-[#2C4E7A] to-[#1B365D]' },
    { href: '/admin/dental/lab-orders', labelKey: 'a_lab_orders', icon: 'lab', color: 'from-amber-400 to-amber-500' },
    { href: '/admin/dental/xrays', labelKey: 'a_xrays', icon: 'xray', color: 'from-[#2C4E7A] to-[#1B365D]' },
    { href: '/admin/dental/chart-search', labelKey: 'a_dental_chart', icon: 'chart', color: 'from-emerald-400 to-emerald-500' },
    { href: '/admin/dental/lab-orders/dashboard', labelKey: 'a_lab_dashboard', icon: 'dashboard', color: 'from-emerald-400 to-emerald-500' },
    { href: '/admin/dental/smart-notifications', label: locale.value === 'ar' ? 'تنبيهات ذكية' : 'Smart Alerts', icon: 'notification', color: 'from-[#D4B57E] to-[#C4A265]' },
]);
</script>

<template>
    <AdminLayout :title="$t('a_dental_dashboard')">
        <div class="space-y-8">
            <!-- ── Hero Header ───────────────────────────────────── -->
            <div class="dental-hero relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl p-8 md:p-10">
                <!-- Gold orbs -->
                <div class="pointer-events-none absolute -top-20 -end-20 w-72 h-72 bg-[#C4A265]/20 rounded-full blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-16 start-1/3 w-56 h-56 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
                <div class="pointer-events-none absolute top-1/2 end-1/4 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>

                <!-- Floating tooth icon decoration -->
                <div class="absolute ltr:right-8 rtl:left-8 top-8 opacity-10">
                    <svg class="w-32 h-32 text-white dental-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                    </svg>
                </div>

                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="dental-hero-up">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg shadow-black/20">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ locale === 'ar' ? 'طب الأسنان' : 'DENTAL' }}</span>
                                    </div>
                                    <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">{{ $t('a_dental_dashboard') }}</h1>
                                    <p class="text-white/70 text-sm mt-1 max-w-xl">{{ $t('a_dental_dashboard_desc') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 dental-hero-up" style="animation-delay: 0.15s">
                            <Link
                                href="/admin/dental/chart-search"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-[#C4A265] hover:bg-[#D4B57E] shadow-lg hover:shadow-xl transition-all duration-300"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                {{ $t('a_search_chart') || (locale === 'ar' ? 'بحث خريطة الاسنان' : 'Search Dental Chart') }}
                            </Link>
                            <Link
                                href="/admin/dental/treatment-plans/create"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/15 hover:bg-white/25 ring-1 ring-white/30 hover:ring-white/50 shadow-lg transition-all duration-300 backdrop-blur-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ $t('a_create_plan') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── KPI Stat Cards (animated counters) ────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <div
                    v-for="(card, index) in statCards"
                    :key="card.key"
                    class="dental-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden"
                    :style="{ animationDelay: `${index * 0.1}s` }"
                >
                    <!-- Gradient accent top bar -->
                    <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80`"></div>

                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[13px] font-medium text-gray-500">{{ card.label || $t(card.labelKey) }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">
                                {{ card.isCurrency ? card.value : card.value }}
                            </p>
                            <!-- Overdue badge for lab orders -->
                            <div v-if="card.badge" class="flex items-center gap-1 mt-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-600 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                    {{ card.badge }} {{ $t('a_overdue') }}
                                </span>
                            </div>
                        </div>
                        <div :class="[card.lightBg]" class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <!-- Calendar -->
                            <svg v-if="card.icon === 'calendar'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <!-- Check -->
                            <svg v-else-if="card.icon === 'check'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <!-- Clipboard -->
                            <svg v-else-if="card.icon === 'clipboard'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                            <!-- Lab -->
                            <svg v-else-if="card.icon === 'lab'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                            <!-- Revenue -->
                            <svg v-else-if="card.icon === 'revenue'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                            <!-- Alert -->
                            <svg v-else-if="card.icon === 'alert'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Critical Alerts Banner ──────────────────────────── -->
            <div v-if="criticalAlerts && criticalAlerts.length > 0"
                 class="dental-card-enter bg-gradient-to-r from-red-50 via-red-50/80 to-amber-50 rounded-2xl border border-red-200/60 shadow-sm overflow-hidden"
                 style="animation-delay: 0.42s">
                <div class="px-6 py-4 border-b border-red-100/60 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-red-800">{{ isRtl ? 'تنبيهات تحتاج انتباهك' : 'Alerts Requiring Attention' }}</h2>
                        <p class="text-xs text-red-600/70 mt-0.5">{{ isRtl ? `${criticalAlerts.length} تنبيه نشط` : `${criticalAlerts.length} active alert(s)` }}</p>
                    </div>
                </div>
                <div class="divide-y divide-red-100/40">
                    <div v-for="(alert, idx) in criticalAlerts" :key="idx"
                         class="px-6 py-3.5 flex items-center justify-between gap-4 hover:bg-white/40 transition-colors">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <span class="flex-shrink-0 w-2 h-2 rounded-full"
                                  :class="alert.severity === 'high' ? 'bg-red-500 animate-pulse' : 'bg-amber-500'"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold" :class="alert.severity === 'high' ? 'text-red-800' : 'text-amber-800'">
                                    {{ isRtl ? alert.message_ar : alert.message_en }}
                                </p>
                                <span class="text-[10px] uppercase tracking-wider font-bold"
                                      :class="alert.severity === 'high' ? 'text-red-500' : 'text-amber-500'">
                                    {{ alert.severity === 'high' ? (isRtl ? 'عاجل' : 'URGENT') : (isRtl ? 'تنبيه' : 'WARNING') }}
                                </span>
                            </div>
                        </div>
                        <Link v-if="alert.action_url" :href="alert.action_url"
                              class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                              :class="alert.severity === 'high'
                                  ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                  : 'bg-amber-100 text-amber-700 hover:bg-amber-200'">
                            {{ isRtl ? 'عرض' : 'View' }}
                            <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Quick Navigation Links ────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <Link
                    v-for="(link, index) in quickLinks"
                    :key="link.href"
                    :href="link.href"
                    class="dental-card-enter group flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-gray-100/80 shadow-sm hover:shadow-lg hover:border-slate-200/50 hover:-translate-y-1 transition-all duration-300"
                    :style="{ animationDelay: `${0.3 + index * 0.08}s` }"
                >
                    <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${link.color} flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300`">
                        <!-- Treatment -->
                        <svg v-if="link.icon === 'treatment'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        <!-- Plan -->
                        <svg v-else-if="link.icon === 'plan'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        <!-- Lab -->
                        <svg v-else-if="link.icon === 'lab'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                        <!-- Xray -->
                        <svg v-else-if="link.icon === 'xray'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <!-- Chart -->
                        <svg v-else-if="link.icon === 'chart'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                        <!-- Dashboard -->
                        <svg v-else-if="link.icon === 'dashboard'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        <!-- Notification bell -->
                        <svg v-else-if="link.icon === 'notification'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 text-center group-hover:text-[#1B365D] transition-colors">{{ link.label || $t(link.labelKey) }}</span>
                </Link>
            </div>

            <!-- ── Today's Appointments ──────────────────────────── -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.5s">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_today_appointments') }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ stats.today_waiting }} {{ $t('a_waiting') }}</p>
                        </div>
                    </div>
                    <Link href="/admin/visits/today-queue" class="text-sm text-[#1B365D] hover:text-[#1B365D] font-medium hover:underline transition-colors">
                        {{ $t('a_view_all') }}
                    </Link>
                </div>

                <div v-if="!todayAppointments || todayAppointments.length === 0" class="flex flex-col items-center justify-center py-16">
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">{{ $t('a_no_appointments_today') }}</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_time') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_doctor') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_service') }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-5 py-3.5 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(apt, idx) in todayAppointments"
                                :key="apt.id"
                                class="dental-row-enter border-b border-gray-50 hover:bg-slate-50/30 transition-colors duration-200"
                                :style="{ animationDelay: `${0.6 + idx * 0.05}s` }"
                            >
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-mono font-semibold text-gray-900">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ formatTime(apt.scheduled_time) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <Link v-if="apt.patient" :href="`/admin/patients/${apt.patient.id}`" class="font-medium text-gray-900 hover:text-[#1B365D] transition-colors">
                                            {{ apt.patient.full_name }}
                                        </Link>
                                        <!-- Medical Risk Badge -->
                                        <button v-if="hasAlert(apt.id)" @click="toggleAlert(apt.id)"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold animate-pulse hover:bg-red-200 transition-colors cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                            </svg>
                                            {{ getAlertFlags(apt.id).length }}
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-0.5 font-mono">{{ apt.patient?.file_number }}</div>
                                    <!-- Expanded risk details -->
                                    <div v-if="expandedAlerts[apt.id] && hasAlert(apt.id)" class="mt-2 space-y-1">
                                        <div v-for="flag in getAlertFlags(apt.id)" :key="flag.key"
                                             :class="[severityStyles[flag.severity]?.bg, severityStyles[flag.severity]?.text, severityStyles[flag.severity]?.border]"
                                             class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border text-[11px] font-medium">
                                            <span :class="severityStyles[flag.severity]?.dot" class="w-1.5 h-1.5 rounded-full flex-shrink-0"></span>
                                            {{ isRtl ? flag.label_ar : flag.label_en }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ apt.doctor ? (locale === 'ar' ? apt.doctor.name_ar : apt.doctor.name_en) : '-' }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ apt.service ? (locale === 'ar' ? apt.service.name_ar : apt.service.name_en) : '-' }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span
                                        :class="[getStatusStyle(apt.status).bg, getStatusStyle(apt.status).text]"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                    >
                                        <span :class="getStatusStyle(apt.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ $t('a_visit_' + apt.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-end">
                                    <Link
                                        :href="`/admin/dental/chart/${apt.patient?.id}`"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1B365D] bg-slate-50 hover:bg-slate-100 transition-colors duration-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347" /></svg>
                                        {{ $t('a_dental_chart') }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── Two-Column: Treatment Plans + Lab Orders ──────── -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Pending Treatment Plans -->
                <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.65s">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                            </div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_active_treatment_plans') }}</h2>
                        </div>
                        <Link href="/admin/dental/treatment-plans" class="text-sm text-[#1B365D] hover:text-[#1B365D] font-medium hover:underline transition-colors">{{ $t('a_view_all') }}</Link>
                    </div>

                    <div v-if="!pendingPlans || pendingPlans.length === 0" class="flex flex-col items-center justify-center py-12">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_active_plans') }}</p>
                    </div>

                    <div v-else class="divide-y divide-gray-50">
                        <div
                            v-for="(plan, idx) in pendingPlans"
                            :key="plan.id"
                            class="dental-row-enter px-5 py-4 hover:bg-slate-50/30 transition-colors duration-200"
                            :style="{ animationDelay: `${0.7 + idx * 0.06}s` }"
                        >
                            <div class="flex justify-between items-start">
                                <div class="min-w-0 flex-1">
                                    <Link :href="`/admin/dental/treatment-plans/${plan.id}`" class="font-medium text-gray-900 hover:text-[#1B365D] transition-colors truncate block">
                                        {{ locale === 'ar' ? (plan.title_ar || plan.title_en || `#${plan.id}`) : (plan.title_en || plan.title_ar || `#${plan.id}`) }}
                                    </Link>
                                    <div class="flex items-center gap-2 mt-1.5 text-xs text-gray-500">
                                        <span class="truncate">{{ plan.patient?.full_name }}</span>
                                        <span class="text-gray-300">&bull;</span>
                                        <span class="truncate">{{ plan.doctor ? (locale === 'ar' ? plan.doctor.name_ar : plan.doctor.name_en) : '' }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1.5 ltr:ml-3 rtl:mr-3">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-50 text-[#1B365D]">
                                        {{ plan.completed_sessions }}/{{ plan.estimated_sessions }}
                                    </span>
                                    <!-- Mini progress bar -->
                                    <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                        <div
                                            class="bg-gradient-to-r from-[#2C4E7A] to-[#1B365D] h-1.5 rounded-full transition-all duration-700"
                                            :style="{ width: (plan.estimated_sessions > 0 ? Math.min((plan.completed_sessions / plan.estimated_sessions) * 100, 100) : 0) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Lab Orders -->
                <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.75s">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                            </div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_pending_lab_orders') }}</h2>
                        </div>
                        <Link href="/admin/dental/lab-orders" class="text-sm text-[#1B365D] hover:text-[#1B365D] font-medium hover:underline transition-colors">{{ $t('a_view_all') }}</Link>
                    </div>

                    <div v-if="!pendingLabOrders || pendingLabOrders.length === 0" class="flex flex-col items-center justify-center py-12">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_pending_orders') }}</p>
                    </div>

                    <div v-else class="divide-y divide-gray-50">
                        <div
                            v-for="(order, idx) in pendingLabOrders"
                            :key="order.id"
                            class="dental-row-enter px-5 py-4 hover:bg-amber-50/30 transition-colors duration-200"
                            :style="{ animationDelay: `${0.8 + idx * 0.06}s` }"
                        >
                            <div class="flex justify-between items-start">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900">{{ $t('a_lab_' + order.item_type) }}</div>
                                    <div class="flex items-center gap-2 mt-1.5 text-xs text-gray-500">
                                        <span class="truncate">{{ order.patient?.full_name }}</span>
                                        <span class="text-gray-300">&bull;</span>
                                        <span class="font-mono">{{ $t('a_tooth') }} {{ order.tooth_number || '-' }}</span>
                                    </div>
                                    <div v-if="order.lab_name" class="text-xs text-gray-400 mt-0.5">{{ order.lab_name }}</div>
                                </div>
                                <div class="flex flex-col items-end gap-1.5 ltr:ml-3 rtl:mr-3">
                                    <span
                                        :class="[getLabStatusStyle(order.status).bg, getLabStatusStyle(order.status).text]"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                    >
                                        <span :class="getLabStatusStyle(order.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ $t('a_lab_status_' + order.status) }}
                                    </span>
                                    <div v-if="order.expected_date" class="text-xs text-gray-400">
                                        {{ formatDate(order.expected_date) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Doctor Performance (this month) ──────────────────── -->
            <div v-if="doctorPerformance?.length > 0" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.82s">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_monthly_revenue') ? (locale === 'ar' ? 'أداء الأطباء هذا الشهر' : 'Doctor Performance (This Month)') : 'Doctor Performance' }}</h2>
                        </div>
                    </div>
                    <Link href="/admin/reports/dental" class="text-sm text-[#1B365D] hover:text-[#1B365D] font-medium hover:underline transition-colors">
                        {{ locale === 'ar' ? 'تقارير تفصيلية' : 'Detailed Reports' }}
                    </Link>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="(doc, idx) in doctorPerformance" :key="doc.id"
                         class="dental-row-enter px-6 py-4 hover:bg-slate-50/20 transition-colors"
                         :style="{ animationDelay: `${0.85 + idx * 0.06}s` }">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                     :class="idx === 0 ? 'bg-amber-100 text-amber-700' : idx === 1 ? 'bg-gray-200 text-gray-600' : idx === 2 ? 'bg-[#F5E7C8]/60 text-[#8B7043]' : 'bg-gray-50 text-gray-400'">
                                    {{ idx + 1 }}
                                </div>
                                <img v-if="doc.photo" :src="`/storage/${doc.photo}`" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
                                <div v-else class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-[#1B365D] text-sm font-bold flex-shrink-0">
                                    {{ (doc.name_en || '?')[0] }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ locale === 'ar' ? doc.name_ar : doc.name_en }}</p>
                                    <p class="text-[11px] text-gray-500">{{ doc.total }} {{ locale === 'ar' ? 'علاج' : 'treatments' }} &middot; {{ doc.completed }} {{ locale === 'ar' ? 'مكتمل' : 'done' }}</p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-[#1B365D]">{{ formatCurrency(doc.revenue) }}</p>
                                <div class="w-20 bg-gray-100 rounded-full h-1.5 mt-1.5">
                                    <div class="bg-gradient-to-r from-[#2C4E7A] to-[#1B365D] h-1.5 rounded-full transition-all duration-700"
                                         :style="{ width: `${doc.total > 0 ? Math.round(doc.completed / doc.total * 100) : 0}%` }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Quick Exports ──────────────────────────────────── -->
            <div class="dental-card-enter flex flex-wrap gap-3" style="animation-delay: 0.84s">
                <a href="/admin/exports/dental-treatments"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 hover:bg-slate-50 hover:border-slate-200 hover:text-[#1B365D] transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    {{ locale === 'ar' ? 'تصدير العلاجات' : 'Export Treatments' }}
                </a>
                <a href="/admin/exports/dental-lab-orders"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 hover:bg-amber-50 hover:border-amber-200 hover:text-amber-700 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    {{ locale === 'ar' ? 'تصدير طلبات المعمل' : 'Export Lab Orders' }}
                </a>
                <a href="/admin/exports/dental-treatment-plans"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 hover:bg-slate-50 hover:border-slate-200 hover:text-[#1B365D] transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    {{ locale === 'ar' ? 'تصدير خطط العلاج' : 'Export Plans' }}
                </a>
                <a href="/admin/exports/dental-followups"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 hover:bg-[#F5E7C8]/40 hover:border-[#F5E7C8] hover:text-[#8B7043] transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    {{ locale === 'ar' ? 'تصدير المتابعات' : 'Export Follow-ups' }}
                </a>
            </div>

            <!-- ── Pending Follow-ups ─────────────────────────────── -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.85s">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#F5E7C8]/40 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'متابعات تحتاج حجز' : 'Follow-ups Needing Booking' }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <span v-if="overdueFollowups > 0" class="text-red-500 font-semibold">{{ overdueFollowups }} {{ isRtl ? 'متأخرة' : 'overdue' }}</span>
                                <span v-else>{{ isRtl ? 'لا يوجد متابعات متأخرة' : 'No overdue follow-ups' }}</span>
                            </p>
                        </div>
                    </div>
                    <Link href="/admin/dental/followup-rules" class="text-sm text-[#1B365D] hover:text-[#1B365D] font-medium hover:underline transition-colors">
                        {{ isRtl ? 'عرض الكل' : 'View All' }}
                    </Link>
                </div>

                <div v-if="!pendingFollowups || pendingFollowups.length === 0" class="flex flex-col items-center justify-center py-12">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'جميع المتابعات محجوزة' : 'All follow-ups are booked' }}</p>
                </div>

                <div v-else class="divide-y divide-gray-50">
                    <div v-for="(f, idx) in pendingFollowups" :key="f.id"
                         class="dental-row-enter px-6 py-4 hover:bg-[#F5E7C8]/40/30 transition-colors duration-200"
                         :class="daysUntil(f.scheduled_date) < 0 ? 'bg-red-50/30' : ''"
                         :style="{ animationDelay: `${0.9 + idx * 0.06}s` }">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold flex-shrink-0"
                                     :class="daysUntil(f.scheduled_date) < 0 ? 'bg-red-100 text-red-700' : 'bg-slate-50 text-[#1B365D]'">
                                    {{ (f.patient?.full_name || '?').charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ f.patient?.full_name || '-' }}</p>
                                        <span class="text-[10px] text-gray-400 font-mono">{{ f.patient?.file_number }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-500">
                                        <span v-if="f.treatment?.treatment_type">{{ f.treatment.treatment_type.replace(/_/g, ' ') }}</span>
                                        <span v-if="f.treatment?.tooth_number" class="text-gray-400">
                                            &middot; {{ isRtl ? 'سن' : 'Tooth' }} #{{ f.treatment.tooth_number }}
                                        </span>
                                        <span v-if="f.doctor" class="text-gray-400">
                                            &middot; {{ isRtl ? f.doctor.name_ar : f.doctor.name_en }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 flex-shrink-0">
                                <!-- Date & countdown -->
                                <div class="text-right">
                                    <p class="text-xs font-semibold text-gray-700">{{ formatDate(f.scheduled_date) }}</p>
                                    <p class="text-[10px] mt-0.5"
                                       :class="daysUntil(f.scheduled_date) < 0 ? 'text-red-600 font-bold' : daysUntil(f.scheduled_date) <= 3 ? 'text-amber-600' : 'text-gray-400'">
                                        <template v-if="daysUntil(f.scheduled_date) < 0">
                                            {{ isRtl ? `متأخر ${Math.abs(daysUntil(f.scheduled_date))} يوم` : `${Math.abs(daysUntil(f.scheduled_date))}d overdue` }}
                                        </template>
                                        <template v-else-if="daysUntil(f.scheduled_date) === 0">{{ isRtl ? 'اليوم' : 'Today' }}</template>
                                        <template v-else>{{ isRtl ? `بعد ${daysUntil(f.scheduled_date)} يوم` : `in ${daysUntil(f.scheduled_date)}d` }}</template>
                                    </p>
                                </div>

                                <!-- Overdue badge -->
                                <span v-if="daysUntil(f.scheduled_date) < 0"
                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-[10px] font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                    {{ isRtl ? 'متأخرة' : 'Overdue' }}
                                </span>

                                <!-- Patient link -->
                                <Link v-if="f.patient" :href="`/admin/patients/${f.patient.id}`"
                                      class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ── Hero animations ───────────────────────────────────── */
@keyframes dentalHeroUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes dentalFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50%      { transform: translateY(-12px) rotate(3deg); }
}

@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes dentalRowEnter {
    from { opacity: 0; transform: translateX(12px); }
    to   { opacity: 1; transform: translateX(0); }
}

.dental-hero-up {
    animation: dentalHeroUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.dental-float {
    animation: dentalFloat 6s ease-in-out infinite;
}

.dental-card-enter {
    animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.dental-row-enter {
    animation: dentalRowEnter 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* ── RTL row animation flip ────────────────────────────── */
[dir="rtl"] .dental-row-enter {
    animation-name: dentalRowEnterRtl;
}

@keyframes dentalRowEnterRtl {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>
