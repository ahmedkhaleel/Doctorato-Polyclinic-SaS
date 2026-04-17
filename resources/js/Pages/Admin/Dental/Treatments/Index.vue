<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

// ─── Toast + Loading ────────────────────────────────────
const isFiltering = ref(false);
const showSuccess = ref(false);
const successMessage = ref('');

const { t } = useLocale();
const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

const props = defineProps({
    treatments: Object,
    filters: Object,
    doctors: Array,
    treatmentTypes: Array,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const typeFilter = ref(props.filters?.treatment_type_id || '');
const doctorFilter = ref(props.filters?.doctor_id || '');
const toothNumber = ref(props.filters?.tooth_number || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const showFilters = ref(false);

let searchTimeout = null;

function applyFilters() {
    clearTimeout(searchTimeout);
    isFiltering.value = true;
    searchTimeout = setTimeout(() => {
        router.get('/admin/dental/treatments', {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            treatment_type_id: typeFilter.value || undefined,
            doctor_id: doctorFilter.value || undefined,
            tooth_number: toothNumber.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => { isFiltering.value = false; },
        });
    }, 400);
}

watch([search, statusFilter, typeFilter, doctorFilter, toothNumber, dateFrom, dateTo], applyFilters);

watch(() => page.props.flash?.success, (msg) => {
    if (msg) { successMessage.value = msg; showSuccess.value = true; setTimeout(() => { showSuccess.value = false; }, 4000); }
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusConfig = {
    planned: { bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-400' },
    in_progress: { bg: 'bg-slate-50', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    completed: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    cancelled: { bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' },
};

function getStatus(status) { return statusConfig[status] || statusConfig.planned; }

const hasActiveFilters = computed(() =>
    statusFilter.value || typeFilter.value || doctorFilter.value || toothNumber.value || dateFrom.value || dateTo.value
);

function clearFilters() {
    statusFilter.value = '';
    typeFilter.value = '';
    doctorFilter.value = '';
    toothNumber.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}
</script>

<template>
    <AdminLayout :title="$t('a_dental_treatments')">
        <div class="space-y-6">
            <!-- ── Hero Header ───────────────────────────────────── -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-7">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-[#2C4E7A]/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-emerald-300/15 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">{{ $t('a_dental_treatments') }}</h1>
                            <p class="text-slate-100/80 text-sm mt-0.5">{{ $t('a_dental_treatments_desc') }}</p>
                        </div>
                    </div>
                    <Link
                        href="/admin/dental"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all duration-200"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        {{ $t('a_dental_dashboard') }}
                    </Link>
                </div>
            </div>

            <!-- ── Search + Filters ──────────────────────────────── -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.15s">
                <div class="p-5">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <svg v-if="!isFiltering" class="absolute top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <svg v-else class="absolute top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 w-4.5 h-4.5 text-[#1B365D] animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="$t('a_search_patient_tooth_notes')"
                                class="w-full ltr:pl-11 rtl:pr-11 ltr:pr-4 rtl:pl-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-slate-200/60 focus:border-slate-300 transition-all duration-200"
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

                    <!-- Expandable filter area -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 max-h-0"
                        enter-to-class="opacity-100 max-h-60"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 max-h-60"
                        leave-to-class="opacity-0 max-h-0"
                    >
                        <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-100 overflow-hidden">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <select v-model="statusFilter" class="dental-select">
                                    <option value="">{{ $t('a_all_statuses') }}</option>
                                    <option value="planned">{{ $t('a_treatment_status_planned') }}</option>
                                    <option value="in_progress">{{ $t('a_treatment_status_in_progress') }}</option>
                                    <option value="completed">{{ $t('a_treatment_status_completed') }}</option>
                                    <option value="cancelled">{{ $t('a_treatment_status_cancelled') }}</option>
                                </select>
                                <select v-model="typeFilter" class="dental-select">
                                    <option value="">{{ $t('a_all_types') }}</option>
                                    <option v-for="tt in treatmentTypes" :key="tt.id" :value="tt.id">
                                        {{ locale === 'ar' ? (tt.name_ar || tt.name_en) : (tt.name_en || tt.name_ar) }}
                                    </option>
                                </select>
                                <select v-model="doctorFilter" class="dental-select">
                                    <option value="">{{ $t('a_all_doctors') }}</option>
                                    <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
                                        {{ locale === 'ar' ? doc.name_ar : doc.name_en }}
                                    </option>
                                </select>
                                <input v-model="toothNumber" type="text" :placeholder="locale === 'ar' ? 'رقم السن (مثال: 14, 36)' : 'Tooth # (e.g. 14, 36)'" class="dental-select font-mono" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ locale === 'ar' ? 'من' : 'From' }}</span>
                                    <input v-model="dateFrom" type="date" class="dental-select" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ locale === 'ar' ? 'الى' : 'To' }}</span>
                                    <input v-model="dateTo" type="date" class="dental-select" />
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
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_doctor') }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_tooth') }}#</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_treatment_type') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_cost') }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(treatment, idx) in treatments.data"
                                :key="treatment.id"
                                class="dental-row-enter border-b border-gray-50 hover:bg-slate-50/30 transition-colors duration-200"
                                :style="{ animationDelay: `${0.3 + idx * 0.04}s` }"
                            >
                                <td class="px-5 py-4 text-sm">
                                    <Link v-if="treatment.patient" :href="`/admin/patients/${treatment.patient.id}`" class="font-medium text-gray-900 hover:text-[#1B365D] transition-colors">
                                        {{ treatment.patient.full_name }}
                                    </Link>
                                    <div class="text-xs text-gray-400 mt-0.5 font-mono">{{ treatment.patient?.file_number }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    {{ treatment.doctor ? (locale === 'ar' ? treatment.doctor.name_ar : treatment.doctor.name_en) : '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-center">
                                    <span v-if="treatment.tooth_number" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-50 text-[#1B365D] font-mono font-bold text-sm">
                                        {{ treatment.tooth_number }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    {{ treatment.treatment_type ? (locale === 'ar' ? (treatment.treatment_type.name_ar || treatment.treatment_type.name_en) : (treatment.treatment_type.name_en || treatment.treatment_type.name_ar)) : '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ formatCurrency(treatment.cost) }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span
                                        :class="[getStatus(treatment.status).bg, getStatus(treatment.status).text]"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                    >
                                        <span :class="getStatus(treatment.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ $t('a_treatment_status_' + (treatment.status || 'planned')) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-500">{{ formatDate(treatment.treatment_date || treatment.created_at) }}</td>
                            </tr>
                            <tr v-if="!treatments.data || treatments.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_treatments_found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="treatments.links && treatments.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ treatments.from }} {{ $t('a_to') }} {{ treatments.to }} {{ $t('a_of') }} {{ treatments.total }} {{ $t('a_results') }}</p>
                    <nav class="flex ltr:space-x-1 rtl:space-x-reverse rtl:space-x-1">
                        <template v-for="link in treatments.links" :key="link.label">
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

        <!-- Success Toast -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition ease-in duration-200" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
            <div v-if="showSuccess" class="fixed bottom-6 ltr:right-6 rtl:left-6 z-50 flex items-center gap-3 px-5 py-3 bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-200/50">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ successMessage }}</span>
            </div>
        </Transition>
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

.dental-hero-enter {
    animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.dental-card-enter {
    animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.dental-row-enter {
    animation: dentalRowEnter 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

[dir="rtl"] .dental-row-enter {
    animation-name: dentalRowEnterRtl;
}
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
