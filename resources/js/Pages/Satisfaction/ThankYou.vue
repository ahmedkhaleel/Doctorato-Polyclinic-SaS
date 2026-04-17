<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const isRtl = computed(() => (page.props.locale || 'ar') === 'ar')
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-slate-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="text-center max-w-md">
            <div class="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 animate-bounce">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-3">{{ isRtl ? 'شكراً لك!' : 'Thank You!' }}</h1>
            <p class="text-gray-500 text-lg">{{ isRtl ? 'تقييمك يساعدنا على تقديم خدمة أفضل' : 'Your feedback helps us provide better service' }}</p>
            <p class="text-gray-400 text-sm mt-4">{{ isRtl ? 'يمكنك إغلاق هذه الصفحة الآن' : 'You can close this page now' }}</p>
        </div>
    </div>
</template>
