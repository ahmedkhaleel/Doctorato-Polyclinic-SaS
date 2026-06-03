<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: AdminLayout });

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ sessions: Object, filters: Object, types: Array, patients: Array, doctors: Array, plans: { type: Array, default: () => [] } });

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
    patient_id: '', doctor_id: '', visit_id: null, treatment_plan_id: '',
    session_type: 'other', area_treated: '', product_used: '',
    session_number: 1, total_sessions: 1, cost: 0,
    completed_at: '', next_session_date: '', notes: '',
});
// Active courses for the chosen patient (link the session to a course).
const patientPlans = computed(() => props.plans.filter(p => p.patient_id === Number(form.patient_id)));
function planTitle(p) { return isRtl.value ? (p.title_ar || p.title_en) : (p.title_en || p.title_ar); }
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
    confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?', () => {
        router.delete(`/admin/derma/sessions/${s.id}`, { preserveScroll: true });
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
                        <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ t('Treatment Sessions', 'جلسات العلاج') }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ t('Dermatology treatment session log', 'سجل جلسات العلاج الجلدي') }}</p>
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
            <select v-model="type" class="doctorato-input px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                <option value="">{{ t('All types', 'كل الأنواع') }}</option>
                <option v-for="tt in types" :key="tt" :value="tt">{{ tt }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-sm">
                    <thead class="bg-[#1B365D]/5 text-[#1B365D]">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Type', 'النوع') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ t('Area', 'المنطقة') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Session', 'رقم الجلسة') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Cost', 'التكلفة') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ t('Completed', 'تاريخ الإنجاز') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(s, i) in sessions.data" :key="s.id" class="lst-row hover:bg-[#C4A265]/5 transition" :style="{ '--row-i': i }">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ s.patient?.full_name || '-' }}</td>
                            <td class="px-5 py-3 capitalize text-slate-700">{{ s.session_type }}</td>
                            <td class="px-5 py-3 hidden md:table-cell text-slate-600">{{ s.area_treated || '-' }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell text-slate-600">{{ s.session_number }}/{{ s.total_sessions }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell text-slate-600">{{ s.cost }}</td>
                            <td class="px-5 py-3 hidden md:table-cell text-slate-500">{{ fmt(s.completed_at) }}</td>
                            <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                                <button @click="open(s)" class="text-[#C4A265] hover:text-[#8B7043] text-xs font-bold">{{ t('Edit', 'تعديل') }}</button>
                                <button @click="remove(s)" class="text-red-600 hover:text-red-800 text-xs font-bold">{{ t('Delete', 'حذف') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!sessions.data.length"><td colspan="7" class="text-center py-8 text-slate-400">{{ t('No sessions', 'لا توجد جلسات') }}</td></tr>
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
                        <label class="block text-xs font-medium mb-1">{{ t('Doctor', 'الطبيب') }}</label>
                        <select v-model="form.doctor_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">—</option>
                            <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name_ar || d.name_en }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Treatment course', 'كورس العلاج') }}</label>
                        <select v-model="form.treatment_plan_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">{{ t('Not part of a course', 'ليست ضمن كورس') }}</option>
                            <option v-for="pl in patientPlans" :key="pl.id" :value="pl.id">{{ planTitle(pl) }} ({{ pl.completed_sessions }}/{{ pl.estimated_sessions }})</option>
                        </select>
                        <p v-if="form.patient_id && !patientPlans.length" class="text-[10px] text-slate-400 mt-1">{{ t('No active courses for this patient', 'لا كورسات نشطة لهذا المريض') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Type', 'النوع') }} *</label>
                        <select v-model="form.session_type" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option v-for="tt in types" :key="tt" :value="tt">{{ tt }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Area treated', 'المنطقة') }}</label>
                        <input v-model="form.area_treated" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Product used', 'المنتج') }}</label>
                        <input v-model="form.product_used" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Session #', 'رقم الجلسة') }}</label>
                        <input v-model.number="form.session_number" type="number" min="1" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Total sessions', 'إجمالي الجلسات') }}</label>
                        <input v-model.number="form.total_sessions" type="number" min="1" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Cost', 'التكلفة') }}</label>
                        <input v-model.number="form.cost" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Completed at', 'تاريخ الإنجاز') }}</label>
                        <input v-model="form.completed_at" type="date" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Next session', 'الجلسة القادمة') }}</label>
                        <input v-model="form.next_session_date" type="date" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Notes', 'ملاحظات') }}</label>
                        <textarea v-model="form.notes" rows="2" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2">
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
