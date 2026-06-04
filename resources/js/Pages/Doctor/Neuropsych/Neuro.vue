<script setup>
import { ref, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: DoctorLayout });

const props = defineProps({
    procedures: Object, patients: Array, procedureTypes: Array, supplies: Array,
    recentSeizures: Array, recentHeadaches: Array,
});

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }
const accent = '#0EA5E9';

const showProc = ref(false);
const procForm = useForm({ patient_id: '', type: 'eeg', performed_at: new Date().toISOString().slice(0, 10), cost: 0, supply_id: '', consumption_qty: '', completed_at: '', notes: '' });
function openProc() { procForm.reset(); procForm.clearErrors(); showProc.value = true; }
function submitProc() {
    procForm.post(route('doctor.neurology.procedures.store'), { preserveScroll: true, onSuccess: () => { showProc.value = false; router.reload({ only: ['procedures'] }); } });
}
function removeProc(p) {
    confirm(t('Delete this procedure?', 'حذف هذا الإجراء؟'), () => router.post(route('doctor.neurology.procedures.destroy', p.id), {}, { preserveScroll: true }));
}

const isBotox = computed(() => procForm.type === 'botox');
function procLabel(type) {
    return { emg_ncs: 'EMG/NCS', lumbar_puncture: t('Lumbar puncture', 'بزل قطني'), eeg: 'EEG', botox: t('Botox', 'بوتوكس'), nerve_block: t('Nerve block', 'حصار عصبي') }[type] || type;
}
function fmt(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB') : '—'; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold">{{ t('Neurology tools', 'أدوات الأعصاب') }}</h1>
                    <p class="text-white/70 text-sm mt-1">{{ t('Procedures & patient diaries', 'الإجراءات ومفكّرات المرضى') }}</p>
                </div>
                <button @click="openProc" class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold px-5 py-2.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('New procedure', 'إجراء جديد') }}
                </button>
            </div>
        </div>

        <!-- Procedures -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100"><h2 class="text-sm font-bold text-slate-700">{{ t('Procedures', 'الإجراءات') }}</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in procedures.data" :key="p.id" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ p.patient?.full_name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ procLabel(p.type) }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ fmt(p.performed_at) }}</td>
                            <td class="px-5 py-3 text-end">
                                <button @click="removeProc(p)" :aria-label="t('Delete', 'حذف')" :title="t('Delete', 'حذف')" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!procedures.data.length" class="px-6 py-12 text-center text-sm text-slate-500">{{ t('No procedures yet', 'لا توجد إجراءات بعد') }}</div>
        </div>

        <!-- Recent diaries -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-2">{{ t('Recent seizures', 'نوبات حديثة') }}</h3>
                <div v-if="recentSeizures.length" class="space-y-1 text-sm">
                    <div v-for="s in recentSeizures" :key="s.id" class="flex justify-between text-slate-600">
                        <span>{{ s.patient?.full_name }}</span><span>{{ s.seizure_type || '—' }} · {{ fmt(s.occurred_at) }}</span>
                    </div>
                </div>
                <p v-else class="text-xs text-slate-400">{{ t('No entries', 'لا توجد سجلات') }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-2">{{ t('Recent headaches', 'صداع حديث') }}</h3>
                <div v-if="recentHeadaches.length" class="space-y-1 text-sm">
                    <div v-for="h in recentHeadaches" :key="h.id" class="flex justify-between text-slate-600">
                        <span>{{ h.patient?.full_name }}</span><span>{{ h.ichd3_type || '—' }} · {{ fmt(h.date) }}</span>
                    </div>
                </div>
                <p v-else class="text-xs text-slate-400">{{ t('No entries', 'لا توجد سجلات') }}</p>
            </div>
        </div>

        <!-- Procedure modal -->
        <div v-if="showProc" v-focus-trap="() => (showProc = false)" role="dialog" aria-modal="true"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showProc = false">
            <div class="bg-white rounded-2xl w-full max-w-lg max-h-[92vh] overflow-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ t('New procedure', 'إجراء جديد') }}</h2>
                    <button @click="showProc = false" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitProc" class="space-y-3">
                    <FormErrors :errors="procForm.errors" />
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Patient', 'المريض') }} *</label>
                            <select v-model="procForm.patient_id" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="">{{ t('Select', 'اختر') }}</option>
                                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Type', 'النوع') }}</label>
                            <select v-model="procForm.type" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                                <option v-for="ty in procedureTypes" :key="ty" :value="ty">{{ procLabel(ty) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Date', 'التاريخ') }} *</label>
                            <input v-model="procForm.performed_at" type="date" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Fee', 'الأتعاب') }}</label>
                            <input v-model.number="procForm.cost" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <template v-if="isBotox">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Supply', 'المستلزم') }}</label>
                                <select v-model="procForm.supply_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                                    <option value="">{{ t('None', 'لا شيء') }}</option>
                                    <option v-for="s in supplies" :key="s.id" :value="s.id">{{ isRtl ? s.name_ar : s.name_en }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Qty used', 'الكمية') }}</label>
                                <input v-model.number="procForm.consumption_qty" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                            </div>
                        </template>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Completion (bills + draws stock)', 'الإكمال (يفوتر + يخصم)') }}</label>
                            <input v-model="procForm.completed_at" type="date" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showProc = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="procForm.processing" class="px-5 py-2 rounded-lg text-white text-sm font-bold disabled:opacity-50" :style="{ background: accent }">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
