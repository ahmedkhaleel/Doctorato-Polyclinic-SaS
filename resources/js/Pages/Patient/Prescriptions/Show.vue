<script setup>
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    prescription: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');

// AI gap #4 — plain-language explanation (gated by the patient_explain flag).
const aiExplainEnabled = computed(() => {
    const ai = page.props.ai;
    return !!ai?.enabled && Array.isArray(ai?.features) && ai.features.includes('patient_explain');
});
const explaining = ref(false);
const explanation = ref('');
const explainError = ref('');
async function explainPrescription() {
    if (explaining.value) return;
    explaining.value = true;
    explainError.value = '';
    explanation.value = '';
    try {
        const { data } = await axios.post(lp(`/prescriptions/${props.prescription.id}/explain`));
        if (data.ok) {
            explanation.value = data.text;
        } else {
            explainError.value = data.message;
        }
    } catch (e) {
        explainError.value = e.response?.data?.message || (isRtl.value ? 'تعذّر الشرح حاليًا.' : 'Could not generate an explanation.');
    } finally {
        explaining.value = false;
    }
}
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('/prescriptions')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'تفاصيل الوصفة' : 'Prescription Details' }}</h1>
        </div>

        <!-- ═══ AI: explain in plain language (gated by patient_explain) ═══ -->
        <div v-if="aiExplainEnabled" class="bg-gradient-to-br from-[#1B365D] to-[#0F2444] rounded-2xl shadow-sm p-5 mb-6 text-white">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-sm">{{ isRtl ? 'لم تفهم وصفتك؟' : 'Confused about your prescription?' }}</p>
                    <p class="text-xs text-white/70 mt-0.5">{{ isRtl ? 'دع المساعد يشرحها لك بلغة بسيطة (ليس بديلاً عن طبيبك).' : 'Let the assistant explain it in simple terms (not a substitute for your doctor).' }}</p>
                    <button type="button" @click="explainPrescription" :disabled="explaining"
                        class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#C4A265] text-[#1B365D] text-sm font-bold hover:opacity-90 disabled:opacity-50 transition">
                        <svg v-if="!explaining" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                        <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ explaining ? (isRtl ? 'جارٍ الشرح…' : 'Explaining…') : (isRtl ? 'اشرح لي بلغة بسيطة' : 'Explain in simple terms') }}
                    </button>
                    <p v-if="explainError" class="mt-2 text-xs bg-amber-400/20 text-amber-100 rounded-lg px-3 py-2">{{ explainError }}</p>
                    <div v-if="explanation" class="mt-3 rounded-xl bg-white/95 text-gray-800 p-4 text-sm leading-relaxed whitespace-pre-wrap">{{ explanation }}</div>
                </div>
            </div>
        </div>

        <!-- Prescription Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الطبيب' : 'Doctor' }}</span>
                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $localized(prescription?.doctor, 'name') || prescription?.doctor_name }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'التاريخ' : 'Date' }}</span>
                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ prescription?.prescription_date || prescription?.created_at?.split('T')[0] }}</p>
                </div>
            </div>
        </div>

        <!-- Diagnosis -->
        <div v-if="prescription?.diagnosis" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</h2>
            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ prescription.diagnosis }}</p>
        </div>

        <!-- Medications Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'الأدوية' : 'Medications' }}</h3>
            </div>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'الدواء' : 'Medication' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'الكمية' : 'Quantity' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'التكرار' : 'Frequency' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'المدة' : 'Duration' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'التعليمات' : 'Instructions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="med in prescription?.medications" :key="med.id" class="border-b border-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $localized(med, 'medication_name') || med.medication_name }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ med.quantity }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ med.frequency }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ med.duration }}</td>
                            <td class="px-6 py-3 text-gray-500 max-w-[200px]">{{ med.instructions || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-gray-100">
                <div v-for="med in prescription?.medications" :key="med.id" class="p-4">
                    <h4 class="text-sm font-semibold text-gray-800 mb-2">{{ $localized(med, 'medication_name') || med.medication_name }}</h4>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'الكمية' : 'Qty' }}</span>
                            <p class="text-gray-700 font-medium">{{ med.quantity }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'التكرار' : 'Frequency' }}</span>
                            <p class="text-gray-700 font-medium">{{ med.frequency }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'المدة' : 'Duration' }}</span>
                            <p class="text-gray-700 font-medium">{{ med.duration }}</p>
                        </div>
                        <div v-if="med.instructions">
                            <span class="text-gray-400">{{ isRtl ? 'التعليمات' : 'Instructions' }}</span>
                            <p class="text-gray-700 font-medium">{{ med.instructions }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="!prescription?.medications?.length" class="text-center py-8 text-gray-400 text-sm">
                {{ isRtl ? 'لا توجد أدوية' : 'No medications listed' }}
            </div>
        </div>
    </div>
</template>
