<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#0D9488';
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    statusCounts: { type: Object, default: () => ({}) },
    recentSessions: { type: Array, default: () => [] },
});

const cards = computed(() => [
    { label: t('Active Plans', 'خطط نشطة'), value: props.stats.active_plans || 0, color: ACCENT },
    { label: t('Sessions / month', 'جلسات / شهر'), value: props.stats.sessions_this_month || 0, color: '#1B365D' },
    { label: t('Assessments / month', 'تقييمات / شهر'), value: props.stats.assessments_this_month || 0, color: '#C4A265' },
    { label: t('Revenue / month', 'إيراد / شهر'), value: Math.round(props.stats.revenue_this_month || 0).toLocaleString(), color: '#059669', money: true },
]);
const dateLabel = (d) => (d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short' }) : '');
const docName = (d) => (d ? (isRtl.value ? d.name_ar : d.name_en) : '');
</script>

<template>
    <AdminLayout>
        <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h1 class="text-xl font-bold text-gray-800">{{ t('Physiotherapy', 'العلاج الطبيعي') }}</h1>
                <div class="flex gap-2">
                    <Link href="/admin/physiotherapy/patients" class="px-4 py-2 rounded-xl text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">{{ t('Patients', 'المرضى') }}</Link>
                    <Link href="/admin/physiotherapy/exercises" class="px-4 py-2 rounded-xl text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">{{ t('Exercises', 'التمارين') }}</Link>
                    <Link href="/admin/physiotherapy/settings" class="px-4 py-2 rounded-xl text-sm font-medium text-white" :style="{ backgroundColor: ACCENT }">{{ t('Settings', 'الإعدادات') }}</Link>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="(c, i) in cards" :key="i" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <p class="text-2xl font-bold tabular-nums" :style="{ color: c.color }">{{ c.value }}<span v-if="c.money" class="text-sm font-normal text-gray-400"> {{ t('EGP', 'ج.م') }}</span></p>
                    <p class="text-sm text-gray-500 mt-1">{{ c.label }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-3">{{ t('Recent Sessions', 'أحدث الجلسات') }}</h2>
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="text-start font-medium pb-2">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start font-medium pb-2">{{ t('Doctor', 'الطبيب') }}</th>
                            <th class="text-start font-medium pb-2">{{ t('Date', 'التاريخ') }}</th>
                            <th class="text-end font-medium pb-2">{{ t('Pain', 'الألم') }}</th>
                            <th class="text-end font-medium pb-2">{{ t('Cost', 'التكلفة') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in recentSessions" :key="s.id" class="border-t border-gray-50">
                            <td class="py-2 font-medium text-gray-700">{{ s.patient?.full_name }}</td>
                            <td class="py-2 text-gray-500">{{ docName(s.doctor) }}</td>
                            <td class="py-2 text-gray-500">{{ dateLabel(s.session_date) }}</td>
                            <td class="py-2 text-end text-gray-500"><span v-if="s.pain_before != null && s.pain_after != null">{{ s.pain_before }}→{{ s.pain_after }}</span></td>
                            <td class="py-2 text-end text-gray-600 tabular-nums">{{ s.cost ? Number(s.cost).toLocaleString() : '—' }}</td>
                        </tr>
                        <tr v-if="!recentSessions.length"><td colspan="5" class="py-8 text-center text-gray-400">{{ t('No sessions yet', 'لا توجد جلسات') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
