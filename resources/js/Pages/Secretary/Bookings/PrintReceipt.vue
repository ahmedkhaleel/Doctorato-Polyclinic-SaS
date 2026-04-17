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
});

const { formatCurrency, currencyCode } = useCurrency();

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const latestPayment = computed(() => {
    const payments = props.booking.invoice?.payments;
    if (!payments || payments.length === 0) return null;
    return payments.reduce((latest, p) =>
        new Date(p.payment_date) > new Date(latest.payment_date) ? p : latest
    , payments[0]);
});

const receiptDate = computed(() => {
    if (latestPayment.value) return formatDate(latestPayment.value.payment_date);
    return formatDate(props.booking.invoice?.invoice_date);
});

const totalPaid = computed(() => {
    const payments = props.booking.invoice?.payments;
    if (!payments || payments.length === 0) return 0;
    return payments.reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const remainingBalance = computed(() => {
    return Number(props.booking.invoice?.total || 0) - Number(props.booking.invoice?.paid_amount || 0);
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
                {{ isRtl ? 'العودة للحجز' : 'Back to Booking' }}
            </Link>
            <button
                @click="handlePrint"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-teal-500 to-[#1B365D] text-white rounded-xl text-sm font-semibold hover:from-teal-600 hover:to-[#1B365D] transition-all duration-300 shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                {{ isRtl ? 'طباعة الإيصال' : 'Print Receipt' }}
            </button>
        </div>

        <!-- Receipt Content -->
        <div class="receipt-container mx-auto max-w-[800px] bg-white rounded-2xl shadow-sm border border-gray-100/80 print:shadow-none print:border-0 print:rounded-none">
            <div class="p-8 print:p-0">

                <!-- Header -->
                <div class="text-center mb-8 pb-6 border-b border-gray-200">
                    <img
                        src="/images/logo.png"
                        alt="Doctorato Polyclinic"
                        class="mx-auto h-20 mb-3 object-contain"
                    />
                    <h1 class="text-xl font-bold text-gray-900 tracking-wide">Doctorato Polyclinic</h1>
                    <p class="text-sm text-gray-500 font-medium" dir="rtl">عيادة دكتوراتو</p>
                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800">Payment Receipt / إيصال دفع</h2>
                    </div>
                </div>

                <!-- Booking & Patient Info -->
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Booking Details / تفاصيل الحجز</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">Booking # / رقم الحجز:</span>
                            <span class="text-sm font-semibold font-mono text-teal-600">{{ booking.booking_number }}</span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">Date / التاريخ:</span>
                            <span class="text-sm font-medium text-gray-800">{{ receiptDate }}</span>
                        </div>
                        <div v-if="booking.invoice?.invoice_number" class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">Invoice # / رقم الفاتورة:</span>
                            <span class="text-sm font-semibold font-mono text-gray-700">{{ booking.invoice.invoice_number }}</span>
                        </div>
                    </div>
                    <div class="space-y-2 ltr:text-right rtl:text-left" dir="rtl">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3" dir="ltr">Patient Info / بيانات المريض</h3>
                        <div class="flex items-baseline gap-2 justify-start">
                            <span class="text-xs text-gray-500">الاسم / Name:</span>
                            <span class="text-sm font-semibold text-gray-800">{{ booking.patient?.full_name }}</span>
                        </div>
                        <div class="flex items-baseline gap-2 justify-start">
                            <span class="text-xs text-gray-500">الهاتف / Phone:</span>
                            <span class="text-sm font-medium text-gray-700" dir="ltr">{{ booking.patient?.phone }}</span>
                        </div>
                        <div v-if="booking.patient?.file_number" class="flex items-baseline gap-2 justify-start">
                            <span class="text-xs text-gray-500">رقم الملف / File #:</span>
                            <span class="text-sm font-semibold font-mono text-teal-600" dir="ltr">{{ booking.patient.file_number }}</span>
                        </div>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Services / الخدمات</h3>
                    <table class="w-full text-sm border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">
                                    Service / الخدمة
                                </th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 border-b border-gray-200">
                                    Sessions / الجلسات
                                </th>
                                <th class="px-4 py-2.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">
                                    Unit Price / سعر الجلسة
                                </th>
                                <th class="px-4 py-2.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">
                                    Total / الإجمالي
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(bs, index) in booking.booking_services" :key="index" class="hover:bg-gray-50/50 print:hover:bg-transparent">
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-gray-800">{{ bs.service?.name_en }}</div>
                                    <div v-if="bs.service?.name_ar" class="text-xs text-gray-400" dir="rtl">{{ bs.service.name_ar }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-center text-gray-600">{{ bs.sessions_count }}</td>
                                <td class="px-4 py-2.5 ltr:text-right rtl:text-left text-gray-600">{{ formatCurrency(bs.unit_price) }}</td>
                                <td class="px-4 py-2.5 ltr:text-right rtl:text-left font-semibold text-gray-800">{{ formatCurrency(bs.total_price) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 border-t-2 border-gray-300">
                                <td colspan="3" class="px-4 py-3 ltr:text-right rtl:text-left text-sm font-bold text-gray-700">
                                    Grand Total / المجموع الكلي
                                </td>
                                <td class="px-4 py-3 ltr:text-right rtl:text-left text-sm font-bold text-teal-600">
                                    {{ formatCurrency(booking.invoice?.total) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Details -->
                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Payment Details / تفاصيل الدفع</h3>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <!-- Payments List -->
                        <table v-if="booking.invoice?.payments && booking.invoice.payments.length > 0" class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">Date / التاريخ</th>
                                    <th class="px-4 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">Method / طريقة الدفع</th>
                                    <th class="px-4 py-2.5 ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">Amount / المبلغ</th>
                                    <th class="px-4 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">Ref # / رقم المرجع</th>
                                    <th class="px-4 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-600 border-b border-gray-200">Received By / المستلم</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="payment in booking.invoice.payments" :key="payment.id">
                                    <td class="px-4 py-2.5 text-gray-600">{{ formatDate(payment.payment_date) }}</td>
                                    <td class="px-4 py-2.5 text-gray-600">
                                        <span>{{ payment.payment_method?.name_en }}</span>
                                        <span v-if="payment.payment_method?.name_ar" class="text-xs text-gray-400 ltr:mr-1 rtl:ml-1"> / {{ payment.payment_method.name_ar }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 ltr:text-right rtl:text-left font-semibold text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                                    <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ payment.reference_number || '-' }}</td>
                                    <td class="px-4 py-2.5 text-gray-600">{{ payment.receiver?.name || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Summary -->
                        <div class="bg-gray-50 border-t border-gray-200 px-4 py-4">
                            <div class="grid grid-cols-2 gap-3 max-w-md ltr:ml-auto rtl:mr-auto">
                                <div class="flex justify-between col-span-2 text-sm">
                                    <span class="text-gray-500">Subtotal / المجموع الفرعي</span>
                                    <span class="font-medium text-gray-800">{{ formatCurrency(booking.invoice?.subtotal) }}</span>
                                </div>
                                <div v-if="Number(booking.invoice?.discount_amount) > 0" class="flex justify-between col-span-2 text-sm">
                                    <span class="text-gray-500">Discount / الخصم</span>
                                    <span class="font-medium text-red-600">-{{ formatCurrency(booking.invoice?.discount_amount) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm border-t border-gray-200 pt-2">
                                    <span class="font-bold text-gray-700">Total / الإجمالي</span>
                                    <span class="font-bold text-gray-800">{{ formatCurrency(booking.invoice?.total) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm">
                                    <span class="font-bold text-gray-700">Amount Paid / المبلغ المدفوع</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(totalPaid) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm border-t border-gray-200 pt-2">
                                    <span class="font-bold text-gray-700">Remaining Balance / الرصيد المتبقي</span>
                                    <span class="font-bold" :class="remainingBalance > 0 ? 'text-red-600' : 'text-emerald-600'">
                                        {{ formatCurrency(remainingBalance) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center border-t border-gray-200 pt-6 mt-8">
                    <p class="text-xs text-gray-400" dir="rtl">هذا مستند إلكتروني ولا يحتاج إلى توقيع</p>
                    <p class="text-xs text-gray-400 mt-1">This is an electronic document and does not require a signature</p>
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
    thead tr {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
