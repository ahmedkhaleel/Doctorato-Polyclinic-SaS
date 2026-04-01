<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
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

/* ── Status Config ──────────────────────────────── */
const statusConfig = {
    scheduled: { en: 'Scheduled', ar: 'مجدول', color: 'bg-gray-100 text-gray-700' },
    confirmed: { en: 'Confirmed', ar: 'مؤكد', color: 'bg-emerald-100 text-emerald-700' },
};

const reminderConfig = {
    same_day: { en: 'Today', ar: 'اليوم', color: 'bg-red-100 text-red-700', ring: 'ring-red-200' },
    tomorrow: { en: 'Tomorrow', ar: 'غداً', color: 'bg-amber-100 text-amber-700', ring: 'ring-amber-200' },
    pending: { en: 'Upcoming', ar: 'قادم', color: 'bg-cyan-100 text-cyan-700', ring: 'ring-cyan-200' },
};

/* ── Grouping ───────────────────────────────────── */
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
</script>

<template>
    <AdminLayout :title="isRtl ? 'تذكيرات المواعيد' : 'Appointment Reminders'">
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'تذكيرات المواعيد' : 'Appointment Reminders' }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'المواعيد القادمة وحالة التذكيرات والتحليلات' : 'Upcoming appointments, reminder status, and analytics' }}</p>
            </div>

            <!-- Stats + SMS Status -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl border border-red-200 p-4">
                    <div class="text-2xl font-bold text-red-600">{{ stats.today }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'مواعيد اليوم' : 'Today' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-amber-200 p-4">
                    <div class="text-2xl font-bold text-amber-600">{{ stats.tomorrow }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'مواعيد غداً' : 'Tomorrow' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-cyan-200 p-4">
                    <div class="text-2xl font-bold text-cyan-600">{{ stats.week }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'هذا الأسبوع' : 'This Week' }}</div>
                </div>
                <div class="bg-white rounded-xl border p-4" :class="stats.no_shows_month > 0 ? 'border-red-200' : 'border-gray-200'">
                    <div class="text-2xl font-bold" :class="stats.no_shows_month > 0 ? 'text-red-600' : 'text-gray-400'">{{ stats.no_shows_month }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'لم يحضروا (الشهر)' : 'No-Shows (Month)' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" :class="smsSettings.sms_enabled ? 'bg-emerald-500' : 'bg-red-500'"></div>
                        <span class="text-sm font-semibold" :class="smsSettings.sms_enabled ? 'text-emerald-600' : 'text-red-600'">
                            {{ smsSettings.sms_enabled ? (isRtl ? 'SMS مفعل' : 'SMS Active') : (isRtl ? 'SMS معطل' : 'SMS Disabled') }}
                        </span>
                    </div>
                    <div class="text-[10px] text-gray-400 mt-1 space-y-0.5">
                        <div>{{ isRtl ? 'قبل يوم:' : 'Day before:' }} {{ smsSettings.sms_reminder_day_before ? '✓' : '✗' }}</div>
                        <div>{{ isRtl ? 'نفس اليوم:' : 'Same day:' }} {{ smsSettings.sms_reminder_same_day ? '✓' : '✗' }}</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Upcoming Appointments (2/3 width) -->
                <div class="lg:col-span-2 space-y-4">
                    <template v-if="Object.keys(groupedUpcoming).length">
                        <div v-for="(apts, date) in groupedUpcoming" :key="date">
                            <div class="sticky top-0 z-10 bg-gray-50/90 backdrop-blur-sm px-3 py-2 rounded-lg mb-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-700">{{ formatDate(date) }}</span>
                                    <span class="text-xs text-gray-400">{{ apts.length }} {{ isRtl ? 'موعد' : 'appointments' }}</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div v-for="apt in apts" :key="apt.id"
                                    class="bg-white rounded-xl border p-4 flex items-center gap-4 hover:shadow-sm transition"
                                    :class="reminderConfig[apt.reminder_status]?.ring">
                                    <!-- Time -->
                                    <div class="flex-shrink-0 text-center w-16">
                                        <div class="text-sm font-bold text-gray-900">{{ formatTime(apt.start_time) }}</div>
                                        <div class="text-[10px] text-gray-400">{{ formatTime(apt.end_time) }}</div>
                                    </div>

                                    <!-- Divider -->
                                    <div class="w-px h-10 bg-gray-200 flex-shrink-0"></div>

                                    <!-- Patient Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-semibold text-gray-900 truncate">{{ apt.patient_name }}</h4>
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium"
                                                :class="reminderConfig[apt.reminder_status]?.color">
                                                {{ isRtl ? reminderConfig[apt.reminder_status]?.ar : reminderConfig[apt.reminder_status]?.en }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ isRtl ? apt.doctor_name_ar : apt.doctor_name_en }}
                                            <span v-if="apt.service_name_en"> · {{ isRtl ? apt.service_name_ar : apt.service_name_en }}</span>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-0.5" dir="ltr">{{ apt.phone }}</div>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium"
                                            :class="statusConfig[apt.status]?.color || 'bg-gray-100 text-gray-600'">
                                            {{ isRtl ? statusConfig[apt.status]?.ar : statusConfig[apt.status]?.en }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div v-else class="bg-white rounded-xl border border-gray-200 p-16 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <h3 class="text-lg font-medium text-gray-900">{{ isRtl ? 'لا توجد مواعيد قادمة' : 'No Upcoming Appointments' }}</h3>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Today's Hourly Load -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ isRtl ? 'حمل اليوم بالساعة' : "Today's Hourly Load" }}</h3>
                        <div v-if="Object.keys(hourlyToday).length" class="space-y-1.5">
                            <div v-for="h in 24" :key="h-1" v-show="hourlyToday[h-1]"
                                class="flex items-center gap-2">
                                <span class="text-[10px] text-gray-500 w-12 flex-shrink-0 text-end">{{ formatHour(h-1) }}</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-3">
                                    <div class="bg-cyan-500 h-3 rounded-full transition-all text-[9px] text-white font-bold flex items-center justify-center"
                                        :style="{ width: `${(hourlyToday[h-1] / maxHourly) * 100}%`, minWidth: '16px' }">
                                        {{ hourlyToday[h-1] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد مواعيد اليوم' : 'No appointments today' }}</p>
                    </div>

                    <!-- No-Show Patients -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4" v-if="noShowPatients?.length">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            {{ isRtl ? 'لم يحضروا (هذا الشهر)' : 'No-Shows (This Month)' }}
                        </h3>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <div v-for="ns in noShowPatients" :key="ns.id"
                                class="flex items-center gap-2 p-2 rounded-lg bg-red-50">
                                <div class="w-7 h-7 rounded-full bg-red-200 flex items-center justify-center text-red-700 text-xs font-bold flex-shrink-0">
                                    {{ ns.patient_name?.charAt(0) || '?' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-900 truncate">{{ ns.patient_name }}</div>
                                    <div class="text-[10px] text-gray-500">{{ ns.appointment_date }} · {{ formatTime(ns.start_time) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
