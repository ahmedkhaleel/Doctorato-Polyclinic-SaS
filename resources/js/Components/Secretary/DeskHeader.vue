<script setup>
/**
 * DeskHeader — shared gradient hero band for secretary front-desk & specialty
 * pages (SD1 header-consistency unification). Navy base bleeding into a
 * per-specialty accent, with a soft accent glow and a subtle dotted texture —
 * the same treatment shipped for the OB/GYN doctor pages (D3).
 *
 * Slots: #actions (right-aligned controls), #meta (extra row under the title).
 */
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    accent: { type: String, default: '#C4A265' },
});
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl p-5 sm:p-7"
        :style="`background: linear-gradient(115deg, #1B365D 0%, #1B365D 42%, ${accent} 135%)`">
        <!-- accent glow -->
        <div class="absolute top-0 ltr:right-0 rtl:left-0 w-60 h-60 rounded-full -translate-y-1/3 ltr:translate-x-1/4 rtl:-translate-x-1/4 blur-3xl"
            :style="`background:${accent}33`"></div>
        <!-- gold accent blur -->
        <div class="absolute bottom-0 ltr:left-0 rtl:right-0 w-40 h-40 rounded-full translate-y-1/2 bg-[#C4A265]/15 blur-2xl"></div>
        <!-- dotted texture -->
        <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 18px 18px;"></div>

        <div class="relative z-10 flex items-start sm:items-center justify-between gap-4 flex-wrap">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-white">{{ title }}</h1>
                <p v-if="subtitle" class="text-xs sm:text-sm text-white/70 mt-1">{{ subtitle }}</p>
                <slot name="meta" />
            </div>
            <div v-if="$slots.actions" class="shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
