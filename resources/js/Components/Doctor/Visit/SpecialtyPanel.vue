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
    // 'doctor' (default) uses per-feature doctor routes; 'admin' routes all deep
    // links to patientHref (the admin patient file) since admin has no per-patient
    // specialty pages — avoids 404s while still surfacing the read-only data.
    context: { type: String, default: 'doctor' },
    patientHref: { type: String, default: null },
    // module-specific payload bag — each panel reads the keys it needs
    extras: { type: Object, default: () => ({}) },
});

const panels = {
    dental: defineAsyncComponent(() => import('./Panels/DentalPanel.vue')),
    derma: defineAsyncComponent(() => import('./Panels/DermaPanel.vue')),
    obgyn: defineAsyncComponent(() => import('./Panels/ObgynPanel.vue')),
    psychiatry: defineAsyncComponent(() => import('./Panels/NeuroPsychPanel.vue')),
    neurology: defineAsyncComponent(() => import('./Panels/NeuroPsychPanel.vue')),
    physiotherapy: defineAsyncComponent(() => import('./Panels/PhysioPanel.vue')),
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
        :context="context"
        :patient-href="patientHref"
        v-bind="extras"
    />
</template>
