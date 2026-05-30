<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#DB2777';

const props = defineProps({
    pregnancies: { type: Object, default: () => ({ data: [], links: [] }) },
    patients: { type: Array, default: () => [] },
    doctors: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'active');
function apply() {
    router.get(route('secretary.obgyn.pregnancies.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true });
}

const showModal = ref(false);
const form = useForm({ patient_id: '', doctor_id: '', lmp: '', edd: '', is_high_risk: false, notes: '' });
function openPregnancy() {
    form.post(route('secretary.obgyn.pregnancies.store'), { onSuccess: () => { showModal.value = false; form.reset(); } });
}

const statuses = computed(() => [
    { k: 'active', l: isRtl.value ? 'نشط' : 'Active' },
    { k: 'delivered', l: isRtl.value ? 'وُلد' : 'Delivered' },
    { k: 'all', l: isRtl.value ? 'الكل' : 'All' },
]);
function statusBadge(s) { return { active: 'bg-rose-100 text-rose-700', delivered: 'bg-emerald-100 text-emerald-700' }[s] || 'bg-gray-100 text-gray-600'; }
function statusText(s) {
    const m = isRtl.value ? { active: 'نشط', delivered: 'وُلد', miscarried: 'إجهاض', terminated: 'إنهاء' } : { active: 'Active', delivered: 'Delivered', miscarried: 'Miscarried', terminated: 'Terminated' };
    return m[s] || s;
}
function dueLabel(d) {
    if (d === null || d === undefined) return '';
    if (d < 0) return isRtl.value ? `متأخّر ${Math.abs(d)}ي` : `${Math.abs(d)}d`;
    return isRtl.value ? `${d}ي` : `${d}d`;
}
</script>

<template>
    <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-8 rounded-full" :style="{ background: ACCENT }"></span>
                    <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'ملفات الحمل' : 'Pregnancy Files' }}</h1>
                </div>
                <button @click="showModal = true" class="inline-flex items-center gap-2 text-white px-4 py-2.5 rounded-xl font-semibold shadow-sm hover:opacity-90 transition" :style="{ background: ACCENT }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ isRtl ? 'فتح ملف حمل' : 'Open Pregnancy' }}
                </button>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <input v-model="search" @keyup.enter="apply" type="text" :placeholder="isRtl ? 'بحث باسم المريضة أو الهاتف…' : 'Search…'"
                       class="flex-1 min-w-[200px] rounded-xl border-gray-200 text-sm py-2.5 focus:border-rose-400 focus:ring-rose-400" />
                <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
                    <button v-for="s in statuses" :key="s.k" @click="status = s.k; apply()" class="px-3 py-1.5 rounded-lg text-sm font-medium transition" :class="status === s.k ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-500'">{{ s.l }}</button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريضة' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيبة' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'عمر الحمل' : 'GA' }}</th>
                            <th class="text-start px-4 py-3 font-medium">EDD</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in pregnancies.data" :key="p.id" class="hover:bg-rose-50/30">
                            <td class="px-4 py-3"><div class="font-medium text-gray-800">{{ p.patient?.full_name }}</div><div class="text-xs text-gray-400">{{ p.patient?.phone }}</div></td>
                            <td class="px-4 py-3 text-gray-500">{{ isRtl ? p.doctor?.name_ar : p.doctor?.name_en }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ p.ga_label || '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ p.edd || '—' }} <span v-if="p.days_until_edd != null" class="text-xs text-rose-600">({{ dueLabel(p.days_until_edd) }})</span></td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="statusBadge(p.status)">{{ statusText(p.status) }}</span></td>
                        </tr>
                        <tr v-if="pregnancies.data.length === 0"><td colspan="5" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد ملفات' : 'No pregnancy files' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Open Pregnancy Modal -->
            <Teleport to="body">
                <Transition name="modal">
                    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
                        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false"></div>
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-bold text-gray-800">{{ isRtl ? 'فتح ملف حمل جديد' : 'Open New Pregnancy' }}</h3>
                                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </div>
                            <form @submit.prevent="openPregnancy" class="p-5 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المريضة' : 'Patient' }} *</label>
                                    <select v-model="form.patient_id" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400">
                                        <option value="" disabled>{{ isRtl ? 'اختر مريضة…' : 'Select patient…' }}</option>
                                        <option v-for="pt in patients" :key="pt.id" :value="pt.id">{{ pt.full_name }} — {{ pt.file_number }}</option>
                                    </select>
                                    <p v-if="form.errors.patient_id" class="text-xs text-red-600 mt-1">{{ form.errors.patient_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الطبيبة المتابعة' : 'Attending doctor' }}</label>
                                    <select v-model="form.doctor_id" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400">
                                        <option value="">{{ isRtl ? 'لاحقاً' : 'Later' }}</option>
                                        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ isRtl ? d.name_ar : d.name_en }}</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'آخر دورة (LMP)' : 'LMP' }}</label><input v-model="form.lmp" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">EDD</label><input v-model="form.edd" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                </div>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'يُحسب الموعد المتوقّع تلقائياً من آخر دورة.' : 'EDD auto-calculated from LMP.' }}</p>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" @click="showModal = false" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" :style="{ background: ACCENT }">{{ isRtl ? 'فتح الملف' : 'Open File' }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
@media (prefers-reduced-motion: reduce) { .modal-enter-active, .modal-leave-active { transition: none; } }
</style>
