<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { useCurrency } from '@/Composables/useCurrency';
import { toEnglishNumbers } from '@/Composables/useArabicNumbers';
import SeoHead from '@/Components/Frontend/SeoHead.vue';
import PromoCodeInput from '@/Components/PromoCodeInput.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    bundle: { type: Object, required: true },
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();
const { formatCurrency, currencyCode } = useCurrency();
const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const bundleName = computed(() => localized(props.bundle, 'name'));

const form = useForm({
    full_name: '',
    phone: '',
    notes: '',
    promo_code: '',
});

const preferredDate = ref('');
const preferredTime = ref('');
const submitted = ref(false);

const todayDate = computed(() => {
    const d = new Date();
    return d.toISOString().split('T')[0];
});

function submitBooking() {
    form.transform((data) => {
        let notes = data.notes || '';
        if (preferredDate.value || preferredTime.value) {
            const parts = [];
            if (preferredDate.value) parts.push(`Preferred Date: ${preferredDate.value}`);
            if (preferredTime.value) parts.push(`Preferred Time: ${preferredTime.value}`);
            const prefix = parts.join(' | ');
            notes = notes ? `${prefix}\n${notes}` : prefix;
        }
        return { ...data, notes };
    }).post(localizedRoute(`/package-bundles/${props.bundle.id}/book`), {
        preserveScroll: true,
        onSuccess: () => {
            submitted.value = true;
        },
    });
}

function handlePhoneInput() {
    // Convert Arabic/Persian digits to English, then strip any non-digit characters
    form.phone = toEnglishNumbers(form.phone).replace(/[^0-9]/g, '');
}

function serviceName(bundleService) {
    return bundleService.service ? localized(bundleService.service, 'name') : '';
}

const totalSessions = computed(() => {
    if (!props.bundle.services) return 0;
    return props.bundle.services.reduce((sum, s) => sum + (s.sessions_count || 1), 0);
});

const discountPercent = computed(() => {
    const original = Number(props.bundle.original_price);
    const total = Number(props.bundle.total_price);
    if (!original || original <= total) return 0;
    return Math.round(((original - total) / original) * 100);
});

/* ---- Staggered Reveal System ---- */
const visibleSections = ref(new Set());

function reveal(key, delay = 0) {
    setTimeout(() => visibleSections.value.add(key), delay);
}

function isVisible(key) {
    return visibleSections.value.has(key);
}

/* ---- Animated Price Counter ---- */
const animatedPrice = ref(0);
const animatedSavings = ref(0);

function animateCounter(target, end, duration) {
    const startTime = performance.now();
    function step(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        target.value = Math.round(ease * end);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

/* ---- Form Focus Tracking ---- */
const focusedField = ref('');

onMounted(() => {
    reveal('hero', 100);
    reveal('form', 250);
    reveal('sidebar', 350);
    reveal('services', 500);
    reveal('trust', 650);

    animateCounter(animatedPrice, Number(props.bundle.total_price || 0), 1200);
    animateCounter(animatedSavings, Number(props.bundle.savings || 0), 1400);
});
</script>

<template>
    <SeoHead :title="t('booking') + ' - ' + bundleName" />

    <div class="book-page">
        <!-- ==================== HERO ==================== -->
        <section class="relative bg-gradient-to-br from-[#3A3A3A] via-[#3a3a3a] to-[#3A3A3A] py-16 lg:py-24 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-diagonal"></div>
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 start-10 w-72 h-72 bg-[var(--brand-primary)] rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 end-10 w-96 h-96 bg-[var(--brand-primary)] rounded-full blur-3xl"></div>
            </div>
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[var(--brand-primary)] to-transparent"></div>

            <!-- Floating elements -->
            <div class="absolute top-10 start-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-14 end-14 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/2 end-1/3 w-10 h-10 rounded-full bg-gold-light/5 animate-float-delay"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div :class="['hero-content', { 'hero-content--visible': isVisible('hero') }]">
                    <!-- Breadcrumb -->
                    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-8 flex-wrap">
                        <Link :href="localizedRoute('/')" class="hover:text-[var(--brand-primary)] transition-colors duration-300">{{ t('home') }}</Link>
                        <span class="text-gray-600">/</span>
                        <Link :href="localizedRoute('/package-bundles')" class="hover:text-[var(--brand-primary)] transition-colors duration-300">{{ t('package_bundles') }}</Link>
                        <span class="text-gray-600">/</span>
                        <Link :href="localizedRoute(`/package-bundles/${bundle.id}`)" class="hover:text-[var(--brand-primary)] transition-colors duration-300">{{ bundleName }}</Link>
                        <span class="text-gray-600">/</span>
                        <span class="text-[var(--brand-primary)]">{{ t('booking') }}</span>
                    </nav>

                    <div class="flex items-center gap-4 mb-4">
                        <div class="hero-icon">
                            <svg class="w-7 h-7 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-4xl font-bold text-white">
                                {{ t('book_package_title') }}
                            </h1>
                        </div>
                    </div>
                    <p class="text-gray-300 text-lg max-w-2xl">{{ bundleName }}</p>

                    <!-- Quick Stats Row -->
                    <div class="flex flex-wrap items-center gap-6 mt-8">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-full bg-[var(--brand-primary)]/15 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-white font-bold text-lg">{{ bundle.services?.length || 0 }}</div>
                                <div class="text-gray-400 text-xs">{{ t('services') }}</div>
                            </div>
                        </div>
                        <div class="w-px h-10 bg-gray-600"></div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-full bg-[var(--brand-primary)]/15 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-white font-bold text-lg">{{ totalSessions }}</div>
                                <div class="text-gray-400 text-xs">{{ t('sessions') }}</div>
                            </div>
                        </div>
                        <div v-if="discountPercent > 0" class="w-px h-10 bg-gray-600"></div>
                        <div v-if="discountPercent > 0" class="savings-hero-badge">
                            {{ t('save_amount') }} {{ discountPercent }}%
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== SUCCESS FLASH ==================== -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
        >
            <div v-if="successMessage" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                <div class="success-flash">
                    <div class="success-flash__icon">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-emerald-800 mb-0.5">{{ t('booking_success_title') || t('success') }}</h3>
                        <p class="text-emerald-700 text-sm">{{ successMessage }}</p>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ==================== MAIN CONTENT ==================== -->
        <section class="relative py-12 lg:py-20 bg-[#FDF8F0] overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-dots"></div>
            <div class="absolute top-20 start-6 w-24 h-24 rounded-full bg-gold-primary/5 animate-float-slow"></div>
            <div class="absolute bottom-20 end-8 w-16 h-16 rounded-full bg-gold-primary/8 animate-float"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">

                    <!-- ========== LEFT: BOOKING FORM ========== -->
                    <div class="lg:col-span-7" :class="['form-panel', { 'form-panel--visible': isVisible('form') }]">
                        <form @submit.prevent="submitBooking" class="booking-form">
                            <!-- Form Header -->
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-[var(--brand-primary)]/10 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-[#3A3A3A]">{{ t('booking_details') }}</h2>
                                    <p class="text-sm text-gray-400">{{ t('booking_fill_details') || t('booking_confirmation_note') }}</p>
                                </div>
                            </div>

                            <!-- Step Indicators -->
                            <div class="step-indicators">
                                <div class="step-dot step-dot--active">
                                    <span class="step-dot__num">1</span>
                                </div>
                                <div class="step-line" :class="{ 'step-line--done': form.full_name && form.phone }"></div>
                                <div class="step-dot" :class="{ 'step-dot--active': form.full_name && form.phone }">
                                    <span class="step-dot__num">2</span>
                                </div>
                                <div class="step-line" :class="{ 'step-line--done': form.full_name && form.phone && !form.processing }"></div>
                                <div class="step-dot" :class="{ 'step-dot--active': submitted }">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </div>
                            </div>

                            <!-- Full Name Field -->
                            <div class="form-group" :class="{ 'form-group--focused': focusedField === 'name', 'form-group--error': form.errors.full_name }">
                                <label class="form-label">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ t('full_name_label') }} <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="form.full_name"
                                    type="text"
                                    required
                                    :placeholder="t('full_name_placeholder')"
                                    class="form-control form-input-animated"
                                    :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.full_name }"
                                    @focus="focusedField = 'name'"
                                    @blur="focusedField = ''"
                                />
                                <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                                    <p v-if="form.errors.full_name" class="form-error">{{ form.errors.full_name }}</p>
                                </Transition>
                            </div>

                            <!-- Phone Field -->
                            <div class="form-group" :class="{ 'form-group--focused': focusedField === 'phone', 'form-group--error': form.errors.phone }">
                                <label class="form-label">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    {{ t('phone_label') }} <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    inputmode="numeric"
                                    required
                                    dir="ltr"
                                    placeholder="01xxxxxxxxx"
                                    maxlength="11"
                                    class="form-control form-input-animated"
                                    :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.phone }"
                                    @input="handlePhoneInput"
                                    @focus="focusedField = 'phone'"
                                    @blur="focusedField = ''"
                                />
                                <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                                    <p v-if="form.errors.phone" class="form-error">{{ form.errors.phone }}</p>
                                </Transition>
                            </div>

                            <!-- Preferred Date & Time (optional) -->
                            <div class="preferred-datetime">
                                <p class="preferred-datetime__label">
                                    <svg class="w-4 h-4 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ t('preferred_schedule') || t('preferred_date_time') || 'Preferred Schedule' }}
                                    <span class="text-gray-400 font-normal text-xs">({{ t('optional') || 'Optional' }})</span>
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="form-group mb-0" :class="{ 'form-group--focused': focusedField === 'preferred_date' }">
                                        <label class="form-label text-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            {{ t('preferred_date') || 'Preferred Date' }}
                                        </label>
                                        <input
                                            v-model="preferredDate"
                                            type="date"
                                            :min="todayDate"
                                            class="form-control form-input-animated text-sm"
                                            @focus="focusedField = 'preferred_date'"
                                            @blur="focusedField = ''"
                                        />
                                    </div>
                                    <div class="form-group mb-0" :class="{ 'form-group--focused': focusedField === 'preferred_time' }">
                                        <label class="form-label text-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ t('preferred_time') || 'Preferred Time' }}
                                        </label>
                                        <input
                                            v-model="preferredTime"
                                            type="time"
                                            class="form-control form-input-animated text-sm"
                                            @focus="focusedField = 'preferred_time'"
                                            @blur="focusedField = ''"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Field -->
                            <div class="form-group" :class="{ 'form-group--focused': focusedField === 'notes' }">
                                <label class="form-label">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                    {{ t('additional_notes_label') }}
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="4"
                                    :placeholder="t('notes_placeholder')"
                                    class="form-control form-input-animated resize-none"
                                    @focus="focusedField = 'notes'"
                                    @blur="focusedField = ''"
                                ></textarea>
                            </div>

                            <!-- Promo Code -->
                            <PromoCodeInput
                                v-model="form.promo_code"
                                booking-type="package"
                                :package-bundle-id="bundle.id"
                                :amount="Number(bundle.total_price) || 0"
                            />

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="submit-btn btn-shimmer"
                            >
                                <span v-if="form.processing" class="flex items-center justify-center gap-2.5">
                                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    {{ t('submitting_booking') }}
                                </span>
                                <span v-else class="flex items-center justify-center gap-2.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ t('confirm_booking') }}
                                </span>
                            </button>

                            <!-- Trust Badges -->
                            <div :class="['trust-badges', { 'trust-badges--visible': isVisible('trust') }]">
                                <div class="trust-badge">
                                    <svg class="w-4 h-4 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    <span>{{ t('secure_booking') || t('booking_confirmation_note') }}</span>
                                </div>
                                <div class="trust-badge">
                                    <svg class="w-4 h-4 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    <span>{{ t('we_will_call_you') || t('booking_confirmation_note') }}</span>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- ========== RIGHT: SIDEBAR ========== -->
                    <div class="lg:col-span-5" :class="['sidebar-panel', { 'sidebar-panel--visible': isVisible('sidebar') }]">
                        <div class="sidebar-sticky">
                            <!-- Package Summary Card -->
                            <div class="summary-card">
                                <div class="summary-card__header">
                                    <h3 class="text-lg font-bold text-white">{{ t('package_summary') }}</h3>
                                </div>

                                <!-- Bundle Image -->
                                <div class="summary-card__image img-hover-reveal">
                                    <div class="aspect-[16/9] bg-gradient-to-br from-[var(--brand-primary)]/20 to-[var(--brand-primary)]/5">
                                        <img v-if="bundle.image_url" :src="bundle.image_url" :alt="bundleName" class="w-full h-full object-cover" />
                                        <div v-else class="flex items-center justify-center h-full">
                                            <svg class="w-16 h-16 text-[var(--brand-primary)]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        </div>
                                    </div>
                                    <!-- Savings Badge -->
                                    <div v-if="discountPercent > 0" class="savings-badge-overlay">
                                        {{ t('save_amount') }} {{ discountPercent }}%
                                    </div>
                                </div>

                                <div class="p-5">
                                    <h4 class="font-bold text-[#3A3A3A] text-lg mb-4">{{ bundleName }}</h4>

                                    <!-- Services Checklist -->
                                    <div :class="['services-list', { 'services-list--visible': isVisible('services') }]">
                                        <div
                                            v-for="(bs, idx) in bundle.services"
                                            :key="bs.id"
                                            class="service-check-item"
                                            :style="{ animationDelay: `${idx * 100 + 600}ms` }"
                                        >
                                            <div class="service-check-icon">
                                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-medium text-[#3A3A3A]">{{ serviceName(bs) }}</span>
                                                <span class="text-xs text-gray-400 block">{{ bs.sessions_count || 1 }} {{ t('sessions') }}</span>
                                            </div>
                                            <div v-if="Number(bs.discount_percentage) > 0" class="service-discount">
                                                -{{ bs.discount_percentage }}%
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price Breakdown -->
                                    <div class="price-breakdown">
                                        <div v-if="Number(bundle.original_price) > Number(bundle.total_price)" class="price-row">
                                            <span class="text-gray-400 text-sm">{{ t('original_price_label') }}</span>
                                            <span class="text-gray-400 text-sm line-through">{{ formatCurrency(bundle.original_price) }}</span>
                                        </div>
                                        <div v-if="Number(bundle.savings) > 0" class="price-row price-row--savings">
                                            <span class="text-emerald-600 text-sm font-medium">{{ t('you_save') }}</span>
                                            <span class="savings-amount">-{{ formatCurrency(animatedSavings) }}</span>
                                        </div>
                                        <div class="price-total">
                                            <span class="text-[#3A3A3A] font-bold">{{ t('total') }}</span>
                                            <div class="price-total__value">
                                                <span class="text-2xl font-bold text-[var(--brand-primary)]">{{ formatCurrency(animatedPrice) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- WhatsApp CTA -->
                            <a :href="whatsappLink" target="_blank" rel="noopener" class="whatsapp-cta card-hover-lift">
                                <div class="whatsapp-cta__icon">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" /><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.387 0-4.592-.794-6.362-2.134l-.444-.332-3.262 1.094 1.094-3.262-.332-.444A9.958 9.958 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z" /></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-[#3A3A3A] text-sm">{{ t('need_help') || t('whatsapp') }}</div>
                                    <div class="text-xs text-gray-400">{{ t('whatsapp_us') || t('whatsapp') }}</div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </a>

                            <!-- Back to Package Link -->
                            <Link :href="localizedRoute(`/package-bundles/${bundle.id}`)" class="back-link">
                                <svg class="w-4 h-4" :class="{ 'rotate-180': !isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                {{ t('view_package_details') || t('package_bundles') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* ============================================
   HERO ANIMATIONS
   ============================================ */
.hero-content {
    opacity: 0;
    transform: translateY(24px);
    transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.hero-content--visible {
    opacity: 1;
    transform: translateY(0);
}

.hero-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(196, 162, 101, 0.15), rgba(196, 162, 101, 0.05));
    border: 1px solid rgba(196, 162, 101, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s ease;
}
.hero-icon:hover {
    transform: scale(1.08);
    box-shadow: 0 0 20px rgba(196, 162, 101, 0.2);
}

.savings-hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    font-size: 0.8rem;
    font-weight: 700;
    border-radius: 9999px;
    animation: badgePop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.8s both;
}

@keyframes badgePop {
    0% { opacity: 0; transform: scale(0.6); }
    100% { opacity: 1; transform: scale(1); }
}

/* ============================================
   SUCCESS FLASH
   ============================================ */
.success-flash {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0;
    border-radius: 16px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);
}
.success-flash__icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.15);
}

/* ============================================
   FORM PANEL
   ============================================ */
.form-panel {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.1s;
}
.form-panel--visible {
    opacity: 1;
    transform: translateY(0);
}

.booking-form {
    background: white;
    border-radius: 20px;
    padding: 32px;
    border: 1px solid rgba(196, 162, 101, 0.1);
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
}

@media (min-width: 640px) {
    .booking-form {
        padding: 40px;
    }
}

/* ============================================
   STEP INDICATORS
   ============================================ */
.step-indicators {
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 32px;
    padding: 0 8px;
}

.step-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f3f4f6;
    border: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    color: #9ca3af;
}

.step-dot--active {
    background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
    border-color: var(--brand-primary);
    color: white;
    box-shadow: 0 2px 12px rgba(196, 162, 101, 0.3);
}

.step-dot__num {
    font-size: 0.75rem;
    font-weight: 700;
}

.step-line {
    flex: 1;
    height: 2px;
    background: #e5e7eb;
    transition: background 0.6s ease;
}

.step-line--done {
    background: linear-gradient(90deg, var(--brand-primary), var(--brand-secondary));
}

/* ============================================
   FORM FIELDS
   ============================================ */
.form-group {
    margin-bottom: 24px;
    position: relative;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #3A3A3A;
    margin-bottom: 8px;
    transition: color 0.3s ease;
}

.form-group--focused .form-label {
    color: var(--brand-primary);
}

.form-group--error .form-label {
    color: #ef4444;
}

.form-control {
    width: 100%;
    padding: 14px 18px;
    border: 1.5px solid #e5e7eb;
    border-radius: 14px;
    background: #fafafa;
    color: #3A3A3A;
    font-size: 0.95rem;
    outline: none;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.form-control:focus {
    border-color: var(--brand-primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(196, 162, 101, 0.12);
}

.form-control::placeholder {
    color: #b0b0b0;
}

.form-error {
    margin-top: 6px;
    font-size: 0.8rem;
    color: #ef4444;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ============================================
   PREFERRED DATE/TIME
   ============================================ */
.preferred-datetime {
    margin-bottom: 24px;
    padding: 16px 20px;
    background: linear-gradient(135deg, rgba(196, 162, 101, 0.04), rgba(196, 162, 101, 0.02));
    border: 1px solid rgba(196, 162, 101, 0.12);
    border-radius: 14px;
}

.preferred-datetime__label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #3A3A3A;
    margin-bottom: 12px;
}

/* ============================================
   SUBMIT BUTTON
   ============================================ */
.submit-btn {
    width: 100%;
    padding: 16px 24px;
    background: linear-gradient(135deg, var(--brand-primary-hover) 0%, var(--brand-primary) 50%, var(--brand-secondary) 100%);
    background-size: 200% 200%;
    color: white;
    font-weight: 700;
    font-size: 1.05rem;
    border-radius: 14px;
    border: none;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    box-shadow: 0 4px 16px rgba(196, 162, 101, 0.25);
    position: relative;
    overflow: hidden;
}

.submit-btn:hover:not(:disabled) {
    background-position: 100% 0;
    box-shadow: 0 8px 28px rgba(196, 162, 101, 0.35);
    transform: translateY(-2px);
}

.submit-btn:active:not(:disabled) {
    transform: translateY(0);
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ============================================
   TRUST BADGES
   ============================================ */
.trust-badges {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 16px;
    margin-top: 20px;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.6s ease 0.2s;
}

.trust-badges--visible {
    opacity: 1;
    transform: translateY(0);
}

.trust-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #6b7280;
}

/* ============================================
   SIDEBAR PANEL
   ============================================ */
.sidebar-panel {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.2s;
}
.sidebar-panel--visible {
    opacity: 1;
    transform: translateY(0);
}

.sidebar-sticky {
    position: sticky;
    top: 24px;
}

/* ============================================
   SUMMARY CARD
   ============================================ */
.summary-card {
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(196, 162, 101, 0.1);
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
}

.summary-card__header {
    background: linear-gradient(135deg, #3A3A3A 0%, #4a4a4a 100%);
    padding: 16px 20px;
    position: relative;
    overflow: hidden;
}

.summary-card__header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--brand-primary), transparent);
}

.summary-card__image {
    position: relative;
    overflow: hidden;
}

.savings-badge-overlay {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 9999px;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
    animation: badgePop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 1s both;
}

[dir="rtl"] .savings-badge-overlay {
    left: auto;
    right: 12px;
}

/* ============================================
   SERVICES CHECKLIST
   ============================================ */
.services-list {
    margin-bottom: 20px;
}

.service-check-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
    opacity: 0;
    transform: translateX(16px);
    animation: serviceSlideIn 0.4s ease forwards;
}

[dir="rtl"] .service-check-item {
    transform: translateX(-16px);
}

.services-list--visible .service-check-item {
    animation: serviceSlideIn 0.4s ease forwards;
}

@keyframes serviceSlideIn {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.service-check-item:last-child {
    border-bottom: none;
}

.service-check-icon {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.service-discount {
    font-size: 0.7rem;
    font-weight: 700;
    color: #16a34a;
    background: #f0fdf4;
    padding: 2px 8px;
    border-radius: 9999px;
}

/* ============================================
   PRICE BREAKDOWN
   ============================================ */
.price-breakdown {
    border-top: 1px solid #f3f4f6;
    padding-top: 16px;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
}

.savings-amount {
    font-size: 0.875rem;
    font-weight: 600;
    color: #16a34a;
    background: #f0fdf4;
    padding: 2px 10px;
    border-radius: 8px;
}

.price-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 2px solid #f3f4f6;
}

.price-total__value {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

/* ============================================
   WHATSAPP CTA
   ============================================ */
.whatsapp-cta {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    margin-top: 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.whatsapp-cta:hover {
    border-color: #25d366;
    box-shadow: 0 4px 16px rgba(37, 211, 102, 0.12);
}

.whatsapp-cta__icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #25d366, #128c7e);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* ============================================
   BACK LINK
   ============================================ */
.back-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
    padding: 12px;
    font-size: 0.875rem;
    color: #6b7280;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.back-link:hover {
    color: var(--brand-primary);
    background: rgba(196, 162, 101, 0.05);
}
</style>
