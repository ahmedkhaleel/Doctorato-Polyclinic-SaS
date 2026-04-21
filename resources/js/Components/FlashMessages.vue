<script setup>
/**
 * Global flash-message banner for success/error flashes forwarded by
 * HandleInertiaRequests. Drop this once at the top of any layout that
 * doesn't already render its own flash UI (PatientLayout, AdminLayout, …)
 * so users always see backend "with('success'|'error')" messages.
 */
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const successMsg = computed(() => page.props.flash?.success || '');
const errorMsg   = computed(() => page.props.flash?.error || '');

const visibleSuccess = ref('');
const visibleError   = ref('');

// Auto-dismiss success after 5s; error stays until user closes it.
watch(successMsg, (v) => {
    if (!v) return;
    visibleSuccess.value = v;
    setTimeout(() => { visibleSuccess.value = ''; }, 5000);
}, { immediate: true });

watch(errorMsg, (v) => {
    if (!v) return;
    visibleError.value = v;
}, { immediate: true });
</script>

<template>
    <div v-if="visibleSuccess || visibleError"
         class="fixed top-4 inset-x-0 z-[9999] flex flex-col items-center gap-2 px-4 pointer-events-none">
        <!-- Success -->
        <div v-if="visibleSuccess"
             class="pointer-events-auto max-w-xl w-full rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3
                    flex items-start gap-3 shadow-lg shadow-emerald-500/10 animate-slideDown">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="flex-1 text-sm text-emerald-800">{{ visibleSuccess }}</div>
            <button @click="visibleSuccess = ''"
                    class="text-emerald-400 hover:text-emerald-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Error -->
        <div v-if="visibleError"
             class="pointer-events-auto max-w-xl w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-3
                    flex items-start gap-3 shadow-lg shadow-red-500/10 animate-slideDown">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1 text-sm text-red-700">{{ visibleError }}</div>
            <button @click="visibleError = ''"
                    class="text-red-400 hover:text-red-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.animate-slideDown { animation: slideDown 0.3s cubic-bezier(0.25, 0.8, 0.3, 1); }
</style>
