<script setup>
import { ref, computed, watchEffect, nextTick } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useTheme } from '@/Composables/useTheme';

useTheme();
import NotificationBell from '@/Components/Admin/NotificationBell.vue';
import AdminToastNotification from '@/Components/Admin/AdminToastNotification.vue';
import CommandPalette from '@/Components/Admin/CommandPalette.vue';
import ChatIcon from '@/Components/Chat/ChatIcon.vue';
import ChatToast from '@/Components/Chat/ChatToast.vue';

const page = usePage();
const sidebarOpen = ref(false);
const { can } = usePermissions();

const adminName = computed(() => page.props.auth?.user?.name || 'Admin');
const adminRole = computed(() => page.props.auth?.user?.role_display || page.props.auth?.user?.role || '');
const currentUrl = computed(() => page.url);
const modules = computed(() => page.props.modules || {});

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
    router.post('/admin/switch-locale', { locale: newLocale }, { preserveState: false });
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

/* ── Grouped Navigation ─────────────────────────────────── */
const navGroups = [
    {
        key: 'main', titleEn: 'Main', titleAr: 'الرئيسية',
        items: [
            { labelEn: 'Dashboard',   labelAr: 'لوحة التحكم',   href: '/admin',                 icon: 'grid',      permission: null },
            { labelEn: 'Calendar',    labelAr: 'التقويم',       href: '/admin/calendar',        icon: 'calendarView', permission: 'visits.view' },
            { labelEn: 'Bookings',    labelAr: 'الحجوزات',      href: '/admin/bookings',        icon: 'calendar',   permission: 'bookings.view' },
            { labelEn: 'Messages',    labelAr: 'الرسائل',       href: '/admin/contact-messages', icon: 'envelope',  permission: 'contact_messages.view' },
            { labelEn: 'Chat',        labelAr: 'المحادثات',     href: '/admin/chat',            icon: 'chat',      permission: null },
        ],
    },
    {
        key: 'clinic', titleEn: 'Clinic', titleAr: 'العيادة',
        items: [
            { labelEn: 'Patients',         labelAr: 'المرضى',           href: '/admin/patients',            icon: 'heart',     permission: 'patients.view' },
            { labelEn: 'Today Queue',      labelAr: 'طابور اليوم',      href: '/admin/visits/today-queue',  icon: 'queue',     permission: 'visits.view' },
            { labelEn: 'Visits',           labelAr: 'الزيارات',         href: '/admin/visits',              icon: 'clipboard', permission: 'visits.view' },
            { labelEn: 'Doctors',          labelAr: 'الأطباء',          href: '/admin/doctors',             icon: 'user',         permission: 'doctors.view' },
            { labelEn: 'Doctor Schedules', labelAr: 'جداول الأطباء',    href: '/admin/schedules',           icon: 'calendarView', permission: 'doctors.view' },
            { labelEn: 'Med. Certificates', labelAr: 'الشهادات الطبية', href: '/admin/medical-certificates', icon: 'document',    permission: 'visits.view' },
            { labelEn: 'Reminders',         labelAr: 'التذكيرات',       href: '/admin/appointment-reminders', icon: 'bellCenter', permission: 'visits.view' },
            { labelEn: 'Bundle Packages',  labelAr: 'باقات الخدمات',    href: '/admin/package-bundles',     icon: 'bundle',    permission: 'package_bundles.view' },
            { labelEn: 'Bundle Bookings',  labelAr: 'حجوزات الباقات',   href: '/admin/package-bundle-bookings', icon: 'bundleBooking', permission: 'package_bundle_bookings.view' },
            { labelEn: 'Prescriptions',    labelAr: 'الوصفات الطبية',   href: '/admin/prescriptions',       icon: 'pill',      permission: 'prescriptions.view' },
            { labelEn: 'Medications',      labelAr: 'الأدوية',          href: '/admin/medications',         icon: 'medication', permission: 'medications.view' },
        ],
    },
    {
        key: 'finance', titleEn: 'Finance', titleAr: 'المالية',
        items: [
            { labelEn: 'Invoices',        labelAr: 'الفواتير',         href: '/admin/invoices',           icon: 'receipt',   permission: 'invoices.view' },
            { labelEn: 'Payments',        labelAr: 'المدفوعات',        href: '/admin/payments',           icon: 'cash',      permission: 'payments.view' },
            { labelEn: 'Discount Codes',  labelAr: 'أكواد الخصم',      href: '/admin/discount-codes',     icon: 'percent',   permission: 'discount_codes.view' },
            { labelEn: 'Expenses',          labelAr: 'المصروفات',        href: '/admin/expenses',             icon: 'wallet',    permission: 'expenses.view' },
            { labelEn: 'Expense Categories', labelAr: 'تصنيفات المصروفات', href: '/admin/expense-categories', icon: 'folder',    permission: 'expenses.view' },
            { labelEn: 'Doctor Payouts',  labelAr: 'مستحقات الأطباء',  href: '/admin/doctor-payouts',     icon: 'payout',    permission: 'doctor_payouts.view', moduleKey: 'hr' },
            { labelEn: 'Credit Notes',   labelAr: 'إشعارات دائنة',   href: '/admin/credit-notes',       icon: 'creditNote', permission: 'invoices.view' },
            { labelEn: 'Patient Wallets', labelAr: 'محافظ المرضى',   href: '/admin/wallets',            icon: 'wallet',     permission: 'payments.view' },
        ],
    },
    {
        key: 'inventory', titleEn: 'Inventory', titleAr: 'المخزون', moduleKey: 'inventory',
        items: [
            { labelEn: 'Overview',         labelAr: 'نظرة عامة',      href: '/admin/inventory',          icon: 'chartPie',  permission: 'supplies.view' },
            { labelEn: 'Products',         labelAr: 'المنتجات',       href: '/admin/supplies',           icon: 'box',       permission: 'supplies.view' },
            { labelEn: 'Categories',       labelAr: 'التصنيفات',      href: '/admin/supply-categories',  icon: 'layers',    permission: 'supplies.view' },
            { labelEn: 'Suppliers',        labelAr: 'الموردين',       href: '/admin/suppliers',          icon: 'truck',     permission: 'supplies.view' },
            { labelEn: 'Purchase Orders',  labelAr: 'أوامر الشراء',   href: '/admin/purchase-orders',    icon: 'clipboard', permission: 'supplies.view' },
        ],
    },
    {
        key: 'crm', titleEn: 'CRM', titleAr: 'إدارة العملاء',
        items: [
            { labelEn: 'CRM Dashboard',    labelAr: 'لوحة CRM',          href: '/admin/crm',              icon: 'crm',       permission: 'leads.view' },
            { labelEn: 'Leads',            labelAr: 'العملاء المحتملين',  href: '/admin/leads',            icon: 'funnel',    permission: 'leads.view' },
            { labelEn: 'Pipeline',         labelAr: 'مسار المبيعات',     href: '/admin/leads/pipeline',   icon: 'pipeline',  permission: 'leads.view' },
            { labelEn: 'Campaigns',        labelAr: 'الحملات',           href: '/admin/campaigns',        icon: 'megaphone', permission: 'crm_campaigns.view' },
            { labelEn: 'Lead Sources',     labelAr: 'مصادر العملاء',     href: '/admin/lead-sources',     icon: 'source',    permission: 'lead_sources.view' },
            { labelEn: 'Templates',        labelAr: 'القوالب',           href: '/admin/templates',        icon: 'document',  permission: 'communication_templates.view' },
            { labelEn: 'Commissions',      labelAr: 'العمولات',          href: '/admin/commissions',      icon: 'cash',      permission: 'marketer_commissions.view' },
            { labelEn: 'Automation',       labelAr: 'الأتمتة',           href: '/admin/sequences',        icon: 'automation', permission: 'leads.view' },
            { labelEn: 'Scoring Rules',    labelAr: 'قواعد التقييم',     href: '/admin/scoring-rules',    icon: 'star',      permission: 'leads.view' },
            { labelEn: 'Assignment Rules', labelAr: 'قواعد التعيين',     href: '/admin/assignment-rules',  icon: 'source',   permission: 'leads.view' },
            { labelEn: 'Import Leads',     labelAr: 'استيراد العملاء',   href: '/admin/leads-import',     icon: 'upload',    permission: 'leads.create' },
            { labelEn: 'CRM Reports',      labelAr: 'تقارير CRM',        href: '/admin/crm-reports',      icon: 'chart',     permission: 'leads.view' },
        ],
    },
    {
        key: 'dental', titleEn: 'Dental', titleAr: 'الأسنان', moduleKey: 'dental',
        items: [
            { labelEn: 'Dental Dashboard',   labelAr: 'لوحة الأسنان',      href: '/admin/dental',                    icon: 'grid',      permission: null },
            { labelEn: 'Dental Chart',       labelAr: 'مخطط الأسنان',      href: '/admin/dental/chart-search',       icon: 'tooth',     permission: null },
            { labelEn: 'Treatment Plans',    labelAr: 'خطط العلاج',        href: '/admin/dental/treatment-plans',    icon: 'clipboard', permission: null },
            { labelEn: 'Treatments',         labelAr: 'العلاجات',          href: '/admin/dental/treatments',         icon: 'sparkles',  permission: null },
            { labelEn: 'X-rays',             labelAr: 'الأشعة',            href: '/admin/dental/xrays',              icon: 'image',     permission: null },
            { labelEn: 'Lab Orders',         labelAr: 'طلبات المعمل',      href: '/admin/dental/lab-orders',         icon: 'box',       permission: null },
            { labelEn: 'Lab Dashboard',      labelAr: 'لوحة المعمل',       href: '/admin/dental/lab-orders/dashboard', icon: 'activity', permission: null },
            { labelEn: 'Rx Templates',      labelAr: 'قوالب الوصفات',     href: '/admin/dental/prescription-templates', icon: 'file-text', permission: null },
        ],
    },
    {
        key: 'hr', titleEn: 'HR', titleAr: 'الموارد البشرية', moduleKey: 'hr',
        items: [
            { labelEn: 'HR Dashboard',  labelAr: 'لوحة HR',         href: '/admin/hr-dashboard',       icon: 'hrDash',    permission: 'employees.view' },
            { labelEn: 'Employees',     labelAr: 'الموظفين',        href: '/admin/employees',          icon: 'users',     permission: 'employees.view' },
            { labelEn: 'Departments',   labelAr: 'الأقسام',         href: '/admin/departments',        icon: 'building',  permission: 'departments.view' },
            { labelEn: 'Payroll',       labelAr: 'كشوف المرتبات',   href: '/admin/payroll',            icon: 'salary',    permission: 'salary_slips.view' },
            { labelEn: 'Advances',      labelAr: 'السلف',           href: '/admin/advances',           icon: 'advance',   permission: 'advances.view' },
            { labelEn: 'Penalties',     labelAr: 'الجزاءات',        href: '/admin/penalties',          icon: 'penalty',   permission: 'penalties.view' },
            { labelEn: 'Shifts',        labelAr: 'الورديات',        href: '/admin/shifts',             icon: 'clock',     permission: 'shifts.view' },
            { labelEn: 'Attendance',    labelAr: 'الحضور والانصراف', href: '/admin/attendances',        icon: 'checklist', permission: 'attendances.view' },
            { labelEn: 'Leaves',        labelAr: 'الإجازات',        href: '/admin/leaves',             icon: 'logout',    permission: 'leaves.view' },
        ],
    },
    {
        key: 'website', titleEn: 'Website', titleAr: 'الموقع',
        items: [
            { labelEn: 'Hero Slider',        labelAr: 'السلايدر الرئيسي',  href: '/admin/slider',             icon: 'slides',   permission: 'settings.view' },
            { labelEn: 'Services',           labelAr: 'الخدمات',           href: '/admin/services',           icon: 'sparkles', permission: 'services.view' },
            { labelEn: 'Service Categories', labelAr: 'أقسام الخدمات',     href: '/admin/service-categories', icon: 'folder',   permission: 'service_categories.view' },
            { labelEn: 'Gallery',            labelAr: 'معرض الصور',        href: '/admin/gallery',            icon: 'image',    permission: 'gallery.view' },
            { labelEn: 'Testimonials',       labelAr: 'آراء العملاء',      href: '/admin/testimonials',       icon: 'star',     permission: 'testimonials.view' },
            { labelEn: 'FAQ',                labelAr: 'الأسئلة الشائعة',   href: '/admin/faqs',               icon: 'question', permission: 'faqs.view' },
            { labelEn: 'Pages',              labelAr: 'الصفحات',           href: '/admin/pages',              icon: 'file',     permission: 'pages.view' },
            { labelEn: 'Posts',              labelAr: 'المقالات',          href: '/admin/posts',              icon: 'document', permission: 'posts.view' },
            { labelEn: 'Post Categories',    labelAr: 'أقسام المقالات',    href: '/admin/post-categories',    icon: 'folder',   permission: 'post_categories.view' },
            { labelEn: 'Tags',               labelAr: 'الوسوم',           href: '/admin/tags',               icon: 'hashtag',  permission: 'tags.view' },
            { labelEn: 'SEO Pages',          labelAr: 'صفحات SEO',        href: '/admin/seo-pages',          icon: 'search',   permission: 'settings.view' },
            { labelEn: 'Tracking & Pixels',  labelAr: 'التتبع والبكسل',   href: '/admin/tracking',           icon: 'code',     permission: 'settings.view' },
        ],
    },
    {
        key: 'insurance', titleEn: 'Insurance', titleAr: 'التأمين', moduleKey: 'insurance',
        items: [
            { labelEn: 'Companies',      labelAr: 'شركات التأمين',  href: '/admin/insurance/companies', icon: 'building',  permission: 'settings.view' },
            { labelEn: 'Claims',         labelAr: 'المطالبات',      href: '/admin/insurance/claims',    icon: 'clipboard', permission: 'invoices.view' },
            { labelEn: 'Reports',        labelAr: 'التقارير',       href: '/admin/insurance/reports',   icon: 'chart',    permission: 'invoices.view' },
        ],
    },
    {
        key: 'quality', titleEn: 'Quality', titleAr: 'الجودة',
        items: [
            { labelEn: 'Satisfaction',   labelAr: 'رضا المرضى',    href: '/admin/satisfaction',        icon: 'star',     permission: 'reports.view' },
            { labelEn: 'Referrals',      labelAr: 'التحويلات',     href: '/admin/referrals',           icon: 'switch',   permission: 'visits.view' },
        ],
    },
    {
        key: 'system', titleEn: 'System', titleAr: 'النظام',
        items: [
            { labelEn: 'Reports',        labelAr: 'التقارير',       href: '/admin/reports',            icon: 'chart',    permission: 'reports.view' },
            { labelEn: 'Revenue Analytics', labelAr: 'تحليل الإيرادات', href: '/admin/reports/revenue-analytics', icon: 'chartUp', permission: 'reports.view' },
            { labelEn: 'Queue Analytics',   labelAr: 'تحليل الانتظار',  href: '/admin/reports/queue-analytics',   icon: 'queue',   permission: 'reports.view' },
            { labelEn: 'Doctor KPIs',      labelAr: 'أداء الأطباء',    href: '/admin/reports/doctor-kpi',        icon: 'user',    permission: 'reports.view', moduleKey: 'hr' },
            { labelEn: 'Staff Performance', labelAr: 'أداء الموظفين', href: '/admin/reports/staff-performance', icon: 'users',   permission: 'reports.view', moduleKey: 'hr' },
            { labelEn: 'Financial Report', labelAr: 'التقرير المالي', href: '/admin/reports/financial',          icon: 'receipt', permission: 'reports.view' },
            { labelEn: 'Notifications',   labelAr: 'مركز الإشعارات', href: '/admin/notification-center', icon: 'bellCenter', permission: null },
            { labelEn: 'Activity Logs',  labelAr: 'سجل النشاطات',  href: '/admin/activity-logs',      icon: 'activity', permission: 'reports.view' },
            { labelEn: 'Medical Logs',   labelAr: 'سجل الوصول الطبي', href: '/admin/medical-access-logs', icon: 'shield', permission: 'patients.view_sensitive_medical' },
            { labelEn: 'Users',          labelAr: 'المستخدمين',    href: '/admin/users',              icon: 'users',    permission: 'users.view' },
            { labelEn: 'Roles',          labelAr: 'الصلاحيات',     href: '/admin/roles',              icon: 'shield',   permission: 'roles.view' },
            { labelEn: 'Settings',       labelAr: 'الإعدادات',     href: '/admin/settings',           icon: 'cog',      permission: 'settings.view' },
            { labelEn: 'Modules',        labelAr: 'المديولات',     href: '/admin/settings/modules',   icon: 'layers',   permission: 'settings.view' },
            { labelEn: 'Recycle Bin',    labelAr: 'سلة المحذوفات', href: '/admin/trash',              icon: 'trash',    permission: 'settings.update' },
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
        .filter(g => {
            // If group has a moduleKey, only show if that module is enabled
            if (g.moduleKey) {
                return modules.value[g.moduleKey]?.enabled === true;
            }
            return true;
        })
        .map(g => ({
            ...g,
            items: g.items.filter(i => {
                if (i.moduleKey && modules.value[i.moduleKey]?.enabled !== true) return false;
                if (i.permission && !can(i.permission)) return false;
                return true;
            }),
        }))
        .filter(g => g.items.length > 0)
);

function isActive(href) {
    if (href === '/admin') return currentUrl.value === '/admin' || currentUrl.value === '/admin/';
    return currentUrl.value.startsWith(href);
}

/* Auto-open the group containing the active route */
watchEffect(() => {
    const newSet = new Set(openGroups.value);
    filteredGroups.value.forEach((group) => {
        if (group.items.some(item => isActive(item.href))) {
            newSet.add(group.key);
        }
    });
    openGroups.value = newSet;
});

function toggleSidebar() { sidebarOpen.value = !sidebarOpen.value; }
function closeSidebar()  { sidebarOpen.value = false; }
function logout()        { router.post('/admin/logout'); }
</script>

<template>
    <div :dir="dir" class="min-h-screen flex bg-[#f5f6fa]" :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden" @click="closeSidebar"></div>

        <!-- ─── Sidebar ──────────────────────────────────────────── -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : (isRtl ? 'translate-x-full' : '-translate-x-full'),
                'lg:translate-x-0'
            ]"
            class="fixed inset-y-0 z-40 w-[260px] transition-transform duration-300 ease-in-out lg:static lg:z-auto flex flex-col brand-sidebar-bg shadow-2xl ltr:left-0 rtl:right-0"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-[72px] px-5 border-b border-white/[0.06]">
                <Link href="/admin" class="flex items-center">
                    <img
                        src="/images/logo/logo.png"
                        alt="AURA Derma Aesthetic Clinic"
                        class="h-12 w-auto brightness-0 invert opacity-90"
                    />
                </Link>
                <button class="lg:hidden text-white/40 hover:text-white p-1" @click="closeSidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Navigation Groups -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 admin-sidebar-scroll">
                <div v-for="group in filteredGroups" :key="group.key">
                    <!-- Group Header - Clickable -->
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
                            <span>{{ groupTitle(group) }}</span>
                            <span v-if="group.key === 'crm' && page.props.notifications?.crm_overdue_count > 0"
                                class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[9px] font-bold text-white bg-red-500 rounded-full animate-pulse"
                            >{{ page.props.notifications.crm_overdue_count }}</span>
                        </div>
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-300 ease-out"
                            :class="[
                                isGroupOpen(group.key) ? 'rotate-0 text-[var(--brand-primary)]/60' : 'text-white/20',
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
                                    ? 'bg-[var(--brand-primary)]/[0.12] text-[var(--brand-secondary)]'
                                    : 'text-white/50 hover:bg-white/[0.04] hover:text-white/80',
                            ]"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200"
                            @click="closeSidebar"
                        >
                            <!-- Icon wrapper -->
                            <div
                                :class="isActive(item.href) ? 'bg-[var(--brand-primary)]/20 text-[var(--brand-secondary)]' : 'bg-white/[0.04] text-white/40'"
                                class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 transition-colors duration-200"
                            >
                                <!-- Grid -->
                                <svg v-if="item.icon === 'grid'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                                <!-- Calendar -->
                                <svg v-else-if="item.icon === 'calendar'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <!-- Envelope -->
                                <svg v-else-if="item.icon === 'envelope'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                <!-- Chat -->
                                <svg v-else-if="item.icon === 'chat'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                <!-- Slides (Hero Slider) -->
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
                                <!-- Users -->
                                <svg v-else-if="item.icon === 'users'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                <!-- Shield -->
                                <svg v-else-if="item.icon === 'shield'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                <!-- Cog -->
                                <svg v-else-if="item.icon === 'cog'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <!-- Heart (Patients) -->
                                <svg v-else-if="item.icon === 'heart'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                <!-- Queue (Today Queue) -->
                                <svg v-else-if="item.icon === 'queue'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16" /><circle cx="2" cy="6" r="1" fill="currentColor" /><circle cx="2" cy="10" r="1" fill="currentColor" /><circle cx="2" cy="14" r="1" fill="currentColor" /><circle cx="2" cy="18" r="1" fill="currentColor" /></svg>
                                <!-- Clipboard (Visits) -->
                                <svg v-else-if="item.icon === 'clipboard'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                <!-- Package -->
                                <svg v-else-if="item.icon === 'package'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                <!-- Bundle (Bundle Packages) -->
                                <svg v-else-if="item.icon === 'bundle'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                <!-- BundleBooking (Bundle Bookings) -->
                                <svg v-else-if="item.icon === 'bundleBooking'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11v6m-3-3h6" /></svg>
                                <!-- Receipt (Invoices) -->
                                <svg v-else-if="item.icon === 'receipt'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                                <!-- Cash (Payments) -->
                                <svg v-else-if="item.icon === 'cash'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                <!-- Percent (Discount Codes) -->
                                <svg v-else-if="item.icon === 'percent'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 8a1 1 0 11-2 0 1 1 0 012 0zm8 8a1 1 0 11-2 0 1 1 0 012 0zm-1-9L8 17" /></svg>
                                <!-- Wallet (Expenses) -->
                                <svg v-else-if="item.icon === 'wallet'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2v-4M3 10v4m18-4v4m-4-2h.01" /></svg>
                                <!-- Payout (Doctor Payouts) -->
                                <svg v-else-if="item.icon === 'payout'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                                <!-- Credit Note -->
                                <svg v-else-if="item.icon === 'creditNote'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM16 3l-4 4-4-4" /></svg>
                                <!-- Box (Supplies) -->
                                <svg v-else-if="item.icon === 'box'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0v10l-8 4-8-4V7m16 0l-8 4m0 10V11m0 0L4 7" /></svg>
                                <!-- ChartPie (Inventory Overview) -->
                                <svg v-else-if="item.icon === 'chartPie'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                                <!-- Layers (Categories) -->
                                <svg v-else-if="item.icon === 'layers'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" /></svg>
                                <!-- HR Dashboard -->
                                <svg v-else-if="item.icon === 'hrDash'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                <!-- Building (Departments) -->
                                <svg v-else-if="item.icon === 'building'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <!-- Salary (Payroll) -->
                                <svg v-else-if="item.icon === 'salary'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- Advance -->
                                <svg v-else-if="item.icon === 'advance'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                                <!-- Penalty -->
                                <svg v-else-if="item.icon === 'penalty'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                <!-- Clock (Shifts) -->
                                <svg v-else-if="item.icon === 'clock'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- Checklist (Attendance) -->
                                <svg v-else-if="item.icon === 'checklist'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h.01M9 16h.01M12 12h3m-3 4h3" /></svg>
                                <!-- Pill (Prescriptions) -->
                                <svg v-else-if="item.icon === 'pill'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" /></svg>
                                <!-- Medication (Medications) -->
                                <svg v-else-if="item.icon === 'medication'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 6v3m0 0v3m0-3h3m-3 0H7.5M6 20.25h12A2.25 2.25 0 0020.25 18V6.75A2.25 2.25 0 0018 4.5H6A2.25 2.25 0 003.75 6.75v11.25A2.25 2.25 0 006 20.25z" /></svg>
                                <!-- Chart (Reports) -->
                                <svg v-else-if="item.icon === 'chart'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                <!-- Chart Up (Revenue Analytics) -->
                                <svg v-else-if="item.icon === 'chartUp'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                <!-- Bell Center (Notification Center) -->
                                <svg v-else-if="item.icon === 'bellCenter'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                <!-- Activity (Activity Logs) -->
                                <svg v-else-if="item.icon === 'activity'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <!-- Calendar View -->
                                <svg v-else-if="item.icon === 'calendarView'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01" /></svg>
                                <!-- Search (SEO Pages) -->
                                <svg v-else-if="item.icon === 'search'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                <!-- Code (Tracking & Pixels) -->
                                <svg v-else-if="item.icon === 'code'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                <!-- Truck (Suppliers) -->
                                <svg v-else-if="item.icon === 'truck'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                                <!-- Star (Satisfaction) -->
                                <svg v-else-if="item.icon === 'star'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                                <!-- Logout (Leaves) -->
                                <svg v-else-if="item.icon === 'logout'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                <!-- CRM Dashboard -->
                                <svg v-else-if="item.icon === 'crm'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                                <!-- Funnel (Leads) -->
                                <svg v-else-if="item.icon === 'funnel'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
                                <!-- Pipeline -->
                                <svg v-else-if="item.icon === 'pipeline'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25h2.25A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                                <!-- Megaphone (Campaigns) -->
                                <svg v-else-if="item.icon === 'megaphone'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" /></svg>
                                <!-- Source (Lead Sources) -->
                                <svg v-else-if="item.icon === 'source'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                <!-- Automation -->
                                <svg v-else-if="item.icon === 'automation'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <!-- Upload -->
                                <svg v-else-if="item.icon === 'upload'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <svg v-else-if="item.icon === 'switch'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                <svg v-else-if="item.icon === 'trash'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </div>
                            <span>{{ navLabel(item) }}</span>
                            <!-- Active indicator dot -->
                            <span v-if="isActive(item.href)" class="ltr:ml-auto rtl:mr-auto w-1.5 h-1.5 rounded-full bg-[var(--brand-primary)]"></span>
                        </Link>
                    </div>
                    </div>
                </div>
            </nav>

            <!-- Sidebar footer — Admin Card -->
            <div class="p-4 border-t border-white/[0.06]">
                <!-- Visit Site -->
                <a
                    href="/"
                    target="_blank"
                    class="flex items-center gap-2 px-3 py-2 mb-2 rounded-lg text-[12px] font-medium text-white/40 hover:text-[var(--brand-primary)] hover:bg-white/[0.04] transition-all duration-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    <span>{{ isRtl ? 'زيارة الموقع' : 'Visit Website' }}</span>
                </a>

                <!-- Profile card -->
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/[0.04]">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[var(--brand-primary)] to-[var(--brand-secondary)] flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-[var(--brand-primary)]/10">
                        {{ adminName.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium text-white/80 truncate">{{ adminName }}</p>
                        <p class="text-[10px] text-white/35 capitalize truncate">{{ adminRole }}</p>
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
                    <!-- Visit site (desktop) -->
                    <a
                        href="/"
                        target="_blank"
                        class="hidden lg:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-500 hover:text-[var(--brand-primary)] hover:bg-[var(--brand-primary)]/5 transition-all duration-200"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        <span>{{ isRtl ? 'الموقع' : 'Visit Site' }}</span>
                    </a>

                    <!-- Language Switcher -->
                    <button
                        @click="switchLocale"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 border"
                        :class="isRtl
                            ? 'text-[var(--brand-primary)] border-[var(--brand-primary)]/30 hover:bg-[var(--brand-primary)]/10'
                            : 'text-[var(--brand-primary)] border-[var(--brand-primary)]/30 hover:bg-[var(--brand-primary)]/10'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                        <span>{{ isRtl ? 'EN' : 'عربي' }}</span>
                    </button>

                    <!-- Global Search -->
                    <button @click="$refs.commandPalette.toggle()" class="hidden sm:flex items-center gap-2 px-3 py-1.5 text-xs text-gray-400 bg-gray-100 hover:bg-gray-200 rounded-lg transition border border-gray-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>{{ isRtl ? 'بحث...' : 'Search...' }}</span>
                        <kbd class="text-[10px] text-gray-400 bg-white border px-1 py-0.5 rounded font-mono">⌘K</kbd>
                    </button>

                    <!-- Chat -->
                    <ChatIcon panelPrefix="admin" :accentColor="'var(--brand-primary)'" />

                    <!-- Notification Bell -->
                    <NotificationBell />

                    <!-- Divider -->
                    <div class="hidden lg:block w-px h-6 bg-gray-200"></div>

                    <!-- Admin badge -->
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[var(--brand-primary)] to-[var(--brand-secondary)] flex items-center justify-center text-white text-sm font-bold shadow-md shadow-[var(--brand-primary)]/15">
                            {{ adminName.charAt(0).toUpperCase() }}
                        </div>
                        <div class="hidden sm:flex flex-col">
                            <span class="text-sm font-semibold text-gray-800 leading-tight">{{ adminName }}</span>
                            <span class="text-[11px] text-gray-400 leading-tight capitalize">{{ adminRole }}</span>
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
                           class="text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] font-semibold transition-colors duration-200 inline-flex items-center gap-1">
                            Markeza Group
                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        </a>
                    </p>
                </div>
            </footer>
        </div>

        <!-- Admin Toast Notifications (Push + Polling) -->
        <AdminToastNotification />

        <!-- Global Search Command Palette -->
        <CommandPalette ref="commandPalette" />

        <!-- Chat Toast Notifications -->
        <ChatToast panelPrefix="admin" :accentColor="'var(--brand-primary)'" />
    </div>
</template>

<style scoped>
.admin-sidebar-scroll::-webkit-scrollbar {
    width: 3px;
}
.admin-sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.admin-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
}
.admin-sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.15);
}
.nav-collapse {
    will-change: max-height, opacity;
}
</style>
