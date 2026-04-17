<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ invoices: Object, filters: Object });

const modules = computed(() => page.props.modules || {});
const clinicalSlugs = ['derma', 'dental', 'pediatric'];
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([slug, m]) => m.is_enabled !== false && clinicalSlugs.includes(slug))
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const moduleFilter = ref(props.filters?.module || '');

let timeout;
watch(search, () => { clearTimeout(timeout); timeout = setTimeout(applyFilters, 400); });
watch([status, moduleFilter], () => applyFilters());

function applyFilters() {
    router.get('/secretary/invoices', { search: search.value || undefined, status: status.value || undefined, module: moduleFilter.value || undefined }, { preserveState: true, replace: true });
}

const { formatCurrency } = useCurrency();

const statusConfig = {
    unpaid:    { label: 'Unpaid',    labelAr: 'غير مدفوع',    bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     border: 'border-red-200' },
    partial:   { label: 'Partial',   labelAr: 'مدفوع جزئياً', bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   border: 'border-amber-200' },
    paid:      { label: 'Paid',      labelAr: 'مدفوع',        bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', border: 'border-emerald-200' },
    cancelled: { label: 'Cancelled', labelAr: 'ملغي',         bg: 'bg-gray-50',    text: 'text-gray-600',    dot: 'bg-gray-400',    border: 'border-gray-200' },
};

function getStatus(s) {
    return statusConfig[s] || { label: s, labelAr: s, bg: 'bg-gray-50', text: 'text-gray-600', dot: 'bg-gray-400', border: 'border-gray-200' };
}

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    return d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

// Stats
const totalRevenue = computed(() => props.invoices?.data?.reduce((s, inv) => s + Number(inv.total || 0), 0) || 0);
const totalPaid = computed(() => props.invoices?.data?.reduce((s, inv) => s + Number(inv.paid_amount || 0), 0) || 0);
const unpaidCount = computed(() => props.invoices?.data?.filter(i => i.status === 'unpaid').length || 0);

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- ═══ HERO HEADER ═══ -->
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 70% 50%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#0d9488] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'المالية' : 'Finance' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'الفواتير' : 'Invoices' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'إدارة فواتير المرضى والمدفوعات' : 'Manage patient invoices and payments' }}</p>
                    </div>
                    <Link href="/secretary/invoices/create" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0d9488] hover:bg-[#0b8278] transition-all shadow-lg shadow-[#0d9488]/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'فاتورة جديدة' : 'New Invoice' }}
                    </Link>
                </div>

                <!-- Filters in Hero -->
                <div class="flex flex-wrap items-center gap-3 mt-6">
                    <div class="relative flex-1 min-w-[220px] max-w-sm">
                        <svg class="absolute left-3 rtl:left-auto rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث برقم الفاتورة أو المريض...' : 'Search by invoice # or patient...'"
                            class="w-full ltr:pl-10 ltr:pr-4 rtl:pr-10 rtl:pl-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] transition"
                        />
                    </div>
                    <select v-model="status" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                        <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                        <option value="unpaid">{{ isRtl ? 'غير مدفوع' : 'Unpaid' }}</option>
                        <option value="partial">{{ isRtl ? 'مدفوع جزئياً' : 'Partial' }}</option>
                        <option value="paid">{{ isRtl ? 'مدفوع' : 'Paid' }}</option>
                    </select>
                    <select v-if="activeModules.length > 1" v-model="moduleFilter" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                        <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                        <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
                    </select>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الإجمالي' : 'Total Revenue' }}</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ formatCurrency(totalRevenue) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'المحصل' : 'Collected' }}</p>
                        <p class="text-2xl font-bold text-emerald-400 mt-1">{{ formatCurrency(totalPaid) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'غير مدفوع' : 'Unpaid' }}</p>
                        <p class="text-2xl font-bold text-red-400 mt-1">{{ unpaidCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ INVOICE CARDS ═══ -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="inv in invoices.data"
                :key="inv.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5">
                    <!-- Status & Invoice Info -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div :class="[getStatus(inv.status).bg, getStatus(inv.status).border]" class="w-12 h-12 rounded-xl border flex items-center justify-center flex-shrink-0">
                            <span :class="getStatus(inv.status).dot" class="w-3 h-3 rounded-full"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-gray-900 text-[15px]">{{ inv.patient?.full_name || '-' }}</p>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-mono font-semibold text-[#0d9488]">{{ inv.invoice_number }}</span>
                                <span :class="[getStatus(inv.status).bg, getStatus(inv.status).text]" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold">
                                    {{ isRtl ? getStatus(inv.status).labelAr : getStatus(inv.status).label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="flex items-center gap-4 sm:gap-6 flex-wrap">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</p>
                            <p class="text-xs font-semibold text-gray-700 mt-0.5">{{ formatDate(inv.invoice_date) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الإجمالي' : 'Total' }}</p>
                            <p class="text-sm font-bold text-gray-800 mt-0.5">{{ formatCurrency(inv.total) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'المدفوع' : 'Paid' }}</p>
                            <p class="text-sm font-bold text-emerald-600 mt-0.5">{{ formatCurrency(inv.paid_amount) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-red-400 font-bold uppercase">{{ isRtl ? 'المتبقي' : 'Due' }}</p>
                            <p class="text-sm font-bold text-red-600 mt-0.5">{{ formatCurrency(Number(inv.total || 0) - Number(inv.paid_amount || 0)) }}</p>
                        </div>
                    </div>

                    <!-- Action -->
                    <Link
                        :href="`/secretary/invoices/${inv.id}`"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-[#0d9488] bg-[#0d9488]/5 hover:bg-[#0d9488]/10 rounded-xl transition-colors flex-shrink-0"
                    >
                        {{ isRtl ? 'عرض التفاصيل' : 'View Details' }}
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="invoices.data?.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد فواتير' : 'No invoices found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'جرب تغيير الفلاتر أو أنشئ فاتورة جديدة' : 'Try adjusting your filters or create a new invoice' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="invoices.links?.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
            <p class="text-xs text-gray-500">
                {{ isRtl ? 'عرض' : 'Showing' }} <span class="font-semibold">{{ invoices.from }}</span> {{ isRtl ? 'إلى' : 'to' }} <span class="font-semibold">{{ invoices.to }}</span> {{ isRtl ? 'من' : 'of' }} <span class="font-semibold">{{ invoices.total }}</span> {{ isRtl ? 'نتيجة' : 'results' }}
            </p>
            <nav class="flex items-center gap-1">
                <template v-for="link in invoices.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#0d9488] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </nav>
        </div>
    </div>
</template>
