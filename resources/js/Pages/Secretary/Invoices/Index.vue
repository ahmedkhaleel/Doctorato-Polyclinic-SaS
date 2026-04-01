<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ invoices: Object, filters: Object });

const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const moduleFilter = ref(props.filters?.module || '');

let timeout;
watch(search, () => { clearTimeout(timeout); timeout = setTimeout(applyFilters, 400); });
watch([status, moduleFilter], () => applyFilters());

function applyFilters() {
    router.get('/secretary/invoices', { search: search.value || undefined, status: status.value || undefined, module: moduleFilter.value || undefined }, { preserveState: true, replace: true });
}

const statusColors = {
    unpaid: 'bg-red-50 text-red-700 border-red-200',
    partial: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-gray-50 text-gray-600 border-gray-200',
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div><h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'الفواتير' : 'Invoices' }}</h1><p class="text-sm text-gray-500 mt-1">Manage patient invoices</p></div>
            <Link href="/secretary/invoices/create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-teal-500 to-cyan-500 text-white rounded-xl text-sm font-semibold hover:from-teal-600 hover:to-cyan-600 transition-all shadow-lg shadow-teal-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                New Invoice
            </Link>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input v-model="search" type="text" placeholder="Search by invoice # or patient..." class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 w-72" />
            <select v-model="status" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
                <option value="paid">{{ isRtl ? 'المدفوع' : 'Paid' }}</option>
            </select>
            <select v-if="activeModules.length > 1" v-model="moduleFilter" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50/80">
                    <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Invoice #</th>
                    <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                    <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                    <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                    <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المدفوع' : 'Paid' }}</th>
                    <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                    <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 font-semibold text-gray-800">{{ inv.invoice_number }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ inv.patient?.full_name }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ inv.invoice_date }}</td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left font-bold text-gray-800">{{ Number(inv.total).toLocaleString() }}</td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left text-gray-500">{{ Number(inv.paid_amount).toLocaleString() }}</td>
                        <td class="px-6 py-3"><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="statusColors[inv.status]">{{ inv.status }}</span></td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left"><Link :href="`/secretary/invoices/${inv.id}`" class="text-teal-600 hover:text-teal-800 text-xs font-semibold">{{ isRtl ? 'عرض' : 'View' }}</Link></td>
                    </tr>
                </tbody>
            </table>
            <div v-if="invoices.data?.length === 0" class="py-12 text-center"><p class="text-sm text-gray-400">No invoices found</p></div>
            <div v-if="invoices.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in invoices.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-teal-500 text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
