<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    plans: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    stats: { type: Object, default: () => ({ total: 0, active: 0, completed: 0 }) },
});

function pct(p) {
    const est = Number(p.estimated_sessions) || 0;
    if (!est) return 0;
    return Math.min(Math.round(((Number(p.completed_sessions) || 0) / est) * 100), 100);
}
function statusMeta(p) {
    const done = (Number(p.completed_sessions) || 0) >= (Number(p.estimated_sessions) || 0) && (Number(p.estimated_sessions) || 0) > 0;
    if (done) return { cls: 'bg-emerald-50 text-emerald-700', label: isRtl.value ? 'مكتملة' : 'Completed' };
    if ((Number(p.completed_sessions) || 0) > 0) return { cls: 'bg-violet-50 text-violet-700', label: isRtl.value ? 'جارية' : 'In progress' };
    return { cls: 'bg-amber-50 text-amber-700', label: isRtl.value ? 'مخطّطة' : 'Planned' };
}
function fmtDate(d) {
    if (!d) return null;
    try { return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }); }
    catch { return d; }
}
const chips = computed(() => [
    { v: props.stats.total || 0, l: isRtl.value ? 'الإجمالي' : 'Total', c: '#1B365D' },
    { v: props.stats.active || 0, l: isRtl.value ? 'نشطة' : 'Active', c: '#8B5CF6' },
    { v: props.stats.completed || 0, l: isRtl.value ? 'مكتملة' : 'Completed', c: '#10B981' },
]);
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Header band -->
        <header class="relative overflow-hidden rounded-3xl px-6 py-7 md:px-8 md:py-8 text-white shadow-lg shadow-violet-900/10"
                style="background: linear-gradient(115deg,#1B365D 0%,#3b2f6b 55%,#8B5CF6 130%)">
            <div class="absolute -top-16 ltr:-right-10 rtl:-left-10 w-56 h-56 rounded-full blur-2xl opacity-30" style="background:#C4A265"></div>
            <div class="absolute inset-0 opacity-[0.07]" style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:22px 22px"></div>
            <div class="relative z-10 flex items-end justify-between flex-wrap gap-5">
                <div>
                    <p class="text-white/60 text-xs font-semibold tracking-wider uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'Dermatology & Cosmetic' }}</p>
                    <h1 class="text-2xl md:text-3xl font-extrabold mt-1.5">{{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}</h1>
                    <p class="text-white/70 mt-2 text-sm max-w-md">{{ isRtl ? 'تتبّع تقدّم الجلسات لكل خطة علاج جلدي أو تجميلي.' : 'Track session progress across every derma & cosmetic course you manage.' }}</p>
                </div>
                <div class="flex items-center gap-2.5">
                    <div v-for="c in chips" :key="c.l" class="text-center bg-white/10 backdrop-blur rounded-2xl px-4 py-2.5 min-w-[78px]">
                        <p class="text-2xl font-extrabold tabular-nums leading-none">{{ c.v }}</p>
                        <p class="text-[11px] text-white/70 mt-1">{{ c.l }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Plan cards -->
        <div v-if="plans.data.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="(pl, i) in plans.data" :key="pl.id"
                 class="group relative bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-xl hover:shadow-violet-900/5 transition-all duration-300 derma-reveal"
                 :style="{ animationDelay: `${Math.min(i, 12) * 45}ms` }">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-violet-500 to-[#1B365D] text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-inner">
                            {{ (pl.patient?.full_name || '?').charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-800 truncate">{{ isRtl ? (pl.title_ar || pl.title_en) : (pl.title_en || pl.title_ar) }}</p>
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ pl.patient?.full_name }}<span v-if="pl.patient?.phone"> · {{ pl.patient.phone }}</span></p>
                        </div>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full" :class="statusMeta(pl).cls">{{ statusMeta(pl).label }}</span>
                </div>

                <div class="mt-5">
                    <div class="flex justify-between items-end text-xs mb-1.5">
                        <span class="text-gray-500 font-medium">{{ isRtl ? 'التقدّم' : 'Progress' }}<span v-if="pl.session_type" class="text-gray-300"> · {{ pl.session_type }}</span></span>
                        <span class="font-bold text-gray-700 tabular-nums">{{ pl.completed_sessions ?? 0 }}<span class="text-gray-300"> / {{ pl.estimated_sessions ?? '—' }}</span></span>
                    </div>
                    <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-violet-400 to-[#1B365D] transition-all duration-700 ease-out" :style="{ width: pct(pl) + '%' }"></div>
                    </div>
                    <div class="flex items-center justify-between mt-3 text-[11px] text-gray-400">
                        <span class="font-bold text-violet-600 tabular-nums">{{ pct(pl) }}%</span>
                        <span v-if="pl.start_date" class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ fmtDate(pl.start_date) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state (premium) -->
        <div v-else class="relative overflow-hidden bg-white rounded-3xl border border-gray-100 shadow-sm py-16 px-6 text-center">
            <div class="absolute inset-0 opacity-[0.04]" style="background-image:radial-gradient(circle at 1px 1px,#8B5CF6 1px,transparent 0);background-size:20px 20px"></div>
            <div class="relative">
                <div class="mx-auto w-20 h-20 rounded-3xl bg-gradient-to-br from-violet-100 to-violet-50 flex items-center justify-center text-violet-500 shadow-inner">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-7-4h.01M9 16h.01"/></svg>
                </div>
                <h3 class="mt-5 text-lg font-bold text-gray-800">{{ isRtl ? 'لا توجد خطط علاج' : 'No treatment plans yet' }}</h3>
                <p class="mt-2 text-sm text-gray-400 max-w-sm mx-auto">{{ isRtl ? 'أنشئ خطة علاج من ملف المريض لتتبّع تقدّم الجلسات هنا.' : 'Create a plan from a patient file to track session progress here.' }}</p>
                <Link :href="route('doctor.derma.patients.index')" class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm hover:opacity-90 transition" style="background:linear-gradient(120deg,#1B365D,#8B5CF6 200%)">
                    {{ isRtl ? 'عرض المرضى' : 'View patients' }}
                </Link>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="plans.links && plans.links.length > 3" class="flex flex-wrap gap-1 justify-center">
            <template v-for="(l, i) in plans.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label" preserve-scroll
                      class="px-3.5 py-1.5 rounded-lg text-sm transition" :class="l.active ? 'bg-[#1B365D] text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'" />
                <span v-else v-html="l.label" class="px-3.5 py-1.5 rounded-lg text-sm text-gray-300"></span>
            </template>
        </div>
    </div>
</template>

<style scoped>
@keyframes dermaReveal { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.derma-reveal { animation: dermaReveal .5s cubic-bezier(.22,1,.36,1) both; }
@media (prefers-reduced-motion: reduce) { .derma-reveal { animation: none; } }
</style>
