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
    invoice: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const statusColors = {
    paid: 'bg-emerald-100 text-emerald-700',
    partial: 'bg-yellow-100 text-amber-700',
    unpaid: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-500',
};

const balanceDue = computed(() => {
    return (parseFloat(props.invoice?.total || 0) - parseFloat(props.invoice?.paid_amount || 0)).toFixed(2);
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('/invoices')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'تفاصيل الفاتورة' : 'Invoice Details' }}</h1>
        </div>

        <!-- Invoice Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">{{ invoice?.invoice_number }}</h2>
                    <p class="text-sm text-gray-400 mt-0.5">{{ invoice?.invoice_date }}</p>
                </div>
                <span :class="statusColors[invoice?.status] || 'bg-gray-100 text-gray-500'" class="text-sm font-semibold px-4 py-1.5 rounded-full self-start">{{ invoice?.status }}</span>
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'البنود' : 'Items' }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'الكمية' : 'Qty' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'سعر الوحدة' : 'Unit Price' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in invoice?.items" :key="item.id" class="border-b border-gray-50">
                            <td class="px-6 py-3 text-gray-700">{{ item.description || item.service_name }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ item.quantity }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ item.unit_price }}</td>
                            <td class="px-6 py-3 font-medium text-gray-800">{{ item.total || (item.quantity * item.unit_price).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
                <div class="flex flex-col items-end gap-2 text-sm">
                    <div v-if="invoice?.subtotal" class="flex items-center gap-8">
                        <span class="text-gray-500">{{ isRtl ? 'المجموع الفرعي' : 'Subtotal' }}</span>
                        <span class="font-medium text-gray-800 w-24 text-end">{{ formatCurrency(invoice.subtotal) }}</span>
                    </div>
                    <div v-if="invoice?.discount && parseFloat(invoice.discount) > 0" class="flex items-center gap-8">
                        <span class="text-gray-500">{{ isRtl ? 'الخصم' : 'Discount' }}</span>
                        <span class="font-medium text-emerald-600 w-24 text-end">-{{ formatCurrency(invoice.discount) }}</span>
                    </div>
                    <div v-if="invoice?.tax && parseFloat(invoice.tax) > 0" class="flex items-center gap-8">
                        <span class="text-gray-500">{{ isRtl ? 'الضريبة' : 'Tax' }}</span>
                        <span class="font-medium text-gray-800 w-24 text-end">{{ formatCurrency(invoice.tax) }}</span>
                    </div>
                    <div class="flex items-center gap-8 pt-2 border-t border-gray-200">
                        <span class="text-gray-800 font-semibold">{{ isRtl ? 'الإجمالي' : 'Grand Total' }}</span>
                        <span class="font-bold text-gray-900 w-24 text-end text-base">{{ formatCurrency(invoice?.total) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div v-if="invoice?.payments?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'سجل المدفوعات' : 'Payment History' }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'طريقة الدفع' : 'Method' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in invoice.payments" :key="payment.id" class="border-b border-gray-50">
                            <td class="px-6 py-3 text-gray-700">{{ payment.payment_date || payment.created_at?.split('T')[0] }}</td>
                            <td class="px-6 py-3 font-medium text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ payment.payment_method || payment.method }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Balance Due Highlight -->
        <div v-if="parseFloat(balanceDue) > 0" class="bg-red-50 rounded-2xl border border-red-200 p-6 text-center">
            <p class="text-sm text-red-600 font-medium mb-1">{{ isRtl ? 'المبلغ المتبقي' : 'Balance Due' }}</p>
            <p class="text-3xl font-bold text-red-700">{{ formatCurrency(balanceDue) }}</p>
        </div>
        <div v-else-if="invoice?.status === 'paid'" class="bg-emerald-50 rounded-2xl border border-emerald-200 p-6 text-center">
            <p class="text-sm text-emerald-600 font-semibold">{{ isRtl ? 'تم الدفع بالكامل' : 'Fully Paid' }}</p>
        </div>
    </div>
</template>
