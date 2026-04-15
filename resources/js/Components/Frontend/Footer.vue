<script setup>
import { Link } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';

const { t, locale, isRtl, localizedRoute } = useLocale();
const { phone1, phone2, email, facebook, instagram, tiktok, googleMaps } = useSettings();

const quickLinks = [
    { label: () => t('home'), route: '/' },
    { label: () => t('about'), route: '/about' },
    { label: () => t('services'), route: '/services' },
    { label: () => t('gallery'), route: '/gallery' },
    { label: () => t('packages'), route: '/package-bundles' },
    { label: () => t('blog'), route: '/blog' },
    { label: () => t('faq'), route: '/faq' },
    { label: () => t('contact'), route: '/contact' },
];

const serviceLinks = [
    { label: () => isRtl ? 'الجلدية والتجميل' : 'Dermatology', route: '/services' },
    { label: () => isRtl ? 'طب الأسنان' : 'Dentistry', route: '/services' },
    { label: () => isRtl ? 'طب الأطفال' : 'Pediatrics', route: '/services' },
    { label: () => isRtl ? 'علاجات الليزر' : 'Laser Treatments', route: '/services' },
    { label: () => isRtl ? 'العناية بالبشرة' : 'Skincare', route: '/services' },
    { label: () => isRtl ? 'الحجز الإلكتروني' : 'Online Booking', route: '/booking' },
];
</script>

<template>
    <footer class="brand-footer-bg text-gray-300 relative">
        <!-- Gold Accent Line -->
        <div class="h-1 bg-gradient-to-r from-[#C4A265]/0 via-[var(--brand-primary)] to-[#C4A265]/0"></div>

        <!-- Main Footer -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
                <!-- About Column -->
                <div class="lg:col-span-1">
                    <div class="mb-4">
                        <img
                            src="/images/logo/logo-light.png"
                            alt="Doctorato Polyclinic"
                            class="h-10 w-auto"
                        />
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed mb-6">
                        {{ t('footer_about_text') }}
                    </p>
                    <!-- Social Media -->
                    <div class="flex items-center gap-3">
                        <a
                            :href="facebook"
                            target="_blank"
                            rel="noopener"
                            class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:bg-[var(--brand-primary)] hover:text-white hover:scale-110 transition-all duration-300"
                            :aria-label="t('facebook')"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a
                            :href="instagram"
                            target="_blank"
                            rel="noopener"
                            class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:bg-[var(--brand-primary)] hover:text-white hover:scale-110 transition-all duration-300"
                            :aria-label="t('instagram')"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        <a
                            :href="tiktok"
                            target="_blank"
                            rel="noopener"
                            class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:bg-[var(--brand-primary)] hover:text-white hover:scale-110 transition-all duration-300"
                            :aria-label="t('tiktok')"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48V13a8.28 8.28 0 005.58 2.17v-3.45a4.85 4.85 0 01-3.77-1.26V6.69h3.77z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links Column -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4 relative">
                        {{ t('quick_links') }}
                        <span class="absolute bottom-0 start-0 w-8 h-0.5 bg-[var(--brand-primary)] -mb-2"></span>
                    </h3>
                    <ul class="space-y-2 mt-6">
                        <li v-for="link in quickLinks" :key="link.route">
                            <Link
                                :href="localizedRoute(link.route)"
                                class="hover-underline text-gray-400 hover:text-[var(--brand-primary)] transition-colors duration-200 text-sm flex items-center gap-2"
                            >
                                <svg class="w-3 h-3 text-[var(--brand-primary)]/50" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                {{ link.label() }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Services Column -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4 relative">
                        {{ t('our_services') }}
                        <span class="absolute bottom-0 start-0 w-8 h-0.5 bg-[var(--brand-primary)] -mb-2"></span>
                    </h3>
                    <ul class="space-y-2 mt-6">
                        <li v-for="link in serviceLinks" :key="link.label()">
                            <Link
                                :href="localizedRoute(link.route)"
                                class="hover-underline text-gray-400 hover:text-[var(--brand-primary)] transition-colors duration-200 text-sm flex items-center gap-2"
                            >
                                <svg class="w-3 h-3 text-[var(--brand-primary)]/50" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                {{ link.label() }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info Column -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4 relative">
                        {{ t('contact_us') }}
                        <span class="absolute bottom-0 start-0 w-8 h-0.5 bg-[var(--brand-primary)] -mb-2"></span>
                    </h3>
                    <ul class="space-y-4 mt-6">
                        <li>
                            <a :href="`tel:${phone1}`" class="text-gray-400 hover:text-[var(--brand-primary)] transition-colors text-sm flex items-start gap-3">
                                <svg class="w-5 h-5 text-[var(--brand-primary)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>
                                    <span class="block">{{ phone1 }}</span>
                                    <span class="block mt-1">{{ phone2 }}</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a :href="`mailto:${email}`" class="text-gray-400 hover:text-[var(--brand-primary)] transition-colors text-sm flex items-center gap-3">
                                <svg class="w-5 h-5 text-[var(--brand-primary)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ email }}
                            </a>
                        </li>
                        <li>
                            <a :href="googleMaps" target="_blank" rel="noopener" class="text-gray-400 hover:text-[var(--brand-primary)] transition-colors text-sm flex items-start gap-3">
                                <svg class="w-5 h-5 text-[var(--brand-primary)] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ t('clinic_address') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-gray-500">
                    <p>&copy; {{ new Date().getFullYear() }} Doctorato Polyclinic. {{ t('all_rights_reserved') }}</p>
                    <div class="flex items-center gap-4">
                        <Link :href="localizedRoute('/page/privacy-policy')" class="hover-underline hover:text-[var(--brand-primary)] transition-colors">
                            {{ t('privacy_policy') }}
                        </Link>
                        <Link :href="localizedRoute('/page/terms-of-use')" class="hover-underline hover:text-[var(--brand-primary)] transition-colors">
                            {{ t('terms_conditions') }}
                        </Link>
                    </div>
                </div>
                <!-- Developer Credit -->
                <div class="text-center mt-3 pt-3 border-t border-white/5">
                    <p class="text-xs text-gray-500">
                        {{ isRtl ? 'تم التطوير بواسطة' : 'Developed by' }}
                        <a href="https://markeza-group.com"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="text-[var(--brand-primary)] hover:text-[var(--brand-secondary)] font-semibold transition-colors duration-200 inline-flex items-center gap-1">
                            Markeza Group
                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
</template>
