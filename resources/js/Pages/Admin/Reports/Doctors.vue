<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import BarChart from '@/Components/Admin/BarChart.vue';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    doctors: Array,
    doctorRevenue: Object,
    doctorCommission: Object,
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
    router.get('/admin/reports/doctors', {
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
const revenueChartData = computed(() => {
    if (!props.doctors?.length) return [];
    return props.doctors.map(d => ({
        label: d.name_en,
        value: props.doctorRevenue?.[d.id] || 0,
    }));
});

/* Totals */
const totals = computed(() => {
    if (!props.doctors?.length) return { visits: 0, completed: 0, consultations: 0, sessions: 0, revenue: 0, commission: 0 };
    return {
        visits: props.doctors.reduce((s, d) => s + (d.visits_count || 0), 0),
        completed: props.doctors.reduce((s, d) => s + (d.completed_visits_count || 0), 0),
        consultations: props.doctors.reduce((s, d) => s + (d.consultation_count || 0), 0),
        sessions: props.doctors.reduce((s, d) => s + (d.session_count || 0), 0),
        revenue: props.doctors.reduce((s, d) => s + (props.doctorRevenue?.[d.id] || 0), 0),
        commission: props.doctors.reduce((s, d) => s + (props.doctorCommission?.[d.id] || 0), 0),
    };
});
</script>

<template>
    <AdminLayout :title="$t('a_doctor_performance')">
        <div class="space-y-8">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link href="/admin/reports" class="w-9 h-9 rounded-xl bg-white border border-gray-200 shadow-sm flex items-center justify-center text-gray-400 hover:text-[#C4A265] hover:border-[#C4A265]/30 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_doctor_performance') }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $t('a_doctor_performance_subtitle') }}</p>
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

            <!-- Revenue per Doctor Bar Chart -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <div class="mb-5">
                    <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_revenue_per_doctor') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_revenue_per_doctor_desc') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <BarChart :data="revenueChartData" color="#C4A265" :valueSuffix="' ' + currencyCode" />
                </div>
            </div>

            <!-- Doctors Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="px-4 md:px-6 py-5 border-b border-gray-100">
                    <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_doctor_details') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_doctor_details_desc') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_doctor') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_total_visits') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_completed') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_consultations') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_sessions') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_revenue') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_commission') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="doctor in doctors" :key="doctor.id" class="hover:bg-gray-50/50 transition-colors duration-150">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center text-white text-sm font-bold shadow-sm flex-shrink-0">
                                            {{ doctor.name_en?.charAt(0)?.toUpperCase() || '?' }}
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900">{{ doctor.name_en }}</span>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-medium">{{ doctor.visits_count || 0 }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-right text-emerald-600 font-medium">{{ doctor.completed_visits_count || 0 }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ doctor.consultation_count || 0 }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ doctor.session_count || 0 }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-[#C4A265]">{{ formatCurrency(doctorRevenue?.[doctor.id]) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-[#1B365D]">{{ formatCurrency(doctorCommission?.[doctor.id]) }}</td>
                            </tr>
                            <tr v-if="!doctors?.length">
                                <td colspan="7" class="px-4 md:px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-3">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_doctor_data') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="doctors?.length" class="bg-gray-50/80 border-t border-gray-200">
                            <tr class="font-semibold">
                                <td class="px-4 md:px-6 py-3.5 text-sm text-gray-700">{{ $t('a_totals') }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-right text-gray-700">{{ totals.visits }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-right text-gray-700">{{ totals.completed }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-right text-gray-700">{{ totals.consultations }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-right text-gray-700">{{ totals.sessions }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-right text-[#C4A265] font-bold">{{ formatCurrency(totals.revenue) }}</td>
                                <td class="px-4 md:px-6 py-3.5 text-sm text-right text-[#1B365D] font-bold">{{ formatCurrency(totals.commission) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
