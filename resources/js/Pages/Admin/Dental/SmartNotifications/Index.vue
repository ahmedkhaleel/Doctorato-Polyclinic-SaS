<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

const props = defineProps({
    notifications: Object,
    filters: Object,
    stats: Object,
    types: Object,
});

// ── Filters ──────────────────────────────────────────
const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const statusFilter = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const showFilters = ref(false);

let searchTimeout = null;
function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/dental/smart-notifications', {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            status: statusFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
}
watch([search, typeFilter, statusFilter, dateFrom, dateTo], applyFilters);

const hasActiveFilters = computed(() => typeFilter.value || statusFilter.value || dateFrom.value || dateTo.value);
function clearFilters() {
    typeFilter.value = '';
    statusFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}

// ── Type config ──────────────────────────────────────
const typeConfig = {
    followup_reminder: {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />`,
        gradient: 'from-[#1B365D] to-[#1B365D]',
        bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200',
        dot: 'bg-[#1B365D]',
    },
    lab_order_ready: {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />`,
        gradient: 'from-emerald-500 to-emerald-500',
        bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200',
        dot: 'bg-emerald-500',
    },
    stalled_plan_reminder: {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />`,
        gradient: 'from-amber-500 to-[#C4A265]',
        bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200',
        dot: 'bg-amber-500',
    },
    post_treatment_checkup: {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />`,
        gradient: 'from-[#C4A265] to-[#C4A265]',
        bg: 'bg-[#F5E7C8]/40', text: 'text-[#8B7043]', border: 'border-[#F5E7C8]',
        dot: 'bg-[#C4A265]',
    },
};

const statusConfig = {
    pending:   { bg: 'bg-gray-50',    text: 'text-gray-700',    dot: 'bg-gray-400',    label_ar: 'قيد الانتظار', label_en: 'Pending' },
    sent:      { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label_ar: 'تم الإرسال',  label_en: 'Sent' },
    delivered: { bg: 'bg-slate-50',    text: 'text-[#1B365D]',    dot: 'bg-[#1B365D]',    label_ar: 'تم التسليم',  label_en: 'Delivered' },
    failed:    { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     label_ar: 'فشل',         label_en: 'Failed' },
    cancelled: { bg: 'bg-gray-50',    text: 'text-gray-500',    dot: 'bg-gray-300',    label_ar: 'ملغي',        label_en: 'Cancelled' },
};

function getTypeLabel(type) {
    if (!props.types[type]) return type;
    return locale.value === 'ar' ? props.types[type].ar : props.types[type].en;
}
function getStatusLabel(status) {
    const s = statusConfig[status];
    return s ? (locale.value === 'ar' ? s.label_ar : s.label_en) : status;
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatDateShort(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
}

// ── Actions ──────────────────────────────────────────
const scanning = ref(false);
function triggerScan() {
    scanning.value = true;
    router.post('/admin/dental/smart-notifications/scan', {}, {
        preserveState: true,
        onFinish: () => { scanning.value = false; },
    });
}

// ─── Confirm Modals ─────────────────────────────────
const showResendConfirm = ref(false);
const showCancelConfirm = ref(false);
const showDeleteConfirm = ref(false);
const pendingAction = ref(null);

function confirmResend(n) { pendingAction.value = n; showResendConfirm.value = true; }
function executeResend() {
    if (!pendingAction.value) return;
    router.post(`/admin/dental/smart-notifications/${pendingAction.value.id}/resend`, {}, { preserveState: true });
    showResendConfirm.value = false; pendingAction.value = null;
}

function confirmCancel(n) { pendingAction.value = n; showCancelConfirm.value = true; }
function executeCancel() {
    if (!pendingAction.value) return;
    router.post(`/admin/dental/smart-notifications/${pendingAction.value.id}/cancel`, {}, { preserveState: true });
    showCancelConfirm.value = false; pendingAction.value = null;
}

function markResponded(n) {
    router.post(`/admin/dental/smart-notifications/${n.id}/responded`, {}, { preserveState: true });
}

const deletingId = ref(null);
function confirmDelete(n) { pendingAction.value = n; showDeleteConfirm.value = true; }
function executeDelete() {
    if (!pendingAction.value) return;
    deletingId.value = pendingAction.value.id;
    router.post(`/admin/dental/smart-notifications/${pendingAction.value.id}/delete`, {
        preserveState: true,
        onFinish: () => { deletingId.value = null; },
    });
    showDeleteConfirm.value = false; pendingAction.value = null;
}

// ── Manual Send Modal ────────────────────────────────
const showManualModal = ref(false);
const manualForm = useForm({
    patient_id: '',
    type: 'post_treatment_checkup',
    message_ar: '',
    message_en: '',
});

const patients = ref([]);
const patientSearch = ref('');
let patientSearchTimeout = null;

function searchPatients() {
    clearTimeout(patientSearchTimeout);
    patientSearchTimeout = setTimeout(async () => {
        if (patientSearch.value.length < 2) { patients.value = []; return; }
        try {
            const response = await fetch(`/admin/patients/search?q=${encodeURIComponent(patientSearch.value)}&limit=10`);
            if (response.ok) {
                patients.value = await response.json();
            }
        } catch (e) {
            patients.value = [];
        }
    }, 300);
}

function selectPatient(p) {
    manualForm.patient_id = p.id;
    patientSearch.value = `${p.full_name} (${p.file_number})`;
    patients.value = [];
}

function submitManual() {
    manualForm.post('/admin/dental/smart-notifications/send', {
        preserveState: true,
        onSuccess: () => {
            showManualModal.value = false;
            manualForm.reset();
            patientSearch.value = '';
        },
    });
}

// ── Expanded row ─────────────────────────────────────
const expandedId = ref(null);
function toggleExpand(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

// ── Stats helpers ────────────────────────────────────
const responseRate = computed(() => {
    if (!props.stats?.sent || props.stats.sent === 0) return 0;
    return Math.round((props.stats.responded_count / props.stats.sent) * 100);
});
</script>

<template>
    <AdminLayout :title="locale === 'ar' ? 'تنبيهات المريض الذكية' : 'Smart Patient Notifications'">
        <div class="space-y-6">

            <!-- ── Hero Header ───────────────────────────────────── -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-7">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-[#2C4E7A]/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-emerald-300/15 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">
                                {{ locale === 'ar' ? 'تنبيهات المريض الذكية' : 'Smart Patient Notifications' }}
                            </h1>
                            <p class="text-slate-100/80 text-sm mt-0.5">
                                {{ locale === 'ar' ? 'متابعة، جاهزية معمل، خطط متوقفة، اطمئنان بعد العلاج' : 'Follow-ups, Lab Ready, Stalled Plans, Post-Treatment Checks' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <Link href="/admin/dental" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            {{ locale === 'ar' ? 'لوحة التحكم' : 'Dashboard' }}
                        </Link>
                        <button
                            @click="triggerScan"
                            :disabled="scanning"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-white/90 bg-white/10 hover:bg-white/20 border border-white/20 transition-all duration-200 disabled:opacity-50"
                        >
                            <svg :class="['w-4 h-4', scanning && 'animate-spin']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            {{ locale === 'ar' ? 'فحص وإرسال' : 'Scan & Send' }}
                        </button>
                        <button
                            @click="showManualModal = true"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-[#C4A265] hover:bg-[#D4B57E] shadow-lg hover:shadow-xl transition-all duration-300"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ locale === 'ar' ? 'إرسال يدوي' : 'Send Manual' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Stats Cards ───────────────────────────────────── -->
            <div class="dental-card-enter grid grid-cols-2 md:grid-cols-4 gap-4" style="animation-delay: 0.1s">
                <!-- By type cards -->
                <div v-for="(tKey, idx) in ['followup_reminder', 'lab_order_ready', 'stalled_plan_reminder', 'post_treatment_checkup']" :key="tKey"
                     class="relative overflow-hidden rounded-2xl p-5 bg-white border border-gray-100/80 shadow-sm group hover:shadow-md transition-all duration-300"
                >
                    <div :class="['absolute top-0 ltr:left-0 rtl:right-0 w-1 h-full bg-gradient-to-b', typeConfig[tKey].gradient]"></div>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">{{ getTypeLabel(tKey) }}</p>
                            <p class="text-2xl font-bold text-gray-800">{{ stats?.by_type?.[tKey] ?? 0 }}</p>
                        </div>
                        <div :class="['w-9 h-9 rounded-lg flex items-center justify-center', typeConfig[tKey].bg]">
                            <svg :class="['w-4.5 h-4.5', typeConfig[tKey].text]" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeConfig[tKey].icon"></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Summary Strip ─────────────────────────────────── -->
            <div class="dental-card-enter grid grid-cols-2 sm:grid-cols-5 gap-3" style="animation-delay: 0.15s">
                <div class="bg-white rounded-xl p-4 border border-gray-100 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ stats?.total ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ locale === 'ar' ? 'إجمالي' : 'Total' }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 text-center">
                    <p class="text-2xl font-bold text-amber-600">{{ stats?.pending ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ locale === 'ar' ? 'قيد الانتظار' : 'Pending' }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 text-center">
                    <p class="text-2xl font-bold text-emerald-600">{{ stats?.today_sent ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ locale === 'ar' ? 'أُرسل اليوم' : 'Sent Today' }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 text-center">
                    <p class="text-2xl font-bold text-[#1B365D]">{{ stats?.this_week_sent ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ locale === 'ar' ? 'هذا الأسبوع' : 'This Week' }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 text-center">
                    <p class="text-2xl font-bold text-[#1B365D]">{{ responseRate }}%</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ locale === 'ar' ? 'معدل الاستجابة' : 'Response Rate' }}</p>
                </div>
            </div>

            <!-- ── Search + Filters ──────────────────────────────── -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.2s">
                <div class="p-5">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="locale === 'ar' ? 'بحث بالاسم، رقم الملف، الهاتف...' : 'Search by name, file number, phone...'"
                                class="doctorato-input w-full ltr:pl-10 rtl:pr-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#2C4E7A] focus:ring-2 focus:ring-slate-100 text-sm transition-all"
                            />
                        </div>
                        <button @click="showFilters = !showFilters" :class="['inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium border transition-all', hasActiveFilters ? 'bg-slate-50 border-slate-200 text-[#1B365D]' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100']">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            {{ locale === 'ar' ? 'فلترة' : 'Filter' }}
                            <span v-if="hasActiveFilters" class="w-2 h-2 bg-[#1B365D] rounded-full"></span>
                        </button>
                    </div>

                    <!-- Filter Panel -->
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
                        <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <select v-model="typeFilter" class="doctorato-input rounded-xl border-gray-200 text-sm focus:border-[#2C4E7A] focus:ring-slate-100">
                                <option value="">{{ locale === 'ar' ? 'كل الأنواع' : 'All Types' }}</option>
                                <option v-for="(labels, key) in types" :key="key" :value="key">{{ locale === 'ar' ? labels.ar : labels.en }}</option>
                            </select>
                            <select v-model="statusFilter" class="doctorato-input rounded-xl border-gray-200 text-sm focus:border-[#2C4E7A] focus:ring-slate-100">
                                <option value="">{{ locale === 'ar' ? 'كل الحالات' : 'All Statuses' }}</option>
                                <option v-for="(s, key) in statusConfig" :key="key" :value="key">{{ locale === 'ar' ? s.label_ar : s.label_en }}</option>
                            </select>
                            <input v-model="dateFrom" type="date" class="doctorato-input rounded-xl border-gray-200 text-sm focus:border-[#2C4E7A] focus:ring-slate-100" :placeholder="locale === 'ar' ? 'من تاريخ' : 'From'" />
                            <div class="flex items-center gap-2">
                                <input v-model="dateTo" type="date" class="doctorato-input flex-1 rounded-xl border-gray-200 text-sm focus:border-[#2C4E7A] focus:ring-slate-100" :placeholder="locale === 'ar' ? 'إلى تاريخ' : 'To'" />
                                <button v-if="hasActiveFilters" @click="clearFilters" class="text-xs text-red-500 hover:text-red-700 whitespace-nowrap">
                                    {{ locale === 'ar' ? 'مسح' : 'Clear' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- ── Notifications Table ───────────────────────────── -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.25s">
                <div v-if="!notifications?.data?.length" class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-2xl mx-auto mb-4 flex items-center justify-center">
                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    </div>
                    <h3 class="text-gray-500 font-medium">{{ locale === 'ar' ? 'لا توجد تنبيهات' : 'No notifications yet' }}</h3>
                    <p class="text-gray-400 text-sm mt-1">{{ locale === 'ar' ? 'ستظهر هنا عند إرسال أو جدولة تنبيهات' : 'Notifications will appear here when sent or scheduled' }}</p>
                </div>

                <div v-else class="divide-y divide-gray-50">
                    <template v-for="n in notifications.data" :key="n.id">
                        <div
                            :class="['group relative', expandedId === n.id ? 'bg-gray-50/50' : 'hover:bg-gray-50/30']"
                            class="transition-colors duration-200"
                        >
                            <!-- Main Row -->
                            <div class="flex items-center gap-4 px-5 py-4 cursor-pointer" @click="toggleExpand(n.id)">
                                <!-- Type indicator -->
                                <div :class="['w-2 h-10 rounded-full flex-shrink-0 bg-gradient-to-b', typeConfig[n.type]?.gradient || 'from-gray-400 to-gray-500']"></div>

                                <!-- Type icon -->
                                <div :class="['w-9 h-9 rounded-lg flex-shrink-0 flex items-center justify-center', typeConfig[n.type]?.bg || 'bg-gray-50']">
                                    <svg :class="['w-4.5 h-4.5', typeConfig[n.type]?.text || 'text-gray-600']" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeConfig[n.type]?.icon || ''"></svg>
                                </div>

                                <!-- Patient & Type -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="font-semibold text-sm text-gray-800 truncate">{{ n.patient?.full_name || '-' }}</span>
                                        <span class="text-xs text-gray-400">{{ n.patient?.file_number }}</span>
                                        <span v-if="n.patient_responded" class="inline-flex items-center gap-0.5 text-xs text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            {{ locale === 'ar' ? 'استجاب' : 'Responded' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ getTypeLabel(n.type) }}</p>
                                </div>

                                <!-- Status badge -->
                                <div :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', statusConfig[n.status]?.bg, statusConfig[n.status]?.text]">
                                    <span :class="['w-1.5 h-1.5 rounded-full', statusConfig[n.status]?.dot]"></span>
                                    {{ getStatusLabel(n.status) }}
                                </div>

                                <!-- Date -->
                                <div class="text-xs text-gray-400 text-right whitespace-nowrap hidden sm:block">
                                    <div v-if="n.sent_at">{{ formatDateShort(n.sent_at) }}</div>
                                    <div v-else-if="n.scheduled_at" class="text-amber-500">
                                        {{ locale === 'ar' ? 'مجدول' : 'Scheduled' }}: {{ formatDateShort(n.scheduled_at) }}
                                    </div>
                                    <div v-else>{{ formatDateShort(n.created_at) }}</div>
                                </div>

                                <!-- Expand icon -->
                                <svg :class="['w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0', expandedId === n.id && 'rotate-180']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <!-- Expanded Detail -->
                            <Transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="opacity-0 max-h-0"
                                enter-to-class="opacity-100 max-h-96"
                                leave-active-class="transition-all duration-200"
                                leave-from-class="opacity-100 max-h-96"
                                leave-to-class="opacity-0 max-h-0"
                            >
                                <div v-if="expandedId === n.id" class="overflow-hidden">
                                    <div class="px-5 pb-5 ltr:pl-20 rtl:pr-20">
                                        <!-- Message preview -->
                                        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-3">
                                            <p class="text-xs text-gray-400 mb-1 font-medium">{{ locale === 'ar' ? 'نص الرسالة' : 'Message' }}</p>
                                            <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ n.message_ar }}</p>
                                        </div>

                                        <!-- Info grid -->
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs mb-3">
                                            <div>
                                                <span class="text-gray-400">{{ locale === 'ar' ? 'الهاتف' : 'Phone' }}</span>
                                                <p class="text-gray-700 font-medium mt-0.5" dir="ltr">{{ n.phone || '-' }}</p>
                                            </div>
                                            <div>
                                                <span class="text-gray-400">{{ locale === 'ar' ? 'الطبيب' : 'Doctor' }}</span>
                                                <p class="text-gray-700 font-medium mt-0.5">{{ locale === 'ar' ? n.doctor?.name_ar : n.doctor?.name_en || '-' }}</p>
                                            </div>
                                            <div>
                                                <span class="text-gray-400">{{ locale === 'ar' ? 'القناة' : 'Channel' }}</span>
                                                <p class="text-gray-700 font-medium mt-0.5">{{ n.channel?.toUpperCase() }}</p>
                                            </div>
                                            <div>
                                                <span class="text-gray-400">{{ locale === 'ar' ? 'تلقائي' : 'Auto' }}</span>
                                                <p class="text-gray-700 font-medium mt-0.5">{{ n.is_auto ? (locale === 'ar' ? 'نعم' : 'Yes') : (locale === 'ar' ? 'يدوي' : 'Manual') }}</p>
                                            </div>
                                        </div>

                                        <div v-if="n.failure_reason" class="bg-red-50 border border-red-100 rounded-lg p-3 text-xs text-red-600 mb-3">
                                            <span class="font-medium">{{ locale === 'ar' ? 'سبب الفشل:' : 'Failure reason:' }}</span> {{ n.failure_reason }}
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <button v-if="n.status === 'failed'" @click.stop="confirmResend(n)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-50 text-[#1B365D] hover:bg-slate-100 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                {{ locale === 'ar' ? 'إعادة إرسال' : 'Resend' }}
                                            </button>
                                            <button v-if="n.status === 'pending'" @click.stop="confirmCancel(n)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
                                            </button>
                                            <button v-if="n.status === 'sent' && !n.patient_responded" @click.stop="markResponded(n)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                {{ locale === 'ar' ? 'استجاب المريض' : 'Patient Responded' }}
                                            </button>
                                            <button @click.stop="confirmDelete(n)" :disabled="deletingId === n.id" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 transition-colors ltr:ml-auto rtl:mr-auto">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                {{ locale === 'ar' ? 'حذف' : 'Delete' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </template>
                </div>

                <!-- Pagination -->
                <div v-if="notifications?.links?.length > 3" class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        {{ locale === 'ar' ? `عرض ${notifications.from}-${notifications.to} من ${notifications.total}` : `Showing ${notifications.from}-${notifications.to} of ${notifications.total}` }}
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="link in notifications.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-all', link.active ? 'bg-[#1B365D] text-white' : 'text-gray-600 hover:bg-gray-100']"
                                preserve-state
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Manual Send Modal ─────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showManualModal" v-focus-trap="() => (showManualModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showManualModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <!-- Modal header -->
                    <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 rounded-t-2xl flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-800">
                            {{ locale === 'ar' ? 'إرسال تنبيه يدوي' : 'Send Manual Notification' }}
                        </h3>
                        <button @click="showManualModal = false" class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400" aria-label="Close">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Patient search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ locale === 'ar' ? 'المريض *' : 'Patient *' }}</label>
                            <div class="relative">
                                <input
                                    v-model="patientSearch"
                                    @input="searchPatients"
                                    type="text"
                                    :placeholder="locale === 'ar' ? 'ابحث عن المريض...' : 'Search patient...'"
                                    class="doctorato-input w-full rounded-xl border-gray-200 text-sm focus:border-[#2C4E7A] focus:ring-slate-100"
                                />
                                <div v-if="patients.length" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                    <button
                                        v-for="p in patients" :key="p.id"
                                        @click="selectPatient(p)"
                                        class="w-full px-4 py-2.5 text-left hover:bg-gray-50 flex items-center gap-3 text-sm"
                                    >
                                        <span class="font-medium text-gray-800">{{ p.full_name }}</span>
                                        <span class="text-xs text-gray-400">{{ p.file_number }}</span>
                                        <span class="text-xs text-gray-400 ltr:ml-auto rtl:mr-auto" dir="ltr">{{ p.phone }}</span>
                                    </button>
                                </div>
                            </div>
                            <p v-if="manualForm.errors.patient_id" class="text-xs text-red-500 mt-1">{{ manualForm.errors.patient_id }}</p>
                        </div>

                        <!-- Notification type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ locale === 'ar' ? 'نوع التنبيه *' : 'Notification Type *' }}</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="(labels, key) in types" :key="key"
                                    @click="manualForm.type = key"
                                    :class="['px-3 py-2.5 rounded-xl border text-xs font-medium text-left transition-all', manualForm.type === key ? 'border-[#2C4E7A] bg-slate-50 text-[#1B365D] ring-2 ring-slate-100' : 'border-gray-200 text-gray-600 hover:bg-gray-50']"
                                >
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-6 h-6 rounded-md flex items-center justify-center', typeConfig[key]?.bg]">
                                            <svg :class="['w-3.5 h-3.5', typeConfig[key]?.text]" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="typeConfig[key]?.icon || ''"></svg>
                                        </div>
                                        <span>{{ locale === 'ar' ? labels.ar : labels.en }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Message AR -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ locale === 'ar' ? 'نص الرسالة (عربي) *' : 'Message (Arabic) *' }}</label>
                            <textarea
                                v-model="manualForm.message_ar"
                                rows="4"
                                class="doctorato-input w-full rounded-xl border-gray-200 text-sm focus:border-[#2C4E7A] focus:ring-slate-100"
                                dir="rtl"
                                :placeholder="locale === 'ar' ? 'اكتب نص الرسالة...' : 'Type message text...'"
                            ></textarea>
                            <div class="flex justify-between mt-1">
                                <p v-if="manualForm.errors.message_ar" class="text-xs text-red-500">{{ manualForm.errors.message_ar }}</p>
                                <p class="text-xs text-gray-400 ltr:ml-auto rtl:mr-auto">{{ manualForm.message_ar.length }}/500</p>
                            </div>
                        </div>

                        <!-- Message EN (optional) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ locale === 'ar' ? 'نص الرسالة (إنجليزي) - اختياري' : 'Message (English) - Optional' }}</label>
                            <textarea
                                v-model="manualForm.message_en"
                                rows="3"
                                class="doctorato-input w-full rounded-xl border-gray-200 text-sm focus:border-[#2C4E7A] focus:ring-slate-100"
                                dir="ltr"
                                placeholder="Optional English version..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="sticky bottom-0 bg-white border-t border-gray-100 px-6 py-4 rounded-b-2xl flex items-center justify-end gap-3">
                        <button @click="showManualModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
                            {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
                        </button>
                        <button
                            @click="submitManual"
                            :disabled="manualForm.processing || !manualForm.patient_id || !manualForm.message_ar"
                            class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#1B365D] to-emerald-600 hover:from-[#1B365D] hover:to-emerald-700 shadow-lg disabled:opacity-50 transition-all"
                        >
                            <span v-if="manualForm.processing" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                {{ locale === 'ar' ? 'جاري الإرسال...' : 'Sending...' }}
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                {{ locale === 'ar' ? 'إرسال التنبيه' : 'Send Notification' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <ConfirmModal :show="showResendConfirm"
            :title="locale === 'ar' ? 'إعادة إرسال التنبيه' : 'Resend Notification'"
            :message="locale === 'ar' ? 'هل تريد إعادة إرسال هذا التنبيه؟' : 'Resend this notification?'"
            :confirmText="locale === 'ar' ? 'إعادة الإرسال' : 'Resend'"
            :cancelText="locale === 'ar' ? 'إلغاء' : 'Cancel'"
            confirmColor="cyan"
            @confirm="executeResend" @cancel="showResendConfirm = false" />

        <ConfirmModal :show="showCancelConfirm"
            :title="locale === 'ar' ? 'إلغاء التنبيه' : 'Cancel Notification'"
            :message="locale === 'ar' ? 'هل تريد إلغاء هذا التنبيه؟' : 'Cancel this notification?'"
            :confirmText="locale === 'ar' ? 'إلغاء التنبيه' : 'Cancel It'"
            :cancelText="locale === 'ar' ? 'تراجع' : 'Go Back'"
            confirmColor="amber"
            @confirm="executeCancel" @cancel="showCancelConfirm = false" />

        <ConfirmModal :show="showDeleteConfirm"
            :title="locale === 'ar' ? 'حذف التنبيه' : 'Delete Notification'"
            :message="locale === 'ar' ? 'هل أنت متأكد من حذف هذا التنبيه؟' : 'Are you sure you want to delete this notification?'"
            :confirmText="locale === 'ar' ? 'حذف' : 'Delete'"
            :cancelText="locale === 'ar' ? 'إلغاء' : 'Cancel'"
            confirmColor="red"
            @confirm="executeDelete" @cancel="showDeleteConfirm = false" />
    </AdminLayout>
</template>

<style>
.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }

@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
