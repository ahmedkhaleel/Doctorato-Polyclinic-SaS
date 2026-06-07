<script setup>
/**
 * NeuroPsychPanel — shared clinical cockpit for psychiatry & neurology visits.
 * Surfaces the encounter note (SOAP/MSE), the measurement-based-care scale trend,
 * active medications, and a SENSITIVE risk summary that is locked unless the user
 * holds {module}.view_sensitive (server enforces + audits; UI mirrors the gate).
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import CalendarHeatmap from '@/Components/Charts/CalendarHeatmap.vue';
import TrendLine from '@/Components/Charts/TrendLine.vue';
import Sparkline from '@/Components/Charts/Sparkline.vue';

const props = defineProps({
    visit: { type: Object, required: true },
    isRtl: { type: Boolean, default: true },
    mounted: { type: Boolean, default: false },
    context: { type: String, default: 'doctor' },
    patientHref: { type: String, default: null },
    neuroEncounter: { type: Object, default: null },
    neuroScales: { type: Array, default: () => [] },
    neuroMeds: { type: Array, default: () => [] },
    neuroRisk: { type: Object, default: null },
    neuroCanViewSensitive: { type: Boolean, default: false },
    neuroSeizures: { type: Array, default: () => [] },
    neuroHeadaches: { type: Array, default: () => [] },
});

const seizureEvents = computed(() => (props.neuroSeizures || []).map(s => ({ date: String(s.occurred_at).slice(0, 10) })));
const headacheSeries = computed(() => [{
    key: 'ha', label: props.isRtl ? 'شدّة الصداع' : 'Headache intensity', color: '#0D9488',
    points: (props.neuroHeadaches || [])
        .filter(h => h.intensity != null)
        .map((h, i) => ({ x: i + 1, y: Number(h.intensity) })),
}]);
const hasSeizures = computed(() => seizureEvents.value.length > 0);
const hasHeadaches = computed(() => headacheSeries.value[0].points.length >= 2);

const module = computed(() => props.visit?.module);
const isPsych = computed(() => module.value === 'psychiatry');
const ACCENT = computed(() => (isPsych.value ? '#4F46E5' : '#0D9488')); // indigo / teal

const isAdmin = computed(() => props.context === 'admin');
const encountersHref = computed(() => isAdmin.value ? (props.patientHref || '#') : `/doctor/${module.value}/encounters`);
const medsHref = computed(() => isAdmin.value ? (props.patientHref || '#') : `/doctor/${module.value}/medications`);
const scalesHref = computed(() => isAdmin.value ? (props.patientHref || '#') : `/doctor/${module.value}/scales/${props.visit.patient_id}/trend`);

const SCALE_LABELS = {
    phq9: 'PHQ-9', gad7: 'GAD-7', hit6: 'HIT-6', moca: 'MoCA',
    mmse: 'MMSE', updrs: 'UPDRS', edss: 'EDSS', auditc: 'AUDIT-C',
};
function scaleLabel(k) { return SCALE_LABELS[k] || (k ? k.toUpperCase() : '—'); }

const SEVERITY_STYLE = {
    none: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    minimal: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    mild: 'bg-amber-50 text-amber-700 border-amber-200',
    moderate: 'bg-orange-50 text-orange-700 border-orange-200',
    'moderately severe': 'bg-red-50 text-red-700 border-red-200',
    severe: 'bg-red-50 text-red-700 border-red-200',
};
function sevStyle(s) { return SEVERITY_STYLE[(s || '').toLowerCase()] || 'bg-slate-50 text-slate-600 border-slate-200'; }

const RISK_STYLE = {
    none: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', label_ar: 'لا يوجد', label_en: 'None' },
    low: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', label_ar: 'منخفض', label_en: 'Low' },
    moderate: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-300', label_ar: 'متوسط', label_en: 'Moderate' },
    high: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-300', label_ar: 'مرتفع', label_en: 'High' },
};
const riskStyle = computed(() => RISK_STYLE[props.neuroRisk?.risk_level] || RISK_STYLE.none);
const riskElevated = computed(() => ['moderate', 'high'].includes(props.neuroRisk?.risk_level));

const latestScale = computed(() => (props.neuroScales || [])[0] || null);

// Measurement-based-care (MBC) trends — one faceted mini-trend per scale_key,
// each on its own scale (oldest → newest). Lower score = improving (PHQ-9/GAD-7…).
const scaleTrends = computed(() => {
    const byKey = {};
    for (const s of [...(props.neuroScales || [])].reverse()) { // oldest → newest
        if (s.score == null) continue;
        (byKey[s.scale_key] ||= []).push(s);
    }
    return Object.entries(byKey).map(([key, arr]) => ({
        key,
        label: scaleLabel(key),
        values: arr.map(s => Number(s.score)),
        latest: arr[arr.length - 1],
        first: arr[0],
        improving: Number(arr[arr.length - 1].score) < Number(arr[0].score),
    }));
});
const hasScaleTrends = computed(() => scaleTrends.value.length > 0);

const activeMeds = computed(() => props.neuroMeds || []);

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString(props.isRtl ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const hasContent = computed(() => props.neuroEncounter || (props.neuroScales || []).length || activeMeds.value.length || props.neuroRisk || hasSeizures.value || hasHeadaches.value);
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
    >
        <!-- Header band -->
        <div class="relative overflow-hidden px-4 sm:px-6 py-4 border-b border-gray-100"
            :style="`background: linear-gradient(115deg, ${ACCENT}14 0%, ${ACCENT}05 60%, transparent 100%)`">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="`background:${ACCENT}1A`">
                        <svg class="w-4 h-4" :style="`color:${ACCENT}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3a6 6 0 00-6 6c0 1.886.871 3.568 2.234 4.668.45.363.766.86.766 1.432V16a1 1 0 001 1h4a1 1 0 001-1v-.9c0-.572.316-1.069.766-1.432A5.99 5.99 0 0018 9a6 6 0 00-6-6z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isPsych ? (isRtl ? 'الطب النفسي' : 'Psychiatry') : (isRtl ? 'المخ والأعصاب' : 'Neurology') }}</h3>
                </div>
                <Link :href="encountersHref" class="inline-flex items-center gap-1 text-xs font-medium transition-colors" :style="`color:${ACCENT}`">
                    {{ isRtl ? 'سجل اللقاءات' : 'Encounters' }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'" /></svg>
                </Link>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-6">
            <!-- Empty -->
            <div v-if="!hasContent" class="text-center py-8">
                <div class="w-12 h-12 mx-auto rounded-xl flex items-center justify-center mb-3 border" :style="`background:${ACCENT}0D; border-color:${ACCENT}33`">
                    <svg class="w-6 h-6" :style="`color:${ACCENT}99`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-sm text-gray-400 mb-3">{{ isRtl ? 'لا يوجد لقاء أو مقاييس بعد' : 'No encounter or scales yet' }}</p>
                <Link :href="encountersHref" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white rounded-lg transition-all hover:-translate-y-0.5" :style="`background:${ACCENT}`">
                    {{ isRtl ? 'تسجيل لقاء' : 'New encounter' }}
                </Link>
            </div>

            <template v-else>
                <!-- Risk banner (sensitive, elevated) -->
                <div v-if="neuroCanViewSensitive && neuroRisk && riskElevated" class="rounded-xl border-2 px-4 py-3 flex items-start gap-2" :class="[riskStyle.border, riskStyle.bg]">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" :class="riskStyle.text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                    <div>
                        <p class="text-sm font-bold" :class="riskStyle.text">{{ isRtl ? 'تنبيه سلامة المريض' : 'Patient-safety alert' }} — {{ isRtl ? riskStyle.label_ar : riskStyle.label_en }}</p>
                        <p class="text-[11px] mt-0.5" :class="riskStyle.text">{{ neuroRisk.type }} · {{ neuroRisk.tool }} · {{ fmtDate(neuroRisk.assessed_at) }}</p>
                    </div>
                </div>

                <!-- Stat chips -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl p-3 border text-center" :style="`background:${ACCENT}0D; border-color:${ACCENT}26`">
                        <p class="text-base font-extrabold" :style="`color:${ACCENT}`">
                            {{ latestScale ? scaleLabel(latestScale.scale_key) : '—' }}
                            <span v-if="latestScale" class="text-sm">· {{ latestScale.score }}</span>
                        </p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'آخر مقياس' : 'Latest scale' }}</p>
                    </div>
                    <div class="rounded-xl p-3 border text-center bg-slate-50 border-slate-200">
                        <p class="text-lg font-extrabold text-[#1B365D]">{{ activeMeds.length }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'أدوية نشطة' : 'Active meds' }}</p>
                    </div>
                    <div v-if="neuroCanViewSensitive" class="rounded-xl p-3 border text-center" :class="[riskStyle.bg, riskStyle.border]">
                        <p class="text-sm font-bold pt-1" :class="riskStyle.text">{{ neuroRisk ? (isRtl ? riskStyle.label_ar : riskStyle.label_en) : (isRtl ? 'لا يوجد' : 'None') }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'مستوى الخطر' : 'Risk level' }}</p>
                    </div>
                    <div v-else class="rounded-xl p-3 border text-center bg-gray-50 border-gray-200">
                        <svg class="w-4 h-4 mx-auto text-gray-400 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ isRtl ? 'الخطر محجوب' : 'Risk locked' }}</p>
                    </div>
                </div>

                <!-- Measurement-based-care: faceted per-scale trends (CV-5) -->
                <div v-if="hasScaleTrends" class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-bold text-gray-500 uppercase">{{ isRtl ? 'مقاييس القياس المنتظم' : 'Measurement-based care' }}</h4>
                        <Link :href="scalesHref" class="text-[11px] font-medium" :style="`color:${ACCENT}`">{{ isRtl ? 'كل المقاييس' : 'All scales' }}</Link>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-x-5 gap-y-2.5">
                        <div v-for="t in scaleTrends" :key="t.key" class="flex items-center gap-3">
                            <div class="min-w-0 w-20 shrink-0">
                                <p class="text-xs font-bold text-gray-700">{{ t.label }}</p>
                                <p class="text-[10px]" :class="t.improving ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ t.first.score }} → {{ t.latest.score }}<span v-if="t.values.length > 1"> · {{ t.improving ? (isRtl ? 'تحسّن' : '↓') : (isRtl ? 'متابعة' : '↑') }}</span>
                                </p>
                            </div>
                            <div class="flex-1 min-w-0">
                                <Sparkline :values="t.values" :color="ACCENT" :width="140" :height="28" fill />
                            </div>
                            <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full border shrink-0" :class="sevStyle(t.latest.severity)">{{ t.latest.severity || '—' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Encounter note (SOAP / MSE) -->
                <div v-if="neuroEncounter">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-gray-500 uppercase">{{ isRtl ? 'تدوينة اللقاء' : 'Encounter note' }}
                            <span v-if="neuroEncounter.visit_id === visit.id" class="ml-1 normal-case text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :style="`background:${ACCENT}1A; color:${ACCENT}`">{{ isRtl ? 'هذه الزيارة' : 'this visit' }}</span>
                        </h4>
                        <span class="text-[10px] text-gray-400 uppercase">{{ neuroEncounter.note_format }}</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div v-if="neuroEncounter.subjective"><p class="text-[10px] font-bold text-gray-400 uppercase">S</p><p class="text-gray-700 whitespace-pre-wrap">{{ neuroEncounter.subjective }}</p></div>
                        <div v-if="neuroEncounter.objective"><p class="text-[10px] font-bold text-gray-400 uppercase">O</p><p class="text-gray-700 whitespace-pre-wrap">{{ neuroEncounter.objective }}</p></div>
                        <div v-if="neuroEncounter.mse"><p class="text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'الفحص النفسي (MSE)' : 'MSE' }}</p><p class="text-gray-700 whitespace-pre-wrap">{{ neuroEncounter.mse }}</p></div>
                        <div v-if="neuroEncounter.assessment"><p class="text-[10px] font-bold text-gray-400 uppercase">A</p><p class="text-gray-700 whitespace-pre-wrap">{{ neuroEncounter.assessment }}</p></div>
                        <div v-if="neuroEncounter.plan"><p class="text-[10px] font-bold text-gray-400 uppercase">P</p><p class="text-gray-700 whitespace-pre-wrap">{{ neuroEncounter.plan }}</p></div>
                    </div>
                </div>

                <!-- Symptom diaries (CV-4) -->
                <div v-if="hasSeizures || hasHeadaches" class="grid sm:grid-cols-2 gap-4">
                    <div v-if="hasSeizures">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ isRtl ? 'نشاط النوبات (٩٠ يومًا)' : 'Seizure activity (90d)' }}</h4>
                        <CalendarHeatmap :events="seizureEvents" :weeks="13" color="#4F46E5" :is-rtl="isRtl" />
                    </div>
                    <div v-if="hasHeadaches">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ isRtl ? 'شدّة الصداع' : 'Headache intensity' }}</h4>
                        <TrendLine :series="headacheSeries" :is-rtl="isRtl" :y-min="0" :y-max="10" :height="160" :x-label="isRtl ? 'نوبة' : '#'" />
                    </div>
                </div>

                <!-- Active medications -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'الأدوية النشطة' : 'Active Medications' }}</h4>
                    <div v-if="activeMeds.length" class="space-y-1.5">
                        <div v-for="m in activeMeds" :key="m.id" class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="font-semibold text-gray-800 truncate">{{ m.drug }}</span>
                                <span v-if="m.drug_class" class="text-[10px] text-gray-400">{{ m.drug_class }}</span>
                                <span v-if="m.is_controlled" class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-red-100 text-red-700 border border-red-200">{{ isRtl ? 'مُراقَب' : 'Controlled' }}</span>
                            </div>
                            <span class="text-gray-600 shrink-0">{{ m.dose }} · {{ m.frequency }}<span v-if="m.route"> · {{ m.route }}</span></span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد أدوية نشطة' : 'No active medications' }}</p>
                </div>

                <!-- Locked sensitive notice -->
                <div v-if="!neuroCanViewSensitive" class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <p class="text-xs text-gray-500">{{ isRtl ? 'تقييم الخطر وملاحظات العلاج النفسي محجوبة — تتطلب صلاحية البيانات الحسّاسة.' : 'Risk assessment & psychotherapy notes are hidden — require sensitive-data permission.' }}</p>
                </div>

                <!-- Quick links -->
                <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                    <Link :href="encountersHref" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-all" :style="`color:${ACCENT}; background:${ACCENT}0D; border-color:${ACCENT}33`">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        {{ isRtl ? 'اللقاءات' : 'Encounters' }}
                    </Link>
                    <Link :href="medsHref" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        {{ isRtl ? 'الأدوية' : 'Medications' }}
                    </Link>
                </div>
            </template>
        </div>
    </div>
</template>
