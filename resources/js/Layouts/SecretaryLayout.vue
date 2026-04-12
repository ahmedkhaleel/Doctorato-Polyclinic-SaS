<script setup>
import { ref, computed, watchEffect } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import SecretaryNotificationBell from '@/Components/Secretary/SecretaryNotificationBell.vue';
import SecretaryToastNotification from '@/Components/Secretary/SecretaryToastNotification.vue';
import ChatIcon from '@/Components/Chat/ChatIcon.vue';
import ChatToast from '@/Components/Chat/ChatToast.vue';

const page = usePage();
const sidebarOpen = ref(false);

const modules = computed(() => page.props.modules || {});
const userName = computed(() => page.props.auth?.user?.name || 'Secretary');
const currentUrl = computed(() => page.url);

/* ── Locale ────────────────────────────────────────────── */
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');

function switchLocale() {
    const newLocale = locale.value === 'ar' ? 'en' : 'ar';
    router.post('/secretary/switch-locale', { locale: newLocale }, { preserveState: false });
}

/* ── Grouped Navigation ─────────────────────────────────── */
const navGroups = [
    {
        key: 'main', titleEn: 'Main', titleAr: 'الرئيسية',
        items: [
            { labelEn: 'Dashboard',   labelAr: 'لوحة التحكم',   href: '/secretary',              icon: 'grid' },
            { labelEn: 'Calendar',    labelAr: 'التقويم',       href: '/secretary/calendar',     icon: 'calendarView' },
            { labelEn: 'Today Queue', labelAr: 'طابور اليوم',   href: '/secretary/queue',        icon: 'queue' },
        ],
    },
    {
        key: 'clinic', titleEn: 'Clinic', titleAr: 'العيادة',
        items: [
            { labelEn: 'Patients',        labelAr: 'المرضى',          href: '/secretary/patients',     icon: 'patients' },
            { labelEn: 'Visits',          labelAr: 'الزيارات',        href: '/secretary/visits',       icon: 'clipboard' },
            { labelEn: 'Bookings',        labelAr: 'الحجوزات',        href: '/secretary/bookings',     icon: 'calendar' },
            { labelEn: 'Bundle Bookings', labelAr: 'حجوزات الباقات',  href: '/secretary/bundle-bookings', icon: 'bundleBooking' },
            { labelEn: 'Prescriptions',   labelAr: 'الوصفات الطبية',  href: '/secretary/prescriptions', icon: 'pill' },
        ],
    },
    {
        key: 'finance', titleEn: 'Finance', titleAr: 'المالية',
        items: [
            { labelEn: 'Invoices',        labelAr: 'الفواتير',        href: '/secretary/invoices',     icon: 'invoice' },
            { labelEn: 'Payments',        labelAr: 'المدفوعات',       href: '/secretary/payments',     icon: 'cash' },
        ],
    },
    {
        key: 'crm', titleEn: 'CRM', titleAr: 'إدارة العملاء',
        items: [
            { labelEn: 'CRM Dashboard',   labelAr: 'لوحة CRM',         href: '/secretary/crm',          icon: 'users' },
            { labelEn: 'My Leads',        labelAr: 'العملاء المحتملين', href: '/secretary/crm/leads',    icon: 'userList' },
            { labelEn: 'Calendar',        labelAr: 'التقويم',          href: '/secretary/crm/calendar',     icon: 'calendarView' },
            { labelEn: 'Import Leads',   labelAr: 'استيراد عملاء',    href: '/secretary/crm/import',      icon: 'upload' },
            { labelEn: 'Performance',     labelAr: 'الأداء',           href: '/secretary/crm/performance', icon: 'barChart' },
        ],
    },
    {
        key: 'dental', titleEn: 'Dental', titleAr: 'طب الأسنان',
        moduleKey: 'dental',
        items: [
            { labelEn: 'Treatment Plans', labelAr: 'خطط العلاج',     href: '/secretary/dental/treatment-plans', icon: 'clipboard' },
            { labelEn: 'Lab Orders',      labelAr: 'طلبات المعمل',    href: '/secretary/dental/lab-orders',     icon: 'invoice' },
            { labelEn: 'Follow-ups',      labelAr: 'المتابعات',       href: '/secretary/dental/followups',     icon: 'clock' },
        ],
    },
    {
        key: 'hr', titleEn: 'HR', titleAr: 'الموارد البشرية',
        moduleKey: 'hr',
        items: [
            { labelEn: 'My Attendance',   labelAr: 'الحضور والانصراف',  href: '/secretary/my-attendance',   icon: 'checklist' },
            { labelEn: 'My Leaves',       labelAr: 'إجازاتي',          href: '/secretary/my-leaves',      icon: 'logout' },
            { labelEn: 'My Salary Slips', labelAr: 'كشوف مرتباتي',     href: '/secretary/my-salary-slips', icon: 'salary' },
        ],
    },
    {
        key: 'account', titleEn: 'My Account', titleAr: 'حسابي',
        items: [
            { labelEn: 'Chat',            labelAr: 'المحادثات',         href: '/secretary/chat',         icon: 'chat' },
            { labelEn: 'My Profile',      labelAr: 'ملفي الشخصي',      href: '/secretary/profile',      icon: 'user' },
        ],
    },
];

const filteredGroups = computed(() =>
    navGroups.filter(g => {
        if (g.moduleKey) return modules.value[g.moduleKey]?.enabled === true;
        return true;
    }).filter(g => g.items.length > 0)
);

function navLabel(item) {
    return locale.value === 'ar' ? item.labelAr : item.labelEn;
}

function groupTitle(group) {
    return locale.value === 'ar' ? group.titleAr : group.titleEn;
}

/* ── Collapsible Groups State ──────────────────────────── */
const openGroups = ref(new Set());

function toggleGroup(key) {
    const newSet = new Set(openGroups.value);
    if (newSet.has(key)) {
        newSet.delete(key);
    } else {
        newSet.add(key);
    }
    openGroups.value = newSet;
}

function isGroupOpen(key) {
    return openGroups.value.has(key);
}

function isActive(href) {
    if (href === '/secretary') return currentUrl.value === '/secretary' || currentUrl.value === '/secretary/';
    if (href === '/secretary/crm') return currentUrl.value === '/secretary/crm' || currentUrl.value === '/secretary/crm/';
    return currentUrl.value.startsWith(href);
}

/* Auto-open group containing active route */
watchEffect(() => {
    const newSet = new Set(openGroups.value);
    navGroups.forEach((group) => {
        if (group.items.some(item => isActive(item.href))) {
            newSet.add(group.key);
        }
    });
    openGroups.value = newSet;
});

function toggleSidebar() { sidebarOpen.value = !sidebarOpen.value; }
function closeSidebar()  { sidebarOpen.value = false; }
function logout()        { router.post('/secretary/logout'); }
</script>

<template>
    <div :dir="dir" class="min-h-screen flex bg-[#f0f9f6]" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden" @click="closeSidebar"></div>

        <!-- ─── Sidebar ──────────────────────────────────────────── -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : (isRtl ? 'translate-x-full' : '-translate-x-full'),
                'lg:translate-x-0'
            ]"
            class="fixed inset-y-0 z-40 w-[260px] transition-transform duration-300 ease-in-out lg:static lg:z-auto flex flex-col bg-[#0d1f2d] shadow-2xl ltr:left-0 rtl:right-0"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-[72px] px-5 border-b border-white/[0.06]">
                <Link href="/secretary" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[15px] font-bold text-teal-400 tracking-wide">AURA</span>
                        <span class="block text-[10px] text-white/30 uppercase tracking-widest -mt-0.5">{{ isRtl ? 'بوابة السكرتارية' : 'Secretary Portal' }}</span>
                    </div>
                </Link>
                <button class="lg:hidden text-white/40 hover:text-white p-1" @click="closeSidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Navigation Groups -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 secretary-sidebar-scroll">
                <div v-for="group in filteredGroups" :key="group.key">
                    <!-- Group Header - Clickable -->
                    <button
                        @click="toggleGroup(group.key)"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[11px] font-semibold uppercase tracking-[0.12em] transition-all duration-200 group/header"
                        :class="isGroupOpen(group.key) ? 'text-teal-400/90 bg-white/[0.02]' : 'text-white/30 hover:text-white/50 hover:bg-white/[0.02]'"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-1.5 h-1.5 rounded-full transition-all duration-300"
                                :class="isGroupOpen(group.key) ? 'bg-teal-400 scale-100' : 'bg-white/20 scale-75'"
                            ></div>
                            <span>{{ groupTitle(group) }}</span>
                        </div>
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-300 ease-out"
                            :class="[
                                isGroupOpen(group.key) ? 'rotate-0 text-teal-400/60' : 'text-white/20',
                                !isGroupOpen(group.key) ? (isRtl ? 'rotate-90' : '-rotate-90') : ''
                            ]"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <!-- Items with collapse animation -->
                    <div
                        class="nav-collapse overflow-hidden transition-all duration-300 ease-in-out"
                        :style="{
                            maxHeight: isGroupOpen(group.key) ? (group.items.length * 44 + 8) + 'px' : '0px',
                            opacity: isGroupOpen(group.key) ? 1 : 0,
                        }"
                    >
                    <div class="space-y-0.5 pt-1 ltr:pl-2 rtl:pr-2">
                        <Link
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                isActive(item.href)
                                    ? 'bg-teal-500/[0.12] text-teal-400'
                                    : 'text-white/50 hover:bg-white/[0.04] hover:text-white/80',
                            ]"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200"
                            @click="closeSidebar"
                        >
                            <!-- Icon wrapper -->
                            <div
                                :class="isActive(item.href) ? 'bg-teal-500/20 text-teal-400' : 'bg-white/[0.04] text-white/40'"
                                class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 transition-colors duration-200"
                            >
                        <!-- Grid (Dashboard) -->
                        <svg v-if="item.icon === 'grid'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                        <!-- Queue -->
                        <svg v-else-if="item.icon === 'queue'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16" /><circle cx="2" cy="6" r="1" fill="currentColor" /><circle cx="2" cy="10" r="1" fill="currentColor" /><circle cx="2" cy="14" r="1" fill="currentColor" /><circle cx="2" cy="18" r="1" fill="currentColor" /></svg>
                        <!-- Patients -->
                        <svg v-else-if="item.icon === 'patients'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <!-- Clipboard (Visits) -->
                        <svg v-else-if="item.icon === 'clipboard'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        <!-- Calendar View -->
                        <svg v-else-if="item.icon === 'calendarView'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01" /></svg>
                        <!-- Calendar (Bookings) -->
                        <svg v-else-if="item.icon === 'calendar'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <!-- Package -->
                        <svg v-else-if="item.icon === 'package'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        <!-- BundleBooking -->
                        <svg v-else-if="item.icon === 'bundleBooking'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v6m-3-3h6" /></svg>
                        <!-- Invoice -->
                        <svg v-else-if="item.icon === 'invoice'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                        <!-- Clock (Follow-ups) -->
                        <svg v-else-if="item.icon === 'clock'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <!-- Cash -->
                        <svg v-else-if="item.icon === 'cash'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <!-- Pill (Prescriptions) -->
                        <svg v-else-if="item.icon === 'pill'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" /></svg>
                        <!-- Checklist (Attendance) -->
                        <svg v-else-if="item.icon === 'checklist'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h.01M9 16h.01M12 12h3m-3 4h3" /></svg>
                        <!-- Logout (Leaves) -->
                        <svg v-else-if="item.icon === 'logout'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <!-- Salary -->
                        <svg v-else-if="item.icon === 'salary'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <!-- Users (CRM Dashboard) -->
                        <svg v-else-if="item.icon === 'users'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        <!-- UserList (My Leads) -->
                        <svg v-else-if="item.icon === 'userList'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        <!-- Bar Chart (Performance) -->
                        <svg v-else-if="item.icon === 'barChart'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        <!-- Upload -->
                        <svg v-else-if="item.icon === 'upload'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                        <!-- Chat -->
                        <svg v-else-if="item.icon === 'chat'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        <!-- User (Profile) -->
                        <svg v-else-if="item.icon === 'user'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <span>{{ navLabel(item) }}</span>
                            <!-- Active indicator dot -->
                            <span v-if="isActive(item.href)" class="ltr:ml-auto rtl:mr-auto w-1.5 h-1.5 rounded-full bg-teal-400"></span>
                        </Link>
                    </div>
                    </div>
                </div>
            </nav>

            <!-- Sidebar footer — User Card -->
            <div class="p-4 border-t border-white/[0.06]">
                <!-- Visit Site -->
                <a
                    href="/"
                    target="_blank"
                    class="flex items-center gap-2 px-3 py-2 mb-2 rounded-lg text-[12px] font-medium text-white/40 hover:text-teal-400 hover:bg-white/[0.04] transition-all duration-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    <span>{{ isRtl ? 'زيارة الموقع' : 'Visit Website' }}</span>
                </a>

                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/[0.04]">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-teal-500/10">
                        {{ userName.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-white/80 truncate">{{ userName }}</p>
                        <p class="text-[10px] text-white/35 truncate">{{ isRtl ? 'سكرتارية' : 'Secretary' }}</p>
                    </div>
                    <button
                        @click="logout"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-white/30 hover:text-red-400 hover:bg-red-500/10 transition-all duration-200"
                        :title="isRtl ? 'تسجيل الخروج' : 'Logout'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- ─── Main Content ─────────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-h-screen lg:min-w-0">
            <!-- Top Header -->
            <header class="h-[72px] bg-white/80 backdrop-blur-md border-b border-gray-200/60 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20">
                <!-- Mobile hamburger -->
                <button
                    class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                    @click="toggleSidebar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <div class="hidden lg:block"></div>

                <!-- Right side -->
                <div class="flex items-center gap-3">
                    <!-- Language Switcher -->
                    <button
                        @click="switchLocale"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 border text-[#C4A265] border-[#C4A265]/30 hover:bg-[#C4A265]/10"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                        <span>{{ isRtl ? 'EN' : 'عربي' }}</span>
                    </button>

                    <!-- Chat -->
                    <ChatIcon panelPrefix="secretary" accentColor="#14b8a6" />

                    <!-- Notification Bell -->
                    <SecretaryNotificationBell />

                    <!-- Divider -->
                    <div class="hidden lg:block w-px h-6 bg-gray-200"></div>

                    <!-- User badge -->
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center text-white text-sm font-bold shadow-md shadow-teal-500/15">
                            {{ userName.charAt(0).toUpperCase() }}
                        </div>
                        <div class="hidden sm:flex flex-col">
                            <span class="text-sm font-semibold text-gray-800 leading-tight">{{ userName }}</span>
                            <span class="text-[11px] text-gray-400 leading-tight">{{ isRtl ? 'سكرتارية' : 'Secretary' }}</span>
                        </div>
                    </div>

                    <!-- Logout -->
                    <button
                        @click="logout"
                        class="hidden lg:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-200"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <span>{{ isRtl ? 'خروج' : 'Logout' }}</span>
                    </button>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4 lg:p-8">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-200/60 bg-white/60 backdrop-blur-sm py-3 px-4 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-1 text-[11px] text-gray-400">
                    <p>&copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة أورا ديرما التجميلية' : 'AURA Derma Aesthetic Clinic' }}</p>
                    <p>
                        {{ isRtl ? 'تطوير' : 'Developed by' }}
                        <a href="https://markeza-group.com" target="_blank" rel="noopener noreferrer"
                           class="text-teal-500 hover:text-teal-600 font-semibold transition-colors duration-200 inline-flex items-center gap-1">
                            Markeza Group
                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        </a>
                    </p>
                </div>
            </footer>
        </div>

        <!-- Toast Notification -->
        <SecretaryToastNotification />

        <!-- Chat Toast Notifications -->
        <ChatToast panelPrefix="secretary" accentColor="#14b8a6" />
    </div>
</template>

<style scoped>
.secretary-sidebar-scroll::-webkit-scrollbar {
    width: 3px;
}
.secretary-sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.secretary-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
}
.secretary-sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.15);
}
.nav-collapse {
    will-change: max-height, opacity;
}
</style>
