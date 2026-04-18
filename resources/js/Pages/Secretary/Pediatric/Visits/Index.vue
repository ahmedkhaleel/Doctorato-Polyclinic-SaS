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

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

let searchTimeout;

function buildParams() {
    return {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    };
}

function applyFilters() {
    router.get('/secretary/pediatric/visits', buildParams(), { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch([statusFilter, dateFrom, dateTo], () => applyFilters());

function clearFilters() {
    search.value = '';
    statusFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
}

const visitStatusColors = {
    waiting: 'bg-amber-50 text-amber-700 border-amber-200',
    in_progress: 'bg-slate-50 text-[#1B365D] border-slate-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
    no_show: 'bg-gray-50 text-gray-500 border-gray-200',
};

const visitStatusLabels = computed(() => ({
    waiting: isRtl.value ? 'انتظار' : 'Waiting',
    in_progress: isRtl.value ? 'جارية' : 'In Progress',
    completed: isRtl.value ? 'مكتملة' : 'Completed',
    cancelled: isRtl.value ? 'ملغاة' : 'Cancelled',
    no_show: isRtl.value ? 'لم يحضر' : 'No Show',
}));

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(date) {
    if (!date) return '';
    return new Date(date).toLocaleTimeString(isRtl.value ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit' });
}

function calculateAge(dob) {
    if (!dob) return '-';
    const birth = new Date(dob);
    const now = new Date();
    const years = Math.floor((now - birth) / (365.25 * 24 * 60 * 60 * 1000));
    const months = Math.floor(((now - birth) % (365.25 * 24 * 60 * 60 * 1000)) / (30.44 * 24 * 60 * 60 * 1000));
    if (years < 1) return isRtl.value ? `${months} شهر` : `${months}mo`;
    if (years < 3) return isRtl.value ? `${years}س ${months}ش` : `${years}y ${months}m`;
    return isRtl.value ? `${years} سنة` : `${years}y`;
}

const hasActiveFilters = computed(() => search.value || statusFilter.value || dateFrom.value || dateTo.value);

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- HERO HEADER -->
        <div
            class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] overflow-hidden transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
        >
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 30% 50%, #4CAF50 0%, transparent 40%), radial-gradient(circle at 70% 50%, #0d9488 0%, transparent 50%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#4CAF50] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'عيادة الأطفال' : 'Pediatric Clinic' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'زيارات الأطفال' : 'Pediatric Visits' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'إدارة ومتابعة زيارات عيادة الأطفال' : 'Manage and track pediatric clinic visits' }}</p>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-6">
                    <div class="relative flex-1 min-w-[200px] max-w-md">
                        <svg class="absolute left-3 rtl:left-auto rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالاسم، رقم الملف...' : 'Search by name, file number...'"
                            class="doctorato-input w-full ltr:pl-10 ltr:pr-4 rtl:pr-10 rtl:pl-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#4CAF50]/50 focus:border-[#4CAF50] transition"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="px-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#4CAF50]/50 focus:border-[#4CAF50] [&>option]:text-gray-900"
                    >
                        <option value="">{{ isRtl ? 'جميع الحالات' : 'All Status' }}</option>
                        <option value="waiting">{{ isRtl ? 'انتظار' : 'Waiting' }}</option>
                        <option value="in_progress">{{ isRtl ? 'جارية' : 'In Progress' }}</option>
                        <option value="completed">{{ isRtl ? 'مكتملة' : 'Completed' }}</option>
                        <option value="cancelled">{{ isRtl ? 'ملغاة' : 'Cancelled' }}</option>
                        <option value="no_show">{{ isRtl ? 'لم يحضر' : 'No Show' }}</option>
                    </select>
                </div>

                <!-- Date Filters -->
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-400 font-medium">{{ isRtl ? 'من' : 'From' }}</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="doctorato-input px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#4CAF50]/50 focus:border-[#4CAF50] [color-scheme:dark]"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-400 font-medium">{{ isRtl ? 'إلى' : 'To' }}</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="doctorato-input px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#4CAF50]/50 focus:border-[#4CAF50] [color-scheme:dark]"
                        />
                    </div>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        {{ isRtl ? 'مسح الفلاتر' : 'Clear Filters' }}
                    </button>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الإجمالي' : 'Total' }}</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ visits?.total || 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'من' : 'From' }}</p>
                        <p class="text-2xl font-bold text-[#4CAF50] mt-1">{{ visits?.from || 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'إلى' : 'To' }}</p>
                        <p class="text-2xl font-bold text-[#0d9488] mt-1">{{ visits?.to || 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'لكل صفحة' : 'Per Page' }}</p>
                        <p class="text-2xl font-bold text-emerald-400 mt-1">{{ visits?.per_page || 15 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- VISIT CARDS -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="(visit, idx) in visits.data"
                :key="visit.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md hover:border-[#4CAF50]/20 transition-all duration-300 overflow-hidden"
                :style="{ transitionDelay: `${idx * 40}ms` }"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5">
                    <!-- Patient Avatar & Info -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-11 h-11 rounded-xl flex-shrink-0 flex items-center justify-center text-white text-sm font-bold bg-gradient-to-br from-[#4CAF50] to-[#0d9488]">
                            {{ visit.patient?.full_name?.charAt(0) || '?' }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <Link
                                    v-if="visit.patient"
                                    :href="`/secretary/pediatric/patients/${visit.patient.id}`"
                                    class="font-bold text-gray-900 text-sm hover:text-[#4CAF50] transition-colors"
                                >
                                    {{ visit.patient.full_name }}
                                </Link>
                                <span v-if="visit.patient?.date_of_birth" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ calculateAge(visit.patient.date_of_birth) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="text-xs text-gray-500">{{ formatDate(visit.visit_date || visit.created_at) }}</span>
                                <span v-if="visit.visit_time || visit.created_at" class="text-xs text-gray-400">{{ visit.visit_time || formatTime(visit.created_at) }}</span>
                                <span v-if="visit.patient?.file_number" class="text-xs font-mono font-semibold text-[#0d9488]">{{ visit.patient.file_number }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status & Info -->
                    <div class="flex items-center gap-4 sm:gap-6">
                        <div v-if="visit.doctor" class="text-center hidden sm:block">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</p>
                            <p class="text-xs font-medium text-gray-700 mt-0.5">{{ visit.doctor.name || visit.doctor.name_en || '-' }}</p>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border"
                            :class="visitStatusColors[visit.status] || 'bg-gray-50 text-gray-500 border-gray-200'"
                        >
                            {{ visitStatusLabels[visit.status] || visit.status }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <Link
                            :href="`/secretary/pediatric/visits/${visit.id}`"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#0d9488] bg-[#0d9488]/5 hover:bg-[#0d9488]/10 rounded-xl transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            {{ isRtl ? 'عرض' : 'View' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!visits.data || visits.data.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد زيارات' : 'No visits found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'جرب تغيير البحث أو الفلاتر' : 'Try adjusting your search or filters' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="visits.links && visits.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
            <p class="text-xs text-gray-500">
                {{ isRtl ? 'عرض' : 'Showing' }} <span class="font-semibold">{{ visits.from }}</span> {{ isRtl ? 'إلى' : 'to' }} <span class="font-semibold">{{ visits.to }}</span> {{ isRtl ? 'من' : 'of' }} <span class="font-semibold">{{ visits.total }}</span> {{ isRtl ? 'نتيجة' : 'results' }}
            </p>
            <nav class="flex items-center gap-1">
                <template v-for="link in visits.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#4CAF50] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </nav>
        </div>
    </div>
</template>
