<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    endpoint: String,
    label: { type: String, default: 'Test Connection' },
});

const loading = ref(false);
const result = ref(null);

async function runTest() {
    loading.value = true;
    result.value = null;
    try {
        const { data } = await axios.post(props.endpoint);
        result.value = data;
    } catch (e) {
        result.value = { success: false, message: e.response?.data?.message || e.message };
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <button type="button" @click="runTest" :disabled="loading"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition disabled:opacity-50">
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            {{ loading ? 'Testing...' : label }}
        </button>

        <div v-if="result" class="mt-3 flex items-start gap-2 p-3 rounded-lg"
             :class="result.success ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'">
            <svg v-if="result.success" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="text-sm">{{ result.message }}</span>
        </div>
    </div>
</template>
