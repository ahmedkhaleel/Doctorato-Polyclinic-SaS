<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    chart: Object,
    conditions: Array,
    allTeeth: Array,
});

const selectedTooth = ref(null);

// FDI Numbering layout
const upperRight = [18, 17, 16, 15, 14, 13, 12, 11];
const upperLeft = [21, 22, 23, 24, 25, 26, 27, 28];
const lowerRight = [48, 47, 46, 45, 44, 43, 42, 41];
const lowerLeft = [31, 32, 33, 34, 35, 36, 37, 38];

const conditionColors = {
    healthy: 'bg-green-200 text-green-900 border-green-400',
    decayed: 'bg-red-200 text-red-900 border-red-400',
    filled: 'bg-blue-200 text-blue-900 border-blue-400',
    missing: 'bg-gray-300 text-gray-600 border-gray-400',
    crown: 'bg-yellow-200 text-yellow-900 border-yellow-400',
    bridge: 'bg-purple-200 text-purple-900 border-purple-400',
    implant: 'bg-indigo-200 text-indigo-900 border-indigo-400',
    root_canal: 'bg-orange-200 text-orange-900 border-orange-400',
    extracted: 'bg-gray-400 text-white border-gray-500',
};

const conditionLabels = computed(() => ({
    healthy: isRtl.value ? 'سليم' : 'Healthy',
    decayed: isRtl.value ? 'متسوس' : 'Decayed',
    filled: isRtl.value ? 'محشو' : 'Filled',
    missing: isRtl.value ? 'مفقود' : 'Missing',
    crown: isRtl.value ? 'تاج' : 'Crown',
    bridge: isRtl.value ? 'جسر' : 'Bridge',
    implant: isRtl.value ? 'زرعة' : 'Implant',
    root_canal: isRtl.value ? 'علاج جذور' : 'Root Canal',
    extracted: isRtl.value ? 'مخلوع' : 'Extracted',
}));

function getToothData(num) {
    return props.chart?.[num] || { tooth_number: num, condition: 'healthy', status: 'present' };
}

function viewTooth(num) {
    selectedTooth.value = selectedTooth.value === num ? null : num;
}

const selectedToothData = computed(() => {
    if (!selectedTooth.value) return null;
    return getToothData(selectedTooth.value);
});

// Count conditions for summary
const conditionSummary = computed(() => {
    const summary = {};
    const teeth = props.allTeeth || [];
    for (const num of teeth) {
        const data = getToothData(num);
        const cond = data.condition || 'healthy';
        if (!summary[cond]) summary[cond] = 0;
        summary[cond]++;
    }
    return summary;
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ patient.full_name }} — {{ patient.file_number }}</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <Link :href="`/secretary/dental/treatment-plans?search=${encodeURIComponent(patient.full_name)}`" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-[#C4A265] bg-[#C4A265]/10 rounded-xl hover:bg-[#C4A265]/20 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    {{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}
                </Link>
                <Link href="/secretary/dental" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'رجوع' : 'Back' }}
                </Link>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 mb-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'الاسم' : 'Name' }}</span>
                    <span class="font-semibold text-gray-800">{{ patient.full_name }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'رقم الملف' : 'File #' }}</span>
                    <span class="font-mono font-semibold text-gray-800">{{ patient.file_number || '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'الهاتف' : 'Phone' }}</span>
                    <span class="font-semibold text-gray-800" dir="ltr">{{ patient.phone || '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'البريد الإلكتروني' : 'Email' }}</span>
                    <span class="font-semibold text-gray-800">{{ patient.email || '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 mb-6">
            <div class="flex flex-wrap gap-3">
                <div v-for="(color, cond) in conditionColors" :key="cond" class="flex items-center gap-2">
                    <span :class="[color, 'w-5 h-5 rounded border']"></span>
                    <span class="text-xs text-gray-600">{{ conditionLabels[cond] }}</span>
                </div>
            </div>
        </div>

        <!-- Dental Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 mb-6 overflow-x-auto">
            <h2 class="text-center text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ isRtl ? 'الفك العلوي' : 'Upper Jaw' }}</h2>

            <!-- Upper Jaw -->
            <div class="flex justify-center gap-1 mb-2">
                <div
                    v-for="num in upperRight"
                    :key="num"
                    @click="viewTooth(num)"
                    :class="[
                        conditionColors[getToothData(num).condition] || 'bg-gray-100 border-gray-300',
                        selectedTooth === num ? 'ring-2 ring-[#C4A265] ring-offset-1' : '',
                        'w-10 h-12 sm:w-12 sm:h-14 rounded-t-lg border-2 cursor-pointer flex flex-col items-center justify-center hover:opacity-80 transition-all'
                    ]"
                >
                    <span class="text-[10px] sm:text-xs font-bold">{{ num }}</span>
                    <span class="text-[8px] sm:text-[9px]">{{ (getToothData(num).condition || 'healthy').charAt(0).toUpperCase() }}</span>
                </div>
                <div class="w-px bg-gray-300 mx-1 self-stretch"></div>
                <div
                    v-for="num in upperLeft"
                    :key="num"
                    @click="viewTooth(num)"
                    :class="[
                        conditionColors[getToothData(num).condition] || 'bg-gray-100 border-gray-300',
                        selectedTooth === num ? 'ring-2 ring-[#C4A265] ring-offset-1' : '',
                        'w-10 h-12 sm:w-12 sm:h-14 rounded-t-lg border-2 cursor-pointer flex flex-col items-center justify-center hover:opacity-80 transition-all'
                    ]"
                >
                    <span class="text-[10px] sm:text-xs font-bold">{{ num }}</span>
                    <span class="text-[8px] sm:text-[9px]">{{ (getToothData(num).condition || 'healthy').charAt(0).toUpperCase() }}</span>
                </div>
            </div>

            <!-- Divider -->
            <div class="flex justify-center">
                <div class="w-[420px] sm:w-[520px] h-px bg-gray-300 my-2"></div>
            </div>

            <!-- Lower Jaw -->
            <div class="flex justify-center gap-1 mt-2">
                <div
                    v-for="num in lowerRight"
                    :key="num"
                    @click="viewTooth(num)"
                    :class="[
                        conditionColors[getToothData(num).condition] || 'bg-gray-100 border-gray-300',
                        selectedTooth === num ? 'ring-2 ring-[#C4A265] ring-offset-1' : '',
                        'w-10 h-12 sm:w-12 sm:h-14 rounded-b-lg border-2 cursor-pointer flex flex-col items-center justify-center hover:opacity-80 transition-all'
                    ]"
                >
                    <span class="text-[8px] sm:text-[9px]">{{ (getToothData(num).condition || 'healthy').charAt(0).toUpperCase() }}</span>
                    <span class="text-[10px] sm:text-xs font-bold">{{ num }}</span>
                </div>
                <div class="w-px bg-gray-300 mx-1 self-stretch"></div>
                <div
                    v-for="num in lowerLeft"
                    :key="num"
                    @click="viewTooth(num)"
                    :class="[
                        conditionColors[getToothData(num).condition] || 'bg-gray-100 border-gray-300',
                        selectedTooth === num ? 'ring-2 ring-[#C4A265] ring-offset-1' : '',
                        'w-10 h-12 sm:w-12 sm:h-14 rounded-b-lg border-2 cursor-pointer flex flex-col items-center justify-center hover:opacity-80 transition-all'
                    ]"
                >
                    <span class="text-[8px] sm:text-[9px]">{{ (getToothData(num).condition || 'healthy').charAt(0).toUpperCase() }}</span>
                    <span class="text-[10px] sm:text-xs font-bold">{{ num }}</span>
                </div>
            </div>

            <h2 class="text-center text-sm font-semibold text-gray-400 uppercase tracking-wider mt-4">{{ isRtl ? 'الفك السفلي' : 'Lower Jaw' }}</h2>
        </div>

        <!-- Selected Tooth Details (read-only) -->
        <div v-if="selectedTooth && selectedToothData" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                {{ isRtl ? 'السن' : 'Tooth' }} #{{ selectedTooth }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'الحالة' : 'Condition' }}</span>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold border" :class="conditionColors[selectedToothData.condition] || 'bg-gray-100 border-gray-300'">
                        {{ conditionLabels[selectedToothData.condition] || selectedToothData.condition }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'الحالة' : 'Status' }}</span>
                    <span class="font-medium text-gray-800 capitalize">{{ selectedToothData.status || (isRtl ? 'موجود' : 'Present') }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block mb-1">{{ isRtl ? 'الأسطح' : 'Surfaces' }}</span>
                    <span class="font-medium text-gray-800">
                        {{ selectedToothData.surfaces && selectedToothData.surfaces.length > 0 ? (Array.isArray(selectedToothData.surfaces) ? selectedToothData.surfaces.join(', ') : selectedToothData.surfaces) : '-' }}
                    </span>
                </div>
            </div>
            <div v-if="selectedToothData.notes" class="mt-4 pt-4 border-t border-gray-100">
                <span class="text-gray-400 block mb-1 text-sm">{{ isRtl ? 'ملاحظات' : 'Notes' }}</span>
                <p class="text-sm text-gray-700">{{ selectedToothData.notes }}</p>
            </div>
        </div>

        <!-- Condition Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ isRtl ? 'ملخص الحالات' : 'Condition Summary' }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <div
                    v-for="(count, cond) in conditionSummary"
                    :key="cond"
                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-100"
                >
                    <span :class="[conditionColors[cond] || 'bg-gray-100 border-gray-300', 'w-8 h-8 rounded-lg border flex items-center justify-center text-xs font-bold']">
                        {{ count }}
                    </span>
                    <span class="text-sm text-gray-600">{{ conditionLabels[cond] || cond }}</span>
                </div>
            </div>
            <div v-if="Object.keys(conditionSummary).length === 0" class="text-center py-6">
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد بيانات مخطط' : 'No chart data available' }}</p>
            </div>
        </div>
    </div>
</template>
