<script setup>
import { ref, watch, onMounted , computed } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const props = defineProps({
    leads: Object,
    stats: Object,
    sources: Array,
    campaigns: Array,
    assignees: Array,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);

onMounted(() => {
    setTimeout(() => {
        mounted.value = true;
    }, 50);
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const priorityFilter = ref(props.filters?.priority || '');
const sourceFilter = ref(props.filters?.source_id || '');
const assignedFilter = ref(props.filters?.assigned_to || '');
const moduleFilter = ref(props.filters?.module || '');

const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    const mods = [];
    if (modules.value) {
        Object.values(modules.value).forEach(m => {
            if (m.enabled) mods.push(m);
        });
    }
    return mods;
});

let searchTimeout = null;

function applyFilters() {
    router.get('/admin/leads', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        priority: priorityFilter.value || undefined,
        source_id: sourceFilter.value || undefined,
        assigned_to: assignedFilter.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([statusFilter, priorityFilter, sourceFilter, assignedFilter], applyFilters);
watch(moduleFilter, applyFilters);

function clearFilters() {
    search.value = '';
    statusFilter.value = '';
    priorityFilter.value = '';
    sourceFilter.value = '';
    assignedFilter.value = '';
    moduleFilter.value = '';
    router.get('/admin/leads', {}, { preserveState: true, replace: true });
}

const statusLabels = {
    new: 'New', contacted: 'Contacted', qualified: 'Qualified',
    appointment_booked: 'Appt. Booked', consultation_done: 'Consultation',
    negotiation: 'Negotiation', converted: 'Converted', lost: 'Lost', dormant: 'Dormant',
};

const statusColors = {
    new: 'bg-blue-100 text-blue-700 ring-blue-600/10',
    contacted: 'bg-indigo-100 text-indigo-700 ring-indigo-600/10',
    qualified: 'bg-purple-100 text-purple-700 ring-purple-600/10',
    appointment_booked: 'bg-amber-100 text-amber-700 ring-amber-600/10',
    consultation_done: 'bg-teal-100 text-teal-700 ring-teal-600/10',
    negotiation: 'bg-orange-100 text-orange-700 ring-orange-600/10',
    converted: 'bg-green-100 text-green-700 ring-green-600/10',
    lost: 'bg-red-100 text-red-700 ring-red-600/10',
    dormant: 'bg-gray-100 text-gray-600 ring-gray-500/10',
};

const priorityLabels = { 1: 'Hot', 2: 'Warm', 3: 'Cold' };
const priorityBadges = {
    1: 'bg-red-100 text-red-700 ring-red-600/10',
    2: 'bg-amber-100 text-amber-700 ring-amber-600/10',
    3: 'bg-blue-100 text-blue-700 ring-blue-600/10',
};

const priorityDots = {
    1: 'bg-red-500',
    2: 'bg-amber-500',
    3: 'bg-blue-500',
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function timeAgo(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

function getInitials(name) {
    if (!name) return '??';
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

const hasActiveFilters = () => search.value || statusFilter.value || priorityFilter.value || sourceFilter.value || assignedFilter.value || moduleFilter.value;

// ── Bulk Actions ──
const selectedLeads = ref([]);
const showBulkModal = ref(false);
const bulkAction = ref('');
const bulkAssignTo = ref('');
const bulkStatus = ref('');

function toggleSelectAll(e) {
    if (e.target.checked) {
        selectedLeads.value = props.leads.data.map(l => l.id);
    } else {
        selectedLeads.value = [];
    }
}

function toggleSelect(id) {
    const idx = selectedLeads.value.indexOf(id);
    if (idx >= 0) selectedLeads.value.splice(idx, 1);
    else selectedLeads.value.push(id);
}

function executeBulkAction() {
    if (!bulkAction.value || !selectedLeads.value.length) return;
    const payload = {
        lead_ids: selectedLeads.value,
        action: bulkAction.value,
    };
    if (bulkAction.value === 'assign') payload.assigned_to = bulkAssignTo.value;
    if (bulkAction.value === 'status') payload.status = bulkStatus.value;

    if (bulkAction.value === 'delete' && !confirm(`Delete ${selectedLeads.value.length} leads?`)) return;

    router.post('/admin/leads/bulk-action', payload, {
        onSuccess: () => {
            selectedLeads.value = [];
            showBulkModal.value = false;
            bulkAction.value = '';
        },
    });
}

function exportLeads() {
    const params = new URLSearchParams();
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (sourceFilter.value) params.set('source_id', sourceFilter.value);
    window.location.href = '/admin/leads-export?' + params.toString();
}
</script>

<template>
    <AdminLayout title="Leads">
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="card-entrance flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                :class="{ 'card-entrance-active': mounted }"
                :style="{ transitionDelay: '0ms' }"
            >
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center shadow-lg shadow-[#C4A265]/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_leads') }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $t('a_leads_subtitle') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link v-if="can('leads.create')" href="/admin/leads-import"
                        class="inline-flex items-center px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow" title="Import CSV"
                    >
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>{{ $t('a_import') }}</Link>
                    <button @click="exportLeads" class="inline-flex items-center px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow" title="Export CSV">
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>{{ $t('a_export') }}</button>
                    <Link href="/admin/leads/pipeline"
                        class="inline-flex items-center px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow"
                    >
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>{{ $t('a_pipeline') }}</Link>
                    <Link v-if="can('leads.create')" href="/admin/leads/create"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-md shadow-[#C4A265]/25 hover:shadow-lg hover:shadow-[#C4A265]/30 hover:-translate-y-0.5 active:translate-y-0"
                        style="background: linear-gradient(135deg, #C4A265, #A8893E);"
                    >
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_new_lead') }}</Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div
                class="card-entrance grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3"
                :class="{ 'card-entrance-active': mounted }"
                :style="{ transitionDelay: '80ms' }"
            >
                <div class="group bg-white rounded-2xl shadow-sm hover:shadow-md p-4 border border-gray-100 text-center transition-all duration-300 hover:-translate-y-0.5 cursor-default border-t-[3px] border-t-gray-300">
                    <div class="w-9 h-9 mx-auto rounded-lg bg-gray-50 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-800">{{ stats.total }}</p>
                    <p class="text-[10px] text-gray-500 uppercase mt-1 font-semibold tracking-wide">{{ $t('a_total') }}</p>
                </div>
                <div class="group bg-white rounded-2xl shadow-sm hover:shadow-md p-4 border border-gray-100 text-center transition-all duration-300 hover:-translate-y-0.5 cursor-default border-t-[3px] border-t-blue-400">
                    <div class="w-9 h-9 mx-auto rounded-lg bg-blue-50 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    </div>
                    <p class="text-xl font-bold text-blue-600">{{ stats.new }}</p>
                    <p class="text-[10px] text-gray-500 uppercase mt-1 font-semibold tracking-wide">{{ $t('a_new') }}</p>
                </div>
                <div class="group bg-white rounded-2xl shadow-sm hover:shadow-md p-4 border border-gray-100 text-center transition-all duration-300 hover:-translate-y-0.5 cursor-default border-t-[3px] border-t-indigo-400">
                    <div class="w-9 h-9 mx-auto rounded-lg bg-indigo-50 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                    <p class="text-xl font-bold text-indigo-600">{{ stats.in_pipeline }}</p>
                    <p class="text-[10px] text-gray-500 uppercase mt-1 font-semibold tracking-wide">{{ $t('a_in_pipeline') }}</p>
                </div>
                <div class="group bg-white rounded-2xl shadow-sm hover:shadow-md p-4 border border-gray-100 text-center transition-all duration-300 hover:-translate-y-0.5 cursor-default border-t-[3px] border-t-emerald-400">
                    <div class="w-9 h-9 mx-auto rounded-lg bg-emerald-50 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-xl font-bold text-green-600">{{ stats.converted }}</p>
                    <p class="text-[10px] text-gray-500 uppercase mt-1 font-semibold tracking-wide">{{ $t('a_converted') }}</p>
                </div>
                <div class="group bg-white rounded-2xl shadow-sm hover:shadow-md p-4 border border-gray-100 text-center transition-all duration-300 hover:-translate-y-0.5 cursor-default border-t-[3px] border-t-red-400">
                    <div class="w-9 h-9 mx-auto rounded-lg bg-red-50 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-xl font-bold text-red-600">{{ stats.lost }}</p>
                    <p class="text-[10px] text-gray-500 uppercase mt-1 font-semibold tracking-wide">{{ $t('a_lost') }}</p>
                </div>
                <div class="group bg-white rounded-2xl shadow-sm hover:shadow-md p-4 border border-gray-100 text-center transition-all duration-300 hover:-translate-y-0.5 cursor-default border-t-[3px] border-t-orange-400">
                    <div class="w-9 h-9 mx-auto rounded-lg bg-orange-50 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" /></svg>
                    </div>
                    <p class="text-xl font-bold text-red-500">{{ stats.hot }}</p>
                    <p class="text-[10px] text-gray-500 uppercase mt-1 font-semibold tracking-wide">{{ $t('a_hot_leads') }}</p>
                </div>
                <div class="group bg-white rounded-2xl shadow-sm hover:shadow-md p-4 border border-gray-100 text-center transition-all duration-300 hover:-translate-y-0.5 cursor-default" :class="stats.overdue_follow_ups > 0 ? 'border-t-[3px] border-t-red-500' : 'border-t-[3px] border-t-gray-200'">
                    <div class="w-9 h-9 mx-auto rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-300" :class="stats.overdue_follow_ups > 0 ? 'bg-red-50' : 'bg-gray-50'">
                        <svg class="w-4 h-4" :class="stats.overdue_follow_ups > 0 ? 'text-red-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-xl font-bold" :class="stats.overdue_follow_ups > 0 ? 'text-red-600' : 'text-gray-400'">{{ stats.overdue_follow_ups }}</p>
                    <p class="text-[10px] text-gray-500 uppercase mt-1 font-semibold tracking-wide">{{ $t('a_overdue') }}</p>
                </div>
            </div>

            <!-- Module Tabs -->
            <div v-if="activeModules.length > 1" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-1.5 flex gap-1 flex-wrap">
                <button
                    @click="moduleFilter = ''"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="moduleFilter === '' ? 'bg-gray-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                >
                    {{ $t('a_all') }}
                </button>
                <button
                    v-for="mod in activeModules"
                    :key="mod.slug"
                    @click="moduleFilter = mod.slug"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1.5"
                    :class="moduleFilter === mod.slug ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                    :style="moduleFilter === mod.slug ? { backgroundColor: mod.color } : {}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                    <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
                </button>
            </div>

            <!-- Filters -->
            <div
                class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md p-5 border border-gray-100 transition-all duration-300"
                :class="{ 'card-entrance-active': mounted }"
                :style="{ transitionDelay: '160ms' }"
            >
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <!-- Search Input -->
                    <div class="relative md:col-span-2">
                        <div class="absolute ltr:left-3.5 rtl:right-3.5 top-1/2 -translate-y-1/2 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input v-model="search" type="text" placeholder="Search by name, phone, email..."
                            class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder:text-gray-400" />
                    </div>
                    <!-- Status Filter -->
                    <div class="relative">
                        <div class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <select v-model="statusFilter" class="w-full text-sm bg-gray-50 border border-gray-200 rounded-xl py-2.5 ltr:pl-9 rtl:pr-9 ltr:pr-8 rtl:pl-8 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 appearance-none cursor-pointer">
                            <option value="">{{ $t('a_all_statuses') }}</option>
                            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <div class="absolute ltr:right-3 rtl:left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                    <!-- Priority Filter -->
                    <div class="relative">
                        <div class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                        </div>
                        <select v-model="priorityFilter" class="w-full text-sm bg-gray-50 border border-gray-200 rounded-xl py-2.5 ltr:pl-9 rtl:pr-9 ltr:pr-8 rtl:pl-8 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 appearance-none cursor-pointer">
                            <option value="">{{ $t('a_all_priorities') }}</option>
                            <option value="1">{{ $t('a_hot') }}</option>
                            <option value="2">{{ $t('a_warm') }}</option>
                            <option value="3">{{ $t('a_cold') }}</option>
                        </select>
                        <div class="absolute ltr:right-3 rtl:left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                    <!-- Source Filter -->
                    <div class="relative">
                        <div class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                        </div>
                        <select v-model="sourceFilter" class="w-full text-sm bg-gray-50 border border-gray-200 rounded-xl py-2.5 ltr:pl-9 rtl:pr-9 ltr:pr-8 rtl:pl-8 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 appearance-none cursor-pointer">
                            <option value="">{{ $t('a_all_sources') }}</option>
                            <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name_en }}</option>
                        </select>
                        <div class="absolute ltr:right-3 rtl:left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                </div>
                <!-- Active filters summary -->
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100" v-if="hasActiveFilters()">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        <span class="text-xs font-medium text-gray-600">{{ leads.total }} results found</span>
                    </div>
                    <button @click="clearFilters" class="inline-flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-600 hover:bg-red-50 px-2.5 py-1 rounded-lg transition-all duration-200">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear all filters
                    </button>
                </div>
            </div>

            <!-- Bulk Action Bar -->
            <transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-3"
            >
                <div v-if="selectedLeads.length > 0" class="bg-gradient-to-r from-[#C4A265]/10 to-[#C4A265]/5 border border-[#C4A265]/25 rounded-2xl p-4 flex flex-wrap items-center gap-3 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-[#C4A265] flex items-center justify-center">
                            <span class="text-xs font-bold text-white">{{ selectedLeads.length }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-700">leads selected</span>
                    </div>
                    <div class="h-6 w-px bg-[#C4A265]/20 hidden sm:block"></div>
                    <div class="relative">
                        <select v-model="bulkAction" class="text-sm bg-white border border-gray-200 rounded-xl py-2 ltr:pl-3 rtl:pr-3 ltr:pr-8 rtl:pl-8 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200 appearance-none cursor-pointer">
                            <option value="">Choose action...</option>
                            <option value="assign">Assign to user</option>
                            <option value="status">Change status</option>
                            <option value="delete">{{ $t('a_delete') }}</option>
                        </select>
                        <div class="absolute ltr:right-2.5 rtl:left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                    <div v-if="bulkAction === 'assign'" class="relative">
                        <select v-model="bulkAssignTo" class="text-sm bg-white border border-gray-200 rounded-xl py-2 ltr:pl-3 rtl:pr-3 ltr:pr-8 rtl:pl-8 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200 appearance-none cursor-pointer">
                            <option value="">Select user...</option>
                            <option v-for="a in assignees" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                        <div class="absolute ltr:right-2.5 rtl:left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                    <div v-if="bulkAction === 'status'" class="relative">
                        <select v-model="bulkStatus" class="text-sm bg-white border border-gray-200 rounded-xl py-2 ltr:pl-3 rtl:pr-3 ltr:pr-8 rtl:pl-8 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200 appearance-none cursor-pointer">
                            <option value="">Select status...</option>
                            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <div class="absolute ltr:right-2.5 rtl:left-2.5 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                    <button @click="executeBulkAction" :disabled="!bulkAction || (bulkAction === 'assign' && !bulkAssignTo) || (bulkAction === 'status' && !bulkStatus)"
                        class="px-4 py-2 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:shadow-sm" style="background: linear-gradient(135deg, #C4A265, #A8893E);">
                        Apply Action
                    </button>
                    <button @click="selectedLeads = []; bulkAction = '';" class="text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-xl transition-all duration-200">{{ $t('a_cancel') }}</button>
                </div>
            </transition>

            <!-- Table -->
            <div
                class="card-entrance bg-white rounded-2xl shadow-sm hover:shadow-md border border-gray-100 overflow-hidden transition-all duration-300"
                :class="{ 'card-entrance-active': mounted }"
                :style="{ transitionDelay: '240ms' }"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[11px] text-gray-400 uppercase border-b border-gray-100 bg-gray-50/60">
                                <th class="px-3 py-3.5 w-10">
                                    <input type="checkbox" @change="toggleSelectAll" :checked="selectedLeads.length === leads.data?.length && leads.data?.length > 0" class="rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265] focus:ring-offset-0 transition" />
                                </th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider">{{ $t('a_lead') }}</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider">{{ $t('a_contact') }}</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider">{{ $t('a_source') }}</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider text-center">{{ $t('a_priority') }}</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider text-center">{{ $t('a_status') }}</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider text-center">{{ $t('a_score') }}</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider">{{ $t('a_assigned_to') }}</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider">Next Follow-up</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider">{{ $t('a_created') }}</th>
                                <th class="px-5 py-3.5 font-semibold tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(lead, index) in leads.data" :key="lead.id"
                                class="transition-colors duration-150 group"
                                :class="[
                                    selectedLeads.includes(lead.id) ? 'bg-[#C4A265]/5' : (index % 2 === 1 ? 'bg-gray-50/30' : ''),
                                    'hover:bg-[#C4A265]/5'
                                ]"
                            >
                                <td class="px-3 py-3.5">
                                    <input type="checkbox" :checked="selectedLeads.includes(lead.id)" @change="toggleSelect(lead.id)" class="rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265] focus:ring-offset-0 transition" />
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#C4A265] to-[#A8893E] flex items-center justify-center flex-shrink-0 shadow-sm">
                                            <span class="text-[10px] font-bold text-white">{{ getInitials(lead.full_name) }}</span>
                                        </div>
                                        <div>
                                            <Link :href="`/admin/leads/${lead.id}`" class="font-semibold text-gray-800 hover:text-[#C4A265] transition-colors duration-200">
                                                {{ lead.full_name }}
                                            </Link>
                                            <p class="text-xs text-gray-400 mt-0.5" v-if="lead.city">{{ lead.city }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="text-gray-700 font-medium">{{ lead.phone || '-' }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5" v-if="lead.email">{{ lead.email }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span v-if="lead.source" class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :style="{ backgroundColor: lead.source.color + '12', color: lead.source.color, border: '1px solid ' + lead.source.color + '20' }"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: lead.source.color }"></span>
                                        {{ lead.source.name_en }}
                                    </span>
                                    <span v-else class="text-gray-400 text-xs">-</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span :class="priorityBadges[lead.priority]" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-full uppercase ring-1">
                                        <span :class="priorityDots[lead.priority]" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ priorityLabels[lead.priority] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span :class="statusColors[lead.status]" class="px-2.5 py-1 text-[10px] font-bold rounded-full whitespace-nowrap ring-1">
                                        {{ statusLabels[lead.status] || lead.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="text-sm font-mono font-bold" :class="lead.score >= 50 ? 'text-emerald-600' : lead.score >= 20 ? 'text-amber-600' : 'text-gray-400'">
                                            {{ lead.score }}
                                        </span>
                                        <div class="w-8 h-1 rounded-full bg-gray-100 mt-1 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="lead.score >= 50 ? 'bg-emerald-400' : lead.score >= 20 ? 'bg-amber-400' : 'bg-gray-300'"
                                                :style="{ width: Math.min(lead.score, 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div v-if="lead.assigned_user" class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                                            <span class="text-[9px] font-bold text-gray-600">{{ getInitials(lead.assigned_user.name) }}</span>
                                        </div>
                                        <span class="text-sm text-gray-600">{{ lead.assigned_user.name }}</span>
                                    </div>
                                    <span v-else class="inline-flex items-center gap-1 text-xs text-gray-400 italic">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        Unassigned
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span v-if="lead.next_follow_up_at" class="inline-flex items-center gap-1 text-xs"
                                        :class="new Date(lead.next_follow_up_at) < new Date() ? 'text-red-600 font-bold' : 'text-gray-500'"
                                    >
                                        <svg v-if="new Date(lead.next_follow_up_at) < new Date()" class="w-3 h-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.27 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                        {{ formatDate(lead.next_follow_up_at) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-xs text-gray-400">{{ timeAgo(lead.created_at) }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <Link :href="`/admin/leads/${lead.id}`"
                                        class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition-all duration-200 hover:bg-[#C4A265]/10 opacity-70 group-hover:opacity-100" style="color: #C4A265;"
                                    >{{ $t('a_view') }}<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!leads.data?.length" class="px-6 py-20 text-center">
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center mb-5 shadow-inner">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                    </div>
                    <p class="text-gray-500 text-sm font-medium mb-1">{{ $t('a_no_leads_found') }}</p>
                    <p class="text-gray-400 text-xs mb-4">{{ $t('a_no_leads_hint') }}</p>
                    <Link v-if="can('leads.create')" href="/admin/leads/create"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold px-4 py-2 rounded-xl transition-all duration-200 hover:bg-[#C4A265]/10" style="color: #C4A265;"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_create_first_lead') }}</Link>
                </div>

                <!-- Pagination -->
                <div v-if="leads.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <p class="text-xs font-medium text-gray-500">
                        {{ $t('a_showing') }} <span class="text-gray-700">{{ leads.from }}</span> {{ $t('a_to') }} <span class="text-gray-700">{{ leads.to }}</span> {{ $t('a_of') }} <span class="text-gray-700">{{ leads.total }}</span>
                    </p>
                    <div class="flex gap-1">
                        <Link v-for="link in leads.links" :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                link.active
                                    ? 'text-white shadow-sm'
                                    : 'text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300',
                                !link.url ? 'opacity-30 pointer-events-none' : ''
                            ]"
                            :style="link.active ? 'background: linear-gradient(135deg, #C4A265, #A8893E);' : ''"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.card-entrance {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.5s ease-out, transform 0.5s ease-out, box-shadow 0.3s ease;
}

.card-entrance-active {
    opacity: 1;
    transform: translateY(0);
}
</style>
