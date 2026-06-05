<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    queue: { type: Array, default: () => [] },
    reminderDays: { type: Number, default: 7 },
});

const accent = '#DB2777';

function docName(d) {
    return isRtl.value ? d?.name_ar : d?.name_en;
}
function dueLabel(row) {
    if (row.overdue) {
        const n = Math.abs(row.days_until);
        return isRtl.value ? `متأخّر ${n} يوم` : `${n}d overdue`;
    }
    if (row.days_until === 0) return isRtl.value ? 'اليوم' : 'Today';
    return isRtl.value ? `خلال ${row.days_until} يوم` : `in ${row.days_until}d`;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'طابور متابعة الحمل' : 'Antenatal Care Queue' }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <p class="text-sm text-gray-500">
                {{ isRtl ? `الحوامل المستحقّة للمتابعة خلال ${reminderDays} يوم أو المتأخّرات.` : `Active pregnancies with antenatal follow-up due within ${reminderDays} days or overdue.` }}
            </p>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريضة' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيبة' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'آخر زيارة' : 'Last visit' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الموعد القادم' : 'Next visit' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(r, i) in queue" :key="r.pregnancy_id" class="lst-row hover:bg-pink-50/30" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', r.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ r.patient.full_name }}</Link>
                                <div class="text-xs text-gray-400">{{ r.patient.file_number || r.patient.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ docName(r.doctor) || '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ r.last_visit || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700" dir="ltr">{{ r.next_visit_date }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="r.overdue ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'">{{ dueLabel(r) }}</span>
                            </td>
                        </tr>
                        <tr v-if="queue.length === 0"><td colspan="5" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد متابعات مستحقّة' : 'No follow-ups due' }}</td></tr>
                    </tbody>
                </table>
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
