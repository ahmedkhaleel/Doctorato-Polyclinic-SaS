<script setup>
import { computed, reactive } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ prompts: Array });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const rows = reactive(props.prompts.map((p) => ({
    id: p.id, feature: p.feature, locale: p.locale, version: p.version,
    system_prompt: p.system_prompt || '', user_template: p.user_template || '', is_active: !!p.is_active,
})));

const save = (row) => {
    router.post(`/admin/ai/prompts/${row.id}`, {
        system_prompt: row.system_prompt, user_template: row.user_template, is_active: row.is_active,
    }, { preserveScroll: true });
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
    <AdminLayout :title="t('الذكاء الاصطناعي — القوالب', 'AI — Prompts')">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/prompts' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <p class="text-sm text-gray-500">{{ t('حرّر قوالب التوجيه (prompts) لكل ميزة دون لمس الكود. استخدم متغيرات بين قوسين مزدوجين مثل name.', 'Edit prompt templates per feature without touching code. Use double-brace placeholders like name for variables.') }}</p>

            <div v-if="rows.length === 0" class="text-center text-gray-400 py-10">{{ t('لا توجد قوالب بعد.', 'No templates yet.') }}</div>

            <div v-for="row in rows" :key="row.id" class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-gray-800 font-mono text-sm">{{ row.feature }}</span>
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600 uppercase">{{ row.locale }}</span>
                        <span class="text-xs text-gray-400">v{{ row.version }}</span>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" v-model="row.is_active" class="rounded text-[#1B365D]">
                        {{ t('مفعّل', 'Active') }}
                    </label>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">{{ t('توجيه النظام', 'System prompt') }}</label>
                    <textarea v-model="row.system_prompt" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">{{ t('قالب المستخدم', 'User template') }}</label>
                    <textarea v-model="row.user_template" rows="3" class="w-full rounded-lg border-gray-300 text-sm font-mono"></textarea>
                </div>
                <div class="flex justify-end">
                    <button @click="save(row)" class="px-4 py-2 rounded-lg bg-[#C4A265] text-white text-sm hover:opacity-90">
                        {{ t('حفظ القالب', 'Save template') }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
