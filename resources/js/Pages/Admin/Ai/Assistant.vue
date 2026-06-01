<script setup>
import { computed, reactive, ref } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

// Wave-1 tools, each with its input fields.
const tools = [
    {
        key: 'translation', ar: 'الترجمة', en: 'Translation',
        fields: [
            { name: 'text', ar: 'النص', en: 'Text', type: 'textarea' },
            { name: 'target', ar: 'اللغة الهدف', en: 'Target language', type: 'select', options: ['العربية', 'English', 'Français'] },
        ],
    },
    {
        key: 'comms_drafting', ar: 'صياغة رسالة', en: 'Draft message',
        fields: [
            { name: 'channel', ar: 'القناة', en: 'Channel', type: 'select', options: ['WhatsApp', 'SMS', 'Email'] },
            { name: 'topic', ar: 'الموضوع', en: 'Topic', type: 'textarea' },
        ],
    },
    {
        key: 'seo_content', ar: 'محتوى SEO', en: 'SEO content',
        fields: [
            { name: 'type', ar: 'النوع', en: 'Type', type: 'select', options: [t('مقال', 'article'), t('وصف خدمة', 'service description'), 'meta description'] },
            { name: 'topic', ar: 'الموضوع', en: 'Topic', type: 'textarea' },
        ],
    },
    {
        key: 'lead_reply', ar: 'رد على عميل', en: 'Lead reply',
        fields: [
            { name: 'message', ar: 'رسالة العميل', en: 'Prospect message', type: 'textarea' },
            { name: 'tone', ar: 'النبرة', en: 'Tone', type: 'select', options: [t('ودّي', 'friendly'), t('مهني', 'professional'), t('موجز', 'concise')] },
        ],
    },
    {
        key: 'campaign_copy', ar: 'نص حملة', en: 'Campaign copy',
        fields: [
            { name: 'product', ar: 'الخدمة/المنتج', en: 'Product/Service', type: 'text' },
            { name: 'channel', ar: 'القناة', en: 'Channel', type: 'select', options: ['Instagram', 'Facebook', 'WhatsApp', 'Email', 'SMS'] },
            { name: 'goal', ar: 'الهدف', en: 'Goal', type: 'text' },
        ],
    },
];

const active = ref(tools[0]);
const vars = reactive({});
const output = ref('');
const loading = ref(false);
const error = ref('');
const meta = ref(null);

const selectTool = (tool) => {
    active.value = tool;
    output.value = '';
    error.value = '';
    meta.value = null;
    Object.keys(vars).forEach((k) => delete vars[k]);
};

const generate = async () => {
    loading.value = true;
    error.value = '';
    output.value = '';
    meta.value = null;
    try {
        const { data } = await axios.post('/admin/ai/assist', {
            feature: active.value.key,
            vars: { ...vars },
            locale: isRtl.value ? 'ar' : 'en',
        });
        if (data.ok) {
            output.value = data.text;
            meta.value = { model: data.model, tokens: data.tokens };
        } else {
            error.value = data.message;
        }
    } catch (e) {
        error.value = e.response?.data?.message || t('تعذّر التوليد.', 'Generation failed.');
    } finally {
        loading.value = false;
    }
};

const copyOut = () => navigator.clipboard?.writeText(output.value);

const tabs = [
    { href: '/admin/ai/settings', ar: 'الإعدادات', en: 'Settings' },
    { href: '/admin/ai/features', ar: 'الميزات', en: 'Features' },
    { href: '/admin/ai/assistant', ar: 'المساعد', en: 'Assistant' },
    { href: '/admin/ai/prompts', ar: 'القوالب', en: 'Prompts' },
    { href: '/admin/ai/usage', ar: 'الاستهلاك', en: 'Usage' },
    { href: '/admin/ai/logs', ar: 'السجلات', en: 'Logs' },
];
</script>

<template>
    <AdminLayout :title="t('مساعد الذكاء الاصطناعي', 'AI Assistant')">
        <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/assistant' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <div class="grid md:grid-cols-[220px_1fr] gap-6">
                <!-- Tool list -->
                <aside class="space-y-1">
                    <button v-for="tool in tools" :key="tool.key" @click="selectTool(tool)"
                        class="w-full text-start px-4 py-2.5 rounded-lg text-sm transition-colors"
                        :class="active.key === tool.key ? 'bg-[#1B365D] text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'">
                        {{ t(tool.ar, tool.en) }}
                    </button>
                </aside>

                <!-- Tool form + output -->
                <section class="space-y-4">
                    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                        <h2 class="font-bold text-gray-800">{{ t(active.ar, active.en) }}</h2>
                        <div v-for="field in active.fields" :key="field.name">
                            <label class="block text-sm text-gray-600 mb-1">{{ t(field.ar, field.en) }}</label>
                            <textarea v-if="field.type === 'textarea'" v-model="vars[field.name]" rows="3"
                                class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                            <select v-else-if="field.type === 'select'" v-model="vars[field.name]"
                                class="w-full rounded-lg border-gray-300 text-sm">
                                <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <input v-else v-model="vars[field.name]" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <button @click="generate" :disabled="loading"
                            class="px-5 py-2.5 rounded-lg bg-[#1B365D] text-white font-medium hover:opacity-90 disabled:opacity-50">
                            <span v-if="loading">{{ t('جارٍ التوليد…', 'Generating…') }}</span>
                            <span v-else>{{ t('توليد', 'Generate') }}</span>
                        </button>
                    </div>

                    <div v-if="error" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm">
                        {{ error }}
                    </div>

                    <div v-if="output" class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">{{ t('النتيجة', 'Result') }}</h3>
                            <button @click="copyOut" class="text-sm px-3 py-1.5 rounded-lg bg-[#C4A265] text-white hover:opacity-90">
                                {{ t('نسخ', 'Copy') }}
                            </button>
                        </div>
                        <pre class="whitespace-pre-wrap text-sm text-gray-700 leading-relaxed font-sans">{{ output }}</pre>
                        <p v-if="meta" class="text-xs text-gray-400">{{ meta.model }} · {{ meta.tokens }} {{ t('توكن', 'tokens') }}</p>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
