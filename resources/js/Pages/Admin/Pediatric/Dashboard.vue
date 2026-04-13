<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const props = defineProps({
    stats: Object,
    ageDistribution: Object,
    genderDistribution: Object,
    monthlyTrend: Array,
    topVaccines: Array,
    growthAlerts: Object,
    milestoneStats: Object,
    allergyTypes: Array,
    chronicStats: Array,
    screeningStats: Object,
    topDoctors: Array,
    recentPatients: Array,
});

/* ── Animated counters ─────────────────────────────────── */
const animatedStats = ref({
    totalPatients: 0,
    totalVisits: 0,
    thisMonthVisits: 0,
    vaccineCoverage: 0,
    overdueVaccinations: 0,
    growthAlerts: 0,
});

function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

function animateCounter(key, target, duration = 1200) {
    const start = performance.now();
    function tick(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        animatedStats.value[key] = Math.round(target * easeOutCubic(progress));
        if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

onMounted(() => {
    setTimeout(() => {
        animateCounter('totalPatients', props.stats?.totalPatients ?? 0);
        animateCounter('totalVisits', props.stats?.totalVisits ?? 0, 1400);
        animateCounter('thisMonthVisits', props.stats?.thisMonthVisits ?? 0, 1600);
        animateCounter('vaccineCoverage', props.stats?.vaccineCoverage ?? 0, 1800);
        animateCounter('overdueVaccinations', props.stats?.overdueVaccinations ?? 0, 1500);
        const alertTotal = (props.growthAlerts?.underweight ?? 0) + (props.growthAlerts?.overweight ?? 0) + (props.growthAlerts?.stunted ?? 0);
        animateCounter('growthAlerts', alertTotal, 1600);
    }, 200);
});

/* ── Stat cards ────────────────────────────────────────── */
const statCards = computed(() => [
    {
        key: 'totalPatients',
        labelEn: 'Total Patients', labelAr: 'إجمالي المرضى',
        value: animatedStats.value.totalPatients,
        gradient: 'from-emerald-500 to-green-600',
        lightBg: 'bg-emerald-50', iconColor: 'text-emerald-500',
        icon: 'users',
    },
    {
        key: 'totalVisits',
        labelEn: 'Total Visits', labelAr: 'إجمالي الزيارات',
        value: animatedStats.value.totalVisits,
        gradient: 'from-green-500 to-teal-600',
        lightBg: 'bg-green-50', iconColor: 'text-green-500',
        icon: 'clipboard',
    },
    {
        key: 'thisMonthVisits',
        labelEn: 'This Month', labelAr: 'هذا الشهر',
        value: animatedStats.value.thisMonthVisits,
        gradient: 'from-teal-500 to-cyan-600',
        lightBg: 'bg-teal-50', iconColor: 'text-teal-500',
        icon: 'calendar',
        subtitle: props.stats?.lastMonthVisits ? (isRtl.value ? `الشهر الماضي: ${props.stats.lastMonthVisits}` : `Last month: ${props.stats.lastMonthVisits}`) : null,
    },
    {
        key: 'vaccineCoverage',
        labelEn: 'Vaccine Coverage', labelAr: 'تغطية التطعيم',
        value: animatedStats.value.vaccineCoverage + '%',
        gradient: 'from-blue-500 to-indigo-600',
        lightBg: 'bg-blue-50', iconColor: 'text-blue-500',
        icon: 'shield',
    },
    {
        key: 'overdueVaccinations',
        labelEn: 'Overdue Vaccines', labelAr: 'تطعيمات متأخرة',
        value: animatedStats.value.overdueVaccinations,
        gradient: 'from-red-500 to-rose-600',
        lightBg: 'bg-red-50', iconColor: 'text-red-500',
        icon: 'alert',
        isAlert: (props.stats?.overdueVaccinations ?? 0) > 0,
    },
    {
        key: 'growthAlerts',
        labelEn: 'Growth Alerts', labelAr: 'تنبيهات النمو',
        value: animatedStats.value.growthAlerts,
        gradient: 'from-amber-500 to-orange-600',
        lightBg: 'bg-amber-50', iconColor: 'text-amber-500',
        icon: 'chart',
    },
]);

/* ── Chart helpers ─────────────────────────────────────── */
const maxMonthlyCount = computed(() => {
    if (!props.monthlyTrend?.length) return 1;
    return Math.max(...props.monthlyTrend.map(m => m.count), 1);
});

const ageLabels = {
    newborn: { en: 'Newborn (0-28d)', ar: 'حديث الولادة' },
    infant: { en: 'Infant (1-12m)', ar: 'رضيع' },
    toddler: { en: 'Toddler (1-3y)', ar: 'طفل صغير' },
    preschool: { en: 'Preschool (3-5y)', ar: 'ما قبل المدرسة' },
    school: { en: 'School (6-12y)', ar: 'سن المدرسة' },
    adolescent: { en: 'Adolescent (13-18y)', ar: 'مراهق' },
};

const ageColors = {
    newborn: 'bg-pink-400',
    infant: 'bg-rose-400',
    toddler: 'bg-amber-400',
    preschool: 'bg-green-400',
    school: 'bg-blue-400',
    adolescent: 'bg-violet-400',
};

const maxAgeCount = computed(() => {
    if (!props.ageDistribution) return 1;
    return Math.max(...Object.values(props.ageDistribution), 1);
});

const totalGender = computed(() => {
    return (props.genderDistribution?.male ?? 0) + (props.genderDistribution?.female ?? 0) || 1;
});

const malePercent = computed(() => Math.round(((props.genderDistribution?.male ?? 0) / totalGender.value) * 100));
const femalePercent = computed(() => 100 - malePercent.value);

/* ── Milestone helpers ─────────────────────────────────── */
const milestoneTotal = computed(() => props.milestoneStats?.total || 1);
const milestoneAchievedPct = computed(() => Math.round(((props.milestoneStats?.achieved ?? 0) / milestoneTotal.value) * 100));
const milestoneEmergingPct = computed(() => Math.round(((props.milestoneStats?.emerging ?? 0) / milestoneTotal.value) * 100));
const milestoneNotPct = computed(() => 100 - milestoneAchievedPct.value - milestoneEmergingPct.value);

/* ── Chronic stats max ─────────────────────────────────── */
const maxChronicCount = computed(() => {
    if (!props.chronicStats?.length) return 1;
    return Math.max(...props.chronicStats.map(c => c.count), 1);
});

/* ── Allergy color map ─────────────────────────────────── */
const allergyColors = {
    food: 'bg-orange-100 text-orange-700 border-orange-200',
    drug: 'bg-red-100 text-red-700 border-red-200',
    environmental: 'bg-green-100 text-green-700 border-green-200',
    contact: 'bg-blue-100 text-blue-700 border-blue-200',
    insect: 'bg-amber-100 text-amber-700 border-amber-200',
    other: 'bg-gray-100 text-gray-700 border-gray-200',
};

function getAllergyColor(type) {
    return allergyColors[type?.toLowerCase()] || allergyColors.other;
}

/* ── Helpers ────────────────────────────────────────────── */
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function calcAge(dob) {
    if (!dob) return '-';
    const birth = new Date(dob);
    const now = new Date();
    const months = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth());
    if (months < 1) return isRtl.value ? 'حديث الولادة' : 'Newborn';
    if (months < 12) return `${months} ${isRtl.value ? 'شهر' : 'mo'}`;
    const years = Math.floor(months / 12);
    const rem = months % 12;
    return rem > 0 ? `${years}${isRtl.value ? 'س' : 'y'} ${rem}${isRtl.value ? 'ش' : 'm'}` : `${years} ${isRtl.value ? 'سنة' : 'y'}`;
}

/* ── Card visibility (staggered) ──────────────────────── */
const visibleSections = ref(new Set());

onMounted(() => {
    const sections = ['stats', 'monthly', 'age', 'gender', 'vaccines', 'growth', 'milestones', 'allergies', 'chronic', 'doctors', 'recent'];
    sections.forEach((s, i) => {
        setTimeout(() => { visibleSections.value.add(s); }, 100 + i * 120);
    });
});
</script>

<template>
    <div class="space-y-8 pb-12">
        <!-- ── Hero Header ───────────────────────────────────── -->
        <div class="ped-hero relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-green-600 to-green-500 p-8 md:p-10">
            <div class="absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-green-400/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-emerald-300/15 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 ltr:right-1/4 rtl:left-1/4 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>

            <!-- Decorative baby icon -->
            <div class="absolute ltr:right-8 rtl:left-8 top-8 opacity-10">
                <svg class="w-32 h-32 text-white ped-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="ped-hero-up">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" /></svg>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white">
                                    {{ isRtl ? 'إحصائيات طب الأطفال' : 'Pediatrics Analytics' }}
                                </h1>
                                <p class="text-green-100/80 text-sm mt-0.5">
                                    {{ isRtl ? 'نظرة شاملة على بيانات طب الأطفال' : 'Comprehensive overview of pediatric data' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 ped-hero-up" style="animation-delay: 0.15s">
                        <Link
                            href="/admin/pediatric/patients"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-green-700 bg-white/90 hover:bg-white shadow-lg hover:shadow-xl transition-all duration-300 backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            {{ isRtl ? 'المرضى' : 'Patients' }}
                        </Link>
                        <Link
                            href="/admin/pediatric/vaccinations"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/15 hover:bg-white/25 ring-1 ring-white/30 hover:ring-white/50 shadow-lg transition-all duration-300 backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
                            {{ isRtl ? 'التطعيمات' : 'Vaccinations' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── KPI Stat Cards ────────────────────────────────── -->
        <transition name="ped-fade">
            <div v-if="visibleSections.has('stats')" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5">
                <div
                    v-for="(card, index) in statCards"
                    :key="card.key"
                    class="ped-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden"
                    :style="{ animationDelay: `${index * 0.1}s` }"
                >
                    <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80`"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[13px] font-medium text-gray-500">{{ isRtl ? card.labelAr : card.labelEn }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2 tabular-nums">{{ card.value }}</p>
                            <p v-if="card.subtitle" class="text-xs text-gray-400 mt-1">{{ card.subtitle }}</p>
                        </div>
                        <div :class="`w-10 h-10 rounded-xl ${card.lightBg} flex items-center justify-center`">
                            <!-- Users -->
                            <svg v-if="card.icon === 'users'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            <!-- Clipboard -->
                            <svg v-else-if="card.icon === 'clipboard'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                            <!-- Calendar -->
                            <svg v-else-if="card.icon === 'calendar'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                            <!-- Shield -->
                            <svg v-else-if="card.icon === 'shield'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                            <!-- Alert -->
                            <svg v-else-if="card.icon === 'alert'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            <!-- Chart -->
                            <svg v-else-if="card.icon === 'chart'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </div>
                    </div>
                    <!-- Pulse for overdue -->
                    <span v-if="card.isAlert" class="absolute top-3 ltr:right-3 rtl:left-3 w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                </div>
            </div>
        </transition>

        <!-- ── Monthly Visits Trend ──────────────────────────── -->
        <transition name="ped-fade">
            <div v-if="visibleSections.has('monthly')" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-6">
                    {{ isRtl ? 'اتجاه الزيارات الشهرية' : 'Monthly Visits Trend' }}
                </h2>
                <div class="flex items-end gap-2 h-48">
                    <div
                        v-for="(month, i) in (monthlyTrend || [])"
                        :key="i"
                        class="flex-1 flex flex-col items-center gap-1"
                    >
                        <span class="text-xs font-semibold text-gray-600 tabular-nums">{{ month.count }}</span>
                        <div
                            class="w-full rounded-t-lg bg-gradient-to-t from-emerald-500 to-green-400 transition-all duration-700 ease-out"
                            :style="{ height: `${Math.max((month.count / maxMonthlyCount) * 100, 4)}%`, animationDelay: `${i * 0.05}s` }"
                        ></div>
                        <span class="text-[11px] text-gray-400 font-medium">{{ month.monthShort }}</span>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ── Age + Gender Row ──────────────────────────────── -->
        <transition name="ped-fade">
            <div v-if="visibleSections.has('age')" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Age Distribution -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ isRtl ? 'التوزيع العمري' : 'Age Distribution' }}
                    </h2>
                    <div class="space-y-3">
                        <div v-for="(key, idx) in Object.keys(ageLabels)" :key="key" class="flex items-center gap-3">
                            <span class="text-sm text-gray-600 w-36 shrink-0 truncate">{{ isRtl ? ageLabels[key].ar : ageLabels[key].en }}</span>
                            <div class="flex-1 h-7 bg-gray-100 rounded-full overflow-hidden relative">
                                <div
                                    :class="`h-full rounded-full ${ageColors[key]} transition-all duration-700 ease-out`"
                                    :style="{ width: `${Math.max(((ageDistribution?.[key] ?? 0) / maxAgeCount) * 100, 2)}%` }"
                                ></div>
                            </div>
                            <span class="text-sm font-bold text-gray-700 tabular-nums w-10 text-center">{{ ageDistribution?.[key] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Gender Distribution -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ isRtl ? 'توزيع الجنس' : 'Gender Distribution' }}
                    </h2>
                    <div class="space-y-6">
                        <!-- Male -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                    <span class="text-sm font-medium text-gray-700">{{ isRtl ? 'ذكور' : 'Male' }}</span>
                                </div>
                                <span class="text-sm font-bold text-blue-600">{{ malePercent }}%</span>
                            </div>
                            <div class="h-4 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-blue-400 to-blue-500 transition-all duration-700" :style="{ width: malePercent + '%' }"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ genderDistribution?.male ?? 0 }} {{ isRtl ? 'مريض' : 'patients' }}</p>
                        </div>
                        <!-- Female -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-pink-500"></div>
                                    <span class="text-sm font-medium text-gray-700">{{ isRtl ? 'إناث' : 'Female' }}</span>
                                </div>
                                <span class="text-sm font-bold text-pink-600">{{ femalePercent }}%</span>
                            </div>
                            <div class="h-4 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-pink-400 to-pink-500 transition-all duration-700" :style="{ width: femalePercent + '%' }"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ genderDistribution?.female ?? 0 }} {{ isRtl ? 'مريضة' : 'patients' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ── Vaccine Coverage + Top Vaccines ───────────────── -->
        <transition name="ped-fade">
            <div v-if="visibleSections.has('vaccines')" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Vaccine Coverage -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ isRtl ? 'تغطية التطعيم' : 'Vaccine Coverage' }}
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="relative w-28 h-28 shrink-0">
                                <svg class="w-28 h-28 -rotate-90" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#E5E7EB" stroke-width="10" />
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#4CAF50" stroke-width="10"
                                        stroke-linecap="round"
                                        :stroke-dasharray="`${(stats?.vaccineCoverage ?? 0) * 2.64} 264`"
                                        class="transition-all duration-1000"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xl font-bold text-gray-900">{{ stats?.vaccineCoverage ?? 0 }}%</span>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                    <span class="text-gray-600">{{ isRtl ? 'تم التطعيم' : 'Given' }}:</span>
                                    <span class="font-bold text-gray-900">{{ stats?.givenVaccinations ?? 0 }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
                                    <span class="text-gray-600">{{ isRtl ? 'الإجمالي' : 'Total' }}:</span>
                                    <span class="font-bold text-gray-900">{{ stats?.totalVaccinations ?? 0 }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                                    <span class="text-gray-600">{{ isRtl ? 'متأخرة' : 'Overdue' }}:</span>
                                    <span class="font-bold text-red-600">{{ stats?.overdueVaccinations ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Vaccines -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ isRtl ? 'أكثر التطعيمات' : 'Top Vaccines' }}
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-start pb-3 font-semibold text-gray-500">{{ isRtl ? 'التطعيم' : 'Vaccine' }}</th>
                                    <th class="text-center pb-3 font-semibold text-gray-500">{{ isRtl ? 'تم' : 'Given' }}</th>
                                    <th class="text-center pb-3 font-semibold text-gray-500">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                                    <th class="text-center pb-3 font-semibold text-gray-500">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="v in (topVaccines || [])" :key="v.vaccine_name" class="border-b border-gray-50 hover:bg-gray-50/50 transition">
                                    <td class="py-2.5 font-medium text-gray-800">{{ v.vaccine_name }}</td>
                                    <td class="py-2.5 text-center text-green-600 font-semibold">{{ v.given_count }}</td>
                                    <td class="py-2.5 text-center text-gray-500">{{ v.total }}</td>
                                    <td class="py-2.5 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                            :class="v.total > 0 && (v.given_count / v.total) >= 0.8 ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'">
                                            {{ v.total > 0 ? Math.round((v.given_count / v.total) * 100) : 0 }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ── Growth Alerts ─────────────────────────────────── -->
        <transition name="ped-fade">
            <div v-if="visibleSections.has('growth')" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-white rounded-2xl shadow-sm border border-red-100 p-6 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5" /></svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ isRtl ? 'نقص الوزن' : 'Underweight' }}</p>
                            <p class="text-2xl font-bold text-red-600">{{ growthAlerts?.underweight ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-6 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ isRtl ? 'زيادة الوزن' : 'Overweight' }}</p>
                            <p class="text-2xl font-bold text-amber-600">{{ growthAlerts?.overweight ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-red-100 p-6 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12" /></svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">{{ isRtl ? 'تأخر النمو' : 'Stunted' }}</p>
                            <p class="text-2xl font-bold text-red-600">{{ growthAlerts?.stunted ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ── Milestones + Allergies Row ────────────────────── -->
        <transition name="ped-fade">
            <div v-if="visibleSections.has('milestones')" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Milestone Progress -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ isRtl ? 'مراحل النمو' : 'Milestone Progress' }}
                    </h2>
                    <!-- Stacked bar -->
                    <div class="h-8 rounded-full overflow-hidden flex bg-gray-100 mb-4">
                        <div class="bg-emerald-500 transition-all duration-700" :style="{ width: milestoneAchievedPct + '%' }"></div>
                        <div class="bg-amber-400 transition-all duration-700" :style="{ width: milestoneEmergingPct + '%' }"></div>
                        <div class="bg-gray-300 transition-all duration-700" :style="{ width: milestoneNotPct + '%' }"></div>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="text-gray-600">{{ isRtl ? 'مكتمل' : 'Achieved' }}</span>
                            <span class="font-bold text-gray-900">{{ milestoneStats?.achieved ?? 0 }} ({{ milestoneAchievedPct }}%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                            <span class="text-gray-600">{{ isRtl ? 'ناشئ' : 'Emerging' }}</span>
                            <span class="font-bold text-gray-900">{{ milestoneStats?.emerging ?? 0 }} ({{ milestoneEmergingPct }}%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-gray-300"></span>
                            <span class="text-gray-600">{{ isRtl ? 'لم يتحقق' : 'Not Achieved' }}</span>
                            <span class="font-bold text-gray-900">{{ milestoneStats?.not_achieved ?? 0 }} ({{ milestoneNotPct }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- Allergy Types -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ isRtl ? 'أنواع الحساسية' : 'Allergy Types' }}
                    </h2>
                    <div class="flex flex-wrap gap-3" v-if="allergyTypes?.length">
                        <div
                            v-for="a in allergyTypes"
                            :key="a.allergy_type"
                            :class="`inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-semibold transition-all hover:shadow-sm ${getAllergyColor(a.allergy_type)}`"
                        >
                            <span class="capitalize">{{ a.allergy_type }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-white/60 text-xs font-bold">{{ a.count }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data available' }}</p>
                </div>
            </div>
        </transition>

        <!-- ── Chronic Conditions + Top Doctors ──────────────── -->
        <transition name="ped-fade">
            <div v-if="visibleSections.has('chronic')" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Chronic Conditions -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ isRtl ? 'الحالات المزمنة' : 'Chronic Conditions' }}
                    </h2>
                    <div class="space-y-3" v-if="chronicStats?.length">
                        <div v-for="c in chronicStats" :key="c.condition_type" class="flex items-center gap-3">
                            <span class="text-sm text-gray-600 w-32 shrink-0 truncate capitalize">{{ c.condition_type }}</span>
                            <div class="flex-1 h-6 bg-gray-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-violet-400 to-purple-500 transition-all duration-700"
                                    :style="{ width: `${Math.max((c.count / maxChronicCount) * 100, 4)}%` }"
                                ></div>
                            </div>
                            <span class="text-sm font-bold text-gray-700 tabular-nums w-8 text-center">{{ c.count }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data available' }}</p>
                </div>

                <!-- Top Doctors -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">
                        {{ isRtl ? 'أفضل الأطباء' : 'Top Doctors' }}
                    </h2>
                    <div class="space-y-3" v-if="topDoctors?.length">
                        <div
                            v-for="(doc, i) in topDoctors"
                            :key="doc.doctor_id"
                            class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition"
                        >
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                                :class="i === 0 ? 'bg-amber-100 text-amber-700' : i === 1 ? 'bg-gray-100 text-gray-600' : i === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-50 text-gray-500'">
                                {{ i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">
                                    {{ isRtl ? doc.doctor?.name_ar : doc.doctor?.name_en }}
                                </p>
                            </div>
                            <span class="text-sm font-bold text-green-600 tabular-nums">{{ doc.visit_count }} {{ isRtl ? 'زيارة' : 'visits' }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات' : 'No data available' }}</p>
                </div>
            </div>
        </transition>

        <!-- ── Recent Registrations ──────────────────────────── -->
        <transition name="ped-fade">
            <div v-if="visibleSections.has('recent')" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ isRtl ? 'التسجيلات الأخيرة' : 'Recent Registrations' }}
                    </h2>
                    <Link href="/admin/pediatric/patients" class="text-sm font-semibold text-green-600 hover:text-green-700 transition">
                        {{ isRtl ? 'عرض الكل' : 'View All' }} &rarr;
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-start pb-3 font-semibold text-gray-500">{{ isRtl ? 'الاسم' : 'Name' }}</th>
                                <th class="text-start pb-3 font-semibold text-gray-500 hidden sm:table-cell">{{ isRtl ? 'العمر' : 'Age' }}</th>
                                <th class="text-center pb-3 font-semibold text-gray-500 hidden md:table-cell">{{ isRtl ? 'الجنس' : 'Gender' }}</th>
                                <th class="text-start pb-3 font-semibold text-gray-500 hidden lg:table-cell">{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                                <th class="text-start pb-3 font-semibold text-gray-500 hidden md:table-cell">{{ isRtl ? 'ولي الأمر' : 'Guardian' }}</th>
                                <th class="text-start pb-3 font-semibold text-gray-500">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in (recentPatients || [])" :key="p.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition">
                                <td class="py-3">
                                    <Link :href="`/secretary/pediatric/patients/${p.id}`" class="font-medium text-gray-800 hover:text-green-600 transition">
                                        {{ p.full_name }}
                                    </Link>
                                </td>
                                <td class="py-3 text-gray-500 hidden sm:table-cell">{{ calcAge(p.date_of_birth) }}</td>
                                <td class="py-3 text-center hidden md:table-cell">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                        :class="p.gender === 'male' ? 'bg-blue-50 text-blue-600' : 'bg-pink-50 text-pink-600'"
                                    >
                                        {{ p.gender === 'male' ? (isRtl ? 'ذكر' : 'Male') : (isRtl ? 'أنثى' : 'Female') }}
                                    </span>
                                </td>
                                <td class="py-3 text-gray-500 hidden lg:table-cell font-mono text-xs">{{ p.file_number || '-' }}</td>
                                <td class="py-3 text-gray-500 hidden md:table-cell">{{ p.guardian_name || '-' }}</td>
                                <td class="py-3 text-gray-400 text-xs">{{ formatDate(p.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* ── Hero animations ──────────────────────────────────── */
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

/* ── Card enter ───────────────────────────────────────── */
.ped-card-enter {
    animation: pedCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedCardEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Section fade ─────────────────────────────────────── */
.ped-fade-enter-active { transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
.ped-fade-enter-from   { opacity: 0; transform: translateY(12px); }
.ped-fade-enter-to     { opacity: 1; transform: translateY(0); }
</style>
