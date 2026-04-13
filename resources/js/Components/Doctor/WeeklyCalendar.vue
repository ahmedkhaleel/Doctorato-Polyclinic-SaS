<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    /** Array of booking/visit objects with preferred_date, preferred_time, patient, status */
    events: { type: Array, default: () => [] },
    isRtl: { type: Boolean, default: false },
    /** Status config object for styling { status_key: { bg, text, dot, border } } */
    statusConfig: { type: Object, default: () => ({}) },
    accentColor: { type: String, default: '#C4A265' },
});

const emit = defineEmits(['event-click', 'week-change']);

const currentWeekStart = ref(getWeekStart(new Date()));

function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    // Saturday = 6 as start of week for Arabic calendar
    const diff = (day + 1) % 7;
    d.setDate(d.getDate() - diff);
    d.setHours(0, 0, 0, 0);
    return d;
}

const weekDays = computed(() => {
    const days = [];
    const start = new Date(currentWeekStart.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let i = 0; i < 7; i++) {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        const dateStr = d.toISOString().split('T')[0];
        days.push({
            date: d,
            dateStr,
            dayName: d.toLocaleDateString(props.isRtl ? 'ar-EG' : 'en-US', { weekday: 'short' }),
            dayNum: d.getDate(),
            isToday: d.getTime() === today.getTime(),
            events: props.events.filter(e => e.preferred_date === dateStr),
        });
    }
    return days;
});

const weekLabel = computed(() => {
    const start = new Date(currentWeekStart.value);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    const opts = { day: 'numeric', month: 'short' };
    const locale = props.isRtl ? 'ar-EG' : 'en-US';
    return `${start.toLocaleDateString(locale, opts)} - ${end.toLocaleDateString(locale, opts)}`;
});

const isCurrentWeek = computed(() => {
    const today = getWeekStart(new Date());
    return currentWeekStart.value.getTime() === today.getTime();
});

function previousWeek() {
    const d = new Date(currentWeekStart.value);
    d.setDate(d.getDate() - 7);
    currentWeekStart.value = d;
    emit('week-change', d);
}

function nextWeek() {
    const d = new Date(currentWeekStart.value);
    d.setDate(d.getDate() + 7);
    currentWeekStart.value = d;
    emit('week-change', d);
}

function goToday() {
    currentWeekStart.value = getWeekStart(new Date());
    emit('week-change', currentWeekStart.value);
}

// Time slots for the day view (8 AM to 8 PM)
const timeSlots = computed(() => {
    const slots = [];
    for (let h = 8; h <= 20; h++) {
        slots.push({
            hour: h,
            label: `${h % 12 || 12}${h < 12 ? ' AM' : ' PM'}`,
            time24: `${String(h).padStart(2, '0')}:00`,
        });
    }
    return slots;
});

function getEventForSlot(day, slotHour) {
    return day.events.filter(e => {
        if (!e.preferred_time) return false;
        const h = parseInt(e.preferred_time.split(':')[0]);
        return h === slotHour;
    });
}

function formatTime(time) {
    if (!time) return '';
    const [h, m] = time.split(':');
    const hour = parseInt(h);
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`;
}

function getStatusStyle(status) {
    const cfg = props.statusConfig[status];
    if (cfg) return cfg;
    return { bg: 'bg-gray-50', text: 'text-gray-600', dot: 'bg-gray-400', border: 'border-gray-200' };
}
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Calendar Header -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                    :style="{ backgroundColor: accentColor + '15' }">
                    <svg class="w-4 h-4" :style="{ color: accentColor }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'التقويم الأسبوعي' : 'Weekly Calendar' }}</h3>
                    <p class="text-[10px] text-gray-400">{{ weekLabel }}</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <button v-if="!isCurrentWeek" @click="goToday"
                    class="px-2.5 py-1 text-[10px] font-semibold rounded-lg transition-all"
                    :style="{ color: accentColor, backgroundColor: accentColor + '10' }">
                    {{ isRtl ? 'اليوم' : 'Today' }}
                </button>
                <button @click="previousWeek" class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <button @click="nextWeek" class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
            </div>
        </div>

        <!-- Days Row (Compact View) -->
        <div class="grid grid-cols-7 border-b border-gray-100">
            <div v-for="day in weekDays" :key="day.dateStr"
                class="text-center py-3 border-e border-gray-100 last:border-e-0 transition-colors"
                :class="day.isToday ? 'bg-gray-50/80' : ''">
                <p class="text-[10px] font-semibold uppercase tracking-wide"
                    :class="day.isToday ? 'text-gray-800' : 'text-gray-400'">{{ day.dayName }}</p>
                <div class="w-8 h-8 mx-auto mt-1 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                    :class="day.isToday ? 'text-white shadow-sm' : 'text-gray-600'"
                    :style="day.isToday ? { backgroundColor: accentColor } : {}">
                    {{ day.dayNum }}
                </div>
                <!-- Event count dots -->
                <div v-if="day.events.length > 0" class="flex items-center justify-center gap-0.5 mt-1.5">
                    <span v-for="(evt, i) in day.events.slice(0, 4)" :key="i"
                        class="w-1.5 h-1.5 rounded-full"
                        :class="getStatusStyle(evt.status)?.dot || 'bg-gray-300'"></span>
                    <span v-if="day.events.length > 4" class="text-[8px] text-gray-400 font-bold ml-0.5">+{{ day.events.length - 4 }}</span>
                </div>
                <p v-else class="text-[9px] text-gray-300 mt-1.5">-</p>
            </div>
        </div>

        <!-- Events Timeline -->
        <div class="max-h-[400px] overflow-y-auto">
            <div v-for="day in weekDays.filter(d => d.events.length > 0)" :key="'events-'+day.dateStr"
                class="border-b border-gray-100 last:border-b-0">
                <!-- Day Label -->
                <div class="px-4 py-2 bg-gray-50/50 sticky top-0 z-10 flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full" :style="day.isToday ? { backgroundColor: accentColor } : { backgroundColor: '#d1d5db' }"></div>
                    <p class="text-[11px] font-bold" :class="day.isToday ? 'text-gray-900' : 'text-gray-500'">
                        {{ day.isToday ? (isRtl ? 'اليوم' : 'Today') : day.date.toLocaleDateString(isRtl ? 'ar-EG' : 'en-US', { weekday: 'long', day: 'numeric', month: 'short' }) }}
                    </p>
                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-gray-200 text-gray-500">{{ day.events.length }}</span>
                </div>

                <!-- Event Cards -->
                <div class="px-4 py-2 space-y-2">
                    <div v-for="event in day.events" :key="event.id || event.booking_number"
                        @click="$emit('event-click', event)"
                        class="group flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-sm"
                        :class="[getStatusStyle(event.status)?.bg || 'bg-white', getStatusStyle(event.status)?.border || 'border-gray-200']">
                        <!-- Time -->
                        <div class="flex-shrink-0 text-center min-w-[52px]">
                            <p class="text-xs font-bold" :class="getStatusStyle(event.status)?.text || 'text-gray-600'">
                                {{ formatTime(event.preferred_time) || '-' }}
                            </p>
                        </div>
                        <!-- Divider -->
                        <div class="w-px h-8 self-stretch rounded-full" :class="getStatusStyle(event.status)?.dot || 'bg-gray-200'"></div>
                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-gray-900">
                                {{ event.patient?.full_name || event.patient_name || '-' }}
                            </p>
                            <p class="text-[11px] text-gray-500 truncate mt-0.5">
                                {{ event.service?.name_ar || event.service?.name_en || event.service_name || '' }}
                            </p>
                        </div>
                        <!-- Status Badge -->
                        <span class="flex-shrink-0 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                            :class="[getStatusStyle(event.status)?.bg, getStatusStyle(event.status)?.text]">
                            <span class="w-1.5 h-1.5 rounded-full" :class="getStatusStyle(event.status)?.dot"></span>
                            {{ props.statusConfig[event.status]?.label || event.status }}
                        </span>
                        <!-- Arrow -->
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- No Events This Week -->
            <div v-if="weekDays.every(d => d.events.length === 0)" class="py-12 text-center">
                <div class="w-14 h-14 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-3 border border-gray-100">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد حجوزات هذا الأسبوع' : 'No bookings this week' }}</p>
            </div>
        </div>
    </div>
</template>
