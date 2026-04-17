<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    /** Array of action objects: { key, label, icon (SVG path), color (tailwind gradient), onAction } */
    leftActions: { type: Array, default: () => [] },
    rightActions: { type: Array, default: () => [] },
    threshold: { type: Number, default: 80 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['action']);

const container = ref(null);
const translateX = ref(0);
const isSwiping = ref(false);
const isSnapped = ref(false);
const snapDirection = ref(null);

let startX = 0;
let startY = 0;
let startTranslate = 0;
let isHorizontal = null;

const actionWidth = 80;

function getActionsWidth(side) {
    const actions = side === 'left' ? props.leftActions : props.rightActions;
    return actions.length * actionWidth;
}

function onTouchStart(e) {
    if (props.disabled) return;
    const touch = e.touches[0];
    startX = touch.clientX;
    startY = touch.clientY;
    startTranslate = translateX.value;
    isHorizontal = null;
    isSwiping.value = false;
}

function onTouchMove(e) {
    if (props.disabled) return;
    const touch = e.touches[0];
    const dx = touch.clientX - startX;
    const dy = touch.clientY - startY;

    // Determine swipe direction on first significant movement
    if (isHorizontal === null) {
        if (Math.abs(dx) > 8 || Math.abs(dy) > 8) {
            isHorizontal = Math.abs(dx) > Math.abs(dy);
        }
        if (!isHorizontal) return;
    }

    if (!isHorizontal) return;
    e.preventDefault();
    isSwiping.value = true;

    let newX = startTranslate + dx;

    // Limit based on available actions
    const maxRight = props.leftActions.length > 0 ? getActionsWidth('left') : 0;
    const maxLeft = props.rightActions.length > 0 ? -getActionsWidth('right') : 0;

    // Add rubber band effect at edges
    if (newX > maxRight) {
        newX = maxRight + (newX - maxRight) * 0.2;
    } else if (newX < maxLeft) {
        newX = maxLeft + (newX - maxLeft) * 0.2;
    }

    translateX.value = newX;
}

function onTouchEnd() {
    if (props.disabled || !isSwiping.value) return;
    isSwiping.value = false;

    const rightThreshold = getActionsWidth('right') * 0.4;
    const leftThreshold = getActionsWidth('left') * 0.4;

    if (translateX.value < -rightThreshold && props.rightActions.length > 0) {
        // Snap open right actions
        translateX.value = -getActionsWidth('right');
        isSnapped.value = true;
        snapDirection.value = 'right';
    } else if (translateX.value > leftThreshold && props.leftActions.length > 0) {
        // Snap open left actions
        translateX.value = getActionsWidth('left');
        isSnapped.value = true;
        snapDirection.value = 'left';
    } else {
        // Spring back
        resetPosition();
    }
}

function resetPosition() {
    translateX.value = 0;
    isSnapped.value = false;
    snapDirection.value = null;
}

function triggerAction(action) {
    resetPosition();
    emit('action', action.key);
}

// Close when clicking outside
function handleDocumentClick(e) {
    if (isSnapped.value && container.value && !container.value.contains(e.target)) {
        resetPosition();
    }
}

onMounted(() => {
    document.addEventListener('click', handleDocumentClick, true);
});
onUnmounted(() => {
    document.removeEventListener('click', handleDocumentClick, true);
});
</script>

<template>
    <div ref="container" class="swipe-container relative overflow-hidden rounded-2xl sm:rounded-none">
        <!-- Left Actions (revealed on swipe right) -->
        <div v-if="leftActions.length"
            class="absolute inset-y-0 left-0 flex items-stretch"
            :style="{ width: getActionsWidth('left') + 'px' }"
        >
            <button
                v-for="action in leftActions"
                :key="action.key"
                @click="triggerAction(action)"
                class="flex flex-col items-center justify-center gap-1.5 text-white font-semibold text-[11px] transition-transform duration-200"
                :class="action.color || 'bg-gradient-to-b from-[#1B365D] to-[#1B365D]'"
                :style="{ width: actionWidth + 'px' }"
            >
                <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.icon" />
                    </svg>
                </div>
                {{ action.label }}
            </button>
        </div>

        <!-- Right Actions (revealed on swipe left) -->
        <div v-if="rightActions.length"
            class="absolute inset-y-0 right-0 flex items-stretch"
            :style="{ width: getActionsWidth('right') + 'px' }"
        >
            <button
                v-for="action in rightActions"
                :key="action.key"
                @click="triggerAction(action)"
                class="flex flex-col items-center justify-center gap-1.5 text-white font-semibold text-[11px] transition-transform duration-200"
                :class="action.color || 'bg-gradient-to-b from-red-500 to-red-600'"
                :style="{ width: actionWidth + 'px' }"
            >
                <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.icon" />
                    </svg>
                </div>
                {{ action.label }}
            </button>
        </div>

        <!-- Main content (slides) -->
        <div
            class="relative z-10 bg-white"
            :class="{ 'swipe-transition': !isSwiping }"
            :style="{ transform: `translateX(${translateX}px)` }"
            @touchstart.passive="onTouchStart"
            @touchmove="onTouchMove"
            @touchend="onTouchEnd"
        >
            <slot />
        </div>
    </div>
</template>

<style scoped>
.swipe-container {
    touch-action: pan-y;
    -webkit-user-select: none;
    user-select: none;
}
.swipe-transition {
    transition: transform 0.35s cubic-bezier(0.25, 1, 0.5, 1);
}
</style>
