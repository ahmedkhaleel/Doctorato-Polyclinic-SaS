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
    medicalSpecialties: Array,
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

// Hero slider
const currentSlide = ref(0);
const isTransitioning = ref(false);

// 5 built-in slides with unique content
const builtInSlides = [
    {
        image: 'https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=1920&q=80',
        titleAr: 'رعايتك الصحية', titleAccentAr: 'أولويتنا', titleEn: 'Your Health is', titleAccentEn: 'Our Priority',
        subtitleAr: 'عيادة متعددة التخصصات تقدم أعلى مستويات الرعاية الصحية المتكاملة',
        subtitleEn: 'A multi-specialty polyclinic delivering the highest standards of comprehensive healthcare',
        tagAr: 'عيادة دكتوراتو التخصصية', tagEn: 'Doctorato Polyclinic',
    },
    {
        image: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=1920&q=80',
        titleAr: 'بشرتك تستحق', titleAccentAr: 'أفضل عناية', titleEn: 'Your Skin Deserves', titleAccentEn: 'The Best Care',
        subtitleAr: 'أحدث تقنيات الليزر والتجميل مع أفضل أطباء الجلدية المتخصصين',
        subtitleEn: 'Latest laser and cosmetic technologies with the best specialized dermatologists',
        tagAr: 'قسم الجلدية والتجميل', tagEn: 'Dermatology & Aesthetics',
    },
    {
        image: 'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?w=1920&q=80',
        titleAr: 'ابتسامتك', titleAccentAr: 'تبدأ من هنا', titleEn: 'Your Smile', titleAccentEn: 'Starts Here',
        subtitleAr: 'رعاية شاملة للأسنان من التنظيف والتبييض إلى التركيبات والزراعة',
        subtitleEn: 'Complete dental care from cleaning and whitening to implants and crowns',
        tagAr: 'قسم طب الأسنان', tagEn: 'Dental Care',
    },
    {
        image: 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=1920&q=80',
        titleAr: 'صحة أطفالك', titleAccentAr: 'في أيدٍ أمينة', titleEn: "Your Child's Health", titleAccentEn: 'In Safe Hands',
        subtitleAr: 'متابعة النمو والتطعيمات والكشف المبكر مع أطباء أطفال متخصصين',
        subtitleEn: 'Growth monitoring, vaccinations, and early screening with specialized pediatricians',
        tagAr: 'قسم طب الأطفال', tagEn: 'Pediatrics',
    },
    {
        image: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1920&q=80',
        titleAr: 'تقنيات عالمية', titleAccentAr: 'بأيدٍ خبيرة', titleEn: 'Global Technology', titleAccentEn: 'Expert Hands',
        subtitleAr: 'نستخدم أحدث الأجهزة والتقنيات المعتمدة عالمياً لضمان أفضل النتائج',
        subtitleEn: 'We use the latest globally certified equipment and technologies for the best results',
        tagAr: 'أحدث الأجهزة الطبية', tagEn: 'Advanced Medical Equipment',
    },
];

// Always use built-in slides — DB slides are for admin-managed content later
const slides = computed(() => builtInSlides);
const totalSlides = computed(() => slides.value.length);

function goToSlide(index) {
    if (isTransitioning.value || index === currentSlide.value) return;
    isTransitioning.value = true;
    currentSlide.value = index;
    setTimeout(() => { isTransitioning.value = false; }, 1200);
}

function nextSlide() {
    goToSlide((currentSlide.value + 1) % totalSlides.value);
}

let slideInterval = null;
onMounted(() => { slideInterval = setInterval(nextSlide, 6000); });
onUnmounted(() => { if (slideInterval) clearInterval(slideInterval); });

// Fallback images per module for specialties without service images
const moduleImages = {
    derma: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=600&q=80',
    dental: 'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?w=600&q=80',
    pediatric: 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=600&q=80',
};
</script>

<template>
    <FrontendLayout :title="t('home')">
        <SeoHead :title="seoTitle" :description="seoDescription" :keywords="seo?.keywords" :image="seo?.image" />

        <!-- ═══════════════════════════════════════ -->
        <!-- HERO — 5-Slide Cinematic Carousel      -->
        <!-- ═══════════════════════════════════════ -->
        <section class="relative h-[70vh] md:h-[85vh] lg:h-[90vh] min-h-[450px] max-h-[800px] flex items-center overflow-hidden bg-[#0f2847]">
            <!-- Background Images with Ken Burns -->
            <div class="absolute inset-0">
                <div v-for="(slide, index) in slides" :key="index"
                     class="absolute inset-0 transition-opacity duration-[1.2s] ease-in-out"
                     :class="currentSlide === index ? 'opacity-100' : 'opacity-0'">
                    <img :src="slide.image" alt=""
                         class="w-full h-full object-cover"
                         :class="currentSlide === index ? 'hero-ken-burns' : ''" />
                </div>
                <!-- Overlay — consistent Navy tint -->
                <div class="absolute inset-0 bg-[#1B365D]/75"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#1B365D]/50 to-transparent"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <div class="text-center max-w-3xl mx-auto">
                    <Transition name="hero-text" mode="out-in">
                        <div :key="currentSlide">
                            <!-- Tag badge -->
                            <div class="inline-flex items-center gap-2.5 px-5 py-2 rounded-full bg-[#C4A265]/15 border border-[#C4A265]/25 mb-7">
                                <span class="w-1.5 h-1.5 bg-[#C4A265] rounded-full animate-pulse"></span>
                                <span class="text-[#C4A265] text-xs font-bold tracking-wider uppercase">
                                    {{ isRtl ? slides[currentSlide].tagAr : slides[currentSlide].tagEn }}
                                </span>
                            </div>

                            <!-- Title -->
                            <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-white mb-5 leading-[1.15]">
                                {{ isRtl ? slides[currentSlide].titleAr : slides[currentSlide].titleEn }}
                                <span class="block text-[#C4A265]">
                                    {{ isRtl ? slides[currentSlide].titleAccentAr : slides[currentSlide].titleAccentEn }}
                                </span>
                            </h1>

                            <!-- Divider -->
                            <div class="w-16 h-[2px] bg-[#C4A265]/50 mx-auto mb-5"></div>

                            <!-- Subtitle -->
                            <p class="text-base sm:text-lg text-white/70 leading-relaxed max-w-xl mx-auto mb-10">
                                {{ isRtl ? slides[currentSlide].subtitleAr : slides[currentSlide].subtitleEn }}
                            </p>
                        </div>
                    </Transition>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <Link :href="localizedRoute('/booking')"
                              class="inline-flex items-center gap-2.5 px-8 py-4 bg-[#C4A265] text-[#1B365D] font-bold rounded-lg
                                     transition-all duration-300 shadow-lg shadow-[#C4A265]/30
                                     hover:shadow-xl hover:bg-[#d4b87a] hover:-translate-y-0.5 text-sm sm:text-base">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ isRtl ? 'احجز موعدك الآن' : 'Book Appointment' }}
                        </Link>
                        <a :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2.5 px-8 py-4 bg-white/10 backdrop-blur-sm border border-white/20 text-white
                                  font-semibold rounded-lg hover:bg-white/20 transition-all duration-300 text-sm sm:text-base">
                            <svg class="w-5 h-5 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            {{ isRtl ? 'استشارة مجانية' : 'Free Consultation' }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide indicators — bottom center -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2.5">
                <button v-for="(slide, index) in slides" :key="index" @click="goToSlide(index)"
                        class="relative transition-all duration-500 rounded-full"
                        :class="currentSlide === index
                            ? 'w-10 h-2 bg-[#C4A265]'
                            : 'w-2 h-2 bg-white/30 hover:bg-white/50'">
                </button>
            </div>

            <!-- Gold bottom line (no wave) -->
            <div class="absolute bottom-0 left-0 w-full h-[3px] bg-gradient-to-r from-transparent via-[#C4A265]/60 to-transparent"></div>
        </section>


        <!-- ═══════════════════════════════════════ -->
        <!-- SPECIALTIES — 3 Module Cards           -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-14 md:py-24 bg-[#FAFBFD] relative overflow-hidden" v-if="medicalSpecialties && medicalSpecialties.length">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <!-- Section Header -->
                <div class="text-center mb-10 md:mb-14" v-scroll-reveal="{ type: 'fade-up' }">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-[#1B365D]/5 text-[#1B365D] text-xs font-semibold tracking-wider uppercase mb-4">
                        {{ isRtl ? 'تخصصاتنا الطبية' : 'Our Specialties' }}
                    </span>
                    <h2 class="text-2xl sm:text-3xl lg:text-5xl font-bold text-[#1B365D] mb-3">
                        {{ isRtl ? 'رعاية متكاملة في مكان واحد' : 'Complete Care Under One Roof' }}
                    </h2>
                    <div class="w-12 h-[2px] bg-[#C4A265]/50 mx-auto mb-4"></div>
                    <p class="text-gray-500 text-sm md:text-lg max-w-2xl mx-auto">
                        {{ isRtl ? 'نجمع بين أفضل التخصصات الطبية لنقدم لك رعاية شاملة ومتكاملة' : 'We bring together the best medical specialties for comprehensive care' }}
                    </p>
                </div>

                <!-- 3 Module Cards — horizontal scroll on mobile, grid on desktop -->
                <div class="flex md:grid md:grid-cols-3 gap-4 md:gap-6 lg:gap-8 overflow-x-auto md:overflow-visible snap-x snap-mandatory pb-4 md:pb-0 -mx-4 px-4 md:mx-0 md:px-0 scrollbar-hide">
                    <a v-for="(spec, si) in medicalSpecialties" :key="spec.slug"
                       :href="localizedRoute('/services') + '?module=' + spec.slug"
                       class="spec-card group relative rounded-2xl overflow-hidden cursor-pointer
                              flex-shrink-0 w-[280px] h-[320px] md:w-auto md:h-[400px] snap-center block"
                       v-scroll-reveal="{ type: 'fade-up', delay: si * 120 }">
                        <!-- Background image -->
                        <img :src="moduleImages[spec.slug]" :alt="isRtl ? spec.name_ar : spec.name_en"
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-[1.2s] ease-out group-hover:scale-110" />

                        <!-- Gradient overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-[#1B365D] via-[#1B365D]/50 to-[#1B365D]/10
                                    group-hover:via-[#1B365D]/60 transition-all duration-500"></div>

                        <!-- Colored top accent -->
                        <div class="absolute top-0 inset-x-0 h-1 group-hover:h-1.5 transition-all duration-500"
                             :style="{ backgroundColor: spec.color }"></div>

                        <!-- Content — bottom aligned -->
                        <div class="absolute inset-0 flex flex-col justify-end p-5 md:p-7">
                            <!-- Icon -->
                            <div class="w-11 h-11 md:w-14 md:h-14 rounded-xl md:rounded-2xl flex items-center justify-center mb-4 backdrop-blur-sm
                                        border transition-all duration-500 group-hover:scale-110 group-hover:-translate-y-1"
                                 :style="{ backgroundColor: spec.color + '20', borderColor: spec.color + '40' }">
                                <svg class="w-5 h-5 md:w-7 md:h-7" :style="{ color: spec.color }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="spec.icon" />
                                </svg>
                            </div>

                            <!-- Module name -->
                            <h3 class="text-xl md:text-2xl font-bold text-white mb-1.5">
                                {{ isRtl ? spec.name_ar : spec.name_en }}
                            </h3>

                            <!-- Service count -->
                            <p class="text-white/50 text-xs md:text-sm mb-3">
                                {{ spec.categories.reduce((sum, c) => sum + (c.services?.length || 0), 0) }} {{ isRtl ? 'خدمة متخصصة' : 'specialized services' }}
                            </p>

                            <!-- Category pills — always visible on mobile, hover on desktop -->
                            <div class="flex flex-wrap gap-1.5 md:gap-2 mb-2
                                        md:max-h-0 md:opacity-0 md:group-hover:max-h-40 md:group-hover:opacity-100
                                        transition-all duration-500 overflow-hidden">
                                <span v-for="cat in spec.categories.slice(0, 3)" :key="cat.id"
                                      class="px-2.5 py-1 rounded-full text-[10px] md:text-xs font-medium bg-white/10 text-white/80 border border-white/10">
                                    {{ isRtl ? cat.name_ar : cat.name_en }}
                                </span>
                                <span v-if="spec.categories.length > 3"
                                      class="px-2.5 py-1 rounded-full text-[10px] md:text-xs font-medium bg-white/10 text-white/60 border border-white/10">
                                    +{{ spec.categories.length - 3 }}
                                </span>
                            </div>

                            <!-- CTA -->
                            <div class="flex items-center gap-2 text-[#C4A265] text-xs md:text-sm font-semibold
                                        md:translate-y-2 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100
                                        transition-all duration-500 delay-100">
                                <span>{{ isRtl ? 'اكتشف خدماتنا' : 'Explore Services' }}</span>
                                <svg class="w-3.5 h-3.5 md:w-4 md:h-4 transition-transform duration-300 group-hover:translate-x-1"
                                     :class="{ 'rotate-180 group-hover:-translate-x-1': isRtl }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>


        <!-- ═══════════════════════════════════════ -->
        <!-- ABOUT — Cinematic Split                -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-14 md:py-24 bg-white relative overflow-hidden">
            <!-- Subtle background texture -->
            <div class="absolute inset-0 opacity-[0.015]"
                 style="background-image: radial-gradient(circle at 1px 1px, #1B365D 1px, transparent 0); background-size: 40px 40px;"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16 items-center">

                    <!-- Image Side — 5 cols -->
                    <div class="lg:col-span-5 relative" :class="{ 'lg:order-2': isRtl }" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                        <div class="relative">
                            <!-- Decorative frame -->
                            <div class="absolute -top-3 -end-3 w-full h-full rounded-2xl border-2 border-[#C4A265]/15"></div>

                            <!-- Main image -->
                            <div class="relative rounded-2xl overflow-hidden shadow-xl">
                                <img src="https://images.unsplash.com/photo-1666214280557-f1b5022eb634?w=800&q=80"
                                     alt="Doctorato Polyclinic"
                                     class="w-full h-[300px] md:h-[420px] object-cover about-img-zoom" />
                                <!-- Navy overlay bottom -->
                                <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-[#1B365D]/60 to-transparent"></div>
                            </div>

                            <!-- Floating stats card -->
                            <div class="absolute -bottom-4 start-4 md:-bottom-5 md:-start-5 bg-[#1B365D] text-white rounded-xl p-4 md:p-5 shadow-2xl about-float-card">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-[#C4A265]/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold text-[#C4A265]">
                                            <StatCounter :end="10" suffix="+" />
                                        </div>
                                        <p class="text-white/60 text-xs">{{ isRtl ? 'سنوات من الخبرة' : 'Years Experience' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Floating badge top -->
                            <div class="absolute -top-3 end-4 md:-top-4 md:-end-4 bg-white rounded-lg shadow-lg px-3 py-2 border border-gray-100 about-float-badge">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                                    </svg>
                                    <span class="text-[#1B365D] font-bold text-[11px]">{{ isRtl ? 'جودة معتمدة' : 'Certified Quality' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Text Side — 7 cols -->
                    <div class="lg:col-span-7" :class="{ 'lg:order-1': isRtl }" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#C4A265]/10 text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/>
                            </svg>
                            {{ isRtl ? 'من نحن' : 'About Us' }}
                        </span>

                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-[#1B365D] mb-5 leading-tight">
                            {{ isRtl ? 'عيادة دكتوراتو' : 'Doctorato' }}
                            <span class="text-[#C4A265]">{{ isRtl ? ' التخصصية' : ' Polyclinic' }}</span>
                        </h2>

                        <p class="text-gray-600 text-base md:text-lg leading-relaxed mb-4">
                            {{ isRtl
                                ? 'عيادة دكتوراتو هي عيادة متعددة التخصصات الطبية تقدم أعلى مستويات الرعاية الصحية في بيئة راقية ومريحة، مع فريق طبي متخصص يضع صحتك وراحتك في المقام الأول.'
                                : 'Doctorato is a multi-specialty polyclinic providing the highest standards of healthcare in an elegant, comfortable environment, with a specialized medical team that puts your health and comfort first.' }}
                        </p>
                        <p class="text-gray-400 text-sm leading-relaxed mb-7">
                            {{ isRtl
                                ? 'تأسست العيادة على يد نخبة من الأطباء المتخصصين في مختلف التخصصات الطبية، لتكون وجهتك الأولى للرعاية الصحية المتكاملة.'
                                : 'Founded by an elite team of medical specialists across multiple disciplines, the clinic is your premier destination for comprehensive healthcare.' }}
                        </p>

                        <!-- Feature grid -->
                        <div class="grid grid-cols-2 gap-3 mb-7">
                            <div v-for="(feat, i) in [
                                { ar: 'أحدث الأجهزة الطبية', en: 'Latest Medical Equipment', icon: 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5' },
                                { ar: 'أطباء معتمدون دولياً', en: 'Internationally Certified', icon: 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904' },
                                { ar: 'رعاية شخصية متميزة', en: 'Personalized Care', icon: 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733' },
                                { ar: 'بيئة مريحة وآمنة', en: 'Comfortable Environment', icon: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6' },
                            ]" :key="i"
                               class="flex items-center gap-2.5 p-3 rounded-lg bg-gray-50 hover:bg-[#C4A265]/5 transition-colors duration-300">
                                <div class="w-8 h-8 rounded-lg bg-[#1B365D]/5 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="feat.icon"/>
                                    </svg>
                                </div>
                                <span class="text-xs md:text-sm text-gray-700 font-medium">{{ isRtl ? feat.ar : feat.en }}</span>
                            </div>
                        </div>

                        <Link :href="localizedRoute('/about')"
                              class="inline-flex items-center gap-2 px-6 py-3 bg-[#1B365D] text-white font-semibold rounded-lg
                                     hover:bg-[#264573] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 text-sm">
                            {{ isRtl ? 'اعرف المزيد عنا' : 'Learn More About Us' }}
                            <svg class="w-4 h-4" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </section>


        <!-- ═══════════════════════════════════════ -->
        <!-- STATS — Floating Bar                   -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-16 bg-[#1B365D] relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(196,162,101,0.08)_0%,_transparent_60%)]"></div>
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8" v-scroll-reveal="{ type: 'stagger', staggerDelay: 120 }">
                    <div v-for="(stat, i) in [
                        { end: 1000, suffix: '+', ar: 'مريض سعيد', en: 'Happy Patients', icon: 'M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
                        { end: 10, suffix: '+', ar: 'طبيب متخصص', en: 'Specialist Doctors', icon: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12' },
                        { end: 20, suffix: '+', ar: 'خدمة طبية', en: 'Medical Services', icon: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z' },
                        { end: 3, suffix: '', ar: 'تخصصات طبية', en: 'Medical Specialties', icon: 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6' },
                    ]" :key="i" class="text-center">
                        <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-[#C4A265]/10 flex items-center justify-center">
                            <svg class="w-7 h-7 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="stat.icon" />
                            </svg>
                        </div>
                        <div class="text-3xl sm:text-4xl font-bold text-white mb-1">
                            <StatCounter :end="stat.end" :suffix="stat.suffix" />
                        </div>
                        <p class="text-[#C4A265]/80 text-sm font-medium">{{ isRtl ? stat.ar : stat.en }}</p>
                    </div>
                </div>
            </div>
        </section>


        <!-- ═══════════════════════════════════════ -->
        <!-- FEATURED SERVICES                      -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-14 md:py-24 bg-[#FAFBFD] relative overflow-hidden" v-if="featuredServices && featuredServices.length">
            <!-- Subtle texture -->
            <div class="absolute inset-0 opacity-[0.015]"
                 style="background-image: radial-gradient(circle at 1px 1px, #1B365D 1px, transparent 0); background-size: 40px 40px;"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-10 md:mb-14" v-scroll-reveal="{ type: 'fade-up' }">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-[#1B365D]/5 text-[#1B365D] text-xs font-semibold tracking-wider uppercase mb-4">
                        {{ isRtl ? 'خدماتنا' : 'Our Services' }}
                    </span>
                    <h2 class="text-2xl sm:text-3xl lg:text-5xl font-bold text-[#1B365D] mb-3">
                        {{ isRtl ? 'خدمات طبية مميزة' : 'Featured Medical Services' }}
                    </h2>
                    <div class="w-12 h-[2px] bg-[#C4A265]/50 mx-auto mb-4"></div>
                    <p class="text-gray-500 text-sm md:text-lg max-w-2xl mx-auto">
                        {{ isRtl ? 'نقدم مجموعة شاملة من الخدمات الطبية المتخصصة بأحدث التقنيات والمعايير العالمية' : 'A comprehensive range of specialized medical services using the latest technologies' }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <ServiceCard v-for="(service, si) in featuredServices.slice(0, 6)" :key="service.id" :service="service"
                                 v-scroll-reveal="{ type: 'fade-up', delay: si * 80 }" />
                </div>

                <div class="text-center mt-14" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                    <Link :href="localizedRoute('/services')"
                          class="inline-flex items-center gap-2 px-8 py-3.5 bg-[#C4A265] text-white font-semibold rounded-xl
                                 transition-all duration-300 shadow-md shadow-[#C4A265]/15 hover:shadow-lg hover:bg-[#B3914F]">
                        {{ isRtl ? 'عرض جميع الخدمات' : 'View All Services' }}
                        <svg class="w-4 h-4" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </section>


        <!-- ═══════════════════════════════════════ -->
        <!-- PACKAGES                               -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-20 md:py-28 relative overflow-hidden" v-if="packageBundles && packageBundles.length">
            <div class="absolute inset-0 bg-gradient-to-br from-[#1B365D] via-[#213f6b] to-[#1B365D]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(196,162,101,0.12)_0%,_transparent_50%)]"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16" v-scroll-reveal="{ type: 'fade-up' }">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-[#C4A265]/10 border border-[#C4A265]/20 text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-4">
                        {{ t('packages') }}
                    </span>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">{{ t('package_bundles_title') }}</h2>
                    <p class="text-white/50 text-lg max-w-2xl mx-auto">{{ t('packages_hero_subtitle') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8" v-scroll-reveal="{ type: 'stagger', staggerDelay: 150 }">
                    <div v-for="bundle in packageBundles" :key="bundle.id"
                         class="group bg-white/[0.06] backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden
                                hover:border-[#C4A265]/30 transition-all duration-500 hover:shadow-xl hover:shadow-[#C4A265]/[0.08]">
                        <div class="relative h-52 overflow-hidden">
                            <img v-if="bundle.image_url" :src="bundle.image_url" :alt="localized(bundle, 'name')"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                            <div v-else class="w-full h-full bg-gradient-to-br from-[#C4A265]/20 to-[#C4A265]/5 flex items-center justify-center">
                                <svg class="w-16 h-16 text-[#C4A265]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-[#1B365D]/80 via-transparent to-transparent"></div>
                            <div v-if="Number(bundle.savings) > 0" class="absolute top-4 start-4 bg-green-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                                {{ t('save_amount') }} {{ formatCurrency(bundle.savings) }}
                            </div>
                            <div class="absolute bottom-4 start-4 flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-xs font-medium px-3 py-1.5 rounded-full">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                {{ bundle.services?.length || 0 }} {{ t('services_included') }}
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-white mb-2 group-hover:text-[#C4A265] transition-colors">{{ localized(bundle, 'name') }}</h3>
                            <p v-if="localized(bundle, 'description')" class="text-white/40 text-sm mb-5 line-clamp-2">{{ localized(bundle, 'description') }}</p>
                            <div class="flex items-end gap-3 mb-5">
                                <span class="text-2xl font-bold text-[#C4A265]">{{ formatCurrency(bundle.total_price) }}</span>
                                <span v-if="Number(bundle.original_price) > Number(bundle.total_price)" class="text-sm text-white/30 line-through pb-0.5 ms-auto">
                                    {{ formatCurrency(bundle.original_price) }}
                                </span>
                            </div>
                            <Link :href="localizedRoute(`/package-bundles/${bundle.id}`)"
                                  class="block w-full text-center py-3 rounded-xl text-white font-semibold text-sm bg-[#C4A265] hover:bg-[#B3914F] transition-all duration-300">
                                {{ t('view_package') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-12" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                    <Link :href="localizedRoute('/package-bundles')"
                          class="inline-flex items-center gap-2 px-8 py-3.5 border-2 border-[#C4A265] text-[#C4A265] font-semibold rounded-xl
                                 hover:bg-[#C4A265] hover:text-white transition-all duration-300">
                        {{ t('view_all_packages') }}
                        <svg class="w-4 h-4" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </section>


        <!-- ═══════════════════════════════════════ -->
        <!-- TESTIMONIALS                           -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-20 md:py-28 bg-white relative" v-if="testimonials && testimonials.length">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16" v-scroll-reveal="{ type: 'fade-up' }">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-[#1B365D]/5 text-[#1B365D] text-xs font-semibold tracking-wider uppercase mb-4">
                        {{ isRtl ? 'آراء المرضى' : 'Patient Reviews' }}
                    </span>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-[#1B365D] mb-4">
                        {{ isRtl ? 'ماذا يقول مرضانا' : 'What Our Patients Say' }}
                    </h2>
                    <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                        {{ isRtl ? 'تجارب حقيقية تعكس التزامنا بتقديم أفضل رعاية صحية' : 'Real experiences reflecting our commitment to providing the best healthcare' }}
                    </p>
                </div>
                <div v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                    <TestimonialCarousel :testimonials="testimonials" />
                </div>
            </div>
        </section>


        <!-- ═══════════════════════════════════════ -->
        <!-- CTA BANNER                             -->
        <!-- ═══════════════════════════════════════ -->
        <section class="relative py-20 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-[#FAFBFD] via-[#f0ebe3] to-[#FAFBFD]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(196,162,101,0.08)_0%,_transparent_50%)]"></div>

            <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#C4A265]/10 border border-[#C4A265]/20 mb-6"
                     v-scroll-reveal="{ type: 'fade-up' }">
                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                    <span class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase">{{ isRtl ? 'تواصل معنا' : 'Get in Touch' }}</span>
                </div>

                <h2 class="text-3xl sm:text-4xl font-bold text-[#1B365D] mb-4 leading-tight" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                    {{ isRtl ? 'صحتك تستحق الأفضل' : 'Your Health Deserves the Best' }}
                    <span class="block text-[#C4A265] mt-1">{{ isRtl ? 'ابدأ رحلتك اليوم' : 'Start Your Journey Today' }}</span>
                </h2>

                <p class="text-gray-500 text-lg mb-10 max-w-xl mx-auto" v-scroll-reveal="{ type: 'fade-up', delay: 150 }">
                    {{ isRtl ? 'تواصل معنا الآن واحصل على استشارة مجانية مع أفضل أطبائنا' : 'Contact us now and get a free consultation with our best doctors' }}
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                    <a :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2.5 px-8 py-4 bg-[#25D366] text-white font-semibold rounded-xl
                              transition-all duration-300 shadow-lg shadow-[#25D366]/20 hover:shadow-xl hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        {{ isRtl ? 'تواصل عبر واتساب' : 'Chat on WhatsApp' }}
                    </a>
                    <Link :href="localizedRoute('/booking')"
                          class="inline-flex items-center gap-2.5 px-8 py-4 bg-[#1B365D] text-white font-semibold rounded-xl
                                 transition-all duration-300 shadow-lg shadow-[#1B365D]/20 hover:shadow-xl hover:-translate-y-0.5 hover:bg-[#264573]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ isRtl ? 'احجز موعدك' : 'Book Appointment' }}
                    </Link>
                </div>

                <!-- Trust badges -->
                <div class="flex items-center justify-center gap-6 mt-10 pt-6 border-t border-[#C4A265]/10" v-scroll-reveal="{ type: 'fade-up', delay: 300 }">
                    <div class="flex items-center gap-1.5 text-gray-400 text-xs">
                        <svg class="w-4 h-4 text-[#C4A265]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622A11.99 11.99 0 0020.402 6a11.959 11.959 0 00-8.402-3.785z"/>
                        </svg>
                        {{ isRtl ? 'استشارة مجانية' : 'Free Consultation' }}
                    </div>
                    <div class="w-1 h-1 rounded-full bg-[#C4A265]/30"></div>
                    <div class="flex items-center gap-1.5 text-gray-400 text-xs">
                        <svg class="w-4 h-4 text-[#C4A265]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                        </svg>
                        {{ isRtl ? 'أطباء معتمدون' : 'Certified Doctors' }}
                    </div>
                    <div class="w-1 h-1 rounded-full bg-[#C4A265]/30 hidden sm:block"></div>
                    <div class="hidden sm:flex items-center gap-1.5 text-gray-400 text-xs">
                        <svg class="w-4 h-4 text-[#C4A265]" fill="currentColor" viewBox="0 0 24 24">
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
/* Ken Burns zoom on active slide */
@keyframes kenBurns {
    0%   { transform: scale(1); }
    100% { transform: scale(1.12); }
}
.hero-ken-burns {
    animation: kenBurns 7s ease-out forwards;
}

/* Orbit decoration */
@keyframes heroOrbit {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.hero-orbit {
    animation: heroOrbit 35s linear infinite;
}

/* Vue transition for hero text content */
.hero-text-enter-active {
    transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
.hero-text-leave-active {
    transition: all 0.3s ease-in;
}
.hero-text-enter-from {
    opacity: 0;
    transform: translateY(30px);
    filter: blur(4px);
}
.hero-text-leave-to {
    opacity: 0;
    transform: translateY(-20px);
    filter: blur(2px);
}

/* Progress ring on active indicator */
@keyframes progressRing {
    from { stroke-dashoffset: 63; }
    to   { stroke-dashoffset: 0;  }
}
.hero-progress-circle {
    animation: progressRing 6s linear forwards;
}
.hero-progress-ring {
    transform: rotate(-90deg);
}

/* About section animations */
.about-img-zoom {
    transition: transform 8s ease-out;
}
.about-img-zoom:hover {
    transform: scale(1.05);
}

@keyframes aboutFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
.about-float-card {
    animation: aboutFloat 4s ease-in-out infinite;
}
.about-float-badge {
    animation: aboutFloat 4s ease-in-out infinite;
    animation-delay: 1.5s;
}
</style>
