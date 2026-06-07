<script setup>
/**
 * ProgressRing — circular progress / gauge (shared chart kit, CV-0).
 * Animated arc sweep + center value. Use for session progress, completion %,
 * or a 0–max severity gauge with a custom color.
 */
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    size: { type: Number, default: 96 },
    stroke: { type: Number, default: 9 },
    color: { type: String, default: '#C4A265' },
    track: { type: String, default: '#EEF2F7' },
    label: { type: String, default: '' },
    display: { type: String, default: null }, // override center text (else value/max %)
});

const pct = computed(() => Math.max(0, Math.min(1, (props.value || 0) / (props.max || 1))));
const r = computed(() => (props.size - props.stroke) / 2);
const c = computed(() => 2 * Math.PI * r.value);
const center = computed(() => props.size / 2);

const drawn = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { drawn.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { drawn.value = true; }));
});
const offset = computed(() => (drawn.value ? c.value * (1 - pct.value) : c.value));
const centerText = computed(() => props.display ?? `${Math.round(pct.value * 100)}%`);
</script>

<template>
    <div class="inline-flex flex-col items-center">
        <div class="relative" :style="`width:${size}px;height:${size}px`">
            <svg :viewBox="`0 0 ${size} ${size}`" :width="size" :height="size" class="-rotate-90">
                <circle :cx="center" :cy="center" :r="r" fill="none" :stroke="track" :stroke-width="stroke" />
                <circle :cx="center" :cy="center" :r="r" fill="none" :stroke="color" :stroke-width="stroke" stroke-linecap="round"
                    :stroke-dasharray="c" :stroke-dashoffset="offset" :style="`transition: stroke-dashoffset 1s cubic-bezier(0.16,1,0.3,1)`" />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="font-extrabold tabular-nums" :style="`color:${color}; font-size:${size * 0.24}px`">{{ centerText }}</span>
            </div>
        </div>
        <span v-if="label" class="text-[11px] text-gray-500 mt-1">{{ label }}</span>
    </div>
</template>
