<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    categories: Object,
});

const showModal = ref(false);
const editingCategory = ref(null);

const form = useForm({
    name_ar: '',
    name_en: '',
});

function openCreate() {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(category) {
    editingCategory.value = category;
    form.name_ar = category.name_ar || '';
    form.name_en = category.name_en || '';
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingCategory.value = null;
    form.reset();
}

function submit() {
    if (editingCategory.value) {
        form.put(`/admin/expense-categories/${editingCategory.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/expense-categories', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteCategory(id) {
    if (window.confirm('Are you sure you want to delete this category? This will also affect related expenses.')) {
        router.delete(`/admin/expense-categories/${id}`);
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_expense_categories')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_expense_categories') }}</h1>
                <button
                    v-if="can('expense_categories.create')"
                    @click="openCreate"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_category') }}
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name_ar') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name_en') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_items_count') }}</th>
                                <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="category in categories.data" :key="category.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ category.name_ar }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ category.name_en }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ category.expense_items_count ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-3 rtl:space-x-reverse">
                                    <button v-if="can('expense_categories.update')" @click="openEdit(category)" class="font-medium text-blue-600 hover:underline">{{ $t('a_edit') }}</button>
                                    <button v-if="can('expense_categories.delete')" @click="deleteCategory(category.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!categories.data || categories.data.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_expense_categories_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="categories.links && categories.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ categories.from }} {{ $t('a_to') }} {{ categories.to }} {{ $t('a_of') }} {{ categories.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1">
                        <template v-for="link in categories.links" :key="link.label">
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

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal"></div>
                <div class="bg-white rounded-lg shadow-xl z-10 w-full max-w-md mx-4 p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">
                        {{ editingCategory ? $t('a_edit_category') : $t('a_add_category') }}
                    </h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.name_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.name_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                        </div>
                        <div class="flex justify-end space-x-3 pt-2">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">{{ $t('a_cancel') }}</button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                style="background-color: #C4A265;"
                            >
                                {{ form.processing ? $t('a_saving') : (editingCategory ? $t('a_update') : $t('a_create')) }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
