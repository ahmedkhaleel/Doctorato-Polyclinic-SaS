<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    invoice: Object,
    paymentMethods: Array,
});

const showPaymentForm = ref(false);

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

const { formatCurrency, currencyCode } = useCurrency();

const statusColors = {
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    partial: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    unpaid: 'bg-red-50 text-red-700 border-red-200',
    cancelled: 'bg-gray-50 text-gray-600 border-gray-200',
};

const balance = props.invoice.total - props.invoice.paid_amount;

function submitPayment() {
    paymentForm
        .transform((data) => ({
            ...data,
            invoice_id: props.invoice.id,
        }))
        .post('/secretary/payments', {
            preserveScroll: true,
            onSuccess: () => {
                paymentForm.reset();
                showPaymentForm.value = false;
            },
        });
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Invoice {{ invoice.invoice_number }}</h1>
                <p class="text-sm text-gray-500 mt-1">Created {{ formatDate(invoice.created_at) }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span :class="statusColors[invoice.status]" class="px-3 py-1 text-xs font-semibold rounded-full border capitalize">{{ invoice.status }}</span>
                <Link href="/secretary/invoices" class="text-sm text-gray-500 hover:text-gray-700">← Back</Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Patient & Invoice Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">{{ isRtl ? 'المريض' : 'Patient' }}</h3>
                            <p class="text-sm font-semibold text-gray-900">{{ invoice.patient?.full_name }}</p>
                            <p class="text-sm text-gray-500">{{ invoice.patient?.phone }}</p>
                            <p v-if="invoice.patient?.file_number" class="text-sm font-mono text-teal-600">{{ invoice.patient.file_number }}</p>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">{{ isRtl ? 'تفاصيل الفاتورة' : 'Invoice Details' }}</h3>
                            <p class="text-sm text-gray-900">Invoice #: <span class="font-mono font-semibold text-teal-600">{{ invoice.invoice_number }}</span></p>
                            <p class="text-sm text-gray-500">Date: {{ formatDate(invoice.invoice_date || invoice.created_at) }}</p>
                            <p v-if="invoice.visit" class="text-sm text-gray-500 mt-1">
                                Visit: {{ formatDate(invoice.visit.visit_date) }} - {{ invoice.visit.service?.name_en || invoice.visit.visit_type }}
                            </p>
                            <p v-if="invoice.creator" class="text-sm text-gray-400 mt-1">Created by: {{ invoice.creator.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">Items</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الخصم' : 'Discount' }}</th>
                                <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-gray-50/50">
                                <td class="px-6 py-3">
                                    <div class="font-medium text-gray-900">{{ item.description_en }}</div>
                                    <div v-if="item.description_ar" class="text-gray-400 text-xs" dir="rtl">{{ item.description_ar }}</div>
                                </td>
                                <td class="px-6 py-3 text-gray-500">{{ item.quantity }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ item.discount > 0 ? formatCurrency(item.discount) : '-' }}</td>
                                <td class="px-6 py-3 ltr:text-right rtl:text-left font-bold text-gray-800">{{ formatCurrency(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Payments Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'المدفوعات' : 'Payments' }}</h2>
                        <button
                            v-if="invoice.status !== 'paid' && invoice.status !== 'cancelled'"
                            type="button"
                            @click="showPaymentForm = !showPaymentForm"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-500 text-white rounded-lg text-xs font-semibold hover:bg-teal-600 transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add Payment
                        </button>
                    </div>

                    <!-- Payment Form -->
                    <div v-if="showPaymentForm" class="px-6 py-4 bg-teal-50/50 border-b border-teal-100">
                        <form @submit.prevent="submitPayment" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Payment Method <span class="text-red-500">*</span></label>
                                <select v-model="paymentForm.payment_method_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                                    <option value="">Select Method</option>
                                    <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.name_en }}</option>
                                </select>
                                <p v-if="paymentForm.errors.payment_method_id" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.payment_method_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Amount <span class="text-red-500">*</span></label>
                                <input v-model.number="paymentForm.amount" type="number" min="0" step="0.01" :placeholder="`Balance: ${formatCurrency(balance)}`" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                                <p v-if="paymentForm.errors.amount" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.amount }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Payment Date <span class="text-red-500">*</span></label>
                                <input v-model="paymentForm.payment_date" type="date" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Reference #</label>
                                <input v-model="paymentForm.reference_number" type="text" :placeholder="isRtl ? 'رقم الإيصال / المعاملة' : 'Receipt / Transaction #'" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <input v-model="paymentForm.notes" type="text" :placeholder="isRtl ? 'ملاحظات اختيارية' : 'Optional notes'" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" :disabled="paymentForm.processing" class="px-4 py-2.5 bg-teal-500 text-white rounded-xl text-sm font-semibold hover:bg-teal-600 transition disabled:opacity-50">
                                    {{ paymentForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ الدفعة' : 'Save Payment') }}
                                </button>
                                <button type="button" @click="showPaymentForm = false" class="px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                            </div>
                        </form>
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطريقة' : 'Method' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-gray-500 uppercase">Received By</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="payment in invoice.payments" :key="payment.id" class="hover:bg-gray-50/50">
                                <td class="px-6 py-3 text-gray-500">{{ formatDate(payment.payment_date) }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ payment.payment_method?.name_en || '-' }}</td>
                                <td class="px-6 py-3 font-bold text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                                <td class="px-6 py-3 text-gray-500 font-mono">{{ payment.reference_number || '-' }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ payment.receiver?.name || '-' }}</td>
                                <td class="px-6 py-3 text-center">
                                    <a :href="`/secretary/invoices/${invoice.id}/payments/${payment.id}/receipt`"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-teal-300 text-teal-600 transition-all hover:bg-teal-50"
                                       :title="isRtl ? 'طباعة إيصال الدفع' : 'Print payment receipt'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                        {{ isRtl ? 'طباعة' : 'Print' }}
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="!invoice.payments || invoice.payments.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No payments recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sidebar Summary -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Summary</h2>
                    <dl class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">Subtotal</dt>
                            <dd class="text-gray-900">{{ formatCurrency(invoice.subtotal) }}</dd>
                        </div>
                        <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ isRtl ? 'الخصم' : 'Discount' }}</dt>
                            <dd class="text-red-600">-{{ formatCurrency(invoice.discount_amount) }}</dd>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t border-gray-100 pt-3">
                            <dt class="text-gray-800">{{ isRtl ? 'الإجمالي' : 'Total' }}</dt>
                            <dd class="text-teal-600">{{ formatCurrency(invoice.total) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-100 pt-3">
                            <dt class="text-gray-500">Paid Amount</dt>
                            <dd class="text-emerald-600 font-medium">{{ formatCurrency(invoice.paid_amount) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm font-bold">
                            <dt class="text-gray-800">{{ isRtl ? 'الرصيد' : 'Balance' }}</dt>
                            <dd :class="balance > 0 ? 'text-red-600' : 'text-emerald-600'">{{ formatCurrency(balance) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Quick Links</h2>
                    <div class="space-y-2">
                        <Link v-if="invoice.patient" :href="`/secretary/patients/${invoice.patient.id}`" class="flex items-center gap-2 text-sm font-medium text-teal-600 hover:text-teal-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            View Patient Profile
                        </Link>
                        <Link v-if="invoice.visit" :href="`/secretary/visits/${invoice.visit.id}`" class="flex items-center gap-2 text-sm font-medium text-teal-600 hover:text-teal-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            View Visit Details
                        </Link>
                    </div>
                </div>

                <div v-if="invoice.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-sm font-bold text-gray-800 mb-2">{{ isRtl ? 'ملاحظات' : 'Notes' }}</h2>
                    <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ invoice.notes }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
