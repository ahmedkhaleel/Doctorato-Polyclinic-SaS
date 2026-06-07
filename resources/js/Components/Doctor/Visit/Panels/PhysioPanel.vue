<script setup>
/**
 * PhysioPanel — physiotherapy clinical cockpit for the visit page (doctor + admin).
 * Surfaces the active plan progress (ProgressRing), the post-session pain trend
 * (TrendLine), the latest assessment's ROM radar (RadialChart) + muscle-strength
 * grid (StrengthGrid), and the body-map pain points (BodyMap). Read-only; deep
 * links route to the doctor patient file (or the admin patient file in admin context).
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ProgressRing from '@/Components/Charts/ProgressRing.vue';
import TrendLine from '@/Components/Charts/TrendLine.vue';
import RadialChart from '@/Components/Charts/RadialChart.vue';
import StrengthGrid from '@/Components/Charts/StrengthGrid.vue';
import BodyMap from '@/Components/Charts/BodyMap.vue';

const props = defineProps({
    visit: { type: Object, required: true },
    isRtl: { type: Boolean, default: true },
    mounted: { type: Boolean, default: false },
    context: { type: String, default: 'doctor' },
    patientHref: { type: String, default: null },
    physioActivePlan: { type: Object, default: null },
    physioSessions: { type: Array, default: () => [] },
    physioRom: { type: Array, default: () => [] },
    physioStrength: { type: Array, default: () => [] },
    physioPainPoints: { type: Array, default: () => [] },
    physioAssessment: { type: Object, default: null },
    physioScales: { type: Array, default: () => [] },
});

const PROM_LABELS = { odi: 'ODI', ndi: 'NDI', lefs: 'LEFS' };
const latestScales = computed(() => {
    const seen = {};
    (props.physioScales || []).forEach((s) => { if (!seen[s.scale_key]) seen[s.scale_key] = s; });
    return Object.values(seen);
});

const ACCENT = '#0D9488';
const t = (en, ar) => (props.isRtl ? ar : en);
const isAdmin = computed(() => props.context === 'admin');
const fileHref = computed(() => (isAdmin.value ? (props.patientHref || '#') : `/doctor/physiotherapy/patients/${props.visit.patient_id}`));

const hasData = computed(() => props.physioActivePlan || props.physioSessions.length || props.physioRom.length || props.physioStrength.length || props.physioPainPoints.length || props.physioScales.length);

// Post-session pain trend (oldest → newest).
const painSeries = computed(() => {
    const pts = (props.physioSessions || [])
        .filter((s) => s.pain_after != null)
        .slice().reverse()
        .map((s, i) => ({ x: i + 1, y: Number(s.pain_after) }));
    return pts.length >= 2 ? [{ key: 'pain', label: t('Pain (post-session)', 'الألم بعد الجلسة'), color: '#EF4444', points: pts }] : [];
});

// ROM radar items.
const romItems = computed(() => (props.physioRom || []).map((r) => ({
    label: `${r.motion}`.slice(0, 8),
    value: Number(r.arom || 0),
    normal: Number(r.normal_ref || 0),
})).filter((r) => r.normal > 0));

const painFront = computed(() => (props.physioPainPoints || []).filter((p) => p.view === 'front').map((p) => ({ ...p, lesion_type: p.pain_type })));
const painBack = computed(() => (props.physioPainPoints || []).filter((p) => p.view === 'back').map((p) => ({ ...p, lesion_type: p.pain_type })));
const dateLabel = (d) => (d ? new Date(d).toLocaleDateString(props.isRtl ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short' }) : '');
</script>

<template>
    <div v-if="hasData" class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5" :style="{ color: ACCENT }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12h4l3 8 4-16 3 8h4"/></svg>
                {{ t('Physiotherapy', 'العلاج الطبيعي') }}
            </h3>
            <Link :href="fileHref" class="text-xs font-medium hover:underline" :style="{ color: ACCENT }">{{ t('Full file', 'الملف الكامل') }} →</Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Plan progress + pain trend -->
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div v-if="physioActivePlan" class="flex items-center gap-4">
                    <ProgressRing :value="physioActivePlan.progress_percentage" :max="100" :size="84" :color="ACCENT" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ (isRtl ? physioActivePlan.title_ar : physioActivePlan.title_en) || t('Active Plan', 'خطة نشطة') }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ physioActivePlan.completed_sessions }}/{{ physioActivePlan.estimated_sessions }} {{ t('sessions', 'جلسة') }} · {{ physioActivePlan.frequency }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ t('Remaining', 'متبقٍ') }}: {{ physioActivePlan.sessions_remaining }}</p>
                    </div>
                </div>
                <p v-else class="text-xs text-gray-400 text-center py-4">{{ t('No active plan', 'لا توجد خطة نشطة') }}</p>

                <div v-if="painSeries.length" class="mt-4 pt-4 border-t border-gray-50">
                    <p class="text-xs text-gray-500 mb-1">{{ t('Pain trend', 'مسار الألم') }}</p>
                    <TrendLine :series="painSeries" :is-rtl="isRtl" :height="140" :y-min="0" :y-max="10" />
                </div>
            </div>

            <!-- ROM radar -->
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs font-medium text-gray-600 mb-2">{{ t('Range of Motion', 'المدى الحركي') }}</p>
                <RadialChart v-if="romItems.length" :items="romItems" :size="220" :color="ACCENT" :is-rtl="isRtl" />
                <p v-else class="text-xs text-gray-400 text-center py-6">—</p>
            </div>

            <!-- Strength grid -->
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs font-medium text-gray-600 mb-2">{{ t('Muscle Strength (0–5)', 'قوة العضلات (0–5)') }}</p>
                <StrengthGrid :items="physioStrength" :is-rtl="isRtl" />
            </div>

            <!-- Pain map -->
            <div v-if="physioPainPoints.length" class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs font-medium text-gray-600 mb-2">{{ t('Pain Map', 'خريطة الألم') }}</p>
                <div class="flex justify-center gap-4">
                    <BodyMap v-if="painFront.length" :lesions="painFront" view="front" :accent="'#EF4444'" :is-rtl="isRtl" :height="220" />
                    <BodyMap v-if="painBack.length" :lesions="painBack" view="back" :accent="'#EF4444'" :is-rtl="isRtl" :height="220" />
                </div>
            </div>
        </div>

        <!-- Latest PROMs -->
        <div v-if="latestScales.length" class="bg-white rounded-xl border border-gray-100 p-4">
            <p class="text-xs font-medium text-gray-600 mb-2">{{ t('Outcome Measures', 'مقاييس النتائج') }}</p>
            <div class="flex flex-wrap gap-2">
                <span v-for="s in latestScales" :key="s.scale_key" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-gray-50">
                    <span class="font-semibold" :style="{ color: ACCENT }">{{ PROM_LABELS[s.scale_key] || s.scale_key }}</span>
                    <span class="text-gray-700 tabular-nums">{{ s.score }}</span>
                    <span class="text-gray-400">{{ s.severity }}</span>
                </span>
            </div>
        </div>

        <!-- Recent sessions -->
        <div v-if="physioSessions.length" class="bg-white rounded-xl border border-gray-100 p-4">
            <p class="text-xs font-medium text-gray-600 mb-2">{{ t('Recent Sessions', 'أحدث الجلسات') }}</p>
            <div class="flex flex-wrap gap-2">
                <span v-for="s in physioSessions.slice(0, 8)" :key="s.id"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-gray-50 text-gray-600">
                    <span class="text-gray-400">#{{ s.session_number }}</span>
                    {{ dateLabel(s.session_date) }}
                    <span v-if="s.pain_before != null && s.pain_after != null" class="font-medium" :style="{ color: ACCENT }">{{ s.pain_before }}→{{ s.pain_after }}</span>
                </span>
            </div>
        </div>
    </div>
</template>
