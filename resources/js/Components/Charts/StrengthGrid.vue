<script setup>
/**
 * StrengthGrid — manual-muscle-test (Oxford 0–5) grid (shared chart kit, PT-3).
 *
 * Rows = muscle groups, columns = sides tested (left/right/bilateral). Each cell
 * is colour-scaled from red (0, no contraction) to green (5, normal) so weakness
 * patterns read instantly. Cells stagger-fade in; reduced-motion aware.
 */
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    // [{ muscle_group, side, grade }]
    items: { type: Array, default: () => [] },
    isRtl: { type: Boolean, default: false },
});

const drawn = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    drawn.value = !!reduce;
    if (!reduce) requestAnimationFrame(() => requestAnimationFrame(() => { drawn.value = true; }));
});

const SIDES = ['left', 'right', 'bilateral'];
const sideLabel = (s) => (props.isRtl
    ? ({ left: 'يسار', right: 'يمين', bilateral: 'كلاهما' }[s] || s)
    : ({ left: 'L', right: 'R', bilateral: 'B' }[s] || s));

const groups = computed(() => {
    const present = SIDES.filter((s) => (props.items || []).some((i) => i.side === s));
    const byMuscle = {};
    (props.items || []).forEach((i) => {
        (byMuscle[i.muscle_group] ||= {})[i.side] = i.grade;
    });
    return { sides: present.length ? present : ['right'], rows: Object.entries(byMuscle).map(([muscle, cells]) => ({ muscle, cells })) };
});

// 0 → red, 3 → amber, 5 → green
function gradeColor(g) {
    if (g == null) return '#F1F5F9';
    const scale = ['#DC2626', '#EA580C', '#D97706', '#CA8A04', '#65A30D', '#16A34A'];
    return scale[Math.max(0, Math.min(5, Number(g)))];
}
</script>

<template>
    <div v-if="!groups.rows.length" class="text-xs text-gray-400 py-6 text-center">—</div>
    <table v-else class="w-full text-sm" :dir="isRtl ? 'rtl' : 'ltr'">
        <thead>
            <tr class="text-[11px] text-gray-400">
                <th class="text-start font-medium pb-1"></th>
                <th v-for="s in groups.sides" :key="s" class="font-medium pb-1 text-center w-12">{{ sideLabel(s) }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(row, ri) in groups.rows" :key="row.muscle">
                <td class="py-1 pe-2 text-gray-600 text-xs">{{ row.muscle }}</td>
                <td v-for="s in groups.sides" :key="s" class="py-1 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-white text-xs font-bold tabular-nums"
                        :style="{
                            backgroundColor: gradeColor(row.cells[s]),
                            opacity: drawn ? 1 : 0,
                            transform: drawn ? 'scale(1)' : 'scale(0.6)',
                            transition: `all 0.4s cubic-bezier(0.16,1,0.3,1) ${ri * 60}ms`,
                        }">
                        {{ row.cells[s] != null ? row.cells[s] : '·' }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
</template>
