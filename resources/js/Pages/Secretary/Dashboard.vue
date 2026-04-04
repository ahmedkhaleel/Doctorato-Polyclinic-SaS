<script setup>
import { computed } from 'vue';
import { Link , usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    stats: Object,
    todayQueue: Array,
    pendingBookings: Array,
    recentPayments: Array,
    dental: Object,
    medicalAlerts: Array,
    pendingFollowups: Array,
    overdueFollowups: Number,
});

const { formatCurrency, currencyCode } = useCurrency();

/* -- Helpers -------------------------------------------------- */

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
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

/* -- Stats Cards ---------------------------------------------- */

const statsCards = computed(() => [
    {
        label: isRtl.value ? 'مرضى اليوم' : 'Patients Today',
        value: props.stats?.patients_today ?? 0,
        gradient: 'from-teal-500 to-teal-600',
        lightBg: 'bg-teal-50',
        iconColor: 'text-teal-600',
        icon: 'patients',
    },
    {
        label: isRtl.value ? 'زيارات اليوم' : 'Visits Today',
        value: props.stats?.visits_today ?? 0,
        gradient: 'from-cyan-500 to-cyan-600',
        lightBg: 'bg-cyan-50',
        iconColor: 'text-cyan-600',
        icon: 'visits',
    },
    {
        label: isRtl.value ? 'حجوزات معلقة' : 'Pending Bookings',
        value: props.stats?.pending_bookings ?? 0,
        gradient: 'from-amber-500 to-amber-600',
        lightBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        icon: 'bookings',
    },
    {
        label: isRtl.value ? 'مدفوعات اليوم' : 'Payments Today',
        value: formatCurrency(props.stats?.payments_today),
        gradient: 'from-emerald-500 to-emerald-600',
        lightBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
        icon: 'payments',
    },
]);

/* -- Status Badge Config -------------------------------------- */

const statusConfig = {
    waiting:     { label: isRtl.value ? 'انتظار' : 'Waiting',     bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500' },
    in_progress: { label: isRtl.value ? 'جاري' : 'In Progress', bg: 'bg-blue-50',    text: 'text-blue-700',     dot: 'bg-blue-500' },
    completed:   { label: isRtl.value ? 'مكتمل' : 'Completed',   bg: 'bg-emerald-50', text: 'text-emerald-700',  dot: 'bg-emerald-500' },
    cancelled:   { label: isRtl.value ? 'ملغي' : 'Cancelled',   bg: 'bg-red-50',     text: 'text-red-700',      dot: 'bg-red-500' },
};

function getStatusStyle(status) {
    return statusConfig[status] || statusConfig.waiting;
}

/* -- Booking Status ------------------------------------------- */

const bookingStatusConfig = {
    new:       { label: isRtl.value ? 'جديد' : 'New',       bg: 'bg-blue-50',    text: 'text-blue-700' },
    pending:   { label: isRtl.value ? 'معلق' : 'Pending',   bg: 'bg-amber-50',   text: 'text-amber-700' },
    confirmed: { label: isRtl.value ? 'مؤكد' : 'Confirmed', bg: 'bg-emerald-50', text: 'text-emerald-700' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-red-50',     text: 'text-red-700' },
};

function getBookingStatusStyle(status) {
    return bookingStatusConfig[status] || bookingStatusConfig.pending;
}

/* -- Followup helpers ---------------------------------------- */

function daysUntil(dateStr) {
    if (!dateStr) return null;
    return Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
}

function $localized(obj, field) {
    if (!obj) return '';
    return obj[field + '_' + (isRtl.value ? 'ar' : 'en')] || obj[field + '_en'] || obj[field] || '';
}

/* -- Severity helpers ---------------------------------------- */

const severityStyles = {
    high: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', icon: 'text-red-500' },
    medium: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', icon: 'text-amber-500' },
    low: { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', icon: 'text-blue-500' },
};
</script>

<template>
    <div class="space-y-8">

        <!-- Header + Quick Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'لوحة تحكم السكرتارية' : 'Secretary Dashboard' }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ isRtl ? 'مرحباً بعودتك، ' : 'Welcome back, ' }}{{ $page.props.auth?.user?.name || (isRtl ? 'سكرتارية' : 'Secretary') }}. {{ isRtl ? 'إليك نظرة عامة على اليوم.' : "Here is today's overview." }}
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <Link
                    href="/secretary/patients/create"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 shadow-sm hover:shadow-md transition-all duration-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    {{ isRtl ? 'مريض جديد' : 'New Patient' }}
                </Link>
                <Link
                    href="/secretary/bookings/create"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 shadow-sm transition-all duration-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ isRtl ? 'حجز جديد' : 'New Booking' }}
                </Link>
                <Link
                    href="/secretary/bookings"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 shadow-sm transition-all duration-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ isRtl ? 'عرض الحجوزات' : 'View Bookings' }}
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            <div
                v-for="card in statsCards"
                :key="card.label"
                class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden"
            >
                <!-- Gradient accent top -->
                <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80`"></div>

                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[13px] font-medium text-gray-500">{{ card.label }}</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ card.value }}</p>
                    </div>
                    <div :class="[card.lightBg]" class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <!-- Patients icon -->
                        <svg v-if="card.icon === 'patients'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <!-- Visits icon -->
                        <svg v-else-if="card.icon === 'visits'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <!-- Bookings icon -->
                        <svg v-else-if="card.icon === 'bookings'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <!-- Payments icon -->
                        <svg v-else-if="card.icon === 'payments'" :class="card.iconColor" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Risk Alerts Banner -->
        <div v-if="medicalAlerts && medicalAlerts.length > 0" class="bg-red-50 rounded-2xl border border-red-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-red-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-[15px] font-bold text-red-800">
                        {{ isRtl ? 'تنبيهات طبية - مرضى اليوم' : 'Medical Alerts - Today\'s Patients' }}
                    </h2>
                    <p class="text-[11px] text-red-600">
                        {{ isRtl
                            ? `${medicalAlerts.length} مريض أسنان لديه مخاطر طبية في مواعيد اليوم`
                            : `${medicalAlerts.length} dental patient(s) with medical risks in today's appointments` }}
                    </p>
                </div>
            </div>
            <div class="divide-y divide-red-100">
                <div v-for="alert in medicalAlerts" :key="alert.visit_id" class="px-6 py-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center text-red-700 text-xs font-bold flex-shrink-0">
                                {{ (alert.patient_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-red-900">{{ alert.patient_name }}</p>
                                <p v-if="alert.doctor_name" class="text-[11px] text-red-600">{{ alert.doctor_name }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 justify-end">
                            <span v-for="flag in alert.flags" :key="flag.key"
                                :class="[severityStyles[flag.severity]?.bg || 'bg-gray-50', severityStyles[flag.severity]?.text || 'text-gray-600']"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold whitespace-nowrap">
                                <svg v-if="flag.severity === 'high'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ isRtl ? flag.label_ar : flag.label_en }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Followups + Overdue Alert -->
        <div v-if="(pendingFollowups && pendingFollowups.length > 0) || overdueFollowups > 0"
            class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'متابعات تحتاج جدولة' : 'Follow-ups Need Scheduling' }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ isRtl ? 'مواعيد متابعة الأسنان بانتظار الحجز' : 'Dental follow-ups awaiting booking' }}
                            <span v-if="overdueFollowups > 0" class="text-red-600 font-semibold">
                                &middot; {{ isRtl ? `${overdueFollowups} متأخرة` : `${overdueFollowups} overdue` }}
                            </span>
                        </p>
                    </div>
                </div>
                <Link
                    href="/secretary/dental/followups"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors duration-200"
                >
                    {{ isRtl ? 'عرض الكل' : 'View All' }}
                    <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="f in pendingFollowups" :key="f.id" class="px-6 py-3 hover:bg-gray-50/50 transition-colors"
                    :class="daysUntil(f.scheduled_date) < 0 ? 'bg-red-50/30' : ''">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                :class="daysUntil(f.scheduled_date) < 0 ? 'bg-red-100 text-red-700' : 'bg-amber-50 text-amber-700'">
                                {{ (f.patient?.full_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ f.patient?.full_name || '-' }}</p>
                                <p class="text-[11px] text-gray-400 truncate">
                                    {{ f.treatment?.treatment_type?.replace('_', ' ') || '' }}
                                    <span v-if="f.treatment?.tooth_number"> &middot; {{ isRtl ? 'سن' : 'Tooth' }} #{{ f.treatment.tooth_number }}</span>
                                    <span v-if="f.doctor"> &middot; {{ $localized(f.doctor, 'name') }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="text-right">
                                <p class="text-xs font-semibold text-gray-700">{{ formatDate(f.scheduled_date) }}</p>
                                <p class="text-[10px]"
                                    :class="daysUntil(f.scheduled_date) < 0 ? 'text-red-600 font-bold' : daysUntil(f.scheduled_date) <= 3 ? 'text-amber-600' : 'text-gray-400'">
                                    <template v-if="daysUntil(f.scheduled_date) < 0">
                                        {{ isRtl ? `متأخر ${Math.abs(daysUntil(f.scheduled_date))} يوم` : `${Math.abs(daysUntil(f.scheduled_date))}d overdue` }}
                                    </template>
                                    <template v-else-if="daysUntil(f.scheduled_date) === 0">{{ isRtl ? 'اليوم' : 'Today' }}</template>
                                    <template v-else>{{ isRtl ? `بعد ${daysUntil(f.scheduled_date)} يوم` : `in ${daysUntil(f.scheduled_date)}d` }}</template>
                                </p>
                            </div>
                            <Link href="/secretary/bookings/create"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-medium text-white bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-600 hover:to-teal-600 shadow-sm transition-all">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ isRtl ? 'حجز' : 'Book' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Queue + Pending Bookings -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Today's Queue (2/3) -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'طابور اليوم' : "Today's Queue" }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'المرضى في الانتظار والعلاج' : 'Patients waiting and in progress' }}</p>
                    </div>
                    <Link
                        href="/secretary/queue"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-teal-600 bg-teal-50 hover:bg-teal-100 transition-colors duration-200"
                    >
                        {{ isRtl ? 'عرض الكل' : 'View All' }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الانتظار' : 'Wait' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="(visit, index) in todayQueue"
                                :key="visit.id"
                                class="hover:bg-gray-50/50 transition-colors duration-150"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 font-mono">{{ index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 text-xs font-bold flex-shrink-0">
                                            {{ (visit.patient?.name || visit.patient?.full_name || '?').charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ visit.patient?.name || visit.patient?.full_name || '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ visit.doctor?.name_en || visit.doctor?.name || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ visit.service?.name_en || visit.service?.name || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[getStatusStyle(visit.status).bg, getStatusStyle(visit.status).text]"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                    >
                                        <span :class="getStatusStyle(visit.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ getStatusStyle(visit.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="visit.status === 'waiting' || visit.status === 'in_progress'"
                                        :class="waitTimeColor(waitTime(visit.created_at))"
                                        class="text-sm font-semibold"
                                    >
                                        {{ waitTime(visit.created_at) }} min
                                    </span>
                                    <span v-else class="text-sm text-gray-400">--</span>
                                </td>
                            </tr>
                            <tr v-if="!todayQueue || todayQueue.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center mb-3">
                                            <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا يوجد مرضى في الطابور' : 'No patients in the queue' }}</p>
                                        <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'ستظهر الزيارات هنا عند تسجيل وصول المرضى' : 'Visits will appear here as patients check in' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Bookings (1/3) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'حجوزات معلقة' : 'Pending Bookings' }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'في انتظار التأكيد' : 'Awaiting confirmation' }}</p>
                    </div>
                    <Link
                        href="/secretary/bookings"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-teal-600 bg-teal-50 hover:bg-teal-100 transition-colors duration-200"
                    >
                        All
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
                <div v-if="pendingBookings && pendingBookings.length > 0" class="divide-y divide-gray-50">
                    <div
                        v-for="booking in pendingBookings"
                        :key="booking.id"
                        class="px-6 py-4 hover:bg-gray-50/50 transition-colors duration-150"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 flex-shrink-0">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">
                                        {{ booking.full_name || booking.patient?.name || '-' }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5 truncate">
                                        {{ booking.service?.name_en || booking.service?.name || booking.notes || '-' }}
                                    </p>
                                </div>
                            </div>
                            <span
                                :class="[getBookingStatusStyle(booking.status).bg, getBookingStatusStyle(booking.status).text]"
                                class="text-[10px] font-semibold px-2 py-0.5 rounded-full whitespace-nowrap flex-shrink-0"
                            >
                                {{ getBookingStatusStyle(booking.status).label }}
                            </span>
                        </div>
                        <div class="mt-2 ltr:ml-12 rtl:mr-12 flex items-center gap-3 text-xs text-gray-400">
                            <span v-if="booking.preferred_date" class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(booking.preferred_date) }}
                            </span>
                            <span v-if="booking.preferred_time" class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ booking.preferred_time }}
                            </span>
                            <span v-if="booking.phone" class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ booking.phone }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا توجد حجوزات معلقة' : 'No pending bookings' }}</p>
                        <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'تم معالجة جميع الحجوزات' : 'All bookings have been processed' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dental Lab Orders Overview -->
        <div v-if="dental" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Dental Alert Cards (1/3) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-cyan-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'طلبات المعمل' : 'Lab Orders' }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'حالة الطلبات الحالية' : 'Current orders status' }}</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-gray-50">
                    <Link href="/secretary/dental/lab-orders?status=ready" class="flex items-center justify-between px-6 py-4 hover:bg-emerald-50/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'جاهز للاستلام' : 'Ready for Pickup' }}</p>
                                <p class="text-[11px] text-gray-400">{{ isRtl ? 'طلبات جاهزة من المعمل' : 'Orders ready from lab' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 rounded-lg text-sm font-bold" :class="(dental.lab_ready ?? 0) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400'">{{ dental.lab_ready ?? 0 }}</span>
                    </Link>
                    <Link href="/secretary/dental/lab-orders" class="flex items-center justify-between px-6 py-4 hover:bg-amber-50/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'قيد التنفيذ' : 'Pending Orders' }}</p>
                                <p class="text-[11px] text-gray-400">{{ isRtl ? 'في انتظار المعمل' : 'Awaiting from lab' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 rounded-lg text-sm font-bold bg-amber-100 text-amber-700">{{ dental.lab_pending ?? 0 }}</span>
                    </Link>
                    <Link href="/secretary/dental/lab-orders?overdue=1" class="flex items-center justify-between px-6 py-4 hover:bg-red-50/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'طلبات متأخرة' : 'Overdue Orders' }}</p>
                                <p class="text-[11px] text-gray-400">{{ isRtl ? 'تجاوزت الموعد المتوقع' : 'Past expected date' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 rounded-lg text-sm font-bold" :class="(dental.lab_overdue ?? 0) > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-400'">{{ dental.lab_overdue ?? 0 }}</span>
                    </Link>
                    <Link href="/secretary/dental/treatment-plans" class="flex items-center justify-between px-6 py-4 hover:bg-blue-50/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'خطط علاج نشطة' : 'Active Treatment Plans' }}</p>
                                <p class="text-[11px] text-gray-400">{{ isRtl ? 'تحتاج متابعة مواعيد' : 'Need appointment scheduling' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 rounded-lg text-sm font-bold bg-blue-100 text-blue-700">{{ dental.active_plans ?? 0 }}</span>
                    </Link>
                </div>
            </div>

            <!-- Recent Lab Orders (2/3) -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'آخر طلبات المعمل' : 'Recent Lab Orders' }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'الطلبات النشطة الأخيرة' : 'Latest active orders' }}</p>
                    </div>
                    <Link
                        href="/secretary/dental/lab-orders"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-cyan-600 bg-cyan-50 hover:bg-cyan-100 transition-colors duration-200"
                    >
                        {{ isRtl ? 'عرض الكل' : 'View All' }}
                        <svg class="w-3.5 h-3.5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table v-if="dental.recent_lab_orders?.length > 0" class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'النوع' : 'Type' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'التاريخ المتوقع' : 'Expected' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="order in dental.recent_lab_orders" :key="order.id" class="hover:bg-gray-50/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-cyan-50 flex items-center justify-center text-cyan-600 text-xs font-bold flex-shrink-0">
                                            {{ (order.patient?.full_name || '?').charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900">{{ order.patient?.full_name || '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ order.doctor?.name_en || order.doctor?.name_ar || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">{{ (order.item_type || '-').replace('_', ' ') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700': order.status === 'ready',
                                            'bg-amber-50 text-amber-700': order.status === 'ordered' || order.status === 'in_production',
                                            'bg-blue-50 text-blue-700': order.status === 'delivered',
                                            'bg-gray-50 text-gray-600': !['ready','ordered','in_production','delivered'].includes(order.status),
                                        }">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="{
                                                'bg-emerald-500': order.status === 'ready',
                                                'bg-amber-500': order.status === 'ordered' || order.status === 'in_production',
                                                'bg-blue-500': order.status === 'delivered',
                                                'bg-gray-400': !['ready','ordered','in_production','delivered'].includes(order.status),
                                            }"></span>
                                        {{ order.status?.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ order.expected_date ? formatDate(order.expected_date) : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="py-12 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا توجد طلبات معمل نشطة' : 'No active lab orders' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-[15px] font-semibold text-gray-900">{{ isRtl ? 'المدفوعات الأخيرة' : 'Recent Payments' }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'آخر المعاملات المالية اليوم' : 'Latest payment transactions today' }}</p>
                </div>
                <Link
                    href="/secretary/payments"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-teal-600 bg-teal-50 hover:bg-teal-100 transition-colors duration-200"
                >
                    {{ isRtl ? 'عرض الكل' : 'View All' }}
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table v-if="recentPayments && recentPayments.length > 0" class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الفاتورة' : 'Invoice' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الطريقة' : 'Method' }}</th>
                            <th class="px-6 py-3 ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الوقت' : 'Time' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="payment in recentPayments"
                            :key="payment.id"
                            class="hover:bg-gray-50/50 transition-colors duration-150"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 text-xs font-bold flex-shrink-0">
                                        {{ (payment.invoice?.patient?.full_name || payment.patient?.full_name || '?').charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">
                                        {{ payment.invoice?.patient?.full_name || payment.patient?.full_name || '-' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                #{{ payment.invoice_id || payment.invoice?.id || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-50 text-gray-600 capitalize">
                                    {{ isRtl ? (payment.payment_method?.name_ar || payment.payment_method?.name_en || payment.method || '-') : (payment.payment_method?.name_en || payment.payment_method?.name_ar || payment.method || '-') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-emerald-600 ltr:text-right rtl:text-left">
                                {{ formatCurrency(payment.amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                {{ formatTime(payment.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا توجد مدفوعات مسجلة اليوم' : 'No payments recorded today' }}</p>
                        <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'ستظهر المدفوعات هنا عند معالجتها' : 'Payments will appear here as they are processed' }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
