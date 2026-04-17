<script setup>
import { computed, onMounted } from 'vue';
import { Link , usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    booking: Object,
    payment: Object,
    allPayments: Array,
});

const { formatCurrency, currencyCode } = useCurrency();

// Helper for bilingual text
const t = (ar, en) => isRtl.value ? ar : en;

// Helper for bilingual names
const localeName = (obj, fallback = '-') => {
    if (!obj) return fallback;
    return isRtl.value ? (obj.name_ar || obj.name_en || fallback) : (obj.name_en || obj.name_ar || fallback);
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

function formatDateTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const totalPaidBefore = computed(() => {
    if (!props.allPayments || !props.payment) return 0;
    return props.allPayments
        .filter(p => new Date(p.created_at) < new Date(props.payment.created_at) || (new Date(p.created_at).getTime() === new Date(props.payment.created_at).getTime() && p.id < props.payment.id))
        .reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const totalPaidAfter = computed(() => {
    return totalPaidBefore.value + Number(props.payment?.amount || 0);
});

const invoiceTotal = computed(() => Number(props.booking?.invoice?.total || 0));

const remainingAfterThis = computed(() => {
    return Math.max(0, invoiceTotal.value - totalPaidAfter.value);
});

const paymentIndex = computed(() => {
    if (!props.allPayments || !props.payment) return 1;
    const idx = props.allPayments.findIndex(p => p.id === props.payment.id);
    return idx >= 0 ? idx + 1 : 1;
});

function handlePrint() {
    window.print();
}

onMounted(() => {
    setTimeout(() => window.print(), 500);
});
</script>

<template>
    <div>
        <!-- Top Bar (Screen Only) -->
        <div class="screen-only flex items-center justify-between mb-6">
            <Link
                :href="`/secretary/bookings/${booking.id}`"
                class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ t('العودة للحجز', 'Back to Booking') }}
            </Link>
            <button
                @click="handlePrint"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-teal-500 to-[#1B365D] text-white rounded-xl text-sm font-semibold hover:from-teal-600 hover:to-[#1B365D] transition-all duration-300 shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                {{ t('طباعة الإيصال', 'Print Receipt') }}
            </button>
        </div>

        <!-- Receipt Content -->
        <div class="receipt-container mx-auto max-w-[800px] bg-white rounded-2xl shadow-sm border border-gray-100/80 print:shadow-none print:border-0 print:rounded-none" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="p-8 print:p-0">

                <!-- Header -->
                <div class="text-center mb-8 pb-6 border-b border-gray-200">
                    <img
                        src="/images/logo/logo.png"
                        alt="Doctorato Polyclinic"
                        class="mx-auto h-20 mb-3 object-contain"
                    />
                    <h1 class="text-xl font-bold text-gray-900 tracking-wide">{{ t('عيادة دكتوراتو', 'Doctorato Polyclinic') }}</h1>
                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800">{{ t('إيصال الدفع', 'Payment Receipt') }}</h2>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ t(`الدفعة ${paymentIndex} من ${allPayments?.length || 1}`, `Payment ${paymentIndex} of ${allPayments?.length || 1}`) }}
                        </p>
                    </div>
                </div>

                <!-- Booking & Patient Info -->
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ t('تفاصيل الإيصال', 'Receipt Details') }}</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ t('رقم الحجز:', 'Booking #:') }}</span>
                            <span class="text-sm font-semibold font-mono text-teal-600">{{ booking.booking_number }}</span>
                        </div>
                        <div v-if="booking.invoice?.invoice_number" class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ t('رقم الفاتورة:', 'Invoice #:') }}</span>
                            <span class="text-sm font-semibold font-mono text-gray-700">{{ booking.invoice.invoice_number }}</span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ t('تاريخ الدفع:', 'Payment Date:') }}</span>
                            <span class="text-sm font-medium text-gray-800">{{ formatDate(payment.payment_date) }}</span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ t('تاريخ الإيصال:', 'Receipt Date:') }}</span>
                            <span class="text-sm font-medium text-gray-800">{{ formatDateTime(payment.created_at) }}</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ t('معلومات المريض', 'Patient Info') }}</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ t('الاسم:', 'Name:') }}</span>
                            <span class="text-sm font-semibold text-gray-800">{{ booking.patient?.full_name }}</span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ t('الهاتف:', 'Phone:') }}</span>
                            <span class="text-sm font-medium text-gray-700" dir="ltr">{{ booking.patient?.phone }}</span>
                        </div>
                        <div v-if="booking.patient?.file_number" class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ t('رقم الملف:', 'File #:') }}</span>
                            <span class="text-sm font-semibold font-mono text-teal-600">{{ booking.patient.file_number }}</span>
                        </div>
                    </div>
                </div>

                <!-- This Payment (Highlighted) -->
                <div class="mb-8 border-2 border-teal-300 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 bg-teal-50/60">
                        <h3 class="text-sm font-bold text-teal-800">{{ t('تفاصيل الدفع', 'Payment Details') }}</h3>
                    </div>
                    <div class="px-6 py-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">{{ t('المبلغ المدفوع', 'Amount Paid') }}</p>
                                <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(payment.amount) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">{{ t('طريقة الدفع', 'Payment Method') }}</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ localeName(payment.payment_method || payment.paymentMethod) }}
                                </p>
                            </div>
                            <div v-if="payment.reference_number">
                                <p class="text-xs text-gray-400 mb-1">{{ t('رقم المرجع', 'Reference #') }}</p>
                                <p class="text-sm font-mono font-medium text-gray-700">{{ payment.reference_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">{{ t('المستلم', 'Received By') }}</p>
                                <p class="text-sm font-medium text-gray-700">{{ payment.receiver?.name || '-' }}</p>
                            </div>
                            <div v-if="payment.notes" class="col-span-2">
                                <p class="text-xs text-gray-400 mb-1">{{ t('ملاحظات', 'Notes') }}</p>
                                <p class="text-sm text-gray-600">{{ payment.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ t('الخدمات', 'Services') }}</h3>
                    <table class="w-full text-sm border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">{{ t('الخدمة', 'Service') }}</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 border-b border-gray-200">{{ t('الجلسات', 'Sessions') }}</th>
                                <th class="px-4 py-2.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">{{ t('سعر الوحدة', 'Unit Price') }}</th>
                                <th class="px-4 py-2.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">{{ t('الإجمالي', 'Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(bs, index) in booking.booking_services" :key="index" class="hover:bg-gray-50/50 print:hover:bg-transparent">
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-gray-800">{{ isRtl ? (bs.service?.name_ar || bs.service?.name_en) : (bs.service?.name_en || bs.service?.name_ar) }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-center text-gray-600">{{ bs.sessions_count }}</td>
                                <td class="px-4 py-2.5 ltr:text-right rtl:text-left text-gray-600">{{ formatCurrency(bs.unit_price) }}</td>
                                <td class="px-4 py-2.5 ltr:text-right rtl:text-left font-semibold text-gray-800">{{ formatCurrency(bs.total_price) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 border-t-2 border-gray-300">
                                <td colspan="3" class="px-4 py-3 ltr:text-right rtl:text-left text-sm font-bold text-gray-700">{{ t('إجمالي الفاتورة', 'Invoice Total') }}</td>
                                <td class="px-4 py-3 ltr:text-right rtl:text-left text-sm font-bold text-teal-600">{{ formatCurrency(invoiceTotal) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- All Payments History for this Invoice -->
                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ t('سجل المدفوعات', 'Payment History') }}</h3>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table v-if="allPayments && allPayments.length > 0" class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">#</th>
                                    <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">{{ t('التاريخ', 'Date') }}</th>
                                    <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">{{ t('الطريقة', 'Method') }}</th>
                                    <th class="px-4 py-2.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">{{ t('المبلغ', 'Amount') }}</th>
                                    <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">{{ t('المرجع', 'Ref #') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(p, idx) in allPayments" :key="p.id"
                                    :class="p.id === payment.id ? 'bg-teal-50/60' : ''">
                                    <td class="px-4 py-2.5 text-gray-500 text-xs font-medium">
                                        {{ idx + 1 }}
                                        <span v-if="p.id === payment.id" class="ltr:ml-1 rtl:mr-1 inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold text-white bg-teal-500">
                                            {{ t('الحالي', 'Current') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-600">{{ formatDate(p.payment_date) }}</td>
                                    <td class="px-4 py-2.5 text-gray-600">
                                        <span>{{ localeName(p.payment_method || p.paymentMethod) }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 ltr:text-right rtl:text-left font-semibold" :class="p.id === payment.id ? 'text-emerald-700' : 'text-emerald-600'">
                                        {{ formatCurrency(p.amount) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ p.reference_number || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Financial Summary -->
                        <div class="bg-gray-50 border-t border-gray-200 px-4 py-4">
                            <div class="grid grid-cols-2 gap-3 max-w-md ltr:ml-auto rtl:mr-auto">
                                <div class="flex justify-between col-span-2 text-sm">
                                    <span class="text-gray-500">{{ t('إجمالي الفاتورة', 'Invoice Total') }}</span>
                                    <span class="font-bold text-gray-800">{{ formatCurrency(invoiceTotal) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm">
                                    <span class="text-gray-500">{{ t('المدفوع سابقاً', 'Paid Before This') }}</span>
                                    <span class="font-medium text-gray-600">{{ formatCurrency(totalPaidBefore) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm border-t border-gray-200 pt-2 text-teal-700">
                                    <span class="font-bold">{{ t('هذه الدفعة', 'This Payment') }}</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(payment.amount) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm">
                                    <span class="font-bold text-gray-700">{{ t('إجمالي المدفوع', 'Total Paid') }}</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(totalPaidAfter) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm border-t border-gray-200 pt-2">
                                    <span class="font-bold text-gray-700">{{ t('المبلغ المتبقي', 'Remaining Balance') }}</span>
                                    <span class="font-bold" :class="remainingAfterThis > 0 ? 'text-red-600' : 'text-emerald-600'">
                                        {{ formatCurrency(remainingAfterThis) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center border-t border-gray-200 pt-6 mt-8">
                    <p class="text-xs text-gray-400">{{ t('هذا مستند إلكتروني ولا يحتاج لتوقيع', 'This is an electronic document and does not require a signature') }}</p>
                </div>

            </div>
        </div>
    </div>
</template>

<style>
/* Print-only and screen-only utility classes */
.screen-only {
    display: flex;
}
.print-only {
    display: none;
}

@media print {
    .screen-only {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }

    @page {
        size: A4;
        margin: 15mm;
    }

    /* Hide SecretaryLayout navigation, header, sidebar */
    nav,
    header,
    aside,
    footer,
    [data-sidebar],
    [data-header],
    [data-navigation] {
        display: none !important;
    }

    /* Remove layout padding/margin so receipt fills the page */
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    main,
    [data-content],
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    /* Receipt container reset for print */
    .receipt-container {
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
    }

    /* Clean table borders for print */
    table {
        border-collapse: collapse;
    }

    th, td {
        border-color: #d1d5db !important;
    }

    /* Ensure background colors print */
    .bg-gray-50,
    .bg-teal-50\/60,
    thead tr {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
