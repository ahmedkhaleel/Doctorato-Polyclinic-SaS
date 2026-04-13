<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: Object,
    filters: Object,
});

const mounted = ref(false);
const statsVisible = ref(false);
const contentVisible = ref(false);
const search = ref(props.filters?.search || '');
let debounce = null;

// View mode with localStorage persistence
const viewMode = ref(localStorage.getItem('doctor_patients_viewMode') || 'grid');
function setViewMode(mode) {
    viewMode.value = mode;
    localStorage.setItem('doctor_patients_viewMode', mode);
}

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    setTimeout(() => { statsVisible.value = true; }, 200);
    setTimeout(() => { contentVisible.value = true; }, 350);

    // Keyboard shortcut: / to focus search
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

function handleKeydown(e) {
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
        e.preventDefault();
        document.getElementById('patient-search')?.focus();
    }
}

watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get('/doctor/patients', { search: val || undefined }, { preserveState: true, replace: true });
    }, 300);
});

// Stats computed from data
const totalPatients = computed(() => props.patients.total || 0);

const newThisMonth = computed(() => {
    if (!props.patients.data) return 0;
    const now = new Date();
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    return props.patients.data.filter(p => {
        if (!p.created_at) return false;
        return new Date(p.created_at) >= startOfMonth;
    }).length;
});

const recentVisitors = computed(() => {
    if (!props.patients.data) return 0;
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    return props.patients.data.filter(p => {
        if (!p.last_visit_date) return false;
        return new Date(p.last_visit_date) >= sevenDaysAgo;
    }).length;
});

const resultCount = computed(() => props.patients.data?.length || 0);

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

const avatarColors = [
    'from-[#C4A265] to-[#A68B52]',
    'from-blue-400 to-blue-600',
    'from-emerald-400 to-emerald-600',
    'from-purple-400 to-purple-600',
    'from-rose-400 to-rose-600',
    'from-cyan-400 to-cyan-600',
    'from-amber-400 to-orange-600',
    'from-indigo-400 to-indigo-600',
];

function getAvatarColor(id) {
    return avatarColors[id % avatarColors.length];
}

function formatDate(dateStr) {
    if (!dateStr) return isRtl.value ? 'لا يوجد' : 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return isRtl.value ? 'اليوم' : 'Today';
    if (diffDays === 1) return isRtl.value ? 'أمس' : 'Yesterday';
    if (diffDays < 7) return isRtl.value ? `منذ ${diffDays} أيام` : `${diffDays}d ago`;
    if (diffDays < 30) {
        const weeks = Math.floor(diffDays / 7);
        return isRtl.value ? `منذ ${weeks} أسبوع` : `${weeks}w ago`;
    }
    const months = Math.floor(diffDays / 30);
    return isRtl.value ? `منذ ${months} شهر` : `${months}mo ago`;
}
</script>

<template>
    <div class="space-y-5">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-80 h-80 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-56 h-56 bg-purple-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-[#C4A265]/5 rounded-full blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-[#C4A265]/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase">{{ $t('a_patients') }}</p>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'مرضاي' : 'My Patients' }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'المرضى الذين عالجتهم' : 'Patients you have treated' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-5 py-3.5 border border-white/10 text-center min-w-[90px]">
                            <p class="text-2xl font-bold text-white leading-none">{{ totalPatients }}</p>
                            <p class="text-[10px] text-gray-400 mt-1.5 font-medium uppercase tracking-wide">{{ isRtl ? 'إجمالي' : 'Total' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Search in Hero -->
                <div class="relative max-w-xl"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                >
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        id="patient-search"
                        v-model="search"
                        type="text"
                        :placeholder="isRtl ? 'بحث بالاسم أو الهاتف أو رقم الملف... (اضغط /)' : 'Search by name, phone, or file number... (press /)'"
                        class="w-full pl-12 pr-20 py-3.5 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all"
                    />
                    <!-- Results count badge -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 scale-75"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-75"
                    >
                        <span v-if="search" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 bg-white/10 rounded-md px-2 py-1 font-medium tabular-nums">
                            {{ resultCount }} {{ isRtl ? 'نتيجة' : 'results' }}
                        </span>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- Stats Strip -->
        <div class="grid grid-cols-3 gap-3"
            :class="statsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <!-- Total Patients -->
            <div class="bg-white rounded-xl border border-gray-100/80 px-4 py-3.5 flex items-center gap-3 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#C4A265]/10 to-[#C4A265]/5 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-bold text-gray-800 leading-none tabular-nums">{{ totalPatients }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ isRtl ? 'إجمالي المرضى' : 'Total Patients' }}</p>
                </div>
            </div>
            <!-- New This Month -->
            <div class="bg-white rounded-xl border border-gray-100/80 px-4 py-3.5 flex items-center gap-3 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100/50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-bold text-gray-800 leading-none tabular-nums">{{ newThisMonth }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ isRtl ? 'جدد هذا الشهر' : 'New This Month' }}</p>
                </div>
            </div>
            <!-- Recent Visits -->
            <div class="bg-white rounded-xl border border-gray-100/80 px-4 py-3.5 flex items-center gap-3 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100/50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-bold text-gray-800 leading-none tabular-nums">{{ recentVisitors }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ isRtl ? 'زيارات حديثة' : 'Recent (7 days)' }}</p>
                </div>
            </div>
        </div>

        <!-- View Toggle + Content Area -->
        <div :class="contentVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <!-- Toolbar -->
            <div class="flex items-center justify-between mb-3 px-1">
                <p class="text-sm text-gray-500 font-medium">
                    <span v-if="search">
                        {{ isRtl ? `${resultCount} نتيجة بحث` : `${resultCount} result${resultCount !== 1 ? 's' : ''}` }}
                    </span>
                    <span v-else>
                        {{ isRtl ? `عرض ${resultCount} مريض` : `Showing ${resultCount} patient${resultCount !== 1 ? 's' : ''}` }}
                    </span>
                </p>
                <div class="flex items-center bg-gray-100 rounded-lg p-0.5">
                    <button @click="setViewMode('grid')"
                        class="flex items-center justify-center w-8 h-8 rounded-md transition-all duration-200"
                        :class="viewMode === 'grid' ? 'bg-white shadow-sm text-[#C4A265]' : 'text-gray-400 hover:text-gray-600'"
                        :title="isRtl ? 'عرض شبكي' : 'Grid view'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                    <button @click="setViewMode('list')"
                        class="flex items-center justify-center w-8 h-8 rounded-md transition-all duration-200"
                        :class="viewMode === 'list' ? 'bg-white shadow-sm text-[#C4A265]' : 'text-gray-400 hover:text-gray-600'"
                        :title="isRtl ? 'عرض قائمة' : 'List view'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Grid View -->
            <div v-if="patients.data?.length > 0 && viewMode === 'grid'"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <Link v-for="(patient, index) in patients.data" :key="patient.id"
                    :href="`/doctor/patients/${patient.id}`"
                    class="group bg-white rounded-xl border border-gray-100/80 shadow-sm hover:shadow-md hover:border-[#C4A265]/20 transition-all duration-300 overflow-hidden"
                    :class="contentVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    :style="{ transition: 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.05 + index * 0.04}s` }"
                >
                    <!-- Card top accent -->
                    <div class="h-1 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        :class="getAvatarColor(patient.id)"
                    ></div>

                    <div class="p-5">
                        <!-- Avatar + Name -->
                        <div class="flex items-start gap-3.5 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow-sm transition-transform duration-300 group-hover:scale-105"
                                :class="getAvatarColor(patient.id)"
                            >
                                {{ getInitials(patient.full_name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-gray-900 transition-colors">{{ patient.full_name }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span v-if="patient.file_number" class="inline-flex items-center font-mono text-[10px] text-[#C4A265] bg-[#C4A265]/5 px-1.5 py-0.5 rounded border border-[#C4A265]/10">
                                        {{ patient.file_number }}
                                    </span>
                                    <span v-if="patient.module" class="inline-flex items-center text-[10px] font-medium px-1.5 py-0.5 rounded"
                                        :class="patient.module === 'dental' ? 'text-blue-600 bg-blue-50' : 'text-purple-600 bg-purple-50'"
                                    >
                                        {{ patient.module === 'dental' ? (isRtl ? 'أسنان' : 'Dental') : (isRtl ? 'جلدية' : 'Derma') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="space-y-2.5">
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span v-if="patient.phone" class="truncate" dir="ltr">{{ patient.phone }}</span>
                                <span v-else class="text-gray-300 italic">{{ isRtl ? 'لا يوجد هاتف' : 'No phone' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span v-if="patient.last_visit_date">{{ timeAgo(patient.last_visit_date) }}</span>
                                <span v-else class="text-gray-300 italic">{{ isRtl ? 'لا توجد زيارات' : 'No visits' }}</span>
                            </div>
                        </div>

                        <!-- Footer: Visits count + Arrow -->
                        <div class="flex items-center justify-between mt-4 pt-3.5 border-t border-gray-50">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-[#C4A265]">
                                <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-2 rounded-full bg-[#C4A265]/10 text-[11px] font-bold tabular-nums">
                                    {{ patient.visits_count }}
                                </span>
                                {{ isRtl ? 'زيارة' : 'visits' }}
                            </span>
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-[#C4A265] transition-all duration-200 group-hover:translate-x-0.5" :class="{ 'group-hover:-translate-x-0.5 group-hover:translate-x-0': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- List View -->
            <div v-if="patients.data?.length > 0 && viewMode === 'list'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            >
                <div class="divide-y divide-gray-50">
                    <Link v-for="(patient, index) in patients.data" :key="patient.id"
                        :href="`/doctor/patients/${patient.id}`"
                        class="group flex items-center gap-4 px-5 py-4 hover:bg-gray-50/80 transition-all duration-200"
                        :class="contentVisible ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                        :style="{ transition: 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.05 + index * 0.03}s` }"
                    >
                        <!-- Avatar -->
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-sm font-bold flex-shrink-0 transition-transform duration-200 group-hover:scale-105 shadow-sm"
                            :class="getAvatarColor(patient.id)"
                        >
                            {{ getInitials(patient.full_name) }}
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-gray-900 transition-colors">{{ patient.full_name }}</p>
                                <span v-if="patient.file_number" class="flex-shrink-0 font-mono text-[10px] text-[#C4A265] bg-[#C4A265]/5 px-1.5 py-0.5 rounded border border-[#C4A265]/10">{{ patient.file_number }}</span>
                                <span v-if="patient.module" class="flex-shrink-0 text-[10px] font-medium px-1.5 py-0.5 rounded"
                                    :class="patient.module === 'dental' ? 'text-blue-600 bg-blue-50' : 'text-purple-600 bg-purple-50'"
                                >
                                    {{ patient.module === 'dental' ? (isRtl ? 'أسنان' : 'Dental') : (isRtl ? 'جلدية' : 'Derma') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-1">
                                <span v-if="patient.phone" class="text-xs text-gray-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    <span dir="ltr">{{ patient.phone }}</span>
                                </span>
                                <span v-else class="text-xs text-gray-300">{{ isRtl ? 'لا يوجد هاتف' : 'No phone' }}</span>
                                <span v-if="patient.last_visit_date" class="text-xs text-gray-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ timeAgo(patient.last_visit_date) }}
                                </span>
                            </div>
                        </div>

                        <!-- Visit Count + Arrow -->
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="text-center">
                                <span class="inline-flex items-center justify-center min-w-[32px] h-7 px-2.5 rounded-full bg-[#C4A265]/10 text-[#C4A265] text-xs font-bold tabular-nums">
                                    {{ patient.visits_count }}
                                </span>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ isRtl ? 'زيارات' : 'visits' }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-[#C4A265] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!patients.data?.length"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            >
                <div class="py-24 text-center">
                    <!-- Animated icon -->
                    <div class="relative w-24 h-24 mx-auto mb-6">
                        <div class="absolute inset-0 rounded-2xl bg-gray-50 border border-gray-100 animate-pulse"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <!-- Floating dots decoration -->
                        <div class="absolute -top-2 -right-2 w-4 h-4 rounded-full bg-[#C4A265]/10 animate-bounce" style="animation-delay: 0s; animation-duration: 2s"></div>
                        <div class="absolute -bottom-1 -left-3 w-3 h-3 rounded-full bg-blue-100 animate-bounce" style="animation-delay: 0.5s; animation-duration: 2.5s"></div>
                        <div class="absolute top-1/2 -right-4 w-2 h-2 rounded-full bg-purple-100 animate-bounce" style="animation-delay: 1s; animation-duration: 3s"></div>
                    </div>

                    <h3 class="text-base font-semibold text-gray-600 mb-1.5">
                        {{ search ? (isRtl ? 'لا توجد نتائج' : 'No results found') : (isRtl ? 'لا يوجد مرضى' : 'No patients yet') }}
                    </h3>
                    <p v-if="search" class="text-sm text-gray-400 max-w-xs mx-auto">
                        {{ isRtl ? 'جرب مصطلح بحث مختلف أو تحقق من الإملاء' : 'Try a different search term or check spelling' }}
                    </p>
                    <p v-else class="text-sm text-gray-400 max-w-xs mx-auto">
                        {{ isRtl ? 'سيظهر المرضى هنا بعد أول زيارة لهم معك' : 'Patients will appear here after their first visit with you' }}
                    </p>
                    <button v-if="search" @click="search = ''"
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-[#C4A265] bg-[#C4A265]/5 rounded-lg hover:bg-[#C4A265]/10 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ isRtl ? 'مسح البحث' : 'Clear search' }}
                    </button>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="patients.links?.length > 3"
                class="flex items-center justify-center gap-1 mt-4 px-2"
            >
                <template v-for="link in patients.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3.5 py-2 rounded-lg text-xs font-medium transition-all duration-200"
                        :class="link.active ? 'bg-[#C4A265] text-white shadow-sm shadow-[#C4A265]/20' : 'text-gray-500 bg-white border border-gray-100 hover:bg-gray-50 hover:border-gray-200'"
                        v-html="link.label" preserve-state
                    />
                    <span v-else class="px-3.5 py-2 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
