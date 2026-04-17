<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    consultations: Object,
    filters: Object,
    stats: Object,
});

/* ── Filters ─────────────────────────────────────────────── */
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
let searchTimer = null;

function applyFilters() {
    router.get('/admin/online-consultations', {
        search: search.value || undefined,
        status: statusFilter.value && statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch(statusFilter, () => applyFilters());

function refresh() {
    router.reload({ preserveScroll: true });
}

/* ── Stat cards ──────────────────────────────────────────── */
const statCards = computed(() => [
    {
        labelAr: 'جلسات اليوم', labelEn: "Today's Sessions",
        value: props.stats?.total_today ?? 0,
        accent: 'from-[#1B365D] to-[#2B4A7A]',
        icon: 'calendar',
    },
    {
        labelAr: 'القادمة', labelEn: 'Upcoming',
        value: props.stats?.upcoming_count ?? 0,
        accent: 'from-[#C4A265] to-[#D4B275]',
        icon: 'clock',
    },
    {
        labelAr: 'مكتملة هذا الشهر', labelEn: 'Completed this month',
        value: props.stats?.completed_count ?? 0,
        accent: 'from-emerald-500 to-emerald-600',
        icon: 'check',
    },
    {
        labelAr: 'إيرادات الشهر', labelEn: 'Revenue this month',
        value: formatMoney(props.stats?.revenue_this_month ?? 0),
        accent: 'from-[#1B365D] via-[#C4A265] to-[#C4A265]',
        icon: 'money',
    },
]);

/* ── Status badge ───────────────────────────────────────── */
const statusConfig = {
    scheduled:       { bg: 'bg-gray-100',   text: 'text-gray-700',    dot: 'bg-gray-400',   ar: 'مجدول',        en: 'Scheduled' },
    waiting:         { bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',  ar: 'في الانتظار',  en: 'Waiting', pulse: true },
    in_progress:     { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500',ar: 'جارية',        en: 'In Progress', pulse: true },
    completed:       { bg: 'bg-emerald-600',text: 'text-white',       dot: 'bg-white',      ar: 'مكتمل',        en: 'Completed', solid: true },
    cancelled:       { bg: 'bg-gray-50',    text: 'text-gray-500',    dot: 'bg-gray-300',   ar: 'ملغى',         en: 'Cancelled' },
    missed_patient:  { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',    ar: 'المريض غاب',   en: 'Patient Missed' },
    missed_doctor:   { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',    ar: 'الطبيب غاب',   en: 'Doctor Missed' },
    refunded:        { bg: 'bg-slate-50',    text: 'text-[#1B365D]',    dot: 'bg-[#1B365D]',   ar: 'مسترد',        en: 'Refunded' },
};

function statusStyle(s) { return statusConfig[s] || statusConfig.scheduled; }
function statusLabel(s) {
    const c = statusConfig[s]; if (!c) return s;
    return isRtl.value ? c.ar : c.en;
}

/* ── Payment badge ──────────────────────────────────────── */
const paymentConfig = {
    paid:     { bg: 'bg-emerald-50', text: 'text-emerald-700', ar: 'مدفوع',   en: 'Paid' },
    pending:  { bg: 'bg-amber-50',   text: 'text-amber-700',   ar: 'معلق',    en: 'Pending' },
    failed:   { bg: 'bg-red-50',     text: 'text-red-700',     ar: 'فشل',     en: 'Failed' },
    refunded: { bg: 'bg-slate-50',    text: 'text-[#1B365D]',    ar: 'مسترد',   en: 'Refunded' },
};
function paymentStyle(s) { return paymentConfig[s] || paymentConfig.pending; }
function paymentLabel(s) {
    const c = paymentConfig[s]; if (!c) return s || '-';
    return isRtl.value ? c.ar : c.en;
}

/* ── Helpers ────────────────────────────────────────────── */
function formatMoney(v) {
    if (v == null) return '0 EGP';
    return `${Number(v).toLocaleString()} EGP`;
}
function formatDate(d) {
    if (!d) return '-';
    const loc = isRtl.value ? 'ar-EG' : 'en-GB';
    return new Date(d).toLocaleDateString(loc, { day: '2-digit', month: 'short', year: 'numeric' });
}
function formatTime(t) {
    if (!t) return '';
    const s = String(t);
    // Accept HH:mm or full datetime
    if (s.length <= 8) return s.slice(0, 5);
    try {
        return new Date(s).toLocaleTimeString(isRtl.value ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit' });
    } catch { return s; }
}
function doctorName(c) {
    if (!c.doctor) return '-';
    return isRtl.value ? (c.doctor.name_ar || c.doctor.name_en || '-') : (c.doctor.name_en || c.doctor.name_ar || '-');
}
function doctorSpecialty(c) {
    if (!c.doctor) return '';
    return isRtl.value ? (c.doctor.specialization_ar || '') : (c.doctor.specialization_en || '');
}
function openRow(c) {
    router.visit(`/admin/online-consultations/${c.id}`);
}

const paginationLinks = computed(() => props.consultations?.links || []);
</script>

<template>
    <div class="space-y-8 pb-12">
        <!-- Hero -->
        <div class="oc-hero relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B365D] via-[#24436F] to-[#1B365D] p-8 md:p-10">
            <div class="absolute -top-24 ltr:-right-24 rtl:-left-24 w-80 h-80 bg-[#C4A265]/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 ltr:-left-20 rtl:-right-20 w-64 h-64 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
            <div class="absolute ltr:right-10 rtl:left-10 top-10 opacity-10">
                <svg class="w-28 h-28 text-[#C4A265] oc-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="oc-hero-up">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-[#C4A265]/20 ring-1 ring-[#C4A265]/40 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-white">
                                {{ isRtl ? 'الاستشارات الأونلاين' : 'Online Consultations' }}
                            </h1>
                            <div class="h-0.5 w-20 bg-gradient-to-r from-[#C4A265] to-transparent mt-1.5"></div>
                            <p class="text-white/70 text-sm mt-2">
                                {{ isRtl ? 'متابعة جلسات الاستشارة عن بُعد، المدفوعات، والإيرادات' : 'Track remote consultations, payments, and revenue' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="oc-hero-up" style="animation-delay:.15s">
                    <Link
                        href="/admin/online-consultations/doctors"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-[#C4A265] hover:bg-[#D4B275] shadow-lg ring-1 ring-[#C4A265]/50 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        {{ isRtl ? 'الأطباء الأونلاين' : 'Online Doctors' }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                v-for="(card, i) in statCards"
                :key="i"
                class="oc-card-enter relative bg-white rounded-2xl p-5 shadow-sm border border-gray-100 overflow-hidden"
                :style="{ animationDelay: `${i * 0.08}s` }"
            >
                <div :class="`absolute top-0 inset-x-0 h-1 bg-gradient-to-r ${card.accent}`"></div>
                <p class="text-[13px] font-medium text-gray-500">{{ isRtl ? card.labelAr : card.labelEn }}</p>
                <p class="text-2xl font-bold text-[#1B365D] mt-1.5 tabular-nums">{{ card.value }}</p>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex flex-col sm:flex-row gap-3 flex-wrap items-stretch sm:items-center">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="isRtl ? 'بحث برقم الاستشارة، المريض، أو الطبيب...' : 'Search by #, patient, or doctor...'"
                        class="w-full ltr:pl-10 rtl:pr-10 py-2.5 ltr:pr-4 rtl:pl-4 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] bg-white"
                >
                    <option value="all">{{ isRtl ? 'كل الحالات' : 'All statuses' }}</option>
                    <option value="scheduled">{{ isRtl ? 'مجدول' : 'Scheduled' }}</option>
                    <option value="waiting">{{ isRtl ? 'في الانتظار' : 'Waiting' }}</option>
                    <option value="in_progress">{{ isRtl ? 'جارية' : 'In progress' }}</option>
                    <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                    <option value="missed_patient">{{ isRtl ? 'المريض غاب' : 'Patient missed' }}</option>
                    <option value="missed_doctor">{{ isRtl ? 'الطبيب غاب' : 'Doctor missed' }}</option>
                    <option value="cancelled">{{ isRtl ? 'ملغى' : 'Cancelled' }}</option>
                </select>
                <button
                    type="button"
                    @click="refresh"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-gray-50 hover:bg-gray-100 border border-gray-200 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992V4.356M2.985 19.644l4.992-4.992m0 0A7.5 7.5 0 0019.44 12m-11.463 2.652a7.5 7.5 0 01-3.48-4.151m14.943 1.499a7.5 7.5 0 00-12.548-3.364" /></svg>
                    {{ isRtl ? 'تحديث' : 'Refresh' }}
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#1B365D]/5">
                        <tr>
                            <th class="text-start px-5 py-3.5 font-semibold text-[#1B365D]">#</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-[#1B365D]">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-[#1B365D] hidden md:table-cell">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-[#1B365D] hidden sm:table-cell">{{ isRtl ? 'الموعد' : 'Scheduled' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-[#1B365D]">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-[#1B365D] hidden lg:table-cell">{{ isRtl ? 'الدفع' : 'Payment' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-[#1B365D] hidden md:table-cell">{{ isRtl ? 'الرسوم' : 'Fee' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-[#1B365D]">{{ isRtl ? 'إجراء' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="c in (consultations?.data || [])"
                            :key="c.id"
                            @click="openRow(c)"
                            class="border-b border-gray-50 hover:bg-[#C4A265]/5 cursor-pointer transition-colors"
                        >
                            <td class="px-5 py-3.5 font-semibold text-[#C4A265] whitespace-nowrap">
                                {{ c.consultation_number || `#${c.id}` }}
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-gray-800">{{ c.patient?.full_name || '-' }}</p>
                                <p class="text-xs text-gray-400 tabular-nums">{{ c.patient?.phone || '' }}</p>
                            </td>
                            <td class="px-5 py-3.5 hidden md:table-cell">
                                <p class="font-medium text-gray-700">{{ doctorName(c) }}</p>
                                <p class="text-xs text-gray-400">{{ doctorSpecialty(c) }}</p>
                            </td>
                            <td class="px-5 py-3.5 hidden sm:table-cell whitespace-nowrap">
                                <p class="text-gray-700">{{ formatDate(c.scheduled_date) }}</p>
                                <p class="text-xs text-gray-400 tabular-nums">{{ formatTime(c.start_time) }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold',
                                        statusStyle(c.status).bg,
                                        statusStyle(c.status).text,
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            statusStyle(c.status).dot,
                                            statusStyle(c.status).pulse ? 'animate-pulse' : '',
                                        ]"
                                    ></span>
                                    {{ statusLabel(c.status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center hidden lg:table-cell">
                                <span
                                    :class="[
                                        'inline-flex px-2.5 py-1 rounded-full text-xs font-semibold',
                                        paymentStyle(c.payment_status).bg,
                                        paymentStyle(c.payment_status).text,
                                    ]"
                                >
                                    {{ paymentLabel(c.payment_status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 hidden md:table-cell whitespace-nowrap font-semibold text-[#1B365D] tabular-nums">
                                {{ formatMoney(c.fee) }}
                            </td>
                            <td class="px-5 py-3.5 text-center" @click.stop>
                                <Link
                                    :href="`/admin/online-consultations/${c.id}`"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1B365D] bg-[#C4A265]/15 hover:bg-[#C4A265]/25 border border-[#C4A265]/30 transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    {{ isRtl ? 'عرض' : 'View' }}
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!consultations?.data?.length">
                            <td colspan="8" class="px-5 py-16 text-center text-gray-400">
                                <svg class="w-14 h-14 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <p class="font-medium text-gray-500">{{ isRtl ? 'لا توجد استشارات بعد' : 'No consultations yet' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="paginationLinks.length > 3" class="flex items-center justify-center gap-1 px-5 py-4 border-t border-gray-100">
                <template v-for="(link, i) in paginationLinks" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-sm transition"
                        :class="link.active ? 'bg-[#1B365D] text-white font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        v-html="link.label"
                        preserve-state
                        preserve-scroll
                    />
                    <span v-else class="px-3 py-1.5 text-sm text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.oc-hero-up { animation: ocHeroUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both; }
@keyframes ocHeroUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.oc-float { animation: ocFloat 6s ease-in-out infinite; }
@keyframes ocFloat {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-12px); }
}
.oc-card-enter { animation: ocCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
@keyframes ocCardEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
