<script setup>
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ attendance: Object, lowStock: Array });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const noShowOut = ref(''); const noShowLoading = ref(false); const noShowErr = ref('');
const reorderOut = ref(''); const reorderLoading = ref(false); const reorderErr = ref('');

const runNoShow = async () => {
    noShowLoading.value = true; noShowErr.value = ''; noShowOut.value = '';
    try {
        const { data } = await axios.post('/admin/ai/predictions/no-show');
        data.ok ? (noShowOut.value = data.text) : (noShowErr.value = data.message);
    } catch (e) { noShowErr.value = e.response?.data?.message || t('تعذّر التحليل.', 'Analysis failed.'); }
    finally { noShowLoading.value = false; }
};

const runReorder = async () => {
    reorderLoading.value = true; reorderErr.value = ''; reorderOut.value = '';
    try {
        const { data } = await axios.post('/admin/ai/predictions/reorder');
        data.ok ? (reorderOut.value = data.text) : (reorderErr.value = data.message);
    } catch (e) { reorderErr.value = e.response?.data?.message || t('تعذّر الاقتراح.', 'Suggestion failed.'); }
    finally { reorderLoading.value = false; }
};

const tabs = [
    { href: '/admin/ai/settings', ar: 'الإعدادات', en: 'Settings' },
    { href: '/admin/ai/insights', ar: 'التحليلات', en: 'Insights' },
    { href: '/admin/ai/predictions', ar: 'التنبؤات', en: 'Predictions' },
    { href: '/admin/ai/usage', ar: 'الاستهلاك', en: 'Usage' },
    { href: '/admin/ai/logs', ar: 'السجلات', en: 'Logs' },
];
</script>

<template>
    <AdminLayout :title="t('التنبؤات', 'Predictions')">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-1">
                <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
                    class="px-4 py-2 text-sm rounded-t-lg transition-colors"
                    :class="tab.href === '/admin/ai/predictions' ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100'">
                    {{ t(tab.ar, tab.en) }}
                </Link>
            </nav>

            <!-- No-show -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-gray-800">{{ t('توقّع الغياب', 'No-show risk') }}</h2>
                    <button @click="runNoShow" :disabled="noShowLoading"
                        class="px-4 py-2 rounded-lg bg-[#1B365D] text-white text-sm hover:opacity-90 disabled:opacity-50">
                        {{ noShowLoading ? t('…', '…') : t('تحليل', 'Analyse') }}
                    </button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">{{ t('مواعيد', 'Appointments') }}</p><p class="text-lg font-bold text-[#1B365D]">{{ attendance.total_appointments }}</p></div>
                    <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">{{ t('حالات غياب', 'No-shows') }}</p><p class="text-lg font-bold text-[#1B365D]">{{ attendance.no_show_count }}</p></div>
                    <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">{{ t('نسبة الغياب', 'No-show rate') }}</p><p class="text-lg font-bold text-[#1B365D]">{{ attendance.no_show_rate_pct }}%</p></div>
                    <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">{{ t('قادم 7 أيام', 'Next 7 days') }}</p><p class="text-lg font-bold text-[#1B365D]">{{ attendance.upcoming_7_days }}</p></div>
                </div>
                <div v-if="noShowErr" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-3 text-sm">{{ noShowErr }}</div>
                <div v-if="noShowOut" class="bg-gray-50 rounded-lg p-4 text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{{ noShowOut }}</div>
            </div>

            <!-- Inventory reorder -->
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-gray-800">{{ t('اقتراح إعادة طلب المخزون', 'Inventory reorder') }}</h2>
                    <button @click="runReorder" :disabled="reorderLoading"
                        class="px-4 py-2 rounded-lg bg-[#C4A265] text-white text-sm hover:opacity-90 disabled:opacity-50">
                        {{ reorderLoading ? t('…', '…') : t('اقتراح', 'Suggest') }}
                    </button>
                </div>
                <p class="text-sm text-gray-500">{{ t('عناصر منخفضة المخزون', 'Low-stock items') }}: <strong>{{ lowStock.length }}</strong></p>
                <div v-if="lowStock.length" class="max-h-40 overflow-y-auto text-sm divide-y divide-gray-100">
                    <div v-for="(it, i) in lowStock" :key="i" class="flex justify-between py-1.5">
                        <span class="text-gray-700">{{ it.name }}</span>
                        <span class="text-gray-400">{{ it.quantity }} / {{ it.min }}</span>
                    </div>
                </div>
                <div v-if="reorderErr" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-3 text-sm">{{ reorderErr }}</div>
                <div v-if="reorderOut" class="bg-gray-50 rounded-lg p-4 text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{{ reorderOut }}</div>
            </div>
        </div>
    </AdminLayout>
</template>
