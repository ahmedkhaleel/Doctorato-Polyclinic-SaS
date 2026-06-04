<script setup>
import { ref, computed, watch, watchEffect } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions.js';
import ChatIcon from '@/Components/Chat/ChatIcon.vue';
import GlobalConfirmDialog from '@/Components/Admin/GlobalConfirmDialog.vue';
import ChatToast from '@/Components/Chat/ChatToast.vue';

const page = usePage();

/* ── Locale ────────────────────────────────────────────── */
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const SIDEBAR_STORAGE_KEY = 'WebmasterLayout_sidebar_open_v2';
const getInitialSidebarState = () => {
    if (typeof window === 'undefined') return false;
    const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
    // Mobile always starts CLOSED — overlay drawer shouldn't be open on page load.
    if (!isDesktop) return false;
    // Desktop: respect user's stored toggle preference; default open on first visit.
    const stored = localStorage.getItem(SIDEBAR_STORAGE_KEY);
    if (stored !== null) return stored === 'true';
    return true;
};
const sidebarOpen = ref(getInitialSidebarState());
if (typeof window !== 'undefined') {
    watch(sidebarOpen, (v) => localStorage.setItem(SIDEBAR_STORAGE_KEY, String(v)));
}
/* Auto-close sidebar if viewport shrinks to mobile (avoids leaked desktop state) */
if (typeof window !== 'undefined') {
    const mqlDesktop = window.matchMedia('(min-width: 1024px)');
    const onBreakpointChange = (e) => { if (!e.matches) sidebarOpen.value = false; };
    mqlDesktop.addEventListener ? mqlDesktop.addEventListener('change', onBreakpointChange) : mqlDesktop.addListener(onBreakpointChange);
}
const { can } = usePermissions();

const userName = computed(() => page.props.auth?.user?.name || 'Webmaster');
const userRole = computed(() => page.props.auth?.user?.role_display || page.props.auth?.user?.role || '');
const currentUrl = computed(() => page.url);

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

const navGroups = [
    {
        key: 'main', titleEn: 'Main', titleAr: 'الرئيسية',
        items: [
            { labelEn: 'Dashboard', labelAr: 'لوحة التحكم', href: '/webmaster', icon: 'grid', permission: null },
            { labelEn: 'Chat', labelAr: 'المحادثات', href: '/webmaster/chat', icon: 'chat', permission: null },
        ],
    },
    {
        key: 'content', titleEn: 'Content', titleAr: 'المحتوى',
        items: [
            { labelEn: 'Hero Slider', labelAr: 'السلايدر الرئيسي', href: '/webmaster/slider', icon: 'slides', permission: 'settings.view' },
            { labelEn: 'Services', labelAr: 'الخدمات', href: '/webmaster/services', icon: 'sparkles', permission: 'services.view' },
            { labelEn: 'Service Categories', labelAr: 'أقسام الخدمات', href: '/webmaster/service-categories', icon: 'folder', permission: 'service_categories.view' },
            { labelEn: 'Doctors', labelAr: 'الأطباء', href: '/webmaster/doctors', icon: 'user', permission: 'doctors.view' },
            { labelEn: 'Gallery', labelAr: 'معرض الصور', href: '/webmaster/gallery', icon: 'image', permission: 'gallery.view' },
            { labelEn: 'Testimonials', labelAr: 'آراء العملاء', href: '/webmaster/testimonials', icon: 'star', permission: 'testimonials.view' },
            { labelEn: 'FAQ', labelAr: 'الأسئلة الشائعة', href: '/webmaster/faqs', icon: 'question', permission: 'faqs.view' },
            { labelEn: 'Pages', labelAr: 'الصفحات', href: '/webmaster/pages', icon: 'file', permission: 'pages.view' },
        ],
    },
    {
        key: 'blog', titleEn: 'Blog', titleAr: 'المدونة',
        items: [
            { labelEn: 'Posts', labelAr: 'المقالات', href: '/webmaster/posts', icon: 'document', permission: 'posts.view' },
            { labelEn: 'Post Categories', labelAr: 'أقسام المقالات', href: '/webmaster/post-categories', icon: 'folder', permission: 'post_categories.view' },
            { labelEn: 'Tags', labelAr: 'الوسوم', href: '/webmaster/tags', icon: 'hashtag', permission: 'tags.view' },
        ],
    },
    {
        key: 'seo', titleEn: 'SEO & Analytics', titleAr: 'SEO والتحليلات',
        items: [
            { labelEn: 'SEO Pages', labelAr: 'صفحات SEO', href: '/webmaster/seo-pages', icon: 'search', permission: 'settings.view' },
            { labelEn: 'Tracking & Pixels', labelAr: 'التتبع والبكسل', href: '/webmaster/tracking', icon: 'code', permission: 'settings.view' },
        ],
    },
];

function navLabel(item) {
    return locale.value === 'ar' ? item.labelAr : item.labelEn;
}

function groupTitle(group) {
    return locale.value === 'ar' ? group.titleAr : group.titleEn;
}

const filteredGroups = computed(() =>
    navGroups
        .map(g => ({
            ...g,
            items: g.items.filter(i => !i.permission || can(i.permission)),
        }))
        .filter(g => g.items.length > 0)
);

function isActive(href) {
    if (href === '/webmaster') return currentUrl.value === '/webmaster' || currentUrl.value === '/webmaster/';
    return currentUrl.value.startsWith(href);
}

/* Auto-open group containing active route */
watchEffect(() => {
    const newSet = new Set(openGroups.value);
    filteredGroups.value.forEach((group) => {
        if (group.items.some(item => isActive(item.href))) {
            newSet.add(group.key || group.titleEn);
        }
    });
    openGroups.value = newSet;
});

function toggleSidebar() { sidebarOpen.value = !sidebarOpen.value; }
function closeSidebar()  { sidebarOpen.value = false; }
/* Only auto-close on mobile. On desktop, sidebar stays open as user navigates. */
function closeSidebarOnMobile() {
    if (typeof window !== 'undefined' && !window.matchMedia('(min-width: 1024px)').matches) {
        sidebarOpen.value = false;
    }
}
function logout()        { router.post('/webmaster/logout'); }

function switchLocale() {
    const newLocale = locale.value === 'ar' ? 'en' : 'ar';
    router.post('/admin/switch-locale', { locale: newLocale }, { preserveState: false });
}
</script>

<template>
    <div :dir="dir" class="min-h-screen bg-[#f5f6fa]" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="closeSidebar"></div>

        <!-- Sidebar -->
        <aside
            :class="[sidebarOpen ? 'translate-x-0' : (isRtl ? 'translate-x-full' : '-translate-x-full')]"
            class="fixed inset-y-0 ltr:left-0 rtl:right-0 z-40 w-[260px] transition-transform duration-300 ease-in-out flex flex-col bg-[#2D1B69] shadow-2xl"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-[72px] px-5 border-b border-white/[0.06]">
                <Link href="/webmaster" class="flex items-center">
                    <img
                        src="/images/logo/logo-light.png"
                        alt="Doctorato Polyclinic"
                        class="h-8 w-auto"
                    />
                </Link>
                <button class="lg:hidden text-white/40 hover:text-white p-1" @click="closeSidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Navigation Groups -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 webmaster-sidebar-scroll">
                <div v-for="group in filteredGroups" :key="group.key || group.titleEn">
                    <!-- Group Header - Clickable -->
                    <button
                        @click="toggleGroup(group.key || group.titleEn)"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[11px] font-semibold uppercase tracking-[0.12em] transition-all duration-200 group/header"
                        :class="isGroupOpen(group.key || group.titleEn) ? 'text-[#C4B5FD]/90 bg-white/[0.02]' : 'text-white/30 hover:text-white/50 hover:bg-white/[0.02]'"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-1.5 h-1.5 rounded-full transition-all duration-300"
                                :class="isGroupOpen(group.key || group.titleEn) ? 'bg-[#7C3AED] scale-100' : 'bg-white/20 scale-75'"
                            ></div>
                            <span>{{ groupTitle(group) }}</span>
                        </div>
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-300 ease-out"
                            :class="isGroupOpen(group.key || group.titleEn) ? 'rotate-0 text-[#7C3AED]/60' : (isRtl ? 'rotate-90 text-white/20' : '-rotate-90 text-white/20')"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <!-- Items with collapse animation -->
                    <div
                        class="nav-collapse overflow-hidden transition-all duration-300 ease-in-out"
                        :style="{
                            maxHeight: isGroupOpen(group.key || group.titleEn) ? (group.items.length * 44 + 8) + 'px' : '0px',
                            opacity: isGroupOpen(group.key || group.titleEn) ? 1 : 0,
                        }"
                    >
                    <div class="space-y-0.5 pt-1 ltr:pl-2 rtl:pr-2">
                        <Link
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                isActive(item.href)
                                    ? 'bg-[#7C3AED]/[0.2] text-[#C4B5FD]'
                                    : 'text-white/50 hover:bg-white/[0.04] hover:text-white/80',
                            ]"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200"
                            @click="closeSidebarOnMobile"
                        >
                            <div
                                :class="isActive(item.href) ? 'bg-[#7C3AED]/30 text-[#C4B5FD]' : 'bg-white/[0.04] text-white/40'"
                                class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 transition-colors duration-200"
                            >
                                <!-- Grid -->
                                <svg v-if="item.icon === 'grid'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                                <!-- Slides -->
                                <svg v-else-if="item.icon === 'slides'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5zm14.25-11.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                                <!-- Sparkles -->
                                <svg v-else-if="item.icon === 'sparkles'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                <!-- Folder -->
                                <svg v-else-if="item.icon === 'folder'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                                <!-- Tag -->
                                <svg v-else-if="item.icon === 'tag'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                <!-- User -->
                                <svg v-else-if="item.icon === 'user'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                <!-- Image -->
                                <svg v-else-if="item.icon === 'image'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <!-- Star -->
                                <svg v-else-if="item.icon === 'star'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                                <!-- Question -->
                                <svg v-else-if="item.icon === 'question'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- File -->
                                <svg v-else-if="item.icon === 'file'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                <!-- Document -->
                                <svg v-else-if="item.icon === 'document'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <!-- Hashtag -->
                                <svg v-else-if="item.icon === 'hashtag'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
                                <!-- Search -->
                                <svg v-else-if="item.icon === 'search'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                <!-- Code -->
                                <svg v-else-if="item.icon === 'code'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                <!-- Chat -->
                                <svg v-else-if="item.icon === 'chat'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                            </div>
                            <span>{{ navLabel(item) }}</span>
                            <span v-if="isActive(item.href)" class="ltr:ml-auto rtl:mr-auto w-1.5 h-1.5 rounded-full bg-[#7C3AED]"></span>
                        </Link>
                    </div>
                    </div>
                </div>
            </nav>

            <!-- Sidebar footer -->
            <div class="p-4 border-t border-white/[0.06]">
                <a
                    href="/"
                    target="_blank"
                    class="flex items-center gap-2 px-3 py-2 mb-2 rounded-lg text-[12px] font-medium text-white/40 hover:text-[#C4B5FD] hover:bg-white/[0.04] transition-all duration-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    <span>{{ isRtl ? 'زيارة الموقع' : 'Visit Website' }}</span>
                </a>
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/[0.04]">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#7C3AED] to-[#A78BFA] flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-[#7C3AED]/10">
                        {{ userName.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-white/80 truncate">{{ userName }}</p>
                        <p class="text-[10px] text-white/35 capitalize truncate">{{ userRole }}</p>
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

        <!-- Main Content -->
        <div
            :class="sidebarOpen ? 'lg:ps-[260px]' : ''"
            class="min-h-screen flex flex-col transition-[padding] duration-300 ease-in-out"
        >
            <!-- Top Header -->
            <header class="h-[64px] md:h-[72px] bg-white/80 backdrop-blur-md border-b border-gray-200/60 flex items-center justify-between px-3 md:px-4 lg:px-8 sticky top-0 z-20 gap-2">
                <button
                    class="inline-flex items-center justify-center w-10 h-10 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0"
                    @click="toggleSidebar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="hidden lg:block"></div>
                <div class="flex items-center gap-1.5 md:gap-3">
                    <ChatIcon panelPrefix="webmaster" accentColor="#7C3AED" />
                    <button @click="switchLocale" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 border text-[#C4A265] border-[#C4A265]/30 hover:bg-[#C4A265]/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                        <span>{{ isRtl ? 'EN' : 'عربي' }}</span>
                    </button>
                    <a
                        href="/"
                        target="_blank"
                        class="hidden lg:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500 hover:text-[#7C3AED] hover:bg-[#7C3AED]/5 transition-all duration-200"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        <span>{{ isRtl ? 'زيارة الموقع' : 'Visit Site' }}</span>
                    </a>
                    <div class="hidden lg:block w-px h-6 bg-gray-200"></div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#7C3AED] to-[#A78BFA] flex items-center justify-center text-white text-sm font-bold shadow-md shadow-[#7C3AED]/15">
                            {{ userName.charAt(0).toUpperCase() }}
                        </div>
                        <div class="hidden sm:flex flex-col">
                            <span class="text-sm font-semibold text-gray-800 leading-tight">{{ userName }}</span>
                            <span class="text-[11px] text-gray-400 leading-tight capitalize">{{ userRole }}</span>
                        </div>
                    </div>
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
            <main class="flex-1 p-4 md:p-6 lg:p-8 overflow-x-hidden">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-200/60 bg-white/60 backdrop-blur-sm py-3 px-4 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-1 text-[11px] text-gray-400">
                    <p>&copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة دكتوراتو التخصصية' : 'Doctorato Polyclinic' }}</p>
                    <p>
                        {{ isRtl ? 'تطوير بواسطة' : 'Developed by' }}
                        <a href="https://wa.me/971557961688" target="_blank" rel="noopener noreferrer"
                           class="text-[#7C3AED] hover:text-[#6D28D9] font-semibold transition-colors duration-200 inline-flex items-center gap-1">
                            Markeza Group
                            <svg class="w-3 h-3 opacity-50" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                    </p>
                </div>
            </footer>
        </div>
        <!-- Chat Toast Notifications -->
        <ChatToast panelPrefix="webmaster" accentColor="#7C3AED" />
        <GlobalConfirmDialog />
    </div>
</template>

<style scoped>
.webmaster-sidebar-scroll::-webkit-scrollbar {
    width: 3px;
}
.webmaster-sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.webmaster-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
}
.webmaster-sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.15);
}
.nav-collapse {
    will-change: max-height, opacity;
}
</style>
