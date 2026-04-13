<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const ACCENT = '#C4A265';

const props = defineProps({
    supplies: Object,
    categories: Array,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || '');
const moduleFilter = ref(props.filters?.module || '');
const categoryFilter = ref(props.filters?.category_id || '');
const stockFilter = ref(props.filters?.stock || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        module: moduleFilter.value || undefined,
        category_id: categoryFilter.value || undefined,
        stock: stockFilter.value || undefined,
    };
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/doctor/inventory', buildParams(), { preserveState: true, replace: true });
    }, 400);
});

watch([moduleFilter, categoryFilter, stockFilter], () => {
    router.get('/doctor/inventory', buildParams(), { preserveState: true, replace: true });
});

function displayName(item) {
    if (!item) return '-';
    return isRtl.value ? (item.name_ar || item.name_en) : (item.name_en || item.name_ar);
}

function stockPercentage(supply) {
    const max = Math.max(supply.min_quantity * 4, supply.quantity, 100);
    return Math.min(Math.round((supply.quantity / max) * 100), 100);
}

function stockBarColor(supply) {
    const pct = stockPercentage(supply);
    if (pct > 50) return '#22c55e';
    if (pct > 25) return '#eab308';
    return '#ef4444';
}

function isLowStock(s) { return s.is_low_stock || s.quantity <= s.min_quantity; }
function isExpired(s) { return s.expiry_date && new Date(s.expiry_date) < new Date(); }

const moduleOptions = [
    { value: '', label: { en: 'All Modules', ar: 'كل الأقسام' } },
    { value: 'derma', label: { en: 'Dermatology', ar: 'الجلدية' } },
    { value: 'dental', label: { en: 'Dental', ar: 'الأسنان' } },
    { value: 'shared', label: { en: 'Shared', ar: 'مشترك' } },
];

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8 transition-all duration-700"
            :style="{ opacity: mounted ? 1 : 0, transform: mounted ? 'translateY(0)' : 'translateY(12px)', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <div class="absolute top-0 right-0 w-64 h-64 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-[#C4A265]/5 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 24px 24px;"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">
                            {{ isRtl ? 'مخزون العيادة' : 'Clinic Inventory' }}
                        </h1>
                        <p class="text-sm text-gray-400 mt-1">
                            {{ isRtl ? 'اطلع على المستلزمات المتاحة ومستويات المخزون' : 'View available supplies and stock levels' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 text-center">
                            <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ isRtl ? 'المنتجات' : 'Products' }}</p>
                        </div>
                        <div class="bg-red-500/20 backdrop-blur-sm rounded-xl px-4 py-3 text-center" v-if="stats.lowStock > 0">
                            <p class="text-2xl font-bold text-red-300">{{ stats.lowStock }}</p>
                            <p class="text-[10px] text-red-300/70 uppercase tracking-wide">{{ isRtl ? 'مخزون منخفض' : 'Low Stock' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px] sm:min-w-[240px] max-w-md">
                <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    :placeholder="isRtl ? 'بحث بالاسم أو الرمز...' : 'Search by name or SKU...'"
                    class="w-full ps-10 pe-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors"
                />
            </div>

            <select v-model="moduleFilter" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265]">
                <option v-for="opt in moduleOptions" :key="opt.value" :value="opt.value">{{ isRtl ? opt.label.ar : opt.label.en }}</option>
            </select>

            <select v-model="categoryFilter" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'كل الفئات' : 'All Categories' }}</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ displayName(cat) }}</option>
            </select>

            <select v-model="stockFilter" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'كل المخزون' : 'All Stock' }}</option>
                <option value="low">{{ isRtl ? 'مخزون منخفض' : 'Low Stock' }}</option>
            </select>
        </div>

        <!-- Supplies Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
                v-for="(supply, index) in supplies.data"
                :key="supply.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500 hover:shadow-md hover:border-gray-200"
                :style="{ opacity: mounted ? 1 : 0, transform: mounted ? 'translateY(0)' : 'translateY(16px)', transitionDelay: `${100 + index * 40}ms`, transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
            >
                <div class="p-4 sm:p-5">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-bold text-gray-900 truncate">{{ displayName(supply) }}</h3>
                            <p class="text-xs text-gray-400 truncate mt-0.5 font-mono">{{ supply.sku || '-' }}</p>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0 ms-2">
                            <span
                                v-if="supply.module && supply.module !== 'shared'"
                                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                                :class="supply.module === 'dental' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700'"
                            >{{ supply.module }}</span>
                            <span
                                v-if="isLowStock(supply)"
                                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-red-100 text-red-700"
                            >{{ isRtl ? 'منخفض' : 'Low' }}</span>
                            <span
                                v-if="isExpired(supply)"
                                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-red-100 text-red-700"
                            >{{ isRtl ? 'منتهي' : 'Expired' }}</span>
                        </div>
                    </div>

                    <!-- Category -->
                    <div v-if="supply.supply_category" class="mb-3">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium"
                            :style="{
                                backgroundColor: (supply.supply_category.color || '#6B7280') + '18',
                                color: supply.supply_category.color || '#6B7280',
                            }"
                        >
                            {{ displayName(supply.supply_category) }}
                        </span>
                    </div>

                    <!-- Stock Bar -->
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-medium text-gray-400 uppercase">{{ isRtl ? 'المخزون' : 'Stock' }}</span>
                            <span class="text-xs font-bold" :class="isLowStock(supply) ? 'text-red-600' : 'text-gray-700'">
                                {{ supply.quantity }} {{ supply.unit || '' }}
                            </span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :style="{ width: stockPercentage(supply) + '%', backgroundColor: stockBarColor(supply) }"
                            ></div>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[10px] text-gray-400">{{ isRtl ? 'الحد الأدنى' : 'Min' }}: {{ supply.min_quantity }}</span>
                            <span class="text-[10px] text-gray-400">{{ stockPercentage(supply) }}%</span>
                        </div>
                    </div>

                    <!-- Price -->
                    <div v-if="supply.purchase_price" class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <span class="text-xs text-gray-400">{{ isRtl ? 'سعر الشراء' : 'Price' }}</span>
                        <span class="text-sm font-semibold" :style="`color: ${ACCENT}`">{{ supply.purchase_price }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="supplies.data.length === 0" class="text-center py-16">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700">{{ isRtl ? 'لا توجد مستلزمات' : 'No supplies found' }}</h3>
            <p class="text-sm text-gray-400 mt-1">{{ isRtl ? 'جرّب تغيير معايير البحث' : 'Try adjusting your search criteria' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="supplies.links && supplies.last_page > 1" class="flex justify-center gap-1">
            <template v-for="link in supplies.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    class="px-3 py-1.5 text-sm rounded-lg border transition-all"
                    :class="link.active ? 'text-white border-transparent shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-gray-50'"
                    :style="link.active ? `background-color: ${ACCENT};` : ''"
                    preserve-state
                />
                <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-400" />
            </template>
        </div>
    </div>
</template>
