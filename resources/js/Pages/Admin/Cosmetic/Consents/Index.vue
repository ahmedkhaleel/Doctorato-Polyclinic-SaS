<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ consents: Object, filters: Object, procedures: Array, patients: Array });

const search = ref(props.filters?.search || '');
const procId = ref(props.filters?.procedure_id || '');
let tm = null;
function apply() {
    clearTimeout(tm);
    tm = setTimeout(() => router.get('/admin/cosmetic/consents', {
        search: search.value || undefined, procedure_id: procId.value || undefined,
    }, { preserveState: true, preserveScroll: true }), 400);
}
watch([search, procId], apply);

const showModal = ref(false);
const form = useForm({
    patient_id: '', procedure_id: '', session_id: null,
    consent_text: '', signed_at: '', signature: null, witnessed_by: '',
});
function submit() {
    form.post('/admin/cosmetic/consents', { preserveScroll: true, onSuccess: () => { showModal.value = false; form.reset(); } });
}
function remove(c) {
    if (!confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?')) return;
    router.delete(`/admin/cosmetic/consents/${c.id}`, { preserveScroll: true });
}
function t(en, ar) { return isRtl.value ? ar : en; }
function fmt(d) { if (!d) return '-'; return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB'); }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-[#1B365D] to-[#C4A265] rounded-2xl p-6 shadow-lg flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">{{ t('Consent Forms', 'نماذج الموافقة') }}</h1>
            <button @click="showModal = true" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold ring-1 ring-white/30">+ {{ t('New consent', 'موافقة جديدة') }}</button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search patient…', 'بحث عن مريض…')" class="doctorato-input flex-1 min-w-[220px] px-4 py-2.5 border rounded-xl text-sm" />
            <select v-model="procId" class="doctorato-input px-4 py-2.5 border rounded-xl text-sm">
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
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Signed at', 'تاريخ التوقيع') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Witnessed by', 'الشاهد') }}</th>
                        <th class="text-end px-5 py-3 font-semibold text-gray-500">{{ t('Actions', 'إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in consents.data" :key="c.id" class="border-t hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium">{{ c.patient?.full_name || '-' }}</td>
                        <td class="px-5 py-3 hidden md:table-cell">{{ c.procedure?.name_ar || '-' }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ fmt(c.signed_at) }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell">{{ c.witnessed_by || '-' }}</td>
                        <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                            <a v-if="c.signature_path" :href="`/storage/${c.signature_path}`" target="_blank" class="text-[#1B365D] text-xs font-semibold">{{ t('View', 'عرض') }}</a>
                            <button @click="remove(c)" class="text-red-600 text-xs font-semibold">{{ t('Delete', 'حذف') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!consents.data.length"><td colspan="5" class="text-center py-8 text-gray-400">{{ t('No consents', 'لا توجد موافقات') }}</td></tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-xl max-h-[90vh] overflow-auto p-6">
                <h2 class="text-lg font-bold mb-4">{{ t('New consent', 'موافقة جديدة') }}</h2>
                <form @submit.prevent="submit" class="space-y-3">
                    <select v-model="form.patient_id" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="">{{ t('Select patient', 'اختر المريض') }}</option>
                        <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                    </select>
                    <select v-model="form.procedure_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="">{{ t('Procedure', 'الإجراء') }}</option>
                        <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                    </select>
                    <textarea v-model="form.consent_text" :placeholder="t('Consent text', 'نص الموافقة')" rows="4" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    <input v-model="form.signed_at" type="date" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    <input v-model="form.witnessed_by" :placeholder="t('Witnessed by', 'الشاهد')" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Signature (image)', 'التوقيع (صورة)') }}</label>
                        <input type="file" accept="image/*" @change="e => form.signature = e.target.files[0]" class="w-full text-sm" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-[#1B365D] text-white text-sm font-semibold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
