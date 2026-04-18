<script setup>
import { ref, computed } from 'vue';
import { usePage, useForm, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';
import SignaturePad from '@/Components/SignaturePad.vue';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    consent: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const signaturePadRef = ref(null);
const signatureData = ref(null);
const showDeclineModal = ref(false);

const plan = computed(() => props.consent?.treatment_plan);
const snapshot = computed(() => props.consent?.consent_text_snapshot || {});
const treatments = computed(() => snapshot.value?.treatments || plan.value?.treatments || []);

const isExpired = computed(() => props.consent?.status === 'expired');
const isSigned = computed(() => props.consent?.status === 'signed');
const isDeclined = computed(() => props.consent?.status === 'declined');
const canSign = computed(() => props.consent?.status === 'pending');

const signForm = useForm({
    signature: '',
    action: 'sign',
    declined_reason: '',
});

const declineForm = useForm({
    signature: '',
    action: 'decline',
    declined_reason: '',
});

function handleSignatureSigned() {
    signatureData.value = signaturePadRef.value ? signaturePadRef.value : null;
}

function submitSign() {
    if (!signatureData.value) return;
    signForm.signature = signatureData.value;
    signForm.action = 'sign';
    signForm.post(lp(`/dental/consent/${props.consent.id}/sign`), {
        preserveScroll: true,
    });
}

function submitDecline() {
    declineForm.action = 'decline';
    declineForm.signature = 'declined';
    declineForm.post(lp(`/dental/consent/${props.consent.id}/sign`), {
        preserveScroll: true,
        onSuccess: () => {
            showDeclineModal.value = false;
        },
    });
}

function handleSignatureUpdate(data) {
    signatureData.value = data;
}

const totalCost = computed(() => {
    return treatments.value.reduce((sum, t) => sum + (parseFloat(t.cost) || 0) + (parseFloat(t.lab_cost) || 0), 0);
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const treatmentTypeLabels = {
    filling: { ar: 'حشو', en: 'Filling' },
    extraction: { ar: 'خلع', en: 'Extraction' },
    root_canal: { ar: 'علاج عصب', en: 'Root Canal' },
    crown: { ar: 'تاج', en: 'Crown' },
    bridge: { ar: 'جسر', en: 'Bridge' },
    implant: { ar: 'زراعة', en: 'Implant' },
    cleaning: { ar: 'تنظيف', en: 'Cleaning' },
    scaling: { ar: 'تقليح', en: 'Scaling' },
    whitening: { ar: 'تبييض', en: 'Whitening' },
    veneer: { ar: 'قشرة', en: 'Veneer' },
    orthodontic: { ar: 'تقويم', en: 'Orthodontic' },
    denture: { ar: 'طقم أسنان', en: 'Denture' },
    sealant: { ar: 'سيلانت', en: 'Sealant' },
    fluoride: { ar: 'فلورايد', en: 'Fluoride' },
    gum_treatment: { ar: 'علاج لثة', en: 'Gum Treatment' },
    surgical_extraction: { ar: 'خلع جراحي', en: 'Surgical Extraction' },
    bone_graft: { ar: 'زراعة عظم', en: 'Bone Graft' },
    sinus_lift: { ar: 'رفع الجيب الأنفي', en: 'Sinus Lift' },
    night_guard: { ar: 'حارس ليلي', en: 'Night Guard' },
    retainer: { ar: 'مثبت', en: 'Retainer' },
};

function treatmentLabel(type) {
    const labels = treatmentTypeLabels[type];
    if (!labels) return type?.replace(/_/g, ' ') || '-';
    return isRtl.value ? labels.ar : labels.en;
}
</script>

<template>
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8 consent-hero-enter">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-400 to-teal-500 text-white mb-4 shadow-lg shadow-cyan-200/50">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'موافقة على خطة العلاج' : 'Treatment Plan Consent' }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ isRtl ? 'يرجى مراجعة خطة العلاج والتوقيع للموافقة' : 'Please review the treatment plan and sign to consent' }}
            </p>
        </div>

        <!-- Status Badges for non-pending states -->
        <div v-if="isExpired" class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6 text-center consent-card-enter">
            <svg class="w-10 h-10 text-amber-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-amber-700">{{ isRtl ? 'انتهت صلاحية هذا الطلب' : 'This consent request has expired' }}</h3>
            <p class="text-sm text-amber-600 mt-1">{{ isRtl ? 'يرجى التواصل مع العيادة لإعادة الإرسال' : 'Please contact the clinic to resend' }}</p>
        </div>

        <div v-if="isSigned" class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 mb-6 text-center consent-card-enter">
            <svg class="w-10 h-10 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-emerald-700">{{ isRtl ? 'تم التوقيع بنجاح' : 'Already Signed' }}</h3>
            <p class="text-sm text-emerald-600 mt-1">{{ isRtl ? 'تم توقيع هذه الموافقة بتاريخ' : 'This consent was signed on' }} {{ formatDate(consent.signed_at) }}</p>
        </div>

        <div v-if="isDeclined" class="bg-red-50 border border-red-200 rounded-2xl p-5 mb-6 text-center consent-card-enter">
            <svg class="w-10 h-10 text-red-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-red-700">{{ isRtl ? 'تم رفض هذا الطلب' : 'This consent was declined' }}</h3>
        </div>

        <!-- Plan Details Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6 consent-card-enter" style="animation-delay: 0.1s;">
            <!-- Plan Header -->
            <div class="bg-gradient-to-r from-[#1B365D] to-teal-500 px-6 py-4">
                <h2 class="text-white font-bold text-lg">
                    {{ isRtl ? (snapshot.plan_title_ar || snapshot.plan_title_en) : (snapshot.plan_title_en || snapshot.plan_title_ar) }}
                </h2>
                <p class="text-slate-100 text-sm mt-0.5">
                    {{ isRtl ? 'الطبيب:' : 'Doctor:' }}
                    {{ isRtl ? (snapshot.doctor_name_ar || snapshot.doctor_name_en) : (snapshot.doctor_name_en || snapshot.doctor_name_ar) }}
                </p>
            </div>

            <!-- Plan Info -->
            <div class="p-6">
                <div v-if="snapshot.plan_description" class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">{{ isRtl ? 'الوصف' : 'Description' }}</p>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ snapshot.plan_description }}</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-5">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'التكلفة التقديرية' : 'Estimated Cost' }}</p>
                        <p class="text-lg font-bold text-[#1B365D] mt-1">{{ formatCurrency(snapshot.estimated_cost || 0) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'عدد الجلسات' : 'Sessions' }}</p>
                        <p class="text-lg font-bold text-gray-800 mt-1">{{ snapshot.estimated_sessions || '-' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'عدد العلاجات' : 'Treatments' }}</p>
                        <p class="text-lg font-bold text-gray-800 mt-1">{{ treatments.length }}</p>
                    </div>
                </div>

                <!-- Treatments List -->
                <div v-if="treatments.length > 0">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'العلاجات المقررة' : 'Planned Treatments' }}</h3>
                    <div class="space-y-2">
                        <div
                            v-for="(treatment, index) in treatments"
                            :key="index"
                            class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors"
                        >
                            <div class="w-8 h-8 rounded-full bg-slate-100 text-[#1B365D] flex items-center justify-center text-xs font-bold flex-shrink-0">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-800">{{ treatmentLabel(treatment.treatment_type) }}</span>
                                    <span v-if="treatment.tooth_number" class="text-xs bg-white text-gray-500 px-2 py-0.5 rounded-full font-mono border">
                                        #{{ treatment.tooth_number }}
                                    </span>
                                </div>
                                <p v-if="treatment.description" class="text-xs text-gray-500 mt-0.5 truncate">{{ treatment.description }}</p>
                            </div>
                            <div class="text-sm font-semibold text-[#1B365D] flex-shrink-0">
                                {{ formatCurrency((parseFloat(treatment.cost) || 0) + (parseFloat(treatment.lab_cost) || 0)) }}
                            </div>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                        <span class="text-lg font-bold text-[#1B365D]">{{ formatCurrency(totalCost) }}</span>
                    </div>
                </div>

                <!-- Risks Notes -->
                <div v-if="consent.risks_notes" class="mt-5 p-4 bg-amber-50 border border-amber-100 rounded-xl">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-semibold text-amber-700">{{ isRtl ? 'ملاحظات ومخاطر' : 'Risks & Notes' }}</h4>
                            <p class="text-sm text-amber-600 mt-1 leading-relaxed">{{ consent.risks_notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Consent Terms -->
        <div v-if="canSign" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 consent-card-enter" style="animation-delay: 0.2s;">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'شروط الموافقة' : 'Consent Terms' }}</h3>
            <div class="text-sm text-gray-600 leading-relaxed space-y-2">
                <p v-if="isRtl">
                    بتوقيعي أدناه، أقر بأنني قد قرأت وفهمت خطة العلاج المقترحة أعلاه بما في ذلك التكاليف والإجراءات المذكورة.
                    أوافق على البدء بالعلاج وفقاً لهذه الخطة، وأفهم أن هناك مخاطر محتملة مرتبطة بأي إجراء طبي.
                    كما أفهم أنه يحق لي طرح أي أسئلة قبل التوقيع.
                </p>
                <p v-else>
                    By signing below, I acknowledge that I have read and understood the proposed treatment plan above,
                    including the costs and procedures described. I consent to proceed with the treatment as outlined,
                    and I understand that there are potential risks associated with any medical procedure.
                    I also understand that I have the right to ask any questions before signing.
                </p>
            </div>
        </div>

        <!-- Signature Section -->
        <div v-if="canSign" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 consent-card-enter" style="animation-delay: 0.3s;">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">{{ isRtl ? 'التوقيع الرقمي' : 'Digital Signature' }}</h3>

            <SignaturePad
                ref="signaturePadRef"
                :placeholder="isRtl ? 'وقّع هنا بإصبعك أو بالماوس' : 'Sign here with your finger or mouse'"
                @update:signature="handleSignatureUpdate"
            />

            <p class="text-xs text-gray-400 mt-2 text-center">
                {{ isRtl ? 'ارسم توقيعك باستخدام الماوس أو بإصبعك على الشاشة' : 'Draw your signature using your mouse or finger on the screen' }}
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 mt-6">
                <button
                    @click="submitSign"
                    :disabled="!signatureData || signForm.processing"
                    class="flex-1 px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-[#1B365D] to-teal-500 rounded-xl hover:from-[#1B365D] hover:to-teal-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-cyan-200/50 flex items-center justify-center gap-2"
                >
                    <svg v-if="signForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ isRtl ? 'أوافق وأوقّع' : 'I Agree & Sign' }}
                </button>
                <button
                    @click="showDeclineModal = true"
                    :disabled="signForm.processing"
                    class="px-6 py-3 text-sm font-medium text-red-600 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 transition-all flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    {{ isRtl ? 'أرفض' : 'Decline' }}
                </button>
            </div>
        </div>

        <!-- Expiry notice -->
        <div v-if="canSign && consent.expires_at" class="text-center mb-6">
            <p class="text-xs text-gray-400">
                {{ isRtl ? 'ينتهي هذا الطلب بتاريخ' : 'This request expires on' }}
                {{ formatDate(consent.expires_at) }}
            </p>
        </div>

        <!-- Decline Modal -->
        <Teleport to="body">
            <div v-if="showDeclineModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showDeclineModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">{{ isRtl ? 'رفض الموافقة' : 'Decline Consent' }}</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        {{ isRtl ? 'يرجى ذكر سبب الرفض (اختياري)' : 'Please provide a reason for declining (optional)' }}
                    </p>
                    <textarea
                        v-model="declineForm.declined_reason"
                        class="doctorato-input w-full p-3 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-red-300"
                        rows="3"
                        :placeholder="isRtl ? 'سبب الرفض...' : 'Reason for declining...'"
                    ></textarea>
                    <div class="flex gap-3 mt-4">
                        <button
                            @click="submitDecline"
                            :disabled="declineForm.processing"
                            class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-xl hover:bg-red-600 disabled:opacity-50 transition"
                        >
                            {{ isRtl ? 'تأكيد الرفض' : 'Confirm Decline' }}
                        </button>
                        <button
                            @click="showDeclineModal = false"
                            class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition"
                        >
                            {{ isRtl ? 'إلغاء' : 'Cancel' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style>
@keyframes consentHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes consentCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.consent-hero-enter { animation: consentHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.consent-card-enter { animation: consentCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
</style>
