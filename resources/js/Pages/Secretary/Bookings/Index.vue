<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bookings: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const source = ref(props.filters?.source || '');
const moduleFilter = ref(props.filters?.module || '');

const modules = computed(() => page.props.modules || {});
const clinicalSlugs = ['derma', 'dental', 'pediatric'];
const activeModules = computed(() => {
    const mods = [];
    if (modules.value) {
        Object.values(modules.value).forEach(m => {
            if (m.enabled && clinicalSlugs.includes(m.slug)) mods.push(m);
        });
    }
    return mods;
});

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch(status, () => applyFilters());
watch(source, () => applyFilters());
watch(moduleFilter, () => applyFilters());

function applyFilters() {
    router.get('/secretary/bookings', {
        search: search.value || undefined,
        status: status.value || undefined,
        source: source.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

const statusConfig = {
    unconfirmed:  { label: 'Unconfirmed', labelAr: 'غير مؤكد',   bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   border: 'border-amber-200' },
    confirmed:    { label: 'Confirmed',   labelAr: 'مؤكد',       bg: 'bg-slate-50',    text: 'text-[#1B365D]',    dot: 'bg-[#1B365D]',    border: 'border-slate-200' },
    in_progress:  { label: 'In Progress', labelAr: 'جاري',       bg: 'bg-slate-50',  text: 'text-[#1B365D]',  dot: 'bg-[#1B365D]',  border: 'border-slate-200' },
    completed:    { label: 'Completed',   labelAr: 'مكتمل',      bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', border: 'border-emerald-200' },
    cancelled:    { label: 'Cancelled',   labelAr: 'ملغي',       bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     border: 'border-red-200' },
    new:          { label: 'New',         labelAr: 'جديد',       bg: 'bg-yellow-50',  text: 'text-amber-700',  dot: 'bg-amber-500',  border: 'border-yellow-200' },
    pending:      { label: 'Pending',     labelAr: 'معلق',       bg: 'bg-yellow-50',  text: 'text-amber-700',  dot: 'bg-amber-500',  border: 'border-yellow-200' },
    contacted:    { label: 'Contacted',   labelAr: 'تم التواصل', bg: 'bg-slate-50',     text: 'text-[#1B365D]',     dot: 'bg-[#1B365D]',     border: 'border-slate-200' },
};

function getStatus(s) {
    return statusConfig[s] || { label: s, labelAr: s, bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-500', border: 'border-gray-200' };
}

const sourceConfig = {
    website:   { label: 'Website',   labelAr: 'الموقع',    bg: 'bg-slate-50',  text: 'text-[#1B365D]' },
    secretary: { label: 'Secretary', labelAr: 'السكرتارية', bg: 'bg-teal-50',  text: 'text-teal-600' },
    admin:     { label: 'Admin',     labelAr: 'الإدارة',    bg: 'bg-amber-50', text: 'text-amber-600' },
};

function getSource(s) {
    return sourceConfig[s] || { label: s, labelAr: s, bg: 'bg-gray-50', text: 'text-gray-600' };
}

const invoiceStatusConfig = {
    unpaid:  { label: 'Unpaid',  labelAr: 'غير مدفوع',      bg: 'bg-red-50',     text: 'text-red-600' },
    partial: { label: 'Partial', labelAr: 'مدفوع جزئياً',   bg: 'bg-amber-50',   text: 'text-amber-600' },
    paid:    { label: 'Paid',    labelAr: 'مدفوع',          bg: 'bg-emerald-50', text: 'text-emerald-600' },
};

function getInvoiceStatus(booking) {
    if (!booking.invoice) return null;
    return invoiceStatusConfig[booking.invoice.status] || { label: booking.invoice.status, labelAr: booking.invoice.status, bg: 'bg-gray-50', text: 'text-gray-600' };
}

function getPatientName(booking) {
    return booking.patient?.full_name || booking.full_name || '-';
}

function getPatientPhone(booking) {
    return booking.patient?.phone || booking.phone || '-';
}

function getServicesText(booking) {
    if (booking.booking_services?.length > 0) {
        return booking.booking_services.map(bs => isRtl.value ? (bs.service?.name_ar || bs.service?.name_en) : (bs.service?.name_en || bs.service?.name_ar)).join(', ');
    }
    return '-';
}

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
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-6 sm:pt-8 pb-8 sm:pb-10 bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 30% 50%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#0d9488] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'إدارة الحجوزات' : 'Booking Management' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'الحجوزات' : 'Bookings' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'إدارة جميع الحجوزات والمواعيد' : 'Manage all bookings and appointments' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link href="/secretary/bundle-bookings/create" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            {{ isRtl ? 'حجز باقة' : 'Book Package' }}
                        </Link>
                        <Link href="/secretary/bookings/create" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0d9488] hover:bg-[#0b8278] transition-all shadow-lg shadow-[#0d9488]/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ isRtl ? 'حجز جديد' : 'New Booking' }}
                        </Link>
                    </div>
                </div>

                <!-- Module Tabs in Hero -->
                <div v-if="activeModules.length > 1" class="flex gap-2 flex-wrap mt-5">
                    <button
                        @click="moduleFilter = ''"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all"
                        :class="moduleFilter === '' ? 'bg-white/20 text-white' : 'text-gray-400 hover:text-white hover:bg-white/10'"
                    >
                        {{ isRtl ? 'الكل' : 'All' }}
                    </button>
                    <button
                        v-for="mod in activeModules"
                        :key="mod.slug"
                        @click="moduleFilter = mod.slug"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-1.5"
                        :class="moduleFilter === mod.slug ? 'bg-white/20 text-white' : 'text-gray-400 hover:text-white hover:bg-white/10'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                        <span>{{ isRtl ? mod.name_ar : mod.name_en }}</span>
                    </button>
                </div>

                <!-- Filters in Hero -->
                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <div class="relative flex-1 min-w-[220px] max-w-sm">
                        <svg class="absolute left-3 rtl:left-auto rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالاسم، الهاتف، رقم الحجز...' : 'Search by name, phone, booking #...'"
                            class="doctorato-input w-full ltr:pl-10 ltr:pr-4 rtl:pr-10 rtl:pl-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] transition"
                        />
                    </div>
                    <select v-model="status" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                        <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                        <option value="unconfirmed">{{ isRtl ? 'غير مؤكد' : 'Unconfirmed' }}</option>
                        <option value="confirmed">{{ isRtl ? 'مؤكد' : 'Confirmed' }}</option>
                        <option value="in_progress">{{ isRtl ? 'جاري' : 'In Progress' }}</option>
                        <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                        <option value="cancelled">{{ isRtl ? 'ملغي' : 'Cancelled' }}</option>
                    </select>
                    <select v-model="source" class="px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                        <option value="">{{ isRtl ? 'جميع المصادر' : 'All Sources' }}</option>
                        <option value="website">{{ isRtl ? 'الموقع' : 'Website' }}</option>
                        <option value="secretary">{{ isRtl ? 'السكرتارية' : 'Secretary' }}</option>
                        <option value="admin">{{ isRtl ? 'الإدارة' : 'Admin' }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ═══ BOOKING CARDS ═══ -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="booking in bookings.data"
                :key="booking.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5">
                    <!-- Status Dot & Patient -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div :class="[getStatus(booking.status).bg, getStatus(booking.status).border]" class="w-12 h-12 rounded-xl border flex items-center justify-center flex-shrink-0">
                            <span :class="getStatus(booking.status).dot" class="w-3 h-3 rounded-full"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-gray-900 text-[15px]">{{ getPatientName(booking) }}</p>
                                <span class="text-xs font-mono text-[#0d9488] font-semibold">{{ booking.booking_number || `#${booking.id}` }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span :class="[getStatus(booking.status).bg, getStatus(booking.status).text]" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold">
                                    {{ isRtl ? getStatus(booking.status).labelAr : getStatus(booking.status).label }}
                                </span>
                                <span v-if="booking.source" :class="[getSource(booking.source).bg, getSource(booking.source).text]" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold">
                                    {{ isRtl ? getSource(booking.source).labelAr : getSource(booking.source).label }}
                                </span>
                                <span v-if="getInvoiceStatus(booking)" :class="[getInvoiceStatus(booking).bg, getInvoiceStatus(booking).text]" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold">
                                    {{ isRtl ? getInvoiceStatus(booking).labelAr : getInvoiceStatus(booking).label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="flex items-center gap-5 sm:gap-6">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الهاتف' : 'Phone' }}</p>
                            <p class="text-xs font-medium text-gray-700 mt-0.5 dir-ltr">{{ getPatientPhone(booking) }}</p>
                        </div>
                        <div class="text-center max-w-[150px]">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الخدمات' : 'Services' }}</p>
                            <p class="text-xs font-medium text-gray-700 mt-0.5 truncate">{{ getServicesText(booking) }}</p>
                            <span v-if="booking.booking_services_count > 1" class="text-[10px] text-gray-400">({{ booking.booking_services_count }} {{ isRtl ? 'خدمات' : 'services' }})</span>
                        </div>
                    </div>

                    <!-- Action -->
                    <Link
                        :href="`/secretary/bookings/${booking.id}`"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-[#0d9488] bg-[#0d9488]/5 hover:bg-[#0d9488]/10 rounded-xl transition-colors flex-shrink-0"
                    >
                        {{ isRtl ? 'عرض التفاصيل' : 'View Details' }}
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="bookings.data?.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد حجوزات' : 'No bookings found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'جرب تغيير الفلاتر أو أنشئ حجز جديد' : 'Try adjusting your filters or create a new booking' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="bookings.links?.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
            <p class="text-xs text-gray-500">
                {{ isRtl ? 'عرض' : 'Showing' }} <span class="font-semibold">{{ bookings.from }}</span> {{ isRtl ? 'إلى' : 'to' }} <span class="font-semibold">{{ bookings.to }}</span> {{ isRtl ? 'من' : 'of' }} <span class="font-semibold">{{ bookings.total }}</span> {{ isRtl ? 'نتيجة' : 'results' }}
            </p>
            <nav class="flex items-center gap-1">
                <template v-for="link in bookings.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#0d9488] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </nav>
        </div>
    </div>
</template>
