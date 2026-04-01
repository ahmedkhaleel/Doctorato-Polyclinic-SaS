<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';

const { t, localized, localizedRoute } = useLocale();

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
});

const formattedDate = computed(() => {
    if (!props.post.published_at && !props.post.created_at) return '';
    const date = new Date(props.post.published_at || props.post.created_at);
    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const readingTime = computed(() => {
    const content = localized(props.post, 'content') || props.post.content || '';
    const words = content.split(/\s+/).length;
    const minutes = Math.max(1, Math.ceil(words / 200));
    return minutes;
});
</script>

<template>
    <article class="group card-hover-lift card-premium bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-500 border border-gray-100 hover:border-[var(--brand-primary)]/20">
        <!-- Image -->
        <div class="relative h-52 overflow-hidden img-hover-reveal">
            <div
                v-if="post.image || post.featured_image"
                class="w-full h-full bg-cover bg-center transition-transform duration-700 group-hover:scale-110"
                :style="{ backgroundImage: `url(${post.image || post.featured_image})` }"
            ></div>
            <div
                v-else
                class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center"
            >
                <svg class="w-16 h-16 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
            </div>

            <!-- Category Badge -->
            <div
                v-if="post.category"
                class="absolute top-4 start-4 bg-[var(--brand-primary)] text-white text-xs font-semibold px-3 py-1 rounded-full"
            >
                {{ localized(post.category, 'name') || post.category.name }}
            </div>

            <!-- Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Meta -->
            <div class="flex items-center gap-4 text-xs text-gray-400 mb-3">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ formattedDate }}
                </span>
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ readingTime }} {{ t('min_read') }}
                </span>
            </div>

            <!-- Title -->
            <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-[var(--brand-primary)] transition-colors line-clamp-2 hover-underline">
                {{ localized(post, 'title') }}
            </h3>

            <!-- Excerpt -->
            <p class="text-gray-500 text-sm leading-relaxed mb-4 line-clamp-3">
                {{ localized(post, 'excerpt') || localized(post, 'short_description') }}
            </p>

            <!-- Read More -->
            <Link
                :href="localizedRoute(`/blog/${post.slug}`)"
                class="inline-flex items-center gap-1.5 text-[var(--brand-primary)] font-semibold text-sm hover:gap-3 transition-all duration-300"
            >
                {{ t('read_more') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </Link>
        </div>
    </article>
</template>
