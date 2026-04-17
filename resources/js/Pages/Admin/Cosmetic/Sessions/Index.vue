<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ sessions: Object, filters: Object, procedures: Array, packages: Array, patients: Array, doctors: Array });

const search = ref(props.filters?.search || '');
const procId = ref(props.filters?.procedure_id || '');
const pid = ref(props.filters?.patient_id || '');
let tm = null;
function apply() {
    clearTimeout(tm);
    tm = setTimeout(() => router.get('/admin/cosmetic/sessions', {
        search: search.value || undefined, procedure_id: procId.value || undefined, patient_id: pid.value || undefined,
    }, { preserveState: true, preserveScroll: true }), 400);
}
watch([search, procId, pid], apply);

const showModal = ref(false);
const editing = ref(null);
const form = useForm({
    patient_id: '', doctor_id: '', package_id: '', procedure_id: '', visit_id: null,
    session_number: 1, area_treated: '', product_used: '', dose_units: null,
    cost: 0, completed_at: '', notes: '',
});
function open(s = null) {
    editing.value = s;
    form.reset();
    if (s) {
        Object.keys(form.data()).forEach(k => form[k] = s[k] ?? form[k]);
        form.completed_at = s.completed_at?.substring(0, 10) || '';
    }
    showModal.value = true;
}
function onProcedureChange() {
    const p = props.procedures.find(x => x.id === Number(form.procedure_id));
    if (p && !editing.value) form.cost = Number(p.default_price) || 0;
}
function submit() {
    const url = editing.value ? `/admin/cosmetic/sessions/${editing.value.id}` : '/admin/cosmetic/sessions';
    form.post(url, { preserveScroll: true, onSuccess: () => { showModal.value = false; router.reload({ only: ['sessions'] }); } });
}
function remove(s) {
    if (!confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?')) return;
    router.delete(`/admin/cosmetic/sessions/${s.id}`, { preserveScroll: true });
}
function t(en, ar) { return isRtl.value ? ar : en; }
function fmt(d) { if (!d) return '-'; return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB'); }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-violet-600 to-fuchsia-500 rounded-2xl p-6 shadow-lg flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">{{ t('Client Sessions', 'جلسات العملاء') }}</h1>
            <button @click="open()" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold ring-1 ring-white/30">+ {{ t('New session', 'جلسة جديدة') }}</button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search patient…', 'بحث عن مريض…')" class="flex-1 min-w-[200px] px-4 py-2.5 border rounded-xl text-sm" />
            <select v-model="procId" class="px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All procedures', 'كل الإجراءات') }}</option>
                <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Patient', 'المريض') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden md:table-cell">{{ t('Procedure', 'الإجراء') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Area', 'المنطقة') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Cost', 'التكلفة') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden md:table-cell">{{ t('Completed', 'تاريخ الإنجاز') }}</th>
                        <th class="text-end px-5 py-3 font-semibold text-gray-500">{{ t('Actions', 'إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in sessions.data" :key="s.id" class="border-t hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium">{{ s.patient?.full_name || '-' }}</td>
                        <td class="px-5 py-3 hidden md:table-cell">{{ s.procedure?.name_ar || '-' }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ s.area_treated || '-' }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ s.cost }}</td>
                        <td class="px-5 py-3 hidden md:table-cell">{{ fmt(s.completed_at) }}</td>
                        <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                            <button @click="open(s)" class="text-violet-600 text-xs font-semibold">{{ t('Edit', 'تعديل') }}</button>
                            <button @click="remove(s)" class="text-red-600 text-xs font-semibold">{{ t('Delete', 'حذف') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!sessions.data.length"><td colspan="6" class="text-center py-8 text-gray-400">{{ t('No sessions', 'لا توجد جلسات') }}</td></tr>
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
                        <label class="block text-xs font-medium mb-1">{{ t('Procedure', 'الإجراء') }}</label>
                        <select v-model="form.procedure_id" @change="onProcedureChange" class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Package', 'الباقة') }}</label>
                        <select v-model="form.package_id" class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
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
                        <label class="block text-xs font-medium mb-1">{{ t('Session #', 'رقم الجلسة') }}</label>
                        <input v-model.number="form.session_number" type="number" min="1" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Area', 'المنطقة') }}</label>
                        <input v-model="form.area_treated" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Product', 'المنتج') }}</label>
                        <input v-model="form.product_used" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Dose (units)', 'الجرعة (وحدات)') }}</label>
                        <input v-model.number="form.dose_units" type="number" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Cost', 'التكلفة') }}</label>
                        <input v-model.number="form.cost" type="number" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Completed at', 'تاريخ الإنجاز') }}</label>
                        <input v-model="form.completed_at" type="date" class="w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Notes', 'ملاحظات') }}</label>
                        <textarea v-model="form.notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-violet-600 text-white text-sm font-semibold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
