<script setup>
import { usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { getChildAge as _getChildAge } from '@/Composables/usePediatricAge';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: Array,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const schedule = computed(() => [
    {
        age: { en: '1 Week', ar: 'أسبوع واحد' },
        checkups: [
            { en: 'General exam', ar: 'فحص عام' },
            { en: 'Weight', ar: 'الوزن' },
            { en: 'Feeding assessment', ar: 'تقييم الرضاعة' },
        ],
        tests: [
            { en: 'Jaundice check', ar: 'فحص اليرقان' },
        ],
    },
    {
        age: { en: '1 Month', ar: 'شهر واحد' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Development', ar: 'التطور' },
            { en: 'Nutrition', ar: 'التغذية' },
        ],
        tests: [],
    },
    {
        age: { en: '2 Months', ar: 'شهران' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Vaccines', ar: 'التطعيمات' },
            { en: 'Development', ar: 'التطور' },
        ],
        tests: [],
    },
    {
        age: { en: '4 Months', ar: '4 أشهر' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Vaccines', ar: 'التطعيمات' },
            { en: 'Development', ar: 'التطور' },
        ],
        tests: [],
    },
    {
        age: { en: '6 Months', ar: '6 أشهر' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Vaccines', ar: 'التطعيمات' },
            { en: 'Nutrition intro', ar: 'إدخال الطعام' },
        ],
        tests: [
            { en: 'Anemia check', ar: 'فحص فقر الدم' },
        ],
    },
    {
        age: { en: '9 Months', ar: '9 أشهر' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Development', ar: 'التطور' },
        ],
        tests: [],
    },
    {
        age: { en: '12 Months', ar: '12 شهر' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Vaccines', ar: 'التطعيمات' },
            { en: 'Development', ar: 'التطور' },
            { en: 'Nutrition', ar: 'التغذية' },
        ],
        tests: [],
    },
    {
        age: { en: '15 Months', ar: '15 شهر' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Vaccines', ar: 'التطعيمات' },
            { en: 'Development', ar: 'التطور' },
        ],
        tests: [],
    },
    {
        age: { en: '18 Months', ar: '18 شهر' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Vaccines', ar: 'التطعيمات' },
            { en: 'Development', ar: 'التطور' },
        ],
        tests: [
            { en: 'M-CHAT Autism', ar: 'فحص التوحد M-CHAT' },
        ],
    },
    {
        age: { en: '24 Months', ar: '24 شهر' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Vaccines', ar: 'التطعيمات' },
            { en: 'Development', ar: 'التطور' },
        ],
        tests: [
            { en: 'M-CHAT Autism', ar: 'فحص التوحد M-CHAT' },
        ],
    },
    {
        age: { en: '3 Years', ar: '3 سنوات' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Blood pressure', ar: 'ضغط الدم' },
            { en: 'Vision', ar: 'فحص النظر' },
            { en: 'Dental', ar: 'فحص الأسنان' },
        ],
        tests: [],
    },
    {
        age: { en: '4 Years', ar: '4 سنوات' },
        checkups: [
            { en: 'Growth', ar: 'النمو' },
            { en: 'Vaccines', ar: 'التطعيمات' },
            { en: 'Vision', ar: 'فحص النظر' },
            { en: 'Hearing', ar: 'فحص السمع' },
        ],
        tests: [
            { en: 'School readiness', ar: 'جاهزية المدرسة' },
        ],
    },
    {
        age: { en: '5-6 Years', ar: '5-6 سنوات' },
        checkups: [
            { en: 'Pre-school exam', ar: 'فحص ما قبل المدرسة' },
        ],
        tests: [],
    },
    {
        age: { en: '6-18 Years', ar: '6-18 سنة' },
        checkups: [
            { en: 'Annual Growth', ar: 'النمو السنوي' },
            { en: 'BMI', ar: 'مؤشر كتلة الجسم' },
            { en: 'Blood pressure', ar: 'ضغط الدم' },
        ],
        tests: [],
    },
]);

function t(obj) {
    if (!obj) return '';
    return isRtl.value ? obj.ar : obj.en;
}

function getChildAge(dob) {
    return _getChildAge(dob, isRtl.value);
}

function formatDate(dateStr) {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    const locale = isRtl.value ? 'ar-EG' : 'en-GB';
    return d.toLocaleDateString(locale, { day: 'numeric', month: 'short', year: 'numeric' });
}

// Determine which milestone index a patient's age maps to
function getMilestoneIndex(dob) {
    if (!dob) return -1;
    const birth = new Date(dob);
    const now = new Date();
    const months = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth());
    const milestoneMonths = [0.25, 1, 2, 4, 6, 9, 12, 15, 18, 24, 36, 48, 66, 72];
    for (let i = milestoneMonths.length - 1; i >= 0; i--) {
        if (months >= milestoneMonths[i]) return i;
    }
    return 0;
}
</script>

<template>
    <div class="px-4 sm:px-6 pb-10">

        <!-- HERO SECTION -->
        <div
            class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-500 p-5 sm:p-7 shadow-xl"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-white/10 blur-3xl -translate-y-1/2 translate-x-1/4"></div>
            <div class="absolute bottom-0 left-1/3 w-64 h-64 rounded-full bg-emerald-300/10 blur-3xl translate-y-1/2"></div>

            <!-- Calendar icon watermark -->
            <div class="absolute top-4 right-6 opacity-[0.07]">
                <svg class="w-44 h-44 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19a2 2 0 002 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/>
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">
                            {{ isRtl ? 'جدول زيارات الطفل السليم' : 'Well-Child Visit Schedule' }}
                        </h1>
                        <p class="text-sm text-emerald-100/80 mt-0.5">
                            {{ isRtl ? 'جدول الزيارات الموصى به من منظمة الصحة العالمية' : 'WHO-Recommended Visit Schedule' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- INFO BANNER -->
        <div
            class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-start gap-3"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.1s"
        >
            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-emerald-800">
                    {{ isRtl ? 'دليل الزيارات المرجعي' : 'Visit Reference Guide' }}
                </p>
                <p class="text-xs text-emerald-600 mt-0.5">
                    {{ isRtl ? 'هذا الجدول يعتمد على توصيات منظمة الصحة العالمية للفحوصات الدورية للأطفال الأصحاء. الفحوصات الخضراء إلزامية والفحوصات الزرقاء اختبارات مطلوبة.' : 'This schedule follows WHO guidelines for routine well-child checkups. Green tags are required checkups and blue tags are required tests.' }}
                </p>
            </div>
        </div>

        <!-- SCHEDULE TABLE -->
        <div class="space-y-3 mb-10">
            <div
                v-for="(item, idx) in schedule"
                :key="idx"
                class="group rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-300"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                :style="{ transitionDelay: `${0.15 + idx * 0.04}s`, transition: 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1)' }"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5 p-4 sm:p-5">
                    <!-- Age Milestone -->
                    <div class="flex items-center gap-3 sm:min-w-[140px]">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                            <span class="text-sm font-bold text-emerald-700">{{ idx + 1 }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ t(item.age) }}</p>
                            <p class="text-[11px] text-gray-400">
                                {{ isRtl ? `الزيارة ${idx + 1}` : `Visit ${idx + 1}` }}
                            </p>
                        </div>
                    </div>

                    <!-- Divider (desktop) -->
                    <div class="hidden sm:block w-px h-10 bg-gray-200"></div>

                    <!-- Checkups & Tests -->
                    <div class="flex-1 flex flex-col gap-2">
                        <!-- Checkups -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider me-1">
                                {{ isRtl ? 'الفحوصات' : 'Checkups' }}
                            </span>
                            <span
                                v-for="(checkup, cIdx) in item.checkups"
                                :key="'c-' + cIdx"
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60"
                            >
                                {{ t(checkup) }}
                            </span>
                        </div>
                        <!-- Tests -->
                        <div v-if="item.tests.length" class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider me-1">
                                {{ isRtl ? 'الاختبارات' : 'Tests' }}
                            </span>
                            <span
                                v-for="(test, tIdx) in item.tests"
                                :key="'t-' + tIdx"
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-50 text-[#1B365D] border border-slate-200/60"
                            >
                                {{ t(test) }}
                            </span>
                        </div>
                        <div v-else class="flex items-center gap-1.5">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider me-1">
                                {{ isRtl ? 'الاختبارات' : 'Tests' }}
                            </span>
                            <span class="text-xs text-gray-300">--</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PATIENT TRACKING SECTION -->
        <div
            v-if="patients && patients.length"
            class="mt-8"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.8s"
        >
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ isRtl ? 'متابعة المرضى' : 'Patient Tracking' }}
                    </h2>
                    <p class="text-xs text-gray-500">
                        {{ isRtl ? 'الزيارات المكتملة والقادمة للمرضى' : 'Completed and upcoming visits for patients' }}
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(patient, pIdx) in patients"
                    :key="patient.id || pIdx"
                    class="rounded-xl border border-gray-200 bg-white shadow-sm p-4 sm:p-5 hover:border-emerald-200 hover:shadow-md transition-all duration-200"
                >
                    <!-- Patient Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-sm font-bold text-emerald-700">
                                {{ (patient.full_name || patient.name || '?').charAt(0) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ patient.full_name || patient.name || patient.name_en }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ isRtl ? 'العمر:' : 'Age:' }} {{ getChildAge(patient.date_of_birth || patient.dob) }}
                                    <span v-if="patient.guardian_name" class="ms-2 text-gray-400">|</span>
                                    <span v-if="patient.guardian_name" class="ms-1">
                                        {{ isRtl ? 'ولي الأمر:' : 'Guardian:' }} {{ patient.guardian_name }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <a
                            :href="`/doctor/pediatric/well-child/${patient.id}`"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ isRtl ? 'عرض الجدول' : 'View Schedule' }}
                        </a>
                    </div>

                    <!-- Visual Timeline -->
                    <div class="flex items-center gap-1 overflow-x-auto pb-1">
                        <div
                            v-for="(item, mIdx) in schedule"
                            :key="mIdx"
                            class="flex flex-col items-center flex-shrink-0"
                        >
                            <div
                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-[10px] font-bold border-2 transition-colors"
                                :class="mIdx <= getMilestoneIndex(patient.date_of_birth || patient.dob)
                                    ? 'bg-emerald-500 border-emerald-500 text-white'
                                    : 'bg-gray-100 border-gray-300 text-gray-400'"
                            >
                                <svg v-if="mIdx <= getMilestoneIndex(patient.date_of_birth || patient.dob)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <span v-else>{{ mIdx + 1 }}</span>
                            </div>
                            <p class="text-[9px] text-gray-400 mt-1 text-center w-12 leading-tight">
                                {{ t(item.age) }}
                            </p>
                            <!-- Connector line -->
                            <div v-if="mIdx < schedule.length - 1" class="absolute"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- EMPTY STATE (no patients) -->
        <div
            v-if="!patients || !patients.length"
            class="mt-4 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 p-8 sm:p-12 text-center"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.8s"
        >
            <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <h3 class="text-base font-semibold text-gray-700 mb-1">
                {{ isRtl ? 'لا يوجد مرضى حاليا' : 'No Patients Yet' }}
            </h3>
            <p class="text-sm text-gray-400 max-w-sm mx-auto">
                {{ isRtl ? 'عند إضافة مرضى أطفال، ستظهر هنا متابعة زياراتهم مقارنة بالجدول المرجعي أعلاه.' : 'When pediatric patients are added, their visit tracking against the schedule above will appear here.' }}
            </p>
        </div>

    </div>
</template>
