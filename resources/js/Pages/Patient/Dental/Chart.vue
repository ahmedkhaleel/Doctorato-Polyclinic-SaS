<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    dentalChart: Object,
    dentalConditions: Object,
    allTeeth: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

/* Tooth quadrants (FDI notation) */
const upperRight = computed(() => [18,17,16,15,14,13,12,11]);
const upperLeft = computed(() => [21,22,23,24,25,26,27,28]);
const lowerLeft = computed(() => [31,32,33,34,35,36,37,38]);
const lowerRight = computed(() => [48,47,46,45,44,43,42,41]);

const conditionColors = {
    healthy: 'bg-green-100 text-green-700 border-green-200',
    decayed: 'bg-red-100 text-red-700 border-red-200',
    filled: 'bg-blue-100 text-blue-700 border-blue-200',
    missing: 'bg-gray-100 text-gray-400 border-gray-200',
    crown: 'bg-purple-100 text-purple-700 border-purple-200',
    bridge: 'bg-amber-100 text-amber-700 border-amber-200',
    root_canal: 'bg-orange-100 text-orange-700 border-orange-200',
    implant: 'bg-cyan-100 text-cyan-700 border-cyan-200',
};

const conditionLabels = {
    healthy: { ar: 'سليم', en: 'Healthy' },
    decayed: { ar: 'تسوس', en: 'Decayed' },
    filled: { ar: 'حشوة', en: 'Filled' },
    missing: { ar: 'مفقود', en: 'Missing' },
    crown: { ar: 'تاج', en: 'Crown' },
    bridge: { ar: 'جسر', en: 'Bridge' },
    root_canal: { ar: 'علاج عصب', en: 'Root Canal' },
    implant: { ar: 'زرعة', en: 'Implant' },
};

function toothData(num) {
    return props.dentalChart?.[num] || null;
}

function toothCondition(num) {
    const data = toothData(num);
    return data?.condition || 'healthy';
}

function conditionLabel(condition) {
    const labels = conditionLabels[condition];
    if (!labels) return condition;
    return isRtl.value ? labels.ar : labels.en;
}

/* Stats */
const chartStats = computed(() => {
    const chart = props.dentalChart || {};
    const entries = Object.values(chart);
    const total = 32;
    const recorded = entries.length;
    const conditions = {};
    entries.forEach(e => {
        const c = e.condition || 'healthy';
        conditions[c] = (conditions[c] || 0) + 1;
    });
    return { total, recorded, conditions };
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ t('p_dental_chart') }}</h1>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-gray-800">{{ chartStats.total }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'إجمالي الأسنان' : 'Total Teeth' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ chartStats.conditions['healthy'] || 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'سليمة' : 'Healthy' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-red-600">{{ chartStats.conditions['decayed'] || 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'تسوس' : 'Decayed' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-blue-600">{{ chartStats.conditions['filled'] || 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'حشوة' : 'Filled' }}</p>
            </div>
        </div>

        <!-- Dental Chart Visual -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h2>

            <!-- Upper Jaw -->
            <div class="mb-2">
                <p class="text-xs text-gray-400 text-center mb-2">{{ isRtl ? 'الفك العلوي' : 'Upper Jaw' }}</p>
                <div class="flex justify-center gap-0.5">
                    <!-- Upper Right (reversed for display) -->
                    <div class="flex gap-0.5">
                        <div v-for="num in upperRight" :key="num" class="text-center">
                            <div
                                :class="conditionColors[toothCondition(num)] || 'bg-gray-50 text-gray-400 border-gray-200'"
                                class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border flex items-center justify-center text-[10px] sm:text-xs font-bold cursor-default transition-all hover:scale-110"
                                :title="conditionLabel(toothCondition(num))"
                            >
                                {{ num }}
                            </div>
                        </div>
                    </div>
                    <div class="w-px bg-gray-300 mx-1"></div>
                    <!-- Upper Left -->
                    <div class="flex gap-0.5">
                        <div v-for="num in upperLeft" :key="num" class="text-center">
                            <div
                                :class="conditionColors[toothCondition(num)] || 'bg-gray-50 text-gray-400 border-gray-200'"
                                class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border flex items-center justify-center text-[10px] sm:text-xs font-bold cursor-default transition-all hover:scale-110"
                                :title="conditionLabel(toothCondition(num))"
                            >
                                {{ num }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="flex justify-center my-2">
                <div class="w-64 sm:w-80 h-px bg-gray-200"></div>
            </div>

            <!-- Lower Jaw -->
            <div>
                <div class="flex justify-center gap-0.5">
                    <!-- Lower Right (reversed for display) -->
                    <div class="flex gap-0.5">
                        <div v-for="num in lowerRight" :key="num" class="text-center">
                            <div
                                :class="conditionColors[toothCondition(num)] || 'bg-gray-50 text-gray-400 border-gray-200'"
                                class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border flex items-center justify-center text-[10px] sm:text-xs font-bold cursor-default transition-all hover:scale-110"
                                :title="conditionLabel(toothCondition(num))"
                            >
                                {{ num }}
                            </div>
                        </div>
                    </div>
                    <div class="w-px bg-gray-300 mx-1"></div>
                    <!-- Lower Left -->
                    <div class="flex gap-0.5">
                        <div v-for="num in lowerLeft" :key="num" class="text-center">
                            <div
                                :class="conditionColors[toothCondition(num)] || 'bg-gray-50 text-gray-400 border-gray-200'"
                                class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg border flex items-center justify-center text-[10px] sm:text-xs font-bold cursor-default transition-all hover:scale-110"
                                :title="conditionLabel(toothCondition(num))"
                            >
                                {{ num }}
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-400 text-center mt-2">{{ isRtl ? 'الفك السفلي' : 'Lower Jaw' }}</p>
            </div>
        </div>

        <!-- Legend -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'دليل الألوان' : 'Color Legend' }}</h3>
            <div class="flex flex-wrap gap-3">
                <div v-for="(colors, condition) in conditionColors" :key="condition" class="flex items-center gap-2">
                    <div :class="colors" class="w-6 h-6 rounded border flex items-center justify-center text-[8px] font-bold">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5" /></svg>
                    </div>
                    <span class="text-xs text-gray-600">{{ conditionLabel(condition) }}</span>
                </div>
            </div>
        </div>

        <!-- Tooth Details Table -->
        <div v-if="chartStats.recorded > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">{{ isRtl ? 'تفاصيل الأسنان' : 'Tooth Details' }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'رقم السن' : 'Tooth #' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Condition' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الأسطح' : 'Surfaces' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'ملاحظات' : 'Notes' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(tooth, num) in dentalChart" :key="num" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-mono font-semibold text-gray-700">{{ num }}</td>
                            <td class="px-4 py-3">
                                <span :class="conditionColors[tooth.condition] || 'bg-gray-100 text-gray-500'" class="text-xs font-medium px-2.5 py-1 rounded-full border">
                                    {{ conditionLabel(tooth.condition) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                <span v-if="tooth.surfaces?.length">{{ tooth.surfaces.join(', ') }}</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ tooth.notes || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
