<script setup>
import { computed, ref, watch } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';
import { usePatientStatus } from '@/Composables/usePatientStatus';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();
const { invoiceLabel, invoiceColor } = usePatientStatus();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const moduleFilter = ref(props.filters?.module || '');

watch(moduleFilter, () => applyFilters());

function applyFilters() {
    router.get(lp('/invoices'), { module: moduleFilter.value || undefined }, { preserveState: true, replace: true });
}

function balance(invoice) {
    return (parseFloat(invoice.total || 0) - parseFloat(invoice.paid_amount || 0)).toFixed(2);
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ t('p_my_invoices') }}</h1>
            <select v-if="activeModules.length > 1" v-model="moduleFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'رقم الفاتورة' : 'Invoice #' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'المدفوع' : 'Paid' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'المتبقي' : 'Balance' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in invoices?.data" :key="invoice.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ invoice.invoice_number }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ invoice.invoice_date }}</td>
                            <td class="px-6 py-4 font-medium text-gray-800">{{ formatCurrency(invoice.total) }}</td>
                            <td class="px-6 py-4 text-green-600">{{ formatCurrency(invoice.paid_amount) }}</td>
                            <td class="px-6 py-4" :class="parseFloat(balance(invoice)) > 0 ? 'text-red-600 font-medium' : 'text-green-600'">{{ formatCurrency(balance(invoice)) }}</td>
                            <td class="px-6 py-4">
                                <span :class="invoiceColor(invoice.status)" class="text-xs font-medium px-2.5 py-1 rounded-full">{{ invoiceLabel(invoice.status) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <Link :href="lp('/invoices/' + invoice.id)" class="text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] text-sm font-medium">
                                    {{ isRtl ? 'عرض' : 'View' }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-gray-100">
                <Link v-for="invoice in invoices?.data" :key="invoice.id" :href="lp('/invoices/' + invoice.id)" class="block p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-800">{{ invoice.invoice_number }}</span>
                        <span :class="invoiceColor(invoice.status)" class="text-xs font-medium px-2.5 py-1 rounded-full">{{ invoiceLabel(invoice.status) }}</span>
                    </div>
                    <p class="text-xs text-gray-400">{{ invoice.invoice_date }}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-sm font-semibold text-gray-800">{{ formatCurrency(invoice.total) }}</span>
                        <span class="text-xs" :class="parseFloat(balance(invoice)) > 0 ? 'text-red-600' : 'text-green-600'">
                            {{ isRtl ? 'المتبقي:' : 'Balance:' }} {{ formatCurrency(balance(invoice)) }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="!invoices?.data?.length" class="text-center py-16 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                <p class="text-sm">{{ isRtl ? 'لا توجد فواتير' : 'No invoices found' }}</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="invoices?.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in invoices.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="link.active ? 'bg-[var(--brand-primary)] text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    v-html="link.label"
                />
                <span v-else class="px-3 py-2 text-sm text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
