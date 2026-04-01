<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    attendances: Object,
    users: Array,
    filters: Object,
});

const employeeFilter = ref(props.filters?.user_id || '');
const statusFilter = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

const showForm = ref(false);

const form = useForm({
    user_id: '',
    date: '',
    check_in: '',
    check_out: '',
    status: 'present',
    notes: '',
});

function applyFilters() {
    router.get('/admin/attendances', {
        user_id: employeeFilter.value || undefined,
        status: statusFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

watch([employeeFilter, statusFilter], () => applyFilters());

let dateTimeout = null;
watch([dateFrom, dateTo], () => {
    clearTimeout(dateTimeout);
    dateTimeout = setTimeout(() => applyFilters(), 400);
});

function submitAttendance() {
    form.post('/admin/attendances', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
}

function deleteAttendance(id) {
    if (window.confirm('Are you sure you want to delete this attendance record?')) {
        router.delete(`/admin/attendances/${id}`, { preserveScroll: true });
    }
}

const statusKeys = {
    present: 'a_present',
    absent: 'a_absent',
    late: 'a_late',
    leave: 'a_leave',
};

const statusColors = {
    present: 'bg-green-100 text-green-800',
    absent: 'bg-red-100 text-red-800',
    late: 'bg-yellow-100 text-yellow-800',
    leave: 'bg-blue-100 text-blue-800',
};
</script>

<template>
    <AdminLayout :title="$t('a_attendance')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_attendance') }}</h1>
                <button
                    v-if="can('attendances.create')"
                    @click="showForm = !showForm"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg :class="['w-4 h-4', isRtl ? 'ml-2' : 'mr-2']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_mark_attendance') }}
                </button>
            </div>

            <!-- Mark Attendance Form -->
            <div v-if="showForm" class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">{{ $t('a_mark_attendance') }}</h2>
                <form @submit.prevent="submitAttendance" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_employee') }} <span class="text-red-500">*</span></label>
                        <select v-model="form.user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                            <option value="">{{ $t('a_select_employee') }}</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                        <p v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">{{ form.errors.user_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_date') }} <span class="text-red-500">*</span></label>
                        <input v-model="form.date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="form.errors.date" class="mt-1 text-sm text-red-600">{{ form.errors.date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }} <span class="text-red-500">*</span></label>
                        <select v-model="form.status" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                            <option value="present">{{ $t('a_present') }}</option>
                            <option value="absent">{{ $t('a_absent') }}</option>
                            <option value="late">{{ $t('a_late') }}</option>
                            <option value="leave">{{ $t('a_leave') }}</option>
                        </select>
                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_check_in') }}</label>
                        <input v-model="form.check_in" type="time" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="form.errors.check_in" class="mt-1 text-sm text-red-600">{{ form.errors.check_in }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_check_out') }}</label>
                        <input v-model="form.check_out" type="time" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="form.errors.check_out" class="mt-1 text-sm text-red-600">{{ form.errors.check_out }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                        <input v-model="form.notes" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" :placeholder="$t('a_optional_notes')" />
                    </div>
                    <div :class="['sm:col-span-2 lg:col-span-3 flex', isRtl ? 'justify-start space-x-reverse space-x-3' : 'justify-end space-x-3']">
                        <button @click="showForm = false" type="button" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50 transition">{{ $t('a_cancel') }}</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50" style="background-color: #C4A265;">
                            {{ form.processing ? $t('a_saving') : $t('a_save_attendance') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <select v-model="employeeFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                    <option value="">{{ $t('a_all_employees') }}</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
                <select v-model="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                    <option value="">{{ $t('a_all_status') }}</option>
                    <option value="present">{{ $t('a_present') }}</option>
                    <option value="absent">{{ $t('a_absent') }}</option>
                    <option value="late">{{ $t('a_late') }}</option>
                    <option value="leave">{{ $t('a_leave') }}</option>
                </select>
                <input v-model="dateFrom" type="date" :max="dateTo || undefined" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                <input v-model="dateTo" type="date" :min="dateFrom || undefined" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
            </div>

            <!-- Attendance Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_employee') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_check_in') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_check_out') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_overtime') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_notes') }}</th>
                                <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="record in attendances.data" :key="record.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div :class="['w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold', isRtl ? 'ml-3' : 'mr-3']" style="background-color: #C4A265;">
                                            {{ record.user?.name?.charAt(0) }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ record.user?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ record.date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ record.check_in || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ record.check_out || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="statusColors[record.status] || 'bg-gray-100 text-gray-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ $t(statusKeys[record.status]) || record.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ record.overtime || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-[200px] truncate">{{ record.notes || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm space-x-3 rtl:space-x-reverse">
                                    <button v-if="can('attendances.delete')" @click="deleteAttendance(record.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!attendances.data || attendances.data.length === 0">
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_attendance_records_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="attendances.links && attendances.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ attendances.from }} {{ $t('a_to') }} {{ attendances.to }} {{ $t('a_of') }} {{ attendances.total }} {{ $t('a_results') }}</p>
                    <nav :class="['flex', isRtl ? 'space-x-reverse space-x-1' : 'space-x-1']">
                        <template v-for="link in attendances.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 text-sm rounded border transition" :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'" :style="link.active ? 'background-color: #C4A265;' : ''" preserve-state />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
