<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import SkeletonLoader from '@/Components/Doctor/SkeletonLoader.vue';
import { getStatusConfig } from '@/Constants/visitStatus';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const modules = computed(() => page.props.modules || {});
const clinicalSlugs = ['derma', 'dental', 'pediatric'];
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([slug, m]) => m.is_enabled !== false && clinicalSlugs.includes(slug))
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const props = defineProps({
    visits: Object,
    filters: Object,
});

const mounted = ref(false);
const dataLoading = ref(true);
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const moduleFilter = ref(props.filters?.module || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const sortOrder = ref(props.filters?.sort || 'newest');

// View mode with localStorage persistence
const viewMode = ref('list');
onMounted(() => {
    try {
        const saved = localStorage.getItem('doctor_visits_viewMode');
        if (saved === 'grid' || saved === 'list') viewMode.value = saved;
    } catch {}
    setTimeout(() => { mounted.value = true; }, 50);
    setTimeout(() => { dataLoading.value = false; }, 600);
});

watch(viewMode, (val) => {
    try { localStorage.setItem('doctor_visits_viewMode', val); } catch {}
});

let debounce = null;
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => applyFilters(), 300);
});

watch(status, () => applyFilters());
watch(moduleFilter, () => applyFilters());
watch(dateFrom, () => applyFilters());
watch(dateTo, () => applyFilters());
watch(sortOrder, () => applyFilters());

function applyFilters() {
    router.get('/doctor/visits', {
        search: search.value || undefined,
        status: status.value || undefined,
        module: moduleFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        sort: sortOrder.value !== 'newest' ? sortOrder.value : undefined,
    }, { preserveState: true, replace: true });
}

function clearAllFilters() {
    search.value = '';
    status.value = '';
    moduleFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    sortOrder.value = 'newest';
    applyFilters();
}

const hasActiveFilters = computed(() => {
    return search.value || status.value || moduleFilter.value || dateFrom.value || dateTo.value;
});

const statusConfig = computed(() => getStatusConfig(isRtl.value));

const statusTabs = computed(() => [
    { key: '', label: isRtl.value ? 'الكل' : 'All', active: status.value === '' },
    { key: 'waiting', label: isRtl.value ? 'في الانتظار' : 'Waiting', active: status.value === 'waiting' },
    { key: 'in_progress', label: isRtl.value ? 'قيد التنفيذ' : 'In Progress', active: status.value === 'in_progress' },
    { key: 'completed', label: isRtl.value ? 'مكتمل' : 'Completed', active: status.value === 'completed' },
    { key: 'cancelled', label: isRtl.value ? 'ملغي' : 'Cancelled', active: status.value === 'cancelled' },
]);

// Stats computed from visits.data
const stats = computed(() => {
    const data = props.visits?.data || [];
    const today = new Date().toDateString();
    const counts = { waiting: 0, in_progress: 0, completed: 0, cancelled: 0 };
    let todayCount = 0;
    data.forEach(v => {
        if (counts[v.status] !== undefined) counts[v.status]++;
        if (v.visit_date && new Date(v.visit_date).toDateString() === today) todayCount++;
    });
    return {
        total: props.visits?.total || data.length,
        today: todayCount,
        ...counts,
    };
});

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(date) {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function getVisitTypeLabel(visit) {
    if (isRtl.value) {
        return (visit.service?.name_ar || visit.service?.name_en) ||
            ({ consultation: 'استشارة', session: 'جلسة', follow_up: 'متابعة' }[visit.visit_type] || visit.visit_type);
    }
    return (visit.service?.name_en || visit.service?.name_ar) || visit.visit_type;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'سجل الزيارات' : 'Visit History' }}</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ $t('a_visits') }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'جميع الزيارات المسندة إليك' : 'All visits assigned to you' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="/doctor/export/visits"
                            class="inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-medium text-white/80 bg-white/10 hover:bg-white/15 border border-white/10 rounded-xl transition-all backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            {{ isRtl ? 'تصدير' : 'Export' }}
                        </a>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 text-center">
                            <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Search + Filters in Hero -->
                <div class="space-y-3">
                    <!-- Search row -->
                    <div class="flex flex-col sm:flex-row gap-3"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
                    >
                        <div class="relative flex-1 max-w-lg">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="isRtl ? 'بحث باسم المريض...' : 'Search patient name...'"
                                class="w-full pl-12 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all"
                            />
                        </div>

                        <!-- Date Range Filters -->
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <input
                                    v-model="dateFrom"
                                    type="date"
                                    :title="isRtl ? 'من تاريخ' : 'From date'"
                                    class="pl-10 pr-3 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all [color-scheme:dark]"
                                />
                            </div>
                            <span class="text-gray-500 text-xs font-medium">{{ isRtl ? 'إلى' : 'to' }}</span>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <input
                                    v-model="dateTo"
                                    type="date"
                                    :title="isRtl ? 'إلى تاريخ' : 'To date'"
                                    class="pl-10 pr-3 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all [color-scheme:dark]"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Module filter pills (if multiple modules) -->
                    <div v-if="activeModules.length > 1"
                        class="flex items-center gap-1.5"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.12s"
                    >
                        <span class="text-xs text-gray-500 font-medium mr-1">{{ isRtl ? 'القسم:' : 'Dept:' }}</span>
                        <button
                            @click="moduleFilter = ''"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 whitespace-nowrap"
                            :class="moduleFilter === ''
                                ? 'bg-[#C4A265]/20 text-[#C4A265] border border-[#C4A265]/30'
                                : 'text-gray-400 hover:text-gray-300 hover:bg-white/5 border border-transparent'"
                        >
                            {{ isRtl ? 'الكل' : 'All' }}
                        </button>
                        <button v-for="mod in activeModules" :key="mod.slug"
                            @click="moduleFilter = mod.slug"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 whitespace-nowrap"
                            :class="moduleFilter === mod.slug
                                ? 'bg-[#C4A265]/20 text-[#C4A265] border border-[#C4A265]/30'
                                : 'text-gray-400 hover:text-gray-300 hover:bg-white/5 border border-transparent'"
                        >
                            {{ mod.name }}
                        </button>
                    </div>

                    <!-- Status Tabs -->
                    <div class="flex items-center gap-1 overflow-x-auto scrollbar-none"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                    >
                        <button v-for="tab in statusTabs" :key="tab.key"
                            @click="status = tab.key"
                            class="px-4 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 whitespace-nowrap"
                            :class="tab.active
                                ? 'bg-white/15 text-white border border-white/20'
                                : 'text-gray-400 hover:text-gray-300 hover:bg-white/5 border border-transparent'"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.18s"
        >
            <!-- Total -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-gray-800">{{ stats.total }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide truncate">{{ isRtl ? 'الإجمالي' : 'Total' }}</p>
                    </div>
                </div>
            </div>
            <!-- Today -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-gray-800">{{ stats.today }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide truncate">{{ isRtl ? 'اليوم' : 'Today' }}</p>
                    </div>
                </div>
            </div>
            <!-- Waiting -->
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
            <!-- In Progress -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-blue-600">{{ stats.in_progress }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide truncate">{{ isRtl ? 'جارٍ' : 'Active' }}</p>
                    </div>
                </div>
            </div>
            <!-- Completed -->
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
            <!-- Cancelled -->
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

        <!-- Toolbar: View Toggle + Sort -->
        <div class="flex items-center justify-between"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div class="flex items-center gap-2">
                <!-- Sort -->
                <div class="flex items-center bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <button
                        @click="sortOrder = 'newest'"
                        class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium transition-colors"
                        :class="sortOrder === 'newest' ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                        {{ isRtl ? 'الأحدث' : 'Newest' }}
                    </button>
                    <button
                        @click="sortOrder = 'oldest'"
                        class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium transition-colors"
                        :class="sortOrder === 'oldest' ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" /></svg>
                        {{ isRtl ? 'الأقدم' : 'Oldest' }}
                    </button>
                </div>

                <!-- Clear filters -->
                <button v-if="hasActiveFilters"
                    @click="clearAllFilters"
                    class="flex items-center gap-1 px-3 py-2 text-xs font-medium text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded-lg border border-red-200 transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    {{ isRtl ? 'مسح الفلاتر' : 'Clear filters' }}
                </button>
            </div>

            <!-- View Toggle -->
            <div class="flex items-center bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <button
                    @click="viewMode = 'list'"
                    class="p-2 transition-colors"
                    :class="viewMode === 'list' ? 'bg-gray-900 text-white' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    :title="isRtl ? 'عرض قائمة' : 'List view'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <button
                    @click="viewMode = 'grid'"
                    class="p-2 transition-colors"
                    :class="viewMode === 'grid' ? 'bg-gray-900 text-white' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'"
                    :title="isRtl ? 'عرض بطاقات' : 'Grid view'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                </button>
            </div>
        </div>

        <!-- Skeleton Loading State -->
        <SkeletonLoader v-if="dataLoading" />

        <!-- LIST VIEW -->
        <div v-if="!dataLoading && viewMode === 'list'" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.22s"
        >
            <div v-if="visits.data?.length > 0" class="divide-y divide-gray-100/80">
                <Link v-for="(visit, index) in visits.data" :key="visit.id"
                    :href="`/doctor/visits/${visit.id}`"
                    class="group flex items-center gap-4 px-3 sm:px-5 py-4 hover:bg-gray-50/60 transition-all duration-200"
                    :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                    :style="{ transition: 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.25 + index * 0.04}s` }"
                >
                    <!-- Status Icon -->
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center border flex-shrink-0 transition-transform duration-200 group-hover:scale-105"
                        :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.border]"
                    >
                        <svg v-if="visit.status === 'waiting'" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else-if="visit.status === 'in_progress'" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        <svg v-else-if="visit.status === 'completed'" class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </div>

                    <!-- Visit Info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-gray-900 transition-colors">{{ visit.patient?.full_name }}</p>
                            <span v-if="visit.patient?.file_number" class="flex-shrink-0 font-mono text-[10px] text-gray-400">{{ visit.patient.file_number }}</span>
                            <span v-if="visit.package_bundle_booking_id" class="flex-shrink-0 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-50 text-purple-600 border border-purple-200">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                {{ isRtl ? 'باقة' : 'PKG' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-xs text-gray-500">{{ getVisitTypeLabel(visit) }}</span>
                            <template v-if="visit.package_bundle_booking?.package_bundle">
                                <span class="text-gray-200">&middot;</span>
                                <span class="text-xs text-purple-500 font-medium truncate">{{ isRtl ? (visit.package_bundle_booking.package_bundle.name_ar || visit.package_bundle_booking.package_bundle.name_en) : (visit.package_bundle_booking.package_bundle.name_en || visit.package_bundle_booking.package_bundle.name_ar) }}</span>
                            </template>
                            <span class="text-gray-200">&middot;</span>
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                {{ formatDate(visit.visit_date) }}
                            </span>
                            <template v-if="visit.status === 'completed' && visit.doctor_commission">
                                <span class="text-gray-200">&middot;</span>
                                <span class="text-xs text-emerald-600 font-semibold">{{ visit.doctor_commission }} {{ isRtl ? 'ر.س' : 'SAR' }}</span>
                            </template>
                        </div>
                    </div>

                    <!-- Status + Arrow -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border"
                            :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text, statusConfig[visit.status]?.border]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[visit.status]?.dot"></span>
                            {{ statusConfig[visit.status]?.labelFull || statusConfig[visit.status]?.label }}
                        </span>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-[#C4A265] transition-all" :class="isRtl ? 'group-hover:-translate-x-0.5 rotate-180' : 'group-hover:translate-x-0.5'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </Link>
            </div>

            <!-- Empty State (list) -->
            <div v-else class="py-20 text-center">
                <div class="w-20 h-20 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-75'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
                >
                    <svg v-if="hasActiveFilters" class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <svg v-else class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
                    style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.35s"
                >
                    <template v-if="search && status">{{ isRtl ? `لا توجد زيارات تطابق "${search}" بحالة "${statusTabs.find(t => t.key === status)?.label}"` : `No visits matching "${search}" with status "${statusTabs.find(t => t.key === status)?.label}"` }}</template>
                    <template v-else-if="search">{{ isRtl ? `لا توجد نتائج لـ "${search}"` : `No results for "${search}"` }}</template>
                    <template v-else-if="status">{{ isRtl ? `لا توجد زيارات بحالة "${statusTabs.find(t => t.key === status)?.label}"` : `No visits with status "${statusTabs.find(t => t.key === status)?.label}"` }}</template>
                    <template v-else-if="dateFrom || dateTo">{{ isRtl ? 'لا توجد زيارات في هذا النطاق الزمني' : 'No visits in this date range' }}</template>
                    <template v-else>{{ isRtl ? 'لا توجد زيارات' : 'No visits found' }}</template>
                </p>
                <p class="text-xs text-gray-400 mt-1.5"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
                    style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.4s"
                >
                    <template v-if="hasActiveFilters">
                        {{ isRtl ? 'جرب تعديل البحث أو الفلاتر' : 'Try adjusting your search or filters' }}
                        <button @click="clearAllFilters" class="text-[#C4A265] hover:underline font-medium mx-1">{{ isRtl ? 'مسح الكل' : 'Clear all' }}</button>
                    </template>
                    <template v-else>{{ isRtl ? 'ستظهر زياراتك هنا بمجرد جدولتها' : 'Your visits will appear here once scheduled' }}</template>
                </p>
            </div>

            <!-- Pagination (list) -->
            <div v-if="visits.links?.length > 3" class="flex items-center justify-center gap-1 px-3 sm:px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                <template v-for="link in visits.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'"
                        v-html="link.label" preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- GRID VIEW -->
        <template v-if="!dataLoading && viewMode === 'grid'">
            <div v-if="visits.data?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link v-for="(visit, index) in visits.data" :key="visit.id"
                    :href="`/doctor/visits/${visit.id}`"
                    class="group relative bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transition: 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.2 + index * 0.05}s` }"
                >
                    <!-- Status top stripe -->
                    <div class="h-1 w-full bg-gradient-to-r" :class="statusConfig[visit.status]?.gradient"></div>

                    <div class="p-4 space-y-3">
                        <!-- Header: Patient + Status -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-gray-900">{{ visit.patient?.full_name }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span v-if="visit.patient?.file_number" class="font-mono text-[10px] text-gray-400">{{ visit.patient.file_number }}</span>
                                    <span v-if="visit.package_bundle_booking_id" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-50 text-purple-600 border border-purple-200">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        {{ isRtl ? 'باقة' : 'PKG' }}
                                    </span>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold px-2 py-0.5 rounded-full border flex-shrink-0"
                                :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text, statusConfig[visit.status]?.border]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[visit.status]?.dot"></span>
                                {{ statusConfig[visit.status]?.labelFull || statusConfig[visit.status]?.label }}
                            </span>
                        </div>

                        <!-- Service / Type -->
                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            <span class="truncate">{{ getVisitTypeLabel(visit) }}</span>
                        </div>

                        <!-- Package name if exists -->
                        <div v-if="visit.package_bundle_booking?.package_bundle" class="flex items-center gap-1.5 text-xs text-purple-500 font-medium">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            <span class="truncate">{{ isRtl ? (visit.package_bundle_booking.package_bundle.name_ar || visit.package_bundle_booking.package_bundle.name_en) : (visit.package_bundle_booking.package_bundle.name_en || visit.package_bundle_booking.package_bundle.name_ar) }}</span>
                        </div>

                        <!-- Footer: Date + Commission -->
                        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                            <div class="flex items-center gap-1.5 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>{{ formatDate(visit.visit_date) }}</span>
                                <span v-if="formatTime(visit.visit_date)" class="text-gray-300">&middot;</span>
                                <span v-if="formatTime(visit.visit_date)">{{ formatTime(visit.visit_date) }}</span>
                            </div>
                            <span v-if="visit.status === 'completed' && visit.doctor_commission" class="text-xs font-semibold text-emerald-600">
                                {{ visit.doctor_commission }} {{ isRtl ? 'ر.س' : 'SAR' }}
                            </span>
                        </div>
                    </div>

                    <!-- Hover arrow indicator -->
                    <div class="absolute top-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                        :class="isRtl ? 'left-3' : 'right-3'"
                    >
                        <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </Link>
            </div>

            <!-- Empty State (grid) -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100/80 py-20 text-center">
                <div class="w-20 h-20 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100"
                    :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-75'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
                >
                    <svg v-if="hasActiveFilters" class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <svg v-else class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
                    style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.35s"
                >
                    <template v-if="search && status">{{ isRtl ? `لا توجد زيارات تطابق "${search}" بحالة "${statusTabs.find(t => t.key === status)?.label}"` : `No visits matching "${search}" with status "${statusTabs.find(t => t.key === status)?.label}"` }}</template>
                    <template v-else-if="search">{{ isRtl ? `لا توجد نتائج لـ "${search}"` : `No results for "${search}"` }}</template>
                    <template v-else-if="status">{{ isRtl ? `لا توجد زيارات بحالة "${statusTabs.find(t => t.key === status)?.label}"` : `No visits with status "${statusTabs.find(t => t.key === status)?.label}"` }}</template>
                    <template v-else-if="dateFrom || dateTo">{{ isRtl ? 'لا توجد زيارات في هذا النطاق الزمني' : 'No visits in this date range' }}</template>
                    <template v-else>{{ isRtl ? 'لا توجد زيارات' : 'No visits found' }}</template>
                </p>
                <p class="text-xs text-gray-400 mt-1.5"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
                    style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.4s"
                >
                    <template v-if="hasActiveFilters">
                        {{ isRtl ? 'جرب تعديل البحث أو الفلاتر' : 'Try adjusting your search or filters' }}
                        <button @click="clearAllFilters" class="text-[#C4A265] hover:underline font-medium mx-1">{{ isRtl ? 'مسح الكل' : 'Clear all' }}</button>
                    </template>
                    <template v-else>{{ isRtl ? 'ستظهر زياراتك هنا بمجرد جدولتها' : 'Your visits will appear here once scheduled' }}</template>
                </p>
            </div>

            <!-- Pagination (grid) -->
            <div v-if="visits.links?.length > 3" class="flex items-center justify-center gap-1 py-2">
                <template v-for="link in visits.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100 bg-white border border-gray-200'"
                        v-html="link.label" preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </template>
    </div>
</template>
