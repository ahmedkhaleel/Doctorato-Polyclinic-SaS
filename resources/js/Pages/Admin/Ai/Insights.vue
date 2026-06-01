<script setup>
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ snapshot: Object });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const question = ref('');
const answer = ref('');
const loading = ref(false);
const error = ref('');

const ask = async () => {
    if (!question.value.trim()) return;
    loading.value = true; error.value = ''; answer.value = '';
    try {
        const { data } = await axios.post('/admin/ai/insights/ask', { question: question.value });
        if (data.ok) answer.value = data.text;
        else error.value = data.message;
    } catch (e) {
        error.value = e.response?.data?.message || t('تعذّر التحليل.', 'Analysis failed.');
    } finally { loading.value = false; }
};

const labels = {
    patients_total: t('إجمالي المرضى', 'Total patients'),
    visits_this_month: t('زيارات هذا الشهر', 'Visits this month'),
    bookings_pending: t('حجوزات معلّقة', 'Pending bookings'),
    revenue_this_month: t('إيرادات الشهر', 'Revenue MTD'),
    expenses_this_month: t('مصروفات الشهر', 'Expenses MTD'),
    unpaid_invoices: t('فواتير غير مدفوعة', 'Unpaid invoices'),
    unpaid_balance: t('الرصيد غير المدفوع', 'Unpaid balance'),
    doctors_active: t('أطباء نشطون', 'Active doctors'),
};
const examples = computed(() => isRtl.value
    ? ['كم إيراد هذا الشهر مقارنة بالمصروفات؟', 'ما حجم الفواتير غير المدفوعة؟']
    : ['How does revenue compare to expenses this month?', 'What is our unpaid balance?']);

const tabs = [
    { href: '/admin/ai/settings', ar: 'الإعدادات', en: 'Settings' },
    { href: '/admin/ai/assistant', ar: 'المساعد', en: 'Assistant' },
    { href: '/admin/ai/insights', ar: 'التحليلات', en: 'Insights' },
    { href: '/admin/ai/usage', ar: 'الاستهلاك', en: 'Usage' },
    { href: '/admin/ai/logs', ar: 'السجلات', en: 'Logs' },
];
</script>

<template>
    <AdminLayout :title="t('تحليلات بلغة طبيعية', 'Natural-language Insights')">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/insights' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <p class="text-sm text-gray-500">{{ t('اسأل عن أداء العيادة بلغة طبيعية. تُبنى الإجابة على لقطة مؤشرات آمنة (لا تُرسل بيانات خام).', 'Ask about clinic performance in natural language. Answered from a safe metrics snapshot — no raw data leaves the system.') }}</p>

            <!-- Snapshot -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="(val, key) in snapshot" :key="key" v-show="labels[key]"
                    class="bg-white rounded-xl border border-gray-200 p-3">
                    <p class="text-xs text-gray-500">{{ labels[key] || key }}</p>
                    <p class="text-lg font-bold text-[#1B365D]">{{ val ?? '—' }}</p>
                </div>
            </div>

            <!-- Ask -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                <form @submit.prevent="ask" class="flex gap-2">
                    <input v-model="question" type="text" :placeholder="t('اطرح سؤالك…', 'Ask a question…')"
                        class="flex-1 rounded-lg border-gray-300 text-sm" />
                    <button type="submit" :disabled="loading"
                        class="px-5 py-2 rounded-lg bg-[#1B365D] text-white text-sm hover:opacity-90 disabled:opacity-50">
                        {{ loading ? t('…', '…') : t('اسأل', 'Ask') }}
                    </button>
                </form>
                <div class="flex flex-wrap gap-2">
                    <button v-for="ex in examples" :key="ex" @click="question = ex; ask()"
                        class="text-xs px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">{{ ex }}</button>
                </div>
                <div v-if="error" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-3 text-sm">{{ error }}</div>
                <div v-if="answer" class="bg-gray-50 rounded-lg p-4 text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{{ answer }}</div>
            </div>
        </div>
    </AdminLayout>
</template>
