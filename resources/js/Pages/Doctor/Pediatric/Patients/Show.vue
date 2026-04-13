<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, useForm, router } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import SearchableSelect from '@/Components/Doctor/SearchableSelect.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    ageMonths: Number,
    ageDays: Number,
    growthRecords: Array,
    vaccinations: Array,
    milestones: Array,
    allergies: Array,
    familyHistory: Array,
    nutritionRecords: Array,
    chronicConditions: Array,
    screeningTests: Array,
    visits: Array,
    vaccineStats: Object,
    milestoneStats: Object,
    tab: String,
    milestoneDefinitions: Array,
    vaccineSchedule: Array,
});

// ── Animation ──
const mounted = ref(false);
const headerLoaded = ref(false);
const tabsLoaded = ref(false);
const contentLoaded = ref(false);

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    setTimeout(() => { headerLoaded.value = true; }, 100);
    setTimeout(() => { tabsLoaded.value = true; }, 250);
    setTimeout(() => { contentLoaded.value = true; }, 400);
});

// ── Tabs ──
const tabs = [
    { key: 'overview', label: 'Overview', labelAr: 'نظرة عامة', icon: 'grid' },
    { key: 'growth', label: 'Growth', labelAr: 'النمو', icon: 'trending-up' },
    { key: 'vaccines', label: 'Vaccines', labelAr: 'التطعيمات', icon: 'shield' },
    { key: 'development', label: 'Development', labelAr: 'التطور', icon: 'activity' },
    { key: 'allergies', label: 'Allergies', labelAr: 'الحساسية', icon: 'alert-triangle' },
    { key: 'visits', label: 'Visits', labelAr: 'الزيارات', icon: 'clipboard' },
];

const activeTab = ref(props.tab || 'overview');

function switchTab(key) {
    activeTab.value = key;
    router.get(`/doctor/pediatric/patients/${props.patient.id}`, { tab: key }, { preserveState: true, replace: true, preserveScroll: true });
}

// ── Age Display ──
const ageDisplay = computed(() => {
    const totalMonths = props.ageMonths || 0;
    const years = Math.floor(totalMonths / 12);
    const months = totalMonths % 12;
    if (totalMonths < 24) {
        return {
            en: `${totalMonths} month${totalMonths !== 1 ? 's' : ''}${props.ageDays ? ` & ${props.ageDays} days` : ''}`,
            ar: `${totalMonths} شهر${props.ageDays ? ` و ${props.ageDays} يوم` : ''}`,
        };
    }
    return {
        en: `${years} year${years !== 1 ? 's' : ''} & ${months} month${months !== 1 ? 's' : ''}`,
        ar: `${years === 2 ? 'عامين' : years + ' سنوات'} و ${months} ${months <= 2 ? 'شهر' : 'أشهر'}`,
    };
});

// ── Overview Computed ──
const latestGrowth = computed(() => {
    if (!props.growthRecords?.length) return null;
    return [...props.growthRecords].sort((a, b) => new Date(b.measurement_date) - new Date(a.measurement_date))[0];
});

const upcomingVaccinations = computed(() => {
    return (props.vaccinations || []).filter(v => v.status === 'scheduled').slice(0, 5);
});

const activeAllergies = computed(() => {
    return (props.allergies || []).filter(a => a.status === 'active' || !a.status);
});

// ── Growth Form ──
const showGrowthForm = ref(false);
const growthForm = useForm({
    measurement_date: new Date().toISOString().split('T')[0],
    weight_kg: '',
    height_cm: '',
    head_circumference_cm: '',
    notes: '',
});

function submitGrowth() {
    growthForm.post(`/doctor/pediatric/patients/${props.patient.id}/growth`, {
        preserveScroll: true,
        onSuccess: () => {
            showGrowthForm.value = false;
            growthForm.reset();
        },
    });
}

// ── Vaccine Form ──
const editingVaccine = ref(null);
const vaccineForm = useForm({
    vaccination_id: '',
    status: 'given',
    given_date: new Date().toISOString().split('T')[0],
    batch_number: '',
    site: '',
    notes: '',
});

function openVaccineEdit(vaccination) {
    editingVaccine.value = vaccination.id;
    vaccineForm.vaccination_id = vaccination.id;
    vaccineForm.status = vaccination.status || 'given';
    vaccineForm.given_date = vaccination.given_date || new Date().toISOString().split('T')[0];
    vaccineForm.batch_number = vaccination.batch_number || '';
    vaccineForm.site = vaccination.site || '';
    vaccineForm.notes = vaccination.notes || '';
}

function submitVaccine() {
    vaccineForm.post(`/doctor/pediatric/patients/${props.patient.id}/vaccinations/${vaccineForm.vaccination_id}/update`, {
        preserveScroll: true,
        onSuccess: () => {
            editingVaccine.value = null;
            vaccineForm.reset();
        },
    });
}

function initializeSchedule() {
    router.post(`/doctor/pediatric/patients/${props.patient.id}/vaccinations/initialize`, {}, { preserveScroll: true });
}

// ── Milestone ──
function toggleMilestone(milestone, newStatus) {
    router.post(`/doctor/pediatric/patients/${props.patient.id}/milestone`, {
        milestone_definition_id: milestone.milestone_definition_id || milestone.id,
        status: newStatus,
    }, { preserveScroll: true });
}

const milestoneCategories = computed(() => {
    const cats = {
        gross_motor: { label: 'Gross Motor', labelAr: 'المهارات الحركية الكبرى', items: [] },
        fine_motor: { label: 'Fine Motor', labelAr: 'المهارات الحركية الدقيقة', items: [] },
        language: { label: 'Language', labelAr: 'اللغة', items: [] },
        social: { label: 'Social', labelAr: 'المهارات الاجتماعية', items: [] },
    };
    for (const m of (props.milestones || [])) {
        const cat = m.category || 'gross_motor';
        if (cats[cat]) cats[cat].items.push(m);
    }
    return cats;
});

// ── Allergy Form ──
const showAllergyForm = ref(false);
const allergyForm = useForm({
    type: '',
    allergen: '',
    severity: '',
    reaction: '',
    notes: '',
});

const allergyTypes = [
    { value: 'food', label: 'Food', labelAr: 'طعام' },
    { value: 'drug', label: 'Drug', labelAr: 'دواء' },
    { value: 'environmental', label: 'Environmental', labelAr: 'بيئي' },
    { value: 'other', label: 'Other', labelAr: 'أخرى' },
];

const severityOptions = [
    { value: 'mild', label: 'Mild', labelAr: 'خفيف' },
    { value: 'moderate', label: 'Moderate', labelAr: 'متوسط' },
    { value: 'severe', label: 'Severe', labelAr: 'شديد' },
    { value: 'life_threatening', label: 'Life-threatening', labelAr: 'مهدد للحياة' },
];

function submitAllergy() {
    allergyForm.post(`/doctor/pediatric/patients/${props.patient.id}/allergy`, {
        preserveScroll: true,
        onSuccess: () => {
            showAllergyForm.value = false;
            allergyForm.reset();
        },
    });
}

// ── Vaccination Grouped ──
const vaccinationGrouped = computed(() => {
    const groups = {};
    for (const v of (props.vaccinations || [])) {
        const period = v.age_period || v.recommended_age || 'Other';
        if (!groups[period]) groups[period] = [];
        groups[period].push(v);
    }
    return groups;
});

const vaccineStatusConfig = {
    given: { bg: 'bg-emerald-50 dark:bg-emerald-900/30', text: 'text-emerald-700 dark:text-emerald-400', dot: 'bg-emerald-500', label: 'Given', labelAr: 'تم إعطاؤه' },
    scheduled: { bg: 'bg-blue-50 dark:bg-blue-900/30', text: 'text-blue-700 dark:text-blue-400', dot: 'bg-blue-500', label: 'Scheduled', labelAr: 'مجدول' },
    missed: { bg: 'bg-red-50 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', dot: 'bg-red-500', label: 'Missed', labelAr: 'فائت' },
    postponed: { bg: 'bg-amber-50 dark:bg-amber-900/30', text: 'text-amber-700 dark:text-amber-400', dot: 'bg-amber-500', label: 'Postponed', labelAr: 'مؤجل' },
};

const severityBadge = {
    mild: { bg: 'bg-yellow-50 dark:bg-yellow-900/30', text: 'text-yellow-700 dark:text-yellow-400' },
    moderate: { bg: 'bg-orange-50 dark:bg-orange-900/30', text: 'text-orange-700 dark:text-orange-400' },
    severe: { bg: 'bg-red-50 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400' },
    life_threatening: { bg: 'bg-red-100 dark:bg-red-900/50', text: 'text-red-800 dark:text-red-300' },
};

const visitStatusConfig = {
    waiting: { bg: 'bg-amber-50', text: 'text-amber-700', label: 'Waiting', labelAr: 'انتظار' },
    in_progress: { bg: 'bg-blue-50', text: 'text-blue-700', label: 'In Progress', labelAr: 'قيد التنفيذ' },
    completed: { bg: 'bg-emerald-50', text: 'text-emerald-700', label: 'Completed', labelAr: 'مكتمل' },
    cancelled: { bg: 'bg-gray-50', text: 'text-gray-600', label: 'Cancelled', labelAr: 'ملغي' },
};

// ── BMI ──
const bmi = computed(() => {
    if (!latestGrowth.value?.weight_kg || !latestGrowth.value?.height_cm) return null;
    const h = latestGrowth.value.height_cm / 100;
    return (latestGrowth.value.weight_kg / (h * h)).toFixed(1);
});
</script>

<template>
    <div class="space-y-5">
        <!-- ═══════════════ PATIENT HEADER ═══════════════ -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-green-500 to-teal-500 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <!-- Decorative -->
            <div class="absolute top-0 right-0 w-80 h-80 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-56 h-56 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10 px-4 sm:px-6 py-5 sm:py-6">
                <!-- Back Link -->
                <Link href="/doctor/pediatric/patients" class="inline-flex items-center gap-1.5 text-xs text-white/80 hover:text-white transition mb-4">
                    <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'العودة للمرضى' : 'Back to Patients' }}
                </Link>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <!-- Avatar -->
                    <div v-if="patient.photo" class="w-16 h-16 rounded-2xl overflow-hidden ring-4 ring-white/20 shadow-lg flex-shrink-0">
                        <img :src="patient.photo" :alt="patient.full_name" class="w-full h-full object-cover" />
                    </div>
                    <div
                        v-else
                        class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl font-bold ring-4 ring-white/20 shadow-lg flex-shrink-0"
                        :class="patient.gender === 'male' ? 'bg-blue-500/40' : patient.gender === 'female' ? 'bg-pink-500/40' : 'bg-white/20'"
                    >
                        {{ (patient.full_name || 'P').charAt(0).toUpperCase() }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl sm:text-2xl font-bold text-white truncate">{{ patient.full_name }}</h1>
                        <div class="flex flex-wrap items-center gap-2.5 mt-1.5">
                            <span v-if="patient.file_number" class="inline-flex items-center gap-1 text-xs font-mono text-white bg-white/15 px-2 py-0.5 rounded-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" /></svg>
                                {{ patient.file_number }}
                            </span>
                            <span class="text-xs text-white/80">
                                {{ isRtl ? ageDisplay.ar : ageDisplay.en }}
                            </span>
                            <span v-if="patient.gender" class="text-xs text-white/70 capitalize">
                                <span v-if="patient.gender === 'male'">{{ isRtl ? 'ذكر' : 'Male' }}</span>
                                <span v-else>{{ isRtl ? 'أنثى' : 'Female' }}</span>
                            </span>
                            <span v-if="patient.blood_type" class="text-xs bg-red-500/30 text-white px-1.5 py-0.5 rounded font-medium">
                                {{ patient.blood_type }}
                            </span>
                        </div>
                        <div v-if="patient.guardian_name" class="mt-1.5 text-xs text-white/60">
                            <span>{{ isRtl ? 'ولي الأمر:' : 'Guardian:' }}</span>
                            <span class="font-medium text-white/80 mx-1">{{ patient.guardian_name }}</span>
                            <span v-if="patient.guardian_phone" class="text-white/50">{{ patient.guardian_phone }}</span>
                        </div>
                    </div>
                </div>

                <!-- Allergy Alert -->
                <div
                    v-if="activeAllergies.length"
                    class="mt-4 bg-red-500/20 border border-red-400/30 rounded-xl px-4 py-2.5 flex items-center gap-2"
                >
                    <svg class="w-4.5 h-4.5 text-red-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <span class="text-xs text-red-100 font-medium">
                        {{ isRtl ? 'تحذير حساسية:' : 'Allergy Alert:' }}
                        {{ activeAllergies.map(a => a.allergen).join(', ') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ═══════════════ TAB NAVIGATION ═══════════════ -->
        <div
            class="transition-all duration-500"
            :class="tabsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
        >
            <div class="overflow-x-auto scrollbar-none -mx-4 px-4 sm:-mx-6 sm:px-6">
                <div class="flex gap-1 min-w-max bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="switchTab(tab.key)"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 whitespace-nowrap"
                        :class="activeTab === tab.key
                            ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    >
                        {{ isRtl ? tab.labelAr : tab.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════════ TAB CONTENT ═══════════════ -->
        <div
            class="transition-all duration-500"
            :class="contentLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <!-- ──────── OVERVIEW TAB ──────── -->
            <div v-if="activeTab === 'overview'" class="space-y-5">
                <!-- Quick Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ isRtl ? 'العمر' : 'Age' }}</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            {{ ageMonths ? (ageMonths >= 24 ? Math.floor(ageMonths / 12) + 'y' : ageMonths + 'm') : '--' }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ isRtl ? 'الوزن' : 'Weight' }}</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            {{ latestGrowth?.weight_kg ? latestGrowth.weight_kg + ' kg' : '--' }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ isRtl ? 'الطول' : 'Height' }}</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            {{ latestGrowth?.height_cm ? latestGrowth.height_cm + ' cm' : '--' }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ isRtl ? 'محيط الرأس' : 'Head Circ.' }}</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            {{ latestGrowth?.head_circumference_cm ? latestGrowth.head_circumference_cm + ' cm' : '--' }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
                        <p class="text-xs text-gray-500 dark:text-gray-400">BMI</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ bmi || '--' }}</p>
                    </div>
                </div>

                <!-- Active Allergies -->
                <div v-if="activeAllergies.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        {{ isRtl ? 'الحساسية النشطة' : 'Active Allergies' }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="allergy in activeAllergies"
                            :key="allergy.id"
                            class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
                            :class="severityBadge[allergy.severity] || severityBadge.mild"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01" /></svg>
                            {{ allergy.allergen }}
                            <span class="opacity-60">({{ allergy.severity }})</span>
                        </span>
                    </div>
                </div>

                <!-- Upcoming Vaccinations -->
                <div v-if="upcomingVaccinations.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                        {{ isRtl ? 'التطعيمات القادمة' : 'Upcoming Vaccinations' }}
                    </h3>
                    <div class="space-y-2">
                        <div v-for="v in upcomingVaccinations" :key="v.id" class="flex items-center justify-between text-sm py-1.5 px-3 bg-blue-50/50 dark:bg-blue-900/10 rounded-lg">
                            <span class="text-gray-700 dark:text-gray-300">{{ isRtl ? (v.vaccine_name_ar || v.vaccine_name) : v.vaccine_name }}</span>
                            <span class="text-xs text-blue-600 dark:text-blue-400">{{ v.recommended_age || v.age_period }}</span>
                        </div>
                    </div>
                </div>

                <!-- Chronic Conditions -->
                <div v-if="chronicConditions?.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                        <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                        {{ isRtl ? 'الأمراض المزمنة' : 'Chronic Conditions' }}
                    </h3>
                    <div class="space-y-2">
                        <div v-for="c in chronicConditions" :key="c.id" class="text-sm text-gray-700 dark:text-gray-300 py-1.5 px-3 bg-amber-50/50 dark:bg-amber-900/10 rounded-lg">
                            {{ isRtl ? (c.name_ar || c.name) : c.name }}
                            <span v-if="c.since" class="text-xs text-gray-400 mx-1">{{ isRtl ? 'منذ' : 'since' }} {{ c.since }}</span>
                        </div>
                    </div>
                </div>

                <!-- Latest Growth -->
                <div v-if="latestGrowth" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                        {{ isRtl ? 'آخر قياسات النمو' : 'Latest Growth Measurements' }}
                        <span class="text-xs text-gray-400 font-normal mx-1">{{ latestGrowth.measurement_date }}</span>
                    </h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center p-3 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-lg">
                            <p class="text-xs text-gray-500">{{ isRtl ? 'الوزن' : 'Weight' }}</p>
                            <p class="text-lg font-bold text-emerald-600">{{ latestGrowth.weight_kg || '--' }}<span class="text-xs font-normal text-gray-400 mx-0.5">kg</span></p>
                        </div>
                        <div class="text-center p-3 bg-blue-50/50 dark:bg-blue-900/10 rounded-lg">
                            <p class="text-xs text-gray-500">{{ isRtl ? 'الطول' : 'Height' }}</p>
                            <p class="text-lg font-bold text-blue-600">{{ latestGrowth.height_cm || '--' }}<span class="text-xs font-normal text-gray-400 mx-0.5">cm</span></p>
                        </div>
                        <div class="text-center p-3 bg-purple-50/50 dark:bg-purple-900/10 rounded-lg">
                            <p class="text-xs text-gray-500">{{ isRtl ? 'محيط الرأس' : 'Head' }}</p>
                            <p class="text-lg font-bold text-purple-600">{{ latestGrowth.head_circumference_cm || '--' }}<span class="text-xs font-normal text-gray-400 mx-0.5">cm</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ──────── GROWTH TAB ──────── -->
            <div v-else-if="activeTab === 'growth'" class="space-y-5">
                <!-- Add Measurement Button -->
                <div class="flex justify-between items-center">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ isRtl ? 'سجلات النمو' : 'Growth Records' }}
                    </h3>
                    <button
                        @click="showGrowthForm = !showGrowthForm"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium rounded-xl shadow-sm shadow-emerald-500/20 transition-all duration-200 hover:shadow-md"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'إضافة قياس' : 'Add Measurement' }}
                    </button>
                </div>

                <!-- Growth Form -->
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-2 max-h-0"
                    enter-to-class="opacity-100 translate-y-0 max-h-96"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0 max-h-96"
                    leave-to-class="opacity-0 -translate-y-2 max-h-0"
                >
                    <div v-if="showGrowthForm" class="bg-white dark:bg-gray-800 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-sm p-4 sm:p-5 overflow-hidden">
                        <form @submit.prevent="submitGrowth" class="space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }}</label>
                                    <input v-model="growthForm.measurement_date" type="date" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'الوزن (كغ)' : 'Weight (kg)' }}</label>
                                    <input v-model="growthForm.weight_kg" type="number" step="0.01" :placeholder="isRtl ? 'مثال: 8.5' : 'e.g. 8.5'" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'الطول (سم)' : 'Height (cm)' }}</label>
                                    <input v-model="growthForm.height_cm" type="number" step="0.1" :placeholder="isRtl ? 'مثال: 72' : 'e.g. 72'" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'محيط الرأس (سم)' : 'Head Circ. (cm)' }}</label>
                                    <input v-model="growthForm.head_circumference_cm" type="number" step="0.1" :placeholder="isRtl ? 'مثال: 45' : 'e.g. 45'" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <input v-model="growthForm.notes" type="text" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" />
                            </div>
                            <div v-if="growthForm.errors && Object.keys(growthForm.errors).length" class="text-xs text-red-500">
                                <p v-for="(err, key) in growthForm.errors" :key="key">{{ err }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="submit" :disabled="growthForm.processing" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition shadow-sm">
                                    {{ growthForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save') }}
                                </button>
                                <button type="button" @click="showGrowthForm = false; growthForm.reset();" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>

                <!-- Growth Table -->
                <div v-if="growthRecords?.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 text-start">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 text-start">{{ isRtl ? 'الوزن' : 'Weight' }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 text-start">{{ isRtl ? 'الطول' : 'Height' }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 text-start">{{ isRtl ? 'محيط الرأس' : 'Head Circ.' }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 text-start">{{ isRtl ? 'ملاحظات' : 'Notes' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr
                                    v-for="(record, idx) in growthRecords"
                                    :key="record.id"
                                    class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                                    :class="idx === 0 ? 'bg-emerald-50/30 dark:bg-emerald-900/10' : ''"
                                >
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        {{ record.measurement_date }}
                                        <span v-if="idx === 0" class="text-[10px] text-emerald-600 font-medium mx-1">{{ isRtl ? 'الأحدث' : 'Latest' }}</span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ record.weight_kg ? record.weight_kg + ' kg' : '--' }}
                                        <span v-if="record.weight_percentile" class="inline-block w-2 h-2 rounded-full mx-1" :class="record.weight_percentile >= 15 && record.weight_percentile <= 85 ? 'bg-emerald-500' : record.weight_percentile >= 5 && record.weight_percentile <= 95 ? 'bg-amber-500' : 'bg-red-500'"></span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ record.height_cm ? record.height_cm + ' cm' : '--' }}
                                        <span v-if="record.height_percentile" class="inline-block w-2 h-2 rounded-full mx-1" :class="record.height_percentile >= 15 && record.height_percentile <= 85 ? 'bg-emerald-500' : record.height_percentile >= 5 && record.height_percentile <= 95 ? 'bg-amber-500' : 'bg-red-500'"></span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ record.head_circumference_cm ? record.head_circumference_cm + ' cm' : '--' }}
                                        <span v-if="record.hc_percentile" class="inline-block w-2 h-2 rounded-full mx-1" :class="record.hc_percentile >= 15 && record.hc_percentile <= 85 ? 'bg-emerald-500' : record.hc_percentile >= 5 && record.hc_percentile <= 95 ? 'bg-amber-500' : 'bg-red-500'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs max-w-[200px] truncate">{{ record.notes || '--' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty Growth -->
                <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-14 h-14 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ isRtl ? 'لا توجد قياسات نمو بعد' : 'No growth records yet' }}</p>
                    <button @click="showGrowthForm = true" class="mt-3 text-sm text-emerald-600 hover:text-emerald-700 font-medium transition">
                        {{ isRtl ? 'إضافة أول قياس' : 'Add first measurement' }}
                    </button>
                </div>
            </div>

            <!-- ──────── VACCINES TAB ──────── -->
            <div v-else-if="activeTab === 'vaccines'" class="space-y-5">
                <!-- Stats Bar -->
                <div v-if="vaccineStats" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ isRtl ? 'تقدم التطعيمات' : 'Vaccination Progress' }}
                        </h3>
                        <span class="text-sm font-bold text-emerald-600">
                            {{ vaccineStats.given || 0 }} / {{ vaccineStats.total || 0 }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-emerald-500 to-green-400 h-full rounded-full transition-all duration-700"
                            :style="{ width: vaccineStats.total ? ((vaccineStats.given / vaccineStats.total) * 100) + '%' : '0%' }"
                        ></div>
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 bg-emerald-500 rounded-full"></span> {{ isRtl ? 'تم إعطاؤها' : 'Given' }} ({{ vaccineStats.given || 0 }})</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> {{ isRtl ? 'مجدولة' : 'Scheduled' }} ({{ vaccineStats.scheduled || 0 }})</span>
                        <span v-if="vaccineStats.missed" class="flex items-center gap-1"><span class="w-2 h-2 bg-red-500 rounded-full"></span> {{ isRtl ? 'فائتة' : 'Missed' }} ({{ vaccineStats.missed }})</span>
                    </div>
                </div>

                <!-- Initialize Button -->
                <div v-if="!vaccinations?.length" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-14 h-14 mx-auto rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ isRtl ? 'لم يتم إعداد جدول التطعيمات بعد' : 'Vaccination schedule not initialized yet' }}</p>
                    <button
                        @click="initializeSchedule"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-xl shadow-sm shadow-blue-500/20 transition-all duration-200"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'إنشاء الجدول' : 'Initialize Schedule' }}
                    </button>
                </div>

                <!-- Grouped Vaccinations -->
                <template v-else>
                    <div v-for="(vaccines, period) in vaccinationGrouped" :key="period" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ period }}</h4>
                        </div>
                        <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            <div v-for="vaccine in vaccines" :key="vaccine.id" class="px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ isRtl ? (vaccine.vaccine_name_ar || vaccine.vaccine_name) : vaccine.vaccine_name }}
                                        </p>
                                        <p v-if="vaccine.dose" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ isRtl ? 'الجرعة:' : 'Dose:' }} {{ vaccine.dose }}
                                        </p>
                                        <p v-if="vaccine.given_date" class="text-xs text-gray-400 mt-0.5">{{ vaccine.given_date }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full"
                                            :class="(vaccineStatusConfig[vaccine.status] || vaccineStatusConfig.scheduled).bg + ' ' + (vaccineStatusConfig[vaccine.status] || vaccineStatusConfig.scheduled).text"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="(vaccineStatusConfig[vaccine.status] || vaccineStatusConfig.scheduled).dot"></span>
                                            {{ isRtl ? (vaccineStatusConfig[vaccine.status] || vaccineStatusConfig.scheduled).labelAr : (vaccineStatusConfig[vaccine.status] || vaccineStatusConfig.scheduled).label }}
                                        </span>
                                        <button
                                            @click="openVaccineEdit(vaccine)"
                                            class="p-1 text-gray-400 hover:text-emerald-600 transition"
                                            :title="isRtl ? 'تحديث' : 'Update'"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Inline Edit Form -->
                                <Transition
                                    enter-active-class="transition-all duration-200"
                                    enter-from-class="opacity-0 max-h-0"
                                    enter-to-class="opacity-100 max-h-60"
                                    leave-active-class="transition-all duration-150"
                                    leave-from-class="opacity-100 max-h-60"
                                    leave-to-class="opacity-0 max-h-0"
                                >
                                    <div v-if="editingVaccine === vaccine.id" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 overflow-hidden">
                                        <form @submit.prevent="submitVaccine" class="space-y-3">
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-0.5">{{ isRtl ? 'الحالة' : 'Status' }}</label>
                                                    <select v-model="vaccineForm.status" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500 transition">
                                                        <option value="given">{{ isRtl ? 'تم إعطاؤه' : 'Given' }}</option>
                                                        <option value="scheduled">{{ isRtl ? 'مجدول' : 'Scheduled' }}</option>
                                                        <option value="missed">{{ isRtl ? 'فائت' : 'Missed' }}</option>
                                                        <option value="postponed">{{ isRtl ? 'مؤجل' : 'Postponed' }}</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-0.5">{{ isRtl ? 'التاريخ' : 'Date' }}</label>
                                                    <input v-model="vaccineForm.given_date" type="date" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500 transition" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-0.5">{{ isRtl ? 'رقم الدفعة' : 'Batch #' }}</label>
                                                    <input v-model="vaccineForm.batch_number" type="text" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500 transition" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-0.5">{{ isRtl ? 'موقع الحقن' : 'Site' }}</label>
                                                    <input v-model="vaccineForm.site" type="text" :placeholder="isRtl ? 'مثال: الفخذ الأيسر' : 'e.g. Left thigh'" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500 transition" />
                                                </div>
                                            </div>
                                            <div v-if="vaccineForm.errors && Object.keys(vaccineForm.errors).length" class="text-xs text-red-500">
                                                <p v-for="(err, key) in vaccineForm.errors" :key="key">{{ err }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button type="submit" :disabled="vaccineForm.processing" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-xs font-medium rounded-lg transition">
                                                    {{ vaccineForm.processing ? '...' : (isRtl ? 'حفظ' : 'Save') }}
                                                </button>
                                                <button type="button" @click="editingVaccine = null" class="px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ──────── DEVELOPMENT TAB ──────── -->
            <div v-else-if="activeTab === 'development'" class="space-y-5">
                <div v-if="milestoneStats" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'تقدم التطور' : 'Developmental Progress' }}</h3>
                        <span class="text-sm font-bold text-emerald-600">{{ milestoneStats.achieved || 0 }} / {{ milestoneStats.total || 0 }}</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-700"
                            :style="{ width: milestoneStats.total ? ((milestoneStats.achieved / milestoneStats.total) * 100) + '%' : '0%' }"
                        ></div>
                    </div>
                </div>

                <!-- Category Sections -->
                <div v-for="(cat, catKey) in milestoneCategories" :key="catKey">
                    <div v-if="cat.items.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                                {{ isRtl ? cat.labelAr : cat.label }}
                            </h4>
                        </div>
                        <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            <div
                                v-for="milestone in cat.items"
                                :key="milestone.id"
                                class="px-4 py-3 flex items-center justify-between gap-3 group hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        {{ isRtl ? (milestone.name_ar || milestone.name) : milestone.name }}
                                    </p>
                                    <p v-if="milestone.expected_age_months" class="text-xs text-gray-400 mt-0.5">
                                        {{ isRtl ? 'العمر المتوقع:' : 'Expected:' }} {{ milestone.expected_age_months }} {{ isRtl ? 'شهر' : 'months' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button
                                        @click="toggleMilestone(milestone, 'achieved')"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition-all duration-200"
                                        :class="milestone.status === 'achieved'
                                            ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/30'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-400 hover:bg-emerald-100 hover:text-emerald-600'"
                                        :title="isRtl ? 'تحقق' : 'Achieved'"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <button
                                        @click="toggleMilestone(milestone, 'emerging')"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition-all duration-200"
                                        :class="milestone.status === 'emerging'
                                            ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/30'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-400 hover:bg-amber-100 hover:text-amber-600'"
                                        :title="isRtl ? 'ناشئ' : 'Emerging'"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                    <button
                                        @click="toggleMilestone(milestone, 'not_achieved')"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition-all duration-200"
                                        :class="milestone.status === 'not_achieved'
                                            ? 'bg-red-500 text-white shadow-sm shadow-red-500/30'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-400 hover:bg-red-100 hover:text-red-600'"
                                        :title="isRtl ? 'لم يتحقق' : 'Not Achieved'"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty Development -->
                <div v-if="!milestones?.length" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-14 h-14 mx-auto rounded-full bg-teal-50 dark:bg-teal-900/20 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ isRtl ? 'لا توجد مراحل تطور مسجلة' : 'No developmental milestones recorded' }}</p>
                </div>
            </div>

            <!-- ──────── ALLERGIES TAB ──────── -->
            <div v-else-if="activeTab === 'allergies'" class="space-y-5">
                <div class="flex justify-between items-center">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ isRtl ? 'الحساسية' : 'Allergies' }}
                    </h3>
                    <button
                        @click="showAllergyForm = !showAllergyForm"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium rounded-xl shadow-sm shadow-emerald-500/20 transition-all duration-200 hover:shadow-md"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'إضافة حساسية' : 'Add Allergy' }}
                    </button>
                </div>

                <!-- Allergy Form -->
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-2 max-h-0"
                    enter-to-class="opacity-100 translate-y-0 max-h-96"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0 max-h-96"
                    leave-to-class="opacity-0 -translate-y-2 max-h-0"
                >
                    <div v-if="showAllergyForm" class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-800/50 shadow-sm p-4 sm:p-5 overflow-hidden">
                        <form @submit.prevent="submitAllergy" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'النوع' : 'Type' }}</label>
                                    <SearchableSelect
                                        v-model="allergyForm.type"
                                        :options="allergyTypes.map(t => ({ value: t.value, label: isRtl ? t.labelAr : t.label }))"
                                        :placeholder="isRtl ? 'اختر النوع' : 'Select type'"
                                        accentColor="#4CAF50"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'المادة المسببة' : 'Allergen' }}</label>
                                    <input v-model="allergyForm.allergen" type="text" :placeholder="isRtl ? 'مثال: الفول السوداني' : 'e.g. Peanuts'" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'الشدة' : 'Severity' }}</label>
                                    <SearchableSelect
                                        v-model="allergyForm.severity"
                                        :options="severityOptions.map(s => ({ value: s.value, label: isRtl ? s.labelAr : s.label }))"
                                        :placeholder="isRtl ? 'اختر الشدة' : 'Select severity'"
                                        accentColor="#4CAF50"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'رد الفعل' : 'Reaction' }}</label>
                                    <input v-model="allergyForm.reaction" type="text" :placeholder="isRtl ? 'مثال: طفح جلدي' : 'e.g. Rash, hives'" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <input v-model="allergyForm.notes" type="text" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition" />
                            </div>
                            <div v-if="allergyForm.errors && Object.keys(allergyForm.errors).length" class="text-xs text-red-500">
                                <p v-for="(err, key) in allergyForm.errors" :key="key">{{ err }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="submit" :disabled="allergyForm.processing" class="px-4 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition shadow-sm">
                                    {{ allergyForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save') }}
                                </button>
                                <button type="button" @click="showAllergyForm = false; allergyForm.reset();" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>

                <!-- Allergies List -->
                <div v-if="allergies?.length" class="space-y-3">
                    <div
                        v-for="(allergy, idx) in allergies"
                        :key="allergy.id"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 hover:shadow-md transition-all duration-200"
                        :style="{ transitionDelay: `${idx * 40}ms` }"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ allergy.allergen }}</h4>
                                    <span
                                        v-if="allergy.severity"
                                        class="text-xs font-medium px-2 py-0.5 rounded-full"
                                        :class="(severityBadge[allergy.severity] || severityBadge.mild).bg + ' ' + (severityBadge[allergy.severity] || severityBadge.mild).text"
                                    >
                                        {{ allergy.severity }}
                                    </span>
                                    <span v-if="allergy.type" class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ allergy.type }}</span>
                                </div>
                                <p v-if="allergy.reaction" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ isRtl ? 'رد الفعل:' : 'Reaction:' }} {{ allergy.reaction }}
                                </p>
                                <p v-if="allergy.notes" class="text-xs text-gray-400 mt-0.5">{{ allergy.notes }}</p>
                            </div>
                            <span
                                class="flex-shrink-0 w-2 h-2 rounded-full mt-2"
                                :class="allergy.status === 'active' || !allergy.status ? 'bg-red-500' : 'bg-gray-300'"
                            ></span>
                        </div>
                    </div>
                </div>

                <!-- Empty Allergies -->
                <div v-else-if="!showAllergyForm" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-14 h-14 mx-auto rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ isRtl ? 'لا توجد حساسية مسجلة' : 'No allergies recorded' }}</p>
                    <button @click="showAllergyForm = true" class="mt-3 text-sm text-emerald-600 hover:text-emerald-700 font-medium transition">
                        {{ isRtl ? 'إضافة حساسية' : 'Add an allergy' }}
                    </button>
                </div>
            </div>

            <!-- ──────── VISITS TAB ──────── -->
            <div v-else-if="activeTab === 'visits'" class="space-y-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ isRtl ? 'سجل الزيارات' : 'Visit History' }}
                    <span v-if="visits?.length" class="text-xs font-normal text-gray-400 mx-1">({{ visits.length }})</span>
                </h3>

                <div v-if="visits?.length" class="space-y-3">
                    <Link
                        v-for="(visit, idx) in visits"
                        :key="visit.id"
                        :href="`/doctor/pediatric/visits/${visit.id}`"
                        class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 cursor-pointer"
                        :style="{ transitionDelay: `${idx * 40}ms` }"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ visit.visit_date || visit.created_at?.split('T')[0] }}
                                    </span>
                                    <span
                                        v-if="visit.status"
                                        class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full"
                                        :class="(visitStatusConfig[visit.status] || visitStatusConfig.waiting).bg + ' ' + (visitStatusConfig[visit.status] || visitStatusConfig.waiting).text"
                                    >
                                        {{ isRtl ? (visitStatusConfig[visit.status] || visitStatusConfig.waiting).labelAr : (visitStatusConfig[visit.status] || visitStatusConfig.waiting).label }}
                                    </span>
                                </div>
                                <p v-if="visit.chief_complaint || visit.diagnosis" class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">
                                    {{ visit.chief_complaint || visit.diagnosis }}
                                </p>
                                <p v-if="visit.doctor_name" class="text-xs text-gray-400 mt-0.5">
                                    {{ isRtl ? 'الطبيب:' : 'Dr.' }} {{ visit.doctor_name }}
                                </p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </Link>
                </div>

                <!-- Empty Visits -->
                <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-14 h-14 mx-auto rounded-full bg-gray-50 dark:bg-gray-700 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ isRtl ? 'لا توجد زيارات مسجلة' : 'No visits recorded yet' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar { display: none; }
.scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
</style>
