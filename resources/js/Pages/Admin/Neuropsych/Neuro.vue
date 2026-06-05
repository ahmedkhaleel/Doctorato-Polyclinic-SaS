<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'neurology' },
    procedures: { type: Object, default: () => ({ data: [], links: [] }) },
    engagement: { type: Object, default: () => ({}) },
});

const accent = '#0EA5E9';

function money(v) {
    return Number(v || 0).toLocaleString(isRtl.value ? 'ar-EG' : 'en-US', { minimumFractionDigits: 0 });
}
const cards = computed(() => [
    { label: isRtl.value ? 'تسجيلات النوبات (30 يوم)' : 'Seizure entries (30d)', value: props.engagement.seizure_entries ?? 0, sub: (props.engagement.seizure_patients ?? 0) + (isRtl.value ? ' مريض' : ' patients') },
    { label: isRtl.value ? 'تسجيلات الصداع (30 يوم)' : 'Headache entries (30d)', value: props.engagement.headache_entries ?? 0, sub: (props.engagement.headache_patients ?? 0) + (isRtl.value ? ' مريض' : ' patients') },
]);
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'أدوات الأعصاب' : 'Neuro Tools' }}</h2>
        </template>

        <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Diary engagement -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div v-for="(c, i) in cards" :key="i" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="text-sm text-gray-500">{{ c.label }}</div>
                    <div class="mt-1 text-3xl font-bold" :style="{ color: accent }">{{ c.value }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ c.sub }}</div>
                </div>
            </div>

            <!-- Procedures -->
            <section>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ isRtl ? 'الإجراءات (EEG / EMG / حقن)' : 'Procedures (EEG / EMG / injections)' }}</h3>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'النوع' : 'Type' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التقرير' : 'Report' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(p, i) in procedures.data" :key="p.id" class="lst-row hover:bg-sky-50/40" :style="{ '--row-i': i }">
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.patients.show', p.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ p.patient.full_name }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ p.type }}</td>
                                <td class="px-4 py-3 text-gray-600" dir="ltr">{{ p.performed_at || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="p.has_report" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">{{ isRtl ? 'مرفق' : 'Attached' }}</span>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700" dir="ltr">{{ money(p.cost) }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="p.billed" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">{{ isRtl ? 'مفوتر' : 'Billed' }}</span>
                                    <span v-else class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">{{ isRtl ? 'غير مفوتر' : 'Unbilled' }}</span>
                                </td>
                            </tr>
                            <tr v-if="procedures.data.length === 0"><td colspan="6" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد إجراءات' : 'No procedures' }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="procedures.links && procedures.links.length > 3" class="flex flex-wrap gap-1 justify-center mt-3">
                    <template v-for="(l, i) in procedures.links" :key="i">
                        <Link v-if="l.url" :href="l.url" v-html="l.label"
                              class="px-3 py-1.5 rounded-lg text-sm" :class="l.active ? 'text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                              :style="l.active ? { background: accent } : {}" preserve-scroll />
                        <span v-else v-html="l.label" class="px-3 py-1.5 rounded-lg text-sm text-gray-300"></span>
                    </template>
                </div>
            </section>
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
