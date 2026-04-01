<script setup>
import { computed } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import Header from '@/Components/Frontend/Header.vue';
import Footer from '@/Components/Frontend/Footer.vue';
import WhatsAppButton from '@/Components/Frontend/WhatsAppButton.vue';
import BackToTop from '@/Components/Frontend/BackToTop.vue';
import CookieConsent from '@/Components/Frontend/CookieConsent.vue';
import PromoCodePopup from '@/Components/PromoCodePopup.vue';
import { useTheme } from '@/Composables/useTheme';

useTheme();

const props = defineProps({
    title: String,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
</script>

<template>
    <div :dir="dir" :class="[locale === 'ar' ? 'font-ar' : 'font-en']" class="min-h-screen flex flex-col bg-white">
        <Head :title="title" />
        <Header />
        <main class="flex-1">
            <slot />
        </main>
        <Footer />
        <WhatsAppButton />
        <BackToTop />
        <CookieConsent />
        <PromoCodePopup />
    </div>
</template>
