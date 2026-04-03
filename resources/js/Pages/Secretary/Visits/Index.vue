<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visits: Object,
    filters: Object,
});

const modules = computed(() => page.props.modules || {});
const clinicalSlugs = ['derma', 'dental'];
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([slug, m]) => m.is_enabled !== false && clinicalSlugs.includes(slug))
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const visitTypeFilter = ref(props.filters?.visit_type || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        visit_type: visitTypeFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    };
}

function applyFilters() {
    router.get('/secretary/visits', buildParams(), { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch([statusFilter, visitTypeFilter, dateFrom, dateTo, moduleFilter], () => applyFilters());

function clearFilters() {
    search.value = '';
    statusFilter.value = '';
    visitTypeFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    moduleFilter.value = '';
    router.get('/secretary/visits', {}, { preserveState: true, replace: true });
}

const hasActiveFilters = computed(() => {
    return !!(search.value || statusFilter.value || visitTypeFilter.value || dateFrom.value || dateTo.value || moduleFilter.value);
});

const statusConfig = {
    waiting:     { label: 'Waiting',     labelAr: 'انتظار',  bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   border: 'border-amber-200' },
    in_progress: { label: 'In Progress', labelAr: 'جاري',    bg: 'bg-blue-50',    text: 'text-blue-700',    dot: 'bg-blue-500',    border: 'border-blue-200' },
    completed:   { label: 'Completed',   labelAr: 'مكتمل',   bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', border: 'border-emerald-200' },
    cancelled:   { label: 'Cancelled',   labelAr: 'ملغي',    bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     border: 'border-red-200' },
};

function getStatus(status) {
    return statusConfig[status] || { label: status, labelAr: status, bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-500', border: 'border-gray-200' };
}

const visitTypeConfig = {
    consultation: { label: 'Consultation', labelAr: 'استشارة', bg: 'bg-teal-50',  text: 'text-teal-700' },
    session:      { label: 'Session',      labelAr: 'جلسة',    bg: 'bg-cyan-50',  text: 'text-cyan-700' },
    follow_up:    { label: 'Follow Up',    labelAr: 'متابعة',  bg: 'bg-sky-50',   text: 'text-sky-700' },
};

function getVisitType(type) {
    return visitTypeConfig[type] || { label: type, labelAr: type, bg: 'bg-gray-50', text: 'text-gray-700' };
}

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    return d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'short', day: '2-digit', month: 'short' });
}

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- ═══ HERO HEADER ═══ -->
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 30% 50%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#0d9488] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'إدارة الزيارات' : 'Visit Management' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'الزيارات' : 'Visits' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'إدارة ومتابعة جميع زيارات المرضى' : 'Manage and track all patient visits' }}</p>
                    </div>
                    <Link
                        href="/secretary/bookings/create"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0d9488] hover:bg-[#0b8278] transition-all shadow-lg shadow-[#0d9488]/20"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'حجز جديد' : 'New Booking' }}
                    </Link>
                </div>

                <!-- Filters in Hero -->
                <div class="flex flex-wrap items-center gap-3 mt-6">
                    <div class="relative flex-1 min-w-[220px] max-w-sm">
                        <svg class="absolute left-3 rtl:left-auto rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالاسم أو الهاتف...' : 'Search by patient name or phone...'"
                            class="w-full ltr:pl-10 ltr:pr-4 rtl:pr-10 rtl:pl-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] transition"
                        />
                    </div>
                    <select v-model="statusFilter" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                        <option value="">{{ isRtl ? 'جميع الحالات' : 'All Status' }}</option>
                        <option value="waiting">{{ isRtl ? 'انتظار' : 'Waiting' }}</option>
                        <option value="in_progress">{{ isRtl ? 'جاري' : 'In Progress' }}</option>
                        <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                        <option value="cancelled">{{ isRtl ? 'ملغي' : 'Cancelled' }}</option>
                    </select>
                    <select v-if="activeModules.length > 1" v-model="moduleFilter" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                        <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                        <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
                    </select>
                    <select v-model="visitTypeFilter" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                        <option value="">{{ isRtl ? 'جميع الأنواع' : 'All Types' }}</option>
                        <option value="consultation">{{ isRtl ? 'استشارة' : 'Consultation' }}</option>
                        <option value="session">{{ isRtl ? 'جلسة' : 'Session' }}</option>
                        <option value="follow_up">{{ isRtl ? 'متابعة' : 'Follow Up' }}</option>
                    </select>
                    <div class="flex items-center gap-2">
                        <input v-model="dateFrom" type="date" :max="dateTo || undefined" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [color-scheme:dark]" />
                        <span class="text-gray-400 text-xs">{{ isRtl ? 'إلى' : 'to' }}</span>
                        <input v-model="dateTo" type="date" :min="dateFrom || undefined" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [color-scheme:dark]" />
                    </div>
                    <button v-if="hasActiveFilters" @click="clearFilters" class="p-2.5 rounded-xl text-gray-400 hover:text-red-400 hover:bg-white/10 transition" :title="isRtl ? 'مسح الفلاتر' : 'Clear filters'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ VISIT CARDS ═══ -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="visit in visits.data"
                :key="visit.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5">
                    <!-- Patient Avatar & Info -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div :class="[getStatus(visit.status).bg, getStatus(visit.status).border]" class="w-12 h-12 rounded-xl border flex items-center justify-center flex-shrink-0">
                            <span :class="getStatus(visit.status).dot" class="w-3 h-3 rounded-full"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-gray-900 text-[15px]">{{ visit.patient?.full_name || '-' }}</p>
                                <span :class="[getVisitType(visit.visit_type).bg, getVisitType(visit.visit_type).text]" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold">
                                    {{ isRtl ? getVisitType(visit.visit_type).labelAr : getVisitType(visit.visit_type).label }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span :class="[getStatus(visit.status).bg, getStatus(visit.status).text]" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold">
                                    {{ isRtl ? getStatus(visit.status).labelAr : getStatus(visit.status).label }}
                                </span>
                                <span v-if="visit.patient?.phone" class="text-xs text-gray-400 dir-ltr">{{ visit.patient.phone }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Visit Details -->
                    <div class="flex items-center gap-5 sm:gap-6">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</p>
                            <p class="text-xs font-semibold text-gray-700 mt-0.5 max-w-[100px] truncate">{{ visit.doctor?.name_en || visit.doctor?.name || '-' }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</p>
                            <p class="text-xs font-semibold text-gray-700 mt-0.5 max-w-[100px] truncate">{{ visit.service?.name_en || visit.service?.name || '-' }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</p>
                            <p class="text-xs font-semibold text-gray-700 mt-0.5">{{ formatDate(visit.visit_date) }}</p>
                        </div>
                        <div v-if="visit.booking" class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الحجز' : 'Booking' }}</p>
                            <Link :href="`/secretary/bookings/${visit.booking.id}`" class="text-xs font-mono font-semibold text-[#0d9488] hover:text-[#0b8278] mt-0.5 block">
                                {{ visit.booking.booking_number || `#${visit.booking.id}` }}
                            </Link>
                        </div>
                    </div>

                    <!-- Action -->
                    <Link
                        :href="`/secretary/visits/${visit.id}`"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-[#0d9488] bg-[#0d9488]/5 hover:bg-[#0d9488]/10 rounded-xl transition-colors flex-shrink-0"
                    >
                        {{ isRtl ? 'عرض التفاصيل' : 'View Details' }}
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!visits.data || visits.data.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد زيارات' : 'No visits found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'جرب تغيير الفلاتر أو إنشاء حجز جديد' : 'Try adjusting your filters or create a new booking' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="visits.links && visits.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
            <p class="text-xs text-gray-500">
                {{ isRtl ? 'عرض' : 'Showing' }} <span class="font-semibold">{{ visits.from }}</span> {{ isRtl ? 'إلى' : 'to' }} <span class="font-semibold">{{ visits.to }}</span> {{ isRtl ? 'من' : 'of' }} <span class="font-semibold">{{ visits.total }}</span> {{ isRtl ? 'نتيجة' : 'results' }}
            </p>
            <nav class="flex items-center gap-1">
                <template v-for="link in visits.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#0d9488] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </nav>
        </div>
    </div>
</template>
