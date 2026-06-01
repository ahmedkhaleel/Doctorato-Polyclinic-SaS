<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visit: Object,
    doctors: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    dentalChart: Object,
    dentalXrays: Array,
    dentalConditions: Object,
    allTeeth: Object,
    treatmentTypes: Object,
    dentalPlans: Array,
});

const activeTab = ref('details');

const tabs = computed(() => {
    const baseTabs = [
        { id: 'details', key: 'a_visit_details' },
        { id: 'diagnosis', key: 'a_diagnosis_notes' },
        { id: 'photos', key: 'a_photos' },
        { id: 'prescription', key: 'a_prescription' },
        { id: 'invoice', key: 'a_invoice' },
    ];
    if (props.visit?.module === 'dental') {
        baseTabs.splice(2, 0, { id: 'dental', label: isRtl.value ? 'طب الأسنان' : 'Dental' });
    }
    return baseTabs;
});

const statusLabels = {
    waiting: 'Waiting',
    in_progress: 'In Progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const statusColors = {
    waiting: 'bg-amber-100 text-amber-800',
    in_progress: 'bg-slate-100 text-[#1B365D]',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800',
};

const visitTypeLabels = {
    consultation: 'Consultation',
    session: 'Session',
    follow_up: 'Follow-up',
};

const invoiceStatusColors = {
    paid: 'bg-emerald-100 text-emerald-800',
    partial: 'bg-amber-100 text-amber-800',
    unpaid: 'bg-red-100 text-red-800',
};

// Inline editing for visit scheduling (date / time / doctor / service)
const editingVisitDate = ref(false);
const visitDateForm = useForm({
    visit_date:     props.visit?.visit_date ? props.visit.visit_date.split('T')[0] : '',
    scheduled_time: props.visit?.scheduled_time || '',
    doctor_id:      props.visit?.doctor_id || '',
    service_id:     props.visit?.service_id || '',
});

function saveVisitDate() {
    // Cancelled visits go through the restore endpoint (which reopens
    // them as "waiting" + applies the new date/time/doctor/service in
    // the same transaction).
    const endpoint = props.visit.status === 'cancelled'
        ? `/admin/visits/${props.visit.id}/restore`
        : `/admin/visits/${props.visit.id}/details`;

    visitDateForm.post(endpoint, {
        preserveScroll: true,
        onSuccess: () => { editingVisitDate.value = false; },
    });
}

function cancelEditVisitDate() {
    visitDateForm.visit_date     = props.visit?.visit_date ? props.visit.visit_date.split('T')[0] : '';
    visitDateForm.scheduled_time = props.visit?.scheduled_time || '';
    visitDateForm.doctor_id      = props.visit?.doctor_id || '';
    visitDateForm.service_id     = props.visit?.service_id || '';
    editingVisitDate.value = false;
}

// Locked only for completed visits — cancelled visits can still be
// restored back to "waiting" via the same form (which routes to the
// /restore endpoint instead of /details).
const isLocked = computed(() => props.visit?.status === 'completed');
const isCancelled = computed(() => props.visit?.status === 'cancelled');

// Inline editing for diagnosis & notes
const editingDiagnosis = ref(false);
const diagnosisForm = useForm({
    diagnosis: props.visit?.diagnosis || '',
    doctor_notes: props.visit?.doctor_notes || '',
});

function saveDiagnosis() {
    diagnosisForm.put(`/admin/visits/${props.visit.id}/diagnosis`, {
        preserveScroll: true,
        onSuccess: () => {
            editingDiagnosis.value = false;
        },
    });
}

function cancelEditDiagnosis() {
    diagnosisForm.diagnosis = props.visit?.diagnosis || '';
    diagnosisForm.doctor_notes = props.visit?.doctor_notes || '';
    editingDiagnosis.value = false;
}

// Photo Upload
const photoForm = useForm({
    photo: null,
    caption: '',
    photo_type: 'other',
});

const photoPreview = ref(null);

function onPhotoSelected(e) {
    const file = e.target.files[0];
    if (file) {
        photoForm.photo = file;
        const reader = new FileReader();
        reader.onload = (ev) => { photoPreview.value = ev.target.result; };
        reader.readAsDataURL(file);
    }
}

function uploadPhoto() {
    photoForm.post(`/admin/visits/${props.visit.id}/photos`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            photoForm.reset();
            photoPreview.value = null;
        },
    });
}

// Prescription Creation
const showPrescriptionForm = ref(false);
const prescriptionForm = useForm({
    diagnosis: '',
    notes: '',
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
});

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
            visit_id: props.visit.id,
            patient_id: props.visit.patient_id,
            doctor_id: props.visit.doctor_id,
        }))
        .post(`/admin/prescriptions`, {
            preserveScroll: true,
            onSuccess: () => {
                prescriptionForm.reset();
                prescriptionForm.items = [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }];
                showPrescriptionForm.value = false;
            },
        });
}

// Prescription Edit & Delete
const editingPrescriptionId = ref(null);
const editPrescriptionForm = useForm({
    diagnosis: '',
    notes: '',
    items: [],
});

function startEditPrescription(rx) {
    editingPrescriptionId.value = rx.id;
    editPrescriptionForm.diagnosis = rx.diagnosis || '';
    editPrescriptionForm.notes = rx.notes || '';
    editPrescriptionForm.items = rx.items?.length
        ? rx.items.map(i => ({
            medication_name: i.medication_name || '',
            dosage: i.dosage || '',
            frequency: i.frequency || '',
            duration: i.duration || '',
            instructions: i.instructions || '',
        }))
        : [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }];
}

function cancelEditPrescription() {
    editingPrescriptionId.value = null;
    editPrescriptionForm.reset();
}

function addEditPrescriptionItem() {
    editPrescriptionForm.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' });
}

function removeEditPrescriptionItem(index) {
    if (editPrescriptionForm.items.length > 1) {
        editPrescriptionForm.items.splice(index, 1);
    }
}

function submitEditPrescription(rxId) {
    editPrescriptionForm.put(`/admin/prescriptions/${rxId}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingPrescriptionId.value = null;
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

// Status Actions
function startVisit() {
    router.post(`/admin/visits/${props.visit.id}/start`, {}, {
        preserveScroll: true,
    });
}

function completeVisit() {
    router.post(`/admin/visits/${props.visit.id}/complete`, {}, {
        preserveScroll: true,
    });
}

function cancelVisit() {
    if (window.confirm('Are you sure you want to cancel this visit?')) {
        router.post(`/admin/visits/${props.visit.id}/cancel`, {}, {
            preserveScroll: true,
        });
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

function formatDateTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

</script>

<template>
    <AdminLayout :title="`Visit: ${visit.patient?.full_name}`">
        <div class="space-y-6">
            <!-- ═════════ Navy Hero Header ═════════ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
                <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="relative p-4 md:p-6 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                    <div class="flex items-start gap-3 md:gap-4 min-w-0">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center text-white text-xl font-extrabold flex-shrink-0 shadow-lg">
                            {{ visit.patient?.full_name?.charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'زيارة' : 'Visit' }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight truncate">{{ visit.patient?.full_name }}</h1>
                                <span :class="statusColors[visit.status]" class="px-3 py-1 text-xs font-semibold rounded-full ring-1 ring-white/20">
                                    {{ {waiting: $t('a_waiting'), in_progress: $t('a_in_progress'), completed: $t('a_completed'), cancelled: $t('a_cancelled')}[visit.status] || visit.status }}
                                </span>
                            </div>
                            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs md:text-sm text-white/70 mt-1">
                                <span>{{ formatDate(visit.visit_date) }}</span>
                                <span>&middot;</span>
                                <Link v-if="visit.doctor && can('doctors.view')" :href="`/admin/doctors/${visit.doctor.id}`" class="hover:underline text-[#C4A265]">Dr. {{ $localized(visit.doctor, 'name') }}</Link>
                                <span v-else>Dr. {{ visit.doctor ? $localized(visit.doctor, 'name') : $t('a_unknown') }}</span>
                                <span>&middot;</span>
                                <span>{{ {consultation: $t('a_consultation'), session: $t('a_session'), follow_up: $t('a_follow_up')}[visit.visit_type] || visit.visit_type }}</span>
                                <span v-if="visit.service">&middot; {{ $localized(visit.service, 'name') }}</span>
                                <span v-if="visit.session_number">&middot; Session #{{ visit.session_number }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                    <button
                        v-if="visit.status === 'waiting' && can('visits.update')"
                        @click="startVisit"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition bg-[#1B365D] hover:bg-[#1B365D]"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $t('a_start_visit') }}
                    </button>
                    <button
                        v-if="visit.status === 'in_progress' && can('visits.update')"
                        @click="completeVisit"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition bg-emerald-600 hover:bg-emerald-700"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $t('a_complete_visit') }}
                    </button>
                    <button
                        v-if="(visit.status === 'waiting' || visit.status === 'in_progress') && can('visits.update')"
                        @click="cancelVisit"
                        class="inline-flex items-center px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium transition hover:bg-red-50"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ $t('a_cancel') }}
                    </button>
                    <Link
                        :href="`/admin/patients/${visit.patient_id}`"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-[#C4A265] to-[#8B7043] text-white text-sm font-semibold hover:from-[#8B7043] hover:to-[#C4A265] transition"
                    >
                        {{ $t('a_view_patient') }}
                    </Link>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100">
                <div class="p-3 md:p-4 border-b border-slate-100">
                    <div class="overflow-x-auto">
                        <div class="inline-flex gap-1 bg-slate-100 p-1 rounded-xl min-w-full sm:min-w-0">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap',
                                    activeTab === tab.id
                                        ? 'bg-gradient-to-r from-[#1B365D] to-[#2C4E7A] text-white shadow-sm'
                                        : 'text-slate-600 hover:bg-white hover:text-[#1B365D]'
                                ]"
                            >
                                {{ tab.key ? $t(tab.key) : tab.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-4 md:p-6">
                    <!-- Visit Details Tab -->
                    <div v-if="activeTab === 'details'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700">{{ $t('a_visit_info') }}</h3>
                            <dl class="space-y-2">
                                <div class="text-sm">
                                    <div class="flex justify-between items-center mb-2">
                                        <dt class="text-gray-500">{{ $t('a_visit_date') }}</dt>
                                        <dd v-if="!editingVisitDate" class="flex items-center gap-2">
                                            <span class="text-gray-900">{{ formatDate(visit.visit_date) }}<span v-if="visit.scheduled_time" class="text-gray-500"> · {{ visit.scheduled_time }}</span></span>
                                            <button
                                                v-if="can('visits.update') && !isLocked"
                                                @click="editingVisitDate = true"
                                                class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded hover:underline transition"
                                                :class="isCancelled ? 'text-emerald-600' : ''"
                                                :style="!isCancelled ? 'color: #C4A265;' : ''"
                                            >
                                                <svg v-if="!isCancelled" class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <svg v-else class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                {{ isCancelled ? (isRtl ? 'استعادة الزيارة' : 'Restore visit') : $t('a_edit') }}
                                            </button>
                                        </dd>
                                    </div>

                                    <!-- Inline multi-field edit form (date + time + doctor + service) -->
                                    <div v-if="editingVisitDate"
                                         :class="isCancelled ? 'bg-emerald-50 border-emerald-300' : 'bg-[#FAF7F0] border-[#C4A265]/30'"
                                         class="border rounded-lg p-3 mt-1 space-y-3">
                                        <div v-if="isCancelled" class="text-xs bg-white border border-emerald-200 rounded p-2 text-emerald-800 flex items-start gap-1.5">
                                            <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                            <span>{{ isRtl
                                                ? 'هذه الزيارة ملغاة. حفظ النموذج سيعيدها للنشاط (حالة: انتظار) ويرسل بريداً للمريض.'
                                                : 'This visit is cancelled. Saving will restore it to "waiting" status and email the patient.' }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">{{ $t('a_visit_date') }}</label>
                                                <input v-model="visitDateForm.visit_date" type="date" required
                                                       class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" />
                                                <p v-if="visitDateForm.errors.visit_date" class="text-[10px] text-red-600 mt-0.5">{{ visitDateForm.errors.visit_date }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">{{ isRtl ? 'الوقت' : 'Time' }}</label>
                                                <input v-model="visitDateForm.scheduled_time" type="time"
                                                       class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" />
                                                <p v-if="visitDateForm.errors.scheduled_time" class="text-[10px] text-red-600 mt-0.5">{{ visitDateForm.errors.scheduled_time }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                                            <select v-model="visitDateForm.doctor_id"
                                                    class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm">
                                                <option :value="''">{{ isRtl ? '— بدون طبيب' : '— No doctor' }}</option>
                                                <option v-for="d in doctors" :key="d.id" :value="d.id">
                                                    {{ isRtl ? d.name_ar : d.name_en }}
                                                </option>
                                            </select>
                                            <p v-if="visitDateForm.errors.doctor_id" class="text-[10px] text-red-600 mt-0.5">{{ visitDateForm.errors.doctor_id }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">{{ isRtl ? 'الخدمة' : 'Service' }}</label>
                                            <select v-model="visitDateForm.service_id"
                                                    class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm">
                                                <option :value="''">{{ isRtl ? '— بدون خدمة' : '— No service' }}</option>
                                                <option v-for="s in services" :key="s.id" :value="s.id">
                                                    {{ isRtl ? s.name_ar : s.name_en }}<span v-if="s.price"> ({{ s.price }})</span>
                                                </option>
                                            </select>
                                            <p v-if="visitDateForm.errors.service_id" class="text-[10px] text-red-600 mt-0.5">{{ visitDateForm.errors.service_id }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 pt-1">
                                            <button @click="saveVisitDate" :disabled="visitDateForm.processing"
                                                    class="px-3 py-1.5 rounded text-white text-xs font-bold disabled:opacity-50"
                                                    :style="isCancelled ? 'background-color: #059669;' : 'background-color: #C4A265;'">
                                                {{ visitDateForm.processing
                                                    ? $t('a_saving')
                                                    : (isCancelled ? (isRtl ? '✓ استعادة الزيارة' : '✓ Restore visit') : $t('a_save')) }}
                                            </button>
                                            <button @click="cancelEditVisitDate"
                                                    class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50">
                                                {{ $t('a_cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_visit_type') }}</dt><dd class="text-gray-900">{{ {consultation: $t('a_consultation'), session: $t('a_session'), follow_up: $t('a_follow_up')}[visit.visit_type] || visit.visit_type }}</dd></div>
                                <div class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_status') }}</dt>
                                    <dd>
                                        <span :class="statusColors[visit.status]" class="px-2 text-xs leading-5 font-semibold rounded-full">
                                            {{ {waiting: $t('a_waiting'), in_progress: $t('a_in_progress'), completed: $t('a_completed'), cancelled: $t('a_cancelled')}[visit.status] || visit.status }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_doctor') }}</dt><dd><Link v-if="visit.doctor && can('doctors.view')" :href="`/admin/doctors/${visit.doctor.id}`" class="hover:underline" style="color: #C4A265;">Dr. {{ $localized(visit.doctor, 'name') }}</Link><span v-else class="text-gray-900">Dr. {{ visit.doctor?.name_en || '-' }}</span></dd></div>
                                <div v-if="visit.service" class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_service') }}</dt><dd class="text-gray-900">{{ $localized(visit.service, 'name') }}</dd></div>
                                <div v-if="visit.session_number" class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_session_number') }}</dt><dd class="text-gray-900">#{{ visit.session_number }}</dd></div>
                                <div v-if="visit.started_at" class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_started_at') }}</dt><dd class="text-gray-900">{{ formatDateTime(visit.started_at) }}</dd></div>
                                <div v-if="visit.completed_at" class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_completed_at') }}</dt><dd class="text-gray-900">{{ formatDateTime(visit.completed_at) }}</dd></div>
                                <div v-if="visit.commission_amount" class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_commission') }}</dt><dd class="text-[#1B365D] font-medium">{{ formatCurrency(visit.commission_amount) }} ({{ visit.commission_rate }}%)</dd></div>
                                <div class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_created') }}</dt><dd class="text-gray-900">{{ formatDateTime(visit.created_at) }}</dd></div>
                            </dl>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700">{{ $t('a_patient_info') }}</h3>
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_name') }}</dt>
                                    <dd>
                                        <Link v-if="can('patients.view')" :href="`/admin/patients/${visit.patient_id}`" class="font-medium hover:underline" style="color: #C4A265;">{{ visit.patient?.full_name }}</Link>
                                        <span v-else class="text-gray-900">{{ visit.patient?.full_name }}</span>
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_file_number') }}</dt><dd class="font-mono" style="color: #C4A265;">{{ visit.patient?.file_number }}</dd></div>
                                <div class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_phone') }}</dt><dd class="text-gray-900">{{ visit.patient?.phone }}</dd></div>
                                <div v-if="visit.patient?.email" class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_email') }}</dt><dd class="text-gray-900">{{ visit.patient?.email }}</dd></div>
                                <div class="flex justify-between text-sm"><dt class="text-gray-500">{{ $t('a_gender') }}</dt><dd class="text-gray-900">{{ visit.patient?.gender === 'male' ? $t('a_male') : $t('a_female') }}</dd></div>
                            </dl>

                            <!-- Linked Booking Info -->
                            <div v-if="visit.booking" class="mt-4">
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">{{ $t('a_linked_booking') }}</h3>
                                <Link :href="`/admin/bookings/${visit.booking.id}`" class="block p-3 rounded-lg border border-[#C4A265]/20 bg-[#C4A265]/5 hover:border-[#C4A265]/40 transition group">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-gray-900 group-hover:text-[#C4A265]">{{ visit.booking.booking_number || `#${visit.booking.id}` }}</p>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold capitalize" :class="{
                                            'bg-amber-100 text-amber-700': visit.booking.status === 'unconfirmed',
                                            'bg-slate-100 text-[#1B365D]': visit.booking.status === 'confirmed',
                                            'bg-slate-100 text-[#1B365D]': visit.booking.status === 'in_progress',
                                            'bg-emerald-100 text-emerald-700': visit.booking.status === 'completed',
                                            'bg-red-100 text-red-700': visit.booking.status === 'cancelled',
                                        }">{{ visit.booking.status?.replace('_', ' ') }}</span>
                                    </div>
                                    <p v-if="visit.booking.booking_type" class="text-xs text-gray-500 mt-1 capitalize">{{ visit.booking.booking_type?.replace(/_/g, ' ') }}</p>
                                    <p v-if="visit.booking_appointment" class="text-xs text-gray-400 mt-1">
                                        Appointment: {{ formatDate(visit.booking_appointment.appointment_date) }}
                                        <span v-if="visit.booking_appointment.start_time"> · {{ visit.booking_appointment.start_time?.substring(0, 5) }}</span>
                                    </p>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Diagnosis & Notes Tab -->
                    <div v-if="activeTab === 'diagnosis'">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-700">{{ $t('a_diagnosis_notes') }}</h3>
                            <div v-if="can('visits.update') && !editingDiagnosis">
                                <button
                                    @click="editingDiagnosis = true"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg transition hover:underline"
                                    style="color: #C4A265;"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                            </div>
                        </div>

                        <!-- View Mode -->
                        <div v-if="!editingDiagnosis" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">{{ $t('a_diagnosis') }}</label>
                                <p v-if="visit.diagnosis" class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg whitespace-pre-wrap">{{ visit.diagnosis }}</p>
                                <p v-else class="text-sm text-gray-400 italic">{{ $t('a_no_diagnosis_recorded') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">{{ $t('a_notes') }}</label>
                                <p v-if="visit.doctor_notes" class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg whitespace-pre-wrap">{{ visit.doctor_notes }}</p>
                                <p v-else class="text-sm text-gray-400 italic">{{ $t('a_no_notes_recorded') }}</p>
                            </div>
                        </div>

                        <!-- Edit Mode -->
                        <form v-else @submit.prevent="saveDiagnosis" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_diagnosis') }}</label>
                                <textarea v-model="diagnosisForm.diagnosis" rows="4" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" placeholder="Enter diagnosis..."></textarea>
                                <p v-if="diagnosisForm.errors.diagnosis" class="mt-1 text-sm text-red-600">{{ diagnosisForm.errors.diagnosis }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                                <textarea v-model="diagnosisForm.doctor_notes" rows="4" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" placeholder="Enter notes..."></textarea>
                                <p v-if="diagnosisForm.errors.doctor_notes" class="mt-1 text-sm text-red-600">{{ diagnosisForm.errors.doctor_notes }}</p>
                            </div>
                            <div class="flex space-x-3">
                                <button
                                    type="submit"
                                    :disabled="diagnosisForm.processing"
                                    class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                    style="background-color: #C4A265;"
                                >
                                    {{ diagnosisForm.processing ? $t('a_saving') : $t('a_save') }}
                                </button>
                                <button
                                    type="button"
                                    @click="cancelEditDiagnosis"
                                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium transition hover:bg-gray-50"
                                >
                                    {{ $t('a_cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Dental Tab -->
                    <div v-if="activeTab === 'dental'" class="space-y-6">
                        <!-- Dental Treatments -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                {{ isRtl ? 'علاجات الأسنان' : 'Dental Treatments' }}
                                <span v-if="visit.dental_treatments?.length" class="text-xs font-medium px-2 py-0.5 rounded-full" style="background: rgba(196,162,101,0.1); color: #C4A265;">{{ visit.dental_treatments.length }}</span>
                            </h3>
                            <div v-if="visit.dental_treatments?.length" class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-xs text-gray-500 uppercase border-b">
                                            <th class="text-left py-2 px-3">{{ isRtl ? 'السن' : 'Tooth' }}</th>
                                            <th class="text-left py-2 px-3">{{ isRtl ? 'النوع' : 'Type' }}</th>
                                            <th class="text-left py-2 px-3">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                                            <th class="text-left py-2 px-3">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                            <th class="text-right py-2 px-3">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="t in visit.dental_treatments" :key="t.id" class="hover:bg-gray-50">
                                            <td class="py-2 px-3 font-mono text-gray-700">#{{ t.tooth_number || '-' }}</td>
                                            <td class="py-2 px-3">
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full" style="background: rgba(196,162,101,0.1); color: #C4A265;">{{ treatmentTypes?.[t.treatment_type] || t.treatment_type }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-gray-600">{{ t.description || '-' }}</td>
                                            <td class="py-2 px-3">
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full" :class="{
                                                    'bg-emerald-100 text-emerald-700': t.status === 'completed',
                                                    'bg-slate-100 text-[#1B365D]': t.status === 'in_progress',
                                                    'bg-amber-100 text-amber-700': t.status === 'planned',
                                                    'bg-red-100 text-red-700': t.status === 'cancelled',
                                                }">{{ t.status }}</span>
                                            </td>
                                            <td class="py-2 px-3 text-right font-medium">{{ t.cost ? formatCurrency(t.cost) : '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا توجد علاجات أسنان لهذه الزيارة' : 'No dental treatments for this visit' }}</p>
                        </div>

                        <!-- Mini Dental Chart -->
                        <div v-if="dentalChart && Object.keys(dentalChart).length > 0">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" /></svg>
                                {{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}
                            </h3>
                            <div class="grid grid-cols-16 gap-1">
                                <div v-for="quadrant in ['upper_right', 'upper_left', 'lower_left', 'lower_right']" :key="quadrant" class="col-span-4">
                                    <div class="flex flex-wrap gap-1">
                                        <div v-for="tooth in (allTeeth?.[quadrant] || [])" :key="tooth" class="relative group">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-mono cursor-default transition-all border"
                                                :class="dentalChart[tooth] ? 'bg-amber-50 border-amber-300 text-amber-700' : 'bg-gray-50 border-gray-200 text-gray-400'"
                                                :title="dentalChart[tooth]?.condition || 'Healthy'"
                                            >{{ tooth }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded border border-gray-200 bg-gray-50"></span> {{ isRtl ? 'سليم' : 'Healthy' }}</span>
                                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded border border-amber-300 bg-amber-50"></span> {{ isRtl ? 'يحتاج علاج' : 'Has Condition' }}</span>
                            </div>
                            <div class="mt-3">
                                <Link :href="`/admin/dental/charts/${visit.patient_id}`" class="inline-flex items-center gap-1 text-xs font-medium hover:underline" style="color: #C4A265;">
                                    {{ isRtl ? 'عرض المخطط الكامل' : 'View Full Chart' }}
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </Link>
                            </div>
                        </div>

                        <!-- Treatment Plans -->
                        <div v-if="dentalPlans?.length > 0">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                {{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <Link v-for="plan in dentalPlans" :key="plan.id" :href="`/admin/dental/treatment-plans/${plan.id}`"
                                    class="block p-4 rounded-lg border border-gray-200 hover:border-[#C4A265]/40 hover:bg-[#C4A265]/5 transition group"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-sm font-semibold text-gray-800 group-hover:text-[#C4A265]">{{ plan.title || (isRtl ? 'خطة علاج' : 'Treatment Plan') }} #{{ plan.id }}</p>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full capitalize" :class="{
                                            'bg-amber-100 text-amber-700': plan.status === 'draft',
                                            'bg-slate-100 text-[#1B365D]': plan.status === 'approved',
                                            'bg-slate-100 text-[#1B365D]': plan.status === 'in_progress',
                                            'bg-emerald-100 text-emerald-700': plan.status === 'completed',
                                        }">{{ plan.status?.replace('_', ' ') }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-gray-500">
                                        <span v-if="plan.doctor">Dr. {{ plan.doctor.name_en || plan.doctor.name_ar }}</span>
                                        <span>{{ plan.treatments_count || 0 }} {{ isRtl ? 'علاج' : 'treatments' }}</span>
                                    </div>
                                </Link>
                            </div>
                        </div>

                        <!-- X-rays -->
                        <div v-if="dentalXrays?.length > 0">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                {{ isRtl ? 'صور الأشعة' : 'X-Rays' }}
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-50 text-[#1B365D] border border-slate-100">{{ dentalXrays.length }}</span>
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div v-for="xray in dentalXrays" :key="xray.id" class="relative group rounded-lg overflow-hidden border border-gray-200 hover:border-gray-300 transition">
                                    <img :src="`/storage/${xray.image_path}`" :alt="xray.notes || 'X-ray'" class="w-full h-32 object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-[10px] px-2 py-1.5 opacity-0 group-hover:opacity-100 transition">
                                        <p v-if="xray.xray_type" class="capitalize">{{ xray.xray_type }}</p>
                                        <p v-if="xray.taken_date">{{ xray.taken_date }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Photos Tab -->
                    <div v-if="activeTab === 'photos'">
                        <!-- Upload Form -->
                        <div v-if="can('visits.update')" class="mb-6 p-4 border border-dashed border-gray-300 rounded-lg bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ $t('a_upload_photo') }}</h4>
                            <form @submit.prevent="uploadPhoto" class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Photo <span class="text-red-500">*</span></label>
                                    <input type="file" accept="image/*" @change="onPhotoSelected" class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#C4A265]/10 file:text-[#C4A265] hover:file:bg-[#C4A265]/20" />
                                    <p v-if="photoForm.errors.photo" class="mt-1 text-xs text-red-600">{{ photoForm.errors.photo }}</p>
                                </div>
                                <div class="w-40">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                                    <select v-model="photoForm.photo_type" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                                        <option value="before">Before</option>
                                        <option value="after">After</option>
                                        <option value="during">During</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Caption</label>
                                    <input v-model="photoForm.caption" type="text" placeholder="Optional caption" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="photoForm.processing || !photoForm.photo"
                                    class="px-4 py-1.5 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                    style="background-color: #C4A265;"
                                >
                                    {{ photoForm.processing ? $t('a_uploading') : $t('a_upload') }}
                                </button>
                            </form>
                            <div v-if="photoPreview" class="mt-3">
                                <img :src="photoPreview" class="h-24 rounded-lg object-cover border" />
                            </div>
                        </div>

                        <div v-if="visit.photos?.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="photo in visit.photos" :key="photo.id" class="relative group">
                                <img :src="`/storage/${photo.photo_path}`" class="w-full h-40 object-cover rounded-lg" />
                                <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs p-2 rounded-b-lg opacity-0 group-hover:opacity-100 transition">
                                    <p v-if="photo.caption">{{ photo.caption }}</p>
                                    <p class="text-gray-300">{{ photo.taken_at ? formatDate(photo.taken_at) : formatDate(photo.created_at) }}</p>
                                    <p v-if="photo.photo_type" class="text-gray-300 capitalize">{{ photo.photo_type }}</p>
                                </div>
                            </div>
                        </div>
                        <p v-else-if="!can('visits.update')" class="text-sm text-gray-500 text-center py-8">{{ $t('a_no_photos') }}</p>
                    </div>

                    <!-- Prescription Tab -->
                    <div v-if="activeTab === 'prescription'">
                        <!-- Existing Prescriptions -->
                        <div v-if="visit.prescriptions?.length" class="space-y-4 mb-6">
                            <div v-for="rx in visit.prescriptions" :key="rx.id" class="border rounded-lg overflow-hidden">
                                <!-- View Mode -->
                                <div v-if="editingPrescriptionId !== rx.id">
                                    <div class="flex justify-between items-start p-4 border-b bg-gray-50">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ rx.diagnosis || 'No diagnosis' }}</p>
                                            <p class="text-xs text-gray-500">Dr. {{ rx.doctor ? $localized(rx.doctor, 'name') : (visit.doctor ? $localized(visit.doctor, 'name') : $t('a_unknown')) }} &middot; {{ formatDate(rx.created_at) }}</p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Link v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}`" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                                                View
                                            </Link>
                                            <a v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}/print`" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border text-[#1B365D] border-slate-300 hover:bg-slate-50 transition">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                                Print
                                            </a>
                                            <a v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}/pdf`" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border text-emerald-600 border-emerald-300 hover:bg-emerald-50 transition">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                PDF
                                            </a>
                                            <button v-if="can('prescriptions.update')" @click="startEditPrescription(rx)" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border text-white transition" style="background-color: #C4A265; border-color: #C4A265;">
                                                Edit
                                            </button>
                                            <button v-if="can('prescriptions.delete')" @click="deletePrescription(rx.id)" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg border border-red-300 text-red-600 hover:bg-red-50 transition">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <table v-if="rx.items?.length" class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-xs text-gray-500 uppercase">
                                                    <th class="text-left py-1">{{ $t('a_medication') }}</th>
                                                    <th class="text-left py-1">{{ $t('a_dosage') }}</th>
                                                    <th class="text-left py-1">{{ $t('a_frequency') }}</th>
                                                    <th class="text-left py-1">{{ $t('a_duration') }}</th>
                                                    <th class="text-left py-1">{{ $t('a_instructions') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="item in rx.items" :key="item.id">
                                                    <td class="py-1 text-gray-900">{{ item.medication_name }}</td>
                                                    <td class="py-1 text-gray-500">{{ item.dosage || '-' }}</td>
                                                    <td class="py-1 text-gray-500">{{ item.frequency || '-' }}</td>
                                                    <td class="py-1 text-gray-500">{{ item.duration || '-' }}</td>
                                                    <td class="py-1 text-gray-500">{{ item.instructions || '-' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div v-if="rx.notes" class="mt-3 p-3 bg-amber-50 rounded-lg">
                                            <p class="text-xs text-gray-500 font-medium mb-1">Notes</p>
                                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ rx.notes }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Mode -->
                                <div v-else class="p-4 bg-gray-50">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('a_edit_prescription') }}</h4>
                                    <form @submit.prevent="submitEditPrescription(rx.id)" class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                                                <input v-model="editPrescriptionForm.diagnosis" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Diagnosis" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                                <input v-model="editPrescriptionForm.notes" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Optional notes" />
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Medications</label>
                                            <div v-for="(item, idx) in editPrescriptionForm.items" :key="idx" class="flex flex-wrap gap-2 mb-3 items-start p-3 bg-white rounded-lg border border-gray-200">
                                                <div class="flex-1 min-w-[160px]">
                                                    <input v-model="item.medication_name" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Medication name *" />
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
                                                <div class="flex-1 min-w-[120px]">
                                                    <input v-model="item.instructions" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Instructions" />
                                                </div>
                                                <button v-if="editPrescriptionForm.items.length > 1" type="button" @click="removeEditPrescriptionItem(idx)" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                            <button type="button" @click="addEditPrescriptionItem" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-dashed border-gray-300 text-gray-500 hover:text-gray-700 hover:border-gray-400 transition">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                Add Medication
                                            </button>
                                        </div>

                                        <div class="flex space-x-3 pt-2">
                                            <button type="submit" :disabled="editPrescriptionForm.processing" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50" style="background-color: #C4A265;">
                                                {{ editPrescriptionForm.processing ? $t('a_saving') : $t('a_update_prescription') }}
                                            </button>
                                            <button type="button" @click="cancelEditPrescription" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium transition hover:bg-gray-50">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <p v-else-if="!can('prescriptions.create')" class="text-sm text-gray-500 text-center py-8">No prescriptions for this visit.</p>

                        <!-- New Prescription Form -->
                        <div v-if="can('prescriptions.create')">
                            <button
                                v-if="!showPrescriptionForm"
                                @click="showPrescriptionForm = true"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                New Prescription
                            </button>

                            <div v-if="showPrescriptionForm" class="border border-gray-200 rounded-lg p-5 bg-gray-50 mt-2">
                                <h4 class="text-sm font-semibold text-gray-700 mb-4">{{ $t('a_create_prescription') }}</h4>
                                <form @submit.prevent="submitPrescription" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                                            <input v-model="prescriptionForm.diagnosis" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Diagnosis for this prescription" />
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
                                                    class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
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
                                            @click="showPrescriptionForm = false"
                                            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium transition hover:bg-gray-50"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Tab -->
                    <div v-if="activeTab === 'invoice'">
                        <div v-if="visit.invoice">
                            <div class="border rounded-lg overflow-hidden">
                                <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex items-center justify-between" style="background-color: rgba(196, 162, 101, 0.08);">
                                    <div>
                                        <p class="text-sm font-mono font-bold" style="color: #C4A265;">{{ visit.invoice.invoice_number }}</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(visit.invoice.created_at) }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span
                                            :class="invoiceStatusColors[visit.invoice.status] || 'bg-gray-100 text-gray-800'"
                                            class="px-3 py-1 text-xs font-semibold rounded-full capitalize"
                                        >
                                            {{ visit.invoice.status }}
                                        </span>
                                        <Link
                                            v-if="can('invoices.view')"
                                            :href="`/admin/invoices/${visit.invoice.id}`"
                                            class="inline-flex items-center px-3 py-1 rounded-lg border text-xs font-medium transition"
                                            style="border-color: #C4A265; color: #C4A265;"
                                        >
                                            View Full Invoice
                                        </Link>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <table v-if="visit.invoice.items?.length" class="min-w-full text-sm mb-4">
                                        <thead>
                                            <tr class="text-xs text-gray-500 uppercase border-b">
                                                <th class="text-left py-2">Item</th>
                                                <th class="text-right py-2">Qty</th>
                                                <th class="text-right py-2">Price</th>
                                                <th class="text-right py-2">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="item in visit.invoice.items" :key="item.id">
                                                <td class="py-2 text-gray-900">{{ item.description || item.service?.name_en || '-' }}</td>
                                                <td class="py-2 text-gray-500 text-right">{{ item.quantity || 1 }}</td>
                                                <td class="py-2 text-gray-500 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                                <td class="py-2 text-gray-900 text-right font-medium">{{ formatCurrency(item.total || (item.quantity * item.unit_price)) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class="border-t pt-4 space-y-1">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Subtotal</span>
                                            <span class="text-gray-900">{{ formatCurrency(visit.invoice.subtotal || visit.invoice.total) }}</span>
                                        </div>
                                        <div v-if="visit.invoice.discount" class="flex justify-between text-sm">
                                            <span class="text-gray-500">Discount</span>
                                            <span class="text-red-600">-{{ formatCurrency(visit.invoice.discount) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm font-bold border-t pt-2">
                                            <span class="text-gray-700">Total</span>
                                            <span style="color: #C4A265;">{{ formatCurrency(visit.invoice.total) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Paid</span>
                                            <span class="text-emerald-600">{{ formatCurrency(visit.invoice.paid_amount) }}</span>
                                        </div>
                                        <div v-if="visit.invoice.total - visit.invoice.paid_amount > 0" class="flex justify-between text-sm">
                                            <span class="text-gray-500">Remaining</span>
                                            <span class="text-red-600">{{ formatCurrency(visit.invoice.total - visit.invoice.paid_amount) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 text-center py-8">No invoice for this visit.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
