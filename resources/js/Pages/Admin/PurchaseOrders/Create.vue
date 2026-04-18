<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, watch } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ suppliers: Array, supplies: Array })

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const form = ref({
    supplier_id: '',
    expected_delivery_date: '',
    notes: '',
    items: [],
})

const submitting = ref(false)
const searchSupply = ref('')

const selectedSupplier = computed(() =>
    props.suppliers.find(s => s.id == form.value.supplier_id)
)

// Filter supplies by supplier and search term
const filteredSupplies = computed(() => {
    let list = props.supplies
    if (form.value.supplier_id) {
        // Show supplier's items first, then all others
        list = [
            ...list.filter(s => s.supplier_id == form.value.supplier_id),
            ...list.filter(s => s.supplier_id != form.value.supplier_id),
        ]
    }
    if (searchSupply.value) {
        const q = searchSupply.value.toLowerCase()
        list = list.filter(s =>
            s.name_ar?.toLowerCase().includes(q) ||
            s.name_en?.toLowerCase().includes(q) ||
            s.sku?.toLowerCase().includes(q)
        )
    }
    return list
})

// Auto-set expected delivery date based on supplier lead time
watch(() => form.value.supplier_id, (newVal) => {
    if (newVal) {
        const supplier = props.suppliers.find(s => s.id == newVal)
        if (supplier?.lead_time_days) {
            const d = new Date()
            d.setDate(d.getDate() + supplier.lead_time_days)
            form.value.expected_delivery_date = d.toISOString().split('T')[0]
        }
    }
})

function addItem(supply) {
    if (form.value.items.find(i => i.supply_id === supply.id)) return
    form.value.items.push({
        supply_id: supply.id,
        name: isRtl.value ? supply.name_ar : supply.name_en,
        sku: supply.sku,
        unit: supply.unit,
        current_stock: supply.quantity,
        min_quantity: supply.min_quantity,
        quantity_ordered: supply.min_quantity ? Math.max(1, supply.min_quantity - supply.quantity) : 1,
        unit_price: supply.purchase_price || 0,
    })
    searchSupply.value = ''
}

function removeItem(index) {
    form.value.items.splice(index, 1)
}

const subtotal = computed(() =>
    form.value.items.reduce((sum, i) => sum + (i.quantity_ordered * i.unit_price), 0)
)

function formatCurrency(amount) {
    return new Intl.NumberFormat(isRtl.value ? 'ar-EG' : 'en-EG', { minimumFractionDigits: 2 }).format(amount || 0)
}

function submit() {
    if (form.value.items.length === 0 || !form.value.supplier_id) return
    submitting.value = true
    router.post('/admin/purchase-orders', {
        supplier_id: form.value.supplier_id,
        expected_delivery_date: form.value.expected_delivery_date || null,
        notes: form.value.notes || null,
        items: form.value.items.map(i => ({
            supply_id: i.supply_id,
            quantity_ordered: i.quantity_ordered,
            unit_price: i.unit_price,
        })),
    }, {
        onFinish: () => { submitting.value = false },
    })
}
</script>

<template>
    <div class="p-4 md:p-6 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link href="/admin/purchase-orders" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                <svg class="w-5 h-5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ isRtl ? 'أمر شراء جديد' : 'New Purchase Order' }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'إنشاء أمر شراء جديد للمورد' : 'Create a new purchase order for a supplier' }}</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Order Details Card -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ isRtl ? 'تفاصيل الأمر' : 'Order Details' }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المورد' : 'Supplier' }} *</label>
                        <select v-model="form.supplier_id" required class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#1B365D] focus:border-[#1B365D]">
                            <option value="">{{ isRtl ? 'اختر المورد' : 'Select Supplier' }}</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ isRtl ? s.name_ar : s.name_en }}</option>
                        </select>
                        <p v-if="selectedSupplier?.lead_time_days" class="text-xs text-gray-400 mt-1">
                            {{ isRtl ? `مدة التوصيل: ${selectedSupplier.lead_time_days} يوم` : `Lead time: ${selectedSupplier.lead_time_days} days` }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'تاريخ التسليم المتوقع' : 'Expected Delivery' }}</label>
                        <input v-model="form.expected_delivery_date" type="date" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#1B365D] focus:border-[#1B365D]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                        <input v-model="form.notes" type="text" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#1B365D] focus:border-[#1B365D]" :placeholder="isRtl ? 'ملاحظات اختيارية...' : 'Optional notes...'" />
                    </div>
                </div>
            </div>

            <!-- Add Items -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ isRtl ? 'إضافة أصناف' : 'Add Items' }}</h2>

                <!-- Search supplies -->
                <div class="relative mb-4">
                    <input v-model="searchSupply" type="text" :placeholder="isRtl ? 'ابحث عن صنف بالاسم أو الرمز...' : 'Search item by name or SKU...'" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#1B365D] focus:border-[#1B365D] ps-10" />
                    <svg class="w-4 h-4 text-gray-400 absolute top-3 start-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Supply list (limited) -->
                <div v-if="searchSupply" class="max-h-48 overflow-y-auto border border-gray-100 rounded-xl mb-4 divide-y divide-gray-50">
                    <button v-for="supply in filteredSupplies.slice(0, 10)" :key="supply.id" type="button" @click="addItem(supply)"
                        class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-slate-50 transition text-start"
                        :class="form.items.find(i => i.supply_id === supply.id) ? 'bg-gray-50 opacity-50' : ''">
                        <div>
                            <div class="text-sm font-medium text-gray-700">{{ isRtl ? supply.name_ar : supply.name_en }}</div>
                            <div class="text-xs text-gray-400">{{ supply.sku }} · {{ isRtl ? 'مخزون:' : 'Stock:' }} {{ supply.quantity }} {{ supply.unit }}</div>
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ supply.purchase_price ? formatCurrency(supply.purchase_price) : '-' }}
                        </div>
                    </button>
                    <div v-if="filteredSupplies.length === 0" class="px-4 py-3 text-center text-gray-400 text-sm">
                        {{ isRtl ? 'لا توجد نتائج' : 'No results' }}
                    </div>
                </div>

                <!-- Items Table -->
                <div v-if="form.items.length > 0" class="border border-gray-100 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <th class="px-4 py-2.5 text-start">{{ isRtl ? 'الصنف' : 'Item' }}</th>
                                <th class="px-4 py-2.5 text-center">{{ isRtl ? 'المخزون الحالي' : 'Current Stock' }}</th>
                                <th class="px-4 py-2.5 text-center w-28">{{ isRtl ? 'الكمية' : 'Qty' }}</th>
                                <th class="px-4 py-2.5 text-center w-32">{{ isRtl ? 'سعر الوحدة' : 'Unit Price' }}</th>
                                <th class="px-4 py-2.5 text-center">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                                <th class="px-4 py-2.5 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(item, idx) in form.items" :key="item.supply_id">
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-gray-700">{{ item.name }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ item.sku }}</div>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span :class="item.current_stock <= (item.min_quantity || 0) ? 'text-red-600 font-medium' : 'text-gray-600'">
                                        {{ item.current_stock }} {{ item.unit }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <input v-model.number="item.quantity_ordered" type="number" min="0.01" step="0.01" required class="doctorato-input w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-center focus:ring-[#1B365D] focus:border-[#1B365D]" />
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <input v-model.number="item.unit_price" type="number" min="0" step="0.01" required class="doctorato-input w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm text-center focus:ring-[#1B365D] focus:border-[#1B365D]" />
                                </td>
                                <td class="px-4 py-2.5 text-center font-medium text-gray-800">
                                    {{ formatCurrency(item.quantity_ordered * item.unit_price) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <button type="button" @click="removeItem(idx)" class="p-1 text-gray-400 hover:text-red-600 rounded transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50">
                                <td colspan="4" class="px-4 py-3 text-end font-semibold text-gray-700">{{ isRtl ? 'الإجمالي' : 'Subtotal' }}</td>
                                <td class="px-4 py-3 text-center font-bold text-[#1B365D] text-base">{{ formatCurrency(subtotal) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-else class="text-center py-8 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p class="text-sm">{{ isRtl ? 'ابحث وأضف أصناف لأمر الشراء' : 'Search and add items to the purchase order' }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link href="/admin/purchase-orders" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium transition">
                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                </Link>
                <button type="submit" :disabled="submitting || form.items.length === 0 || !form.supplier_id"
                    class="px-4 md:px-6 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#1B365D] text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                    <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    {{ isRtl ? 'إنشاء أمر الشراء' : 'Create Purchase Order' }}
                </button>
            </div>
        </form>
    </div>
</template>
