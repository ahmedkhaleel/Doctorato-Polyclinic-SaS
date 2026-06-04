<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import SeoHead from '@/Components/Frontend/SeoHead.vue';
import PageHero from '@/Components/Frontend/PageHero.vue';

defineOptions({ layout: FrontendLayout });

const props = defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
    seo: Object,
});

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink } = useSettings();

const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

const searchQuery = ref('');
const openItems = ref({});

const categoryLabels = computed(() => ({
    general: locale.value === 'ar' ? 'أسئلة عامة' : 'General Questions',
    laser: locale.value === 'ar' ? 'علاجات الليزر' : 'Laser Treatments',
    skin: locale.value === 'ar' ? 'العناية بالبشرة' : 'Skin Care',
    booking: locale.value === 'ar' ? 'الحجز والمواعيد' : 'Booking & Appointments',
    pricing: locale.value === 'ar' ? 'الأسعار والعروض' : 'Pricing & Offers',
}));

const categoryIcons = {
    general: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    laser: 'M13 10V3L4 14h7v7l9-11h-7z',
    skin: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    booking: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    pricing: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

const filteredFaqs = computed(() => {
    if (!searchQuery.value.trim()) return props.faqs;
    const query = searchQuery.value.toLowerCase().trim();
    return props.faqs.filter((faq) => {
        const question = localized(faq, 'question')?.toLowerCase() || '';
        const answer = localized(faq, 'answer')?.toLowerCase() || '';
        return question.includes(query) || answer.includes(query);
    });
});

const groupedFaqs = computed(() => {
    const groups = {};
    const categoryOrder = ['general', 'laser', 'skin', 'booking', 'pricing'];

    filteredFaqs.value.forEach((faq) => {
        const cat = faq.category || 'general';
        if (!groups[cat]) {
            groups[cat] = [];
        }
        groups[cat].push(faq);
    });

    // Return ordered groups
    const ordered = [];
    categoryOrder.forEach((cat) => {
        if (groups[cat] && groups[cat].length) {
            ordered.push({
                key: cat,
                label: categoryLabels.value[cat] || cat,
                icon: categoryIcons[cat] || categoryIcons.general,
                items: groups[cat],
            });
        }
    });

    // Add any categories not in our order list
    Object.keys(groups).forEach((cat) => {
        if (!categoryOrder.includes(cat) && groups[cat].length) {
            ordered.push({
                key: cat,
                label: categoryLabels.value[cat] || cat,
                icon: categoryIcons.general,
                items: groups[cat],
            });
        }
    });

    return ordered;
});

function toggleItem(faqId) {
    openItems.value[faqId] = !openItems.value[faqId];
}

function isOpen(faqId) {
    return !!openItems.value[faqId];
}
</script>

<template>
    <SeoHead
        :title="seoTitle"
        :description="seoDescription"
        :keywords="seo?.keywords"
        :image="seo?.image"
    />

    <PageHero :title="isRtl ? 'الأسئلة الشائعة' : 'FAQ'" :subtitle="isRtl ? 'إجابات على أكثر الأسئلة شيوعاً' : 'Answers to frequently asked questions'" :breadcrumb="isRtl ? 'الأسئلة الشائعة' : 'FAQ'" />

    <!-- Search Section -->
    <section class="relative py-10 bg-[#FDF8F0] overflow-hidden">
        <!-- Subtle dot pattern -->
        <div class="absolute inset-0 pointer-events-none texture-dots"></div>
        <!-- Floating decorative elements -->
        <div class="absolute top-6 end-12 w-16 h-16 rounded-full bg-gold-primary/5 animate-float-slow"></div>
        <div class="absolute bottom-6 start-8 w-12 h-12 rounded-full bg-gold-primary/8 animate-float"></div>
        <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8" v-scroll-reveal="{ type: 'fade-up' }">
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="locale === 'ar' ? 'ابحثي عن سؤالك...' : 'Search for your question...'"
                    class="doctorato-input w-full ps-12 pe-4 py-4 bg-white border-2 border-gray-200 rounded-2xl text-[#3A3A3A] placeholder-gray-400 focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]/20 focus:ring-4 outline-none transition-all duration-300 text-lg shadow-sm form-input-animated"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    :aria-label="isRtl ? 'مسح البحث' : 'Clear search'" :title="isRtl ? 'مسح البحث' : 'Clear search'"
                    class="absolute inset-y-0 end-0 flex items-center pe-4 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p v-if="searchQuery && !filteredFaqs.length" class="text-center text-gray-500 mt-4">
                {{ locale === 'ar' ? 'لم يتم العثور على نتائج' : 'No results found' }}
            </p>
        </div>
    </section>

    <!-- FAQ Groups -->
    <section class="relative pb-12 lg:pb-16 overflow-hidden">
        <div class="absolute inset-0 pointer-events-none texture-crosshatch"></div>
        <!-- Floating decorative elements -->
        <div class="absolute top-20 start-6 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
        <div class="absolute bottom-40 end-8 w-16 h-16 rounded-full bg-gold-primary/8 animate-float-slow"></div>
        <div class="absolute top-1/2 end-20 w-10 h-10 rounded-full bg-gold-light/5 animate-float-delay"></div>
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                v-for="(group, groupIndex) in groupedFaqs"
                :key="group.key"
                class="py-10 lg:py-12"
                :class="groupIndex % 2 === 0 ? 'bg-white' : 'bg-[#FDF8F0]'"
                :style="{ margin: '0 -1rem', padding: '2.5rem 1rem' }"
            >
                <!-- Category Header -->
                <div class="flex items-center gap-3 mb-8" v-scroll-reveal="{ type: 'blur-in' }">
                    <div class="w-12 h-12 rounded-xl bg-[var(--brand-primary)]/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="group.icon" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl lg:text-2xl font-bold text-[#3A3A3A]">{{ group.label }}</h2>
                        <p class="text-sm text-gray-500">
                            {{ group.items.length }} {{ locale === 'ar' ? (group.items.length > 2 ? 'أسئلة' : 'سؤال') : (group.items.length === 1 ? 'question' : 'questions') }}
                        </p>
                    </div>
                </div>

                <!-- Accordion Items -->
                <div class="space-y-3" v-scroll-reveal="{ type: 'stagger', staggerDelay: 100 }">
                    <div
                        v-for="(faq, faqIndex) in group.items"
                        :key="faq.id"
                        class="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300 card-hover-lift"
                        :class="{ 'border-[var(--brand-primary)]/30 shadow-md': isOpen(faq.id) }"
                    >
                        <!-- Question Button -->
                        <button
                            @click="toggleItem(faq.id)"
                            class="w-full flex items-center justify-between gap-4 p-5 lg:p-6 text-start hover:bg-[#FDF8F0]/50 transition-colors"
                        >
                            <span
                                class="text-base lg:text-lg font-semibold transition-colors"
                                :class="isOpen(faq.id) ? 'text-[var(--brand-primary)]' : 'text-[#3A3A3A]'"
                            >
                                {{ localized(faq, 'question') }}
                            </span>
                            <span
                                class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300"
                                :class="isOpen(faq.id) ? 'bg-[var(--brand-primary)] text-white rotate-180' : 'bg-[#F5EDE0] text-[#3A3A3A]'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>

                        <!-- Answer -->
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-screen opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-screen opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div v-show="isOpen(faq.id)" class="overflow-hidden">
                                <div class="px-5 lg:px-6 pb-5 lg:pb-6 pt-0">
                                    <div class="w-full h-px bg-gray-100 mb-4"></div>
                                    <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                                        {{ localized(faq, 'answer') }}
                                    </p>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

            <!-- No FAQs -->
            <div v-if="!groupedFaqs.length && !searchQuery" class="text-center py-16" v-scroll-reveal="{ type: 'fade-up' }">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-500 mb-2">
                    {{ locale === 'ar' ? 'لا توجد أسئلة حاليا' : 'No FAQs available' }}
                </h3>
                <p class="text-gray-400">
                    {{ locale === 'ar' ? 'تواصلي معنا مباشرة لأي استفسار' : 'Contact us directly for any inquiries' }}
                </p>
            </div>
        </div>
    </section>

    <!-- Not Found CTA -->
    <section class="relative py-16 lg:py-20 bg-[#F5EDE0] overflow-hidden">
        <!-- Subtle diagonal line pattern -->
        <div class="absolute inset-0 pointer-events-none texture-diagonal-reverse"></div>
        <!-- Floating decorative elements -->
        <div class="absolute top-10 start-10 w-20 h-20 rounded-full bg-gold-primary/5 animate-float"></div>
        <div class="absolute bottom-10 end-14 w-16 h-16 rounded-full bg-gold-primary/8 animate-float-slow"></div>
        <div class="absolute top-1/3 end-1/3 w-12 h-12 rounded-full bg-gold-light/5 animate-float-delay"></div>
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" v-scroll-reveal="{ type: 'fade-up' }">
            <div class="w-16 h-16 rounded-full bg-[var(--brand-primary)]/10 flex items-center justify-center mx-auto mb-6 animate-breathe">
                <svg class="w-8 h-8 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold text-[#3A3A3A] mb-4" v-scroll-reveal="{ type: 'blur-in', delay: 100 }">
                {{ locale === 'ar' ? 'لم تجدي إجابتك؟' : "Didn't Find Your Answer?" }}
            </h2>
            <p class="text-gray-600 mb-8 text-lg" v-scroll-reveal="{ type: 'fade-up', delay: 200 }">
                {{ locale === 'ar' ? 'تواصلي معنا مباشرة وسنكون سعداء بمساعدتك' : 'Contact us directly and we will be happy to help you' }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4" v-scroll-reveal="{ type: 'fade-up', delay: 300 }">
                <a
                    :href="whatsappLink"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-[#25D366] text-white font-semibold rounded-full hover:bg-[#1da851] transition-all duration-300 shadow-lg hover:shadow-xl btn-shimmer"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    {{ locale === 'ar' ? 'تواصلي عبر واتساب' : 'Chat on WhatsApp' }}
                </a>
                <Link
                    :href="localizedRoute('/contact')"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-[var(--brand-primary)] text-white font-semibold rounded-full hover:bg-[var(--brand-primary-hover)] transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-[var(--brand-primary)]/30 btn-shimmer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {{ locale === 'ar' ? 'تواصلي معنا' : 'Contact Us' }}
                </Link>
            </div>
        </div>
    </section>
</template>
