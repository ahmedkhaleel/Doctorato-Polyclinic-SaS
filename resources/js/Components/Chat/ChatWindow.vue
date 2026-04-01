<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import UserAvatar from './UserAvatar.vue';
import MessageBubble from './MessageBubble.vue';
import MessageInput from './MessageInput.vue';

const props = defineProps({
    otherUser: { type: Object, default: null },
    messages: { type: Array, default: () => [] },
    currentUserId: { type: Number, required: true },
    accentColor: { type: String, default: '#C4A265' },
    panelPrefix: { type: String, required: true },
});

const emit = defineEmits(['send', 'back', 'loadOlder', 'deleteConversation']);

const messagesContainer = ref(null);
const localMessages = ref([...props.messages]);
const loading = ref(false);
const loadingOlder = ref(false);
const hasMore = ref(true);
const sendFailed = ref(false);
const sending = ref(false);
const showDeleteConfirm = ref(false);
const deleting = ref(false);
let pollInterval = null;

// Get the last message ID for polling
function getLastMessageId() {
    if (localMessages.value.length === 0) return null;
    return localMessages.value[localMessages.value.length - 1].id;
}

// Scroll to bottom
function scrollToBottom(smooth = false) {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTo({
                top: messagesContainer.value.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto',
            });
        }
    });
}

// Poll for new messages
async function pollNewMessages() {
    if (!props.otherUser) return;
    const lastId = getLastMessageId();
    try {
        const res = await fetch(`/${props.panelPrefix}/chat/${props.otherUser.id}/poll?after=${lastId || ''}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const data = await res.json();
            if (data.messages && data.messages.length > 0) {
                // Add new messages that don't already exist
                const existingIds = new Set(localMessages.value.map(m => m.id));
                const newMsgs = data.messages.filter(m => !existingIds.has(m.id));
                if (newMsgs.length > 0) {
                    localMessages.value.push(...newMsgs);
                    scrollToBottom(true);
                    // Play sound for messages from others
                    if (newMsgs.some(m => m.sender_id !== props.currentUserId)) {
                        playSound();
                    }
                }
            }
        }
    } catch (e) {
        // Silently fail
    }
}

// Load older messages
async function loadOlderMessages() {
    if (loadingOlder.value || !hasMore.value || localMessages.value.length === 0) return;
    loadingOlder.value = true;
    const firstId = localMessages.value[0].id;
    try {
        const res = await fetch(`/${props.panelPrefix}/chat/${props.otherUser.id}/older?before=${firstId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const data = await res.json();
            if (data.messages && data.messages.length > 0) {
                const container = messagesContainer.value;
                const oldScrollHeight = container.scrollHeight;
                localMessages.value.unshift(...data.messages);
                nextTick(() => {
                    container.scrollTop = container.scrollHeight - oldScrollHeight;
                });
            } else {
                hasMore.value = false;
            }
        }
    } catch (e) {
        // Silently fail
    } finally {
        loadingOlder.value = false;
    }
}

// Handle scroll for load older
function handleScroll() {
    if (messagesContainer.value && messagesContainer.value.scrollTop < 50) {
        loadOlderMessages();
    }
}

// Handle send
function handleSend({ body, attachment }) {
    sendFailed.value = false;
    sending.value = true;
    emit('send', { body, attachment, callback: (newMessage) => {
        sending.value = false;
        if (newMessage) {
            localMessages.value.push(newMessage);
            scrollToBottom(true);
        } else {
            // Send failed — show error feedback
            sendFailed.value = true;
            setTimeout(() => { sendFailed.value = false; }, 5000);
        }
    }});
}

// Delete conversation
async function handleDelete() {
    deleting.value = true;
    try {
        const res = await fetch(`/${props.panelPrefix}/chat/${props.otherUser.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });
        if (res.ok) {
            showDeleteConfirm.value = false;
            emit('deleteConversation');
        }
    } catch (e) {
        // Silently fail
    } finally {
        deleting.value = false;
    }
}

// Play notification sound — two-tone chime for received messages
function playSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        // First tone
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.frequency.value = 880;
        osc1.type = 'sine';
        gain1.gain.setValueAtTime(0.1, now);
        gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
        osc1.start(now);
        osc1.stop(now + 0.15);
        // Second tone (higher, slight delay)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.frequency.value = 1175;
        osc2.type = 'sine';
        gain2.gain.setValueAtTime(0.08, now + 0.12);
        gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.35);
        osc2.start(now + 0.12);
        osc2.stop(now + 0.35);
    } catch (e) {}
}

// Watch for props.messages changes (initial load / conversation switch)
watch(() => props.messages, (newVal) => {
    localMessages.value = [...newVal];
    hasMore.value = true;
    nextTick(() => scrollToBottom());
}, { deep: true });

watch(() => props.otherUser?.id, () => {
    localMessages.value = [...props.messages];
    hasMore.value = true;
    sendFailed.value = false;
    nextTick(() => scrollToBottom());
    // Immediately poll when switching to a new conversation
    pollNewMessages();
});

onMounted(() => {
    scrollToBottom();
    // Poll immediately on mount, then every 5 seconds
    pollNewMessages();
    pollInterval = setInterval(pollNewMessages, 5000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

function formatDate(isoString) {
    const date = new Date(isoString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) return 'Today';
    if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function shouldShowDate(index) {
    if (index === 0) return true;
    const curr = new Date(localMessages.value[index].created_at).toDateString();
    const prev = new Date(localMessages.value[index - 1].created_at).toDateString();
    return curr !== prev;
}
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 bg-white">
            <!-- Back button (mobile) -->
            <button
                @click="emit('back')"
                class="lg:hidden flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <UserAvatar
                v-if="otherUser"
                :name="otherUser.name"
                :is-online="otherUser.is_online"
                :accent-color="accentColor"
            />
            <div v-if="otherUser" class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-gray-800 truncate">{{ otherUser.name }}</h3>
                <p class="text-[11px] text-gray-400 capitalize">
                    {{ otherUser.role_display || otherUser.role }}
                    <span v-if="otherUser.is_online" class="text-emerald-500 font-medium"> &bull; Online</span>
                    <span v-else> &bull; Offline</span>
                </p>
            </div>

            <!-- Delete conversation button -->
            <button
                @click="showDeleteConfirm = true"
                class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all duration-200"
                title="Delete conversation"
            >
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>

        <!-- Send error banner -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <div v-if="sendFailed" class="bg-red-50 border-b border-red-200 px-4 py-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs text-red-600">Failed to send message. Please try again.</span>
                <button @click="sendFailed = false" class="ml-auto text-red-400 hover:text-red-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- Messages area -->
        <div
            ref="messagesContainer"
            @scroll="handleScroll"
            class="flex-1 overflow-y-auto px-4 py-4 bg-[#f8f9fb]"
        >
            <!-- Load older indicator -->
            <div v-if="loadingOlder" class="flex justify-center py-3">
                <svg class="animate-spin w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>

            <!-- Messages -->
            <template v-for="(msg, index) in localMessages" :key="msg.id">
                <!-- Date separator -->
                <div v-if="shouldShowDate(index)" class="flex items-center gap-3 my-4">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ formatDate(msg.created_at) }}</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <MessageBubble
                    :message="msg"
                    :current-user-id="currentUserId"
                    :accent-color="accentColor"
                />
            </template>

            <!-- Empty state -->
            <div v-if="localMessages.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">No messages yet</p>
                <p class="text-xs text-gray-400 mt-1">Send a message to start the conversation</p>
            </div>
        </div>

        <!-- Input -->
        <MessageInput :accent-color="accentColor" :disabled="sending" @send="handleSend" />

        <!-- Delete confirmation modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>

                    <!-- Modal -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-2"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-2"
                    >
                        <div v-if="showDeleteConfirm" class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
                            <!-- Icon -->
                            <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>

                            <h3 class="text-base font-bold text-gray-800 text-center mb-1">Delete Conversation</h3>
                            <p class="text-sm text-gray-500 text-center mb-6">
                                Are you sure you want to delete the entire conversation with
                                <span class="font-semibold text-gray-700">{{ otherUser?.name }}</span>?
                                This action cannot be undone.
                            </p>

                            <div class="flex gap-3">
                                <button
                                    @click="showDeleteConfirm = false"
                                    :disabled="deleting"
                                    class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="handleDelete"
                                    :disabled="deleting"
                                    class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500 hover:bg-red-600 transition-colors flex items-center justify-center gap-2"
                                >
                                    <svg v-if="deleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    <span>{{ deleting ? 'Deleting...' : 'Delete' }}</span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
