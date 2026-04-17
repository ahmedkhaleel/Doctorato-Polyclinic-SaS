<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    advances: Object,
    employees: Array,
    filters: Object,
    stats: Object,
});

const statusFilter = ref(props.filters?.status || '');
const showModal = ref(false);

const form = ref({
    employee_id: '',
    amount: '',
    monthly_installment: '',
    reason: '',
    start_month: '',
    start_year: '',
});

const formErrors = ref({});
const formProcessing = ref(false);

function applyFilter(status) {
    statusFilter.value = status;
    router.get('/admin/advances', {
        status: status || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

const { formatCurrency } = useCurrency();

function openModal() {
    form.value = {
        employee_id: '',
        amount: '',
        monthly_installment: '',
        reason: '',
        start_month: '',
        start_year: '',
    };
    formErrors.value = {};
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    formErrors.value = {};
}

function submitAdvance() {
    formProcessing.value = true;
    router.post('/admin/advances', form.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            formProcessing.value = false;
        },
        onError: (errors) => {
            formErrors.value = errors;
            formProcessing.value = false;
        },
    });
}

function approveAdvance(id) {
    if (window.confirm(t('a_confirm_approve_advance'))) {
        router.post(`/admin/advances/${id}/approve`, {}, { preserveScroll: true });
    }
}

function rejectAdvance(id) {
    if (window.confirm(t('a_confirm_reject_advance'))) {
        router.post(`/admin/advances/${id}/reject`, {}, { preserveScroll: true });
    }
}

const statusKeys = {
    pending: 'a_pending',
    approved: 'a_approved',
    active: 'a_active',
    completed: 'a_completed',
    rejected: 'a_rejected',
};

const statusColors = {
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-slate-100 text-[#1B365D]',
    active: 'bg-emerald-100 text-emerald-800',
    completed: 'bg-slate-100 text-[#1B365D]',
    rejected: 'bg-red-100 text-red-800',
};

const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];
</script>

<template>
    <AdminLayout :title="$t('a_advances')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_advances') }}</h1>
                <button
                    v-if="can('advances.create')"
                    @click="openModal"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg :class="['w-4 h-4', isRtl ? 'ml-2' : 'mr-2']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_new_advance') }}
                </button>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-amber-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ $t('a_pending_requests') }}</p>
                            <p class="text-xl md:text-2xl font-bold text-amber-600">{{ stats?.pending || 0 }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-emerald-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ $t('a_active_advances') }}</p>
                            <p class="text-xl md:text-2xl font-bold text-emerald-600">{{ stats?.active || 0 }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5" style="border-left: 4px solid #C4A265;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ $t('a_total_outstanding') }}</p>
                            <p class="text-xl md:text-2xl font-bold" style="color: #C4A265;">{{ formatCurrency(stats?.total_outstanding) }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: rgba(196, 162, 101, 0.15);">
                            <svg class="w-5 h-5" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-2">
                <button
                    @click="applyFilter('')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition"
                    :class="statusFilter === '' ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200'"
                    :style="statusFilter === '' ? 'background-color: #C4A265;' : ''"
                >
                    {{ $t('a_all') }}
                </button>
                <button
                    v-for="s in ['pending', 'approved', 'active', 'completed', 'rejected']"
                    :key="s"
                    @click="applyFilter(s)"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition"
                    :class="statusFilter === s ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200'"
                    :style="statusFilter === s ? 'background-color: #C4A265;' : ''"
                >
                    {{ $t(statusKeys[s]) }}
                </button>
            </div>

            <!-- Advances Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_employee') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_amount') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_monthly_installment') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_remaining_balance') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="advance in advances.data" :key="advance.id" class="hover:bg-gray-50">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div :class="['w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold', isRtl ? 'ml-3' : 'mr-3']" style="background-color: #C4A265;">
                                            {{ advance.employee?.user?.name?.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ advance.employee?.user?.name }}</div>
                                            <div class="text-xs text-gray-500">{{ advance.employee?.department?.name_en }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold" style="color: #C4A265;">{{ formatCurrency(advance.amount) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatCurrency(advance.monthly_installment) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatCurrency(advance.remaining_balance) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="statusColors[advance.status] || 'bg-gray-100 text-gray-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ $t(statusKeys[advance.status]) || advance.status }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm space-x-2 rtl:space-x-reverse">
                                    <template v-if="advance.status === 'pending' && can('advances.update')">
                                        <button
                                            @click="approveAdvance(advance.id)"
                                            class="px-3 py-1 rounded text-white text-xs font-medium bg-emerald-600 hover:bg-emerald-700 transition"
                                        >
                                            {{ $t('a_approve') }}
                                        </button>
                                        <button
                                            @click="rejectAdvance(advance.id)"
                                            class="px-3 py-1 rounded text-white text-xs font-medium bg-red-600 hover:bg-red-700 transition"
                                        >
                                            {{ $t('a_reject') }}
                                        </button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="!advances.data || advances.data.length === 0">
                                <td colspan="6" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_advances_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="advances.links && advances.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ advances.from }} {{ $t('a_to') }} {{ advances.to }} {{ $t('a_of') }} {{ advances.total }} {{ $t('a_results') }}</p>
                    <nav :class="['flex', isRtl ? 'space-x-reverse space-x-1' : 'space-x-1']">
                        <template v-for="link in advances.links" :key="link.label">
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
        </div>

        <!-- Create Advance Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg mx-auto z-10">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800">{{ $t('a_new_advance') }}</h3>
                                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <form @submit.prevent="submitAdvance" class="px-4 md:px-6 py-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_employee') }} <span class="text-red-500">*</span></label>
                                <select
                                    v-model="form.employee_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                >
                                    <option value="">{{ $t('a_select_employee') }}</option>
                                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                </select>
                                <p v-if="formErrors.employee_id" class="mt-1 text-sm text-red-600">{{ formErrors.employee_id }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_amount') }} <span class="text-red-500">*</span></label>
                                    <input
                                        v-model="form.amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                        placeholder="0.00"
                                    />
                                    <p v-if="formErrors.amount" class="mt-1 text-sm text-red-600">{{ formErrors.amount }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_monthly_installment') }} <span class="text-red-500">*</span></label>
                                    <input
                                        v-model="form.monthly_installment"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                        placeholder="0.00"
                                    />
                                    <p v-if="formErrors.monthly_installment" class="mt-1 text-sm text-red-600">{{ formErrors.monthly_installment }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_reason') }}</label>
                                <textarea
                                    v-model="form.reason"
                                    rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                    :placeholder="$t('a_reason_for_advance')"
                                ></textarea>
                                <p v-if="formErrors.reason" class="mt-1 text-sm text-red-600">{{ formErrors.reason }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_start_month') }} <span class="text-red-500">*</span></label>
                                    <select
                                        v-model="form.start_month"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                    >
                                        <option value="">{{ $t('a_select_month') }}</option>
                                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                                    </select>
                                    <p v-if="formErrors.start_month" class="mt-1 text-sm text-red-600">{{ formErrors.start_month }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_start_year') }} <span class="text-red-500">*</span></label>
                                    <input
                                        v-model="form.start_year"
                                        type="number"
                                        min="2024"
                                        max="2030"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                        placeholder="2026"
                                    />
                                    <p v-if="formErrors.start_year" class="mt-1 text-sm text-red-600">{{ formErrors.start_year }}</p>
                                </div>
                            </div>
                            <div :class="['flex pt-2 border-t border-gray-200', isRtl ? 'justify-start space-x-reverse space-x-3' : 'justify-end space-x-3']">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50 transition"
                                >
                                    {{ $t('a_cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="formProcessing"
                                    class="px-4 md:px-6 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                    style="background-color: #C4A265;"
                                >
                                    {{ formProcessing ? $t('a_saving') : $t('a_create_advance') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
