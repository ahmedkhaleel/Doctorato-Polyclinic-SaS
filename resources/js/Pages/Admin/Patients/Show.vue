<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import PediatricGrowthChart from '@/Components/PediatricGrowthChart.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    financialSummary: Object,
    dentalData: Object,
    pediatricData: Object,
    doctors: Array,
});

const canViewSensitive = computed(() => props.dentalData?.canViewSensitive ?? false);
const canUpdateSensitive = computed(() => props.dentalData?.canUpdateSensitive ?? false);

function isRestricted(value) {
    return typeof value === 'string' && value.includes('[RESTRICTED');
}

const activeTab = ref('overview');

// ── Growth recording form ──────────────────────────
const showGrowthForm = ref(false);
const growthForm = useForm({
    measurement_date: new Date().toISOString().split('T')[0],
    weight_kg: '',
    height_cm: '',
    head_circumference_cm: '',
    notes: '',
});

function submitGrowth() {
    growthForm.post(`/admin/pediatric/patients/${props.patient.id}/growth`, {
        preserveScroll: true,
        onSuccess: () => {
            growthForm.reset();
            growthForm.measurement_date = new Date().toISOString().split('T')[0];
            showGrowthForm.value = false;
        },
    });
}

function deleteGrowthRecord(recordId) {
    if (!window.confirm(isRtl.value ? 'هل أنت متأكد من حذف هذا القياس؟' : 'Are you sure you want to delete this measurement?')) return;
    router.post(`/admin/pediatric/growth/${recordId}/delete`, {}, { preserveScroll: true });
}

const hasDentalVisits = computed(() => (props.patient?.visits || []).some(v => v.module === 'dental'));

const isDentalEnabled = computed(() => page.props.modules?.dental?.enabled);
const isPediatricEnabled = computed(() => page.props.modules?.pediatric?.enabled);

const tabs = computed(() => {
    const baseTabs = [
        { id: 'overview', key: 'a_overview' },
        { id: 'visits', key: 'a_visits' },
        { id: 'packages', key: 'a_packages' },
        { id: 'invoices', key: 'a_invoices' },
        { id: 'prescriptions', key: 'a_prescriptions' },
        { id: 'photos', key: 'a_photos' },
    ];
    if (isDentalEnabled.value) {
        baseTabs.push({ id: 'dental', label: isRtl.value ? 'طب الأسنان' : 'Dental' });
    }
    if (isPediatricEnabled.value) {
        baseTabs.push({ id: 'pediatric', label: isRtl.value ? 'طب الأطفال' : 'Pediatric' });
    }
    return baseTabs;
});

const statusColors = {
    waiting: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    active: 'bg-green-100 text-green-800',
    paid: 'bg-green-100 text-green-800',
    partial: 'bg-yellow-100 text-yellow-800',
    unpaid: 'bg-red-100 text-red-800',
};

const bundleStatusColors = {
    pending: 'bg-gray-100 text-gray-700',
    confirmed: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const bundleServiceStatusColors = {
    pending: 'bg-gray-50 text-gray-600 border-gray-200',
    in_progress: 'bg-blue-50 text-blue-700 border-blue-200',
    completed: 'bg-green-50 text-green-700 border-green-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

function bundleProgress(booking) {
    if (!booking.bundle_services?.length) return 0;
    const total = booking.bundle_services.reduce((s, bs) => s + (bs.sessions_count || 0), 0);
    const done = booking.bundle_services.reduce((s, bs) => s + (bs.completed_sessions || 0), 0);
    return total > 0 ? Math.round((done / total) * 100) : 0;
}

const referralLabels = {
    walk_in: 'Walk-in',
    social_media: 'Social Media',
    google: 'Google',
    friend: 'Friend',
    doctor: 'Doctor',
    advertisement: 'Advertisement',
    other: 'Other',
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

// Prescription creation from patient profile
const showNewPrescription = ref(false);
const prescriptionForm = useForm({
    diagnosis: '',
    notes: '',
    doctor_id: '',
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
});

const doctorOptions = computed(() => (props.doctors || []).map(d => ({ value: d.id, label: d.name_en })));

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
            patient_id: props.patient.id,
            visit_id: null,
        }))
        .post('/admin/prescriptions', {
            preserveScroll: true,
            onSuccess: () => {
                prescriptionForm.reset();
                prescriptionForm.items = [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }];
                showNewPrescription.value = false;
            },
        });
}

function deletePrescription(rxId) {
    if (window.confirm('Are you sure you want to delete this prescription?')) {
        router.post(`/admin/prescriptions/${rxId}/delete`, {
            preserveScroll: true,
        });
    }
}

// ─── Dental Medical History ──────────────────────
const showDentalMedicalEdit = ref(false);
const dentalMedicalForm = useForm({
    has_dental_anxiety: props.dentalData?.medicalHistory?.has_dental_anxiety || false,
    dental_anxiety_level: props.dentalData?.medicalHistory?.dental_anxiety_level || 'none',
    previous_dental_surgeries: props.dentalData?.medicalHistory?.previous_dental_surgeries || '',
    latex_allergy: props.dentalData?.medicalHistory?.latex_allergy || false,
    anesthesia_complications: props.dentalData?.medicalHistory?.anesthesia_complications || false,
    anesthesia_notes: props.dentalData?.medicalHistory?.anesthesia_notes || '',
    is_pregnant: props.dentalData?.medicalHistory?.is_pregnant || false,
    is_breastfeeding: props.dentalData?.medicalHistory?.is_breastfeeding || false,
    has_bleeding_disorder: props.dentalData?.medicalHistory?.has_bleeding_disorder || false,
    takes_blood_thinners: props.dentalData?.medicalHistory?.takes_blood_thinners || false,
    blood_thinner_name: props.dentalData?.medicalHistory?.blood_thinner_name || '',
    has_heart_condition: props.dentalData?.medicalHistory?.has_heart_condition || false,
    has_diabetes: props.dentalData?.medicalHistory?.has_diabetes || false,
    diabetes_type: props.dentalData?.medicalHistory?.diabetes_type || '',
    has_hepatitis: props.dentalData?.medicalHistory?.has_hepatitis || false,
    hepatitis_type: props.dentalData?.medicalHistory?.hepatitis_type || '',
    has_hiv: props.dentalData?.medicalHistory?.has_hiv || false,
    is_smoker: props.dentalData?.medicalHistory?.is_smoker || false,
    smoking_frequency: props.dentalData?.medicalHistory?.smoking_frequency || '',
    jaw_problems: props.dentalData?.medicalHistory?.jaw_problems || false,
    teeth_grinding: props.dentalData?.medicalHistory?.teeth_grinding || false,
    last_dental_visit: props.dentalData?.medicalHistory?.last_dental_visit || '',
    dental_medical_notes: props.dentalData?.medicalHistory?.dental_medical_notes || '',
});

function saveDentalMedical() {
    dentalMedicalForm.put(`/admin/patients/${props.patient.id}/dental-medical`, {
        preserveScroll: true,
        onSuccess: () => { showDentalMedicalEdit.value = false; },
    });
}

const dentalRiskFlags = computed(() => props.dentalData?.riskFlags || []);
</script>

<template>
    <AdminLayout :title="`Patient: ${patient.full_name}`">
        <div class="space-y-6">
            <!-- ═══════ Hero Header Card ═══════ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 shadow-xl transition-all duration-700"
                :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                <!-- Decorative Elements -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute -top-20 -end-20 w-64 h-64 rounded-full" style="background: radial-gradient(circle, #C4A265, transparent 70%);"></div>
                    <div class="absolute -bottom-10 -start-10 w-48 h-48 rounded-full" style="background: radial-gradient(circle, #C4A265, transparent 70%);"></div>
                </div>

                <div class="relative p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                        <!-- Avatar -->
                        <div class="relative group">
                            <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 opacity-60 blur group-hover:opacity-80 transition-opacity duration-300"></div>
                            <div v-if="patient.photo" class="relative w-20 h-20 rounded-2xl overflow-hidden ring-2 ring-white/20">
                                <img :src="patient.photo.startsWith('http') ? patient.photo : `/storage/${patient.photo}`" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="relative w-20 h-20 rounded-2xl flex items-center justify-center text-2xl font-bold text-white ring-2 ring-white/20" style="background: linear-gradient(135deg, #C4A265, #A68B52);">
                                {{ patient.full_name?.charAt(0) }}
                            </div>
                            <div class="absolute -bottom-1 -end-1 w-6 h-6 rounded-full flex items-center justify-center text-[10px]"
                                :class="patient.is_active ? 'bg-emerald-500 text-white' : 'bg-gray-500 text-white'">
                                <svg v-if="patient.is_active" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                <svg v-else class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1.5">
                                <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight truncate">{{ patient.full_name }}</h1>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :class="patient.is_active ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/30' : 'bg-gray-500/20 text-gray-400 ring-1 ring-gray-500/30'">
                                    {{ patient.is_active ? $t('a_active') : $t('a_inactive') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-white/50">
                                <span class="font-mono font-semibold text-amber-400/80">{{ patient.file_number }}</span>
                                <span v-if="patient.phone" class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    {{ patient.phone }}
                                </span>
                                <span v-if="patient.gender" class="flex items-center gap-1">
                                    {{ patient.gender === 'male' ? '♂' : '♀' }} {{ patient.gender === 'male' ? $t('a_male') : $t('a_female') }}
                                </span>
                                <span v-if="patient.age">{{ patient.age }} {{ isRtl ? 'سنة' : 'yrs' }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <Link
                                :href="`/admin/patients/${patient.id}/timeline`"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/10 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ isRtl ? 'السجل الزمني' : 'Timeline' }}
                            </Link>
                            <Link
                                v-if="can('patients.update')"
                                :href="`/admin/patients/${patient.id}/edit`"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/10 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                {{ $t('a_edit_patient') }}
                            </Link>
                            <Link
                                v-if="can('visits.create')"
                                :href="`/admin/bookings/create?patient_id=${patient.id}`"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl text-sm font-bold text-gray-900 shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-200"
                                style="background: linear-gradient(135deg, #C4A265, #D4B275);"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                {{ $t('a_new_booking') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Quick Stats Bar -->
                    <div v-if="financialSummary" class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/10 transition-all duration-200">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider">{{ $t('a_total_visits') }}</p>
                            <p class="text-xl font-bold text-white mt-0.5">{{ financialSummary.total_visits }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/10 transition-all duration-200">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider">{{ $t('a_total_invoiced') }}</p>
                            <p class="text-xl font-bold text-amber-400 mt-0.5">{{ formatCurrency(financialSummary.total_invoiced) }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/10 transition-all duration-200">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider">{{ $t('a_total_paid') }}</p>
                            <p class="text-xl font-bold text-emerald-400 mt-0.5">{{ formatCurrency(financialSummary.total_paid) }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/10 transition-all duration-200">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider">{{ $t('a_outstanding') }}</p>
                            <p class="text-xl font-bold mt-0.5" :class="financialSummary.outstanding_balance > 0 ? 'text-red-400' : 'text-emerald-400'">{{ formatCurrency(financialSummary.outstanding_balance) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════ Tab Navigation ═══════ -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 transition-all duration-500"
                :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                <div class="px-4 pt-4 pb-0">
                    <nav class="flex gap-1 overflow-x-auto pb-0 -mb-px">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            class="relative px-5 py-3 text-sm font-semibold whitespace-nowrap rounded-t-xl transition-all duration-200"
                            :class="activeTab === tab.id
                                ? 'text-[#C4A265] bg-amber-50/50 border border-gray-200 border-b-white -mb-px z-10'
                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 border border-transparent'"
                        >
                            {{ tab.key ? $t(tab.key) : tab.label }}
                            <span v-if="activeTab === tab.id" class="absolute bottom-0 inset-x-4 h-0.5 rounded-full" style="background-color: #C4A265;"></span>
                        </button>
                    </nav>
                </div>

                <div class="border-t border-gray-200"></div>

                <div class="p-6">
                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Personal Info Card -->
                            <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl border border-gray-100 overflow-hidden">
                                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800">{{ $t('a_personal_info') }}</h3>
                                </div>
                                <dl class="p-5 space-y-3">
                                    <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <dt class="text-xs font-medium text-gray-500 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            {{ $t('a_phone') }}
                                        </dt>
                                        <dd class="text-sm font-semibold text-gray-800" dir="ltr">{{ patient.phone }}</dd>
                                    </div>
                                    <div v-if="patient.phone2" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <dt class="text-xs font-medium text-gray-500">{{ $t('a_phone2') }}</dt>
                                        <dd class="text-sm font-semibold text-gray-800" dir="ltr">{{ patient.phone2 }}</dd>
                                    </div>
                                    <div v-if="patient.email" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <dt class="text-xs font-medium text-gray-500 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            {{ $t('a_email') }}
                                        </dt>
                                        <dd class="text-sm font-semibold text-gray-800">{{ patient.email }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <dt class="text-xs font-medium text-gray-500">{{ $t('a_gender') }}</dt>
                                        <dd class="text-sm font-semibold text-gray-800">{{ patient.gender === 'male' ? $t('a_male') : $t('a_female') }}</dd>
                                    </div>
                                    <div v-if="patient.date_of_birth" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <dt class="text-xs font-medium text-gray-500 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ $t('a_date_of_birth') }}
                                        </dt>
                                        <dd class="text-sm font-semibold text-gray-800">{{ formatDate(patient.date_of_birth) }} <span class="text-xs text-gray-400 font-normal">({{ patient.age }} {{ isRtl ? 'سنة' : 'yrs' }})</span></dd>
                                    </div>
                                    <div v-if="patient.nationality" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <dt class="text-xs font-medium text-gray-500">{{ $t('a_nationality') }}</dt>
                                        <dd class="text-sm font-semibold text-gray-800">{{ patient.nationality }}</dd>
                                    </div>
                                    <div v-if="patient.address" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <dt class="text-xs font-medium text-gray-500">{{ $t('a_address') }}</dt>
                                        <dd class="text-sm font-semibold text-gray-800">{{ patient.address }}</dd>
                                    </div>
                                    <div v-if="patient.occupation" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                        <dt class="text-xs font-medium text-gray-500">{{ $t('a_occupation') }}</dt>
                                        <dd class="text-sm font-semibold text-gray-800">{{ patient.occupation }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Clinic Info Card -->
                            <div class="space-y-5">
                                <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl border border-gray-100 overflow-hidden">
                                    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: rgba(196,162,101,0.1);">
                                            <svg class="w-4 h-4" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-800">{{ $t('a_clinic_info') }}</h3>
                                    </div>
                                    <dl class="p-5 space-y-3">
                                        <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                            <dt class="text-xs font-medium text-gray-500">{{ $t('a_referral') }}</dt>
                                            <dd class="text-sm font-semibold text-gray-800">{{ referralLabels[patient.referral_source] || '-' }}</dd>
                                        </div>
                                        <div v-if="patient.referred_by" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                            <dt class="text-xs font-medium text-gray-500">{{ $t('a_referred_by') }}</dt>
                                            <dd class="text-sm font-semibold text-gray-800">{{ patient.referred_by }}</dd>
                                        </div>
                                        <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                            <dt class="text-xs font-medium text-gray-500">{{ $t('a_total_visits') }}</dt>
                                            <dd class="text-sm font-bold" style="color: #C4A265;">{{ patient.visits?.length || 0 }}</dd>
                                        </div>
                                        <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors">
                                            <dt class="text-xs font-medium text-gray-500">{{ $t('a_completed') }}</dt>
                                            <dd class="text-sm font-bold text-emerald-600">{{ financialSummary?.completed_visits || 0 }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Medical Notes -->
                                <div v-if="patient.medical_notes" class="rounded-2xl border-2 border-amber-100 overflow-hidden">
                                    <div class="px-5 py-3 bg-amber-50 border-b border-amber-100 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                        <h3 class="text-sm font-bold text-amber-800">{{ $t('a_medical_notes') }}</h3>
                                    </div>
                                    <div class="p-5 bg-amber-50/30">
                                        <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ patient.medical_notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visits Tab -->
                    <div v-if="activeTab === 'visits'">
                        <div v-if="patient.visits?.length" class="space-y-3">
                            <div v-for="visit in patient.visits" :key="visit.id"
                                class="group relative bg-white rounded-xl border border-gray-100 p-4 hover:border-gray-200 hover:shadow-md transition-all duration-200">
                                <div class="flex items-center gap-4">
                                    <!-- Date Circle -->
                                    <div class="flex-shrink-0 w-14 h-14 rounded-xl flex flex-col items-center justify-center border border-gray-100"
                                        :class="visit.status === 'completed' ? 'bg-emerald-50' : visit.status === 'cancelled' ? 'bg-red-50' : 'bg-amber-50'">
                                        <span class="text-lg font-bold leading-none" :class="visit.status === 'completed' ? 'text-emerald-600' : visit.status === 'cancelled' ? 'text-red-500' : 'text-amber-600'">
                                            {{ visit.visit_date ? new Date(visit.visit_date).getDate() : '-' }}
                                        </span>
                                        <span class="text-[9px] font-semibold text-gray-400 uppercase">
                                            {{ visit.visit_date ? new Date(visit.visit_date).toLocaleDateString('en', { month: 'short' }) : '' }}
                                        </span>
                                    </div>

                                    <!-- Visit Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-sm font-bold text-gray-800">{{ visit.service ? $localized(visit.service, 'name') : (visit.visit_type || '-') }}</span>
                                            <span :class="statusColors[visit.status]" class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize">{{ visit.status }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 text-xs text-gray-500">
                                            <span v-if="visit.doctor" class="flex items-center gap-1">
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                {{ $localized(visit.doctor, 'name') }}
                                            </span>
                                            <span class="capitalize text-gray-400">{{ visit.visit_type }}</span>
                                        </div>
                                    </div>

                                    <!-- Action -->
                                    <Link v-if="can('visits.view')" :href="`/admin/visits/${visit.id}`"
                                        class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold transition-all duration-200 opacity-70 group-hover:opacity-100"
                                        style="color: #C4A265; background: rgba(196,162,101,0.08);">
                                        {{ $t('a_view') }}
                                        <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <p class="text-sm text-gray-400 font-medium">{{ $t('a_no_visits_yet') }}</p>
                        </div>
                    </div>

                    <!-- Packages Tab -->
                    <div v-if="activeTab === 'packages'">
                        <div v-if="patient.package_bundle_bookings?.length" class="space-y-5">
                            <div v-for="booking in patient.package_bundle_bookings" :key="booking.id" class="border border-gray-200 rounded-xl overflow-hidden">
                                <!-- Booking Header -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 border-b border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: rgba(196, 162, 101, 0.12);">
                                            <svg class="w-5 h-5" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ booking.package_bundle ? $localized(booking.package_bundle, 'name') : $t('a_package_bundle') }}</h4>
                                            <p class="text-xs text-gray-400 font-mono">{{ booking.booking_number }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span :class="bundleStatusColors[booking.status]" class="px-2.5 py-0.5 text-xs font-semibold rounded-full capitalize">{{ booking.status?.replace('_', ' ') }}</span>
                                        <Link v-if="can('package_bundle_bookings.view')" :href="`/admin/package-bundle-bookings/${booking.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view') }}</Link>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="px-4 pt-3 pb-1">
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <span class="text-gray-500">{{ $t('a_progress') }}</span>
                                        <span class="font-semibold" style="color: #C4A265;">{{ bundleProgress(booking) }}%</span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500" style="background-color: #C4A265;" :style="{ width: bundleProgress(booking) + '%' }"></div>
                                    </div>
                                </div>

                                <!-- Financial Row -->
                                <div class="grid grid-cols-3 gap-4 px-4 py-3 text-sm">
                                    <div>
                                        <span class="text-gray-400 text-xs block">{{ $t('a_total') }}</span>
                                        <span class="font-semibold text-gray-800">{{ formatCurrency(booking.total_price) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 text-xs block">{{ $t('a_paid') }}</span>
                                        <span class="font-semibold text-green-700">{{ formatCurrency(booking.total_paid) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 text-xs block">{{ $t('a_remaining') }}</span>
                                        <span class="font-semibold" :class="Number(booking.balance_due) > 0 ? 'text-red-600' : 'text-gray-500'">{{ formatCurrency(booking.balance_due) }}</span>
                                    </div>
                                </div>

                                <!-- Services Breakdown -->
                                <div class="border-t border-gray-100">
                                    <div class="px-4 py-2.5 bg-gray-50/50">
                                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_services') }}</span>
                                    </div>
                                    <div class="divide-y divide-gray-100">
                                        <div v-for="bs in booking.bundle_services" :key="bs.id" class="px-4 py-3 flex items-center justify-between gap-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate">{{ bs.service ? $localized(bs.service, 'name') : '-' }}</p>
                                                <p class="text-xs text-gray-400">
                                                    Dr. {{ bs.doctor?.name_en || '-' }}
                                                    <span class="mx-1">&middot;</span>
                                                    {{ formatCurrency(bs.bundle_price) }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3 flex-shrink-0">
                                                <!-- Sessions Counter -->
                                                <div class="flex items-center gap-1.5">
                                                    <div class="flex gap-0.5">
                                                        <span
                                                            v-for="n in (bs.sessions_count || 1)"
                                                            :key="n"
                                                            class="w-2.5 h-2.5 rounded-full border"
                                                            :class="n <= (bs.completed_sessions || 0)
                                                                ? 'bg-green-500 border-green-500'
                                                                : 'bg-white border-gray-300'"
                                                        ></span>
                                                    </div>
                                                    <span class="text-xs text-gray-500 font-medium">{{ bs.completed_sessions || 0 }}/{{ bs.sessions_count || 0 }}</span>
                                                </div>
                                                <!-- Status Badge -->
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border capitalize" :class="bundleServiceStatusColors[bs.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                                    {{ bs.status?.replace('_', ' ') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dates Footer -->
                                <div class="px-4 py-2.5 bg-gray-50 border-t border-gray-100 flex items-center gap-6 text-xs text-gray-400">
                                    <span v-if="booking.started_at">Started: {{ formatDate(booking.started_at) }}</span>
                                    <span v-if="booking.completed_at">Completed: {{ formatDate(booking.completed_at) }}</span>
                                    <span v-if="!booking.started_at && !booking.completed_at">Created: {{ formatDate(booking.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 text-center py-8">{{ $t('a_no_packages_yet') }}</p>
                    </div>

                    <!-- Invoices Tab -->
                    <div v-if="activeTab === 'invoices'">
                        <table v-if="patient.invoices?.length" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('a_invoice_number') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('a_date') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('a_total') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('a_paid') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('a_status') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('a_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="inv in patient.invoices" :key="inv.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <Link v-if="can('invoices.view')" :href="`/admin/invoices/${inv.id}`" class="font-mono font-medium hover:underline" style="color: #C4A265;">{{ inv.invoice_number }}</Link>
                                        <span v-else class="font-mono" style="color: #C4A265;">{{ inv.invoice_number }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(inv.created_at) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ formatCurrency(inv.total) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ formatCurrency(inv.paid_amount) }}</td>
                                    <td class="px-4 py-3"><span :class="statusColors[inv.status]" class="px-2 text-xs leading-5 font-semibold rounded-full">{{ inv.status }}</span></td>
                                    <td class="px-4 py-3 text-right">
                                        <Link v-if="can('invoices.view')" :href="`/admin/invoices/${inv.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view') }}</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-sm text-gray-500 text-center py-8">{{ $t('a_no_invoices_yet') }}</p>
                    </div>

                    <!-- Prescriptions Tab -->
                    <div v-if="activeTab === 'prescriptions'">
                        <!-- Existing Prescriptions -->
                        <div v-if="patient.prescriptions?.length" class="space-y-4 mb-6">
                            <div v-for="rx in patient.prescriptions" :key="rx.id" class="border rounded-lg overflow-hidden">
                                <div class="flex justify-between items-start p-4 border-b bg-gray-50">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ rx.diagnosis || 'No diagnosis' }}</p>
                                        <p class="text-xs text-gray-500">Dr. {{ rx.doctor ? $localized(rx.doctor, 'name') : $t('a_unknown') }} &middot; {{ formatDate(rx.created_at) }}</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Link v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}`" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                                            View
                                        </Link>
                                        <a v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}/print`" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border text-blue-600 border-blue-300 hover:bg-blue-50 transition">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                            Print
                                        </a>
                                        <a v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}/pdf`" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border text-green-600 border-green-300 hover:bg-green-50 transition">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            PDF
                                        </a>
                                        <Link v-if="rx.visit_id && can('visits.view')" :href="`/admin/visits/${rx.visit_id}`" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                                            Visit
                                        </Link>
                                        <button v-if="can('prescriptions.delete')" @click="deletePrescription(rx.id)" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border border-red-300 text-red-600 hover:bg-red-50 transition">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-xs text-gray-500 uppercase">
                                                <th class="text-left py-1">{{ $t('a_medication') }}</th>
                                                <th class="text-left py-1">{{ $t('a_dosage') }}</th>
                                                <th class="text-left py-1">{{ $t('a_frequency') }}</th>
                                                <th class="text-left py-1">{{ $t('a_duration') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in rx.items" :key="item.id">
                                                <td class="py-1 text-gray-900">{{ item.medication_name }}</td>
                                                <td class="py-1 text-gray-500">{{ item.dosage || '-' }}</td>
                                                <td class="py-1 text-gray-500">{{ item.frequency || '-' }}</td>
                                                <td class="py-1 text-gray-500">{{ item.duration || '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <p v-else-if="!can('prescriptions.create')" class="text-sm text-gray-500 text-center py-8">{{ $t('a_no_prescriptions_yet') }}</p>

                        <!-- New Prescription Form -->
                        <div v-if="can('prescriptions.create')">
                            <button
                                v-if="!showNewPrescription"
                                @click="showNewPrescription = true"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                New Prescription
                            </button>

                            <div v-if="showNewPrescription" class="border border-gray-200 rounded-lg p-5 bg-gray-50 mt-2">
                                <h4 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('a_create_prescription') }}</h4>
                                <form @submit.prevent="submitPrescription" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Doctor <span class="text-red-500">*</span></label>
                                            <SearchableSelect v-model="prescriptionForm.doctor_id" :options="doctorOptions" placeholder="Select Doctor" searchPlaceholder="Search doctors..." />
                                            <p v-if="prescriptionForm.errors.doctor_id" class="mt-1 text-xs text-red-600">{{ prescriptionForm.errors.doctor_id }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                                            <input v-model="prescriptionForm.diagnosis" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Diagnosis" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                            <input v-model="prescriptionForm.notes" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Optional notes" />
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
                                                <input v-model="item.dosage" type="text" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Dosage" />
                                            </div>
                                            <div class="w-32">
                                                <input v-model="item.frequency" type="text" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Frequency" />
                                            </div>
                                            <div class="w-24">
                                                <input v-model="item.duration" type="text" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Duration" />
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
                                            {{ prescriptionForm.processing ? $t('a_saving') : $t('a_save_prescription') }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="showNewPrescription = false"
                                            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium transition hover:bg-gray-50"
                                        >
                                            {{ $t('a_cancel') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Photos Tab -->
                    <div v-if="activeTab === 'photos'">
                        <div v-if="patient.photos?.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="photo in patient.photos" :key="photo.id" class="relative group">
                                <img :src="`/storage/${photo.photo_path}`" class="w-full h-40 object-cover rounded-lg" />
                                <div v-if="photo.caption" class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs p-2 rounded-b-lg">{{ photo.caption }}</div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 text-center py-8">No photos yet.</p>
                    </div>

                    <!-- Dental Tab -->
                    <div v-if="activeTab === 'dental'" class="space-y-6">
                        <!-- Stats Cards -->
                        <div v-if="dentalData?.stats" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-white rounded-xl border border-cyan-100 p-4">
                                <p class="text-2xl font-bold text-cyan-700">{{ dentalData.stats.total_treatments }}</p>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'إجمالي العلاجات' : 'Total Treatments' }}</p>
                            </div>
                            <div class="bg-white rounded-xl border border-green-100 p-4">
                                <p class="text-2xl font-bold text-green-600">{{ dentalData.stats.completed_treatments }}</p>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
                            </div>
                            <div class="bg-white rounded-xl border border-purple-100 p-4">
                                <p class="text-2xl font-bold text-purple-600">{{ dentalData.stats.active_plans }}</p>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'خطط نشطة' : 'Active Plans' }}</p>
                            </div>
                            <div class="bg-white rounded-xl border border-amber-100 p-4">
                                <p class="text-2xl font-bold text-amber-600">{{ dentalData.stats.pending_lab_orders }}</p>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'طلبات معمل معلقة' : 'Pending Lab Orders' }}</p>
                            </div>
                        </div>

                        <!-- Dental Risk Flags -->
                        <div v-if="dentalRiskFlags.length" class="bg-gradient-to-r from-red-50 via-amber-50 to-red-50 rounded-xl border-2 border-red-200 p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                </div>
                                <h3 class="text-sm font-bold text-red-800">{{ isRtl ? 'تنبيهات طبية' : 'Medical Alerts' }}</h3>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="flag in dentalRiskFlags" :key="flag.key"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border"
                                    :class="flag.severity === 'high' ? 'bg-red-100 text-red-800 border-red-200' : flag.severity === 'medium' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-blue-100 text-blue-800 border-blue-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="flag.severity === 'high' ? 'bg-red-500' : flag.severity === 'medium' ? 'bg-amber-500' : 'bg-blue-500'"></span>
                                    {{ isRtl ? flag.label_ar : flag.label_en }}
                                </span>
                            </div>
                        </div>

                        <!-- Dental Medical History -->
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-teal-50 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'السجل الطبي للأسنان' : 'Dental Medical History' }}</h3>
                                </div>
                                <button v-if="can('patients.update')" @click="showDentalMedicalEdit = !showDentalMedicalEdit"
                                    class="text-xs font-medium px-3 py-1.5 rounded-lg transition-all"
                                    :class="showDentalMedicalEdit ? 'text-gray-500 bg-gray-100 hover:bg-gray-200' : 'text-[#C4A265] hover:text-[#A68B52] hover:bg-[#C4A265]/5'"
                                >{{ showDentalMedicalEdit ? (isRtl ? 'إلغاء' : 'Cancel') : (isRtl ? 'تعديل' : 'Edit') }}</button>
                            </div>

                            <!-- View Mode -->
                            <div v-if="!showDentalMedicalEdit" class="p-5">
                                <div v-if="!dentalData?.medicalHistory || Object.values(dentalData.medicalHistory).every(v => !v)" class="text-center py-6">
                                    <p class="text-sm text-gray-400">{{ isRtl ? 'لم يتم تسجيل السجل الطبي بعد' : 'No dental medical history recorded yet' }}</p>
                                    <button v-if="can('patients.update')" @click="showDentalMedicalEdit = true" class="mt-2 text-xs font-medium text-[#C4A265] hover:text-[#A68B52]">
                                        {{ isRtl ? '+ إضافة السجل الطبي' : '+ Add Medical History' }}
                                    </button>
                                </div>
                                <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                    <div v-if="dentalData.medicalHistory.has_dental_anxiety" class="flex items-center gap-2 p-2 rounded-lg bg-blue-50 border border-blue-100">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span class="text-xs text-blue-700">{{ isRtl ? 'قلق الأسنان' : 'Dental Anxiety' }}: {{ dentalData.medicalHistory.dental_anxiety_level || '-' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.latex_allergy" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-100">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-xs text-red-700">{{ isRtl ? 'حساسية اللاتكس' : 'Latex Allergy' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.anesthesia_complications" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-100">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-xs text-red-700">{{ isRtl ? 'مضاعفات التخدير' : 'Anesthesia Issues' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.is_pregnant" class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 border border-amber-100">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span class="text-xs text-amber-700">{{ isRtl ? 'حامل' : 'Pregnant' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.is_breastfeeding" class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 border border-amber-100">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span class="text-xs text-amber-700">{{ isRtl ? 'مرضعة' : 'Breastfeeding' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.has_bleeding_disorder" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-100">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-xs text-red-700">{{ isRtl ? 'اضطراب نزيف' : 'Bleeding Disorder' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.takes_blood_thinners" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-100">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-xs text-red-700">{{ isRtl ? 'مميعات الدم' : 'Blood Thinners' }}: {{ dentalData.medicalHistory.blood_thinner_name || '-' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.has_heart_condition" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-100">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-xs text-red-700">{{ isRtl ? 'مشاكل قلبية' : 'Heart Condition' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.has_diabetes" class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 border border-amber-100">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span class="text-xs text-amber-700">{{ isRtl ? 'سكري' : 'Diabetes' }}: {{ dentalData.medicalHistory.diabetes_type || '-' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.has_hepatitis" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-100">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-xs text-red-700">
                                            {{ isRtl ? 'التهاب كبد' : 'Hepatitis' }}
                                            <template v-if="!isRestricted(dentalData.medicalHistory.hepatitis_type)"> {{ dentalData.medicalHistory.hepatitis_type || '' }}</template>
                                            <span v-else class="text-red-400 italic ms-1">{{ isRtl ? '[محدود الوصول]' : '[Restricted]' }}</span>
                                        </span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.has_hiv" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 border border-red-100">
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-xs text-red-700">{{ isRtl ? 'HIV' : 'HIV Positive' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.is_smoker" class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 border border-gray-200">
                                        <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                                        <span class="text-xs text-gray-700">{{ isRtl ? 'مدخن' : 'Smoker' }}: {{ dentalData.medicalHistory.smoking_frequency || '-' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.jaw_problems" class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 border border-amber-100">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span class="text-xs text-amber-700">{{ isRtl ? 'مشاكل الفك (TMJ)' : 'Jaw Problems (TMJ)' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.teeth_grinding" class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 border border-amber-100">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span class="text-xs text-amber-700">{{ isRtl ? 'صرير الأسنان' : 'Teeth Grinding' }}</span>
                                    </div>
                                    <div v-if="dentalData.medicalHistory.last_dental_visit" class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 border border-gray-200">
                                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                        <span class="text-xs text-gray-600">{{ isRtl ? 'آخر زيارة' : 'Last Visit' }}: {{ dentalData.medicalHistory.last_dental_visit }}</span>
                                    </div>
                                </div>
                                <p v-if="dentalData?.medicalHistory?.dental_medical_notes" class="mt-3 text-xs border-t border-gray-100 pt-3" :class="isRestricted(dentalData.medicalHistory.dental_medical_notes) ? 'text-red-400 italic' : 'text-gray-500 italic'">
                                    {{ isRestricted(dentalData.medicalHistory.dental_medical_notes) ? (isRtl ? 'ملاحظات طبية — يتطلب صلاحية الوصول للبيانات الحساسة' : 'Medical notes — requires sensitive data permission') : dentalData.medicalHistory.dental_medical_notes }}
                                </p>

                                <!-- Restricted Access Notice -->
                                <div v-if="!canViewSensitive" class="mt-3 p-3 bg-red-50/50 border border-red-100 rounded-lg flex items-start gap-2">
                                    <svg class="w-4 h-4 text-red-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <p class="text-[11px] text-red-600">
                                        {{ isRtl ? 'بعض البيانات الطبية الحساسة مخفية. تحتاج صلاحية "عرض البيانات الطبية الحساسة" للوصول الكامل.' : 'Some sensitive medical data is restricted. You need "View Sensitive Medical Data" permission for full access.' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-[2000px]" leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <form v-if="showDentalMedicalEdit" @submit.prevent="saveDentalMedical" class="p-5 space-y-5 bg-gray-50/50">
                                    <!-- Conditions Grid -->
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'الحالات الطبية' : 'Medical Conditions' }}</h4>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                            <label class="flex items-center gap-2 p-2.5 bg-white rounded-lg border border-gray-200 hover:border-[#C4A265]/40 cursor-pointer transition-all">
                                                <input type="checkbox" v-model="dentalMedicalForm.latex_allergy" class="rounded border-gray-300 text-red-500 focus:ring-red-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'حساسية اللاتكس' : 'Latex Allergy' }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 bg-white rounded-lg border border-gray-200 hover:border-[#C4A265]/40 cursor-pointer transition-all">
                                                <input type="checkbox" v-model="dentalMedicalForm.anesthesia_complications" class="rounded border-gray-300 text-red-500 focus:ring-red-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'مضاعفات التخدير' : 'Anesthesia Issues' }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 bg-white rounded-lg border border-gray-200 hover:border-[#C4A265]/40 cursor-pointer transition-all">
                                                <input type="checkbox" v-model="dentalMedicalForm.has_bleeding_disorder" class="rounded border-gray-300 text-red-500 focus:ring-red-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'اضطراب نزيف' : 'Bleeding Disorder' }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 bg-white rounded-lg border border-gray-200 hover:border-[#C4A265]/40 cursor-pointer transition-all">
                                                <input type="checkbox" v-model="dentalMedicalForm.has_heart_condition" class="rounded border-gray-300 text-red-500 focus:ring-red-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'مشاكل قلبية' : 'Heart Condition' }}</span>
                                            </label>
                                            <label :class="['flex items-center gap-2 p-2.5 bg-white rounded-lg border transition-all', canUpdateSensitive ? 'border-gray-200 hover:border-[#C4A265]/40 cursor-pointer' : 'border-red-100 opacity-60 cursor-not-allowed']">
                                                <input type="checkbox" v-model="dentalMedicalForm.has_hepatitis" :disabled="!canUpdateSensitive" class="rounded border-gray-300 text-red-500 focus:ring-red-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'التهاب كبد' : 'Hepatitis' }}</span>
                                                <svg v-if="!canUpdateSensitive" class="w-3 h-3 text-red-400 ms-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            </label>
                                            <label :class="['flex items-center gap-2 p-2.5 bg-white rounded-lg border transition-all', canUpdateSensitive ? 'border-gray-200 hover:border-[#C4A265]/40 cursor-pointer' : 'border-red-100 opacity-60 cursor-not-allowed']">
                                                <input type="checkbox" v-model="dentalMedicalForm.has_hiv" :disabled="!canUpdateSensitive" class="rounded border-gray-300 text-red-500 focus:ring-red-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'فيروس نقص المناعة' : 'HIV' }}</span>
                                                <svg v-if="!canUpdateSensitive" class="w-3 h-3 text-red-400 ms-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 bg-white rounded-lg border border-gray-200 hover:border-[#C4A265]/40 cursor-pointer transition-all">
                                                <input type="checkbox" v-model="dentalMedicalForm.is_pregnant" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'حامل' : 'Pregnant' }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 bg-white rounded-lg border border-gray-200 hover:border-[#C4A265]/40 cursor-pointer transition-all">
                                                <input type="checkbox" v-model="dentalMedicalForm.is_breastfeeding" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'مرضعة' : 'Breastfeeding' }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 bg-white rounded-lg border border-gray-200 hover:border-[#C4A265]/40 cursor-pointer transition-all">
                                                <input type="checkbox" v-model="dentalMedicalForm.jaw_problems" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'مشاكل الفك (TMJ)' : 'Jaw Problems (TMJ)' }}</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 bg-white rounded-lg border border-gray-200 hover:border-[#C4A265]/40 cursor-pointer transition-all">
                                                <input type="checkbox" v-model="dentalMedicalForm.teeth_grinding" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500/30" />
                                                <span class="text-xs text-gray-700">{{ isRtl ? 'صرير الأسنان' : 'Teeth Grinding' }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Conditional Fields -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="flex items-center gap-2 mb-2">
                                                <input type="checkbox" v-model="dentalMedicalForm.takes_blood_thinners" class="rounded border-gray-300 text-red-500 focus:ring-red-500/30" />
                                                <span class="text-xs font-medium text-gray-700">{{ isRtl ? 'يأخذ مميعات دم' : 'Takes Blood Thinners' }}</span>
                                            </label>
                                            <input v-if="dentalMedicalForm.takes_blood_thinners" v-model="dentalMedicalForm.blood_thinner_name" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'اسم الدواء...' : 'Medication name...'" />
                                        </div>
                                        <div>
                                            <label class="flex items-center gap-2 mb-2">
                                                <input type="checkbox" v-model="dentalMedicalForm.has_diabetes" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500/30" />
                                                <span class="text-xs font-medium text-gray-700">{{ isRtl ? 'مريض سكري' : 'Has Diabetes' }}</span>
                                            </label>
                                            <select v-if="dentalMedicalForm.has_diabetes" v-model="dentalMedicalForm.diabetes_type" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                                <option value="">{{ isRtl ? 'اختر النوع' : 'Select type' }}</option>
                                                <option value="type_1">{{ isRtl ? 'النوع الأول' : 'Type 1' }}</option>
                                                <option value="type_2">{{ isRtl ? 'النوع الثاني' : 'Type 2' }}</option>
                                                <option value="gestational">{{ isRtl ? 'سكر الحمل' : 'Gestational' }}</option>
                                            </select>
                                        </div>
                                        <div v-if="dentalMedicalForm.has_hepatitis">
                                            <label class="text-xs font-medium text-gray-700 mb-1 block">{{ isRtl ? 'نوع التهاب الكبد' : 'Hepatitis Type' }}</label>
                                            <select v-model="dentalMedicalForm.hepatitis_type" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                                <option value="">{{ isRtl ? 'اختر' : 'Select' }}</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="flex items-center gap-2 mb-2">
                                                <input type="checkbox" v-model="dentalMedicalForm.has_dental_anxiety" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500/30" />
                                                <span class="text-xs font-medium text-gray-700">{{ isRtl ? 'قلق من الأسنان' : 'Dental Anxiety' }}</span>
                                            </label>
                                            <select v-if="dentalMedicalForm.has_dental_anxiety" v-model="dentalMedicalForm.dental_anxiety_level" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                                <option value="none">{{ isRtl ? 'لا يوجد' : 'None' }}</option>
                                                <option value="mild">{{ isRtl ? 'خفيف' : 'Mild' }}</option>
                                                <option value="moderate">{{ isRtl ? 'متوسط' : 'Moderate' }}</option>
                                                <option value="severe">{{ isRtl ? 'شديد' : 'Severe' }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="flex items-center gap-2 mb-2">
                                                <input type="checkbox" v-model="dentalMedicalForm.is_smoker" class="rounded border-gray-300 text-gray-500 focus:ring-gray-500/30" />
                                                <span class="text-xs font-medium text-gray-700">{{ isRtl ? 'مدخن' : 'Smoker' }}</span>
                                            </label>
                                            <select v-if="dentalMedicalForm.is_smoker" v-model="dentalMedicalForm.smoking_frequency" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                                <option value="">{{ isRtl ? 'اختر' : 'Select' }}</option>
                                                <option value="light">{{ isRtl ? 'خفيف' : 'Light' }}</option>
                                                <option value="moderate">{{ isRtl ? 'متوسط' : 'Moderate' }}</option>
                                                <option value="heavy">{{ isRtl ? 'كثيف' : 'Heavy' }}</option>
                                                <option value="former">{{ isRtl ? 'سابق' : 'Former' }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-700 mb-1 block">{{ isRtl ? 'آخر زيارة أسنان' : 'Last Dental Visit' }}</label>
                                            <input v-model="dentalMedicalForm.last_dental_visit" type="date" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                        </div>
                                    </div>

                                    <!-- Text Fields -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-xs font-medium text-gray-700 mb-1 block">{{ isRtl ? 'عمليات أسنان سابقة' : 'Previous Dental Surgeries' }}</label>
                                            <textarea v-model="dentalMedicalForm.previous_dental_surgeries" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'تفاصيل العمليات...' : 'Surgery details...'"></textarea>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-700 mb-1 block">{{ isRtl ? 'ملاحظات التخدير' : 'Anesthesia Notes' }}</label>
                                            <textarea v-model="dentalMedicalForm.anesthesia_notes" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'ملاحظات...' : 'Notes...'"></textarea>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-700 mb-1 block">{{ isRtl ? 'ملاحظات طبية إضافية' : 'Additional Medical Notes' }}</label>
                                        <textarea v-model="dentalMedicalForm.dental_medical_notes" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'ملاحظات...' : 'Notes...'"></textarea>
                                    </div>

                                    <div class="flex gap-2 pt-2">
                                        <button type="submit" :disabled="dentalMedicalForm.processing"
                                            class="px-5 py-2 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] rounded-lg transition-colors shadow-sm disabled:opacity-50"
                                        >{{ dentalMedicalForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save') }}</button>
                                        <button type="button" @click="showDentalMedicalEdit = false" class="px-4 py-2 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                    </div>
                                </form>
                            </Transition>
                        </div>

                        <!-- Quick Links -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <Link :href="`/admin/dental/chart/${patient.id}`" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-[#C4A265]/40 hover:bg-[#C4A265]/5 transition group">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center border border-[#C4A265]/20" style="background: rgba(196,162,101,0.08);">
                                    <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-[#C4A265]">{{ isRtl ? 'المخطط' : 'Chart' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ dentalData?.charts?.length || 0 }} {{ isRtl ? 'سن مسجل' : 'teeth recorded' }}</p>
                                </div>
                            </Link>
                            <Link :href="`/admin/dental/treatment-plans?patient_id=${patient.id}`" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-purple-300 hover:bg-purple-50/50 transition group">
                                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center"><svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg></div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-purple-600">{{ isRtl ? 'الخطط' : 'Plans' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ dentalData?.plans?.length || 0 }} {{ isRtl ? 'خطة' : 'plans' }}</p>
                                </div>
                            </Link>
                            <Link :href="`/admin/dental/xrays?patient_id=${patient.id}`" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-cyan-300 hover:bg-cyan-50/50 transition group">
                                <div class="w-10 h-10 rounded-lg bg-cyan-50 flex items-center justify-center"><svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-cyan-600">{{ isRtl ? 'الأشعة' : 'X-Rays' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ dentalData?.xrays?.length || 0 }} {{ isRtl ? 'صورة' : 'images' }}</p>
                                </div>
                            </Link>
                            <Link :href="`/admin/dental/lab-orders?search=${patient.full_name}`" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-amber-300 hover:bg-amber-50/50 transition group">
                                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center"><svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg></div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-amber-600">{{ isRtl ? 'المعمل' : 'Lab Orders' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ dentalData?.labOrders?.length || 0 }} {{ isRtl ? 'طلب' : 'orders' }}</p>
                                </div>
                            </Link>
                        </div>

                        <!-- Recent Treatments -->
                        <div v-if="dentalData?.treatments?.length" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'آخر العلاجات' : 'Recent Treatments' }}</h3>
                                <span class="text-xs text-gray-400">{{ dentalData.treatments.length }} {{ isRtl ? 'من' : 'of' }} {{ dentalData.stats?.total_treatments }}</span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <div v-for="t in dentalData.treatments" :key="t.id" class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50/50">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                                            :class="t.status === 'completed' ? 'bg-green-50 text-green-600' : t.status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-500'">
                                            {{ t.tooth_number || '—' }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-800 truncate">{{ t.treatment_type?.replace(/_/g, ' ') }}</p>
                                            <p class="text-[10px] text-gray-400">
                                                Dr. {{ t.doctor?.name_en || t.doctor?.name_ar || '-' }}
                                                <span v-if="t.created_at"> · {{ t.created_at?.substring(0, 10) }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span v-if="t.lab_order" class="px-1.5 py-0.5 text-[9px] font-semibold rounded bg-amber-50 text-amber-600 border border-amber-200">
                                            {{ isRtl ? 'معمل' : 'Lab' }}: {{ t.lab_order.status }}
                                        </span>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize"
                                            :class="t.status === 'completed' ? 'bg-green-100 text-green-700' : t.status === 'in_progress' ? 'bg-blue-100 text-blue-700' : t.status === 'planned' ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-700'">
                                            {{ t.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Treatment Plans -->
                        <div v-if="dentalData?.plans?.length" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}</h3>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <Link v-for="plan in dentalData.plans" :key="plan.id"
                                    :href="`/admin/dental/treatment-plans/${plan.id}`"
                                    class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-purple-50/30 transition group"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-800 group-hover:text-purple-600 truncate">{{ plan.title_en || plan.title_ar || (isRtl ? 'خطة علاج' : 'Treatment Plan') }}</p>
                                        <p class="text-[10px] text-gray-400">
                                            {{ plan.treatments_count }} {{ isRtl ? 'علاج' : 'treatments' }}
                                            <span v-if="plan.doctor"> · Dr. {{ plan.doctor.name_en || plan.doctor.name_ar }}</span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <div v-if="plan.estimated_sessions > 0" class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-purple-500 rounded-full" :style="{ width: Math.min(100, Math.round((plan.completed_sessions / plan.estimated_sessions) * 100)) + '%' }"></div>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize"
                                            :class="plan.status === 'completed' ? 'bg-green-100 text-green-700' : plan.status === 'approved' ? 'bg-blue-100 text-blue-700' : plan.status === 'in_progress' ? 'bg-cyan-100 text-cyan-700' : plan.status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                                            {{ plan.status }}
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- Lab Orders -->
                        <div v-if="dentalData?.labOrders?.length" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'طلبات المعمل' : 'Lab Orders' }}</h3>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <div v-for="lo in dentalData.labOrders" :key="lo.id" class="px-5 py-3 flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-800">{{ lo.item_type?.replace(/_/g, ' ') }} <span v-if="lo.tooth_number" class="text-gray-400">#{{ lo.tooth_number }}</span></p>
                                        <p class="text-[10px] text-gray-400">
                                            {{ lo.lab_name || '-' }} · {{ lo.order_date }}
                                            <span v-if="lo.doctor"> · Dr. {{ lo.doctor.name_en || lo.doctor.name_ar }}</span>
                                        </p>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize"
                                        :class="lo.status === 'delivered' || lo.status === 'completed' ? 'bg-green-100 text-green-700' : lo.status === 'ready' ? 'bg-blue-100 text-blue-700' : lo.status === 'adjustment' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'">
                                        {{ lo.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Dental Visits -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'زيارات الأسنان' : 'Dental Visits' }}</h3>
                            <div class="space-y-2">
                                <Link v-for="visit in patient.visits?.filter(v => v.module === 'dental')" :key="visit.id"
                                    :href="`/admin/visits/${visit.id}`"
                                    class="flex items-center justify-between p-4 rounded-lg border border-gray-200 hover:border-[#C4A265]/40 hover:bg-[#C4A265]/5 transition group"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white" style="background-color: #C4A265;">
                                            {{ visit.visit_date?.substring(8, 10) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 group-hover:text-[#C4A265]">
                                                {{ visit.service?.name_en || visit.service?.name || visit.visit_type }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ visit.visit_date }} · Dr. {{ visit.doctor?.name_en || visit.doctor?.name_ar || '-' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize" :class="statusColors[visit.status]">{{ visit.status }}</span>
                                </Link>
                                <p v-if="!patient.visits?.filter(v => v.module === 'dental')?.length" class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا توجد زيارات أسنان' : 'No dental visits yet' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pediatric Tab -->
                    <div v-if="activeTab === 'pediatric'" class="space-y-6">
                        <!-- Stats Cards -->
                        <div v-if="pediatricData?.stats" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div class="bg-white rounded-xl border border-green-100 p-4">
                                <p class="text-2xl font-bold text-green-600">{{ pediatricData.stats.total_visits ?? 0 }}</p>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
                            </div>
                            <div class="bg-white rounded-xl border border-emerald-100 p-4">
                                <p class="text-2xl font-bold text-emerald-600">{{ pediatricData.stats.total_vaccinations ?? 0 }}</p>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'التطعيمات' : 'Vaccinations' }}</p>
                            </div>
                            <div class="bg-white rounded-xl border border-teal-100 p-4">
                                <p class="text-2xl font-bold text-teal-600">{{ pediatricData.stats.growth_records ?? 0 }}</p>
                                <p class="text-xs text-gray-500">{{ isRtl ? 'سجلات النمو' : 'Growth Records' }}</p>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <Link :href="`/admin/pediatric/patients?search=${encodeURIComponent(patient.full_name || '')}`" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-green-300 hover:bg-green-50/50 transition group">
                                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800 group-hover:text-green-600">{{ isRtl ? 'مرضى الأطفال' : 'Pediatric Patients' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ isRtl ? 'القائمة الكاملة' : 'Full list' }}</p>
                                </div>
                            </Link>
                            <Link :href="`/admin/pediatric/vaccinations?search=${encodeURIComponent(patient.full_name || '')}`" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition group">
                                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800 group-hover:text-emerald-600">{{ isRtl ? 'التطعيمات' : 'Vaccinations' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ pediatricData?.stats?.total_vaccinations ?? 0 }} {{ isRtl ? 'تطعيم' : 'records' }}</p>
                                </div>
                            </Link>
                            <Link :href="`/admin/pediatric/growth?search=${encodeURIComponent(patient.full_name || '')}`" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 transition group">
                                <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800 group-hover:text-teal-600">{{ isRtl ? 'سجل النمو' : 'Growth Chart' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ pediatricData?.stats?.growth_records ?? 0 }} {{ isRtl ? 'سجل' : 'records' }}</p>
                                </div>
                            </Link>
                            <Link :href="`/admin/pediatric/visits?patient_id=${patient.id}`" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-green-300 hover:bg-green-50/50 transition group">
                                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800 group-hover:text-green-600">{{ isRtl ? 'الزيارات' : 'Visits' }}</p>
                                    <p class="text-[10px] text-gray-400">{{ isRtl ? 'كل الزيارات' : 'All visits' }}</p>
                                </div>
                            </Link>
                        </div>

                        <!-- ═══════════════════════════════════════ -->
                        <!-- Growth Chart + Recording Form          -->
                        <!-- ═══════════════════════════════════════ -->
                        <div class="bg-gradient-to-br from-teal-50/50 to-emerald-50/50 rounded-2xl border border-teal-100 p-1 overflow-hidden">
                            <div class="flex items-center justify-between px-5 py-3 mb-1">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-sm">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 14l4-4 4 4 4-8" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'مخطط النمو' : 'Growth Chart' }}</h3>
                                        <p class="text-xs text-gray-500">{{ pediatricData?.growthRecords?.length || 0 }} {{ isRtl ? 'قياس مسجّل' : 'measurements recorded' }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="showGrowthForm = !showGrowthForm"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 shadow-sm transition"
                                >
                                    <svg v-if="!showGrowthForm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ showGrowthForm ? (isRtl ? 'إلغاء' : 'Cancel') : (isRtl ? 'تسجيل قياس' : 'Add Measurement') }}
                                </button>
                            </div>

                            <!-- Growth Recording Form (collapsible) -->
                            <div v-if="showGrowthForm" class="bg-white mx-2 mb-2 p-4 rounded-xl border border-teal-200 shadow-sm">
                                <form @submit.prevent="submitGrowth" class="space-y-3">
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }} *</label>
                                            <input v-model="growthForm.measurement_date" type="date" :max="new Date().toISOString().split('T')[0]" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-300 focus:border-teal-500" />
                                            <p v-if="growthForm.errors.measurement_date" class="text-[10px] text-red-500 mt-0.5">{{ growthForm.errors.measurement_date }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">{{ isRtl ? 'الوزن (kg)' : 'Weight (kg)' }}</label>
                                            <input v-model="growthForm.weight_kg" type="number" step="0.01" min="0" max="200" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-300 focus:border-teal-500" placeholder="0.00" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">{{ isRtl ? 'الطول (cm)' : 'Height (cm)' }}</label>
                                            <input v-model="growthForm.height_cm" type="number" step="0.1" min="0" max="250" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-300 focus:border-teal-500" placeholder="0.0" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">{{ isRtl ? 'محيط الرأس (cm)' : 'Head (cm)' }}</label>
                                            <input v-model="growthForm.head_circumference_cm" type="number" step="0.1" min="0" max="100" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-300 focus:border-teal-500" placeholder="0.0" />
                                        </div>
                                        <div class="flex items-end">
                                            <button type="submit" :disabled="growthForm.processing" class="w-full px-4 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 text-white text-sm font-bold rounded-lg hover:from-teal-600 hover:to-emerald-700 transition shadow-sm disabled:opacity-50">
                                                {{ growthForm.processing ? (isRtl ? 'جاري...' : 'Saving...') : (isRtl ? 'حفظ القياس' : 'Save') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                        <textarea v-model="growthForm.notes" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-300 focus:border-teal-500" :placeholder="isRtl ? 'ملاحظات اختيارية...' : 'Optional notes...'"></textarea>
                                    </div>
                                    <p class="text-[11px] text-teal-700 bg-teal-50 px-3 py-2 rounded-lg flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ isRtl ? 'سيتم حساب BMI ومعدلات WHO تلقائياً' : 'BMI and WHO percentiles will be calculated automatically' }}
                                    </p>
                                </form>
                            </div>

                            <!-- Chart -->
                            <PediatricGrowthChart
                                v-if="pediatricData?.growthRecords?.length"
                                :records="pediatricData.growthRecords"
                                :gender="patient.gender"
                                :height="340"
                            />

                            <!-- Measurements Log Table -->
                            <div v-if="pediatricData?.growthRecords?.length" class="mx-2 mb-2 mt-3 bg-white rounded-xl border border-gray-100 overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                                    <h4 class="text-xs font-bold text-gray-700">{{ isRtl ? 'سجل القياسات' : 'Measurements Log' }}</h4>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead class="bg-gray-50/50">
                                            <tr>
                                                <th class="text-start px-4 py-2 font-semibold text-gray-500">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                                <th class="text-center px-4 py-2 font-semibold text-gray-500">{{ isRtl ? 'العمر' : 'Age' }}</th>
                                                <th class="text-center px-4 py-2 font-semibold text-gray-500">{{ isRtl ? 'الوزن' : 'Weight' }}</th>
                                                <th class="text-center px-4 py-2 font-semibold text-gray-500">{{ isRtl ? 'الطول' : 'Height' }}</th>
                                                <th class="text-center px-4 py-2 font-semibold text-gray-500 hidden sm:table-cell">{{ isRtl ? 'الرأس' : 'Head' }}</th>
                                                <th class="text-center px-4 py-2 font-semibold text-gray-500 hidden md:table-cell">BMI</th>
                                                <th class="text-center px-4 py-2 font-semibold text-gray-500"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="r in [...pediatricData.growthRecords].reverse()" :key="r.id" class="border-t border-gray-50 hover:bg-teal-50/30">
                                                <td class="px-4 py-2.5 font-medium text-gray-700">{{ r.measurement_date?.substring(0, 10) }}</td>
                                                <td class="px-4 py-2.5 text-center text-gray-600">{{ Math.round(r.age_months || 0) }}m</td>
                                                <td class="px-4 py-2.5 text-center">
                                                    <span class="text-gray-800">{{ r.weight_kg || '-' }}</span>
                                                    <span v-if="r.weight_percentile" class="ml-1 text-[9px] font-semibold" :class="r.weight_percentile < 5 || r.weight_percentile > 95 ? 'text-red-500' : r.weight_percentile < 15 || r.weight_percentile > 85 ? 'text-amber-500' : 'text-emerald-600'">P{{ Math.round(r.weight_percentile) }}</span>
                                                </td>
                                                <td class="px-4 py-2.5 text-center">
                                                    <span class="text-gray-800">{{ r.height_cm || '-' }}</span>
                                                    <span v-if="r.height_percentile" class="ml-1 text-[9px] font-semibold" :class="r.height_percentile < 5 || r.height_percentile > 95 ? 'text-red-500' : r.height_percentile < 15 || r.height_percentile > 85 ? 'text-amber-500' : 'text-emerald-600'">P{{ Math.round(r.height_percentile) }}</span>
                                                </td>
                                                <td class="px-4 py-2.5 text-center text-gray-600 hidden sm:table-cell">{{ r.head_circumference_cm || '-' }}</td>
                                                <td class="px-4 py-2.5 text-center text-gray-600 hidden md:table-cell">{{ r.bmi || '-' }}</td>
                                                <td class="px-4 py-2.5 text-center">
                                                    <button @click="deleteGrowthRecord(r.id)" class="p-1 text-red-500 hover:bg-red-50 rounded transition" :title="isRtl ? 'حذف' : 'Delete'">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Latest Measurements Summary -->
                        <div v-if="pediatricData?.stats?.latest_weight || pediatricData?.stats?.latest_height" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-white rounded-xl border border-gray-100 p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                                    <p class="text-[11px] font-semibold text-gray-500">{{ isRtl ? 'الوزن' : 'Weight' }}</p>
                                </div>
                                <p class="text-xl font-bold text-gray-800">{{ pediatricData.stats.latest_weight || '-' }} <span class="text-xs text-gray-400">kg</span></p>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-100 p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v10m0 0h8m-8 0l4-4 4 4M12 3v4" /></svg>
                                    <p class="text-[11px] font-semibold text-gray-500">{{ isRtl ? 'الطول' : 'Height' }}</p>
                                </div>
                                <p class="text-xl font-bold text-gray-800">{{ pediatricData.stats.latest_height || '-' }} <span class="text-xs text-gray-400">cm</span></p>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-100 p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    <p class="text-[11px] font-semibold text-gray-500">BMI</p>
                                </div>
                                <p class="text-xl font-bold text-gray-800">{{ pediatricData.stats.latest_bmi || '-' }}</p>
                            </div>
                            <div class="bg-white rounded-xl border border-gray-100 p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p class="text-[11px] font-semibold text-gray-500">{{ isRtl ? 'آخر قياس' : 'Last' }}</p>
                                </div>
                                <p class="text-sm font-bold text-gray-800">{{ pediatricData.growthRecords[pediatricData.growthRecords.length - 1]?.measurement_date?.substring(0, 10) || '-' }}</p>
                            </div>
                        </div>

                        <!-- Empty state hint if no growth records -->
                        <div v-if="!pediatricData?.growthRecords?.length" class="bg-gradient-to-br from-teal-50/40 to-emerald-50/40 rounded-2xl border border-teal-100 p-6 text-center">
                            <div class="w-12 h-12 mx-auto rounded-xl bg-white flex items-center justify-center shadow-sm mb-2">
                                <svg class="w-6 h-6 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3v18h18M7 14l4-4 4 4 4-8" /></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700 mb-1">{{ isRtl ? 'ابدأ بتسجيل أول قياس' : 'Start by recording the first measurement' }}</p>
                            <p class="text-xs text-gray-500">{{ isRtl ? 'اضغط زر "تسجيل قياس" أعلى المخطط' : 'Click "Add Measurement" above the chart' }}</p>
                        </div>

                        <!-- Pediatric Visits -->
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'زيارات الأطفال' : 'Pediatric Visits' }}</h3>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <Link v-for="visit in patient.visits?.filter(v => v.module === 'pediatric')" :key="visit.id"
                                    :href="`/admin/visits/${visit.id}`"
                                    class="flex items-center gap-3 px-5 py-3 hover:bg-green-50/30 transition group"
                                >
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-green-50 text-green-600 font-bold text-xs">
                                        {{ visit.visit_date?.substring(8, 10) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800 group-hover:text-green-600">
                                            {{ visit.service?.name_en || visit.service?.name || visit.visit_type }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ visit.visit_date }} · Dr. {{ visit.doctor?.name_en || visit.doctor?.name_ar || '-' }}
                                        </p>
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize" :class="statusColors[visit.status]">{{ visit.status }}</span>
                                </Link>
                                <p v-if="!patient.visits?.filter(v => v.module === 'pediatric')?.length" class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا توجد زيارات أطفال' : 'No pediatric visits yet' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
