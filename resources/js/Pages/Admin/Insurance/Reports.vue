<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    claimStats: Object,
    collectionRate: Number,
    companyPerformance: Array,
    monthlyTrend: Array,
    activeInsured: Number,
    avgProcessingDays: Number,
    aging: Object,
    filters: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

function applyFilters() {
    router.get('/admin/insurance/reports', { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true, replace: true })
}

function fmt(val) {
    return new Intl.NumberFormat(isRtl.value ? 'ar-EG' : 'en-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0)
}

function fmtInt(val) {
    return new Intl.NumberFormat(isRtl.value ? 'ar-EG' : 'en-EG').format(val || 0)
}

// Max bar for visual scaling
const maxClaimed = computed(() => Math.max(...(props.companyPerformance || []).map(c => c.total_claimed || 0), 1))

// Monthly chart max
const maxMonthly = computed(() => Math.max(...(props.monthlyTrend || []).map(m => Math.max(m.claimed || 0, m.paid || 0)), 1))
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'تقارير التأمين' : 'Insurance Reports' }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'تحليل المطالبات والتحصيل وأداء الشركات' : 'Claims analysis, collection rates & company performance' }}</p>
            </div>
        </div>

        <!-- Date Filters -->
        <div class="flex flex-wrap items-end gap-3 mb-6">
            <div>
                <label class="block text-xs text-gray-500 mb-1">{{ isRtl ? 'من' : 'From' }}</label>
                <input v-model="dateFrom" type="date" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">{{ isRtl ? 'إلى' : 'To' }}</label>
                <input v-model="dateTo" type="date" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
            </div>
            <button @click="applyFilters" class="px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#142849] text-sm font-medium transition">
                {{ isRtl ? 'تطبيق' : 'Apply' }}
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'إجمالي المطالبات' : 'Total Claims' }}</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ fmtInt(claimStats.total_claims) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'المبلغ المطالب' : 'Claimed' }}</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ fmt(claimStats.total_claimed) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'المحصل' : 'Collected' }}</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ fmt(claimStats.total_paid) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'نسبة التحصيل' : 'Collection Rate' }}</p>
                <p class="text-2xl font-bold mt-1" :class="collectionRate >= 80 ? 'text-emerald-600' : collectionRate >= 60 ? 'text-yellow-600' : 'text-red-600'">{{ collectionRate }}%</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'نسبة الرفض' : 'Rejection Rate' }}</p>
                <p class="text-2xl font-bold mt-1" :class="(claimStats.rejection_rate || 0) <= 10 ? 'text-emerald-600' : 'text-red-600'">{{ claimStats.rejection_rate || 0 }}%</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'متوسط أيام المعالجة' : 'Avg Processing Days' }}</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ avgProcessingDays }}</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mb-6">
            <!-- Claims Status Breakdown -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">{{ isRtl ? 'حالة المطالبات' : 'Claims Status' }}</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="text-sm text-gray-600">{{ isRtl ? 'مدفوعة' : 'Paid' }}</span>
                        </div>
                        <span class="font-bold text-gray-800">{{ fmtInt(claimStats.paid_claims) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                            <span class="text-sm text-gray-600">{{ isRtl ? 'قيد المعالجة' : 'Pending' }}</span>
                        </div>
                        <span class="font-bold text-gray-800">{{ fmtInt(claimStats.pending_claims) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="text-sm text-gray-600">{{ isRtl ? 'مرفوضة' : 'Rejected' }}</span>
                        </div>
                        <span class="font-bold text-gray-800">{{ fmtInt(claimStats.rejected_claims) }}</span>
                    </div>
                </div>
                <!-- Visual bar -->
                <div v-if="claimStats.total_claims > 0" class="flex h-3 rounded-full overflow-hidden mt-4 bg-gray-100">
                    <div class="bg-emerald-500 transition-all" :style="{ width: (claimStats.paid_claims / claimStats.total_claims * 100) + '%' }"></div>
                    <div class="bg-yellow-500 transition-all" :style="{ width: (claimStats.pending_claims / claimStats.total_claims * 100) + '%' }"></div>
                    <div class="bg-red-500 transition-all" :style="{ width: (claimStats.rejected_claims / claimStats.total_claims * 100) + '%' }"></div>
                </div>
            </div>

            <!-- Aging Analysis -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">{{ isRtl ? 'تقادم المطالبات المعلقة' : 'Pending Claims Aging' }}</h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500">0-30 {{ isRtl ? 'يوم' : 'days' }}</span>
                            <span class="font-bold text-emerald-600">{{ aging?.within_30 || 0 }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" :style="{ width: Math.min(100, ((aging?.within_30 || 0) / Math.max(1, (aging?.within_30||0) + (aging?.days_31_60||0) + (aging?.days_61_90||0) + (aging?.over_90||0))) * 100) + '%' }"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500">31-60 {{ isRtl ? 'يوم' : 'days' }}</span>
                            <span class="font-bold text-yellow-600">{{ aging?.days_31_60 || 0 }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-yellow-500 rounded-full" :style="{ width: Math.min(100, ((aging?.days_31_60 || 0) / Math.max(1, (aging?.within_30||0) + (aging?.days_31_60||0) + (aging?.days_61_90||0) + (aging?.over_90||0))) * 100) + '%' }"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500">61-90 {{ isRtl ? 'يوم' : 'days' }}</span>
                            <span class="font-bold text-orange-600">{{ aging?.days_61_90 || 0 }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-orange-500 rounded-full" :style="{ width: Math.min(100, ((aging?.days_61_90 || 0) / Math.max(1, (aging?.within_30||0) + (aging?.days_31_60||0) + (aging?.days_61_90||0) + (aging?.over_90||0))) * 100) + '%' }"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500">90+ {{ isRtl ? 'يوم' : 'days' }}</span>
                            <span class="font-bold text-red-600">{{ aging?.over_90 || 0 }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-red-500 rounded-full" :style="{ width: Math.min(100, ((aging?.over_90 || 0) / Math.max(1, (aging?.within_30||0) + (aging?.days_31_60||0) + (aging?.days_61_90||0) + (aging?.over_90||0))) * 100) + '%' }"></div></div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">{{ isRtl ? 'ملخص سريع' : 'Quick Summary' }}</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'المرضى المؤمنين النشطين' : 'Active Insured Patients' }}</span>
                        <span class="font-bold text-[#1B365D]">{{ fmtInt(activeInsured) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'المبلغ المعتمد' : 'Approved Amount' }}</span>
                        <span class="font-medium text-gray-800">{{ fmt(claimStats.total_approved) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ isRtl ? 'الفرق (معتمد - محصل)' : 'Gap (Approved - Collected)' }}</span>
                        <span class="font-medium" :class="(claimStats.total_approved - claimStats.total_paid) > 0 ? 'text-red-600' : 'text-emerald-600'">
                            {{ fmt((claimStats.total_approved || 0) - (claimStats.total_paid || 0)) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Trend Chart -->
        <div v-if="monthlyTrend?.length > 0" class="bg-white rounded-2xl border border-gray-100 p-5 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">{{ isRtl ? 'الاتجاه الشهري' : 'Monthly Trend' }}</h3>
            <div class="flex items-end gap-3 h-48">
                <div v-for="m in monthlyTrend" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex items-end gap-1 h-36">
                        <!-- Claimed bar -->
                        <div class="flex-1 bg-[#C4A265]/20 rounded-t-lg transition-all relative group" :style="{ height: (m.claimed / maxMonthly * 100) + '%', minHeight: m.claimed > 0 ? '4px' : '0' }">
                            <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-[9px] px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 whitespace-nowrap">{{ fmt(m.claimed) }}</div>
                        </div>
                        <!-- Paid bar -->
                        <div class="flex-1 bg-emerald-400 rounded-t-lg transition-all relative group" :style="{ height: (m.paid / maxMonthly * 100) + '%', minHeight: m.paid > 0 ? '4px' : '0' }">
                            <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-[9px] px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 whitespace-nowrap">{{ fmt(m.paid) }}</div>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-400">{{ m.month.slice(5) }}/{{ m.month.slice(2, 4) }}</span>
                </div>
            </div>
            <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-[#C4A265]/20"></span> {{ isRtl ? 'المطالب' : 'Claimed' }}</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-400"></span> {{ isRtl ? 'المحصل' : 'Collected' }}</span>
            </div>
        </div>

        <!-- Company Performance Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">{{ isRtl ? 'أداء شركات التأمين' : 'Insurance Company Performance' }}</h3>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <th class="px-4 py-3 text-start">#</th>
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'الشركة' : 'Company' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'المطالبات' : 'Claims' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'المطالب' : 'Claimed' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'المعتمد' : 'Approved' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'المحصل' : 'Collected' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'التحصيل %' : 'Collection %' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الرفض %' : 'Rejection %' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="(c, idx) in companyPerformance" :key="c.id" class="hover:bg-gray-50/50">
                        <td class="px-4 py-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" :class="idx === 0 ? 'bg-yellow-100 text-yellow-700' : idx === 1 ? 'bg-gray-100 text-gray-600' : idx === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-50 text-gray-400'">{{ idx + 1 }}</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ isRtl ? c.name_ar : c.name_en }}</td>
                        <td class="px-4 py-3 text-center">{{ fmtInt(c.total_claims) }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ fmt(c.total_claimed) }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ fmt(c.total_approved) }}</td>
                        <td class="px-4 py-3 text-center font-medium text-emerald-600">{{ fmt(c.total_paid) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="(c.collection_rate||0) >= 80 ? 'bg-emerald-100 text-emerald-700' : (c.collection_rate||0) >= 60 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'">
                                {{ c.collection_rate || 0 }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="(c.rejection_rate||0) <= 10 ? 'bg-emerald-100 text-emerald-700' : (c.rejection_rate||0) <= 20 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'">
                                {{ c.rejection_rate || 0 }}%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!companyPerformance?.length" class="text-center py-12 text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data available' }}</div>
        </div>
    </div>
</template>
