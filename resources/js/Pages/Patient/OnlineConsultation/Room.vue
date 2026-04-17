<script setup>
import VideoRoom from '@/Components/Telemedicine/VideoRoom.vue';

defineOptions({ layout: null });

const props = defineProps({
    consultationId: { type: [Number, String], required: true },
    role: { type: String, required: true },
});
</script>

<template>
    <VideoRoom :consultation-id="consultationId" :role="role" />
</template>
