<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    contactUrl: { type: String, default: 'https://doctorato.com/contact' },
    clinicName: { type: String, default: 'Doctorato Polyclinic' },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'"
        class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-br from-[#0f172a] via-[#1B365D] to-[#0f172a] relative overflow-hidden">
        <!-- subtle decorative glow -->
        <div class="absolute -top-32 -end-32 w-96 h-96 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
        <div class="absolute -bottom-32 -start-32 w-96 h-96 rounded-full bg-[#C4A265]/10 blur-3xl"></div>

        <div class="relative w-full max-w-lg bg-white/95 backdrop-blur rounded-3xl shadow-2xl border border-white/10 p-8 sm:p-10 text-center"
            style="animation: trialIn 0.6s cubic-bezier(0.22,1,0.36,1) both;">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-[#C4A265]/15 flex items-center justify-center mb-5">
                <svg class="w-8 h-8 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <span class="text-[11px] font-bold text-[#C4A265] tracking-[0.3em] uppercase">{{ clinicName }}</span>
            <h1 class="mt-2 text-2xl font-bold text-[#1B365D]">
                {{ t('انتهت الفترة التجريبية', 'Your trial has ended') }}
            </h1>
            <p class="mt-3 text-gray-600 leading-relaxed text-sm">
                {{ t('انتهت مدة الحساب التجريبي. للاستمرار في استخدام النظام، يرجى التواصل مع الإدارة.',
                      'Your trial period has ended. To continue using the system, please contact the administration.') }}
            </p>

            <a :href="contactUrl" target="_blank" rel="noopener"
                class="mt-7 inline-flex items-center justify-center gap-2 w-full px-6 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-[#A68B52] to-[#C4A265] hover:opacity-90 transition-opacity shadow-lg shadow-[#C4A265]/25">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ t('تواصل مع الإدارة', 'Contact the administration') }}
            </a>

            <Link href="/" class="mt-4 inline-block text-sm text-gray-400 hover:text-[#1B365D] transition-colors">
                {{ t('العودة إلى الموقع', 'Back to the website') }}
            </Link>
        </div>
    </div>
</template>

<style scoped>
@keyframes trialIn {
    from { opacity: 0; transform: translateY(16px) scale(0.98); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    div[style*="trialIn"] { animation: none !important; }
}
</style>
