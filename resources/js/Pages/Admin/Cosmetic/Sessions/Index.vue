<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: AdminLayout });

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ sessions: Object, filters: Object, procedures: Array, packages: Array, patients: Array, doctors: Array, supplies: { type: Array, default: () => [] }, packagePurchases: { type: Array, default: () => [] } });

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
    patient_id: '', doctor_id: '', package_id: '', package_purchase_id: '', procedure_id: '', visit_id: null,
    supply_id: '', consumption_qty: null,
    session_number: 1, area_treated: '', product_used: '', dose_units: null,
    cost: 0, completed_at: '', notes: '',
});
// Active prepaid packages for the chosen patient (draw a session from one).
const patientPurchases = computed(() => props.packagePurchases.filter(p => p.patient_id === Number(form.patient_id)));
function supplyName(s) { return isRtl.value ? (s.name_ar || s.name_en) : (s.name_en || s.name_ar); }
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
    confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?', () => {
        router.delete(`/admin/cosmetic/sessions/${s.id}`, { preserveScroll: true });
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
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'DERMA & COSMETIC' }}</span>
                        </div>
                        <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ t('Client Sessions', 'جلسات العملاء') }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ t('Cosmetic session activity log', 'سجل نشاط جلسات التجميل') }}</p>
                    </div>
                </div>
                <button @click="open()" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-4 md:px-5 py-2.5 shadow-md hover:shadow-lg transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ t('New session', 'جلسة جديدة') }}
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search patient…', 'بحث عن مريض…')" class="doctorato-input flex-1 min-w-[200px] px-4 py-2.5 border border-slate-200 rounded-xl text-sm" />
            <select v-model="procId" class="doctorato-input px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                <option value="">{{ t('All procedures', 'كل الإجراءات') }}</option>
                <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-sm">
                    <thead class="bg-[#1B365D]/5 text-[#1B365D]">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ t('Procedure', 'الإجراء') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Area', 'المنطقة') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Cost', 'التكلفة') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ t('Completed', 'تاريخ الإنجاز') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in sessions.data" :key="s.id" class="hover:bg-[#C4A265]/5 transition">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ s.patient?.full_name || '-' }}</td>
                            <td class="px-5 py-3 hidden md:table-cell text-slate-600">{{ s.procedure?.name_ar || '-' }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell text-slate-600">{{ s.area_treated || '-' }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell text-slate-600">{{ s.cost }}</td>
                            <td class="px-5 py-3 hidden md:table-cell text-slate-500">{{ fmt(s.completed_at) }}</td>
                            <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                                <button @click="open(s)" class="text-[#C4A265] hover:text-[#8B7043] text-xs font-bold">{{ t('Edit', 'تعديل') }}</button>
                                <button @click="remove(s)" class="text-red-600 hover:text-red-800 text-xs font-bold">{{ t('Delete', 'حذف') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!sessions.data.length"><td colspan="6" class="text-center py-8 text-slate-400">{{ t('No sessions', 'لا توجد جلسات') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-auto p-6">
                <h2 class="text-lg font-bold mb-4">{{ editing ? t('Edit session', 'تعديل الجلسة') : t('New session', 'جلسة جديدة') }}</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Patient', 'المريض') }} *</label>
                        <select v-model="form.patient_id" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Procedure', 'الإجراء') }}</label>
                        <select v-model="form.procedure_id" @change="onProcedureChange" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Package', 'الباقة') }}</label>
                        <select v-model="form.package_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Draw from prepaid package', 'خصم من باقة مدفوعة') }}</label>
                        <select v-model="form.package_purchase_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">{{ t('No — bill separately', 'لا — فوترة منفصلة') }}</option>
                            <option v-for="pp in patientPurchases" :key="pp.id" :value="pp.id">{{ pp.name }} ({{ pp.remaining }} {{ t('left', 'متبقية') }})</option>
                        </select>
                        <p v-if="form.patient_id && !patientPurchases.length" class="text-[10px] text-slate-400 mt-1">{{ t('No active packages for this patient', 'لا باقات نشطة لهذا المريض') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Doctor', 'الطبيب') }}</label>
                        <select v-model="form.doctor_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name_ar || d.name_en }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Session #', 'رقم الجلسة') }}</label>
                        <input v-model.number="form.session_number" type="number" min="1" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Area', 'المنطقة') }}</label>
                        <input v-model="form.area_treated" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Product', 'المنتج') }}</label>
                        <input v-model="form.product_used" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Dose (units)', 'الجرعة (وحدات)') }}</label>
                        <input v-model.number="form.dose_units" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Inventory override (optional)', 'تجاوز المخزون (اختياري)') }}</label>
                        <select v-model="form.supply_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">{{ t('Use procedure default', 'افتراضي الإجراء') }}</option>
                            <option v-for="s in supplies" :key="s.id" :value="s.id">{{ supplyName(s) }}<span v-if="s.unit"> ({{ s.unit }})</span></option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Qty consumed', 'الكمية المستهلكة') }}</label>
                        <input v-model.number="form.consumption_qty" type="number" min="0" step="0.01" :placeholder="t('default', 'افتراضي')" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Cost', 'التكلفة') }}</label>
                        <input v-model.number="form.cost" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Completed at', 'تاريخ الإنجاز') }}</label>
                        <input v-model="form.completed_at" type="date" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Notes', 'ملاحظات') }}</label>
                        <textarea v-model="form.notes" rows="2" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-gradient-to-r from-[#C4A265] to-[#8B7043] text-white text-sm font-bold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
