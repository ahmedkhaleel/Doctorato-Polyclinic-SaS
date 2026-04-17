<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConversationList from '@/Components/Chat/ConversationList.vue';
import ChatWindow from '@/Components/Chat/ChatWindow.vue';
import NewConversationModal from '@/Components/Chat/NewConversationModal.vue';
import { useChatPolling } from '@/Composables/useChatPolling.js';

defineOptions({ layout: AdminLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    conversations: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    activeConversation: { type: Object, default: null },
    messages: { type: Array, default: () => [] },
});

const panelPrefix = 'admin';
const accentColor = '#C4A265';
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

function handleDeleteConversation() {
    mobileShowChat.value = false;
    router.visit(`/${panelPrefix}/chat`, { preserveState: false });
}
</script>

<template>
    <div class="h-[calc(100vh-72px-57px)] flex rounded-2xl overflow-hidden bg-white shadow-sm border border-gray-200/80">
        <!-- Conversation list (left panel) -->
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

        <!-- Chat window (right panel) -->
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
                @delete-conversation="handleDeleteConversation"
            />

            <!-- Empty state -->
            <div v-else class="flex-1 flex flex-col items-center justify-center bg-gradient-to-br from-white to-slate-50 p-6">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#1B365D]/10 to-[#C4A265]/10 flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-[#1B365D]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-[#1B365D] mb-1">{{ $t('a_your_messages') }}</h3>
                <p class="text-sm text-slate-500 mb-5">{{ $t('a_select_conversation') }}</p>
                <button
                    @click="showNewChat = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-5 py-2.5 shadow-md hover:shadow-lg transition text-sm"
                >
                    {{ $t('a_start_new_chat') }}
                </button>
            </div>
        </div>

        <!-- New conversation modal -->
        <NewConversationModal
            :users="users"
            :show="showNewChat"
            :accent-color="accentColor"
            @close="showNewChat = false"
            @select="handleNewChatSelect"
        />
    </div>
</template>
