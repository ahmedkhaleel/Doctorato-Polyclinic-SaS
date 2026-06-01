<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();
defineOptions({ layout: PatientLayout });

const props = defineProps({
    treatmentSummary: Object,
    activePlans: Array,
    upcomingFollowups: Array,
    pendingLabOrders: Array,
    pendingConsents: Array,
    nextAppointment: Object,
    recentXrays: Array,
    chartSummary: Object,
    visitCount: Number,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function $localized(obj, field) {
    if (!obj) return '';
    return obj[field + '_' + (locale.value === 'ar' ? 'ar' : 'en')] || obj[field + '_en'] || obj[field] || '';
}

function daysUntil(dateStr) {
    if (!dateStr) return null;
    return Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

const completionRate = computed(() => {
    if (!props.treatmentSummary?.total) return 0;
    return Math.round((props.treatmentSummary.completed / props.treatmentSummary.total) * 100);
});

const labStatusConfig = {
    ordered: { ar: 'تم الطلب', en: 'Ordered', color: 'bg-gray-100 text-gray-600', icon: '' },
    in_production: { ar: 'قيد التصنيع', en: 'In Production', color: 'bg-slate-100 text-[#1B365D]', icon: '' },
    ready: { ar: 'جاهز للاستلام', en: 'Ready for Pickup', color: 'bg-emerald-100 text-emerald-700', icon: '✅' },
    delivered: { ar: 'تم التسليم', en: 'Delivered', color: 'bg-teal-100 text-teal-700', icon: '' },
};

const conditionLabels = {
    healthy: { ar: 'سليم', en: 'Healthy', color: 'bg-emerald-100 text-emerald-700' },
    decayed: { ar: 'تسوس', en: 'Decayed', color: 'bg-red-100 text-red-700' },
    filled: { ar: 'حشوة', en: 'Filled', color: 'bg-slate-100 text-[#1B365D]' },
    missing: { ar: 'مفقود', en: 'Missing', color: 'bg-gray-100 text-gray-600' },
    crown: { ar: 'تاج', en: 'Crown', color: 'bg-amber-100 text-amber-700' },
    bridge: { ar: 'جسر', en: 'Bridge', color: 'bg-slate-100 text-[#1B365D]' },
    implant: { ar: 'زرعة', en: 'Implant', color: 'bg-slate-100 text-[#1B365D]' },
    root_canal: { ar: 'علاج عصب', en: 'Root Canal', color: 'bg-amber-100 text-[#C4A265]' },
    extracted: { ar: 'مقلوع', en: 'Extracted', color: 'bg-gray-200 text-gray-700' },
};

const chartTotal = computed(() => Object.values(props.chartSummary || {}).reduce((a, b) => a + b, 0));

const quickLinks = computed(() => [
    { href: lp('/dental/chart'), label: isRtl.value ? 'خريطة الأسنان' : 'Dental Chart', icon: 'tooth', color: 'from-slate-400 to-[#1B365D]' },
    { href: lp('/dental/treatments'), label: isRtl.value ? 'العلاجات' : 'Treatments', icon: 'treatment', color: 'from-teal-400 to-teal-500' },
    { href: lp('/dental/treatment-plans'), label: isRtl.value ? 'خطط العلاج' : 'Plans', icon: 'plan', color: 'from-slate-400 to-[#1B365D]' },
    { href: lp('/dental/xrays'), label: isRtl.value ? 'الأشعة' : 'X-Rays', icon: 'xray', color: 'from-slate-400 to-[#1B365D]' },
    { href: lp('/dental/lab-orders'), label: isRtl.value ? 'طلبات المعمل' : 'Lab Orders', icon: 'lab', color: 'from-amber-400 to-amber-500' },
    { href: lp('/dental/followups'), label: isRtl.value ? 'المتابعات' : 'Follow-ups', icon: 'followup', color: 'from-amber-400 to-[#C4A265]' },
]);
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-teal-800 p-6 lg:p-8">
            <div class="absolute -top-16 ltr:-right-16 rtl:-left-16 w-56 h-56 bg-slate-400/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-12 ltr:-left-12 rtl:-right-12 w-40 h-40 bg-teal-300/15 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2C9.5 2 7 4 7 7c0 2-.5 4-1 6-.5 2-.5 4 0 5.5S7.5 21 8.5 21s1.5-1 2-3c.5-1.5 1-2 1.5-2s1 .5 1.5 2c.5 2 1 3 2 3s2-1 2.5-2.5.5-3.5 0-5.5c-.5-2-1-4-1-6 0-3-2.5-5-5-5z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-bold text-white">{{ isRtl ? 'ملخص أسنانك' : 'Your Dental Summary' }}</h1>
                        <p class="text-slate-100/70 text-sm">{{ isRtl ? 'نظرة شاملة على حالة أسنانك وعلاجاتك' : 'A comprehensive view of your dental health and treatments' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Consent Alert -->
        <div v-if="pendingConsents?.length" class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-amber-800">{{ isRtl ? 'موافقات تنتظر توقيعك' : 'Consents Awaiting Your Signature' }}</h3>
                    <p class="text-xs text-amber-600 mt-0.5">{{ isRtl ? 'يرجى مراجعة وتوقيع موافقات خطط العلاج التالية' : 'Please review and sign the following treatment plan consents' }}</p>
                    <div class="mt-3 space-y-2">
                        <Link v-for="plan in pendingConsents" :key="plan.id"
                              :href="lp(`/dental/consent/${plan.consent.id}`)"
                              class="flex items-center justify-between p-3 rounded-xl bg-white border border-amber-100 hover:border-amber-300 transition-colors group">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-800">{{ $localized(plan, 'title') || `#${plan.id}` }}</span>
                                <span class="text-xs text-gray-400">— {{ $localized(plan.doctor, 'name') }}</span>
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-[#1B365D]">{{ treatmentSummary?.total || 0 }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ isRtl ? 'إجمالي العلاجات' : 'Total Treatments' }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600">{{ treatmentSummary?.completed || 0 }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-[#1B365D]">{{ treatmentSummary?.in_progress || 0 }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ isRtl ? 'قيد التنفيذ' : 'In Progress' }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                <p class="text-2xl font-bold text-gray-700">{{ visitCount || 0 }}</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ isRtl ? 'زيارات أسنان' : 'Dental Visits' }}</p>
            </div>
        </div>

        <!-- Treatment Progress -->
        <div v-if="treatmentSummary?.total > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تقدم العلاج' : 'Treatment Progress' }}</h3>
                <span class="text-sm font-bold" :class="completionRate >= 70 ? 'text-emerald-600' : completionRate >= 40 ? 'text-amber-600' : 'text-[#1B365D]'">{{ completionRate }}%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3 mb-3">
                <div class="h-3 rounded-full transition-all duration-700"
                     :class="completionRate >= 70 ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : completionRate >= 40 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : 'bg-gradient-to-r from-slate-400 to-[#1B365D]'"
                     :style="{ width: `${completionRate}%` }"></div>
            </div>
            <div class="flex items-center gap-4 text-[11px]">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> {{ treatmentSummary.completed }} {{ isRtl ? 'مكتمل' : 'completed' }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#1B365D]"></span> {{ treatmentSummary.in_progress }} {{ isRtl ? 'قيد التنفيذ' : 'in progress' }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-gray-300"></span> {{ treatmentSummary.planned }} {{ isRtl ? 'مخطط' : 'planned' }}</span>
            </div>
        </div>

        <!-- Next Appointment -->
        <div v-if="nextAppointment" class="bg-gradient-to-r from-slate-50 to-teal-50 rounded-2xl border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-[#1B365D]">{{ isRtl ? 'موعدك القادم' : 'Your Next Appointment' }}</p>
                        <p class="text-xs text-[#1B365D] mt-0.5">
                            {{ formatDate(nextAppointment.visit_date) }}
                            <span v-if="nextAppointment.scheduled_time"> &middot; {{ nextAppointment.scheduled_time }}</span>
                        </p>
                        <p class="text-[10px] text-gray-500 mt-0.5">{{ $localized(nextAppointment.doctor, 'name') }} &middot; {{ $localized(nextAppointment.service, 'name') }}</p>
                    </div>
                </div>
                <div v-if="daysUntil(nextAppointment.visit_date) !== null" class="text-center">
                    <p class="text-2xl font-bold text-[#1B365D]">{{ Math.max(0, daysUntil(nextAppointment.visit_date)) }}</p>
                    <p class="text-[10px] text-[#1B365D]">{{ isRtl ? 'يوم' : 'days' }}</p>
                </div>
            </div>
        </div>

        <!-- Two columns: Active Plans + Followups -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Active Treatment Plans -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'خطط العلاج النشطة' : 'Active Treatment Plans' }}</h3>
                    <Link :href="lp('/dental/treatment-plans')" class="text-xs text-[#1B365D] hover:text-[#1B365D] font-medium">{{ isRtl ? 'عرض الكل' : 'View All' }}</Link>
                </div>
                <div v-if="activePlans?.length" class="divide-y divide-gray-50">
                    <Link v-for="plan in activePlans" :key="plan.id"
                          :href="lp(`/dental/treatment-plans/${plan.id}`)"
                          class="block px-5 py-4 hover:bg-slate-50/30 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $localized(plan, 'title') || `#${plan.id}` }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $localized(plan.doctor, 'name') }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-50 text-[#1B365D]">
                                    {{ plan.completed_sessions }}/{{ plan.estimated_sessions }}
                                </span>
                                <!-- Consent badge -->
                                <span v-if="plan.consent?.status === 'pending'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">
                                    {{ isRtl ? 'تحتاج توقيع' : 'Needs Sign' }}
                                </span>
                                <span v-else-if="plan.consent?.status === 'signed'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                                    {{ isRtl ? 'تم التوقيع' : 'Signed' }}
                                </span>
                            </div>
                        </div>
                        <!-- Mini progress bar -->
                        <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                            <div class="bg-gradient-to-r from-slate-400 to-[#1B365D] h-1.5 rounded-full transition-all duration-500"
                                 :style="{ width: (plan.estimated_sessions > 0 ? Math.min((plan.completed_sessions / plan.estimated_sessions) * 100, 100) : 0) + '%' }"></div>
                        </div>
                    </Link>
                </div>
                <div v-else class="py-10 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد خطط نشطة' : 'No active plans' }}</p>
                </div>
            </div>

            <!-- Upcoming Follow-ups -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'مواعيد المتابعة القادمة' : 'Upcoming Follow-ups' }}</h3>
                    <Link :href="lp('/dental/followups')" class="text-xs text-[#1B365D] hover:text-[#1B365D] font-medium">{{ isRtl ? 'عرض الكل' : 'View All' }}</Link>
                </div>
                <div v-if="upcomingFollowups?.length" class="divide-y divide-gray-50">
                    <div v-for="f in upcomingFollowups" :key="f.id"
                         class="px-5 py-4"
                         :class="daysUntil(f.scheduled_date) < 0 ? 'bg-red-50/30' : ''">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-2 h-2 rounded-full flex-shrink-0"
                                     :class="daysUntil(f.scheduled_date) < 0 ? 'bg-red-500' : daysUntil(f.scheduled_date) <= 3 ? 'bg-amber-500' : 'bg-emerald-500'"></div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">{{ formatDate(f.scheduled_date) }}</p>
                                    <p v-if="f.treatment" class="text-xs text-gray-500 mt-0.5">
                                        {{ f.treatment.treatment_type?.replace(/_/g, ' ') }}
                                        <span v-if="f.treatment.tooth_number" class="text-gray-400"> &middot; {{ isRtl ? 'سن' : 'Tooth' }} #{{ f.treatment.tooth_number }}</span>
                                    </p>
                                    <p v-if="f.doctor" class="text-[10px] text-gray-400">{{ $localized(f.doctor, 'name') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                                      :class="daysUntil(f.scheduled_date) < 0 ? 'bg-red-100 text-red-700' : daysUntil(f.scheduled_date) <= 3 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'">
                                    <template v-if="daysUntil(f.scheduled_date) < 0">{{ isRtl ? `متأخر ${Math.abs(daysUntil(f.scheduled_date))} يوم` : `${Math.abs(daysUntil(f.scheduled_date))}d overdue` }}</template>
                                    <template v-else-if="daysUntil(f.scheduled_date) === 0">{{ isRtl ? 'اليوم' : 'Today' }}</template>
                                    <template v-else>{{ isRtl ? `بعد ${daysUntil(f.scheduled_date)} يوم` : `in ${daysUntil(f.scheduled_date)}d` }}</template>
                                </span>
                            </div>
                        </div>
                        <div v-if="['pending', 'sms_sent'].includes(f.status)" class="mt-2">
                            <Link :href="lp('/bookings/create')"
                                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:from-[var(--brand-primary-hover)] hover:to-[var(--brand-primary)] transition shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ isRtl ? 'حجز الموعد' : 'Book Now' }}
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="py-10 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد مواعيد متابعة قادمة' : 'No upcoming follow-ups' }}</p>
                </div>
            </div>
        </div>

        <!-- Pending Lab Orders -->
        <div v-if="pendingLabOrders?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تتبع طلبات المعمل' : 'Lab Order Tracking' }}</h3>
                <Link :href="lp('/dental/lab-orders')" class="text-xs text-[#1B365D] hover:text-[#1B365D] font-medium">{{ isRtl ? 'عرض الكل' : 'View All' }}</Link>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="lo in pendingLabOrders" :key="lo.id" class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-lg">{{ labStatusConfig[lo.status]?.icon || '' }}</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 capitalize">{{ lo.item_type?.replace(/_/g, ' ') }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $localized(lo.doctor, 'name') }}
                                    <span v-if="lo.tooth_number"> &middot; {{ isRtl ? 'سن' : 'Tooth' }} #{{ lo.tooth_number }}</span>
                                </p>
                            </div>
                        </div>
                        <span :class="labStatusConfig[lo.status]?.color || 'bg-gray-100 text-gray-600'"
                              class="px-2.5 py-1 rounded-full text-xs font-semibold">
                            {{ isRtl ? labStatusConfig[lo.status]?.ar : labStatusConfig[lo.status]?.en }}
                        </span>
                    </div>
                    <!-- Progress steps -->
                    <div class="mt-3 flex items-center gap-1">
                        <div class="flex-1 h-1.5 rounded-full" :class="['ordered','in_production','ready','delivered'].indexOf(lo.status) >= 0 ? 'bg-[#1B365D]' : 'bg-gray-200'"></div>
                        <div class="flex-1 h-1.5 rounded-full" :class="['in_production','ready','delivered'].indexOf(lo.status) >= 0 ? 'bg-[#1B365D]' : 'bg-gray-200'"></div>
                        <div class="flex-1 h-1.5 rounded-full" :class="['ready','delivered'].indexOf(lo.status) >= 0 ? 'bg-emerald-500' : 'bg-gray-200'"></div>
                        <div class="flex-1 h-1.5 rounded-full" :class="lo.status === 'delivered' ? 'bg-emerald-500' : 'bg-gray-200'"></div>
                    </div>
                    <div class="flex justify-between mt-1 text-[9px] text-gray-400">
                        <span>{{ isRtl ? 'طلب' : 'Ordered' }}</span>
                        <span>{{ isRtl ? 'تصنيع' : 'Making' }}</span>
                        <span>{{ isRtl ? 'جاهز' : 'Ready' }}</span>
                        <span>{{ isRtl ? 'مسلّم' : 'Done' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dental Chart Summary + Recent X-rays -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Chart Summary -->
            <div v-if="chartTotal > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'ملخص حالة الأسنان' : 'Tooth Condition Summary' }}</h3>
                    <Link :href="lp('/dental/chart')" class="text-xs text-[#1B365D] hover:text-[#1B365D] font-medium">{{ isRtl ? 'عرض المخطط' : 'View Chart' }}</Link>
                </div>
                <div class="space-y-2">
                    <div v-for="(count, condition) in chartSummary" :key="condition"
                         class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :class="conditionLabels[condition]?.color?.split(' ')[0] || 'bg-gray-200'"></span>
                            <span class="text-sm text-gray-700">{{ isRtl ? (conditionLabels[condition]?.ar || condition) : (conditionLabels[condition]?.en || condition) }}</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ count }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent X-rays -->
            <div v-if="recentXrays?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'آخر الأشعة' : 'Recent X-Rays' }}</h3>
                    <Link :href="lp('/dental/xrays')" class="text-xs text-[#1B365D] hover:text-[#1B365D] font-medium">{{ isRtl ? 'عرض الكل' : 'View All' }}</Link>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div v-for="xray in recentXrays" :key="xray.id" class="group relative">
                        <img v-if="xray.image_path" :src="`/storage/${xray.image_path}`"
                             class="w-full h-24 object-cover rounded-xl border border-gray-200 group-hover:border-slate-300 transition" />
                        <div v-else class="w-full h-24 rounded-xl bg-gray-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1 text-center capitalize">{{ xray.xray_type?.replace(/_/g, ' ') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
            <Link v-for="link in quickLinks" :key="link.href" :href="link.href"
                  class="group flex flex-col items-center gap-2 p-4 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div :class="`w-10 h-10 rounded-xl bg-gradient-to-br ${link.color} flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-200`">
                    <svg v-if="link.icon === 'tooth'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2C9.5 2 7 4 7 7c0 2-.5 4-1 6-.5 2-.5 4 0 5.5S7.5 21 8.5 21s1.5-1 2-3c.5-1.5 1-2 1.5-2s1 .5 1.5 2c.5 2 1 3 2 3s2-1 2.5-2.5.5-3.5 0-5.5c-.5-2-1-4-1-6 0-3-2.5-5-5-5z" /></svg>
                    <svg v-else-if="link.icon === 'treatment'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    <svg v-else-if="link.icon === 'plan'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    <svg v-else-if="link.icon === 'xray'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <svg v-else-if="link.icon === 'lab'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    <svg v-else-if="link.icon === 'followup'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center group-hover:text-[#1B365D] transition-colors">{{ link.label }}</span>
            </Link>
        </div>
    </div>
</template>
