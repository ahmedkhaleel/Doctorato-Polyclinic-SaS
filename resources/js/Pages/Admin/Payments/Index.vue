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
const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false && m.is_medical !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const props = defineProps({
    payments: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    };
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/payments', buildParams(), {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

watch([dateFrom, dateTo, moduleFilter], () => {
    router.get('/admin/payments', buildParams(), {
        preserveState: true,
        replace: true,
    });
});

function deletePayment(id) {
    confirm(t('a_confirm_delete_payment'), () => {
        router.post(`/admin/payments/${id}/delete`);
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

</script>

<template>
    <AdminLayout :title="$t('a_payments')">
        <div class="space-y-6">
            <!-- ═════════ Compact Hero ═════════ -->
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1B365D] to-[#0F2444] flex items-center justify-center shadow-md flex-shrink-0">
                    <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl md:text-2xl font-extrabold text-[#1B365D] truncate">{{ $t('a_payments') }}</h1>
                    <p class="text-xs text-slate-500 mt-0.5">{{ isRtl ? 'سجل المدفوعات والإيصالات' : 'Payment records and receipts' }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_payments_placeholder')"
                    class="doctorato-input w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                />
                <select
                    v-if="activeModules.length> 1"
                    v-model="moduleFilter"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                >
                    <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                    <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
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
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_invoice_number') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_method') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_amount') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_reference') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_received_by') }}</th>
                                <th class="px-4 md:px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(payment, i) in payments.data" :key="payment.id" class="pay-row hover:bg-gray-50" :style="{ '--row-i': i }">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(payment.payment_date) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <Link
                                        v-if="payment.invoice"
                                        :href="`/admin/invoices/${payment.invoice.id}`"
                                        class="text-sm font-mono font-medium hover:underline"
                                        style="color: #C4A265;"
                                    >
                                        {{ payment.invoice.invoice_number }}
                                    </Link>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ payment.invoice?.patient?.full_name || '-' }}</div>
                                        <div class="text-xs text-gray-400">{{ payment.invoice?.patient?.phone || '' }}</div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ payment.payment_method?.name_en || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ payment.reference_number || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ payment.receiver?.name || payment.received_by_user?.name || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                                    <Link
                                        v-if="payment.invoice && can('invoices.view')"
                                        :href="`/admin/invoices/${payment.invoice.id}`"
                                        class="font-medium hover:underline"
                                        style="color: #C4A265;"
                                    >
                                        {{ $t('a_view_invoice') }}
                                    </Link>
                                    <button
                                        v-if="can('payments.delete')"
                                        @click="deletePayment(payment.id)"
                                        class="font-medium text-red-600 hover:underline"
                                    >
                                        {{ $t('a_delete') }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="8" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_payments_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="payments.links && payments.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ payments.from }} {{ $t('a_to') }} {{ payments.to }} {{ $t('a_of') }} {{ payments.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1">
                        <template v-for="link in payments.links" :key="link.label">
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

<style scoped>
.pay-row {
    animation: payRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes payRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .pay-row { animation: none !important; }
}
</style>
