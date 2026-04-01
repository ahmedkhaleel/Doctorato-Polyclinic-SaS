<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');

const isVisible = ref(false);

const text = computed(() => {
    return locale.value === 'ar'
        ? 'نستخدم ملفات تعريف الارتباط لتحسين تجربتك.'
        : 'We use cookies to enhance your experience.';
});

const acceptLabel = computed(() => {
    return locale.value === 'ar' ? 'قبول' : 'Accept';
});

const declineLabel = computed(() => {
    return locale.value === 'ar' ? 'رفض' : 'Decline';
});

function accept() {
    localStorage.setItem('cookie_consent', 'accepted');
    isVisible.value = false;
}

function decline() {
    localStorage.setItem('cookie_consent', 'declined');
    isVisible.value = false;
}

onMounted(() => {
    const consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        // Small delay so it slides up smoothly after page load
        setTimeout(() => {
            isVisible.value = true;
        }, 1000);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="isVisible"
            class="fixed inset-x-0 bottom-0 z-[60]"
        >
            <!-- Semi-transparent backdrop -->
            <div class="absolute inset-0 -top-2 bg-gradient-to-t from-black/20 to-transparent pointer-events-none" />

            <!-- Banner -->
            <div class="relative bg-[#3A3A3A]/95 backdrop-blur-md border-t border-[var(--brand-primary)]/20">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4">
                    <div
                        class="flex flex-col sm:flex-row items-center justify-between gap-4"
                        :class="{ 'sm:flex-row-reverse': isRtl }"
                    >
                        <!-- Text -->
                        <p class="text-white/90 text-sm sm:text-base text-center sm:text-start">
                            <svg
                                class="inline-block w-5 h-5 text-[var(--brand-primary)] -mt-0.5"
                                :class="isRtl ? 'ml-2' : 'mr-2'"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                            {{ text }}
                        </p>

                        <!-- Buttons -->
                        <div class="flex items-center gap-3 shrink-0">
                            <button
                                @click="decline"
                                class="px-5 py-2 text-sm font-medium text-white/70 border border-white/20 rounded-full hover:border-white/40 hover:text-white transition-all duration-200"
                            >
                                {{ declineLabel }}
                            </button>
                            <button
                                @click="accept"
                                class="px-5 py-2 text-sm font-medium text-white bg-[var(--brand-primary)] rounded-full hover:bg-[var(--brand-primary-hover)] shadow-lg shadow-[var(--brand-primary)]/20 hover:shadow-[var(--brand-primary)]/40 transition-all duration-200"
                            >
                                {{ acceptLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
