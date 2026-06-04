<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ module: String, stats: Object });
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }
const title = computed(() => props.module === 'neurology' ? t('Neurology', 'طب الأعصاب') : t('Psychiatry', 'الطب النفسي'));
const accent = computed(() => props.module === 'neurology' ? '#0EA5E9' : '#7C3AED');

const cards = computed(() => [
    [t('Encounters this month', 'لقاءات هذا الشهر'), props.stats.encounters_this_month, '#1B365D'],
    [t('Active high risk', 'خطر مرتفع نشط'), props.stats.active_high_risk, '#DC2626'],
    [t('Monitoring due', 'مراقبة مستحقّة'), props.stats.monitoring_due, '#D97706'],
]);
</script>

<template>
    <div class="space-y-6">
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <h1 class="text-2xl font-bold">{{ title }}</h1>
            <p class="text-white/70 text-sm mt-1">{{ t('Module overview', 'نظرة عامة على الوحدة') }}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div v-for="([label, value, color], i) in cards" :key="i" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="text-3xl font-extrabold" :style="{ color }">{{ value }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ label }}</div>
            </div>
        </div>
    </div>
</template>
