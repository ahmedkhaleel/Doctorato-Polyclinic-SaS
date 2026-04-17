<script setup>
import { ref, computed } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const isDentalEnabled = computed(() => {
    const modules = page.props.modules || {};
    return modules.dental?.enabled || modules.dental?.is_core;
});

const props = defineProps({
    visitsByDoctor: Object,
    doctors: Array,
    medicalAlerts: Object, // { visit_id: [riskFlags] }
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
    low: { bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200' },
};

/* ── Status helpers ─────────────────────────────────────── */
const statusLabels = {
    waiting: 'Waiting',
    in_progress: 'In Progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const statusColors = {
    waiting: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    in_progress: 'bg-slate-100 text-[#1B365D] border-slate-200',
    completed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    cancelled: 'bg-red-100 text-red-800 border-red-200',
};

const statusDotColors = {
    waiting: 'bg-amber-500',
    in_progress: 'bg-[#1B365D]',
    completed: 'bg-emerald-500',
    cancelled: 'bg-red-500',
};

const visitTypeLabels = {
    consultation: isRtl.value ? 'استشارة' : 'Consultation',
    session: isRtl.value ? 'جلسة' : 'Session',
    follow_up: isRtl.value ? 'متابعة' : 'Follow Up',
};

const visitTypeBadgeColors = {
    consultation: 'bg-teal-100 text-teal-700',
    session: 'bg-slate-100 text-[#1B365D]',
    follow_up: 'bg-slate-100 text-[#1B365D]',
};

/* ── Module helpers ────────────────────────────────────── */
const dentalTreatmentTypeLabels = {
    filling: isRtl.value ? 'حشو' : 'Filling',
    extraction: isRtl.value ? 'خلع' : 'Extraction',
    root_canal: isRtl.value ? 'علاج عصب' : 'Root Canal',
    crown: isRtl.value ? 'تاج' : 'Crown',
    bridge: isRtl.value ? 'جسر' : 'Bridge',
    implant: isRtl.value ? 'زراعة' : 'Implant',
    cleaning: isRtl.value ? 'تنظيف' : 'Cleaning',
    scaling: isRtl.value ? 'تقليح' : 'Scaling',
    whitening: isRtl.value ? 'تبييض' : 'Whitening',
    orthodontic: isRtl.value ? 'تقويم' : 'Orthodontic',
    surgical_extraction: isRtl.value ? 'خلع جراحي' : 'Surgical Extraction',
};

function getDentalDescription(visit) {
    if (visit.module !== 'dental' || !visit.dental_treatments?.length) return '';
    const t = visit.dental_treatments[0];
    const label = dentalTreatmentTypeLabels[t.treatment_type] || t.treatment_type?.replace(/_/g, ' ');
    const tooth = t.tooth_number ? ` #${t.tooth_number}` : '';
    return `${label}${tooth}`;
}

/* ── Module filter ─────────────────────────────────────── */
const moduleFilter = ref('all'); // 'all' | 'dental' | 'derma'

function filterVisits(visits) {
    if (moduleFilter.value === 'all') return visits;
    return visits.filter(v => {
        if (moduleFilter.value === 'dental') return v.module === 'dental';
        return v.module !== 'dental'; // dermatology or null
    });
}

/* ── Doctor colors for avatars ──────────────────────────── */
const doctorGradients = [
    'from-teal-500 to-[#1B365D]',
    'from-[#1B365D] to-[#1B365D]',
    'from-teal-600 to-emerald-500',
    'from-[#1B365D] to-[#1B365D]',
    'from-[#1B365D] to-teal-400',
    'from-[#1B365D] to-slate-400',
];

function getDoctorGradient(index) {
    return doctorGradients[index % doctorGradients.length];
}

/* ── Helpers ────────────────────────────────────────────── */
function getDoctorName(doctorId) {
    const doctor = (props.doctors || []).find(d => d.id == doctorId);
    return doctor?.name_en || doctor?.name || `Doctor #${doctorId}`;
}

function getDoctorSpecialty(doctorId) {
    const doctor = (props.doctors || []).find(d => d.id == doctorId);
    return doctor?.specialty_en || doctor?.specialty || '';
}

function formatTime(date) {
    if (!date) return '';
    return new Date(date).toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

const todayFormatted = computed(() => {
    return new Date().toLocaleDateString('en-GB', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
});

const doctorIds = computed(() => Object.keys(props.visitsByDoctor || {}));

const filteredVisitsByDoctor = computed(() => {
    const result = {};
    for (const key of doctorIds.value) {
        const filtered = filterVisits(props.visitsByDoctor[key] || []);
        if (filtered.length > 0) result[key] = filtered;
    }
    return result;
});

const filteredDoctorIds = computed(() => Object.keys(filteredVisitsByDoctor.value));

const totalVisits = computed(() => {
    let count = 0;
    for (const key of filteredDoctorIds.value) {
        count += filteredVisitsByDoctor.value[key].length;
    }
    return count;
});

const waitingCount = computed(() => {
    let count = 0;
    for (const key of filteredDoctorIds.value) {
        count += filteredVisitsByDoctor.value[key].filter(v => v.status === 'waiting').length;
    }
    return count;
});

const inProgressCount = computed(() => {
    let count = 0;
    for (const key of filteredDoctorIds.value) {
        count += filteredVisitsByDoctor.value[key].filter(v => v.status === 'in_progress').length;
    }
    return count;
});

const completedCount = computed(() => {
    let count = 0;
    for (const key of filteredDoctorIds.value) {
        count += filteredVisitsByDoctor.value[key].filter(v => v.status === 'completed').length;
    }
    return count;
});

// Module counts for filter badges
const dentalCount = computed(() => {
    let count = 0;
    for (const key of doctorIds.value) {
        count += (props.visitsByDoctor[key] || []).filter(v => v.module === 'dental').length;
    }
    return count;
});

const dermaCount = computed(() => {
    let count = 0;
    for (const key of doctorIds.value) {
        count += (props.visitsByDoctor[key] || []).filter(v => v.module !== 'dental').length;
    }
    return count;
});

/* ── Actions ────────────────────────────────────────────── */
function startVisit(visitId) {
    router.post(`/secretary/visits/${visitId}/start`, {}, {
        preserveScroll: true,
    });
}

function completeVisit(visitId) {
    router.post(`/secretary/visits/${visitId}/complete`, {}, {
        preserveScroll: true,
    });
}

function refreshQueue() {
    router.reload({ preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'طابور اليوم' : "Today's Queue" }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ todayFormatted }}</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <button
                    @click="refreshQueue"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"
                    :title="isRtl ? 'تحديث الطابور' : 'Refresh queue'"
                >
                    <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ isRtl ? 'تحديث' : 'Refresh' }}
                </button>
                <Link
                    href="/secretary/bookings/create"
                    class="inline-flex items-center px-5 py-2.5 rounded-lg text-white text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg"
                    style="background: linear-gradient(135deg, #0d9488, #06b6d4);"
                >
                    <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ isRtl ? 'حجز جديد' : 'New Booking' }}
                </Link>
            </div>
        </div>

        <!-- Module Filter (only show if both modules are enabled) -->
        <div v-if="isDentalEnabled" class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-medium text-gray-500 ltr:mr-1 rtl:ml-1">{{ isRtl ? 'القسم:' : 'Module:' }}</span>
            <button
                @click="moduleFilter = 'all'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all duration-200"
                :class="moduleFilter === 'all' ? 'bg-gray-800 text-white border-gray-800 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
            >
                {{ isRtl ? 'الكل' : 'All' }}
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold"
                    :class="moduleFilter === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'">
                    {{ dentalCount + dermaCount }}
                </span>
            </button>
            <button
                @click="moduleFilter = 'dental'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all duration-200"
                :class="moduleFilter === 'dental' ? 'bg-[#1B365D] text-white border-[#1B365D] shadow-sm' : 'bg-white text-[#1B365D] border-slate-200 hover:bg-slate-50'"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                {{ isRtl ? 'أسنان' : 'Dental' }}
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold"
                    :class="moduleFilter === 'dental' ? 'bg-white/20 text-white' : 'bg-slate-50 text-[#1B365D]'">
                    {{ dentalCount }}
                </span>
            </button>
            <button
                @click="moduleFilter = 'derma'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all duration-200"
                :class="moduleFilter === 'derma' ? 'bg-[#C4A265] text-white border-[#C4A265] shadow-sm' : 'bg-white text-[#C4A265] border-amber-200 hover:bg-amber-50'"
            >
                {{ isRtl ? 'جلدية' : 'Derma' }}
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold"
                    :class="moduleFilter === 'derma' ? 'bg-white/20 text-white' : 'bg-amber-50 text-[#C4A265]'">
                    {{ dermaCount }}
                </span>
            </button>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-500 to-[#1B365D] flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800">{{ totalVisits }}</p>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-amber-600">{{ waitingCount }}</p>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'انتظار' : 'Waiting' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-[#1B365D]">{{ inProgressCount }}</p>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'جاري' : 'In Progress' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-emerald-600">{{ completedCount }}</p>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctor Queues -->
        <div v-if="filteredDoctorIds.length > 0" class="space-y-4 sm:space-y-6">
            <div v-for="(doctorId, dIdx) in filteredDoctorIds" :key="doctorId">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Doctor Header -->
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-teal-50 to-slate-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div :class="'bg-gradient-to-br ' + getDoctorGradient(dIdx)" class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                    {{ getDoctorName(doctorId).charAt(0) }}
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-gray-800">{{ getDoctorName(doctorId) }}</h2>
                                    <p v-if="getDoctorSpecialty(doctorId)" class="text-xs text-gray-500">{{ getDoctorSpecialty(doctorId) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-teal-600 bg-teal-100 px-2.5 py-1 rounded-full">
                                    {{ filteredVisitsByDoctor[doctorId].length }} visits
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Visit Cards -->
                    <div class="divide-y divide-gray-50">
                        <div
                            v-for="(visit, vIdx) in filteredVisitsByDoctor[doctorId]"
                            :key="visit.id"
                            class="px-4 sm:px-6 py-4 hover:bg-teal-50/20 transition-colors"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <!-- Left: Queue number + Patient info -->
                                <div class="flex items-center gap-4 min-w-0 flex-1">
                                    <!-- Queue number -->
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                        :class="hasAlert(visit.id) ? 'bg-red-100 ring-2 ring-red-300' : 'bg-gray-100'">
                                        <span class="text-xs font-bold" :class="hasAlert(visit.id) ? 'text-red-600' : 'text-gray-500'">{{ vIdx + 1 }}</span>
                                    </div>

                                    <!-- Patient info -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <Link
                                                :href="`/secretary/visits/${visit.id}`"
                                                class="text-sm font-medium text-gray-900 hover:text-teal-600 transition truncate"
                                            >
                                                {{ visit.patient?.full_name || '-' }}
                                            </Link>
                                            <!-- Medical Risk Alert Badge -->
                                            <button
                                                v-if="hasAlert(visit.id)"
                                                @click.prevent="toggleAlert(visit.id)"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200 hover:bg-red-200 transition animate-pulse cursor-pointer"
                                                :title="isRtl ? 'تنبيه طبي - انقر للتفاصيل' : 'Medical Alert - Click for details'"
                                            >
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                {{ highRiskCount(visit.id) }} {{ isRtl ? 'خطر' : 'risk' }}
                                            </button>
                                            <!-- Visit Type Badge -->
                                            <span
                                                :class="visitTypeBadgeColors[visit.visit_type] || 'bg-gray-100 text-gray-600'"
                                                class="px-2 py-0.5 text-[10px] font-semibold rounded-full flex-shrink-0"
                                            >
                                                {{ visitTypeLabels[visit.visit_type] || visit.visit_type }}
                                            </span>
                                            <!-- Module Badge -->
                                            <span v-if="visit.module === 'dental'" class="px-2 py-0.5 text-[10px] font-semibold rounded-full flex-shrink-0 bg-slate-50 text-[#1B365D] border border-slate-200 inline-flex items-center gap-0.5">
                                                <svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg> {{ isRtl ? 'أسنان' : 'Dental' }}
                                            </span>
                                            <span v-else class="px-2 py-0.5 text-[10px] font-semibold rounded-full flex-shrink-0 bg-amber-50 text-[#C4A265] border border-amber-200 inline-flex items-center gap-0.5">
                                                {{ isRtl ? 'جلدية' : 'Derma' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 mt-0.5">
                                            <span class="text-xs text-gray-400">{{ visit.service?.name_en || visit.service?.name || '-' }}</span>
                                            <!-- Dental treatment description -->
                                            <span v-if="getDentalDescription(visit)" class="text-xs text-[#1B365D] font-medium">
                                                {{ getDentalDescription(visit) }}
                                            </span>
                                            <span v-if="visit.visit_date || visit.created_at" class="text-xs text-gray-300">
                                                {{ formatTime(visit.visit_date || visit.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Medical Alert Details (expandable) -->
                                <Transition name="slide">
                                    <div v-if="hasAlert(visit.id) && expandedAlerts[visit.id]"
                                        class="w-full mt-2 p-3 rounded-xl bg-red-50 border border-red-200">
                                        <p class="text-[11px] font-bold text-red-800 mb-2">
                                            <svg class="w-3.5 h-3.5 inline -mt-0.5 ltr:mr-1 rtl:ml-1" fill="currentColor" viewBox="0 0 20 20">
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
                                </Transition>

                                <!-- Right: Status + Actions -->
                                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0 flex-wrap">
                                    <!-- Status Badge -->
                                    <span
                                        :class="statusColors[visit.status]"
                                        class="px-2.5 py-1 text-[10px] font-semibold rounded-full border inline-flex items-center gap-1"
                                    >
                                        <span :class="statusDotColors[visit.status]" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ statusLabels[visit.status] || visit.status }}
                                    </span>

                                    <!-- Quick Action Buttons -->
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            v-if="visit.status === 'waiting'"
                                            @click="startVisit(visit.id)"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 border border-slate-200 transition"
                                            :title="isRtl ? 'بدء الزيارة' : 'Start visit'"
                                        >
                                            <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            </svg>
                                            {{ isRtl ? 'بدء' : 'Start' }}
                                        </button>

                                        <button
                                            v-if="visit.status === 'in_progress'"
                                            @click="completeVisit(visit.id)"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition"
                                            :title="isRtl ? 'إنهاء الزيارة' : 'Complete visit'"
                                        >
                                            <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            {{ isRtl ? 'إتمام' : 'Complete' }}
                                        </button>

                                        <!-- View link -->
                                        <Link
                                            :href="`/secretary/visits/${visit.id}`"
                                            class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition"
                                            :title="isRtl ? 'عرض التفاصيل' : 'View details'"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 p-16 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-teal-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">{{ isRtl ? 'لا زيارات اليوم' : 'No Visits Today' }}</h3>
            <p class="text-sm text-gray-500 mb-6">{{ isRtl ? 'لا توجد زيارات مجدولة لليوم حتى الآن.' : 'There are no visits scheduled for today yet.' }}</p>
            <Link
                href="/secretary/bookings/create"
                class="inline-flex items-center px-5 py-2.5 rounded-lg text-white text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg"
                style="background: linear-gradient(135deg, #0d9488, #06b6d4);"
            >
                <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ isRtl ? 'حجز جديد' : 'New Booking' }}
            </Link>
        </div>
    </div>
</template>
