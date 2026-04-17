<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    expense: Object,
    categories: Array,
});

const form = useForm({
    _method: 'PUT',
    expense_category_id: props.expense.expense_category_id || '',
    expense_item_id: props.expense.expense_item_id || '',
    amount: props.expense.amount || '',
    expense_date: props.expense.expense_date || '',
    description: props.expense.description || '',
    receipt_photo: null,
    is_recurring: props.expense.is_recurring ?? false,
    recurring_period: props.expense.recurring_period || '',
});

const filteredItems = computed(() => {
    if (!form.expense_category_id) return [];
    const category = props.categories.find(c => c.id == form.expense_category_id);
    return category?.expense_items || [];
});

watch(() => form.expense_category_id, (newVal, oldVal) => {
    if (oldVal && newVal !== oldVal) {
        form.expense_item_id = '';
    }
});

function submit() {
    form.post(`/admin/expenses/${props.expense.id}`, {
        forceFormData: true,
    });
}

const recurringOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'yearly', label: 'Yearly' },
];
</script>

<template>
    <AdminLayout :title="$t('a_edit_expense')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_edit_expense') }}</h1>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Fields -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_expense_details') }}</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_category') }} <span class="text-red-500">*</span></label>
                                <select v-model="form.expense_category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent">
                                    <option value="">{{ $t('a_select_category') }}</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name_en }}</option>
                                </select>
                                <p v-if="form.errors.expense_category_id" class="mt-1 text-sm text-red-600">{{ form.errors.expense_category_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_item') }} <span class="text-red-500">*</span></label>
                                <select v-model="form.expense_item_id" :disabled="!form.expense_category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent disabled:bg-gray-100">
                                    <option value="">{{ $t('a_select_item') }}</option>
                                    <option v-for="item in filteredItems" :key="item.id" :value="item.id">{{ item.name_en }}</option>
                                </select>
                                <p v-if="form.errors.expense_item_id" class="mt-1 text-sm text-red-600">{{ form.errors.expense_item_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_amount') }} <span class="text-red-500">*</span></label>
                                <input v-model="form.amount" type="number" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" placeholder="0.00" />
                                <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_date') }} <span class="text-red-500">*</span></label>
                                <input v-model="form.expense_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" />
                                <p v-if="form.errors.expense_date" class="mt-1 text-sm text-red-600">{{ form.errors.expense_date }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description') }}</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" :placeholder="$t('a_optional_description')"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_receipt') }}</h2>
                        <div v-if="expense.receipt_photo" class="mb-2">
                            <img :src="expense.receipt_photo.startsWith('http') ? expense.receipt_photo : `/storage/${expense.receipt_photo}`" class="w-full max-h-40 object-contain rounded border" />
                            <span class="text-xs text-gray-500">{{ $t('a_current_receipt') }}</span>
                        </div>
                        <div>
                            <input type="file" accept="image/*" @input="form.receipt_photo = $event.target.files[0]" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100" />
                            <p class="text-xs text-gray-400 mt-1">{{ $t('a_leave_empty_keep_receipt') }}</p>
                            <p v-if="form.errors.receipt_photo" class="mt-1 text-sm text-red-600">{{ form.errors.receipt_photo }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_recurring') }}</h2>
                        <div>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" v-model="form.is_recurring" class="rounded border-gray-300 text-amber-600 focus:ring-amber-200" />
                                <span class="text-sm text-gray-700">{{ $t('a_recurring_expense') }}</span>
                            </label>
                        </div>
                        <div v-if="form.is_recurring">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_period') }}</label>
                            <select v-model="form.recurring_period" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent">
                                <option value="">{{ $t('a_select_period') }}</option>
                                <option v-for="opt in recurringOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <p v-if="form.errors.recurring_period" class="mt-1 text-sm text-red-600">{{ form.errors.recurring_period }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full px-4 md:px-6 py-3 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_update_expense') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
