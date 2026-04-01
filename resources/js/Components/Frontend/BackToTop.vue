<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useLocale } from '@/Composables/useLocale';

const { t, isRtl } = useLocale();

const isVisible = ref(false);

function handleScroll() {
    isVisible.value = window.scrollY > 300;
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4 scale-90"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-4 scale-90"
    >
        <button
            v-if="isVisible"
            @click="scrollToTop"
            class="fixed z-50 bottom-24 w-11 h-11 bg-[var(--brand-primary)] text-white rounded-full shadow-lg hover:shadow-xl hover:shadow-[var(--brand-primary)]/30 flex items-center justify-center transition-all duration-300 hover:bg-[var(--brand-primary-hover)] hover:scale-110"
            :class="isRtl ? 'left-7' : 'right-7'"
            :aria-label="t('back_to_top')"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
            </svg>
        </button>
    </Transition>
</template>
