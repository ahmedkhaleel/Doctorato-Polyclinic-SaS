<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    records: Array,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function percentileColor(p) {
    if (p === null || p === undefined) return 'text-gray-400';
    if (p < 3 || p > 97) return 'text-red-600 font-bold';
    if (p < 15 || p > 85) return 'text-amber-600';
    return 'text-emerald-600';
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link href="/patient/pediatric/overview" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ isRtl ? 'منحنى النمو' : 'Growth Chart' }}</h1>
                <p class="text-sm text-gray-500">{{ isRtl ? 'سجل قياسات الوزن والطول' : 'Weight and height measurements history' }}</p>
            </div>
        </div>

        <!-- Growth Records Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'العمر' : 'Age' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الوزن' : 'Weight' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطول' : 'Height' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'محيط الرأس' : 'Head' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">BMI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="r in records" :key="r.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-gray-800">{{ formatDate(r.measurement_date) }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ r.age_months ? `${Math.floor(r.age_months / 12)}y ${Math.round(r.age_months % 12)}m` : '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-gray-800">{{ r.weight_kg ?? '-' }} kg</span>
                                <p v-if="r.weight_percentile !== null" class="text-[10px] mt-0.5" :class="percentileColor(r.weight_percentile)">P{{ r.weight_percentile }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-gray-800">{{ r.height_cm ?? '-' }} cm</span>
                                <p v-if="r.height_percentile !== null" class="text-[10px] mt-0.5" :class="percentileColor(r.height_percentile)">P{{ r.height_percentile }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-gray-800">{{ r.head_circumference_cm ?? '-' }} cm</span>
                                <p v-if="r.head_percentile !== null" class="text-[10px] mt-0.5" :class="percentileColor(r.head_percentile)">P{{ r.head_percentile }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-gray-800">{{ r.bmi ?? '-' }}</span>
                                <p v-if="r.bmi_percentile !== null" class="text-[10px] mt-0.5" :class="percentileColor(r.bmi_percentile)">P{{ r.bmi_percentile }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!records?.length" class="py-16 text-center">
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد قياسات مسجلة' : 'No growth measurements recorded' }}</p>
            </div>
        </div>
    </div>
</template>
