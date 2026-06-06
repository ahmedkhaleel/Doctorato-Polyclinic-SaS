<script setup>
/**
 * Shared empty-state block (P4-4) — replaces bare "No records found." text
 * with a consistent icon + title + description (+ optional CTA slot). Icon is
 * a small named SVG set so callers never pass emoji/glyphs (keeps the NoEmoji
 * guard green). Bilingual text is passed by the caller.
 *
 * Usage:
 *   <UiEmptyState icon="search" :title="t('No patients')" :description="t('…')">
 *     <UiButton href="/admin/patients/create">Add</UiButton>
 *   </UiEmptyState>
 */
defineProps({
    icon: { type: String, default: 'inbox' }, // inbox | search | calendar | users | document | chart
    title: { type: String, default: '' },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="px-5 py-14 text-center">
        <div class="mx-auto w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-400">
            <svg v-if="icon === 'search'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <svg v-else-if="icon === 'calendar'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <svg v-else-if="icon === 'users'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
            <svg v-else-if="icon === 'document'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <svg v-else-if="icon === 'chart'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6m4 6V5m4 14v-9M5 21h14"/></svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-2.5 5a2 2 0 01-1.8 1.1H8.3A2 2 0 016.5 18L4 13m16 0h-5l-1 2h-4l-1-2H4"/></svg>
        </div>
        <p v-if="title" class="mt-3 text-sm font-bold text-gray-700">{{ title }}</p>
        <p v-if="description" class="mt-1 text-xs text-gray-400 max-w-sm mx-auto">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4 flex items-center justify-center"><slot /></div>
    </div>
</template>
