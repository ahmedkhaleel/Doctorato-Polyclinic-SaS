<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    payout: Object,
});

const mounted = ref(false);
const animatedNet = ref(0);

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    animateCounter();
});

function animateCounter() {
    const target = parseFloat(props.payout?.net_amount || 0);
    const duration = 1200;
    const start = performance.now();
    function step(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        animatedNet.value = Math.round(target * eased);
        if (progress < 1) requestAnimationFrame(step);
        else animatedNet.value = target;
    }
    requestAnimationFrame(step);
}

const statusConfig = computed(() => ({
    draft: { label: isRtl.value ? 'مسودة' : 'Draft', bg: 'bg-gray-500/20', text: 'text-gray-300', dot: 'bg-gray-400', heroBg: 'bg-gray-50', heroBorder: 'border-gray-200', heroText: 'text-gray-600', heroIcon: 'text-gray-400' },
    confirmed: { label: isRtl.value ? 'في انتظار الدفع' : 'Pending Payment', bg: 'bg-amber-500/20', text: 'text-amber-300', dot: 'bg-amber-400', heroBg: 'bg-amber-50', heroBorder: 'border-amber-200', heroText: 'text-amber-700', heroIcon: 'text-amber-500' },
    paid: { label: isRtl.value ? 'مدفوع' : 'Paid', bg: 'bg-emerald-500/20', text: 'text-emerald-300', dot: 'bg-emerald-400', heroBg: 'bg-emerald-50', heroBorder: 'border-emerald-200', heroText: 'text-emerald-700', heroIcon: 'text-emerald-500' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-red-500/20', text: 'text-red-300', dot: 'bg-red-400', heroBg: 'bg-red-50', heroBorder: 'border-red-200', heroText: 'text-red-600', heroIcon: 'text-red-400' },
}));

const paymentMethodLabels = computed(() => ({
    cash: isRtl.value ? 'نقدي' : 'Cash',
    bank_transfer: isRtl.value ? 'تحويل بنكي' : 'Bank Transfer',
    check: isRtl.value ? 'شيك' : 'Check',
    mobile_wallet: isRtl.value ? 'محفظة إلكترونية' : 'Mobile Wallet',
}));

const currentStatus = computed(() => statusConfig.value[props.payout?.status] || statusConfig.value.draft);

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <!-- Back + Breadcrumb -->
                <Link href="/doctor/commission"
                    class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#C4A265] transition-colors mb-4"
                    :class="mounted ? 'opacity-100' : 'opacity-0'"
                    style="transition: opacity 0.5s ease"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    {{ $t('a_commission') }}
                </Link>

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ payout.payout_number }}</h1>
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full"
                                :class="[currentStatus.bg, currentStatus.text]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="currentStatus.dot"></span>
                                {{ currentStatus.label }}
                            </span>
                        </div>
                        <p class="text-gray-400 text-sm mt-1.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ formatDate(payout.period_start) }} – {{ formatDate(payout.period_end) }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3"
                        :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                    >
                        <!-- Print Receipt Button (paid only) -->
                        <Link v-if="payout.status === 'paid'"
                            :href="`/doctor/commission/payouts/${payout.id}/print`"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/15 hover:border-[#C4A265]/30 rounded-xl text-sm font-medium text-white transition-all duration-200 group"
                        >
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-[#C4A265] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            {{ isRtl ? 'طباعة الإيصال' : 'Print Receipt' }}
                        </Link>

                        <!-- Net Amount Counter -->
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-5 py-3 border border-white/10 text-center">
                            <p class="text-2xl sm:text-3xl font-bold text-[#C4A265]">{{ animatedNet.toLocaleString() }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? `صافي المبلغ (${currencyCode})` : `Net Amount (${currencyCode})` }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div class="flex items-center gap-4 mt-5 flex-wrap"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
                >
                    <div class="flex items-center gap-1.5 text-sm text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        <span>{{ payout.total_visits }} {{ isRtl ? 'زيارة' : 'visits' }}</span>
                    </div>
                    <span class="text-gray-600">|</span>
                    <div class="flex items-center gap-1.5 text-sm text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ formatCurrency(payout.total_revenue) }} {{ isRtl ? 'إيرادات' : 'revenue' }}</span>
                    </div>
                    <span class="text-gray-600">|</span>
                    <div class="text-sm text-gray-400">
                        {{ isRtl ? 'أنشئ في' : 'Created' }} {{ formatDate(payout.created_at) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Visits Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.25s"
                >
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الزيارات المشمولة' : 'Included Visits' }}</h2>
                        <span class="ml-auto text-xs font-semibold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ payout.total_visits }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80">
                                    <th class="ltr:text-left rtl:text-right px-5 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                    <th class="ltr:text-left rtl:text-right px-5 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                                    <th class="ltr:text-left rtl:text-right px-5 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                                    <th class="ltr:text-right rtl:text-left px-5 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                                    <th class="ltr:text-right rtl:text-left px-5 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'النسبة' : 'Rate' }}</th>
                                    <th class="ltr:text-right rtl:text-left px-5 py-2.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'العمولة' : 'Commission' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100/80">
                                <tr v-for="(visit, index) in payout.visits" :key="visit.id"
                                    class="hover:bg-gray-50/50 transition-colors duration-150"
                                    :class="mounted ? 'opacity-100' : 'opacity-0'"
                                    :style="{ transition: 'opacity 0.4s ease', transitionDelay: `${0.35 + index * 0.03}s` }"
                                >
                                    <td class="px-5 py-3">
                                        <span class="text-xs text-gray-500">{{ formatDate(visit.visit_date) }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-sm font-medium text-gray-800">{{ visit.patient?.full_name || '-' }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-xs text-gray-500">{{ visit.service?.name_en || (visit.visit_type === 'consultation' ? (isRtl ? 'استشارة' : 'Consultation') : (isRtl ? 'جلسة' : 'Session')) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-xs text-gray-600 font-medium">{{ parseFloat(visit.pivot?.visit_amount || 0).toLocaleString() }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-xs font-semibold text-[#C4A265]">{{ visit.pivot?.commission_rate }}%</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-sm font-bold text-[#C4A265]">{{ parseFloat(visit.pivot?.commission_amount || 0).toLocaleString() }}</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50/80 border-t-2 border-gray-100">
                                    <td colspan="3" class="px-5 py-3.5">
                                        <span class="text-sm font-bold text-gray-700">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <span class="text-sm font-bold text-gray-700">{{ parseFloat(payout.total_revenue || 0).toLocaleString() }}</span>
                                    </td>
                                    <td class="px-5 py-3.5"></td>
                                    <td class="px-5 py-3.5 text-right">
                                        <span class="text-sm font-bold text-[#C4A265]">{{ formatCurrency(payout.total_commission) }}</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-5">
                <!-- Financial Summary -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
                >
                    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الملخص المالي' : 'Financial Summary' }}</h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'إجمالي الإيرادات' : 'Total Revenue' }}</span>
                            <span class="font-semibold text-gray-800">{{ formatCurrency(payout.total_revenue) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'العمولة' : 'Commission' }}</span>
                            <span class="font-bold text-[#C4A265]">{{ formatCurrency(payout.total_commission) }}</span>
                        </div>
                        <div v-if="parseFloat(payout.deductions) > 0" class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'الخصومات' : 'Deductions' }}</span>
                            <span class="font-semibold text-red-500">-{{ formatCurrency(payout.deductions) }}</span>
                        </div>
                        <div v-if="payout.deduction_notes" class="text-xs text-gray-400 pl-3 border-l-2 border-gray-200 italic">
                            {{ payout.deduction_notes }}
                        </div>
                        <div class="border-t border-gray-100 pt-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-gray-700">{{ isRtl ? 'صافي المبلغ' : 'Net Amount' }}</span>
                                <span class="text-lg font-bold text-[#C4A265]">{{ formatCurrency(payout.net_amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Card -->
                <div class="rounded-2xl border overflow-hidden"
                    :class="[currentStatus.heroBg, currentStatus.heroBorder, mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.35s"
                >
                    <!-- Paid -->
                    <div v-if="payout.status === 'paid'" class="p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-emerald-700">{{ isRtl ? 'تم استلام الدفعة' : 'Payment Received' }}</h3>
                        </div>
                        <div class="space-y-2.5">
                            <div class="flex justify-between text-sm">
                                <span class="text-emerald-600">{{ isRtl ? 'الطريقة' : 'Method' }}</span>
                                <span class="font-semibold text-emerald-800">{{ paymentMethodLabels[payout.payment_method] || payout.payment_method }}</span>
                            </div>
                            <div v-if="payout.payment_reference" class="flex justify-between text-sm">
                                <span class="text-emerald-600">{{ isRtl ? 'المرجع' : 'Reference' }}</span>
                                <span class="font-medium text-emerald-800 font-mono text-xs">{{ payout.payment_reference }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-emerald-600">{{ isRtl ? 'تاريخ الدفع' : 'Paid On' }}</span>
                                <span class="font-medium text-emerald-800">{{ formatDateTime(payout.paid_at) }}</span>
                            </div>
                            <div v-if="payout.paid_by_user" class="flex justify-between text-sm">
                                <span class="text-emerald-600">{{ isRtl ? 'بواسطة' : 'By' }}</span>
                                <span class="font-medium text-emerald-800">{{ payout.paid_by_user?.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmed -->
                    <div v-else-if="payout.status === 'confirmed'" class="p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-amber-700">{{ isRtl ? 'في انتظار الدفع' : 'Awaiting Payment' }}</h3>
                        </div>
                        <p class="text-xs text-amber-600 leading-relaxed">{{ isRtl ? 'تم تأكيد هذه الدفعة وهي في انتظار الصرف من العيادة.' : 'This payout has been confirmed and is awaiting payment from the clinic.' }}</p>
                        <div class="flex items-center gap-1.5 text-xs text-amber-500 mt-3">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ isRtl ? 'تم التأكيد في' : 'Confirmed on' }} {{ formatDateTime(payout.confirmed_at) }}
                        </div>
                    </div>

                    <!-- Draft -->
                    <div v-else-if="payout.status === 'draft'" class="p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-600">{{ isRtl ? 'دفعة مسودة' : 'Draft Payout' }}</h3>
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed">{{ isRtl ? 'هذه الدفعة لا تزال مسودة ولم يتم اعتمادها بعد.' : 'This payout is still in draft and has not been finalized yet.' }}</p>
                    </div>

                    <!-- Cancelled -->
                    <div v-else-if="payout.status === 'cancelled'" class="p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-red-600">{{ isRtl ? 'ملغي' : 'Cancelled' }}</h3>
                        </div>
                        <p v-if="payout.cancellation_reason" class="text-xs text-red-500 leading-relaxed">{{ payout.cancellation_reason }}</p>
                        <div class="flex items-center gap-1.5 text-xs text-red-400 mt-3">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ isRtl ? 'تم الإلغاء في' : 'Cancelled on' }} {{ formatDateTime(payout.cancelled_at) }}
                        </div>
                    </div>
                </div>

                <!-- Payout Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.4s"
                >
                    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'التفاصيل' : 'Details' }}</h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</span>
                            <span class="font-semibold text-gray-800">{{ payout.total_visits }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'الفترة' : 'Period' }}</span>
                            <span class="font-medium text-gray-700 text-xs">{{ formatDate(payout.period_start) }} – {{ formatDate(payout.period_end) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'أنشئ في' : 'Created' }}</span>
                            <span class="font-medium text-gray-700 text-xs">{{ formatDateTime(payout.created_at) }}</span>
                        </div>
                        <div v-if="payout.confirmed_at" class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'تم التأكيد' : 'Confirmed' }}</span>
                            <span class="font-medium text-gray-700 text-xs">{{ formatDateTime(payout.confirmed_at) }}</span>
                        </div>
                        <div v-if="payout.paid_at" class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ isRtl ? 'تم الدفع' : 'Paid' }}</span>
                            <span class="font-medium text-gray-700 text-xs">{{ formatDateTime(payout.paid_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="payout.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.45s"
                >
                    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-cyan-50 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'ملاحظات' : 'Notes' }}</h3>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-gray-600 whitespace-pre-line leading-relaxed">{{ payout.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
