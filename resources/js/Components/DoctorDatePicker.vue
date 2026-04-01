<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { onClickOutside } from '@vueuse/core';

const props = defineProps({
    modelValue: { type: String, default: '' },
    doctorId: { type: [Number, String], default: '' },
    doctorSchedules: { type: Array, default: () => [] },
    minDate: { type: String, default: '' },
    accentColor: { type: String, default: '#C4A265' },
    disabled: { type: Boolean, default: false },
    popover: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

/* ------------------------------------------------------------------ */
/*  Popover state                                                      */
/* ------------------------------------------------------------------ */
const isOpen = ref(false);
const containerRef = ref(null);

onClickOutside(containerRef, () => {
    if (props.popover) isOpen.value = false;
});

function togglePopover() {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
}

/* ------------------------------------------------------------------ */
/*  Calendar state                                                     */
/* ------------------------------------------------------------------ */
const currentMonth = ref(getInitialMonth());

function getInitialMonth() {
    if (props.modelValue) return props.modelValue.slice(0, 7);
    return new Date().toISOString().slice(0, 7);
}

/* ------------------------------------------------------------------ */
/*  Day-of-week mapping                                                */
/* ------------------------------------------------------------------ */
// JS getDay(): 0=Sun,1=Mon,2=Tue,3=Wed,4=Thu,5=Fri,6=Sat
// System:      0=Sat,1=Sun,2=Mon,3=Tue,4=Wed,5=Thu,6=Fri
function jsToSystemDay(jsDay) {
    const map = { 0: 1, 1: 2, 2: 3, 3: 4, 4: 5, 5: 6, 6: 0 };
    return map[jsDay];
}

/* ------------------------------------------------------------------ */
/*  Computed                                                           */
/* ------------------------------------------------------------------ */
const effectiveMinDate = computed(() => {
    return props.minDate || new Date().toISOString().split('T')[0];
});

const doctorWorkingDays = computed(() => {
    if (!props.doctorId) return new Set();
    const days = new Set();
    for (const schedule of props.doctorSchedules) {
        if (schedule.doctor_id == props.doctorId) {
            days.add(schedule.day_of_week);
        }
    }
    return days;
});

const weekDays = ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
const weekDaysFull = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const monthLabel = computed(() => {
    const d = new Date(currentMonth.value + '-01');
    return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const monthName = computed(() => {
    const d = new Date(currentMonth.value + '-01');
    return d.toLocaleDateString('en-US', { month: 'long' });
});

const monthYear = computed(() => {
    const d = new Date(currentMonth.value + '-01');
    return d.getFullYear();
});

const calendarDays = computed(() => {
    const first = new Date(currentMonth.value + '-01');
    const year = first.getFullYear();
    const month = first.getMonth();

    // Saturday-start offset
    let startDay = first.getDay(); // 0=Sun
    startDay = (startDay + 1) % 7; // Convert to Sat-start

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const days = [];

    for (let i = 0; i < startDay; i++) {
        days.push({ date: null, day: null });
    }

    const todayStr = new Date().toISOString().split('T')[0];

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const dateObj = new Date(dateStr + 'T00:00:00');
        const systemDay = jsToSystemDay(dateObj.getDay());

        const isPast = dateStr < effectiveMinDate.value;
        const isAvailable = props.doctorId ? doctorWorkingDays.value.has(systemDay) : false;
        const isToday = dateStr === todayStr;
        const isSelected = dateStr === props.modelValue;

        days.push({
            date: dateStr,
            day: d,
            isToday,
            isSelected,
            isPast,
            isAvailable,
            isDisabled: isPast || !isAvailable || !props.doctorId || props.disabled,
            dayName: weekDaysFull[systemDay],
        });
    }

    return days;
});

const canGoPrev = computed(() => {
    const minMonth = effectiveMinDate.value.slice(0, 7);
    return currentMonth.value > minMonth;
});

const formattedSelectedDate = computed(() => {
    if (!props.modelValue) return '';
    const d = new Date(props.modelValue + 'T00:00:00');
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
});

const availableCount = computed(() => {
    return calendarDays.value.filter(d => d.date && d.isAvailable && !d.isPast).length;
});

const selectedDateInfo = computed(() => {
    if (!props.modelValue) return null;
    const d = new Date(props.modelValue + 'T00:00:00');
    return {
        dayName: d.toLocaleDateString('en-US', { weekday: 'long' }),
        dayNum: d.getDate(),
        monthShort: d.toLocaleDateString('en-US', { month: 'short' }),
        year: d.getFullYear(),
    };
});

/* ------------------------------------------------------------------ */
/*  Methods                                                            */
/* ------------------------------------------------------------------ */
function prevMonth() {
    if (!canGoPrev.value) return;
    const d = new Date(currentMonth.value + '-01');
    d.setMonth(d.getMonth() - 1);
    currentMonth.value = d.toISOString().slice(0, 7);
}

function nextMonth() {
    const d = new Date(currentMonth.value + '-01');
    d.setMonth(d.getMonth() + 1);
    currentMonth.value = d.toISOString().slice(0, 7);
}

function selectDay(day) {
    if (day.isDisabled || !day.date) return;
    emit('update:modelValue', day.date);
    if (props.popover) {
        nextTick(() => { isOpen.value = false; });
    }
}

/* ------------------------------------------------------------------ */
/*  Watchers                                                           */
/* ------------------------------------------------------------------ */
watch(() => props.doctorId, (newId, oldId) => {
    if (!newId) {
        emit('update:modelValue', '');
        return;
    }
    if (props.modelValue) {
        const dateObj = new Date(props.modelValue + 'T00:00:00');
        const systemDay = jsToSystemDay(dateObj.getDay());
        const newWorkingDays = new Set();
        for (const s of props.doctorSchedules) {
            if (s.doctor_id == newId) newWorkingDays.add(s.day_of_week);
        }
        if (!newWorkingDays.has(systemDay)) {
            emit('update:modelValue', '');
        }
    }
    currentMonth.value = props.modelValue ? props.modelValue.slice(0, 7) : new Date().toISOString().slice(0, 7);
});

watch(() => props.modelValue, (val) => {
    if (val && val.slice(0, 7) !== currentMonth.value) {
        currentMonth.value = val.slice(0, 7);
    }
});
</script>

<template>
    <!-- ==================== POPOVER MODE ==================== -->
    <div v-if="popover" ref="containerRef" class="relative">
        <button
            type="button"
            @click="togglePopover"
            class="dp-trigger group w-full flex items-center gap-3 px-3.5 py-2.5 bg-white border rounded-xl text-sm transition-all duration-200"
            :class="[
                disabled ? 'opacity-50 cursor-not-allowed border-gray-200' : 'border-gray-200 hover:border-gray-300 hover:shadow-sm cursor-pointer',
                isOpen ? 'dp-trigger-active' : ''
            ]"
        >
            <span class="dp-trigger-icon flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </span>
            <div class="flex-1 text-left min-w-0">
                <span v-if="modelValue" class="text-gray-800 font-medium truncate block">{{ formattedSelectedDate }}</span>
                <span v-else class="text-gray-400">Select date...</span>
            </div>
            <svg class="w-4 h-4 text-gray-300 flex-shrink-0 transition-transform duration-300" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1 scale-[0.98]"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-[0.98]"
        >
            <div v-if="isOpen" class="absolute z-50 mt-2 rounded-2xl dp-popover-panel overflow-hidden w-[330px] right-0">
                <div class="dp-calendar">
                    <!-- Popover Header -->
                    <div class="dp-pop-header">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="prevMonth" :disabled="!canGoPrev"
                                class="dp-pop-nav" :class="canGoPrev ? '' : 'opacity-20 cursor-not-allowed'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <div class="text-center select-none">
                                <span class="text-[15px] font-bold text-gray-800">{{ monthName }}</span>
                                <span class="text-[15px] font-light text-gray-400 ml-1.5">{{ monthYear }}</span>
                            </div>
                            <button type="button" @click="nextMonth" class="dp-pop-nav">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-4 pb-3">
                        <!-- Weekday row -->
                        <div class="grid grid-cols-7 mb-1">
                            <div v-for="(wd, wdIdx) in weekDays" :key="wd"
                                class="text-center text-[10px] font-semibold uppercase tracking-wider py-1.5"
                                :class="wdIdx === 0 || wdIdx === 6 ? 'dp-wd-accent' : 'text-gray-400'">
                                {{ wd }}
                            </div>
                        </div>
                        <div class="dp-sep mb-2"></div>

                        <!-- Grid -->
                        <div class="grid grid-cols-7 gap-[3px] relative">
                            <div v-for="(day, idx) in calendarDays" :key="idx" class="flex items-center justify-center">
                                <button v-if="day.date" type="button" @click="selectDay(day)" :disabled="day.isDisabled"
                                    class="dp-day-pop"
                                    :class="{
                                        'dp-day-pop-selected': day.isSelected,
                                        'dp-day-pop-available': day.isAvailable && !day.isPast && !day.isSelected && !disabled,
                                        'dp-day-pop-today': day.isToday && !day.isSelected,
                                        'dp-day-pop-disabled': day.isDisabled && !day.isSelected,
                                    }">
                                    {{ day.day }}
                                    <span v-if="day.isAvailable && !day.isPast && !day.isSelected && !disabled" class="dp-pop-dot"></span>
                                </button>
                                <span v-else class="w-9 h-9"></span>
                            </div>
                            <!-- No doctor overlay -->
                            <div v-if="!doctorId" class="absolute inset-0 bg-white/90 backdrop-blur-[2px] rounded-xl flex flex-col items-center justify-center gap-2">
                                <div class="dp-ndr-icon w-10 h-10 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </div>
                                <p class="text-[11px] text-gray-400 font-medium">Select a doctor first</p>
                            </div>
                        </div>
                        <!-- Footer -->
                        <div v-if="doctorId" class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full dp-pop-legend-avail"></span>
                                        <span class="text-[10px] text-gray-400">Available</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-gray-200"></span>
                                        <span class="text-[10px] text-gray-400">Unavailable</span>
                                    </div>
                                </div>
                                <span v-if="availableCount > 0" class="dp-pop-badge">{{ availableCount }} days</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>

    <!-- ==================== INLINE MODE ==================== -->
    <div v-else class="dp-calendar dp-inline-wrap">
        <!-- Header -->
        <div class="dp-header">
            <button
                type="button"
                @click="prevMonth"
                :disabled="!canGoPrev"
                class="dp-nav-btn"
                :class="canGoPrev ? '' : 'dp-nav-disabled'"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <div class="dp-month-label">
                <span class="dp-month-name">{{ monthName }}</span>
                <span class="dp-month-year">{{ monthYear }}</span>
            </div>

            <button
                type="button"
                @click="nextMonth"
                class="dp-nav-btn"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Weekday headers -->
        <div class="dp-weekdays">
            <div
                v-for="(wd, wdIdx) in weekDays"
                :key="wd"
                class="dp-wd"
                :class="{ 'dp-wd-weekend': wdIdx === 0 || wdIdx === 6 }"
            >
                {{ wd }}
            </div>
        </div>

        <!-- Separator -->
        <div class="dp-divider"></div>

        <!-- Day Grid -->
        <div class="dp-grid relative">
            <div
                v-for="(day, idx) in calendarDays"
                :key="idx"
                class="dp-cell"
            >
                <button
                    v-if="day.date"
                    type="button"
                    @click="selectDay(day)"
                    :disabled="day.isDisabled"
                    class="dp-day"
                    :class="{
                        'dp-day-selected': day.isSelected,
                        'dp-day-available': day.isAvailable && !day.isPast && !day.isSelected && !disabled,
                        'dp-day-today': day.isToday && !day.isSelected,
                        'dp-day-past': day.isPast && !day.isSelected,
                        'dp-day-off': day.isDisabled && !day.isPast && !day.isSelected,
                    }"
                    :title="day.isAvailable && !day.isPast ? day.dayName : ''"
                >
                    {{ day.day }}
                </button>
                <div v-else class="dp-day-empty"></div>
            </div>

            <!-- No doctor overlay -->
            <div v-if="!doctorId" class="dp-overlay">
                <div class="dp-overlay-content">
                    <div class="dp-overlay-icon">
                        <svg fill="none" viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M5.5 19.5c0-3.59 2.91-6.5 6.5-6.5s6.5 2.91 6.5 6.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <p class="dp-overlay-text">Choose Your Doctor</p>
                    <p class="dp-overlay-sub">Available dates will appear</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div v-if="doctorId" class="dp-footer">
            <!-- Selected date display -->
            <Transition
                enter-active-class="transition ease-out duration-250"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="selectedDateInfo" class="dp-selected">
                    <svg class="dp-selected-check" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="dp-selected-text">{{ selectedDateInfo.dayName }}, {{ selectedDateInfo.monthShort }} {{ selectedDateInfo.dayNum }}</span>
                </div>
            </Transition>

            <div v-if="availableCount > 0 && !selectedDateInfo" class="dp-hint">
                <span class="dp-hint-dot"></span>
                {{ availableCount }} available days
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ================================================================== */
/*  DESIGN TOKENS                                                      */
/* ================================================================== */
.dp-calendar {
    --dp-accent: v-bind(accentColor);
    --dp-accent-5: color-mix(in srgb, var(--dp-accent) 5%, transparent);
    --dp-accent-8: color-mix(in srgb, var(--dp-accent) 8%, transparent);
    --dp-accent-10: color-mix(in srgb, var(--dp-accent) 10%, transparent);
    --dp-accent-15: color-mix(in srgb, var(--dp-accent) 15%, transparent);
    --dp-accent-20: color-mix(in srgb, var(--dp-accent) 20%, transparent);
    --dp-accent-25: color-mix(in srgb, var(--dp-accent) 25%, transparent);
    --dp-accent-30: color-mix(in srgb, var(--dp-accent) 30%, transparent);
    --dp-accent-40: color-mix(in srgb, var(--dp-accent) 40%, transparent);
    --dp-accent-60: color-mix(in srgb, var(--dp-accent) 60%, transparent);
    --dp-accent-80: color-mix(in srgb, var(--dp-accent) 80%, transparent);
    --dp-accent-dark: color-mix(in srgb, var(--dp-accent) 85%, #000);
}

/* ================================================================== */
/*  INLINE WRAPPER                                                     */
/* ================================================================== */
.dp-inline-wrap {
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #E8E5DF;
    width: 100%;
}

/* ================================================================== */
/*  HEADER                                                             */
/* ================================================================== */
.dp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 14px 10px;
}

.dp-month-label {
    display: flex;
    align-items: baseline;
    gap: 6px;
    user-select: none;
}

.dp-month-name {
    font-size: 15px;
    font-weight: 700;
    color: #2D2D2D;
    letter-spacing: -0.01em;
}

.dp-month-year {
    font-size: 13px;
    font-weight: 400;
    color: #A09A90;
}

/* ================================================================== */
/*  NAV BUTTONS                                                        */
/* ================================================================== */
.dp-nav-btn {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #8C8780;
    background: transparent;
    border: 1px solid #E8E5DF;
    transition: all 0.2s ease;
    cursor: pointer;
}

.dp-nav-btn:hover:not(:disabled) {
    background: var(--dp-accent-10);
    border-color: var(--dp-accent-25);
    color: var(--dp-accent-dark);
}

.dp-nav-btn:active:not(:disabled) {
    transform: scale(0.93);
}

.dp-nav-disabled {
    opacity: 0.25;
    cursor: not-allowed !important;
    pointer-events: none;
}

/* ================================================================== */
/*  WEEKDAY HEADERS                                                    */
/* ================================================================== */
.dp-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    padding: 0 12px;
}

.dp-wd {
    text-align: center;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #B5B0A8;
    padding: 0 0 6px;
    user-select: none;
}

.dp-wd-weekend {
    color: var(--dp-accent-60);
}

/* ================================================================== */
/*  DIVIDER                                                            */
/* ================================================================== */
.dp-divider {
    height: 1px;
    margin: 0 14px 8px;
    background: #F0EDE8;
}

/* ================================================================== */
/*  DAY GRID                                                           */
/* ================================================================== */
.dp-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    padding: 0 10px 10px;
}

.dp-cell {
    display: flex;
    align-items: center;
    justify-content: center;
}

.dp-day-empty {
    width: 36px;
    height: 36px;
}

/* ================================================================== */
/*  DAY BUTTON                                                         */
/* ================================================================== */
.dp-day {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: default;
    transition: all 0.2s ease;
    border: 1.5px solid transparent;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #D0CBC4;
    position: relative;
    padding: 0;
}

/* Past days */
.dp-day-past {
    color: #E4E0DB;
}

/* Off / not available */
.dp-day-off {
    color: #D0CBC4;
}

/* Available days */
.dp-day-available {
    cursor: pointer;
    color: #3A3A3A;
    font-weight: 600;
    background: var(--dp-accent-5);
    border-color: var(--dp-accent-15);
}

.dp-day-available:hover {
    background: var(--dp-accent-15);
    border-color: var(--dp-accent-40);
    transform: scale(1.08);
    box-shadow: 0 2px 8px -2px var(--dp-accent-20);
}

.dp-day-available:active {
    transform: scale(0.96);
}

/* Today */
.dp-day-today {
    font-weight: 700;
    color: var(--dp-accent-dark);
    background: var(--dp-accent-8);
    border-color: var(--dp-accent-30);
}

.dp-day-today::after {
    content: '';
    position: absolute;
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--dp-accent);
}

/* Selected day */
.dp-day-selected {
    cursor: pointer;
    background: var(--dp-accent);
    border-color: var(--dp-accent);
    color: #fff;
    font-weight: 700;
    transform: scale(1.06);
    box-shadow: 0 3px 12px -3px var(--dp-accent-40);
}

.dp-day-selected:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 14px -3px var(--dp-accent-60);
}

/* ================================================================== */
/*  NO-DOCTOR OVERLAY                                                  */
/* ================================================================== */
.dp-overlay {
    position: absolute;
    inset: -4px;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(3px);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.dp-overlay-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    text-align: center;
}

.dp-overlay-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--dp-accent-8);
    color: var(--dp-accent);
    border: 1.5px dashed var(--dp-accent-25);
}

.dp-overlay-icon svg {
    width: 20px;
    height: 20px;
}

.dp-overlay-text {
    font-size: 12px;
    font-weight: 600;
    color: #4A4A4A;
    line-height: 1;
}

.dp-overlay-sub {
    font-size: 10px;
    color: #A09A90;
    margin-top: -2px;
}

/* ================================================================== */
/*  FOOTER                                                             */
/* ================================================================== */
.dp-footer {
    padding: 0 14px 12px;
}

.dp-selected {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 10px;
    background: var(--dp-accent-8);
    border: 1px solid var(--dp-accent-15);
}

.dp-selected-check {
    width: 14px;
    height: 14px;
    color: var(--dp-accent);
    flex-shrink: 0;
}

.dp-selected-text {
    font-size: 12.5px;
    font-weight: 600;
    color: #3A3A3A;
    letter-spacing: -0.01em;
}

.dp-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 0 0;
    font-size: 11px;
    font-weight: 500;
    color: #A09A90;
}

.dp-hint-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--dp-accent);
    opacity: 0.5;
}

/* ================================================================== */
/*  POPOVER-SPECIFIC STYLES                                            */
/* ================================================================== */
.dp-popover-panel {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow:
        0 4px 6px -1px rgba(0,0,0,0.05),
        0 10px 25px -5px rgba(0,0,0,0.08),
        0 20px 50px -12px rgba(0,0,0,0.06);
}

.dp-pop-header {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
}

.dp-pop-nav {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    transition: all 0.2s ease;
    cursor: pointer;
}

.dp-pop-nav:hover:not(:disabled) {
    background: var(--dp-accent-10);
    color: var(--dp-accent);
}

.dp-wd-accent {
    color: var(--dp-accent);
    opacity: 0.7;
}

.dp-sep {
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--dp-accent-15), transparent);
}

/* Popover day */
.dp-day-pop {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    position: relative;
    transition: all 0.2s ease;
    cursor: default;
}

.dp-day-pop-disabled {
    color: #d1d5db;
    cursor: not-allowed;
}

.dp-day-pop-available {
    color: var(--dp-accent-dark);
    background: var(--dp-accent-8);
    cursor: pointer;
}

.dp-day-pop-available:hover {
    background: var(--dp-accent-20);
    transform: scale(1.1);
}

.dp-day-pop-today:not(.dp-day-pop-selected) {
    box-shadow: inset 0 0 0 2px var(--dp-accent-30);
    color: var(--dp-accent-dark);
}

.dp-day-pop-selected {
    background: linear-gradient(145deg, var(--dp-accent), var(--dp-accent-dark));
    color: #fff;
    font-weight: 700;
    transform: scale(1.08);
    box-shadow: 0 3px 10px -2px var(--dp-accent-40);
    cursor: pointer;
}

.dp-pop-dot {
    position: absolute;
    bottom: 3px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--dp-accent);
    opacity: 0.5;
}

.dp-ndr-icon {
    background: var(--dp-accent-10);
    color: var(--dp-accent);
    opacity: 0.6;
}

.dp-pop-legend-avail {
    background: var(--dp-accent-15);
    border: 1.5px solid var(--dp-accent-40);
}

.dp-pop-badge {
    font-size: 9px;
    font-weight: 700;
    background: var(--dp-accent-10);
    color: var(--dp-accent);
    padding: 2px 8px;
    border-radius: 20px;
}

/* Trigger styles */
.dp-trigger-icon {
    background-color: var(--dp-accent-10);
    color: var(--dp-accent);
}

.dp-trigger:hover:not(:disabled) .dp-trigger-icon {
    background-color: var(--dp-accent-15);
}

.dp-trigger-active {
    border-color: var(--dp-accent) !important;
    box-shadow: 0 0 0 3px var(--dp-accent-10), 0 1px 2px rgba(0,0,0,0.05) !important;
}

.dp-trigger-active .dp-trigger-icon {
    background-color: var(--dp-accent-25);
}

/* ================================================================== */
/*  ANIMATIONS                                                         */
/* ================================================================== */
@keyframes dp-pulse {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 0.9; }
}

.dp-day-today::after {
    animation: dp-pulse 2.5s ease-in-out infinite;
}

.dp-day-pop-today .dp-pop-dot {
    animation: dp-pulse 2s ease-in-out infinite;
}
</style>
