<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import DonutChart from '@/Components/Admin/DonutChart.vue';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    newPatients: Number,
    totalPatients: Number,
    patientsByReferral: Array,
    genderDistribution: Array,
    topPatients: Array,
    topSpenders: Array,
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
    router.get('/admin/reports/patients', {
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

const { formatCurrency } = useCurrency();

/* Chart data */
const referralChartData = computed(() => {
    if (!props.patientsByReferral?.length) return [];
    return props.patientsByReferral.map(d => ({
        label: d.referral_source || 'Unknown',
        value: d.count,
    }));
});

const genderChartData = computed(() => {
    if (!props.genderDistribution?.length) return [];
    return props.genderDistribution.map(d => ({
        label: d.gender || 'Unknown',
        value: d.count,
    }));
});
</script>

<template>
    <AdminLayout :title="$t('a_patient_analytics')">
        <div class="space-y-8">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link href="/admin/reports" class="w-9 h-9 rounded-xl bg-white border border-gray-200 shadow-sm flex items-center justify-center text-gray-400 hover:text-[#C4A265] hover:border-[#C4A265]/30 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_patient_analytics') }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $t('a_patient_analytics_subtitle') }}</p>
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

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- New Patients -->
                <div class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#C4A265] to-[#D4B87A] opacity-80"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[13px] font-medium text-gray-500">{{ $t('a_new_patients') }}</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ newPatients || 0 }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $t('a_in_selected_period') }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-[#C4A265]/10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Total Patients -->
                <div class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-blue-600 opacity-80"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[13px] font-medium text-gray-500">{{ $t('a_total_patients') }}</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ totalPatients || 0 }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $t('a_all_time_registered') }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Donut Charts: Referral Sources & Gender Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Referral Sources -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="mb-5">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_referral_sources') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_referral_sources_desc') }}</p>
                    </div>
                    <DonutChart :data="referralChartData" />
                </div>

                <!-- Gender Distribution -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="mb-5">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_gender_distribution') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_gender_distribution_desc') }}</p>
                    </div>
                    <DonutChart :data="genderChartData" />
                </div>
            </div>

            <!-- Top Patients by Visits & Top Spenders -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Top Patients by Visits -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_top_patients_visits') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_most_frequent_visitors') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_file_no') }}</th>
                                    <th class="px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_visits') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(patient, index) in topPatients" :key="patient.id" class="hover:bg-gray-50/50 transition-colors duration-150">
                                    <td class="px-6 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-bold"
                                              :class="index < 3 ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'bg-gray-100 text-gray-500'">
                                            {{ index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap">
                                        <Link :href="`/admin/patients/${patient.id}`" class="text-sm font-semibold text-gray-900 hover:text-[#C4A265] transition-colors duration-200">
                                            {{ patient.full_name }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-sm text-gray-500 font-mono">{{ patient.file_number || '-' }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-sm text-right font-bold text-gray-900">{{ patient.visits_count || 0 }}</td>
                                </tr>
                                <tr v-if="!topPatients?.length">
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <p class="text-sm text-gray-400">{{ $t('a_no_visit_data') }}</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Spenders -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="text-[15px] font-semibold text-gray-900">{{ $t('a_top_spenders') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_highest_value_patients') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                    <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_file_no') }}</th>
                                    <th class="px-6 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_total_spent') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(patient, index) in topSpenders" :key="patient.id" class="hover:bg-gray-50/50 transition-colors duration-150">
                                    <td class="px-6 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-bold"
                                              :class="index < 3 ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'bg-gray-100 text-gray-500'">
                                            {{ index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap">
                                        <Link :href="`/admin/patients/${patient.id}`" class="text-sm font-semibold text-gray-900 hover:text-[#C4A265] transition-colors duration-200">
                                            {{ patient.full_name }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-sm text-gray-500 font-mono">{{ patient.file_number || '-' }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap text-sm text-right font-bold text-[#C4A265]">{{ formatCurrency(patient.total_spent) }}</td>
                                </tr>
                                <tr v-if="!topSpenders?.length">
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <p class="text-sm text-gray-400">{{ $t('a_no_spending_data') }}</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </AdminLayout>
</template>
