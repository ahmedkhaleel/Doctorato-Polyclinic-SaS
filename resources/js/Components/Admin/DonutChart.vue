<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    data: { type: Array, required: true },
    size: { type: Number, default: 180 },
    showLegend: { type: Boolean, default: true },
    formatValue: { type: Function, default: null },
    centerLabel: { type: String, default: '' },
});

const hoveredIndex = ref(-1);

const defaultColors = [
    '#C4A265', '#3B82F6', '#10B981', '#F59E0B',
    '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4',
    '#14B8A6', '#F97316', '#6366F1', '#84CC16',
];

const radius = computed(() => props.size / 2 - 10);
const strokeWidth = computed(() => Math.max(radius.value * 0.32, 16));
const innerRadius = computed(() => radius.value - strokeWidth.value / 2);
const circumference = computed(() => 2 * Math.PI * innerRadius.value);
const center = computed(() => props.size / 2);

const total = computed(() => {
    if (!props.data || props.data.length === 0) return 0;
    return props.data.reduce((sum, item) => sum + item.value, 0);
});

const formattedTotal = computed(() => {
    if (props.formatValue) return props.formatValue(total.value);
    if (total.value >= 1000000) return (total.value / 1000000).toFixed(1) + 'M';
    if (total.value >= 1000) return (total.value / 1000).toFixed(1) + 'K';
    return total.value.toLocaleString();
});

const segments = computed(() => {
    if (!props.data || props.data.length === 0 || total.value === 0) return [];
    let cumulativeOffset = 0;
    return props.data.map((item, i) => {
        const ratio = item.value / total.value;
        const dashLength = ratio * circumference.value;
        const gapLength = circumference.value - dashLength;
        const offset = -cumulativeOffset + circumference.value * 0.25;
        const segment = {
            label: item.label,
            value: item.value,
            color: item.color || defaultColors[i % defaultColors.length],
            dasharray: `${dashLength} ${gapLength}`,
            dashoffset: offset,
            percentage: Math.round(ratio * 100),
            formattedValue: props.formatValue ? props.formatValue(item.value) : item.value.toLocaleString(),
        };
        cumulativeOffset += dashLength;
        return segment;
    });
});

const hasData = computed(() => props.data && props.data.length > 0 && total.value > 0);
</script>

<template>
    <div class="donut-wrapper">
        <div v-if="!hasData" class="donut-empty" :style="{ height: size + 'px' }">
            <span>No data available</span>
        </div>
        <div v-else class="donut-layout">
            <!-- SVG Donut -->
            <div class="donut-svg-container" @mouseleave="hoveredIndex = -1">
                <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
                    <!-- Background circle -->
                    <circle :cx="center" :cy="center" :r="innerRadius" fill="none" stroke="#f3f4f6" :stroke-width="strokeWidth" />

                    <!-- Segments -->
                    <circle
                        v-for="(seg, idx) in segments"
                        :key="'seg-' + idx"
                        :cx="center"
                        :cy="center"
                        :r="innerRadius"
                        fill="none"
                        :stroke="seg.color"
                        :stroke-width="hoveredIndex === idx ? strokeWidth + 4 : strokeWidth"
                        :stroke-dasharray="seg.dasharray"
                        :stroke-dashoffset="seg.dashoffset"
                        stroke-linecap="butt"
                        class="donut-segment"
                        @mouseenter="hoveredIndex = idx"
                    />

                    <!-- Center text -->
                    <text :x="center" :y="center - 4" text-anchor="middle" fill="#374151" font-size="18" font-weight="700">
                        {{ hoveredIndex >= 0 ? segments[hoveredIndex]?.percentage + '%' : formattedTotal }}
                    </text>
                    <text :x="center" :y="center + 14" text-anchor="middle" fill="#9ca3af" font-size="10" font-weight="500">
                        {{ hoveredIndex >= 0 ? segments[hoveredIndex]?.label : (centerLabel || 'Total') }}
                    </text>
                </svg>
            </div>

            <!-- Legend -->
            <div v-if="showLegend" class="donut-legend">
                <div
                    v-for="(seg, idx) in segments"
                    :key="'legend-' + idx"
                    class="donut-legend-row"
                    :class="{ 'donut-legend-active': hoveredIndex === idx }"
                    @mouseenter="hoveredIndex = idx"
                    @mouseleave="hoveredIndex = -1"
                >
                    <div class="donut-legend-left">
                        <span class="donut-legend-dot" :style="{ backgroundColor: seg.color }"></span>
                        <span class="donut-legend-label">{{ seg.label }}</span>
                    </div>
                    <div class="donut-legend-right">
                        <span class="donut-legend-pct" :style="{ color: seg.color }">{{ seg.percentage }}%</span>
                        <span class="donut-legend-value">{{ seg.formattedValue }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.donut-wrapper {
    width: 100%;
}

.donut-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 0.875rem;
    border: 1px dashed #e5e7eb;
    border-radius: 0.75rem;
    background: #fafafa;
}

.donut-layout {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

@media (max-width: 640px) {
    .donut-layout {
        flex-direction: column;
        align-items: center;
    }
}

.donut-svg-container {
    flex-shrink: 0;
}

.donut-segment {
    transition: stroke-width 0.25s ease, opacity 0.2s ease;
    cursor: pointer;
}

.donut-segment:hover {
    filter: brightness(1.08);
}

.donut-legend {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.donut-legend-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    border-radius: 8px;
    transition: background-color 0.15s ease;
    cursor: pointer;
    gap: 12px;
}

.donut-legend-row:hover,
.donut-legend-active {
    background-color: #f9fafb;
}

.donut-legend-left {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.donut-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.donut-legend-label {
    color: #6b7280;
    font-size: 0.8125rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.donut-legend-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.donut-legend-pct {
    font-size: 0.75rem;
    font-weight: 700;
    min-width: 32px;
    text-align: right;
}

.donut-legend-value {
    color: #374151;
    font-size: 0.8125rem;
    font-weight: 600;
    min-width: 60px;
    text-align: right;
}
</style>
