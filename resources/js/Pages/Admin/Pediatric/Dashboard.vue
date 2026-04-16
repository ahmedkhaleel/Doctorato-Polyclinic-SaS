<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
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
    revenueTrend: Array,
    revenueByDoctor: Array,
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

function easeOutExpo(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }

function animateCounter(key, target, duration = 1200) {
    if (!target) return;
    const start = performance.now();
    function tick(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        animatedStats.value[key] = Math.round(target * easeOutExpo(progress));
        if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

/* ── Scroll-triggered section reveal ──────────────────── */
const visibleSections = ref(new Set());
const sectionRefs = ref({});
let observer = null;

function setSectionRef(key) {
    return (el) => { if (el) sectionRefs.value[key] = el; };
}

onMounted(() => {
    // Start counter animations
    setTimeout(() => {
        animateCounter('totalPatients', props.stats?.totalPatients ?? 0, 1000);
        animateCounter('totalVisits', props.stats?.totalVisits ?? 0, 1200);
        animateCounter('thisMonthVisits', props.stats?.thisMonthVisits ?? 0, 1400);
        animateCounter('vaccineCoverage', props.stats?.vaccineCoverage ?? 0, 1600);
        animateCounter('overdueVaccinations', props.stats?.overdueVaccinations ?? 0, 1300);
        const alertTotal = (props.growthAlerts?.underweight ?? 0) + (props.growthAlerts?.overweight ?? 0) + (props.growthAlerts?.stunted ?? 0);
        animateCounter('growthAlerts', alertTotal, 1400);
    }, 300);

    // IntersectionObserver for scroll-triggered reveals
    nextTick(() => {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        visibleSections.value.add(entry.target.dataset.section);
                        observer?.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
        );
        Object.values(sectionRefs.value).forEach(el => {
            if (el) observer.observe(el);
        });
    });
});

onUnmounted(() => { observer?.disconnect(); });

/* ── Stat cards ────────────────────────────────────────── */
const statCards = computed(() => [
    {
        key: 'totalPatients',
        labelEn: 'Total Patients', labelAr: 'إجمالي المرضى',
        value: animatedStats.value.totalPatients,
        gradient: 'from-emerald-500 to-green-600',
        lightBg: 'bg-emerald-50 dark:bg-emerald-900/20', iconColor: 'text-emerald-500',
        icon: 'users', link: '/admin/pediatric/patients',
    },
    {
        key: 'totalVisits',
        labelEn: 'Total Visits', labelAr: 'إجمالي الزيارات',
        value: animatedStats.value.totalVisits,
        gradient: 'from-green-500 to-teal-600',
        lightBg: 'bg-green-50', iconColor: 'text-green-500',
        icon: 'clipboard', link: '/admin/pediatric/visits',
    },
    {
        key: 'thisMonthVisits',
        labelEn: 'This Month', labelAr: 'هذا الشهر',
        value: animatedStats.value.thisMonthVisits,
        gradient: 'from-teal-500 to-cyan-600',
        lightBg: 'bg-teal-50', iconColor: 'text-teal-500',
        icon: 'calendar',
        subtitle: props.stats?.lastMonthVisits != null ? (isRtl.value ? `الشهر الماضي: ${props.stats.lastMonthVisits}` : `Last month: ${props.stats.lastMonthVisits}`) : null,
    },
    {
        key: 'vaccineCoverage',
        labelEn: 'Vaccine Coverage', labelAr: 'تغطية التطعيم',
        value: animatedStats.value.vaccineCoverage + '%',
        gradient: 'from-blue-500 to-indigo-600',
        lightBg: 'bg-blue-50', iconColor: 'text-blue-500',
        icon: 'shield', link: '/admin/pediatric/vaccinations',
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
        icon: 'chart', link: '/admin/pediatric/growth',
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
    newborn: { bar: 'from-pink-400 to-pink-500', bg: 'bg-pink-100', text: 'text-pink-700' },
    infant: { bar: 'from-rose-400 to-rose-500', bg: 'bg-rose-100', text: 'text-rose-700' },
    toddler: { bar: 'from-amber-400 to-amber-500', bg: 'bg-amber-100', text: 'text-amber-700' },
    preschool: { bar: 'from-green-400 to-green-500', bg: 'bg-green-100', text: 'text-green-700' },
    school: { bar: 'from-blue-400 to-blue-500', bg: 'bg-blue-100', text: 'text-blue-700' },
    adolescent: { bar: 'from-violet-400 to-violet-500', bg: 'bg-violet-100', text: 'text-violet-700' },
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

/* ── Screening stats ──────────────────────────────────── */
const screeningEntries = computed(() => {
    if (!props.screeningStats) return [];
    return Object.entries(props.screeningStats).map(([type, results]) => {
        const total = results.reduce((s, r) => s + r.count, 0);
        const normal = results.find(r => r.result === 'normal')?.count ?? 0;
        const abnormal = results.find(r => r.result === 'abnormal')?.count ?? 0;
        const pending = results.find(r => r.result === 'pending')?.count ?? 0;
        return { type, total, normal, abnormal, pending };
    });
});

/* ── Allergy color map ─────────────────────────────────── */
const allergyColors = {
    food: 'bg-orange-50 text-orange-700 border-orange-200 ring-orange-100',
    drug: 'bg-red-50 text-red-700 border-red-200 ring-red-100',
    environmental: 'bg-green-50 text-green-700 border-green-200 ring-green-100',
    contact: 'bg-blue-50 text-blue-700 border-blue-200 ring-blue-100',
    insect: 'bg-amber-50 text-amber-700 border-amber-200 ring-amber-100',
    other: 'bg-gray-50 text-gray-700 border-gray-200 ring-gray-100',
};
function getAllergyColor(type) {
    return allergyColors[type?.toLowerCase()] || allergyColors.other;
}

const allergyIcons = { food: '🍎', drug: '💊', environmental: '🌿', contact: '🧤', insect: '🐝', other: '⚕️' };
function getAllergyIcon(type) { return allergyIcons[type?.toLowerCase()] || '⚕️'; }

/* ── Helpers ────────────────────────────────────────────── */
function formatDate(date) {
    if (!date) return '-';
    const loc = isRtl.value ? 'ar-SA' : 'en-GB';
    return new Date(date).toLocaleDateString(loc, { day: '2-digit', month: 'short', year: 'numeric' });
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

/* ── Hovered bar tooltip ─────────────────────────────── */
const hoveredBar = ref(null);
</script>

<template>
    <div class="space-y-8 pb-12">
        <!-- ── Hero Header ───────────────────────────────────── -->
        <div class="ped-hero relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-green-600 to-teal-500 p-8 md:p-10">
            <!-- Animated background shapes -->
            <div class="ped-shape-1 absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-green-400/20 rounded-full blur-3xl"></div>
            <div class="ped-shape-2 absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-emerald-300/15 rounded-full blur-3xl"></div>
            <div class="ped-shape-3 absolute top-1/2 ltr:right-1/4 rtl:left-1/4 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>

            <!-- Decorative floating elements -->
            <div class="absolute ltr:right-8 rtl:left-8 top-6 opacity-[0.08]">
                <svg class="w-36 h-36 text-white ped-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                </svg>
            </div>
            <div class="absolute ltr:right-48 rtl:left-48 bottom-8 opacity-[0.06]">
                <svg class="w-20 h-20 text-white ped-float-delayed" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="ped-hero-up">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" /></svg>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight">
                                    {{ isRtl ? 'إحصائيات طب الأطفال' : 'Pediatrics Analytics' }}
                                </h1>
                                <p class="text-green-100/70 text-sm mt-0.5">
                                    {{ isRtl ? 'نظرة شاملة على بيانات طب الأطفال' : 'Comprehensive overview of pediatric data' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5 ped-hero-up" style="animation-delay: 0.15s">
                        <Link href="/admin/pediatric/patients" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-green-700 bg-white/90 hover:bg-white shadow-lg hover:shadow-xl transition-all duration-300 backdrop-blur-sm hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            {{ isRtl ? 'المرضى' : 'Patients' }}
                        </Link>
                        <Link href="/admin/pediatric/vaccinations" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/10 hover:bg-white/20 ring-1 ring-white/20 hover:ring-white/40 shadow-lg transition-all duration-300 backdrop-blur-sm hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
                            {{ isRtl ? 'التطعيمات' : 'Vaccinations' }}
                        </Link>
                        <Link href="/admin/pediatric/growth" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/10 hover:bg-white/20 ring-1 ring-white/20 hover:ring-white/40 shadow-lg transition-all duration-300 backdrop-blur-sm hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                            {{ isRtl ? 'النمو' : 'Growth' }}
                        </Link>
                        <Link href="/admin/pediatric/visits" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/10 hover:bg-white/20 ring-1 ring-white/20 hover:ring-white/40 shadow-lg transition-all duration-300 backdrop-blur-sm hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                            {{ isRtl ? 'الزيارات' : 'Visits' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── KPI Stat Cards ────────────────────────────────── -->
        <div :ref="setSectionRef('stats')" data-section="stats"
            class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4"
            :class="{ 'ped-section-visible': visibleSections.has('stats') }">
            <component
                v-for="(card, index) in statCards"
                :key="card.key"
                :is="card.link ? Link : 'div'"
                :href="card.link || undefined"
                class="ped-card-enter group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-xl border border-gray-100/80 hover:border-gray-200 transition-all duration-500 overflow-hidden cursor-pointer"
                :style="{ animationDelay: `${index * 0.08}s` }"
            >
                <!-- Gradient top accent -->
                <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80 group-hover:h-1.5 transition-all duration-300`"></div>
                <!-- Hover glow -->
                <div :class="`absolute inset-0 bg-gradient-to-br ${card.gradient} opacity-0 group-hover:opacity-[0.03] transition-opacity duration-500`"></div>

                <div class="relative flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="text-[13px] font-medium text-gray-500 truncate">{{ isRtl ? card.labelAr : card.labelEn }}</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1.5 tabular-nums">{{ card.value }}</p>
                        <p v-if="card.subtitle" class="text-[11px] text-gray-400 mt-1 truncate">{{ card.subtitle }}</p>
                    </div>
                    <div :class="`w-10 h-10 rounded-xl ${card.lightBg} flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform duration-300`">
                        <svg v-if="card.icon === 'users'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        <svg v-else-if="card.icon === 'clipboard'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                        <svg v-else-if="card.icon === 'calendar'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        <svg v-else-if="card.icon === 'shield'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        <svg v-else-if="card.icon === 'alert'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        <svg v-else-if="card.icon === 'chart'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    </div>
                </div>
                <!-- Pulse for overdue -->
                <span v-if="card.isAlert" class="absolute top-3 ltr:right-3 rtl:left-3 flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                </span>
            </component>
        </div>

        <!-- ── Monthly Visits Trend ──────────────────────────── -->
        <div :ref="setSectionRef('monthly')" data-section="monthly"
            class="ped-section bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6"
            :class="{ 'ped-section-visible': visibleSections.has('monthly') }">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-900">
                    {{ isRtl ? 'اتجاه الزيارات الشهرية' : 'Monthly Visits Trend' }}
                </h2>
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span class="w-3 h-3 rounded-sm bg-gradient-to-t from-emerald-500 to-green-400"></span>
                    {{ isRtl ? 'الزيارات' : 'Visits' }}
                </div>
            </div>
            <div v-if="monthlyTrend?.length" class="flex items-end gap-1.5 sm:gap-2 h-52 px-1">
                <div
                    v-for="(month, i) in monthlyTrend"
                    :key="i"
                    class="flex-1 flex flex-col items-center gap-1.5 group cursor-pointer"
                    @mouseenter="hoveredBar = i"
                    @mouseleave="hoveredBar = null"
                >
                    <!-- Tooltip -->
                    <div
                        class="relative mb-1 transition-all duration-200"
                        :class="hoveredBar === i ? 'opacity-100 -translate-y-1' : 'opacity-0'"
                    >
                        <div class="bg-gray-900 text-white text-[11px] font-semibold px-2 py-1 rounded-lg shadow-lg whitespace-nowrap">
                            {{ month.count }} {{ isRtl ? 'زيارة' : 'visits' }}
                        </div>
                        <div class="absolute left-1/2 -translate-x-1/2 -bottom-1 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                    <div
                        class="ped-bar w-full rounded-t-xl bg-gradient-to-t from-emerald-500 to-green-400 group-hover:from-emerald-400 group-hover:to-green-300 transition-all duration-500 relative"
                        :style="{ height: `${Math.max((month.count / maxMonthlyCount) * 100, 4)}%`, transitionDelay: `${i * 40}ms` }"
                    >
                        <div class="absolute inset-0 bg-white/0 group-hover:bg-white/10 rounded-t-xl transition-all duration-300"></div>
                    </div>
                    <span class="text-[11px] text-gray-400 font-medium group-hover:text-gray-600 transition-colors">{{ month.monthShort }}</span>
                </div>
            </div>
            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center h-52 text-gray-300">
                <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                <p class="text-sm font-medium">{{ isRtl ? 'لا توجد بيانات للاتجاه الشهري' : 'No monthly trend data yet' }}</p>
            </div>
        </div>

        <!-- ── Age + Gender Row ──────────────────────────────── -->
        <div :ref="setSectionRef('age')" data-section="age"
            class="ped-section grid grid-cols-1 lg:grid-cols-3 gap-6"
            :class="{ 'ped-section-visible': visibleSections.has('age') }">
            <!-- Age Distribution -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-5">
                    {{ isRtl ? 'التوزيع العمري' : 'Age Distribution' }}
                </h2>
                <div class="space-y-3">
                    <div v-for="key in Object.keys(ageLabels)" :key="key" class="group flex items-center gap-3">
                        <div class="flex items-center gap-2 w-40 shrink-0">
                            <span :class="`w-2 h-2 rounded-full bg-gradient-to-r ${ageColors[key].bar}`"></span>
                            <span class="text-sm text-gray-600 truncate">{{ isRtl ? ageLabels[key].ar : ageLabels[key].en }}</span>
                        </div>
                        <div class="flex-1 h-8 bg-gray-50 rounded-xl overflow-hidden relative">
                            <div
                                :class="`h-full rounded-xl bg-gradient-to-r ${ageColors[key].bar} transition-all duration-700 ease-out relative`"
                                :style="{ width: `${Math.max(((ageDistribution?.[key] ?? 0) / maxAgeCount) * 100, 2)}%` }"
                            >
                                <div class="absolute inset-0 bg-white/0 group-hover:bg-white/20 transition-all duration-300 rounded-xl"></div>
                            </div>
                        </div>
                        <span :class="`text-sm font-bold tabular-nums w-12 text-center rounded-lg px-2 py-1 transition-colors duration-300 ${ageColors[key].bg} ${ageColors[key].text}`">
                            {{ ageDistribution?.[key] ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Gender Distribution -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-5">
                    {{ isRtl ? 'توزيع الجنس' : 'Gender Distribution' }}
                </h2>
                <div class="space-y-6">
                    <!-- Donut Chart -->
                    <div class="flex justify-center">
                        <div class="relative w-36 h-36">
                            <svg class="w-36 h-36 -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="38" fill="none" stroke="#EFF6FF" stroke-width="14" />
                                <circle cx="50" cy="50" r="38" fill="none" stroke="#3B82F6" stroke-width="14"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${malePercent * 2.388} 238.8`"
                                    class="ped-donut-animate" />
                                <circle cx="50" cy="50" r="38" fill="none" stroke="#EC4899" stroke-width="14"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${femalePercent * 2.388} 238.8`"
                                    :stroke-dashoffset="`${-malePercent * 2.388}`"
                                    class="ped-donut-animate" style="animation-delay: 0.3s" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-gray-900">{{ totalGender }}</span>
                                <span class="text-[10px] text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المجموع' : 'Total' }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Legend -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 rounded-xl bg-blue-50/50 border border-blue-100">
                            <div class="flex items-center justify-center gap-1.5 mb-1">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                <span class="text-xs font-semibold text-blue-700">{{ isRtl ? 'ذكور' : 'Male' }}</span>
                            </div>
                            <p class="text-lg font-bold text-blue-600">{{ malePercent }}%</p>
                            <p class="text-[11px] text-gray-400">{{ genderDistribution?.male ?? 0 }}</p>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-pink-50/50 border border-pink-100">
                            <div class="flex items-center justify-center gap-1.5 mb-1">
                                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                                <span class="text-xs font-semibold text-pink-700">{{ isRtl ? 'إناث' : 'Female' }}</span>
                            </div>
                            <p class="text-lg font-bold text-pink-600">{{ femalePercent }}%</p>
                            <p class="text-[11px] text-gray-400">{{ genderDistribution?.female ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Vaccine Coverage + Top Vaccines ───────────────── -->
        <div :ref="setSectionRef('vaccines')" data-section="vaccines"
            class="ped-section grid grid-cols-1 lg:grid-cols-2 gap-6"
            :class="{ 'ped-section-visible': visibleSections.has('vaccines') }">
            <!-- Vaccine Coverage -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-5">
                    {{ isRtl ? 'تغطية التطعيم' : 'Vaccine Coverage' }}
                </h2>
                <div class="flex items-center gap-6">
                    <div class="relative w-32 h-32 shrink-0">
                        <svg class="w-32 h-32 -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#F3F4F6" stroke-width="10" />
                            <circle cx="50" cy="50" r="42" fill="none" stroke="url(#vaccGrad)" stroke-width="10"
                                stroke-linecap="round"
                                :stroke-dasharray="`${(stats?.vaccineCoverage ?? 0) * 2.64} 264`"
                                class="ped-donut-animate"
                            />
                            <defs>
                                <linearGradient id="vaccGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#4CAF50" />
                                    <stop offset="100%" style="stop-color:#2DD4BF" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-gray-900">{{ stats?.vaccineCoverage ?? 0 }}%</span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider">{{ isRtl ? 'التغطية' : 'Coverage' }}</span>
                        </div>
                    </div>
                    <div class="space-y-3 flex-1">
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-green-50/50 border border-green-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                <span class="text-sm text-gray-600">{{ isRtl ? 'تم التطعيم' : 'Given' }}</span>
                            </div>
                            <span class="text-sm font-bold text-green-700">{{ stats?.givenVaccinations ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 border border-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                                <span class="text-sm text-gray-600">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                            </div>
                            <span class="text-sm font-bold text-gray-700">{{ stats?.totalVaccinations ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-red-50/50 border border-red-100">
                            <div class="flex items-center gap-2">
                                <span class="flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-2.5 w-2.5 rounded-full bg-red-400 opacity-75" v-if="(stats?.overdueVaccinations ?? 0) > 0"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                                </span>
                                <span class="text-sm text-gray-600">{{ isRtl ? 'متأخرة' : 'Overdue' }}</span>
                            </div>
                            <span class="text-sm font-bold text-red-600">{{ stats?.overdueVaccinations ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Vaccines -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-5">
                    {{ isRtl ? 'أكثر التطعيمات' : 'Top Vaccines' }}
                </h2>
                <div v-if="topVaccines?.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-start pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider">{{ isRtl ? 'التطعيم' : 'Vaccine' }}</th>
                                <th class="text-center pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider">{{ isRtl ? 'تم' : 'Given' }}</th>
                                <th class="text-center pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider hidden sm:table-cell">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                                <th class="text-center pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider">{{ isRtl ? 'النسبة' : 'Rate' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(v, vi) in topVaccines" :key="v.vaccine_name"
                                class="border-b border-gray-50 hover:bg-green-50/30 transition-colors duration-200 group">
                                <td class="py-3 font-medium text-gray-800 group-hover:text-green-700 transition-colors">{{ v.vaccine_name }}</td>
                                <td class="py-3 text-center text-green-600 font-bold tabular-nums">{{ v.given_count }}</td>
                                <td class="py-3 text-center text-gray-500 tabular-nums hidden sm:table-cell">{{ v.total }}</td>
                                <td class="py-3 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <div class="w-16 h-1.5 rounded-full bg-gray-100 overflow-hidden hidden sm:block">
                                            <div class="h-full rounded-full bg-green-500 transition-all duration-500"
                                                :style="{ width: `${v.total > 0 ? (v.given_count / v.total) * 100 : 0}%` }"></div>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                            :class="v.total > 0 && (v.given_count / v.total) >= 0.8 ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'">
                                            {{ v.total > 0 ? Math.round((v.given_count / v.total) * 100) : 0 }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-10 text-gray-300">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3" /></svg>
                    <p class="text-sm font-medium">{{ isRtl ? 'لا توجد بيانات تطعيمات' : 'No vaccine data yet' }}</p>
                </div>
            </div>
        </div>

        <!-- ── Growth Alerts ─────────────────────────────────── -->
        <div :ref="setSectionRef('growth')" data-section="growth"
            class="ped-section grid grid-cols-1 md:grid-cols-3 gap-5"
            :class="{ 'ped-section-visible': visibleSections.has('growth') }">
            <div class="group bg-white rounded-2xl shadow-sm border border-red-100/80 p-6 hover:shadow-lg hover:border-red-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-400 to-red-500"></div>
                <div class="absolute -bottom-6 ltr:-right-6 rtl:-left-6 w-24 h-24 bg-red-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'نقص الوزن' : 'Underweight' }}</p>
                        <p class="text-3xl font-bold text-red-600 tabular-nums">{{ growthAlerts?.underweight ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="group bg-white rounded-2xl shadow-sm border border-amber-100/80 p-6 hover:shadow-lg hover:border-amber-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 to-amber-500"></div>
                <div class="absolute -bottom-6 ltr:-right-6 rtl:-left-6 w-24 h-24 bg-amber-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'زيادة الوزن' : 'Overweight' }}</p>
                        <p class="text-3xl font-bold text-amber-600 tabular-nums">{{ growthAlerts?.overweight ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="group bg-white rounded-2xl shadow-sm border border-orange-100/80 p-6 hover:shadow-lg hover:border-orange-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-400 to-red-500"></div>
                <div class="absolute -bottom-6 ltr:-right-6 rtl:-left-6 w-24 h-24 bg-orange-50 rounded-full opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'تأخر النمو' : 'Stunted Growth' }}</p>
                        <p class="text-3xl font-bold text-orange-600 tabular-nums">{{ growthAlerts?.stunted ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Milestones + Allergies Row ────────────────────── -->
        <div :ref="setSectionRef('milestones')" data-section="milestones"
            class="ped-section grid grid-cols-1 lg:grid-cols-2 gap-6"
            :class="{ 'ped-section-visible': visibleSections.has('milestones') }">
            <!-- Milestone Progress -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-5">
                    {{ isRtl ? 'مراحل النمو' : 'Milestone Progress' }}
                </h2>
                <div v-if="milestoneStats?.total" class="space-y-5">
                    <!-- Stacked bar -->
                    <div class="h-10 rounded-2xl overflow-hidden flex bg-gray-100 shadow-inner">
                        <div class="bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all duration-1000 ease-out relative group"
                            :style="{ width: milestoneAchievedPct + '%' }">
                            <span v-if="milestoneAchievedPct > 12" class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white">{{ milestoneAchievedPct }}%</span>
                        </div>
                        <div class="bg-gradient-to-r from-amber-300 to-amber-400 transition-all duration-1000 ease-out relative"
                            :style="{ width: milestoneEmergingPct + '%' }">
                            <span v-if="milestoneEmergingPct > 12" class="absolute inset-0 flex items-center justify-center text-xs font-bold text-amber-800">{{ milestoneEmergingPct }}%</span>
                        </div>
                        <div class="bg-gray-200 transition-all duration-1000 ease-out"
                            :style="{ width: milestoneNotPct + '%' }"></div>
                    </div>
                    <!-- Legend cards -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center p-3 rounded-xl bg-emerald-50/60 border border-emerald-100">
                            <p class="text-lg font-bold text-emerald-600">{{ milestoneStats?.achieved ?? 0 }}</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">{{ isRtl ? 'مكتمل' : 'Achieved' }}</p>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-amber-50/60 border border-amber-100">
                            <p class="text-lg font-bold text-amber-600">{{ milestoneStats?.emerging ?? 0 }}</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">{{ isRtl ? 'ناشئ' : 'Emerging' }}</p>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <p class="text-lg font-bold text-gray-500">{{ milestoneStats?.not_achieved ?? 0 }}</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">{{ isRtl ? 'لم يتحقق' : 'Not Yet' }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-8 text-gray-300">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                    <p class="text-sm font-medium">{{ isRtl ? 'لا توجد بيانات مراحل النمو' : 'No milestone data yet' }}</p>
                </div>
            </div>

            <!-- Allergy Types -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-5">
                    {{ isRtl ? 'أنواع الحساسية' : 'Allergy Types' }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" v-if="allergyTypes?.length">
                    <div
                        v-for="a in allergyTypes"
                        :key="a.allergy_type"
                        :class="`group flex flex-col items-center gap-2 p-4 rounded-2xl border ring-1 transition-all duration-300 hover:shadow-md hover:scale-[1.02] cursor-default ${getAllergyColor(a.allergy_type)}`"
                    >
                        <span class="text-2xl">{{ getAllergyIcon(a.allergy_type) }}</span>
                        <span class="text-sm font-semibold capitalize">{{ a.allergy_type }}</span>
                        <span class="text-xl font-bold">{{ a.count }}</span>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-8 text-gray-300">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" /></svg>
                    <p class="text-sm font-medium">{{ isRtl ? 'لا توجد بيانات حساسية' : 'No allergy data yet' }}</p>
                </div>
            </div>
        </div>

        <!-- ── Screening Stats (NEW) ────────────────────────── -->
        <div v-if="screeningEntries.length" :ref="setSectionRef('screening')" data-section="screening"
            class="ped-section bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6"
            :class="{ 'ped-section-visible': visibleSections.has('screening') }">
            <h2 class="text-lg font-bold text-gray-900 mb-5">
                {{ isRtl ? 'فحوصات الكشف المبكر' : 'Screening Tests' }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="s in screeningEntries" :key="s.type"
                    class="group p-4 rounded-2xl border border-gray-100 hover:border-gray-200 hover:shadow-md transition-all duration-300 bg-gradient-to-br from-white to-gray-50/50">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-semibold text-gray-800 capitalize">{{ s.type.replace(/_/g, ' ') }}</span>
                        <span class="text-xs font-bold text-gray-400 tabular-nums">{{ s.total }}</span>
                    </div>
                    <!-- Mini stacked bar -->
                    <div class="h-3 rounded-full overflow-hidden flex bg-gray-100 mb-3">
                        <div v-if="s.normal" class="bg-green-400 transition-all duration-700" :style="{ width: `${(s.normal / s.total) * 100}%` }"></div>
                        <div v-if="s.abnormal" class="bg-red-400 transition-all duration-700" :style="{ width: `${(s.abnormal / s.total) * 100}%` }"></div>
                        <div v-if="s.pending" class="bg-amber-300 transition-all duration-700" :style="{ width: `${(s.pending / s.total) * 100}%` }"></div>
                    </div>
                    <div class="flex items-center gap-3 text-[11px]">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400"></span> {{ s.normal }}</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-400"></span> {{ s.abnormal }}</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-300"></span> {{ s.pending }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Chronic Conditions + Top Doctors ──────────────── -->
        <div :ref="setSectionRef('chronic')" data-section="chronic"
            class="ped-section grid grid-cols-1 lg:grid-cols-2 gap-6"
            :class="{ 'ped-section-visible': visibleSections.has('chronic') }">
            <!-- Chronic Conditions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-5">
                    {{ isRtl ? 'الحالات المزمنة' : 'Chronic Conditions' }}
                </h2>
                <div class="space-y-3" v-if="chronicStats?.length">
                    <div v-for="(c, ci) in chronicStats" :key="c.condition_type"
                        class="group flex items-center gap-3 p-2 rounded-xl hover:bg-violet-50/30 transition-all duration-200">
                        <span class="text-sm text-gray-600 w-32 shrink-0 truncate capitalize font-medium">{{ c.condition_type }}</span>
                        <div class="flex-1 h-7 bg-gray-50 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-violet-400 to-purple-500 transition-all duration-700 ease-out group-hover:from-violet-300 group-hover:to-purple-400"
                                :style="{ width: `${Math.max((c.count / maxChronicCount) * 100, 4)}%` }"
                            ></div>
                        </div>
                        <span class="text-sm font-bold text-violet-700 tabular-nums w-10 text-center bg-violet-50 rounded-lg px-2 py-1">{{ c.count }}</span>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-8 text-gray-300">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    <p class="text-sm font-medium">{{ isRtl ? 'لا توجد حالات مزمنة' : 'No chronic conditions recorded' }}</p>
                </div>
            </div>

            <!-- Top Doctors -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-5">
                    {{ isRtl ? 'أفضل الأطباء' : 'Top Doctors' }}
                </h2>
                <div class="space-y-2" v-if="topDoctors?.length">
                    <div
                        v-for="(doc, i) in topDoctors"
                        :key="doc.doctor_id"
                        class="group flex items-center gap-3 p-3 rounded-2xl hover:bg-gradient-to-r hover:from-green-50/50 hover:to-transparent border border-transparent hover:border-green-100 transition-all duration-300"
                    >
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold shrink-0 shadow-sm transition-transform duration-300 group-hover:scale-110"
                            :class="i === 0 ? 'bg-gradient-to-br from-amber-100 to-amber-200 text-amber-700 ring-1 ring-amber-300' : i === 1 ? 'bg-gradient-to-br from-gray-100 to-gray-200 text-gray-600 ring-1 ring-gray-300' : i === 2 ? 'bg-gradient-to-br from-orange-100 to-orange-200 text-orange-700 ring-1 ring-orange-300' : 'bg-gray-50 text-gray-500 ring-1 ring-gray-200'">
                            {{ i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : i + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-green-700 transition-colors">
                                {{ isRtl ? doc.doctor?.name_ar : doc.doctor?.name_en }}
                            </p>
                        </div>
                        <div class="text-end shrink-0">
                            <span class="text-sm font-bold text-green-600 tabular-nums">{{ doc.visit_count }}</span>
                            <span class="text-[11px] text-gray-400 block">{{ isRtl ? 'زيارة' : 'visits' }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-8 text-gray-300">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    <p class="text-sm font-medium">{{ isRtl ? 'لا توجد بيانات أطباء' : 'No doctor data yet' }}</p>
                </div>
            </div>
        </div>

        <!-- ══════════ REVENUE ANALYTICS ══════════ -->
        <div :ref="setSectionRef('revenue')" data-section="revenue"
            class="ped-section bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6"
            :class="{ 'ped-section-visible': visibleSections.has('revenue') }">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                    {{ isRtl ? 'تحليل الإيرادات' : 'Revenue Analytics' }}
                </h2>
            </div>

            <!-- Revenue Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-green-50 p-4">
                    <p class="text-xs font-medium text-emerald-600">{{ isRtl ? 'إيرادات هذا الشهر' : 'Revenue This Month' }}</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-1 tabular-nums">{{ (stats?.revenueThisMonth ?? 0).toLocaleString() }} <span class="text-sm font-normal text-emerald-500">{{ isRtl ? 'د.ع' : 'IQD' }}</span></p>
                    <div v-if="stats?.revenueLastMonth" class="mt-1.5">
                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                            :class="stats.revenueThisMonth >= stats.revenueLastMonth ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'">
                            {{ stats.revenueThisMonth >= stats.revenueLastMonth ? '↑' : '↓' }}
                            {{ stats.revenueLastMonth > 0 ? Math.abs(Math.round(((stats.revenueThisMonth - stats.revenueLastMonth) / stats.revenueLastMonth) * 100)) : 0 }}%
                            {{ isRtl ? 'عن الشهر الماضي' : 'vs last month' }}
                        </span>
                    </div>
                </div>
                <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-4">
                    <p class="text-xs font-medium text-blue-600">{{ isRtl ? 'المحصّل هذا الشهر' : 'Collected This Month' }}</p>
                    <p class="text-2xl font-bold text-blue-700 mt-1 tabular-nums">{{ (stats?.collectedThisMonth ?? 0).toLocaleString() }} <span class="text-sm font-normal text-blue-500">{{ isRtl ? 'د.ع' : 'IQD' }}</span></p>
                    <div v-if="stats?.revenueThisMonth > 0" class="mt-1.5">
                        <div class="w-full bg-blue-100 rounded-full h-1.5">
                            <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-700" :style="{ width: Math.min(100, Math.round((stats.collectedThisMonth / stats.revenueThisMonth) * 100)) + '%' }"></div>
                        </div>
                        <span class="text-[10px] text-blue-500 mt-0.5">{{ Math.round((stats.collectedThisMonth / stats.revenueThisMonth) * 100) }}% {{ isRtl ? 'محصّل' : 'collected' }}</span>
                    </div>
                </div>
                <div class="rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50 to-orange-50 p-4">
                    <p class="text-xs font-medium text-amber-600">{{ isRtl ? 'الشهر الماضي' : 'Last Month' }}</p>
                    <p class="text-2xl font-bold text-amber-700 mt-1 tabular-nums">{{ (stats?.revenueLastMonth ?? 0).toLocaleString() }} <span class="text-sm font-normal text-amber-500">{{ isRtl ? 'د.ع' : 'IQD' }}</span></p>
                </div>
            </div>

            <!-- Revenue Trend Chart (Bar) -->
            <div v-if="revenueTrend?.length" class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'اتجاه الإيرادات (12 شهر)' : 'Revenue Trend (12 Months)' }}</h3>
                <div class="flex items-end gap-1.5 h-32">
                    <div v-for="(m, mi) in revenueTrend" :key="mi"
                        class="flex-1 flex flex-col items-center gap-1 group relative">
                        <div class="w-full rounded-t-md bg-gradient-to-t from-emerald-500 to-green-400 ped-bar transition-all duration-200 group-hover:from-emerald-600 group-hover:to-green-500"
                            :style="{ height: Math.max(4, (m.total / Math.max(...revenueTrend.map(r => r.total), 1)) * 100) + '%', animationDelay: `${mi * 60}ms` }">
                        </div>
                        <span class="text-[9px] text-gray-400 truncate w-full text-center">{{ m.monthShort }}</span>
                        <!-- Tooltip -->
                        <div class="absolute -top-10 left-1/2 -translate-x-1/2 hidden group-hover:flex bg-gray-800 text-white text-[10px] px-2 py-1 rounded-lg whitespace-nowrap z-10 shadow-lg">
                            {{ m.month }}: {{ m.total?.toLocaleString() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue by Doctor -->
            <div v-if="revenueByDoctor?.length">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'الإيرادات حسب الطبيب (هذا الشهر)' : 'Revenue by Doctor (This Month)' }}</h3>
                <div class="space-y-2.5">
                    <div v-for="doc in revenueByDoctor" :key="doc.doctor_id" class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-xs font-bold text-emerald-600 flex-shrink-0">
                            {{ (doc.name_en || doc.name_ar || '?').charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-gray-700 truncate">{{ isRtl ? (doc.name_ar || doc.name_en) : (doc.name_en || doc.name_ar) }}</span>
                                <span class="font-bold text-gray-900 tabular-nums ms-2">{{ doc.total?.toLocaleString() }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-700"
                                    :style="{ width: Math.max(4, (doc.total / Math.max(...revenueByDoctor.map(d => d.total), 1)) * 100) + '%' }"></div>
                            </div>
                        </div>
                        <span class="text-[10px] text-gray-400 flex-shrink-0 tabular-nums">{{ doc.invoice_count }} {{ isRtl ? 'فاتورة' : 'inv.' }}</span>
                    </div>
                </div>
            </div>

            <div v-if="!revenueTrend?.length && !revenueByDoctor?.length" class="text-center py-8 text-gray-300">
                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-sm font-medium">{{ isRtl ? 'لا توجد بيانات إيرادات بعد' : 'No revenue data yet' }}</p>
            </div>
        </div>

        <!-- ── Recent Registrations ──────────────────────────── -->
        <div :ref="setSectionRef('recent')" data-section="recent"
            class="ped-section bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6"
            :class="{ 'ped-section-visible': visibleSections.has('recent') }">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-gray-900">
                    {{ isRtl ? 'التسجيلات الأخيرة' : 'Recent Registrations' }}
                </h2>
                <Link href="/admin/pediatric/patients" class="inline-flex items-center gap-1.5 text-sm font-semibold text-green-600 hover:text-green-700 transition group">
                    {{ isRtl ? 'عرض الكل' : 'View All' }}
                    <svg class="w-4 h-4 ltr:group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                </Link>
            </div>
            <div v-if="recentPatients?.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-start pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider">{{ isRtl ? 'الاسم' : 'Name' }}</th>
                            <th class="text-start pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider hidden sm:table-cell">{{ isRtl ? 'العمر' : 'Age' }}</th>
                            <th class="text-center pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider hidden md:table-cell">{{ isRtl ? 'الجنس' : 'Gender' }}</th>
                            <th class="text-start pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider hidden lg:table-cell">{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                            <th class="text-start pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider hidden md:table-cell">{{ isRtl ? 'ولي الأمر' : 'Guardian' }}</th>
                            <th class="text-start pb-3 font-semibold text-gray-500 text-xs uppercase tracking-wider">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, pi) in recentPatients" :key="p.id"
                            class="border-b border-gray-50 hover:bg-green-50/20 transition-colors duration-200 group">
                            <td class="py-3.5">
                                <Link :href="`/admin/patients/${p.id}`" class="font-semibold text-gray-800 hover:text-green-600 group-hover:text-green-600 transition-colors">
                                    {{ p.full_name }}
                                </Link>
                            </td>
                            <td class="py-3.5 text-gray-500 hidden sm:table-cell">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-gray-50 text-xs font-medium">{{ calcAge(p.date_of_birth) }}</span>
                            </td>
                            <td class="py-3.5 text-center hidden md:table-cell">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="p.gender === 'male' ? 'bg-blue-50 text-blue-600' : 'bg-pink-50 text-pink-600'">
                                    {{ p.gender === 'male' ? (isRtl ? 'ذكر' : 'M') : (isRtl ? 'أنثى' : 'F') }}
                                </span>
                            </td>
                            <td class="py-3.5 text-gray-500 hidden lg:table-cell">
                                <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded-lg">{{ p.file_number || '-' }}</span>
                            </td>
                            <td class="py-3.5 text-gray-500 hidden md:table-cell text-sm">{{ p.guardian_name || '-' }}</td>
                            <td class="py-3.5 text-gray-400 text-xs">{{ formatDate(p.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-12 text-gray-300">
                <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                <p class="text-sm font-medium">{{ isRtl ? 'لا يوجد مرضى مسجلين بعد' : 'No patients registered yet' }}</p>
                <Link href="/admin/patients/create" class="mt-3 text-sm font-semibold text-green-600 hover:text-green-700 transition">
                    {{ isRtl ? 'تسجيل مريض جديد' : 'Register First Patient' }} &rarr;
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Hero animations ──────────────────────────────────── */
.ped-hero-up {
    animation: pedHeroUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedHeroUp {
    from { opacity: 0; transform: translateY(24px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.ped-float {
    animation: pedFloat 8s ease-in-out infinite;
}
@keyframes pedFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    33%      { transform: translateY(-10px) rotate(2deg); }
    66%      { transform: translateY(-6px) rotate(-1deg); }
}

.ped-float-delayed {
    animation: pedFloat 7s ease-in-out 2s infinite;
}

/* ── Animated background shapes ──────────────────────── */
.ped-shape-1 { animation: shapeMove1 12s ease-in-out infinite; }
.ped-shape-2 { animation: shapeMove2 10s ease-in-out infinite; }
.ped-shape-3 { animation: shapeMove3 14s ease-in-out infinite; }

@keyframes shapeMove1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%      { transform: translate(-20px, 15px) scale(1.1); }
}
@keyframes shapeMove2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%      { transform: translate(15px, -20px) scale(1.05); }
}
@keyframes shapeMove3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%      { transform: translate(-10px, -15px) scale(0.95); }
}

/* ── Hero buttons — defined inline via classes ──────── */

/* ── Card enter ───────────────────────────────────────── */
.ped-card-enter {
    animation: pedCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedCardEnter {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* ── Section scroll reveal ────────────────────────────── */
.ped-section {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
.ped-section-visible, .ped-section.ped-section-visible {
    opacity: 1;
    transform: translateY(0);
}

/* ── SVG donut animation ─────────────────────────────── */
.ped-donut-animate {
    animation: donutDraw 1.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes donutDraw {
    from { stroke-dasharray: 0 999; }
}

/* ── Bar chart animation ─────────────────────────────── */
.ped-bar {
    transform-origin: bottom;
    animation: barGrow 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes barGrow {
    from { transform: scaleY(0); }
    to   { transform: scaleY(1); }
}
</style>
