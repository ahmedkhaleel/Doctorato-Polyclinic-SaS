<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

defineProps({
    stats: Object, monthlyTrend: Array,
    topProcedures: Array, categoryBreakdown: Array, recentPatients: Array,
});

function t(en, ar) { return isRtl.value ? ar : en; }
function fmtDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB'); }
function fmtMoney(n) { return Number(n || 0).toLocaleString(); }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#C4A265] p-6 md:p-8 shadow-xl">
            <div class="absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-[#1B365D]/30 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-[#C4A265]/20 rounded-full blur-3xl"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">{{ t('Cosmetic Dashboard', 'لوحة التجميل') }}</h1>
                    <p class="mt-1 text-white/80 text-sm">{{ t('Overview of cosmetic procedures and revenue', 'نظرة عامة على الإجراءات والإيرادات') }}</p>
                </div>
                <div class="text-start md:text-end">
                    <div class="text-3xl md:text-4xl font-bold text-white">{{ fmtMoney(stats?.totalRevenue) }}</div>
                    <div class="text-xs text-white/70">{{ t('Total Revenue', 'إجمالي الإيرادات') }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="s in [
                { label: t('Patients', 'المرضى'), val: stats?.totalPatients ?? 0, color: 'from-[#1B365D] to-[#2C4E7A]' },
                { label: t('Visits', 'الزيارات'), val: stats?.totalVisits ?? 0, color: 'from-[#1B365D] to-[#2C4E7A]' },
                { label: t('Sessions', 'الجلسات'), val: stats?.totalSessions ?? 0, color: 'from-[#C4A265] to-[#D4B57A]' },
                { label: t('This Month Sessions', 'جلسات الشهر'), val: stats?.thisMonthSessions ?? 0, color: 'from-[#C4A265] to-[#D4B57A]' },
                { label: t('This Month Revenue', 'إيرادات الشهر'), val: fmtMoney(stats?.monthRevenue ?? 0), color: 'from-[#C4A265] to-[#D4B57A]' },
                { label: t('Active Procedures', 'إجراءات نشطة'), val: stats?.activeProcedures ?? 0, color: 'from-emerald-500 to-emerald-400' },
                { label: t('Monthly Visits', 'زيارات الشهر'), val: stats?.thisMonthVisits ?? 0, color: 'from-[#1B365D] to-[#2C4E7A]' },
            ]" :key="s.label" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br text-white flex items-center justify-center mb-3" :class="s.color">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118L2.98 10.1c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.518-4.674z"/></svg>
                </div>
                <div class="text-2xl font-bold text-slate-900">{{ s.val }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ s.label }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 md:p-6">
                <h3 class="text-lg font-semibold mb-4 text-slate-800">{{ t('Top Procedures', 'أعلى الإجراءات') }}</h3>
                <div class="space-y-2">
                    <div v-for="p in topProcedures" :key="p.procedure_id" class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50">
                        <span class="text-sm text-slate-700">{{ isRtl ? p.procedure?.name_ar : (p.procedure?.name_en || p.procedure?.name_ar) || '—' }}</span>
                        <div class="text-xs">
                            <span class="font-semibold text-[#1B365D]">{{ p.total }}</span>
                            <span class="text-slate-400 mx-1">·</span>
                            <span class="text-slate-600">{{ fmtMoney(p.revenue) }}</span>
                        </div>
                    </div>
                    <p v-if="!topProcedures?.length" class="text-sm text-slate-400 text-center py-6">{{ t('No data yet', 'لا توجد بيانات') }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 md:p-6">
                <h3 class="text-lg font-semibold mb-4 text-slate-800">{{ t('Category Breakdown', 'تصنيف الإجراءات') }}</h3>
                <div class="space-y-2">
                    <div v-for="c in categoryBreakdown" :key="c.category" class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50">
                        <span class="text-sm text-slate-700 capitalize">{{ c.category }}</span>
                        <span class="text-sm font-semibold text-[#1B365D]">{{ c.total }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-4 md:px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-800">{{ t('Recent Patients', 'أحدث المرضى') }}</h3>
                <Link href="/admin/cosmetic/patients" class="text-sm text-[#1B365D] font-semibold hover:text-[#C4A265]">{{ t('View all →', 'عرض الكل ←') }}</Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-start px-4 md:px-6 py-3 text-slate-500 font-semibold">{{ t('Name', 'الاسم') }}</th>
                            <th class="text-start px-4 md:px-6 py-3 text-slate-500 font-semibold">{{ t('Phone', 'الهاتف') }}</th>
                            <th class="text-start px-4 md:px-6 py-3 text-slate-500 font-semibold">{{ t('File #', 'رقم الملف') }}</th>
                            <th class="text-start px-4 md:px-6 py-3 text-slate-500 font-semibold">{{ t('Updated', 'آخر تحديث') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in recentPatients" :key="p.id" class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="px-4 md:px-6 py-3 font-medium text-slate-800">{{ p.full_name }}</td>
                            <td class="px-4 md:px-6 py-3 text-slate-600">{{ p.phone || '-' }}</td>
                            <td class="px-4 md:px-6 py-3 text-slate-600">{{ p.file_number || '-' }}</td>
                            <td class="px-4 md:px-6 py-3 text-slate-500">{{ fmtDate(p.updated_at) }}</td>
                        </tr>
                        <tr v-if="!recentPatients?.length"><td colspan="4" class="text-center py-6 text-slate-400 text-sm">{{ t('No patients yet', 'لا يوجد مرضى') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
