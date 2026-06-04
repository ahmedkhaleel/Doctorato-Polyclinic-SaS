<script setup>
import { ref, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import MseForm from '@/Components/Clinical/MseForm.vue';
import RiskAssessmentPanel from '@/Components/Clinical/RiskAssessmentPanel.vue';
import { useConfirm } from '@/Composables/useConfirm.js';
import { usePermissions } from '@/Composables/usePermissions.js';

defineOptions({ layout: DoctorLayout });

const props = defineProps({
    module: String,            // psychiatry | neurology
    encounters: Object,        // paginator
    patients: Array,
    noteFormats: Array,
    activeRisks: { type: Object, default: () => ({}) }, // { patient_id: {level, reason_en, reason_ar} }
});

const { confirm } = useConfirm();
const { can } = usePermissions();
const canSensitive = computed(() => can(`${props.module}.view_sensitive`));
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }

const title = computed(() => props.module === 'neurology' ? t('Neurology', 'طب الأعصاب') : t('Psychiatry', 'الطب النفسي'));
const accent = computed(() => props.module === 'neurology' ? '#0EA5E9' : '#7C3AED');

const showModal = ref(false);
const editing = ref(null);

const blankForm = () => ({
    patient_id: '', encounter_date: new Date().toISOString().slice(0, 10),
    note_format: 'soap', subjective: '', objective: '', assessment: '', plan: '',
    mse: {}, cost: 0, completed_at: '',
    diagnoses: [{ code_system: props.module === 'psychiatry' ? 'dsm5' : 'icd11', code: '', label: '', is_primary: true }],
});
const form = useForm(blankForm());

function openAdd() {
    editing.value = null;
    form.defaults(blankForm());
    form.reset();
    form.clearErrors();
    showModal.value = true;
}
function openEdit(enc) {
    editing.value = enc;
    form.defaults({
        patient_id: enc.patient_id, encounter_date: enc.encounter_date?.slice(0, 10),
        note_format: enc.note_format, subjective: enc.subjective || '', objective: enc.objective || '',
        assessment: enc.assessment || '', plan: enc.plan || '', mse: enc.mse || {},
        cost: enc.cost || 0, completed_at: enc.completed_at ? enc.completed_at.slice(0, 10) : '',
        diagnoses: enc.diagnoses?.length ? enc.diagnoses.map(d => ({ ...d })) : blankForm().diagnoses,
    });
    form.reset();
    form.clearErrors();
    showModal.value = true;
}
function addDx() { form.diagnoses.push({ code_system: 'icd11', code: '', label: '', is_primary: false }); }
function removeDx(i) { form.diagnoses.splice(i, 1); }

function submit() {
    const url = editing.value
        ? route(`doctor.${props.module}.encounters.update`, editing.value.id)
        : route(`doctor.${props.module}.encounters.store`);
    form.post(url, { preserveScroll: true, onSuccess: () => { showModal.value = false; router.reload({ only: ['encounters'] }); } });
}
function remove(enc) {
    confirm(t('Delete this encounter?', 'حذف هذا اللقاء؟'), () => {
        router.delete(route(`doctor.${props.module}.encounters.destroy`, enc.id), { preserveScroll: true });
    });
}

function patientName(enc) { return enc.patient?.full_name || '—'; }
function fmt(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB') : '—'; }

// ── Risk assessment (C-SSRS) ──
const showRisk = ref(false);
const riskPatient = ref(null);
const riskForm = useForm({ patient_id: '', answers: {}, safety_plan: '' });
function openRisk(enc) {
    riskPatient.value = enc.patient;
    riskForm.defaults({ patient_id: enc.patient_id, answers: {}, safety_plan: '' });
    riskForm.reset();
    riskForm.clearErrors();
    showRisk.value = true;
}
function submitRisk() {
    riskForm.post(route(`doctor.${props.module}.risk.store`), {
        preserveScroll: true,
        onSuccess: () => { showRisk.value = false; router.reload({ only: ['encounters', 'activeRisks'] }); },
    });
}

// Patients (in this page) with an active risk, for the banner.
const riskPatients = computed(() => {
    const byId = {};
    for (const enc of props.encounters.data) {
        const r = props.activeRisks?.[enc.patient_id];
        if (r && !byId[enc.patient_id]) byId[enc.patient_id] = { name: patientName(enc), ...r };
    }
    return Object.values(byId);
});
function riskOf(enc) { return props.activeRisks?.[enc.patient_id] || null; }
function riskChipClass(level) {
    return level === 'high' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700';
}
</script>

<template>
    <div class="space-y-6 pb-10">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold">{{ title }}</h1>
                    <p class="text-white/70 text-sm mt-1">{{ t('Clinical encounters', 'اللقاءات السريرية') }}</p>
                </div>
                <button @click="openAdd"
                    class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold px-5 py-2.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('New encounter', 'لقاء جديد') }}
                </button>
            </div>
        </div>

        <!-- Patient-safety banner: active suicide/self-harm risk among listed patients -->
        <div v-if="canSensitive && riskPatients.length" role="alert"
            class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div>
                    <p class="text-sm font-bold text-red-700">{{ t('Active patient-safety alerts', 'تنبيهات سلامة نشطة') }}</p>
                    <ul class="mt-1 space-y-0.5 text-xs text-red-600">
                        <li v-for="(r, i) in riskPatients" :key="i">
                            <span class="font-semibold">{{ r.name }}</span> — {{ isRtl ? r.reason_ar : r.reason_en }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Date', 'التاريخ') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ t('Diagnosis', 'التشخيص') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Status', 'الحالة') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="enc in encounters.data" :key="enc.id" class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3 font-medium text-slate-800">
                                {{ patientName(enc) }}
                                <span v-if="canSensitive && riskOf(enc)" :class="riskChipClass(riskOf(enc).level)"
                                    class="ms-2 inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full align-middle"
                                    :title="isRtl ? riskOf(enc).reason_ar : riskOf(enc).reason_en">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ t('Risk', 'خطر') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ fmt(enc.encounter_date) }}</td>
                            <td class="px-5 py-3 text-slate-600 hidden md:table-cell">{{ enc.diagnoses?.find(d => d.is_primary)?.label || enc.diagnoses?.[0]?.label || '—' }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell">
                                <span :class="enc.completed_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" class="text-xs font-semibold px-2.5 py-1 rounded-full">
                                    {{ enc.completed_at ? t('Completed', 'مكتمل') : t('Draft', 'مسودة') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                                <button v-if="canSensitive" @click="openRisk(enc)" :aria-label="t('Risk assessment', 'تقييم الخطر')" :title="t('Risk assessment', 'تقييم الخطر')"
                                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                                <button @click="openEdit(enc)" :aria-label="t('Edit', 'تعديل')" :title="t('Edit', 'تعديل')"
                                    class="p-1.5 text-slate-400 hover:text-[#1B365D] hover:bg-slate-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button @click="remove(enc)" :aria-label="t('Delete', 'حذف')" :title="t('Delete', 'حذف')"
                                    class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!encounters.data.length" class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-700">{{ t('No encounters yet', 'لا توجد لقاءات بعد') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ t('Create the first encounter to get started', 'أنشئ أول لقاء للبدء') }}</p>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" v-focus-trap="() => (showModal = false)" role="dialog" aria-modal="true"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[92vh] overflow-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ editing ? t('Edit encounter', 'تعديل اللقاء') : t('New encounter', 'لقاء جديد') }}</h2>
                    <button @click="showModal = false" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <FormErrors :errors="form.errors" />

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Patient', 'المريض') }} *</label>
                            <select v-model="form.patient_id" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="">{{ t('Select patient', 'اختر المريض') }}</option>
                                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Date', 'التاريخ') }} *</label>
                            <input v-model="form.encounter_date" type="date" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Note format', 'صيغة الملاحظة') }}</label>
                            <select v-model="form.note_format" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                                <option v-for="f in noteFormats" :key="f" :value="f">{{ f.toUpperCase() }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Subjective', 'الشكوى/الذاتي') }}</label>
                            <textarea v-model="form.subjective" rows="2" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Objective', 'الموضوعي') }}</label>
                            <textarea v-model="form.objective" rows="2" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Assessment', 'التقييم') }}</label>
                            <textarea v-model="form.assessment" rows="2" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Plan', 'الخطة') }}</label>
                            <textarea v-model="form.plan" rows="2" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                        </div>
                    </div>

                    <!-- MSE -->
                    <div class="rounded-xl border border-slate-200 p-3">
                        <p class="text-xs font-bold text-slate-700 mb-2">{{ t('Mental Status Examination', 'فحص الحالة العقلية') }}</p>
                        <MseForm v-model="form.mse" />
                    </div>

                    <!-- Diagnoses -->
                    <div class="rounded-xl border border-slate-200 p-3">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-bold text-slate-700">{{ t('Diagnoses', 'التشخيصات') }}</p>
                            <button type="button" @click="addDx" :aria-label="t('Add diagnosis', 'إضافة تشخيص')" :title="t('Add diagnosis', 'إضافة تشخيص')"
                                class="p-1 text-[#1B365D] hover:bg-slate-100 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </button>
                        </div>
                        <div v-for="(dx, i) in form.diagnoses" :key="i" class="grid grid-cols-12 gap-2 mb-2 items-center">
                            <select v-model="dx.code_system" class="doctorato-input col-span-3 px-2 py-1.5 border rounded-lg text-xs">
                                <option value="icd11">ICD-11</option>
                                <option value="dsm5">DSM-5</option>
                            </select>
                            <input v-model="dx.code" :placeholder="t('Code', 'الرمز')" class="doctorato-input col-span-2 px-2 py-1.5 border rounded-lg text-xs" />
                            <input v-model="dx.label" :placeholder="t('Diagnosis', 'التشخيص')" class="doctorato-input col-span-5 px-2 py-1.5 border rounded-lg text-xs" />
                            <label class="col-span-1 inline-flex items-center justify-center text-xs" :title="t('Primary', 'رئيسي')">
                                <input type="radio" :checked="dx.is_primary" @change="form.diagnoses.forEach((d, j) => d.is_primary = j === i)" />
                            </label>
                            <button type="button" @click="removeDx(i)" :aria-label="t('Remove', 'حذف')" :title="t('Remove', 'حذف')"
                                class="col-span-1 p-1 text-red-400 hover:text-red-600 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Fee', 'الأتعاب') }}</label>
                            <input v-model.number="form.cost" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Completion date (bills on completion)', 'تاريخ الإكمال (يُفوتَر عند الإكمال)') }}</label>
                            <input v-model="form.completed_at" type="date" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg text-white text-sm font-bold disabled:opacity-50" :style="{ background: accent }">
                            {{ form.processing ? t('Saving…', 'جارٍ الحفظ…') : t('Save', 'حفظ') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Risk assessment modal (C-SSRS) -->
        <div v-if="showRisk" v-focus-trap="() => (showRisk = false)" role="dialog" aria-modal="true"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showRisk = false">
            <div class="bg-white rounded-2xl w-full max-w-lg max-h-[92vh] overflow-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ t('Risk assessment', 'تقييم الخطر') }}<span v-if="riskPatient"> — {{ riskPatient.full_name }}</span></h2>
                    <button @click="showRisk = false" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form @submit.prevent="submitRisk" class="space-y-4">
                    <FormErrors :errors="riskForm.errors" />
                    <RiskAssessmentPanel v-model:answers="riskForm.answers" v-model:safetyPlan="riskForm.safety_plan" />
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showRisk = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="riskForm.processing" class="px-5 py-2 rounded-lg bg-red-600 text-white text-sm font-bold disabled:opacity-50">
                            {{ riskForm.processing ? t('Saving…', 'جارٍ الحفظ…') : t('Save assessment', 'حفظ التقييم') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
