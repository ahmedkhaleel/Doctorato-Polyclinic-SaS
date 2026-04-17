<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const isOpen = ref(false);
const items = ref([]);
const loading = ref(false);
const bellRef = ref(null);
const bellAnimating = ref(false);
const liveCount = ref(0);

const inertiaCount = computed(() => {
    const n = page.props.secretary_notifications;
    return n ? (n.unread_bookings || 0) + (n.unread_messages || 0) + (n.unread_dental || 0) : 0;
});

const unreadCount = computed(() => liveCount.value || inertiaCount.value);

function handleNotificationUpdate(event) {
    const { unread_count, items: newItems } = event.detail;
    const oldCount = liveCount.value;
    liveCount.value = unread_count;

    if (unread_count > oldCount && oldCount > 0) {
        shakeBell();
    }

    if (isOpen.value && newItems?.length) {
        items.value = newItems;
    }
}

function shakeBell() {
    bellAnimating.value = true;
    setTimeout(() => { bellAnimating.value = false; }, 1200);
}

async function fetchNotifications() {
    loading.value = true;
    try {
        const res = await fetch('/secretary/notifications', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) {
            const data = await res.json();
            items.value = data.items || [];
            liveCount.value = data.unread_count ?? 0;
        }
    } catch (e) {} finally { loading.value = false; }
}

function toggle() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) fetchNotifications();
}

function goTo(item) {
    isOpen.value = false;
    router.visit(item.url);
}

async function markAllRead() {
    try {
        const res = await fetch('/secretary/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });
        if (res.ok) {
            items.value = [];
            liveCount.value = 0;
            router.reload({ only: ['secretary_notifications'] });
        }
    } catch (e) {}
}

function timeAgo(isoString) {
    const now = new Date();
    const time = new Date(isoString);
    const diffMs = now - time;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHr = Math.floor(diffMs / 3600000);
    const diffDay = Math.floor(diffMs / 86400000);

    if (diffMin < 1) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    if (diffHr < 24) return `${diffHr}h ago`;
    if (diffDay < 7) return `${diffDay}d ago`;
    return time.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function getTypeBadge(type) {
    const badges = {
        'new_booking': { label: 'New Booking', bg: 'bg-slate-50', text: 'text-[#1B365D]' },
        'new_message': { label: 'New Message', bg: 'bg-slate-50', text: 'text-[#1B365D]' },
        'dental_lab_overdue': { label: 'Lab Overdue', bg: 'bg-red-50', text: 'text-red-600' },
        'dental_appointment_reminder': { label: 'Dental Appt', bg: 'bg-slate-50', text: 'text-[#1B365D]' },
        'dental_followup_reminder': { label: 'Follow-up', bg: 'bg-amber-50', text: 'text-amber-600' },
        'dental_plan_due': { label: 'Plan Due', bg: 'bg-amber-50', text: 'text-[#C4A265]' },
    };
    return badges[type] || { label: 'Notification', bg: 'bg-gray-50', text: 'text-gray-600' };
}

function handleClickOutside(e) {
    if (bellRef.value && !bellRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('secretary-notification-update', handleNotificationUpdate);
    liveCount.value = inertiaCount.value;
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('secretary-notification-update', handleNotificationUpdate);
});
</script>

<template>
    <div ref="bellRef" class="relative">
        <button
            @click="toggle"
            class="relative inline-flex items-center justify-center w-10 h-10 rounded-xl text-white bg-teal-600 hover:bg-teal-700 shadow-sm shadow-teal-600/20 transition-all duration-200"
            :class="{ 'ring-2 ring-teal-400/30': isOpen }"
        >
            <div :class="bellAnimating ? 'bell-shake' : ''">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>

            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="scale-0 opacity-0" enter-to-class="scale-100 opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="scale-100 opacity-100" leave-to-class="scale-0 opacity-0">
                <span v-if="unreadCount > 0" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full ring-2 ring-teal-600" :class="{ 'badge-pulse': bellAnimating }">
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Transition>

            <span v-if="bellAnimating && unreadCount > 0" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full bg-red-400 animate-ping opacity-75"></span>
        </button>

        <!-- Dropdown -->
        <Transition enter-active-class="transition-all duration-250 ease-out" enter-from-class="opacity-0 translate-y-3 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 translate-y-3 scale-95">
            <div v-if="isOpen" class="absolute right-0 top-full mt-2 w-[400px] max-h-[520px] bg-white rounded-2xl shadow-2xl shadow-black/15 border border-gray-200/80 overflow-hidden z-50">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">Notifications</h3>
                            <p v-if="unreadCount > 0" class="text-[10px] text-teal-600 font-semibold">{{ unreadCount }} unread</p>
                        </div>
                    </div>
                    <button v-if="unreadCount > 0" @click="markAllRead" class="text-[11px] font-semibold text-teal-600 hover:text-teal-700 bg-teal-50 hover:bg-teal-100 px-3 py-1.5 rounded-lg transition-all duration-200">
                        Mark all read
                    </button>
                </div>

                <div class="overflow-y-auto max-h-[430px] divide-y divide-gray-50">
                    <div v-if="loading && items.length === 0" class="flex items-center justify-center py-12">
                        <svg class="animate-spin w-7 h-7 text-teal-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>

                    <button
                        v-for="item in items"
                        :key="item.id"
                        @click="goTo(item)"
                        class="w-full flex items-start gap-3.5 px-5 py-4 hover:bg-teal-50/50 transition-all duration-200 text-left group"
                    >
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center mt-0.5" :class="{
                            'bg-slate-50 text-[#1B365D]': item.type === 'new_booking',
                            'bg-slate-50 text-[#1B365D]': item.type === 'new_message',
                            'bg-red-50 text-red-500': item.type === 'dental_lab_overdue',
                            'bg-slate-50 text-[#1B365D]': item.type === 'dental_appointment_reminder',
                            'bg-amber-50 text-amber-500': item.type === 'dental_followup_reminder',
                            'bg-amber-50 text-[#C4A265]': item.type === 'dental_plan_due',
                            'bg-gray-50 text-gray-400': !['new_booking','new_message','dental_lab_overdue','dental_appointment_reminder','dental_followup_reminder','dental_plan_due'].includes(item.type),
                        }">
                            <svg v-if="item.type === 'new_booking'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <svg v-else-if="item.type === 'new_message'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            <!-- Dental Lab Overdue (tooth) -->
                            <svg v-else-if="item.type === 'dental_lab_overdue'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2C9.5 2 7 4 7 7c0 2-.5 4-1 6s-1 5 0 7c.5 1 1.5 2 3 2s2-1.5 3-4c1 2.5 1.5 4 3 4s2.5-1 3-2c1-2 .5-5 0-7s-1-4-1-6c0-3-2.5-5-5-5z" /></svg>
                            <!-- Dental Appointment -->
                            <svg v-else-if="item.type === 'dental_appointment_reminder'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c-.8 0-1.5.6-1.5 1.5 0 .5-.2 1-.4 1.5s-.3 1.2 0 1.7c.15.3.45.6.9.6s.6-.4.9-1c.3.6.45 1 .9 1s.75-.3.9-.6c.3-.5.2-1.2 0-1.7s-.4-1-.4-1.5c0-.9-.7-1.5-1.3-1.5z" /></svg>
                            <!-- Follow-up Reminder (clock + check) -->
                            <svg v-else-if="item.type === 'dental_followup_reminder'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <!-- Plan Due (clipboard + plus) -->
                            <svg v-else-if="item.type === 'dental_plan_due'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v4m-2-2h4" /></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-[13px] font-semibold text-gray-800 group-hover:text-teal-700 transition-colors truncate">{{ item.title }}</p>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap flex-shrink-0 font-medium">{{ timeAgo(item.time) }}</span>
                            </div>
                            <p class="text-[12px] text-gray-500 truncate mt-0.5">{{ item.subtitle }}</p>
                            <span class="inline-block mt-2 text-[10px] font-semibold px-2.5 py-0.5 rounded-full" :class="[getTypeBadge(item.type).bg, getTypeBadge(item.type).text]">
                                {{ getTypeBadge(item.type).label }}
                            </span>
                        </div>

                        <div class="flex-shrink-0 mt-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-40"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-teal-500"></span>
                            </span>
                        </div>
                    </button>

                    <div v-if="!loading && items.length === 0" class="flex flex-col items-center justify-center py-14 px-4">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mb-4 shadow-inner">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-500">All caught up!</p>
                        <p class="text-xs text-gray-400 mt-1">No new notifications</p>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
@keyframes bellShake {
    0% { transform: rotate(0deg); } 5% { transform: rotate(15deg); } 10% { transform: rotate(-13deg); }
    15% { transform: rotate(12deg); } 20% { transform: rotate(-10deg); } 25% { transform: rotate(8deg); }
    30% { transform: rotate(-6deg); } 35% { transform: rotate(4deg); } 40% { transform: rotate(-3deg); }
    45% { transform: rotate(2deg); } 50% { transform: rotate(0deg); } 100% { transform: rotate(0deg); }
}
.bell-shake { animation: bellShake 1.2s ease-in-out; transform-origin: top center; }
@keyframes badgePulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.3); } }
.badge-pulse { animation: badgePulse 0.6s ease-in-out 3; }
</style>
