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
        <!-- Navy Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
            <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                <div class="flex items-start gap-3 md:gap-4 min-w-0">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'DERMA & COSMETIC' }}</span>
                        </div>
                        <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ t('Cosmetic Dashboard', 'لوحة التجميل') }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1 max-w-xl">{{ t('Overview of cosmetic procedures and revenue', 'نظرة عامة على الإجراءات والإيرادات') }}</p>
                    </div>
                </div>
                <div class="inline-flex items-center gap-3 rounded-xl backdrop-blur-sm px-4 py-3 border bg-[#C4A265]/10 border-[#C4A265]/30 flex-shrink-0">
                    <div>
                        <div class="text-2xl md:text-3xl font-extrabold text-[#C4A265]" dir="ltr">{{ fmtMoney(stats?.totalRevenue) }}</div>
                        <div class="text-[10px] text-white/70 uppercase tracking-wider">{{ t('Total Revenue', 'إجمالي الإيرادات') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            <div v-for="(s, i) in [
                { label: t('Patients', 'المرضى'), val: stats?.totalPatients ?? 0 },
                { label: t('Visits', 'الزيارات'), val: stats?.totalVisits ?? 0 },
                { label: t('Sessions', 'الجلسات'), val: stats?.totalSessions ?? 0 },
                { label: t('This Month Sessions', 'جلسات الشهر'), val: stats?.thisMonthSessions ?? 0 },
                { label: t('This Month Revenue', 'إيرادات الشهر'), val: fmtMoney(stats?.monthRevenue ?? 0) },
                { label: t('Active Procedures', 'إجراءات نشطة'), val: stats?.activeProcedures ?? 0 },
                { label: t('Monthly Visits', 'زيارات الشهر'), val: stats?.thisMonthVisits ?? 0 },
            ]" :key="s.label" class="relative bg-white rounded-2xl border border-[#C4A265]/20 p-4 md:p-5 overflow-hidden hover:shadow-lg hover:shadow-[#C4A265]/10 transition">
                <div class="absolute top-0 inset-x-0 h-1"
                    :class="['bg-gradient-to-r from-[#C4A265] to-[#8B7043]', 'bg-gradient-to-r from-[#1B365D] to-[#2C4E7A]', 'bg-gradient-to-r from-emerald-400 to-emerald-600', 'bg-gradient-to-r from-[#C4A265] to-[#8B7043]'][i % 4]"></div>
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-[10px] md:text-xs font-semibold text-[#8B7043] tracking-wide uppercase mb-1.5 md:mb-2">{{ s.label }}</div>
                        <div class="text-xl md:text-3xl font-extrabold text-[#1B365D] truncate">{{ s.val }}</div>
                    </div>
                    <div class="w-9 h-9 md:w-11 md:h-11 rounded-xl bg-gradient-to-br from-[#C4A265]/20 to-[#C4A265]/5 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.784.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118L2.98 10.1c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.518-4.674z"/></svg>
                    </div>
                </div>
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
