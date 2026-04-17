<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    booking: { type: Object, default: null },
    accentColor: { type: String, default: '#C4A265' },
});

const emit = defineEmits(['dismiss', 'navigate']);

const visible = ref(false);
const progress = ref(100);
let progressTimer = null;
let dismissTimer = null;
let audioCtx = null;

const DURATION = 15000;

function playBookingSound() {
    try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const now = audioCtx.currentTime;

        // Ascending chime: C5 -> E5 -> G5 (pleasant triad)
        const notes = [
            { freq: 523.25, start: 0, duration: 0.28 },      // C5
            { freq: 659.25, start: 0.15, duration: 0.28 },    // E5
            { freq: 783.99, start: 0.30, duration: 0.45 },    // G5
        ];

        notes.forEach(({ freq, start, duration }) => {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, now + start);
            gain.gain.setValueAtTime(0, now + start);
            gain.gain.linearRampToValueAtTime(0.18, now + start + 0.03);
            gain.gain.exponentialRampToValueAtTime(0.005, now + start + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(now + start);
            osc.stop(now + start + duration);
        });

        // Soft shimmer tail
        const shimmer = audioCtx.createOscillator();
        const shimmerGain = audioCtx.createGain();
        shimmer.type = 'triangle';
        shimmer.frequency.setValueAtTime(1046.50, now + 0.45); // C6
        shimmerGain.gain.setValueAtTime(0.06, now + 0.45);
        shimmerGain.gain.exponentialRampToValueAtTime(0.001, now + 1.0);
        shimmer.connect(shimmerGain);
        shimmerGain.connect(audioCtx.destination);
        shimmer.start(now + 0.45);
        shimmer.stop(now + 1.0);
    } catch (e) {}
}

function startDismissTimer() {
    const startTime = Date.now();
    progressTimer = setInterval(() => {
        const elapsed = Date.now() - startTime;
        progress.value = Math.max(0, 100 - (elapsed / DURATION) * 100);
        if (progress.value <= 0) clearInterval(progressTimer);
    }, 50);
    dismissTimer = setTimeout(() => {
        dismiss();
    }, DURATION);
}

function dismiss() {
    visible.value = false;
    if (progressTimer) clearInterval(progressTimer);
    if (dismissTimer) clearTimeout(dismissTimer);
    setTimeout(() => emit('dismiss'), 400);
}

function navigate() {
    visible.value = false;
    if (progressTimer) clearInterval(progressTimer);
    if (dismissTimer) clearTimeout(dismissTimer);
    setTimeout(() => emit('navigate'), 300);
}

watch(() => props.booking, (val) => {
    if (val) {
        visible.value = true;
        progress.value = 100;
        playBookingSound();
        startDismissTimer();
    }
}, { immediate: true });

onUnmounted(() => {
    if (progressTimer) clearInterval(progressTimer);
    if (dismissTimer) clearTimeout(dismissTimer);
});

function formatTime(time) {
    if (!time) return '';
    const parts = time.split(':');
    if (parts.length < 2) return time;
    const h = parseInt(parts[0]);
    const m = parts[1];
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

function formatDate(date) {
    if (!date) return '';
    try {
        return new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
            weekday: 'short', month: 'short', day: 'numeric',
        });
    } catch { return date; }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="booking && visible"
                class="fixed inset-0 z-[999999] flex items-center justify-center p-4"
                @click.self="dismiss"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

                <!-- Card -->
                <div
                    :style="{ opacity: visible ? 1 : 0, transform: visible ? 'scale(1) translateY(0)' : 'scale(0.85) translateY(20px)', transition: 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)' }"
                    class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden"
                >
                    <!-- Top accent gradient -->
                    <div class="h-1.5" :style="{ background: `linear-gradient(to right, ${accentColor}, ${accentColor}dd, ${accentColor})` }"></div>

                    <!-- Animated ring background -->
                    <div class="absolute top-0 right-0 w-40 h-40 -mr-10 -mt-10 opacity-[0.04] pointer-events-none">
                        <div class="w-full h-full rounded-full border-[16px]" :style="{ borderColor: accentColor, animation: 'popupRing 2s ease-out infinite' }"></div>
                    </div>

                    <div class="px-6 pt-5 pb-4">
                        <!-- Icon + Badge row -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <!-- Animated calendar icon -->
                                <div
                                    class="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg"
                                    :style="{ background: `linear-gradient(135deg, ${accentColor}, ${accentColor}cc)`, animation: 'popupIconBounce 0.6s ease-out' }"
                                >
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation: popupBellSwing 0.8s ease-in-out;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <span
                                        class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full"
                                        :style="{ backgroundColor: accentColor + '18', color: accentColor }"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full animate-pulse" :style="{ backgroundColor: accentColor }"></span>
                                        New Booking
                                    </span>
                                    <p v-if="booking.bookingNumber" class="text-[11px] text-gray-400 font-mono mt-1">{{ booking.bookingNumber }}</p>
                                </div>
                            </div>
                            <!-- Close -->
                            <button
                                @click="dismiss"
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-300 hover:text-gray-500 hover:bg-gray-100 transition-all"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Patient Name -->
                        <h3 class="text-xl font-bold text-gray-900 leading-tight mb-3">{{ booking.title }}</h3>

                        <!-- Info Grid -->
                        <div class="space-y-2 mb-4">
                            <!-- Service -->
                            <div v-if="booking.serviceName" class="flex items-center gap-3 text-sm">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-[10px] uppercase tracking-wider font-semibold">Service</p>
                                    <p class="text-gray-800 font-medium">{{ booking.serviceName }}</p>
                                </div>
                            </div>

                            <!-- Doctor -->
                            <div v-if="booking.doctorName" class="flex items-center gap-3 text-sm">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-[10px] uppercase tracking-wider font-semibold">Doctor</p>
                                    <p class="text-gray-800 font-medium">{{ booking.doctorName }}</p>
                                </div>
                            </div>

                            <!-- Date & Time -->
                            <div v-if="booking.date || booking.time" class="flex items-center gap-3 text-sm">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-[10px] uppercase tracking-wider font-semibold">Date & Time</p>
                                    <p class="text-gray-800 font-medium">
                                        <span v-if="booking.date">{{ formatDate(booking.date) }}</span>
                                        <span v-if="booking.date && booking.time" class="text-gray-300 mx-1">|</span>
                                        <span v-if="booking.time">{{ formatTime(booking.time) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 pt-2">
                            <button
                                @click="navigate"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-semibold shadow-lg hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5"
                                :style="{ backgroundColor: accentColor }"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View Details
                            </button>
                            <button
                                @click="dismiss"
                                class="px-4 py-2.5 rounded-xl text-gray-500 text-sm font-medium border border-gray-200 hover:bg-gray-50 transition-all duration-200"
                            >
                                Dismiss
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="h-1 bg-gray-100">
                        <div
                            class="h-full transition-all duration-100 ease-linear rounded-full"
                            :style="{ width: progress + '%', backgroundColor: accentColor }"
                        ></div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style>
@keyframes popupIconBounce {
    0% { transform: scale(0.3) rotate(-15deg); opacity: 0; }
    50% { transform: scale(1.15) rotate(3deg); opacity: 1; }
    70% { transform: scale(0.95) rotate(-1deg); }
    100% { transform: scale(1) rotate(0deg); }
}
@keyframes popupBellSwing {
    0% { transform: rotate(0deg); }
    15% { transform: rotate(16deg); }
    30% { transform: rotate(-14deg); }
    45% { transform: rotate(10deg); }
    60% { transform: rotate(-6deg); }
    75% { transform: rotate(3deg); }
    100% { transform: rotate(0deg); }
}
@keyframes popupRing {
    0% { transform: scale(0.8); opacity: 0.08; }
    50% { transform: scale(1.2); opacity: 0.02; }
    100% { transform: scale(0.8); opacity: 0.08; }
}
</style>
