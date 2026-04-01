<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { sanitizeHtml } from '@/Composables/useSanitize';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    post: Object,
    relatedPosts: {
        type: Array,
        default: () => [],
    },
    seo: Object,
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();

const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

const currentUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.href;
    }
    return '';
});

const articleTitle = computed(() => localized(props.post, 'title'));
const articleContent = computed(() => sanitizeHtml(localized(props.post, 'content')));
const articleExcerpt = computed(() => localized(props.post, 'excerpt'));

const shareUrls = computed(() => {
    const url = encodeURIComponent(currentUrl.value);
    const title = encodeURIComponent(articleTitle.value);
    return {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
        twitter: `https://twitter.com/intent/tweet?url=${url}&text=${title}`,
        whatsapp: `https://wa.me/?text=${title}%20${url}`,
    };
});

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <SeoHead
        :title="seoTitle"
        :description="seoDescription"
        :keywords="seo?.keywords"
        :image="seo?.image"
    />

    <!-- Breadcrumb -->
    <section class="relative bg-[#FDF8F0] border-b border-[#F5EDE0] overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-dots"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
                <Link :href="localizedRoute('/')" class="hover:text-[var(--brand-primary)] transition-colors">
                    {{ t('home') }}
                </Link>
                <svg class="w-4 h-4 flex-shrink-0" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <Link :href="localizedRoute('/blog')" class="hover:text-[var(--brand-primary)] transition-colors">
                    {{ t('blog') }}
                </Link>
                <svg class="w-4 h-4 flex-shrink-0" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-[var(--brand-primary)] line-clamp-1">{{ articleTitle }}</span>
            </nav>
        </div>
    </section>

    <!-- Article Header -->
    <section class="relative bg-white pt-8 lg:pt-12 pb-0 overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-diamond"></div>
        <!-- Floating decorative elements -->
        <div class="absolute top-6 end-[10%] w-16 h-16 rounded-full bg-gold-primary/5 animate-float"></div>
        <div class="absolute bottom-10 start-[8%] w-12 h-12 rounded-full bg-gold-primary/6 animate-float-slow"></div>
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Category Badge -->
            <div v-if="post.category" class="mb-4" v-scroll-reveal="{ type: 'fade-up' }">
                <Link
                    :href="localizedRoute(`/blog?category=${post.category.id}`)"
                    class="inline-block px-4 py-1.5 bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] text-xs font-semibold rounded-full hover:bg-[var(--brand-primary)]/20 transition-colors"
                >
                    {{ localized(post.category, 'name') }}
                </Link>
            </div>

            <!-- Title -->
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-[#3A3A3A] mb-6 leading-tight" v-scroll-reveal="{ type: 'blur-in', delay: 100 }">
                {{ articleTitle }}
            </h1>

            <!-- Meta -->
            <div class="flex flex-wrap items-center justify-center gap-4 text-sm text-gray-500 mb-8" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ formatDate(post.published_at) }}
                </span>
                <span v-if="post.author" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ localized(post.author, 'name') || post.author.name }}
                </span>
                <span v-if="post.reading_time" class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ post.reading_time }} {{ locale === 'ar' ? 'دقائق قراءة' : 'min read' }}
                </span>
            </div>
        </div>

        <!-- Featured Image -->
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8" v-scroll-reveal="{ type: 'fade-up', delay: 300 }">
            <div class="rounded-2xl overflow-hidden shadow-lg img-hover-reveal">
                <img
                    :src="post.featured_image || '/images/blog-placeholder.jpg'"
                    :alt="articleTitle"
                    class="w-full max-h-96 object-cover"
                />
            </div>
        </div>
    </section>

    <!-- Article Body + Sidebar -->
    <section class="relative py-12 lg:py-16 bg-white overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-diagonal-reverse"></div>
        <!-- Floating decorative elements -->
        <div class="absolute top-10 start-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
        <div class="absolute bottom-20 end-10 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
        <div class="absolute top-1/2 end-[3%] w-10 h-10 rounded-full bg-gold-primary/4 animate-float-delay"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Main Content -->
                <article class="lg:col-span-2" v-scroll-reveal="{ type: 'fade-up' }">
                    <div
                        class="prose prose-lg max-w-none prose-headings:text-[#3A3A3A] prose-headings:font-bold prose-a:text-[var(--brand-primary)] prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl prose-blockquote:border-[var(--brand-primary)] prose-strong:text-[#3A3A3A] leading-relaxed"
                        :class="locale === 'ar' ? 'text-right' : 'text-left'"
                        v-html="articleContent"
                    ></div>

                    <!-- Tags -->
                    <div v-if="post.tags && post.tags.length" class="mt-10 pt-8 border-t border-gray-100">
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            {{ locale === 'ar' ? 'الوسوم' : 'Tags' }}
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in post.tags"
                                :key="tag.id || tag"
                                class="inline-block px-3 py-1.5 bg-[#F5EDE0] text-[#3A3A3A] text-sm rounded-full hover:bg-[var(--brand-primary)]/10 hover:text-[var(--brand-primary)] transition-colors cursor-default"
                            >
                                #{{ typeof tag === 'string' ? tag : (localized(tag, 'name') || tag.name) }}
                            </span>
                        </div>
                    </div>

                    <!-- Social Share - Mobile (below content) -->
                    <div class="mt-10 pt-8 border-t border-gray-100 lg:hidden">
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                            {{ locale === 'ar' ? 'شاركي المقال' : 'Share Article' }}
                        </h4>
                        <div class="flex items-center gap-3">
                            <a
                                :href="shareUrls.facebook"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-[#F5EDE0] text-gray-600 hover:bg-[var(--brand-primary)] hover:text-white transition-all duration-300"
                                :aria-label="locale === 'ar' ? 'مشاركة على فيسبوك' : 'Share on Facebook'"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a
                                :href="shareUrls.twitter"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-[#F5EDE0] text-gray-600 hover:bg-[var(--brand-primary)] hover:text-white transition-all duration-300"
                                :aria-label="locale === 'ar' ? 'مشاركة على تويتر' : 'Share on Twitter'"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                            <a
                                :href="shareUrls.whatsapp"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-[#F5EDE0] text-gray-600 hover:bg-[#25D366] hover:text-white transition-all duration-300"
                                :aria-label="locale === 'ar' ? 'مشاركة على واتساب' : 'Share on WhatsApp'"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                        </div>
                    </div>
                </article>

                <!-- Sidebar -->
                <aside class="lg:col-span-1 space-y-8" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                    <!-- Author Card -->
                    <div v-if="post.author" class="bg-[#FDF8F0] rounded-2xl p-6 border border-[#F5EDE0] card-hover-lift card-premium" v-scroll-reveal="{ type: 'fade-left', delay: 250 }">
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                            {{ locale === 'ar' ? 'الكاتب' : 'Author' }}
                        </h4>
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-[var(--brand-primary)]/10 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                <img
                                    v-if="post.author.avatar || post.author.image"
                                    :src="post.author.avatar || post.author.image"
                                    :alt="localized(post.author, 'name') || post.author.name"
                                    class="w-full h-full object-cover"
                                />
                                <svg v-else class="w-7 h-7 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-[#3A3A3A]">
                                    {{ localized(post.author, 'name') || post.author.name }}
                                </p>
                                <p v-if="post.author.title || post.author.specialty" class="text-sm text-gray-500">
                                    {{ localized(post.author, 'title') || localized(post.author, 'specialty') || post.author.title }}
                                </p>
                            </div>
                        </div>
                        <p v-if="localized(post.author, 'bio') || post.author.bio" class="text-sm text-gray-600 mt-4 leading-relaxed">
                            {{ localized(post.author, 'bio') || post.author.bio }}
                        </p>
                    </div>

                    <!-- Share Section - Desktop -->
                    <div class="hidden lg:block bg-[#FDF8F0] rounded-2xl p-6 border border-[#F5EDE0] card-hover-lift card-premium" v-scroll-reveal="{ type: 'fade-left', delay: 300 }">
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                            {{ locale === 'ar' ? 'شاركي المقال' : 'Share Article' }}
                        </h4>
                        <div class="flex items-center gap-3">
                            <a
                                :href="shareUrls.facebook"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white text-gray-600 hover:bg-[var(--brand-primary)] hover:text-white transition-all duration-300 shadow-sm"
                                :aria-label="locale === 'ar' ? 'مشاركة على فيسبوك' : 'Share on Facebook'"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a
                                :href="shareUrls.twitter"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white text-gray-600 hover:bg-[var(--brand-primary)] hover:text-white transition-all duration-300 shadow-sm"
                                :aria-label="locale === 'ar' ? 'مشاركة على تويتر' : 'Share on Twitter'"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                            <a
                                :href="shareUrls.whatsapp"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white text-gray-600 hover:bg-[#25D366] hover:text-white transition-all duration-300 shadow-sm"
                                :aria-label="locale === 'ar' ? 'مشاركة على واتساب' : 'Share on WhatsApp'"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Tags - Sidebar -->
                    <div v-if="post.tags && post.tags.length" class="hidden lg:block bg-[#FDF8F0] rounded-2xl p-6 border border-[#F5EDE0] card-hover-lift card-premium" v-scroll-reveal="{ type: 'fade-left', delay: 350 }">
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                            {{ locale === 'ar' ? 'الوسوم' : 'Tags' }}
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in post.tags"
                                :key="tag.id || tag"
                                class="inline-block px-3 py-1.5 bg-white text-[#3A3A3A] text-sm rounded-full hover:bg-[var(--brand-primary)]/10 hover:text-[var(--brand-primary)] transition-colors cursor-default shadow-sm"
                            >
                                #{{ typeof tag === 'string' ? tag : (localized(tag, 'name') || tag.name) }}
                            </span>
                        </div>
                    </div>

                    <!-- Related Articles - Sidebar -->
                    <div v-if="relatedPosts.length" class="bg-[#FDF8F0] rounded-2xl p-6 border border-[#F5EDE0] card-hover-lift card-premium" v-scroll-reveal="{ type: 'fade-left', delay: 400 }">
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                            {{ locale === 'ar' ? 'مقالات ذات صلة' : 'Related Articles' }}
                        </h4>
                        <div class="space-y-4">
                            <Link
                                v-for="related in relatedPosts.slice(0, 4)"
                                :key="related.id"
                                :href="localizedRoute(`/blog/${related.slug}`)"
                                class="flex gap-3 group"
                            >
                                <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                                    <img
                                        :src="related.featured_image || '/images/blog-placeholder.jpg'"
                                        :alt="localized(related, 'title')"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-sm font-semibold text-[#3A3A3A] line-clamp-2 group-hover:text-[var(--brand-primary)] transition-colors">
                                        {{ localized(related, 'title') }}
                                    </h5>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ formatDate(related.published_at) }}
                                    </p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- Related Articles Grid -->
    <section v-if="relatedPosts.length" class="relative py-12 lg:py-16 bg-[#FDF8F0] overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-crosshatch"></div>
        <!-- Floating decorative elements -->
        <div class="absolute top-12 end-12 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
        <div class="absolute bottom-14 start-14 w-14 h-14 rounded-full bg-gold-primary/6 animate-float-slow"></div>
        <div class="absolute top-1/3 start-[6%] w-10 h-10 rounded-full bg-gold-primary/4 animate-float-delay"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                <h2 class="text-2xl md:text-3xl font-bold text-[#3A3A3A] mb-3">
                    {{ locale === 'ar' ? 'مقالات قد تهمك' : 'You May Also Like' }}
                </h2>
                <div class="w-16 h-1 bg-[var(--brand-primary)] mx-auto rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                <article
                    v-for="(related, index) in relatedPosts.slice(0, 3)"
                    :key="related.id"
                    class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl border border-gray-100 hover:border-[var(--brand-primary)]/30 transition-all duration-300 hover:-translate-y-1 card-hover-lift card-premium"
                >
                    <Link :href="localizedRoute(`/blog/${related.slug}`)" class="block relative h-52 overflow-hidden img-hover-reveal">
                        <img
                            :src="related.featured_image || '/images/blog-placeholder.jpg'"
                            :alt="localized(related, 'title')"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div v-if="related.category" class="absolute top-3 left-3">
                            <span class="inline-block px-3 py-1 bg-[var(--brand-primary)] text-white text-xs font-semibold rounded-full">
                                {{ localized(related.category, 'name') }}
                            </span>
                        </div>
                    </Link>
                    <div class="p-6">
                        <div class="flex items-center gap-3 text-xs text-gray-500 mb-3">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(related.published_at) }}
                            </span>
                            <span v-if="related.reading_time" class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ related.reading_time }} {{ locale === 'ar' ? 'د' : 'min' }}
                            </span>
                        </div>
                        <Link :href="localizedRoute(`/blog/${related.slug}`)">
                            <h3 class="text-lg font-bold text-[#3A3A3A] mb-2 line-clamp-2 group-hover:text-[var(--brand-primary)] transition-colors">
                                {{ localized(related, 'title') }}
                            </h3>
                        </Link>
                        <p class="text-gray-600 text-sm line-clamp-2 mb-4 leading-relaxed">
                            {{ localized(related, 'excerpt') }}
                        </p>
                        <Link
                            :href="localizedRoute(`/blog/${related.slug}`)"
                            class="inline-flex items-center gap-1.5 text-[var(--brand-primary)] text-sm font-semibold hover:gap-3 transition-all duration-300"
                        >
                            {{ t('read_more', { default: locale === 'ar' ? 'اقرأ المزيد' : 'Read More' }) }}
                            <svg class="w-4 h-4" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 lg:py-20 bg-gradient-to-br from-[#3A3A3A] via-[#3a3a3a] to-[#3A3A3A] relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-wave"></div>
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-[var(--brand-primary)] rounded-full blur-3xl animate-float-slow"></div>
        </div>
        <!-- Floating decorative elements -->
        <div class="absolute top-8 start-[12%] w-14 h-14 rounded-full bg-white/5 animate-float"></div>
        <div class="absolute bottom-12 end-[18%] w-10 h-10 rounded-full bg-white/6 animate-float-delay"></div>
        <div class="absolute top-1/3 end-[6%] w-8 h-8 rounded-full bg-white/4 animate-float-slow"></div>
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" v-scroll-reveal="{ type: 'fade-up' }">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4" v-scroll-reveal="{ type: 'blur-in' }">
                {{ locale === 'ar' ? 'هل تحتاجين لاستشارة متخصصة؟' : 'Need a Specialized Consultation?' }}
            </h2>
            <p class="text-gray-300 mb-8 text-lg" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                {{ locale === 'ar' ? 'احجزي موعدك الآن واحصلي على رعاية طبية متميزة' : 'Book your appointment and receive exceptional medical care' }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                <Link
                    :href="localizedRoute('/booking')"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-[var(--brand-primary)] text-white font-semibold rounded-full hover:bg-[var(--brand-primary-hover)] transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-[var(--brand-primary)]/30 btn-shimmer"
                >
                    {{ t('book_now', { default: locale === 'ar' ? 'احجزي الآن' : 'Book Now' }) }}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </Link>
                <a
                    :href="whatsappLink"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-full hover:bg-white/20 transition-all duration-300 border border-white/20 btn-shimmer"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    {{ locale === 'ar' ? 'تواصلي عبر واتساب' : 'Chat on WhatsApp' }}
                </a>
            </div>
        </div>
    </section>
</template>
