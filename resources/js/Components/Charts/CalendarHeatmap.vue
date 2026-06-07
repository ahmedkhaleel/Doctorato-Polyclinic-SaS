<script setup>
/**
 * CalendarHeatmap — GitHub-style day grid for event frequency (chart kit, CV-0).
 * Aggregates events by day over the last N weeks; cell intensity = count.
 * Used for seizure-frequency / headache / symptom diaries. Animated fade-in,
 * per-cell tooltip, reduced-motion aware. Axis is LTR (weeks → newest right).
 */
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    events: { type: Array, default: () => [] }, // [{ date: 'YYYY-MM-DD' }] (value optional)
    weeks: { type: Number, default: 13 },
    color: { type: String, default: '#0D9488' },
    isRtl: { type: Boolean, default: true },
});

const CELL = 13;
const GAP = 3;
const today = new Date();
today.setHours(0, 0, 0, 0);

// Start at the Sunday on/before (today - weeks*7 + 1).
const startDate = computed(() => {
    const d = new Date(today);
    d.setDate(d.getDate() - (props.weeks * 7 - 1));
    d.setDate(d.getDate() - d.getDay()); // back to Sunday
    return d;
});

const counts = computed(() => {
    const m = {};
    for (const e of props.events) {
        if (!e?.date) continue;
        const key = String(e.date).slice(0, 10);
        m[key] = (m[key] || 0) + (e.value != null ? Number(e.value) : 1);
    }
    return m;
});
const maxCount = computed(() => Math.max(1, ...Object.values(counts.value)));

function iso(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

const grid = computed(() => {
    const cols = [];
    const cur = new Date(startDate.value);
    let week = [];
    while (cur <= today) {
        const key = iso(cur);
        const cnt = counts.value[key] || 0;
        week.push({ key, count: cnt, intensity: cnt === 0 ? 0 : 0.25 + 0.75 * (cnt / maxCount.value), future: false, date: new Date(cur) });
        if (cur.getDay() === 6) { cols.push(week); week = []; }
        cur.setDate(cur.getDate() + 1);
    }
    if (week.length) { while (week.length < 7) week.push(null); cols.push(week); }
    return cols;
});

const width = computed(() => grid.value.length * (CELL + GAP));
const totalEvents = computed(() => Object.values(counts.value).reduce((a, b) => a + b, 0));

const shown = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { shown.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { shown.value = true; }));
});

function tip(cell) {
    if (!cell) return '';
    const d = cell.date.toLocaleDateString(props.isRtl ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short' });
    return `${d}: ${cell.count}`;
}
</script>

<template>
    <div style="direction: ltr">
        <svg :viewBox="`0 0 ${width} ${7 * (CELL + GAP)}`" :width="width" :height="7 * (CELL + GAP)" class="max-w-full">
            <g v-for="(col, ci) in grid" :key="ci">
                <template v-for="(cell, ri) in col" :key="ci + '-' + ri">
                    <rect v-if="cell"
                        :x="ci * (CELL + GAP)" :y="ri * (CELL + GAP)" :width="CELL" :height="CELL" rx="2.5"
                        :fill="cell.count === 0 ? '#F1F5F9' : color"
                        :fill-opacity="cell.count === 0 ? 1 : cell.intensity"
                        :style="`opacity:${shown ? 1 : 0}; transition: opacity .4s ease ${ci * 0.012}s`">
                        <title>{{ tip(cell) }}</title>
                    </rect>
                </template>
            </g>
        </svg>
        <div class="flex items-center justify-between mt-1.5">
            <span class="text-[10px] text-gray-400">{{ isRtl ? `${weeks} أسبوعًا · ${totalEvents} حدثًا` : `${weeks} weeks · ${totalEvents} events` }}</span>
            <span class="inline-flex items-center gap-1 text-[10px] text-gray-400">
                {{ isRtl ? 'أقل' : 'Less' }}
                <span v-for="o in [0, 0.4, 0.7, 1]" :key="o" class="w-2.5 h-2.5 rounded-sm" :style="o === 0 ? 'background:#F1F5F9' : `background:${color};opacity:${o}`"></span>
                {{ isRtl ? 'أكثر' : 'More' }}
            </span>
        </div>
    </div>
</template>
