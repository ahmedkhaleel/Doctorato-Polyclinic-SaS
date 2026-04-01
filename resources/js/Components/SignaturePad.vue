<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    width: { type: Number, default: 500 },
    height: { type: Number, default: 200 },
    penColor: { type: String, default: '#1a1a2e' },
    lineWidth: { type: Number, default: 2.5 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:signature', 'signed', 'cleared']);

const canvasRef = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
let ctx = null;
let lastPoint = null;

onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    ctx = canvas.getContext('2d');
    setupCanvas();

    // Touch events
    canvas.addEventListener('touchstart', handleTouchStart, { passive: false });
    canvas.addEventListener('touchmove', handleTouchMove, { passive: false });
    canvas.addEventListener('touchend', handleTouchEnd);
});

onUnmounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    canvas.removeEventListener('touchstart', handleTouchStart);
    canvas.removeEventListener('touchmove', handleTouchMove);
    canvas.removeEventListener('touchend', handleTouchEnd);
});

function setupCanvas() {
    const canvas = canvasRef.value;
    const rect = canvas.getBoundingClientRect();

    // Handle high DPI
    const dpr = window.devicePixelRatio || 1;
    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;
    ctx.scale(dpr, dpr);

    ctx.strokeStyle = props.penColor;
    ctx.lineWidth = props.lineWidth;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
}

function getPosition(e) {
    const rect = canvasRef.value.getBoundingClientRect();
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top,
    };
}

function getTouchPosition(e) {
    const rect = canvasRef.value.getBoundingClientRect();
    const touch = e.touches[0];
    return {
        x: touch.clientX - rect.left,
        y: touch.clientY - rect.top,
    };
}

function startDrawing(e) {
    if (props.disabled) return;
    isDrawing.value = true;
    lastPoint = getPosition(e);
    ctx.beginPath();
    ctx.moveTo(lastPoint.x, lastPoint.y);
}

function draw(e) {
    if (!isDrawing.value || props.disabled) return;
    const currentPoint = getPosition(e);

    ctx.lineTo(currentPoint.x, currentPoint.y);
    ctx.stroke();

    lastPoint = currentPoint;
    hasDrawn.value = true;
}

function stopDrawing() {
    if (isDrawing.value) {
        isDrawing.value = false;
        ctx.closePath();
        if (hasDrawn.value) {
            emitSignature();
        }
    }
}

function handleTouchStart(e) {
    if (props.disabled) return;
    e.preventDefault();
    isDrawing.value = true;
    lastPoint = getTouchPosition(e);
    ctx.beginPath();
    ctx.moveTo(lastPoint.x, lastPoint.y);
}

function handleTouchMove(e) {
    if (!isDrawing.value || props.disabled) return;
    e.preventDefault();
    const currentPoint = getTouchPosition(e);

    ctx.lineTo(currentPoint.x, currentPoint.y);
    ctx.stroke();

    lastPoint = currentPoint;
    hasDrawn.value = true;
}

function handleTouchEnd() {
    if (isDrawing.value) {
        isDrawing.value = false;
        ctx.closePath();
        if (hasDrawn.value) {
            emitSignature();
        }
    }
}

function emitSignature() {
    const dataUrl = canvasRef.value.toDataURL('image/png');
    emit('update:signature', dataUrl);
    emit('signed');
}

function clear() {
    const canvas = canvasRef.value;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasDrawn.value = false;
    lastPoint = null;
    emit('update:signature', null);
    emit('cleared');
}

function isEmpty() {
    return !hasDrawn.value;
}

defineExpose({ clear, isEmpty });
</script>

<template>
    <div class="signature-pad-wrapper">
        <canvas
            ref="canvasRef"
            class="signature-canvas"
            :class="{ 'opacity-50 cursor-not-allowed': disabled }"
            @mousedown="startDrawing"
            @mousemove="draw"
            @mouseup="stopDrawing"
            @mouseleave="stopDrawing"
        ></canvas>
        <div v-if="!hasDrawn && !disabled" class="signature-placeholder">
            <svg class="w-5 h-5 text-gray-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span class="signature-hint-text">
                {{ $attrs['placeholder'] || 'وقّع هنا / Sign here' }}
            </span>
        </div>
        <button
            v-if="hasDrawn && !disabled"
            type="button"
            class="signature-clear-btn"
            @click="clear"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </div>
</template>

<style scoped>
.signature-pad-wrapper {
    position: relative;
    border: 2px dashed #d1d5db;
    border-radius: 1rem;
    overflow: hidden;
    background: #fafafa;
    transition: border-color 0.2s;
}

.signature-pad-wrapper:hover {
    border-color: #06B6D4;
}

.signature-canvas {
    display: block;
    width: 100%;
    height: 200px;
    cursor: crosshair;
}

.signature-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.signature-hint-text {
    font-size: 0.8rem;
    color: #9ca3af;
}

.signature-clear-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.4rem;
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.9);
    color: #ef4444;
    border: 1px solid #fecaca;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

[dir="rtl"] .signature-clear-btn {
    right: auto;
    left: 0.5rem;
}

.signature-clear-btn:hover {
    background: #fef2f2;
    border-color: #f87171;
}
</style>
