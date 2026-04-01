<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    seo: Object,
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();

const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

const equipment = [
    {
        name_ar: 'جهاز ليزر ديود لإزالة الشعر',
        name_en: 'Diode Laser Hair Removal',
        icon: 'M13 10V3L4 14h7v7l9-11h-7z',
    },
    {
        name_ar: 'ليزر فراكشنال',
        name_en: 'Fractional Laser',
        icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    },
    {
        name_ar: 'ليزر إكسيمر',
        name_en: 'Excimer Laser',
        icon: 'M13 10V3L4 14h7v7l9-11h-7z',
    },
    {
        name_ar: 'جهاز هيدرافيشل',
        name_en: 'HydraFacial Machine',
        icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    },
    {
        name_ar: 'جهاز الديرموسكوبي',
        name_en: 'Dermoscopy Device',
        icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7',
    },
    {
        name_ar: "جهاز وود لايت",
        name_en: "Wood's Light",
        icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    },
    {
        name_ar: 'ديرمابن',
        name_en: 'Dermapen',
        icon: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
    },
];
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
                <div class="absolute top-10 start-10 w-40 h-40 border border-gold-primary/30 rounded-full animate-float"></div>
                <div class="absolute bottom-5 end-20 w-60 h-60 border border-gold-light/20 rounded-full animate-float-slow"></div>
            </div>
            <!-- Floating decorative elements -->
            <div class="absolute top-6 end-[15%] w-16 h-16 rounded-full bg-gold-primary/5 animate-float-delay"></div>
            <div class="absolute bottom-10 start-[20%] w-10 h-10 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-4">
                <h1
                    class="text-4xl md:text-5xl font-bold text-white mb-4"
                    v-scroll-reveal="{ type: 'blur-in', duration: 800 }"
                >
                    {{ t('our_services', { default: locale === 'ar' ? 'خدماتنا' : 'Our Services' }) }}
                </h1>
                <p
                    class="text-lg text-beige/80 max-w-2xl"
                    v-scroll-reveal="{ type: 'fade-up', delay: 200 }"
                >
                    {{ locale === 'ar'
                        ? 'نقدم لكم أحدث التقنيات وأفضل العلاجات في عالم الجلدية والتجميل'
                        : 'We offer the latest technologies and best treatments in dermatology and aesthetics'
                    }}
                </p>
                <div class="mt-4 w-20 h-1 bg-gold-primary rounded-full" v-scroll-reveal="{ type: 'zoom-in', delay: 400 }"></div>
            </div>
        </section>

        <!-- Services by Category -->
        <section class="relative py-16 md:py-24 bg-off-white overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-dots"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-10 start-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-20 end-10 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/2 end-[5%] w-8 h-8 rounded-full bg-gold-primary/6 animate-float-delay"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div
                    v-for="(category, catIndex) in categories"
                    :key="category.id"
                    class="mb-16 last:mb-0"
                    v-scroll-reveal="{ type: 'fade-up', delay: catIndex * 100 }"
                >
                    <!-- Category Header -->
                    <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                        <h2 class="text-3xl md:text-4xl font-bold text-charcoal mb-3">
                            {{ localized(category, 'name') }}
                        </h2>
                        <div class="flex items-center justify-center gap-3">
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                            <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                        </div>
                        <p
                            v-if="localized(category, 'description')"
                            class="mt-4 text-charcoal/60 max-w-2xl mx-auto"
                        >
                            {{ localized(category, 'description') }}
                        </p>
                    </div>

                    <!-- Service Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                        <Link
                            v-for="(service, svcIndex) in category.services"
                            :key="service.id"
                            :href="localizedRoute(`/services/${service.slug}`)"
                            class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden border border-beige/50 hover:border-gold-primary/30 card-hover-lift card-premium"
                        >
                            <!-- Card Image / Icon Area -->
                            <div class="relative h-48 bg-gradient-to-br from-beige to-off-white overflow-hidden img-hover-reveal">
                                <img
                                    v-if="service.image || service.featured_image"
                                    :src="service.image || service.featured_image"
                                    :alt="localized(service, 'name')"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                />
                                <div
                                    v-else
                                    class="flex items-center justify-center w-full h-full"
                                >
                                    <div class="w-20 h-20 rounded-full bg-gold-primary/10 flex items-center justify-center group-hover:bg-gold-primary/20 transition-colors duration-300">
                                        <svg class="w-10 h-10 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <!-- Category Badge -->
                                <div class="absolute top-3 start-3">
                                    <span class="inline-block px-3 py-1 text-xs font-medium bg-gold-primary/90 text-white rounded-full">
                                        {{ localized(category, 'name') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Card Content -->
                            <div class="p-5">
                                <h3 class="text-lg font-bold text-charcoal group-hover:text-gold-primary transition-colors duration-300 mb-2">
                                    {{ localized(service, 'name') }}
                                </h3>
                                <p class="text-sm text-charcoal/60 line-clamp-3 mb-4 leading-relaxed">
                                    {{ localized(service, 'short_desc') || localized(service, 'description') }}
                                </p>
                                <div class="flex items-center text-gold-primary text-sm font-medium">
                                    <span>{{ locale === 'ar' ? 'عرض التفاصيل' : 'View Details' }}</span>
                                    <svg
                                        class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1 rtl:group-hover:-translate-x-1"
                                        :class="isRtl ? 'me-1 rotate-180' : 'ms-1'"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="categories.length === 0"
                    class="text-center py-20"
                >
                    <div class="w-24 h-24 rounded-full bg-beige flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gold-primary/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-charcoal mb-2">
                        {{ locale === 'ar' ? 'لا توجد خدمات حالياً' : 'No services available' }}
                    </h3>
                    <p class="text-charcoal/50">
                        {{ locale === 'ar' ? 'سيتم إضافة الخدمات قريباً' : 'Services will be added soon' }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Equipment Section -->
        <section class="relative py-16 md:py-24 bg-white overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-diagonal"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-14 end-14 w-24 h-24 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-16 start-8 w-16 h-16 rounded-full bg-gold-primary/6 animate-float-slow"></div>
            <div class="absolute top-1/3 start-[8%] w-10 h-10 rounded-full bg-gold-primary/4 animate-float-delay"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Section Header -->
                <div class="text-center mb-14" v-scroll-reveal="{ type: 'blur-in' }">
                    <span class="inline-block px-4 py-1.5 bg-gold-primary/10 text-gold-primary text-sm font-medium rounded-full mb-4">
                        {{ locale === 'ar' ? 'تقنيات حديثة' : 'Modern Technology' }}
                    </span>
                    <h2 class="text-3xl md:text-4xl font-bold text-charcoal mb-3">
                        {{ locale === 'ar' ? 'الأجهزة المستخدمة' : 'Equipment Used' }}
                    </h2>
                    <div class="flex items-center justify-center gap-3 mb-4">
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                        <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                        <span class="h-px w-12 bg-gold-primary/40"></span>
                    </div>
                    <p class="text-charcoal/60 max-w-2xl mx-auto">
                        {{ locale === 'ar'
                            ? 'نستخدم أحدث الأجهزة والتقنيات العالمية لضمان أفضل النتائج لعملائنا'
                            : 'We use the latest global equipment and technologies to ensure the best results for our clients'
                        }}
                    </p>
                </div>

                <!-- Equipment Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 lg:gap-6" v-scroll-reveal="{ type: 'stagger', staggerDelay: 80 }">
                    <div
                        v-for="(item, index) in equipment"
                        :key="index"
                        class="group bg-off-white rounded-xl p-6 text-center hover:bg-white hover:shadow-lg transition-all duration-500 border border-transparent hover:border-gold-primary/20 card-hover-lift"
                    >
                        <!-- Placeholder Image / Icon -->
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-gold-primary/10 to-gold-light/10 flex items-center justify-center group-hover:from-gold-primary/20 group-hover:to-gold-light/20 transition-all duration-300">
                            <svg class="w-8 h-8 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" />
                            </svg>
                        </div>
                        <h3 class="text-sm md:text-base font-semibold text-charcoal group-hover:text-gold-primary transition-colors duration-300 leading-snug">
                            {{ locale === 'ar' ? item.name_ar : item.name_en }}
                        </h3>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA - Book Now -->
        <section class="relative py-16 md:py-20 overflow-hidden">
            <div class="absolute inset-0 gold-gradient"></div>
            <div class="absolute inset-0 pointer-events-none texture-wave"></div>
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 start-0 w-96 h-96 bg-white rounded-full -translate-x-1/2 -translate-y-1/2 animate-float-slow"></div>
                <div class="absolute bottom-0 end-0 w-72 h-72 bg-white rounded-full translate-x-1/3 translate-y-1/3 animate-float"></div>
            </div>
            <!-- Floating decorative elements -->
            <div class="absolute top-8 end-[20%] w-12 h-12 rounded-full bg-white/5 animate-float-delay"></div>
            <div class="absolute bottom-12 start-[15%] w-8 h-8 rounded-full bg-white/8 animate-float"></div>
            <div class="relative z-10 max-w-4xl mx-auto px-4 text-center">
                <h2
                    class="text-3xl md:text-4xl font-bold text-white mb-4"
                    v-scroll-reveal="{ type: 'blur-in' }"
                >
                    {{ locale === 'ar'
                        ? 'هل أنت مستعد لبدء رحلة العناية ببشرتك؟'
                        : 'Ready to Start Your Skincare Journey?'
                    }}
                </h2>
                <p
                    class="text-white/80 text-lg mb-8 max-w-2xl mx-auto"
                    v-scroll-reveal="{ type: 'fade-up', delay: 100 }"
                >
                    {{ locale === 'ar'
                        ? 'احجز موعدك الآن واحصل على استشارة مجانية مع أطبائنا المتخصصين'
                        : 'Book your appointment now and get a free consultation with our specialists'
                    }}
                </p>
                <div
                    class="flex flex-col sm:flex-row items-center justify-center gap-4"
                    v-scroll-reveal="{ type: 'bounce-in', delay: 200 }"
                >
                    <Link
                        :href="localizedRoute('/booking')"
                        class="inline-flex items-center px-8 py-3.5 bg-white text-gold-primary font-bold rounded-full hover:bg-off-white transition-colors duration-300 shadow-lg hover:shadow-xl btn-shimmer"
                    >
                        <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ locale === 'ar' ? 'احجز موعدك الآن' : 'Book Now' }}
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
