<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const props = defineProps({
    vaccinations: Object,
    stats: Object,
    filters: Object,
});

/* ── Filters ───────────────────────────────────────────── */
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
let searchTimer = null;

function applyFilters() {
    router.get('/admin/pediatric/vaccinations', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch(statusFilter, () => applyFilters());

/* ── Stats cards ───────────────────────────────────────── */
const statCards = computed(() => [
    {
        labelEn: 'Total', labelAr: 'الإجمالي',
        value: props.stats?.total ?? 0,
        gradient: 'from-gray-500 to-gray-600',
        lightBg: 'bg-gray-50', iconColor: 'text-gray-500',
    },
    {
        labelEn: 'Given', labelAr: 'تم التطعيم',
        value: props.stats?.given ?? 0,
        gradient: 'from-emerald-500 to-green-600',
        lightBg: 'bg-emerald-50', iconColor: 'text-emerald-500',
    },
    {
        labelEn: 'Scheduled', labelAr: 'مجدولة',
        value: props.stats?.scheduled ?? 0,
        gradient: 'from-blue-500 to-indigo-600',
        lightBg: 'bg-blue-50', iconColor: 'text-blue-500',
    },
    {
        labelEn: 'Overdue', labelAr: 'متأخرة',
        value: props.stats?.overdue ?? 0,
        gradient: 'from-red-500 to-rose-600',
        lightBg: 'bg-red-50', iconColor: 'text-red-500',
        isAlert: (props.stats?.overdue ?? 0) > 0,
    },
    {
        labelEn: 'Missed', labelAr: 'فائتة',
        value: props.stats?.missed ?? 0,
        gradient: 'from-gray-400 to-gray-500',
        lightBg: 'bg-gray-50', iconColor: 'text-gray-400',
    },
]);

/* ── Status badge ──────────────────────────────────────── */
const statusStyles = {
    given:     { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    scheduled: { bg: 'bg-blue-50',    text: 'text-blue-700',    dot: 'bg-blue-500' },
    overdue:   { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500' },
    missed:    { bg: 'bg-gray-100',   text: 'text-gray-600',    dot: 'bg-gray-400' },
};

function getStatusStyle(status) { return statusStyles[status] || statusStyles.scheduled; }

function statusLabel(status) {
    const labels = {
        given:     { en: 'Given',     ar: 'تم' },
        scheduled: { en: 'Scheduled', ar: 'مجدول' },
        overdue:   { en: 'Overdue',   ar: 'متأخر' },
        missed:    { en: 'Missed',    ar: 'فائت' },
    };
    return isRtl.value ? (labels[status]?.ar || status) : (labels[status]?.en || status);
}

/* ── Helpers ────────────────────────────────────────────── */
function formatDate(date) {
    if (!date) return '-';
    const loc = isRtl.value ? 'ar-EG' : 'en-GB';
    return new Date(date).toLocaleDateString(loc, { day: '2-digit', month: 'short', year: 'numeric' });
}

/* ── Pagination ────────────────────────────────────────── */
const paginationLinks = computed(() => props.vaccinations?.links || []);
</script>

<template>
    <div class="space-y-8 pb-12">
        <!-- ── Hero ──────────────────────────────────────── -->
        <div class="ped-hero relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-green-600 to-green-500 p-8 md:p-10">
            <div class="absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-green-400/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-emerald-300/15 rounded-full blur-3xl"></div>

            <div class="absolute ltr:right-8 rtl:left-8 top-8 opacity-10">
                <svg class="w-28 h-28 text-white ped-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="ped-hero-up">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white">
                                    {{ isRtl ? 'التطعيمات' : 'Vaccinations' }}
                                </h1>
                                <p class="text-green-100/80 text-sm mt-0.5">
                                    {{ isRtl ? 'إدارة جدول تطعيمات الأطفال' : 'Manage pediatric vaccination schedule' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="ped-hero-up" style="animation-delay: 0.15s">
                        <Link
                            href="/admin/pediatric"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/15 hover:bg-white/25 ring-1 ring-white/30 hover:ring-white/50 shadow-lg transition-all duration-300 backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            {{ isRtl ? 'لوحة التحكم' : 'Dashboard' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Stats Row ─────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div
                v-for="(card, index) in statCards"
                :key="index"
                class="ped-card-enter relative bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80 overflow-hidden"
                :style="{ animationDelay: `${index * 0.08}s` }"
            >
                <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80`"></div>
                <p class="text-[13px] font-medium text-gray-500">{{ isRtl ? card.labelAr : card.labelEn }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-1.5 tabular-nums">{{ card.value }}</p>
                <span v-if="card.isAlert" class="absolute top-3 ltr:right-3 rtl:left-3 w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            </div>
        </div>

        <!-- ── Search + Filter ───────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-5">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="isRtl ? 'بحث بالمريض أو التطعيم...' : 'Search by patient or vaccine...'"
                        class="w-full ltr:pl-10 rtl:pr-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-500/20 focus:border-green-400 transition"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-500/20 focus:border-green-400 transition bg-white"
                >
                    <option value="">{{ isRtl ? 'كل الحالات' : 'All Statuses' }}</option>
                    <option value="given">{{ isRtl ? 'تم التطعيم' : 'Given' }}</option>
                    <option value="scheduled">{{ isRtl ? 'مجدولة' : 'Scheduled' }}</option>
                    <option value="overdue">{{ isRtl ? 'متأخرة' : 'Overdue' }}</option>
                    <option value="missed">{{ isRtl ? 'فائتة' : 'Missed' }}</option>
                </select>
            </div>
        </div>

        <!-- ── Vaccinations Table ────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'التطعيم' : 'Vaccine' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden sm:table-cell">{{ isRtl ? 'الجرعة' : 'Dose' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden md:table-cell">{{ isRtl ? 'تاريخ الجدولة' : 'Scheduled' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden lg:table-cell">{{ isRtl ? 'تاريخ التطعيم' : 'Given Date' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden xl:table-cell">{{ isRtl ? 'رقم الدفعة' : 'Batch #' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="v in (vaccinations?.data || [])"
                            :key="v.id"
                            class="border-b border-gray-50 hover:bg-green-50/30 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ v.patient?.full_name || v.patient_name || '-' }}</p>
                                    <p class="text-xs text-gray-400">{{ v.patient?.file_number || '' }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-gray-700">{{ v.vaccine_name || v.vaccine?.name || '-' }}</td>
                            <td class="px-5 py-3.5 text-center hidden sm:table-cell">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                                    {{ v.dose_number || v.dose || '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 hidden md:table-cell">{{ formatDate(v.scheduled_date) }}</td>
                            <td class="px-5 py-3.5 text-gray-500 hidden lg:table-cell">{{ formatDate(v.given_date) }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span
                                    :class="`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${getStatusStyle(v.status).bg} ${getStatusStyle(v.status).text}`"
                                >
                                    <span :class="`w-1.5 h-1.5 rounded-full ${getStatusStyle(v.status).dot}`" :style="v.status === 'overdue' ? 'animation: pulse 2s infinite' : ''"></span>
                                    {{ statusLabel(v.status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 font-mono text-xs hidden xl:table-cell">{{ v.batch_number || '-' }}</td>
                        </tr>
                        <tr v-if="!vaccinations?.data?.length">
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082" /></svg>
                                {{ isRtl ? 'لا توجد تطعيمات' : 'No vaccinations found' }}
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
                        :class="link.active ? 'bg-green-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        v-html="link.label"
                        preserve-state
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm text-gray-300"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ped-hero-up {
    animation: pedHeroUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedHeroUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ped-float {
    animation: pedFloat 6s ease-in-out infinite;
}
@keyframes pedFloat {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-12px); }
}
.ped-card-enter {
    animation: pedCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedCardEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
