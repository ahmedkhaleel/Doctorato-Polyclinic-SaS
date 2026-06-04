<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import AiAssist from '@/Components/Ai/AiAssist.vue';
import PrescriptionsSection from '@/Components/Doctor/Visit/PrescriptionsSection.vue';
import VitalsHistorySection from '@/Components/Doctor/Visit/VitalsHistorySection.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    visit: Object,
    dentalChart: Object,
    dentalXrays: Array,
    dentalConditions: Object,
    dentalSurfaces: Object,
    allTeeth: Object,
    treatmentTypes: Object,
    dentalRiskFlags: Array,
    dentalMedicalInfo: Object,
    latestVitals: Object,
    vitalsAlerts: Array,
    vitalsHistory: Array,
    activeInsurance: Object,
});

const isDental = computed(() => props.visit?.module === 'dental');

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

// ─── Diagnosis Form ──────────────────────────────
const diagnosisForm = useForm({
    diagnosis: props.visit.diagnosis || '',
    doctor_notes: props.visit.doctor_notes || '',
});
const showDiagnosisEdit = ref(false);

function saveDiagnosis() {
    diagnosisForm.put(`/doctor/visits/${props.visit.id}/diagnosis`, {
        onSuccess: () => { showDiagnosisEdit.value = false; },
    });
}

// ─── Photo Upload Form ───────────────────────────
const showPhotoUpload = ref(false);
const photoForm = useForm({ photo: null, caption: '', type: 'before' });
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
    photoForm.post(`/doctor/visits/${props.visit.id}/photos`, {
        forceFormData: true,
        onSuccess: () => {
            showPhotoUpload.value = false;
            photoForm.reset();
            photoPreview.value = null;
        },
    });
}

// ─── Visit Actions ───────────────────────────────
const showConfirmComplete = ref(false);
const showConfirmCancel = ref(false);

function startVisit() { router.post(`/doctor/visits/${props.visit.id}/start`); }
function confirmComplete() { showConfirmComplete.value = true; }
function completeVisit() {
    router.post(`/doctor/visits/${props.visit.id}/complete`);
    showConfirmComplete.value = false;
}
function confirmCancel() { showConfirmCancel.value = true; }
function cancelVisit() {
    router.post(`/doctor/visits/${props.visit.id}/cancel`);
    showConfirmCancel.value = false;
}

// ─── Helpers ─────────────────────────────────────
const isEditable = computed(() => props.visit.status === 'in_progress' || props.visit.status === 'waiting');
const canEditDiagnosis = computed(() => isEditable.value || props.visit.status === 'completed');

// Dental risk flags
const highRiskFlags = computed(() => (props.dentalRiskFlags || []).filter(f => f.severity === 'high'));
const mediumRiskFlags = computed(() => (props.dentalRiskFlags || []).filter(f => f.severity === 'medium'));
const lowRiskFlags = computed(() => (props.dentalRiskFlags || []).filter(f => f.severity === 'low'));
const hasRiskFlags = computed(() => (props.dentalRiskFlags || []).length > 0);
const showMedicalAlerts = ref(true);

const statusConfig = computed(() => ({
    waiting: { label: isRtl.value ? 'انتظار' : 'Waiting', bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-400', heroBg: 'from-amber-600 to-amber-800' },
    in_progress: { label: isRtl.value ? 'جاري' : 'In Progress', bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]', heroBg: 'from-[#1B365D] to-[#1B365D]' },
    completed: { label: isRtl.value ? 'مكتمل' : 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500', heroBg: 'from-emerald-700 to-emerald-900' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-gray-50', text: 'text-gray-500', border: 'border-gray-200', dot: 'bg-gray-400', heroBg: 'from-gray-700 to-gray-900' },
}));

const beforePhotos = computed(() => (props.visit.photos || []).filter(p => p.type === 'before'));
const afterPhotos = computed(() => (props.visit.photos || []).filter(p => p.type === 'after'));
const otherPhotos = computed(() => (props.visit.photos || []).filter(p => p.type !== 'before' && p.type !== 'after'));
const totalPhotos = computed(() => (props.visit.photos || []).length);

// ─── Vitals Quick Record ────────────────────────
const showVitalsForm = ref(false);
const vitalsForm = useForm({
    patient_id: props.visit.patient_id,
    visit_id: props.visit.id,
    bp_systolic: '',
    bp_diastolic: '',
    heart_rate: '',
    temperature: '',
    respiratory_rate: '',
    spo2: '',
    weight: '',
    height: '',
    blood_sugar: '',
    pain_level: '',
});

const vitalsSuccess = ref('');
const vitalsError = ref('');

function toNum(v) {
    if (v === '' || v === null || v === undefined) return null;
    const n = Number(v);
    return isNaN(n) ? null : n;
}

function submitVitals() {
    vitalsForm.clearErrors();
    vitalsSuccess.value = '';
    vitalsError.value = '';
    vitalsForm.processing = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
        || '';

    fetch(`/doctor/patients/${props.visit.patient_id}/vitals`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            visit_id: vitalsForm.visit_id,
            bp_systolic: toNum(vitalsForm.bp_systolic),
            bp_diastolic: toNum(vitalsForm.bp_diastolic),
            heart_rate: toNum(vitalsForm.heart_rate),
            temperature: toNum(vitalsForm.temperature),
            respiratory_rate: toNum(vitalsForm.respiratory_rate),
            spo2: toNum(vitalsForm.spo2),
            weight: toNum(vitalsForm.weight),
            height: toNum(vitalsForm.height),
            blood_sugar: toNum(vitalsForm.blood_sugar),
            pain_level: toNum(vitalsForm.pain_level),
        }),
    })
    .then(res => {
        if (res.ok) {
            return res.json().then(() => {
                vitalsSuccess.value = isRtl.value ? 'تم حفظ العلامات الحيوية بنجاح' : 'Vitals saved successfully';
                showVitalsForm.value = false;
                vitalsForm.reset();
                router.reload({ preserveScroll: true });
            });
        } else if (res.status === 422) {
            return res.json().then(data => {
                if (data.errors) {
                    Object.keys(data.errors).forEach(k => vitalsForm.setError(k, data.errors[k][0]));
                }
                vitalsError.value = data.message || (isRtl.value ? 'يرجى تصحيح الأخطاء' : 'Please fix the errors');
            });
        } else if (res.status === 419) {
            vitalsError.value = isRtl.value ? 'انتهت صلاحية الجلسة، يرجى تحديث الصفحة' : 'Session expired, please refresh the page';
        } else {
            vitalsError.value = isRtl.value ? 'حدث خطأ، حاول مرة أخرى' : 'An error occurred, please try again';
        }
    })
    .catch(err => {
        console.error('Vitals save error:', err);
        vitalsError.value = isRtl.value ? 'خطأ في الاتصال، حاول مرة أخرى' : 'Connection error, please try again';
    })
    .finally(() => { vitalsForm.processing = false; });
}

const hasVitalsAlerts = computed(() => (props.vitalsAlerts || []).length > 0);

// BP Classification helper
function bpClassification(sys, dia) {
    if (!sys || !dia) return null;
    if (sys >= 180 || dia >= 120) return { label: isRtl.value ? 'أزمة' : 'Crisis', color: 'text-red-700 bg-red-50' };
    if (sys >= 140 || dia >= 90) return { label: isRtl.value ? 'مرتفع ٢' : 'Stage 2', color: 'text-red-600 bg-red-50' };
    if (sys >= 130 || dia >= 80) return { label: isRtl.value ? 'مرتفع ١' : 'Stage 1', color: 'text-amber-600 bg-amber-50' };
    if (sys >= 120 && dia < 80) return { label: isRtl.value ? 'مرتفع' : 'Elevated', color: 'text-amber-600 bg-yellow-50' };
    return { label: isRtl.value ? 'طبيعي' : 'Normal', color: 'text-emerald-600 bg-emerald-50' };
}

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    return d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-[#1B365D]/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <!-- Breadcrumb -->
                <Link href="/doctor/visits" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-[#C4A265] transition-colors mb-4">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    {{ isRtl ? 'العودة للزيارات' : 'Back to Visits' }}
                </Link>

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ visit.patient?.full_name }}</h1>
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full border"
                                :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text, statusConfig[visit.status]?.border]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[visit.status]?.dot"></span>
                                {{ statusConfig[visit.status]?.label }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                {{ formatDate(visit.visit_date) }}
                            </span>
                            <span class="text-gray-600">&middot;</span>
                            <span>{{ (isRtl ? (visit.service?.name_ar || visit.service?.name_en) : visit.service?.name_en) || visit.visit_type }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button v-if="visit.status === 'waiting'" @click="startVisit"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-white bg-[#1B365D] hover:bg-[#1B365D] rounded-xl transition-all shadow-sm shadow-[#1B365D]/30 hover:shadow-md hover:-translate-y-0.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /></svg>
                            {{ isRtl ? 'بدء الزيارة' : 'Start Visit' }}
                        </button>
                        <button v-if="visit.status === 'in_progress'" @click="confirmComplete"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-all shadow-sm shadow-emerald-900/30 hover:shadow-md hover:-translate-y-0.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ isRtl ? 'إكمال' : 'Complete' }}
                        </button>
                        <button v-if="visit.status === 'waiting' || visit.status === 'in_progress'" @click="confirmCancel"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-red-300 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 rounded-xl transition-all"
                        >{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dental Medical Alerts Banner -->
        <Transition enter-active-class="transition-all duration-500" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
            <div v-if="isDental && hasRiskFlags && showMedicalAlerts"
                class="relative overflow-hidden rounded-2xl border-2 border-red-200 bg-gradient-to-r from-red-50 via-amber-50 to-red-50"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
            >
                <div class="absolute top-0 left-0 w-1.5 h-full bg-gradient-to-b from-red-500 via-amber-500 to-red-500"></div>
                <div class="px-4 sm:px-6 py-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center animate-pulse">
                                <svg class="w-4.5 h-4.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-red-800">{{ isRtl ? 'تنبيهات طبية مهمة' : 'Medical Alerts' }}</h3>
                            <span class="text-[10px] font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded-full border border-red-200">{{ dentalRiskFlags.length }}</span>
                        </div>
                        <button @click="showMedicalAlerts = false" class="text-gray-400 hover:text-gray-600 transition-colors" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <!-- High severity (red) -->
                        <span v-for="flag in highRiskFlags" :key="flag.key"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-red-100 text-red-800 border border-red-200"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            {{ isRtl ? flag.label_ar : flag.label_en }}
                        </span>
                        <!-- Medium severity (amber) -->
                        <span v-for="flag in mediumRiskFlags" :key="flag.key"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 border border-amber-200"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            {{ isRtl ? flag.label_ar : flag.label_en }}
                        </span>
                        <!-- Low severity (blue) -->
                        <span v-for="flag in lowRiskFlags" :key="flag.key"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-slate-100 text-[#1B365D] border border-slate-200"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-[#1B365D]"></span>
                            {{ isRtl ? flag.label_ar : flag.label_en }}
                        </span>
                    </div>
                    <!-- Extra medical info row -->
                    <div v-if="dentalMedicalInfo?.chronic_conditions || dentalMedicalInfo?.current_medications || dentalMedicalInfo?.blood_type" class="flex flex-wrap gap-3 mt-3 pt-3 border-t border-red-200/60 text-xs">
                        <span v-if="dentalMedicalInfo.blood_type" class="text-gray-600">
                            <strong class="text-gray-800">{{ isRtl ? 'فصيلة الدم:' : 'Blood:' }}</strong> {{ dentalMedicalInfo.blood_type }}
                        </span>
                        <span v-if="dentalMedicalInfo.chronic_conditions" class="text-gray-600">
                            <strong class="text-gray-800">{{ isRtl ? 'أمراض مزمنة:' : 'Chronic:' }}</strong> {{ dentalMedicalInfo.chronic_conditions }}
                        </span>
                        <span v-if="dentalMedicalInfo.current_medications" class="text-gray-600">
                            <strong class="text-gray-800">{{ isRtl ? 'أدوية حالية:' : 'Medications:' }}</strong> {{ dentalMedicalInfo.current_medications }}
                        </span>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Collapsed alert indicator (when alerts hidden) -->
        <div v-if="isDental && hasRiskFlags && !showMedicalAlerts"
            class="flex items-center justify-center"
        >
            <button @click="showMedicalAlerts = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-full transition-all"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                {{ isRtl ? `${dentalRiskFlags.length} تنبيهات طبية — اضغط للعرض` : `${dentalRiskFlags.length} Medical Alert(s) — Click to Show` }}
            </button>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Diagnosis & Notes -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                >
                    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'التشخيص والملاحظات' : 'Diagnosis & Notes' }}</h3>
                        </div>
                        <button v-if="!showDiagnosisEdit && canEditDiagnosis" @click="showDiagnosisEdit = true"
                            class="text-xs font-medium text-[#C4A265] hover:text-[#A68B52] hover:bg-[#C4A265]/5 px-3 py-1.5 rounded-lg transition-all"
                        >{{ isRtl ? 'تعديل' : 'Edit' }}</button>
                    </div>
                    <div class="p-4 sm:p-6">
                        <div v-if="!showDiagnosisEdit">
                            <div class="mb-4">
                                <p class="text-xs text-gray-500 font-medium mb-1.5">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</p>
                                <p class="text-sm text-gray-700" :class="!visit.diagnosis && 'text-gray-400 italic'">{{ visit.diagnosis || (isRtl ? 'لم يُضاف بعد' : 'Not yet added') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-medium mb-1.5">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap" :class="!visit.doctor_notes && 'text-gray-400 italic'">{{ visit.doctor_notes || (isRtl ? 'لا توجد ملاحظات' : 'No notes') }}</p>
                            </div>
                        </div>
                        <form v-else @submit.prevent="saveDiagnosis" class="space-y-4">
                            <div>
                                <label class="text-xs text-gray-500 font-medium mb-1.5 block">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</label>
                                <textarea v-model="diagnosisForm.diagnosis" rows="3" class="doctorato-input w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all" :placeholder="isRtl ? 'أدخل التشخيص...' : 'Enter diagnosis...'"></textarea>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 font-medium mb-1.5 block">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</label>
                                <textarea v-model="diagnosisForm.doctor_notes" rows="3" class="doctorato-input w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all" :placeholder="isRtl ? 'أدخل الملاحظات...' : 'Enter notes...'"></textarea>
                                <AiAssist feature="soap_note" endpoint="/doctor/ai/soap"
                                    label-ar="حوّل الملاحظات إلى SOAP" label-en="Turn notes into SOAP"
                                    :payload="() => ({ notes: diagnosisForm.doctor_notes || diagnosisForm.diagnosis })"
                                    @insert="(t) => diagnosisForm.doctor_notes = t" />
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="diagnosisForm.processing" class="px-5 py-2 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] rounded-lg transition-colors shadow-sm">{{ isRtl ? 'حفظ' : 'Save' }}</button>
                                <button type="button" @click="showDiagnosisEdit = false" class="px-4 py-2 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Prescriptions -->
                <PrescriptionsSection :visit="visit" :is-rtl="isRtl" :is-editable="isEditable" :mounted="mounted" />

                <!-- Photos -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.25s"
                >
                    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'صور الزيارة' : 'Visit Photos' }}</h3>
                            <span v-if="totalPhotos" class="text-[10px] font-bold text-[#1B365D] bg-slate-50 px-1.5 py-0.5 rounded-full border border-slate-100">{{ totalPhotos }}</span>
                        </div>
                        <button v-if="isEditable && !showPhotoUpload" @click="showPhotoUpload = true"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] px-3 py-1.5 rounded-lg transition-all shadow-sm hover:shadow"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ isRtl ? 'رفع' : 'Upload' }}
                        </button>
                    </div>
                    <div class="p-4 sm:p-6">
                        <!-- Photo Upload Form -->
                        <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-3" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-3">
                            <div v-if="showPhotoUpload" class="bg-gray-50 rounded-xl p-5 mb-5 border border-gray-200">
                                <form @submit.prevent="uploadPhoto" class="space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-xs text-gray-500 font-medium mb-1 block">{{ isRtl ? 'نوع الصورة' : 'Photo Type' }}</label>
                                            <select v-model="photoForm.type" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                                <option value="before">{{ isRtl ? 'قبل' : 'Before' }}</option>
                                                <option value="after">{{ isRtl ? 'بعد' : 'After' }}</option>
                                                <option value="other">{{ isRtl ? 'أخرى' : 'Other' }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500 font-medium mb-1 block">{{ isRtl ? 'وصف' : 'Caption' }}</label>
                                            <input v-model="photoForm.caption" type="text" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'وصف اختياري...' : 'Optional caption...'" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 font-medium mb-1 block">{{ isRtl ? 'اختر صورة' : 'Select Photo' }}</label>
                                        <input type="file" accept="image/*" @change="onPhotoSelected" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#C4A265]/10 file:text-[#C4A265] hover:file:bg-[#C4A265]/20" />
                                        <p v-if="photoForm.errors.photo" class="text-xs text-red-500 mt-1">{{ photoForm.errors.photo }}</p>
                                    </div>
                                    <div v-if="photoPreview" class="w-32 h-32 rounded-xl overflow-hidden border border-gray-200 shadow-sm">
                                        <img :src="photoPreview" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="showPhotoUpload = false; photoForm.reset(); photoPreview = null;" class="px-4 py-2 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                        <button type="submit" :disabled="photoForm.processing || !photoForm.photo" class="px-5 py-2 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] rounded-lg transition-colors shadow-sm disabled:opacity-50">
                                            {{ photoForm.processing ? (isRtl ? 'جاري الرفع...' : 'Uploading...') : (isRtl ? 'رفع' : 'Upload') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </Transition>

                        <!-- Before/After Grid -->
                        <div v-if="beforePhotos.length > 0 || afterPhotos.length > 0" class="grid md:grid-cols-2 gap-4 mb-4">
                            <div v-if="beforePhotos.length > 0">
                                <p class="text-xs font-semibold text-amber-600 uppercase mb-2 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> {{ isRtl ? 'قبل' : 'Before' }}
                                </p>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="photo in beforePhotos" :key="photo.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group cursor-pointer hover:ring-2 hover:ring-amber-300 transition-all">
                                        <img :src="'/storage/' + photo.photo_path" :alt="photo.caption || 'Before'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                        <div v-if="photo.caption" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent text-white text-[10px] px-2 py-2 opacity-0 group-hover:opacity-100 transition-opacity">{{ photo.caption }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="afterPhotos.length > 0">
                                <p class="text-xs font-semibold text-emerald-600 uppercase mb-2 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span> {{ isRtl ? 'بعد' : 'After' }}
                                </p>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="photo in afterPhotos" :key="photo.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group cursor-pointer hover:ring-2 hover:ring-emerald-300 transition-all">
                                        <img :src="'/storage/' + photo.photo_path" :alt="photo.caption || 'After'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                        <div v-if="photo.caption" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent text-white text-[10px] px-2 py-2 opacity-0 group-hover:opacity-100 transition-opacity">{{ photo.caption }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="otherPhotos.length > 0">
                            <p v-if="beforePhotos.length > 0 || afterPhotos.length > 0" class="text-xs font-semibold text-gray-500 uppercase mb-2 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-gray-400"></span> {{ isRtl ? 'أخرى' : 'Other' }}
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div v-for="photo in otherPhotos" :key="photo.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group cursor-pointer hover:ring-2 hover:ring-gray-300 transition-all">
                                    <img :src="'/storage/' + photo.photo_path" :alt="photo.caption" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div v-if="photo.caption" class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent text-white text-[10px] px-2 py-2 opacity-0 group-hover:opacity-100 transition-opacity">{{ photo.caption }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!visit.photos?.length && !showPhotoUpload" class="text-center py-8">
                            <div class="w-12 h-12 mx-auto bg-gray-50 rounded-xl flex items-center justify-center mb-3 border border-gray-100">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد صور مرفوعة' : 'No photos uploaded' }}</p>
                        </div>
                    </div>
                </div>

                <!-- ═══ Vitals History & Trends ═══════════════════════ -->
                <VitalsHistorySection
                    :vitals-history="vitalsHistory"
                    :is-rtl="isRtl"
                    :visit="visit"
                    :mounted="mounted"
                />


                <!-- Dental Section -->
                <div v-if="isDental" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
                >
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'طب الأسنان' : 'Dental' }}</h3>
                    </div>
                    <div class="p-4 sm:p-6 space-y-6">
                        <!-- Dental Treatments Table -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'علاجات الأسنان' : 'Dental Treatments' }}</h4>
                            <div v-if="visit.dental_treatments?.length" class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-xs text-gray-400 uppercase">
                                            <th class="text-left py-2 pr-3">{{ isRtl ? 'السن' : 'Tooth' }}</th>
                                            <th class="text-left py-2 pr-3">{{ isRtl ? 'النوع' : 'Type' }}</th>
                                            <th class="text-left py-2 pr-3">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                                            <th class="text-left py-2 pr-3">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="t in visit.dental_treatments" :key="t.id">
                                            <td class="py-2 pr-3 font-mono text-[#C4A265]">#{{ t.tooth_number || '-' }}</td>
                                            <td class="py-2 pr-3"><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-[#C4A265]/10 text-[#C4A265]">{{ treatmentTypes?.[t.treatment_type] || t.treatment_type }}</span></td>
                                            <td class="py-2 pr-3 text-gray-600 text-xs">{{ t.description || '-' }}</td>
                                            <td class="py-2 pr-3">
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full" :class="{
                                                    'bg-emerald-50 text-emerald-700 border border-emerald-200': t.status === 'completed',
                                                    'bg-slate-50 text-[#1B365D] border border-slate-200': t.status === 'in_progress',
                                                    'bg-amber-50 text-amber-700 border border-amber-200': t.status === 'planned',
                                                    'bg-gray-50 text-gray-500 border border-gray-200': t.status === 'cancelled',
                                                }">{{ isRtl ? ({ completed: 'مكتمل', in_progress: 'جاري', planned: 'مخطط', cancelled: 'ملغي' }[t.status] || t.status) : ({ completed: 'Completed', in_progress: 'In Progress', planned: 'Planned', cancelled: 'Cancelled' }[t.status] || t.status) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد علاجات' : 'No treatments recorded' }}</p>
                            <!-- Cost Summary -->
                            <div v-if="visit.dental_treatments?.length" class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'إجمالي تكلفة العلاجات' : 'Total Treatment Cost' }}</span>
                                <span class="font-bold text-[#C4A265]">{{ formatCurrency(visit.dental_treatments.reduce((sum, t) => sum + (parseFloat(t.cost) || 0), 0)) }}</span>
                            </div>
                        </div>

                        <!-- Mini Dental Chart -->
                        <div v-if="dentalChart && Object.keys(dentalChart).length > 0">
                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h4>
                            <div class="flex flex-wrap gap-1 mb-2">
                                <div v-for="quadrant in ['upper_right', 'upper_left', 'lower_left', 'lower_right']" :key="quadrant" class="flex flex-wrap gap-1">
                                    <div v-for="tooth in (allTeeth?.[quadrant] || [])" :key="tooth"
                                        class="w-7 h-7 rounded flex items-center justify-center text-[10px] font-mono border transition-all"
                                        :class="dentalChart[tooth] ? 'bg-amber-50 border-amber-300 text-amber-700 font-bold' : 'bg-gray-50 border-gray-200 text-gray-400'"
                                        :title="dentalChart[tooth]?.condition || 'Healthy'"
                                    >{{ tooth }}</div>
                                </div>
                            </div>
                            <Link :href="`/doctor/dental/chart/${visit.patient_id}`" class="inline-flex items-center gap-1 text-xs font-medium text-[#C4A265] hover:text-[#A68B52] transition-colors mt-1">
                                {{ isRtl ? 'عرض المخطط الكامل' : 'View Full Chart' }}
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>

                        <!-- X-rays Preview -->
                        <div v-if="dentalXrays?.length > 0">
                            <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'صور الأشعة' : 'X-Rays' }}</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div v-for="xray in dentalXrays.slice(0, 6)" :key="xray.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group cursor-pointer hover:ring-2 hover:ring-[#C4A265]/50 transition-all">
                                    <img :src="'/storage/' + xray.image_path" :alt="xray.notes || 'X-ray'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent text-white text-[10px] px-2 py-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <p v-if="xray.xray_type" class="capitalize">{{ xray.xray_type }}</p>
                                        <p v-if="xray.taken_date">{{ xray.taken_date }}</p>
                                    </div>
                                </div>
                            </div>
                            <Link v-if="dentalXrays.length > 6" :href="`/doctor/dental/xrays/${visit.patient_id}`" class="inline-flex items-center gap-1 text-xs font-medium text-[#C4A265] hover:text-[#A68B52] transition-colors mt-2">
                                {{ isRtl ? `عرض الكل (${dentalXrays.length})` : `View All (${dentalXrays.length})` }}
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>

                        <!-- Quick Links -->
                        <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                            <Link :href="`/doctor/dental/chart/${visit.patient_id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 rounded-lg border border-[#C4A265]/20 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" /></svg>
                                {{ isRtl ? 'المخطط' : 'Chart' }}
                            </Link>
                            <Link :href="`/doctor/dental/treatment-plans?patient_id=${visit.patient_id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                {{ isRtl ? 'خطط العلاج' : 'Plans' }}
                            </Link>
                            <Link :href="`/doctor/dental/xrays/${visit.patient_id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                {{ isRtl ? 'الأشعة' : 'X-Rays' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Side Panel -->
            <div class="space-y-6">
                <!-- Patient Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                >
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'معلومات المريض' : 'Patient Info' }}</h3>
                    </div>
                    <div class="p-4 sm:p-6 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الاسم' : 'Name' }}</span><span class="font-medium text-gray-800">{{ visit.patient?.full_name }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'رقم الملف' : 'File #' }}</span><span class="font-mono text-[#C4A265]">{{ visit.patient?.file_number || '-' }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الهاتف' : 'Phone' }}</span><span class="text-gray-800">{{ visit.patient?.phone || '-' }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الجنس' : 'Gender' }}</span><span class="text-gray-800 capitalize">{{ visit.patient?.gender || '-' }}</span></div>

                        <div v-if="visit.patient?.allergies" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex items-center gap-1.5 text-red-600 text-xs font-bold mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                {{ isRtl ? 'الحساسية' : 'Allergies' }}
                            </div>
                            <p class="text-xs text-red-700">{{ visit.patient.allergies }}</p>
                        </div>
                    </div>
                    <div class="px-4 sm:px-6 pb-5">
                        <Link :href="`/doctor/patients/${visit.patient?.id}`" class="inline-flex items-center gap-1 text-xs font-medium text-[#C4A265] hover:text-[#A68B52] transition-colors">
                            {{ isRtl ? 'عرض الملف الكامل' : 'View Full Profile' }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>
                </div>

                <!-- Vitals Widget -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.18s"
                >
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'العلامات الحيوية' : 'Vitals' }}</h3>
                            <span v-if="hasVitalsAlerts" class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        </div>
                        <button v-if="isEditable && !showVitalsForm" @click="showVitalsForm = true"
                            class="text-xs font-medium text-[#C4A265] hover:text-[#A68B52] hover:bg-[#C4A265]/5 px-2 py-1 rounded-lg transition-all">
                            {{ latestVitals ? (isRtl ? 'تحديث' : 'Update') : (isRtl ? 'تسجيل' : 'Record') }}
                        </button>
                    </div>

                    <!-- Vitals Alerts -->
                    <div v-if="hasVitalsAlerts" class="mx-4 mt-3 p-2.5 bg-red-50 border border-red-200 rounded-xl">
                        <div v-for="alert in vitalsAlerts" :key="alert.key" class="flex items-center gap-1.5 text-xs text-red-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0" :class="alert.severity === 'critical' ? 'animate-pulse' : ''"></span>
                            {{ isRtl ? alert.message_ar : alert.message_en }}
                        </div>
                    </div>

                    <div class="p-4">
                        <!-- Current Vitals Display -->
                        <div v-if="latestVitals && !showVitalsForm" class="space-y-2">
                            <div v-if="latestVitals.bp_systolic" class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'ضغط الدم' : 'Blood Pressure' }}</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-gray-800">{{ latestVitals.bp_systolic }}/{{ latestVitals.bp_diastolic }}</span>
                                    <span v-if="bpClassification(latestVitals.bp_systolic, latestVitals.bp_diastolic)"
                                        class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                                        :class="bpClassification(latestVitals.bp_systolic, latestVitals.bp_diastolic).color">
                                        {{ bpClassification(latestVitals.bp_systolic, latestVitals.bp_diastolic).label }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="latestVitals.heart_rate" class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'نبض القلب' : 'Heart Rate' }}</span>
                                <span class="font-medium text-gray-800">{{ latestVitals.heart_rate }} bpm</span>
                            </div>
                            <div v-if="latestVitals.temperature" class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'الحرارة' : 'Temperature' }}</span>
                                <span class="font-medium" :class="latestVitals.temperature > 38 ? 'text-red-600' : 'text-gray-800'">{{ latestVitals.temperature }}°C</span>
                            </div>
                            <div v-if="latestVitals.spo2" class="flex justify-between text-xs">
                                <span class="text-gray-500">SpO2</span>
                                <span class="font-medium" :class="latestVitals.spo2 < 95 ? 'text-red-600' : 'text-gray-800'">{{ latestVitals.spo2 }}%</span>
                            </div>
                            <div v-if="latestVitals.respiratory_rate" class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'معدل التنفس' : 'Respiratory' }}</span>
                                <span class="font-medium text-gray-800">{{ latestVitals.respiratory_rate }}/min</span>
                            </div>
                            <div v-if="latestVitals.weight" class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'الوزن' : 'Weight' }}</span>
                                <span class="font-medium text-gray-800">{{ latestVitals.weight }} kg</span>
                            </div>
                            <div v-if="latestVitals.bmi" class="flex justify-between text-xs">
                                <span class="text-gray-500">BMI</span>
                                <span class="font-medium text-gray-800">{{ parseFloat(latestVitals.bmi).toFixed(1) }}</span>
                            </div>
                            <div v-if="latestVitals.blood_sugar" class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'السكر' : 'Blood Sugar' }}</span>
                                <span class="font-medium" :class="latestVitals.blood_sugar > 200 || latestVitals.blood_sugar < 70 ? 'text-red-600' : 'text-gray-800'">{{ latestVitals.blood_sugar }} mg/dL</span>
                            </div>
                            <div v-if="latestVitals.pain_level != null" class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'مستوى الألم' : 'Pain Level' }}</span>
                                <span class="font-medium" :class="latestVitals.pain_level >= 7 ? 'text-red-600' : latestVitals.pain_level >= 4 ? 'text-amber-600' : 'text-gray-800'">{{ latestVitals.pain_level }}/10</span>
                            </div>
                            <p class="text-[10px] text-gray-400 pt-1 border-t border-gray-50">
                                {{ isRtl ? 'آخر تحديث:' : 'Last updated:' }} {{ latestVitals.recorded_at ? new Date(latestVitals.recorded_at).toLocaleString(isRtl ? 'ar-EG' : 'en-GB') : '-' }}
                            </p>
                        </div>

                        <!-- No Vitals -->
                        <div v-if="!latestVitals && !showVitalsForm" class="text-center py-4">
                            <p class="text-xs text-gray-400">{{ isRtl ? 'لم تُسجل بعد' : 'No vitals recorded yet' }}</p>
                        </div>

                        <!-- Quick Record Form -->
                        <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                            <!-- Success Message -->
                            <div v-if="vitalsSuccess" class="mb-2 px-3 py-2 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-700 flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ vitalsSuccess }}
                            </div>

                            <form v-if="showVitalsForm" @submit.prevent="submitVitals" class="space-y-2.5">
                                <!-- Error Message -->
                                <div v-if="vitalsError" class="px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 flex items-center gap-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ vitalsError }}
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'انقباضي' : 'Systolic' }}</label>
                                        <input v-model="vitalsForm.bp_systolic" type="number" placeholder="120" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.bp_systolic ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.bp_systolic" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.bp_systolic }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'انبساطي' : 'Diastolic' }}</label>
                                        <input v-model="vitalsForm.bp_diastolic" type="number" placeholder="80" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.bp_diastolic ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.bp_diastolic" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.bp_diastolic }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'النبض' : 'Heart Rate' }}</label>
                                        <input v-model="vitalsForm.heart_rate" type="number" placeholder="72" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.heart_rate ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.heart_rate" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.heart_rate }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'الحرارة' : 'Temp °C' }}</label>
                                        <input v-model="vitalsForm.temperature" type="number" step="0.1" placeholder="37.0" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.temperature ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.temperature" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.temperature }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">SpO2 %</label>
                                        <input v-model="vitalsForm.spo2" type="number" placeholder="98" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.spo2 ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.spo2" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.spo2 }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'التنفس' : 'Resp. Rate' }}</label>
                                        <input v-model="vitalsForm.respiratory_rate" type="number" placeholder="16" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.respiratory_rate ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.respiratory_rate" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.respiratory_rate }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'الوزن kg' : 'Weight kg' }}</label>
                                        <input v-model="vitalsForm.weight" type="number" step="0.1" placeholder="70" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.weight ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.weight" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.weight }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'الطول cm' : 'Height cm' }}</label>
                                        <input v-model="vitalsForm.height" type="number" placeholder="170" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.height ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.height" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.height }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'السكر mg/dL' : 'Sugar mg/dL' }}</label>
                                        <input v-model="vitalsForm.blood_sugar" type="number" placeholder="100" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.blood_sugar ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.blood_sugar" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.blood_sugar }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? 'الألم 0-10' : 'Pain 0-10' }}</label>
                                        <input v-model="vitalsForm.pain_level" type="number" min="0" max="10" placeholder="0" class="doctorato-input w-full px-2 py-1.5 border rounded-lg text-xs focus:ring-1 focus:ring-[#C4A265] focus:border-[#C4A265]" :class="vitalsForm.errors.pain_level ? 'border-red-400 bg-red-50' : 'border-gray-200'" />
                                        <p v-if="vitalsForm.errors.pain_level" class="text-[10px] text-red-500 mt-0.5">{{ vitalsForm.errors.pain_level }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2 pt-1">
                                    <button type="submit" :disabled="vitalsForm.processing" class="flex-1 px-3 py-2 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] rounded-lg transition-colors disabled:opacity-50">
                                        {{ vitalsForm.processing ? '...' : (isRtl ? 'حفظ' : 'Save') }}
                                    </button>
                                    <button type="button" @click="showVitalsForm = false; vitalsError = ''" class="px-3 py-2 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                    </button>
                                </div>
                            </form>
                        </Transition>
                    </div>
                </div>

                <!-- Insurance Info (if available) -->
                <div v-if="activeInsurance" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.19s"
                >
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'التأمين' : 'Insurance' }}</h3>
                    </div>
                    <div class="p-4 space-y-2 text-xs">
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الشركة' : 'Company' }}</span><span class="font-medium text-gray-800">{{ isRtl ? activeInsurance.company?.name_ar : activeInsurance.company?.name_en }}</span></div>
                        <div v-if="activeInsurance.policy_number" class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'رقم البوليصة' : 'Policy #' }}</span><span class="font-mono text-gray-700">{{ activeInsurance.policy_number }}</span></div>
                        <div v-if="activeInsurance.plan" class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'التغطية' : 'Coverage' }}</span><span class="font-medium text-[#1B365D]">{{ activeInsurance.plan.coverage_percentage }}%</span></div>
                        <div v-if="activeInsurance.valid_until" class="flex justify-between">
                            <span class="text-gray-500">{{ isRtl ? 'صالح حتى' : 'Valid Until' }}</span>
                            <span :class="new Date(activeInsurance.valid_until) < new Date() ? 'text-red-600 font-medium' : 'text-gray-700'">{{ new Date(activeInsurance.valid_until).toLocaleDateString(isRtl ? 'ar-EG' : 'en-GB') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Visit Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
                >
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تفاصيل الزيارة' : 'Visit Details' }}</h3>
                    </div>
                    <div class="p-4 sm:p-6 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'النوع' : 'Type' }}</span><span class="text-gray-800 capitalize">{{ isRtl ? ({ consultation: 'استشارة', session: 'جلسة', follow_up: 'متابعة' }[visit.visit_type] || visit.visit_type) : visit.visit_type }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الخدمة' : 'Service' }}</span><span class="text-gray-800">{{ (isRtl ? (visit.service?.name_ar || visit.service?.name_en) : visit.service?.name_en) || '-' }}</span></div>
                        <div v-if="visit.started_at" class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'بدأت' : 'Started' }}</span><span class="text-gray-800">{{ new Date(visit.started_at).toLocaleTimeString(isRtl ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit' }) }}</span></div>
                        <div v-if="visit.completed_at" class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'اكتملت' : 'Completed' }}</span><span class="text-gray-800">{{ new Date(visit.completed_at).toLocaleTimeString(isRtl ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit' }) }}</span></div>
                        <div v-if="visit.commission_amount" class="flex justify-between border-t border-gray-100 pt-3 mt-3">
                            <span class="text-gray-500">{{ isRtl ? 'العمولة' : 'Commission' }}</span>
                            <span class="font-bold text-[#C4A265] text-base">{{ formatCurrency(visit.commission_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Invoice -->
                <div v-if="visit.invoice" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.25s"
                >
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الفاتورة' : 'Invoice' }}</h3>
                    </div>
                    <div class="p-4 sm:p-6 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'رقم الفاتورة' : 'Invoice #' }}</span><span class="font-mono text-[#C4A265]">{{ visit.invoice.invoice_number }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الإجمالي' : 'Total' }}</span><span class="font-medium">{{ formatCurrency(visit.invoice.total) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'المدفوع' : 'Paid' }}</span><span class="text-emerald-600 font-medium">{{ formatCurrency(visit.invoice.paid_amount) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-500">{{ isRtl ? 'الحالة' : 'Status' }}</span>
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border"
                                :class="visit.invoice.status === 'paid'
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : 'bg-amber-50 text-amber-700 border-amber-200'"
                            >{{ visit.invoice.status }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complete Confirmation Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showConfirmComplete" v-focus-trap="() => (showConfirmComplete = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showConfirmComplete = false">
                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95" appear>
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center border border-emerald-100">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'إكمال الزيارة؟' : 'Complete Visit?' }}</h3>
                                    <p class="text-sm text-gray-500">{{ isRtl ? 'سيتم إنهاء الزيارة وإنشاء فاتورة.' : 'This will finalize the visit and generate an invoice.' }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 mb-5">
                                <p class="text-sm text-gray-600">Patient: <strong>{{ visit.patient?.full_name }}</strong></p>
                                <p class="text-sm text-gray-600">{{ isRtl ? 'الخدمة' : 'Service' }}: <strong>{{ (isRtl ? (visit.service?.name_ar || visit.service?.name_en) : visit.service?.name_en) || visit.visit_type }}</strong></p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="showConfirmComplete = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button @click="completeVisit" class="px-5 py-2 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm shadow-emerald-200">{{ isRtl ? 'نعم، إكمال' : 'Yes, Complete' }}</button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Cancel Confirmation Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showConfirmCancel" v-focus-trap="() => (showConfirmCancel = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showConfirmCancel = false">
                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95" appear>
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center border border-red-100">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'إلغاء الزيارة؟' : 'Cancel Visit?' }}</h3>
                                    <p class="text-sm text-gray-500">{{ isRtl ? 'لا يمكن التراجع عن هذا الإجراء.' : 'This action cannot be undone.' }}</p>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="showConfirmCancel = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'رجوع' : 'Go Back' }}</button>
                                <button @click="cancelVisit" class="px-5 py-2 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors shadow-sm shadow-red-200">{{ isRtl ? 'نعم، إلغاء الزيارة' : 'Yes, Cancel Visit' }}</button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
