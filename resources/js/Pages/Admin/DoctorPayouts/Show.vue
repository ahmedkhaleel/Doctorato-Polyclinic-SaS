<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: AdminLayout });

const { confirm } = useConfirm();

const pg = usePage();
const locale = computed(() => pg.props.locale || 'ar');
const isRtl = computed(() => (pg.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    payout: Object,
});

const { formatCurrency, currencyCode } = useCurrency();

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

function formatDateTime(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' });
}

const statusConfig = {
    draft: { label: 'Draft', bg: 'bg-gray-100', text: 'text-gray-600', ring: 'ring-gray-200' },
    confirmed: { label: 'Confirmed', bg: 'bg-amber-100', text: 'text-amber-700', ring: 'ring-amber-200' },
    paid: { label: 'Paid', bg: 'bg-emerald-100', text: 'text-emerald-700', ring: 'ring-emerald-200' },
    cancelled: { label: 'Cancelled', bg: 'bg-red-100', text: 'text-red-600', ring: 'ring-red-200' },
};

const paymentLabels = {
    cash: 'Cash',
    bank_transfer: 'Bank Transfer',
    check: 'Check',
    mobile_wallet: 'Mobile Wallet',
};

// Confirm action
const confirmForm = useForm({});
function confirmPayout() {
    confirm({ message: 'Are you sure you want to confirm this payout?', confirmColor: 'green' }, () => {
        confirmForm.put(`/admin/doctor-payouts/${props.payout.id}/confirm`);
    });
}

// Mark paid modal
const showPayModal = ref(false);
const payForm = useForm({
    payment_method: 'cash',
    payment_reference: '',
});
function markPaid() {
    payForm.put(`/admin/doctor-payouts/${props.payout.id}/mark-paid`, {
        onSuccess: () => { showPayModal.value = false; },
    });
}

// Cancel modal
const showCancelModal = ref(false);
const cancelForm = useForm({
    cancellation_reason: '',
});
function cancelPayout() {
    cancelForm.put(`/admin/doctor-payouts/${props.payout.id}/cancel`, {
        onSuccess: () => { showCancelModal.value = false; },
    });
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <Link href="/admin/doctor-payouts" class="text-sm text-gray-400 hover:text-[#C4A265]">&larr; {{ $t('a_all_payouts') }}</Link>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ payout.payout_number }}</h1>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full ring-1" :class="[statusConfig[payout.status]?.bg, statusConfig[payout.status]?.text, statusConfig[payout.status]?.ring]">
                        {{ statusConfig[payout.status]?.label }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button v-if="payout.status === 'draft'" @click="confirmPayout" :disabled="confirmForm.processing" class="px-5 py-2.5 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 transition disabled:opacity-50">
                    {{ $t('a_confirm_payout') }}
                </button>
                <button v-if="payout.status === 'confirmed'" @click="showPayModal = true" class="px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition">
                    {{ $t('a_mark_as_paid') }}
                </button>
                <button v-if="payout.status === 'draft' || payout.status === 'confirmed'" @click="showCancelModal = true" class="px-5 py-2.5 bg-white text-red-600 text-sm font-semibold rounded-xl border border-red-200 hover:bg-red-50 transition">
                    {{ $t('a_cancel') }}
                </button>
                <Link v-if="payout.status === 'paid'" :href="`/admin/doctor-payouts/${payout.id}/print`" class="px-5 py-2.5 bg-[#C4A265] text-white text-sm font-semibold rounded-xl hover:bg-[#B3914F] transition">
                    {{ $t('a_print_receipt') }}
                </Link>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Left: Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_doctor') }}</p>
                            <p class="text-sm font-semibold text-gray-800 mt-1">{{ payout.doctor?.name_en }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_period') }}</p>
                            <p class="text-sm font-medium text-gray-700 mt-1">{{ formatDate(payout.period_start) }} — {{ formatDate(payout.period_end) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_created') }}</p>
                            <p class="text-sm text-gray-700 mt-1">{{ formatDateTime(payout.created_at) }}</p>
                            <p class="text-xs text-gray-400">by {{ payout.created_by_user?.name || 'System' }}</p>
                        </div>
                        <div v-if="payout.status === 'confirmed' || payout.status === 'paid'">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_confirmed') }}</p>
                            <p class="text-sm text-gray-700 mt-1">{{ formatDateTime(payout.confirmed_at) }}</p>
                            <p class="text-xs text-gray-400">by {{ payout.confirmed_by_user?.name }}</p>
                        </div>
                        <div v-if="payout.status === 'paid'">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_paid') }}</p>
                            <p class="text-sm text-gray-700 mt-1">{{ formatDateTime(payout.paid_at) }}</p>
                            <p class="text-xs text-gray-400">by {{ payout.paid_by_user?.name }}</p>
                        </div>
                        <div v-if="payout.status === 'paid'">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_payment_method') }}</p>
                            <p class="text-sm font-medium text-gray-700 mt-1">{{ paymentLabels[payout.payment_method] || payout.payment_method }}</p>
                            <p v-if="payout.payment_reference" class="text-xs text-gray-400">Ref: {{ payout.payment_reference }}</p>
                        </div>
                    </div>
                    <div v-if="payout.notes" class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold mb-1">{{ $t('a_notes') }}</p>
                        <p class="text-sm text-gray-600">{{ payout.notes }}</p>
                    </div>
                    <div v-if="payout.status === 'cancelled'" class="mt-4 pt-4 border-t border-red-100 bg-red-50/50 -mx-6 -mb-6 px-4 md:px-6 pb-6 rounded-b-2xl">
                        <p class="text-[10px] text-red-500 uppercase tracking-wide font-semibold mb-1">{{ $t('a_cancellation_reason') }}</p>
                        <p class="text-sm text-red-600">{{ payout.cancellation_reason }}</p>
                        <p class="text-xs text-red-400 mt-1">Cancelled by {{ payout.cancelled_by_user?.name }} at {{ formatDateTime(payout.cancelled_at) }}</p>
                    </div>
                </div>

                <!-- Visits Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">{{ $t('a_included_visits') }} ({{ payout.total_visits }})</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                <th class="px-4 md:px-6 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase">#</th>
                                <th class="px-3 py-2.5 text-start text-[10px] font-semibold text-gray-400 uppercase">{{ $t('a_date') }}</th>
                                <th class="px-3 py-2.5 text-start text-[10px] font-semibold text-gray-400 uppercase">{{ $t('a_patient') }}</th>
                                <th class="px-3 py-2.5 text-start text-[10px] font-semibold text-gray-400 uppercase">{{ $t('a_service') }}</th>
                                <th class="px-3 py-2.5 text-end text-[10px] font-semibold text-gray-400 uppercase">{{ $t('a_amount') }}</th>
                                <th class="px-3 py-2.5 text-end text-[10px] font-semibold text-gray-400 uppercase">{{ $t('a_rate') }}</th>
                                <th class="px-4 md:px-6 py-2.5 text-end text-[10px] font-semibold text-gray-400 uppercase">{{ $t('a_commission') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(visit, i) in payout.visits" :key="visit.id" class="hover:bg-gray-50/50">
                                <td class="px-4 md:px-6 py-3 text-xs text-gray-400">{{ i + 1 }}</td>
                                <td class="px-3 py-3 text-xs text-gray-600">{{ formatDate(visit.visit_date) }}</td>
                                <td class="px-3 py-3 text-xs font-medium text-gray-800">{{ visit.patient?.full_name }}</td>
                                <td class="px-3 py-3 text-xs text-gray-600">
                                    {{ visit.service?.name_en || (visit.visit_type === 'consultation' ? 'Consultation' : 'Session') }}
                                </td>
                                <td class="px-3 py-3 text-xs text-right text-gray-600">{{ formatCurrency(visit.pivot?.visit_amount) }}</td>
                                <td class="px-3 py-3 text-xs text-right text-gray-600">{{ visit.pivot?.commission_rate }}%</td>
                                <td class="px-4 md:px-6 py-3 text-xs text-right font-semibold text-[#C4A265]">{{ formatCurrency(visit.pivot?.commission_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Financial Summary -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-4">{{ $t('a_financial_summary') }}</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ $t('a_total_visits') }}</span>
                            <span class="font-semibold text-gray-800">{{ payout.total_visits }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ $t('a_total_revenue') }}</span>
                            <span class="font-medium text-gray-700">{{ formatCurrency(payout.total_revenue) }}</span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-100 pt-2">
                            <span class="text-gray-500">{{ $t('a_total_commission') }}</span>
                            <span class="font-semibold text-[#C4A265]">{{ formatCurrency(payout.total_commission) }}</span>
                        </div>
                        <div v-if="parseFloat(payout.deductions) > 0" class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                {{ $t('a_deductions') }}
                                <span v-if="payout.deduction_notes" class="block text-[10px] text-gray-400">{{ payout.deduction_notes }}</span>
                            </span>
                            <span class="font-medium text-red-500">-{{ formatCurrency(payout.deductions) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t-2 border-[#C4A265]/30">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-gray-700">{{ $t('a_net_amount') }}</span>
                            <span class="text-xl font-bold text-[#C4A265]">{{ formatCurrency(payout.net_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status Timeline -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-4">{{ $t('a_status_timeline') }}</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-700">{{ $t('a_created') }}</p>
                                <p class="text-[10px] text-gray-400">{{ formatDateTime(payout.created_at) }}</p>
                            </div>
                        </div>
                        <div v-if="payout.confirmed_at" class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-amber-200 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-amber-700">{{ $t('a_confirmed') }}</p>
                                <p class="text-[10px] text-gray-400">{{ formatDateTime(payout.confirmed_at) }}</p>
                            </div>
                        </div>
                        <div v-if="payout.paid_at" class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-200 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-emerald-700">{{ $t('a_paid') }}</p>
                                <p class="text-[10px] text-gray-400">{{ formatDateTime(payout.paid_at) }}</p>
                            </div>
                        </div>
                        <div v-if="payout.cancelled_at" class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-red-200 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-red-700">{{ $t('a_cancelled') }}</p>
                                <p class="text-[10px] text-gray-400">{{ formatDateTime(payout.cancelled_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark as Paid Modal -->
        <Teleport to="body">
            <div v-if="showPayModal" v-focus-trap="() => (showPayModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showPayModal = false">
                <div class="bg-white rounded-2xl shadow-2xl p-4 md:p-6 w-full max-w-md">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $t('a_mark_as_paid') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">{{ $t('a_payment_method') }} *</label>
                            <select v-model="payForm.payment_method" class="doctorato-input mt-1 w-full rounded-lg border-gray-200 text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                                <option value="cash">{{ $t('a_cash') }}</option>
                                <option value="bank_transfer">{{ $t('a_bank_transfer') }}</option>
                                <option value="check">{{ $t('a_check') }}</option>
                                <option value="mobile_wallet">{{ $t('a_mobile_wallet') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">{{ $t('a_reference_number') }}</label>
                            <input type="text" v-model="payForm.payment_reference" placeholder="Check #, transfer ref, etc." class="doctorato-input mt-1 w-full rounded-lg border-gray-200 text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        </div>
                        <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-200">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-emerald-700">{{ $t('a_amount_to_pay') }}</span>
                                <span class="text-lg font-bold text-emerald-700">{{ formatCurrency(payout.net_amount) }}</span>
                            </div>
                        </div>
                        <div v-if="payForm.errors && Object.keys(payForm.errors).length" class="p-3 bg-red-50 rounded-lg">
                            <p v-for="(err, key) in payForm.errors" :key="key" class="text-xs text-red-600">{{ err }}</p>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button @click="showPayModal = false" class="flex-1 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">{{ $t('a_cancel') }}</button>
                        <button @click="markPaid" :disabled="payForm.processing" class="flex-1 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition disabled:opacity-50">
                            {{ payForm.processing ? $t('a_processing') : $t('a_confirm_payment') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Cancel Modal -->
        <Teleport to="body">
            <div v-if="showCancelModal" v-focus-trap="() => (showCancelModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showCancelModal = false">
                <div class="bg-white rounded-2xl shadow-2xl p-4 md:p-6 w-full max-w-md">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $t('a_cancel_payout') }}</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ $t('a_cancel_payout_warning') }}</p>
                    <div>
                        <label class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">{{ $t('a_reason') }} *</label>
                        <textarea v-model="cancelForm.cancellation_reason" rows="3" placeholder="Why is this payout being cancelled?" class="doctorato-input mt-1 w-full rounded-lg border-gray-200 text-sm focus:ring-[#C4A265] focus:border-[#C4A265]"></textarea>
                    </div>
                    <div v-if="cancelForm.errors && Object.keys(cancelForm.errors).length" class="mt-3 p-3 bg-red-50 rounded-lg">
                        <p v-for="(err, key) in cancelForm.errors" :key="key" class="text-xs text-red-600">{{ err }}</p>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button @click="showCancelModal = false" class="flex-1 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">{{ $t('a_back') }}</button>
                        <button @click="cancelPayout" :disabled="!cancelForm.cancellation_reason || cancelForm.processing" class="flex-1 py-2.5 text-sm font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 transition disabled:opacity-50">
                            {{ cancelForm.processing ? $t('a_cancelling') : $t('a_cancel_payout') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
