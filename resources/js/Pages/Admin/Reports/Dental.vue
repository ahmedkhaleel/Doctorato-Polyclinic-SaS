<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';
import BarChart from '@/Components/Admin/BarChart.vue';
import DonutChart from '@/Components/Admin/DonutChart.vue';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    filters: Object,
    treatmentRevenue: Object,
    consultationRevenue: Number,
    labOrderCosts: Object,
    prevPeriod: Object,
    revenueByType: Array,
    monthlyRevenue: Array,
    revenueByDoctor: Array,
    labStats: Object,
    labPerformance: Array,
    labByStatus: Array,
    treatmentStats: Object,
    planStats: Object,
    topPatients: Array,
    chartStats: Array,
    treatmentTypeBreakdown: Array,
    riskPatientsCount: Number,
    followupStats: Object,
    doctorProductivity: Array,
    patientRetention: Object,
});

/* ── Growth helper ──────────────────────────── */
function calcGrowth(current, previous) {
    if (!previous || previous === 0) return current > 0 ? 100 : 0;
    return Math.round(((current - previous) / previous) * 100);
}

const revenueGrowth = computed(() => {
    const prevTotal = (props.prevPeriod?.treatment_revenue || 0) + (props.prevPeriod?.consultation_revenue || 0);
    return calcGrowth(totalRevenue.value, prevTotal);
});

const treatmentCountGrowth = computed(() => {
    return calcGrowth(props.treatmentRevenue?.count || 0, props.prevPeriod?.treatment_count || 0);
});

const labOrderGrowth = computed(() => {
    return calcGrowth(props.labOrderCosts?.count || 0, props.prevPeriod?.lab_orders || 0);
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

let dateTimeout = null;
watch([dateFrom, dateTo], () => {
    clearTimeout(dateTimeout);
    dateTimeout = setTimeout(() => {
        router.get('/admin/reports/dental', {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const activeTab = ref('revenue');
const tabs = [
    { key: 'revenue', label: 'Revenue', labelAr: 'الإيرادات', icon: 'revenue' },
    { key: 'lab', label: 'Lab Performance', labelAr: 'أداء المعمل', icon: 'lab' },
    { key: 'treatments', label: 'Treatment Progress', labelAr: 'تقدم العلاج', icon: 'treatment' },
    { key: 'followups', label: 'Follow-ups & Retention', labelAr: 'المتابعات والاستبقاء', icon: 'followup' },
    { key: 'productivity', label: 'Doctor Productivity', labelAr: 'إنتاجية الأطباء', icon: 'productivity' },
    { key: 'chart', label: 'Chart Overview', labelAr: 'نظرة عامة', icon: 'chart' },
];

/* ── Revenue ───────────────────────────────── */
const totalRevenue = computed(() => {
    return (props.treatmentRevenue?.treatment_cost || 0)
         + (props.treatmentRevenue?.lab_cost || 0)
         + (props.consultationRevenue || 0);
});

const labProfit = computed(() => {
    return (props.labOrderCosts?.total_charge || 0) - (props.labOrderCosts?.total_cost || 0);
});

const revenueTypeData = computed(() => {
    return (props.revenueByType || []).map(r => ({
        label: r.treatment_type?.replace(/_/g, ' ') || '?',
        value: parseFloat(r.total) || 0,
    }));
});

const monthlyData = computed(() => {
    return (props.monthlyRevenue || []).map(m => ({
        label: m.month,
        value: parseFloat(m.treatment_revenue) + parseFloat(m.lab_revenue),
    }));
});

/* ── Lab ───────────────────────────────────── */
const labDeliveryRate = computed(() => {
    if (!props.labStats?.total_orders) return 0;
    return Math.round((props.labStats.delivered / props.labStats.total_orders) * 100);
});

const labOverdueRate = computed(() => {
    if (!props.labStats?.total_orders) return 0;
    return Math.round((props.labStats.overdue / props.labStats.total_orders) * 100);
});

const labStatusData = computed(() => {
    const colors = {
        ordered: '#3B82F6',
        in_production: '#F59E0B',
        ready: '#8B5CF6',
        delivered: '#10B981',
        adjustment: '#EF4444',
        completed: '#06B6D4',
    };
    return (props.labByStatus || []).map(s => ({
        label: s.status?.replace(/_/g, ' ') || '?',
        value: parseInt(s.count),
        color: colors[s.status] || '#6B7280',
    }));
});

/* ── Treatments ────────────────────────────── */
const treatmentCompletionRate = computed(() => {
    if (!props.treatmentStats?.total) return 0;
    return Math.round((props.treatmentStats.completed / props.treatmentStats.total) * 100);
});

const treatmentStatusData = computed(() => {
    const s = props.treatmentStats || {};
    return [
        { label: 'Completed', value: s.completed || 0, color: '#10B981' },
        { label: 'In Progress', value: s.in_progress || 0, color: '#F59E0B' },
        { label: 'Planned', value: s.planned || 0, color: '#3B82F6' },
        { label: 'Cancelled', value: s.cancelled || 0, color: '#EF4444' },
    ].filter(d => d.value > 0);
});

/* ── Chart Stats ───────────────────────────── */
const chartConditionData = computed(() => {
    const colors = {
        healthy: '#22c55e', decayed: '#ef4444', filled: '#3b82f6', missing: '#6b7280',
        crown: '#f59e0b', bridge: '#8b5cf6', implant: '#06b6d4', root_canal: '#ec4899', extracted: '#1f2937',
    };
    return (props.chartStats || []).map(c => ({
        label: c.condition?.replace(/_/g, ' ') || '?',
        value: parseInt(c.count),
        color: colors[c.condition] || '#6B7280',
    }));
});

function formatType(type) {
    return (type || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatStatus(status) {
    return (status || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<template>
    <AdminLayout>
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">
                        {{ isRtl ? 'تقارير الأسنان' : 'Dental Reports' }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ isRtl ? 'إيرادات، أداء المعمل، تقدم العلاج' : 'Revenue, lab performance, treatment progress' }}
                    </p>
                </div>

                <!-- Date Filters -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <input type="date" v-model="dateFrom" class="doctorato-input text-sm border-0 focus:ring-0 p-0 w-32 bg-transparent" />
                        <span class="text-gray-400">→</span>
                        <input type="date" v-model="dateTo" class="doctorato-input text-sm border-0 focus:ring-0 p-0 w-32 bg-transparent" />
                    </div>

                    <!-- Export Buttons -->
                    <a :href="`/admin/exports/dental-treatments?date_from=${dateFrom}&date_to=${dateTo}`"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-50 text-[#1B365D] rounded-xl text-xs font-semibold hover:bg-slate-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ isRtl ? 'تصدير العلاجات' : 'Export Treatments' }}
                    </a>
                    <a :href="`/admin/exports/dental-lab-orders?date_from=${dateFrom}&date_to=${dateTo}`"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-50 text-[#1B365D] rounded-xl text-xs font-semibold hover:bg-slate-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ isRtl ? 'تصدير المعمل' : 'Lab Orders' }}
                    </a>
                    <a :href="`/admin/exports/dental-treatment-plans?date_from=${dateFrom}&date_to=${dateTo}`"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-50 text-[#1B365D] rounded-xl text-xs font-semibold hover:bg-slate-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ isRtl ? 'تصدير الخطط' : 'Plans' }}
                    </a>
                    <a :href="`/admin/exports/dental-followups?date_from=${dateFrom}&date_to=${dateTo}`"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-50 text-amber-700 rounded-xl text-xs font-semibold hover:bg-amber-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ isRtl ? 'تصدير المتابعات' : 'Follow-ups' }}
                    </a>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 mb-6 bg-gray-100 rounded-xl p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200"
                    :class="activeTab === tab.key
                        ? 'bg-white text-[#1B365D] shadow-sm'
                        : 'text-gray-500 hover:text-gray-700'"
                >
                    {{ isRtl ? tab.labelAr : tab.label }}
                </button>
            </div>

            <!-- ═══════════════════ REVENUE TAB ═══════════════════ -->
            <div v-show="activeTab === 'revenue'" class="space-y-6">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm"
                         :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-4 opacity-0': !mounted }"
                         style="transition: all 0.4s ease;">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-500">{{ isRtl ? 'إجمالي الإيرادات' : 'Total Revenue' }}</span>
                        </div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ formatCurrency(totalRevenue) }}</div>
                        <div v-if="prevPeriod" class="flex items-center gap-1.5 mt-1.5">
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                                :class="revenueGrowth >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                                <svg class="w-2.5 h-2.5" :class="revenueGrowth >= 0 ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" /></svg>
                                {{ Math.abs(revenueGrowth) }}%
                            </span>
                            <span class="text-[10px] text-gray-400">{{ isRtl ? 'مقارنة بالفترة السابقة' : 'vs prev period' }}</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm"
                         :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-4 opacity-0': !mounted }"
                         style="transition: all 0.5s ease;">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-500">{{ isRtl ? 'إيرادات العلاج' : 'Treatment Revenue' }}</span>
                        </div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ formatCurrency(treatmentRevenue?.treatment_cost) }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-400">{{ treatmentRevenue?.count || 0 }} {{ isRtl ? 'علاج مكتمل' : 'completed' }}</span>
                            <span v-if="prevPeriod" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                                :class="treatmentCountGrowth >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                                <svg class="w-2.5 h-2.5" :class="treatmentCountGrowth >= 0 ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" /></svg>
                                {{ Math.abs(treatmentCountGrowth) }}%
                            </span>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm"
                         :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-4 opacity-0': !mounted }"
                         style="transition: all 0.6s ease;">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-500">{{ isRtl ? 'إيرادات الكشف' : 'Consultation Revenue' }}</span>
                        </div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ formatCurrency(consultationRevenue) }}</div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm"
                         :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-4 opacity-0': !mounted }"
                         style="transition: all 0.7s ease;">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-500">{{ isRtl ? 'ربح المعمل' : 'Lab Profit' }}</span>
                        </div>
                        <div class="text-xl md:text-2xl font-bold" :class="labProfit >= 0 ? 'text-emerald-600' : 'text-red-600'">{{ formatCurrency(labProfit) }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-400">{{ labOrderCosts?.count || 0 }} {{ isRtl ? 'طلب' : 'orders' }}</span>
                            <span v-if="prevPeriod" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                                :class="labOrderGrowth >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                                <svg class="w-2.5 h-2.5" :class="labOrderGrowth >= 0 ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" /></svg>
                                {{ Math.abs(labOrderGrowth) }}%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Revenue Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Revenue by Treatment Type -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'الإيرادات حسب نوع العلاج' : 'Revenue by Treatment Type' }}</h3>
                        <BarChart v-if="revenueTypeData.length > 0" :data="revenueTypeData" color="#C4A265" :height="220" />
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data available' }}</div>
                    </div>

                    <!-- Monthly Revenue Trend -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'اتجاه الإيرادات الشهرية' : 'Monthly Revenue Trend' }}</h3>
                        <BarChart v-if="monthlyData.length > 0" :data="monthlyData" color="#C4A265" :height="220" />
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data available' }}</div>
                    </div>
                </div>

                <!-- Revenue by Doctor -->
                <div v-if="revenueByDoctor?.length > 0" class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'الإيرادات حسب الطبيب' : 'Revenue by Doctor' }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                                    <th class="text-center py-3 px-4 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'العلاجات' : 'Treatments' }}</th>
                                    <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الإيرادات' : 'Revenue' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="doc in revenueByDoctor" :key="doc.id" class="border-b border-gray-50 hover:bg-slate-50/30 transition">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <img v-if="doc.photo" :src="`/storage/${doc.photo}`" class="w-8 h-8 rounded-full object-cover" alt="" />
                                            <div v-else class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[#1B365D] text-xs font-bold">
                                                {{ (doc.name_en || '?')[0] }}
                                            </div>
                                            <span class="font-medium text-gray-800">{{ isRtl ? doc.name_ar : doc.name_en }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3 px-4 text-gray-600">{{ doc.treatments_count }}</td>
                                    <td class="text-right py-3 px-4 font-semibold text-[#1B365D]">{{ formatCurrency(doc.total_revenue) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════ LAB TAB ═══════════════════ -->
            <div v-show="activeTab === 'lab'" class="space-y-6">
                <!-- Lab KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'إجمالي الطلبات' : 'Total Orders' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ labStats?.total_orders || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'نسبة التسليم' : 'Delivery Rate' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-emerald-600">{{ labDeliveryRate }}%</div>
                        <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${labDeliveryRate}%` }"></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'نسبة التأخير' : 'Overdue Rate' }}</div>
                        <div class="text-xl md:text-2xl font-bold" :class="labOverdueRate > 20 ? 'text-red-600' : labOverdueRate > 10 ? 'text-amber-600' : 'text-emerald-600'">{{ labOverdueRate }}%</div>
                        <div class="text-xs text-gray-400 mt-1">{{ labStats?.overdue || 0 }} {{ isRtl ? 'متأخر' : 'overdue' }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'متوسط وقت التسليم' : 'Avg Delivery Time' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ labStats?.avg_delivery_days || 0 }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ isRtl ? 'يوم' : 'days' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Lab Orders by Status -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'الطلبات حسب الحالة' : 'Orders by Status' }}</h3>
                        <DonutChart v-if="labStatusData.length > 0" :data="labStatusData" :size="200" />
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</div>
                    </div>

                    <!-- Lab Performance Table -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'أداء المعامل' : 'Lab Performance' }}</h3>
                        <div v-if="labPerformance?.length > 0" class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="text-left py-2 px-3 text-xs font-semibold text-gray-500">{{ isRtl ? 'المعمل' : 'Lab' }}</th>
                                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500">{{ isRtl ? 'الطلبات' : 'Orders' }}</th>
                                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500">{{ isRtl ? 'متوسط الأيام' : 'Avg Days' }}</th>
                                        <th class="text-center py-2 px-3 text-xs font-semibold text-gray-500">{{ isRtl ? 'متأخر' : 'Overdue' }}</th>
                                        <th class="text-right py-2 px-3 text-xs font-semibold text-gray-500">{{ isRtl ? 'الربح' : 'Profit' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="lab in labPerformance" :key="lab.lab_name" class="border-b border-gray-50 hover:bg-gray-50 transition">
                                        <td class="py-2.5 px-3 font-medium text-gray-800">{{ lab.lab_name }}</td>
                                        <td class="text-center py-2.5 px-3 text-gray-600">{{ lab.total_orders }}</td>
                                        <td class="text-center py-2.5 px-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                                  :class="parseFloat(lab.avg_days) > 10 ? 'bg-red-50 text-red-600' : parseFloat(lab.avg_days) > 7 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'">
                                                {{ parseFloat(lab.avg_days || 0).toFixed(1) }}d
                                            </span>
                                        </td>
                                        <td class="text-center py-2.5 px-3">
                                            <span v-if="lab.overdue > 0" class="text-red-600 font-semibold">{{ lab.overdue }}</span>
                                            <span v-else class="text-emerald-600">0</span>
                                        </td>
                                        <td class="text-right py-2.5 px-3 font-semibold" :class="(parseFloat(lab.total_charge) - parseFloat(lab.total_cost)) >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                            {{ formatCurrency(parseFloat(lab.total_charge || 0) - parseFloat(lab.total_cost || 0)) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════ TREATMENTS TAB ═══════════════════ -->
            <div v-show="activeTab === 'treatments'" class="space-y-6">
                <!-- Treatment KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'إجمالي العلاجات' : 'Total Treatments' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ treatmentStats?.total || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'نسبة الإنجاز' : 'Completion Rate' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-emerald-600">{{ treatmentCompletionRate }}%</div>
                        <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" :style="{ width: `${treatmentCompletionRate}%` }"></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'خطط العلاج النشطة' : 'Active Plans' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ planStats?.active || 0 }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ planStats?.total || 0 }} {{ isRtl ? 'إجمالي' : 'total' }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'التكلفة المقدرة' : 'Estimated Cost' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ formatCurrency(planStats?.estimated_total) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ isRtl ? 'فعلي:' : 'Actual:' }} {{ formatCurrency(planStats?.actual_total) }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Treatment Status Distribution -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'حالة العلاجات' : 'Treatment Status Distribution' }}</h3>
                        <DonutChart v-if="treatmentStatusData.length > 0" :data="treatmentStatusData" :size="200" />
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</div>
                    </div>

                    <!-- Top Patients -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'أكثر المرضى علاجاً' : 'Top Patients by Treatments' }}</h3>
                        <div v-if="topPatients?.length > 0" class="space-y-3 max-h-[350px] overflow-y-auto">
                            <div v-for="(p, i) in topPatients" :key="p.id" class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                                          :class="i < 3 ? 'bg-slate-100 text-[#1B365D]' : 'bg-gray-100 text-gray-600'">{{ i + 1 }}</span>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-800">{{ p.full_name }}</div>
                                        <div class="text-xs text-gray-400">#{{ p.file_number }} &bull; {{ p.completed_count }}/{{ p.treatments_count }} {{ isRtl ? 'مكتمل' : 'done' }}</div>
                                    </div>
                                </div>
                                <div class="text-sm font-semibold text-[#1B365D]">{{ formatCurrency(p.total_cost) }}</div>
                            </div>
                        </div>
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════ FOLLOW-UPS & RETENTION TAB ═══════════════════ -->
            <div v-show="activeTab === 'followups'" class="space-y-6">
                <!-- Follow-up KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'إجمالي المتابعات' : 'Total Follow-ups' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-gray-900">{{ followupStats?.total || 0 }}</div>
                        <div class="flex items-center gap-2 mt-1.5 text-[10px]">
                            <span class="text-amber-600">{{ followupStats?.pending || 0 }} {{ isRtl ? 'بانتظار' : 'pending' }}</span>
                            <span v-if="followupStats?.overdue > 0" class="text-red-600 font-bold">{{ followupStats?.overdue }} {{ isRtl ? 'متأخر' : 'overdue' }}</span>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'نسبة الحجز' : 'Booking Rate' }}</div>
                        <div class="text-xl md:text-2xl font-bold" :class="(followupStats?.booking_rate || 0) >= 70 ? 'text-emerald-600' : (followupStats?.booking_rate || 0) >= 40 ? 'text-amber-600' : 'text-red-600'">
                            {{ followupStats?.booking_rate || 0 }}%
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                            <div class="h-2 rounded-full transition-all duration-500"
                                 :class="(followupStats?.booking_rate || 0) >= 70 ? 'bg-emerald-500' : (followupStats?.booking_rate || 0) >= 40 ? 'bg-amber-500' : 'bg-red-500'"
                                 :style="{ width: `${followupStats?.booking_rate || 0}%` }"></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'المتابعات المكتملة' : 'Completed Follow-ups' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-emerald-600">{{ followupStats?.completed || 0 }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ followupStats?.cancelled || 0 }} {{ isRtl ? 'ملغي' : 'cancelled' }}</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 shadow-sm">
                        <div class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'تم الحجز' : 'Booked' }}</div>
                        <div class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ followupStats?.booked || 0 }}</div>
                    </div>
                </div>

                <!-- Follow-up Status Breakdown -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'توزيع حالات المتابعات' : 'Follow-up Status Distribution' }}</h3>
                        <DonutChart v-if="followupStats?.total > 0" :data="[
                            { label: isRtl ? 'بانتظار' : 'Pending', value: followupStats?.pending || 0, color: '#F59E0B' },
                            { label: isRtl ? 'متأخر' : 'Overdue', value: followupStats?.overdue || 0, color: '#EF4444' },
                            { label: isRtl ? 'محجوز' : 'Booked', value: followupStats?.booked || 0, color: '#3B82F6' },
                            { label: isRtl ? 'مكتمل' : 'Completed', value: followupStats?.completed || 0, color: '#10B981' },
                            { label: isRtl ? 'ملغي' : 'Cancelled', value: followupStats?.cancelled || 0, color: '#6B7280' },
                        ].filter(d => d.value > 0)" :size="200" />
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</div>
                    </div>

                    <!-- Patient Retention -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'استبقاء المرضى' : 'Patient Retention' }}</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-slate-50 to-teal-50 border border-slate-100">
                                <div>
                                    <p class="text-xs font-medium text-gray-500">{{ isRtl ? 'إجمالي المرضى' : 'Total Patients' }}</p>
                                    <p class="text-xl md:text-2xl font-bold text-gray-900 mt-1">{{ patientRetention?.total_patients || 0 }}</p>
                                </div>
                                <div class="w-14 h-14 rounded-xl bg-white/80 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                                    <p class="text-xs font-medium text-emerald-600">{{ isRtl ? 'مرضى عائدون' : 'Returning' }}</p>
                                    <p class="text-xl font-bold text-emerald-700 mt-1">{{ patientRetention?.returning_patients || 0 }}</p>
                                </div>
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-xs font-medium text-[#1B365D]">{{ isRtl ? 'مرضى جدد' : 'New' }}</p>
                                    <p class="text-xl font-bold text-[#1B365D] mt-1">{{ patientRetention?.new_patients || 0 }}</p>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-medium text-gray-600">{{ isRtl ? 'نسبة الاستبقاء' : 'Retention Rate' }}</p>
                                    <span class="text-sm font-bold" :class="(patientRetention?.retention_rate || 0) >= 50 ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ patientRetention?.retention_rate || 0 }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all duration-700"
                                         :class="(patientRetention?.retention_rate || 0) >= 50 ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : 'bg-gradient-to-r from-amber-400 to-amber-600'"
                                         :style="{ width: `${patientRetention?.retention_rate || 0}%` }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════ DOCTOR PRODUCTIVITY TAB ═══════════════════ -->
            <div v-show="activeTab === 'productivity'" class="space-y-6">
                <div v-if="doctorProductivity?.length > 0" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'مقارنة إنتاجية الأطباء' : 'Doctor Productivity Comparison' }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'عدد العلاجات والإيرادات ومتوسط وقت الإنجاز' : 'Treatments, revenue, and average completion time' }}</p>
                    </div>

                    <div class="divide-y divide-gray-50">
                        <div v-for="(doc, idx) in doctorProductivity" :key="doc.id"
                             class="px-4 md:px-6 py-5 hover:bg-slate-50/20 transition-colors duration-200">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-4 min-w-0 flex-1">
                                    <!-- Rank -->
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                         :class="idx === 0 ? 'bg-amber-100 text-amber-700' : idx === 1 ? 'bg-gray-100 text-gray-600' : idx === 2 ? 'bg-amber-100 text-[#C4A265]' : 'bg-gray-50 text-gray-400'">
                                        {{ idx + 1 }}
                                    </div>
                                    <!-- Doctor info -->
                                    <div class="flex items-center gap-3 min-w-0">
                                        <img v-if="doc.photo" :src="`/storage/${doc.photo}`" class="w-10 h-10 rounded-full object-cover flex-shrink-0" alt="" />
                                        <div v-else class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-[#1B365D] text-sm font-bold flex-shrink-0">
                                            {{ (doc.name_en || '?')[0] }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ isRtl ? doc.name_ar : doc.name_en }}</p>
                                            <div class="flex items-center gap-3 mt-1 text-[11px]">
                                                <span class="text-gray-500">{{ doc.total_treatments }} {{ isRtl ? 'علاج' : 'treatments' }}</span>
                                                <span class="text-emerald-600 font-medium">{{ doc.completed }} {{ isRtl ? 'مكتمل' : 'completed' }}</span>
                                                <span v-if="doc.in_progress > 0" class="text-[#1B365D]">{{ doc.in_progress }} {{ isRtl ? 'جاري' : 'in progress' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-6 flex-shrink-0">
                                    <!-- Completion Rate -->
                                    <div class="text-center">
                                        <p class="text-[10px] font-medium text-gray-400 uppercase">{{ isRtl ? 'نسبة الإنجاز' : 'Rate' }}</p>
                                        <p class="text-lg font-bold mt-0.5"
                                           :class="doc.total_treatments > 0 && (doc.completed / doc.total_treatments * 100) >= 70 ? 'text-emerald-600' : 'text-amber-600'">
                                            {{ doc.total_treatments > 0 ? Math.round(doc.completed / doc.total_treatments * 100) : 0 }}%
                                        </p>
                                    </div>

                                    <!-- Avg Completion Days -->
                                    <div class="text-center">
                                        <p class="text-[10px] font-medium text-gray-400 uppercase">{{ isRtl ? 'متوسط الأيام' : 'Avg Days' }}</p>
                                        <p class="text-lg font-bold text-gray-700 mt-0.5">
                                            {{ doc.avg_completion_days ? parseFloat(doc.avg_completion_days).toFixed(1) : '-' }}
                                        </p>
                                    </div>

                                    <!-- Revenue -->
                                    <div class="text-right">
                                        <p class="text-[10px] font-medium text-gray-400 uppercase">{{ isRtl ? 'الإيرادات' : 'Revenue' }}</p>
                                        <p class="text-lg font-bold text-[#1B365D] mt-0.5">{{ formatCurrency(parseFloat(doc.treatment_revenue || 0) + parseFloat(doc.lab_cost || 0)) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Mini progress bar -->
                            <div class="mt-3 flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-gradient-to-r from-slate-400 to-[#1B365D] h-1.5 rounded-full transition-all duration-700"
                                         :style="{ width: `${doc.total_treatments > 0 ? Math.round(doc.completed / doc.total_treatments * 100) : 0}%` }"></div>
                                </div>
                                <span class="text-[10px] text-gray-400 font-mono w-12 text-right">{{ doc.completed }}/{{ doc.total_treatments }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white rounded-2xl border border-gray-200/80 p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا توجد بيانات إنتاجية في هذه الفترة' : 'No productivity data for this period' }}</p>
                </div>
            </div>

            <!-- ═══════════════════ CHART TAB ═══════════════════ -->
            <div v-show="activeTab === 'chart'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Condition Distribution -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'توزيع حالات الأسنان' : 'Tooth Condition Distribution' }}</h3>
                        <DonutChart v-if="chartConditionData.length > 0" :data="chartConditionData" :size="220" />
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data recorded' }}</div>
                    </div>

                    <!-- Condition Stats Table -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-4 md:p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'إحصائيات المخطط' : 'Chart Statistics' }}</h3>
                        <div v-if="chartStats?.length > 0" class="space-y-3">
                            <div v-for="stat in chartStats" :key="stat.condition"
                                 class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full"
                                          :style="{ backgroundColor: {
                                              healthy: '#22c55e', decayed: '#ef4444', filled: '#3b82f6', missing: '#6b7280',
                                              crown: '#f59e0b', bridge: '#8b5cf6', implant: '#06b6d4', root_canal: '#ec4899', extracted: '#1f2937'
                                          }[stat.condition] || '#6b7280' }"></span>
                                    <span class="text-sm font-medium text-gray-700">{{ formatType(stat.condition) }}</span>
                                </div>
                                <span class="text-sm font-bold text-gray-900">{{ stat.count }}</span>
                            </div>
                        </div>
                        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data' }}</div>
                    </div>
                </div>

                <!-- Treatment Type Breakdown -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تحليل أنواع العلاج' : 'Treatment Type Breakdown' }}</h3>
                        <span v-if="riskPatientsCount > 0" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            {{ riskPatientsCount }} {{ isRtl ? 'مرضى عالي الخطورة' : 'high-risk patients' }}
                        </span>
                    </div>
                    <div v-if="treatmentTypeBreakdown?.length > 0" class="divide-y divide-gray-50">
                        <div v-for="item in treatmentTypeBreakdown" :key="item.treatment_type"
                            class="px-4 md:px-6 py-3 flex items-center justify-between gap-4 hover:bg-gray-50/50 transition">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-xs font-bold text-[#1B365D]">
                                    {{ item.total }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 capitalize">{{ item.treatment_type?.replace(/_/g, ' ') }}</p>
                                    <div class="flex items-center gap-2 text-[10px] text-gray-400 mt-0.5">
                                        <span class="text-emerald-600">{{ item.completed }} {{ isRtl ? 'مكتمل' : 'done' }}</span>
                                        <span v-if="item.in_progress > 0" class="text-[#1B365D]">{{ item.in_progress }} {{ isRtl ? 'جاري' : 'active' }}</span>
                                        <span v-if="item.planned > 0" class="text-gray-500">{{ item.planned }} {{ isRtl ? 'مخطط' : 'planned' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-gray-900">{{ formatCurrency(parseFloat(item.total_cost) + parseFloat(item.total_lab_cost)) }}</p>
                                <p class="text-[10px] text-gray-400">{{ isRtl ? 'متوسط' : 'avg' }}: {{ formatCurrency(item.avg_cost) }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-4 md:p-8 text-center text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data for this period' }}</div>
                </div>

                <!-- Info about Chart PDF -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-[#1B365D]">{{ isRtl ? 'تصدير مخطط الأسنان PDF' : 'Export Dental Chart PDF' }}</h4>
                            <p class="text-sm text-[#1B365D] mt-1">
                                {{ isRtl
                                    ? 'لتصدير مخطط أسنان مريض معين كملف PDF، اذهب إلى صفحة المريض > الأسنان > مخطط الأسنان واضغط على زر التصدير.'
                                    : 'To export a specific patient\'s dental chart as PDF, navigate to the patient page > Dental > Dental Chart and click the export button.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
