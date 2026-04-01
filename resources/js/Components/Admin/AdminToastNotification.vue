<script setup>
import { ref, watch, computed, onMounted, onUnmounted, provide, nextTick } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import BookingPopup from '@/Components/BookingPopup.vue';

const page = usePage();
const toasts = ref([]);
let toastId = 0;
let previousCount = null;
let previousItemIds = [];
let pollInterval = null;
let audioCtx = null;

const activeBookingPopup = ref(null);

// ─── Browser Notification Permission ─────────────────────────
const browserNotifPermission = ref(typeof Notification !== 'undefined' ? Notification.permission : 'denied');

function requestBrowserNotification() {
    if (typeof Notification !== 'undefined' && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            browserNotifPermission.value = permission;
        });
    }
}

function showBrowserNotification(title, body, url = null, icon = null) {
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
    try {
        const notif = new Notification(title, {
            body,
            icon: icon || '/favicon.ico',
            badge: '/favicon.ico',
            tag: 'aura-admin-' + Date.now(),
            requireInteraction: false,
        });
        if (url) {
            notif.onclick = () => {
                window.focus();
                router.visit(url);
                notif.close();
            };
        }
        setTimeout(() => notif.close(), 15000);
    } catch (e) {}
}

// ─── Booking Popup ──────────────────────────────────────────
function showBookingPopup(item) {
    activeBookingPopup.value = {
        title: item.title,
        subtitle: item.subtitle,
        url: item.url,
        bookingNumber: item.booking_number || null,
        doctorName: item.doctor_name || null,
        serviceName: item.service_name || null,
        date: item.preferred_date || null,
        time: item.preferred_time || null,
    };
}

function dismissBookingPopup() {
    activeBookingPopup.value = null;
}

function navigateBookingPopup() {
    const url = activeBookingPopup.value?.url;
    activeBookingPopup.value = null;
    if (url) router.visit(url);
}

// ─── Notification Sound (Web Audio API) ───────────────────────
function playNotificationSound() {
    try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const now = audioCtx.currentTime;

        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(880, now);
        osc1.frequency.setValueAtTime(1100, now + 0.08);
        gain1.gain.setValueAtTime(0.15, now);
        gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.25);
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.start(now);
        osc1.stop(now + 0.25);

        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(1320, now + 0.12);
        gain2.gain.setValueAtTime(0, now);
        gain2.gain.setValueAtTime(0.12, now + 0.12);
        gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.45);
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.start(now + 0.12);
        osc2.stop(now + 0.45);
    } catch (e) {}
}

// ─── Urgent sound for high-priority leads ───────────────────
function playUrgentSound() {
    try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const now = audioCtx.currentTime;

        // Three-tone ascending urgent alert
        for (let i = 0; i < 3; i++) {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            const freq = 660 + (i * 220);
            const start = now + (i * 0.15);
            osc.frequency.setValueAtTime(freq, start);
            gain.gain.setValueAtTime(0.18, start);
            gain.gain.exponentialRampToValueAtTime(0.01, start + 0.2);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(start);
            osc.stop(start + 0.2);
        }
    } catch (e) {}
}

// ─── Bell Shake Event ─────────────────────────────────────────
const bellShaking = ref(false);
provide('bellShaking', bellShaking);
function triggerBellShake() {
    bellShaking.value = true;
    setTimeout(() => { bellShaking.value = false; }, 1200);
}
provide('triggerBellShake', triggerBellShake);

// ─── Flash Message Watchers ───────────────────────────────────
watch(() => page.props.flash?.success, (msg) => {
    if (msg) addToast({ message: msg, type: 'success' });
}, { flush: 'post' });

watch(() => page.props.flash?.error, (msg) => {
    if (msg) addToast({ message: msg, type: 'error' });
}, { flush: 'post' });

// ─── Global Inertia Error Listener ──────────────────────────
function handleInertiaError(event) {
    const { message, type } = event.detail || {};
    if (message) addToast({ message, type: type || 'error' });
}
onMounted(() => window.addEventListener('inertia-error', handleInertiaError));
onUnmounted(() => window.removeEventListener('inertia-error', handleInertiaError));

// ─── Independent Polling (every 5s) ──────────────────────────
function checkNotifications() {
    fetch('/admin/notifications', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(res => res.ok ? res.json() : null)
    .then(data => {
        if (!data || data.unread_count === undefined) return;

        const newCount = data.unread_count;
        const items = data.items || [];
        const currentItemIds = items.map(i => i.id);

        // Find genuinely NEW notification items
        const newItems = items.filter(item => !previousItemIds.includes(item.id));

        if (newItems.length > 0 && previousItemIds.length > 0) {
            triggerBellShake();

            const bookingItems = newItems.filter(i => i.type === 'booking');
            const leadItems = newItems.filter(i => i.type === 'new_website_lead');
            const otherItems = newItems.filter(i => i.type !== 'booking' && i.type !== 'new_website_lead');

            // Show popup for the first new booking
            if (bookingItems.length > 0) {
                showBookingPopup(bookingItems[0]);
                playNotificationSound();
                showBrowserNotification(
                    'New Booking!',
                    bookingItems[0].title + ' - ' + bookingItems[0].subtitle,
                    bookingItems[0].url
                );
                bookingItems.slice(1).forEach((item, idx) => {
                    setTimeout(() => addToast({
                        type: 'notification', notificationType: item.type,
                        title: item.title, subtitle: item.subtitle, url: item.url, time: item.time,
                    }), (idx + 1) * 300);
                });
            }

            // Show urgent alert for new website leads
            if (leadItems.length > 0) {
                playUrgentSound();
                leadItems.forEach((item, idx) => {
                    setTimeout(() => {
                        addToast({
                            type: 'notification', notificationType: item.type,
                            title: item.title, subtitle: item.subtitle, url: item.url, time: item.time,
                            priority: item.priority,
                        });
                        showBrowserNotification(
                            'New Lead from Website!',
                            item.title + (item.subtitle ? ' - ' + item.subtitle : ''),
                            item.url
                        );
                    }, idx * 300);
                });
            }

            // Other notifications (follow-ups, reports, etc.)
            if (otherItems.length > 0) {
                if (bookingItems.length === 0 && leadItems.length === 0) playNotificationSound();
                otherItems.forEach((item, idx) => {
                    setTimeout(() => {
                        addToast({
                            type: 'notification', notificationType: item.type,
                            title: item.title, subtitle: item.subtitle, url: item.url, time: item.time,
                        });
                        // Browser notification for important types
                        if (['follow_up_overdue', 'daily_lead_report'].includes(item.type)) {
                            showBrowserNotification(
                                getNotificationLabel(item.type),
                                item.subtitle || item.title,
                                item.url
                            );
                        }
                    }, idx * 300);
                });
            }
        }

        previousCount = newCount;
        previousItemIds = currentItemIds;

        // Emit event so NotificationBell can update
        window.dispatchEvent(new CustomEvent('admin-notification-update', {
            detail: { unread_count: newCount, items, ...data }
        }));
    })
    .catch(() => {});
}

onMounted(() => {
    // Request browser notification permission
    requestBrowserNotification();

    // Fetch initial items
    fetch('/admin/notifications', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(res => res.ok ? res.json() : null)
    .then(data => {
        if (data?.items) {
            previousItemIds = data.items.map(i => i.id);
            previousCount = data.unread_count ?? 0;
        }
    })
    .catch(() => {});

    // Start polling every 5 seconds
    pollInterval = setInterval(checkNotifications, 5000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

// ─── Navigate to notification URL ─────────────────────────────
function goToNotification(toast) {
    if (toast.url) {
        removeToast(toast.id);
        router.visit(toast.url);
    }
}

// ─── Toast Management ─────────────────────────────────────────
function addToast(options) {
    const id = ++toastId;
    toasts.value.push({
        id,
        visible: false,
        progress: 100,
        type: options.type || 'info',
        message: options.message || null,
        notificationType: options.notificationType || null,
        title: options.title || null,
        subtitle: options.subtitle || null,
        url: options.url || null,
        time: options.time || null,
        priority: options.priority || null,
    });

    nextTick(() => {
        const idx = toasts.value.findIndex(t => t.id === id);
        if (idx !== -1) toasts.value[idx].visible = true;
    });

    const duration = options.type === 'notification' ? 15000 : 5000;
    const startTime = Date.now();
    const progressInterval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, 100 - (elapsed / duration) * 100);
        const idx = toasts.value.findIndex(t => t.id === id);
        if (idx !== -1) toasts.value[idx].progress = remaining;
        if (remaining <= 0) clearInterval(progressInterval);
    }, 50);

    setTimeout(() => {
        removeToast(id);
        clearInterval(progressInterval);
    }, duration);
}

function removeToast(id) {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index !== -1) {
        toasts.value[index].visible = false;
        setTimeout(() => {
            toasts.value = toasts.value.filter(t => t.id !== id);
        }, 400);
    }
}

function getNotificationIcon(notifType) {
    switch (notifType) {
        case 'booking': return { icon: 'calendar', color: 'from-blue-500 to-blue-600' };
        case 'message': return { icon: 'envelope', color: 'from-amber-500 to-amber-600' };
        case 'new_website_lead': return { icon: 'lead', color: 'from-emerald-500 to-emerald-600' };
        case 'lead_assigned': return { icon: 'user', color: 'from-indigo-500 to-indigo-600' };
        case 'follow_up_reminder': return { icon: 'clock', color: 'from-amber-500 to-orange-500' };
        case 'follow_up_overdue': return { icon: 'alert', color: 'from-red-500 to-red-600' };
        case 'sequence_step': return { icon: 'automation', color: 'from-purple-500 to-purple-600' };
        case 'daily_lead_report': return { icon: 'chart', color: 'from-[#C4A265] to-[#D4B87A]' };
        default: return { icon: 'bell', color: 'from-[#C4A265] to-[#D4B87A]' };
    }
}

function getNotificationLabel(notifType) {
    switch (notifType) {
        case 'booking': return 'New Booking';
        case 'message': return 'New Message';
        case 'new_website_lead': return 'New Lead';
        case 'lead_assigned': return 'Lead Assigned';
        case 'follow_up_reminder': return 'Follow-up Due';
        case 'follow_up_overdue': return 'Overdue!';
        case 'sequence_step': return 'Automation';
        case 'daily_lead_report': return 'Daily Report';
        default: return 'Notification';
    }
}

function getNotificationLabelColor(notifType) {
    switch (notifType) {
        case 'booking': return 'bg-blue-100 text-blue-700';
        case 'message': return 'bg-amber-100 text-amber-700';
        case 'new_website_lead': return 'bg-emerald-100 text-emerald-700';
        case 'lead_assigned': return 'bg-indigo-100 text-indigo-700';
        case 'follow_up_reminder': return 'bg-orange-100 text-orange-700';
        case 'follow_up_overdue': return 'bg-red-100 text-red-700';
        case 'sequence_step': return 'bg-purple-100 text-purple-700';
        case 'daily_lead_report': return 'bg-[#C4A265]/15 text-[#8B6F3A]';
        default: return 'bg-gray-100 text-gray-700';
    }
}
</script>

<template>
    <!-- Browser Notification Permission Banner -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
        >
            <div
                v-if="browserNotifPermission === 'default'"
                class="fixed top-4 left-1/2 -translate-x-1/2 z-[99998] bg-white rounded-2xl shadow-xl shadow-black/10 border border-[#C4A265]/30 px-5 py-3.5 flex items-center gap-4 max-w-md"
            >
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-gray-800">Enable Push Notifications</p>
                    <p class="text-xs text-gray-500 mt-0.5">Get instant alerts for new leads, bookings & follow-ups</p>
                </div>
                <button
                    @click="requestBrowserNotification"
                    class="px-4 py-2 bg-[#C4A265] text-white text-xs font-bold rounded-xl hover:bg-[#A68B52] transition-colors flex-shrink-0"
                >
                    Enable
                </button>
                <button
                    @click="browserNotifPermission = 'denied'"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>
    </Teleport>

    <!-- Booking Popup -->
    <BookingPopup
        :booking="activeBookingPopup"
        accent-color="#C4A265"
        @dismiss="dismissBookingPopup"
        @navigate="navigateBookingPopup"
    />

    <!-- Toast Notifications -->
    <Teleport to="body">
        <div class="fixed top-5 right-5 z-[99999] flex flex-col gap-3 pointer-events-none" style="max-width: 440px;">
            <TransitionGroup
                enter-active-class="toast-enter-active"
                enter-from-class="toast-enter-from"
                enter-to-class="toast-enter-to"
                leave-active-class="toast-leave-active"
                leave-from-class="toast-leave-from"
                leave-to-class="toast-leave-to"
                move-class="toast-move"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :style="{ opacity: toast.visible ? 1 : 0, transform: toast.visible ? 'translateX(0) scale(1)' : 'translateX(40px) scale(0.92)', transition: 'all 0.45s cubic-bezier(0.34, 1.56, 0.64, 1)' }"
                >
                    <!-- NOTIFICATION TYPE -->
                    <div
                        v-if="toast.type === 'notification'"
                        class="pointer-events-auto relative overflow-hidden rounded-2xl shadow-2xl shadow-black/20 border bg-white min-w-[380px] cursor-pointer group hover:shadow-[#C4A265]/10 transition-all duration-300"
                        :class="toast.notificationType === 'new_website_lead'
                            ? 'border-emerald-300 hover:border-emerald-400'
                            : toast.notificationType === 'follow_up_overdue'
                                ? 'border-red-300 hover:border-red-400'
                                : 'border-[#C4A265]/30 hover:border-[#C4A265]/50'"
                        @click="goToNotification(toast)"
                    >
                        <!-- Accent stripe -->
                        <div
                            class="h-1 bg-gradient-to-r"
                            :class="toast.notificationType === 'new_website_lead'
                                ? 'from-emerald-400 via-emerald-500 to-emerald-400'
                                : toast.notificationType === 'follow_up_overdue'
                                    ? 'from-red-400 via-red-500 to-red-400'
                                    : 'from-[#C4A265] via-[#D4B87A] to-[#C4A265]'"
                        ></div>

                        <div class="flex items-start gap-3.5 px-4 pt-3.5 pb-3">
                            <!-- Type Icon -->
                            <div
                                class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center shadow-lg bg-gradient-to-br"
                                :class="getNotificationIcon(toast.notificationType).color"
                                style="animation: bellPulse 0.6s ease-in-out"
                            >
                                <!-- New Lead -->
                                <svg v-if="toast.notificationType === 'new_website_lead'" class="w-6 h-6 text-white animate-bell-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <!-- Booking -->
                                <svg v-else-if="toast.notificationType === 'booking'" class="w-6 h-6 text-white animate-bell-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <!-- Message -->
                                <svg v-else-if="toast.notificationType === 'message'" class="w-6 h-6 text-white animate-bell-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <!-- Overdue -->
                                <svg v-else-if="toast.notificationType === 'follow_up_overdue'" class="w-6 h-6 text-white animate-bell-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <!-- Follow-up -->
                                <svg v-else-if="['follow_up_reminder', 'lead_assigned'].includes(toast.notificationType)" class="w-6 h-6 text-white animate-bell-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <!-- Automation -->
                                <svg v-else-if="toast.notificationType === 'sequence_step'" class="w-6 h-6 text-white animate-bell-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <!-- Daily Report -->
                                <svg v-else-if="toast.notificationType === 'daily_lead_report'" class="w-6 h-6 text-white animate-bell-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <!-- Bell General -->
                                <svg v-else class="w-6 h-6 text-white animate-bell-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>

                            <!-- Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span
                                        class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                        :class="getNotificationLabelColor(toast.notificationType)"
                                    >
                                        {{ getNotificationLabel(toast.notificationType) }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 font-medium">Just now</span>
                                </div>
                                <p class="text-[15px] font-bold text-gray-900 leading-snug">{{ toast.title }}</p>
                                <p class="text-[12px] text-gray-500 mt-0.5 leading-relaxed line-clamp-2">{{ toast.subtitle }}</p>
                                <div class="flex items-center gap-1 mt-2 text-[11px] font-semibold text-[#C4A265] group-hover:text-[#A68B52] transition-colors">
                                    <span>Click to view details</span>
                                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Close -->
                            <button
                                @click.stop="removeToast(toast.id)"
                                class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-gray-300 hover:text-gray-600 hover:bg-gray-100 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Progress Bar -->
                        <div class="h-[3px] bg-gray-100 w-full">
                            <div
                                class="h-full transition-all duration-100 ease-linear rounded-full"
                                :class="toast.notificationType === 'new_website_lead'
                                    ? 'bg-gradient-to-r from-emerald-400 to-emerald-500'
                                    : toast.notificationType === 'follow_up_overdue'
                                        ? 'bg-gradient-to-r from-red-400 to-red-500'
                                        : 'bg-gradient-to-r from-[#C4A265] to-[#D4B87A]'"
                                :style="{ width: toast.progress + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- SUCCESS / ERROR / INFO TYPE -->
                    <div
                        v-else
                        class="pointer-events-auto relative overflow-hidden rounded-2xl shadow-2xl shadow-black/15 border backdrop-blur-sm min-w-[340px]"
                        :class="{
                            'bg-emerald-50 border-emerald-200': toast.type === 'success',
                            'bg-red-50 border-red-200': toast.type === 'error',
                            'bg-white border-gray-200': toast.type === 'info',
                        }"
                    >
                        <div class="flex items-start gap-3.5 px-4 py-3.5">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-lg"
                                :class="{
                                    'bg-emerald-500': toast.type === 'success',
                                    'bg-red-500': toast.type === 'error',
                                    'bg-gray-500': toast.type === 'info',
                                }"
                            >
                                <svg v-if="toast.type === 'success'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else-if="toast.type === 'error'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 3h.01M12 3a9 9 0 110 18 9 9 0 010-18z" />
                                </svg>
                                <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0 pt-0.5">
                                <p class="text-sm font-bold" :class="{ 'text-emerald-800': toast.type === 'success', 'text-red-800': toast.type === 'error', 'text-gray-800': toast.type === 'info' }">
                                    {{ toast.type === 'success' ? 'Success' : toast.type === 'error' ? 'Error' : 'Info' }}
                                </p>
                                <p class="text-[13px] text-gray-600 mt-0.5 leading-relaxed">{{ toast.message }}</p>
                            </div>
                            <button
                                @click="removeToast(toast.id)"
                                class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-black/5 transition-all duration-200 mt-0.5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="h-[3px] bg-black/5 w-full">
                            <div
                                class="h-full transition-all duration-100 ease-linear rounded-full"
                                :class="{
                                    'bg-emerald-400': toast.type === 'success',
                                    'bg-red-400': toast.type === 'error',
                                    'bg-gray-400': toast.type === 'info',
                                }"
                                :style="{ width: toast.progress + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style>
@keyframes bellRing {
    0% { transform: rotate(0deg); }
    10% { transform: rotate(14deg); }
    20% { transform: rotate(-12deg); }
    30% { transform: rotate(10deg); }
    40% { transform: rotate(-8deg); }
    50% { transform: rotate(6deg); }
    60% { transform: rotate(-4deg); }
    70% { transform: rotate(2deg); }
    80% { transform: rotate(-1deg); }
    100% { transform: rotate(0deg); }
}
.animate-bell-ring {
    animation: bellRing 0.8s ease-in-out;
    transform-origin: top center;
}
@keyframes bellPulse {
    0% { transform: scale(1); }
    25% { transform: scale(1.15); }
    50% { transform: scale(0.95); }
    75% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
.toast-enter-active { transition: all 0.45s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-leave-active { transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
.toast-enter-from { opacity: 0; transform: translateX(60px) scale(0.8); }
.toast-enter-to { opacity: 1; transform: translateX(0) scale(1); }
.toast-leave-from { opacity: 1; transform: translateX(0) scale(1); }
.toast-leave-to { opacity: 0; transform: translateX(60px) scale(0.8); }
.toast-move { transition: transform 0.3s ease; }
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
