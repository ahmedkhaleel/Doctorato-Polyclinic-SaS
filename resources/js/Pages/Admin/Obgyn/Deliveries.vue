<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

defineProps({
    deliveries: { type: Object, default: () => ({ data: [], links: [] }) },
});

const accent = '#DB2777';

function modeText(m) {
    const map = isRtl.value
        ? { vaginal: 'طبيعية', cesarean: 'قيصرية', assisted: 'بمساعدة' }
        : { vaginal: 'Vaginal', cesarean: 'Cesarean', assisted: 'Assisted' };
    return map[m] || m || '—';
}
function outcomeBadge(o) {
    const k = (o || '').toLowerCase();
    if (k.includes('live') || k.includes('حي')) return 'bg-emerald-100 text-emerald-700';
    if (k.includes('still') || k.includes('وفاة') || k.includes('متوفّ')) return 'bg-red-100 text-red-700';
    return 'bg-gray-100 text-gray-600';
}
function docName(d) {
    return isRtl.value ? d?.name_ar : d?.name_en;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'سجل الولادات' : 'Deliveries' }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريضة' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'النوع' : 'Mode' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الوزن' : 'Weight' }}</th>
                            <th class="text-start px-4 py-3 font-medium">Apgar</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيبة' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'النتيجة' : 'Outcome' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(d, i) in deliveries.data" :key="d.id" class="lst-row hover:bg-pink-50/30" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link v-if="d.patient.id" :href="route('admin.patients.show', d.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ d.patient.full_name }}</Link>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ d.delivery_date || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ modeText(d.delivery_mode) }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ d.baby_weight_grams ? d.baby_weight_grams + 'g' : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ d.apgar || '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ docName(d.doctor) || '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="d.outcome" class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="outcomeBadge(d.outcome)">{{ d.outcome }}</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                        </tr>
                        <tr v-if="deliveries.data.length === 0"><td colspan="7" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد ولادات' : 'No deliveries' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="deliveries.links && deliveries.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in deliveries.links" :key="i">
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
