<script setup>
import { watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirm Action' },
    message: { type: String, default: 'Are you sure you want to proceed?' },
    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Cancel' },
    confirmColor: { type: String, default: 'red' },
});

const emit = defineEmits(['confirm', 'cancel']);

function handleKeydown(e) {
    if (e.key === 'Escape' && props.show) {
        emit('cancel');
    }
}

onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => document.removeEventListener('keydown', handleKeydown));

const colorMap = {
    red: { bg: 'bg-red-600 hover:bg-red-700', icon: 'bg-red-100 text-red-600' },
    gold: { bg: 'bg-[#C4A265] hover:bg-[#B3914F]', icon: 'bg-[#C4A265]/10 text-[#C4A265]' },
    blue: { bg: 'bg-[#1B365D] hover:bg-[#1B365D]', icon: 'bg-slate-100 text-[#1B365D]' },
    green: { bg: 'bg-emerald-600 hover:bg-emerald-700', icon: 'bg-emerald-100 text-emerald-600' },
    cyan: { bg: 'bg-[#1B365D] hover:bg-[#1B365D]', icon: 'bg-slate-100 text-[#1B365D]' },
    amber: { bg: 'bg-amber-600 hover:bg-amber-700', icon: 'bg-amber-100 text-amber-600' },
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="$emit('cancel')">
                <div class="fixed inset-0 bg-black/40"></div>
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="show" class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 z-10">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :class="colorMap[confirmColor]?.icon || colorMap.red.icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ message }}</p>
                            </div>
                        </div>
                        <div class="mt-5 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="$emit('cancel')"
                                class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                            >
                                {{ cancelText }}
                            </button>
                            <button
                                type="button"
                                @click="$emit('confirm')"
                                class="px-4 py-2 rounded-xl text-white text-sm font-medium transition"
                                :class="colorMap[confirmColor]?.bg || colorMap.red.bg"
                            >
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
