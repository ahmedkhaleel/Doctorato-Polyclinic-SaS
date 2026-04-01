<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    patient: Object,
    patients: Array,
    doctors: Array,
    categories: Array,
    treatmentPlans: Array,
});

const categoryLabels = {
    orthodontic: { ar: 'تقويم', en: 'Orthodontic' },
    cosmetic: { ar: 'تجميلي', en: 'Cosmetic' },
    implant: { ar: 'زراعة', en: 'Implant' },
    whitening: { ar: 'تبييض', en: 'Whitening' },
    restoration: { ar: 'ترميم', en: 'Restoration' },
    surgical: { ar: 'جراحي', en: 'Surgical' },
    xray: { ar: 'أشعة', en: 'X-ray' },
    other: { ar: 'أخرى', en: 'Other' },
};

function categoryLabel(cat) {
    const l = categoryLabels[cat];
    return l ? (isRtl.value ? l.ar : l.en) : cat;
}

const form = useForm({
    patient_id: props.patient?.id || '',
    doctor_id: '',
    treatment_plan_id: '',
    title_ar: '',
    title_en: '',
    description: '',
    category: 'other',
    before_image: null,
    before_date: '',
    before_notes: '',
    after_image: null,
    after_date: '',
    after_notes: '',
    tooth_numbers: '',
    is_visible_to_patient: true,
    is_featured: false,
});

const beforePreview = ref(null);
const afterPreview = ref(null);

function handleBeforeImage(e) {
    const file = e.target.files[0];
    if (file) {
        form.before_image = file;
        beforePreview.value = URL.createObjectURL(file);
    }
}

function handleAfterImage(e) {
    const file = e.target.files[0];
    if (file) {
        form.after_image = file;
        afterPreview.value = URL.createObjectURL(file);
    }
}

function submit() {
    form.post('/admin/dental/comparisons', {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'إضافة مقارنة' : 'Add Comparison'">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3 dental-hero-enter">
                <Link href="/admin/dental/comparisons" class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'إضافة مقارنة قبل / بعد' : 'Add Before & After Comparison' }}</h1>
                    <p class="text-sm text-gray-500">{{ isRtl ? 'رفع صور قبل وبعد العلاج' : 'Upload before and after treatment photos' }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Images Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6 dental-card-enter" style="animation-delay:0.1s">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-5">{{ isRtl ? 'الصور' : 'Images' }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Before Image -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-2 block">{{ isRtl ? 'صورة قبل العلاج *' : 'Before Image *' }}</label>
                            <div class="relative aspect-[4/3] rounded-xl border-2 border-dashed border-gray-200 hover:border-cyan-300 transition overflow-hidden bg-gray-50 cursor-pointer group"
                                @click="$refs.beforeInput.click()">
                                <img v-if="beforePreview" :src="beforePreview" class="w-full h-full object-cover" />
                                <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 group-hover:text-cyan-500 transition">
                                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="text-sm font-medium">{{ isRtl ? 'صورة قبل' : 'Before Photo' }}</span>
                                </div>
                                <div class="absolute top-2 ltr:left-2 rtl:right-2">
                                    <span class="bg-gray-800/70 text-white text-[10px] font-bold px-2.5 py-1 rounded-full backdrop-blur">{{ isRtl ? 'قبل' : 'BEFORE' }}</span>
                                </div>
                            </div>
                            <input ref="beforeInput" type="file" accept="image/*" class="hidden" @change="handleBeforeImage" />
                            <div v-if="form.errors.before_image" class="text-xs text-red-500 mt-1">{{ form.errors.before_image }}</div>

                            <div class="grid grid-cols-1 gap-3 mt-3">
                                <input v-model="form.before_date" type="date" :placeholder="isRtl ? 'تاريخ قبل' : 'Before date'"
                                    class="dental-input" />
                                <input v-model="form.before_notes" type="text" :placeholder="isRtl ? 'ملاحظات قبل العلاج...' : 'Before notes...'"
                                    class="dental-input" />
                            </div>
                        </div>

                        <!-- After Image -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-2 block">{{ isRtl ? 'صورة بعد العلاج *' : 'After Image *' }}</label>
                            <div class="relative aspect-[4/3] rounded-xl border-2 border-dashed border-gray-200 hover:border-cyan-300 transition overflow-hidden bg-gray-50 cursor-pointer group"
                                @click="$refs.afterInput.click()">
                                <img v-if="afterPreview" :src="afterPreview" class="w-full h-full object-cover" />
                                <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 group-hover:text-cyan-500 transition">
                                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="text-sm font-medium">{{ isRtl ? 'صورة بعد' : 'After Photo' }}</span>
                                </div>
                                <div class="absolute top-2 ltr:left-2 rtl:right-2">
                                    <span class="bg-emerald-600/80 text-white text-[10px] font-bold px-2.5 py-1 rounded-full backdrop-blur">{{ isRtl ? 'بعد' : 'AFTER' }}</span>
                                </div>
                            </div>
                            <input ref="afterInput" type="file" accept="image/*" class="hidden" @change="handleAfterImage" />
                            <div v-if="form.errors.after_image" class="text-xs text-red-500 mt-1">{{ form.errors.after_image }}</div>

                            <div class="grid grid-cols-1 gap-3 mt-3">
                                <input v-model="form.after_date" type="date" :placeholder="isRtl ? 'تاريخ بعد' : 'After date'"
                                    class="dental-input" />
                                <input v-model="form.after_notes" type="text" :placeholder="isRtl ? 'ملاحظات بعد العلاج...' : 'After notes...'"
                                    class="dental-input" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6 dental-card-enter" style="animation-delay:0.2s">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-5">{{ isRtl ? 'التفاصيل' : 'Details' }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'المريض *' : 'Patient *' }}</label>
                            <select v-model="form.patient_id" class="dental-select">
                                <option value="">{{ isRtl ? 'اختر المريض' : 'Select Patient' }}</option>
                                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }} ({{ p.file_number }})</option>
                            </select>
                            <div v-if="form.errors.patient_id" class="text-xs text-red-500 mt-1">{{ form.errors.patient_id }}</div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                            <select v-model="form.doctor_id" class="dental-select">
                                <option value="">{{ isRtl ? 'اختر الطبيب' : 'Select Doctor' }}</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ isRtl ? d.name_ar : d.name_en }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'التصنيف *' : 'Category *' }}</label>
                            <select v-model="form.category" class="dental-select">
                                <option v-for="cat in categories" :key="cat" :value="cat">{{ categoryLabel(cat) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'أرقام الأسنان' : 'Tooth Numbers' }}</label>
                            <input v-model="form.tooth_numbers" type="text" :placeholder="isRtl ? 'مثال: 11, 12, 21' : 'e.g. 11, 12, 21'"
                                class="dental-input" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'العنوان بالعربي' : 'Title (Arabic)' }}</label>
                            <input v-model="form.title_ar" type="text" class="dental-input" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'العنوان بالإنجليزي' : 'Title (English)' }}</label>
                            <input v-model="form.title_en" type="text" class="dental-input" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">{{ isRtl ? 'الوصف' : 'Description' }}</label>
                            <textarea v-model="form.description" rows="3" class="dental-input resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Toggles -->
                    <div class="flex items-center gap-6 mt-5 pt-5 border-t border-gray-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.is_visible_to_patient" type="checkbox" class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500" />
                            <span class="text-sm text-gray-700">{{ isRtl ? 'مرئي للمريض' : 'Visible to patient' }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.is_featured" type="checkbox" class="w-4 h-4 text-amber-500 border-gray-300 rounded focus:ring-amber-400" />
                            <span class="text-sm text-gray-700">{{ isRtl ? 'مميز' : 'Featured' }}</span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3">
                    <Link href="/admin/dental/comparisons" class="px-6 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-8 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-cyan-500 to-teal-500 rounded-xl hover:from-cyan-600 hover:to-teal-600 disabled:opacity-50 transition shadow-lg shadow-cyan-200/40">
                        {{ form.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ المقارنة' : 'Save Comparison') }}
                    </button>
                </div>
            </form>
        </div>
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

.dental-input {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    background: rgba(249, 250, 251, 0.5);
    transition: all 0.2s;
}
.dental-input:focus {
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(34, 211, 238, 0.3);
    border-color: #67e8f9;
}

.dental-select {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    background: rgba(249, 250, 251, 0.5);
    transition: all 0.2s;
}
.dental-select:focus {
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(34, 211, 238, 0.3);
    border-color: #67e8f9;
}
</style>
