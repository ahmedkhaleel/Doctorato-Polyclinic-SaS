<script setup>
import { defineAsyncComponent } from 'vue';

// Lazy-load VideoRoom (includes Agora SDK). Bundled weight only loads when
// a doctor actually enters a consultation room, not on every app navigation.
const VideoRoom = defineAsyncComponent({
    loader: () => import('@/Components/Telemedicine/VideoRoom.vue'),
    delay: 0,
    timeout: 15000,
});

defineOptions({ layout: null });

const props = defineProps({
    consultationId: { type: [Number, String], required: true },
    role: { type: String, required: true },
});
</script>

<template>
    <Suspense>
        <VideoRoom :consultation-id="consultationId" :role="role" />
        <template #fallback>
            <div class="min-h-screen flex items-center justify-center bg-slate-900 text-white">
                <div class="text-center">
                    <div class="w-12 h-12 border-4 border-[#C4A265] border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-sm text-white/70">جارٍ تحميل غرفة الاستشارة…</p>
                </div>
            </div>
        </template>
    </Suspense>
</template>
