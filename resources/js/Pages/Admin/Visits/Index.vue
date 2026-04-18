<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visits: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const visitTypeFilter = ref(props.filters?.visit_type || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');
let searchTimeout = null;

const modules = computed(() => page.props.modules || {});
const medicalSlugs = ['derma', 'dental', 'pediatric'];
const activeModules = computed(() => {
    const mods = [];
    if (modules.value) {
        Object.values(modules.value).forEach(m => {
            if (m.enabled && medicalSlugs.includes(m.slug)) mods.push(m);
        });
    }
    return mods;
});

const hasActiveFilters = computed(() => {
    return search.value || statusFilter.value || visitTypeFilter.value || dateFrom.value || dateTo.value || moduleFilter.value;
});

function buildParams() {
    return {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        visit_type: visitTypeFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    };
}

function applyFilters() {
    router.get('/admin/visits', buildParams(), {
        preserveState: true,
        replace: true,
    });
}

function clearAllFilters() {
    search.value = '';
    statusFilter.value = '';
    visitTypeFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    moduleFilter.value = '';
    router.get('/admin/visits', {}, { preserveState: true, replace: true });
}

function clearSearch() {
    search.value = '';
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

watch([statusFilter, visitTypeFilter, dateFrom, dateTo], () => {
    applyFilters();
});

watch(moduleFilter, () => {
    applyFilters();
});

function deleteVisit(id) {
    if (window.confirm('Are you sure you want to delete this visit? This action cannot be undone.')) {
        router.post(`/admin/visits/${id}/delete`);
    }
}

const statusConfig = {
    waiting: { label: 'Waiting', dot: 'bg-amber-400', text: 'text-amber-700', bg: 'bg-amber-50', border: 'border-amber-100' },
    in_progress: { label: 'In Progress', dot: 'bg-slate-400', text: 'text-[#1B365D]', bg: 'bg-slate-50', border: 'border-slate-100' },
    completed: { label: 'Completed', dot: 'bg-emerald-400', text: 'text-emerald-700', bg: 'bg-emerald-50', border: 'border-emerald-100' },
    cancelled: { label: 'Cancelled', dot: 'bg-red-400', text: 'text-red-700', bg: 'bg-red-50', border: 'border-red-100' },
};

const visitTypeConfig = {
    consultation: { label: 'Consultation', icon: 'chat', classes: 'text-[#1B365D] bg-slate-50 border-slate-100' },
    session: { label: 'Session', icon: 'bolt', classes: 'text-[#1B365D] bg-slate-50 border-slate-100' },
};

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    return name.charAt(0).toUpperCase();
}
</script>

<template>
    <AdminLayout :title="$t('a_visits')">
        <div class="space-y-6">
            <!-- ═════════ Compact Hero ═════════ -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1B365D] to-[#0F2444] flex items-center justify-center shadow-md flex-shrink-0">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xl md:text-2xl font-extrabold text-[#1B365D] truncate">{{ $t('a_visits') }}</h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <span class="font-semibold text-[#C4A265]">{{ visits.total }}</span> {{ $t('a_total_visits') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <Link
                        v-if="can('visits.view')"
                        href="/admin/visits/today-queue"
                        class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200 text-[#1B365D] hover:bg-[#1B365D] hover:text-white font-semibold px-4 py-2.5 transition text-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        {{ $t('a_todays_queue') }}
                    </Link>
                    <Link
                        v-if="can('visits.create')"
                        href="/admin/bookings/create"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-4 py-2.5 shadow-md hover:shadow-lg transition text-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ $t('a_new_booking') }}
                    </Link>
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

            <!-- Search & Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search -->
                    <div class="relative flex-1 min-w-[220px]">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="$t('a_search_visits_placeholder')"
                            class="doctorato-input w-full pl-10 pr-9 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200"
                        />
                        <button v-if="search" @click="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <select
                            v-model="statusFilter"
                            class="doctorato-input pl-10 pr-9 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 appearance-none cursor-pointer"
                        >
                            <option value="">{{ $t('a_all_status') }}</option>
                            <option value="waiting">{{ $t('a_waiting') }}</option>
                            <option value="in_progress">{{ $t('a_in_progress') }}</option>
                            <option value="completed">{{ $t('a_completed') }}</option>
                            <option value="cancelled">{{ $t('a_cancelled') }}</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <select
                            v-model="visitTypeFilter"
                            class="doctorato-input pl-10 pr-9 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200 appearance-none cursor-pointer"
                        >
                            <option value="">{{ $t('a_all_types') }}</option>
                            <option value="consultation">{{ $t('a_consultation') }}</option>
                            <option value="session">{{ $t('a_session') }}</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <!-- Date From -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <input
                            v-model="dateFrom"
                            type="date"
                            :max="dateTo || undefined"
                            class="doctorato-input pl-10 pr-3 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <input
                            v-model="dateTo"
                            type="date"
                            :min="dateFrom || undefined"
                            class="doctorato-input pl-10 pr-3 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] focus:bg-white hover:border-gray-300 transition-all duration-200"
                        />
                    </div>

                    <!-- Clear Filters -->
                    <button
                        v-if="hasActiveFilters"
                        @click="clearAllFilters"
                        class="px-3 py-2.5 text-xs font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl border border-gray-200 hover:border-red-200 transition-all duration-200"
                    >
                        {{ $t('a_clear') }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50">
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_patients') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_doctor') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_type') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_service') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_booking') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="visit in visits.data"
                                :key="visit.id"
                                class="group hover:bg-gray-50/60 transition-colors duration-150"
                            >
                                <!-- Date -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">{{ formatDate(visit.visit_date) }}</span>
                                    </div>
                                </td>

                                <!-- Patient -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center text-white text-xs font-bold shadow-sm" style="background-color: #C4A265;">
                                            {{ getInitials(visit.patient?.full_name) }}
                                        </div>
                                        <div>
                                            <Link :href="`/admin/patients/${visit.patient_id}`" class="text-sm font-semibold text-gray-800 hover:underline transition" style="text-decoration-color: #C4A265;">
                                                {{ visit.patient?.full_name }}
                                            </Link>
                                            <div class="flex items-center gap-1 mt-0.5">
                                                <svg class="w-3 h-3 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                <span class="text-xs text-gray-400" dir="ltr">{{ visit.patient?.phone }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Doctor -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div v-if="visit.doctor?.name_en || visit.doctor?.name_ar" class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <span class="text-sm text-gray-600">{{ $localized(visit.doctor, 'name') }}</span>
                                    </div>
                                    <span v-else class="text-sm text-gray-300">-</span>
                                </td>

                                <!-- Type -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span v-if="visit.visit_type && visitTypeConfig[visit.visit_type]"
                                        :class="visitTypeConfig[visit.visit_type].classes"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border">
                                        <!-- Consultation icon -->
                                        <svg v-if="visit.visit_type === 'consultation'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        <!-- Session icon -->
                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        {{ visit.visit_type === 'consultation' ? $t('a_consultation') : $t('a_session') }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">{{ visit.visit_type || '-' }}</span>
                                </td>

                                <!-- Service -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span v-if="visit.service?.name_en || visit.service?.name_ar" class="text-sm text-gray-600">{{ $localized(visit.service, 'name') }}</span>
                                    <span v-else class="text-sm text-gray-300">-</span>
                                </td>

                                <!-- Booking -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <Link v-if="visit.booking" :href="`/admin/bookings/${visit.booking.id}`"
                                        class="inline-flex items-center gap-1 text-xs font-mono font-bold px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-100 hover:bg-amber-100 transition-colors duration-150" style="color: #C4A265;">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                        {{ visit.booking.booking_number || `#${visit.booking.id}` }}
                                    </Link>
                                    <span v-else class="text-sm text-gray-300">-</span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span v-if="statusConfig[visit.status]"
                                        :class="[statusConfig[visit.status].bg, statusConfig[visit.status].text, statusConfig[visit.status].border]"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border">
                                        <span :class="statusConfig[visit.status].dot" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ {waiting: $t('a_waiting'), in_progress: $t('a_in_progress'), completed: $t('a_completed'), cancelled: $t('a_cancelled')}[visit.status] }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">{{ visit.status }}</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- View -->
                                        <Link v-if="can('visits.view')" :href="`/admin/visits/${visit.id}`"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-amber-50 transition-all duration-200" :title="$t('a_view')">
                                            <svg class="w-4 h-4 hover-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </Link>
                                        <!-- Delete -->
                                        <button v-if="can('visits.delete')" @click="deleteVisit(visit.id)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-200" :title="$t('a_delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!visits.data || visits.data.length === 0">
                                <td colspan="8" class="px-4 md:px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-500">{{ $t('a_no_visits_found') }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ $t('a_try_adjusting_filters') }}</p>
                                        <button v-if="hasActiveFilters" @click="clearAllFilters" class="mt-4 text-xs font-medium px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition">
                                            {{ $t('a_clear_filters') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="visits.links && visits.links.length > 3" class="px-4 md:px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        {{ $t('a_showing') }} <span class="font-semibold text-gray-700">{{ visits.from }}</span>
                        {{ $t('a_to') }} <span class="font-semibold text-gray-700">{{ visits.to }}</span>
                        {{ $t('a_of') }} <span class="font-semibold text-gray-700">{{ visits.total }}</span>
                    </p>
                    <nav class="flex items-center gap-1">
                        <template v-for="link in visits.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-200"
                                :class="link.active ? 'text-white border-transparent shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-300" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.hover-gold:hover {
    color: #C4A265;
}
tr:hover .hover-gold {
    color: #C4A265;
}
</style>
