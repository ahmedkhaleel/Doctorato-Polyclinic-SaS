<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    templates: Array,
    categories: Array,
    treatmentTypes: Array,
    filters: Object,
});

const activeFilter = ref(props.filters?.category || '');
const searchQuery = ref(props.filters?.search || '');
const showModal = ref(false);
const editingTemplate = ref(null);

const categoryLabels = computed(() => ({
    general: isRtl.value ? 'عام' : 'General',
    orthodontic: isRtl.value ? 'تقويم' : 'Orthodontic',
    implant: isRtl.value ? 'زراعة' : 'Implant',
    cosmetic: isRtl.value ? 'تجميل' : 'Cosmetic',
    restorative: isRtl.value ? 'ترميم' : 'Restorative',
    surgical: isRtl.value ? 'جراحة' : 'Surgical',
    periodontal: isRtl.value ? 'لثة' : 'Periodontal',
    pediatric: isRtl.value ? 'أطفال' : 'Pediatric',
}));

const categoryIcons = {
    general: '🦷', orthodontic: '🔧', implant: '🔩', cosmetic: '✨',
    restorative: '🏗️', surgical: '🔪', periodontal: '🩺', pediatric: '👶',
};

const categoryColors = {
    general: { bg: 'bg-gray-50', border: 'border-gray-200', text: 'text-gray-700', accent: '#6B7280' },
    orthodontic: { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-[#1B365D]', accent: '#1B365D' },
    implant: { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-[#1B365D]', accent: '#1B365D' },
    cosmetic: { bg: 'bg-[#F5E7C8]/40', border: 'border-[#F5E7C8]', text: 'text-[#8B7043]', accent: '#C4A265' },
    restorative: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', accent: '#C4A265' },
    surgical: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', accent: '#EF4444' },
    periodontal: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', accent: '#10B981' },
    pediatric: { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-[#1B365D]', accent: '#1B365D' },
};

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

const priorityLabels = computed(() => ({
    low: isRtl.value ? 'منخفض' : 'Low', normal: isRtl.value ? 'عادي' : 'Normal',
    high: isRtl.value ? 'عالي' : 'High', urgent: isRtl.value ? 'عاجل' : 'Urgent',
}));

// Filtered templates
const filteredTemplates = computed(() => {
    let list = props.templates || [];
    if (activeFilter.value) {
        list = list.filter(t => t.category === activeFilter.value);
    }
    return list;
});

// Category stats
const categoryCounts = computed(() => {
    const counts = {};
    (props.templates || []).forEach(t => {
        counts[t.category] = (counts[t.category] || 0) + 1;
    });
    return counts;
});

// ─── Form ───────────────────────────────────────────
const form = ref(getEmptyForm());

function getEmptyForm() {
    return {
        name_ar: '', name_en: '', category: 'general',
        description_ar: '', description_en: '',
        treatments: [{ treatment_type: '', tooth_number: '', surfaces: [], description: '', cost: '', lab_cost: '', sort_order: 1 }],
        estimated_sessions: 1, priority: 'normal', notes: '',
    };
}

function openCreate() {
    editingTemplate.value = null;
    form.value = getEmptyForm();
    showModal.value = true;
}

function openEdit(template) {
    editingTemplate.value = template;
    form.value = {
        name_ar: template.name_ar, name_en: template.name_en,
        category: template.category,
        description_ar: template.description_ar || '',
        description_en: template.description_en || '',
        treatments: (template.treatments || []).map((t, i) => ({
            treatment_type: t.treatment_type || '', tooth_number: t.tooth_number || '',
            surfaces: t.surfaces || [], description: t.description || '',
            cost: t.cost || '', lab_cost: t.lab_cost || '', sort_order: t.sort_order || i + 1,
        })),
        estimated_sessions: template.estimated_sessions || 1,
        priority: template.priority || 'normal',
        notes: template.notes || '',
    };
    showModal.value = true;
}

function addTreatment() {
    form.value.treatments.push({
        treatment_type: '', tooth_number: '', surfaces: [], description: '',
        cost: '', lab_cost: '', sort_order: form.value.treatments.length + 1,
    });
}

function removeTreatment(index) {
    form.value.treatments.splice(index, 1);
}

const formTotalCost = computed(() => {
    return form.value.treatments.reduce((sum, t) => sum + (parseFloat(t.cost) || 0) + (parseFloat(t.lab_cost) || 0), 0);
});

const saving = ref(false);

function saveTemplate() {
    saving.value = true;
    const url = editingTemplate.value
        ? `/admin/dental/treatment-plan-templates/${editingTemplate.value.id}`
        : '/admin/dental/treatment-plan-templates';

    router.post(url, form.value, {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
        onFinish: () => { saving.value = false; },
    });
}

function toggleActive(template) {
    router.post(`/admin/dental/treatment-plan-templates/${template.id}/toggle`, {}, { preserveScroll: true });
}

function duplicateTemplate(template) {
    router.post(`/admin/dental/treatment-plan-templates/${template.id}/duplicate`, {}, { preserveScroll: true });
}

const showDeleteConfirm = ref(false);
const pendingDeleteTpl = ref(null);
const showSeedConfirm = ref(false);

function confirmDeleteTemplate(template) {
    pendingDeleteTpl.value = template;
    showDeleteConfirm.value = true;
}

function executeDeleteTemplate() {
    if (!pendingDeleteTpl.value) return;
    router.post(`/admin/dental/treatment-plan-templates/${pendingDeleteTpl.value.id}/delete`, { preserveScroll: true });
    showDeleteConfirm.value = false;
    pendingDeleteTpl.value = null;
}

function seedDefaults() {
    showSeedConfirm.value = true;
}

function executeSeedDefaults() {
    router.post('/admin/dental/treatment-plan-templates-seed', {}, { preserveScroll: true });
    showSeedConfirm.value = false;
}

function useTemplate(template) {
    // Navigate to create page with template pre-loaded
    window.location.href = `/admin/dental/treatment-plans/create?template_id=${template.id}`;
}
</script>

<template>
<AdminLayout :title="isRtl ? 'قوالب خطط العلاج' : 'Treatment Plan Templates'">
<div class="space-y-5">
    <!-- ═══ HERO ═══ -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-7 dental-hero-enter">
        <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-[#2C4E7A]/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-slate-300/15 rounded-full blur-3xl"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-white">{{ isRtl ? 'قوالب خطط العلاج' : 'Treatment Plan Templates' }}</h1>
                    <p class="text-slate-100/80 text-sm mt-0.5">{{ isRtl ? 'قوالب جاهزة لتوفير الوقت عند إنشاء خطط العلاج' : 'Ready-made templates to save time creating treatment plans' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <Link href="/admin/dental/treatment-plans" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                    {{ isRtl ? 'خطط العلاج' : 'Plans' }}
                </Link>
                <button @click="seedDefaults" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    {{ isRtl ? 'القوالب الافتراضية' : 'Reset Defaults' }}
                </button>
                <button @click="openCreate" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-[#C4A265] hover:bg-[#D4B57E] shadow-lg hover:shadow-xl transition-all backdrop-blur-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'قالب جديد' : 'New Template' }}
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ CATEGORY FILTER ═══ -->
    <div class="flex flex-wrap gap-2 dental-card-enter" style="animation-delay:0.1s">
        <button @click="activeFilter = ''" class="px-4 py-2 rounded-xl text-sm font-medium border-2 transition-all" :class="!activeFilter ? 'border-[#1B365D] bg-slate-50 text-[#1B365D] shadow-sm' : 'border-gray-100 text-gray-500 hover:border-gray-200 bg-white'">
            {{ isRtl ? 'الكل' : 'All' }} ({{ templates.length }})
        </button>
        <button v-for="cat in categories" :key="cat" @click="activeFilter = activeFilter === cat ? '' : cat" v-show="categoryCounts[cat]" class="px-4 py-2 rounded-xl text-sm font-medium border-2 transition-all" :class="activeFilter === cat ? categoryColors[cat].border + ' ' + categoryColors[cat].bg + ' ' + categoryColors[cat].text + ' shadow-sm' : 'border-gray-100 text-gray-500 hover:border-gray-200 bg-white'">
            <span class="me-1">{{ categoryIcons[cat] }}</span>
            {{ categoryLabels[cat] }} ({{ categoryCounts[cat] || 0 }})
        </button>
    </div>

    <!-- ═══ TEMPLATE CARDS ═══ -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-for="(template, ti) in filteredTemplates" :key="template.id"
            class="group bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-lg hover:border-gray-200/80 transition-all duration-300 overflow-hidden dental-card-enter"
            :class="[!template.is_active ? 'opacity-60' : '']"
            :style="{ animationDelay: `${0.15 + ti * 0.04}s` }">
            <!-- Category Color Bar -->
            <div class="h-1" :style="{ background: categoryColors[template.category]?.accent || '#6B7280' }"></div>

            <div class="p-5">
                <!-- Header -->
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">{{ categoryIcons[template.category] }}</span>
                        <div>
                            <h3 class="font-bold text-gray-900 text-sm leading-tight">{{ isRtl ? template.name_ar : template.name_en }}</h3>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-md mt-0.5 inline-block" :class="categoryColors[template.category]?.bg + ' ' + categoryColors[template.category]?.text">{{ categoryLabels[template.category] }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openEdit(template)" class="p-1.5 text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 rounded-lg transition-all" :title="isRtl ? 'تعديل' : 'Edit'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </button>
                        <button @click="duplicateTemplate(template)" class="p-1.5 text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 rounded-lg transition-all" :title="isRtl ? 'نسخ' : 'Duplicate'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        </button>
                        <button @click="toggleActive(template)" class="p-1.5 rounded-lg transition-all" :class="template.is_active ? 'text-emerald-500 hover:text-red-500 hover:bg-red-50' : 'text-gray-300 hover:text-emerald-500 hover:bg-emerald-50'" :title="template.is_active ? (isRtl ? 'تعطيل' : 'Deactivate') : (isRtl ? 'تفعيل' : 'Activate')">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path v-if="template.is_active" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" /><path v-else d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z" /></svg>
                        </button>
                        <button @click="confirmDeleteTemplate(template)" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" :title="isRtl ? 'حذف' : 'Delete'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Description -->
                <p v-if="isRtl ? template.description_ar : template.description_en" class="text-xs text-gray-500 mb-3 line-clamp-2">{{ isRtl ? template.description_ar : template.description_en }}</p>

                <!-- Treatments List -->
                <div class="space-y-1.5 mb-4">
                    <div v-for="(tr, tri) in (template.treatments || []).slice(0, 5)" :key="tri" class="flex items-center gap-2 text-xs">
                        <span class="w-4 h-4 rounded flex items-center justify-center text-[9px] font-bold flex-shrink-0" :class="categoryColors[template.category]?.bg + ' ' + categoryColors[template.category]?.text">{{ tri + 1 }}</span>
                        <span class="font-medium text-gray-700">{{ treatmentTypeLabels[tr.treatment_type] || tr.treatment_type }}</span>
                        <span v-if="tr.tooth_number" class="text-[10px] text-gray-400 font-mono">#{{ tr.tooth_number }}</span>
                        <span class="ms-auto text-[10px] font-semibold text-gray-400 tabular-nums">{{ formatCurrency((tr.cost || 0) + (tr.lab_cost || 0)) }}</span>
                    </div>
                    <div v-if="(template.treatments || []).length > 5" class="text-[10px] text-gray-400 ps-6">+{{ template.treatments.length - 5 }} {{ isRtl ? 'إجراء آخر' : 'more' }}</div>
                </div>

                <!-- Footer Stats -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] text-gray-400">
                            <span class="font-bold text-gray-600">{{ (template.treatments || []).length }}</span> {{ isRtl ? 'إجراء' : 'procedures' }}
                        </span>
                        <span class="text-[10px] text-gray-400">
                            <span class="font-bold text-gray-600">{{ template.estimated_sessions }}</span> {{ isRtl ? 'جلسة' : 'sessions' }}
                        </span>
                        <span v-if="template.usage_count > 0" class="text-[10px] text-[#1B365D]">
                            {{ isRtl ? 'استخدم' : 'Used' }} {{ template.usage_count }}x
                        </span>
                    </div>
                    <span class="text-sm font-bold" :style="{ color: categoryColors[template.category]?.accent }">{{ formatCurrency(template.estimated_cost) }}</span>
                </div>

                <!-- Use Template Button -->
                <button @click="useTemplate(template)" class="mt-3 w-full py-2.5 text-sm font-semibold rounded-xl border-2 transition-all" :class="categoryColors[template.category]?.border + ' ' + categoryColors[template.category]?.text + ' hover:' + categoryColors[template.category]?.bg">
                    {{ isRtl ? 'استخدام هذا القالب' : 'Use This Template' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div v-if="filteredTemplates.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-12 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-50 flex items-center justify-center">
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-600">{{ isRtl ? 'لا يوجد قوالب' : 'No Templates' }}</h3>
        <p class="text-sm text-gray-400 mt-1">{{ isRtl ? 'لا يوجد قوالب تطابق الفلتر المحدد' : 'No templates match the selected filter' }}</p>
    </div>
</div>

<!-- ═══ CREATE/EDIT MODAL ═══ -->
<Teleport to="body">
<Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
<div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-center p-4 overflow-y-auto" @click.self="showModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl my-6 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-[#1B365D] to-[#1B365D] px-6 py-5 flex items-center justify-between">
            <div>
                <h3 class="text-white font-bold text-lg">{{ editingTemplate ? (isRtl ? 'تعديل القالب' : 'Edit Template') : (isRtl ? 'قالب جديد' : 'New Template') }}</h3>
                <p class="text-slate-100 text-xs mt-0.5">{{ isRtl ? 'أنشئ قالب خطة علاج قابل لإعادة الاستخدام' : 'Create a reusable treatment plan template' }}</p>
            </div>
            <button @click="showModal=false" class="text-slate-200 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>

        <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
            <!-- Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الاسم بالعربي' : 'Name (Arabic)' }} *</label>
                    <input v-model="form.name_ar" type="text" dir="rtl" class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الاسم بالإنجليزي' : 'Name (English)' }} *</label>
                    <input v-model="form.name_en" type="text" class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" />
                </div>
            </div>

            <!-- Category & Priority & Sessions -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الفئة' : 'Category' }} *</label>
                    <select v-model="form.category" class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all">
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ categoryIcons[cat] }} {{ categoryLabels[cat] }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الأولوية' : 'Priority' }}</label>
                    <select v-model="form.priority" class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all">
                        <option v-for="(label, val) in priorityLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'عدد الجلسات' : 'Sessions' }}</label>
                    <input v-model="form.estimated_sessions" type="number" min="1" class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" />
                </div>
            </div>

            <!-- Description -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الوصف بالعربي' : 'Description (AR)' }}</label>
                    <textarea v-model="form.description_ar" rows="2" dir="rtl" class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الوصف بالإنجليزي' : 'Description (EN)' }}</label>
                    <textarea v-model="form.description_en" rows="2" class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all resize-none"></textarea>
                </div>
            </div>

            <!-- Treatments -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-semibold text-gray-700">{{ isRtl ? 'الإجراءات' : 'Procedures' }} *</label>
                    <button type="button" @click="addTreatment" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'إضافة' : 'Add' }}
                    </button>
                </div>

                <div class="space-y-3">
                    <div v-for="(tr, ti) in form.treatments" :key="ti" class="bg-gray-50/50 rounded-xl p-4 border border-gray-100 relative group">
                        <button type="button" @click="removeTreatment(ti)" class="absolute top-2 end-2 p-1 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        <div class="text-[10px] font-bold text-gray-400 mb-2">{{ isRtl ? 'إجراء' : 'Step' }} #{{ ti + 1 }}</div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="col-span-2 sm:col-span-1">
                                <select v-model="tr.treatment_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all">
                                    <option value="">{{ isRtl ? 'نوع العلاج' : 'Type' }}</option>
                                    <option v-for="tt in treatmentTypes" :key="tt" :value="tt">{{ treatmentTypeLabels[tt] || tt }}</option>
                                </select>
                            </div>
                            <div>
                                <input v-model="tr.tooth_number" type="number" min="11" max="85" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" :placeholder="isRtl ? 'رقم السن' : 'Tooth #'" />
                            </div>
                            <div>
                                <input v-model="tr.cost" type="number" min="0" step="0.01" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" :placeholder="isRtl ? 'التكلفة' : 'Cost'" />
                            </div>
                            <div>
                                <input v-model="tr.lab_cost" type="number" min="0" step="0.01" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" :placeholder="isRtl ? 'تكلفة المعمل' : 'Lab Cost'" />
                            </div>
                        </div>
                        <div class="mt-2">
                            <input v-model="tr.description" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" :placeholder="isRtl ? 'وصف الإجراء...' : 'Procedure description...'" />
                        </div>
                    </div>
                </div>

                <!-- Total Cost -->
                <div v-if="form.treatments.length > 0" class="flex items-center justify-end mt-3 pt-3 border-t border-gray-100">
                    <span class="text-sm text-gray-500">{{ isRtl ? 'إجمالي التكلفة:' : 'Total Cost:' }}</span>
                    <span class="text-lg font-bold text-[#1B365D] ms-2">{{ formatCurrency(formTotalCost) }}</span>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                <textarea v-model="form.notes" rows="2" class="w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all resize-none" :placeholder="isRtl ? 'ملاحظات إضافية...' : 'Additional notes...'"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t flex gap-3 justify-end">
            <button @click="showModal=false" class="px-5 py-2.5 border-2 border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-white transition-all">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
            <button @click="saveTemplate" :disabled="saving" class="px-6 py-2.5 bg-gradient-to-r from-[#1B365D] to-[#1B365D] text-white rounded-xl text-sm font-semibold hover:from-[#1B365D] hover:to-[#1B365D] disabled:opacity-50 transition-all shadow-lg shadow-[#1B365D]/20 flex items-center gap-2">
                <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                {{ editingTemplate ? (isRtl ? 'تحديث' : 'Update') : (isRtl ? 'إنشاء' : 'Create') }}
            </button>
        </div>
    </div>
</div>
</Transition>
</Teleport>

<!-- Delete Template Confirm Modal -->
<ConfirmModal
    :show="showDeleteConfirm"
    :title="isRtl ? 'حذف القالب' : 'Delete Template'"
    :message="isRtl ? 'هل أنت متأكد من حذف هذا القالب؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this template? This action cannot be undone.'"
    :confirmText="isRtl ? 'حذف' : 'Delete'"
    :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
    confirmColor="red"
    @confirm="executeDeleteTemplate"
    @cancel="showDeleteConfirm = false"
/>

<!-- Reset Defaults Confirm Modal -->
<ConfirmModal
    :show="showSeedConfirm"
    :title="isRtl ? 'إعادة تحميل القوالب' : 'Reload Default Templates'"
    :message="isRtl ? 'سيتم إعادة تحميل القوالب الافتراضية. هل أنت متأكد؟' : 'This will reload the default templates. Are you sure?'"
    :confirmText="isRtl ? 'إعادة تحميل' : 'Reload'"
    :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
    confirmColor="amber"
    @confirm="executeSeedDefaults"
    @cancel="showSeedConfirm = false"
/>
</AdminLayout>
</template>

<style>
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
