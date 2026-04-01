<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const pg = usePage();
const locale = computed(() => pg.props.locale || 'ar');
const isRtl = computed(() => (pg.props.dir || 'rtl') === 'rtl');

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payouts: Object,
    summary: Object,
    doctors: Array,
    topDoctors: Array,
    recentActivity: Array,
    filters: Object,
});

const modules = computed(() => pg.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const form = ref({
    doctor_id: props.filters?.doctor_id || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    module: props.filters?.module || '',
});

const showFilters = ref(!!(props.filters?.doctor_id || props.filters?.status || props.filters?.date_from || props.filters?.date_to || props.filters?.module));

const hasActiveFilters = computed(() => {
    return !!(form.value.doctor_id || form.value.status || form.value.date_from || form.value.date_to || form.value.module);
});

function applyFilters() {
    const params = {};
    if (form.value.doctor_id) params.doctor_id = form.value.doctor_id;
    if (form.value.status) params.status = form.value.status;
    if (form.value.date_from) params.date_from = form.value.date_from;
    if (form.value.date_to) params.date_to = form.value.date_to;
    if (form.value.module) params.module = form.value.module;
    router.get('/admin/doctor-payouts', params, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    form.value = { doctor_id: '', status: '', date_from: '', date_to: '', module: '' };
    router.get('/admin/doctor-payouts', {}, { preserveState: true });
}

function formatDate(date) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusConfig = {
    draft: { label: 'Draft', bg: 'bg-gray-100', text: 'text-gray-600', dot: 'bg-gray-400', icon: 'draft' },
    confirmed: { label: 'Confirmed', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-400', icon: 'confirmed' },
    paid: { label: 'Paid', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', icon: 'paid' },
    cancelled: { label: 'Cancelled', bg: 'bg-red-50', text: 'text-red-600', dot: 'bg-red-400', icon: 'cancelled' },
};

const paymentMethodLabels = {
    cash: 'Cash',
    bank_transfer: 'Bank Transfer',
    check: 'Check',
    mobile_wallet: 'Mobile Wallet',
};

// Status distribution for mini chart
const statusDistribution = computed(() => {
    const total = (props.summary?.count_total || 0);
    if (!total) return [];
    return [
        { key: 'paid', count: props.summary?.count_paid || 0, pct: Math.round(((props.summary?.count_paid || 0) / total) * 100), color: 'bg-emerald-500' },
        { key: 'confirmed', count: props.summary?.count_confirmed || 0, pct: Math.round(((props.summary?.count_confirmed || 0) / total) * 100), color: 'bg-amber-400' },
        { key: 'draft', count: props.summary?.count_draft || 0, pct: Math.round(((props.summary?.count_draft || 0) / total) * 100), color: 'bg-gray-300' },
        { key: 'cancelled', count: props.summary?.count_cancelled || 0, pct: Math.round(((props.summary?.count_cancelled || 0) / total) * 100), color: 'bg-red-400' },
    ].filter(s => s.count > 0);
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-start justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    {{ $t('a_doctor_payouts') }}
                    <span v-if="summary.count_confirmed > 0" class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 border border-amber-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        {{ summary.count_confirmed }} {{ $t('a_awaiting_payment') }}
                    </span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">{{ $t('a_doctor_payouts_description') }}</p>
            </div>
            <Link href="/admin/doctor-payouts/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-[#C4A265] to-[#B3914F] text-white text-sm font-semibold rounded-xl hover:from-[#B3914F] hover:to-[#A28145] transition-all shadow-sm hover:shadow-md active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ $t('a_new_payout') }}
            </Link>
        </div>

        <!-- Summary Cards Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Draft -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-gray-300 to-gray-400"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_draft') }}</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1.5 tabular-nums">{{ formatCurrency(summary.total_draft) }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ currencyCode }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ summary.count_draft || 0 }}</span>
                    <span class="text-[10px] text-gray-400">{{ $t('a_payouts') }}</span>
                </div>
            </div>

            <!-- Pending Payment -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-amber-400 to-amber-500"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_pending_payment') }}</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1.5 tabular-nums">{{ formatCurrency(summary.total_confirmed) }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ currencyCode }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-600">{{ summary.count_confirmed || 0 }}</span>
                    <span class="text-[10px] text-gray-400">{{ $t('a_payouts') }}</span>
                </div>
            </div>

            <!-- Paid This Month -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-emerald-400 to-emerald-500"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_paid_this_month') }}</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1.5 tabular-nums">{{ formatCurrency(summary.total_paid_this_month) }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ currencyCode }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-600">{{ summary.count_paid || 0 }}</span>
                    <span class="text-[10px] text-gray-400">{{ $t('a_total_paid') }}</span>
                </div>
            </div>

            <!-- Total Outstanding -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80 relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-[#C4A265] to-[#D4B87A]"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_outstanding') }}</p>
                        <p class="text-2xl font-bold text-[#C4A265] mt-1.5 tabular-nums">{{ formatCurrency(summary.total_outstanding) }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ currencyCode }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-[#C4A265]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-1.5">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-[#C4A265]/10 text-[#C4A265]">{{ (summary.count_draft || 0) + (summary.count_confirmed || 0) }}</span>
                    <span class="text-[10px] text-gray-400">{{ $t('a_payouts') }}</span>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid lg:grid-cols-4 gap-6">
            <!-- Left: Table + Filters (3 cols) -->
            <div class="lg:col-span-3 space-y-4">

                <!-- Filters Bar -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <!-- Filter Toggle Header -->
                    <div class="px-5 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors" @click="showFilters = !showFilters">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">{{ $t('a_filters') }}</span>
                            <span v-if="hasActiveFilters" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#C4A265]/10 text-[#C4A265]">{{ $t('a_active') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>

                    <!-- Filter Fields (collapsible) -->
                    <transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="max-h-0 opacity-0" enter-to-class="max-h-40 opacity-100" leave-active-class="transition-all duration-150 ease-in" leave-from-class="max-h-40 opacity-100" leave-to-class="max-h-0 opacity-0">
                        <div v-show="showFilters" class="px-5 pb-4 border-t border-gray-100 overflow-hidden">
                            <div class="flex flex-wrap items-end gap-3 pt-4">
                                <div v-if="activeModules.length > 1" class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1 block">{{ $t('a_department') }}</label>
                                    <select v-model="form.module" @change="applyFilters" class="w-full py-2 rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                        <option value="">{{ $t('a_all_departments') }}</option>
                                        <option v-for="m in activeModules" :key="m.slug" :value="m.slug">{{ m.name }}</option>
                                    </select>
                                </div>
                                <div class="flex-1 min-w-[160px]">
                                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1 block">{{ $t('a_doctor') }}</label>
                                    <div class="relative">
                                        <select v-model="form.doctor_id" @change="applyFilters" class="w-full pl-9 pr-3 py-2 rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] appearance-none bg-white">
                                            <option value="">{{ $t('a_all_doctors') }}</option>
                                            <option v-for="d in doctors" :key="d.id" :value="d.id">Dr. {{ d.name_en }}</option>
                                        </select>
                                        <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1 block">{{ $t('a_status') }}</label>
                                    <select v-model="form.status" @change="applyFilters" class="w-full py-2 rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                        <option value="">{{ $t('a_all_statuses') }}</option>
                                        <option value="draft">{{ $t('a_draft') }}</option>
                                        <option value="confirmed">{{ $t('a_confirmed') }}</option>
                                        <option value="paid">{{ $t('a_paid') }}</option>
                                        <option value="cancelled">{{ $t('a_cancelled') }}</option>
                                    </select>
                                </div>
                                <div class="flex-1 min-w-[130px]">
                                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1 block">{{ $t('a_from') }}</label>
                                    <div class="relative">
                                        <input type="date" v-model="form.date_from" @change="applyFilters" :max="form.date_to || undefined" class="w-full pl-9 pr-3 py-2 rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                        <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-[130px]">
                                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1 block">{{ $t('a_to') }}</label>
                                    <div class="relative">
                                        <input type="date" v-model="form.date_to" @change="applyFilters" :min="form.date_from || undefined" class="w-full pl-9 pr-3 py-2 rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                        <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        </div>
                                    </div>
                                </div>
                                <button v-if="hasActiveFilters" @click="clearFilters" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ $t('a_clear') }}
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Payouts Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <!-- Table Header -->
                    <div class="px-6 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-gray-700">{{ $t('a_all_payouts') }}</h2>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ payouts.total || 0 }}</span>
                        </div>
                        <p v-if="payouts.total" class="text-[11px] text-gray-400">
                            {{ $t('a_showing') }} {{ payouts.from }}–{{ payouts.to }} {{ $t('a_of') }} {{ payouts.total }}
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-start px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_payout') }}</th>
                                    <th class="text-start px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_doctor') }}</th>
                                    <th class="text-start px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_period') }}</th>
                                    <th class="text-center px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_visits') }}</th>
                                    <th class="text-end px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_commission') }}</th>
                                    <th class="text-end px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_net_amount') }}</th>
                                    <th class="text-center px-4 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                    <th class="text-center px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="p in payouts.data" :key="p.id"
                                    class="group hover:bg-gray-50/60 transition-colors cursor-pointer"
                                    @click="router.visit(`/admin/doctor-payouts/${p.id}`)">
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs" :class="[statusConfig[p.status]?.bg]">
                                                <span class="w-2 h-2 rounded-full" :class="[statusConfig[p.status]?.dot]"></span>
                                            </div>
                                            <div>
                                                <span class="font-mono text-xs font-bold text-[#C4A265]">{{ p.payout_number }}</span>
                                                <p class="text-[10px] text-gray-400 mt-0.5">{{ formatDate(p.created_at) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#C4A265]/20 to-[#C4A265]/5 flex items-center justify-center text-[10px] font-bold text-[#C4A265]">
                                                {{ p.doctor?.name_en?.charAt(0) }}
                                            </div>
                                            <span class="text-xs font-semibold text-gray-800">Dr. {{ p.doctor?.name_en }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3 h-3 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <span class="text-[11px] text-gray-500">{{ formatDate(p.period_start) }} — {{ formatDate(p.period_end) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-50 text-xs font-bold text-gray-700">{{ p.total_visits }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="text-xs font-medium text-gray-600 tabular-nums">{{ formatCurrency(p.total_commission) }}</span>
                                        <span class="text-[10px] text-gray-400 ml-0.5">{{ currencyCode }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="text-sm font-bold tabular-nums" :class="p.status === 'paid' ? 'text-emerald-600' : p.status === 'cancelled' ? 'text-gray-400 line-through' : 'text-gray-900'">
                                            {{ formatCurrency(p.net_amount) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 ml-0.5">{{ currencyCode }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2.5 py-1 rounded-full" :class="[statusConfig[p.status]?.bg, statusConfig[p.status]?.text]">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="[statusConfig[p.status]?.dot]"></span>
                                            {{ statusConfig[p.status]?.label || p.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-center" @click.stop>
                                        <div class="flex items-center justify-center gap-1">
                                            <Link :href="`/admin/doctor-payouts/${p.id}`"
                                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#C4A265] hover:text-[#B3914F] px-2 py-1 rounded-lg hover:bg-[#C4A265]/5 transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                {{ $t('a_view') }}
                                            </Link>
                                            <Link v-if="p.status === 'paid'" :href="`/admin/doctor-payouts/${p.id}/print`"
                                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 px-2 py-1 rounded-lg hover:bg-emerald-50 transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                                {{ $t('a_receipt') }}
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Empty State -->
                        <div v-if="!payouts.data?.length" class="py-20 text-center">
                            <div class="inline-flex flex-col items-center gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-500">{{ $t('a_no_payouts_found') }}</p>
                                    <p class="text-xs text-gray-400 mt-1" v-if="hasActiveFilters">{{ $t('a_try_adjusting_filters') }}</p>
                                    <p class="text-xs text-gray-400 mt-1" v-else>{{ $t('a_create_first_payout') }}</p>
                                </div>
                                <Link v-if="!hasActiveFilters" href="/admin/doctor-payouts/create" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#C4A265] hover:text-[#B3914F] px-3 py-1.5 rounded-lg hover:bg-[#C4A265]/5 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    {{ $t('a_create_payout') }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="payouts.links?.length > 3" class="flex items-center justify-between px-6 py-3 border-t border-gray-100 bg-gray-50/40">
                        <p class="text-[11px] text-gray-400">Page {{ payouts.current_page }} of {{ payouts.last_page }}</p>
                        <div class="flex gap-1">
                            <template v-for="link in payouts.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                    :class="link.active ? 'bg-[#C4A265] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                                    v-html="link.label"
                                    preserve-scroll
                                />
                                <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar (1 col) -->
            <div class="space-y-5">

                <!-- Quick Stats Widget -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_quick_stats') }}</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                {{ $t('a_total_paid_all_time') }}
                            </span>
                            <span class="text-xs font-bold text-gray-800 tabular-nums">{{ formatCurrency(summary.total_paid_all_time) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                {{ $t('a_avg_payout') }}
                            </span>
                            <span class="text-xs font-bold text-gray-800 tabular-nums">{{ formatCurrency(summary.avg_payout_amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                                {{ $t('a_total_deductions') }}
                            </span>
                            <span class="text-xs font-bold text-gray-800 tabular-nums">{{ formatCurrency(summary.total_deductions) }}</span>
                        </div>
                        <div class="h-px bg-gray-100"></div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 flex items-center gap-2">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                {{ $t('a_visits_covered') }}
                            </span>
                            <span class="text-xs font-bold text-gray-800 tabular-nums">{{ summary.total_visits_covered }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div v-if="statusDistribution.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_status_overview') }}</h3>
                    </div>
                    <div class="p-5">
                        <!-- Mini Bar Chart -->
                        <div class="flex rounded-full h-2 overflow-hidden mb-4">
                            <div v-for="s in statusDistribution" :key="s.key"
                                :class="s.color"
                                :style="{ width: s.pct + '%' }"
                                class="transition-all duration-500 first:rounded-l-full last:rounded-r-full">
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="space-y-2.5">
                            <div v-for="s in statusDistribution" :key="s.key" class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" :class="s.color"></span>
                                    {{ statusConfig[s.key]?.label }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-700">{{ s.count }}</span>
                                    <span class="text-[10px] text-gray-400">({{ s.pct }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Doctors -->
                <div v-if="topDoctors?.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_top_doctors') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="(doc, index) in topDoctors" :key="index" class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50/50 transition-colors">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#C4A265]/20 to-[#C4A265]/5 flex items-center justify-center text-[10px] font-bold text-[#C4A265]">
                                {{ doc.doctor_name?.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 truncate">Dr. {{ doc.doctor_name }}</p>
                                <p class="text-[10px] text-gray-400">{{ doc.payout_count }} payout{{ doc.payout_count !== 1 ? 's' : '' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-[#C4A265] tabular-nums">{{ formatCurrency(doc.total_paid) }}</p>
                                <p class="text-[10px] text-gray-400">{{ currencyCode }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div v-if="recentActivity?.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white">
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_recent_activity') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <Link v-for="item in recentActivity" :key="item.id" :href="`/admin/doctor-payouts/${item.id}`"
                            class="block px-5 py-3 hover:bg-gray-50/50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center mt-0.5 shrink-0" :class="[statusConfig[item.status]?.bg]">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="[statusConfig[item.status]?.dot]"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] font-mono font-bold text-[#C4A265]">{{ item.payout_number }}</span>
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :class="[statusConfig[item.status]?.bg, statusConfig[item.status]?.text]">
                                            {{ statusConfig[item.status]?.label }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Dr. {{ item.doctor_name }} &middot; {{ formatCurrency(item.net_amount) }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ item.updated_at }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
