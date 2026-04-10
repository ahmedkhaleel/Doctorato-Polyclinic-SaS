<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    data: { type: Array, required: true },
    color: { type: String, default: '#C4A265' },
    height: { type: Number, default: 240 },
    showValues: { type: Boolean, default: true },
    valuePrefix: { type: String, default: '' },
    valueSuffix: { type: String, default: '' },
    formatValue: { type: Function, default: null },
});

const hoveredIndex = ref(-1);

const barMinWidth = 36;
const barGap = 6;
const paddingLeft = 50;
const paddingRight = 16;
const paddingTop = 32;
const paddingBottom = 32;

const drawHeight = computed(() => props.height - paddingTop - paddingBottom);

const chartWidth = computed(() => {
    if (!props.data || props.data.length === 0) return 300;
    const contentWidth = props.data.length * (barMinWidth + barGap) - barGap;
    return Math.max(paddingLeft + contentWidth + paddingRight, 300);
});

const maxValue = computed(() => {
    if (!props.data || props.data.length === 0) return 0;
    return Math.max(...props.data.map(d => d.value), 1);
});

const yAxisTicks = computed(() => {
    const max = maxValue.value;
    if (max === 0) return [];
    const step = niceStep(max, 4);
    const ticks = [];
    for (let v = 0; v <= max * 1.1; v += step) {
        ticks.push(v);
    }
    if (ticks.length > 6) return ticks.slice(0, 6);
    return ticks;
});

function niceStep(max, targetTicks) {
    const rough = max / targetTicks;
    const mag = Math.pow(10, Math.floor(Math.log10(rough)));
    const residual = rough / mag;
    let nice;
    if (residual <= 1.5) nice = 1;
    else if (residual <= 3) nice = 2;
    else if (residual <= 7) nice = 5;
    else nice = 10;
    return nice * mag;
}

function formatAxisValue(v) {
    if (v >= 1000000) return (v / 1000000).toFixed(1) + 'M';
    if (v >= 1000) return (v / 1000).toFixed(v >= 10000 ? 0 : 1) + 'K';
    return Math.round(v).toString();
}

const bars = computed(() => {
    if (!props.data || props.data.length === 0) return [];
    const yMax = yAxisTicks.value.length ? yAxisTicks.value[yAxisTicks.value.length - 1] : maxValue.value;
    return props.data.map((item, i) => {
        const ratio = item.value / (yMax || 1);
        const barHeight = Math.max(ratio * drawHeight.value, 0);
        const x = paddingLeft + i * (barMinWidth + barGap);
        const y = paddingTop + drawHeight.value - barHeight;
        const formatted = props.formatValue
            ? props.formatValue(item.value)
            : `${props.valuePrefix}${item.value.toLocaleString()}${props.valueSuffix}`;
        return { x, y, width: barMinWidth, height: barHeight, label: item.label, value: item.value, formattedValue: formatted };
    });
});

const gridLines = computed(() => {
    const yMax = yAxisTicks.value.length ? yAxisTicks.value[yAxisTicks.value.length - 1] : maxValue.value;
    return yAxisTicks.value.map(v => ({
        y: paddingTop + drawHeight.value - (v / (yMax || 1)) * drawHeight.value,
        value: v,
        label: formatAxisValue(v),
    }));
});

function lightenColor(hex, amount = 0.15) {
    const num = parseInt(hex.replace('#', ''), 16);
    const r = Math.min(255, (num >> 16) + Math.round(255 * amount));
    const g = Math.min(255, ((num >> 8) & 0x00FF) + Math.round(255 * amount));
    const b = Math.min(255, (num & 0x0000FF) + Math.round(255 * amount));
    return `rgb(${r},${g},${b})`;
}
</script>

<template>
    <div class="bar-chart-wrapper">
        <div v-if="!data || data.length === 0" class="bar-chart-empty" :style="{ height: height + 'px' }">
            <span>No data available</span>
        </div>
        <div v-else class="bar-chart-scroll" :style="{ maxHeight: (height + 20) + 'px' }">
            <svg
                :viewBox="`0 0 ${chartWidth} ${height}`"
                :width="chartWidth"
                :height="height"
                class="bar-chart-svg"
            >
                <defs>
                    <linearGradient :id="'barGrad'" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" :stop-color="color" />
                        <stop offset="100%" :stop-color="color" stop-opacity="0.7" />
                    </linearGradient>
                    <linearGradient :id="'barGradHover'" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" :stop-color="lightenColor(color, 0.1)" />
                        <stop offset="100%" :stop-color="color" />
                    </linearGradient>
                </defs>

                <!-- Y-axis grid lines -->
                <g v-for="(line, idx) in gridLines" :key="'grid-' + idx">
                    <line
                        :x1="paddingLeft - 4"
                        :y1="line.y"
                        :x2="chartWidth - paddingRight"
                        :y2="line.y"
                        stroke="#e5e7eb"
                        stroke-width="0.5"
                        :stroke-dasharray="line.value === 0 ? 'none' : '4,4'"
                    />
                    <text
                        :x="paddingLeft - 8"
                        :y="line.y + 3.5"
                        text-anchor="end"
                        fill="#9ca3af"
                        font-size="10"
                        font-weight="500"
                    >{{ line.label }}</text>
                </g>

                <!-- Bars -->
                <g v-for="(bar, idx) in bars" :key="'bar-' + idx"
                   class="bar-group"
                   @mouseenter="hoveredIndex = idx"
                   @mouseleave="hoveredIndex = -1">

                    <!-- Hover background -->
                    <rect
                        v-if="hoveredIndex === idx"
                        :x="bar.x - 3"
                        :y="paddingTop"
                        :width="bar.width + 6"
                        :height="drawHeight"
                        fill="#f9fafb"
                        rx="4"
                    />

                    <!-- Bar -->
                    <rect
                        :x="bar.x"
                        :y="bar.y"
                        :width="bar.width"
                        :height="Math.max(bar.height, 2)"
                        :fill="hoveredIndex === idx ? `url(#barGradHover)` : `url(#barGrad)`"
                        rx="4"
                        ry="4"
                        class="bar-rect"
                    />
                    <!-- Flat bottom -->
                    <rect
                        v-if="bar.height > 8"
                        :x="bar.x"
                        :y="bar.y + bar.height - 4"
                        :width="bar.width"
                        :height="4"
                        :fill="hoveredIndex === idx ? `url(#barGradHover)` : `url(#barGrad)`"
                    />

                    <!-- Value label on hover -->
                    <g v-if="showValues && hoveredIndex === idx">
                        <rect
                            :x="bar.x + bar.width / 2 - 30"
                            :y="bar.y - 24"
                            width="60"
                            height="18"
                            rx="4"
                            fill="#1f2937"
                            opacity="0.9"
                        />
                        <text
                            :x="bar.x + bar.width / 2"
                            :y="bar.y - 12"
                            text-anchor="middle"
                            fill="white"
                            font-size="9"
                            font-weight="600"
                        >{{ bar.formattedValue }}</text>
                    </g>

                    <!-- Label below bar -->
                    <text
                        :x="bar.x + bar.width / 2"
                        :y="paddingTop + drawHeight + 16"
                        text-anchor="middle"
                        :fill="hoveredIndex === idx ? '#374151' : '#9ca3af'"
                        font-size="9"
                        :font-weight="hoveredIndex === idx ? '600' : '400'"
                        class="bar-label"
                    >{{ bar.label }}</text>
                </g>
            </svg>
        </div>
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
    border-radius: 0.75rem;
    background: #fafafa;
}

.bar-chart-scroll {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: thin;
    scrollbar-color: #d1d5db transparent;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 4px;
}

.bar-chart-scroll::-webkit-scrollbar {
    height: 6px;
}

.bar-chart-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.bar-chart-scroll::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.bar-chart-svg {
    display: block;
    min-width: 100%;
}

.bar-group {
    cursor: pointer;
}

.bar-rect {
    transition: all 0.2s ease;
}

.bar-label {
    transition: all 0.15s ease;
}
</style>
