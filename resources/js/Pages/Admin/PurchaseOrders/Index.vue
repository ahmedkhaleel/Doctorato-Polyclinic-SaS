<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ orders: Object, stats: Object, suppliers: Array, filters: Object })

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const supplierFilter = ref(props.filters?.supplier_id || '')

function applyFilters() {
    router.get('/admin/purchase-orders', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        supplier_id: supplierFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

const statusLabels = {
    draft: { ar: 'مسودة', en: 'Draft', color: 'bg-gray-100 text-gray-700' },
    pending_approval: { ar: 'بانتظار الموافقة', en: 'Pending Approval', color: 'bg-yellow-100 text-yellow-700' },
    approved: { ar: 'معتمد', en: 'Approved', color: 'bg-blue-100 text-blue-700' },
    ordered: { ar: 'تم الطلب', en: 'Ordered', color: 'bg-indigo-100 text-indigo-700' },
    partially_received: { ar: 'استلام جزئي', en: 'Partially Received', color: 'bg-orange-100 text-orange-700' },
    received: { ar: 'مستلم', en: 'Received', color: 'bg-green-100 text-green-700' },
    cancelled: { ar: 'ملغي', en: 'Cancelled', color: 'bg-red-100 text-red-700' },
}

function statusLabel(status) {
    const s = statusLabels[status] || { ar: status, en: status, color: 'bg-gray-100 text-gray-600' }
    return { text: isRtl.value ? s.ar : s.en, color: s.color }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat(isRtl.value ? 'ar-EG' : 'en-EG', { minimumFractionDigits: 2 }).format(amount || 0)
}

function formatDate(d) {
    if (!d) return '-'
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB')
}
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'أوامر الشراء' : 'Purchase Orders' }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'إدارة أوامر الشراء والتوريدات' : 'Manage purchase orders and deliveries' }}</p>
            </div>
            <Link href="/admin/purchase-orders/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-600 text-white rounded-xl hover:bg-cyan-700 transition font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ isRtl ? 'أمر شراء جديد' : 'New Purchase Order' }}
            </Link>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</p>
                        <p class="text-xl font-bold text-gray-800">{{ stats.pending }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'نشط' : 'Active' }}</p>
                        <p class="text-xl font-bold text-gray-800">{{ stats.active }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-cyan-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'إجمالي القيمة' : 'Total Value' }}</p>
                        <p class="text-xl font-bold text-gray-800">{{ formatCurrency(stats.total_value) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'متأخر' : 'Overdue' }}</p>
                        <p class="text-xl font-bold" :class="stats.overdue > 0 ? 'text-red-600' : 'text-gray-800'">{{ stats.overdue }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <input v-model="search" @keyup.enter="applyFilters" type="text" :placeholder="isRtl ? 'بحث برقم الأمر...' : 'Search by PO number...'" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm w-64 focus:ring-cyan-500 focus:border-cyan-500" />
            <select v-model="statusFilter" @change="applyFilters" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ isRtl ? 'كل الحالات' : 'All Statuses' }}</option>
                <option v-for="(lbl, key) in statusLabels" :key="key" :value="key">{{ isRtl ? lbl.ar : lbl.en }}</option>
            </select>
            <select v-model="supplierFilter" @change="applyFilters" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ isRtl ? 'كل الموردين' : 'All Suppliers' }}</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ isRtl ? s.name_ar : s.name_en }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'رقم الأمر' : 'PO Number' }}</th>
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'المورد' : 'Supplier' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الأصناف' : 'Items' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'تاريخ الطلب' : 'Order Date' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'التسليم المتوقع' : 'Expected Delivery' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="o in orders.data" :key="o.id" class="hover:bg-gray-50/50">
                        <td class="px-4 py-3">
                            <span class="font-mono font-medium text-gray-800">{{ o.po_number }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-700">{{ isRtl ? o.supplier?.name_ar : o.supplier?.name_en }}</div>
                            <div v-if="o.supplier?.code" class="text-xs text-gray-400 font-mono">{{ o.supplier.code }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 bg-cyan-50 text-cyan-700 rounded-full text-xs font-medium">{{ o.items_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center font-medium text-gray-800">{{ formatCurrency(o.total) }}</td>
                        <td class="px-4 py-3 text-center text-gray-500 text-xs">{{ formatDate(o.order_date) }}</td>
                        <td class="px-4 py-3 text-center text-xs" :class="o.expected_delivery_date && new Date(o.expected_delivery_date) < new Date() && !['received','cancelled'].includes(o.status) ? 'text-red-600 font-medium' : 'text-gray-500'">
                            {{ formatDate(o.expected_delivery_date) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="statusLabel(o.status).color" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ statusLabel(o.status).text }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Link :href="`/admin/purchase-orders/${o.id}`" class="p-1.5 text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition inline-flex">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="orders.data.length === 0" class="text-center py-12 text-gray-400">
                {{ isRtl ? 'لا توجد أوامر شراء' : 'No purchase orders found' }}
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="orders.links && orders.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in orders.links" :key="link.label" :href="link.url || '#'" class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-cyan-600 text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
        </div>
    </div>
</template>
