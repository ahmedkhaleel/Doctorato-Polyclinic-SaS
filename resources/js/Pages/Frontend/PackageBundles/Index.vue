<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useCurrency } from '@/Composables/useCurrency';
import SeoHead from '@/Components/Frontend/SeoHead.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    bundles: { type: Array, default: () => [] },
    seo: Object,
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { formatCurrency, currencyCode } = useCurrency();

const seoTitle = computed(() => localized(props.seo, 'title') || t('package_bundles_title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

function totalSessions(bundle) {
    if (!bundle.services) return 0;
    return bundle.services.reduce((sum, s) => sum + (s.sessions_count || 1), 0);
}

function discountPercent(bundle) {
    const original = Number(bundle.original_price);
    const total = Number(bundle.total_price);
    if (!original || original <= total) return 0;
    return Math.round(((original - total) / original) * 100);
}
</script>

<template>
    <SeoHead :title="seoTitle" :description="seoDescription" />

    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <!-- Hero Section -->
        <section class="relative py-16 md:py-24 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-[#1E1E1E] via-[#2A2A2A] to-[#1E1E1E]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(196,162,101,0.15)_0%,_transparent_50%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,_rgba(196,162,101,0.1)_0%,_transparent_50%)]"></div>
            <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/50 to-transparent"></div>
            <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-[var(--brand-primary)]/50 to-transparent"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <nav class="flex items-center justify-center gap-2 text-sm text-white/40 mb-8">
                    <Link :href="localizedRoute('/')" class="hover:text-white/70 transition">{{ t('home') }}</Link>
                    <span>/</span>
                    <span class="text-[var(--brand-primary)]">{{ t('packages') }}</span>
                </nav>

                <div class="flex items-center justify-center gap-3 mb-5">
                    <span class="block w-10 h-px bg-gradient-to-r from-transparent to-[var(--brand-primary)]/70"></span>
                    <svg class="w-6 h-6 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span class="block w-10 h-px bg-gradient-to-l from-transparent to-[var(--brand-primary)]/70"></span>
                </div>

                <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">
                    {{ t('package_bundles_title') }}
                </h1>
                <p class="text-lg text-white/45 max-w-2xl mx-auto leading-relaxed">
                    {{ t('packages_hero_subtitle') }}
                </p>
            </div>
        </section>

        <!-- Bundles Grid -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20">
            <!-- Empty state -->
            <div v-if="bundles.length === 0" class="text-center py-20">
                <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-[var(--brand-primary)]/10 to-[var(--brand-primary)]/5 flex items-center justify-center">
                    <svg class="w-10 h-10 text-[var(--brand-primary)]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">{{ t('no_packages') }}</h3>
                <p class="text-gray-400 text-sm">{{ t('services_coming_soon') }}</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="bundle in bundles" :key="bundle.id"
                     class="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden
                            hover:shadow-2xl hover:shadow-[var(--brand-primary)]/[0.08] hover:-translate-y-1.5 transition-all duration-500">
                    <!-- Image -->
                    <div class="relative h-56 overflow-hidden">
                        <img v-if="bundle.image_url" :src="bundle.image_url" :alt="localized(bundle, 'name')"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
                        <div v-else class="w-full h-full bg-gradient-to-br from-[var(--brand-primary)]/20 to-[var(--brand-primary)]/5 flex items-center justify-center">
                            <svg class="w-20 h-20 text-[var(--brand-primary)]/20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <!-- Gradient overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                        <!-- Discount badge -->
                        <div v-if="discountPercent(bundle) > 0"
                             class="absolute top-4 start-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                            -{{ discountPercent(bundle) }}%
                        </div>
                        <!-- Bottom stats -->
                        <div class="absolute bottom-4 start-4 end-4 flex items-center justify-between">
                            <div class="flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-xs font-medium px-3 py-1.5 rounded-full">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                {{ bundle.services?.length || 0 }} {{ t('services_included') }}
                            </div>
                            <div class="flex items-center gap-1.5 bg-white/15 backdrop-blur-sm text-white text-xs font-medium px-3 py-1.5 rounded-full">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ totalSessions(bundle) }} {{ t('sessions') }}
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-[var(--brand-primary)] transition-colors duration-300">
                            {{ localized(bundle, 'name') }}
                        </h3>
                        <p v-if="localized(bundle, 'description')" class="text-sm text-gray-500 mb-5 line-clamp-2 leading-relaxed">
                            {{ localized(bundle, 'description') }}
                        </p>

                        <!-- Pricing -->
                        <div class="flex items-end gap-3 mb-6">
                            <span class="text-2xl font-bold text-[var(--brand-primary)]">{{ formatCurrency(bundle.total_price) }}</span>
                            <div v-if="Number(bundle.original_price) > Number(bundle.total_price)" class="ms-auto flex flex-col items-end">
                                <span class="text-xs text-gray-400 line-through">{{ formatCurrency(bundle.original_price) }}</span>
                                <span v-if="Number(bundle.savings) > 0" class="text-xs text-green-600 font-semibold">
                                    {{ t('save_amount') }} {{ formatCurrency(bundle.savings) }}
                                </span>
                            </div>
                        </div>

                        <!-- CTA -->
                        <Link :href="localizedRoute(`/package-bundles/${bundle.id}`)"
                              class="block w-full text-center py-3.5 px-6 rounded-xl text-white font-semibold text-sm
                                     bg-[var(--brand-primary)] hover:bg-[var(--brand-primary-hover)] transition-all duration-300 hover:shadow-lg hover:shadow-[var(--brand-primary)]/20">
                            {{ t('view_package') }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
