<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { useCurrency } from '@/Composables/useCurrency.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    claims: Object,
    stats: Object,
    companies: Array,
    filters: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')
const { formatCurrency } = useCurrency()

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const companyFilter = ref(props.filters?.company_id || '')
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

function applyFilters() {
    router.get('/admin/insurance/claims', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        company_id: companyFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true })
}

const statusLabels = {
    draft: { ar: 'مسودة', en: 'Draft', color: 'bg-gray-100 text-gray-700' },
    submitted: { ar: 'مقدم', en: 'Submitted', color: 'bg-blue-100 text-blue-700' },
    under_review: { ar: 'قيد المراجعة', en: 'Under Review', color: 'bg-yellow-100 text-yellow-700' },
    approved: { ar: 'معتمد', en: 'Approved', color: 'bg-green-100 text-green-700' },
    partially_approved: { ar: 'معتمد جزئياً', en: 'Partially Approved', color: 'bg-lime-100 text-lime-700' },
    rejected: { ar: 'مرفوض', en: 'Rejected', color: 'bg-red-100 text-red-700' },
    paid: { ar: 'مدفوع', en: 'Paid', color: 'bg-emerald-100 text-emerald-700' },
    partially_paid: { ar: 'مدفوع جزئياً', en: 'Partially Paid', color: 'bg-teal-100 text-teal-700' },
}

// Status update
const updatingClaim = ref(null)
const statusForm = ref({ status: '', approved_amount: null, paid_amount: null, rejection_reason: '', reference_number: '' })

function openStatusUpdate(claim) {
    updatingClaim.value = claim
    statusForm.value = { status: '', approved_amount: claim.approved_amount, paid_amount: claim.paid_amount, rejection_reason: '', reference_number: claim.reference_number || '' }
}

function submitStatusUpdate() {
    router.post(`/admin/insurance/claims/${updatingClaim.value.id}/status`, statusForm.value, {
        onSuccess: () => { updatingClaim.value = null }
    })
}
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'مطالبات التأمين' : 'Insurance Claims' }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'إدارة ومتابعة مطالبات التأمين' : 'Manage and track insurance claims' }}</p>
            </div>
            <Link href="/admin/insurance/companies" class="inline-flex items-center gap-2 px-4 py-2 text-[#1B365D] bg-[#1B365D]/5 rounded-xl hover:bg-cyan-100 transition text-sm font-medium">
                {{ isRtl ? 'شركات التأمين' : 'Companies' }}
            </Link>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</p>
                <p class="text-xl font-bold text-amber-600 mt-1">{{ stats.pending_count }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400">{{ isRtl ? 'مبلغ الانتظار' : 'Pending Amount' }}</p>
                <p class="text-xl font-bold text-blue-600 mt-1">{{ formatCurrency(stats.pending_amount) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400">{{ isRtl ? 'غير محصل' : 'Unpaid' }}</p>
                <p class="text-xl font-bold text-red-600 mt-1">{{ formatCurrency(stats.unpaid_amount) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400">{{ isRtl ? 'مقدم هذا الشهر' : 'Submitted (Month)' }}</p>
                <p class="text-xl font-bold text-[#1B365D] mt-1">{{ stats.this_month_submitted }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400">{{ isRtl ? 'محصل هذا الشهر' : 'Collected (Month)' }}</p>
                <p class="text-xl font-bold text-green-600 mt-1">{{ formatCurrency(stats.this_month_paid) }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <p class="text-xs text-gray-400">{{ isRtl ? 'نسبة الرفض' : 'Rejection Rate' }}</p>
                <p class="text-xl font-bold mt-1" :class="stats.rejection_rate > 15 ? 'text-red-600' : 'text-gray-600'">{{ stats.rejection_rate }}%</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6 bg-white rounded-2xl border border-gray-100 p-4">
            <input v-model="search" @keyup.enter="applyFilters" type="text" :placeholder="isRtl ? 'رقم المطالبة أو المريض...' : 'Claim # or patient...'" class="px-4 py-2 border border-gray-200 rounded-xl text-sm w-56 focus:ring-[#C4A265] focus:border-[#C4A265]" />
            <select v-model="statusFilter" @change="applyFilters" class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'كل الحالات' : 'All Status' }}</option>
                <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ isRtl ? label.ar : label.en }}</option>
            </select>
            <select v-model="companyFilter" @change="applyFilters" class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'كل الشركات' : 'All Companies' }}</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ isRtl ? c.name_ar : c.name_en }}</option>
            </select>
            <input v-model="dateFrom" @change="applyFilters" type="date" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
            <input v-model="dateTo" @change="applyFilters" type="date" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
        </div>

        <!-- Claims Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <th class="px-4 py-3 text-start">{{ isRtl ? 'رقم المطالبة' : 'Claim #' }}</th>
                            <th class="px-4 py-3 text-start">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-4 py-3 text-start">{{ isRtl ? 'الشركة' : 'Company' }}</th>
                            <th class="px-4 py-3 text-start">{{ isRtl ? 'تاريخ الخدمة' : 'Service Date' }}</th>
                            <th class="px-4 py-3 text-end">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                            <th class="px-4 py-3 text-end">{{ isRtl ? 'التغطية' : 'Covered' }}</th>
                            <th class="px-4 py-3 text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-4 py-3 text-center">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="claim in claims.data" :key="claim.id" class="hover:bg-gray-50/50 transition">
                            <td class="px-4 py-3 font-mono text-xs font-medium text-gray-700">{{ claim.claim_number }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ claim.patient?.full_name }}</div>
                                <div class="text-xs text-gray-400">{{ claim.patient?.file_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ claim.patient_insurance?.company ? (isRtl ? claim.patient_insurance.company.name_ar : claim.patient_insurance.company.name_en) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ claim.service_date }}</td>
                            <td class="px-4 py-3 text-end font-medium text-gray-800">{{ formatCurrency(claim.total_amount) }}</td>
                            <td class="px-4 py-3 text-end font-medium text-green-600">{{ formatCurrency(claim.covered_amount) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="statusLabels[claim.status]?.color || 'bg-gray-100 text-gray-600'" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ isRtl ? statusLabels[claim.status]?.ar : statusLabels[claim.status]?.en }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="openStatusUpdate(claim)" class="text-[#1B365D] hover:text-cyan-800 text-xs font-medium">
                                    {{ isRtl ? 'تحديث' : 'Update' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="claims.data.length === 0" class="text-center py-12 text-gray-400">
                {{ isRtl ? 'لا توجد مطالبات' : 'No claims found' }}
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="claims.links && claims.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in claims.links" :key="link.label" :href="link.url || '#'"
                class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-[#1B365D] text-white' : 'text-gray-500 hover:bg-gray-100'"
                v-html="link.label" preserve-state />
        </div>

        <!-- Status Update Modal -->
        <Teleport to="body">
            <div v-if="updatingClaim" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="updatingClaim = null" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">
                        {{ isRtl ? 'تحديث حالة المطالبة' : 'Update Claim Status' }}
                        <span class="text-sm font-mono text-gray-400 block mt-1">{{ updatingClaim.claim_number }}</span>
                    </h2>

                    <form @submit.prevent="submitStatusUpdate" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الحالة الجديدة' : 'New Status' }} *</label>
                            <select v-model="statusForm.status" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                                <option value="">{{ isRtl ? 'اختر...' : 'Select...' }}</option>
                                <option value="submitted">{{ isRtl ? 'مقدم' : 'Submitted' }}</option>
                                <option value="under_review">{{ isRtl ? 'قيد المراجعة' : 'Under Review' }}</option>
                                <option value="approved">{{ isRtl ? 'معتمد' : 'Approved' }}</option>
                                <option value="partially_approved">{{ isRtl ? 'معتمد جزئياً' : 'Partially Approved' }}</option>
                                <option value="rejected">{{ isRtl ? 'مرفوض' : 'Rejected' }}</option>
                                <option value="paid">{{ isRtl ? 'مدفوع' : 'Paid' }}</option>
                                <option value="partially_paid">{{ isRtl ? 'مدفوع جزئياً' : 'Partially Paid' }}</option>
                            </select>
                        </div>

                        <div v-if="['approved', 'partially_approved'].includes(statusForm.status)">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المبلغ المعتمد' : 'Approved Amount' }}</label>
                            <input v-model="statusForm.approved_amount" type="number" step="0.01" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        </div>

                        <div v-if="['paid', 'partially_paid'].includes(statusForm.status)">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المبلغ المحصل' : 'Paid Amount' }}</label>
                            <input v-model="statusForm.paid_amount" type="number" step="0.01" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        </div>

                        <div v-if="statusForm.status === 'rejected'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'سبب الرفض' : 'Rejection Reason' }}</label>
                            <textarea v-model="statusForm.rejection_reason" rows="2" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'رقم المرجع' : 'Reference Number' }}</label>
                            <input v-model="statusForm.reference_number" type="text" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <button type="button" @click="updatingClaim = null" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl transition text-sm font-medium">
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#142849] transition text-sm font-medium">
                                {{ isRtl ? 'تحديث' : 'Update' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
