<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    invoice: Object,
    paymentMethods: Array,
});

const showPaymentForm = ref(false);

// Inline editing for invoice date & notes
const editingInvoice = ref(false);
const invoiceEditForm = useForm({
    invoice_date: props.invoice?.invoice_date
        ? props.invoice.invoice_date.split('T')[0]
        : (props.invoice?.created_at ? props.invoice.created_at.split('T')[0] : ''),
    notes: props.invoice?.notes || '',
});

function saveInvoiceEdit() {
    invoiceEditForm.put(`/admin/invoices/${props.invoice.id}`, {
        preserveScroll: true,
        onSuccess: () => { editingInvoice.value = false; },
    });
}

function cancelInvoiceEdit() {
    invoiceEditForm.invoice_date = props.invoice?.invoice_date
        ? props.invoice.invoice_date.split('T')[0]
        : (props.invoice?.created_at ? props.invoice.created_at.split('T')[0] : '');
    invoiceEditForm.notes = props.invoice?.notes || '';
    editingInvoice.value = false;
}

const paymentForm = useForm({
    payment_method_id: '',
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    reference_number: '',
    notes: '',
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const statusColors = {
    paid: 'bg-green-100 text-green-800',
    partial: 'bg-yellow-100 text-yellow-800',
    unpaid: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
};

const statusTranslations = {
    paid: { en: 'Paid', ar: 'مدفوع' },
    partial: { en: 'Partial', ar: 'جزئي' },
    unpaid: { en: 'Unpaid', ar: 'غير مدفوع' },
    cancelled: { en: 'Cancelled', ar: 'ملغي' },
};

function statusLabel(status) {
    return statusTranslations[status]?.[locale.value] || status;
}

const balance = props.invoice.total - props.invoice.paid_amount;

function submitPayment() {
    paymentForm
        .transform((data) => ({
            ...data,
            invoice_id: props.invoice.id,
        }))
        .post('/admin/payments', {
            preserveScroll: true,
            onSuccess: () => {
                paymentForm.reset();
                showPaymentForm.value = false;
            },
        });
}
</script>

<template>
    <AdminLayout :title="`${$t('a_invoice_number')}: ${invoice.invoice_number}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_invoice_number') }} {{ invoice.invoice_number }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_created') }} {{ formatDate(invoice.created_at) }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        :class="statusColors[invoice.status]"
                        class="px-3 py-1 text-sm font-semibold rounded-full"
                    >
                        {{ statusLabel(invoice.status) }}
                    </span>
                    <Link
                        :href="`/admin/invoices/${invoice.id}/print`"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium transition"
                        style="border-color: #C4A265; color: #C4A265;"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        {{ $t('a_print') }}
                    </Link>
                    <a
                        :href="`/admin/invoices/${invoice.id}/pdf`"
                        class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium transition border-green-400 text-green-700 hover:bg-green-50"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF
                    </a>
                    <Link href="/admin/invoices" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_invoices') }}</Link>
                </div>
            </div>

            <!-- Invoice Info -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Patient & Visit Info -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-2">{{ $t('a_patient') }}</h3>
                                <p class="text-sm font-medium text-gray-900">
                                    <Link v-if="invoice.patient && can('patients.view')" :href="`/admin/patients/${invoice.patient.id}`" class="hover:underline" style="color: #C4A265;">{{ invoice.patient.full_name }}</Link>
                                    <span v-else>{{ invoice.patient?.full_name }}</span>
                                </p>
                                <p class="text-sm text-gray-500">{{ invoice.patient?.phone }}</p>
                                <p v-if="invoice.patient?.file_number" class="text-sm font-mono" style="color: #C4A265;">{{ invoice.patient.file_number }}</p>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-sm font-medium text-gray-500">{{ $t('a_invoice_details') }}</h3>
                                    <button
                                        v-if="can('invoices.update') && !editingInvoice"
                                        @click="editingInvoice = true"
                                        class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded hover:underline transition"
                                        style="color: #C4A265;"
                                    >
                                        <svg class="w-3.5 h-3.5 ltr:mr-0.5 rtl:ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        {{ $t('a_edit') }}
                                    </button>
                                </div>
                                <p class="text-sm text-gray-900">{{ $t('a_invoice_number') }}: <span class="font-mono font-medium" style="color: #C4A265;">{{ invoice.invoice_number }}</span></p>

                                <!-- View Mode -->
                                <template v-if="!editingInvoice">
                                    <p class="text-sm text-gray-500">{{ $t('a_date') }}: {{ formatDate(invoice.invoice_date || invoice.created_at) }}</p>
                                </template>

                                <!-- Edit Mode -->
                                <template v-else>
                                    <div class="mt-2 space-y-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('a_invoice_date') }}</label>
                                            <input
                                                v-model="invoiceEditForm.invoice_date"
                                                type="date"
                                                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                            />
                                            <p v-if="invoiceEditForm.errors.invoice_date" class="mt-1 text-xs text-red-600">{{ invoiceEditForm.errors.invoice_date }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                                            <textarea
                                                v-model="invoiceEditForm.notes"
                                                rows="3"
                                                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                                :placeholder="$t('a_invoice_notes')"
                                            ></textarea>
                                            <p v-if="invoiceEditForm.errors.notes" class="mt-1 text-xs text-red-600">{{ invoiceEditForm.errors.notes }}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                @click="saveInvoiceEdit"
                                                :disabled="invoiceEditForm.processing"
                                                class="px-3 py-1.5 rounded-lg text-white text-xs font-medium transition disabled:opacity-50"
                                                style="background-color: #C4A265;"
                                            >
                                                {{ invoiceEditForm.processing ? $t('a_saving') : $t('a_save') }}
                                            </button>
                                            <button
                                                @click="cancelInvoiceEdit"
                                                class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-xs font-medium transition hover:bg-gray-50"
                                            >
                                                {{ $t('a_cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <p v-if="invoice.visit" class="text-sm text-gray-500 mt-1">
                                    {{ $t('a_visit') }}:
                                    <Link v-if="can('visits.view')" :href="`/admin/visits/${invoice.visit.id}`" class="font-medium hover:underline" style="color: #C4A265;">
                                        {{ formatDate(invoice.visit.visit_date) }} - {{ invoice.visit.service?.name_en || invoice.visit.visit_type }}
                                    </Link>
                                    <span v-else>{{ formatDate(invoice.visit.visit_date) }} - {{ invoice.visit.service?.name_en || invoice.visit.visit_type }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_items') }}</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_description') }}</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_qty') }}</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_unit_price') }}</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_discount') }}</th>
                                        <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_total') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ item.description_en }}</div>
                                            <div v-if="item.description_ar" class="text-sm text-gray-400" dir="rtl">{{ item.description_ar }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ item.quantity }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatCurrency(item.unit_price) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ item.discount > 0 ? formatCurrency(item.discount) : '-' }}</td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 text-end">{{ formatCurrency(item.total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_payments') }}</h2>
                            <button
                                v-if="can('payments.create') && invoice.status !== 'paid' && invoice.status !== 'cancelled'"
                                type="button"
                                @click="showPaymentForm = !showPaymentForm"
                                class="inline-flex items-center px-3 py-1 rounded text-sm font-medium text-white transition"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('a_add_payment') }}
                            </button>
                        </div>

                        <!-- Add Payment Form -->
                        <div v-if="showPaymentForm" class="px-6 py-4 bg-yellow-50 border-b border-yellow-100">
                            <form @submit.prevent="submitPayment" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_payment_method') }} <span class="text-red-500">*</span></label>
                                    <select
                                        v-model="paymentForm.payment_method_id"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                    >
                                        <option value="">{{ $t('a_select_method') }}</option>
                                        <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.name_en }}</option>
                                    </select>
                                    <p v-if="paymentForm.errors.payment_method_id" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.payment_method_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_amount') }} <span class="text-red-500">*</span></label>
                                    <input
                                        v-model.number="paymentForm.amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :placeholder="`${$t('a_balance')}: ${formatCurrency(balance)}`"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                    />
                                    <p v-if="paymentForm.errors.amount" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.amount }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_payment_date') }} <span class="text-red-500">*</span></label>
                                    <input
                                        v-model="paymentForm.payment_date"
                                        type="date"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                    />
                                    <p v-if="paymentForm.errors.payment_date" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.payment_date }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_reference_number') }}</label>
                                    <input
                                        v-model="paymentForm.reference_number"
                                        type="text"
                                        :placeholder="$t('a_transaction_receipt')"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                                    <input
                                        v-model="paymentForm.notes"
                                        type="text"
                                        :placeholder="$t('a_optional_notes')"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                    />
                                </div>
                                <div class="flex items-end gap-2">
                                    <button
                                        type="submit"
                                        :disabled="paymentForm.processing"
                                        class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                        style="background-color: #C4A265;"
                                    >
                                        {{ paymentForm.processing ? $t('a_saving') : $t('a_save_payment') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showPaymentForm = false"
                                        class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"
                                    >
                                        {{ $t('a_cancel') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_method') }}</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_amount') }}</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_reference') }}</th>
                                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_received_by') }}</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_receipt') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="payment in invoice.payments" :key="payment.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(payment.payment_date) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ payment.payment_method?.name_en || '-' }}</td>
                                        <td class="px-6 py-4 text-sm font-medium text-green-600">{{ formatCurrency(payment.amount) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ payment.reference_number || '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ payment.receiver?.name || '-' }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <a :href="`/admin/invoices/${invoice.id}/payments/${payment.id}/receipt`"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg border transition-all hover:opacity-80"
                                               style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);"
                                               :title="$t('a_print_payment_receipt')">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                                {{ $t('a_print') }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="!invoice.payments || invoice.payments.length === 0">
                                        <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-500">{{ $t('a_no_payments_recorded') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Totals -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">{{ $t('a_summary') }}</h2>
                        <dl class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">{{ $t('a_subtotal') }}</dt>
                                <dd class="text-gray-900">{{ formatCurrency(invoice.subtotal) }}</dd>
                            </div>
                            <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm">
                                <dt class="text-gray-500">{{ $t('a_discount') }}</dt>
                                <dd class="text-red-600">-{{ formatCurrency(invoice.discount_amount) }}</dd>
                            </div>
                            <div v-if="invoice.discount_code" class="text-xs text-gray-400">
                                {{ $t('a_code') }}: {{ invoice.discount_code }}
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-3">
                                <dt class="text-gray-800">{{ $t('a_total') }}</dt>
                                <dd style="color: #C4A265;">{{ formatCurrency(invoice.total) }}</dd>
                            </div>
                            <div class="flex justify-between text-sm border-t pt-3">
                                <dt class="text-gray-500">{{ $t('a_paid_amount') }}</dt>
                                <dd class="text-green-600 font-medium">{{ formatCurrency(invoice.paid_amount) }}</dd>
                            </div>
                            <div class="flex justify-between text-sm font-bold">
                                <dt class="text-gray-800">{{ $t('a_balance') }}</dt>
                                <dd :class="balance > 0 ? 'text-red-600' : 'text-green-600'">{{ formatCurrency(balance) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">{{ $t('a_quick_links') }}</h2>
                        <div class="space-y-2">
                            <Link v-if="invoice.patient && can('patients.view')" :href="`/admin/patients/${invoice.patient.id}`" class="flex items-center gap-2 text-sm font-medium hover:underline transition" style="color: #C4A265;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                {{ $t('a_view_patient_profile') }}
                            </Link>
                            <Link v-if="invoice.visit && can('visits.view')" :href="`/admin/visits/${invoice.visit.id}`" class="flex items-center gap-2 text-sm font-medium hover:underline transition" style="color: #C4A265;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                {{ $t('a_view_visit_details') }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="invoice.notes || can('invoices.update')" class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between border-b pb-2 mb-4">
                            <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_notes') }}</h2>
                            <button
                                v-if="can('invoices.update') && !editingInvoice"
                                @click="editingInvoice = true"
                                class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded hover:underline transition"
                                style="color: #C4A265;"
                            >
                                <svg class="w-3.5 h-3.5 ltr:mr-0.5 rtl:ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                {{ $t('a_edit') }}
                            </button>
                        </div>
                        <p v-if="invoice.notes" class="text-sm text-gray-600 whitespace-pre-wrap">{{ invoice.notes }}</p>
                        <p v-else class="text-sm text-gray-400 italic">{{ $t('a_no_notes') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
