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
    departments: Array,
    managers: Array,
});

const showModal = ref(false);
const editingDepartment = ref(null);

const form = useForm({
    name_ar: '',
    name_en: '',
    description: '',
    manager_id: '',
    is_active: true,
    display_order: 0,
});

function openCreate() {
    editingDepartment.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(dept) {
    editingDepartment.value = dept;
    form.name_ar = dept.name_ar;
    form.name_en = dept.name_en;
    form.description = dept.description || '';
    form.manager_id = dept.manager_id || '';
    form.is_active = dept.is_active;
    form.display_order = dept.display_order;
    form.clearErrors();
    showModal.value = true;
}

function submitForm() {
    if (editingDepartment.value) {
        form.post(`/admin/departments/${editingDepartment.value.id}`, {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post('/admin/departments', {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function deleteDepartment(dept) {
    if (dept.employees_count > 0) {
        alert('Cannot delete department with existing employees.');
        return;
    }
    if (window.confirm(`Are you sure you want to delete "${dept.name_en}"?`)) {
        router.post(`/admin/departments/${dept.id}/delete`, { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_departments')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_departments') }}</h1>
                <button
                    v-if="can('departments.create')"
                    @click="openCreate"
                    class="px-4 py-2 rounded-lg text-white text-sm font-medium transition hover:opacity-90"
                    style="background-color: #C4A265;"
                >
                    + {{ $t('a_add_department') }}
                </button>
            </div>

            <!-- Departments Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="dept in departments"
                    :key="dept.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow"
                >
                    <div class="h-1.5" :style="{ backgroundColor: dept.is_active ? '#C4A265' : '#D1D5DB' }"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ dept.name_en }}</h3>
                                <p class="text-sm text-gray-500" dir="rtl">{{ dept.name_ar }}</p>
                            </div>
                            <span
                                :class="dept.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'"
                                class="px-2 py-0.5 text-xs font-medium rounded-full"
                            >
                                {{ dept.is_active ? $t('a_active') : $t('a_inactive') }}
                            </span>
                        </div>

                        <p v-if="dept.description" class="text-sm text-gray-500 mb-3 line-clamp-2">{{ dept.description }}</p>

                        <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span>{{ dept.employees_count }} {{ $t('a_employees') }}</span>
                            </div>
                            <span v-if="dept.manager" class="text-xs truncate max-w-[120px]">{{ dept.manager.name }}</span>
                        </div>

                        <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                            <button
                                v-if="can('departments.update')"
                                @click="openEdit(dept)"
                                class="flex-1 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50 transition"
                            >
                                {{ $t('a_edit') }}
                            </button>
                            <button
                                v-if="can('departments.delete')"
                                @click="deleteDepartment(dept)"
                                class="px-3 py-1.5 text-sm font-medium rounded-md border border-red-200 text-red-600 hover:bg-red-50 transition"
                            >
                                {{ $t('a_delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!departments || departments.length === 0" class="bg-white rounded-lg shadow-sm p-4 md:p-8 text-center text-gray-500">
                {{ $t('a_no_departments_found') }}
            </div>

            <!-- Modal -->
            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg p-4 md:p-6 z-10">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">
                            {{ editingDepartment ? $t('a_edit_department') : $t('a_add_department') }}
                        </h2>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description') }}</label>
                                <textarea v-model="form.description" rows="2" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_manager') }}</label>
                                    <select v-model="form.manager_id" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                        <option value="">{{ $t('a_none') }}</option>
                                        <option v-for="mgr in managers" :key="mgr.id" :value="mgr.id">{{ mgr.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                                    <input v-model="form.display_order" type="number" min="0" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-gray-300 text-amber-600 focus:ring-[#C4A265]/30" />
                                <label for="is_active" class="text-sm text-gray-700">{{ $t('a_active') }}</label>
                            </div>

                            <div class="flex items-center gap-3 pt-4 border-t">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                    style="background-color: #C4A265;"
                                >
                                    {{ form.processing ? $t('a_saving') : (editingDepartment ? $t('a_update') : $t('a_create')) }}
                                </button>
                                <button
                                    type="button"
                                    @click="showModal = false"
                                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50 transition"
                                >
                                    {{ $t('a_cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </AdminLayout>
</template>
