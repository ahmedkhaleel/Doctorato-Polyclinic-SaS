<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';

const { t, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();

const scrollY = ref(0);

function handleScroll() {
    scrollY.value = window.scrollY;
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <section class="relative min-h-[80vh] flex items-center justify-center overflow-hidden">
        <!-- Background with Parallax -->
        <div
            class="absolute inset-0 bg-gradient-to-br from-[#3A3A3A] via-[#1a1a2e] to-[#16213e]"
            :style="{ transform: `translateY(${scrollY * 0.3}px)` }"
        >
            <!-- Decorative Gold Pattern -->
            <div class="absolute inset-0 opacity-5">
                <div class="absolute top-20 left-10 w-72 h-72 rounded-full border border-[var(--brand-primary)]"></div>
                <div class="absolute top-40 right-20 w-96 h-96 rounded-full border border-[var(--brand-primary)]"></div>
                <div class="absolute bottom-10 left-1/3 w-64 h-64 rounded-full border border-[var(--brand-primary)]"></div>
            </div>

            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/20 to-black/60"></div>
        </div>

        <!-- Floating Gold Particles (Decorative) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-1 h-1 bg-[var(--brand-primary)] rounded-full animate-pulse opacity-60"></div>
            <div class="absolute top-1/3 right-1/3 w-1.5 h-1.5 bg-[var(--brand-primary)] rounded-full animate-ping opacity-40" style="animation-delay: 0.5s;"></div>
            <div class="absolute bottom-1/3 left-1/2 w-1 h-1 bg-[var(--brand-primary)] rounded-full animate-pulse opacity-50" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 right-1/4 w-2 h-2 bg-[var(--brand-primary)] rounded-full animate-ping opacity-30" style="animation-delay: 1.5s;"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Gold Decorative Line -->
            <div class="flex items-center justify-center gap-4 mb-6">
                <span class="block w-12 h-px bg-[var(--brand-primary)]"></span>
                <span class="text-[var(--brand-primary)] text-sm uppercase tracking-[0.3em] font-light">{{ t('welcome_to') }}</span>
                <span class="block w-12 h-px bg-[var(--brand-primary)]"></span>
            </div>

            <!-- Clinic Name -->
            <h1 class="mb-4">
                <span class="block text-5xl sm:text-6xl lg:text-7xl font-bold text-[var(--brand-primary)] tracking-[0.2em] mb-2">
                    AURA
                </span>
                <span class="block text-lg sm:text-xl lg:text-2xl text-white/90 font-light tracking-[0.15em] uppercase">
                    Derma Aesthetic Clinic
                </span>
            </h1>

            <!-- Tagline -->
            <p class="text-white/70 text-lg sm:text-xl lg:text-2xl font-light mt-6 mb-10 max-w-2xl mx-auto leading-relaxed">
                {{ t('hero_tagline') }}
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <Link
                    :href="localizedRoute('/booking')"
                    class="group inline-flex items-center gap-2 px-8 py-4 bg-[var(--brand-primary)] text-white font-semibold rounded-full hover:bg-[var(--brand-primary-hover)] transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-[var(--brand-primary)]/30 text-lg"
                >
                    {{ t('book_appointment') }}
                    <svg class="w-5 h-5 transition-transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </Link>
                <a
                    :href="whatsappLink"
                    target="_blank"
                    rel="noopener"
                    class="group inline-flex items-center gap-2 px-8 py-4 border-2 border-white/30 text-white font-semibold rounded-full hover:border-[#25D366] hover:bg-[#25D366]/10 transition-all duration-300 text-lg"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    {{ t('whatsapp_us') }}
                </a>
            </div>

            <!-- Scroll Indicator -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
                <div class="w-6 h-10 rounded-full border-2 border-white/30 flex items-start justify-center p-1.5">
                    <div class="w-1 h-2.5 bg-[var(--brand-primary)] rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>
    </section>
</template>
