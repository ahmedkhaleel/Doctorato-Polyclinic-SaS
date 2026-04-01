<script setup>
import { Link } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { sanitizeHtml } from '@/Composables/useSanitize';

const { t, localized, localizedRoute } = useLocale();

const props = defineProps({
    service: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="group animated-border bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-500 border border-gray-100 hover:border-[var(--brand-primary)]/30
                card-hover-lift" @mousemove="$event.currentTarget.style.setProperty('--mouse-x', (($event.clientX - $event.currentTarget.getBoundingClientRect().left) / $event.currentTarget.getBoundingClientRect().width * 100) + '%'); $event.currentTarget.style.setProperty('--mouse-y', (($event.clientY - $event.currentTarget.getBoundingClientRect().top) / $event.currentTarget.getBoundingClientRect().height * 100) + '%')"
    >
        <!-- Image -->
        <div class="relative h-52 overflow-hidden">
            <div
                v-if="service.image || service.featured_image"
                class="w-full h-full bg-cover bg-center transition-transform duration-700 group-hover:scale-110"
                :style="{ backgroundImage: `url(${service.image || service.featured_image})` }"
            ></div>
            <div
                v-else
                class="w-full h-full bg-gradient-to-br from-[var(--brand-primary)]/20 via-[var(--brand-primary)]/10 to-[var(--brand-primary)]/5 flex items-center justify-center"
            >
                <svg class="w-16 h-16 text-[var(--brand-primary)]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </div>
            <!-- Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        </div>

        <!-- Gold Icon Circle -->
        <div class="relative flex justify-center -mt-7 z-10">
            <div class="w-14 h-14 bg-[var(--brand-primary)] rounded-full flex items-center justify-center shadow-lg shadow-[var(--brand-primary)]/20 group-hover:shadow-[var(--brand-primary)]/40 group-hover:animate-glow-ring transition-all duration-500 group-hover:scale-110">
                <div v-if="service.icon" v-html="sanitizeHtml(service.icon)" class="w-6 h-6 text-white"></div>
                <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 pt-4 text-center">
            <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-[var(--brand-primary)] transition-colors">
                {{ localized(service, 'name') }}
            </h3>
            <p class="text-gray-500 text-sm leading-relaxed mb-4 line-clamp-3">
                {{ localized(service, 'short_desc') }}
            </p>
            <Link
                :href="localizedRoute(`/services/${service.slug}`)"
                class="btn-shimmer inline-flex items-center gap-1.5 text-[var(--brand-primary)] font-semibold text-sm hover:gap-3 transition-all duration-300"
            >
                {{ t('learn_more') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </Link>
        </div>
    </div>
</template>
