<script setup>
import { useLocale } from '@/Composables/useLocale';

const { t, localized } = useLocale();

const props = defineProps({
    doctor: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="group card-hover-lift text-center bg-white rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-500 border border-gray-100 hover:border-[var(--brand-primary)]/20 animated-border card-premium"
         @mousemove="$event.currentTarget.style.setProperty('--mouse-x', (($event.clientX - $event.currentTarget.getBoundingClientRect().left) / $event.currentTarget.getBoundingClientRect().width * 100) + '%'); $event.currentTarget.style.setProperty('--mouse-y', (($event.clientY - $event.currentTarget.getBoundingClientRect().top) / $event.currentTarget.getBoundingClientRect().height * 100) + '%')"
    >
        <!-- Photo -->
        <div class="relative w-36 h-36 mx-auto mb-5">
            <!-- Gold Border Ring -->
            <div class="absolute inset-0 rounded-full border-2 border-[var(--brand-primary)]/30 group-hover:border-[var(--brand-primary)] group-hover:animate-glow-ring transition-colors duration-500"></div>
            <div class="absolute inset-1 rounded-full overflow-hidden bg-gradient-to-br from-[var(--brand-primary)]/10 to-[var(--brand-primary)]/5 img-hover-reveal">
                <img
                    v-if="doctor.photo_url || doctor.photo || doctor.image"
                    :src="doctor.photo_url || doctor.photo || doctor.image"
                    :alt="localized(doctor, 'name')"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-[var(--brand-primary)]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <!-- Decorative Gold Dot -->
            <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-3 h-3 bg-[var(--brand-primary)] rounded-full border-2 border-white shadow-sm"></div>
        </div>

        <!-- Info -->
        <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-[var(--brand-primary)] transition-colors">
            {{ localized(doctor, 'name') }}
        </h3>
        <p class="text-[var(--brand-primary)] text-sm font-medium mb-3">
            {{ localized(doctor, 'specialization') || localized(doctor, 'title') }}
        </p>
        <p class="text-gray-500 text-sm leading-relaxed line-clamp-3">
            {{ localized(doctor, 'bio') || localized(doctor, 'short_bio') }}
        </p>
    </div>
</template>
