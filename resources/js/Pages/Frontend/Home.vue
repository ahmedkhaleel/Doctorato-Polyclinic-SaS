<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { useCurrency } from '@/Composables/useCurrency';
import ServiceCard from '@/Components/Frontend/ServiceCard.vue';
import TestimonialCarousel from '@/Components/Frontend/TestimonialCarousel.vue';
import StatCounter from '@/Components/Frontend/StatCounter.vue';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

const props = defineProps({
    featuredServices: Array,
    packageBundles: Array,
    testimonials: Array,
    doctors: Array,
    heroSlides: Array,
    seo: Object,
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink, phone1 } = useSettings();
const { formatCurrency, currencyCode } = useCurrency();

const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

// Hero image slider
const currentSlide = ref(0);

// Fallback images when no slides in database
const fallbackImages = [
    'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=1920&q=80',
    'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=1920&q=80',
    'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1920&q=80',
    'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1920&q=80',
    'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=1920&q=80',
];

const hasDbSlides = computed(() => props.heroSlides && props.heroSlides.length > 0);
const slides = computed(() => {
    if (hasDbSlides.value) {
        return props.heroSlides.map(s => ({
            image: s.image_url,
            title: localized(s, 'title'),
            subtitle: localized(s, 'subtitle'),
            description: localized(s, 'description'),
            buttonText: localized(s, 'button_text'),
            buttonLink: s.button_link,
        }));
    }
    return fallbackImages.map(img => ({ image: img }));
});
const totalSlides = computed(() => slides.value.length);

let slideInterval = null;

onMounted(() => {
    slideInterval = setInterval(() => {
        currentSlide.value = (currentSlide.value + 1) % totalSlides.value;
    }, 5000);
});

onUnmounted(() => {
    if (slideInterval) {
        clearInterval(slideInterval);
    }
});
</script>

<template>
    <FrontendLayout :title="t('home')">
        <SeoHead
            :title="seoTitle"
            :description="seoDescription"
            :keywords="seo?.keywords"
            :image="seo?.image"
        />

        <!-- ============================== -->
        <!-- HERO SECTION                   -->
        <!-- ============================== -->
        <section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden">
            <!-- Background Image Slider -->
            <div class="absolute inset-0">
                <div v-for="(slide, index) in slides" :key="index"
                     class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                     :class="currentSlide === index ? 'opacity-100' : 'opacity-0'">
                    <img :src="slide.image"
                         alt=""
                         class="w-full h-full object-cover"
                         :class="currentSlide === index ? 'hero-slide-active' : 'hero-slide-inactive'" />
                </div>
                <!-- Deep cinematic overlay -->
                <div class="absolute inset-0 bg-gradient-to-b from-[#1E1E1E]/90 via-[#1E1E1E]/50 to-[#1E1E1E]/85"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_transparent_30%,_rgba(30,30,30,0.6)_100%)]"></div>
            </div>
            <!-- Subtle noise texture -->
            <div class="absolute inset-0 pointer-events-none grain-overlay"></div>
            <!-- Animated morphing blobs -->
            <div class="absolute top-[15%] start-[8%] w-64 h-64 bg-[var(--brand-primary)]/[0.04] animate-morph-blob pointer-events-none"></div>
            <div class="absolute bottom-[10%] end-[5%] w-80 h-80 bg-[var(--brand-primary)]/[0.03] animate-morph-blob-slow pointer-events-none"></div>
            <!-- Sparkle particles -->
            <div class="sparkle" style="top: 20%; left: 15%;"></div>
            <div class="sparkle" style="top: 35%; right: 20%;"></div>
            <div class="sparkle" style="top: 60%; left: 25%;"></div>
            <div class="sparkle" style="top: 75%; right: 30%;"></div>
            <div class="sparkle" style="top: 45%; left: 70%;"></div>
            <!-- Bottom gold accent -->
            <div class="absolute bottom-0 left-0 w-full h-[2px] gold-gradient opacity-60"></div>

            <!-- Slide indicators -->
            <div class="absolute bottom-16 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2.5">
                <button v-for="(_, index) in slides" :key="index"
                        @click="currentSlide = index"
                        class="h-1 rounded-full transition-all duration-500"
                        :class="currentSlide === index
                            ? 'bg-[var(--brand-primary)] w-8 animate-glow-ring'
                            : 'bg-white/30 w-4 hover:bg-white/50'"
                        :aria-label="isRtl ? 'انتقال للشريحة ' + (index + 1) : 'Go to slide ' + (index + 1)">
                </button>
            </div>

            <!-- Content -->
            <div class="relative z-10 text-center px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
                <!-- Subtle top decorative line -->
                <div class="flex items-center justify-center gap-5 mb-8" v-scroll-reveal="{ type: 'blur-in', delay: 100 }">
                    <span class="block w-16 h-px bg-gradient-to-r from-transparent to-[var(--brand-primary)]/70"></span>
                    <span class="text-[var(--brand-primary)] text-xs font-semibold uppercase tracking-[0.35em]">
                        {{ isRtl ? 'عيادة الجلدية والتجميل' : 'Derma & Aesthetic Clinic' }}
                    </span>
                    <span class="block w-16 h-px bg-gradient-to-l from-transparent to-[var(--brand-primary)]/70"></span>
                </div>

                <!-- Dynamic title from current slide (or default) -->
                <h1 class="text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-bold mb-6 tracking-wider" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                    <template v-if="slides[currentSlide]?.title">
                        <span class="text-gold-shimmer">{{ slides[currentSlide].title }}</span>
                    </template>
                    <template v-else>
                        <span class="text-gold-shimmer">{{ isRtl ? 'أورا ديرما' : 'AURA' }}</span>
                        <span v-if="!isRtl" class="text-white"> Derma</span>
                    </template>
                </h1>

                <!-- Dynamic subtitle -->
                <p class="text-lg sm:text-xl md:text-2xl text-white/80 mb-3 leading-relaxed max-w-2xl mx-auto"
                   style="font-family: var(--font-en-heading), var(--font-ar-heading), sans-serif;"
                   v-scroll-reveal="{ type: 'blur-in', delay: 350 }">
                    {{ slides[currentSlide]?.subtitle
                        || (isRtl
                            ? 'حيث تلتقي التكنولوجيا المتقدمة بالجمال الطبيعي'
                            : 'Where Advanced Technology Meets Natural Beauty')
                    }}
                </p>

                <!-- Dynamic description -->
                <p class="text-sm sm:text-base text-white/40 mb-12 max-w-lg mx-auto" v-scroll-reveal="{ type: 'blur-in', delay: 450 }">
                    {{ slides[currentSlide]?.description
                        || (isRtl
                            ? 'اكتشفي عالم العناية بالبشرة والجمال مع أحدث التقنيات وأفضل الأطباء'
                            : 'Discover the world of skincare and beauty with the latest technologies and top dermatologists')
                    }}
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4" v-scroll-reveal="{ type: 'bounce-in', delay: 550 }">
                    <!-- Dynamic button from slide or default booking button -->
                    <Link v-if="slides[currentSlide]?.buttonLink && slides[currentSlide]?.buttonText"
                          :href="slides[currentSlide].buttonLink.startsWith('http') ? slides[currentSlide].buttonLink : localizedRoute(slides[currentSlide].buttonLink)"
                          class="btn-shimmer group inline-flex items-center gap-2.5 px-9 py-4 gold-gradient text-white font-semibold rounded-full
                                 transition-all duration-300 shadow-lg shadow-[var(--brand-primary)]/20
                                 hover:shadow-xl hover:shadow-[var(--brand-primary)]/30 hover:scale-[1.03] active:scale-[0.98]
                                 text-sm sm:text-base">
                        <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ slides[currentSlide].buttonText }}
                    </Link>
                    <Link v-else
                          :href="localizedRoute('/booking')"
                          class="btn-shimmer group inline-flex items-center gap-2.5 px-9 py-4 gold-gradient text-white font-semibold rounded-full
                                 transition-all duration-300 shadow-lg shadow-[var(--brand-primary)]/20
                                 hover:shadow-xl hover:shadow-[var(--brand-primary)]/30 hover:scale-[1.03] active:scale-[0.98]
                                 text-sm sm:text-base">
                        <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ isRtl ? 'احجزي موعدك' : 'Book Appointment' }}
                    </Link>
                    <a :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                       class="btn-shimmer group inline-flex items-center gap-2.5 px-9 py-4 bg-white/5 backdrop-blur-sm border border-white/20 text-white
                              font-semibold rounded-full hover:bg-white/10 hover:border-[var(--brand-primary)]/40 transition-all duration-300
                              text-sm sm:text-base">
                        <svg class="w-5 h-5 text-[#25D366] transition-transform duration-300 group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        {{ isRtl ? 'استشارة مجانية' : 'Free Consultation' }}
                    </a>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 opacity-40">
                <span class="text-white text-[10px] uppercase tracking-[0.2em]">{{ isRtl ? 'اكتشفي' : 'Scroll' }}</span>
                <div class="w-px h-8 bg-gradient-to-b from-white/60 to-transparent animate-pulse"></div>
            </div>
        </section>


        <!-- ============================== -->
        <!-- ABOUT PREVIEW SECTION          -->
        <!-- ============================== -->
        <section class="py-20 md:py-28 bg-[#FDFAF6] relative overflow-hidden">
            <!-- Subtle texture -->
            <div class="absolute inset-0 pointer-events-none opacity-[0.02]"
                 style="background-image: radial-gradient(circle, var(--brand-primary) 1px, transparent 1px); background-size: 28px 28px;"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center"
                     :class="{ 'lg:grid-flow-dense': isRtl }">

                    <!-- Image -->
                    <div class="relative" :class="{ 'lg:col-start-2': isRtl }" v-scroll-reveal="{ type: 'flip-left', delay: 100 }">
                        <div class="relative w-full max-w-lg mx-auto">
                            <!-- Background decorative shape -->
                            <div class="absolute -top-6 -end-6 w-full h-full rounded-2xl bg-gradient-to-br from-[var(--brand-primary)]/15 to-[var(--brand-primary)]/5"></div>
                            <!-- Main image -->
                            <div class="relative w-full h-[420px] sm:h-[480px] rounded-2xl overflow-hidden shadow-2xl shadow-[var(--brand-primary)]/10">
                                <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800&q=80"
                                     alt="AURA Derma Clinic"
                                     class="w-full h-full object-cover transition-transform duration-700 hover:scale-105" />
                                <!-- Bottom gradient overlay -->
                                <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-[#1E1E1E]/40 to-transparent"></div>
                            </div>
                            <!-- Experience badge -->
                            <div class="absolute -bottom-4 -start-4 bg-white rounded-xl shadow-lg shadow-black/5 p-4 flex items-center gap-3 border border-[var(--brand-primary)]/10">
                                <div class="w-12 h-12 gold-gradient rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[#1E1E1E] font-bold text-sm">{{ isRtl ? 'جودة معتمدة' : 'Certified Quality' }}</p>
                                    <p class="text-[#8C8279] text-xs">{{ isRtl ? 'أعلى المعايير الطبية' : 'Highest Medical Standards' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Text -->
                    <div :class="{ 'lg:col-start-1 lg:row-start-1': isRtl }" v-scroll-reveal="{ type: 'fade-left', delay: 200 }">
                        <!-- Section label -->
                        <div class="flex items-center gap-3 mb-5">
                            <span class="block w-10 h-px bg-[var(--brand-primary)]"></span>
                            <span class="text-[var(--brand-primary)] text-xs font-semibold uppercase tracking-[0.25em]">
                                {{ isRtl ? 'من نحن' : 'About Us' }}
                            </span>
                        </div>

                        <h2 class="text-3xl sm:text-4xl md:text-[2.75rem] font-bold text-[#1E1E1E] mb-6 leading-tight">
                            {{ isRtl
                                ? 'عيادة أورا ديرما للجلدية والتجميل'
                                : 'AURA Derma Dermatology & Cosmetic Clinic'
                            }}
                        </h2>

                        <p class="text-[#6B5B4E] text-lg leading-[1.85] mb-5">
                            {{ isRtl
                                ? 'نؤمن في عيادة أورا ديرما بأن كل بشرة تستحق العناية الفائقة. نقدم أحدث تقنيات العلاج والتجميل في بيئة راقية ومريحة، مع فريق طبي متخصص يضع راحتك وجمالك في المقام الأول.'
                                : 'At AURA Derma Clinic, we believe every skin deserves exceptional care. We offer the latest treatment and cosmetic technologies in an elegant, comfortable environment, with a specialized medical team that puts your comfort and beauty first.'
                            }}
                        </p>
                        <p class="text-[#8C8279] leading-relaxed mb-8">
                            {{ isRtl
                                ? 'تأسست العيادة على يد نخبة من أطباء الجلدية والتجميل، لتكون وجهتك الأولى للعناية بالبشرة في مصر.'
                                : 'Founded by an elite team of dermatology and cosmetic specialists, the clinic is your premier destination for skincare in Egypt.'
                            }}
                        </p>

                        <Link :href="localizedRoute('/about')"
                              class="btn-shimmer inline-flex items-center gap-2.5 px-7 py-3 bg-[#1E1E1E] text-white font-semibold
                                     rounded-full hover:bg-[#3A3A3A] transition-all duration-300 shadow-md hover:shadow-lg text-sm group">
                            {{ isRtl ? 'اعرفي المزيد' : 'Learn More' }}
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1"
                                 :class="{ 'rotate-180 group-hover:-translate-x-1 group-hover:translate-x-0': isRtl }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </section>


        <!-- ============================== -->
        <!-- FEATURED SERVICES SECTION      -->
        <!-- ============================== -->
        <section class="py-20 md:py-28 bg-white relative overflow-hidden" v-if="featuredServices && featuredServices.length">
            <!-- Subtle top border -->
            <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/20 to-transparent"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <!-- Section Header -->
                <div class="text-center mb-14 md:mb-20" v-scroll-reveal="{ type: 'blur-in' }">
                    <div class="flex items-center justify-center gap-3 mb-5">
                        <span class="block w-10 h-px bg-[var(--brand-primary)]"></span>
                        <span class="text-[var(--brand-primary)] text-xs font-semibold uppercase tracking-[0.25em]">
                            {{ isRtl ? 'خدماتنا' : 'Our Services' }}
                        </span>
                        <span class="block w-10 h-px bg-[var(--brand-primary)]"></span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl md:text-[2.75rem] font-bold text-[#1E1E1E] mb-5">
                        {{ isRtl ? 'خدمات مميزة نقدمها لك' : 'Featured Services We Offer' }}
                    </h2>
                    <p class="text-[#8C8279] text-lg max-w-2xl mx-auto leading-relaxed">
                        {{ isRtl
                            ? 'نقدم مجموعة شاملة من خدمات العناية بالبشرة والتجميل بأحدث التقنيات العالمية'
                            : 'We offer a comprehensive range of skincare and cosmetic services using the latest global technologies'
                        }}
                    </p>
                </div>

                <!-- Services Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8"
                     v-scroll-reveal="{ type: 'stagger', staggerDelay: 120 }">
                    <div v-for="(service, index) in featuredServices" :key="service.id"
                         class="card-premium rounded-2xl">
                        <ServiceCard :service="service" />
                    </div>
                </div>

                <!-- View All Button -->
                <div class="text-center mt-14" v-scroll-reveal="{ type: 'bounce-in', delay: 200 }">
                    <Link :href="localizedRoute('/services')"
                          class="btn-shimmer group inline-flex items-center gap-2.5 px-8 py-3.5 gold-gradient text-white font-semibold
                                 rounded-full transition-all duration-300 shadow-md shadow-[var(--brand-primary)]/15
                                 hover:shadow-lg hover:shadow-[var(--brand-primary)]/25 hover:scale-[1.03] active:scale-[0.98]">
                        {{ isRtl ? 'عرض جميع الخدمات' : 'View All Services' }}
                        <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" :class="{ 'rotate-180 group-hover:-translate-x-1 group-hover:translate-x-0': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </section>


        <!-- ============================== -->
        <!-- WHY CHOOSE US SECTION          -->
        <!-- ============================== -->
        <section class="py-20 md:py-28 bg-[#FDFAF6] relative overflow-hidden">
            <!-- Top border -->
            <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/20 to-transparent"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <!-- Section Header -->
                <div class="text-center mb-14 md:mb-20" v-scroll-reveal="{ type: 'blur-in' }">
                    <div class="flex items-center justify-center gap-3 mb-5">
                        <span class="block w-10 h-px bg-[var(--brand-primary)]"></span>
                        <span class="text-[var(--brand-primary)] text-xs font-semibold uppercase tracking-[0.25em]">
                            {{ isRtl ? 'لماذا أورا' : 'Why AURA' }}
                        </span>
                        <span class="block w-10 h-px bg-[var(--brand-primary)]"></span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl md:text-[2.75rem] font-bold text-[#1E1E1E]">
                        {{ isRtl ? 'لماذا تختارين عيادة أورا؟' : 'Why Choose AURA Clinic?' }}
                    </h2>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-5"
                     v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                    <!-- Feature 1: Latest Technology -->
                    <div class="group text-center p-6 sm:p-8 rounded-2xl bg-white border border-gray-100 hover:border-[var(--brand-primary)]/20
                                transition-all duration-500 hover:shadow-xl hover:shadow-[var(--brand-primary)]/[0.07] hover:-translate-y-1"
                         v-tilt="{ max: 6 }">
                        <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-gradient-to-br from-[var(--brand-primary)]/10 to-[var(--brand-primary)]/5
                                    flex items-center justify-center group-hover:from-[var(--brand-primary)] group-hover:to-[var(--brand-primary-hover)]
                                    transition-all duration-400">
                            <svg class="w-8 h-8 text-[var(--brand-primary)] group-hover:text-white transition-colors duration-400"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-[#1E1E1E] mb-2.5">
                            {{ isRtl ? 'أحدث التقنيات' : 'Latest Technology' }}
                        </h3>
                        <p class="text-[#8C8279] text-sm leading-relaxed">
                            {{ isRtl
                                ? 'نستخدم أحدث الأجهزة والتقنيات المعتمدة عالمياً لضمان أفضل النتائج'
                                : 'We use the latest globally certified devices and technologies to ensure the best results'
                            }}
                        </p>
                    </div>

                    <!-- Feature 2: Expert Team -->
                    <div class="group text-center p-6 sm:p-8 rounded-2xl bg-white border border-gray-100 hover:border-[var(--brand-primary)]/20
                                transition-all duration-500 hover:shadow-xl hover:shadow-[var(--brand-primary)]/[0.07] hover:-translate-y-1"
                         v-tilt="{ max: 6 }">
                        <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-gradient-to-br from-[var(--brand-primary)]/10 to-[var(--brand-primary)]/5
                                    flex items-center justify-center group-hover:from-[var(--brand-primary)] group-hover:to-[var(--brand-primary-hover)]
                                    transition-all duration-400">
                            <svg class="w-8 h-8 text-[var(--brand-primary)] group-hover:text-white transition-colors duration-400"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-[#1E1E1E] mb-2.5">
                            {{ isRtl ? 'فريق متخصص' : 'Expert Team' }}
                        </h3>
                        <p class="text-[#8C8279] text-sm leading-relaxed">
                            {{ isRtl
                                ? 'نخبة من أمهر أطباء الجلدية والتجميل بخبرات واسعة وشهادات معتمدة'
                                : 'An elite group of the most skilled dermatology and cosmetic doctors with extensive experience'
                            }}
                        </p>
                    </div>

                    <!-- Feature 3: Female Staff -->
                    <div class="group text-center p-6 sm:p-8 rounded-2xl bg-white border border-gray-100 hover:border-[var(--brand-primary)]/20
                                transition-all duration-500 hover:shadow-xl hover:shadow-[var(--brand-primary)]/[0.07] hover:-translate-y-1"
                         v-tilt="{ max: 6 }">
                        <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-gradient-to-br from-[var(--brand-primary)]/10 to-[var(--brand-primary)]/5
                                    flex items-center justify-center group-hover:from-[var(--brand-primary)] group-hover:to-[var(--brand-primary-hover)]
                                    transition-all duration-400">
                            <svg class="w-8 h-8 text-[var(--brand-primary)] group-hover:text-white transition-colors duration-400"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-[#1E1E1E] mb-2.5">
                            {{ isRtl ? 'طاقم نسائي' : 'Female Staff' }}
                        </h3>
                        <p class="text-[#8C8279] text-sm leading-relaxed">
                            {{ isRtl
                                ? 'طاقم نسائي متكامل لراحتك وخصوصيتك مع أعلى معايير الخدمة'
                                : 'A fully dedicated female staff for your comfort and privacy with the highest service standards'
                            }}
                        </p>
                    </div>

                    <!-- Feature 4: Guaranteed Results -->
                    <div class="group text-center p-6 sm:p-8 rounded-2xl bg-white border border-gray-100 hover:border-[var(--brand-primary)]/20
                                transition-all duration-500 hover:shadow-xl hover:shadow-[var(--brand-primary)]/[0.07] hover:-translate-y-1"
                         v-tilt="{ max: 6 }">
                        <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-gradient-to-br from-[var(--brand-primary)]/10 to-[var(--brand-primary)]/5
                                    flex items-center justify-center group-hover:from-[var(--brand-primary)] group-hover:to-[var(--brand-primary-hover)]
                                    transition-all duration-400">
                            <svg class="w-8 h-8 text-[var(--brand-primary)] group-hover:text-white transition-colors duration-400"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-[#1E1E1E] mb-2.5">
                            {{ isRtl ? 'نتائج مضمونة' : 'Guaranteed Results' }}
                        </h3>
                        <p class="text-[#8C8279] text-sm leading-relaxed">
                            {{ isRtl
                                ? 'نتائج مثبتة ومضمونة مع متابعة مستمرة بعد كل جلسة علاجية'
                                : 'Proven and guaranteed results with continuous follow-up after each treatment session'
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </section>


        <!-- ============================== -->
        <!-- PACKAGE BUNDLES SECTION        -->
        <!-- ============================== -->
        <section class="py-20 md:py-28 relative overflow-hidden" v-if="packageBundles && packageBundles.length">
            <!-- Rich dark background -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#1E1E1E] via-[#2A2A2A] to-[#1E1E1E]"></div>
            <!-- Elegant gold radial glow -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(196,162,101,0.15)_0%,_transparent_50%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,_rgba(196,162,101,0.1)_0%,_transparent_50%)]"></div>
            <!-- Geometric pattern overlay -->
            <div class="absolute inset-0 pointer-events-none opacity-[0.03]"
                 style="background-image: repeating-linear-gradient(45deg, var(--brand-primary) 0px, var(--brand-primary) 1px, transparent 1px, transparent 40px), repeating-linear-gradient(-45deg, var(--brand-primary) 0px, var(--brand-primary) 1px, transparent 1px, transparent 40px);"></div>
            <!-- Top & bottom gold borders -->
            <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/50 to-transparent"></div>
            <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/50 to-transparent"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-20 start-[10%] w-24 h-24 rounded-full border border-[var(--brand-primary)]/10 animate-float-slow"></div>
            <div class="absolute bottom-20 end-[8%] w-32 h-32 rounded-full border border-[var(--brand-primary)]/8 animate-float"></div>
            <div class="absolute top-1/2 end-[15%] w-16 h-16 rounded-full bg-[var(--brand-primary)]/5 animate-float-delay"></div>
            <div class="absolute top-[30%] start-[5%] w-12 h-12 rounded-full bg-[var(--brand-primary)]/5 animate-float"></div>
            <!-- Decorative corner accents -->
            <div class="absolute top-8 start-8 w-20 h-20 border-t-2 border-s-2 border-[var(--brand-primary)]/20 rounded-tl-lg"></div>
            <div class="absolute bottom-8 end-8 w-20 h-20 border-b-2 border-e-2 border-[var(--brand-primary)]/20 rounded-br-lg"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <!-- Section Header -->
                <div class="text-center mb-14 md:mb-20" v-scroll-reveal="{ type: 'blur-in' }">
                    <div class="flex items-center justify-center gap-3 mb-5">
                        <span class="block w-10 h-px bg-gradient-to-r from-transparent to-[var(--brand-primary)]/70"></span>
                        <span class="text-[var(--brand-primary)] text-xs font-semibold uppercase tracking-[0.25em]">
                            {{ t('packages') }}
                        </span>
                        <span class="block w-10 h-px bg-gradient-to-l from-transparent to-[var(--brand-primary)]/70"></span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl md:text-[2.75rem] font-bold text-white mb-5">
                        {{ t('package_bundles_title') }}
                    </h2>
                    <p class="text-white/45 text-lg max-w-2xl mx-auto leading-relaxed">
                        {{ t('packages_hero_subtitle') }}
                    </p>
                </div>

                <!-- Packages Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8"
                     v-scroll-reveal="{ type: 'stagger', staggerDelay: 150 }">
                    <div v-for="(bundle, index) in packageBundles" :key="bundle.id"
                         class="card-premium rounded-2xl">
                        <!-- Package Card -->
                        <div class="group bg-white/[0.06] backdrop-blur-sm rounded-2xl border border-white/10
                                    overflow-hidden hover:border-[var(--brand-primary)]/30 transition-all duration-500
                                    hover:shadow-xl hover:shadow-[var(--brand-primary)]/[0.08]">
                            <!-- Image -->
                            <div class="relative h-52 overflow-hidden">
                                <img v-if="bundle.image_url" :src="bundle.image_url" :alt="localized(bundle, 'name')"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                                <div v-else class="w-full h-full bg-gradient-to-br from-[var(--brand-primary)]/20 to-[var(--brand-primary)]/5 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-[var(--brand-primary)]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <!-- Overlay gradient -->
                                <div class="absolute inset-0 bg-gradient-to-t from-[#1E1E1E]/80 via-transparent to-transparent"></div>
                                <!-- Savings badge -->
                                <div v-if="Number(bundle.savings) > 0" class="absolute top-4 start-4 bg-green-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                                    {{ t('save_amount') }} {{ formatCurrency(bundle.savings) }}
                                </div>
                                <!-- Services count badge -->
                                <div class="absolute bottom-4 start-4 flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-xs font-medium px-3 py-1.5 rounded-full">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    {{ bundle.services?.length || 0 }} {{ t('services_included') }}
                                </div>
                            </div>
                            <!-- Content -->
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-white mb-2 group-hover:text-[var(--brand-primary)] transition-colors duration-300">
                                    {{ localized(bundle, 'name') }}
                                </h3>
                                <p v-if="localized(bundle, 'description')" class="text-white/40 text-sm mb-5 line-clamp-2 leading-relaxed">
                                    {{ localized(bundle, 'description') }}
                                </p>
                                <!-- Pricing -->
                                <div class="flex items-end gap-3 mb-5">
                                    <span class="text-2xl font-bold text-[var(--brand-primary)]">{{ formatCurrency(bundle.total_price) }}</span>
                                    <span v-if="Number(bundle.original_price) > Number(bundle.total_price)"
                                          class="text-sm text-white/30 line-through pb-0.5 ms-auto">
                                        {{ formatCurrency(bundle.original_price) }}
                                    </span>
                                </div>
                                <!-- CTA -->
                                <Link :href="localizedRoute(`/package-bundles/${bundle.id}`)"
                                      class="block w-full text-center py-3 px-6 rounded-xl text-white font-semibold text-sm
                                             bg-[var(--brand-primary)] hover:bg-[#B3914F] transition-all duration-300 hover:shadow-lg hover:shadow-[var(--brand-primary)]/20">
                                    {{ t('view_package') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View All Link -->
                <div class="text-center mt-12" v-scroll-reveal="{ type: 'bounce-in', delay: 200 }">
                    <Link :href="localizedRoute('/package-bundles')"
                          class="btn-shimmer inline-flex items-center gap-2 px-8 py-3.5 border-2 border-[var(--brand-primary)] text-[var(--brand-primary)]
                                 font-semibold rounded-full hover:bg-[var(--brand-primary)] hover:text-white transition-all duration-300
                                 shadow-md hover:shadow-lg hover:shadow-[var(--brand-primary)]/20 text-lg">
                        {{ t('view_all_packages') }}
                        <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1"
                             :class="{ 'rotate-180': isRtl }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </section>


        <!-- ============================== -->
        <!-- TESTIMONIALS SECTION           -->
        <!-- ============================== -->
        <section class="py-20 md:py-28 bg-white relative overflow-hidden" v-if="testimonials && testimonials.length">
            <!-- Top border -->
            <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/20 to-transparent"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <!-- Section Header -->
                <div class="text-center mb-14 md:mb-20" v-scroll-reveal="{ type: 'blur-in' }">
                    <div class="flex items-center justify-center gap-3 mb-5">
                        <span class="block w-10 h-px bg-[var(--brand-primary)]"></span>
                        <span class="text-[var(--brand-primary)] text-xs font-semibold uppercase tracking-[0.25em]">
                            {{ isRtl ? 'آراء العملاء' : 'Testimonials' }}
                        </span>
                        <span class="block w-10 h-px bg-[var(--brand-primary)]"></span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl md:text-[2.75rem] font-bold text-[#1E1E1E] mb-5">
                        {{ isRtl ? 'ماذا يقول عملاؤنا' : 'What Our Clients Say' }}
                    </h2>
                    <p class="text-[#8C8279] text-lg max-w-2xl mx-auto leading-relaxed">
                        {{ isRtl
                            ? 'تجارب حقيقية من عملائنا الكرام تعكس جودة خدماتنا والتزامنا بالتميز'
                            : 'Real experiences from our valued clients reflecting our service quality and commitment to excellence'
                        }}
                    </p>
                </div>

                <!-- Carousel -->
                <div v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                    <TestimonialCarousel :testimonials="testimonials" />
                </div>
            </div>
        </section>


        <!-- ============================== -->
        <!-- STATISTICS COUNTER SECTION     -->
        <!-- ============================== -->
        <section class="py-12 md:py-16 relative overflow-hidden">
            <!-- Elegant dark background -->
            <div class="absolute inset-0 bg-gradient-to-r from-[#1E1E1E] via-[#262626] to-[#1E1E1E]"></div>
            <!-- Subtle gold ambient glow -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(196,162,101,0.06)_0%,_transparent_60%)]"></div>
            <!-- Top & bottom gold lines -->
            <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/40 to-transparent"></div>
            <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/40 to-transparent"></div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-0"
                     v-scroll-reveal="{ type: 'stagger', staggerDelay: 120 }">

                    <!-- Happy Clients -->
                    <div class="stat-card group">
                        <div class="stat-card-inner">
                            <div class="stat-icon-wrapper">
                                <div class="stat-icon-bg"></div>
                                <svg class="w-6 h-6 text-[var(--brand-primary)] relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                                </svg>
                            </div>
                            <div class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-1">
                                <StatCounter :end="1000" suffix="+" />
                            </div>
                            <p class="text-[var(--brand-primary)]/70 text-xs sm:text-sm font-medium tracking-wide">
                                {{ isRtl ? 'عميلة سعيدة' : 'Happy Clients' }}
                            </p>
                        </div>
                    </div>

                    <!-- Doctors -->
                    <div class="stat-card group">
                        <div class="stat-card-inner">
                            <div class="stat-icon-wrapper">
                                <div class="stat-icon-bg"></div>
                                <svg class="w-6 h-6 text-[var(--brand-primary)] relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 14.25V16.5m0 0v2.25m0-2.25h2.25M18 16.5h-2.25" />
                                </svg>
                            </div>
                            <div class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-1">
                                <StatCounter :end="10" suffix="+" />
                            </div>
                            <p class="text-[var(--brand-primary)]/70 text-xs sm:text-sm font-medium tracking-wide">
                                {{ isRtl ? 'طبيب متخصص' : 'Specialist Doctors' }}
                            </p>
                        </div>
                    </div>

                    <!-- Services -->
                    <div class="stat-card group">
                        <div class="stat-card-inner">
                            <div class="stat-icon-wrapper">
                                <div class="stat-icon-bg"></div>
                                <svg class="w-6 h-6 text-[var(--brand-primary)] relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                            </div>
                            <div class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-1">
                                <StatCounter :end="20" suffix="+" />
                            </div>
                            <p class="text-[var(--brand-primary)]/70 text-xs sm:text-sm font-medium tracking-wide">
                                {{ isRtl ? 'خدمة متنوعة' : 'Diverse Services' }}
                            </p>
                        </div>
                    </div>

                    <!-- Devices -->
                    <div class="stat-card group">
                        <div class="stat-card-inner">
                            <div class="stat-icon-wrapper">
                                <div class="stat-icon-bg"></div>
                                <svg class="w-6 h-6 text-[var(--brand-primary)] relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                                </svg>
                            </div>
                            <div class="text-3xl sm:text-4xl font-bold text-white mt-3 mb-1">
                                <StatCounter :end="8" suffix="+" />
                            </div>
                            <p class="text-[var(--brand-primary)]/70 text-xs sm:text-sm font-medium tracking-wide">
                                {{ isRtl ? 'جهاز حديث' : 'Modern Devices' }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </section>


        <!-- ============================== -->
        <!-- CTA BANNER SECTION             -->
        <!-- ============================== -->
        <section class="relative py-14 md:py-20 overflow-hidden">
            <!-- Warm beige-gold gradient background -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#F7F1E8] via-[#F0E6D6] to-[#E8DCCB]"></div>
            <!-- Subtle gold radial glow -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_center,_rgba(196,162,101,0.12)_0%,_transparent_50%)]"></div>
            <!-- Top gold accent line -->
            <div class="absolute top-0 left-0 w-full h-[2px] gold-gradient opacity-80"></div>
            <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/25 to-transparent"></div>
            <!-- Decorative corner frames -->
            <div class="absolute top-6 start-6 w-16 h-16 border-t-2 border-s-2 border-[var(--brand-primary)]/15 rounded-tl-xl hidden md:block"></div>
            <div class="absolute bottom-6 end-6 w-16 h-16 border-b-2 border-e-2 border-[var(--brand-primary)]/15 rounded-br-xl hidden md:block"></div>

            <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <!-- Section label -->
                <div class="flex items-center justify-center gap-3 mb-5" v-scroll-reveal="{ type: 'blur-in' }">
                    <span class="block w-10 h-px bg-[var(--brand-primary)]/50"></span>
                    <span class="text-[var(--brand-primary)] text-xs font-semibold uppercase tracking-[0.25em]">
                        {{ isRtl ? 'تواصلي معنا' : 'Get in Touch' }}
                    </span>
                    <span class="block w-10 h-px bg-[var(--brand-primary)]/50"></span>
                </div>

                <!-- Main heading -->
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-[#1E1E1E] mb-4 leading-tight" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                    {{ isRtl ? 'بشرتك تستحق الأفضل..' : 'Your skin deserves the best..' }}
                    <span class="gold-text-gradient">
                        {{ isRtl ? ' ابدئي رحلتك اليوم!' : ' Start your journey today!' }}
                    </span>
                </h2>

                <!-- Subtitle -->
                <p class="text-[#6B5B4E] text-base md:text-lg mb-8 max-w-xl mx-auto leading-relaxed" v-scroll-reveal="{ type: 'blur-in', delay: 150 }">
                    {{ isRtl
                        ? 'تواصلي معنا الآن واحصلي على استشارة مجانية مع أفضل أطبائنا'
                        : 'Contact us now and get a free consultation with our best doctors'
                    }}
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4" v-scroll-reveal="{ type: 'bounce-in', delay: 200 }">
                    <!-- WhatsApp button -->
                    <a :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                       class="btn-shimmer group inline-flex items-center gap-2.5 px-8 py-3.5 bg-[#25D366] text-white font-semibold
                              rounded-full transition-all duration-300 shadow-md shadow-[#25D366]/20
                              hover:shadow-lg hover:shadow-[#25D366]/30 hover:scale-[1.02] active:scale-[0.98] text-sm sm:text-base">
                        <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        {{ isRtl ? 'تواصلي عبر واتساب' : 'Chat on WhatsApp' }}
                    </a>
                    <!-- Call button -->
                    <a :href="`tel:${phone1}`"
                       class="btn-shimmer group inline-flex items-center gap-2.5 px-8 py-3.5 bg-[#1E1E1E] text-white
                              font-semibold rounded-full transition-all duration-300 shadow-md shadow-[#1E1E1E]/15
                              hover:shadow-lg hover:shadow-[#1E1E1E]/25 hover:scale-[1.02] active:scale-[0.98] text-sm sm:text-base">
                        <svg class="w-5 h-5 transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        {{ isRtl ? 'اتصلي بنا' : 'Call Us' }}
                    </a>
                    <!-- Booking button -->
                    <Link :href="localizedRoute('/booking')"
                          class="btn-shimmer group inline-flex items-center gap-2.5 px-8 py-3.5 border-2 border-[var(--brand-primary)] text-[var(--brand-primary)]
                                 font-semibold rounded-full hover:bg-[var(--brand-primary)] hover:text-white transition-all duration-300
                                 text-sm sm:text-base">
                        <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ isRtl ? 'احجزي موعدك' : 'Book Now' }}
                    </Link>
                </div>

                <!-- Trust badges -->
                <div class="flex items-center justify-center gap-5 sm:gap-7 mt-8 pt-6 border-t border-[var(--brand-primary)]/15" v-scroll-reveal="{ type: 'fade-up', delay: 300 }">
                    <div class="flex items-center gap-1.5 text-[#8C8279] text-xs">
                        <svg class="w-3.5 h-3.5 text-[var(--brand-primary)]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622A11.99 11.99 0 0020.402 6a11.959 11.959 0 00-8.402-3.785z"/>
                        </svg>
                        {{ isRtl ? 'استشارة مجانية' : 'Free Consultation' }}
                    </div>
                    <div class="w-1 h-1 rounded-full bg-[var(--brand-primary)]/25"></div>
                    <div class="flex items-center gap-1.5 text-[#8C8279] text-xs">
                        <svg class="w-3.5 h-3.5 text-[var(--brand-primary)]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                        </svg>
                        {{ isRtl ? 'أطباء معتمدون' : 'Certified Doctors' }}
                    </div>
                    <div class="w-1 h-1 rounded-full bg-[var(--brand-primary)]/25 hidden sm:block"></div>
                    <div class="hidden sm:flex items-center gap-1.5 text-[#8C8279] text-xs">
                        <svg class="w-3.5 h-3.5 text-[var(--brand-primary)]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                        </svg>
                        {{ isRtl ? 'نتائج مضمونة' : 'Guaranteed Results' }}
                    </div>
                </div>
            </div>
        </section>

    </FrontendLayout>
</template>

<style scoped>
/* Ken Burns zoom effect for active slides */
@keyframes kenBurns {
    0% {
        transform: scale(1);
    }
    100% {
        transform: scale(1.1);
    }
}

.hero-slide-active {
    animation: kenBurns 5s ease-out forwards;
}

.hero-slide-inactive {
    transform: scale(1);
}
</style>
