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
const heroVisible = ref(false);
const cardsVisible = ref(false);
const search = ref(props.filters?.search || '');
let debounceTimer = null;

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    setTimeout(() => { heroVisible.value = true; }, 150);
    setTimeout(() => { cardsVisible.value = true; }, 350);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    if (debounceTimer) clearTimeout(debounceTimer);
});

function handleKeydown(e) {
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
        e.preventDefault();
        document.getElementById('pediatric-search')?.focus();
    }
}

watch(search, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const params = {};
        if (val) params.search = val;
        router.get('/doctor/pediatric/patients', params, { preserveState: true, replace: true });
    }, 300);
});

function calculateAge(dob) {
    if (!dob) return { text: '', textAr: '' };
    const birth = new Date(dob);
    const now = new Date();
    let years = now.getFullYear() - birth.getFullYear();
    let months = now.getMonth() - birth.getMonth();
    if (months < 0) { years--; months += 12; }
    if (now.getDate() < birth.getDate()) {
        months--;
        if (months < 0) { years--; months += 12; }
    }
    const totalMonths = years * 12 + months;
    if (totalMonths < 24) {
        return {
            text: `${totalMonths} month${totalMonths !== 1 ? 's' : ''}`,
            textAr: `${totalMonths} شهر`,
        };
    }
    return {
        text: `${years}y ${months}m`,
        textAr: `${years} سنة ${months} شهر`,
    };
}

function getInitialColor(gender) {
    if (gender === 'male') return 'from-slate-400 to-[#1B365D]';
    if (gender === 'female') return 'from-amber-400 to-[#C4A265]';
    return 'from-emerald-400 to-emerald-600';
}

function getInitialBg(gender) {
    if (gender === 'male') return 'ring-slate-200';
    if (gender === 'female') return 'ring-amber-200';
    return 'ring-emerald-200';
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-500 transition-all duration-700"
            :class="heroVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <!-- Decorative blobs -->
            <div class="absolute top-0 right-0 w-72 h-72 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/4"></div>
            <div class="absolute top-1/2 left-1/3 w-24 h-24 bg-white/5 rounded-full"></div>

            <div class="relative z-10 px-4 sm:px-6 py-5 sm:py-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-white">
                                {{ isRtl ? 'مرضى الأطفال' : 'Pediatric Patients' }}
                            </h1>
                            <p class="text-emerald-100 text-sm mt-0.5">
                                {{ isRtl ? 'إدارة وتتبع ملفات المرضى' : 'Manage and track patient files' }}
                            </p>
                        </div>
                    </div>
                    <div v-if="patients?.total !== undefined" class="bg-white/15 backdrop-blur-sm rounded-full px-4 py-1.5 text-white text-sm font-semibold">
                        {{ patients.total }} {{ isRtl ? 'مريض' : 'patients' }}
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="mt-5 relative max-w-lg">
                    <div class="absolute inset-y-0 flex items-center pointer-events-none" :class="isRtl ? 'right-0 pr-3.5' : 'left-0 pl-3.5'">
                        <svg class="w-4.5 h-4.5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        id="pediatric-search"
                        v-model="search"
                        type="text"
                        :placeholder="isRtl ? 'البحث بالاسم أو رقم الملف...' : 'Search by name or file number...'"
                        class="doctorato-input w-full bg-white/15 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-white/50 text-sm py-2.5 focus:outline-none focus:ring-2 focus:ring-white/30 transition"
                        :class="isRtl ? 'pr-10 pl-4' : 'pl-10 pr-4'"
                    />
                    <div v-if="search" class="absolute inset-y-0 flex items-center" :class="isRtl ? 'left-0 pl-3' : 'right-0 pr-3'">
                        <button @click="search = ''" class="text-white/60 hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient Cards Grid -->
        <div v-if="patients?.data?.length" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 px-0">
            <Link
                v-for="(patient, idx) in patients.data"
                :key="patient.id"
                :href="`/doctor/pediatric/patients/${patient.id}`"
                class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 sm:p-5 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 cursor-pointer"
                :class="cardsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                :style="{ transitionDelay: `${idx * 60}ms` }"
            >
                <div class="flex items-start gap-3.5">
                    <!-- Avatar -->
                    <div v-if="patient.photo" class="w-12 h-12 rounded-xl overflow-hidden ring-2 ring-gray-100 dark:ring-gray-700 flex-shrink-0">
                        <img :src="patient.photo" :alt="patient.full_name" class="w-full h-full object-cover" />
                    </div>
                    <div
                        v-else
                        class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-lg font-bold flex-shrink-0 ring-2"
                        :class="[getInitialColor(patient.gender), getInitialBg(patient.gender)]"
                    >
                        {{ (patient.full_name || patient.name || 'P').charAt(0).toUpperCase() }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-emerald-600 transition-colors">
                            {{ patient.full_name || patient.name }}
                        </h3>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span v-if="patient.file_number" class="text-xs font-mono text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-400 px-1.5 py-0.5 rounded">
                                #{{ patient.file_number }}
                            </span>
                            <span v-if="patient.date_of_birth" class="text-xs text-gray-500 dark:text-gray-400">
                                {{ isRtl ? calculateAge(patient.date_of_birth).textAr : calculateAge(patient.date_of_birth).text }}
                            </span>
                        </div>
                    </div>

                    <!-- Gender Icon -->
                    <div class="flex-shrink-0">
                        <span v-if="patient.gender === 'male'" class="text-slate-400" title="Male">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a1 1 0 011 1v3.586l2.293-2.293a1 1 0 111.414 1.414L14.414 8H18a1 1 0 110 2h-5a1 1 0 01-1-1V4a1 1 0 011-1zM8 13a4 4 0 100 8 4 4 0 000-8zm-6 4a6 6 0 1112 0 6 6 0 01-12 0z"/></svg>
                        </span>
                        <span v-else-if="patient.gender === 'female'" class="text-amber-400" title="Female">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a6 6 0 016 6 6 6 0 01-5 5.917V16h2a1 1 0 110 2h-2v2a1 1 0 11-2 0v-2H9a1 1 0 110-2h2v-2.083A6.002 6.002 0 0112 2zm0 2a4 4 0 100 8 4 4 0 000-8z"/></svg>
                        </span>
                    </div>
                </div>

                <!-- Details Row -->
                <div class="mt-3.5 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span v-if="patient.guardian_name" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            {{ patient.guardian_name }}
                        </span>
                    </div>
                    <div v-if="patient.visits_count !== undefined" class="flex items-center gap-1 text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-400 px-2 py-0.5 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        {{ patient.visits_count }} {{ isRtl ? 'زيارة' : 'visits' }}
                    </div>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <div
            v-else-if="mounted"
            class="text-center py-16 transition-all duration-500"
            :class="cardsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="w-20 h-20 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                {{ search ? (isRtl ? 'لا توجد نتائج' : 'No results found') : (isRtl ? 'لا يوجد مرضى' : 'No patients yet') }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ search ? (isRtl ? 'حاول تعديل كلمات البحث' : 'Try adjusting your search terms') : (isRtl ? 'سيظهر المرضى هنا بعد إضافتهم' : 'Patients will appear here once added') }}
            </p>
        </div>

        <!-- Pagination -->
        <nav v-if="patients?.links && patients.last_page > 1" class="flex items-center justify-center gap-1 pt-2">
            <template v-for="link in patients.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-1.5 text-sm rounded-lg transition-all duration-200"
                    :class="link.active
                        ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/25'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                    v-html="link.label"
                    preserve-state
                />
                <span
                    v-else
                    class="px-3 py-1.5 text-sm text-gray-300 dark:text-gray-600"
                    v-html="link.label"
                />
            </template>
        </nav>

        <!-- Page info -->
        <p v-if="patients?.last_page > 1" class="text-center text-xs text-gray-400 dark:text-gray-500">
            {{ isRtl ? `صفحة ${patients.current_page} من ${patients.last_page}` : `Page ${patients.current_page} of ${patients.last_page}` }}
        </p>
    </div>
</template>
