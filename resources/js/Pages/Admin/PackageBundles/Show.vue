<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bundle: Object,
});

/* ── Staggered reveal animation ── */
const visibleItems = ref(new Set());
function reveal(key, delay = 0) {
    setTimeout(() => visibleItems.value.add(key), delay);
}
onMounted(() => {
    reveal('header', 80);
    reveal('stats', 180);
    reveal('services', 300);
    reveal('bookings', 420);
    reveal('sidebar-details', 300);
    reveal('sidebar-pricing', 400);
    reveal('sidebar-desc', 500);
    reveal('sidebar-image', 560);
    reveal('sidebar-actions', 620);
});
function isVisible(key) { return visibleItems.value.has(key); }

/* ── Animated counters ── */
const animatedPrice = ref(0);
const animatedSavings = ref(0);
const animatedSessions = ref(0);
onMounted(() => {
    animateCounter(animatedPrice, Number(props.bundle.total_price || 0), 1200);
    animateCounter(animatedSavings, Number(props.bundle.savings || 0), 1400);
    animateCounter(animatedSessions, totalSessions.value, 800);
});
function animateCounter(target, end, duration) {
    const start = 0;
    const startTime = performance.now();
    function step(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3); // easeOutCubic
        target.value = Math.round(start + (end - start) * ease);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

/* ── Helpers ── */
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const savingsPercentage = computed(() => {
    if (!props.bundle.original_price || Number(props.bundle.original_price) <= 0) return 0;
    return Math.round((Number(props.bundle.savings) / Number(props.bundle.original_price)) * 100);
});

const totalSessions = computed(() => {
    if (!props.bundle.services) return 0;
    return props.bundle.services.reduce((sum, s) => sum + Number(s.sessions_count || 0), 0);
});

const statusColors = {
    pending: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' },
    confirmed: { bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]' },
    in_progress: { bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]' },
    completed: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    cancelled: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-500' },
};
function getStatusStyle(status) {
    return statusColors[status] || { bg: 'bg-gray-50', text: 'text-gray-600', border: 'border-gray-200', dot: 'bg-gray-400' };
}
</script>

<template>
    <AdminLayout :title="`${bundle.name_en} — Package Bundle`">
        <div class="space-y-6">

            <!-- ===== HEADER ===== -->
            <div class="show-item" :class="{ 'show-item--visible': isVisible('header') }">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                    <div class="flex-1">
                        <nav class="flex items-center text-sm text-gray-400 mb-4 space-x-2 rtl:space-x-reverse">
                            <Link href="/admin" class="hover:text-[#C4A265] transition-colors duration-200">Dashboard</Link>
                            <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <Link href="/admin/package-bundles" class="hover:text-[#C4A265] transition-colors duration-200">Package Bundles</Link>
                            <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="text-gray-700 font-medium">{{ bundle.name_en }}</span>
                        </nav>

                        <div class="flex items-center gap-5">
                            <!-- Animated image/icon with glow -->
                            <div class="relative group">
                                <div class="absolute -inset-1 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 blur-md"
                                     style="background: linear-gradient(135deg, #C4A265, #D4B87A);"></div>
                                <div v-if="bundle.image_url"
                                     class="relative w-18 h-18 rounded-2xl bg-cover bg-center border-2 border-white shadow-xl ring-2 ring-[#C4A265]/20"
                                     style="width:72px;height:72px;"
                                     :style="{ backgroundImage: `url(${bundle.image_url})` }">
                                </div>
                                <div v-else class="relative w-18 h-18 rounded-2xl flex items-center justify-center shadow-xl"
                                     style="width:72px;height:72px;background: linear-gradient(135deg, #C4A265 0%, #D4B87A 50%, #C4A265 100%);background-size:200% 200%;"
                                     :class="{ 'gradient-shift': isVisible('header') }">
                                    <svg class="w-9 h-9 text-white drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                            </div>

                            <div>
                                <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ bundle.name_en }}</h1>
                                <p class="text-sm text-gray-400 mt-0.5" dir="rtl">{{ bundle.name_ar }}</p>
                                <div class="flex items-center gap-2.5 mt-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all duration-300"
                                          :class="bundle.is_active
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm shadow-emerald-100'
                                            : 'bg-gray-50 text-gray-500 border border-gray-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="[bundle.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400']"></span>
                                        {{ bundle.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span class="text-xs text-gray-400 bg-gray-50 px-2.5 py-1 rounded-full border border-gray-100">
                                        {{ bundle.services?.length || 0 }} services
                                    </span>
                                    <span class="text-xs text-gray-400 bg-gray-50 px-2.5 py-1 rounded-full border border-gray-100">
                                        {{ totalSessions }} sessions
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 lg:mt-8">
                        <Link v-if="can('package_bundles.update')" :href="`/admin/package-bundles/${bundle.id}/edit`"
                              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold border-2 border-[#C4A265] text-[#C4A265] hover:bg-[#C4A265] hover:text-white transition-all duration-300 hover:shadow-lg hover:shadow-[#C4A265]/20 active:scale-[0.97]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit Bundle
                        </Link>
                        <Link href="/admin/package-bundles"
                              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all duration-300 active:scale-[0.97]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            Back
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ===== STATS CARDS ===== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 show-item"
                 :class="{ 'show-item--visible': isVisible('stats') }">

                <!-- Bundle Price -->
                <div class="stats-card group">
                    <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                         style="background: linear-gradient(135deg, rgba(196,162,101,0.03), rgba(196,162,101,0.08));"></div>
                    <div class="stats-card__accent" style="background: linear-gradient(135deg, #C4A265, #D4B87A);"></div>
                    <div class="relative flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3"
                             style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Bundle Price</p>
                            <p class="text-xl md:text-2xl font-extrabold mt-1 tabular-nums" style="color: #C4A265;">
                                {{ formatCurrency(animatedPrice) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Original Price -->
                <div class="stats-card group">
                    <div class="stats-card__accent bg-gray-300"></div>
                    <div class="relative flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gray-100 shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Original Price</p>
                            <p class="text-xl md:text-2xl font-extrabold text-gray-300 mt-1 line-through decoration-2 tabular-nums">
                                {{ formatCurrency(bundle.original_price) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Savings -->
                <div class="stats-card group">
                    <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 bg-gradient-to-br from-emerald-50/50 to-transparent"></div>
                    <div class="stats-card__accent bg-emerald-400"></div>
                    <div class="relative flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-emerald-50 shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Savings</p>
                            <p class="text-xl md:text-2xl font-extrabold text-emerald-600 mt-1 tabular-nums">
                                {{ formatCurrency(animatedSavings) }}
                            </p>
                        </div>
                    </div>
                    <div v-if="savingsPercentage > 0"
                         class="absolute top-3 right-3 px-2.5 py-1 rounded-lg text-[11px] font-bold text-emerald-700 bg-emerald-100 border border-emerald-200 savings-badge">
                        {{ savingsPercentage }}% OFF
                    </div>
                </div>

                <!-- Total Sessions -->
                <div class="stats-card group">
                    <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 bg-gradient-to-br from-slate-50/50 to-transparent"></div>
                    <div class="stats-card__accent bg-slate-400"></div>
                    <div class="relative flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-slate-50 shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                            <svg class="w-6 h-6 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">Total Sessions</p>
                            <p class="text-xl md:text-2xl font-extrabold text-[#1B365D] mt-1 tabular-nums">{{ animatedSessions }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">across {{ bundle.services?.length || 0 }} services</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== MAIN GRID ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT COLUMN -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Included Services -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden show-item"
                         :class="{ 'show-item--visible': isVisible('services') }">
                        <div class="px-4 md:px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: rgba(196,162,101,0.1);">
                                    <svg class="w-4 h-4" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                </div>
                                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_included_services') }}</h2>
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-[11px] font-bold text-white shadow-sm"
                                      style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                    {{ bundle.services?.length || 0 }}
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-50">
                            <div v-for="(bs, index) in bundle.services" :key="bs.id"
                                 class="service-row px-4 md:px-6 py-4 flex items-center justify-between group cursor-default"
                                 :style="{ animationDelay: `${index * 80 + 400}ms` }"
                                 :class="{ 'service-row--visible': isVisible('services') }">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-md transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg group-hover:rotate-3"
                                         style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                        {{ bs.sessions_count }}x
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 group-hover:text-[#C4A265] transition-colors duration-200">
                                            {{ bs.service?.name_en || '-' }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5" dir="rtl">{{ bs.service?.name_ar || '' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p v-if="bs.service?.price" class="text-xs text-gray-300 line-through decoration-1">
                                            {{ formatCurrency(bs.service.price * bs.sessions_count) }}
                                        </p>
                                        <p class="text-sm font-bold" style="color: #C4A265;">{{ formatCurrency(bs.bundle_price) }}</p>
                                    </div>
                                    <span v-if="Number(bs.discount_percentage) > 0"
                                          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 transition-transform duration-200 group-hover:scale-105">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                        {{ bs.discount_percentage }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Footer -->
                        <div class="px-4 md:px-6 py-4 border-t border-gray-100 flex items-center justify-between"
                             style="background: linear-gradient(135deg, rgba(196,162,101,0.03), rgba(196,162,101,0.08));">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bundle Total</span>
                            </div>
                            <span class="text-lg font-extrabold" style="color: #C4A265;">{{ formatCurrency(bundle.total_price) }}</span>
                        </div>
                    </div>

                    <!-- Recent Bookings -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden show-item"
                         :class="{ 'show-item--visible': isVisible('bookings') }">
                        <div class="px-4 md:px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-50">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                </div>
                                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_recent_bookings') }}</h2>
                            </div>
                            <Link v-if="can('package_bundle_bookings.view')" href="/admin/package-bundle-bookings"
                                  class="text-xs font-semibold hover:underline transition-all duration-200 underline-offset-4" style="color: #C4A265;">
                                View All
                            </Link>
                        </div>

                        <div v-if="bundle.bundle_bookings && bundle.bundle_bookings.length > 0" class="divide-y divide-gray-50">
                            <Link v-for="booking in bundle.bundle_bookings" :key="booking.id"
                                  :href="`/admin/package-bundle-bookings/${booking.id}`"
                                  class="px-4 md:px-6 py-4 flex items-center justify-between hover:bg-gray-50/80 transition-all duration-200 group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-100 group-hover:border-[#C4A265]/30 group-hover:bg-[#C4A265]/5 group-hover:text-[#C4A265] transition-all duration-300">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 group-hover:text-[#C4A265] transition-colors duration-200">{{ booking.booking_number }}</p>
                                        <p class="text-xs text-gray-400">{{ booking.patient?.full_name }} &mdash; {{ formatDate(booking.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold tabular-nums" style="color: #C4A265;">{{ formatCurrency(booking.total_price) }}</span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border"
                                          :class="[getStatusStyle(booking.status).bg, getStatusStyle(booking.status).text, getStatusStyle(booking.status).border]">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusStyle(booking.status).dot"></span>
                                        {{ booking.status?.replace(/_/g, ' ') }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="px-4 md:px-6 py-16 text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-400">No bookings yet</p>
                            <p class="text-xs text-gray-300 mt-1">Bookings will appear here once customers book this bundle</p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDEBAR -->
                <div class="space-y-6">

                    <!-- Bundle Details -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden show-item"
                         :class="{ 'show-item--visible': isVisible('sidebar-details') }">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-gray-50">
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_details') }}</h3>
                        </div>
                        <div class="p-4 md:p-6 space-y-0">
                            <div class="detail-row flex items-center justify-between py-3 border-b border-gray-50 group">
                                <span class="text-xs text-gray-400 font-medium">Status</span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="bundle.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="bundle.is_active ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                    {{ bundle.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="detail-row flex items-center justify-between py-3 border-b border-gray-50">
                                <span class="text-xs text-gray-400 font-medium">Display Order</span>
                                <span class="text-sm font-semibold text-gray-700 tabular-nums">{{ bundle.display_order }}</span>
                            </div>
                            <div class="detail-row flex items-center justify-between py-3 border-b border-gray-50">
                                <span class="text-xs text-gray-400 font-medium">Services</span>
                                <span class="text-sm font-semibold text-gray-700">{{ bundle.services?.length || 0 }}</span>
                            </div>
                            <div class="detail-row flex items-center justify-between py-3 border-b border-gray-50">
                                <span class="text-xs text-gray-400 font-medium">Sessions</span>
                                <span class="text-sm font-semibold text-gray-700 tabular-nums">{{ totalSessions }}</span>
                            </div>
                            <div class="detail-row flex items-center justify-between py-3 border-b border-gray-50">
                                <span class="text-xs text-gray-400 font-medium">Created</span>
                                <span class="text-sm font-medium text-gray-600">{{ formatDate(bundle.created_at) }}</span>
                            </div>
                            <div class="detail-row flex items-center justify-between py-3">
                                <span class="text-xs text-gray-400 font-medium">Updated</span>
                                <span class="text-sm font-medium text-gray-600">{{ formatDate(bundle.updated_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Breakdown -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden show-item"
                         :class="{ 'show-item--visible': isVisible('sidebar-pricing') }">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background-color: rgba(196,162,101,0.1);">
                                <svg class="w-3.5 h-3.5" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_pricing') }}</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div v-for="bs in bundle.services" :key="'price-' + bs.id"
                                     class="flex items-center justify-between text-xs group">
                                    <span class="text-gray-500 truncate mr-3 group-hover:text-gray-700 transition-colors duration-200">{{ bs.service?.name_en || '-' }}</span>
                                    <span class="font-semibold text-gray-700 whitespace-nowrap tabular-nums">{{ formatCurrency(bs.bundle_price) }}</span>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-t border-dashed border-gray-200 space-y-2.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-400">Original Total</span>
                                    <span class="text-gray-300 line-through tabular-nums">{{ formatCurrency(bundle.original_price) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-700">Bundle Price</span>
                                    <span class="text-xl font-extrabold" style="color: #C4A265;">{{ formatCurrency(bundle.total_price) }}</span>
                                </div>
                                <div v-if="Number(bundle.savings) > 0" class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-emerald-600">You Save</span>
                                    <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(bundle.savings) }} ({{ savingsPercentage }}%)</span>
                                </div>
                            </div>

                            <!-- Savings progress bar -->
                            <div v-if="savingsPercentage > 0" class="mt-4">
                                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000 ease-out savings-bar"
                                         :style="{ width: isVisible('sidebar-pricing') ? savingsPercentage + '%' : '0%', background: 'linear-gradient(90deg, #C4A265, #10B981)' }">
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1.5 text-right">{{ savingsPercentage }}% discount applied</p>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="bundle.description_en || bundle.description_ar"
                         class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden show-item"
                         :class="{ 'show-item--visible': isVisible('sidebar-desc') }">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-slate-50">
                                <svg class="w-3.5 h-3.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_description') }}</h3>
                        </div>
                        <div class="p-6">
                            <p v-if="bundle.description_en" class="text-sm text-gray-600 leading-relaxed">{{ bundle.description_en }}</p>
                            <p v-if="bundle.description_ar" class="text-sm text-gray-400 mt-3 leading-relaxed border-t border-gray-50 pt-3" dir="rtl">{{ bundle.description_ar }}</p>
                        </div>
                    </div>

                    <!-- Image -->
                    <div v-if="bundle.image_url"
                         class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden show-item"
                         :class="{ 'show-item--visible': isVisible('sidebar-image') }">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center bg-slate-50">
                                <svg class="w-3.5 h-3.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_image') }}</h3>
                        </div>
                        <div class="p-4">
                            <div class="relative group overflow-hidden rounded-xl">
                                <img :src="bundle.image_url" :alt="bundle.name_en"
                                     class="w-full h-52 object-cover transition-transform duration-500 group-hover:scale-105" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="space-y-2.5 show-item" :class="{ 'show-item--visible': isVisible('sidebar-actions') }">
                        <Link v-if="can('package_bundles.update')" :href="`/admin/package-bundles/${bundle.id}/edit`"
                              class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-white text-sm font-semibold transition-all duration-300 hover:shadow-lg hover:shadow-[#C4A265]/25 active:scale-[0.97]"
                              style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit Bundle
                        </Link>
                        <Link href="/admin/package-bundles"
                              class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-gray-50 border border-gray-200 text-gray-600 text-sm font-medium hover:bg-gray-100 hover:border-gray-300 transition-all duration-200 active:scale-[0.97]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            Back to Bundles
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ── Reveal animations ── */
.show-item {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.show-item--visible {
    opacity: 1;
    transform: translateY(0);
}

/* ── Stats cards ── */
.stats-card {
    position: relative;
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    border: 1px solid rgb(243, 244, 246);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
}
.stats-card__accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: 0 0 4px 4px;
}

/* ── Service rows stagger ── */
.service-row {
    opacity: 0;
    transform: translateX(-15px);
    transition: background 0.2s ease;
}
.service-row--visible {
    animation: slideInRow 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes slideInRow {
    from { opacity: 0; transform: translateX(-15px); }
    to { opacity: 1; transform: translateX(0); }
}
.service-row:hover {
    background: rgba(196, 162, 101, 0.02);
}

/* ── Savings badge bounce ── */
.savings-badge {
    animation: badgeBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 1.2s both;
}
@keyframes badgeBounce {
    from { opacity: 0; transform: scale(0.5); }
    to { opacity: 1; transform: scale(1); }
}

/* ── Savings bar ── */
.savings-bar {
    transition: width 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.5s;
}

/* ── Gradient shift for icon ── */
.gradient-shift {
    animation: gradientMove 4s ease-in-out infinite;
}
@keyframes gradientMove {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

/* ── Detail rows hover ── */
.detail-row {
    transition: background 0.2s ease;
}
.detail-row:hover {
    background: rgba(249, 250, 251, 0.8);
}
</style>
