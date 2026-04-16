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

/* ── Specialties mobile slider ───────────────── */
const specActiveSlide = ref(0);
const specProgress = ref(0);
const SPEC_DURATION = 5000; // 5 seconds per slide
let specInterval = null;
let specProgressInterval = null;

function specGoToSlide(index) {
    specActiveSlide.value = index;
    specProgress.value = 0;
    resetSpecTimer();
}

function specNextSlide() {
    const total = props.medicalSpecialties?.length || 0;
    if (!total) return;
    specActiveSlide.value = (specActiveSlide.value + 1) % total;
    specProgress.value = 0;
}

function resetSpecTimer() {
    if (specInterval) clearInterval(specInterval);
    if (specProgressInterval) clearInterval(specProgressInterval);
    startSpecTimer();
}

function startSpecTimer() {
    specInterval = setInterval(specNextSlide, SPEC_DURATION);
    specProgressInterval = setInterval(() => {
        specProgress.value = Math.min(specProgress.value + (100 / (SPEC_DURATION / 50)), 100);
    }, 50);
}

onMounted(() => {
    if (props.medicalSpecialties?.length > 1) startSpecTimer();
});
onUnmounted(() => {
    if (specInterval) clearInterval(specInterval);
    if (specProgressInterval) clearInterval(specProgressInterval);
});

// Package module filter
const activePackageModule = ref('all');
const filteredPackages = computed(() => {
    if (activePackageModule.value === 'all') return props.packageBundles || [];
    return (props.packageBundles || []).filter(b => b.module === activePackageModule.value);
});

// Module info for tabs
const packageModules = computed(() => {
    const modules = [...new Set((props.packageBundles || []).map(b => b.module))];
    return modules.map(m => {
        const spec = (props.medicalSpecialties || []).find(s => s.slug === m);
        return { slug: m, name_ar: spec?.name_ar || m, name_en: spec?.name_en || m, color: spec?.color || '#C4A265' };
    });
});

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

                <!-- Desktop: 3-column grid -->
                <div class="hidden md:grid md:grid-cols-3 gap-6 lg:gap-8">
                    <a v-for="(spec, si) in medicalSpecialties" :key="'desktop-' + spec.slug"
                       :href="localizedRoute('/services') + '?module=' + spec.slug"
                       class="spec-card group relative rounded-2xl overflow-hidden cursor-pointer h-[400px] block"
                       v-scroll-reveal="{ type: 'fade-up', delay: si * 120 }">
                        <img :src="moduleImages[spec.slug]" :alt="isRtl ? spec.name_ar : spec.name_en"
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-[1.2s] ease-out group-hover:scale-110" />
                        <div class="absolute inset-0 bg-gradient-to-t from-[#1B365D] via-[#1B365D]/50 to-[#1B365D]/10 group-hover:via-[#1B365D]/60 transition-all duration-500"></div>
                        <div class="absolute top-0 inset-x-0 h-1 group-hover:h-1.5 transition-all duration-500" :style="{ backgroundColor: spec.color }"></div>
                        <div class="absolute inset-0 flex flex-col justify-end p-7">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4 backdrop-blur-sm border transition-all duration-500 group-hover:scale-110 group-hover:-translate-y-1"
                                 :style="{ backgroundColor: spec.color + '20', borderColor: spec.color + '40' }">
                                <svg class="w-7 h-7" :style="{ color: spec.color }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="spec.icon" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-1.5">{{ isRtl ? spec.name_ar : spec.name_en }}</h3>
                            <p class="text-white/50 text-sm mb-3">
                                {{ spec.categories.reduce((sum, c) => sum + (c.services?.length || 0), 0) }} {{ isRtl ? 'خدمة متخصصة' : 'specialized services' }}
                            </p>
                            <div class="flex flex-wrap gap-2 mb-2 max-h-0 opacity-0 group-hover:max-h-40 group-hover:opacity-100 transition-all duration-500 overflow-hidden">
                                <span v-for="cat in spec.categories.slice(0, 3)" :key="cat.id"
                                      class="px-2.5 py-1 rounded-full text-xs font-medium bg-white/10 text-white/80 border border-white/10">
                                    {{ isRtl ? cat.name_ar : cat.name_en }}
                                </span>
                                <span v-if="spec.categories.length > 3"
                                      class="px-2.5 py-1 rounded-full text-xs font-medium bg-white/10 text-white/60 border border-white/10">
                                    +{{ spec.categories.length - 3 }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-[#C4A265] text-sm font-semibold translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500 delay-100">
                                <span>{{ isRtl ? 'اكتشف خدماتنا' : 'Explore Services' }}</span>
                                <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1"
                                     :class="{ 'rotate-180 group-hover:-translate-x-1': isRtl }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Mobile: Animated Slider -->
                <div class="md:hidden relative" v-scroll-reveal="{ type: 'fade-up' }">
                    <!-- Slider viewport -->
                    <div class="overflow-hidden rounded-2xl">
                        <div class="flex transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
                             :style="{ transform: `translateX(${isRtl ? '' : '-'}${specActiveSlide * 100}%)` }">
                            <a v-for="(spec, si) in medicalSpecialties" :key="'mobile-' + spec.slug"
                               :href="localizedRoute('/services') + '?module=' + spec.slug"
                               class="spec-mobile-card group relative rounded-2xl overflow-hidden cursor-pointer w-full h-[360px] flex-shrink-0 block"
                               :class="{ 'spec-slide-active': si === specActiveSlide }">
                                <img :src="moduleImages[spec.slug]" :alt="isRtl ? spec.name_ar : spec.name_en"
                                     class="absolute inset-0 w-full h-full object-cover spec-slide-img" />
                                <div class="absolute inset-0 bg-gradient-to-t from-[#1B365D] via-[#1B365D]/50 to-[#1B365D]/10"></div>
                                <div class="absolute top-0 inset-x-0 h-1.5" :style="{ backgroundColor: spec.color }"></div>
                                <div class="absolute inset-0 flex flex-col justify-end p-6">
                                    <div class="spec-slide-content">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-3 backdrop-blur-sm border"
                                             :style="{ backgroundColor: spec.color + '25', borderColor: spec.color + '50' }">
                                            <svg class="w-6 h-6" :style="{ color: spec.color }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" :d="spec.icon" />
                                            </svg>
                                        </div>
                                        <h3 class="text-2xl font-bold text-white mb-1.5">{{ isRtl ? spec.name_ar : spec.name_en }}</h3>
                                        <p class="text-white/60 text-xs mb-3">
                                            {{ spec.categories.reduce((sum, c) => sum + (c.services?.length || 0), 0) }} {{ isRtl ? 'خدمة متخصصة' : 'specialized services' }}
                                        </p>
                                        <div class="flex flex-wrap gap-1.5 mb-3">
                                            <span v-for="cat in spec.categories.slice(0, 2)" :key="cat.id"
                                                  class="px-2.5 py-1 rounded-full text-[10px] font-medium bg-white/15 text-white/90 border border-white/20 backdrop-blur-sm">
                                                {{ isRtl ? cat.name_ar : cat.name_en }}
                                            </span>
                                            <span v-if="spec.categories.length > 2"
                                                  class="px-2.5 py-1 rounded-full text-[10px] font-medium bg-white/10 text-white/70 border border-white/10">
                                                +{{ spec.categories.length - 2 }}
                                            </span>
                                        </div>
                                        <div class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-white/15 backdrop-blur-md border border-white/25 text-white text-xs font-semibold">
                                            <span>{{ isRtl ? 'اكتشف خدماتنا' : 'Explore Services' }}</span>
                                            <svg class="w-3 h-3" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Dot indicators -->
                    <div class="flex items-center justify-center gap-2 mt-5">
                        <button v-for="(spec, si) in medicalSpecialties" :key="'dot-' + spec.slug"
                                @click="specGoToSlide(si)"
                                type="button"
                                class="spec-dot"
                                :class="{ 'spec-dot-active': si === specActiveSlide }"
                                :style="si === specActiveSlide ? { backgroundColor: spec.color } : {}"
                                :aria-label="`Go to ${spec.name_en}`">
                        </button>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-3 mx-auto max-w-[120px] h-0.5 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-100 ease-linear"
                             :style="{
                                width: specProgress + '%',
                                backgroundColor: medicalSpecialties[specActiveSlide]?.color || '#C4A265'
                             }"></div>
                    </div>
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
        <!-- STATS — Premium Counter Bar            -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-16 md:py-20 relative overflow-hidden stats-section">
            <!-- Navy gradient background -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#0a1f3a] via-[#1B365D] to-[#122e52]"></div>

            <!-- Hexagonal pattern texture -->
            <div class="absolute inset-0 opacity-[0.08] stats-hex-pattern"></div>

            <!-- Medical cross pattern overlay -->
            <div class="absolute inset-0 opacity-[0.04] stats-cross-pattern"></div>

            <!-- Subtle dot grid -->
            <div class="absolute inset-0 opacity-[0.05]"
                 style="background-image: radial-gradient(circle at 1px 1px, rgba(196,162,101,0.9) 1px, transparent 0); background-size: 32px 32px;"></div>

            <!-- Animated gradient blobs -->
            <div class="absolute top-[-10%] start-[-5%] w-[400px] h-[400px] rounded-full stats-blob-1 bg-[radial-gradient(circle,_rgba(196,162,101,0.15)_0%,_transparent_60%)] blur-2xl"></div>
            <div class="absolute bottom-[-15%] end-[-8%] w-[500px] h-[500px] rounded-full stats-blob-2 bg-[radial-gradient(circle,_rgba(147,197,253,0.08)_0%,_transparent_60%)] blur-3xl"></div>
            <div class="absolute top-[40%] start-[40%] w-[300px] h-[300px] rounded-full stats-blob-3 bg-[radial-gradient(circle,_rgba(196,162,101,0.1)_0%,_transparent_60%)] blur-2xl"></div>

            <!-- Diagonal light beams -->
            <div class="absolute inset-0 stats-beam-bg"></div>

            <!-- Animated SVG wave at top -->
            <svg class="absolute top-0 inset-x-0 w-full h-16 opacity-20" viewBox="0 0 1440 60" preserveAspectRatio="none" fill="none">
                <path class="stats-wave" d="M0,30 C360,60 720,0 1080,30 C1260,45 1350,15 1440,30 L1440,0 L0,0 Z" fill="url(#statsGradient1)"/>
                <defs>
                    <linearGradient id="statsGradient1" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#C4A265" stop-opacity="0"/>
                        <stop offset="50%" stop-color="#C4A265" stop-opacity="0.4"/>
                        <stop offset="100%" stop-color="#C4A265" stop-opacity="0"/>
                    </linearGradient>
                </defs>
            </svg>

            <!-- Pulse rings (heartbeat feel) -->
            <div class="absolute top-[15%] start-[8%] w-24 h-24 border border-[#C4A265]/20 rounded-full stats-pulse-ring"></div>
            <div class="absolute top-[15%] start-[8%] w-24 h-24 border border-[#C4A265]/30 rounded-full stats-pulse-ring" style="animation-delay:1s;"></div>
            <div class="absolute bottom-[20%] end-[10%] w-32 h-32 border border-white/10 rounded-full stats-pulse-ring" style="animation-delay:0.5s;"></div>

            <!-- Floating medical icons -->
            <div class="absolute top-[20%] end-[15%] text-[#C4A265]/[0.07] stats-float-icon" style="animation-delay:0s;">
                <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8h-2V6a4 4 0 10-8 0v2H7a1 1 0 00-1 1v11a1 1 0 001 1h12a1 1 0 001-1V9a1 1 0 00-1-1zM11 6a2 2 0 114 0v2h-4V6z"/></svg>
            </div>
            <div class="absolute bottom-[25%] start-[15%] text-white/[0.06] stats-float-icon" style="animation-delay:2s;">
                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4 6v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V6l-8-4zm0 16c-3.5 0-6.5-3.5-6.5-8V7.3l6.5-3.2 6.5 3.2V10c0 4.5-3 8-6.5 8z"/></svg>
            </div>
            <div class="absolute top-[45%] end-[5%] text-[#C4A265]/[0.08] stats-float-icon" style="animation-delay:4s;">
                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14h2v2h-2v-2zm0-10h2v8h-2V6z"/></svg>
            </div>

            <!-- Heartbeat line SVG -->
            <svg class="absolute inset-x-0 top-1/2 -translate-y-1/2 w-full h-24 opacity-10 stats-heartbeat" viewBox="0 0 1200 100" preserveAspectRatio="none" fill="none">
                <path d="M0,50 L200,50 L230,50 L245,20 L260,80 L275,10 L290,90 L305,50 L500,50 L530,50 L545,25 L560,75 L575,15 L590,85 L605,50 L800,50 L830,50 L845,20 L860,80 L875,10 L890,90 L905,50 L1200,50"
                      stroke="#C4A265" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            <!-- Geometric circles -->
            <div class="absolute top-[10%] start-[5%] w-40 h-40 border border-[#C4A265]/[0.08] rounded-full stats-orbit"></div>
            <div class="absolute bottom-[10%] end-[8%] w-56 h-56 border border-white/[0.04] rounded-full stats-orbit" style="animation-duration:30s;animation-direction:reverse;"></div>

            <!-- Top & bottom gold lines with glow -->
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#C4A265]/50 to-transparent"></div>
            <div class="absolute bottom-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#C4A265]/50 to-transparent"></div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                    <div v-for="(stat, i) in [
                        { end: 1000, suffix: '+', ar: 'مريض سعيد', en: 'Happy Patients', icon: 'M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z' },
                        { end: 10, suffix: '+', ar: 'طبيب متخصص', en: 'Specialist Doctors', icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z' },
                        { end: 20, suffix: '+', ar: 'خدمة طبية', en: 'Medical Services', icon: 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5' },
                        { end: 3, suffix: '', ar: 'تخصصات طبية', en: 'Medical Specialties', icon: 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21' },
                    ]" :key="i"
                       class="text-center group"
                       v-scroll-reveal="{ type: 'fade-up', delay: i * 120 }">
                        <!-- Card with glass effect -->
                        <div class="relative p-5 md:p-6 rounded-2xl bg-white/[0.04] backdrop-blur-sm border border-white/[0.06]
                                    hover:bg-white/[0.08] hover:border-[#C4A265]/20 transition-all duration-500">
                            <!-- Icon -->
                            <div class="w-12 h-12 md:w-14 md:h-14 mx-auto mb-4 rounded-xl bg-[#C4A265]/10 flex items-center justify-center
                                        group-hover:bg-[#C4A265]/20 group-hover:scale-110 transition-all duration-500">
                                <svg class="w-6 h-6 md:w-7 md:h-7 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="stat.icon" />
                                </svg>
                            </div>
                            <!-- Number -->
                            <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-white mb-1">
                                <StatCounter :end="stat.end" :suffix="stat.suffix" />
                            </div>
                            <!-- Label -->
                            <p class="text-[#C4A265]/70 text-xs md:text-sm font-medium">{{ isRtl ? stat.ar : stat.en }}</p>
                        </div>
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

                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-5">
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
        <!-- PACKAGES — 3 Specialty Cards           -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-14 md:py-24 relative overflow-hidden" v-if="packageBundles && packageBundles.length">
            <!-- Navy background -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#0f2847] via-[#1B365D] to-[#264573]"></div>
            <!-- Animated dot texture -->
            <div class="absolute inset-0 opacity-[0.04] pkg-texture-move"
                 style="background-image: radial-gradient(circle at 1px 1px, rgba(196,162,101,0.9) 1px, transparent 0); background-size: 24px 24px;"></div>
            <!-- Glow -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_rgba(196,162,101,0.06)_0%,_transparent_60%)]"></div>
            <!-- Gold lines -->
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#C4A265]/30 to-transparent"></div>
            <div class="absolute bottom-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#C4A265]/30 to-transparent"></div>

            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <!-- Header -->
                <div class="text-center mb-10 md:mb-14" v-scroll-reveal="{ type: 'fade-up' }">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-[#C4A265]/10 border border-[#C4A265]/20 text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-4">
                        {{ t('packages') }}
                    </span>
                    <h2 class="text-2xl sm:text-3xl lg:text-5xl font-bold text-white mb-3">
                        {{ isRtl ? 'باقات لكل تخصص' : 'Packages Per Specialty' }}
                    </h2>
                    <div class="w-12 h-[2px] bg-[#C4A265]/50 mx-auto mb-4"></div>
                    <p class="text-white/50 text-sm md:text-lg max-w-xl mx-auto">
                        {{ isRtl ? 'اختر تخصصك واكتشف الباقات المصممة خصيصاً لك' : 'Choose your specialty and discover packages designed for you' }}
                    </p>
                </div>

                <!-- 3 Module Cards — side by side on mobile -->
                <div class="grid grid-cols-3 gap-3 md:gap-6" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                    <a v-for="(mod, mi) in packageModules" :key="mod.slug"
                       :href="localizedRoute('/package-bundles') + '?module=' + mod.slug"
                       class="group relative rounded-xl md:rounded-2xl overflow-hidden text-center
                              bg-white/[0.04] border border-white/[0.08] backdrop-blur-sm
                              hover:bg-white/[0.1] hover:border-[#C4A265]/30
                              transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#C4A265]/10
                              p-4 md:p-8">

                        <!-- Hover glow -->
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none
                                    bg-[radial-gradient(circle_at_center,_rgba(196,162,101,0.08)_0%,_transparent_70%)]"></div>

                        <!-- Icon circle -->
                        <div class="relative w-14 h-14 md:w-20 md:h-20 mx-auto mb-3 md:mb-5 rounded-full
                                    flex items-center justify-center
                                    bg-[#C4A265]/10 border border-[#C4A265]/20
                                    group-hover:bg-[#C4A265]/20 group-hover:scale-110 group-hover:border-[#C4A265]/40
                                    transition-all duration-500">
                            <svg class="w-6 h-6 md:w-9 md:h-9 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      :d="mod.slug === 'dental'
                                          ? 'M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0'
                                          : mod.slug === 'pediatric'
                                          ? 'M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z'
                                          : 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12'" />
                            </svg>
                        </div>

                        <!-- Title -->
                        <h3 class="text-xs md:text-lg font-bold text-white mb-1 md:mb-2 group-hover:text-[#C4A265] transition-colors leading-tight">
                            {{ isRtl ? mod.name_ar : mod.name_en }}
                        </h3>

                        <!-- Package count -->
                        <p class="text-white/40 text-[10px] md:text-sm mb-2 md:mb-4">
                            {{ (packageBundles || []).filter(b => b.module === mod.slug).length }}
                            {{ isRtl ? 'باقات' : 'packages' }}
                        </p>

                        <!-- CTA arrow — hidden on mobile -->
                        <div class="hidden md:flex items-center justify-center gap-1.5 text-[#C4A265] text-sm font-semibold
                                    opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0
                                    transition-all duration-400">
                            <span>{{ isRtl ? 'اكتشف الباقات' : 'View Packages' }}</span>
                            <svg class="w-4 h-4" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </div>
                    </a>
                </div>

                <!-- View all -->
                <div class="text-center mt-8 md:mt-10" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                    <Link :href="localizedRoute('/package-bundles')"
                          class="inline-flex items-center gap-2 px-6 py-2.5 md:px-7 md:py-3 border border-[#C4A265]/30 text-[#C4A265] font-semibold rounded-lg
                                 hover:bg-[#C4A265] hover:text-[#1B365D] transition-all duration-300 text-xs md:text-sm">
                        {{ t('view_all_packages') }}
                        <svg class="w-3.5 h-3.5 md:w-4 md:h-4" :class="{ 'rotate-180': isRtl }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </section>


        <!-- ═══════════════════════════════════════ -->
        <!-- TESTIMONIALS                           -->
        <!-- ═══════════════════════════════════════ -->
        <section class="py-14 md:py-24 bg-[#FAFBFD] relative overflow-hidden" v-if="testimonials && testimonials.length">
            <!-- Background texture -->
            <div class="absolute inset-0 opacity-[0.015]"
                 style="background-image: radial-gradient(circle at 1px 1px, #1B365D 1px, transparent 0); background-size: 40px 40px;"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-10 md:mb-14" v-scroll-reveal="{ type: 'fade-up' }">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-[#1B365D]/5 text-[#1B365D] text-xs font-semibold tracking-wider uppercase mb-4">
                        {{ isRtl ? 'آراء المرضى' : 'Patient Reviews' }}
                    </span>
                    <h2 class="text-2xl sm:text-3xl lg:text-5xl font-bold text-[#1B365D] mb-3">
                        {{ isRtl ? 'ماذا يقول مرضانا' : 'What Our Patients Say' }}
                    </h2>
                    <div class="w-12 h-[2px] bg-[#C4A265]/50 mx-auto mb-4"></div>
                    <p class="text-gray-500 text-sm md:text-lg max-w-2xl mx-auto">
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
        <section class="relative py-16 md:py-24 overflow-hidden">
            <!-- Warm light background -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#f8f6f1] via-[#f0ebe0] to-[#f5f2ec]"></div>
            <!-- Subtle dot texture -->
            <div class="absolute inset-0 opacity-[0.03]"
                 style="background-image: radial-gradient(circle at 1px 1px, #1B365D 1px, transparent 0); background-size: 40px 40px;"></div>
            <!-- Gold radial glow -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] bg-[radial-gradient(ellipse,_rgba(196,162,101,0.06)_0%,_transparent_65%)]"></div>
            <!-- Decorative circles -->
            <div class="absolute -top-20 -right-20 w-72 h-72 border border-[#1B365D]/[0.04] rounded-full cta-orbit"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 border border-[#C4A265]/[0.06] rounded-full cta-orbit" style="animation-direction: reverse; animation-duration: 50s;"></div>

            <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-16">

                    <!-- Left/Top: Text content -->
                    <div class="flex-1 text-center lg:text-start">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#1B365D]/5 border border-[#1B365D]/10 mb-5"
                             v-scroll-reveal="{ type: 'fade-up' }">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#C4A265] opacity-50"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#C4A265]"></span>
                            </span>
                            <span class="text-[#1B365D] text-[11px] font-semibold tracking-wider uppercase">{{ isRtl ? 'متاح الآن' : 'Available Now' }}</span>
                        </div>

                        <h2 class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-bold text-[#1B365D] mb-4 leading-tight" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                            {{ isRtl ? 'صحتك تستحق' : 'Your Health' }}
                            <span class="relative inline-block">
                                <span class="relative z-10 text-[#C4A265]">{{ isRtl ? 'الأفضل' : 'Deserves the Best' }}</span>
                                <span class="absolute bottom-1 inset-x-0 h-2 bg-[#C4A265]/15 rounded-full -z-0"></span>
                            </span>
                        </h2>

                        <p class="text-[#1B365D]/50 text-sm md:text-base lg:text-lg mb-8 max-w-lg mx-auto lg:mx-0 leading-relaxed" v-scroll-reveal="{ type: 'fade-up', delay: 150 }">
                            {{ isRtl ? 'تواصل معنا الآن واحصل على استشارة مجانية مع أفضل أطبائنا المتخصصين في مختلف المجالات الطبية' : 'Contact us now and get a free consultation with our top specialized doctors across all medical fields' }}
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                            <a :href="whatsappLink" target="_blank" rel="noopener noreferrer"
                               class="group relative inline-flex items-center gap-2.5 px-7 py-3.5 bg-[#25D366] text-white font-bold text-sm rounded-xl
                                      transition-all duration-300 shadow-lg shadow-[#25D366]/20 hover:shadow-xl hover:shadow-[#25D366]/30 hover:-translate-y-0.5 overflow-hidden">
                                <span class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/15 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                                <svg class="w-5 h-5 relative" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                </svg>
                                <span class="relative">{{ isRtl ? 'تواصل عبر واتساب' : 'Chat on WhatsApp' }}</span>
                            </a>
                            <Link :href="localizedRoute('/booking')"
                                  class="group relative inline-flex items-center gap-2.5 px-7 py-3.5 bg-[#1B365D] text-white font-bold text-sm rounded-xl
                                         transition-all duration-300 shadow-lg shadow-[#1B365D]/20 hover:shadow-xl hover:shadow-[#1B365D]/30 hover:-translate-y-0.5 overflow-hidden">
                                <span class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                                <svg class="w-5 h-5 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="relative">{{ isRtl ? 'احجز موعدك' : 'Book Appointment' }}</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Right/Bottom: Trust cards -->
                    <div class="flex-shrink-0 w-full lg:w-auto" v-scroll-reveal="{ type: 'fade-up', delay: 250 }">
                        <div class="grid grid-cols-3 lg:grid-cols-1 gap-3 lg:gap-4 max-w-sm mx-auto lg:mx-0">
                            <!-- Free Consultation -->
                            <div class="group flex flex-col lg:flex-row items-center lg:items-center gap-2 lg:gap-4 p-3 lg:p-4 rounded-xl
                                        bg-white/70 border border-[#1B365D]/[0.06] shadow-sm
                                        hover:bg-white hover:shadow-md hover:border-[#C4A265]/20 transition-all duration-400">
                                <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center flex-shrink-0
                                            group-hover:bg-emerald-100 group-hover:scale-105 transition-all duration-400">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622A11.99 11.99 0 0020.402 6a11.959 11.959 0 00-8.402-3.785z"/>
                                    </svg>
                                </div>
                                <div class="text-center lg:text-start">
                                    <p class="text-[#1B365D] text-[11px] lg:text-sm font-semibold leading-tight">{{ isRtl ? 'استشارة مجانية' : 'Free Consultation' }}</p>
                                    <p class="text-[#1B365D]/35 text-[10px] lg:text-xs hidden lg:block mt-0.5">{{ isRtl ? 'أول زيارة مجاناً' : 'First visit is free' }}</p>
                                </div>
                            </div>

                            <!-- Certified Doctors -->
                            <div class="group flex flex-col lg:flex-row items-center lg:items-center gap-2 lg:gap-4 p-3 lg:p-4 rounded-xl
                                        bg-white/70 border border-[#1B365D]/[0.06] shadow-sm
                                        hover:bg-white hover:shadow-md hover:border-[#C4A265]/20 transition-all duration-400">
                                <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-xl bg-[#C4A265]/10 border border-[#C4A265]/15 flex items-center justify-center flex-shrink-0
                                            group-hover:bg-[#C4A265]/20 group-hover:scale-105 transition-all duration-400">
                                    <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                                    </svg>
                                </div>
                                <div class="text-center lg:text-start">
                                    <p class="text-[#1B365D] text-[11px] lg:text-sm font-semibold leading-tight">{{ isRtl ? 'أطباء معتمدون' : 'Certified Doctors' }}</p>
                                    <p class="text-[#1B365D]/35 text-[10px] lg:text-xs hidden lg:block mt-0.5">{{ isRtl ? 'خبرة +10 سنوات' : '10+ years experience' }}</p>
                                </div>
                            </div>

                            <!-- Guaranteed Results -->
                            <div class="group flex flex-col lg:flex-row items-center lg:items-center gap-2 lg:gap-4 p-3 lg:p-4 rounded-xl
                                        bg-white/70 border border-[#1B365D]/[0.06] shadow-sm
                                        hover:bg-white hover:shadow-md hover:border-[#C4A265]/20 transition-all duration-400">
                                <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center flex-shrink-0
                                            group-hover:bg-rose-100 group-hover:scale-105 transition-all duration-400">
                                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                                    </svg>
                                </div>
                                <div class="text-center lg:text-start">
                                    <p class="text-[#1B365D] text-[11px] lg:text-sm font-semibold leading-tight">{{ isRtl ? 'نتائج مضمونة' : 'Guaranteed Results' }}</p>
                                    <p class="text-[#1B365D]/35 text-[10px] lg:text-xs hidden lg:block mt-0.5">{{ isRtl ? 'رضا 100%' : '100% satisfaction' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </FrontendLayout>
</template>

<style scoped>
/* ═══ STATS SECTION TEXTURES ═══════════════════ */

/* Hexagonal SVG pattern */
.stats-hex-pattern {
    background-image: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='70' viewBox='0 0 60 70'%3E%3Cpath d='M30 0l30 17.5v35L30 70 0 52.5v-35z' fill='none' stroke='%23C4A265' stroke-width='0.5'/%3E%3C/svg%3E");
    background-size: 60px 70px;
}

/* Medical cross pattern */
.stats-cross-pattern {
    background-image: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cpath d='M36 32h8v4h-4v8h-4v-8h-4v-4h4z' fill='%23C4A265'/%3E%3C/svg%3E");
    background-size: 80px 80px;
}

/* Animated floating blobs */
.stats-blob-1 {
    animation: statsBlobFloat 18s ease-in-out infinite;
}
.stats-blob-2 {
    animation: statsBlobFloat 22s ease-in-out infinite reverse;
}
.stats-blob-3 {
    animation: statsBlobFloat 15s ease-in-out infinite;
    animation-delay: -5s;
}
@keyframes statsBlobFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25%      { transform: translate(30px, -40px) scale(1.05); }
    50%      { transform: translate(-20px, 30px) scale(0.95); }
    75%      { transform: translate(40px, 20px) scale(1.08); }
}

/* Diagonal light beams */
.stats-beam-bg {
    background: repeating-linear-gradient(
        135deg,
        transparent 0px,
        transparent 80px,
        rgba(196, 162, 101, 0.025) 80px,
        rgba(196, 162, 101, 0.025) 82px
    );
}

/* SVG wave animation */
.stats-wave {
    animation: statsWaveShift 8s ease-in-out infinite;
}
@keyframes statsWaveShift {
    0%, 100% { transform: translateX(0); }
    50%      { transform: translateX(-30px); }
}

/* Pulse ring animation */
.stats-pulse-ring {
    animation: statsPulseRing 3s ease-out infinite;
    transform: scale(0.3);
    opacity: 0;
}
@keyframes statsPulseRing {
    0% { transform: scale(0.3); opacity: 0.8; }
    100% { transform: scale(1.5); opacity: 0; }
}

/* Floating medical icons */
.stats-float-icon {
    animation: statsFloatIcon 8s ease-in-out infinite;
}
@keyframes statsFloatIcon {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    50%      { transform: translate(12px, -15px) rotate(5deg); }
}

/* Heartbeat line animation */
.stats-heartbeat {
    stroke-dasharray: 2500;
    stroke-dashoffset: 2500;
    animation: statsHeartbeat 6s ease-in-out infinite;
}
@keyframes statsHeartbeat {
    0%   { stroke-dashoffset: 2500; }
    50%  { stroke-dashoffset: 0; }
    100% { stroke-dashoffset: -2500; }
}

/* Orbit rotation */
.stats-orbit {
    animation: statsOrbitKf 25s linear infinite;
}
@keyframes statsOrbitKf {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}

/* ─── Specialties mobile slider ─────────── */
.spec-dot {
    width: 28px;
    height: 6px;
    border-radius: 9999px;
    background: rgba(27, 54, 93, 0.15);
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
    cursor: pointer;
    border: none;
    padding: 0;
}
.spec-dot-active {
    width: 40px;
    box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.2);
}

.spec-mobile-card {
    box-shadow: 0 10px 40px -8px rgba(27, 54, 93, 0.25);
}

.spec-slide-img {
    transition: transform 6s ease-out;
    transform: scale(1);
}
.spec-slide-active .spec-slide-img {
    transform: scale(1.08);
}

.spec-slide-content {
    transform: translateY(12px);
    opacity: 0;
    transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.15s, opacity 0.7s ease 0.15s;
}
.spec-slide-active .spec-slide-content {
    transform: translateY(0);
    opacity: 1;
}

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

/* Stats orbit */
@keyframes statsOrbit {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.stats-orbit {
    animation: statsOrbit 35s linear infinite;
}

/* CTA grid animation */
@keyframes ctaGridMove {
    0%   { background-position: 0 0; }
    100% { background-position: 60px 60px; }
}
.cta-grid-move {
    animation: ctaGridMove 12s linear infinite;
}

/* CTA orbit */
@keyframes ctaOrbitSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.cta-orbit {
    animation: ctaOrbitSpin 40s linear infinite;
}

/* Animated package texture */
@keyframes pkgTextureMove {
    0%   { background-position: 0 0; }
    100% { background-position: 24px 24px; }
}
.pkg-texture-move {
    animation: pkgTextureMove 8s linear infinite;
}

/* Package card transitions */
.pkg-card-enter-active { transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
.pkg-card-leave-active { transition: all 0.3s ease-in; }
.pkg-card-enter-from { opacity: 0; transform: translateY(20px) scale(0.95); }
.pkg-card-leave-to { opacity: 0; transform: translateY(-10px) scale(0.95); }
.pkg-card-move { transition: all 0.5s ease; }
</style>
