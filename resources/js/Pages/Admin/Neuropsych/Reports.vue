<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ module: String, byMonth: Array, totalEncounters: Number, revenue: Number, completedCourses: Number });
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }
const title = computed(() => props.module === 'neurology' ? t('Neurology', 'طب الأعصاب') : t('Psychiatry', 'الطب النفسي'));
const accent = computed(() => props.module === 'neurology' ? '#0EA5E9' : '#7C3AED');
const maxEnc = computed(() => Math.max(1, ...props.byMonth.map(m => m.encounters)));

const cards = computed(() => [
    [t('Encounters (6 mo)', 'لقاءات (6 أشهر)'), props.totalEncounters],
    [t('Revenue (6 mo)', 'الإيراد (6 أشهر)'), props.revenue],
    [t('Completed courses', 'دورات مكتملة'), props.completedCourses],
]);
</script>

<template>
    <div class="space-y-6">
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <h1 class="text-2xl font-bold">{{ title }} — {{ t('Reports', 'التقارير') }}</h1>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div v-for="([label, value], i) in cards" :key="i" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="text-2xl font-extrabold" :style="{ color: accent }">{{ value }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ label }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-700 mb-4">{{ t('Encounters by month', 'اللقاءات حسب الشهر') }}</h2>
            <div class="space-y-2">
                <div v-for="m in byMonth" :key="m.month" class="flex items-center gap-3">
                    <span class="text-xs text-slate-500 w-16">{{ m.month }}</span>
                    <div class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div class="h-full rounded-full" :style="{ width: ((m.encounters / maxEnc) * 100) + '%', background: accent }"></div>
                    </div>
                    <span class="text-xs font-bold text-slate-700 w-8 text-end">{{ m.encounters }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
