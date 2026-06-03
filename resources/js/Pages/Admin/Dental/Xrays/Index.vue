<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

const props = defineProps({
    xrays: Object,
    filters: Object,
    xrayTypes: Array,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const viewMode = ref('table');
const showFilters = ref(false);

let searchTimeout = null;

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/dental/xrays', {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
}

watch([search, typeFilter, dateFrom, dateTo], applyFilters);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const hasActiveFilters = computed(() => typeFilter.value || dateFrom.value || dateTo.value);

function clearFilters() {
    typeFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}
</script>

<template>
    <AdminLayout :title="$t('a_dental_xrays')">
        <div class="space-y-6">
            <!-- ── Hero Header ───────────────────────────────────── -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-7">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-[#2C4E7A]/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-emerald-300/15 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">{{ $t('a_dental_xrays') }}</h1>
                            <p class="text-slate-100/80 text-sm mt-0.5">{{ $t('a_dental_xrays_desc') }}</p>
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
                        <!-- View Toggle -->
                        <div class="flex items-center bg-white/15 backdrop-blur-sm rounded-xl p-1 ring-1 ring-white/20">
                            <button
                                @click="viewMode = 'table'"
                                :class="viewMode === 'table' ? 'bg-white/90 text-[#1B365D] shadow-sm' : 'text-white/70 hover:text-white'"
                                class="px-3 py-1.5 text-sm rounded-lg transition-all duration-200"
                             aria-label="Table view">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'grid'"
                                :class="viewMode === 'grid' ? 'bg-white/90 text-[#1B365D] shadow-sm' : 'text-white/70 hover:text-white'"
                                class="px-3 py-1.5 text-sm rounded-lg transition-all duration-200"
                             aria-label="Grid view">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Search + Filters ──────────────────────────────── -->
            <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.15s">
                <div class="p-5">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="$t('a_search_patient_tooth_notes')"
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
                        enter-to-class="opacity-100 max-h-40"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 max-h-40"
                        leave-to-class="opacity-0 max-h-0"
                    >
                        <div v-if="showFilters" class="mt-4 pt-4 border-t border-gray-100 overflow-hidden">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <select v-model="typeFilter" class="doctorato-input dental-select">
                                    <option value="">{{ $t('a_all_types') }}</option>
                                    <option v-for="xType in xrayTypes" :key="xType.value || xType" :value="xType.value || xType">
                                        {{ $t('a_xray_type_' + (xType.value || xType)) }}
                                    </option>
                                </select>
                                <div class="flex items-center gap-2">
                                    <input v-model="dateFrom" type="date" class="doctorato-input dental-select flex-1" />
                                    <span class="text-gray-300">-</span>
                                    <input v-model="dateTo" type="date" class="doctorato-input dental-select flex-1" />
                                </div>
                                <div v-if="hasActiveFilters" class="flex items-center justify-end">
                                    <button @click="clearFilters" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                                        {{ locale === 'ar' ? 'مسح الفلاتر' : 'Clear filters' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- ── Table View ────────────────────────────────────── -->
            <div v-if="viewMode === 'table'" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay: 0.25s">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_type') }}</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_tooth') }}#</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_thumbnail') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_doctor') }}</th>
                                <th class="px-5 py-3.5 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                <th class="px-5 py-3.5 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(xray, idx) in xrays.data"
                                :key="xray.id"
                                class="dental-row-enter border-b border-gray-50 hover:bg-slate-50/30 transition-colors duration-200"
                                :style="{ animationDelay: `${0.3 + idx * 0.04}s` }"
                            >
                                <td class="px-5 py-4 text-sm">
                                    <Link v-if="xray.patient" :href="`/admin/patients/${xray.patient.id}`" class="font-medium text-gray-900 hover:text-[#1B365D] transition-colors">
                                        {{ xray.patient.full_name }}
                                    </Link>
                                    <div class="text-xs text-gray-400 mt-0.5 font-mono">{{ xray.patient?.file_number }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 bg-slate-50 text-[#1B365D] rounded-full text-xs font-semibold">
                                        {{ $t('a_xray_type_' + xray.type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-center">
                                    <span v-if="xray.tooth_number" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-50 text-[#1B365D] font-mono font-bold text-sm">
                                        {{ xray.tooth_number }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div v-if="xray.image_url" class="inline-block">
                                        <img
                                            :src="xray.image_url"
                                            :alt="$t('a_xray')"
                                            class="w-14 h-14 object-cover rounded-xl border-2 border-gray-100 cursor-pointer hover:border-slate-300 hover:shadow-lg transition-all duration-200"
                                        />
                                    </div>
                                    <div v-else class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-gray-50 border border-gray-100">
                                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">
                                    {{ xray.doctor ? (locale === 'ar' ? xray.doctor.name_ar : xray.doctor.name_en) : '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-500">{{ formatDate(xray.taken_date || xray.created_at) }}</td>
                                <td class="px-5 py-4 text-end">
                                    <Link
                                        v-if="xray.patient"
                                        :href="`/admin/dental/xrays/patient/${xray.patient.id}`"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1B365D] bg-slate-50 hover:bg-slate-100 transition-colors duration-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14" /></svg>
                                        {{ $t('a_view_all') }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!xrays.data || xrays.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_xrays_found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="xrays.links && xrays.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ xrays.from }} {{ $t('a_to') }} {{ xrays.to }} {{ $t('a_of') }} {{ xrays.total }} {{ $t('a_results') }}</p>
                    <nav class="flex ltr:space-x-1 rtl:space-x-reverse rtl:space-x-1">
                        <template v-for="link in xrays.links" :key="link.label">
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

            <!-- ── Grid View ─────────────────────────────────────── -->
            <div v-if="viewMode === 'grid'" class="dental-card-enter" style="animation-delay: 0.25s">
                <div v-if="!xrays.data || xrays.data.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 flex flex-col items-center justify-center py-16">
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">{{ $t('a_no_xrays_found') }}</p>
                </div>
                <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                    <div
                        v-for="(xray, idx) in xrays.data"
                        :key="xray.id"
                        class="dental-card-enter group bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden hover:shadow-lg hover:border-slate-200/50 hover:-translate-y-1 transition-all duration-300"
                        :style="{ animationDelay: `${0.3 + idx * 0.06}s` }"
                    >
                        <div class="aspect-square bg-gray-100 relative overflow-hidden">
                            <img
                                v-if="xray.image_url"
                                :src="xray.image_url"
                                :alt="$t('a_xray')"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-14 h-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="absolute top-3 start-3">
                                <span class="inline-flex items-center px-2.5 py-1 bg-[#1B365D]/90 backdrop-blur-sm text-white rounded-lg text-xs font-semibold shadow-sm">
                                    {{ $t('a_xray_type_' + xray.type) }}
                                </span>
                            </div>
                            <div v-if="xray.tooth_number" class="absolute top-3 end-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-white/90 backdrop-blur-sm text-[#1B365D] rounded-lg text-xs font-bold font-mono shadow-sm">
                                    {{ xray.tooth_number }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="font-semibold text-sm text-gray-900 group-hover:text-[#1B365D] transition-colors">
                                {{ xray.patient?.full_name || '-' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1.5 flex items-center justify-between">
                                <span>{{ xray.doctor ? (locale === 'ar' ? xray.doctor.name_ar : xray.doctor.name_en) : '' }}</span>
                                <span class="text-gray-400">{{ formatDate(xray.taken_date || xray.created_at) }}</span>
                            </div>
                            <Link
                                v-if="xray.patient"
                                :href="`/admin/dental/xrays/patient/${xray.patient.id}`"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1B365D] hover:text-[#1B365D] mt-3 transition-colors"
                            >
                                {{ $t('a_view_patient_xrays') }}
                                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Grid Pagination -->
                <div v-if="xrays.links && xrays.links.length > 3" class="mt-6 flex items-center justify-center">
                    <nav class="flex ltr:space-x-1 rtl:space-x-reverse rtl:space-x-1">
                        <template v-for="link in xrays.links" :key="link.label">
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
