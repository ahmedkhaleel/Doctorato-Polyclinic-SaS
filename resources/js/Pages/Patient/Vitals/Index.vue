<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    latest:  Object,
    history: Array,
    series:  Object,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleString(isRtl.value ? 'ar-EG' : 'en-US',
            { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch { return d; }
}

// Tiny SVG line chart from a series of nullable numbers — handles
// gaps gracefully (null = no data point), normalises to viewbox.
function spark(values, height = 40, width = 280) {
    const points = values.map((v, i) => v != null ? { x: i, y: Number(v) } : null).filter(Boolean);
    if (points.length < 2) return { path: '', dots: [], min: 0, max: 0, last: points[0]?.y ?? null };
    const ys = points.map(p => p.y);
    const min = Math.min(...ys);
    const max = Math.max(...ys);
    const span = max - min || 1;
    const stepX = points.length > 1 ? width / (points.length - 1) : 0;
    const dots = points.map((p, i) => ({
        x: i * stepX,
        y: height - 6 - ((p.y - min) / span) * (height - 12),
        v: p.y,
    }));
    const path = dots.map((d, i) => (i === 0 ? `M ${d.x},${d.y}` : `L ${d.x},${d.y}`)).join(' ');
    return { path, dots, min, max, last: ys[ys.length - 1] };
}

const weightSpark = computed(() => spark(props.series?.weight || []));
const sugarSpark  = computed(() => spark(props.series?.sugar  || []));
// BP needs two lines (sys + dia)
const bpSysSpark  = computed(() => spark((props.series?.bp || []).map(b => b ? b[0] : null)));
const bpDiaSpark  = computed(() => spark((props.series?.bp || []).map(b => b ? b[1] : null)));

// BP category color
function bpCategory(sys, dia) {
    if (!sys || !dia) return null;
    if (sys >= 140 || dia >= 90) return { color: 'red',   label: isRtl.value ? 'مرتفع' : 'High' };
    if (sys >= 130 || dia >= 80) return { color: 'amber', label: isRtl.value ? 'مرتفع قليلاً' : 'Elevated' };
    if (sys < 90  || dia < 60)   return { color: 'blue',  label: isRtl.value ? 'منخفض' : 'Low' };
    return { color: 'emerald', label: isRtl.value ? 'طبيعي' : 'Normal' };
}

const latestBpCategory = computed(() => bpCategory(props.latest?.blood_pressure_systolic, props.latest?.blood_pressure_diastolic));

// BMI category
function bmiCategory(bmi) {
    if (!bmi) return null;
    const v = Number(bmi);
    if (v < 18.5) return { color: 'blue',    label: isRtl.value ? 'نقص وزن' : 'Underweight' };
    if (v < 25)   return { color: 'emerald', label: isRtl.value ? 'طبيعي' : 'Normal' };
    if (v < 30)   return { color: 'amber',   label: isRtl.value ? 'زيادة وزن' : 'Overweight' };
    return { color: 'red', label: isRtl.value ? 'سمنة' : 'Obese' };
}

const latestBmi = computed(() => bmiCategory(props.latest?.bmi));
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-[#1B365D] to-[#22406F] rounded-2xl p-6 md:p-8 mb-6 text-white relative overflow-hidden">
            <div class="absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/15 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-2">
                    <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                    <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">
                        {{ isRtl ? 'مؤشراتي الحيوية' : 'My Vitals' }}
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold mb-2">
                    {{ isRtl ? 'تتبّع رحلتك الصحية' : 'Track your health journey' }}
                </h1>
                <p class="text-sm text-white/70 max-w-xl">
                    {{ isRtl
                        ? 'كل القياسات التي يأخذها الفريق الطبي خلال زياراتك في مكان واحد.'
                        : 'Every measurement your medical team has taken across visits, in one place.' }}
                </p>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!latest" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="text-5xl mb-3">📊</div>
            <p class="text-sm text-gray-500">
                {{ isRtl ? 'لم تُسجَّل أي قياسات بعد' : 'No vitals recorded yet' }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                {{ isRtl ? 'ستظهر هنا بعد زيارتك القادمة' : 'They will appear here after your next visit' }}
            </p>
        </div>

        <div v-else class="space-y-5">
            <!-- Latest reading cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- BP -->
                <div class="bg-white rounded-2xl border p-4"
                     :class="latestBpCategory?.color === 'red'     ? 'border-red-200'     :
                             latestBpCategory?.color === 'amber'   ? 'border-amber-200'   :
                             latestBpCategory?.color === 'blue'    ? 'border-blue-200'    :
                             latestBpCategory?.color === 'emerald' ? 'border-emerald-200' : 'border-gray-100'">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'ضغط الدم' : 'Blood Pressure' }}</p>
                    <p v-if="latest.blood_pressure_systolic" class="text-2xl font-extrabold text-[#1B365D] mt-1 tabular-nums">
                        {{ latest.blood_pressure_systolic }}<span class="text-base font-light text-gray-400">/{{ latest.blood_pressure_diastolic }}</span>
                        <span class="text-[10px] font-normal text-gray-400 ms-1">mmHg</span>
                    </p>
                    <p v-else class="text-xl text-gray-300 mt-1">—</p>
                    <span v-if="latestBpCategory" class="inline-block mt-1 text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                          :class="`bg-${latestBpCategory.color}-50 text-${latestBpCategory.color}-700`">
                        {{ latestBpCategory.label }}
                    </span>
                </div>

                <!-- Heart Rate -->
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'النبض' : 'Heart Rate' }}</p>
                    <p v-if="latest.heart_rate" class="text-2xl font-extrabold text-rose-600 mt-1 tabular-nums">
                        {{ latest.heart_rate }} <span class="text-[10px] font-normal text-gray-400">bpm</span>
                    </p>
                    <p v-else class="text-xl text-gray-300 mt-1">—</p>
                </div>

                <!-- Weight + BMI -->
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'الوزن' : 'Weight' }}</p>
                    <p v-if="latest.weight" class="text-2xl font-extrabold text-[#1B365D] mt-1 tabular-nums">
                        {{ latest.weight }} <span class="text-[10px] font-normal text-gray-400">kg</span>
                    </p>
                    <p v-else class="text-xl text-gray-300 mt-1">—</p>
                    <span v-if="latestBmi" class="inline-block mt-1 text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                          :class="`bg-${latestBmi.color}-50 text-${latestBmi.color}-700`">
                        BMI {{ latest.bmi }} · {{ latestBmi.label }}
                    </span>
                </div>

                <!-- Blood Sugar -->
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'سكر الدم' : 'Blood Sugar' }}</p>
                    <p v-if="latest.blood_sugar" class="text-2xl font-extrabold text-amber-600 mt-1 tabular-nums">
                        {{ latest.blood_sugar }} <span class="text-[10px] font-normal text-gray-400">mg/dL</span>
                    </p>
                    <p v-else class="text-xl text-gray-300 mt-1">—</p>
                    <span v-if="latest.blood_sugar_type" class="inline-block mt-1 text-[10px] text-gray-500 italic">
                        {{ latest.blood_sugar_type }}
                    </span>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 px-1">
                {{ isRtl ? 'آخر قراءة:' : 'Last reading:' }} {{ fmtDate(latest.recorded_at) }}
            </p>

            <!-- Trend charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Weight trend -->
                <div v-if="weightSpark.path" class="bg-white rounded-2xl border border-gray-100 p-5">
                    <p class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">
                        {{ isRtl ? 'الوزن (kg)' : 'Weight (kg)' }}
                    </p>
                    <svg viewBox="0 0 280 50" class="w-full h-12" preserveAspectRatio="none">
                        <path :d="weightSpark.path" fill="none" stroke="#1B365D" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                    </svg>
                    <div class="flex justify-between text-[10px] text-gray-400 mt-1 tabular-nums">
                        <span>min: {{ weightSpark.min.toFixed(1) }}</span>
                        <span>max: {{ weightSpark.max.toFixed(1) }}</span>
                    </div>
                </div>

                <!-- BP trend (sys + dia) -->
                <div v-if="bpSysSpark.path" class="bg-white rounded-2xl border border-gray-100 p-5">
                    <p class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">
                        {{ isRtl ? 'ضغط الدم' : 'Blood Pressure' }}
                    </p>
                    <svg viewBox="0 0 280 50" class="w-full h-12" preserveAspectRatio="none">
                        <path :d="bpSysSpark.path" fill="none" stroke="#dc2626" stroke-width="2" />
                        <path :d="bpDiaSpark.path" fill="none" stroke="#1B365D" stroke-width="2" stroke-dasharray="3 3" />
                    </svg>
                    <div class="flex justify-between text-[10px] text-gray-400 mt-1 tabular-nums">
                        <span><span class="inline-block w-2 h-0.5 bg-red-500 align-middle"></span> sys</span>
                        <span><span class="inline-block w-2 h-0.5 bg-[#1B365D] align-middle"></span> dia</span>
                    </div>
                </div>

                <!-- Sugar trend -->
                <div v-if="sugarSpark.path" class="bg-white rounded-2xl border border-gray-100 p-5">
                    <p class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">
                        {{ isRtl ? 'سكر الدم' : 'Blood Sugar' }}
                    </p>
                    <svg viewBox="0 0 280 50" class="w-full h-12" preserveAspectRatio="none">
                        <path :d="sugarSpark.path" fill="none" stroke="#d97706" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                    </svg>
                    <div class="flex justify-between text-[10px] text-gray-400 mt-1 tabular-nums">
                        <span>min: {{ sugarSpark.min.toFixed(0) }}</span>
                        <span>max: {{ sugarSpark.max.toFixed(0) }}</span>
                    </div>
                </div>
            </div>

            <!-- History table -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'سجل القياسات' : 'Measurement history' }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr class="text-[10px] uppercase tracking-wider text-gray-500">
                                <th class="text-start px-4 py-3">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                <th class="text-end px-4 py-3">BP</th>
                                <th class="text-end px-4 py-3 hidden sm:table-cell">HR</th>
                                <th class="text-end px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'الوزن' : 'Weight' }}</th>
                                <th class="text-end px-4 py-3 hidden md:table-cell">BMI</th>
                                <th class="text-end px-4 py-3 hidden md:table-cell">{{ isRtl ? 'السكر' : 'Sugar' }}</th>
                                <th class="text-end px-4 py-3 hidden lg:table-cell">{{ isRtl ? 'الحرارة' : 'Temp' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="r in history" :key="r.id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 text-xs text-gray-700 tabular-nums">{{ fmtDate(r.recorded_at) }}</td>
                                <td class="px-4 py-3 text-end font-mono text-xs">
                                    <span v-if="r.bp_systolic">{{ r.bp_systolic }}/{{ r.bp_diastolic }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-end text-xs hidden sm:table-cell">{{ r.heart_rate || '—' }}</td>
                                <td class="px-4 py-3 text-end text-xs tabular-nums hidden sm:table-cell">{{ r.weight || '—' }}</td>
                                <td class="px-4 py-3 text-end text-xs tabular-nums hidden md:table-cell">{{ r.bmi || '—' }}</td>
                                <td class="px-4 py-3 text-end text-xs tabular-nums hidden md:table-cell">{{ r.blood_sugar || '—' }}</td>
                                <td class="px-4 py-3 text-end text-xs tabular-nums hidden lg:table-cell">{{ r.temperature || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-[11px] text-gray-400 text-center">
                {{ isRtl ? 'تُسجَّل القياسات بواسطة الفريق الطبي خلال زياراتك. للاستفسار تواصل مع العيادة.' : 'Vitals are recorded by your medical team during visits. Contact the clinic with any questions.' }}
            </p>
        </div>
    </div>
</template>
