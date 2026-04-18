<script setup>
import { ref } from 'vue';

const props = defineProps({
    accentColor: { type: String, default: '#C4A265' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['send']);

const body = ref('');
const attachment = ref(null);
const attachmentName = ref('');
const fileInput = ref(null);

function handleSend() {
    if (!body.value.trim() && !attachment.value) return;

    emit('send', {
        body: body.value.trim(),
        attachment: attachment.value,
    });

    body.value = '';
    attachment.value = null;
    attachmentName.value = '';
    if (fileInput.value) fileInput.value.value = '';
}

function handleKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
    }
}

function handleFileSelect(e) {
    const file = e.target.files[0];
    if (file) {
        attachment.value = file;
        attachmentName.value = file.name;
    }
}

function removeAttachment() {
    attachment.value = null;
    attachmentName.value = '';
    if (fileInput.value) fileInput.value.value = '';
}
</script>

<template>
    <div class="border-t border-gray-200 bg-white p-4">
        <!-- Attachment preview -->
        <div v-if="attachmentName" class="flex items-center gap-2 mb-3 px-3 py-2 bg-gray-50 rounded-lg">
            <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
            <span class="text-xs text-gray-600 truncate flex-1">{{ attachmentName }}</span>
            <button @click="removeAttachment" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Input area -->
        <div class="flex items-end gap-2">
            <!-- File attach button -->
            <button
                @click="fileInput?.click()"
                class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all"
                :disabled="disabled"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
            </button>
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                @change="handleFileSelect"
                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"
            />

            <!-- Text input -->
            <textarea
                v-model="body"
                @keydown="handleKeydown"
                :disabled="disabled"
                placeholder="Type a message..."
                rows="1"
                class="doctorato-input flex-1 resize-none border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-gray-300 focus:ring-1 focus:ring-gray-200 transition-all max-h-32 overflow-y-auto"
                style="min-height: 42px;"
            ></textarea>

            <!-- Send button -->
            <button
                @click="handleSend"
                :disabled="disabled || (!body.trim() && !attachment)"
                class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-white transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed"
                :style="{ backgroundColor: accentColor }"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </div>
    </div>
</template>
