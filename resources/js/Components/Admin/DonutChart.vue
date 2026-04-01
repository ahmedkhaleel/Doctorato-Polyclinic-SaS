<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    size: {
        type: Number,
        default: 180,
    },
    showLegend: {
        type: Boolean,
        default: true,
    },
});

const defaultColors = [
    '#C4A265',
    '#3B82F6',
    '#10B981',
    '#F59E0B',
    '#EF4444',
    '#8B5CF6',
    '#EC4899',
    '#06B6D4',
];

const radius = computed(() => props.size / 2 - 10);
const strokeWidth = computed(() => Math.max(radius.value * 0.35, 16));
const innerRadius = computed(() => radius.value - strokeWidth.value / 2);
const circumference = computed(() => 2 * Math.PI * innerRadius.value);
const center = computed(() => props.size / 2);

const total = computed(() => {
    if (!props.data || props.data.length === 0) return 0;
    return props.data.reduce((sum, item) => sum + item.value, 0);
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
        };

        cumulativeOffset += dashLength;
        return segment;
    });
});

const hasData = computed(() => props.data && props.data.length > 0 && total.value > 0);
</script>

<template>
    <div class="donut-chart-wrapper">
        <div v-if="!hasData" class="donut-chart-empty" :style="{ height: size + 'px' }">
            <span>No data available</span>
        </div>
        <div v-else class="donut-chart-container">
            <!-- SVG Donut -->
            <div class="donut-chart-svg-wrap">
                <svg
                    :width="size"
                    :height="size"
                    :viewBox="`0 0 ${size} ${size}`"
                    class="donut-chart-svg"
                >
                    <!-- Background circle -->
                    <circle
                        :cx="center"
                        :cy="center"
                        :r="innerRadius"
                        fill="none"
                        :stroke="'#f3f4f6'"
                        :stroke-width="strokeWidth"
                    />

                    <!-- Segments -->
                    <circle
                        v-for="(seg, idx) in segments"
                        :key="'seg-' + idx"
                        :cx="center"
                        :cy="center"
                        :r="innerRadius"
                        fill="none"
                        :stroke="seg.color"
                        :stroke-width="strokeWidth"
                        :stroke-dasharray="seg.dasharray"
                        :stroke-dashoffset="seg.dashoffset"
                        stroke-linecap="butt"
                        class="donut-segment"
                    />

                    <!-- Center text -->
                    <text
                        :x="center"
                        :y="center - 6"
                        text-anchor="middle"
                        fill="#374151"
                        font-size="22"
                        font-weight="700"
                    >
                        {{ total }}
                    </text>
                    <text
                        :x="center"
                        :y="center + 12"
                        text-anchor="middle"
                        fill="#9ca3af"
                        font-size="11"
                    >
                        Total
                    </text>
                </svg>
            </div>

            <!-- Legend -->
            <div v-if="showLegend" class="donut-chart-legend">
                <div
                    v-for="(seg, idx) in segments"
                    :key="'legend-' + idx"
                    class="donut-legend-item"
                >
                    <span class="donut-legend-dot" :style="{ backgroundColor: seg.color }"></span>
                    <span class="donut-legend-label">{{ seg.label }}</span>
                    <span class="donut-legend-value">{{ seg.value }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.donut-chart-wrapper {
    width: 100%;
}

.donut-chart-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 0.875rem;
    border: 1px dashed #e5e7eb;
    border-radius: 0.5rem;
}

.donut-chart-container {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.donut-chart-svg-wrap {
    flex-shrink: 0;
}

.donut-chart-svg {
    display: block;
}

.donut-segment {
    transition: opacity 0.2s ease;
}

.donut-segment:hover {
    opacity: 0.75;
    cursor: pointer;
}

.donut-chart-legend {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 0;
}

.donut-legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    line-height: 1;
}

.donut-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.donut-legend-label {
    color: #6b7280;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.donut-legend-value {
    color: #374151;
    font-weight: 600;
    flex-shrink: 0;
}
</style>
