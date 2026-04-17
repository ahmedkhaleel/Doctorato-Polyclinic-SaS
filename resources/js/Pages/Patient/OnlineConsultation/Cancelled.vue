<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

defineProps({
    consultationId: [Number, String],
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function lp(path) { return `/${locale.value}/patient${path}`; }
</script>

<template>
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-br from-red-50 via-white to-[#C4A265]/5 p-8 text-center">
                <div class="w-20 h-20 mx-auto rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="mt-5 text-2xl font-bold text-[#1B365D]">
                    {{ isRtl ? 'تم إلغاء الدفع' : 'Payment cancelled' }}
                </h1>
                <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                    {{ isRtl ? 'لم يتم تأكيد الحجز لأن عملية الدفع أُلغيت. يمكنك المحاولة مجدداً في أي وقت.' : 'Your booking was not confirmed because the payment was cancelled. You can try again anytime.' }}
                </p>
            </div>

            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <Link :href="lp('/online-consultations/doctors')" class="inline-flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold text-[#1B365D] bg-gradient-to-r from-[#C4A265] to-[#D9B985] hover:shadow-lg transition-all">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'العودة للأطباء' : 'Back to doctors' }}
                </Link>
                <a href="tel:+20000000000" class="inline-flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold text-[#1B365D] border-2 border-gray-200 hover:bg-gray-50 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    {{ isRtl ? 'الاتصال بالدعم' : 'Contact support' }}
                </a>
            </div>
        </div>
    </div>
</template>
