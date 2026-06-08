<script setup>
import { computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import ProgressRing from '@/Components/Charts/ProgressRing.vue';
import CalendarHeatmap from '@/Components/Charts/CalendarHeatmap.vue';

defineOptions({ layout: PatientLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { lp } = usePatientLocale();
const ACCENT = '#0D9488';
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({
    plan: { type: Object, default: null },
    prescriptions: { type: Array, default: () => [] },
    adherence: { type: Array, default: () => [] },
    recentSessions: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
});

const form = useForm({ prescription_id: null, done: true });
function toggleDone(rx) {
    form.prescription_id = rx.id;
    form.done = !rx.done_today;
    form.post(lp('/physiotherapy/adherence'), { preserveScroll: true });
}
const exName = (ex) => (ex ? (isRtl.value ? ex.name_ar : ex.name_en) : '');
const dateLabel = (d) => (d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short' }) : '');
const doneCount = computed(() => props.prescriptions.filter((r) => r.done_today).length);
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" style="background: linear-gradient(120deg,#1B365D 0%,#155e63 60%,#0D9488 160%)">
            <div class="absolute -top-10 end-0 w-44 h-44 rounded-full opacity-20" style="background:#C4A265"></div>
            <div class="relative z-10 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold">{{ t('My Home Program', 'برنامجي المنزلي') }}</h1>
                    <p class="text-white/80 mt-1 text-sm">{{ t('Daily exercises to keep your recovery on track', 'تمارين يومية للحفاظ على تقدّم تعافيك') }}</p>
                </div>
                <div v-if="prescriptions.length" class="text-center">
                    <p class="text-3xl font-extrabold tabular-nums">{{ doneCount }}/{{ prescriptions.length }}</p>
                    <p class="text-white/70 text-xs">{{ t('done today', 'مكتمل اليوم') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Plan + adherence -->
            <div class="space-y-5">
                <div v-if="plan" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                    <ProgressRing :value="plan.progress_percentage" :max="100" :size="84" :color="ACCENT" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ (isRtl ? plan.title_ar : plan.title_en) || t('Treatment Plan', 'الخطة العلاجية') }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ plan.completed_sessions }}/{{ plan.estimated_sessions }} {{ t('sessions', 'جلسة') }}</p>
                        <p v-if="plan.doctor" class="text-xs text-gray-400 mt-0.5">{{ isRtl ? plan.doctor.name_ar : plan.doctor.name_en }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h2 class="font-semibold text-gray-800 mb-3 text-sm">{{ t('Adherence (12 weeks)', 'الالتزام (12 أسبوع)') }}</h2>
                    <CalendarHeatmap :events="adherence" :weeks="13" :color="ACCENT" :is-rtl="isRtl" />
                </div>

                <div v-if="packages.length" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h2 class="font-semibold text-gray-800 mb-3 text-sm">{{ t('My Packages', 'باقاتي') }}</h2>
                    <div v-for="(p, i) in packages" :key="i" class="flex items-center justify-between text-sm py-1">
                        <span class="text-gray-700">{{ isRtl ? p.name_ar : p.name_en }}</span>
                        <span class="text-xs"><span class="font-bold" :style="{ color: ACCENT }">{{ p.sessions_remaining }}</span> / {{ p.total_sessions }} {{ t('left', 'متبقٍ') }}</span>
                    </div>
                </div>
            </div>

            <!-- Exercises -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t('Prescribed Exercises', 'التمارين الموصوفة') }}</h2>
                <p v-if="!prescriptions.length" class="text-sm text-gray-400 py-10 text-center">{{ t('No exercises prescribed yet.', 'لا توجد تمارين موصوفة بعد.') }}</p>
                <div v-else class="space-y-3">
                    <div v-for="rx in prescriptions" :key="rx.id" class="flex items-center gap-3 p-3 rounded-xl border transition"
                        :class="rx.done_today ? 'border-teal-200 bg-teal-50/50' : 'border-gray-100'">
                        <button @click="toggleDone(rx)" :disabled="form.processing"
                            :aria-label="rx.done_today ? t('Mark not done', 'إلغاء الإتمام') : t('Mark done', 'تحديد كمكتمل')"
                            class="shrink-0 w-9 h-9 rounded-full border-2 flex items-center justify-center transition"
                            :class="rx.done_today ? 'border-teal-500 bg-teal-500 text-white' : 'border-gray-300 text-transparent hover:border-teal-400'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </button>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ exName(rx.exercise) }}</p>
                            <p class="text-xs text-gray-500">
                                <span v-if="rx.sets">{{ rx.sets }} × {{ rx.reps }}</span>
                                <span v-if="rx.hold_sec"> · {{ t('hold', 'ثبات') }} {{ rx.hold_sec }}s</span>
                                <span v-if="rx.frequency"> · {{ rx.frequency }}</span>
                            </p>
                            <p v-if="rx.notes" class="text-[11px] text-gray-400 mt-0.5">{{ rx.notes }}</p>
                        </div>
                        <span class="text-[11px] font-medium" :class="rx.done_today ? 'text-teal-600' : 'text-gray-300'">{{ rx.done_today ? t('Done', 'تم') : t('Tap', 'اضغط') }}</span>
                    </div>
                </div>

                <div v-if="recentSessions.length" class="mt-6 pt-4 border-t border-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ t('Recent Sessions', 'أحدث الجلسات') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="s in recentSessions" :key="s.id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-gray-50 text-gray-600">
                            #{{ s.session_number }} · {{ dateLabel(s.session_date) }}
                            <span v-if="s.pain_before != null && s.pain_after != null" :style="{ color: ACCENT }">{{ s.pain_before }}→{{ s.pain_after }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
