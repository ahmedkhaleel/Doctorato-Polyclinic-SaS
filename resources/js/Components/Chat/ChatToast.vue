<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    panelPrefix: { type: String, required: true },
    accentColor: { type: String, default: '#C4A265' },
});

const toasts = ref([]);
let toastId = 0;

function handleNewMessageToast(e) {
    const { sender_name, sender_id, body, has_attachment } = e.detail || {};
    if (!sender_name) return;

    // Don't show toast if user is already on the chat page with this sender
    if (window.location.pathname.includes(`/${props.panelPrefix}/chat/${sender_id}`)) {
        return;
    }

    const id = ++toastId;
    const preview = body
        ? (body.length > 60 ? body.substring(0, 60) + '...' : body)
        : (has_attachment ? 'Attachment' : 'New message');

    toasts.value.push({ id, sender_name, sender_id, preview });

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        dismissToast(id);
    }, 5000);
}

function dismissToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

function openChat(senderId) {
    router.visit(`/${props.panelPrefix}/chat/${senderId}`);
    // Dismiss all toasts from this sender
    toasts.value = toasts.value.filter(t => t.sender_id !== senderId);
}

onMounted(() => {
    window.addEventListener('chat-new-message-toast', handleNewMessageToast);
});

onUnmounted(() => {
    window.removeEventListener('chat-new-message-toast', handleNewMessageToast);
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none" style="max-width: 360px;">
            <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
                move-class="transition-all duration-200"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden cursor-pointer hover:shadow-xl transition-shadow"
                    @click="openChat(toast.sender_id)"
                >
                    <div class="flex items-start gap-3 p-3.5">
                        <!-- Avatar -->
                        <div
                            class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-sm"
                            :style="{ backgroundColor: accentColor }"
                        >
                            {{ toast.sender_name.charAt(0).toUpperCase() }}
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ toast.sender_name }}</p>
                                <button
                                    @click.stop="dismissToast(toast.id)"
                                    class="flex-shrink-0 w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ toast.preview }}</p>
                        </div>
                    </div>
                    <!-- Accent bar -->
                    <div class="h-0.5" :style="{ backgroundColor: accentColor }"></div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
