<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { t } = useLocale();
const { formatCurrency, currencyCode } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    patients: Array,
    doctors: Array,
    treatmentTypes: Array,
    templates: { type: Array, default: () => [] },
});

const form = ref({
    patient_id: props.patient?.id || '',
    doctor_id: '',
    template_id: '',
    title_ar: '',
    title_en: '',
    description: '',
    estimated_cost: '',
    estimated_sessions: '',
    priority: 'normal',
    start_date: '',
    expected_end_date: '',
    notes: '',
    treatments: [],
});

const errors = ref({});
const processing = ref(false);
const showTemplatePanel = ref(false);
const selectedTemplateId = ref(null);
const activeStep = ref(1);
const templateFilter = ref('all');

// ─── Searchable Select State ───────────────────────
const patientSearch = ref('');
const doctorSearch = ref('');
const showPatientDropdown = ref(false);
const showDoctorDropdown = ref(false);

const filteredPatients = computed(() => {
    if (!patientSearch.value) return props.patients || [];
    const s = patientSearch.value.toLowerCase();
    return (props.patients || []).filter(p =>
        p.full_name?.toLowerCase().includes(s) || p.phone?.includes(s) || p.file_number?.includes(s)
    );
});

const filteredDoctors = computed(() => {
    if (!doctorSearch.value) return props.doctors || [];
    const s = doctorSearch.value.toLowerCase();
    return (props.doctors || []).filter(d =>
        d.name_ar?.toLowerCase().includes(s) || d.name_en?.toLowerCase().includes(s)
    );
});

const selectedPatientName = computed(() => {
    if (!form.value.patient_id) return '';
    const p = (props.patients || []).find(p => p.id === form.value.patient_id);
    return p ? `${p.full_name} (${p.file_number})` : '';
});

const selectedDoctorName = computed(() => {
    if (!form.value.doctor_id) return '';
    const d = (props.doctors || []).find(d => d.id === form.value.doctor_id);
    return d ? (locale.value === 'ar' ? d.name_ar : d.name_en) : '';
});

function selectPatient(p) {
    form.value.patient_id = p.id;
    patientSearch.value = '';
    showPatientDropdown.value = false;
}

function selectDoctor(d) {
    form.value.doctor_id = d.id;
    doctorSearch.value = '';
    showDoctorDropdown.value = false;
}

function closeDropdowns(e) {
    if (!e.target.closest('.searchable-select')) {
        showPatientDropdown.value = false;
        showDoctorDropdown.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', closeDropdowns);
    const params = new URLSearchParams(window.location.search);
    const templateId = params.get('template_id');
    if (templateId) {
        const template = props.templates.find(t => t.id === parseInt(templateId));
        if (template) applyTemplate(template);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeDropdowns);
});

// Template categories
const templateCategories = computed(() => {
    const cats = new Set((props.templates || []).map(t => t.category));
    return ['all', ...cats];
});

const filteredTemplates = computed(() => {
    if (templateFilter.value === 'all') return props.templates || [];
    return (props.templates || []).filter(t => t.category === templateFilter.value);
});

// Treatment type labels
const treatmentTypeLabels = computed(() => ({
    filling: isRtl.value ? 'حشو' : 'Filling', extraction: isRtl.value ? 'خلع' : 'Extraction',
    root_canal: isRtl.value ? 'علاج عصب' : 'Root Canal', crown: isRtl.value ? 'تاج' : 'Crown',
    bridge: isRtl.value ? 'جسر' : 'Bridge', implant: isRtl.value ? 'زرعة' : 'Implant',
    cleaning: isRtl.value ? 'تنظيف' : 'Cleaning', scaling: isRtl.value ? 'تقليح' : 'Scaling',
    whitening: isRtl.value ? 'تبييض' : 'Whitening', veneer: isRtl.value ? 'قشرة' : 'Veneer',
    orthodontic: isRtl.value ? 'تقويم' : 'Orthodontic', denture: isRtl.value ? 'طقم' : 'Denture',
    sealant: isRtl.value ? 'مانع تسوس' : 'Sealant', fluoride: isRtl.value ? 'فلورايد' : 'Fluoride',
    gum_treatment: isRtl.value ? 'علاج لثة' : 'Gum Tx', surgical_extraction: isRtl.value ? 'خلع جراحي' : 'Surgical Ext.',
    bone_graft: isRtl.value ? 'ترقيع عظم' : 'Bone Graft', sinus_lift: isRtl.value ? 'رفع جيب' : 'Sinus Lift',
    night_guard: isRtl.value ? 'واقي ليلي' : 'Night Guard', retainer: isRtl.value ? 'مثبت' : 'Retainer',
}));

const categoryLabels = computed(() => ({
    all: isRtl.value ? 'الكل' : 'All',
    general: isRtl.value ? 'عام' : 'General', orthodontic: isRtl.value ? 'تقويم' : 'Orthodontic',
    implant: isRtl.value ? 'زراعة' : 'Implant', cosmetic: isRtl.value ? 'تجميل' : 'Cosmetic',
    restorative: isRtl.value ? 'ترميم' : 'Restorative', surgical: isRtl.value ? 'جراحة' : 'Surgical',
    periodontal: isRtl.value ? 'لثة' : 'Periodontal', pediatric: isRtl.value ? 'أطفال' : 'Pediatric',
}));

const categorySvgIcons = {
    all: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
    general: 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342',
    orthodontic: 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085',
    implant: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z',
    cosmetic: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z',
    restorative: 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z',
    surgical: 'M7.848 8.25l1.536.887M7.848 8.25a3 3 0 11-5.196-3 3 3 0 015.196 3zm1.536.887a2.165 2.165 0 011.083 1.839c.005.461.134.89.341 1.268a.75.75 0 00.129.222l2.678 3.313a1.5 1.5 0 002.346-1.87l-1.884-2.356a.75.75 0 01-.1-.226c-.242-.915-.063-1.91.608-2.602a3.75 3.75 0 00.553-4.555 3.75 3.75 0 00-5.774-.46m3.184 1.434a2.25 2.25 0 00-3.184-1.434',
    periodontal: 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z',
    pediatric: 'M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z',
};

const priorityConfig = {
    low: { label: () => isRtl.value ? 'منخفضة' : 'Low', color: 'slate', icon: 'M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5' },
    normal: { label: () => isRtl.value ? 'عادي' : 'Normal', color: 'emerald', icon: 'M3.75 9h16.5m-16.5 6.75h16.5' },
    high: { label: () => isRtl.value ? 'عالية' : 'High', color: 'amber', icon: 'M4.5 12.75l7.5-7.5 7.5 7.5m-15 6l7.5-7.5 7.5 7.5' },
    urgent: { label: () => isRtl.value ? 'طارئة' : 'Urgent', color: 'rose', icon: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z' },
};

function addTreatment() {
    form.value.treatments.push({
        tooth_number: '', treatment_type: '', surfaces: '',
        description: '', cost: '', lab_cost: '',
    });
    nextTick(() => {
        const el = document.querySelector(`[data-treatment-index="${form.value.treatments.length - 1}"]`);
        el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
}

function removeTreatment(index) {
    form.value.treatments.splice(index, 1);
}

const totalCost = computed(() => {
    return form.value.treatments.reduce((sum, item) => {
        return sum + (parseFloat(item.cost) || 0) + (parseFloat(item.lab_cost) || 0);
    }, 0);
});

// Progress tracking
const formProgress = computed(() => {
    let filled = 0;
    let total = 5;
    if (form.value.patient_id) filled++;
    if (form.value.doctor_id) filled++;
    if (form.value.title_ar || form.value.title_en) filled++;
    if (form.value.treatments.length > 0) filled++;
    if (form.value.priority) filled++;
    return Math.round((filled / total) * 100);
});

// ─── Template ──────────────────────────────────────
const showReplaceModal = ref(false);
const pendingTemplate = ref(null);

function applyTemplate(template) {
    if (form.value.treatments.length > 0) {
        pendingTemplate.value = template;
        showReplaceModal.value = true;
        return;
    }
    executeApplyTemplate(template);
}

function executeApplyTemplate(template) {
    const tpl = template || pendingTemplate.value;
    if (!tpl) return;
    selectedTemplateId.value = tpl.id;
    form.value.template_id = tpl.id;
    form.value.title_ar = tpl.name_ar;
    form.value.title_en = tpl.name_en;
    form.value.description = isRtl.value ? (tpl.description_ar || '') : (tpl.description_en || '');
    form.value.estimated_sessions = tpl.estimated_sessions || 1;
    form.value.priority = tpl.priority || 'normal';
    form.value.notes = tpl.notes || '';
    form.value.treatments = (tpl.treatments || []).map(tr => ({
        tooth_number: tr.tooth_number || '', treatment_type: tr.treatment_type || '',
        surfaces: tr.surfaces || '', description: tr.description || '',
        cost: tr.cost || '', lab_cost: tr.lab_cost || '',
    }));
    showTemplatePanel.value = false;
    showReplaceModal.value = false;
    pendingTemplate.value = null;
}

function submit() {
    processing.value = true;
    errors.value = {};
    router.post('/admin/dental/treatment-plans', form.value, {
        onError: (errs) => { errors.value = errs; },
        onFinish: () => { processing.value = false; },
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_create_treatment_plan')">
        <div class="max-w-5xl mx-auto pb-16 space-y-8">

            <!-- ═══ HERO ═══ -->
            <div class="tp-animate tp-animate-1 relative overflow-hidden rounded-3xl shadow-2xl">
                <!-- Animated BG -->
                <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-emerald-900 to-[#0F2444]"></div>
                <div class="tp-hero-pattern absolute inset-0 opacity-[0.04]"></div>
                <div class="tp-hero-glow absolute -top-24 -end-24 w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl"></div>
                <div class="tp-hero-glow-2 absolute -bottom-20 -start-20 w-80 h-80 bg-[#2C4E7A]/15 rounded-full blur-3xl"></div>

                <div class="relative px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div class="flex items-center gap-5">
                            <div class="tp-icon-float w-16 h-16 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-black text-white tracking-tight">{{ isRtl ? 'إنشاء خطة علاج' : 'Create Treatment Plan' }}</h1>
                                <p class="text-emerald-200/70 text-sm mt-1">{{ isRtl ? 'إنشاء خطة علاج جديدة للمريض' : 'Build a comprehensive treatment plan for the patient' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link href="/admin/dental/treatment-plan-templates" class="group inline-flex items-center gap-2 px-5 py-3 rounded-2xl text-sm font-semibold bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/10 text-white transition-all duration-300 hover:scale-[1.02]">
                                <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                                {{ isRtl ? 'إدارة القوالب' : 'Templates' }}
                            </Link>
                            <Link href="/admin/dental/treatment-plans" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl text-sm font-medium text-white/60 hover:text-white hover:bg-white/10 transition-all duration-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                {{ isRtl ? 'رجوع' : 'Back' }}
                            </Link>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-6 flex items-center gap-4">
                        <div class="flex-1 h-1.5 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-400 to-slate-300 rounded-full transition-all duration-700 ease-out" :style="{ width: formProgress + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold text-emerald-300 tabular-nums">{{ formProgress }}%</span>
                    </div>
                </div>
            </div>

            <!-- ═══ TEMPLATES ═══ -->
            <div v-if="templates.length > 0" class="tp-animate tp-animate-2">
                <div class="tp-glass-card rounded-3xl p-6 border border-slate-100/60">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#1B365D] to-[#2C4E7A] flex items-center justify-center shadow-lg shadow-[#1B365D]/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">{{ isRtl ? 'ابدأ من قالب جاهز' : 'Start from a Template' }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'اختر قالباً لتعبئة البيانات تلقائياً' : 'Auto-fill with a pre-built template' }}</p>
                            </div>
                        </div>
                        <button @click="showTemplatePanel = !showTemplatePanel" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1B365D] hover:text-[#0F2444] bg-slate-50 hover:bg-slate-100 px-4 py-2.5 rounded-xl transition-all duration-300 hover:scale-[1.02]">
                            <svg class="w-4 h-4 transition-transform duration-300" :class="showTemplatePanel ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            {{ showTemplatePanel ? (isRtl ? 'إخفاء' : 'Hide') : (isRtl ? 'عرض الكل' : 'Show All') }}
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-[#1B365D] text-[10px] font-bold">{{ templates.length }}</span>
                        </button>
                    </div>

                    <!-- Quick Chips -->
                    <div v-if="!showTemplatePanel" class="flex flex-wrap gap-2">
                        <button v-for="tmpl in templates.slice(0, 6)" :key="tmpl.id" @click="applyTemplate(tmpl)" class="group inline-flex items-center gap-2.5 px-5 py-3 text-sm font-medium rounded-2xl border-2 transition-all duration-300 hover:shadow-lg hover:scale-[1.02]" :class="selectedTemplateId === tmpl.id ? 'bg-[#1B365D] text-white border-[#1B365D] shadow-lg shadow-[#1B365D]/20' : 'bg-white text-gray-700 border-gray-100 hover:border-slate-200 hover:bg-slate-50'">
                            <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-300 group-hover:scale-110" :class="selectedTemplateId === tmpl.id ? 'text-slate-200' : 'text-[#2C4E7A]'" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="categorySvgIcons[tmpl.category] || categorySvgIcons.general" /></svg>
                            {{ isRtl ? tmpl.name_ar : tmpl.name_en }}
                        </button>
                        <button v-if="templates.length > 6" @click="showTemplatePanel = true" class="inline-flex items-center px-5 py-3 text-sm font-semibold text-[#1B365D] bg-white border-2 border-dashed border-slate-200 rounded-2xl hover:bg-slate-50 hover:border-[#2C4E7A] transition-all duration-300">
                            +{{ templates.length - 6 }} {{ isRtl ? 'المزيد' : 'more' }}
                        </button>
                    </div>

                    <!-- Expanded Grid with Category Filters -->
                    <Transition enter-active-class="transition-all duration-400 ease-out" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-[2000px]" leave-active-class="transition-all duration-300 ease-in" leave-from-class="opacity-100 max-h-[2000px]" leave-to-class="opacity-0 max-h-0">
                        <div v-if="showTemplatePanel" class="overflow-hidden">
                            <!-- Category Filter Tabs -->
                            <div class="flex flex-wrap gap-2 mb-5 pb-4 border-b border-gray-100">
                                <button v-for="cat in templateCategories" :key="cat" @click="templateFilter = cat" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200" :class="templateFilter === cat ? 'bg-[#1B365D] text-white shadow-sm' : 'bg-gray-50 text-gray-500 hover:bg-gray-100'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="categorySvgIcons[cat] || categorySvgIcons.general" /></svg>
                                    {{ categoryLabels[cat] || cat }}
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                <div v-for="tmpl in filteredTemplates" :key="tmpl.id" @click="applyTemplate(tmpl)" class="group cursor-pointer bg-white rounded-2xl border-2 p-5 transition-all duration-300 hover:shadow-xl hover:scale-[1.01]" :class="selectedTemplateId === tmpl.id ? 'border-[#1B365D] ring-4 ring-slate-100 shadow-xl' : 'border-gray-100 hover:border-slate-200'">
                                    <div class="flex items-start gap-3 mb-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-300" :class="selectedTemplateId === tmpl.id ? 'bg-slate-100' : 'bg-gray-50 group-hover:bg-slate-50'">
                                            <svg class="w-5 h-5 transition-all duration-300" :class="selectedTemplateId === tmpl.id ? 'text-[#1B365D]' : 'text-gray-400 group-hover:text-[#1B365D]'" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="categorySvgIcons[tmpl.category] || categorySvgIcons.general" /></svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-bold text-gray-900 truncate">{{ isRtl ? tmpl.name_ar : tmpl.name_en }}</h4>
                                            <span class="inline-block mt-1 text-[10px] font-bold text-[#1B365D] bg-slate-50 px-2 py-0.5 rounded-md uppercase tracking-wider">{{ categoryLabels[tmpl.category] }}</span>
                                        </div>
                                        <svg v-if="selectedTemplateId === tmpl.id" class="w-6 h-6 text-[#1B365D] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                                    </div>
                                    <p v-if="isRtl ? tmpl.description_ar : tmpl.description_en" class="text-xs text-gray-400 line-clamp-2 mb-3">{{ isRtl ? tmpl.description_ar : tmpl.description_en }}</p>
                                    <div class="flex items-center gap-3 text-[10px] text-gray-400 pt-3 border-t border-gray-50">
                                        <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>{{ (tmpl.treatments || []).length }} {{ isRtl ? 'إجراء' : 'procedures' }}</span>
                                        <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{{ tmpl.estimated_sessions }} {{ isRtl ? 'جلسة' : 'sessions' }}</span>
                                        <span class="ms-auto font-bold text-sm text-[#1B365D]">{{ formatCurrency(tmpl.estimated_cost) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <!-- Applied Indicator -->
                    <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                        <div v-if="selectedTemplateId" class="mt-5 flex items-center gap-3 text-sm text-[#1B365D] bg-gradient-to-r from-slate-50 to-slate-50 rounded-2xl px-5 py-3 border border-slate-200/50">
                            <svg class="w-5 h-5 text-[#1B365D] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                            <span class="font-medium">{{ isRtl ? 'تم تطبيق القالب — يمكنك تعديل التفاصيل أدناه' : 'Template applied — customize the details below' }}</span>
                            <button @click="selectedTemplateId = null; form.template_id = ''" class="ms-auto p-1.5 rounded-lg hover:bg-slate-200 transition-colors" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'">
                                <svg class="w-4 h-4 text-[#2C4E7A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">

                <!-- ═══ PLAN DETAILS ═══ -->
                <div class="tp-animate tp-animate-3 tp-glass-card rounded-3xl overflow-hidden border border-gray-100/80">
                    <div class="px-8 py-5 bg-gradient-to-r from-slate-50 via-gray-50/50 to-white border-b border-gray-100/50">
                        <div class="flex items-center gap-4">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#1B365D] to-[#2C4E7A] flex items-center justify-center shadow-lg shadow-[#1B365D]/15">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">{{ isRtl ? 'تفاصيل الخطة' : 'Plan Details' }}</h2>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'المعلومات الأساسية لخطة العلاج' : 'Basic information for the treatment plan' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 space-y-6">
                        <!-- Patient & Doctor -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Patient Select -->
                            <div class="searchable-select">
                                <label class="tp-label">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                    {{ isRtl ? 'المريض' : 'Patient' }} <span class="text-[#D4B57E]">*</span>
                                </label>
                                <div class="relative">
                                    <div @click="!patient && (showPatientDropdown = !showPatientDropdown)" class="tp-input-wrapper cursor-pointer" :class="{ 'tp-input-focus': showPatientDropdown, 'tp-input-disabled': !!patient }">
                                        <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                        <input v-if="showPatientDropdown && !patient" v-model="patientSearch" type="text" :placeholder="isRtl ? 'ابحث بالاسم أو رقم الملف...' : 'Search by name or file number...'" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm text-gray-700 placeholder-gray-300 p-0 focus:ring-0" @click.stop />
                                        <span v-else class="flex-1 text-sm" :class="selectedPatientName ? 'text-gray-800 font-medium' : 'text-gray-300'">{{ selectedPatientName || (isRtl ? 'اختر المريض' : 'Select patient') }}</span>
                                        <svg class="w-4 h-4 text-gray-300 transition-transform duration-300" :class="showPatientDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </div>
                                    <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2 scale-[0.97]" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
                                        <div v-if="showPatientDropdown && !patient" class="absolute z-50 mt-2 w-full bg-white rounded-2xl border border-gray-100 shadow-2xl shadow-gray-200/50 max-h-56 overflow-y-auto">
                                            <div v-if="filteredPatients.length === 0" class="p-4 text-center text-sm text-gray-300">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                            <button v-for="p in filteredPatients" :key="p.id" type="button" @click="selectPatient(p)" class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-emerald-50 transition-all duration-200 text-start" :class="form.patient_id === p.id ? 'bg-emerald-50 text-emerald-700' : 'text-gray-700'">
                                                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                                </div>
                                                <div class="min-w-0"><div class="font-medium truncate">{{ p.full_name }}</div><div class="text-[10px] text-gray-400">{{ p.file_number }} · {{ p.phone }}</div></div>
                                                <svg v-if="form.patient_id === p.id" class="w-5 h-5 text-emerald-500 ms-auto flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </div>
                                    </Transition>
                                </div>
                                <p v-if="errors.patient_id" class="tp-error"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg> {{ errors.patient_id }}</p>
                            </div>

                            <!-- Doctor Select -->
                            <div class="searchable-select">
                                <label class="tp-label">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                    {{ isRtl ? 'الطبيب' : 'Doctor' }} <span class="text-[#D4B57E]">*</span>
                                </label>
                                <div class="relative">
                                    <div @click="showDoctorDropdown = !showDoctorDropdown" class="tp-input-wrapper cursor-pointer" :class="{ 'tp-input-focus': showDoctorDropdown }">
                                        <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                        <input v-if="showDoctorDropdown" v-model="doctorSearch" type="text" :placeholder="isRtl ? 'ابحث عن طبيب...' : 'Search doctor...'" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm text-gray-700 placeholder-gray-300 p-0 focus:ring-0" @click.stop />
                                        <span v-else class="flex-1 text-sm" :class="selectedDoctorName ? 'text-gray-800 font-medium' : 'text-gray-300'">{{ selectedDoctorName || (isRtl ? 'اختر الطبيب' : 'Select doctor') }}</span>
                                        <svg class="w-4 h-4 text-gray-300 transition-transform duration-300" :class="showDoctorDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </div>
                                    <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2 scale-[0.97]" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
                                        <div v-if="showDoctorDropdown" class="absolute z-50 mt-2 w-full bg-white rounded-2xl border border-gray-100 shadow-2xl shadow-gray-200/50 max-h-56 overflow-y-auto">
                                            <div v-if="filteredDoctors.length === 0" class="p-4 text-center text-sm text-gray-300">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                            <button v-for="d in filteredDoctors" :key="d.id" type="button" @click="selectDoctor(d)" class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-emerald-50 transition-all duration-200 text-start" :class="form.doctor_id === d.id ? 'bg-emerald-50 text-emerald-700' : 'text-gray-700'">
                                                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                                </div>
                                                <div class="font-medium">{{ locale === 'ar' ? d.name_ar : d.name_en }}</div>
                                                <svg v-if="form.doctor_id === d.id" class="w-5 h-5 text-emerald-500 ms-auto flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </div>
                                    </Transition>
                                </div>
                                <p v-if="errors.doctor_id" class="tp-error"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg> {{ errors.doctor_id }}</p>
                            </div>
                        </div>

                        <!-- Titles -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg> {{ isRtl ? 'العنوان (عربي)' : 'Title (Arabic)' }}</label>
                                <input v-model="form.title_ar" type="text" dir="rtl" class="doctorato-input tp-input" :placeholder="isRtl ? 'عنوان خطة العلاج بالعربي' : 'Arabic title'" />
                                <p v-if="errors.title_ar" class="tp-error">{{ errors.title_ar }}</p>
                            </div>
                            <div>
                                <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" /></svg> {{ isRtl ? 'العنوان (إنجليزي)' : 'Title (English)' }}</label>
                                <input v-model="form.title_en" type="text" class="doctorato-input tp-input" :placeholder="isRtl ? 'عنوان خطة العلاج بالإنجليزي' : 'English title'" />
                                <p v-if="errors.title_en" class="tp-error">{{ errors.title_en }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg> {{ isRtl ? 'الوصف' : 'Description' }}</label>
                            <textarea v-model="form.description" rows="3" class="doctorato-input tp-input resize-none" :placeholder="isRtl ? 'وصف مختصر لخطة العلاج...' : 'Brief description of the treatment plan...'"></textarea>
                            <p v-if="errors.description" class="tp-error">{{ errors.description }}</p>
                        </div>

                        <!-- Cost / Sessions / Priority -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg> {{ isRtl ? 'التكلفة التقديرية' : 'Estimated Cost' }}</label>
                                <div class="tp-input-wrapper">
                                    <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <input v-model="form.estimated_cost" type="number" min="0" step="0.01" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm text-gray-700 p-0 focus:ring-0" :placeholder="isRtl ? '0.00' : '0.00'" />
                                    <span class="text-xs text-gray-400 font-medium">{{ currencyCode }}</span>
                                </div>
                                <p v-if="errors.estimated_cost" class="tp-error">{{ errors.estimated_cost }}</p>
                            </div>
                            <div>
                                <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> {{ isRtl ? 'عدد الجلسات التقديري' : 'Estimated Sessions' }}</label>
                                <div class="tp-input-wrapper">
                                    <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                    <input v-model="form.estimated_sessions" type="number" min="1" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm text-gray-700 p-0 focus:ring-0" placeholder="1" />
                                    <span class="text-xs text-gray-400">{{ isRtl ? 'جلسة' : 'sessions' }}</span>
                                </div>
                                <p v-if="errors.estimated_sessions" class="tp-error">{{ errors.estimated_sessions }}</p>
                            </div>
                            <div>
                                <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg> {{ isRtl ? 'الأولوية' : 'Priority' }}</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <button v-for="(cfg, key) in priorityConfig" :key="key" type="button" @click="form.priority = key" class="tp-priority-btn flex flex-col items-center gap-1.5 px-2 py-3 rounded-2xl border-2 text-[10px] font-bold transition-all duration-300" :class="form.priority === key ? `tp-priority-active-${cfg.color}` : 'bg-gray-50/80 border-transparent text-gray-400 hover:bg-gray-100'">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="cfg.icon" /></svg>
                                        {{ cfg.label() }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" /></svg> {{ isRtl ? 'تاريخ البدء' : 'Start Date' }}</label>
                                <input v-model="form.start_date" type="date" class="doctorato-input tp-input" />
                                <p v-if="errors.start_date" class="tp-error">{{ errors.start_date }}</p>
                            </div>
                            <div>
                                <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg> {{ isRtl ? 'تاريخ الانتهاء المتوقع' : 'Expected End Date' }}</label>
                                <input v-model="form.expected_end_date" type="date" class="doctorato-input tp-input" />
                                <p v-if="errors.expected_end_date" class="tp-error">{{ errors.expected_end_date }}</p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="tp-label"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg> {{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="form.notes" rows="2" class="doctorato-input tp-input resize-none" :placeholder="isRtl ? 'ملاحظات إضافية...' : 'Additional notes...'"></textarea>
                        </div>
                    </div>
                </div>

                <!-- ═══ TREATMENTS ═══ -->
                <div class="tp-animate tp-animate-4 tp-glass-card rounded-3xl overflow-hidden border border-gray-100/80">
                    <div class="px-8 py-5 bg-gradient-to-r from-slate-50 via-gray-50/50 to-white border-b border-gray-100/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/15">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">{{ isRtl ? 'الإجراءات العلاجية' : 'Treatment Procedures' }}</h2>
                                    <p class="text-xs text-gray-400">{{ isRtl ? 'أضف الإجراءات المطلوبة لخطة العلاج' : 'Add required procedures for the plan' }}</p>
                                </div>
                            </div>
                            <button type="button" @click="addTreatment" class="group inline-flex items-center gap-2 px-5 py-3 text-sm font-bold text-white bg-gradient-to-r from-emerald-500 to-emerald-500 rounded-2xl hover:from-emerald-600 hover:to-emerald-600 shadow-lg shadow-emerald-500/20 hover:shadow-xl hover:shadow-emerald-500/25 transition-all duration-300 active:scale-[0.97]">
                                <svg class="w-5 h-5 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                {{ isRtl ? 'إضافة إجراء' : 'Add Procedure' }}
                            </button>
                        </div>
                    </div>

                    <div class="p-8">
                        <!-- Empty State -->
                        <div v-if="form.treatments.length === 0" class="text-center py-20">
                            <div class="relative w-24 h-24 mx-auto mb-6">
                                <div class="absolute inset-0 bg-gradient-to-br from-emerald-100 to-slate-100 rounded-3xl rotate-6"></div>
                                <div class="absolute inset-0 bg-white rounded-3xl shadow-sm flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                                </div>
                            </div>
                            <p class="text-base font-semibold text-gray-500 mb-1">{{ isRtl ? 'لم تتم إضافة إجراءات بعد' : 'No procedures added yet' }}</p>
                            <p class="text-sm text-gray-300 mb-6">{{ isRtl ? 'اضغط الزر أعلاه لإضافة إجراء جديد' : 'Click the button above to add a new procedure' }}</p>
                            <button type="button" @click="addTreatment" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-emerald-600 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                {{ isRtl ? 'إضافة أول إجراء' : 'Add First Procedure' }}
                            </button>
                        </div>

                        <!-- Treatment Cards -->
                        <TransitionGroup enter-active-class="transition-all duration-400 ease-out" enter-from-class="opacity-0 translate-y-6 scale-[0.96]" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition-all duration-300 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-x-12 scale-90" move-class="transition-all duration-400 ease-in-out" tag="div" class="space-y-5">
                            <div v-for="(treatment, index) in form.treatments" :key="index" :data-treatment-index="index" class="tp-treatment-card group relative rounded-2xl border-2 border-gray-100 hover:border-emerald-200 p-6 transition-all duration-300 hover:shadow-lg hover:shadow-emerald-500/5">
                                <!-- Left accent bar -->
                                <div class="absolute top-4 bottom-4 start-0 w-1 rounded-full bg-gradient-to-b from-emerald-400 to-[#2C4E7A] opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                                <!-- Remove Button -->
                                <button type="button" @click="removeTreatment(index)" class="absolute top-4 end-4 w-8 h-8 rounded-xl flex items-center justify-center text-gray-300 hover:text-white hover:bg-[#C4A265] opacity-0 group-hover:opacity-100 transition-all duration-300 hover:scale-110" :aria-label="isRtl ? 'حذف' : 'Delete'" :title="isRtl ? 'حذف' : 'Delete'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                </button>

                                <!-- Badge -->
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-50 to-slate-50 text-emerald-600 text-xs font-bold mb-5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" /></svg>
                                    {{ isRtl ? 'إجراء' : 'Procedure' }} {{ index + 1 }}
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label class="tp-label-sm">{{ isRtl ? 'رقم السن' : 'Tooth Number' }}</label>
                                        <div class="tp-input-wrapper-sm">
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" /></svg>
                                            <input v-model="treatment.tooth_number" type="text" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm p-0 focus:ring-0" placeholder="e.g. 18" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="tp-label-sm">{{ isRtl ? 'نوع العلاج' : 'Treatment Type' }}</label>
                                        <select v-model="treatment.treatment_type" class="doctorato-input tp-select-sm">
                                            <option value="">{{ isRtl ? 'اختر النوع' : 'Select type' }}</option>
                                            <option v-for="tt in treatmentTypes" :key="tt" :value="tt">{{ treatmentTypeLabels[tt] || tt }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="tp-label-sm">{{ isRtl ? 'الأسطح' : 'Surfaces' }}</label>
                                        <div class="tp-input-wrapper-sm">
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z" /></svg>
                                            <input v-model="treatment.surfaces" type="text" placeholder="M, O, D, B, L" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm p-0 focus:ring-0" />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5">
                                    <label class="tp-label-sm">{{ isRtl ? 'الوصف' : 'Description' }}</label>
                                    <div class="tp-input-wrapper-sm">
                                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                                        <input v-model="treatment.description" type="text" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm p-0 focus:ring-0" :placeholder="isRtl ? 'وصف الإجراء...' : 'Describe the procedure...'" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                                    <div>
                                        <label class="tp-label-sm">{{ isRtl ? 'التكلفة' : 'Cost' }}</label>
                                        <div class="tp-input-wrapper-sm">
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <input v-model="treatment.cost" type="number" min="0" step="0.01" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm p-0 focus:ring-0" placeholder="0.00" />
                                            <span class="text-[10px] text-gray-400 font-medium">{{ currencyCode }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="tp-label-sm">{{ isRtl ? 'تكلفة المعمل' : 'Lab Cost' }}</label>
                                        <div class="tp-input-wrapper-sm">
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
                                            <input v-model="treatment.lab_cost" type="number" min="0" step="0.01" class="doctorato-input flex-1 bg-transparent border-none outline-none text-sm p-0 focus:ring-0" placeholder="0.00" />
                                            <span class="text-[10px] text-gray-400 font-medium">{{ currencyCode }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TransitionGroup>

                        <!-- Total Footer -->
                        <div v-if="form.treatments.length > 0" class="mt-8 flex items-center justify-between pt-6 border-t-2 border-dashed border-gray-100">
                            <div class="flex items-center gap-2 text-sm text-gray-400">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                                {{ form.treatments.length }} {{ isRtl ? 'إجراء' : 'procedures' }}
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-500 font-medium">{{ isRtl ? 'إجمالي التكلفة' : 'Total Cost' }}</span>
                                <span class="text-2xl font-black bg-gradient-to-r from-emerald-600 to-emerald-600 bg-clip-text text-transparent">{{ formatCurrency(totalCost) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ SUBMIT ═══ -->
                <div class="tp-animate tp-animate-5 flex items-center gap-4 pt-2">
                    <button type="submit" :disabled="processing" class="group relative inline-flex items-center gap-3 px-10 py-4 rounded-2xl text-white font-bold text-sm overflow-hidden transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.97] hover:scale-[1.01] shadow-xl shadow-emerald-500/25 hover:shadow-2xl hover:shadow-emerald-500/30">
                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500 via-[#1B365D] to-emerald-500 bg-[length:200%_100%] tp-shimmer"></div>
                        <svg v-if="!processing" class="w-5 h-5 relative transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else class="w-5 h-5 relative animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                        <span class="relative">{{ processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'إنشاء الخطة' : 'Create Plan') }}</span>
                    </button>
                    <Link href="/admin/dental/treatment-plans" class="inline-flex items-center gap-2 px-6 py-4 rounded-2xl bg-gray-100 text-gray-500 text-sm font-semibold hover:bg-gray-200 hover:text-gray-700 transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </Link>
                </div>
            </form>
        </div>

        <!-- Replace Procedures Confirm Modal -->
        <ConfirmModal
            :show="showReplaceModal"
            :title="isRtl ? 'استبدال الإجراءات' : 'Replace Procedures'"
            :message="isRtl ? 'سيتم استبدال الإجراءات الحالية بإجراءات القالب. هل أنت متأكد؟' : 'Current procedures will be replaced with the template procedures. Are you sure?'"
            :confirmText="isRtl ? 'استبدال' : 'Replace'"
            :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
            confirmColor="cyan"
            @confirm="executeApplyTemplate(null)"
            @cancel="showReplaceModal = false; pendingTemplate = null"
        />
    </AdminLayout>
</template>

<style scoped>
/* ─── Staggered Entry Animations ────────────────── */
.tp-animate {
    opacity: 0;
    transform: translateY(24px);
    animation: tpReveal 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.tp-animate-1 { animation-delay: 0s; }
.tp-animate-2 { animation-delay: 0.1s; }
.tp-animate-3 { animation-delay: 0.18s; }
.tp-animate-4 { animation-delay: 0.26s; }
.tp-animate-5 { animation-delay: 0.34s; }

@keyframes tpReveal {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ─── Hero Effects ──────────────────────────────── */
.tp-hero-pattern {
    background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0);
    background-size: 24px 24px;
}
.tp-hero-glow {
    animation: tpPulseGlow 6s ease-in-out infinite alternate;
}
.tp-hero-glow-2 {
    animation: tpPulseGlow 8s ease-in-out infinite alternate-reverse;
}
@keyframes tpPulseGlow {
    from { opacity: 0.15; transform: scale(1); }
    to { opacity: 0.3; transform: scale(1.15); }
}

.tp-icon-float {
    animation: tpFloat 4s ease-in-out infinite;
}
@keyframes tpFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

/* ─── Shimmer on Submit ─────────────────────────── */
.tp-shimmer {
    animation: tpShimmerBg 3s linear infinite;
}
@keyframes tpShimmerBg {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}

/* ─── Glass Card ────────────────────────────────── */
.tp-glass-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 32px rgba(0,0,0,0.04);
}

/* ─── Treatment Card Hover ──────────────────────── */
.tp-treatment-card {
    background: linear-gradient(135deg, rgba(249,250,251,0.5) 0%, rgba(255,255,255,1) 100%);
}

/* ─── Priority Buttons ──────────────────────────── */
.tp-priority-active-slate {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    color: #475569;
    box-shadow: 0 2px 8px rgba(100,116,139,0.15);
}
.tp-priority-active-emerald {
    background-color: #ecfdf5;
    border-color: #6ee7b7;
    color: #059669;
    box-shadow: 0 2px 8px rgba(16,185,129,0.2);
}
.tp-priority-active-amber {
    background-color: #fffbeb;
    border-color: #fcd34d;
    color: #d97706;
    box-shadow: 0 2px 8px rgba(245,158,11,0.2);
}
.tp-priority-active-rose {
    background-color: #fff1f2;
    border-color: #fda4af;
    color: #e11d48;
    box-shadow: 0 2px 8px rgba(225,29,72,0.15);
}

/* ─── Labels ────────────────────────────────────── */
.tp-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.625rem;
}
.tp-label-sm {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    line-height: 1rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

/* ─── Inputs ────────────────────────────────────── */
.tp-input {
    width: 100%;
    padding: 0.875rem 1.125rem;
    border: 2px solid #f3f4f6;
    border-radius: 1rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #374151;
    background-color: rgba(249, 250, 251, 0.5);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
}
.tp-input::placeholder { color: #d1d5db; }
.tp-input:hover { border-color: #e5e7eb; }
.tp-input:focus {
    border-color: #5eead4;
    box-shadow: 0 0 0 4px rgba(94, 234, 212, 0.1);
    background-color: #fff;
}
.tp-input-wrapper {
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
.tp-input-wrapper:hover { border-color: #e5e7eb; }
.tp-input-focus {
    border-color: #5eead4 !important;
    box-shadow: 0 0 0 4px rgba(94, 234, 212, 0.1) !important;
    background-color: #fff !important;
}
.tp-input-disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #f3f4f6;
}
.tp-input-wrapper-sm {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.75rem 1rem;
    border: 2px solid #f3f4f6;
    border-radius: 1rem;
    font-size: 0.875rem;
    background-color: rgba(249, 250, 251, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.tp-input-wrapper-sm:hover { border-color: #e5e7eb; }
.tp-input-wrapper-sm:focus-within {
    border-color: #5eead4;
    box-shadow: 0 0 0 4px rgba(94, 234, 212, 0.1);
    background-color: #fff;
}
.tp-select-sm {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #f3f4f6;
    border-radius: 1rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #374151;
    background-color: rgba(249, 250, 251, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    appearance: none;
    cursor: pointer;
    outline: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: left 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-inline-start: 2.5rem;
}
.tp-select-sm:hover { border-color: #e5e7eb; }
.tp-select-sm:focus {
    border-color: #5eead4;
    box-shadow: 0 0 0 4px rgba(94, 234, 212, 0.1);
    background-color: #fff;
}
[dir="ltr"] .tp-select-sm,
:root:not([dir="rtl"]) .tp-select-sm {
    background-position: right 0.75rem center;
    padding-inline-start: 1rem;
    padding-inline-end: 2.5rem;
}

/* ─── Error ─────────────────────────────────────── */
.tp-error {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    line-height: 1rem;
    font-weight: 500;
    color: #f43f5e;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}
</style>
