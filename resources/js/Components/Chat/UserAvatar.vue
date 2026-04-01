<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, default: '' },
    isOnline: { type: Boolean, default: false },
    size: { type: String, default: 'md' }, // sm, md, lg
    accentColor: { type: String, default: '#C4A265' },
});

const initials = computed(() => {
    const parts = props.name.trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return props.name.charAt(0).toUpperCase();
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm': return 'w-8 h-8 text-xs';
        case 'lg': return 'w-12 h-12 text-base';
        default: return 'w-10 h-10 text-sm';
    }
});

const dotSize = computed(() => {
    switch (props.size) {
        case 'sm': return 'w-2 h-2 -bottom-0 -right-0';
        case 'lg': return 'w-3.5 h-3.5 -bottom-0.5 -right-0.5';
        default: return 'w-2.5 h-2.5 -bottom-0 -right-0';
    }
});
</script>

<template>
    <div class="relative flex-shrink-0">
        <div
            class="rounded-full flex items-center justify-center font-bold text-white"
            :class="sizeClasses"
            :style="{ backgroundColor: accentColor }"
        >
            {{ initials }}
        </div>
        <span
            v-if="isOnline"
            class="absolute rounded-full bg-emerald-500 ring-2 ring-white"
            :class="dotSize"
        ></span>
    </div>
</template>
