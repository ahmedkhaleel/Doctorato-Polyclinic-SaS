<script setup>
import { ref, onMounted } from 'vue';
import { useLocale } from '@/Composables/useLocale';

const { t, localized, isRtl } = useLocale();

const props = defineProps({
    testimonials: {
        type: Array,
        default: () => [],
    },
});

const swiperInstance = ref(null);
const swiperContainer = ref(null);
const currentIndex = ref(0);
const isLoaded = ref(false);

async function initSwiper() {
    try {
        const { Swiper } = await import('swiper');
        const { Navigation, Pagination, Autoplay } = await import('swiper/modules');
        await import('swiper/css');
        await import('swiper/css/navigation');
        await import('swiper/css/pagination');

        if (!swiperContainer.value) return;

        swiperInstance.value = new Swiper(swiperContainer.value, {
            modules: [Navigation, Pagination, Autoplay],
            slidesPerView: 1,
            spaceBetween: 24,
            loop: props.testimonials.length > 3,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                bulletClass: 'inline-block w-2 h-2 rounded-full bg-gray-300 mx-1 cursor-pointer transition-all duration-300',
                bulletActiveClass: '!bg-[var(--brand-primary)] !w-6',
            },
            navigation: {
                nextEl: '.swiper-button-next-custom',
                prevEl: '.swiper-button-prev-custom',
            },
            breakpoints: {
                640: { slidesPerView: 1 },
                768: { slidesPerView: 2 },
                1024: { slidesPerView: 3 },
            },
            on: {
                slideChange: (swiper) => {
                    currentIndex.value = swiper.activeIndex;
                },
            },
        });

        isLoaded.value = true;
    } catch (e) {
        console.warn('Swiper not available, using fallback display.');
        isLoaded.value = true;
    }
}

onMounted(() => {
    if (props.testimonials.length > 0) {
        initSwiper();
    }
});

function renderStars(rating) {
    return Math.min(Math.max(Math.round(rating || 5), 0), 5);
}
</script>

<template>
    <div class="relative" v-if="testimonials.length > 0">
        <!-- Navigation Arrows -->
        <button
            class="swiper-button-prev-custom absolute top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white rounded-full shadow-md flex items-center justify-center text-gray-600 hover:text-[var(--brand-primary)] hover:shadow-lg transition-all duration-200 -left-4 lg:-left-5"
            :aria-label="t('previous')"
        >
            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <button
            class="swiper-button-next-custom absolute top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white rounded-full shadow-md flex items-center justify-center text-gray-600 hover:text-[var(--brand-primary)] hover:shadow-lg transition-all duration-200 -right-4 lg:-right-5"
            :aria-label="t('next')"
        >
            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Swiper Container -->
        <div ref="swiperContainer" class="swiper overflow-hidden px-2 py-4">
            <div class="swiper-wrapper">
                <div
                    v-for="(testimonial, index) in testimonials"
                    :key="index"
                    class="swiper-slide"
                >
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 h-full flex flex-col relative">
                        <!-- Gold Quote Mark -->
                        <div class="absolute -top-3 start-6">
                            <span class="text-5xl text-[var(--brand-primary)] font-serif leading-none">"</span>
                        </div>

                        <!-- Rating Stars -->
                        <div class="flex items-center gap-0.5 mb-4 pt-4">
                            <svg
                                v-for="star in 5"
                                :key="star"
                                class="w-4 h-4"
                                :class="star <= renderStars(testimonial.rating) ? 'text-[var(--brand-primary)]' : 'text-gray-200'"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>

                        <!-- Quote Text -->
                        <p class="text-gray-600 text-sm leading-relaxed flex-1 mb-6">
                            {{ localized(testimonial, 'review') || testimonial.review_ar }}
                        </p>

                        <!-- Client Info -->
                        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                            <img
                                v-if="testimonial.photo_url"
                                :src="testimonial.photo_url"
                                :alt="localized(testimonial, 'client_name') || testimonial.client_name"
                                class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                            />
                            <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-[var(--brand-primary)]/20 to-[var(--brand-primary)]/5 flex items-center justify-center flex-shrink-0">
                                <span class="text-[var(--brand-primary)] font-bold text-sm">
                                    {{ (localized(testimonial, 'client_name') || testimonial.client_name || '?').charAt(0) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ localized(testimonial, 'client_name') || testimonial.client_name }}
                                </p>
                                <p v-if="testimonial.service" class="text-xs text-[var(--brand-primary)]">
                                    {{ localized(testimonial.service, 'name') || testimonial.service.name_ar }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="swiper-pagination flex items-center justify-center mt-6"></div>
    </div>
</template>
