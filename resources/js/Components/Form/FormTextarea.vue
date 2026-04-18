<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, null], default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    rows: { type: [String, Number], default: 4 },
    size: { type: String, default: 'md' },
    id: { type: String, default: '' },
    name: { type: String, default: '' },
    maxlength: { type: [String, Number], default: null },
    hint: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const sizeClass = computed(() => {
    if (props.size === 'sm') return 'px-3 py-2 text-sm';
    if (props.size === 'lg') return 'px-4 py-3 text-base';
    return 'px-4 py-2.5 text-sm';
});

const baseClass = computed(() => [
    'w-full rounded-xl border bg-white text-slate-900',
    'placeholder:text-slate-400 transition-colors',
    'focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D]',
    'disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed',
    'read-only:bg-slate-50 resize-y',
    sizeClass.value,
    props.error ? 'border-red-400 ring-1 ring-red-100' : 'border-slate-300',
]);
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="id" class="text-sm font-semibold text-slate-700 mb-1.5 block">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <textarea
            :id="id"
            :name="name"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :readonly="readonly"
            :rows="rows"
            :maxlength="maxlength"
            :class="baseClass"
            @input="$emit('update:modelValue', $event.target.value)"
            @blur="$emit('blur', $event)"
            @focus="$emit('focus', $event)"
        />
        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
