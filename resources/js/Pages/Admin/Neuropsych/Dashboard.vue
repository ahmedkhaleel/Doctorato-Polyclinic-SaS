<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ module: String, stats: Object, canSeeSensitive: { type: Boolean, default: false } });
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }
const title = computed(() => props.module === 'neurology' ? t('Neurology', 'طب الأعصاب') : t('Psychiatry', 'الطب النفسي'));
const accent = computed(() => props.module === 'neurology' ? '#0EA5E9' : '#7C3AED');

function money(v) {
    return Number(v || 0).toLocaleString(isRtl.value ? 'ar-EG' : 'en-US', { minimumFractionDigits: 0 });
}

// KPI tiles link into the relevant oversight screen. The high-risk tile is
// only shown to admins with view_sensitive (its target route is sensitive).
const cards = computed(() => [
    { label: t('Active cases', 'حالات نشطة'), value: props.stats.active_cases ?? 0, color: '#1B365D', href: `/admin/${props.module}/cases` },
    { label: t('Encounters this month', 'لقاءات هذا الشهر'), value: props.stats.encounters_this_month ?? 0, color: accent.value, href: `/admin/${props.module}/encounters` },
    ...(props.canSeeSensitive ? [{ label: t('Active high risk', 'خطر مرتفع نشط'), value: props.stats.active_high_risk ?? 0, color: '#DC2626', href: `/admin/${props.module}/risk` }] : []),
    { label: t('Monitoring due', 'مراقبة مستحقّة'), value: props.stats.monitoring_due ?? 0, color: '#D97706', href: `/admin/${props.module}/medications` },
    { label: t('Revenue this month', 'إيراد هذا الشهر'), value: money(props.stats.revenue_this_month), color: '#059669', href: `/admin/${props.module}/reports` },
]);
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <h1 class="text-2xl font-bold">{{ title }}</h1>
            <p class="text-white/70 text-sm mt-1">{{ t('Module overview', 'نظرة عامة على الوحدة') }}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <Link v-for="(c, i) in cards" :key="i" :href="c.href"
                  class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 block">
                <div class="text-3xl font-extrabold" :style="{ color: c.color }">{{ c.value }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ c.label }}</div>
            </Link>
        </div>
    </div>
</template>
