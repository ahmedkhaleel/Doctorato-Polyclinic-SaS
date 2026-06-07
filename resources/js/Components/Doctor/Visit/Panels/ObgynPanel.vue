<script setup>
/**
 * ObgynPanel — obstetric clinical cockpit for the visit page.
 * Surfaces the active pregnancy (gestational age, EDD, G/P, risk), the latest
 * antenatal lab results (abnormal values flagged), and deep links into the
 * pregnancy record. Read-only summary; deep actions live on the OB/GYN pages.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    visit: { type: Object, required: true },
    isRtl: { type: Boolean, default: true },
    mounted: { type: Boolean, default: false },
    context: { type: String, default: 'doctor' },
    patientHref: { type: String, default: null },
    obgynPregnancy: { type: Object, default: null },
    obgynLabTests: { type: Array, default: () => [] },
});

const ACCENT = '#DB2777';
const TERM_WEEKS = 40;

const isAdmin = computed(() => props.context === 'admin');
const pregHref = computed(() => isAdmin.value ? (props.patientHref || '#') : `/doctor/obgyn/pregnancies/${props.obgynPregnancy?.id}`);
const pregListHref = computed(() => isAdmin.value ? (props.patientHref || '#') : '/doctor/obgyn/pregnancies');
const ancCardHref = computed(() => isAdmin.value ? (props.patientHref || '#') : `/doctor/obgyn/pregnancies/${props.obgynPregnancy?.id}/antenatal-card`);

const preg = computed(() => props.obgynPregnancy);
const gaWeeks = computed(() => Number(preg.value?.gestational_weeks ?? 0));
const gaDays = computed(() => Number(preg.value?.gestational_days ?? 0));
const gaLabel = computed(() => preg.value?.gestational_weeks != null ? `${gaWeeks.value}w ${gaDays.value}d` : '—');
const gaProgress = computed(() => Math.min(100, Math.max(0, Math.round((gaWeeks.value / TERM_WEEKS) * 100))));
const trimester = computed(() => {
    if (!preg.value) return null;
    if (gaWeeks.value < 14) return props.isRtl ? 'الثلث الأول' : '1st trimester';
    if (gaWeeks.value < 28) return props.isRtl ? 'الثلث الثاني' : '2nd trimester';
    return props.isRtl ? 'الثلث الثالث' : '3rd trimester';
});

const riskFactors = computed(() => {
    const r = preg.value?.risk_factors;
    if (Array.isArray(r)) return r;
    if (typeof r === 'string' && r) return [r];
    return [];
});

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString(props.isRtl ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
    >
        <!-- Header band (pink specialty accent) -->
        <div class="relative overflow-hidden px-4 sm:px-6 py-4 border-b border-gray-100"
            :style="`background: linear-gradient(115deg, ${ACCENT}14 0%, ${ACCENT}05 60%, transparent 100%)`"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="`background:${ACCENT}1A`">
                        <svg class="w-4 h-4" :style="`color:${ACCENT}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'النساء والتوليد' : 'Obstetrics & Gynecology' }}</h3>
                </div>
                <Link v-if="preg" :href="pregHref"
                    class="inline-flex items-center gap-1 text-xs font-medium transition-colors" :style="`color:${ACCENT}`">
                    {{ isRtl ? 'ملف الحمل' : 'Pregnancy Record' }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'" /></svg>
                </Link>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-6">
            <!-- Empty state -->
            <div v-if="!preg" class="text-center py-8">
                <div class="w-12 h-12 mx-auto rounded-xl flex items-center justify-center mb-3 border" :style="`background:${ACCENT}0D; border-color:${ACCENT}33`">
                    <svg class="w-6 h-6" :style="`color:${ACCENT}99`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-12h10M9 13h6" /></svg>
                </div>
                <p class="text-sm text-gray-400 mb-3">{{ isRtl ? 'لا يوجد حمل نشط مسجّل' : 'No active pregnancy on record' }}</p>
                <Link :href="pregListHref"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white rounded-lg transition-all hover:-translate-y-0.5" :style="`background:${ACCENT}`">
                    {{ isRtl ? 'سجل حمل / مراجعة نسائية' : 'Register pregnancy / gyn visit' }}
                </Link>
            </div>

            <template v-else>
                <!-- High-risk banner -->
                <div v-if="preg.is_high_risk" class="rounded-xl border-2 px-4 py-3 flex items-start gap-2"
                    style="border-color:#FCA5A5; background:linear-gradient(90deg,#FEF2F2,#FFF7ED)">
                    <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                    <div>
                        <p class="text-sm font-bold text-red-800">{{ isRtl ? 'حمل عالي الخطورة' : 'High-risk pregnancy' }}</p>
                        <div v-if="riskFactors.length" class="flex flex-wrap gap-1.5 mt-1.5">
                            <span v-for="(f, i) in riskFactors" :key="i" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700 border border-red-200">{{ f }}</span>
                        </div>
                    </div>
                </div>

                <!-- Stat chips -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl p-3 border text-center" :style="`background:${ACCENT}0D; border-color:${ACCENT}26`">
                        <p class="text-lg font-extrabold" :style="`color:${ACCENT}`">{{ gaLabel }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ trimester || (isRtl ? 'عمر الحمل' : 'Gestation') }}</p>
                    </div>
                    <div class="rounded-xl p-3 border text-center bg-slate-50 border-slate-200">
                        <p class="text-lg font-extrabold text-[#1B365D]">G{{ preg.gravida ?? '—' }} P{{ preg.para ?? '—' }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'حمل / ولادة' : 'Gravida / Para' }}</p>
                    </div>
                    <div class="rounded-xl p-3 border text-center bg-emerald-50 border-emerald-100">
                        <p class="text-xs font-bold text-emerald-700 leading-tight pt-1">{{ fmtDate(preg.edd) }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'موعد الولادة' : 'EDD' }}</p>
                    </div>
                </div>

                <!-- Gestation progress 0-40w -->
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-2 text-[11px] text-gray-500">
                        <span>{{ isRtl ? 'آخر دورة:' : 'LMP:' }} {{ fmtDate(preg.lmp) }}</span>
                        <span class="font-bold" :style="`color:${ACCENT}`">{{ gaWeeks }}/{{ TERM_WEEKS }}w</span>
                    </div>
                    <div class="relative h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                            :style="`width:${mounted ? gaProgress : 0}%; background:linear-gradient(90deg, ${ACCENT}, ${ACCENT}99)`"></div>
                    </div>
                    <div class="flex justify-between mt-1 text-[9px] text-gray-300 font-medium">
                        <span>0</span><span>13</span><span>27</span><span>40</span>
                    </div>
                    <div v-if="preg.blood_group || preg.rh_factor" class="mt-2 pt-2 border-t border-gray-50 text-[11px] text-gray-500">
                        {{ isRtl ? 'فصيلة الدم:' : 'Blood group:' }}
                        <strong class="text-gray-800">{{ preg.blood_group || '—' }} {{ preg.rh_factor || '' }}</strong>
                    </div>
                </div>

                <!-- Labs -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">{{ isRtl ? 'أحدث التحاليل' : 'Recent Labs' }}</h4>
                    <div v-if="obgynLabTests.length" class="space-y-1.5">
                        <div v-for="lab in obgynLabTests" :key="lab.id"
                            class="flex items-center justify-between rounded-lg border px-3 py-2 text-xs"
                            :class="lab.is_abnormal ? 'border-red-200 bg-red-50' : 'border-gray-100'">
                            <div class="flex items-center gap-2 min-w-0">
                                <span v-if="lab.is_abnormal" class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0 animate-pulse"></span>
                                <span class="font-semibold text-gray-700 truncate">{{ lab.test_type }}</span>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="font-mono" :class="lab.is_abnormal ? 'text-red-700 font-bold' : 'text-gray-800'">{{ lab.value }} {{ lab.unit }}</span>
                                <span v-if="lab.reference_range" class="text-[10px] text-gray-400 hidden sm:inline">({{ lab.reference_range }})</span>
                                <span class="text-[10px] text-gray-400">{{ fmtDate(lab.result_date) }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-4">{{ isRtl ? 'لا توجد تحاليل مسجّلة' : 'No labs recorded' }}</p>
                </div>

                <!-- Quick links -->
                <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                    <Link :href="pregHref" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-all" :style="`color:${ACCENT}; background:${ACCENT}0D; border-color:${ACCENT}33`">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        {{ isRtl ? 'بطاقة الحمل' : 'ANC Card' }}
                    </Link>
                    <Link :href="ancCardHref" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        {{ isRtl ? 'طباعة البطاقة' : 'Print card' }}
                    </Link>
                </div>
            </template>
        </div>
    </div>
</template>
