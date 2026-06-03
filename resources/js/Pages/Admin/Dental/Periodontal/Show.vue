<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    records: [Object, Array],
    examDate: String,
    examDates: Array,
    allTeeth: Array,
    doctors: Array,
});

const selectedExamDate = ref(props.examDate || '');
const selectedDoctor = ref('');
const saving = ref(false);
const activeQuadrant = ref('all');
const showDoctorDropdown = ref(false);
const doctorSearch = ref('');

// Stats
const filledTeethCount = computed(() => {
    let count = 0;
    Object.values(measurements.value).forEach(m => {
        const hasData = m.probing_depths.some(v => v !== '' && v !== null) ||
            m.recession.some(v => v !== '' && v !== null) ||
            m.mobility !== '' || m.furcation !== '';
        if (hasData) count++;
    });
    return count;
});

const deepPocketsCount = computed(() => {
    let count = 0;
    Object.values(measurements.value).forEach(m => {
        m.probing_depths.forEach(v => {
            if (parseInt(v) >= 5) count++;
        });
    });
    return count;
});

const bleedingSitesCount = computed(() => {
    let count = 0;
    Object.values(measurements.value).forEach(m => {
        m.bleeding_on_probing.forEach(v => {
            if (v === true || v === 1 || v === '1') count++;
        });
    });
    return count;
});

// Doctor search
const filteredDoctors = computed(() => {
    if (!doctorSearch.value) return props.doctors || [];
    const s = doctorSearch.value.toLowerCase();
    return (props.doctors || []).filter(d =>
        d.name_ar?.toLowerCase().includes(s) || d.name_en?.toLowerCase().includes(s)
    );
});

const selectedDoctorName = computed(() => {
    if (!selectedDoctor.value) return '';
    const d = (props.doctors || []).find(d => d.id === selectedDoctor.value);
    return d ? (locale.value === 'ar' ? d.name_ar : d.name_en) : '';
});

function selectDoctor(d) {
    selectedDoctor.value = d.id;
    doctorSearch.value = '';
    showDoctorDropdown.value = false;
}

function closeDropdowns(e) {
    if (!e.target.closest('.searchable-select')) {
        showDoctorDropdown.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', closeDropdowns);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeDropdowns);
});

// Build measurement data: { toothNumber: { probing_depths: [6 sites], recession: [6], bleeding_on_probing: [6], plaque_index: [6], mobility, furcation, notes } }
const measurements = ref({});

function initMeasurements() {
    const data = {};
    const recs = props.records || {};
    (props.allTeeth || []).forEach(tooth => {
        const existing = recs[tooth] || null;
        data[tooth] = {
            probing_depths: existing?.probing_depths || ['', '', '', '', '', ''],
            recession: existing?.recession || ['', '', '', '', '', ''],
            bleeding_on_probing: existing?.bleeding_on_probing || [false, false, false, false, false, false],
            plaque_index: existing?.plaque_index || [false, false, false, false, false, false],
            mobility: existing?.mobility ?? '',
            furcation: existing?.furcation ?? '',
            notes: existing?.notes || '',
        };
    });
    measurements.value = data;
}
initMeasurements();

function loadExam(date) {
    router.get(`/admin/dental/periodontal/${props.patient.id}`, { exam_date: date }, { preserveState: false });
}

function depthColor(val) {
    const num = parseInt(val);
    if (!num || isNaN(num)) return '';
    if (num >= 7) return 'pd-severe';
    if (num >= 5) return 'pd-danger';
    if (num >= 4) return 'pd-warning';
    if (num >= 1) return 'pd-normal';
    return '';
}

const saveError = ref('');

function saveMeasurements() {
    saveError.value = '';
    if (!selectedDoctor.value) {
        saveError.value = isRtl.value ? 'يرجى اختيار الطبيب أولاً' : 'Please select a doctor first';
        return;
    }
    saving.value = true;
    const payload = {
        exam_date: selectedExamDate.value || new Date().toISOString().slice(0, 10),
        doctor_id: selectedDoctor.value,
        measurements: Object.entries(measurements.value).map(([tooth, data]) => ({
            tooth_number: parseInt(tooth),
            probing_depths: data.probing_depths,
            recession: data.recession,
            bleeding_on_probing: data.bleeding_on_probing,
            plaque_index: data.plaque_index,
            mobility: data.mobility || null,
            furcation: data.furcation || null,
            notes: data.notes || null,
        })),
    };
    router.post(`/admin/dental/periodontal/${props.patient.id}`, payload, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
        onError: (errors) => {
            saveError.value = Object.values(errors).flat().join(', ');
        },
    });
}

// Quadrant groupings
const upperRight = computed(() => (props.allTeeth || []).filter(t => t >= 11 && t <= 18).sort((a, b) => b - a));
const upperLeft = computed(() => (props.allTeeth || []).filter(t => t >= 21 && t <= 28).sort((a, b) => a - b));
const lowerLeft = computed(() => (props.allTeeth || []).filter(t => t >= 31 && t <= 38).sort((a, b) => a - b));
const lowerRight = computed(() => (props.allTeeth || []).filter(t => t >= 41 && t <= 48).sort((a, b) => b - a));

const upperTeeth = computed(() => [...upperRight.value, ...upperLeft.value]);
const lowerTeeth = computed(() => [...lowerLeft.value, ...lowerRight.value]);

const siteLabels = ['MB', 'B', 'DB', 'ML', 'L', 'DL'];

// Toggle bleeding
function toggleBleeding(tooth, site) {
    measurements.value[tooth].bleeding_on_probing[site] = !measurements.value[tooth].bleeding_on_probing[site];
}

// Toggle plaque
function togglePlaque(tooth, site) {
    measurements.value[tooth].plaque_index[site] = !measurements.value[tooth].plaque_index[site];
}

// Expanded tooth detail
const expandedTooth = ref(null);
function toggleToothDetail(tooth) {
    expandedTooth.value = expandedTooth.value === tooth ? null : tooth;
}
</script>

<template>
    <AdminLayout :title="$t('a_periodontal_chart')">
        <div class="max-w-full mx-auto pb-16 space-y-8">

            <!-- ═══ HERO ═══ -->
            <div class="perio-animate perio-animate-1 relative overflow-hidden rounded-3xl shadow-2xl">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-[#6B5633] to-[#6B5633]"></div>
                <div class="perio-hero-pattern absolute inset-0 opacity-[0.04]"></div>
                <div class="perio-glow absolute -top-24 -end-24 w-96 h-96 bg-[#D4B57E]/20 rounded-full blur-3xl"></div>
                <div class="perio-glow-2 absolute -bottom-20 -start-20 w-80 h-80 bg-[#D4B57E]/15 rounded-full blur-3xl"></div>

                <div class="relative px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="flex items-center gap-5">
                            <div class="perio-icon-float w-16 h-16 rounded-2xl bg-gradient-to-br from-[#D4B57E] to-[#C4A265] flex items-center justify-center shadow-lg shadow-[#C4A265]/30">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-black text-white tracking-tight">{{ isRtl ? 'مخطط اللثة' : 'Periodontal Chart' }}</h1>
                                <p class="text-[#F5E7C8]/70 text-sm mt-1">{{ patient.full_name }} <span class="text-[#E6CF9A]/50 mx-1">|</span> {{ patient.file_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link :href="`/admin/dental/chart/${patient.id}`" class="group inline-flex items-center gap-2 px-5 py-3 rounded-2xl text-sm font-semibold bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/10 text-white transition-all duration-300 hover:scale-[1.02]">
                                <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                                {{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}
                            </Link>
                            <Link :href="`/admin/patients/${patient.id}`" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl text-sm font-medium text-white/60 hover:text-white hover:bg-white/10 transition-all duration-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                {{ isRtl ? 'ملف المريض' : 'Patient File' }}
                            </Link>
                        </div>
                    </div>

                    <!-- Stats Mini Cards -->
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-4 py-3 border border-white/10">
                            <div class="text-xs text-[#F5E7C8]/60 mb-1">{{ isRtl ? 'أسنان مفحوصة' : 'Teeth Examined' }}</div>
                            <div class="text-2xl font-black text-white tabular-nums">{{ filledTeethCount }} <span class="text-sm font-normal text-white/40">/ 32</span></div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-4 py-3 border border-white/10">
                            <div class="text-xs text-[#F5E7C8]/60 mb-1">{{ isRtl ? 'جيوب عميقة' : 'Deep Pockets' }}</div>
                            <div class="text-2xl font-black tabular-nums" :class="deepPocketsCount > 0 ? 'text-[#E6CF9A]' : 'text-emerald-300'">{{ deepPocketsCount }}</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-4 py-3 border border-white/10">
                            <div class="text-xs text-[#F5E7C8]/60 mb-1">{{ isRtl ? 'مواقع نزيف' : 'Bleeding Sites' }}</div>
                            <div class="text-2xl font-black tabular-nums" :class="bleedingSitesCount > 0 ? 'text-amber-300' : 'text-emerald-300'">{{ bleedingSitesCount }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ CONTROLS ═══ -->
            <div class="perio-animate perio-animate-2 perio-glass-card rounded-3xl p-6 border border-gray-100/80">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Exam Date -->
                    <div>
                        <label class="perio-label">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                            {{ isRtl ? 'تاريخ الفحص' : 'Exam Date' }}
                        </label>
                        <div class="perio-input-wrapper">
                            <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                            <select v-model="selectedExamDate" @change="loadExam(selectedExamDate)" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm text-gray-700 p-0 focus:ring-0 cursor-pointer appearance-none">
                                <option value="">{{ isRtl ? 'فحص جديد' : 'New Exam' }}</option>
                                <option v-for="date in examDates" :key="date" :value="date">{{ date }}</option>
                            </select>
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                        </div>
                    </div>

                    <!-- Doctor - Searchable -->
                    <div class="searchable-select">
                        <label class="perio-label">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                            {{ isRtl ? 'الطبيب' : 'Doctor' }}
                        </label>
                        <div class="relative">
                            <div @click="showDoctorDropdown = !showDoctorDropdown" class="perio-input-wrapper cursor-pointer" :class="{ 'perio-input-focus': showDoctorDropdown }">
                                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                <input v-if="showDoctorDropdown" v-model="doctorSearch" type="text" :placeholder="isRtl ? 'ابحث عن طبيب...' : 'Search doctor...'" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm text-gray-700 placeholder-gray-300 p-0 focus:ring-0" @click.stop />
                                <span v-else class="flex-1 text-sm" :class="selectedDoctorName ? 'text-gray-800 font-medium' : 'text-gray-300'">{{ selectedDoctorName || (isRtl ? 'اختر الطبيب' : 'Select doctor') }}</span>
                                <svg class="w-4 h-4 text-gray-300 transition-transform duration-300" :class="showDoctorDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </div>
                            <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2 scale-[0.97]" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
                                <div v-if="showDoctorDropdown" class="absolute z-50 mt-2 w-full bg-white rounded-2xl border border-gray-100 shadow-2xl shadow-gray-200/50 max-h-56 overflow-y-auto">
                                    <div v-if="filteredDoctors.length === 0" class="p-4 text-center text-sm text-gray-300">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                    <button v-for="d in filteredDoctors" :key="d.id" type="button" @click="selectDoctor(d)" class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-[#F5E7C8]/40 transition-all duration-200 text-start" :class="selectedDoctor === d.id ? 'bg-[#F5E7C8]/40 text-[#8B7043]' : 'text-gray-700'">
                                        <div class="w-9 h-9 rounded-xl bg-[#F5E7C8]/40 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                        </div>
                                        <div class="font-medium">{{ locale === 'ar' ? d.name_ar : d.name_en }}</div>
                                        <svg v-if="selectedDoctor === d.id" class="w-5 h-5 text-[#C4A265] ms-auto flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="flex flex-col items-stretch justify-end gap-2">
                        <button @click="saveMeasurements" :disabled="saving" class="group relative w-full inline-flex items-center justify-center gap-3 px-6 py-3.5 rounded-2xl text-white font-bold text-sm overflow-hidden transition-all duration-300 disabled:opacity-50 active:scale-[0.97] hover:scale-[1.01] shadow-xl shadow-[#C4A265]/20 hover:shadow-2xl hover:shadow-[#C4A265]/25">
                            <div class="absolute inset-0 bg-gradient-to-r from-[#C4A265] via-[#C4A265] to-[#C4A265] bg-[length:200%_100%] perio-shimmer"></div>
                            <svg v-if="!saving" class="w-5 h-5 relative" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else class="w-5 h-5 relative animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                            <span class="relative">{{ saving ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'حفظ القياسات' : 'Save Measurements') }}</span>
                        </button>
                        <p v-if="saveError" class="text-xs text-[#C4A265] font-medium text-center">{{ saveError }}</p>
                    </div>
                </div>
            </div>

            <!-- ═══ LEGEND ═══ -->
            <div class="perio-animate perio-animate-3 flex flex-wrap items-center gap-3 px-2">
                <span class="text-xs font-semibold text-gray-400 me-1">{{ isRtl ? 'دليل الألوان:' : 'Legend:' }}</span>
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                    <span class="w-5 h-5 rounded-lg bg-emerald-50 border-2 border-emerald-200 flex items-center justify-center text-[9px] font-bold text-emerald-600">1-3</span>
                    {{ isRtl ? 'طبيعي' : 'Normal' }}
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                    <span class="w-5 h-5 rounded-lg bg-amber-50 border-2 border-amber-200 flex items-center justify-center text-[9px] font-bold text-amber-600">4</span>
                    {{ isRtl ? 'مراقبة' : 'Monitor' }}
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                    <span class="w-5 h-5 rounded-lg bg-red-50 border-2 border-red-200 flex items-center justify-center text-[9px] font-bold text-red-600">5-6</span>
                    {{ isRtl ? 'خطر' : 'Danger' }}
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                    <span class="w-5 h-5 rounded-lg bg-[#F5E7C8]/60 border-2 border-[#E6CF9A] flex items-center justify-center text-[9px] font-bold text-[#8B7043]">7+</span>
                    {{ isRtl ? 'شديد' : 'Severe' }}
                </span>
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 ms-2">
                    <span class="w-5 h-5 rounded-lg bg-red-500 flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" /></svg>
                    </span>
                    {{ isRtl ? 'نزيف' : 'Bleeding' }}
                </span>
            </div>

            <!-- ═══ UPPER TEETH ═══ -->
            <div class="perio-animate perio-animate-3 perio-glass-card rounded-3xl overflow-hidden border border-gray-100/80">
                <div class="px-8 py-5 bg-gradient-to-r from-slate-50 via-gray-50/50 to-white border-b border-gray-100/50">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#1B365D] to-[#2C4E7A] flex items-center justify-center shadow-lg shadow-[#1B365D]/15">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l7.5-7.5 7.5 7.5m-15 6l7.5-7.5 7.5 7.5" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ isRtl ? 'الفك العلوي' : 'Upper Jaw (Maxilla)' }}</h2>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'الأسنان من 18 إلى 28' : 'Teeth 18 to 28' }}</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto p-6">
                    <table class="w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-start text-gray-400 font-semibold w-28"></th>
                                <th v-for="tooth in upperTeeth" :key="'uh-' + tooth" class="px-0.5 py-2 text-center">
                                    <button @click="toggleToothDetail(tooth)" class="w-full px-1 py-1 rounded-lg font-bold transition-all duration-200" :class="expandedTooth === tooth ? 'bg-slate-100 text-[#1B365D]' : 'text-gray-700 hover:bg-gray-50'">
                                        {{ tooth }}
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Buccal Probing Depths -->
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#2C4E7A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l7.5-7.5 7.5 7.5" /></svg>
                                        {{ isRtl ? 'دهليزي' : 'Buccal' }}
                                    </div>
                                    <div class="text-[9px] text-gray-300 mt-0.5">MB / B / DB</div>
                                </td>
                                <td v-for="tooth in upperTeeth" :key="'ub-' + tooth" class="px-0 py-1 border-e border-gray-50">
                                    <div class="flex">
                                        <input v-for="i in 3" :key="i" v-model="measurements[tooth].probing_depths[i-1]" type="number" min="0" max="15" class="doctorato-input perio-cell" :class="depthColor(measurements[tooth].probing_depths[i-1])" />
                                    </div>
                                </td>
                            </tr>
                            <!-- Buccal Bleeding -->
                            <tr>
                                <td class="px-3 py-1 text-gray-400 font-medium border-e-2 border-gray-100 text-[10px]">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" /></svg>
                                        {{ isRtl ? 'نزيف (د)' : 'BOP (B)' }}
                                    </div>
                                </td>
                                <td v-for="tooth in upperTeeth" :key="'ubop-' + tooth" class="px-0 py-0.5 border-e border-gray-50">
                                    <div class="flex">
                                        <button v-for="i in 3" :key="i" @click="toggleBleeding(tooth, i-1)" type="button" class="perio-bop-btn" :class="measurements[tooth].bleeding_on_probing[i-1] ? 'bg-red-500 border-red-500' : 'bg-gray-50 border-gray-200 hover:border-red-300'" :aria-label="isRtl ? 'نزيف' : 'Bleeding'" :title="isRtl ? 'نزيف' : 'Bleeding'">
                                            <svg v-if="measurements[tooth].bleeding_on_probing[i-1]" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Separator -->
                            <tr><td :colspan="upperTeeth.length + 1" class="py-1"><div class="border-t-2 border-dashed border-gray-100"></div></td></tr>
                            <!-- Lingual Probing Depths -->
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#2C4E7A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5" /></svg>
                                        {{ isRtl ? 'لساني' : 'Palatal' }}
                                    </div>
                                    <div class="text-[9px] text-gray-300 mt-0.5">ML / L / DL</div>
                                </td>
                                <td v-for="tooth in upperTeeth" :key="'ul-' + tooth" class="px-0 py-1 border-e border-gray-50">
                                    <div class="flex">
                                        <input v-for="i in 3" :key="i" v-model="measurements[tooth].probing_depths[i+2]" type="number" min="0" max="15" class="doctorato-input perio-cell" :class="depthColor(measurements[tooth].probing_depths[i+2])" />
                                    </div>
                                </td>
                            </tr>
                            <!-- Lingual Bleeding -->
                            <tr>
                                <td class="px-3 py-1 text-gray-400 font-medium border-e-2 border-gray-100 text-[10px]">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" /></svg>
                                        {{ isRtl ? 'نزيف (ل)' : 'BOP (P)' }}
                                    </div>
                                </td>
                                <td v-for="tooth in upperTeeth" :key="'ulbop-' + tooth" class="px-0 py-0.5 border-e border-gray-50">
                                    <div class="flex">
                                        <button v-for="i in 3" :key="i" @click="toggleBleeding(tooth, i+2)" type="button" class="perio-bop-btn" :class="measurements[tooth].bleeding_on_probing[i+2] ? 'bg-red-500 border-red-500' : 'bg-gray-50 border-gray-200 hover:border-red-300'" :aria-label="isRtl ? 'نزيف' : 'Bleeding'" :title="isRtl ? 'نزيف' : 'Bleeding'">
                                            <svg v-if="measurements[tooth].bleeding_on_probing[i+2]" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Recession Buccal -->
                            <tr><td :colspan="upperTeeth.length + 1" class="py-1"><div class="border-t-2 border-dashed border-gray-100"></div></td></tr>
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#2C4E7A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5-7.5L16.5 3m0 0L12 7.5m4.5-4.5v13.5" /></svg>
                                        {{ isRtl ? 'انحسار' : 'Recession' }}
                                    </div>
                                    <div class="text-[9px] text-gray-300 mt-0.5">MB / B / DB</div>
                                </td>
                                <td v-for="tooth in upperTeeth" :key="'ur-' + tooth" class="px-0 py-1 border-e border-gray-50">
                                    <div class="flex">
                                        <input v-for="i in 3" :key="i" v-model="measurements[tooth].recession[i-1]" type="number" min="0" max="15" class="doctorato-input perio-cell perio-cell-recession" />
                                    </div>
                                </td>
                            </tr>
                            <!-- Mobility & Furcation -->
                            <tr><td :colspan="upperTeeth.length + 1" class="py-1"><div class="border-t-2 border-dashed border-gray-100"></div></td></tr>
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#D4B57E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                        {{ isRtl ? 'حركة' : 'Mobility' }}
                                    </div>
                                    <div class="text-[9px] text-gray-300 mt-0.5">0-3</div>
                                </td>
                                <td v-for="tooth in upperTeeth" :key="'um-' + tooth" class="px-0.5 py-1 border-e border-gray-50">
                                    <input v-model="measurements[tooth].mobility" type="number" min="0" max="3" class="doctorato-input perio-cell-single" />
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546" /></svg>
                                        {{ isRtl ? 'تفرع' : 'Furcation' }}
                                    </div>
                                    <div class="text-[9px] text-gray-300 mt-0.5">0-3</div>
                                </td>
                                <td v-for="tooth in upperTeeth" :key="'uf-' + tooth" class="px-0.5 py-1 border-e border-gray-50">
                                    <input v-model="measurements[tooth].furcation" type="number" min="0" max="3" class="doctorato-input perio-cell-single" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══ LOWER TEETH ═══ -->
            <div class="perio-animate perio-animate-4 perio-glass-card rounded-3xl overflow-hidden border border-gray-100/80">
                <div class="px-8 py-5 bg-gradient-to-r from-slate-50 via-gray-50/50 to-white border-b border-gray-100/50">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/15">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ isRtl ? 'الفك السفلي' : 'Lower Jaw (Mandible)' }}</h2>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'الأسنان من 48 إلى 38' : 'Teeth 48 to 38' }}</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto p-6">
                    <table class="w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-start text-gray-400 font-semibold w-28"></th>
                                <th v-for="tooth in lowerTeeth" :key="'lh-' + tooth" class="px-0.5 py-2 text-center">
                                    <button @click="toggleToothDetail(tooth)" class="w-full px-1 py-1 rounded-lg font-bold transition-all duration-200" :class="expandedTooth === tooth ? 'bg-emerald-100 text-emerald-700' : 'text-gray-700 hover:bg-gray-50'">
                                        {{ tooth }}
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Buccal Probing Depths -->
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l7.5-7.5 7.5 7.5" /></svg>
                                        {{ isRtl ? 'دهليزي' : 'Buccal' }}
                                    </div>
                                    <div class="text-[9px] text-gray-300 mt-0.5">MB / B / DB</div>
                                </td>
                                <td v-for="tooth in lowerTeeth" :key="'lb-' + tooth" class="px-0 py-1 border-e border-gray-50">
                                    <div class="flex">
                                        <input v-for="i in 3" :key="i" v-model="measurements[tooth].probing_depths[i-1]" type="number" min="0" max="15" class="doctorato-input perio-cell" :class="depthColor(measurements[tooth].probing_depths[i-1])" />
                                    </div>
                                </td>
                            </tr>
                            <!-- Buccal Bleeding -->
                            <tr>
                                <td class="px-3 py-1 text-gray-400 font-medium border-e-2 border-gray-100 text-[10px]">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" /></svg>
                                        {{ isRtl ? 'نزيف (د)' : 'BOP (B)' }}
                                    </div>
                                </td>
                                <td v-for="tooth in lowerTeeth" :key="'lbop-' + tooth" class="px-0 py-0.5 border-e border-gray-50">
                                    <div class="flex">
                                        <button v-for="i in 3" :key="i" @click="toggleBleeding(tooth, i-1)" type="button" class="perio-bop-btn" :class="measurements[tooth].bleeding_on_probing[i-1] ? 'bg-red-500 border-red-500' : 'bg-gray-50 border-gray-200 hover:border-red-300'" :aria-label="isRtl ? 'نزيف' : 'Bleeding'" :title="isRtl ? 'نزيف' : 'Bleeding'">
                                            <svg v-if="measurements[tooth].bleeding_on_probing[i-1]" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr><td :colspan="lowerTeeth.length + 1" class="py-1"><div class="border-t-2 border-dashed border-gray-100"></div></td></tr>
                            <!-- Lingual Probing Depths -->
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5" /></svg>
                                        {{ isRtl ? 'لساني' : 'Lingual' }}
                                    </div>
                                    <div class="text-[9px] text-gray-300 mt-0.5">ML / L / DL</div>
                                </td>
                                <td v-for="tooth in lowerTeeth" :key="'ll-' + tooth" class="px-0 py-1 border-e border-gray-50">
                                    <div class="flex">
                                        <input v-for="i in 3" :key="i" v-model="measurements[tooth].probing_depths[i+2]" type="number" min="0" max="15" class="doctorato-input perio-cell" :class="depthColor(measurements[tooth].probing_depths[i+2])" />
                                    </div>
                                </td>
                            </tr>
                            <!-- Lingual Bleeding -->
                            <tr>
                                <td class="px-3 py-1 text-gray-400 font-medium border-e-2 border-gray-100 text-[10px]">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" /></svg>
                                        {{ isRtl ? 'نزيف (ل)' : 'BOP (L)' }}
                                    </div>
                                </td>
                                <td v-for="tooth in lowerTeeth" :key="'llbop-' + tooth" class="px-0 py-0.5 border-e border-gray-50">
                                    <div class="flex">
                                        <button v-for="i in 3" :key="i" @click="toggleBleeding(tooth, i+2)" type="button" class="perio-bop-btn" :class="measurements[tooth].bleeding_on_probing[i+2] ? 'bg-red-500 border-red-500' : 'bg-gray-50 border-gray-200 hover:border-red-300'" :aria-label="isRtl ? 'نزيف' : 'Bleeding'" :title="isRtl ? 'نزيف' : 'Bleeding'">
                                            <svg v-if="measurements[tooth].bleeding_on_probing[i+2]" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Recession -->
                            <tr><td :colspan="lowerTeeth.length + 1" class="py-1"><div class="border-t-2 border-dashed border-gray-100"></div></td></tr>
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#2C4E7A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5-7.5L16.5 3m0 0L12 7.5m4.5-4.5v13.5" /></svg>
                                        {{ isRtl ? 'انحسار' : 'Recession' }}
                                    </div>
                                    <div class="text-[9px] text-gray-300 mt-0.5">MB / B / DB</div>
                                </td>
                                <td v-for="tooth in lowerTeeth" :key="'lr-' + tooth" class="px-0 py-1 border-e border-gray-50">
                                    <div class="flex">
                                        <input v-for="i in 3" :key="i" v-model="measurements[tooth].recession[i-1]" type="number" min="0" max="15" class="doctorato-input perio-cell perio-cell-recession" />
                                    </div>
                                </td>
                            </tr>
                            <!-- Mobility & Furcation -->
                            <tr><td :colspan="lowerTeeth.length + 1" class="py-1"><div class="border-t-2 border-dashed border-gray-100"></div></td></tr>
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#D4B57E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                        {{ isRtl ? 'حركة' : 'Mobility' }}
                                    </div>
                                </td>
                                <td v-for="tooth in lowerTeeth" :key="'lm-' + tooth" class="px-0.5 py-1 border-e border-gray-50">
                                    <input v-model="measurements[tooth].mobility" type="number" min="0" max="3" class="doctorato-input perio-cell-single" />
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-1.5 text-gray-500 font-semibold border-e-2 border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546" /></svg>
                                        {{ isRtl ? 'تفرع' : 'Furcation' }}
                                    </div>
                                </td>
                                <td v-for="tooth in lowerTeeth" :key="'lf-' + tooth" class="px-0.5 py-1 border-e border-gray-50">
                                    <input v-model="measurements[tooth].furcation" type="number" min="0" max="3" class="doctorato-input perio-cell-single" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══ BOTTOM SAVE ═══ -->
            <div class="perio-animate perio-animate-5 flex items-center justify-end gap-4">
                <Link :href="`/admin/patients/${patient.id}`" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl bg-gray-100 text-gray-500 text-sm font-semibold hover:bg-gray-200 hover:text-gray-700 transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                </Link>
                <div class="flex flex-col items-end gap-2">
                    <button @click="saveMeasurements" :disabled="saving" class="group relative inline-flex items-center gap-3 px-10 py-3.5 rounded-2xl text-white font-bold text-sm overflow-hidden transition-all duration-300 disabled:opacity-50 active:scale-[0.97] hover:scale-[1.01] shadow-xl shadow-[#C4A265]/20">
                        <div class="absolute inset-0 bg-gradient-to-r from-[#C4A265] via-[#C4A265] to-[#C4A265] bg-[length:200%_100%] perio-shimmer"></div>
                        <svg v-if="!saving" class="w-5 h-5 relative" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else class="w-5 h-5 relative animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                        <span class="relative">{{ saving ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'حفظ القياسات' : 'Save Measurements') }}</span>
                    </button>
                    <p v-if="saveError" class="text-xs text-[#C4A265] font-medium">{{ saveError }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ─── Staggered Animations ──────────────────────── */
.perio-animate {
    opacity: 0;
    transform: translateY(24px);
    animation: perioReveal 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.perio-animate-1 { animation-delay: 0s; }
.perio-animate-2 { animation-delay: 0.1s; }
.perio-animate-3 { animation-delay: 0.18s; }
.perio-animate-4 { animation-delay: 0.26s; }
.perio-animate-5 { animation-delay: 0.34s; }

@keyframes perioReveal {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ─── Hero Effects ──────────────────────────────── */
.perio-hero-pattern {
    background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0);
    background-size: 24px 24px;
}
.perio-glow {
    animation: perioPulse 6s ease-in-out infinite alternate;
}
.perio-glow-2 {
    animation: perioPulse 8s ease-in-out infinite alternate-reverse;
}
@keyframes perioPulse {
    from { opacity: 0.15; transform: scale(1); }
    to { opacity: 0.3; transform: scale(1.15); }
}
.perio-icon-float {
    animation: perioFloat 4s ease-in-out infinite;
}
@keyframes perioFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}
.perio-shimmer {
    animation: perioShimmer 3s linear infinite;
}
@keyframes perioShimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

/* ─── Glass Card ────────────────────────────────── */
.perio-glass-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 32px rgba(0,0,0,0.04);
}

/* ─── Labels ────────────────────────────────────── */
.perio-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.625rem;
}

/* ─── Inputs ────────────────────────────────────── */
.perio-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.125rem;
    border: 2px solid #f3f4f6;
    border-radius: 1rem;
    font-size: 0.875rem;
    background-color: rgba(249, 250, 251, 0.5);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.perio-input-wrapper:hover { border-color: #e5e7eb; }
.perio-input-focus {
    border-color: #fda4af !important;
    box-shadow: 0 0 0 4px rgba(253, 164, 175, 0.1) !important;
    background-color: #fff !important;
}

/* ─── Probing Depth Cells ───────────────────────── */
.perio-cell {
    width: 100%;
    text-align: center;
    border: none;
    border-inline-end: 1px solid #f3f4f6;
    padding: 6px 0;
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    background: transparent;
    outline: none;
    transition: all 0.2s;
    -moz-appearance: textfield;
}
.perio-cell::-webkit-inner-spin-button,
.perio-cell::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.perio-cell:last-child { border-inline-end: none; }
.perio-cell:focus {
    background: #ecfdf5;
    color: #059669;
    box-shadow: inset 0 0 0 2px #F5E7C8;
    border-radius: 4px;
}

.perio-cell-recession {
    color: #1B365D;
}
.perio-cell-recession:focus {
    background: #f5f3ff;
    color: #0F2444;
    box-shadow: inset 0 0 0 2px #c4b5fd;
}

.perio-cell-single {
    width: 100%;
    text-align: center;
    border: 1px solid #f3f4f6;
    border-radius: 8px;
    padding: 5px 0;
    font-size: 11px;
    font-weight: 700;
    color: #374151;
    background: #fafafa;
    outline: none;
    transition: all 0.2s;
    -moz-appearance: textfield;
}
.perio-cell-single::-webkit-inner-spin-button,
.perio-cell-single::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.perio-cell-single:focus {
    background: #fff;
    border-color: #fda4af;
    box-shadow: 0 0 0 3px rgba(253, 164, 175, 0.1);
}

/* ─── Depth Color Coding ────────────────────────── */
.pd-normal { background: #ecfdf5; color: #059669; }
.pd-warning { background: #fffbeb; color: #d97706; font-weight: 700; }
.pd-danger { background: #fef2f2; color: #dc2626; font-weight: 800; }
.pd-severe { background: #fff1f2; color: #9f1239; font-weight: 900; animation: pdPulse 2s ease-in-out infinite; }

@keyframes pdPulse {
    0%, 100% { background: #fff1f2; }
    50% { background: #ffe4e6; }
}

/* ─── BOP Toggle Buttons ────────────────────────── */
.perio-bop-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 18px;
    border: 1.5px solid;
    border-radius: 4px;
    margin: 0 1px;
    transition: all 0.2s;
    cursor: pointer;
}
.perio-bop-btn:hover {
    transform: scale(1.1);
}
</style>
