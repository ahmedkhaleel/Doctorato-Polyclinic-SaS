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
    leaves: Object,
    users: Array,
    filters: Object,
});

const employeeFilter = ref(props.filters?.user_id || '');
const statusFilter = ref(props.filters?.status || '');
const typeFilter = ref(props.filters?.leave_type || '');

watch([employeeFilter, statusFilter, typeFilter], () => {
    router.get('/admin/leaves', {
        user_id: employeeFilter.value || undefined,
        status: statusFilter.value || undefined,
        leave_type: typeFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

function approveLeave(id) {
    const msg = isRtl.value ? 'هل تريد الموافقة على هذا الطلب؟' : 'Approve this leave request?';
    if (window.confirm(msg)) {
        router.post(`/admin/leaves/${id}/approve`, {}, { preserveScroll: true });
    }
}

function rejectLeave(id) {
    const msg = isRtl.value ? 'هل تريد رفض هذا الطلب؟' : 'Reject this leave request?';
    if (window.confirm(msg)) {
        router.post(`/admin/leaves/${id}/reject`, {}, { preserveScroll: true });
    }
}

function deleteLeave(id) {
    const msg = isRtl.value ? 'هل أنت متأكد من حذف هذا الطلب؟' : 'Are you sure you want to delete this leave request?';
    if (window.confirm(msg)) {
        router.post(`/admin/leaves/${id}/delete`, { preserveScroll: true });
    }
}

const typeConfig = {
    annual:   { key: 'a_annual',   icon: '☀️', bg: 'bg-slate-50',     text: 'text-[#1B365D]',    border: 'border-slate-200',    dot: 'bg-[#1B365D]' },
    sick:     { key: 'a_sick',     icon: '🏥', bg: 'bg-amber-50',    text: 'text-[#C4A265]',   border: 'border-amber-200',   dot: 'bg-[#C4A265]' },
    personal: { key: 'a_personal', icon: '👤', bg: 'bg-slate-50',  text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]' },
    unpaid:   { key: 'a_unpaid',   icon: '📋', bg: 'bg-slate-50',   text: 'text-slate-700',  border: 'border-slate-200',  dot: 'bg-slate-500' },
};

const statusConfig = {
    pending:  { key: 'a_pending',  bg: 'bg-amber-50',   text: 'text-amber-700',   border: 'border-amber-200',   dot: 'bg-amber-500',   icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    approved: { key: 'a_approved', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    rejected: { key: 'a_rejected', bg: 'bg-red-50',     text: 'text-red-700',     border: 'border-red-200',     dot: 'bg-red-500',     icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
};

function getType(t) { return typeConfig[t] || { key: t, icon: '📋', bg: 'bg-gray-50', text: 'text-gray-700', border: 'border-gray-200', dot: 'bg-gray-500' }; }
function getStatus(s) { return statusConfig[s] || { key: s, bg: 'bg-gray-50', text: 'text-gray-700', border: 'border-gray-200', dot: 'bg-gray-500', icon: '' }; }

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function daysBetween(s, e) {
    if (!s || !e) return 0;
    const diff = new Date(e) - new Date(s);
    return Math.max(1, Math.ceil(diff / (1000 * 60 * 60 * 24)) + 1);
}

/* ── Stats ── */
const stats = computed(() => {
    const data = props.leaves?.data || [];
    return {
        total: props.leaves?.total || data.length,
        pending: data.filter(l => l.status === 'pending').length,
        approved: data.filter(l => l.status === 'approved').length,
        rejected: data.filter(l => l.status === 'rejected').length,
    };
});
</script>

<template>
    <AdminLayout :title="$t('a_leaves')">
        <div class="space-y-6">

            <!-- ═══════════ HEADER ═══════════ -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_leave_requests') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'إدارة طلبات الإجازات والموافقات' : 'Manage leave requests and approvals' }}</p>
                </div>
                <Link
                    v-if="can('leaves.create')"
                    href="/admin/leaves/create"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-200"
                    style="background: linear-gradient(135deg, #C4A265 0%, #B08D4C 100%);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_new_leave_request') }}
                </Link>
            </div>

            <!-- ═══════════ STAT CARDS ═══════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                    </div>
                    <p class="text-xl md:text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-amber-500 uppercase tracking-wider">{{ $t('a_pending') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-xl md:text-2xl font-bold text-amber-600">{{ stats.pending }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-emerald-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">{{ $t('a_approved') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-xl md:text-2xl font-bold text-emerald-600">{{ stats.approved }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-red-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-red-400 uppercase tracking-wider">{{ $t('a_rejected') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-xl md:text-2xl font-bold text-red-500">{{ stats.rejected }}</p>
                </div>
            </div>

            <!-- ═══════════ FILTERS ═══════════ -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span class="text-sm font-semibold text-gray-600">{{ isRtl ? 'تصفية النتائج' : 'Filter Results' }}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Employee Filter -->
                    <div class="relative">
                        <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ $t('a_employee') }}</label>
                        <div class="relative">
                            <select
                                v-model="employeeFilter"
                                class="w-full appearance-none bg-gray-50/80 border border-gray-200 rounded-xl px-4 py-2.5 pe-10 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-amber-200/50 focus:border-amber-300 transition-all duration-200 cursor-pointer"
                            >
                                <option value="">{{ $t('a_all_employees') }}</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                    <!-- Status Filter -->
                    <div class="relative">
                        <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ $t('a_status') }}</label>
                        <div class="relative">
                            <select
                                v-model="statusFilter"
                                class="w-full appearance-none bg-gray-50/80 border border-gray-200 rounded-xl px-4 py-2.5 pe-10 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-amber-200/50 focus:border-amber-300 transition-all duration-200 cursor-pointer"
                            >
                                <option value="">{{ $t('a_all_status') }}</option>
                                <option value="pending">{{ $t('a_pending') }}</option>
                                <option value="approved">{{ $t('a_approved') }}</option>
                                <option value="rejected">{{ $t('a_rejected') }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                    <!-- Type Filter -->
                    <div class="relative">
                        <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ $t('a_type') }}</label>
                        <div class="relative">
                            <select
                                v-model="typeFilter"
                                class="w-full appearance-none bg-gray-50/80 border border-gray-200 rounded-xl px-4 py-2.5 pe-10 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-amber-200/50 focus:border-amber-300 transition-all duration-200 cursor-pointer"
                            >
                                <option value="">{{ $t('a_all_types') }}</option>
                                <option value="annual">{{ $t('a_annual') }}</option>
                                <option value="sick">{{ $t('a_sick') }}</option>
                                <option value="personal">{{ $t('a_personal') }}</option>
                                <option value="unpaid">{{ $t('a_unpaid') }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════ LEAVE CARDS ═══════════ -->
            <div v-if="leaves.data && leaves.data.length > 0" class="space-y-3">
                <div
                    v-for="leave in leaves.data"
                    :key="leave.id"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden"
                >
                    <div class="flex flex-col sm:flex-row">
                        <!-- Status Accent Bar -->
                        <div
                            :class="[
                                'w-full sm:w-1.5 h-1.5 sm:h-auto flex-shrink-0',
                                getStatus(leave.status).dot
                            ]"
                        />

                        <div class="flex-1 p-5">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                                <!-- Employee Info -->
                                <div class="flex items-center gap-3 lg:w-[200px] flex-shrink-0">
                                    <div
                                        class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-sm flex-shrink-0"
                                        style="background: linear-gradient(135deg, #C4A265 0%, #B08D4C 100%);"
                                    >
                                        {{ leave.user?.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ leave.user?.name }}</p>
                                        <p class="text-[11px] text-gray-400">{{ isRtl ? 'موظف' : 'Employee' }}</p>
                                    </div>
                                </div>

                                <!-- Details Grid -->
                                <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <!-- Type -->
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_type') }}</p>
                                        <span
                                            :class="[getType(leave.leave_type).bg, getType(leave.leave_type).text, getType(leave.leave_type).border]"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold border"
                                        >
                                            {{ $t(getType(leave.leave_type).key) || leave.leave_type }}
                                        </span>
                                    </div>

                                    <!-- Date Range -->
                                    <div class="col-span-1">
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'الفترة' : 'Period' }}</p>
                                        <p class="text-sm font-semibold text-gray-800">{{ formatDate(leave.start_date) }}</p>
                                        <p class="text-[11px] text-gray-400">{{ isRtl ? 'إلى' : 'to' }} {{ formatDate(leave.end_date) }}</p>
                                    </div>

                                    <!-- Duration -->
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'المدة' : 'Duration' }}</p>
                                        <p class="text-sm font-bold text-gray-800">
                                            {{ daysBetween(leave.start_date, leave.end_date) }}
                                            <span class="text-[11px] font-normal text-gray-400">{{ isRtl ? 'يوم' : 'days' }}</span>
                                        </p>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_status') }}</p>
                                        <span
                                            :class="[getStatus(leave.status).bg, getStatus(leave.status).text, getStatus(leave.status).border]"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border"
                                        >
                                            <span :class="[getStatus(leave.status).dot]" class="w-1.5 h-1.5 rounded-full" />
                                            {{ $t(getStatus(leave.status).key) || leave.status }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2 lg:w-auto flex-shrink-0">
                                    <template v-if="leave.status === 'pending'">
                                        <button
                                            v-if="can('leaves.approve')"
                                            @click="approveLeave(leave.id)"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 shadow-sm hover:shadow transition-all duration-200"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                            {{ $t('a_approve') }}
                                        </button>
                                        <button
                                            v-if="can('leaves.approve')"
                                            @click="rejectLeave(leave.id)"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all duration-200"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                            {{ $t('a_reject') }}
                                        </button>
                                    </template>
                                    <template v-else>
                                        <span v-if="leave.approved_by_user" class="text-[11px] text-gray-400">
                                            {{ isRtl ? 'بواسطة' : 'by' }} {{ leave.approved_by_user?.name }}
                                        </span>
                                    </template>
                                    <button
                                        v-if="can('leaves.delete')"
                                        @click="deleteLeave(leave.id)"
                                        class="p-2 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all duration-200"
                                        :title="$t('a_delete')"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Reason (if exists) -->
                            <div v-if="leave.reason" class="mt-3 pt-3 border-t border-gray-100">
                                <p class="text-[11px] text-gray-400 mb-0.5">{{ $t('a_reason') }}</p>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ leave.reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════ EMPTY STATE ═══════════ -->
            <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm py-16">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-500">{{ $t('a_no_leave_requests_found') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'لا توجد طلبات إجازة تطابق الفلتر' : 'No leave requests match your filters' }}</p>
                </div>
            </div>

            <!-- ═══════════ PAGINATION ═══════════ -->
            <div v-if="leaves.links && leaves.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4">
                <p class="text-sm text-gray-500">
                    {{ $t('a_showing') }} <span class="font-semibold text-gray-700">{{ leaves.from }}</span> {{ $t('a_to') }} <span class="font-semibold text-gray-700">{{ leaves.to }}</span> {{ $t('a_of') }} <span class="font-semibold text-gray-700">{{ leaves.total }}</span> {{ $t('a_results') }}
                </p>
                <nav :class="['flex', isRtl ? 'space-x-reverse space-x-1' : 'space-x-1']">
                    <template v-for="link in leaves.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-200"
                            :class="link.active ? 'text-white border-transparent shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300'"
                            :style="link.active ? 'background: linear-gradient(135deg, #C4A265 0%, #B08D4C 100%);' : ''"
                            preserve-state
                        />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-300" />
                    </template>
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>
