<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visits: Object,
    todayStats: Object,
    upcomingCount: { type: Number, default: 0 },
    filters: { type: Object, default: () => ({}) },
    medicalAlerts: { type: Object, default: () => ({}) },
});

/* ── Medical alert helpers ──────────────────────────────── */
const expandedAlerts = ref({});

function hasAlert(visitId) {
    return props.medicalAlerts && props.medicalAlerts[visitId]?.length > 0;
}

function getAlertFlags(visitId) {
    return props.medicalAlerts?.[visitId] || [];
}

function highRiskCount(visitId) {
    return getAlertFlags(visitId).filter(f => f.severity === 'high').length;
}

function toggleAlert(visitId) {
    expandedAlerts.value[visitId] = !expandedAlerts.value[visitId];
}

const severityStyles = {
    high: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200' },
    medium: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200' },
    low: { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200' },
};

const mounted = ref(false);
const activeView = ref(props.filters.view || 'today');
const statusFilter = ref(props.filters.status || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const showDateFilter = ref(!!(props.filters.date_from || props.filters.date_to));
const moduleFilter = ref(props.filters.module || '');

// Animated counters
const animatedStats = ref({ total: 0, waiting: 0, in_progress: 0, completed: 0 });

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    animateCounters();
    if (activeView.value === 'today') {
        refreshInterval = setInterval(refreshQueue, 30000);
    }
});

function animateCounters() {
    const targets = {
        total: props.todayStats?.total || 0,
        waiting: props.todayStats?.waiting || 0,
        in_progress: props.todayStats?.in_progress || 0,
        completed: props.todayStats?.completed || 0,
    };
    const duration = 1200;
    const start = performance.now();
    function step(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        animatedStats.value.total = Math.round(targets.total * eased);
        animatedStats.value.waiting = Math.round(targets.waiting * eased);
        animatedStats.value.in_progress = Math.round(targets.in_progress * eased);
        animatedStats.value.completed = Math.round(targets.completed * eased);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

const statusConfig = computed(() => ({
    waiting: { label: isRtl.value ? 'انتظار' : 'Waiting', bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-400' },
    in_progress: { label: isRtl.value ? 'جاري' : 'In Progress', bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500' },
    completed: { label: isRtl.value ? 'مكتمل' : 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-gray-50', text: 'text-gray-500', border: 'border-gray-200', dot: 'bg-gray-400' },
}));

const visitTypeLabels = computed(() => isRtl.value
    ? { consultation: 'استشارة', session: 'جلسة', follow_up: 'متابعة' }
    : { consultation: 'Consultation', session: 'Session', follow_up: 'Follow-up' }
);
const consultationTypeLabels = computed(() => isRtl.value
    ? { dermatology: 'جلدية', cosmetic: 'تجميل' }
    : { dermatology: 'Dermatology', cosmetic: 'Cosmetic' }
);

/* ── Dental helpers ────────────────────────────────── */
const dentalTreatmentTypeLabels = computed(() => isRtl.value
    ? { filling: 'حشو', extraction: 'خلع', root_canal: 'علاج عصب', crown: 'تاج', bridge: 'جسر', implant: 'زراعة', cleaning: 'تنظيف', scaling: 'تقليح', whitening: 'تبييض', orthodontic: 'تقويم', surgical_extraction: 'خلع جراحي' }
    : { filling: 'Filling', extraction: 'Extraction', root_canal: 'Root Canal', crown: 'Crown', bridge: 'Bridge', implant: 'Implant', cleaning: 'Cleaning', scaling: 'Scaling', whitening: 'Whitening', orthodontic: 'Orthodontic', surgical_extraction: 'Surgical Extraction' }
);

function getDentalDescription(visit) {
    if (visit.module !== 'dental' || !visit.dental_treatments?.length) return '';
    const t = visit.dental_treatments[0];
    const label = dentalTreatmentTypeLabels.value[t.treatment_type] || t.treatment_type?.replace(/_/g, ' ');
    const tooth = t.tooth_number ? ` #${t.tooth_number}` : '';
    return `${label}${tooth}`;
}

// Auto-Refresh
let refreshInterval = null;
function refreshQueue() {
    router.reload({ only: ['visits', 'todayStats', 'upcomingCount'], preserveScroll: true });
}

onUnmounted(() => { if (refreshInterval) clearInterval(refreshInterval); });

// Navigation
function switchView(view) {
    activeView.value = view;
    statusFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    showDateFilter.value = false;
    if (refreshInterval) clearInterval(refreshInterval);
    if (view === 'today') refreshInterval = setInterval(refreshQueue, 30000);
    applyFilters();
}

function applyFilters() {
    const params = { view: activeView.value };
    if (statusFilter.value) params.status = statusFilter.value;
    if (moduleFilter.value) params.module = moduleFilter.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;
    router.get('/doctor/queue', params, { preserveState: true, preserveScroll: true });
}

function setModuleFilter(val) {
    moduleFilter.value = val;
    applyFilters();
}

function clearDateFilter() {
    dateFrom.value = '';
    dateTo.value = '';
    showDateFilter.value = false;
    applyFilters();
}

// Wait Time
function getWaitTime(visit) {
    if (visit.status !== 'waiting') return null;
    const createdAt = new Date(visit.created_at);
    const now = new Date();
    const diffMin = Math.floor((now - createdAt) / 60000);
    if (diffMin < 1) return isRtl.value ? 'الآن' : 'Just now';
    if (diffMin < 60) return `${diffMin}${isRtl.value ? 'د' : 'm'}`;
    const hrs = Math.floor(diffMin / 60);
    const mins = diffMin % 60;
    return mins > 0 ? `${hrs}${isRtl.value ? 'س' : 'h'} ${mins}${isRtl.value ? 'د' : 'm'}` : `${hrs}${isRtl.value ? 'س' : 'h'}`;
}

function getDuration(visit) {
    if (!visit.started_at || !visit.completed_at) return null;
    const start = new Date(visit.started_at);
    const end = new Date(visit.completed_at);
    const diffMin = Math.floor((end - start) / 60000);
    if (diffMin < 60) return `${diffMin}${isRtl.value ? 'د' : 'm'}`;
    const hrs = Math.floor(diffMin / 60);
    const mins = diffMin % 60;
    return mins > 0 ? `${hrs}${isRtl.value ? 'س' : 'h'} ${mins}${isRtl.value ? 'د' : 'm'}` : `${hrs}${isRtl.value ? 'س' : 'h'}`;
}

// Formatters
function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate() + 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    if (d.toDateString() === tomorrow.toDateString()) return isRtl.value ? 'غداً' : 'Tomorrow';
    return d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(time) {
    if (!time) return '';
    try {
        const [h, m] = time.split(':');
        const hr = parseInt(h);
        const ampm = hr >= 12 ? 'PM' : 'AM';
        return `${hr % 12 || 12}:${m} ${ampm}`;
    } catch { return time; }
}

// Confirmation Dialogs
const showConfirmStart = ref(null);
const showConfirmComplete = ref(null);
const showConfirmCancel = ref(null);

function startVisit(visit) {
    router.post(`/doctor/visits/${visit.id}/start`, {}, { preserveScroll: true });
    showConfirmStart.value = null;
}
function completeVisit(visit) {
    router.post(`/doctor/visits/${visit.id}/complete`, {}, { preserveScroll: true });
    showConfirmComplete.value = null;
}
function cancelVisit(visit) {
    router.post(`/doctor/visits/${visit.id}/cancel`, {}, { preserveScroll: true });
    showConfirmCancel.value = null;
}

// Computed
const timeDisplay = computed(() => {
    return new Date().toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

function getVisitTypeLabel(visit) {
    if (visit.visit_type === 'consultation' && visit.consultation_type) {
        const ctLabel = consultationTypeLabels.value[visit.consultation_type] || visit.consultation_type;
        return isRtl.value ? `${ctLabel} استشارة` : `${ctLabel} Consultation`;
    }
    return visitTypeLabels.value[visit.visit_type] || visit.visit_type || (isRtl.value ? 'زيارة' : 'Visit');
}

const viewTabs = computed(() => [
    { key: 'today', label: isRtl.value ? 'اليوم' : 'Today', count: props.todayStats.total },
    { key: 'upcoming', label: isRtl.value ? 'القادمة' : 'Upcoming', count: props.upcomingCount },
    { key: 'past', label: isRtl.value ? 'السابقة' : 'Past', count: null },
    { key: 'all', label: isRtl.value ? 'الكل' : 'All', count: null },
]);

const currentInProgress = computed(() => {
    if (!props.visits?.data) return null;
    return props.visits.data.find(v => v.status === 'in_progress');
});

// Greeting based on time
const greeting = computed(() => {
    const hr = new Date().getHours();
    if (isRtl.value) {
        if (hr < 12) return 'صباح الخير';
        if (hr < 17) return 'مساء الخير';
        return 'مساء الخير';
    }
    if (hr < 12) return 'Good Morning';
    if (hr < 17) return 'Good Afternoon';
    return 'Good Evening';
});

// Progress bar for today
const todayProgress = computed(() => {
    const total = props.todayStats?.total || 0;
    const completed = props.todayStats?.completed || 0;
    return total > 0 ? Math.round((completed / total) * 100) : 0;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ greeting }} Queue</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ $t('a_today_queue') }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ timeDisplay }}</p>
                    </div>
                    <button @click="refreshQueue"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white/80 bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl transition-all backdrop-blur-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        {{ isRtl ? 'تحديث' : 'Refresh' }}
                    </button>
                </div>

                <!-- Stats in Hero -->
                <div v-if="activeView === 'today'" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-white">{{ animatedStats.total }}</p>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'الإجمالي' : 'Total' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-amber-400">{{ animatedStats.waiting }}</p>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'انتظار' : 'Waiting' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-blue-400">{{ animatedStats.in_progress }}</p>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'جاري' : 'In Progress' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.25s"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-emerald-400">{{ animatedStats.completed }}</p>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today Progress Bar -->
                <div v-if="activeView === 'today' && todayStats.total > 0" class="mt-4"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
                >
                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'تقدم اليوم' : 'Today\'s Progress' }}</p>
                        <p class="text-xs font-semibold text-[#C4A265]">{{ todayProgress }}%</p>
                    </div>
                    <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#C4A265] to-[#D4B87A] rounded-full transition-all duration-1000 ease-out"
                            :style="{ width: mounted ? todayProgress + '%' : '0%' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current In-Progress Banner -->
        <div v-if="currentInProgress && activeView === 'today'"
            class="relative overflow-hidden rounded-2xl border-2 border-blue-200 bg-gradient-to-r from-blue-50 via-white to-blue-50 p-5"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.35s"
        >
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100/30 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="relative flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="w-14 h-14 rounded-2xl bg-blue-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <div class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-blue-500 rounded-full border-2 border-white flex items-center justify-center">
                            <div class="w-1.5 h-1.5 bg-white rounded-full pulse-dot"></div>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-0.5">{{ isRtl ? 'حالياً مع المريض' : 'Currently with patient' }}</p>
                        <p class="text-lg font-bold text-gray-900">{{ currentInProgress.patient?.full_name }}</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-gray-500">{{ currentInProgress.patient?.file_number }}</span>
                            <span class="text-gray-300">&middot;</span>
                            <span class="text-xs text-gray-500">{{ getVisitTypeLabel(currentInProgress) }}</span>
                            <template v-if="currentInProgress.service">
                                <span class="text-gray-300">&middot;</span>
                                <span class="text-xs text-gray-500">{{ currentInProgress.service?.name_en }}</span>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="showConfirmComplete = currentInProgress" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-all shadow-sm shadow-emerald-200 hover:shadow-md hover:shadow-emerald-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        {{ isRtl ? 'إكمال' : 'Complete' }}
                    </button>
                    <Link :href="`/doctor/visits/${currentInProgress.id}`" class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-all hover:shadow-sm">
                        {{ isRtl ? 'عرض التفاصيل' : 'View Details' }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.4s"
        >
            <!-- Tabs & Filters -->
            <div class="px-5 pt-4 pb-0 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-1 bg-gray-100/80 rounded-xl p-1">
                    <button v-for="tab in viewTabs" :key="tab.key" @click="switchView(tab.key)"
                        class="relative px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                        :class="activeView === tab.key
                            ? 'bg-white text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'"
                    >
                        {{ tab.label }}
                        <span v-if="tab.count" class="ml-1.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold transition-colors"
                            :class="activeView === tab.key ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'bg-gray-200 text-gray-500'"
                        >{{ tab.count }}</span>
                    </button>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Module Filter -->
                    <div class="inline-flex items-center rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="setModuleFilter('')"
                            class="px-2.5 py-1.5 text-[11px] font-semibold transition-all"
                            :class="!moduleFilter ? 'bg-gray-800 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                        >{{ isRtl ? 'الكل' : 'All' }}</button>
                        <button @click="setModuleFilter('dental')"
                            class="px-2.5 py-1.5 text-[11px] font-semibold transition-all border-x border-gray-200"
                            :class="moduleFilter === 'dental' ? 'bg-cyan-600 text-white' : 'bg-white text-cyan-700 hover:bg-cyan-50'"
                        ><svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg> {{ isRtl ? 'أسنان' : 'Dental' }}</button>
                        <button @click="setModuleFilter('dermatology')"
                            class="px-2.5 py-1.5 text-[11px] font-semibold transition-all"
                            :class="moduleFilter === 'dermatology' ? 'bg-pink-600 text-white' : 'bg-white text-pink-700 hover:bg-pink-50'"
                        >{{ isRtl ? 'جلدية' : 'Derma' }}</button>
                    </div>
                    <select v-model="statusFilter" @change="applyFilters" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-600 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                        <option value="">{{ isRtl ? 'كل الحالات' : 'All Statuses' }}</option>
                        <option value="waiting">{{ isRtl ? 'انتظار' : 'Waiting' }}</option>
                        <option value="in_progress">{{ isRtl ? 'جاري' : 'In Progress' }}</option>
                        <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                        <option value="cancelled">{{ isRtl ? 'ملغي' : 'Cancelled' }}</option>
                    </select>
                    <button @click="showDateFilter = !showDateFilter"
                        class="p-2 rounded-lg border transition-all duration-200"
                        :class="showDateFilter ? 'bg-[#C4A265]/5 border-[#C4A265]/30 text-[#C4A265]' : 'border-gray-200 text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </button>
                </div>
            </div>

            <!-- Date Range Filter -->
            <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="max-h-0 opacity-0" enter-to-class="max-h-20 opacity-100" leave-active-class="transition-all duration-150 ease-in" leave-from-class="max-h-20 opacity-100" leave-to-class="max-h-0 opacity-0">
                <div v-if="showDateFilter" class="px-5 pt-3 pb-0 overflow-hidden">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-500">{{ isRtl ? 'من' : 'From' }}</label>
                            <input v-model="dateFrom" type="date" :max="dateTo || undefined" @change="applyFilters" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-600 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265]" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-500">{{ isRtl ? 'إلى' : 'To' }}</label>
                            <input v-model="dateTo" type="date" :min="dateFrom || undefined" @change="applyFilters" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-600 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265]" />
                        </div>
                        <button v-if="dateFrom || dateTo" @click="clearDateFilter" class="text-xs text-red-400 hover:text-red-600 font-medium transition-colors">{{ isRtl ? 'مسح' : 'Clear' }}</button>
                    </div>
                </div>
            </Transition>

            <!-- Visits List -->
            <div class="mt-3">
                <div v-if="visits.data?.length > 0" class="divide-y divide-gray-100/80">
                    <div v-for="(visit, index) in visits.data" :key="visit.id"
                        class="group px-5 py-4 hover:bg-gray-50/60 transition-all duration-200 cursor-pointer"
                        :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                        :style="{ transition: 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.45 + index * 0.03}s` }"
                        @click="$inertia ? null : router.visit(`/doctor/visits/${visit.id}`)"
                    >
                        <div class="flex flex-wrap items-center justify-between">
                            <div class="flex items-center gap-4 min-w-0 flex-1">
                                <!-- Status Indicator -->
                                <div class="relative flex-shrink-0">
                                    <div class="w-11 h-11 rounded-xl flex items-center justify-center border transition-all duration-200 group-hover:scale-105"
                                        :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.border]"
                                    >
                                        <svg v-if="visit.status === 'waiting'" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else-if="visit.status === 'in_progress'" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <svg v-else-if="visit.status === 'completed'" class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </div>
                                    <div v-if="visit.status === 'in_progress'" class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white bg-blue-500">
                                        <div class="w-full h-full rounded-full animate-ping bg-blue-400 opacity-75"></div>
                                    </div>
                                </div>

                                <!-- Visit Details -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-gray-900 transition-colors">{{ visit.patient?.full_name }}</p>
                                        <!-- Medical Risk Alert Badge -->
                                        <button
                                            v-if="hasAlert(visit.id)"
                                            @click.prevent="toggleAlert(visit.id)"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200 hover:bg-red-200 transition cursor-pointer flex-shrink-0"
                                            :class="{ 'animate-pulse': !expandedAlerts[visit.id] }"
                                            :title="isRtl ? 'تنبيه طبي' : 'Medical Alert'">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            {{ highRiskCount(visit.id) }} {{ isRtl ? 'خطر' : 'risk' }}
                                        </button>
                                        <span v-if="getWaitTime(visit)" class="flex-shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 border border-amber-200 tabular-nums">
                                            {{ getWaitTime(visit) }}
                                        </span>
                                        <span v-if="getDuration(visit)" class="flex-shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 tabular-nums">
                                            {{ getDuration(visit) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                        <span class="text-xs text-gray-400">{{ visit.patient?.file_number || '-' }}</span>
                                        <span class="text-gray-200">&middot;</span>
                                        <span v-if="visit.module === 'dental'" class="inline-flex items-center gap-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg> {{ isRtl ? 'أسنان' : 'Dental' }}</span>
                                        <span v-else class="inline-flex items-center gap-0.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-pink-50 text-pink-700 border border-pink-200">{{ isRtl ? 'جلدية' : 'Derma' }}</span>
                                        <span class="text-gray-200">&middot;</span>
                                        <span class="inline-flex items-center text-xs font-medium px-1.5 py-0.5 rounded bg-gray-50 text-gray-600">{{ getVisitTypeLabel(visit) }}</span>
                                        <template v-if="getDentalDescription(visit)">
                                            <span class="text-gray-200">&middot;</span>
                                            <span class="text-xs font-medium text-cyan-600">{{ getDentalDescription(visit) }}</span>
                                        </template>
                                        <template v-if="visit.service">
                                            <span class="text-gray-200">&middot;</span>
                                            <span class="text-xs text-gray-500">{{ visit.service.name_en }}</span>
                                        </template>
                                        <template v-if="activeView !== 'today'">
                                            <span class="text-gray-200">&middot;</span>
                                            <span class="text-xs text-gray-400">{{ formatDate(visit.visit_date) }}</span>
                                        </template>
                                        <template v-if="visit.scheduled_time">
                                            <span class="text-gray-200">&middot;</span>
                                            <span class="text-xs text-gray-400">{{ formatTime(visit.scheduled_time) }}</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Medical Alert Details (expandable) -->
                            <div v-if="hasAlert(visit.id) && expandedAlerts[visit.id]"
                                class="w-full mt-2 p-3 rounded-xl bg-red-50 border border-red-200">
                                <p class="text-[11px] font-bold text-red-800 mb-2 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ isRtl ? 'تنبيهات المخاطر الطبية' : 'Medical Risk Alerts' }}
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="flag in getAlertFlags(visit.id)" :key="flag.key"
                                        :class="[severityStyles[flag.severity]?.bg || 'bg-gray-50', severityStyles[flag.severity]?.text || 'text-gray-600', severityStyles[flag.severity]?.border || 'border-gray-200']"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold border">
                                        <svg v-if="flag.severity === 'high'" class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ isRtl ? flag.label_ar : flag.label_en }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right Side -->
                            <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                                <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border"
                                    :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text, statusConfig[visit.status]?.border]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[visit.status]?.dot"></span>
                                    {{ statusConfig[visit.status]?.label }}
                                </span>
                                <div class="flex items-center gap-1.5" @click.stop>
                                    <button v-if="visit.status === 'waiting'" @click="showConfirmStart = visit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded-lg transition-all shadow-sm shadow-blue-200 hover:shadow-md hover:shadow-blue-200 hover:-translate-y-0.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /></svg>
                                        {{ isRtl ? 'بدء' : 'Start' }}
                                    </button>
                                    <button v-if="visit.status === 'in_progress'" @click="showConfirmComplete = visit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-all shadow-sm shadow-emerald-200 hover:shadow-md hover:shadow-emerald-200 hover:-translate-y-0.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        {{ isRtl ? 'إكمال' : 'Complete' }}
                                    </button>
                                    <button v-if="visit.status === 'waiting' || visit.status === 'in_progress'" @click="showConfirmCancel = visit"
                                        class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Cancel Visit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                    <Link :href="`/doctor/visits/${visit.id}`"
                                        class="p-1.5 text-gray-300 group-hover:text-[#C4A265] hover:bg-[#C4A265]/5 rounded-lg transition-all" title="View Details"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-20 text-center">
                    <div class="w-20 h-20 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                        <svg v-if="activeView === 'today'" class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else-if="activeView === 'upcoming'" class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <svg v-else class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500">
                        <template v-if="activeView === 'today'">{{ isRtl ? 'لا يوجد مرضى في الطابور اليوم' : 'No patients in queue today' }}</template>
                        <template v-else-if="activeView === 'upcoming'">{{ isRtl ? 'لا توجد زيارات قادمة' : 'No upcoming visits' }}</template>
                        <template v-else-if="activeView === 'past'">{{ isRtl ? 'لا توجد زيارات سابقة' : 'No past visits found' }}</template>
                        <template v-else>{{ isRtl ? 'لا توجد زيارات' : 'No visits found' }}</template>
                    </p>
                    <p class="text-xs text-gray-400 mt-1.5">
                        <template v-if="activeView === 'today'">{{ isRtl ? 'سيظهر المرضى هنا عند إنشاء الزيارات' : 'Patients will appear here when visits are created' }}</template>
                        <template v-else-if="activeView === 'upcoming'">{{ isRtl ? 'ستظهر الزيارات المجدولة هنا' : 'Scheduled visits will appear here' }}</template>
                        <template v-else>{{ isRtl ? 'حاول تعديل الفلاتر' : 'Try adjusting your filters' }}</template>
                    </p>
                </div>
            </div>

            <!-- Pagination & Footer -->
            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-[11px] text-gray-400">
                    <template v-if="activeView === 'today'">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span>
                            {{ isRtl ? 'تحديث تلقائي كل 30 ثانية' : 'Auto-refreshes every 30s' }}
                        </span>
                    </template>
                    <template v-else>Showing {{ visits.from || 0 }}-{{ visits.to || 0 }} of {{ visits.total || 0 }}</template>
                </p>
                <div v-if="visits.last_page > 1" class="flex items-center gap-1">
                    <Link v-for="link in visits.links" :key="link.label"
                        :href="link.url || ''"
                        class="px-2.5 py-1 text-xs rounded-lg transition-colors"
                        :class="link.active
                            ? 'bg-[#C4A265] text-white font-semibold'
                            : link.url
                                ? 'text-gray-500 hover:bg-gray-100'
                                : 'text-gray-300 cursor-not-allowed'"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
                <p v-else class="text-[11px] text-gray-400">{{ visits.total || visits.data?.length || 0 }} visit{{ (visits.total || visits.data?.length || 0) !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        <!-- ═══ CONFIRMATION MODALS ═══ -->

        <!-- Start Visit Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showConfirmStart" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showConfirmStart = null">
                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95" appear>
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'بدء الزيارة؟' : 'Start Visit?' }}</h3>
                                    <p class="text-sm text-gray-500">{{ showConfirmStart?.patient?.full_name }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mb-5">{{ isRtl ? 'سيتم نقل المريض من قائمة الانتظار إلى قيد التنفيذ.' : 'The patient will be moved from the waiting queue to in-progress.' }}</p>
                            <div class="flex justify-end gap-2">
                                <button @click="showConfirmStart = null" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button @click="startVisit(showConfirmStart)" class="px-5 py-2 text-sm font-semibold text-white bg-blue-500 hover:bg-blue-600 rounded-xl transition-colors shadow-sm shadow-blue-200">{{ isRtl ? 'بدء الزيارة' : 'Start Visit' }}</button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Complete Visit Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showConfirmComplete" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showConfirmComplete = null">
                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95" appear>
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center border border-emerald-100">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'إكمال الزيارة؟' : 'Complete Visit?' }}</h3>
                                    <p class="text-sm text-gray-500">{{ showConfirmComplete?.patient?.full_name }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 mb-5">
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ isRtl ? 'سيتم إنهاء الزيارة وحساب عمولتك.' : 'This will finalize the visit and calculate your commission.' }}
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="showConfirmComplete = null" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button @click="completeVisit(showConfirmComplete)" class="px-5 py-2 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm shadow-emerald-200">{{ isRtl ? 'إكمال' : 'Complete' }}</button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Cancel Visit Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showConfirmCancel" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showConfirmCancel = null">
                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95" appear>
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center border border-red-100">
                                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'إلغاء الزيارة؟' : 'Cancel Visit?' }}</h3>
                                    <p class="text-sm text-gray-500">{{ showConfirmCancel?.patient?.full_name }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mb-5">{{ isRtl ? 'سيتم إلغاء الزيارة. للتراجع، تواصل مع المسؤول.' : 'This action will cancel the visit. If you need to reverse this, contact the admin.' }}</p>
                            <div class="flex justify-end gap-2">
                                <button @click="showConfirmCancel = null" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'رجوع' : 'Go Back' }}</button>
                                <button @click="cancelVisit(showConfirmCancel)" class="px-5 py-2 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors shadow-sm shadow-red-200">{{ isRtl ? 'إلغاء الزيارة' : 'Cancel Visit' }}</button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.pulse-dot {
    animation: pulse-dot 2s ease-in-out infinite;
}
@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>
