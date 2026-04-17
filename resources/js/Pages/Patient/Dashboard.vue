<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';
import { usePatientStatus } from '@/Composables/usePatientStatus';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();
const { bookingLabel, bookingColor, visitLabel, visitColor } = usePatientStatus();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    patient: Object,
    upcomingBookings: Array,
    recentVisits: Array,
    stats: Object,
    dentalStats: Object,
    nextDentalAppointment: Object,
    pendingFollowups: Array,
    dentalLabOrders: Array,
    pendingConsents: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}

</script>

<template>
    <div>
        <!-- Doctorato Welcome Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl mb-6">
            <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 -start-20 h-64 w-64 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative p-5 md:p-8 flex items-start gap-4 md:gap-5">
                <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0 ring-4 ring-white/10">
                    <span class="text-white font-extrabold text-xl md:text-2xl">{{ (patient?.full_name || '?').charAt(0).toUpperCase() }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] md:text-[11px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                        {{ isRtl ? 'أهلاً،' : 'Welcome,' }} {{ patient?.full_name }}
                    </h1>
                    <p class="text-sm md:text-base text-white/70 mt-2">
                        {{ isRtl ? 'إليك نظرة عامة على حسابك وحالتك الصحية' : 'Here is an overview of your account and health' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Upcoming Appointments -->
            <div class="relative bg-white rounded-2xl shadow-sm border border-[#C4A265]/20 p-5 overflow-hidden hover:shadow-lg transition"><div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'المواعيد القادمة' : 'Upcoming Appointments' }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ stats?.upcoming_bookings ?? 0 }}</p>
            </div>

            <!-- Total Visits -->
            <div class="relative bg-white rounded-2xl shadow-sm border border-[#C4A265]/20 p-5 overflow-hidden hover:shadow-lg transition"><div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    </div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ stats?.total_visits ?? 0 }}</p>
            </div>

            <!-- Unpaid Invoices -->
            <div class="relative bg-white rounded-2xl shadow-sm border border-[#C4A265]/20 p-5 overflow-hidden hover:shadow-lg transition"><div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'فواتير غير مدفوعة' : 'Unpaid Invoices' }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ stats?.unpaid_invoices_count ?? 0 }}</p>
                <p class="text-xs text-red-500 mt-1" v-if="stats?.unpaid_invoices_amount">{{ formatCurrency(stats.unpaid_invoices_amount) }}</p>
            </div>

            <!-- Active Treatment Plans -->
            <div class="relative bg-white rounded-2xl shadow-sm border border-[#C4A265]/20 p-5 overflow-hidden hover:shadow-lg transition"><div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'خطط العلاج النشطة' : 'Active Treatment Plans' }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ stats?.active_plans ?? 0 }}</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3 mb-8">
            <Link :href="lp('/bookings/create')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:from-[var(--brand-primary-hover)] hover:to-[var(--brand-primary)] shadow-md shadow-[var(--brand-primary)]/20 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ isRtl ? 'حجز موعد' : 'Book Appointment' }}
            </Link>
            <Link :href="lp('/invoices')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                {{ isRtl ? 'عرض الفواتير' : 'View Invoices' }}
            </Link>
        </div>

        <!-- Pending Consent Alert -->
        <div v-if="pendingConsents?.length" class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-amber-800">{{ isRtl ? 'موافقات تنتظر توقيعك' : 'Consents Awaiting Your Signature' }}</h3>
                    <p class="text-xs text-amber-600 mt-0.5">{{ isRtl ? 'يرجى مراجعة وتوقيع موافقات خطط العلاج' : 'Please review and sign your treatment plan consents' }}</p>
                    <div class="mt-3 space-y-2">
                        <Link v-for="plan in pendingConsents" :key="plan.id"
                              :href="lp(`/dental/consent/${plan.consent.id}`)"
                              class="flex items-center justify-between p-3 rounded-xl bg-white border border-amber-100 hover:border-amber-300 transition-colors group">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-800">{{ $localized(plan, 'title') || `#${plan.id}` }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold group-hover:bg-amber-200 transition">
                                {{ isRtl ? 'وقّع الآن' : 'Sign Now' }}
                                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dental Overview (if patient has dental visits) -->
        <div v-if="dentalStats" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                {{ isRtl ? 'طب الأسنان' : 'Dental Overview' }}
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="bg-slate-50/50 rounded-xl p-3 text-center">
                    <p class="text-xl font-bold text-[#1B365D]">{{ dentalStats.total_treatments }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'إجمالي العلاجات' : 'Total Treatments' }}</p>
                </div>
                <div class="bg-emerald-50/50 rounded-xl p-3 text-center">
                    <p class="text-xl font-bold text-emerald-600">{{ dentalStats.completed_treatments }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
                </div>
                <div class="bg-slate-50/50 rounded-xl p-3 text-center">
                    <p class="text-xl font-bold text-[#1B365D]">{{ dentalStats.active_plans }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'خطط نشطة' : 'Active Plans' }}</p>
                </div>
                <div class="bg-amber-50/50 rounded-xl p-3 text-center">
                    <p class="text-xl font-bold text-amber-600">{{ dentalStats.pending_lab_orders ?? 0 }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'طلبات معمل' : 'Lab Orders' }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <Link :href="lp('/dental/chart')" class="flex items-center gap-2 p-2.5 rounded-xl bg-gray-50 hover:bg-[var(--brand-primary)]/5 hover:border-[var(--brand-primary)]/30 border border-transparent transition text-sm group">
                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                    <span class="text-xs font-medium text-gray-600 group-hover:text-[var(--brand-primary)]">{{ isRtl ? 'المخطط' : 'Chart' }}</span>
                </Link>
                <Link :href="lp('/dental/treatments')" class="flex items-center gap-2 p-2.5 rounded-xl bg-gray-50 hover:bg-teal-50 border border-transparent hover:border-teal-200 transition text-sm group">
                    <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    <span class="text-xs font-medium text-gray-600 group-hover:text-teal-600">{{ isRtl ? 'العلاجات' : 'Treatments' }}</span>
                </Link>
                <Link :href="lp('/dental/treatment-plans')" class="flex items-center gap-2 p-2.5 rounded-xl bg-gray-50 hover:bg-slate-50 border border-transparent hover:border-slate-200 transition text-sm group">
                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    <span class="text-xs font-medium text-gray-600 group-hover:text-[#1B365D]">{{ isRtl ? 'الخطط' : 'Plans' }}</span>
                </Link>
                <Link :href="lp('/dental/xrays')" class="flex items-center gap-2 p-2.5 rounded-xl bg-gray-50 hover:bg-slate-50 border border-transparent hover:border-slate-200 transition text-sm group">
                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <span class="text-xs font-medium text-gray-600 group-hover:text-[#1B365D]">{{ isRtl ? 'الأشعة' : 'X-Rays' }}</span>
                </Link>
            </div>

            <!-- Next Dental Appointment -->
            <div v-if="nextDentalAppointment" class="mt-4 p-4 bg-gradient-to-r from-slate-50 to-teal-50 rounded-xl border border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-[#1B365D]">{{ isRtl ? 'موعدك القادم' : 'Your Next Appointment' }}</p>
                            <p class="text-xs text-[#1B365D] mt-0.5">
                                {{ nextDentalAppointment.visit_date }}
                                <span v-if="nextDentalAppointment.scheduled_time"> &middot; {{ nextDentalAppointment.scheduled_time }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-end">
                        <p class="text-xs font-medium text-gray-700">{{ $localized(nextDentalAppointment.doctor, 'name') }}</p>
                        <p class="text-[10px] text-gray-500">{{ $localized(nextDentalAppointment.service, 'name') }}</p>
                    </div>
                </div>
            </div>

            <!-- Pending Follow-ups -->
            <div v-if="pendingFollowups?.length" class="mt-3 space-y-2">
                <p class="text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'مواعيد متابعة مقترحة' : 'Suggested Follow-ups' }}</p>
                <div v-for="f in pendingFollowups" :key="f.id" class="flex items-center justify-between p-3 rounded-xl bg-amber-50/50 border border-amber-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="text-xs text-gray-700">{{ f.notes || (isRtl ? 'موعد متابعة' : 'Follow-up appointment') }}</span>
                    </div>
                    <span class="text-xs text-amber-600 font-medium">{{ f.scheduled_date }}</span>
                </div>
            </div>

            <!-- Lab Orders Ready -->
            <div v-if="dentalLabOrders?.length" class="mt-3 space-y-2">
                <p class="text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'طلبات معمل جاهزة' : 'Lab Orders Ready' }}</p>
                <div v-for="lo in dentalLabOrders" :key="lo.id" class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 border border-emerald-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <span class="text-xs text-gray-700">{{ lo.item_type }} — {{ $localized(lo.doctor, 'name') }}</span>
                    </div>
                    <span class="text-xs text-emerald-600 font-medium px-2 py-0.5 bg-emerald-100 rounded-full">{{ isRtl ? 'جاهز' : 'Ready' }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Upcoming Bookings -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">{{ isRtl ? 'المواعيد القادمة' : 'Upcoming Bookings' }}</h2>
                    <Link :href="lp('/bookings')" class="text-xs font-medium text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] transition-colors">{{ isRtl ? 'عرض الكل' : 'View All' }} &rarr;</Link>
                </div>
                <div v-if="upcomingBookings && upcomingBookings.length" class="space-y-3">
                    <div v-for="booking in upcomingBookings" :key="booking.id" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $localized(booking.service, 'name') || $localized(booking, 'service_name') }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $localized(booking.doctor, 'name') }} &middot; {{ booking.preferred_date }} {{ booking.preferred_time }}</p>
                        </div>
                        <span :class="bookingColor(booking.status)" class="text-xs font-medium px-2.5 py-1 rounded-full whitespace-nowrap ltr:ml-3 rtl:mr-3">{{ bookingLabel(booking.status) }}</span>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-400 text-sm">
                    {{ isRtl ? 'لا توجد مواعيد قادمة' : 'No upcoming bookings' }}
                </div>
            </div>

            <!-- Recent Visits -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">{{ isRtl ? 'الزيارات الأخيرة' : 'Recent Visits' }}</h2>
                    <Link :href="lp('/visits')" class="text-xs font-medium text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] transition-colors">{{ isRtl ? 'عرض الكل' : 'View All' }} &rarr;</Link>
                </div>
                <div v-if="recentVisits && recentVisits.length" class="space-y-3">
                    <Link v-for="visit in recentVisits" :key="visit.id" :href="lp('/visits/' + visit.id)" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors block">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $localized(visit.doctor, 'name') }}</p>
                            <p class="text-xs text-gray-400 mt-0.5"><svg v-if="visit.module === 'dental'" class="w-3 h-3 text-[#1B365D] inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>{{ $localized(visit.service, 'name') || $localized(visit, 'service_name') }} &middot; {{ visit.visit_date }}</p>
                            <p v-if="visit.diagnosis" class="text-xs text-gray-500 mt-1 truncate">{{ visit.diagnosis }}</p>
                        </div>
                        <span :class="visitColor(visit.status)" class="text-xs font-medium px-2.5 py-1 rounded-full whitespace-nowrap ltr:ml-3 rtl:mr-3">{{ visitLabel(visit.status) }}</span>
                    </Link>
                </div>
                <div v-else class="text-center py-8 text-gray-400 text-sm">
                    {{ isRtl ? 'لا توجد زيارات سابقة' : 'No recent visits' }}
                </div>
            </div>
        </div>
    </div>
</template>
