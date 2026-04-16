<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useLocale } from '@/Composables/useLocale';
import axios from 'axios';

const { locale, isRtl, localized, localizedRoute } = useLocale();

const isVisible = ref(false);
const promo = ref(null);
const copied = ref(false);
const copyTimeout = ref(null);
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let countdownTimer = null;

const STORAGE_KEY = 'promo_popup_dismissed';
const SHOW_DELAY = 3000;

const discountLabel = computed(() => {
    if (!promo.value) return '';
    if (promo.value.discount_type === 'percentage') {
        return `${Number(promo.value.discount_value)}%`;
    }
    return `${Number(promo.value.discount_value)} SAR`;
});

const title = computed(() => {
    if (!promo.value) return '';
    const custom = locale.value === 'ar' ? promo.value.popup_title_ar : promo.value.popup_title_en;
    if (custom) return custom;
    return locale.value === 'ar' ? 'عرض خاص لك!' : 'Special Offer For You!';
});

const description = computed(() => {
    if (!promo.value) return '';
    const custom = locale.value === 'ar' ? promo.value.popup_description_ar : promo.value.popup_description_en;
    if (custom) return custom;
    return locale.value === 'ar'
        ? 'استخدم كود الخصم للحصول على خصم على حجزك القادم'
        : 'Use this discount code to get a special discount on your next booking';
});

const hasCountdown = computed(() => {
    return promo.value?.end_date && (
        countdown.value.days > 0 ||
        countdown.value.hours > 0 ||
        countdown.value.minutes > 0 ||
        countdown.value.seconds > 0
    );
});

function updateCountdown() {
    if (!promo.value?.end_date) return;
    const now = new Date();
    const end = new Date(promo.value.end_date);
    const diff = end - now;

    if (diff <= 0) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        if (countdownTimer) clearInterval(countdownTimer);
        return;
    }

    countdown.value = {
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((diff / (1000 * 60)) % 60),
        seconds: Math.floor((diff / 1000) % 60),
    };
}

async function copyCode() {
    if (!promo.value?.code) return;
    try {
        await navigator.clipboard.writeText(promo.value.code);
        copied.value = true;
        if (copyTimeout.value) clearTimeout(copyTimeout.value);
        copyTimeout.value = setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        const textArea = document.createElement('textarea');
        textArea.value = promo.value.code;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        copied.value = true;
        if (copyTimeout.value) clearTimeout(copyTimeout.value);
        copyTimeout.value = setTimeout(() => { copied.value = false; }, 2000);
    }
}

function dismiss() {
    isVisible.value = false;
    localStorage.setItem(STORAGE_KEY, Date.now().toString());
}

function bookNow() {
    if (promo.value?.code) {
        localStorage.setItem('promo_code_applied', promo.value.code);
    }
    dismiss();
    window.location.href = localizedRoute('/booking');
}

onMounted(async () => {
    const dismissedAt = localStorage.getItem(STORAGE_KEY);
    if (dismissedAt) {
        const elapsed = Date.now() - parseInt(dismissedAt);
        if (elapsed < 24 * 60 * 60 * 1000) return;
    }

    try {
        const { data } = await axios.get('/api/active-promo');
        if (!data.active) return;

        promo.value = data;

        if (data.end_date) {
            updateCountdown();
            countdownTimer = setInterval(updateCountdown, 1000);
        }

        setTimeout(() => {
            isVisible.value = true;
        }, SHOW_DELAY);
    } catch {
        // Silently fail
    }
});

onUnmounted(() => {
    if (countdownTimer) clearInterval(countdownTimer);
    if (copyTimeout.value) clearTimeout(copyTimeout.value);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-600 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="promo && isVisible"
                class="fixed inset-0 z-[999999] flex items-center justify-center p-4"
                @click.self="dismiss"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/60 backdrop-blur-md promoBackdropIn"></div>

                <!-- Card -->
                <div
                    class="relative w-full max-w-[400px] overflow-hidden promoCardIn"
                    :dir="isRtl ? 'rtl' : 'ltr'"
                >
                    <!-- Main card with glass morphism -->
                    <div class="relative bg-white rounded-[28px] shadow-2xl shadow-black/20 overflow-hidden">

                        <!-- Animated gradient header -->
                        <div class="relative h-36 overflow-hidden promo-header-bg">
                            <!-- Floating particles -->
                            <div class="absolute inset-0">
                                <div class="promo-particle promo-particle-1"></div>
                                <div class="promo-particle promo-particle-2"></div>
                                <div class="promo-particle promo-particle-3"></div>
                                <div class="promo-particle promo-particle-4"></div>
                            </div>

                            <!-- Close button -->
                            <button
                                @click="dismiss"
                                class="absolute top-3 z-10 w-8 h-8 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white/80 hover:text-white hover:bg-white/30 transition-all duration-200"
                                :class="isRtl ? 'left-3' : 'right-3'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <!-- Discount badge -->
                            <div class="absolute top-3 z-10" :class="isRtl ? 'right-3' : 'left-3'">
                                <div class="bg-[#C4A265] text-white text-sm font-black px-3 py-1 rounded-full shadow-lg promoBadgePulse flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm2.5 3a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm6.207.293a1 1 0 00-1.414 0l-6 6a1 1 0 101.414 1.414l6-6a1 1 0 000-1.414zM12.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" clip-rule="evenodd" /></svg>
                                    {{ discountLabel }}
                                </div>
                            </div>

                            <!-- Center icon -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="promoIconBounce">
                                    <div class="w-20 h-20 rounded-[20px] bg-gradient-to-br from-[#C4A265] to-[#A68B52] flex items-center justify-center shadow-xl shadow-black/30 border-2 border-[#C4A265]/60">
                                        <svg class="w-10 h-10 text-white drop-shadow-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="px-6 pt-5 pb-2 text-center">
                            <h2 class="text-xl font-black text-gray-900 leading-tight">{{ title }}</h2>
                            <p class="mt-2 text-[13px] text-gray-500 leading-relaxed max-w-[300px] mx-auto">{{ description }}</p>
                        </div>

                        <!-- Promo code coupon -->
                        <div class="mx-5 my-3">
                            <div class="relative flex items-center bg-gradient-to-r from-[#0f1d3a] to-[#1a2d5a] rounded-2xl overflow-hidden border border-[#C4A265]/30">
                                <!-- Coupon notches -->
                                <div class="absolute -left-2.5 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-white"></div>
                                <div class="absolute -right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-white"></div>

                                <!-- Code section -->
                                <div class="flex-1 py-3.5 px-6 text-center border-e border-dashed border-[#C4A265]/40">
                                    <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-[#C4A265] mb-1">
                                        {{ locale === 'ar' ? 'كود الخصم' : 'PROMO CODE' }}
                                    </p>
                                    <span class="text-xl font-mono font-black tracking-[0.15em] text-white promoCodeReveal">
                                        {{ promo.code }}
                                    </span>
                                </div>

                                <!-- Copy button -->
                                <button
                                    @click="copyCode"
                                    class="px-4 py-3.5 flex flex-col items-center gap-1 transition-all duration-300 hover:bg-white/10"
                                    :class="copied ? 'text-emerald-400' : 'text-[#C4A265]'"
                                >
                                    <svg v-if="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 promoCopyCheck" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-[10px] font-bold">
                                        {{ copied ? (locale === 'ar' ? 'تم!' : 'Done!') : (locale === 'ar' ? 'نسخ' : 'Copy') }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Countdown timer -->
                        <div v-if="hasCountdown" class="flex items-center justify-center gap-1.5 py-2">
                            <svg class="w-3.5 h-3.5 text-red-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-[11px] text-gray-400 font-medium">
                                {{ locale === 'ar' ? 'ينتهي خلال' : 'Ends in' }}
                            </span>
                            <div class="flex items-center gap-0.5">
                                <template v-if="countdown.days > 0">
                                    <span class="promo-timer-digit">{{ countdown.days }}</span>
                                    <span class="text-[9px] text-gray-400 mx-0.5">{{ locale === 'ar' ? 'ي' : 'd' }}</span>
                                </template>
                                <span class="promo-timer-digit">{{ String(countdown.hours).padStart(2, '0') }}</span>
                                <span class="text-gray-300 text-[10px] font-bold mx-px">:</span>
                                <span class="promo-timer-digit">{{ String(countdown.minutes).padStart(2, '0') }}</span>
                                <span class="text-gray-300 text-[10px] font-bold mx-px">:</span>
                                <span class="promo-timer-digit promo-timer-seconds">{{ String(countdown.seconds).padStart(2, '0') }}</span>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="px-5 pb-5 pt-2">
                            <button
                                @click="bookNow"
                                class="promo-book-btn w-full flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl text-white font-bold text-[15px] transition-all duration-300 hover:-translate-y-0.5"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ locale === 'ar' ? 'احجز الآن' : 'Book Now' }}
                            </button>
                            <button
                                @click="dismiss"
                                class="w-full mt-2 px-4 py-2 text-xs text-gray-400 hover:text-gray-600 transition-colors duration-200"
                            >
                                {{ locale === 'ar' ? 'لاحقاً' : 'Maybe Later' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style>
/* ── Header gradient ─────────────────── */
.promo-header-bg {
    background: linear-gradient(135deg, #0a1528 0%, #0f1d3a 30%, #1a2d5a 70%, #0f1d3a 100%);
}

/* ── Floating particles ──────────────── */
.promo-particle {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(196, 162, 101, 0.3), rgba(196, 162, 101, 0.08));
    animation: promoFloat 6s ease-in-out infinite;
}
.promo-particle-1 { width: 80px; height: 80px; top: -20px; right: -10px; animation-delay: 0s; }
.promo-particle-2 { width: 50px; height: 50px; bottom: -10px; left: 20px; animation-delay: 1.5s; }
.promo-particle-3 { width: 30px; height: 30px; top: 30px; left: 40%; animation-delay: 3s; }
.promo-particle-4 { width: 60px; height: 60px; bottom: 10px; right: 20%; animation-delay: 4.5s; }
@keyframes promoFloat {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
    50% { transform: translateY(-12px) scale(1.1); opacity: 0.55; }
}

/* ── Card entrance ───────────────────── */
.promoCardIn {
    animation: promoCardSlideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes promoCardSlideIn {
    0% { opacity: 0; transform: scale(0.85) translateY(40px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

/* ── Backdrop ────────────────────────── */
.promoBackdropIn {
    animation: promoBackdropFade 0.4s ease-out both;
}
@keyframes promoBackdropFade {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

/* ── Icon bounce ─────────────────────── */
.promoIconBounce {
    animation: promoIconBounceKf 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
}
@keyframes promoIconBounceKf {
    0% { opacity: 0; transform: scale(0) rotate(-15deg); }
    60% { opacity: 1; transform: scale(1.12) rotate(3deg); }
    80% { transform: scale(0.95) rotate(-1deg); }
    100% { transform: scale(1) rotate(0deg); }
}

/* ── Badge pulse ─────────────────────── */
.promoBadgePulse {
    animation: promoBadgePulseKf 2s ease-in-out infinite;
}
@keyframes promoBadgePulseKf {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.06); }
}

/* ── Code reveal ─────────────────────── */
.promoCodeReveal {
    animation: promoCodeRevealKf 0.8s ease-out 0.5s both;
}
@keyframes promoCodeRevealKf {
    0% { opacity: 0; letter-spacing: 0.4em; filter: blur(4px); }
    100% { opacity: 1; letter-spacing: 0.15em; filter: blur(0); }
}

/* ── Copy check animation ────────────── */
.promoCopyCheck {
    animation: promoCopyCheckKf 0.4s ease-out both;
}
@keyframes promoCopyCheckKf {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); opacity: 1; }
}

/* ── Timer digits ────────────────────── */
.promo-timer-digit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    padding: 2px 5px;
    font-size: 11px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    color: #374151;
    background: #f3f4f6;
    border-radius: 6px;
}
.promo-timer-seconds {
    color: #ef4444;
    background: #fef2f2;
}

/* ── Book Now button ─────────────────── */
.promo-book-btn {
    background: linear-gradient(135deg, #0f1d3a 0%, #1a2d5a 100%);
    box-shadow: 0 8px 24px -4px rgba(15, 29, 58, 0.5);
    border: 1px solid rgba(196, 162, 101, 0.3);
}
.promo-book-btn:hover {
    background: linear-gradient(135deg, #1a2d5a 0%, #0f1d3a 100%);
    box-shadow: 0 12px 32px -4px rgba(15, 29, 58, 0.6);
    border-color: rgba(196, 162, 101, 0.5);
}

/* ── Transition duration ─────────────── */
.duration-600 { transition-duration: 600ms; }
</style>
