<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    employee: Object,
    attendanceSummary: Object,
    recentLeaves: Array,
    recentAttendance: Array,
});

const statusColors = {
    active: 'bg-green-100 text-green-700',
    on_leave: 'bg-amber-100 text-amber-700',
    suspended: 'bg-red-100 text-red-700',
    terminated: 'bg-gray-100 text-gray-600',
};

const statusKeys = {
    active: 'a_active',
    on_leave: 'a_on_leave',
    suspended: 'a_suspended',
    terminated: 'a_terminated',
};

const leaveTypeColors = {
    annual: 'bg-blue-100 text-blue-700',
    sick: 'bg-red-100 text-red-700',
    personal: 'bg-purple-100 text-purple-700',
    unpaid: 'bg-gray-100 text-gray-600',
};

const leaveStatusColors = {
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
};

const contractTypeKeys = {
    full_time: 'a_full_time',
    part_time: 'a_part_time',
    contract: 'a_contract_type',
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

</script>

<template>
    <AdminLayout :title="`${$t('a_employee_profile')}: ${employee.user?.name}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/admin/employees" class="text-sm text-gray-500 hover:text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-800">{{ employee.user?.name }}</h1>
                            <span class="text-xs font-mono px-2.5 py-1 rounded-full bg-yellow-50" style="color: #C4A265;">
                                {{ employee.employee_number }}
                            </span>
                            <span
                                :class="statusColors[employee.status] || 'bg-gray-100 text-gray-600'"
                                class="px-2.5 py-1 text-xs font-semibold rounded-full"
                            >
                                {{ $t(statusKeys[employee.status]) || employee.status }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ employee.department?.name_en || '-' }} -- {{ employee.job_title_en || '-' }}
                        </p>
                    </div>
                </div>
                <Link
                    v-if="can('employees.update')"
                    :href="`/admin/employees/${employee.id}/edit`"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg :class="['w-4 h-4', isRtl ? 'ml-2' : 'mr-2']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ $t('a_edit_employee') }}
                </Link>
            </div>

            <!-- Two-column grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left column (col-span-2) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Employee Info Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('a_employee_information') }}</h3>
                        <div class="space-y-0">
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_name') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ employee.user?.name || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_email') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ employee.user?.email || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_role') }}</span>
                                <span class="text-sm font-medium text-gray-800 capitalize">{{ employee.user?.role?.display_name_en || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_department') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ employee.department?.name_en || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_job_title_en') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ employee.job_title_en || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_job_title_ar') }}</span>
                                <span class="text-sm font-medium text-gray-800" dir="rtl">{{ employee.job_title_ar || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-500">{{ $t('a_employee_number') }}</span>
                                <span class="text-sm font-medium font-mono" style="color: #C4A265;">{{ employee.employee_number }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Employment Details Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('a_employment_details') }}</h3>
                        <div class="space-y-0">
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_hire_date') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ formatDate(employee.hire_date) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_contract_type_label') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ $t(contractTypeKeys[employee.contract_type]) || employee.contract_type || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_contract_end_date') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ formatDate(employee.contract_end_date) }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-500">{{ $t('a_status') }}</span>
                                <span
                                    :class="statusColors[employee.status] || 'bg-gray-100 text-gray-600'"
                                    class="px-2.5 py-0.5 text-xs font-semibold rounded-full"
                                >
                                    {{ $t(statusKeys[employee.status]) || employee.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('a_salary_details') }}</h3>
                        <div class="space-y-0">
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_basic_salary') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ formatCurrency(employee.basic_salary) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_housing_allowance') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ formatCurrency(employee.housing_allowance) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_transport_allowance') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ formatCurrency(employee.transport_allowance) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_other_allowances') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ formatCurrency(employee.other_allowances) }}</span>
                            </div>
                            <div class="flex justify-between py-3 bg-gray-50 -mx-6 px-6 rounded-b-lg mt-2">
                                <span class="text-sm font-semibold text-gray-700">{{ $t('a_total_gross_salary') }}</span>
                                <span class="text-sm font-bold" style="color: #C4A265;">{{ formatCurrency(Number(employee.basic_salary || 0) + Number(employee.housing_allowance || 0) + Number(employee.transport_allowance || 0) + Number(employee.other_allowances || 0)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right column (col-span-1) -->
                <div class="space-y-6">
                    <!-- Attendance Summary Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('a_attendance_current_month') }}</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-green-50 rounded-lg">
                                <p class="text-2xl font-bold text-green-700">{{ attendanceSummary?.present ?? 0 }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $t('a_present') }}</p>
                            </div>
                            <div class="text-center p-3 bg-red-50 rounded-lg">
                                <p class="text-2xl font-bold text-red-600">{{ attendanceSummary?.absent ?? 0 }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $t('a_absent') }}</p>
                            </div>
                            <div class="text-center p-3 bg-amber-50 rounded-lg">
                                <p class="text-2xl font-bold text-amber-600">{{ attendanceSummary?.late ?? 0 }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $t('a_late') }}</p>
                            </div>
                            <div class="text-center p-3 bg-blue-50 rounded-lg">
                                <p class="text-2xl font-bold text-blue-600">{{ attendanceSummary?.overtime_hours ?? 0 }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $t('a_overtime_hrs') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Leaves Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('a_recent_leaves') }}</h3>
                        <div v-if="recentLeaves && recentLeaves.length" class="space-y-3">
                            <div
                                v-for="leave in recentLeaves.slice(0, 5)"
                                :key="leave.id"
                                class="flex flex-col gap-1.5 py-2.5 border-b border-gray-50 last:border-0"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        :class="leaveTypeColors[leave.leave_type] || 'bg-gray-100 text-gray-600'"
                                        class="px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                    >
                                        {{ leave.leave_type }}
                                    </span>
                                    <span
                                        :class="leaveStatusColors[leave.status] || 'bg-gray-100 text-gray-600'"
                                        class="px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                    >
                                        {{ leave.status }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 text-center py-4">{{ $t('a_no_recent_leaves') }}</p>
                    </div>

                    <!-- Personal Info Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('a_personal_information') }}</h3>
                        <div class="space-y-0">
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_national_id') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ employee.national_id || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_phone') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ employee.phone || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_emergency_contact') }}</span>
                                <span class="text-sm font-medium text-gray-800">
                                    <template v-if="employee.emergency_contact_name">
                                        {{ employee.emergency_contact_name }}
                                        <span v-if="employee.emergency_contact_phone" :class="isRtl ? 'mr-1' : 'ml-1'" class="text-gray-400">({{ employee.emergency_contact_phone }})</span>
                                    </template>
                                    <template v-else>-</template>
                                </span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-500">{{ $t('a_address') }}</span>
                                <span class="text-sm font-medium text-gray-800 ltr:text-right rtl:text-left max-w-[60%]">{{ employee.address || '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Info Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ $t('a_bank_information') }}</h3>
                        <div class="space-y-0">
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_bank_name') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ employee.bank_name || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-50">
                                <span class="text-sm text-gray-500">{{ $t('a_account_number') }}</span>
                                <span class="text-sm font-medium text-gray-800 font-mono">{{ employee.bank_account_number || '-' }}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-500">{{ $t('a_insurance_number') }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ employee.insurance_number || '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
