<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#0D9488';
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({
    exercises: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const showForm = ref(false);
const editing = ref(null);
const form = useForm({ name_ar: '', name_en: '', region: 'general', category: 'strength', default_sets: 3, default_reps: 10, default_hold_sec: null, instructions: '' });

function startNew() {
    editing.value = null;
    form.reset();
    showForm.value = true;
}
function startEdit(ex) {
    editing.value = ex.id;
    form.name_ar = ex.name_ar; form.name_en = ex.name_en; form.region = ex.region || 'general';
    form.category = ex.category || ''; form.default_sets = ex.default_sets; form.default_reps = ex.default_reps;
    form.default_hold_sec = ex.default_hold_sec; form.instructions = ex.instructions || '';
    showForm.value = true;
}
function submit() {
    const url = editing.value ? `/admin/physiotherapy/exercises/${editing.value}` : '/admin/physiotherapy/exercises';
    form.post(url, { preserveScroll: true, onSuccess: () => { showForm.value = false; form.reset(); editing.value = null; } });
}
function toggle(ex) {
    router.post(`/admin/physiotherapy/exercises/${ex.id}/toggle`, {}, { preserveScroll: true });
}
function filterRegion(r) {
    router.get('/admin/physiotherapy/exercises', { region: r || undefined }, { preserveState: true, replace: true });
}
const exName = (ex) => (isRtl.value ? ex.name_ar : ex.name_en);
</script>

<template>
    <AdminLayout>
        <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h1 class="text-xl font-bold text-gray-800">{{ t('Exercise Catalog', 'دليل التمارين') }}</h1>
                <button @click="startNew" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">+ {{ t('New Exercise', 'تمرين جديد') }}</button>
            </div>

            <div class="flex flex-wrap gap-1">
                <button @click="filterRegion(null)" class="px-3 py-1 rounded-lg text-xs font-medium" :class="!filters.region ? 'text-white' : 'bg-gray-100 text-gray-600'" :style="!filters.region ? { backgroundColor: ACCENT } : {}">{{ t('All', 'الكل') }}</button>
                <button v-for="r in regions" :key="r" @click="filterRegion(r)" class="px-3 py-1 rounded-lg text-xs font-medium" :class="filters.region === r ? 'text-white' : 'bg-gray-100 text-gray-600'" :style="filters.region === r ? { backgroundColor: ACCENT } : {}">{{ r }}</button>
            </div>

            <!-- Form -->
            <form v-if="showForm" @submit.prevent="submit" class="bg-white rounded-2xl p-5 shadow-sm border border-teal-100 space-y-3">
                <h3 class="font-semibold text-gray-800">{{ editing ? t('Edit Exercise', 'تعديل التمرين') : t('New Exercise', 'تمرين جديد') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input v-model="form.name_en" :placeholder="t('Name (EN)', 'الاسم (EN)')" class="form-in" required />
                    <input v-model="form.name_ar" :placeholder="t('Name (AR)', 'الاسم (AR)')" class="form-in" required />
                    <select v-model="form.region" class="form-in"><option v-for="r in regions" :key="r" :value="r">{{ r }}</option></select>
                    <input v-model="form.category" :placeholder="t('Category', 'الفئة')" class="form-in" />
                    <input v-model.number="form.default_sets" type="number" min="0" :placeholder="t('Default sets', 'مجموعات افتراضية')" class="form-in" />
                    <input v-model.number="form.default_reps" type="number" min="0" :placeholder="t('Default reps', 'تكرارات افتراضية')" class="form-in" />
                    <input v-model.number="form.default_hold_sec" type="number" min="0" :placeholder="t('Hold sec', 'ثبات ث')" class="form-in" />
                </div>
                <textarea v-model="form.instructions" :placeholder="t('Instructions', 'التعليمات')" rows="2" class="form-in w-full"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showForm = false" class="px-4 py-2 text-sm text-gray-500">{{ t('Cancel', 'إلغاء') }}</button>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">{{ t('Save', 'حفظ') }}</button>
                </div>
            </form>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-start font-medium px-5 py-3">{{ t('Exercise', 'التمرين') }}</th>
                            <th class="text-start font-medium px-5 py-3">{{ t('Region', 'المنطقة') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Default', 'الافتراضي') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Active', 'مُفعّل') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ex in exercises" :key="ex.id" class="border-t border-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ exName(ex) }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ ex.region }}</td>
                            <td class="px-5 py-3 text-center text-gray-500 text-xs">{{ ex.default_sets }}×{{ ex.default_reps }}<span v-if="ex.default_hold_sec"> · {{ ex.default_hold_sec }}s</span></td>
                            <td class="px-5 py-3 text-center">
                                <button @click="toggle(ex)" :aria-label="ex.is_active ? t('Deactivate', 'تعطيل') : t('Activate', 'تفعيل')"
                                    class="w-10 h-6 rounded-full transition relative" :class="ex.is_active ? 'bg-teal-500' : 'bg-gray-200'">
                                    <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white transition-all" :class="ex.is_active ? 'start-4' : 'start-0.5'"></span>
                                </button>
                            </td>
                            <td class="px-5 py-3 text-end">
                                <button @click="startEdit(ex)" class="text-teal-600 hover:text-teal-700 text-sm font-medium">{{ t('Edit', 'تعديل') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!exercises.length"><td colspan="5" class="px-5 py-12 text-center text-gray-400">{{ t('No exercises', 'لا توجد تمارين') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.form-in {
    padding: 0.5rem 0.75rem;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    font-size: 0.875rem;
    background: #fff;
    outline: none;
}
.form-in:focus { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,0.25); }
</style>
