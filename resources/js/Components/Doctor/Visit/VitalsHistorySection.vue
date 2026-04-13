<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    vitalsHistory: {
        type: Array,
        default: () => [],
    },
    isRtl: {
        type: Boolean,
        default: false,
    },
    visit: {
        type: Object,
        required: true,
    },
    mounted: {
        type: Boolean,
        default: false,
    },
});

// ─── Internal State ─────────────────────────────────
const showVitalsHistory = ref(false);
const selectedVitalMetric = ref('bp');
const vitalsHistoryAnimated = ref(false);

// ─── Computed ───────────────────────────────────────
const vitalMetrics = computed(() => [
    { key: 'bp', label: props.isRtl ? '\u0636\u063a\u0637 \u0627\u0644\u062f\u0645' : 'Blood Pressure', icon: '\uD83E\uDEC0', color: '#EF4444', unit: 'mmHg' },
    { key: 'hr', label: props.isRtl ? '\u0627\u0644\u0646\u0628\u0636' : 'Heart Rate', icon: '\uD83D\uDC93', color: '#EC4899', unit: 'bpm' },
    { key: 'temp', label: props.isRtl ? '\u0627\u0644\u062d\u0631\u0627\u0631\u0629' : 'Temperature', icon: '\uD83C\uDF21\uFE0F', color: '#F59E0B', unit: '\u00B0C' },
    { key: 'spo2', label: 'SpO2', icon: '\uD83E\uDEC1', color: '#3B82F6', unit: '%' },
    { key: 'sugar', label: props.isRtl ? '\u0627\u0644\u0633\u0643\u0631' : 'Blood Sugar', icon: '\uD83E\uDE78', color: '#8B5CF6', unit: 'mg/dL' },
    { key: 'weight', label: props.isRtl ? '\u0627\u0644\u0648\u0632\u0646' : 'Weight', icon: '\u2696\uFE0F', color: '#10B981', unit: 'kg' },
]);

const historyData = computed(() => props.vitalsHistory || []);
const hasHistory = computed(() => historyData.value.length > 1);

// Normal ranges for reference lines
const normalRanges = {
    bp: { low: 90, high: 140, label: '90-140' },
    hr: { low: 60, high: 100, label: '60-100' },
    temp: { low: 36.1, high: 37.5, label: '36.1-37.5' },
    spo2: { low: 95, high: 100, label: '95-100' },
    sugar: { low: 70, high: 140, label: '70-140' },
    weight: { low: 0, high: 999, label: '' },
};

// ─── Functions ──────────────────────────────────────

function getChartPoints(metric) {
    const data = historyData.value.filter(v => {
        if (metric === 'bp') return v.bp_sys != null;
        return v[metric] != null;
    });
    if (!data.length) return { points: [], labels: [], values: [], min: 0, max: 100 };

    let values, min, max;
    if (metric === 'bp') {
        values = data.map(v => v.bp_sys);
        const diaValues = data.map(v => v.bp_dia).filter(v => v != null);
        min = Math.min(...values, ...diaValues) - 10;
        max = Math.max(...values, ...diaValues) + 10;
    } else {
        values = data.map(v => v[metric]);
        const range = Math.max(...values) - Math.min(...values);
        const padding = range < 1 ? 5 : range * 0.2;
        min = Math.min(...values) - padding;
        max = Math.max(...values) + padding;
    }

    const w = 100, h = 100;
    const points = values.map((v, i) => ({
        x: data.length === 1 ? w / 2 : (i / (data.length - 1)) * w,
        y: h - ((v - min) / (max - min)) * h,
        value: v,
        label: data[i].date_label,
        isCurrent: data[i].is_current,
    }));

    let diaPoints = [];
    if (metric === 'bp') {
        diaPoints = data.map((v, i) => ({
            x: data.length === 1 ? w / 2 : (i / (data.length - 1)) * w,
            y: h - ((v.bp_dia - min) / (max - min)) * h,
            value: v.bp_dia,
        }));
    }

    return { points, diaPoints, labels: data.map(v => v.date_label), values, min, max, data };
}

function svgPath(points) {
    if (points.length < 2) return '';
    return points.map((p, i) => {
        if (i === 0) return `M ${p.x} ${p.y}`;
        const prev = points[i - 1];
        const cpx1 = prev.x + (p.x - prev.x) * 0.4;
        const cpx2 = p.x - (p.x - prev.x) * 0.4;
        return `C ${cpx1} ${prev.y}, ${cpx2} ${p.y}, ${p.x} ${p.y}`;
    }).join(' ');
}

function svgAreaPath(points, h = 100) {
    if (points.length < 2) return '';
    const line = svgPath(points);
    return `${line} L ${points[points.length-1].x} ${h} L ${points[0].x} ${h} Z`;
}

function getVitalChange(metric) {
    const data = historyData.value;
    if (data.length < 2) return null;
    const current = data[data.length - 1];
    const previous = data[data.length - 2];
    const key = metric === 'bp' ? 'bp_sys' : metric;
    if (current[key] == null || previous[key] == null) return null;
    const diff = current[key] - previous[key];
    const pct = ((diff / previous[key]) * 100).toFixed(1);
    return { diff: diff.toFixed(1), pct, direction: diff > 0 ? 'up' : diff < 0 ? 'down' : 'same' };
}

function toggleVitalsHistory() {
    showVitalsHistory.value = !showVitalsHistory.value;
    if (showVitalsHistory.value) {
        vitalsHistoryAnimated.value = false;
        setTimeout(() => { vitalsHistoryAnimated.value = true; }, 100);
    }
}
</script>

<template>
    <div v-if="hasHistory" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.28s"
    >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-500 to-pink-500 flex items-center justify-center shadow-sm shadow-rose-200">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'اتجاهات العلامات الحيوية' : 'Vitals Trends' }}</h3>
                    <p class="text-[10px] text-gray-400">{{ isRtl ? `${historyData.length} قراءات` : `${historyData.length} readings` }}</p>
                </div>
            </div>
            <button @click="toggleVitalsHistory"
                class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg transition-all"
                :class="showVitalsHistory ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'text-gray-500 hover:bg-gray-50'">
                <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="showVitalsHistory ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                {{ showVitalsHistory ? (isRtl ? 'إخفاء' : 'Hide') : (isRtl ? 'عرض التفاصيل' : 'Show Details') }}
            </button>
        </div>

        <!-- Mini Preview (always visible) -->
        <div class="px-6 py-3">
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                <button v-for="metric in vitalMetrics" :key="metric.key"
                    @click="selectedVitalMetric = metric.key; if(!showVitalsHistory) toggleVitalsHistory()"
                    class="group relative p-2 rounded-xl text-center transition-all duration-300 border"
                    :class="selectedVitalMetric === metric.key && showVitalsHistory
                        ? 'bg-gradient-to-b from-white to-gray-50 border-[#C4A265]/30 shadow-sm shadow-[#C4A265]/10 scale-[1.02]'
                        : 'border-transparent hover:bg-gray-50 hover:border-gray-200'"
                >
                    <div class="text-base mb-0.5">{{ metric.icon }}</div>
                    <div class="text-[10px] font-medium text-gray-500 truncate">{{ metric.label }}</div>
                    <div class="text-xs font-bold mt-0.5" :style="{ color: metric.color }">
                        <template v-if="metric.key === 'bp' && historyData[historyData.length-1]?.bp_sys">
                            {{ historyData[historyData.length-1].bp_sys }}/{{ historyData[historyData.length-1].bp_dia }}
                        </template>
                        <template v-else-if="historyData[historyData.length-1]?.[metric.key] != null">
                            {{ historyData[historyData.length-1][metric.key] }}
                        </template>
                        <template v-else><span class="text-gray-300">--</span></template>
                    </div>
                    <!-- Change arrow -->
                    <div v-if="getVitalChange(metric.key)" class="absolute -top-1 -end-1">
                        <span class="text-[9px] font-bold px-1 py-0.5 rounded-full shadow-sm"
                            :class="getVitalChange(metric.key).direction === 'up' ? 'bg-red-50 text-red-500' : getVitalChange(metric.key).direction === 'down' ? 'bg-blue-50 text-blue-500' : 'bg-gray-50 text-gray-400'">
                            {{ getVitalChange(metric.key).direction === 'up' ? '\u2191' : getVitalChange(metric.key).direction === 'down' ? '\u2193' : '\u2192' }}
                        </span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Expanded Chart & History -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[800px] opacity-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="max-h-[800px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-if="showVitalsHistory" class="overflow-hidden">
                <!-- Chart Area -->
                <div class="px-6 pb-4">
                    <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl border border-gray-100 p-4 relative overflow-hidden">
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, #000 1px, transparent 1px); background-size: 20px 20px;"></div>

                        <!-- Metric Title with Change -->
                        <div class="flex items-center justify-between mb-4 relative">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ vitalMetrics.find(m => m.key === selectedVitalMetric)?.icon }}</span>
                                <span class="text-sm font-bold text-gray-800">{{ vitalMetrics.find(m => m.key === selectedVitalMetric)?.label }}</span>
                                <span class="text-[10px] text-gray-400 font-medium">({{ vitalMetrics.find(m => m.key === selectedVitalMetric)?.unit }})</span>
                            </div>
                            <div v-if="getVitalChange(selectedVitalMetric)" class="flex items-center gap-1.5">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                    :class="getVitalChange(selectedVitalMetric).direction === 'up'
                                        ? 'bg-red-50 text-red-600 border border-red-100'
                                        : getVitalChange(selectedVitalMetric).direction === 'down'
                                            ? 'bg-blue-50 text-blue-600 border border-blue-100'
                                            : 'bg-gray-50 text-gray-500 border border-gray-200'">
                                    {{ getVitalChange(selectedVitalMetric).direction === 'up' ? '\u25B2' : '\u25BC' }}
                                    {{ Math.abs(getVitalChange(selectedVitalMetric).diff) }}
                                    <span class="opacity-60">({{ Math.abs(getVitalChange(selectedVitalMetric).pct) }}%)</span>
                                </span>
                            </div>
                        </div>

                        <!-- Normal Range Reference -->
                        <div v-if="normalRanges[selectedVitalMetric]?.label" class="mb-2 flex items-center gap-1.5">
                            <div class="w-3 h-0.5 bg-emerald-400 rounded-full"></div>
                            <span class="text-[10px] text-emerald-600 font-medium">{{ isRtl ? 'المدى الطبيعي:' : 'Normal:' }} {{ normalRanges[selectedVitalMetric].label }}</span>
                        </div>

                        <!-- SVG Chart -->
                        <div class="relative h-40">
                            <svg v-if="getChartPoints(selectedVitalMetric).points.length >= 2"
                                viewBox="-5 -10 110 120" preserveAspectRatio="none" class="w-full h-full">
                                <!-- Grid Lines -->
                                <line v-for="i in 4" :key="'grid-'+i" :x1="0" :y1="i*25" :x2="100" :y2="i*25"
                                    stroke="#e5e7eb" stroke-width="0.3" stroke-dasharray="2,2" />

                                <!-- Normal Range Band -->
                                <rect v-if="normalRanges[selectedVitalMetric]?.label"
                                    x="0"
                                    :y="100 - ((normalRanges[selectedVitalMetric].high - getChartPoints(selectedVitalMetric).min) / (getChartPoints(selectedVitalMetric).max - getChartPoints(selectedVitalMetric).min)) * 100"
                                    width="100"
                                    :height="((normalRanges[selectedVitalMetric].high - normalRanges[selectedVitalMetric].low) / (getChartPoints(selectedVitalMetric).max - getChartPoints(selectedVitalMetric).min)) * 100"
                                    fill="#10b981" opacity="0.06" rx="2" />

                                <!-- Area Fill -->
                                <path :d="svgAreaPath(getChartPoints(selectedVitalMetric).points)"
                                    :fill="vitalMetrics.find(m => m.key === selectedVitalMetric)?.color"
                                    opacity="0.08"
                                    class="transition-all duration-700"
                                    :class="vitalsHistoryAnimated ? 'opacity-[0.08]' : 'opacity-0'" />

                                <!-- Diastolic line (for BP) -->
                                <path v-if="selectedVitalMetric === 'bp' && getChartPoints('bp').diaPoints.length >= 2"
                                    :d="svgPath(getChartPoints('bp').diaPoints)"
                                    fill="none" stroke="#93C5FD" stroke-width="1.5" stroke-linecap="round"
                                    :stroke-dasharray="vitalsHistoryAnimated ? '0' : '500'"
                                    :stroke-dashoffset="vitalsHistoryAnimated ? '0' : '500'"
                                    style="transition: stroke-dasharray 1.2s ease-out, stroke-dashoffset 1.2s ease-out;" />

                                <!-- Main Line -->
                                <path :d="svgPath(getChartPoints(selectedVitalMetric).points)"
                                    fill="none"
                                    :stroke="vitalMetrics.find(m => m.key === selectedVitalMetric)?.color"
                                    stroke-width="2" stroke-linecap="round"
                                    :stroke-dasharray="vitalsHistoryAnimated ? '0' : '500'"
                                    :stroke-dashoffset="vitalsHistoryAnimated ? '0' : '500'"
                                    style="transition: stroke-dasharray 1s ease-out, stroke-dashoffset 1s ease-out;" />

                                <!-- Data Points -->
                                <g v-for="(p, i) in getChartPoints(selectedVitalMetric).points" :key="'pt-'+i">
                                    <circle :cx="p.x" :cy="p.y" :r="p.isCurrent ? 4 : 2.5"
                                        :fill="p.isCurrent ? vitalMetrics.find(m => m.key === selectedVitalMetric)?.color : '#fff'"
                                        :stroke="vitalMetrics.find(m => m.key === selectedVitalMetric)?.color"
                                        stroke-width="1.5"
                                        class="transition-all duration-500"
                                        :class="vitalsHistoryAnimated ? 'opacity-100' : 'opacity-0'"
                                        :style="`transition-delay: ${i * 100 + 300}ms`" />
                                    <!-- Value label -->
                                    <text :x="p.x" :y="p.y - 7" text-anchor="middle"
                                        class="text-[5px] font-bold transition-all duration-500"
                                        :class="vitalsHistoryAnimated ? 'opacity-100' : 'opacity-0'"
                                        :style="`transition-delay: ${i * 100 + 500}ms`"
                                        :fill="p.isCurrent ? vitalMetrics.find(m => m.key === selectedVitalMetric)?.color : '#6B7280'">
                                        {{ p.value }}
                                    </text>
                                    <!-- Current visit marker -->
                                    <circle v-if="p.isCurrent" :cx="p.x" :cy="p.y" r="7"
                                        :stroke="vitalMetrics.find(m => m.key === selectedVitalMetric)?.color"
                                        fill="none" stroke-width="0.5" opacity="0.4"
                                        class="animate-ping" style="animation-duration: 2s;" />
                                    <!-- Date label -->
                                    <text :x="p.x" :y="108" text-anchor="middle" fill="#9CA3AF"
                                        class="text-[4px]">{{ p.label }}</text>
                                </g>

                                <!-- BP Diastolic points -->
                                <g v-if="selectedVitalMetric === 'bp'">
                                    <circle v-for="(p, i) in getChartPoints('bp').diaPoints" :key="'dia-'+i"
                                        :cx="p.x" :cy="p.y" r="2"
                                        fill="#fff" stroke="#93C5FD" stroke-width="1.5"
                                        class="transition-all duration-500"
                                        :class="vitalsHistoryAnimated ? 'opacity-100' : 'opacity-0'"
                                        :style="`transition-delay: ${i * 100 + 400}ms`" />
                                </g>
                            </svg>

                            <!-- Single data point message -->
                            <div v-else-if="getChartPoints(selectedVitalMetric).points.length === 1" class="flex items-center justify-center h-full">
                                <div class="text-center">
                                    <div class="text-3xl font-bold" :style="{ color: vitalMetrics.find(m => m.key === selectedVitalMetric)?.color }">
                                        {{ getChartPoints(selectedVitalMetric).points[0].value }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">{{ isRtl ? 'قراءة واحدة فقط' : 'Only one reading' }}</div>
                                </div>
                            </div>

                            <!-- No data for this metric -->
                            <div v-else class="flex items-center justify-center h-full">
                                <p class="text-xs text-gray-400">{{ isRtl ? 'لا توجد بيانات لهذا المقياس' : 'No data for this metric' }}</p>
                            </div>
                        </div>

                        <!-- BP Legend -->
                        <div v-if="selectedVitalMetric === 'bp'" class="flex items-center justify-center gap-4 mt-2 text-[10px]">
                            <div class="flex items-center gap-1"><div class="w-3 h-0.5 rounded bg-red-500"></div><span class="text-gray-500">{{ isRtl ? 'انقباضي' : 'Systolic' }}</span></div>
                            <div class="flex items-center gap-1"><div class="w-3 h-0.5 rounded bg-blue-300"></div><span class="text-gray-500">{{ isRtl ? 'انبساطي' : 'Diastolic' }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- History Table -->
                <div class="px-6 pb-5">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ isRtl ? 'سجل القراءات' : 'Reading History' }}
                    </h4>
                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr class="bg-gray-50/80">
                                    <th class="px-3 py-2 text-start text-[10px] font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-500">{{ isRtl ? 'الضغط' : 'BP' }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-500">{{ isRtl ? 'النبض' : 'HR' }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-500">{{ isRtl ? 'الحرارة' : 'Temp' }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-500">SpO2</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-500">{{ isRtl ? 'السكر' : 'Sugar' }}</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-500">{{ isRtl ? 'الوزن' : 'Weight' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(reading, idx) in [...historyData].reverse()" :key="reading.id"
                                    class="transition-all duration-300 hover:bg-[#C4A265]/5"
                                    :class="[
                                        reading.is_current ? 'bg-[#C4A265]/5 border-s-2 border-s-[#C4A265]' : '',
                                        vitalsHistoryAnimated ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-4'
                                    ]"
                                    :style="`transition-delay: ${idx * 60}ms`"
                                >
                                    <td class="px-3 py-2.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span v-if="reading.is_current" class="w-1.5 h-1.5 rounded-full bg-[#C4A265] animate-pulse"></span>
                                            <div>
                                                <span class="font-medium text-gray-800">{{ reading.date_label }}</span>
                                                <span class="text-gray-400 ms-1">{{ reading.time }}</span>
                                            </div>
                                        </div>
                                        <div v-if="reading.is_current" class="text-[9px] text-[#C4A265] font-semibold mt-0.5">{{ isRtl ? 'الزيارة الحالية' : 'Current visit' }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-mono">
                                        <span v-if="reading.bp_sys" class="font-semibold" :class="reading.bp_sys >= 140 ? 'text-red-600' : reading.bp_sys >= 130 ? 'text-orange-500' : 'text-gray-700'">{{ reading.bp_sys }}/{{ reading.bp_dia }}</span>
                                        <span v-else class="text-gray-300">&mdash;</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span v-if="reading.hr" :class="reading.hr > 100 || reading.hr < 60 ? 'text-orange-500 font-semibold' : 'text-gray-700'">{{ reading.hr }}</span>
                                        <span v-else class="text-gray-300">&mdash;</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span v-if="reading.temp" :class="reading.temp > 38 ? 'text-red-600 font-semibold' : 'text-gray-700'">{{ reading.temp }}&deg;</span>
                                        <span v-else class="text-gray-300">&mdash;</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span v-if="reading.spo2" :class="reading.spo2 < 95 ? 'text-red-600 font-semibold' : 'text-gray-700'">{{ reading.spo2 }}%</span>
                                        <span v-else class="text-gray-300">&mdash;</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span v-if="reading.sugar" :class="reading.sugar > 200 || reading.sugar < 70 ? 'text-red-600 font-semibold' : 'text-gray-700'">{{ reading.sugar }}</span>
                                        <span v-else class="text-gray-300">&mdash;</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span v-if="reading.weight" class="text-gray-700">{{ reading.weight }}</span>
                                        <span v-else class="text-gray-300">&mdash;</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
