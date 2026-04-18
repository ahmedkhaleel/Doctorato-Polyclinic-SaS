<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    wallet: Object,
    transactions: Object,
    outstandingBalance: Number,
});

/* ── Modals ─────────────────────────────────────── */
const showDepositModal = ref(false);
const showWithdrawModal = ref(false);
const showAdjustModal = ref(false);

const depositForm = useForm({
    amount: '',
    description: '',
    notes: '',
});

const withdrawForm = useForm({
    amount: '',
    description: '',
    type: 'withdrawal',
    notes: '',
});

const adjustForm = useForm({
    amount: '',
    description: '',
    notes: '',
});

function submitDeposit() {
    depositForm.post(`/admin/wallets/${props.patient.id}/deposit`, {
        preserveScroll: true,
        onSuccess: () => { showDepositModal.value = false; depositForm.reset(); },
    });
}

function submitWithdraw() {
    withdrawForm.post(`/admin/wallets/${props.patient.id}/withdraw`, {
        preserveScroll: true,
        onSuccess: () => { showWithdrawModal.value = false; withdrawForm.reset(); },
    });
}

function submitAdjust() {
    adjustForm.post(`/admin/wallets/${props.patient.id}/adjust`, {
        preserveScroll: true,
        onSuccess: () => { showAdjustModal.value = false; adjustForm.reset(); },
    });
}

/* ── Transaction type config ────────────────────── */
const typeConfig = {
    deposit:       { labelEn: 'Deposit',    labelAr: 'إيداع',       color: 'bg-emerald-100 text-emerald-700', sign: '+' },
    withdrawal:    { labelEn: 'Withdrawal', labelAr: 'سحب',         color: 'bg-red-100 text-red-700',         sign: '-' },
    refund_credit: { labelEn: 'Refund',     labelAr: 'رد مبلغ',     color: 'bg-slate-100 text-[#1B365D]',       sign: '+' },
    payment:       { labelEn: 'Payment',    labelAr: 'دفع فاتورة',  color: 'bg-amber-100 text-[#C4A265]',   sign: '-' },
    adjustment:    { labelEn: 'Adjustment', labelAr: 'تعديل',       color: 'bg-slate-100 text-[#1B365D]',   sign: '~' },
};

function getTypeConfig(type) {
    return typeConfig[type] || { labelEn: type, labelAr: type, color: 'bg-gray-100 text-gray-700', sign: '' };
}

function formatDateTime(iso) {
    return new Date(iso).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <AdminLayout :title="isRtl ? `محفظة ${patient.full_name}` : `${patient.full_name}'s Wallet`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <Link href="/admin/wallets" class="text-sm text-gray-500 hover:text-[#1B365D] transition">
                            {{ isRtl ? 'محافظ المرضى' : 'Patient Wallets' }}
                        </Link>
                        <span class="text-gray-300">/</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ patient.full_name }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ patient.file_number }} &bull; {{ patient.phone }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="showDepositModal = true"
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition">
                        {{ isRtl ? '+ إيداع' : '+ Deposit' }}
                    </button>
                    <button @click="showWithdrawModal = true"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                        {{ isRtl ? '- سحب' : '- Withdraw' }}
                    </button>
                    <button @click="showAdjustModal = true"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        {{ isRtl ? 'تعديل الرصيد' : 'Adjust' }}
                    </button>
                </div>
            </div>

            <!-- Balance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-[#1B365D] to-[#1B365D] rounded-xl p-4 md:p-6 text-white">
                    <div class="text-sm opacity-80">{{ isRtl ? 'رصيد المحفظة' : 'Wallet Balance' }}</div>
                    <div class="text-2xl md:text-3xl font-bold mt-2">{{ formatCurrency(wallet.balance) }}</div>
                </div>
                <div class="bg-white rounded-xl border border-red-200 p-6">
                    <div class="text-sm text-gray-500">{{ isRtl ? 'فواتير غير مسددة' : 'Outstanding Invoices' }}</div>
                    <div class="text-2xl md:text-3xl font-bold text-red-600 mt-2">{{ formatCurrency(outstandingBalance) }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="text-sm text-gray-500">{{ isRtl ? 'صافي الوضع' : 'Net Position' }}</div>
                    <div class="text-2xl md:text-3xl font-bold mt-2"
                        :class="Number(wallet.balance) - outstandingBalance >= 0 ? 'text-emerald-600' : 'text-red-600'">
                        {{ formatCurrency(Number(wallet.balance) - outstandingBalance) }}
                    </div>
                </div>
            </div>

            <!-- Transactions History -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-4 md:px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">{{ isRtl ? 'سجل المعاملات' : 'Transaction History' }}</h3>
                </div>

                <div v-if="transactions.data?.length" class="divide-y divide-gray-50">
                    <div v-for="tx in transactions.data" :key="tx.id"
                        class="flex items-center gap-4 px-4 md:px-6 py-4 hover:bg-gray-50/50 transition">
                        <!-- Type Icon -->
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full"
                                :class="getTypeConfig(tx.type).color">
                                <svg v-if="tx.type === 'deposit' || tx.type === 'refund_credit'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0 0l-4-4m4 4l4-4" /></svg>
                                <svg v-else-if="tx.type === 'withdrawal' || tx.type === 'payment'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V4m0 0l-4 4m4-4l4 4" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900">{{ tx.description }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                                    :class="getTypeConfig(tx.type).color">
                                    {{ isRtl ? getTypeConfig(tx.type).labelAr : getTypeConfig(tx.type).labelEn }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="text-xs text-gray-400">{{ formatDateTime(tx.created_at) }}</span>
                                <span v-if="tx.creator" class="text-xs text-gray-400">{{ isRtl ? 'بواسطة' : 'by' }} {{ tx.creator?.name }}</span>
                            </div>
                            <p v-if="tx.notes" class="text-xs text-gray-500 mt-1">{{ tx.notes }}</p>
                        </div>

                        <!-- Amount -->
                        <div class="flex-shrink-0 text-end">
                            <div class="text-sm font-bold"
                                :class="Number(tx.amount) > 0 ? 'text-emerald-600' : 'text-red-600'">
                                {{ Number(tx.amount) > 0 ? '+' : '' }}{{ formatCurrency(tx.amount) }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ isRtl ? 'الرصيد:' : 'Balance:' }} {{ formatCurrency(tx.balance_after) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="p-16 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    <p class="text-gray-500">{{ isRtl ? 'لا توجد معاملات بعد' : 'No transactions yet' }}</p>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links?.length > 3" class="flex justify-center gap-1 p-4 border-t border-gray-100">
                    <template v-for="link in transactions.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url"
                            class="px-3 py-1.5 text-sm rounded-lg border transition"
                            :class="link.active ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            v-html="link.label" preserve-state />
                        <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Deposit Modal -->
        <Teleport to="body">
            <div v-if="showDepositModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40" @click="showDepositModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-4 md:p-6 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'إيداع مبلغ' : 'Deposit Funds' }}</h3>
                    <form @submit.prevent="submitDeposit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المبلغ' : 'Amount' }} *</label>
                            <input v-model="depositForm.amount" type="number" step="0.01" min="0.01" required
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]" />
                            <p v-if="depositForm.errors.amount" class="text-red-500 text-xs mt-1">{{ depositForm.errors.amount }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الوصف' : 'Description' }} *</label>
                            <input v-model="depositForm.description" type="text" required maxlength="255"
                                :placeholder="isRtl ? 'مثال: إيداع نقدي' : 'e.g., Cash deposit'"
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="depositForm.notes" rows="2"
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showDepositModal = false"
                                class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button type="submit" :disabled="depositForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                                {{ isRtl ? 'إيداع' : 'Deposit' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Withdraw Modal -->
        <Teleport to="body">
            <div v-if="showWithdrawModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40" @click="showWithdrawModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-4 md:p-6 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'سحب مبلغ' : 'Withdraw Funds' }}</h3>
                    <div class="text-sm text-gray-500">
                        {{ isRtl ? 'الرصيد المتاح:' : 'Available balance:' }}
                        <span class="font-bold text-gray-900">{{ formatCurrency(wallet.balance) }}</span>
                    </div>
                    <form @submit.prevent="submitWithdraw" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المبلغ' : 'Amount' }} *</label>
                            <input v-model="withdrawForm.amount" type="number" step="0.01" min="0.01" :max="wallet.balance" required
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'النوع' : 'Type' }}</label>
                            <select v-model="withdrawForm.type"
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]">
                                <option value="withdrawal">{{ isRtl ? 'سحب نقدي' : 'Cash Withdrawal' }}</option>
                                <option value="payment">{{ isRtl ? 'دفع فاتورة' : 'Invoice Payment' }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الوصف' : 'Description' }} *</label>
                            <input v-model="withdrawForm.description" type="text" required maxlength="255"
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="withdrawForm.notes" rows="2"
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showWithdrawModal = false"
                                class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button type="submit" :disabled="withdrawForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50">
                                {{ isRtl ? 'سحب' : 'Withdraw' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Adjust Modal -->
        <Teleport to="body">
            <div v-if="showAdjustModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40" @click="showAdjustModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-4 md:p-6 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'تعديل الرصيد' : 'Adjust Balance' }}</h3>
                    <p class="text-xs text-gray-500">{{ isRtl ? 'أدخل قيمة موجبة للزيادة أو سالبة للنقصان' : 'Enter positive to add, negative to subtract' }}</p>
                    <form @submit.prevent="submitAdjust" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المبلغ' : 'Amount' }} *</label>
                            <input v-model="adjustForm.amount" type="number" step="0.01" required
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'السبب' : 'Reason' }} *</label>
                            <input v-model="adjustForm.description" type="text" required maxlength="255"
                                :placeholder="isRtl ? 'سبب التعديل' : 'Reason for adjustment'"
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="adjustForm.notes" rows="2"
                                class="doctorato-input w-full rounded-lg border-gray-300 focus:ring-[#1B365D] focus:border-[#1B365D]"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showAdjustModal = false"
                                class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button type="submit" :disabled="adjustForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-[#1B365D] rounded-lg hover:bg-[#1B365D] disabled:opacity-50">
                                {{ isRtl ? 'تعديل' : 'Adjust' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
