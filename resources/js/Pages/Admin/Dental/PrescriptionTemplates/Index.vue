<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

const props = defineProps({
    templates: Object,
    filters: Object,
    treatmentTypes: Array,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.treatment_type || '');
let searchTimeout = null;

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/dental/prescription-templates', {
            search: search.value || undefined,
            treatment_type: typeFilter.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
}
watch([search, typeFilter], applyFilters);

// ─── Modal State ────────────────────────────────────────────
const showModal = ref(false);
const editingTemplate = ref(null);

const form = useForm({
    name_ar: '',
    name_en: '',
    treatment_type: '',
    diagnosis_ar: '',
    diagnosis_en: '',
    notes_ar: '',
    notes_en: '',
    auto_apply: false,
    is_active: true,
    sort_order: 0,
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions_ar: '', instructions_en: '' }],
});

function openCreate() {
    editingTemplate.value = null;
    form.reset();
    form.items = [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions_ar: '', instructions_en: '' }];
    form.is_active = true;
    showModal.value = true;
}

function openEdit(tpl) {
    editingTemplate.value = tpl;
    form.name_ar = tpl.name_ar;
    form.name_en = tpl.name_en;
    form.treatment_type = tpl.treatment_type || '';
    form.diagnosis_ar = tpl.diagnosis_ar || '';
    form.diagnosis_en = tpl.diagnosis_en || '';
    form.notes_ar = tpl.notes_ar || '';
    form.notes_en = tpl.notes_en || '';
    form.auto_apply = tpl.auto_apply;
    form.is_active = tpl.is_active;
    form.sort_order = tpl.sort_order;
    // Load items - need to fetch them
    router.reload({ only: ['templates'], onSuccess: () => {
        const found = props.templates.data.find(t => t.id === tpl.id);
        if (found && found.items) {
            form.items = found.items.map(i => ({
                medication_name: i.medication_name,
                dosage: i.dosage || '',
                frequency: i.frequency || '',
                duration: i.duration || '',
                instructions_ar: i.instructions_ar || '',
                instructions_en: i.instructions_en || '',
            }));
        }
    }});
    showModal.value = true;
}

function addItem() {
    form.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions_ar: '', instructions_en: '' });
}

function removeItem(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function submitForm() {
    if (editingTemplate.value) {
        form.post(`/admin/dental/prescription-templates/${editingTemplate.value.id}`, {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post('/admin/dental/prescription-templates', {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    }
}

const showDeleteConfirm = ref(false);
const pendingDeleteTpl = ref(null);

function confirmDeleteTemplate(tpl) {
    pendingDeleteTpl.value = tpl;
    showDeleteConfirm.value = true;
}

function executeDeleteTemplate() {
    if (!pendingDeleteTpl.value) return;
    router.post(`/admin/dental/prescription-templates/${pendingDeleteTpl.value.id}/delete`, { preserveScroll: true });
    showDeleteConfirm.value = false;
    pendingDeleteTpl.value = null;
}

const treatmentTypeLabels = {
    filling: { ar: 'حشو', en: 'Filling' },
    extraction: { ar: 'خلع', en: 'Extraction' },
    root_canal: { ar: 'علاج عصب', en: 'Root Canal' },
    crown: { ar: 'تاج', en: 'Crown' },
    bridge: { ar: 'جسر', en: 'Bridge' },
    implant: { ar: 'زرع', en: 'Implant' },
    cleaning: { ar: 'تنظيف', en: 'Cleaning' },
    scaling: { ar: 'تقليح', en: 'Scaling' },
    whitening: { ar: 'تبييض', en: 'Whitening' },
    veneer: { ar: 'قشرة', en: 'Veneer' },
    orthodontic: { ar: 'تقويم', en: 'Orthodontic' },
    denture: { ar: 'طقم أسنان', en: 'Denture' },
    sealant: { ar: 'حشو وقائي', en: 'Sealant' },
    fluoride: { ar: 'فلورايد', en: 'Fluoride' },
    gum_treatment: { ar: 'علاج لثة', en: 'Gum Treatment' },
    surgical_extraction: { ar: 'خلع جراحي', en: 'Surgical Extraction' },
    bone_graft: { ar: 'ترقيع عظم', en: 'Bone Graft' },
    sinus_lift: { ar: 'رفع جيب أنفي', en: 'Sinus Lift' },
    night_guard: { ar: 'واقي ليلي', en: 'Night Guard' },
    retainer: { ar: 'مثبت', en: 'Retainer' },
};

function getTypeLabel(type) {
    if (!type) return '-';
    const label = treatmentTypeLabels[type];
    return label ? (locale.value === 'ar' ? label.ar : label.en) : type;
}

// Frequency presets
const frequencies = [
    'Once daily', 'Twice daily', 'Three times daily', 'Four times daily',
    'Every 8 hours', 'Every 12 hours', 'As needed (PRN)', 'Before meals', 'After meals', 'At bedtime',
];
const durations = [
    '3 days', '5 days', '7 days', '10 days', '14 days', '21 days', '1 month', '2 months', '3 months', 'Ongoing',
];
</script>

<template>
    <AdminLayout :title="locale === 'ar' ? 'قوالب الوصفات الطبية' : 'Prescription Templates'">
        <div class="space-y-6">
            <!-- Hero Header -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-600 via-cyan-700 to-teal-800 p-7">
                <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-cyan-400/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-teal-300/15 rounded-full blur-3xl"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">{{ locale === 'ar' ? 'قوالب وصفات الأسنان' : 'Dental Prescription Templates' }}</h1>
                            <p class="text-cyan-100/80 text-sm mt-0.5">{{ locale === 'ar' ? 'إدارة قوالب الوصفات الطبية الجاهزة لعلاجات الأسنان' : 'Manage ready-made prescription templates for dental treatments' }}</p>
                        </div>
                    </div>
                    <button @click="openCreate" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-cyan-700 bg-white/90 hover:bg-white shadow-lg hover:shadow-xl transition-all duration-300 backdrop-blur-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ locale === 'ar' ? 'قالب جديد' : 'New Template' }}
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 p-5" style="animation-delay: 0.15s">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="relative">
                        <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input v-model="search" type="text" :placeholder="locale === 'ar' ? 'بحث بالاسم...' : 'Search by name...'" class="w-full ltr:pl-11 rtl:pr-11 ltr:pr-4 rtl:pl-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-cyan-200/60 focus:border-cyan-300 transition-all duration-200" />
                    </div>
                    <select v-model="typeFilter" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-cyan-200/60 focus:border-cyan-300 transition-all duration-200">
                        <option value="">{{ locale === 'ar' ? 'جميع أنواع العلاج' : 'All Treatment Types' }}</option>
                        <option v-for="tt in treatmentTypes" :key="tt" :value="tt">{{ getTypeLabel(tt) }}</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.25s">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ locale === 'ar' ? 'الاسم' : 'Name' }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ locale === 'ar' ? 'نوع العلاج' : 'Treatment Type' }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ locale === 'ar' ? 'الأدوية' : 'Medications' }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ locale === 'ar' ? 'تطبيق تلقائي' : 'Auto Apply' }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ locale === 'ar' ? 'الحالة' : 'Status' }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ locale === 'ar' ? 'إجراءات' : 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="tpl in templates.data" :key="tpl.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900">{{ locale === 'ar' ? tpl.name_ar : tpl.name_en }}</div>
                                    <div class="text-xs text-gray-400">{{ locale === 'ar' ? tpl.name_en : tpl.name_ar }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span v-if="tpl.treatment_type" class="px-2 py-1 bg-cyan-50 text-cyan-700 rounded-full text-xs font-medium">{{ getTypeLabel(tpl.treatment_type) }}</span>
                                    <span v-else class="text-gray-400 text-xs">-</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 bg-gray-100 rounded-full text-xs font-semibold text-gray-700">{{ tpl.items_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="tpl.auto_apply" class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">{{ locale === 'ar' ? 'تلقائي' : 'Auto' }}</span>
                                    <span v-else class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-medium">{{ locale === 'ar' ? 'يدوي' : 'Manual' }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="tpl.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ tpl.is_active ? (locale === 'ar' ? 'مفعّل' : 'Active') : (locale === 'ar' ? 'معطّل' : 'Inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openEdit(tpl)" class="text-cyan-600 hover:text-cyan-800 text-sm font-medium">
                                            {{ locale === 'ar' ? 'تعديل' : 'Edit' }}
                                        </button>
                                        <button @click="confirmDeleteTemplate(tpl)" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                            {{ locale === 'ar' ? 'حذف' : 'Delete' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!templates.data || templates.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">{{ locale === 'ar' ? 'لا توجد قوالب' : 'No templates found' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="templates.links && templates.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ templates.from }} {{ $t('a_to') }} {{ templates.to }} {{ $t('a_of') }} {{ templates.total }} {{ $t('a_results') }}</p>
                    <nav class="flex ltr:space-x-1 rtl:space-x-reverse rtl:space-x-1">
                        <template v-for="link in templates.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 text-sm rounded border transition" :class="link.active ? 'bg-cyan-600 text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'" preserve-state />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- ─── Create/Edit Modal ──────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-start justify-center pt-10 px-4 overflow-y-auto">
                <div class="fixed inset-0 bg-black/50" @click="showModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-3xl mb-10" @click.stop>
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b">
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ editingTemplate ? (locale === 'ar' ? 'تعديل القالب' : 'Edit Template') : (locale === 'ar' ? 'قالب جديد' : 'New Template') }}
                        </h2>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                        <!-- Template Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ locale === 'ar' ? 'الاسم (عربي)' : 'Name (Arabic)' }} *</label>
                                <input v-model="form.name_ar" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ locale === 'ar' ? 'الاسم (إنجليزي)' : 'Name (English)' }} *</label>
                                <input v-model="form.name_en" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ locale === 'ar' ? 'نوع العلاج' : 'Treatment Type' }}</label>
                                <select v-model="form.treatment_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent">
                                    <option value="">{{ locale === 'ar' ? 'عام' : 'General' }}</option>
                                    <option v-for="tt in treatmentTypes" :key="tt" :value="tt">{{ getTypeLabel(tt) }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ locale === 'ar' ? 'الترتيب' : 'Sort Order' }}</label>
                                <input v-model="form.sort_order" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            </div>
                            <div class="flex items-end gap-6 pb-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.auto_apply" type="checkbox" class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500" />
                                    <span class="text-sm text-gray-700">{{ locale === 'ar' ? 'تطبيق تلقائي' : 'Auto Apply' }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500" />
                                    <span class="text-sm text-gray-700">{{ locale === 'ar' ? 'مفعّل' : 'Active' }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ locale === 'ar' ? 'التشخيص (عربي)' : 'Diagnosis (Arabic)' }}</label>
                                <input v-model="form.diagnosis_ar" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ locale === 'ar' ? 'التشخيص (إنجليزي)' : 'Diagnosis (English)' }}</label>
                                <input v-model="form.diagnosis_en" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ locale === 'ar' ? 'ملاحظات (عربي)' : 'Notes (Arabic)' }}</label>
                                <textarea v-model="form.notes_ar" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ locale === 'ar' ? 'ملاحظات (إنجليزي)' : 'Notes (English)' }}</label>
                                <textarea v-model="form.notes_en" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            </div>
                        </div>

                        <!-- Medications -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-800">{{ locale === 'ar' ? 'الأدوية' : 'Medications' }} *</h3>
                                <button type="button" @click="addItem" class="text-sm text-cyan-600 hover:text-cyan-800 font-medium">
                                    + {{ locale === 'ar' ? 'إضافة دواء' : 'Add Medication' }}
                                </button>
                            </div>

                            <div v-for="(item, idx) in form.items" :key="idx" class="border border-gray-200 rounded-xl p-4 mb-3 bg-gray-50/50">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold text-gray-500">{{ locale === 'ar' ? 'الدواء' : 'Medication' }} #{{ idx + 1 }}</span>
                                    <button v-if="form.items.length > 1" type="button" @click="removeItem(idx)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="sm:col-span-2">
                                        <input v-model="item.medication_name" type="text" required :placeholder="locale === 'ar' ? 'اسم الدواء *' : 'Medication Name *'" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                                    </div>
                                    <div>
                                        <input v-model="item.dosage" type="text" :placeholder="locale === 'ar' ? 'الجرعة' : 'Dosage'" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                                    </div>
                                    <div>
                                        <select v-model="item.frequency" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent">
                                            <option value="">{{ locale === 'ar' ? 'التكرار' : 'Frequency' }}</option>
                                            <option v-for="f in frequencies" :key="f" :value="f">{{ f }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <select v-model="item.duration" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent">
                                            <option value="">{{ locale === 'ar' ? 'المدة' : 'Duration' }}</option>
                                            <option v-for="d in durations" :key="d" :value="d">{{ d }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <input v-model="item.instructions_ar" type="text" :placeholder="locale === 'ar' ? 'تعليمات (عربي)' : 'Instructions (AR)'" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Errors -->
                        <div v-if="form.errors && Object.keys(form.errors).length > 0" class="bg-red-50 text-red-600 text-sm rounded-lg p-3">
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>
                    </form>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 p-6 border-t">
                        <button @click="showModal = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
                        </button>
                        <button @click="submitForm" :disabled="form.processing" class="px-6 py-2 text-sm font-medium text-white bg-cyan-600 rounded-lg hover:bg-cyan-700 transition disabled:opacity-50">
                            {{ form.processing ? (locale === 'ar' ? 'جاري الحفظ...' : 'Saving...') : (locale === 'ar' ? 'حفظ' : 'Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <ConfirmModal
            :show="showDeleteConfirm"
            :title="locale === 'ar' ? 'حذف القالب' : 'Delete Template'"
            :message="locale === 'ar' ? 'هل أنت متأكد من حذف هذا القالب؟' : 'Are you sure you want to delete this template?'"
            :confirmText="locale === 'ar' ? 'حذف' : 'Delete'"
            :cancelText="locale === 'ar' ? 'إلغاء' : 'Cancel'"
            confirmColor="red"
            @confirm="executeDeleteTemplate"
            @cancel="showDeleteConfirm = false"
        />
    </AdminLayout>
</template>

<style scoped>
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
