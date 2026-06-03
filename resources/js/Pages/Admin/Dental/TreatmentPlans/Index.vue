<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { t } = useLocale();
const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const props = defineProps({
    plans: Object,
    filters: Object,
    doctors: Array,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const doctorFilter = ref(props.filters?.doctor_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const showFilters = ref(false);

let searchTimeout = null;

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        isFiltering.value = true;
        router.get('/admin/dental/treatment-plans', {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            doctor_id: doctorFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => { isFiltering.value = false; },
        });
    }, 400);
}

watch([search, statusFilter, doctorFilter, dateFrom, dateTo], applyFilters);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusConfig = {
    draft: { bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-400', label: 'a_plan_status_draft' },
    pending: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', label: 'a_plan_status_pending' },
    approved: { bg: 'bg-slate-50', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]', label: 'a_plan_status_approved' },
    in_progress: { bg: 'bg-slate-50', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]', label: 'a_plan_status_in_progress' },
    completed: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'a_plan_status_completed' },
    cancelled: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', label: 'a_plan_status_cancelled' },
};

const priorityConfig = {
    low: { bg: 'bg-gray-50', text: 'text-gray-600' },
    normal: { bg: 'bg-slate-50', text: 'text-[#1B365D]' },
    high: { bg: 'bg-[#F5E7C8]/40', text: 'text-[#8B7043]' },
    urgent: { bg: 'bg-red-50', text: 'text-red-700' },
};

function getStatus(status) { return statusConfig[status] || statusConfig.draft; }
function getPriority(priority) { return priorityConfig[priority] || priorityConfig.normal; }

const hasActiveFilters = computed(() => statusFilter.value || doctorFilter.value || dateFrom.value || dateTo.value);

function clearFilters() {
    statusFilter.value = '';
    doctorFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}

const deletingId = ref(null);
const showDeleteModal = ref(false);
const pendingDeleteId = ref(null);
const isFiltering = ref(false);
const showSuccess = ref(false);
const successMessage = ref('');

// Flash success from server
const flash = computed(() => page.props.flash || {});
watch(() => flash.value?.success, (msg) => {
    if (msg) {
        successMessage.value = msg;
        showSuccess.value = true;
        setTimeout(() => { showSuccess.value = false; }, 3500);
    }
}, { immediate: true });

function deletePlan(id) {
    pendingDeleteId.value = id;
    showDeleteModal.value = true;
}

function confirmDelete() {
    const id = pendingDeleteId.value;
    showDeleteModal.value = false;
    deletingId.value = id;
    router.post(`/admin/dental/treatment-plans/${id}/delete`, {}, {
        onFinish: () => { deletingId.value = null; pendingDeleteId.value = null; },
    });
}

function progressPercent(plan) {
    if (!plan.estimated_sessions || plan.estimated_sessions === 0) return 0;
    return Math.min((plan.completed_sessions / plan.estimated_sessions) * 100, 100);
}
</script>

<template>
    <AdminLayout :title="$t('a_treatment_plans')">
        <div class="space-y-6">
            <!-- ── Hero Header ───────────────────────────────────── -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-7">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-[#2C4E7A]/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-emerald-300/15 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">{{ $t('a_treatment_plans') }}</h1>
                            <p class="text-slate-100/80 text-sm mt-0.5">{{ $t('a_treatment_plans_desc') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            href="/admin/dental"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            {{ $t('a_dental_dashboard') }}
                        </Link>
                        <Link
                            href="/admin/dental/treatment-plan-templates"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                            {{ locale === 'ar' ? 'القوالب' : 'Templates' }}
                        </Link>
                        <Link
                            href="/admin/dental/treatment-plans/create"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-[#C4A265] hover:bg-[#D4B57E] shadow-lg hover:shadow-xl transition-all duration-300 backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ $t('a_create_plan') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Quick Stats ──────────────────────────────────── -->
            <div class="dental-card-enter grid grid-cols-2 sm:grid-cols-4 gap-3" style="animation-delay: 0.1s">
                <div v-for="(stat, i) in [
                    { label: isRtl ? 'مسودة' : 'Draft', count: plans.data?.filter(p => p.status === 'draft').length || 0, color: 'gray', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
                    { label: isRtl ? 'قيد التنفيذ' : 'In Progress', count: plans.data?.filter(p => p.status === 'in_progress').length || 0, color: 'cyan', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
                    { label: isRtl ? 'مكتملة' : 'Completed', count: plans.data?.filter(p => p.status === 'completed').length || 0, color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
                    { label: isRtl ? 'الإجمالي' : 'Total', count: plans.total || 0, color: 'blue', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
                ]" :key="i"
                    class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-all duration-300 group"
                    :style="{ animationDelay: `${0.12 + i * 0.06}s` }"
                >
                    <div class="flex items-center gap-3">
                        <div :class="`bg-${stat.color}-50 text-${stat.color}-600 group-hover:scale-110`" class="w-10 h-10 rounded-xl flex items-center justify-center transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="stat.icon" /></svg>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-gray-900">{{ stat.count }}</div>
                            <div class="text-xs text-gray-500">{{ stat.label }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Search + Filters ──────────────────────────────── -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.15s">
                <div class="p-5">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <svg v-if="!isFiltering" class="absolute top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <svg v-else class="absolute top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 w-4.5 h-4.5 text-[#1B365D] animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="$t('a_search_patient_title_notes')"
                                class="doctorato-input w-full ltr:pl-11 rtl:pr-11 ltr:pr-4 rtl:pl-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-slate-200/60 focus:border-slate-300 transition-all duration-200"
                            />
                        </div>
                        <button
                            @click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium border transition-all duration-200"
                            :class="showFilters || hasActiveFilters ? 'bg-slate-50 border-slate-200 text-[#1B365D]' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            {{ locale === 'ar' ? 'فلاتر' : 'Filters' }}
                            <span v-if="hasActiveFilters" class="w-2 h-2 rounded-full bg-[#1B365D] animate-pulse"></span>
                        </button>
                    </div>

                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 max-h-0"
                        enter-to-class="opacity-100 max-h-48"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 max-h-48"
                        leave-to-class="opacity-0 max-h-0"
                    >
                        <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-100 overflow-hidden">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <select v-model="statusFilter" class="doctorato-input dental-select">
                                    <option value="">{{ $t('a_all_statuses') }}</option>
                                    <option value="draft">{{ $t('a_plan_status_draft') }}</option>
                                    <option value="pending">{{ $t('a_plan_status_pending') }}</option>
                                    <option value="approved">{{ $t('a_plan_status_approved') }}</option>
                                    <option value="in_progress">{{ $t('a_plan_status_in_progress') }}</option>
                                    <option value="completed">{{ $t('a_plan_status_completed') }}</option>
                                    <option value="cancelled">{{ $t('a_plan_status_cancelled') }}</option>
                                </select>
                                <select v-model="doctorFilter" class="doctorato-input dental-select">
                                    <option value="">{{ $t('a_all_doctors') }}</option>
                                    <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
                                        {{ locale === 'ar' ? doc.name_ar : doc.name_en }}
                                    </option>
                                </select>
                                <div class="flex items-center gap-2">
                                    <input v-model="dateFrom" type="date" class="doctorato-input dental-select flex-1" />
                                    <span class="text-gray-300">-</span>
                                    <input v-model="dateTo" type="date" class="doctorato-input dental-select flex-1" />
                                </div>
                            </div>
                            <div v-if="hasActiveFilters" class="mt-3 flex justify-end">
                                <button @click="clearFilters" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                                    {{ locale === 'ar' ? 'مسح الفلاتر' : 'Clear filters' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- ── Table ─────────────────────────────────────────── -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.25s">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_title') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_doctor') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_estimated_cost') }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_sessions_progress') }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_priority') }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-5 py-3.5 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(plan, idx) in plans.data"
                                :key="plan.id"
                                class="dental-row-enter border-b border-gray-50 hover:bg-slate-50/30 transition-colors duration-200"
                                :style="{ animationDelay: `${0.3 + idx * 0.04}s` }"
                            >
                                <td class="px-5 py-4 text-sm">
                                    <Link v-if="plan.patient" :href="`/admin/patients/${plan.patient.id}`" class="font-medium text-gray-900 hover:text-[#1B365D] transition-colors">
                                        {{ plan.patient.full_name }}
                                    </Link>
                                    <div class="text-xs text-gray-400 mt-0.5 font-mono">{{ plan.patient?.file_number }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <Link :href="`/admin/dental/treatment-plans/${plan.id}`" class="font-semibold text-gray-900 hover:text-[#1B365D] transition-colors">
                                        {{ locale === 'ar' ? (plan.title_ar || plan.title_en) : (plan.title_en || plan.title_ar) }}
                                    </Link>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    {{ plan.doctor ? (locale === 'ar' ? plan.doctor.name_ar : plan.doctor.name_en) : '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ formatCurrency(plan.estimated_cost) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <div class="w-full max-w-[80px] bg-gray-100 rounded-full h-2">
                                            <div
                                                class="bg-gradient-to-r from-[#2C4E7A] to-[#1B365D] h-2 rounded-full transition-all duration-700"
                                                :style="{ width: progressPercent(plan) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-xs text-gray-500 font-mono">{{ plan.completed_sessions }}/{{ plan.estimated_sessions }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span
                                        :class="[getPriority(plan.priority).bg, getPriority(plan.priority).text]"
                                        class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                    >
                                        {{ $t('a_priority_' + (plan.priority || 'normal')) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span
                                        :class="[getStatus(plan.status).bg, getStatus(plan.status).text]"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                    >
                                        <span :class="getStatus(plan.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ $t('a_plan_status_' + plan.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="`/admin/dental/treatment-plans/${plan.id}`"
                                            class="p-2 rounded-lg text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 transition-all duration-200"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="deletePlan(plan.id)"
                                            :disabled="deletingId === plan.id"
                                            class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-200 disabled:opacity-50"
                                         :aria-label="isRtl ? 'حذف' : 'Delete'" :title="isRtl ? 'حذف' : 'Delete'">
                                            <svg v-if="deletingId === plan.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!plans.data || plans.data.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_treatment_plans') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="plans.links && plans.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ plans.from }} {{ $t('a_to') }} {{ plans.to }} {{ $t('a_of') }} {{ plans.total }} {{ $t('a_results') }}</p>
                    <nav class="flex ltr:space-x-1 rtl:space-x-reverse rtl:space-x-1">
                        <template v-for="link in plans.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-200"
                                :class="link.active ? 'bg-[#1B365D] text-white border-transparent shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-slate-50 hover:border-slate-200 hover:text-[#1B365D]'"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- ── Delete Confirm Modal ────────────────────────────── -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="isRtl ? 'حذف خطة العلاج' : 'Delete Treatment Plan'"
            :message="isRtl ? 'هل أنت متأكد من حذف هذه الخطة؟ سيتم حذف جميع العلاجات المرتبطة بها.' : 'Are you sure you want to delete this plan? All associated treatments will be removed.'"
            :confirm-text="isRtl ? 'حذف' : 'Delete'"
            :cancel-text="isRtl ? 'إلغاء' : 'Cancel'"
            confirm-color="red"
            @confirm="confirmDelete"
            @cancel="showDeleteModal = false"
        />

        <!-- ── Success Toast ───────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-2 scale-95"
            >
                <div v-if="showSuccess" class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3 bg-emerald-600 text-white rounded-xl shadow-2xl shadow-emerald-200">
                    <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <span class="text-sm font-medium">{{ successMessage }}</span>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalRowEnter {
    from { opacity: 0; transform: translateX(12px); }
    to   { opacity: 1; transform: translateX(0); }
}

.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-row-enter { animation: dentalRowEnter 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }

[dir="rtl"] .dental-row-enter { animation-name: dentalRowEnterRtl; }
@keyframes dentalRowEnterRtl {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
}

.dental-select {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    background: rgba(249, 250, 251, 0.5);
    transition: all 0.2s;
}
.dental-select:focus {
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(34, 211, 238, 0.3);
    border-color: #C4A265;
}
</style>
