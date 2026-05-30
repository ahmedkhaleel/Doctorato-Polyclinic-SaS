<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#DB2777';

const props = defineProps({
    pregnancy: { type: Object, default: null },
    history: { type: Array, default: () => [] },
});

const progress = computed(() => {
    const w = props.pregnancy?.ga_weeks;
    return w ? Math.min(Math.round((w / 40) * 100), 100) : 0;
});
function trimesterLabel(t) {
    if (!t) return '';
    if (isRtl.value) return t === 1 ? 'الثلث الأول' : t === 2 ? 'الثلث الثاني' : 'الثلث الثالث';
    return t === 1 ? '1st trimester' : t === 2 ? '2nd trimester' : '3rd trimester';
}
function dueText() {
    const d = props.pregnancy?.days_until_edd;
    if (d === null || d === undefined) return '';
    if (d < 0) return isRtl.value ? `تجاوز الموعد بـ ${Math.abs(d)} يوم` : `${Math.abs(d)} days past due`;
    if (d === 0) return isRtl.value ? 'اليوم المتوقّع!' : 'Due today!';
    return isRtl.value ? `باقٍ ${d} يوم على الولادة` : `${d} days to go`;
}
function fmt(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'; }
function modeLabel(m) {
    const map = isRtl.value ? { nvd: 'طبيعية', cesarean: 'قيصرية', instrumental: 'بمساعدة' } : { nvd: 'Vaginal', cesarean: 'Cesarean', instrumental: 'Instrumental' };
    return map[m] || m;
}
</script>

<template>
    <div class="max-w-3xl mx-auto space-y-6 py-2" :dir="isRtl ? 'rtl' : 'ltr'">
        <h1 class="text-xl font-bold text-[#1B365D]">{{ isRtl ? 'متابعة حملي' : 'My Pregnancy' }}</h1>

        <!-- Active pregnancy -->
        <template v-if="pregnancy">
            <div class="relative overflow-hidden rounded-3xl p-6 text-white shadow-lg" style="background: linear-gradient(135deg,#1B365D,#24456f 55%,#DB2777 170%)">
                <div class="absolute -top-8 end-4 w-36 h-36 rounded-full opacity-15" style="background:#C4A265"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <p class="text-white/70 text-sm">{{ isRtl ? 'عمر الحمل' : 'Gestational age' }}</p>
                            <p class="text-3xl font-bold mt-1">{{ pregnancy.ga_label || '—' }}</p>
                            <p class="text-white/80 text-sm mt-1">{{ trimesterLabel(pregnancy.trimester) }}</p>
                        </div>
                        <div class="text-center">
                            <div class="w-20 h-20 rounded-full bg-white/15 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold">{{ progress }}%</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 h-2.5 rounded-full bg-white/20 overflow-hidden">
                        <div class="h-full rounded-full bg-white transition-all duration-700" :style="{ width: progress + '%' }"></div>
                    </div>
                    <div class="flex items-center justify-between mt-4 text-sm">
                        <div>
                            <p class="text-white/60 text-xs">{{ isRtl ? 'الموعد المتوقّع' : 'Due date' }}</p>
                            <p class="font-semibold" dir="ltr">{{ pregnancy.edd || '—' }}</p>
                        </div>
                        <span class="px-3 py-1.5 rounded-full bg-white/15 font-semibold">{{ dueText() }}</span>
                    </div>
                </div>
            </div>

            <!-- Next visit + doctor -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center" :style="{ color: ACCENT }">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">{{ isRtl ? 'الزيارة القادمة' : 'Next visit' }}</p>
                        <p class="font-semibold text-gray-800" dir="ltr">{{ fmt(pregnancy.next_visit) }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#1B365D]/10 flex items-center justify-center text-[#1B365D]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">{{ isRtl ? 'الطبيبة المتابعة' : 'My doctor' }}</p>
                        <p class="font-semibold text-gray-800">{{ isRtl ? pregnancy.doctor?.name_ar : pregnancy.doctor?.name_en }}</p>
                    </div>
                </div>
            </div>

            <!-- Antenatal timeline -->
            <div v-if="pregnancy.antenatal_visits.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'زيارات المتابعة' : 'Antenatal Visits' }}</h2>
                <ul class="divide-y divide-gray-50">
                    <li v-for="(a, i) in pregnancy.antenatal_visits" :key="i" class="py-2.5 flex items-center justify-between text-sm">
                        <span class="text-gray-700" dir="ltr">{{ fmt(a.visit_date) }}</span>
                        <span class="text-gray-400 text-xs">
                            <template v-if="a.gestational_age_weeks">{{ a.gestational_age_weeks }}w</template>
                            <template v-if="a.blood_pressure"> · BP {{ a.blood_pressure }}</template>
                            <template v-if="a.weight_kg"> · {{ a.weight_kg }}kg</template>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Ultrasounds -->
            <div v-if="pregnancy.ultrasounds.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'فحوصات السونار' : 'Ultrasound Scans' }}</h2>
                <ul class="space-y-3">
                    <li v-for="(u, i) in pregnancy.ultrasounds" :key="i" class="rounded-xl bg-gray-50/70 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium" :style="{ color: ACCENT }">{{ u.scan_type }}</span>
                            <span class="text-gray-400 text-xs" dir="ltr">{{ fmt(u.scan_date) }}</span>
                        </div>
                        <p v-if="u.findings" class="text-xs text-gray-500 mt-1">{{ u.findings }}</p>
                    </li>
                </ul>
            </div>

            <!-- Lab results -->
            <div v-if="pregnancy.lab_tests.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'نتائج التحاليل' : 'Lab Results' }}</h2>
                <ul class="divide-y divide-gray-50">
                    <li v-for="(l, i) in pregnancy.lab_tests" :key="i" class="py-2.5 flex items-center justify-between text-sm">
                        <span class="text-gray-700">{{ l.test_type }}</span>
                        <span :class="l.is_abnormal ? 'text-red-600 font-semibold' : 'text-gray-600'">{{ l.value }} {{ l.unit }}</span>
                    </li>
                </ul>
            </div>
        </template>

        <!-- No active pregnancy -->
        <div v-else class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 text-center">
            <div class="w-16 h-16 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" :style="{ color: ACCENT }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
            </div>
            <p class="text-gray-500">{{ isRtl ? 'لا يوجد حمل نشط حالياً' : 'No active pregnancy at the moment' }}</p>
        </div>

        <!-- History -->
        <div v-if="history.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'السجل السابق' : 'Past History' }}</h2>
            <ul class="divide-y divide-gray-50">
                <li v-for="h in history" :key="h.id" class="py-2.5 flex items-center justify-between text-sm">
                    <span class="text-gray-600" dir="ltr">{{ fmt(h.lmp) }}</span>
                    <span v-if="h.delivery" class="text-gray-700">{{ modeLabel(h.delivery.delivery_mode) }} · <span dir="ltr">{{ fmt(h.delivery.delivery_date) }}</span></span>
                    <span v-else class="text-gray-400 text-xs">{{ h.status }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
