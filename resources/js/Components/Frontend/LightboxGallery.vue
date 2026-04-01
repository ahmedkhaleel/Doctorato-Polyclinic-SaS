<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { useLocale } from '@/Composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    images: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Boolean,
        default: false,
    },
    startIndex: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['update:modelValue']);

const currentIndex = ref(props.startIndex);
const isZoomed = ref(false);

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const currentImage = computed(() => {
    if (!props.images.length) return null;
    const img = props.images[currentIndex.value];
    return typeof img === 'string' ? { src: img } : img;
});

const hasNext = computed(() => currentIndex.value < props.images.length - 1);
const hasPrev = computed(() => currentIndex.value > 0);

watch(() => props.startIndex, (val) => {
    currentIndex.value = val;
});

watch(() => props.modelValue, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
        currentIndex.value = props.startIndex;
        isZoomed.value = false;
    } else {
        document.body.style.overflow = '';
    }
});

function close() {
    isOpen.value = false;
    isZoomed.value = false;
}

function next() {
    if (hasNext.value) {
        currentIndex.value++;
        isZoomed.value = false;
    }
}

function prev() {
    if (hasPrev.value) {
        currentIndex.value--;
        isZoomed.value = false;
    }
}

function handleKeydown(e) {
    if (!isOpen.value) return;
    switch (e.key) {
        case 'Escape':
            close();
            break;
        case 'ArrowRight':
            next();
            break;
        case 'ArrowLeft':
            prev();
            break;
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen && images.length > 0"
                class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center"
                @click.self="close"
            >
                <!-- Close Button -->
                <button
                    @click="close"
                    class="absolute top-4 right-4 z-20 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                    :aria-label="t('close')"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Counter -->
                <div class="absolute top-4 left-4 z-20 text-white/60 text-sm font-medium">
                    {{ currentIndex + 1 }} / {{ images.length }}
                </div>

                <!-- Previous Button -->
                <button
                    v-if="hasPrev"
                    @click.stop="prev"
                    class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                    :aria-label="t('previous')"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Next Button -->
                <button
                    v-if="hasNext"
                    @click.stop="next"
                    class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                    :aria-label="t('next')"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Image -->
                <div class="max-w-[90vw] max-h-[85vh] flex items-center justify-center px-16">
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                        mode="out-in"
                    >
                        <img
                            :key="currentIndex"
                            v-if="currentImage"
                            :src="currentImage.src || currentImage"
                            :alt="currentImage.alt || currentImage.title || `Image ${currentIndex + 1}`"
                            class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl select-none"
                            draggable="false"
                        />
                    </Transition>
                </div>

                <!-- Caption -->
                <div
                    v-if="currentImage && (currentImage.title || currentImage.caption)"
                    class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 text-center max-w-lg"
                >
                    <p v-if="currentImage.title" class="text-white font-semibold text-sm mb-1">
                        {{ currentImage.title }}
                    </p>
                    <p v-if="currentImage.caption" class="text-white/60 text-xs">
                        {{ currentImage.caption }}
                    </p>
                </div>

                <!-- Thumbnail Strip -->
                <div
                    v-if="images.length > 1"
                    class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2 max-w-[80vw] overflow-x-auto py-2 px-4 bg-black/40 backdrop-blur-sm rounded-full"
                    :class="{ 'bottom-16': currentImage && (currentImage.title || currentImage.caption) }"
                >
                    <button
                        v-for="(img, idx) in images"
                        :key="idx"
                        @click.stop="currentIndex = idx; isZoomed = false;"
                        class="w-10 h-10 rounded-md overflow-hidden flex-shrink-0 border-2 transition-all duration-200"
                        :class="idx === currentIndex ? 'border-[var(--brand-primary)] opacity-100 scale-110' : 'border-transparent opacity-50 hover:opacity-75'"
                    >
                        <img
                            :src="typeof img === 'string' ? img : (img.src || img.thumbnail)"
                            class="w-full h-full object-cover"
                            :alt="`Thumbnail ${idx + 1}`"
                        />
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
