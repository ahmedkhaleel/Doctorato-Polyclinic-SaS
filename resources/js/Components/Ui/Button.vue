<script setup>
import { computed } from 'vue';

/**
 * Shared button primitive — the start of a unified design-system surface
 * (P4-1). Variants + sizes + loading/disabled, brand-token aware, RTL-safe.
 * Renders an <a> when `href` is set, otherwise a <button>. New/refactored
 * code should prefer this over hand-rolled inline Tailwind buttons.
 *
 * Usage:
 *   <UiButton variant="primary" :loading="form.processing">Save</UiButton>
 *   <UiButton variant="outline" size="sm" href="/admin/x">Open</UiButton>
 */
const props = defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | outline | ghost | danger
    size: { type: String, default: 'md' },          // sm | md | lg
    type: { type: String, default: 'button' },
    href: { type: String, default: null },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const isDisabled = computed(() => props.disabled || props.loading);

const sizeClasses = computed(() => ({
    sm: 'px-3 py-1.5 text-xs gap-1.5 rounded-lg',
    md: 'px-4 py-2.5 text-sm gap-2 rounded-xl',
    lg: 'px-6 py-3 text-base gap-2.5 rounded-xl',
}[props.size] || 'px-4 py-2.5 text-sm gap-2 rounded-xl'));

const variantClasses = computed(() => ({
    primary: 'bg-[var(--brand-primary,#C4A265)] text-white hover:opacity-90 border border-transparent shadow-sm',
    secondary: 'bg-[#1B365D] text-white hover:opacity-90 border border-transparent shadow-sm',
    outline: 'bg-white text-[var(--brand-primary,#C4A265)] border border-[var(--brand-primary,#C4A265)]/40 hover:bg-[var(--brand-primary,#C4A265)]/10',
    ghost: 'bg-transparent text-gray-600 border border-transparent hover:bg-gray-100',
    danger: 'bg-rose-600 text-white hover:bg-rose-700 border border-transparent shadow-sm',
}[props.variant] || ''));

const base = 'inline-flex items-center justify-center font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary,#C4A265)]/30 disabled:opacity-50 disabled:cursor-not-allowed';
</script>

<template>
    <component
        :is="href ? 'a' : 'button'"
        :href="href || undefined"
        :type="href ? undefined : type"
        :disabled="href ? undefined : isDisabled"
        :aria-disabled="isDisabled ? 'true' : undefined"
        :class="[base, sizeClasses, variantClasses, block ? 'w-full' : '']"
    >
        <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <slot />
    </component>
</template>
