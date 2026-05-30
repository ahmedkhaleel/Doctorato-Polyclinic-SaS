<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

// AdminLayout is applied via the <AdminLayout> wrapper below — do NOT also
// set it via defineOptions, or the layout (and its header) renders twice.

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { formatCurrency } = useCurrency();
const ACCENT = '#DB2777';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    trimesters: { type: Object, default: () => ({}) },
    upcomingDue: { type: Array, default: () => [] },
});

const mounted = ref(false);
onMounted(() => setTimeout(() => { mounted.value = true; }, 50));

const cards = computed(() => [
    { key: 'active_pregnancies', v: props.stats.active_pregnancies || 0, label: isRtl.value ? 'حمل نشط' : 'Active Pregnancies', color: ACCENT },
    { key: 'high_risk', v: props.stats.high_risk || 0, label: isRtl.value ? 'عالي الخطورة' : 'High-Risk', color: '#EF4444' },
    { key: 'anc_this_month', v: props.stats.anc_this_month || 0, label: isRtl.value ? 'متابعات هذا الشهر' : 'ANC This Month', color: '#1B365D' },
    { key: 'deliveries_this_month', v: props.stats.deliveries_this_month || 0, label: isRtl.value ? 'ولادات هذا الشهر' : 'Deliveries', color: '#10B981' },
]);

const trimesterTotal = computed(() => (props.trimesters[1] || 0) + (props.trimesters[2] || 0) + (props.trimesters[3] || 0) || 1);
function tPct(t) { return Math.round(((props.trimesters[t] || 0) / trimesterTotal.value) * 100); }
function dueLabel(d) {
    if (d === null || d === undefined) return '';
    if (d < 0) return isRtl.value ? `متأخّر ${Math.abs(d)} يوم` : `${Math.abs(d)}d overdue`;
    return isRtl.value ? `خلال ${d} يوم` : `in ${d}d`;
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'النساء والتوليد' : 'Obstetrics & Gynecology' }}</h2>
        </template>

        <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="(c, i) in cards" :key="c.key"
                     class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden transition-all duration-500"
                     :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" :style="{ transitionDelay: `${i*70}ms` }">
                    <span class="absolute top-0 inset-x-0 h-1" :style="{ background: c.color }"></span>
                    <p class="text-3xl font-bold text-gray-900 tabular-nums">{{ c.v }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ c.label }}</p>
                </div>
            </div>

            <!-- Revenue + trimesters -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="rounded-2xl p-6 text-white shadow-sm" style="background: linear-gradient(120deg,#1B365D,#24456f 70%,#DB2777 180%)">
                    <p class="text-white/70 text-sm">{{ isRtl ? 'إيراد هذا الشهر' : 'Revenue This Month' }}</p>
                    <p class="text-3xl font-bold mt-2">{{ formatCurrency(stats.revenue_this_month || 0) }}</p>
                    <p class="text-white/60 text-xs mt-2">{{ isRtl ? 'فواتير قسم النساء والتوليد' : 'OB/GYN module invoices' }}</p>
                </div>
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">{{ isRtl ? 'توزيع الحمل حسب الثلث' : 'Active Pregnancies by Trimester' }}</h3>
                    <div class="space-y-3">
                        <div v-for="t in [1,2,3]" :key="t">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">{{ isRtl ? (t===1?'الثلث الأول':t===2?'الثلث الثاني':'الثلث الثالث') : (t===1?'1st':t===2?'2nd':'3rd') + ' trimester' }}</span>
                                <span class="font-semibold text-gray-800">{{ trimesters[t] || 0 }}</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700" :style="{ width: tPct(t)+'%', background: ACCENT }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming due -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-2 h-6 rounded-full" :style="{ background: ACCENT }"></span>
                    <h3 class="font-bold text-gray-800">{{ isRtl ? 'قرب موعد الولادة (30 يوم)' : 'Due Within 30 Days' }}</h3>
                </div>
                <div v-if="upcomingDue.length === 0" class="text-center text-gray-400 py-8 text-sm">{{ isRtl ? 'لا توجد حالات قريبة' : 'None upcoming' }}</div>
                <table v-else class="w-full text-sm">
                    <thead class="text-gray-500 border-b border-gray-100">
                        <tr><th class="text-start py-2 font-medium">{{ isRtl ? 'المريضة' : 'Patient' }}</th><th class="text-start py-2 font-medium">{{ isRtl ? 'الطبيبة' : 'Doctor' }}</th><th class="text-start py-2 font-medium">EDD</th><th class="text-end py-2 font-medium"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in upcomingDue" :key="p.id" class="hover:bg-rose-50/30">
                            <td class="py-2.5 font-medium text-gray-800">{{ p.patient?.full_name }}</td>
                            <td class="py-2.5 text-gray-500">{{ isRtl ? p.doctor?.name_ar : p.doctor?.name_en }}</td>
                            <td class="py-2.5 text-gray-500" dir="ltr">{{ p.edd }}</td>
                            <td class="py-2.5 text-end"><span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="p.days_until_edd < 0 ? 'bg-red-100 text-red-700' : 'bg-rose-100 text-rose-700'">{{ dueLabel(p.days_until_edd) }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
