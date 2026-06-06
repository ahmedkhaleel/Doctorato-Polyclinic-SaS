<script setup>
/**
 * SpecialtyPanel — dynamic, lazy per-specialty clinical cockpit for the visit page.
 *
 * Renders the right clinical panel for the visit's module. Each panel is its own
 * lazy-loaded component so a derma visit never ships the dental/obgyn/neuropsych JS.
 * Unknown / generic modules render nothing (the shared chrome already covers them).
 */
import { defineAsyncComponent, computed } from 'vue';

const props = defineProps({
    visit: { type: Object, required: true },
    isRtl: { type: Boolean, default: true },
    mounted: { type: Boolean, default: false },
    // module-specific payload bag — each panel reads the keys it needs
    extras: { type: Object, default: () => ({}) },
});

const panels = {
    dental: defineAsyncComponent(() => import('./Panels/DentalPanel.vue')),
    derma: defineAsyncComponent(() => import('./Panels/DermaPanel.vue')),
};

const current = computed(() => panels[props.visit?.module] || null);
</script>

<template>
    <component
        :is="current"
        v-if="current"
        :visit="visit"
        :is-rtl="isRtl"
        :mounted="mounted"
        v-bind="extras"
    />
</template>
