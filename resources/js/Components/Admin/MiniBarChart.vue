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
    maxValue: {
        type: Number,
        default: undefined,
    },
});

const computedMax = computed(() => {
    if (props.maxValue !== undefined && props.maxValue > 0) return props.maxValue;
    if (!props.data || props.data.length === 0) return 1;
    return Math.max(...props.data.map((d) => d.value), 1);
});

const rows = computed(() => {
    if (!props.data || props.data.length === 0) return [];
    return props.data.map((item) => ({
        label: item.label,
        value: item.value,
        percentage: Math.min((item.value / computedMax.value) * 100, 100),
    }));
});
</script>

<template>
    <div class="mini-bar-chart">
        <div v-if="!data || data.length === 0" class="mini-bar-empty">
            <span>No data available</span>
        </div>
        <div v-else class="mini-bar-rows">
            <div v-for="(row, idx) in rows" :key="idx" class="mini-bar-row">
                <span class="mini-bar-label">{{ row.label }}</span>
                <div class="mini-bar-track">
                    <div
                        class="mini-bar-fill"
                        :style="{
                            width: row.percentage + '%',
                            backgroundColor: color,
                        }"
                    ></div>
                </div>
                <span class="mini-bar-value">{{ row.value }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mini-bar-chart {
    width: 100%;
}

.mini-bar-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    color: #9ca3af;
    font-size: 0.8125rem;
}

.mini-bar-rows {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.mini-bar-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mini-bar-label {
    width: 80px;
    flex-shrink: 0;
    font-size: 0.8125rem;
    color: #6b7280;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mini-bar-track {
    flex: 1;
    height: 8px;
    background-color: #f3f4f6;
    border-radius: 4px;
    overflow: hidden;
}

.mini-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.4s ease;
    min-width: 2px;
}

.mini-bar-value {
    width: 40px;
    flex-shrink: 0;
    text-align: right;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
}
</style>
