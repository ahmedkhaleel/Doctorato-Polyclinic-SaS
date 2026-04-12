<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import Sortable from 'sortablejs';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    columns: Object,
});

/* ── Local reactive columns for optimistic drag ─────────── */
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

/* ── Toast Notification ───────────────────────────────── */
const toast = ref({ show: false, message: '', type: 'success' });
let toastTimer = null;

function showToast(message, type = 'success') {
    if (toastTimer) clearTimeout(toastTimer);
    toast.value = { show: true, message, type };
    toastTimer = setTimeout(() => { toast.value.show = false; }, 3000);
}

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    nextTick(() => initDragAndDrop());
});

/* ── Status Config ──────────────────────────────────────── */
const statuses = ['new', 'contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation'];

const statusConfig = {
    new:               { en: 'New',          ar: 'جديد',         gradient: 'from-blue-500 to-blue-600',    bg: 'bg-blue-50/60',    border: 'border-blue-200', icon: 'M12 6v6m0 0v6m0-6h6m-6 0H6' },
    contacted:         { en: 'Contacted',    ar: 'تم التواصل',   gradient: 'from-indigo-500 to-indigo-600', bg: 'bg-indigo-50/60', border: 'border-indigo-200', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
    qualified:         { en: 'Qualified',    ar: 'مؤهل',         gradient: 'from-purple-500 to-purple-600', bg: 'bg-purple-50/60', border: 'border-purple-200', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    appointment_booked:{ en: 'Booked',       ar: 'تم الحجز',     gradient: 'from-amber-500 to-amber-600',  bg: 'bg-amber-50/60',  border: 'border-amber-200', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    consultation_done: { en: 'Consulted',    ar: 'تم الاستشارة',  gradient: 'from-teal-500 to-teal-600',    bg: 'bg-teal-50/60',   border: 'border-teal-200', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
    negotiation:       { en: 'Negotiation',  ar: 'تفاوض',        gradient: 'from-orange-500 to-orange-600', bg: 'bg-orange-50/60', border: 'border-orange-200', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
};

const priorityConfig = {
    1: { en: 'Hot',  ar: 'ساخن', dot: 'bg-red-500',   badge: 'bg-red-50 text-red-700 border-red-200',   borderL: 'border-s-red-500' },
    2: { en: 'Warm', ar: 'دافئ', dot: 'bg-amber-400', badge: 'bg-amber-50 text-amber-700 border-amber-200', borderL: 'border-s-amber-400' },
    3: { en: 'Cold', ar: 'بارد', dot: 'bg-blue-400',  badge: 'bg-blue-50 text-blue-700 border-blue-200',   borderL: 'border-s-blue-400' },
};

const totalLeads = computed(() => {
    return Object.values(localColumns.value || {}).reduce((sum, leads) => sum + (leads?.length || 0), 0);
});

/* ── Filter ─────────────────────────────────────────────── */
const filterPriority = ref('');
const searchQuery = ref('');

function filteredLeads(leads) {
    if (!leads) return [];
    let result = leads;
    if (filterPriority.value) {
        result = result.filter(l => l.priority == filterPriority.value);
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.trim().toLowerCase();
        result = result.filter(l =>
            (l.full_name || '').toLowerCase().includes(q) ||
            (l.phone || '').includes(q)
        );
    }
    return result;
}

/* ── Stale-in-stage helper ─────────────────────────────── */
function daysInStage(lead) {
    if (!lead.status_changed_at && !lead.created_at) return 0;
    const ref = lead.status_changed_at || lead.created_at;
    return Math.floor((Date.now() - new Date(ref).getTime()) / 86400000);
}

function isStale(lead) {
    return daysInStage(lead) >= 7;
}

/* ── Drag and Drop ──────────────────────────────────────── */
const sortableInstances = [];

function initDragAndDrop() {
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

                // Optimistic update
                const lead = localColumns.value[fromStatus]?.find(l => l.id == leadId);
                if (lead) {
                    localColumns.value[fromStatus] = localColumns.value[fromStatus].filter(l => l.id != leadId);
                    if (!localColumns.value[toStatus]) localColumns.value[toStatus] = [];
                    localColumns.value[toStatus].splice(evt.newIndex, 0, lead);
                }

                // Send to server (POST because ModSecurity blocks PATCH)
                const toLabel = isRtl.value ? statusConfig[toStatus]?.ar : statusConfig[toStatus]?.en;
                router.post(`/secretary/crm/leads/${leadId}/status`, { status: toStatus }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        showToast(
                            isRtl.value
                                ? `تم نقل العميل إلى "${toLabel}" بنجاح`
                                : `Lead moved to "${toLabel}" successfully`,
                            'success'
                        );
                    },
                    onError: () => {
                        // Revert
                        if (lead) {
                            localColumns.value[toStatus] = localColumns.value[toStatus].filter(l => l.id != leadId);
                            localColumns.value[fromStatus].push(lead);
                        }
                        showToast(
                            isRtl.value
                                ? 'حدث خطأ أثناء نقل العميل. تم التراجع.'
                                : 'Error moving lead. Changes reverted.',
                            'error'
                        );
                    },
                });
            },
        });
        sortableInstances.push(instance);
    });
}

watch(filterPriority, () => { nextTick(() => initDragAndDrop()); });

onBeforeUnmount(() => {
    sortableInstances.forEach(s => s.destroy());
});

/* ── Helpers ────────────────────────────────────────────── */
function timeAgo(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 3600) return Math.floor(diff / 60) + (isRtl.value ? ' د' : 'm');
    if (diff < 86400) return Math.floor(diff / 3600) + (isRtl.value ? ' س' : 'h');
    return Math.floor(diff / 86400) + (isRtl.value ? ' ي' : 'd');
}
</script>

<template>
<SecretaryLayout :title="isRtl ? 'خط الأنابيب' : 'Pipeline'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/20 to-slate-50 p-4 md:p-5" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- Header -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="h-1 bg-gradient-to-r from-teal-500 via-emerald-400 to-teal-500"></div>
        <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ isRtl ? 'خط الأنابيب' : 'Lead Pipeline' }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ totalLeads }} {{ isRtl ? 'عميل' : 'leads' }} — {{ isRtl ? 'اسحب لتغيير الحالة' : 'Drag to change status' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Search -->
                <div class="relative">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute top-1/2 -translate-y-1/2 start-2.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input v-model="searchQuery" type="text"
                        :placeholder="isRtl ? 'بحث...' : 'Search...'"
                        class="text-xs rounded-lg border border-gray-200 bg-white ps-8 pe-3 py-2 text-gray-600 outline-none focus:ring-2 focus:ring-teal-200 transition-all w-36 focus:w-48 placeholder:text-gray-400">
                </div>
                <!-- Priority filter -->
                <select v-model="filterPriority" class="text-xs rounded-lg border border-gray-200 bg-white px-3 py-2 text-gray-600 outline-none focus:ring-2 focus:ring-teal-200 transition-all">
                    <option value="">{{ isRtl ? 'كل الأولويات' : 'All Priorities' }}</option>
                    <option value="1">{{ isRtl ? 'ساخن' : 'Hot' }}</option>
                    <option value="2">{{ isRtl ? 'دافئ' : 'Warm' }}</option>
                    <option value="3">{{ isRtl ? 'بارد' : 'Cold' }}</option>
                </select>
                <!-- List view -->
                <Link href="/secretary/crm/leads" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-600 hover:bg-gray-50 transition-all">
                    <svg class="w-3.5 h-3.5 me-1.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    {{ isRtl ? 'قائمة' : 'List' }}
                </Link>
                <!-- New lead -->
                <Link href="/secretary/crm/leads/create" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-teal-500 text-white text-xs font-semibold hover:bg-teal-600 shadow-sm transition-all">
                    <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ isRtl ? 'عميل جديد' : 'New Lead' }}
                </Link>
            </div>
        </div>
    </div>

    <!-- Pipeline Columns -->
    <div :class="['flex gap-4 overflow-x-auto pb-4 transition-all duration-700 delay-150', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">

        <div v-for="status in statuses" :key="status" class="flex-shrink-0 w-[280px]">
            <!-- Column header -->
            <div :class="['rounded-t-xl bg-gradient-to-r p-3 flex items-center justify-between', statusConfig[status].gradient]">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="statusConfig[status].icon"/></svg>
                    <span class="text-sm font-semibold text-white">{{ isRtl ? statusConfig[status].ar : statusConfig[status].en }}</span>
                </div>
                <span class="bg-white/20 backdrop-blur text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
                    {{ filteredLeads(localColumns[status]).length }}
                </span>
            </div>

            <!-- Column body / drop zone -->
            <div :data-status="status"
                 :class="['min-h-[400px] rounded-b-xl border-x border-b p-2 space-y-2', statusConfig[status].bg, statusConfig[status].border]">

                <!-- Lead cards -->
                <div v-for="lead in filteredLeads(localColumns[status])" :key="lead.id"
                     :data-lead-id="lead.id"
                     :class="['bg-white rounded-xl border shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer group border-s-[3px]',
                        priorityConfig[lead.priority]?.borderL || 'border-s-gray-300']">

                    <!-- Drag handle -->
                    <div class="drag-handle px-3 pt-2.5 pb-1 flex items-center justify-between cursor-grab active:cursor-grabbing">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><circle cx="8" cy="6" r="1.5"/><circle cx="16" cy="6" r="1.5"/><circle cx="8" cy="12" r="1.5"/><circle cx="16" cy="12" r="1.5"/><circle cx="8" cy="18" r="1.5"/><circle cx="16" cy="18" r="1.5"/></svg>
                            <Link :href="`/secretary/crm/leads/${lead.id}`" class="text-sm font-semibold text-gray-800 truncate hover:text-teal-600 transition-colors" @click.stop>
                                {{ lead.full_name }}
                            </Link>
                        </div>
                        <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full border', priorityConfig[lead.priority]?.badge || 'bg-gray-50 text-gray-500 border-gray-200']">
                            {{ isRtl ? priorityConfig[lead.priority]?.ar : priorityConfig[lead.priority]?.en }}
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="px-3 pb-2.5">
                        <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-1.5">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span dir="ltr">{{ lead.phone }}</span>
                        </div>

                        <!-- Source + Score -->
                        <div class="flex items-center justify-between">
                            <span v-if="lead.source" class="text-[10px] text-gray-400 truncate max-w-[120px]">
                                {{ isRtl ? lead.source?.name_ar : lead.source?.name_en }}
                            </span>
                            <span v-else class="text-[10px] text-gray-300">-</span>
                            <div v-if="lead.score > 0" class="flex items-center gap-0.5">
                                <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="text-[10px] font-bold text-amber-600">{{ lead.score }}</span>
                            </div>
                        </div>

                        <!-- Stale warning -->
                        <div v-if="isStale(lead)" class="mt-1.5 flex items-center gap-1 text-[10px] text-amber-600 bg-amber-50 rounded-md px-2 py-1 border border-amber-200/60">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            {{ daysInStage(lead) }}{{ isRtl ? ' يوم في هذه المرحلة' : 'd in stage' }}
                        </div>

                        <!-- Footer: last contact + next follow-up -->
                        <div class="mt-2 pt-2 border-t border-gray-50 flex items-center justify-between">
                            <div v-if="lead.last_contacted_at" class="flex items-center gap-1 text-[10px] text-gray-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ timeAgo(lead.last_contacted_at) }}
                            </div>
                            <div v-else class="text-[10px] text-gray-300">{{ isRtl ? 'لم يتم التواصل' : 'No contact' }}</div>

                            <div v-if="lead.next_follow_up_at" class="flex items-center gap-1 text-[10px]"
                                 :class="new Date(lead.next_follow_up_at) < new Date() ? 'text-red-500 font-semibold' : 'text-teal-500'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ new Date(lead.next_follow_up_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredLeads(localColumns[status]).length === 0"
                     class="flex flex-col items-center justify-center py-8 text-gray-300">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <p class="text-xs">{{ isRtl ? 'لا يوجد عملاء' : 'No leads' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-400 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-2 scale-95"
        >
            <div
                v-if="toast.show"
                class="fixed top-6 left-1/2 -translate-x-1/2 z-[9999] flex items-center gap-3 px-5 py-3 rounded-xl shadow-2xl border backdrop-blur-sm"
                :class="toast.type === 'success'
                    ? 'bg-white/95 border-teal-200 text-teal-800'
                    : 'bg-white/95 border-red-200 text-red-800'"
            >
                <div
                    class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                    :class="toast.type === 'success' ? 'bg-teal-100' : 'bg-red-100'"
                >
                    <svg v-if="toast.type === 'success'" class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <svg v-else class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <span class="text-sm font-medium">{{ toast.message }}</span>
                <button @click="toast.show = false" class="ms-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </Transition>
    </Teleport>

</div>
</SecretaryLayout>
</template>

<style>
.pipeline-ghost {
    opacity: 0.4;
    background: #f0fdfa !important;
    border: 2px dashed #14b8a6 !important;
    border-radius: 12px !important;
}
.pipeline-chosen {
    box-shadow: 0 10px 40px -5px rgba(0,0,0,0.15) !important;
    transform: rotate(1.5deg) !important;
}
.pipeline-drag {
    opacity: 0.9;
    transform: rotate(2deg);
}
</style>
