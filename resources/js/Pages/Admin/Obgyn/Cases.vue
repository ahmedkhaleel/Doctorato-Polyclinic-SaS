<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    cases: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const accent = '#DB2777';

const search = ref(props.filters.search || '');
function apply() {
    router.get(route('admin.obgyn.cases'), { search: search.value }, { preserveState: true, replace: true });
}
function docName(d) {
    return isRtl.value ? d?.name_ar : d?.name_en;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'حالات النساء والتوليد' : 'OB/GYN Cases' }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center gap-2 flex-wrap">
                <input v-model="search" @keyup.enter="apply" type="text" :placeholder="isRtl ? 'بحث بالاسم أو الهاتف أو رقم الملف…' : 'Search name, phone, or file no…'"
                       class="flex-1 min-w-[220px] rounded-xl border-gray-200 text-sm py-2.5 focus:border-pink-400 focus:ring-pink-400" />
                <button @click="apply" class="px-4 py-2.5 rounded-xl text-sm font-medium text-white transition" :style="{ background: accent }">
                    {{ isRtl ? 'بحث' : 'Search' }}
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريضة' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الطبيبة' : 'Doctor' }}</th>
                            <th class="text-start px-4 py-3 font-medium">G / P</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'فصيلة الدم' : 'Blood' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'حمل نشط' : 'Active pregnancy' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(c, i) in cases.data" :key="c.patient.id" class="lst-row hover:bg-pink-50/30" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', c.patient.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ c.patient.full_name }}</Link>
                                <div class="text-xs text-gray-400">{{ c.patient.file_number || c.patient.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ docName(c.doctor) || '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ (c.gravida ?? '—') }} / {{ (c.para ?? '—') }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ c.blood_group || '—' }}</td>
                            <td class="px-4 py-3">
                                <span v-if="c.has_active_pregnancy" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-rose-100 text-rose-700">{{ isRtl ? 'نعم' : 'Yes' }}</span>
                                <span v-else class="text-xs text-gray-400">{{ isRtl ? 'لا' : 'No' }}</span>
                            </td>
                        </tr>
                        <tr v-if="cases.data.length === 0"><td colspan="5" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد حالات' : 'No cases' }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="cases.links && cases.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <template v-for="(l, i) in cases.links" :key="i">
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
