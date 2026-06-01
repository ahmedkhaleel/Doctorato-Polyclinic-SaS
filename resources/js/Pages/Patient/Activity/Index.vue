<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    events: { type: Array, default: () => [] },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

function title(e) {
    return locale.value === 'ar' ? e.title_ar : e.title_en;
}

function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleString(isRtl.value ? 'ar-EG' : 'en-US',
            { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch { return d; }
}

// Per-type style: icon path + ring color
const STYLES = {
    booking: {
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        color: 'bg-blue-50 text-blue-600 ring-blue-100',
    },
    visit: {
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        color: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    },
    invoice: {
        icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        color: 'bg-amber-50 text-amber-600 ring-amber-100',
    },
    loyalty: {
        icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        color: 'bg-[#FAF7F0] text-[#C4A265] ring-[#C4A265]/20',
    },
    prescription: {
        icon: 'M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3',
        color: 'bg-violet-50 text-violet-600 ring-violet-100',
    },
    review: {
        icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        color: 'bg-pink-50 text-pink-600 ring-pink-100',
    },
};

function styleFor(type) {
    return STYLES[type] || { icon: 'M13 16h-1v-4h-1m1-4h.01', color: 'bg-gray-50 text-gray-500 ring-gray-100' };
}
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-[#1B365D] to-[#22406F] rounded-2xl p-6 md:p-8 mb-6 text-white relative overflow-hidden">
            <div class="absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/15 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-2">
                    <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                    <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">
                        {{ isRtl ? 'سجل النشاط' : 'Activity Log' }}
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold mb-2">
                    {{ isRtl ? 'كل ما حدث في حسابك' : 'Everything that happened in your account' }}
                </h1>
                <p class="text-sm text-white/70 max-w-xl">
                    {{ isRtl
                        ? 'حجوزات، زيارات، فواتير، وصفات، نقاط ولاء، تقييمات — كلها في تسلسل زمني واحد.'
                        : 'Bookings, visits, invoices, prescriptions, loyalty points, reviews — all in one chronological feed.' }}
                </p>
            </div>
        </div>

        <!-- Timeline -->
        <div v-if="events.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="relative p-5 sm:p-6">
                <!-- vertical line -->
                <div class="absolute top-0 bottom-0 ltr:left-9 rtl:right-9 w-px bg-gradient-to-b from-gray-200 via-gray-100 to-transparent pointer-events-none"></div>

                <div class="space-y-5">
                    <component v-for="e in events" :key="e.id"
                               :is="e.href ? Link : 'div'"
                               :href="e.href ? lp(e.href) : undefined"
                               class="relative flex items-start gap-4 group">
                        <!-- Icon node -->
                        <div :class="styleFor(e.type).color"
                             class="relative w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ring-4 z-10 transition group-hover:scale-110">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="styleFor(e.type).icon" />
                            </svg>
                        </div>
                        <!-- Body -->
                        <div class="flex-1 min-w-0 pt-1.5">
                            <div class="flex items-baseline justify-between gap-3 flex-wrap">
                                <p class="text-sm font-bold text-gray-800">{{ title(e) }}</p>
                                <p class="text-[10px] text-gray-400 tabular-nums whitespace-nowrap">{{ fmtDate(e.occurred_at) }}</p>
                            </div>
                            <p v-if="e.detail" class="text-xs text-gray-500 mt-1 truncate">{{ e.detail }}</p>
                            <p v-if="e.subtype === 'cancelled'" class="text-[10px] text-red-600 font-semibold uppercase mt-0.5">
                                {{ isRtl ? 'ملغي' : 'Cancelled' }}
                            </p>
                            <p v-else-if="e.subtype === 'paid'" class="text-[10px] text-emerald-600 font-semibold uppercase mt-0.5">
                                {{ isRtl ? 'مدفوعة' : 'Paid' }}
                            </p>
                            <p v-else-if="e.subtype === 'unpaid'" class="text-[10px] text-amber-600 font-semibold uppercase mt-0.5">
                                {{ isRtl ? 'غير مدفوعة' : 'Unpaid' }}
                            </p>
                        </div>
                    </component>
                </div>
            </div>
        </div>

        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <p class="text-sm text-gray-500">
                {{ isRtl ? 'لا توجد أنشطة بعد. ابدأ بحجز موعدك الأول!' : 'No activity yet. Book your first appointment!' }}
            </p>
            <Link :href="lp('/bookings/create')" class="inline-block mt-4 px-5 py-2 rounded-xl bg-[var(--brand-primary)] text-white text-sm font-semibold hover:opacity-90">
                {{ isRtl ? 'احجز الآن' : 'Book now' }}
            </Link>
        </div>
    </div>
</template>
