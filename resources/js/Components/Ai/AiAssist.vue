<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    // Feature key — used to gate visibility against the shared ai.features list.
    feature: { type: String, required: true },
    labelAr: { type: String, default: 'مساعدة الذكاء الاصطناعي' },
    labelEn: { type: String, default: 'AI assist' },
    // Generic admin endpoint by default; override for panel-specific endpoints.
    endpoint: { type: String, default: '/admin/ai/assist' },
    // For the default endpoint: returns the {vars} object (read from the parent form).
    buildVars: { type: Function, default: null },
    // Optional: returns the FULL request body (overrides {feature,vars,locale}).
    payload: { type: Function, default: null },
    // Optional: auto-emit insert instead of showing an "insert" button.
    autoInsert: { type: Boolean, default: false },
});
const emit = defineEmits(['insert']);

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

// Self-hide unless AI is ready AND this feature is enabled.
const visible = computed(() => {
    const ai = page.props.ai;
    return !!ai?.enabled && Array.isArray(ai?.features) && ai.features.includes(props.feature);
});

const busy = ref(false);
const error = ref('');
const result = ref('');

async function run() {
    busy.value = true; error.value = ''; result.value = '';
    try {
        const body = props.payload
            ? props.payload()
            : { feature: props.feature, vars: props.buildVars ? props.buildVars() : {}, locale: isRtl.value ? 'ar' : 'en' };
        const { data } = await axios.post(props.endpoint, body);
        if (data.ok) {
            result.value = data.text;
            if (props.autoInsert) { emit('insert', data.text); result.value = ''; }
        } else { error.value = data.message; }
    } catch (e) {
        error.value = e.response?.data?.message || t('تعذّر التوليد.', 'Generation failed.');
    } finally { busy.value = false; }
}

function insert() { emit('insert', result.value); result.value = ''; }
function copy() { navigator.clipboard?.writeText(result.value); }
</script>

<template>
    <div v-if="visible" class="my-2">
        <button type="button" @click="run" :disabled="busy"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg bg-[#7C3AED] text-white hover:opacity-90 disabled:opacity-50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            <span>{{ busy ? t('جارٍ…', 'Working…') : t(labelAr, labelEn) }}</span>
        </button>
        <p v-if="error" class="text-xs text-amber-700 bg-amber-50 rounded-lg p-2 mt-1.5">{{ error }}</p>
        <div v-if="result" class="mt-1.5 rounded-lg border border-gray-200 bg-white p-3">
            <p class="text-xs text-gray-700 whitespace-pre-wrap leading-relaxed">{{ result }}</p>
            <div class="flex gap-2 mt-2">
                <button type="button" @click="insert" class="px-2.5 py-1 text-xs rounded-md bg-[#1B365D] text-white hover:opacity-90">{{ t('إدراج', 'Insert') }}</button>
                <button type="button" @click="copy" class="px-2.5 py-1 text-xs rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200">{{ t('نسخ', 'Copy') }}</button>
            </div>
        </div>
    </div>
</template>
