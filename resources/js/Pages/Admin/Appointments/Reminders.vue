<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    upcoming: Array,
    stats: Object,
    smsSettings: Object,
    noShowPatients: Array,
    hourlyToday: Object,
});

/* ── Status / Reminder config (navy + gold palette) ───────── */
const statusConfig = {
    scheduled: { en: 'Scheduled', ar: 'مجدول',   class: 'bg-slate-100 text-slate-700 ring-slate-200' },
    confirmed: { en: 'Confirmed', ar: 'مؤكد',    class: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
};

const reminderConfig = {
    same_day: {
        en: 'Today', ar: 'اليوم',
        chip: 'bg-gradient-to-r from-[#C4A265] to-[#8B7043] text-white shadow-sm',
        ring: 'ring-2 ring-[#C4A265]/40 shadow-[#C4A265]/10',
        dot: 'bg-[#C4A265]',
        label: 'text-[#8B7043]',
    },
    tomorrow: {
        en: 'Tomorrow', ar: 'غداً',
        chip: 'bg-[#1B365D] text-[#F5E7C8]',
        ring: 'ring-2 ring-[#1B365D]/20',
        dot: 'bg-[#1B365D]',
        label: 'text-[#1B365D]',
    },
    pending: {
        en: 'Upcoming', ar: 'قادم',
        chip: 'bg-slate-100 text-slate-600',
        ring: 'ring-1 ring-slate-200',
        dot: 'bg-slate-400',
        label: 'text-slate-600',
    },
};

/* ── Grouping ──────────────────────────────────────────────── */
const groupedUpcoming = computed(() => {
    const groups = {};
    for (const apt of props.upcoming) {
        const date = apt.appointment_date;
        if (!groups[date]) groups[date] = [];
        groups[date].push(apt);
    }
    return groups;
});

function formatDate(date) {
    const d = new Date(date + 'T00:00:00');
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === tomorrow.toDateString()) return isRtl.value ? 'غداً' : 'Tomorrow';
    return d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', { weekday: 'long', month: 'long', day: 'numeric' });
}

function formatTime(t) {
    if (!t) return '';
    const [h, m] = t.split(':');
    const hour = parseInt(h);
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`;
}

function formatHour(h) {
    return `${h % 12 || 12} ${h >= 12 ? 'PM' : 'AM'}`;
}

const maxHourly = computed(() => Math.max(...Object.values(props.hourlyToday || {}), 1));

function initials(name) {
    if (!name) return '?';
    return name.trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'تذكيرات المواعيد' : 'Appointment Reminders'">
        <div class="space-y-6">
            <!-- ═════════ Navy Hero Header ═════════ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
                <!-- decorative gold orbs -->
                <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
                <!-- gold accent line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>

                <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-5 p-6 md:p-7">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg shadow-black/20 flex-shrink-0">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'مركز التذكيرات' : 'Reminders Center' }}</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
                                {{ isRtl ? 'تذكيرات المواعيد' : 'Appointment Reminders' }}
                            </h1>
                            <p class="text-sm text-white/70 mt-1 max-w-xl">
                                {{ isRtl
                                    ? 'المواعيد القادمة، حالة التذكيرات، وتحليلات الحمل على مدار اليوم'
                                    : 'Upcoming appointments, reminder status, and today\'s load analytics' }}
                            </p>
                        </div>
                    </div>

                    <!-- SMS status pill -->
                    <div class="flex-shrink-0">
                        <div class="inline-flex items-center gap-3 rounded-xl backdrop-blur-sm px-4 py-3 border"
                            :class="smsSettings.sms_enabled
                                ? 'bg-emerald-500/10 border-emerald-400/30'
                                : 'bg-red-500/10 border-red-400/30'">
                            <span class="relative flex h-2.5 w-2.5">
                                <span v-if="smsSettings.sms_enabled" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                                    :class="smsSettings.sms_enabled ? 'bg-emerald-400' : 'bg-red-400'"></span>
                            </span>
                            <div>
                                <div class="text-sm font-bold" :class="smsSettings.sms_enabled ? 'text-emerald-300' : 'text-red-300'">
                                    {{ smsSettings.sms_enabled ? (isRtl ? 'SMS مفعل' : 'SMS Active') : (isRtl ? 'SMS معطل' : 'SMS Disabled') }}
                                </div>
                                <div class="text-[10px] text-white/60 flex gap-2 mt-0.5" dir="ltr">
                                    <span>{{ isRtl ? 'قبل يوم' : 'Day-before' }} {{ smsSettings.sms_reminder_day_before ? '✓' : '✗' }}</span>
                                    <span>•</span>
                                    <span>{{ isRtl ? 'نفس اليوم' : 'Same-day' }} {{ smsSettings.sms_reminder_same_day ? '✓' : '✗' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═════════ Stat Cards ═════════ -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Today -->
                <div class="group relative bg-white rounded-2xl border border-[#C4A265]/20 p-5 overflow-hidden hover:shadow-lg hover:shadow-[#C4A265]/10 transition">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-xs font-semibold text-[#8B7043] tracking-wide uppercase mb-2">{{ isRtl ? 'اليوم' : 'Today' }}</div>
                            <div class="text-3xl font-extrabold text-[#1B365D]">{{ stats.today }}</div>
                            <div class="text-[11px] text-slate-400 mt-1">{{ isRtl ? 'موعد' : 'appointments' }}</div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#C4A265]/20 to-[#C4A265]/5 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Tomorrow -->
                <div class="group relative bg-white rounded-2xl border border-[#1B365D]/15 p-5 overflow-hidden hover:shadow-lg hover:shadow-[#1B365D]/10 transition">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#1B365D] to-[#2C4E7A]"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-xs font-semibold text-[#1B365D]/70 tracking-wide uppercase mb-2">{{ isRtl ? 'غداً' : 'Tomorrow' }}</div>
                            <div class="text-3xl font-extrabold text-[#1B365D]">{{ stats.tomorrow }}</div>
                            <div class="text-[11px] text-slate-400 mt-1">{{ isRtl ? 'موعد' : 'appointments' }}</div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#1B365D]/15 to-[#1B365D]/5 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- This Week -->
                <div class="group relative bg-white rounded-2xl border border-slate-200 p-5 overflow-hidden hover:shadow-lg hover:shadow-slate-200/50 transition">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-slate-400 to-slate-600"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-xs font-semibold text-slate-500 tracking-wide uppercase mb-2">{{ isRtl ? 'هذا الأسبوع' : 'This Week' }}</div>
                            <div class="text-3xl font-extrabold text-slate-700">{{ stats.week }}</div>
                            <div class="text-[11px] text-slate-400 mt-1">{{ isRtl ? '٧ أيام قادمة' : 'next 7 days' }}</div>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                        </div>
                    </div>
                </div>

                <!-- No-Shows -->
                <div class="group relative bg-white rounded-2xl border p-5 overflow-hidden transition hover:shadow-lg"
                    :class="stats.no_shows_month > 0 ? 'border-red-200 hover:shadow-red-100' : 'border-emerald-200 hover:shadow-emerald-100'">
                    <div class="absolute top-0 inset-x-0 h-1"
                        :class="stats.no_shows_month > 0 ? 'bg-gradient-to-r from-red-500 to-red-600' : 'bg-gradient-to-r from-emerald-400 to-emerald-600'"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-xs font-semibold tracking-wide uppercase mb-2"
                                :class="stats.no_shows_month > 0 ? 'text-red-600' : 'text-emerald-600'">
                                {{ isRtl ? 'لم يحضروا' : 'No-Shows' }}
                            </div>
                            <div class="text-3xl font-extrabold" :class="stats.no_shows_month > 0 ? 'text-red-600' : 'text-emerald-600'">
                                {{ stats.no_shows_month }}
                            </div>
                            <div class="text-[11px] text-slate-400 mt-1">{{ isRtl ? 'هذا الشهر' : 'this month' }}</div>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                            :class="stats.no_shows_month > 0 ? 'bg-red-50' : 'bg-emerald-50'">
                            <svg class="w-5 h-5" :class="stats.no_shows_month > 0 ? 'text-red-500' : 'text-emerald-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="stats.no_shows_month > 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═════════ Main grid ═════════ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- ── Upcoming Appointments (2/3) ── -->
                <div class="lg:col-span-2 space-y-5">
                    <template v-if="Object.keys(groupedUpcoming).length">
                        <div v-for="(apts, date) in groupedUpcoming" :key="date" class="space-y-2.5">
                            <!-- Date header -->
                            <div class="flex items-center gap-3">
                                <div class="h-px flex-1 bg-gradient-to-r from-[#C4A265]/30 to-transparent rtl:bg-gradient-to-l"></div>
                                <div class="flex items-center gap-2 bg-white border border-[#C4A265]/30 rounded-full px-4 py-1.5 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C4A265]"></span>
                                    <span class="text-sm font-bold text-[#1B365D]">{{ formatDate(date) }}</span>
                                    <span class="text-[10px] text-slate-400">·</span>
                                    <span class="text-[11px] font-medium text-slate-500">{{ apts.length }} {{ isRtl ? 'موعد' : 'appts' }}</span>
                                </div>
                                <div class="h-px flex-1 bg-gradient-to-l from-[#C4A265]/30 to-transparent rtl:bg-gradient-to-r"></div>
                            </div>

                            <!-- Appointment cards -->
                            <div class="space-y-2.5">
                                <div v-for="apt in apts" :key="apt.id"
                                    class="group relative bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4 transition hover:border-[#C4A265]/40 hover:shadow-md hover:shadow-[#1B365D]/5"
                                    :class="reminderConfig[apt.reminder_status]?.ring">
                                    <!-- Status accent bar -->
                                    <div class="absolute top-4 bottom-4 start-0 w-1 rounded-e-full"
                                        :class="reminderConfig[apt.reminder_status]?.dot"></div>

                                    <!-- Time block -->
                                    <div class="flex-shrink-0 text-center w-20 ms-2">
                                        <div class="text-base font-extrabold text-[#1B365D] leading-tight" dir="ltr">{{ formatTime(apt.start_time) }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5" dir="ltr">→ {{ formatTime(apt.end_time) }}</div>
                                    </div>

                                    <!-- Avatar -->
                                    <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-gradient-to-br from-[#1B365D] to-[#2C4E7A] flex items-center justify-center text-white text-sm font-bold shadow-sm ring-1 ring-[#C4A265]/20">
                                        {{ initials(apt.patient_name) }}
                                    </div>

                                    <!-- Patient info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <h4 class="text-sm font-bold text-slate-900 truncate">{{ apt.patient_name }}</h4>
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                :class="reminderConfig[apt.reminder_status]?.chip">
                                                {{ isRtl ? reminderConfig[apt.reminder_status]?.ar : reminderConfig[apt.reminder_status]?.en }}
                                            </span>
                                        </div>
                                        <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 text-xs text-slate-600">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 text-[#C4A265]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                                                {{ isRtl ? apt.doctor_name_ar : apt.doctor_name_en }}
                                            </span>
                                            <span v-if="apt.service_name_en" class="text-slate-400">·</span>
                                            <span v-if="apt.service_name_en" class="text-slate-500">{{ isRtl ? apt.service_name_ar : apt.service_name_en }}</span>
                                            <span v-if="apt.session_number" class="text-slate-400">·</span>
                                            <span v-if="apt.session_number" class="inline-flex items-center rounded bg-[#1B365D]/5 text-[#1B365D] text-[10px] font-bold px-1.5 py-0.5">
                                                #{{ apt.session_number }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1" dir="ltr">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68l1.5 4.49a1 1 0 01-.5 1.21l-2.26 1.13a11 11 0 005.52 5.52l1.13-2.26a1 1 0 011.21-.5l4.49 1.5a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.72 21 3 14.28 3 6V5z"/></svg>
                                            {{ apt.phone }}
                                        </div>
                                    </div>

                                    <!-- Status + actions -->
                                    <div class="flex-shrink-0 flex flex-col items-end gap-2">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-semibold ring-1"
                                            :class="statusConfig[apt.status]?.class || 'bg-slate-100 text-slate-600 ring-slate-200'">
                                            {{ isRtl ? statusConfig[apt.status]?.ar : statusConfig[apt.status]?.en }}
                                        </span>
                                        <a v-if="apt.phone" :href="`tel:${apt.phone}`"
                                            class="inline-flex items-center gap-1 text-[10px] font-semibold text-[#1B365D] hover:text-[#C4A265] transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68l1.5 4.49a1 1 0 01-.5 1.21l-2.26 1.13a11 11 0 005.52 5.52l1.13-2.26a1 1 0 011.21-.5l4.49 1.5a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.72 21 3 14.28 3 6V5z"/></svg>
                                            {{ isRtl ? 'اتصال' : 'Call' }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div v-else class="bg-gradient-to-br from-white to-slate-50 rounded-2xl border-2 border-dashed border-[#C4A265]/30 p-16 text-center">
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-[#1B365D]/10 to-[#C4A265]/10 flex items-center justify-center">
                            <svg class="w-10 h-10 text-[#1B365D]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-[#1B365D]">{{ isRtl ? 'لا توجد مواعيد قادمة' : 'No Upcoming Appointments' }}</h3>
                        <p class="text-sm text-slate-500 mt-1">{{ isRtl ? 'ستظهر المواعيد هنا عند جدولتها' : 'Appointments will appear here once scheduled' }}</p>
                    </div>
                </div>

                <!-- ── Sidebar (1/3) ── -->
                <div class="space-y-5">
                    <!-- Today's Hourly Load -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-[#1B365D] to-[#2C4E7A] px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <h3 class="text-sm font-bold text-white">{{ isRtl ? 'حمل اليوم بالساعة' : "Today's Hourly Load" }}</h3>
                            </div>
                            <span v-if="Object.keys(hourlyToday).length" class="text-[10px] font-bold text-[#C4A265] bg-black/20 px-2 py-0.5 rounded">
                                MAX {{ maxHourly }}
                            </span>
                        </div>
                        <div class="p-4">
                            <div v-if="Object.keys(hourlyToday).length" class="space-y-2">
                                <div v-for="h in 24" :key="h-1" v-show="hourlyToday[h-1]"
                                    class="flex items-center gap-2.5 group">
                                    <span class="text-[10px] font-semibold text-slate-500 w-12 flex-shrink-0 text-end tabular-nums">{{ formatHour(h-1) }}</span>
                                    <div class="flex-1 bg-slate-100 rounded-full h-3.5 overflow-hidden relative">
                                        <div class="h-full rounded-full bg-gradient-to-r from-[#1B365D] to-[#C4A265] transition-all flex items-center justify-end pe-1.5 text-[9px] text-white font-bold"
                                            :style="{ width: `${(hourlyToday[h-1] / maxHourly) * 100}%`, minWidth: '22px' }">
                                            {{ hourlyToday[h-1] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <svg class="w-10 h-10 mx-auto text-slate-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12 1-7 12H9z"/></svg>
                                <p class="text-xs text-slate-400">{{ isRtl ? 'لا توجد مواعيد اليوم' : 'No appointments today' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- No-Show Patients -->
                    <div v-if="noShowPatients?.length" class="bg-white rounded-2xl border border-red-200 shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-red-500 to-red-600 px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <h3 class="text-sm font-bold text-white">{{ isRtl ? 'لم يحضروا (هذا الشهر)' : 'No-Shows (This Month)' }}</h3>
                            </div>
                            <span class="text-[10px] font-bold text-white bg-white/20 px-2 py-0.5 rounded">{{ noShowPatients.length }}</span>
                        </div>
                        <div class="p-3 space-y-1.5 max-h-80 overflow-y-auto">
                            <div v-for="ns in noShowPatients" :key="ns.id"
                                class="flex items-center gap-2.5 p-2 rounded-lg bg-red-50/50 hover:bg-red-50 transition group">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ initials(ns.patient_name) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-slate-800 truncate">{{ ns.patient_name }}</div>
                                    <div class="text-[10px] text-slate-500 flex items-center gap-1" dir="ltr">
                                        <span>{{ ns.appointment_date }}</span>
                                        <span class="text-slate-300">·</span>
                                        <span>{{ formatTime(ns.start_time) }}</span>
                                    </div>
                                </div>
                                <a v-if="ns.phone" :href="`tel:${ns.phone}`" class="opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68l1.5 4.49a1 1 0 01-.5 1.21l-2.26 1.13a11 11 0 005.52 5.52l1.13-2.26a1 1 0 011.21-.5l4.49 1.5a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.72 21 3 14.28 3 6V5z"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- SMS Quick Settings Card -->
                    <div class="bg-gradient-to-br from-[#1B365D] to-[#0F2444] rounded-2xl shadow-lg overflow-hidden relative">
                        <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-[#C4A265]/20 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                </div>
                                <h3 class="text-sm font-bold text-white">{{ isRtl ? 'إعدادات SMS' : 'SMS Settings' }}</h3>
                            </div>
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-white/70">{{ isRtl ? 'تذكير قبل يوم' : 'Day-before reminder' }}</span>
                                    <span class="inline-flex items-center gap-1 font-bold" :class="smsSettings.sms_reminder_day_before ? 'text-emerald-400' : 'text-red-400'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="smsSettings.sms_reminder_day_before ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                        {{ smsSettings.sms_reminder_day_before ? (isRtl ? 'مفعل' : 'On') : (isRtl ? 'معطل' : 'Off') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-white/70">{{ isRtl ? 'تذكير نفس اليوم' : 'Same-day reminder' }}</span>
                                    <span class="inline-flex items-center gap-1 font-bold" :class="smsSettings.sms_reminder_same_day ? 'text-emerald-400' : 'text-red-400'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="smsSettings.sms_reminder_same_day ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                        {{ smsSettings.sms_reminder_same_day ? (isRtl ? 'مفعل' : 'On') : (isRtl ? 'معطل' : 'Off') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs pt-2 border-t border-white/10">
                                    <span class="text-white/70">{{ isRtl ? 'وقت الإرسال' : 'Reminder time' }}</span>
                                    <span class="font-bold text-[#C4A265]" dir="ltr">{{ smsSettings.sms_reminder_hour }}</span>
                                </div>
                            </div>
                            <Link href="/admin/settings" class="mt-4 flex items-center justify-center gap-2 w-full rounded-lg border border-[#C4A265]/30 bg-[#C4A265]/10 hover:bg-[#C4A265]/20 px-3 py-2 text-xs font-bold text-[#C4A265] transition">
                                {{ isRtl ? 'إدارة الإعدادات' : 'Manage Settings' }}
                                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
