<script setup>
/**
 * Sparkline — tiny inline trend line for a single metric (shared chart kit, CV-0).
 * Draw-on animation, optional area fill, last-point dot. No axes/labels.
 */
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    values: { type: Array, default: () => [] }, // array of numbers (oldest → newest)
    color: { type: String, default: '#1B365D' },
    width: { type: Number, default: 120 },
    height: { type: Number, default: 32 },
    fill: { type: Boolean, default: false },
});

const nums = computed(() => props.values.filter(v => v != null && !isNaN(v)).map(Number));
const ok = computed(() => nums.value.length >= 2);

const geom = computed(() => {
    const vs = nums.value;
    const max = Math.max(...vs), min = Math.min(...vs);
    const span = max - min || 1;
    const w = props.width, h = props.height, pad = 3;
    const pts = vs.map((v, i) => {
        const x = pad + (i / (vs.length - 1)) * (w - pad * 2);
        const y = h - pad - ((v - min) / span) * (h - pad * 2);
        return [x, y];
    });
    const line = pts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p[0].toFixed(1)},${p[1].toFixed(1)}`).join(' ');
    const area = `${line} L${pts[pts.length - 1][0].toFixed(1)},${h} L${pts[0][0].toFixed(1)},${h} Z`;
    return { pts, line, area, last: pts[pts.length - 1] };
});

const drawn = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { drawn.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { drawn.value = true; }));
});
</script>

<template>
    <svg v-if="ok" :viewBox="`0 0 ${width} ${height}`" :width="width" :height="height" preserveAspectRatio="none" class="overflow-visible">
        <path v-if="fill" :d="geom.area" :fill="color" opacity="0.08" />
        <path :d="geom.line" fill="none" :stroke="color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            pathLength="1" :style="`stroke-dasharray:1; stroke-dashoffset:${drawn ? 0 : 1}; transition: stroke-dashoffset .9s ease`" />
        <circle :cx="geom.last[0]" :cy="geom.last[1]" r="2.5" :fill="color" :style="`opacity:${drawn ? 1 : 0}; transition: opacity .4s ease .8s`" />
    </svg>
    <span v-else class="text-[10px] text-gray-300">—</span>
</template>
