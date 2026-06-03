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
    slips: Object,
    summary: Object,
    filters: Object,
});

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const monthFilter = ref(props.filters?.month || '');
const yearFilter = ref(props.filters?.year || '');
const statusFilter = ref(props.filters?.status || '');

const selectedIds = ref([]);
const selectAll = ref(false);

const { formatCurrency } = useCurrency();

function applyFilters() {
    router.get('/admin/payroll', {
        month: monthFilter.value || undefined,
        year: yearFilter.value || undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function toggleSelectAll() {
    if (selectAll.value) {
        selectedIds.value = (props.slips?.data || []).map(s => s.id);
    } else {
        selectedIds.value = [];
    }
}

function toggleSlip(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
    selectAll.value = selectedIds.value.length === (props.slips?.data || []).length && selectedIds.value.length > 0;
}

const hasSelection = computed(() => selectedIds.value.length > 0);

const bulkProcessing = ref(false);

function bulkApprove() {
    if (selectedIds.value.length === 0) return;
    confirm({ message: isRtl.value ? `اعتماد ${selectedIds.value.length} كشف محدد؟` : `Approve ${selectedIds.value.length} selected slip(s)?`, confirmColor: 'green' }, () => {
        bulkProcessing.value = true;
        router.post('/admin/payroll/bulk-approve', {
            ids: selectedIds.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                selectAll.value = false;
            },
            onFinish: () => {
                bulkProcessing.value = false;
            },
        });
    });
}

function bulkMarkPaid() {
    if (selectedIds.value.length === 0) return;
    confirm({ message: isRtl.value ? `تعليم ${selectedIds.value.length} كشف كمدفوع؟` : `Mark ${selectedIds.value.length} selected slip(s) as paid?`, confirmColor: 'green' }, () => {
        bulkProcessing.value = true;
        router.post('/admin/payroll/bulk-mark-paid', {
            ids: selectedIds.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                selectAll.value = false;
            },
            onFinish: () => {
                bulkProcessing.value = false;
            },
        });
    });
}

const statusColors = {
    draft: 'bg-gray-100 text-gray-600',
    approved: 'bg-slate-100 text-[#1B365D]',
    paid: 'bg-emerald-100 text-emerald-700',
};

const statusKeys = {
    draft: 'a_draft',
    approved: 'a_approved',
    paid: 'a_paid',
};
</script>

<template>
    <AdminLayout :title="$t('a_payroll')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_payroll') }}</h1>
                <Link
                    v-if="can('salary_slips.create')"
                    href="/admin/payroll/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg :class="['w-4 h-4', isRtl ? 'ml-2' : 'mr-2']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_generate_payroll') }}
                </Link>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4" style="border-left-color: #C4A265;">
                    <p class="text-sm font-medium text-gray-500">{{ $t('a_total_slips') }}</p>
                    <p class="mt-1 text-xl md:text-2xl font-bold text-gray-800">{{ summary?.total_slips ?? 0 }}</p>
                    <div :class="['mt-2 flex items-center text-xs text-gray-400', isRtl ? 'space-x-reverse space-x-3' : 'space-x-3']">
                        <span>{{ summary?.draft ?? 0 }} {{ $t('a_draft') }}</span>
                        <span>{{ summary?.approved ?? 0 }} {{ $t('a_approved') }}</span>
                        <span>{{ summary?.paid ?? 0 }} {{ $t('a_paid') }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4" style="border-left-color: #C4A265;">
                    <p class="text-sm font-medium text-gray-500">{{ $t('a_total_earnings') }}</p>
                    <p class="mt-1 text-xl md:text-2xl font-bold text-gray-800">{{ formatCurrency(summary?.total_earnings) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4" style="border-left-color: #C4A265;">
                    <p class="text-sm font-medium text-gray-500">{{ $t('a_total_deductions') }}</p>
                    <p class="mt-1 text-xl md:text-2xl font-bold text-gray-800">{{ formatCurrency(summary?.total_deductions) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4" style="border-left-color: #C4A265;">
                    <p class="text-sm font-medium text-gray-500">{{ $t('a_net_payroll') }}</p>
                    <p class="mt-1 text-xl md:text-2xl font-bold text-gray-800">{{ formatCurrency(summary?.total_net) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_month') }}</label>
                    <select
                        v-model="monthFilter"
                        class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                    >
                        <option value="">{{ $t('a_all_months') }}</option>
                        <option v-for="(name, index) in months" :key="index" :value="index + 1">{{ name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_year') }}</label>
                    <input
                        v-model="yearFilter"
                        type="number"
                        min="2020"
                        max="2099"
                        :placeholder="$t('a_year')"
                        class="doctorato-input w-28 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_status') }}</label>
                    <select
                        v-model="statusFilter"
                        class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                    >
                        <option value="">{{ $t('a_all_status') }}</option>
                        <option value="draft">{{ $t('a_draft') }}</option>
                        <option value="approved">{{ $t('a_approved') }}</option>
                        <option value="paid">{{ $t('a_paid') }}</option>
                    </select>
                </div>
                <button
                    @click="applyFilters"
                    class="px-5 py-2 rounded-lg text-white text-sm font-medium transition hover:opacity-90"
                    style="background-color: #C4A265;"
                >
                    {{ $t('a_apply') }}
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 ltr:text-left rtl:text-right">
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        @change="toggleSelectAll"
                                        class="rounded border-gray-300 text-amber-600 focus:ring-[#C4A265]/30"
                                    />
                                </th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_slip_number') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_employee') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_department') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_earnings') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_deductions') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_net_salary') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(slip, i) in slips.data" :key="slip.id" class="lst-row hover:bg-gray-50" :style="{ '--row-i': i }">
                                <td class="px-4 py-4">
                                    <input
                                        type="checkbox"
                                        :value="slip.id"
                                        :checked="selectedIds.includes(slip.id)"
                                        @change="toggleSlip(slip.id)"
                                        class="rounded border-gray-300 text-amber-600 focus:ring-[#C4A265]/30"
                                    />
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ slip.slip_number }}
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            :class="['w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold', isRtl ? 'ml-3' : 'mr-3']"
                                            style="background-color: #C4A265;"
                                        >
                                            {{ slip.employee?.user?.name?.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ slip.employee?.user?.name }}</p>
                                            <p class="text-xs text-gray-400">{{ slip.employee?.user?.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ slip.employee?.department?.name_en || '-' }}
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700 ltr:text-right rtl:text-left">
                                    {{ formatCurrency(slip.total_earnings) }}
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700 ltr:text-right rtl:text-left">
                                    {{ formatCurrency(slip.total_deductions) }}
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 ltr:text-right rtl:text-left">
                                    {{ formatCurrency(slip.net_salary) }}
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="statusColors[slip.status] || 'bg-gray-100 text-gray-600'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ $t(statusKeys[slip.status]) || slip.status }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm">
                                    <Link
                                        v-if="can('salary_slips.view')"
                                        :href="`/admin/payroll/${slip.id}`"
                                        class="font-medium hover:underline"
                                        style="color: #C4A265;"
                                    >
                                        {{ $t('a_view') }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!slips.data || slips.data.length === 0">
                                <td colspan="9" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">
                                    {{ $t('a_no_payroll_slips_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="slips.links && slips.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ slips.from }} {{ $t('a_to') }} {{ slips.to }} {{ $t('a_of') }} {{ slips.total }} {{ $t('a_results') }}</p>
                    <nav :class="['flex', isRtl ? 'space-x-reverse space-x-1' : 'space-x-1']">
                        <template v-for="link in slips.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 text-sm rounded border transition"
                                :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>

            <!-- Bulk Action Bar -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div
                    v-if="hasSelection"
                    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white rounded-xl shadow-2xl px-4 md:px-6 py-3 flex items-center space-x-4 rtl:space-x-reverse"
                >
                    <span class="text-sm font-medium">{{ selectedIds.length }} {{ $t('a_slips_selected') }}</span>
                    <div class="h-5 border-l border-gray-600"></div>
                    <button
                        @click="bulkApprove"
                        :disabled="bulkProcessing"
                        class="px-4 py-1.5 text-sm font-medium rounded-lg bg-[#1B365D] hover:bg-[#1B365D] transition disabled:opacity-50"
                    >
                        {{ $t('a_approve_selected') }}
                    </button>
                    <button
                        @click="bulkMarkPaid"
                        :disabled="bulkProcessing"
                        class="px-4 py-1.5 text-sm font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 transition disabled:opacity-50"
                    >
                        {{ $t('a_mark_paid_selected') }}
                    </button>
                    <button
                        @click="selectedIds = []; selectAll = false;"
                        class="px-3 py-1.5 text-sm font-medium rounded-lg bg-gray-700 hover:bg-gray-600 transition"
                    >
                        {{ $t('a_clear') }}
                    </button>
                </div>
            </Transition>
        </div>
    </AdminLayout>
</template>

<style scoped>
.lst-row {
    animation: lstRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes lstRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .lst-row { animation: none !important; }
}
</style>
