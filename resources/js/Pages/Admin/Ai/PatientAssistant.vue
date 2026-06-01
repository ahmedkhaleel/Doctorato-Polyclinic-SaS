<script setup>
import { computed, ref } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ indexedCount: Number });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const rebuilding = ref(false);
const rebuild = () => {
    rebuilding.value = true;
    router.post('/admin/ai/patient-assistant/rebuild', {}, {
        preserveScroll: true,
        onFinish: () => { rebuilding.value = false; },
    });
};

const messages = ref([]);
const question = ref('');
const loading = ref(false);

const ask = async () => {
    const q = question.value.trim();
    if (!q) return;
    messages.value.push({ role: 'user', content: q });
    question.value = '';
    loading.value = true;
    try {
        const { data } = await axios.post('/admin/ai/patient-assistant/test', { question: q, session_id: 'admin-playground' });
        messages.value.push({ role: 'assistant', content: data.ok ? data.text : data.message });
    } catch (e) {
        messages.value.push({ role: 'assistant', content: e.response?.data?.message || t('تعذّر الرد.', 'Failed to respond.') });
    } finally {
        loading.value = false;
    }
};

const tabs = [
    { href: '/admin/ai/settings', ar: 'الإعدادات', en: 'Settings' },
    { href: '/admin/ai/assistant', ar: 'المساعد', en: 'Assistant' },
    { href: '/admin/ai/patient-assistant', ar: 'مساعد المريض', en: 'Patient Assistant' },
    { href: '/admin/ai/features', ar: 'الميزات', en: 'Features' },
    { href: '/admin/ai/usage', ar: 'الاستهلاك', en: 'Usage' },
];
</script>

<template>
    <AdminLayout :title="t('مساعد المريض (RAG)', 'Patient Assistant (RAG)')">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/patient-assistant' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <!-- Index management -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-gray-800">{{ t('فهرس المعرفة', 'Knowledge index') }}</h2>
                    <p class="text-sm text-gray-500">{{ t('عناصر مفهرسة', 'Indexed items') }}: <strong>{{ indexedCount }}</strong>
                        — {{ t('الأسئلة الشائعة والخدمات والأطباء', 'FAQs, services and doctors') }}</p>
                </div>
                <button @click="rebuild" :disabled="rebuilding"
                    class="px-4 py-2 rounded-lg bg-[#C4A265] text-white text-sm hover:opacity-90 disabled:opacity-50">
                    {{ rebuilding ? t('جارٍ البناء…', 'Building…') : t('إعادة بناء الفهرس', 'Rebuild index') }}
                </button>
            </div>

            <!-- Playground chat -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h2 class="font-bold text-gray-800">{{ t('تجربة المساعد', 'Test the assistant') }}</h2>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div v-if="messages.length === 0" class="text-sm text-gray-400 text-center py-6">
                        {{ t('اطرح سؤالًا كأنك مريض (مثل: ما هي خدمات الجلدية؟)', 'Ask as a patient (e.g. What dermatology services do you offer?)') }}
                    </div>
                    <div v-for="(m, i) in messages" :key="i" class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm whitespace-pre-wrap"
                            :class="m.role === 'user' ? 'bg-[#1B365D] text-white' : 'bg-gray-100 text-gray-800'">
                            {{ m.content }}
                        </div>
                    </div>
                    <div v-if="loading" class="text-sm text-gray-400">{{ t('يكتب…', 'typing…') }}</div>
                </div>
                <form @submit.prevent="ask" class="flex gap-2">
                    <input v-model="question" type="text" :placeholder="t('اكتب سؤالك…', 'Type your question…')"
                        class="flex-1 rounded-lg border-gray-300 text-sm" />
                    <button type="submit" :disabled="loading"
                        class="px-5 py-2 rounded-lg bg-[#1B365D] text-white text-sm hover:opacity-90 disabled:opacity-50">
                        {{ t('إرسال', 'Send') }}
                    </button>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
