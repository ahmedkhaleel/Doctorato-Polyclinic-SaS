<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    leads: Object,
    filters: Object,
    sources: Array,
});

/* ── View / Animation State ── */
const savedViewMode = typeof localStorage !== 'undefined' ? localStorage.getItem('crm_leads_viewMode') : null;
const viewMode = ref(savedViewMode || 'grid');
const mounted = ref(false);
const inlineStatusOpen = ref(null);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    document.addEventListener('click', () => { inlineStatusOpen.value = null; showColumnMenu.value = false; });
});
onBeforeUnmount(() => { document.removeEventListener('click', () => {}); });

/* ── Filters ── */
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const priorityFilter = ref(props.filters?.priority || '');
const sourceFilter = ref(props.filters?.source || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const sortField = ref(props.filters?.sort || 'created_at');
const sortDir = ref(props.filters?.dir || 'desc');
const showAdvancedFilters = ref(false);

let searchTimeout = null;

function applyFilters() {
    router.get('/secretary/crm/leads', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        priority: priorityFilter.value || undefined,
        source: sourceFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        sort: sortField.value !== 'created_at' ? sortField.value : undefined,
        dir: sortDir.value !== 'desc' ? sortDir.value : undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch(statusFilter, applyFilters);
watch(priorityFilter, applyFilters);
watch(sourceFilter, applyFilters);
watch(dateFrom, applyFilters);
watch(dateTo, applyFilters);

function changeSort(field) {
    if (sortField.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDir.value = field === 'full_name' ? 'asc' : 'desc';
    }
    applyFilters();
}

function clearFilters() {
    search.value = '';
    statusFilter.value = '';
    priorityFilter.value = '';
    sourceFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    sortField.value = 'created_at';
    sortDir.value = 'desc';
    router.get('/secretary/crm/leads', {}, { preserveState: true, replace: true });
}

function removeFilter(key) {
    if (key === 'search') search.value = '';
    if (key === 'status') statusFilter.value = '';
    if (key === 'priority') priorityFilter.value = '';
    if (key === 'source') sourceFilter.value = '';
    if (key === 'date_from') dateFrom.value = '';
    if (key === 'date_to') dateTo.value = '';
    applyFilters();
}

const hasActiveFilters = computed(() => !!(search.value || statusFilter.value || priorityFilter.value || sourceFilter.value || dateFrom.value || dateTo.value));

const advancedFilterCount = computed(() => {
    let c = 0;
    if (sourceFilter.value) c++;
    if (dateFrom.value) c++;
    if (dateTo.value) c++;
    return c;
});

/* ── Status ── */
const statusLabels = computed(() => ({
    new: isRtl.value ? 'جديد' : 'New',
    contacted: isRtl.value ? 'تم التواصل' : 'Contacted',
    qualified: isRtl.value ? 'مؤهل' : 'Qualified',
    appointment_booked: isRtl.value ? 'تم الحجز' : 'Appt. Booked',
    consultation_done: isRtl.value ? 'تم الاستشارة' : 'Consultation Done',
    negotiation: isRtl.value ? 'تفاوض' : 'Negotiation',
    converted: isRtl.value ? 'محوّل' : 'Converted',
    lost: isRtl.value ? 'خسارة' : 'Lost',
    dormant: isRtl.value ? 'خامد' : 'Dormant',
}));

const statusColors = {
    new: { bg: '#dbeafe', text: '#1d4ed8', dot: '#3b82f6' },
    contacted: { bg: '#e0e7ff', text: '#4338ca', dot: '#6366f1' },
    qualified: { bg: '#f3e8ff', text: '#7e22ce', dot: '#a855f7' },
    appointment_booked: { bg: '#fef3c7', text: '#b45309', dot: '#f59e0b' },
    consultation_done: { bg: '#ccfbf1', text: '#0f766e', dot: '#14b8a6' },
    negotiation: { bg: '#ffedd5', text: '#c2410c', dot: '#f97316' },
    converted: { bg: '#dcfce7', text: '#15803d', dot: '#22c55e' },
    lost: { bg: '#fee2e2', text: '#b91c1c', dot: '#ef4444' },
    dormant: { bg: '#f3f4f6', text: '#6b7280', dot: '#9ca3af' },
};

/* ── Priority ── */
const priorityConfig = computed(() => ({
    1: { label: isRtl.value ? 'ساخن' : 'Hot', icon: 'M13 10V3L4 14h7v7l9-11h-7z', bg: '#fef2f2', text: '#dc2626', border: '#fecaca' },
    2: { label: isRtl.value ? 'دافئ' : 'Warm', icon: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z', bg: '#fffbeb', text: '#d97706', border: '#fde68a' },
    3: { label: isRtl.value ? 'بارد' : 'Cold', icon: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z', bg: '#eff6ff', text: '#2563eb', border: '#bfdbfe' },
}));

/* ── Helpers ── */
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function isOverdue(date) {
    if (!date) return false;
    return new Date(date) < new Date();
}

function timeAgo(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return isRtl.value ? 'الآن' : 'just now';
    if (diff < 3600) return isRtl.value ? `منذ ${Math.floor(diff / 60)} د` : `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return isRtl.value ? `منذ ${Math.floor(diff / 3600)} س` : `${Math.floor(diff / 3600)}h ago`;
    return isRtl.value ? `منذ ${Math.floor(diff / 86400)} ي` : `${Math.floor(diff / 86400)}d ago`;
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0].substring(0, 2).toUpperCase();
}

function getAvatarGradient(id) {
    const gradients = [
        'linear-gradient(135deg, #0d9488, #06b6d4)',
        'linear-gradient(135deg, #7c3aed, #a78bfa)',
        'linear-gradient(135deg, #db2777, #f472b6)',
        'linear-gradient(135deg, #ea580c, #fb923c)',
        'linear-gradient(135deg, #2563eb, #60a5fa)',
        'linear-gradient(135deg, #059669, #34d399)',
        'linear-gradient(135deg, #d97706, #fbbf24)',
        'linear-gradient(135deg, #dc2626, #f87171)',
    ];
    return gradients[(id || 0) % gradients.length];
}

function scoreColor(score) {
    if (score >= 70) return '#0d9488';
    if (score >= 40) return '#d97706';
    return '#9ca3af';
}

function scoreDash(score) {
    const circumference = 2 * Math.PI * 16;
    const offset = circumference - (Math.min(score || 0, 100) / 100) * circumference;
    return { circumference, offset };
}

function whatsappLink(phone) {
    if (!phone) return '#';
    const clean = phone.replace(/[^0-9+]/g, '');
    return `https://wa.me/${clean.replace('+', '')}`;
}

function phoneLink(phone) {
    if (!phone) return '#';
    return `tel:${phone}`;
}

/* ── Bulk Selection ── */
const selectedLeads = ref([]);
const selectAll = ref(false);

function toggleSelectAll() {
    if (selectAll.value) {
        selectedLeads.value = (props.leads?.data || []).map(l => l.id);
    } else {
        selectedLeads.value = [];
    }
}

function toggleLead(id) {
    const idx = selectedLeads.value.indexOf(id);
    if (idx > -1) {
        selectedLeads.value.splice(idx, 1);
    } else {
        selectedLeads.value.push(id);
    }
    selectAll.value = selectedLeads.value.length === (props.leads?.data || []).length;
}

function isSelected(id) {
    return selectedLeads.value.includes(id);
}

const showBulkActions = computed(() => selectedLeads.value.length > 0);

function bulkUpdateStatus(status) {
    if (!selectedLeads.value.length) return;
    if (!confirm(isRtl.value ? `تغيير حالة ${selectedLeads.value.length} عميل؟` : `Update status of ${selectedLeads.value.length} leads?`)) return;

    router.post('/secretary/crm/leads/bulk-status', {
        lead_ids: selectedLeads.value,
        status: status,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedLeads.value = [];
            selectAll.value = false;
        },
    });
}

function clearSelection() {
    selectedLeads.value = [];
    selectAll.value = false;
}

function bulkUpdatePriority(priority) {
    if (!selectedLeads.value.length) return;
    if (!confirm(isRtl.value ? `تغيير أولوية ${selectedLeads.value.length} عميل؟` : `Update priority of ${selectedLeads.value.length} leads?`)) return;
    router.post('/secretary/crm/leads/bulk-priority', {
        lead_ids: selectedLeads.value,
        priority: priority,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedLeads.value = [];
            selectAll.value = false;
        },
    });
}

/* ── Export CSV ── */
function exportLeadsCSV() {
    const allData = props.leads?.data || [];
    // If leads are selected, export only selected; otherwise export all
    const data = selectedLeads.value.length > 0
        ? allData.filter(l => selectedLeads.value.includes(l.id))
        : allData;
    if (!data.length) return;

    const headers = ['Name', 'Phone', 'Email', 'Status', 'Priority', 'Source', 'Score', 'City', 'Next Follow-up', 'Created'];
    let csv = headers.join(',') + '\n';

    data.forEach(l => {
        const row = [
            `"${(l.full_name || '').replace(/"/g, '""')}"`,
            `"${l.phone || ''}"`,
            `"${l.email || ''}"`,
            l.status || '',
            l.priority || '',
            `"${(l.source?.name_en || '').replace(/"/g, '""')}"`,
            l.score || 0,
            `"${(l.city || '').replace(/"/g, '""')}"`,
            l.next_follow_up_at || '',
            l.created_at ? new Date(l.created_at).toISOString().split('T')[0] : '',
        ];
        csv += row.join(',') + '\n';
    });

    const suffix = selectedLeads.value.length > 0 ? `-selected-${selectedLeads.value.length}` : '';
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `leads-export${suffix}-${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    URL.revokeObjectURL(url);
}

/* ── Status distribution mini-bar ── */
const statusDistribution = computed(() => {
    const allData = props.leads?.data || [];
    if (!allData.length) return [];
    const counts = {};
    allData.forEach(l => { counts[l.status] = (counts[l.status] || 0) + 1; });
    const total = allData.length;
    const order = ['new', 'contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation', 'converted', 'lost', 'dormant'];
    return order
        .filter(s => counts[s])
        .map(s => ({
            status: s,
            count: counts[s],
            pct: Math.round((counts[s] / total) * 100),
            label: statusLabels.value[s] || s,
            color: statusColors[s]?.dot || '#9ca3af',
            bg: statusColors[s]?.bg || '#f3f4f6',
        }));
});

/* ── Inline status change ── */
const inlineStatusOptions = ['new', 'contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation'];

function toggleInlineStatus(leadId) {
    inlineStatusOpen.value = inlineStatusOpen.value === leadId ? null : leadId;
}

function changeLeadStatus(leadId, newStatus) {
    inlineStatusOpen.value = null;
    router.post(`/secretary/crm/leads/${leadId}/status`, { status: newStatus }, {
        preserveScroll: true,
        preserveState: true,
    });
}

/* ── Column visibility ── */
const showColumnMenu = ref(false);
const visibleColumns = ref(JSON.parse(localStorage.getItem('crm_leads_columns') || '{}'));

const tableColumns = [
    { key: 'phone', en: 'Phone', ar: 'الهاتف', default: true },
    { key: 'email', en: 'Email', ar: 'البريد', default: false },
    { key: 'status', en: 'Status', ar: 'الحالة', default: true },
    { key: 'priority', en: 'Priority', ar: 'الأولوية', default: true },
    { key: 'source', en: 'Source', ar: 'المصدر', default: true },
    { key: 'score', en: 'Score', ar: 'النقاط', default: true },
    { key: 'city', en: 'City', ar: 'المدينة', default: false },
    { key: 'next_follow_up', en: 'Next Follow-up', ar: 'المتابعة القادمة', default: true },
    { key: 'created_at', en: 'Created', ar: 'تاريخ الإنشاء', default: false },
];

function isColumnVisible(key) {
    if (visibleColumns.value[key] !== undefined) return visibleColumns.value[key];
    return tableColumns.find(c => c.key === key)?.default ?? true;
}

function toggleColumn(key) {
    visibleColumns.value = { ...visibleColumns.value, [key]: !isColumnVisible(key) };
    localStorage.setItem('crm_leads_columns', JSON.stringify(visibleColumns.value));
}

/* ── Quick View ── */
const quickViewLead = ref(null);
const quickViewOpen = ref(false);
const quickViewLoading = ref(false);
const quickViewActivities = ref([]);
const quickViewFollowUps = ref([]);

async function openQuickView(lead) {
    quickViewLead.value = lead;
    quickViewOpen.value = true;
    quickViewLoading.value = true;
    quickViewActivities.value = [];
    quickViewFollowUps.value = [];

    try {
        const res = await fetch(`/secretary/crm/leads/${lead.id}/quick-view`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        quickViewActivities.value = data.activities || [];
        quickViewFollowUps.value = data.followUps || [];
    } catch (e) {
        console.error(e);
    } finally {
        quickViewLoading.value = false;
    }
}

function closeQuickView() {
    quickViewOpen.value = false;
    setTimeout(() => { quickViewLead.value = null; }, 300);
}

/* ── Quick view actions ── */
const quickViewNote = ref('');
const quickViewSavingNote = ref(false);
const quickViewStatusChanging = ref(false);

function quickViewChangeStatus(newStatus) {
    if (!quickViewLead.value || quickViewStatusChanging.value) return;
    quickViewStatusChanging.value = true;
    router.post(`/secretary/crm/leads/${quickViewLead.value.id}/status`, {
        status: newStatus,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (quickViewLead.value) quickViewLead.value.status = newStatus;
        },
        onFinish: () => { quickViewStatusChanging.value = false; },
    });
}

function quickViewAddNote() {
    if (!quickViewLead.value || !quickViewNote.value.trim() || quickViewSavingNote.value) return;
    quickViewSavingNote.value = true;
    router.post(`/secretary/crm/leads/${quickViewLead.value.id}/activity`, {
        type: 'note',
        description: quickViewNote.value.trim(),
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            quickViewNote.value = '';
            // Refresh quick view data
            openQuickView(quickViewLead.value);
        },
        onFinish: () => { quickViewSavingNote.value = false; },
    });
}

/* ── Scroll-to-top button ── */
const showScrollTop = ref(false);
function handleScrollTop() {
    showScrollTop.value = window.scrollY > 400;
}
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
onMounted(() => { window.addEventListener('scroll', handleScrollTop, { passive: true }); });
onUnmounted(() => { window.removeEventListener('scroll', handleScrollTop); });

/* ── Persist view mode ── */
watch(viewMode, (val) => {
    try { localStorage.setItem('crm_leads_viewMode', val); } catch {}
});

/* ── Keyboard shortcuts ── */
function handleKeyboard(e) {
    // Don't fire if user is typing in an input
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
    if (e.key === '/' || e.key === '\u0637') {
        e.preventDefault();
        document.querySelector('[data-search-input]')?.focus();
    }
    if (e.key === 'n' || e.key === 'N' || e.key === '\u0646') {
        e.preventDefault();
        router.get('/secretary/crm/leads/create');
    }
    if (e.key === 'Escape') {
        closeQuickView();
        inlineStatusOpen.value = null;
        showColumnMenu.value = false;
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeyboard);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyboard);
});

/* ── Leads summary stats ── */
const leadsSummary = computed(() => {
    const data = props.leads?.data || [];
    const total = props.leads?.total || data.length;
    const hotCount = data.filter(l => l.priority == 1 || l.priority === 'hot').length;
    const overdueCount = data.filter(l => l.next_follow_up_at && new Date(l.next_follow_up_at) < new Date()).length;
    const highScore = data.filter(l => (l.score || 0) >= 70).length;
    return { total, hotCount, overdueCount, highScore, shown: data.length };
});

/* ── Active filter pills ── */
const activeFilterPills = computed(() => {
    const pills = [];
    if (search.value) {
        pills.push({ key: 'search', label: isRtl.value ? `بحث: "${search.value}"` : `Search: "${search.value}"` });
    }
    if (statusFilter.value) {
        pills.push({ key: 'status', label: statusLabels.value[statusFilter.value] || statusFilter.value });
    }
    if (priorityFilter.value) {
        const p = priorityConfig.value[priorityFilter.value];
        pills.push({ key: 'priority', label: p ? p.label : priorityFilter.value });
    }
    if (sourceFilter.value) {
        const s = (props.sources || []).find(s => s.id == sourceFilter.value);
        pills.push({ key: 'source', label: s ? (isRtl.value ? s.name_ar : s.name_en) : sourceFilter.value });
    }
    if (dateFrom.value) {
        pills.push({ key: 'date_from', label: (isRtl.value ? 'من: ' : 'From: ') + dateFrom.value });
    }
    if (dateTo.value) {
        pills.push({ key: 'date_to', label: (isRtl.value ? 'إلى: ' : 'To: ') + dateTo.value });
    }
    return pills;
});
</script>

<template>
    <SecretaryLayout :title="isRtl ? 'العملاء المحتملين' : 'Leads'">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- ═══════════════ HEADER ═══════════════ -->
            <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8"
                 style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%);">
                <!-- Decorative circles -->
                <div class="absolute -top-10 ltr:-right-10 rtl:-left-10 w-40 h-40 rounded-full opacity-10" style="background: white;"></div>
                <div class="absolute -bottom-6 ltr:-left-6 rtl:-right-6 w-24 h-24 rounded-full opacity-10" style="background: white;"></div>

                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-teal-100 text-sm mb-4">
                    <Link href="/secretary/crm" class="hover:text-white transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>{{ isRtl ? 'لوحة التحكم' : 'Dashboard' }}</span>
                    </Link>
                    <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-white font-medium">{{ isRtl ? 'العملاء المحتملين' : 'Leads' }}</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">
                            {{ isRtl ? 'العملاء المحتملين' : 'Leads' }}
                        </h1>
                        <p class="text-teal-100 text-sm mt-1">
                            {{ isRtl ? 'إدارة ومتابعة جميع العملاء المحتملين' : 'Manage and follow up with all your leads' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Count badge -->
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/20">
                            <span class="text-2xl font-bold text-white">{{ leads.total || 0 }}</span>
                            <span class="text-teal-100 text-xs block">{{ isRtl ? 'عميل محتمل' : 'Total Leads' }}</span>
                        </div>
                        <!-- Action buttons -->
                        <div class="flex items-center gap-2">
                            <button @click="exportLeadsCSV"
                               class="inline-flex items-center gap-1.5 px-3 py-2 bg-white/15 hover:bg-white/25 backdrop-blur text-white rounded-lg text-xs font-medium transition-all duration-200 border border-white/20">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                {{ selectedLeads.length > 0
                                    ? (isRtl ? `تصدير (${selectedLeads.length})` : `Export (${selectedLeads.length})`)
                                    : (isRtl ? 'تصدير CSV' : 'Export CSV') }}
                            </button>
                            <Link href="/secretary/crm/pipeline"
                                  class="inline-flex items-center gap-1.5 px-3 py-2 bg-white/15 hover:bg-white/25 backdrop-blur text-white rounded-lg text-xs font-medium transition-all duration-200 border border-white/20">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                                {{ isRtl ? 'أنابيب' : 'Pipeline' }}
                            </Link>
                            <Link href="/secretary/crm/leads/create"
                                  class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-teal-600 to-emerald-500 text-white rounded-lg text-sm font-semibold shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 border border-white/20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                {{ isRtl ? 'عميل جديد' : 'New Lead' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════ FILTER BAR ═══════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 transition-all duration-300">
                <div class="flex flex-col lg:flex-row gap-3">
                    <!-- Search -->
                    <div class="relative flex-1 group">
                        <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400 group-focus-within:text-teal-500 transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input v-model="search" type="text" data-search-input
                            :placeholder="isRtl ? '\u0628\u062D\u062B \u0628\u0627\u0644\u0627\u0633\u0645\u060C \u0627\u0644\u0647\u0627\u062A\u0641\u060C \u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A...' : 'Search by name, phone, email...'  "
                            class="w-full ltr:pl-11 rtl:pr-11 ltr:pr-12 rtl:pl-12 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 bg-gray-50 focus:bg-white" />
                        <!-- Keyboard shortcut badge -->
                        <div v-if="!search" class="absolute inset-y-0 ltr:right-0 rtl:left-0 ltr:pr-3 rtl:pl-3 hidden md:flex items-center pointer-events-none">
                            <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-semibold text-gray-400 bg-gray-100 border border-gray-200 rounded shadow-sm">/</kbd>
                        </div>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="relative min-w-[180px]">
                        <select v-model="statusFilter"
                            class="w-full appearance-none text-sm border border-gray-200 rounded-xl py-2.5 ltr:pl-4 rtl:pr-4 ltr:pr-10 rtl:pl-10 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 bg-gray-50 focus:bg-white cursor-pointer">
                            <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <div class="absolute inset-y-0 ltr:right-0 rtl:left-0 ltr:pr-3 rtl:pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Priority Dropdown -->
                    <div class="relative min-w-[160px]">
                        <select v-model="priorityFilter"
                            class="w-full appearance-none text-sm border border-gray-200 rounded-xl py-2.5 ltr:pl-4 rtl:pr-4 ltr:pr-10 rtl:pl-10 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 bg-gray-50 focus:bg-white cursor-pointer">
                            <option value="">{{ isRtl ? 'جميع الأولويات' : 'All Priorities' }}</option>
                            <option value="1">{{ isRtl ? 'ساخن' : 'Hot' }}</option>
                            <option value="2">{{ isRtl ? 'دافئ' : 'Warm' }}</option>
                            <option value="3">{{ isRtl ? 'بارد' : 'Cold' }}</option>
                        </select>
                        <div class="absolute inset-y-0 ltr:right-0 rtl:left-0 ltr:pr-3 rtl:pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Advanced Filters Toggle + View Toggle -->
                    <div class="flex items-center gap-2">
                        <button @click="showAdvancedFilters = !showAdvancedFilters"
                            class="relative inline-flex items-center gap-1.5 px-3 py-2.5 text-sm border rounded-xl transition-all duration-200"
                            :class="showAdvancedFilters || advancedFilterCount > 0
                                ? 'border-teal-300 bg-teal-50 text-teal-700'
                                : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span class="hidden sm:inline">{{ isRtl ? 'متقدم' : 'More' }}</span>
                            <span v-if="advancedFilterCount > 0"
                                class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-teal-500 text-white text-[10px] font-bold flex items-center justify-center">
                                {{ advancedFilterCount }}
                            </span>
                        </button>

                        <!-- Sort Dropdown -->
                        <div class="relative min-w-[140px]">
                            <select @change="changeSort($event.target.value)" :value="sortField"
                                class="w-full appearance-none text-sm border border-gray-200 rounded-xl py-2.5 ltr:pl-4 rtl:pr-4 ltr:pr-10 rtl:pl-10 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 bg-gray-50 focus:bg-white cursor-pointer">
                                <option value="created_at">{{ isRtl ? 'الأحدث' : 'Newest' }}</option>
                                <option value="full_name">{{ isRtl ? 'الاسم' : 'Name' }}</option>
                                <option value="score">{{ isRtl ? 'النقاط' : 'Score' }}</option>
                                <option value="priority">{{ isRtl ? 'الأولوية' : 'Priority' }}</option>
                                <option value="next_follow_up_at">{{ isRtl ? 'المتابعة' : 'Follow-up' }}</option>
                            </select>
                            <div class="absolute inset-y-0 ltr:right-0 rtl:left-0 ltr:pr-3 rtl:pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="sortDir === 'asc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>

                        <!-- View Toggle -->
                        <div class="flex items-center bg-gray-100 rounded-xl p-1 gap-0.5">
                            <button @click="viewMode = 'grid'"
                                :class="viewMode === 'grid' ? 'bg-white shadow-sm text-teal-600' : 'text-gray-500 hover:text-gray-700'"
                                class="p-2 rounded-lg transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button @click="viewMode = 'table'"
                                :class="viewMode === 'table' ? 'bg-white shadow-sm text-teal-600' : 'text-gray-500 hover:text-gray-700'"
                                class="p-2 rounded-lg transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Column Visibility Toggle (table view only) -->
                        <div v-if="viewMode === 'table'" class="relative">
                            <button @click.stop="showColumnMenu = !showColumnMenu"
                                class="p-2 rounded-lg bg-gray-100 text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200"
                                :title="isRtl ? 'إظهار/إخفاء الأعمدة' : 'Show/hide columns'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95">
                                <div v-if="showColumnMenu" class="absolute top-full mt-1 end-0 w-52 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-30">
                                    <p class="px-3 pb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">{{ isRtl ? 'الأعمدة المرئية' : 'Visible Columns' }}</p>
                                    <label v-for="col in tableColumns" :key="col.key"
                                        class="flex items-center gap-2.5 px-3 py-1.5 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" :checked="isColumnVisible(col.key)" @change="toggleColumn(col.key)"
                                            class="w-3.5 h-3.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 transition" />
                                        <span class="text-xs text-gray-700">{{ isRtl ? col.ar : col.en }}</span>
                                    </label>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters Panel -->
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 max-h-0"
                    enter-to-class="opacity-100 max-h-40"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 max-h-40"
                    leave-to-class="opacity-0 max-h-0">
                    <div v-if="showAdvancedFilters" class="overflow-hidden mt-3 pt-3 border-t border-gray-100">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <!-- Source Filter -->
                            <div class="relative flex-1">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1 uppercase tracking-wider">
                                    {{ isRtl ? 'المصدر' : 'Source' }}
                                </label>
                                <select v-model="sourceFilter"
                                    class="w-full appearance-none text-sm border border-gray-200 rounded-xl py-2.5 ltr:pl-4 rtl:pr-4 ltr:pr-10 rtl:pl-10 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 bg-gray-50 focus:bg-white cursor-pointer">
                                    <option value="">{{ isRtl ? 'جميع المصادر' : 'All Sources' }}</option>
                                    <option v-for="src in sources" :key="src.id" :value="src.id">
                                        {{ isRtl ? src.name_ar : src.name_en }}
                                    </option>
                                </select>
                                <div class="absolute bottom-0 inset-y-auto ltr:right-0 rtl:left-0 ltr:pr-3 rtl:pl-3 h-[42px] flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                            <!-- Date From -->
                            <div class="flex-1">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1 uppercase tracking-wider">
                                    {{ isRtl ? 'من تاريخ' : 'Date From' }}
                                </label>
                                <input v-model="dateFrom" type="date"
                                    class="w-full text-sm border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 bg-gray-50 focus:bg-white" />
                            </div>
                            <!-- Date To -->
                            <div class="flex-1">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1 uppercase tracking-wider">
                                    {{ isRtl ? 'إلى تاريخ' : 'Date To' }}
                                </label>
                                <input v-model="dateTo" type="date"
                                    class="w-full text-sm border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 bg-gray-50 focus:bg-white" />
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Active Filter Pills -->
                <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                    <span class="text-xs text-gray-500 font-medium">
                        {{ isRtl ? 'الفلاتر النشطة:' : 'Active filters:' }}
                    </span>
                    <TransitionGroup name="pill">
                        <span v-for="pill in activeFilterPills" :key="pill.key"
                            class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border transition-all duration-200"
                            style="background-color: #f0fdfa; color: #0d9488; border-color: #99f6e4;">
                            {{ pill.label }}
                            <button @click="removeFilter(pill.key)" class="hover:bg-teal-200 rounded-full p-0.5 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    </TransitionGroup>
                    <button @click="clearFilters"
                        class="text-xs text-red-500 hover:text-red-700 hover:underline font-medium transition-colors ltr:ml-2 rtl:mr-2">
                        {{ isRtl ? 'مسح الكل' : 'Clear all' }}
                    </button>
                    <span class="ltr:ml-auto rtl:mr-auto text-xs text-gray-400">
                        {{ leads.total }} {{ isRtl ? 'نتيجة' : 'results' }}
                    </span>
                </div>
            </div>

            <!-- ═══════════════ BULK ACTIONS BAR ═══════════════ -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-3 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-3 scale-95">
                <div v-if="showBulkActions" class="bg-teal-50 border border-teal-200 rounded-xl px-4 py-3 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full bg-teal-500 text-white text-xs font-bold flex items-center justify-center">{{ selectedLeads.length }}</span>
                        <span class="text-sm font-medium text-teal-800">{{ isRtl ? 'عميل محدد' : 'selected' }}</span>
                        <button @click="clearSelection" class="text-xs text-teal-600 hover:text-teal-800 underline ms-2">{{ isRtl ? 'إلغاء التحديد' : 'Clear' }}</button>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-teal-600 font-medium">{{ isRtl ? 'تغيير الحالة:' : 'Status:' }}</span>
                        <button @click="bulkUpdateStatus('contacted')" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-colors">{{ isRtl ? 'تم التواصل' : 'Contacted' }}</button>
                        <button @click="bulkUpdateStatus('qualified')" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors">{{ isRtl ? 'مؤهل' : 'Qualified' }}</button>
                        <button @click="bulkUpdateStatus('appointment_booked')" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">{{ isRtl ? 'تم الحجز' : 'Booked' }}</button>
                        <button @click="bulkUpdateStatus('negotiation')" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-orange-100 text-orange-700 hover:bg-orange-200 transition-colors">{{ isRtl ? 'تفاوض' : 'Negotiation' }}</button>
                        <span class="w-px h-5 bg-teal-200 mx-1"></span>
                        <span class="text-xs text-teal-600 font-medium">{{ isRtl ? 'الأولوية:' : 'Priority:' }}</span>
                        <button @click="bulkUpdatePriority(1)" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-red-100 text-red-700 hover:bg-red-200 transition-colors">{{ isRtl ? 'ساخن' : 'Hot' }}</button>
                        <button @click="bulkUpdatePriority(2)" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">{{ isRtl ? 'دافئ' : 'Warm' }}</button>
                        <button @click="bulkUpdatePriority(3)" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">{{ isRtl ? 'بارد' : 'Cold' }}</button>
                    </div>
                </div>
            </Transition>

            <!-- ═══════════════ SUMMARY STRIP ═══════════════ -->
            <div class="flex items-center gap-3 flex-wrap text-xs" :class="mounted ? 'opacity-100' : 'opacity-0'" style="transition: opacity 0.4s">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ leadsSummary.shown }}<span v-if="leadsSummary.total > leadsSummary.shown">/{{ leadsSummary.total }}</span> {{ isRtl ? '\u0639\u0645\u064A\u0644' : 'leads' }}
                </span>
                <span v-if="leadsSummary.hotCount > 0" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 text-red-600 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                    {{ leadsSummary.hotCount }} {{ isRtl ? '\u0633\u0627\u062E\u0646' : 'hot' }}
                </span>
                <span v-if="leadsSummary.overdueCount > 0" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 font-medium">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ leadsSummary.overdueCount }} {{ isRtl ? '\u0645\u062A\u0623\u062E\u0631' : 'overdue' }}
                </span>
                <span v-if="leadsSummary.highScore > 0" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 text-teal-600 font-medium">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    {{ leadsSummary.highScore }} {{ isRtl ? '\u0646\u0642\u0627\u0637 \u0639\u0627\u0644\u064A\u0629' : 'high score' }}
                </span>
                <span class="flex-1"></span>
                <span class="text-gray-400 hidden sm:inline">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono border border-gray-200">/</kbd> {{ isRtl ? '\u0628\u062D\u062B' : 'search' }}
                    <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono border border-gray-200 ms-2">N</kbd> {{ isRtl ? '\u062C\u062F\u064A\u062F' : 'new' }}
                </span>
            </div>

            <!-- ═══════════════ STATUS DISTRIBUTION BAR ═══════════════ -->
            <div v-if="statusDistribution.length > 1 && leads.data?.length"
                 class="bg-white rounded-xl border border-gray-100 p-3 mb-4 transition-all duration-500"
                 :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
                 :style="{ transitionDelay: '0.12s' }">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[11px] font-semibold text-gray-500">{{ isRtl ? 'توزيع الحالات' : 'Status Distribution' }}</span>
                    <span class="text-[10px] text-gray-400">({{ leads.data.length }} {{ isRtl ? 'عميل' : 'leads' }})</span>
                </div>
                <!-- Segmented bar -->
                <div class="flex rounded-full overflow-hidden h-2 bg-gray-100">
                    <div v-for="seg in statusDistribution" :key="seg.status"
                         class="transition-all duration-700 ease-out first:rounded-s-full last:rounded-e-full"
                         :style="{ width: Math.max(seg.pct, 2) + '%', backgroundColor: seg.color }"
                         :title="`${seg.label}: ${seg.count} (${seg.pct}%)`">
                    </div>
                </div>
                <!-- Legend -->
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2">
                    <div v-for="seg in statusDistribution" :key="seg.status" class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: seg.color }"></span>
                        <span class="text-[10px] text-gray-500">{{ seg.label }}</span>
                        <span class="text-[10px] font-bold text-gray-700">{{ seg.count }}</span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════ GRID VIEW ═══════════════ -->
            <div v-if="viewMode === 'grid' && leads.data?.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div v-for="(lead, idx) in leads.data" :key="lead.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-teal-200 transition-all duration-300 group"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    :style="{ transitionDelay: mounted ? (idx * 0.04 + 0.1) + 's' : '0s' }">

                    <!-- Card Top: Avatar + Name + Quick Actions -->
                    <div class="flex items-start gap-3 mb-4">
                        <!-- Checkbox -->
                        <label class="relative flex items-center cursor-pointer mt-1" @click.stop>
                            <input type="checkbox" :checked="isSelected(lead.id)" @change="toggleLead(lead.id)"
                                class="w-4 h-4 rounded border-gray-300 text-teal-500 focus:ring-teal-400 transition-all" />
                        </label>
                        <!-- Avatar -->
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm"
                             :style="{ background: getAvatarGradient(lead.id) }">
                            {{ getInitials(lead.full_name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <Link :href="`/secretary/crm/leads/${lead.id}`"
                                class="font-semibold text-gray-800 hover:text-teal-600 transition-colors block truncate text-sm">
                                {{ lead.full_name }}
                            </Link>
                            <p v-if="lead.city" class="text-xs text-gray-400 mt-0.5 truncate">{{ lead.city }}</p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <p v-if="lead.phone" class="text-xs text-gray-500 truncate">{{ lead.phone }}</p>
                            </div>
                        </div>
                        <!-- Quick Actions -->
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <a :href="phoneLink(lead.phone)" v-if="lead.phone"
                               class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition-all" :title="isRtl ? 'اتصال' : 'Call'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </a>
                            <a :href="whatsappLink(lead.phone)" target="_blank" v-if="lead.phone"
                               class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-green-600 hover:bg-green-50 transition-all" :title="isRtl ? 'واتساب' : 'WhatsApp'">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.555 4.126 1.527 5.86L.06 23.487a.5.5 0 00.614.614l5.627-1.467A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.94 0-3.788-.55-5.394-1.59a.5.5 0 00-.384-.063l-3.713.968.968-3.713a.5.5 0 00-.063-.384A9.953 9.953 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                </svg>
                            </a>
                            <button @click.stop="openQuickView(lead)"
                               class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-purple-600 hover:bg-purple-50 transition-all" :title="isRtl ? 'معاينة' : 'Preview'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <Link :href="`/secretary/crm/leads/${lead.id}`"
                               class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition-all" :title="isRtl ? 'تفاصيل' : 'Details'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <!-- Badges Row -->
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <!-- Status Badge (clickable) -->
                        <div class="relative">
                            <button @click.stop="toggleInlineStatus(lead.id)"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full cursor-pointer hover:ring-2 hover:ring-offset-1 hover:ring-teal-300 transition-all"
                                :style="{ backgroundColor: statusColors[lead.status]?.bg, color: statusColors[lead.status]?.text }">
                                <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: statusColors[lead.status]?.dot }"></span>
                                {{ statusLabels[lead.status] || lead.status }}
                                <svg class="w-2.5 h-2.5 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <Transition enter-active-class="transition-all duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition-all duration-100 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                <div v-if="inlineStatusOpen === lead.id"
                                     class="absolute z-30 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 min-w-[160px]"
                                     :class="isRtl ? 'right-0' : 'left-0'">
                                    <button v-for="st in inlineStatusOptions" :key="st" @click.stop="changeLeadStatus(lead.id, st)"
                                        :class="['w-full flex items-center gap-2 px-3 py-1.5 text-xs text-start hover:bg-gray-50 transition-colors',
                                            lead.status === st ? 'font-bold' : '']">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: statusColors[st]?.dot }"></span>
                                        {{ statusLabels[st] || st }}
                                        <svg v-if="lead.status === st" class="w-3 h-3 text-teal-500 ms-auto" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </Transition>
                        </div>

                        <!-- Priority Badge -->
                        <span v-if="lead.priority && priorityConfig[lead.priority]"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full border"
                            :style="{ backgroundColor: priorityConfig[lead.priority].bg, color: priorityConfig[lead.priority].text, borderColor: priorityConfig[lead.priority].border }">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="priorityConfig[lead.priority].icon"/></svg>
                            {{ priorityConfig[lead.priority].label }}
                        </span>

                        <!-- Source Badge -->
                        <span v-if="lead.source"
                            class="inline-flex items-center gap-1 text-[11px] font-medium px-2.5 py-1 rounded-full"
                            :style="{ backgroundColor: lead.source.color + '15', color: lead.source.color }">
                            {{ lead.source.name_en }}
                        </span>
                    </div>

                    <!-- Score + Follow-up Row -->
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <!-- Score Gauge -->
                        <div class="flex items-center gap-2">
                            <div class="relative w-10 h-10">
                                <svg class="w-10 h-10 -rotate-90" viewBox="0 0 36 36">
                                    <circle cx="18" cy="18" r="16" fill="none" stroke="#f3f4f6" stroke-width="3" />
                                    <circle cx="18" cy="18" r="16" fill="none"
                                        :stroke="scoreColor(lead.score)"
                                        stroke-width="3"
                                        stroke-linecap="round"
                                        :stroke-dasharray="scoreDash(lead.score).circumference"
                                        :stroke-dashoffset="mounted ? scoreDash(lead.score).offset : scoreDash(lead.score).circumference"
                                        class="transition-all duration-1000 ease-out" />
                                </svg>
                                <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold"
                                      :style="{ color: scoreColor(lead.score) }">
                                    {{ lead.score || 0 }}
                                </span>
                            </div>
                            <span class="text-[10px] text-gray-400 uppercase font-medium">{{ isRtl ? 'النقاط' : 'Score' }}</span>
                        </div>

                        <!-- Follow-up -->
                        <div class="text-end">
                            <div v-if="lead.next_follow_up_at" class="flex items-center gap-1"
                                 :class="isOverdue(lead.next_follow_up_at) ? 'text-red-500' : 'text-gray-500'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-[11px] font-medium" :class="isOverdue(lead.next_follow_up_at) ? 'font-semibold' : ''">
                                    {{ formatDate(lead.next_follow_up_at) }}
                                </span>
                                <span v-if="isOverdue(lead.next_follow_up_at)" class="text-[9px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-bold">
                                    {{ isRtl ? 'متأخر' : 'OVERDUE' }}
                                </span>
                            </div>
                            <div v-else class="text-xs text-gray-400">
                                {{ isRtl ? 'لا توجد متابعة' : 'No follow-up' }}
                            </div>
                            <p class="text-[10px] text-gray-400 mt-0.5">
                                {{ isRtl ? 'المتابعات:' : 'Follow-ups:' }} {{ lead.follow_up_count || 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════ TABLE VIEW ═══════════════ -->
            <div v-if="viewMode === 'table' && leads.data?.length"
                 class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                 :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                 style="transition: all 0.4s ease;">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100"
                                style="background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);">
                                <th class="px-3 py-3.5 w-10">
                                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded border-gray-300 text-teal-500 focus:ring-teal-400" />
                                </th>
                                <th class="px-5 py-3.5 font-semibold ltr:text-left rtl:text-right">{{ isRtl ? 'العميل' : 'Lead' }}</th>
                                <th v-if="isColumnVisible('phone')" class="px-5 py-3.5 font-semibold ltr:text-left rtl:text-right">{{ isRtl ? 'التواصل' : 'Contact' }}</th>
                                <th v-if="isColumnVisible('source')" class="px-5 py-3.5 font-semibold ltr:text-left rtl:text-right">{{ isRtl ? 'المصدر' : 'Source' }}</th>
                                <th v-if="isColumnVisible('priority')" class="px-5 py-3.5 font-semibold text-center">{{ isRtl ? 'الأولوية' : 'Priority' }}</th>
                                <th v-if="isColumnVisible('status')" class="px-5 py-3.5 font-semibold text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                <th v-if="isColumnVisible('score')" class="px-5 py-3.5 font-semibold text-center">{{ isRtl ? 'النقاط' : 'Score' }}</th>
                                <th v-if="isColumnVisible('next_follow_up')" class="px-5 py-3.5 font-semibold ltr:text-left rtl:text-right">{{ isRtl ? 'المتابعة القادمة' : 'Next Follow-up' }}</th>
                                <th v-if="isColumnVisible('created_at')" class="px-5 py-3.5 font-semibold ltr:text-left rtl:text-right">{{ isRtl ? 'تاريخ الإنشاء' : 'Created' }}</th>
                                <th class="px-5 py-3.5 font-semibold text-center">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(lead, idx) in leads.data" :key="lead.id"
                                :class="['transition-colors duration-150', isSelected(lead.id) ? 'bg-teal-50/50' : 'hover:bg-teal-50/30']">
                                <!-- Checkbox -->
                                <td class="px-3 py-3.5">
                                    <input type="checkbox" :checked="isSelected(lead.id)" @change="toggleLead(lead.id)" class="w-4 h-4 rounded border-gray-300 text-teal-500 focus:ring-teal-400" />
                                </td>
                                <!-- Lead -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                                             :style="{ background: getAvatarGradient(lead.id) }">
                                            {{ getInitials(lead.full_name) }}
                                        </div>
                                        <div>
                                            <Link :href="`/secretary/crm/leads/${lead.id}`" class="font-semibold text-gray-800 hover:text-teal-600 transition-colors text-sm">
                                                {{ lead.full_name }}
                                            </Link>
                                            <p v-if="lead.city" class="text-[11px] text-gray-400 mt-0.5">{{ lead.city }}</p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Contact -->
                                <td v-if="isColumnVisible('phone')" class="px-5 py-3.5">
                                    <p class="text-gray-700 text-sm">{{ lead.phone || '-' }}</p>
                                    <p v-if="isColumnVisible('email') && lead.email" class="text-[11px] text-gray-400 mt-0.5 truncate max-w-[180px]">{{ lead.email }}</p>
                                </td>
                                <!-- Source -->
                                <td v-if="isColumnVisible('source')" class="px-5 py-3.5">
                                    <span v-if="lead.source"
                                        class="inline-flex items-center gap-1 text-[11px] font-medium px-2.5 py-1 rounded-full"
                                        :style="{ backgroundColor: lead.source.color + '15', color: lead.source.color }">
                                        {{ lead.source.name_en }}
                                    </span>
                                    <span v-else class="text-gray-400 text-xs">-</span>
                                </td>
                                <!-- Priority -->
                                <td v-if="isColumnVisible('priority')" class="px-5 py-3.5 text-center">
                                    <span v-if="lead.priority && priorityConfig[lead.priority]"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full border"
                                        :style="{ backgroundColor: priorityConfig[lead.priority].bg, color: priorityConfig[lead.priority].text, borderColor: priorityConfig[lead.priority].border }">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="priorityConfig[lead.priority].icon"/></svg>
                            {{ priorityConfig[lead.priority].label }}
                                    </span>
                                </td>
                                <!-- Status (clickable inline change) -->
                                <td v-if="isColumnVisible('status')" class="px-5 py-3.5 text-center">
                                    <div class="relative inline-block">
                                        <button @click.stop="toggleInlineStatus(lead.id)"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full whitespace-nowrap cursor-pointer hover:ring-2 hover:ring-offset-1 hover:ring-teal-300 transition-all"
                                            :style="{ backgroundColor: statusColors[lead.status]?.bg, color: statusColors[lead.status]?.text }">
                                            <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: statusColors[lead.status]?.dot }"></span>
                                            {{ statusLabels[lead.status] || lead.status }}
                                            <svg class="w-2.5 h-2.5 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <Transition enter-active-class="transition-all duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition-all duration-100 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                            <div v-if="inlineStatusOpen === lead.id"
                                                 class="absolute z-30 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 py-1.5 min-w-[160px]"
                                                 :class="isRtl ? 'right-0' : 'left-0'">
                                                <button v-for="st in inlineStatusOptions" :key="st" @click.stop="changeLeadStatus(lead.id, st)"
                                                    :class="['w-full flex items-center gap-2 px-3 py-1.5 text-xs text-start hover:bg-gray-50 transition-colors',
                                                        lead.status === st ? 'font-bold' : '']">
                                                    <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: statusColors[st]?.dot }"></span>
                                                    {{ statusLabels[st] || st }}
                                                    <svg v-if="lead.status === st" class="w-3 h-3 text-teal-500 ms-auto" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </div>
                                        </Transition>
                                    </div>
                                </td>
                                <!-- Score -->
                                <td v-if="isColumnVisible('score')" class="px-5 py-3.5 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <div class="relative w-8 h-8">
                                            <svg class="w-8 h-8 -rotate-90" viewBox="0 0 36 36">
                                                <circle cx="18" cy="18" r="16" fill="none" stroke="#f3f4f6" stroke-width="3" />
                                                <circle cx="18" cy="18" r="16" fill="none"
                                                    :stroke="scoreColor(lead.score)"
                                                    stroke-width="3"
                                                    stroke-linecap="round"
                                                    :stroke-dasharray="scoreDash(lead.score).circumference"
                                                    :stroke-dashoffset="mounted ? scoreDash(lead.score).offset : scoreDash(lead.score).circumference"
                                                    class="transition-all duration-1000 ease-out" />
                                            </svg>
                                            <span class="absolute inset-0 flex items-center justify-center text-[9px] font-bold"
                                                  :style="{ color: scoreColor(lead.score) }">
                                                {{ lead.score || 0 }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <!-- Next Follow-up -->
                                <td v-if="isColumnVisible('next_follow_up')" class="px-5 py-3.5">
                                    <div v-if="lead.next_follow_up_at" class="flex items-center gap-1.5"
                                         :class="isOverdue(lead.next_follow_up_at) ? 'text-red-500' : 'text-gray-500'">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-xs" :class="isOverdue(lead.next_follow_up_at) ? 'font-semibold' : ''">
                                            {{ formatDate(lead.next_follow_up_at) }}
                                        </span>
                                        <span v-if="isOverdue(lead.next_follow_up_at)" class="text-[9px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-bold">
                                            {{ isRtl ? 'متأخر' : 'OVERDUE' }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <!-- Created -->
                                <td v-if="isColumnVisible('created_at')" class="px-5 py-3.5">
                                    <span class="text-xs text-gray-400">{{ timeAgo(lead.created_at) }}</span>
                                </td>
                                <!-- Actions -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <a :href="phoneLink(lead.phone)" v-if="lead.phone"
                                           class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </a>
                                        <a :href="whatsappLink(lead.phone)" target="_blank" v-if="lead.phone"
                                           class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-green-600 hover:bg-green-50 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.555 4.126 1.527 5.86L.06 23.487a.5.5 0 00.614.614l5.627-1.467A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.94 0-3.788-.55-5.394-1.59a.5.5 0 00-.384-.063l-3.713.968.968-3.713a.5.5 0 00-.063-.384A9.953 9.953 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                            </svg>
                                        </a>
                                        <Link :href="`/secretary/crm/leads/${lead.id}`"
                                           class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══════════════ EMPTY STATE ═══════════════ -->
            <div v-if="!leads.data?.length"
                 class="bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-20 text-center"
                 :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                 style="transition: all 0.5s ease;">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #ccfbf1, #99f6e4);">
                    <svg class="w-10 h-10 text-teal-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">
                    {{ isRtl ? 'لا توجد عملاء محتملين' : 'No leads found' }}
                </h3>
                <p class="text-sm text-gray-400 max-w-md mx-auto mb-6">
                    {{ isRtl
                        ? 'لم يتم العثور على أي عملاء محتملين بناءً على الفلاتر الحالية. حاول تعديل معايير البحث.'
                        : 'No leads match your current filters. Try adjusting your search criteria or clearing filters.' }}
                </p>
                <button v-if="hasActiveFilters" @click="clearFilters"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-medium transition-all duration-200 hover:shadow-lg"
                    style="background-color: #0d9488;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ isRtl ? 'مسح جميع الفلاتر' : 'Clear all filters' }}
                </button>
            </div>

            <!-- ═══════════════ PAGINATION ═══════════════ -->
            <div v-if="leads.last_page > 1"
                 class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-500">
                        {{ isRtl ? 'عرض' : 'Showing' }}
                        <span class="font-semibold text-gray-700">{{ leads.from }}</span>
                        {{ isRtl ? 'إلى' : 'to' }}
                        <span class="font-semibold text-gray-700">{{ leads.to }}</span>
                        {{ isRtl ? 'من' : 'of' }}
                        <span class="font-semibold text-gray-700">{{ leads.total }}</span>
                        {{ isRtl ? 'نتيجة' : 'results' }}
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in leads.links" :key="i">
                            <Link v-if="link.url"
                                :href="link.url"
                                class="min-w-[36px] h-9 px-3 flex items-center justify-center text-sm rounded-lg transition-all duration-200"
                                :class="link.active
                                    ? 'text-white font-semibold shadow-sm'
                                    : 'text-gray-600 hover:bg-gray-100'"
                                :style="link.active ? { backgroundColor: '#0d9488' } : {}"
                                v-html="link.label"
                                preserveState
                            />
                            <span v-else
                                class="min-w-[36px] h-9 px-3 flex items-center justify-center text-sm text-gray-300 cursor-not-allowed"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

        </div>

    <!-- ═══════════════ QUICK VIEW PANEL ═══════════════ -->
    <Teleport to="body">
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                    leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="quickViewOpen" class="fixed inset-0 z-50 flex" :dir="isRtl ? 'rtl' : 'ltr'">
                <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeQuickView"></div>
                <Transition enter-active-class="transition-transform duration-300 ease-out"
                            :enter-from-class="isRtl ? '-translate-x-full' : 'translate-x-full'" enter-to-class="translate-x-0"
                            leave-active-class="transition-transform duration-200 ease-in" leave-from-class="translate-x-0"
                            :leave-to-class="isRtl ? '-translate-x-full' : 'translate-x-full'">
                    <div v-if="quickViewOpen" :class="['relative w-full max-w-sm bg-white shadow-2xl overflow-y-auto', isRtl ? 'rounded-r-2xl' : 'rounded-l-2xl ms-auto']">
                        <!-- Header -->
                        <div v-if="quickViewLead" class="sticky top-0 z-10 bg-gradient-to-r from-teal-500 to-emerald-500 p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-medium text-teal-100">{{ isRtl ? 'معاينة سريعة' : 'Quick Preview' }}</span>
                                <button @click="closeQuickView" class="w-7 h-7 rounded-lg bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-sm border-2 border-white/30"
                                     :style="{ background: getAvatarGradient(quickViewLead.id) }">
                                    {{ getInitials(quickViewLead.full_name) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white">{{ quickViewLead.full_name }}</h3>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-teal-100" dir="ltr">{{ quickViewLead.phone }}</span>
                                        <span v-if="quickViewLead.priority" :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full', quickViewLead.priority == 1 ? 'bg-red-500/30 text-red-100' : quickViewLead.priority == 2 ? 'bg-amber-400/30 text-amber-100' : 'bg-blue-400/30 text-blue-100']">
                                            {{ priorityConfig[quickViewLead.priority]?.label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div v-if="quickViewLead" class="p-4 space-y-4">
                            <!-- Info cards -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded-lg bg-gray-50 p-3 text-center">
                                    <div class="text-lg font-bold" :style="{ color: statusColors[quickViewLead.status]?.text }">{{ statusLabels[quickViewLead.status] || quickViewLead.status }}</div>
                                    <div class="text-[10px] text-gray-400">{{ isRtl ? 'الحالة' : 'Status' }}</div>
                                </div>
                                <div class="rounded-lg bg-gray-50 p-3 text-center">
                                    <div class="text-lg font-bold" :style="{ color: scoreColor(quickViewLead.score) }">{{ quickViewLead.score || 0 }}</div>
                                    <div class="text-[10px] text-gray-400">{{ isRtl ? 'النقاط' : 'Score' }}</div>
                                </div>
                            </div>

                            <!-- Contact buttons -->
                            <div class="flex gap-2">
                                <a :href="phoneLink(quickViewLead.phone)" class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-teal-50 text-teal-700 text-xs font-medium hover:bg-teal-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    {{ isRtl ? '\u0627\u062A\u0635\u0627\u0644' : 'Call' }}
                                </a>
                                <a :href="whatsappLink(quickViewLead.phone)" target="_blank" class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-green-50 text-green-700 text-xs font-medium hover:bg-green-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                    {{ isRtl ? '\u0648\u0627\u062A\u0633\u0627\u0628' : 'WhatsApp' }}
                                </a>
                                <Link :href="`/secretary/crm/leads/${quickViewLead.id}`" class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-gray-50 text-gray-700 text-xs font-medium hover:bg-gray-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                    {{ isRtl ? '\u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644' : 'Details' }}
                                </Link>
                            </div>

                            <!-- Quick Status Change -->
                            <div>
                                <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ isRtl ? '\u062A\u063A\u064A\u064A\u0631 \u0627\u0644\u062D\u0627\u0644\u0629' : 'Change Status' }}</h4>
                                <div class="flex flex-wrap gap-1">
                                    <button v-for="opt in inlineStatusOptions" :key="opt.value"
                                        @click="quickViewChangeStatus(opt.value)"
                                        :disabled="quickViewStatusChanging || quickViewLead.status === opt.value"
                                        :class="['px-2 py-1 rounded-md text-[10px] font-medium border transition-all duration-150',
                                            quickViewLead.status === opt.value
                                                ? 'border-teal-300 bg-teal-50 text-teal-700 ring-1 ring-teal-200'
                                                : 'border-gray-200 bg-white text-gray-600 hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700',
                                            quickViewStatusChanging ? 'opacity-50 cursor-not-allowed' : '']">
                                        {{ isRtl ? opt.ar : opt.en }}
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Note -->
                            <div>
                                <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ isRtl ? '\u0645\u0644\u0627\u062D\u0638\u0629 \u0633\u0631\u064A\u0639\u0629' : 'Quick Note' }}</h4>
                                <form @submit.prevent="quickViewAddNote" class="flex gap-1.5">
                                    <input v-model="quickViewNote" type="text"
                                        class="flex-1 rounded-lg border-gray-200 text-xs py-1.5 px-2.5 focus:ring-teal-500 focus:border-teal-500"
                                        :placeholder="isRtl ? '\u0627\u0643\u062A\u0628 \u0645\u0644\u0627\u062D\u0638\u0629...' : 'Type a note...'" />
                                    <button type="submit"
                                        :disabled="!quickViewNote.trim() || quickViewSavingNote"
                                        class="px-3 py-1.5 bg-teal-600 text-white text-xs font-medium rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-1">
                                        <svg v-if="quickViewSavingNote" class="w-3 h-3 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        {{ isRtl ? '\u0625\u0636\u0627\u0641\u0629' : 'Add' }}
                                    </button>
                                </form>
                            </div>

                            <!-- Loading -->
                            <div v-if="quickViewLoading" class="flex justify-center py-6">
                                <svg class="w-6 h-6 text-teal-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </div>

                            <!-- Pending Follow-ups -->
                            <div v-if="!quickViewLoading && quickViewFollowUps.length">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ isRtl ? 'متابعات معلقة' : 'Pending Follow-ups' }}</h4>
                                <div class="space-y-2">
                                    <div v-for="fu in quickViewFollowUps" :key="fu.id"
                                         :class="['flex items-center gap-2 p-2.5 rounded-lg border text-xs',
                                            new Date(fu.scheduled_at) < new Date() ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-gray-50']">
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="font-medium text-gray-700 capitalize">{{ fu.type }}</span>
                                        <span :class="new Date(fu.scheduled_at) < new Date() ? 'text-red-500 font-semibold' : 'text-gray-400'" class="ms-auto">
                                            {{ new Date(fu.scheduled_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activities -->
                            <div v-if="!quickViewLoading && quickViewActivities.length">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ isRtl ? 'آخر الأنشطة' : 'Recent Activities' }}</h4>
                                <div class="space-y-1.5">
                                    <div v-for="act in quickViewActivities" :key="act.id" class="flex items-start gap-2 p-2 rounded-lg hover:bg-gray-50 text-xs">
                                        <div :class="['w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5',
                                            act.type === 'call' ? 'bg-green-100 text-green-600' :
                                            act.type === 'whatsapp' ? 'bg-emerald-100 text-emerald-600' :
                                            act.type === 'email' ? 'bg-blue-100 text-blue-600' :
                                            act.type === 'meeting' ? 'bg-amber-100 text-amber-600' :
                                            act.type === 'status_change' ? 'bg-indigo-100 text-indigo-600' :
                                            'bg-gray-100 text-gray-500']">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-gray-700 truncate">{{ act.subject || act.type }}</div>
                                            <div class="text-gray-400 text-[10px] mt-0.5">
                                                {{ act.performer?.name || '-' }} - {{ timeAgo(act.created_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-if="!quickViewLoading && !quickViewActivities.length && !quickViewFollowUps.length" class="text-center py-6">
                                <p class="text-xs text-gray-400">{{ isRtl ? 'لا توجد أنشطة أو متابعات بعد' : 'No activities or follow-ups yet' }}</p>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- Scroll-to-top floating button -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-75 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-75 translate-y-4"
        >
            <button v-if="showScrollTop" @click="scrollToTop"
                class="fixed bottom-6 z-40 w-10 h-10 rounded-full bg-teal-600 text-white shadow-lg hover:bg-teal-700 hover:shadow-xl hover:scale-110 transition-all duration-200 flex items-center justify-center"
                :class="isRtl ? 'left-6' : 'right-6'"
                :title="isRtl ? 'للأعلى' : 'Back to top'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
            </button>
        </Transition>
    </Teleport>

    </SecretaryLayout>
</template>

<style scoped>
.pill-enter-active,
.pill-leave-active {
    transition: all 0.25s ease;
}
.pill-enter-from,
.pill-leave-to {
    opacity: 0;
    transform: scale(0.85) translateY(-4px);
}
</style>
