<script setup>
const props = defineProps({
    type: { type: String, default: 'card' }, // card, list, stats, table, chart
    count: { type: Number, default: 3 },
    animate: { type: Boolean, default: true },
});
</script>

<template>
    <!-- Stats Skeleton -->
    <div v-if="type === 'stats'" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div v-for="i in count" :key="i" class="bg-white rounded-xl border border-gray-100 p-5 overflow-hidden relative">
            <div :class="animate ? 'skeleton-shimmer' : ''">
                <div class="h-3 w-20 bg-gray-200 rounded-full mb-3"></div>
                <div class="h-7 w-16 bg-gray-200 rounded-lg mb-2"></div>
                <div class="h-2.5 w-24 bg-gray-100 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Card Skeleton -->
    <div v-else-if="type === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in count" :key="i" class="bg-white rounded-xl border border-gray-100 overflow-hidden relative">
            <div :class="animate ? 'skeleton-shimmer' : ''">
                <div class="p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-11 h-11 rounded-xl bg-gray-200"></div>
                        <div class="flex-1">
                            <div class="h-4 w-32 bg-gray-200 rounded-full mb-2"></div>
                            <div class="h-3 w-20 bg-gray-100 rounded-full"></div>
                        </div>
                    </div>
                    <div class="space-y-2.5">
                        <div class="h-3 w-full bg-gray-100 rounded-full"></div>
                        <div class="h-3 w-3/4 bg-gray-100 rounded-full"></div>
                        <div class="h-3 w-1/2 bg-gray-100 rounded-full"></div>
                    </div>
                    <div class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-50">
                        <div class="h-6 w-16 bg-gray-100 rounded-full"></div>
                        <div class="h-6 w-20 bg-gray-100 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- List Skeleton -->
    <div v-else-if="type === 'list'" class="space-y-3">
        <div v-for="i in count" :key="i" class="bg-white rounded-xl border border-gray-100 overflow-hidden relative">
            <div :class="animate ? 'skeleton-shimmer' : ''" class="flex items-center gap-4 p-4">
                <div class="w-11 h-11 rounded-xl bg-gray-200 shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="h-4 w-40 bg-gray-200 rounded-full mb-2"></div>
                    <div class="h-3 w-24 bg-gray-100 rounded-full"></div>
                </div>
                <div class="hidden sm:flex items-center gap-4">
                    <div class="h-3.5 w-20 bg-gray-100 rounded-full"></div>
                    <div class="h-3.5 w-16 bg-gray-100 rounded-full"></div>
                </div>
                <div class="h-7 w-20 bg-gray-100 rounded-full shrink-0"></div>
            </div>
        </div>
    </div>

    <!-- Table Skeleton -->
    <div v-else-if="type === 'table'" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div :class="animate ? 'skeleton-shimmer' : ''">
            <!-- Table Header -->
            <div class="flex items-center gap-4 px-5 py-3.5 bg-gray-50/50 border-b border-gray-100">
                <div class="h-3.5 w-8 bg-gray-200 rounded-full"></div>
                <div class="h-3.5 w-32 bg-gray-200 rounded-full"></div>
                <div class="flex-1"></div>
                <div class="h-3.5 w-20 bg-gray-200 rounded-full"></div>
                <div class="h-3.5 w-16 bg-gray-200 rounded-full"></div>
                <div class="h-3.5 w-16 bg-gray-200 rounded-full"></div>
            </div>
            <!-- Table Rows -->
            <div v-for="i in count" :key="i" class="flex items-center gap-4 px-5 py-4 border-b border-gray-50 last:border-0">
                <div class="w-4 h-4 rounded bg-gray-100"></div>
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-9 h-9 rounded-full bg-gray-200"></div>
                    <div>
                        <div class="h-3.5 w-28 bg-gray-200 rounded-full mb-1.5"></div>
                        <div class="h-2.5 w-20 bg-gray-100 rounded-full"></div>
                    </div>
                </div>
                <div class="h-3 w-16 bg-gray-100 rounded-full"></div>
                <div class="h-6 w-16 bg-gray-100 rounded-full"></div>
                <div class="h-6 w-14 bg-gray-100 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Chart Skeleton -->
    <div v-else-if="type === 'chart'" class="bg-white rounded-xl border border-gray-100 p-5 overflow-hidden relative">
        <div :class="animate ? 'skeleton-shimmer' : ''">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="h-4 w-32 bg-gray-200 rounded-full mb-2"></div>
                    <div class="h-3 w-48 bg-gray-100 rounded-full"></div>
                </div>
                <div class="flex gap-2">
                    <div class="h-8 w-16 bg-gray-100 rounded-lg"></div>
                    <div class="h-8 w-16 bg-gray-100 rounded-lg"></div>
                </div>
            </div>
            <div class="flex items-end gap-2 h-40">
                <div v-for="i in 12" :key="i" class="flex-1 rounded-t-lg bg-gray-100" :style="{ height: (20 + Math.random() * 80) + '%' }"></div>
            </div>
            <div class="flex justify-between mt-3">
                <div v-for="i in 6" :key="i" class="h-2.5 w-8 bg-gray-100 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Hero Skeleton -->
    <div v-else-if="type === 'hero'" class="rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 p-6 sm:p-8 overflow-hidden relative">
        <div :class="animate ? 'skeleton-shimmer' : ''">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-xl bg-gray-200"></div>
                <div>
                    <div class="h-6 w-48 bg-gray-200 rounded-full mb-2"></div>
                    <div class="h-3.5 w-32 bg-gray-100 rounded-full"></div>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="i in 4" :key="i" class="bg-white/60 rounded-xl px-4 py-3">
                    <div class="h-2.5 w-16 bg-gray-200 rounded-full mb-2"></div>
                    <div class="h-5 w-12 bg-gray-200 rounded-lg"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.skeleton-shimmer {
    position: relative;
    overflow: hidden;
}

.skeleton-shimmer::after {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 1;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(255, 255, 255, 0.4) 40%,
        rgba(255, 255, 255, 0.6) 50%,
        rgba(255, 255, 255, 0.4) 60%,
        transparent 100%
    );
    background-size: 200% 100%;
    animation: skeletonShimmer 1.8s ease-in-out infinite;
}

@keyframes skeletonShimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
