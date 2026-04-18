<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    options: { type: Array, default: () => [] },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'اختر...' },
    searchPlaceholder: { type: String, default: 'ابحث...' },
    noResultsText: { type: String, default: 'لا توجد نتائج' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true },
    size: { type: String, default: 'md' }, // sm | md | lg
    searchThreshold: { type: Number, default: 5 },
    hint: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'change']);

const searchQuery = ref('');
const isOpen = ref(false);
const highlightedIndex = ref(-1);
const containerRef = ref(null);
const inputRef = ref(null);
const dropdownRef = ref(null);

const sizeClass = computed(() => {
    if (props.size === 'sm') return 'px-3 py-2 text-sm min-h-[38px]';
    if (props.size === 'lg') return 'px-4 py-3 text-base min-h-[50px]';
    return 'px-4 py-2.5 text-sm min-h-[42px]';
});

const selectedLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return '';
    const found = props.options.find(opt => String(opt.value) === String(props.modelValue));
    return found ? found.label : '';
});

const showSearch = computed(() => props.options.length > props.searchThreshold);

const groupedOptions = computed(() => {
    // Flat list; grouping handled by rendering
    const list = searchQuery.value
        ? props.options.filter(opt => {
            const q = searchQuery.value.toLowerCase();
            const text = (opt.searchText || opt.label || '').toString().toLowerCase();
            return text.includes(q);
        })
        : props.options.slice();
    return list.slice(0, 200);
});

function openDropdown() {
    if (props.disabled) return;
    isOpen.value = true;
    searchQuery.value = '';
    highlightedIndex.value = -1;
    nextTick(() => inputRef.value?.focus());
}

function closeDropdown() {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
}

function selectOption(option) {
    emit('update:modelValue', option.value);
    emit('change', option.value);
    closeDropdown();
}

function clearSelection(e) {
    e?.stopPropagation();
    emit('update:modelValue', '');
    emit('change', '');
    closeDropdown();
}

function handleKeydown(e) {
    if (!isOpen.value) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            openDropdown();
        }
        return;
    }
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (highlightedIndex.value < groupedOptions.value.length - 1) {
                highlightedIndex.value++;
                scrollToHighlighted();
            }
            break;
        case 'ArrowUp':
            e.preventDefault();
            if (highlightedIndex.value > 0) {
                highlightedIndex.value--;
                scrollToHighlighted();
            }
            break;
        case 'Enter':
            e.preventDefault();
            if (highlightedIndex.value >= 0 && highlightedIndex.value < groupedOptions.value.length) {
                selectOption(groupedOptions.value[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            e.preventDefault();
            closeDropdown();
            break;
    }
}

function scrollToHighlighted() {
    nextTick(() => {
        const dropdown = dropdownRef.value;
        if (!dropdown) return;
        const items = dropdown.querySelectorAll('[data-option]');
        const highlighted = items[highlightedIndex.value];
        if (highlighted) highlighted.scrollIntoView({ block: 'nearest' });
    });
}

function handleClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        closeDropdown();
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside));

watch(groupedOptions, () => { highlightedIndex.value = -1; });
</script>

<template>
    <div ref="containerRef" class="w-full relative">
        <label v-if="label" class="text-sm font-semibold text-slate-700 mb-1.5 block">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Trigger -->
        <div
            v-if="!isOpen"
            @click="openDropdown"
            tabindex="0"
            @keydown="handleKeydown"
            :class="[
                'flex items-center w-full rounded-xl border bg-white transition-colors',
                sizeClass,
                disabled ? 'bg-slate-50 text-slate-400 cursor-not-allowed' : 'cursor-pointer hover:border-slate-400',
                error ? 'border-red-400 ring-1 ring-red-100' : 'border-slate-300',
                'focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D]',
            ]"
        >
            <span v-if="selectedLabel" class="text-slate-900 truncate flex-1">{{ selectedLabel }}</span>
            <span v-else class="text-slate-400 truncate flex-1">{{ placeholder }}</span>

            <div class="flex items-center gap-1 ms-2 shrink-0">
                <button
                    v-if="selectedLabel && clearable && !disabled"
                    type="button"
                    @click.stop="clearSelection"
                    class="text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-100 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Open state -->
        <div v-if="isOpen" class="relative">
            <div
                :class="[
                    'flex items-center gap-2 w-full rounded-t-xl border bg-white',
                    sizeClass,
                    error ? 'border-red-400' : 'border-slate-300',
                ]"
            >
                <svg v-if="showSearch" class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    ref="inputRef"
                    v-model="searchQuery"
                    :placeholder="showSearch ? searchPlaceholder : (selectedLabel || placeholder)"
                    :readonly="!showSearch"
                    type="text"
                    class="flex-1 border-0 outline-none text-sm bg-transparent p-0 focus:ring-0 w-full"
                    @keydown="handleKeydown"
                />
                <button
                    type="button"
                    @click="closeDropdown"
                    class="text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-100 transition shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </button>
            </div>

            <div
                ref="dropdownRef"
                class="absolute start-0 end-0 z-50 max-h-60 overflow-y-auto bg-white border border-t-0 border-slate-300 rounded-b-xl shadow-lg"
            >
                <div
                    v-if="groupedOptions.length === 0"
                    class="px-4 py-3 text-sm text-slate-400 text-center"
                >
                    {{ noResultsText }}
                </div>
                <div
                    v-for="(option, index) in groupedOptions"
                    :key="option.value + '-' + index"
                    data-option
                    @mousedown.prevent="selectOption(option)"
                    @mouseenter="highlightedIndex = index"
                    :class="[
                        'flex items-center justify-between px-4 py-2 text-sm cursor-pointer transition-colors',
                        highlightedIndex === index ? 'bg-[#C4A265]/10' : '',
                        String(modelValue) === String(option.value) ? 'text-[#1B365D] font-semibold bg-[#C4A265]/5' : 'text-slate-700',
                    ]"
                >
                    <div class="truncate flex-1 min-w-0">
                        <span class="truncate">{{ option.label }}</span>
                        <span v-if="option.subtitle" class="block text-xs text-slate-400 truncate">{{ option.subtitle }}</span>
                    </div>
                    <svg
                        v-if="String(modelValue) === String(option.value)"
                        class="w-4 h-4 shrink-0 text-[#C4A265]"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
