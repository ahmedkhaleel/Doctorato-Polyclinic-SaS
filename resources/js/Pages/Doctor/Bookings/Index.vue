<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import SearchableSelect from '@/Components/Doctor/SearchableSelect.vue';
import SkeletonLoader from '@/Components/Doctor/SkeletonLoader.vue';
import WeeklyCalendar from '@/Components/Doctor/WeeklyCalendar.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const modules = computed(() => page.props.modules || {});
const clinicalSlugs = ['derma', 'dental'];
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([slug, m]) => m.is_enabled !== false && clinicalSlugs.includes(slug))
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const props = defineProps({
    bookings: Object,
    bundleBookings: Object,
    bookingsCount: { type: Number, default: 0 },
    bundleCount: { type: Number, default: 0 },
    filters: Object,
});

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
const dataLoading = ref(true);
const viewMode = ref(localStorage.getItem('doctor_bookings_viewMode') || 'list');

function setViewMode(mode) {
    viewMode.value = mode;
    try { localStorage.setItem('doctor_bookings_viewMode', mode); } catch {}
}

// Calendar status config (localized)
const calendarStatusConfig = computed(() => ({
    confirmed: { label: isRtl.value ? 'مؤكد' : 'Confirmed', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500', border: 'border-blue-200' },
    in_progress: { label: isRtl.value ? 'قيد التنفيذ' : 'In Progress', bg: 'bg-indigo-50', text: 'text-indigo-700', dot: 'bg-indigo-500', border: 'border-indigo-200' },
    completed: { label: isRtl.value ? 'مكتمل' : 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', border: 'border-emerald-200' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', border: 'border-red-200' },
    pending: { label: isRtl.value ? 'معلق' : 'Pending', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', border: 'border-amber-200' },
}));

function onCalendarEventClick(event) {
    if (event.id) router.visit(`/doctor/bookings/${event.id}`);
}

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
    setTimeout(() => dataLoading.value = false, 600);
});

// ── Tab & Filters ──────────────────────────────────────
const activeTab = ref(props.filters?.tab || 'bookings');
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const moduleFilter = ref(props.filters?.module || '');
let debounce = null;

watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => applyFilters(), 300);
});
watch(status, () => applyFilters());
watch(moduleFilter, () => applyFilters());

function switchTab(tab) {
    activeTab.value = tab;
    search.value = '';
    status.value = '';
    moduleFilter.value = '';
    router.get('/doctor/bookings', { tab }, { preserveState: false });
}

function applyFilters() {
    router.get('/doctor/bookings', {
        tab: activeTab.value,
        search: search.value || undefined,
        status: status.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

const statusConfig = {
    confirmed: { label: 'Confirmed', labelAr: 'مؤكد', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500', border: 'border-blue-200' },
    in_progress: { label: 'In Progress', labelAr: 'قيد التنفيذ', bg: 'bg-indigo-50', text: 'text-indigo-700', dot: 'bg-indigo-500', border: 'border-indigo-200' },
    completed: { label: 'Completed', labelAr: 'مكتمل', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', border: 'border-emerald-200' },
    cancelled: { label: 'Cancelled', labelAr: 'ملغي', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', border: 'border-red-200' },
    pending: { label: 'Pending', labelAr: 'معلق', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', border: 'border-amber-200' },
};

function formatTime(time) {
    if (!time) return '-';
    const [h, m] = time.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const h12 = hour % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

function sessionsText(completed, total) {
    if (!total) return '-';
    return `${completed}/${total}`;
}

function sessionsPercent(completed, total) {
    if (!total) return 0;
    return Math.round((completed / total) * 100);
}

const bookingsList = computed(() => props.bookings?.data || []);
const bundleList = computed(() => props.bundleBookings?.data || []);

// Stats for bookings tab
const totalBookings = computed(() => props.bookingsCount || 0);
const inProgressCount = computed(() => bookingsList.value.filter(b => b.status === 'in_progress').length);
const completedCount = computed(() => bookingsList.value.filter(b => b.status === 'completed').length);

// Active pagination
const activePagination = computed(() => {
    return activeTab.value === 'bookings' ? props.bookings : props.bundleBookings;
});

// Bundle status options
const bundleStatuses = [
    { value: 'pending', label: 'Pending', labelAr: 'معلق' },
    { value: 'in_progress', label: 'In Progress', labelAr: 'قيد التنفيذ' },
    { value: 'completed', label: 'Completed', labelAr: 'مكتمل' },
    { value: 'cancelled', label: 'Cancelled', labelAr: 'ملغي' },
];

// SearchableSelect options
const moduleOptions = computed(() => [
    { value: '', label: isRtl.value ? 'كل الأقسام' : 'All Departments' },
    ...activeModules.value.map(m => ({ value: m.slug, label: m.name })),
]);

const statusOptions = computed(() => {
    const base = [{ value: '', label: isRtl.value ? 'جميع الحالات' : 'All Statuses' }];
    if (activeTab.value === 'bookings') {
        return [...base,
            { value: 'confirmed', label: isRtl.value ? 'مؤكد' : 'Confirmed', color: '#3b82f6' },
            { value: 'in_progress', label: isRtl.value ? 'قيد التنفيذ' : 'In Progress', color: '#6366f1' },
            { value: 'completed', label: isRtl.value ? 'مكتمل' : 'Completed', color: '#10b981' },
            { value: 'cancelled', label: isRtl.value ? 'ملغي' : 'Cancelled', color: '#ef4444' },
        ];
    }
    return [...base, ...bundleStatuses.map(s => ({ value: s.value, label: isRtl.value ? s.labelAr : s.label }))];
});
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-[#C4A265]/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-blue-500/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#B08D4C] flex items-center justify-center shadow-lg shadow-[#C4A265]/20">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ $t('a_bookings') }}</h1>
                        <p class="text-sm text-gray-400 mt-0.5">{{ isRtl ? 'الحجوزات والباقات المسندة إليك' : 'Bookings & packages assigned to you' }}</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex gap-3 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 flex-1">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'الحجوزات' : 'Bookings' }}</p>
                        <p class="text-lg font-bold text-white mt-0.5">{{ bookingsCount }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 flex-1">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'الباقات' : 'Packages' }}</p>
                        <p class="text-lg font-bold text-purple-400 mt-0.5">{{ bundleCount }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 flex-1">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'الإجمالي' : 'Total' }}</p>
                        <p class="text-lg font-bold text-[#C4A265] mt-0.5">{{ bookingsCount + bundleCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div
            class="flex gap-2 transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <button
                @click="switchTab('bookings')"
                class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold transition-all duration-200 border"
                :class="activeTab === 'bookings'
                    ? 'bg-[#C4A265] text-white border-[#C4A265] shadow-md shadow-[#C4A265]/20'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:shadow-sm'"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                {{ isRtl ? 'الحجوزات' : 'Bookings' }}
                <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full"
                    :class="activeTab === 'bookings' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                >{{ bookingsCount }}</span>
            </button>
            <button
                @click="switchTab('packages')"
                class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold transition-all duration-200 border"
                :class="activeTab === 'packages'
                    ? 'bg-[#C4A265] text-white border-[#C4A265] shadow-md shadow-[#C4A265]/20'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:shadow-sm'"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                {{ isRtl ? 'الباقات' : 'Packages' }}
                <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full"
                    :class="activeTab === 'packages' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                >{{ bundleCount }}</span>
            </button>
        </div>

        <!-- Filters Bar -->
        <div
            class="flex flex-wrap gap-3 transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div class="relative flex-1 min-w-[240px]">
                <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input v-model="search" type="text"
                    :placeholder="activeTab === 'bookings'
                        ? (isRtl ? 'بحث بالاسم، الهاتف، رقم الحجز...' : 'Search name, phone, booking #...')
                        : (isRtl ? 'بحث بالاسم، الهاتف، اسم الباقة...' : 'Search name, phone, package...')"
                    class="w-full ps-10 pe-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition" />
            </div>
            <div v-if="activeTab === 'bookings' && activeModules.length > 1" class="w-44">
                <SearchableSelect
                    v-model="moduleFilter"
                    :options="moduleOptions"
                    :placeholder="isRtl ? 'كل الأقسام' : 'All Departments'"
                    :search-placeholder="isRtl ? 'بحث...' : 'Search...'"
                    icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                    size="sm"
                />
            </div>
            <div class="w-44">
                <SearchableSelect
                    v-model="status"
                    :options="statusOptions"
                    :placeholder="isRtl ? 'جميع الحالات' : 'All Statuses'"
                    :search-placeholder="isRtl ? 'بحث...' : 'Search...'"
                    icon="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                    size="sm"
                />
            </div>
            <!-- View Mode Toggle -->
            <div v-if="activeTab === 'bookings'" class="flex items-center bg-gray-100 rounded-lg p-0.5 gap-0.5 ms-auto">
                <button @click="setViewMode('list')" class="p-2 rounded-md transition-all"
                    :class="viewMode === 'list' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <button @click="setViewMode('calendar')" class="p-2 rounded-md transition-all"
                    :class="viewMode === 'calendar' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-400 hover:text-gray-600'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </button>
            </div>
        </div>

        <!-- Skeleton Loading -->
        <SkeletonLoader v-if="dataLoading" type="list" :count="5" />

        <!-- ═══════════ CALENDAR VIEW ═══════════ -->
        <WeeklyCalendar
            v-if="!dataLoading && activeTab === 'bookings' && viewMode === 'calendar'"
            :events="bookingsList"
            :is-rtl="isRtl"
            :status-config="calendarStatusConfig"
            accent-color="#C4A265"
            @event-click="onCalendarEventClick"
        />

        <!-- ═══════════ BOOKINGS TAB (LIST VIEW) ═══════════ -->
        <div v-else-if="activeTab === 'bookings'"
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div v-if="bookingsList.length" class="space-y-3">
                <div
                    v-for="booking in bookingsList"
                    :key="booking.id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
                        <!-- Booking Number + Patient -->
                        <div class="flex items-center gap-3 sm:w-56">
                            <div class="w-11 h-11 rounded-xl bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold text-[#C4A265] font-mono">#{{ booking.booking_number || '-' }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate">{{ booking.patient_name }}</p>
                                <p class="text-xs text-gray-400">{{ booking.patient_phone }}</p>
                            </div>
                        </div>

                        <!-- Service -->
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</p>
                            <p class="text-sm font-semibold text-gray-700 truncate mt-0.5">{{ booking.doctor_service_name }}</p>
                        </div>

                        <!-- Next Appointment -->
                        <div class="sm:w-36">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الموعد القادم' : 'Next Apt.' }}</p>
                            <template v-if="booking.next_appointment_date">
                                <p class="text-sm font-semibold text-gray-700 mt-0.5">{{ booking.next_appointment_date }}</p>
                                <p class="text-[10px] text-gray-400">{{ formatTime(booking.next_appointment_time) }}</p>
                            </template>
                            <span v-else class="text-xs text-gray-300 mt-0.5 block">{{ isRtl ? 'لا يوجد' : 'None' }}</span>
                        </div>

                        <!-- Sessions Progress -->
                        <div class="sm:w-24">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الجلسات' : 'Sessions' }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-sm font-bold text-gray-700 tabular-nums">{{ sessionsText(booking.doctor_completed_sessions, booking.doctor_sessions_count) }}</span>
                            </div>
                            <div v-if="booking.doctor_sessions_count" class="w-full h-1.5 bg-gray-100 rounded-full mt-1.5 overflow-hidden">
                                <div class="h-full bg-[#C4A265] rounded-full transition-all duration-500" :style="{ width: sessionsPercent(booking.doctor_completed_sessions, booking.doctor_sessions_count) + '%' }"></div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="flex items-center">
                            <span v-if="statusConfig[booking.status]"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-lg border"
                                :class="[statusConfig[booking.status].bg, statusConfig[booking.status].text, statusConfig[booking.status].border]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[booking.status].dot"></span>
                                {{ isRtl ? statusConfig[booking.status].labelAr : statusConfig[booking.status].label }}
                            </span>
                            <span v-else class="text-xs text-gray-400">{{ booking.status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد حجوزات' : 'No bookings found' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'الحجوزات المؤكدة فقط تظهر هنا' : 'Only confirmed bookings appear here' }}</p>
            </div>
        </div>

        <!-- ═══════════ PACKAGES TAB ═══════════ -->
        <div v-else-if="activeTab === 'packages'"
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div v-if="bundleList.length" class="space-y-3">
                <div
                    v-for="bb in bundleList"
                    :key="bb.id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden"
                >
                    <div class="p-5">
                        <!-- Top Row: Booking Number + Patient + Status -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-800 truncate">{{ bb.patient_name }}</p>
                                    <p class="text-xs text-gray-400">{{ bb.patient_phone }}</p>
                                </div>
                            </div>

                            <!-- Bundle Name -->
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الباقة' : 'Package' }}</p>
                                <p class="text-sm font-semibold text-purple-700 truncate mt-0.5">{{ bb.bundle_name }}</p>
                                <p class="text-[10px] font-mono text-gray-400 mt-0.5">{{ bb.booking_number }}</p>
                            </div>

                            <!-- Overall Progress -->
                            <div class="sm:w-28">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'التقدم الكلي' : 'Progress' }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-sm font-bold text-gray-700 tabular-nums">{{ sessionsText(bb.doctor_completed_sessions, bb.doctor_total_sessions) }}</span>
                                    <span class="text-[10px] text-gray-400">({{ bb.progress_percent }}%)</span>
                                </div>
                                <div class="w-full h-1.5 bg-gray-100 rounded-full mt-1.5 overflow-hidden">
                                    <div class="h-full bg-purple-500 rounded-full transition-all duration-500" :style="{ width: bb.progress_percent + '%' }"></div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="flex items-center">
                                <span v-if="statusConfig[bb.status]"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-lg border"
                                    :class="[statusConfig[bb.status].bg, statusConfig[bb.status].text, statusConfig[bb.status].border]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[bb.status].dot"></span>
                                    {{ isRtl ? statusConfig[bb.status].labelAr : statusConfig[bb.status].label }}
                                </span>
                                <span v-else class="text-xs text-gray-400">{{ bb.status }}</span>
                            </div>
                        </div>

                        <!-- Services Breakdown -->
                        <div v-if="bb.doctor_services?.length" class="mt-4 pt-3 border-t border-gray-100">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase mb-2">{{ isRtl ? 'خدماتك في هذه الباقة' : 'Your services in this package' }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                <div v-for="(svc, i) in bb.doctor_services" :key="i"
                                    class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-700 truncate">{{ svc.name }}</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span class="text-xs font-bold tabular-nums"
                                            :class="svc.completed_sessions >= svc.sessions_count ? 'text-emerald-600' : 'text-gray-600'"
                                        >{{ svc.completed_sessions }}/{{ svc.sessions_count }}</span>
                                        <div class="w-12 h-1 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="svc.completed_sessions >= svc.sessions_count ? 'bg-emerald-500' : 'bg-purple-400'"
                                                :style="{ width: sessionsPercent(svc.completed_sessions, svc.sessions_count) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                <div class="w-16 h-16 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد حجوزات باقات' : 'No package bookings found' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'حجوزات الباقات المسندة إليك ستظهر هنا' : 'Package bookings assigned to you will appear here' }}</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="activePagination?.links?.length > 3" class="flex items-center justify-center gap-1 mt-4">
            <template v-for="link in activePagination.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100 bg-white border border-gray-200'" v-html="link.label" preserve-state />
                <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
