<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    posts: Object,
    categories: Array,
    featuredPost: {
        type: Object,
        default: null,
    },
    seo: Object,
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();

const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

const activeCategory = ref(null);

function filterByCategory(categoryId) {
    activeCategory.value = categoryId;
    const params = {};
    if (categoryId) {
        params.category = categoryId;
    }
    router.get(localizedRoute('/blog'), params, {
        preserveState: true,
        preserveScroll: false,
    });
}

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

    <!-- Page Hero -->
    <section class="relative bg-gradient-to-br from-[#3A3A3A] via-[#3a3a3a] to-[#3A3A3A] py-20 lg:py-28 overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-diamond"></div>
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-72 h-72 bg-[var(--brand-primary)] rounded-full blur-3xl animate-float"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-[var(--brand-primary)] rounded-full blur-3xl animate-float-slow"></div>
        </div>
        <!-- Floating decorative elements -->
        <div class="absolute top-8 end-[15%] w-16 h-16 rounded-full bg-gold-primary/5 animate-float-delay"></div>
        <div class="absolute bottom-12 start-[20%] w-10 h-10 rounded-full bg-gold-primary/8 animate-float-slow"></div>
        <div class="absolute top-1/2 start-[8%] w-8 h-8 rounded-full bg-gold-primary/4 animate-float"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1
                class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4"
                v-scroll-reveal="{ type: 'blur-in' }"
            >
                {{ t('blog') }}
            </h1>
            <div class="w-20 h-1 bg-[var(--brand-primary)] mx-auto rounded-full mb-6" v-scroll-reveal="{ type: 'fade-up', delay: 100 }"></div>
            <p class="text-lg text-gray-300 max-w-2xl mx-auto" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                {{ t('blog_subtitle', { default: locale === 'ar' ? 'مقالات ونصائح طبية من خبراء العناية بالبشرة' : 'Medical articles and skincare tips from our experts' }) }}
            </p>
            <!-- Breadcrumb -->
            <nav class="mt-8 flex items-center justify-center gap-2 text-sm text-gray-400" v-scroll-reveal="{ type: 'fade-up', delay: 300 }">
                <Link :href="localizedRoute('/')" class="hover:text-[var(--brand-primary)] transition-colors">
                    {{ t('home') }}
                </Link>
                <svg class="w-4 h-4" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-[var(--brand-primary)]">{{ t('blog') }}</span>
            </nav>
        </div>
    </section>

    <!-- Featured Post -->
    <section v-if="featuredPost" class="relative py-12 lg:py-16 bg-[#FDF8F0] overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-diagonal-reverse"></div>
        <!-- Floating decorative elements -->
        <div class="absolute top-10 start-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
        <div class="absolute bottom-20 end-10 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                class="bg-white rounded-2xl overflow-hidden shadow-lg border border-[var(--brand-primary)]/20 hover:shadow-xl transition-shadow duration-300 card-hover-lift card-premium"
                v-scroll-reveal="{ type: 'fade-up' }"
            >
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <!-- Image -->
                    <div class="relative h-64 lg:h-full min-h-[300px] img-hover-reveal">
                        <img
                            :src="featuredPost.featured_image || '/images/blog-placeholder.jpg'"
                            :alt="localized(featuredPost, 'title')"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute top-4 left-4">
                            <span class="inline-block px-4 py-1.5 bg-[var(--brand-primary)] text-white text-xs font-semibold rounded-full uppercase tracking-wider">
                                {{ locale === 'ar' ? 'مميز' : 'Featured' }}
                            </span>
                        </div>
                    </div>
                    <!-- Content -->
                    <div class="p-8 lg:p-12 flex flex-col justify-center">
                        <div v-if="featuredPost.category" class="mb-4" v-scroll-reveal="{ type: 'fade-left', delay: 100 }">
                            <span class="inline-block px-3 py-1 bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] text-xs font-semibold rounded-full">
                                {{ localized(featuredPost.category, 'name') }}
                            </span>
                        </div>
                        <h2 class="text-2xl lg:text-3xl font-bold text-[#3A3A3A] mb-4 leading-tight" v-scroll-reveal="{ type: 'fade-left', delay: 150 }">
                            {{ localized(featuredPost, 'title') }}
                        </h2>
                        <p class="text-gray-600 mb-6 line-clamp-3 leading-relaxed" v-scroll-reveal="{ type: 'fade-left', delay: 200 }">
                            {{ localized(featuredPost, 'excerpt') }}
                        </p>
                        <div class="flex items-center gap-4 text-sm text-gray-500 mb-6">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(featuredPost.published_at) }}
                            </span>
                            <span v-if="featuredPost.author" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ localized(featuredPost.author, 'name') || featuredPost.author.name }}
                            </span>
                            <span v-if="featuredPost.reading_time" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ featuredPost.reading_time }} {{ locale === 'ar' ? 'دقائق قراءة' : 'min read' }}
                            </span>
                        </div>
                        <Link
                            :href="localizedRoute(`/blog/${featuredPost.slug}`)"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-[var(--brand-primary)] text-white font-semibold rounded-full hover:bg-[var(--brand-primary-hover)] transition-all duration-300 shadow-md hover:shadow-lg hover:shadow-[var(--brand-primary)]/25 self-start btn-shimmer"
                        >
                            {{ t('read_more', { default: locale === 'ar' ? 'اقرأ المزيد' : 'Read More' }) }}
                            <svg class="w-4 h-4" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Category Filter + Posts Grid -->
    <section class="relative py-12 lg:py-16 bg-white overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-crosshatch"></div>
        <!-- Floating decorative elements -->
        <div class="absolute top-14 end-14 w-24 h-24 rounded-full bg-gold-primary/5 animate-float"></div>
        <div class="absolute bottom-16 start-8 w-16 h-16 rounded-full bg-gold-primary/6 animate-float-slow"></div>
        <div class="absolute top-1/3 start-[5%] w-10 h-10 rounded-full bg-gold-primary/4 animate-float-delay"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Category Filter Tabs -->
            <div class="flex flex-wrap items-center justify-center gap-3 mb-12" v-scroll-reveal="{ type: 'fade-up' }">
                <button
                    @click="filterByCategory(null)"
                    class="px-5 py-2.5 rounded-full text-sm font-medium transition-all duration-300 border"
                    :class="!activeCategory
                        ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)] shadow-md shadow-[var(--brand-primary)]/25'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-[var(--brand-primary)] hover:text-[var(--brand-primary)]'"
                >
                    {{ locale === 'ar' ? 'الكل' : 'All' }}
                </button>
                <button
                    v-for="category in categories"
                    :key="category.id"
                    @click="filterByCategory(category.id)"
                    class="px-5 py-2.5 rounded-full text-sm font-medium transition-all duration-300 border"
                    :class="activeCategory === category.id
                        ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)] shadow-md shadow-[var(--brand-primary)]/25'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-[var(--brand-primary)] hover:text-[var(--brand-primary)]'"
                >
                    {{ localized(category, 'name') }}
                </button>
            </div>

            <!-- Posts Grid -->
            <div v-if="posts.data && posts.data.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                <article
                    v-for="(post, index) in posts.data"
                    :key="post.id"
                    class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl border border-gray-100 hover:border-[var(--brand-primary)]/30 transition-all duration-300 hover:-translate-y-1 card-hover-lift card-premium"
                >
                    <!-- Post Image -->
                    <Link :href="localizedRoute(`/blog/${post.slug}`)" class="block relative h-52 overflow-hidden img-hover-reveal">
                        <img
                            :src="post.featured_image || '/images/blog-placeholder.jpg'"
                            :alt="localized(post, 'title')"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div v-if="post.category" class="absolute top-3 left-3">
                            <span class="inline-block px-3 py-1 bg-[var(--brand-primary)] text-white text-xs font-semibold rounded-full">
                                {{ localized(post.category, 'name') }}
                            </span>
                        </div>
                    </Link>

                    <!-- Post Content -->
                    <div class="p-6">
                        <div class="flex items-center gap-3 text-xs text-gray-500 mb-3">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(post.published_at) }}
                            </span>
                            <span v-if="post.reading_time" class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ post.reading_time }} {{ locale === 'ar' ? 'د' : 'min' }}
                            </span>
                        </div>

                        <Link :href="localizedRoute(`/blog/${post.slug}`)">
                            <h3 class="text-lg font-bold text-[#3A3A3A] mb-2 line-clamp-2 group-hover:text-[var(--brand-primary)] transition-colors">
                                {{ localized(post, 'title') }}
                            </h3>
                        </Link>

                        <p class="text-gray-600 text-sm line-clamp-2 mb-4 leading-relaxed">
                            {{ localized(post, 'excerpt') }}
                        </p>

                        <Link
                            :href="localizedRoute(`/blog/${post.slug}`)"
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

            <!-- No Posts -->
            <div v-else class="text-center py-16" v-scroll-reveal="{ type: 'fade-up' }">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-500 mb-2">
                    {{ locale === 'ar' ? 'لا توجد مقالات حاليا' : 'No articles found' }}
                </h3>
                <p class="text-gray-400">
                    {{ locale === 'ar' ? 'ترقبوا مقالاتنا القادمة' : 'Stay tuned for upcoming articles' }}
                </p>
            </div>

            <!-- Pagination -->
            <nav v-if="posts.links && posts.links.length > 3" class="mt-12 flex items-center justify-center gap-2" v-scroll-reveal="{ type: 'fade-up' }">
                <template v-for="(link, index) in posts.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg text-sm font-medium transition-all duration-300 border"
                        :class="link.active
                            ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)] shadow-md shadow-[var(--brand-primary)]/25'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-[var(--brand-primary)] hover:text-[var(--brand-primary)]'"
                        v-html="link.label"
                        preserveScroll
                    />
                    <span
                        v-else
                        class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg text-sm font-medium text-gray-300 border border-gray-100 bg-gray-50 cursor-not-allowed"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 lg:py-20 bg-gradient-to-br from-[#3A3A3A] via-[#3a3a3a] to-[#3A3A3A] relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-wave"></div>
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-[var(--brand-primary)] rounded-full blur-3xl animate-float-slow"></div>
        </div>
        <!-- Floating decorative elements -->
        <div class="absolute top-8 start-[15%] w-12 h-12 rounded-full bg-white/5 animate-float"></div>
        <div class="absolute bottom-10 end-[20%] w-16 h-16 rounded-full bg-white/4 animate-float-delay"></div>
        <div class="absolute top-1/3 end-[8%] w-8 h-8 rounded-full bg-white/6 animate-float-slow"></div>
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" v-scroll-reveal="{ type: 'fade-up' }">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4" v-scroll-reveal="{ type: 'blur-in' }">
                {{ locale === 'ar' ? 'هل تحتاجين لاستشارة طبية؟' : 'Need a Medical Consultation?' }}
            </h2>
            <p class="text-gray-300 mb-8 text-lg" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                {{ locale === 'ar' ? 'احجزي موعدك الآن واحصلي على استشارة متخصصة من أطبائنا' : 'Book your appointment now and get a specialized consultation from our doctors' }}
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
