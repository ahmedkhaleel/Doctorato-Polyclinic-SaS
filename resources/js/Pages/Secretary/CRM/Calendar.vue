<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    followUps: Array,
    monthStats: Object,
    currentMonth: Number,
    currentYear: Number,
    activeLeads: Array,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

/* ── Calendar Data ─────────────────────────────────────── */
const monthNames = {
    en: ['January','February','March','April','May','June','July','August','September','October','November','December'],
    ar: ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
};

const dayNames = {
    en: ['Sat','Sun','Mon','Tue','Wed','Thu','Fri'],
    ar: ['سبت','أحد','إثنين','ثلاثاء','أربعاء','خميس','جمعة'],
};

const currentMonthName = computed(() => {
    const lang = isRtl.value ? 'ar' : 'en';
    return monthNames[lang][props.currentMonth - 1];
});

/* ── Build calendar grid ─────────────────────────────── */
const calendarDays = computed(() => {
    const year = props.currentYear;
    const month = props.currentMonth - 1; // JS months are 0-indexed
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Saturday = 6, but we want Saturday as first column
    // getDay(): 0=Sun, 1=Mon, ..., 5=Fri, 6=Sat
    const startOffset = (firstDay.getDay() + 1) % 7; // Convert so Sat=0

    const days = [];

    // Previous month's trailing days
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startOffset - 1; i >= 0; i--) {
        const d = prevMonthLastDay - i;
        const prevMonth = month === 0 ? 12 : month;
        const prevYear = month === 0 ? year - 1 : year;
        const dateStr = `${prevYear}-${String(prevMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({ day: d, date: dateStr, isCurrentMonth: false, isToday: false });
    }

    // Current month days
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    for (let d = 1; d <= lastDay.getDate(); d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({ day: d, date: dateStr, isCurrentMonth: true, isToday: dateStr === todayStr });
    }

    // Next month's leading days to fill the grid
    const remaining = 42 - days.length; // 6 rows x 7 columns
    for (let d = 1; d <= remaining; d++) {
        const nextMonth = month + 2 > 12 ? 1 : month + 2;
        const nextYear = month + 2 > 12 ? year + 1 : year;
        const dateStr = `${nextYear}-${String(nextMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({ day: d, date: dateStr, isCurrentMonth: false, isToday: false });
    }

    return days;
});

/* ── Follow-ups grouped by date ──────────────────────── */
const followUpsByDate = computed(() => {
    const map = {};
    (props.followUps || []).forEach(fu => {
        if (!map[fu.scheduled_date]) map[fu.scheduled_date] = [];
        map[fu.scheduled_date].push(fu);
    });
    return map;
});

function getFollowUpsForDay(dateStr) {
    return followUpsByDate.value[dateStr] || [];
}

/* ── View mode (month / week) — persisted ────────────── */
const savedViewMode = (() => { try { return localStorage.getItem('crm_calendar_viewMode'); } catch { return null; } })();
const viewMode = ref(savedViewMode || 'month'); // 'month' | 'week'
const currentWeekStart = ref(null);

function initWeek() {
    const today = new Date();
    // Find the Saturday of the current week
    const day = today.getDay(); // 0=Sun...6=Sat
    const diff = (day + 1) % 7; // days since Saturday
    const sat = new Date(today);
    sat.setDate(sat.getDate() - diff);
    currentWeekStart.value = sat;
}
initWeek();

watch(viewMode, (val) => { try { localStorage.setItem('crm_calendar_viewMode', val); } catch {} });

const weekDays = computed(() => {
    if (!currentWeekStart.value) return [];
    const days = [];
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    for (let i = 0; i < 7; i++) {
        const d = new Date(currentWeekStart.value);
        d.setDate(d.getDate() + i);
        const dateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        days.push({
            day: d.getDate(),
            date: dateStr,
            isToday: dateStr === todayStr,
            dayName: d.toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US', { weekday: 'short' }),
            monthName: d.toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US', { month: 'short' }),
        });
    }
    return days;
});

function prevWeek() {
    const d = new Date(currentWeekStart.value);
    d.setDate(d.getDate() - 7);
    currentWeekStart.value = d;
}

function nextWeek() {
    const d = new Date(currentWeekStart.value);
    d.setDate(d.getDate() + 7);
    currentWeekStart.value = d;
}

function goTodayWeek() {
    initWeek();
}

const weekRangeLabel = computed(() => {
    if (!weekDays.value.length) return '';
    const first = weekDays.value[0];
    const last = weekDays.value[6];
    return `${first.day} ${first.monthName} - ${last.day} ${last.monthName}`;
});

/* ── Selected day detail ─────────────────────────────── */
const selectedDate = ref(null);
const showDetail = ref(false);

function selectDay(dateStr) {
    selectedDate.value = dateStr;
    showDetail.value = true;
}

function closeDetail() {
    showDetail.value = false;
    selectedDate.value = null;
}

const selectedFollowUps = computed(() => {
    if (!selectedDate.value) return [];
    return getFollowUpsForDay(selectedDate.value);
});

const selectedDateFormatted = computed(() => {
    if (!selectedDate.value) return '';
    const d = new Date(selectedDate.value + 'T00:00:00');
    const dayNum = d.getDate();
    const monthIdx = d.getMonth();
    const lang = isRtl.value ? 'ar' : 'en';
    return `${dayNum} ${monthNames[lang][monthIdx]} ${d.getFullYear()}`;
});

/* ── Today's follow-up count ────────────────────────── */
const todayFollowUpCount = computed(() => {
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    return (followUpsByDate.value[todayStr] || []).length;
});

/* ── Navigation ──────────────────────────────────────── */
function goToMonth(month, year) {
    router.get('/secretary/crm/calendar', { month, year }, { preserveState: true, preserveScroll: true });
}

function prevMonth() {
    let m = props.currentMonth - 1;
    let y = props.currentYear;
    if (m < 1) { m = 12; y--; }
    goToMonth(m, y);
}

function nextMonth() {
    let m = props.currentMonth + 1;
    let y = props.currentYear;
    if (m > 12) { m = 1; y++; }
    goToMonth(m, y);
}

function goToday() {
    const now = new Date();
    goToMonth(now.getMonth() + 1, now.getFullYear());
}

/* ── Filter ──────────────────────────────────────────── */
const activeFilter = ref('all');
const filterOptions = [
    { key: 'all', en: 'All', ar: 'الكل' },
    { key: 'pending', en: 'Pending', ar: 'معلق' },
    { key: 'completed', en: 'Completed', ar: 'مكتمل' },
    { key: 'missed', en: 'Missed', ar: 'فائت' },
    { key: 'overdue', en: 'Overdue', ar: 'متأخر' },
];

function getFilteredFollowUpsForDay(dateStr) {
    const fups = getFollowUpsForDay(dateStr);
    if (activeFilter.value === 'all') return fups;
    if (activeFilter.value === 'overdue') return fups.filter(f => f.is_overdue);
    return fups.filter(f => f.status === activeFilter.value);
}

/* ── Visual helpers ──────────────────────────────────── */
const typeConfig = {
    call:     { en: 'Call',     ar: 'مكالمة',   color: 'bg-blue-500',   lightBg: 'bg-blue-50 text-blue-700 border-blue-200' },
    whatsapp: { en: 'WhatsApp', ar: 'واتساب',   color: 'bg-green-500',  lightBg: 'bg-green-50 text-green-700 border-green-200' },
    email:    { en: 'Email',    ar: 'بريد',      color: 'bg-purple-500', lightBg: 'bg-purple-50 text-purple-700 border-purple-200' },
    sms:      { en: 'SMS',      ar: 'رسالة',     color: 'bg-cyan-500',   lightBg: 'bg-cyan-50 text-cyan-700 border-cyan-200' },
    meeting:  { en: 'Meeting',  ar: 'اجتماع',    color: 'bg-amber-500',  lightBg: 'bg-amber-50 text-amber-700 border-amber-200' },
    other:    { en: 'Other',    ar: 'أخرى',      color: 'bg-gray-500',   lightBg: 'bg-gray-50 text-gray-700 border-gray-200' },
};

const statusConfig = {
    pending:     { en: 'Pending',     ar: 'معلق',      color: 'text-amber-600 bg-amber-50 border-amber-200' },
    completed:   { en: 'Completed',   ar: 'مكتمل',     color: 'text-green-600 bg-green-50 border-green-200' },
    missed:      { en: 'Missed',      ar: 'فائت',       color: 'text-red-600 bg-red-50 border-red-200' },
    rescheduled: { en: 'Rescheduled', ar: 'مُعاد جدولته', color: 'text-blue-600 bg-blue-50 border-blue-200' },
};

const priorityConfig = {
    1: { en: 'Hot',  ar: 'ساخن',  color: 'text-red-500' },
    2: { en: 'Warm', ar: 'دافئ',  color: 'text-amber-500' },
    3: { en: 'Cold', ar: 'بارد',  color: 'text-blue-400' },
};

function getTypeDots(fups) {
    const types = new Set(fups.map(f => f.type));
    return [...types].slice(0, 4);
}

/* ── Quick Actions on follow-ups ────────────────────── */
const completingFuId = ref(null);
const completionResult = ref('');

function completeFollowUp(fuId) {
    router.post(`/secretary/crm/follow-ups/${fuId}/complete`, { result: '' }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function startDetailComplete(fuId) {
    completingFuId.value = fuId;
    completionResult.value = '';
}

function submitDetailComplete(fuId) {
    router.post(`/secretary/crm/follow-ups/${fuId}/complete`, {
        result: completionResult.value.trim(),
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => { completingFuId.value = null; completionResult.value = ''; },
    });
}

function cancelDetailComplete() {
    completingFuId.value = null;
    completionResult.value = '';
}

function missFollowUp(fuId) {
    router.post(`/secretary/crm/follow-ups/${fuId}/miss`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

/* ── Snooze from calendar ───────────────────────────── */
/* ── Quick-add follow-up ─────────────────────────────── */
const showQuickAdd = ref(false);
const quickAddDate = ref('');
const quickAddType = ref('call');
const quickAddLeadId = ref('');
const quickAddNotes = ref('');
const quickAddTime = ref('09:00');
const quickAddSaving = ref(false);

const quickAddLeadSearch = ref('');
const filteredQuickAddLeads = computed(() => {
    if (!props.activeLeads?.length) return [];
    if (!quickAddLeadSearch.value.trim()) return props.activeLeads;
    const q = quickAddLeadSearch.value.trim().toLowerCase();
    return props.activeLeads.filter(l =>
        (l.full_name || '').toLowerCase().includes(q) ||
        (l.phone || '').includes(q)
    );
});

function openQuickAdd(dateStr) {
    quickAddDate.value = dateStr;
    quickAddType.value = 'call';
    quickAddLeadId.value = '';
    quickAddNotes.value = '';
    quickAddTime.value = '09:00';
    quickAddLeadSearch.value = '';
    showQuickAdd.value = true;
}

function submitQuickAdd() {
    if (!quickAddLeadId.value || !quickAddDate.value) return;
    quickAddSaving.value = true;
    router.post(`/secretary/crm/leads/${quickAddLeadId.value}/follow-up`, {
        type: quickAddType.value,
        scheduled_at: quickAddDate.value + 'T' + quickAddTime.value,
        notes: quickAddNotes.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            quickAddSaving.value = false;
            showQuickAdd.value = false;
        },
    });
}

/* ── Calendar summary stats ─────────────────────────── */
const totalFollowUps = computed(() => (props.followUps || []).length);
const pendingCount = computed(() => (props.followUps || []).filter(f => f.status === 'pending').length);
const overdueCount = computed(() => (props.followUps || []).filter(f => f.is_overdue).length);
const completedCount = computed(() => (props.followUps || []).filter(f => f.status === 'completed').length);

function snoozeFollowUp(fuId, key) {
    let scheduledAt;
    const now = new Date();
    if (key === '1h') {
        scheduledAt = new Date(now.getTime() + 60 * 60 * 1000);
    } else if (key === 'tomorrow') {
        scheduledAt = new Date(now);
        scheduledAt.setDate(scheduledAt.getDate() + 1);
        scheduledAt.setHours(9, 0, 0, 0);
    } else if (key === '1w') {
        scheduledAt = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
        scheduledAt.setHours(9, 0, 0, 0);
    }
    const isoStr = scheduledAt.getFullYear() + '-' +
        String(scheduledAt.getMonth() + 1).padStart(2, '0') + '-' +
        String(scheduledAt.getDate()).padStart(2, '0') + 'T' +
        String(scheduledAt.getHours()).padStart(2, '0') + ':' +
        String(scheduledAt.getMinutes()).padStart(2, '0');
    router.post(`/secretary/crm/follow-ups/${fuId}/reschedule`, {
        scheduled_at: isoStr,
        notes: isRtl.value ? 'تم التأجيل' : 'Snoozed',
    }, { preserveScroll: true, preserveState: true });
}

/* ── Keyboard shortcuts ──────────────────────────────── */
function handleCalendarKey(e) {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
    if (showQuickAdd.value || showDetail.value) return;

    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        if (viewMode.value === 'month') { isRtl.value ? nextMonth() : prevMonth(); }
        else { isRtl.value ? nextWeek() : prevWeek(); }
    }
    if (e.key === 'ArrowRight') {
        e.preventDefault();
        if (viewMode.value === 'month') { isRtl.value ? prevMonth() : nextMonth(); }
        else { isRtl.value ? prevWeek() : nextWeek(); }
    }
    if (e.key === 't' || e.key === 'T' || e.key === '\u062A') {
        e.preventDefault();
        if (viewMode.value === 'month') goToday(); else goTodayWeek();
    }
    if (e.key === 'w' || e.key === 'W' || e.key === '\u0648') {
        e.preventDefault();
        viewMode.value = 'week';
    }
    if (e.key === 'm' || e.key === 'M' || e.key === '\u0645') {
        e.preventDefault();
        viewMode.value = 'month';
    }
    if (e.key === 'n' || e.key === 'N' || e.key === '\u0646') {
        e.preventDefault();
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        openQuickAdd(todayStr);
    }
}

onMounted(() => { document.addEventListener('keydown', handleCalendarKey); });
onBeforeUnmount(() => { document.removeEventListener('keydown', handleCalendarKey); });
</script>

<template>
<SecretaryLayout :title="isRtl ? 'تقويم المتابعات' : 'Follow-ups Calendar'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/20 to-slate-50 p-4 md:p-6" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- Header -->
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 p-6 md:p-8 mb-6 shadow-xl transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white rounded-full"></div>
        </div>
        <div class="relative flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ isRtl ? 'تقويم المتابعات' : 'Follow-ups Calendar' }}</h1>
                    <p class="text-teal-100 mt-1 text-sm">{{ isRtl ? 'تتبع جميع المتابعات المجدولة' : 'Track all your scheduled follow-ups' }}</p>
                </div>
            </div>
            <!-- Quick stats -->
            <div class="flex gap-3 flex-wrap">
                <div v-if="todayFollowUpCount > 0" class="bg-white/25 backdrop-blur rounded-xl px-4 py-2 text-center ring-1 ring-white/30">
                    <div class="text-2xl font-bold text-white">{{ todayFollowUpCount }}</div>
                    <div class="text-xs text-white font-medium">{{ isRtl ? 'اليوم' : 'Today' }}</div>
                </div>
                <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-2 text-center">
                    <div class="text-2xl font-bold text-white">{{ monthStats?.total || 0 }}</div>
                    <div class="text-xs text-teal-100">{{ isRtl ? 'الإجمالي' : 'Total' }}</div>
                </div>
                <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-2 text-center">
                    <div class="text-2xl font-bold text-white">{{ monthStats?.pending || 0 }}</div>
                    <div class="text-xs text-teal-100">{{ isRtl ? 'معلق' : 'Pending' }}</div>
                </div>
                <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-2 text-center">
                    <div class="text-2xl font-bold text-white">{{ monthStats?.completed || 0 }}</div>
                    <div class="text-xs text-teal-100">{{ isRtl ? 'مكتمل' : 'Done' }}</div>
                </div>
                <div v-if="monthStats?.overdue > 0" class="bg-red-500/30 backdrop-blur rounded-xl px-4 py-2 text-center">
                    <div class="text-2xl font-bold text-white">{{ monthStats.overdue }}</div>
                    <div class="text-xs text-red-100">{{ isRtl ? 'متأخر' : 'Overdue' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Controls -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4 transition-all duration-700 delay-100', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <!-- Month navigation -->
            <div class="flex items-center gap-3">
                <button @click="prevMonth" class="w-9 h-9 rounded-lg bg-gray-50 hover:bg-teal-50 border border-gray-200 hover:border-teal-300 flex items-center justify-center transition-all duration-200 group">
                    <svg :class="['w-4 h-4 text-gray-500 group-hover:text-teal-600 transition-colors', isRtl ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <h2 class="text-lg font-semibold text-gray-800 min-w-[180px] text-center">
                    {{ currentMonthName }} {{ currentYear }}
                </h2>
                <button @click="nextMonth" class="w-9 h-9 rounded-lg bg-gray-50 hover:bg-teal-50 border border-gray-200 hover:border-teal-300 flex items-center justify-center transition-all duration-200 group">
                    <svg :class="['w-4 h-4 text-gray-500 group-hover:text-teal-600 transition-colors', isRtl ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button @click="goToday" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-100 transition-all duration-200">
                    {{ isRtl ? 'اليوم' : 'Today' }}
                </button>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <!-- View toggle -->
                <div class="flex items-center bg-gray-100 rounded-lg p-0.5">
                    <button @click="viewMode = 'month'"
                        :class="['px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200',
                            viewMode === 'month' ? 'bg-white text-teal-700 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        <svg class="w-3.5 h-3.5 inline-block me-1 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M10 3v18"/></svg>
                        {{ isRtl ? 'شهر' : 'Month' }}
                    </button>
                    <button @click="viewMode = 'week'"
                        :class="['px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200',
                            viewMode === 'week' ? 'bg-white text-teal-700 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                        <svg class="w-3.5 h-3.5 inline-block me-1 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        {{ isRtl ? 'اسبوع' : 'Week' }}
                    </button>
                </div>
                <!-- Keyboard hints (desktop) -->
                <div class="hidden md:flex items-center gap-1.5 text-[10px] text-gray-400">
                    <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 font-mono font-semibold bg-gray-100 border border-gray-200 rounded shadow-sm text-[9px]">T</kbd> {{ isRtl ? 'اليوم' : 'Today' }}</span>
                    <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 font-mono font-semibold bg-gray-100 border border-gray-200 rounded shadow-sm text-[9px]">N</kbd> {{ isRtl ? 'جديد' : 'New' }}</span>
                    <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 font-mono font-semibold bg-gray-100 border border-gray-200 rounded shadow-sm text-[9px]">&larr;&rarr;</kbd> {{ isRtl ? 'تنقل' : 'Nav' }}</span>
                </div>
                <!-- Filter pills -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button v-for="opt in filterOptions" :key="opt.key"
                        @click="activeFilter = opt.key"
                        :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 border',
                            activeFilter === opt.key
                                ? 'bg-teal-500 text-white border-teal-500 shadow-sm'
                                : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100']">
                        {{ isRtl ? opt.ar : opt.en }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Type legend + quick add -->
        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 flex-wrap gap-2">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'الأنواع:' : 'Types:' }}</span>
                <div v-for="(cfg, key) in typeConfig" :key="key" class="flex items-center gap-1">
                    <span :class="['w-2 h-2 rounded-full', cfg.color]"></span>
                    <span class="text-[11px] text-gray-500">{{ isRtl ? cfg.ar : cfg.en }}</span>
                </div>
            </div>
            <button @click="openQuickAdd(new Date().toISOString().split('T')[0])"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-100 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ isRtl ? 'متابعة جديدة' : 'New Follow-up' }}
            </button>
        </div>
    </div>

    <!-- ========== MONTH VIEW ========== -->
    <div v-if="viewMode === 'month'"
         :class="['bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-200', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">

        <!-- Day headers -->
        <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-100">
            <div v-for="(day, idx) in (isRtl ? dayNames.ar : dayNames.en)" :key="idx"
                 :class="['py-3 text-center text-xs font-semibold uppercase tracking-wider',
                    idx === 6 ? 'text-red-400' : 'text-gray-500']">
                {{ day }}
            </div>
        </div>

        <!-- Calendar cells -->
        <div class="grid grid-cols-7">
            <div v-for="(cell, idx) in calendarDays" :key="idx"
                 @click="selectDay(cell.date)"
                 :class="['relative min-h-[90px] md:min-h-[110px] border-b border-r border-gray-50 p-1.5 cursor-pointer transition-all duration-150 hover:bg-teal-50/40 group',
                    !cell.isCurrentMonth ? 'bg-gray-50/50' : '',
                    cell.isToday ? 'bg-teal-50/60 ring-1 ring-inset ring-teal-200' : '',
                    selectedDate === cell.date ? 'bg-teal-50 ring-2 ring-inset ring-teal-400' : '']">

                <!-- Day number -->
                <div class="flex items-center justify-between mb-1">
                    <span :class="['text-sm font-medium leading-none relative',
                        cell.isToday ? 'w-7 h-7 rounded-full bg-teal-500 text-white flex items-center justify-center text-xs font-bold' : '',
                        !cell.isCurrentMonth ? 'text-gray-300' : 'text-gray-700']">
                        <span v-if="cell.isToday" class="absolute inset-0 rounded-full bg-teal-400 animate-ping opacity-30"></span>
                        <span class="relative">{{ cell.day }}</span>
                    </span>
                    <div class="flex items-center gap-1">
                        <button v-if="cell.isCurrentMonth" @click.stop="openQuickAdd(cell.date)"
                            class="w-5 h-5 rounded-full bg-teal-100 text-teal-600 hover:bg-teal-500 hover:text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-150"
                            :title="isRtl ? 'إضافة متابعة' : 'Add follow-up'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </button>
                        <span v-if="getFilteredFollowUpsForDay(cell.date).length > 0" class="text-[10px] font-bold text-teal-600 bg-teal-100 rounded-full w-5 h-5 flex items-center justify-center">
                            {{ getFilteredFollowUpsForDay(cell.date).length }}
                        </span>
                    </div>
                </div>

                <!-- Follow-up dots / mini cards -->
                <div class="space-y-0.5 overflow-hidden" style="max-height: 85px;">
                    <div v-for="fu in getFilteredFollowUpsForDay(cell.date).slice(0, 3)" :key="fu.id"
                         class="group/card relative"
                         @click.stop>
                        <div :class="['flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium border truncate transition-all duration-150',
                            fu.is_overdue ? 'bg-red-50 text-red-600 border-red-200 animate-pulse' :
                            fu.status === 'completed' ? 'bg-green-50 text-green-600 border-green-200' :
                            fu.status === 'missed' ? 'bg-red-50 text-red-500 border-red-200' :
                            typeConfig[fu.type]?.lightBg || 'bg-gray-50 text-gray-600 border-gray-200']">
                            <span :class="['w-1.5 h-1.5 rounded-full flex-shrink-0', typeConfig[fu.type]?.color || 'bg-gray-400']"></span>
                            <span class="truncate">{{ fu.scheduled_time }} {{ fu.lead_name }}</span>
                        </div>
                        <!-- Quick action buttons on hover -->
                        <div v-if="fu.status === 'pending'"
                             class="absolute -top-1 z-20 flex items-center gap-0.5 opacity-0 group-hover/card:opacity-100 transition-all duration-150"
                             :class="isRtl ? 'left-0' : 'right-0'">
                            <button @click.stop="completeFollowUp(fu.id)"
                                    class="w-4 h-4 rounded-full bg-green-500 text-white flex items-center justify-center hover:bg-green-600 shadow-sm"
                                    :title="isRtl ? '\u0625\u0643\u0645\u0627\u0644' : 'Complete'">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <button @click.stop="missFollowUp(fu.id)"
                                    class="w-4 h-4 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 shadow-sm"
                                    :title="isRtl ? '\u0641\u0627\u0626\u062A' : 'Miss'">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div v-if="getFilteredFollowUpsForDay(cell.date).length > 3"
                         class="text-[9px] text-gray-400 font-medium px-1.5">
                        +{{ getFilteredFollowUpsForDay(cell.date).length - 3 }} {{ isRtl ? 'أخرى' : 'more' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== WEEK VIEW ========== -->
    <div v-if="viewMode === 'week'"
         :class="['bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']">

        <!-- Week navigation -->
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button @click="prevWeek" class="w-8 h-8 rounded-lg bg-white hover:bg-teal-50 border border-gray-200 hover:border-teal-300 flex items-center justify-center transition-all">
                    <svg :class="['w-4 h-4 text-gray-500', isRtl ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <span class="text-sm font-semibold text-gray-800 min-w-[180px] text-center">{{ weekRangeLabel }}</span>
                <button @click="nextWeek" class="w-8 h-8 rounded-lg bg-white hover:bg-teal-50 border border-gray-200 hover:border-teal-300 flex items-center justify-center transition-all">
                    <svg :class="['w-4 h-4 text-gray-500', isRtl ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button @click="goTodayWeek" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-100 transition-all">
                    {{ isRtl ? 'اليوم' : 'Today' }}
                </button>
            </div>
        </div>

        <!-- Week columns -->
        <div class="grid grid-cols-7 divide-x divide-gray-100">
            <div v-for="(wd, idx) in weekDays" :key="wd.date"
                 @click="selectDay(wd.date)"
                 :class="['group min-h-[350px] cursor-pointer transition-all duration-150 hover:bg-teal-50/30',
                    wd.isToday ? 'bg-teal-50/40' : '',
                    selectedDate === wd.date ? 'bg-teal-50/60' : '']">

                <!-- Day header -->
                <div class="px-2 py-3 text-center border-b border-gray-100 sticky top-0 bg-white/95 backdrop-blur-sm">
                    <div class="text-[11px] font-medium text-gray-400 uppercase">{{ wd.dayName }}</div>
                    <div :class="['text-lg font-bold mt-0.5',
                        wd.isToday ? 'w-8 h-8 rounded-full bg-teal-500 text-white flex items-center justify-center mx-auto text-sm' : 'text-gray-800']">
                        {{ wd.day }}
                    </div>
                    <div v-if="getFilteredFollowUpsForDay(wd.date).length > 0"
                         class="mt-1 text-[10px] font-bold text-teal-600">
                        {{ getFilteredFollowUpsForDay(wd.date).length }} {{ isRtl ? 'متابعة' : 'items' }}
                    </div>
                </div>

                <!-- Follow-ups for the day -->
                <div class="p-1.5 space-y-1.5">
                    <div v-for="fu in getFilteredFollowUpsForDay(wd.date)" :key="fu.id"
                         @click.stop="selectDay(wd.date)"
                         :class="['p-2 rounded-lg border text-[11px] transition-all duration-200 hover:shadow-sm',
                            fu.is_overdue ? 'bg-red-50 border-red-200' :
                            fu.status === 'completed' ? 'bg-green-50 border-green-200' :
                            fu.status === 'missed' ? 'bg-red-50/60 border-red-200' :
                            'bg-white border-gray-150 hover:border-teal-200']">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span :class="['w-2 h-2 rounded-full flex-shrink-0', typeConfig[fu.type]?.color || 'bg-gray-400']"></span>
                            <span class="font-semibold text-gray-700">{{ fu.scheduled_time }}</span>
                        </div>
                        <div class="font-medium text-gray-800 truncate">{{ fu.lead_name }}</div>
                        <div class="flex items-center gap-1 mt-1 text-[10px] text-gray-400">
                            <span>{{ isRtl ? typeConfig[fu.type]?.ar : typeConfig[fu.type]?.en }}</span>
                            <span v-if="fu.is_overdue" class="text-red-500 font-semibold">{{ isRtl ? '\u0645\u062A\u0623\u062E\u0631' : 'Overdue' }}</span>
                        </div>
                        <!-- Inline quick-complete button -->
                        <button v-if="fu.status === 'pending'"
                            @click.stop="completeFollowUp(fu.id)"
                            class="mt-1.5 w-full flex items-center justify-center gap-1 px-2 py-1 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-all duration-200 opacity-0 group-hover:opacity-100"
                            :title="isRtl ? '\u0625\u0643\u0645\u0627\u0644' : 'Complete'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ isRtl ? '\u0625\u0643\u0645\u0627\u0644' : 'Done' }}
                        </button>
                    </div>

                    <div v-if="getFilteredFollowUpsForDay(wd.date).length === 0"
                         class="text-center py-8 text-gray-300">
                        <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        <span class="text-[10px]">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Type Legend -->
    <div :class="['mt-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 transition-all duration-700 delay-300', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="flex items-center flex-wrap gap-4">
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الأنواع' : 'Types' }}</span>
            <div v-for="(cfg, key) in typeConfig" :key="key" class="flex items-center gap-1.5">
                <span :class="['w-2.5 h-2.5 rounded-full', cfg.color]"></span>
                <span class="text-xs text-gray-600">{{ isRtl ? cfg.ar : cfg.en }}</span>
            </div>
            <span class="mx-2 text-gray-200">|</span>
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</span>
            <div v-for="(cfg, key) in statusConfig" :key="key" class="flex items-center gap-1.5">
                <span :class="['w-2.5 h-2.5 rounded-sm border', cfg.color]"></span>
                <span class="text-xs text-gray-600">{{ isRtl ? cfg.ar : cfg.en }}</span>
            </div>
        </div>
    </div>

</div>

<!-- Day Detail Slide Panel -->
<Teleport to="body">
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="showDetail" class="fixed inset-0 z-50 flex" :dir="isRtl ? 'rtl' : 'ltr'">

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeDetail"></div>

            <!-- Panel -->
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                :enter-from-class="isRtl ? '-translate-x-full' : 'translate-x-full'"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-from-class="translate-x-0"
                :leave-to-class="isRtl ? '-translate-x-full' : 'translate-x-full'">
                <div v-if="showDetail"
                     :class="['relative w-full max-w-md bg-white shadow-2xl overflow-y-auto',
                        isRtl ? 'rounded-r-2xl' : 'rounded-l-2xl ms-auto']">

                    <!-- Panel Header -->
                    <div class="sticky top-0 bg-gradient-to-r from-teal-500 to-emerald-500 p-5 z-10">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-white">{{ selectedDateFormatted }}</h3>
                                <p class="text-teal-100 text-sm mt-0.5">
                                    {{ selectedFollowUps.length }} {{ isRtl ? 'متابعة' : 'follow-up(s)' }}
                                </p>
                            </div>
                            <button @click="closeDetail" class="w-8 h-8 rounded-lg bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Follow-ups list -->
                    <div class="p-4 space-y-3">
                        <div v-if="selectedFollowUps.length === 0" class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد متابعات في هذا اليوم' : 'No follow-ups on this day' }}</p>
                        </div>

                        <div v-for="fu in selectedFollowUps" :key="fu.id"
                             :class="['rounded-xl border p-4 transition-all duration-200 hover:shadow-md',
                                fu.is_overdue ? 'border-red-200 bg-red-50/50' :
                                fu.status === 'completed' ? 'border-green-200 bg-green-50/30' :
                                fu.status === 'missed' ? 'border-red-200 bg-red-50/30' :
                                'border-gray-150 bg-white']">

                            <!-- Top row: time + type + status -->
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', typeConfig[fu.type]?.color || 'bg-gray-500']">
                                        <!-- Type icons -->
                                        <svg v-if="fu.type === 'call'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <svg v-else-if="fu.type === 'whatsapp'" class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.944 11.944 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.387 0-4.594-.83-6.326-2.216l-.44-.362-3.108 1.042 1.042-3.108-.362-.44A9.956 9.956 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                                        <svg v-else-if="fu.type === 'email'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <svg v-else-if="fu.type === 'sms'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        <svg v-else-if="fu.type === 'meeting'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-sm font-semibold text-gray-800">{{ fu.scheduled_time }}</span>
                                        <span class="text-xs text-gray-400 mx-1">-</span>
                                        <span class="text-xs text-gray-500">{{ isRtl ? typeConfig[fu.type]?.ar : typeConfig[fu.type]?.en }}</span>
                                    </div>
                                </div>
                                <span :class="['text-[10px] font-medium px-2 py-0.5 rounded-full border',
                                    fu.is_overdue ? 'text-red-600 bg-red-50 border-red-200' :
                                    statusConfig[fu.status]?.color || 'text-gray-500 bg-gray-50 border-gray-200']">
                                    {{ fu.is_overdue ? (isRtl ? 'متأخر' : 'Overdue') : (isRtl ? statusConfig[fu.status]?.ar : statusConfig[fu.status]?.en) }}
                                </span>
                            </div>

                            <!-- Lead info -->
                            <Link :href="`/secretary/crm/leads/${fu.lead_id}`"
                                  class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50 hover:bg-teal-50 transition-colors group mb-2">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ fu.lead_name?.charAt(0)?.toUpperCase() || '?' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-800 group-hover:text-teal-700 truncate transition-colors">{{ fu.lead_name }}</div>
                                    <div class="text-xs text-gray-400 flex items-center gap-1.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        {{ fu.lead_phone }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <svg :class="['w-3.5 h-3.5', priorityConfig[fu.lead_priority]?.color || 'text-gray-400']" fill="currentColor" viewBox="0 0 24 24">
                                        <path v-if="fu.lead_priority === 1" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15v-4H7l5-7v4h4l-5 7z"/>
                                        <path v-else-if="fu.lead_priority === 2" d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 18a8 8 0 110-16 8 8 0 010 16zm-1-5h2v2h-2v-2zm0-8h2v6h-2V7z"/>
                                        <path v-else d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                    </svg>
                                    <svg class="w-4 h-4 text-gray-300 group-hover:text-teal-500 transition-colors" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </div>
                            </Link>

                            <!-- Notes -->
                            <div v-if="fu.notes" class="text-xs text-gray-500 leading-relaxed px-1">
                                <svg class="w-3 h-3 inline-block text-gray-400 me-1 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                {{ fu.notes }}
                            </div>

                            <!-- Result -->
                            <div v-if="fu.result" class="mt-2 text-xs text-green-600 bg-green-50 rounded-lg p-2 border border-green-100">
                                <svg class="w-3 h-3 inline-block me-1 -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                {{ fu.result }}
                            </div>

                            <!-- Quick Actions for pending -->
                            <div v-if="fu.status === 'pending'" class="mt-3 pt-3 border-t border-gray-100">
                                <!-- Completion result form -->
                                <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                                            leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <div v-if="completingFuId === fu.id" class="mb-2.5">
                                        <form @submit.prevent="submitDetailComplete(fu.id)" class="flex flex-col gap-2">
                                            <input v-model="completionResult" type="text" autofocus
                                                class="w-full rounded-lg border-emerald-300 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500 bg-emerald-50/50"
                                                :placeholder="isRtl ? '\u0646\u062A\u064A\u062C\u0629 \u0627\u0644\u0645\u062A\u0627\u0628\u0639\u0629 (\u0627\u062E\u062A\u064A\u0627\u0631\u064A)...' : 'Follow-up result (optional)...'" />
                                            <div class="flex items-center gap-2">
                                                <button type="submit"
                                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition-all duration-200">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    {{ isRtl ? '\u062A\u0623\u0643\u064A\u062F \u0627\u0644\u0625\u0643\u0645\u0627\u0644' : 'Confirm Complete' }}
                                                </button>
                                                <button type="button" @click="cancelDetailComplete"
                                                    class="px-3 py-2 rounded-lg text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                                                    {{ isRtl ? '\u0625\u0644\u063A\u0627\u0621' : 'Cancel' }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </Transition>

                                <div v-if="completingFuId !== fu.id" class="flex items-center gap-2">
                                    <button
                                        @click="startDetailComplete(fu.id)"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-all duration-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        {{ isRtl ? '\u0645\u0643\u062A\u0645\u0644' : 'Complete' }}
                                    </button>
                                    <button
                                        @click="missFollowUp(fu.id)"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 transition-all duration-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        {{ isRtl ? '\u0641\u0627\u0626\u062A' : 'Missed' }}
                                    </button>
                                    <!-- Snooze dropdown -->
                                    <div class="relative group/snooze">
                                        <button class="inline-flex items-center justify-center gap-1 px-3 py-2 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition-all duration-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <div class="absolute bottom-full mb-1 z-30 bg-white rounded-xl shadow-xl border border-gray-200 py-1 min-w-[130px] opacity-0 invisible group-hover/snooze:opacity-100 group-hover/snooze:visible transition-all duration-200"
                                             :class="isRtl ? 'right-0' : 'left-0'">
                                            <button @click="snoozeFollowUp(fu.id, '1h')" class="w-full text-start px-3 py-2 text-xs text-gray-700 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                                                {{ isRtl ? '\u0633\u0627\u0639\u0629 \u0648\u0627\u062D\u062F\u0629' : '1 Hour' }}
                                            </button>
                                            <button @click="snoozeFollowUp(fu.id, 'tomorrow')" class="w-full text-start px-3 py-2 text-xs text-gray-700 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                                                {{ isRtl ? '\u063A\u062F\u0627\u064B 9 \u0635' : 'Tomorrow 9 AM' }}
                                            </button>
                                            <button @click="snoozeFollowUp(fu.id, '1w')" class="w-full text-start px-3 py-2 text-xs text-gray-700 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                                                {{ isRtl ? '\u0623\u0633\u0628\u0648\u0639' : '1 Week' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</Teleport>

<!-- Quick Add Follow-up Modal -->
<Teleport to="body">
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showQuickAdd" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showQuickAdd = false"></div>
            <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="showQuickAdd" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">{{ isRtl ? 'إضافة متابعة سريعة' : 'Quick Add Follow-up' }}</h3>
                        <button @click="showQuickAdd = false" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center"><svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'العميل' : 'Lead' }} *</label>
                            <input v-if="activeLeads && activeLeads.length > 8"
                                v-model="quickAddLeadSearch"
                                type="text"
                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500 mb-2"
                                :placeholder="isRtl ? 'ابحث بالاسم أو الهاتف...' : 'Search by name or phone...'" />
                            <select v-model="quickAddLeadId" class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500" size="1">
                                <option value="">{{ isRtl ? '-- اختر عميل --' : '-- Select lead --' }}</option>
                                <option v-for="lead in filteredQuickAddLeads" :key="lead.id" :value="lead.id">{{ lead.full_name }} - {{ lead.phone }}</option>
                            </select>
                            <p v-if="quickAddLeadSearch && filteredQuickAddLeads.length === 0" class="text-xs text-gray-400 mt-1">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النوع' : 'Type' }}</label>
                                <select v-model="quickAddType" class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500">
                                    <option v-for="(cfg, key) in typeConfig" :key="key" :value="key">{{ isRtl ? cfg.ar : cfg.en }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الوقت' : 'Time' }}</label>
                                <input v-model="quickAddTime" type="time" class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500"/>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }}</label>
                            <input v-model="quickAddDate" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <input v-model="quickAddNotes" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500" :placeholder="isRtl ? 'اختياري...' : 'Optional...'"/>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button @click="submitQuickAdd" :disabled="!quickAddLeadId || quickAddSaving"
                                :class="['flex-1 py-2.5 rounded-xl text-sm font-semibold transition-all',
                                    !quickAddLeadId || quickAddSaving ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-teal-600 hover:bg-teal-700 text-white shadow-lg shadow-teal-200']">
                                <svg v-if="quickAddSaving" class="w-4 h-4 animate-spin inline me-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ quickAddSaving ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'إضافة متابعة' : 'Add Follow-up') }}
                            </button>
                            <button @click="showQuickAdd = false" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</Teleport>

</SecretaryLayout>
</template>
