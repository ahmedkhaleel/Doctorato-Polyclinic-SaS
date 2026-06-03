<script setup>
import { computed, reactive, watch } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ logs: Object, filters: Object });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const f = reactive({ feature: props.filters?.feature || '', status: props.filters?.status || '' });
let timer = null;
watch(f, () => {
    clearTimeout(timer);
    timer = setTimeout(() => router.get('/admin/ai/logs', { ...f }, { preserveState: true, replace: true, preserveScroll: true }), 300);
});

const statusStyle = {
    success: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
    blocked: 'bg-amber-100 text-amber-700',
};

const tabs = [
    { href: '/admin/ai/settings', ar: 'الإعدادات', en: 'Settings' },
    { href: '/admin/ai/features', ar: 'الميزات', en: 'Features' },
    { href: '/admin/ai/prompts', ar: 'القوالب', en: 'Prompts' },
    { href: '/admin/ai/usage', ar: 'الاستهلاك', en: 'Usage' },
    { href: '/admin/ai/logs', ar: 'السجلات', en: 'Logs' },
];
</script>

<template>
    <AdminLayout :title="t('الذكاء الاصطناعي — السجلات', 'AI — Logs')">
        <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/logs' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <div class="flex flex-wrap gap-3">
                <input v-model="f.feature" type="text" :placeholder="t('الميزة', 'Feature')" class="rounded-lg border-gray-300 text-sm" />
                <select v-model="f.status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">{{ t('كل الحالات', 'All statuses') }}</option>
                    <option value="success">{{ t('نجاح', 'Success') }}</option>
                    <option value="failed">{{ t('فشل', 'Failed') }}</option>
                    <option value="blocked">{{ t('محجوب', 'Blocked') }}</option>
                </select>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs bg-gray-50">
                            <th class="text-start px-4 py-2">{{ t('التاريخ', 'Date') }}</th>
                            <th class="text-start px-4 py-2">{{ t('الميزة', 'Feature') }}</th>
                            <th class="text-start px-4 py-2">{{ t('النموذج', 'Model') }}</th>
                            <th class="text-start px-4 py-2">{{ t('توكنز', 'Tokens') }}</th>
                            <th class="text-start px-4 py-2">{{ t('التكلفة', 'Cost') }}</th>
                            <th class="text-start px-4 py-2">{{ t('الزمن', 'Latency') }}</th>
                            <th class="text-start px-4 py-2">{{ t('الحالة', 'Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="logs.data.length === 0"><td colspan="7" class="text-center text-gray-400 py-8">{{ t('لا توجد سجلات.', 'No logs yet.') }}</td></tr>
                        <tr v-for="(log, i) in logs.data" :key="log.id" class="lst-row border-t border-gray-100" :style="{ '--row-i': i }">
                            <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ new Date(log.created_at).toLocaleString() }}</td>
                            <td class="px-4 py-2 font-mono">{{ log.feature }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ log.model || '—' }}</td>
                            <td class="px-4 py-2">{{ (log.prompt_tokens || 0) + (log.completion_tokens || 0) }}</td>
                            <td class="px-4 py-2">${{ Number(log.cost_usd).toFixed(4) }}</td>
                            <td class="px-4 py-2">{{ log.latency_ms }}ms</td>
                            <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded" :class="statusStyle[log.status] || 'bg-gray-100 text-gray-600'">{{ log.status }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="logs.links" class="flex flex-wrap gap-1 justify-center">
                <Link v-for="link in logs.links" :key="link.label" :href="link.url || '#'"
                    v-html="link.label"
                    class="px-3 py-1.5 text-sm rounded-lg"
                    :class="[link.active ? 'bg-[#1B365D] text-white' : 'bg-white border border-gray-200 text-gray-600', !link.url ? 'opacity-40 pointer-events-none' : '']" />
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
