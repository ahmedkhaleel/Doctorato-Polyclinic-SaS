<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useEscapeKey } from '@/Composables/useEscapeKey';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#DB2777';

const props = defineProps({
    pregnancies: { type: Object, default: () => ({ data: [] }) },
    patients: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'active');

function applyFilters() {
    router.get(route('doctor.obgyn.pregnancies.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true });
}

const showModal = ref(false);
useEscapeKey(() => { showModal.value = false; });
const form = useForm({
    patient_id: '', lmp: '', edd: '', gravida: '', para: '',
    conception_method: 'natural', blood_group: '', rh_factor: '', is_high_risk: false, notes: '',
});
function openPregnancy() {
    form.post(route('doctor.obgyn.pregnancies.store'), {
        onSuccess: () => { showModal.value = false; form.reset(); },
    });
}

const statuses = computed(() => [
    { key: 'active', label: isRtl.value ? 'نشط' : 'Active' },
    { key: 'delivered', label: isRtl.value ? 'وُلد' : 'Delivered' },
    { key: 'all', label: isRtl.value ? 'الكل' : 'All' },
]);
function trimesterLabel(t) {
    if (!t) return '';
    if (isRtl.value) return t === 1 ? 'الثلث الأول' : t === 2 ? 'الثلث الثاني' : 'الثلث الثالث';
    return t === 1 ? 'T1' : t === 2 ? 'T2' : 'T3';
}
function statusBadge(s) {
    return {
        active: 'bg-rose-100 text-rose-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        miscarried: 'bg-gray-100 text-gray-600',
        terminated: 'bg-gray-100 text-gray-600',
    }[s] || 'bg-gray-100 text-gray-600';
}
function statusText(s) {
    const map = isRtl.value
        ? { active: 'نشط', delivered: 'وُلد', miscarried: 'إجهاض', terminated: 'إنهاء' }
        : { active: 'Active', delivered: 'Delivered', miscarried: 'Miscarried', terminated: 'Terminated' };
    return map[s] || s;
}
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="w-2 h-8 rounded-full" :style="{ background: ACCENT }"></span>
                <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'ملفات الحمل' : 'Pregnancy Files' }}</h1>
            </div>
            <button @click="showModal = true"
                    class="inline-flex items-center gap-2 text-white px-4 py-2.5 rounded-xl font-semibold shadow-sm hover:opacity-90 transition"
                    :style="{ background: ACCENT }">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ isRtl ? 'فتح ملف حمل' : 'Open Pregnancy' }}
            </button>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-2 flex-wrap">
            <div class="relative flex-1 min-w-[200px]">
                <input v-model="search" @keyup.enter="applyFilters" type="text"
                       :placeholder="isRtl ? 'بحث باسم المريضة أو الهاتف…' : 'Search patient name or phone…'"
                       class="w-full rounded-xl border-gray-200 ps-10 py-2.5 text-sm focus:border-rose-400 focus:ring-rose-400" />
                <svg class="w-5 h-5 text-gray-400 absolute top-2.5 start-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
                <button v-for="s in statuses" :key="s.key" @click="status = s.key; applyFilters()"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
                        :class="status === s.key ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-500'">{{ s.label }}</button>
            </div>
        </div>

        <!-- Cards -->
        <div v-if="pregnancies.data.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <Link v-for="p in pregnancies.data" :key="p.id" :href="route('doctor.obgyn.pregnancies.show', p.id)"
                  class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 font-bold">{{ (p.patient?.full_name || '?').charAt(0) }}</div>
                        <div>
                            <p class="font-semibold text-gray-800 group-hover:text-rose-700 transition">{{ p.patient?.full_name }}</p>
                            <p class="text-xs text-gray-400">{{ p.patient?.phone }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="statusBadge(p.status)">{{ statusText(p.status) }}</span>
                </div>
                <div class="mt-4 flex items-center gap-2 flex-wrap">
                    <span v-if="p.ga_label" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-[#1B365D]/10 text-[#1B365D]">{{ p.ga_label }}</span>
                    <span v-if="p.trimester" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-[#C4A265]/15 text-[#9a7b3f]">{{ trimesterLabel(p.trimester) }}</span>
                    <span v-if="p.is_high_risk" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-700">{{ isRtl ? 'عالي الخطورة' : 'High-risk' }}</span>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-50 flex items-center justify-between text-sm">
                    <span class="text-gray-400">{{ isRtl ? 'الولادة المتوقعة' : 'EDD' }}</span>
                    <span class="font-medium text-gray-700" dir="ltr">{{ p.edd || '—' }}</span>
                </div>
            </Link>
        </div>
        <div v-else class="bg-white rounded-2xl border border-dashed border-gray-200 py-16 text-center text-gray-400">
            {{ isRtl ? 'لا توجد ملفات حمل' : 'No pregnancy files yet' }}
        </div>

        <!-- Open Pregnancy Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" v-focus-trap="() => (showModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false"></div>
                    <div role="dialog" aria-modal="true" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <div class="p-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
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
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'آخر دورة (LMP)' : 'LMP' }}</label>
                                    <input v-model="form.lmp" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الولادة المتوقعة' : 'EDD' }}</label>
                                    <input v-model="form.edd" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" />
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'يُحسب الموعد المتوقع تلقائياً من آخر دورة (قاعدة Naegele).' : 'EDD is auto-calculated from LMP (Naegele\'s rule).' }}</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gravida</label>
                                    <input v-model="form.gravida" type="number" min="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Para</label>
                                    <input v-model="form.para" type="number" min="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" />
                                </div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.is_high_risk" type="checkbox" class="rounded text-rose-600 focus:ring-rose-400" />
                                {{ isRtl ? 'حمل عالي الخطورة' : 'High-risk pregnancy' }}
                            </label>
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
