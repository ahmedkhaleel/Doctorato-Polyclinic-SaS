<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    employeeStats: Object,
    departmentDistribution: Array,
    salaryOverview: Object,
    todayAttendance: Object,
    pendingLeaves: Array,
    expiringContracts: Array,
    payrollTrend: Array,
    pendingAdvances: Number,
});

const { formatCurrency } = useCurrency();

/* ── Helpers ─────────────────────────────────────────────── */

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function daysUntil(date) {
    if (!date) return 0;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const target = new Date(date);
    target.setHours(0, 0, 0, 0);
    return Math.ceil((target - today) / (1000 * 60 * 60 * 24));
}

/* ── Row 1: Employee Stats Cards ─────────────────────────── */

const employeeCardKeys = ['a_total_employees', 'a_active', 'a_on_leave', 'a_terminated'];

const employeeCards = computed(() => [
    {
        tKey: 'a_total_employees',
        value: props.employeeStats?.total ?? 0,
        borderColor: 'border-l-[#C4A265]',
        iconBg: 'bg-[#C4A265]/10',
        iconColor: 'text-[#C4A265]',
        icon: 'users',
    },
    {
        tKey: 'a_active',
        value: props.employeeStats?.active ?? 0,
        borderColor: 'border-l-emerald-500',
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-500',
        icon: 'check-circle',
    },
    {
        tKey: 'a_on_leave',
        value: props.employeeStats?.on_leave ?? 0,
        borderColor: 'border-l-amber-500',
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        icon: 'clock',
    },
    {
        tKey: 'a_terminated',
        value: props.employeeStats?.terminated ?? 0,
        borderColor: 'border-l-gray-400',
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-400',
        icon: 'x-circle',
    },
]);

/* ── Row 2: Salary Overview Cards ────────────────────────── */

const salaryCards = computed(() => [
    {
        tKey: 'a_total_payroll',
        value: formatCurrency(props.salaryOverview?.total),
        borderColor: 'border-l-[#C4A265]',
        iconBg: 'bg-[#C4A265]/10',
        iconColor: 'text-[#C4A265]',
        icon: 'currency',
    },
    {
        tKey: 'a_paid',
        value: formatCurrency(props.salaryOverview?.paid),
        borderColor: 'border-l-emerald-500',
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-500',
        icon: 'check',
    },
    {
        tKey: 'a_pending',
        value: formatCurrency(props.salaryOverview?.pending),
        borderColor: 'border-l-amber-500',
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        icon: 'hourglass',
    },
    {
        tKey: 'a_total_slips',
        value: props.salaryOverview?.slips_count ?? 0,
        borderColor: 'border-l-[#1B365D]',
        iconBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
        icon: 'document',
    },
]);

/* ── Row 3: Today's Attendance Cards ─────────────────────── */

const attendanceCards = computed(() => [
    {
        tKey: 'a_present',
        value: props.todayAttendance?.present ?? 0,
        borderColor: 'border-l-emerald-500',
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-500',
        icon: 'check-circle',
    },
    {
        tKey: 'a_absent',
        value: props.todayAttendance?.absent ?? 0,
        borderColor: 'border-l-red-500',
        iconBg: 'bg-red-50',
        iconColor: 'text-red-500',
        icon: 'x-circle',
    },
    {
        tKey: 'a_late',
        value: props.todayAttendance?.late ?? 0,
        borderColor: 'border-l-amber-500',
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        icon: 'exclamation',
    },
    {
        tKey: 'a_on_leave',
        value: props.todayAttendance?.on_leave ?? 0,
        borderColor: 'border-l-[#1B365D]',
        iconBg: 'bg-slate-50',
        iconColor: 'text-[#1B365D]',
        icon: 'calendar',
    },
]);

/* ── Payroll Trend Chart ─────────────────────────────────── */

const maxPayroll = computed(() => {
    if (!props.payrollTrend?.length) return 1;
    return Math.max(...props.payrollTrend.map((d) => Number(d.total) || 0), 1);
});

/* ── Leave Status Styles ─────────────────────────────────── */

const leaveStatusStyles = {
    pending:  { bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500' },
    approved: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500' },
};

const leaveStatusKeys = {
    pending: 'a_pending',
    approved: 'a_approved',
    rejected: 'a_rejected',
};

const leaveTypeKeys = {
    annual: 'a_annual',
    sick: 'a_sick',
    personal: 'a_personal',
    unpaid: 'a_unpaid',
};

function getLeaveStatusStyle(status) {
    return leaveStatusStyles[status] || leaveStatusStyles.pending;
}

/* ── Expiring Contract Urgency ───────────────────────────── */

function daysRemainingColor(days) {
    if (days <= 7) return 'text-red-600 bg-red-50';
    if (days <= 30) return 'text-amber-600 bg-amber-50';
    return 'text-emerald-600 bg-emerald-50';
}
</script>

<template>
    <AdminLayout :title="$t('a_hr_dashboard')">
        <div class="space-y-8">

            <!-- ═════════ Navy Hero Header ═════════ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
                <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                    <div class="flex items-start gap-3 md:gap-4 min-w-0">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                            <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الموارد البشرية' : 'Human Resources' }}</span>
                            </div>
                            <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight truncate">{{ $t('a_hr_dashboard') }}</h1>
                            <p class="text-xs md:text-sm text-white/70 mt-1 max-w-xl">{{ $t('a_hr_dashboard_subtitle') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <Link href="/admin/employees" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-4 py-2.5 shadow-md hover:shadow-lg transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $t('a_employees') }}
                        </Link>
                        <Link href="/admin/payroll" class="inline-flex items-center gap-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 text-white font-semibold px-4 py-2.5 transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $t('a_payroll') }}
                        </Link>
                        <Link href="/admin/leaves" class="inline-flex items-center gap-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 text-white font-semibold px-4 py-2.5 transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $t('a_leaves') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Row 1: Employee Stats ────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                <div
                    v-for="card in employeeCards"
                    :key="card.tKey"
                    :class="card.borderColor"
                    class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 ltr:border-l-4 rtl:border-r-4 transition-all duration-300"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[13px] font-medium text-gray-500">{{ $t(card.tKey) }}</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900 mt-2">{{ card.value }}</p>
                        </div>
                        <div :class="card.iconBg" class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <!-- Users -->
                            <svg v-if="card.icon === 'users'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            <!-- Check Circle -->
                            <svg v-else-if="card.icon === 'check-circle'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <!-- Clock -->
                            <svg v-else-if="card.icon === 'clock'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <!-- X Circle -->
                            <svg v-else-if="card.icon === 'x-circle'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Row 2: Salary Overview (Current Month) ───────── -->
            <div>
                <h2 class="text-[15px] font-semibold text-gray-900 mb-4">{{ $t('a_salary_overview') }} <span :class="['text-xs font-normal text-gray-400', isRtl ? 'mr-1' : 'ml-1']">{{ $t('a_current_month') }}</span></h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                    <div
                        v-for="card in salaryCards"
                        :key="card.tKey"
                        :class="card.borderColor"
                        class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 ltr:border-l-4 rtl:border-r-4 transition-all duration-300"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[13px] font-medium text-gray-500">{{ $t(card.tKey) }}</p>
                                <p class="text-xl md:text-2xl font-bold text-gray-900 mt-2">{{ card.value }}</p>
                            </div>
                            <div :class="card.iconBg" class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <!-- Currency -->
                                <svg v-if="card.icon === 'currency'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- Check -->
                                <svg v-else-if="card.icon === 'check'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" /></svg>
                                <!-- Hourglass -->
                                <svg v-else-if="card.icon === 'hourglass'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- Document -->
                                <svg v-else-if="card.icon === 'document'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Row 3: Today's Attendance ─────────────────────── -->
            <div>
                <h2 class="text-[15px] font-semibold text-gray-900 mb-4">{{ $t('a_todays_attendance') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                    <div
                        v-for="card in attendanceCards"
                        :key="card.tKey"
                        :class="card.borderColor"
                        class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 ltr:border-l-4 rtl:border-r-4 transition-all duration-300"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[13px] font-medium text-gray-500">{{ $t(card.tKey) }}</p>
                                <p class="text-2xl md:text-3xl font-bold text-gray-900 mt-2">{{ card.value }}</p>
                            </div>
                            <div :class="card.iconBg" class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <!-- Check Circle -->
                                <svg v-if="card.icon === 'check-circle'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- X Circle -->
                                <svg v-else-if="card.icon === 'x-circle'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- Exclamation -->
                                <svg v-else-if="card.icon === 'exclamation'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                <!-- Calendar -->
                                <svg v-else-if="card.icon === 'calendar'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Row 4: Payroll Trend + Department Distribution ── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Payroll Trend Bar Chart (2/3) -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="mb-5">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_payroll_trend') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_monthly_payroll_totals') }}</p>
                    </div>
                    <div v-if="payrollTrend && payrollTrend.length" class="space-y-3">
                        <div
                            v-for="item in payrollTrend"
                            :key="item.label"
                            class="group"
                        >
                            <div class="flex items-center gap-4">
                                <span :class="['text-sm font-medium text-gray-500 w-20 flex-shrink-0', isRtl ? 'text-left' : 'text-right']">{{ item.label }}</span>
                                <div class="flex-1 h-8 bg-gray-100 rounded-lg overflow-hidden relative">
                                    <div
                                        class="h-full bg-gradient-to-r from-[#C4A265] to-[#D4B87A] rounded-lg transition-all duration-500 group-hover:opacity-80 flex items-center justify-end pr-3"
                                        :style="{ width: Math.max((Number(item.total) / maxPayroll) * 100, 2) + '%' }"
                                    >
                                        <span
                                            v-if="(Number(item.total) / maxPayroll) * 100 > 20"
                                            class="text-[11px] font-semibold text-white whitespace-nowrap"
                                        >
                                            {{ formatCurrency(item.total) }}
                                        </span>
                                    </div>
                                    <span
                                        v-if="(Number(item.total) / maxPayroll) * 100 <= 20"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-gray-500 whitespace-nowrap"
                                    >
                                        {{ formatCurrency(item.total) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-12">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0h6m-6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m6 0v-6a2 2 0 00-2-2h-2a2 2 0 00-2 2v6" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_payroll_data') }}</p>
                    </div>
                </div>

                <!-- Department Distribution (1/3) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="mb-5">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_department_distribution') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_employees_per_department') }}</p>
                    </div>
                    <div v-if="departmentDistribution && departmentDistribution.length" class="space-y-3">
                        <div
                            v-for="dept in departmentDistribution"
                            :key="dept.name"
                            class="flex items-center justify-between py-2.5 px-3 rounded-xl hover:bg-gray-50 transition-colors duration-200"
                        >
                            <span class="text-sm font-medium text-gray-700">{{ dept.name }}</span>
                            <span class="inline-flex items-center justify-center min-w-[2rem] px-2.5 py-1 rounded-full text-[11px] font-bold text-[#C4A265] bg-[#C4A265]/10">
                                {{ dept.count }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-12">
                        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_department_data') }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Row 5: Pending Leaves + Expiring Contracts ────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Pending Leave Requests Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_pending_leave_requests') }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_awaiting_approval') }}</p>
                        </div>
                        <Link
                            href="/admin/leaves"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 transition-colors duration-200"
                        >
                            {{ $t('a_view_all') }}
                            <svg :class="['w-3.5 h-3.5', isRtl ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_employee') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_type') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_dates') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="leave in pendingLeaves?.slice(0, 5)"
                                    :key="leave.id"
                                    class="hover:bg-gray-50/50 transition-colors duration-150"
                                >
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        {{ leave.user?.name ?? '-' }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">
                                        {{ $t(leaveTypeKeys[leave.leave_type]) || leave.leave_type?.replace(/_/g, ' ') }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[getLeaveStatusStyle(leave.status).bg, getLeaveStatusStyle(leave.status).text]"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold capitalize"
                                        >
                                            <span :class="getLeaveStatusStyle(leave.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                            {{ $t(leaveStatusKeys[leave.status]) || leave.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!pendingLeaves || pendingLeaves.length === 0">
                                    <td colspan="4" class="px-4 md:px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mb-3">
                                                <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-400">{{ $t('a_no_pending_leave_requests') }}</p>
                                            <p class="text-xs text-gray-300 mt-1">{{ $t('a_all_leaves_processed') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Expiring Contracts Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_expiring_contracts') }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_contracts_nearing_end') }}</p>
                        </div>
                        <Link
                            href="/admin/employees"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 transition-colors duration-200"
                        >
                            {{ $t('a_view_all') }}
                            <svg :class="['w-3.5 h-3.5', isRtl ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_employee') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_department') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_end_date') }}</th>
                                    <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_remaining') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="contract in expiringContracts"
                                    :key="contract.id"
                                    class="hover:bg-gray-50/50 transition-colors duration-150"
                                >
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        {{ contract.user?.name ?? '-' }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ contract.department?.name_en ?? '-' }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ formatDate(contract.contract_end_date) }}
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="daysRemainingColor(daysUntil(contract.contract_end_date))"
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold"
                                        >
                                            {{ daysUntil(contract.contract_end_date) }} {{ $t('a_days') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!expiringContracts || expiringContracts.length === 0">
                                    <td colspan="4" class="px-4 md:px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mb-3">
                                                <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-400">{{ $t('a_no_expiring_contracts') }}</p>
                                            <p class="text-xs text-gray-300 mt-1">{{ $t('a_all_contracts_good') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── Row 6: Quick Stats - Pending Advances ─────────── -->
            <div>
                <Link
                    href="/admin/advances"
                    class="group flex items-center gap-5 bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-[#C4A265]/30 transition-all duration-300"
                >
                    <div class="w-14 h-14 rounded-xl bg-[#C4A265]/10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-[13px] font-medium text-gray-500">{{ $t('a_pending_salary_advances') }}</p>
                        <p class="text-xl md:text-2xl font-bold text-gray-900 mt-0.5">{{ pendingAdvances ?? 0 }}</p>
                    </div>
                    <div :class="['flex items-center gap-1.5 text-[#C4A265] opacity-0 group-hover:opacity-100 transition-opacity duration-200']">
                        <span class="text-sm font-semibold">{{ $t('a_view_all') }}</span>
                        <svg :class="['w-4 h-4', isRtl ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </Link>
            </div>

        </div>
    </AdminLayout>
</template>
