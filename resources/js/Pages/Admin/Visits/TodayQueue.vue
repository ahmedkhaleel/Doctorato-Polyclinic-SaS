<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visitsByDoctor: Object,
    doctors: Array,
});

const refreshing = ref(false);
const now = ref(new Date());
const confirmingCancel = ref(null);
const mounted = ref(false);
const selectedDoctor = ref(null);
const moduleFilter = ref('all'); // 'all' | 'dental' | 'derma'
let autoRefreshTimer = null;
let clockTimer = null;

const visitTypeLabels = {
    consultation: 'Consultation',
    session: 'Session',
    follow_up: 'Follow Up',
};

const visitTypeIcons = {
    consultation: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    session: 'M13 10V3L4 14h7v7l9-11h-7z',
    follow_up: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
};

/* ── Module / Dental helpers ──────────────────────────── */
const dentalTreatmentTypeLabels = {
    filling: 'Filling', extraction: 'Extraction', root_canal: 'Root Canal',
    crown: 'Crown', bridge: 'Bridge', implant: 'Implant',
    cleaning: 'Cleaning', scaling: 'Scaling', whitening: 'Whitening',
    orthodontic: 'Orthodontic', surgical_extraction: 'Surgical Extraction',
};

function getDentalDescription(visit) {
    if (visit.module !== 'dental' || !visit.dental_treatments?.length) return '';
    const t = visit.dental_treatments[0];
    const label = dentalTreatmentTypeLabels[t.treatment_type] || t.treatment_type?.replace(/_/g, ' ');
    const tooth = t.tooth_number ? ` #${t.tooth_number}` : '';
    return `${label}${tooth}`;
}

/* ── Module filter helper ─────────────────────────────── */
function filterByModule(visits) {
    if (moduleFilter.value === 'all') return visits;
    if (moduleFilter.value === 'dental') return visits.filter(v => v.module === 'dental');
    return visits.filter(v => v.module !== 'dental');
}

// Module counts for filter badges
const dentalCount = computed(() => {
    let count = 0;
    if (props.visitsByDoctor) {
        Object.values(props.visitsByDoctor).forEach(visits => {
            count += visits.filter(v => v.module === 'dental').length;
        });
    }
    return count;
});
const dermaCount = computed(() => {
    let count = 0;
    if (props.visitsByDoctor) {
        Object.values(props.visitsByDoctor).forEach(visits => {
            count += visits.filter(v => v.module !== 'dental').length;
        });
    }
    return count;
});

/* ---- Summary Stats ---- */
const totalStats = computed(() => {
    let waiting = 0, in_progress = 0, completed = 0, cancelled = 0;
    if (props.visitsByDoctor) {
        Object.values(props.visitsByDoctor).forEach(visits => {
            filterByModule(visits).forEach(v => {
                if (v.status === 'waiting') waiting++;
                else if (v.status === 'in_progress') in_progress++;
                else if (v.status === 'completed') completed++;
                else if (v.status === 'cancelled') cancelled++;
            });
        });
    }
    return { waiting, in_progress, completed, cancelled, total: waiting + in_progress + completed + cancelled };
});

/* ---- Filtered Doctors ---- */
const filteredDoctors = computed(() => {
    if (!props.doctors) return [];
    if (!selectedDoctor.value) return props.doctors;
    return props.doctors.filter(d => d.id === selectedDoctor.value);
});

/* ---- Time / Wait Utilities ---- */
function formatTime(datetime) {
    if (!datetime) return '-';
    return new Date(datetime).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

function getWaitMinutes(visit) {
    if (visit.status !== 'waiting') return null;
    const created = new Date(visit.created_at);
    const diff = Math.floor((now.value - created) / 60000);
    return Math.max(0, diff);
}

function formatWait(minutes) {
    if (minutes === null) return '';
    if (minutes < 60) return `${minutes}m`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${h}h ${m}m`;
}

function getWaitLevel(minutes) {
    if (minutes === null) return 'none';
    if (minutes < 15) return 'good';
    if (minutes < 30) return 'warn';
    return 'critical';
}

/* ---- Doctor Helpers ---- */
function getDoctorVisits(doctorId) {
    return filterByModule(props.visitsByDoctor?.[doctorId] || []);
}

function getQueueStats(doctorId) {
    const visits = getDoctorVisits(doctorId);
    return {
        waiting: visits.filter(v => v.status === 'waiting').length,
        in_progress: visits.filter(v => v.status === 'in_progress').length,
        completed: visits.filter(v => v.status === 'completed').length,
        total: visits.length,
    };
}

function getDoctorProgress(doctorId) {
    const stats = getQueueStats(doctorId);
    if (stats.total === 0) return 0;
    return Math.round((stats.completed / stats.total) * 100);
}

/* ---- Actions ---- */
function startVisit(visitId) {
    router.post(`/admin/visits/${visitId}/start`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function completeVisit(visitId) {
    router.post(`/admin/visits/${visitId}/complete`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function cancelVisit(visitId) {
    confirmingCancel.value = visitId;
}

function confirmCancel() {
    if (confirmingCancel.value) {
        router.post(`/admin/visits/${confirmingCancel.value}/cancel`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    }
    confirmingCancel.value = null;
}

/* ---- Refresh ---- */
function refreshQueue() {
    refreshing.value = true;
    router.reload({
        preserveScroll: true,
        onFinish: () => {
            refreshing.value = false;
        },
    });
}

const autoRefreshEnabled = ref(true);

function toggleAutoRefresh() {
    autoRefreshEnabled.value = !autoRefreshEnabled.value;
    if (autoRefreshEnabled.value) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
}

function startAutoRefresh() {
    stopAutoRefresh();
    autoRefreshTimer = setInterval(() => {
        if (!refreshing.value && autoRefreshEnabled.value) {
            refreshing.value = true;
            router.reload({
                preserveScroll: true,
                onFinish: () => {
                    refreshing.value = false;
                },
            });
        }
    }, 30000);
}

function stopAutoRefresh() {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
}

onMounted(() => {
    startAutoRefresh();
    clockTimer = setInterval(() => {
        now.value = new Date();
    }, 15000);
    nextTick(() => { mounted.value = true; });
});

onUnmounted(() => {
    stopAutoRefresh();
    if (clockTimer) clearInterval(clockTimer);
});
</script>

<template>
    <AdminLayout :title="$t('a_todays_queue')">
        <div class="tq-page" :class="{ 'tq-mounted': mounted }">

            <!-- ========== HEADER ========== -->
            <div class="tq-header">
                <div class="tq-header-left">
                    <div class="tq-title-row">
                        <div class="tq-title-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="tq-title">{{ $t('a_todays_queue') }}</h1>
                            <p class="tq-date">
                                <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ new Date().toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="tq-header-actions">
                    <button
                        @click="toggleAutoRefresh"
                        class="tq-btn tq-btn-auto"
                        :class="autoRefreshEnabled ? 'tq-btn-auto--on' : 'tq-btn-auto--off'"
                    >
                        <span class="tq-pulse-dot" :class="autoRefreshEnabled ? 'tq-pulse-dot--on' : 'tq-pulse-dot--off'"></span>
                        {{ $t('a_auto') }} {{ autoRefreshEnabled ? $t('a_on') : $t('a_off') }}
                    </button>
                    <button @click="refreshQueue" :disabled="refreshing" class="tq-btn tq-btn-refresh" :aria-label="isRtl ? 'تحديث' : 'Refresh'" :title="isRtl ? 'تحديث' : 'Refresh'">
                        <svg class="w-4 h-4" :class="{ 'tq-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <Link href="/admin/visits" class="tq-btn tq-btn-outline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        {{ $t('a_all_visits') }}
                    </Link>
                    <Link v-if="can('visits.create')" href="/admin/bookings/create" class="tq-btn tq-btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('a_new_booking') }}
                    </Link>
                </div>
            </div>

            <!-- ========== SUMMARY STATS ========== -->
            <div class="tq-stats-grid">
                <div class="tq-stat tq-stat--waiting" :style="{ transitionDelay: '0.05s' }">
                    <div class="tq-stat-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="tq-stat-info">
                        <span class="tq-stat-number">{{ totalStats.waiting }}</span>
                        <span class="tq-stat-label">{{ $t('a_waiting') }}</span>
                    </div>
                </div>
                <div class="tq-stat tq-stat--active" :style="{ transitionDelay: '0.1s' }">
                    <div class="tq-stat-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="tq-stat-info">
                        <span class="tq-stat-number">{{ totalStats.in_progress }}</span>
                        <span class="tq-stat-label">{{ $t('a_in_progress') }}</span>
                    </div>
                </div>
                <div class="tq-stat tq-stat--completed" :style="{ transitionDelay: '0.15s' }">
                    <div class="tq-stat-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="tq-stat-info">
                        <span class="tq-stat-number">{{ totalStats.completed }}</span>
                        <span class="tq-stat-label">{{ $t('a_completed') }}</span>
                    </div>
                </div>
                <div class="tq-stat tq-stat--total" :style="{ transitionDelay: '0.2s' }">
                    <div class="tq-stat-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="tq-stat-info">
                        <span class="tq-stat-number">{{ totalStats.total }}</span>
                        <span class="tq-stat-label">{{ $t('a_total_visits') }}</span>
                    </div>
                </div>
            </div>

            <!-- ========== FILTER ROW: Doctor + Module ========== -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <!-- Doctor Filter -->
                <div v-if="doctors && doctors.length > 1" class="tq-doctor-filter" style="margin:0">
                    <button
                        @click="selectedDoctor = null"
                        class="tq-filter-pill"
                        :class="{ 'tq-filter-pill--active': !selectedDoctor }"
                    >
                        {{ $t('a_all_doctors') }}
                    </button>
                    <button
                        v-for="doctor in doctors"
                        :key="'filter-'+doctor.id"
                        @click="selectedDoctor = selectedDoctor === doctor.id ? null : doctor.id"
                        class="tq-filter-pill"
                        :class="{ 'tq-filter-pill--active': selectedDoctor === doctor.id }"
                    >
                        Dr. {{ $localized(doctor, 'name') }}
                    </button>
                </div>

                <!-- Module Filter -->
                <div class="flex items-center gap-1.5">
                    <button
                        @click="moduleFilter = 'all'"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full text-[11px] font-semibold border transition-all"
                        :class="moduleFilter === 'all' ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                    >
                        {{ $t('a_all') }}
                        <span class="text-[10px] opacity-70">({{ dentalCount + dermaCount }})</span>
                    </button>
                    <button
                        @click="moduleFilter = 'dental'"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full text-[11px] font-semibold border transition-all"
                        :class="moduleFilter === 'dental' ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-[#1B365D] border-slate-200 hover:bg-slate-50'"
                    >
                        <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg> {{ $t('a_dental') }}
                        <span class="text-[10px] opacity-70">({{ dentalCount }})</span>
                    </button>
                    <button
                        @click="moduleFilter = 'derma'"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full text-[11px] font-semibold border transition-all"
                        :class="moduleFilter === 'derma' ? 'bg-[#C4A265] text-white border-[#C4A265]' : 'bg-white text-[#C4A265] border-amber-200 hover:bg-amber-50'"
                    >
                        {{ $t('a_derma') }}
                        <span class="text-[10px] opacity-70">({{ dermaCount }})</span>
                    </button>
                </div>
            </div>

            <!-- ========== EMPTY STATE ========== -->
            <div v-if="!doctors || doctors.length === 0" class="tq-empty-state">
                <div class="tq-empty-icon">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mt-4">{{ $t('a_no_doctors_available') }}</h3>
                <p class="text-sm text-gray-400 mt-1">{{ $t('a_no_active_doctors_msg') }}</p>
            </div>

            <!-- ========== DOCTOR QUEUE CARDS ========== -->
            <div v-else class="tq-cards-grid">
                <div
                    v-for="(doctor, dIdx) in filteredDoctors"
                    :key="doctor.id"
                    class="tq-card"
                    :style="{ transitionDelay: (dIdx * 0.08) + 's' }"
                >
                    <!-- Doctor Card Header -->
                    <div class="tq-card-header">
                        <div class="tq-card-header-bg"></div>
                        <div class="tq-card-header-content">
                            <div class="tq-doctor-info">
                                <div class="tq-doctor-avatar">
                                    <span>{{ ($localized(doctor, 'name'))?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <h3 class="tq-doctor-name">Dr. {{ $localized(doctor, 'name') }}</h3>
                                    <p class="tq-doctor-spec">{{ doctor.specialization || 'Dermatologist' }}</p>
                                </div>
                            </div>
                            <div class="tq-stats-badges">
                                <span class="tq-badge tq-badge--waiting">
                                    <span class="tq-badge-dot tq-badge-dot--waiting"></span>
                                    {{ getQueueStats(doctor.id).waiting }}
                                </span>
                                <span class="tq-badge tq-badge--active">
                                    <span class="tq-badge-dot tq-badge-dot--active"></span>
                                    {{ getQueueStats(doctor.id).in_progress }}
                                </span>
                                <span class="tq-badge tq-badge--done">
                                    <span class="tq-badge-dot tq-badge-dot--done"></span>
                                    {{ getQueueStats(doctor.id).completed }}
                                </span>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="tq-progress-track">
                            <div class="tq-progress-fill" :style="{ width: getDoctorProgress(doctor.id) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Visit Items -->
                    <div class="tq-visit-list">
                        <TransitionGroup name="visit-list">
                            <div
                                v-for="(visit, vIdx) in getDoctorVisits(doctor.id)"
                                :key="visit.id"
                                class="tq-visit"
                                :class="[
                                    'tq-visit--' + visit.status,
                                    visit.status === 'waiting' ? 'tq-visit--wait-' + getWaitLevel(getWaitMinutes(visit)) : ''
                                ]"
                            >
                                <!-- Status indicator line -->
                                <div class="tq-visit-indicator" :class="'tq-visit-indicator--' + visit.status"></div>

                                <div class="tq-visit-body">
                                    <!-- Top row: Patient + Wait time -->
                                    <div class="tq-visit-top">
                                        <div class="tq-visit-patient">
                                            <div class="tq-visit-avatar" :class="'tq-visit-avatar--' + visit.status">
                                                {{ visit.patient?.full_name?.charAt(0) || '?' }}
                                            </div>
                                            <div class="tq-visit-patient-info">
                                                <Link :href="`/admin/visits/${visit.id}`" class="tq-visit-name">
                                                    {{ visit.patient?.full_name }}
                                                </Link>
                                                <div class="tq-visit-meta">
                                                    <span v-if="visit.module === 'dental'" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-slate-50 text-[#1B365D] border border-slate-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg> {{ $t('a_dental') }}</span>
                                                    <span v-else class="inline-flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-amber-50 text-[#C4A265] border border-amber-200">{{ $t('a_derma') }}</span>
                                                    <span class="tq-visit-type">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="visitTypeIcons[visit.visit_type] || visitTypeIcons.consultation" />
                                                        </svg>
                                                        {{ {consultation: $t('a_consultation'), session: $t('a_session'), follow_up: $t('a_follow_up')}[visit.visit_type] || visit.visit_type }}
                                                    </span>
                                                    <span v-if="getDentalDescription(visit)" class="text-[10px] font-medium text-[#1B365D]">{{ getDentalDescription(visit) }}</span>
                                                    <span v-if="visit.service" class="tq-visit-service">{{ $localized(visit.service, 'name') }}</span>
                                                    <span v-if="visit.session_number" class="tq-visit-session">#{{ visit.session_number }}</span>
                                                    <span v-if="visit.package_bundle_booking_id" class="tq-visit-bundle-badge" :title="$t('a_package_bundle')">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                                        {{ $t('a_bundle') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status / Wait badge -->
                                        <div class="tq-visit-status-area">
                                            <div
                                                v-if="visit.status === 'waiting' && getWaitMinutes(visit) !== null"
                                                class="tq-wait-badge"
                                                :class="'tq-wait-badge--' + getWaitLevel(getWaitMinutes(visit))"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ formatWait(getWaitMinutes(visit)) }}
                                            </div>
                                            <div v-else-if="visit.status === 'in_progress'" class="tq-status-chip tq-status-chip--active">
                                                <span class="tq-status-chip-dot"></span>
                                                {{ $t('a_in_progress') }}
                                            </div>
                                            <div v-else-if="visit.status === 'completed'" class="tq-status-chip tq-status-chip--completed">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {{ $t('a_done') }}
                                            </div>
                                            <div v-else-if="visit.status === 'cancelled'" class="tq-status-chip tq-status-chip--cancelled">
                                                {{ $t('a_cancelled') }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bottom row: Time + Actions -->
                                    <div class="tq-visit-bottom">
                                        <div class="tq-visit-time">
                                            <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ formatTime(visit.created_at) }}
                                            <span v-if="visit.started_at" class="tq-visit-time-sep">
                                                <svg class="w-3 h-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                                {{ $t('a_started') }} {{ formatTime(visit.started_at) }}
                                            </span>
                                            <span v-if="visit.completed_at" class="tq-visit-time-sep">
                                                <svg class="w-3 h-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                                {{ $t('a_finished') }} {{ formatTime(visit.completed_at) }}
                                            </span>
                                        </div>

                                        <div class="tq-visit-actions">
                                            <!-- Start -->
                                            <button
                                                v-if="visit.status === 'waiting' && can('visits.update')"
                                                @click="startVisit(visit.id)"
                                                class="tq-action tq-action--start"
                                                :title="$t('a_start_visit')"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>{{ $t('a_start') }}</span>
                                            </button>
                                            <!-- Complete -->
                                            <button
                                                v-if="visit.status === 'in_progress' && can('visits.update')"
                                                @click="completeVisit(visit.id)"
                                                class="tq-action tq-action--complete"
                                                :title="$t('a_complete_visit')"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>{{ $t('a_complete') }}</span>
                                            </button>
                                            <!-- Cancel -->
                                            <button
                                                v-if="(visit.status === 'waiting' || visit.status === 'in_progress') && can('visits.update')"
                                                @click="cancelVisit(visit.id)"
                                                class="tq-action tq-action--cancel"
                                                :title="$t('a_cancel_visit')"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <!-- View -->
                                            <Link
                                                :href="`/admin/visits/${visit.id}`"
                                                class="tq-action tq-action--view"
                                                :title="$t('a_view_details')"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TransitionGroup>

                        <!-- Empty state per doctor -->
                        <div v-if="getDoctorVisits(doctor.id).length === 0" class="tq-card-empty">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p>{{ $t('a_no_visits_today') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Confirmation Modal -->
        <ConfirmModal
            :show="confirmingCancel !== null"
            :title="$t('a_cancel_visit')"
            :message="$t('a_cancel_visit_confirm')"
            :confirm-text="$t('a_cancel_visit')"
            confirm-color="red"
            @confirm="confirmCancel"
            @cancel="confirmingCancel = null"
        />
    </AdminLayout>
</template>

<style scoped>
/* ============================================
   TODAY'S QUEUE - PREMIUM UI
   ============================================ */

/* --- Page & Animations --- */
.tq-page {
    --gold: #C4A265;
    --gold-light: #f5eedd;
    --gold-dark: #B3914F;
    opacity: 0;
    transform: translateY(12px);
    transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}
.tq-mounted { opacity: 1; transform: translateY(0); }

/* --- Header --- */
.tq-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.tq-title-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.tq-title-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(196, 162, 101, 0.3);
}
.tq-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.025em;
}
.tq-date {
    font-size: 0.8rem;
    color: #9ca3af;
    margin-top: 2px;
}
.tq-header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* --- Buttons --- */
.tq-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1);
    border: 1px solid transparent;
    cursor: pointer;
    white-space: nowrap;
}
.tq-btn:active { transform: scale(0.97); }
.tq-btn-auto {
    padding: 0.4rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 20px;
}
.tq-btn-auto--on {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
}
.tq-btn-auto--off {
    background: #f9fafb;
    color: #6b7280;
    border-color: #e5e7eb;
}
.tq-btn-refresh {
    background: #f9fafb;
    color: #6b7280;
    border-color: #e5e7eb;
    padding: 0.5rem;
}
.tq-btn-refresh:hover { background: #f3f4f6; color: #374151; }
.tq-btn-refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.tq-btn-outline {
    background: white;
    color: var(--gold);
    border-color: var(--gold);
}
.tq-btn-outline:hover {
    background: var(--gold-light);
}
.tq-btn-primary {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: white;
    box-shadow: 0 2px 8px rgba(196, 162, 101, 0.35);
}
.tq-btn-primary:hover {
    box-shadow: 0 4px 16px rgba(196, 162, 101, 0.45);
    transform: translateY(-1px);
}

/* Pulse dot */
.tq-pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.tq-pulse-dot--on {
    background: #10b981;
    animation: tqPulse 2s ease-in-out infinite;
}
.tq-pulse-dot--off { background: #9ca3af; }

@keyframes tqPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
}

/* Spin */
.tq-spin { animation: tqSpin 1s linear infinite; }
@keyframes tqSpin { to { transform: rotate(360deg); } }

/* --- Summary Stats --- */
.tq-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}
@media (max-width: 768px) {
    .tq-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
.tq-stat {
    background: white;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    border: 1px solid #f3f4f6;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.tq-mounted .tq-stat { opacity: 1; transform: translateY(0); }
.tq-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.tq-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.tq-stat--waiting .tq-stat-icon { background: #fef3c7; color: #d97706; }
.tq-stat--active .tq-stat-icon { background: #dbeafe; color: #2563eb; }
.tq-stat--completed .tq-stat-icon { background: #d1fae5; color: #059669; }
.tq-stat--total .tq-stat-icon { background: var(--gold-light); color: var(--gold); }
.tq-stat-info { display: flex; flex-direction: column; }
.tq-stat-number { font-size: 1.375rem; font-weight: 700; color: #111827; line-height: 1; }
.tq-stat-label { font-size: 0.75rem; color: #9ca3af; margin-top: 2px; }

/* --- Doctor Filter Pills --- */
.tq-doctor-filter {
    display: flex;
    gap: 0.375rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
    padding: 0.375rem;
    background: #f9fafb;
    border-radius: 12px;
    border: 1px solid #f3f4f6;
}
.tq-filter-pill {
    padding: 0.375rem 0.875rem;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #6b7280;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}
.tq-filter-pill:hover { color: #374151; background: white; }
.tq-filter-pill--active {
    background: white;
    color: var(--gold-dark);
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    font-weight: 600;
}

/* --- Empty State --- */
.tq-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
    border: 1px solid #f3f4f6;
}
.tq-empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto;
    border-radius: 16px;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d1d5db;
}

/* --- Cards Grid --- */
.tq-cards-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 1.25rem;
}
@media (min-width: 1280px) {
    .tq-cards-grid { grid-template-columns: repeat(2, 1fr); }
}

/* --- Card --- */
.tq-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #f0f0f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    opacity: 0;
    transform: translateY(16px);
    transition: all 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}
.tq-mounted .tq-card { opacity: 1; transform: translateY(0); }
.tq-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}

/* --- Card Header --- */
.tq-card-header {
    position: relative;
    overflow: hidden;
}
.tq-card-header-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(196,162,101,0.06), rgba(196,162,101,0.02));
}
.tq-card-header-content {
    position: relative;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.tq-doctor-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.tq-doctor-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(196, 162, 101, 0.25);
}
.tq-doctor-name {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
}
.tq-doctor-spec {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 1px;
}
.tq-stats-badges {
    display: flex;
    gap: 0.375rem;
}
.tq-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.25rem 0.625rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}
.tq-badge--waiting { background: #fef9ee; color: #b45309; }
.tq-badge--active { background: #eff6ff; color: #1d4ed8; }
.tq-badge--done { background: #ecfdf5; color: #047857; }
.tq-badge-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
}
.tq-badge-dot--waiting { background: #f59e0b; }
.tq-badge-dot--active { background: #3b82f6; animation: tqPulse2 1.5s ease-in-out infinite; }
.tq-badge-dot--done { background: #10b981; }

@keyframes tqPulse2 {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* Progress bar */
.tq-progress-track {
    height: 3px;
    background: #f3f4f6;
    position: relative;
}
.tq-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--gold), #d4b87a);
    border-radius: 0 3px 3px 0;
    transition: width 0.8s cubic-bezier(0.22, 1, 0.36, 1);
    position: relative;
}
.tq-progress-fill::after {
    content: '';
    position: absolute;
    right: 0;
    top: -1px;
    width: 8px;
    height: 5px;
    border-radius: 3px;
    background: var(--gold);
    box-shadow: 0 0 6px rgba(196, 162, 101, 0.5);
}

/* --- Visit List --- */
.tq-visit-list {
    padding: 0.25rem 0;
}

/* TransitionGroup animations */
.visit-list-enter-active { transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1); }
.visit-list-leave-active { transition: all 0.3s ease-in; }
.visit-list-enter-from { opacity: 0; transform: translateX(-16px); }
.visit-list-leave-to { opacity: 0; transform: translateX(16px); }
.visit-list-move { transition: transform 0.4s ease; }

/* --- Visit Item --- */
.tq-visit {
    display: flex;
    padding: 0 1.5rem;
    transition: all 0.25s ease;
    position: relative;
}
.tq-visit:hover {
    background: #fafafa;
}
.tq-visit--completed { opacity: 0.6; }
.tq-visit--completed:hover { opacity: 0.85; }
.tq-visit--cancelled { opacity: 0.4; }

/* Indicator line */
.tq-visit-indicator {
    width: 3px;
    border-radius: 3px;
    flex-shrink: 0;
    margin: 0.75rem 0;
    transition: all 0.3s ease;
}
.tq-visit-indicator--waiting { background: #f59e0b; }
.tq-visit-indicator--in_progress {
    background: #3b82f6;
    animation: indicatorPulse 2s ease-in-out infinite;
}
.tq-visit-indicator--completed { background: #10b981; }
.tq-visit-indicator--cancelled { background: #d1d5db; }

@keyframes indicatorPulse {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.3); }
    50% { opacity: 0.7; box-shadow: 0 0 8px 2px rgba(59, 130, 246, 0.15); }
}

/* Visit body */
.tq-visit-body {
    flex: 1;
    padding: 0.875rem 0 0.875rem 1rem;
    border-bottom: 1px solid #f5f5f5;
    min-width: 0;
}
.tq-visit:last-child .tq-visit-body { border-bottom: none; }

/* Visit top row */
.tq-visit-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}
.tq-visit-patient {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    min-width: 0;
}
.tq-visit-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.tq-visit-avatar--waiting { background: #fef3c7; color: #b45309; }
.tq-visit-avatar--in_progress { background: #dbeafe; color: #1d4ed8; }
.tq-visit-avatar--completed { background: #d1fae5; color: #059669; }
.tq-visit-avatar--cancelled { background: #f3f4f6; color: #9ca3af; }

.tq-visit-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    text-decoration: none;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    transition: color 0.15s ease;
}
.tq-visit-name:hover { color: var(--gold); }

.tq-visit-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2px;
    flex-wrap: wrap;
}
.tq-visit-type {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 0.7rem;
    color: #6b7280;
}
.tq-visit-service {
    font-size: 0.7rem;
    color: #9ca3af;
    padding-left: 0.5rem;
    border-left: 1px solid #e5e7eb;
}
.tq-visit-session {
    font-size: 0.65rem;
    color: var(--gold);
    font-weight: 600;
    background: var(--gold-light);
    padding: 1px 6px;
    border-radius: 4px;
}

.tq-visit-bundle-badge {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-size: 0.6rem;
    font-weight: 600;
    color: #7c3aed;
    background: #ede9fe;
    padding: 1px 6px;
    border-radius: 4px;
}

/* Status area */
.tq-visit-status-area {
    flex-shrink: 0;
}

/* Wait badge */
.tq-wait-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.25rem 0.625rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    animation: fadeInRight 0.3s ease;
}
.tq-wait-badge--good { background: #ecfdf5; color: #059669; }
.tq-wait-badge--warn { background: #fefce8; color: #ca8a04; }
.tq-wait-badge--critical {
    background: #fef2f2;
    color: #dc2626;
    animation: fadeInRight 0.3s ease, criticalPulse 2s ease-in-out infinite;
}

@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(8px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes criticalPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.15); }
    50% { box-shadow: 0 0 0 4px rgba(220, 38, 38, 0); }
}

/* Status chips */
.tq-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}
.tq-status-chip--active {
    background: #eff6ff;
    color: #1d4ed8;
}
.tq-status-chip-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #3b82f6;
    animation: tqPulse2 1.5s ease-in-out infinite;
}
.tq-status-chip--completed { background: #ecfdf5; color: #059669; }
.tq-status-chip--cancelled { background: #f3f4f6; color: #9ca3af; }

/* Visit bottom row */
.tq-visit-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 0.5rem;
    gap: 0.5rem;
}
.tq-visit-time {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    color: #9ca3af;
}
.tq-visit-time-sep {
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

/* Actions */
.tq-visit-actions {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    opacity: 0;
    transform: translateX(8px);
    transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}
.tq-visit:hover .tq-visit-actions {
    opacity: 1;
    transform: translateX(0);
}
/* Always show on touch devices */
@media (hover: none) {
    .tq-visit-actions { opacity: 1; transform: translateX(0); }
}

.tq-action {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.3rem 0.6rem;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1);
    text-decoration: none;
}
.tq-action:active { transform: scale(0.95); }

.tq-action--start {
    background: #eff6ff;
    color: #2563eb;
}
.tq-action--start:hover {
    background: #2563eb;
    color: white;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}
.tq-action--complete {
    background: #ecfdf5;
    color: #059669;
}
.tq-action--complete:hover {
    background: #059669;
    color: white;
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
}
.tq-action--cancel {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.3rem;
}
.tq-action--cancel:hover {
    background: #dc2626;
    color: white;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}
.tq-action--view {
    background: #f9fafb;
    color: var(--gold);
    padding: 0.3rem;
}
.tq-action--view:hover {
    background: var(--gold-light);
    color: var(--gold-dark);
}

/* Card empty */
.tq-card-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 2.5rem 1rem;
    color: #d1d5db;
    font-size: 0.8125rem;
}

/* --- Wait level backgrounds --- */
.tq-visit--wait-warn { background: rgba(254, 252, 232, 0.4); }
.tq-visit--wait-critical { background: rgba(254, 242, 242, 0.4); }
.tq-visit--wait-warn:hover { background: rgba(254, 252, 232, 0.7); }
.tq-visit--wait-critical:hover { background: rgba(254, 242, 242, 0.7); }
</style>
