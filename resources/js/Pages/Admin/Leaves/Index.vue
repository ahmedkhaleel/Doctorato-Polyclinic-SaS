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
    leaves: Object,
    users: Array,
    filters: Object,
});

const employeeFilter = ref(props.filters?.user_id || '');
const statusFilter = ref(props.filters?.status || '');
const typeFilter = ref(props.filters?.leave_type || '');

watch([employeeFilter, statusFilter, typeFilter], () => {
    router.get('/admin/leaves', {
        user_id: employeeFilter.value || undefined,
        status: statusFilter.value || undefined,
        leave_type: typeFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

function approveLeave(id) {
    if (window.confirm('Approve this leave request?')) {
        router.put(`/admin/leaves/${id}/approve`, {}, { preserveScroll: true });
    }
}

function rejectLeave(id) {
    if (window.confirm('Reject this leave request?')) {
        router.put(`/admin/leaves/${id}/reject`, {}, { preserveScroll: true });
    }
}

function deleteLeave(id) {
    if (window.confirm('Are you sure you want to delete this leave request?')) {
        router.delete(`/admin/leaves/${id}`, { preserveScroll: true });
    }
}

const typeKeys = {
    annual: 'a_annual',
    sick: 'a_sick',
    personal: 'a_personal',
    unpaid: 'a_unpaid',
};

const typeColors = {
    annual: 'bg-blue-100 text-blue-800',
    sick: 'bg-red-100 text-red-800',
    personal: 'bg-purple-100 text-purple-800',
    unpaid: 'bg-gray-100 text-gray-800',
};

const statusKeys = {
    pending: 'a_pending',
    approved: 'a_approved',
    rejected: 'a_rejected',
};

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
};
</script>

<template>
    <AdminLayout :title="$t('a_leaves')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_leave_requests') }}</h1>
                <Link
                    v-if="can('leaves.create')"
                    href="/admin/leaves/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg :class="['w-4 h-4', isRtl ? 'ml-2' : 'mr-2']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_new_leave_request') }}
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <select
                    v-model="employeeFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_employees') }}</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_status') }}</option>
                    <option value="pending">{{ $t('a_pending') }}</option>
                    <option value="approved">{{ $t('a_approved') }}</option>
                    <option value="rejected">{{ $t('a_rejected') }}</option>
                </select>
                <select
                    v-model="typeFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_types') }}</option>
                    <option value="annual">{{ $t('a_annual') }}</option>
                    <option value="sick">{{ $t('a_sick') }}</option>
                    <option value="personal">{{ $t('a_personal') }}</option>
                    <option value="unpaid">{{ $t('a_unpaid') }}</option>
                </select>
            </div>

            <!-- Leaves Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_employee') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_type') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_start_date') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_end_date') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_reason') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_approved_by') }}</th>
                                <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div :class="['w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold', isRtl ? 'ml-3' : 'mr-3']" style="background-color: #C4A265;">
                                            {{ leave.user?.name?.charAt(0) }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ leave.user?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="typeColors[leave.leave_type] || 'bg-gray-100 text-gray-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ $t(typeKeys[leave.leave_type]) || leave.leave_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ leave.start_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ leave.end_date }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-[200px] truncate">{{ leave.reason || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="statusColors[leave.status] || 'bg-gray-100 text-gray-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ $t(statusKeys[leave.status]) || leave.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ leave.approved_by_user?.name || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm space-x-2 rtl:space-x-reverse">
                                    <template v-if="leave.status === 'pending'">
                                        <button v-if="can('leaves.approve')" @click="approveLeave(leave.id)" class="px-3 py-1 rounded text-white text-xs font-medium bg-green-600 hover:bg-green-700 transition">{{ $t('a_approve') }}</button>
                                        <button v-if="can('leaves.approve')" @click="rejectLeave(leave.id)" class="px-3 py-1 rounded text-white text-xs font-medium bg-red-600 hover:bg-red-700 transition">{{ $t('a_reject') }}</button>
                                    </template>
                                    <button v-if="can('leaves.delete')" @click="deleteLeave(leave.id)" class="font-medium text-red-600 hover:underline text-xs">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!leaves.data || leaves.data.length === 0">
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_leave_requests_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="leaves.links && leaves.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ leaves.from }} {{ $t('a_to') }} {{ leaves.to }} {{ $t('a_of') }} {{ leaves.total }} {{ $t('a_results') }}</p>
                    <nav :class="['flex', isRtl ? 'space-x-reverse space-x-1' : 'space-x-1']">
                        <template v-for="link in leaves.links" :key="link.label">
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
