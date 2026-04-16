<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    patient: { type: Object, required: true },
    financialSummary: { type: Object, default: null },
    role: { type: String, default: 'admin' },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function invoiceHref(i) {
    if (props.role === 'admin') return `/admin/invoices/${i.id}`;
    if (props.role === 'doctor') return null;
    if (props.role === 'secretary') return `/secretary/invoices/${i.id}`;
    return null;
}

function formatDate(d) {
    if (!d) return '-';
    try {
        return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch { return d; }
}

function statusClass(s) {
    if (s === 'paid') return 'bg-emerald-50 text-emerald-600';
    if (s === 'partial') return 'bg-amber-50 text-amber-600';
    if (s === 'unpaid') return 'bg-red-50 text-red-600';
    if (s === 'cancelled') return 'bg-gray-50 text-gray-500';
    return 'bg-gray-50 text-gray-600';
}

function statusLabel(s) {
    const ar = { paid: 'مدفوع', partial: 'جزئي', unpaid: 'غير مدفوع', cancelled: 'ملغي' };
    const en = { paid: 'Paid', partial: 'Partial', unpaid: 'Unpaid', cancelled: 'Cancelled' };
    return (isRtl.value ? ar[s] : en[s]) || s;
}
</script>

<template>
    <div class="space-y-5">
        <!-- Financial Summary Card -->
        <div v-if="financialSummary" class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 mb-1">{{ isRtl ? 'إجمالي الفواتير' : 'Total Invoiced' }}</p>
                    <p class="text-xl font-bold text-gray-800">{{ (financialSummary.total_invoiced || 0).toLocaleString() }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">{{ isRtl ? 'المدفوع' : 'Total Paid' }}</p>
                    <p class="text-xl font-bold text-emerald-600">{{ (financialSummary.total_paid || 0).toLocaleString() }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">{{ isRtl ? 'المتبقي' : 'Outstanding' }}</p>
                    <p class="text-xl font-bold" :class="(financialSummary.outstanding_balance || 0) > 0 ? 'text-red-500' : 'text-emerald-600'">
                        {{ (financialSummary.outstanding_balance || 0).toLocaleString() }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Invoices List -->
        <div v-if="invoices.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="divide-y divide-gray-50">
                <div v-for="invoice in invoices" :key="invoice.id" class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50 transition">
                    <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800">#{{ invoice.invoice_number || invoice.id }}</p>
                        <p class="text-[10px] text-gray-400">{{ formatDate(invoice.invoice_date || invoice.created_at) }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-gray-800">{{ Number(invoice.total || 0).toLocaleString() }}</p>
                        <p v-if="invoice.paid_amount" class="text-[10px] text-emerald-600">
                            {{ isRtl ? 'مدفوع:' : 'Paid:' }} {{ Number(invoice.paid_amount).toLocaleString() }}
                        </p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0" :class="statusClass(invoice.status)">
                        {{ statusLabel(invoice.status) }}
                    </span>
                    <Link v-if="invoiceHref(invoice)" :href="invoiceHref(invoice)"
                        class="text-xs font-semibold text-[#C4A265] hover:text-[#8B6F3F] transition px-2 flex-shrink-0">
                        {{ isRtl ? 'عرض' : 'View' }}
                    </Link>
                </div>
            </div>
        </div>

        <div v-else class="bg-gray-50 rounded-xl p-8 text-center">
            <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد فواتير' : 'No invoices' }}</p>
        </div>
    </div>
</template>
