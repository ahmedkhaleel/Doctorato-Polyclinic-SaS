<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
    webpSrc: {
        type: String,
        default: null,
    },
    alt: {
        type: String,
        default: '',
    },
    imgClass: {
        type: String,
        default: '',
    },
    width: {
        type: [Number, String],
        default: null,
    },
    height: {
        type: [Number, String],
        default: null,
    },
});

const loaded = ref(false);
const inView = ref(false);
const containerRef = ref(null);

let observer = null;

onMounted(() => {
    if (!('IntersectionObserver' in window)) {
        // Fallback for browsers without IntersectionObserver
        inView.value = true;
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    inView.value = true;
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            rootMargin: '200px 0px', // Start loading 200px before entering viewport
            threshold: 0.01,
        }
    );

    if (containerRef.value) {
        observer.observe(containerRef.value);
    }
});

onUnmounted(() => {
    if (observer && containerRef.value) {
        observer.unobserve(containerRef.value);
        observer.disconnect();
    }
});

const aspectStyle = props.width && props.height
    ? { aspectRatio: `${props.width}/${props.height}` }
    : undefined;
</script>

<template>
    <div
        ref="containerRef"
        class="relative overflow-hidden"
        :style="aspectStyle"
    >
        <!-- Shimmer skeleton -->
        <div v-if="!loaded" class="absolute inset-0 shimmer-gold" />

        <!-- WebP with fallback using picture element -->
        <picture v-if="inView && webpSrc">
            <source :srcset="webpSrc" type="image/webp" />
            <img
                :src="src"
                :alt="alt"
                :width="width || undefined"
                :height="height || undefined"
                :class="[imgClass, { 'opacity-0': !loaded, 'opacity-100': loaded }]"
                class="transition-opacity duration-500"
                @load="loaded = true"
                loading="lazy"
            />
        </picture>

        <!-- Standard image (no WebP) -->
        <img
            v-else-if="inView"
            :src="src"
            :alt="alt"
            :width="width || undefined"
            :height="height || undefined"
            :class="[imgClass, { 'opacity-0': !loaded, 'opacity-100': loaded }]"
            class="transition-opacity duration-500"
            @load="loaded = true"
            loading="lazy"
        />
    </div>
</template>

<style scoped>
.shimmer-gold {
    background: linear-gradient(90deg, #F5EDE0 25%, #FDF8F0 50%, #F5EDE0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
