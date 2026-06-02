<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: [Number, String], default: 0 },
    max: { type: Number, default: 5 },
    size: { type: Number, default: 16 },
});

const filled = computed(() => Math.max(0, Math.min(props.max, Math.round(Number(props.value) || 0))));
</script>

<template>
    <span class="inline-flex items-center gap-0.5 align-middle" :aria-label="`${filled}/${max}`" role="img">
        <svg v-for="n in max" :key="n" :width="size" :height="size" viewBox="0 0 24 24"
            :class="n <= filled ? 'text-[#C4A265]' : 'text-gray-300'"
            :fill="n <= filled ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M11.48 3.5a.56.56 0 011.04 0l2.13 4.32c.08.16.23.27.4.3l4.77.69c.46.07.64.63.31.95l-3.45 3.36a.56.56 0 00-.16.5l.82 4.74c.08.45-.4.8-.81.59l-4.27-2.24a.56.56 0 00-.52 0L7.5 19.44c-.41.21-.89-.14-.81-.59l.82-4.74a.56.56 0 00-.16-.5L3.9 10.25a.56.56 0 01.31-.95l4.77-.69a.56.56 0 00.4-.3l2.1-4.31z" />
        </svg>
    </span>
</template>
