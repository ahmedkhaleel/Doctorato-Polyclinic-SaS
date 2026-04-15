<script setup>
import { Link } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { sanitizeHtml } from '@/Composables/useSanitize';

const { t, localized, isRtl, localizedRoute } = useLocale();

const props = defineProps({
    service: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Link :href="localizedRoute(`/services/${service.slug}`)"
          class="svc-card group relative bg-white rounded-2xl p-6 border border-gray-100
                 hover:border-[#C4A265]/25 hover:shadow-xl hover:shadow-[#1B365D]/5
                 hover:-translate-y-1.5 transition-all duration-500 block overflow-hidden">

        <!-- Background texture on hover -->
        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"
             style="background-image: radial-gradient(circle at 80% 20%, rgba(196,162,101,0.04) 0%, transparent 50%);"></div>

        <!-- Top accent line -->
        <div class="absolute top-0 inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-[#C4A265]/0 to-transparent
                    group-hover:via-[#C4A265]/50 transition-all duration-500"></div>

        <!-- Icon -->
        <div class="relative w-14 h-14 mb-5">
            <!-- Icon background with glow -->
            <div class="absolute inset-0 bg-[#1B365D]/5 rounded-xl group-hover:bg-[#C4A265]/10
                        transition-all duration-500 group-hover:scale-110 group-hover:rotate-3"></div>
            <div class="relative w-full h-full rounded-xl flex items-center justify-center">
                <div v-if="service.icon" v-html="sanitizeHtml(service.icon)"
                     class="w-6 h-6 text-[#1B365D] group-hover:text-[#C4A265] transition-colors duration-500"></div>
                <svg v-else class="w-6 h-6 text-[#1B365D] group-hover:text-[#C4A265] transition-colors duration-500"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
            </div>
        </div>

        <!-- Title -->
        <h3 class="text-base md:text-lg font-bold text-[#1B365D] mb-2.5 group-hover:text-[#C4A265] transition-colors duration-300 leading-snug">
            {{ localized(service, 'name') }}
        </h3>

        <!-- Description -->
        <p class="text-gray-500 text-sm leading-relaxed mb-5 line-clamp-2">
            {{ localized(service, 'short_desc') }}
        </p>

        <!-- CTA -->
        <div class="flex items-center gap-1.5 text-[#C4A265] text-sm font-semibold">
            <span>{{ t('learn_more') }}</span>
            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1"
                 :class="{ 'rotate-180 group-hover:-translate-x-1': isRtl }"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </div>
    </Link>
</template>
