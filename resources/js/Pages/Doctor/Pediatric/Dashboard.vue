<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { ref, computed, onMounted } from 'vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    todayVisits: Array,
    stats: Object,
    overdueVaccinations: Array,
    growthAlerts: Array,
    upcomingVaccinations: Array,
    visitTrend: Array,
    ageDistribution: Object,
});

// Animation state
const mounted = ref(false);
const animatedValues = ref({
    totalPatients: 0,
    todayTotal: 0,
    waitingCount: 0,
    monthlyVisits: 0,
    completedToday: 0,
});

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    animateCounters();
});

function animateCounters() {
    const targets = {
        totalPatients: props.stats?.totalPatients || 0,
        todayTotal: props.stats?.todayTotal || 0,
        waitingCount: props.stats?.waitingCount || 0,
        monthlyVisits: props.stats?.monthlyVisits || 0,
        completedToday: props.stats?.completedToday || 0,
    };

    const duration = 1200;
    const start = performance.now();

    function step(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic

        animatedValues.value.totalPatients = Math.round(targets.totalPatients * eased);
        animatedValues.value.todayTotal = Math.round(targets.todayTotal * eased);
        animatedValues.value.waitingCount = Math.round(targets.waitingCount * eased);
        animatedValues.value.monthlyVisits = Math.round(targets.monthlyVisits * eased);
        animatedValues.value.completedToday = Math.round(targets.completedToday * eased);

        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

const maxTrend = computed(() => Math.max(...(props.visitTrend?.map(d => d.value) || [1]), 1));

const greeting = computed(() => {
    const h = new Date().getHours();
    if (isRtl.value) {
        if (h < 12) return 'صباح الخير';
        if (h < 17) return 'مساء الخير';
        return 'مساء الخير';
    }
    if (h < 12) return 'Good Morning';
    if (h < 17) return 'Good Afternoon';
    return 'Good Evening';
});

const currentDate = computed(() => {
    const locale = isRtl.value ? 'ar-EG' : 'en-GB';
    return new Date().toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
});

const ageGroups = computed(() => {
    const dist = props.ageDistribution || {};
    return [
        { key: 'newborn', label: isRtl.value ? 'حديث الولادة' : 'Newborn', count: dist.newborn || 0, color: 'bg-pink-100 text-pink-700 border-pink-200' },
        { key: 'infant', label: isRtl.value ? 'رضيع' : 'Infant', count: dist.infant || 0, color: 'bg-rose-100 text-rose-700 border-rose-200' },
        { key: 'toddler', label: isRtl.value ? 'طفل صغير' : 'Toddler', count: dist.toddler || 0, color: 'bg-orange-100 text-orange-700 border-orange-200' },
        { key: 'preschool', label: isRtl.value ? 'ما قبل المدرسة' : 'Preschool', count: dist.preschool || 0, color: 'bg-amber-100 text-amber-700 border-amber-200' },
        { key: 'school', label: isRtl.value ? 'سن المدرسة' : 'School Age', count: dist.school || 0, color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
        { key: 'adolescent', label: isRtl.value ? 'مراهق' : 'Adolescent', count: dist.adolescent || 0, color: 'bg-blue-100 text-blue-700 border-blue-200' },
    ];
});

function getVisitStatusBadge(status) {
    const map = {
        waiting: { label: isRtl.value ? 'انتظار' : 'Waiting', cls: 'bg-amber-50 text-amber-700 border-amber-200' },
        in_progress: { label: isRtl.value ? 'جاري' : 'In Progress', cls: 'bg-blue-50 text-blue-700 border-blue-200' },
        completed: { label: isRtl.value ? 'مكتمل' : 'Completed', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', cls: 'bg-gray-50 text-gray-500 border-gray-200' },
        no_show: { label: isRtl.value ? 'لم يحضر' : 'No Show', cls: 'bg-red-50 text-red-600 border-red-200' },
    };
    return map[status] || { label: status, cls: 'bg-gray-50 text-gray-600 border-gray-200' };
}

function formatTime(dateStr) {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    const locale = isRtl.value ? 'ar-EG' : 'en-GB';
    return d.toLocaleDateString(locale, { day: 'numeric', month: 'short' });
}

function getChildAge(dob) {
    if (!dob) return '--';
    const birth = new Date(dob);
    const now = new Date();
    const months = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth());
    if (months < 1) {
        const days = Math.floor((now - birth) / (1000 * 60 * 60 * 24));
        return isRtl.value ? `${days} يوم` : `${days}d`;
    }
    if (months < 24) return isRtl.value ? `${months} شهر` : `${months}m`;
    const years = Math.floor(months / 12);
    const rem = months % 12;
    if (rem === 0) return isRtl.value ? `${years} سنة` : `${years}y`;
    return isRtl.value ? `${years} سنة ${rem} شهر` : `${years}y ${rem}m`;
}
</script>

<template>
    <div class="px-4 sm:px-6 pb-10">

        <!-- ============================================ -->
        <!-- HERO SECTION                                 -->
        <!-- ============================================ -->
        <div
            class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-green-500 p-5 sm:p-7 shadow-xl"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-white/10 blur-3xl -translate-y-1/2 translate-x-1/4"></div>
            <div class="absolute bottom-0 left-1/3 w-64 h-64 rounded-full bg-emerald-300/10 blur-3xl translate-y-1/2"></div>

            <!-- Baby icon watermark -->
            <div class="absolute top-4 right-6 opacity-[0.07]">
                <svg class="w-44 h-44 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
            </div>

            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-emerald-100 text-xs font-semibold tracking-widest uppercase mb-1">{{ currentDate }}</p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">
                        {{ greeting }}, <span class="text-emerald-100">{{ isRtl ? 'د.' : 'Dr.' }} {{ $page.props.auth?.doctor?.name_en || (isRtl ? 'الطبيب' : 'Doctor') }}</span>
                    </h1>
                    <p class="text-sm text-emerald-100/80 mt-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        {{ isRtl ? 'لوحة تحكم طب الأطفال' : 'Pediatric Care Dashboard' }}
                    </p>
                </div>
                <div class="hidden sm:flex items-center gap-2">
                    <Link href="/doctor/pediatric/patients" class="inline-flex items-center gap-2 px-4 py-2 bg-white/15 backdrop-blur-sm text-white text-xs font-semibold rounded-xl hover:bg-white/25 transition-all border border-white/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ isRtl ? 'المرضى' : 'Patients' }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- STAT CARDS (4 cards)                         -->
        <!-- ============================================ -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Patients -->
            <div
                class="bg-white rounded-xl p-4 sm:p-5 shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 relative overflow-hidden"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 100ms"
            >
                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500 rounded-l-xl"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'إجمالي المرضى' : 'Total Patients' }}</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 tabular-nums">{{ animatedValues.totalPatients }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'أطفال مسجلين' : 'registered children' }}</p>
            </div>

            <!-- Today's Visits -->
            <div
                class="bg-white rounded-xl p-4 sm:p-5 shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 relative overflow-hidden"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 200ms"
            >
                <div class="absolute top-0 left-0 w-1 h-full bg-blue-500 rounded-l-xl"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'زيارات اليوم' : "Today's Visits" }}</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 tabular-nums">{{ animatedValues.todayTotal }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    <span class="text-emerald-500 font-medium">{{ animatedValues.completedToday }}</span> {{ isRtl ? 'مكتمل' : 'completed' }}
                </p>
            </div>

            <!-- Waiting -->
            <div
                class="bg-white rounded-xl p-4 sm:p-5 shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 relative overflow-hidden"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 300ms"
            >
                <div class="absolute top-0 left-0 w-1 h-full bg-amber-500 rounded-l-xl"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'في الانتظار' : 'Waiting' }}</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 tabular-nums">{{ animatedValues.waitingCount }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'في الطابور الآن' : 'in queue now' }}</p>
            </div>

            <!-- Monthly Visits -->
            <div
                class="bg-white rounded-xl p-4 sm:p-5 shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 relative overflow-hidden"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 400ms"
            >
                <div class="absolute top-0 left-0 w-1 h-full bg-violet-500 rounded-l-xl"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'هذا الشهر' : 'This Month' }}</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 tabular-nums">{{ animatedValues.monthlyVisits }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'زيارة شهرية' : 'monthly visits' }}</p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- VISIT TREND + AGE DISTRIBUTION               -->
        <!-- ============================================ -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">

            <!-- 7-Day Visit Trend -->
            <div
                class="bg-white rounded-xl p-5 sm:p-6 shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-lg transition-all duration-300"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 500ms"
            >
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'اتجاه الزيارات (7 أيام)' : 'Visit Trend (7 Days)' }}</h3>
                    <div class="flex items-center gap-1.5 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        <span class="text-xs font-semibold">{{ isRtl ? 'آخر أسبوع' : 'Last 7 days' }}</span>
                    </div>
                </div>
                <div class="flex items-end gap-2 h-36">
                    <div
                        v-for="(day, i) in (visitTrend || [])"
                        :key="i"
                        class="flex-1 flex flex-col items-center gap-1.5"
                    >
                        <span class="text-[10px] font-bold text-gray-600 tabular-nums">{{ day.value }}</span>
                        <div
                            class="w-full rounded-t-lg transition-all duration-700 ease-out"
                            :class="i === (visitTrend || []).length - 1 ? 'bg-emerald-500' : 'bg-emerald-200'"
                            :style="{
                                height: mounted ? `${Math.max((day.value / maxTrend) * 100, 6)}%` : '0%',
                                transitionDelay: `${600 + i * 80}ms`,
                            }"
                        ></div>
                        <span class="text-[9px] text-gray-400 font-medium">{{ day.date?.slice(-2) || '' }}</span>
                    </div>
                </div>
                <div v-if="!visitTrend || visitTrend.length === 0" class="flex items-center justify-center h-36 text-gray-300 text-sm">
                    {{ isRtl ? 'لا توجد بيانات' : 'No data available' }}
                </div>
            </div>

            <!-- Age Distribution -->
            <div
                class="bg-white rounded-xl p-5 sm:p-6 shadow-sm border border-gray-100 hover:-translate-y-1 hover:shadow-lg transition-all duration-300"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 600ms"
            >
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'التوزيع العمري' : 'Age Distribution' }}</h3>
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="flex flex-wrap gap-2.5">
                    <div
                        v-for="(group, idx) in ageGroups"
                        :key="group.key"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-sm font-semibold transition-all duration-500"
                        :class="[
                            group.color,
                            mounted ? 'translate-y-0 opacity-100 scale-100' : 'translate-y-3 opacity-0 scale-95',
                        ]"
                        :style="{ transitionDelay: `${700 + idx * 80}ms` }"
                    >
                        <span>{{ group.label }}</span>
                        <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 rounded-lg bg-white/60 text-xs font-bold">{{ group.count }}</span>
                    </div>
                </div>
                <div v-if="!ageDistribution" class="flex items-center justify-center h-24 text-gray-300 text-sm mt-4">
                    {{ isRtl ? 'لا توجد بيانات' : 'No data available' }}
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- TODAY'S QUEUE                                -->
        <!-- ============================================ -->
        <div
            class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden hover:-translate-y-1 hover:shadow-lg transition-all duration-300"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 700ms"
        >
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'طابور اليوم' : "Today's Queue" }}</h3>
                        <p class="text-xs text-gray-400">{{ (todayVisits || []).length }} {{ isRtl ? 'زيارة' : 'visits' }}</p>
                    </div>
                </div>
                <Link href="/doctor/queue" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                    {{ isRtl ? 'عرض الكل' : 'View All' }}
                </Link>
            </div>

            <div v-if="todayVisits && todayVisits.length > 0" class="divide-y divide-gray-50">
                <div
                    v-for="(visit, i) in todayVisits.slice(0, 8)"
                    :key="visit.id || i"
                    class="px-5 sm:px-6 py-3.5 flex items-center justify-between hover:bg-gray-50/50 transition-colors duration-200"
                    :class="mounted ? 'translate-x-0 opacity-100' : (isRtl ? '-translate-x-4' : 'translate-x-4') + ' opacity-0'"
                    :style="{ transition: 'all 0.4s ease-out', transitionDelay: `${800 + i * 60}ms` }"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-emerald-700">{{ (visit.patient_name || visit.child_name || '?')[0] }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ visit.patient_name || visit.child_name || '--' }}</p>
                            <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5">
                                <span v-if="visit.dob || visit.age">{{ visit.age || getChildAge(visit.dob) }}</span>
                                <span v-if="visit.guardian_name" class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ visit.guardian_name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-xs text-gray-400 hidden sm:inline tabular-nums">{{ formatTime(visit.time || visit.created_at) }}</span>
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border"
                            :class="getVisitStatusBadge(visit.status).cls"
                        >
                            {{ getVisitStatusBadge(visit.status).label }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-else class="px-6 py-12 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm text-gray-400 font-medium">{{ isRtl ? 'لا توجد زيارات اليوم' : 'No visits scheduled today' }}</p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- BOTTOM ROW: Vaccinations + Growth Alerts     -->
        <!-- ============================================ -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Overdue Vaccinations -->
            <div
                class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden hover:-translate-y-1 hover:shadow-lg transition-all duration-300"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 900ms"
            >
                <div class="px-5 py-4 border-b border-red-50 bg-gradient-to-r from-red-50 to-orange-50">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-red-800">{{ isRtl ? 'تطعيمات متأخرة' : 'Overdue Vaccinations' }}</h3>
                            <p class="text-[10px] text-red-500 font-medium uppercase tracking-wider">{{ (overdueVaccinations || []).length }} {{ isRtl ? 'حالة' : 'cases' }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="overdueVaccinations && overdueVaccinations.length > 0" class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
                    <div
                        v-for="(item, i) in overdueVaccinations.slice(0, 6)"
                        :key="item.id || i"
                        class="px-5 py-3 hover:bg-red-50/30 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ item.patient_name || item.child_name || '--' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ item.vaccine_name || item.vaccination || '--' }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold text-red-700 bg-red-100">
                                    {{ isRtl ? 'متأخر' : 'OVERDUE' }}
                                </span>
                                <p class="text-[10px] text-gray-400 mt-0.5 text-right tabular-nums">{{ formatDate(item.due_date) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <svg class="w-10 h-10 text-emerald-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'لا توجد تطعيمات متأخرة' : 'No overdue vaccinations' }}</p>
                </div>
            </div>

            <!-- Upcoming Vaccinations -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:-translate-y-1 hover:shadow-lg transition-all duration-300"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 1000ms"
            >
                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تطعيمات قادمة' : 'Upcoming Vaccinations' }}</h3>
                            <p class="text-[10px] text-emerald-600 font-medium uppercase tracking-wider">{{ isRtl ? 'الأيام السبعة القادمة' : 'Next 7 days' }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="upcomingVaccinations && upcomingVaccinations.length > 0" class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
                    <div
                        v-for="(item, i) in upcomingVaccinations.slice(0, 6)"
                        :key="item.id || i"
                        class="px-5 py-3 hover:bg-emerald-50/30 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ item.patient_name || item.child_name || '--' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ item.vaccine_name || item.vaccination || '--' }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold text-emerald-700 bg-emerald-100">
                                    {{ isRtl ? 'قادم' : 'DUE' }}
                                </span>
                                <p class="text-[10px] text-gray-400 mt-0.5 tabular-nums">{{ formatDate(item.due_date) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'لا توجد تطعيمات قادمة' : 'No upcoming vaccinations' }}</p>
                </div>
            </div>

            <!-- Growth Alerts -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:-translate-y-1 hover:shadow-lg transition-all duration-300 md:col-span-2 lg:col-span-1"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 1100ms"
            >
                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تنبيهات النمو' : 'Growth Alerts' }}</h3>
                            <p class="text-[10px] text-purple-600 font-medium uppercase tracking-wider">{{ (growthAlerts || []).length }} {{ isRtl ? 'تنبيه' : 'alerts' }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="growthAlerts && growthAlerts.length > 0" class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
                    <div
                        v-for="(alert, i) in growthAlerts.slice(0, 6)"
                        :key="alert.id || i"
                        class="px-5 py-3 hover:bg-purple-50/30 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ alert.patient_name || alert.child_name || '--' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ alert.age || getChildAge(alert.dob) }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold"
                                    :class="alert.severity === 'high' || alert.type === 'critical'
                                        ? 'text-red-700 bg-red-100'
                                        : 'text-amber-700 bg-amber-100'"
                                >
                                    {{ alert.metric || alert.type || (isRtl ? 'غير طبيعي' : 'Abnormal') }}
                                </span>
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    {{ alert.percentile ? (isRtl ? `المئوي ${alert.percentile}%` : `${alert.percentile}th %ile`) : alert.detail || '' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-10 text-center">
                    <svg class="w-10 h-10 text-emerald-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'لا توجد تنبيهات نمو' : 'All growth metrics normal' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
