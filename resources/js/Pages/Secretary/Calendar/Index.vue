<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    events: Object,
    doctors: Array,
    vacations: Array,
    schedules: Object,
    stats: Object,
    filters: Object,
});

const currentMonth = ref(props.filters?.month || new Date().toISOString().slice(0, 7));
const doctorId = ref(props.filters?.doctor_id || '');
const selectedDay = ref(null);

function applyFilters() {
    router.get('/secretary/calendar', {
        month: currentMonth.value,
        doctor_id: doctorId.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([currentMonth, doctorId], applyFilters);

function prevMonth() {
    const d = new Date(currentMonth.value + '-01');
    d.setMonth(d.getMonth() - 1);
    currentMonth.value = d.toISOString().slice(0, 7);
}

function nextMonth() {
    const d = new Date(currentMonth.value + '-01');
    d.setMonth(d.getMonth() + 1);
    currentMonth.value = d.toISOString().slice(0, 7);
}

function goToday() {
    currentMonth.value = new Date().toISOString().slice(0, 7);
}

const monthLabel = computed(() => {
    const d = new Date(currentMonth.value + '-01');
    return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const calendarDays = computed(() => {
    const first = new Date(currentMonth.value + '-01');
    const year = first.getFullYear();
    const month = first.getMonth();
    let startDay = first.getDay();
    startDay = (startDay + 1) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const days = [];
    for (let i = 0; i < startDay; i++) {
        days.push({ date: null, day: null });
    }
    const todayStr = new Date().toISOString().slice(0, 10);
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const dayEvents = props.events?.[dateStr] || [];
        days.push({
            date: dateStr,
            day: d,
            events: dayEvents,
            isToday: dateStr === todayStr,
            visitCount: dayEvents.filter(e => e.type === 'visit').length,
            bookingCount: dayEvents.filter(e => e.type === 'booking').length,
        });
    }
    return days;
});

const weekDays = ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

const selectedDayEvents = computed(() => {
    if (!selectedDay.value) return [];
    return props.events?.[selectedDay.value] || [];
});

function selectDay(day) {
    if (!day.date) return;
    selectedDay.value = selectedDay.value === day.date ? null : day.date;
}

function statusColor(status, type) {
    if (type === 'booking') {
        const colors = { new: 'bg-slate-100 text-[#1B365D]', contacted: 'bg-yellow-100 text-amber-700', confirmed: 'bg-emerald-100 text-emerald-700', completed: 'bg-gray-100 text-gray-600', cancelled: 'bg-red-100 text-red-600' };
        return colors[status] || 'bg-gray-100 text-gray-600';
    }
    const colors = { waiting: 'bg-amber-100 text-amber-700', in_progress: 'bg-slate-100 text-[#1B365D]', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-red-100 text-red-600' };
    return colors[status] || 'bg-gray-100 text-gray-600';
}

function eventDotColor(event) {
    if (event.type === 'booking') return 'bg-slate-400';
    const colors = { waiting: 'bg-amber-400', in_progress: 'bg-[#1B365D]', completed: 'bg-emerald-400', cancelled: 'bg-red-400' };
    return colors[event.status] || 'bg-gray-400';
}

function formatSelectedDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'تقويم المواعيد' : 'Appointment Calendar' }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'عرض جميع الزيارات والحجوزات حسب التاريخ.' : 'View all visits and bookings by date.' }}</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <Link href="/secretary/bookings/create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-teal-500 to-[#1B365D] text-white text-sm font-medium rounded-xl hover:from-teal-600 hover:to-[#1B365D] transition-all duration-200 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'حجز جديد' : 'New Booking' }}
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm text-center">
                <p class="text-lg font-bold text-gray-900">{{ stats?.total_visits || 0 }}</p>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ isRtl ? 'الزيارات' : 'Visits' }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm text-center">
                <p class="text-lg font-bold text-emerald-600">{{ stats?.completed_visits || 0 }}</p>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm text-center">
                <p class="text-lg font-bold text-amber-600">{{ stats?.waiting_visits || 0 }}</p>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ isRtl ? 'انتظار' : 'Waiting' }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm text-center">
                <p class="text-lg font-bold text-[#1B365D]">{{ stats?.total_bookings || 0 }}</p>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ isRtl ? 'الحجوزات' : 'Bookings' }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm text-center">
                <p class="text-lg font-bold text-[#1B365D]">{{ stats?.pending_bookings || 0 }}</p>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ isRtl ? 'معلق' : 'Pending' }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm text-center">
                <p class="text-lg font-bold text-emerald-500">{{ stats?.confirmed_bookings || 0 }}</p>
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ isRtl ? 'مؤكد' : 'Confirmed' }}</p>
            </div>
        </div>

        <!-- Controls -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-3 sm:p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button @click="prevMonth" class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <h2 class="text-lg font-bold text-gray-900 min-w-[180px] text-center">{{ monthLabel }}</h2>
                <button @click="nextMonth" class="w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
                <button @click="goToday" class="px-3 py-1.5 text-xs font-medium text-teal-600 bg-teal-50 rounded-lg hover:bg-teal-100 transition-colors ltr:ml-2 rtl:mr-2">{{ isRtl ? 'اليوم' : 'Today' }}</button>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <select v-model="doctorId" class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-colors">
                    <option value="">{{ isRtl ? 'جميع الأطباء' : 'All Doctors' }}</option>
                    <option v-for="doc in doctors" :key="doc.id" :value="doc.id">{{ doc.name_en }}</option>
                </select>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>{{ isRtl ? 'زيارة' : 'Visit' }}</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>{{ isRtl ? 'حجز' : 'Booking' }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Calendar Grid -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="grid grid-cols-7 bg-gray-50/80">
                    <div v-for="day in weekDays" :key="day" class="py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ day }}</div>
                </div>
                <div class="grid grid-cols-7 border-t border-gray-100">
                    <div
                        v-for="(day, idx) in calendarDays"
                        :key="idx"
                        @click="selectDay(day)"
                        class="min-h-[60px] sm:min-h-[90px] p-1 sm:p-1.5 border-b border-r border-gray-100 transition-colors duration-150"
                        :class="{
                            'bg-white hover:bg-gray-50 cursor-pointer': day.date,
                            'bg-gray-50/50': !day.date,
                            'ring-2 ring-teal-500 ring-inset bg-teal-50/30': selectedDay === day.date,
                        }"
                    >
                        <div v-if="day.date" class="flex flex-col h-full">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium w-7 h-7 flex items-center justify-center rounded-full" :class="day.isToday ? 'bg-teal-500 text-white' : 'text-gray-700'">{{ day.day }}</span>
                                <span v-if="day.events.length" class="text-[10px] text-gray-400 font-medium">{{ day.events.length }}</span>
                            </div>
                            <div class="flex flex-wrap gap-0.5 mt-auto">
                                <span v-for="(ev, i) in day.events.slice(0, 4)" :key="i" :class="eventDotColor(ev)" class="w-1.5 h-1.5 rounded-full"></span>
                                <span v-if="day.events.length > 4" class="text-[9px] text-gray-400 ml-0.5">+{{ day.events.length - 4 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Day Detail Sidebar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="p-3 sm:p-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 v-if="selectedDay" class="text-sm font-semibold text-gray-900">{{ formatSelectedDate(selectedDay) }}</h3>
                    <h3 v-else class="text-sm font-semibold text-gray-400">{{ isRtl ? 'اختر يوماً لعرض التفاصيل' : 'Select a day to view details' }}</h3>
                </div>
                <div class="p-3 sm:p-4 max-h-[500px] overflow-y-auto">
                    <div v-if="selectedDayEvents.length" class="space-y-2.5">
                        <div
                            v-for="ev in selectedDayEvents"
                            :key="ev.type + '-' + ev.id"
                            class="p-3 rounded-xl border transition-colors duration-150"
                            :class="ev.type === 'visit' ? 'border-emerald-100 bg-emerald-50/30 hover:bg-emerald-50' : 'border-slate-100 bg-slate-50/30 hover:bg-slate-50'"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide" :class="ev.type === 'visit' ? 'text-emerald-600' : 'text-[#1B365D]'">{{ ev.type }}</span>
                                        <span :class="statusColor(ev.status, ev.type)" class="text-[10px] px-1.5 py-0.5 rounded-full font-medium">{{ ev.status }}</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ ev.title }}</p>
                                    <p class="text-xs text-gray-500">{{ ev.doctor }} &middot; {{ ev.service }}</p>
                                    <p v-if="ev.time" class="text-xs text-gray-400 mt-0.5">{{ ev.time }}</p>
                                </div>
                                <Link
                                    v-if="ev.type === 'visit'"
                                    :href="'/secretary/visits/' + ev.id"
                                    class="text-xs text-teal-600 hover:underline flex-shrink-0"
                                >{{ isRtl ? 'عرض' : 'View' }}</Link>
                                <Link
                                    v-else
                                    :href="'/secretary/bookings/' + ev.id"
                                    class="text-xs text-[#1B365D] hover:underline flex-shrink-0"
                                >{{ isRtl ? 'عرض' : 'View' }}</Link>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="selectedDay" class="py-8 text-center">
                        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد مواعيد في هذا اليوم.' : 'No appointments on this day.' }}</p>
                    </div>
                    <div v-else class="py-8 text-center">
                        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" /></svg>
                        <p class="text-sm text-gray-400">{{ isRtl ? 'اضغط على يوم لعرض المواعيد.' : 'Click a day to see appointments.' }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
