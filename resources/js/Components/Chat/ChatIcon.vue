<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useChatPolling } from '@/Composables/useChatPolling';

const props = defineProps({
    panelPrefix: { type: String, required: true },
    accentColor: { type: String, default: '#C4A265' },
});

const page = usePage();
const { unreadCount, startUnreadPolling, stopUnreadPolling } = useChatPolling(props.panelPrefix);

// Get initial count from Inertia shared data
const initialCount = computed(() => page.props.chat_notifications?.unread_count || 0);

onMounted(() => {
    unreadCount.value = initialCount.value;
    startUnreadPolling();
});

function goToChat() {
    router.visit(`/${props.panelPrefix}/chat`);
}

onUnmounted(() => {
    stopUnreadPolling();
});
</script>

<template>
    <button
        @click="goToChat"
        class="relative inline-flex items-center justify-center w-10 h-10 rounded-xl text-gray-500 hover:bg-gray-100 transition-all duration-200"
        title="Messages"
    >
        <!-- Chat bubble icon -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>

        <!-- Unread badge -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="scale-0 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-0 opacity-0"
        >
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center text-white text-[10px] font-bold rounded-full shadow-sm ring-2 ring-white"
                :style="{ backgroundColor: accentColor }"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </Transition>
    </button>
</template>
