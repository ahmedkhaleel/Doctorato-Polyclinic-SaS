<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    encounters: { type: Object, default: () => ({ data: [], links: [] }) },
    doctors: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const accent = computed(() => (props.module === 'neurology' ? '#0EA5E9' : '#7C3AED'));
const moduleName = computed(() => {
    const m = isRtl.value
        ? { psychiatry: 'الطب النفسي', neurology: 'طب الأعصاب' }
        : { psychiatry: 'Psychiatry', neurology: 'Neurology' };
    return m[props.module] || props.module;
});

const doctorId = ref(props.filters.doctor_id || '');
const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');
function apply() {
    router.get(route(`admin.${props.module}.encounters`), { doctor_id: doctorId.value || undefined, from: from.value || undefined, to: to.value || undefined }, { preserveState: true, replace: true });
}
function reset() {
    doctorId.value = ''; from.value = ''; to.value = '';
    apply();
}
function money(v) {
    return Number(v || 0).toLocaleString(isRtl.value ? 'ar-EG' : 'en-US', { minimumFractionDigits: 0 });
}
function docName(d) {
    return isRtl.value ? d.name_ar : d.name_en;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ (isRtl ? 'سجل اللقاءات — ' : 'Encounters — ') + moduleName }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Filters -->
            <div class="flex items-end gap-2 flex-wrap bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
                <label class="flex flex-col gap-1 text-xs text-gray-500">
                    <span>{{ isRtl ? 'الطبيب' : 'Doctor' }}</span>
                    <select v-model="doctorId" @change="apply" class="rounded-xl border-gray-200 text-sm py-2 min-w-[160px]">
                        <option value="">{{ isRtl ? 'الكل' : 'All' }}</option>
                        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ docName(d) }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-xs text-gray-500">
                    <span>{{ isRtl ? 'من' : 'From' }}</span>
                    <input v-model="from" @change="apply" type="date" class="rounded-xl border-gray-200 text-sm py-2" />
                </label>
                <label class="flex flex-col gap-1 text-xs text-gray-500">
                    <span>{{ isRtl ? 'إلى' : 'To' }}</span>
                    <input v-model="to" @change="apply" type="date" class="rounded-xl border-gray-200 text-sm py-2" />
                </label>
                <button @click="reset" class="px-3 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
                    {{ isRtl ? 'إعادة ضبط' : 'Reset' }}
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(e, i) in encounters.data" :key="e.id" class="lst-row hover:bg-gray-50/60" :style="{ '--row-i': i }">
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ e.encounter_date }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', e.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ e.patient.full_name }}</Link>
                                <div class="text-xs text-gray-400">{{ e.patient.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ docName(e.doctor) }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                <span v-if="e.primary_dx">{{ e.primary_dx }}</span>
                                <span v-else class="text-gray-300">—</span>
                                <span v-if="e.dx_count > 1" class="text-xs text-gray-400"> (+{{ e.dx_count - 1 }})</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700" dir="ltr">{{ money(e.cost) }}</td>
                            <td class="px-4 py-3">
                                <span v-if="e.billed" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">{{ isRtl ? 'مفوتر' : 'Billed' }}</span>
                                <span v-else-if="e.completed" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">{{ isRtl ? 'مكتمل (بلا فاتورة)' : 'Completed' }}</span>
                                <span v-else class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">{{ isRtl ? 'مسودة' : 'Draft' }}</span>
                            </td>
                        </tr>
                        <tr v-if="encounters.data.length === 0"><td colspan="6" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد لقاءات' : 'No encounters' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="encounters.links && encounters.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in encounters.links" :key="i">
                    <Link v-if="l.url" :href="l.url" v-html="l.label"
                          class="px-3 py-1.5 rounded-lg text-sm" :class="l.active ? 'text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                          :style="l.active ? { background: accent } : {}" preserve-scroll />
                    <span v-else v-html="l.label" class="px-3 py-1.5 rounded-lg text-sm text-gray-300"></span>
                </template>
            </div>
        </div>
    </AdminLayout>
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
