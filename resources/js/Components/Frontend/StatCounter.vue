<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    end: {
        type: Number,
        required: true,
    },
    suffix: {
        type: String,
        default: '+',
    },
    duration: {
        type: Number,
        default: 2500,
    },
});

const displayNumber = ref(0);
const counterRef = ref(null);
const hasAnimated = ref(false);
const isVisible = ref(false);
let observer = null;
let animationFrame = null;

function animateCount() {
    if (hasAnimated.value) return;
    hasAnimated.value = true;
    isVisible.value = true;

    const target = props.end;
    const duration = props.duration;
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Ease-out exponential for smooth deceleration
        const eased = 1 - Math.pow(1 - progress, 4);
        displayNumber.value = Math.round(eased * target);

        if (progress < 1) {
            animationFrame = requestAnimationFrame(update);
        } else {
            displayNumber.value = target;
        }
    }

    animationFrame = requestAnimationFrame(update);
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateCount();
                    observer.disconnect();
                }
            });
        },
        { threshold: 0.3 }
    );

    if (counterRef.value) {
        observer.observe(counterRef.value);
    }
});

onUnmounted(() => {
    if (observer) observer.disconnect();
    if (animationFrame) cancelAnimationFrame(animationFrame);
});
</script>

<template>
    <div
        ref="counterRef"
        class="stat-number-wrapper"
        :class="{ 'stat-visible': isVisible }"
    >
        <span class="stat-number tabular-nums">
            {{ displayNumber.toLocaleString() }}
        </span>
        <span v-if="suffix" class="stat-suffix">{{ suffix }}</span>
    </div>
</template>

<style scoped>
.stat-number-wrapper {
    display: inline-flex;
    align-items: baseline;
    opacity: 0;
    transform: translateY(10px) scale(0.9);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.stat-visible {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.stat-number {
    font-size: inherit;
    font-weight: inherit;
    color: inherit;
    line-height: 1;
}

.stat-suffix {
    font-size: 0.65em;
    font-weight: inherit;
    color: inherit;
    margin-inline-start: 2px;
    opacity: 0.9;
}
</style>
