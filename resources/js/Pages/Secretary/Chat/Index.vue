<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import ConversationList from '@/Components/Chat/ConversationList.vue';
import ChatWindow from '@/Components/Chat/ChatWindow.vue';
import NewConversationModal from '@/Components/Chat/NewConversationModal.vue';
import { useChatPolling } from '@/Composables/useChatPolling.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const props = defineProps({
    conversations: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    activeConversation: { type: Object, default: null },
    messages: { type: Array, default: () => [] },
});

const panelPrefix = 'secretary';
const accentColor = '#14b8a6';
const currentUserId = computed(() => page.props.auth?.user?.id);
const showNewChat = ref(false);
const mobileShowChat = ref(!!props.activeConversation);
const { sendMessage } = useChatPolling(panelPrefix);

// Auto-refresh conversation list every 10 seconds
let refreshInterval = null;
onMounted(() => {
    refreshInterval = setInterval(() => {
        router.reload({ only: ['conversations'], preserveScroll: true });
    }, 10000);
});
onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

function selectUser(user) {
    router.visit(`/${panelPrefix}/chat/${user.id}`, { preserveState: false });
    mobileShowChat.value = true;
}

function handleNewChatSelect(user) {
    showNewChat.value = false;
    router.visit(`/${panelPrefix}/chat/${user.id}`, { preserveState: false });
    mobileShowChat.value = true;
}

async function handleSend({ body, attachment, callback }) {
    const message = await sendMessage(props.activeConversation.id, body, attachment);
    if (callback) callback(message);
}

function handleBack() {
    mobileShowChat.value = false;
    router.visit(`/${panelPrefix}/chat`, { preserveState: false });
}
</script>

<template>
    <div class="h-[calc(100vh-72px-57px)] flex rounded-xl sm:rounded-2xl overflow-hidden bg-white shadow-sm border border-gray-200/80">
        <div
            class="w-full lg:w-[340px] lg:border-r border-gray-200 flex-shrink-0"
            :class="mobileShowChat && activeConversation ? 'hidden lg:flex lg:flex-col' : 'flex flex-col'"
        >
            <ConversationList
                :conversations="conversations"
                :active-user-id="activeConversation?.id"
                :accent-color="accentColor"
                @select="selectUser"
                @new-chat="showNewChat = true"
            />
        </div>
        <div
            class="flex-1 flex flex-col"
            :class="!mobileShowChat && !activeConversation ? 'hidden lg:flex' : 'flex'"
        >
            <ChatWindow
                v-if="activeConversation"
                :other-user="activeConversation"
                :messages="messages"
                :current-user-id="currentUserId"
                :accent-color="accentColor"
                :panel-prefix="panelPrefix"
                @send="handleSend"
                @back="handleBack"
            />
            <div v-else class="flex-1 flex flex-col items-center justify-center bg-[#f8f9fb]">
                <div class="w-20 h-20 rounded-3xl bg-gray-100 flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-700 mb-1">{{ isRtl ? 'رسائلك' : 'Your Messages' }}</h3>
                <p class="text-sm text-gray-400 mb-5">{{ isRtl ? 'اختر محادثة أو ابدأ واحدة جديدة' : 'Select a conversation or start a new one' }}</p>
                <button @click="showNewChat = true" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm transition-all hover:opacity-90" :style="{ backgroundColor: accentColor }">
                    Start New Chat
                </button>
            </div>
        </div>
        <NewConversationModal :users="users" :show="showNewChat" :accent-color="accentColor" @close="showNewChat = false" @select="handleNewChatSelect" />
    </div>
</template>
