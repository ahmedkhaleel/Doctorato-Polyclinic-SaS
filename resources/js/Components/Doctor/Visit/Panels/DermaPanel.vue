<script setup>
/**
 * DermaPanel — dermatology / cosmetic clinical cockpit for the visit page.
 * Surfaces the active treatment course (with session progress), the sessions
 * logged on / around this visit, and deep links into the full derma record.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency.js';

const props = defineProps({
    visit: { type: Object, required: true },
    isRtl: { type: Boolean, default: true },
    mounted: { type: Boolean, default: false },
    context: { type: String, default: 'doctor' },
    patientHref: { type: String, default: null },
    dermaActivePlan: { type: Object, default: null },
    dermaSessions: { type: Array, default: () => [] },
});

const { formatCurrency } = useCurrency();

const ACCENT = '#8B5CF6';

const isAdmin = computed(() => props.context === 'admin');
const recordHref = computed(() => isAdmin.value ? (props.patientHref || '#') : `/doctor/derma/patients/${props.visit.patient_id}`);
const plansHref = computed(() => isAdmin.value ? (props.patientHref || '#') : '/doctor/derma/treatment-plans');

const TYPE_LABELS = {
    laser: { ar: 'ليزر', en: 'Laser' },
    peel: { ar: 'تقشير', en: 'Peel' },
    phototherapy: { ar: 'علاج ضوئي', en: 'Phototherapy' },
    injection: { ar: 'حقن', en: 'Injection' },
    cryotherapy: { ar: 'تبريد', en: 'Cryotherapy' },
    other: { ar: 'أخرى', en: 'Other' },
};

function typeLabel(t) {
    const o = TYPE_LABELS[t];
    return o ? (props.isRtl ? o.ar : o.en) : (t || '-');
}

const plan = computed(() => props.dermaActivePlan);
const progress = computed(() => Math.min(100, Math.max(0, Number(plan.value?.progress_percentage ?? 0))));
const completed = computed(() => Number(plan.value?.completed_sessions ?? 0));
const estimated = computed(() => Number(plan.value?.estimated_sessions ?? 0));
const remaining = computed(() => Math.max(0, Number(plan.value?.sessions_remaining ?? (estimated.value - completed.value))));

const sessionsThisVisit = computed(() => (props.dermaSessions || []).filter(s => s.visit_id === props.visit.id));
const totalSessions = computed(() => (props.dermaSessions || []).length);

const nextDate = computed(() => {
    const withNext = (props.dermaSessions || []).find(s => s.next_session_date);
    return plan.value?.next_session_date || withNext?.next_session_date || null;
});

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString(props.isRtl ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const hasContent = computed(() => plan.value || totalSessions.value > 0);
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
    >
        <!-- Header band (violet specialty accent) -->
        <div class="relative overflow-hidden px-4 sm:px-6 py-4 border-b border-gray-100"
            :style="`background: linear-gradient(115deg, ${ACCENT}14 0%, ${ACCENT}05 60%, transparent 100%)`"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="`background:${ACCENT}1A`">
                        <svg class="w-4 h-4" :style="`color:${ACCENT}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الجلدية والتجميل' : 'Dermatology & Aesthetics' }}</h3>
                </div>
                <Link :href="recordHref"
                    class="inline-flex items-center gap-1 text-xs font-medium transition-colors"
                    :style="`color:${ACCENT}`">
                    {{ isRtl ? 'الملف الكامل' : 'Full Record' }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'" /></svg>
                </Link>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-6">
            <!-- Empty state -->
            <div v-if="!hasContent" class="text-center py-8">
                <div class="w-12 h-12 mx-auto rounded-xl flex items-center justify-center mb-3 border" :style="`background:${ACCENT}0D; border-color:${ACCENT}33`">
                    <svg class="w-6 h-6" :style="`color:${ACCENT}99`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-sm text-gray-400 mb-3">{{ isRtl ? 'لا توجد خطة علاج أو جلسات بعد' : 'No treatment plan or sessions yet' }}</p>
                <Link :href="recordHref"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white rounded-lg transition-all hover:-translate-y-0.5"
                    :style="`background:${ACCENT}`">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'بدء خطة علاج' : 'Start a Plan' }}
                </Link>
            </div>

            <template v-else>
                <!-- Stat chips -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl p-3 border text-center" :style="`background:${ACCENT}0D; border-color:${ACCENT}26`">
                        <p class="text-lg font-extrabold" :style="`color:${ACCENT}`">{{ completed }}<span class="text-xs text-gray-400 font-medium">/{{ estimated || '—' }}</span></p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'جلسات مكتملة' : 'Sessions done' }}</p>
                    </div>
                    <div class="rounded-xl p-3 border text-center bg-slate-50 border-slate-200">
                        <p class="text-lg font-extrabold text-[#1B365D]">{{ remaining }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'متبقية' : 'Remaining' }}</p>
                    </div>
                    <div class="rounded-xl p-3 border text-center bg-emerald-50 border-emerald-100">
                        <p class="text-xs font-bold text-emerald-700 leading-tight pt-1">{{ fmtDate(nextDate) }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'الجلسة القادمة' : 'Next session' }}</p>
                    </div>
                </div>

                <!-- Active plan card -->
                <div v-if="plan" class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full" :style="`background:${ACCENT}1A; color:${ACCENT}`">{{ typeLabel(plan.session_type) }}</span>
                            <h4 class="text-sm font-bold text-gray-800">{{ isRtl ? (plan.title_ar || plan.title_en) : (plan.title_en || plan.title_ar) }}</h4>
                        </div>
                        <span class="text-xs font-bold" :style="`color:${ACCENT}`">{{ progress }}%</span>
                    </div>
                    <!-- Progress bar -->
                    <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                            :style="`width:${mounted ? progress : 0}%; background:linear-gradient(90deg, ${ACCENT}, ${ACCENT}99)`"></div>
                    </div>
                    <div class="flex items-center justify-between mt-2 text-[11px] text-gray-500">
                        <span>{{ isRtl ? 'تبدأ:' : 'Started:' }} {{ fmtDate(plan.start_date) }}</span>
                        <span v-if="plan.interval_days">{{ isRtl ? `كل ${plan.interval_days} يوم` : `Every ${plan.interval_days}d` }}</span>
                    </div>
                </div>

                <!-- Sessions timeline -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">
                        {{ isRtl ? 'سجل الجلسات' : 'Session Log' }}
                        <span v-if="sessionsThisVisit.length" class="ml-1 normal-case text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :style="`background:${ACCENT}1A; color:${ACCENT}`">{{ isRtl ? `${sessionsThisVisit.length} في هذه الزيارة` : `${sessionsThisVisit.length} this visit` }}</span>
                    </h4>
                    <div v-if="totalSessions" class="space-y-2">
                        <div v-for="s in dermaSessions.slice(0, 8)" :key="s.id"
                            class="flex items-center gap-3 rounded-xl border p-3 transition-all"
                            :class="s.visit_id === visit.id ? 'border-transparent' : 'border-gray-100 hover:bg-gray-50'"
                            :style="s.visit_id === visit.id ? `background:${ACCENT}0D; box-shadow: inset 0 0 0 1px ${ACCENT}33` : ''">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 text-xs font-bold" :style="`background:${ACCENT}1A; color:${ACCENT}`">
                                {{ s.session_number || '•' }}<span v-if="s.total_sessions" class="text-[9px] text-gray-400">/{{ s.total_sessions }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-semibold text-gray-800">{{ typeLabel(s.session_type) }}</span>
                                    <span v-if="s.area_treated" class="text-[11px] text-gray-500">· {{ s.area_treated }}</span>
                                    <span v-if="s.visit_id === visit.id" class="text-[9px] font-bold px-1.5 py-0.5 rounded-full" :style="`background:${ACCENT}; color:#fff`">{{ isRtl ? 'هذه الزيارة' : 'This visit' }}</span>
                                </div>
                                <p v-if="s.product_used" class="text-[11px] text-gray-400 truncate">{{ s.product_used }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p v-if="s.cost" class="text-xs font-bold" :style="`color:${ACCENT}`">{{ formatCurrency(s.cost) }}</p>
                                <p class="text-[10px] text-gray-400">{{ fmtDate(s.completed_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد جلسات مسجّلة' : 'No sessions recorded' }}</p>
                </div>

                <!-- Quick links -->
                <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                    <Link :href="recordHref" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-all" :style="`color:${ACCENT}; background:${ACCENT}0D; border-color:${ACCENT}33`">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        {{ isRtl ? 'ملف المريض' : 'Patient Record' }}
                    </Link>
                    <Link :href="plansHref" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        {{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}
                    </Link>
                </div>
            </template>
        </div>
    </div>
</template>
