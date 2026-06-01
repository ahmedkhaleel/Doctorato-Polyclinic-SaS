<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visit: Object,
});

/* ── Entrance animations ──────────────────────────────────── */
const headerLoaded = ref(false);
const cardsLoaded = ref(false);
const sidebarLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
    setTimeout(() => { sidebarLoaded.value = true; }, 350);
});

/* ── Status configuration ─────────────────────────────────── */
const statusConfig = computed(() => ({
    waiting: { label: isRtl.value ? 'انتظار' : 'Waiting', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-400', heroBg: 'from-amber-600 to-amber-500' },
    in_progress: { label: isRtl.value ? 'جاري' : 'In Progress', bg: 'bg-slate-50', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]', heroBg: 'from-[#1B365D] to-[#1B365D]' },
    completed: { label: isRtl.value ? 'مكتمل' : 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', heroBg: 'from-emerald-600 to-emerald-500' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-gray-50', text: 'text-gray-500', dot: 'bg-gray-400', heroBg: 'from-gray-600 to-gray-500' },
}));

const currentStatus = computed(() => statusConfig.value[props.visit?.status] || statusConfig.value.waiting);

/* ── Visit type labels ────────────────────────────────────── */
const visitTypeLabels = computed(() => ({
    consultation: isRtl.value ? 'استشارة' : 'Consultation',
    session: isRtl.value ? 'جلسة' : 'Session',
    follow_up: isRtl.value ? 'متابعة' : 'Follow Up',
}));

const visitTypeBadgeColors = {
    consultation: 'bg-teal-100 text-teal-700',
    session: 'bg-slate-100 text-[#1B365D]',
    follow_up: 'bg-slate-100 text-[#1B365D]',
};

/* ── Helper functions ─────────────────────────────────────── */
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatDateTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleString(isRtl.value ? 'ar-EG' : 'en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatAge(dob) {
    if (!dob) return '-';
    const birth = new Date(dob);
    const now = new Date();
    const years = Math.floor((now - birth) / (365.25 * 24 * 60 * 60 * 1000));
    const months = Math.floor(((now - birth) % (365.25 * 24 * 60 * 60 * 1000)) / (30.44 * 24 * 60 * 60 * 1000));
    if (years < 1) {
        return isRtl.value ? `${months} شهر` : `${months}m`;
    }
    if (isRtl.value) {
        return months > 0 ? `${years} سنة و ${months} شهر` : `${years} سنة`;
    }
    return months > 0 ? `${years}y ${months}m` : `${years}y`;
}

function formatTime(time) {
    if (!time) return '-';
    if (typeof time === 'string' && time.length <= 8) {
        const [h, m] = time.split(':');
        const hour = parseInt(h);
        const ampm = hour >= 12 ? (isRtl.value ? 'م' : 'PM') : (isRtl.value ? 'ص' : 'AM');
        const h12 = hour % 12 || 12;
        return `${h12}:${m} ${ampm}`;
    }
    return new Date(time).toLocaleTimeString(isRtl.value ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit' });
}

const genderIcon = computed(() => {
    const g = props.visit?.patient?.gender;
    if (g === 'male') return '';
    if (g === 'female') return '';
    return '';
});

const genderLabel = computed(() => {
    const g = props.visit?.patient?.gender;
    if (g === 'male') return isRtl.value ? 'ذكر' : 'Male';
    if (g === 'female') return isRtl.value ? 'أنثى' : 'Female';
    return '-';
});

const doctorName = computed(() => {
    const d = props.visit?.doctor;
    if (!d) return '-';
    return isRtl.value ? (d.name_ar || d.name_en || '-') : (d.name_en || d.name_ar || '-');
});
</script>

<template>
    <div>
        <!-- ═══ BACK BUTTON + BREADCRUMB ═══ -->
        <div
            class="mb-6 transition-all duration-500"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
        >
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <Link href="/secretary/pediatric/visits" class="flex items-center gap-1.5 text-teal-600 hover:text-teal-700 transition font-medium">
                    <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ isRtl ? 'زيارات الأطفال' : 'Pediatric Visits' }}
                </Link>
                <svg class="w-3.5 h-3.5 text-gray-300" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-400">{{ isRtl ? 'تفاصيل الزيارة' : 'Visit Details' }} #{{ visit.id }}</span>
            </nav>
        </div>

        <!-- ═══ VISIT HEADER CARD (Hero) ═══ -->
        <div
            class="mb-8 rounded-2xl overflow-hidden shadow-lg transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div class="bg-gradient-to-r p-6 sm:p-8 relative" :class="currentStatus.heroBg">
                <!-- Decorative elements -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
                <div class="absolute bottom-0 left-0 w-40 h-40 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/4"></div>

                <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <!-- Patient Avatar -->
                        <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-3xl shadow-lg border border-white/20">
                            {{ genderIcon }}
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                                {{ visit.patient?.full_name || '-' }}
                                <span class="text-white/80 text-sm font-normal">
                                    ({{ formatAge(visit.patient?.date_of_birth) }})
                                </span>
                            </h1>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5">
                                <span v-if="visit.patient?.guardian_name" class="text-white/80 text-sm flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ isRtl ? 'ولي الأمر:' : 'Guardian:' }} {{ visit.patient.guardian_name }}
                                </span>
                                <span v-if="visit.patient?.file_number" class="text-white/80 text-sm font-mono flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    #{{ visit.patient.file_number }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-start sm:items-end gap-2">
                        <!-- Status Badge -->
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-white/20 backdrop-blur-sm text-white border border-white/30">
                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                            {{ currentStatus.label }}
                        </span>
                        <!-- Visit date & doctor -->
                        <div class="text-white/80 text-sm flex items-center gap-3">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(visit.visit_date) }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ isRtl ? 'د.' : 'Dr.' }} {{ doctorName }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TWO-COLUMN LAYOUT ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- ── LEFT COLUMN (2/3) ── -->
            <div
                class="lg:col-span-2 space-y-6 transition-all duration-700 delay-100"
                :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
            >
                <!-- Visit Information Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-emerald-50 to-emerald-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-emerald-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            {{ isRtl ? 'معلومات الزيارة' : 'Visit Information' }}
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'نوع الزيارة' : 'Visit Type' }}</dt>
                                <dd>
                                    <span
                                        :class="visitTypeBadgeColors[visit.visit_type] || 'bg-gray-100 text-gray-600'"
                                        class="px-2.5 py-0.5 text-xs font-semibold rounded-full"
                                    >
                                        {{ visitTypeLabels[visit.visit_type] || visit.visit_type || '-' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'القسم' : 'Module' }}</dt>
                                <dd class="text-sm text-gray-900 capitalize">{{ visit.module || '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الخدمة' : 'Service' }}</dt>
                                <dd class="text-sm text-gray-900">{{ visit.service?.name_en || visit.service?.name || visit.service_name || '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'تاريخ الزيارة' : 'Visit Date' }}</dt>
                                <dd class="text-sm text-gray-900">{{ formatDate(visit.visit_date) }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الوقت المحدد' : 'Scheduled Time' }}</dt>
                                <dd class="text-sm text-gray-900">{{ formatTime(visit.scheduled_time) }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'وقت البدء' : 'Started At' }}</dt>
                                <dd class="text-sm text-gray-900">{{ visit.started_at ? formatDateTime(visit.started_at) : (isRtl ? 'لم يبدأ بعد' : 'Not started') }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'وقت الانتهاء' : 'Completed At' }}</dt>
                                <dd class="text-sm text-gray-900">{{ visit.completed_at ? formatDateTime(visit.completed_at) : (isRtl ? 'لم يكتمل بعد' : 'Not completed') }}</dd>
                            </div>

                            <div v-if="visit.session_number">
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'رقم الجلسة' : 'Session Number' }}</dt>
                                <dd class="text-sm text-gray-900 font-semibold">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                        {{ visit.session_number }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الحالة' : 'Status' }}</dt>
                                <dd>
                                    <span
                                        :class="[currentStatus.bg, currentStatus.text]"
                                        class="px-2.5 py-0.5 text-xs font-semibold rounded-full border inline-flex items-center gap-1.5"
                                    >
                                        <span :class="currentStatus.dot" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ currentStatus.label }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Diagnosis & Notes Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-emerald-50 to-emerald-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-emerald-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ isRtl ? 'التشخيص والملاحظات' : 'Diagnosis & Notes' }}
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6 space-y-5">
                        <!-- Diagnosis -->
                        <div>
                            <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</h3>
                            <div v-if="visit.diagnosis" class="text-sm text-gray-800 leading-relaxed bg-emerald-50/50 border border-emerald-100 rounded-lg p-3">
                                {{ visit.diagnosis }}
                            </div>
                            <p v-else class="text-sm text-gray-400 italic">{{ isRtl ? 'لا يوجد تشخيص بعد' : 'No diagnosis yet' }}</p>
                        </div>
                        <!-- Doctor Notes -->
                        <div>
                            <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</h3>
                            <div v-if="visit.doctor_notes" class="text-sm text-gray-800 leading-relaxed bg-gray-50 border border-gray-100 rounded-lg p-3">
                                {{ visit.doctor_notes }}
                            </div>
                            <p v-else class="text-sm text-gray-400 italic">{{ isRtl ? 'لا توجد ملاحظات بعد' : 'No notes yet' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Prescriptions Section -->
                <div v-if="visit.prescriptions && visit.prescriptions.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-emerald-50 to-emerald-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-emerald-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            {{ isRtl ? 'الوصفات الطبية' : 'Prescriptions' }}
                            <span class="ltr:ml-auto rtl:mr-auto text-xs font-normal text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">
                                {{ visit.prescriptions.length }}
                            </span>
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="prescription in visit.prescriptions" :key="prescription.id" class="p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    {{ isRtl ? 'وصفة' : 'Prescription' }} #{{ prescription.id }}
                                </h3>
                                <span class="text-xs text-gray-400 flex-shrink-0">{{ formatDate(prescription.created_at) }}</span>
                            </div>
                            <p v-if="prescription.notes" class="text-sm text-gray-600 mb-3">{{ prescription.notes }}</p>

                            <!-- Prescription Items -->
                            <div v-if="prescription.items && prescription.items.length" class="space-y-2">
                                <div
                                    v-for="item in prescription.items"
                                    :key="item.id"
                                    class="flex flex-wrap items-start gap-x-4 gap-y-1 bg-gray-50 rounded-lg px-3 py-2.5 text-sm"
                                >
                                    <div class="flex items-center gap-2 font-medium text-gray-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                                        {{ item.medication?.name || item.medication_name || '-' }}
                                    </div>
                                    <span v-if="item.dosage" class="text-gray-500 text-xs bg-white px-2 py-0.5 rounded border border-gray-200">
                                        {{ isRtl ? 'الجرعة:' : 'Dosage:' }} {{ item.dosage }}
                                    </span>
                                    <span v-if="item.frequency" class="text-gray-500 text-xs bg-white px-2 py-0.5 rounded border border-gray-200">
                                        {{ isRtl ? 'التكرار:' : 'Freq:' }} {{ item.frequency }}
                                    </span>
                                    <span v-if="item.duration" class="text-gray-500 text-xs bg-white px-2 py-0.5 rounded border border-gray-200">
                                        {{ isRtl ? 'المدة:' : 'Duration:' }} {{ item.duration }}
                                    </span>
                                </div>
                            </div>
                            <p v-else class="text-xs text-gray-400 italic">{{ isRtl ? 'لا توجد أدوية' : 'No items' }}</p>
                        </div>
                    </div>
                </div>

                <!-- No prescriptions placeholder -->
                <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-emerald-50 to-emerald-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-emerald-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            {{ isRtl ? 'الوصفات الطبية' : 'Prescriptions' }}
                        </h2>
                    </div>
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد وصفات طبية لهذه الزيارة' : 'No prescriptions for this visit' }}</p>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT COLUMN (1/3) ── -->
            <div
                class="space-y-6 transition-all duration-700 delay-200"
                :class="sidebarLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
            >
                <!-- Patient Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-emerald-50 to-emerald-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-emerald-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ isRtl ? 'بيانات المريض' : 'Patient Info' }}
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6">
                        <!-- Avatar & Name -->
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-500 flex items-center justify-center text-white text-xl font-bold shadow-md">
                                {{ visit.patient?.full_name?.charAt(0) || '?' }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ visit.patient?.full_name || '-' }}</p>
                                <p v-if="visit.patient?.file_number" class="text-xs text-emerald-600 font-mono mt-0.5">
                                    {{ isRtl ? 'ملف' : 'File' }} #{{ visit.patient.file_number }}
                                </p>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="space-y-3">
                            <div v-if="visit.patient?.phone" class="flex items-center gap-2.5 text-sm">
                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400">{{ isRtl ? 'الهاتف' : 'Phone' }}</span>
                                    <p class="text-gray-700 font-medium" dir="ltr">{{ visit.patient.phone }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 text-sm">
                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400">{{ isRtl ? 'الجنس' : 'Gender' }}</span>
                                    <p class="text-gray-700 font-medium">{{ genderLabel }}</p>
                                </div>
                            </div>

                            <div v-if="visit.patient?.date_of_birth" class="flex items-center gap-2.5 text-sm">
                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }}</span>
                                    <p class="text-gray-700 font-medium">{{ formatDate(visit.patient.date_of_birth) }} ({{ formatAge(visit.patient.date_of_birth) }})</p>
                                </div>
                            </div>

                            <div v-if="visit.patient?.guardian_name" class="flex items-center gap-2.5 text-sm">
                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400">{{ isRtl ? 'ولي الأمر' : 'Guardian' }}</span>
                                    <p class="text-gray-700 font-medium">{{ visit.patient.guardian_name }}</p>
                                    <p v-if="visit.patient.guardian_phone" class="text-xs text-gray-400 mt-0.5" dir="ltr">{{ visit.patient.guardian_phone }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 bg-gradient-to-r from-teal-50 to-slate-50 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-teal-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ isRtl ? 'روابط سريعة' : 'Quick Links' }}
                        </h2>
                    </div>
                    <div class="p-4 sm:p-6 space-y-2">
                        <Link
                            href="/secretary/pediatric/visits"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition border border-gray-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            {{ isRtl ? 'جميع الزيارات' : 'All Visits' }}
                        </Link>
                        <Link
                            :href="`/secretary/pediatric/patients/${visit.patient_id || visit.patient?.id}`"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg text-sm font-medium text-emerald-700 hover:bg-emerald-50 transition border border-emerald-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ isRtl ? 'ملف المريض' : 'Patient Profile' }}
                        </Link>
                        <Link
                            href="/secretary/bookings/create"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition shadow-sm"
                            style="background: linear-gradient(135deg, #0d9488, #4CAF50);"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ isRtl ? 'حجز جديد' : 'New Booking' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
