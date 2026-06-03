<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';

const { can } = usePermissions();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    supplies: Object,
    categories: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const categoryFilter = ref(props.filters?.category_id || '');
const stockFilter = ref(props.filters?.stock || '');
const moduleFilter = ref(props.filters?.module || '');
const viewMode = ref('grid');
let searchTimeout = null;

const showDeleteModal = ref(false);
const deletingSupplyId = ref(null);
const deletingSupplyName = ref('');

function buildParams() {
    return {
        search: search.value || undefined,
        category_id: categoryFilter.value || undefined,
        stock: stockFilter.value || undefined,
        module: moduleFilter.value || undefined,
    };
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/supplies', buildParams(), {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

watch([categoryFilter, stockFilter, moduleFilter], () => {
    router.get('/admin/supplies', buildParams(), {
        preserveState: true,
        replace: true,
    });
});

function isLowStock(supply) {
    return supply.is_low_stock || supply.quantity <= supply.min_quantity;
}

function isExpired(supply) {
    if (!supply.expiry_date) return false;
    return new Date(supply.expiry_date) < new Date();
}

function isExpiring(supply) {
    if (!supply.expiry_date) return false;
    const expiry = new Date(supply.expiry_date);
    const now = new Date();
    const thirtyDays = 30 * 24 * 60 * 60 * 1000;
    return expiry > now && (expiry - now) < thirtyDays;
}

function stockPercentage(supply) {
    const max = Math.max(supply.min_quantity * 4, supply.quantity, 100);
    return Math.min(Math.round((supply.quantity / max) * 100), 100);
}

function stockBarColor(supply) {
    const pct = stockPercentage(supply);
    if (pct < 25) return 'bg-red-500';
    if (pct < 50) return 'bg-amber-500';
    return 'bg-emerald-500';
}

function statusBadge(supply) {
    if (isExpired(supply)) return { text: t('a_expired'), classes: 'bg-amber-100 text-amber-800 border border-amber-200' };
    if (isLowStock(supply)) return { text: t('a_low_stock'), classes: 'bg-red-100 text-red-800 border border-red-200' };
    return { text: t('a_in_stock'), classes: 'bg-emerald-100 text-emerald-800 border border-emerald-200' };
}

const ACCENT = '#6366F1'; // Inventory module indigo

function categoryColor(supply) {
    return supply.supply_category?.color || ACCENT;
}

function displayName(supply) {
    return isRtl.value ? (supply.name_ar || supply.name_en) : supply.name_en;
}

function confirmDelete(supply) {
    deletingSupplyId.value = supply.id;
    deletingSupplyName.value = supply.name_en;
    showDeleteModal.value = true;
}

function executeDelete() {
    router.post(`/admin/supplies/${deletingSupplyId.value}/delete`, {
        onFinish: () => {
            showDeleteModal.value = false;
            deletingSupplyId.value = null;
            deletingSupplyName.value = '';
        },
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    deletingSupplyId.value = null;
    deletingSupplyName.value = '';
}

const hasActiveFilters = computed(() => {
    return search.value || categoryFilter.value || stockFilter.value || moduleFilter.value;
});

function clearFilters() {
    search.value = '';
    categoryFilter.value = '';
    stockFilter.value = '';
    moduleFilter.value = '';
    router.get('/admin/supplies', {}, { preserveState: true, replace: true });
}

const moduleOptions = [
    { value: '', label: { en: 'All Modules', ar: 'كل الأقسام' } },
    { value: 'derma', label: { en: 'Dermatology', ar: 'الجلدية' } },
    { value: 'dental', label: { en: 'Dental', ar: 'الأسنان' } },
    { value: 'shared', label: { en: 'Shared', ar: 'مشترك' } },
];
</script>

<template>
    <AdminLayout :title="$t('a_products')">
        <div class="space-y-6">

            <!-- Hero Header -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#1B365D] p-4 md:p-6 sm:p-8">
                <div class="absolute top-0 right-0 w-72 h-72 bg-slate-400/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-[#1B365D]/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;40&quot; height=&quot;40&quot; viewBox=&quot;0 0 40 40&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;%23fff&quot;%3E%3Ccircle cx=&quot;1&quot; cy=&quot;1&quot; r=&quot;1&quot;/%3E%3C/g%3E%3C/svg%3E')"></div>
                <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-slate-400/20 flex items-center justify-center">
                                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            </div>
                            <p class="text-slate-300 text-xs font-semibold tracking-wider uppercase">{{ isRtl ? 'المخزون' : 'Inventory' }}</p>
                        </div>
                        <h1 class="text-xl md:text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'المنتجات' : 'Products' }}</h1>
                        <p class="text-slate-200/70 text-sm mt-1">{{ isRtl ? 'إدارة المستلزمات والمنتجات الطبية' : 'Manage medical supplies and products' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-5 py-3.5 border border-white/10 text-center min-w-[80px]">
                            <p class="text-xl md:text-2xl font-bold text-white leading-none tabular-nums">{{ supplies.total }}</p>
                            <p class="text-[10px] text-slate-300 mt-1 font-medium uppercase tracking-wide">{{ isRtl ? 'إجمالي' : 'Total' }}</p>
                        </div>
                        <a
                            :href="`/admin/exports/supplies?${new URLSearchParams(Object.fromEntries(Object.entries(buildParams()).filter(([,v]) => v))).toString()}`"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/5 backdrop-blur-sm text-white/80 text-sm font-medium rounded-xl border border-white/10 hover:bg-white/10 hover:text-white transition-all duration-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            {{ isRtl ? 'تصدير Excel' : 'Export' }}
                        </a>
                        <Link v-if="can('supplies.create')" href="/admin/supplies/create"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 backdrop-blur-sm text-white text-sm font-semibold rounded-xl border border-white/20 hover:bg-white/20 transition-all duration-200 shadow-lg shadow-black/10"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ isRtl ? 'إضافة منتج' : 'Add Product' }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                    <!-- Search -->
                    <div class="relative flex-1 min-w-0">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="$t('a_search_products')"
                            class="doctorato-input w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Category -->
                        <select
                            v-model="categoryFilter"
                            class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors min-w-[160px]"
                        >
                            <option value="">{{ $t('a_all_categories') }}</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name_en }}</option>
                        </select>

                        <!-- Module -->
                        <select
                            v-model="moduleFilter"
                            class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors min-w-[140px]"
                        >
                            <option v-for="opt in moduleOptions" :key="opt.value" :value="opt.value">{{ isRtl ? opt.label.ar : opt.label.en }}</option>
                        </select>

                        <!-- Stock Status -->
                        <select
                            v-model="stockFilter"
                            class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors min-w-[140px]"
                        >
                            <option value="">{{ $t('a_all_stock') }}</option>
                            <option value="low">{{ $t('a_low_stock') }}</option>
                            <option value="expiring">{{ $t('a_expiring_soon') }}</option>
                            <option value="expired">{{ $t('a_expired') }}</option>
                        </select>

                        <!-- Clear Filters -->
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="px-3 py-2.5 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors"
                            :title="t('a_clear_filters')"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Divider -->
                        <div class="hidden lg:block w-px h-8 bg-gray-200"></div>

                        <!-- View Toggle -->
                        <div class="flex items-center bg-gray-100 rounded-xl p-1">
                            <button
                                @click="viewMode = 'grid'"
                                :class="viewMode === 'grid' ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                                class="p-2 rounded-lg transition-all duration-200"
                                :title="t('a_grid_view')"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'table'"
                                :class="viewMode === 'table' ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-400 hover:text-gray-600'"
                                class="p-2 rounded-lg transition-all duration-200"
                                :title="t('a_table_view')"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <Transition
                mode="out-in"
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <!-- Grid View -->
                <div v-if="viewMode === 'grid'" key="grid">
                    <TransitionGroup
                        tag="div"
                        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
                        enter-active-class="transition-all duration-400 ease-out"
                        enter-from-class="opacity-0 translate-y-4 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                        move-class="transition-all duration-300"
                    >
                        <div
                            v-for="(supply, index) in supplies.data"
                            :key="supply.id"
                            class="inventory-card-hover bg-white rounded-2xl border border-gray-100 overflow-hidden group relative"
                            :class="{ 'border-l-4 border-l-red-400': isLowStock(supply) && !isExpired(supply) }"
                            :style="{ animationDelay: `${index * 60}ms` }"
                        >
                            <!-- Top Accent Line -->
                            <div class="h-1 w-full" :style="{ backgroundColor: categoryColor(supply) }"></div>

                            <!-- Expired overlay badge -->
                            <div v-if="isExpired(supply)" class="absolute top-3 left-3 z-10">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500 text-white shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    {{ $t('a_expired') }}
                                </span>
                            </div>

                            <div class="p-5">
                                <!-- Category badge -->
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-bold text-gray-900 truncate">{{ displayName(supply) }}</h3>
                                        <p v-if="!isRtl && supply.name_ar" class="text-sm text-gray-400 truncate mt-0.5" dir="rtl">{{ supply.name_ar }}</p>
                                        <p v-if="isRtl && supply.name_en" class="text-sm text-gray-400 truncate mt-0.5">{{ supply.name_en }}</p>
                                    </div>
                                    <span
                                        v-if="supply.supply_category"
                                        class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium flex-shrink-0"
                                        :style="{
                                            backgroundColor: categoryColor(supply) + '18',
                                            color: categoryColor(supply),
                                            border: '1px solid ' + categoryColor(supply) + '30'
                                        }"
                                    >
                                        <span v-if="supply.supply_category.icon" v-html="supply.supply_category.icon" class="w-3 h-3"></span>
                                        {{ supply.supply_category.name_en }}
                                    </span>
                                </div>

                                <!-- SKU & Module -->
                                <div class="flex items-center gap-2 mb-4">
                                    <p class="text-xs font-mono text-[#1B365D] tracking-wide">SKU: {{ supply.sku || 'N/A' }}</p>
                                    <span
                                        v-if="supply.module && supply.module !== 'shared'"
                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                        :class="supply.module === 'dental' ? 'bg-slate-100 text-[#1B365D]' : 'bg-amber-100 text-[#C4A265]'"
                                    >
                                        {{ supply.module }}
                                    </span>
                                    <span v-else-if="supply.module === 'shared'" class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-500">
                                        {{ isRtl ? 'مشترك' : 'shared' }}
                                    </span>
                                </div>

                                <!-- Stock Level -->
                                <div class="mb-3">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-xs font-medium text-gray-500">{{ $t('a_stock_level') }}</span>
                                        <span class="text-xs font-semibold" :class="isLowStock(supply) ? 'text-red-600' : 'text-gray-700'">
                                            {{ supply.quantity }} {{ supply.unit || 'units' }}
                                        </span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="stockBarColor(supply)"
                                            :style="{ width: stockPercentage(supply) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-[10px] text-gray-400">Min: {{ supply.min_quantity }}</span>
                                        <span v-if="isLowStock(supply)" class="text-[10px] font-medium text-red-500">{{ $t('a_below_minimum') }}</span>
                                    </div>
                                </div>

                                <!-- Info Row -->
                                <div class="flex items-center gap-3 text-xs text-gray-500 mb-4">
                                    <span v-if="supply.supplier" class="flex items-center gap-1 truncate">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span class="truncate">{{ supply.supplier }}</span>
                                    </span>
                                    <span v-if="supply.unit" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        {{ supply.unit }}
                                    </span>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-gray-100 pt-3">
                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-2">
                                        <Link
                                            v-if="can('supplies.view')"
                                            :href="`/admin/supplies/${supply.id}/transactions`"
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 border border-slate-100 transition-colors duration-200"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            {{ $t('a_view') }}
                                        </Link>
                                        <Link
                                            v-if="can('supplies.update')"
                                            :href="`/admin/supplies/${supply.id}/edit`"
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 border border-slate-100 transition-colors duration-200"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            {{ $t('a_edit') }}
                                        </Link>
                                        <button
                                            v-if="can('supplies.delete')"
                                            @click="confirmDelete(supply)"
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 transition-colors duration-200"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            {{ $t('a_delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TransitionGroup>

                    <!-- Empty State -->
                    <div v-if="!supplies.data || supplies.data.length === 0" class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ $t('a_no_products_found') }}</h3>
                        <p class="text-sm text-gray-500 mb-4">{{ $t('a_adjust_search') }}</p>
                        <button v-if="hasActiveFilters" @click="clearFilters" class="text-sm font-medium text-[#1B365D] hover:underline">
                            {{ $t('a_clear_all_filters') }}
                        </button>
                    </div>
                </div>

                <!-- Table View -->
                <div v-else key="table">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr class="bg-gray-50/80">
                                        <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">SKU</th>
                                        <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_name') }}</th>
                                        <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_category') }}</th>
                                        <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_quantity') }}</th>
                                        <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                        <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_supplier') }}</th>
                                        <th class="px-4 md:px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr
                                        v-for="supply in supplies.data"
                                        :key="supply.id"
                                        class="hover:bg-gray-50/50 transition-colors duration-150"
                                        :class="{ 'bg-red-50/50': isLowStock(supply) }"
                                    >
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-mono text-[#1B365D] font-medium">{{ supply.sku || '-' }}</span>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">{{ supply.name_en }}</div>
                                            <div v-if="supply.name_ar" class="text-xs text-gray-400 mt-0.5" dir="rtl">{{ supply.name_ar }}</div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            <span
                                                v-if="supply.supply_category"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium"
                                                :style="{
                                                    backgroundColor: categoryColor(supply) + '18',
                                                    color: categoryColor(supply)
                                                }"
                                            >
                                                {{ supply.supply_category.name_en }}
                                            </span>
                                            <span v-else class="text-sm text-gray-400">-</span>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-20">
                                                    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                        <div
                                                            class="h-full rounded-full transition-all duration-500"
                                                            :class="stockBarColor(supply)"
                                                            :style="{ width: stockPercentage(supply) + '%' }"
                                                        ></div>
                                                    </div>
                                                </div>
                                                <span class="text-sm font-semibold" :class="isLowStock(supply) ? 'text-red-600' : 'text-gray-900'">
                                                    {{ supply.quantity }}
                                                </span>
                                                <span class="text-xs text-gray-400">{{ supply.unit || '' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                                :class="statusBadge(supply).classes"
                                            >
                                                {{ statusBadge(supply).text }}
                                            </span>
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ supply.supplier || '-' }}
                                        </td>
                                        <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <Link
                                                    v-if="can('supplies.view')"
                                                    :href="`/admin/supplies/${supply.id}/transactions`"
                                                    class="p-2 rounded-lg text-[#1B365D] hover:bg-slate-50 transition-colors duration-200"
                                                    :title="t('a_view')"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </Link>
                                                <Link
                                                    v-if="can('supplies.update')"
                                                    :href="`/admin/supplies/${supply.id}/edit`"
                                                    class="p-2 rounded-lg text-[#1B365D] hover:bg-slate-50 transition-colors duration-200"
                                                    :title="t('a_edit')"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </Link>
                                                <button
                                                    v-if="can('supplies.delete')"
                                                    @click="confirmDelete(supply)"
                                                    class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors duration-200"
                                                    :title="t('a_delete')"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!supplies.data || supplies.data.length === 0">
                                        <td colspan="7" class="px-4 md:px-6 py-12 text-center">
                                            <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-900 mb-1">{{ $t('a_no_products_found') }}</p>
                                            <p class="text-sm text-gray-500">{{ $t('a_adjust_search') }}</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Pagination -->
            <div v-if="supplies.links && supplies.links.length > 3" class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 md:px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-sm text-gray-500">
                    {{ $t('a_showing') }} <span class="font-semibold text-gray-700">{{ supplies.from }}</span>
                    {{ $t('a_to') }} <span class="font-semibold text-gray-700">{{ supplies.to }}</span>
                    {{ $t('a_of') }} <span class="font-semibold text-gray-700">{{ supplies.total }}</span> {{ $t('a_results') }}
                </p>
                <nav class="flex items-center space-x-1 rtl:space-x-reverse">
                    <template v-for="link in supplies.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-200"
                            :class="link.active
                                ? 'text-white border-transparent shadow-sm'
                                : 'text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300'"
                            :style="link.active ? 'background-color: #6366F1;' : ''"
                            preserve-state
                        />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-300" />
                    </template>
                </nav>
            </div>
        </div>

        <!-- Delete Confirm Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="$t('a_delete_product')"
            :message="$t('a_delete_product_confirm')"
            :confirm-text="$t('a_delete')"
            :cancel-text="$t('a_cancel')"
            confirm-color="red"
            @confirm="executeDelete"
            @cancel="cancelDelete"
        />
    </AdminLayout>
</template>

<style scoped>
.inventory-card-hover {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.inventory-card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08),
                0 8px 10px -6px rgba(0, 0, 0, 0.04);
}

.stagger-inventory {
    animation: staggerFadeIn 0.4s ease-out both;
}

@keyframes staggerFadeIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
