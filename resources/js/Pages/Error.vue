<script setup>
import { computed } from 'vue';
import { usePage, Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: Number,
        required: true,
    },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');

const errorData = computed(() => {
    const messages = {
        403: {
            title: { ar: 'محظور', en: 'Forbidden' },
            description: {
                ar: 'عذراً، ليس لديك صلاحية للوصول إلى هذه الصفحة.',
                en: 'Sorry, you are not authorized to access this page.',
            },
            icon: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
        },
        404: {
            title: { ar: 'الصفحة غير موجودة', en: 'Page Not Found' },
            description: {
                ar: 'عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.',
                en: 'Sorry, the page you are looking for does not exist or has been moved.',
            },
            icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        },
        500: {
            title: { ar: 'خطأ في الخادم', en: 'Server Error' },
            description: {
                ar: 'عذراً، حدث خطأ في الخادم. يرجى المحاولة مرة أخرى لاحقاً.',
                en: 'Sorry, something went wrong on our end. Please try again later.',
            },
            icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        },
        503: {
            title: { ar: 'الخدمة غير متوفرة', en: 'Service Unavailable' },
            description: {
                ar: 'عذراً، الخدمة غير متاحة حالياً. يرجى المحاولة مرة أخرى لاحقاً.',
                en: 'Sorry, we are currently undergoing maintenance. Please try again later.',
            },
            icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        },
    };

    return messages[props.status] || messages[404];
});

const title = computed(() => errorData.value.title[locale.value]);
const description = computed(() => errorData.value.description[locale.value]);
const iconPath = computed(() => errorData.value.icon);

const goHomeLabel = computed(() => {
    return locale.value === 'ar' ? 'العودة للرئيسية' : 'Go Home';
});

const homeUrl = computed(() => `/${locale.value}`);
</script>

<template>
    <Head :title="`${status} - ${title}`" />

    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :class="[locale === 'ar' ? 'font-ar' : 'font-en']"
        class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 via-white to-gray-100 px-4"
    >
        <div class="text-center max-w-lg">
            <!-- Status Code with Gold Gradient -->
            <h1 class="text-[8rem] sm:text-[10rem] font-bold leading-none tracking-tight bg-gradient-to-b from-[#C4A265] via-[#D4B87A] to-[#C4A265]/40 bg-clip-text text-transparent select-none">
                {{ status }}
            </h1>

            <!-- Icon -->
            <div class="flex justify-center -mt-4 mb-6">
                <div class="w-16 h-16 rounded-full bg-[#C4A265]/10 flex items-center justify-center">
                    <svg
                        class="w-8 h-8 text-[#C4A265]"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            :d="iconPath"
                        />
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h2 class="text-2xl sm:text-3xl font-bold text-[#3A3A3A] mb-3">
                {{ title }}
            </h2>

            <!-- Description -->
            <p class="text-gray-500 text-base sm:text-lg mb-10 leading-relaxed">
                {{ description }}
            </p>

            <!-- Go Home Button -->
            <Link
                :href="homeUrl"
                class="inline-flex items-center gap-2 px-8 py-3 bg-[#C4A265] text-white font-medium rounded-full hover:bg-[#A68B52] shadow-lg shadow-[#C4A265]/20 hover:shadow-[#C4A265]/40 transition-all duration-300 hover:scale-105"
            >
                <!-- Arrow icon (flipped for RTL) -->
                <svg
                    class="w-5 h-5"
                    :class="{ 'rotate-180': !isRtl }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 12H7m0 0l4-4m-4 4l4 4" />
                </svg>
                {{ goHomeLabel }}
            </Link>

            <!-- AURA Branding -->
            <div class="mt-16">
                <p class="text-sm text-gray-300 tracking-[0.3em] uppercase">AURA Derma</p>
            </div>
        </div>
    </div>
</template>
