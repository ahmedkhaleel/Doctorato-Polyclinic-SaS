<script setup>
import { usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { ref, computed, onMounted } from 'vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: Array,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

// Modal state
const showModal = ref(false);
const selectedReportType = ref(null);
const showToast = ref(false);
const toastMessage = ref('');

const form = ref({
    patient_id: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
});

const reportTypes = computed(() => [
    {
        id: 'general',
        icon: '📋',
        title: { en: 'General Medical Report', ar: 'تقرير طبي عام' },
        description: { en: 'Comprehensive medical report with diagnosis and treatment plan', ar: 'تقرير طبي شامل مع التشخيص وخطة العلاج' },
        color: 'emerald',
    },
    {
        id: 'vaccination',
        icon: '💉',
        title: { en: 'Vaccination Certificate', ar: 'شهادة تطعيمات' },
        description: { en: 'Official vaccination record and immunization certificate', ar: 'سجل التطعيمات الرسمي وشهادة التحصين' },
        color: 'blue',
    },
    {
        id: 'growth',
        icon: '📊',
        title: { en: 'Growth Report', ar: 'تقرير النمو والتطور' },
        description: { en: 'Growth charts, developmental milestones and percentiles', ar: 'مخططات النمو ومراحل التطور والنسب المئوية' },
        color: 'purple',
    },
    {
        id: 'school',
        icon: '🏫',
        title: { en: 'School Health Certificate', ar: 'شهادة صحية مدرسية' },
        description: { en: 'Health clearance certificate for school enrollment', ar: 'شهادة اللياقة الصحية للتسجيل المدرسي' },
        color: 'amber',
    },
    {
        id: 'referral',
        icon: '🏥',
        title: { en: 'Referral Report', ar: 'تقرير تحويل' },
        description: { en: 'Specialist referral with clinical summary and reason', ar: 'تحويل لطبيب مختص مع الملخص السريري والسبب' },
        color: 'rose',
    },
    {
        id: 'medical_leave',
        icon: '📝',
        title: { en: 'Medical Leave', ar: 'إجازة مرضية' },
        description: { en: 'Medical leave certificate with diagnosis and duration', ar: 'شهادة إجازة مرضية مع التشخيص والمدة' },
        color: 'orange',
    },
]);

const colorClasses = {
    emerald: {
        bg: 'bg-emerald-50', border: 'border-emerald-200', icon: 'bg-emerald-100',
        text: 'text-emerald-700', hover: 'hover:border-emerald-300 hover:shadow-emerald-100/50',
        btn: 'bg-emerald-500 hover:bg-emerald-600',
    },
    blue: {
        bg: 'bg-slate-50', border: 'border-slate-200', icon: 'bg-slate-100',
        text: 'text-[#1B365D]', hover: 'hover:border-slate-300 hover:shadow-blue-100/50',
        btn: 'bg-[#1B365D] hover:bg-[#1B365D]',
    },
    purple: {
        bg: 'bg-slate-50', border: 'border-slate-200', icon: 'bg-slate-100',
        text: 'text-[#1B365D]', hover: 'hover:border-slate-300 hover:shadow-purple-100/50',
        btn: 'bg-[#1B365D] hover:bg-[#1B365D]',
    },
    amber: {
        bg: 'bg-amber-50', border: 'border-amber-200', icon: 'bg-amber-100',
        text: 'text-amber-700', hover: 'hover:border-amber-300 hover:shadow-amber-100/50',
        btn: 'bg-amber-500 hover:bg-amber-600',
    },
    rose: {
        bg: 'bg-amber-50', border: 'border-amber-200', icon: 'bg-amber-100',
        text: 'text-[#C4A265]', hover: 'hover:border-amber-300 hover:shadow-rose-100/50',
        btn: 'bg-[#C4A265] hover:bg-[#C4A265]',
    },
    orange: {
        bg: 'bg-amber-50', border: 'border-amber-200', icon: 'bg-amber-100',
        text: 'text-amber-700', hover: 'hover:border-amber-300 hover:shadow-orange-100/50',
        btn: 'bg-amber-500 hover:bg-amber-600',
    },
};

function t(obj) {
    if (!obj) return '';
    return isRtl.value ? obj.ar : obj.en;
}

function openReport(report) {
    selectedReportType.value = report;
    form.value = {
        patient_id: '',
        date: new Date().toISOString().split('T')[0],
        notes: '',
        // Referral fields
        referred_to: '',
        reason: '',
        clinical_summary: '',
        urgency: 'routine',
        // Medical leave fields
        leave_from: new Date().toISOString().split('T')[0],
        leave_to: new Date(Date.now() + 2 * 86400000).toISOString().split('T')[0],
        diagnosis: '',
    };
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedReportType.value = null;
}

const generating = ref(false);

function generateReport() {
    if (!form.value.patient_id) {
        toastMessage.value = isRtl.value ? 'يرجى اختيار المريض أولاً' : 'Please select a patient first';
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 3000);
        return;
    }

    const reportId = selectedReportType.value?.id;
    const patientId = form.value.patient_id;

    // Reports with existing PDF endpoints
    if (reportId === 'vaccination') {
        window.open(`/doctor/pediatric/patients/${patientId}/vaccination-card`, '_blank');
        showModal.value = false;
        toastMessage.value = isRtl.value ? 'تم فتح شهادة التطعيمات' : 'Vaccination certificate opened';
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 3000);
        return;
    }

    if (reportId === 'growth') {
        window.open(`/doctor/pediatric/patients/${patientId}/growth-report`, '_blank');
        showModal.value = false;
        toastMessage.value = isRtl.value ? 'تم فتح تقرير النمو' : 'Growth report opened';
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 3000);
        return;
    }

    // Build query params from form data
    const params = new URLSearchParams();
    if (form.value.date) params.set('date', form.value.date);
    if (form.value.notes) params.set('notes', form.value.notes);

    const pdfRoutes = {
        general: 'general-report',
        school: 'school-certificate',
        referral: 'referral-letter',
        medical_leave: 'medical-leave',
    };

    const route = pdfRoutes[reportId];
    if (route) {
        // Add extra fields for specific reports
        if (reportId === 'referral') {
            if (form.value.referred_to) params.set('referred_to', form.value.referred_to);
            if (form.value.reason) params.set('reason', form.value.reason);
            if (form.value.clinical_summary) params.set('clinical_summary', form.value.clinical_summary);
            if (form.value.urgency) params.set('urgency', form.value.urgency);
        }
        if (reportId === 'medical_leave') {
            if (form.value.leave_from) params.set('leave_from', form.value.leave_from);
            if (form.value.leave_to) params.set('leave_to', form.value.leave_to);
            if (form.value.diagnosis) params.set('diagnosis', form.value.diagnosis);
        }

        const qs = params.toString();
        window.open(`/doctor/pediatric/patients/${patientId}/${route}${qs ? '?' + qs : ''}`, '_blank');
        showModal.value = false;
        toastMessage.value = isRtl.value ? 'تم فتح التقرير' : 'Report opened';
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 3000);
        return;
    }

    // Fallback for unknown types
    showModal.value = false;
    toastMessage.value = isRtl.value
        ? 'هذه الميزة قيد التطوير'
        : 'Feature coming soon';
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 4000);
}

// Patient search for modal
const patientSearch = ref('');
const showPatientDropdown = ref(false);

const filteredPatients = computed(() => {
    if (!props.patients) return [];
    const q = patientSearch.value.toLowerCase().trim();
    if (!q) return props.patients.slice(0, 10);
    return props.patients.filter(p => {
        const name = (p.full_name || p.name || p.name_en || p.name_ar || '').toLowerCase();
        const guardian = (p.guardian_name || '').toLowerCase();
        return name.includes(q) || guardian.includes(q);
    }).slice(0, 10);
});

function selectPatient(patient) {
    form.value.patient_id = patient.id;
    patientSearch.value = patient.full_name || (isRtl.value
        ? (patient.name_ar || patient.name || patient.name_en)
        : (patient.name_en || patient.name || patient.name_ar));
    showPatientDropdown.value = false;
}
</script>

<template>
    <div class="px-4 sm:px-6 pb-10">

        <!-- TOAST NOTIFICATION -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="showToast"
                class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3.5 rounded-xl bg-gray-900 text-white shadow-2xl max-w-md"
            >
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-sm font-medium">{{ toastMessage }}</p>
                <button @click="showToast = false" class="flex-shrink-0 ms-2 text-gray-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </Transition>

        <!-- HERO SECTION -->
        <div
            class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-500 p-5 sm:p-7 shadow-xl"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-white/10 blur-3xl -translate-y-1/2 translate-x-1/4"></div>
            <div class="absolute bottom-0 left-1/3 w-64 h-64 rounded-full bg-emerald-300/10 blur-3xl translate-y-1/2"></div>

            <!-- Document icon watermark -->
            <div class="absolute top-4 right-6 opacity-[0.07]">
                <svg class="w-44 h-44 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/>
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">
                            {{ isRtl ? 'التقارير الطبية' : 'Medical Reports' }}
                        </h1>
                        <p class="text-sm text-emerald-100/80 mt-0.5">
                            {{ isRtl ? 'إنشاء وإدارة التقارير والشهادات الطبية' : 'Generate and manage medical reports & certificates' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- REPORT TYPE CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
            <div
                v-for="(report, idx) in reportTypes"
                :key="report.id"
                class="rounded-xl border bg-white shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer overflow-hidden"
                :class="[
                    colorClasses[report.color]?.border,
                    colorClasses[report.color]?.hover,
                    mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0',
                ]"
                :style="{ transitionDelay: `${0.15 + idx * 0.06}s`, transition: 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1)' }"
                @click="openReport(report)"
            >
                <!-- Top color accent bar -->
                <div class="h-1" :class="colorClasses[report.color]?.btn"></div>

                <div class="p-5">
                    <!-- Icon -->
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4 border"
                        :class="[colorClasses[report.color]?.icon, colorClasses[report.color]?.border]"
                    >
                        {{ report.icon }}
                    </div>

                    <!-- Title -->
                    <h3 class="text-sm font-bold text-gray-900 mb-1.5">
                        {{ t(report.title) }}
                    </h3>

                    <!-- Description -->
                    <p class="text-xs text-gray-500 leading-relaxed mb-4">
                        {{ t(report.description) }}
                    </p>

                    <!-- Generate Button -->
                    <button
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold text-white transition-colors"
                        :class="colorClasses[report.color]?.btn"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ isRtl ? 'إنشاء تقرير' : 'Generate Report' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- RECENT REPORTS SECTION (placeholder) -->
        <div
            class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.6s"
        >
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">
                            {{ isRtl ? 'التقارير الأخيرة' : 'Recent Reports' }}
                        </h2>
                        <p class="text-[11px] text-gray-400">
                            {{ isRtl ? 'سجل التقارير المنشأة سابقاً' : 'History of previously generated reports' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-8 sm:p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-base font-semibold text-gray-600 mb-1">
                    {{ isRtl ? 'لا توجد تقارير حتى الآن' : 'No Reports Yet' }}
                </h3>
                <p class="text-sm text-gray-400 max-w-sm mx-auto">
                    {{ isRtl ? 'عند إنشاء تقارير طبية، ستظهر هنا للمراجعة وإعادة الطباعة.' : 'When you generate medical reports, they will appear here for review and reprinting.' }}
                </p>
            </div>
        </div>

        <!-- REPORT GENERATION MODAL -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="closeModal"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

                <!-- Modal content -->
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div
                        v-if="showModal"
                        class="relative z-10 w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden"
                    >
                        <!-- Modal header -->
                        <div
                            class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
                            :class="selectedReportType ? colorClasses[selectedReportType.color]?.bg : ''"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ selectedReportType?.icon }}</span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">
                                        {{ t(selectedReportType?.title) }}
                                    </h3>
                                    <p class="text-[11px] text-gray-500">
                                        {{ isRtl ? 'تعبئة بيانات التقرير' : 'Fill in report details' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="closeModal"
                                class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors"
                            >
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Modal body -->
                        <div class="px-6 py-5 space-y-4">
                            <!-- Patient Selection (Searchable) -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                    {{ isRtl ? 'المريض' : 'Patient' }}
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="patientSearch"
                                        @focus="showPatientDropdown = true"
                                        @blur="setTimeout(() => showPatientDropdown = false, 200)"
                                        type="text"
                                        class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:text-gray-400"
                                        :placeholder="isRtl ? 'ابحث عن مريض...' : 'Search for a patient...'"
                                    />
                                    <div class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>

                                    <!-- Dropdown -->
                                    <div
                                        v-if="showPatientDropdown && filteredPatients.length"
                                        class="absolute z-20 top-full mt-1 w-full bg-white rounded-xl border border-gray-200 shadow-lg max-h-48 overflow-y-auto"
                                    >
                                        <button
                                            v-for="p in filteredPatients"
                                            :key="p.id"
                                            type="button"
                                            class="w-full text-start px-3.5 py-2.5 text-sm hover:bg-emerald-50 transition-colors flex items-center gap-2.5 border-b border-gray-100 last:border-0"
                                            @mousedown.prevent="selectPatient(p)"
                                        >
                                            <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center text-xs font-bold text-emerald-700 flex-shrink-0">
                                                {{ (p.full_name || p.name || '?').charAt(0) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-gray-700">{{ p.full_name || p.name || p.name_en }}</span>
                                                <span v-if="p.guardian_name" class="text-[10px] text-gray-400">{{ p.guardian_name }}</span>
                                            </div>
                                        </button>
                                    </div>

                                    <div
                                        v-if="showPatientDropdown && !filteredPatients.length && patients && patients.length"
                                        class="absolute z-20 top-full mt-1 w-full bg-white rounded-xl border border-gray-200 shadow-lg p-4 text-center"
                                    >
                                        <p class="text-xs text-gray-400">
                                            {{ isRtl ? 'لا توجد نتائج' : 'No results found' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Date -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                    {{ isRtl ? 'التاريخ' : 'Date' }}
                                </label>
                                <input
                                    v-model="form.date"
                                    type="date"
                                    class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/30/20 focus:border-[#1B365D] transition-all"
                                />
                            </div>

                            <!-- Additional Notes -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                    {{ isRtl ? 'ملاحظات إضافية' : 'Additional Notes' }}
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/30/20 focus:border-[#1B365D] transition-all resize-none placeholder:text-gray-400"
                                    :placeholder="isRtl ? 'أضف ملاحظات إضافية للتقرير...' : 'Add additional notes for the report...'"
                                ></textarea>
                            </div>

                            <!-- Referral-specific fields -->
                            <template v-if="selectedReportType?.id === 'referral'">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'التحويل إلى' : 'Referred To' }}</label>
                                    <input v-model="form.referred_to" type="text" class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all placeholder:text-gray-400" :placeholder="isRtl ? 'اسم الطبيب أو التخصص...' : 'Doctor name or specialty...'" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'سبب التحويل' : 'Reason for Referral' }}</label>
                                    <textarea v-model="form.reason" rows="2" class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all resize-none placeholder:text-gray-400" :placeholder="isRtl ? 'سبب التحويل...' : 'Reason for referral...'"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'الملخص السريري' : 'Clinical Summary' }}</label>
                                    <textarea v-model="form.clinical_summary" rows="2" class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all resize-none placeholder:text-gray-400" :placeholder="isRtl ? 'ملخص الحالة السريرية...' : 'Clinical summary...'"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'الأولوية' : 'Urgency' }}</label>
                                    <select v-model="form.urgency" class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all">
                                        <option value="routine">{{ isRtl ? 'روتيني' : 'Routine' }}</option>
                                        <option value="urgent">{{ isRtl ? 'عاجل' : 'Urgent' }}</option>
                                        <option value="emergency">{{ isRtl ? 'طوارئ' : 'Emergency' }}</option>
                                    </select>
                                </div>
                            </template>

                            <!-- Medical Leave-specific fields -->
                            <template v-if="selectedReportType?.id === 'medical_leave'">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</label>
                                    <input v-model="form.diagnosis" type="text" class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/30/20 focus:border-[#1B365D] transition-all placeholder:text-gray-400" :placeholder="isRtl ? 'التشخيص...' : 'Diagnosis...'" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'من تاريخ' : 'Leave From' }}</label>
                                        <input v-model="form.leave_from" type="date" class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/30/20 focus:border-[#1B365D] transition-all" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'إلى تاريخ' : 'Leave To' }}</label>
                                        <input v-model="form.leave_to" type="date" class="doctorato-input w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-[#C4A265]/30/20 focus:border-[#1B365D] transition-all" />
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Modal footer -->
                        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button
                                @click="closeModal"
                                class="px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
                            >
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button
                                @click="generateReport"
                                class="px-5 py-2.5 rounded-xl text-xs font-semibold text-white transition-colors flex items-center gap-2"
                                :class="selectedReportType ? colorClasses[selectedReportType.color]?.btn : 'bg-emerald-500 hover:bg-emerald-600'"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ isRtl ? 'إنشاء التقرير' : 'Generate Report' }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

    </div>
</template>
