<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    followUps: Array,
    monthStats: Object,
    assignees: Array,
    month: Number,
    year: Number,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const statusFilter = ref('all');
const selectedDay = ref(null);
const showPanel = ref(false);

const GOLD = '#C4A265';

const monthNames = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December',
];

// Saturday-first week headers
const weekDays = ['Sat','Sun','Mon','Tue','Wed','Thu','Fri'];

const filteredFollowUps = computed(() => {
    if (statusFilter.value === 'all') return props.followUps || [];
    if (statusFilter.value === 'overdue') return (props.followUps || []).filter(f => f.is_overdue);
    return (props.followUps || []).filter(f => f.status === statusFilter.value);
});

const followUpsByDate = computed(() => {
    const map = {};
    (filteredFollowUps.value || []).forEach(f => {
        const d = f.scheduled_date;
        if (!d) return;
        if (!map[d]) map[d] = [];
        map[d].push(f);
    });
    return map;
});

const calendarDays = computed(() => {
    const first = new Date(props.year, props.month - 1, 1);
    const daysInMonth = new Date(props.year, props.month, 0).getDate();
    // Saturday = 6. Get day of week for 1st, offset from Saturday
    let startDow = first.getDay(); // 0=Sun
    // Convert to Saturday-first: Sat=0, Sun=1, Mon=2 ... Fri=6
    let offset = (startDow + 1) % 7;
    const days = [];
    // Padding before
    for (let i = 0; i < offset; i++) days.push(null);
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${props.year}-${String(props.month).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const items = followUpsByDate.value[dateStr] || [];
        days.push({ day: d, date: dateStr, items, count: items.length });
    }
    return days;
});

const maxDayCount = computed(() => Math.max(...calendarDays.value.filter(Boolean).map(d => d.count), 1));

function heatColor(count) {
    if (!count) return '';
    const ratio = count / maxDayCount.value;
    if (ratio > 0.75) return 'bg-amber-200';
    if (ratio > 0.5) return 'bg-amber-100';
    if (ratio > 0.25) return 'bg-amber-50';
    return 'bg-yellow-50/50';
}

const selectedDayFollowUps = computed(() => {
    if (!selectedDay.value) return [];
    return followUpsByDate.value[selectedDay.value] || [];
});

function selectDay(day) {
    if (!day) return;
    selectedDay.value = day.date;
    showPanel.value = true;
}
function closePanel() { showPanel.value = false; }

function prevMonth() {
    let m = props.month - 1, y = props.year;
    if (m < 1) { m = 12; y--; }
    router.get('/admin/crm/calendar', { month: m, year: y }, { preserveState: true, replace: true });
}
function nextMonth() {
    let m = props.month + 1, y = props.year;
    if (m > 12) { m = 1; y++; }
    router.get('/admin/crm/calendar', { month: m, year: y }, { preserveState: true, replace: true });
}

function completeFollowUp(id) {
    router.post(`/admin/follow-ups/${id}/complete`, {}, { preserveScroll: true });
}
function missFollowUp(id) {
    router.post(`/admin/follow-ups/${id}/miss`, {}, { preserveScroll: true });
}

const statusBadge = {
    pending: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    missed: 'bg-red-100 text-red-700',
};

const stats = computed(() => props.monthStats || {});

const progressPct = computed(() => {
    const total = (stats.value.total || 0);
    if (!total) return 0;
    return Math.round(((stats.value.completed || 0) / total) * 100);
});

const todayStr = computed(() => {
    const t = new Date();
    return `${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,'0')}-${String(t.getDate()).padStart(2,'0')}`;
});

const filters = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending' },
    { key: 'completed', label: 'Completed' },
    { key: 'missed', label: 'Missed' },
    { key: 'overdue', label: 'Overdue' },
];
</script>

<template>
    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6"
                 :class="[mounted ? 'card-entrance-active' : 'card-entrance']">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <Link href="/admin/crm" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      :d="isRtl ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'" />
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">Follow-up Calendar</h1>
                    </div>
                    <p class="text-sm text-gray-500">{{ monthNames[month - 1] }} {{ year }}</p>
                </div>
                <!-- Month nav -->
                <div class="flex items-center gap-2">
                    <button @click="prevMonth"
                            class="p-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  :d="isRtl ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'" />
                        </svg>
                    </button>
                    <span class="text-sm font-semibold text-gray-700 min-w-[140px] text-center">
                        {{ monthNames[month - 1] }} {{ year }}
                    </span>
                    <button @click="nextMonth"
                            class="p-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Stats + Progress bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6"
                 :class="[mounted ? 'card-entrance-active' : 'card-entrance']">
                <div class="flex flex-wrap items-center gap-6 mb-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</div>
                        <div class="text-xs text-gray-500">Total</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending || 0 }}</div>
                        <div class="text-xs text-gray-500">Pending</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.completed || 0 }}</div>
                        <div class="text-xs text-gray-500">Completed</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ stats.missed || 0 }}</div>
                        <div class="text-xs text-gray-500">Missed</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ stats.overdue || 0 }}</div>
                        <div class="text-xs text-gray-500">Overdue</div>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-500"
                         :style="{ width: progressPct + '%', backgroundColor: GOLD }"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">{{ progressPct }}% completed this month</p>
            </div>

            <!-- Status filters -->
            <div class="flex flex-wrap gap-2 mb-4"
                 :class="[mounted ? 'card-entrance-active' : 'card-entrance']">
                <button v-for="f in filters" :key="f.key"
                        @click="statusFilter = f.key"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                        :class="statusFilter === f.key
                            ? 'text-white border-transparent'
                            : 'text-gray-600 border-gray-200 hover:bg-gray-50'"
                        :style="statusFilter === f.key ? { backgroundColor: GOLD } : {}">
                    {{ f.label }}
                </button>
            </div>

            <div class="flex gap-6">
                <!-- Calendar grid -->
                <div class="flex-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                     :class="[mounted ? 'card-entrance-active' : 'card-entrance']">
                    <!-- Week day headers -->
                    <div class="grid grid-cols-7 border-b border-gray-100">
                        <div v-for="d in weekDays" :key="d"
                             class="py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            {{ d }}
                        </div>
                    </div>
                    <!-- Day cells -->
                    <div class="grid grid-cols-7">
                        <div v-for="(cell, i) in calendarDays" :key="i"
                             @click="selectDay(cell)"
                             class="min-h-[90px] border-b border-gray-50 p-1.5 transition cursor-pointer hover:bg-gray-50/50"
                             :class="[
                                 i % 7 !== 6 ? (isRtl ? 'border-l border-gray-50' : 'border-r border-gray-50') : '',
                                 cell && cell.date === todayStr ? 'ring-1 ring-inset ring-amber-300/60' : '',
                                 cell ? heatColor(cell.count) : '',
                                 cell && cell.date === selectedDay ? 'bg-amber-50/80' : '',
                             ]">
                            <template v-if="cell">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold"
                                          :class="cell.date === todayStr ? 'text-amber-700' : 'text-gray-700'">
                                        {{ cell.day }}
                                    </span>
                                    <span v-if="cell.count"
                                          class="text-[10px] font-medium text-white rounded-full w-4 h-4 flex items-center justify-center"
                                          :style="{ backgroundColor: GOLD }">
                                        {{ cell.count }}
                                    </span>
                                </div>
                                <!-- Mini follow-up preview (max 2) -->
                                <div v-for="(fu, fi) in cell.items.slice(0, 2)" :key="fu.id"
                                     class="text-[10px] leading-tight mb-0.5 px-1 py-0.5 rounded truncate"
                                     :class="statusBadge[fu.status] || 'bg-gray-100 text-gray-600'">
                                    {{ fu.lead_name }}
                                </div>
                                <div v-if="cell.items.length > 2"
                                     class="text-[9px] text-gray-400 px-1">
                                    +{{ cell.items.length - 2 }} more
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Side panel -->
                <Transition name="slide">
                    <div v-if="showPanel" class="w-80 flex-shrink-0">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 sticky top-6">
                            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">{{ selectedDay }}</h3>
                                <button @click="closePanel" class="text-gray-400 hover:text-gray-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="p-4 space-y-3 max-h-[60vh] overflow-y-auto">
                                <div v-if="!selectedDayFollowUps.length"
                                     class="text-center text-sm text-gray-400 py-8">
                                    No follow-ups for this day
                                </div>
                                <div v-for="fu in selectedDayFollowUps" :key="fu.id"
                                     class="bg-gray-50 rounded-xl p-3 space-y-2">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">{{ fu.lead_name }}</div>
                                            <div class="text-xs text-gray-500">{{ fu.lead_phone }}</div>
                                        </div>
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-md"
                                              :class="statusBadge[fu.status] || 'bg-gray-100 text-gray-600'">
                                            {{ fu.status }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" fill="none"/>
                                        </svg>
                                        {{ fu.scheduled_time || '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <span class="font-medium text-gray-600">Assignee:</span> {{ fu.assigned_to_name || '-' }}
                                    </div>
                                    <div v-if="fu.type" class="text-xs text-gray-500">
                                        <span class="font-medium text-gray-600">Type:</span> {{ fu.type }}
                                    </div>
                                    <div v-if="fu.notes" class="text-xs text-gray-400 italic">{{ fu.notes }}</div>
                                    <div v-if="fu.is_overdue"
                                         class="text-[10px] font-medium text-red-600">Overdue</div>
                                    <!-- Quick actions -->
                                    <div v-if="fu.status === 'pending'" class="flex gap-2 pt-1">
                                        <button @click.stop="completeFollowUp(fu.id)"
                                                class="flex-1 text-xs font-medium py-1.5 rounded-lg text-white transition hover:opacity-90"
                                                :style="{ backgroundColor: GOLD }">
                                            <svg class="w-3.5 h-3.5 inline-block -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Complete
                                        </button>
                                        <button @click.stop="missFollowUp(fu.id)"
                                                class="flex-1 text-xs font-medium py-1.5 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition">
                                            <svg class="w-3.5 h-3.5 inline-block -mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Miss
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.card-entrance {
    opacity: 0;
    transform: translateY(12px);
}
.card-entrance-active {
    opacity: 1;
    transform: translateY(0);
    transition: opacity 0.4s ease, transform 0.4s ease;
}
.slide-enter-active { transition: all 0.25s ease-out; }
.slide-leave-active { transition: all 0.2s ease-in; }
.slide-enter-from, .slide-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>
