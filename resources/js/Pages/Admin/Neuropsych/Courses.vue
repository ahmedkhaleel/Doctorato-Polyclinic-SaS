<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    courses: { type: Object, default: () => ({ data: [], links: [] }) },
});

const accent = computed(() => (props.module === 'neurology' ? '#0EA5E9' : '#7C3AED'));

function typeText(t) {
    const m = { ect: 'ECT', rtms: 'rTMS', ketamine: isRtl.value ? 'كيتامين' : 'Ketamine' };
    return m[t] || t;
}
function statusBadge(s) {
    return { active: 'bg-blue-100 text-blue-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-red-100 text-red-700' }[s] || 'bg-gray-100 text-gray-600';
}
function docName(d) {
    return isRtl.value ? d?.name_ar : d?.name_en;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'الدورات العلاجية' : 'Treatment Courses' }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'النوع' : 'Type' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التقدّم' : 'Progress' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الموافقة' : 'Consent' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(c, i) in courses.data" :key="c.id" class="lst-row hover:bg-gray-50/60" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', c.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ c.patient.full_name }}</Link>
                            </td>
                            <td class="px-4 py-3 text-gray-700 font-medium" dir="ltr">{{ typeText(c.type) }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ c.completed_sessions }} / {{ c.planned_sessions || '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="!c.consent_required" class="text-xs text-gray-400">{{ isRtl ? 'غير مطلوبة' : 'N/A' }}</span>
                                <span v-else-if="c.consent_ok" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">{{ isRtl ? 'موقّعة' : 'Signed' }}</span>
                                <span v-else class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-700">{{ isRtl ? 'ناقصة' : 'Missing' }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ docName(c.doctor) || '—' }}</td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="statusBadge(c.status)">{{ c.status }}</span></td>
                        </tr>
                        <tr v-if="courses.data.length === 0"><td colspan="6" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد دورات' : 'No courses' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="courses.links && courses.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in courses.links" :key="i">
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
