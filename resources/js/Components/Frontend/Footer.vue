<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { computed } from 'vue';

const { t, locale, isRtl, localizedRoute } = useLocale();
const { phone1, phone2, email, facebook, instagram, tiktok, googleMaps, whatsappLink } = useSettings();
const modules = computed(() => usePage().props.modules || {});

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

// Module icon mapping
const moduleIcons = {
    derma: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12',
    dental: 'M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0',
    pediatric: 'M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z',
};

// Build service links dynamically from enabled modules
const medicalSlugs = ['derma', 'dental', 'pediatric'];
const serviceLinks = computed(() => {
    const links = medicalSlugs
        .filter(slug => modules.value[slug]?.enabled)
        .map(slug => ({
            label: () => isRtl ? modules.value[slug].name_ar : modules.value[slug].name_en,
            route: `/services?module=${slug}`,
            icon: moduleIcons[slug] || '',
        }));
    // Always add booking link
    links.push({
        label: () => isRtl ? 'الحجز الإلكتروني' : 'Online Booking',
        route: '/booking',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2 2v12a2 2 0 002 2z',
    });
    return links;
});

const socialLinks = [
    { name: 'Facebook', href: facebook, icon: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' },
    { name: 'Instagram', href: instagram, icon: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z' },
    { name: 'TikTok', href: tiktok, icon: 'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48V13a8.28 8.28 0 005.58 2.17v-3.45a4.85 4.85 0 01-3.77-1.26V6.69h3.77z' },
];
</script>

<template>
    <footer class="relative overflow-hidden">
        <!-- Navy gradient background -->
        <div class="absolute inset-0 bg-gradient-to-b from-[#0f2847] via-[#142f52] to-[#0d2240]"></div>
        <!-- Dot texture -->
        <div class="absolute inset-0 opacity-[0.025]"
             style="background-image: radial-gradient(circle at 1px 1px, rgba(196,162,101,0.8) 1px, transparent 0); background-size: 32px 32px;"></div>
        <!-- Subtle glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[radial-gradient(ellipse,_rgba(196,162,101,0.04)_0%,_transparent_70%)]"></div>

        <!-- Top gold accent line -->
        <div class="relative h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>

        <!-- ══════════════════════════════════════════ -->
        <!-- CTA STRIP — Book + WhatsApp                -->
        <!-- ══════════════════════════════════════════ -->
        <div class="relative z-10 border-b border-white/[0.05]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-10">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <!-- Logo + tagline -->
                    <div class="flex flex-col items-center md:items-start gap-3">
                        <img src="/images/logo/logo-light.png" alt="Doctorato Polyclinic" class="h-9 md:h-11 w-auto" />
                        <p class="text-white/35 text-xs md:text-sm max-w-sm text-center md:text-start leading-relaxed">
                            {{ t('footer_about_text') }}
                        </p>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <Link :href="localizedRoute('/booking')"
                              class="group relative inline-flex items-center gap-2.5 px-6 py-3 bg-gradient-to-r from-[#C4A265] to-[#d4b275] text-[#1B365D] font-bold text-sm rounded-xl
                                     hover:shadow-lg hover:shadow-[#C4A265]/25 transition-all duration-300 hover:-translate-y-0.5 overflow-hidden">
                            <span class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                            <svg class="w-4 h-4 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="relative">{{ isRtl ? 'احجز موعدك' : 'Book Now' }}</span>
                        </Link>
                        <a :href="whatsappLink" target="_blank" rel="noopener"
                           class="group inline-flex items-center gap-2.5 px-6 py-3 bg-white/[0.05] border border-white/10 text-white font-semibold text-sm rounded-xl
                                  hover:bg-[#25D366]/10 hover:border-[#25D366]/30 transition-all duration-300 hover:-translate-y-0.5">
                            <svg class="w-4 h-4 text-[#25D366] group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            </svg>
                            {{ isRtl ? 'واتساب' : 'WhatsApp' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- MAIN LINKS GRID                            -->
        <!-- ══════════════════════════════════════════ -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
            <!-- Row 1: Quick Links + Specialties (always 2 cols) -->
            <!-- Row 2: Contact + Social (always 2 cols) -->
            <!-- On lg: all 4 in one row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-10 md:gap-x-10 lg:gap-10">

                <!-- Column 1: Quick Links -->
                <div>
                    <h3 class="text-white font-bold text-sm mb-5 flex items-center gap-2.5">
                        <span class="w-6 h-[2px] bg-gradient-to-r from-[#C4A265] to-transparent rounded-full"></span>
                        {{ t('quick_links') }}
                    </h3>
                    <ul class="space-y-2">
                        <li v-for="link in quickLinks" :key="link.route">
                            <Link :href="localizedRoute(link.route)"
                                  class="group flex items-center gap-2 text-white/40 hover:text-[#C4A265] transition-all duration-300 text-[13px] py-0.5">
                                <svg class="w-3 h-3 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 text-[#C4A265]/60 flex-shrink-0"
                                     :class="{ 'rotate-180 translate-x-2 -translate-x-0 group-hover:-translate-x-0 group-hover:translate-x-2': isRtl }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                </svg>
                                <span>{{ link.label() }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Column 2: Medical Specialties -->
                <div>
                    <h3 class="text-white font-bold text-sm mb-5 flex items-center gap-2.5">
                        <span class="w-6 h-[2px] bg-gradient-to-r from-[#C4A265] to-transparent rounded-full"></span>
                        {{ isRtl ? 'تخصصاتنا الطبية' : 'Medical Specialties' }}
                    </h3>
                    <ul class="space-y-2">
                        <li v-for="link in serviceLinks" :key="link.label()">
                            <a :href="localizedRoute(link.route.split('?')[0]) + (link.route.includes('?') ? '?' + link.route.split('?')[1] : '')"
                               class="group flex items-center gap-2.5 text-white/40 hover:text-[#C4A265] transition-all duration-300 text-[13px] py-0.5">
                                <span class="w-7 h-7 rounded-lg bg-white/[0.04] border border-white/[0.06] flex items-center justify-center flex-shrink-0
                                             group-hover:bg-[#C4A265]/10 group-hover:border-[#C4A265]/20 transition-all duration-300">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="link.icon" />
                                    </svg>
                                </span>
                                {{ link.label() }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 3: Contact Info -->
                <div>
                    <h3 class="text-white font-bold text-sm mb-5 flex items-center gap-2.5">
                        <span class="w-6 h-[2px] bg-gradient-to-r from-[#C4A265] to-transparent rounded-full"></span>
                        {{ t('contact_us') }}
                    </h3>
                    <ul class="space-y-3">
                        <!-- Phone 1 -->
                        <li>
                            <a :href="`tel:${phone1}`"
                               class="group flex items-center gap-3 text-white/40 hover:text-white transition-all duration-300 text-[13px]">
                                <span class="w-8 h-8 rounded-lg bg-[#C4A265]/[0.08] border border-[#C4A265]/10 flex items-center justify-center flex-shrink-0
                                             group-hover:bg-[#C4A265]/20 group-hover:border-[#C4A265]/25 transition-all duration-300">
                                    <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                                    </svg>
                                </span>
                                <span dir="ltr" class="font-mono text-xs tracking-wide">{{ phone1 }}</span>
                            </a>
                        </li>
                        <!-- Phone 2 -->
                        <li>
                            <a :href="`tel:${phone2}`"
                               class="group flex items-center gap-3 text-white/40 hover:text-white transition-all duration-300 text-[13px]">
                                <span class="w-8 h-8 rounded-lg bg-[#C4A265]/[0.08] border border-[#C4A265]/10 flex items-center justify-center flex-shrink-0
                                             group-hover:bg-[#C4A265]/20 group-hover:border-[#C4A265]/25 transition-all duration-300">
                                    <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                                    </svg>
                                </span>
                                <span dir="ltr" class="font-mono text-xs tracking-wide">{{ phone2 }}</span>
                            </a>
                        </li>
                        <!-- Email -->
                        <li>
                            <a :href="`mailto:${email}`"
                               class="group flex items-center gap-3 text-white/40 hover:text-white transition-all duration-300 text-[13px]">
                                <span class="w-8 h-8 rounded-lg bg-[#C4A265]/[0.08] border border-[#C4A265]/10 flex items-center justify-center flex-shrink-0
                                             group-hover:bg-[#C4A265]/20 group-hover:border-[#C4A265]/25 transition-all duration-300">
                                    <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                    </svg>
                                </span>
                                <span dir="ltr" class="text-xs">{{ email }}</span>
                            </a>
                        </li>
                        <!-- Address -->
                        <li>
                            <a :href="googleMaps" target="_blank" rel="noopener"
                               class="group flex items-start gap-3 text-white/40 hover:text-white transition-all duration-300 text-[13px]">
                                <span class="w-8 h-8 rounded-lg bg-[#C4A265]/[0.08] border border-[#C4A265]/10 flex items-center justify-center flex-shrink-0
                                             group-hover:bg-[#C4A265]/20 group-hover:border-[#C4A265]/25 transition-all duration-300 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                    </svg>
                                </span>
                                <span class="text-xs leading-relaxed">{{ t('clinic_address') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 4: Social + Working Hours -->
                <div>
                    <h3 class="text-white font-bold text-sm mb-5 flex items-center gap-2.5">
                        <span class="w-6 h-[2px] bg-gradient-to-r from-[#C4A265] to-transparent rounded-full"></span>
                        {{ isRtl ? 'تابعنا' : 'Follow Us' }}
                    </h3>

                    <!-- Social icons row -->
                    <div class="flex gap-2.5 mb-6">
                        <a v-for="s in socialLinks" :key="s.name"
                           :href="s.href" target="_blank" rel="noopener"
                           :title="s.name"
                           class="group w-10 h-10 rounded-xl bg-white/[0.04] border border-white/[0.06] flex items-center justify-center
                                  text-white/40 hover:text-[#C4A265] hover:bg-[#C4A265]/10 hover:border-[#C4A265]/20
                                  hover:-translate-y-1 hover:shadow-md hover:shadow-[#C4A265]/10
                                  transition-all duration-300">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path :d="s.icon"/></svg>
                        </a>
                    </div>

                    <!-- Working Hours Card -->
                    <div class="p-4 rounded-xl bg-gradient-to-br from-white/[0.04] to-white/[0.02] border border-white/[0.06]
                                hover:border-[#C4A265]/15 transition-all duration-500">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
                            </span>
                            <p class="text-white/60 text-xs font-semibold tracking-wider uppercase">
                                {{ isRtl ? 'ساعات العمل' : 'Working Hours' }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-white/30">{{ isRtl ? 'السبت - الخميس' : 'Sat - Thu' }}</span>
                                <span class="text-[#C4A265]/80 font-medium font-mono text-[11px]">10:00 - 22:00</span>
                            </div>
                            <div class="w-full h-px bg-white/[0.04]"></div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-white/30">{{ isRtl ? 'الجمعة' : 'Friday' }}</span>
                                <span class="text-red-400/70 font-medium text-[11px]">{{ isRtl ? 'مغلق' : 'Closed' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════ -->
        <!-- BOTTOM BAR                                 -->
        <!-- ══════════════════════════════════════════ -->
        <div class="relative z-10 border-t border-white/[0.05]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-[11px] md:text-xs text-white/25">
                        &copy; {{ new Date().getFullYear() }} Doctorato Polyclinic. {{ t('all_rights_reserved') }}
                    </p>
                    <div class="flex items-center gap-4 text-[11px] md:text-xs text-white/25">
                        <Link :href="localizedRoute('/page/privacy-policy')" class="hover:text-[#C4A265] transition-colors duration-300">
                            {{ t('privacy_policy') }}
                        </Link>
                        <span class="w-1 h-1 rounded-full bg-white/10"></span>
                        <Link :href="localizedRoute('/page/terms-of-use')" class="hover:text-[#C4A265] transition-colors duration-300">
                            {{ t('terms_conditions') }}
                        </Link>
                    </div>
                </div>

                <!-- Developer credit -->
                <div class="text-center mt-3 pt-3 border-t border-white/[0.03]">
                    <p class="text-[10px] text-white/15">
                        {{ isRtl ? 'تم التطوير بواسطة' : 'Developed by' }}
                        <a href="https://markeza-group.com" target="_blank" rel="noopener noreferrer"
                           class="text-[#C4A265]/40 hover:text-[#C4A265]/70 font-semibold transition-colors duration-300 inline-flex items-center gap-0.5">
                            Markeza Group
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
</template>
