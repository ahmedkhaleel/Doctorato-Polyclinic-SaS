<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    wallets: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filter = ref(props.filters?.filter || '');

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

function applyFilters() {
    router.get('/admin/wallets', {
        search: search.value || undefined,
        filter: filter.value || undefined,
    }, { preserveState: true, replace: true });
}

function setFilter(f) {
    filter.value = filter.value === f ? '' : f;
    applyFilters();
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'محافظ المرضى' : 'Patient Wallets'">
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ isRtl ? 'محافظ المرضى' : 'Patient Wallets' }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'إدارة أرصدة المرضى والمعاملات المالية' : 'Manage patient balances and financial transactions' }}</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-gray-900">{{ stats.total_wallets }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي المحافظ' : 'Total Wallets' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-emerald-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-emerald-600">{{ formatCurrency(stats.total_balance) }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي الأرصدة' : 'Total Balance' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ stats.positive_wallets }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'محافظ نشطة' : 'Active Wallets' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-gray-900">{{ stats.transactions_today }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'معاملات اليوم' : 'Today\'s Transactions' }}</div>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <svg class="absolute top-2.5 w-4 h-4 text-gray-400" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35"/></svg>
                        <input v-model="search" type="text"
                            :placeholder="isRtl ? 'بحث بالاسم أو الهاتف أو رقم الملف...' : 'Search by name, phone, or file number...'"
                            class="doctorato-input w-full rounded-lg border-gray-300 text-sm focus:ring-[#1B365D] focus:border-[#1B365D]"
                            :class="isRtl ? 'pr-10 pl-3' : 'pl-10 pr-3'" />
                    </div>
                    <div class="flex gap-2">
                        <button @click="setFilter('positive')"
                            class="px-4 py-2 text-sm font-medium rounded-lg border transition"
                            :class="filter === 'positive' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                            {{ isRtl ? 'رصيد موجب' : 'Positive Balance' }}
                        </button>
                        <button @click="setFilter('zero')"
                            class="px-4 py-2 text-sm font-medium rounded-lg border transition"
                            :class="filter === 'zero' ? 'bg-gray-600 text-white border-gray-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                            {{ isRtl ? 'رصيد صفر' : 'Zero Balance' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Wallets Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الهاتف' : 'Phone' }}</th>
                            <th class="text-end px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الرصيد' : 'Balance' }}</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'آخر تحديث' : 'Last Updated' }}</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="wallet in wallets.data" :key="wallet.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/wallets/${wallet.patient?.id}`"
                                    class="text-sm font-semibold text-gray-900 hover:text-[#1B365D] transition">
                                    {{ wallet.patient?.full_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ wallet.patient?.file_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500" dir="ltr">{{ wallet.patient?.phone }}</td>
                            <td class="px-4 py-3 text-end">
                                <span class="text-sm font-bold"
                                    :class="Number(wallet.balance) > 0 ? 'text-emerald-600' : Number(wallet.balance) < 0 ? 'text-red-600' : 'text-gray-400'">
                                    {{ formatCurrency(wallet.balance) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                {{ new Date(wallet.updated_at).toLocaleDateString(isRtl ? 'ar-EG' : 'en-US', { month: 'short', day: 'numeric' }) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Link :href="`/admin/wallets/${wallet.patient?.id}`"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    {{ isRtl ? 'عرض' : 'View' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!wallets.data?.length" class="p-16 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 10h18V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2v-4M3 10v4m18-4v4m-4-2h.01" /></svg>
                    <h3 class="text-lg font-medium text-gray-900">{{ isRtl ? 'لا توجد محافظ' : 'No Wallets Found' }}</h3>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="wallets.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="link in wallets.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 text-sm rounded-lg border transition"
                        :class="link.active ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                        v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label" />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
