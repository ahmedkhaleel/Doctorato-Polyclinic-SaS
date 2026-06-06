<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UiEmptyState from '@/Components/Ui/EmptyState.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    sections: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');
function apply() {
    router.get(route('admin.reports.outcomes'), { from: from.value, to: to.value }, { preserveState: true, replace: true });
}
function exportCsv() {
    const q = new URLSearchParams({ from: from.value || '', to: to.value || '' }).toString();
    window.location.href = route('admin.reports.outcomes.export') + '?' + q;
}

const accent = (m) => ({
    dental: '#0EA5E9', derma: '#0EA5E9', pediatric: '#F59E0B',
    obgyn: '#DB2777', psychiatry: '#4F46E5', neurology: '#0D9488',
}[m] || '#C4A265');
const title = (s) => isRtl.value ? s.title_ar : s.title_en;
const label = (m) => isRtl.value ? m.label_ar : m.label_en;
const hint = (m) => isRtl.value ? m.hint_ar : m.hint_en;
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="w-2 h-8 rounded-full bg-[#C4A265]"></span>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'المخرجات السريرية والجودة' : 'Clinical Outcomes & Quality' }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'مؤشرات مجمّعة عبر التخصصات المُفعّلة' : 'Aggregated indicators across enabled specialties' }}</p>
                </div>
            </div>
            <button @click="exportCsv" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ isRtl ? 'تصدير CSV' : 'Export CSV' }}
            </button>
        </div>

        <!-- Date window -->
        <div class="flex items-end gap-2 flex-wrap">
            <label class="text-xs font-semibold text-gray-500">{{ isRtl ? 'من' : 'From' }}
                <input v-model="from" type="date" class="mt-1 block rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-200" />
            </label>
            <label class="text-xs font-semibold text-gray-500">{{ isRtl ? 'إلى' : 'To' }}
                <input v-model="to" type="date" class="mt-1 block rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-200" />
            </label>
            <button @click="apply" class="px-4 py-2 rounded-xl text-sm font-semibold bg-[#1B365D] text-white hover:opacity-90">{{ isRtl ? 'تطبيق' : 'Apply' }}</button>
        </div>

        <!-- Sections -->
        <div v-if="sections.length" class="space-y-5">
            <section v-for="s in sections" :key="s.module" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                    <span class="w-2 h-6 rounded-full" :style="{ background: accent(s.module) }"></span>
                    <h2 class="text-sm font-bold text-gray-700">{{ title(s) }}</h2>
                </div>
                <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="(m, i) in s.metrics" :key="i" class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                        <p class="text-2xl font-bold text-gray-800">{{ m.value }}</p>
                        <p class="text-xs font-semibold text-gray-600 mt-1">{{ label(m) }}</p>
                        <p v-if="hint(m)" class="text-[11px] text-gray-400 mt-0.5">{{ hint(m) }}</p>
                    </div>
                </div>
            </section>
        </div>
        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <UiEmptyState icon="chart"
                :title="isRtl ? 'لا بيانات مخرجات بعد' : 'No outcome data yet'"
                :description="isRtl ? 'فعّل تخصصاً وأكمل بعض الزيارات لتظهر المؤشرات.' : 'Enable a specialty and complete some visits to populate indicators.'" />
        </div>
    </div>
</template>
