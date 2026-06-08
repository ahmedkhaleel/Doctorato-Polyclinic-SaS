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
const CYAN = '#06B6D4';
const GOLD = '#C4A265';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    todayQueue: { type: Array, default: () => [] },
    alerts: { type: Object, default: () => ({}) },
    conditionMix: { type: Array, default: () => [] },
    procedureMix: { type: Array, default: () => [] },
    productionTrend: { type: Array, default: () => [] },
    resumePlans: { type: Array, default: () => [] },
});

// Staggered reveal (reduced-motion aware).
const shown = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { shown.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { shown.value = true; }));
});

const { values: counters } = useCountUp({
    production_today: props.stats.production_today || 0,
    production_month: props.stats.production_month || 0,
    completed_today: props.stats.completed_today || 0,
    active_plans: props.stats.active_plans || 0,
    pending_treatments: props.stats.pending_treatments || 0,
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (isRtl.value) return h < 12 ? 'صباح الخير' : 'مساء الخير';
    return h < 12 ? 'Good Morning' : (h < 17 ? 'Good Afternoon' : 'Good Evening');
});
const today = computed(() => new Date().toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));

// Production delta vs last month.
const prodDelta = computed(() => {
    const cur = props.stats.production_month || 0;
    const prev = props.stats.production_prev_month || 0;
    if (!prev) return null;
    return Math.round(((cur - prev) / prev) * 100);
});

const fmt = (n) => Math.round(Number(n) || 0).toLocaleString();

const kpis = computed(() => [
    { key: 'production_month', label: t('Production / month', 'الإنتاج / الشهر'), color: NAVY, money: true, delta: prodDelta.value },
    { key: 'production_today', label: t('Production / today', 'الإنتاج / اليوم'), color: CYAN, money: true },
    { key: 'completed_today', label: t('Completed today', 'مكتملة اليوم'), color: '#059669' },
    { key: 'active_plans', label: t('Active plans', 'خطط نشطة'), color: GOLD },
    { key: 'pending_treatments', label: t('Pending treatments', 'علاجات معلّقة'), color: '#EA580C' },
]);

// ── Condition mix (matches odontogram accents) ──
const CONDITION_COLORS = { healthy: '#22C55E', decayed: '#EF4444', filled: '#3B82F6', missing: '#9CA3AF', crown: '#EAB308', bridge: '#A855F7', implant: '#6366F1', root_canal: '#F97316', extracted: '#6B7280', caries: '#EF4444' };
const CONDITION_LABELS = { healthy: ['Healthy', 'سليم'], decayed: ['Decayed', 'تسوّس'], filled: ['Filled', 'محشو'], missing: ['Missing', 'مفقود'], crown: ['Crown', 'تاج'], bridge: ['Bridge', 'جسر'], implant: ['Implant', 'زرعة'], root_canal: ['Root canal', 'عصب'], extracted: ['Extracted', 'مخلوع'] };
const condColor = (c) => CONDITION_COLORS[c] || '#94A3B8';
const condLabel = (c) => (CONDITION_LABELS[c] ? (isRtl.value ? CONDITION_LABELS[c][1] : CONDITION_LABELS[c][0]) : c);
const condTotal = computed(() => props.conditionMix.reduce((s, r) => s + r.count, 0) || 1);

// ── Procedure mix ──
const PROC_LABELS = { filling: ['Filling', 'حشو'], extraction: ['Extraction', 'خلع'], crown: ['Crown', 'تاج'], root_canal: ['Root canal', 'علاج عصب'], cleaning: ['Cleaning', 'تنظيف'], scaling: ['Scaling', 'تقليح'], implant: ['Implant', 'زرعة'], bridge: ['Bridge', 'جسر'], whitening: ['Whitening', 'تبييض'], denture: ['Denture', 'طقم'], veneer: ['Veneer', 'فينير'] };
const procLabel = (ty) => (PROC_LABELS[ty] ? (isRtl.value ? PROC_LABELS[ty][1] : PROC_LABELS[ty][0]) : ty);
const procMax = computed(() => Math.max(1, ...props.procedureMix.map((p) => p.count)));

// Production trend → TrendLine series.
const trendSeries = computed(() => {
    const pts = props.productionTrend.map((d) => ({ x: d.x, y: d.y }));
    return pts.some((p) => p.y > 0) ? [{ key: 'prod', label: t('Production', 'الإنتاج'), color: CYAN, points: pts }] : [];
});

const procName = (ty) => procLabel(ty);
const statusStyle = (s) => ({ waiting: 'bg-amber-50 text-amber-600', in_progress: 'bg-blue-50 text-blue-600', completed: 'bg-emerald-50 text-emerald-600' }[s] || 'bg-gray-100 text-gray-500');
const toothProc = (q) => [q.next_tooth ? '#' + q.next_tooth : null, q.next_procedure ? procName(q.next_procedure) : null].filter(Boolean).join(' · ');
const totalAlerts = computed(() => (props.alerts.consents_pending?.length || 0) + (props.alerts.lab_ready?.length || 0) + (props.alerts.stalled_plans || 0));
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl p-6 md:p-8 text-white shadow-lg transition-all duration-700"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-2'"
             style="background: linear-gradient(120deg,#1B365D 0%,#155e75 55%,#06B6D4 165%)">
            <div class="absolute -top-10 end-0 w-52 h-52 rounded-full opacity-15" style="background:#C4A265"></div>
            <svg class="absolute -bottom-8 end-10 w-40 h-40 opacity-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C9 2 8 4 6 4S2 3 2 8c0 4 2 6 3 9s1 5 3 5 1-4 4-4 2 4 4 4 2-2 3-5 3-5 3-9c0-5-2-4-4-4s-3-2-6-2z"/></svg>
            <div class="relative z-10 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-white/70 text-sm">{{ today }}</p>
                    <h1 class="text-2xl md:text-3xl font-bold mt-1">{{ greeting }}</h1>
                    <p class="text-white/80 mt-2">{{ t('Dental Cockpit', 'لوحة قيادة الأسنان') }} · {{ t('today', 'اليوم') }}: {{ stats.treatments_today || 0 }} {{ t('treatments', 'علاج') }} · {{ todayQueue.length }} {{ t('in queue', 'بالطابور') }}</p>
                </div>
                <Link href="/doctor/dental/chart-search"
                      class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 backdrop-blur px-5 py-3 rounded-xl font-semibold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    {{ t('Open Odontogram', 'فتح المخطط') }}
                </Link>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div v-for="(c, i) in kpis" :key="c.key"
                 class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 transition-all duration-500 hover:-translate-y-0.5 hover:shadow-md"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
                 :style="{ transitionDelay: (i * 70) + 'ms' }">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-bold tabular-nums" :style="{ color: c.color }">
                        {{ fmt(counters[c.key]) }}<span v-if="c.money" class="text-xs font-normal text-gray-400"> {{ t('EGP', 'ج.م') }}</span>
                    </p>
                    <span v-if="c.delta != null" class="text-[11px] font-semibold px-1.5 py-0.5 rounded-md"
                          :class="c.delta >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                        {{ c.delta >= 0 ? '+' : '' }}{{ c.delta }}%
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ c.label }}</p>
            </div>
        </div>

        <!-- Action strip -->
        <div v-if="totalAlerts" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-2xl p-4 border bg-rose-50/60 border-rose-100">
                <p class="text-xs font-semibold text-rose-700 mb-2">{{ t('Consents awaiting signature', 'موافقات بانتظار التوقيع') }} ({{ alerts.consents_pending?.length || 0 }})</p>
                <ul class="space-y-1">
                    <li v-for="c in (alerts.consents_pending || []).slice(0,4)" :key="c.id" class="text-xs text-gray-600 flex items-center justify-between gap-2">
                        <span class="truncate">{{ c.patient }}</span>
                        <Link :href="`/doctor/dental/treatment-plans/${c.id}`" class="text-rose-600 hover:underline shrink-0">{{ t('open', 'فتح') }}</Link>
                    </li>
                    <li v-if="!(alerts.consents_pending || []).length" class="text-xs text-gray-400">—</li>
                </ul>
            </div>
            <div class="rounded-2xl p-4 border bg-cyan-50/60 border-cyan-100">
                <p class="text-xs font-semibold text-cyan-700 mb-2">{{ t('Lab work ready', 'أعمال المعمل جاهزة') }} ({{ alerts.lab_ready?.length || 0 }})</p>
                <ul class="space-y-1">
                    <li v-for="l in (alerts.lab_ready || []).slice(0,4)" :key="l.id" class="text-xs text-gray-600 truncate">{{ l.patient }}</li>
                    <li v-if="!(alerts.lab_ready || []).length" class="text-xs text-gray-400">—</li>
                </ul>
            </div>
            <div class="rounded-2xl p-4 border bg-amber-50/60 border-amber-100 flex flex-col justify-center">
                <p class="text-xs font-semibold text-amber-700">{{ t('Stalled plans', 'خطط متوقفة') }}</p>
                <p class="text-3xl font-bold text-amber-600 tabular-nums mt-1">{{ alerts.stalled_plans || 0 }}</p>
                <p class="text-[11px] text-gray-400">{{ t('started but no update in 14+ days', 'بدأت ولم تُحدّث منذ 14+ يوماً') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Today chair queue -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t("Today's Chair", 'كرسي اليوم') }}</h2>
                <p v-if="!todayQueue.length" class="text-sm text-gray-400 py-8 text-center">{{ t('No patients scheduled today', 'لا مرضى اليوم') }}</p>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="q in todayQueue" :key="q.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="py-2.5 text-gray-400 text-xs w-14">{{ q.time }}</td>
                            <td class="py-2.5 font-medium text-gray-800">{{ q.patient?.full_name }}</td>
                            <td class="py-2.5 text-xs" :style="{ color: CYAN }">{{ toothProc(q) || '—' }}</td>
                            <td class="py-2.5"><span class="text-[11px] px-2 py-0.5 rounded-full" :class="statusStyle(q.status)">{{ q.status }}</span></td>
                            <td class="py-2.5 text-end">
                                <Link v-if="q.patient" :href="`/doctor/dental/chart/${q.patient.id}`" class="text-xs font-medium hover:underline" :style="{ color: NAVY }">{{ t('chart', 'المخطط') }}</Link>
                                <Link :href="`/doctor/visits/${q.id}`" class="text-xs font-medium hover:underline ms-3" :style="{ color: CYAN }">{{ t('visit', 'الزيارة') }}</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Condition mix -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t('Caseload condition mix', 'توزيع حالات الأسنان') }}</h2>
                <p v-if="!conditionMix.length" class="text-sm text-gray-400 py-6 text-center">—</p>
                <div v-else class="space-y-2.5">
                    <div v-for="c in conditionMix" :key="c.condition">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-600">{{ condLabel(c.condition) }}</span>
                            <span class="text-gray-400 tabular-nums">{{ c.count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full" :style="{ width: (shown ? Math.round(c.count / condTotal * 100) : 0) + '%', backgroundColor: condColor(c.condition), transition: 'width 0.9s cubic-bezier(0.16,1,0.3,1)' }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Production trend -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-3">{{ t('Production — last 14 days', 'الإنتاج — آخر 14 يوماً') }}</h2>
                <TrendLine v-if="trendSeries.length" :series="trendSeries" :is-rtl="isRtl" :height="200" />
                <p v-else class="text-sm text-gray-400 py-10 text-center">{{ t('No completed treatments yet', 'لا علاجات مكتملة بعد') }}</p>
            </div>

            <!-- Procedure mix + production -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-3">{{ t('Procedure mix', 'مزيج الإجراءات') }}</h2>
                <p v-if="!procedureMix.length" class="text-sm text-gray-400 py-10 text-center">—</p>
                <div v-else class="space-y-2.5">
                    <div v-for="(p, i) in procedureMix.slice(0, 7)" :key="p.type">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-700">{{ procName(p.type) }} <span class="text-gray-400">×{{ p.count }}</span></span>
                            <span class="text-gray-500 tabular-nums">{{ fmt(p.revenue) }} {{ t('EGP', 'ج.م') }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full" :style="{ width: (shown ? Math.round(p.count / procMax * 100) : 0) + '%', backgroundColor: NAVY, transition: `width 0.9s cubic-bezier(0.16,1,0.3,1) ${i * 60}ms` }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resume plans -->
        <div v-if="resumePlans.length" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">{{ t('Resume treatment plans', 'استئناف خطط العلاج') }}</h2>
                <Link href="/doctor/dental/treatment-plans" class="text-xs font-medium hover:underline" :style="{ color: NAVY }">{{ t('All plans', 'كل الخطط') }} →</Link>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <Link v-for="p in resumePlans" :key="p.id" :href="`/doctor/dental/treatment-plans/${p.id}`"
                      class="block border border-gray-100 rounded-xl p-3 hover:border-cyan-200 hover:shadow-sm transition">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ p.patient?.full_name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ p.title }}</p>
                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden mt-2">
                        <div class="h-full rounded-full" :style="{ width: (shown ? p.progress : 0) + '%', backgroundColor: CYAN, transition: 'width 0.9s cubic-bezier(0.16,1,0.3,1)' }"></div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">{{ p.completed_sessions }}/{{ p.estimated_sessions }} · {{ p.progress }}%</p>
                </Link>
            </div>
        </div>
    </div>
</template>
