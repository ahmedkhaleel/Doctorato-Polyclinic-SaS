<script setup>
import { computed } from 'vue';
import { useForm, usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: Object,
    usage: Object,
    driverReady: Boolean,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const form = useForm({
    ai_enabled: props.settings.ai_enabled === '1' || props.settings.ai_enabled === 1,
    ai_provider: props.settings.ai_provider || 'openai',
    ai_openai_api_key: '',
    ai_openai_org: props.settings.ai_openai_org || '',
    ai_default_model: props.settings.ai_default_model || 'gpt-4o-mini',
    ai_clinical_model: props.settings.ai_clinical_model || 'gpt-4o',
    ai_vision_model: props.settings.ai_vision_model || 'gpt-4o',
    ai_embedding_model: props.settings.ai_embedding_model || 'text-embedding-3-small',
    ai_transcribe_model: props.settings.ai_transcribe_model || 'whisper-1',
    ai_monthly_budget_usd: props.settings.ai_monthly_budget_usd || '50',
    ai_budget_alert_pct: props.settings.ai_budget_alert_pct || '80',
    ai_rate_limit_per_min: props.settings.ai_rate_limit_per_min || '20',
    ai_phi_redaction: props.settings.ai_phi_redaction === '1' || props.settings.ai_phi_redaction === 1,
    ai_log_prompts: props.settings.ai_log_prompts === '1' || props.settings.ai_log_prompts === 1,
    ai_patient_consent_required: props.settings.ai_patient_consent_required === '1' || props.settings.ai_patient_consent_required === 1,
    ai_cache_enabled: props.settings.ai_cache_enabled === '1' || props.settings.ai_cache_enabled === 1,
});

const submit = () => {
    form.transform((d) => ({
        ...d,
        ai_enabled: d.ai_enabled ? '1' : '0',
        ai_phi_redaction: d.ai_phi_redaction ? '1' : '0',
        ai_log_prompts: d.ai_log_prompts ? '1' : '0',
        ai_patient_consent_required: d.ai_patient_consent_required ? '1' : '0',
        ai_cache_enabled: d.ai_cache_enabled ? '1' : '0',
    })).post('/admin/ai/settings', { preserveScroll: true });
};

const testConnection = () => {
    router.post('/admin/ai/settings/test', {}, { preserveScroll: true });
};

const chatModels = ['gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini', 'gpt-4.1'];
const tabs = [
    { href: '/admin/ai/settings', ar: 'الإعدادات', en: 'Settings' },
    { href: '/admin/ai/features', ar: 'الميزات', en: 'Features' },
    { href: '/admin/ai/prompts', ar: 'القوالب', en: 'Prompts' },
    { href: '/admin/ai/usage', ar: 'الاستهلاك', en: 'Usage' },
    { href: '/admin/ai/logs', ar: 'السجلات', en: 'Logs' },
];
</script>

<template>
    <AdminLayout :title="t('الذكاء الاصطناعي — الإعدادات', 'AI — Settings')">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <!-- Tabs -->
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/settings' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <!-- Status banner -->
            <div class="rounded-xl p-4 flex items-center justify-between"
                :class="form.ai_enabled && driverReady ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200'">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full" :class="form.ai_enabled && driverReady ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    <div>
                        <p class="font-semibold text-gray-800">
                            {{ form.ai_enabled && driverReady ? t('الذكاء الاصطناعي مفعّل وجاهز', 'AI is enabled and ready')
                                : form.ai_enabled ? t('مفعّل لكن المفتاح غير مُهيّأ', 'Enabled but API key not configured')
                                : t('الذكاء الاصطناعي معطّل', 'AI is disabled') }}
                        </p>
                        <p class="text-xs text-gray-500">{{ t('المزوّد', 'Provider') }}: {{ form.ai_provider }}</p>
                    </div>
                </div>
                <button type="button" @click="testConnection"
                    class="px-4 py-2 text-sm rounded-lg bg-[#C4A265] text-white hover:opacity-90">
                    {{ t('اختبار الاتصال', 'Test connection') }}
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Master switch -->
                <section class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <h2 class="font-bold text-gray-800">{{ t('التشغيل العام', 'Master control') }}</h2>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="form.ai_enabled" class="w-5 h-5 rounded text-[#1B365D]">
                        <span class="text-sm text-gray-700">{{ t('تفعيل الذكاء الاصطناعي في النظام', 'Enable AI across the system') }}</span>
                    </label>
                </section>

                <!-- Provider / key -->
                <section class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <h2 class="font-bold text-gray-800">{{ t('المزوّد والمفتاح', 'Provider & key') }}</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ t('المزوّد', 'Provider') }}</label>
                            <select v-model="form.ai_provider" class="w-full rounded-lg border-gray-300">
                                <option value="openai">OpenAI</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ t('المؤسسة (اختياري)', 'Organization (optional)') }}</label>
                            <input v-model="form.ai_openai_org" type="text" class="w-full rounded-lg border-gray-300" placeholder="org-..." />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">
                            {{ t('مفتاح OpenAI API', 'OpenAI API key') }}
                            <span v-if="settings.ai_openai_api_key_set" class="text-emerald-600 text-xs">({{ t('مضبوط — اتركه فارغًا للإبقاء عليه', 'set — leave blank to keep') }})</span>
                        </label>
                        <input v-model="form.ai_openai_api_key" type="password" autocomplete="off"
                            class="w-full rounded-lg border-gray-300" placeholder="sk-..." />
                        <p class="text-xs text-gray-400 mt-1">{{ t('يُخزَّن مشفّرًا ولا يُعرض مطلقًا.', 'Stored encrypted, never displayed.') }}</p>
                    </div>
                </section>

                <!-- Models -->
                <section class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <h2 class="font-bold text-gray-800">{{ t('النماذج', 'Models') }}</h2>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ t('الافتراضي', 'Default') }}</label>
                            <select v-model="form.ai_default_model" class="w-full rounded-lg border-gray-300">
                                <option v-for="m in chatModels" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ t('السريري', 'Clinical') }}</label>
                            <select v-model="form.ai_clinical_model" class="w-full rounded-lg border-gray-300">
                                <option v-for="m in chatModels" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ t('الرؤية', 'Vision') }}</label>
                            <select v-model="form.ai_vision_model" class="w-full rounded-lg border-gray-300">
                                <option v-for="m in chatModels" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Budget & limits -->
                <section class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <h2 class="font-bold text-gray-800">{{ t('الميزانية والحدود', 'Budget & limits') }}</h2>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ t('السقف الشهري ($)', 'Monthly budget ($)') }}</label>
                            <input v-model="form.ai_monthly_budget_usd" type="number" min="0" step="1" class="w-full rounded-lg border-gray-300" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ t('تنبيه عند (%)', 'Alert at (%)') }}</label>
                            <input v-model="form.ai_budget_alert_pct" type="number" min="0" max="100" class="w-full rounded-lg border-gray-300" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">{{ t('حد الطلبات/الدقيقة', 'Requests / min') }}</label>
                            <input v-model="form.ai_rate_limit_per_min" type="number" min="0" class="w-full rounded-lg border-gray-300" />
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                        {{ t('المنفق هذا الشهر', 'Spent this month') }}:
                        <strong>${{ usage.month_to_date_usd }}</strong> / ${{ usage.budget_usd }}
                        <span v-if="usage.over_budget" class="text-red-600 ms-2">({{ t('تجاوز الميزانية', 'over budget') }})</span>
                    </div>
                </section>

                <!-- Privacy & safety -->
                <section class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                    <h2 class="font-bold text-gray-800">{{ t('الخصوصية والأمان', 'Privacy & safety') }}</h2>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="form.ai_phi_redaction" class="w-5 h-5 rounded text-[#1B365D]">
                        <span class="text-sm text-gray-700">{{ t('إخفاء بيانات المريض قبل الإرسال (PHI)', 'Redact patient identifiers (PHI) before sending') }}</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="form.ai_patient_consent_required" class="w-5 h-5 rounded text-[#1B365D]">
                        <span class="text-sm text-gray-700">{{ t('اشتراط موافقة المريض لمعالجة بياناته', 'Require patient consent for AI processing') }}</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="form.ai_log_prompts" class="w-5 h-5 rounded text-[#1B365D]">
                        <span class="text-sm text-gray-700">{{ t('تسجيل نص الـ prompt في السجلات (للتدقيق)', 'Log prompt text in request logs (audit)') }}</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="form.ai_cache_enabled" class="w-5 h-5 rounded text-[#1B365D]">
                        <span class="text-sm text-gray-700">{{ t('تخزين مؤقت للنتائج المتكررة (توفير التكلفة)', 'Cache repeated results (cost saver)') }}</span>
                    </label>
                </section>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 rounded-lg bg-[#1B365D] text-white font-medium hover:opacity-90 disabled:opacity-50">
                        {{ t('حفظ الإعدادات', 'Save settings') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
