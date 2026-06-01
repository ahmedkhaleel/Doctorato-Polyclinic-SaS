<script setup>
import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const props = defineProps({ consentRequired: Boolean });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const tools = [
    { key: 'summary', ar: 'تلخيص ملف المريض', en: 'Patient summary' },
    { key: 'soap', ar: 'ملاحظة SOAP', en: 'SOAP note' },
    { key: 'differential', ar: 'تشخيص تفريقي', en: 'Differential Dx' },
    { key: 'icd10', ar: 'ترميز ICD-10', en: 'ICD-10' },
    { key: 'prescription', ar: 'اقتراح وصفة', en: 'Prescription' },
    { key: 'report', ar: 'تقرير/إحالة', en: 'Report/Referral' },
    { key: 'vision', ar: 'تحليل صورة/أشعة', en: 'Image / X-ray' },
    { key: 'dictation', ar: 'إملاء صوتي', en: 'Voice dictation' },
];

const visionMode = ref('dental_xray_vision');
const imageFile = ref(null);
const audioFile = ref(null);
const onImage = (e) => { imageFile.value = e.target.files?.[0] || null; };
const onAudio = (e) => { audioFile.value = e.target.files?.[0] || null; };

const active = ref('summary');
const f = reactive({
    patient_id: '', consent: false,
    notes: '', symptoms: '', diagnosis: '', patient_age: '',
    rx_diagnosis: '', report_type: t('تقرير طبي', 'medical report'), report_content: '',
    medications: '', allergies: '', current_meds: '',
});

const output = ref('');
const loading = ref(false);
const error = ref('');
// D9 enforcement: a prescription suggestion cannot be "accepted" until checked.
const drugChecked = ref(false);
const drugCheckOutput = ref('');

const post = async (url, body) => {
    const { data } = await axios.post(url, body);
    return data;
};

const selectTool = (k) => { active.value = k; output.value = ''; error.value = ''; drugChecked.value = false; drugCheckOutput.value = ''; };

const run = async () => {
    loading.value = true; error.value = ''; output.value = ''; drugChecked.value = false; drugCheckOutput.value = '';
    try {
        let data;
        if (active.value === 'summary') data = await post('/doctor/ai/summary', { patient_id: f.patient_id, consent: f.consent });
        else if (active.value === 'soap') data = await post('/doctor/ai/soap', { notes: f.notes });
        else if (active.value === 'differential') data = await post('/doctor/ai/differential', { symptoms: f.symptoms });
        else if (active.value === 'icd10') data = await post('/doctor/ai/icd10', { diagnosis: f.diagnosis });
        else if (active.value === 'prescription') data = await post('/doctor/ai/prescription', { diagnosis: f.rx_diagnosis, patient_age: f.patient_age });
        else if (active.value === 'report') data = await post('/doctor/ai/report', { type: f.report_type, content: f.report_content });
        else if (active.value === 'vision') {
            if (!imageFile.value) { error.value = t('اختر صورة.', 'Choose an image.'); loading.value = false; return; }
            const fd = new FormData();
            fd.append('mode', visionMode.value);
            fd.append('image', imageFile.value);
            data = (await axios.post('/doctor/ai/vision', fd, { headers: { 'Content-Type': 'multipart/form-data' } })).data;
        } else if (active.value === 'dictation') {
            if (!audioFile.value) { error.value = t('اختر ملفًا صوتيًا.', 'Choose an audio file.'); loading.value = false; return; }
            const fd = new FormData();
            fd.append('audio', audioFile.value);
            data = (await axios.post('/doctor/ai/transcribe', fd, { headers: { 'Content-Type': 'multipart/form-data' } })).data;
        }
        if (data.ok) output.value = data.text;
        else error.value = data.message;
    } catch (e) {
        error.value = e.response?.data?.message || t('تعذّر التوليد.', 'Generation failed.');
    } finally { loading.value = false; }
};

const runDrugCheck = async () => {
    loading.value = true; error.value = '';
    try {
        const meds = f.medications.split(/[\n,]+/).map((s) => s.trim()).filter(Boolean);
        const data = await post('/doctor/ai/drug-check', { medications: meds, allergies: f.allergies, current_meds: f.current_meds });
        if (data.ok) { drugCheckOutput.value = data.text; drugChecked.value = true; }
        else error.value = data.message;
    } catch (e) {
        error.value = e.response?.data?.message || t('تعذّر الفحص.', 'Check failed.');
    } finally { loading.value = false; }
};

const copyOut = () => navigator.clipboard?.writeText(output.value);
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-6">
        <div class="mb-4">
            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ t('مساعدة سريرية', 'Clinical support') }}</span>
            <h1 class="text-xl font-bold text-[#1B365D]">{{ t('المساعد السريري بالذكاء الاصطناعي', 'AI Clinical Assistant') }}</h1>
        </div>

        <!-- Mandatory disclaimer -->
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-3 text-sm mb-5">
            {{ t('كل المخرجات اقتراحات للمساعدة فقط — وليست تشخيصًا أو وصفة نهائية. راجِعها واعتمدها بنفسك.',
                  'All outputs are decision-support suggestions only — not a final diagnosis or prescription. Review and approve them yourself.') }}
        </div>

        <div class="grid md:grid-cols-[200px_1fr] gap-6">
            <aside class="space-y-1">
                <button v-for="tool in tools" :key="tool.key" @click="selectTool(tool.key)"
                    class="w-full text-start px-4 py-2.5 rounded-lg text-sm transition-colors"
                    :class="active === tool.key ? 'bg-[#1B365D] text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'">
                    {{ t(tool.ar, tool.en) }}
                </button>
            </aside>

            <section class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                    <!-- Summary -->
                    <template v-if="active === 'summary'">
                        <label class="block text-sm text-gray-600">{{ t('رقم المريض', 'Patient ID') }}</label>
                        <input v-model="f.patient_id" type="number" class="w-full rounded-lg border-gray-300 text-sm" />
                        <label v-if="consentRequired" class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" v-model="f.consent" class="rounded text-[#1B365D]" />
                            {{ t('أؤكد موافقة المريض على المعالجة بالذكاء الاصطناعي', 'I confirm patient consent for AI processing') }}
                        </label>
                    </template>
                    <!-- SOAP -->
                    <template v-else-if="active === 'soap'">
                        <label class="block text-sm text-gray-600">{{ t('ملاحظات مختصرة', 'Brief notes') }}</label>
                        <textarea v-model="f.notes" rows="4" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </template>
                    <!-- Differential -->
                    <template v-else-if="active === 'differential'">
                        <label class="block text-sm text-gray-600">{{ t('الأعراض', 'Symptoms') }}</label>
                        <textarea v-model="f.symptoms" rows="4" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </template>
                    <!-- ICD10 -->
                    <template v-else-if="active === 'icd10'">
                        <label class="block text-sm text-gray-600">{{ t('التشخيص', 'Diagnosis') }}</label>
                        <input v-model="f.diagnosis" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                    </template>
                    <!-- Prescription -->
                    <template v-else-if="active === 'prescription'">
                        <label class="block text-sm text-gray-600">{{ t('التشخيص', 'Diagnosis') }}</label>
                        <input v-model="f.rx_diagnosis" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                        <label class="block text-sm text-gray-600">{{ t('العمر (اختياري)', 'Age (optional)') }}</label>
                        <input v-model="f.patient_age" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                    </template>
                    <!-- Report -->
                    <template v-else-if="active === 'report'">
                        <label class="block text-sm text-gray-600">{{ t('نوع التقرير', 'Report type') }}</label>
                        <input v-model="f.report_type" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                        <label class="block text-sm text-gray-600">{{ t('التفاصيل', 'Details') }}</label>
                        <textarea v-model="f.report_content" rows="4" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </template>
                    <!-- Vision -->
                    <template v-else-if="active === 'vision'">
                        <label class="block text-sm text-gray-600">{{ t('نوع الصورة', 'Image type') }}</label>
                        <select v-model="visionMode" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="dental_xray_vision">{{ t('أشعة أسنان', 'Dental X-ray') }}</option>
                            <option value="derma_image_vision">{{ t('صورة جلدية', 'Dermatology photo') }}</option>
                        </select>
                        <label class="block text-sm text-gray-600">{{ t('الصورة', 'Image') }}</label>
                        <input type="file" accept="image/*" @change="onImage" class="w-full text-sm" />
                        <p class="text-xs text-amber-600">{{ t('استشاري فقط — ليس تشخيصًا.', 'Advisory only — not a diagnosis.') }}</p>
                    </template>
                    <!-- Dictation -->
                    <template v-else-if="active === 'dictation'">
                        <label class="block text-sm text-gray-600">{{ t('ملف صوتي', 'Audio file') }}</label>
                        <input type="file" accept="audio/*" @change="onAudio" class="w-full text-sm" />
                        <p class="text-xs text-gray-400">{{ t('يُفرَّغ النص ويمكنك تحويله لملاحظة SOAP.', 'Transcribed text can then be turned into a SOAP note.') }}</p>
                    </template>

                    <button @click="run" :disabled="loading"
                        class="px-5 py-2.5 rounded-lg bg-[#1B365D] text-white font-medium hover:opacity-90 disabled:opacity-50">
                        {{ loading ? t('جارٍ…', 'Working…') : t('توليد', 'Generate') }}
                    </button>
                </div>

                <div v-if="error" class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm">{{ error }}</div>

                <div v-if="output" class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">{{ t('مسوّدة (تحتاج اعتمادك)', 'Draft (needs your approval)') }}</h3>
                        <button @click="copyOut" class="text-sm px-3 py-1.5 rounded-lg bg-[#C4A265] text-white hover:opacity-90">{{ t('نسخ', 'Copy') }}</button>
                    </div>
                    <pre class="whitespace-pre-wrap text-sm text-gray-700 leading-relaxed font-sans">{{ output }}</pre>

                    <!-- D9 enforcement for prescription suggestions -->
                    <div v-if="active === 'prescription'" class="border-t border-gray-100 pt-3 space-y-3">
                        <p class="text-sm font-semibold text-red-700">{{ t('إلزامي: افحص التعارض والحساسية قبل الاعتماد', 'Required: check interactions & allergies before accepting') }}</p>
                        <textarea v-model="f.medications" rows="2" class="w-full rounded-lg border-gray-300 text-sm"
                            :placeholder="t('الأدوية (سطر أو فاصلة لكل دواء)', 'Medications (one per line or comma)')"></textarea>
                        <div class="grid sm:grid-cols-2 gap-2">
                            <input v-model="f.allergies" type="text" class="rounded-lg border-gray-300 text-sm" :placeholder="t('حساسية المريض', 'Patient allergies')" />
                            <input v-model="f.current_meds" type="text" class="rounded-lg border-gray-300 text-sm" :placeholder="t('الأدوية الحالية', 'Current medications')" />
                        </div>
                        <button @click="runDrugCheck" :disabled="loading"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm hover:opacity-90 disabled:opacity-50">
                            {{ t('فحص التعارض (D9)', 'Check interactions (D9)') }}
                        </button>
                        <div v-if="drugCheckOutput" class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-gray-800 whitespace-pre-wrap">{{ drugCheckOutput }}</div>
                        <p v-if="!drugChecked" class="text-xs text-gray-400">{{ t('لا يمكن اعتماد الوصفة قبل إجراء الفحص.', 'The prescription cannot be accepted before the check is run.') }}</p>
                        <p v-else class="text-xs text-emerald-600">{{ t('تم الفحص — راجِع التحذيرات قبل الاعتماد.', 'Checked — review warnings before approving.') }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
