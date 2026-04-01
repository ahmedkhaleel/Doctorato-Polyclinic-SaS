<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    notifications: Object,
    bookingNotifications: Array,
    messageNotifications: Array,
    stats: Object,
    types: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filter = ref(props.filters?.filter || 'all');
const typeFilter = ref(props.filters?.type || '');

/* ── Type Config ────────────────────────────────── */
const typeConfig = {
    new_website_lead:       { labelEn: 'New Lead',          labelAr: 'عميل جديد',         color: 'bg-blue-100 text-blue-700',    icon: 'user-plus' },
    lead_assigned:          { labelEn: 'Lead Assigned',     labelAr: 'تعيين عميل',        color: 'bg-indigo-100 text-indigo-700', icon: 'user-check' },
    follow_up_reminder:     { labelEn: 'Follow-up',         labelAr: 'متابعة',            color: 'bg-amber-100 text-amber-700',  icon: 'clock' },
    follow_up_overdue:      { labelEn: 'Overdue',           labelAr: 'متأخر',             color: 'bg-red-100 text-red-700',      icon: 'alert' },
    sequence_step:          { labelEn: 'Automation',        labelAr: 'أتمتة',             color: 'bg-purple-100 text-purple-700', icon: 'zap' },
    daily_lead_report:      { labelEn: 'Report',            labelAr: 'تقرير',             color: 'bg-emerald-100 text-emerald-700', icon: 'chart' },
    dental_lab_overdue:     { labelEn: 'Lab Overdue',       labelAr: 'معمل متأخر',        color: 'bg-red-100 text-red-700',      icon: 'alert' },
    dental_appointment_reminder: { labelEn: 'Appointment',  labelAr: 'موعد',              color: 'bg-cyan-100 text-cyan-700',    icon: 'calendar' },
    dental_plan_due:        { labelEn: 'Plan Due',          labelAr: 'خطة مستحقة',        color: 'bg-orange-100 text-orange-700', icon: 'clipboard' },
    dental_followup_reminder: { labelEn: 'Dental Follow-up', labelAr: 'متابعة أسنان',    color: 'bg-teal-100 text-teal-700',    icon: 'tooth' },
    booking:                { labelEn: 'Booking',           labelAr: 'حجز',               color: 'bg-green-100 text-green-700',  icon: 'calendar' },
    message:                { labelEn: 'Message',           labelAr: 'رسالة',             color: 'bg-sky-100 text-sky-700',      icon: 'mail' },
    general:                { labelEn: 'General',           labelAr: 'عام',               color: 'bg-gray-100 text-gray-700',    icon: 'bell' },
};

function getTypeConfig(type) {
    return typeConfig[type] || typeConfig.general;
}

/* ── Module-aware filtering ────────────────────── */
const modules = computed(() => page.props.modules || {});
const dentalTypes = ['dental_lab_overdue', 'dental_appointment_reminder', 'dental_plan_due', 'dental_followup_reminder'];
const isDentalEnabled = computed(() => modules.value.dental?.is_enabled !== false);

const filteredTypes = computed(() => {
    if (isDentalEnabled.value) return props.types || {};
    const result = {};
    for (const [key, val] of Object.entries(props.types || {})) {
        if (!dentalTypes.includes(key)) result[key] = val;
    }
    return result;
});

/* ── All notifications merged ───────────────────── */
const allItems = computed(() => {
    const dbNotifs = props.notifications?.data || [];
    const bookings = props.bookingNotifications || [];
    const messages = props.messageNotifications || [];
    let items = [...bookings, ...messages, ...dbNotifs];
    if (!isDentalEnabled.value) {
        items = items.filter(n => !dentalTypes.includes(n.type));
    }
    return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

/* ── Actions ────────────────────────────────────── */
let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

function applyFilters() {
    router.get('/admin/notification-center', {
        filter: filter.value !== 'all' ? filter.value : undefined,
        type: typeFilter.value || undefined,
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
}

function setFilter(f) {
    filter.value = f;
    applyFilters();
}

function setTypeFilter(t) {
    typeFilter.value = typeFilter.value === t ? '' : t;
    applyFilters();
}

function markRead(item) {
    if (item.read_at) return;
    if (String(item.id).startsWith('booking_') || String(item.id).startsWith('message_')) {
        const [type, id] = String(item.id).split('_');
        router.post(`/admin/notifications/${type}/${id}/read`, {}, { preserveState: true });
    } else {
        router.post(`/admin/notification-center/${item.id}/read`, {}, { preserveState: true });
    }
}

function markAllRead() {
    router.post('/admin/notification-center/mark-all-read', {}, { preserveState: false });
}

function deleteNotification(id) {
    if (String(id).startsWith('booking_') || String(id).startsWith('message_')) return;
    router.delete(`/admin/notification-center/${id}`, { preserveState: false });
}

function clearRead() {
    if (!confirm(isRtl.value ? 'حذف جميع الإشعارات المقروءة؟' : 'Delete all read notifications?')) return;
    router.delete('/admin/notification-center/clear', {
        data: { only_read: true },
        preserveState: false,
    });
}

function navigateTo(item) {
    markRead(item);
    if (item.url) router.visit(item.url);
}

function formatDateTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

/* ── Grouped by date ────────────────────────────── */
const groupedNotifications = computed(() => {
    const groups = {};
    for (const item of allItems.value) {
        const d = new Date(item.created_at);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        let label;
        if (d.toDateString() === today.toDateString()) {
            label = isRtl.value ? 'اليوم' : 'Today';
        } else if (d.toDateString() === yesterday.toDateString()) {
            label = isRtl.value ? 'أمس' : 'Yesterday';
        } else {
            label = d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', {
                weekday: 'long', month: 'long', day: 'numeric',
            });
        }

        if (!groups[label]) groups[label] = [];
        groups[label].push(item);
    }
    return groups;
});
</script>

<template>
    <AdminLayout :title="isRtl ? 'مركز الإشعارات' : 'Notification Center'">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'مركز الإشعارات' : 'Notification Center' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ isRtl ? `${stats.unread} إشعار غير مقروء` : `${stats.unread} unread notifications` }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="stats.unread > 0" @click="markAllRead"
                        class="px-4 py-2 text-sm font-medium text-cyan-700 bg-cyan-50 rounded-lg hover:bg-cyan-100 transition">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            {{ isRtl ? 'تحديد الكل كمقروء' : 'Mark All Read' }}
                        </span>
                    </button>
                    <button @click="clearRead"
                        class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            {{ isRtl ? 'حذف المقروءة' : 'Clear Read' }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي الإشعارات' : 'Total' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-cyan-200 p-4">
                    <div class="text-2xl font-bold text-cyan-600">{{ stats.unread }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'غير مقروء' : 'Unread' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-green-200 p-4">
                    <div class="text-2xl font-bold text-green-600">{{ stats.unread_bookings }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'حجوزات جديدة' : 'New Bookings' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-sky-200 p-4">
                    <div class="text-2xl font-bold text-sky-600">{{ stats.unread_messages }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'رسائل جديدة' : 'New Messages' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ stats.today }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'اليوم' : 'Today' }}</div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                <!-- Search + Status Filter -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <svg class="absolute top-2.5 w-4 h-4 text-gray-400" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35"/></svg>
                        <input v-model="search" type="text"
                            :placeholder="isRtl ? 'بحث في الإشعارات...' : 'Search notifications...'"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500"
                            :class="isRtl ? 'pr-10 pl-3' : 'pl-10 pr-3'" />
                    </div>
                    <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                        <button v-for="f in [
                            { key: 'all', labelEn: 'All', labelAr: 'الكل' },
                            { key: 'unread', labelEn: 'Unread', labelAr: 'غير مقروء' },
                            { key: 'read', labelEn: 'Read', labelAr: 'مقروء' },
                        ]" :key="f.key"
                            @click="setFilter(f.key)"
                            class="px-4 py-2 text-sm font-medium transition"
                            :class="filter === f.key
                                ? 'bg-cyan-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-50'">
                            {{ isRtl ? f.labelAr : f.labelEn }}
                        </button>
                    </div>
                </div>

                <!-- Type chips -->
                <div class="flex flex-wrap gap-2" v-if="Object.keys(filteredTypes).length">
                    <button v-for="(count, t) in filteredTypes" :key="t"
                        @click="setTypeFilter(t)"
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium transition border"
                        :class="typeFilter === t
                            ? 'bg-cyan-600 text-white border-cyan-600'
                            : `${getTypeConfig(t).color} border-transparent hover:opacity-80`">
                        {{ isRtl ? getTypeConfig(t).labelAr : getTypeConfig(t).labelEn }}
                        <span class="font-bold">({{ count }})</span>
                    </button>
                </div>
            </div>

            <!-- Notification List -->
            <div class="space-y-6">
                <template v-if="Object.keys(groupedNotifications).length">
                    <div v-for="(items, dateLabel) in groupedNotifications" :key="dateLabel">
                        <!-- Date Group Header -->
                        <div class="sticky top-0 z-10 bg-gray-50/80 backdrop-blur-sm px-4 py-2 rounded-lg mb-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ dateLabel }}</span>
                        </div>

                        <!-- Notification Items -->
                        <div class="space-y-1">
                            <div v-for="item in items" :key="item.id"
                                @click="navigateTo(item)"
                                class="group flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all duration-150"
                                :class="item.read_at
                                    ? 'bg-white border-gray-100 hover:border-gray-200 hover:bg-gray-50'
                                    : 'bg-cyan-50/50 border-cyan-100 hover:border-cyan-200 hover:bg-cyan-50'">

                                <!-- Unread dot -->
                                <div class="flex-shrink-0 mt-1.5">
                                    <div v-if="!item.read_at" class="w-2.5 h-2.5 rounded-full bg-cyan-500 ring-2 ring-cyan-100"></div>
                                    <div v-else class="w-2.5 h-2.5 rounded-full bg-gray-200"></div>
                                </div>

                                <!-- Type badge -->
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg"
                                        :class="getTypeConfig(item.type).color">
                                        <!-- Bell -->
                                        <svg v-if="getTypeConfig(item.type).icon === 'bell'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                        <!-- User Plus -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'user-plus'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm10-2v6m3-3h-6" /></svg>
                                        <!-- User Check -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'user-check'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm10 0l2 2 4-4" /></svg>
                                        <!-- Clock -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'clock'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="1.5"/><path stroke-linecap="round" stroke-width="1.5" d="M12 6v6l4 2" /></svg>
                                        <!-- Alert -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'alert'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <!-- Zap -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'zap'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <!-- Chart -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'chart'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                        <!-- Calendar -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'calendar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" stroke-width="1.5"/><path stroke-linecap="round" stroke-width="1.5" d="M16 2v4M8 2v4M3 10h18" /></svg>
                                        <!-- Clipboard -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'clipboard'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                        <!-- Mail -->
                                        <svg v-else-if="getTypeConfig(item.type).icon === 'mail'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        <!-- Fallback bell -->
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                    </span>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-sm font-semibold text-gray-900 truncate"
                                            :class="{ 'font-bold': !item.read_at }">
                                            {{ item.title }}
                                        </h4>
                                        <span class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">
                                            {{ item.time_ago }}
                                        </span>
                                    </div>
                                    <p v-if="item.message" class="text-sm text-gray-500 mt-0.5 line-clamp-2">{{ item.message }}</p>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="getTypeConfig(item.type).color">
                                            {{ isRtl ? getTypeConfig(item.type).labelAr : getTypeConfig(item.type).labelEn }}
                                        </span>
                                        <span v-if="item.priority === 'high' || item.priority === 'urgent'"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            {{ item.priority === 'urgent' ? (isRtl ? 'عاجل' : 'Urgent') : (isRtl ? 'مهم' : 'High') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                                    <button v-if="!item.read_at" @click.stop="markRead(item)"
                                        class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-cyan-600" :title="isRtl ? 'تحديد كمقروء' : 'Mark as read'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <button v-if="!String(item.id).startsWith('booking_') && !String(item.id).startsWith('message_')"
                                        @click.stop="deleteNotification(item.id)"
                                        class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-red-600" :title="isRtl ? 'حذف' : 'Delete'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-xl border border-gray-200 p-16 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    <h3 class="text-lg font-medium text-gray-900">{{ isRtl ? 'لا توجد إشعارات' : 'No Notifications' }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'أنت على اطلاع بكل شيء!' : "You're all caught up!" }}</p>
                </div>

                <!-- Pagination -->
                <div v-if="notifications?.links?.length > 3" class="flex justify-center gap-1">
                    <template v-for="link in notifications.links" :key="link.label">
                        <Link v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1.5 text-sm rounded-lg border transition"
                            :class="link.active ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            v-html="link.label" preserve-state />
                        <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
