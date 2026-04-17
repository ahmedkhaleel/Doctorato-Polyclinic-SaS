<script setup>
import { ref, computed } from 'vue';
import { getWhoReferenceData } from '@/utils/whoReferenceData';
import { useLocale } from '@/Composables/useLocale';

const { locale, isRtl } = useLocale();

const props = defineProps({
    records: {
        type: Array,
        default: () => [],
    },
    gender: {
        type: String,
        default: 'male',
    },
    initialMetric: {
        type: String,
        default: 'weight',
    },
    showWhoLinesDefault: {
        type: Boolean,
        default: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    height: {
        type: Number,
        default: 320,
    },
});

const activeMetric = ref(props.initialMetric || 'weight');
const showWhoLines = ref(props.showWhoLinesDefault !== false);

const metrics = [
    { key: 'weight', labelAr: 'الوزن',         labelEn: 'Weight', unit: 'kg', field: 'weight_kg' },
    { key: 'height', labelAr: 'الطول',         labelEn: 'Height', unit: 'cm', field: 'height_cm' },
    { key: 'head',   labelAr: 'محيط الرأس',    labelEn: 'Head',   unit: 'cm', field: 'head_circumference_cm' },
    { key: 'bmi',    labelAr: 'مؤشر الكتلة',   labelEn: 'BMI',    unit: '',   field: 'bmi' },
];

const sortedRecords = computed(() =>
    [...(props.records || [])].sort(
        (a, b) => new Date(a.measurement_date) - new Date(b.measurement_date)
    )
);

const currentField = computed(
    () => metrics.find((m) => m.key === activeMetric.value)?.field
);

const whoData = computed(() => getWhoReferenceData(activeMetric.value, props.gender));

// Chart dimensions
const padding = { top: 20, right: 20, bottom: 40, left: 50 };
const width = 700;
const chartHeight = computed(() => props.height || 320);

// Scale: age on x-axis (0 to max age in months, or 60)
const maxAge = computed(() => {
    const records = sortedRecords.value;
    const recordMax = records.length
        ? Math.max(...records.map((r) => Number(r.age_months) || 0))
        : 0;
    const whoMax = whoData.value.length > 0
        ? whoData.value[whoData.value.length - 1].age
        : 60;
    return Math.max(recordMax + 3, Math.min(whoMax, 60));
});

// Y-axis scale
const yMax = computed(() => {
    const values = [];
    sortedRecords.value.forEach((r) => {
        const v = r[currentField.value];
        if (v !== null && v !== undefined && v !== '') values.push(parseFloat(v));
    });
    whoData.value.forEach((p) => values.push(p.p97));
    const max = values.length ? Math.max(...values) : 10;
    return Math.ceil(max * 1.1);
});

const yMin = computed(() => {
    const values = [];
    whoData.value.forEach((p) => values.push(p.p3));
    sortedRecords.value.forEach((r) => {
        const v = r[currentField.value];
        if (v !== null && v !== undefined && v !== '') values.push(parseFloat(v));
    });
    if (!values.length) return 0;
    return Math.max(0, Math.floor(Math.min(...values) * 0.8));
});

function xScale(age) {
    return padding.left + (age / maxAge.value) * (width - padding.left - padding.right);
}

function yScale(value) {
    const h = chartHeight.value - padding.top - padding.bottom;
    const range = yMax.value - yMin.value || 1;
    return padding.top + h - ((value - yMin.value) / range) * h;
}

// Generate path for WHO percentile line
function whoPathD(percentileKey) {
    if (!whoData.value.length) return '';
    return whoData.value
        .filter((p) => p.age <= maxAge.value)
        .map(
            (p, i) =>
                `${i === 0 ? 'M' : 'L'}${xScale(p.age).toFixed(1)},${yScale(p[percentileKey]).toFixed(1)}`
        )
        .join(' ');
}

// Which percentile key to read from the record
function percentileKeyForMetric(metric) {
    if (metric === 'weight') return 'weight_percentile';
    if (metric === 'height') return 'height_percentile';
    if (metric === 'head')   return 'head_percentile';
    if (metric === 'bmi')    return 'bmi_percentile';
    return null;
}

// Patient data points
const patientPoints = computed(() =>
    sortedRecords.value
        .filter((r) => {
            const v = r[currentField.value];
            return v !== null && v !== undefined && v !== '';
        })
        .map((r) => {
            const pKey = percentileKeyForMetric(activeMetric.value);
            return {
                x: xScale(Number(r.age_months) || 0),
                y: yScale(parseFloat(r[currentField.value])),
                value: parseFloat(r[currentField.value]),
                date: r.measurement_date,
                age: Number(r.age_months) || 0,
                percentile: pKey ? r[pKey] : null,
            };
        })
);

const patientLineD = computed(() => {
    if (patientPoints.value.length < 2) return '';
    return patientPoints.value
        .map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x.toFixed(1)},${p.y.toFixed(1)}`)
        .join(' ');
});

// Hover tooltip
const hoveredPoint = ref(null);
function showTooltip(point) {
    hoveredPoint.value = point;
}
function hideTooltip() {
    hoveredPoint.value = null;
}

// Y-axis ticks
const yTicks = computed(() => {
    const count = 5;
    const step = (yMax.value - yMin.value) / count;
    return Array.from({ length: count + 1 }, (_, i) => yMin.value + i * step);
});

// X-axis ticks (age in months)
const xTicks = computed(() => {
    const interval = maxAge.value > 36 ? 12 : 6;
    const ticks = [];
    for (let i = 0; i <= maxAge.value; i += interval) ticks.push(i);
    return ticks;
});

function formatValue(v) {
    if (activeMetric.value === 'bmi') return v.toFixed(1);
    if (activeMetric.value === 'height') return v.toFixed(1);
    return v.toFixed(2);
}

function formatDate(d) {
    try {
        return new Date(d).toLocaleDateString('en-GB');
    } catch (e) {
        return d;
    }
}
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-100 p-4 md:p-6">
        <!-- Header with controls (hidden if compact) -->
        <div v-if="!compact" class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <!-- Metric tabs -->
            <div class="flex gap-1 p-1 bg-gray-50 rounded-xl">
                <button
                    v-for="m in metrics"
                    :key="m.key"
                    type="button"
                    @click="activeMetric = m.key"
                    :class="[
                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition',
                        activeMetric === m.key
                            ? 'bg-white text-emerald-700 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700',
                    ]"
                >
                    {{ locale === 'ar' ? m.labelAr : m.labelEn }}
                </button>
            </div>
            <!-- WHO lines toggle -->
            <button
                type="button"
                @click="showWhoLines = !showWhoLines"
                :class="[
                    'flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg border transition',
                    showWhoLines
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : 'bg-gray-50 text-gray-500 border-gray-200',
                ]"
            >
                <span class="w-2 h-2 rounded-full" :class="showWhoLines ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                WHO
            </button>
        </div>

        <!-- Empty state -->
        <div
            v-if="!sortedRecords.length"
            class="flex flex-col items-center justify-center py-12 text-gray-400"
        >
            <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M3 3v18h18M7 14l4-4 4 4 4-8"
                />
            </svg>
            <p class="text-sm">{{ isRtl ? 'لا توجد قياسات بعد' : 'No measurements yet' }}</p>
        </div>

        <!-- SVG Chart -->
        <div v-else class="relative">
            <svg
                :viewBox="`0 0 ${width} ${chartHeight}`"
                class="w-full h-auto"
                preserveAspectRatio="xMidYMid meet"
            >
                <!-- Grid lines -->
                <g class="grid">
                    <line
                        v-for="(tick, i) in yTicks"
                        :key="'g' + i"
                        :x1="padding.left"
                        :x2="width - padding.right"
                        :y1="yScale(tick)"
                        :y2="yScale(tick)"
                        stroke="#e5e7eb"
                        stroke-width="0.5"
                        stroke-dasharray="2,3"
                    />
                </g>

                <!-- WHO percentile lines -->
                <g v-if="showWhoLines">
                    <path
                        :d="whoPathD('p3')"
                        fill="none"
                        stroke="#ef4444"
                        stroke-width="1"
                        stroke-dasharray="4,3"
                        opacity="0.6"
                    />
                    <path
                        :d="whoPathD('p15')"
                        fill="none"
                        stroke="#f59e0b"
                        stroke-width="1"
                        stroke-dasharray="3,2"
                        opacity="0.5"
                    />
                    <path
                        :d="whoPathD('p50')"
                        fill="none"
                        stroke="#10b981"
                        stroke-width="1.5"
                        opacity="0.8"
                    />
                    <path
                        :d="whoPathD('p85')"
                        fill="none"
                        stroke="#f59e0b"
                        stroke-width="1"
                        stroke-dasharray="3,2"
                        opacity="0.5"
                    />
                    <path
                        :d="whoPathD('p97')"
                        fill="none"
                        stroke="#ef4444"
                        stroke-width="1"
                        stroke-dasharray="4,3"
                        opacity="0.6"
                    />
                </g>

                <!-- Patient line -->
                <path
                    v-if="patientLineD"
                    :d="patientLineD"
                    fill="none"
                    stroke="#1B365D"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />

                <!-- Patient points -->
                <g>
                    <circle
                        v-for="(p, i) in patientPoints"
                        :key="'p' + i"
                        :cx="p.x"
                        :cy="p.y"
                        r="5"
                        fill="#C4A265"
                        stroke="white"
                        stroke-width="2"
                        class="cursor-pointer transition-all"
                        @mouseenter="showTooltip(p)"
                        @mouseleave="hideTooltip"
                    />
                </g>

                <!-- X-axis -->
                <line
                    :x1="padding.left"
                    :x2="width - padding.right"
                    :y1="chartHeight - padding.bottom"
                    :y2="chartHeight - padding.bottom"
                    stroke="#9ca3af"
                    stroke-width="1"
                />
                <text
                    v-for="(tick, i) in xTicks"
                    :key="'xt' + i"
                    :x="xScale(tick)"
                    :y="chartHeight - padding.bottom + 18"
                    text-anchor="middle"
                    fill="#6b7280"
                    font-size="10"
                >
                    {{ tick }}
                </text>
                <text
                    :x="width / 2"
                    :y="chartHeight - 5"
                    text-anchor="middle"
                    fill="#9ca3af"
                    font-size="10"
                    font-weight="600"
                >
                    {{ isRtl ? 'العمر (بالأشهر)' : 'Age (months)' }}
                </text>

                <!-- Y-axis -->
                <line
                    :x1="padding.left"
                    :x2="padding.left"
                    :y1="padding.top"
                    :y2="chartHeight - padding.bottom"
                    stroke="#9ca3af"
                    stroke-width="1"
                />
                <text
                    v-for="(tick, i) in yTicks"
                    :key="'yt' + i"
                    :x="padding.left - 8"
                    :y="yScale(tick) + 3"
                    text-anchor="end"
                    fill="#6b7280"
                    font-size="10"
                >
                    {{ tick.toFixed(activeMetric === 'bmi' ? 1 : 0) }}
                </text>

                <!-- Gradient defs -->
                <defs>
                    <linearGradient id="dangerGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#ef4444" />
                        <stop offset="100%" stop-color="#ef4444" stop-opacity="0" />
                    </linearGradient>
                </defs>
            </svg>

            <!-- Tooltip -->
            <div
                v-if="hoveredPoint"
                class="absolute pointer-events-none bg-[#1B365D] text-white text-xs px-3 py-2 rounded-lg shadow-lg z-10"
                :style="{
                    left: (hoveredPoint.x / width * 100) + '%',
                    top: (hoveredPoint.y / chartHeight * 100) + '%',
                    transform: 'translate(-50%, -120%)',
                }"
            >
                <div class="font-bold">
                    {{ formatValue(hoveredPoint.value) }}
                    {{ metrics.find((m) => m.key === activeMetric)?.unit }}
                </div>
                <div class="opacity-75 text-[10px]">
                    {{ hoveredPoint.age }} {{ isRtl ? 'شهر' : 'mo' }} ·
                    {{ formatDate(hoveredPoint.date) }}
                </div>
                <div
                    v-if="hoveredPoint.percentile"
                    class="opacity-90 text-[10px] mt-0.5 text-[#C4A265]"
                >
                    P{{ parseFloat(hoveredPoint.percentile).toFixed(0) }}
                </div>
            </div>
        </div>

        <!-- Legend (hidden if compact) -->
        <div
            v-if="!compact && showWhoLines && sortedRecords.length"
            class="flex flex-wrap items-center justify-center gap-3 mt-4 text-[10px] text-gray-500"
        >
            <div class="flex items-center gap-1">
                <span class="w-4 h-0.5 bg-red-400 rounded"></span> 3% · 97%
            </div>
            <div class="flex items-center gap-1">
                <span class="w-4 h-0.5 bg-amber-400 rounded"></span> 15% · 85%
            </div>
            <div class="flex items-center gap-1">
                <span class="w-4 h-0.5 bg-emerald-500 rounded"></span>
                50% ({{ isRtl ? 'المتوسط' : 'Median' }})
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-[#C4A265] border-2 border-white shadow-sm"></span>
                {{ isRtl ? 'المريض' : 'Patient' }}
            </div>
        </div>
    </div>
</template>
