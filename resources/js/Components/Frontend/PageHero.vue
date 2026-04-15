<script setup>
import { useLocale } from '@/Composables/useLocale';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    breadcrumb: { type: String, default: '' },
});

const { isRtl, localizedRoute } = useLocale();
</script>

<template>
    <section class="relative h-72 md:h-80 overflow-hidden">
        <!-- Navy gradient background -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#0f2847] via-[#1B365D] to-[#264573]"></div>

        <!-- Texture pattern -->
        <div class="absolute inset-0 opacity-[0.04]"
             style="background-image: radial-gradient(circle at 1px 1px, rgba(196,162,101,0.8) 1px, transparent 0); background-size: 32px 32px;"></div>

        <!-- Geometric decorations -->
        <div class="absolute top-8 end-[10%] w-48 h-48 border border-[#C4A265]/[0.08] rounded-full page-hero-orbit"></div>
        <div class="absolute -bottom-10 start-[5%] w-64 h-64 border border-white/[0.04] rounded-full page-hero-orbit" style="animation-duration:30s;animation-direction:reverse;"></div>
        <div class="absolute top-1/3 start-[15%] w-2 h-2 bg-[#C4A265]/20 rounded-full animate-pulse"></div>
        <div class="absolute bottom-1/3 end-[20%] w-1.5 h-1.5 bg-[#C4A265]/15 rounded-full animate-pulse" style="animation-delay:1.5s;"></div>

        <!-- Gold accent lines -->
        <div class="absolute bottom-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265]/40 to-transparent"></div>
        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/5 to-transparent"></div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-4 max-w-4xl mx-auto">
            <!-- Breadcrumb -->
            <div v-if="breadcrumb" class="flex items-center gap-2 mb-4 text-white/40 text-xs tracking-wider">
                <a :href="localizedRoute('/')" class="hover:text-[#C4A265] transition-colors">
                    {{ isRtl ? 'الرئيسية' : 'Home' }}
                </a>
                <svg class="w-3 h-3" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-[#C4A265]/70">{{ breadcrumb }}</span>
            </div>

            <!-- Title -->
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4 page-hero-title">
                {{ title }}
            </h1>

            <!-- Divider -->
            <div class="w-12 h-[2px] bg-[#C4A265]/60 mx-auto mb-4 page-hero-line"></div>

            <!-- Subtitle -->
            <p v-if="subtitle" class="text-base md:text-lg text-white/60 max-w-2xl leading-relaxed page-hero-subtitle">
                {{ subtitle }}
            </p>
        </div>
    </section>
</template>

<style scoped>
@keyframes pageHeroOrbit {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.page-hero-orbit {
    animation: pageHeroOrbit 35s linear infinite;
}

@keyframes heroTitleIn {
    0%   { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
@keyframes heroLineIn {
    0%   { opacity: 0; width: 0; }
    100% { opacity: 1; width: 3rem; }
}
@keyframes heroSubIn {
    0%   { opacity: 0; transform: translateY(10px); filter: blur(2px); }
    100% { opacity: 1; transform: translateY(0); filter: blur(0); }
}

.page-hero-title {
    animation: heroTitleIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;
}
.page-hero-line {
    animation: heroLineIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both;
}
.page-hero-subtitle {
    animation: heroSubIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both;
}
</style>
