<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { computed } from 'vue';

const { t, isRtl, localizedRoute } = useLocale();
const { phone1, phone2, email, facebook, instagram, tiktok, googleMaps, whatsappLink } = useSettings();
const modules = computed(() => usePage().props.modules || {});

const quickLinks = [
    { label: () => t('home'), route: '/' },
    { label: () => t('about'), route: '/about' },
    { label: () => t('services'), route: '/services' },
    { label: () => t('packages'), route: '/package-bundles' },
    { label: () => t('blog'), route: '/blog' },
    { label: () => t('contact'), route: '/contact' },
];

const medicalSlugs = ['derma', 'dental', 'pediatric'];
const serviceLinks = computed(() => {
    const links = medicalSlugs
        .filter(slug => modules.value[slug]?.enabled)
        .map(slug => ({
            label: () => isRtl ? modules.value[slug].name_ar : modules.value[slug].name_en,
            route: `/services?module=${slug}`,
        }));
    links.push({
        label: () => isRtl ? 'الحجز الإلكتروني' : 'Online Booking',
        route: '/booking',
    });
    return links;
});

const socialLinks = computed(() => [
    { name: 'Facebook', href: facebook.value, icon: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' },
    { name: 'Instagram', href: instagram.value, icon: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z' },
    { name: 'TikTok', href: tiktok.value, icon: 'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48V13a8.28 8.28 0 005.58 2.17v-3.45a4.85 4.85 0 01-3.77-1.26V6.69h3.77z' },
    { name: 'WhatsApp', href: whatsappLink.value, icon: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.004 2C6.476 2 2 6.476 2 12.004c0 1.77.463 3.497 1.34 5.02L2 22l5.108-1.318a9.953 9.953 0 004.896 1.277h.004c5.524 0 10-4.476 10-10.004 0-2.671-1.04-5.183-2.929-7.071A9.928 9.928 0 0012.004 2' },
]);
</script>

<template>
    <footer class="footer-pro relative overflow-hidden bg-[#0d2240]">
        <!-- Texture layers -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#0a1e3d] via-[#142f52] to-[#0d2240]"></div>

        <!-- Animated SVG medical-cross + grid pattern (drifts slowly) -->
        <div class="footer-pattern absolute inset-0 opacity-[0.06] pointer-events-none"></div>

        <!-- Diagonal lines that scroll -->
        <div class="footer-stripes absolute inset-0 opacity-[0.035] pointer-events-none"></div>

        <!-- Floating animated gold orbs (movement) -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <span class="f-orb f-orb-1"></span>
            <span class="f-orb f-orb-2"></span>
            <span class="f-orb f-orb-3"></span>
        </div>

        <!-- Animated shimmer line across the top -->
        <div class="relative h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent overflow-hidden">
            <span class="footer-shimmer absolute inset-y-0 w-1/3 bg-gradient-to-r from-transparent via-white/60 to-transparent"></span>
        </div>

        <!-- Soft radial glow (top-center) -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[260px] opacity-70
                    bg-[radial-gradient(ellipse_at_top,_rgba(196,162,101,0.09)_0%,_transparent_70%)]
                    pointer-events-none"></div>

        <!-- ══ Main content ══ -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-5">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-x-6 gap-y-8">

                <!-- Brand block -->
                <div class="col-span-2 md:col-span-4 lg:col-span-4">
                    <img src="/images/logo/logo-light.png" alt="Doctorato Polyclinic" class="h-9 w-auto mb-3" />
                    <p class="text-white/45 text-[13px] leading-relaxed max-w-sm mb-4">
                        {{ t('footer_about_text') }}
                    </p>

                    <!-- Social icons -->
                    <div class="flex gap-2">
                        <a v-for="s in socialLinks" :key="s.name"
                           :href="s.href" target="_blank" rel="noopener"
                           :title="s.name"
                           class="w-8 h-8 rounded-lg bg-white/[0.05] border border-white/10 flex items-center justify-center
                                  text-white/50 hover:text-[#C4A265] hover:bg-[#C4A265]/10 hover:border-[#C4A265]/30
                                  hover:-translate-y-0.5 transition-all duration-300">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path :d="s.icon"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="lg:col-span-2">
                    <h3 class="text-white font-semibold text-[13px] mb-3 tracking-wide uppercase">
                        {{ t('quick_links') }}
                    </h3>
                    <ul class="space-y-1.5">
                        <li v-for="link in quickLinks" :key="link.route">
                            <Link :href="localizedRoute(link.route)"
                                  class="text-white/45 hover:text-[#C4A265] transition-colors duration-200 text-[13px]">
                                {{ link.label() }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Specialties -->
                <div class="lg:col-span-2">
                    <h3 class="text-white font-semibold text-[13px] mb-3 tracking-wide uppercase">
                        {{ isRtl ? 'التخصصات' : 'Specialties' }}
                    </h3>
                    <ul class="space-y-1.5">
                        <li v-for="link in serviceLinks" :key="link.label()">
                            <a :href="localizedRoute(link.route.split('?')[0]) + (link.route.includes('?') ? '?' + link.route.split('?')[1] : '')"
                               class="text-white/45 hover:text-[#C4A265] transition-colors duration-200 text-[13px]">
                                {{ link.label() }}
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact + hours -->
                <div class="col-span-2 md:col-span-4 lg:col-span-4">
                    <h3 class="text-white font-semibold text-[13px] mb-3 tracking-wide uppercase">
                        {{ t('contact_us') }}
                    </h3>
                    <ul class="space-y-2">
                        <li>
                            <a :href="`tel:${phone1}`" class="group flex items-center gap-2.5 text-white/50 hover:text-white transition-colors text-[13px]">
                                <svg class="w-3.5 h-3.5 text-[#C4A265] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                                </svg>
                                <span dir="ltr" class="font-mono text-xs tracking-wide">{{ phone1 }}</span>
                            </a>
                        </li>
                        <li>
                            <a :href="`mailto:${email}`" class="group flex items-center gap-2.5 text-white/50 hover:text-white transition-colors text-[13px]">
                                <svg class="w-3.5 h-3.5 text-[#C4A265] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                                <span dir="ltr" class="text-xs">{{ email }}</span>
                            </a>
                        </li>
                        <li>
                            <a :href="googleMaps" target="_blank" rel="noopener" class="group flex items-start gap-2.5 text-white/50 hover:text-white transition-colors text-[13px]">
                                <svg class="w-3.5 h-3.5 text-[#C4A265] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                </svg>
                                <span class="text-xs leading-relaxed">{{ t('clinic_address') }}</span>
                            </a>
                        </li>
                        <li class="flex items-center gap-2.5 pt-1">
                            <span class="relative flex h-2 w-2 flex-shrink-0">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-70"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                            </span>
                            <span class="text-white/50 text-xs">
                                {{ isRtl ? 'السبت - الخميس' : 'Sat - Thu' }} ·
                                <span class="font-mono text-[#C4A265]/90">10:00 – 22:00</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- ══ Bottom bar ══ -->
        <div class="relative z-10 border-t border-white/[0.06]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-2">
                <p class="text-[11px] text-white/35">
                    &copy; {{ new Date().getFullYear() }} Doctorato Polyclinic. {{ t('all_rights_reserved') }}
                </p>
                <div class="flex items-center gap-3 text-[11px] text-white/35">
                    <Link :href="localizedRoute('/page/privacy-policy')" class="hover:text-[#C4A265] transition-colors">
                        {{ t('privacy_policy') }}
                    </Link>
                    <span class="w-1 h-1 rounded-full bg-white/20"></span>
                    <Link :href="localizedRoute('/page/terms-of-use')" class="hover:text-[#C4A265] transition-colors">
                        {{ t('terms_conditions') }}
                    </Link>
                    <span class="w-1 h-1 rounded-full bg-white/20"></span>
                    <span>
                        {{ isRtl ? 'تطوير' : 'Dev' }}
                        <a href="https://markeza-group.com" target="_blank" rel="noopener"
                           class="text-[#C4A265]/70 hover:text-[#C4A265] font-medium transition-colors">Markeza</a>
                    </span>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
/* ══ Animated SVG grid + medical-cross pattern ══ */
.footer-pattern {
    background-image:
        url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' viewBox='0 0 48 48'><g fill='none' stroke='%23C4A265' stroke-width='0.8' stroke-linecap='round'><path d='M24 18 L24 30 M18 24 L30 24'/></g><circle cx='0' cy='0' r='1' fill='%23C4A265'/><circle cx='48' cy='48' r='1' fill='%23C4A265'/><circle cx='0' cy='48' r='0.6' fill='%23C4A265'/><circle cx='48' cy='0' r='0.6' fill='%23C4A265'/></svg>");
    background-size: 48px 48px;
    animation: patternDrift 40s linear infinite;
    will-change: background-position;
}
@keyframes patternDrift {
    from { background-position: 0 0; }
    to   { background-position: 480px 240px; }
}

/* ══ Diagonal stripe texture that slowly slides ══ */
.footer-stripes {
    background-image: repeating-linear-gradient(45deg,
        transparent 0 38px,
        #C4A265 38px 39px);
    background-size: 60px 60px;
    animation: stripeSlide 22s linear infinite;
    will-change: background-position;
}
@keyframes stripeSlide {
    from { background-position: 0 0; }
    to   { background-position: 120px 120px; }
}

/* ══ Floating gold orbs ══ */
.f-orb {
    position: absolute;
    border-radius: 9999px;
    filter: blur(60px);
    will-change: transform;
}
.f-orb-1 {
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(196,162,101,0.55) 0%, transparent 70%);
    top: -80px; left: -60px;
    opacity: 0.35;
    animation: orbA 16s ease-in-out infinite;
}
.f-orb-2 {
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(196,162,101,0.45) 0%, transparent 70%);
    bottom: -70px; right: 8%;
    opacity: 0.3;
    animation: orbB 20s ease-in-out infinite;
}
.f-orb-3 {
    width: 180px; height: 180px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
    top: 30%; left: 55%;
    opacity: 0.4;
    animation: orbC 26s ease-in-out infinite;
}
@keyframes orbA {
    0%,100% { transform: translate(0,0) scale(1); }
    33%     { transform: translate(80px, 30px) scale(1.1); }
    66%     { transform: translate(40px,-25px) scale(0.95); }
}
@keyframes orbB {
    0%,100% { transform: translate(0,0) scale(1); }
    50%     { transform: translate(-90px,-40px) scale(1.15); }
}
@keyframes orbC {
    0%,100% { transform: translate(0,0) scale(1); opacity: 0.3; }
    50%     { transform: translate(-50px,70px) scale(1.2); opacity: 0.55; }
}

/* ══ Shimmer along top accent line ══ */
.footer-shimmer {
    animation: shimmerSlide 4.5s ease-in-out infinite;
    will-change: transform;
}
@keyframes shimmerSlide {
    0%   { transform: translateX(-120%); }
    60%  { transform: translateX(420%); }
    100% { transform: translateX(420%); }
}

/* ══ Respect reduced-motion ══ */
@media (prefers-reduced-motion: reduce) {
    .footer-pattern, .footer-stripes,
    .f-orb-1, .f-orb-2, .f-orb-3, .footer-shimmer {
        animation: none;
    }
}
</style>
