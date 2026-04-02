<script setup>
import { ref, computed, onMounted } from 'vue';
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

const headerLoaded = ref(false);
const cardsLoaded = ref(false);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
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
    new_booking: { icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'text-blue-500', bg: 'bg-blue-50' },
    new_visit: { icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', color: 'text-emerald-500', bg: 'bg-emerald-50' },
    booking_reminder: { icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-amber-500', bg: 'bg-amber-50' },
    dental_lab_overdue: { icon: 'M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z', color: 'text-red-500', bg: 'bg-red-50' },
    dental_plan_due: { icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', color: 'text-purple-500', bg: 'bg-purple-50' },
    dental_followup_reminder: { icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'text-pink-500', bg: 'bg-pink-50' },
};
const defaultIcon = { icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5A4.97 4.97 0 008 11v3.159c0 .538-.214 1.055-.595 1.436L6 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9', color: 'text-gray-500', bg: 'bg-gray-50' };

const notificationsList = computed(() => props.notifications?.data || []);
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-amber-500/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-[#C4A265]/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 relative">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5A4.97 4.97 0 008 11v3.159c0 .538-.214 1.055-.595 1.436L6 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ isRtl ? 'سجل الإشعارات' : 'Notifications' }}</h1>
                            <p class="text-sm text-gray-400 mt-0.5">
                                <span v-if="unreadCount > 0" class="text-amber-400 font-semibold">{{ unreadCount }}</span>
                                {{ isRtl ? 'إشعارات غير مقروءة' : 'unread notifications' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Filter tabs -->
                        <div class="hidden sm:flex bg-white/5 backdrop-blur-sm rounded-xl p-1 border border-white/10">
                            <button @click="applyFilter('all')" class="px-4 py-2 text-xs font-bold rounded-lg transition-all" :class="activeFilter === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-400 hover:text-white'">
                                {{ isRtl ? 'الكل' : 'All' }}
                            </button>
                            <button @click="applyFilter('unread')" class="px-4 py-2 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5" :class="activeFilter === 'unread' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-400 hover:text-white'">
                                {{ isRtl ? 'غير مقروء' : 'Unread' }}
                                <span v-if="unreadCount > 0" class="w-5 h-5 text-[10px] font-bold rounded-full flex items-center justify-center" :class="activeFilter === 'unread' ? 'bg-amber-100 text-amber-700' : 'bg-white/10 text-amber-400'">{{ unreadCount }}</span>
                            </button>
                        </div>

                        <!-- Mark all read -->
                        <button v-if="unreadCount > 0" @click="markAllRead" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-xs font-semibold rounded-xl border border-white/10 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            {{ isRtl ? 'قراءة الكل' : 'Mark all read' }}
                        </button>
                    </div>
                </div>

                <!-- Mobile filter tabs -->
                <div class="flex sm:hidden gap-2 mt-4">
                    <button @click="applyFilter('all')" class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex-1" :class="activeFilter === 'all' ? 'bg-white/20 text-white' : 'bg-white/5 text-gray-400'">
                        {{ isRtl ? 'الكل' : 'All' }}
                    </button>
                    <button @click="applyFilter('unread')" class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex-1 flex items-center justify-center gap-1.5" :class="activeFilter === 'unread' ? 'bg-white/20 text-white' : 'bg-white/5 text-gray-400'">
                        {{ isRtl ? 'غير مقروء' : 'Unread' }}
                        <span v-if="unreadCount > 0" class="w-4 h-4 text-[9px] font-bold rounded-full bg-amber-500 text-white flex items-center justify-center">{{ unreadCount }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Notification Cards -->
        <div
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div v-if="notificationsList.length > 0" class="space-y-2">
                <component
                    :is="n.url ? 'a' : 'div'"
                    v-for="n in notificationsList"
                    :key="n.id"
                    :href="n.url || undefined"
                    @click="!n.read && markRead(n.id)"
                    class="flex items-start gap-4 p-4 rounded-xl border transition-all duration-300 cursor-pointer group"
                    :class="n.read ? 'bg-white border-gray-100 hover:shadow-sm' : 'bg-amber-50/30 border-amber-100 hover:bg-amber-50/50 hover:shadow-md'"
                >
                    <!-- Icon -->
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" :class="(typeIcons[n.type] || defaultIcon).bg">
                        <svg class="w-5 h-5" :class="(typeIcons[n.type] || defaultIcon).color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="(typeIcons[n.type] || defaultIcon).icon" /></svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold truncate" :class="n.read ? 'text-gray-600' : 'text-gray-900'">{{ n.title }}</span>
                            <span v-if="!n.read" class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0 animate-pulse"></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.subtitle }}</p>
                    </div>

                    <!-- Time -->
                    <span class="text-[10px] text-gray-400 whitespace-nowrap flex-shrink-0 pt-0.5">{{ timeAgo(n.time) }}</span>
                </component>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5A4.97 4.97 0 008 11v3.159c0 .538-.214 1.055-.595 1.436L6 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد إشعارات' : 'No notifications' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'ستظهر الإشعارات الجديدة هنا' : 'New notifications will appear here' }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.links?.length > 3" class="flex justify-center gap-1 mt-4">
                <template v-for="link in notifications.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-amber-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'" v-html="link.label" preserve-scroll />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
