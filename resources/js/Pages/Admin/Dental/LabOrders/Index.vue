<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { t } = useLocale();
const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

const props = defineProps({
    orders: Object,
    filters: Object,
    stats: Object,
    itemTypes: Array,
    materials: Array,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const overdueOnly = ref(props.filters?.overdue === '1' || props.filters?.overdue === true);
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const showFilters = ref(false);

let searchTimeout = null;

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/dental/lab-orders', {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            overdue: overdueOnly.value ? '1' : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
}

watch([search, statusFilter, overdueOnly, dateFrom, dateTo], applyFilters);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusConfig = {
    ordered: { bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-400' },
    in_production: { bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
    ready: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    delivered: { bg: 'bg-teal-50', text: 'text-teal-700', dot: 'bg-teal-500' },
    adjustment: { bg: 'bg-orange-50', text: 'text-orange-700', dot: 'bg-orange-500' },
    completed: { bg: 'bg-green-50', text: 'text-green-700', dot: 'bg-green-500' },
    cancelled: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' },
};

function getStatus(status) { return statusConfig[status] || statusConfig.ordered; }

const statusOptions = ['ordered', 'in_production', 'ready', 'delivered', 'adjustment', 'completed', 'cancelled'];

function updateOrderStatus(orderId, newStatus) {
    router.post(`/admin/dental/lab-orders/${orderId}/status`, {
        status: newStatus,
    }, {
        preserveScroll: true,
    });
}

function isOverdue(order) {
    if (!order.expected_date || order.status === 'delivered' || order.status === 'completed' || order.status === 'cancelled') return false;
    return new Date(order.expected_date) < new Date();
}

const hasActiveFilters = computed(() => statusFilter.value || overdueOnly.value || dateFrom.value || dateTo.value);

/* ── Pipeline SVG icon paths (Heroicons) ─────────────── */
const pipelineIcons = {
    ordered: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.251 2.251 0 011.15.564m-5.8 0c-.376.023-.75.05-1.124.08C7.095 3.007 6.25 3.97 6.25 5.108v12.142A2.25 2.25 0 008.5 19.5h1.25',
    in_production: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
    ready: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    delivered: 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
    completed: 'M16.5 18.75h-9m9 0a3 3 0 013-3h.008v.008a3 3 0 01-3 2.992zm-9 0a3 3 0 01-3-3h-.008v.008a3 3 0 013 2.992zm9-3V7.875C16.5 6.839 15.661 6 14.625 6h-1.5L12 3.75 10.875 6h-1.5A1.875 1.875 0 007.5 7.875V15.75m9 0H7.5',
};

/* ── Status Pipeline ─────────────────────────────────── */
const statusPipeline = computed(() => {
    const pipeline = [
        { key: 'ordered', color: 'gray' },
        { key: 'in_production', color: 'blue' },
        { key: 'ready', color: 'emerald' },
        { key: 'delivered', color: 'teal' },
        { key: 'completed', color: 'green' },
    ];
    const counts = {};
    if (props.orders?.data) {
        for (const o of props.orders.data) {
            counts[o.status] = (counts[o.status] || 0) + 1;
        }
    }
    return pipeline.map(p => ({
        ...p,
        iconPath: pipelineIcons[p.key],
        count: props.stats?.[`count_${p.key}`] ?? counts[p.key] ?? 0,
        active: statusFilter.value === p.key,
    }));
});

function filterByPipelineStatus(key) {
    statusFilter.value = statusFilter.value === key ? '' : key;
}

/* ── Next Status Helper for Inline Actions ───────────── */
const nextStatusMap = {
    ordered: 'in_production',
    in_production: 'ready',
    ready: 'delivered',
    delivered: 'completed',
};

function getNextStatus(status) { return nextStatusMap[status] || null; }

function advanceOrderStatus(orderId, currentStatus) {
    const next = getNextStatus(currentStatus);
    if (next) updateOrderStatus(orderId, next);
}

function clearFilters() {
    statusFilter.value = '';
    overdueOnly.value = false;
    dateFrom.value = '';
    dateTo.value = '';
}

/* ── Stat cards ────────────────────────────────────────── */
const statCards = computed(() => [
    {
        label: locale.value === 'ar' ? 'طلبات قيد الانتظار' : 'Pending Orders',
        value: props.stats?.total_pending || 0,
        gradient: 'from-amber-500 to-amber-600',
        lightBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        icon: 'pending',
    },
    {
        label: locale.value === 'ar' ? 'طلبات متاخرة' : 'Overdue Orders',
        value: props.stats?.overdue || 0,
        gradient: 'from-red-500 to-red-600',
        lightBg: 'bg-red-50',
        iconColor: 'text-red-500',
        icon: 'overdue',
        pulse: (props.stats?.overdue || 0) > 0,
    },
    {
        label: locale.value === 'ar' ? 'تكلفة هذا الشهر' : 'This Month Cost',
        value: formatCurrency(props.stats?.this_month_cost || 0),
        gradient: 'from-cyan-500 to-cyan-600',
        lightBg: 'bg-cyan-50',
        iconColor: 'text-cyan-500',
        icon: 'cost',
    },
]);

/* ── Bulk Selection ────────────────────────────────────── */
const selectedIds = ref([]);
const bulkStatus = ref('');
const bulkProcessing = ref(false);
const showBulkStatusModal = ref(false);

const allSelected = computed({
    get: () => props.orders?.data?.length > 0 && selectedIds.value.length === props.orders.data.length,
    set: (val) => {
        selectedIds.value = val ? props.orders.data.map(o => o.id) : [];
    },
});

const someSelected = computed(() => selectedIds.value.length > 0 && selectedIds.value.length < (props.orders?.data?.length || 0));

function toggleOrder(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
}

function clearSelection() {
    selectedIds.value = [];
}

/* Bulk Actions */
function openBulkStatusModal() {
    bulkStatus.value = '';
    showBulkStatusModal.value = true;
}

function executeBulkStatus() {
    if (!bulkStatus.value || !selectedIds.value.length) return;
    bulkProcessing.value = true;
    router.post('/admin/dental/lab-orders-bulk/update-status', {
        order_ids: selectedIds.value,
        status: bulkStatus.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            bulkProcessing.value = false;
            showBulkStatusModal.value = false;
            selectedIds.value = [];
        },
    });
}

function executeBulkSms() {
    if (!selectedIds.value.length) return;
    if (!confirm(locale.value === 'ar'
        ? `هل تريد إرسال SMS لـ ${selectedIds.value.length} مريض؟`
        : `Send SMS to ${selectedIds.value.length} patients?`
    )) return;
    bulkProcessing.value = true;
    router.post('/admin/dental/lab-orders-bulk/sms-notify', {
        order_ids: selectedIds.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            bulkProcessing.value = false;
            selectedIds.value = [];
        },
    });
}

function executeBulkPrint() {
    if (!selectedIds.value.length) return;
    const ids = selectedIds.value.join(',');
    window.open(`/admin/dental/lab-orders-bulk/print?order_ids=${ids}`, '_blank');
}
</script>

<template>
    <AdminLayout :title="$t('a_lab_orders')">
        <div class="lo-page-wrapper">
            <!-- ── Hero Header ───────────────────────────────────── -->
            <div class="lo-hero">
                <!-- Animated glow orbs -->
                <div class="lo-hero-orb lo-hero-orb--1"></div>
                <div class="lo-hero-orb lo-hero-orb--2"></div>
                <div class="lo-hero-orb lo-hero-orb--3"></div>
                <!-- Dot pattern overlay -->
                <div class="lo-hero-dots"></div>
                <!-- Floating icon -->
                <div class="lo-hero-floating-icon">
                    <svg class="w-20 h-20" fill="none" stroke="currentColor" stroke-width="0.5" viewBox="0 0 24 24" style="opacity: 0.08; color: white;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>

                <div class="lo-hero-content">
                    <div class="lo-hero-left">
                        <div class="lo-hero-icon-badge">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                        </div>
                        <div>
                            <h1 class="lo-hero-title">{{ $t('a_lab_orders') }}</h1>
                            <p class="lo-hero-subtitle">{{ $t('a_lab_orders_desc') }}</p>
                        </div>
                    </div>
                    <div class="lo-hero-actions">
                        <Link
                            href="/admin/dental/lab-orders/profitability"
                            class="lo-hero-link"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ locale === 'ar' ? 'تحليل الربحية' : 'Profitability' }}
                        </Link>
                        <Link
                            href="/admin/dental/lab-orders/dashboard"
                            class="lo-hero-link"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            {{ locale === 'ar' ? 'لوحة التحكم' : 'Dashboard' }}
                        </Link>
                        <Link
                            href="/admin/dental/lab-orders/create"
                            class="lo-hero-cta"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ $t('a_create_order') }}
                            <span class="lo-shimmer"></span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Stat Cards ────────────────────────────────────── -->
            <div class="lo-stats-grid">
                <div
                    v-for="(card, index) in statCards"
                    :key="card.label"
                    class="lo-stat-card lo-reveal"
                    :style="{ animationDelay: `${0.15 + index * 0.1}s` }"
                >
                    <div :class="`lo-stat-bar bg-gradient-to-r ${card.gradient}`"></div>
                    <div class="lo-stat-content">
                        <div>
                            <p class="lo-stat-label">{{ card.label }}</p>
                            <p class="lo-stat-value">{{ card.value }}</p>
                        </div>
                        <div class="lo-stat-icon-wrapper" :class="[`bg-gradient-to-br ${card.gradient}`]">
                            <svg v-if="card.icon === 'pending'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <div v-else-if="card.icon === 'overdue'" class="relative">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                <span v-if="card.pulse" class="lo-pulse-dot"></span>
                            </div>
                            <svg v-else-if="card.icon === 'cost'" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Visual Status Pipeline ────────────────────────── -->
            <div class="lo-glass-card lo-reveal" style="animation-delay: 0.35s">
                <div class="lo-section-header">
                    <div class="lo-section-badge">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </div>
                    <span class="lo-section-title">{{ locale === 'ar' ? 'مسار الطلب' : 'Order Pipeline' }}</span>
                    <span v-if="statusFilter" class="lo-section-clear" @click="statusFilter = ''">{{ locale === 'ar' ? 'عرض الكل' : 'Show all' }}</span>
                </div>
                <div class="lo-pipeline">
                    <template v-for="(step, i) in statusPipeline" :key="step.key">
                        <button
                            @click="filterByPipelineStatus(step.key)"
                            class="lo-pipeline-step"
                            :class="[
                                step.active
                                    ? `lo-pipeline-step--active border-${step.color}-400 bg-${step.color}-50`
                                    : step.count > 0
                                        ? 'lo-pipeline-step--has-count'
                                        : 'lo-pipeline-step--empty'
                            ]"
                        >
                            <div class="lo-pipeline-icon" :class="step.active ? `bg-${step.color}-100 text-${step.color}-600` : 'bg-gray-100 text-gray-400'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="step.iconPath" /></svg>
                            </div>
                            <span class="lo-pipeline-count" :class="step.active ? `text-${step.color}-700` : step.count > 0 ? 'text-gray-800' : 'text-gray-300'">{{ step.count }}</span>
                            <span class="lo-pipeline-label" :class="step.active ? `text-${step.color}-600` : 'text-gray-400'">{{ $t('a_lab_status_' + step.key) }}</span>
                        </button>
                        <!-- Arrow connector -->
                        <div v-if="i < statusPipeline.length - 1" class="lo-pipeline-arrow">
                            <svg class="w-4 h-4 text-gray-300 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ── Search + Filters ──────────────────────────────── -->
            <div class="lo-glass-card lo-reveal" style="animation-delay: 0.4s">
                <div class="lo-filters-top">
                    <div class="lo-input-wrapper lo-input-wrapper--search">
                        <svg class="w-4.5 h-4.5 lo-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="$t('a_search_patient_lab_tooth')"
                            class="lo-input-field"
                        />
                    </div>
                    <!-- Overdue toggle -->
                    <button
                        @click="overdueOnly = !overdueOnly"
                        class="lo-filter-btn"
                        :class="overdueOnly ? 'lo-filter-btn--active-red' : ''"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                        {{ $t('a_overdue_only') }}
                    </button>
                    <button
                        @click="showFilters = !showFilters"
                        class="lo-filter-btn"
                        :class="showFilters || hasActiveFilters ? 'lo-filter-btn--active-cyan' : ''"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        {{ locale === 'ar' ? 'فلاتر' : 'Filters' }}
                    </button>
                </div>

                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 max-h-0"
                    enter-to-class="opacity-100 max-h-40"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 max-h-40"
                    leave-to-class="opacity-0 max-h-0"
                >
                    <div v-if="showFilters" class="lo-filters-expanded">
                        <div class="lo-filters-grid">
                            <div class="lo-input-wrapper">
                                <svg class="w-4 h-4 lo-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                                <select v-model="statusFilter" class="lo-input-field lo-select-field">
                                    <option value="">{{ $t('a_all_statuses') }}</option>
                                    <option v-for="s in statusOptions" :key="s" :value="s">{{ $t('a_lab_status_' + s) }}</option>
                                </select>
                            </div>
                            <div class="lo-date-range">
                                <div class="lo-input-wrapper">
                                    <svg class="w-4 h-4 lo-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <input v-model="dateFrom" type="date" class="lo-input-field" />
                                </div>
                                <span class="lo-date-sep">-</span>
                                <div class="lo-input-wrapper">
                                    <svg class="w-4 h-4 lo-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <input v-model="dateTo" type="date" class="lo-input-field" />
                                </div>
                            </div>
                            <div v-if="hasActiveFilters" class="lo-clear-filters">
                                <button @click="clearFilters" class="lo-clear-btn">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ locale === 'ar' ? 'مسح الفلاتر' : 'Clear filters' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- ── Bulk Action Bar ─────────────────────────────────── -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-3"
            >
                <div v-if="selectedIds.length > 0" class="lo-bulk-bar">
                    <div class="lo-bulk-inner">
                        <div class="lo-bulk-info">
                            <div class="lo-bulk-count-badge">
                                <span class="text-white font-bold text-sm">{{ selectedIds.length }}</span>
                            </div>
                            <p class="text-white font-medium text-sm">
                                {{ locale === 'ar' ? `${selectedIds.length} طلب محدد` : `${selectedIds.length} selected` }}
                            </p>
                            <button @click="clearSelection" class="lo-bulk-clear">
                                {{ locale === 'ar' ? 'إلغاء التحديد' : 'Clear' }}
                            </button>
                        </div>
                        <div class="lo-bulk-actions">
                            <!-- Bulk Status Change -->
                            <button @click="openBulkStatusModal" class="lo-bulk-btn lo-bulk-btn--primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                {{ locale === 'ar' ? 'تغيير الحالة' : 'Change Status' }}
                            </button>
                            <!-- Bulk SMS -->
                            <button @click="executeBulkSms" :disabled="bulkProcessing" class="lo-bulk-btn lo-bulk-btn--sms">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                                {{ locale === 'ar' ? 'إرسال SMS' : 'Send SMS' }}
                            </button>
                            <!-- Bulk Print -->
                            <button @click="executeBulkPrint" class="lo-bulk-btn lo-bulk-btn--ghost">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                {{ locale === 'ar' ? 'طباعة' : 'Print' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── Table ─────────────────────────────────────────── -->
            <div class="lo-glass-card lo-glass-card--table lo-reveal" style="animation-delay: 0.5s">
                <div class="lo-table-wrap">
                    <table class="lo-table">
                        <thead>
                            <tr class="lo-table-head-row">
                                <th class="lo-th lo-th--check">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="allSelected" :indeterminate="someSelected"
                                            class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500 cursor-pointer" />
                                    </label>
                                </th>
                                <th class="lo-th">{{ $t('a_patient') }}</th>
                                <th class="lo-th">{{ $t('a_item_type') }}</th>
                                <th class="lo-th lo-th--center">{{ $t('a_tooth') }}</th>
                                <th class="lo-th">{{ $t('a_shade') }}</th>
                                <th class="lo-th">{{ $t('a_material') }}</th>
                                <th class="lo-th">{{ $t('a_lab_name') }}</th>
                                <th class="lo-th lo-th--center">{{ $t('a_status') }}</th>
                                <th class="lo-th">{{ $t('a_expected_date') }}</th>
                                <th class="lo-th">{{ $t('a_cost') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(order, idx) in orders.data"
                                :key="order.id"
                                class="lo-table-row lo-row-reveal"
                                :class="[
                                    isOverdue(order) ? 'lo-row--overdue' : '',
                                    selectedIds.includes(order.id) ? 'lo-row--selected' : '',
                                ]"
                                :style="{ animationDelay: `${0.55 + idx * 0.04}s` }"
                            >
                                <td class="lo-td lo-td--check">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" :checked="selectedIds.includes(order.id)"
                                            @change="toggleOrder(order.id)"
                                            class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500 cursor-pointer" />
                                    </label>
                                </td>
                                <td class="lo-td">
                                    <Link v-if="order.patient" :href="`/admin/patients/${order.patient.id}`" class="lo-patient-link">
                                        {{ order.patient.full_name }}
                                    </Link>
                                    <div class="lo-file-number">{{ order.patient?.file_number }}</div>
                                </td>
                                <td class="lo-td lo-td--text">{{ $t('a_lab_' + (order.item_type || 'other')) }}</td>
                                <td class="lo-td lo-td--center">
                                    <span v-if="order.tooth_number" class="lo-tooth-badge">
                                        {{ order.tooth_number }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="lo-td lo-td--text">{{ order.shade || '-' }}</td>
                                <td class="lo-td lo-td--text">{{ order.material || '-' }}</td>
                                <td class="lo-td lo-td--text">{{ order.lab_name || '-' }}</td>
                                <td class="lo-td lo-td--center">
                                    <div class="lo-status-cell">
                                        <select
                                            :value="order.status"
                                            @change="updateOrderStatus(order.id, $event.target.value)"
                                            :class="[getStatus(order.status).bg, getStatus(order.status).text]"
                                            class="lo-status-select"
                                        >
                                            <option v-for="s in statusOptions" :key="s" :value="s">{{ $t('a_lab_status_' + s) }}</option>
                                        </select>
                                        <!-- Quick Advance Button -->
                                        <button v-if="getNextStatus(order.status)"
                                            @click="advanceOrderStatus(order.id, order.status)"
                                            class="lo-advance-btn"
                                            :title="(locale === 'ar' ? 'نقل إلى: ' : 'Move to: ') + $t('a_lab_status_' + getNextStatus(order.status))">
                                            <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="lo-td">
                                    <span :class="{ 'lo-date--overdue': isOverdue(order), 'lo-date--normal': !isOverdue(order) }">
                                        {{ formatDate(order.expected_date) }}
                                    </span>
                                    <div v-if="isOverdue(order)" class="lo-overdue-indicator">
                                        <span class="lo-overdue-dot"></span>
                                        <span class="lo-overdue-text">{{ $t('a_overdue') }}</span>
                                    </div>
                                </td>
                                <td class="lo-td lo-td--cost">{{ formatCurrency(order.cost) }}</td>
                            </tr>
                            <tr v-if="!orders.data || orders.data.length === 0">
                                <td colspan="10" class="lo-empty-state">
                                    <div class="lo-empty-inner">
                                        <div class="lo-empty-icon">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                        </div>
                                        <p class="lo-empty-text">{{ $t('a_no_lab_orders') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.links && orders.links.length > 3" class="lo-pagination">
                    <p class="lo-pagination-info">{{ $t('a_showing') }} {{ orders.from }} {{ $t('a_to') }} {{ orders.to }} {{ $t('a_of') }} {{ orders.total }} {{ $t('a_results') }}</p>
                    <nav class="lo-pagination-nav">
                        <template v-for="link in orders.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="lo-page-link"
                                :class="link.active ? 'lo-page-link--active' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="lo-page-link lo-page-link--disabled" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- ── Bulk Status Modal ─────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showBulkStatusModal" class="lo-modal-overlay">
                    <div class="lo-modal-backdrop" @click="showBulkStatusModal = false"></div>
                    <div class="lo-modal-card">
                        <div class="lo-modal-header">
                            <div class="lo-modal-icon">
                                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </div>
                            <div>
                                <h3 class="lo-modal-title">{{ locale === 'ar' ? 'تغيير الحالة دفعة واحدة' : 'Bulk Status Change' }}</h3>
                                <p class="lo-modal-subtitle">{{ locale === 'ar' ? `${selectedIds.length} طلب محدد` : `${selectedIds.length} orders selected` }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="lo-modal-label">{{ locale === 'ar' ? 'الحالة الجديدة' : 'New Status' }}</label>
                            <div class="lo-modal-status-grid">
                                <button v-for="s in ['ordered', 'in_production', 'ready', 'delivered', 'adjustment', 'completed']" :key="s"
                                    @click="bulkStatus = s"
                                    class="lo-modal-status-btn"
                                    :class="bulkStatus === s ? 'lo-modal-status-btn--active' : ''"
                                >
                                    <span class="lo-modal-status-dot" :class="getStatus(s).dot"></span>
                                    {{ $t('a_lab_status_' + s) }}
                                </button>
                            </div>
                        </div>

                        <!-- Warning for delivered/completed -->
                        <div v-if="bulkStatus === 'delivered' || bulkStatus === 'completed'" class="lo-modal-warning">
                            <div class="lo-modal-warning-inner">
                                <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                <p class="lo-modal-warning-text">
                                    {{ locale === 'ar'
                                        ? 'سيتم إضافة تكلفة المعمل تلقائياً إلى فاتورة المريض للطلبات المؤهلة'
                                        : 'Lab charges will be automatically added to patient invoices for eligible orders' }}
                                </p>
                            </div>
                        </div>

                        <div class="lo-modal-actions">
                            <button @click="showBulkStatusModal = false" class="lo-modal-cancel">
                                {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button @click="executeBulkStatus" :disabled="!bulkStatus || bulkProcessing" class="lo-modal-confirm">
                                {{ bulkProcessing
                                    ? (locale === 'ar' ? 'جاري التحديث...' : 'Updating...')
                                    : (locale === 'ar' ? 'تحديث الحالة' : 'Update Status') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
/* ─── Keyframes ──────────────────────────────────────── */
@keyframes loReveal {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes loRowReveal {
    from { opacity: 0; transform: translateX(12px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes loOrbFloat1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(30px, -20px) scale(1.1); }
}
@keyframes loOrbFloat2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-20px, 15px) scale(1.15); }
}
@keyframes loOrbFloat3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(15px, 25px) scale(1.05); }
}
@keyframes loShimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
@keyframes loFloatIcon {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(3deg); }
}
@keyframes loPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.5); }
}

[dir="rtl"] .lo-row-reveal { animation-name: loRowRevealRtl; }
@keyframes loRowRevealRtl {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
}

/* ─── Page Wrapper ───────────────────────────────────── */
.lo-page-wrapper {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* ─── Animations ─────────────────────────────────────── */
.lo-reveal {
    animation: loReveal 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.lo-row-reveal {
    animation: loRowReveal 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* ─── Hero ───────────────────────────────────────────── */
.lo-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.5rem;
    background: linear-gradient(135deg, #0f172a 0%, #164e63 50%, #0e7490 100%);
    padding: 2rem 2rem;
    animation: loReveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.lo-hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    pointer-events: none;
}
.lo-hero-orb--1 {
    top: -4rem;
    right: -4rem;
    width: 16rem;
    height: 16rem;
    background: rgba(6, 182, 212, 0.25);
    animation: loOrbFloat1 8s ease-in-out infinite;
}
[dir="rtl"] .lo-hero-orb--1 { right: auto; left: -4rem; }

.lo-hero-orb--2 {
    bottom: -3rem;
    left: -3rem;
    width: 12rem;
    height: 12rem;
    background: rgba(20, 184, 166, 0.18);
    animation: loOrbFloat2 10s ease-in-out infinite;
}
[dir="rtl"] .lo-hero-orb--2 { left: auto; right: -3rem; }

.lo-hero-orb--3 {
    top: 50%;
    left: 40%;
    width: 8rem;
    height: 8rem;
    background: rgba(34, 211, 238, 0.1);
    animation: loOrbFloat3 12s ease-in-out infinite;
}

.lo-hero-dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.07) 1px, transparent 1px);
    background-size: 20px 20px;
    pointer-events: none;
}

.lo-hero-floating-icon {
    position: absolute;
    bottom: -1rem;
    right: 2rem;
    animation: loFloatIcon 6s ease-in-out infinite;
    pointer-events: none;
}
[dir="rtl"] .lo-hero-floating-icon { right: auto; left: 2rem; }

.lo-hero-content {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
@media (min-width: 640px) {
    .lo-hero-content {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.lo-hero-left {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.lo-hero-icon-badge {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.875rem;
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.4), rgba(20, 184, 166, 0.3));
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 20px rgba(6, 182, 212, 0.2);
}

.lo-hero-title {
    font-size: 1.375rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.3;
}
@media (min-width: 768px) { .lo-hero-title { font-size: 1.625rem; } }

.lo-hero-subtitle {
    color: rgba(207, 250, 254, 0.7);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.lo-hero-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.lo-hero-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.875rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
}
.lo-hero-link:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.1);
}

.lo-hero-cta {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.5rem;
    border-radius: 0.875rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #155e75;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}
.lo-hero-cta:hover {
    background: #fff;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.lo-shimmer {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(6, 182, 212, 0.1), transparent);
    animation: loShimmer 3s ease-in-out infinite;
    pointer-events: none;
}

/* ─── Stat Cards ─────────────────────────────────────── */
.lo-stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}
@media (min-width: 768px) { .lo-stats-grid { grid-template-columns: repeat(3, 1fr); } }

.lo-stat-card {
    position: relative;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border-radius: 1.5rem;
    padding: 1.5rem;
    border: 1px solid rgba(243, 244, 246, 0.8);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.02);
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}
.lo-stat-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06), 0 2px 8px rgba(0, 0, 0, 0.04);
    border-color: rgba(229, 231, 235, 0.9);
    transform: translateY(-2px);
}

.lo-stat-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0.9;
    border-radius: 1.5rem 1.5rem 0 0;
}

.lo-stat-content {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.lo-stat-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #6b7280;
}

.lo-stat-value {
    font-size: 1.625rem;
    font-weight: 700;
    color: #111827;
    margin-top: 0.625rem;
    font-variant-numeric: tabular-nums;
}

.lo-stat-icon-wrapper {
    width: 3rem;
    height: 3rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.lo-stat-card:hover .lo-stat-icon-wrapper {
    transform: scale(1.1);
}

.lo-pulse-dot {
    position: absolute;
    top: -0.25rem;
    right: -0.25rem;
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
    background: #ef4444;
    animation: loPulse 2s ease-in-out infinite;
}

/* ─── Glass Card ─────────────────────────────────────── */
.lo-glass-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border-radius: 1.5rem;
    border: 1px solid rgba(243, 244, 246, 0.8);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.02);
    padding: 1.5rem;
    overflow: hidden;
}
.lo-glass-card--table {
    padding: 0;
}

/* ─── Section Header ─────────────────────────────────── */
.lo-section-header {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    margin-bottom: 1rem;
}

.lo-section-badge {
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, #06b6d4, #14b8a6);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(6, 182, 212, 0.3);
}

.lo-section-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.lo-section-clear {
    margin-inline-start: auto;
    font-size: 0.6875rem;
    color: #0891b2;
    cursor: pointer;
    transition: color 0.2s;
}
.lo-section-clear:hover { color: #0e7490; text-decoration: underline; }

/* ─── Pipeline ───────────────────────────────────────── */
.lo-pipeline {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.lo-pipeline-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.875rem 0.5rem;
    border-radius: 1rem;
    border: 2px solid transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    background: transparent;
}
.lo-pipeline-step--has-count {
    border-color: #f3f4f6;
    background: rgba(249, 250, 251, 0.5);
}
.lo-pipeline-step--has-count:hover {
    border-color: #e5e7eb;
    background: #f9fafb;
}
.lo-pipeline-step--empty {
    border-color: #fafafa;
    opacity: 0.5;
}
.lo-pipeline-step--empty:hover {
    opacity: 0.75;
}
.lo-pipeline-step--active {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.lo-pipeline-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.375rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.lo-pipeline-count {
    font-size: 1.25rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.lo-pipeline-label {
    font-size: 0.625rem;
    font-weight: 500;
    margin-top: 0.125rem;
}

.lo-pipeline-arrow {
    flex-shrink: 0;
    width: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ─── Filters ────────────────────────────────────────── */
.lo-filters-top {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.lo-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.125rem;
    border: 2px solid #f3f4f6;
    border-radius: 1rem;
    font-size: 0.875rem;
    background-color: rgba(249, 250, 251, 0.5);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.lo-input-wrapper:focus-within {
    background-color: #fff;
    border-color: #67e8f9;
    box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.15);
}
.lo-input-wrapper--search {
    flex: 1;
}

.lo-input-icon {
    color: #9ca3af;
    flex-shrink: 0;
    transition: color 0.2s;
}
.lo-input-wrapper:focus-within .lo-input-icon {
    color: #06b6d4;
}

.lo-input-field {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 0.875rem;
    color: #111827;
    min-width: 0;
}
.lo-input-field::placeholder {
    color: #9ca3af;
}

.lo-select-field {
    cursor: pointer;
    appearance: auto;
}

.lo-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.125rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 2px solid #f3f4f6;
    background: rgba(249, 250, 251, 0.5);
    color: #4b5563;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}
.lo-filter-btn:hover {
    border-color: #e5e7eb;
    background: #f9fafb;
}
.lo-filter-btn--active-red {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
}
.lo-filter-btn--active-cyan {
    background: #ecfeff;
    border-color: #a5f3fc;
    color: #0e7490;
}

.lo-filters-expanded {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
    overflow: hidden;
}

.lo-filters-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
}
@media (min-width: 640px) { .lo-filters-grid { grid-template-columns: 1fr 2fr auto; } }

.lo-date-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.lo-date-range .lo-input-wrapper { flex: 1; }

.lo-date-sep {
    color: #d1d5db;
    font-weight: 500;
}

.lo-clear-filters {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.lo-clear-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #ef4444;
    font-weight: 500;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
}
.lo-clear-btn:hover {
    color: #b91c1c;
    background: #fef2f2;
}

/* ─── Bulk Action Bar ────────────────────────────────── */
.lo-bulk-bar {
    position: sticky;
    top: 1rem;
    z-index: 30;
    border-radius: 1.5rem;
    background: linear-gradient(135deg, #0891b2, #0d9488);
    box-shadow: 0 12px 32px rgba(6, 182, 212, 0.3), 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 1rem 1.25rem;
}

.lo-bulk-inner {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.lo-bulk-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.lo-bulk-count-badge {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.lo-bulk-clear {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    text-decoration: underline;
    text-underline-offset: 2px;
    cursor: pointer;
    background: none;
    border: none;
    transition: color 0.2s;
}
.lo-bulk-clear:hover { color: #fff; }

.lo-bulk-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.lo-bulk-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.lo-bulk-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.lo-bulk-btn--primary {
    background: #fff;
    color: #0e7490;
}
.lo-bulk-btn--primary:hover { background: #ecfeff; }

.lo-bulk-btn--sms {
    background: #10b981;
    color: #fff;
}
.lo-bulk-btn--sms:hover { background: #059669; }

.lo-bulk-btn--ghost {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    box-shadow: none;
}
.lo-bulk-btn--ghost:hover { background: rgba(255, 255, 255, 0.3); }

/* ─── Table ──────────────────────────────────────────── */
.lo-table-wrap {
    overflow-x: auto;
}

.lo-table {
    min-width: 100%;
    border-collapse: collapse;
}

.lo-table-head-row {
    border-bottom: 2px solid #f3f4f6;
}

.lo-th {
    padding: 0.875rem 1.25rem;
    text-align: start;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.lo-th--center { text-align: center; }
.lo-th--check { text-align: center; width: 3rem; }

.lo-table-row {
    border-bottom: 1px solid rgba(243, 244, 246, 0.6);
    transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.lo-table-row:hover { background: rgba(236, 254, 255, 0.3); }
.lo-row--overdue { background: rgba(254, 242, 242, 0.4); }
.lo-row--overdue:hover { background: rgba(254, 242, 242, 0.6); }
.lo-row--selected { background: rgba(236, 254, 255, 0.5); }

.lo-td {
    padding: 1rem 1.25rem;
    font-size: 0.875rem;
}
.lo-td--check { text-align: center; }
.lo-td--center { text-align: center; }
.lo-td--text { color: #4b5563; }
.lo-td--cost { font-weight: 600; color: #111827; }

.lo-patient-link {
    font-weight: 500;
    color: #111827;
    transition: color 0.2s;
}
.lo-patient-link:hover { color: #0891b2; }

.lo-file-number {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.125rem;
    font-family: ui-monospace, monospace;
}

.lo-tooth-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.625rem;
    background: #ecfeff;
    color: #0e7490;
    font-family: ui-monospace, monospace;
    font-weight: 700;
    font-size: 0.875rem;
}

.lo-status-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.lo-status-select {
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.lo-status-select:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(34, 211, 238, 0.3);
}

.lo-advance-btn {
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.5rem;
    background: #ecfeff;
    color: #0891b2;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.lo-advance-btn:hover {
    background: #cffafe;
    color: #0e7490;
}
.lo-advance-btn:hover svg {
    transform: translateX(2px);
}
[dir="rtl"] .lo-advance-btn:hover svg {
    transform: translateX(-2px);
}

.lo-date--overdue {
    color: #dc2626;
    font-weight: 600;
}
.lo-date--normal {
    color: #6b7280;
}

.lo-overdue-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.25rem;
}

.lo-overdue-dot {
    width: 0.375rem;
    height: 0.375rem;
    border-radius: 50%;
    background: #ef4444;
    animation: loPulse 2s ease-in-out infinite;
}

.lo-overdue-text {
    font-size: 0.75rem;
    color: #ef4444;
    font-weight: 500;
}

/* ─── Empty State ────────────────────────────────────── */
.lo-empty-state {
    padding: 4rem 1.5rem;
    text-align: center;
}

.lo-empty-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.lo-empty-icon {
    width: 4rem;
    height: 4rem;
    border-radius: 1.25rem;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.lo-empty-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: #9ca3af;
}

/* ─── Pagination ─────────────────────────────────────── */
.lo-pagination {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.lo-pagination-info {
    font-size: 0.875rem;
    color: #6b7280;
}

.lo-pagination-nav {
    display: flex;
    gap: 0.25rem;
}
[dir="rtl"] .lo-pagination-nav { direction: rtl; }

.lo-page-link {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    color: #4b5563;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
}
.lo-page-link:hover {
    background: #ecfeff;
    border-color: #a5f3fc;
    color: #0e7490;
}
.lo-page-link--active {
    background: #0891b2;
    color: #fff;
    border-color: transparent;
    box-shadow: 0 2px 4px rgba(8, 145, 178, 0.3);
}
.lo-page-link--active:hover {
    background: #0891b2;
    color: #fff;
}
.lo-page-link--disabled {
    color: #9ca3af;
    border-color: transparent;
    cursor: default;
}
.lo-page-link--disabled:hover {
    background: transparent;
    color: #9ca3af;
}

/* ─── Modal ──────────────────────────────────────────── */
.lo-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.lo-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
}

.lo-modal-card {
    position: relative;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(16px);
    border-radius: 1.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
    width: 100%;
    max-width: 28rem;
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    z-index: 10;
}

.lo-modal-header {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.lo-modal-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.875rem;
    background: linear-gradient(135deg, #ecfeff, #cffafe);
    display: flex;
    align-items: center;
    justify-content: center;
}

.lo-modal-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
}

.lo-modal-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
}

.lo-modal-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.625rem;
    display: block;
}

.lo-modal-status-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.lo-modal-status-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 0.875rem;
    border-radius: 0.875rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 2px solid #e5e7eb;
    background: #fff;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.lo-modal-status-btn:hover {
    border-color: #d1d5db;
    background: #f9fafb;
}
.lo-modal-status-btn--active {
    border-color: #06b6d4;
    background: #ecfeff;
    color: #0e7490;
}

.lo-modal-status-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
}

.lo-modal-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.875rem;
    padding: 0.875rem;
}

.lo-modal-warning-inner {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.lo-modal-warning-text {
    font-size: 0.75rem;
    color: #92400e;
}

.lo-modal-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 0.5rem;
}

.lo-modal-cancel {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
    background: #f3f4f6;
    border-radius: 0.875rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.lo-modal-cancel:hover { background: #e5e7eb; }

.lo-modal-confirm {
    padding: 0.625rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #06b6d4, #14b8a6);
    border-radius: 0.875rem;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.lo-modal-confirm:hover {
    box-shadow: 0 6px 16px rgba(6, 182, 212, 0.4);
    transform: translateY(-1px);
}
.lo-modal-confirm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
</style>
