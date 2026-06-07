<script setup>
/**
 * RadialChart — range-of-motion visual (shared chart kit, PT-3).
 *
 * With ≥3 motions it draws a spider/radar: a reference polygon at the normal
 * ROM (ratio = 1.0) and the patient's active ROM polygon inside it, so deficits
 * read at a glance. With <3 motions it gracefully falls back to horizontal
 * actual-vs-normal comparison bars. Pure SVG, draw-on animation, reduced-motion
 * aware. The geometry stays LTR even under RTL (per the chart-kit rule).
 */
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    // [{ label, value, normal }]
    items: { type: Array, default: () => [] },
    size: { type: Number, default: 240 },
    color: { type: String, default: '#0D9488' },
    isRtl: { type: Boolean, default: false },
});

const rows = computed(() => (props.items || []).filter((d) => d && d.normal > 0));
const isRadar = computed(() => rows.value.length >= 3);

const drawn = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { drawn.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { drawn.value = true; }));
});

// ── Radar geometry ──
const cx = computed(() => props.size / 2);
const cy = computed(() => props.size / 2);
const R = computed(() => props.size * 0.34); // radius for ratio = 1.0
const CAP = 1.15;

function ratio(d) {
    return Math.max(0, Math.min(CAP, (Number(d.value) || 0) / (Number(d.normal) || 1)));
}
function angle(i) {
    return -Math.PI / 2 + (i * 2 * Math.PI) / rows.value.length;
}
function pt(i, rad) {
    const a = angle(i);
    return [cx.value + rad * Math.cos(a), cy.value + rad * Math.sin(a)];
}
const normalPoly = computed(() => rows.value.map((_, i) => pt(i, R.value).join(',')).join(' '));
const valuePoly = computed(() => rows.value.map((d, i) => pt(i, R.value * (drawn.value ? ratio(d) : 0)).join(',')).join(' '));
const axes = computed(() => rows.value.map((d, i) => {
    const [x, y] = pt(i, R.value);
    const [lx, ly] = pt(i, R.value + 16);
    return { x1: cx.value, y1: cy.value, x2: x, y2: y, lx, ly, label: d.label, pct: Math.round(ratio(d) / 1 * 100) };
}));

// ── Bar fallback geometry ──
const bars = computed(() => rows.value.map((d) => ({
    label: d.label,
    value: Number(d.value) || 0,
    normal: Number(d.normal) || 0,
    pct: Math.round(Math.min(1, (Number(d.value) || 0) / (Number(d.normal) || 1)) * 100),
})));
</script>

<template>
    <div v-if="!rows.length" class="text-xs text-gray-400 py-6 text-center">—</div>

    <!-- Radar -->
    <svg v-else-if="isRadar" :viewBox="`0 0 ${size} ${size}`" :width="size" :height="size" dir="ltr" class="mx-auto">
        <!-- rings -->
        <circle v-for="g in [0.5, 1]" :key="g" :cx="cx" :cy="cy" :r="R * g" fill="none" stroke="#EEF2F7" stroke-width="1" />
        <!-- axes -->
        <line v-for="(a, i) in axes" :key="'ax' + i" :x1="a.x1" :y1="a.y1" :x2="a.x2" :y2="a.y2" stroke="#E5E7EB" stroke-width="1" />
        <!-- normal reference polygon -->
        <polygon :points="normalPoly" fill="none" stroke="#CBD5E1" stroke-width="1.5" stroke-dasharray="3 3" />
        <!-- patient polygon -->
        <polygon :points="valuePoly" :fill="color + '33'" :stroke="color" stroke-width="2"
            style="transition: all 1s cubic-bezier(0.16,1,0.3,1)" />
        <!-- labels -->
        <text v-for="(a, i) in axes" :key="'lb' + i" :x="a.lx" :y="a.ly" text-anchor="middle" dominant-baseline="middle"
            class="fill-gray-500" style="font-size:9px">{{ a.label }}</text>
    </svg>

    <!-- Bar fallback -->
    <div v-else class="space-y-2" :dir="isRtl ? 'rtl' : 'ltr'">
        <div v-for="(b, i) in bars" :key="i">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>{{ b.label }}</span>
                <span class="tabular-nums">{{ b.value }}° / {{ b.normal }}°</span>
            </div>
            <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full" :style="{ width: (drawn ? b.pct : 0) + '%', backgroundColor: color, transition: 'width 0.9s cubic-bezier(0.16,1,0.3,1)' }"></div>
            </div>
        </div>
    </div>
</template>
