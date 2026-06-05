<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    rows: { type: Object, default: () => ({ data: [], links: [] }) },
});

const accent = computed(() => (props.module === 'neurology' ? '#0EA5E9' : '#7C3AED'));

function riskBadge(level) {
    return { high: 'bg-red-100 text-red-700', moderate: 'bg-amber-100 text-amber-700' }[level] || 'bg-gray-100 text-gray-600';
}
function riskText(level) {
    const m = isRtl.value ? { high: 'مرتفع', moderate: 'متوسط' } : { high: 'High', moderate: 'Moderate' };
    return m[level] || level;
}
function docName(d) {
    return isRtl.value ? d?.name_ar : d?.name_en;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'سجل المخاطر' : 'Risk Register' }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-start gap-3 rounded-2xl bg-red-50 border border-red-100 p-4">
                <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <p class="text-sm text-red-700">{{ isRtl ? 'بيانات حسّاسة — المرضى ذوو الخطر النشط (متوسط/مرتفع). تأكّد من وجود خطة سلامة لكل حالة.' : 'Sensitive — patients with active moderate/high risk. Confirm a safety plan exists for each.' }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'مستوى الخطر' : 'Risk level' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الأداة' : 'Tool' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'خطة السلامة' : 'Safety plan' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'تاريخ التقييم' : 'Assessed' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(r, i) in rows.data" :key="r.id" class="lst-row hover:bg-red-50/30" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', r.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ r.patient.full_name }}</Link>
                                <div class="text-xs text-gray-400">{{ r.patient.file_number || r.patient.phone }}</div>
                            </td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="riskBadge(r.risk_level)">{{ riskText(r.risk_level) }}</span></td>
                            <td class="px-4 py-3 text-gray-600 uppercase" dir="ltr">{{ r.tool }}</td>
                            <td class="px-4 py-3">
                                <span v-if="r.has_safety_plan" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">{{ isRtl ? 'موجودة' : 'On file' }}</span>
                                <span v-else class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-700">{{ isRtl ? 'ناقصة' : 'Missing' }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ docName(r.doctor) || '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ r.assessed_at || '—' }}</td>
                        </tr>
                        <tr v-if="rows.data.length === 0"><td colspan="6" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد مخاطر نشطة' : 'No active risks' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="rows.links && rows.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in rows.links" :key="i">
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
