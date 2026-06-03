<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { confirm } = useConfirm();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    slip: Object,
});

const monthsAr = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
const monthsEn = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const months = computed(() => isRtl.value ? monthsAr : monthsEn);

const { formatCurrency, currencyCode } = useCurrency();

function formatDateTime(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString(isRtl.value ? 'ar-EG' : 'en-GB', { dateStyle: 'medium', timeStyle: 'short' });
}

const paymentMethodLabel = (method) => {
    if (!method) return '-';
    const map = {
        bank_transfer: { ar: 'تحويل بنكي', en: 'Bank Transfer' },
        cash:          { ar: 'نقدي',       en: 'Cash' },
        cheque:        { ar: 'شيك',        en: 'Cheque' },
        other:         { ar: 'أخرى',       en: 'Other' },
    };
    return (map[method]?.[isRtl.value ? 'ar' : 'en']) || method.replace(/_/g, ' ');
};

const departmentName = (dept) => {
    if (!dept) return '-';
    return isRtl.value ? (dept.name_ar || dept.name_en) : (dept.name_en || dept.name_ar);
};

const statusConfig = {
    draft: { key: 'a_draft', bg: 'bg-gray-100', text: 'text-gray-600', ring: 'ring-gray-200' },
    approved: { key: 'a_approved', bg: 'bg-slate-100', text: 'text-[#1B365D]', ring: 'ring-slate-200' },
    paid: { key: 'a_paid', bg: 'bg-emerald-100', text: 'text-emerald-700', ring: 'ring-emerald-200' },
};

const earningKeys = [
    { key: 'basic_salary', tKey: 'a_basic_salary' },
    { key: 'housing_allowance', tKey: 'a_housing_allowance' },
    { key: 'transport_allowance', tKey: 'a_transport_allowance' },
    { key: 'other_allowances', tKey: 'a_other_allowances' },
    { key: 'overtime_amount', tKey: 'a_overtime' },
    { key: 'bonus', tKey: 'a_bonus' },
    { key: 'commission_amount', tKey: 'a_commission' },
];

const deductionKeys = [
    { key: 'insurance_deduction', tKey: 'a_insurance' },
    { key: 'tax_deduction', tKey: 'a_tax' },
    { key: 'absence_deduction', tKey: 'a_absence' },
    { key: 'advance_deduction', tKey: 'a_advance_deduction' },
    { key: 'penalty_deduction', tKey: 'a_penalty_deduction' },
    { key: 'other_deductions', tKey: 'a_other_deductions' },
];

/* -- Approve -- */
const approving = ref(false);

function approveSlip() {
    const msg = isRtl.value
        ? 'هل أنت متأكد من اعتماد كشف الراتب هذا؟'
        : 'Are you sure you want to approve this salary slip?';
    confirm({ message: msg, confirmColor: 'green' }, () => {
        approving.value = true;
        router.post(`/admin/payroll/${props.slip.id}/approve`, {}, {
            preserveScroll: true,
            onFinish: () => { approving.value = false; },
        });
    });
}

/* -- Mark as Paid modal -- */
const showPaidModal = ref(false);
const paidForm = ref({
    payment_method: '',
    payment_reference: '',
});
const markingPaid = ref(false);

function openPaidModal() {
    paidForm.value = { payment_method: '', payment_reference: '' };
    showPaidModal.value = true;
}

function closePaidModal() {
    showPaidModal.value = false;
}

function submitMarkPaid() {
    markingPaid.value = true;
    router.post(`/admin/payroll/${props.slip.id}/mark-paid`, {
        payment_method: paidForm.value.payment_method,
        payment_reference: paidForm.value.payment_reference,
    }, {
        preserveScroll: true,
        onFinish: () => {
            markingPaid.value = false;
            showPaidModal.value = false;
        },
    });
}
</script>

<template>
    <AdminLayout :title="`${$t('a_salary_slip')}: ${slip.slip_number}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <Link href="/admin/payroll" class="text-sm text-gray-400 hover:text-[#C4A265] transition">
                            {{ isRtl ? '&rarr;' : '&larr;' }} {{ $t('a_back_to_payroll') }}
                        </Link>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ slip.slip_number }}</h1>
                        <span
                            class="text-xs font-semibold px-3 py-1 rounded-full ring-1"
                            :class="[
                                statusConfig[slip.status]?.bg,
                                statusConfig[slip.status]?.text,
                                statusConfig[slip.status]?.ring,
                            ]"
                        >
                            {{ $t(statusConfig[slip.status]?.key) || slip.status }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Print button -->
                    <Link
                        :href="`/admin/payroll/${slip.id}/print`"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl border text-sm font-medium transition hover:opacity-80"
                        style="border-color: #C4A265; color: #C4A265;"
                    >
                        <svg :class="['w-4 h-4', isRtl ? 'ml-2' : 'mr-2']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        {{ $t('a_print') }}
                    </Link>

                    <!-- Approve button (draft only) -->
                    <button
                        v-if="slip.status === 'draft' && can('salary_slips.update')"
                        @click="approveSlip"
                        :disabled="approving"
                        class="px-5 py-2.5 bg-[#1B365D] text-white text-sm font-semibold rounded-xl hover:bg-[#1B365D] transition disabled:opacity-50"
                    >
                        {{ approving ? $t('a_approving') : $t('a_approve') }}
                    </button>

                    <!-- Mark as Paid button (approved only) -->
                    <button
                        v-if="slip.status === 'approved' && can('salary_slips.update')"
                        @click="openPaidModal"
                        class="px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition"
                    >
                        {{ $t('a_mark_as_paid') }}
                    </button>
                </div>
            </div>

            <!-- Employee Info Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h3 class="text-sm font-bold text-gray-800 mb-4">{{ $t('a_employee_information') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_name') }}</p>
                        <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ slip.employee?.user?.name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_employee_number') }}</p>
                        <p class="text-sm font-mono mt-0.5" style="color: #C4A265;">{{ slip.employee?.employee_number }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_department') }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ departmentName(slip.employee?.department) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_email') }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ slip.employee?.user?.email }}</p>
                    </div>
                </div>
            </div>

            <!-- Two-column: Earnings & Deductions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Earnings Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">{{ $t('a_earnings') }}</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                <th class="px-4 md:px-6 py-2.5 ltr:text-left rtl:text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $t('a_description') }}</th>
                                <th class="px-4 md:px-6 py-2.5 ltr:text-right rtl:text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $t('a_amount') }} ({{ currencyCode }})</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in earningKeys" :key="item.key" class="hover:bg-gray-50/50">
                                <td class="px-4 md:px-6 py-3 text-gray-700">{{ $t(item.tKey) }}</td>
                                <td class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left font-medium text-gray-800">{{ formatCurrency(slip[item.key]) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2" style="border-color: #C4A265;">
                                <td class="px-4 md:px-6 py-3 font-bold text-gray-800">{{ $t('a_total_earnings') }}</td>
                                <td class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left font-bold" style="color: #C4A265;">{{ formatCurrency(slip.total_earnings) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Deductions Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">{{ $t('a_deductions') }}</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                <th class="px-4 md:px-6 py-2.5 ltr:text-left rtl:text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $t('a_description') }}</th>
                                <th class="px-4 md:px-6 py-2.5 ltr:text-right rtl:text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $t('a_amount') }} ({{ currencyCode }})</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in deductionKeys" :key="item.key" class="hover:bg-gray-50/50">
                                <td class="px-4 md:px-6 py-3 text-gray-700">{{ $t(item.tKey) }}</td>
                                <td class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left font-medium text-red-600">{{ formatCurrency(slip[item.key]) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-red-200">
                                <td class="px-4 md:px-6 py-3 font-bold text-gray-800">{{ $t('a_total_deductions') }}</td>
                                <td class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left font-bold text-red-600">{{ formatCurrency(slip.total_deductions) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Net Salary Card -->
            <div class="rounded-2xl shadow-sm border-2 p-6" style="border-color: #C4A265; background: linear-gradient(135deg, #fdfbf7 0%, #f9f3e8 100%);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color: #C4A265;">{{ $t('a_net_salary') }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ months[slip.month] || slip.month }} {{ slip.year }}</p>
                    </div>
                    <div :class="isRtl ? 'text-left' : 'text-right'">
                        <p class="text-2xl md:text-3xl font-bold" style="color: #C4A265;">{{ formatCurrency(slip.net_salary) }}</p>
                    </div>
                </div>
            </div>

            <!-- Workflow Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h3 class="text-sm font-bold text-gray-800 mb-4">{{ $t('a_workflow_details') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_approved_by') }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ slip.approved_by_user?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_approved_at') }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ formatDateTime(slip.approved_at) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_paid_by') }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ slip.paid_by_user?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_paid_at') }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ formatDateTime(slip.paid_at) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_payment_method') }}</p>
                        <p class="text-sm text-gray-700 mt-0.5">{{ paymentMethodLabel(slip.payment_method) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold">{{ $t('a_payment_reference') }}</p>
                        <p class="text-sm font-mono text-gray-700 mt-0.5">{{ slip.payment_reference || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="slip.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h3 class="text-sm font-bold text-gray-800 mb-3">{{ $t('a_notes') }}</h3>
                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ slip.notes }}</p>
            </div>
        </div>

        <!-- Mark as Paid Modal -->
        <Teleport to="body">
            <div
                v-if="showPaidModal"
                class="fixed inset-0 z-50 flex items-center justify-center"
            >
                <div class="absolute inset-0 bg-black/40" @click="closePaidModal"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-4 md:p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $t('a_mark_as_paid') }}</h3>
                    <p class="text-sm text-gray-500 mb-5">
                        {{ $t('a_enter_payment_details') }} <span class="font-mono font-semibold" style="color: #C4A265;">{{ slip.slip_number }}</span>
                    </p>

                    <form @submit.prevent="submitMarkPaid" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_payment_method') }}</label>
                            <select
                                v-model="paidForm.payment_method"
                                required
                                class="doctorato-input w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-[#C4A265] focus:ring-[#C4A265] focus:outline-none"
                            >
                                <option value="" disabled>{{ $t('a_select_method') }}</option>
                                <option value="bank_transfer">{{ $t('a_bank_transfer') }}</option>
                                <option value="cash">{{ $t('a_cash') }}</option>
                                <option value="cheque">{{ $t('a_cheque') }}</option>
                                <option value="other">{{ $t('a_other') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_payment_reference') }}</label>
                            <input
                                v-model="paidForm.payment_reference"
                                type="text"
                                :placeholder="$t('a_payment_reference_placeholder')"
                                class="doctorato-input w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-[#C4A265] focus:ring-[#C4A265] focus:outline-none"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closePaidModal"
                                class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 transition"
                            >
                                {{ $t('a_cancel') }}
                            </button>
                            <button
                                type="submit"
                                :disabled="markingPaid || !paidForm.payment_method"
                                class="px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition disabled:opacity-50"
                            >
                                {{ markingPaid ? $t('a_processing') : $t('a_confirm_payment') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
