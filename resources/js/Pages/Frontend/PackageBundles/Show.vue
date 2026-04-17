<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { useCurrency } from '@/Composables/useCurrency';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    bundle: { type: Object, required: true },
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();
const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const bundleName = computed(() => localized(props.bundle, 'name'));
const bundleDescription = computed(() => localized(props.bundle, 'description'));

const totalSessions = computed(() => {
    if (!props.bundle.services) return 0;
    return props.bundle.services.reduce((sum, s) => sum + (s.sessions_count || 1), 0);
});

const discountPercent = computed(() => {
    const original = Number(props.bundle.original_price);
    const total = Number(props.bundle.total_price);
    if (!original || original <= total) return 0;
    return Math.round(((original - total) / original) * 100);
});

function serviceName(bundleService) {
    return bundleService.service ? localized(bundleService.service, 'name') : '';
}

function serviceShortDesc(bundleService) {
    return bundleService.service ? localized(bundleService.service, 'short_desc') : '';
}
</script>

<template>
    <div>
        <SeoHead :title="bundleName" :description="bundleDescription" />

        <!-- Breadcrumb -->
        <nav class="bg-off-white border-b border-beige/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <ol class="flex items-center text-sm flex-wrap gap-1">
                    <li>
                        <Link :href="localizedRoute('/')" class="text-gold-primary hover:text-gold-dark transition-colors duration-200">
                            {{ t('home') }}
                        </Link>
                    </li>
                    <li class="text-charcoal/30 mx-1">
                        <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </li>
                    <li>
                        <Link :href="localizedRoute('/package-bundles')" class="text-gold-primary hover:text-gold-dark transition-colors duration-200">
                            {{ t('packages') }}
                        </Link>
                    </li>
                    <li class="text-charcoal/30 mx-1">
                        <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </li>
                    <li class="text-charcoal/70 font-medium">
                        {{ bundleName }}
                    </li>
                </ol>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative h-72 md:h-80 bg-charcoal overflow-hidden">
            <div v-if="bundle.image_url" class="absolute inset-0">
                <img :src="bundle.image_url" :alt="bundleName" class="w-full h-full object-cover opacity-40" />
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black-deep/90 via-charcoal/60 to-transparent"></div>
            <div class="absolute inset-0 pointer-events-none texture-diamond"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-8 end-[12%] w-16 h-16 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-14 start-[10%] w-12 h-12 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/3 start-[25%] w-8 h-8 rounded-full bg-gold-primary/4 animate-float-delay"></div>
            <div class="relative z-10 flex flex-col items-center justify-end h-full text-center px-4 pb-10">
                <!-- Discount badge -->
                <div v-if="discountPercent > 0" class="mb-3" v-scroll-reveal="{ type: 'fade-down' }">
                    <span class="inline-block px-4 py-1.5 bg-emerald-500/90 text-white text-sm font-bold rounded-full">
                        {{ t('save_amount') }} {{ discountPercent }}%
                    </span>
                </div>
                <h1 class="text-3xl md:text-5xl font-bold text-white mb-2" v-scroll-reveal="{ type: 'blur-in', duration: 800 }">
                    {{ bundleName }}
                </h1>
                <p v-if="bundleDescription" class="text-beige/70 text-lg max-w-2xl mt-2" v-scroll-reveal="{ type: 'fade-up', delay: 150 }">
                    {{ bundleDescription }}
                </p>
            </div>
        </section>

        <!-- Success Flash -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div v-if="successMessage" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-emerald-700 text-sm font-medium">{{ successMessage }}</p>
                </div>
            </div>
        </Transition>

        <!-- Package Details: Two-Column Layout -->
        <section class="relative py-12 md:py-20 bg-off-white overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-dots"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-10 start-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-20 end-10 w-14 h-14 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-10 lg:gap-14">
                    <!-- Main Content (2/3) -->
                    <div class="lg:w-2/3" v-scroll-reveal="{ type: 'fade-up' }">
                        <!-- Package Image -->
                        <div v-if="bundle.image_url" class="bg-white rounded-2xl overflow-hidden shadow-sm border border-beige/30 mb-8 img-hover-reveal">
                            <img :src="bundle.image_url" :alt="bundleName" class="w-full h-64 md:h-80 object-cover" />
                        </div>

                        <!-- Package Description -->
                        <div v-if="bundleDescription" class="bg-white rounded-2xl p-6 md:p-10 shadow-sm border border-beige/30 mb-8">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 rounded-full bg-gold-primary/10 flex items-center justify-center me-3">
                                    <svg class="w-5 h-5 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-charcoal">{{ t('package_summary') }}</h2>
                            </div>
                            <p class="text-charcoal/70 leading-relaxed text-base">{{ bundleDescription }}</p>
                        </div>

                        <!-- Quick Stats -->
                        <div class="grid grid-cols-3 gap-4 mb-8" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                            <div class="bg-white rounded-2xl p-5 shadow-sm border border-beige/30 text-center card-hover-lift card-premium">
                                <div class="w-10 h-10 mx-auto rounded-full bg-gold-primary/10 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div class="text-2xl font-bold text-charcoal">{{ bundle.services?.length || 0 }}</div>
                                <div class="text-xs text-charcoal/60 mt-1">{{ t('services') }}</div>
                            </div>
                            <div class="bg-white rounded-2xl p-5 shadow-sm border border-beige/30 text-center card-hover-lift card-premium">
                                <div class="w-10 h-10 mx-auto rounded-full bg-gold-primary/10 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-2xl font-bold text-charcoal">{{ totalSessions }}</div>
                                <div class="text-xs text-charcoal/60 mt-1">{{ t('sessions') }}</div>
                            </div>
                            <div class="bg-white rounded-2xl p-5 shadow-sm border border-beige/30 text-center card-hover-lift card-premium">
                                <div class="w-10 h-10 mx-auto rounded-full bg-emerald-500/10 flex items-center justify-center mb-3">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-2xl font-bold text-emerald-600">{{ discountPercent }}%</div>
                                <div class="text-xs text-charcoal/60 mt-1">{{ t('save_amount') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar (1/3) -->
                    <div class="lg:w-1/3 space-y-6" v-scroll-reveal="{ type: 'fade-up', delay: 150 }">
                        <!-- Pricing Card -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-beige/30 card-hover-lift card-premium" v-scroll-reveal="{ type: 'fade-left', delay: 200 }">
                            <h3 class="text-lg font-bold text-charcoal mb-5 flex items-center">
                                <svg class="w-5 h-5 text-gold-primary me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ t('package_price') }}
                            </h3>
                            <div class="space-y-4">
                                <!-- Bundle price -->
                                <div class="flex items-center justify-between py-3 border-b border-beige/30">
                                    <span class="text-charcoal/60 text-sm">{{ t('package_price') }}</span>
                                    <span class="text-2xl font-bold text-gold-primary">{{ formatCurrency(bundle.total_price) }}</span>
                                </div>
                                <!-- Original price -->
                                <div v-if="Number(bundle.original_price) > Number(bundle.total_price)" class="flex items-center justify-between py-3 border-b border-beige/30">
                                    <span class="text-charcoal/60 text-sm">{{ t('original_price_label') }}</span>
                                    <span class="text-charcoal/50 line-through text-sm">{{ formatCurrency(bundle.original_price) }}</span>
                                </div>
                                <!-- Savings -->
                                <div v-if="Number(bundle.savings) > 0" class="flex items-center justify-between py-3 bg-emerald-50 rounded-xl px-4 -mx-1">
                                    <span class="text-emerald-700 text-sm font-medium">{{ t('you_save') }}</span>
                                    <span class="text-emerald-600 font-bold text-sm">{{ formatCurrency(bundle.savings) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Book This Package -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-beige/30 card-hover-lift card-premium" v-scroll-reveal="{ type: 'fade-left', delay: 300 }">
                            <h3 class="text-lg font-bold text-charcoal mb-4">
                                {{ t('book_this_package') }}
                            </h3>
                            <p class="text-sm text-charcoal/60 mb-5">
                                {{ t('we_will_call_you') }}
                            </p>
                            <Link
                                :href="localizedRoute(`/package-bundles/${bundle.id}/book`)"
                                class="w-full inline-flex items-center justify-center px-6 py-3 gold-gradient text-white font-bold rounded-full hover:opacity-90 transition-opacity duration-300 shadow-md mb-3 btn-shimmer"
                            >
                                <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ t('book_package') }}
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
                                {{ t('whatsapp') }}
                            </a>
                        </div>

                        <!-- Trust Badges -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-beige/30" v-scroll-reveal="{ type: 'fade-left', delay: 400 }">
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gold-primary/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-charcoal/70">{{ t('secure_booking') }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gold-primary/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-charcoal/70">{{ t('we_will_call_you') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Included Services -->
        <section v-if="bundle.services && bundle.services.length" class="relative py-12 md:py-20 bg-white overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-diagonal"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-12 end-12 w-18 h-18 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-16 start-16 w-12 h-12 rounded-full bg-gold-primary/6 animate-float-slow"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                        <h2 class="text-3xl font-bold text-charcoal mb-3">
                            {{ t('package_services') }}
                        </h2>
                        <div class="flex items-center justify-center gap-3">
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                            <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                        </div>
                        <p class="text-charcoal/60 text-sm mt-4">
                            {{ bundle.services.length }} {{ t('services') }} &middot; {{ totalSessions }} {{ t('sessions') }}
                        </p>
                    </div>

                    <div class="space-y-4" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                        <div
                            v-for="(bs, index) in bundle.services"
                            :key="bs.id"
                            class="flex items-start gap-4 p-5 bg-off-white rounded-xl shadow-sm border border-beige/30 card-hover-lift card-premium"
                        >
                            <!-- Number -->
                            <div class="flex-shrink-0 w-10 h-10 rounded-full gold-gradient flex items-center justify-center text-white font-bold text-sm">
                                {{ index + 1 }}
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-bold text-charcoal mb-1">{{ serviceName(bs) }}</h3>
                                <p v-if="serviceShortDesc(bs)" class="text-sm text-charcoal/60 leading-relaxed mb-3">{{ serviceShortDesc(bs) }}</p>
                                <div class="flex flex-wrap items-center gap-3 text-xs">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-beige/50 text-charcoal/70">
                                        <svg class="w-3.5 h-3.5 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ bs.sessions_count || 1 }} {{ t('sessions') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white rounded-full border border-beige/50 font-bold text-gold-primary">
                                        {{ formatCurrency(bs.bundle_price) }}
                                    </span>
                                    <span v-if="Number(bs.discount_percentage) > 0" class="inline-flex items-center px-3 py-1.5 bg-emerald-50 text-emerald-600 font-bold rounded-full">
                                        -{{ bs.discount_percentage }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Price Summary -->
        <section class="relative py-12 md:py-20 bg-off-white overflow-hidden">
            <div class="absolute inset-0 pointer-events-none texture-crosshatch"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-8 start-8 w-16 h-16 rounded-full bg-gold-primary/5 animate-float-slow"></div>
            <div class="absolute bottom-12 end-16 w-20 h-20 rounded-full bg-gold-primary/4 animate-float"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-lg mx-auto">
                    <div class="text-center mb-10" v-scroll-reveal="{ type: 'blur-in' }">
                        <div class="flex items-center justify-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-gold-primary/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gold-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-3xl font-bold text-charcoal mb-3">{{ t('package_price') }}</h2>
                        <div class="flex items-center justify-center gap-3">
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                            <span class="w-2 h-2 rounded-full bg-gold-primary"></span>
                            <span class="h-px w-12 bg-gold-primary/40"></span>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-beige/30 overflow-hidden" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                        <div class="p-6 space-y-4">
                            <div v-if="Number(bundle.original_price) > Number(bundle.total_price)" class="flex items-center justify-between py-3 border-b border-beige/30">
                                <span class="text-charcoal/60 text-sm">{{ t('original_price_label') }}</span>
                                <span class="text-charcoal/50 line-through">{{ formatCurrency(bundle.original_price) }}</span>
                            </div>
                            <div v-if="Number(bundle.savings) > 0" class="flex items-center justify-between py-3 bg-emerald-50 rounded-xl px-4 -mx-1">
                                <span class="text-emerald-700 font-medium text-sm">{{ t('you_save') }}</span>
                                <span class="text-emerald-600 font-bold">-{{ formatCurrency(bundle.savings) }}</span>
                            </div>
                            <div class="flex items-center justify-between py-4 border-t-2 border-beige/50 mt-2">
                                <span class="text-lg font-bold text-charcoal">{{ t('total') }}</span>
                                <span class="text-3xl font-bold text-gold-primary">{{ formatCurrency(bundle.total_price) }}</span>
                            </div>
                        </div>
                    </div>
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
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4" v-scroll-reveal="{ type: 'blur-in' }">
                    {{ t('book_this_package') }}
                </h2>
                <p class="text-white/80 text-lg mb-8 max-w-2xl mx-auto" v-scroll-reveal="{ type: 'fade-up', delay: 100 }">
                    {{ t('packages_hero_subtitle') }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                    <Link
                        :href="localizedRoute(`/package-bundles/${bundle.id}/book`)"
                        class="inline-flex items-center px-8 py-3.5 bg-white text-gold-primary font-bold rounded-full hover:bg-off-white transition-colors duration-300 shadow-lg hover:shadow-xl btn-shimmer"
                    >
                        <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ t('book_package') }}
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
                        {{ t('whatsapp') }}
                    </a>
                </div>
            </div>
        </section>
    </div>
</template>
