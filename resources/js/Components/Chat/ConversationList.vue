<script setup>
import { ref, computed } from 'vue';
import UserAvatar from './UserAvatar.vue';

const props = defineProps({
    conversations: { type: Array, default: () => [] },
    activeUserId: { type: Number, default: null },
    accentColor: { type: String, default: '#C4A265' },
});

const emit = defineEmits(['select', 'newChat']);
const search = ref('');

const filteredConversations = computed(() => {
    if (!search.value.trim()) return props.conversations;
    const q = search.value.toLowerCase();
    return props.conversations.filter(c =>
        c.user.name.toLowerCase().includes(q) ||
        c.user.email.toLowerCase().includes(q) ||
        (c.user.role_display || '').toLowerCase().includes(q)
    );
});

function timeAgo(isoString) {
    if (!isoString) return '';
    const now = new Date();
    const time = new Date(isoString);
    const diffMs = now - time;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHr = Math.floor(diffMs / 3600000);
    const diffDay = Math.floor(diffMs / 86400000);

    if (diffMin < 1) return 'now';
    if (diffMin < 60) return `${diffMin}m`;
    if (diffHr < 24) return `${diffHr}h`;
    if (diffDay < 7) return `${diffDay}d`;
    return time.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function getPreview(conv) {
    if (!conv.last_message) return 'No messages yet';
    if (conv.last_message.attachment_name && !conv.last_message.body) return conv.last_message.attachment_name;
    return conv.last_message.body || '';
}
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="p-4 border-b border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-gray-800">Messages</h2>
                <button
                    @click="emit('newChat')"
                    class="w-9 h-9 rounded-xl flex items-center justify-center text-white transition-all hover:opacity-90 shadow-sm"
                    :style="{ backgroundColor: accentColor }"
                    title="New conversation"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
            <!-- Search -->
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search conversations..."
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-gray-300 focus:border-gray-300 bg-gray-50 transition-all"
                />
            </div>
        </div>

        <!-- Conversation list -->
        <div class="flex-1 overflow-y-auto">
            <button
                v-for="conv in filteredConversations"
                :key="conv.user.id"
                @click="emit('select', conv.user)"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors text-left border-b border-gray-50"
                :class="conv.user.id === activeUserId ? 'bg-gray-50' : ''"
            >
                <UserAvatar
                    :name="conv.user.name"
                    :is-online="conv.user.is_online"
                    :accent-color="accentColor"
                />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[13px] font-semibold text-gray-800 truncate">{{ conv.user.name }}</span>
                        <span class="text-[10px] text-gray-400 flex-shrink-0">{{ timeAgo(conv.last_message?.created_at) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2 mt-0.5">
                        <p class="text-[12px] text-gray-500 truncate">{{ getPreview(conv) }}</p>
                        <span
                            v-if="conv.unread_count > 0"
                            class="flex-shrink-0 min-w-[18px] h-[18px] px-1 flex items-center justify-center text-[10px] font-bold text-white rounded-full"
                            :style="{ backgroundColor: accentColor }"
                        >
                            {{ conv.unread_count > 99 ? '99+' : conv.unread_count }}
                        </span>
                    </div>
                    <span class="text-[10px] text-gray-400 capitalize">{{ conv.user.role_display || conv.user.role }}</span>
                </div>
            </button>

            <!-- Empty state -->
            <div v-if="filteredConversations.length === 0" class="flex flex-col items-center justify-center py-16 px-4">
                <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">No conversations yet</p>
                <p class="text-xs text-gray-400 mt-1">Start a new chat to get going</p>
            </div>
        </div>
    </div>
</template>
