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
const statsLoaded = ref(false);
const cardsLoaded = ref(false);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => statsLoaded.value = true, 150);
    setTimeout(() => cardsLoaded.value = true, 250);
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

function handleNotificationClick(n) {
    if (!n.read) {
        router.post(`/doctor/notifications/${n.id}/read`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                const url = n.data?.url || n.url;
                if (url) {
                    router.visit(url);
                } else {
                    router.reload();
                }
            },
        });
    } else {
        const url = n.data?.url || n.url;
        if (url) {
            router.visit(url);
        }
    }
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

function getDateGroup(isoString) {
    const date = new Date(isoString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const weekAgo = new Date(today);
    weekAgo.setDate(weekAgo.getDate() - 7);

    const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());

    if (dateOnly >= today) return 'today';
    if (dateOnly >= yesterday) return 'yesterday';
    if (dateOnly >= weekAgo) return 'this_week';
    return 'older';
}

const groupLabels = computed(() => ({
    today: isRtl.value ? 'اليوم' : 'Today',
    yesterday: isRtl.value ? 'أمس' : 'Yesterday',
    this_week: isRtl.value ? 'هذا الأسبوع' : 'This Week',
    older: isRtl.value ? 'أقدم' : 'Older',
}));

const typeIcons = {
    new_booking: { icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'text-blue-500', bg: 'bg-blue-50', border: 'border-blue-400', category: 'booking' },
    new_visit: { icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', color: 'text-emerald-500', bg: 'bg-emerald-50', border: 'border-emerald-400', category: 'visit' },
    booking_reminder: { icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-amber-500', bg: 'bg-amber-50', border: 'border-amber-400', category: 'reminder' },
    dental_lab_overdue: { icon: 'M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z', color: 'text-red-500', bg: 'bg-red-50', border: 'border-red-400', category: 'alert' },
    dental_plan_due: { icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', color: 'text-purple-500', bg: 'bg-purple-50', border: 'border-purple-400', category: 'reminder' },
    dental_followup_reminder: { icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'text-pink-500', bg: 'bg-pink-50', border: 'border-pink-400', category: 'reminder' },
};
const defaultIcon = { icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5A4.97 4.97 0 008 11v3.159c0 .538-.214 1.055-.595 1.436L6 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9', color: 'text-gray-500', bg: 'bg-gray-50', border: 'border-gray-300', category: 'other' };

const notificationsList = computed(() => props.notifications?.data || []);

const groupedNotifications = computed(() => {
    const groups = { today: [], yesterday: [], this_week: [], older: [] };
    notificationsList.value.forEach(n => {
        const group = getDateGroup(n.time);
        groups[group].push(n);
    });
    return Object.entries(groups).filter(([, items]) => items.length > 0);
});

const totalCount = computed(() => props.notifications?.total || notificationsList.value.length);

const mostCommonType = computed(() => {
    const counts = {};
    notificationsList.value.forEach(n => {
        const cat = (typeIcons[n.type] || defaultIcon).category;
        counts[cat] = (counts[cat] || 0) + 1;
    });
    let max = 0;
    let maxType = '';
    Object.entries(counts).forEach(([type, count]) => {
        if (count > max) { max = count; maxType = type; }
    });
    const labels = {
        booking: isRtl.value ? 'الحجوزات' : 'Bookings',
        visit: isRtl.value ? 'الزيارات' : 'Visits',
        reminder: isRtl.value ? 'التذكيرات' : 'Reminders',
        alert: isRtl.value ? 'التنبيهات' : 'Alerts',
        other: isRtl.value ? 'أخرى' : 'Other',
    };
    return maxType ? labels[maxType] || maxType : (isRtl.value ? 'لا يوجد' : 'None');
});

function getNotificationMeta(n) {
    return typeIcons[n.type] || defaultIcon;
}

function getActionUrl(n) {
    return n.data?.url || n.url || null;
}
</script>

<template>
    <div class="space-y-5">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-amber-500/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-[#C4A265]/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 relative">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5A4.97 4.97 0 008 11v3.159c0 .538-.214 1.055-.595 1.436L6 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            <span v-if="unreadCount > 0" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-gray-900">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
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
                        <!-- Filter pills (Desktop) -->
                        <div class="hidden sm:flex bg-white/5 backdrop-blur-sm rounded-xl p-1 border border-white/10">
                            <button
                                @click="applyFilter('all')"
                                class="relative px-5 py-2 text-xs font-bold rounded-lg transition-all duration-300"
                                :class="activeFilter === 'all' ? 'bg-white text-gray-900 shadow-md shadow-white/10' : 'text-gray-400 hover:text-white hover:bg-white/5'"
                            >
                                {{ isRtl ? 'الكل' : 'All' }}
                                <span class="text-[10px] font-semibold opacity-60 ms-1">{{ totalCount }}</span>
                            </button>
                            <button
                                @click="applyFilter('unread')"
                                class="relative px-5 py-2 text-xs font-bold rounded-lg transition-all duration-300 flex items-center gap-1.5"
                                :class="activeFilter === 'unread' ? 'bg-white text-gray-900 shadow-md shadow-white/10' : 'text-gray-400 hover:text-white hover:bg-white/5'"
                            >
                                {{ isRtl ? 'غير مقروء' : 'Unread' }}
                                <span
                                    v-if="unreadCount > 0"
                                    class="min-w-[20px] h-5 px-1 text-[10px] font-bold rounded-full flex items-center justify-center transition-colors duration-300"
                                    :class="activeFilter === 'unread' ? 'bg-amber-100 text-amber-700' : 'bg-amber-500/20 text-amber-400'"
                                >{{ unreadCount }}</span>
                            </button>
                        </div>

                        <!-- Mark all read -->
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllRead"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-xs font-semibold rounded-xl border border-white/10 transition-all duration-200 hover:scale-[1.02] active:scale-95"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            {{ isRtl ? 'قراءة الكل' : 'Mark all read' }}
                        </button>
                    </div>
                </div>

                <!-- Mobile filter pills -->
                <div class="flex sm:hidden gap-2 mt-4">
                    <button
                        @click="applyFilter('all')"
                        class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all duration-300 flex-1 flex items-center justify-center gap-1.5"
                        :class="activeFilter === 'all' ? 'bg-white/20 text-white ring-1 ring-white/20' : 'bg-white/5 text-gray-400'"
                    >
                        {{ isRtl ? 'الكل' : 'All' }}
                        <span class="text-[10px] opacity-60">{{ totalCount }}</span>
                    </button>
                    <button
                        @click="applyFilter('unread')"
                        class="px-4 py-2.5 text-xs font-bold rounded-xl transition-all duration-300 flex-1 flex items-center justify-center gap-1.5"
                        :class="activeFilter === 'unread' ? 'bg-white/20 text-white ring-1 ring-white/20' : 'bg-white/5 text-gray-400'"
                    >
                        {{ isRtl ? 'غير مقروء' : 'Unread' }}
                        <span v-if="unreadCount > 0" class="w-4 h-4 text-[9px] font-bold rounded-full bg-amber-500 text-white flex items-center justify-center">{{ unreadCount }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Stats Strip -->
        <div
            class="grid grid-cols-3 gap-3 transition-all duration-700"
            :class="statsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="bg-white rounded-xl border border-gray-100 px-4 py-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-bold text-gray-900 leading-tight">{{ totalCount }}</p>
                    <p class="text-[10px] text-gray-400 font-medium truncate">{{ isRtl ? 'إجمالي' : 'Total' }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 px-4 py-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-bold leading-tight" :class="unreadCount > 0 ? 'text-amber-600' : 'text-gray-900'">{{ unreadCount }}</p>
                    <p class="text-[10px] text-gray-400 font-medium truncate">{{ isRtl ? 'غير مقروء' : 'Unread' }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 px-4 py-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-gray-900 leading-tight truncate">{{ mostCommonType }}</p>
                    <p class="text-[10px] text-gray-400 font-medium truncate">{{ isRtl ? 'الأكثر شيوعا' : 'Most common' }}</p>
                </div>
            </div>
        </div>

        <!-- Mobile swipe hint -->
        <p
            class="sm:hidden text-center text-[11px] text-gray-400 -mt-2 transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100' : 'opacity-0'"
        >
            <svg class="w-3.5 h-3.5 inline-block align-middle opacity-50" :class="isRtl ? 'me-1 rotate-180' : 'me-1'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
            {{ isRtl ? 'اسحب للتفاعل مع الإشعار' : 'Tap a notification to view details' }}
        </p>

        <!-- Notification Cards (Grouped) -->
        <div
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div v-if="notificationsList.length > 0" class="space-y-6">
                <div v-for="([groupKey, items], gi) in groupedNotifications" :key="groupKey">
                    <!-- Section header -->
                    <div class="flex items-center gap-3 px-1">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">{{ groupLabels[groupKey] }}</h3>
                        <div class="flex-1 h-px bg-gray-100"></div>
                        <span class="text-[10px] font-semibold text-gray-300 bg-gray-50 px-2 py-0.5 rounded-full">{{ items.length }}</span>
                    </div>

                    <!-- Notification cards -->
                    <div class="space-y-2">
                        <div
                            v-for="(n, ni) in items"
                            :key="n.id"
                            @click="handleNotificationClick(n)"
                            class="notification-card group relative flex items-start gap-4 p-4 rounded-xl border transition-all duration-300 cursor-pointer overflow-hidden"
                            :class="[
                                n.read
                                    ? 'bg-white border-gray-100 hover:shadow-md hover:shadow-gray-100/80 hover:-translate-y-0.5'
                                    : 'bg-amber-50/30 border-amber-100/80 hover:shadow-lg hover:shadow-amber-100/50 hover:-translate-y-0.5',
                            ]"
                            :style="{ animationDelay: `${(gi * 100) + (ni * 60)}ms`, borderLeftWidth: isRtl ? '' : '3px', borderRightWidth: isRtl ? '3px' : '', borderLeftColor: isRtl ? '' : undefined, borderRightColor: isRtl ? undefined : '' }"
                            :dir="isRtl ? 'rtl' : 'ltr'"
                        >
                            <!-- Colored side border -->
                            <div
                                class="absolute top-0 bottom-0 w-[3px]"
                                :class="[
                                    isRtl ? 'right-0' : 'left-0',
                                    getNotificationMeta(n).border,
                                ]"
                            ></div>

                            <!-- Icon -->
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform duration-300 group-hover:scale-110" :class="getNotificationMeta(n).bg">
                                <svg class="w-5 h-5" :class="getNotificationMeta(n).color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getNotificationMeta(n).icon" /></svg>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold truncate" :class="n.read ? 'text-gray-600' : 'text-gray-900'">{{ n.title }}</span>
                                    <!-- Animated unread pulsing dot -->
                                    <span v-if="!n.read" class="relative flex-shrink-0 w-2.5 h-2.5">
                                        <span class="absolute inset-0 rounded-full bg-amber-400 animate-ping opacity-40"></span>
                                        <span class="relative block w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.subtitle }}</p>

                                <!-- Action URL link -->
                                <div v-if="getActionUrl(n)" class="mt-2 flex items-center gap-1 text-[11px] font-medium text-[#C4A265] opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <span>{{ isRtl ? 'عرض التفاصيل' : 'View details' }}</span>
                                    <svg class="w-3 h-3 transition-transform duration-200" :class="isRtl ? 'rotate-180 group-hover:-translate-x-0.5' : 'group-hover:translate-x-0.5'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </div>

                            <!-- Time -->
                            <span class="text-[10px] text-gray-400 whitespace-nowrap flex-shrink-0 pt-0.5">{{ timeAgo(n.time) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-20 bg-white rounded-2xl border border-gray-100">
                <div class="empty-bell-container mx-auto mb-5 w-20 h-20 bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl flex items-center justify-center">
                    <svg class="empty-bell w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-9.33-5A4.97 4.97 0 008 11v3.159c0 .538-.214 1.055-.595 1.436L6 17h5m4 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                </div>
                <p class="text-sm font-semibold text-gray-500">
                    {{ activeFilter === 'unread'
                        ? (isRtl ? 'لا توجد إشعارات غير مقروءة' : 'No unread notifications')
                        : (isRtl ? 'لا توجد إشعارات' : 'No notifications yet')
                    }}
                </p>
                <p class="text-xs text-gray-400 mt-1.5 max-w-xs mx-auto">
                    {{ activeFilter === 'unread'
                        ? (isRtl ? 'لقد قرأت جميع إشعاراتك - أحسنت!' : 'You\'re all caught up - great job!')
                        : (isRtl ? 'ستظهر الإشعارات الجديدة هنا عند وصولها' : 'New notifications will appear here as they arrive')
                    }}
                </p>
                <button
                    v-if="activeFilter === 'unread'"
                    @click="applyFilter('all')"
                    class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-[#C4A265] bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors duration-200"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                    {{ isRtl ? 'عرض جميع الإشعارات' : 'View all notifications' }}
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.links?.length > 3" class="flex justify-center gap-1 mt-6">
                <template v-for="link in notifications.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200"
                        :class="link.active ? 'bg-[#C4A265] text-white shadow-sm shadow-amber-200' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300'"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Slide-in entrance animation for notification cards */
.notification-card {
    animation: slideInUp 0.4s ease-out both;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Empty state bell sway animation */
.empty-bell {
    animation: bellSway 3s ease-in-out infinite;
    transform-origin: top center;
}

@keyframes bellSway {
    0%, 100% {
        transform: rotate(0deg);
    }
    15% {
        transform: rotate(8deg);
    }
    30% {
        transform: rotate(-6deg);
    }
    45% {
        transform: rotate(4deg);
    }
    60% {
        transform: rotate(-2deg);
    }
    75% {
        transform: rotate(1deg);
    }
}

.empty-bell-container {
    animation: gentleFloat 4s ease-in-out infinite;
}

@keyframes gentleFloat {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}
</style>
