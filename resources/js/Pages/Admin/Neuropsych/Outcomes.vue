<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    summary: { type: Array, default: () => [] },
    recent: { type: Object, default: () => ({ data: [], links: [] }) },
});

const accent = computed(() => (props.module === 'neurology' ? '#0EA5E9' : '#7C3AED'));
const moduleName = computed(() => {
    const m = isRtl.value
        ? { psychiatry: 'الطب النفسي', neurology: 'طب الأعصاب' }
        : { psychiatry: 'Psychiatry', neurology: 'Neurology' };
    return m[props.module] || props.module;
});

function sevBadge(s) {
    const k = (s || '').toLowerCase();
    if (k.includes('severe') || k.includes('شديد')) return 'bg-red-100 text-red-700';
    if (k.includes('moderate') || k.includes('متوسط')) return 'bg-amber-100 text-amber-700';
    if (k.includes('mild') || k.includes('خفيف')) return 'bg-yellow-100 text-yellow-700';
    if (k.includes('min') || k.includes('بسيط') || k.includes('طبيعي')) return 'bg-emerald-100 text-emerald-700';
    return 'bg-gray-100 text-gray-600';
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ (isRtl ? 'النتائج — ' : 'Outcomes — ') + moduleName }}</h2>
        </template>

        <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Per-scale summary cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="s in summary" :key="s.key" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-gray-800">{{ isRtl ? s.name_ar : s.name_en }}</h3>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full text-white" :style="{ background: accent }">{{ s.total }}</span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ isRtl ? 'معدل التحسّن' : 'Improvement rate' }}</span>
                            <span class="font-semibold text-gray-800">{{ s.improved_pct === null ? '—' : s.improved_pct + '%' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ isRtl ? 'استبيانات مُعلَّمة' : 'Flagged' }}</span>
                            <span class="font-semibold" :class="s.flagged > 0 ? 'text-red-600' : 'text-gray-800'">{{ s.flagged }}</span>
                        </div>
                    </div>
                    <div v-if="Object.keys(s.by_severity || {}).length" class="mt-4 flex flex-wrap gap-1.5">
                        <span v-for="(c, sev) in s.by_severity" :key="sev" class="text-xs font-medium px-2 py-1 rounded-full" :class="sevBadge(sev)">{{ sev }}: {{ c }}</span>
                    </div>
                </div>
                <div v-if="summary.length === 0" class="text-gray-400 text-sm py-8">{{ isRtl ? 'لا توجد مقاييس مسجّلة' : 'No scale data yet' }}</div>
            </div>

            <!-- Recent results -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">{{ isRtl ? 'أحدث النتائج' : 'Recent results' }}</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المقياس' : 'Scale' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الدرجة' : 'Score' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الشدّة' : 'Severity' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(r, i) in recent.data" :key="r.id" class="lst-row hover:bg-gray-50/60" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', r.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ r.patient.full_name }}</Link>
                            </td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ r.scale_key }}</td>
                            <td class="px-4 py-3 text-gray-800 font-medium" dir="ltr">
                                {{ r.score }}
                                <span v-if="r.flag" class="ms-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ isRtl ? 'مُعلَّم' : 'Flag' }}</span>
                            </td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="sevBadge(r.severity)">{{ r.severity || '—' }}</span></td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ r.taken_at || '—' }}</td>
                        </tr>
                        <tr v-if="recent.data.length === 0"><td colspan="5" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="recent.links && recent.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in recent.links" :key="i">
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
