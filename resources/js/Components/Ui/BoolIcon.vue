<script setup>
defineProps({
    ok: { type: Boolean, default: false },
    size: { type: Number, default: 16 },
});
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        class="inline-block align-middle shrink-0" :class="ok ? 'text-emerald-600' : 'text-rose-500'" role="img"
        :aria-label="ok ? 'yes' : 'no'">
        <path v-if="ok" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" />
        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 6l12 12M18 6L6 18" />
    </svg>
</template>
