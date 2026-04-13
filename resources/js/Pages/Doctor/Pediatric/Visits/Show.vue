<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visit: Object,
    allergies: Array,
    latestGrowth: Object,
    ageMonths: Number,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

// ─── Diagnosis Form ──────────────────────────────
const diagnosisForm = useForm({
    diagnosis: props.visit.diagnosis || '',
    doctor_notes: props.visit.doctor_notes || '',
});

function saveDiagnosis() {
    diagnosisForm.post(`/doctor/visits/${props.visit.id}/diagnosis`, {
        preserveScroll: true,
        onSuccess: () => {},
    });
}

// ─── Visit Actions ───────────────────────────────
const showCompleteModal = ref(false);

function startVisit() { router.post(`/doctor/visits/${props.visit.id}/start`); }
function confirmComplete() { showCompleteModal.value = true; }
function completeVisit() {
    router.post(`/doctor/visits/${props.visit.id}/complete`);
    showCompleteModal.value = false;
}
function cancelVisit() { router.post(`/doctor/visits/${props.visit.id}/cancel`); }

// Lightbox
const lightboxOpen = ref(false);
const lightboxPhoto = ref(null);
function openLightbox(photo) { lightboxPhoto.value = photo; lightboxOpen.value = true; }
function closeLightbox() { lightboxOpen.value = false; lightboxPhoto.value = null; }

// Helpers
const patient = computed(() => props.visit?.patient || {});
const prescriptions = computed(() => props.visit?.prescriptions || []);
const photos = computed(() => props.visit?.photos || []);

const statusConfig = computed(() => ({
    waiting: { label: isRtl.value ? 'انتظار' : 'Waiting', bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-400', heroBg: 'from-amber-600 to-amber-500' },
    in_progress: { label: isRtl.value ? 'جاري' : 'In Progress', bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500', heroBg: 'from-blue-600 to-blue-500' },
    completed: { label: isRtl.value ? 'مكتمل' : 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500', heroBg: 'from-emerald-600 to-green-500' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-gray-50', text: 'text-gray-500', border: 'border-gray-200', dot: 'bg-gray-400', heroBg: 'from-gray-600 to-gray-500' },
}));

const currentStatus = computed(() => statusConfig.value[props.visit?.status] || statusConfig.value.waiting);

function formatAge(months) {
    if (!months && months !== 0) return isRtl.value ? 'غير معروف' : 'Unknown';
    if (months < 1) return isRtl.value ? 'حديث الولادة' : 'Newborn';
    if (months < 12) return isRtl.value ? `${months} شهر` : `${months} months`;
    const years = Math.floor(months / 12);
    const rem = months % 12;
    if (rem === 0) return isRtl.value ? `${years} سنة` : `${years} years`;
    return isRtl.value ? `${years} سنة و ${rem} شهر` : `${years}y ${rem}m`;
}

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(date) {
    if (!date) return '-';
    const d = new Date(date);
    const datePart = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const timePart = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    return `${datePart} ${timePart}`;
}

function severityColor(severity) {
    switch (severity) {
        case 'mild': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'moderate': return 'bg-orange-100 text-orange-800 border-orange-200';
        case 'severe': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Back Button + Breadcrumb -->
        <div class="flex items-center gap-3"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <Link href="/doctor/pediatric/visits"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl shadow-sm transition-all duration-200 hover:border-emerald-200 hover:text-emerald-600 group"
            >
                <svg class="w-4 h-4 transition-transform duration-200" :class="isRtl ? 'group-hover:translate-x-0.5' : 'group-hover:-translate-x-0.5 rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                {{ isRtl ? 'العودة للزيارات' : 'Back to Visits' }}
            </Link>
        </div>

        <!-- Visit Header Card -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br p-6 sm:p-8"
            :class="[currentStatus.heroBg, mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.05s"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <!-- Patient Avatar -->
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center bg-white/20 backdrop-blur-sm border border-white/30 flex-shrink-0">
                            <svg v-if="patient.gender === 'female'" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2a6 6 0 016 6c0 2.22-1.21 4.16-3 5.2V16h2v2h-2v2h-2v-2h-2v-2h2v-2.8A6.004 6.004 0 016 8a6 6 0 016-6zm0 2a4 4 0 100 8 4 4 0 000-8z"/>
                            </svg>
                            <svg v-else class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9.5 11c1.93 0 3.5 1.57 3.5 3.5S11.43 18 9.5 18 6 16.43 6 14.5 7.57 11 9.5 11zm0 2C8.67 13 8 13.67 8 14.5S8.67 16 9.5 16s1.5-.67 1.5-1.5S10.33 13 9.5 13zM15 3l4 4-1.5 1.5L19 10l-2 2-1.5-1.5L14 12l-1.5-1.5 4.5-4.5L15 4z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-white">{{ patient.full_name }}</h1>
                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold bg-white/20 text-white">
                                    {{ formatAge(ageMonths) }}
                                </span>
                                <span v-if="patient.gender" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-white/15 text-white/90">
                                    {{ patient.gender === 'male' ? (isRtl ? 'ذكر' : 'Male') : (isRtl ? 'أنثى' : 'Female') }}
                                </span>
                                <span v-if="patient.file_number" class="font-mono text-xs text-white/60">
                                    #{{ patient.file_number }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-2 text-white/70 text-xs">
                                <span v-if="patient.guardian_name" class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ isRtl ? 'ولي الأمر:' : 'Guardian:' }} {{ patient.guardian_name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Status + Date + Actions -->
                    <div class="flex flex-col items-start sm:items-end gap-3">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-white/20 text-white border border-white/30">
                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                            {{ currentStatus.label }}
                        </span>
                        <div class="text-xs text-white/60">
                            <p>{{ formatDateTime(visit.visit_date) }}</p>
                            <p v-if="visit.doctor?.name" class="mt-0.5">{{ isRtl ? 'الطبيب:' : 'Dr.' }} {{ visit.doctor.name }}</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Start Visit -->
                            <button v-if="visit.status === 'waiting'" @click="startVisit"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-white bg-green-500 hover:bg-green-600 rounded-xl transition-all shadow-sm shadow-green-900/30 hover:shadow-md hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /></svg>
                                {{ isRtl ? 'بدء الزيارة' : 'Start Visit' }}
                            </button>

                            <!-- Complete Visit -->
                            <button v-if="visit.status === 'in_progress'" @click="confirmComplete"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-all shadow-sm shadow-emerald-900/30 hover:shadow-md hover:-translate-y-0.5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ isRtl ? 'إكمال الزيارة' : 'Complete Visit' }}
                            </button>

                            <!-- Cancel Visit -->
                            <button v-if="visit.status === 'waiting' || visit.status === 'in_progress'" @click="cancelVisit"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white/80 hover:text-white bg-white/10 hover:bg-red-500/80 border border-white/20 hover:border-red-400 rounded-xl transition-all"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>

                            <!-- View Full Profile -->
                            <Link v-if="visit.patient_id" :href="`/doctor/pediatric/patients/${visit.patient_id}`"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white/80 hover:text-white bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl transition-all"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                {{ isRtl ? 'عرض الملف الكامل' : 'View Full Profile' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Allergy Alert Bar -->
        <div v-if="allergies && allergies.length > 0"
            class="rounded-xl border-2 border-red-200 bg-red-50 px-4 sm:px-6 py-4"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
        >
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-red-800">
                        {{ isRtl ? 'تنبيه الحساسية' : 'Allergy Alert' }}
                    </h3>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span v-for="allergy in allergies" :key="allergy.id"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold border"
                            :class="severityColor(allergy.severity)"
                        >
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            {{ allergy.allergen || allergy.name }}
                            <span v-if="allergy.severity" class="text-[10px] opacity-75">({{ allergy.severity }})</span>
                        </span>
                    </div>
                    <p v-if="allergies.some(a => a.reaction)" class="text-xs text-red-600 mt-1.5">
                        {{ isRtl ? 'التفاعلات:' : 'Reactions:' }}
                        {{ allergies.filter(a => a.reaction).map(a => a.reaction).join(', ') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Vitals (Growth) Card -->
        <div v-if="latestGrowth"
            class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
        >
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-green-50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'قياسات النمو الأخيرة' : 'Latest Growth Measurements' }}</h3>
                        <p class="text-[11px] text-gray-500">{{ formatDate(latestGrowth.measured_at || latestGrowth.created_at) }}</p>
                    </div>
                </div>
            </div>
            <div class="px-4 sm:px-6 py-5">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <!-- Weight -->
                    <div v-if="latestGrowth.weight" class="text-center p-3 rounded-xl bg-blue-50/50 border border-blue-100/50">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-blue-100 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                        </div>
                        <p class="text-lg font-bold text-blue-700">{{ latestGrowth.weight }}</p>
                        <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide">{{ isRtl ? 'الوزن (كجم)' : 'Weight (kg)' }}</p>
                    </div>
                    <!-- Height -->
                    <div v-if="latestGrowth.height" class="text-center p-3 rounded-xl bg-emerald-50/50 border border-emerald-100/50">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-emerald-100 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                        </div>
                        <p class="text-lg font-bold text-emerald-700">{{ latestGrowth.height }}</p>
                        <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide">{{ isRtl ? 'الطول (سم)' : 'Height (cm)' }}</p>
                    </div>
                    <!-- Head Circ -->
                    <div v-if="latestGrowth.head_circumference" class="text-center p-3 rounded-xl bg-purple-50/50 border border-purple-100/50">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-purple-100 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6" /></svg>
                        </div>
                        <p class="text-lg font-bold text-purple-700">{{ latestGrowth.head_circumference }}</p>
                        <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide">{{ isRtl ? 'محيط الرأس (سم)' : 'Head Circ (cm)' }}</p>
                    </div>
                    <!-- BMI -->
                    <div v-if="latestGrowth.bmi" class="text-center p-3 rounded-xl bg-amber-50/50 border border-amber-100/50">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-amber-100 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </div>
                        <p class="text-lg font-bold text-amber-700">{{ latestGrowth.bmi }}</p>
                        <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide">{{ isRtl ? 'مؤشر كتلة الجسم' : 'BMI' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visit Details -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'تفاصيل الزيارة' : 'Visit Details' }}</h3>
                </div>
            </div>
            <div class="px-4 sm:px-6 py-5 space-y-5">
                <!-- Visit Type + Consultation Type -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-if="visit.visit_type">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">{{ isRtl ? 'نوع الزيارة' : 'Visit Type' }}</label>
                        <p class="text-sm text-gray-800 font-medium capitalize">{{ visit.visit_type }}</p>
                    </div>
                    <div v-if="visit.consultation_type">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">{{ isRtl ? 'نوع الاستشارة' : 'Consultation Type' }}</label>
                        <p class="text-sm text-gray-800 font-medium capitalize">{{ visit.consultation_type }}</p>
                    </div>
                </div>

                <!-- Read-only Diagnosis (when NOT in_progress) -->
                <div v-if="visit.diagnosis && visit.status !== 'in_progress'">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</label>
                    <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl px-4 py-3">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ visit.diagnosis }}</p>
                    </div>
                </div>

                <!-- Read-only Doctor Notes (when NOT in_progress) -->
                <div v-if="visit.doctor_notes && visit.status !== 'in_progress'">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</label>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ visit.doctor_notes }}</p>
                    </div>
                </div>

                <!-- No diagnosis/notes (when NOT in_progress) -->
                <div v-if="!visit.diagnosis && !visit.doctor_notes && visit.status !== 'in_progress'" class="text-center py-6">
                    <svg class="w-10 h-10 mx-auto text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا يوجد تشخيص أو ملاحظات بعد' : 'No diagnosis or notes yet' }}</p>
                </div>
            </div>
        </div>

        <!-- Editable Diagnosis & Notes Section (in_progress only) -->
        <div v-if="visit.status === 'in_progress'"
            class="bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.22s"
        >
            <div class="px-4 sm:px-6 py-4 border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-green-50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'التشخيص والملاحظات' : 'Diagnosis & Notes' }}</h3>
                        <p class="text-[11px] text-emerald-600 font-medium">{{ isRtl ? 'يمكنك التعديل أثناء الزيارة' : 'Editable during visit' }}</p>
                    </div>
                </div>
            </div>
            <div class="px-4 sm:px-6 py-5 space-y-4">
                <!-- Diagnosis Textarea -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide mb-1.5">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</label>
                    <textarea
                        v-model="diagnosisForm.diagnosis"
                        rows="3"
                        class="w-full rounded-xl border border-gray-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 text-sm text-gray-700 placeholder-gray-400 px-4 py-3 transition-all resize-none"
                        :class="isRtl ? 'text-right' : 'text-left'"
                        :placeholder="isRtl ? 'أدخل التشخيص...' : 'Enter diagnosis...'"
                    ></textarea>
                    <p v-if="diagnosisForm.errors.diagnosis" class="text-xs text-red-500 mt-1">{{ diagnosisForm.errors.diagnosis }}</p>
                </div>

                <!-- Doctor Notes Textarea -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide mb-1.5">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</label>
                    <textarea
                        v-model="diagnosisForm.doctor_notes"
                        rows="3"
                        class="w-full rounded-xl border border-gray-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 text-sm text-gray-700 placeholder-gray-400 px-4 py-3 transition-all resize-none"
                        :class="isRtl ? 'text-right' : 'text-left'"
                        :placeholder="isRtl ? 'أدخل ملاحظات الطبيب...' : 'Enter doctor notes...'"
                    ></textarea>
                    <p v-if="diagnosisForm.errors.doctor_notes" class="text-xs text-red-500 mt-1">{{ diagnosisForm.errors.doctor_notes }}</p>
                </div>

                <!-- Save Button -->
                <div class="flex" :class="isRtl ? 'justify-start' : 'justify-end'">
                    <button @click="saveDiagnosis"
                        :disabled="diagnosisForm.processing"
                        class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="diagnosisForm.processing ? 'bg-gray-400' : 'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-200'"
                    >
                        <svg v-if="diagnosisForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        {{ diagnosisForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ التشخيص' : 'Save Diagnosis') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Prescriptions Section -->
        <div v-if="prescriptions.length > 0"
            class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.25s"
        >
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'الوصفات الطبية' : 'Prescriptions' }}</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                        {{ prescriptions.length }}
                    </span>
                </div>
            </div>
            <div class="divide-y divide-gray-100">
                <div v-for="(prescription, pIdx) in prescriptions" :key="prescription.id || pIdx" class="px-4 sm:px-6 py-4">
                    <!-- Prescription Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 rounded-md px-2 py-0.5 border border-emerald-200">
                                Rx {{ pIdx + 1 }}
                            </span>
                            <span v-if="prescription.created_at" class="text-[11px] text-gray-400">
                                {{ formatDate(prescription.created_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Prescription Items -->
                    <div v-if="prescription.items && prescription.items.length > 0" class="space-y-2">
                        <div v-for="(item, iIdx) in prescription.items" :key="item.id || iIdx"
                            class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 p-3 bg-gray-50 rounded-lg border border-gray-100"
                        >
                            <!-- Medication -->
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <div class="w-7 h-7 rounded-md bg-white border border-gray-200 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ item.medication || item.medication_name || item.name }}</p>
                                    <p v-if="item.notes" class="text-[11px] text-gray-400 truncate">{{ item.notes }}</p>
                                </div>
                            </div>
                            <!-- Dose, Frequency, Duration -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span v-if="item.dose || item.dosage" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-md border border-blue-100 font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                    {{ item.dose || item.dosage }}
                                </span>
                                <span v-if="item.frequency" class="inline-flex items-center gap-1 px-2 py-1 bg-purple-50 text-purple-700 rounded-md border border-purple-100 font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ item.frequency }}
                                </span>
                                <span v-if="item.duration" class="inline-flex items-center gap-1 px-2 py-1 bg-amber-50 text-amber-700 rounded-md border border-amber-100 font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ item.duration }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400 italic">
                        {{ isRtl ? 'لا توجد أدوية في هذه الوصفة' : 'No items in this prescription' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Photos Section -->
        <div v-if="photos.length > 0"
            class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
        >
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'صور الزيارة' : 'Visit Photos' }}</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-200">
                        {{ photos.length }}
                    </span>
                </div>
            </div>
            <div class="px-4 sm:px-6 py-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div v-for="(photo, idx) in photos" :key="photo.id || idx"
                        @click="openLightbox(photo)"
                        class="group relative aspect-square rounded-xl overflow-hidden border border-gray-200 cursor-pointer hover:border-emerald-300 hover:shadow-md transition-all duration-200"
                    >
                        <img :src="photo.url || photo.path" :alt="photo.caption || 'Visit photo'"
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-end p-2">
                            <div class="w-full">
                                <span v-if="photo.type" class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                                    :class="photo.type === 'before' ? 'bg-amber-500 text-white' : photo.type === 'after' ? 'bg-emerald-500 text-white' : 'bg-white/80 text-gray-700'"
                                >
                                    {{ photo.type }}
                                </span>
                                <p v-if="photo.caption" class="text-[10px] text-white mt-0.5 truncate">{{ photo.caption }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div v-if="lightboxOpen"
                class="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
                @click.self="closeLightbox"
            >
                <button @click="closeLightbox"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <div class="max-w-4xl max-h-[85vh] rounded-xl overflow-hidden">
                    <img v-if="lightboxPhoto" :src="lightboxPhoto.url || lightboxPhoto.path" :alt="lightboxPhoto.caption || ''"
                        class="max-w-full max-h-[85vh] object-contain"
                    />
                </div>
                <p v-if="lightboxPhoto?.caption" class="absolute bottom-6 left-1/2 -translate-x-1/2 text-sm text-white bg-black/50 px-4 py-2 rounded-lg backdrop-blur-sm">
                    {{ lightboxPhoto.caption }}
                </p>
            </div>
        </Teleport>

        <!-- Complete Visit Confirmation Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showCompleteModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showCompleteModal = false">
                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95" appear>
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">{{ isRtl ? 'إكمال الزيارة' : 'Complete Visit' }}</h3>
                                    <p class="text-sm text-gray-500">{{ isRtl ? 'هل أنت متأكد من إكمال هذه الزيارة؟' : 'Are you sure you want to complete this visit?' }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 mb-4 text-sm text-gray-600">
                                <p>{{ isRtl ? 'المريض' : 'Patient' }}: <strong>{{ patient.full_name }}</strong></p>
                                <p v-if="visit.visit_type" class="mt-0.5">{{ isRtl ? 'نوع الزيارة' : 'Visit Type' }}: <strong class="capitalize">{{ visit.visit_type }}</strong></p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="showCompleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button @click="completeVisit" class="px-5 py-2 text-sm font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors shadow-sm shadow-emerald-200">{{ isRtl ? 'نعم، إكمال' : 'Yes, Complete' }}</button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
