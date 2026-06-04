<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { sanitizeHtml } from '@/Composables/useSanitize';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    service: {
        type: Object,
        required: true,
    },
    relatedServices: {
        type: Array,
        default: () => [],
    },
    seo: Object,
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();

const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

// FAQ accordion state
const openFaqIndex = ref(null);

function toggleFaq(index) {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
}

// Parse benefits from text (split by newlines)
const benefitsList = computed(() => {
    const raw = localized(props.service, 'benefits');
    if (!raw) return [];
    return raw
        .split('\n')
        .map((line) => line.replace(/^[-*]\s*/, '').trim())
        .filter((line) => line.length > 0);
});

// Parse expected results
const expectedResults = computed(() => {
    const raw = localized(props.service, 'results') || localized(props.service, 'expected_results');
    if (!raw) return [];
    return raw
        .split('\n')
        .map((line) => line.replace(/^[-*]\s*/, '').trim())
        .filter((line) => line.length > 0);
});

// Service gallery images
const galleryImages = computed(() => props.service.gallery || props.service.images || []);

// Service FAQs
const faqs = computed(() => props.service.faqs || []);

// Lightbox state
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

function openLightbox(index) {
    lightboxIndex.value = index;
    lightboxOpen.value = true;
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    lightboxOpen.value = false;
    document.body.style.overflow = '';
}

function nextImage() {
    if (lightboxIndex.value < galleryImages.value.length - 1) {
        lightboxIndex.value++;
    } else {
        lightboxIndex.value = 0;
    }
}

function prevImage() {
    if (lightboxIndex.value > 0) {
        lightboxIndex.value--;
    } else {
        lightboxIndex.value = galleryImages.value.length - 1;
    }
}
</script>

<template>
    <div>
        <SeoHead
            :title="seoTitle"
            :description="seoDescription"
            :keywords="seo?.keywords"
            :image="seo?.image"
        />

        <!-- Breadcrumb -->
        <nav class="bg-off-white border-b border-beige/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <ol class="flex items-center text-sm flex-wrap gap-1">
                    <li>
                        <Link
                            :href="localizedRoute('/')"
                            class="text-gold-primary hover:text-gold-dark transition-colors duration-200"
                        >
                            {{ locale === 'ar' ? 'الرئيسية' : 'Home' }}
                        </Link>
                    </li>
                    <li class="text-charcoal/30 mx-1">
                        <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </li>
                    <li>
                        <Link
                            :href="localizedRoute('/services')"
                            class="text-gold-primary hover:text-gold-dark transition-colors duration-200"
                        >
                            {{ locale === 'ar' ? 'خدماتنا' : 'Services' }}
                        </Link>
                    </li>
                    <li class="text-charcoal/30 mx-1">
                        <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </li>
                    <li class="text-charcoal/70 font-medium">
                        {{ localized(service, 'name') }}
                    </li>
                </ol>
            </div>
        </nav>

        <!-- Service Hero -->
        <section class="relative h-72 md:h-80 bg-charcoal overflow-hidden">
            <div
                v-if="service.banner_image || service.featured_image || service.image"
                class="absolute inset-0"
            >
                <img
                    :src="service.banner_image || service.featured_image || service.image"
                    :alt="localized(service, 'name')"
                    class="w-full h-full object-cover opacity-40"
                />
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black-deep/90 via-charcoal/60 to-transparent"></div>
            <div class="absolute inset-0 pointer-events-none texture-diamond"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-8 end-[12%] w-16 h-16 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-14 start-[10%] w-12 h-12 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/3 start-[25%] w-8 h-8 rounded-full bg-gold-primary/4 animate-float-delay"></div>
            <div class="relative z-10 flex flex-col items-center justify-end h-full text-center px-4 pb-10">
                <div
                    v-if="service.category"
                    class="mb-3"
                    v-scroll-reveal="{ type: 'fade-down' }"
                >
                    <span class="inline-block px-4 py-1.5 bg-gold-primary/90 text-white text-sm font-medium rounded-full">
                        {{ localized(service.category, 'name') }}
                    </span>
                </div>
                <h1
                    class="text-3xl md:text-5xl font-bold text-white mb-2"
                    v-scroll-reveal="{ type: 'fade-up', duration: 800 }"
                >
                    {{ localized(service, 'name') }}
                </h1>
                <p
                    v-if="localized(service, 'short_desc')"
                    class="text-beige/70 text-lg max-w-2xl mt-2"
                    v-scroll-reveal="{ type: 'fade-up', delay: 150 }"
                >
                    {{ localized(service, 'short_desc') }}
                </p>
            </div>
        </section>

        <!-- Service Content: Two-Column Layout -->
        <section class="relative py-12 md:py-20 bg-off-white overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-dots"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-10 start-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-20 end-10 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-10 lg:gap-14">
                    <!-- Main Content (2/3) -->
                    <div class="lg:w-2/3" v-scroll-reveal="{ type: 'fade-up' }">
                        <div
                            v-if="localized(service, 'full_desc') || localized(service, 'description')"
                            class="prose-content bg-white rounded-2xl p-6 md:p-10 shadow-sm border border-beige/30"
                        >
                            <div v-html="sanitizeHtml(localized(service, 'full_desc') || localized(service, 'description'))"></div>
                        </div>

                        <!-- How It Works -->
                        <div
                            v-if="localized(service, 'how_it_works')"
                            class="mt-10 bg-white rounded-2xl p-6 md:p-10 shadow-sm border border-beige/30"
                            v-scroll-reveal="{ type: 'fade-up' }"
                        >
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 rounded-full bg-gold-primary/10 flex items-center justify-center me-3">
                                    <svg class="w-5 h-5 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-charcoal">
                                    {{ locale === 'ar' ? 'كيف يتم العلاج' : 'How It Works' }}
                                </h2>
                            </div>
                            <div class="prose-content text-charcoal/70 leading-relaxed" v-html="sanitizeHtml(localized(service, 'how_it_works'))"></div>
                        </div>
                    </div>

                    <!-- Sidebar (1/3) -->
                    <div class="lg:w-1/3 space-y-6" v-scroll-reveal="{ type: 'fade-up', delay: 150 }">
                        <!-- Quick Info Card -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-beige/30 card-hover-lift card-premium" v-scroll-reveal="{ type: 'fade-left', delay: 200 }">
                            <h3 class="text-lg font-bold text-charcoal mb-5 flex items-center">
                                <svg class="w-5 h-5 text-gold-primary me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ locale === 'ar' ? 'معلومات سريعة' : 'Quick Info' }}
                            </h3>
                            <div class="space-y-4">
                                <!-- Category -->
                                <div v-if="service.category" class="flex items-center justify-between py-3 border-b border-beige/30">
                                    <span class="text-charcoal/60 text-sm">{{ locale === 'ar' ? 'التصنيف' : 'Category' }}</span>
                                    <span class="text-charcoal font-medium text-sm">{{ localized(service.category, 'name') }}</span>
                                </div>
                                <!-- Sessions -->
                                <div v-if="service.sessions_count" class="flex items-center justify-between py-3 border-b border-beige/30">
                                    <span class="text-charcoal/60 text-sm">{{ locale === 'ar' ? 'عدد الجلسات' : 'Sessions' }}</span>
                                    <span class="text-charcoal font-medium text-sm">{{ service.sessions_count }}</span>
                                </div>
                                <!-- Duration -->
                                <div v-if="service.duration" class="flex items-center justify-between py-3 border-b border-beige/30">
                                    <span class="text-charcoal/60 text-sm">{{ locale === 'ar' ? 'مدة الجلسة' : 'Duration' }}</span>
                                    <span class="text-charcoal font-medium text-sm">{{ service.duration }}</span>
                                </div>
                                <!-- Recovery -->
                                <div v-if="service.recovery_time" class="flex items-center justify-between py-3">
                                    <span class="text-charcoal/60 text-sm">{{ locale === 'ar' ? 'فترة التعافي' : 'Recovery' }}</span>
                                    <span class="text-charcoal font-medium text-sm">{{ service.recovery_time }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Book This Service -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-beige/30 card-hover-lift card-premium" v-scroll-reveal="{ type: 'fade-left', delay: 300 }">
                            <h3 class="text-lg font-bold text-charcoal mb-4">
                                {{ locale === 'ar' ? 'احجز هذه الخدمة' : 'Book This Service' }}
                            </h3>
                            <p class="text-sm text-charcoal/60 mb-5">
                                {{ locale === 'ar'
                                    ? 'احجز موعدك الآن واستمتع بأفضل رعاية طبية'
                                    : 'Book your appointment now and enjoy the best medical care'
                                }}
                            </p>
                            <Link
                                :href="localizedRoute('/booking')"
                                class="w-full inline-flex items-center justify-center px-6 py-3 gold-gradient text-white font-bold rounded-full hover:opacity-90 transition-opacity duration-300 shadow-md mb-3 btn-shimmer"
                            >
                                <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ locale === 'ar' ? 'احجز الآن' : 'Book Now' }}
                            </Link>
                            <a
                                :href="whatsappLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full inline-flex items-center justify-center px-6 py-3 bg-emerald-600 text-white font-bold rounded-full hover:bg-emerald-700 transition-colors duration-300"
                            >
                                <svg class="w-5 h-5 me-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                                {{ locale === 'ar' ? 'واتساب' : 'WhatsApp' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits -->
        <section
            v-if="benefitsList.length > 0"
            class="relative py-12 md:py-20 bg-white overflow-hidden"
        >
            <div class="absolute inset-0 pointer-events-none texture-diagonal-reverse"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-12 end-12 w-18 h-18 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-16 start-16 w-12 h-12 rounded-full bg-gold-primary/6 animate-float-slow"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                        <h2 class="text-3xl font-bold text-charcoal mb-3">
                            {{ locale === 'ar' ? 'المزايا والفوائد' : 'Benefits' }}
                        </h2>
                        <div class="flex items-center justify-center gap-3">
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                            <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                        <div
                            v-for="(benefit, index) in benefitsList"
                            :key="index"
                            class="flex items-start gap-3 p-4 bg-off-white rounded-xl card-hover-lift card-premium"
                        >
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gold-primary/10 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-charcoal/80 leading-relaxed">{{ benefit }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Expected Results -->
        <section
            v-if="expectedResults.length > 0"
            class="relative py-12 md:py-20 bg-off-white overflow-hidden"
        >
            <div class="absolute inset-0 pointer-events-none texture-crosshatch"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-8 start-8 w-16 h-16 rounded-full bg-gold-primary/5 animate-float-slow"></div>
            <div class="absolute bottom-12 end-16 w-20 h-20 rounded-full bg-gold-primary/4 animate-float"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                        <div class="flex items-center justify-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-gold-primary/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-3xl font-bold text-charcoal mb-3">
                            {{ locale === 'ar' ? 'النتائج المتوقعة' : 'Expected Results' }}
                        </h2>
                        <div class="flex items-center justify-center gap-3">
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                            <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                        </div>
                    </div>
                    <div class="space-y-4" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                        <div
                            v-for="(result, index) in expectedResults"
                            :key="index"
                            class="flex items-start gap-4 p-5 bg-white rounded-xl shadow-sm border border-beige/30 card-hover-lift card-premium"
                        >
                            <div class="flex-shrink-0 w-8 h-8 rounded-full gold-gradient flex items-center justify-center text-white font-bold text-sm">
                                {{ index + 1 }}
                            </div>
                            <span class="text-charcoal/80 leading-relaxed pt-1">{{ result }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Before & After Gallery -->
        <section
            v-if="galleryImages.length > 0"
            class="relative py-12 md:py-20 bg-white overflow-hidden"
        >
            <div class="absolute inset-0 pointer-events-none texture-dots-lg"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-10 end-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-14 start-14 w-14 h-14 rounded-full bg-gold-primary/6 animate-float-slow"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                    <h2 class="text-3xl font-bold text-charcoal mb-3">
                        {{ locale === 'ar' ? 'معرض الصور' : 'Gallery' }}
                    </h2>
                    <div class="flex items-center justify-center gap-3">
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                        <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                    </div>
                </div>

                <!-- Gallery Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                    <div
                        v-for="(image, index) in galleryImages"
                        :key="index"
                        class="group relative aspect-square rounded-xl overflow-hidden cursor-pointer shadow-sm img-hover-reveal"
                        @click="openLightbox(index)"
                    >
                        <img
                            :src="image.url || image.path || image"
                            :alt="image.caption || localized(service, 'name')"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                        />
                        <div class="absolute inset-0 bg-black-deep/0 group-hover:bg-black-deep/50 transition-all duration-300 flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 text-center">
                                <svg class="w-8 h-8 text-white mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                </svg>
                                <span
                                    v-if="image.caption"
                                    class="text-white text-sm px-2"
                                >
                                    {{ image.caption }}
                                </span>
                            </div>
                        </div>
                        <!-- Before/After Badge -->
                        <div
                            v-if="image.type === 'before_after' || image.is_before_after"
                            class="absolute top-2 start-2"
                        >
                            <span class="inline-block px-2 py-1 text-xs font-medium bg-gold-primary text-white rounded-md">
                                {{ locale === 'ar' ? 'قبل وبعد' : 'Before & After' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lightbox -->
            <Teleport to="body">
                <Transition name="fade">
                    <div
                        v-if="lightboxOpen"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black-deep/95 p-4"
                        @click.self="closeLightbox"
                    >
                        <!-- Close Button -->
                        <button
                            class="absolute top-4 end-4 z-10 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                            :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'"
                            @click="closeLightbox"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Prev Button -->
                        <button
                            v-if="galleryImages.length > 1"
                            class="absolute start-4 z-10 w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                            @click="prevImage"
                        >
                            <svg class="w-6 h-6" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <!-- Image -->
                        <div class="max-w-4xl max-h-[85vh] w-full">
                            <img
                                :src="galleryImages[lightboxIndex]?.url || galleryImages[lightboxIndex]?.path || galleryImages[lightboxIndex]"
                                :alt="galleryImages[lightboxIndex]?.caption || ''"
                                class="w-full h-full object-contain rounded-lg"
                            />
                            <p
                                v-if="galleryImages[lightboxIndex]?.caption"
                                class="text-white/80 text-center mt-4"
                            >
                                {{ galleryImages[lightboxIndex].caption }}
                            </p>
                            <p class="text-white/40 text-center text-sm mt-2">
                                {{ lightboxIndex + 1 }} / {{ galleryImages.length }}
                            </p>
                        </div>

                        <!-- Next Button -->
                        <button
                            v-if="galleryImages.length > 1"
                            class="absolute end-4 z-10 w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                            @click="nextImage"
                        >
                            <svg class="w-6 h-6" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </Transition>
            </Teleport>
        </section>

        <!-- Service FAQs -->
        <section
            v-if="faqs.length > 0"
            class="relative py-12 md:py-20 bg-off-white overflow-hidden"
        >
            <div class="absolute inset-0 pointer-events-none texture-diagonal"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-10 start-10 w-16 h-16 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-20 end-8 w-12 h-12 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                    <h2 class="text-3xl font-bold text-charcoal mb-3">
                        {{ locale === 'ar' ? 'الأسئلة الشائعة' : 'Frequently Asked Questions' }}
                    </h2>
                    <div class="flex items-center justify-center gap-3">
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                        <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                    </div>
                </div>

                <div class="space-y-3" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="faq.id || index"
                        class="bg-white rounded-xl overflow-hidden shadow-sm border border-beige/30 card-hover-lift card-premium"
                    >
                        <button
                            class="w-full flex items-center justify-between p-5 text-start hover:bg-off-white/50 transition-colors duration-200"
                            @click="toggleFaq(index)"
                        >
                            <span class="font-semibold text-charcoal pe-4">
                                {{ localized(faq, 'question') }}
                            </span>
                            <svg
                                class="w-5 h-5 text-gold-primary flex-shrink-0 transition-transform duration-300"
                                :class="{ 'rotate-180': openFaqIndex === index }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-96 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-96 opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div
                                v-if="openFaqIndex === index"
                                class="overflow-hidden"
                            >
                                <div class="px-5 pb-5 text-charcoal/70 leading-relaxed border-t border-beige/30 pt-4">
                                    {{ localized(faq, 'answer') }}
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- Related Services -->
        <section
            v-if="relatedServices.length > 0"
            class="relative py-12 md:py-20 bg-white overflow-hidden"
        >
            <div class="absolute inset-0 pointer-events-none texture-wave"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-12 end-12 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-16 start-8 w-16 h-16 rounded-full bg-gold-primary/6 animate-float-slow"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                    <h2 class="text-3xl font-bold text-charcoal mb-3">
                        {{ locale === 'ar' ? 'خدمات ذات صلة' : 'Related Services' }}
                    </h2>
                    <div class="flex items-center justify-center gap-3">
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                        <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                    <Link
                        v-for="(related, index) in relatedServices.slice(0, 4)"
                        :key="related.id"
                        :href="localizedRoute(`/services/${related.slug}`)"
                        class="group bg-off-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden border border-beige/50 hover:border-gold-primary/30 card-hover-lift card-premium"
                    >
                        <div class="relative h-40 bg-gradient-to-br from-beige to-off-white overflow-hidden img-hover-reveal">
                            <img
                                v-if="related.image || related.featured_image"
                                :src="related.image || related.featured_image"
                                :alt="localized(related, 'name')"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                            />
                            <div
                                v-else
                                class="flex items-center justify-center w-full h-full"
                            >
                                <div class="w-16 h-16 rounded-full bg-gold-primary/10 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="text-base font-bold text-charcoal group-hover:text-gold-primary transition-colors duration-300 mb-1">
                                {{ localized(related, 'name') }}
                            </h3>
                            <p class="text-sm text-charcoal/60 line-clamp-2">
                                {{ localized(related, 'short_desc') || localized(related, 'description') }}
                            </p>
                            <span class="inline-flex items-center mt-3 text-gold-primary text-sm font-medium">
                                {{ locale === 'ar' ? 'اعرف المزيد' : 'Learn More' }}
                                <svg
                                    class="w-3.5 h-3.5 transition-transform duration-300 group-hover:translate-x-1 rtl:group-hover:-translate-x-1"
                                    :class="isRtl ? 'me-1 rotate-180' : 'ms-1'"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA - Book Now -->
        <section class="relative py-16 md:py-20 overflow-hidden">
            <div class="absolute inset-0 gold-gradient"></div>
            <div class="absolute inset-0 pointer-events-none texture-crosshatch"></div>
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 start-0 w-96 h-96 bg-white rounded-full -translate-x-1/2 -translate-y-1/2 animate-float-slow"></div>
                <div class="absolute bottom-0 end-0 w-72 h-72 bg-white rounded-full translate-x-1/3 translate-y-1/3 animate-float"></div>
            </div>
            <!-- Floating decorative elements -->
            <div class="absolute top-10 end-[18%] w-10 h-10 rounded-full bg-white/5 animate-float-delay"></div>
            <div class="absolute bottom-8 start-[12%] w-14 h-14 rounded-full bg-white/8 animate-float"></div>
            <div class="relative z-10 max-w-4xl mx-auto px-4 text-center">
                <h2
                    class="text-3xl md:text-4xl font-bold text-white mb-4"
                    v-scroll-reveal="{ type: 'blur-in' }"
                >
                    {{ locale === 'ar'
                        ? 'هل تريد الاستفسار عن هذه الخدمة؟'
                        : 'Want to Learn More About This Service?'
                    }}
                </h2>
                <p
                    class="text-white/80 text-lg mb-8 max-w-2xl mx-auto"
                    v-scroll-reveal="{ type: 'fade-up', delay: 100 }"
                >
                    {{ locale === 'ar'
                        ? 'تواصل معنا الآن للحصول على استشارة مجانية وحجز موعدك'
                        : 'Contact us now for a free consultation and book your appointment'
                    }}
                </p>
                <div
                    class="flex flex-col sm:flex-row items-center justify-center gap-4"
                    v-scroll-reveal="{ type: 'fade-up', delay: 200 }"
                >
                    <Link
                        :href="localizedRoute('/booking')"
                        class="inline-flex items-center px-8 py-3.5 bg-white text-gold-primary font-bold rounded-full hover:bg-off-white transition-colors duration-300 shadow-lg hover:shadow-xl btn-shimmer"
                    >
                        <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ locale === 'ar' ? 'احجز موعدك الآن' : 'Book Your Appointment' }}
                    </Link>
                    <a
                        :href="whatsappLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center px-8 py-3.5 bg-transparent border-2 border-white text-white font-bold rounded-full hover:bg-white/10 transition-colors duration-300 btn-shimmer"
                    >
                        <svg class="w-5 h-5 me-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        {{ locale === 'ar' ? 'تواصل عبر واتساب' : 'WhatsApp Us' }}
                    </a>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
