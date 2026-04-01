<script setup>
import { computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bundleBooking: Object,
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const b = computed(() => props.bundleBooking);
const invoice = computed(() => b.value?.invoice);
const payments = computed(() => invoice.value?.payments || []);

const totalPaid = computed(() => {
    return payments.value.reduce((sum, p) => sum + Number(p.amount || 0), 0);
});

const remainingBalance = computed(() => {
    return Number(invoice.value?.total || 0) - Number(invoice.value?.paid_amount || 0);
});

const latestPayment = computed(() => {
    if (payments.value.length === 0) return null;
    return payments.value.reduce((latest, p) =>
        new Date(p.payment_date) > new Date(latest.payment_date) ? p : latest
    , payments.value[0]);
});

const receiptDate = computed(() => {
    if (latestPayment.value) return formatDate(latestPayment.value.payment_date);
    return formatDate(invoice.value?.invoice_date);
});

function handlePrint() {
    window.print();
}

onMounted(() => {
    setTimeout(() => window.print(), 500);
});
</script>

<template>
    <AdminLayout title="Bundle Booking Receipt">
        <!-- Top Bar (Screen Only) -->
        <div class="screen-only flex items-center justify-between mb-6">
            <Link
                :href="`/admin/package-bundle-bookings/${b.id}`"
                class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Booking
            </Link>
            <button
                @click="handlePrint"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-white rounded-xl text-sm font-semibold transition-all duration-300 shadow-sm hover:opacity-90"
                style="background-color: #C4A265;"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Receipt
            </button>
        </div>

        <!-- Receipt Content -->
        <div class="receipt-container mx-auto max-w-[800px] bg-white rounded-2xl shadow-sm border border-gray-100/80 print:shadow-none print:border-0 print:rounded-none">
            <div class="p-8 print:p-0">

                <!-- Header -->
                <div class="text-center mb-8 pb-6 border-b border-gray-200">
                    <img
                        src="/images/logo.png"
                        alt="AURA Derma Clinic"
                        class="mx-auto h-20 mb-3 object-contain"
                    />
                    <h1 class="text-xl font-bold text-gray-900 tracking-wide">AURA Derma Clinic</h1>
                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800">Package Bundle Receipt</h2>
                    </div>
                </div>

                <!-- Booking & Patient Info -->
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="space-y-2">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Booking Details</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">Booking #:</span>
                            <span class="text-sm font-semibold font-mono" style="color: #C4A265;">{{ b.booking_number }}</span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ $t('a_date') }}:</span>
                            <span class="text-sm font-medium text-gray-800">{{ receiptDate }}</span>
                        </div>
                        <div v-if="invoice?.invoice_number" class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">Invoice #:</span>
                            <span class="text-sm font-semibold font-mono text-gray-700">{{ invoice.invoice_number }}</span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs text-gray-500">{{ $t('a_bundle') }}:</span>
                            <span class="text-sm font-medium text-gray-800">{{ b.package_bundle?.name_en }}</span>
                        </div>
                    </div>
                    <div class="space-y-2 text-right">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Patient Info</h3>
                        <div class="flex items-baseline gap-2 justify-end">
                            <span class="text-xs text-gray-500">Name:</span>
                            <span class="text-sm font-semibold text-gray-800">{{ b.patient?.full_name }}</span>
                        </div>
                        <div class="flex items-baseline gap-2 justify-end">
                            <span class="text-xs text-gray-500">Phone:</span>
                            <span class="text-sm font-medium text-gray-700">{{ b.patient?.phone }}</span>
                        </div>
                        <div v-if="b.patient?.file_number" class="flex items-baseline gap-2 justify-end">
                            <span class="text-xs text-gray-500">File #:</span>
                            <span class="text-sm font-semibold font-mono" style="color: #C4A265;">{{ b.patient.file_number }}</span>
                        </div>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Bundle Services</h3>
                    <table class="w-full text-sm border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">{{ $t('a_service') }}</th>
                                <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">Doctor</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 border-b border-gray-200">{{ $t('a_sessions') }}</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 border-b border-gray-200">{{ $t('a_price') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="bs in b.bundle_services" :key="bs.id" class="hover:bg-gray-50/50 print:hover:bg-transparent">
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-gray-800">{{ bs.service?.name_en }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-gray-600">{{ bs.doctor?.name_en || '-' }}</td>
                                <td class="px-4 py-2.5 text-center text-gray-600">{{ bs.sessions_count }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-gray-800">{{ formatCurrency(bs.bundle_price) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 border-t-2 border-gray-300">
                                <td colspan="3" class="px-4 py-3 text-right text-sm font-bold text-gray-700">
                                    Bundle Total
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold" style="color: #C4A265;">
                                    {{ formatCurrency(invoice?.total) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Details -->
                <div class="mb-8">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Payment Details</h3>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <!-- Payments List -->
                        <table v-if="payments.length > 0" class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">Date</th>
                                    <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">Method</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 border-b border-gray-200">Amount</th>
                                    <th class="px-4 py-2.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 border-b border-gray-200">Ref #</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="payment in payments" :key="payment.id">
                                    <td class="px-4 py-2.5 text-gray-600">{{ formatDate(payment.payment_date) }}</td>
                                    <td class="px-4 py-2.5 text-gray-600">{{ payment.payment_method?.name_en }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                                    <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ payment.reference_number || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Summary -->
                        <div class="bg-gray-50 border-t border-gray-200 px-4 py-4">
                            <div class="grid grid-cols-2 gap-3 max-w-md ml-auto">
                                <div class="flex justify-between col-span-2 text-sm">
                                    <span class="text-gray-500">Subtotal</span>
                                    <span class="font-medium text-gray-800">{{ formatCurrency(invoice?.subtotal) }}</span>
                                </div>
                                <div v-if="Number(invoice?.discount_amount) > 0" class="flex justify-between col-span-2 text-sm">
                                    <span class="text-gray-500">Bundle Discount</span>
                                    <span class="font-medium text-red-600">-{{ formatCurrency(invoice?.discount_amount) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm border-t border-gray-200 pt-2">
                                    <span class="font-bold text-gray-700">Total</span>
                                    <span class="font-bold text-gray-800">{{ formatCurrency(invoice?.total) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm">
                                    <span class="font-bold text-gray-700">Amount Paid</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(totalPaid) }}</span>
                                </div>
                                <div class="flex justify-between col-span-2 text-sm border-t border-gray-200 pt-2">
                                    <span class="font-bold text-gray-700">Remaining Balance</span>
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
                    <p class="text-xs text-gray-400">This is an electronic document and does not require a signature</p>
                </div>

            </div>
        </div>
    </AdminLayout>
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

    /* Hide AdminLayout navigation, header, sidebar */
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
