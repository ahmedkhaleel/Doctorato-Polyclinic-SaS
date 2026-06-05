<script setup>
import { ref, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: DoctorLayout });

const props = defineProps({ module: String, prescriptions: Object, patients: Array, gateway: String });

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }
const accent = computed(() => props.module === 'neurology' ? '#0EA5E9' : '#7C3AED');

const showAdd = ref(false);
const form = useForm({ patient_id: '', drug: '', schedule: '', dose: '', quantity: '', notes: '' });
function openAdd() { form.reset(); form.clearErrors(); showAdd.value = true; }
function submitDraft() { form.post(route(`doctor.${props.module}.controlled-rx.store`), { preserveScroll: true, onSuccess: () => { showAdd.value = false; router.reload({ only: ['prescriptions'] }); } }); }

// Sign requires a second factor (MFA hook).
const signTarget = ref(null);
const signForm = useForm({ mfa_token: '' });
function openSign(rx) { signTarget.value = rx; signForm.reset(); signForm.clearErrors(); }
function submitSign() {
    signForm.post(route(`doctor.${props.module}.controlled-rx.sign`, signTarget.value.id), { preserveScroll: true, onSuccess: () => { signTarget.value = null; router.reload({ only: ['prescriptions'] }); } });
}

function submitRx(rx) {
    router.post(route(`doctor.${props.module}.controlled-rx.submit`, rx.id), {}, { preserveScroll: true });
}
function dispense(rx) {
    router.post(route(`doctor.${props.module}.controlled-rx.dispense`, rx.id), {}, { preserveScroll: true });
}
function remove(rx) {
    confirm(t('Delete this prescription?', 'حذف هذه الروشتة؟'), () => router.post(route(`doctor.${props.module}.controlled-rx.destroy`, rx.id), {}, { preserveScroll: true }));
}

function statusClass(s) {
    return {
        draft: 'bg-slate-100 text-slate-600', signed: 'bg-indigo-50 text-indigo-700',
        submitted: 'bg-amber-50 text-amber-700', authorized: 'bg-emerald-50 text-emerald-700',
        dispensed: 'bg-emerald-100 text-emerald-800', cancelled: 'bg-red-50 text-red-700',
    }[s] || 'bg-slate-100 text-slate-600';
}
function statusLabel(s) {
    return { draft: t('Draft', 'مسودة'), signed: t('Signed', 'موقّعة'), submitted: t('Submitted', 'مُرسَلة'), authorized: t('Authorized', 'معتمدة'), dispensed: t('Dispensed', 'مصروفة'), cancelled: t('Cancelled', 'ملغاة') }[s] || s;
}
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold">{{ t('Controlled prescriptions', 'الروشتات الخاضعة') }}</h1>
                    <p class="text-white/70 text-sm mt-1">{{ t('Gateway', 'البوّابة') }}: {{ gateway }}</p>
                </div>
                <button @click="openAdd" class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold px-5 py-2.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('New prescription', 'روشتة جديدة') }}
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase">{{ t('Drug', 'الدواء') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase hidden md:table-cell">{{ t('Serial', 'التسلسل') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase">{{ t('Status', 'الحالة') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="rx in prescriptions.data" :key="rx.id" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ rx.patient?.full_name }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ rx.drug }} <span v-if="rx.schedule" class="text-[10px] text-slate-400">({{ rx.schedule }})</span></td>
                            <td class="px-5 py-3 text-slate-500 hidden md:table-cell">{{ rx.external_ref || '—' }}</td>
                            <td class="px-5 py-3"><span :class="statusClass(rx.status)" class="text-xs font-semibold px-2.5 py-1 rounded-full">{{ statusLabel(rx.status) }}</span></td>
                            <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                                <button v-if="rx.status === 'draft'" @click="openSign(rx)" :aria-label="t('Sign', 'توقيع')" :title="t('Sign', 'توقيع')" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button v-if="rx.status === 'signed'" @click="submitRx(rx)" :aria-label="t('Submit', 'إرسال')" :title="t('Submit', 'إرسال')" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 6l6 6-6 6" /></svg>
                                </button>
                                <button v-if="rx.status === 'authorized'" @click="dispense(rx)" :aria-label="t('Mark dispensed', 'وضع علامة مصروف')" :title="t('Mark dispensed', 'وضع علامة مصروف')" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                                <button @click="remove(rx)" :aria-label="t('Delete', 'حذف')" :title="t('Delete', 'حذف')" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!prescriptions.data.length" class="px-6 py-16 text-center text-sm text-slate-500">{{ t('No controlled prescriptions yet', 'لا توجد روشتات خاضعة بعد') }}</div>
        </div>

        <!-- Add modal -->
        <div v-if="showAdd" v-focus-trap="() => (showAdd = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showAdd = false">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ t('New controlled prescription', 'روشتة خاضعة جديدة') }}</h2>
                    <button @click="showAdd = false" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitDraft" class="space-y-3">
                    <FormErrors :errors="form.errors" />
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Patient', 'المريض') }} *</label>
                        <select v-model="form.patient_id" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">{{ t('Select', 'اختر') }}</option>
                            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Drug', 'الدواء') }} *</label><input v-model="form.drug" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Schedule', 'الجدول') }}</label><input v-model="form.schedule" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Dose', 'الجرعة') }}</label><input v-model="form.dose" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Quantity', 'الكمية') }} *</label><input v-model="form.quantity" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showAdd = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg text-white text-sm font-bold disabled:opacity-50" :style="{ background: accent }">{{ t('Save draft', 'حفظ مسودة') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sign modal (MFA) -->
        <div v-if="signTarget" v-focus-trap="() => (signTarget = null)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="signTarget = null">
            <div class="bg-white rounded-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ t('Sign prescription', 'توقيع الروشتة') }}</h2>
                    <button @click="signTarget = null" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitSign" class="space-y-3">
                    <FormErrors :errors="signForm.errors" />
                    <p class="text-xs text-gray-500">{{ t('Enter your second-factor code to sign this controlled prescription.', 'أدخل رمز العامل الثاني لتوقيع هذه الروشتة الخاضعة.') }}</p>
                    <input v-model="signForm.mfa_token" :placeholder="t('Authentication code', 'رمز المصادقة')" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="signTarget = null" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="signForm.processing" class="px-5 py-2 rounded-lg text-white text-sm font-bold disabled:opacity-50" :style="{ background: accent }">{{ t('Sign', 'توقيع') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
