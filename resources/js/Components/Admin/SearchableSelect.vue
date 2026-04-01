<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
    modelValue: [String, Number, null],
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select...',
    },
    searchPlaceholder: {
        type: String,
        default: 'Type to search...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');
const isOpen = ref(false);
const highlightedIndex = ref(-1);
const containerRef = ref(null);
const inputRef = ref(null);
const dropdownRef = ref(null);

// Find the selected option's label
const selectedLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return '';
    const found = props.options.find(opt => String(opt.value) === String(props.modelValue));
    return found ? found.label : '';
});

// Filter options based on search query (supports searchText for extended matching)
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options.slice(0, 50);
    const query = searchQuery.value.toLowerCase();
    return props.options
        .filter(opt => {
            const text = (opt.searchText || opt.label || '').toLowerCase();
            return text.includes(query);
        })
        .slice(0, 50);
});

function openDropdown() {
    if (props.disabled) return;
    isOpen.value = true;
    searchQuery.value = '';
    highlightedIndex.value = -1;
    nextTick(() => {
        inputRef.value?.focus();
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

function clearSelection() {
    emit('update:modelValue', '');
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
            if (highlightedIndex.value < filteredOptions.value.length - 1) {
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
        const highlighted = items[highlightedIndex.value];
        if (highlighted) {
            highlighted.scrollIntoView({ block: 'nearest' });
        }
    });
}

// Click outside to close
function handleClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

// Reset highlighted index when filtered options change
watch(filteredOptions, () => {
    highlightedIndex.value = -1;
});
</script>

<template>
    <div ref="containerRef" class="searchable-select-container">
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>

        <!-- Trigger button (shows selected value or placeholder) -->
        <div
            v-if="!isOpen"
            @click="openDropdown"
            :class="[
                'searchable-select-trigger',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                error ? 'border-red-400 focus:ring-red-200' : 'border-gray-300 focus:ring-yellow-200',
            ]"
            tabindex="0"
            @keydown="handleKeydown"
        >
            <span v-if="selectedLabel" class="text-gray-900 truncate flex-1">{{ selectedLabel }}</span>
            <span v-else class="text-gray-400 truncate flex-1">{{ placeholder }}</span>

            <div class="flex items-center gap-1 ml-2 shrink-0">
                <!-- Clear button -->
                <button
                    v-if="selectedLabel && !disabled"
                    type="button"
                    @click.stop="clearSelection"
                    class="text-gray-400 hover:text-gray-600 p-0.5 rounded-full hover:bg-gray-100 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <!-- Chevron -->
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Open state: search input + dropdown -->
        <div v-if="isOpen" class="relative">
            <div class="searchable-select-input-wrapper" :class="error ? 'border-red-400' : 'border-gray-300'">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    ref="inputRef"
                    v-model="searchQuery"
                    :placeholder="searchPlaceholder"
                    type="text"
                    class="flex-1 border-0 outline-none text-sm bg-transparent p-0 focus:ring-0"
                    @keydown="handleKeydown"
                />
                <button
                    type="button"
                    @click="closeDropdown"
                    class="text-gray-400 hover:text-gray-600 p-0.5 rounded-full hover:bg-gray-100 transition shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </button>
            </div>

            <!-- Dropdown list -->
            <div
                ref="dropdownRef"
                class="searchable-select-dropdown"
            >
                <div
                    v-if="filteredOptions.length === 0"
                    class="px-4 py-3 text-sm text-gray-400 text-center"
                >
                    No results found
                </div>
                <div
                    v-for="(option, index) in filteredOptions"
                    :key="option.value"
                    data-option
                    @mousedown.prevent="selectOption(option)"
                    @mouseenter="highlightedIndex = index"
                    :class="[
                        'searchable-select-option',
                        highlightedIndex === index ? 'bg-amber-50' : '',
                        String(props.modelValue) === String(option.value) ? 'selected-option' : '',
                    ]"
                >
                    <div class="truncate flex-1 min-w-0">
                        <span class="truncate">{{ option.label }}</span>
                        <span v-if="option.subtitle" class="block text-xs text-gray-400 truncate">{{ option.subtitle }}</span>
                    </div>
                    <svg
                        v-if="String(props.modelValue) === String(option.value)"
                        class="w-4 h-4 shrink-0"
                        style="color: #C4A265;"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Error message -->
        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<style scoped>
.searchable-select-container {
    position: relative;
}

.searchable-select-trigger {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.5rem 1rem;
    border: 1px solid;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    background-color: white;
    transition: all 0.15s ease;
    min-height: 42px;
}

.searchable-select-trigger:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(253, 224, 71, 0.5);
    border-color: transparent;
}

.searchable-select-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem 1rem;
    border: 1px solid;
    border-radius: 0.5rem 0.5rem 0 0;
    font-size: 0.875rem;
    background-color: white;
    min-height: 42px;
}

.searchable-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 50;
    max-height: 240px;
    overflow-y: auto;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 0.5rem 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.searchable-select-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
    transition: background-color 0.1s ease;
}

.searchable-select-option:hover {
    background-color: #fffbeb;
}

.searchable-select-option.selected-option {
    color: #C4A265;
    font-weight: 500;
}

/* Scrollbar styling */
.searchable-select-dropdown::-webkit-scrollbar {
    width: 6px;
}

.searchable-select-dropdown::-webkit-scrollbar-track {
    background: #f9fafb;
}

.searchable-select-dropdown::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.searchable-select-dropdown::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
