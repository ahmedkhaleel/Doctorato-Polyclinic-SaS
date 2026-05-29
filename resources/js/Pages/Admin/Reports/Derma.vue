<script setup>
import { computed, ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    filters: Object,
    summary: Object,
    byType: { type: Array, default: () => [] },
    topProcedures: { type: Array, default: () => [] },
    conditionMix: { type: Array, default: () => [] },
    plans: Object,
    packages: Object,
});

const { formatCurrency } = useCurrency();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const from = ref(props.filters?.date_from || '');
const to = ref(props.filters?.date_to || '');
function applyRange() {
    router.get('/admin/reports/derma', { date_from: from.value, date_to: to.value }, { preserveState: true, preserveScroll: true });
}

const typeLabels = {
    laser: { ar: 'ليزر', en: 'Laser' }, peel: { ar: 'تقشير', en: 'Peel' },
    phototherapy: { ar: 'علاج ضوئي', en: 'Phototherapy' }, injection: { ar: 'حقن', en: 'Injection' },
    cryotherapy: { ar: 'تبريد', en: 'Cryotherapy' }, other: { ar: 'أخرى', en: 'Other' },
};
function typeLabel(t) { return typeLabels[t] ? (isRtl.value ? typeLabels[t].ar : typeLabels[t].en) : t; }

const condLabels = {
    acne: { ar: 'حب الشباب', en: 'Acne' }, psoriasis: { ar: 'صدفية', en: 'Psoriasis' },
    eczema: { ar: 'إكزيما', en: 'Eczema' }, melasma: { ar: 'كلف', en: 'Melasma' },
    vitiligo: { ar: 'بهاق', en: 'Vitiligo' }, rosacea: { ar: 'وردية', en: 'Rosacea' },
    dermatitis: { ar: 'التهاب جلدي', en: 'Dermatitis' }, fungal: { ar: 'فطري', en: 'Fungal' }, other: { ar: 'أخرى', en: 'Other' },
};
function condLabel(c) { return condLabels[c] ? (isRtl.value ? condLabels[c].ar : condLabels[c].en) : c; }
function procName(p) { return isRtl.value ? (p.name_ar || p.name_en) : (p.name_en || p.name_ar); }

const maxType = computed(() => Math.max(1, ...props.byType.map(t => t.total)));
const maxProc = computed(() => Math.max(1, ...props.topProcedures.map(p => p.total)));

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="dr-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">
        <!-- Hero -->
        <div class="dr-hero dr-stagger" style="--i:0">
            <div class="dr-hero-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="dr-hero-badge">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'تقارير' : 'Reports' }}</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'تقرير الجلدية والتجميل' : 'Derma & Cosmetic Report' }}</h1>
                    </div>
                </div>
                <div class="flex items-end gap-2 bg-white/10 rounded-xl p-2">
                    <div>
                        <label class="block text-[9px] text-white/60 uppercase mb-1">{{ isRtl ? 'من' : 'From' }}</label>
                        <input v-model="from" type="date" class="dr-date" />
                    </div>
                    <div>
                        <label class="block text-[9px] text-white/60 uppercase mb-1">{{ isRtl ? 'إلى' : 'To' }}</label>
                        <input v-model="to" type="date" class="dr-date" />
                    </div>
                    <button @click="applyRange" class="dr-apply">{{ isRtl ? 'تطبيق' : 'Apply' }}</button>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6 mb-6">
            <div class="dr-stat dr-stagger" style="--i:1"><div class="dr-bar bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div><p class="dr-label">{{ isRtl ? 'إجمالي الإيراد' : 'Total Revenue' }}</p><p class="dr-val text-[#8B7043] text-xl">{{ formatCurrency(summary.total_revenue) }}</p></div>
            <div class="dr-stat dr-stagger" style="--i:2"><div class="dr-bar bg-gradient-to-r from-[#1B365D] to-[#22406F]"></div><p class="dr-label">{{ isRtl ? 'جلسات جلدية' : 'Derma Sessions' }}</p><p class="dr-val text-[#1B365D]">{{ summary.derma_sessions }}</p></div>
            <div class="dr-stat dr-stagger" style="--i:3"><div class="dr-bar bg-gradient-to-r from-[#1B365D] to-[#22406F]"></div><p class="dr-label">{{ isRtl ? 'جلسات تجميل' : 'Cosmetic Sessions' }}</p><p class="dr-val text-[#1B365D]">{{ summary.cosmetic_sessions }}</p></div>
            <div class="dr-stat dr-stagger" style="--i:4"><div class="dr-bar bg-gradient-to-r from-emerald-500 to-emerald-600"></div><p class="dr-label">{{ isRtl ? 'مبيعات الباقات' : 'Package Sales' }}</p><p class="dr-val text-emerald-600 text-lg">{{ formatCurrency(summary.package_sales) }}</p></div>
        </div>

        <!-- Revenue split -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <div class="dr-card dr-stagger" style="--i:5">
                <h2 class="dr-h2">{{ isRtl ? 'تفصيل الإيراد' : 'Revenue Breakdown' }}</h2>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-sm"><span class="text-gray-600">{{ isRtl ? 'جلسات جلدية' : 'Derma sessions' }}</span><span class="font-bold text-[#1B365D]">{{ formatCurrency(summary.derma_revenue) }}</span></div>
                    <div class="flex items-center justify-between text-sm"><span class="text-gray-600">{{ isRtl ? 'جلسات تجميل (مفردة)' : 'Cosmetic (à-la-carte)' }}</span><span class="font-bold text-[#1B365D]">{{ formatCurrency(summary.cosmetic_revenue) }}</span></div>
                    <div class="flex items-center justify-between text-sm"><span class="text-gray-600">{{ isRtl ? 'مبيعات الباقات' : 'Package sales' }}</span><span class="font-bold text-emerald-600">{{ formatCurrency(summary.package_sales) }}</span></div>
                    <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-100"><span class="font-bold text-gray-800">{{ isRtl ? 'الإجمالي' : 'Total' }}</span><span class="font-extrabold text-[#8B7043]">{{ formatCurrency(summary.total_revenue) }}</span></div>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-gray-50">
                    <div class="text-center"><p class="text-[11px] text-gray-400">{{ isRtl ? 'كورسات نشطة / مكتملة' : 'Courses active / done' }}</p><p class="font-bold text-[#1B365D]">{{ plans.active }} / {{ plans.completed }}</p></div>
                    <div class="text-center"><p class="text-[11px] text-gray-400">{{ isRtl ? 'باقات نشطة / مكتملة' : 'Packages active / done' }}</p><p class="font-bold text-[#1B365D]">{{ packages.active }} / {{ packages.completed }}</p></div>
                </div>
            </div>

            <!-- Sessions by type -->
            <div class="dr-card dr-stagger" style="--i:6">
                <h2 class="dr-h2">{{ isRtl ? 'الجلسات الجلدية حسب النوع' : 'Derma Sessions by Type' }}</h2>
                <div v-if="byType.length" class="space-y-3">
                    <div v-for="t in byType" :key="t.session_type">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700">{{ typeLabel(t.session_type) }}</span>
                            <span class="text-gray-400">{{ t.total }} · {{ formatCurrency(t.revenue) }}</span>
                        </div>
                        <div class="bg-gray-100 rounded-full h-2"><div class="h-2 rounded-full bg-gradient-to-r from-[#1B365D] to-[#22406F]" :style="{ width: (t.total / maxType * 100) + '%' }"></div></div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا بيانات في هذه الفترة' : 'No data in this period' }}</p>
            </div>
        </div>

        <!-- Top procedures + conditions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="dr-card dr-stagger" style="--i:7">
                <h2 class="dr-h2">{{ isRtl ? 'أكثر إجراءات التجميل' : 'Top Cosmetic Procedures' }}</h2>
                <div v-if="topProcedures.length" class="space-y-3">
                    <div v-for="(p, i) in topProcedures" :key="i">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700">{{ procName(p) }}</span>
                            <span class="text-gray-400">{{ p.total }} · {{ formatCurrency(p.revenue) }}</span>
                        </div>
                        <div class="bg-gray-100 rounded-full h-2"><div class="h-2 rounded-full bg-gradient-to-r from-[#C4A265] to-[#8B7043]" :style="{ width: (p.total / maxProc * 100) + '%' }"></div></div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا بيانات في هذه الفترة' : 'No data in this period' }}</p>
            </div>

            <div class="dr-card dr-stagger" style="--i:8">
                <h2 class="dr-h2">{{ isRtl ? 'الحالات الجلدية النشطة' : 'Active Skin Conditions' }}</h2>
                <div v-if="conditionMix.length" class="flex flex-wrap gap-2">
                    <span v-for="c in conditionMix" :key="c.category" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-gray-100 text-sm">
                        <span class="font-medium text-gray-700">{{ condLabel(c.category) }}</span>
                        <span class="px-1.5 rounded-full bg-[#1B365D] text-white text-[11px] font-bold">{{ c.total }}</span>
                    </span>
                </div>
                <p v-else class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا حالات نشطة' : 'No active conditions' }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dr-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px; background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%); box-shadow: 0 18px 40px -20px rgba(27,54,93,0.5); }
.dr-hero-orb { position: absolute; top: -80px; inset-inline-end: -60px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(196,162,101,0.25), transparent 70%); filter: blur(20px); pointer-events: none; }
.dr-hero-badge { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(196,162,101,0.35); }
.dr-date { background: rgba(255,255,255,0.9); border: 0; border-radius: 8px; padding: 5px 8px; font-size: 12px; }
.dr-apply { padding: 7px 16px; border-radius: 8px; background: linear-gradient(135deg, #C4A265, #8B7043); color: #fff; font-size: 12px; font-weight: 700; }

.dr-stat { position: relative; background: #fff; border: 1px solid rgba(196,162,101,0.16); border-radius: 14px; padding: 16px; overflow: hidden; }
.dr-bar { position: absolute; top: 0; inset-inline: 0; height: 3px; }
.dr-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; }
.dr-val { font-size: 24px; font-weight: 800; margin-top: 4px; font-variant-numeric: tabular-nums; line-height: 1.1; }

.dr-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; padding: 18px; box-shadow: 0 10px 30px -24px rgba(27,54,93,0.2); }
.dr-h2 { font-size: 14px; font-weight: 800; color: #1B365D; margin-bottom: 14px; }

.dr-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0) * 60ms + 80ms); }
.is-mounted .dr-stagger { opacity: 1; transform: translateY(0); }
@media (prefers-reduced-motion: reduce) { .dr-stagger { transition-duration: 0.01s; transition-delay: 0s; } }
</style>
