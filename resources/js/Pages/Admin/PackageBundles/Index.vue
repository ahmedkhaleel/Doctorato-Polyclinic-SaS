<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

/* ── Custom searchable dropdown state ── */
const openDropdown = ref(null);
const dropdownSearches = ref({ module: '', status: '' });

function toggleDropdown(key) {
    if (openDropdown.value === key) {
        openDropdown.value = null;
    } else {
        openDropdown.value = key;
        dropdownSearches.value[key] = '';
        nextTick(() => {
            const input = document.querySelector(`.pb-dd-${key} .pb-dd-search`);
            if (input) input.focus();
        });
    }
}

function handleClickOutside(e) {
    if (!e.target.closest('.pb-dd')) openDropdown.value = null;
}
onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));

const props = defineProps({
    bundles: Object,
    filters: Object,
});

/* ── Animation ── */
const visibleItems = ref(new Set());
function reveal(key, delay = 0) {
    setTimeout(() => visibleItems.value.add(key), delay);
}
onMounted(() => {
    reveal('header', 80);
    reveal('stats', 160);
    reveal('filters', 260);
    reveal('table', 360);
});
function isVisible(key) { return visibleItems.value.has(key); }

/* ── Modules ── */
const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

/* ── Search & filters ── */
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const moduleFilter = ref(props.filters?.module || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        module: moduleFilter.value || undefined,
    };
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/package-bundles', buildParams(), { preserveState: true, replace: true });
    }, 400);
});

watch(statusFilter, () => {
    router.get('/admin/package-bundles', buildParams(), { preserveState: true, replace: true });
});

watch(moduleFilter, () => {
    router.get('/admin/package-bundles', buildParams(), { preserveState: true, replace: true });
});

/* ── Filtered dropdown options ── */
const filteredModules = computed(() => {
    const q = (dropdownSearches.value.module || '').toLowerCase();
    if (!q) return activeModules.value;
    return activeModules.value.filter(m => m.name.toLowerCase().includes(q) || m.slug.toLowerCase().includes(q));
});

const statusOptions = computed(() => [
    { value: 'active', label: isRtl.value ? 'نشطة' : 'Active', icon: '🟢' },
    { value: 'inactive', label: isRtl.value ? 'غير نشطة' : 'Inactive', icon: '🔴' },
]);

const filteredStatuses = computed(() => {
    const q = (dropdownSearches.value.status || '').toLowerCase();
    if (!q) return statusOptions.value;
    return statusOptions.value.filter(s => s.label.toLowerCase().includes(q));
});

const selectedModuleLabel = computed(() => {
    if (!moduleFilter.value) return isRtl.value ? 'كل الأقسام' : 'All Departments';
    const found = activeModules.value.find(m => m.slug === moduleFilter.value);
    return found ? found.name : moduleFilter.value;
});

const selectedStatusLabel = computed(() => {
    if (!statusFilter.value) return isRtl.value ? 'كل الحالات' : 'All Status';
    const found = statusOptions.value.find(s => s.value === statusFilter.value);
    return found ? found.label : statusFilter.value;
});

function selectModule(slug) {
    moduleFilter.value = slug;
    openDropdown.value = null;
}

function selectStatus(val) {
    statusFilter.value = val;
    openDropdown.value = null;
}

/* ── Quick stats ── */
const totalBundles = computed(() => props.bundles?.total || props.bundles?.data?.length || 0);
const activeBundles = computed(() => props.bundles?.data?.filter(b => b.is_active).length || 0);
const totalRevenue = computed(() => {
    return props.bundles?.data?.reduce((sum, b) => sum + Number(b.total_price || 0), 0) || 0;
});
const totalBookings = computed(() => {
    return props.bundles?.data?.reduce((sum, b) => sum + Number(b.bundle_bookings_count || 0), 0) || 0;
});

/* ── Helpers ── */
function savingsPercent(bundle) {
    if (!bundle.original_price || Number(bundle.original_price) <= 0) return 0;
    return Math.round(((Number(bundle.original_price) - Number(bundle.total_price)) / Number(bundle.original_price)) * 100);
}

/* ── Delete ── */
const showDeleteModal = ref(false);
const deletingBundle = ref(null);

function confirmDelete(bundle) {
    deletingBundle.value = bundle;
    showDeleteModal.value = true;
}
function executeDelete() {
    router.post(`/admin/package-bundles/${deletingBundle.value.id}/delete`, {
        onFinish: () => {
            showDeleteModal.value = false;
            deletingBundle.value = null;
        },
    });
}

function toggleActive(bundle) {
    router.post(`/admin/package-bundles/${bundle.id}/toggle-active`, {}, { preserveState: true });
}
</script>

<template>
    <AdminLayout :title="$t('a_bundle_packages')">
        <div class="space-y-6">

            <!-- ===== HEADER ===== -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 idx-item"
                 :class="{ 'idx-item--visible': isVisible('header') }">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ $t('a_bundle_packages') }}</h1>
                    <p class="text-sm text-gray-400 mt-1">{{ isRtl ? 'إدارة حزم الخدمات وباقات الأسعار' : 'Manage your service packages and pricing bundles' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link v-if="can('bookings.create')"
                          href="/admin/bookings/create"
                          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 border-2 hover:shadow-md active:scale-[0.97]"
                          style="border-color: #C4A265; color: #C4A265;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        {{ isRtl ? 'حجز خدمة' : 'Book Service' }}
                    </Link>
                    <Link v-if="can('package_bundle_bookings.create')"
                          href="/admin/package-bundle-bookings/create"
                          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 border-2 hover:shadow-md active:scale-[0.97]"
                          style="border-color: #C4A265; color: #C4A265;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        {{ isRtl ? 'حجز باقة' : 'Book Package' }}
                    </Link>
                    <Link v-if="can('package_bundles.create')"
                          href="/admin/package-bundles/create"
                          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 hover:shadow-lg hover:shadow-[#C4A265]/25 active:scale-[0.97]"
                          style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'باقة جديدة' : 'New Bundle' }}
                    </Link>
                </div>
            </div>

            <!-- ===== MINI STATS ===== -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 idx-item"
                 :class="{ 'idx-item--visible': isVisible('stats') }">
                <div class="mini-stat group">
                    <div class="mini-stat__bar" style="background: linear-gradient(135deg, #C4A265, #D4B87A);"></div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:scale-110"
                             style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الإجمالي' : 'Total' }}</p>
                            <p class="text-lg font-extrabold text-gray-800">{{ totalBundles }}</p>
                        </div>
                    </div>
                </div>
                <div class="mini-stat group">
                    <div class="mini-stat__bar bg-emerald-400"></div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-50 shadow-sm transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'نشطة' : 'Active' }}</p>
                            <p class="text-lg font-extrabold text-emerald-600">{{ activeBundles }}</p>
                        </div>
                    </div>
                </div>
                <div class="mini-stat group">
                    <div class="mini-stat__bar bg-slate-400"></div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-slate-50 shadow-sm transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الحجوزات' : 'Bookings' }}</p>
                            <p class="text-lg font-extrabold text-[#1B365D]">{{ totalBookings }}</p>
                        </div>
                    </div>
                </div>
                <div class="mini-stat group">
                    <div class="mini-stat__bar bg-slate-400"></div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-slate-50 shadow-sm transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'القيمة الإجمالية' : 'Combined Value' }}</p>
                            <p class="text-lg font-extrabold text-[#1B365D] tabular-nums">{{ formatCurrency(totalRevenue) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== FILTERS ===== -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 idx-item"
                 :class="{ 'idx-item--visible': isVisible('filters') }">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 flex items-center pointer-events-none" :class="isRtl ? 'right-0 pr-4' : 'left-0 pl-4'">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input v-model="search" type="text" :placeholder="isRtl ? 'ابحث عن الباقات بالاسم...' : 'Search bundles by name...'"
                               class="doctorato-input w-full py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200 placeholder-gray-400 bg-gray-50/50 hover:bg-white focus:bg-white"
                               :class="isRtl ? 'pr-11 pl-4' : 'pl-11 pr-4'" />
                    </div>
                    <!-- Module Dropdown -->
                    <div v-if="activeModules.length > 1" class="relative pb-dd pb-dd-module" @click.stop>
                        <button type="button" @click="toggleDropdown('module')"
                                class="flex items-center gap-2 px-4 py-2.5 border rounded-xl text-sm transition-all duration-200 bg-white min-w-[170px] group"
                                :class="openDropdown === 'module' ? 'border-[#C4A265] ring-2 ring-[#C4A265]/20 shadow-sm' : 'border-gray-200 hover:border-gray-300'">
                            <svg class="w-4 h-4 flex-shrink-0" :class="moduleFilter ? 'text-[#C4A265]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            <span class="flex-1 text-start truncate" :class="moduleFilter ? 'text-gray-800 font-medium' : 'text-gray-500'">{{ selectedModuleLabel }}</span>
                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="openDropdown === 'module' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <Transition
                            enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="openDropdown === 'module'" class="absolute z-50 mt-1.5 w-full min-w-[220px] bg-white rounded-xl border border-gray-200 shadow-xl shadow-black/8 overflow-hidden"
                                 :class="isRtl ? 'right-0' : 'left-0'">
                                <div class="p-2 border-b border-gray-100">
                                    <div class="relative">
                                        <svg class="absolute top-2.5 w-3.5 h-3.5 text-gray-400" :class="isRtl ? 'right-2.5' : 'left-2.5'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35"/></svg>
                                        <input v-model="dropdownSearches.module" type="text"
                                               :placeholder="isRtl ? 'بحث عن قسم...' : 'Search department...'"
                                               class="doctorato-input pb-dd-search w-full text-xs border border-gray-200 rounded-lg py-2 bg-gray-50 focus:bg-white focus:ring-1 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all"
                                               :class="isRtl ? 'pr-8 pl-3' : 'pl-8 pr-3'" />
                                    </div>
                                </div>
                                <div class="max-h-[220px] overflow-y-auto py-1">
                                    <button type="button" @click="selectModule('')"
                                            class="w-full flex items-center gap-2.5 px-3 py-2 text-xs transition-colors"
                                            :class="!moduleFilter ? 'bg-[#C4A265]/10 text-[#C4A265] font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px]"
                                              :class="!moduleFilter ? 'bg-[#C4A265] text-white' : 'bg-gray-100 text-gray-400'">✦</span>
                                        {{ isRtl ? 'كل الأقسام' : 'All Departments' }}
                                        <svg v-if="!moduleFilter" class="w-3.5 h-3.5 text-[#C4A265]" :class="isRtl ? 'mr-auto' : 'ml-auto'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <button v-for="m in filteredModules" :key="m.slug" type="button" @click="selectModule(m.slug)"
                                            class="w-full flex items-center gap-2.5 px-3 py-2 text-xs transition-colors"
                                            :class="moduleFilter === m.slug ? 'bg-[#C4A265]/10 text-[#C4A265] font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px]"
                                              :class="moduleFilter === m.slug ? 'bg-[#C4A265] text-white' : 'bg-gray-100 text-gray-400'">🏥</span>
                                        {{ m.name }}
                                        <svg v-if="moduleFilter === m.slug" class="w-3.5 h-3.5 text-[#C4A265]" :class="isRtl ? 'mr-auto' : 'ml-auto'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <div v-if="filteredModules.length === 0 && dropdownSearches.module" class="px-3 py-4 text-center text-xs text-gray-400">
                                        {{ isRtl ? 'لا توجد نتائج' : 'No results found' }}
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="relative pb-dd pb-dd-status" @click.stop>
                        <button type="button" @click="toggleDropdown('status')"
                                class="flex items-center gap-2 px-4 py-2.5 border rounded-xl text-sm transition-all duration-200 bg-white min-w-[160px] group"
                                :class="openDropdown === 'status' ? 'border-[#C4A265] ring-2 ring-[#C4A265]/20 shadow-sm' : 'border-gray-200 hover:border-gray-300'">
                            <span v-if="statusFilter === 'active'" class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                            <span v-else-if="statusFilter === 'inactive'" class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
                            <svg v-else class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            <span class="flex-1 text-start truncate" :class="statusFilter ? 'text-gray-800 font-medium' : 'text-gray-500'">{{ selectedStatusLabel }}</span>
                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="openDropdown === 'status' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <Transition
                            enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="openDropdown === 'status'" class="absolute z-50 mt-1.5 w-full min-w-[200px] bg-white rounded-xl border border-gray-200 shadow-xl shadow-black/8 overflow-hidden"
                                 :class="isRtl ? 'right-0' : 'left-0'">
                                <div class="py-1">
                                    <button type="button" @click="selectStatus('')"
                                            class="w-full flex items-center gap-2.5 px-3 py-2.5 text-xs transition-colors"
                                            :class="!statusFilter ? 'bg-[#C4A265]/10 text-[#C4A265] font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px]"
                                              :class="!statusFilter ? 'bg-[#C4A265] text-white' : 'bg-gray-100 text-gray-400'">✦</span>
                                        {{ isRtl ? 'كل الحالات' : 'All Status' }}
                                        <svg v-if="!statusFilter" class="w-3.5 h-3.5 text-[#C4A265]" :class="isRtl ? 'mr-auto' : 'ml-auto'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <button type="button" @click="selectStatus('active')"
                                            class="w-full flex items-center gap-2.5 px-3 py-2.5 text-xs transition-colors"
                                            :class="statusFilter === 'active' ? 'bg-[#C4A265]/10 text-[#C4A265] font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center bg-emerald-50">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        </span>
                                        {{ isRtl ? 'نشطة' : 'Active' }}
                                        <svg v-if="statusFilter === 'active'" class="w-3.5 h-3.5 text-[#C4A265]" :class="isRtl ? 'mr-auto' : 'ml-auto'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <button type="button" @click="selectStatus('inactive')"
                                            class="w-full flex items-center gap-2.5 px-3 py-2.5 text-xs transition-colors"
                                            :class="statusFilter === 'inactive' ? 'bg-[#C4A265]/10 text-[#C4A265] font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center bg-gray-100">
                                            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                        </span>
                                        {{ isRtl ? 'غير نشطة' : 'Inactive' }}
                                        <svg v-if="statusFilter === 'inactive'" class="w-3.5 h-3.5 text-[#C4A265]" :class="isRtl ? 'mr-auto' : 'ml-auto'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                    <div v-if="search || statusFilter || moduleFilter" class="flex items-center gap-1.5 px-3 py-1.5 bg-[#C4A265]/10 rounded-lg">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#C4A265] animate-pulse"></div>
                        <span class="text-xs font-semibold text-[#C4A265]">{{ isRtl ? 'مُصفّى' : 'Filtered' }}</span>
                    </div>
                </div>
            </div>

            <!-- ===== TABLE ===== -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden idx-item"
                 :class="{ 'idx-item--visible': isVisible('table') }">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-4 md:px-6 py-4 ltr:text-left rtl:text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_bundle') }}</th>
                                <th class="px-4 md:px-6 py-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_services') }}</th>
                                <th class="px-4 md:px-6 py-4 ltr:text-left rtl:text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_price') }}</th>
                                <th class="px-4 md:px-6 py-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_savings') }}</th>
                                <th class="px-4 md:px-6 py-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_bookings') }}</th>
                                <th class="px-4 md:px-6 py-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(bundle, index) in bundles.data" :key="bundle.id"
                                class="table-row group"
                                :class="{ 'table-row--visible': isVisible('table') }"
                                :style="{ animationDelay: `${index * 60 + 400}ms` }">

                                <!-- Bundle Name -->
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="relative">
                                            <div v-if="bundle.image_url"
                                                 class="w-11 h-11 rounded-xl bg-cover bg-center border border-gray-100 shadow-sm transition-all duration-300 group-hover:shadow-md group-hover:border-[#C4A265]/30"
                                                 :style="`background-image: url(${bundle.image_url})`"></div>
                                            <div v-else class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow-sm transition-all duration-300 group-hover:shadow-md group-hover:scale-105"
                                                 style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                        </div>
                                        <div>
                                            <Link :href="`/admin/package-bundles/${bundle.id}`"
                                                  class="text-sm font-semibold text-gray-800 group-hover:text-[#C4A265] transition-colors duration-200">
                                                {{ bundle.name_en }}
                                            </Link>
                                            <p class="text-xs text-gray-400 mt-0.5" dir="rtl">{{ bundle.name_ar }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Services -->
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-50 text-[#1B365D] border border-slate-100">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                        {{ bundle.services_count }}
                                    </span>
                                </td>

                                <!-- Price -->
                                <td class="px-4 md:px-6 py-4">
                                    <div>
                                        <p class="text-sm font-bold tabular-nums" style="color: #C4A265;">{{ formatCurrency(bundle.total_price) }}</p>
                                        <p class="text-xs text-gray-300 line-through tabular-nums mt-0.5">{{ formatCurrency(bundle.original_price) }}</p>
                                    </div>
                                </td>

                                <!-- Savings -->
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span v-if="savingsPercent(bundle) > 0"
                                          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                        {{ savingsPercent(bundle) }}%
                                    </span>
                                    <span v-else class="text-xs text-gray-300">-</span>
                                </td>

                                <!-- Bookings -->
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span class="text-sm font-semibold tabular-nums" :class="bundle.bundle_bookings_count > 0 ? 'text-gray-700' : 'text-gray-300'">
                                        {{ bundle.bundle_bookings_count }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <button v-if="can('package_bundles.update')"
                                            @click="toggleActive(bundle)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold cursor-pointer transition-all duration-300 border"
                                            :class="bundle.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 shadow-sm shadow-emerald-100'
                                                : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-100'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="bundle.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                                        {{ bundle.is_active ? (isRtl ? 'نشطة' : 'Active') : (isRtl ? 'غير نشطة' : 'Inactive') }}
                                    </button>
                                    <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                          :class="bundle.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="bundle.is_active ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                        {{ bundle.is_active ? (isRtl ? 'نشطة' : 'Active') : (isRtl ? 'غير نشطة' : 'Inactive') }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="`/admin/package-bundles/${bundle.id}`"
                                              class="action-btn text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 hover:border-slate-200"
                                              :title="$t('a_view')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        <Link v-if="can('package_bundles.update')"
                                              :href="`/admin/package-bundles/${bundle.id}/edit`"
                                              class="action-btn text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/10 hover:border-[#C4A265]/30"
                                              :title="$t('a_edit')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button v-if="can('package_bundles.delete')"
                                                @click="confirmDelete(bundle)"
                                                class="action-btn text-gray-400 hover:text-red-600 hover:bg-red-50 hover:border-red-200"
                                                :title="$t('a_delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!bundles.data || bundles.data.length === 0">
                                <td colspan="7" class="px-4 md:px-6 py-16 text-center">
                                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-400">{{ $t('a_no_bundles_found') }}</p>
                                    <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'حاول تعديل البحث أو عوامل التصفية' : 'Try adjusting your search or filters' }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="bundles.links && bundles.links.length > 3"
                     class="px-4 md:px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-gray-400 font-medium">
                        <template v-if="isRtl">
                            عرض <span class="font-semibold text-gray-600">{{ bundles.from }}</span> إلى
                            <span class="font-semibold text-gray-600">{{ bundles.to }}</span> من
                            <span class="font-semibold text-gray-600">{{ bundles.total }}</span> نتيجة
                        </template>
                        <template v-else>
                            Showing <span class="font-semibold text-gray-600">{{ bundles.from }}</span> to
                            <span class="font-semibold text-gray-600">{{ bundles.to }}</span> of
                            <span class="font-semibold text-gray-600">{{ bundles.total }}</span> results
                        </template>
                    </p>
                    <nav class="flex space-x-1 rtl:space-x-reverse">
                        <template v-for="link in bundles.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" v-html="link.label"
                                  class="px-3 py-1.5 text-xs rounded-lg border transition-all duration-200 font-medium"
                                  :class="link.active ? 'text-white border-transparent shadow-sm' : 'text-gray-500 border-gray-200 hover:bg-gray-50 hover:border-gray-300'"
                                  :style="link.active ? 'background: linear-gradient(135deg, #C4A265, #D4B87A);' : ''"
                                  preserve-state />
                            <span v-else v-html="link.label" class="px-3 py-1.5 text-xs text-gray-300 font-medium" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            :title="$t('a_delete_bundle')"
            :message="isRtl ? 'هل أنت متأكد من حذف هذه الباقة؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this package bundle? This action cannot be undone.'"
            :confirm-text="isRtl ? 'حذف' : 'Delete'"
            confirm-color="red"
            @confirm="executeDelete"
            @cancel="showDeleteModal = false"
        />
    </AdminLayout>
</template>

<style scoped>
/* ── Reveal animation ── */
.idx-item {
    opacity: 0;
    transform: translateY(18px);
    transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.idx-item--visible {
    opacity: 1;
    transform: translateY(0);
}

/* ── Mini stat cards ── */
.mini-stat {
    position: relative;
    background: white;
    border-radius: 1rem;
    padding: 1rem 1.25rem;
    border: 1px solid rgb(243, 244, 246);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.mini-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}
.mini-stat__bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: 0 0 3px 3px;
}

/* ── Table rows stagger ── */
.table-row {
    opacity: 0;
    transform: translateY(8px);
    transition: background 0.15s ease;
}
.table-row--visible {
    animation: rowReveal 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes rowReveal {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.table-row:hover {
    background: rgba(249, 250, 251, 0.8);
}

/* ── Action buttons ── */
.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 0.625rem;
    border: 1px solid transparent;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.action-btn:hover {
    transform: scale(1.08);
}
.action-btn:active {
    transform: scale(0.95);
}
</style>
