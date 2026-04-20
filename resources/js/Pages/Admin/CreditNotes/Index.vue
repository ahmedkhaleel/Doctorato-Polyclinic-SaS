<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { useCurrency } from '@/Composables/useCurrency.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({ creditNotes: Object, stats: Object, filters: Object })

const { formatCurrency } = useCurrency()
const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const modules = computed(() => page.props.modules || {})
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false && m.is_medical !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }))
})

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const typeFilter = ref(props.filters?.type || '')
const moduleFilter = ref(props.filters?.module || '')

const showCreateModal = ref(false)
const showStatusModal = ref(false)
const selectedNote = ref(null)
const submitting = ref(false)

// Search invoice for refund
const invoiceSearch = ref('')
const invoiceResults = ref([])
const selectedInvoice = ref(null)
let invoiceSearchTimeout = null

function searchInvoices() {
    clearTimeout(invoiceSearchTimeout)
    if (invoiceSearch.value.length < 2) { invoiceResults.value = []; return }
    invoiceSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`/admin/api/global-search?q=${encodeURIComponent(invoiceSearch.value)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            if (res.ok) {
                const data = await res.json()
                invoiceResults.value = (data.results || []).filter(r => r.type === 'invoice')
            }
        } catch (e) { console.error(e) }
    }, 300)
}

const createForm = ref({ invoice_id: '', type: 'partial_refund', amount: '', reason: '', notes: '' })

function selectInvoice(inv) {
    selectedInvoice.value = inv
    createForm.value.invoice_id = inv.id
    invoiceSearch.value = ''
    invoiceResults.value = []
}

function submitCreate() {
    submitting.value = true
    router.post('/admin/credit-notes', createForm.value, {
        onFinish: () => { submitting.value = false },
        onSuccess: () => {
            showCreateModal.value = false
            createForm.value = { invoice_id: '', type: 'partial_refund', amount: '', reason: '', notes: '' }
            selectedInvoice.value = null
        },
    })
}

const statusLabels = {
    draft: { ar: 'مسودة', en: 'Draft', color: 'bg-gray-100 text-gray-700' },
    pending_approval: { ar: 'بانتظار الموافقة', en: 'Pending Approval', color: 'bg-amber-100 text-amber-700' },
    approved: { ar: 'معتمد', en: 'Approved', color: 'bg-slate-100 text-[#1B365D]' },
    rejected: { ar: 'مرفوض', en: 'Rejected', color: 'bg-red-100 text-red-700' },
    refunded: { ar: 'تم الاسترداد', en: 'Refunded', color: 'bg-emerald-100 text-emerald-700' },
}

const typeLabels = {
    full_refund: { ar: 'استرداد كامل', en: 'Full Refund' },
    partial_refund: { ar: 'استرداد جزئي', en: 'Partial Refund' },
    adjustment: { ar: 'تعديل', en: 'Adjustment' },
    cancellation: { ar: 'إلغاء', en: 'Cancellation' },
}

const allowedTransitions = {
    draft: ['pending_approval', 'approved'],
    pending_approval: ['approved', 'rejected'],
    approved: ['refunded'],
}

const statusForm = ref({ status: '', refund_method: 'cash', notes: '' })

function openStatusChange(note) {
    selectedNote.value = note
    statusForm.value = { status: '', refund_method: 'cash', notes: '' }
    showStatusModal.value = true
}

function submitStatusChange() {
    if (!selectedNote.value || !statusForm.value.status) return
    submitting.value = true
    router.post(`/admin/credit-notes/${selectedNote.value.id}/status`, statusForm.value, {
        onFinish: () => { submitting.value = false },
        onSuccess: () => { showStatusModal.value = false },
    })
}

function applyFilters() {
    router.get('/admin/credit-notes', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        type: typeFilter.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function formatDate(d) {
    if (!d) return '-'
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB')
}
</script>

<template>
    <div class="p-4 md:p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ isRtl ? 'إشعارات دائنة / استرداد' : 'Credit Notes / Refunds' }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'إدارة المبالغ المستردة والتعديلات' : 'Manage refunds, adjustments & cancellations' }}</p>
            </div>
            <button @click="showCreateModal = true" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#1B365D] transition font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ isRtl ? 'إشعار دائن جديد' : 'New Credit Note' }}
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</p>
                <p class="text-xl font-bold text-amber-600 mt-1">{{ stats.pending }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'مبلغ معلق' : 'Pending Amount' }}</p>
                <p class="text-xl font-bold text-gray-800 mt-1">{{ formatCurrency(stats.pending_amount) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'معتمد هذا الشهر' : 'Approved (Month)' }}</p>
                <p class="text-xl font-bold text-[#1B365D] mt-1">{{ stats.approved_month }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-500">{{ isRtl ? 'مسترد هذا الشهر' : 'Refunded (Month)' }}</p>
                <p class="text-xl font-bold text-red-600 mt-1">{{ formatCurrency(stats.refunded_month) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <input v-model="search" @keyup.enter="applyFilters" type="text" :placeholder="isRtl ? 'بحث...' : 'Search...'" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm w-64 focus:ring-[#1B365D] focus:border-[#1B365D]" />
            <select v-model="statusFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ isRtl ? 'كل الحالات' : 'All Statuses' }}</option>
                <option v-for="(l, k) in statusLabels" :key="k" :value="k">{{ isRtl ? l.ar : l.en }}</option>
            </select>
            <select v-model="typeFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ isRtl ? 'كل الأنواع' : 'All Types' }}</option>
                <option v-for="(l, k) in typeLabels" :key="k" :value="k">{{ isRtl ? l.ar : l.en }}</option>
            </select>
            <select v-if="activeModules.length> 1" v-model="moduleFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'الرقم' : 'CN #' }}</th>
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الفاتورة' : 'Invoice' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'النوع' : 'Type' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'السبب' : 'Reason' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="cn in creditNotes.data" :key="cn.id" class="hover:bg-gray-50/50">
                        <td class="px-4 py-3"><span class="font-mono text-xs font-medium text-[#1B365D]">{{ cn.credit_note_number }}</span></td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ cn.patient?.full_name }}</div>
                            <div class="text-xs text-gray-400">{{ cn.patient?.file_number }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Link v-if="cn.invoice" :href="`/admin/invoices/${cn.invoice.id}`" class="font-mono text-xs text-[#1B365D] hover:underline">{{ cn.invoice.invoice_number }}</Link>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-600">{{ isRtl ? typeLabels[cn.type]?.ar : typeLabels[cn.type]?.en }}</td>
                        <td class="px-4 py-3 text-center font-bold text-red-600">{{ formatCurrency(cn.amount) }}</td>
                        <td class="px-4 py-3 text-xs text-gray-600 max-w-[200px] truncate">{{ cn.reason }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="statusLabels[cn.status]?.color" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ isRtl ? statusLabels[cn.status]?.ar : statusLabels[cn.status]?.en }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ formatDate(cn.created_at) }}</td>
                        <td class="px-4 py-3 text-center">
                            <button v-if="allowedTransitions[cn.status]?.length" @click="openStatusChange(cn)" class="p-1.5 text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="creditNotes.data.length === 0" class="text-center py-12 text-gray-400">{{ isRtl ? 'لا توجد إشعارات دائنة' : 'No credit notes found' }}</div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showCreateModal = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-5">{{ isRtl ? 'إشعار دائن جديد' : 'New Credit Note' }}</h2>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <!-- Invoice Search -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الفاتورة' : 'Invoice' }} *</label>
                            <div v-if="selectedInvoice" class="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl">
                                <span class="text-sm font-mono text-gray-800">{{ selectedInvoice.title }}</span>
                                <button type="button" @click="selectedInvoice = null; createForm.invoice_id = ''" class="text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </div>
                            <div v-else class="relative">
                                <input v-model="invoiceSearch" @input="searchInvoices" type="text" :placeholder="isRtl ? 'ابحث برقم الفاتورة...' : 'Search invoice number...'" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                                <div v-if="invoiceResults.length" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-40 overflow-y-auto">
                                    <button v-for="inv in invoiceResults" :key="inv.id" type="button" @click="selectInvoice(inv)" class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 text-start">
                                        <span class="text-sm font-mono text-gray-700">{{ inv.title }}</span>
                                        <span class="text-xs text-gray-400">{{ inv.subtitle }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النوع' : 'Type' }} *</label>
                                <select v-model="createForm.type" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                    <option v-for="(l, k) in typeLabels" :key="k" :value="k">{{ isRtl ? l.ar : l.en }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المبلغ' : 'Amount' }} *</label>
                                <input v-model.number="createForm.amount" type="number" min="0.01" step="0.01" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'السبب' : 'Reason' }} *</label>
                            <input v-model="createForm.reason" required type="text" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" :placeholder="isRtl ? 'سبب الاسترداد...' : 'Reason for refund...'" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="createForm.notes" rows="2" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                            <button type="submit" :disabled="submitting || !createForm.invoice_id" class="px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#1B365D] text-sm font-medium disabled:opacity-50">
                                {{ isRtl ? 'إنشاء' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Status Modal -->
        <Teleport to="body">
            <div v-if="showStatusModal && selectedNote" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showStatusModal = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">{{ isRtl ? 'تحديث الحالة' : 'Update Status' }}</h2>
                    <p class="text-sm text-gray-500 mb-4">{{ selectedNote.credit_note_number }} — {{ formatCurrency(selectedNote.amount) }}</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الحالة' : 'Status' }} *</label>
                            <select v-model="statusForm.status" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option value="">{{ isRtl ? 'اختر' : 'Select' }}</option>
                                <option v-for="s in (allowedTransitions[selectedNote.status] || [])" :key="s" :value="s">{{ isRtl ? statusLabels[s]?.ar : statusLabels[s]?.en }}</option>
                            </select>
                        </div>

                        <div v-if="statusForm.status === 'refunded'">
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'طريقة الاسترداد' : 'Refund Method' }}</label>
                            <select v-model="statusForm.refund_method" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option value="cash">{{ isRtl ? 'نقدي' : 'Cash' }}</option>
                                <option value="card">{{ isRtl ? 'بطاقة' : 'Card' }}</option>
                                <option value="bank_transfer">{{ isRtl ? 'تحويل بنكي' : 'Bank Transfer' }}</option>
                                <option value="wallet_credit">{{ isRtl ? 'رصيد محفظة' : 'Wallet Credit' }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="statusForm.notes" rows="2" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 mt-4 border-t">
                        <button @click="showStatusModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button @click="submitStatusChange" :disabled="submitting || !statusForm.status"
                            :class="statusForm.status === 'rejected' ? 'bg-red-600 hover:bg-red-700' : 'bg-[#1B365D] hover:bg-[#1B365D]'"
                            class="px-5 py-2.5 text-white rounded-xl text-sm font-medium disabled:opacity-50">
                            {{ isRtl ? 'تحديث' : 'Update' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
