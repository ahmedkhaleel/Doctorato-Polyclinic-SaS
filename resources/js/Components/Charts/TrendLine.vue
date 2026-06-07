<script setup>
/**
 * TrendLine — the workhorse clinical time-series chart (shared chart kit, CV-0).
 *
 * Multi-series line chart with an optional shaded normal-range corridor (band),
 * horizontal threshold lines (e.g. 140/90 for pre-eclampsia), draw-on animation,
 * data dots, and a hover crosshair that reads every series at the nearest x.
 *
 * Pure SVG — no chart library. Brand-themed, RTL-aware container (the plot axis
 * stays LTR by data-viz convention), and honours prefers-reduced-motion.
 *
 * Props:
 *   series:      [{ key, label, color, points: [{x, y}] }]
 *   band:        [{ x, low, high }]      optional normal-range corridor (sorted by x)
 *   thresholds:  [{ y, label, color, dashed? }]
 *   yMin/yMax/xMin/xMax: optional fixed domain (auto otherwise)
 *   xLabel/yLabel/unit/height/isRtl
 */
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    series: { type: Array, default: () => [] },
    band: { type: Array, default: () => [] },
    thresholds: { type: Array, default: () => [] },
    yMin: { type: Number, default: null },
    yMax: { type: Number, default: null },
    xMin: { type: Number, default: null },
    xMax: { type: Number, default: null },
    xLabel: { type: String, default: '' },
    yLabel: { type: String, default: '' },
    unit: { type: String, default: '' },
    height: { type: Number, default: 240 },
    isRtl: { type: Boolean, default: true },
});

const W = 640;
const PAD = { l: 46, r: 18, t: 16, b: 30 };
const ph = computed(() => props.height - PAD.t - PAD.b);
const pw = W - PAD.l - PAD.r;

const allPoints = computed(() => props.series.flatMap(s => s.points || []));
const hasData = computed(() => allPoints.value.length >= 1 && props.series.some(s => (s.points || []).length >= 1));

const xDomain = computed(() => {
    const xs = allPoints.value.map(p => p.x);
    let lo = props.xMin ?? Math.min(...xs);
    let hi = props.xMax ?? Math.max(...xs);
    if (lo === hi) { lo -= 1; hi += 1; }
    return [lo, hi];
});
const yDomain = computed(() => {
    const ys = [
        ...allPoints.value.map(p => p.y),
        ...props.band.flatMap(b => [b.low, b.high]),
        ...props.thresholds.map(t => t.y),
    ].filter(v => v != null && !isNaN(v));
    let lo = props.yMin ?? Math.min(...ys);
    let hi = props.yMax ?? Math.max(...ys);
    if (lo === hi) { lo -= 1; hi += 1; }
    const pad = (hi - lo) * 0.08;
    return [props.yMin ?? (lo - pad), props.yMax ?? (hi + pad)];
});

function sx(x) {
    const [lo, hi] = xDomain.value;
    return PAD.l + ((x - lo) / (hi - lo)) * pw;
}
function sy(y) {
    const [lo, hi] = yDomain.value;
    return PAD.t + (1 - (y - lo) / (hi - lo)) * ph.value;
}

const yTicks = computed(() => {
    const [lo, hi] = yDomain.value;
    const n = 4;
    return Array.from({ length: n + 1 }, (_, i) => {
        const v = lo + ((hi - lo) * i) / n;
        return { v, y: sy(v), label: fmt(v) };
    });
});
const xTicks = computed(() => {
    const xs = [...new Set(allPoints.value.map(p => p.x))].sort((a, b) => a - b);
    if (xs.length <= 6) return xs.map(x => ({ x, sx: sx(x) }));
    const step = Math.ceil(xs.length / 6);
    return xs.filter((_, i) => i % step === 0).map(x => ({ x, sx: sx(x) }));
});

function fmt(v) {
    if (v == null) return '';
    return Math.abs(v) >= 100 ? Math.round(v) : Math.round(v * 10) / 10;
}

function linePath(points) {
    const pts = (points || []).filter(p => p.y != null).sort((a, b) => a.x - b.x);
    return pts.map((p, i) => `${i === 0 ? 'M' : 'L'}${sx(p.x).toFixed(1)},${sy(p.y).toFixed(1)}`).join(' ');
}

const bandPath = computed(() => {
    const b = [...props.band].filter(p => p.low != null && p.high != null).sort((a, b) => a.x - b.x);
    if (b.length < 2) return '';
    const top = b.map((p, i) => `${i === 0 ? 'M' : 'L'}${sx(p.x).toFixed(1)},${sy(p.high).toFixed(1)}`).join(' ');
    const bottom = [...b].reverse().map(p => `L${sx(p.x).toFixed(1)},${sy(p.low).toFixed(1)}`).join(' ');
    return `${top} ${bottom} Z`;
});

// ── draw-on animation ──
const drawn = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { drawn.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { drawn.value = true; }));
});

// ── hover crosshair ──
const hoverX = ref(null);
const svgRef = ref(null);
const focus = computed(() => {
    if (hoverX.value == null) return null;
    const xs = [...new Set(allPoints.value.map(p => p.x))].sort((a, b) => a - b);
    if (!xs.length) return null;
    // nearest x in data space
    const [lo, hi] = xDomain.value;
    const xData = lo + ((hoverX.value - PAD.l) / pw) * (hi - lo);
    let nearest = xs[0];
    for (const x of xs) if (Math.abs(x - xData) < Math.abs(nearest - xData)) nearest = x;
    const rows = props.series.map(s => {
        const p = (s.points || []).find(pt => pt.x === nearest);
        return p && p.y != null ? { key: s.key, label: s.label, color: s.color, y: p.y } : null;
    }).filter(Boolean);
    return rows.length ? { x: nearest, px: sx(nearest), rows } : null;
});
function onMove(e) {
    const rect = svgRef.value.getBoundingClientRect();
    const scale = W / rect.width;
    hoverX.value = (e.clientX - rect.left) * scale;
}
function onLeave() { hoverX.value = null; }
</script>

<template>
    <div class="w-full" style="direction: ltr">
        <div v-if="!hasData" class="flex items-center justify-center text-sm text-gray-400" :style="`height:${height}px`">
            {{ isRtl ? 'لا توجد بيانات كافية للرسم' : 'Not enough data to chart' }}
        </div>
        <svg v-else ref="svgRef" :viewBox="`0 0 ${W} ${height}`" class="w-full select-none" :style="`height:${height}px`"
            @mousemove="onMove" @mouseleave="onLeave" role="img"
            :aria-label="(isRtl ? 'رسم بياني: ' : 'Chart: ') + (yLabel || '')">
            <!-- y gridlines + ticks -->
            <g>
                <line v-for="t in yTicks" :key="'g'+t.v" :x1="PAD.l" :x2="W - PAD.r" :y1="t.y" :y2="t.y" stroke="#F1F5F9" stroke-width="1" />
                <text v-for="t in yTicks" :key="'yt'+t.v" :x="PAD.l - 8" :y="t.y + 3" text-anchor="end" class="fill-gray-400" style="font-size:10px">{{ t.label }}</text>
            </g>

            <!-- normal-range corridor -->
            <path v-if="bandPath" :d="bandPath" fill="#10B98114" stroke="#10B98133" stroke-width="1" />

            <!-- thresholds -->
            <g v-for="(th, i) in thresholds" :key="'th'+i">
                <line :x1="PAD.l" :x2="W - PAD.r" :y1="sy(th.y)" :y2="sy(th.y)" :stroke="th.color || '#EF4444'" stroke-width="1.25" :stroke-dasharray="th.dashed === false ? '0' : '5 4'" opacity="0.8" />
                <text v-if="th.label" :x="W - PAD.r" :y="sy(th.y) - 4" text-anchor="end" :fill="th.color || '#EF4444'" style="font-size:9px;font-weight:700">{{ th.label }}</text>
            </g>

            <!-- x ticks -->
            <text v-for="t in xTicks" :key="'xt'+t.x" :x="t.sx" :y="height - 10" text-anchor="middle" class="fill-gray-400" style="font-size:10px">{{ t.x }}</text>

            <!-- hover crosshair -->
            <g v-if="focus">
                <line :x1="focus.px" :x2="focus.px" :y1="PAD.t" :y2="height - PAD.b" stroke="#94A3B8" stroke-width="1" stroke-dasharray="3 3" />
            </g>

            <!-- series -->
            <g v-for="s in series" :key="s.key">
                <path :d="linePath(s.points)" fill="none" :stroke="s.color" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"
                    pathLength="1" :style="`stroke-dasharray:1; stroke-dashoffset:${drawn ? 0 : 1}; transition: stroke-dashoffset 1s ease`" />
                <circle v-for="(p, i) in (s.points || []).filter(pt => pt.y != null)" :key="s.key+i"
                    :cx="sx(p.x)" :cy="sy(p.y)" :r="focus && focus.x === p.x ? 4 : 2.5" :fill="s.color"
                    :style="`opacity:${drawn ? 1 : 0}; transition: opacity .5s ease ${0.4 + i * 0.04}s`">
                    <title>{{ s.label }}: {{ fmt(p.y) }}{{ unit }}</title>
                </circle>
            </g>
        </svg>

        <!-- hover tooltip + legend -->
        <div class="flex items-center justify-between gap-3 mt-1.5 px-1 flex-wrap">
            <div class="flex items-center gap-3 flex-wrap">
                <span v-for="s in series" :key="'lg'+s.key" class="inline-flex items-center gap-1.5 text-[11px] text-gray-500">
                    <span class="w-2.5 h-2.5 rounded-full" :style="`background:${s.color}`"></span>{{ s.label }}
                </span>
            </div>
            <div v-if="focus" class="inline-flex items-center gap-2 text-[11px]">
                <span class="text-gray-400">{{ xLabel }} {{ focus.x }}:</span>
                <span v-for="r in focus.rows" :key="'fr'+r.key" class="font-bold tabular-nums" :style="`color:${r.color}`">{{ fmt(r.y) }}{{ unit }}</span>
            </div>
        </div>
    </div>
</template>
