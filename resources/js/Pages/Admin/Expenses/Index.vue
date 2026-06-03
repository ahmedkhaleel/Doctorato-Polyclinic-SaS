<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { confirm } = useConfirm();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    expenses: Object,
    categories: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const categoryFilter = ref(props.filters?.category || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        category: categoryFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    };
}

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/expenses', buildParams(), {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

watch([categoryFilter, dateFrom, dateTo], () => {
    router.get('/admin/expenses', buildParams(), {
        preserveState: true,
        replace: true,
    });
});

function deleteExpense(id) {
    confirm(t('a_confirm_delete_expense'), () => {
        router.post(`/admin/expenses/${id}/delete`);
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const { formatCurrency } = useCurrency();

const recurringLabels = computed(() => ({
    daily: t('a_daily'),
    weekly: t('a_weekly'),
    monthly: t('a_monthly'),
    yearly: t('a_yearly'),
}));
</script>

<template>
    <AdminLayout :title="$t('a_expenses')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_expenses') }}</h1>
                <Link
                    v-if="can('expenses.create')"
                    href="/admin/expenses/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_expense') }}
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_expenses')"
                    class="doctorato-input w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                />
                <select
                    v-model="categoryFilter"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_categories') }}</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name_en }}</option>
                </select>
                <input
                    v-model="dateFrom"
                    type="date"
                    :max="dateTo || undefined"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                    :placeholder="$t('a_from')"
                />
                <input
                    v-model="dateTo"
                    type="date"
                    :min="dateFrom || undefined"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                    :placeholder="$t('a_to_date')"
                />
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_category') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_item') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_amount') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_description') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_recurring') }}</th>
                                <th class="px-4 md:px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="expense in expenses.data" :key="expense.id" class="hover:bg-gray-50">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(expense.expense_date) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ expense.category?.name_en || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ expense.item?.name_en || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold" style="color: #C4A265;">{{ formatCurrency(expense.amount) }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ expense.description || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span v-if="expense.is_recurring" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-[#1B365D]">
                                        {{ recurringLabels[expense.recurring_period] || expense.recurring_period }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-end text-sm space-x-3 rtl:space-x-reverse">
                                    <Link v-if="can('expenses.update')" :href="`/admin/expenses/${expense.id}/edit`" class="font-medium text-[#1B365D] hover:underline">{{ $t('a_edit') }}</Link>
                                    <button v-if="can('expenses.delete')" @click="deleteExpense(expense.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!expenses.data || expenses.data.length === 0">
                                <td colspan="7" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_expenses_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="expenses.links && expenses.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ expenses.from }} {{ $t('a_to') }} {{ expenses.to }} {{ $t('a_of') }} {{ expenses.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1">
                        <template v-for="link in expenses.links" :key="link.label">
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
