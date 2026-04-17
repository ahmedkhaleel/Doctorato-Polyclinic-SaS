<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');
const { t } = useLocale();
const { formatCurrency } = useCurrency();

const props = defineProps({
    labComparison: Array,
    labQuality: Array,
    bestLabPerType: Object,
    profitByType: Array,
    monthlyProfit: Array,
    summary: Object,
    filters: Object,
    itemTypes: Array,
    materials: Array,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

function applyFilters() {
    router.get('/admin/dental/lab-orders/profitability', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

const itemLabels = {
    crown: { ar: 'تاج', en: 'Crown' }, bridge: { ar: 'جسر', en: 'Bridge' },
    denture: { ar: 'طقم أسنان', en: 'Denture' }, retainer: { ar: 'مثبت', en: 'Retainer' },
    aligner: { ar: 'تقويم شفاف', en: 'Aligner' }, veneer: { ar: 'قشرة', en: 'Veneer' },
    implant_abutment: { ar: 'دعامة زراعة', en: 'Implant Abutment' },
    night_guard: { ar: 'واقي ليلي', en: 'Night Guard' },
    inlay_onlay: { ar: 'حشوة', en: 'Inlay/Onlay' },
};
function itemLabel(type) { const l = itemLabels[type]; return l ? (isRtl.value ? l.ar : l.en) : type; }

function scoreColor(score) {
    if (score >= 80) return 'text-emerald-600';
    if (score >= 60) return 'text-amber-600';
    return 'text-red-500';
}
function scoreBg(score) {
    if (score >= 80) return 'bg-emerald-500';
    if (score >= 60) return 'bg-amber-500';
    return 'bg-red-400';
}
function marginColor(margin) {
    if (margin >= 40) return 'text-emerald-600';
    if (margin >= 20) return 'text-amber-600';
    return 'text-red-500';
}

const maxProfit = computed(() => {
    if (!props.profitByType?.length) return 1;
    return Math.max(...props.profitByType.map(p => Math.abs(p.total_profit)));
});

const maxMonthlyCharge = computed(() => {
    if (!props.monthlyProfit?.length) return 1;
    return Math.max(...props.monthlyProfit.map(m => Math.max(m.total_charge, m.total_cost)));
});

const monthLabels = computed(() => {
    return props.monthlyProfit?.map(m => {
        const [y, mo] = m.month.split('-');
        const date = new Date(y, mo - 1);
        return date.toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US', { month: 'short' });
    }) ?? [];
});

const activeTab = ref('quality');

// Group comparison by lab for price comparison view
const comparisonByItemType = computed(() => {
    const grouped = {};
    (props.labComparison || []).forEach(row => {
        if (!grouped[row.item_type]) grouped[row.item_type] = [];
        grouped[row.item_type].push(row);
    });
    // Sort each group by avg_cost ascending
    Object.keys(grouped).forEach(type => {
        grouped[type].sort((a, b) => a.avg_cost - b.avg_cost);
    });
    return grouped;
});
</script>

<template>
    <AdminLayout :title="isRtl ? 'تحليل ربحية المعامل' : 'Lab Profitability Analysis'">
        <div class="space-y-6">
            <!-- Hero -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-7">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-emerald-400/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-emerald-300/15 rounded-full blur-3xl"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">{{ isRtl ? 'تحليل ربحية المعامل' : 'Lab Profitability Analysis' }}</h1>
                            <p class="text-emerald-100/80 text-sm mt-0.5">{{ isRtl ? 'مقارنة الأسعار والجودة وسرعة التسليم' : 'Compare pricing, quality & delivery speed' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link href="/admin/dental/lab-orders/dashboard" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            {{ isRtl ? 'لوحة التحكم' : 'Dashboard' }}
                        </Link>
                        <!-- Date Filter -->
                        <div class="flex items-center gap-2 bg-white/10 rounded-xl px-3 py-1.5 backdrop-blur-sm">
                            <input v-model="dateFrom" type="date" class="bg-transparent text-white text-sm border-0 focus:ring-0 p-1 w-32" @change="applyFilters" />
                            <span class="text-white/50">→</span>
                            <input v-model="dateTo" type="date" class="bg-transparent text-white text-sm border-0 focus:ring-0 p-1 w-32" @change="applyFilters" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="dental-card-enter bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80" style="animation-delay:0.1s">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'إجمالي الإيرادات' : 'Total Revenue' }}</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(summary?.total_charge || 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ summary?.total_orders || 0 }} {{ isRtl ? 'طلب' : 'orders' }}</p>
                </div>
                <div class="dental-card-enter bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80" style="animation-delay:0.15s">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'صافي الربح' : 'Net Profit' }}</p>
                    <p class="text-2xl font-bold mt-1" :class="(summary?.total_profit || 0) >= 0 ? 'text-emerald-600' : 'text-red-500'">{{ formatCurrency(summary?.total_profit || 0) }}</p>
                    <p class="text-xs mt-1" :class="marginColor(summary?.overall_margin || 0)">{{ summary?.overall_margin || 0 }}% {{ isRtl ? 'هامش' : 'margin' }}</p>
                </div>
                <div class="dental-card-enter bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80" style="animation-delay:0.2s">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'متوسط ربح/طلب' : 'Avg Profit/Order' }}</p>
                    <p class="text-2xl font-bold text-[#1B365D] mt-1">{{ formatCurrency(summary?.avg_profit_per_order || 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ summary?.total_labs || 0 }} {{ isRtl ? 'معمل' : 'labs' }}</p>
                </div>
                <div class="dental-card-enter bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80" style="animation-delay:0.25s">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'متوسط التسليم' : 'Avg Delivery' }}</p>
                    <p class="text-2xl font-bold text-[#1B365D] mt-1">{{ Number(summary?.avg_delivery_days || 0).toFixed(1) }} <span class="text-sm font-normal text-gray-400">{{ isRtl ? 'يوم' : 'days' }}</span></p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="dental-card-enter flex flex-wrap items-center gap-1 bg-white rounded-xl p-1 border border-gray-100 shadow-sm" style="animation-delay:0.3s">
                <button v-for="tab in [
                    { key: 'quality', icon: '⭐', ar: 'تصنيف الجودة', en: 'Quality Ranking' },
                    { key: 'prices', icon: '💰', ar: 'مقارنة الأسعار', en: 'Price Comparison' },
                    { key: 'recommendations', icon: '🏆', ar: 'التوصيات', en: 'Recommendations' },
                    { key: 'profitability', icon: '📊', ar: 'الربحية حسب النوع', en: 'Profit by Type' },
                    { key: 'trend', icon: '📈', ar: 'الاتجاه الشهري', en: 'Monthly Trend' },
                ]" :key="tab.key" @click="activeTab = tab.key"
                    :class="activeTab === tab.key ? 'bg-emerald-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                    class="px-4 py-2.5 rounded-lg text-xs font-medium transition-all whitespace-nowrap">
                    {{ tab.icon }} {{ isRtl ? tab.ar : tab.en }}
                </button>
            </div>

            <!-- ═══ TAB: Quality Ranking ═══ -->
            <div v-if="activeTab === 'quality'" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay:0.35s">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">{{ isRtl ? 'تصنيف المعامل حسب الجودة' : 'Lab Quality Ranking' }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'نقاط الجودة = 40% التسليم بالموعد + 30% قلة التعديلات + 30% هامش الربح' : 'Score = 40% on-time + 30% low adjustments + 30% profit margin' }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 uppercase w-8">#</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المعمل' : 'Lab' }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'النقاط' : 'Score' }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطلبات' : 'Orders' }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'نسبة التعديل' : 'Adj. Rate' }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التسليم بالموعد' : 'On-time' }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'متوسط أيام' : 'Avg Days' }}</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'متوسط التكلفة' : 'Avg Cost' }}</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الربح' : 'Profit' }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الهامش' : 'Margin' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(lab, idx) in labQuality" :key="lab.lab_name"
                                class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
                                :class="idx === 0 ? 'bg-emerald-50/30' : ''">
                                <td class="px-5 py-3.5">
                                    <span v-if="idx === 0" class="text-lg">🥇</span>
                                    <span v-else-if="idx === 1" class="text-lg">🥈</span>
                                    <span v-else-if="idx === 2" class="text-lg">🥉</span>
                                    <span v-else class="text-xs text-gray-400 font-mono">{{ idx + 1 }}</span>
                                </td>
                                <td class="px-5 py-3.5 font-semibold text-gray-900">{{ lab.lab_name }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <div class="w-10 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full" :class="scoreBg(lab.quality_score)" :style="{ width: lab.quality_score + '%' }"></div>
                                        </div>
                                        <span class="text-sm font-bold tabular-nums" :class="scoreColor(lab.quality_score)">{{ lab.quality_score }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center font-medium">{{ lab.total_orders }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span :class="lab.adjustment_rate <= 5 ? 'text-emerald-600' : lab.adjustment_rate <= 15 ? 'text-amber-600' : 'text-red-600'" class="font-semibold">
                                        {{ lab.adjustment_rate }}%
                                    </span>
                                    <span class="text-xs text-gray-400 block">{{ lab.adjustment_count }} {{ isRtl ? 'تعديل' : 'adj.' }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span v-if="lab.on_time_rate !== null" :class="lab.on_time_rate >= 80 ? 'text-emerald-600' : lab.on_time_rate >= 60 ? 'text-amber-600' : 'text-red-600'" class="font-semibold">
                                        {{ lab.on_time_rate }}%
                                    </span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span :class="lab.avg_delivery_days <= 5 ? 'text-emerald-600' : lab.avg_delivery_days <= 10 ? 'text-amber-600' : 'text-red-500'">
                                        {{ lab.avg_delivery_days ? Number(lab.avg_delivery_days).toFixed(1) : '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-end text-gray-600">{{ formatCurrency(lab.avg_cost) }}</td>
                                <td class="px-4 py-3.5 text-end font-semibold" :class="(lab.total_charge - lab.total_cost) >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                    {{ formatCurrency(lab.total_charge - lab.total_cost) }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-semibold" :class="marginColor(lab.profit_margin)">{{ lab.profit_margin }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!labQuality?.length" class="p-12 text-center text-gray-400 text-sm">
                    {{ isRtl ? 'لا توجد بيانات كافية للتحليل' : 'Not enough data for analysis' }}
                </div>
            </div>

            <!-- ═══ TAB: Price Comparison ═══ -->
            <div v-if="activeTab === 'prices'" class="space-y-4">
                <div v-for="(labs, type) in comparisonByItemType" :key="type"
                    class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900">{{ itemLabel(type) }}</h3>
                        <span class="text-xs text-gray-400">{{ labs.length }} {{ isRtl ? 'معمل' : 'labs' }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="px-5 py-2.5 text-start text-xs font-semibold text-gray-500">{{ isRtl ? 'المعمل' : 'Lab' }}</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500">{{ isRtl ? 'عدد الطلبات' : 'Orders' }}</th>
                                    <th class="px-4 py-2.5 text-end text-xs font-semibold text-gray-500">{{ isRtl ? 'أقل سعر' : 'Min' }}</th>
                                    <th class="px-4 py-2.5 text-end text-xs font-semibold text-gray-500">{{ isRtl ? 'متوسط السعر' : 'Avg Cost' }}</th>
                                    <th class="px-4 py-2.5 text-end text-xs font-semibold text-gray-500">{{ isRtl ? 'أعلى سعر' : 'Max' }}</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500">{{ isRtl ? 'متوسط أيام' : 'Avg Days' }}</th>
                                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500">{{ isRtl ? 'التعديلات' : 'Adjustments' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(lab, idx) in labs" :key="lab.lab_name"
                                    class="border-b border-gray-50 hover:bg-gray-50/50"
                                    :class="idx === 0 ? 'bg-emerald-50/30' : ''">
                                    <td class="px-5 py-3 font-medium text-gray-900">
                                        <span v-if="idx === 0" class="text-emerald-500 me-1">★</span>
                                        {{ lab.lab_name }}
                                        <span v-if="idx === 0" class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full ms-1 font-semibold">
                                            {{ isRtl ? 'الأرخص' : 'Best Price' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ lab.order_count }}</td>
                                    <td class="px-4 py-3 text-end text-gray-500">{{ formatCurrency(lab.min_cost) }}</td>
                                    <td class="px-4 py-3 text-end font-semibold text-gray-900">{{ formatCurrency(lab.avg_cost) }}</td>
                                    <td class="px-4 py-3 text-end text-gray-500">{{ formatCurrency(lab.max_cost) }}</td>
                                    <td class="px-4 py-3 text-center" :class="lab.avg_delivery_days && lab.avg_delivery_days <= 5 ? 'text-emerald-600' : 'text-gray-600'">
                                        {{ lab.avg_delivery_days ? Number(lab.avg_delivery_days).toFixed(1) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center" :class="lab.adjustment_count > 0 ? 'text-red-500 font-semibold' : 'text-gray-400'">{{ lab.adjustment_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="!Object.keys(comparisonByItemType).length" class="bg-white rounded-2xl p-12 text-center text-gray-400 text-sm shadow-sm border border-gray-100">
                    {{ isRtl ? 'لا توجد بيانات كافية للمقارنة' : 'Not enough data for comparison' }}
                </div>
            </div>

            <!-- ═══ TAB: Recommendations ═══ -->
            <div v-if="activeTab === 'recommendations'" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div v-for="(labs, type) in bestLabPerType" :key="type"
                    class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100">
                        <h3 class="text-sm font-bold text-gray-900">{{ itemLabel(type) }}</h3>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="(lab, idx) in labs.slice(0, 3)" :key="lab.lab_name"
                            class="px-5 py-3.5 flex items-center gap-3"
                            :class="idx === 0 ? 'bg-emerald-50/40' : ''">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                :class="idx === 0 ? 'bg-emerald-100' : idx === 1 ? 'bg-gray-100' : 'bg-gray-50'">
                                <span v-if="idx === 0" class="text-sm">🏆</span>
                                <span v-else class="text-xs text-gray-400 font-bold">{{ idx + 1 }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ lab.lab_name }}</p>
                                <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-500">
                                    <span>{{ formatCurrency(lab.avg_cost) }} {{ isRtl ? 'متوسط' : 'avg' }}</span>
                                    <span>{{ lab.avg_days ? Number(lab.avg_days).toFixed(0) + (isRtl ? ' يوم' : 'd') : '-' }}</span>
                                    <span :class="lab.adj_rate <= 5 ? 'text-emerald-500' : 'text-amber-500'">{{ lab.adj_rate }}% {{ isRtl ? 'تعديل' : 'adj' }}</span>
                                </div>
                            </div>
                            <div class="text-end shrink-0">
                                <span class="text-sm font-bold" :class="scoreColor(lab.recommendation_score)">{{ lab.recommendation_score }}</span>
                                <p class="text-[10px] text-gray-400 uppercase">{{ isRtl ? 'نقاط' : 'pts' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="!Object.keys(bestLabPerType || {}).length" class="col-span-2 bg-white rounded-2xl p-12 text-center text-gray-400 text-sm shadow-sm border border-gray-100">
                    {{ isRtl ? 'لا توجد بيانات كافية (يلزم 3 طلبات على الأقل لكل معمل)' : 'Not enough data (min 3 orders per lab required)' }}
                </div>
            </div>

            <!-- ═══ TAB: Profitability by Type ═══ -->
            <div v-if="activeTab === 'profitability'" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay:0.35s">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">{{ isRtl ? 'الربحية حسب نوع العمل' : 'Profitability by Item Type' }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'النوع' : 'Type' }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطلبات' : 'Orders' }}</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الإيراد' : 'Revenue' }}</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الربح' : 'Profit' }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الهامش' : 'Margin' }}</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase w-48">{{ isRtl ? 'الربح' : 'Profit Bar' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in profitByType" :key="item.item_type" class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-5 py-3.5 font-semibold text-gray-900">{{ itemLabel(item.item_type) }}</td>
                                <td class="px-4 py-3.5 text-center">{{ item.order_count }}</td>
                                <td class="px-4 py-3.5 text-end text-gray-600">{{ formatCurrency(item.total_cost) }}</td>
                                <td class="px-4 py-3.5 text-end text-gray-600">{{ formatCurrency(item.total_charge) }}</td>
                                <td class="px-4 py-3.5 text-end font-bold" :class="item.total_profit >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                    {{ formatCurrency(item.total_profit) }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-semibold" :class="marginColor(item.profit_margin || 0)">{{ item.profit_margin || 0 }}%</td>
                                <td class="px-5 py-3.5">
                                    <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all"
                                            :class="item.total_profit >= 0 ? 'bg-emerald-400' : 'bg-red-400'"
                                            :style="{ width: Math.min(100, Math.abs(item.total_profit) / maxProfit * 100) + '%' }"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!profitByType?.length" class="p-12 text-center text-gray-400 text-sm">
                    {{ isRtl ? 'لا توجد بيانات' : 'No data available' }}
                </div>
            </div>

            <!-- ═══ TAB: Monthly Trend ═══ -->
            <div v-if="activeTab === 'trend'" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6" style="animation-delay:0.35s">
                <h2 class="text-sm font-bold text-gray-900 mb-6">{{ isRtl ? 'الاتجاه الشهري للربحية' : 'Monthly Profitability Trend' }}</h2>

                <div v-if="monthlyProfit?.length" class="space-y-6">
                    <!-- Bar Chart -->
                    <div class="flex items-end gap-3 h-48">
                        <div v-for="(m, idx) in monthlyProfit" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
                            <div class="text-xs font-bold tabular-nums" :class="m.profit >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ formatCurrency(m.profit) }}
                            </div>
                            <div class="w-full flex gap-1" style="align-items: flex-end; height: 120px;">
                                <div class="flex-1 bg-gray-300 rounded-t-sm transition-all"
                                    :style="{ height: Math.max(4, m.total_cost / maxMonthlyCharge * 120) + 'px' }"
                                    :title="isRtl ? 'التكلفة: ' + formatCurrency(m.total_cost) : 'Cost: ' + formatCurrency(m.total_cost)">
                                </div>
                                <div class="flex-1 bg-emerald-400 rounded-t-sm transition-all"
                                    :style="{ height: Math.max(4, m.total_charge / maxMonthlyCharge * 120) + 'px' }"
                                    :title="isRtl ? 'الإيراد: ' + formatCurrency(m.total_charge) : 'Revenue: ' + formatCurrency(m.total_charge)">
                                </div>
                            </div>
                            <div class="text-[10px] text-gray-500 font-medium">{{ monthLabels[idx] }}</div>
                            <div class="text-[10px] text-gray-400">{{ m.order_count }}</div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="flex items-center justify-center gap-6">
                        <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-sm bg-gray-300"></div><span class="text-xs text-gray-500">{{ isRtl ? 'التكلفة' : 'Cost' }}</span></div>
                        <div class="flex items-center gap-1.5"><div class="w-3 h-3 rounded-sm bg-emerald-400"></div><span class="text-xs text-gray-500">{{ isRtl ? 'الإيراد' : 'Revenue' }}</span></div>
                    </div>

                    <!-- Summary Row -->
                    <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-100">
                        <div v-for="m in monthlyProfit.slice(-3)" :key="m.month" class="text-center">
                            <p class="text-xs text-gray-400">{{ monthLabels[monthlyProfit.indexOf(m)] }}</p>
                            <p class="text-lg font-bold" :class="m.profit >= 0 ? 'text-emerald-600' : 'text-red-500'">{{ formatCurrency(m.profit) }}</p>
                            <p class="text-xs text-gray-400">{{ m.order_count }} {{ isRtl ? 'طلب' : 'orders' }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center text-gray-400 text-sm">
                    {{ isRtl ? 'لا توجد بيانات كافية' : 'Not enough data' }}
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
</style>
