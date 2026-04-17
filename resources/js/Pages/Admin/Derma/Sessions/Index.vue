<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ sessions: Object, filters: Object, types: Array, patients: Array, doctors: Array });

const search = ref(props.filters?.search || '');
const type = ref(props.filters?.type || '');
const pid = ref(props.filters?.patient_id || '');
let tm = null;
function apply() {
    clearTimeout(tm);
    tm = setTimeout(() => router.get('/admin/derma/sessions', {
        search: search.value || undefined, type: type.value || undefined, patient_id: pid.value || undefined,
    }, { preserveState: true, preserveScroll: true }), 400);
}
watch([search, type, pid], apply);

const showModal = ref(false);
const editing = ref(null);
const form = useForm({
    patient_id: '', doctor_id: '', visit_id: null,
    session_type: 'other', area_treated: '', product_used: '',
    session_number: 1, total_sessions: 1, cost: 0,
    completed_at: '', next_session_date: '', notes: '',
});
function open(s = null) {
    editing.value = s;
    form.reset();
    if (s) {
        Object.keys(form.data()).forEach(k => form[k] = s[k] ?? form[k]);
        form.completed_at = s.completed_at?.substring(0, 10) || '';
        form.next_session_date = s.next_session_date?.substring(0, 10) || '';
    }
    showModal.value = true;
}
function submit() {
    const url = editing.value ? `/admin/derma/sessions/${editing.value.id}` : '/admin/derma/sessions';
    form.post(url, { preserveScroll: true, onSuccess: () => { showModal.value = false; router.reload({ only: ['sessions'] }); } });
}
function remove(s) {
    if (!confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?')) return;
    router.delete(`/admin/derma/sessions/${s.id}`, { preserveScroll: true });
}

function t(en, ar) { return isRtl.value ? ar : en; }
function fmt(d) { if (!d) return '-'; return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB'); }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-pink-600 to-rose-500 rounded-2xl p-6 shadow-lg flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">{{ t('Treatment Sessions', 'جلسات العلاج') }}</h1>
            <button @click="open()" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold ring-1 ring-white/30">+ {{ t('New session', 'جلسة جديدة') }}</button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search patient…', 'بحث عن مريض…')" class="flex-1 min-w-[200px] px-4 py-2.5 border rounded-xl text-sm" />
            <select v-model="type" class="px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All types', 'كل الأنواع') }}</option>
                <option v-for="tt in types" :key="tt" :value="tt">{{ tt }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Patient', 'المريض') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Type', 'النوع') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden md:table-cell">{{ t('Area', 'المنطقة') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Session', 'رقم الجلسة') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Cost', 'التكلفة') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden md:table-cell">{{ t('Completed', 'تاريخ الإنجاز') }}</th>
                        <th class="text-end px-5 py-3 font-semibold text-gray-500">{{ t('Actions', 'إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in sessions.data" :key="s.id" class="border-t hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium">{{ s.patient?.full_name || '-' }}</td>
                        <td class="px-5 py-3 capitalize">{{ s.session_type }}</td>
                        <td class="px-5 py-3 hidden md:table-cell">{{ s.area_treated || '-' }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ s.session_number }}/{{ s.total_sessions }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ s.cost }}</td>
                        <td class="px-5 py-3 hidden md:table-cell">{{ fmt(s.completed_at) }}</td>
                        <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                            <button @click="open(s)" class="text-pink-600 text-xs font-semibold">{{ t('Edit', 'تعديل') }}</button>
                            <button @click="remove(s)" class="text-red-600 text-xs font-semibold">{{ t('Delete', 'حذف') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!sessions.data.length"><td colspan="7" class="text-center py-8 text-gray-400">{{ t('No sessions', 'لا توجد جلسات') }}</td></tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-auto p-6">
                <h2 class="text-lg font-bold mb-4">{{ editing ? t('Edit session', 'تعديل الجلسة') : t('New session', 'جلسة جديدة') }}</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Patient', 'المريض') }} *</label>
                        <select v-model="form.patient_id" required class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Doctor', 'الطبيب') }}</label>
                        <select v-model="form.doctor_id" class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name_ar || d.name_en }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Type', 'النوع') }} *</label>
                        <select v-model="form.session_type" required class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option v-for="tt in types" :key="tt" :value="tt">{{ tt }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Area treated', 'المنطقة') }}</label>
                        <input v-model="form.area_treated" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Product used', 'المنتج') }}</label>
                        <input v-model="form.product_used" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Session #', 'رقم الجلسة') }}</label>
                        <input v-model.number="form.session_number" type="number" min="1" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Total sessions', 'إجمالي الجلسات') }}</label>
                        <input v-model.number="form.total_sessions" type="number" min="1" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Cost', 'التكلفة') }}</label>
                        <input v-model.number="form.cost" type="number" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Completed at', 'تاريخ الإنجاز') }}</label>
                        <input v-model="form.completed_at" type="date" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Next session', 'الجلسة القادمة') }}</label>
                        <input v-model="form.next_session_date" type="date" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Notes', 'ملاحظات') }}</label>
                        <textarea v-model="form.notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-pink-600 text-white text-sm font-semibold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
