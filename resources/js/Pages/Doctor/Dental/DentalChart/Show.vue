<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import ConfirmModal from '@/Components/Doctor/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { t } = useLocale();

const props = defineProps({
    patient: Object,
    chart: Object,
    treatments: Object,
    conditions: Array,
    surfaces: Array,
    allTeeth: Array,
    deciduousTeeth: Array,
    isChild: Boolean,
    treatmentTypes: { type: Array, default: () => [] },
});

const selectedTooth = ref(null);
const editForm = ref({ condition: 'healthy', surfaces: [], notes: '', status: 'present' });
const showModal = ref(false);
const mounted = ref(false);
const hoveredTooth = ref(null);
const saving = ref(false);
const activeTab = ref('overview'); // overview | periodontal
const showDeciduousTeeth = ref(props.isChild || false);

// ─── Quick Add Treatment ─────────────────────────────────
const showQuickTreatment = ref(false);
const savingTreatment = ref(false);
const quickTreatmentForm = ref({
    treatment_type: '',
    surfaces: [],
    description: '',
    status: 'planned',
    cost: '',
});

function resetQuickTreatment() {
    quickTreatmentForm.value = { treatment_type: '', surfaces: [], description: '', status: 'planned', cost: '' };
    showQuickTreatment.value = false;
}

function toggleQuickTreatmentSurface(surface) {
    const idx = quickTreatmentForm.value.surfaces.indexOf(surface);
    if (idx > -1) quickTreatmentForm.value.surfaces.splice(idx, 1);
    else quickTreatmentForm.value.surfaces.push(surface);
}

function saveQuickTreatment() {
    if (!quickTreatmentForm.value.treatment_type || !selectedTooth.value) return;
    savingTreatment.value = true;
    router.post('/doctor/dental/treatments', {
        patient_id: props.patient.id,
        tooth_number: selectedTooth.value,
        treatment_type: quickTreatmentForm.value.treatment_type,
        surfaces: quickTreatmentForm.value.surfaces.length ? quickTreatmentForm.value.surfaces : null,
        description: quickTreatmentForm.value.description || null,
        status: quickTreatmentForm.value.status,
        cost: quickTreatmentForm.value.cost || null,
    }, {
        preserveScroll: true,
        onSuccess: () => { resetQuickTreatment(); },
        onFinish: () => { savingTreatment.value = false; },
    });
}

// Treatment type labels
const treatmentTypeLabels = computed(() => ({
    filling: isRtl.value ? 'حشوة' : 'Filling',
    extraction: isRtl.value ? 'خلع' : 'Extraction',
    root_canal: isRtl.value ? 'علاج عصب' : 'Root Canal',
    crown: isRtl.value ? 'تاج' : 'Crown',
    bridge: isRtl.value ? 'جسر' : 'Bridge',
    implant: isRtl.value ? 'زرعة' : 'Implant',
    cleaning: isRtl.value ? 'تنظيف' : 'Cleaning',
    scaling: isRtl.value ? 'تقليح' : 'Scaling',
    whitening: isRtl.value ? 'تبييض' : 'Whitening',
    veneer: isRtl.value ? 'فينير' : 'Veneer',
    orthodontic: isRtl.value ? 'تقويم' : 'Orthodontic',
    denture: isRtl.value ? 'طقم أسنان' : 'Denture',
    sealant: isRtl.value ? 'مانع تسوس' : 'Sealant',
    fluoride: isRtl.value ? 'فلورايد' : 'Fluoride',
    gum_treatment: isRtl.value ? 'علاج لثة' : 'Gum Treatment',
    surgical_extraction: isRtl.value ? 'خلع جراحي' : 'Surgical Extraction',
    bone_graft: isRtl.value ? 'زراعة عظم' : 'Bone Graft',
    sinus_lift: isRtl.value ? 'رفع الجيب' : 'Sinus Lift',
    night_guard: isRtl.value ? 'واقي ليلي' : 'Night Guard',
    retainer: isRtl.value ? 'مثبت' : 'Retainer',
}));

onMounted(() => { setTimeout(() => { mounted.value = true; }, 80); });

// FDI Numbering layout — Adult
const adultUpperRight = [18, 17, 16, 15, 14, 13, 12, 11];
const adultUpperLeft = [21, 22, 23, 24, 25, 26, 27, 28];
const adultLowerRight = [48, 47, 46, 45, 44, 43, 42, 41];
const adultLowerLeft = [31, 32, 33, 34, 35, 36, 37, 38];

// FDI Numbering layout — Deciduous (children)
const deciduousUpperRight = [55, 54, 53, 52, 51];
const deciduousUpperLeft = [61, 62, 63, 64, 65];
const deciduousLowerRight = [85, 84, 83, 82, 81];
const deciduousLowerLeft = [71, 72, 73, 74, 75];

// Dynamic based on toggle
const upperRight = computed(() => showDeciduousTeeth.value ? deciduousUpperRight : adultUpperRight);
const upperLeft = computed(() => showDeciduousTeeth.value ? deciduousUpperLeft : adultUpperLeft);
const lowerRight = computed(() => showDeciduousTeeth.value ? deciduousLowerRight : adultLowerRight);
const lowerLeft = computed(() => showDeciduousTeeth.value ? deciduousLowerLeft : adultLowerLeft);
const totalTeethCount = computed(() => showDeciduousTeeth.value ? 20 : 32);

// ─── Tooth Side View SVG Paths ────────────────────────────
// Each tooth: { crown, roots[], width, height }
// Orientation: crown at bottom, roots at top (for upper jaw display)
const sideViewPaths = {
    central: {
        crown: 'M10,32 C10,28 8,24 8,20 C8,16 10,14 12,13 C14,12 18,12 20,13 C22,14 24,16 24,20 C24,24 22,28 22,32 Z',
        roots: ['M14,13 C14,10 13,6 13,3 C13,1 15,0 16,0 C17,0 19,1 19,3 C19,6 18,10 18,13'],
        enamel: 'M10,32 C10,29 9,25 9,22 C9,19 11,17 13,16 C15,15 17,15 19,16 C21,17 23,19 23,22 C23,25 22,29 22,32 Z',
        width: 32, height: 36
    },
    lateral: {
        crown: 'M11,32 C11,28 9,24 9,21 C9,17 11,15 13,14 C15,13 17,13 19,14 C21,15 23,17 23,21 C23,24 21,28 21,32 Z',
        roots: ['M14,14 C14,11 13,7 13,3 C13,1 15,0 16,0 C17,0 18,1 18,3 C18,7 17,11 17,14'],
        enamel: 'M11,32 C11,29 10,25 10,22 C10,19 12,17 14,16 C15,15.5 17,15.5 18,16 C20,17 22,19 22,22 C22,25 21,29 21,32 Z',
        width: 32, height: 36
    },
    canine: {
        crown: 'M10,32 C10,28 8,24 8,20 C8,16 10,14 13,12 C15,11 16,10.5 17,11 C18,11.5 20,14 22,16 C24,18 24,24 24,28 C24,30 22,32 22,32 Z',
        roots: ['M14,12 C14,9 13,5 12,2 C12,0.5 14,0 15,0 C16,0 18,0.5 18,2 C17,5 16,9 16,12'],
        enamel: 'M10,32 C10,29 9,25 9,22 C9,18 11,16 14,14 C15,13 17,14 19,16 C21,18 23,20 23,24 C23,28 22,30 22,32 Z',
        width: 32, height: 36
    },
    premolar1: {
        crown: 'M8,32 C8,28 7,24 7,20 C7,17 9,14 11,12 C12,11 14,10 16,10 C18,10 20,11 21,12 C23,14 25,17 25,20 C25,24 24,28 24,32 Z',
        roots: ['M12,12 C12,9 11,6 10,3 C10,1 12,0 13,0 C14,0 15,1 15,3 C14,6 14,9 14,11',
                'M19,12 C19,9 20,6 21,3 C21,1 19,0 18,0 C17,0 16,1 16,3 C17,6 17,9 18,11'],
        enamel: 'M9,32 C9,28 8,24 8,21 C8,18 10,16 12,14 C14,13 18,13 20,14 C22,16 24,18 24,21 C24,24 23,28 23,32 Z',
        width: 32, height: 36
    },
    premolar2: {
        crown: 'M8,32 C8,28 7,24 7,20 C7,17 9,14 11,12 C12,11 14,10.5 16,10.5 C18,10.5 20,11 21,12 C23,14 25,17 25,20 C25,24 24,28 24,32 Z',
        roots: ['M14,12 C14,9 13,5 13,2 C13,0.5 15,0 16,0 C17,0 19,0.5 19,2 C19,5 18,9 18,12'],
        enamel: 'M9,32 C9,28 8,24 8,21 C8,18 10,16 12,14 C14,13 18,13 20,14 C22,16 24,18 24,21 C24,24 23,28 23,32 Z',
        width: 32, height: 36
    },
    molar1: {
        crown: 'M5,32 C5,28 4,24 4,20 C4,16 6,13 9,11 C11,10 14,9.5 18,9.5 C22,9.5 25,10 27,11 C30,13 32,16 32,20 C32,24 31,28 31,32 Z',
        roots: ['M10,11 C10,8 9,5 8,2 C8,0.5 10,0 11,0 C12,0 13,0.5 13,2 C12,5 12,8 12,11',
                'M17,10 C17,7 17,4 17,2 C17,0.5 18,0 19,0 C20,0 20,0.5 20,2 C20,4 20,7 20,10',
                'M25,11 C25,8 26,5 27,2 C27,0.5 25,0 24,0 C23,0 22,0.5 22,2 C23,5 23,8 24,11'],
        enamel: 'M6,32 C6,28 5,24 5,21 C5,18 7,15 10,13 C12,12 16,11 20,11 C24,11 26,12 28,14 C30,16 31,19 31,22 C31,25 30,28 30,32 Z',
        width: 36, height: 36
    },
    molar2: {
        crown: 'M6,32 C6,28 5,24 5,20 C5,16 7,13 10,11 C12,10 15,9.5 18,9.5 C21,9.5 24,10 26,11 C29,13 31,16 31,20 C31,24 30,28 30,32 Z',
        roots: ['M11,11 C11,8 10,5 9,2 C9,0.5 11,0 12,0 C13,0 14,1 13,3 C13,5 12,8 12,11',
                'M18,10 C18,7 18,4 18,2 C18,0.5 19,0 20,0 C21,0 21,1 21,2 C21,4 20,7 20,10',
                'M24,11 C24,8 25,5 26,2 C26,0.5 24,0 23,0 C22,0 22,1 22,3 C23,5 23,8 23,11'],
        enamel: 'M7,32 C7,28 6,24 6,21 C6,18 8,15 11,13 C13,12 16,11.5 19,11.5 C22,11.5 24,12 26,14 C28,16 29,19 29,22 C29,25 28,28 28,32 Z',
        width: 36, height: 36
    },
    molar3: {
        crown: 'M7,32 C7,29 6,25 6,21 C6,17 8,14 11,12 C13,11 15,10.5 17,10.5 C19,10.5 22,11 24,12 C27,14 29,17 29,21 C29,25 28,29 28,32 Z',
        roots: ['M12,12 C12,9 11,6 10,3 C10,1 12,0 13,0 C14,0 15,1 15,3 C14,6 14,9 14,12',
                'M22,12 C22,9 23,6 24,3 C24,1 22,0 21,0 C20,0 19,1 19,3 C20,6 20,9 20,12'],
        enamel: 'M8,32 C8,29 7,25 7,22 C7,19 9,16 12,14 C14,13 16,12.5 18,12.5 C20,12.5 22,13 24,15 C26,17 27,20 27,23 C27,26 26,29 26,32 Z',
        width: 34, height: 36
    },
};

// Tooth type mapping — Adult + Deciduous
const toothTypeMap = {
    // Adult teeth
    18: 'molar3', 17: 'molar2', 16: 'molar1', 15: 'premolar2', 14: 'premolar1', 13: 'canine', 12: 'lateral', 11: 'central',
    21: 'central', 22: 'lateral', 23: 'canine', 24: 'premolar1', 25: 'premolar2', 26: 'molar1', 27: 'molar2', 28: 'molar3',
    31: 'central', 32: 'lateral', 33: 'canine', 34: 'premolar1', 35: 'premolar2', 36: 'molar1', 37: 'molar2', 38: 'molar3',
    41: 'central', 42: 'lateral', 43: 'canine', 44: 'premolar1', 45: 'premolar2', 46: 'molar1', 47: 'molar2', 48: 'molar3',
    // Deciduous teeth (5 per quadrant: central, lateral, canine, 1st molar, 2nd molar)
    51: 'central', 52: 'lateral', 53: 'canine', 54: 'molar1', 55: 'molar2',
    61: 'central', 62: 'lateral', 63: 'canine', 64: 'molar1', 65: 'molar2',
    71: 'central', 72: 'lateral', 73: 'canine', 74: 'molar1', 75: 'molar2',
    81: 'central', 82: 'lateral', 83: 'canine', 84: 'molar1', 85: 'molar2',
};

// ─── Occlusal View (5-surface cross) ──────────────────────
// Surfaces: top=buccal, bottom=lingual, left=mesial, right=distal, center=occlusal
const occlusalShapes = {
    // For anterior teeth (incisors, canines) - more rounded
    anterior: {
        outer: 'M6,2 C10,0 18,0 22,2 C26,5 28,10 28,16 C28,22 26,27 22,30 C18,32 10,32 6,30 C2,27 0,22 0,16 C0,10 2,5 6,2Z',
        buccal: 'M6,2 C10,0 18,0 22,2 C20,7 18,10 14,10 C10,10 8,7 6,2Z',
        lingual: 'M6,30 C10,32 18,32 22,30 C20,25 18,22 14,22 C10,22 8,25 6,30Z',
        mesial: 'M6,2 C2,5 0,10 0,16 C0,22 2,27 6,30 C7,24 7,20 7,16 C7,12 7,8 6,2Z',
        distal: 'M22,2 C26,5 28,10 28,16 C28,22 26,27 22,30 C21,24 21,20 21,16 C21,12 21,8 22,2Z',
        occlusal: 'M7,10 C10,9 18,9 21,10 C22,13 22,19 21,22 C18,23 10,23 7,22 C6,19 6,13 7,10Z',
        size: 28,
    },
    // For posterior teeth (premolars, molars) - more square/rectangular
    posterior: {
        outer: 'M4,3 C8,0 24,0 28,3 C32,7 34,12 34,16 C34,21 32,26 28,29 C24,32 8,32 4,29 C0,26 -2,21 -2,16 C-2,12 0,7 4,3Z',
        buccal: 'M4,3 C8,0 24,0 28,3 C25,8 22,10 16,10 C10,10 7,8 4,3Z',
        lingual: 'M4,29 C8,32 24,32 28,29 C25,24 22,22 16,22 C10,22 7,24 4,29Z',
        mesial: 'M4,3 C0,7 -2,12 -2,16 C-2,21 0,26 4,29 C5,23 5,19 5,16 C5,13 5,9 4,3Z',
        distal: 'M28,3 C32,7 34,12 34,16 C34,21 32,26 28,29 C27,23 27,19 27,16 C27,13 27,9 28,3Z',
        occlusal: 'M6,10 C10,9 22,9 26,10 C27,13 27,19 26,22 C22,23 10,23 6,22 C5,19 5,13 6,10Z',
        size: 32,
    },
};

function getOcclusalType(num) {
    const type = toothTypeMap[num];
    if (['central','lateral','canine'].includes(type)) return 'anterior';
    return 'posterior';
}

// ─── Condition Colors ─────────────────────────────────────
const conditionTheme = {
    healthy:    { fill: '#F0FDF4', stroke: '#86EFAC', inner: '#BBF7D0', text: '#166534', accent: '#22C55E' },
    decayed:    { fill: '#FEF2F2', stroke: '#FCA5A5', inner: '#FECACA', text: '#991B1B', accent: '#EF4444' },
    filled:     { fill: '#EFF6FF', stroke: '#93C5FD', inner: '#BFDBFE', text: '#1E40AF', accent: '#3B82F6' },
    missing:    { fill: '#F9FAFB', stroke: '#D1D5DB', inner: '#E5E7EB', text: '#6B7280', accent: '#9CA3AF' },
    crown:      { fill: '#FEFCE8', stroke: '#FDE047', inner: '#FEF08A', text: '#854D0E', accent: '#EAB308' },
    bridge:     { fill: '#FAF5FF', stroke: '#C084FC', inner: '#DDD6FE', text: '#6B21A8', accent: '#A855F7' },
    implant:    { fill: '#EEF2FF', stroke: '#A5B4FC', inner: '#C7D2FE', text: '#3730A3', accent: '#6366F1' },
    root_canal: { fill: '#FFF7ED', stroke: '#FDBA74', inner: '#FED7AA', text: '#9A3412', accent: '#F97316' },
    extracted:  { fill: '#F3F4F6', stroke: '#9CA3AF', inner: '#D1D5DB', text: '#374151', accent: '#6B7280' },
};

const conditionLabels = computed(() => ({
    healthy: isRtl.value ? 'سليم' : 'Healthy',
    decayed: isRtl.value ? 'متسوس' : 'Decayed',
    filled: isRtl.value ? 'محشو' : 'Filled',
    missing: isRtl.value ? 'مفقود' : 'Missing',
    crown: isRtl.value ? 'تاج' : 'Crown',
    bridge: isRtl.value ? 'جسر' : 'Bridge',
    implant: isRtl.value ? 'زرعة' : 'Implant',
    root_canal: isRtl.value ? 'علاج عصب' : 'Root Canal',
    extracted: isRtl.value ? 'مخلوع' : 'Extracted',
}));

const surfaceLabels = computed(() => ({
    mesial: isRtl.value ? 'وسطي' : 'Mesial',
    distal: isRtl.value ? 'بعيد' : 'Distal',
    buccal: isRtl.value ? 'خدي' : 'Buccal',
    lingual: isRtl.value ? 'لساني' : 'Lingual',
    occlusal: isRtl.value ? 'إطباقي' : 'Occlusal',
    incisal: isRtl.value ? 'قاطع' : 'Incisal',
}));

// ─── Stats ────────────────────────────────────────────────
const chartStats = computed(() => {
    const stats = {};
    for (const cond of Object.keys(conditionTheme)) stats[cond] = 0;
    for (const tooth of [...upperRight.value, ...upperLeft.value, ...lowerRight.value, ...lowerLeft.value]) {
        const c = getToothData(tooth).condition;
        if (stats[c] !== undefined) stats[c]++;
    }
    return stats;
});

const totalRecorded = computed(() => {
    const currentTeeth = [...upperRight.value, ...upperLeft.value, ...lowerRight.value, ...lowerLeft.value];
    return currentTeeth.filter(t => props.chart && props.chart[t]).length;
});

// ─── Helpers ──────────────────────────────────────────────
function getToothData(num) {
    return props.chart[num] || { tooth_number: num, condition: 'healthy', status: 'present', surfaces: [] };
}

function getToothType(num) { return toothTypeMap[num] || 'premolar1'; }

function getSidePaths(num) { return sideViewPaths[getToothType(num)]; }

function getSideViewBox(num) {
    const p = getSidePaths(num);
    return `0 0 ${p.width} ${p.height}`;
}

function hasSurface(num, surface) {
    const data = getToothData(num);
    return (data.surfaces || []).includes(surface);
}

function getSurfaceFill(num, surface) {
    const data = getToothData(num);
    const cond = data.condition || 'healthy';
    const theme = conditionTheme[cond];
    if ((data.surfaces || []).includes(surface)) return theme.accent;
    if (cond !== 'healthy') return theme.inner;
    return '#FFFFFF';
}

function selectTooth(num) {
    selectedTooth.value = num;
    const data = getToothData(num);
    editForm.value = {
        condition: data.condition || 'healthy',
        surfaces: data.surfaces ? [...data.surfaces] : [],
        notes: data.notes || '',
        status: data.status || 'present',
    };
    showModal.value = true;
}

function selectToothInfo(num) {
    if (selectedTooth.value === num) {
        selectedTooth.value = null;
    } else {
        selectedTooth.value = num;
    }
}

function saveTooth() {
    saving.value = true;
    router.post(`/doctor/dental/chart/${props.patient.id}/tooth/${selectedTooth.value}`, editForm.value, {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
        onFinish: () => { saving.value = false; },
    });
}

const showInitializeModal = ref(false);

function confirmInitializeChart() {
    showInitializeModal.value = true;
}

function executeInitializeChart() {
    showInitializeModal.value = false;
    router.post(`/doctor/dental/chart/${props.patient.id}/initialize`, {}, { preserveScroll: true });
}

function toggleSurface(surface) {
    const idx = editForm.value.surfaces.indexOf(surface);
    if (idx > -1) editForm.value.surfaces.splice(idx, 1);
    else editForm.value.surfaces.push(surface);
}

function getToothTreatments(num) { return props.treatments[num] || []; }

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-5">
        <!-- ═══ HERO HEADER ═══ -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-5 sm:p-7 dental-hero-enter">
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-cyan-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'طب الأسنان' : 'Dental' }}</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="text-gray-300 text-sm">{{ patient.full_name }}</span>
                            <span class="text-gray-500 font-mono text-xs bg-white/10 px-2 py-0.5 rounded">{{ patient.file_number }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap justify-start sm:justify-end">
                        <!-- Adult/Deciduous Toggle -->
                        <button @click="showDeciduousTeeth = !showDeciduousTeeth; selectedTooth = null;"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-xl border transition-all"
                            :class="showDeciduousTeeth
                                ? 'text-pink-300 bg-pink-500/15 border-pink-500/20 hover:bg-pink-500/25'
                                : 'text-emerald-300 bg-emerald-500/15 border-emerald-500/20 hover:bg-emerald-500/25'">
                            <svg class="w-3.5 h-3.5 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                            {{ showDeciduousTeeth ? (isRtl ? 'أسنان دائمة' : 'Adult') : (isRtl ? 'أسنان لبنية' : 'Deciduous') }}
                        </button>
                        <Link :href="`/doctor/dental/treatment-plans?patient_id=${patient.id}`"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-purple-300 bg-purple-500/15 rounded-xl hover:bg-purple-500/25 border border-purple-500/20 transition-all">
                            <svg class="w-3.5 h-3.5 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                            {{ isRtl ? 'خطط العلاج' : 'Plans' }}
                        </Link>
                        <Link :href="`/doctor/dental/xrays/patient/${patient.id}`"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-cyan-300 bg-cyan-500/15 rounded-xl hover:bg-cyan-500/25 border border-cyan-500/20 transition-all">
                            <svg class="w-3.5 h-3.5 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ isRtl ? 'الأشعة' : 'X-Rays' }}
                        </Link>
                        <button @click="confirmInitializeChart"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-[#C4A265] bg-[#C4A265]/15 rounded-xl hover:bg-[#C4A265]/25 border border-[#C4A265]/20 transition-all">
                            <svg class="w-3.5 h-3.5 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            {{ isRtl ? 'تهيئة' : 'Init' }}
                        </button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="flex flex-wrap gap-2 mt-4">
                    <template v-for="cond in Object.keys(conditionTheme)" :key="cond">
                        <div v-if="chartStats[cond] > 0"
                            class="bg-white/5 backdrop-blur-sm rounded-lg px-2.5 py-1.5 border border-white/10 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: conditionTheme[cond].accent }"></span>
                            <span class="text-[10px] text-gray-400">{{ conditionLabels[cond] }}</span>
                            <span class="text-xs font-bold text-white">{{ chartStats[cond] }}</span>
                        </div>
                    </template>
                    <div class="bg-white/5 backdrop-blur-sm rounded-lg px-2.5 py-1.5 border border-white/10 flex items-center gap-1.5 ms-auto">
                        <span class="text-[10px] text-gray-400">{{ isRtl ? 'مسجل' : 'Recorded' }}</span>
                        <span class="text-xs font-bold text-[#C4A265]">{{ totalRecorded }}/{{ totalTeethCount }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ MEDICAL ALERT BANNER ═══ -->
        <div v-if="patient.allergies || patient.chronic_conditions || patient.current_medications"
            class="rounded-2xl border-2 overflow-hidden dental-card-enter"
            :class="patient.allergies ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'"
            style="animation-delay: 0.1s">
            <div class="px-3 sm:px-5 py-3 flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                    :class="patient.allergies ? 'bg-red-100' : 'bg-amber-100'">
                    <svg class="w-5 h-5" :class="patient.allergies ? 'text-red-600' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-bold uppercase tracking-wider mb-1.5"
                        :class="patient.allergies ? 'text-red-700' : 'text-amber-700'">
                        {{ isRtl ? 'تنبيه طبي' : 'Medical Alert' }}
                    </h4>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <div v-if="patient.allergies">
                            <span class="text-[10px] font-bold text-red-500 uppercase block">{{ isRtl ? 'الحساسية' : 'Allergies' }}</span>
                            <span class="text-xs font-semibold text-red-800">{{ patient.allergies }}</span>
                        </div>
                        <div v-if="patient.chronic_conditions">
                            <span class="text-[10px] font-bold text-amber-500 uppercase block">{{ isRtl ? 'الأمراض المزمنة' : 'Chronic Conditions' }}</span>
                            <span class="text-xs text-amber-800">{{ patient.chronic_conditions }}</span>
                        </div>
                        <div v-if="patient.current_medications">
                            <span class="text-[10px] font-bold text-amber-500 uppercase block">{{ isRtl ? 'الأدوية الحالية' : 'Current Medications' }}</span>
                            <span class="text-xs text-amber-800">{{ patient.current_medications }}</span>
                        </div>
                        <div v-if="patient.blood_type">
                            <span class="text-[10px] font-bold text-amber-500 uppercase block">{{ isRtl ? 'فصيلة الدم' : 'Blood Type' }}</span>
                            <span class="text-xs font-semibold text-amber-800">{{ patient.blood_type }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ MAIN DENTAL CHART ═══ -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden dental-card-enter"
            style="animation-delay: 0.15s">

            <!-- Legend Bar -->
            <div class="px-4 sm:px-6 py-3 bg-gray-50/80 border-b border-gray-100 flex flex-wrap items-center gap-x-4 gap-y-1.5">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'دليل' : 'Legend' }}</span>
                <div v-for="cond in Object.keys(conditionTheme)" :key="cond" class="flex items-center gap-1 cursor-default group">
                    <span class="w-2.5 h-2.5 rounded-sm border transition-transform group-hover:scale-125"
                        :style="{ backgroundColor: conditionTheme[cond].inner, borderColor: conditionTheme[cond].stroke }"></span>
                    <span class="text-[10px] text-gray-500 group-hover:text-gray-700 transition-colors">{{ conditionLabels[cond] }}</span>
                </div>
            </div>

            <div class="px-2 sm:px-4 md:px-6 py-4 overflow-x-auto scrollbar-none">
                <div class="min-w-[500px]">

                <!-- ═══ UPPER JAW ═══ -->
                <div class="text-center mb-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ isRtl ? 'الفك العلوي' : 'Upper Jaw' }}</span>
                </div>

                <!-- Upper Side View (Roots UP, Crowns DOWN) -->
                <div class="flex justify-center items-end gap-px sm:gap-0.5 mb-0.5 dental-row" :class="mounted ? 'dental-row-enter' : ''">
                    <template v-for="(num, i) in upperRight" :key="'us-r-'+num">
                        <div class="dental-tooth-cell" :style="{ animationDelay: (i * 30) + 'ms' }"
                            @click="selectToothInfo(num)" @dblclick="selectTooth(num)"
                            @mouseenter="hoveredTooth = num" @mouseleave="hoveredTooth = null">
                            <svg :viewBox="getSideViewBox(num)" class="dental-side-svg upper-tooth"
                                :class="{ 'dental-hovered': hoveredTooth === num, 'dental-selected': selectedTooth === num, 'dental-missing': getToothData(num).condition === 'missing' || getToothData(num).condition === 'extracted' }"
                                :style="selectedTooth === num ? { filter: 'drop-shadow(0 2px 6px ' + conditionTheme[getToothData(num).condition].accent + '60)' } : {}">
                                <!-- Roots (displayed at top for upper teeth via CSS transform) -->
                                <g class="tooth-roots" opacity="0.6">
                                    <path v-for="(rp, ri) in getSidePaths(num).roots" :key="ri" :d="rp"
                                        :fill="conditionTheme[getToothData(num).condition].fill"
                                        :stroke="conditionTheme[getToothData(num).condition].stroke"
                                        stroke-width="0.8" />
                                    <!-- Root canal lines -->
                                    <path v-for="(rp, ri) in getSidePaths(num).roots" :key="'rc-'+ri" :d="rp"
                                        fill="none" :stroke="conditionTheme[getToothData(num).condition].stroke"
                                        stroke-width="0.3" opacity="0.4" />
                                </g>
                                <!-- Crown -->
                                <path :d="getSidePaths(num).crown"
                                    :fill="conditionTheme[getToothData(num).condition].inner"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="1" />
                                <!-- Enamel layer -->
                                <path :d="getSidePaths(num).enamel"
                                    :fill="conditionTheme[getToothData(num).condition].fill"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="0.5" opacity="0.6" />
                            </svg>
                        </div>
                    </template>

                    <!-- Center Line -->
                    <div class="dental-center-line"></div>

                    <template v-for="(num, i) in upperLeft" :key="'us-l-'+num">
                        <div class="dental-tooth-cell" :style="{ animationDelay: ((8+i) * 30) + 'ms' }"
                            @click="selectToothInfo(num)" @dblclick="selectTooth(num)"
                            @mouseenter="hoveredTooth = num" @mouseleave="hoveredTooth = null">
                            <svg :viewBox="getSideViewBox(num)" class="dental-side-svg upper-tooth"
                                :class="{ 'dental-hovered': hoveredTooth === num, 'dental-selected': selectedTooth === num, 'dental-missing': getToothData(num).condition === 'missing' || getToothData(num).condition === 'extracted' }"
                                :style="selectedTooth === num ? { filter: 'drop-shadow(0 2px 6px ' + conditionTheme[getToothData(num).condition].accent + '60)' } : {}">
                                <g class="tooth-roots" opacity="0.6">
                                    <path v-for="(rp, ri) in getSidePaths(num).roots" :key="ri" :d="rp"
                                        :fill="conditionTheme[getToothData(num).condition].fill"
                                        :stroke="conditionTheme[getToothData(num).condition].stroke"
                                        stroke-width="0.8" />
                                </g>
                                <path :d="getSidePaths(num).crown"
                                    :fill="conditionTheme[getToothData(num).condition].inner"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="1" />
                                <path :d="getSidePaths(num).enamel"
                                    :fill="conditionTheme[getToothData(num).condition].fill"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="0.5" opacity="0.6" />
                            </svg>
                        </div>
                    </template>
                </div>

                <!-- Gum Line Upper -->
                <div class="dental-gum-line upper-gum"></div>

                <!-- Upper Occlusal View -->
                <div class="flex justify-center items-center gap-px sm:gap-0.5 my-1 dental-row" :class="mounted ? 'dental-row-enter' : ''" style="animation-delay: 0.2s">
                    <template v-for="(num, i) in upperRight" :key="'uo-r-'+num">
                        <div class="dental-occlusal-cell" :style="{ animationDelay: (i * 30 + 200) + 'ms' }"
                            @click="selectToothInfo(num)" @dblclick="selectTooth(num)"
                            @mouseenter="hoveredTooth = num" @mouseleave="hoveredTooth = null">
                            <svg :viewBox="getOcclusalType(num) === 'anterior' ? '0 0 28 32' : '-2 0 36 32'"
                                class="dental-occlusal-svg"
                                :class="{ 'dental-hovered': hoveredTooth === num, 'dental-selected': selectedTooth === num, 'dental-missing': getToothData(num).condition === 'missing' || getToothData(num).condition === 'extracted' }">
                                <!-- Outer border -->
                                <path :d="occlusalShapes[getOcclusalType(num)].outer"
                                    :fill="conditionTheme[getToothData(num).condition].fill"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="1" />
                                <!-- Buccal -->
                                <path :d="occlusalShapes[getOcclusalType(num)].buccal"
                                    :fill="getSurfaceFill(num, 'buccal')"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="0.5" opacity="0.8" />
                                <!-- Lingual -->
                                <path :d="occlusalShapes[getOcclusalType(num)].lingual"
                                    :fill="getSurfaceFill(num, 'lingual')"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="0.5" opacity="0.8" />
                                <!-- Mesial -->
                                <path :d="occlusalShapes[getOcclusalType(num)].mesial"
                                    :fill="getSurfaceFill(num, 'mesial')"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="0.5" opacity="0.8" />
                                <!-- Distal -->
                                <path :d="occlusalShapes[getOcclusalType(num)].distal"
                                    :fill="getSurfaceFill(num, 'distal')"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="0.5" opacity="0.8" />
                                <!-- Occlusal center -->
                                <path :d="occlusalShapes[getOcclusalType(num)].occlusal"
                                    :fill="getSurfaceFill(num, 'occlusal')"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="0.6" />
                            </svg>
                            <!-- Tooltip -->
                            <div v-if="hoveredTooth === num" class="dental-tooltip above">
                                {{ conditionLabels[getToothData(num).condition] }}
                                <span v-if="getToothTreatments(num).length" class="text-[#C4A265]"> ({{ getToothTreatments(num).length }})</span>
                            </div>
                        </div>
                    </template>
                    <div class="dental-center-line short"></div>
                    <template v-for="(num, i) in upperLeft" :key="'uo-l-'+num">
                        <div class="dental-occlusal-cell" :style="{ animationDelay: ((8+i) * 30 + 200) + 'ms' }"
                            @click="selectToothInfo(num)" @dblclick="selectTooth(num)"
                            @mouseenter="hoveredTooth = num" @mouseleave="hoveredTooth = null">
                            <svg :viewBox="getOcclusalType(num) === 'anterior' ? '0 0 28 32' : '-2 0 36 32'"
                                class="dental-occlusal-svg"
                                :class="{ 'dental-hovered': hoveredTooth === num, 'dental-selected': selectedTooth === num, 'dental-missing': getToothData(num).condition === 'missing' || getToothData(num).condition === 'extracted' }">
                                <path :d="occlusalShapes[getOcclusalType(num)].outer" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                                <path :d="occlusalShapes[getOcclusalType(num)].buccal" :fill="getSurfaceFill(num, 'buccal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].lingual" :fill="getSurfaceFill(num, 'lingual')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].mesial" :fill="getSurfaceFill(num, 'mesial')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].distal" :fill="getSurfaceFill(num, 'distal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].occlusal" :fill="getSurfaceFill(num, 'occlusal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.6" />
                            </svg>
                            <div v-if="hoveredTooth === num" class="dental-tooltip above">
                                {{ conditionLabels[getToothData(num).condition] }}
                                <span v-if="getToothTreatments(num).length" class="text-[#C4A265]"> ({{ getToothTreatments(num).length }})</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- ═══ TOOTH NUMBERS ROW ═══ -->
                <div class="flex justify-center items-center gap-px sm:gap-0.5 my-1.5">
                    <template v-for="num in upperRight" :key="'n-ur-'+num">
                        <div class="dental-number-cell" :class="{ 'dental-number-active': selectedTooth === num || hoveredTooth === num }"
                            @click="selectToothInfo(num)">{{ num }}</div>
                    </template>
                    <div class="dental-center-line short"></div>
                    <template v-for="num in upperLeft" :key="'n-ul-'+num">
                        <div class="dental-number-cell" :class="{ 'dental-number-active': selectedTooth === num || hoveredTooth === num }"
                            @click="selectToothInfo(num)">{{ num }}</div>
                    </template>
                </div>

                <!-- ═══ MIDLINE DIVIDER ═══ -->
                <div class="relative my-2">
                    <div class="border-t border-dashed border-gray-300"></div>
                    <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 flex justify-center">
                        <span class="bg-white px-3 text-[9px] font-bold text-gray-300 uppercase tracking-[0.2em]">
                            {{ isRtl ? 'يمين' : 'RIGHT' }} &nbsp;&bull;&nbsp; {{ isRtl ? 'يسار' : 'LEFT' }}
                        </span>
                    </div>
                </div>

                <!-- Lower Numbers -->
                <div class="flex justify-center items-center gap-px sm:gap-0.5 my-1.5">
                    <template v-for="num in lowerRight" :key="'n-lr-'+num">
                        <div class="dental-number-cell" :class="{ 'dental-number-active': selectedTooth === num || hoveredTooth === num }"
                            @click="selectToothInfo(num)">{{ num }}</div>
                    </template>
                    <div class="dental-center-line short"></div>
                    <template v-for="num in lowerLeft" :key="'n-ll-'+num">
                        <div class="dental-number-cell" :class="{ 'dental-number-active': selectedTooth === num || hoveredTooth === num }"
                            @click="selectToothInfo(num)">{{ num }}</div>
                    </template>
                </div>

                <!-- ═══ LOWER JAW ═══ -->

                <!-- Lower Occlusal View -->
                <div class="flex justify-center items-center gap-px sm:gap-0.5 my-1 dental-row" :class="mounted ? 'dental-row-enter' : ''" style="animation-delay: 0.4s">
                    <template v-for="(num, i) in lowerRight" :key="'lo-r-'+num">
                        <div class="dental-occlusal-cell" :style="{ animationDelay: ((16+i) * 30 + 200) + 'ms' }"
                            @click="selectToothInfo(num)" @dblclick="selectTooth(num)"
                            @mouseenter="hoveredTooth = num" @mouseleave="hoveredTooth = null">
                            <svg :viewBox="getOcclusalType(num) === 'anterior' ? '0 0 28 32' : '-2 0 36 32'" class="dental-occlusal-svg"
                                :class="{ 'dental-hovered': hoveredTooth === num, 'dental-selected': selectedTooth === num, 'dental-missing': getToothData(num).condition === 'missing' || getToothData(num).condition === 'extracted' }">
                                <path :d="occlusalShapes[getOcclusalType(num)].outer" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                                <path :d="occlusalShapes[getOcclusalType(num)].buccal" :fill="getSurfaceFill(num, 'buccal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].lingual" :fill="getSurfaceFill(num, 'lingual')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].mesial" :fill="getSurfaceFill(num, 'mesial')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].distal" :fill="getSurfaceFill(num, 'distal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].occlusal" :fill="getSurfaceFill(num, 'occlusal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.6" />
                            </svg>
                            <div v-if="hoveredTooth === num" class="dental-tooltip below">
                                {{ conditionLabels[getToothData(num).condition] }}
                                <span v-if="getToothTreatments(num).length" class="text-[#C4A265]"> ({{ getToothTreatments(num).length }})</span>
                            </div>
                        </div>
                    </template>
                    <div class="dental-center-line short"></div>
                    <template v-for="(num, i) in lowerLeft" :key="'lo-l-'+num">
                        <div class="dental-occlusal-cell" :style="{ animationDelay: ((24+i) * 30 + 200) + 'ms' }"
                            @click="selectToothInfo(num)" @dblclick="selectTooth(num)"
                            @mouseenter="hoveredTooth = num" @mouseleave="hoveredTooth = null">
                            <svg :viewBox="getOcclusalType(num) === 'anterior' ? '0 0 28 32' : '-2 0 36 32'" class="dental-occlusal-svg"
                                :class="{ 'dental-hovered': hoveredTooth === num, 'dental-selected': selectedTooth === num, 'dental-missing': getToothData(num).condition === 'missing' || getToothData(num).condition === 'extracted' }">
                                <path :d="occlusalShapes[getOcclusalType(num)].outer" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                                <path :d="occlusalShapes[getOcclusalType(num)].buccal" :fill="getSurfaceFill(num, 'buccal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].lingual" :fill="getSurfaceFill(num, 'lingual')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].mesial" :fill="getSurfaceFill(num, 'mesial')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].distal" :fill="getSurfaceFill(num, 'distal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.8" />
                                <path :d="occlusalShapes[getOcclusalType(num)].occlusal" :fill="getSurfaceFill(num, 'occlusal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.6" />
                            </svg>
                            <div v-if="hoveredTooth === num" class="dental-tooltip below">
                                {{ conditionLabels[getToothData(num).condition] }}
                                <span v-if="getToothTreatments(num).length" class="text-[#C4A265]"> ({{ getToothTreatments(num).length }})</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Gum Line Lower -->
                <div class="dental-gum-line lower-gum"></div>

                <!-- Lower Side View (Crowns UP, Roots DOWN) -->
                <div class="flex justify-center items-start gap-px sm:gap-0.5 mt-0.5 dental-row" :class="mounted ? 'dental-row-enter' : ''" style="animation-delay: 0.5s">
                    <template v-for="(num, i) in lowerRight" :key="'ls-r-'+num">
                        <div class="dental-tooth-cell" :style="{ animationDelay: ((16+i) * 30 + 400) + 'ms' }"
                            @click="selectToothInfo(num)" @dblclick="selectTooth(num)"
                            @mouseenter="hoveredTooth = num" @mouseleave="hoveredTooth = null">
                            <svg :viewBox="getSideViewBox(num)" class="dental-side-svg lower-tooth"
                                :class="{ 'dental-hovered': hoveredTooth === num, 'dental-selected': selectedTooth === num, 'dental-missing': getToothData(num).condition === 'missing' || getToothData(num).condition === 'extracted' }"
                                :style="selectedTooth === num ? { filter: 'drop-shadow(0 2px 6px ' + conditionTheme[getToothData(num).condition].accent + '60)' } : {}">
                                <path :d="getSidePaths(num).crown"
                                    :fill="conditionTheme[getToothData(num).condition].inner"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="1" />
                                <path :d="getSidePaths(num).enamel"
                                    :fill="conditionTheme[getToothData(num).condition].fill"
                                    :stroke="conditionTheme[getToothData(num).condition].stroke"
                                    stroke-width="0.5" opacity="0.6" />
                                <g class="tooth-roots" opacity="0.6">
                                    <path v-for="(rp, ri) in getSidePaths(num).roots" :key="ri" :d="rp"
                                        :fill="conditionTheme[getToothData(num).condition].fill"
                                        :stroke="conditionTheme[getToothData(num).condition].stroke"
                                        stroke-width="0.8" />
                                </g>
                            </svg>
                        </div>
                    </template>
                    <div class="dental-center-line"></div>
                    <template v-for="(num, i) in lowerLeft" :key="'ls-l-'+num">
                        <div class="dental-tooth-cell" :style="{ animationDelay: ((24+i) * 30 + 400) + 'ms' }"
                            @click="selectToothInfo(num)" @dblclick="selectTooth(num)"
                            @mouseenter="hoveredTooth = num" @mouseleave="hoveredTooth = null">
                            <svg :viewBox="getSideViewBox(num)" class="dental-side-svg lower-tooth"
                                :class="{ 'dental-hovered': hoveredTooth === num, 'dental-selected': selectedTooth === num, 'dental-missing': getToothData(num).condition === 'missing' || getToothData(num).condition === 'extracted' }"
                                :style="selectedTooth === num ? { filter: 'drop-shadow(0 2px 6px ' + conditionTheme[getToothData(num).condition].accent + '60)' } : {}">
                                <path :d="getSidePaths(num).crown" :fill="conditionTheme[getToothData(num).condition].inner" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                                <path :d="getSidePaths(num).enamel" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.6" />
                                <g class="tooth-roots" opacity="0.6">
                                    <path v-for="(rp, ri) in getSidePaths(num).roots" :key="ri" :d="rp" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.8" />
                                </g>
                            </svg>
                        </div>
                    </template>
                </div>

                <div class="text-center mt-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ isRtl ? 'الفك السفلي' : 'Lower Jaw' }}</span>
                </div>

                <!-- Interaction hint -->
                <div class="text-center mt-3 mb-1">
                    <span class="text-[10px] text-gray-300">{{ isRtl ? 'اضغط مرة لعرض التفاصيل • اضغط مرتين للتعديل' : 'Click to view details • Double-click to edit' }}</span>
                </div>
                </div>
            </div>
        </div>

        <!-- ═══ SELECTED TOOTH DETAIL PANEL ═══ -->
        <Transition enter-active-class="transition-all duration-400 ease-out" enter-from-class="opacity-0 translate-y-3" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="selectedTooth && !showModal" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="flex items-stretch border-b border-gray-100">
                    <!-- Tooth Info -->
                    <div class="flex-1 px-3 sm:px-5 py-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-mono text-lg font-bold"
                                :style="{ backgroundColor: conditionTheme[getToothData(selectedTooth).condition].fill, color: conditionTheme[getToothData(selectedTooth).condition].text, border: '2px solid ' + conditionTheme[getToothData(selectedTooth).condition].stroke }">
                                {{ selectedTooth }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">{{ isRtl ? 'سن' : 'Tooth' }} #{{ selectedTooth }}</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                        :style="{ backgroundColor: conditionTheme[getToothData(selectedTooth).condition].fill, color: conditionTheme[getToothData(selectedTooth).condition].text }">
                                        {{ conditionLabels[getToothData(selectedTooth).condition] }}
                                    </span>
                                    <span v-if="getToothData(selectedTooth).notes" class="text-[10px] text-gray-400">{{ getToothData(selectedTooth).notes }}</span>
                                </div>
                            </div>
                            <div class="ms-auto flex items-center gap-1.5 flex-wrap">
                                <button @click="showQuickTreatment = !showQuickTreatment"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-cyan-600 bg-cyan-50 hover:bg-cyan-100 rounded-lg border border-cyan-200 transition-all"
                                    :class="showQuickTreatment ? 'ring-2 ring-cyan-300' : ''">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    {{ isRtl ? 'إضافة علاج' : 'Add Treatment' }}
                                </button>
                                <button @click="selectTooth(selectedTooth)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 rounded-lg border border-[#C4A265]/20 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    {{ isRtl ? 'تعديل' : 'Edit' }}
                                </button>
                            </div>
                        </div>

                        <!-- Surfaces -->
                        <div v-if="getToothData(selectedTooth).surfaces?.length" class="flex flex-wrap gap-1.5 mb-2">
                            <span class="text-[10px] text-gray-400 font-semibold uppercase self-center me-1">{{ isRtl ? 'الأسطح:' : 'Surfaces:' }}</span>
                            <span v-for="s in getToothData(selectedTooth).surfaces" :key="s"
                                class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                                :style="{ backgroundColor: conditionTheme[getToothData(selectedTooth).condition].fill, color: conditionTheme[getToothData(selectedTooth).condition].text }">
                                {{ surfaceLabels[s] || s }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Add Treatment Form -->
                <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-96"
                    leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 max-h-96" leave-to-class="opacity-0 max-h-0">
                    <div v-if="showQuickTreatment" class="border-b border-gray-100 overflow-hidden">
                        <div class="px-3 sm:px-5 py-2.5 bg-cyan-50/50 border-b border-cyan-100/50 flex items-center justify-between">
                            <span class="text-xs font-bold text-cyan-700 uppercase tracking-wider">{{ isRtl ? 'إضافة علاج سريع' : 'Quick Add Treatment' }}</span>
                            <button @click="resetQuickTreatment" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="p-4 space-y-3">
                            <!-- Treatment Type -->
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'نوع العلاج' : 'Treatment Type' }} *</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="type in ['filling', 'extraction', 'root_canal', 'crown', 'bridge', 'implant', 'cleaning', 'scaling', 'veneer', 'whitening', 'surgical_extraction', 'sealant']"
                                        :key="type" type="button" @click="quickTreatmentForm.treatment_type = type"
                                        class="px-2.5 py-1.5 rounded-lg text-[11px] font-medium border transition-all"
                                        :class="quickTreatmentForm.treatment_type === type
                                            ? 'bg-cyan-600 text-white border-cyan-600 shadow-sm'
                                            : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-gray-300'">
                                        {{ treatmentTypeLabels[type] || type }}
                                    </button>
                                </div>
                            </div>
                            <!-- Surfaces -->
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'الأسطح' : 'Surfaces' }}</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="s in surfaces" :key="s" type="button" @click="toggleQuickTreatmentSurface(s)"
                                        class="px-3 py-1.5 rounded-lg text-[11px] font-medium border transition-all"
                                        :class="quickTreatmentForm.surfaces.includes(s)
                                            ? 'bg-[#C4A265] text-white border-[#C4A265]'
                                            : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-gray-300'">
                                        {{ surfaceLabels[s] || s }}
                                    </button>
                                </div>
                            </div>
                            <!-- Status + Cost -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'الحالة' : 'Status' }}</label>
                                    <select v-model="quickTreatmentForm.status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-cyan-200 focus:border-cyan-300 transition-all">
                                        <option value="planned">{{ isRtl ? 'مخطط' : 'Planned' }}</option>
                                        <option value="in_progress">{{ isRtl ? 'قيد التنفيذ' : 'In Progress' }}</option>
                                        <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'التكلفة' : 'Cost' }}</label>
                                    <input v-model="quickTreatmentForm.cost" type="number" step="0.01" min="0"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-cyan-200 focus:border-cyan-300 transition-all"
                                        :placeholder="isRtl ? 'اختياري' : 'Optional'" />
                                </div>
                            </div>
                            <!-- Description -->
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <input v-model="quickTreatmentForm.description" type="text"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-cyan-200 focus:border-cyan-300 transition-all"
                                    :placeholder="isRtl ? 'وصف مختصر...' : 'Brief description...'" />
                            </div>
                            <!-- Save -->
                            <div class="flex justify-end gap-2 pt-1">
                                <button @click="resetQuickTreatment" class="px-4 py-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                                <button @click="saveQuickTreatment" :disabled="savingTreatment || !quickTreatmentForm.treatment_type"
                                    class="px-5 py-2 bg-cyan-600 text-white rounded-lg text-xs font-semibold hover:bg-cyan-700 disabled:opacity-40 transition-all shadow-sm flex items-center gap-1.5">
                                    <svg v-if="savingTreatment" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ isRtl ? 'حفظ العلاج' : 'Save Treatment' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Treatment History -->
                <div v-if="getToothTreatments(selectedTooth).length > 0">
                    <div class="px-3 sm:px-5 py-2.5 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'سجل العلاج' : 'Treatment History' }}</span>
                        <span class="text-[10px] bg-[#C4A265]/10 text-[#C4A265] px-2 py-0.5 rounded-full font-bold">{{ getToothTreatments(selectedTooth).length }}</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="tr in getToothTreatments(selectedTooth)" :key="tr.id" class="px-3 sm:px-5 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="{
                                    'bg-green-500': tr.status === 'completed',
                                    'bg-[#C4A265]': tr.status === 'in_progress',
                                    'bg-gray-300': tr.status === 'planned',
                                    'bg-red-400': tr.status === 'cancelled',
                                }"></div>
                                <div>
                                    <span class="text-sm font-medium text-gray-800">{{ tr.treatment_type ? (locale === 'ar' ? (tr.treatment_type.name_ar || tr.treatment_type.name_en) : (tr.treatment_type.name_en || tr.treatment_type.name_ar)) : (tr.treatment_type_name || '-') }}</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span v-if="tr.doctor" class="text-[10px] text-gray-400">{{ locale === 'ar' ? tr.doctor.name_ar : tr.doctor.name_en }}</span>
                                        <span v-if="tr.created_at" class="text-[10px] text-gray-300">{{ formatDate(tr.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="{
                                'bg-green-50 text-green-700': tr.status === 'completed',
                                'bg-[#C4A265]/10 text-[#C4A265]': tr.status === 'in_progress',
                                'bg-gray-100 text-gray-500': tr.status === 'planned',
                                'bg-red-50 text-red-600': tr.status === 'cancelled',
                            }">
                                {{ isRtl ? { planned: 'مخطط', in_progress: 'جاري', completed: 'مكتمل', cancelled: 'ملغي' }[tr.status] || tr.status
                                    : { planned: 'Planned', in_progress: 'In Progress', completed: 'Done', cancelled: 'Cancelled' }[tr.status] || tr.status }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-3 sm:px-5 py-6 text-center">
                    <p class="text-xs text-gray-300">{{ isRtl ? 'لا يوجد سجل علاج لهذا السن' : 'No treatment history for this tooth' }}</p>
                </div>
            </div>
        </Transition>
    </div>

    <!-- ═══ EDIT TOOTH MODAL ═══ -->
    <Teleport to="body">
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showModal = false">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-900 to-gray-800 px-4 sm:px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#C4A265]/20 flex items-center justify-center">
                                <span class="text-[#C4A265] font-bold font-mono text-lg">{{ selectedTooth }}</span>
                            </div>
                            <div>
                                <h3 class="text-white font-bold">{{ isRtl ? 'تعديل السن' : 'Edit Tooth' }}</h3>
                                <p class="text-gray-400 text-xs">{{ conditionLabels[editForm.condition] }}</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="p-4 sm:p-6 space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'حالة السن' : 'Condition' }}</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button v-for="cond in conditions" :key="cond" type="button" @click="editForm.condition = cond"
                                    class="relative px-2.5 py-2 rounded-xl text-xs font-medium border-2 transition-all duration-200"
                                    :class="editForm.condition === cond ? 'scale-[1.02] shadow-md' : 'border-gray-100 hover:border-gray-200'"
                                    :style="editForm.condition === cond ? { borderColor: conditionTheme[cond].stroke, backgroundColor: conditionTheme[cond].fill, color: conditionTheme[cond].text } : {}">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full border" :style="{ backgroundColor: conditionTheme[cond].inner, borderColor: conditionTheme[cond].stroke }"></span>
                                        {{ conditionLabels[cond] }}
                                    </span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'الأسطح' : 'Surfaces' }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="s in surfaces" :key="s" type="button" @click="toggleSurface(s)"
                                    class="px-4 py-2 rounded-xl text-sm font-medium border-2 transition-all"
                                    :class="editForm.surfaces.includes(s) ? 'bg-[#C4A265] text-white border-[#C4A265] shadow-md shadow-[#C4A265]/20' : 'bg-gray-50 text-gray-600 border-gray-100 hover:border-gray-200'">
                                    {{ surfaceLabels[s] || s }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'وجود السن' : 'Presence' }}</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="editForm.status = 'present'" class="px-3 py-2.5 rounded-xl text-sm font-medium border-2 transition-all" :class="editForm.status === 'present' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-100 text-gray-500'">{{ isRtl ? 'موجود' : 'Present' }}</button>
                                <button type="button" @click="editForm.status = 'missing'" class="px-3 py-2.5 rounded-xl text-sm font-medium border-2 transition-all" :class="editForm.status === 'missing' ? 'border-gray-500 bg-gray-100 text-gray-700' : 'border-gray-100 text-gray-500'">{{ isRtl ? 'مفقود' : 'Missing' }}</button>
                                <button type="button" @click="editForm.status = 'implant'" class="px-3 py-2.5 rounded-xl text-sm font-medium border-2 transition-all" :class="editForm.status === 'implant' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-100 text-gray-500'">{{ isRtl ? 'زرعة' : 'Implant' }}</button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="editForm.notes" rows="2" class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all resize-none" :placeholder="isRtl ? 'أضف ملاحظات...' : 'Add notes...'"></textarea>
                        </div>
                    </div>
                    <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex gap-3 justify-end flex-wrap">
                        <button @click="showModal = false" class="px-5 py-2.5 border-2 border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-white transition-all">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button @click="saveTooth" :disabled="saving" class="px-6 py-2.5 bg-[#C4A265] text-white rounded-xl text-sm font-semibold hover:bg-[#B39255] disabled:opacity-50 transition-all shadow-lg shadow-[#C4A265]/20 flex items-center gap-2">
                            <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ isRtl ? 'حفظ' : 'Save' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Initialize Chart Confirm Modal -->
    <ConfirmModal
        :show="showInitializeModal"
        :title="isRtl ? 'تهيئة المخطط' : 'Initialize Chart'"
        :message="isRtl ? 'هل أنت متأكد من تهيئة المخطط؟ سيتم إنشاء سجل لكل سن.' : 'Initialize chart? This will create a record for each tooth.'"
        :confirmText="isRtl ? 'تهيئة' : 'Initialize'"
        :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
        type="warning"
        @confirm="executeInitializeChart"
        @cancel="showInitializeModal = false"
    />
</template>

<style scoped>
/* ═══ Tooth Cell ═══ */
.dental-tooth-cell {
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
}

.dental-side-svg {
    width: 28px;
    height: 44px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@media (min-width: 640px) { .dental-side-svg { width: 34px; height: 52px; } }
@media (min-width: 768px) { .dental-side-svg { width: 40px; height: 60px; } }

.upper-tooth {
    transform: scaleY(-1);
}

.dental-occlusal-cell {
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
}

.dental-occlusal-svg {
    width: 26px;
    height: 28px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@media (min-width: 640px) { .dental-occlusal-svg { width: 32px; height: 34px; } }
@media (min-width: 768px) { .dental-occlusal-svg { width: 38px; height: 38px; } }

/* ═══ Hover & Selection ═══ */
.dental-hovered {
    transform: scale(1.12);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
}
.upper-tooth.dental-hovered {
    transform: scaleY(-1) scale(1.12);
}
.dental-selected {
    transform: scale(1.15);
}
.upper-tooth.dental-selected {
    transform: scaleY(-1) scale(1.15);
}
.dental-missing {
    opacity: 0.3;
}
.upper-tooth.dental-missing {
    opacity: 0.3;
    transform: scaleY(-1);
}

/* ═══ Number Cells ═══ */
.dental-number-cell {
    width: 26px;
    text-align: center;
    font-size: 9px;
    font-weight: 700;
    font-family: ui-monospace, monospace;
    color: #9CA3AF;
    cursor: pointer;
    padding: 2px 0;
    border-radius: 4px;
    transition: all 0.2s;
    flex-shrink: 0;
}
@media (min-width: 640px) { .dental-number-cell { width: 32px; font-size: 10px; } }
@media (min-width: 768px) { .dental-number-cell { width: 38px; font-size: 11px; } }
.dental-number-cell:hover,
.dental-number-active {
    color: #C4A265;
    background: rgba(196, 162, 101, 0.08);
}

/* ═══ Center Line ═══ */
.dental-center-line {
    width: 1px;
    align-self: stretch;
    background: linear-gradient(to bottom, transparent, #C4A265, transparent);
    opacity: 0.3;
    margin: 0 4px;
    flex-shrink: 0;
}
@media (min-width: 640px) { .dental-center-line { margin: 0 6px; } }
.dental-center-line.short {
    height: 20px;
    align-self: center;
}

/* ═══ Gum Line ═══ */
.dental-gum-line {
    height: 3px;
    margin: 0 8px;
    border-radius: 2px;
    position: relative;
}
.upper-gum {
    background: linear-gradient(90deg, transparent, #FDA4AF, #FB7185, #FDA4AF, transparent);
    opacity: 0.5;
}
.lower-gum {
    background: linear-gradient(90deg, transparent, #FDA4AF, #FB7185, #FDA4AF, transparent);
    opacity: 0.5;
}

/* ═══ Tooltip ═══ */
.dental-tooltip {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    background: #1F2937;
    color: white;
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 6px;
    white-space: nowrap;
    z-index: 30;
    pointer-events: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
.dental-tooltip.above {
    bottom: calc(100% + 6px);
}
.dental-tooltip.above::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #1F2937;
}
.dental-tooltip.below {
    top: calc(100% + 6px);
}
.dental-tooltip.below::after {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-bottom-color: #1F2937;
}

/* ═══ Animations ═══ */
.dental-row-enter .dental-tooth-cell,
.dental-row-enter .dental-occlusal-cell {
    animation: toothFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}

@keyframes toothFadeIn {
    from { opacity: 0; transform: translateY(8px) scale(0.9); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* ═══ Hero & Card Enter Animations ═══ */
@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
</style>
