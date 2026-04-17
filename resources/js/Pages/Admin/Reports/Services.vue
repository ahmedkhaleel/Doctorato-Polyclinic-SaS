<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import BarChart from '@/Components/Admin/BarChart.vue';
import MiniBarChart from '@/Components/Admin/MiniBarChart.vue';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    serviceUsage: Array,
    serviceRevenue: Array,
    modules: Object,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');

const activeModules = computed(() => {
    if (!props.modules) return [];
    return Object.values(props.modules).filter(m => m.enabled);
});

function applyFilters() {
    router.get('/admin/reports/services', {
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

let dateTimeout = null;
watch([dateFrom, dateTo], () => {
    clearTimeout(dateTimeout);
    dateTimeout = setTimeout(applyFilters, 400);
});

watch(moduleFilter, () => applyFilters());

const { formatCurrency, currencyCode } = useCurrency();

/* Chart data */
const usageChartData = computed(() => {
    if (!props.serviceUsage?.length) return [];
    return props.serviceUsage.map(d => ({
        label: d.name_en,
        value: d.visits_count || 0,
    }));
});

const revenueChartData = computed(() => {
    if (!props.serviceRevenue?.length) return [];
    return props.serviceRevenue.map(d => ({
        label: d.name_en,
        value: d.total_revenue || 0,
    }));
});

/* Totals */
const totalUsage = computed(() =>
    (props.serviceRevenue || []).reduce((s, d) => s + (d.visit_count || 0), 0)
);
const totalRevenue = computed(() =>
    (props.serviceRevenue || []).reduce((s, d) => s + (d.total_revenue || 0), 0)
);
</script>

<template>
    <AdminLayout :title="$t('a_service_analytics')">
        <div class="space-y-8">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link href="/admin/reports" class="w-9 h-9 rounded-xl bg-white border border-gray-200 shadow-sm flex items-center justify-center text-gray-400 hover:text-[#C4A265] hover:border-[#C4A265]/30 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_service_analytics') }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $t('a_service_analytics_subtitle') }}</p>
                </div>
            </div>

            <!-- Module Tabs -->
            <div v-if="activeModules.length > 1" class="bg-white rounded-lg shadow-sm p-1.5 flex gap-1 flex-wrap">
                <button @click="moduleFilter = ''"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="moduleFilter === '' ? 'bg-gray-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                    {{ $t('a_all') }}
                </button>
                <button v-for="mod in activeModules" :key="mod.slug" @click="moduleFilter = mod.slug"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1.5"
                    :class="moduleFilter === mod.slug ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                    :style="moduleFilter === mod.slug ? { backgroundColor: mod.color } : {}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                    <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
                </button>
            </div>

            <!-- Date Range Filter -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 flex flex-wrap gap-3 items-center">
                <div class="w-9 h-9 rounded-xl bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <span class="text-sm font-medium text-gray-700">{{ $t('a_date_range') }}:</span>
                <input
                    v-model="dateFrom"
                    type="date"
                    :max="dateTo || undefined"
                    class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors"
                />
                <span class="text-sm text-gray-400">{{ $t('a_to') }}</span>
                <input
                    v-model="dateTo"
                    type="date"
                    :min="dateFrom || undefined"
                    class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors"
                />
            </div>

            <!-- Service Popularity (MiniBarChart) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <div class="mb-5">
                    <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_service_popularity') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_visits_per_service') }}</p>
                </div>
                <MiniBarChart :data="usageChartData" />
            </div>

            <!-- Revenue per Service (BarChart) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <div class="mb-5">
                    <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_revenue_per_service') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_revenue_per_service_desc') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <BarChart :data="revenueChartData" color="#C4A265" :valueSuffix="' ' + currencyCode" />
                </div>
            </div>

            <!-- Services Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-4 md:px-6 py-5 border-b border-gray-100">
                    <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_service_details') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_service_details_desc') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_service_name') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_usage_count') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="service in serviceRevenue" :key="service.id" class="hover:bg-gray-50/50 transition-colors duration-150">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ service.name_en }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-medium">{{ service.visit_count || 0 }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-[#C4A265]">{{ formatCurrency(service.total_revenue) }}</td>
                            </tr>
                            <tr v-if="!serviceRevenue?.length">
                                <td colspan="3" class="px-4 md:px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_service_data') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="serviceRevenue?.length" class="bg-gray-50/80 border-t border-gray-200">
                            <tr class="font-semibold">
                                <td class="px-4 md:px-6 py-3.5 text-sm text-gray-700">{{ $t('a_totals') }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-right text-gray-700">{{ totalUsage }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-right text-[#C4A265] font-bold">{{ formatCurrency(totalRevenue) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
