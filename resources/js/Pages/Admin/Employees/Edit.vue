<script setup>
import { computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    employee: Object,
    departments: Array,
});

const form = useForm({
    department_id: props.employee.department_id || '',
    job_title_en: props.employee.job_title_en || '',
    job_title_ar: props.employee.job_title_ar || '',
    hire_date: props.employee.hire_date ? props.employee.hire_date.substring(0, 10) : '',
    contract_type: props.employee.contract_type || 'full_time',
    contract_end_date: props.employee.contract_end_date ? props.employee.contract_end_date.substring(0, 10) : '',
    basic_salary: props.employee.basic_salary || 0,
    housing_allowance: props.employee.housing_allowance || 0,
    transport_allowance: props.employee.transport_allowance || 0,
    other_allowances: props.employee.other_allowances || 0,
    national_id: props.employee.national_id || '',
    phone: props.employee.phone || '',
    emergency_contact_name: props.employee.emergency_contact_name || '',
    emergency_contact_phone: props.employee.emergency_contact_phone || '',
    address: props.employee.address || '',
    bank_name: props.employee.bank_name || '',
    bank_account_number: props.employee.bank_account_number || '',
    insurance_number: props.employee.insurance_number || '',
    status: props.employee.status || 'active',
    termination_date: props.employee.termination_date ? props.employee.termination_date.substring(0, 10) : '',
    termination_reason: props.employee.termination_reason || '',
});

const isTerminated = computed(() => form.status === 'terminated');

function submit() {
    form.post(`/admin/employees/${props.employee.id}`);
}
</script>

<template>
    <AdminLayout :title="`${$t('a_edit_employee')}: ${employee.user?.name}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_edit_employee') }}: {{ employee.user?.name }}</h1>
                    <p class="text-sm font-mono mt-0.5" style="color: #C4A265;">{{ employee.employee_number }}</p>
                </div>
                <Link :href="`/admin/employees/${employee.id}`" class="text-sm text-gray-500 hover:underline">
                    {{ isRtl ? '&rarr;' : '&larr;' }} {{ $t('a_back_to_profile') }}
                </Link>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Fields (col-span-2) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Employment Information -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_employment_information') }}</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_department') }}</label>
                                <select
                                    v-model="form.department_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                >
                                    <option value="">-- {{ $t('a_select_department') }} --</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name_en }}</option>
                                </select>
                                <p v-if="form.errors.department_id" class="mt-1 text-sm text-red-600">{{ form.errors.department_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }} <span class="text-red-500">*</span></label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                >
                                    <option value="active">{{ $t('a_active') }}</option>
                                    <option value="on_leave">{{ $t('a_on_leave') }}</option>
                                    <option value="suspended">{{ $t('a_suspended') }}</option>
                                    <option value="terminated">{{ $t('a_terminated') }}</option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_job_title_en') }}</label>
                                <input
                                    v-model="form.job_title_en"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                    :placeholder="$t('a_job_title_en_placeholder')"
                                />
                                <p v-if="form.errors.job_title_en" class="mt-1 text-sm text-red-600">{{ form.errors.job_title_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_job_title_ar') }}</label>
                                <input
                                    v-model="form.job_title_ar"
                                    type="text"
                                    dir="rtl"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                    :placeholder="$t('a_job_title_ar_placeholder')"
                                />
                                <p v-if="form.errors.job_title_ar" class="mt-1 text-sm text-red-600">{{ form.errors.job_title_ar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_hire_date') }}</label>
                                <input
                                    v-model="form.hire_date"
                                    type="date"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.hire_date" class="mt-1 text-sm text-red-600">{{ form.errors.hire_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_contract_type_label') }}</label>
                                <select
                                    v-model="form.contract_type"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                >
                                    <option value="full_time">{{ $t('a_full_time') }}</option>
                                    <option value="part_time">{{ $t('a_part_time') }}</option>
                                    <option value="contract">{{ $t('a_contract_type') }}</option>
                                </select>
                                <p v-if="form.errors.contract_type" class="mt-1 text-sm text-red-600">{{ form.errors.contract_type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_contract_end_date') }}</label>
                                <input
                                    v-model="form.contract_end_date"
                                    type="date"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.contract_end_date" class="mt-1 text-sm text-red-600">{{ form.errors.contract_end_date }}</p>
                            </div>
                        </div>

                        <!-- Termination fields (only when status === 'terminated') -->
                        <div v-if="isTerminated" class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-red-50 rounded-lg border border-red-100">
                            <div>
                                <label class="block text-sm font-medium text-red-700 mb-1">{{ $t('a_termination_date') }}</label>
                                <input
                                    v-model="form.termination_date"
                                    type="date"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.termination_date" class="mt-1 text-sm text-red-600">{{ form.errors.termination_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-red-700 mb-1">{{ $t('a_termination_reason') }}</label>
                                <textarea
                                    v-model="form.termination_reason"
                                    rows="2"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                    :placeholder="$t('a_termination_reason_placeholder')"
                                ></textarea>
                                <p v-if="form.errors.termination_reason" class="mt-1 text-sm text-red-600">{{ form.errors.termination_reason }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Information -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_salary_information') }}</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_basic_salary') }}</label>
                                <input
                                    v-model="form.basic_salary"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.basic_salary" class="mt-1 text-sm text-red-600">{{ form.errors.basic_salary }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_housing_allowance') }}</label>
                                <input
                                    v-model="form.housing_allowance"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.housing_allowance" class="mt-1 text-sm text-red-600">{{ form.errors.housing_allowance }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_transport_allowance') }}</label>
                                <input
                                    v-model="form.transport_allowance"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.transport_allowance" class="mt-1 text-sm text-red-600">{{ form.errors.transport_allowance }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_other_allowances') }}</label>
                                <input
                                    v-model="form.other_allowances"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.other_allowances" class="mt-1 text-sm text-red-600">{{ form.errors.other_allowances }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_personal_details') }}</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_national_id') }}</label>
                                <input
                                    v-model="form.national_id"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.national_id" class="mt-1 text-sm text-red-600">{{ form.errors.national_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_phone') }}</label>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_emergency_contact_name') }}</label>
                                <input
                                    v-model="form.emergency_contact_name"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.emergency_contact_name" class="mt-1 text-sm text-red-600">{{ form.errors.emergency_contact_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_emergency_contact_phone') }}</label>
                                <input
                                    v-model="form.emergency_contact_phone"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <p v-if="form.errors.emergency_contact_phone" class="mt-1 text-sm text-red-600">{{ form.errors.emergency_contact_phone }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_address') }}</label>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                :placeholder="$t('a_full_address')"
                            ></textarea>
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (col-span-1) -->
                <div class="space-y-6">
                    <!-- Bank Information -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_bank_information') }}</h2>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_bank_name') }}</label>
                            <input
                                v-model="form.bank_name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                            />
                            <p v-if="form.errors.bank_name" class="mt-1 text-sm text-red-600">{{ form.errors.bank_name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_account_number') }}</label>
                            <input
                                v-model="form.bank_account_number"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                            />
                            <p v-if="form.errors.bank_account_number" class="mt-1 text-sm text-red-600">{{ form.errors.bank_account_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_insurance_number') }}</label>
                            <input
                                v-model="form.insurance_number"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                            />
                            <p v-if="form.errors.insurance_number" class="mt-1 text-sm text-red-600">{{ form.errors.insurance_number }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full px-4 md:px-6 py-3 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_updating') : $t('a_update_employee') }}
                        </button>
                        <Link
                            :href="`/admin/employees/${employee.id}`"
                            class="block w-full text-center px-4 md:px-6 py-3 rounded-lg border border-gray-300 text-gray-600 font-medium text-sm hover:bg-gray-50 transition"
                        >
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
