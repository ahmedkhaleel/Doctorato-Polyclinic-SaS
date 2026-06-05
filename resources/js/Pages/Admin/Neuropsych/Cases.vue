<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    cases: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    canSeeSensitive: { type: Boolean, default: false },
});

const accent = computed(() => (props.module === 'neurology' ? '#0EA5E9' : '#7C3AED'));
const moduleName = computed(() => {
    const m = isRtl.value
        ? { psychiatry: 'الطب النفسي', neurology: 'طب الأعصاب' }
        : { psychiatry: 'Psychiatry', neurology: 'Neurology' };
    return m[props.module] || props.module;
});

const search = ref(props.filters.search || '');
function apply() {
    router.get(route(`admin.${props.module}.cases`), { search: search.value }, { preserveState: true, replace: true });
}

function riskBadge(level) {
    return { high: 'bg-red-100 text-red-700', moderate: 'bg-amber-100 text-amber-700', low: 'bg-yellow-100 text-yellow-700' }[level] || 'bg-gray-100 text-gray-500';
}
function riskText(level) {
    const m = isRtl.value
        ? { high: 'خطر مرتفع', moderate: 'خطر متوسط', low: 'خطر منخفض' }
        : { high: 'High risk', moderate: 'Moderate risk', low: 'Low risk' };
    return m[level] || level;
}
function fmtDate(d) {
    return d || '—';
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ (isRtl ? 'حالات ' : 'Cases — ') + moduleName }}</h2>
        </template>

        <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center gap-2 flex-wrap">
                <input v-model="search" @keyup.enter="apply" type="text" :placeholder="isRtl ? 'بحث بالاسم أو الهاتف أو رقم الملف…' : 'Search name, phone, or file no…'"
                       class="flex-1 min-w-[220px] rounded-xl border-gray-200 text-sm py-2.5 focus:ring-2"
                       :style="{ '--tw-ring-color': accent }" />
                <button @click="apply" class="px-4 py-2.5 rounded-xl text-sm font-medium text-white transition" :style="{ background: accent }">
                    {{ isRtl ? 'بحث' : 'Search' }}
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'الشكوى الرئيسية' : 'Chief complaint' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'عدد اللقاءات' : 'Encounters' }}</th>
                            <th class="text-start px-4 py-3 font-medium">{{ isRtl ? 'آخر لقاء' : 'Last visit' }}</th>
                            <th v-if="canSeeSensitive" class="text-start px-4 py-3 font-medium">{{ isRtl ? 'المخاطر' : 'Risk' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(c, i) in cases.data" :key="c.id" class="lst-row hover:bg-gray-50/60" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <Link :href="route('admin.patients.show', c.id)" class="font-medium hover:underline" :style="{ color: accent }">{{ c.full_name }}</Link>
                                <div class="text-xs text-gray-400">{{ c.file_number || c.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-[280px] truncate">{{ c.chief_complaint || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 font-medium">{{ c.encounters_count }}</td>
                            <td class="px-4 py-3 text-gray-600" dir="ltr">{{ fmtDate(c.last_encounter) }}</td>
                            <td v-if="canSeeSensitive" class="px-4 py-3">
                                <span v-if="c.risk_level" class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="riskBadge(c.risk_level)">{{ riskText(c.risk_level) }}</span>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>
                        </tr>
                        <tr v-if="cases.data.length === 0"><td :colspan="canSeeSensitive ? 5 : 4" class="text-center text-gray-400 py-10">{{ isRtl ? 'لا توجد حالات' : 'No cases' }}</td></tr>
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
