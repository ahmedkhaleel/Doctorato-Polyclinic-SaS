<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    prescriptions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
let timeout;

watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/secretary/prescriptions', { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 400);
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}
</script>

<template>
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'الوصفات الطبية' : 'Prescriptions' }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'عرض وصفات المرضى (للقراءة فقط)' : 'View patient prescriptions (read-only)' }}</p>
            </div>
        </div>

        <!-- Search -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input v-model="search" type="text" :placeholder="isRtl ? 'بحث بالمريض أو الطبيب...' : 'Search by patient or doctor...'" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 w-full sm:w-72" />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Rx #</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الأدوية' : 'Items' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Diagnosis</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الإجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="rx in prescriptions.data" :key="rx.id" class="hover:bg-gray-50/50">
                        <td class="px-6 py-3">
                            <span class="font-mono font-semibold text-teal-600">{{ rx.rx_number || `RX-${rx.id}` }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="font-semibold text-gray-800">{{ rx.patient?.full_name || '-' }}</div>
                            <div class="text-xs text-gray-400">{{ rx.patient?.phone || '' }}</div>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ rx.doctor?.name_en || rx.doctor?.name_ar || '-' }}</td>
                        <td class="px-6 py-3 text-gray-500 hidden sm:table-cell">{{ formatDate(rx.prescription_date || rx.created_at) }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700">
                                {{ isRtl ? (rx.items?.length || 0) + ' دواء' : (rx.items?.length || 0) + ' items' }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-500 max-w-xs truncate hidden sm:table-cell">{{ rx.diagnosis || '-' }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <Link :href="`/secretary/prescriptions/${rx.id}`" class="text-teal-600 hover:text-teal-800 transition" :title="isRtl ? 'عرض' : 'View'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </Link>
                                <Link :href="`/secretary/prescriptions/${rx.id}/print`" target="_blank" class="text-gray-400 hover:text-teal-600 transition" :title="isRtl ? 'طباعة' : 'Print'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                </Link>
                                <a :href="`/secretary/prescriptions/${rx.id}/pdf`" class="text-gray-400 hover:text-emerald-600 transition" :title="isRtl ? 'تحميل PDF' : 'Download PDF'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>

            <div v-if="prescriptions.data?.length === 0" class="py-12 text-center">
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد وصفات طبية' : 'No prescriptions found' }}</p>
            </div>

            <div v-if="prescriptions.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in prescriptions.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-teal-500 text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
