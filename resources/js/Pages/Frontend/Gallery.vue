<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    galleryItems: {
        type: Array,
        default: () => [],
    },
    seo: Object,
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();

const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

// Filter categories
const filterCategories = computed(() => [
    { key: 'all', label_ar: 'الكل', label_en: 'All' },
    { key: 'clinic', label_ar: 'العيادة', label_en: 'Clinic' },
    { key: 'before_after', label_ar: 'قبل وبعد', label_en: 'Before & After' },
    { key: 'team', label_ar: 'فريق العمل', label_en: 'Team' },
    { key: 'equipment', label_ar: 'الأجهزة', label_en: 'Equipment' },
]);

// Active filter
const activeFilter = ref('all');

function setFilter(key) {
    activeFilter.value = key;
}

// Filtered gallery items
const filteredItems = computed(() => {
    if (activeFilter.value === 'all') {
        return props.galleryItems;
    }
    return props.galleryItems.filter(
        (item) => item.category === activeFilter.value || item.type === activeFilter.value
    );
});

// Separate video items
const videoItems = computed(() => {
    return props.galleryItems.filter((item) => item.video_url);
});

// Lightbox state
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

// Filter out videos for lightbox navigation
const lightboxItems = computed(() => {
    return filteredItems.value.filter((item) => !item.video_url);
});

function openLightbox(item) {
    if (item.video_url) return;
    const idx = lightboxItems.value.findIndex((i) => i.id === item.id);
    if (idx >= 0) {
        lightboxIndex.value = idx;
    } else {
        lightboxIndex.value = 0;
    }
    lightboxOpen.value = true;
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    lightboxOpen.value = false;
    document.body.style.overflow = '';
}

function nextImage() {
    if (lightboxIndex.value < lightboxItems.value.length - 1) {
        lightboxIndex.value++;
    } else {
        lightboxIndex.value = 0;
    }
}

function prevImage() {
    if (lightboxIndex.value > 0) {
        lightboxIndex.value--;
    } else {
        lightboxIndex.value = lightboxItems.value.length - 1;
    }
}

// Handle keyboard navigation
function handleKeydown(e) {
    if (!lightboxOpen.value) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') isRtl.value ? prevImage() : nextImage();
    if (e.key === 'ArrowLeft') isRtl.value ? nextImage() : prevImage();
}

// Register keyboard listener
if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleKeydown);
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

        <!-- Page Hero -->
        <section class="relative h-64 bg-charcoal overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-black-deep/90 via-charcoal/80 to-gold-dark/30"></div>
            <div class="absolute inset-0 pointer-events-none texture-diamond"></div>
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 end-10 w-40 h-40 border border-gold-primary/30 rounded-full"></div>
                <div class="absolute bottom-5 start-20 w-60 h-60 border border-gold-light/20 rounded-full"></div>
            </div>
            <!-- Floating decorative elements -->
            <div class="absolute top-8 start-8 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-12 end-16 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/2 end-1/4 w-10 h-10 rounded-full bg-gold-light/5 animate-float-delay"></div>
            <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-4">
                <div class="mb-4" v-scroll-reveal="{ type: 'zoom-in' }">
                    <div class="w-14 h-14 mx-auto rounded-full bg-gold-primary/20 flex items-center justify-center">
                        <svg class="w-7 h-7 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <h1
                    class="text-4xl md:text-5xl font-bold text-white mb-4"
                    v-scroll-reveal="{ type: 'blur-in', duration: 800 }"
                >
                    {{ locale === 'ar' ? 'معرض الصور' : 'Photo Gallery' }}
                </h1>
                <p
                    class="text-lg text-beige/80 max-w-2xl"
                    v-scroll-reveal="{ type: 'blur-in', delay: 200 }"
                >
                    {{ locale === 'ar'
                        ? 'استعرض مرافق عيادتنا ونتائج علاجاتنا المتميزة'
                        : 'Browse our clinic facilities and outstanding treatment results'
                    }}
                </p>
                <div class="mt-4 w-20 h-1 bg-gold-primary rounded-full" v-scroll-reveal="{ type: 'zoom-in', delay: 400 }"></div>
            </div>
        </section>

        <!-- Filter Tabs -->
        <section class="sticky top-0 z-30 bg-white shadow-sm border-b border-beige/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2 py-4 overflow-x-auto scrollbar-hide">
                    <button
                        v-for="cat in filterCategories"
                        :key="cat.key"
                        class="flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium transition-all duration-300"
                        :class="
                            activeFilter === cat.key
                                ? 'gold-gradient text-white shadow-md'
                                : 'bg-off-white text-charcoal/70 hover:bg-beige hover:text-charcoal'
                        "
                        @click="setFilter(cat.key)"
                    >
                        {{ locale === 'ar' ? cat.label_ar : cat.label_en }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Gallery Grid -->
        <section class="relative py-12 md:py-20 bg-off-white overflow-hidden">
            <!-- Subtle diagonal line pattern -->
            <div class="absolute inset-0 pointer-events-none texture-diagonal"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-10 start-10 w-24 h-24 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-20 end-10 w-16 h-16 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/3 end-20 w-12 h-12 rounded-full bg-gold-light/5 animate-float-delay"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Results Count -->
                <div class="mb-8 text-charcoal/50 text-sm" v-scroll-reveal="{ type: 'fade-up' }">
                    {{ locale === 'ar'
                        ? `عرض ${filteredItems.length} صورة`
                        : `Showing ${filteredItems.length} items`
                    }}
                </div>

                <!-- Grid -->
                <TransitionGroup
                    name="gallery"
                    tag="div"
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-5"
                    v-scroll-reveal="{ type: 'stagger', staggerDelay: 80 }"
                >
                    <div
                        v-for="(item, index) in filteredItems"
                        :key="item.id || index"
                        class="group relative overflow-hidden rounded-xl shadow-sm cursor-pointer bg-white card-hover-lift img-hover-reveal"
                        :class="[
                            item.featured ? 'md:col-span-2 md:row-span-2' : '',
                            item.video_url ? '' : 'aspect-square',
                        ]"
                        @click="openLightbox(item)"
                    >
                        <!-- Image -->
                        <img
                            v-if="!item.video_url"
                            :src="item.image || item.url || item.path"
                            :alt="localized(item, 'caption') || localized(item, 'title') || ''"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                            loading="lazy"
                        />

                        <!-- Video Thumbnail -->
                        <div
                            v-else
                            class="relative aspect-video"
                        >
                            <img
                                v-if="item.thumbnail || item.image"
                                :src="item.thumbnail || item.image"
                                :alt="localized(item, 'caption') || ''"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            />
                            <div v-else class="w-full h-full bg-charcoal flex items-center justify-center">
                                <svg class="w-12 h-12 text-gold-primary" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                            <!-- Play icon overlay -->
                            <div class="absolute inset-0 flex items-center justify-center bg-black-deep/30">
                                <div class="w-14 h-14 rounded-full bg-gold-primary/90 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    <svg class="w-7 h-7 text-white ms-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Hover Overlay (images only) -->
                        <div
                            v-if="!item.video_url"
                            class="absolute inset-0 bg-gradient-to-t from-black-deep/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500"
                        >
                            <div class="absolute bottom-0 start-0 end-0 p-4">
                                <div class="flex items-end justify-between">
                                    <div class="flex-1">
                                        <p
                                            v-if="localized(item, 'caption') || localized(item, 'title')"
                                            class="text-white font-medium text-sm mb-1"
                                        >
                                            {{ localized(item, 'caption') || localized(item, 'title') }}
                                        </p>
                                        <p
                                            v-if="item.category"
                                            class="text-white/60 text-xs"
                                        >
                                            {{
                                                item.category === 'before_after'
                                                    ? (locale === 'ar' ? 'قبل وبعد' : 'Before & After')
                                                    : item.category === 'clinic'
                                                    ? (locale === 'ar' ? 'العيادة' : 'Clinic')
                                                    : item.category === 'team'
                                                    ? (locale === 'ar' ? 'فريق العمل' : 'Team')
                                                    : item.category === 'equipment'
                                                    ? (locale === 'ar' ? 'الأجهزة' : 'Equipment')
                                                    : item.category
                                            }}
                                        </p>
                                    </div>
                                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 ms-2">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Before & After Badge -->
                        <div
                            v-if="item.category === 'before_after' || item.type === 'before_after' || item.is_before_after"
                            class="absolute top-3 start-3 z-10"
                        >
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold bg-gold-primary text-white rounded-md shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                {{ locale === 'ar' ? 'قبل وبعد' : 'B&A' }}
                            </span>
                        </div>
                    </div>
                </TransitionGroup>

                <!-- Empty State -->
                <div
                    v-if="filteredItems.length === 0"
                    class="text-center py-20"
                    v-scroll-reveal="{ type: 'fade-up' }"
                >
                    <div class="w-24 h-24 rounded-full bg-beige flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gold-primary/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-charcoal mb-2">
                        {{ locale === 'ar' ? 'لا توجد صور في هذا التصنيف' : 'No images in this category' }}
                    </h3>
                    <p class="text-charcoal/50">
                        {{ locale === 'ar' ? 'جرب تصنيف آخر لعرض المزيد من الصور' : 'Try another category to see more photos' }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Video Section -->
        <section
            v-if="videoItems.length > 0"
            class="relative py-12 md:py-20 bg-white overflow-hidden"
        >
            <!-- Subtle dot pattern -->
            <div class="absolute inset-0 pointer-events-none texture-dots"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-16 end-8 w-20 h-20 rounded-full bg-gold-primary/5 animate-float-slow"></div>
            <div class="absolute bottom-10 start-16 w-14 h-14 rounded-full bg-gold-primary/8 animate-float"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                    <span class="inline-block px-4 py-1.5 bg-gold-primary/10 text-gold-primary text-sm font-medium rounded-full mb-4">
                        <svg class="w-4 h-4 inline me-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        {{ locale === 'ar' ? 'فيديو' : 'Videos' }}
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-charcoal mb-3" v-scroll-reveal="{ type: 'blur-in', delay: 100 }">
                        {{ locale === 'ar' ? 'مقاطع الفيديو' : 'Video Gallery' }}
                    </h2>
                    <div class="flex items-center justify-center gap-3">
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                        <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" v-scroll-reveal="{ type: 'stagger', staggerDelay: 80 }">
                    <div
                        v-for="(video, index) in videoItems"
                        :key="video.id || `video-${index}`"
                        class="rounded-2xl overflow-hidden shadow-md bg-off-white border border-beige/30 card-hover-lift"
                    >
                        <div class="aspect-video">
                            <iframe
                                v-if="video.video_url && (video.video_url.includes('youtube') || video.video_url.includes('youtu.be'))"
                                :src="video.video_url.replace('watch?v=', 'embed/').replace('youtu.be/', 'www.youtube.com/embed/')"
                                class="w-full h-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                            <iframe
                                v-else-if="video.video_url && video.video_url.includes('vimeo')"
                                :src="video.video_url.replace('vimeo.com/', 'player.vimeo.com/video/')"
                                class="w-full h-full"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                            <video
                                v-else
                                :src="video.video_url"
                                class="w-full h-full object-cover"
                                controls
                                preload="metadata"
                            ></video>
                        </div>
                        <div v-if="localized(video, 'caption') || localized(video, 'title')" class="p-4">
                            <p class="text-charcoal font-medium text-sm">
                                {{ localized(video, 'caption') || localized(video, 'title') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Lightbox -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="lightboxOpen && lightboxItems.length > 0"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black-deep/95 p-4"
                    @click.self="closeLightbox"
                >
                    <!-- Close -->
                    <button
                        class="absolute top-4 end-4 z-10 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                        @click="closeLightbox"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Prev -->
                    <button
                        v-if="lightboxItems.length > 1"
                        class="absolute start-4 z-10 w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                        @click="prevImage"
                    >
                        <svg class="w-6 h-6" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Image -->
                    <div class="max-w-5xl max-h-[85vh] w-full">
                        <Transition name="slide" mode="out-in">
                            <img
                                :key="lightboxIndex"
                                :src="lightboxItems[lightboxIndex]?.image || lightboxItems[lightboxIndex]?.url || lightboxItems[lightboxIndex]?.path"
                                :alt="localized(lightboxItems[lightboxIndex], 'caption') || ''"
                                class="w-full h-full object-contain rounded-lg max-h-[75vh]"
                            />
                        </Transition>
                        <div class="text-center mt-4">
                            <p
                                v-if="localized(lightboxItems[lightboxIndex], 'caption') || localized(lightboxItems[lightboxIndex], 'title')"
                                class="text-white/80 mb-1"
                            >
                                {{ localized(lightboxItems[lightboxIndex], 'caption') || localized(lightboxItems[lightboxIndex], 'title') }}
                            </p>
                            <p class="text-white/40 text-sm">
                                {{ lightboxIndex + 1 }} / {{ lightboxItems.length }}
                            </p>
                        </div>
                    </div>

                    <!-- Next -->
                    <button
                        v-if="lightboxItems.length > 1"
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
    </div>
</template>

<style scoped>
/* Lightbox transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Image slide transition in lightbox */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.25s ease;
}

.slide-enter-from {
    opacity: 0;
    transform: translateX(30px);
}

.slide-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

/* Gallery grid filtering transitions */
.gallery-enter-active {
    transition: all 0.4s ease-out;
}

.gallery-leave-active {
    transition: all 0.3s ease-in;
    position: absolute;
}

.gallery-enter-from {
    opacity: 0;
    transform: scale(0.85);
}

.gallery-leave-to {
    opacity: 0;
    transform: scale(0.85);
}

.gallery-move {
    transition: transform 0.4s ease;
}

/* Hide scrollbar on filter tabs */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
