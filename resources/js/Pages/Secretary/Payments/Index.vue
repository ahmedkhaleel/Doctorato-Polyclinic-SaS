<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    payments: Object,
    filters: Object,
    paymentMethods: Array,
});

const search = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    };
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/secretary/payments', buildParams(), { preserveState: true, replace: true });
    }, 400);
});

watch([dateFrom, dateTo], () => {
    router.get('/secretary/payments', buildParams(), { preserveState: true, replace: true });
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const { formatCurrency, currencyCode } = useCurrency();
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'المدفوعات' : 'Payments' }}</h1>
                <p class="text-sm text-gray-500 mt-1">View all payment transactions</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input v-model="search" type="text" placeholder="Search by reference #, patient, invoice..." class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 w-72" />
            <input v-model="dateFrom" type="date" :max="dateTo || undefined" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
            <input v-model="dateTo" type="date" :min="dateFrom || undefined" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Invoice #</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطريقة' : 'Method' }}</th>
                        <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Reference</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Received By</th>
                        <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 text-gray-500">{{ formatDate(payment.payment_date) }}</td>
                        <td class="px-6 py-3">
                            <Link v-if="payment.invoice" :href="`/secretary/invoices/${payment.invoice.id}`" class="font-mono font-semibold text-teal-600 hover:text-teal-800">
                                {{ payment.invoice.invoice_number }}
                            </Link>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="font-semibold text-gray-800">{{ payment.invoice?.patient?.full_name || '-' }}</div>
                            <div class="text-xs text-gray-400">{{ payment.invoice?.patient?.phone || '' }}</div>
                        </td>
                        <td class="px-6 py-3 text-gray-500">{{ payment.payment_method?.name_en || '-' }}</td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left font-bold text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                        <td class="px-6 py-3 text-gray-500 font-mono">{{ payment.reference_number || '-' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ payment.receiver?.name || '-' }}</td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left">
                            <Link v-if="payment.invoice" :href="`/secretary/invoices/${payment.invoice.id}`" class="text-teal-600 hover:text-teal-800 text-xs font-semibold">
                                View Invoice
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="payments.data?.length === 0" class="py-12 text-center">
                <p class="text-sm text-gray-400">No payments found</p>
            </div>

            <div v-if="payments.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in payments.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-teal-500 text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
