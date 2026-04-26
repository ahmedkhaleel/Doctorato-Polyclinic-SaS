<script setup>
/**
 * Lazy wrapper around RichTextEditor.vue.
 *
 * The TipTap-based editor pulls ~373 KB of vendor JS that's only needed
 * on edit forms (Posts, Pages, FAQs, Services, Doctors). Wrapping it in
 * defineAsyncComponent moves that weight into a separate chunk that
 * downloads ONLY when the form actually mounts.
 *
 * Drop-in replacement: same v-model + same dir + placeholder props as
 * the underlying RichTextEditor.
 */
import { defineAsyncComponent } from 'vue';

const RichTextEditor = defineAsyncComponent({
    loader: () => import('@/Components/Admin/RichTextEditor.vue'),
    delay: 0,
    timeout: 15000,
});

defineProps({
    modelValue: { type: String, default: '' },
    dir: { type: String, default: 'ltr' },
    placeholder: { type: String, default: 'Start writing...' },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <Suspense>
        <RichTextEditor
            :model-value="modelValue"
            :dir="dir"
            :placeholder="placeholder"
            @update:model-value="$emit('update:modelValue', $event)"
        />
        <template #fallback>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 flex items-center justify-center min-h-[200px]">
                <div class="flex items-center gap-3 text-slate-500">
                    <div class="w-5 h-5 border-2 border-[#C4A265] border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-sm">Loading editor…</span>
                </div>
            </div>
        </template>
    </Suspense>
</template>
