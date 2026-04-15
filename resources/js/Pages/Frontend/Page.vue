<script setup>
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useLocale } from '@/Composables/useLocale';
import { sanitizeHtml } from '@/Composables/useSanitize';
import { computed } from 'vue';
import SeoHead from '@/Components/Frontend/SeoHead.vue';
import PageHero from '@/Components/Frontend/PageHero.vue';

const { t, localized, locale, isRtl } = useLocale();

const props = defineProps({
    page: Object,
    seo: Object,
});

const pageTitle = computed(() => localized(props.page, 'title'));
const pageContent = computed(() => sanitizeHtml(localized(props.page, 'content')));
const seoTitle = computed(() => localized(props.seo, 'title') || pageTitle.value);
const seoDescription = computed(() => localized(props.seo, 'description'));
</script>

<template>
    <FrontendLayout :title="pageTitle">
        <SeoHead
            :title="seoTitle"
            :description="seoDescription"
            :keywords="seo?.keywords"
            :image="seo?.image"
        />

        <PageHero :title="localized(page, 'title')" :breadcrumb="localized(page, 'title')" />

        <!-- Page Content -->
        <section class="py-16 lg:py-24 bg-[#FDF8F0]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div
                    v-scroll-reveal="{ type: 'fade-up' }"
                    class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 lg:p-12 border border-gray-100"
                >
                    <div
                        class="prose-content prose prose-lg max-w-none"
                        :class="[
                            isRtl ? 'text-right' : 'text-left',
                            'prose-headings:text-[#3A3A3A] prose-headings:font-bold',
                            'prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4 prose-h2:pb-2 prose-h2:border-b prose-h2:border-[var(--brand-primary)]/20',
                            'prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3',
                            'prose-p:text-gray-600 prose-p:leading-relaxed prose-p:mb-4',
                            'prose-a:text-[var(--brand-primary)] prose-a:underline prose-a:underline-offset-2 hover:prose-a:text-[var(--brand-primary-hover)]',
                            'prose-strong:text-[#3A3A3A] prose-strong:font-semibold',
                            'prose-ul:list-disc prose-ul:my-4',
                            'prose-ol:list-decimal prose-ol:my-4',
                            'prose-li:text-gray-600 prose-li:mb-2 prose-li:leading-relaxed',
                            'prose-blockquote:border-[var(--brand-primary)] prose-blockquote:bg-[#FDF8F0] prose-blockquote:rounded-lg prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:not-italic',
                            'prose-table:border-collapse prose-table:w-full',
                            'prose-th:bg-[#F5EDE0] prose-th:text-[#3A3A3A] prose-th:font-semibold prose-th:px-4 prose-th:py-3 prose-th:border prose-th:border-gray-200',
                            'prose-td:px-4 prose-td:py-3 prose-td:border prose-td:border-gray-200 prose-td:text-gray-600',
                            'prose-img:rounded-xl prose-img:shadow-md',
                        ]"
                        v-html="pageContent"
                    ></div>
                </div>
            </div>
        </section>

    </FrontendLayout>
</template>

<style scoped>
.prose-content :deep(h1) {
    font-size: 2rem;
    font-weight: 700;
    color: #3A3A3A;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose-content :deep(h2) {
    font-size: 1.5rem;
    font-weight: 700;
    color: #3A3A3A;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid rgba(184, 150, 12, 0.2);
}

.prose-content :deep(h3) {
    font-size: 1.25rem;
    font-weight: 600;
    color: #3A3A3A;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
}

.prose-content :deep(p) {
    color: #4B5563;
    line-height: 1.8;
    margin-bottom: 1rem;
}

.prose-content :deep(a) {
    color: var(--brand-primary);
    text-decoration: underline;
    text-underline-offset: 2px;
    transition: color 0.2s;
}

.prose-content :deep(a:hover) {
    color: var(--brand-primary-hover);
}

.prose-content :deep(ul),
.prose-content :deep(ol) {
    margin: 1rem 0;
    padding-inline-start: 1.5rem;
}

.prose-content :deep(li) {
    color: #4B5563;
    margin-bottom: 0.5rem;
    line-height: 1.8;
}

.prose-content :deep(blockquote) {
    border-inline-start: 4px solid var(--brand-primary);
    background-color: #FDF8F0;
    padding: 1rem 1.5rem;
    margin: 1.5rem 0;
    border-radius: 0.5rem;
}

.prose-content :deep(blockquote p) {
    margin-bottom: 0;
}

.prose-content :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
    border-radius: 0.75rem;
    overflow: hidden;
}

.prose-content :deep(th) {
    background-color: #F5EDE0;
    color: #3A3A3A;
    font-weight: 600;
    padding: 0.75rem 1rem;
    border: 1px solid #E5E7EB;
    text-align: start;
}

.prose-content :deep(td) {
    padding: 0.75rem 1rem;
    border: 1px solid #E5E7EB;
    color: #4B5563;
}

.prose-content :deep(img) {
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    max-width: 100%;
    height: auto;
    margin: 1.5rem 0;
}

.prose-content :deep(hr) {
    border: none;
    border-top: 2px solid rgba(184, 150, 12, 0.15);
    margin: 2rem 0;
}

.prose-content :deep(code) {
    background-color: #F5EDE0;
    color: var(--brand-primary);
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.prose-content :deep(pre) {
    background-color: #3A3A3A;
    color: #F5EDE0;
    padding: 1.5rem;
    border-radius: 0.75rem;
    overflow-x: auto;
    margin: 1.5rem 0;
}

.prose-content :deep(pre code) {
    background-color: transparent;
    color: inherit;
    padding: 0;
}
</style>
