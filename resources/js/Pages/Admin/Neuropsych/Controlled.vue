<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    rx: { type: Object, default: () => ({ data: [], links: [] }) },
});

const accent = computed(() => (props.module === 'neurology' ? '#0EA5E9' : '#7C3AED'));

function statusBadge(s) {
    return {
        draft: 'bg-gray-100 text-gray-600',
        signed: 'bg-blue-100 text-blue-700',
        submitted: 'bg-indigo-100 text-indigo-700',
        authorized: 'bg-emerald-100 text-emerald-700',
        dispensed: 'bg-teal-100 text-teal-700',
        cancelled: 'bg-red-100 text-red-700',
    }[s] || 'bg-gray-100 text-gray-600';
}
function statusText(s) {
    const m = isRtl.value
        ? { draft: 'مسودة', signed: 'موقّعة', submitted: 'مُرسلة', authorized: 'معتمدة', dispensed: 'مصروفة', cancelled: 'ملغاة' }
        : { draft: 'Draft', signed: 'Signed', submitted: 'Submitted', authorized: 'Authorized', dispensed: 'Dispensed', cancelled: 'Cancelled' };
    return m[s] || s;
}
function docName(d) {
    return isRtl.value ? d?.name_ar : d?.name_en;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'سجل المواد الخاضعة' : 'Controlled Substances Audit' }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-start gap-3 rounded-2xl bg-purple-50 border border-purple-100 p-4">
                <svg class="w-5 h-5 mt-0.5 shrink-0" :style="{ color: accent }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-purple-800">{{ isRtl ? 'سجل تدقيق للروشتات الخاضعة — للمراجعة والامتثال فقط (للقراءة).' : 'Audit log of controlled prescriptions — review/compliance only (read-only).' }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الدواء' : 'Drug' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الجدول' : 'Schedule' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المرجع' : 'Ref' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(p, i) in rx.data" :key="p.id" class="lst-row hover:bg-purple-50/30" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', p.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ p.patient.full_name }}</Link>
                                <div class="text-xs text-gray-400">{{ p.patient.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ p.drug }}
                                <div class="text-xs text-gray-400">{{ p.quantity }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ p.schedule || '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ docName(p.doctor) || '—' }}</td>
                            <td class="px-4 py-3 text-gray-500" dir="ltr">{{ p.external_ref || '—' }}</td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="statusBadge(p.status)">{{ statusText(p.status) }}</span></td>
                        </tr>
                        <tr v-if="rx.data.length === 0"><td colspan="6" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد روشتات خاضعة' : 'No controlled prescriptions' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="rx.links && rx.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in rx.links" :key="i">
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
