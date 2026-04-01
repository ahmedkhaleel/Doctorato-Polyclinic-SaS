<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';
import Sortable from 'sortablejs';

const { can } = usePermissions();
const { t } = useLocale();

const props = defineProps({
    columns: Object,
    assignees: Array,
    sources: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

// ─── Local reactive columns for optimistic drag-and-drop ───
const localColumns = ref({});
watch(() => props.columns, (val) => {
    if (val) {
        const cols = {};
        for (const key of Object.keys(val)) {
            cols[key] = [...(val[key] || [])];
        }
        localColumns.value = cols;
    }
}, { immediate: true, deep: true });

const mounted = ref(false);
onMounted(() => {
    setTimeout(() => mounted.value = true, 50);
    nextTick(() => initDragAndDrop());
});

// ─── Filters ───
const filterSource = ref('');
const filterPriority = ref('');
const filterAssignee = ref('');
const showFilters = ref(false);

const hasActiveFilters = computed(() => filterSource.value || filterPriority.value || filterAssignee.value);

function clearFilters() {
    filterSource.value = '';
    filterPriority.value = '';
    filterAssignee.value = '';
}

function filteredLeads(leads) {
    if (!leads) return [];
    return leads.filter(lead => {
        if (filterSource.value && lead.lead_source_id != filterSource.value) return false;
        if (filterPriority.value && lead.priority != filterPriority.value) return false;
        if (filterAssignee.value) {
            if (filterAssignee.value === 'unassigned' && lead.assigned_to) return false;
            if (filterAssignee.value !== 'unassigned' && lead.assigned_to != filterAssignee.value) return false;
        }
        return true;
    });
}

const totalLeads = computed(() => {
    return Object.values(localColumns.value || {}).reduce((sum, leads) => sum + (filteredLeads(leads)?.length || 0), 0);
});

// ─── Status config ───
const statusLabels = computed(() => ({
    new: t('a_lead_new'), contacted: t('a_lead_contacted'), qualified: t('a_lead_qualified'),
    appointment_booked: t('a_lead_appt_booked'), consultation_done: t('a_lead_consultation'), negotiation: t('a_lead_negotiation'),
}));
const statusHeaderGradients = {
    new: 'from-blue-500 to-blue-600',
    contacted: 'from-indigo-500 to-indigo-600',
    qualified: 'from-purple-500 to-purple-600',
    appointment_booked: 'from-amber-500 to-amber-600',
    consultation_done: 'from-teal-500 to-teal-600',
    negotiation: 'from-orange-500 to-orange-600',
};
const statusBgColors = {
    new: 'bg-blue-50/50',
    contacted: 'bg-indigo-50/50',
    qualified: 'bg-purple-50/50',
    appointment_booked: 'bg-amber-50/50',
    consultation_done: 'bg-teal-50/50',
    negotiation: 'bg-orange-50/50',
};
const statusIcons = {
    new: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
    contacted: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
    qualified: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    appointment_booked: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    consultation_done: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    negotiation: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
};
const priorityLabels = computed(() => ({ 1: t('a_hot'), 2: t('a_warm'), 3: t('a_cold') }));
const priorityBorderColors = { 1: 'border-l-red-500', 2: 'border-l-amber-400', 3: 'border-l-blue-400' };
const priorityDots = { 1: 'bg-red-500', 2: 'bg-amber-400', 3: 'bg-blue-400' };
const priorityBadgeBg = { 1: 'bg-red-50 text-red-700', 2: 'bg-amber-50 text-amber-700', 3: 'bg-blue-50 text-blue-700' };

// ─── Drag and Drop ───
const sortableInstances = [];

function initDragAndDrop() {
    // Clean up old instances
    sortableInstances.forEach(s => s.destroy());
    sortableInstances.length = 0;

    const containers = document.querySelectorAll('[data-status]');
    containers.forEach(container => {
        const instance = Sortable.create(container, {
            group: 'pipeline',
            animation: 250,
            easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
            ghostClass: 'pipeline-ghost',
            chosenClass: 'pipeline-chosen',
            dragClass: 'pipeline-drag',
            handle: '.drag-handle',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onEnd(evt) {
                const leadId = evt.item.dataset.leadId;
                const fromStatus = evt.from.dataset.status;
                const toStatus = evt.to.dataset.status;

                if (fromStatus === toStatus) return;

                // Optimistic update: move lead in local state
                const lead = localColumns.value[fromStatus]?.find(l => l.id == leadId);
                if (lead) {
                    localColumns.value[fromStatus] = localColumns.value[fromStatus].filter(l => l.id != leadId);
                    if (!localColumns.value[toStatus]) localColumns.value[toStatus] = [];
                    localColumns.value[toStatus].splice(evt.newIndex, 0, lead);
                }

                // Send to server
                router.post(`/admin/leads/${leadId}/status`, { status: toStatus }, {
                    preserveScroll: true,
                    preserveState: true,
                    onError: () => {
                        // Revert on error
                        if (lead) {
                            localColumns.value[toStatus] = localColumns.value[toStatus].filter(l => l.id != leadId);
                            localColumns.value[fromStatus].push(lead);
                        }
                    }
                });
            },
        });
        sortableInstances.push(instance);
    });
}

// Re-init drag when filters change (DOM changes)
watch([filterSource, filterPriority, filterAssignee], () => {
    nextTick(() => initDragAndDrop());
});

onBeforeUnmount(() => {
    sortableInstances.forEach(s => s.destroy());
});

function moveLeadToStatus(leadId, fromStatus, newStatus) {
    const lead = localColumns.value[fromStatus]?.find(l => l.id == leadId);
    if (lead) {
        localColumns.value[fromStatus] = localColumns.value[fromStatus].filter(l => l.id != leadId);
        if (!localColumns.value[newStatus]) localColumns.value[newStatus] = [];
        localColumns.value[newStatus].unshift(lead);
    }
    router.post(`/admin/leads/${leadId}/status`, { status: newStatus }, { preserveScroll: true, preserveState: true });
}

function timeAgo(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 3600) return Math.floor(diff / 60) + 'm';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    return Math.floor(diff / 86400) + 'd';
}

// ─── Lead Quick View Drawer ───
const drawerOpen = ref(false);
const drawerLead = ref(null);
const drawerActivities = ref([]);
const drawerFollowUps = ref([]);
const drawerLoading = ref(false);

async function openDrawer(lead) {
    drawerOpen.value = true;
    drawerLead.value = lead;
    drawerActivities.value = [];
    drawerFollowUps.value = [];
    drawerLoading.value = true;

    try {
        const res = await fetch(`/admin/leads/${lead.id}/quick-view`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        const data = await res.json();
        drawerLead.value = data.lead;
        drawerActivities.value = data.activities || [];
        drawerFollowUps.value = data.followUps || [];
    } catch (e) {
        console.error('Failed to load lead details', e);
    } finally {
        drawerLoading.value = false;
    }
}

function closeDrawer() {
    drawerOpen.value = false;
    setTimeout(() => {
        drawerLead.value = null;
    }, 300);
}

const activityIcons = {
    note: { path: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', color: 'text-gray-500 bg-gray-100' },
    call: { path: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', color: 'text-green-600 bg-green-100' },
    whatsapp: { path: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', color: 'text-emerald-600 bg-emerald-100' },
    email: { path: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', color: 'text-blue-600 bg-blue-100' },
    sms: { path: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', color: 'text-purple-600 bg-purple-100' },
    meeting: { path: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', color: 'text-amber-600 bg-amber-100' },
    status_change: { path: 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4', color: 'text-indigo-600 bg-indigo-100' },
    follow_up_scheduled: { path: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-cyan-600 bg-cyan-100' },
    follow_up_completed: { path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-green-600 bg-green-100' },
    system: { path: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', color: 'text-gray-500 bg-gray-100' },
};

function getActivityIcon(type) {
    return activityIcons[type] || activityIcons.system;
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout :title="$t('a_lead_pipeline')">
        <div class="space-y-5">
            <!-- Header Card -->
            <div
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
            >
                <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_lead_pipeline') }}</h1>
                            <p class="text-sm text-gray-500 mt-0.5">{{ totalLeads }} {{ $t('a_leads') }} — {{ $t('a_drag_leads_hint') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <!-- Filter Toggle -->
                        <button @click="showFilters = !showFilters"
                            :class="hasActiveFilters ? 'border-[#C4A265] bg-[#C4A265]/5 text-[#C4A265]' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl border text-sm font-medium hover:shadow-sm hover:-translate-y-0.5 transition-all duration-300 relative">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            {{ $t('a_filters') }}
                            <span v-if="hasActiveFilters" class="ltr:ml-1.5 rtl:mr-1.5 w-2 h-2 rounded-full bg-[#C4A265] animate-pulse"></span>
                        </button>
                        <Link href="/admin/leads" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-300">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                            {{ $t('a_list_view') }}
                        </Link>
                        <Link v-if="can('leads.create')" href="/admin/leads/create"
                            class="inline-flex items-center px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5"
                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_new_lead') }}</Link>
                    </div>
                </div>

                <!-- Filter Bar -->
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-40 opacity-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="max-h-40 opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <div v-if="showFilters" class="overflow-hidden border-t border-gray-100">
                        <div class="px-6 py-4 flex flex-wrap items-center gap-3 bg-gray-50/50">
                            <!-- Source filter -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_source') }}</label>
                                <select v-model="filterSource" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none min-w-[140px]">
                                    <option value="">{{ $t('a_all_sources') }}</option>
                                    <option v-for="src in sources" :key="src.id" :value="src.id">{{ src[`name_${locale}`] || src.name_en }}</option>
                                </select>
                            </div>
                            <!-- Priority filter -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_priority') }}</label>
                                <select v-model="filterPriority" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none min-w-[120px]">
                                    <option value="">{{ $t('a_all') }}</option>
                                    <option value="1">{{ $t('a_hot') }}</option>
                                    <option value="2">{{ $t('a_warm') }}</option>
                                    <option value="3">{{ $t('a_cold') }}</option>
                                </select>
                            </div>
                            <!-- Assignee filter -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_assigned_to') }}</label>
                                <select v-model="filterAssignee" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none min-w-[150px]">
                                    <option value="">{{ $t('a_all') }}</option>
                                    <option value="unassigned">{{ $t('a_unassigned') }}</option>
                                    <option v-for="user in assignees" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                            </div>
                            <!-- Clear -->
                            <button v-if="hasActiveFilters" @click="clearFilters" class="text-xs text-red-500 hover:text-red-700 font-medium px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                                {{ $t('a_clear_all') }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Pipeline Stats Summary -->
            <div
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 transition-all duration-700 ease-out"
                style="transition-delay: 80ms;"
            >
                <div v-for="(leads, status) in localColumns" :key="'stat-' + status"
                    class="bg-white rounded-xl border border-gray-100 p-3 hover:-translate-y-0.5 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center gap-2 mb-1.5">
                        <div :class="`bg-gradient-to-r ${statusHeaderGradients[status]}`" class="w-7 h-7 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusIcons[status]" /></svg>
                        </div>
                        <span class="text-xs font-medium text-gray-500 truncate">{{ statusLabels[status] }}</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900 ltr:pl-1 rtl:pr-1">{{ filteredLeads(leads)?.length || 0 }}</p>
                </div>
            </div>

            <!-- Pipeline Board -->
            <div class="flex gap-4 overflow-x-auto pb-4 -mx-2 px-2 scroll-smooth" style="scrollbar-width: thin;">
                <div v-for="(leads, status, colIdx) in localColumns" :key="status"
                    :class="[
                        mounted ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0',
                        statusBgColors[status]
                    ]"
                    :style="{ transitionDelay: (colIdx * 80 + 200) + 'ms' }"
                    class="flex-shrink-0 w-[300px] rounded-2xl border border-gray-200/60 overflow-hidden transition-all duration-700 ease-out"
                >
                    <!-- Column Header -->
                    <div :class="`bg-gradient-to-r ${statusHeaderGradients[status]}`" class="h-1.5"></div>
                    <div class="px-4 py-3.5 flex items-center justify-between border-b border-gray-100/80 bg-white/70 backdrop-blur-sm">
                        <div class="flex items-center gap-2.5">
                            <div :class="`bg-gradient-to-r ${statusHeaderGradients[status]}`" class="w-8 h-8 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusIcons[status]" /></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-700">{{ statusLabels[status] }}</h3>
                                <p class="text-[10px] text-gray-400">{{ filteredLeads(leads)?.length || 0 }} {{ $t('a_leads') }}</p>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full text-white min-w-[28px] text-center shadow-sm"
                            :class="`bg-gradient-to-r ${statusHeaderGradients[status]}`">
                            {{ filteredLeads(leads)?.length || 0 }}
                        </span>
                    </div>

                    <!-- Drag-drop Cards Container -->
                    <div :data-status="status" class="px-3 py-3 space-y-2.5 min-h-[120px] max-h-[65vh] overflow-y-auto" style="scrollbar-width: thin;">
                        <div v-for="lead in filteredLeads(leads)" :key="lead.id"
                            :data-lead-id="lead.id"
                            :class="priorityBorderColors[lead.priority]"
                            class="bg-white/90 backdrop-blur-sm rounded-xl border border-gray-100/80 border-l-[3px] shadow-sm p-3.5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 group cursor-default"
                        >
                            <!-- Drag Handle + Lead Name -->
                            <div class="flex items-start gap-2 mb-2.5">
                                <div class="drag-handle cursor-grab active:cursor-grabbing flex-shrink-0 mt-0.5 p-0.5 rounded hover:bg-gray-100 opacity-40 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
                                        <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                        <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
                                    </svg>
                                </div>
                                <button @click.stop="openDrawer(lead)" class="text-sm font-bold text-gray-800 hover:text-[#C4A265] transition-colors duration-200 line-clamp-1 flex-1 ltr:text-left rtl:text-right">
                                    {{ lead.full_name }}
                                </button>
                                <span :class="priorityBadgeBg[lead.priority]" class="text-[9px] font-bold px-2 py-0.5 rounded-full ltr:ml-1 rtl:mr-1 shrink-0 flex items-center gap-1">
                                    <span :class="priorityDots[lead.priority]" class="w-1.5 h-1.5 rounded-full"></span>
                                    {{ priorityLabels[lead.priority] }}
                                </span>
                            </div>

                            <!-- Phone -->
                            <p v-if="lead.phone" class="text-xs text-gray-500 mb-2.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                <span class="font-mono text-[11px]">{{ lead.phone }}</span>
                            </p>

                            <!-- Source & Time -->
                            <div class="flex items-center gap-1.5 mb-3">
                                <span v-if="lead.source" class="text-[9px] font-semibold px-2 py-0.5 rounded-full"
                                    :style="{ backgroundColor: lead.source.color + '15', color: lead.source.color }">
                                    {{ lead.source[`name_${locale}`] || lead.source.name_en }}
                                </span>
                                <span class="text-[9px] text-gray-400 ml-auto font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ timeAgo(lead.created_at) }} {{ $t('a_ago') }}
                                </span>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between pt-2.5 border-t border-gray-100/80">
                                <span v-if="lead.assigned_user" class="text-[10px] text-gray-500 font-medium flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center text-[9px] font-bold text-gray-500">
                                        {{ lead.assigned_user.name?.charAt(0)?.toUpperCase() }}
                                    </span>
                                    {{ lead.assigned_user.name }}
                                </span>
                                <span v-else class="text-[10px] text-gray-300 italic">{{ $t('a_unassigned') }}</span>

                                <!-- Move arrows -->
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200">
                                    <button v-if="Object.keys(localColumns).indexOf(status) > 0"
                                        @click="moveLeadToStatus(lead.id, status, Object.keys(localColumns)[Object.keys(localColumns).indexOf(status) - 1])"
                                        class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-100 hover:border-[#C4A265]/30 hover:bg-[#C4A265]/10 hover:text-[#C4A265] flex items-center justify-center transition-all duration-200"
                                        :title="`Move to ${statusLabels[Object.keys(localColumns)[Object.keys(localColumns).indexOf(status) - 1]]}`"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                    </button>
                                    <button v-if="Object.keys(localColumns).indexOf(status) < Object.keys(localColumns).length - 1"
                                        @click="moveLeadToStatus(lead.id, status, Object.keys(localColumns)[Object.keys(localColumns).indexOf(status) + 1])"
                                        class="w-7 h-7 rounded-lg bg-gray-50 border border-gray-100 hover:border-[#C4A265]/30 hover:bg-[#C4A265]/10 hover:text-[#C4A265] flex items-center justify-center transition-all duration-200"
                                        :title="`Move to ${statusLabels[Object.keys(localColumns)[Object.keys(localColumns).indexOf(status) + 1]]}`"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="!filteredLeads(leads)?.length" class="py-12 text-center">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                            </div>
                            <p class="text-xs text-gray-400 font-medium">{{ $t('a_no_leads_in_stage') }}</p>
                            <p class="text-[10px] text-gray-300 mt-1">{{ $t('a_drag_leads_hint') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Lead Quick View Drawer ─── -->
        <Teleport to="body">
            <!-- Backdrop -->
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="drawerOpen" @click="closeDrawer" class="fixed inset-0 bg-black/30 backdrop-blur-sm z-[60]"></div>
            </Transition>

            <!-- Drawer Panel -->
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <div v-if="drawerOpen" class="fixed top-0 ltr:right-0 rtl:left-0 h-full w-full max-w-[480px] bg-white shadow-2xl z-[70] flex flex-col">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                {{ drawerLead?.full_name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-lg font-bold text-gray-900 truncate">{{ drawerLead?.full_name }}</h2>
                                <p class="text-xs text-gray-400">{{ $t('a_lead') }} #{{ drawerLead?.id }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link :href="`/admin/leads/${drawerLead?.id}`"
                                class="p-2 rounded-lg text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/10 transition-colors" :title="$t('a_view_full_details')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                            </Link>
                            <button @click="closeDrawer" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Drawer Body -->
                    <div class="flex-1 overflow-y-auto" style="scrollbar-width: thin;">
                        <!-- Loading State -->
                        <div v-if="drawerLoading" class="flex items-center justify-center py-20">
                            <div class="w-8 h-8 border-2 border-[#C4A265]/30 border-t-[#C4A265] rounded-full animate-spin"></div>
                        </div>

                        <div v-else-if="drawerLead" class="divide-y divide-gray-100">
                            <!-- Lead Info Card -->
                            <div class="px-6 py-5">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Status -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_status') }}</label>
                                        <div class="mt-1">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-full text-white"
                                                :class="`bg-gradient-to-r ${statusHeaderGradients[drawerLead.status] || 'from-gray-500 to-gray-600'}`">
                                                {{ statusLabels[drawerLead.status] || drawerLead.status }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Priority -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_priority') }}</label>
                                        <div class="mt-1">
                                            <span :class="priorityBadgeBg[drawerLead.priority]" class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full">
                                                <span :class="priorityDots[drawerLead.priority]" class="w-2 h-2 rounded-full"></span>
                                                {{ priorityLabels[drawerLead.priority] }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Phone -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_phone') }}</label>
                                        <p class="mt-1 text-sm font-mono text-gray-700">{{ drawerLead.phone || '--' }}</p>
                                    </div>
                                    <!-- Email -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_email') }}</label>
                                        <p class="mt-1 text-sm text-gray-700 truncate">{{ drawerLead.email || '--' }}</p>
                                    </div>
                                    <!-- Source -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_source') }}</label>
                                        <div class="mt-1">
                                            <span v-if="drawerLead.source" class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                                :style="{ backgroundColor: drawerLead.source.color + '15', color: drawerLead.source.color }">
                                                {{ drawerLead.source[`name_${locale}`] || drawerLead.source.name_en }}
                                            </span>
                                            <span v-else class="text-sm text-gray-400">--</span>
                                        </div>
                                    </div>
                                    <!-- Campaign -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_campaign') }}</label>
                                        <p class="mt-1 text-sm text-gray-700">{{ drawerLead.campaign?.name || '--' }}</p>
                                    </div>
                                    <!-- Assigned To -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_assigned_to') }}</label>
                                        <p class="mt-1 text-sm text-gray-700">{{ drawerLead.assigned_user?.name || $t('a_unassigned') }}</p>
                                    </div>
                                    <!-- Score -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_score') }}</label>
                                        <p class="mt-1 text-sm font-bold" :class="drawerLead.score >= 70 ? 'text-green-600' : drawerLead.score >= 40 ? 'text-amber-600' : 'text-gray-500'">
                                            {{ drawerLead.score || 0 }} {{ $t('a_pts') }}
                                        </p>
                                    </div>
                                    <!-- Created -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_created') }}</label>
                                        <p class="mt-1 text-sm text-gray-700">{{ formatDate(drawerLead.created_at) }}</p>
                                    </div>
                                    <!-- Last Contact -->
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_last_contact') }}</label>
                                        <p class="mt-1 text-sm text-gray-700">{{ drawerLead.last_contacted_at ? formatDate(drawerLead.last_contacted_at) : $t('a_never') }}</p>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div v-if="drawerLead.notes" class="mt-4">
                                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_notes') }}</label>
                                    <p class="mt-1 text-sm text-gray-600 bg-gray-50 rounded-lg p-3 leading-relaxed">{{ drawerLead.notes }}</p>
                                </div>

                                <!-- Interested Services -->
                                <div v-if="drawerLead.interested_services?.length" class="mt-4">
                                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_interested_in') }}</label>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        <span v-for="svc in drawerLead.interested_services" :key="svc"
                                            class="text-[10px] font-medium px-2.5 py-1 rounded-full bg-[#C4A265]/10 text-[#C4A265]">
                                            {{ svc }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Follow-ups -->
                            <div v-if="drawerFollowUps.length" class="px-6 py-5">
                                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $t('a_pending_follow_ups') }}
                                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ drawerFollowUps.length }}</span>
                                </h3>
                                <div class="space-y-2">
                                    <div v-for="fu in drawerFollowUps" :key="fu.id"
                                        :class="new Date(fu.scheduled_at) < new Date() ? 'border-red-200 bg-red-50/50' : 'border-gray-100 bg-gray-50/50'"
                                        class="rounded-lg border p-3 text-xs">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-gray-700 capitalize">{{ fu.type }}</span>
                                            <span :class="new Date(fu.scheduled_at) < new Date() ? 'text-red-600 font-bold' : 'text-gray-500'">
                                                {{ formatDate(fu.scheduled_at) }}
                                            </span>
                                        </div>
                                        <p v-if="fu.notes" class="text-gray-500 mt-1">{{ fu.notes }}</p>
                                        <p v-if="fu.assigned_user" class="text-gray-400 mt-1 text-[10px]">{{ $t('a_assigned_to') }}: {{ fu.assigned_user.name }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div class="px-6 py-5">
                                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $t('a_recent_activity') }}
                                </h3>
                                <div v-if="drawerActivities.length" class="space-y-3">
                                    <div v-for="act in drawerActivities" :key="act.id" class="flex gap-3">
                                        <div :class="getActivityIcon(act.type).color" class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActivityIcon(act.type).path" /></svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-medium text-gray-700">
                                                {{ act.subject || act.type.replace(/_/g, ' ') }}
                                            </p>
                                            <p v-if="act.description" class="text-[11px] text-gray-500 line-clamp-2 mt-0.5">{{ act.description }}</p>
                                            <div class="flex items-center gap-2 mt-1 text-[10px] text-gray-400">
                                                <span v-if="act.performer">{{ act.performer.name }}</span>
                                                <span>{{ formatDate(act.created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-gray-400 text-center py-6">{{ $t('a_no_activity_yet') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Drawer Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center gap-3">
                        <Link v-if="drawerLead" :href="`/admin/leads/${drawerLead.id}`"
                            class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-sm hover:shadow-md"
                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            {{ $t('a_view_full_details') }}
                        </Link>
                        <Link v-if="drawerLead && can('leads.update')" :href="`/admin/leads/${drawerLead.id}/edit`"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-300">
                            <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>{{ $t('a_edit') }}</Link>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style>
/* Drag and Drop styles */
.pipeline-ghost {
    opacity: 0.4;
    border: 2px dashed #C4A265 !important;
    background: #FDF8EF !important;
    border-radius: 12px;
}
.pipeline-chosen {
    box-shadow: 0 10px 40px rgba(196, 162, 101, 0.25) !important;
    transform: rotate(2deg);
    z-index: 50;
}
.pipeline-drag {
    opacity: 0.9;
    transform: rotate(3deg) scale(1.02);
}
</style>
