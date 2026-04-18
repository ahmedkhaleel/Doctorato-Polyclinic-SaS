<script setup>
import { ref, watch, onMounted , computed } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const props = defineProps({
    commissions: Object,
    stats: Object,
    marketers: Array,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const userFilter = ref(props.filters?.user_id || '');
const statusFilter = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

function applyFilters() {
    router.get('/admin/commissions', {
        user_id: userFilter.value || undefined,
        status: statusFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([userFilter, statusFilter], applyFilters);

function filterByDate() { applyFilters(); }

const statusColors = {
    pending: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/60',
    approved: 'bg-slate-50 text-[#1B365D] ring-1 ring-slate-200/60',
    paid: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60',
    rejected: 'bg-red-50 text-red-700 ring-1 ring-red-200/60',
};

const statusIcons = {
    pending: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
    approved: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
    paid: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />',
    rejected: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />',
};

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function approve(id) {
    if (confirm('Approve this commission?')) {
        router.post(`/admin/commissions/${id}/approve`);
    }
}

function markPaid(id) {
    if (confirm('Mark this commission as paid?')) {
        router.post(`/admin/commissions/${id}/mark-paid`);
    }
}

function deleteCommission(id) {
    if (confirm('Delete this commission?')) {
        router.post(`/admin/commissions/${id}/delete`);
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_marketer_commissions')">
        <div class="space-y-6">
            <!-- Header -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 ease-out flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ $t('a_marketer_commissions') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_commissions_description') }}</p>
                </div>
                <Link v-if="can('marketer_commissions.create')" href="/admin/commissions/create"
                    class="inline-flex items-center gap-2 px-4 md:px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/35 hover:-translate-y-0.5 active:translate-y-0"
                    style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_add_commission') }}</Link>
            </div>

            <!-- Stats Cards -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 delay-100 ease-out grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Pending -->
                <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden cursor-default">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-amber-400 to-amber-500 opacity-80"></div>
                    <div class="absolute top-0 ltr:right-0 rtl:left-0 w-28 h-28 bg-gradient-to-br from-amber-500/5 to-transparent rounded-bl-[5rem]"></div>
                    <div class="relative">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full ring-1 ring-amber-100">{{ stats.pending_count }}</span>
                        </div>
                        <p class="text-xl md:text-2xl font-bold text-amber-600 tracking-tight font-mono">{{ formatCurrency(stats.total_pending) }}</p>
                        <p class="text-xs text-gray-400 mt-1.5 font-semibold uppercase tracking-wider">{{ $t('a_pending') }}</p>
                    </div>
                </div>

                <!-- Approved -->
                <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden cursor-default">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-slate-400 to-[#1B365D] opacity-80"></div>
                    <div class="absolute top-0 ltr:right-0 rtl:left-0 w-28 h-28 bg-gradient-to-br from-[#1B365D]/5 to-transparent rounded-bl-[5rem]"></div>
                    <div class="relative">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-11 h-11 rounded-xl bg-slate-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <p class="text-xl md:text-2xl font-bold text-[#1B365D] tracking-tight font-mono">{{ formatCurrency(stats.total_approved) }}</p>
                        <p class="text-xs text-gray-400 mt-1.5 font-semibold uppercase tracking-wider">{{ $t('a_approved') }}</p>
                    </div>
                </div>

                <!-- Paid -->
                <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden cursor-default">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-500 opacity-80"></div>
                    <div class="absolute top-0 ltr:right-0 rtl:left-0 w-28 h-28 bg-gradient-to-br from-emerald-500/5 to-transparent rounded-bl-[5rem]"></div>
                    <div class="relative">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </div>
                        <p class="text-xl md:text-2xl font-bold text-emerald-600 tracking-tight font-mono">{{ formatCurrency(stats.total_paid) }}</p>
                        <p class="text-xs text-gray-400 mt-1.5 font-semibold uppercase tracking-wider">{{ $t('a_total_paid') }}</p>
                    </div>
                </div>

                <!-- Outstanding -->
                <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden cursor-default">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 opacity-80" style="background: linear-gradient(90deg, #C4A265, #D4B87A);"></div>
                    <div class="absolute top-0 ltr:right-0 rtl:left-0 w-28 h-28 bg-gradient-to-br from-[#C4A265]/5 to-transparent rounded-bl-[5rem]"></div>
                    <div class="relative">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-11 h-11 rounded-xl bg-[#C4A265]/10 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <p class="text-xl md:text-2xl font-bold tracking-tight font-mono" style="color: #C4A265;">{{ formatCurrency(stats.total_pending + stats.total_approved) }}</p>
                        <p class="text-xs text-gray-400 mt-1.5 font-semibold uppercase tracking-wider">{{ $t('a_outstanding') }}</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 delay-150 ease-out bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Status Pills -->
                    <div class="flex items-center bg-gray-50 rounded-xl p-1 gap-1">
                        <button @click="statusFilter = ''"
                            :class="statusFilter === '' ? 'text-white shadow-md' : 'text-gray-500 hover:text-gray-700 hover:bg-white'"
                            :style="statusFilter === '' ? 'background: linear-gradient(135deg, #C4A265, #D4B87A)' : ''"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg transition-all duration-200">{{ $t('a_all') }}</button>
                        <button @click="statusFilter = 'pending'"
                            :class="statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-md' : 'text-gray-500 hover:text-gray-700 hover:bg-white'"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg transition-all duration-200">{{ $t('a_pending') }}</button>
                        <button @click="statusFilter = 'approved'"
                            :class="statusFilter === 'approved' ? 'bg-[#1B365D] text-white shadow-md' : 'text-gray-500 hover:text-gray-700 hover:bg-white'"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg transition-all duration-200">{{ $t('a_approved') }}</button>
                        <button @click="statusFilter = 'paid'"
                            :class="statusFilter === 'paid' ? 'bg-emerald-500 text-white shadow-md' : 'text-gray-500 hover:text-gray-700 hover:bg-white'"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg transition-all duration-200">{{ $t('a_paid') }}</button>
                        <button @click="statusFilter = 'rejected'"
                            :class="statusFilter === 'rejected' ? 'bg-red-500 text-white shadow-md' : 'text-gray-500 hover:text-gray-700 hover:bg-white'"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg transition-all duration-200">{{ $t('a_rejected') }}</button>
                    </div>

                    <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>

                    <select v-model="userFilter"
                        class="doctorato-input px-4 py-2.5 text-xs font-semibold border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200 appearance-none cursor-pointer">
                        <option value="">{{ $t('a_all_marketers') }}</option>
                        <option v-for="m in marketers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>

                    <div class="flex items-center gap-2 ml-auto">
                        <input v-model="dateFrom" type="date" @change="filterByDate"
                            class="doctorato-input px-3 py-2.5 text-xs border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200" />
                        <span class="text-gray-300 text-xs font-medium">to</span>
                        <input v-model="dateTo" type="date" @change="filterByDate"
                            class="doctorato-input px-3 py-2.5 text-xs border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 delay-200 ease-out bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Gold gradient top bar -->
                <div class="h-1" style="background: linear-gradient(90deg, #C4A265, #D4B87A, #C4A265);"></div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[10px] text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-gray-50/60">
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_marketer') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_lead') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_type') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold ltr:text-right rtl:text-left">{{ $t('a_base_amount') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold ltr:text-right rtl:text-left">{{ $t('a_commission') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold ltr:text-right rtl:text-left">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(c, idx) in commissions.data" :key="c.id"
                                :style="{ transitionDelay: (idx * 30) + 'ms' }"
                                :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                                class="transition-all duration-300 hover:bg-gradient-to-r hover:from-[#C4A265]/[0.03] hover:to-transparent group">
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-xs font-bold shadow-sm flex-shrink-0"
                                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                            {{ c.marketer?.name?.charAt(0)?.toUpperCase() || '?' }}
                                        </div>
                                        <span class="font-semibold text-gray-800 text-sm">{{ c.marketer?.name || '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <Link v-if="c.lead" :href="`/admin/leads/${c.lead_id}`"
                                        class="text-gray-600 hover:text-[#C4A265] transition-colors duration-200 font-medium text-sm">
                                        {{ c.lead.full_name }}
                                    </Link>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="text-xs text-gray-700 capitalize font-semibold">{{ c.commission_type }}</span>
                                        <span class="text-[10px] text-gray-400 font-mono mt-0.5">
                                            {{ c.commission_type === 'percentage' ? c.rate + '%' : formatCurrency(c.rate) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 ltr:text-right rtl:text-left">
                                    <span class="font-mono text-gray-500 text-xs">{{ formatCurrency(c.base_amount) }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 ltr:text-right rtl:text-left">
                                    <span class="font-mono font-bold text-gray-900 text-sm">{{ formatCurrency(c.commission_amount) }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span :class="statusColors[c.status] || 'bg-gray-100 text-gray-600 ring-1 ring-gray-200'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-full capitalize">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="statusIcons[c.status] || ''"></svg>
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-xs text-gray-400 font-medium whitespace-nowrap">{{ formatDate(c.created_at) }}</td>
                                <td class="px-4 md:px-6 py-4 ltr:text-right rtl:text-left">
                                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <button v-if="c.status === 'pending' && can('marketer_commissions.update')" @click="approve(c.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-bold rounded-lg bg-slate-50 text-[#1B365D] hover:bg-slate-100 transition-all duration-200 ring-1 ring-slate-100" :title="$t('a_approve')">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>{{ $t('a_approve') }}</button>
                                        <button v-if="c.status === 'approved' && can('marketer_commissions.update')" @click="markPaid(c.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-all duration-200 ring-1 ring-emerald-100" :title="$t('a_mark_paid')">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>{{ $t('a_paid') }}</button>
                                        <button v-if="c.status !== 'paid' && can('marketer_commissions.delete')" @click="deleteCommission(c.id)"
                                            class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all duration-200" :title="$t('a_delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                    <!-- Always-visible actions for non-hover devices -->
                                    <div class="flex items-center justify-end gap-1 sm:hidden">
                                        <button v-if="c.status === 'pending' && can('marketer_commissions.update')" @click="approve(c.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-bold rounded-lg bg-slate-50 text-[#1B365D] hover:bg-slate-100 transition-all duration-200 ring-1 ring-slate-100">{{ $t('a_approve') }}</button>
                                        <button v-if="c.status === 'approved' && can('marketer_commissions.update')" @click="markPaid(c.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-all duration-200 ring-1 ring-emerald-100">{{ $t('a_paid') }}</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!commissions.data?.length" class="px-4 md:px-6 py-24 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">{{ $t('a_no_commissions_found') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $t('a_no_commissions_hint') }}</p>
                </div>

                <!-- Pagination -->
                <div v-if="commissions.links?.length > 3" class="px-4 md:px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <p class="text-xs text-gray-400 font-medium">
                        {{ $t('a_showing') }} <span class="text-gray-600 font-semibold">{{ commissions.from }}-{{ commissions.to }}</span> {{ $t('a_of') }} <span class="text-gray-600 font-semibold">{{ commissions.total }}</span>
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in commissions.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url"
                                :class="link.active ? 'text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                                :style="link.active ? 'background: linear-gradient(135deg, #C4A265, #D4B87A)' : ''"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200" v-html="link.label" />
                            <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
