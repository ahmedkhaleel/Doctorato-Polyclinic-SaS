<script setup>
import { ref, computed, watchEffect } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import DoctorNotificationBell from '@/Components/Doctor/DoctorNotificationBell.vue';
import ToastNotification from '@/Components/Doctor/ToastNotification.vue';
import ChatIcon from '@/Components/Chat/ChatIcon.vue';
import ChatToast from '@/Components/Chat/ChatToast.vue';

const page = usePage();
const sidebarOpen = ref(false);

/* ── Locale ────────────────────────────────────────────── */
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});

function t(key) {
    return translations.value[key] || key;
}

function switchLocale() {
    const newLocale = locale.value === 'ar' ? 'en' : 'ar';
    router.post('/doctor/switch-locale', { locale: newLocale }, { preserveState: false });
}

const modules = computed(() => page.props.modules || {});
const doctor = computed(() => page.props.auth?.doctor);
const userName = computed(() => doctor.value?.name_en || page.props.auth?.user?.name || 'Doctor');
const specialty = computed(() => isRtl.value ? (doctor.value?.specialization_ar || doctor.value?.specialization_en || 'طبيب') : (doctor.value?.specialization_en || 'Doctor'));
const currentUrl = computed(() => page.url);

/* ── Grouped Navigation ─────────────────────────────────── */
const navGroups = computed(() => [
    {
        title: isRtl.value ? 'الرئيسية' : 'Main',
        key: 'main',
        items: [
            { label: t('a_dashboard'),       href: '/doctor',              icon: 'grid' },
            { label: t('a_today_queue'),     href: '/doctor/queue',        icon: 'queue' },
        ],
    },
    {
        title: isRtl.value ? 'العيادة' : 'Clinical',
        key: 'clinical',
        items: [
            { label: t('a_patients'),        href: '/doctor/patients',     icon: 'heart' },
            { label: t('a_visits'),          href: '/doctor/visits',       icon: 'clipboard' },
            { label: t('a_prescriptions'),   href: '/doctor/prescriptions', icon: 'pill' },
            { label: t('a_bookings'),        href: '/doctor/bookings',     icon: 'calendar' },
        ],
    },
    {
        title: isRtl.value ? 'طب الأسنان' : 'Dental',
        key: 'dental',
        moduleKey: 'dental',
        items: [
            { label: isRtl.value ? 'مخطط الأسنان' : 'Dental Chart',   href: '/doctor/dental/chart-search', icon: 'tooth' },
            { label: isRtl.value ? 'خطط العلاج' : 'Treatment Plans', href: '/doctor/dental/treatment-plans', icon: 'clipboard' },
            { label: isRtl.value ? 'العلاجات' : 'Treatments',        href: '/doctor/dental/treatments',      icon: 'pill' },
            { label: isRtl.value ? 'الأشعة' : 'X-rays',             href: '/doctor/dental/xrays',           icon: 'camera' },
            { label: isRtl.value ? 'المتابعات' : 'Follow-ups',      href: '/doctor/dental/followups',       icon: 'clock' },
            { label: isRtl.value ? 'مقارنات قبل/بعد' : 'Comparisons', href: '/doctor/dental/comparisons',    icon: 'camera' },
        ],
    },
    {
        title: isRtl.value ? 'الموارد البشرية' : 'HR',
        key: 'hr',
        moduleKey: 'hr',
        items: [
            { label: t('a_commission'),      href: '/doctor/commission',      icon: 'cash' },
            { label: t('a_my_attendance'),   href: '/doctor/my-attendance',   icon: 'checklist' },
            { label: t('a_my_leaves'),       href: '/doctor/my-leaves',      icon: 'logout' },
            { label: t('a_my_salary_slips'), href: '/doctor/my-salary-slips', icon: 'salary' },
        ],
    },
    {
        title: isRtl.value ? 'حسابي' : 'My Account',
        key: 'account',
        items: [
            { label: t('a_chat'),            href: '/doctor/chat',         icon: 'chat' },
            { label: t('a_profile'),         href: '/doctor/profile',      icon: 'user' },
        ],
    },
]);

const doctorModule = computed(() => doctor.value?.module || 'derma');

const filteredGroups = computed(() =>
    navGroups.value.filter(g => {
        if (g.moduleKey) {
            // Module must be enabled system-wide
            if (modules.value[g.moduleKey]?.enabled !== true) return false;
            // Dental section only for dental doctors
            if (g.moduleKey === 'dental' && doctorModule.value !== 'dental') return false;
            return true;
        }
        return true;
    }).filter(g => g.items.length > 0)
);

/* ── Collapsible Groups State ──────────────────────────── */
const openGroups = ref(new Set());

function toggleGroup(title) {
    const newSet = new Set(openGroups.value);
    if (newSet.has(title)) {
        newSet.delete(title);
    } else {
        newSet.add(title);
    }
    openGroups.value = newSet;
}

function isGroupOpen(title) {
    return openGroups.value.has(title);
}

function isActive(href) {
    if (href === '/doctor') return currentUrl.value === '/doctor' || currentUrl.value === '/doctor/';
    return currentUrl.value.startsWith(href);
}

/* Auto-open group containing active route */
watchEffect(() => {
    const newSet = new Set(openGroups.value);
    navGroups.value.forEach((group) => {
        if (group.items.some(item => isActive(item.href))) {
            newSet.add(group.key);
        }
    });
    openGroups.value = newSet;
});

function toggleSidebar() { sidebarOpen.value = !sidebarOpen.value; }
function closeSidebar()  { sidebarOpen.value = false; }
function logout()        { router.post('/doctor/logout'); }
</script>

<template>
    <div :dir="dir" class="min-h-screen flex bg-[#f5f6fa]" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden" @click="closeSidebar"></div>

        <!-- ─── Sidebar ──────────────────────────────────────────── -->
        <aside
            :class="[sidebarOpen ? 'translate-x-0' : (isRtl ? 'translate-x-full' : '-translate-x-full'), 'lg:translate-x-0']"
            class="fixed inset-y-0 z-40 w-[260px] transition-transform duration-300 ease-in-out lg:static lg:z-auto flex flex-col bg-[#0f172a] shadow-2xl ltr:left-0 rtl:right-0"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-[72px] px-5 border-b border-white/[0.06]">
                <Link href="/doctor" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[15px] font-bold text-[#C4A265] tracking-wide">AURA</span>
                        <span class="block text-[10px] text-white/30 uppercase tracking-widest -mt-0.5">{{ isRtl ? 'بوابة الطبيب' : 'Doctor Portal' }}</span>
                    </div>
                </Link>
                <button class="lg:hidden text-white/40 hover:text-white p-1" @click="closeSidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Navigation Groups -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 doctor-sidebar-scroll">
                <div v-for="group in filteredGroups" :key="group.key">
                    <!-- Group Header - Clickable -->
                    <button
                        @click="toggleGroup(group.key)"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[11px] font-semibold uppercase tracking-[0.12em] transition-all duration-200 group/header"
                        :class="isGroupOpen(group.key) ? 'text-[#C4A265]/90 bg-white/[0.02]' : 'text-white/30 hover:text-white/50 hover:bg-white/[0.02]'"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-1.5 h-1.5 rounded-full transition-all duration-300"
                                :class="isGroupOpen(group.key) ? 'bg-[#C4A265] scale-100' : 'bg-white/20 scale-75'"
                            ></div>
                            <span>{{ group.title }}</span>
                        </div>
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-300 ease-out"
                            :class="isGroupOpen(group.key) ? 'rotate-0 text-[#C4A265]/60' : '-rotate-90 text-white/20'"
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
                                    ? 'bg-[#C4A265]/[0.12] text-[#D4B87A]'
                                    : 'text-white/50 hover:bg-white/[0.04] hover:text-white/80',
                            ]"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200"
                            @click="closeSidebar"
                        >
                            <!-- Icon wrapper -->
                            <div
                                :class="isActive(item.href) ? 'bg-[#C4A265]/20 text-[#D4B87A]' : 'bg-white/[0.04] text-white/40'"
                                class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 transition-colors duration-200"
                            >
                        <!-- Grid (Dashboard) -->
                        <svg v-if="item.icon === 'grid'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                        <!-- Queue -->
                        <svg v-else-if="item.icon === 'queue'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16" /><circle cx="2" cy="6" r="1" fill="currentColor" /><circle cx="2" cy="10" r="1" fill="currentColor" /><circle cx="2" cy="14" r="1" fill="currentColor" /><circle cx="2" cy="18" r="1" fill="currentColor" /></svg>
                        <!-- Heart (Patients) -->
                        <svg v-else-if="item.icon === 'heart'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        <!-- Clipboard (Visits) -->
                        <svg v-else-if="item.icon === 'clipboard'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        <!-- Pill (Prescriptions) -->
                        <svg v-else-if="item.icon === 'pill'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" /></svg>
                        <!-- Calendar (Bookings) -->
                        <svg v-else-if="item.icon === 'calendar'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <!-- Cash (Commission) -->
                        <svg v-else-if="item.icon === 'cash'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <!-- Checklist (Attendance) -->
                        <svg v-else-if="item.icon === 'checklist'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h.01M9 16h.01M12 12h3m-3 4h3" /></svg>
                        <!-- Logout (Leaves) -->
                        <svg v-else-if="item.icon === 'logout'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <!-- Salary -->
                        <svg v-else-if="item.icon === 'salary'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <!-- Chat -->
                        <svg v-else-if="item.icon === 'chat'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        <!-- User (Profile) -->
                        <svg v-else-if="item.icon === 'user'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <!-- Tooth -->
                        <svg v-else-if="item.icon === 'tooth'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
                        <!-- Camera (X-rays) -->
                        <svg v-else-if="item.icon === 'camera'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <!-- Clock (Follow-ups) -->
                        <svg v-else-if="item.icon === 'clock'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span>{{ item.label }}</span>
                            <!-- Active indicator dot -->
                            <span v-if="isActive(item.href)" class="ltr:ml-auto rtl:mr-auto w-1.5 h-1.5 rounded-full bg-[#C4A265]"></span>
                        </Link>
                    </div>
                    </div>
                </div>
            </nav>

            <!-- Sidebar footer — Doctor Card -->
            <div class="p-4 border-t border-white/[0.06]">
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/[0.04]">
                    <div v-if="doctor?.photo_url" class="w-9 h-9 rounded-lg overflow-hidden shadow-lg">
                        <img :src="doctor.photo_url" :alt="userName" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-[#C4A265]/10">
                        {{ userName.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-white/80 truncate">Dr. {{ userName }}</p>
                        <p class="text-[10px] text-white/35 truncate">{{ specialty }}</p>
                    </div>
                    <button
                        @click="logout"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-white/30 hover:text-red-400 hover:bg-red-500/10 transition-all duration-200"
                        :title="isRtl ? 'خروج' : 'Logout'"
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
                    <!-- Chat -->
                    <ChatIcon panelPrefix="doctor" accentColor="#C4A265" />

                    <!-- Notification Bell -->
                    <DoctorNotificationBell />

                    <!-- Divider -->
                    <div class="hidden lg:block w-px h-6 bg-gray-200"></div>

                    <!-- Doctor badge -->
                    <div class="flex items-center gap-2.5">
                        <div v-if="doctor?.photo_url" class="w-9 h-9 rounded-xl overflow-hidden shadow-md">
                            <img :src="doctor.photo_url" :alt="userName" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center text-white text-sm font-bold shadow-md shadow-[#C4A265]/15">
                            {{ userName.charAt(0).toUpperCase() }}
                        </div>
                        <div class="hidden sm:flex flex-col">
                            <span class="text-sm font-semibold text-gray-800 leading-tight">Dr. {{ userName }}</span>
                            <span class="text-[11px] text-gray-400 leading-tight">{{ specialty }}</span>
                        </div>
                    </div>

                    <!-- Language Switcher -->
                    <button
                        @click="switchLocale"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 border text-[#C4A265] border-[#C4A265]/30 hover:bg-[#C4A265]/10"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                        <span>{{ isRtl ? 'EN' : 'عربي' }}</span>
                    </button>

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
                           class="text-[#C4A265] hover:text-[#A68B52] font-semibold transition-colors duration-200 inline-flex items-center gap-1">
                            Markeza Group
                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        </a>
                    </p>
                </div>
            </footer>
        </div>

        <!-- Toast Notification -->
        <ToastNotification />

        <!-- Chat Toast Notifications -->
        <ChatToast panelPrefix="doctor" accentColor="#C4A265" />
    </div>
</template>

<style scoped>
.doctor-sidebar-scroll::-webkit-scrollbar {
    width: 3px;
}
.doctor-sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.doctor-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
}
.doctor-sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.15);
}
.nav-collapse {
    will-change: max-height, opacity;
}
</style>
