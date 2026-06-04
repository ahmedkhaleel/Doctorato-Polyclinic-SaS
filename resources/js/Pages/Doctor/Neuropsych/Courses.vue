<script setup>
import { ref, computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: DoctorLayout });

const props = defineProps({ module: String, courses: Object, patients: Array, courseTypes: Array });

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }
const accent = computed(() => props.module === 'neurology' ? '#0EA5E9' : '#7C3AED');

const showAdd = ref(false);
const form = useForm({ patient_id: '', type: 'rtms', planned_sessions: 12, session_fee: 0, consent_required: true, notes: '' });
function openAdd() { form.reset(); form.clearErrors(); showAdd.value = true; }
function submit() { form.post(route(`doctor.${props.module}.courses.store`), { preserveScroll: true, onSuccess: () => { showAdd.value = false; router.reload({ only: ['courses'] }); } }); }

function signConsent(c) {
    confirm(t('Record signed consent for this course?', 'توثيق موافقة موقّعة لهذه الدورة؟'), () => router.post(route(`doctor.${props.module}.courses.consent`, c.id), { signed_by: 'patient' }, { preserveScroll: true }));
}
function remove(c) {
    confirm(t('Delete this course?', 'حذف هذه الدورة؟'), () => router.post(route(`doctor.${props.module}.courses.destroy`, c.id), {}, { preserveScroll: true }));
}

const sessTarget = ref(null);
const sessForm = useForm({ performed_at: new Date().toISOString().slice(0, 10), cost: 0, completed_at: '', notes: '' });
function openSession(c) { sessTarget.value = c; sessForm.reset(); sessForm.cost = c.session_fee; sessForm.clearErrors(); }
function submitSession() {
    sessForm.post(route(`doctor.${props.module}.courses.sessions.store`, sessTarget.value.id), { preserveScroll: true, onSuccess: () => { sessTarget.value = null; router.reload({ only: ['courses'] }); } });
}

function typeLabel(ty) { return { ect: 'ECT', rtms: 'rTMS', ketamine: t('Ketamine', 'كيتامين') }[ty] || ty; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold">{{ t('Treatment courses', 'الدورات العلاجية') }}</h1>
                    <p class="text-white/70 text-sm mt-1">{{ t('ECT / rTMS / ketamine session series', 'سلاسل جلسات ECT/rTMS/كيتامين') }}</p>
                </div>
                <button @click="openAdd" class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold px-5 py-2.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('New course', 'دورة جديدة') }}
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase">{{ t('Type', 'النوع') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase hidden md:table-cell">{{ t('Sessions', 'الجلسات') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase hidden lg:table-cell">{{ t('Consent', 'الموافقة') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in courses.data" :key="c.id" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ c.patient?.full_name }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ typeLabel(c.type) }}</td>
                            <td class="px-5 py-3 text-slate-600 hidden md:table-cell">{{ c.sessions?.length || 0 }} / {{ c.planned_sessions }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell">
                                <span v-if="!c.consent_required" class="text-xs text-slate-400">{{ t('Not required', 'غير مطلوبة') }}</span>
                                <span v-else-if="c.consent_signed_at" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">{{ t('Signed', 'موقّعة') }}</span>
                                <span v-else class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">{{ t('Pending', 'بانتظار') }}</span>
                            </td>
                            <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                                <button v-if="c.consent_required && !c.consent_signed_at" @click="signConsent(c)" :aria-label="t('Record consent', 'توثيق الموافقة')" :title="t('Record consent', 'توثيق الموافقة')" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                                <button @click="openSession(c)" :aria-label="t('Add session', 'إضافة جلسة')" :title="t('Add session', 'إضافة جلسة')" class="p-1.5 text-slate-400 hover:text-[#1B365D] hover:bg-slate-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                                <button @click="remove(c)" :aria-label="t('Delete', 'حذف')" :title="t('Delete', 'حذف')" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!courses.data.length" class="px-6 py-16 text-center text-sm text-slate-500">{{ t('No courses yet', 'لا توجد دورات بعد') }}</div>
        </div>

        <!-- Add course modal -->
        <div v-if="showAdd" v-focus-trap="() => (showAdd = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showAdd = false">
            <div class="bg-white rounded-2xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ t('New course', 'دورة جديدة') }}</h2>
                    <button @click="showAdd = false" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submit" class="space-y-3">
                    <FormErrors :errors="form.errors" />
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Patient', 'المريض') }} *</label>
                        <select v-model="form.patient_id" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">{{ t('Select', 'اختر') }}</option>
                            <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Type', 'النوع') }}</label><select v-model="form.type" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"><option v-for="ty in courseTypes" :key="ty" :value="ty">{{ typeLabel(ty) }}</option></select></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Sessions', 'الجلسات') }}</label><input v-model.number="form.planned_sessions" type="number" min="1" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Session fee', 'أتعاب الجلسة') }}</label><input v-model.number="form.session_fee" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm"><input v-model="form.consent_required" type="checkbox" /> {{ t('Requires signed consent', 'تتطلّب موافقة موقّعة') }}</label>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showAdd = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg text-white text-sm font-bold disabled:opacity-50" :style="{ background: accent }">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add session modal -->
        <div v-if="sessTarget" v-focus-trap="() => (sessTarget = null)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="sessTarget = null">
            <div class="bg-white rounded-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ t('Add session', 'إضافة جلسة') }}</h2>
                    <button @click="sessTarget = null" :aria-label="t('Close', 'إغلاق')" :title="t('Close', 'إغلاق')" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitSession" class="space-y-3">
                    <FormErrors :errors="sessForm.errors" />
                    <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Date', 'التاريخ') }} *</label><input v-model="sessForm.performed_at" type="date" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Fee', 'الأتعاب') }}</label><input v-model.number="sessForm.cost" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Completion', 'الإكمال') }}</label><input v-model="sessForm.completed_at" type="date" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="sessTarget = null" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="sessForm.processing" class="px-5 py-2 rounded-lg text-white text-sm font-bold disabled:opacity-50" :style="{ background: accent }">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
