<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    overdue: { type: Array, default: () => [] },
    plans: { type: Object, default: () => ({ data: [], links: [] }) },
});

const accent = computed(() => (props.module === 'neurology' ? '#0EA5E9' : '#7C3AED'));
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'الأدوية والمراقبة' : 'Medications & Monitoring' }}</h2>
        </template>

        <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Overdue monitoring queue -->
            <section>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ isRtl ? 'مراقبة متأخّرة' : 'Overdue monitoring' }}</h3>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الدواء' : 'Drug' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الفحص' : 'Test' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'تاريخ الاستحقاق' : 'Due' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التأخّر' : 'Overdue' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(m, i) in overdue" :key="m.id" class="lst-row hover:bg-amber-50/40" :style="{ '--row-i': i }">
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.patients.show', m.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ m.patient.full_name }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ m.drug || '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ m.type }}</td>
                                <td class="px-4 py-3 text-gray-600" dir="ltr">{{ m.due_at }}</td>
                                <td class="px-4 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-700">{{ isRtl ? `${m.days_overdue} يوم` : `${m.days_overdue}d` }}</span></td>
                            </tr>
                            <tr v-if="overdue.length === 0"><td colspan="5" class="text-center text-gray-400 py-8">{{ isRtl ? 'لا توجد مراقبة متأخّرة' : 'Nothing overdue' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Active medication plans -->
            <section>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ isRtl ? 'خطط الأدوية النشطة' : 'Active medication plans' }}</h3>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الدواء' : 'Drug' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الجرعة' : 'Dose' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'التكرار' : 'Frequency' }}</th>
                                <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'بدأ في' : 'Started' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(p, i) in plans.data" :key="p.id" class="lst-row hover:bg-gray-50/60" :style="{ '--row-i': i }">
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.patients.show', p.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ p.patient.full_name }}</Link>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ p.drug }}
                                    <span v-if="p.is_controlled" class="ms-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">{{ isRtl ? 'خاضع' : 'Controlled' }}</span>
                                    <div class="text-xs text-gray-400">{{ p.drug_class }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600" dir="ltr">{{ p.dose || '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ p.frequency || '—' }}</td>
                                <td class="px-4 py-3 text-gray-600" dir="ltr">{{ p.started_at || '—' }}</td>
                            </tr>
                            <tr v-if="plans.data.length === 0"><td colspan="5" class="text-center text-gray-400 py-8">{{ isRtl ? 'لا توجد خطط نشطة' : 'No active plans' }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="plans.links && plans.links.length > 3" class="flex flex-wrap gap-1 justify-center mt-3">
                    <template v-for="(l, i) in plans.links" :key="i">
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
