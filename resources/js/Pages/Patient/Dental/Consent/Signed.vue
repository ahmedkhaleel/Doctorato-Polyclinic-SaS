<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    consent: Object,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const isSigned = computed(() => props.consent?.status === 'signed');
const isDeclined = computed(() => props.consent?.status === 'declined');

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div class="max-w-lg mx-auto text-center py-8">
        <!-- Success: Signed -->
        <div v-if="isSigned" class="consent-result-enter">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-xl shadow-green-200/50">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                {{ isRtl ? 'تم التوقيع بنجاح!' : 'Successfully Signed!' }}
            </h1>
            <p class="text-gray-500 mb-1">
                {{ isRtl ? 'تم توقيع الموافقة على خطة العلاج بنجاح' : 'You have successfully signed the treatment plan consent' }}
            </p>
            <p class="text-sm text-gray-400 mb-8">
                {{ isRtl ? 'تاريخ التوقيع:' : 'Signed on:' }} {{ formatDate(consent.signed_at) }}
            </p>

            <div class="bg-green-50 border border-green-100 rounded-2xl p-5 mb-8 text-start">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm text-green-700 font-medium">{{ isRtl ? 'ماذا يحدث الآن؟' : 'What happens next?' }}</p>
                        <p class="text-sm text-green-600 mt-1">
                            {{ isRtl
                                ? 'سيقوم فريق العيادة بمراجعة موافقتك والبدء في تنفيذ خطة العلاج. ستتلقى إشعاراً عند حجز موعدك القادم.'
                                : 'The clinic team will review your consent and begin executing the treatment plan. You will be notified when your next appointment is scheduled.'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Signature Preview -->
            <div v-if="consent.signature_url" class="bg-white rounded-2xl border border-gray-100 p-4 mb-8 inline-block">
                <p class="text-xs text-gray-400 mb-2">{{ isRtl ? 'توقيعك' : 'Your Signature' }}</p>
                <img :src="consent.signature_url" alt="Signature" class="h-16 mx-auto" />
            </div>
        </div>

        <!-- Declined -->
        <div v-else-if="isDeclined" class="consent-result-enter">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center shadow-xl shadow-red-200/50">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                {{ isRtl ? 'تم رفض الموافقة' : 'Consent Declined' }}
            </h1>
            <p class="text-gray-500 mb-8">
                {{ isRtl ? 'لقد رفضت الموافقة على خطة العلاج. يمكنك التواصل مع العيادة لمناقشة البدائل.' : 'You have declined the treatment plan consent. You can contact the clinic to discuss alternatives.' }}
            </p>
        </div>

        <!-- Navigation -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <Link :href="lp('/dental/treatment-plans')"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-cyan-600 bg-cyan-50 border border-cyan-100 rounded-xl hover:bg-cyan-100 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                {{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}
            </Link>
            <Link :href="lp('')"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-all">
                {{ isRtl ? 'الرئيسية' : 'Dashboard' }}
            </Link>
        </div>
    </div>
</template>

<style>
@keyframes consentResultEnter {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.consent-result-enter { animation: consentResultEnter 0.7s cubic-bezier(0.16, 1, 0.3, 1) both; }
</style>
