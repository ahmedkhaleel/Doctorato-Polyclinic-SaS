<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: { type: Object, required: true },
    currentUserId: { type: Number, required: true },
    accentColor: { type: String, default: '#C4A265' },
});

const isMine = computed(() => props.message.sender_id === props.currentUserId);

function timeAgo(isoString) {
    const now = new Date();
    const time = new Date(isoString);
    const diffMs = now - time;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHr = Math.floor(diffMs / 3600000);

    if (diffMin < 1) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    if (diffHr < 24) return `${diffHr}h ago`;
    return time.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatTime(isoString) {
    return new Date(isoString).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

const isImage = computed(() => {
    if (!props.message.attachment_name) return false;
    return /\.(jpg|jpeg|png|gif|webp)$/i.test(props.message.attachment_name);
});

function openImage(path) {
    window.open(`/storage/${path}`, '_blank');
}
</script>

<template>
    <div class="flex gap-2 mb-3" :class="isMine ? 'justify-end' : 'justify-start'">
        <div class="max-w-[75%] min-w-[80px]">
            <!-- Message bubble -->
            <div
                class="rounded-2xl px-4 py-2.5 shadow-sm"
                :class="isMine
                    ? 'rounded-br-md text-white'
                    : 'rounded-bl-md bg-white text-gray-800 border border-gray-100'"
                :style="isMine ? { backgroundColor: accentColor } : {}"
            >
                <!-- Text body -->
                <p v-if="message.body" class="text-[13px] leading-relaxed whitespace-pre-wrap break-words">{{ message.body }}</p>

                <!-- Attachment -->
                <div v-if="message.attachment_path" class="mt-2">
                    <!-- Image preview -->
                    <img
                        v-if="isImage"
                        :src="`/storage/${message.attachment_path}`"
                        :alt="message.attachment_name"
                        class="rounded-lg max-w-full max-h-48 object-cover cursor-pointer"
                        @click="openImage(message.attachment_path)"
                    />
                    <!-- File download -->
                    <a
                        v-else
                        :href="`/storage/${message.attachment_path}`"
                        target="_blank"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors"
                        :class="isMine ? 'bg-white/20 text-white hover:bg-white/30' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="truncate">{{ message.attachment_name }}</span>
                    </a>
                </div>
            </div>

            <!-- Timestamp + read status -->
            <div class="flex items-center gap-1.5 mt-1 px-1" :class="isMine ? 'justify-end' : 'justify-start'">
                <span class="text-[10px] text-gray-400">{{ formatTime(message.created_at) }}</span>
                <!-- Read receipt for sent messages -->
                <svg v-if="isMine && message.read_at" class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else-if="isMine" class="w-3.5 h-3.5 text-gray-300" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
    </div>
</template>
