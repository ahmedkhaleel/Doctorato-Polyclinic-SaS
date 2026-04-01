<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const isOpen = ref(false);
const items = ref([]);
const loading = ref(false);
const activeFilter = ref('all');
const bellRef = ref(null);

// Inject bell shake from AdminToastNotification
const bellShaking = inject('bellShaking', ref(false));

// Counts from Inertia shared data
const unreadBookings = computed(() => page.props.notifications?.unread_bookings || 0);
const unreadMessages = computed(() => page.props.notifications?.unread_messages || 0);
const unreadSystem = computed(() => page.props.notifications?.unread_system || 0);
const totalUnread = computed(() => unreadBookings.value + unreadMessages.value + unreadSystem.value);

// Filtered items
const filteredItems = computed(() => {
    if (activeFilter.value === 'all') return items.value;
    if (activeFilter.value === 'booking') return items.value.filter(i => i.type === 'booking');
    if (activeFilter.value === 'message') return items.value.filter(i => i.type === 'message');
    if (activeFilter.value === 'leads') return items.value.filter(i => ['new_website_lead', 'lead_assigned', 'follow_up_reminder', 'follow_up_overdue', 'sequence_step'].includes(i.type));
    if (activeFilter.value === 'reports') return items.value.filter(i => i.type === 'daily_lead_report');
    if (activeFilter.value === 'dental') return items.value.filter(i => ['dental_lab_overdue', 'dental_appointment_reminder', 'dental_plan_due', 'dental_followup_reminder'].includes(i.type));
    return items.value;
});

// Fetch notifications from API
async function fetchNotifications() {
    loading.value = true;
    try {
        const res = await fetch('/admin/notifications', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.ok) {
            const data = await res.json();
            items.value = data.items || [];
        }
    } catch (e) {
    } finally {
        loading.value = false;
    }
}

// Toggle dropdown
function toggle() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications();
    }
}

// Navigate to notification item
function goTo(item) {
    isOpen.value = false;

    // Mark as read
    const idParts = item.id.split('_');
    const type = idParts[0]; // 'booking', 'message', or 'notif'
    const realId = idParts.slice(1).join('_');

    fetch(`/admin/notifications/${type}/${realId}/read`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
    });

    router.visit(item.url);
}

// Mark all as read
async function markAllRead() {
    try {
        const res = await fetch('/admin/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });
        if (res.ok) {
            items.value = [];
            router.reload({ only: ['notifications'] });
        }
    } catch (e) {}
}

// Relative time formatting
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

function getItemIcon(type) {
    switch (type) {
        case 'booking': return { bg: 'bg-blue-50 text-blue-500 group-hover:bg-blue-100', icon: 'calendar' };
        case 'message': return { bg: 'bg-amber-50 text-amber-500 group-hover:bg-amber-100', icon: 'envelope' };
        case 'new_website_lead': return { bg: 'bg-emerald-50 text-emerald-500 group-hover:bg-emerald-100', icon: 'lead' };
        case 'lead_assigned': return { bg: 'bg-indigo-50 text-indigo-500 group-hover:bg-indigo-100', icon: 'user' };
        case 'follow_up_reminder': return { bg: 'bg-orange-50 text-orange-500 group-hover:bg-orange-100', icon: 'clock' };
        case 'follow_up_overdue': return { bg: 'bg-red-50 text-red-500 group-hover:bg-red-100', icon: 'alert' };
        case 'sequence_step': return { bg: 'bg-purple-50 text-purple-500 group-hover:bg-purple-100', icon: 'automation' };
        case 'daily_lead_report': return { bg: 'bg-[#C4A265]/10 text-[#C4A265] group-hover:bg-[#C4A265]/20', icon: 'chart' };
        case 'dental_lab_overdue': return { bg: 'bg-red-50 text-red-500 group-hover:bg-red-100', icon: 'dental_lab' };
        case 'dental_appointment_reminder': return { bg: 'bg-cyan-50 text-cyan-500 group-hover:bg-cyan-100', icon: 'dental_appt' };
        case 'dental_plan_due': return { bg: 'bg-orange-50 text-orange-500 group-hover:bg-orange-100', icon: 'dental_plan' };
        case 'dental_followup_reminder': return { bg: 'bg-amber-50 text-amber-500 group-hover:bg-amber-100', icon: 'dental_followup' };
        default: return { bg: 'bg-gray-50 text-gray-500 group-hover:bg-gray-100', icon: 'bell' };
    }
}

function getItemBadge(type) {
    switch (type) {
        case 'booking': return { label: 'Booking', class: 'bg-blue-50 text-blue-600' };
        case 'message': return { label: 'Message', class: 'bg-amber-50 text-amber-600' };
        case 'new_website_lead': return { label: 'New Lead', class: 'bg-emerald-50 text-emerald-600' };
        case 'lead_assigned': return { label: 'Assigned', class: 'bg-indigo-50 text-indigo-600' };
        case 'follow_up_reminder': return { label: 'Follow-up', class: 'bg-orange-50 text-orange-600' };
        case 'follow_up_overdue': return { label: 'Overdue', class: 'bg-red-50 text-red-600' };
        case 'sequence_step': return { label: 'Automation', class: 'bg-purple-50 text-purple-600' };
        case 'daily_lead_report': return { label: 'Report', class: 'bg-[#C4A265]/10 text-[#C4A265]' };
        case 'dental_lab_overdue': return { label: 'Lab Overdue', class: 'bg-red-50 text-red-600' };
        case 'dental_appointment_reminder': return { label: 'Dental Appt', class: 'bg-cyan-50 text-cyan-600' };
        case 'dental_plan_due': return { label: 'Plan Due', class: 'bg-orange-50 text-orange-600' };
        case 'dental_followup_reminder': return { label: 'Follow-up', class: 'bg-amber-50 text-amber-600' };
        default: return { label: 'Notification', class: 'bg-gray-50 text-gray-600' };
    }
}

// Close on click outside
function handleClickOutside(e) {
    if (bellRef.value && !bellRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

// Listen to polling updates from AdminToastNotification
function handlePollingUpdate(e) {
    if (isOpen.value && e.detail?.items) {
        items.value = e.detail.items;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('admin-notification-update', handlePollingUpdate);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('admin-notification-update', handlePollingUpdate);
});
</script>

<template>
    <div ref="bellRef" class="relative">
        <!-- Bell Button -->
        <button
            @click="toggle"
            class="relative inline-flex items-center justify-center w-10 h-10 rounded-xl text-white bg-[#C4A265] hover:bg-[#B08D4C] shadow-sm shadow-[#C4A265]/20 transition-all duration-200"
            :class="[
                isOpen ? 'ring-2 ring-[#C4A265]/30' : '',
                bellShaking ? 'animate-bell-shake' : '',
            ]"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            <!-- Badge -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="scale-0 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-0 opacity-0"
            >
                <span
                    v-if="totalUnread > 0"
                    class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full shadow-sm shadow-red-500/30 ring-2 ring-[#C4A265] animate-pulse-slow"
                >
                    {{ totalUnread > 99 ? '99+' : totalUnread }}
                </span>
            </Transition>
        </button>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 top-full mt-2 w-[400px] max-h-[520px] bg-white rounded-2xl shadow-xl shadow-black/10 border border-gray-200/80 overflow-hidden z-50"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gray-50/50">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-gray-800">Notifications</h3>
                        <span v-if="totalUnread > 0" class="text-[11px] font-semibold text-[#C4A265] bg-[#C4A265]/10 px-2 py-0.5 rounded-full">
                            {{ totalUnread }} new
                        </span>
                    </div>
                    <button
                        v-if="totalUnread > 0"
                        @click="markAllRead"
                        class="text-[11px] font-medium text-[#C4A265] hover:text-[#A68B52] transition-colors"
                    >
                        Mark all read
                    </button>
                </div>

                <!-- Filter Tabs -->
                <div class="flex gap-1 px-4 pt-3 pb-2 overflow-x-auto">
                    <button
                        v-for="tab in [
                            { key: 'all', label: 'All' },
                            { key: 'booking', label: 'Bookings' },
                            { key: 'message', label: 'Messages' },
                            { key: 'leads', label: 'CRM' },
                            { key: 'dental', label: 'Dental' },
                            { key: 'reports', label: 'Reports' },
                        ]"
                        :key="tab.key"
                        @click="activeFilter = tab.key"
                        class="px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-200 whitespace-nowrap"
                        :class="activeFilter === tab.key
                            ? 'bg-[#C4A265]/10 text-[#C4A265]'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Items List -->
                <div class="overflow-y-auto max-h-[380px] divide-y divide-gray-50">
                    <!-- Loading -->
                    <div v-if="loading && items.length === 0" class="flex items-center justify-center py-10">
                        <svg class="animate-spin w-6 h-6 text-[#C4A265]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>

                    <!-- Notification Items -->
                    <button
                        v-for="item in filteredItems"
                        :key="item.id"
                        @click="goTo(item)"
                        class="w-full flex items-start gap-3 px-5 py-3.5 hover:bg-[#FDF8F0] transition-colors duration-150 text-left group"
                    >
                        <!-- Icon -->
                        <div
                            class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center mt-0.5 transition-colors"
                            :class="getItemIcon(item.type).bg"
                        >
                            <!-- Calendar (booking) -->
                            <svg v-if="item.type === 'booking'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <!-- Envelope (message) -->
                            <svg v-else-if="item.type === 'message'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <!-- New Lead -->
                            <svg v-else-if="item.type === 'new_website_lead'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <!-- Overdue Alert -->
                            <svg v-else-if="item.type === 'follow_up_overdue'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <!-- Chart (report) -->
                            <svg v-else-if="item.type === 'daily_lead_report'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Clock (follow-up/assigned) -->
                            <svg v-else-if="['follow_up_reminder', 'lead_assigned'].includes(item.type)" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Automation -->
                            <svg v-else-if="item.type === 'sequence_step'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <!-- Dental Lab Overdue (tooth icon) -->
                            <svg v-else-if="item.type === 'dental_lab_overdue'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2C9.5 2 7 4 7 7c0 2-.5 4-1 6s-1 5 0 7c.5 1 1.5 2 3 2s2-1.5 3-4c1 2.5 1.5 4 3 4s2.5-1 3-2c1-2 .5-5 0-7s-1-4-1-6c0-3-2.5-5-5-5z" />
                            </svg>
                            <!-- Dental Appointment (calendar + tooth) -->
                            <svg v-else-if="item.type === 'dental_appointment_reminder'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c-.8 0-1.5.6-1.5 1.5 0 .5-.2 1-.4 1.5s-.3 1.2 0 1.7c.15.3.45.6.9.6s.6-.4.9-1c.3.6.45 1 .9 1s.75-.3.9-.6c.3-.5.2-1.2 0-1.7s-.4-1-.4-1.5c0-.9-.7-1.5-1.3-1.5z" />
                            </svg>
                            <!-- Dental Plan Due (clipboard) -->
                            <svg v-else-if="item.type === 'dental_plan_due'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v4m-2-2h4" />
                            </svg>
                            <!-- Dental Follow-up Reminder (clock) -->
                            <svg v-else-if="item.type === 'dental_followup_reminder'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Bell General -->
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-[13px] font-semibold text-gray-800 truncate">{{ item.title }}</p>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap flex-shrink-0">{{ timeAgo(item.time) }}</span>
                            </div>
                            <p class="text-[12px] text-gray-500 truncate mt-0.5">{{ item.subtitle }}</p>
                            <span
                                class="inline-block mt-1.5 text-[10px] font-medium px-2 py-0.5 rounded-full"
                                :class="getItemBadge(item.type).class"
                            >
                                {{ getItemBadge(item.type).label }}
                            </span>
                        </div>

                        <!-- Unread dot -->
                        <div class="flex-shrink-0 mt-2">
                            <span
                                class="w-2 h-2 rounded-full block"
                                :class="['follow_up_overdue', 'dental_lab_overdue'].includes(item.type) ? 'bg-red-500' : item.type === 'new_website_lead' ? 'bg-emerald-500' : ['dental_appointment_reminder', 'dental_plan_due'].includes(item.type) ? 'bg-cyan-500' : item.type === 'dental_followup_reminder' ? 'bg-amber-500' : 'bg-[#C4A265]'"
                            ></span>
                        </div>
                    </button>

                    <!-- Empty State -->
                    <div v-if="!loading && filteredItems.length === 0" class="flex flex-col items-center justify-center py-10 px-4">
                        <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">No new notifications</p>
                        <p class="text-xs text-gray-400 mt-1">You're all caught up!</p>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
@keyframes bellShake {
    0% { transform: rotate(0deg); }
    10% { transform: rotate(10deg); }
    20% { transform: rotate(-8deg); }
    30% { transform: rotate(6deg); }
    40% { transform: rotate(-4deg); }
    50% { transform: rotate(2deg); }
    60% { transform: rotate(0deg); }
    100% { transform: rotate(0deg); }
}
.animate-bell-shake {
    animation: bellShake 0.8s ease-in-out;
}
.animate-pulse-slow {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
