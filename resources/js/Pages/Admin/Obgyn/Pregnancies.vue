<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

// AdminLayout is applied via the <AdminLayout> wrapper below — do NOT also
// set it via defineOptions, or the layout (and its header) renders twice.

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    pregnancies: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
function apply() {
    router.get(route('admin.obgyn.pregnancies'), { search: search.value, status: status.value }, { preserveState: true, replace: true });
}
function statusBadge(s) {
    return { active: 'bg-rose-100 text-rose-700', delivered: 'bg-emerald-100 text-emerald-700' }[s] || 'bg-gray-100 text-gray-600';
}
function statusText(s) {
    const m = isRtl.value ? { active: 'نشط', delivered: 'وُلد', miscarried: 'إجهاض', terminated: 'إنهاء' } : { active: 'Active', delivered: 'Delivered', miscarried: 'Miscarried', terminated: 'Terminated' };
    return m[s] || s;
}
const statuses = computed(() => [
    { k: 'all', l: isRtl.value ? 'الكل' : 'All' },
    { k: 'active', l: isRtl.value ? 'نشط' : 'Active' },
    { k: 'delivered', l: isRtl.value ? 'وُلد' : 'Delivered' },
]);
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'ملفات الحمل' : 'Pregnancy Files' }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center gap-2 flex-wrap">
                <input v-model="search" @keyup.enter="apply" type="text" :placeholder="isRtl ? 'بحث…' : 'Search…'"
                       class="flex-1 min-w-[200px] rounded-xl border-gray-200 text-sm py-2.5 focus:border-rose-400 focus:ring-rose-400" />
                <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
                    <button v-for="s in statuses" :key="s.k" @click="status = s.k; apply()"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition" :class="status === s.k ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-500'">{{ s.l }}</button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريضة' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيبة' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'عمر الحمل' : 'GA' }}</th>
                            <th class="text-start px-4 py-3 font-medium">EDD</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(p, i) in pregnancies.data" :key="p.id" class="lst-row hover:bg-rose-50/30" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ p.patient?.full_name }}</div>
                                <div class="text-xs text-gray-400">{{ p.patient?.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ isRtl ? p.doctor?.name_ar : p.doctor?.name_en }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ p.ga_label || '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ p.edd || '—' }}</td>
                            <td class="px-4 py-3"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="statusBadge(p.status)">{{ statusText(p.status) }}</span></td>
                        </tr>
                        <tr v-if="pregnancies.data.length === 0"><td colspan="5" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد ملفات' : 'No pregnancy files' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pregnancies.links && pregnancies.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in pregnancies.links" :key="i">
                    <Link v-if="l.url" :href="l.url" v-html="l.label"
                          class="px-3 py-1.5 rounded-lg text-sm" :class="l.active ? 'bg-[#1B365D] text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'" preserve-scroll />
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
