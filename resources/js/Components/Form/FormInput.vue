<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    type: { type: String, default: 'text' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    icon: { type: String, default: '' }, // svg path data (d attribute) or leave blank
    size: { type: String, default: 'md' }, // sm | md | lg
    id: { type: String, default: '' },
    name: { type: String, default: '' },
    autocomplete: { type: String, default: 'off' },
    min: { type: [String, Number], default: null },
    max: { type: [String, Number], default: null },
    step: { type: [String, Number], default: null },
    maxlength: { type: [String, Number], default: null },
    hint: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus', 'input', 'keydown']);

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
    'read-only:bg-slate-50',
    sizeClass.value,
    props.error ? 'border-red-400 ring-1 ring-red-100' : 'border-slate-300',
    props.icon ? 'ps-10' : '',
]);

function onInput(e) {
    emit('update:modelValue', e.target.value);
    emit('input', e);
}
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="id" class="text-sm font-semibold text-slate-700 mb-1.5 block">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <span v-if="icon" class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
                </svg>
            </span>
            <input
                :id="id"
                :name="name"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :readonly="readonly"
                :autocomplete="autocomplete"
                :min="min"
                :max="max"
                :step="step"
                :maxlength="maxlength"
                :class="baseClass"
                @input="onInput"
                @blur="$emit('blur', $event)"
                @focus="$emit('focus', $event)"
                @keydown="$emit('keydown', $event)"
            />
        </div>
        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
