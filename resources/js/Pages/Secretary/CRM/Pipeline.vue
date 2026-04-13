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

/* ── Collapse/Expand stages ────────────────────────────── */
const savedCollapsed = (() => {
    try { return JSON.parse(localStorage.getItem('crm_pipeline_collapsed') || '{}'); } catch { return {}; }
})();
const collapsedStages = ref(savedCollapsed);

function toggleCollapse(status) {
    collapsedStages.value = { ...collapsedStages.value, [status]: !collapsedStages.value[status] };
    try { localStorage.setItem('crm_pipeline_collapsed', JSON.stringify(collapsedStages.value)); } catch {}
}

function isCollapsed(status) {
    return !!collapsedStages.value[status];
}

/* ── Hover preview ─────────────────────────────────────── */
const hoveredLead = ref(null);
const hoverPos = ref({ x: 0, y: 0 });
let hoverTimeout = null;

function onCardEnter(lead, event) {
    clearTimeout(hoverTimeout);
    hoverTimeout = setTimeout(() => {
        hoveredLead.value = lead;
        const rect = event.currentTarget.getBoundingClientRect();
        hoverPos.value = { x: rect.left + rect.width / 2, y: rect.top };
    }, 400);
}

function onCardLeave() {
    clearTimeout(hoverTimeout);
    hoveredLead.value = null;
}

/* ── Stage conversion rates ────────────────────────────── */
const stageConversions = computed(() => {
    const results = [];
    for (let i = 0; i < statuses.length; i++) {
        const current = (localColumns.value[statuses[i]] || []).length;
        const next = i < statuses.length - 1 ? (localColumns.value[statuses[i + 1]] || []).length : 0;
        const rate = current > 0 && i < statuses.length - 1 ? Math.round((next / current) * 100) : null;
        results.push({ status: statuses[i], count: current, rate });
    }
    return results;
});

const maxStageCount = computed(() => Math.max(...stageConversions.value.map(s => s.count), 1));

/* ── Column summary stats ─────────────────────────────── */
const columnStats = computed(() => {
    const stats = {};
    for (const status of statuses) {
        const leads = localColumns.value[status] || [];
        const count = leads.length;
        const totalScore = leads.reduce((sum, l) => sum + (l.score || 0), 0);
        const avgScore = count > 0 ? Math.round(totalScore / count) : 0;
        const totalDays = leads.reduce((sum, l) => {
            const ref = l.status_changed_at || l.created_at;
            return sum + (ref ? Math.floor((Date.now() - new Date(ref).getTime()) / 86400000) : 0);
        }, 0);
        const avgDays = count > 0 ? Math.round(totalDays / count) : 0;
        const overdueCount = leads.filter(l => l.next_follow_up_at && new Date(l.next_follow_up_at) < new Date()).length;
        stats[status] = { avgScore, avgDays, overdueCount };
    }
    return stats;
});

/* ── Pipeline-wide priority breakdown ─────────────────── */
const priorityBreakdown = computed(() => {
    const all = Object.values(localColumns.value || {}).flat();
    const total = all.length || 1;
    const hot = all.filter(l => l.priority == 1).length;
    const warm = all.filter(l => l.priority == 2).length;
    const cold = all.filter(l => l.priority == 3).length;
    const none = all.length - hot - warm - cold;
    return {
        hot, warm, cold, none, total: all.length,
        hotPct: Math.round((hot / total) * 100),
        warmPct: Math.round((warm / total) * 100),
        coldPct: Math.round((cold / total) * 100),
        nonePct: Math.round((none / total) * 100),
    };
});

/* ── Quick move to next stage ─────────────────────────── */
function moveToNextStage(lead, currentStatus) {
    const idx = statuses.indexOf(currentStatus);
    if (idx < 0 || idx >= statuses.length - 1) return;
    const toStatus = statuses[idx + 1];

    // Optimistic update
    localColumns.value[currentStatus] = (localColumns.value[currentStatus] || []).filter(l => l.id != lead.id);
    if (!localColumns.value[toStatus]) localColumns.value[toStatus] = [];
    localColumns.value[toStatus].push(lead);

    const toLabel = isRtl.value ? statusConfig[toStatus]?.ar : statusConfig[toStatus]?.en;
    router.post(`/secretary/crm/leads/${lead.id}/status`, { status: toStatus }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showToast(
                isRtl.value
                    ? `تم نقل العميل إلى "${toLabel}" بنجاح`
                    : `Lead moved to "${toLabel}" successfully`,
                'success'
            );
            nextTick(() => initDragAndDrop());
        },
        onError: () => {
            localColumns.value[toStatus] = (localColumns.value[toStatus] || []).filter(l => l.id != lead.id);
            if (!localColumns.value[currentStatus]) localColumns.value[currentStatus] = [];
            localColumns.value[currentStatus].push(lead);
            showToast(isRtl.value ? 'حدث خطأ. تم التراجع.' : 'Error. Changes reverted.', 'error');
            nextTick(() => initDragAndDrop());
        },
    });
}

/* ── Helpers ────────────────────────────────────────────── */
function timeAgo(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 3600) return Math.floor(diff / 60) + (isRtl.value ? ' د' : 'm');
    if (diff < 86400) return Math.floor(diff / 3600) + (isRtl.value ? ' س' : 'h');
    return Math.floor(diff / 86400) + (isRtl.value ? ' ي' : 'd');
}

function formatFullDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

/* ── Compact view toggle ─────────────────────────────── */
const savedCompactView = (() => { try { return localStorage.getItem('crm_pipeline_compact') === '1'; } catch { return false; } })();
const compactView = ref(savedCompactView);

function toggleCompactView() {
    compactView.value = !compactView.value;
    try { localStorage.setItem('crm_pipeline_compact', compactView.value ? '1' : '0'); } catch {}
}

/* ── Keyboard shortcuts ──────────────────────────────── */
function handlePipelineKey(e) {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
    if (e.key === 'n' || e.key === 'N' || e.key === '\u0646') {
        e.preventDefault(); router.get('/secretary/crm/leads/create');
    }
    if (e.key === 'l' || e.key === 'L' || e.key === '\u0644') {
        e.preventDefault(); router.get('/secretary/crm/leads');
    }
    if (e.key === 'c' || e.key === 'C' || e.key === '\u0634') {
        e.preventDefault(); router.get('/secretary/crm/calendar');
    }
    if (e.key === 'd' || e.key === 'D' || e.key === '\u062F') {
        e.preventDefault(); router.get('/secretary/crm');
    }
    if (e.key === '/' || e.key === '\u0637') {
        e.preventDefault();
        document.querySelector('[data-pipeline-search]')?.focus();
    }
}

onMounted(() => { document.addEventListener('keydown', handlePipelineKey); });
onBeforeUnmount(() => { document.removeEventListener('keydown', handlePipelineKey); });
</script>

<template>
<SecretaryLayout :title="isRtl ? 'خط الأنابيب' : 'Pipeline'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/20 to-slate-50 p-4 md:p-5" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- Header -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="h-1 bg-gradient-to-r from-teal-500 via-emerald-400 to-teal-500"></div>
        <div class="px-3 sm:px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ isRtl ? 'خط الأنابيب' : 'Lead Pipeline' }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ totalLeads }} {{ isRtl ? 'عميل' : 'leads' }} — {{ isRtl ? 'اسحب لتغيير الحالة' : 'Drag to change status' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Search -->
                <div class="relative">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute top-1/2 -translate-y-1/2 start-2.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input v-model="searchQuery" type="text" data-pipeline-search
                        :placeholder="isRtl ? 'بحث...' : 'Search...'"
                        class="text-xs rounded-lg border border-gray-200 bg-white ps-8 pe-3 py-2 text-gray-600 outline-none focus:ring-2 focus:ring-teal-200 transition-all w-36 focus:w-48 placeholder:text-gray-400">
                    <kbd v-if="!searchQuery" class="absolute top-1/2 -translate-y-1/2 end-2 px-1 py-0.5 text-[9px] font-mono font-semibold text-gray-300 bg-gray-50 border border-gray-200 rounded pointer-events-none hidden md:inline">/</kbd>
                </div>
                <!-- Priority filter -->
                <select v-model="filterPriority" class="text-xs rounded-lg border border-gray-200 bg-white px-3 py-2 text-gray-600 outline-none focus:ring-2 focus:ring-teal-200 transition-all">
                    <option value="">{{ isRtl ? 'كل الأولويات' : 'All Priorities' }}</option>
                    <option value="1">{{ isRtl ? 'ساخن' : 'Hot' }}</option>
                    <option value="2">{{ isRtl ? 'دافئ' : 'Warm' }}</option>
                    <option value="3">{{ isRtl ? 'بارد' : 'Cold' }}</option>
                </select>
                <!-- Compact toggle -->
                <button @click="toggleCompactView"
                    :class="['p-2 rounded-lg border text-xs transition-all duration-200',
                        compactView ? 'bg-teal-50 border-teal-200 text-teal-600' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50']"
                    :title="isRtl ? (compactView ? 'عرض عادي' : 'عرض مضغوط') : (compactView ? 'Normal view' : 'Compact view')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </button>
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

    <!-- Stage Conversion Funnel Bar -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <span class="text-xs font-semibold text-gray-700">{{ isRtl ? '\u0645\u0639\u062F\u0644\u0627\u062A \u0627\u0644\u062A\u062D\u0648\u064A\u0644 \u0628\u064A\u0646 \u0627\u0644\u0645\u0631\u0627\u062D\u0644' : 'Stage-to-Stage Conversion' }}</span>
            </div>
            <div class="flex items-center gap-3 text-[11px]">
                <span class="flex items-center gap-1 text-gray-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    {{ isRtl ? 'أول←آخر:' : 'Start→End:' }}
                    <span :class="['font-bold', stageConversions.length > 1 && stageConversions[0].count > 0 ? (Math.round(stageConversions[stageConversions.length - 1].count / stageConversions[0].count * 100) >= 30 ? 'text-emerald-600' : 'text-amber-600') : 'text-gray-400']">
                        {{ stageConversions.length > 1 && stageConversions[0].count > 0 ? Math.round(stageConversions[stageConversions.length - 1].count / stageConversions[0].count * 100) : 0 }}%
                    </span>
                </span>
                <span class="flex items-center gap-1 text-gray-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ isRtl ? 'متأخر:' : 'Overdue:' }}
                    <span :class="['font-bold', Object.values(columnStats).reduce((s, c) => s + c.overdueCount, 0) > 0 ? 'text-red-600' : 'text-gray-400']">
                        {{ Object.values(columnStats).reduce((s, c) => s + c.overdueCount, 0) }}
                    </span>
                </span>
            </div>
        </div>
        <div class="flex items-end gap-1">
            <template v-for="(sc, idx) in stageConversions" :key="sc.status">
                <!-- Stage bar -->
                <div class="flex-1 text-center group">
                    <div class="relative mx-auto" style="max-width: 80px">
                        <div class="bg-slate-100 rounded-t-lg overflow-hidden" style="height: 48px">
                            <div class="w-full rounded-t-lg transition-all duration-1000 ease-out absolute bottom-0"
                                 :class="['bg-gradient-to-t', statusConfig[sc.status].gradient]"
                                 :style="{ height: mounted ? Math.max((sc.count / maxStageCount) * 100, 8) + '%' : '0%' }"></div>
                        </div>
                        <div class="text-[11px] font-bold text-gray-800 mt-1">{{ sc.count }}</div>
                        <div class="text-[9px] text-gray-400 truncate px-0.5">{{ isRtl ? statusConfig[sc.status].ar : statusConfig[sc.status].en }}</div>
                    </div>
                </div>
                <!-- Arrow with conversion rate -->
                <div v-if="idx < stageConversions.length - 1" class="flex-shrink-0 flex flex-col items-center justify-end pb-5 w-8">
                    <span v-if="sc.rate !== null" :class="['text-[9px] font-bold mb-0.5 tabular-nums', sc.rate >= 50 ? 'text-emerald-600' : sc.rate >= 25 ? 'text-amber-600' : 'text-red-500']">{{ sc.rate }}%</span>
                    <svg class="w-4 h-4 text-gray-300" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </div>
            </template>
        </div>
    </div>

    <!-- Priority Breakdown Bar -->
    <div v-if="priorityBreakdown.total > 0"
         :class="['bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-2.5 mb-4 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '130ms' }">
        <div class="flex items-center gap-4">
            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider flex-shrink-0">{{ isRtl ? 'الأولويات' : 'Priorities' }}</span>
            <!-- Stacked bar -->
            <div class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden flex">
                <div v-if="priorityBreakdown.hot" class="h-full bg-gradient-to-r from-red-500 to-rose-400 transition-all duration-1000 ease-out" :style="{ width: mounted ? priorityBreakdown.hotPct + '%' : '0%' }"></div>
                <div v-if="priorityBreakdown.warm" class="h-full bg-gradient-to-r from-amber-400 to-yellow-300 transition-all duration-1000 ease-out" :style="{ width: mounted ? priorityBreakdown.warmPct + '%' : '0%', transitionDelay: '100ms' }"></div>
                <div v-if="priorityBreakdown.cold" class="h-full bg-gradient-to-r from-blue-400 to-sky-300 transition-all duration-1000 ease-out" :style="{ width: mounted ? priorityBreakdown.coldPct + '%' : '0%', transitionDelay: '200ms' }"></div>
                <div v-if="priorityBreakdown.none" class="h-full bg-gray-200 transition-all duration-1000 ease-out" :style="{ width: mounted ? priorityBreakdown.nonePct + '%' : '0%', transitionDelay: '300ms' }"></div>
            </div>
            <!-- Legend -->
            <div class="flex items-center gap-3 flex-shrink-0">
                <button v-if="priorityBreakdown.hot" @click="filterPriority = filterPriority === '1' ? '' : '1'"
                    class="flex items-center gap-1 text-[10px] font-semibold transition-all px-1.5 py-0.5 rounded-md"
                    :class="filterPriority === '1' ? 'bg-red-100 text-red-700 ring-1 ring-red-300' : 'text-gray-500 hover:text-red-600'">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    {{ priorityBreakdown.hot }}
                </button>
                <button v-if="priorityBreakdown.warm" @click="filterPriority = filterPriority === '2' ? '' : '2'"
                    class="flex items-center gap-1 text-[10px] font-semibold transition-all px-1.5 py-0.5 rounded-md"
                    :class="filterPriority === '2' ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-300' : 'text-gray-500 hover:text-amber-600'">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    {{ priorityBreakdown.warm }}
                </button>
                <button v-if="priorityBreakdown.cold" @click="filterPriority = filterPriority === '3' ? '' : '3'"
                    class="flex items-center gap-1 text-[10px] font-semibold transition-all px-1.5 py-0.5 rounded-md"
                    :class="filterPriority === '3' ? 'bg-blue-100 text-blue-700 ring-1 ring-blue-300' : 'text-gray-500 hover:text-blue-600'">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    {{ priorityBreakdown.cold }}
                </button>
            </div>
        </div>
    </div>

    <!-- Pipeline Columns -->
    <div :class="['flex gap-3 sm:gap-4 overflow-x-auto pb-4 scrollbar-none transition-all duration-700 delay-150', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">

        <div v-for="status in statuses" :key="status" :class="['flex-shrink-0 transition-all duration-300', isCollapsed(status) ? 'w-[52px]' : 'w-[280px]']">
            <!-- Column header -->
            <div :class="['rounded-t-xl bg-gradient-to-r p-3 flex items-center justify-between cursor-pointer select-none', statusConfig[status].gradient]"
                 @click="toggleCollapse(status)">
                <div class="flex items-center gap-2 min-w-0">
                    <svg :class="['w-4 h-4 text-white/80 transition-transform duration-200 flex-shrink-0', isCollapsed(status) ? 'rotate-90' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="statusConfig[status].icon"/></svg>
                    <span v-if="!isCollapsed(status)" class="text-sm font-semibold text-white truncate">{{ isRtl ? statusConfig[status].ar : statusConfig[status].en }}</span>
                </div>
                <span class="bg-white/20 backdrop-blur text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0">
                    {{ filteredLeads(localColumns[status]).length }}
                </span>
            </div>

            <!-- Collapsed vertical label -->
            <div v-if="isCollapsed(status)"
                 :class="['rounded-b-xl border-x border-b min-h-[400px] flex items-center justify-center cursor-pointer', statusConfig[status].bg, statusConfig[status].border]"
                 @click="toggleCollapse(status)">
                <span class="text-xs font-semibold text-gray-400 writing-mode-vertical whitespace-nowrap" style="writing-mode: vertical-rl; text-orientation: mixed;">
                    {{ isRtl ? statusConfig[status].ar : statusConfig[status].en }}
                </span>
            </div>

            <!-- Column stats strip + age health bar -->
            <div v-if="!isCollapsed(status) && filteredLeads(localColumns[status]).length > 0"
                 :class="['border-x', statusConfig[status].bg, statusConfig[status].border]">
                <!-- Age health micro-bar -->
                <div class="h-1 w-full bg-gray-100 overflow-hidden">
                    <div class="h-full transition-all duration-700 ease-out"
                         :style="{ width: Math.min((columnStats[status].avgDays / 14) * 100, 100) + '%',
                                   background: columnStats[status].avgDays <= 3 ? '#10b981' : columnStats[status].avgDays <= 7 ? '#f59e0b' : '#ef4444' }"></div>
                </div>
                <div class="flex items-center justify-between px-3 py-1.5 text-[10px]">
                    <div class="flex items-center gap-0.5 text-gray-400" :title="isRtl ? '\u0645\u062A\u0648\u0633\u0637 \u0627\u0644\u0623\u064A\u0627\u0645' : 'Avg days'">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="font-semibold" :class="columnStats[status].avgDays > 7 ? 'text-red-500' : columnStats[status].avgDays > 3 ? 'text-amber-500' : 'text-emerald-600'">{{ columnStats[status].avgDays }}{{ isRtl ? '\u064A' : 'd' }}</span>
                    </div>
                    <div class="flex items-center gap-0.5 text-gray-400" :title="isRtl ? '\u0645\u062A\u0648\u0633\u0637 \u0627\u0644\u0646\u0642\u0627\u0637' : 'Avg score'">
                        <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span class="font-semibold" :class="columnStats[status].avgScore >= 70 ? 'text-teal-600' : columnStats[status].avgScore >= 40 ? 'text-amber-500' : 'text-gray-500'">{{ columnStats[status].avgScore }}</span>
                    </div>
                    <div v-if="columnStats[status].overdueCount > 0" class="flex items-center gap-0.5 text-red-500" :title="isRtl ? '\u0645\u062A\u0623\u062E\u0631\u0629' : 'Overdue'">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        <span class="font-bold">{{ columnStats[status].overdueCount }}</span>
                    </div>
                </div>
            </div>

            <!-- Column body / drop zone -->
            <div v-if="!isCollapsed(status)"
                 :data-status="status"
                 :class="['min-h-[400px] rounded-b-xl border-x border-b p-2 space-y-2', statusConfig[status].bg, statusConfig[status].border]">

                <!-- Lead cards -->
                <div v-for="lead in filteredLeads(localColumns[status])" :key="lead.id"
                     :data-lead-id="lead.id"
                     @mouseenter="onCardEnter(lead, $event)"
                     @mouseleave="onCardLeave"
                     :class="['bg-white rounded-xl border shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer group border-s-[3px]',
                        priorityConfig[lead.priority]?.borderL || 'border-s-gray-300',
                        isStale(lead) ? 'ring-1 ring-amber-300/60 bg-amber-50/30' : '']">

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
                    <div :class="['px-3', compactView ? 'pb-1.5' : 'pb-2.5']">
                        <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-1.5">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span dir="ltr">{{ lead.phone }}</span>
                            <!-- Compact: inline score -->
                            <span v-if="compactView && lead.score > 0" class="ms-auto text-[10px] font-bold text-amber-600 flex items-center gap-0.5">
                                <svg class="w-2.5 h-2.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ lead.score }}
                            </span>
                        </div>

                        <!-- Source + Score (hidden in compact) -->
                        <div v-if="!compactView" class="flex items-center justify-between">
                            <span v-if="lead.source" class="text-[10px] text-gray-400 truncate max-w-[120px]">
                                {{ isRtl ? lead.source?.name_ar : lead.source?.name_en }}
                            </span>
                            <span v-else class="text-[10px] text-gray-300">-</span>
                            <div v-if="lead.score > 0" class="flex items-center gap-0.5">
                                <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="text-[10px] font-bold text-amber-600">{{ lead.score }}</span>
                            </div>
                        </div>

                        <!-- Stale warning (hidden in compact) -->
                        <div v-if="!compactView && isStale(lead)" class="mt-1.5 flex items-center gap-1 text-[10px] text-amber-600 bg-amber-50 rounded-md px-2 py-1 border border-amber-200/60">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            {{ daysInStage(lead) }}{{ isRtl ? ' يوم في هذه المرحلة' : 'd in stage' }}
                        </div>

                        <!-- Footer: last contact + next follow-up -->
                        <div :class="['flex items-center justify-between', compactView ? 'mt-1' : 'mt-2 pt-2 border-t border-gray-50']">
                            <div v-if="lead.last_contacted_at" class="flex items-center gap-1 text-[10px] text-gray-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ timeAgo(lead.last_contacted_at) }}
                            </div>
                            <div v-else class="text-[10px] text-gray-300">{{ isRtl ? '\u0644\u0645 \u064A\u062A\u0645 \u0627\u0644\u062A\u0648\u0627\u0635\u0644' : 'No contact' }}</div>

                            <div v-if="lead.next_follow_up_at" class="flex items-center gap-1 text-[10px]"
                                 :class="new Date(lead.next_follow_up_at) < new Date() ? 'text-red-500 font-semibold' : 'text-teal-500'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ new Date(lead.next_follow_up_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}
                            </div>
                        </div>

                        <!-- Quick action buttons (visible on hover, hidden in compact) -->
                        <div v-if="!compactView" class="mt-2 pt-2 border-t border-gray-50 flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-200">
                            <!-- Quick move to next stage -->
                            <button v-if="statuses.indexOf(status) < statuses.length - 1"
                                @click.stop="moveToNextStage(lead, status)"
                                class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 rounded-lg text-[10px] font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200/60 transition-colors"
                                :title="isRtl ? ('نقل إلى ' + statusConfig[statuses[statuses.indexOf(status) + 1]]?.ar) : ('Move to ' + statusConfig[statuses[statuses.indexOf(status) + 1]]?.en)">
                                <svg class="w-3 h-3" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                {{ isRtl ? statusConfig[statuses[statuses.indexOf(status) + 1]]?.ar : statusConfig[statuses[statuses.indexOf(status) + 1]]?.en }}
                            </button>
                            <a :href="`tel:${lead.phone}`" @click.stop
                               class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 rounded-lg text-[10px] font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200/60 transition-colors"
                               :title="isRtl ? '\u0627\u062A\u0635\u0627\u0644' : 'Call'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ isRtl ? '\u0627\u062A\u0635\u0627\u0644' : 'Call' }}
                            </a>
                            <a :href="`https://wa.me/${(lead.phone || '').replace(/[^0-9+]/g, '')}`" target="_blank" @click.stop
                               class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 rounded-lg text-[10px] font-semibold bg-green-50 text-green-700 hover:bg-green-100 border border-green-200/60 transition-colors"
                               :title="isRtl ? '\u0648\u0627\u062A\u0633\u0627\u0628' : 'WhatsApp'">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                <span class="hidden sm:inline">WA</span>
                            </a>
                            <Link :href="`/secretary/crm/leads/${lead.id}`" @click.stop
                                class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 rounded-lg text-[10px] font-semibold bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200/60 transition-colors"
                                :title="isRtl ? '\u0639\u0631\u0636' : 'View'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                {{ isRtl ? '\u0639\u0631\u0636' : 'View' }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredLeads(localColumns[status]).length === 0"
                     class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="w-14 h-14 rounded-2xl mb-3 flex items-center justify-center border-2 border-dashed"
                         :class="statusConfig[status].border" style="opacity: 0.4;">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <p class="text-xs font-medium text-gray-400 mb-1">{{ isRtl ? 'لا يوجد عملاء' : 'No leads here' }}</p>
                    <p class="text-[10px] text-gray-300 mb-3 max-w-[180px]">
                        {{ isRtl ? 'اسحب عميلاً من مرحلة أخرى أو أنشئ عميلاً جديداً' : 'Drag a lead from another stage or create a new one' }}
                    </p>
                    <Link href="/secretary/crm/leads/create"
                          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-[10px] font-semibold text-teal-600 bg-teal-50 hover:bg-teal-100 border border-teal-200/60 transition-all duration-200">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ isRtl ? 'عميل جديد' : 'New Lead' }}
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- Hover Preview Card -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95">
            <div v-if="hoveredLead"
                 class="fixed z-[9998] w-72 bg-white rounded-xl shadow-2xl border border-gray-200 p-4 pointer-events-none"
                 :style="{ top: Math.max(8, hoverPos.y - 220) + 'px', left: Math.min(hoverPos.x - 144, window.innerWidth - 300) + 'px' }">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ hoveredLead.full_name ? hoveredLead.full_name.substring(0,1).toUpperCase() : '?' }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-800 truncate">{{ hoveredLead.full_name }}</p>
                        <p class="text-xs text-gray-400" dir="ltr">{{ hoveredLead.phone }}</p>
                    </div>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">{{ isRtl ? 'المصدر' : 'Source' }}</span>
                        <span class="font-medium text-gray-700">{{ (isRtl ? hoveredLead.source?.name_ar : hoveredLead.source?.name_en) || '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">{{ isRtl ? 'النقاط' : 'Score' }}</span>
                        <div class="flex items-center gap-1">
                            <svg v-if="hoveredLead.score > 0" class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <span class="font-medium text-gray-700">{{ hoveredLead.score || 0 }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">{{ isRtl ? 'آخر تواصل' : 'Last Contact' }}</span>
                        <span class="font-medium text-gray-700">{{ hoveredLead.last_contacted_at ? timeAgo(hoveredLead.last_contacted_at) : (isRtl ? 'لا يوجد' : 'None') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">{{ isRtl ? 'المتابعة القادمة' : 'Next Follow-up' }}</span>
                        <span class="font-medium" :class="hoveredLead.next_follow_up_at && new Date(hoveredLead.next_follow_up_at) < new Date() ? 'text-red-500' : 'text-gray-700'">
                            {{ hoveredLead.next_follow_up_at ? formatFullDate(hoveredLead.next_follow_up_at) : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">{{ isRtl ? 'في المرحلة' : 'In Stage' }}</span>
                        <span class="font-medium text-amber-600">{{ daysInStage(hoveredLead) }} {{ isRtl ? 'يوم' : 'days' }}</span>
                    </div>
                    <div v-if="hoveredLead.email" class="flex items-center justify-between">
                        <span class="text-gray-400">{{ isRtl ? 'البريد' : 'Email' }}</span>
                        <span class="font-medium text-gray-700 truncate max-w-[140px]">{{ hoveredLead.email }}</span>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-100 text-center">
                    <span class="text-[10px] text-gray-300">{{ isRtl ? 'انقر لعرض التفاصيل' : 'Click to view details' }}</span>
                </div>
            </div>
        </Transition>
    </Teleport>

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

    <!-- Mobile Bottom Action Bar -->
    <Teleport to="body">
        <div class="fixed bottom-0 left-0 right-0 z-40 md:hidden">
            <div class="bg-white/95 backdrop-blur-lg border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] px-2" style="padding-bottom: env(safe-area-inset-bottom, 0.5rem);">
                <div class="flex items-center justify-around py-2">
                    <Link href="/secretary/crm" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/></svg>
                        <span class="text-[10px] font-semibold">{{ isRtl ? 'لوحة' : 'Dashboard' }}</span>
                    </Link>
                    <div class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-teal-600 bg-teal-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                        <span class="text-[10px] font-bold">{{ isRtl ? 'أنابيب' : 'Pipeline' }}</span>
                    </div>
                    <Link href="/secretary/crm/leads/create" class="flex flex-col items-center gap-0.5 -mt-5">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-lg shadow-teal-500/30 ring-4 ring-white">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-teal-600">{{ isRtl ? 'جديد' : 'New' }}</span>
                    </Link>
                    <Link href="/secretary/crm/calendar" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        <span class="text-[10px] font-semibold">{{ isRtl ? 'التقويم' : 'Calendar' }}</span>
                    </Link>
                    <Link href="/secretary/crm/leads" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                        <span class="text-[10px] font-semibold">{{ isRtl ? 'العملاء' : 'Leads' }}</span>
                    </Link>
                </div>
            </div>
        </div>
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
