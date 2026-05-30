<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

// AdminLayout is applied via the <AdminLayout> wrapper below — do NOT also
// set it via defineOptions, or the layout (and its header) renders twice.

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { formatCurrency } = useCurrency();
const ACCENT = '#DB2777';

const props = defineProps({
    byStatus: { type: Object, default: () => ({}) },
    byMode: { type: Object, default: () => ({}) },
    byPap: { type: Object, default: () => ({}) },
    revenueTrend: { type: Array, default: () => [] },
    topDoctors: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
});

const maxTrend = computed(() => Math.max(...props.revenueTrend.map(d => d.value), 1));
function modeLabel(m) {
    const map = isRtl.value ? { nvd: 'طبيعية', cesarean: 'قيصرية', instrumental: 'بمساعدة' } : { nvd: 'Vaginal', cesarean: 'Cesarean', instrumental: 'Instrumental' };
    return map[m] || m;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'تقارير النساء والتوليد' : 'OB/GYN Reports' }}</h2>
        </template>

        <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- KPI -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-sm text-gray-500">{{ isRtl ? 'إجمالي ملفات الحمل' : 'Total Pregnancies' }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ totals.total_pregnancies || 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-sm text-gray-500">{{ isRtl ? 'إجمالي الولادات' : 'Total Deliveries' }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ totals.total_deliveries || 0 }}</p>
                </div>
                <div class="rounded-2xl p-5 text-white shadow-sm" style="background: linear-gradient(120deg,#1B365D,#DB2777 200%)">
                    <p class="text-sm text-white/70">{{ isRtl ? 'إيراد السنة' : 'Revenue (Year)' }}</p>
                    <p class="text-3xl font-bold mt-1">{{ formatCurrency(totals.revenue_year || 0) }}</p>
                </div>
            </div>

            <!-- Revenue trend -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-bold text-gray-800 mb-4">{{ isRtl ? 'اتجاه الإيراد (6 أشهر)' : 'Revenue Trend (6 months)' }}</h3>
                <div class="flex items-end justify-between gap-2 h-40">
                    <div v-for="(d, i) in revenueTrend" :key="i" class="flex-1 flex flex-col items-center justify-end gap-2">
                        <div class="w-full rounded-t-lg transition-all duration-700" :style="{ height: Math.max((d.value/maxTrend)*100, 3)+'%', background: ACCENT }"></div>
                        <span class="text-xs text-gray-400">{{ d.label }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Deliveries by mode -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">{{ isRtl ? 'الولادات حسب النوع' : 'Deliveries by Mode' }}</h3>
                    <div v-if="Object.keys(byMode).length === 0" class="text-sm text-gray-400 py-6 text-center">—</div>
                    <ul v-else class="space-y-2">
                        <li v-for="(c, m) in byMode" :key="m" class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ modeLabel(m) }}</span><span class="font-semibold text-gray-800">{{ c }}</span>
                        </li>
                    </ul>
                </div>
                <!-- Pap results -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">{{ isRtl ? 'نتائج مسحة عنق الرحم' : 'Pap Smear Results' }}</h3>
                    <div v-if="Object.keys(byPap).length === 0" class="text-sm text-gray-400 py-6 text-center">—</div>
                    <ul v-else class="space-y-2">
                        <li v-for="(c, r) in byPap" :key="r" class="flex justify-between text-sm">
                            <span class="text-gray-600 uppercase">{{ r }}</span><span class="font-semibold" :class="r === 'normal' ? 'text-emerald-600' : 'text-amber-600'">{{ c }}</span>
                        </li>
                    </ul>
                </div>
                <!-- Top doctors -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">{{ isRtl ? 'الأطباء الأكثر متابعة' : 'Top Doctors' }}</h3>
                    <div v-if="topDoctors.length === 0" class="text-sm text-gray-400 py-6 text-center">—</div>
                    <ul v-else class="space-y-2">
                        <li v-for="(d, i) in topDoctors" :key="i" class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ isRtl ? d.doctor?.name_ar : d.doctor?.name_en }}</span><span class="font-semibold text-gray-800">{{ d.count }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
