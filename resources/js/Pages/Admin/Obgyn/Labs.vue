<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    labs: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const accent = '#DB2777';

function setFilter(f) {
    router.get(route('admin.obgyn.labs'), { filter: f }, { preserveState: true, replace: true });
}
const tabs = computed(() => [
    { k: 'abnormal', l: isRtl.value ? 'غير طبيعية' : 'Abnormal' },
    { k: 'all', l: isRtl.value ? 'الكل' : 'All' },
]);
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'تحاليل النساء والتوليد' : 'OB/GYN Lab Tests' }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
                <button v-for="t in tabs" :key="t.k" @click="setFilter(t.k)"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition" :class="(filters.filter || 'abnormal') === t.k ? 'bg-white shadow-sm' : 'text-gray-500'"
                        :style="(filters.filter || 'abnormal') === t.k ? { color: accent } : {}">{{ t.l }}</button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريضة' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التحليل' : 'Test' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'النتيجة' : 'Result' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المرجع' : 'Reference' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(t, i) in labs.data" :key="t.id" class="lst-row hover:bg-pink-50/30" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', t.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ t.patient.full_name }}</Link>
                                <div class="text-xs text-gray-400">{{ t.patient.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ t.test_type }}</td>
                            <td class="px-4 py-3 text-gray-700" dir="ltr">{{ t.value }} {{ t.unit }}</td>
                            <td class="px-4 py-3 text-gray-500" dir="ltr">{{ t.reference_range || '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ t.result_date || '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="t.is_abnormal" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-700">{{ isRtl ? 'غير طبيعي' : 'Abnormal' }}</span>
                                <span v-else class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">{{ isRtl ? 'طبيعي' : 'Normal' }}</span>
                            </td>
                        </tr>
                        <tr v-if="labs.data.length === 0"><td colspan="6" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد تحاليل' : 'No lab tests' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="labs.links && labs.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in labs.links" :key="i">
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
