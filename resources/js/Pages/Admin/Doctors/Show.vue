<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
import BarChart from '@/Components/Admin/BarChart.vue';
import DonutChart from '@/Components/Admin/DonutChart.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { sanitizeHtml } from '@/Composables/useSanitize';
import { useLocale } from '@/Composables/useLocale.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { t } = useLocale();
const { confirm } = useConfirm();

const props = defineProps({
    doctor: Object,
    performanceStats: Object,
    visits: Array,
    todayQueue: Array,
    patients: Array,
    prescriptions: Array,
    monthlyCommission: Array,
    monthlyRevenue: Array,
    leaves: Array,
    bookings: Array,
    commissionStatement: Array,
    allPatients: Array,
    payoutSummary: Object,
    recentPayouts: Array,
    attendanceRecords: Array,
    attendanceSummary: Object,
});

const activeTab = ref('overview');
const mounted = ref(false);
const tabTransition = ref(false);

const tabs = [
    { id: 'overview', label: 'Overview', key: 'a_overview', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { id: 'visits', label: 'Visits & Queue', key: 'a_visits_queue', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
    { id: 'patients', label: 'Patients', key: 'a_patients', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { id: 'prescriptions', label: 'Prescriptions', key: 'a_prescriptions', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { id: 'finance', label: 'Commission & Finance', key: 'a_commission_finance', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'schedule', label: 'Schedule & Leaves', key: 'a_schedule_leaves', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'bookings', label: 'Bookings', key: 'a_bookings', icon: 'M16 4v12l-4-2-4 2V4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'attendance', label: 'Attendance', key: 'a_attendance', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
];

const dayNames = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const visitTypeLabels = {
    consultation: 'Consultation',
    session: 'Session',
    follow_up: 'Follow Up',
};

function switchTab(id) {
    tabTransition.value = true;
    setTimeout(() => {
        activeTab.value = id;
        nextTick(() => { tabTransition.value = false; });
    }, 150);
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

function formatTime(time) {
    if (!time) return '-';
    const parts = time.split(':');
    if (parts.length < 2) return time;
    const h = parseInt(parts[0]);
    const m = parts[1];
    const ampm = h >= 12 ? 'PM' : 'AM';
    return `${h % 12 || 12}:${m} ${ampm}`;
}

function getScheduleForDay(dayIndex) {
    return props.doctor?.schedules?.find(s => s.day_of_week === dayIndex);
}

const commissionChartData = computed(() =>
    (props.monthlyCommission || []).map(m => ({
        label: m.month?.slice(5) || '',
        value: Math.round(Number(m.commission || 0)),
    }))
);

// Prescription creation
const showNewPrescription = ref(false);
const prescriptionForm = useForm({
    patient_id: '',
    diagnosis: '',
    notes: '',
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
});

const patientSearch = ref('');
const filteredPatients = ref([]);
const showPatientDropdown = ref(false);

function searchPatients(query) {
    patientSearch.value = query;
    if (!query || query.length < 2) {
        filteredPatients.value = [];
        showPatientDropdown.value = false;
        return;
    }
    const q = query.toLowerCase();
    filteredPatients.value = (props.allPatients || [])
        .filter(p => p.full_name?.toLowerCase().includes(q) || p.phone?.includes(q) || p.file_number?.includes(q))
        .slice(0, 10);
    showPatientDropdown.value = filteredPatients.value.length > 0;
}

function selectPatient(patient) {
    prescriptionForm.patient_id = patient.id;
    patientSearch.value = patient.full_name;
    showPatientDropdown.value = false;
    filteredPatients.value = [];
}

const medicationSuggestions = ref([]);
const activeMedIdx = ref(-1);
let medSearchTimeout = null;

function searchMedications(query, index) {
    activeMedIdx.value = index;
    clearTimeout(medSearchTimeout);
    if (!query || query.length < 2) {
        medicationSuggestions.value = [];
        return;
    }
    medSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`/admin/api/medications?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            medicationSuggestions.value = data;
        } catch {
            medicationSuggestions.value = [];
        }
    }, 300);
}

function selectMedication(med, index) {
    prescriptionForm.items[index].medication_name = med.name;
    if (med.default_dosage) prescriptionForm.items[index].dosage = med.default_dosage;
    if (med.default_frequency) prescriptionForm.items[index].frequency = med.default_frequency;
    medicationSuggestions.value = [];
    activeMedIdx.value = -1;
}

function addPrescriptionItem() {
    prescriptionForm.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' });
}

function removePrescriptionItem(index) {
    if (prescriptionForm.items.length > 1) {
        prescriptionForm.items.splice(index, 1);
    }
}

function submitPrescription() {
    prescriptionForm
        .transform((data) => ({
            ...data,
            doctor_id: props.doctor.id,
            visit_id: null,
        }))
        .post('/admin/prescriptions', {
            preserveScroll: true,
            onSuccess: () => {
                prescriptionForm.reset();
                prescriptionForm.items = [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }];
                patientSearch.value = '';
                showNewPrescription.value = false;
            },
        });
}

function deletePrescription(rxId) {
    confirm(t('a_confirm_delete_prescription'), () => {
        router.post(`/admin/prescriptions/${rxId}/delete`, {
            preserveScroll: true,
        });
    });
}

const revenueChartData = computed(() =>
    (props.monthlyRevenue || []).map(m => ({
        label: m.month?.slice(5) || '',
        value: Math.round(Number(m.revenue || 0)),
    }))
);

const visitTypeDistribution = computed(() => {
    const s = props.performanceStats;
    if (!s) return [];
    return [
        { label: 'Consultations', value: s.consultations || 0, color: '#C4A265' },
        { label: 'Sessions', value: s.sessions || 0, color: '#3B82F6' },
    ].filter(d => d.value > 0);
});

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});
</script>

<template>
    <AdminLayout :title="`${$t('a_doctor')} ${$localized(doctor, 'name')}`">
        <div class="doctor-profile-page space-y-6">

            <!-- ======================== HERO HEADER ======================== -->
            <div class="relative overflow-hidden rounded-2xl shadow-xl hero-card">
                <!-- Animated Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
                    <div class="absolute inset-0 opacity-20">
                        <div class="hero-pattern"></div>
                    </div>
                    <div class="absolute top-0 right-0 w-96 h-96 bg-[#C4A265] opacity-10 rounded-full blur-3xl floating-orb"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#C4A265] opacity-5 rounded-full blur-2xl floating-orb-delayed"></div>
                </div>

                <div class="relative z-10 p-8">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                        <!-- Doctor Photo -->
                        <div class="relative group hero-avatar">
                            <div class="absolute -inset-1 bg-gradient-to-r from-[#C4A265] to-[#D4B275] rounded-full opacity-75 blur group-hover:opacity-100 transition duration-500"></div>
                            <img
                                v-if="doctor.photo_url"
                                :src="doctor.photo_url"
                                :alt="doctor.name_en"
                                class="relative w-28 h-28 rounded-full object-cover border-4 border-white/20 shadow-2xl ring-2 ring-[#C4A265]/50"
                            />
                            <div
                                v-else
                                class="relative w-28 h-28 rounded-full flex items-center justify-center text-white text-2xl md:text-4xl font-bold border-4 border-white/20 shadow-2xl bg-gradient-to-br from-[#C4A265] to-[#A08245]"
                            >
                                {{ $localized(doctor, 'name')?.charAt(0) }}
                            </div>
                            <!-- Online indicator -->
                            <div v-if="doctor.status === 'active'" class="absolute bottom-1 right-1 w-5 h-5 bg-emerald-400 rounded-full border-3 border-gray-900 pulse-dot"></div>
                        </div>

                        <!-- Doctor Info -->
                        <div class="flex-1 hero-info">
                            <div class="flex flex-wrap items-center gap-3 mb-1">
                                <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight">
                                    {{ $t('a_doctor') }} {{ $localized(doctor, 'name') }}
                                </h1>
                                <StatusBadge :status="doctor.status" />
                            </div>
                            <p class="text-lg text-gray-300 font-light" :dir="isRtl ? 'ltr' : 'rtl'">
                                {{ locale === 'ar' ? doctor.name_en : doctor.name_ar }}
                            </p>
                            <p class="text-[#C4A265] font-medium mt-1 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                {{ $localized(doctor, 'specialization') }}
                            </p>

                            <!-- Quick Stats Badges -->
                            <div class="flex flex-wrap gap-3 mt-4">
                                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2 text-sm text-white/90 border border-white/10">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span class="font-semibold text-[#C4A265]">{{ performanceStats?.total_visits || 0 }}</span> {{ $t('a_visits') }}
                                </div>
                                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2 text-sm text-white/90 border border-white/10">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="font-semibold text-emerald-400">{{ formatCurrency(performanceStats?.total_revenue || 0) }}</span>
                                </div>
                                <div v-if="doctor.consultation_fee" class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-2 text-sm text-white/90 border border-white/10">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                    {{ $t('a_fee') }}: <span class="font-semibold text-slate-400">{{ formatCurrency(doctor.consultation_fee) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Button -->
                        <Link
                            v-if="can('doctors.update')"
                            :href="`/admin/doctors/${doctor.id}/edit`"
                            class="group inline-flex items-center gap-2 px-4 md:px-6 py-3 rounded-xl text-gray-900 text-sm font-semibold transition-all duration-300 bg-gradient-to-r from-[#C4A265] to-[#D4B275] hover:from-[#D4B275] hover:to-[#E4C285] shadow-lg shadow-[#C4A265]/20 hover:shadow-[#C4A265]/40 hover:scale-105"
                        >
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            {{ $t('a_edit_doctor') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ======================== TAB NAVIGATION ======================== -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <nav class="flex border-b border-gray-100 overflow-x-auto scrollbar-hide px-2 pt-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="switchTab(tab.id)"
                        class="relative flex items-center gap-2 px-5 py-3.5 text-sm font-medium transition-all duration-300 whitespace-nowrap rounded-t-xl mx-0.5"
                        :class="activeTab === tab.id
                            ? 'text-[#C4A265] bg-gradient-to-b from-[#C4A265]/5 to-transparent'
                            : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                        </svg>
                        <span>{{ tab.key ? $t(tab.key) : tab.label }}</span>
                        <!-- Active indicator -->
                        <div v-if="activeTab === tab.id"
                            class="absolute bottom-0 left-2 right-2 h-0.5 bg-gradient-to-r from-[#C4A265] to-[#D4B275] rounded-full tab-indicator">
                        </div>
                    </button>
                </nav>

                <!-- Tab Content -->
                <div class="p-6" :class="{ 'tab-fade-out': tabTransition, 'tab-fade-in': !tabTransition }">

                    <!-- ==================== OVERVIEW TAB ==================== -->
                    <div v-if="activeTab === 'overview'" class="space-y-8">
                        <!-- KPI Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="kpi-card group relative overflow-hidden bg-gradient-to-br from-white to-gray-50 rounded-2xl p-5 border border-gray-100 hover:border-[#C4A265]/30 hover:shadow-lg hover:shadow-[#C4A265]/5 transition-all duration-500" style="animation-delay: 0ms">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-[#C4A265]/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_total_visits') }}</p>
                                    </div>
                                    <p class="text-2xl md:text-3xl font-bold text-gray-800 counter-number">{{ performanceStats.total_visits }}</p>
                                </div>
                            </div>

                            <div class="kpi-card group relative overflow-hidden bg-gradient-to-br from-white to-emerald-50/30 rounded-2xl p-5 border border-gray-100 hover:border-emerald-200 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-500" style="animation-delay: 80ms">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_completed') }}</p>
                                    </div>
                                    <p class="text-2xl md:text-3xl font-bold text-emerald-700 counter-number">{{ performanceStats.completed_visits }}</p>
                                </div>
                            </div>

                            <div class="kpi-card group relative overflow-hidden bg-gradient-to-br from-white to-[#C4A265]/5 rounded-2xl p-5 border border-gray-100 hover:border-[#C4A265]/30 hover:shadow-lg hover:shadow-[#C4A265]/5 transition-all duration-500" style="animation-delay: 160ms">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-[#C4A265]/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_consultations') }}</p>
                                    </div>
                                    <p class="text-2xl md:text-3xl font-bold text-[#C4A265] counter-number">{{ performanceStats.consultations }}</p>
                                </div>
                            </div>

                            <div class="kpi-card group relative overflow-hidden bg-gradient-to-br from-white to-slate-50/30 rounded-2xl p-5 border border-gray-100 hover:border-slate-200 hover:shadow-lg hover:shadow-[#1B365D]/5 transition-all duration-500" style="animation-delay: 240ms">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-[#1B365D]/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_sessions') }}</p>
                                    </div>
                                    <p class="text-2xl md:text-3xl font-bold text-[#1B365D] counter-number">{{ performanceStats.sessions }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue Cards Row -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="kpi-card group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:scale-[1.02] transition-all duration-500" style="animation-delay: 320ms">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                                <p class="text-xs font-semibold text-emerald-100 uppercase tracking-wider">{{ $t('a_total_revenue') }}</p>
                                <p class="text-xl md:text-2xl font-bold mt-2">{{ formatCurrency(performanceStats.total_revenue) }}</p>
                            </div>
                            <div class="kpi-card group relative overflow-hidden bg-gradient-to-br from-[#C4A265] to-[#A08245] rounded-2xl p-5 text-white shadow-lg shadow-[#C4A265]/20 hover:shadow-[#C4A265]/30 hover:scale-[1.02] transition-all duration-500" style="animation-delay: 400ms">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                                <p class="text-xs font-semibold text-[#E4D2A5] uppercase tracking-wider">{{ $t('a_total_commission') }}</p>
                                <p class="text-xl md:text-2xl font-bold mt-2">{{ formatCurrency(performanceStats.total_commission) }}</p>
                            </div>
                            <div class="kpi-card group relative overflow-hidden bg-gradient-to-br from-[#1B365D] to-[#1B365D] rounded-2xl p-5 text-white shadow-lg shadow-[#1B365D]/20 hover:shadow-[#1B365D]/30 hover:scale-[1.02] transition-all duration-500" style="animation-delay: 480ms">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                                <p class="text-xs font-semibold text-slate-100 uppercase tracking-wider">{{ $t('a_this_month_revenue') }}</p>
                                <p class="text-xl md:text-2xl font-bold mt-2">{{ formatCurrency(performanceStats.this_month_revenue) }}</p>
                            </div>
                            <div class="kpi-card group relative overflow-hidden bg-gradient-to-br from-[#1B365D] to-[#1B365D] rounded-2xl p-5 text-white shadow-lg shadow-[#1B365D]/20 hover:shadow-[#1B365D]/30 hover:scale-[1.02] transition-all duration-500" style="animation-delay: 560ms">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                                <p class="text-xs font-semibold text-slate-100 uppercase tracking-wider">{{ $t('a_this_month_commission') }}</p>
                                <p class="text-xl md:text-2xl font-bold mt-2">{{ formatCurrency(performanceStats.this_month_commission) }}</p>
                            </div>
                        </div>

                        <!-- Donut + Personal Info -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Donut Chart Card -->
                            <div v-if="visitTypeDistribution.length > 0" class="bg-white rounded-2xl p-4 md:p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300 flex flex-col items-center justify-center">
                                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4 self-start">{{ $t('a_type') }} {{ $t('a_visits') }}</h3>
                                <DonutChart :data="visitTypeDistribution" :size="140" />
                            </div>

                            <!-- Personal Information -->
                            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
                                <div class="px-4 md:px-6 py-4 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                                    <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </div>
                                        {{ $t('a_personal_information') }}
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                            <div class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_phone') }}</p>
                                                <p class="text-sm font-medium text-gray-800">{{ doctor.phone || '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                            <div class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_email') }}</p>
                                                <p class="text-sm font-medium text-gray-800">{{ doctor.email || '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                            <div class="w-9 h-9 rounded-lg bg-[#C4A265]/10 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_specialization_en') }}</p>
                                                <p class="text-sm font-medium text-gray-800">{{ doctor.specialization_en || '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                            <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_default_commission') }}</p>
                                                <p class="text-sm font-bold text-[#C4A265]">{{ doctor.default_commission_percentage || 0 }}%</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Qualifications -->
                                    <div v-if="doctor.qualifications_en" class="mt-5 pt-4 border-t border-gray-100">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ $t('a_qualifications') }}</p>
                                        <p class="text-sm text-gray-700 leading-relaxed">{{ doctor.qualifications_en }}</p>
                                    </div>
                                    <!-- Bio -->
                                    <div v-if="doctor.bio_en" class="mt-4 pt-4 border-t border-gray-100">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ $t('a_bio') }}</p>
                                        <div class="text-sm text-gray-700 prose prose-sm max-w-none leading-relaxed" v-html="sanitizeHtml(doctor.bio_en)"></div>
                                    </div>
                                    <!-- Clinic Notes -->
                                    <div v-if="doctor.clinic_notes" class="mt-4 p-4 bg-[#F5E7C8]/30 rounded-xl border border-amber-100">
                                        <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-2">{{ $t('a_clinic_notes') }}</p>
                                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ doctor.clinic_notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== VISITS & QUEUE TAB ==================== -->
                    <div v-if="activeTab === 'visits'" class="space-y-6">
                        <!-- Today's Queue -->
                        <div v-if="todayQueue && todayQueue.length > 0">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_todays_queue') }}</h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div v-for="visit in todayQueue" :key="visit.id"
                                    class="group bg-white rounded-xl p-4 border border-gray-100 hover:border-[#C4A265]/30 hover:shadow-md transition-all duration-300">
                                    <div class="flex items-center justify-between mb-2">
                                        <StatusBadge :status="visit.status" type="visit" />
                                        <Link :href="`/admin/visits/${visit.id}`" class="text-xs font-semibold text-[#C4A265] hover:underline opacity-0 group-hover:opacity-100 transition-opacity">{{ $t('a_view') }} &rarr;</Link>
                                    </div>
                                    <Link :href="`/admin/visits/${visit.id}`" class="text-sm font-semibold text-gray-800 hover:text-[#C4A265] transition-colors">
                                        {{ visit.patient?.full_name }}
                                    </Link>
                                    <p class="text-xs text-gray-400 mt-1">{{ visit.service ? $localized(visit.service, 'name') : '-' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Visits Table -->
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                            <div class="px-4 md:px-6 py-4 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_all_visits') }}</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50/50">
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_date') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_patient') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_type') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_service') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_status') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_commission') }}</th>
                                            <th class="px-5 py-3.5"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="visit in visits" :key="visit.id" class="hover:bg-[#C4A265]/[0.02] transition-colors duration-200">
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ formatDate(visit.visit_date) }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap">
                                                <Link v-if="can('patients.view')" :href="`/admin/patients/${visit.patient?.id}`" class="text-sm font-semibold text-[#C4A265] hover:underline">{{ visit.patient?.full_name }}</Link>
                                                <span v-else class="text-sm text-gray-900">{{ visit.patient?.full_name }}</span>
                                            </td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ visitTypeLabels[visit.visit_type] || visit.visit_type }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ visit.service ? $localized(visit.service, 'name') : '-' }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap"><StatusBadge :status="visit.status" type="visit" /></td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-bold text-[#C4A265]">{{ visit.commission_amount ? formatCurrency(visit.commission_amount) : '-' }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap ltr:text-right rtl:text-left">
                                                <Link :href="`/admin/visits/${visit.id}`" class="text-xs font-semibold text-[#C4A265] hover:underline">{{ $t('a_view') }}</Link>
                                            </td>
                                        </tr>
                                        <tr v-if="!visits || visits.length === 0">
                                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">{{ $t('a_no_visits_found_dot') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== PATIENTS TAB ==================== -->
                    <div v-if="activeTab === 'patients'">
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50/50">
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_patient') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_phone') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_file_number') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_visits') }}</th>
                                            <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_last_visit') }}</th>
                                            <th class="px-5 py-3.5"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="patient in patients" :key="patient.id" class="hover:bg-[#C4A265]/[0.02] transition-colors duration-200">
                                            <td class="px-5 py-3.5 whitespace-nowrap">
                                                <Link v-if="can('patients.view')" :href="`/admin/patients/${patient.id}`" class="text-sm font-semibold text-[#C4A265] hover:underline">{{ patient.full_name }}</Link>
                                                <span v-else class="text-sm font-medium text-gray-900">{{ patient.full_name }}</span>
                                            </td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ patient.phone || '-' }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500 font-mono">{{ patient.file_number || '-' }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#C4A265]/10 text-sm font-bold text-[#C4A265]">{{ patient.visit_count }}</span>
                                            </td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ formatDate(patient.last_visit_date) }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap ltr:text-right rtl:text-left">
                                                <Link v-if="can('patients.view')" :href="`/admin/patients/${patient.id}`" class="text-xs font-semibold text-[#C4A265] hover:underline">{{ $t('a_view_profile_link') }}</Link>
                                            </td>
                                        </tr>
                                        <tr v-if="!patients || patients.length === 0">
                                            <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">{{ $t('a_no_patients_found') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== PRESCRIPTIONS TAB ==================== -->
                    <div v-if="activeTab === 'prescriptions'" class="space-y-4">
                        <div v-for="rx in prescriptions" :key="rx.id"
                            class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:border-[#C4A265]/20 hover:shadow-md transition-all duration-300">
                            <div class="flex justify-between items-start p-5 border-b border-gray-50 bg-gradient-to-r from-gray-50/50 to-white">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                        <Link v-if="can('patients.view') && rx.patient" :href="`/admin/patients/${rx.patient.id}`" class="text-sm font-semibold text-[#C4A265] hover:underline">{{ rx.patient?.full_name }}</Link>
                                        <span v-else class="text-sm font-semibold text-gray-900">{{ rx.patient?.full_name || '-' }}</span>
                                        <span class="text-xs text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">{{ formatDate(rx.visit?.visit_date || rx.created_at) }}</span>
                                    </div>
                                    <p v-if="rx.diagnosis" class="text-sm text-gray-500 mt-2 ltr:ml-11 rtl:mr-11">{{ rx.diagnosis }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Link v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}`" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">View</Link>
                                    <a v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}/print`" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border text-[#1B365D] border-slate-200 hover:bg-slate-50 transition">
                                        <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                        {{ $t('a_print') }}
                                    </a>
                                    <a v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}/pdf`" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border text-emerald-600 border-emerald-200 hover:bg-emerald-50 transition">
                                        <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        PDF
                                    </a>
                                    <Link v-if="rx.visit_id && can('visits.view')" :href="`/admin/visits/${rx.visit_id}`" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">Visit</Link>
                                    <button v-if="can('prescriptions.delete')" @click="deletePrescription(rx.id)" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition">{{ $t('a_delete') }}</button>
                                </div>
                            </div>
                            <div class="p-5">
                                <div v-if="rx.items && rx.items.length" class="space-y-2">
                                    <div v-for="item in rx.items" :key="item.id" class="flex items-center gap-3 text-sm bg-gray-50 rounded-xl px-4 py-2.5 hover:bg-[#C4A265]/5 transition-colors">
                                        <div class="w-1.5 h-1.5 rounded-full bg-[#C4A265] shrink-0"></div>
                                        <span class="font-semibold text-gray-800">{{ item.medication_name }}</span>
                                        <span v-if="item.dosage" class="text-gray-400">{{ item.dosage }}</span>
                                        <span v-if="item.frequency" class="text-gray-400">| {{ item.frequency }}</span>
                                        <span v-if="item.duration" class="text-gray-400">| {{ item.duration }}</span>
                                    </div>
                                </div>
                                <div v-if="rx.notes" class="mt-3 p-3 bg-[#F5E7C8]/30 rounded-xl border border-amber-100 text-sm text-gray-600">
                                    <span class="font-semibold text-amber-700">{{ $t('a_notes') }}:</span> {{ rx.notes }}
                                </div>
                            </div>
                        </div>

                        <div v-if="(!prescriptions || prescriptions.length === 0) && !can('prescriptions.create')" class="text-center py-12 text-sm text-gray-400">
                            No prescriptions found.
                        </div>

                        <!-- New Prescription Form -->
                        <div v-if="can('prescriptions.create')">
                            <button
                                v-if="!showNewPrescription"
                                @click="showNewPrescription = true"
                                class="group inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 bg-gradient-to-r from-[#C4A265] to-[#D4B275] hover:shadow-lg hover:shadow-[#C4A265]/20 hover:scale-105"
                            >
                                <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ $t('a_new_prescription') }}
                            </button>

                            <div v-if="showNewPrescription" class="border border-gray-200 rounded-2xl p-4 md:p-6 bg-gray-50/50 mt-3">
                                <h4 class="text-sm font-bold text-gray-700 mb-5">{{ $t('a_create_prescription_for') }} {{ $localized(doctor, 'name') }}</h4>
                                <form @submit.prevent="submitPrescription" class="space-y-5">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="relative">
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_patient') }} <span class="text-red-500">*</span></label>
                                            <input
                                                v-model="patientSearch"
                                                @input="searchPatients(patientSearch)"
                                                @focus="searchPatients(patientSearch)"
                                                @blur="setTimeout(() => showPatientDropdown = false, 200)"
                                                type="text"
                                                class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition"
                                                :placeholder="$t('a_search_patient')"
                                                autocomplete="off"
                                            />
                                            <div v-if="showPatientDropdown && filteredPatients.length" class="absolute z-20 mt-1 w-full bg-white rounded-xl shadow-xl border border-gray-100 max-h-48 overflow-y-auto">
                                                <button v-for="p in filteredPatients" :key="p.id" type="button" @mousedown.prevent="selectPatient(p)" class="w-full ltr:text-left rtl:text-right px-4 py-2.5 text-sm hover:bg-[#C4A265]/5 border-b last:border-b-0 transition-colors">
                                                    <span class="font-semibold text-gray-900">{{ p.full_name }}</span>
                                                    <span v-if="p.phone" class="text-gray-400 ltr:ml-2 rtl:mr-2">{{ p.phone }}</span>
                                                </button>
                                            </div>
                                            <p v-if="prescriptionForm.errors.patient_id" class="mt-1 text-xs text-red-600">{{ prescriptionForm.errors.patient_id }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_diagnosis') }}</label>
                                            <input v-model="prescriptionForm.diagnosis" type="text" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition" placeholder="Diagnosis" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_notes') }}</label>
                                            <input v-model="prescriptionForm.notes" type="text" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition" placeholder="Optional notes" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_medications') }}</label>
                                        <div v-for="(item, idx) in prescriptionForm.items" :key="idx" class="flex flex-wrap gap-2 mb-3 items-start p-4 bg-white rounded-xl border border-gray-200">
                                            <div class="flex-1 min-w-[160px] relative">
                                                <input v-model="item.medication_name" @input="searchMedications(item.medication_name, idx)" @blur="setTimeout(() => { if (activeMedIdx === idx) medicationSuggestions = []; }, 200)" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition" placeholder="Medication name *" />
                                                <div v-if="activeMedIdx === idx && medicationSuggestions.length" class="absolute z-10 mt-1 w-full bg-white rounded-xl shadow-xl border max-h-40 overflow-y-auto">
                                                    <button v-for="med in medicationSuggestions" :key="med.id" type="button" @mousedown.prevent="selectMedication(med, idx)" class="w-full ltr:text-left rtl:text-right px-3 py-2 text-sm hover:bg-[#C4A265]/5 border-b last:border-b-0">
                                                        <span class="font-medium text-gray-900">{{ med.name }}</span>
                                                        <span v-if="med.default_dosage" class="text-gray-400 ltr:ml-2 rtl:mr-2">{{ med.default_dosage }}</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="w-28"><input v-model="item.dosage" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Dosage" /></div>
                                            <div class="w-32"><input v-model="item.frequency" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Frequency" /></div>
                                            <div class="w-24"><input v-model="item.duration" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Duration" /></div>
                                            <button v-if="prescriptionForm.items.length > 1" type="button" @click="removePrescriptionItem(idx)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                        <button type="button" @click="addPrescriptionItem" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-xl border-2 border-dashed border-gray-200 text-gray-400 hover:text-[#C4A265] hover:border-[#C4A265]/30 transition-all">
                                            <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            {{ $t('a_add_medication') }}
                                        </button>
                                    </div>
                                    <div class="flex gap-3 pt-2">
                                        <button type="submit" :disabled="prescriptionForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all bg-gradient-to-r from-[#C4A265] to-[#D4B275] hover:shadow-lg hover:shadow-[#C4A265]/20 disabled:opacity-50">
                                            {{ prescriptionForm.processing ? $t('a_saving_dots') : $t('a_save_prescription') }}
                                        </button>
                                        <button type="button" @click="showNewPrescription = false" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-medium hover:bg-gray-50 transition">{{ $t('a_cancel') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== COMMISSION & FINANCE TAB ==================== -->
                    <div v-if="activeTab === 'finance'" class="space-y-8">
                        <!-- Payout Summary -->
                        <div v-if="payoutSummary" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="group relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-4 md:p-6 text-white shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:scale-[1.02] transition-all duration-500">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                                <p class="text-xs font-semibold text-emerald-100 uppercase tracking-wider">{{ $t('a_total_paid') }}</p>
                                <p class="text-xl md:text-2xl font-bold mt-2">{{ formatCurrency(payoutSummary.total_paid) }}</p>
                            </div>
                            <div class="group relative overflow-hidden bg-gradient-to-br from-[#C4A265] to-[#8B7043] rounded-2xl p-4 md:p-6 text-white shadow-lg shadow-[#C4A265]/30 hover:shadow-[#C4A265]/40 hover:scale-[1.02] transition-all duration-500">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                                <p class="text-xs font-semibold text-[#F5E7C8] uppercase tracking-wider">{{ $t('a_pending_payment') }}</p>
                                <p class="text-xl md:text-2xl font-bold mt-2">{{ formatCurrency(payoutSummary.total_pending) }}</p>
                            </div>
                            <div class="group relative overflow-hidden bg-gradient-to-br from-[#C4A265] to-[#C4A265] rounded-2xl p-4 md:p-6 text-white shadow-lg shadow-[#C4A265]/20 hover:shadow-[#C4A265]/30 hover:scale-[1.02] transition-all duration-500">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                                <p class="text-xs font-semibold text-[#F5E7C8] uppercase tracking-wider">{{ $t('a_unpaid_commission') }}</p>
                                <p class="text-xl md:text-2xl font-bold mt-2">{{ formatCurrency(payoutSummary.total_unpaid) }}</p>
                            </div>
                        </div>

                        <!-- Recent Payouts -->
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                            <div class="px-4 md:px-6 py-4 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_recent_payouts') }}</h3>
                                <div class="flex items-center gap-2">
                                    <Link v-if="can('doctor_payouts.create')" :href="`/admin/doctor-payouts/create?doctor_id=${doctor.id}`"
                                        class="text-xs font-semibold px-4 py-2 rounded-xl text-white bg-gradient-to-r from-[#C4A265] to-[#D4B275] hover:shadow-md transition-all">+ Create Payout</Link>
                                    <Link v-if="can('doctor_payouts.view')" :href="`/admin/doctor-payouts?doctor_id=${doctor.id}`"
                                        class="text-xs font-medium px-4 py-2 rounded-xl text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition">View All</Link>
                                </div>
                            </div>
                            <div v-if="recentPayouts?.length > 0" class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead><tr class="bg-gray-50/50">
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_payout_number') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_period') }}</th>
                                        <th class="px-5 py-3 ltr:text-right rtl:text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_amount') }}</th>
                                        <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_status') }}</th>
                                        <th class="px-5 py-3"></th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="p in recentPayouts" :key="p.id" class="hover:bg-[#C4A265]/[0.02] transition-colors">
                                            <td class="px-5 py-3 text-xs font-mono font-semibold text-gray-700">{{ p.payout_number }}</td>
                                            <td class="px-5 py-3 text-xs text-gray-500">{{ formatDate(p.period_start) }} – {{ formatDate(p.period_end) }}</td>
                                            <td class="px-5 py-3 text-sm font-bold ltr:text-right rtl:text-left text-[#C4A265]">{{ formatCurrency(p.net_amount) }}</td>
                                            <td class="px-5 py-3 text-center">
                                                <span class="text-[10px] font-bold px-3 py-1 rounded-full"
                                                    :class="{
                                                        'bg-gray-100 text-gray-500': p.status === 'draft',
                                                        'bg-amber-100 text-amber-700': p.status === 'confirmed',
                                                        'bg-emerald-100 text-emerald-700': p.status === 'paid',
                                                        'bg-red-100 text-red-600': p.status === 'cancelled',
                                                    }">
                                                    {{ p.status === 'confirmed' ? 'Pending' : p.status.charAt(0).toUpperCase() + p.status.slice(1) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 ltr:text-right rtl:text-left">
                                                <Link v-if="can('doctor_payouts.view')" :href="`/admin/doctor-payouts/${p.id}`" class="text-xs font-semibold text-[#C4A265] hover:underline">{{ $t('a_view') }}</Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-400 text-center py-8">{{ $t('a_no_payouts_yet') }}</p>
                        </div>

                        <!-- Commission Settings -->
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                            <div class="px-4 md:px-6 py-4 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_commission_settings') }}</h3>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center gap-4 mb-5">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#A08245] flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-[#C4A265]/20">
                                        {{ doctor.default_commission_percentage || 0 }}%
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_default_commission_rate') }}</p>
                                        <p class="text-lg font-bold text-gray-800">{{ $t('a_consultation_fee') }}: {{ formatCurrency(doctor.consultation_fee) }}</p>
                                    </div>
                                </div>
                                <div v-if="doctor.service_rates && doctor.service_rates.length > 0">
                                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-3">{{ $t('a_per_service_rates') }}</p>
                                    <div class="space-y-2">
                                        <div v-for="rate in doctor.service_rates" :key="rate.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-[#C4A265]/5 transition-colors">
                                            <span class="text-sm text-gray-700">{{ $localized(rate.service, 'name') || '-' }}</span>
                                            <span class="text-sm font-bold text-[#C4A265] bg-[#C4A265]/10 px-3 py-1 rounded-lg">{{ rate.commission_percentage }}%</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-gray-400 mt-2">{{ $t('a_no_custom_rates_default') }}</p>
                            </div>
                        </div>

                        <!-- Summary + Charts -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-5 border border-emerald-100">
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_total_revenue') }}</p>
                                <p class="text-xl font-bold text-emerald-700 mt-2">{{ formatCurrency(performanceStats.total_revenue) }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-[#C4A265]/10 to-white rounded-2xl p-5 border border-[#C4A265]/20">
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_total_commission') }}</p>
                                <p class="text-xl font-bold text-[#C4A265] mt-2">{{ formatCurrency(performanceStats.total_commission) }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-slate-50 to-white rounded-2xl p-5 border border-slate-100">
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_this_month_revenue') }}</p>
                                <p class="text-xl font-bold text-[#1B365D] mt-2">{{ formatCurrency(performanceStats.this_month_revenue) }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-slate-50 to-white rounded-2xl p-5 border border-slate-100">
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">{{ $t('a_this_month_commission') }}</p>
                                <p class="text-xl font-bold text-[#1B365D] mt-2">{{ formatCurrency(performanceStats.this_month_commission) }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white rounded-2xl p-4 md:p-6 border border-gray-100 shadow-sm">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">{{ $t('a_monthly_commission') }}</h3>
                                <BarChart v-if="commissionChartData.length > 0" :data="commissionChartData" :height="220" color="#C4A265" />
                                <p v-else class="text-sm text-gray-400 text-center py-8">{{ $t('a_no_commission_data') }}</p>
                            </div>
                            <div class="bg-white rounded-2xl p-4 md:p-6 border border-gray-100 shadow-sm">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">{{ $t('a_monthly_revenue') }}</h3>
                                <BarChart v-if="revenueChartData.length > 0" :data="revenueChartData" :height="220" color="#3B82F6" />
                                <p v-else class="text-sm text-gray-400 text-center py-8">{{ $t('a_no_revenue_data_chart') }}</p>
                            </div>
                        </div>

                        <!-- Commission Statement -->
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                            <div class="px-4 md:px-6 py-4 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_commission_statement') }}</h3>
                            </div>
                            <div v-if="commissionStatement && commissionStatement.length > 0" class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead><tr class="bg-gray-50/50">
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_date') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_patient') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_service') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_type') }}</th>
                                        <th class="px-5 py-3 ltr:text-right rtl:text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_invoice_total') }}</th>
                                        <th class="px-5 py-3 ltr:text-right rtl:text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_supply_cost') }}</th>
                                        <th class="px-5 py-3 ltr:text-right rtl:text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_rate') }}</th>
                                        <th class="px-5 py-3 ltr:text-right rtl:text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_commission') }}</th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="row in commissionStatement" :key="row.id" class="hover:bg-[#C4A265]/[0.02] transition-colors">
                                            <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">{{ formatDate(row.visit_date) }}</td>
                                            <td class="px-5 py-3 whitespace-nowrap">
                                                <Link v-if="can('patients.view') && row.patient_id" :href="`/admin/patients/${row.patient_id}`" class="text-sm font-semibold text-[#C4A265] hover:underline">{{ row.patient_name }}</Link>
                                                <span v-else class="text-sm text-gray-900">{{ row.patient_name || '-' }}</span>
                                            </td>
                                            <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">{{ row.service_name || '-' }}</td>
                                            <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500 capitalize">{{ (row.visit_type || '').replace(/_/g, ' ') }}</td>
                                            <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900 ltr:text-right rtl:text-left">{{ row.invoice_total ? formatCurrency(row.invoice_total) : '-' }}</td>
                                            <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500 ltr:text-right rtl:text-left">{{ row.supply_cost ? formatCurrency(row.supply_cost) : '-' }}</td>
                                            <td class="px-5 py-3 whitespace-nowrap text-sm font-bold ltr:text-right rtl:text-left text-[#C4A265]">{{ row.commission_rate }}%</td>
                                            <td class="px-5 py-3 whitespace-nowrap text-sm font-bold ltr:text-right rtl:text-left text-emerald-600">{{ formatCurrency(row.commission_amount) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50/80">
                                        <tr class="font-bold">
                                            <td colspan="4" class="px-5 py-3 text-sm text-gray-700">{{ $t('a_total_showing_last_50') }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-900 ltr:text-right rtl:text-left">{{ formatCurrency(commissionStatement.reduce((sum, r) => sum + Number(r.invoice_total || 0), 0)) }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500 ltr:text-right rtl:text-left">{{ formatCurrency(commissionStatement.reduce((sum, r) => sum + Number(r.supply_cost || 0), 0)) }}</td>
                                            <td class="px-5 py-3"></td>
                                            <td class="px-5 py-3 text-sm text-emerald-600 ltr:text-right rtl:text-left">{{ formatCurrency(commissionStatement.reduce((sum, r) => sum + Number(r.commission_amount || 0), 0)) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-400 text-center py-10">{{ $t('a_no_commission_records') }}</p>
                        </div>
                    </div>

                    <!-- ==================== SCHEDULE & LEAVES TAB ==================== -->
                    <div v-if="activeTab === 'schedule'" class="space-y-8">
                        <!-- Weekly Schedule -->
                        <div>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                {{ $t('a_weekly_schedule') }}
                            </h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                                <div v-for="(day, i) in dayNames" :key="i"
                                    class="group relative overflow-hidden rounded-2xl p-4 border transition-all duration-300 hover:shadow-md"
                                    :class="getScheduleForDay(i)?.is_active
                                        ? 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-white hover:border-emerald-300'
                                        : 'border-gray-200 bg-gradient-to-br from-gray-50 to-white'"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-gray-700 uppercase">{{ day.substring(0, 3) }}</span>
                                        <div class="w-2.5 h-2.5 rounded-full transition-all" :class="getScheduleForDay(i)?.is_active ? 'bg-emerald-500 shadow-sm shadow-emerald-500/50' : 'bg-gray-300'"></div>
                                    </div>
                                    <p v-if="getScheduleForDay(i)" class="text-xs text-gray-600 font-medium">
                                        {{ formatTime(getScheduleForDay(i).start_time) }}<br/>{{ formatTime(getScheduleForDay(i).end_time) }}
                                    </p>
                                    <p v-else class="text-xs text-gray-400">{{ $t('a_not_scheduled') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Vacations -->
                        <div v-if="doctor.vacations && doctor.vacations.length > 0">
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">{{ $t('a_vacations') }}</h3>
                            <div class="space-y-3">
                                <div v-for="vacation in doctor.vacations" :key="vacation.id" class="bg-gradient-to-r from-[#F5E7C8]/40 to-white rounded-2xl px-5 py-4 border border-amber-100 flex items-center gap-4 hover:shadow-md transition-shadow">
                                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" /></svg>
                                    </div>
                                    <div>
                                        <span class="text-sm font-semibold text-gray-800">{{ formatDate(vacation.start_date) }} - {{ formatDate(vacation.end_date) }}</span>
                                        <p v-if="vacation.reason" class="text-xs text-gray-500 mt-0.5">{{ vacation.reason }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Requests -->
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                            <div class="px-4 md:px-6 py-4 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_leave_requests') }}</h3>
                            </div>
                            <div v-if="leaves && leaves.length > 0" class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead><tr class="bg-gray-50/50">
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_type') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_from') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_to') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_reason') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_status') }}</th>
                                        <th class="px-5 py-3 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_approved_by') }}</th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="leave in leaves" :key="leave.id" class="hover:bg-[#C4A265]/[0.02] transition-colors">
                                            <td class="px-5 py-3 text-sm text-gray-900 capitalize">{{ leave.leave_type?.replace(/_/g, ' ') || '-' }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ formatDate(leave.start_date) }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ formatDate(leave.end_date) }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ leave.reason || '-' }}</td>
                                            <td class="px-5 py-3"><StatusBadge :status="leave.status" type="leave" /></td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ leave.approver?.name || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-400 text-center py-10">{{ $t('a_no_leave_requests') }}</p>
                        </div>
                    </div>

                    <!-- ==================== BOOKINGS TAB ==================== -->
                    <div v-if="activeTab === 'bookings'">
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead><tr class="bg-gray-50/50">
                                        <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_date') }}</th>
                                        <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_client') }}</th>
                                        <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_phone') }}</th>
                                        <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_service') }}</th>
                                        <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_time') }}</th>
                                        <th class="px-5 py-3.5 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_status') }}</th>
                                        <th class="px-5 py-3.5"></th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="booking in bookings" :key="booking.id" class="hover:bg-[#C4A265]/[0.02] transition-colors duration-200">
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ formatDate(booking.preferred_date) }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-semibold text-gray-900">{{ booking.full_name }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ booking.phone }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ booking.service ? $localized(booking.service, 'name') : '-' }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ booking.preferred_time || '-' }}</td>
                                            <td class="px-5 py-3.5 whitespace-nowrap"><StatusBadge :status="booking.status" type="booking" /></td>
                                            <td class="px-5 py-3.5 whitespace-nowrap ltr:text-right rtl:text-left">
                                                <Link v-if="can('bookings.view')" :href="`/admin/bookings/${booking.id}`" class="text-xs font-semibold text-[#C4A265] hover:underline">{{ $t('a_view') }}</Link>
                                            </td>
                                        </tr>
                                        <tr v-if="!bookings || bookings.length === 0">
                                            <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">{{ $t('a_no_bookings_found_dot') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== ATTENDANCE TAB ==================== -->
                    <div v-if="activeTab === 'attendance'" class="space-y-6">
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                            <div class="group relative overflow-hidden bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-5 border border-emerald-100 hover:shadow-lg hover:shadow-emerald-500/10 hover:scale-[1.02] transition-all duration-500">
                                <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-500/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center mb-3">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">{{ isRtl ? 'حاضر' : 'Present' }}</p>
                                    <p class="text-2xl md:text-3xl font-bold text-emerald-700 mt-1">{{ attendanceSummary?.present ?? 0 }}</p>
                                </div>
                            </div>
                            <div class="group relative overflow-hidden bg-gradient-to-br from-red-50 to-white rounded-2xl p-5 border border-red-100 hover:shadow-lg hover:shadow-red-500/10 hover:scale-[1.02] transition-all duration-500">
                                <div class="absolute top-0 right-0 w-16 h-16 bg-red-500/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center mb-3">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <p class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ isRtl ? 'غائب' : 'Absent' }}</p>
                                    <p class="text-2xl md:text-3xl font-bold text-red-700 mt-1">{{ attendanceSummary?.absent ?? 0 }}</p>
                                </div>
                            </div>
                            <div class="group relative overflow-hidden bg-gradient-to-br from-[#F5E7C8]/40 to-white rounded-2xl p-5 border border-amber-100 hover:shadow-lg hover:shadow-[#C4A265]/20 hover:scale-[1.02] transition-all duration-500">
                                <div class="absolute top-0 right-0 w-16 h-16 bg-[#C4A265]/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center mb-3">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <p class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">{{ isRtl ? 'متأخر' : 'Late' }}</p>
                                    <p class="text-2xl md:text-3xl font-bold text-amber-700 mt-1">{{ attendanceSummary?.late ?? 0 }}</p>
                                </div>
                            </div>
                            <div class="group relative overflow-hidden bg-gradient-to-br from-slate-50 to-white rounded-2xl p-5 border border-slate-100 hover:shadow-lg hover:shadow-[#1B365D]/10 hover:scale-[1.02] transition-all duration-500">
                                <div class="absolute top-0 right-0 w-16 h-16 bg-[#1B365D]/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center mb-3">
                                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" /></svg>
                                    </div>
                                    <p class="text-[10px] font-bold text-[#1B365D] uppercase tracking-wider">{{ isRtl ? 'إجازة' : 'Leave' }}</p>
                                    <p class="text-2xl md:text-3xl font-bold text-[#1B365D] mt-1">{{ attendanceSummary?.leave ?? 0 }}</p>
                                </div>
                            </div>
                            <div class="group relative overflow-hidden bg-gradient-to-br from-slate-50 to-white rounded-2xl p-5 border border-slate-100 hover:shadow-lg hover:shadow-[#1B365D]/10 hover:scale-[1.02] transition-all duration-500">
                                <div class="absolute top-0 right-0 w-16 h-16 bg-[#1B365D]/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-700"></div>
                                <div class="relative">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center mb-3">
                                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    </div>
                                    <p class="text-[10px] font-bold text-[#1B365D] uppercase tracking-wider">{{ isRtl ? 'إضافي' : 'Overtime' }}</p>
                                    <p class="text-2xl md:text-3xl font-bold text-[#1B365D] mt-1">{{ attendanceSummary?.overtime_hours ?? 0 }}<span class="text-sm font-normal text-slate-400">h</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Records Table -->
                        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead><tr class="bg-gray-50/50">
                                        <th class="px-5 py-3.5 text-start text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                        <th class="px-5 py-3.5 text-start text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                        <th class="px-5 py-3.5 text-start text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ isRtl ? 'حضور' : 'Check In' }}</th>
                                        <th class="px-5 py-3.5 text-start text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ isRtl ? 'انصراف' : 'Check Out' }}</th>
                                        <th class="px-5 py-3.5 text-start text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ isRtl ? 'موقع' : 'GPS' }}</th>
                                        <th class="px-5 py-3.5 text-start text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ isRtl ? 'ملاحظات' : 'Notes' }}</th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="record in attendanceRecords" :key="record.id" class="hover:bg-[#C4A265]/[0.02] transition-colors duration-200">
                                            <td class="px-5 py-3.5">
                                                <span class="text-sm font-semibold text-gray-900">{{ record.date ? new Date(record.date).toLocaleDateString(isRtl ? 'ar-EG' : 'en-GB', { weekday: 'short', day: '2-digit', month: 'short' }) : '-' }}</span>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1.5 rounded-full"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700': record.status === 'present',
                                                        'bg-red-50 text-red-700': record.status === 'absent',
                                                        'bg-amber-50 text-amber-700': record.status === 'late',
                                                        'bg-slate-50 text-[#1B365D]': record.status === 'leave',
                                                    }">
                                                    <span class="w-1.5 h-1.5 rounded-full"
                                                        :class="{
                                                            'bg-emerald-500': record.status === 'present',
                                                            'bg-red-500': record.status === 'absent',
                                                            'bg-amber-500': record.status === 'late',
                                                            'bg-[#1B365D]': record.status === 'leave',
                                                        }"></span>
                                                    {{ record.status === 'present' ? (isRtl ? 'حاضر' : 'Present') :
                                                       record.status === 'absent' ? (isRtl ? 'غائب' : 'Absent') :
                                                       record.status === 'late' ? (isRtl ? 'متأخر' : 'Late') :
                                                       record.status === 'leave' ? (isRtl ? 'إجازة' : 'Leave') : record.status }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span v-if="record.check_in" class="text-sm font-mono font-bold text-gray-700 bg-gray-50 px-2.5 py-1 rounded-lg">{{ record.check_in?.substring(0, 5) }}</span>
                                                <span v-else class="text-sm text-gray-300">--:--</span>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span v-if="record.check_out" class="text-sm font-mono font-bold text-gray-700 bg-gray-50 px-2.5 py-1 rounded-lg">{{ record.check_out?.substring(0, 5) }}</span>
                                                <span v-else class="text-sm text-gray-300">--:--</span>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <div class="flex items-center gap-1.5">
                                                    <span v-if="record.check_in_lat" class="inline-flex items-center gap-1 text-[9px] text-emerald-600 font-bold bg-emerald-50 px-2 py-1 rounded-lg">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                                        {{ isRtl ? 'دخول' : 'In' }}
                                                    </span>
                                                    <span v-if="record.check_out_lat" class="inline-flex items-center gap-1 text-[9px] text-red-500 font-bold bg-red-50 px-2 py-1 rounded-lg">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                                        {{ isRtl ? 'خروج' : 'Out' }}
                                                    </span>
                                                    <span v-if="!record.check_in_lat && !record.check_out_lat" class="text-xs text-gray-300">-</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span v-if="record.notes" class="text-xs text-gray-500 max-w-[150px] truncate block">{{ record.notes }}</span>
                                                <span v-else class="text-xs text-gray-300">-</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!attendanceRecords || attendanceRecords.length === 0">
                                            <td colspan="6" class="px-5 py-16 text-center">
                                                <div class="flex flex-col items-center">
                                                    <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                                    </div>
                                                    <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد سجلات حضور' : 'No attendance records found' }}</p>
                                                    <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'سجلات آخر 3 أشهر' : 'Last 3 months records' }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Link to full attendance -->
                        <div class="text-center">
                            <Link href="/admin/attendances" class="group inline-flex items-center gap-2 text-sm font-semibold text-[#C4A265] hover:text-[#A08245] transition-colors">
                                {{ isRtl ? 'عرض كل سجلات الحضور' : 'View all attendance records' }}
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ---- Hero Animations ---- */
.hero-card {
    animation: heroSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes heroSlideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.hero-avatar {
    animation: avatarPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s both;
}

@keyframes avatarPop {
    from { opacity: 0; transform: scale(0.5); }
    to { opacity: 1; transform: scale(1); }
}

.hero-info {
    animation: infoSlide 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both;
}

@keyframes infoSlide {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

[dir="rtl"] .hero-info {
    animation-name: infoSlideRtl;
}

@keyframes infoSlideRtl {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

/* ---- Floating Orbs ---- */
.floating-orb {
    animation: floatOrb 8s ease-in-out infinite;
}

.floating-orb-delayed {
    animation: floatOrb 10s ease-in-out 2s infinite;
}

@keyframes floatOrb {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(-20px, 15px) scale(1.1); }
    50% { transform: translate(10px, -10px) scale(0.95); }
    75% { transform: translate(-15px, -20px) scale(1.05); }
}

/* ---- Hero Pattern ---- */
.hero-pattern {
    width: 100%;
    height: 100%;
    background-image: radial-gradient(circle at 1px 1px, rgba(196, 162, 101, 0.15) 1px, transparent 0);
    background-size: 40px 40px;
}

/* ---- Pulse Dot ---- */
.pulse-dot {
    animation: pulseDot 2s ease-in-out infinite;
}

@keyframes pulseDot {
    0%, 100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.5); }
    50% { box-shadow: 0 0 0 6px rgba(52, 211, 153, 0); }
}

/* ---- Tab Indicator ---- */
.tab-indicator {
    animation: tabSlide 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes tabSlide {
    from { transform: scaleX(0); }
    to { transform: scaleX(1); }
}

/* ---- Tab Content Transitions ---- */
.tab-fade-out {
    opacity: 0;
    transform: translateY(8px);
    transition: all 0.15s ease-out;
}

.tab-fade-in {
    opacity: 1;
    transform: translateY(0);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

/* ---- KPI Card Stagger ---- */
.kpi-card {
    animation: cardSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-delay: var(--delay, 0ms);
}

.kpi-card:nth-child(1) { --delay: 0ms; }
.kpi-card:nth-child(2) { --delay: 80ms; }
.kpi-card:nth-child(3) { --delay: 160ms; }
.kpi-card:nth-child(4) { --delay: 240ms; }

@keyframes cardSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ---- Counter Number ---- */
.counter-number {
    animation: countPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s both;
}

@keyframes countPop {
    from { opacity: 0; transform: scale(0.5); }
    to { opacity: 1; transform: scale(1); }
}

/* ---- Scrollbar hide for tabs ---- */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
