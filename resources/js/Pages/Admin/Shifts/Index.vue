<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    shifts: Array,
});

const editingId = ref(null);

const addForm = useForm({
    name_ar: '',
    name_en: '',
    start_time: '',
    end_time: '',
    is_active: true,
});

const editForm = useForm({
    name_ar: '',
    name_en: '',
    start_time: '',
    end_time: '',
    is_active: true,
});

function submitAdd() {
    addForm.post('/admin/shifts', {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    });
}

function startEdit(shift) {
    editingId.value = shift.id;
    editForm.name_ar = shift.name_ar;
    editForm.name_en = shift.name_en;
    editForm.start_time = shift.start_time;
    editForm.end_time = shift.end_time;
    editForm.is_active = shift.is_active;
}

function cancelEdit() {
    editingId.value = null;
    editForm.reset();
}

function submitEdit(id) {
    editForm.put(`/admin/shifts/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });
}

function deleteShift(id) {
    if (window.confirm('Are you sure you want to delete this shift?')) {
        router.delete(`/admin/shifts/${id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_shifts')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_shifts') }}</h1>
            </div>

            <!-- Add Shift Form -->
            <div v-if="can('shifts.create')" class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">{{ $t('a_add_new_shift') }}</h2>
                <form @submit.prevent="submitAdd" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }} <span class="text-red-500">*</span></label>
                        <input v-model="addForm.name_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="addForm.errors.name_ar" class="mt-1 text-sm text-red-600">{{ addForm.errors.name_ar }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }} <span class="text-red-500">*</span></label>
                        <input v-model="addForm.name_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="addForm.errors.name_en" class="mt-1 text-sm text-red-600">{{ addForm.errors.name_en }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_start_time') }} <span class="text-red-500">*</span></label>
                        <input v-model="addForm.start_time" type="time" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="addForm.errors.start_time" class="mt-1 text-sm text-red-600">{{ addForm.errors.start_time }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_end_time') }} <span class="text-red-500">*</span></label>
                        <input v-model="addForm.end_time" type="time" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="addForm.errors.end_time" class="mt-1 text-sm text-red-600">{{ addForm.errors.end_time }}</p>
                    </div>
                    <div>
                        <button type="submit" :disabled="addForm.processing" class="w-full px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50" style="background-color: #C4A265;">
                            {{ addForm.processing ? $t('a_adding') : $t('a_add_shift') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Shifts Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name_ar') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name_en') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_start_time') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_end_time') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_active') }}</th>
                                <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template v-for="shift in shifts" :key="shift.id">
                                <!-- Display Row -->
                                <tr v-if="editingId !== shift.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" dir="rtl">{{ shift.name_ar }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ shift.name_en }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ shift.start_time }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ shift.end_time }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="shift.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ shift.is_active ? $t('a_active') : $t('a_inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm space-x-3 rtl:space-x-reverse">
                                        <button v-if="can('shifts.update')" @click="startEdit(shift)" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_edit') }}</button>
                                        <button v-if="can('shifts.delete')" @click="deleteShift(shift.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                    </td>
                                </tr>
                                <!-- Edit Row -->
                                <tr v-else class="bg-yellow-50">
                                    <td class="px-6 py-3">
                                        <input v-model="editForm.name_ar" type="text" dir="rtl" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </td>
                                    <td class="px-6 py-3">
                                        <input v-model="editForm.name_en" type="text" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </td>
                                    <td class="px-6 py-3">
                                        <input v-model="editForm.start_time" type="time" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </td>
                                    <td class="px-6 py-3">
                                        <input v-model="editForm.end_time" type="time" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </td>
                                    <td class="px-6 py-3">
                                        <select v-model="editForm.is_active" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                                            <option :value="true">{{ $t('a_active') }}</option>
                                            <option :value="false">{{ $t('a_inactive') }}</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-3 ltr:text-right rtl:text-left space-x-2 rtl:space-x-reverse">
                                        <button @click="submitEdit(shift.id)" :disabled="editForm.processing" class="px-3 py-1 rounded text-white text-sm font-medium transition disabled:opacity-50" style="background-color: #C4A265;">{{ $t('a_save') }}</button>
                                        <button @click="cancelEdit" class="px-3 py-1 rounded border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50 transition">{{ $t('a_cancel') }}</button>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="!shifts || shifts.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_shifts_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
