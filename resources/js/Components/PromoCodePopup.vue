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
        // Fallback
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
    // Store the code so the booking form can pick it up
    if (promo.value?.code) {
        localStorage.setItem('promo_code_applied', promo.value.code);
    }
    dismiss();
    window.location.href = localizedRoute('/booking');
}

onMounted(async () => {
    // Check if already dismissed (within last 24 hours)
    const dismissedAt = localStorage.getItem(STORAGE_KEY);
    if (dismissedAt) {
        const elapsed = Date.now() - parseInt(dismissedAt);
        if (elapsed < 24 * 60 * 60 * 1000) return;
    }

    try {
        const { data } = await axios.get('/api/active-promo');
        if (!data.active) return;

        promo.value = data;

        // Start countdown if there's an end date
        if (data.end_date) {
            updateCountdown();
            countdownTimer = setInterval(updateCountdown, 1000);
        }

        // Show popup after delay
        setTimeout(() => {
            isVisible.value = true;
        }, SHOW_DELAY);
    } catch {
        // Silently fail - promo popup is non-critical
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
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm promoBackdropIn"></div>

                <!-- Card -->
                <div
                    class="relative w-full max-w-[420px] bg-white rounded-3xl shadow-2xl overflow-hidden promoCardIn"
                    :dir="isRtl ? 'rtl' : 'ltr'"
                >
                    <!-- Top decorative bar -->
                    <div class="h-1.5 bg-gradient-to-r from-[#C4A265] via-[#E8D5A8] to-[#C4A265]"></div>

                    <!-- Close button -->
                    <button
                        @click="dismiss"
                        class="absolute top-4 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-white shadow-sm transition-all duration-200"
                        :class="isRtl ? 'left-4' : 'right-4'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Header section with gradient background -->
                    <div class="relative px-6 pt-8 pb-6 text-center overflow-hidden">
                        <!-- Background decoration -->
                        <div class="absolute inset-0 bg-gradient-to-b from-[#FBF7EE] to-white"></div>
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[300px] h-[300px] rounded-full bg-[#C4A265]/[0.04] -mt-[150px]"></div>

                        <!-- Animated offer icon -->
                        <div class="relative">
                            <div class="mx-auto w-20 h-20 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#A68B52] flex items-center justify-center shadow-xl shadow-[#C4A265]/30 promoIconBounce">
                                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                            </div>

                            <!-- Discount badge -->
                            <div class="absolute -top-1 -right-1 left-auto mx-auto w-fit" :class="isRtl ? '-left-1 right-auto' : '-right-1 left-auto'" style="transform: translateX(50%)">
                                <div class="bg-red-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg promoBadgePulse">
                                    {{ discountLabel }}
                                    {{ locale === 'ar' ? 'خصم' : 'OFF' }}
                                </div>
                            </div>
                        </div>

                        <!-- Title -->
                        <h2 class="relative mt-5 text-2xl font-bold text-gray-900 leading-tight">{{ title }}</h2>

                        <!-- Description -->
                        <p class="relative mt-2 text-sm text-gray-500 leading-relaxed max-w-[280px] mx-auto">{{ description }}</p>
                    </div>

                    <!-- Code display section -->
                    <div class="px-6 pb-4">
                        <div class="relative bg-[#FBF7EE] border-2 border-dashed border-[#C4A265]/40 rounded-2xl p-4">
                            <!-- Scissors decoration -->
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-white px-2">
                                <svg class="w-5 h-5 text-[#C4A265]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" />
                                </svg>
                            </div>

                            <p class="text-center text-[11px] font-semibold uppercase tracking-widest text-[#C4A265]/70 mb-2">
                                {{ locale === 'ar' ? 'كود الخصم' : 'Discount Code' }}
                            </p>

                            <!-- Code with copy button -->
                            <div class="flex items-center justify-center gap-3">
                                <span class="text-2xl font-mono font-black tracking-[0.2em] text-gray-800 select-all promoCodeReveal">
                                    {{ promo.code }}
                                </span>
                                <button
                                    @click="copyCode"
                                    class="group flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all duration-300"
                                    :class="copied
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-[#C4A265]/10 text-[#C4A265] hover:bg-[#C4A265]/20'"
                                >
                                    <svg v-if="!copied" class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 promoCopyCheck" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-xs font-semibold">
                                        {{ copied ? (locale === 'ar' ? 'تم النسخ!' : 'Copied!') : (locale === 'ar' ? 'نسخ' : 'Copy') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Countdown timer -->
                    <div v-if="hasCountdown" class="px-6 pb-4">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs text-gray-500 font-medium">
                                {{ locale === 'ar' ? 'ينتهي خلال' : 'Expires in' }}
                            </span>
                            <div class="flex items-center gap-1">
                                <template v-if="countdown.days > 0">
                                    <span class="inline-flex items-center justify-center bg-gray-100 text-gray-800 text-xs font-bold px-2 py-0.5 rounded min-w-[28px]">{{ countdown.days }}</span>
                                    <span class="text-[10px] text-gray-400">{{ locale === 'ar' ? 'ي' : 'd' }}</span>
                                </template>
                                <span class="inline-flex items-center justify-center bg-gray-100 text-gray-800 text-xs font-bold px-2 py-0.5 rounded min-w-[28px]">{{ String(countdown.hours).padStart(2, '0') }}</span>
                                <span class="text-gray-400 text-xs font-bold">:</span>
                                <span class="inline-flex items-center justify-center bg-gray-100 text-gray-800 text-xs font-bold px-2 py-0.5 rounded min-w-[28px]">{{ String(countdown.minutes).padStart(2, '0') }}</span>
                                <span class="text-gray-400 text-xs font-bold">:</span>
                                <span class="inline-flex items-center justify-center bg-gray-100 text-gray-800 text-xs font-bold px-2 py-0.5 rounded min-w-[28px]">{{ String(countdown.seconds).padStart(2, '0') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="px-6 pb-6">
                        <button
                            @click="bookNow"
                            class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-white font-bold text-base shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/35 transition-all duration-300 hover:-translate-y-0.5 bg-gradient-to-r from-[#C4A265] to-[#A68B52]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ locale === 'ar' ? 'احجز الان' : 'Book Now' }}
                        </button>
                        <button
                            @click="dismiss"
                            class="w-full mt-2 px-4 py-2 text-sm text-gray-400 hover:text-gray-600 transition-colors duration-200"
                        >
                            {{ locale === 'ar' ? 'لاحقا' : 'Maybe Later' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style>
/* Popup card entrance */
.promoCardIn {
    animation: promoCardSlideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes promoCardSlideIn {
    0% { opacity: 0; transform: scale(0.8) translateY(30px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

/* Backdrop fade */
.promoBackdropIn {
    animation: promoBackdropFade 0.4s ease-out both;
}
@keyframes promoBackdropFade {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

/* Icon bounce entrance */
.promoIconBounce {
    animation: promoIconBounceKf 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
}
@keyframes promoIconBounceKf {
    0% { opacity: 0; transform: scale(0) rotate(-20deg); }
    60% { opacity: 1; transform: scale(1.15) rotate(5deg); }
    80% { transform: scale(0.95) rotate(-2deg); }
    100% { transform: scale(1) rotate(0deg); }
}

/* Discount badge pulse */
.promoBadgePulse {
    animation: promoBadgePulseKf 2s ease-in-out infinite;
}
@keyframes promoBadgePulseKf {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
}

/* Code reveal animation */
.promoCodeReveal {
    animation: promoCodeRevealKf 0.8s ease-out 0.5s both;
}
@keyframes promoCodeRevealKf {
    0% { opacity: 0; letter-spacing: 0.5em; filter: blur(4px); }
    100% { opacity: 1; letter-spacing: 0.2em; filter: blur(0px); }
}

/* Copy checkmark animation */
.promoCopyCheck {
    animation: promoCopyCheckKf 0.4s ease-out both;
}
@keyframes promoCopyCheckKf {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

/* Smooth transition duration */
.duration-600 {
    transition-duration: 600ms;
}
</style>
