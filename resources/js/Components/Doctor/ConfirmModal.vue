<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Cancel' },
    type: { type: String, default: 'info' }, // info, success, warning, danger
    processing: { type: Boolean, default: false },
    icon: { type: String, default: '' }, // custom SVG path
});

const emit = defineEmits(['confirm', 'cancel']);

const typeConfig = computed(() => {
    const configs = {
        info: {
            iconBg: 'bg-blue-50',
            iconColor: 'text-blue-500',
            iconPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            btnClass: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20',
        },
        success: {
            iconBg: 'bg-emerald-50',
            iconColor: 'text-emerald-500',
            iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            btnClass: 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20',
        },
        warning: {
            iconBg: 'bg-amber-50',
            iconColor: 'text-amber-500',
            iconPath: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            btnClass: 'bg-amber-600 hover:bg-amber-700 shadow-amber-500/20',
        },
        danger: {
            iconBg: 'bg-red-50',
            iconColor: 'text-red-500',
            iconPath: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
            btnClass: 'bg-red-600 hover:bg-red-700 shadow-red-500/20',
        },
    };
    return configs[props.type] || configs.info;
});

const iconSvgPath = computed(() => props.icon || typeConfig.value.iconPath);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="!processing && $emit('cancel')"></div>

                <!-- Modal -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-90 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="show" class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div class="p-6 text-center">
                            <!-- Icon -->
                            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center" :class="typeConfig.iconBg">
                                <svg class="w-7 h-7" :class="typeConfig.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="iconSvgPath" />
                                </svg>
                            </div>

                            <!-- Title -->
                            <h3 class="text-lg font-bold text-gray-900 mb-1.5">{{ title }}</h3>

                            <!-- Message -->
                            <p class="text-sm text-gray-500 leading-relaxed">{{ message }}</p>

                            <!-- Custom slot for extra content -->
                            <div v-if="$slots.default" class="mt-4">
                                <slot />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 px-6 pb-6">
                            <button
                                type="button"
                                @click="$emit('cancel')"
                                :disabled="processing"
                                class="flex-1 py-2.5 px-4 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200 disabled:opacity-50"
                            >
                                {{ cancelText }}
                            </button>
                            <button
                                type="button"
                                @click="$emit('confirm')"
                                :disabled="processing"
                                class="flex-1 py-2.5 px-4 text-sm font-semibold text-white rounded-xl transition-all duration-200 shadow-lg disabled:opacity-50 inline-flex items-center justify-center gap-2"
                                :class="typeConfig.btnClass"
                            >
                                <svg v-if="processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
