<script setup>
import { ref } from 'vue';
import { useLocale } from '@/Composables/useLocale';

const { t, localized } = useLocale();

const props = defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: '',
    },
});

const activeIndex = ref(null);

function toggle(index) {
    activeIndex.value = activeIndex.value === index ? null : index;
}
</script>

<template>
    <div class="w-full max-w-3xl mx-auto">
        <!-- Title -->
        <h2 v-if="title" class="text-2xl lg:text-3xl font-bold text-gray-900 mb-8 text-center">
            {{ title }}
        </h2>

        <!-- FAQ Items -->
        <div class="space-y-3">
            <div
                v-for="(faq, index) in faqs"
                :key="index"
                class="rounded-xl border transition-all duration-300 overflow-hidden"
                :class="activeIndex === index ? 'border-[var(--brand-primary)]/30 shadow-md shadow-[var(--brand-primary)]/5 bg-white' : 'border-gray-200 bg-white hover:border-gray-300'"
            >
                <!-- Question -->
                <button
                    @click="toggle(index)"
                    class="w-full flex items-center justify-between gap-4 px-6 py-5 text-start transition-colors"
                    :class="activeIndex === index ? 'text-[var(--brand-primary)]' : 'text-gray-800 hover:text-gray-900'"
                >
                    <span class="font-semibold text-sm lg:text-base">
                        {{ localized(faq, 'question') || faq.question }}
                    </span>
                    <span
                        class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300"
                        :class="activeIndex === index ? 'bg-[var(--brand-primary)] text-white rotate-45' : 'bg-gray-100 text-gray-500'"
                    >
                        <svg class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                </button>

                <!-- Answer -->
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-96 opacity-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="max-h-96 opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <div v-show="activeIndex === index" class="overflow-hidden">
                        <div class="px-6 pb-5 text-gray-500 text-sm leading-relaxed border-t border-[var(--brand-primary)]/10 pt-4">
                            {{ localized(faq, 'answer') || faq.answer }}
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>
