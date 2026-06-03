<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { confirm } = useConfirm();
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
    invoices: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const moduleFilter = ref(props.filters?.module || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        module: moduleFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    };
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/invoices', buildParams(), {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

watch([statusFilter, moduleFilter, dateFrom, dateTo], () => {
    router.get('/admin/invoices', buildParams(), {
        preserveState: true,
        replace: true,
    });
});

function deleteInvoice(id) {
    confirm({
        title: isRtl.value ? 'حذف الفاتورة' : 'Delete invoice',
        message: isRtl.value ? 'سيتم حذف هذه الفاتورة. لا يمكن التراجع عن هذا الإجراء.' : 'This invoice will be deleted. This action cannot be undone.',
    }, () => {
        router.post(`/admin/invoices/${id}/delete`);
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const statusColors = {
    paid: 'bg-emerald-100 text-emerald-800',
    partial: 'bg-amber-100 text-amber-800',
    unpaid: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
};

const statusTranslations = {
    paid: { en: 'Paid', ar: 'مدفوع' },
    partial: { en: 'Partial', ar: 'جزئي' },
    unpaid: { en: 'Unpaid', ar: 'غير مدفوع' },
    cancelled: { en: 'Cancelled', ar: 'ملغي' },
};

function statusLabel(status) {
    return statusTranslations[status]?.[locale.value] || status;
}
</script>

<template>
    <AdminLayout :title="$t('a_invoices')">
        <div class="space-y-6">
            <!-- ═════════ Compact Hero ═════════ -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1B365D] to-[#0F2444] flex items-center justify-center shadow-md flex-shrink-0">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xl md:text-2xl font-extrabold text-[#1B365D] truncate">{{ $t('a_invoices') }}</h1>
                        <p class="text-xs text-slate-500 mt-0.5">{{ isRtl ? 'إدارة الفواتير والمدفوعات' : 'Manage invoices and billing' }}</p>
                    </div>
                </div>
                <Link
                    v-if="can('invoices.create')"
                    href="/admin/invoices/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-4 py-2.5 shadow-md hover:shadow-lg transition text-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ $t('a_new_invoice') }}
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_invoices_placeholder')"
                    class="doctorato-input w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                />
                <select
                    v-model="statusFilter"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_status') }}</option>
                    <option value="paid">{{ $t('a_paid') }}</option>
                    <option value="partial">{{ $t('a_partial') }}</option>
                    <option value="unpaid">{{ $t('a_unpaid') }}</option>
                    <option value="cancelled">{{ $t('a_cancelled') }}</option>
                </select>
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
                    :placeholder="$t('a_from_date')"
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
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_invoice_number') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_total') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_paid') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_balance') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(invoice, i) in invoices.data" :key="invoice.id" class="inv-row hover:bg-gray-50" :style="{ '--row-i': i }">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-mono" style="color: #C4A265;">{{ invoice.invoice_number }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ invoice.patient?.full_name }}</div>
                                        <div class="text-xs text-gray-400">{{ invoice.patient?.phone }}</div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(invoice.invoice_date || invoice.created_at) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ formatCurrency(invoice.total) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatCurrency(invoice.paid_amount) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium" :class="(invoice.total - invoice.paid_amount) > 0 ? 'text-red-600' : 'text-emerald-600'">
                                    {{ formatCurrency(invoice.total - invoice.paid_amount) }}
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="statusColors[invoice.status]"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ statusLabel(invoice.status) }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-end text-sm space-x-3 rtl:space-x-reverse">
                                    <Link v-if="can('invoices.view')" :href="`/admin/invoices/${invoice.id}`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view') }}</Link>
                                    <Link v-if="can('invoices.view')" :href="`/admin/invoices/${invoice.id}/print`" target="_blank" class="font-medium text-[#1B365D] hover:underline">{{ $t('a_print') }}</Link>
                                    <a v-if="can('invoices.view')" :href="`/admin/invoices/${invoice.id}/pdf`" class="font-medium text-emerald-600 hover:underline">PDF</a>
                                    <button v-if="can('invoices.delete') && invoice.status !== 'paid'" @click="deleteInvoice(invoice.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="8" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_invoices_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="invoices.links && invoices.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ invoices.from }} {{ $t('a_to') }} {{ invoices.to }} {{ $t('a_of') }} {{ invoices.total }} {{ $t('a_results') }}</p>
                    <nav class="flex gap-1 ltr:space-x-1 rtl:space-x-reverse">
                        <template v-for="link in invoices.links" :key="link.label">
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
.inv-row {
    animation: invRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes invRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .inv-row { animation: none !important; }
}
</style>
