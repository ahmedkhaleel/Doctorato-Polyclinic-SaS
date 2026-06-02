<script setup>
import { computed, ref, reactive, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

defineOptions({ layout: PatientLayout });

const props = defineProps({ enabled: Boolean });

const { lp } = usePatientLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');
const t = (ar, en) => (isRtl.value ? ar : en);

const messages = ref([]);
const question = ref('');
const loading = ref(false);
const sessionId = ref(null);
const scroller = ref(null);

const scrollDown = () => nextTick(() => { if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight; });

const xsrf = () => decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');

// Non-streaming fallback (used if streaming isn't available).
const askJson = async (q, assistantMsg) => {
    const { data } = await axios.post(lp('/assistant/ask'), { question: q, session_id: sessionId.value });
    if (data.ok) { sessionId.value = data.session_id; assistantMsg.content = data.text; }
    else assistantMsg.content = data.message;
};

const ask = async () => {
    const q = question.value.trim();
    if (!q || loading.value) return;
    messages.value.push({ role: 'user', content: q });
    question.value = '';
    loading.value = true;
    scrollDown();

    const assistantMsg = reactive({ role: 'assistant', content: '' });
    messages.value.push(assistantMsg);

    try {
        const res = await fetch(lp('/assistant/stream'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrf(), Accept: 'text/plain' },
            credentials: 'same-origin',
            body: JSON.stringify({ question: q, session_id: sessionId.value }),
        });

        if (! res.ok || ! res.body) { await askJson(q, assistantMsg); return; }

        const sess = res.headers.get('X-Session-Id');
        if (sess) sessionId.value = sess;

        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        for (;;) {
            const { done, value } = await reader.read();
            if (done) break;
            assistantMsg.content += decoder.decode(value, { stream: true });
            scrollDown();
        }
        if (! assistantMsg.content) await askJson(q, assistantMsg);
    } catch (e) {
        try { await askJson(q, assistantMsg); }
        catch (e2) { assistantMsg.content = t('تعذّر الرد حاليًا.', 'Could not respond right now.'); }
    } finally {
        loading.value = false;
        scrollDown();
    }
};

const suggestions = computed(() => isRtl.value
    ? ['ما هي خدمات الجلدية لديكم؟', 'كيف أحجز موعدًا؟', 'ما هي مواعيد العمل؟']
    : ['What dermatology services do you offer?', 'How do I book an appointment?', 'What are your working hours?']);

const useSuggestion = (s) => { question.value = s; ask(); };
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-6">
        <div class="mb-4">
            <span class="text-[11px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ t('المساعد الذكي', 'Smart Assistant') }}</span>
            <h1 class="text-xl font-bold text-[#1B365D]">{{ t('اسأل مساعد العيادة', 'Ask the clinic assistant') }}</h1>
            <p class="text-sm text-gray-500">{{ t('يساعدك في معرفة الخدمات والأطباء والحجز. ليس بديلًا عن الاستشارة الطبية.', 'Helps with services, doctors and booking. Not a substitute for medical advice.') }}</p>
        </div>

        <div v-if="!enabled" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm">
            {{ t('المساعد غير متاح حاليًا. يرجى التواصل مع العيادة مباشرة.', 'The assistant is currently unavailable. Please contact the clinic directly.') }}
        </div>

        <div v-else class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col" style="height: 60vh;">
            <div ref="scroller" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div v-if="messages.length === 0" class="text-center py-8 space-y-3">
                    <p class="text-sm text-gray-400">{{ t('ابدأ بسؤال، أو جرّب:', 'Start with a question, or try:') }}</p>
                    <div class="flex flex-wrap gap-2 justify-center">
                        <button v-for="s in suggestions" :key="s" @click="useSuggestion(s)"
                            class="text-xs px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">{{ s }}</button>
                    </div>
                </div>
                <div v-for="(m, i) in messages" :key="i" class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm whitespace-pre-wrap"
                        :class="m.role === 'user' ? 'bg-[#1B365D] text-white' : 'bg-gray-100 text-gray-800'">{{ m.content }}</div>
                </div>
                <div v-if="loading" class="text-xs text-gray-400">{{ t('يكتب…', 'typing…') }}</div>
            </div>
            <form @submit.prevent="ask" class="border-t border-gray-100 p-3 flex gap-2">
                <input v-model="question" type="text" :placeholder="t('اكتب رسالتك…', 'Type your message…')"
                    class="flex-1 rounded-xl border-gray-300 text-sm" />
                <button type="submit" :disabled="loading"
                    class="px-5 py-2 rounded-xl text-white text-sm font-semibold bg-[#1B365D] hover:opacity-90 disabled:opacity-50">
                    {{ t('إرسال', 'Send') }}
                </button>
            </form>
        </div>
    </div>
</template>
