<script setup>
/**
 * DentalPanel — dental clinical section of the visit page.
 * Extracted verbatim from Doctor/Visits/Show.vue (Phase 0) to establish the
 * per-specialty panel pattern with NO behavioural change.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency.js';

const props = defineProps({
    visit: { type: Object, required: true },
    isRtl: { type: Boolean, default: true },
    mounted: { type: Boolean, default: false },
    dentalChart: { type: Object, default: () => ({}) },
    dentalXrays: { type: Array, default: () => [] },
    allTeeth: { type: Object, default: () => ({}) },
    treatmentTypes: { type: Object, default: () => ({}) },
    perioSummary: { type: Object, default: null },
});

const { formatCurrency } = useCurrency();

const hasPerio = computed(() => (props.perioSummary?.charted_teeth || 0) > 0);
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
    >
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
            </div>
            <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'طب الأسنان' : 'Dental' }}</h3>
        </div>
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Dental Treatments Table -->
            <div>
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'علاجات الأسنان' : 'Dental Treatments' }}</h4>
                <div v-if="visit.dental_treatments?.length" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase">
                                <th class="text-left py-2 pr-3">{{ isRtl ? 'السن' : 'Tooth' }}</th>
                                <th class="text-left py-2 pr-3">{{ isRtl ? 'النوع' : 'Type' }}</th>
                                <th class="text-left py-2 pr-3">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                                <th class="text-left py-2 pr-3">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="t in visit.dental_treatments" :key="t.id">
                                <td class="py-2 pr-3 font-mono text-[#C4A265]">#{{ t.tooth_number || '-' }}</td>
                                <td class="py-2 pr-3"><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-[#C4A265]/10 text-[#C4A265]">{{ treatmentTypes?.[t.treatment_type] || t.treatment_type }}</span></td>
                                <td class="py-2 pr-3 text-gray-600 text-xs">{{ t.description || '-' }}</td>
                                <td class="py-2 pr-3">
                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full" :class="{
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200': t.status === 'completed',
                                        'bg-slate-50 text-[#1B365D] border border-slate-200': t.status === 'in_progress',
                                        'bg-amber-50 text-amber-700 border border-amber-200': t.status === 'planned',
                                        'bg-gray-50 text-gray-500 border border-gray-200': t.status === 'cancelled',
                                    }">{{ isRtl ? ({ completed: 'مكتمل', in_progress: 'جاري', planned: 'مخطط', cancelled: 'ملغي' }[t.status] || t.status) : ({ completed: 'Completed', in_progress: 'In Progress', planned: 'Planned', cancelled: 'Cancelled' }[t.status] || t.status) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد علاجات' : 'No treatments recorded' }}</p>
                <!-- Cost Summary -->
                <div v-if="visit.dental_treatments?.length" class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                    <span class="text-gray-500">{{ isRtl ? 'إجمالي تكلفة العلاجات' : 'Total Treatment Cost' }}</span>
                    <span class="font-bold text-[#C4A265]">{{ formatCurrency(visit.dental_treatments.reduce((sum, t) => sum + (parseFloat(t.cost) || 0), 0)) }}</span>
                </div>
            </div>

            <!-- Periodontal summary -->
            <div v-if="hasPerio">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ isRtl ? 'حالة اللثة' : 'Periodontal status' }}</h4>
                <div class="grid grid-cols-4 gap-2">
                    <div class="rounded-xl border text-center p-2" :class="perioSummary.max_pd >= 6 ? 'bg-red-50 border-red-200' : perioSummary.max_pd >= 4 ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-100'">
                        <p class="text-base font-extrabold" :class="perioSummary.max_pd >= 6 ? 'text-red-700' : perioSummary.max_pd >= 4 ? 'text-amber-700' : 'text-emerald-700'">{{ perioSummary.max_pd }}<span class="text-[10px] font-medium">mm</span></p>
                        <p class="text-[10px] text-gray-500">{{ isRtl ? 'أعمق جيب' : 'Deepest' }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 text-center p-2">
                        <p class="text-base font-extrabold text-[#1B365D]">{{ perioSummary.sites_4plus }}</p>
                        <p class="text-[10px] text-gray-500">{{ isRtl ? 'مواقع ≥٤مم' : 'Sites ≥4mm' }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 text-center p-2">
                        <p class="text-base font-extrabold text-red-600">{{ perioSummary.bop_sites }}</p>
                        <p class="text-[10px] text-gray-500">{{ isRtl ? 'نزف عند السبر' : 'BoP sites' }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 text-center p-2">
                        <p class="text-base font-extrabold text-[#C4A265]">{{ perioSummary.charted_teeth }}</p>
                        <p class="text-[10px] text-gray-500">{{ isRtl ? 'أسنان مُخطّطة' : 'Charted' }}</p>
                    </div>
                </div>
            </div>

            <!-- Mini Dental Chart -->
            <div v-if="dentalChart && Object.keys(dentalChart).length > 0">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h4>
                <div class="flex flex-wrap gap-1 mb-2">
                    <div v-for="quadrant in ['upper_right', 'upper_left', 'lower_left', 'lower_right']" :key="quadrant" class="flex flex-wrap gap-1">
                        <div v-for="tooth in (allTeeth?.[quadrant] || [])" :key="tooth"
                            class="w-7 h-7 rounded flex items-center justify-center text-[10px] font-mono border transition-all"
                            :class="dentalChart[tooth] ? 'bg-amber-50 border-amber-300 text-amber-700 font-bold' : 'bg-gray-50 border-gray-200 text-gray-400'"
                            :title="dentalChart[tooth]?.condition || 'Healthy'"
                        >{{ tooth }}</div>
                    </div>
                </div>
                <Link :href="`/doctor/dental/chart/${visit.patient_id}`" class="inline-flex items-center gap-1 text-xs font-medium text-[#C4A265] hover:text-[#A68B52] transition-colors mt-1">
                    {{ isRtl ? 'عرض المخطط الكامل' : 'View Full Chart' }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>

            <!-- X-rays Preview -->
            <div v-if="dentalXrays?.length > 0">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'صور الأشعة' : 'X-Rays' }}</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <div v-for="xray in dentalXrays.slice(0, 6)" :key="xray.id" class="aspect-square rounded-xl overflow-hidden bg-gray-100 relative group cursor-pointer hover:ring-2 hover:ring-[#C4A265]/50 transition-all">
                        <img :src="xray.image_url" :alt="xray.notes || 'X-ray'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent text-white text-[10px] px-2 py-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <p v-if="xray.xray_type" class="capitalize">{{ xray.xray_type }}</p>
                            <p v-if="xray.taken_date">{{ xray.taken_date }}</p>
                        </div>
                    </div>
                </div>
                <Link v-if="dentalXrays.length > 6" :href="`/doctor/dental/xrays/${visit.patient_id}`" class="inline-flex items-center gap-1 text-xs font-medium text-[#C4A265] hover:text-[#A68B52] transition-colors mt-2">
                    {{ isRtl ? `عرض الكل (${dentalXrays.length})` : `View All (${dentalXrays.length})` }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>

            <!-- Quick Links -->
            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                <Link :href="`/doctor/dental/chart/${visit.patient_id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 rounded-lg border border-[#C4A265]/20 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" /></svg>
                    {{ isRtl ? 'المخطط' : 'Chart' }}
                </Link>
                <Link :href="`/doctor/dental/treatment-plans?patient_id=${visit.patient_id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    {{ isRtl ? 'خطط العلاج' : 'Plans' }}
                </Link>
                <Link :href="`/doctor/dental/xrays/${visit.patient_id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    {{ isRtl ? 'الأشعة' : 'X-Rays' }}
                </Link>
            </div>
        </div>
    </div>
</template>
