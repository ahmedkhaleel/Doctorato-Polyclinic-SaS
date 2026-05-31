<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    bellUrl: { type: String, required: true },     // GET → { unread, items[] }
    indexUrl: { type: String, required: true },     // full feed page
    readUrlBase: { type: String, required: true },  // POST {base}/{id}/read
    readAllUrl: { type: String, required: true },   // POST mark all read
    rtl: { type: Boolean, default: true },
    pollMs: { type: Number, default: 60000 },
});

const unread = ref(0);
const items = ref([]);
const open = ref(false);
let timer = null;

const t = (ar, en) => (props.rtl ? ar : en);

async function refresh() {
    try {
        const res = await fetch(props.bellUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!res.ok) return;
        const data = await res.json();
        unread.value = data.unread ?? 0;
        items.value = data.items ?? [];
    } catch (e) { /* offline / transient — keep last state */ }
}

function openItem(item) {
    open.value = false;
    router.post(props.readUrlBase.replace('{id}', item.id), {}, {
        preserveScroll: true, preserveState: true,
        onSuccess: () => { refresh(); if (item.url) router.visit(item.url); },
    });
}

function markAll() {
    router.post(props.readAllUrl, {}, { preserveScroll: true, preserveState: true, onSuccess: refresh });
}

const icons = {
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    receipt: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
    star: 'M11.05 3.69l1.18 3.64h3.83c.97 0 1.37 1.24.59 1.81l-3.1 2.25 1.18 3.64c.3.92-.75 1.69-1.54 1.12L10 17.27l-3.1 2.25c-.79.57-1.84-.2-1.54-1.12l1.18-3.64-3.1-2.25c-.78-.57-.38-1.81.59-1.81h3.83l1.18-3.64c.3-.92 1.6-.92 1.9 0z',
    clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    heart: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
};
const timeAgo = (iso) => {
    if (!iso) return '';
    const s = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (s < 60) return t('الآن', 'now');
    if (s < 3600) return t(`${Math.floor(s / 60)} د`, `${Math.floor(s / 60)}m`);
    if (s < 86400) return t(`${Math.floor(s / 3600)} س`, `${Math.floor(s / 3600)}h`);
    return t(`${Math.floor(s / 86400)} ي`, `${Math.floor(s / 86400)}d`);
};

const hasUnread = computed(() => unread.value > 0);

onMounted(() => { refresh(); timer = setInterval(refresh, props.pollMs); });
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div class="relative">
        <button @click="open = !open" class="relative inline-flex items-center justify-center w-10 h-10 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors" :aria-label="t('الإشعارات', 'Notifications')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons.bell" /></svg>
            <span v-if="hasUnread" class="absolute -top-0.5 -end-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ unread > 99 ? '99+' : unread }}</span>
        </button>

        <!-- Dropdown -->
        <transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0">
            <div v-if="open" class="absolute z-50 mt-2 w-80 max-w-[92vw] rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden" :class="rtl ? 'start-0' : 'end-0'" :style="rtl ? 'inset-inline-start:0' : 'inset-inline-end:0'">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <span class="font-bold text-gray-900 text-sm">{{ t('الإشعارات', 'Notifications') }}</span>
                    <button v-if="hasUnread" @click="markAll" class="text-xs font-semibold text-[var(--brand-primary,#1B365D)] hover:underline">{{ t('تحديد الكل كمقروء', 'Mark all read') }}</button>
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <div v-if="!items.length" class="px-4 py-8 text-center text-gray-400 text-sm">{{ t('لا توجد إشعارات', 'No notifications') }}</div>
                    <button v-for="item in items" :key="item.id" @click="openItem(item)"
                            class="w-full text-start flex gap-3 px-4 py-3 hover:bg-gray-50 transition border-b border-gray-50"
                            :class="item.is_read ? 'opacity-70' : 'bg-[var(--brand-primary,#1B365D)]/[0.03]'">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shrink-0" style="background:var(--brand-primary,#1B365D)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons[item.icon] || icons.bell" /></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800 line-clamp-2 whitespace-pre-line">{{ item.title || item.body }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ timeAgo(item.created_at) }}</p>
                        </div>
                        <span v-if="!item.is_read" class="w-2 h-2 rounded-full bg-red-500 shrink-0 mt-1.5"></span>
                    </button>
                </div>
                <a :href="indexUrl" class="block text-center px-4 py-3 text-sm font-semibold text-[var(--brand-primary,#1B365D)] border-t border-gray-100 hover:bg-gray-50">{{ t('عرض كل الإشعارات', 'View all notifications') }}</a>
            </div>
        </transition>

        <!-- click-away -->
        <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>
    </div>
</template>
