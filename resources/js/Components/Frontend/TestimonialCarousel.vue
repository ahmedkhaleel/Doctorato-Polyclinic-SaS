<script setup>
import { ref, onMounted } from 'vue';
import { useLocale } from '@/Composables/useLocale';

const { t, localized, isRtl } = useLocale();

const props = defineProps({
    testimonials: { type: Array, default: () => [] },
});

const swiperInstance = ref(null);
const swiperContainer = ref(null);
const isLoaded = ref(false);

async function initSwiper() {
    try {
        const { Swiper } = await import('swiper');
        const { Navigation, Pagination, Autoplay } = await import('swiper/modules');
        await import('swiper/css');

        if (!swiperContainer.value) return;

        swiperInstance.value = new Swiper(swiperContainer.value, {
            modules: [Navigation, Pagination, Autoplay],
            slidesPerView: 1,
            spaceBetween: 20,
            loop: props.testimonials.length > 2,
            autoplay: { delay: 5000, disableOnInteraction: false },
            pagination: {
                el: '.testi-pagination',
                clickable: true,
                bulletClass: 'inline-block w-2 h-2 rounded-full bg-[#1B365D]/15 mx-1 cursor-pointer transition-all duration-300',
                bulletActiveClass: '!bg-[#C4A265] !w-7',
            },
            navigation: {
                nextEl: '.testi-next',
                prevEl: '.testi-prev',
            },
            breakpoints: {
                768: { slidesPerView: 2, spaceBetween: 20 },
                1024: { slidesPerView: 3, spaceBetween: 24 },
            },
        });
        isLoaded.value = true;
    } catch (e) {
        isLoaded.value = true;
    }
}

onMounted(() => { if (props.testimonials.length > 0) initSwiper(); });

function stars(rating) { return Math.min(Math.max(Math.round(rating || 5), 0), 5); }
</script>

<template>
    <div class="relative" v-if="testimonials.length > 0">
        <!-- Nav arrows — hidden on mobile -->
        <button class="testi-prev hidden md:flex absolute top-1/2 -translate-y-1/2 z-10 -start-5 w-11 h-11 bg-[#1B365D] rounded-full
                       items-center justify-center text-white shadow-lg hover:bg-[#264573] hover:scale-110 transition-all duration-300">
            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <button class="testi-next hidden md:flex absolute top-1/2 -translate-y-1/2 z-10 -end-5 w-11 h-11 bg-[#1B365D] rounded-full
                       items-center justify-center text-white shadow-lg hover:bg-[#264573] hover:scale-110 transition-all duration-300">
            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Swiper -->
        <div ref="swiperContainer" class="swiper overflow-hidden">
            <div class="swiper-wrapper pb-2">
                <div v-for="(item, index) in testimonials" :key="index" class="swiper-slide h-auto">
                    <!-- Card -->
                    <div class="group relative bg-white rounded-2xl p-6 h-full flex flex-col
                                border border-gray-100 hover:border-[#C4A265]/20
                                shadow-sm hover:shadow-lg transition-all duration-500">
                        <!-- Top accent -->
                        <div class="absolute top-0 inset-x-6 h-0.5 bg-gradient-to-r from-transparent via-[#C4A265]/0 to-transparent
                                    group-hover:via-[#C4A265]/40 transition-all duration-500 rounded-full"></div>

                        <!-- Quote icon -->
                        <div class="w-10 h-10 rounded-lg bg-[#1B365D]/5 flex items-center justify-center mb-4
                                    group-hover:bg-[#C4A265]/10 transition-colors duration-500">
                            <svg class="w-5 h-5 text-[#C4A265]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151C7.563 6.068 6 8.789 6 11h4.017v10H0z"/>
                            </svg>
                        </div>

                        <!-- Stars -->
                        <div class="flex items-center gap-0.5 mb-3">
                            <svg v-for="s in 5" :key="s" class="w-4 h-4"
                                 :class="s <= stars(item.rating) ? 'text-[#C4A265]' : 'text-gray-200'"
                                 fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>

                        <!-- Review text -->
                        <p class="text-gray-600 text-sm leading-relaxed flex-1 mb-5 line-clamp-4">
                            {{ localized(item, 'review') || item.review_ar }}
                        </p>

                        <!-- Client info -->
                        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                            <div v-if="item.photo_url"
                                 class="w-10 h-10 rounded-full overflow-hidden ring-2 ring-[#C4A265]/20 flex-shrink-0">
                                <img :src="item.photo_url" :alt="localized(item, 'client_name')" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-[#1B365D] to-[#264573]
                                        flex items-center justify-center flex-shrink-0 ring-2 ring-[#C4A265]/20">
                                <span class="text-white font-bold text-sm">
                                    {{ (localized(item, 'client_name') || item.client_name || '?').charAt(0) }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-[#1B365D] truncate">
                                    {{ localized(item, 'client_name') || item.client_name }}
                                </p>
                                <p v-if="item.service" class="text-xs text-[#C4A265] truncate">
                                    {{ localized(item.service, 'name') || item.service.name_ar }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="testi-pagination flex items-center justify-center mt-8"></div>
    </div>
</template>
