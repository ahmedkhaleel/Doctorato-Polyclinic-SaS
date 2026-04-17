<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    conditions: Object, filters: Object,
    categories: Array, severities: Array, statuses: Array, patients: Array,
});

const search = ref(props.filters?.search || '');
const category = ref(props.filters?.category || '');
const status = ref(props.filters?.status || '');
let tm = null;
function apply() {
    clearTimeout(tm);
    tm = setTimeout(() => router.get('/admin/derma/conditions', {
        search: search.value || undefined, category: category.value || undefined, status: status.value || undefined,
    }, { preserveState: true, preserveScroll: true }), 400);
}
watch([search, category, status], apply);

const showModal = ref(false);
const editing = ref(null);
const form = useForm({
    patient_id: '', visit_id: null, doctor_id: null,
    name_ar: '', name_en: '', category: 'other', severity: 'mild',
    body_area: '', diagnosed_at: '', status: 'active', notes: '',
});
function open(c = null) {
    editing.value = c;
    if (c) form.defaults({ ...c, diagnosed_at: c.diagnosed_at?.substring(0, 10) || '' });
    else form.reset();
    form.reset();
    if (c) Object.keys(form.data()).forEach(k => form[k] = c[k] ?? form[k]);
    showModal.value = true;
}
function submit() {
    const url = editing.value ? `/admin/derma/conditions/${editing.value.id}` : '/admin/derma/conditions';
    form.post(url, { preserveScroll: true, onSuccess: () => { showModal.value = false; router.reload({ only: ['conditions'] }); } });
}
function remove(c) {
    if (!confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?')) return;
    router.delete(`/admin/derma/conditions/${c.id}`, { preserveScroll: true });
}

function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-amber-600 to-amber-500 rounded-2xl p-6 shadow-lg flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">{{ t('Skin Conditions', 'الحالات الجلدية') }}</h1>
                <p class="text-amber-100/80 text-sm mt-1">{{ t('Manage diagnosed skin conditions', 'إدارة الحالات الجلدية المشخصة') }}</p>
            </div>
            <button @click="open()" class="px-4 py-2 bg-white/15 hover:bg-white/25 backdrop-blur text-white rounded-xl text-sm font-semibold ring-1 ring-white/30">
                + {{ t('Add condition', 'إضافة حالة') }}
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search…', 'بحث…')" class="flex-1 min-w-[200px] px-4 py-2.5 border rounded-xl text-sm" />
            <select v-model="category" class="px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All categories', 'كل التصنيفات') }}</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <select v-model="status" class="px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All statuses', 'كل الحالات') }}</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Patient', 'المريض') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Condition', 'الحالة') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden md:table-cell">{{ t('Category', 'التصنيف') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Severity', 'الشدة') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Status', 'الحالة') }}</th>
                        <th class="text-end px-5 py-3 font-semibold text-gray-500">{{ t('Actions', 'إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in conditions.data" :key="c.id" class="border-t hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ c.patient?.full_name || '-' }}</td>
                        <td class="px-5 py-3">{{ isRtl ? c.name_ar : (c.name_en || c.name_ar) }}</td>
                        <td class="px-5 py-3 text-gray-600 hidden md:table-cell capitalize">{{ c.category }}</td>
                        <td class="px-5 py-3 text-gray-600 hidden lg:table-cell capitalize">{{ c.severity }}</td>
                        <td class="px-5 py-3 capitalize">{{ c.status }}</td>
                        <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                            <button @click="open(c)" class="text-amber-600 text-xs font-semibold">{{ t('Edit', 'تعديل') }}</button>
                            <button @click="remove(c)" class="text-red-600 text-xs font-semibold">{{ t('Delete', 'حذف') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!conditions.data.length"><td colspan="6" class="text-center py-8 text-gray-400">{{ t('No data', 'لا توجد بيانات') }}</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-auto p-6">
                <h2 class="text-lg font-bold mb-4">{{ editing ? t('Edit condition', 'تعديل الحالة') : t('Add condition', 'إضافة حالة') }}</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Patient', 'المريض') }} *</label>
                        <select v-model="form.patient_id" required class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }} ({{ p.phone }})</option>
                        </select>
                    </div>
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
                        <label class="block text-xs font-medium mb-1">{{ t('Severity', 'الشدة') }} *</label>
                        <select v-model="form.severity" required class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option v-for="s in severities" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Body Area', 'منطقة الجسم') }}</label>
                        <input v-model="form.body_area" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Diagnosed at', 'تاريخ التشخيص') }}</label>
                        <input v-model="form.diagnosed_at" type="date" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Status', 'الحالة') }} *</label>
                        <select v-model="form.status" required class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Notes', 'ملاحظات') }}</label>
                        <textarea v-model="form.notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
