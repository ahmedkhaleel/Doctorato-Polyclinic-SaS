<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useLocale } from '@/Composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    beforeImage: {
        type: String,
        required: true,
    },
    afterImage: {
        type: String,
        required: true,
    },
});

const sliderPosition = ref(50);
const containerRef = ref(null);
const isDragging = ref(false);

function getPosition(e) {
    if (!containerRef.value) return 50;
    const rect = containerRef.value.getBoundingClientRect();
    let clientX;
    if (e.touches) {
        clientX = e.touches[0].clientX;
    } else {
        clientX = e.clientX;
    }
    const x = clientX - rect.left;
    const percentage = (x / rect.width) * 100;
    return Math.max(0, Math.min(100, percentage));
}

function startDrag(e) {
    isDragging.value = true;
    sliderPosition.value = getPosition(e);
}

function onDrag(e) {
    if (!isDragging.value) return;
    e.preventDefault();
    sliderPosition.value = getPosition(e);
}

function stopDrag() {
    isDragging.value = false;
}

onMounted(() => {
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
    document.addEventListener('touchmove', onDrag, { passive: false });
    document.addEventListener('touchend', stopDrag);
});

onUnmounted(() => {
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('touchend', stopDrag);
});
</script>

<template>
    <div
        ref="containerRef"
        class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden cursor-col-resize select-none shadow-lg"
        @mousedown="startDrag"
        @touchstart.prevent="startDrag"
    >
        <!-- After Image (Full background) -->
        <div
            class="absolute inset-0 bg-cover bg-center"
            :style="{ backgroundImage: `url(${afterImage})` }"
        ></div>

        <!-- Before Image (Clipped) -->
        <div
            class="absolute inset-0 bg-cover bg-center"
            :style="{
                backgroundImage: `url(${beforeImage})`,
                clipPath: `inset(0 ${100 - sliderPosition}% 0 0)`,
            }"
        ></div>

        <!-- Divider Line -->
        <div
            class="absolute top-0 bottom-0 w-0.5 bg-white shadow-lg z-10"
            :style="{ left: `${sliderPosition}%` }"
        >
            <!-- Handle -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center z-20 transition-transform hover:scale-110"
                :class="isDragging ? 'scale-110' : ''"
            >
                <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                </svg>
            </div>
        </div>

        <!-- Labels -->
        <div class="absolute bottom-4 left-4 z-10">
            <span class="bg-black/60 text-white text-xs font-semibold px-3 py-1.5 rounded-full backdrop-blur-sm">
                {{ t('before') }}
            </span>
        </div>
        <div class="absolute bottom-4 right-4 z-10">
            <span class="bg-black/60 text-white text-xs font-semibold px-3 py-1.5 rounded-full backdrop-blur-sm">
                {{ t('after') }}
            </span>
        </div>
    </div>
</template>
