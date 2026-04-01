<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ order: Object })

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const showStatusModal = ref(false)
const showReceiveModal = ref(false)
const newStatus = ref('')
const deliveryNotes = ref('')
const processing = ref(false)

const receiveItems = ref([])

const statusLabels = {
    draft: { ar: 'مسودة', en: 'Draft', color: 'bg-gray-100 text-gray-700' },
    pending_approval: { ar: 'بانتظار الموافقة', en: 'Pending Approval', color: 'bg-yellow-100 text-yellow-700' },
    approved: { ar: 'معتمد', en: 'Approved', color: 'bg-blue-100 text-blue-700' },
    ordered: { ar: 'تم الطلب', en: 'Ordered', color: 'bg-indigo-100 text-indigo-700' },
    partially_received: { ar: 'استلام جزئي', en: 'Partially Received', color: 'bg-orange-100 text-orange-700' },
    received: { ar: 'مستلم', en: 'Received', color: 'bg-green-100 text-green-700' },
    cancelled: { ar: 'ملغي', en: 'Cancelled', color: 'bg-red-100 text-red-700' },
}

const statusFlow = {
    draft: ['pending_approval', 'cancelled'],
    pending_approval: ['approved', 'cancelled'],
    approved: ['ordered', 'cancelled'],
    ordered: ['partially_received', 'received', 'cancelled'],
    partially_received: ['received'],
}

function statusLabel(status) {
    const s = statusLabels[status] || { ar: status, en: status, color: 'bg-gray-100 text-gray-600' }
    return { text: isRtl.value ? s.ar : s.en, color: s.color }
}

const nextStatuses = computed(() => statusFlow[props.order.status] || [])

function formatCurrency(amount) {
    return new Intl.NumberFormat(isRtl.value ? 'ar-EG' : 'en-EG', { minimumFractionDigits: 2 }).format(amount || 0)
}

function formatDate(d) {
    if (!d) return '-'
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB')
}

function openStatusChange(status) {
    newStatus.value = status
    deliveryNotes.value = ''
    showStatusModal.value = true
}

function submitStatusChange() {
    processing.value = true
    router.put(`/admin/purchase-orders/${props.order.id}/status`, {
        status: newStatus.value,
        delivery_notes: deliveryNotes.value || undefined,
    }, {
        onFinish: () => { processing.value = false; showStatusModal.value = false },
    })
}

function openReceive() {
    receiveItems.value = props.order.items.map(item => ({
        id: item.id,
        name: isRtl.value ? item.supply?.name_ar : item.supply?.name_en,
        sku: item.supply?.sku,
        unit: item.supply?.unit,
        quantity_ordered: parseFloat(item.quantity_ordered),
        quantity_received: parseFloat(item.quantity_received || 0),
        new_received: parseFloat(item.quantity_received || 0),
        remaining: parseFloat(item.quantity_ordered) - parseFloat(item.quantity_received || 0),
        batch_number: item.batch_number || '',
        expiry_date: item.expiry_date ? item.expiry_date.split('T')[0] : '',
    }))
    showReceiveModal.value = true
}

function receiveAll() {
    receiveItems.value.forEach(item => {
        item.new_received = item.quantity_ordered
    })
}

function submitReceive() {
    processing.value = true
    router.post(`/admin/purchase-orders/${props.order.id}/receive`, {
        items: receiveItems.value.map(i => ({
            id: i.id,
            quantity_received: i.new_received,
            batch_number: i.batch_number || null,
            expiry_date: i.expiry_date || null,
        })),
    }, {
        onFinish: () => { processing.value = false; showReceiveModal.value = false },
    })
}

const receivedPercentage = computed(() => {
    if (!props.order.items?.length) return 0
    const totalOrdered = props.order.items.reduce((s, i) => s + parseFloat(i.quantity_ordered), 0)
    const totalReceived = props.order.items.reduce((s, i) => s + parseFloat(i.quantity_received || 0), 0)
    return totalOrdered > 0 ? Math.round((totalReceived / totalOrdered) * 100) : 0
})
</script>

<template>
    <div class="p-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <Link href="/admin/purchase-orders" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                    <svg class="w-5 h-5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-800 font-mono">{{ order.po_number }}</h1>
                        <span :class="statusLabel(order.status).color" class="px-3 py-1 rounded-full text-xs font-medium">{{ statusLabel(order.status).text }}</span>
                    </div>
                    <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'تاريخ الأمر:' : 'Order Date:' }} {{ formatDate(order.order_date) }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Receive Items Button -->
                <button v-if="['ordered', 'partially_received'].includes(order.status)" @click="openReceive"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ isRtl ? 'استلام أصناف' : 'Receive Items' }}
                </button>
                <!-- Status Actions -->
                <button v-for="ns in nextStatuses" :key="ns" @click="openStatusChange(ns)"
                    :class="ns === 'cancelled' ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-cyan-200 text-cyan-700 hover:bg-cyan-50'"
                    class="px-4 py-2.5 border rounded-xl text-sm font-medium transition">
                    {{ statusLabel(ns).text }}
                </button>
            </div>
        </div>

        <!-- Order Info + Progress -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Supplier Info -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">{{ isRtl ? 'المورد' : 'Supplier' }}</h3>
                <p class="font-medium text-gray-800">{{ isRtl ? order.supplier?.name_ar : order.supplier?.name_en }}</p>
                <p v-if="order.supplier?.phone" class="text-sm text-gray-500 mt-1">{{ order.supplier.phone }}</p>
                <p v-if="order.supplier?.email" class="text-sm text-gray-400">{{ order.supplier.email }}</p>
                <p v-if="order.supplier?.contact_person" class="text-sm text-gray-400 mt-1">{{ order.supplier.contact_person }}</p>
            </div>

            <!-- Financials -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">{{ isRtl ? 'المبالغ' : 'Financials' }}</h3>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ isRtl ? 'المبلغ الفرعي' : 'Subtotal' }}</span>
                        <span class="text-gray-700">{{ formatCurrency(order.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ isRtl ? 'الضريبة' : 'Tax' }}</span>
                        <span class="text-gray-700">{{ formatCurrency(order.tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold pt-2 border-t">
                        <span class="text-gray-700">{{ isRtl ? 'الإجمالي' : 'Total' }}</span>
                        <span class="text-cyan-700">{{ formatCurrency(order.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Delivery Progress -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-3">{{ isRtl ? 'تقدم الاستلام' : 'Receiving Progress' }}</h3>
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500" :class="receivedPercentage === 100 ? 'bg-green-500' : receivedPercentage > 0 ? 'bg-cyan-500' : 'bg-gray-200'" :style="{ width: receivedPercentage + '%' }"></div>
                    </div>
                    <span class="text-sm font-bold" :class="receivedPercentage === 100 ? 'text-green-600' : 'text-gray-700'">{{ receivedPercentage }}%</span>
                </div>
                <div class="space-y-1 text-xs text-gray-500">
                    <div v-if="order.expected_delivery_date" class="flex justify-between">
                        <span>{{ isRtl ? 'التسليم المتوقع' : 'Expected' }}</span>
                        <span :class="new Date(order.expected_delivery_date) < new Date() && receivedPercentage < 100 ? 'text-red-600 font-medium' : ''">{{ formatDate(order.expected_delivery_date) }}</span>
                    </div>
                    <div v-if="order.received_date" class="flex justify-between">
                        <span>{{ isRtl ? 'تاريخ الاستلام' : 'Received' }}</span>
                        <span class="text-green-600 font-medium">{{ formatDate(order.received_date) }}</span>
                    </div>
                    <div v-if="order.creator" class="flex justify-between">
                        <span>{{ isRtl ? 'أنشأ بواسطة' : 'Created by' }}</span>
                        <span>{{ order.creator.name }}</span>
                    </div>
                    <div v-if="order.approver" class="flex justify-between">
                        <span>{{ isRtl ? 'اعتمد بواسطة' : 'Approved by' }}</span>
                        <span>{{ order.approver.name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div v-if="order.notes || order.delivery_notes" class="bg-yellow-50 border border-yellow-100 rounded-2xl p-4 mb-6">
            <p v-if="order.notes" class="text-sm text-yellow-800"><strong>{{ isRtl ? 'ملاحظات:' : 'Notes:' }}</strong> {{ order.notes }}</p>
            <p v-if="order.delivery_notes" class="text-sm text-yellow-800 mt-1"><strong>{{ isRtl ? 'ملاحظات التسليم:' : 'Delivery Notes:' }}</strong> {{ order.delivery_notes }}</p>
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">{{ isRtl ? 'الأصناف' : 'Items' }} ({{ order.items?.length || 0 }})</h3>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'الصنف' : 'Item' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الكمية المطلوبة' : 'Ordered' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'المستلم' : 'Received' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'المتبقي' : 'Remaining' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'سعر الوحدة' : 'Unit Price' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'رقم الدفعة' : 'Batch' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'انتهاء الصلاحية' : 'Expiry' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50/50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-700">{{ isRtl ? item.supply?.name_ar : item.supply?.name_en }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ item.supply?.sku }}</div>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ parseFloat(item.quantity_ordered) }} {{ item.supply?.unit }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="parseFloat(item.quantity_received) >= parseFloat(item.quantity_ordered) ? 'text-green-600 font-medium' : parseFloat(item.quantity_received) > 0 ? 'text-orange-600' : 'text-gray-400'">
                                {{ parseFloat(item.quantity_received || 0) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="item.remaining_quantity > 0 ? 'text-red-600 font-medium' : 'text-green-600'">
                                {{ item.remaining_quantity }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ formatCurrency(item.unit_price) }}</td>
                        <td class="px-4 py-3 text-center font-medium text-gray-800">{{ formatCurrency(item.total_price) }}</td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500 font-mono">{{ item.batch_number || '-' }}</td>
                        <td class="px-4 py-3 text-center text-xs" :class="item.expiry_date && new Date(item.expiry_date) < new Date() ? 'text-red-600 font-medium' : 'text-gray-500'">
                            {{ item.expiry_date ? formatDate(item.expiry_date) : '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Status Change Modal -->
        <Teleport to="body">
            <div v-if="showStatusModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showStatusModal = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">
                        {{ isRtl ? 'تغيير الحالة' : 'Change Status' }}
                    </h2>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">
                            {{ isRtl ? 'تغيير الحالة إلى:' : 'Change status to:' }}
                            <span :class="statusLabel(newStatus).color" class="px-2.5 py-0.5 rounded-full text-xs font-medium ms-1">{{ statusLabel(newStatus).text }}</span>
                        </p>
                    </div>
                    <div v-if="['received', 'partially_received'].includes(newStatus)" class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات التسليم' : 'Delivery Notes' }}</label>
                        <textarea v-model="deliveryNotes" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"></textarea>
                    </div>
                    <div v-if="newStatus === 'cancelled'" class="bg-red-50 border border-red-100 rounded-xl p-3 mb-4">
                        <p class="text-sm text-red-700">{{ isRtl ? 'هل أنت متأكد من إلغاء أمر الشراء؟ لا يمكن التراجع عن هذا.' : 'Are you sure you want to cancel this PO? This cannot be undone.' }}</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button @click="showStatusModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button @click="submitStatusChange" :disabled="processing"
                            :class="newStatus === 'cancelled' ? 'bg-red-600 hover:bg-red-700' : 'bg-cyan-600 hover:bg-cyan-700'"
                            class="px-5 py-2.5 text-white rounded-xl text-sm font-medium transition disabled:opacity-50">
                            {{ isRtl ? 'تأكيد' : 'Confirm' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Receive Items Modal -->
        <Teleport to="body">
            <div v-if="showReceiveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showReceiveModal = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-lg font-bold text-gray-800">{{ isRtl ? 'استلام الأصناف' : 'Receive Items' }}</h2>
                        <button @click="receiveAll" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium">
                            {{ isRtl ? 'استلام الكل' : 'Receive All' }}
                        </button>
                    </div>

                    <table class="w-full text-sm mb-5">
                        <thead>
                            <tr class="text-gray-500 text-xs uppercase border-b">
                                <th class="px-3 py-2 text-start">{{ isRtl ? 'الصنف' : 'Item' }}</th>
                                <th class="px-3 py-2 text-center">{{ isRtl ? 'مطلوب' : 'Ordered' }}</th>
                                <th class="px-3 py-2 text-center">{{ isRtl ? 'سابق' : 'Prev.' }}</th>
                                <th class="px-3 py-2 text-center w-28">{{ isRtl ? 'الكمية المستلمة' : 'Received Qty' }}</th>
                                <th class="px-3 py-2 text-center">{{ isRtl ? 'رقم الدفعة' : 'Batch #' }}</th>
                                <th class="px-3 py-2 text-center">{{ isRtl ? 'انتهاء الصلاحية' : 'Expiry' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in receiveItems" :key="item.id">
                                <td class="px-3 py-2.5">
                                    <div class="font-medium text-gray-700">{{ item.name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ item.sku }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-center text-gray-600">{{ item.quantity_ordered }} {{ item.unit }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-400">{{ item.quantity_received }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    <input v-model.number="item.new_received" type="number" :min="0" :max="item.quantity_ordered" step="0.01" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-center focus:ring-cyan-500 focus:border-cyan-500" />
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <input v-model="item.batch_number" type="text" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-center" :placeholder="isRtl ? 'اختياري' : 'Optional'" />
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <input v-model="item.expiry_date" type="date" class="w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm" />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button @click="showReceiveModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button @click="submitReceive" :disabled="processing" class="px-5 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 text-sm font-medium transition disabled:opacity-50">
                            {{ isRtl ? 'تأكيد الاستلام' : 'Confirm Receiving' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
