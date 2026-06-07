<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import DeskHeader from '@/Components/Secretary/DeskHeader.vue';
import UiEmptyState from '@/Components/Ui/EmptyState.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    orders: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch(status, () => applyFilters());

function applyFilters() {
    router.get('/secretary/dental/lab-orders', {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function isOverdue(order) {
    if (!order.expected_date || order.status === 'delivered' || order.status === 'completed' || order.status === 'cancelled') return false;
    return new Date(order.expected_date) < new Date();
}

const receivingOrderId = ref(null);

function markReceived(order) {
    if (receivingOrderId.value) return;
    receivingOrderId.value = order.id;
    router.post(`/secretary/dental/lab-orders/${order.id}/received`, {}, {
        preserveScroll: true,
        onFinish: () => {
            receivingOrderId.value = null;
        },
    });
}

const statusColors = {
    ordered: 'bg-gray-50 text-gray-700 border-gray-200',
    in_production: 'bg-slate-50 text-[#1B365D] border-slate-200',
    ready: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    delivered: 'bg-teal-50 text-teal-700 border-teal-200',
    adjustment: 'bg-amber-50 text-amber-700 border-amber-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

const statusLabels = computed(() => ({
    ordered: isRtl.value ? 'تم الطلب' : 'Ordered',
    in_production: isRtl.value ? 'قيد التصنيع' : 'In Production',
    ready: isRtl.value ? 'جاهز' : 'Ready',
    delivered: isRtl.value ? 'تم التسليم' : 'Delivered',
    adjustment: isRtl.value ? 'تعديل' : 'Adjustment',
    completed: isRtl.value ? 'مكتمل' : 'Completed',
    cancelled: isRtl.value ? 'ملغي' : 'Cancelled',
}));

const itemTypeLabels = computed(() => ({
    crown: isRtl.value ? 'تاج' : 'Crown',
    bridge: isRtl.value ? 'جسر' : 'Bridge',
    veneer: isRtl.value ? 'قشرة' : 'Veneer',
    denture: isRtl.value ? 'طقم أسنان' : 'Denture',
    implant: isRtl.value ? 'زرعة' : 'Implant',
    retainer: isRtl.value ? 'مثبت' : 'Retainer',
    night_guard: isRtl.value ? 'واقي ليلي' : 'Night Guard',
    other: isRtl.value ? 'أخرى' : 'Other',
}));
</script>

<template>
    <div>
        <!-- Header -->
        <DeskHeader accent="#C4A265" class="mb-6"
            :title="isRtl ? 'طلبات المختبر' : 'Lab Orders'"
            :subtitle="isRtl ? 'متابعة طلبات مختبر الأسنان' : 'Track dental lab orders'">
            <template #actions>
                <Link href="/secretary/dental" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-[#1B365D] bg-white/95 rounded-xl hover:bg-white transition-all">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'رجوع' : 'Back' }}
                </Link>
            </template>
        </DeskHeader>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ isRtl ? 'طلبات معلقة' : 'Pending Orders' }}</p>
                        <p class="text-3xl font-bold text-amber-600 mt-1">{{ stats?.pending ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-yellow-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ isRtl ? 'متأخرة' : 'Overdue' }}</p>
                        <p class="text-3xl font-bold text-red-600 mt-1">{{ stats?.overdue ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ isRtl ? 'في المختبر' : 'In Lab' }}</p>
                        <p class="text-3xl font-bold text-[#C4A265] mt-1">{{ stats?.in_lab ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-[#C4A265]/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input
                v-model="search"
                type="text"
                :placeholder="isRtl ? 'بحث بالمريض أو المختبر...' : 'Search by patient or lab...'"
                class="doctorato-input px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] w-full sm:w-72"
            />
            <select v-model="status" class="doctorato-input px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                <option value="ordered">{{ isRtl ? 'تم الطلب' : 'Ordered' }}</option>
                <option value="in_production">{{ isRtl ? 'قيد التصنيع' : 'In Production' }}</option>
                <option value="ready">{{ isRtl ? 'جاهز' : 'Ready' }}</option>
                <option value="delivered">{{ isRtl ? 'تم التسليم' : 'Delivered' }}</option>
                <option value="adjustment">{{ isRtl ? 'تعديل' : 'Adjustment' }}</option>
                <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                <option value="cancelled">{{ isRtl ? 'ملغي' : 'Cancelled' }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'نوع العنصر' : 'Item Type' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'المادة' : 'Material' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">{{ isRtl ? 'المختبر' : 'Lab Name' }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ المتوقع' : 'Expected Date' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="hover:bg-gray-50/50 transition-colors"
                            :class="{ 'bg-red-50/40': isOverdue(order) }"
                        >
                            <td class="px-6 py-3">
                                <div class="font-semibold text-gray-800">{{ order.patient?.full_name || '-' }}</div>
                                <div class="text-xs text-gray-400">{{ order.patient?.file_number || '' }}</div>
                            </td>
                            <td class="px-6 py-3 text-gray-700">{{ itemTypeLabels[order.item_type] || order.item_type || '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 hidden sm:table-cell">{{ order.material || '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 hidden sm:table-cell">{{ order.lab_name || '-' }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="statusColors[order.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                    {{ statusLabels[order.status] || order.status }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                <span :class="{ 'text-red-600 font-semibold': isOverdue(order), 'text-gray-600': !isOverdue(order) }">
                                    {{ formatDate(order.expected_date) }}
                                </span>
                                <div v-if="isOverdue(order)" class="text-xs text-red-500 font-medium">{{ isRtl ? 'متأخر' : 'Overdue' }}</div>
                            </td>
                            <td class="px-6 py-3 ltr:text-right rtl:text-left">
                                <button
                                    v-if="order.status === 'ready' || order.status === 'in_production'"
                                    @click="markReceived(order)"
                                    :disabled="receivingOrderId === order.id"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all"
                                    :class="receivingOrderId === order.id
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-[#C4A265]/10 text-[#C4A265] hover:bg-[#C4A265]/20'"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ receivingOrderId === order.id ? (isRtl ? 'جاري...' : 'Processing...') : (isRtl ? 'تم الاستلام' : 'Mark Received') }}
                                </button>
                                <span v-else-if="order.status === 'delivered' || order.status === 'completed'" class="text-xs text-emerald-600 font-medium">
                                    {{ isRtl ? 'تم الاستلام' : 'Received' }}
                                </span>
                                <span v-else class="text-xs text-gray-400">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <UiEmptyState v-if="orders.data?.length === 0" icon="document" :title="isRtl ? 'لا توجد طلبات مختبر' : 'No lab orders found'" />

            <!-- Pagination -->
            <div v-if="orders.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in orders.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
