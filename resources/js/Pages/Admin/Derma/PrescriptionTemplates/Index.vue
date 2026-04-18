<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ templates: Object, filters: Object, categories: Array });

const search = ref(props.filters?.search || '');
const cat = ref(props.filters?.condition_category || '');
let tm = null;
function apply() {
    clearTimeout(tm);
    tm = setTimeout(() => router.get('/admin/derma/prescription-templates', {
        search: search.value || undefined, condition_category: cat.value || undefined,
    }, { preserveState: true, preserveScroll: true }), 400);
}
watch([search, cat], apply);

const showModal = ref(false);
const editing = ref(null);
const form = useForm({
    name_ar: '', name_en: '', condition_category: '',
    diagnosis_ar: '', diagnosis_en: '',
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
    notes_ar: '', notes_en: '',
    is_active: true, sort_order: 0,
});
function open(tpl = null) {
    editing.value = tpl;
    form.reset();
    if (tpl) {
        Object.keys(form.data()).forEach(k => form[k] = tpl[k] ?? form[k]);
        form.items = tpl.items?.length ? tpl.items : form.items;
    }
    showModal.value = true;
}
function addItem() { form.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }); }
function removeItem(i) { form.items.splice(i, 1); }
function submit() {
    const url = editing.value ? `/admin/derma/prescription-templates/${editing.value.id}` : '/admin/derma/prescription-templates';
    form.post(url, { preserveScroll: true, onSuccess: () => { showModal.value = false; router.reload({ only: ['templates'] }); } });
}
function remove(tpl) {
    if (!confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?')) return;
    router.delete(`/admin/derma/prescription-templates/${tpl.id}`, { preserveScroll: true });
}
function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-amber-600 to-amber-500 rounded-2xl p-6 shadow-lg flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">{{ t('Rx Templates', 'قوالب الوصفات') }}</h1>
            <button @click="open()" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold ring-1 ring-white/30">+ {{ t('New template', 'قالب جديد') }}</button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search…', 'بحث…')" class="doctorato-input flex-1 min-w-[220px] px-4 py-2.5 border rounded-xl text-sm" />
            <select v-model="cat" class="doctorato-input px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All categories', 'كل التصنيفات') }}</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Name', 'الاسم') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden md:table-cell">{{ t('Category', 'التصنيف') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Items', 'عناصر') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Active', 'نشط') }}</th>
                        <th class="text-end px-5 py-3 font-semibold text-gray-500">{{ t('Actions', 'إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tpl in templates.data" :key="tpl.id" class="border-t hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium">{{ isRtl ? tpl.name_ar : (tpl.name_en || tpl.name_ar) }}</td>
                        <td class="px-5 py-3 hidden md:table-cell">{{ tpl.condition_category || '-' }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ tpl.items?.length || 0 }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ tpl.is_active ? '✓' : '✗' }}</td>
                        <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                            <button @click="open(tpl)" class="text-amber-600 text-xs font-semibold">{{ t('Edit', 'تعديل') }}</button>
                            <button @click="remove(tpl)" class="text-red-600 text-xs font-semibold">{{ t('Delete', 'حذف') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!templates.data.length"><td colspan="5" class="text-center py-8 text-gray-400">{{ t('No templates', 'لا توجد قوالب') }}</td></tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-auto p-6">
                <h2 class="text-lg font-bold mb-4">{{ editing ? t('Edit template', 'تعديل القالب') : t('New template', 'قالب جديد') }}</h2>
                <form @submit.prevent="submit" class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1">{{ t('Name (AR)', 'الاسم (عربي)') }} *</label>
                            <input v-model="form.name_ar" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">{{ t('Name (EN)', 'الاسم (إنجليزي)') }}</label>
                            <input v-model="form.name_en" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">{{ t('Category', 'التصنيف') }}</label>
                            <select v-model="form.condition_category" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="">—</option>
                                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">{{ t('Sort order', 'الترتيب') }}</label>
                            <input v-model.number="form.sort_order" type="number" min="0" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Diagnosis (AR)', 'التشخيص (عربي)') }}</label>
                        <textarea v-model="form.diagnosis_ar" rows="2" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold">{{ t('Medications', 'الأدوية') }}</label>
                            <button type="button" @click="addItem" class="text-xs text-amber-600 font-semibold">+ {{ t('Add', 'إضافة') }}</button>
                        </div>
                        <div v-for="(it, i) in form.items" :key="i" class="grid grid-cols-1 md:grid-cols-5 gap-2 p-2 border rounded-lg mb-2">
                            <input v-model="it.medication_name" :placeholder="t('Medication', 'الدواء')" class="doctorato-input px-2 py-1.5 border rounded text-xs" />
                            <input v-model="it.dosage" :placeholder="t('Dosage', 'الجرعة')" class="doctorato-input px-2 py-1.5 border rounded text-xs" />
                            <input v-model="it.frequency" :placeholder="t('Frequency', 'التكرار')" class="doctorato-input px-2 py-1.5 border rounded text-xs" />
                            <input v-model="it.duration" :placeholder="t('Duration', 'المدة')" class="doctorato-input px-2 py-1.5 border rounded text-xs" />
                            <div class="flex gap-1">
                                <input v-model="it.instructions" :placeholder="t('Instructions', 'تعليمات')" class="doctorato-input flex-1 px-2 py-1.5 border rounded text-xs" />
                                <button type="button" @click="removeItem(i)" class="text-red-600 text-xs px-2">✕</button>
                            </div>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" /> {{ t('Active', 'نشط') }}
                    </label>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
