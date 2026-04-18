<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
    modelValue: [String, Number, null],
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select...' },
    searchPlaceholder: { type: String, default: 'Type to search...' },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    label: { type: String, default: '' },
    multiple: { type: Boolean, default: false },
    clearable: { type: Boolean, default: true },
    size: { type: String, default: 'md' }, // sm, md, lg
    icon: { type: String, default: '' }, // SVG path for leading icon
    accentColor: { type: String, default: '#C4A265' },
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');
const isOpen = ref(false);
const highlightedIndex = ref(-1);
const containerRef = ref(null);
const inputRef = ref(null);
const dropdownRef = ref(null);
const entering = ref(false);

const selectedLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return '';
    const found = props.options.find(opt => String(opt.value) === String(props.modelValue));
    return found ? found.label : '';
});

const selectedOption = computed(() => {
    if (!props.modelValue && props.modelValue !== 0) return null;
    return props.options.find(opt => String(opt.value) === String(props.modelValue)) || null;
});

const filteredOptions = computed(() => {
    let opts = props.options;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        opts = opts.filter(opt => {
            const text = (opt.searchText || opt.label || '').toLowerCase();
            return text.includes(q);
        });
    }
    return opts.slice(0, 80);
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: { trigger: 'min-h-[36px] py-1.5 px-3 text-xs', dropdown: 'text-xs', option: 'py-1.5 px-3' },
        md: { trigger: 'min-h-[42px] py-2 px-3.5 text-sm', dropdown: 'text-sm', option: 'py-2.5 px-3.5' },
        lg: { trigger: 'min-h-[48px] py-2.5 px-4 text-sm', dropdown: 'text-sm', option: 'py-3 px-4' },
    };
    return sizes[props.size] || sizes.md;
});

function openDropdown() {
    if (props.disabled) return;
    isOpen.value = true;
    entering.value = true;
    searchQuery.value = '';
    highlightedIndex.value = -1;
    nextTick(() => {
        inputRef.value?.focus();
        setTimeout(() => { entering.value = false; }, 200);
    });
}

function closeDropdown() {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
}

function selectOption(option) {
    emit('update:modelValue', option.value);
    closeDropdown();
}

function clearSelection(e) {
    e?.stopPropagation();
    emit('update:modelValue', '');
    closeDropdown();
}

function handleKeydown(e) {
    if (!isOpen.value) {
        if (['Enter', ' ', 'ArrowDown'].includes(e.key)) {
            e.preventDefault();
            openDropdown();
        }
        return;
    }

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1);
            scrollToHighlighted();
            break;
        case 'ArrowUp':
            e.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
            scrollToHighlighted();
            break;
        case 'Enter':
            e.preventDefault();
            if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
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
        items[highlightedIndex.value]?.scrollIntoView({ block: 'nearest' });
    });
}

function handleClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        closeDropdown();
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside));

watch(filteredOptions, () => { highlightedIndex.value = -1; });
</script>

<template>
    <div ref="containerRef" class="relative">
        <label v-if="label" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ label }}</label>

        <!-- Trigger -->
        <div
            v-if="!isOpen"
            @click="openDropdown"
            @keydown="handleKeydown"
            tabindex="0"
            class="flex items-center w-full rounded-xl border bg-white transition-all duration-200 group"
            :class="[
                sizeClasses.trigger,
                disabled ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'cursor-pointer hover:border-gray-400 hover:shadow-sm',
                error ? 'border-red-300 focus:ring-2 focus:ring-red-200' : 'border-gray-200 focus:ring-2 focus:ring-[var(--accent)]/20 focus:border-[var(--accent)]',
            ]"
            :style="{ '--accent': accentColor }"
        >
            <!-- Leading icon -->
            <svg v-if="icon" class="w-4 h-4 text-gray-400 shrink-0 ltr:mr-2.5 rtl:ml-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icon" />
            </svg>

            <!-- Selected value with optional color dot -->
            <div v-if="selectedOption" class="flex items-center gap-2 flex-1 min-w-0">
                <span v-if="selectedOption.color" class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: selectedOption.color }"></span>
                <span class="text-gray-800 truncate font-medium">{{ selectedOption.label }}</span>
                <span v-if="selectedOption.badge" class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 shrink-0">{{ selectedOption.badge }}</span>
            </div>
            <span v-else class="text-gray-400 truncate flex-1">{{ placeholder }}</span>

            <div class="flex items-center gap-1 ltr:ml-2 rtl:mr-2 shrink-0">
                <button
                    v-if="clearable && selectedLabel && !disabled"
                    type="button"
                    @click.stop="clearSelection"
                    class="p-0.5 rounded-full text-gray-300 hover:text-gray-500 hover:bg-gray-100 transition-all opacity-0 group-hover:opacity-100"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </div>
        </div>

        <!-- Open State -->
        <div v-if="isOpen" class="relative z-50">
            <!-- Search Input -->
            <div
                class="flex items-center gap-2.5 w-full rounded-t-xl border bg-white"
                :class="[sizeClasses.trigger, error ? 'border-red-300' : 'border-gray-200 ring-2 ring-[var(--accent)]/20 border-[var(--accent)]']"
                :style="{ '--accent': accentColor }"
            >
                <svg class="w-4 h-4 text-gray-400 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input
                    ref="inputRef"
                    v-model="searchQuery"
                    :placeholder="searchPlaceholder"
                    type="text"
                    class="doctorato-input flex-1 border-0 outline-none bg-transparent p-0 focus:ring-0 text-gray-800 placeholder-gray-400"
                    :class="sizeClasses.dropdown"
                    @keydown="handleKeydown"
                />
                <button type="button" @click="closeDropdown" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                </button>
            </div>

            <!-- Dropdown -->
            <div
                ref="dropdownRef"
                class="absolute top-full inset-x-0 z-50 max-h-[260px] overflow-y-auto bg-white border border-t-0 border-gray-200 rounded-b-xl shadow-xl shadow-black/[0.06] overscroll-contain"
                :class="entering ? 'animate-dropdown-in' : ''"
            >
                <!-- No Results -->
                <div v-if="filteredOptions.length === 0" class="flex flex-col items-center justify-center py-8 text-gray-400">
                    <svg class="w-8 h-8 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <span class="text-sm">No results found</span>
                </div>

                <!-- Options -->
                <div
                    v-for="(option, index) in filteredOptions"
                    :key="option.value"
                    data-option
                    @mousedown.prevent="selectOption(option)"
                    @mouseenter="highlightedIndex = index"
                    class="flex items-center gap-3 cursor-pointer transition-colors duration-100"
                    :class="[
                        sizeClasses.option,
                        highlightedIndex === index ? 'bg-gray-50' : '',
                        String(modelValue) === String(option.value) ? 'font-semibold' : '',
                    ]"
                    :style="String(modelValue) === String(option.value) ? { color: accentColor } : {}"
                >
                    <span v-if="option.color" class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: option.color }"></span>
                    <div class="flex-1 min-w-0">
                        <span class="truncate block">{{ option.label }}</span>
                        <span v-if="option.subtitle" class="block text-[11px] text-gray-400 truncate mt-0.5">{{ option.subtitle }}</span>
                    </div>
                    <span v-if="option.badge" class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 shrink-0">{{ option.badge }}</span>
                    <svg
                        v-if="String(modelValue) === String(option.value)"
                        class="w-4 h-4 shrink-0"
                        :style="{ color: accentColor }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </div>

        <p v-if="error" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
@keyframes dropdownIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-dropdown-in {
    animation: dropdownIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Scrollbar */
div[ref="dropdownRef"]::-webkit-scrollbar,
.max-h-\[260px\]::-webkit-scrollbar {
    width: 5px;
}
.max-h-\[260px\]::-webkit-scrollbar-track {
    background: transparent;
}
.max-h-\[260px\]::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 3px;
}
.max-h-\[260px\]::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>
