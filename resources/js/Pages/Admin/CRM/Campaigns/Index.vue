<script setup>
import { ref, watch, onMounted , computed } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const props = defineProps({
    campaigns: Object,
    stats: Object,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

const mounted = ref(false);
const showStats = ref(false);
const showFilters = ref(false);
const showTable = ref(false);

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    setTimeout(() => { showStats.value = true; }, 100);
    setTimeout(() => { showFilters.value = true; }, 200);
    setTimeout(() => { showTable.value = true; }, 300);
});

let searchTimeout = null;
function applyFilters() {
    router.get('/admin/campaigns', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(applyFilters, 400); });
watch(statusFilter, applyFilters);

const statusColors = {
    draft: 'bg-gray-100 text-gray-600 border-gray-200',
    active: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    paused: 'bg-amber-50 text-amber-700 border-amber-200',
    completed: 'bg-blue-50 text-blue-700 border-blue-200',
};

const statusDotColors = {
    draft: 'bg-gray-400',
    active: 'bg-emerald-500',
    paused: 'bg-amber-500',
    completed: 'bg-blue-500',
};

/* Searchable status filter */
const showStatusDropdown = ref(false);
const statusSearch = ref('');
const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Active' },
    { value: 'paused', label: 'Paused' },
    { value: 'completed', label: 'Completed' },
];

function getStatusLabel(val) {
    const opt = statusOptions.find(o => o.value === val);
    return opt ? opt.label : 'All Statuses';
}

function selectStatus(val) {
    statusFilter.value = val;
    statusSearch.value = '';
    showStatusDropdown.value = false;
}

function onStatusFocus() {
    showStatusDropdown.value = true;
    statusSearch.value = '';
}

function onStatusBlur() {
    setTimeout(() => { showStatusDropdown.value = false; }, 200);
}

function filteredStatusOptions() {
    if (!statusSearch.value) return statusOptions;
    const q = statusSearch.value.toLowerCase();
    return statusOptions.filter(o => o.label.toLowerCase().includes(q));
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function deleteCampaign(id) {
    if (confirm('Are you sure you want to delete this campaign?')) {
        router.post(`/admin/campaigns/${id}/delete`);
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_campaigns')">
        <div class="space-y-6">
            <!-- Header -->
            <div
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 transition-all duration-500 ease-out"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $t('a_campaigns') }}</h1>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $t('a_campaigns_description') }}</p>
                        </div>
                    </div>
                </div>
                <Link
                    v-if="can('crm_campaigns.create')"
                    href="/admin/campaigns/create"
                    class="group inline-flex items-center px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:-translate-y-0.5"
                    style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                >
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2 transition-transform duration-200 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_new_campaign') }}</Link>
            </div>

            <!-- Stats Cards -->
            <div
                :class="showStats ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="grid grid-cols-2 md:grid-cols-5 gap-4 transition-all duration-500 ease-out"
            >
                <!-- Total -->
                <div class="relative bg-white rounded-2xl shadow-sm p-5 border border-gray-100 text-center overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-gray-300 to-gray-400 rounded-t-2xl"></div>
                    <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-gray-50 mb-3 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-1 font-semibold">{{ $t('a_total') }}</p>
                </div>
                <!-- Active -->
                <div class="relative bg-white rounded-2xl shadow-sm p-5 border border-gray-100 text-center overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-500 rounded-t-2xl"></div>
                    <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-emerald-50 mb-3 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.active }}</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-1 font-semibold">{{ $t('a_active') }}</p>
                </div>
                <!-- Total Budget -->
                <div class="relative bg-white rounded-2xl shadow-sm p-5 border border-gray-100 text-center overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 rounded-t-2xl" style="background: linear-gradient(to right, #C4A265, #D4B87A);"></div>
                    <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl mb-3 group-hover:scale-105 transition-transform duration-300" style="background-color: rgba(196, 162, 101, 0.1);">
                        <svg class="w-5 h-5" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-800">{{ formatCurrency(stats.total_budget) }}</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-1 font-semibold">{{ $t('a_total_budget') }}</p>
                </div>
                <!-- Total Leads -->
                <div class="relative bg-white rounded-2xl shadow-sm p-5 border border-gray-100 text-center overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-blue-400 to-blue-500 rounded-t-2xl"></div>
                    <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-blue-50 mb-3 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-600">{{ stats.total_leads }}</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-1 font-semibold">{{ $t('a_total_leads') }}</p>
                </div>
                <!-- Conversions -->
                <div class="relative bg-white rounded-2xl shadow-sm p-5 border border-gray-100 text-center overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-green-400 to-green-500 rounded-t-2xl"></div>
                    <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-green-50 mb-3 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-2xl font-bold text-green-600">{{ stats.total_conversions }}</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-1 font-semibold">{{ $t('a_conversions') }}</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div
                :class="showFilters ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500 ease-out"
            >
                <!-- Gold gradient top bar -->
                <div class="h-1 w-full" style="background: linear-gradient(135deg, #C4A265, #D4B87A);"></div>
                <div class="p-4">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="$t('a_search_campaigns')"
                                class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-400"
                            />
                        </div>
                        <!-- Searchable Status Filter -->
                        <div class="relative min-w-[200px]">
                            <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3 rtl:pr-3 flex items-center pointer-events-none z-10">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            </div>
                            <input
                                :value="showStatusDropdown ? statusSearch : getStatusLabel(statusFilter)"
                                @input="statusSearch = $event.target.value"
                                @focus="onStatusFocus"
                                @blur="onStatusBlur"
                                type="text"
                                :placeholder="$t('a_filter_by_status')"
                                class="w-full ltr:pl-9 rtl:pr-9 ltr:pr-8 rtl:pl-8 py-2.5 text-sm bg-gray-50/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 cursor-pointer"
                            />
                            <div class="absolute inset-y-0 ltr:right-0 rtl:left-0 ltr:pr-2.5 rtl:pl-2.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            <!-- Dropdown -->
                            <div
                                v-if="showStatusDropdown"
                                class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
                            >
                                <button
                                    v-for="opt in filteredStatusOptions()"
                                    :key="opt.value"
                                    type="button"
                                    @click="selectStatus(opt.value)"
                                    :class="statusFilter === opt.value ? 'bg-[#C4A265]/5 text-[#C4A265]' : 'text-gray-700 hover:bg-gray-50'"
                                    class="w-full ltr:text-left rtl:text-right px-4 py-2.5 text-sm transition-colors flex items-center justify-between"
                                >
                                    <span class="flex items-center gap-2">
                                        <span v-if="opt.value" :class="statusDotColors[opt.value]" class="w-2 h-2 rounded-full"></span>
                                        {{ opt.label }}
                                    </span>
                                    <svg v-if="statusFilter === opt.value" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div
                :class="showTable ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500 ease-out"
            >
                <!-- Gold gradient top bar -->
                <div class="h-1 w-full" style="background: linear-gradient(135deg, #C4A265, #D4B87A);"></div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[11px] text-gray-500 uppercase tracking-wider border-b border-gray-100 bg-gray-50/70">
                                <th class="px-6 py-4 font-semibold">{{ $t('a_campaign') }}</th>
                                <th class="px-5 py-4 font-semibold text-center">{{ $t('a_status') }}</th>
                                <th class="px-5 py-4 font-semibold ltr:text-right rtl:text-left">{{ $t('a_budget') }}</th>
                                <th class="px-5 py-4 font-semibold text-center">{{ $t('a_leads') }}</th>
                                <th class="px-5 py-4 font-semibold text-center">{{ $t('a_conversions') }}</th>
                                <th class="px-5 py-4 font-semibold">{{ $t('a_period') }}</th>
                                <th class="px-5 py-4 font-semibold text-center">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="(c, index) in campaigns.data"
                                :key="c.id"
                                :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/30'"
                                class="hover:bg-[#C4A265]/[0.03] transition-colors duration-150 group"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform duration-200" style="background-color: rgba(196, 162, 101, 0.08);">
                                            <svg class="w-4 h-4" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                                        </div>
                                        <div>
                                            <Link :href="`/admin/campaigns/${c.id}`" class="font-semibold text-gray-900 hover:text-[#C4A265] transition-colors duration-150">{{ c.name }}</Link>
                                            <div v-if="c.lead_source" class="mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-md bg-gray-100 text-gray-500">
                                                    <svg class="w-3 h-3 ltr:mr-1 rtl:ml-1 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                                    {{ c.lead_source.name_en }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="statusColors[c.status]" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full capitalize border">
                                        <span :class="statusDotColors[c.status]" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 ltr:text-right rtl:text-left font-semibold text-gray-700 tabular-nums">{{ formatCurrency(c.budget) }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 text-xs font-bold rounded-full bg-blue-50 text-blue-700">{{ c.leads_count }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 text-xs font-bold rounded-full bg-green-50 text-green-700">{{ c.conversions_count }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <span>{{ formatDate(c.start_date) }} - {{ formatDate(c.end_date) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- View -->
                                        <Link
                                            :href="`/admin/campaigns/${c.id}`"
                                            class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all duration-150"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <span class="absolute -top-8 ltr:left-1 rtl:right-1/2 -translate-x-1/2 px-2 py-1 text-[10px] font-medium text-white bg-gray-800 rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity duration-150 whitespace-nowrap pointer-events-none">{{ $t('a_view') }}</span>
                                        </Link>
                                        <!-- Edit -->
                                        <Link
                                            :href="`/admin/campaigns/${c.id}/edit`"
                                            class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/10 transition-all duration-150"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            <span class="absolute -top-8 ltr:left-1 rtl:right-1/2 -translate-x-1/2 px-2 py-1 text-[10px] font-medium text-white bg-gray-800 rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity duration-150 whitespace-nowrap pointer-events-none">{{ $t('a_edit') }}</span>
                                        </Link>
                                        <!-- Delete -->
                                        <button
                                            v-if="can('crm_campaigns.delete')"
                                            @click="deleteCampaign(c.id)"
                                            class="group/btn relative inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-150"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            <span class="absolute -top-8 ltr:left-1 rtl:right-1/2 -translate-x-1/2 px-2 py-1 text-[10px] font-medium text-white bg-gray-800 rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity duration-150 whitespace-nowrap pointer-events-none">{{ $t('a_delete') }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!campaigns.data?.length" class="px-6 py-20 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">{{ $t('a_no_campaigns_found') }}</h3>
                    <p class="text-sm text-gray-400 mb-5">{{ $t('a_no_campaigns_description') }}</p>
                    <Link
                        v-if="can('crm_campaigns.create')"
                        href="/admin/campaigns/create"
                        class="inline-flex items-center px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                    >
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_create_campaign') }}</Link>
                </div>

                <!-- Pagination -->
                <div v-if="campaigns.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">
                        {{ $t('a_showing') }} <span class="font-semibold text-gray-700">{{ campaigns.from }}</span> {{ $t('a_to') }} <span class="font-semibold text-gray-700">{{ campaigns.to }}</span> {{ $t('a_of') }} <span class="font-semibold text-gray-700">{{ campaigns.total }}</span> {{ $t('a_results') }}
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in campaigns.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                link.active
                                    ? 'text-white shadow-sm'
                                    : 'text-gray-600 hover:bg-gray-100 bg-white',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            :style="link.active ? 'background: linear-gradient(135deg, #C4A265, #D4B87A);' : ''"
                            class="px-3 py-1.5 text-xs font-medium rounded-full border border-gray-200 transition-all duration-150 min-w-[32px] text-center hover:-translate-y-0.5"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
