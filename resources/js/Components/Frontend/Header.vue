<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import LanguageSwitcher from './LanguageSwitcher.vue';

const { t, locale, isRtl, localizedRoute } = useLocale();
const { phone1, phone2, email, facebook, instagram, tiktok } = useSettings();

const isScrolled = ref(false);
const mobileMenuOpen = ref(false);

const navItems = computed(() => [
    { label: t('home'), route: localizedRoute('/') },
    { label: t('about'), route: localizedRoute('/about') },
    { label: t('services'), route: localizedRoute('/services') },
    { label: t('doctors'), route: localizedRoute('/doctors') },
    { label: t('gallery'), route: localizedRoute('/gallery') },
    { label: t('packages'), route: localizedRoute('/package-bundles') },
    { label: t('blog'), route: localizedRoute('/blog') },
    { label: t('faq'), route: localizedRoute('/faq') },
    { label: t('contact'), route: localizedRoute('/contact') },
]);

function handleScroll() {
    isScrolled.value = window.scrollY > 20;
}

function toggleMobileMenu() {
    mobileMenuOpen.value = !mobileMenuOpen.value;
    if (mobileMenuOpen.value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

function closeMobileMenu() {
    mobileMenuOpen.value = false;
    document.body.style.overflow = '';
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    document.body.style.overflow = '';
});
</script>

<template>
    <header
        class="fixed top-0 left-0 right-0 z-40 transition-all duration-300 bg-white"
        :class="isScrolled ? 'shadow-lg' : 'shadow-sm'"
    >
        <!-- Top Bar -->
        <div
            class="hidden lg:block transition-all duration-300 border-b border-white/10 bg-[#1B365D]"
            :class="isScrolled ? 'py-1' : 'py-2'"
        >
            <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between text-sm">
                <!-- Contact Info -->
                <div class="flex items-center gap-6 text-gray-300">
                    <a :href="`tel:${phone1}`" class="flex items-center gap-1.5 hover:text-[var(--brand-primary)] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>{{ phone1 }}</span>
                    </a>
                    <a :href="`tel:${phone2}`" class="flex items-center gap-1.5 hover:text-[var(--brand-primary)] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>{{ phone2 }}</span>
                    </a>
                    <a :href="`mailto:${email}`" class="flex items-center gap-1.5 hover:text-[var(--brand-primary)] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>{{ email }}</span>
                    </a>
                </div>

                <!-- Social + Language -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3 text-gray-300">
                        <a :href="facebook" target="_blank" rel="noopener" class="hover:text-[var(--brand-primary)] transition-colors" :aria-label="t('facebook')">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a :href="instagram" target="_blank" rel="noopener" class="hover:text-[var(--brand-primary)] transition-colors" :aria-label="t('instagram')">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        <a :href="tiktok" target="_blank" rel="noopener" class="hover:text-[var(--brand-primary)] transition-colors" :aria-label="t('tiktok')">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48V13a8.28 8.28 0 005.58 2.17v-3.45a4.85 4.85 0 01-3.77-1.26V6.69h3.77z"/></svg>
                        </a>
                    </div>
                    <div class="w-px h-4 bg-gray-600"></div>
                    <LanguageSwitcher />
                </div>
            </div>
        </div>

        <!-- Main Navigation -->
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <!-- Logo -->
                <Link :href="localizedRoute('/')" class="flex-shrink-0 group">
                    <img
                        src="/images/logo/logo.png"
                        alt="Doctorato Polyclinic"
                        class="h-7 lg:h-8 w-auto transition-transform duration-300 group-hover:scale-105"
                    />
                </Link>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="item.route"
                        class="hover-underline px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 text-gray-700 hover:text-[var(--brand-primary)] hover:bg-[var(--brand-primary)]/5"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <!-- Patient Portal + Book Now CTA + Mobile Menu -->
                <div class="flex items-center gap-3">
                    <a
                        :href="`/${locale}/patient/login`"
                        class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2.5 border-2 border-[#C4A265] text-[#1B365D] text-sm font-semibold rounded-full transition-all duration-300 hover:bg-[#C4A265] hover:text-white hover:shadow-md hover:shadow-[#C4A265]/30"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ t('patient_portal') }}
                    </a>
                    <Link
                        :href="localizedRoute('/booking')"
                        class="hidden sm:inline-flex items-center px-5 py-2.5 bg-[#1B365D] text-white text-sm font-semibold rounded-full hover:bg-[#264573] transition-all duration-300 shadow-md hover:shadow-lg hover:shadow-[#1B365D]/30"
                    >
                        {{ t('book_now') }}
                    </Link>

                    <!-- Mobile Menu Button -->
                    <button
                        @click="toggleMobileMenu"
                        class="lg:hidden p-2 rounded-md text-gray-700 hover:text-[var(--brand-primary)] hover:bg-gray-100 transition-colors relative z-50"
                        :aria-label="t('menu')"
                    >
                        <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

    </header>

    <!-- Mobile Menu (Teleported to body to avoid stacking context issues) -->
    <Teleport to="body">
        <!-- Mobile Menu Overlay -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileMenuOpen" class="fixed inset-0 bg-black/50 z-[9998] lg:hidden" @click="closeMobileMenu"></div>
        </Transition>

        <!-- Mobile Menu Panel -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            :enter-from-class="isRtl ? '-translate-x-full' : 'translate-x-full'"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-300 ease-in"
            leave-from-class="translate-x-0"
            :leave-to-class="isRtl ? '-translate-x-full' : 'translate-x-full'"
        >
            <div
                v-if="mobileMenuOpen"
                class="fixed top-0 bottom-0 w-80 max-w-[85vw] bg-white z-[9999] shadow-2xl lg:hidden overflow-y-auto"
                :class="isRtl ? 'left-0' : 'right-0'"
            >
                <!-- Mobile Menu Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-100">
                    <img
                        src="/images/logo/logo.png"
                        alt="Doctorato Polyclinic"
                        class="h-7 w-auto"
                    />
                    <button @click="closeMobileMenu" class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Nav Links -->
                <nav class="p-4 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="item.route"
                        @click="closeMobileMenu"
                        class="block px-4 py-3 text-gray-700 hover:text-[var(--brand-primary)] hover:bg-[var(--brand-primary)]/5 rounded-lg transition-colors font-medium"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <!-- Mobile Book Now + Patient Portal -->
                <div class="p-4 border-t border-gray-100 space-y-3">
                    <Link
                        :href="localizedRoute('/booking')"
                        @click="closeMobileMenu"
                        class="block w-full text-center px-5 py-3 bg-[#1B365D] text-white font-semibold rounded-full hover:bg-[#264573] transition-colors shadow-md"
                    >
                        {{ t('book_now') }}
                    </Link>
                    <a
                        :href="`/${locale}/patient/login`"
                        @click="closeMobileMenu"
                        class="flex items-center justify-center gap-2 w-full px-5 py-3 border-2 border-[#C4A265] text-[#1B365D] font-semibold rounded-full hover:bg-[#C4A265] hover:text-white transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ t('patient_portal') }}
                    </a>
                </div>

                <!-- Mobile Contact Info -->
                <div class="p-4 space-y-3 text-sm text-gray-500">
                    <a :href="`tel:${phone1}`" class="flex items-center gap-2 hover:text-[var(--brand-primary)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ phone1 }}
                    </a>
                    <a :href="`mailto:${email}`" class="flex items-center gap-2 hover:text-[var(--brand-primary)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ email }}
                    </a>
                </div>

                <!-- Mobile Language Switcher -->
                <div class="p-4 border-t border-gray-100">
                    <LanguageSwitcher />
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Spacer for fixed header -->
    <div class="h-16 lg:h-[calc(theme(spacing.20)+2.5rem)]"></div>
</template>
