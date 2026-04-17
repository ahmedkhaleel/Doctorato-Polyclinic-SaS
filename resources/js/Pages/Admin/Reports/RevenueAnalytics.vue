<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const props = defineProps({
    comparison: Object,
    monthlyTrend: Array,
    topServices: Array,
    doctorRevenue: Array,
    weekdayRevenue: Array,
    forecast: Object,
    collectionRate: Number,
    totalInvoiced: Number,
    totalCollected: Number,
    filters: Object,
});

const moduleFilter = ref(props.filters?.module || '');

watch(moduleFilter, () => {
    router.get('/admin/reports/revenue-analytics', {
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
});

/* ── Helpers ────────────────────────────────────── */
function pctChange(current, previous) {
    if (!previous || previous === 0) return current > 0 ? 100 : 0;
    return Math.round(((current - previous) / previous) * 100);
}

function changeColor(pct, inverse = false) {
    const positive = inverse ? pct < 0 : pct > 0;
    return positive ? 'text-emerald-600' : pct < 0 && !inverse ? 'text-red-600' : 'text-gray-500';
}

function changeBg(pct, inverse = false) {
    const positive = inverse ? pct < 0 : pct > 0;
    return positive ? 'bg-emerald-50' : pct < 0 && !inverse ? 'bg-red-50' : 'bg-gray-50';
}

function changeArrow(pct) {
    return pct > 0 ? '↑' : pct < 0 ? '↓' : '→';
}

/* ── Chart bar height calc ──────────────────────── */
const maxMonthlyRevenue = computed(() => Math.max(...props.monthlyTrend.map(m => m.revenue), 1));
const maxWeekdayRevenue = computed(() => Math.max(...props.weekdayRevenue.map(d => d.avg_revenue), 1));
const maxServiceRevenue = computed(() => Math.max(...(props.topServices?.map(s => Number(s.total_revenue)) || [1]), 1));
</script>

<template>
    <AdminLayout :title="isRtl ? 'تحليلات الإيرادات' : 'Revenue Analytics'">
        <div class="space-y-6">
            <!-- ═════════ Navy Hero Header ═════════ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
                <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                    <div class="flex items-start gap-3 md:gap-4 min-w-0">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                            <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'تحليلات' : 'Analytics' }}</span>
                            </div>
                            <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight truncate">{{ isRtl ? 'تحليلات الإيرادات' : 'Revenue Analytics' }}</h1>
                            <p class="text-xs md:text-sm text-white/70 mt-1 max-w-xl">{{ isRtl ? 'مقارنة الشهر الحالي بالسابق مع توقعات' : 'Current vs previous month with forecasting' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <select v-if="activeModules.length > 1" v-model="moduleFilter"
                            class="px-3 py-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/40 focus:border-[#C4A265]">
                            <option value="" class="text-slate-800">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                            <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug" class="text-slate-800">{{ mod.name }}</option>
                        </select>
                        <Link href="/admin/reports/financial" class="inline-flex items-center gap-2 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 text-white font-semibold px-4 py-2 transition text-sm">
                            {{ isRtl ? 'التقرير المالي التفصيلي' : 'Detailed Financial Report' }} →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- KPI Comparison Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div v-for="(data, key) in comparison" :key="key"
                    class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-xs text-gray-500 uppercase font-semibold mb-2">
                        {{ key === 'revenue' ? (isRtl ? 'الإيرادات' : 'Revenue') :
                           key === 'expenses' ? (isRtl ? 'المصروفات' : 'Expenses') :
                           key === 'net_income' ? (isRtl ? 'صافي الدخل' : 'Net Income') :
                           key === 'invoices' ? (isRtl ? 'الفواتير' : 'Invoices') :
                           (isRtl ? 'متوسط الفاتورة' : 'Avg Ticket') }}
                    </div>
                    <div class="text-xl font-bold text-gray-900">
                        {{ key === 'invoices' ? data.current : formatCurrency(data.current) }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium"
                            :class="[changeBg(pctChange(data.current, data.previous), key === 'expenses'),
                                     changeColor(pctChange(data.current, data.previous), key === 'expenses')]">
                            {{ changeArrow(pctChange(data.current, data.previous)) }}
                            {{ Math.abs(pctChange(data.current, data.previous)) }}%
                        </span>
                        <span class="text-xs text-gray-400">{{ isRtl ? 'من الشهر السابق' : 'vs last month' }}</span>
                    </div>
                </div>
            </div>

            <!-- Collection Rate + Forecast -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Collection Rate -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">{{ isRtl ? 'معدل التحصيل' : 'Collection Rate' }}</h3>
                    <div class="flex items-center gap-6">
                        <div class="relative w-28 h-28 flex-shrink-0">
                            <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 36 36">
                                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    fill="none" stroke="#e5e7eb" stroke-width="3" />
                                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    fill="none" stroke="#06b6d4" stroke-width="3"
                                    :stroke-dasharray="`${collectionRate}, 100`" stroke-linecap="round" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xl md:text-2xl font-bold text-gray-900">{{ collectionRate }}%</span>
                            </div>
                        </div>
                        <div class="space-y-2 flex-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">{{ isRtl ? 'إجمالي الفوترة' : 'Total Invoiced' }}</span>
                                <span class="font-semibold">{{ formatCurrency(totalInvoiced) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">{{ isRtl ? 'إجمالي المحصل' : 'Total Collected' }}</span>
                                <span class="font-semibold text-emerald-600">{{ formatCurrency(totalCollected) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">{{ isRtl ? 'غير محصل' : 'Outstanding' }}</span>
                                <span class="font-semibold text-red-600">{{ formatCurrency(totalInvoiced - totalCollected) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Forecast -->
                <div class="bg-gradient-to-br from-slate-50 to-slate-50 rounded-xl border border-slate-200 p-6">
                    <h3 class="text-sm font-semibold text-[#1B365D] uppercase mb-4">
                        {{ isRtl ? 'توقعات الشهر القادم' : 'Next Month Forecast' }}
                    </h3>
                    <div class="text-2xl md:text-3xl font-bold text-[#1B365D]">{{ formatCurrency(forecast.next_month_revenue) }}</div>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-sm" :class="forecast.growth_rate >= 0 ? 'text-emerald-600' : 'text-red-600'">
                            {{ forecast.growth_rate >= 0 ? '↑' : '↓' }} {{ Math.abs(forecast.growth_rate) }}% {{ isRtl ? 'معدل النمو' : 'growth rate' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-3">
                        <div class="w-2 h-2 rounded-full"
                            :class="forecast.confidence === 'medium' ? 'bg-amber-400' : 'bg-gray-400'"></div>
                        <span class="text-xs text-gray-500">
                            {{ isRtl ? 'مستوى الثقة:' : 'Confidence:' }}
                            {{ forecast.confidence === 'medium' ? (isRtl ? 'متوسط' : 'Medium') : (isRtl ? 'منخفض' : 'Low') }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ isRtl ? 'بناءً على اتجاه آخر 3 أشهر' : 'Based on last 3 months trend' }}</p>
                </div>
            </div>

            <!-- Monthly Revenue Trend (12 months) -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'اتجاه الإيرادات (12 شهر)' : 'Revenue Trend (12 Months)' }}</h3>
                <div class="flex items-end gap-1 h-48">
                    <div v-for="(month, i) in monthlyTrend" :key="i" class="flex-1 flex flex-col items-center gap-1">
                        <!-- Revenue bar -->
                        <div class="w-full flex flex-col items-center gap-0.5" style="height: 160px;">
                            <div class="text-[10px] text-gray-400 font-medium" v-if="month.revenue > 0">
                                {{ (month.revenue / 1000).toFixed(0) }}k
                            </div>
                            <div class="w-full max-w-[30px] flex flex-col justify-end" style="flex: 1;">
                                <div class="bg-slate-400 rounded-t transition-all duration-500 relative group"
                                    :style="{ height: `${(month.revenue / maxMonthlyRevenue) * 100}%`, minHeight: month.revenue > 0 ? '4px' : '0' }">
                                    <!-- Expense overlay -->
                                    <div class="absolute bottom-0 left-0 right-0 bg-red-300 rounded-t opacity-60"
                                        :style="{ height: `${(month.expenses / (month.revenue || 1)) * 100}%`, maxHeight: '100%' }"></div>
                                </div>
                            </div>
                        </div>
                        <div class="text-[10px] text-gray-500 font-medium">{{ month.month_short }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-4 mt-3 justify-center">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded bg-slate-400"></div>
                        <span class="text-xs text-gray-500">{{ isRtl ? 'الإيرادات' : 'Revenue' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded bg-red-300 opacity-60"></div>
                        <span class="text-xs text-gray-500">{{ isRtl ? 'المصروفات' : 'Expenses' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Services -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'أعلى الخدمات إيراداً' : 'Top Services by Revenue' }}</h3>
                    <div class="space-y-3">
                        <div v-for="(service, i) in topServices" :key="i" class="flex items-center gap-3">
                            <span class="w-6 text-center text-xs font-bold"
                                :class="i < 3 ? 'text-[#1B365D]' : 'text-gray-400'">{{ i + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-gray-900 truncate">
                                        {{ isRtl ? service.name_ar : service.name_en }}
                                    </span>
                                    <span class="text-sm font-bold text-gray-700 flex-shrink-0">{{ formatCurrency(service.total_revenue) }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-[#1B365D] h-1.5 rounded-full transition-all duration-500"
                                        :style="{ width: `${(Number(service.total_revenue) / maxServiceRevenue) * 100}%` }"></div>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ service.total_quantity }} {{ isRtl ? 'وحدة' : 'units' }} &bull;
                                    {{ service.invoice_count }} {{ isRtl ? 'فاتورة' : 'invoices' }}
                                </div>
                            </div>
                        </div>
                        <p v-if="!topServices?.length" class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</p>
                    </div>
                </div>

                <!-- Doctor Revenue -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'إيرادات الأطباء' : 'Doctor Revenue' }}</h3>
                    <div class="space-y-3">
                        <div v-for="(doc, i) in doctorRevenue" :key="i"
                            class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-400 to-[#1B365D] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ (isRtl ? doc.name_ar : doc.name_en)?.charAt(0) || 'D' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-900 truncate">
                                        {{ isRtl ? doc.name_ar : doc.name_en }}
                                    </span>
                                    <span class="text-sm font-bold text-emerald-600 flex-shrink-0">{{ formatCurrency(doc.total_revenue) }}</span>
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ doc.visit_count }} {{ isRtl ? 'زيارة' : 'visits' }} &bull;
                                    {{ isRtl ? 'متوسط:' : 'Avg:' }} {{ formatCurrency(doc.avg_per_visit) }}
                                </div>
                            </div>
                        </div>
                        <p v-if="!doctorRevenue?.length" class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</p>
                    </div>
                </div>
            </div>

            <!-- Revenue by Day of Week -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'متوسط الإيراد حسب اليوم' : 'Average Revenue by Day of Week' }}</h3>
                <div class="flex items-end gap-3 h-40 justify-center">
                    <div v-for="day in weekdayRevenue" :key="day.day_en" class="flex flex-col items-center gap-1 w-20">
                        <div class="text-xs text-gray-500 font-medium" v-if="day.avg_revenue > 0">
                            {{ formatCurrency(day.avg_revenue) }}
                        </div>
                        <div class="w-12 rounded-t transition-all duration-500"
                            :class="day.avg_revenue === Math.max(...weekdayRevenue.map(d => d.avg_revenue))
                                ? 'bg-[#1B365D]' : 'bg-slate-200'"
                            :style="{ height: `${(day.avg_revenue / maxWeekdayRevenue) * 100}px`, minHeight: day.avg_revenue > 0 ? '4px' : '0' }">
                        </div>
                        <div class="text-xs font-medium text-gray-600">{{ isRtl ? day.day_ar : day.day_en }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
