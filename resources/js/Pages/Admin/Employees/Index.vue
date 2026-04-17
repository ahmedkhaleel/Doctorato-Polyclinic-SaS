<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    employees: Object,
    departments: Array,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || '');
const departmentFilter = ref(props.filters?.department_id || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/employees', { ...props.filters, search: val, page: 1 }, { preserveState: true, preserveScroll: true });
    }, 400);
});

function applyFilter(key, value) {
    router.get('/admin/employees', { ...props.filters, [key]: value, page: 1 }, { preserveState: true, preserveScroll: true });
}

const statusKeys = {
    active: 'a_active',
    on_leave: 'a_on_leave',
    suspended: 'a_suspended',
    terminated: 'a_terminated',
};

const statusColors = {
    active: 'bg-emerald-100 text-emerald-700',
    on_leave: 'bg-amber-100 text-amber-700',
    suspended: 'bg-red-100 text-red-700',
    terminated: 'bg-gray-100 text-gray-600',
};

const contractKeys = {
    full_time: 'a_full_time',
    part_time: 'a_part_time',
    contract: 'a_contract_type',
};
</script>

<template>
    <AdminLayout :title="$t('a_employees')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_employees') }}</h1>
                <Link
                    v-if="can('employees.create')"
                    href="/admin/employees/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg :class="['w-4 h-4', isRtl ? 'ml-2' : 'mr-2']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_employee') }}
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4" style="border-color: #C4A265;">
                    <p class="text-sm font-medium text-gray-500">{{ $t('a_total_employees') }}</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-800 mt-1">{{ stats?.total || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-emerald-500">
                    <p class="text-sm font-medium text-gray-500">{{ $t('a_active') }}</p>
                    <p class="text-xl md:text-2xl font-bold text-emerald-700 mt-1">{{ stats?.active || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-amber-500">
                    <p class="text-sm font-medium text-gray-500">{{ $t('a_on_leave') }}</p>
                    <p class="text-xl md:text-2xl font-bold text-amber-700 mt-1">{{ stats?.on_leave || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-gray-400">
                    <p class="text-sm font-medium text-gray-500">{{ $t('a_terminated') }}</p>
                    <p class="text-xl md:text-2xl font-bold text-gray-600 mt-1">{{ stats?.terminated || 0 }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_employees')"
                    class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                />
                <select
                    v-model="departmentFilter"
                    @change="applyFilter('department_id', departmentFilter)"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_departments') }}</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name_en }}</option>
                </select>
                <select
                    v-model="statusFilter"
                    @change="applyFilter('status', statusFilter)"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_status') }}</option>
                    <option value="active">{{ $t('a_active') }}</option>
                    <option value="on_leave">{{ $t('a_on_leave') }}</option>
                    <option value="suspended">{{ $t('a_suspended') }}</option>
                    <option value="terminated">{{ $t('a_terminated') }}</option>
                </select>
            </div>

            <!-- Employees Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_employee_number') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_email') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_department') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_job_title') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_contract') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="employee in employees.data" :key="employee.id" class="hover:bg-gray-50">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ employee.employee_number }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div :class="['w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold', isRtl ? 'ml-3' : 'mr-3']" style="background-color: #C4A265;">
                                            {{ employee.user?.name?.charAt(0) }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ employee.user?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ employee.user?.email }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ employee.department?.name_en || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ employee.job_title_en || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="statusColors[employee.status] || 'bg-gray-100 text-gray-600'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ $t(statusKeys[employee.status]) || employee.status }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $t(contractKeys[employee.contract_type]) || employee.contract_type || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm space-x-3 rtl:space-x-reverse">
                                    <Link v-if="can('employees.view')" :href="`/admin/employees/${employee.id}`" class="font-medium text-[#1B365D] hover:underline">{{ $t('a_view') }}</Link>
                                    <Link v-if="can('employees.update')" :href="`/admin/employees/${employee.id}/edit`" class="font-medium text-amber-600 hover:underline">{{ $t('a_edit') }}</Link>
                                </td>
                            </tr>
                            <tr v-if="!employees.data || employees.data.length === 0">
                                <td colspan="8" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_employees_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="employees.links && employees.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ employees.from }} {{ $t('a_to') }} {{ employees.to }} {{ $t('a_of') }} {{ employees.total }} {{ $t('a_results') }}</p>
                    <nav :class="['flex', isRtl ? 'space-x-reverse space-x-1' : 'space-x-1']">
                        <template v-for="link in employees.links" :key="link.label">
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
    </AdminLayout>
</template>
