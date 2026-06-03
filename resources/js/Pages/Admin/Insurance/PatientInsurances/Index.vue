<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { useConfirm } from '@/Composables/useConfirm.js'

defineOptions({ layout: AdminLayout })

const { confirm } = useConfirm()

const props = defineProps({
    insurances: Object,
    companies: Array,
    companiesWithPlans: { type: Array, default: () => [] },
    filters: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')
const cn = (o) => (o ? (isRtl.value ? o.name_ar : o.name_en) : '')

// ── Add-insurance modal ──────────────────────────────────
const showAdd = ref(false)
const selectedPatient = ref(null)
const patientQuery = ref('')
const patientResults = ref([])
let patientTimer = null
const form = useForm({
    insurance_company_id: '', insurance_plan_id: '', member_id: '', policy_number: '',
    card_number: '', relationship: 'self', principal_name: '', start_date: '', expiry_date: '',
    max_annual_limit: '', notes: '', card_image_front: null,
})
const plansForCompany = computed(() => {
    const c = props.companiesWithPlans.find(x => String(x.id) === String(form.insurance_company_id))
    return c?.plans || []
})

function searchPatients() {
    clearTimeout(patientTimer)
    const q = patientQuery.value.trim()
    if (q.length < 2) { patientResults.value = []; return }
    patientTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get('/admin/patients/search', { params: { q } })
            patientResults.value = data
        } catch { patientResults.value = [] }
    }, 300)
}
function pickPatient(p) {
    selectedPatient.value = p
    patientResults.value = []
    patientQuery.value = p.full_name
}
function openAdd() {
    showAdd.value = true
    selectedPatient.value = null
    patientQuery.value = ''
    patientResults.value = []
    ocrHint.value = ''
    ocrError.value = ''
    form.reset()
}
function submitAdd() {
    if (!selectedPatient.value) return
    form.post(`/admin/patients/${selectedPatient.value.id}/insurances`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { showAdd.value = false },
    })
}

// ── AI OCR (gated by insurance_ocr) ──────────────────────
const aiOcrEnabled = computed(() => {
    const ai = page.props.ai
    return !!ai?.enabled && Array.isArray(ai?.features) && ai.features.includes('insurance_ocr')
})
const ocrBusy = ref(false)
const ocrHint = ref('')
const ocrError = ref('')
function onCardChange(e) {
    form.card_image_front = e.target.files?.[0] || null
}
async function extractFromCard() {
    if (!form.card_image_front || ocrBusy.value) return
    ocrBusy.value = true; ocrError.value = ''; ocrHint.value = ''
    try {
        const fd = new FormData()
        fd.append('image', form.card_image_front)
        const { data } = await axios.post('/admin/insurance/patient-insurances/ocr', fd)
        if (data.ok) {
            const f = data.fields || {}
            if (f.member_id) form.member_id = f.member_id
            if (f.policy_number) form.policy_number = f.policy_number
            if (f.card_number) form.card_number = f.card_number
            if (f.principal_name) form.principal_name = f.principal_name
            if (f.expiry_date) form.expiry_date = f.expiry_date
            const hints = [f.company_name, f.plan_name].filter(Boolean)
            ocrHint.value = hints.length
                ? (isRtl.value ? 'من البطاقة: ' : 'From card: ') + hints.join(' · ') + (isRtl.value ? ' (اختر الشركة/الباقة المطابقة)' : ' (select the matching company/plan)')
                : (isRtl.value ? 'تم استخراج الحقول المتاحة.' : 'Extracted available fields.')
        } else { ocrError.value = data.message }
    } catch (e) {
        ocrError.value = e.response?.data?.message || (isRtl.value ? 'تعذّر الاستخراج.' : 'Extraction failed.')
    } finally { ocrBusy.value = false }
}

const search = ref(props.filters?.search || '')
const companyFilter = ref(props.filters?.company_id || '')
const verifiedFilter = ref(props.filters?.verified || '')
const expiredFilter = ref(props.filters?.expired || '')

function applyFilters() {
    router.get('/admin/insurance/patient-insurances', {
        search: search.value || undefined,
        company_id: companyFilter.value || undefined,
        verified: verifiedFilter.value || undefined,
        expired: expiredFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function verify(ins) {
    router.post(`/admin/patient-insurances/${ins.id}/verify`, {}, { preserveScroll: true })
}

function deactivate(ins) {
    confirm(isRtl.value ? 'هل تريد إلغاء تفعيل هذا التأمين؟' : 'Deactivate this insurance?', () => {
        router.post(`/admin/patient-insurances/${ins.id}/delete`, {}, { preserveScroll: true })
    })
}

function expiryStatus(ins) {
    if (!ins.expiry_date) return { label: isRtl.value ? 'بلا انتهاء' : 'No expiry', color: 'bg-gray-50 text-gray-600' }
    const days = Math.floor((new Date(ins.expiry_date) - new Date()) / (1000 * 60 * 60 * 24))
    if (days < 0) return { label: isRtl.value ? 'منتهي' : 'Expired', color: 'bg-red-50 text-red-700' }
    if (days <= 30) return { label: (isRtl.value ? 'ينتهي خلال ' : 'Expires in ') + days + (isRtl.value ? ' يوم' : 'd'), color: 'bg-amber-50 text-amber-700' }
    return { label: isRtl.value ? 'ساري' : 'Active', color: 'bg-emerald-50 text-emerald-700' }
}

function fmt(n) {
    if (!n && n !== 0) return '—'
    return Number(n).toLocaleString()
}
</script>

<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-3xl mb-6 p-7"
             style="background: linear-gradient(135deg, #1B365D 0%, #254677 55%, #1B365D 100%);">
            <div class="absolute inset-0 opacity-20 pointer-events-none"
                 style="background-image: radial-gradient(circle at 20% 50%, #C4A265 0%, transparent 40%);"></div>
            <div class="relative">
                <h1 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl"
                          style="background: rgba(196, 162, 101, 0.2); border: 1px solid rgba(196, 162, 101, 0.4);">
                        <svg class="w-6 h-6 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </span>
                    {{ isRtl ? 'تأمينات المرضى' : 'Patient Insurances' }}
                </h1>
                <p class="text-sm text-white/70 mt-2">{{ isRtl ? 'جميع تأمينات المرضى مع التحقق وحدود الاستخدام' : 'All patient insurance cards with verification and usage' }}</p>
                <button type="button" @click="openAdd"
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#C4A265] text-[#1B365D] text-sm font-bold hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'إضافة تأمين' : 'Add Insurance' }}
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-5 flex flex-wrap gap-3">
            <input v-model="search" @keyup.enter="applyFilters" type="text"
                   :placeholder="isRtl ? 'بحث بالاسم، رقم الملف، رقم العضوية...' : 'Search name, file#, member...'"
                   class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm flex-1 min-w-[220px] focus:ring-[#C4A265] focus:border-[#C4A265]" />
            <select v-model="companyFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'كل الشركات' : 'All Companies' }}</option>
                <option v-for="c in companies" :key="c.id" :value="c.id">{{ isRtl ? c.name_ar : c.name_en }}</option>
            </select>
            <select v-model="verifiedFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'كل الحالات' : 'All' }}</option>
                <option value="1">{{ isRtl ? 'تم التحقق' : 'Verified' }}</option>
                <option value="0">{{ isRtl ? 'بانتظار التحقق' : 'Unverified' }}</option>
            </select>
            <select v-model="expiredFilter" @change="applyFilters" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'السريان' : 'Validity' }}</option>
                <option value="1">{{ isRtl ? 'منتهي فقط' : 'Expired only' }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead style="background: #1B365D;">
                    <tr class="text-white/90 text-xs uppercase">
                        <th class="px-4 py-3 text-start font-semibold">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ isRtl ? 'الشركة/الباقة' : 'Company / Plan' }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ isRtl ? 'العضوية' : 'Member ID' }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ isRtl ? 'الصلاحية' : 'Validity' }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ isRtl ? 'الاستهلاك' : 'Usage' }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ isRtl ? 'التحقق' : 'Verified' }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(ins, i) in insurances.data" :key="ins.id" class="lst-row border-t border-gray-50 hover:bg-gray-50/50" :class="{ 'opacity-60': !ins.is_active }" :style="{ '--row-i': i }">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ ins.patient?.full_name }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ ins.patient?.file_number }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ isRtl ? ins.company?.name_ar : ins.company?.name_en }}</div>
                            <div v-if="ins.plan" class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                <span>{{ isRtl ? ins.plan.name_ar : ins.plan.name_en }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-[#C4A265]/15 text-[#8B6F3F] font-bold">{{ ins.plan.class }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ ins.member_id }}</td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-gray-500">{{ ins.expiry_date || '—' }}</div>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-xs font-medium" :class="expiryStatus(ins).color">
                                {{ expiryStatus(ins).label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div v-if="ins.max_annual_limit">
                                <div class="text-xs text-gray-600 mb-1">
                                    {{ fmt(ins.used_amount) }} / {{ fmt(ins.max_annual_limit) }}
                                </div>
                                <div class="w-32 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all"
                                         :style="{ width: (ins.usage_percentage || 0) + '%', background: (ins.usage_percentage || 0) > 80 ? '#DC2626' : '#C4A265' }"></div>
                                </div>
                            </div>
                            <span v-else class="text-xs text-gray-400">{{ isRtl ? 'بلا حد' : 'No limit' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span v-if="ins.is_verified" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ isRtl ? 'موثق' : 'Verified' }}
                            </span>
                            <span v-else class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                {{ isRtl ? 'بانتظار' : 'Pending' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <Link v-if="ins.patient_id" :href="`/admin/patients/${ins.patient_id}`"
                                      class="p-1.5 text-gray-400 hover:text-[#1B365D] hover:bg-[#C4A265]/10 rounded-lg transition" :title="isRtl ? 'عرض المريض' : 'View patient'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </Link>
                                <button v-if="!ins.is_verified" @click="verify(ins)" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" :title="isRtl ? 'تحقق' : 'Verify'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </button>
                                <button @click="deactivate(ins)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" :title="isRtl ? 'إلغاء تفعيل' : 'Deactivate'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="insurances.data.length === 0">
                        <td colspan="7" class="text-center py-10 text-gray-400">{{ isRtl ? 'لا توجد تأمينات' : 'No insurances' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ═══ Add Insurance modal ═══ -->
        <Transition name="ins-modal">
            <div v-if="showAdd" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="showAdd = false">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[92vh] overflow-y-auto p-5 md:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-[#1B365D]">{{ isRtl ? 'إضافة تأمين لمريض' : 'Add Patient Insurance' }}</h3>
                        <button type="button" @click="showAdd = false" class="text-gray-400 hover:text-gray-600" :title="isRtl ? 'إغلاق' : 'Close'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Patient picker -->
                    <div class="relative mb-3">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'المريض' : 'Patient' }} <span class="text-red-500">*</span></label>
                        <input v-model="patientQuery" @input="searchPatients" type="text" dir="auto"
                            :placeholder="isRtl ? 'ابحث بالاسم/الملف/الهاتف' : 'Search name / file # / phone'"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        <ul v-if="patientResults.length" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <li v-for="p in patientResults" :key="p.id" @click="pickPatient(p)"
                                class="px-3 py-2 text-sm hover:bg-[#C4A265]/10 cursor-pointer flex justify-between">
                                <span>{{ p.full_name }}</span><span class="text-xs text-gray-400 font-mono">{{ p.file_number }}</span>
                            </li>
                        </ul>
                        <p v-if="selectedPatient" class="text-xs text-emerald-600 mt-1">{{ isRtl ? 'محدد: ' : 'Selected: ' }}{{ selectedPatient.full_name }}</p>
                    </div>

                    <!-- AI OCR card upload -->
                    <div class="mb-4 rounded-xl border border-dashed border-[#C4A265]/50 bg-[#C4A265]/5 p-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'صورة بطاقة التأمين (الوجه)' : 'Insurance card photo (front)' }}</label>
                        <input type="file" accept="image/*" @change="onCardChange" class="block w-full text-xs text-gray-600 file:mr-2 file:rtl:ml-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-[#1B365D] file:text-white file:text-xs" />
                        <button v-if="aiOcrEnabled" type="button" @click="extractFromCard" :disabled="!form.card_image_front || ocrBusy"
                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#7C3AED] text-white text-xs font-semibold disabled:opacity-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                            {{ ocrBusy ? (isRtl ? 'جارٍ الاستخراج…' : 'Extracting…') : (isRtl ? 'استخراج البيانات بالـ AI' : 'Extract data with AI') }}
                        </button>
                        <p v-if="ocrHint" class="text-xs text-[#1B365D] mt-1.5">{{ ocrHint }}</p>
                        <p v-if="ocrError" class="text-xs text-amber-700 mt-1.5">{{ ocrError }}</p>
                    </div>

                    <!-- Fields -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الشركة' : 'Company' }} <span class="text-red-500">*</span></label>
                            <select v-model="form.insurance_company_id" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">—</option>
                                <option v-for="c in companiesWithPlans" :key="c.id" :value="c.id">{{ cn(c) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الباقة' : 'Plan' }}</label>
                            <select v-model="form.insurance_plan_id" class="w-full rounded-lg border-gray-300 text-sm" :disabled="!plansForCompany.length">
                                <option value="">—</option>
                                <option v-for="p in plansForCompany" :key="p.id" :value="p.id">{{ cn(p) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'رقم العضوية' : 'Member ID' }} <span class="text-red-500">*</span></label>
                            <input v-model="form.member_id" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'رقم الوثيقة' : 'Policy #' }}</label>
                            <input v-model="form.policy_number" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الصلة' : 'Relationship' }} <span class="text-red-500">*</span></label>
                            <select v-model="form.relationship" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="self">{{ isRtl ? 'نفسه' : 'Self' }}</option>
                                <option value="spouse">{{ isRtl ? 'زوج/ة' : 'Spouse' }}</option>
                                <option value="child">{{ isRtl ? 'ابن/ة' : 'Child' }}</option>
                                <option value="parent">{{ isRtl ? 'والد/ة' : 'Parent' }}</option>
                                <option value="other">{{ isRtl ? 'أخرى' : 'Other' }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'تاريخ الانتهاء' : 'Expiry date' }}</label>
                            <input v-model="form.expiry_date" type="date" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الحد السنوي' : 'Annual limit' }}</label>
                            <input v-model="form.max_annual_limit" type="number" min="0" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'اسم حامل البطاقة' : 'Principal name' }}</label>
                            <input v-model="form.principal_name" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <p v-if="form.errors.member_id || form.errors.insurance_company_id" class="text-xs text-red-600 mt-2">
                        {{ form.errors.member_id || form.errors.insurance_company_id }}
                    </p>

                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" @click="showAdd = false" class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button type="button" @click="submitAdd" :disabled="!selectedPatient || !form.insurance_company_id || !form.member_id || form.processing"
                            class="px-4 py-2 rounded-lg bg-[#1B365D] text-white text-sm font-semibold disabled:opacity-50">
                            {{ form.processing ? (isRtl ? 'جارٍ الحفظ…' : 'Saving…') : (isRtl ? 'حفظ' : 'Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Pagination -->
        <div v-if="insurances.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in insurances.links" :key="link.label" :href="link.url || '#'"
                class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-[#1B365D] text-white' : 'text-gray-500 hover:bg-gray-100'"
                v-html="link.label" preserve-state />
        </div>
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
.ins-modal-enter-active, .ins-modal-leave-active { transition: opacity .2s ease; }
.ins-modal-enter-from, .ins-modal-leave-to { opacity: 0; }
</style>
