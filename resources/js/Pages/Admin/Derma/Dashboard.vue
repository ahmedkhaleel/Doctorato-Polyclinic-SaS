<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

defineProps({
    stats: Object,
    monthlyTrend: Array,
    topConditions: Array,
    sessionTypes: Array,
    recentPatients: Array,
});

function t(en, ar) { return isRtl.value ? ar : en; }
function fmtDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB'); }
</script>

<template>
    <div class="space-y-6 pb-10">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-pink-600 via-pink-500 to-rose-500 p-8 shadow-xl">
            <div class="absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-pink-300/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-rose-300/15 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <h1 class="text-3xl font-bold text-white">{{ t('Dermatology Dashboard', 'لوحة الجلدية') }}</h1>
                <p class="mt-1 text-pink-100/90">{{ t('Overview of derma module activity', 'نظرة عامة على نشاط قسم الجلدية') }}</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="s in [
                { label: t('Patients', 'المرضى'), val: stats?.totalPatients ?? 0, color: 'from-pink-500 to-pink-400' },
                { label: t('Visits', 'الزيارات'), val: stats?.totalVisits ?? 0, color: 'from-rose-500 to-rose-400' },
                { label: t('This Month', 'هذا الشهر'), val: stats?.thisMonthVisits ?? 0, color: 'from-fuchsia-500 to-fuchsia-400' },
                { label: t('Active Conditions', 'حالات نشطة'), val: stats?.activeConditions ?? 0, color: 'from-amber-500 to-amber-400' },
                { label: t('Sessions', 'الجلسات'), val: stats?.totalSessions ?? 0, color: 'from-purple-500 to-purple-400' },
                { label: t('Sessions / month', 'جلسات الشهر'), val: stats?.sessionsThisMonth ?? 0, color: 'from-indigo-500 to-indigo-400' },
                { label: t('Photos', 'الصور'), val: stats?.totalPhotos ?? 0, color: 'from-teal-500 to-teal-400' },
            ]" :key="s.label"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br text-white flex items-center justify-center mb-3" :class="s.color">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ s.val }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ s.label }}</div>
            </div>
        </div>

        <!-- Top Conditions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">{{ t('Top Skin Conditions', 'أعلى الحالات الجلدية') }}</h3>
                <div class="space-y-2">
                    <div v-for="c in topConditions" :key="c.category" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50">
                        <span class="text-sm text-gray-700 capitalize">{{ c.category }}</span>
                        <span class="text-sm font-semibold text-pink-600">{{ c.total }}</span>
                    </div>
                    <p v-if="!topConditions?.length" class="text-sm text-gray-400 text-center py-6">{{ t('No data yet', 'لا توجد بيانات') }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">{{ t('Session Types', 'أنواع الجلسات') }}</h3>
                <div class="space-y-2">
                    <div v-for="s in sessionTypes" :key="s.session_type" class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50">
                        <span class="text-sm text-gray-700 capitalize">{{ s.session_type }}</span>
                        <span class="text-sm font-semibold text-rose-600">{{ s.total }}</span>
                    </div>
                    <p v-if="!sessionTypes?.length" class="text-sm text-gray-400 text-center py-6">{{ t('No sessions yet', 'لا توجد جلسات') }}</p>
                </div>
            </div>
        </div>

        <!-- Recent Patients -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">{{ t('Recent Patients', 'أحدث المرضى') }}</h3>
                <Link href="/admin/derma/patients" class="text-sm text-pink-600 font-medium">{{ t('View all →', 'عرض الكل ←') }}</Link>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-6 py-3 text-gray-500 font-semibold">{{ t('Name', 'الاسم') }}</th>
                        <th class="text-start px-6 py-3 text-gray-500 font-semibold">{{ t('Phone', 'الهاتف') }}</th>
                        <th class="text-start px-6 py-3 text-gray-500 font-semibold">{{ t('File #', 'رقم الملف') }}</th>
                        <th class="text-start px-6 py-3 text-gray-500 font-semibold">{{ t('Updated', 'آخر تحديث') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in recentPatients" :key="p.id" class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-800">{{ p.full_name }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ p.phone || '-' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ p.file_number || '-' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ fmtDate(p.updated_at) }}</td>
                    </tr>
                    <tr v-if="!recentPatients?.length"><td colspan="4" class="text-center py-6 text-gray-400 text-sm">{{ t('No patients yet', 'لا يوجد مرضى') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
