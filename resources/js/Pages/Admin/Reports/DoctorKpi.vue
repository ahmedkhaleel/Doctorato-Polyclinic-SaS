<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    doctors: Array,
    totals: Object,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const sortBy = ref('revenue');

let filterTimeout = null;
watch([dateFrom, dateTo], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get('/admin/reports/doctor-kpi', {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
});

const sortedDoctors = computed(() => {
    const docs = [...props.doctors];
    switch (sortBy.value) {
        case 'revenue': return docs.sort((a, b) => b.revenue.current - a.revenue.current);
        case 'visits': return docs.sort((a, b) => b.visits.current - a.visits.current);
        case 'rating': return docs.sort((a, b) => b.rating - a.rating);
        case 'retention': return docs.sort((a, b) => b.retention_rate - a.retention_rate);
        case 'efficiency': return docs.sort((a, b) => (a.avg_service_minutes || 999) - (b.avg_service_minutes || 999));
        default: return docs;
    }
});

function pctChange(cur, prev) {
    if (!prev) return cur > 0 ? 100 : 0;
    return Math.round(((cur - prev) / prev) * 100);
}

function stars(rating) {
    return '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'أداء الأطباء' : 'Doctor KPIs'">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'مؤشرات أداء الأطباء' : 'Doctor Performance KPIs' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'مقارنة شاملة للإنتاجية والجودة والإيرادات' : 'Comprehensive productivity, quality, and revenue comparison' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="dateFrom" type="date" class="rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                    <span class="text-gray-400">→</span>
                    <input v-model="dateTo" type="date" class="rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-5 text-white">
                    <div class="text-sm opacity-80">{{ isRtl ? 'إجمالي الإيرادات' : 'Total Revenue' }}</div>
                    <div class="text-2xl font-bold mt-1">{{ formatCurrency(totals.revenue) }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="text-xs text-gray-500">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ totals.visits }}</div>
                </div>
                <div class="bg-white rounded-xl border border-amber-200 p-5">
                    <div class="text-xs text-gray-500">{{ isRtl ? 'متوسط التقييم' : 'Avg Rating' }}</div>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ totals.avg_rating || '—' }} <span class="text-sm">/ 5</span></div>
                </div>
                <div class="bg-white rounded-xl border border-emerald-200 p-5">
                    <div class="text-xs text-gray-500">{{ isRtl ? 'متوسط الاحتفاظ' : 'Avg Retention' }}</div>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ totals.avg_retention }}%</div>
                </div>
            </div>

            <!-- Sort Options -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm text-gray-500">{{ isRtl ? 'ترتيب حسب:' : 'Sort by:' }}</span>
                <button v-for="opt in [
                    { key: 'revenue', en: 'Revenue', ar: 'الإيراد' },
                    { key: 'visits', en: 'Visits', ar: 'الزيارات' },
                    { key: 'rating', en: 'Rating', ar: 'التقييم' },
                    { key: 'retention', en: 'Retention', ar: 'الاحتفاظ' },
                    { key: 'efficiency', en: 'Efficiency', ar: 'الكفاءة' },
                ]" :key="opt.key"
                    @click="sortBy = opt.key"
                    class="px-3 py-1.5 text-xs font-medium rounded-full border transition"
                    :class="sortBy === opt.key ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                    {{ isRtl ? opt.ar : opt.en }}
                </button>
            </div>

            <!-- Doctor KPI Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div v-for="(doc, i) in sortedDoctors" :key="doc.id"
                    class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
                    <!-- Doctor Header -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white text-lg font-bold">
                                {{ (isRtl ? doc.name_ar : doc.name_en)?.charAt(0) || 'D' }}
                            </div>
                            <span v-if="i < 3" class="absolute -top-1 -right-1 w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center"
                                :class="i === 0 ? 'bg-amber-400 text-white' : i === 1 ? 'bg-gray-300 text-gray-700' : 'bg-orange-300 text-white'">
                                {{ i + 1 }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold text-gray-900 truncate">{{ isRtl ? doc.name_ar : doc.name_en }}</h4>
                            <p class="text-xs text-gray-500">{{ isRtl ? doc.specialty_ar : doc.specialty_en }}</p>
                        </div>
                        <div v-if="doc.rating > 0" class="text-end">
                            <div class="text-amber-400 text-xs tracking-wider">{{ stars(doc.rating) }}</div>
                            <div class="text-[10px] text-gray-400">{{ doc.rating }}/5 ({{ doc.rating_count }})</div>
                        </div>
                    </div>

                    <!-- KPI Grid -->
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <!-- Revenue -->
                        <div class="text-center p-2 rounded-lg bg-gray-50">
                            <div class="text-xs text-gray-500 mb-1">{{ isRtl ? 'الإيراد' : 'Revenue' }}</div>
                            <div class="text-sm font-bold text-gray-900">{{ formatCurrency(doc.revenue.current) }}</div>
                            <div class="text-[10px] mt-0.5"
                                :class="pctChange(doc.revenue.current, doc.revenue.previous) >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                {{ pctChange(doc.revenue.current, doc.revenue.previous) >= 0 ? '↑' : '↓' }}
                                {{ Math.abs(pctChange(doc.revenue.current, doc.revenue.previous)) }}%
                            </div>
                        </div>
                        <!-- Visits -->
                        <div class="text-center p-2 rounded-lg bg-gray-50">
                            <div class="text-xs text-gray-500 mb-1">{{ isRtl ? 'الزيارات' : 'Visits' }}</div>
                            <div class="text-sm font-bold text-gray-900">{{ doc.visits.current }}</div>
                            <div class="text-[10px] mt-0.5"
                                :class="pctChange(doc.visits.current, doc.visits.previous) >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                {{ pctChange(doc.visits.current, doc.visits.previous) >= 0 ? '↑' : '↓' }}
                                {{ Math.abs(pctChange(doc.visits.current, doc.visits.previous)) }}%
                            </div>
                        </div>
                        <!-- Avg / Visit -->
                        <div class="text-center p-2 rounded-lg bg-gray-50">
                            <div class="text-xs text-gray-500 mb-1">{{ isRtl ? 'متوسط/زيارة' : 'Avg/Visit' }}</div>
                            <div class="text-sm font-bold text-cyan-600">{{ formatCurrency(doc.avg_revenue_per_visit) }}</div>
                        </div>
                    </div>

                    <!-- Progress Bars -->
                    <div class="space-y-2">
                        <!-- Retention -->
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-gray-500 w-20 flex-shrink-0">{{ isRtl ? 'الاحتفاظ' : 'Retention' }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all" :style="{ width: `${doc.retention_rate}%` }"></div>
                            </div>
                            <span class="text-[11px] font-bold text-gray-700 w-10 text-end">{{ doc.retention_rate }}%</span>
                        </div>
                        <!-- Cancellation -->
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-gray-500 w-20 flex-shrink-0">{{ isRtl ? 'الإلغاء' : 'Cancel' }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                    :class="doc.cancellation_rate > 15 ? 'bg-red-500' : doc.cancellation_rate > 5 ? 'bg-amber-500' : 'bg-emerald-500'"
                                    :style="{ width: `${Math.min(doc.cancellation_rate, 100)}%` }"></div>
                            </div>
                            <span class="text-[11px] font-bold text-gray-700 w-10 text-end">{{ doc.cancellation_rate }}%</span>
                        </div>
                    </div>

                    <!-- Footer Stats -->
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 text-[11px] text-gray-500">
                        <span>{{ isRtl ? 'مرضى:' : 'Patients:' }} <b class="text-gray-700">{{ doc.unique_patients }}</b></span>
                        <span>{{ isRtl ? 'خدمة:' : 'Service:' }} <b class="text-gray-700">{{ doc.avg_service_minutes }}{{ isRtl ? 'د' : 'min' }}</b></span>
                        <span>{{ isRtl ? 'انتظار:' : 'Wait:' }} <b :class="doc.avg_wait_minutes > 30 ? 'text-red-600' : 'text-gray-700'">{{ doc.avg_wait_minutes }}{{ isRtl ? 'د' : 'min' }}</b></span>
                        <span>{{ isRtl ? 'عمولة:' : 'Commission:' }} <b class="text-gray-700">{{ formatCurrency(doc.commission) }}</b></span>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="!doctors?.length" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                <h3 class="text-lg font-medium text-gray-900">{{ isRtl ? 'لا يوجد أطباء نشطين' : 'No active doctors' }}</h3>
            </div>
        </div>
    </AdminLayout>
</template>
