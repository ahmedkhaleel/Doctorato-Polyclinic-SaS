<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: AdminLayout });

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ consents: Object, filters: Object, procedures: Array, patients: Array, templates: { type: Array, default: () => [] } });

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
    patient_id: '', procedure_id: '', template_id: '', session_id: null,
    consent_text: '', signed_at: '', signature: null, witnessed_by: '',
});
// Picking a template prefills the consent text (and procedure) from it.
function onTemplateChange() {
    const tpl = props.templates.find(x => x.id === Number(form.template_id));
    if (!tpl) return;
    form.consent_text = isRtl.value ? (tpl.body_ar || tpl.body_en) : (tpl.body_en || tpl.body_ar);
    if (!form.procedure_id && tpl.procedure_id) form.procedure_id = tpl.procedure_id;
}
function tplTitle(t) { return isRtl.value ? (t.title_ar || t.title_en) : (t.title_en || t.title_ar); }
function submit() {
    form.post('/admin/cosmetic/consents', { preserveScroll: true, onSuccess: () => { showModal.value = false; form.reset(); } });
}
function remove(c) {
    confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?', () => {
        router.delete(`/admin/cosmetic/consents/${c.id}`, { preserveScroll: true });
    });
}
function t(en, ar) { return isRtl.value ? ar : en; }
function fmt(d) { if (!d) return '-'; return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB'); }
</script>

<template>
    <div class="space-y-6 pb-10">
        <!-- Navy Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
            <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                <div class="flex items-start gap-3 md:gap-4 min-w-0">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'DERMA & COSMETIC' }}</span>
                        </div>
                        <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ t('Consent Forms', 'نماذج الموافقة') }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ t('Signed patient consents', 'موافقات المرضى الموقعة') }}</p>
                    </div>
                </div>
                <button @click="showModal = true" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-4 md:px-5 py-2.5 shadow-md hover:shadow-lg transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ t('New consent', 'موافقة جديدة') }}
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search patient…', 'بحث عن مريض…')" class="doctorato-input flex-1 min-w-[220px] px-4 py-2.5 border border-slate-200 rounded-xl text-sm" />
            <select v-model="procId" class="doctorato-input px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                <option value="">{{ t('All procedures', 'كل الإجراءات') }}</option>
                <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm">
                    <thead class="bg-[#1B365D]/5 text-[#1B365D]">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ t('Procedure', 'الإجراء') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Signed at', 'تاريخ التوقيع') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Witnessed by', 'الشاهد') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(c, i) in consents.data" :key="c.id" class="lst-row hover:bg-[#C4A265]/5 transition" :style="{ '--row-i': i }">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ c.patient?.full_name || '-' }}</td>
                            <td class="px-5 py-3 hidden md:table-cell text-slate-600">{{ c.procedure?.name_ar || '-' }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell text-slate-600">{{ fmt(c.signed_at) }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell text-slate-600">{{ c.witnessed_by || '-' }}</td>
                            <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                                <a v-if="c.signature_path" :href="`/storage/${c.signature_path}`" target="_blank" class="text-[#C4A265] hover:text-[#8B7043] text-xs font-bold">{{ t('View', 'عرض') }}</a>
                                <button @click="remove(c)" class="text-red-600 hover:text-red-800 text-xs font-bold">{{ t('Delete', 'حذف') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!consents.data.length"><td colspan="5" class="text-center py-8 text-slate-400">{{ t('No consents', 'لا توجد موافقات') }}</td></tr>
                    </tbody>
                </table>
            </div>
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
                    <select v-if="templates.length" v-model="form.template_id" @change="onTemplateChange" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="">{{ t('Use a template (optional)', 'استخدم قالباً (اختياري)') }}</option>
                        <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">{{ tplTitle(tpl) }}</option>
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
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-gradient-to-r from-[#C4A265] to-[#8B7043] text-white text-sm font-bold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.lst-row {
    animation: lstRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes lstRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .lst-row { animation: none !important; }
}
</style>
