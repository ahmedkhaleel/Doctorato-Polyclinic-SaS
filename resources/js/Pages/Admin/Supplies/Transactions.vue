<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    supply: Object,
    transactions: Object,
});

const showForm = ref(false);

const form = useForm({
    transaction_type: 'purchase',
    quantity: '',
    unit_cost: '',
    notes: '',
});

function submit() {
    form.post(`/admin/supplies/${props.supply.id}/transactions`, {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const typeLabels = computed(() => ({
    purchase: t('a_purchase'),
    usage: t('a_usage'),
    adjustment: t('a_adjustment'),
    return: t('a_return'),
}));

const typeColors = {
    purchase: 'bg-green-100 text-green-800',
    usage: 'bg-blue-100 text-blue-800',
    adjustment: 'bg-yellow-100 text-yellow-800',
    return: 'bg-purple-100 text-purple-800',
};

function isLowStock() {
    return props.supply.quantity <= props.supply.min_quantity;
}
</script>

<template>
    <AdminLayout :title="`${$t('a_transactions')} - ${supply.name_en}`">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ supply.name_en }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_transaction_history') }}</p>
                </div>
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <span class="text-sm font-mono px-3 py-1 rounded-full bg-yellow-50" style="color: #6366F1;">{{ supply.sku }}</span>
                    <Link href="/admin/supplies" class="text-sm text-gray-500 hover:text-gray-700 underline">{{ $t('a_back_to_supplies') }}</Link>
                </div>
            </div>

            <!-- Supply Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">{{ $t('a_current_stock') }}</p>
                    <p class="text-2xl font-bold" :class="isLowStock() ? 'text-red-600' : 'text-gray-900'">{{ supply.quantity }} {{ supply.unit || '' }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">{{ $t('a_min_quantity') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ supply.min_quantity }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">{{ $t('a_status') }}</p>
                    <span
                        :class="isLowStock() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                        class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    >
                        {{ isLowStock() ? $t('a_low_stock') : $t('a_in_stock') }}
                    </span>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">{{ $t('a_category') }}</p>
                    <p class="text-lg font-semibold text-gray-900">{{ supply.category || '-' }}</p>
                </div>
            </div>

            <!-- Add Transaction Form -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_add_transaction') }}</h2>
                    <button
                        v-if="can('supplies.update')"
                        @click="showForm = !showForm"
                        class="text-sm font-medium hover:underline"
                        style="color: #6366F1;"
                    >
                        {{ showForm ? $t('a_cancel') : $t('a_new_transaction') }}
                    </button>
                </div>
                <div v-if="showForm" class="p-6">
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_type') }} <span class="text-red-500">*</span></label>
                            <select v-model="form.transaction_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-transparent">
                                <option value="purchase">{{ $t('a_purchase') }}</option>
                                <option value="adjustment">{{ $t('a_adjustment') }}</option>
                            </select>
                            <p v-if="form.errors.transaction_type" class="mt-1 text-sm text-red-600">{{ form.errors.transaction_type }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_quantity') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.quantity" type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-transparent" :placeholder="$t('a_use_negative_reduction')" />
                            <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_unit_cost') }}</label>
                            <input v-model="form.unit_cost" type="number" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-transparent" placeholder="0.00" />
                            <p v-if="form.errors.unit_cost" class="mt-1 text-sm text-red-600">{{ form.errors.unit_cost }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                            <input v-model="form.notes" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-transparent" :placeholder="$t('a_optional_notes')" />
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                style="background-color: #6366F1;"
                            >
                                {{ form.processing ? $t('a_saving') : $t('a_add_transaction') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transaction History Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_type') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_quantity') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_unit_cost') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_visit') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_notes') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_by') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="txn in transactions.data" :key="txn.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(txn.created_at) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="typeColors[txn.transaction_type] || 'bg-gray-100 text-gray-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ typeLabels[txn.transaction_type] || txn.transaction_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" :class="txn.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ txn.quantity > 0 ? '+' : '' }}{{ txn.quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ txn.unit_cost ? formatCurrency(txn.unit_cost) : '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <Link v-if="txn.visit_id" :href="`/admin/visits/${txn.visit_id}`" class="font-medium hover:underline" style="color: #6366F1;">#{{ txn.visit_id }}</Link>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ txn.notes || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ txn.user?.name || '-' }}</td>
                            </tr>
                            <tr v-if="!transactions.data || transactions.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_transactions_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="transactions.links && transactions.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ transactions.from }} {{ $t('a_to') }} {{ transactions.to }} {{ $t('a_of') }} {{ transactions.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1 rtl:space-x-reverse">
                        <template v-for="link in transactions.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 text-sm rounded border transition"
                                :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #6366F1;' : ''"
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
