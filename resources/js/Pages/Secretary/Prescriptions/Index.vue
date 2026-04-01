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
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'الوصفات الطبية' : 'Prescriptions' }}</h1>
                <p class="text-sm text-gray-500 mt-1">View patient prescriptions (read-only)</p>
            </div>
        </div>

        <!-- Search -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input v-model="search" type="text" placeholder="Search by patient or doctor..." class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 w-72" />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Rx #</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Items</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Diagnosis</th>
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
                        <td class="px-6 py-3 text-gray-500">{{ formatDate(rx.prescription_date || rx.created_at) }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700">
                                {{ rx.items?.length || 0 }} items
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-500 max-w-xs truncate">{{ rx.diagnosis || '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <div v-if="prescriptions.data?.length === 0" class="py-12 text-center">
                <p class="text-sm text-gray-400">No prescriptions found</p>
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
