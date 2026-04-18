<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import SearchableSelect from '@/Components/Doctor/SearchableSelect.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visits: Object,
    filters: Object,
});

const mounted = ref(false);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

let debounce = null;
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => applyFilters(), 300);
});
watch(status, () => applyFilters());
watch(dateFrom, () => applyFilters());
watch(dateTo, () => applyFilters());

function applyFilters() {
    router.get('/doctor/pediatric/visits', {
        search: search.value || undefined,
        status: status.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearAllFilters() {
    search.value = '';
    status.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
}

const hasActiveFilters = computed(() => {
    return search.value || status.value || dateFrom.value || dateTo.value;
});

const statusOptions = computed(() => [
    { value: '', label: isRtl.value ? 'جميع الحالات' : 'All Statuses' },
    { value: 'waiting', label: isRtl.value ? 'في الانتظار' : 'Waiting' },
    { value: 'in_progress', label: isRtl.value ? 'قيد التنفيذ' : 'In Progress' },
    { value: 'completed', label: isRtl.value ? 'مكتمل' : 'Completed' },
    { value: 'cancelled', label: isRtl.value ? 'ملغي' : 'Cancelled' },
]);

const statusConfig = computed(() => ({
    waiting: { label: isRtl.value ? 'انتظار' : 'Waiting', bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-400' },
    in_progress: { label: isRtl.value ? 'جاري' : 'In Progress', bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]' },
    completed: { label: isRtl.value ? 'مكتمل' : 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-gray-50', text: 'text-gray-500', border: 'border-gray-200', dot: 'bg-gray-400' },
}));

const stats = computed(() => {
    const data = props.visits?.data || [];
    const counts = { waiting: 0, in_progress: 0, completed: 0, cancelled: 0 };
    data.forEach(v => {
        if (counts[v.status] !== undefined) counts[v.status]++;
    });
    return { total: props.visits?.total || data.length, ...counts };
});

function calculateAge(dob) {
    if (!dob) return isRtl.value ? 'غير معروف' : 'Unknown';
    const birth = new Date(dob);
    const now = new Date();
    let years = now.getFullYear() - birth.getFullYear();
    let months = now.getMonth() - birth.getMonth();
    if (months < 0) { years--; months += 12; }
    if (now.getDate() < birth.getDate()) months--;
    if (months < 0) { years--; months += 12; }
    if (years < 1) {
        return isRtl.value ? `${months} شهر` : `${months}mo`;
    }
    if (years < 3) {
        return isRtl.value ? `${years} سنة و ${months} شهر` : `${years}y ${months}m`;
    }
    return isRtl.value ? `${years} سنة` : `${years}y`;
}

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    const loc = isRtl.value ? 'ar-EG' : 'en-GB';
    return d.toLocaleDateString(loc, { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(date) {
    if (!date) return '';
    const d = new Date(date);
    const loc = isRtl.value ? 'ar-EG' : 'en-US';
    return d.toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit', hour12: true });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-500 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-300/20 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
            <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-emerald-400/10 rounded-full blur-xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/15 backdrop-blur-sm rounded-full mb-3">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                            <span class="text-white/90 text-xs font-medium">{{ isRtl ? 'طب الأطفال' : 'Pediatrics' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'زيارات الأطفال' : 'Pediatric Visits' }}</h1>
                        <p class="text-emerald-100 text-sm mt-1">{{ isRtl ? 'إدارة ومتابعة زيارات الأطفال' : 'Manage and track pediatric visits' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/20 text-center">
                            <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
                            <p class="text-xs text-emerald-100">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Search + Filters -->
                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row gap-3"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
                    >
                        <!-- Search -->
                        <div class="relative flex-1 max-w-lg">
                            <svg class="absolute ltr:left-4 rtl:right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="isRtl ? 'بحث باسم المريض أو ولي الأمر...' : 'Search patient or guardian name...'"
                                class="doctorato-input w-full ltr:pl-12 ltr:pr-4 rtl:pr-12 rtl:pl-4 py-3 bg-white/15 backdrop-blur-sm border border-white/20 rounded-xl text-sm text-white placeholder-white/50 focus:ring-2 focus:ring-white/30 focus:border-white/40 focus:bg-white/20 transition-all"
                            />
                        </div>

                        <!-- Status Select -->
                        <div class="w-full sm:w-52">
                            <SearchableSelect
                                v-model="status"
                                :options="statusOptions"
                                :placeholder="isRtl ? 'حالة الزيارة' : 'Visit Status'"
                                accentColor="#4CAF50"
                                size="md"
                            />
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="flex flex-wrap items-center gap-2"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                    >
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/50 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <input
                                v-model="dateFrom"
                                type="date"
                                :title="isRtl ? 'من تاريخ' : 'From date'"
                                class="doctorato-input pl-10 pr-3 py-2.5 bg-white/15 backdrop-blur-sm border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-white/30 focus:border-white/40 transition-all [color-scheme:dark]"
                            />
                        </div>
                        <span class="text-white/60 text-xs font-medium">{{ isRtl ? 'إلى' : 'to' }}</span>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/50 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <input
                                v-model="dateTo"
                                type="date"
                                :title="isRtl ? 'إلى تاريخ' : 'To date'"
                                class="doctorato-input pl-10 pr-3 py-2.5 bg-white/15 backdrop-blur-sm border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-white/30 focus:border-white/40 transition-all [color-scheme:dark]"
                            />
                        </div>

                        <button v-if="hasActiveFilters"
                            @click="clearAllFilters"
                            class="inline-flex items-center gap-1.5 px-3 py-2.5 text-xs font-medium text-white/80 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl transition-all"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            {{ isRtl ? 'مسح الفلاتر' : 'Clear' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.18s"
        >
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-amber-600">{{ stats.waiting }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide truncate">{{ isRtl ? 'انتظار' : 'Waiting' }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-[#1B365D]">{{ stats.in_progress }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide truncate">{{ isRtl ? 'جاري' : 'Active' }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-emerald-600">{{ stats.completed }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide truncate">{{ isRtl ? 'مكتمل' : 'Done' }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-gray-500">{{ stats.cancelled }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide truncate">{{ isRtl ? 'ملغي' : 'Cancelled' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visit Cards -->
        <div v-if="visits.data?.length > 0" class="space-y-3">
            <Link v-for="(visit, index) in visits.data" :key="visit.id"
                :href="`/doctor/pediatric/visits/${visit.id}`"
                class="group block bg-white rounded-xl border border-gray-100/80 shadow-sm hover:shadow-md hover:border-emerald-200/60 transition-all duration-200 overflow-hidden"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                :style="{ transition: 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.22 + index * 0.04}s` }"
            >
                <div class="px-4 sm:px-6 py-4">
                    <div class="flex items-start gap-4">
                        <!-- Child Avatar -->
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform duration-200 group-hover:scale-105"
                            :class="visit.patient?.gender === 'female'
                                ? 'bg-amber-50 border border-amber-200'
                                : 'bg-slate-50 border border-slate-200'"
                        >
                            <svg v-if="visit.patient?.gender === 'female'" class="w-6 h-6 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2a6 6 0 016 6c0 2.22-1.21 4.16-3 5.2V16h2v2h-2v2h-2v-2h-2v-2h2v-2.8A6.004 6.004 0 016 8a6 6 0 016-6zm0 2a4 4 0 100 8 4 4 0 000-8z"/>
                            </svg>
                            <svg v-else class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9.5 11c1.93 0 3.5 1.57 3.5 3.5S11.43 18 9.5 18 6 16.43 6 14.5 7.57 11 9.5 11zm0 2C8.67 13 8 13.67 8 14.5S8.67 16 9.5 16s1.5-.67 1.5-1.5S10.33 13 9.5 13zM15 3l4 4-1.5 1.5L19 10l-2 2-1.5-1.5L14 12l-1.5-1.5 4.5-4.5L15 4z"/>
                            </svg>
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-emerald-700 transition-colors">
                                        {{ visit.patient?.full_name }}
                                    </p>
                                    <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        {{ calculateAge(visit.patient?.date_of_birth) }}
                                    </span>
                                </div>
                                <!-- Status Badge -->
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border w-fit"
                                    :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text, statusConfig[visit.status]?.border]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[visit.status]?.dot"></span>
                                    {{ statusConfig[visit.status]?.label }}
                                </span>
                            </div>

                            <!-- Details Row -->
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2">
                                <!-- Guardian -->
                                <span v-if="visit.patient?.guardian_name" class="inline-flex items-center gap-1 text-xs text-gray-500">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ visit.patient.guardian_name }}
                                </span>
                                <!-- Date -->
                                <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ formatDate(visit.visit_date) }}
                                    <template v-if="formatTime(visit.visit_date)">
                                        <span class="text-gray-300 mx-0.5">|</span>
                                        {{ formatTime(visit.visit_date) }}
                                    </template>
                                </span>
                                <!-- Visit Type -->
                                <span v-if="visit.visit_type" class="hidden sm:inline-flex items-center gap-1 text-xs text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                    {{ visit.visit_type }}
                                </span>
                            </div>

                            <!-- Diagnosis Snippet -->
                            <p v-if="visit.diagnosis" class="mt-2 text-xs text-gray-400 line-clamp-1">
                                <span class="font-medium text-gray-500">{{ isRtl ? 'التشخيص:' : 'Dx:' }}</span>
                                {{ visit.diagnosis }}
                            </p>
                        </div>

                        <!-- Arrow -->
                        <div class="flex-shrink-0 self-center">
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-emerald-500 transition-all duration-200" :class="isRtl ? 'group-hover:-translate-x-0.5 rotate-180' : 'group-hover:translate-x-0.5'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100/80 py-20 text-center"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.25s"
        >
            <div class="w-20 h-20 mx-auto bg-emerald-50 rounded-2xl flex items-center justify-center mb-4 border border-emerald-100">
                <svg v-if="hasActiveFilters" class="w-10 h-10 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <svg v-else class="w-10 h-10 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
            <p class="text-sm font-medium text-gray-500">
                {{ hasActiveFilters
                    ? (isRtl ? 'لا توجد زيارات تطابق البحث' : 'No visits match your filters')
                    : (isRtl ? 'لا توجد زيارات أطفال حتى الآن' : 'No pediatric visits yet')
                }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                {{ hasActiveFilters
                    ? (isRtl ? 'جرب تعديل معايير البحث' : 'Try adjusting your search criteria')
                    : (isRtl ? 'ستظهر الزيارات هنا بمجرد إضافتها' : 'Visits will appear here once created')
                }}
            </p>
            <button v-if="hasActiveFilters"
                @click="clearAllFilters"
                class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                {{ isRtl ? 'مسح جميع الفلاتر' : 'Clear all filters' }}
            </button>
        </div>

        <!-- Pagination -->
        <div v-if="visits.links && visits.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
        >
            <template v-for="(link, i) in visits.links" :key="i">
                <Link v-if="link.url"
                    :href="link.url"
                    class="px-3.5 py-2 text-xs font-medium rounded-lg border transition-all duration-200"
                    :class="link.active
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                        : 'bg-white text-gray-600 border-gray-200 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200'"
                    preserveState
                    v-html="link.label"
                />
                <span v-else
                    class="px-3.5 py-2 text-xs font-medium rounded-lg border border-gray-100 text-gray-300 bg-gray-50"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
