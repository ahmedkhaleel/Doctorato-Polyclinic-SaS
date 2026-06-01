<script setup>
import { computed, reactive } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ features: Array });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const rows = reactive(props.features.map((f) => ({
    id: f.id, key: f.key, label_ar: f.label_ar, label_en: f.label_en, group: f.group,
    enabled: !!f.enabled, model_override: f.model_override || '',
})));

const groups = computed(() => {
    const g = {};
    rows.forEach((r) => { (g[r.group] = g[r.group] || []).push(r); });
    return g;
});

const groupLabel = (k) => ({
    wave1: t('الموجة 1 — مكاسب سريعة', 'Wave 1 — Quick wins'),
    patient: t('الموجة 2 — تفاعل المريض', 'Wave 2 — Patient'),
    clinical: t('الموجة 3 — سريري', 'Wave 3 — Clinical'),
    vision: t('الموجة 4 — رؤية/صوت', 'Wave 4 — Vision/Voice'),
}[k] || k);

const models = ['', 'gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini', 'gpt-4.1'];

const save = () => {
    router.post('/admin/ai/features', { features: rows }, { preserveScroll: true });
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
    <AdminLayout :title="t('الذكاء الاصطناعي — الميزات', 'AI — Features')">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/features' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <p class="text-sm text-gray-500">{{ t('فعّل أو عطّل كل ميزة على حدة، واختر نموذجًا مخصّصًا لها عند الحاجة.', 'Toggle each feature and optionally override its model.') }}</p>

            <div v-for="(items, g) in groups" :key="g" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 font-bold text-gray-800 text-sm">{{ groupLabel(g) }}</div>
                <div v-for="row in items" :key="row.id" class="flex items-center justify-between px-5 py-3 border-b border-gray-100 last:border-0">
                    <div>
                        <p class="font-medium text-gray-800">{{ t(row.label_ar, row.label_en) }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ row.key }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select v-model="row.model_override" class="text-sm rounded-lg border-gray-300 w-40">
                            <option v-for="m in models" :key="m" :value="m">{{ m || t('النموذج الافتراضي', 'default model') }}</option>
                        </select>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="row.enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-[#1B365D] peer-checked:after:translate-x-5 after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button @click="save" class="px-6 py-2.5 rounded-lg bg-[#1B365D] text-white font-medium hover:opacity-90">
                    {{ t('حفظ', 'Save') }}
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
