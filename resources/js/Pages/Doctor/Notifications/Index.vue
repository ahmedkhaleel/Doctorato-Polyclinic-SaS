<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    notifications: Object,
    filter: { type: String, default: 'all' },
    unreadCount: { type: Number, default: 0 },
});

const activeFilter = ref(props.filter);

function applyFilter(f) {
    activeFilter.value = f;
    router.get('/doctor/notifications/history', { filter: f }, { preserveState: true, replace: true });
}

function markAllRead() {
    router.post('/doctor/notifications/mark-all-read', {}, {
        preserveScroll: true,
        onSuccess: () => router.reload(),
    });
}

function markRead(id) {
    router.post(`/doctor/notifications/${id}/read`, {}, {
        preserveScroll: true,
        onSuccess: () => router.reload(),
    });
}

function timeAgo(isoString) {
    const date = new Date(isoString);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHr = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHr / 24);

    if (diffMin < 1) return isRtl.value ? 'الآن' : 'Just now';
    if (diffMin < 60) return isRtl.value ? `منذ ${diffMin} دقيقة` : `${diffMin}m ago`;
    if (diffHr < 24) return isRtl.value ? `منذ ${diffHr} ساعة` : `${diffHr}h ago`;
    if (diffDay < 7) return isRtl.value ? `منذ ${diffDay} يوم` : `${diffDay}d ago`;
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

const typeIcons = {
    new_booking: { icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'text-blue-500 bg-blue-50' },
    new_visit: { icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', color: 'text-emerald-500 bg-emerald-50' },
    booking_reminder: { icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-amber-500 bg-amber-50' },
    dental_lab_overdue: { icon: 'M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z', color: 'text-red-500 bg-red-50' },
    dental_plan_due: { icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', color: 'text-purple-500 bg-purple-50' },
    dental_followup_reminder: { icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'text-pink-500 bg-pink-50' },
};
const defaultIcon = { icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5A4.97 4.97 0 008 11v3.159c0 .538-.214 1.055-.595 1.436L6 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9', color: 'text-gray-500 bg-gray-50' };
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'سجل الإشعارات' : 'Notification History' }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    <span v-if="unreadCount > 0" class="text-[#C4A265] font-semibold">{{ unreadCount }}</span>
                    {{ isRtl ? 'إشعارات غير مقروءة' : 'unread notifications' }}
                </p>
            </div>
            <button v-if="unreadCount > 0" @click="markAllRead" class="px-4 py-2 text-xs font-bold text-[#C4A265] bg-[#C4A265]/10 rounded-xl hover:bg-[#C4A265]/20 transition">
                {{ isRtl ? 'تعيين الكل كمقروء' : 'Mark all as read' }}
            </button>
        </div>

        <!-- Filter tabs -->
        <div class="flex gap-2 mb-6">
            <button @click="applyFilter('all')" :class="['px-4 py-2 text-xs font-bold rounded-xl transition', activeFilter === 'all' ? 'bg-[#C4A265] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                {{ isRtl ? 'الكل' : 'All' }}
            </button>
            <button @click="applyFilter('unread')" :class="['px-4 py-2 text-xs font-bold rounded-xl transition', activeFilter === 'unread' ? 'bg-[#C4A265] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                {{ isRtl ? 'غير مقروء' : 'Unread' }}
                <span v-if="unreadCount > 0" class="ms-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full" :class="activeFilter === 'unread' ? 'bg-white/20 text-white' : 'bg-[#C4A265]/20 text-[#C4A265]'">{{ unreadCount }}</span>
            </button>
        </div>

        <!-- Notification list -->
        <div v-if="notifications.data?.length > 0" class="space-y-2">
            <component :is="n.url ? 'a' : 'div'" v-for="n in notifications.data" :key="n.id" :href="n.url || undefined" @click="!n.read && markRead(n.id)"
                :class="['flex items-start gap-4 p-4 rounded-2xl border transition group', n.read ? 'bg-white border-gray-100' : 'bg-[#C4A265]/[0.03] border-[#C4A265]/20 hover:bg-[#C4A265]/[0.06]']">
                <!-- Icon -->
                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', (typeIcons[n.type] || defaultIcon).color]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="(typeIcons[n.type] || defaultIcon).icon" /></svg>
                </div>
                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span :class="['text-sm font-semibold truncate', n.read ? 'text-gray-700' : 'text-gray-900']">{{ n.title }}</span>
                        <span v-if="!n.read" class="w-2 h-2 rounded-full bg-[#C4A265] flex-shrink-0"></span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.subtitle }}</p>
                </div>
                <!-- Time -->
                <span class="text-[10px] text-gray-400 whitespace-nowrap flex-shrink-0">{{ timeAgo(n.time) }}</span>
            </component>
        </div>

        <!-- Empty state -->
        <div v-else class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5A4.97 4.97 0 008 11v3.159c0 .538-.214 1.055-.595 1.436L6 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد إشعارات' : 'No notifications' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="notifications.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in notifications.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 text-xs rounded-lg transition', link.active ? 'bg-[#C4A265] text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50']" v-html="link.label" preserve-scroll />
                <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
