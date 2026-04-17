<script setup>
import { ref, computed, watch, watchEffect } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';

useTheme();

const props = defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const SIDEBAR_STORAGE_KEY = 'PatientLayout_sidebar_open_v2';
const getInitialSidebarState = () => {
    if (typeof window === 'undefined') return false;
    const stored = localStorage.getItem(SIDEBAR_STORAGE_KEY);
    if (stored !== null) return stored === 'true';
    return window.matchMedia('(min-width: 1024px)').matches;
};
const sidebarOpen = ref(getInitialSidebarState());
if (typeof window !== 'undefined') {
    watch(sidebarOpen, (v) => localStorage.setItem(SIDEBAR_STORAGE_KEY, String(v)));
}

/* ── Locale ────────────────────────────────────────────── */
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});

function t(key) {
    return translations.value[key] || key;
}

/* ── Locale-aware path helper ─────────────────────────── */
function lp(path) {
    return `/${locale.value}/patient${path}`;
}

function switchLocale() {
    const newLocale = locale.value === 'ar' ? 'en' : 'ar';
    // Replace locale segment in current URL
    const currentPath = page.url;
    const newPath = currentPath.replace(/^\/(ar|en)\//, `/${newLocale}/`);
    router.visit(newPath, { preserveState: false });
}

const modules = computed(() => page.props.modules || {});
const patient = computed(() => page.props.auth?.patient);
const userName = computed(() => patient.value?.full_name || 'Patient');
const fileNumber = computed(() => patient.value?.file_number || '');
const currentUrl = computed(() => page.url);

/* ── Grouped Navigation ─────────────────────────────────── */
const navGroups = computed(() => [
    {
        title: isRtl.value ? 'الرئيسية' : 'Main',
        key: 'main',
        items: [
            { label: t('p_dashboard'), href: lp(''), icon: 'grid' },
        ],
    },
    {
        title: isRtl.value ? 'رعايتي' : 'My Care',
        key: 'care',
        items: [
            { label: t('p_my_bookings'), href: lp('/bookings'), icon: 'calendar' },
            { label: t('p_my_visits'), href: lp('/visits'), icon: 'clipboard' },
            { label: t('p_my_photos'), href: lp('/photos'), icon: 'camera' },
            { label: t('p_my_prescriptions'), href: lp('/prescriptions'), icon: 'pill' },
            { label: t('p_my_treatment_plans'), href: lp('/treatment-plans'), icon: 'plan' },
        ],
    },
    {
        title: isRtl.value ? 'عن بُعد' : 'Telemedicine',
        key: 'telemedicine',
        moduleKey: 'telemedicine',
        items: [
            { label: isRtl.value ? 'الاستشارات الأونلاين' : 'Online Consultations', href: lp('/online-consultations'), icon: 'video' },
        ],
    },
    {
        title: isRtl.value ? 'طب الأسنان' : 'Dental',
        key: 'dental',
        moduleKey: 'dental',
        items: [
            { label: isRtl.value ? 'نظرة عامة' : 'Overview', href: lp('/dental/overview'), icon: 'overview' },
            { label: t('p_dental_chart'), href: lp('/dental/chart'), icon: 'tooth' },
            { label: t('p_dental_treatments'), href: lp('/dental/treatments'), icon: 'treatment' },
            { label: t('p_dental_xrays'), href: lp('/dental/xrays'), icon: 'xray' },
            { label: t('p_dental_plans'), href: lp('/dental/treatment-plans'), icon: 'plan' },
            { label: isRtl.value ? 'طلبات المعمل' : 'Lab Orders', href: lp('/dental/lab-orders'), icon: 'lab' },
            { label: isRtl.value ? 'المتابعات' : 'Follow-ups', href: lp('/dental/followups'), icon: 'followup' },
        ],
    },
    {
        title: isRtl.value ? 'المالية' : 'Financial',
        key: 'financial',
        items: [
            { label: t('p_my_invoices'), href: lp('/invoices'), icon: 'invoice' },
        ],
    },
    {
        title: isRtl.value ? 'حسابي' : 'Account',
        key: 'account',
        items: [
            { label: t('p_my_profile'), href: lp('/profile'), icon: 'user' },
        ],
    },
]);

const filteredGroups = computed(() => {
    return navGroups.value.filter(g => {
        if (g.moduleKey) {
            return modules.value[g.moduleKey]?.enabled === true;
        }
        return true;
    });
});

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
    const url = currentUrl.value;
    // Dashboard: exact match
    if (href === lp('')) return url === lp('') || url === lp('/');
    return url.startsWith(href);
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

/* ── Active page title ──────────────────────────────── */
const activePageTitle = computed(() => {
    for (const group of filteredGroups.value) {
        for (const item of group.items) {
            if (isActive(item.href)) return item.label;
        }
    }
    return '';
});

function toggleSidebar() { sidebarOpen.value = !sidebarOpen.value; }
function closeSidebar()  { sidebarOpen.value = false; }
/* Only auto-close on mobile. On desktop, sidebar stays open as user navigates. */
function closeSidebarOnMobile() {
    if (typeof window !== 'undefined' && !window.matchMedia('(min-width: 1024px)').matches) {
        sidebarOpen.value = false;
    }
}
function logout()        { router.post(lp('/logout')); }
</script>

<template>
    <div :dir="dir" class="min-h-screen flex bg-[#f5f6fa]" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden" @click="closeSidebar"></div>

        <!-- ─── Sidebar ──────────────────────────────────────────── -->
        <aside
            :class="[sidebarOpen ? 'translate-x-0' : (isRtl ? 'translate-x-full' : '-translate-x-full')]"
            class="fixed inset-y-0 z-40 w-[260px] transition-transform duration-300 ease-in-out flex flex-col brand-sidebar-bg shadow-2xl ltr:left-0 rtl:right-0"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-[72px] px-5 border-b border-white/[0.06]">
                <Link :href="lp('')" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[var(--brand-primary)] to-[var(--brand-secondary)] flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[15px] font-bold text-[var(--brand-primary)] tracking-wide">Doctorato</span>
                        <span class="block text-[10px] text-white/30 uppercase tracking-widest -mt-0.5">{{ isRtl ? 'بوابة المريض' : 'Patient Portal' }}</span>
                    </div>
                </Link>
                <button class="lg:hidden text-white/40 hover:text-white p-1" @click="closeSidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Back to Website -->
            <a :href="`/${locale}/`" class="flex items-center gap-2.5 mx-3 mt-3 px-3 py-2 rounded-lg text-white/30 hover:text-white/60 hover:bg-white/[0.04] transition-all duration-200 text-[12px]">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                {{ isRtl ? 'العودة للموقع' : 'Back to Website' }}
            </a>

            <!-- Navigation Groups -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 patient-sidebar-scroll">
                <div v-for="group in filteredGroups" :key="group.key">
                    <!-- Group Header -->
                    <button
                        @click="toggleGroup(group.key)"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[11px] font-semibold uppercase tracking-[0.12em] transition-all duration-200 group/header"
                        :class="isGroupOpen(group.key) ? 'text-[var(--brand-primary)]/90 bg-white/[0.02]' : 'text-white/30 hover:text-white/50 hover:bg-white/[0.02]'"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-1.5 h-1.5 rounded-full transition-all duration-300"
                                :class="isGroupOpen(group.key) ? 'bg-[var(--brand-primary)] scale-100' : 'bg-white/20 scale-75'"
                            ></div>
                            <span>{{ group.title }}</span>
                        </div>
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-300 ease-out"
                            :class="isGroupOpen(group.key) ? 'rotate-0 text-[var(--brand-primary)]/60' : '-rotate-90 text-white/20'"
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
                                    ? 'bg-[var(--brand-primary)]/[0.12] text-[var(--brand-secondary)]'
                                    : 'text-white/50 hover:bg-white/[0.04] hover:text-white/80',
                            ]"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200"
                            @click="closeSidebarOnMobile"
                        >
                            <!-- Icon wrapper -->
                            <div
                                :class="isActive(item.href) ? 'bg-[var(--brand-primary)]/20 text-[var(--brand-secondary)]' : 'bg-white/[0.04] text-white/40'"
                                class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 transition-colors duration-200"
                            >
                        <!-- Grid (Dashboard) -->
                        <svg v-if="item.icon === 'grid'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                        <!-- Calendar (Bookings) -->
                        <svg v-else-if="item.icon === 'calendar'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <!-- Clipboard (Visits) -->
                        <svg v-else-if="item.icon === 'clipboard'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        <!-- Camera (Photos) -->
                        <svg v-else-if="item.icon === 'camera'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0010.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <!-- Pill (Prescriptions) -->
                        <svg v-else-if="item.icon === 'pill'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" /></svg>
                        <!-- Plan (Treatment Plans) -->
                        <svg v-else-if="item.icon === 'plan'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        <!-- Invoice (reuse cash icon) -->
                        <svg v-else-if="item.icon === 'invoice'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <!-- Overview (Dashboard) -->
                        <svg v-else-if="item.icon === 'overview'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                        <!-- Tooth (Dental Chart) -->
                        <svg v-else-if="item.icon === 'tooth'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2C9.5 2 7 4 7 7c0 2-.5 4-1 6-.5 2-.5 4 0 5.5S7.5 21 8.5 21s1.5-1 2-3c.5-1.5 1-2 1.5-2s1 .5 1.5 2c.5 2 1 3 2 3s2-1 2.5-2.5.5-3.5 0-5.5c-.5-2-1-4-1-6 0-3-2.5-5-5-5z" /></svg>
                        <!-- Treatment -->
                        <svg v-else-if="item.icon === 'treatment'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                        <!-- X-ray -->
                        <svg v-else-if="item.icon === 'xray'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <svg v-else-if="item.icon === 'lab'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        <svg v-else-if="item.icon === 'followup'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <!-- User (Profile) -->
                        <svg v-else-if="item.icon === 'user'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <!-- Video (Online Consultations) -->
                        <svg v-else-if="item.icon === 'video'" class="w-[16px] h-[16px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </div>
                            <span>{{ item.label }}</span>
                            <!-- Active indicator dot -->
                            <span v-if="isActive(item.href)" class="ltr:ml-auto rtl:mr-auto w-1.5 h-1.5 rounded-full bg-[var(--brand-primary)]"></span>
                        </Link>
                    </div>
                    </div>
                </div>
            </nav>

            <!-- Sidebar footer — Patient Card -->
            <div class="p-4 border-t border-white/[0.06]">
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/[0.04]">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[var(--brand-primary)] to-[var(--brand-secondary)] flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-[var(--brand-primary)]/10">
                        {{ userName.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-white/80 truncate">{{ userName }}</p>
                        <p class="text-[10px] text-white/35 truncate">{{ fileNumber ? (isRtl ? 'ملف #' : 'File #') + fileNumber : '' }}</p>
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
        <div class="flex-1 flex flex-col min-h-screen min-w-0">
            <!-- Top Header -->
            <header class="h-[64px] md:h-[72px] bg-white/80 backdrop-blur-md border-b border-gray-200/60 flex items-center justify-between px-3 md:px-4 lg:px-8 sticky top-0 z-20 gap-2">
                <!-- Mobile hamburger -->
                <button
                    class="inline-flex items-center justify-center w-10 h-10 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0"
                    @click="toggleSidebar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <h2 class="hidden lg:block text-sm font-semibold text-gray-700">{{ activePageTitle }}</h2>

                <!-- Right side -->
                <div class="flex items-center gap-1.5 md:gap-3">
                    <!-- Patient badge -->
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[var(--brand-primary)] to-[var(--brand-secondary)] flex items-center justify-center text-white text-sm font-bold shadow-md shadow-[var(--brand-primary)]/15">
                            {{ userName.charAt(0).toUpperCase() }}
                        </div>
                    </div>

                    <!-- Language Switcher -->
                    <button
                        @click="switchLocale"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 border text-[var(--brand-primary)] border-[var(--brand-primary)]/30 hover:bg-[var(--brand-primary)]/10"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                        <span>{{ isRtl ? 'EN' : 'عربي' }}</span>
                    </button>
                </div>
            </header>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mx-4 lg:mx-8 mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="mx-4 lg:mx-8 mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                {{ $page.props.flash.error }}
            </div>

            <!-- Page content -->
            <main class="flex-1 p-4 md:p-6 lg:p-8 overflow-x-hidden">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-200/60 bg-white/60 backdrop-blur-sm py-3 px-4 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-1 text-[11px] text-gray-400">
                    <p>&copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة دكتوراتو التخصصية' : 'Doctorato Polyclinic' }}</p>
                    <p>
                        {{ isRtl ? 'تطوير' : 'Developed by' }}
                        <a href="https://markeza-group.com" target="_blank" rel="noopener noreferrer"
                           class="text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] font-semibold transition-colors duration-200 inline-flex items-center gap-1">
                            Markeza Group
                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        </a>
                    </p>
                </div>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.patient-sidebar-scroll::-webkit-scrollbar {
    width: 3px;
}
.patient-sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.patient-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
}
.patient-sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.15);
}
.nav-collapse {
    will-change: max-height, opacity;
}
</style>
