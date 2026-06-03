<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ referrals: Object, stats: Object, doctors: Array, filters: Object })

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const deptFilter = ref(props.filters?.department || '')

const showCreateModal = ref(false)
const showStatusModal = ref(false)
const selectedReferral = ref(null)

const departments = [
    { value: 'dermatology', ar: 'الجلدية', en: 'Dermatology' },
    { value: 'dental', ar: 'الأسنان', en: 'Dental' },
    { value: 'general', ar: 'عام', en: 'General' },
    { value: 'cosmetic', ar: 'التجميل', en: 'Cosmetic' },
    { value: 'laser', ar: 'الليزر', en: 'Laser' },
]

const statusLabels = {
    pending: { ar: 'معلق', en: 'Pending', color: 'bg-amber-100 text-amber-700' },
    accepted: { ar: 'مقبول', en: 'Accepted', color: 'bg-slate-100 text-[#1B365D]' },
    scheduled: { ar: 'مجدول', en: 'Scheduled', color: 'bg-slate-100 text-[#1B365D]' },
    completed: { ar: 'مكتمل', en: 'Completed', color: 'bg-emerald-100 text-emerald-700' },
    declined: { ar: 'مرفوض', en: 'Declined', color: 'bg-red-100 text-red-700' },
    cancelled: { ar: 'ملغي', en: 'Cancelled', color: 'bg-gray-100 text-gray-500' },
}

const urgencyLabels = {
    routine: { ar: 'عادي', en: 'Routine', color: 'text-gray-600' },
    urgent: { ar: 'عاجل', en: 'Urgent', color: 'text-[#C4A265]' },
    emergency: { ar: 'طوارئ', en: 'Emergency', color: 'text-red-600' },
}

function statusLabel(s) { return statusLabels[s] || { ar: s, en: s, color: 'bg-gray-100 text-gray-600' } }
function deptLabel(d) { const dept = departments.find(x => x.value === d); return dept ? (isRtl.value ? dept.ar : dept.en) : d }

function applyFilters() {
    router.get('/admin/referrals', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        department: deptFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

// Create form
const createForm = ref({
    patient_id: '',
    referring_doctor_id: '',
    referred_to_doctor_id: '',
    from_department: '',
    to_department: '',
    urgency: 'routine',
    reason: '',
    clinical_notes: '',
    referring_diagnosis: '',
})

const patientSearch = ref('')
const patientResults = ref([])
const selectedPatient = ref(null)
let patientSearchTimeout = null

function searchPatients() {
    clearTimeout(patientSearchTimeout)
    if (patientSearch.value.length < 2) { patientResults.value = []; return }
    patientSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`/admin/patients/search?q=${encodeURIComponent(patientSearch.value)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            if (res.ok) patientResults.value = await res.json()
        } catch (e) { console.error(e) }
    }, 300)
}

function selectPatient(p) {
    selectedPatient.value = p
    createForm.value.patient_id = p.id
    patientSearch.value = ''
    patientResults.value = []
}

const submitting = ref(false)

function submitCreate() {
    submitting.value = true
    router.post('/admin/referrals', createForm.value, {
        onFinish: () => { submitting.value = false },
        onSuccess: () => {
            showCreateModal.value = false
            createForm.value = { patient_id: '', referring_doctor_id: '', referred_to_doctor_id: '', from_department: '', to_department: '', urgency: 'routine', reason: '', clinical_notes: '', referring_diagnosis: '' }
            selectedPatient.value = null
        },
    })
}

// Status change
const statusForm = ref({ status: '', response_notes: '', referred_to_doctor_id: '', scheduled_at: '' })

function openStatusChange(referral) {
    selectedReferral.value = referral
    statusForm.value = { status: '', response_notes: '', referred_to_doctor_id: referral.referred_to_doctor_id || '', scheduled_at: '' }
    showStatusModal.value = true
}

const allowedTransitions = {
    pending: ['accepted', 'declined', 'cancelled'],
    accepted: ['scheduled', 'completed', 'cancelled'],
    scheduled: ['completed', 'cancelled'],
}

function submitStatusChange() {
    if (!selectedReferral.value || !statusForm.value.status) return
    submitting.value = true
    router.post(`/admin/referrals/${selectedReferral.value.id}/status`, statusForm.value, {
        onFinish: () => { submitting.value = false },
        onSuccess: () => { showStatusModal.value = false },
    })
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
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ isRtl ? 'التحويلات بين الأقسام' : 'Inter-department Referrals' }}</h1>
                <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'إدارة تحويلات المرضى بين الأقسام' : 'Manage patient referrals between departments' }}</p>
            </div>
            <button @click="showCreateModal = true" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#1B365D] transition font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ isRtl ? 'تحويل جديد' : 'New Referral' }}
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'معلق' : 'Pending' }}</p>
                        <p class="text-xl font-bold text-gray-800">{{ stats.pending }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'نشط' : 'Active' }}</p>
                        <p class="text-xl font-bold text-gray-800">{{ stats.active }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'مكتمل هذا الشهر' : 'Completed (Month)' }}</p>
                        <p class="text-xl font-bold text-gray-800">{{ stats.completed_month }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ isRtl ? 'عاجل' : 'Urgent' }}</p>
                        <p class="text-xl font-bold" :class="stats.urgent > 0 ? 'text-red-600' : 'text-gray-800'">{{ stats.urgent }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <input v-model="search" @keyup.enter="applyFilters" type="text" :placeholder="isRtl ? 'بحث...' : 'Search...'" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm w-64 focus:ring-[#1B365D] focus:border-[#1B365D]" />
            <select v-model="statusFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ isRtl ? 'كل الحالات' : 'All Statuses' }}</option>
                <option v-for="(lbl, key) in statusLabels" :key="key" :value="key">{{ isRtl ? lbl.ar : lbl.en }}</option>
            </select>
            <select v-model="deptFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                <option v-for="d in departments" :key="d.value" :value="d.value">{{ isRtl ? d.ar : d.en }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'الرقم' : 'Ref #' }}</th>
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'من' : 'From' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'إلى' : 'To' }}</th>
                        <th class="px-4 py-3 text-start">{{ isRtl ? 'السبب' : 'Reason' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الأولوية' : 'Urgency' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-4 py-3 text-center">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="(r, i) in referrals.data" :key="r.id" class="lst-row hover:bg-gray-50/50" :style="{ '--row-i': i }">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-medium text-[#1B365D]">{{ r.referral_number }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ r.patient?.full_name }}</div>
                            <div class="text-xs text-gray-400">{{ r.patient?.file_number }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="text-xs">
                                <span class="px-2 py-0.5 bg-gray-50 rounded-full text-gray-600">{{ deptLabel(r.from_department) }}</span>
                                <div class="text-gray-400 mt-0.5">{{ r.referring_doctor?.name }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="text-xs">
                                <span class="px-2 py-0.5 bg-slate-50 rounded-full text-[#1B365D]">{{ deptLabel(r.to_department) }}</span>
                                <div class="text-gray-400 mt-0.5">{{ r.referred_to_doctor?.name || '-' }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 max-w-[200px] truncate">{{ r.reason || '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs font-medium" :class="urgencyLabels[r.urgency]?.color">
                                {{ isRtl ? urgencyLabels[r.urgency]?.ar : urgencyLabels[r.urgency]?.en }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="statusLabel(r.status).color" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ isRtl ? statusLabel(r.status).ar : statusLabel(r.status).en }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ formatDate(r.created_at) }}</td>
                        <td class="px-4 py-3 text-center">
                            <button v-if="allowedTransitions[r.status]?.length" @click="openStatusChange(r)" class="p-1.5 text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 rounded-lg transition" :aria-label="isRtl ? 'تغيير الحالة' : 'Change status'" :title="isRtl ? 'تغيير الحالة' : 'Change status'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="referrals.data.length === 0" class="text-center py-12 text-gray-400">{{ isRtl ? 'لا توجد تحويلات' : 'No referrals found' }}</div>
        </div>

        <!-- Pagination -->
        <div v-if="referrals.links && referrals.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in referrals.links" :key="link.label" :href="link.url || '#'" class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-[#1B365D] text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
        </div>

        <!-- Create Referral Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showCreateModal = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-5">{{ isRtl ? 'تحويل جديد' : 'New Referral' }}</h2>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <!-- Patient Search -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المريض' : 'Patient' }} *</label>
                            <div v-if="selectedPatient" class="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl">
                                <div><span class="font-medium text-sm text-gray-800">{{ selectedPatient.full_name }}</span><span class="text-xs text-gray-400 ms-2">{{ selectedPatient.file_number }}</span></div>
                                <button type="button" @click="selectedPatient = null; createForm.patient_id = ''" class="text-gray-400 hover:text-red-500" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                            </div>
                            <div v-else class="relative">
                                <input v-model="patientSearch" @input="searchPatients" type="text" :placeholder="isRtl ? 'ابحث عن مريض...' : 'Search patient...'" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                                <div v-if="patientResults.length" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-40 overflow-y-auto">
                                    <button v-for="p in patientResults" :key="p.id" type="button" @click="selectPatient(p)" class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 text-start">
                                        <span class="text-sm text-gray-700">{{ p.full_name }}</span>
                                        <span class="text-xs text-gray-400">{{ p.file_number }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Departments -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'من قسم' : 'From Department' }} *</label>
                                <select v-model="createForm.from_department" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                    <option value="">{{ isRtl ? 'اختر' : 'Select' }}</option>
                                    <option v-for="d in departments" :key="d.value" :value="d.value">{{ isRtl ? d.ar : d.en }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'إلى قسم' : 'To Department' }} *</label>
                                <select v-model="createForm.to_department" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                    <option value="">{{ isRtl ? 'اختر' : 'Select' }}</option>
                                    <option v-for="d in departments" :key="d.value" :value="d.value">{{ isRtl ? d.ar : d.en }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Doctors -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الطبيب المحول' : 'Referring Doctor' }} *</label>
                                <select v-model="createForm.referring_doctor_id" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                    <option value="">{{ isRtl ? 'اختر' : 'Select' }}</option>
                                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المحول إليه' : 'Referred To' }}</label>
                                <select v-model="createForm.referred_to_doctor_id" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                    <option value="">{{ isRtl ? 'غير محدد' : 'Not specified' }}</option>
                                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Urgency -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الأولوية' : 'Urgency' }} *</label>
                            <div class="flex gap-3">
                                <label v-for="u in ['routine','urgent','emergency']" :key="u" class="flex items-center gap-1.5 cursor-pointer">
                                    <input v-model="createForm.urgency" type="radio" :value="u" class="text-[#1B365D]" />
                                    <span class="text-sm" :class="urgencyLabels[u].color">{{ isRtl ? urgencyLabels[u].ar : urgencyLabels[u].en }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'سبب التحويل' : 'Reason' }} *</label>
                            <input v-model="createForm.reason" required type="text" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" :placeholder="isRtl ? 'سبب التحويل...' : 'Reason for referral...'" />
                        </div>

                        <!-- Clinical Notes -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات سريرية' : 'Clinical Notes' }}</label>
                            <textarea v-model="createForm.clinical_notes" rows="2" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" :placeholder="isRtl ? 'ملاحظات...' : 'Notes...'"></textarea>
                        </div>

                        <!-- Diagnosis -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التشخيص المبدئي' : 'Referring Diagnosis' }}</label>
                            <input v-model="createForm.referring_diagnosis" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" @click="showCreateModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                            <button type="submit" :disabled="submitting || !createForm.patient_id" class="px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#1B365D] text-sm font-medium disabled:opacity-50">
                                {{ isRtl ? 'إنشاء التحويل' : 'Create Referral' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Status Change Modal -->
        <Teleport to="body">
            <div v-if="showStatusModal && selectedReferral" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showStatusModal = false" />
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-full sm:max-w-md p-4 md:p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">{{ isRtl ? 'تحديث حالة التحويل' : 'Update Referral Status' }}</h2>
                    <p class="text-sm text-gray-500 mb-4">{{ selectedReferral.referral_number }} - {{ selectedReferral.patient?.full_name }}</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الحالة الجديدة' : 'New Status' }} *</label>
                            <select v-model="statusForm.status" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option value="">{{ isRtl ? 'اختر' : 'Select' }}</option>
                                <option v-for="s in (allowedTransitions[selectedReferral.status] || [])" :key="s" :value="s">
                                    {{ isRtl ? statusLabels[s].ar : statusLabels[s].en }}
                                </option>
                            </select>
                        </div>

                        <div v-if="statusForm.status === 'accepted' || statusForm.status === 'scheduled'">
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الطبيب المعالج' : 'Assigned Doctor' }}</label>
                            <select v-model="statusForm.referred_to_doctor_id" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                                <option value="">{{ isRtl ? 'اختر' : 'Select' }}</option>
                                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>

                        <div v-if="statusForm.status === 'scheduled'">
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'موعد الزيارة' : 'Scheduled Date' }}</label>
                            <input v-model="statusForm.scheduled_at" type="datetime-local" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="statusForm.response_notes" rows="2" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 mt-4 border-t">
                        <button @click="showStatusModal = false" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button @click="submitStatusChange" :disabled="submitting || !statusForm.status" class="px-5 py-2.5 bg-[#1B365D] text-white rounded-xl hover:bg-[#1B365D] text-sm font-medium disabled:opacity-50">
                            {{ isRtl ? 'تحديث' : 'Update' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.lst-row {
    animation: lstRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes lstRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .lst-row { animation: none !important; }
}
</style>
