<script setup>
import { computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    notifications: Object,
    unreadCount: Number,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const lp = (path) => `/${locale.value}/patient${path}`;

const icons = {
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    receipt: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
    star: 'M11.05 3.69l1.18 3.64h3.83c.97 0 1.37 1.24.59 1.81l-3.1 2.25 1.18 3.64c.3.92-.75 1.69-1.54 1.12L10 17.27l-3.1 2.25c-.79.57-1.84-.2-1.54-1.12l1.18-3.64-3.1-2.25c-.78-.57-.38-1.81.59-1.81h3.83l1.18-3.64c.3-.92 1.6-.92 1.9 0z',
    clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2',
    heart: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
};

function markRead(item) {
    if (item.is_read) { if (item.url) router.visit(item.url); return; }
    router.post(lp(`/notifications/${item.id}/read`), {}, {
        preserveScroll: true,
        onSuccess: () => { if (item.url) router.visit(item.url); },
    });
}
function markAll() {
    router.post(lp('/notifications/read-all'), {}, { preserveScroll: true });
}
const fmt = (iso) => iso ? new Date(iso).toLocaleString(isRtl.value ? 'ar-EG' : 'en-GB') : '';
</script>

<template>
    <div class="max-w-3xl mx-auto p-4 md:p-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center justify-between mb-5">
            <h1 class="text-xl font-bold text-gray-900">{{ t('الإشعارات', 'Notifications') }}</h1>
            <button v-if="unreadCount > 0" @click="markAll" class="text-sm font-semibold text-[var(--brand-primary,#1B365D)] hover:underline">{{ t('تحديد الكل كمقروء', 'Mark all read') }}</button>
        </div>

        <div v-if="!notifications.data.length" class="text-center text-gray-400 py-16 bg-white rounded-2xl border border-gray-100">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.bell" /></svg>
            {{ t('لا توجد إشعارات بعد', 'No notifications yet') }}
        </div>

        <div v-else class="space-y-2">
            <button v-for="item in notifications.data" :key="item.id" @click="markRead(item)"
                    class="w-full text-start flex gap-3 p-4 rounded-2xl border transition hover:shadow-sm"
                    :class="item.is_read ? 'bg-white border-gray-100' : 'bg-[var(--brand-primary,#1B365D)]/[0.04] border-[var(--brand-primary,#1B365D)]/20'">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0" style="background:var(--brand-primary,#1B365D)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons[item.icon] || icons.bell" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p v-if="item.title" class="font-semibold text-gray-900 text-sm">{{ item.title }}</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ item.body }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ fmt(item.created_at) }}</p>
                </div>
                <span v-if="!item.is_read" class="w-2.5 h-2.5 rounded-full bg-red-500 shrink-0 mt-1.5"></span>
            </button>
        </div>

        <!-- Pagination -->
        <div v-if="notifications.links && notifications.links.length > 3" class="flex flex-wrap gap-1 justify-center mt-6">
            <Link v-for="link in notifications.links" :key="link.label" :href="link.url || ''" v-html="link.label"
                  class="px-3 py-1.5 rounded-lg text-sm"
                  :class="[link.active ? 'text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
                  :style="link.active ? 'background:var(--brand-primary,#1B365D)' : ''" />
        </div>
    </div>
</template>
