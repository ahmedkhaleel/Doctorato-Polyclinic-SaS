<script setup>
import { ref, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: DoctorLayout });

const props = defineProps({
    module: String,
    plans: Object,
    dueMonitoring: Array,
    patients: Array,
    drugClasses: Array,
});

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }
const accent = computed(() => props.module === 'neurology' ? '#0EA5E9' : '#7C3AED');

const showAdd = ref(false);
const form = useForm({ patient_id: '', drug: '', drug_class: 'other', dose: '', frequency: '', route: '', is_controlled: false, notes: '' });
function openAdd() { form.reset(); form.clearErrors(); showAdd.value = true; }
function submit() {
    form.post(route(`doctor.${props.module}.medications.store`), { preserveScroll: true, onSuccess: () => { showAdd.value = false; router.reload({ only: ['plans', 'dueMonitoring'] }); } });
}
function stop(plan) {
    confirm(t('Stop this medication?', 'إيقاف هذا الدواء؟'), () => router.post(route(`doctor.${props.module}.medications.stop`, plan.id), {}, { preserveScroll: true }));
}
function remove(plan) {
    confirm(t('Delete this plan?', 'حذف هذه الخطة؟'), () => router.post(route(`doctor.${props.module}.medications.destroy`, plan.id), {}, { preserveScroll: true }));
}

const monForm = useForm({ result: '', notes: '' });
const monTarget = ref(null);
function openMon(m) { monTarget.value = m; monForm.reset(); monForm.clearErrors(); }
function submitMon() {
    monForm.post(route(`doctor.${props.module}.monitoring.result`, monTarget.value.id), { preserveScroll: true, onSuccess: () => { monTarget.value = null; router.reload({ only: ['dueMonitoring'] }); } });
}

function monLabel(type) {
    return { clozapine_anc: t('Clozapine ANC', 'كلوزابين ANC'), lithium_level: t('Lithium level', 'مستوى الليثيوم'), metabolic: t('Metabolic panel', 'لوحة أيضية') }[type] || type;
}
function fmt(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB') : '—'; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold">{{ t('Medications', 'الأدوية') }}</h1>
                    <p class="text-white/70 text-sm mt-1">{{ t('Plans, safety monitoring & controlled register', 'الخطط والمراقبة وسجلّ المواد الخاضعة') }}</p>
                </div>
                <button @click="openAdd" class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold px-5 py-2.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('New medication', 'دواء جديد') }}
                </button>
            </div>
        </div>

        <!-- Due monitoring -->
        <div v-if="dueMonitoring.length" class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
            <p class="text-sm font-bold text-amber-800 mb-2">{{ t('Monitoring due', 'مراقبة مستحقّة') }}</p>
            <div class="space-y-2">
                <div v-for="m in dueMonitoring" :key="m.id" class="flex items-center justify-between text-sm bg-white rounded-lg px-3 py-2">
                    <span class="text-gray-700"><span class="font-semibold">{{ m.plan?.patient?.full_name }}</span> — {{ monLabel(m.type) }} · {{ fmt(m.due_at) }}</span>
                    <button @click="openMon(m)" class="px-3 py-1 rounded-lg text-xs font-bold text-white" :style="{ background: accent }">{{ t('Record result', 'تسجيل النتيجة') }}</button>
                </div>
            </div>
        </div>

        <!-- Plans -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase">{{ t('Drug', 'الدواء') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase hidden md:table-cell">{{ t('Dose', 'الجرعة') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase hidden lg:table-cell">{{ t('Status', 'الحالة') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in plans.data" :key="p.id" class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ p.patient?.full_name }}</td>
                            <td class="px-5 py-3 text-slate-700">
                                {{ p.drug }}
                                <span v-if="p.is_controlled" class="ms-2 text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ t('Controlled', 'خاضع') }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600 hidden md:table-cell">{{ p.dose || '—' }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell">
                                <span :class="p.stopped_at ? 'bg-slate-100 text-slate-500' : 'bg-emerald-50 text-emerald-700'" class="text-xs font-semibold px-2.5 py-1 rounded-full">
                                    {{ p.stopped_at ? t('Stopped', 'موقوف') : t('Active', 'نشط') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                                <button v-if="!p.stopped_at" @click="stop(p)" :aria-label="t('Stop', 'إيقاف')" :title="t('Stop', 'إيقاف')" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z M9 9h6v6H9z" /></svg>
                                </button>
                                <button @click="remove(p)" :aria-label="t('Delete', 'حذف')" :title="t('Delete', 'حذف')" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!plans.data.length" class="px-6 py-16 text-center">
                <p class="text-sm font-semibold text-slate-700">{{ t('No medications yet', 'لا توجد أدوية بعد') }}</p>
            </div>
        </div>

        <!-- Add modal -->
        <div v-if="showAdd" v-focus-trap="() => (showAdd = false)" role="dialog" aria-modal="true"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showAdd = false">
            <div class="bg-white rounded-2xl w-full max-w-lg max-h-[92vh] overflow-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ t('New medication', 'دواء جديد') }}</h2>
                    <button @click="showAdd = false" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form @submit.prevent="submit" class="space-y-3">
                    <FormErrors :errors="form.errors" />
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Patient', 'المريض') }} *</label>
                        <select v-model="form.patient_id" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">{{ t('Select patient', 'اختر المريض') }}</option>
                            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Drug', 'الدواء') }} *</label>
                            <input v-model="form.drug" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Class', 'الفئة') }}</label>
                            <select v-model="form.drug_class" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                                <option v-for="c in drugClasses" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Dose', 'الجرعة') }}</label>
                            <input v-model="form.dose" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Frequency', 'التكرار') }}</label>
                            <input v-model="form.frequency" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input v-model="form.is_controlled" type="checkbox" /> {{ t('Controlled substance', 'مادة خاضعة للرقابة') }}
                    </label>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showAdd = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg text-white text-sm font-bold disabled:opacity-50" :style="{ background: accent }">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Monitoring result modal -->
        <div v-if="monTarget" v-focus-trap="() => (monTarget = null)" role="dialog" aria-modal="true"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="monTarget = null">
            <div class="bg-white rounded-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ monLabel(monTarget.type) }}</h2>
                    <button @click="monTarget = null" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form @submit.prevent="submitMon" class="space-y-3">
                    <FormErrors :errors="monForm.errors" />
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Result', 'النتيجة') }} *</label>
                        <input v-model="monForm.result" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="monTarget = null" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="monForm.processing" class="px-5 py-2 rounded-lg text-white text-sm font-bold disabled:opacity-50" :style="{ background: accent }">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
