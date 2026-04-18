<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    label: String,
    settingKey: String,
    isSet: { type: Boolean, default: false },
    masked: { type: String, default: '' },
    description: String,
    type: { type: String, default: 'password' },
    placeholder: String,
});

const editing = ref(false);
const value = ref('');
const showPlain = ref(false);
const saving = ref(false);

function startEdit() {
    value.value = '';
    showPlain.value = false;
    editing.value = true;
}

function cancelEdit() {
    editing.value = false;
    value.value = '';
}

function save() {
    if (!value.value) {
        cancelEdit();
        return;
    }
    saving.value = true;
    router.post('/admin/settings/telemedicine', {
        key: props.settingKey,
        value: value.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
            value.value = '';
        },
        onFinish: () => (saving.value = false),
    });
}
</script>

<template>
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">{{ label }}</label>

        <!-- Display mode -->
        <div v-if="!editing" class="flex items-center gap-2">
            <div class="flex-1 px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm font-mono"
                 :class="isSet ? 'text-gray-700' : 'text-gray-400 italic'">
                {{ isSet ? masked : (placeholder || 'Not configured') }}
            </div>
            <button type="button" @click="startEdit"
                class="px-3 py-2 rounded-lg bg-[#1B365D] text-white text-xs font-semibold hover:bg-[#1B365D]/90 transition">
                {{ isSet ? 'Change' : 'Set' }}
            </button>
        </div>

        <!-- Edit mode -->
        <div v-else class="flex items-center gap-2">
            <div class="flex-1 relative">
                <input
                    v-model="value"
                    :type="showPlain ? 'text' : type"
                    :placeholder="placeholder || 'Enter new value'"
                    class="doctorato-input w-full px-3 py-2 pe-10 rounded-lg border border-[#C4A265] bg-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30"
                    @keydown.enter="save"
                    @keydown.escape="cancelEdit"
                />
                <button type="button" @click="showPlain = !showPlain"
                    class="absolute end-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg v-if="!showPlain" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <button type="button" @click="save" :disabled="saving || !value"
                class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition disabled:opacity-50">
                {{ saving ? 'Saving...' : 'Save' }}
            </button>
            <button type="button" @click="cancelEdit"
                class="px-3 py-2 rounded-lg bg-gray-200 text-gray-700 text-xs font-semibold hover:bg-gray-300 transition">
                Cancel
            </button>
        </div>

        <p v-if="description" class="mt-1 text-xs text-gray-500">{{ description }}</p>
    </div>
</template>
