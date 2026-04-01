<script setup>
import { computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    keywords: {
        type: String,
        default: '',
    },
    image: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'website',
    },
    url: {
        type: String,
        default: '',
    },
    noindex: {
        type: Boolean,
        default: false,
    },
    canonicalUrl: {
        type: String,
        default: '',
    },
    structuredData: {
        type: [Object, Array],
        default: null,
    },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

const siteUrl = 'https://auraderma.com';
const siteName = 'AURA Derma Aesthetic Clinic';

const currentPath = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.pathname;
    }
    return '';
});

const resolvedCanonicalUrl = computed(() => {
    if (props.canonicalUrl) return props.canonicalUrl;
    if (props.url) return props.url;
    return `${siteUrl}${currentPath.value}`;
});

const arUrl = computed(() => {
    const path = currentPath.value.replace(/^\/(ar|en)/, '/ar');
    return `${siteUrl}${path}`;
});

const enUrl = computed(() => {
    const path = currentPath.value.replace(/^\/(ar|en)/, '/en');
    return `${siteUrl}${path}`;
});

const defaultUrl = computed(() => arUrl.value);

const ogImage = computed(() => {
    if (props.image) {
        if (props.image.startsWith('http')) return props.image;
        return `${siteUrl}${props.image}`;
    }
    return `${siteUrl}/images/og-default.jpg`;
});

const structuredDataJson = computed(() => {
    if (!props.structuredData) return null;
    try {
        return JSON.stringify(props.structuredData);
    } catch {
        return null;
    }
});

// Inject JSON-LD structured data dynamically (Vue 3 disallows <script> in templates)
let jsonLdScript = null;

function updateJsonLd() {
    // Remove existing
    if (jsonLdScript && jsonLdScript.parentNode) {
        jsonLdScript.parentNode.removeChild(jsonLdScript);
        jsonLdScript = null;
    }
    // Add new if data exists
    if (structuredDataJson.value) {
        jsonLdScript = document.createElement('script');
        jsonLdScript.type = 'application/ld+json';
        jsonLdScript.textContent = structuredDataJson.value;
        document.head.appendChild(jsonLdScript);
    }
}

onMounted(() => {
    updateJsonLd();
});

watch(structuredDataJson, () => {
    updateJsonLd();
});

onBeforeUnmount(() => {
    if (jsonLdScript && jsonLdScript.parentNode) {
        jsonLdScript.parentNode.removeChild(jsonLdScript);
    }
});
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta v-if="keywords" name="keywords" :content="keywords" />

        <!-- Open Graph -->
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="ogImage" />
        <meta property="og:type" :content="type" />
        <meta property="og:url" :content="resolvedCanonicalUrl" />
        <meta property="og:site_name" :content="siteName" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="ogImage" />

        <!-- Canonical -->
        <link rel="canonical" :href="resolvedCanonicalUrl" />

        <!-- Robots -->
        <meta v-if="noindex" name="robots" content="noindex, nofollow" />

        <!-- Hreflang -->
        <link rel="alternate" hreflang="ar" :href="arUrl" />
        <link rel="alternate" hreflang="en" :href="enUrl" />
        <link rel="alternate" hreflang="x-default" :href="defaultUrl" />

        <!-- Structured Data (JSON-LD) is injected dynamically via script setup -->
    </Head>
</template>
