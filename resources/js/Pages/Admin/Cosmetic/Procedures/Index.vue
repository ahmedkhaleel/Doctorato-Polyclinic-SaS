<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ procedures: Object, filters: Object, categories: Array });

const search = ref(props.filters?.search || '');
const cat = ref(props.filters?.category || '');
const active = ref(props.filters?.active ?? '');
let tm = null;
function apply() {
    clearTimeout(tm);
    tm = setTimeout(() => router.get('/admin/cosmetic/procedures', {
        search: search.value || undefined, category: cat.value || undefined, active: active.value === '' ? undefined : active.value,
    }, { preserveState: true, preserveScroll: true }), 400);
}
watch([search, cat, active], apply);

const showModal = ref(false);
const editing = ref(null);
const form = useForm({
    name_ar: '', name_en: '', category: 'other', description: '',
    default_price: 0, default_duration_minutes: 30, recovery_days: 0,
    is_active: true, display_order: 0,
});
function open(p = null) {
    editing.value = p;
    form.reset();
    if (p) Object.keys(form.data()).forEach(k => form[k] = p[k] ?? form[k]);
    showModal.value = true;
}
function submit() {
    const url = editing.value ? `/admin/cosmetic/procedures/${editing.value.id}` : '/admin/cosmetic/procedures';
    form.post(url, { preserveScroll: true, onSuccess: () => { showModal.value = false; router.reload({ only: ['procedures'] }); } });
}
function remove(p) {
    if (!confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?')) return;
    router.delete(`/admin/cosmetic/procedures/${p.id}`, { preserveScroll: true });
}
function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-[#1B365D] to-[#C4A265] rounded-2xl p-6 shadow-lg flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">{{ t('Procedures', 'الإجراءات') }}</h1>
            <button @click="open()" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold ring-1 ring-white/30">+ {{ t('Add procedure', 'إضافة إجراء') }}</button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search…', 'بحث…')" class="flex-1 min-w-[220px] px-4 py-2.5 border rounded-xl text-sm" />
            <select v-model="cat" class="px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All categories', 'كل التصنيفات') }}</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <select v-model="active" class="px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All', 'الكل') }}</option>
                <option value="1">{{ t('Active', 'نشط') }}</option>
                <option value="0">{{ t('Inactive', 'معطل') }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Name', 'الاسم') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden md:table-cell">{{ t('Category', 'التصنيف') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Price', 'السعر') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Duration', 'المدة') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Active', 'نشط') }}</th>
                        <th class="text-end px-5 py-3 font-semibold text-gray-500">{{ t('Actions', 'إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in procedures.data" :key="p.id" class="border-t hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium">{{ isRtl ? p.name_ar : (p.name_en || p.name_ar) }}</td>
                        <td class="px-5 py-3 hidden md:table-cell capitalize">{{ p.category }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ p.default_price }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ p.default_duration_minutes }} min</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ p.is_active ? '✓' : '✗' }}</td>
                        <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                            <button @click="open(p)" class="text-[#1B365D] text-xs font-semibold">{{ t('Edit', 'تعديل') }}</button>
                            <button @click="remove(p)" class="text-red-600 text-xs font-semibold">{{ t('Delete', 'حذف') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!procedures.data.length"><td colspan="6" class="text-center py-8 text-gray-400">{{ t('No procedures', 'لا توجد إجراءات') }}</td></tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-auto p-6">
                <h2 class="text-lg font-bold mb-4">{{ editing ? t('Edit procedure', 'تعديل الإجراء') : t('Add procedure', 'إضافة إجراء') }}</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Name (AR)', 'الاسم (عربي)') }} *</label>
                        <input v-model="form.name_ar" required class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Name (EN)', 'الاسم (إنجليزي)') }}</label>
                        <input v-model="form.name_en" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Category', 'التصنيف') }} *</label>
                        <select v-model="form.category" required class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Default Price', 'السعر الافتراضي') }}</label>
                        <input v-model.number="form.default_price" type="number" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Duration (min)', 'المدة (دقائق)') }}</label>
                        <input v-model.number="form.default_duration_minutes" type="number" min="0" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Recovery days', 'أيام التعافي') }}</label>
                        <input v-model.number="form.recovery_days" type="number" min="0" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Display order', 'الترتيب') }}</label>
                        <input v-model.number="form.display_order" type="number" min="0" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" /> {{ t('Active', 'نشط') }}
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Description', 'الوصف') }}</label>
                        <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-[#1B365D] text-white text-sm font-semibold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
