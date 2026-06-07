<script setup>
/**
 * VisitProgressStepper — slim premium stepper showing the visit lifecycle
 * (Waiting → In Progress → Completed), with the connector filled up to the
 * current step and the active step gently pulsing. Cancelled visits show a
 * distinct terminal state. Uses Tailwind transitions only, so it degrades
 * cleanly under prefers-reduced-motion (handled globally in app.css).
 */
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: 'waiting' },
    isRtl: { type: Boolean, default: true },
    startedAt: { type: String, default: null },
    completedAt: { type: String, default: null },
    mounted: { type: Boolean, default: false },
});

const STEPS = [
    { key: 'waiting', ar: 'انتظار', en: 'Waiting' },
    { key: 'in_progress', ar: 'جارية', en: 'In Progress' },
    { key: 'completed', ar: 'مكتملة', en: 'Completed' },
];

const isCancelled = computed(() => props.status === 'cancelled');
const currentIndex = computed(() => {
    const i = STEPS.findIndex(s => s.key === props.status);
    return i === -1 ? 0 : i;
});
// Connector fill (0–100%) up to the current step.
const fillPct = computed(() => (currentIndex.value / (STEPS.length - 1)) * 100);

function stepState(i) {
    if (i < currentIndex.value) return 'done';
    if (i === currentIndex.value) return 'current';
    return 'todo';
}

function fmtTime(d) {
    if (!d) return null;
    return new Date(d).toLocaleTimeString(props.isRtl ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <!-- Cancelled terminal state -->
    <div v-if="isCancelled"
        class="rounded-2xl border border-red-200 bg-red-50/70 px-4 sm:px-6 py-3 flex items-center gap-2"
        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
        style="transition: all 0.5s cubic-bezier(0.16,1,0.3,1); transition-delay: 0.08s">
        <span class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </span>
        <span class="text-sm font-semibold text-red-700">{{ isRtl ? 'تم إلغاء هذه الزيارة' : 'This visit was cancelled' }}</span>
    </div>

    <!-- Active lifecycle stepper -->
    <div v-else
        class="rounded-2xl border border-gray-100 bg-white shadow-sm px-5 sm:px-8 py-4"
        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
        style="transition: all 0.5s cubic-bezier(0.16,1,0.3,1); transition-delay: 0.08s">
        <div class="relative flex items-center justify-between">
            <!-- Track -->
            <div class="absolute top-3.5 h-0.5 bg-gray-100" :style="isRtl ? 'right: 1.25rem; left: 1.25rem' : 'left: 1.25rem; right: 1.25rem'"></div>
            <!-- Progress fill -->
            <div class="absolute top-3.5 h-0.5 bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all duration-700"
                :style="`${isRtl ? 'right' : 'left'}: 1.25rem; width: calc((100% - 2.5rem) * ${mounted ? fillPct : 0} / 100)`"></div>

            <div v-for="(s, i) in STEPS" :key="s.key" class="relative z-10 flex flex-col items-center gap-1.5 flex-1">
                <span class="w-7 h-7 rounded-full flex items-center justify-center border-2 transition-all duration-300"
                    :class="{
                        'bg-emerald-500 border-emerald-500 text-white': stepState(i) === 'done',
                        'bg-[#1B365D] border-[#1B365D] text-white ring-4 ring-[#1B365D]/15 animate-pulse-glow': stepState(i) === 'current',
                        'bg-white border-gray-200 text-gray-300': stepState(i) === 'todo',
                    }">
                    <svg v-if="stepState(i) === 'done'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    <span v-else class="text-[11px] font-bold">{{ i + 1 }}</span>
                </span>
                <span class="text-[11px] font-semibold transition-colors"
                    :class="stepState(i) === 'todo' ? 'text-gray-400' : 'text-gray-700'">{{ isRtl ? s.ar : s.en }}</span>
                <span v-if="s.key === 'in_progress' && startedAt" class="text-[10px] text-gray-400">{{ fmtTime(startedAt) }}</span>
                <span v-else-if="s.key === 'completed' && completedAt" class="text-[10px] text-gray-400">{{ fmtTime(completedAt) }}</span>
            </div>
        </div>
    </div>
</template>
