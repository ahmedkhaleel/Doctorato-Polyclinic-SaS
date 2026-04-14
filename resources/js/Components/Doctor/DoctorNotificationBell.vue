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

// Count from Inertia shared data
const inertiaCount = computed(() => page.props.doctor_notifications?.unread_count || 0);

// Use liveCount (updated by polling event) or Inertia count
const unreadCount = computed(() => liveCount.value || inertiaCount.value);

// ─── Listen for polling events from ToastNotification ─────────────
function handleNotificationUpdate(event) {
    const { unread_count, items: newItems } = event.detail;
    const oldCount = liveCount.value;
    liveCount.value = unread_count;

    // Trigger bell animation when count increases
    if (unread_count > oldCount && oldCount > 0) {
        shakeBell();
    }

    // Update items if dropdown is open
    if (isOpen.value && newItems?.length) {
        items.value = newItems;
    }
}

function shakeBell() {
    bellAnimating.value = true;
    setTimeout(() => { bellAnimating.value = false; }, 1200);
}

// ─── Fetch notifications from API ─────────────────────────────────
async function fetchNotifications() {
    loading.value = true;
    try {
        const res = await fetch('/doctor/notifications', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const data = await res.json();
            items.value = data.items || [];
            liveCount.value = data.unread_count ?? 0;
        }
    } catch (e) {
        // Silently fail
    } finally {
        loading.value = false;
    }
}

// ─── Toggle dropdown ──────────────────────────────────────────────
function toggle() {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications();
    }
}

// ─── Navigate to notification item ────────────────────────────────
function goTo(item) {
    isOpen.value = false;

    // Mark as read via API (fire and forget)
    fetch(`/doctor/notifications/${item.id}/read`, {
        method: 'PATCH',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        credentials: 'same-origin',
    }).then(() => {
        // Decrease live count
        if (liveCount.value > 0) liveCount.value--;
    });

    router.visit(item.url);
}

// ─── Mark all as read ─────────────────────────────────────────────
async function markAllRead() {
    try {
        const res = await fetch('/doctor/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
        });
        if (res.ok) {
            items.value = [];
            liveCount.value = 0;
            router.reload({ only: ['doctor_notifications'] });
        }
    } catch (e) {
        // Silently fail
    }
}

// ─── Relative time formatting ─────────────────────────────────────
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
        'new_booking': { label: 'New Booking', bg: 'bg-blue-50', text: 'text-blue-600', icon: 'calendar' },
        'new_visit': { label: 'New Visit', bg: 'bg-emerald-50', text: 'text-emerald-600', icon: 'visit' },
        'booking_reminder': { label: 'Reminder', bg: 'bg-amber-50', text: 'text-amber-600', icon: 'reminder' },
        'dental_lab_overdue': { label: 'Lab Overdue', bg: 'bg-red-50', text: 'text-red-600', icon: 'dental_lab' },
        'dental_appointment_reminder': { label: 'Dental Appt', bg: 'bg-cyan-50', text: 'text-cyan-600', icon: 'dental_appt' },
        'dental_plan_due': { label: 'Plan Due', bg: 'bg-orange-50', text: 'text-orange-600', icon: 'dental_plan' },
        'dental_followup_reminder': { label: 'Follow-up', bg: 'bg-amber-50', text: 'text-amber-600', icon: 'dental_followup' },
        'pediatric_vaccination_due': { label: 'Vaccine Due', bg: 'bg-green-50', text: 'text-green-600', icon: 'pediatric_vaccine' },
        'pediatric_vaccination_overdue': { label: 'Vaccine Overdue', bg: 'bg-red-50', text: 'text-red-600', icon: 'pediatric_vaccine_overdue' },
        'pediatric_growth_alert': { label: 'Growth Alert', bg: 'bg-rose-50', text: 'text-rose-600', icon: 'pediatric_growth' },
        'pediatric_milestone_alert': { label: 'Milestone Alert', bg: 'bg-purple-50', text: 'text-purple-600', icon: 'pediatric_milestone' },
    };
    return badges[type] || { label: 'Notification', bg: 'bg-gray-50', text: 'text-gray-600', icon: 'general' };
}

// ─── Close on click outside ───────────────────────────────────────
function handleClickOutside(e) {
    if (bellRef.value && !bellRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('doctor-notification-update', handleNotificationUpdate);
    liveCount.value = inertiaCount.value;
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('doctor-notification-update', handleNotificationUpdate);
});
</script>

<template>
    <div ref="bellRef" class="relative">
        <!-- Bell Button with Shake Animation -->
        <button
            @click="toggle"
            class="relative inline-flex items-center justify-center w-10 h-10 rounded-xl text-white bg-[#C4A265] hover:bg-[#B08D4C] shadow-sm shadow-[#C4A265]/20 transition-all duration-200"
            :class="{ 'ring-2 ring-[#C4A265]/30': isOpen }"
        >
            <!-- Bell Icon with ring animation -->
            <div :class="bellAnimating ? 'bell-shake' : ''">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>

            <!-- Animated Badge with pulse -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="scale-0 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-0 opacity-0"
            >
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full ring-2 ring-[#C4A265]"
                    :class="{ 'badge-pulse': bellAnimating }"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Transition>

            <!-- Ping animation when bell is shaking -->
            <span
                v-if="bellAnimating && unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full bg-red-400 animate-ping opacity-75"
            ></span>
        </button>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition-all duration-250 ease-out"
            enter-from-class="opacity-0 translate-y-3 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-3 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 top-full mt-2 w-[400px] max-h-[520px] bg-white rounded-2xl shadow-2xl shadow-black/15 border border-gray-200/80 overflow-hidden z-50"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">Notifications</h3>
                            <p v-if="unreadCount > 0" class="text-[10px] text-[#C4A265] font-semibold">{{ unreadCount }} unread</p>
                        </div>
                    </div>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-[11px] font-semibold text-[#C4A265] hover:text-[#A68B52] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 px-3 py-1.5 rounded-lg transition-all duration-200"
                    >
                        Mark all read
                    </button>
                </div>

                <!-- Items List -->
                <div class="overflow-y-auto max-h-[430px] divide-y divide-gray-50">
                    <!-- Loading -->
                    <div v-if="loading && items.length === 0" class="flex items-center justify-center py-12">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="animate-spin w-7 h-7 text-[#C4A265]" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <p class="text-xs text-gray-400">Loading notifications...</p>
                        </div>
                    </div>

                    <!-- Notification Items -->
                    <button
                        v-for="(item, index) in items"
                        :key="item.id"
                        @click="goTo(item)"
                        class="w-full flex items-start gap-3.5 px-5 py-4 hover:bg-[#FDF8F0] transition-all duration-200 text-left group"
                        :style="{ animationDelay: `${index * 50}ms` }"
                    >
                        <!-- Type Icon -->
                        <div
                            class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center mt-0.5 transition-all duration-200 group-hover:scale-110 group-hover:shadow-md"
                            :class="{
                                'bg-blue-50 text-blue-500 group-hover:bg-blue-100': item.type === 'new_booking',
                                'bg-emerald-50 text-emerald-500 group-hover:bg-emerald-100': item.type === 'new_visit',
                                'bg-amber-50 text-amber-500 group-hover:bg-amber-100': item.type === 'booking_reminder',
                                'bg-red-50 text-red-500 group-hover:bg-red-100': item.type === 'dental_lab_overdue',
                                'bg-cyan-50 text-cyan-500 group-hover:bg-cyan-100': item.type === 'dental_appointment_reminder',
                                'bg-orange-50 text-orange-500 group-hover:bg-orange-100': item.type === 'dental_plan_due',
                                'bg-amber-50 text-amber-500 group-hover:bg-amber-100': item.type === 'dental_followup_reminder',
                                'bg-green-50 text-green-500 group-hover:bg-green-100': item.type === 'pediatric_vaccination_due',
                            'bg-red-50 text-red-500 group-hover:bg-red-100': item.type === 'pediatric_vaccination_overdue',
                            'bg-rose-50 text-rose-500 group-hover:bg-rose-100': item.type === 'pediatric_growth_alert',
                            'bg-purple-50 text-purple-500 group-hover:bg-purple-100': item.type === 'pediatric_milestone_alert',
                            'bg-gray-50 text-gray-400 group-hover:bg-gray-100': !['new_booking','new_visit','booking_reminder','dental_lab_overdue','dental_appointment_reminder','dental_plan_due','dental_followup_reminder','pediatric_vaccination_due','pediatric_vaccination_overdue','pediatric_growth_alert','pediatric_milestone_alert'].includes(item.type),
                            }"
                        >
                            <!-- Calendar for bookings -->
                            <svg v-if="item.type === 'new_booking'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <!-- Clipboard for visits -->
                            <svg v-else-if="item.type === 'new_visit'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <!-- Clock for reminders -->
                            <svg v-else-if="item.type === 'booking_reminder'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Dental Lab Overdue -->
                            <svg v-else-if="item.type === 'dental_lab_overdue'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2C9.5 2 7 4 7 7c0 2-.5 4-1 6s-1 5 0 7c.5 1 1.5 2 3 2s2-1.5 3-4c1 2.5 1.5 4 3 4s2.5-1 3-2c1-2 .5-5 0-7s-1-4-1-6c0-3-2.5-5-5-5z" />
                            </svg>
                            <!-- Dental Appointment -->
                            <svg v-else-if="item.type === 'dental_appointment_reminder'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <!-- Dental Plan Due -->
                            <svg v-else-if="item.type === 'dental_plan_due'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v4m-2-2h4" />
                            </svg>
                            <!-- Dental Follow-up Reminder (clock) -->
                            <svg v-else-if="item.type === 'dental_followup_reminder'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Pediatric Vaccination (syringe) -->
                            <svg v-else-if="item.type === 'pediatric_vaccination_due' || item.type === 'pediatric_vaccination_overdue'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                            </svg>
                            <!-- Pediatric Growth Alert (chart) -->
                            <svg v-else-if="item.type === 'pediatric_growth_alert'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            <!-- Pediatric Milestone Alert (puzzle/star) -->
                            <svg v-else-if="item.type === 'pediatric_milestone_alert'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                            </svg>
                            <!-- Bell for general -->
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-[13px] font-semibold text-gray-800 group-hover:text-[#8B6F3A] transition-colors truncate">{{ item.title }}</p>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap flex-shrink-0 font-medium">{{ timeAgo(item.time) }}</span>
                            </div>
                            <p class="text-[12px] text-gray-500 truncate mt-0.5">{{ item.subtitle }}</p>
                            <span
                                class="inline-block mt-2 text-[10px] font-semibold px-2.5 py-0.5 rounded-full"
                                :class="[getTypeBadge(item.type).bg, getTypeBadge(item.type).text]"
                            >
                                {{ getTypeBadge(item.type).label }}
                            </span>
                        </div>

                        <!-- Unread indicator -->
                        <div class="flex-shrink-0 mt-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#C4A265] opacity-40"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#C4A265]"></span>
                            </span>
                        </div>
                    </button>

                    <!-- Empty State -->
                    <div v-if="!loading && items.length === 0" class="flex flex-col items-center justify-center py-14 px-4">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mb-4 shadow-inner">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
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
/* Bell shake animation triggered by new notifications */
@keyframes bellShake {
    0% { transform: rotate(0deg); }
    5% { transform: rotate(15deg); }
    10% { transform: rotate(-13deg); }
    15% { transform: rotate(12deg); }
    20% { transform: rotate(-10deg); }
    25% { transform: rotate(8deg); }
    30% { transform: rotate(-6deg); }
    35% { transform: rotate(4deg); }
    40% { transform: rotate(-3deg); }
    45% { transform: rotate(2deg); }
    50% { transform: rotate(0deg); }
    100% { transform: rotate(0deg); }
}

.bell-shake {
    animation: bellShake 1.2s ease-in-out;
    transform-origin: top center;
}

/* Badge pulse when new notification arrives */
@keyframes badgePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.3); }
}

.badge-pulse {
    animation: badgePulse 0.6s ease-in-out 3;
}
</style>
