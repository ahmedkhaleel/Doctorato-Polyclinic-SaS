<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    color: {
        type: String,
        default: '#C4A265',
    },
    height: {
        type: Number,
        default: 200,
    },
    showValues: {
        type: Boolean,
        default: true,
    },
    valuePrefix: {
        type: String,
        default: '',
    },
    valueSuffix: {
        type: String,
        default: '',
    },
});

const barWidth = 32;
const barGap = 16;
const paddingLeft = 10;
const paddingRight = 10;
const paddingTop = 28;
const paddingBottom = 24;

const chartWidth = computed(() => {
    if (!props.data || props.data.length === 0) return 200;
    return paddingLeft + props.data.length * (barWidth + barGap) - barGap + paddingRight;
});

const drawHeight = computed(() => props.height - paddingTop - paddingBottom);

const maxValue = computed(() => {
    if (!props.data || props.data.length === 0) return 0;
    return Math.max(...props.data.map((d) => d.value), 1);
});

const gridLines = computed(() => {
    return [0.25, 0.5, 0.75].map((pct) => ({
        y: paddingTop + drawHeight.value * (1 - pct),
        pct,
    }));
});

const bars = computed(() => {
    if (!props.data || props.data.length === 0) return [];
    return props.data.map((item, i) => {
        const ratio = item.value / maxValue.value;
        const barHeight = ratio * drawHeight.value;
        const x = paddingLeft + i * (barWidth + barGap);
        const y = paddingTop + drawHeight.value - barHeight;
        return {
            x,
            y,
            width: barWidth,
            height: barHeight,
            label: item.label,
            value: item.value,
            formattedValue: `${props.valuePrefix}${item.value}${props.valueSuffix}`,
        };
    });
});
</script>

<template>
    <div class="bar-chart-wrapper">
        <div v-if="!data || data.length === 0" class="bar-chart-empty" :style="{ height: height + 'px' }">
            <span>No data available</span>
        </div>
        <svg
            v-else
            :viewBox="`0 0 ${chartWidth} ${height}`"
            width="100%"
            :preserveAspectRatio="'xMidYMid meet'"
            class="bar-chart-svg"
        >
            <!-- Grid lines -->
            <line
                v-for="(line, idx) in gridLines"
                :key="'grid-' + idx"
                :x1="paddingLeft"
                :y1="line.y"
                :x2="chartWidth - paddingRight"
                :y2="line.y"
                stroke="#e5e7eb"
                stroke-width="0.5"
                stroke-dasharray="3,3"
            />

            <!-- Baseline -->
            <line
                :x1="paddingLeft"
                :y1="paddingTop + drawHeight"
                :x2="chartWidth - paddingRight"
                :y2="paddingTop + drawHeight"
                stroke="#d1d5db"
                stroke-width="0.5"
            />

            <!-- Bars -->
            <g v-for="(bar, idx) in bars" :key="'bar-' + idx" class="bar-group">
                <rect
                    :x="bar.x"
                    :y="bar.y"
                    :width="bar.width"
                    :height="Math.max(bar.height, 0)"
                    :fill="color"
                    rx="3"
                    ry="3"
                    class="bar-rect"
                />
                <!-- Flat bottom edge to override rounded corners at base -->
                <rect
                    v-if="bar.height > 6"
                    :x="bar.x"
                    :y="bar.y + bar.height - 4"
                    :width="bar.width"
                    :height="4"
                    :fill="color"
                    class="bar-rect"
                />

                <!-- Value label above bar -->
                <text
                    v-if="showValues"
                    :x="bar.x + bar.width / 2"
                    :y="bar.y - 6"
                    text-anchor="middle"
                    fill="#374151"
                    font-size="10"
                    font-weight="500"
                >
                    {{ bar.formattedValue }}
                </text>

                <!-- Label below bar -->
                <text
                    :x="bar.x + bar.width / 2"
                    :y="paddingTop + drawHeight + 14"
                    text-anchor="middle"
                    fill="#6b7280"
                    font-size="9"
                >
                    {{ bar.label }}
                </text>
            </g>
        </svg>
    </div>
</template>

<style scoped>
.bar-chart-wrapper {
    width: 100%;
}

.bar-chart-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 0.875rem;
    border: 1px dashed #e5e7eb;
    border-radius: 0.5rem;
}

.bar-chart-svg {
    display: block;
}

.bar-group .bar-rect {
    transition: opacity 0.2s ease;
}

.bar-group:hover .bar-rect {
    opacity: 0.75;
    cursor: pointer;
}
</style>
