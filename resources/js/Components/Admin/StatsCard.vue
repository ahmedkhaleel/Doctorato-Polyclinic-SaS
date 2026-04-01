<script setup>
defineProps({
    title: String,
    value: [String, Number],
    icon: {
        type: String,
        default: 'chart',
    },
    color: {
        type: String,
        default: 'gold',
    },
    href: {
        type: String,
        default: null,
    },
});

const colorConfig = {
    gold:   { gradient: 'from-[#C4A265] to-[#D4B87A]', bg: 'bg-[#C4A265]/10', text: 'text-[#C4A265]' },
    blue:   { gradient: 'from-blue-500 to-blue-600',    bg: 'bg-blue-50',       text: 'text-blue-600' },
    green:  { gradient: 'from-emerald-500 to-emerald-600', bg: 'bg-emerald-50', text: 'text-emerald-600' },
    red:    { gradient: 'from-rose-500 to-rose-600',    bg: 'bg-rose-50',       text: 'text-rose-600' },
    purple: { gradient: 'from-purple-500 to-purple-600', bg: 'bg-purple-50',    text: 'text-purple-600' },
};
</script>

<template>
    <component
        :is="href ? 'a' : 'div'"
        :href="href"
        class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden"
        :class="{ 'cursor-pointer': href }"
    >
        <!-- Accent top bar -->
        <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${colorConfig[color]?.gradient || colorConfig.gold.gradient} opacity-80`"></div>

        <div class="flex items-start justify-between">
            <div>
                <p class="text-[13px] font-medium text-gray-500">{{ title }}</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ value }}</p>
            </div>
            <div
                :class="colorConfig[color]?.bg || colorConfig.gold.bg"
                class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
            >
                <slot name="icon">
                    <svg :class="colorConfig[color]?.text || colorConfig.gold.text" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </slot>
            </div>
        </div>
    </component>
</template>
