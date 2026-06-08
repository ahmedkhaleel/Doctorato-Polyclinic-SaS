<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCountUp } from '@/Composables/useCountUp';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    canSeeSensitive: { type: Boolean, default: false },
    stats: { type: Object, default: () => ({}) },
    todayQueue: { type: Array, default: () => [] },
    flaggedScales: { type: Array, default: () => [] },
    severityMix: { type: Array, default: () => [] },
    monitoringDue: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    recentEncounters: { type: Array, default: () => [] },
    riskRegister: { type: Array, default: () => [] },
});

const isPsych = computed(() => props.module === 'psychiatry');
const ACCENT = computed(() => (isPsych.value ? '#4F46E5' : '#0D9488')); // indigo / teal
const ACCENT2 = computed(() => (isPsych.value ? '#7C3AED' : '#0EA5E9'));
const moduleName = computed(() => (isPsych.value ? t('Psychiatry', 'الطب النفسي') : t('Neurology', 'المخ والأعصاب')));

const shown = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { shown.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { shown.value = true; }));
});

const { values: counters } = useCountUp({
    active_cases: props.stats.active_cases || 0,
    encounters_this_month: props.stats.encounters_this_month || 0,
    active_medications: props.stats.active_medications || 0,
    scales_this_month: props.stats.scales_this_month || 0,
    monitoring_due: props.stats.monitoring_due || 0,
    active_high_risk: props.stats.active_high_risk || 0,
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (isRtl.value) return h < 12 ? 'صباح الخير' : 'مساء الخير';
    return h < 12 ? 'Good Morning' : (h < 17 ? 'Good Afternoon' : 'Good Evening');
});
const today = computed(() => new Date().toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));

const kpis = computed(() => {
    const base = [
        { key: 'active_cases', label: t('Active cases', 'حالات نشطة'), color: ACCENT.value },
        { key: 'encounters_this_month', label: t('Encounters / month', 'لقاءات / شهر'), color: ACCENT2.value },
        { key: 'active_medications', label: t('Active medications', 'أدوية فعّالة'), color: '#0D9488' },
        { key: 'scales_this_month', label: t('Scales / month', 'مقاييس / شهر'), color: '#C4A265' },
        { key: 'monitoring_due', label: t('Monitoring due', 'مراقبة مستحقة'), color: '#EA580C' },
    ];
    if (props.canSeeSensitive) base.push({ key: 'active_high_risk', label: t('Elevated risk', 'خطورة مرتفعة'), color: '#EF4444' });
    return base;
});

const SCALE_LABELS = { phq9: 'PHQ-9', gad7: 'GAD-7', hit6: 'HIT-6', moca: 'MoCA', mmse: 'MMSE', updrs: 'UPDRS', edss: 'EDSS', auditc: 'AUDIT-C' };
const scaleLabel = (k) => SCALE_LABELS[k] || (k ? k.toUpperCase() : '—');

const SEV_COLOR = { none: '#22C55E', minimal: '#22C55E', mild: '#84CC16', moderate: '#F59E0B', 'moderately severe': '#F97316', severe: '#EF4444' };
const sevColor = (s) => SEV_COLOR[(s || '').toLowerCase()] || '#94A3B8';
const sevTotal = computed(() => props.severityMix.reduce((a, r) => a + r.count, 0) || 1);

const riskStyle = (lvl) => (lvl === 'high' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-amber-50 text-amber-600 border-amber-200');
const statusStyle = (s) => ({ waiting: 'bg-amber-50 text-amber-600', in_progress: 'bg-blue-50 text-blue-600', completed: 'bg-emerald-50 text-emerald-600' }[s] || 'bg-gray-100 text-gray-500');
const dateLabel = (d) => (d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short' }) : '');
const lp = (path) => `/doctor/${props.module}${path}`;
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl p-6 md:p-8 text-white shadow-lg transition-all duration-700"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-2'"
             :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${ACCENT2} 60%, ${ACCENT} 165%)` }">
            <div class="absolute -top-10 end-0 w-52 h-52 rounded-full opacity-15" style="background:#C4A265"></div>
            <div class="relative z-10 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-white/70 text-sm">{{ today }}</p>
                    <h1 class="text-2xl md:text-3xl font-bold mt-1">{{ greeting }}</h1>
                    <p class="text-white/80 mt-2">{{ moduleName }} · {{ t('today', 'اليوم') }}: {{ todayQueue.length }} {{ t('in queue', 'بالطابور') }}</p>
                </div>
                <Link :href="lp('/encounters')" class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 backdrop-blur px-5 py-3 rounded-xl font-semibold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    {{ t('Encounters', 'اللقاءات') }}
                </Link>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
            <div v-for="(c, i) in kpis" :key="c.key"
                 class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 transition-all duration-500 hover:-translate-y-0.5 hover:shadow-md"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'" :style="{ transitionDelay: (i * 70) + 'ms' }">
                <p class="text-2xl font-bold tabular-nums" :style="{ color: c.color }">{{ counters[c.key] }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ c.label }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Today queue -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t("Today's Schedule", 'مواعيد اليوم') }}</h2>
                <p v-if="!todayQueue.length" class="text-sm text-gray-400 py-8 text-center">{{ t('No patients scheduled today', 'لا مرضى اليوم') }}</p>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="q in todayQueue" :key="q.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="py-2.5 text-gray-400 text-xs w-14">{{ q.time }}</td>
                            <td class="py-2.5 font-medium text-gray-800">{{ q.patient?.full_name }}</td>
                            <td class="py-2.5 text-gray-400 text-xs">{{ q.patient?.file_number }}</td>
                            <td class="py-2.5"><span class="text-[11px] px-2 py-0.5 rounded-full" :class="statusStyle(q.status)">{{ q.status }}</span></td>
                            <td class="py-2.5 text-end"><Link :href="`/doctor/visits/${q.id}`" class="text-xs font-medium hover:underline" :style="{ color: ACCENT }">{{ t('visit', 'الزيارة') }}</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- MBC severity mix -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t('Latest-scale severity', 'شدّة آخر المقاييس') }}</h2>
                <p v-if="!severityMix.length" class="text-sm text-gray-400 py-6 text-center">—</p>
                <div v-else class="space-y-2.5">
                    <div v-for="s in severityMix" :key="s.severity">
                        <div class="flex justify-between text-xs mb-1"><span class="text-gray-600 capitalize">{{ s.severity }}</span><span class="text-gray-400 tabular-nums">{{ s.count }}</span></div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full" :style="{ width: (shown ? Math.round(s.count / sevTotal * 100) : 0) + '%', backgroundColor: sevColor(s.severity), transition: 'width 0.9s cubic-bezier(0.16,1,0.3,1)' }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action strip -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Flagged scales -->
            <div class="rounded-2xl p-4 border bg-rose-50/60 border-rose-100">
                <p class="text-xs font-semibold text-rose-700 mb-2">{{ t('Flagged scales', 'مقاييس بإنذار') }} ({{ flaggedScales.length }})</p>
                <ul class="space-y-1">
                    <li v-for="s in flaggedScales.slice(0,5)" :key="s.id" class="text-xs text-gray-600 flex items-center justify-between gap-2">
                        <span class="truncate">{{ s.patient }} · {{ scaleLabel(s.scale_key) }} {{ s.score }}</span>
                        <span class="text-gray-400 shrink-0">{{ dateLabel(s.taken_at) }}</span>
                    </li>
                    <li v-if="!flaggedScales.length" class="text-xs text-gray-400">—</li>
                </ul>
            </div>
            <!-- Monitoring due -->
            <div class="rounded-2xl p-4 border bg-amber-50/60 border-amber-100">
                <p class="text-xs font-semibold text-amber-700 mb-2">{{ t('Medication monitoring due', 'مراقبة دواء مستحقة') }} ({{ monitoringDue.length }})</p>
                <ul class="space-y-1">
                    <li v-for="m in monitoringDue.slice(0,5)" :key="m.id" class="text-xs text-gray-600 flex items-center justify-between gap-2">
                        <span class="truncate">{{ m.patient }} · {{ m.type }}</span>
                        <span class="text-gray-400 shrink-0">{{ dateLabel(m.due_at) }}</span>
                    </li>
                    <li v-if="!monitoringDue.length" class="text-xs text-gray-400">—</li>
                </ul>
            </div>
            <!-- Risk register (gated) -->
            <div v-if="canSeeSensitive" class="rounded-2xl p-4 border bg-red-50/60 border-red-100">
                <p class="text-xs font-semibold text-red-700 mb-2 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ t('Risk register', 'سجل المخاطر') }} ({{ riskRegister.length }})
                </p>
                <ul class="space-y-1">
                    <li v-for="(r, i) in riskRegister.slice(0,5)" :key="i" class="text-xs text-gray-700 flex items-center justify-between gap-2">
                        <span class="truncate">{{ r.patient }} · {{ r.type }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full border shrink-0" :class="riskStyle(r.risk_level)">{{ r.risk_level }}</span>
                    </li>
                    <li v-if="!riskRegister.length" class="text-xs text-gray-400">—</li>
                </ul>
            </div>
            <div v-else class="rounded-2xl p-4 border bg-gray-50 border-gray-100 flex items-center justify-center text-center">
                <p class="text-[11px] text-gray-400">{{ t('Risk register requires elevated access', 'سجل المخاطر يتطلب صلاحية مرتفعة') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Active courses -->
            <div v-if="courses.length" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">{{ t('Treatment courses', 'الدورات العلاجية') }}</h2>
                    <Link :href="lp('/courses')" class="text-xs font-medium hover:underline" :style="{ color: ACCENT }">{{ t('All', 'الكل') }} →</Link>
                </div>
                <div v-for="c in courses" :key="c.id" class="py-2 border-b border-gray-50 last:border-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-800">{{ c.patient }} <span class="text-xs text-gray-400 uppercase">· {{ c.type }}</span></p>
                        <span class="text-xs text-gray-500">{{ c.done_sessions }}/{{ c.planned_sessions }}</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden mt-1.5">
                        <div class="h-full rounded-full" :style="{ width: (shown ? Math.min(100, Math.round(c.done_sessions / Math.max(1, c.planned_sessions) * 100)) : 0) + '%', backgroundColor: ACCENT, transition: 'width 0.9s cubic-bezier(0.16,1,0.3,1)' }"></div>
                    </div>
                </div>
            </div>

            <!-- Recent encounters -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100" :class="!courses.length && 'lg:col-span-2'">
                <h2 class="font-semibold text-gray-800 mb-3">{{ t('Recent encounters', 'أحدث اللقاءات') }}</h2>
                <p v-if="!recentEncounters.length" class="text-sm text-gray-400 py-6 text-center">{{ t('No encounters yet', 'لا لقاءات بعد') }}</p>
                <ul v-else class="space-y-1.5">
                    <li v-for="e in recentEncounters" :key="e.id" class="flex items-center justify-between text-sm border-b border-gray-50 last:border-0 pb-1.5">
                        <span class="text-gray-700">{{ e.patient?.full_name }}</span>
                        <span class="text-xs text-gray-400">{{ dateLabel(e.date) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
