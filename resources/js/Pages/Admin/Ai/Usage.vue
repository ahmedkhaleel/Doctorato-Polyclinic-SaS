<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    monthToDateUsd: Number,
    budgetUsd: Number,
    overBudget: Boolean,
    byFeature: Array,
    totalCalls: Number,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const pct = computed(() => props.budgetUsd > 0 ? Math.min(100, Math.round((props.monthToDateUsd / props.budgetUsd) * 100)) : 0);

const tabs = [
    { href: '/admin/ai/settings', ar: 'الإعدادات', en: 'Settings' },
    { href: '/admin/ai/features', ar: 'الميزات', en: 'Features' },
    { href: '/admin/ai/prompts', ar: 'القوالب', en: 'Prompts' },
    { href: '/admin/ai/usage', ar: 'الاستهلاك', en: 'Usage' },
    { href: '/admin/ai/logs', ar: 'السجلات', en: 'Logs' },
];
</script>

<template>
    <AdminLayout :title="t('الذكاء الاصطناعي — الاستهلاك', 'AI — Usage')">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/usage' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <div class="grid sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">{{ t('المنفق هذا الشهر', 'Spent this month') }}</p>
                    <p class="text-2xl font-bold text-[#1B365D]">${{ monthToDateUsd }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">{{ t('الميزانية', 'Budget') }}</p>
                    <p class="text-2xl font-bold text-gray-800">${{ budgetUsd }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-sm text-gray-500">{{ t('عدد النداءات', 'Total calls') }}</p>
                    <p class="text-2xl font-bold text-gray-800">{{ totalCalls }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">{{ t('استهلاك الميزانية', 'Budget usage') }}</span>
                    <span :class="overBudget ? 'text-red-600 font-bold' : 'text-gray-600'">{{ pct }}%</span>
                </div>
                <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all" :class="overBudget ? 'bg-red-500' : 'bg-[#C4A265]'" :style="{ width: pct + '%' }"></div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 font-bold text-gray-800 text-sm">{{ t('حسب الميزة', 'By feature') }}</div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs">
                            <th class="text-start px-5 py-2">{{ t('الميزة', 'Feature') }}</th>
                            <th class="text-start px-5 py-2">{{ t('نداءات', 'Calls') }}</th>
                            <th class="text-start px-5 py-2">{{ t('توكنز', 'Tokens') }}</th>
                            <th class="text-start px-5 py-2">{{ t('التكلفة', 'Cost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="byFeature.length === 0"><td colspan="4" class="text-center text-gray-400 py-6">{{ t('لا توجد بيانات هذا الشهر.', 'No usage this month.') }}</td></tr>
                        <tr v-for="r in byFeature" :key="r.feature" class="border-t border-gray-100">
                            <td class="px-5 py-2 font-mono">{{ r.feature }}</td>
                            <td class="px-5 py-2">{{ r.calls }}</td>
                            <td class="px-5 py-2">{{ r.tokens }}</td>
                            <td class="px-5 py-2">${{ Number(r.cost).toFixed(4) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
