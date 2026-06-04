<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Compact validation-error summary for a form. Renders the messages from an
 * Inertia useForm's `errors` object (already localized server-side) so a 422
 * never fails silently. Self-hides when there are no errors.
 *
 * Usage:  <FormErrors :errors="form.errors" />
 */
const props = defineProps({
    errors: { type: Object, default: () => ({}) },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || page.props.locale === 'ar' ? 'rtl' : 'ltr') === 'rtl' || page.props.locale === 'ar');

const messages = computed(() => Object.values(props.errors || {}).filter(Boolean));
</script>

<template>
    <transition name="form-errors">
        <div
            v-if="messages.length"
            role="alert"
            aria-live="assertive"
            class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3"
        >
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-red-700">{{ isRtl ? 'يرجى تصحيح الأخطاء التالية' : 'Please fix the following' }}</p>
                    <ul class="mt-1 space-y-0.5 text-xs text-red-600 list-disc ltr:pl-4 rtl:pr-4">
                        <li v-for="(msg, i) in messages" :key="i">{{ msg }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.form-errors-enter-active,
.form-errors-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.form-errors-enter-from,
.form-errors-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
@media (prefers-reduced-motion: reduce) {
    .form-errors-enter-active,
    .form-errors-leave-active {
        transition: none;
    }
}
</style>
