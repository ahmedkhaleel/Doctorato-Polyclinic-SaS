<script setup>
import { useLocale } from '@/Composables/useLocale';

const { locale, switchLocale } = useLocale();
</script>

<template>
    <button
        @click="switchLocale"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200 border border-[var(--brand-primary)]/30 hover:border-[var(--brand-primary)] hover:bg-[var(--brand-primary)]/10 text-[var(--brand-primary)]"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
        </svg>
        <span>{{ locale === 'ar' ? 'English' : 'العربية' }}</span>
    </button>
</template>
