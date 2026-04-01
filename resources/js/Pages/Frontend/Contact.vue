<script setup>
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { toEnglishNumbers } from '@/Composables/useArabicNumbers';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { phone1, phone2, email, whatsapp, whatsappLink, facebook, instagram, tiktok, googleMaps } = useSettings();
const page = usePage();

const props = defineProps({
    settings: Object,
    seo: Object,
});

const flash = computed(() => page.props.flash || {});
const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

// Country codes
const countryCodes = [
    { code: '+20', flag: '🇪🇬', name: 'Egypt', nameAr: 'مصر' },
    { code: '+966', flag: '🇸🇦', name: 'Saudi Arabia', nameAr: 'السعودية' },
    { code: '+971', flag: '🇦🇪', name: 'UAE', nameAr: 'الإمارات' },
    { code: '+965', flag: '🇰🇼', name: 'Kuwait', nameAr: 'الكويت' },
    { code: '+974', flag: '🇶🇦', name: 'Qatar', nameAr: 'قطر' },
    { code: '+973', flag: '🇧🇭', name: 'Bahrain', nameAr: 'البحرين' },
    { code: '+968', flag: '🇴🇲', name: 'Oman', nameAr: 'عُمان' },
    { code: '+962', flag: '🇯🇴', name: 'Jordan', nameAr: 'الأردن' },
    { code: '+961', flag: '🇱🇧', name: 'Lebanon', nameAr: 'لبنان' },
    { code: '+964', flag: '🇮🇶', name: 'Iraq', nameAr: 'العراق' },
    { code: '+218', flag: '🇱🇾', name: 'Libya', nameAr: 'ليبيا' },
    { code: '+216', flag: '🇹🇳', name: 'Tunisia', nameAr: 'تونس' },
    { code: '+213', flag: '🇩🇿', name: 'Algeria', nameAr: 'الجزائر' },
    { code: '+212', flag: '🇲🇦', name: 'Morocco', nameAr: 'المغرب' },
    { code: '+249', flag: '🇸🇩', name: 'Sudan', nameAr: 'السودان' },
    { code: '+967', flag: '🇾🇪', name: 'Yemen', nameAr: 'اليمن' },
    { code: '+1', flag: '🇺🇸', name: 'USA', nameAr: 'أمريكا' },
    { code: '+44', flag: '🇬🇧', name: 'UK', nameAr: 'بريطانيا' },
    { code: '+49', flag: '🇩🇪', name: 'Germany', nameAr: 'ألمانيا' },
    { code: '+33', flag: '🇫🇷', name: 'France', nameAr: 'فرنسا' },
    { code: '+39', flag: '🇮🇹', name: 'Italy', nameAr: 'إيطاليا' },
    { code: '+90', flag: '🇹🇷', name: 'Turkey', nameAr: 'تركيا' },
];

const selectedCountryCode = ref('+20');
const phoneNumber = ref('');
const showCountryDropdown = ref(false);
const countrySearch = ref('');
const emailError = ref('');

const filteredCountries = computed(() => {
    if (!countrySearch.value) return countryCodes;
    const q = countrySearch.value.toLowerCase();
    return countryCodes.filter(c =>
        c.name.toLowerCase().includes(q) || c.nameAr.includes(q) || c.code.includes(q)
    );
});

const selectedCountry = computed(() => countryCodes.find(c => c.code === selectedCountryCode.value) || countryCodes[0]);

function selectCountry(c) {
    selectedCountryCode.value = c.code;
    showCountryDropdown.value = false;
    countrySearch.value = '';
    syncPhone();
}

function syncPhone() {
    phoneNumber.value = toEnglishNumbers(phoneNumber.value).replace(/[^\d]/g, '');
    form.phone = selectedCountryCode.value + phoneNumber.value;
}

function handleCountryClickOutside(e) {
    if (!e.target.closest('.loc-country-dd')) {
        showCountryDropdown.value = false;
        countrySearch.value = '';
    }
}

function validateEmail() {
    if (!form.email) { emailError.value = ''; return; }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    emailError.value = emailRegex.test(form.email)
        ? ''
        : (locale.value === 'ar' ? 'يرجى إدخال بريد إلكتروني صحيح' : 'Please enter a valid email address');
}

onMounted(() => document.addEventListener('click', handleCountryClickOutside));
onUnmounted(() => document.removeEventListener('click', handleCountryClickOutside));

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

function normalizePhone() {
    syncPhone();
}

function submit() {
    validateEmail();
    if (emailError.value) return;

    form.post(localizedRoute('/contact'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            phoneNumber.value = '';
            selectedCountryCode.value = '+20';
            emailError.value = '';
        },
    });
}
</script>

<template>
    <FrontendLayout :title="t('contact_us')">
        <SeoHead
            :title="seoTitle"
            :description="seoDescription"
            :keywords="seo?.keywords"
            :image="seo?.image"
        />

        <!-- Page Hero -->
        <section class="relative bg-gradient-to-br from-[#3A3A3A] via-[#3a3a3a] to-[#3A3A3A] py-20 lg:py-28 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-diagonal"></div>
            <!-- Decorative elements -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 left-10 w-72 h-72 bg-[var(--brand-primary)] rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-96 h-96 bg-[var(--brand-primary)] rounded-full blur-3xl"></div>
            </div>
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[var(--brand-primary)] to-transparent"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-10 start-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-14 end-14 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/2 end-1/3 w-10 h-10 rounded-full bg-gold-light/5 animate-float-delay"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div v-scroll-reveal="{ type: 'blur-in' }">
                    <span class="inline-block px-4 py-1.5 bg-[var(--brand-primary)]/20 text-[var(--brand-primary)] text-sm font-medium rounded-full mb-6 tracking-wide">
                        {{ t('contact') }}
                    </span>
                </div>
                <h1
                    v-scroll-reveal="{ type: 'fade-up', delay: 100 }"
                    class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4"
                >
                    {{ t('contact_us') }}
                </h1>
                <p
                    v-scroll-reveal="{ type: 'fade-up', delay: 200 }"
                    class="text-lg text-gray-300 max-w-2xl mx-auto"
                >
                    {{ t('contact_subtitle') }}
                </p>
            </div>
        </section>

        <!-- Contact Info Cards -->
        <section class="relative py-16 lg:py-20 bg-[#FDF8F0] overflow-hidden">
            <!-- Subtle cross-hatch pattern -->
            <div class="absolute inset-0 pointer-events-none texture-crosshatch"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-12 start-8 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-16 end-12 w-16 h-16 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">

                    <!-- Phone Card -->
                    <div
                        class="bg-white rounded-2xl p-6 shadow-md border border-gray-100 hover:border-b-[var(--brand-primary)] hover:border-b-4 transition-all duration-300 group card-hover-lift card-premium"
                    >
                        <div class="w-14 h-14 bg-[var(--brand-primary)]/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[var(--brand-primary)] transition-colors duration-300">
                            <svg class="w-6 h-6 text-[var(--brand-primary)] group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-[#3A3A3A] mb-3">{{ t('phone') }}</h3>
                        <a :href="`tel:${phone1}`" class="block text-gray-600 hover:text-[var(--brand-primary)] transition-colors font-medium" dir="ltr">
                            {{ phone1 }}
                        </a>
                        <a :href="`tel:${phone2}`" class="block text-gray-600 hover:text-[var(--brand-primary)] transition-colors font-medium mt-1" dir="ltr">
                            {{ phone2 }}
                        </a>
                    </div>

                    <!-- WhatsApp Card -->
                    <div
                        class="bg-white rounded-2xl p-6 shadow-md border border-gray-100 hover:border-b-[var(--brand-primary)] hover:border-b-4 transition-all duration-300 group card-hover-lift card-premium"
                    >
                        <div class="w-14 h-14 bg-[var(--brand-primary)]/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[var(--brand-primary)] transition-colors duration-300">
                            <svg class="w-6 h-6 text-[var(--brand-primary)] group-hover:text-white transition-colors duration-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-[#3A3A3A] mb-3">{{ t('whatsapp') }}</h3>
                        <a :href="whatsappLink" target="_blank" rel="noopener" class="text-gray-600 hover:text-[var(--brand-primary)] transition-colors font-medium" dir="ltr">
                            {{ whatsapp }}
                        </a>
                    </div>

                    <!-- Email Card -->
                    <div
                        class="bg-white rounded-2xl p-6 shadow-md border border-gray-100 hover:border-b-[var(--brand-primary)] hover:border-b-4 transition-all duration-300 group card-hover-lift card-premium"
                    >
                        <div class="w-14 h-14 bg-[var(--brand-primary)]/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[var(--brand-primary)] transition-colors duration-300">
                            <svg class="w-6 h-6 text-[var(--brand-primary)] group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-[#3A3A3A] mb-3">{{ t('email') }}</h3>
                        <a :href="`mailto:${email}`" class="text-gray-600 hover:text-[var(--brand-primary)] transition-colors font-medium break-all">
                            {{ email }}
                        </a>
                    </div>

                    <!-- Address + Hours Card -->
                    <div
                        class="bg-white rounded-2xl p-6 shadow-md border border-gray-100 hover:border-b-[var(--brand-primary)] hover:border-b-4 transition-all duration-300 group card-hover-lift card-premium"
                    >
                        <div class="w-14 h-14 bg-[var(--brand-primary)]/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[var(--brand-primary)] transition-colors duration-300">
                            <svg class="w-6 h-6 text-[var(--brand-primary)] group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-[#3A3A3A] mb-3">{{ t('address') }}</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            {{ t('clinic_address') }}
                        </p>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <svg class="w-4 h-4 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ t('working_hours_weekdays') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Form + Map Section -->
        <section class="relative py-16 lg:py-24 bg-white overflow-hidden">
            <!-- Subtle diagonal line pattern -->
            <div class="absolute inset-0 pointer-events-none texture-diagonal-reverse"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-20 start-6 w-24 h-24 rounded-full bg-gold-primary/5 animate-float-slow"></div>
            <div class="absolute bottom-20 end-8 w-16 h-16 rounded-full bg-gold-primary/8 animate-float"></div>
            <div class="absolute top-1/3 end-1/4 w-10 h-10 rounded-full bg-gold-light/5 animate-float-delay"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Section Header -->
                <div class="text-center mb-12" v-scroll-reveal="{ type: 'blur-in' }">
                    <h2 class="text-3xl lg:text-4xl font-bold text-[#3A3A3A] mb-4">
                        {{ t('send_us_message') }}
                    </h2>
                    <p class="text-gray-500 max-w-2xl mx-auto">
                        {{ t('contact_form_description') }}
                    </p>
                </div>

                <!-- Success Flash Message -->
                <Transition
                    enter-active-class="transition-all duration-500 ease-out"
                    enter-from-class="opacity-0 -translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-300 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-4"
                >
                    <div
                        v-if="flash.success"
                        class="mb-8 max-w-3xl mx-auto p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3"
                    >
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-green-800 font-medium">{{ flash.success }}</p>
                    </div>
                </Transition>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

                    <!-- Contact Form -->
                    <div v-scroll-reveal="{ type: 'fade-right' }">
                        <div class="bg-[#FDF8F0] rounded-2xl p-6 sm:p-8 lg:p-10 border border-[var(--brand-primary)]/10">
                            <form @submit.prevent="submit" class="space-y-6">

                                <!-- Name -->
                                <div>
                                    <label for="contact_name" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                        {{ t('full_name') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="contact_name"
                                        v-model="form.name"
                                        type="text"
                                        :placeholder="t('full_name_placeholder')"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none form-input-animated"
                                        :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.name }"
                                        required
                                    />
                                    <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-500">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Row: Email + Phone -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Email -->
                                    <div>
                                        <label for="contact_email" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                            {{ t('email') }} <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="contact_email"
                                            v-model="form.email"
                                            @blur="validateEmail"
                                            type="email"
                                            :placeholder="t('email_placeholder')"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none form-input-animated"
                                            :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.email || emailError }"
                                            required
                                        />
                                        <p v-if="emailError" class="mt-1.5 text-sm text-red-500">
                                            {{ emailError }}
                                        </p>
                                        <p v-else-if="form.errors.email" class="mt-1.5 text-sm text-red-500">
                                            {{ form.errors.email }}
                                        </p>
                                    </div>

                                    <!-- Phone with Country Code -->
                                    <div>
                                        <label for="contact_phone" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                            {{ t('phone_number') }}
                                        </label>
                                        <div class="relative flex loc-country-dd" dir="ltr">
                                            <!-- Country Code Selector -->
                                            <button
                                                type="button"
                                                @click="showCountryDropdown = !showCountryDropdown"
                                                class="flex items-center gap-1.5 px-3 py-3 border border-gray-300 rounded-s-lg bg-gray-50 hover:bg-gray-100 transition-colors flex-shrink-0 border-e-0"
                                                :class="{ 'border-red-400': form.errors.phone }"
                                            >
                                                <span class="text-lg leading-none">{{ selectedCountry.flag }}</span>
                                                <span class="text-sm text-gray-700 font-medium">{{ selectedCountry.code }}</span>
                                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="{ 'rotate-180': showCountryDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            <!-- Phone Input -->
                                            <input
                                                id="contact_phone"
                                                v-model="phoneNumber"
                                                @input="normalizePhone"
                                                type="tel"
                                                :placeholder="t('phone_placeholder')"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-e-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none form-input-animated"
                                                :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.phone }"
                                            />

                                            <!-- Country Dropdown -->
                                            <Transition
                                                enter-active-class="transition duration-150 ease-out"
                                                enter-from-class="opacity-0 -translate-y-1"
                                                enter-to-class="opacity-100 translate-y-0"
                                                leave-active-class="transition duration-100 ease-in"
                                                leave-from-class="opacity-100 translate-y-0"
                                                leave-to-class="opacity-0 -translate-y-1"
                                            >
                                                <div v-if="showCountryDropdown" class="absolute top-full start-0 mt-1 w-72 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden">
                                                    <div class="p-2 border-b border-gray-100">
                                                        <input
                                                            v-model="countrySearch"
                                                            type="text"
                                                            :placeholder="locale === 'ar' ? 'ابحث عن دولة...' : 'Search country...'"
                                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/30"
                                                        />
                                                    </div>
                                                    <div class="max-h-52 overflow-y-auto">
                                                        <button
                                                            v-for="c in filteredCountries"
                                                            :key="c.code"
                                                            type="button"
                                                            @click="selectCountry(c)"
                                                            class="w-full flex items-center gap-3 px-3 py-2.5 text-sm hover:bg-gray-50 transition-colors"
                                                            :class="{ 'bg-[var(--brand-primary)]/5 font-medium': c.code === selectedCountryCode }"
                                                        >
                                                            <span class="text-lg leading-none">{{ c.flag }}</span>
                                                            <span class="text-gray-800">{{ locale === 'ar' ? c.nameAr : c.name }}</span>
                                                            <span class="ms-auto text-gray-400 text-xs font-mono">{{ c.code }}</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>
                                        <p v-if="form.errors.phone" class="mt-1.5 text-sm text-red-500">
                                            {{ form.errors.phone }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Subject -->
                                <div>
                                    <label for="contact_subject" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                        {{ t('subject') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="contact_subject"
                                        v-model="form.subject"
                                        type="text"
                                        :placeholder="t('subject_placeholder')"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none form-input-animated"
                                        :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.subject }"
                                        required
                                    />
                                    <p v-if="form.errors.subject" class="mt-1.5 text-sm text-red-500">
                                        {{ form.errors.subject }}
                                    </p>
                                </div>

                                <!-- Message -->
                                <div>
                                    <label for="contact_message" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                        {{ t('message') }} <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="contact_message"
                                        v-model="form.message"
                                        rows="5"
                                        :placeholder="t('message_placeholder')"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none resize-none form-input-animated"
                                        :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.message }"
                                        required
                                    ></textarea>
                                    <p v-if="form.errors.message" class="mt-1.5 text-sm text-red-500">
                                        {{ form.errors.message }}
                                    </p>
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full py-4 bg-[var(--brand-primary)] text-white font-bold text-lg rounded-xl hover:bg-[var(--brand-primary-hover)] focus:ring-4 focus:ring-[var(--brand-primary)]/30 transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-[var(--brand-primary)]/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 btn-shimmer"
                                >
                                    <svg v-if="form.processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <svg v-if="!form.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    <span v-if="!form.processing">{{ t('send_message') }}</span>
                                    <span v-else>{{ t('sending') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Map -->
                    <div v-scroll-reveal="{ type: 'fade-left', delay: 150 }">
                        <div class="bg-[#FDF8F0] rounded-2xl overflow-hidden border border-[var(--brand-primary)]/10 h-full min-h-[400px] flex flex-col">
                            <!-- Map Embed -->
                            <div class="flex-1 relative">
                                <iframe
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3456.789!2d30.9!3d29.97!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sCairo+Medical+Center!5e0!3m2!1sar!2seg!4v1234567890"
                                    class="w-full h-full min-h-[350px] border-0"
                                    allowfullscreen=""
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                ></iframe>
                            </div>

                            <!-- Map Footer -->
                            <div class="p-4 bg-white border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="text-sm font-medium text-[#3A3A3A]">{{ t('clinic_name') }}</span>
                                    </div>
                                    <a
                                        :href="googleMaps"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1.5 text-sm text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] font-medium transition-colors"
                                    >
                                        {{ t('open_in_maps') }}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Social Media Section -->
        <section class="relative py-16 bg-[#F5EDE0] overflow-hidden">
            <!-- Subtle dot pattern -->
            <div class="absolute inset-0 pointer-events-none texture-dots"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-8 start-12 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-8 end-10 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/2 start-1/4 w-12 h-12 rounded-full bg-gold-light/5 animate-float-delay"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center" v-scroll-reveal="{ type: 'blur-in' }">
                    <h2 class="text-2xl lg:text-3xl font-bold text-[#3A3A3A] mb-3">
                        {{ t('follow_us') }}
                    </h2>
                    <p class="text-gray-500 mb-8 max-w-lg mx-auto">
                        {{ t('follow_us_description') }}
                    </p>

                    <div class="flex items-center justify-center gap-6" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                        <!-- Facebook -->
                        <a
                            :href="facebook"
                            target="_blank"
                            rel="noopener"
                            :aria-label="t('facebook')"
                            class="w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-md border border-gray-100 text-gray-600 hover:bg-[var(--brand-primary)] hover:text-white hover:border-[var(--brand-primary)] hover:shadow-lg hover:shadow-[var(--brand-primary)]/20 transition-all duration-300 group"
                        >
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </a>

                        <!-- Instagram -->
                        <a
                            :href="instagram"
                            target="_blank"
                            rel="noopener"
                            :aria-label="t('instagram')"
                            class="w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-md border border-gray-100 text-gray-600 hover:bg-[var(--brand-primary)] hover:text-white hover:border-[var(--brand-primary)] hover:shadow-lg hover:shadow-[var(--brand-primary)]/20 transition-all duration-300 group"
                        >
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                            </svg>
                        </a>

                        <!-- TikTok -->
                        <a
                            :href="tiktok"
                            target="_blank"
                            rel="noopener"
                            :aria-label="t('tiktok')"
                            class="w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-md border border-gray-100 text-gray-600 hover:bg-[var(--brand-primary)] hover:text-white hover:border-[var(--brand-primary)] hover:shadow-lg hover:shadow-[var(--brand-primary)]/20 transition-all duration-300 group"
                        >
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48V13a8.28 8.28 0 005.58 2.17v-3.45a4.85 4.85 0 01-3.77-1.26V6.69h3.77z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </FrontendLayout>
</template>
