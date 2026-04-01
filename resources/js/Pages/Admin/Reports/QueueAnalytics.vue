<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    stats: Object,
    waitDistribution: Array,
    dailyTrend: Array,
    byDoctor: Array,
    hourlyData: Object,
    peakHours: Array,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

let filterTimeout = null;
watch([dateFrom, dateTo], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get('/admin/reports/queue-analytics', {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
});

/* ── Helpers ────────────────────────────────────── */
const maxDistCount = computed(() => Math.max(...props.waitDistribution.map(d => d.count), 1));
const maxPeakCount = computed(() => Math.max(...(props.peakHours?.map(h => h.count) || [1]), 1));

function waitColor(minutes) {
    if (minutes <= 10) return 'text-emerald-600';
    if (minutes <= 20) return 'text-cyan-600';
    if (minutes <= 30) return 'text-amber-600';
    return 'text-red-600';
}

function waitBg(minutes) {
    if (minutes <= 10) return 'bg-emerald-500';
    if (minutes <= 20) return 'bg-cyan-500';
    if (minutes <= 30) return 'bg-amber-500';
    return 'bg-red-500';
}

function distColor(index) {
    const colors = ['bg-emerald-400', 'bg-cyan-400', 'bg-blue-400', 'bg-amber-400', 'bg-orange-400', 'bg-red-400'];
    return colors[index] || 'bg-gray-400';
}

function formatHour(h) {
    if (h === 0) return '12 AM';
    if (h < 12) return `${h} AM`;
    if (h === 12) return '12 PM';
    return `${h - 12} PM`;
}

function formatHourAr(h) {
    if (h < 12) return `${h || 12} ص`;
    return `${h === 12 ? 12 : h - 12} م`;
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'تحليلات الانتظار' : 'Queue Analytics'">
        <div class="space-y-6">
            <!-- Header + Date Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'تحليلات أداء الانتظار' : 'Queue Performance Analytics' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'أوقات الانتظار وأداء الطابور والساعات المزدحمة' : 'Wait times, queue performance, and peak hours' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="dateFrom" type="date"
                        class="rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                    <span class="text-gray-400">→</span>
                    <input v-model="dateTo" type="date"
                        class="rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total_visits }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</div>
                </div>
                <div class="bg-white rounded-xl border p-4"
                    :class="stats.avg_wait <= 15 ? 'border-emerald-200' : stats.avg_wait <= 30 ? 'border-amber-200' : 'border-red-200'">
                    <div class="text-2xl font-bold" :class="waitColor(stats.avg_wait)">
                        {{ stats.avg_wait }} <span class="text-sm font-normal text-gray-400">{{ isRtl ? 'دقيقة' : 'min' }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'متوسط الانتظار' : 'Avg Wait Time' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-cyan-200 p-4">
                    <div class="text-2xl font-bold text-cyan-600">
                        {{ stats.avg_service }} <span class="text-sm font-normal text-gray-400">{{ isRtl ? 'دقيقة' : 'min' }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'متوسط مدة الخدمة' : 'Avg Service Duration' }}</div>
                </div>
                <div class="bg-white rounded-xl border p-4"
                    :class="stats.no_show_rate < 5 ? 'border-emerald-200' : stats.no_show_rate < 15 ? 'border-amber-200' : 'border-red-200'">
                    <div class="text-2xl font-bold"
                        :class="stats.no_show_rate < 5 ? 'text-emerald-600' : stats.no_show_rate < 15 ? 'text-amber-600' : 'text-red-600'">
                        {{ stats.no_show_rate }}%
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'معدل عدم الحضور' : 'No-Show Rate' }}</div>
                </div>
                <div class="bg-white rounded-xl border p-4"
                    :class="stats.long_wait_pct < 10 ? 'border-emerald-200' : 'border-red-200'">
                    <div class="text-2xl font-bold"
                        :class="stats.long_wait_pct < 10 ? 'text-emerald-600' : 'text-red-600'">
                        {{ stats.long_wait_pct }}%
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'انتظار > 30 دقيقة' : 'Wait > 30 min' }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Wait Time Distribution -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'توزيع أوقات الانتظار' : 'Wait Time Distribution' }}</h3>
                    <div class="space-y-3">
                        <div v-for="(d, i) in waitDistribution" :key="i" class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 w-20 flex-shrink-0 text-end">{{ isRtl ? d.label_ar : d.label }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-6 relative overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 flex items-center"
                                    :class="distColor(i)"
                                    :style="{ width: `${Math.max((d.count / maxDistCount) * 100, d.count > 0 ? 3 : 0)}%` }">
                                </div>
                            </div>
                            <span class="text-sm font-bold text-gray-700 w-10 text-end">{{ d.count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Peak Hours -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'الساعات المزدحمة' : 'Peak Hours' }}</h3>
                    <div v-if="peakHours?.length" class="flex items-end gap-1 h-44">
                        <div v-for="h in peakHours" :key="h.hour" class="flex-1 flex flex-col items-center gap-1">
                            <div class="text-[9px] text-gray-400" v-if="h.count > 0">{{ h.count }}</div>
                            <div class="w-full flex justify-center" style="height: 120px;">
                                <div class="w-full max-w-[24px] flex flex-col justify-end">
                                    <div class="rounded-t transition-all duration-500"
                                        :class="h.avg_wait > 30 ? 'bg-red-400' : h.avg_wait > 15 ? 'bg-amber-400' : 'bg-cyan-400'"
                                        :style="{ height: `${(h.count / maxPeakCount) * 100}%`, minHeight: h.count > 0 ? '4px' : '0' }">
                                    </div>
                                </div>
                            </div>
                            <div class="text-[9px] text-gray-500 font-medium">
                                {{ isRtl ? formatHourAr(h.hour) : formatHour(h.hour) }}
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-8">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</p>
                    <div class="flex items-center gap-4 mt-3 justify-center text-xs">
                        <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-cyan-400"></div>{{ isRtl ? '≤ 15 دقيقة' : '≤ 15 min' }}</div>
                        <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-amber-400"></div>{{ isRtl ? '16-30 دقيقة' : '16-30 min' }}</div>
                        <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-red-400"></div>{{ isRtl ? '> 30 دقيقة' : '> 30 min' }}</div>
                    </div>
                </div>
            </div>

            <!-- Daily Wait Trend -->
            <div class="bg-white rounded-xl border border-gray-200 p-6" v-if="dailyTrend?.length">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'اتجاه الانتظار اليومي' : 'Daily Wait Time Trend' }}</h3>
                <div class="flex items-end gap-0.5 h-32">
                    <div v-for="d in dailyTrend" :key="d.date" class="flex-1 flex flex-col items-center group relative">
                        <div class="w-full max-w-[16px] flex flex-col justify-end h-24">
                            <div class="rounded-t transition-all duration-300"
                                :class="waitBg(d.avg_wait)"
                                :style="{ height: `${Math.min((d.avg_wait / 60) * 100, 100)}%`, minHeight: d.avg_wait > 0 ? '2px' : '0' }">
                            </div>
                        </div>
                        <!-- Tooltip -->
                        <div class="absolute bottom-full mb-2 hidden group-hover:block z-10 bg-gray-800 text-white rounded-lg px-2 py-1 text-xs whitespace-nowrap">
                            {{ d.date }}<br>
                            {{ isRtl ? 'متوسط:' : 'Avg:' }} {{ d.avg_wait }} {{ isRtl ? 'د' : 'min' }}<br>
                            {{ isRtl ? 'أقصى:' : 'Max:' }} {{ d.max_wait }} {{ isRtl ? 'د' : 'min' }}<br>
                            {{ d.visit_count }} {{ isRtl ? 'زيارة' : 'visits' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doctor Wait Times -->
            <div class="bg-white rounded-xl border border-gray-200 p-6" v-if="byDoctor?.length">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'أوقات الانتظار حسب الطبيب' : 'Wait Times by Doctor' }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-start px-3 py-2 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                                <th class="text-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الزيارات' : 'Visits' }}</th>
                                <th class="text-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'متوسط الانتظار' : 'Avg Wait' }}</th>
                                <th class="text-center px-3 py-2 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'مدة الخدمة' : 'Avg Service' }}</th>
                                <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase w-48">{{ isRtl ? 'الأداء' : 'Performance' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(doc, i) in byDoctor" :key="i" class="hover:bg-gray-50/50">
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            {{ (isRtl ? doc.name_ar : doc.name_en)?.charAt(0) || 'D' }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ isRtl ? doc.name_ar : doc.name_en }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center text-sm text-gray-600">{{ doc.visit_count }}</td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm font-bold" :class="waitColor(doc.avg_wait)">
                                        {{ doc.avg_wait }} {{ isRtl ? 'د' : 'min' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center text-sm text-gray-600">
                                    {{ doc.avg_service || '—' }} {{ doc.avg_service ? (isRtl ? 'د' : 'min') : '' }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all"
                                                :class="waitBg(doc.avg_wait)"
                                                :style="{ width: `${Math.min((doc.avg_wait / 60) * 100, 100)}%` }">
                                            </div>
                                        </div>
                                        <span v-if="i === 0" class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 font-medium">
                                            {{ isRtl ? 'الأفضل' : 'Best' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
