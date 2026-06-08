<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import TrendLine from '@/Components/Charts/TrendLine.vue';
import { useCountUp } from '@/Composables/useCountUp';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (en, ar) => (isRtl.value ? ar : en);

const NAVY = '#1B365D';
const GOLD = '#C4A265';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    todayVisits: { type: Array, default: () => [] },
    recentSessions: { type: Array, default: () => [] },
    sessionsTrend: { type: Array, default: () => [] },
    resumePlans: { type: Array, default: () => [] },
});

const shown = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { shown.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { shown.value = true; }));
});

const { values: counters } = useCountUp({
    visits_today: props.stats.visits_today || 0,
    sessions_this_month: props.stats.sessions_this_month || 0,
    active_plans: props.stats.active_plans || 0,
    revenue_this_month: props.stats.revenue_this_month || 0,
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (isRtl.value) return h < 12 ? 'صباح الخير' : 'مساء الخير';
    return h < 12 ? 'Good Morning' : (h < 17 ? 'Good Afternoon' : 'Good Evening');
});
const today = computed(() => new Date().toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
const fmt = (n) => Math.round(Number(n) || 0).toLocaleString();

const revDelta = computed(() => {
    const cur = props.stats.revenue_this_month || 0;
    const prev = props.stats.revenue_prev_month || 0;
    if (!prev) return null;
    return Math.round(((cur - prev) / prev) * 100);
});

const kpis = computed(() => [
    { key: 'revenue_this_month', label: t('Revenue / month', 'الإيراد / الشهر'), color: NAVY, money: true, delta: revDelta.value },
    { key: 'visits_today', label: t('Visits today', 'زيارات اليوم'), color: '#0EA5E9' },
    { key: 'sessions_this_month', label: t('Sessions / month', 'جلسات / شهر'), color: GOLD },
    { key: 'active_plans', label: t('Active plans', 'خطط نشطة'), color: '#059669' },
]);

const trendSeries = computed(() => {
    const pts = props.sessionsTrend.map((d) => ({ x: d.x, y: d.y }));
    return pts.some((p) => p.y > 0) ? [{ key: 's', label: t('Sessions', 'الجلسات'), color: GOLD, points: pts }] : [];
});
const statusStyle = (s) => ({ waiting: 'bg-amber-50 text-amber-600', in_progress: 'bg-blue-50 text-blue-600', completed: 'bg-emerald-50 text-emerald-600' }[s] || 'bg-gray-100 text-gray-500');
const dateLabel = (d) => (d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short' }) : '');
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl p-6 md:p-8 text-white shadow-lg transition-all duration-700"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-2'"
             style="background: linear-gradient(120deg,#1B365D 0%,#3b4d74 55%,#C4A265 165%)">
            <div class="absolute -top-10 end-0 w-52 h-52 rounded-full opacity-15 bg-white"></div>
            <div class="relative z-10 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-white/70 text-sm">{{ today }}</p>
                    <h1 class="text-2xl md:text-3xl font-bold mt-1">{{ greeting }}</h1>
                    <p class="text-white/80 mt-2">{{ t('Dermatology & Cosmetics', 'الجلدية والتجميل') }} · {{ todayVisits.length }} {{ t('visits today', 'زيارة اليوم') }}</p>
                </div>
                <Link href="/doctor/derma/patients" class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 backdrop-blur px-5 py-3 rounded-xl font-semibold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
                    {{ t('Patients', 'المرضى') }}
                </Link>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="(c, i) in kpis" :key="c.key"
                 class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 transition-all duration-500 hover:-translate-y-0.5 hover:shadow-md"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'" :style="{ transitionDelay: (i * 70) + 'ms' }">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold tabular-nums" :style="{ color: c.color }">{{ fmt(counters[c.key]) }}<span v-if="c.money" class="text-xs font-normal text-gray-400"> {{ t('EGP', 'ج.م') }}</span></p>
                    <span v-if="c.delta != null" class="text-[11px] font-semibold px-1.5 py-0.5 rounded-md" :class="c.delta >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">{{ c.delta >= 0 ? '+' : '' }}{{ c.delta }}%</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ c.label }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Today visits -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t("Today's Visits", 'زيارات اليوم') }}</h2>
                <p v-if="!todayVisits.length" class="text-sm text-gray-400 py-8 text-center">{{ t('No visits scheduled today', 'لا زيارات اليوم') }}</p>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="v in todayVisits" :key="v.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="py-2.5 text-gray-400 text-xs w-14">{{ v.scheduled_time }}</td>
                            <td class="py-2.5 font-medium text-gray-800">{{ v.patient?.full_name }}</td>
                            <td class="py-2.5"><span class="text-[11px] px-2 py-0.5 rounded-full" :class="statusStyle(v.status)">{{ v.status }}</span></td>
                            <td class="py-2.5 text-end"><Link :href="`/doctor/visits/${v.id}`" class="text-xs font-medium hover:underline" :style="{ color: NAVY }">{{ t('open', 'فتح') }}</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Sessions trend -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-3">{{ t('Sessions — 14 days', 'الجلسات — 14 يوماً') }}</h2>
                <TrendLine v-if="trendSeries.length" :series="trendSeries" :is-rtl="isRtl" :height="180" />
                <p v-else class="text-sm text-gray-400 py-10 text-center">{{ t('No sessions yet', 'لا جلسات بعد') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Resume plans -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">{{ t('Resume treatment plans', 'استئناف الخطط') }}</h2>
                    <Link href="/doctor/derma/treatment-plans" class="text-xs font-medium hover:underline" :style="{ color: NAVY }">{{ t('All', 'الكل') }} →</Link>
                </div>
                <p v-if="!resumePlans.length" class="text-sm text-gray-400 py-6 text-center">{{ t('No active plans', 'لا خطط نشطة') }}</p>
                <div v-for="p in resumePlans" :key="p.id" class="py-2 border-b border-gray-50 last:border-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ p.patient?.full_name }} <span class="text-xs text-gray-400">· {{ p.title }}</span></p>
                        <span class="text-xs text-gray-500">{{ p.completed_sessions }}/{{ p.estimated_sessions }}</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden mt-1.5">
                        <div class="h-full rounded-full" :style="{ width: (shown ? p.progress : 0) + '%', backgroundColor: GOLD, transition: 'width 0.9s cubic-bezier(0.16,1,0.3,1)' }"></div>
                    </div>
                </div>
            </div>

            <!-- Recent sessions -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-3">{{ t('Recent sessions', 'أحدث الجلسات') }}</h2>
                <p v-if="!recentSessions.length" class="text-sm text-gray-400 py-6 text-center">{{ t('No sessions yet', 'لا جلسات بعد') }}</p>
                <ul v-else class="space-y-1.5">
                    <li v-for="s in recentSessions" :key="s.id" class="flex items-center justify-between text-sm border-b border-gray-50 last:border-0 pb-1.5">
                        <span class="text-gray-700">{{ s.patient?.full_name }}</span>
                        <span class="text-xs text-gray-400">{{ dateLabel(s.completed_at) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
