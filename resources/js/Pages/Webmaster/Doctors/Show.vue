<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import BarChart from '@/Components/Admin/BarChart.vue';
import DonutChart from '@/Components/Admin/DonutChart.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { sanitizeHtml } from '@/Composables/useSanitize';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency, currencyCode } = useCurrency();

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
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const activeTab = ref('overview');

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'visits', label: 'Visits & Queue' },
    { id: 'patients', label: 'Patients' },
    { id: 'prescriptions', label: 'Prescriptions' },
    { id: 'finance', label: 'Commission & Finance' },
    { id: 'schedule', label: 'Schedule & Leaves' },
    { id: 'bookings', label: 'Bookings' },
];

const dayNames = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const visitTypeLabels = {
    consultation: 'Consultation',
    session: 'Session',
    follow_up: 'Follow Up',
};

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
            const res = await fetch(`/webmaster/api/medications?q=${encodeURIComponent(query)}`);
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
        .post('/webmaster/prescriptions', {
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
    if (window.confirm('Are you sure you want to delete this prescription?')) {
        router.post(`/webmaster/prescriptions/${rxId}/delete`, {}, {
            preserveScroll: true,
        });
    }
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
</script>

<template>
    <WebmasterLayout :title="`Dr. ${doctor.name_en}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <img
                            v-if="doctor.photo_url"
                            :src="doctor.photo_url"
                            :alt="doctor.name_en"
                            class="w-20 h-20 rounded-full object-cover border-2"
                            style="border-color: #C4A265;"
                        />
                        <div
                            v-else
                            class="w-20 h-20 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                            style="background-color: #C4A265;"
                        >
                            {{ doctor.name_en?.charAt(0) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Dr. {{ doctor.name_en }}</h1>
                            <p class="text-lg text-gray-500" dir="rtl">د. {{ doctor.name_ar }}</p>
                            <p class="text-sm text-gray-500 mt-0.5">{{ doctor.specialization_en }}</p>
                            <div class="flex items-center space-x-3 mt-2">
                                <StatusBadge :status="doctor.status" />
                                <span v-if="doctor.consultation_fee" class="text-sm font-medium" style="color: #C4A265;">
                                    Fee: {{ formatCurrency(doctor.consultation_fee) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <Link
                        v-if="can('doctors.update')"
                        :href="`/webmaster/doctors/${doctor.id}/edit`"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                        style="background-color: #C4A265;"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Edit Doctor
                    </Link>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-lg shadow-sm">
                <nav class="flex border-b border-gray-200 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="px-6 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap"
                        :class="activeTab === tab.id
                            ? 'border-current text-[#C4A265]'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                        :style="activeTab === tab.id ? 'color: #C4A265;' : ''"
                    >
                        {{ tab.label }}
                    </button>
                </nav>

                <div class="p-6">
                    <!-- ==================== OVERVIEW TAB ==================== -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <!-- KPI Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Total Visits</p>
                                <p class="text-2xl font-bold text-gray-800 mt-1">{{ performanceStats.total_visits }}</p>
                            </div>
                            <div class="bg-emerald-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Completed</p>
                                <p class="text-2xl font-bold text-emerald-700 mt-1">{{ performanceStats.completed_visits }}</p>
                            </div>
                            <div class="rounded-lg p-4" style="background-color: rgba(196,162,101,0.08);">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Consultations</p>
                                <p class="text-2xl font-bold mt-1" style="color: #C4A265;">{{ performanceStats.consultations }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Sessions</p>
                                <p class="text-2xl font-bold text-[#1B365D] mt-1">{{ performanceStats.sessions }}</p>
                            </div>
                            <div class="bg-emerald-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Total Revenue</p>
                                <p class="text-xl font-bold text-emerald-700 mt-1">{{ formatCurrency(performanceStats.total_revenue) }}</p>
                            </div>
                            <div class="rounded-lg p-4" style="background-color: rgba(196,162,101,0.08);">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Total Commission</p>
                                <p class="text-xl font-bold mt-1" style="color: #C4A265;">{{ formatCurrency(performanceStats.total_commission) }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">This Month Revenue</p>
                                <p class="text-xl font-bold text-[#1B365D] mt-1">{{ formatCurrency(performanceStats.this_month_revenue) }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">This Month Commission</p>
                                <p class="text-xl font-bold text-[#1B365D] mt-1">{{ formatCurrency(performanceStats.this_month_commission) }}</p>
                            </div>
                            <div v-if="visitTypeDistribution.length > 0" class="bg-gray-50 rounded-lg p-4 flex items-center justify-center">
                                <DonutChart :data="visitTypeDistribution" :size="100" />
                            </div>
                        </div>

                        <!-- Personal & Clinic Info -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-gray-50 rounded-lg p-5 space-y-3">
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Personal Information</h3>
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd class="text-gray-900">{{ doctor.phone || '-' }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd class="text-gray-900">{{ doctor.email || '-' }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">Specialization (EN)</dt><dd class="text-gray-900">{{ doctor.specialization_en || '-' }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">Specialization (AR)</dt><dd class="text-gray-900" dir="rtl">{{ doctor.specialization_ar || '-' }}</dd></div>
                                </dl>
                                <div v-if="doctor.qualifications_en" class="pt-2 border-t border-gray-200">
                                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Qualifications</p>
                                    <p class="text-sm text-gray-800">{{ doctor.qualifications_en }}</p>
                                </div>
                                <div v-if="doctor.bio_en" class="pt-2 border-t border-gray-200">
                                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Bio</p>
                                    <div class="text-sm text-gray-800 prose prose-sm max-w-none" v-html="sanitizeHtml(doctor.bio_en)"></div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-5 space-y-3">
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Clinic Details</h3>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between"><dt class="text-gray-500">Default Commission</dt><dd class="font-medium" style="color: #C4A265;">{{ doctor.default_commission_percentage || 0 }}%</dd></div>
                                        <div class="flex justify-between"><dt class="text-gray-500">Consultation Fee</dt><dd class="text-gray-900">{{ formatCurrency(doctor.consultation_fee) }}</dd></div>
                                        <div class="flex justify-between"><dt class="text-gray-500">{{ $t('a_display_order') }}</dt><dd class="text-gray-900">{{ doctor.display_order || '-' }}</dd></div>
                                    </dl>
                                </div>
                                <div v-if="doctor.clinic_notes" class="bg-yellow-50 rounded-lg p-5">
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-2">{{ $t('a_clinic_notes') }}</h3>
                                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ doctor.clinic_notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== VISITS & QUEUE TAB ==================== -->
                    <div v-if="activeTab === 'visits'" class="space-y-6">
                        <!-- Today's Queue -->
                        <div v-if="todayQueue && todayQueue.length > 0">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Today's Queue</h3>
                            <div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
                                <div v-for="visit in todayQueue" :key="visit.id" class="px-4 py-3 flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <StatusBadge :status="visit.status" type="visit" />
                                        <Link :href="`/webmaster/visits/${visit.id}`" class="text-sm font-medium text-gray-900 hover:underline">
                                            {{ visit.patient?.full_name }}
                                        </Link>
                                        <span class="text-xs text-gray-500">{{ visit.service?.name_en }}</span>
                                    </div>
                                    <Link :href="`/webmaster/visits/${visit.id}`" class="text-xs font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view') }}</Link>
                                </div>
                            </div>
                        </div>

                        <!-- All Visits Table -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">All Visits</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('a_date') }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('a_status') }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commission</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('a_actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="visit in visits" :key="visit.id" class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ formatDate(visit.visit_date) }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <Link v-if="can('patients.view')" :href="`/webmaster/patients/${visit.patient?.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ visit.patient?.full_name }}</Link>
                                                <span v-else class="text-sm text-gray-900">{{ visit.patient?.full_name }}</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ visitTypeLabels[visit.visit_type] || visit.visit_type }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ visit.service?.name_en || '-' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap"><StatusBadge :status="visit.status" type="visit" /></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" style="color: #C4A265;">{{ visit.commission_amount ? formatCurrency(visit.commission_amount) : '-' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                                <Link :href="`/webmaster/visits/${visit.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">View</Link>
                                            </td>
                                        </tr>
                                        <tr v-if="!visits || visits.length === 0">
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No visits found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== PATIENTS TAB ==================== -->
                    <div v-if="activeTab === 'patients'">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">File #</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visits</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Visit</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="patient in patients" :key="patient.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <Link v-if="can('patients.view')" :href="`/webmaster/patients/${patient.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ patient.full_name }}</Link>
                                            <span v-else class="text-sm font-medium text-gray-900">{{ patient.full_name }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ patient.phone || '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono">{{ patient.file_number || '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ patient.visit_count }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ formatDate(patient.last_visit_date) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <Link v-if="can('patients.view')" :href="`/webmaster/patients/${patient.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view_profile') }}</Link>
                                        </td>
                                    </tr>
                                    <tr v-if="!patients || patients.length === 0">
                                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No patients found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==================== PRESCRIPTIONS TAB ==================== -->
                    <div v-if="activeTab === 'prescriptions'" class="space-y-4">
                        <!-- Existing Prescriptions -->
                        <div v-for="rx in prescriptions" :key="rx.id" class="border rounded-lg overflow-hidden">
                            <div class="flex justify-between items-start p-4 border-b bg-gray-50">
                                <div>
                                    <div class="flex items-center space-x-3">
                                        <Link v-if="can('patients.view') && rx.patient" :href="`/webmaster/patients/${rx.patient.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ rx.patient?.full_name }}</Link>
                                        <span v-else class="text-sm font-medium text-gray-900">{{ rx.patient?.full_name || '-' }}</span>
                                        <span class="text-xs text-gray-500">{{ formatDate(rx.visit?.visit_date || rx.created_at) }}</span>
                                    </div>
                                    <p v-if="rx.diagnosis" class="text-sm text-gray-600 mt-1">{{ rx.diagnosis }}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Link v-if="can('prescriptions.view')" :href="`/webmaster/prescriptions/${rx.id}`" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                                        View
                                    </Link>
                                    <a v-if="can('prescriptions.view')" :href="`/webmaster/prescriptions/${rx.id}/print`" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border text-[#1B365D] border-slate-300 hover:bg-slate-50 transition">
                                        <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                        Print
                                    </a>
                                    <a v-if="can('prescriptions.view')" :href="`/webmaster/prescriptions/${rx.id}/pdf`" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border text-emerald-600 border-emerald-300 hover:bg-emerald-50 transition">
                                        <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        PDF
                                    </a>
                                    <Link v-if="rx.visit_id && can('visits.view')" :href="`/webmaster/visits/${rx.visit_id}`" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                                        Visit
                                    </Link>
                                    <button v-if="can('prescriptions.delete')" @click="deletePrescription(rx.id)" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border border-red-300 text-red-600 hover:bg-red-50 transition">
                                        Delete
                                    </button>
                                </div>
                            </div>
                            <div class="p-4">
                                <div v-if="rx.items && rx.items.length" class="space-y-1">
                                    <div v-for="item in rx.items" :key="item.id" class="text-xs text-gray-600 bg-gray-50 rounded px-3 py-1.5">
                                        <span class="font-medium text-gray-800">{{ item.medication_name }}</span>
                                        <span v-if="item.dosage"> - {{ item.dosage }}</span>
                                        <span v-if="item.frequency"> | {{ item.frequency }}</span>
                                        <span v-if="item.duration"> | {{ item.duration }}</span>
                                    </div>
                                </div>
                                <div v-if="rx.notes" class="mt-2 p-2 bg-yellow-50 rounded text-xs text-gray-600">
                                    <span class="font-medium">Notes:</span> {{ rx.notes }}
                                </div>
                            </div>
                        </div>
                        <div v-if="(!prescriptions || prescriptions.length === 0) && !can('prescriptions.create')" class="text-center py-8 text-sm text-gray-500">
                            No prescriptions found.
                        </div>

                        <!-- New Prescription Form -->
                        <div v-if="can('prescriptions.create')">
                            <button
                                v-if="!showNewPrescription"
                                @click="showNewPrescription = true"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                New Prescription
                            </button>

                            <div v-if="showNewPrescription" class="border border-gray-200 rounded-lg p-5 bg-gray-50 mt-2">
                                <h4 class="text-sm font-semibold text-gray-700 mb-4">Create Prescription for Dr. {{ doctor.name_en }}</h4>
                                <form @submit.prevent="submitPrescription" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Patient Search -->
                                        <div class="relative">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Patient <span class="text-red-500">*</span></label>
                                            <input
                                                v-model="patientSearch"
                                                @input="searchPatients(patientSearch)"
                                                @focus="searchPatients(patientSearch)"
                                                @blur="setTimeout(() => showPatientDropdown = false, 200)"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                                placeholder="Search patient by name or phone..."
                                                autocomplete="off"
                                            />
                                            <div v-if="showPatientDropdown && filteredPatients.length" class="absolute z-20 mt-1 w-full bg-white rounded-lg shadow-lg border max-h-48 overflow-y-auto">
                                                <button
                                                    v-for="p in filteredPatients"
                                                    :key="p.id"
                                                    type="button"
                                                    @mousedown.prevent="selectPatient(p)"
                                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 border-b last:border-b-0"
                                                >
                                                    <span class="font-medium text-gray-900">{{ p.full_name }}</span>
                                                    <span v-if="p.phone" class="text-gray-400 ml-2">{{ p.phone }}</span>
                                                </button>
                                            </div>
                                            <p v-if="prescriptionForm.errors.patient_id" class="mt-1 text-xs text-red-600">{{ prescriptionForm.errors.patient_id }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                                            <input v-model="prescriptionForm.diagnosis" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Diagnosis" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                            <input v-model="prescriptionForm.notes" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Optional notes" />
                                        </div>
                                    </div>

                                    <!-- Medication Items -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Medications</label>
                                        <div v-for="(item, idx) in prescriptionForm.items" :key="idx" class="flex flex-wrap gap-2 mb-3 items-start p-3 bg-white rounded-lg border border-gray-200">
                                            <div class="flex-1 min-w-[160px] relative">
                                                <input
                                                    v-model="item.medication_name"
                                                    @input="searchMedications(item.medication_name, idx)"
                                                    @blur="setTimeout(() => { if (activeMedIdx === idx) medicationSuggestions = []; }, 200)"
                                                    type="text"
                                                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
                                                    placeholder="Medication name *"
                                                />
                                                <div v-if="activeMedIdx === idx && medicationSuggestions.length" class="absolute z-10 mt-1 w-full bg-white rounded-lg shadow-lg border max-h-40 overflow-y-auto">
                                                    <button
                                                        v-for="med in medicationSuggestions"
                                                        :key="med.id"
                                                        type="button"
                                                        @mousedown.prevent="selectMedication(med, idx)"
                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 border-b last:border-b-0"
                                                    >
                                                        <span class="font-medium text-gray-900">{{ med.name }}</span>
                                                        <span v-if="med.default_dosage" class="text-gray-400 ml-2">{{ med.default_dosage }}</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="w-28">
                                                <input v-model="item.dosage" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Dosage" />
                                            </div>
                                            <div class="w-32">
                                                <input v-model="item.frequency" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Frequency" />
                                            </div>
                                            <div class="w-24">
                                                <input v-model="item.duration" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Duration" />
                                            </div>
                                            <button
                                                v-if="prescriptionForm.items.length > 1"
                                                type="button"
                                                @click="removePrescriptionItem(idx)"
                                                class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            @click="addPrescriptionItem"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-dashed border-gray-300 text-gray-500 hover:text-gray-700 hover:border-gray-400 transition"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            Add Medication
                                        </button>
                                    </div>

                                    <div class="flex space-x-3 pt-2">
                                        <button
                                            type="submit"
                                            :disabled="prescriptionForm.processing"
                                            class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                            style="background-color: #C4A265;"
                                        >
                                            {{ prescriptionForm.processing ? 'Saving...' : 'Save Prescription' }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="showNewPrescription = false"
                                            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium transition hover:bg-gray-50"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== COMMISSION & FINANCE TAB ==================== -->
                    <div v-if="activeTab === 'finance'" class="space-y-6">
                        <!-- Commission Settings -->
                        <div class="bg-gray-50 rounded-lg p-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Commission Settings</h3>
                            <div class="mb-3 text-sm">
                                <span class="text-gray-500">Default Commission Rate:</span>
                                <span class="ml-2 font-bold text-lg" style="color: #C4A265;">{{ doctor.default_commission_percentage || 0 }}%</span>
                            </div>
                            <div v-if="doctor.service_rates && doctor.service_rates.length > 0">
                                <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Per-Service Rates</p>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Commission %</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="rate in doctor.service_rates" :key="rate.id">
                                            <td class="px-4 py-2 text-sm text-gray-900">{{ rate.service?.name_en || '-' }}</td>
                                            <td class="px-4 py-2 text-sm font-medium text-right" style="color: #C4A265;">{{ rate.commission_percentage }}%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-xs text-gray-400">No custom service rates configured. Default rate applies to all services.</p>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-emerald-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Total Revenue</p>
                                <p class="text-xl font-bold text-emerald-700 mt-1">{{ formatCurrency(performanceStats.total_revenue) }}</p>
                            </div>
                            <div class="rounded-lg p-4" style="background-color: rgba(196,162,101,0.08);">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Total Commission</p>
                                <p class="text-xl font-bold mt-1" style="color: #C4A265;">{{ formatCurrency(performanceStats.total_commission) }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">This Month Revenue</p>
                                <p class="text-xl font-bold text-[#1B365D] mt-1">{{ formatCurrency(performanceStats.this_month_revenue) }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">This Month Commission</p>
                                <p class="text-xl font-bold text-[#1B365D] mt-1">{{ formatCurrency(performanceStats.this_month_commission) }}</p>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-gray-50 rounded-lg p-5">
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Monthly Commission (12 months)</h3>
                                <BarChart v-if="commissionChartData.length > 0" :data="commissionChartData" :height="220" color="#C4A265" />
                                <p v-else class="text-sm text-gray-400 text-center py-8">No commission data available.</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-5">
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Monthly Revenue (12 months)</h3>
                                <BarChart v-if="revenueChartData.length > 0" :data="revenueChartData" :height="220" color="#3B82F6" />
                                <p v-else class="text-sm text-gray-400 text-center py-8">No revenue data available.</p>
                            </div>
                        </div>

                        <!-- Commission Statement — per-visit breakdown -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Commission Statement</h3>
                            <div v-if="commissionStatement && commissionStatement.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Invoice Total</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Supply Cost</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rate</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Commission</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="row in commissionStatement" :key="row.id" class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ formatDate(row.visit_date) }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <Link v-if="can('patients.view') && row.patient_id" :href="`/webmaster/patients/${row.patient_id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ row.patient_name }}</Link>
                                                <span v-else class="text-sm text-gray-900">{{ row.patient_name || '-' }}</span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ row.service_name || '-' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 capitalize">{{ (row.visit_type || '').replace(/_/g, ' ') }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ row.invoice_total ? formatCurrency(row.invoice_total) : '-' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-right">{{ row.supply_cost ? formatCurrency(row.supply_cost) : '-' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-right" style="color: #C4A265;">{{ row.commission_rate }}%</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-right text-emerald-700">{{ formatCurrency(row.commission_amount) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-700">Total (showing last 50)</td>
                                            <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">
                                                {{ formatCurrency(commissionStatement.reduce((sum, r) => sum + Number(r.invoice_total || 0), 0)) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-bold text-gray-500 text-right">
                                                {{ formatCurrency(commissionStatement.reduce((sum, r) => sum + Number(r.supply_cost || 0), 0)) }}
                                            </td>
                                            <td class="px-4 py-3"></td>
                                            <td class="px-4 py-3 text-sm font-bold text-emerald-700 text-right">
                                                {{ formatCurrency(commissionStatement.reduce((sum, r) => sum + Number(r.commission_amount || 0), 0)) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-400 text-center py-8">No commission records found.</p>
                        </div>
                    </div>

                    <!-- ==================== SCHEDULE & LEAVES TAB ==================== -->
                    <div v-if="activeTab === 'schedule'" class="space-y-6">
                        <!-- Weekly Schedule -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">{{ $t('a_weekly_schedule') }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <div v-for="(day, i) in dayNames" :key="i" class="border rounded-lg p-3" :class="getScheduleForDay(i)?.is_active ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-gray-50'">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-800">{{ day }}</span>
                                        <span v-if="getScheduleForDay(i)?.is_active" class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span v-else class="w-2 h-2 rounded-full bg-gray-300"></span>
                                    </div>
                                    <p v-if="getScheduleForDay(i)" class="text-xs text-gray-600">
                                        {{ formatTime(getScheduleForDay(i).start_time) }} - {{ formatTime(getScheduleForDay(i).end_time) }}
                                    </p>
                                    <p v-else class="text-xs text-gray-400">Not scheduled</p>
                                </div>
                            </div>
                        </div>

                        <!-- Vacations -->
                        <div v-if="doctor.vacations && doctor.vacations.length > 0">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Vacations</h3>
                            <div class="space-y-2">
                                <div v-for="vacation in doctor.vacations" :key="vacation.id" class="bg-yellow-50 rounded-lg px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-gray-800">{{ formatDate(vacation.start_date) }} - {{ formatDate(vacation.end_date) }}</span>
                                        <p v-if="vacation.reason" class="text-xs text-gray-500 mt-0.5">{{ vacation.reason }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Requests -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Leave Requests</h3>
                            <div v-if="leaves && leaves.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved By</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="leave in leaves" :key="leave.id" class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900 capitalize">{{ leave.leave_type?.replace(/_/g, ' ') || '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(leave.start_date) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(leave.end_date) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ leave.reason || '-' }}</td>
                                            <td class="px-4 py-3"><StatusBadge :status="leave.status" type="leave" /></td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ leave.approver?.name || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-400 text-center py-6">No leave requests found.</p>
                        </div>
                    </div>

                    <!-- ==================== BOOKINGS TAB ==================== -->
                    <div v-if="activeTab === 'bookings'">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('a_client') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="booking in bookings" :key="booking.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ formatDate(booking.preferred_date) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ booking.full_name }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ booking.phone }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ booking.service?.name_en || '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ booking.preferred_time || '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><StatusBadge :status="booking.status" type="booking" /></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <Link v-if="can('bookings.view')" :href="`/webmaster/bookings/${booking.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">View</Link>
                                        </td>
                                    </tr>
                                    <tr v-if="!bookings || bookings.length === 0">
                                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No bookings found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WebmasterLayout>
</template>
